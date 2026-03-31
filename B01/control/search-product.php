<?php

header('Content-Type: application/json');
require_once __DIR__ . '/../control/connect.php';

// Kiểm tra đăng nhập
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$keyword = trim($_GET['q'] ?? '');
$limit = intval($_GET['limit'] ?? 10);

if (strlen($keyword) < 2) {
    echo json_encode([]);
    exit;
}

// Tìm kiếm sản phẩm với LIKE
$stmt = $conn->prepare("
    SELECT SanPham_id, TenSP, GiaNhapTB, GiaBan, SoLuongTon, PhanTramLoiNhuan, NCC_id 
    FROM sanpham 
    WHERE (TenSP LIKE ? OR SanPham_id LIKE ?) 
    AND (TrangThai = 1 OR Trangthai = 0)
    ORDER BY TenSP 
    LIMIT ?
");
$searchTerm = "%$keyword%";
$stmt->bind_param("ssi", $searchTerm, $searchTerm, $limit);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = [
        'id' => $row['SanPham_id'],
        'name' => $row['TenSP'],
        'gia_nhap' => floatval($row['GiaNhapTB']),
        'gia_ban' => floatval($row['GiaBan']),
        'ton' => intval($row['SoLuongTon']),
        'loi' => floatval($row['PhanTramLoiNhuan'] ?? 0.2),
        'ncc_id' => $row['NCC_id']
    ];
}

echo json_encode($products);
$stmt->close();
$conn->close();
?>