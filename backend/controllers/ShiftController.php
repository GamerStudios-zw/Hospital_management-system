<?php
// FILE: backend/controllers/ShiftController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/DbSchema.php';
require_once __DIR__ . '/../utils/Realtime.php';

class ShiftController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    private function acquireShiftLock($userId, $shiftStart, $shiftEnd) {
        $seed = (int)$userId . '|' . trim((string)$shiftStart) . '|' . trim((string)$shiftEnd);
        $lockKey = 'hms:shift:' . substr(hash('sha256', $seed), 0, 48);
        $stmt = $this->db->prepare("SELECT GET_LOCK(?, 5)");
        $stmt->execute([$lockKey]);
        $acquired = ((int)$stmt->fetchColumn() === 1);
        return [$acquired, $lockKey];
    }

    private function releaseShiftLock($lockKey, $acquired) {
        if (!$acquired) return;
        try {
            $stmt = $this->db->prepare("SELECT RELEASE_LOCK(?)");
            $stmt->execute([$lockKey]);
        } catch (Exception $e) {
            // Ignore unlock failures.
        }
    }

    // Assign a new shift to a staff member
    public function createShift() {
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->user_id) || !isset($data->shift_start) || !isset($data->shift_end)) {
            http_response_code(400);
            echo json_encode(["message" => "Incomplete shift data."]);
            return;
        }

        try {
            DbSchema::ensureStaffShifts($this->db);

            $startTs = strtotime($data->shift_start);
            $endTs = strtotime($data->shift_end);
            if (!$startTs || !$endTs || $endTs <= $startTs) {
                http_response_code(400);
                echo json_encode(["message" => "End time must be after start time."]);
                return;
            }

            [$lockAcquired, $lockKey] = $this->acquireShiftLock($data->user_id, $data->shift_start, $data->shift_end);
            if (!$lockAcquired) {
                http_response_code(429);
                echo json_encode(["message" => "Shift assignment is busy. Please retry."]);
                return;
            }

            try {
                $dupStmt = $this->db->prepare("SELECT id
                                               FROM staff_shifts
                                               WHERE user_id = ?
                                                 AND shift_start = ?
                                                 AND shift_end = ?
                                               LIMIT 1");
                $dupStmt->execute([(int)$data->user_id, $data->shift_start, $data->shift_end]);
                $duplicate = $dupStmt->fetch(PDO::FETCH_ASSOC);
                if ($duplicate) {
                    http_response_code(409);
                    echo json_encode([
                        "message" => "An identical shift already exists for this staff member.",
                        "shift_id" => (int)$duplicate['id']
                    ]);
                    return;
                }

                $columns = [];
                $colStmt = $this->db->query("SHOW COLUMNS FROM staff_shifts");
                foreach ($colStmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
                    $columns[$col['Field']] = true;
                }

                $insertCols = [];
                $insertVals = [];
                $params = [];

                $maybeAdd = function ($col, $val) use (&$insertCols, &$insertVals, &$params, $columns) {
                    if (isset($columns[$col])) {
                        $insertCols[] = $col;
                        $insertVals[] = '?';
                        $params[] = $val;
                    }
                };

                $status = $data->status ?? 'Confirmed';
                $ward = $data->ward_name ?? 'General';
                $role = $data->role ?? null;
                $shiftType = $data->shift_type ?? null;

                $maybeAdd('user_id', $data->user_id);
                $maybeAdd('role', $role);
                $maybeAdd('shift_start', $data->shift_start);
                $maybeAdd('shift_end', $data->shift_end);
                $maybeAdd('shift_type', $shiftType);
                $maybeAdd('ward_name', $ward);
                $maybeAdd('status', $status);

                if (empty($insertCols)) {
                    http_response_code(500);
                    echo json_encode(["message" => "staff_shifts schema is missing required columns."]);
                    return;
                }

                $sql = "INSERT INTO staff_shifts (" . implode(', ', $insertCols) . ")
                        VALUES (" . implode(', ', $insertVals) . ")";
                $stmt = $this->db->prepare($sql);

                if ($stmt->execute($params)) {
                    $newId = $this->db->lastInsertId();
                    Realtime::emit('shifts.add', ['shift_id' => $newId]);
                    echo json_encode(["message" => "Shift assigned successfully."]);
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Failed to assign shift."]);
                }
            } catch (PDOException $e) {
                if ((string)$e->getCode() === '23000') {
                    http_response_code(409);
                    echo json_encode(["message" => "An identical shift already exists for this staff member."]);
                    return;
                }
                throw $e;
            } finally {
                $this->releaseShiftLock($lockKey, $lockAcquired);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Database error: " . $e->getMessage()]);
        }
    }

    // Get all shifts (for Admin Roster View)
    public function getAllShifts() {
        try {
            $query = "SELECT s.*, u.full_name, u.role 
                      FROM staff_shifts s 
                      JOIN users u ON s.user_id = u.id 
                      ORDER BY s.shift_start DESC";
            $stmt = $this->db->query($query);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Failed to fetch roster."]);
        }
    }
}
