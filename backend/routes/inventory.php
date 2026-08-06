<?php
// FILE: backend/routes/inventory.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';

$database = new Database();
$db = $database->getConnection();

$medicineColumns = null;
$getMedicineColumns = function() use ($db, &$medicineColumns) {
    if ($medicineColumns !== null) return $medicineColumns;
    $medicineColumns = [];
    try {
        $cols = $db->query("SHOW COLUMNS FROM medicines")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($cols as $col) {
            if (!empty($col['Field'])) {
                $medicineColumns[$col['Field']] = true;
            }
        }
    } catch (Exception $e) {
        // Keep empty map on failure.
    }
    return $medicineColumns;
};
$hasMedicineColumn = function($column) use (&$getMedicineColumns) {
    $cols = $getMedicineColumns();
    return isset($cols[$column]);
};

$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

$user = AuthMiddleware::isAuthenticated();
RoleMiddleware::allow(['senior_pharmacist'], $user);

switch ($action) {
    // 1. GET ALL STOCK
    case 'list':
        if ($method === 'GET') {
            $stmt = $db->query("SELECT *,
                DATEDIFF(expiry_date, CURDATE()) AS days_to_expiry,
                CASE
                    WHEN expiry_date IS NULL THEN 0
                    WHEN expiry_date < CURDATE() THEN 1
                    WHEN expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) THEN 2
                    ELSE 0
                END AS expiry_alert
            FROM medicines ORDER BY name ASC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    // 2. ADD NEW STOCK
    case 'add':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            $name = preg_replace('/\s+/', ' ', trim((string)($data->name ?? '')));
            $quantity = (int)($data->quantity ?? 0);
            if($name === '' || $quantity <= 0) {
                http_response_code(400);
                echo json_encode(["message" => "Name and Quantity are required"]);
                exit;
            }

            $batch = trim((string)($data->batch_number ?? ''));
            $unit = trim((string)($data->unit ?? ''));
            $expiry = trim((string)($data->expiry_date ?? ''));
            $expiry = $expiry !== '' ? $expiry : null;
            $price = isset($data->price) && $data->price !== '' ? (float)$data->price : 0.0;

            $lockSeedParts = [strtolower($name)];
            if ($hasMedicineColumn('batch_number')) $lockSeedParts[] = strtolower($batch);
            if ($hasMedicineColumn('unit')) $lockSeedParts[] = strtolower($unit);
            if ($hasMedicineColumn('expiry_date')) $lockSeedParts[] = (string)$expiry;
            if ($hasMedicineColumn('price')) $lockSeedParts[] = number_format($price, 2, '.', '');
            $lockKey = 'hms:stock:' . substr(hash('sha256', implode('|', $lockSeedParts)), 0, 48);
            $lockAcquired = false;

            try {
                $lockStmt = $db->prepare("SELECT GET_LOCK(?, 5)");
                $lockStmt->execute([$lockKey]);
                $lockAcquired = ((int)$lockStmt->fetchColumn() === 1);
                if (!$lockAcquired) {
                    http_response_code(429);
                    echo json_encode(["message" => "Stock update is busy. Please retry."]);
                    exit;
                }

                $where = ["UPPER(TRIM(name)) = UPPER(TRIM(?))"];
                $params = [$name];
                if ($hasMedicineColumn('batch_number')) {
                    $where[] = "COALESCE(UPPER(TRIM(batch_number)), '') = COALESCE(UPPER(TRIM(?)), '')";
                    $params[] = $batch;
                }
                if ($hasMedicineColumn('unit')) {
                    $where[] = "COALESCE(UPPER(TRIM(unit)), '') = COALESCE(UPPER(TRIM(?)), '')";
                    $params[] = $unit;
                }
                if ($hasMedicineColumn('expiry_date')) {
                    $where[] = "expiry_date <=> ?";
                    $params[] = $expiry;
                }
                if ($hasMedicineColumn('price')) {
                    $where[] = "price = ?";
                    $params[] = $price;
                }

                $dupSql = "SELECT id FROM medicines WHERE " . implode(" AND ", $where) . " LIMIT 1";
                $dupStmt = $db->prepare($dupSql);
                $dupStmt->execute($params);
                $existing = $dupStmt->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    $upd = $db->prepare("UPDATE medicines SET stock_quantity = stock_quantity + ? WHERE id = ?");
                    $upd->execute([$quantity, $existing['id']]);
                    echo json_encode([
                        "message" => "Duplicate stock merged with existing medicine entry.",
                        "medicine_id" => (int)$existing['id'],
                        "merged" => true
                    ]);
                    exit;
                }

                $insertCols = ['name', 'stock_quantity'];
                $insertVals = ['?', '?'];
                $insertParams = [$name, $quantity];

                if ($hasMedicineColumn('batch_number')) { $insertCols[] = 'batch_number'; $insertVals[] = '?'; $insertParams[] = ($batch !== '' ? $batch : null); }
                if ($hasMedicineColumn('unit')) { $insertCols[] = 'unit'; $insertVals[] = '?'; $insertParams[] = ($unit !== '' ? $unit : null); }
                if ($hasMedicineColumn('expiry_date')) { $insertCols[] = 'expiry_date'; $insertVals[] = '?'; $insertParams[] = $expiry; }
                if ($hasMedicineColumn('price')) { $insertCols[] = 'price'; $insertVals[] = '?'; $insertParams[] = $price; }

                $sql = "INSERT INTO medicines (" . implode(', ', $insertCols) . ") VALUES (" . implode(', ', $insertVals) . ")";
                $stmt = $db->prepare($sql);

                if($stmt->execute($insertParams)) {
                    echo json_encode(["message" => "Stock Added Successfully", "merged" => false]);
                } else {
                    http_response_code(500);
                }
            } catch (PDOException $e) {
                if ((string)$e->getCode() === '23000') {
                    http_response_code(409);
                    echo json_encode(["message" => "An identical medicine entry already exists."]);
                } else {
                    throw $e;
                }
            } finally {
                if ($lockAcquired) {
                    try {
                        $unlockStmt = $db->prepare("SELECT RELEASE_LOCK(?)");
                        $unlockStmt->execute([$lockKey]);
                    } catch (Exception $e) {
                        // Ignore unlock failures.
                    }
                }
            }
        }
        break;

    // 3. UPDATE STOCK (Fixed to include price)
    case 'update':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if(!isset($data->id)) { http_response_code(400); exit; }
            $id = (int)$data->id;
            $name = preg_replace('/\s+/', ' ', trim((string)($data->name ?? '')));
            $quantity = (int)($data->quantity ?? 0);
            if ($id <= 0 || $name === '' || $quantity < 0) {
                http_response_code(400);
                echo json_encode(["message" => "Valid stock data is required"]);
                exit;
            }

            $batch = trim((string)($data->batch_number ?? ''));
            $unit = trim((string)($data->unit ?? ''));
            $expiry = trim((string)($data->expiry_date ?? ''));
            $expiry = $expiry !== '' ? $expiry : null;
            $price = isset($data->price) && $data->price !== '' ? (float)$data->price : 0.0;

            $where = ["UPPER(TRIM(name)) = UPPER(TRIM(?))", "id <> ?"];
            $params = [$name, $id];
            if ($hasMedicineColumn('batch_number')) {
                $where[] = "COALESCE(UPPER(TRIM(batch_number)), '') = COALESCE(UPPER(TRIM(?)), '')";
                $params[] = $batch;
            }
            if ($hasMedicineColumn('unit')) {
                $where[] = "COALESCE(UPPER(TRIM(unit)), '') = COALESCE(UPPER(TRIM(?)), '')";
                $params[] = $unit;
            }
            if ($hasMedicineColumn('expiry_date')) {
                $where[] = "expiry_date <=> ?";
                $params[] = $expiry;
            }
            if ($hasMedicineColumn('price')) {
                $where[] = "price = ?";
                $params[] = $price;
            }
            $dupStmt = $db->prepare("SELECT id FROM medicines WHERE " . implode(" AND ", $where) . " LIMIT 1");
            $dupStmt->execute($params);
            $duplicate = $dupStmt->fetch(PDO::FETCH_ASSOC);
            if ($duplicate) {
                http_response_code(409);
                echo json_encode([
                    "message" => "An identical medicine entry already exists.",
                    "medicine_id" => (int)$duplicate['id']
                ]);
                exit;
            }

            $set = ["name = ?", "stock_quantity = ?"];
            $setParams = [$name, $quantity];
            if ($hasMedicineColumn('batch_number')) { $set[] = "batch_number = ?"; $setParams[] = ($batch !== '' ? $batch : null); }
            if ($hasMedicineColumn('unit')) { $set[] = "unit = ?"; $setParams[] = ($unit !== '' ? $unit : null); }
            if ($hasMedicineColumn('expiry_date')) { $set[] = "expiry_date = ?"; $setParams[] = $expiry; }
            if ($hasMedicineColumn('price')) { $set[] = "price = ?"; $setParams[] = $price; }
            $setParams[] = $id;

            $sql = "UPDATE medicines SET " . implode(', ', $set) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            if($stmt->execute($setParams)) {
                echo json_encode(["message" => "Stock Updated"]);
            } else {
                http_response_code(500);
            }
        }
        break;

    // 4. INVENTORY REPORTS (New Case for Analytics)
    case 'reports':
        if ($method === 'GET') {
            $type = $_GET['type'] ?? 'summary';
            if ($type === 'summary') {
                $stats = [
                    "total_value" => $db->query("SELECT SUM(stock_quantity * price) FROM medicines")->fetchColumn() ?: 0,
                    "low_stock_count" => $db->query("SELECT COUNT(*) FROM medicines WHERE stock_quantity < 20")->fetchColumn(),
                    "expiring_soon" => $db->query("SELECT COUNT(*) FROM medicines WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)")->fetchColumn()
                ];
                echo json_encode($stats);
            }
        }
        break;

    // 5. DELETE STOCK
    case 'delete':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if(!isset($data->id)) { http_response_code(400); exit; }
            $stmt = $db->prepare("DELETE FROM medicines WHERE id = ?");
            if($stmt->execute([$data->id])) {
                echo json_encode(["message" => "Stock Deleted"]);
            } else {
                http_response_code(500);
            }
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Inventory endpoint not found"]);
        break;
}
?>
