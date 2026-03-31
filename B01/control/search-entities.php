<?php
// control/search_entities.php
header('Content-Type: application/json');

// ✅ Relative path: cùng thư mục control nên dùng '/connect.php'
require_once __DIR__ . '/../control/connect.php';

// ✅ Start session nếu chưa có (AJAX request là request mới)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$type = $_GET['type'] ?? '';
$keyword = trim($_GET['q'] ?? '');
$limit = intval($_GET['limit'] ?? 10);

if (strlen($keyword) < 2 || !in_array($type, ['category', 'brand', 'supplier'])) {
    echo json_encode([]);
    exit;
}

$searchTerm = "%$keyword%";
$results = [];

try {
    switch ($type) {
        case 'category':
            $stmt = $conn->prepare("SELECT Danhmuc_id, Ten_danhmuc FROM danhmuc WHERE Ten_danhmuc LIKE ? ORDER BY Ten_danhmuc LIMIT ?");
            $stmt->bind_param("si", $searchTerm, $limit);
            break;
        case 'brand':
            $stmt = $conn->prepare("SELECT Ma_thuonghieu, Ten_thuonghieu FROM thuonghieu WHERE Ten_thuonghieu LIKE ? ORDER BY Ten_thuonghieu LIMIT ?");
            $stmt->bind_param("si", $searchTerm, $limit);
            break;
        case 'supplier':
            $stmt = $conn->prepare("SELECT NCC_id, Ten_NCC, DiaChi, SDT FROM nhacungcap WHERE Ten_NCC LIKE ? OR SDT LIKE ? ORDER BY Ten_NCC LIMIT ?");
            $stmt->bind_param("ssi", $searchTerm, $searchTerm, $limit);
            break;
        default:
            echo json_encode([]);
            exit;
    }
    
    $stmt->execute();
    $res = $stmt->get_result();
    
    while ($row = $res->fetch_assoc()) {
        $item = [
            'id' => $row[($type === 'category') ? 'Danhmuc_id' : (($type === 'brand') ? 'Ma_thuonghieu' : 'NCC_id')],
            'name' => $row[($type === 'category') ? 'Ten_danhmuc' : (($type === 'brand') ? 'Ten_thuonghieu' : 'Ten_NCC')],
            'type' => $type
        ];
        if ($type === 'supplier' && isset($row['DiaChi'], $row['SDT'])) {
            $item['extra'] = ($row['DiaChi'] ?? '') . ' | ' . ($row['SDT'] ?? '');
        }
        $results[] = $item;
    }
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Search Error [$type]: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
    exit;
}

echo json_encode($results);
$conn->close();
?>