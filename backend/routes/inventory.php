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
RoleMiddleware::allow(['senior_pharmacist'], $user);
$normalizeCategory = function ($category): string {
    $value = trim((string)$category);
    if ($value === '') return 'General';
    $allowed = ['General', 'Tablet', 'Capsule', 'Syrup', 'Injection', 'Consumable'];
    foreach ($allowed as $item) {
        if (strcasecmp($item, $value) === 0) return $item;
    }
    return 'General';
};
$extractMedicationMeta = function ($data, string $category) {
    $spec = trim((string)($data->meta_spec ?? ''));
    $route = strtoupper(trim((string)($data->meta_route ?? '')));
    $description = trim((string)($data->description ?? ''));

    if (in_array($category, ['Tablet', 'Capsule', 'Syrup'], true) && $spec === '') {
        $fieldLabel = $category === 'Syrup' ? 'Concentration' : 'Strength';
        http_response_code(400);
        echo json_encode(["message" => $fieldLabel . " is required for {$category} category."]);
        exit;
    }

    if (strcasecmp($category, 'Injection') === 0) {
        if ($spec === '') {
            http_response_code(400);
            echo json_encode(["message" => "Concentration is required for Injection category."]);
            exit;
        }
        $allowedRoutes = ['IV', 'IM', 'SC', 'ID'];
        if (!in_array($route, $allowedRoutes, true)) {
            http_response_code(400);
            echo json_encode(["message" => "Route is required for Injection category (IV/IM/SC/ID)."]);
            exit;
        }
    } else {
        $route = '';
    }

    if ($description === '') {
        $parts = [];
        if ($spec !== '') {
            $specLabel = strcasecmp($category, 'Injection') === 0 || strcasecmp($category, 'Syrup') === 0
                ? 'Concentration'
                : (in_array($category, ['Tablet', 'Capsule'], true) ? 'Strength' : 'Specification');
            $parts[] = $specLabel . ': ' . $spec;
        }
        if ($route !== '') $parts[] = 'Route: ' . $route;
        $description = implode(' | ', $parts);
    }

    return [$spec, $route, $description !== '' ? $description : null];
};

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
            $data = RequestValidator::json();
            if(!isset($data->name) || !isset($data->quantity)) {
                http_response_code(400);
                echo json_encode(["message" => "Name and Quantity are required"]);
                exit;
            }
            $category = $normalizeCategory($data->category ?? 'General');
            [, , $description] = $extractMedicationMeta($data, $category);
            $sql = "INSERT INTO medicines (name, description, category, batch_number, stock_quantity, unit, expiry_date, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $price = isset($data->price) ? $data->price : 0;
            if($stmt->execute([$data->name, $description, $category, $data->batch_number, $data->quantity, $data->unit, $data->expiry_date, $price])) {
                echo json_encode(["message" => "Stock Added Successfully"]);
            } else {
                http_response_code(500);
            }
        }
        break;

    // 3. UPDATE STOCK (Fixed to include price)
    case 'update':
        if ($method === 'POST') {
            $data = RequestValidator::json();
            if(!isset($data->id)) { http_response_code(400); exit; }

            $category = $normalizeCategory($data->category ?? 'General');
            [, , $description] = $extractMedicationMeta($data, $category);
            $sql = "UPDATE medicines SET name=?, description=?, category=?, batch_number=?, stock_quantity=?, unit=?, expiry_date=?, price=? WHERE id=?";
            $stmt = $db->prepare($sql);
            $price = isset($data->price) ? $data->price : 0;

            if($stmt->execute([$data->name, $description, $category, $data->batch_number, $data->quantity, $data->unit, $data->expiry_date, $price, $data->id])) {
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
            $data = RequestValidator::json();
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
