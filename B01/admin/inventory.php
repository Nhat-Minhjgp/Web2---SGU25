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

    $reports = [];

    // XÂY DỰNG CÂU SQL NHẬP HÀNG VỚI NGÀY OPTIONAL
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

    // Thêm điều kiện ngày nếu có
    if (!empty($from_date) && !empty($to_date)) {
        $sql_import .= " AND pn.NgayNhap BETWEEN ? AND ?";
        $params_import[] = $from_date;
        $params_import[] = $to_date;
        $types_import .= "ss";
    } elseif (!empty($from_date)) {
        $sql_import .= " AND pn.NgayNhap >= ?";
        $params_import[] = $from_date;
        $types_import .= "s";
    } elseif (!empty($to_date)) {
        $sql_import .= " AND pn.NgayNhap <= ?";
        $params_import[] = $to_date;
        $types_import .= "s";
    }

    // Thêm điều kiện sản phẩm nếu có
    if (!empty($product_id)) {
        $sql_import .= " AND sp.SanPham_id = ?";
        $params_import[] = $product_id;
        $types_import .= "i";
    }

    // Thực thi câu lệnh nhập hàng
    $imports = [];
    if (!empty($params_import)) {
        $stmt = $conn->prepare($sql_import);
        if ($stmt) {
            $stmt->bind_param($types_import, ...$params_import);
            $stmt->execute();
            $imports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    } else {
        // Nếu không có tham số nào, lấy tất cả
        $stmt = $conn->prepare($sql_import);
        if ($stmt) {
            $stmt->execute();
            $imports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }

    // XÂY DỰNG CÂU SQL XUẤT HÀNG VỚI NGÀY OPTIONAL
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

    // Thêm điều kiện ngày nếu có
    if (!empty($from_date) && !empty($to_date)) {
        $sql_export .= " AND d.NgayDat BETWEEN ? AND ?";
        $params_export[] = $from_date;
        $params_export[] = $to_date;
        $types_export .= "ss";
    } elseif (!empty($from_date)) {
        $sql_export .= " AND d.NgayDat >= ?";
        $params_export[] = $from_date;
        $types_export .= "s";
    } elseif (!empty($to_date)) {
        $sql_export .= " AND d.NgayDat <= ?";
        $params_export[] = $to_date;
        $types_export .= "s";
    }

    // Thêm điều kiện sản phẩm nếu có
    if (!empty($product_id)) {
        $sql_export .= " AND sp.SanPham_id = ?";
        $params_export[] = $product_id;
        $types_export .= "i";
    }

    // Thực thi câu lệnh xuất hàng
    $exports = [];
    if (!empty($params_export)) {
        $stmt = $conn->prepare($sql_export);
        if ($stmt) {
            $stmt->bind_param($types_export, ...$params_export);
            $stmt->execute();
            $exports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    } else {
        // Nếu không có tham số nào, lấy tất cả
        $stmt = $conn->prepare($sql_export);
        if ($stmt) {
            $stmt->execute();
            $exports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }

    // Gộp và sắp xếp dữ liệu
    $reports = array_merge($imports, $exports);
    usort($reports, function ($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });

    header('Content-Type: application/json');
    echo json_encode($reports);
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
    </style>
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
                            <a href="categories.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
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
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-search text-primary mr-2"></i>
                                Tra cứu tồn kho theo loại sản phẩm
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                <select id="filterCategory"
                                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none">
                                    <option value="">-- Tất cả danh mục --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['Danhmuc_id']; ?>">
                                            <?php echo htmlspecialchars($cat['Ten_danhmuc']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="date" id="inventoryDate"
                                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none">
                                <button onclick="searchInventory()"
                                    class="bg-gradient-custom text-white px-4 py-2 rounded-lg hover:opacity-90 transition">
                                    <i class="fas fa-search mr-2"></i>Tra cứu
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto border border-gray-200 rounded-xl">
                            <table class="w-full min-w-[900px]" id="inventoryTable">
                                <thead class="bg-gradient-custom text-white">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Mã SP</th>
                                        <th class="px-4 py-3 text-left">Tên sản phẩm</th>
                                        <th class="px-4 py-3 text-left">Danh mục</th>
                                        <th class="px-4 py-3 text-right">Tồn kho</th>
                                        <th class="px-4 py-3 text-right">Đã nhập</th>
                                        <th class="px-4 py-3 text-right">Đã xuất</th>
                                        <th class="px-4 py-3 text-right">Giá vốn TB</th>
                                        <th class="px-4 py-3 text-right">Tổng giá trị</th>
                                        <th class="px-4 py-3 text-center">Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody id="inventoryTableBody">
                                    <?php foreach ($products as $p): ?>
                                        <?php
                                        $ton = $p['SoLuongTon'];
                                        $nguong = $p['canh_bao'];
                                        $giavon = $p['GiaNhapTB'] ?? 0;
                                        $tonggiatri = $ton * $giavon;
                                        $rowClass = '';
                                        if ($ton <= $nguong && $ton > 0)
                                            $rowClass = 'inventory-row-low';
                                        if ($ton == 0)
                                            $rowClass = 'inventory-row-critical';
                                        ?>
                                        <tr class="hover:bg-gray-50 transition <?php echo $rowClass; ?>">
                                            <td class="px-4 py-3 font-mono">
                                                SP<?php echo str_pad($p['SanPham_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                            <td class="px-4 py-3 font-medium"><?php echo htmlspecialchars($p['TenSP']); ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <?php echo htmlspecialchars($p['Ten_danhmuc'] ?? 'Chưa có'); ?></td>
                                            <td
                                                class="px-4 py-3 text-right font-semibold <?php echo $ton <= $nguong ? 'text-red-600' : 'text-green-600'; ?>">
                                                <?php echo number_format($ton); ?></td>
                                            <td class="px-4 py-3 text-right"><?php echo number_format($p['tong_nhap']); ?>
                                            </td>
                                            <td class="px-4 py-3 text-right"><?php echo number_format($p['tong_xuat']); ?>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <?php echo number_format($giavon, 0, ',', '.'); ?>đ</td>
                                            <td class="px-4 py-3 text-right">
                                                <?php echo number_format($tonggiatri, 0, ',', '.'); ?>đ</td>
                                            <td class="px-4 py-3 text-center">
                                                <?php if ($ton == 0): ?>
                                                    <span class="badge-danger">🔴 Hết hàng</span>
                                                <?php elseif ($ton <= $nguong): ?>
                                                    <span class="badge-warning">⚠️ Sắp hết
                                                        (<?php echo $ton; ?>/<?php echo $nguong; ?>)</span>
                                                <?php else: ?>
                                                    <span class="badge-success">✅ Còn hàng</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 2: BÁO CÁO NHẬP - XUẤT (NGÀY OPTIONAL) -->
                    <div id="reportTab" class="tab-content hidden">
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-chart-line text-primary mr-2"></i>
                                Báo cáo nhập - xuất theo thời gian
                            </h3>
                            <p class="text-sm text-blue-600 mb-3">
                                <i class="fas fa-info-circle mr-1"></i>
                                <strong>Lưu ý:</strong> Bạn có thể để trống ngày để xem tất cả dữ liệu, hoặc chỉ chọn 1
                                ngày (từ ngày hoặc đến ngày)
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                                <div class="flex gap-2">
                                    <input type="date" id="reportFromDate"
                                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary flex-1"
                                        placeholder="Từ ngày">
                                    <span class="self-center text-gray-400">-</span>
                                    <input type="date" id="reportToDate"
                                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary flex-1"
                                        placeholder="Đến ngày">
                                </div>
                                <select id="reportProduct"
                                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                                    <option value="">-- Tất cả sản phẩm --</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?php echo $p['SanPham_id']; ?>">
                                            SP<?php echo str_pad($p['SanPham_id'], 4, '0', STR_PAD_LEFT); ?> -
                                            <?php echo htmlspecialchars($p['TenSP']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button onclick="generateReport()"
                                    class="bg-gradient-custom text-white px-4 py-2 rounded-lg hover:opacity-90 transition">
                                    <i class="fas fa-chart-bar mr-2"></i>Xem báo cáo
                                </button>
                                <button onclick="exportReport()"
                                    class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition">
                                    <i class="fas fa-file-excel mr-2"></i>Xuất Excel
                                </button>
                            </div>
                        </div>

                        <div id="reportResult" class="overflow-x-auto border border-gray-200 rounded-xl">
                            <table class="w-full min-w-[800px]">
                                <thead class="bg-gradient-custom text-white">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Ngày</th>
                                        <th class="px-4 py-3 text-left">Loại</th>
                                        <th class="px-4 py-3 text-left">Mã SP</th>
                                        <th class="px-4 py-3 text-left">Sản phẩm</th>
                                        <th class="px-4 py-3 text-right">SL</th>
                                        <th class="px-4 py-3 text-right">Đơn giá</th>
                                        <th class="px-4 py-3 text-right">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody id="reportTableBody">
                                    <tr>
                                        <td colspan="7" class="text-center py-8 text-gray-500">
                                            <i class="fas fa-chart-line text-4xl mb-2 block"></i>
                                            Chọn khoảng thời gian (hoặc để trống) để xem báo cáo
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 3: CẢNH BÁO -->
                    <div id="warningTab" class="tab-content hidden">
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-exclamation-triangle text-primary mr-2"></i>
                                Cảnh báo sản phẩm sắp hết hàng
                            </h3>
                            <p class="text-sm text-gray-500 mb-4">* Mỗi sản phẩm có thể thiết lập ngưỡng cảnh báo riêng
                            </p>
                        </div>

                        <div class="overflow-x-auto border border-gray-200 rounded-xl">
                            <table class="w-full min-w-[800px]">
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
                                <tbody>
                                    <?php foreach ($products as $p): ?>
                                        <?php
                                        $ton = $p['SoLuongTon'];
                                        $nguong = $p['canh_bao'];
                                        $statusClass = '';
                                        $statusText = '';
                                        if ($ton == 0) {
                                            $statusClass = 'badge-danger';
                                            $statusText = '🔴 Hết hàng';
                                        } elseif ($ton <= $nguong) {
                                            $statusClass = 'badge-warning';
                                            $statusText = '⚠️ Sắp hết';
                                        } else {
                                            $statusClass = 'badge-success';
                                            $statusText = '✅ Bình thường';
                                        }
                                        ?>
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-4 py-3 font-mono">
                                                SP<?php echo str_pad($p['SanPham_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                            <td class="px-4 py-3 font-medium"><?php echo htmlspecialchars($p['TenSP']); ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <?php echo htmlspecialchars($p['Ten_danhmuc'] ?? 'Chưa có'); ?></td>
                                            <td
                                                class="px-4 py-3 text-right font-semibold <?php echo $ton <= $nguong ? 'text-red-600' : 'text-green-600'; ?>">
                                                <?php echo number_format($ton); ?></td>
                                            <td class="px-4 py-3 text-right">
                                                <form method="POST" class="inline-flex items-center gap-2">
                                                    <input type="hidden" name="product_id"
                                                        value="<?php echo $p['SanPham_id']; ?>">
                                                    <input type="number" name="threshold" value="<?php echo $nguong; ?>"
                                                        class="w-20 px-2 py-1 border border-gray-300 rounded-lg text-center text-sm">
                                                    <button type="submit" name="update_threshold"
                                                        class="text-blue-500 hover:text-blue-700 text-sm">
                                                        <i class="fas fa-save"></i>
                                                    </button>
                                                </form>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span
                                                    class="px-2 py-1 rounded-full text-xs <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <?php if ($ton <= $nguong): ?>
                                                    <a href="import.php" class="text-green-500 hover:text-green-700">
                                                        <i class="fas fa-truck-loading"></i> Nhập hàng
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

                        <div class="mt-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-info-circle text-yellow-600 text-xl"></i>
                                <div>
                                    <p class="font-semibold text-yellow-800">Hướng dẫn</p>
                                    <p class="text-sm text-yellow-700">Mỗi sản phẩm có thể thiết lập ngưỡng cảnh báo
                                        riêng. Khi số lượng tồn kho <= ngưỡng, hệ thống sẽ cảnh báo "Sắp hết" .</p>
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

            function searchInventory() {
                const category = document.getElementById('filterCategory').value;
                let rows = document.querySelectorAll('#inventoryTableBody tr');

                rows.forEach(row => {
                    const rowCategory = row.cells[2]?.textContent.trim() || '';
                    const selectedCategory = document.querySelector(`#filterCategory option[value="${category}"]`)?.textContent || '';
                    const categoryMatch = category === '' || rowCategory === selectedCategory;
                    row.style.display = categoryMatch ? '' : 'none';
                });
            }

            function generateReport() {
                const fromDate = document.getElementById('reportFromDate').value;
                const toDate = document.getElementById('reportToDate').value;
                const productId = document.getElementById('reportProduct').value;

                const tbody = document.getElementById('reportTableBody');
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl"></i><p class="mt-2">Đang tải dữ liệu...</p></td></tr>';

                let url = `?get_report=1`;
                if (fromDate) url += `&from=${fromDate}`;
                if (toDate) url += `&to=${toDate}`;
                if (productId) url += `&product=${productId}`;

                fetch(url)
                    .then(res => {
                        if (!res.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return res.json();
                    })
                    .then(data => {
                        if (!data || data.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-gray-500"><i class="fas fa-inbox text-4xl mb-2 block"></i>Không có dữ liệu</td></tr>';
                            return;
                        }

                        let html = '';
                        let totalImport = 0, totalExport = 0, totalImportQty = 0, totalExportQty = 0;

                        data.forEach(item => {
                            // Đảm bảo các giá trị là số
                            const quantity = parseFloat(item.quantity) || 0;
                            const price = parseFloat(item.price) || 0;
                            const total = parseFloat(item.total) || 0;
                            const typeClass = item.type === 'Nhập' ? 'text-green-600' : 'text-blue-600';

                            html += `
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3">${item.date || ''}</td>
                                <td class="px-4 py-3"><span class="${typeClass} font-medium">${item.type || ''}</span></td>
                                <td class="px-4 py-3 font-mono">SP${String(item.product_id || 0).padStart(4, '0')}</td>
                                <td class="px-4 py-3">${item.product_name || ''}</td>
                                <td class="px-4 py-3 text-right">${quantity.toLocaleString('vi-VN')}</td>
                                <td class="px-4 py-3 text-right">${price.toLocaleString('vi-VN')}đ</td>
                                <td class="px-4 py-3 text-right">${total.toLocaleString('vi-VN')}đ</td>
                            </tr>
                        `;

                            if (item.type === 'Nhập') {
                                totalImport += total;
                                totalImportQty += quantity;
                            } else {
                                totalExport += total;
                                totalExportQty += quantity;
                            }
                        });

                        const chenhLech = totalImport - totalExport;
                        const chenhLechQty = totalImportQty - totalExportQty;

                        html += `
                        <tr class="bg-gray-100 font-bold">
                            <td colspan="4" class="px-4 py-3 text-right">TỔNG CỘNG:</td>
                            <td class="px-4 py-3 text-right text-red-600">${totalImportQty.toLocaleString('vi-VN')} / ${totalExportQty.toLocaleString('vi-VN')}</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right text-indigo-700">${totalImport.toLocaleString('vi-VN')}đ / ${totalExport.toLocaleString('vi-VN')}đ</td>
                        </tr>
                        <tr class="bg-gray-50 font-semibold">
                            <td colspan="6" class="px-4 py-3 text-right">CHÊNH LỆCH (Nhập - Xuất):</td>
                            <td class="px-4 py-3 text-right ${chenhLech >= 0 ? 'text-green-600' : 'text-red-600'}">
                                ${chenhLech.toLocaleString('vi-VN')}đ (SL: ${chenhLechQty.toLocaleString('vi-VN')})
                            </td>
                        </tr>
                    `;
                        tbody.innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-red-500"><i class="fas fa-exclamation-triangle text-2xl mb-2 block"></i>Có lỗi xảy ra khi tải dữ liệu!</td></tr>';
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
        </script>
</body>

</html>