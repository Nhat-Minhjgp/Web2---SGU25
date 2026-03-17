<?php
// admin/reset_all_accounts.php
require_once __DIR__ . '/../control/connect.php';

$admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
$staff_pass = password_hash('staff123', PASSWORD_DEFAULT);

// Xóa tài khoản cũ
$conn->query("DELETE FROM users WHERE Username IN ('admin', 'staff1', 'staff2')");

// Tạo lại
$sql = "INSERT INTO users (Username, password, Ho_ten, email, role, status, created_at) VALUES 
        ('admin', '$admin_pass', 'Quản trị viên', 'admin@nvbplay.vn', 'admin', 'active', NOW()),
        ('staff1', '$staff_pass', 'Nhân viên 1', 'staff1@nvbplay.vn', 'staff', 'active', NOW()),
        ('staff2', '$staff_pass', 'Nhân viên 2', 'staff2@nvbplay.vn', 'staff', 'active', NOW())";

if ($conn->multi_query($sql)) {
    echo "✅ Đã tạo lại tài khoản thành công!<br>";
    echo "Admin: admin / admin123<br>";
    echo "Staff1: staff1 / staff123<br>";
    echo "Staff2: staff2 / staff123<br>";
    echo "<br><a href='index.php'>Đến trang đăng nhập</a>";
} else {
    echo "❌ Lỗi: " . $conn->error;
}
?>