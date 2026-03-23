<?php
// admin/hash.php
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Mật khẩu: $password<br>";
echo "Hash: $hash<br>";
echo "<hr>";
echo "INSERT INTO users (Username, password, Ho_ten, email, role, status) VALUES ('admin', '$hash', 'Quản trị viên', 'admin@nvbplay.vn', 'admin', 'active');";
?>