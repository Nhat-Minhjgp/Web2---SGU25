<?php
// admin/function.php
require_once __DIR__ . '/../control/connect.php';

/**
 * Lấy danh sách người dùng
 */
function getUsers($conn) {
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);  // SỬA: fetch_all (có gạch dưới)
}

/**
 * Lấy thông tin một người dùng
 */
function getUserById($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE User_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();  // fetch_assoc cho 1 dòng
}

/**
 * Thêm người dùng mới
 */
function addUser($conn, $data) {
    $sql = "INSERT INTO users (Username, password, Ho_ten, email, SDT, role, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt->bind_param("sssssss", 
        $data['username'],
        $hashed,
        $data['ho_ten'],
        $data['email'],
        $data['sdt'],
        $data['role'],
        $data['status']
    );
    return $stmt->execute();
}

/**
 * Cập nhật thông tin người dùng
 */
function updateUser($conn, $user_id, $data) {
    $sql = "UPDATE users SET Ho_ten = ?, email = ?, SDT = ?, role = ?, status = ? WHERE User_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", 
        $data['ho_ten'],
        $data['email'],
        $data['sdt'],
        $data['role'],
        $data['status'],
        $user_id
    );
    return $stmt->execute();
}

/**
 * Khóa/Mở khóa tài khoản
 */
function toggleUserStatus($conn, $user_id, $status) {
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE User_id = ?");
    $stmt->bind_param("si", $status, $user_id);
    return $stmt->execute();
}

/**
 * Đổi mật khẩu
 */
function changePassword($conn, $user_id, $new_password) {
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE User_id = ?");
    $stmt->bind_param("si", $hashed, $user_id);
    return $stmt->execute();
}

/**
 * Lấy danh sách danh mục
 */
function getCategories($conn) {
    $stmt = $conn->prepare("SELECT * FROM danhmuc ORDER BY Ten_danhmuc");
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);  // SỬA: fetch_all
}

/**
 * Thêm danh mục mới
 */
function addCategory($conn, $ten_danhmuc) {
    $stmt = $conn->prepare("INSERT INTO danhmuc (Ten_danhmuc) VALUES (?)");
    $stmt->bind_param("s", $ten_danhmuc);
    return $stmt->execute();
}

/**
 * Lấy danh sách thương hiệu
 */
function getBrands($conn) {
    $stmt = $conn->prepare("SELECT * FROM thuonghieu ORDER BY Ten_thuonghieu");
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);  // SỬA: fetch_all
}

/**
 * Lấy danh sách nhà cung cấp
 */
function getSuppliers($conn) {
    $stmt = $conn->prepare("SELECT * FROM nhacungcap ORDER BY Ten_NCC");
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);  // SỬA: fetch_all
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
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);  // SỬA: fetch_all
}

/**
 * Lấy thông tin một sản phẩm
 */
function getProductById($conn, $sanpham_id) {
    $stmt = $conn->prepare("SELECT * FROM sanpham WHERE SanPham_id = ?");
    $stmt->bind_param("i", $sanpham_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();  // fetch_assoc cho 1 dòng
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
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);  // SỬA: fetch_all
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
    $stmt->bind_param("i", $donhang_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);  // SỬA: fetch_all
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
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);  // SỬA: fetch_all
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
    $stmt->bind_param("i", $phieunhap_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);  // SỬA: fetch_all
}

/**
 * Đăng nhập admin
 */
function adminLogin($conn, $username, $password) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE Username = ? AND role IN ('admin', 'staff')");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return false;
}
?>