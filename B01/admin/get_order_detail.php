<?php
// admin/get_order_detail.php - API lấy chi tiết đơn hàng
session_start();
require_once __DIR__ . '/../control/connect.php';
require_once __DIR__ . '/../control/function.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit();
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ']);
    exit();
}

// Lấy thông tin đơn hàng
$sql = "SELECT d.*, u.Ho_ten as customer_name, u.SDT as customer_phone, u.email as customer_email
        FROM donhang d
        LEFT JOIN users u ON d.User_id = u.User_id
        WHERE d.DonHang_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
    exit();
}

// Lấy địa chỉ giao hàng
$address = null;
if ($order['DiaChi_id']) {
    $stmt = $conn->prepare("SELECT * FROM diachigh WHERE add_id = ?");
    $stmt->bind_param("i", $order['DiaChi_id']);
    $stmt->execute();
    $address = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Lấy chi tiết sản phẩm
$stmt = $conn->prepare("
    SELECT ctdh.*, sp.TenSP, sp.image_url
    FROM chitiethoadon ctdh
    LEFT JOIN sanpham sp ON ctdh.SanPham_id = sp.SanPham_id
    WHERE ctdh.DonHang_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode([
    'success' => true,
    'order' => $order,
    'address' => $address,
    'items' => $items
]);
?>