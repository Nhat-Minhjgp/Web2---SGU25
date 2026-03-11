<?php
// get_phieu_detail.php
header('Content-Type: application/json');

$host = 'localhost'; 
$db = 'b01_nhahodau'; // Tên database của Ấn
$user = 'root'; 
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id > 0) {
        // LIÊN KẾT 3 BẢNG: phieunhap -> chitietphieunhap -> sanpham
        // Chúng ta Join chitietphieunhap với sanpham để lấy TenSP hiển thị lên ô tìm kiếm
        $sql = "SELECT 
                    ct.SanPham_id, 
                    ct.SoLuong, 
                    ct.Gia_Nhap, 
                    ct.MaLoHang,
                    sp.TenSP 
                FROM chitietphieunhap ct
                INNER JOIN sanpham sp ON ct.SanPham_id = sp.SanPham_id
                WHERE ct.PhieuNhap_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($result);
    } else {
        echo json_encode([]);
    }

} catch (PDOException $e) {
    // Trả về lỗi dưới dạng JSON để dễ kiểm tra
    echo json_encode(['error' => $e->getMessage()]);
}
?>