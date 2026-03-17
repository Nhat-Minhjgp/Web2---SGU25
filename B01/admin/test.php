<?php
// admin/test_connection.php
require_once __DIR__ . '/../control/connect.php';

echo "<h2>Kiểm tra kết nối database</h2>";

// Kiểm tra kết nối
if ($conn) {
    echo "✅ Kết nối database thành công!<br>";
    echo "Database: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "<br>";
} else {
    echo "❌ Kết nối database thất bại!<br>";
}

// Kiểm tra bảng users
$result = $conn->query("SELECT COUNT(*) as total FROM users");
$row = $result->fetch_assoc();
echo "✅ Tổng số users: " . $row['total'] . "<br>";

// Kiểm tra tài khoản admin
$check = $conn->query("SELECT * FROM users WHERE Username = 'admin'");
if ($check->num_rows > 0) {
    $user = $check->fetch_assoc();
    echo "✅ Tìm thấy tài khoản admin<br>";
    echo "Username: " . $user['Username'] . "<br>";
    echo "Role: " . $user['role'] . "<br>";
    echo "Status: " . $user['status'] . "<br>";
    
    // Test mật khẩu
    $test_password = 'admin123';
    if (password_verify($test_password, $user['password'])) {
        echo "✅ Mật khẩu 'admin123' ĐÚNG!<br>";
    } else {
        echo "❌ Mật khẩu 'admin123' SAI!<br>";
        echo "Hash trong DB: " . $user['password'] . "<br>";
    }
} else {
    echo "❌ KHÔNG tìm thấy tài khoản admin!<br>";
}
?>