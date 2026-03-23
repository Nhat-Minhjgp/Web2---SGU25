<?php
// control/search-handler.php
header('Content-Type: application/json');
require_once __DIR__ . '/../control/connect.php';

// Lấy từ khóa tìm kiếm
$query = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;

if (strlen($query) < 2) {
    echo json_encode(['success' => false, 'data' => []]);
    exit();
}

try {
    // Truy vấn tìm kiếm sản phẩm theo tên (GIỐNG shop.php)
    $sql = "SELECT s.*, d.Ten_danhmuc, d.slug as danhmuc_slug,
            th.Ten_thuonghieu, th.slug as thuonghieu_slug
            FROM sanpham s
            LEFT JOIN danhmuc d ON s.Danhmuc_id = d.Danhmuc_id
            LEFT JOIN thuonghieu th ON s.Ma_thuonghieu = th.Ma_thuonghieu
            WHERE s.TenSP LIKE ? 
            AND s.TrangThai = 1
            ORDER BY s.SanPham_id DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $search_param = "%{$query}%";
    $stmt->bind_param("si", $search_param, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $discount = 0;
        if ($row['GiaNhapTB'] > $row['GiaBan'] && $row['GiaNhapTB'] > 0) {
            $discount = round(($row['GiaNhapTB'] - $row['GiaBan']) / $row['GiaNhapTB'] * 100);
        }
        
        $products[] = [
            'id' => $row['SanPham_id'],
            'name' => $row['TenSP'],
            'price' => number_format($row['GiaBan'], 0, ',', '.') . '₫',
            'category' => $row['Ten_danhmuc'] ?? 'Không rõ',
            'image' => !empty($row['image_url']) ? $row['image_url'] : './img/sanpham/placeholder.png',
            'url' => './view/product.php?id=' . $row['SanPham_id'],
            'inStock' => $row['SoLuongTon'] > 0
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $products,
        'total' => count($products)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'data' => []]);
}
?>