<?php
// control/search-suggest.php
header('Content-Type: application/json');

// Kết nối database
require_once __DIR__ . '/../control/connect.php';

// Lấy từ khóa tìm kiếm
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = 8; // Số sản phẩm hiển thị trong gợi ý

if (empty($query) || strlen($query) < 1) {
    echo json_encode(['success' => false, 'data' => []]);
    exit();
}

try {
    // Truy vấn tìm sản phẩm theo tên (LIKE %keyword%)
    $sql = "SELECT s.*, d.Ten_danhmuc, th.Ten_thuonghieu
            FROM sanpham s
            LEFT JOIN danhmuc d ON s.Danhmuc_id = d.Danhmuc_id
            LEFT JOIN thuonghieu th ON s.Ma_thuonghieu = th.Ma_thuonghieu
            WHERE s.TenSP LIKE ? AND s.TrangThai = 1
            ORDER BY s.SanPham_id DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $search_term = "%{$query}%";
    $stmt->bind_param("si", $search_term, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        // Tính phần trăm giảm giá
        $discount = 0;
        if ($row['GiaNhapTB'] > $row['GiaBan'] && $row['GiaNhapTB'] > 0) {
            $discount = round(($row['GiaNhapTB'] - $row['GiaBan']) / $row['GiaNhapTB'] * 100);
        }
        
        $products[] = [
            'id' => $row['SanPham_id'],
            'name' => $row['TenSP'],
            'price' => number_format($row['GiaBan'], 0, ',', '.') . '₫',
            'old_price' => $discount > 0 ? number_format($row['GiaNhapTB'], 0, ',', '.') . '₫' : null,
            'discount' => $discount,
            'category' => $row['Ten_danhmuc'] ?? 'Không rõ',
            'brand' => $row['Ten_thuonghieu'] ?? '',
            'image' => !empty($row['image_url']) ? $row['image_url'] : './img/sanpham/placeholder.png',
            'url' => './view/product.php?id=' . $row['SanPham_id'],
            'inStock' => $row['SoLuongTon'] > 0,
            'stock_quantity' => $row['SoLuongTon']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $products,
        'count' => count($products)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'data' => []]);
}
?>