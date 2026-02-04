<?php
// FILE: backend/routes/inventory.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($segments[1]) ? $segments[1] : '';
$method = $_SERVER['REQUEST_METHOD'];

$user = AuthMiddleware::isAuthenticated();
RoleMiddleware::allow(['pharmacist', 'admin'], $user);

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
            if(!isset($data->name) || !isset($data->quantity)) {
                http_response_code(400);
                echo json_encode(["message" => "Name and Quantity are required"]);
                exit;
            }
            $sql = "INSERT INTO medicines (name, batch_number, stock_quantity, unit, expiry_date, price) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $price = isset($data->price) ? $data->price : 0;
            if($stmt->execute([$data->name, $data->batch_number, $data->quantity, $data->unit, $data->expiry_date, $price])) {
                echo json_encode(["message" => "Stock Added Successfully"]);
            } else {
                http_response_code(500);
            }
        }
        break;

    // 3. UPDATE STOCK (Fixed to include price)
    case 'update':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents("php://input"));
            if(!isset($data->id)) { http_response_code(400); exit; }

            // UPDATED: Added price=? to the query
            $sql = "UPDATE medicines SET name=?, batch_number=?, stock_quantity=?, unit=?, expiry_date=?, price=? WHERE id=?";
            $stmt = $db->prepare($sql);
            $price = isset($data->price) ? $data->price : 0;

            if($stmt->execute([$data->name, $data->batch_number, $data->quantity, $data->unit, $data->expiry_date, $price, $data->id])) {
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
