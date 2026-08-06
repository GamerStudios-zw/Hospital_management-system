<?php
require_once __DIR__ . '/../models/Ward.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../utils/ActivityLogger.php';

class WardController {
    private $db;
    private $ward;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->ward = new Ward($this->db);
    }

    private function normalizeWardName($name) {
        $name = preg_replace('/\s+/', ' ', trim((string)$name));
        return $name ?? '';
    }

    private function findWardByName($name, $excludeId = null) {
        $sql = "SELECT id, name FROM wards WHERE UPPER(TRIM(name)) = UPPER(TRIM(?))";
        $params = [$name];
        if ($excludeId !== null) {
            $sql .= " AND id <> ?";
            $params[] = (int)$excludeId;
        }
        $sql .= " ORDER BY id ASC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function acquireWardLock($name) {
        $seed = strtolower($this->normalizeWardName($name));
        $lockKey = 'hms:ward:' . substr(hash('sha256', $seed), 0, 48);
        $stmt = $this->db->prepare("SELECT GET_LOCK(?, 5)");
        $stmt->execute([$lockKey]);
        $acquired = ((int)$stmt->fetchColumn() === 1);
        return [$acquired, $lockKey];
    }

    private function releaseWardLock($lockKey, $acquired) {
        if (!$acquired) return;
        try {
            $stmt = $this->db->prepare("SELECT RELEASE_LOCK(?)");
            $stmt->execute([$lockKey]);
        } catch (Exception $e) {
            // Ignore unlock failures.
        }
    }

    // GET /wards or GET /wards?id=1
    public function listOrGet() {
        try {
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                $row = $this->ward->getById($id);
                if ($row) {
                    echo json_encode($row);
                    return;
                }
                http_response_code(404);
                echo json_encode(["message" => "Ward not found"]);
                return;
            }

            $stmt = $this->ward->readAll();
            $rows = [];
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) { $rows[] = $r; }
            echo json_encode($rows);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error: " . $e->getMessage()]);
        }
    }

    // POST /wards/add
    public function create() {
        RoleMiddleware::allow(['admin'], AuthMiddleware::isAuthenticated());
        $data = json_decode(file_get_contents('php://input'));
        $name = $this->normalizeWardName($data->name ?? '');
        if ($name === '') {
            http_response_code(400);
            echo json_encode(["message" => "Name is required"]);
            return;
        }

        [$lockAcquired, $lockKey] = $this->acquireWardLock($name);
        if (!$lockAcquired) {
            http_response_code(429);
            echo json_encode(["message" => "Ward creation is busy. Please retry."]);
            return;
        }

        try {
            $duplicate = $this->findWardByName($name);
            if ($duplicate) {
                http_response_code(409);
                echo json_encode([
                    "message" => "A ward with this name already exists.",
                    "ward_id" => (int)$duplicate['id']
                ]);
                return;
            }

            $this->ward->name = $name;
            $this->ward->capacity = max(0, isset($data->capacity) ? (int)$data->capacity : 0);
            $this->ward->notes = $data->notes ?? '';
            $this->ward->is_active = $data->is_active ?? 1;

            if ($this->ward->create()) {
                http_response_code(201);
                echo json_encode(["message" => "Ward created"]);
                return;
            }
            http_response_code(503);
            echo json_encode(["message" => "Unable to create ward"]);
        } catch (PDOException $e) {
            if ((string)$e->getCode() === '23000') {
                http_response_code(409);
                echo json_encode(["message" => "A ward with this name already exists."]);
                return;
            }
            throw $e;
        } finally {
            $this->releaseWardLock($lockKey, $lockAcquired);
        }
    }

    // PUT /wards?id= - update
    public function update() {
        RoleMiddleware::allow(['admin'], AuthMiddleware::isAuthenticated());
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["message" => "Valid id required"]);
            return;
        }
        $data = json_decode(file_get_contents('php://input'));
        $name = $this->normalizeWardName($data->name ?? '');
        if ($name === '') {
            http_response_code(400);
            echo json_encode(["message" => "Name is required"]);
            return;
        }

        [$lockAcquired, $lockKey] = $this->acquireWardLock($name);
        if (!$lockAcquired) {
            http_response_code(429);
            echo json_encode(["message" => "Ward update is busy. Please retry."]);
            return;
        }

        try {
            $duplicate = $this->findWardByName($name, $id);
            if ($duplicate) {
                http_response_code(409);
                echo json_encode([
                    "message" => "A ward with this name already exists.",
                    "ward_id" => (int)$duplicate['id']
                ]);
                return;
            }

            $this->ward->name = $name;
            $this->ward->capacity = max(0, isset($data->capacity) ? (int)$data->capacity : 0);
            $this->ward->notes = $data->notes ?? '';
            $this->ward->is_active = $data->is_active ?? 1;

            if ($this->ward->update($id)) {
                echo json_encode(["message" => "Ward updated"]);
                return;
            }
            http_response_code(503);
            echo json_encode(["message" => "Unable to update ward"]);
        } catch (PDOException $e) {
            if ((string)$e->getCode() === '23000') {
                http_response_code(409);
                echo json_encode(["message" => "A ward with this name already exists."]);
                return;
            }
            throw $e;
        } finally {
            $this->releaseWardLock($lockKey, $lockAcquired);
        }
    }

    // DELETE /wards?id=
    public function delete() {
        RoleMiddleware::allow(['admin'], AuthMiddleware::isAuthenticated());
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(["message" => "Valid id required"]);
            return;
        }
        if ($this->ward->delete($id)) {
            echo json_encode(["message" => "Ward deleted"]);
            return;
        }
        http_response_code(503);
        echo json_encode(["message" => "Unable to delete ward"]);
    }
}
?>
