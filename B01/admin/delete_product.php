<?php
session_start();
require_once __DIR__ . '/../control/connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ!']);
        exit();
    }
    
    $check_sql = "SELECT TenSP, SoLuongTon, TrangThai FROM sanpham WHERE SanPham_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại!']);
        exit();
    }
    
    $product = $result->fetch_assoc();
    
    // Kiểm tra đơn hàng
    $check_order = "SELECT COUNT(*) as total FROM chitiethoadon WHERE SanPham_id = ?";
    $stmt = $conn->prepare($check_order);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $order_count = $stmt->get_result()->fetch_assoc()['total'];
    
    if ($order_count > 0) {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa sản phẩm này vì đã có trong đơn hàng!']);
        exit();
    }
    
    // Kiểm tra đã nhập hàng chưa
    $check_import = "SELECT COUNT(*) as total FROM chitietphieunhap WHERE SanPham_id = ?";
    $stmt = $conn->prepare($check_import);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $import_count = $stmt->get_result()->fetch_assoc()['total'];
    
    $conn->begin_transaction();
    
    try {
        if ($import_count == 0) {
            // Chưa nhập hàng -> xóa hẳn
            $sql = "DELETE FROM sanpham WHERE SanPham_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $message = 'xóa vĩnh viễn sản phẩm "' . htmlspecialchars($product['TenSP']) . '"!';
            $action = 'delete';
        } else {
            // Đã nhập hàng -> chỉ ẩn
            if ($product['TrangThai'] == 0) {
                $message = 'Sản phẩm "' . htmlspecialchars($product['TenSP']) . '" đã được ẩn trước đó!';
                $action = 'already_hidden';
            } else {
                $sql = "UPDATE sanpham SET TrangThai = 0 WHERE SanPham_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                
                $message = 'đã được ẩn sản phẩm "' . htmlspecialchars($product['TenSP']) . '" (vì đã có lịch sử nhập hàng)!';
                $action = 'hide';
            }
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'action' => $action  // Trả về action để JS xử lý
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
    }
    
    exit();
}

header('Location: product.php');
exit();
?>