<?php
session_start();
require_once __DIR__ . '/../control/connect.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
    exit();
}

// Kiểm tra nếu là request POST (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ!']);
        exit();
    }
    
    // Kiểm tra sản phẩm có tồn tại không
    $check_sql = "SELECT TenSP FROM sanpham WHERE SanPham_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại!']);
        exit();
    }
    
    $product = $result->fetch_assoc();
    
    // Kiểm tra sản phẩm có trong đơn hàng không
    $check_order = "SELECT COUNT(*) as total FROM chitiethoadon WHERE SanPham_id = ?";
    $stmt = $conn->prepare($check_order);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $order_result = $stmt->get_result();
    $order_count = $order_result->fetch_assoc()['total'];
    
    if ($order_count > 0) {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa sản phẩm này vì đã có trong đơn hàng!']);
        exit();
    }
    
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    try {
        // Xóa khỏi bảng chitietphieunhap trước
        $sql1 = "DELETE FROM chitietphieunhap WHERE SanPham_id = ?";
        $stmt = $conn->prepare($sql1);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Xóa khỏi bảng sanpham
        $sql2 = "DELETE FROM sanpham WHERE SanPham_id = ?";
        $stmt = $conn->prepare($sql2);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Đã xóa sản phẩm "' . htmlspecialchars($product['TenSP']) . '" thành công!'
        ]);
        
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
    }
    
    exit();
}

// Nếu không phải POST, chuyển hướng về trang product
header('Location: product.php');
exit();
?>