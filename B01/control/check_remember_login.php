<?php
// control/check_remember_login.php
// Tự động nhận diện môi trường để bật/tắt flag secure
$secure_cookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] == 443);

if (!isset($_SESSION['user_id']) && isset($_COOKIE['auth_remember'])) {
    if (!isset($conn)) require_once __DIR__ . '/connect.php';

    $token = $_COOKIE['auth_remember'];
    $stmt = $conn->prepare("SELECT User_id, Username, Ho_ten, email, role, status FROM users WHERE remember_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Chặn tài khoản khóa hoặc staff/admin
        if ($user['status'] != 1 || $user['role'] == 1) {
            $clear = $conn->prepare("UPDATE users SET remember_token = NULL WHERE User_id = ?");
            $clear->bind_param("i", $user['User_id']);
            $clear->execute(); $clear->close();
            setcookie('auth_remember', '', time() - 3600, '/', '', $secure_cookie, true);
        } else {
            session_regenerate_id(true); // Chống Session Fixation
            $_SESSION['user_id']  = $user['User_id'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['ho_ten']   = $user['Ho_ten'];
            $_SESSION['email']    = $user['email'];
            $_SESSION['role']     = $user['role'];
        }
    } else {
        // Token không hợp lệ
        setcookie('auth_remember', '', time() - 3600, '/', '', $secure_cookie, true);
    }
    $stmt->close();
}
?>