<?php
session_start();
require_once __DIR__ . '/../control/connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false]);
    exit();
}

$from_date = $_GET['from'] ?? '';
$to_date = $_GET['to'] ?? '';
$product_id = $_GET['product'] ?? '';

// Lấy dữ liệu nhập hàng
$sql_import = "SELECT pn.NgayNhap as date, 'Nhập' as type, 
               sp.SanPham_id as product_id, sp.TenSP as product_name,
               ct.SoLuong as quantity, ct.Gia_Nhap as price,
               (ct.SoLuong * ct.Gia_Nhap) as total
               FROM phieunhap pn
               JOIN chitietphieunhap ct ON pn.NhapHang_id = ct.PhieuNhap_id
               JOIN sanpham sp ON ct.SanPham_id = sp.SanPham_id
               WHERE pn.NgayNhap BETWEEN ? AND ?";

// Lấy dữ liệu xuất hàng (bán)
$sql_export = "SELECT d.NgayDat as date, 'Xuất' as type,
               sp.SanPham_id as product_id, sp.TenSP as product_name,
               ctdh.SoLuong as quantity, ctdh.Gia as price,
               (ctdh.SoLuong * ctdh.Gia) as total
               FROM donhang d
               JOIN chitiethoadon ctdh ON d.DonHang_id = ctdh.DonHang_id
               JOIN sanpham sp ON ctdh.SanPham_id = sp.SanPham_id
               WHERE d.NgayDat BETWEEN ? AND ? AND d.TrangThai = 'delivered'";

// Gộp kết quả
$result = array_merge($import, $export);
echo json_encode($result);
?>