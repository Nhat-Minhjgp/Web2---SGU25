<?php
require_once __DIR__ . '/../control/connect.php';
header('Content-Type: application/json');

$keyword = trim($_GET['q'] ?? '');
if (strlen($keyword) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT SanPham_id, TenSP, GiaNhapTB, GiaBan, SoLuongTon, PhanTramLoiNhuan 
                        FROM sanpham 
                        WHERE (TenSP LIKE ? OR SanPham_id LIKE ?) AND (TrangThai = 1 OR Trangthai = 0)
                        LIMIT 50");
$like = "%$keyword%";
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while($row = $result->fetch_assoc()) {
    $products[] = [
        'id' => $row['SanPham_id'],
        'text' => $row['TenSP'] . " (Tồn: {$row['SoLuongTon']})",
        'giaNhap' => $row['GiaNhapTB'],
        'giaBan' => $row['GiaBan'],
        'loiNhuan' => $row['PhanTramLoiNhuan']
    ];
}
echo json_encode(['results' => $products]);
?>