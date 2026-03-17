<?php
// admin/includes/functions.php
require_once __DIR__ . '/../../control/connect.php';

/**
 * Lấy danh sách người dùng
 */
function getUsers($conn) {
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Lấy thông tin một người dùng
 */
function getUserById($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE User_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * Thêm người dùng mới
 */
function addUser($conn, $data) {
    $sql = "INSERT INTO users (Username, password, Ho_ten, email, SDT, role, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([
        $data['username'],
        password_hash($data['password'], PASSWORD_DEFAULT),
        $data['ho_ten'],
        $data['email'],
        $data['sdt'],
        $data['role'],
        $data['status']
    ]);
}

/**
 * Cập nhật thông tin người dùng
 */
function updateUser($conn, $user_id, $data) {
    $sql = "UPDATE users SET Ho_ten = ?, email = ?, SDT = ?, role = ?, status = ? WHERE User_id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([
        $data['ho_ten'],
        $data['email'],
        $data['sdt'],
        $data['role'],
        $data['status'],
        $user_id
    ]);
}

/**
 * Khóa/Mở khóa tài khoản
 */
function toggleUserStatus($conn, $user_id, $status) {
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE User_id = ?");
    return $stmt->execute([$status, $user_id]);
}

/**
 * Đổi mật khẩu
 */
function changePassword($conn, $user_id, $new_password) {
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE User_id = ?");
    return $stmt->execute([password_hash($new_password, PASSWORD_DEFAULT), $user_id]);
}

/**
 * Lấy danh sách danh mục
 */
