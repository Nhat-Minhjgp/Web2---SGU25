<?php
session_start();
require_once 'connect.php';

$secure_cookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] == 443);

// 1. Xóa token trong DB nếu đang đăng nhập
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("UPDATE users SET remember_token = NULL WHERE User_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}

// 2. Dọn session & cookie
$_SESSION = [];
if (isset($_COOKIE['auth_remember'])) {
    setcookie('auth_remember', '', time() - 3600, '/', '', $secure_cookie, true);
}
session_destroy();

header("Location: ../index.php");
exit();
?>