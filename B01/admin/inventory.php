<?php
session_start();
require_once __DIR__ . '/../control/connect.php';
require_once __DIR__ . '/../control/function.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Quản trị viên';
$admin_role = $_SESSION['admin_role'] ?? '';
$admin_username = $_SESSION['admin_username'] ?? '';




// ============================================
// XỬ LÝ AJAX: LẤY TỒN KHO THEO NGÀY
// ============================================
if (isset($_GET['get_inventory_by_date'])) {
    header('Content-Type: application/json');

    $date = $_GET['date'] ?? date('Y-m-d');
    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

    // Lấy danh sách sản phẩm (có thể lọc theo danh mục)
    $sql = "SELECT sp.*, dm.Ten_danhmuc, th.Ten_thuonghieu,
            COALESCE(sp.CanhBaoTon, 10) as canh_bao,
            COALESCE(sp.GiaNhapTB, 0) as GiaNhapTB
            FROM sanpham sp
            LEFT JOIN danhmuc dm ON sp.Danhmuc_id = dm.Danhmuc_id
            LEFT JOIN thuonghieu th ON sp.Ma_thuonghieu = th.Ma_thuonghieu";

    if ($category_id > 0) {
        $sql .= " WHERE sp.Danhmuc_id = $category_id";
    }
    $sql .= " ORDER BY sp.SanPham_id DESC";

    $result = $conn->query($sql);
    $products = $result->fetch_all(MYSQLI_ASSOC);
    $inventory_data = [];

    foreach ($products as $p) {
        $pid = $p['SanPham_id'];
        $gia_von = floatval($p['GiaNhapTB']);

        // 📥 Tổng nhập TRƯỚC ngày (tồn đầu)
        $stmt = $conn->prepare("SELECT COALESCE(SUM(ct.SoLuong),0) as tong 
        FROM chitietphieunhap ct
        JOIN phieunhap pn ON ct.PhieuNhap_id = pn.NhapHang_id
        WHERE ct.SanPham_id = ? AND pn.NgayNhap < DATE(?)");
        $stmt->bind_param("is", $pid, $date);
        $stmt->execute();
        $nhap_truoc = $stmt->get_result()->fetch_assoc()['tong'];
        $stmt->close();  // ← Quan trọng: đóng statement trước khi dùng lại

        // 📤 Tổng xuất TRƯỚC ngày
        $stmt = $conn->prepare("SELECT COALESCE(SUM(ctdh.SoLuong),0) as tong 
        FROM chitiethoadon ctdh
        JOIN donhang d ON ctdh.DonHang_id = d.DonHang_id
        WHERE ctdh.SanPham_id = ? AND d.TrangThai IN (1,2) AND d.NgayDat < DATE(?)");
        $stmt->bind_param("is", $pid, $date);
        $stmt->execute();
        $xuat_truoc = $stmt->get_result()->fetch_assoc()['tong'];
        $stmt->close();

        // 📥 Tổng nhập TRONG ngày
        $stmt = $conn->prepare("SELECT COALESCE(SUM(ct.SoLuong),0) as tong 
        FROM chitietphieunhap ct
        JOIN phieunhap pn ON ct.PhieuNhap_id = pn.NhapHang_id
        WHERE ct.SanPham_id = ? AND DATE(pn.NgayNhap) = ?");
        $stmt->bind_param("is", $pid, $date);
        $stmt->execute();
        $nhap_trong_ngay = $stmt->get_result()->fetch_assoc()['tong'];
        $stmt->close();

        // 📤 Tổng xuất TRONG ngày
        $stmt = $conn->prepare("SELECT COALESCE(SUM(ctdh.SoLuong),0) as tong 
        FROM chitiethoadon ctdh
        JOIN donhang d ON ctdh.DonHang_id = d.DonHang_id
        WHERE ctdh.SanPham_id = ? AND d.TrangThai IN (1,2) AND DATE(d.NgayDat) = ?");
        $stmt->bind_param("is", $pid, $date);
        $stmt->execute();
        $xuat_trong_ngay = $stmt->get_result()->fetch_assoc()['tong'];
        $stmt->close();

        // Tính toán
        $ton_dau = $nhap_truoc - $xuat_truoc;
        $ton_cuoi = $ton_dau + $nhap_trong_ngay - $xuat_trong_ngay;

        $inventory_data[] = [
            'masp' => 'SP' . str_pad($p['SanPham_id'], 4, '0', STR_PAD_LEFT),
            'ten_sp' => $p['TenSP'],
            'danh_muc' => $p['Ten_danhmuc'] ?? 'Chưa có',
            'ton_dau_ngay' => $ton_dau,
            'nhap_trong_ngay' => $nhap_trong_ngay,
            'xuat_trong_ngay' => $xuat_trong_ngay,
            'ton_cuoi_ngay' => $ton_cuoi,
            'gia_nhap_tb' => $gia_von,
            'tong_gia_tri_dau' => $ton_dau * $gia_von,
            'tong_gia_tri_cuoi' => $ton_cuoi * $gia_von,
            'canh_bao' => $p['canh_bao'],
        ];
    }

    echo json_encode($inventory_data);
    exit();
}

// ============================================
// XỬ LÝ CẬP NHẬT NGƯỠNG CẢNH BÁO
// ============================================
if (isset($_POST['update_threshold'])) {
    $product_id = intval($_POST['product_id']);
    $threshold = intval($_POST['threshold']);

    // Kiểm tra cột CanhBaoTon
    $check_column = $conn->query("SHOW COLUMNS FROM sanpham LIKE 'CanhBaoTon'");
    if ($check_column->num_rows == 0) {
        $conn->query("ALTER TABLE sanpham ADD COLUMN CanhBaoTon INT DEFAULT 10 AFTER SoLuongTon");
    }

    $sql = "UPDATE sanpham SET CanhBaoTon = ? WHERE SanPham_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $threshold, $product_id);
    if ($stmt->execute()) {
        $message = "Đã cập nhật ngưỡng cảnh báo thành công!";
    } else {
        $error = "Có lỗi xảy ra!";
    }
}

// ============================================
// LẤY DANH SÁCH SẢN PHẨM VỚI DỮ LIỆU THẬT
// ============================================
$sql = "SELECT sp.*, dm.Ten_danhmuc, th.Ten_thuonghieu,
        COALESCE((SELECT SUM(SoLuong) FROM chitietphieunhap WHERE SanPham_id = sp.SanPham_id), 0) as tong_nhap,
        COALESCE((SELECT SUM(SoLuong) FROM chitiethoadon WHERE SanPham_id = sp.SanPham_id), 0) as tong_xuat,
        COALESCE(sp.CanhBaoTon, 10) as canh_bao,
        COALESCE(sp.GiaNhapTB, 0) as GiaNhapTB
        FROM sanpham sp
        LEFT JOIN danhmuc dm ON sp.Danhmuc_id = dm.Danhmuc_id
        LEFT JOIN thuonghieu th ON sp.Ma_thuonghieu = th.Ma_thuonghieu
        ORDER BY sp.SanPham_id DESC";
$result = $conn->query($sql);
$products = $result->fetch_all(MYSQLI_ASSOC);

// Lấy danh sách danh mục để lọc
$categories = getCategories($conn);
// ============================================
// XỬ LÝ BÁO CÁO NHẬP - XUẤT QUA AJAX (NGÀY OPTIONAL)
// ============================================
if (isset($_GET['get_report'])) {
    $from_date = $_GET['from'] ?? '';
    $to_date = $_GET['to'] ?? '';
    $product_id = $_GET['product'] ?? '';

    // ============================================
    // 1. XỬ LÝ NHẬP HÀNG
    // ============================================
    $sql_import = "SELECT pn.NgayNhap as date, 'Nhập' as type, 
                   sp.SanPham_id as product_id, sp.TenSP as product_name,
                   COALESCE(ct.SoLuong, 0) as quantity, 
                   COALESCE(ct.Gia_Nhap, 0) as price,
                   COALESCE(ct.SoLuong * ct.Gia_Nhap, 0) as total
                   FROM phieunhap pn
                   JOIN chitietphieunhap ct ON pn.NhapHang_id = ct.PhieuNhap_id
                   JOIN sanpham sp ON ct.SanPham_id = sp.SanPham_id
                   WHERE 1=1";

    $params_import = [];
    $types_import = "";

    // Thêm điều kiện ngày cho nhập
    if (!empty($from_date) && !empty($to_date)) {
        $sql_import .= " AND DATE(pn.NgayNhap) BETWEEN DATE(?) AND DATE(?)";
        $params_import[] = $from_date;
        $params_import[] = $to_date;
        $types_import .= "ss";
    } elseif (!empty($from_date)) {
        $sql_import .= " AND DATE(pn.NgayNhap) >= DATE(?)";
        $params_import[] = $from_date;
        $types_import .= "s";
    } elseif (!empty($to_date)) {
        $sql_import .= " AND DATE(pn.NgayNhap) <= DATE(?)";
        $params_import[] = $to_date;
        $types_import .= "s";
    }

    if (!empty($product_id)) {
        $sql_import .= " AND sp.SanPham_id = ?";
        $params_import[] = $product_id;
        $types_import .= "i";
    }

    $imports = [];
    $stmt = $conn->prepare($sql_import);
    if ($stmt) {
        if (!empty($params_import)) {
            $stmt->bind_param($types_import, ...$params_import);
        }
        $stmt->execute();
        $imports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    // ============================================
    // 2. XỬ LÝ XUẤT HÀNG
    // ============================================
    $sql_export = "SELECT d.NgayDat as date, 
                   CASE 
                       WHEN d.TrangThai = 1 THEN 'Đã xác nhận'
                       WHEN d.TrangThai = 2 THEN 'Đã giao'
                       ELSE 'Xuất kho'
                   END as type,
                   sp.SanPham_id as product_id, sp.TenSP as product_name,
                   COALESCE(ctdh.SoLuong, 0) as quantity, 
                   COALESCE(ctdh.Gia, 0) as price,
                   COALESCE(ctdh.SoLuong * ctdh.Gia, 0) as total
                   FROM donhang d
                   JOIN chitiethoadon ctdh ON d.DonHang_id = ctdh.DonHang_id
                   JOIN sanpham sp ON ctdh.SanPham_id = sp.SanPham_id
                   WHERE d.TrangThai IN (1, 2)";

    $params_export = [];
    $types_export = "";

    if (!empty($from_date) && !empty($to_date)) {
        $sql_export .= " AND DATE(d.NgayDat) BETWEEN DATE(?) AND DATE(?)";
        $params_export[] = $from_date;
        $params_export[] = $to_date;
        $types_export .= "ss";
    } elseif (!empty($from_date)) {
        $sql_export .= " AND DATE(d.NgayDat) >= DATE(?)";
        $params_export[] = $from_date;
        $types_export .= "s";
    } elseif (!empty($to_date)) {
        $sql_export .= " AND DATE(d.NgayDat) <= DATE(?)";
        $params_export[] = $to_date;
        $types_export .= "s";
    }

    if (!empty($product_id)) {
        $sql_export .= " AND sp.SanPham_id = ?";
        $params_export[] = $product_id;
        $types_export .= "i";
    }

    $exports = [];
    $stmt = $conn->prepare($sql_export);
    if ($stmt) {
        if (!empty($params_export)) {
            $stmt->bind_param($types_export, ...$params_export);
        }
        $stmt->execute();
        $exports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    // ============================================
    // 3. GỘP VÀ SẮP XẾP DỮ LIỆU
    // ============================================
    $reports = array_merge($imports, $exports);
    usort($reports, function ($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });

    // ============================================
    // 4. ✅ TÍNH TỒN KHO THEO KHOẢNG NGÀY (ĐÃ FIX)
    // ============================================
    $inventory_summary = ['has_product' => false];

    if (!empty($product_id)) {
        // Lấy giá vốn
        $stmt = $conn->prepare("SELECT COALESCE(GiaNhapTB, 0) as gia_von FROM sanpham WHERE SanPham_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $gia_von = $stmt->get_result()->fetch_assoc()['gia_von'] ?? 0;
        $stmt->close();

        // 🎯 XÁC ĐỊNH NGÀY BẮT ĐẦU ĐỂ TÍNH TỒN ĐẦU
        // Nếu có from_date: tồn đầu = trước from_date
        // Nếu không có from_date nhưng có to_date: tồn đầu = trước to_date (kỳ là 1 ngày)
        // Nếu không có cả hai: tồn đầu = 0 (tính từ đầu hệ thống)
        $reference_date_for_ton_dau = null;
        if (!empty($from_date)) {
            $reference_date_for_ton_dau = $from_date;
        } elseif (!empty($to_date)) {
            // Khi chỉ có to_date, coi như xem báo cáo cho ngày đó → tồn đầu là trước ngày đó
            $reference_date_for_ton_dau = $to_date;
        }

        $ton_dau = 0;
        if (!empty($reference_date_for_ton_dau)) {
            $sql_ton_dau = "SELECT 
                COALESCE((SELECT SUM(ct.SoLuong) FROM chitietphieunhap ct 
                    JOIN phieunhap pn ON ct.PhieuNhap_id = pn.NhapHang_id 
                    WHERE ct.SanPham_id = ? AND DATE(pn.NgayNhap) < DATE(?)), 0) as nhap_truoc,
                COALESCE((SELECT SUM(ctdh.SoLuong) FROM chitiethoadon ctdh 
                    JOIN donhang d ON ctdh.DonHang_id = d.DonHang_id 
                    WHERE ctdh.SanPham_id = ? AND d.TrangThai IN (1,2) AND DATE(d.NgayDat) < DATE(?)), 0) as xuat_truoc";

            $stmt = $conn->prepare($sql_ton_dau);
            $stmt->bind_param("isii", $product_id, $reference_date_for_ton_dau, $product_id, $reference_date_for_ton_dau);
            $stmt->execute();
            $ton_dau_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $ton_dau = ($ton_dau_data['nhap_truoc'] ?? 0) - ($ton_dau_data['xuat_truoc'] ?? 0);
        }
        // Nếu không có reference_date → ton_dau = 0 (bắt đầu từ 0)

        $tong_nhap = array_sum(array_column($imports, 'quantity'));
        $tong_xuat = array_sum(array_column($exports, 'quantity'));
        $ton_cuoi = $ton_dau + $tong_nhap - $tong_xuat;

        $inventory_summary = [
            'ton_dau' => $ton_dau,
            'ton_cuoi' => $ton_cuoi,
            'tong_nhap' => $tong_nhap,
            'tong_xuat' => $tong_xuat,
            'gia_tri_dau' => $ton_dau * $gia_von,
            'gia_tri_cuoi' => $ton_cuoi * $gia_von,
            'gia_von' => $gia_von,
            'has_product' => true
        ];
    }

    // Trả về JSON
    header('Content-Type: application/json');
    echo json_encode([
        'reports' => $reports,
        'inventory' => $inventory_summary,
        'period' => ['from' => $from_date, 'to' => $to_date]
    ]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tồn kho & Báo cáo - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: '#667eea', secondary: '#764ba2' },
                    backgroundImage: { 'gradient-custom': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }
                }
            }
        }
    </script>
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeIn {
            animation: fadeIn 0.3s ease-out;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .tab-btn {
            padding: 10px 20px;
            border-radius: 8px 8px 0 0;
            transition: all 0.2s;
            cursor: pointer;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .inventory-row-low {
            background-color: #fef3c7;
        }

        .inventory-row-critical {
            background-color: #fee2e2;
        }

        .sidebar {
            width: 280px;
            background: white;
            border-right: 1px solid #e5e7eb;
            padding: 20px 0;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 20px;
        }

        .sidebar-header h3 {
            font-size: 12px;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .menu-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 8px;
            color: #4b5563;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 14px;
        }

        .menu-btn i {
            width: 20px;
            color: #9ca3af;
        }

        .menu-btn:hover {
            background-color: #f3f4f6;
            color: #667eea;
        }

        .menu-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .menu-btn.active i {
            color: white;
        }

        /* Animation cho bảng */
        .table-row-hover:hover {
            background-color: #f9fafb;
            transition: all 0.2s;
        }

        /* Fix layout responsive cho filter */
        @media (max-width: 768px) {
            .flex-wrap.gap-3>div {
                min-width: 100% !important;
            }
        }

        /* Highlight row khi hover có filter */
        .inventory-row-filtered {
            animation: highlight 0.5s ease;
        }

        @keyframes highlight {
            0% {
                background-color: #fef3c7;
            }

            100% {
                background-color: transparent;
            }
        }

        #reportTab .flex-wrap>div {
            display: flex;
            flex-direction: column;
        }





        #dateError.hidden {
            display: none;
        }
    </style>
    <link rel="icon" type="image/svg+xml" href="../img/icons/favicon.png" sizes="32x32">
</head>

<body class="bg-gray-50 font-sans text-gray-800 min-h-screen">

    <!-- HEADER -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="flex justify-between items-center px-6 py-4">
            <h1 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-custom">NVBPlay Admin Panel</h1>
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-3 bg-gray-100 px-4 py-2 rounded-lg">
                    <div
                        class="w-10 h-10 rounded-full bg-gradient-custom flex items-center justify-center text-white font-bold">
                        <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($admin_username); ?></p>
                    </div>
                </div>
                <button onclick="logout()"
                    class="bg-gradient-custom text-white font-semibold px-4 py-2 rounded-lg hover:opacity-90 transition shadow-md">
                    <i class="fas fa-sign-out-alt mr-2"></i>Đăng xuất
                </button>
            </div>
        </div>
    </header>

    <div class="flex w-full min-h-[calc(100vh-70px)]">
        <!-- SIDEBAR -->
        <div class="flex w-full min-h-[calc(100vh-70px)]">
            <aside class="w-64 bg-white shadow-lg hidden lg:block flex-shrink-0 border-r border-gray-100">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Danh mục chức năng</h3>
                </div>
                <nav class="p-4 space-y-2">
                    <a href="dashboard.php"
                        class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i
                            class="fas fa-home w-5"></i> Dashboard</a>
                    <a href="users.php"
                        class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i
                            class="fas fa-users w-5"></i> Quản lý người dùng</a>
                    <a href="categories.php"
                        class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                        <i class="fas fa-list w-5 text-center"></i> Quản lý danh mục
                    </a>
                    <a href="product.php"
                        class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i
                            class="fas fa-box w-5"></i> Quản lý sản phẩm</a>
                    <a href="import.php"
                        class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i
                            class="fas fa-arrow-down w-5"></i> Quản lý nhập hàng</a>
                    <a href="price.php"
                        class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i
                            class="fas fa-tag w-5"></i> Quản lý giá bán</a>
                    <a href="orders.php"
                        class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i
                            class="fas fa-receipt w-5"></i> Quản lý đơn hàng</a>
                    <a href="inventory.php"
                        class="flex items-center gap-3 px-4 py-3 bg-gradient-custom text-white rounded-lg shadow-md"><i
                            class="fas fa-warehouse w-5"></i> Tồn kho & Báo cáo</a>
                </nav>
            </aside>

            <!-- MAIN CONTENT -->
            <main class="flex-1 p-6 lg:p-8 overflow-x-hidden bg-gray-50">
                <div class="bg-white rounded-xl shadow-lg p-6 lg:p-8 min-h-full animate-fadeIn">
                    <div class="flex justify-between items-center mb-6 pb-4 border-b">
                        <h2 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-warehouse text-primary mr-2"></i>Quản lý tồn kho & Báo cáo
                        </h2>
                    </div>

                    <?php if (isset($message)): ?>
                        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">
                            <i class="fas fa-check-circle mr-2"></i><?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
                            <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Tabs -->
                    <div class="flex border-b border-gray-200 mb-6">
                        <button onclick="showTab('inventory')" id="tabInventoryBtn"
                            class="tab-btn active bg-gradient-custom text-white">
                            <i class="fas fa-boxes mr-2"></i>Tồn kho
                        </button>
                        <button onclick="showTab('report')" id="tabReportBtn"
                            class="tab-btn text-gray-600 hover:text-primary">
                            <i class="fas fa-chart-line mr-2"></i>Báo cáo nhập - xuất
                        </button>
                        <button onclick="showTab('warning')" id="tabWarningBtn"
                            class="tab-btn text-gray-600 hover:text-primary">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Cảnh báo
                        </button>
                    </div>

                    <!-- TAB 1: TỒN KHO -->
                    <div id="inventoryTab" class="tab-content">
                        <!-- 🎯 Filter Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-calendar-alt text-primary mr-2"></i>Tra cứu tồn kho theo ngày
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                                <!-- Danh mục -->
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Loại sản phẩm</label>
                                    <select id="filterCategory"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                                        <option value="">-- Tất cả --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['Danhmuc_id'] ?>">
                                                <?= htmlspecialchars($cat['Ten_danhmuc']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Ngày -->
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Ngày xem tồn kho</label>
                                    <input type="date" id="inventoryDate" max="<?= date('Y-m-d') ?>"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                                </div>

                                <!-- Nút -->
                                <div class="flex items-end gap-2">
                                    <button onclick="searchInventoryByDate()"
                                        class="flex-1 bg-gradient-custom text-white px-4 py-2 rounded-lg hover:opacity-90 transition">
                                        <i class="fas fa-search mr-2"></i>Tra cứu
                                    </button>
                                    <button onclick="resetInventoryFilter()"
                                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Info box -->

                            <span class="font-medium hidden text-blue-800"> Ngày:</span>
                            <span id="selectedDateDisplay" class="text-blue-700 hidden font-semibold"></span>


                        </div>

                        <!-- 📊 Data Table -->
                        <div class="overflow-x-auto border border-gray-200 rounded-xl">
                            <table class="w-full min-w-[1200px]" id="inventoryTable">
                                <thead class="bg-gradient-custom text-white">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Mã SP</th>
                                        <th class="px-4 py-3 text-left">Tên sản phẩm</th>
                                        <th class="px-4 py-3 text-left">Danh mục</th>
                                        <th class="px-4 py-3 text-right">Tồn đầu</th>
                                        <th class="px-4 py-3 text-right">Nhập</th>
                                        <th class="px-4 py-3 text-right">Xuất</th>
                                        <th class="px-4 py-3 text-right">Tồn cuối</th>

                                        <th class="px-4 py-3 text-center">Trạng thái</th>
                                    </tr>
                                </thead>

                                <tbody id="inventoryTableBody">
                                    <tr>
                                        <td colspan="8" class="text-center py-12 text-gray-400">
                                            <i class="fas fa-calendar-check text-4xl mb-3 block opacity-50"></i>
                                            <p class="font-medium">Chọn ngày và nhấn "Tra cứu" để xem tồn kho</p>
                                            <p class="text-xs mt-1 opacity-70">Dữ liệu sẽ được tính toán dựa trên lịch
                                                sử nhập/xuất</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 2: BÁO CÁO NHẬP - XUẤT -->
                    <div id="reportTab" class="tab-content hidden">

                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-chart-line text-primary mr-2"></i>
                                Báo cáo nhập - xuất theo thời gian
                            </h3>
                            <p class="text-sm text-blue-600 mb-3">
                                <i class="fas fa-info-circle mr-1"></i>
                                <strong>Lưu ý:</strong> Bạn có thể để trống ngày để xem tất cả dữ liệu, hoặc chỉ
                                chọn 1
                                ngày
                            </p>

                            <div class="flex flex-wrap gap-3 mb-6 items-end">

                                <!-- Khoảng thời gian -->
                                <div class="flex-1 min-w-[280px] pb-5 relative">
                                    <label class="block text-xs text-gray-500 mb-1">Khoảng thời gian</label>
                                    <div class="flex gap-2 items-center">
                                        <input type="date" id="reportFromDate" max="<?= date('Y-m-d') ?>"
                                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none text-sm"
                                            placeholder="Từ ngày">
                                        <span class="text-gray-400">→</span>
                                        <input type="date" id="reportToDate" max="<?= date('Y-m-d') ?>"
                                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none text-sm"
                                            placeholder="Đến ngày">
                                    </div>
                                    <span id="dateError"
                                        class="absolute left-0 bottom-0 text-xs text-red-500 hidden whitespace-nowrap">
                                        <i class="fas fa-exclamation-circle mr-1"></i>Ngày bắt đầu không được lớn
                                        hơn
                                        ngày kết thúc
                                    </span>
                                </div>

                                <!-- Filter sản phẩm -->
                                <div class="relative flex-1 min-w-[200px] pb-5">
                                    <label class="block text-xs text-gray-500 mb-1">Tìm sản phẩm</label>
                                    <div class="relative">
                                        <input type="text" id="reportProductSearch" placeholder="Nhập tên sản phẩm..."
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none text-sm pr-8"
                                            autocomplete="off">
                                        <button type="button" id="clearProductSearch"
                                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" id="reportProductId">
                                    <div id="reportProductSuggestions"
                                        class="absolute top-full left-0 z-50 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden">
                                    </div>
                                </div>

                                <!-- Nút hành động -->
                                <div class="flex gap-2 items-end pb-5">
                                    <button onclick="generateReport()" id="btnGenerateReport"
                                        class="bg-gradient-custom text-white px-5 py-2 rounded-lg hover:opacity-90 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                                        <i class="fas fa-chart-bar mr-1"></i>Xem báo cáo
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div id="inventorySummary" class="p-4 bg-blue-50 rounded-lg border border-blue-200 mb-4 hidden">
                            <div class="flex flex-wrap justify-center gap-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-calendar-range text-blue-600"></i>
                                    <span class="text-blue-800 font-medium">Kỳ báo cáo:</span>
                                    <span id="summaryPeriod" class="font-semibold text-blue-700">-</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-box-open text-indigo-600"></i>
                                    <span class="text-gray-600">Tồn đầu:</span>
                                    <span id="summaryTonDau" class="font-bold text-indigo-700">-</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-arrow-down text-green-600"></i>
                                    <span class="text-gray-600">Tổng nhập:</span>
                                    <span id="summaryNhap" class="font-bold text-green-700">-</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-arrow-up text-red-600"></i>
                                    <span class="text-gray-600">Tổng xuất:</span>
                                    <span id="summaryXuat" class="font-bold text-red-700">-</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-boxes text-purple-600"></i>
                                    <span class="text-gray-600">Tồn cuối:</span>
                                    <span id="summaryTonCuoi" class="font-bold text-purple-700">-</span>
                                </div>

                                <span class="text-gray-500 hidden">Giá vốn:</span>
                                <span id="summaryGiaVon" class="font-mono text-gray-700 hidden">-</span>

                            </div>
                        </div>

                        <div id="reportResult" class="space-y-8">
                            <!-- 📥 BẢNG NHẬP -->
                            <div class="overflow-x-auto border border-gray-200 rounded-xl">
                                <div
                                    class="bg-green-50 px-4 py-2 font-semibold text-green-700 border-b border-green-200 flex items-center gap-2">
                                    <i class="fas fa-arrow-down"></i> Chi tiết Nhập hàng
                                </div>
                                <table class="w-full min-w-[950px]">
                                    <thead class="bg-gradient-custom text-white">
                                        <tr>
                                            <th class="px-4 py-3 text-left">Ngày</th>
                                            <th class="px-4 py-3 text-left">Loại</th>
                                            <th class="px-4 py-3 text-left">Mã SP</th>
                                            <th class="px-4 py-3 text-left">Sản phẩm</th>
                                            <th class="px-4 py-3 text-right">SL</th>
                                            <th class="px-4 py-3 text-right">Đơn giá</th>
                                            <th class="px-4 py-3 text-right">Thành tiền</th>
                                            <th class="px-4 py-3 text-center">Chi tiết</th>
                                        </tr>
                                    </thead>
                                    <tbody id="importTableBody">
                                        <tr>
                                            <td colspan="8" class="text-center py-8 text-gray-500">Chọn điều kiện để xem
                                                báo cáo</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- 📤 BẢNG XUẤT -->
                            <div class="overflow-x-auto border border-gray-200 rounded-xl">
                                <div
                                    class="bg-blue-50 px-4 py-2 font-semibold text-blue-700 border-b border-blue-200 flex items-center gap-2">
                                    <i class="fas fa-arrow-up"></i> Chi tiết Xuất hàng / Đơn bán
                                </div>
                                <table class="w-full min-w-[950px]">
                                    <thead class="bg-gradient-custom text-white">
                                        <tr>
                                            <th class="px-4 py-3 text-left">Ngày</th>
                                            <th class="px-4 py-3 text-left">Loại</th>
                                            <th class="px-4 py-3 text-left">Mã SP</th>
                                            <th class="px-4 py-3 text-left">Sản phẩm</th>
                                            <th class="px-4 py-3 text-right">SL</th>
                                            <th class="px-4 py-3 text-right">Đơn giá</th>
                                            <th class="px-4 py-3 text-right">Thành tiền</th>
                                            <th class="px-4 py-3 text-center">Chi tiết</th>
                                        </tr>
                                    </thead>
                                    <tbody id="exportTableBody">
                                        <tr>
                                            <td colspan="8" class="text-center py-8 text-gray-500">Chọn điều kiện để xem
                                                báo cáo</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: CẢNH BÁO -->
                    <div id="warningTab" class="tab-content hidden">
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-exclamation-triangle text-primary mr-2"></i>
                                Cảnh báo sản phẩm sắp hết hàng
                            </h3>
                            <p class="text-sm text-gray-500 mb-4">* Lọc theo danh mục, ngưỡng tồn kho hoặc tên sản
                                phẩm
                            </p>

                            <!-- Filter cho tab cảnh báo -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                                <!-- Filter danh mục -->
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Loại sản phẩm</label>
                                    <select id="warningCategory"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none">
                                        <option value="">-- Tất cả danh mục --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['Danhmuc_id']; ?>">
                                                <?php echo htmlspecialchars($cat['Ten_danhmuc']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Filter ngưỡng tồn kho  -->
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Ngưỡng cảnh báo ≥</label>
                                    <input type="number" id="warningThreshold" min="0" placeholder="VD: 10"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none"
                                        title="Lọc sản phẩm có ngưỡng cảnh báo >= giá trị nhập">
                                </div>

                                <!-- Filter tìm theo tên sản phẩm -->
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Tìm theo tên</label>
                                    <input type="text" id="warningKeyword" placeholder="VD: Yonex..."
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none">
                                </div>

                                <!-- Nút hành động -->
                                <div class="flex items-end gap-2">
                                    <button onclick="searchWarning()"
                                        class="flex-1 bg-gradient-custom text-white px-4 py-2 rounded-lg hover:opacity-90 transition">
                                        <i class="fas fa-search mr-2"></i>Áp dụng
                                    </button>
                                    <button onclick="resetWarningFilter()"
                                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                                        title="Xóa bộ lọc">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Hiển thị trạng thái filter -->
                            <div id="warningFilterStatus"
                                class="hidden text-sm text-gray-600 mb-2 p-2 bg-gray-50 rounded-lg">
                                <i class="fas fa-filter mr-1"></i>
                                <span id="warningFilterText"></span>
                                <button onclick="resetWarningFilter()" class="text-primary hover:underline ml-2">Xóa
                                    lọc</button>
                            </div>
                        </div>

                        <div class="overflow-x-auto border border-gray-200 rounded-xl">
                            <table class="w-full min-w-[800px]" id="warningTable">
                                <thead class="bg-gradient-custom text-white">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Mã SP</th>
                                        <th class="px-4 py-3 text-left">Tên sản phẩm</th>
                                        <th class="px-4 py-3 text-left">Danh mục</th>
                                        <th class="px-4 py-3 text-right">Tồn kho</th>
                                        <th class="px-4 py-3 text-right">Ngưỡng cảnh báo</th>
                                        <th class="px-4 py-3 text-center">Trạng thái</th>
                                        <th class="px-4 py-3 text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody id="warningTableBody">
                                    <?php foreach ($products as $p): ?>
                                        <?php
                                        $ton = $p['SoLuongTon'];
                                        $nguong = $p['canh_bao'];
                                        $statusClass = '';
                                        $statusText = '';
                                        $showRow = true; // Mặc định hiển thị
                                    
                                        if ($ton == 0) {
                                            $statusClass = 'badge-danger';
                                            $statusText = '🔴 Hết hàng';
                                        } elseif ($ton <= $nguong) {
                                            $statusClass = 'badge-warning';
                                            $statusText = '⚠️ Sắp hết';
                                        } else {
                                            $statusClass = 'badge-success';
                                            $statusText = '✅ Bình thường';
                                            // Có thể ẩn sản phẩm bình thường nếu muốn chỉ xem cảnh báo
                                            // $showRow = false;
                                        }

                                        // Data attributes để filter JS
                                        $dataCategory = htmlspecialchars($p['Ten_danhmuc'] ?? '');
                                        $dataName = htmlspecialchars($p['TenSP'] ?? '');
                                        ?>
                                        <tr class="hover:bg-gray-50 transition warning-row"
                                            data-category="<?php echo $dataCategory; ?>"
                                            data-name="<?php echo $dataName; ?>" data-ton="<?php echo $ton; ?>"
                                            data-nguong="<?php echo $nguong; ?>"
                                            style="<?php echo $showRow ? '' : 'display:none;'; ?>">
                                            <td class="px-4 py-3 font-mono">
                                                SP<?php echo str_pad($p['SanPham_id'], 4, '0', STR_PAD_LEFT); ?>
                                            </td>
                                            <td class="px-4 py-3 font-medium">
                                                <?php echo htmlspecialchars($p['TenSP']); ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <?php echo htmlspecialchars($p['Ten_danhmuc'] ?? 'Chưa có'); ?>
                                            </td>
                                            <td
                                                class="px-4 py-3 text-right font-semibold <?php echo $ton <= $nguong ? 'text-red-600' : 'text-green-600'; ?>">
                                                <?php echo number_format($ton); ?>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <!-- Chỉ hiển thị ngưỡng, không cho edit -->
                                                <span
                                                    class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                                                    <?php echo number_format($nguong); ?>

                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="px-2 py-1 rounded-full text-xs <?php echo $statusClass; ?>">
                                                    <?php echo $statusText; ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <?php if ($ton <= $nguong): ?>
                                                    <a href="import.php?product_id=<?php echo $p['SanPham_id']; ?>"
                                                        class="text-green-500 hover:text-green-700 font-medium">
                                                        <i class="fas fa-truck-loading mr-1"></i>Nhập hàng
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-gray-400">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Stats summary -->
                        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="p-4 bg-red-50 rounded-lg border border-red-200">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-circle text-red-500 text-lg"></i>
                                    <div>
                                        <p class="text-xs text-red-600 font-medium">HẾT HÀNG</p>
                                        <p class="text-xl font-bold text-red-700" id="countOutStock">0</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-exclamation-triangle text-yellow-500 text-lg"></i>
                                    <div>
                                        <p class="text-xs text-yellow-600 font-medium">SẮP HẾT</p>
                                        <p class="text-xl font-bold text-yellow-700" id="countLowStock">0</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-check-circle text-green-500 text-lg"></i>
                                    <div>
                                        <p class="text-xs text-green-600 font-medium">ĐỦ HÀNG</p>
                                        <p class="text-xl font-bold text-green-700" id="countOkStock">0</p>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
            </main>
        </div>

        <script>
            function showTab(tabName) {
                document.getElementById('inventoryTab').classList.add('hidden');
                document.getElementById('reportTab').classList.add('hidden');
                document.getElementById('warningTab').classList.add('hidden');

                document.getElementById('tabInventoryBtn').classList.remove('active', 'bg-gradient-custom', 'text-white');
                document.getElementById('tabReportBtn').classList.remove('active', 'bg-gradient-custom', 'text-white');
                document.getElementById('tabWarningBtn').classList.remove('active', 'bg-gradient-custom', 'text-white');
                document.getElementById('tabInventoryBtn').classList.add('text-gray-600');
                document.getElementById('tabReportBtn').classList.add('text-gray-600');
                document.getElementById('tabWarningBtn').classList.add('text-gray-600');

                if (tabName === 'inventory') {
                    document.getElementById('inventoryTab').classList.remove('hidden');
                    document.getElementById('tabInventoryBtn').classList.add('active', 'bg-gradient-custom', 'text-white');
                } else if (tabName === 'report') {
                    document.getElementById('reportTab').classList.remove('hidden');
                    document.getElementById('tabReportBtn').classList.add('active', 'bg-gradient-custom', 'text-white');
                } else if (tabName === 'warning') {
                    document.getElementById('warningTab').classList.remove('hidden');
                    document.getElementById('tabWarningBtn').classList.add('active', 'bg-gradient-custom', 'text-white');
                }
            }

            // Hàm lọc tồn kho theo danh mục và ngưỡng
            function searchInventory() {
                const categoryId = document.getElementById('filterCategory').value;
                const threshold = document.getElementById('filterThreshold').value;
                const rows = document.querySelectorAll('#inventoryTableBody tr');
                let visibleCount = 0;
                let filterText = [];

                rows.forEach(row => {
                    // Lấy dữ liệu từ các cột
                    const rowCategorySelect = document.getElementById('filterCategory');
                    const selectedCategoryText = rowCategorySelect.options[rowCategorySelect.selectedIndex]?.text || '';
                    const rowCategoryCell = row.cells[2]?.textContent.trim() || '';

                    // Lấy số lượng tồn kho (cột 3 - index 3)
                    const tonText = row.cells[3]?.textContent.trim().replace(/\./g, '').replace(/[^0-9]/g, '') || '0';
                    const tonKho = parseInt(tonText) || 0;

                    // Lọc theo danh mục
                    const categoryMatch = categoryId === '' || rowCategoryCell === selectedCategoryText;

                    // Lọc theo ngưỡng tồn kho
                    const thresholdMatch = threshold === '' || tonKho >= parseInt(threshold);

                    // Hiển thị/ẩn row
                    if (categoryMatch && thresholdMatch) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Hiển thị trạng thái filter
                showFilterStatus(categoryId, threshold, visibleCount, rows.length);
            }

            // Hàm hiển thị trạng thái filter
            function showFilterStatus(categoryId, threshold, visible, total) {
                const statusDiv = document.getElementById('filterStatus');
                const statusText = document.getElementById('filterStatusText');
                const parts = [];

                if (categoryId) {
                    const catName = document.querySelector(`#filterCategory option[value="${categoryId}"]`)?.text || '';
                    parts.push(`Danh mục: ${catName}`);
                }
                if (threshold) {
                    parts.push(`Tồn kho ≥ ${threshold}`);
                }

                if (parts.length > 0) {
                    statusText.innerHTML = `<strong>${parts.join(' | ')}</strong> — Hiển thị ${visible}/${total} sản phẩm`;
                    statusDiv.classList.remove('hidden');
                } else {
                    statusDiv.classList.add('hidden');
                }
            }

            // Hàm reset filter
            function resetInventoryFilter() {
                document.getElementById('filterCategory').value = '';
                document.getElementById('filterThreshold').value = '';
                document.getElementById('inventoryDate').value = '';

                // Hiển thị lại tất cả rows
                const rows = document.querySelectorAll('#inventoryTableBody tr');
                rows.forEach(row => row.style.display = '');

                // Ẩn status filter
                document.getElementById('filterStatus').classList.add('hidden');
            }

            // Cho phép Enter để áp dụng filter
            document.addEventListener('DOMContentLoaded', function () {
                const thresholdInput = document.getElementById('filterThreshold');
                if (thresholdInput) {
                    thresholdInput.addEventListener('keypress', function (e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            searchInventory();
                        }
                    });
                }
            });

            // 🔹 Hàm toggle dropdown chi tiết
            function toggleReportDetail(id) {
                const detailRow = document.querySelector(`.detail-row-${id}`);
                const btn = document.getElementById(`detail-btn-${id}`);
                if (!detailRow || !btn) return;

                if (detailRow.style.display === 'none' || detailRow.style.display === '') {
                    detailRow.style.display = 'table-row';
                    btn.innerHTML = '<i class="fas fa-eye-slash"></i>';
                    btn.classList.add('bg-blue-700', 'shadow-md');
                } else {
                    detailRow.style.display = 'none';
                    btn.innerHTML = '<i class="fas fa-eye"></i>';
                    btn.classList.remove('bg-blue-700', 'shadow-md');
                }
            }

            // 🔹 Hàm render báo cáo nhập - xuất riêng biệt
            function generateReport() {
                const from = document.getElementById('reportFromDate')?.value || '';
                const to = document.getElementById('reportToDate')?.value || '';
                const productId = document.getElementById('reportProductId')?.value || '';

                // ✅ VALIDATION 1: Yêu cầu chọn ít nhất 1 ngày
                if (!from && !to) {
                    alert('⚠️ Vui lòng chọn ngày trong ô "Từ ngày" hoặc "Đến ngày" để xem báo cáo!');
                    return;
                }

                // ✅ VALIDATION 2: Yêu cầu CHỌN SẢN PHẨM (bắt buộc)
                if (!productId) {
                    alert('⚠️ Vui lòng chọn sản phẩm từ ô tìm kiếm để xem báo cáo!');
                    document.getElementById('reportProductSearch')?.focus();
                    return;
                }

                // ✅ VALIDATION 3: Kiểm tra khoảng ngày hợp lệ
                if (!isDateRangeValid(from, to)) return;

                const importBody = document.getElementById('importTableBody');
                const exportBody = document.getElementById('exportTableBody');

                // Loading state
                const loadingHtml = '<tr><td colspan="8" class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-primary"></i><p class="mt-2 text-gray-600">Đang tải dữ liệu...</p></td></tr>';
                importBody.innerHTML = loadingHtml;
                exportBody.innerHTML = loadingHtml;

                let url = `?get_report=1`;
                if (from) url += `&from=${from}`;
                if (to) url += `&to=${to}`;
                if (productId) url += `&product=${productId}`;

                fetch(url)
                    .then(res => {
                        if (!res.ok) throw new Error('Network response was not ok');
                        return res.json();
                    })
                    .then(data => {
                        const reports = Array.isArray(data) ? data : (data.reports || []);
                        const inventory = data.inventory || {};
                        const period = data.period || {};

                        //  Hiển thị summary tồn kho nếu có chọn sản phẩm


                        const summaryBox = document.getElementById('inventorySummary');
                        const summaryInner = summaryBox.querySelector('.flex.flex-wrap');
                        const hasProduct = inventory.has_product === true && productId;

                        // 🔹 Bước 1: Luôn xóa hint-msg cũ trước (nếu có)
                        const oldHint = summaryBox.querySelector('.hint-msg');
                        if (oldHint) oldHint.remove();

                        if (hasProduct) {
                            // ✅ Có chọn sản phẩm → Hiển thị số liệu
                            summaryBox.classList.remove('hidden');

                            const formatDate = (d) => d ? new Date(d).toLocaleDateString('vi-VN') : 'Từ đầu';
                            document.getElementById('summaryPeriod').textContent =
                                `${formatDate(period.from)} → ${formatDate(period.to)}`;

                            const fmt = (n) => (n || 0).toLocaleString('vi-VN');
                            const fmtMoney = (n) => (n || 0).toLocaleString('vi-VN') + 'đ';

                            document.getElementById('summaryTonDau').textContent = fmt(inventory.ton_dau);
                            document.getElementById('summaryNhap').textContent = '+' + fmt(inventory.tong_nhap);
                            document.getElementById('summaryXuat').textContent = '-' + fmt(inventory.tong_xuat);
                            document.getElementById('summaryTonCuoi').textContent = fmt(inventory.ton_cuoi);
                            document.getElementById('summaryGiaVon').textContent = fmtMoney(inventory.gia_von);

                            // Màu cảnh báo
                            const tonCuoiEl = document.getElementById('summaryTonCuoi');
                            if (inventory.ton_cuoi <= 0) {
                                tonCuoiEl.className = 'font-bold text-red-600';
                            } else if (inventory.ton_cuoi <= 10) {
                                tonCuoiEl.className = 'font-bold text-yellow-600';
                            } else {
                                tonCuoiEl.className = 'font-bold text-purple-700';
                            }

                        } else {
                            // ❌ Chưa chọn sản phẩm → Hiển thị message hướng dẫn
                            summaryBox.classList.remove('hidden');

                            document.getElementById('summaryPeriod').textContent =
                                period.from ? `${new Date(period.from).toLocaleDateString('vi-VN')} → ${new Date(period.to).toLocaleDateString('vi-VN')}` : 'Tất cả thời gian';
                            document.getElementById('summaryTonDau').textContent = '-';
                            document.getElementById('summaryNhap').textContent = '-';
                            document.getElementById('summaryXuat').textContent = '-';
                            document.getElementById('summaryTonCuoi').textContent = '-';
                            document.getElementById('summaryGiaVon').textContent = '-';

                            // Thêm hint message (sau khi đã xóa cái cũ ở trên)
                            const hint = document.createElement('div');
                            hint.className = 'hint-msg w-full text-center text-sm text-blue-600 mt-2 pt-2';
                            hint.innerHTML = '<i class="fas fa-info-circle mr-1"></i>Chọn sản phẩm để xem chi tiết tồn kho';
                            summaryInner.appendChild(hint);
                        }
                        const imports = reports.filter(d => d.type === 'Nhập');
                        const exports = reports.filter(d => d.type !== 'Nhập');

                        // --- RENDER BẢNG NHẬP ---
                        if (imports.length === 0) {
                            importBody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-gray-500"><i class="fas fa-inbox text-3xl mb-2 block opacity-50"></i>Không có dữ liệu nhập</td></tr>';
                        } else {
                            let html = '';
                            let totalQty = 0, totalVal = 0;
                            imports.forEach((item, i) => {
                                const id = `imp-${i}`;
                                const qty = parseFloat(item.quantity) || 0;
                                const price = parseFloat(item.price) || 0;
                                const total = parseFloat(item.total) || 0;
                                totalQty += qty; totalVal += total;

                                html += `
                <tr class="border-b hover:bg-gray-50 transition">
                    <td class="px-4 py-3">${item.date || ''}</td>
                    <td class="px-4 py-3"><span class="text-green-600 font-medium">${item.type}</span></td>
                    <td class="px-4 py-3 font-mono">SP${String(item.product_id || 0).padStart(4, '0')}</td>
                    <td class="px-4 py-3">${item.product_name || ''}</td>
                    <td class="px-4 py-3 text-right">${qty.toLocaleString('vi-VN')}</td>
                    <td class="px-4 py-3 text-right">${price.toLocaleString('vi-VN')}đ</td>
                    <td class="px-4 py-3 text-right">${total.toLocaleString('vi-VN')}đ</td>
                    <td class="px-4 py-3 text-center">
                        <button id="detail-btn-${id}" onclick="toggleReportDetail('${id}')" class="bg-blue-500 hover:bg-blue-600 text-white p-2 rounded cursor-pointer transition" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
                <tr class="detail-row-${id}" style="display:none;">
                    <td colspan="8" class="p-0">
                        <div class="bg-gray-50 p-4 border-t border-gray-200">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div><span class="text-gray-500">Mã SP:</span> <strong>SP${String(item.product_id || 0).padStart(4, '0')}</strong></div>
                                <div><span class="text-gray-500">Tên SP:</span> <strong>${item.product_name || 'N/A'}</strong></div>
                                <div><span class="text-gray-500">Số lượng:</span> <strong>${qty.toLocaleString('vi-VN')}</strong></div>
                                <div><span class="text-gray-500">Đơn giá:</span> <strong>${price.toLocaleString('vi-VN')}đ</strong></div>
                                <div><span class="text-gray-500">Thành tiền:</span> <strong>${total.toLocaleString('vi-VN')}đ</strong></div>
                                <div><span class="text-gray-500">Ngày:</span> <strong>${item.date || 'N/A'}</strong></div>
                                <div><span class="text-gray-500">Loại:</span> <strong class="text-green-600">${item.type}</strong></div>
                              
                            </div>
                        </div>
                    </td>
                </tr>`;
                            });
                            html += `<tr class="bg-gray-100 font-bold border-t-2 border-gray-300">
                <td colspan="4" class="px-4 py-3 text-right">TỔNG NHẬP:</td>
                <td class="px-4 py-3 text-right text-green-600">${totalQty.toLocaleString('vi-VN')}</td>
                <td class="px-4 py-3 text-right">-</td>
                <td class="px-4 py-3 text-right text-indigo-700">${totalVal.toLocaleString('vi-VN')}đ</td>
                <td class="px-4 py-3"></td>
            </tr>`;
                            importBody.innerHTML = html;
                        }

                        // --- RENDER BẢNG XUẤT ---
                        if (exports.length === 0) {
                            exportBody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-gray-500"><i class="fas fa-inbox text-3xl mb-2 block opacity-50"></i>Không có dữ liệu xuất</td></tr>';
                        } else {
                            let html = '';
                            let totalQty = 0, totalVal = 0;
                            exports.forEach((item, i) => {
                                const id = `exp-${i}`;
                                const qty = parseFloat(item.quantity) || 0;
                                const price = parseFloat(item.price) || 0;
                                const total = parseFloat(item.total) || 0;
                                totalQty += qty; totalVal += total;

                                html += `
                <tr class="border-b hover:bg-gray-50 transition">
                    <td class="px-4 py-3">${item.date || ''}</td>
                    <td class="px-4 py-3"><span class="text-blue-600 font-medium">${item.type}</span></td>
                    <td class="px-4 py-3 font-mono">SP${String(item.product_id || 0).padStart(4, '0')}</td>
                    <td class="px-4 py-3">${item.product_name || ''}</td>
                    <td class="px-4 py-3 text-right">${qty.toLocaleString('vi-VN')}</td>
                    <td class="px-4 py-3 text-right">${price.toLocaleString('vi-VN')}đ</td>
                    <td class="px-4 py-3 text-right">${total.toLocaleString('vi-VN')}đ</td>
                    <td class="px-4 py-3 text-center">
                        <button id="detail-btn-${id}" onclick="toggleReportDetail('${id}')" class="bg-blue-500 hover:bg-blue-600 text-white p-2 rounded cursor-pointer transition" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
                <tr class="detail-row-${id}" style="display:none;">
                    <td colspan="8" class="p-0">
                        <div class="bg-gray-50 p-4 border-t border-gray-200">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div><span class="text-gray-500">Mã SP:</span> <strong>SP${String(item.product_id || 0).padStart(4, '0')}</strong></div>
                                <div><span class="text-gray-500">Tên SP:</span> <strong>${item.product_name || 'N/A'}</strong></div>
                                <div><span class="text-gray-500">Số lượng:</span> <strong>${qty.toLocaleString('vi-VN')}</strong></div>
                                <div><span class="text-gray-500">Đơn giá:</span> <strong>${price.toLocaleString('vi-VN')}đ</strong></div>
                                <div><span class="text-gray-500">Thành tiền:</span> <strong>${total.toLocaleString('vi-VN')}đ</strong></div>
                                <div><span class="text-gray-500">Ngày:</span> <strong>${item.date || 'N/A'}</strong></div>
                                <div><span class="text-gray-500">Loại:</span> <strong class="text-blue-600">${item.type}</strong></div>
                             
                            </div>
                        </div>
                    </td>
                </tr>`;
                            });
                            html += `<tr class="bg-gray-100 font-bold border-t-2 border-gray-300">
                <td colspan="4" class="px-4 py-3 text-right">TỔNG XUẤT:</td>
                <td class="px-4 py-3 text-right text-red-600">${totalQty.toLocaleString('vi-VN')}</td>
                <td class="px-4 py-3 text-right">-</td>
                <td class="px-4 py-3 text-right text-indigo-700">${totalVal.toLocaleString('vi-VN')}đ</td>
                <td class="px-4 py-3"></td>
            </tr>`;
                            exportBody.innerHTML = html;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        const errHtml = '<tr><td colspan="8" class="text-center py-8 text-red-500"><i class="fas fa-exclamation-triangle text-2xl mb-2 block"></i>Có lỗi xảy ra khi tải dữ liệu!</td></tr>';
                        importBody.innerHTML = errHtml;
                        exportBody.innerHTML = errHtml;
                    });
            }

            function exportReport() {
                const fromDate = document.getElementById('reportFromDate').value;
                const toDate = document.getElementById('reportToDate').value;
                const productId = document.getElementById('reportProduct').value;

                let url = `export_report.php?from=${fromDate}&to=${toDate}&product=${productId}`;
                window.open(url, '_blank');
            }

            function logout() {
                if (confirm('Bạn có chắc muốn đăng xuất?')) {
                    window.location.href = 'logout.php';
                }
            }

            // Cho phép Enter để tìm kiếm
            document.addEventListener('DOMContentLoaded', function () {
                const fromDate = document.getElementById('reportFromDate');
                const toDate = document.getElementById('reportToDate');

                if (fromDate) {
                    fromDate.addEventListener('keypress', function (e) {
                        if (e.key === 'Enter') generateReport();
                    });
                }
                if (toDate) {
                    toDate.addEventListener('keypress', function (e) {
                        if (e.key === 'Enter') generateReport();
                    });
                }
            });
            function searchWarning() {
                const categoryId = document.getElementById('warningCategory').value;
                const threshold = document.getElementById('warningThreshold').value;  // Ngưỡng cảnh báo cần lọc
                const keyword = document.getElementById('warningKeyword').value.toLowerCase();
                const rows = document.querySelectorAll('#warningTableBody tr.warning-row');

                let visibleCount = 0;
                let outStock = 0, lowStock = 0, okStock = 0;

                rows.forEach(row => {
                    const rowCategory = row.dataset.category || '';
                    const rowName = (row.dataset.name || '').toLowerCase();
                    const tonKho = parseInt(row.dataset.ton) || 0;
                    const nguong = parseInt(row.dataset.nguong) || 10;  // Ngưỡng cảnh báo của sản phẩm

                    // Lọc theo danh mục
                    const categoryMatch = categoryId === '' ||
                        rowCategory === document.querySelector(`#warningCategory option[value="${categoryId}"]`)?.text;

                    //  SỬA: Lọc theo NGƯỠNG CẢNH BÁO (nguong), không phải tồn kho (tonKho)
                    // Hiển thị sản phẩm có ngưỡng cảnh báo >= giá trị nhập
                    const thresholdMatch = threshold === '' || nguong >= parseInt(threshold);

                    // Lọc theo từ khóa tên sản phẩm
                    const keywordMatch = keyword === '' || rowName.includes(keyword);

                    // Hiển thị/ẩn row
                    if (categoryMatch && thresholdMatch && keywordMatch) {
                        row.style.display = '';
                        visibleCount++;

                        // Đếm thống kê (vẫn dựa trên trạng thái thực tế)
                        if (tonKho === 0) outStock++;
                        else if (tonKho <= nguong) lowStock++;
                        else okStock++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Cập nhật stats
                document.getElementById('countOutStock').textContent = outStock;
                document.getElementById('countLowStock').textContent = lowStock;
                document.getElementById('countOkStock').textContent = okStock;

                // Hiển thị trạng thái filter
                showWarningFilterStatus(categoryId, threshold, keyword, visibleCount, rows.length);
            }

            function showWarningFilterStatus(categoryId, threshold, keyword, visible, total) {
                const statusDiv = document.getElementById('warningFilterStatus');
                const statusText = document.getElementById('warningFilterText');
                const parts = [];

                if (categoryId) {
                    const catName = document.querySelector(`#warningCategory option[value="${categoryId}"]`)?.text || '';
                    parts.push(`Danh mục: ${catName}`);
                }
                // SỬA: Hiển thị "Ngưỡng ≥" thay vì "Tồn ≤"
                if (threshold) {
                    parts.push(`Ngưỡng cảnh báo ≥ ${threshold}`);
                }
                if (keyword) {
                    parts.push(`Tên chứa: "${keyword}"`);
                }

                if (parts.length > 0) {
                    statusText.innerHTML = `<strong>${parts.join(' | ')}</strong> — Hiển thị ${visible}/${total} sản phẩm`;
                    statusDiv.classList.remove('hidden');
                } else {
                    statusDiv.classList.add('hidden');
                }
            }

            // Reset filter warning
            function resetWarningFilter() {
                document.getElementById('warningCategory').value = '';
                document.getElementById('warningThreshold').value = '';
                document.getElementById('warningKeyword').value = '';

                const rows = document.querySelectorAll('#warningTableBody tr.warning-row');
                rows.forEach(row => row.style.display = '');

                document.getElementById('warningFilterStatus').classList.add('hidden');

                // Reset stats về tổng
                updateWarningStats();
            }

            // Cập nhật thống kê ban đầu
            function updateWarningStats() {
                let outStock = 0, lowStock = 0, okStock = 0;

                document.querySelectorAll('#warningTableBody tr.warning-row').forEach(row => {
                    const tonKho = parseInt(row.dataset.ton) || 0;
                    const nguong = parseInt(row.dataset.nguong) || 10;

                    if (tonKho === 0) outStock++;
                    else if (tonKho <= nguong) lowStock++;
                    else okStock++;
                });

                document.getElementById('countOutStock').textContent = outStock;
                document.getElementById('countLowStock').textContent = lowStock;
                document.getElementById('countOkStock').textContent = okStock;
            }

            // Init stats khi load trang
            document.addEventListener('DOMContentLoaded', function () {
                updateWarningStats();

                // Enter để filter warning
                const warningInputs = ['warningThreshold', 'warningKeyword'];
                warningInputs.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.addEventListener('keypress', function (e) {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                searchWarning();
                            }
                        });
                    }
                });

            });
            // ==========================================
            // AUTOCOMPLETE TÌM KIẾM SẢN PHẨM
            // ==========================================
            const searchInput = document.getElementById('reportProductSearch');
            const suggestionsBox = document.getElementById('reportProductSuggestions');
            const hiddenProductId = document.getElementById('reportProductId');
            const clearBtn = document.getElementById('clearProductSearch');

            // Debounce để tránh gọi API liên tục
            function debounce(func, wait) {
                let timeout;
                return function (...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), wait);
                };
            }

            if (searchInput) {
                searchInput.addEventListener('input', debounce(function () {
                    const query = this.value.trim();
                    hiddenProductId.value = ''; // Xóa ID đã chọn khi gõ mới
                    clearBtn.classList.toggle('hidden', query.length === 0);

                    if (query.length < 2) {
                        suggestionsBox.classList.add('hidden');
                        return;
                    }

                    fetch(`../control/search-product.php?q=${encodeURIComponent(query)}&limit=10`)
                        .then(res => res.json())
                        .then(data => {
                            suggestionsBox.innerHTML = '';
                            if (!data || data.length === 0 || data.error) {
                                suggestionsBox.classList.add('hidden');
                                return;
                            }

                            data.forEach(prod => {
                                const div = document.createElement('div');
                                div.className = 'px-4 py-2 cursor-pointer hover:bg-gray-100 text-sm flex justify-between items-center border-b border-gray-100 last:border-0';
                                div.innerHTML = `
                        <span class="truncate font-medium text-gray-700">${prod.name}</span>
                        <span class="text-xs text-gray-400 ml-2">Mã: ${prod.id} | Tồn: ${prod.ton}</span>
                    `;
                                div.addEventListener('click', () => {
                                    searchInput.value = prod.name;
                                    hiddenProductId.value = prod.id;
                                    suggestionsBox.classList.add('hidden');
                                    clearBtn.classList.remove('hidden');
                                });
                                suggestionsBox.appendChild(div);
                            });
                            suggestionsBox.classList.remove('hidden');
                        })
                        .catch(err => console.error('Lỗi tìm kiếm:', err));
                }, 300));

                // Đóng gợi ý khi click ra ngoài
                document.addEventListener('click', (e) => {
                    if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                        suggestionsBox.classList.add('hidden');
                    }
                });

                // Xử lý nút xóa
                clearBtn.addEventListener('click', () => {
                    searchInput.value = '';
                    hiddenProductId.value = '';
                    suggestionsBox.classList.add('hidden');
                    clearBtn.classList.add('hidden');
                    searchInput.focus();
                });
            }


            function isDateRangeValid(from, to) {
                if (!from || !to) return true;
                return new Date(from) <= new Date(to);
            }

            function updateReportButton() {
                const from = document.getElementById('reportFromDate')?.value || '';
                const to = document.getElementById('reportToDate')?.value || '';
                const errorText = document.getElementById('dateError');
                const btn = document.getElementById('btnGenerateReport');

                const isValid = isDateRangeValid(from, to);

                // Toggle class hidden để show/hide error
                if (errorText) {
                    if (isValid) {
                        errorText.classList.add('hidden');
                    } else {
                        errorText.classList.remove('hidden');
                    }
                }

                // Disable/enable nút
                if (btn) {
                    btn.disabled = !isValid;
                }
            }

            // Gắn event listener
            document.addEventListener('DOMContentLoaded', function () {
                const fromInput = document.getElementById('reportFromDate');
                const toInput = document.getElementById('reportToDate');

                fromInput?.addEventListener('change', updateReportButton);
                toInput?.addEventListener('change', updateReportButton);

                updateReportButton(); // init
            });


            // 🔹 Helper: Format tiền ngắn gọn (1.2K, 3.5M, 1.2B)
            function formatCurrency(amount) {
                if (!amount) return '0đ';
                const num = Math.abs(parseFloat(amount));
                let suffix = 'đ', value = num;

                if (num >= 1e9) { value = num / 1e9; suffix = 'Bđ'; }
                else if (num >= 1e6) { value = num / 1e6; suffix = 'Mđ'; }
                else if (num >= 1e3) { value = num / 1e3; suffix = 'Kđ'; }

                return value.toFixed(1) + suffix;
            }

            // 🔹 Helper: Escape HTML chống XSS
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // 🔹 Hàm chính: Load dữ liệu tồn kho
            function searchInventoryByDate() {
                const date = document.getElementById('inventoryDate').value;
                const categoryId = document.getElementById('filterCategory').value;

                if (!date) {
                    alert('⚠️ Vui lòng chọn ngày cần xem tồn kho!');
                    document.getElementById('inventoryDate')?.focus();
                    return;
                }

                // Cập nhật hiển thị ngày
                const dateObj = new Date(date);
                document.getElementById('selectedDateDisplay').innerText = dateObj.toLocaleDateString('vi-VN');

                // Show loading
                const tbody = document.getElementById('inventoryTableBody');
                tbody.innerHTML = '<tr><td colspan="11" class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl"></i><p class="mt-2">Đang tải...</p></td></tr>';

                // Gọi API
                let url = `?get_inventory_by_date=1&date=${date}`;
                if (categoryId) url += `&category_id=${categoryId}`;

                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        if (!data || data.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-12 text-gray-400"><i class="fas fa-calendar-check text-4xl mb-3 block opacity-50"></i><p class="font-medium">Chọn ngày và nhấn "Tra cứu" để xem tồn kho</p><p class="text-xs mt-1 opacity-70">Dữ liệu sẽ được tính toán dựa trên lịch sử nhập/xuất</p></td></tr>';
                            return;
                        }

                        let html = '';
                        let totalDau = 0, totalCuoi = 0, totalGT_Dau = 0, totalGT_Cuoi = 0;

                        data.forEach(item => {
                            const tonCuoi = item.ton_cuoi_ngay;
                            const nguong = item.canh_bao;

                            // Badge trạng thái
                            let badge = '';
                            if (tonCuoi == 0) badge = '<span class="badge-danger">🔴 Hết</span>';
                            else if (tonCuoi <= nguong) badge = '<span class="badge-warning flex">⚠️ Sắp hết</span>';
                            else badge = '<span class="badge-success flex">✅ Còn hàng</span>';

                            // Cộng dồn tổng
                            totalDau += item.ton_dau_ngay;
                            totalCuoi += tonCuoi;
                            totalGT_Dau += item.tong_gia_tri_dau;
                            totalGT_Cuoi += item.tong_gia_tri_cuoi;

                            html += `
    <tr class="hover:bg-gray-50 transition">
        <td class="px-4 py-3 font-mono">${escapeHtml(item.masp)}</td>
        <td class="px-4 py-3 font-medium">${escapeHtml(item.ten_sp)}</td>
        <td class="px-4 py-3">${escapeHtml(item.danh_muc)}</td>
        <td class="px-4 py-3 text-right">${item.ton_dau_ngay.toLocaleString('vi-VN')}</td>
        <td class="px-4 py-3 text-right text-green-600">+${item.nhap_trong_ngay.toLocaleString('vi-VN')}</td>
        <td class="px-4 py-3 text-right text-red-600">-${item.xuat_trong_ngay.toLocaleString('vi-VN')}</td>
        <td class="px-4 py-3 text-right font-bold ${tonCuoi <= nguong ? 'text-red-600' : 'text-green-600'}">
            ${tonCuoi.toLocaleString('vi-VN')}
        </td>
        <td class="px-4 py-3 text-center">${badge}</td>
    </tr>`;
                        });

                        // Row tổng cộng
                        html += `
<tr class="bg-gray-100 font-bold border-t-2 border-gray-300">
    <td colspan="3" class="px-4 py-3 text-right">TỔNG:</td>
    <td class="px-4 py-3 text-right">${totalDau.toLocaleString('vi-VN')}</td>
    <td class="px-4 py-3 text-right">-</td>
    <td class="px-4 py-3 text-right">-</td>
    <td class="px-4 py-3 text-right text-indigo-700">${totalCuoi.toLocaleString('vi-VN')}</td>
    <td class="px-4 py-3 text-center">-</td>
</tr>`;
                        tbody.innerHTML = html;
                    })
                    .catch(err => {
                        console.error(err);
                        tbody.innerHTML = '<tr><td colspan="11" class="text-center py-8 text-red-500">❌ Lỗi tải dữ liệu</td></tr>';
                    });
            }

            // 🔹 Reset filter
            function resetInventoryFilter() {
                document.getElementById('filterCategory').value = '';
                document.getElementById('inventoryDate').value = '<?php echo date("Y-m-d"); ?>';
                searchInventoryByDate();
            }


            document.addEventListener('DOMContentLoaded', function () {
                // Chỉ gắn Enter để search, KHÔNG gọi searchInventoryByDate() ngay
                document.getElementById('inventoryDate')?.addEventListener('keypress', function (e) {
                    if (e.key === 'Enter') { e.preventDefault(); searchInventoryByDate(); }
                });

                // Optional: Gắn event cho dropdown category để search khi đổi 
                document.getElementById('filterCategory')?.addEventListener('change', function () {
                    const dateInput = document.getElementById('inventoryDate');
                    if (dateInput?.value) {  // Chỉ search nếu đã có ngày
                        searchInventoryByDate();
                    }
                });
            });

            //  VALIDATE CHẶN NGÀY TƯƠNG LAI (KHI GÕ TAY HOẶC DÁN)
            function validateNoFutureDate(input) {
                if (!input.value) return; // Cho phép ô trống

                // Lấy ngày hôm nay dạng YYYY-MM-DD theo giờ máy khách
                const today = new Date();
                const todayStr = today.getFullYear() + '-' +
                    String(today.getMonth() + 1).padStart(2, '0') + '-' +
                    String(today.getDate()).padStart(2, '0');

                // So sánh chuỗi ngày (định dạng YYYY-MM-DD so sánh > hoạt động chính xác)
                if (input.value > todayStr) {
                    alert("⚠️ Không được chọn ngày trong tương lai! Vui lòng chọn lại.");
                    input.value = '';      // Ép xóa giá trị sai
                    input.focus();         // Trả con trỏ về ô input
                }
            }

            // Gắn sự kiện validate cho 3 ô ngày
            document.addEventListener('DOMContentLoaded', function () {
                ['inventoryDate', 'reportFromDate', 'reportToDate'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        // Chạy validate khi người dùng chọn xong hoặc rời khỏi ô input
                        el.addEventListener('change', () => validateNoFutureDate(el));

                        // Vẫn giữ max để trình duyệt chặn UI date picker
                        const today = new Date().toISOString().split('T')[0];
                        el.setAttribute('max', today);
                    }
                });
            });
        </script>
</body>

</html>
