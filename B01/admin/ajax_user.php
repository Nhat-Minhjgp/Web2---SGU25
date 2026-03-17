<?php
session_start();
require_once __DIR__ . '/../control/connect.php';
require_once __DIR__ . '/function.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit();
}

// Đọc dữ liệu JSON từ request
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

switch ($action) {
    case 'add':
        // Thêm admin mới
        $result = addUser($conn, [
            'username' => $data['username'],
            'password' => $data['password'],
            'ho_ten' => $data['fullname'],
            'email' => $data['email'],
            'sdt' => $data['phone'],
            'role' => $data['role'],
            'status' => 'active'
        ]);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Thêm thành công' : 'Có lỗi xảy ra'
        ]);
        break;

    case 'update':
        // Cập nhật thông tin
        $result = updateUser($conn, $data['user_id'], [
            'ho_ten' => $data['fullname'],
            'email' => $data['email'],
            'sdt' => $data['phone'],
            'role' => $data['role'],
            'status' => 'active'
        ]);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Cập nhật thành công' : 'Có lỗi xảy ra'
        ]);
        break;

    case 'reset_password':
        // Đặt lại mật khẩu
        $result = changePassword($conn, $data['user_id'], $data['new_password']);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Đặt lại mật khẩu thành công' : 'Có lỗi xảy ra'
        ]);
        break;

    case 'toggle_status':
        // Khóa/Mở khóa tài khoản
        $result = toggleUserStatus($conn, $data['user_id'], $data['status']);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Cập nhật trạng thái thành công' : 'Có lỗi xảy ra'
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
}
?>