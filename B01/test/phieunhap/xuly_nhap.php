<?php
// xuly_nhap.php
$host = 'localhost'; $db = 'b01_nhahodau'; $user = 'root'; $pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}

if (isset($_POST['btn_save'])) {
    $ngay_nhap = $_POST['ngay_nhap'];
    $sp_ids = $_POST['sp_id']; // Mảng ID sản phẩm từ input hidden
    $soluongs = $_POST['sl'];  // Mảng số lượng
    $gias = $_POST['gia'];    // Mảng giá nhập
    $los = $_POST['lo'];      // Mảng mã lô hàng

    try {
        $pdo->beginTransaction();

        // BƯỚC 1: Lưu thông tin chung vào bảng phieunhap
        $tong_sl_phieu = array_sum($soluongs);
        $sql_phieu = "INSERT INTO phieunhap (NgayNhap, SoLuong) VALUES (?, ?)";
        $stmt_phieu = $pdo->prepare($sql_phieu);
        $stmt_phieu->execute([$ngay_nhap, $tong_sl_phieu]);
        
        // Lấy ID phiếu vừa tạo để làm khóa ngoại cho bảng chi tiết
        $id_phieu_moi = $pdo->lastInsertId();

        // Chuẩn bị các câu lệnh SQL để dùng trong vòng lặp (tăng hiệu năng)
        $sql_ct = "INSERT INTO chitietphieunhap (PhieuNhap_id, SanPham_id, SoLuong, Gia_Nhap, MaLoHang) VALUES (?, ?, ?, ?, ?)";
        $stmt_ct = $pdo->prepare($sql_ct);

        $sql_update_kho = "UPDATE sanpham SET SoLuongTon = SoLuongTon + ? WHERE SanPham_id = ?";
        $stmt_update = $pdo->prepare($sql_update_kho);

        // BƯỚC 2: Duyệt qua từng sản phẩm trong danh sách nhập
        foreach ($sp_ids as $index => $id_sp) {
            if (!empty($id_sp)) {
                $sl_nhap = $soluongs[$index];
                $gia_nhap = $gias[$index];
                $ma_lo = $los[$index];

                // 2.1. Lưu vào bảng chi tiết phiếu nhập
                $stmt_ct->execute([$id_phieu_moi, $id_sp, $sl_nhap, $gia_nhap, $ma_lo]);

                // 2.2. CẬP NHẬT KHO: Cộng dồn số lượng vào bảng sanpham
                $stmt_update->execute([$sl_nhap, $id_sp]);
            }
        }

        // Nếu mọi thứ chạy tốt, xác nhận lưu vĩnh viễn vào DB
        $pdo->commit();
        echo "<script>alert('Nhập hàng thành công và đã cập nhật kho!'); window.location.href='tao_phieu.php';</script>";

    } catch (Exception $e) {
        // Nếu có bất kỳ lỗi gì, hủy bỏ toàn bộ các lệnh trên
        $pdo->rollBack();
        echo "Lỗi hệ thống: " . $e->getMessage();
    }
}
?>