<?php
$server = 'localhost';
$username = 'b01_nhahodau';       
$password = 'hFyTCnjtk2elaScb';    
$database = 'b01_nhahodau';

$conn = new mysqli($server, $username, $password, $database);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
} else {
    $conn->set_charset("utf8mb4");
    // echo "Kết nối thành công!";
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);    
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}
?>