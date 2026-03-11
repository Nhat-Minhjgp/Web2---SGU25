<?php
// Kết nối PDO
$host = 'localhost'; $db = 'b01_nhahodau'; $user = 'root'; $pass = '';
$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);

$key = isset($_GET['key']) ? $_GET['key'] : '';

// Tìm sản phẩm theo tên (Like %...%)
$stmt = $pdo->prepare("SELECT SanPham_id, TenSP FROM sanpham WHERE TenSP LIKE ? LIMIT 10");
$stmt->execute(["%$key%"]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Trả về định dạng JSON
header('Content-Type: application/json');
echo json_encode($results);
?>