function getCategories($conn) {
    $stmt = $conn->prepare("SELECT * FROM danhmuc ORDER BY Ten_danhmuc");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Thêm danh mục mới
 */
function addCategory($conn, $ten_danhmuc) {
    $stmt = $conn->prepare("INSERT INTO danhmuc (Ten_danhmuc) VALUES (?)");
    return $stmt->execute([$ten_danhmuc]);
}

/**
 * Sửa danh mục
 */
function updateCategory($conn, $danhmuc_id, $ten_danhmuc) {
    $stmt = $conn->prepare("UPDATE danhmuc SET Ten_danhmuc = ? WHERE Danhmuc_id = ?");
    return $stmt->execute([$ten_danhmuc, $danhmuc_id]);
}

/**
 * Xóa danh mục
 */
function deleteCategory($conn, $danhmuc_id) {
    $stmt = $conn->prepare("DELETE FROM danhmuc WHERE Danhmuc_id = ?");
    return $stmt->execute([$danhmuc_id]);
}

/**
 * Lấy danh sách thương hiệu
 */
function getBrands($conn) {
    $stmt = $conn->prepare("SELECT * FROM thuonghieu ORDER BY Ten_thuonghieu");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Lấy danh sách nhà cung cấp
 */
function getSuppliers($conn) {
    $stmt = $conn->prepare("SELECT * FROM nhacungcap ORDER BY Ten_NCC");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Lấy danh sách sản phẩm
 */
function getProducts($conn) {
    $sql = "SELECT s.*, d.Ten_danhmuc, n.Ten_NCC, t.Ten_thuonghieu 
            FROM sanpham s
            LEFT JOIN danhmuc d ON s.Danhmuc_id = d.Danhmuc_id
            LEFT JOIN nhacungcap n ON s.NCC_id = n.NCC_id
            LEFT JOIN thuonghieu t ON s.Ma_thuonghieu = t.Ma_thuonghieu
            ORDER BY s.TaoNgay DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Lấy thông tin một sản phẩm
 */
function getProductById($conn, $sanpham_id) {
    $stmt = $conn->prepare("SELECT * FROM sanpham WHERE SanPham_id = ?");
    $stmt->execute([$sanpham_id]);
    return $stmt->fetch();
}

/**
 * Thêm sản phẩm mới
 */
function addProduct($conn, $data) {
    $sql = "INSERT INTO sanpham (TenSP, Danhmuc_id, NCC_id, Ma_thuonghieu, MoTa, image_url, 
            GiaNhapTB, GiaBan, PhanTramLoiNhuan, TrangThai, SoLuongTon) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([
        $data['ten_sp'],
        $data['danhmuc_id'],
        $data['ncc_id'],
        $data['thuonghieu_id'],
        $data['mota'],
        $data['image_url'],
        $data['gia_nhap'],
        $data['gia_ban'],
        $data['phan_tram_loi_nhuan'],
        $data['trang_thai'],
        $data['so_luong_ton']
    ]);
}

/**
 * Cập nhật sản phẩm
 */
function updateProduct($conn, $sanpham_id, $data) {
    $sql = "UPDATE sanpham SET TenSP = ?, Danhmuc_id = ?, NCC_id = ?, Ma_thuonghieu = ?, 
            MoTa = ?, image_url = ?, GiaNhapTB = ?, GiaBan = ?, PhanTramLoiNhuan = ?, 
            TrangThai = ? WHERE SanPham_id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([
        $data['ten_sp'],
        $data['danhmuc_id'],
        $data['ncc_id'],
        $data['thuonghieu_id'],
        $data['mota'],
        $data['image_url'],
        $data['gia_nhap'],
        $data['gia_ban'],
        $data['phan_tram_loi_nhuan'],
        $data['trang_thai'],
        $sanpham_id
    ]);
}

/**
 * Xóa sản phẩm
 */
function deleteProduct($conn, $sanpham_id) {
    $stmt = $conn->prepare("DELETE FROM sanpham WHERE SanPham_id = ?");
    return $stmt->execute([$sanpham_id]);
}

/**
 * Cập nhật số lượng tồn kho
 */
function updateProductStock($conn, $sanpham_id, $so_luong) {
    $stmt = $conn->prepare("UPDATE sanpham SET SoLuongTon = ? WHERE SanPham_id = ?");
    return $stmt->execute([$so_luong, $sanpham_id]);
}

/**
 * Lấy danh sách đơn hàng
 */
function getOrders($conn) {
    $sql = "SELECT d.*, u.Ho_ten, u.SDT, dc.Ten_nguoi_nhan, dc.SDT_nhan 
            FROM donhang d
            LEFT JOIN users u ON d.User_id = u.User_id
            LEFT JOIN diachigh dc ON d.DiaChi_id = dc.add_id
            ORDER BY d.NgayDat DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Lấy chi tiết đơn hàng
 */
function getOrderDetails($conn, $donhang_id) {
    $sql = "SELECT c.*, s.TenSP 
            FROM chitiethoadon c
            LEFT JOIN sanpham s ON c.SanPham_id = s.SanPham_id
            WHERE c.DonHang_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$donhang_id]);
    return $stmt->fetchAll();
}

/**
 * Cập nhật trạng thái đơn hàng
 */
function updateOrderStatus($conn, $donhang_id, $trang_thai) {
    $stmt = $conn->prepare("UPDATE donhang SET TrangThai = ? WHERE DonHang_id = ?");
    return $stmt->execute([$trang_thai, $donhang_id]);
}

/**
 * Lấy danh sách phiếu nhập
 */
function getImportReceipts($conn) {
    $sql = "SELECT p.*, n.Ten_NCC 
            FROM phieunhap p
            LEFT JOIN nhacungcap n ON p.NCC_id = n.NCC_id
            ORDER BY p.NgayNhap DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Lấy chi tiết phiếu nhập
 */
function getImportDetails($conn, $phieunhap_id) {
    $sql = "SELECT c.*, s.TenSP 
            FROM chitietphieunhap c
            LEFT JOIN sanpham s ON c.SanPham_id = s.SanPham_id
            WHERE c.PhieuNhap_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$phieunhap_id]);
    return $stmt->fetchAll();
}

/**
 * Tạo phiếu nhập mới
 */
function createImportReceipt($conn, $data) {
    // Bắt đầu transaction
    $conn->beginTransaction();
    
    try {
        // Thêm phiếu nhập
        $sql1 = "INSERT INTO phieunhap (NCC_id, NguoiNhap, NgayNhap, SoLuong) VALUES (?, ?, ?, ?)";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->execute([
            $data['ncc_id'],
            $data['nguoi_nhap'],
            $data['ngay_nhap'],
            $data['tong_so_luong']
        ]);
        
        $phieunhap_id = $conn->lastInsertId();
        
        // Thêm chi tiết phiếu nhập
        $sql2 = "INSERT INTO chitietphieunhap (PhieuNhap_id, SanPham_id, SoLuong, Gia_Nhap, MaLoHang) 
                 VALUES (?, ?, ?, ?, ?)";
        $stmt2 = $conn->prepare($sql2);
        
        foreach ($data['chi_tiet'] as $ct) {
            $stmt2->execute([
                $phieunhap_id,
                $ct['sanpham_id'],
                $ct['so_luong'],
                $ct['gia_nhap'],
                $ct['ma_lo_hang']
            ]);
            
            // Cập nhật tồn kho và giá nhập trung bình
            updateStockAfterImport($conn, $ct['sanpham_id'], $ct['so_luong'], $ct['gia_nhap']);
        }
        
        $conn->commit();
        return $phieunhap_id;
    } catch (Exception $e) {
        $conn->rollBack();
        return false;
    }
}

/**
 * Cập nhật tồn kho sau khi nhập
 */
function updateStockAfterImport($conn, $sanpham_id, $so_luong_nhap, $gia_nhap) {
    // Lấy thông tin sản phẩm hiện tại
    $sp = getProductById($conn, $sanpham_id);
    
    $ton_hien_tai = $sp['SoLuongTon'] ?? 0;
    $gia_nhap_tb_hien_tai = $sp['GiaNhapTB'] ?? 0;
    
    // Tính giá nhập trung bình mới
    if ($ton_hien_tai > 0) {
        $gia_nhap_moi = (($ton_hien_tai * $gia_nhap_tb_hien_tai) + ($so_luong_nhap * $gia_nhap)) / ($ton_hien_tai + $so_luong_nhap);
    } else {
        $gia_nhap_moi = $gia_nhap;
    }
    
    // Cập nhật
    $ton_moi = $ton_hien_tai + $so_luong_nhap;
    $stmt = $conn->prepare("UPDATE sanpham SET SoLuongTon = ?, GiaNhapTB = ? WHERE SanPham_id = ?");
    return $stmt->execute([$ton_moi, $gia_nhap_moi, $sanpham_id]);
}

/**
 * Lấy báo cáo tồn kho
 */
function getInventoryReport($conn) {
    $sql = "SELECT s.SanPham_id, s.TenSP, d.Ten_danhmuc, s.SoLuongTon, s.GiaNhapTB, s.GiaBan,
            (s.SoLuongTon * s.GiaNhapTB) as TongGiaTriTon
            FROM sanpham s
            LEFT JOIN danhmuc d ON s.Danhmuc_id = d.Danhmuc_id
            ORDER BY s.SoLuongTon ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Lấy báo cáo nhập xuất theo thời gian
 */
function getImportExportReport($conn, $tu_ngay, $den_ngay) {
    // Nhập hàng
    $sql_import = "SELECT 'Nhập' as Loai, p.NgayNhap as Ngay, n.Ten_NCC as DoiTac, 
                   COUNT(DISTINCT p.NhapHang_id) as SoPhieu, SUM(c.SoLuong) as TongSoLuong,
                   SUM(c.SoLuong * c.Gia_Nhap) as TongTien
                   FROM phieunhap p
                   JOIN chitietphieunhap c ON p.NhapHang_id = c.PhieuNhap_id
                   LEFT JOIN nhacungcap n ON p.NCC_id = n.NCC_id
                   WHERE p.NgayNhap BETWEEN ? AND ?
                   GROUP BY p.NgayNhap";
    
    // Xuất hàng (bán)
    $sql_export = "SELECT 'Xuất' as Loai, d.NgayDat as Ngay, u.Ho_ten as DoiTac,
                   COUNT(DISTINCT d.DonHang_id) as SoPhieu, SUM(c.SoLuong) as TongSoLuong,
                   SUM(c.SoLuong * c.Gia) as TongTien
                   FROM donhang d
                   JOIN chitiethoadon c ON d.DonHang_id = c.DonHang_id
                   LEFT JOIN users u ON d.User_id = u.User_id
                   WHERE d.NgayDat BETWEEN ? AND ? AND d.TrangThai = 'Đã giao'
                   GROUP BY d.NgayDat";
    
    $stmt1 = $conn->prepare($sql_import);
    $stmt1->execute([$tu_ngay, $den_ngay]);
    $import = $stmt1->fetchAll();
    
    $stmt2 = $conn->prepare($sql_export);
    $stmt2->execute([$tu_ngay, $den_ngay]);
    $export = $stmt2->fetchAll();
    
    return array_merge($import, $export);
}

/**
 * Lấy sản phẩm sắp hết hàng
 */
function getLowStockProducts($conn, $nguong = 10) {
    $sql = "SELECT s.*, d.Ten_danhmuc 
            FROM sanpham s
            LEFT JOIN danhmuc d ON s.Danhmuc_id = d.Danhmuc_id
            WHERE s.SoLuongTon <= ? AND s.SoLuongTon > 0
            ORDER BY s.SoLuongTon ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nguong]);
    return $stmt->fetchAll();
}

/**
 * Lấy sản phẩm hết hàng
 */
function getOutOfStockProducts($conn) {
    $sql = "SELECT s.*, d.Ten_danhmuc 
            FROM sanpham s
            LEFT JOIN danhmuc d ON s.Danhmuc_id = d.Danhmuc_id
            WHERE s.SoLuongTon = 0 OR s.SoLuongTon IS NULL
            ORDER BY s.TenSP";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Thống kê dashboard
 */
function getDashboardStats($conn) {
    $stats = [];
    
    // Tổng người dùng
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
    $stats['total_users'] = $stmt->fetch()['total'];
    
    // Tổng sản phẩm
    $stmt = $conn->query("SELECT COUNT(*) as total FROM sanpham WHERE TrangThai = 'active'");
    $stats['total_products'] = $stmt->fetch()['total'];
    
    // Đơn hàng hôm nay
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM donhang WHERE DATE(NgayDat) = CURDATE()");
    $stmt->execute();
    $stats['today_orders'] = $stmt->fetch()['total'];
    
    // Doanh thu tháng này
    $stmt = $conn->prepare("SELECT SUM(TongTien) as total FROM donhang 
                            WHERE MONTH(NgayDat) = MONTH(CURDATE()) 
                            AND YEAR(NgayDat) = YEAR(CURDATE())
                            AND TrangThai = 'Đã giao'");
    $stmt->execute();
    $stats['month_revenue'] = $stmt->fetch()['total'] ?? 0;
    
    // Đơn hàng gần đây
    $stmt = $conn->prepare("SELECT d.*, u.Ho_ten FROM donhang d 
                            LEFT JOIN users u ON d.User_id = u.User_id 
                            ORDER BY d.NgayDat DESC LIMIT 5");
    $stmt->execute();
    $stats['recent_orders'] = $stmt->fetchAll();
    
    // Sản phẩm sắp hết
    $stats['low_stock'] = getLowStockProducts($conn, 10);
    
    return $stats;
}

/**
 * Đăng nhập admin
 */
function adminLogin($conn, $username, $password) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE Username = ? AND role IN ('admin', 'staff')");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return false;
}

/**
 * Ghi log hoạt động
 */
function logActivity($conn, $user_id, $action, $details = '') {
    // Bạn có thể tạo bảng activity_logs để lưu log
    // Tạm thời chưa cần implement
    return true;
}
?>