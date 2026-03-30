<?php
session_start();
require_once __DIR__ . '/../control/connect.php';
require_once __DIR__ . '/../control/function.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

// Lấy thông tin admin
$admin_name = $_SESSION['admin_name'] ?? '';
$admin_role = $_SESSION['admin_role'] ?? '';
$admin_username = $_SESSION['admin_username'] ?? '';

// ============================================
// LẤY DỮ LIỆU THỐNG KÊ THỰC TẾ
// ============================================

// 1. Tổng số người dùng (khách hàng)
$sql_total_users = "SELECT COUNT(*) as total FROM users WHERE role = 0";
$result_users = $conn->query($sql_total_users);
$total_users = $result_users->fetch_assoc()['total'] ?? 0;

// 2. Tổng số người dùng tháng trước
$first_day_last_month = date('Y-m-d', strtotime('first day of last month'));
$last_day_last_month = date('Y-m-d', strtotime('last day of last month'));

$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'created_at'");
$has_created_date = $check_column->num_rows > 0;

if ($has_created_date) {
    $sql_users_last_month = "SELECT COUNT(*) as total FROM users WHERE role = 0 AND DATE(created_at) BETWEEN '$first_day_last_month' AND '$last_day_last_month'";
    $result_users_last_month = $conn->query($sql_users_last_month);
    $users_last_month = $result_users_last_month->fetch_assoc()['total'] ?? 0;
} else {
    $users_last_month = 0;
}

$user_change = 0;
if ($users_last_month > 0) {
    $user_change = (($total_users - $users_last_month) / $users_last_month) * 100;
}

// 3. Tổng số sản phẩm
$sql_total_products = "SELECT COUNT(*) as total FROM sanpham";
$result_products = $conn->query($sql_total_products);
$total_products = $result_products->fetch_assoc()['total'] ?? 0;

// 4. Tổng số sản phẩm tháng trước
$sql_products_last_month = "SELECT COUNT(*) as total FROM sanpham WHERE DATE(TaoNgay) BETWEEN '$first_day_last_month' AND '$last_day_last_month'";
$result_products_last_month = $conn->query($sql_products_last_month);
$products_last_month = $result_products_last_month->fetch_assoc()['total'] ?? 0;

$product_change = 0;
if ($products_last_month > 0) {
    $product_change = (($total_products - $products_last_month) / $products_last_month) * 100;
}

// 5. Đơn hàng hôm nay
$today = date('Y-m-d');
$sql_orders_today = "SELECT COUNT(*) as total FROM donhang WHERE DATE(NgayDat) = '$today'";
$result_orders_today = $conn->query($sql_orders_today);
$orders_today = $result_orders_today->fetch_assoc()['total'] ?? 0;

// 6. Đơn hàng hôm qua
$yesterday = date('Y-m-d', strtotime('-1 day'));
$sql_orders_yesterday = "SELECT COUNT(*) as total FROM donhang WHERE DATE(NgayDat) = '$yesterday'";
$result_orders_yesterday = $conn->query($sql_orders_yesterday);
$orders_yesterday = $result_orders_yesterday->fetch_assoc()['total'] ?? 0;

$order_change = 0;
if ($orders_yesterday > 0) {
    $order_change = (($orders_today - $orders_yesterday) / $orders_yesterday) * 100;
} elseif ($orders_today > 0) {
    $order_change = 100;
}

// 7. Doanh thu tháng này
$first_day_this_month = date('Y-m-01');
$last_day_this_month = date('Y-m-t');
$sql_revenue_this_month = "SELECT SUM(ctdh.SoLuong * ctdh.Gia) as total 
                            FROM donhang d 
                            JOIN chitiethoadon ctdh ON d.DonHang_id = ctdh.DonHang_id 
                            WHERE d.TrangThai IN (1, 2) 
                            AND DATE(d.NgayDat) BETWEEN '$first_day_this_month' AND '$last_day_this_month'";
$result_revenue = $conn->query($sql_revenue_this_month);
$row = $result_revenue->fetch_assoc();
$revenue_this_month = $row['total'] ?? 0;

// 8. Doanh thu tháng trước
$first_day_last_month_full = date('Y-m-01', strtotime('first day of last month'));
$last_day_last_month_full = date('Y-m-t', strtotime('last day of last month'));
$sql_revenue_last_month = "SELECT SUM(ctdh.SoLuong * ctdh.Gia) as total 
                           FROM donhang d 
                           JOIN chitiethoadon ctdh ON d.DonHang_id = ctdh.DonHang_id 
                           WHERE d.TrangThai IN (1, 2) 
                           AND DATE(d.NgayDat) BETWEEN '$first_day_last_month_full' AND '$last_day_last_month_full'";
$result_revenue_last = $conn->query($sql_revenue_last_month);
$row_last = $result_revenue_last->fetch_assoc();
$revenue_last_month = $row_last['total'] ?? 0;

$revenue_change = 0;
if ($revenue_last_month > 0) {
    $revenue_change = (($revenue_this_month - $revenue_last_month) / $revenue_last_month) * 100;
}

// 9. Tổng số đơn hàng đang chờ xử lý
$sql_pending_orders = "SELECT COUNT(*) as total FROM donhang WHERE TrangThai = 0";
$result_pending = $conn->query($sql_pending_orders);
$pending_orders = $result_pending->fetch_assoc()['total'] ?? 0;

// ============================================
// PHÂN LOẠI CẢNH BÁO TỒN KHO
// ============================================

// 10. Sản phẩm HẾT HÀNG (tồn kho = 0) - TẤT CẢ SẢN PHẨM
$sql_out_of_stock = "SELECT COUNT(*) as total FROM sanpham WHERE SoLuongTon = 0";
$result_out_of_stock = $conn->query($sql_out_of_stock);
$out_of_stock_products = $result_out_of_stock->fetch_assoc()['total'] ?? 0;

// 11. Sản phẩm SẮP HẾT (tồn kho > 0 và <= ngưỡng cảnh báo) - TẤT CẢ SẢN PHẨM
$sql_low_stock = "SELECT COUNT(*) as total FROM sanpham WHERE SoLuongTon > 0 AND SoLuongTon <= COALESCE(CanhBaoTon, 10)";
$result_low_stock = $conn->query($sql_low_stock);
$low_stock_products = $result_low_stock->fetch_assoc()['total'] ?? 0;

// 12. Sản phẩm ĐANG BÁN (TrangThai = 1)
$sql_active_products = "SELECT COUNT(*) as total FROM sanpham WHERE TrangThai = 1";
$result_active_products = $conn->query($sql_active_products);
$active_products = $result_active_products->fetch_assoc()['total'] ?? 0;

// 13. Sản phẩm ĐANG BÁN và còn hàng tốt
$sql_good_stock = "SELECT COUNT(*) as total FROM sanpham WHERE TrangThai = 1 AND SoLuongTon > COALESCE(CanhBaoTon, 10)";
$result_good_stock = $conn->query($sql_good_stock);
$good_stock_products = $result_good_stock->fetch_assoc()['total'] ?? 0;

// 14. Sản phẩm ĐANG BÁN và sắp hết
$sql_active_low_stock = "SELECT COUNT(*) as total FROM sanpham WHERE TrangThai = 1 AND SoLuongTon > 0 AND SoLuongTon <= COALESCE(CanhBaoTon, 10)";
$result_active_low_stock = $conn->query($sql_active_low_stock);
$active_low_stock = $result_active_low_stock->fetch_assoc()['total'] ?? 0;

// 15. Sản phẩm ĐANG BÁN và hết hàng
$sql_active_out_of_stock = "SELECT COUNT(*) as total FROM sanpham WHERE TrangThai = 1 AND SoLuongTon = 0";
$result_active_out_of_stock = $conn->query($sql_active_out_of_stock);
$active_out_of_stock = $result_active_out_of_stock->fetch_assoc()['total'] ?? 0;

// 16. Sản phẩm NGỪNG BÁN (TrangThai = 0)
$sql_inactive_products = "SELECT COUNT(*) as total FROM sanpham WHERE TrangThai = 0";
$result_inactive_products = $conn->query($sql_inactive_products);
$inactive_products = $result_inactive_products->fetch_assoc()['total'] ?? 0;

// 17. 5 đơn hàng gần nhất
$sql_recent_orders = "SELECT d.DonHang_id, d.NgayDat, d.TongTien, d.TrangThai, 
                      COALESCE(u.Ho_ten, 'Khách lẻ') as customer_name
                      FROM donhang d
                      LEFT JOIN users u ON d.User_id = u.User_id
                      ORDER BY d.NgayDat DESC LIMIT 5";
$recent_orders = $conn->query($sql_recent_orders)->fetch_all(MYSQLI_ASSOC);

// 18. 5 sản phẩm bán chạy nhất tháng này
$sql_top_products = "SELECT sp.SanPham_id, sp.TenSP, SUM(ctdh.SoLuong) as total_sold
                     FROM chitiethoadon ctdh
                     JOIN sanpham sp ON ctdh.SanPham_id = sp.SanPham_id
                     JOIN donhang d ON ctdh.DonHang_id = d.DonHang_id
                     WHERE d.TrangThai IN (1, 2) 
                     AND DATE(d.NgayDat) BETWEEN '$first_day_this_month' AND '$last_day_this_month'
                     GROUP BY sp.SanPham_id, sp.TenSP
                     ORDER BY total_sold DESC LIMIT 5";
$top_products = $conn->query($sql_top_products)->fetch_all(MYSQLI_ASSOC);

// Định dạng số tiền
function formatMoney($amount) {
    if ($amount >= 1000000000) {
        return round($amount / 1000000000, 1) . 'B';
    } elseif ($amount >= 1000000) {
        return round($amount / 1000000, 1) . 'M';
    } elseif ($amount >= 1000) {
        return round($amount / 1000, 1) . 'K';
    }
    return number_format($amount, 0, ',', '.');
}

$revenue_formatted = formatMoney($revenue_this_month);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - Admin</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn {
            animation: fadeIn 0.3s ease-out;
        }
        
        .stat-icon-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
        }
        
        .change-positive {
            color: #10b981;
        }
        .change-negative {
            color: #ef4444;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #d1fae5; color: #065f46; }
        .status-delivered { background: #dbeafe; color: #1e40af; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        
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
                    <div class="w-10 h-10 rounded-full bg-gradient-custom flex items-center justify-center text-white font-bold">
                        <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($admin_username); ?></p>
                    </div>
                </div>
                <button onclick="logout()" class="bg-gradient-custom text-white font-semibold px-4 py-2 rounded-lg hover:opacity-90 transition duration-200 shadow-md hover:shadow-lg">
                    <i class="fas fa-sign-out-alt mr-2"></i>Đăng xuất
                </button>
            </div>
        </div>
    </header>

    <div class="flex w-full min-h-[calc(100vh-70px)]">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>Danh mục chức năng</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="menu-btn active">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="users.php" class="menu-btn">
                    <i class="fas fa-users"></i> Quản lý người dùng
                </a>
                <a href="product.php" class="menu-btn">
                    <i class="fas fa-box"></i> Quản lý sản phẩm
                </a>
                <a href="import.php" class="menu-btn">
                    <i class="fas fa-arrow-down"></i> Quản lý nhập hàng
                </a>
                <a href="price.php" class="menu-btn">
                    <i class="fas fa-tag"></i> Quản lý giá bán
                </a>
                <a href="orders.php" class="menu-btn">
                    <i class="fas fa-receipt"></i> Quản lý đơn hàng
                </a>
                <a href="inventory.php" class="menu-btn">
                    <i class="fas fa-warehouse"></i> Tồn kho & Báo cáo
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6 lg:p-8 overflow-x-hidden bg-gray-50">
            <div class="bg-white rounded-xl shadow-lg p-6 lg:p-8 min-h-full animate-fadeIn">
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Xin chào, <?php echo htmlspecialchars($admin_username); ?>!</h2>
                    <p class="text-gray-600">Chào mừng bạn quay trở lại hệ thống quản lý NVBPlay. Hôm nay là ngày <?php echo date('d/m/Y'); ?>.</p>
                    
                    <!-- Alert nếu có đơn hàng chờ xử lý -->
                    <?php if ($pending_orders > 0): ?>
                    <div class="mt-4 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-yellow-600 mr-3 text-xl"></i>
                            <div class="flex-1">
                                <p class="text-yellow-700 font-medium">⚠️ Có <?php echo $pending_orders; ?> đơn hàng đang chờ xử lý!</p>
                                <p class="text-yellow-600 text-sm">Vui lòng kiểm tra và xác nhận đơn hàng.</p>
                            </div>
                            <a href="orders.php" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition text-sm whitespace-nowrap">
                                Xem ngay
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Alert nếu có sản phẩm ĐANG BÁN và HẾT HÀNG -->
                    <?php if ($active_out_of_stock > 0): ?>
                    <div class="mt-4 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-ban text-red-600 mr-3 text-xl"></i>
                            <div class="flex-1">
                                <p class="text-red-700 font-medium">⚠️ Có <?php echo $active_out_of_stock; ?> sản phẩm ĐANG BÁN đã HẾT HÀNG!</p>
                                <p class="text-red-600 text-sm">Cần nhập hàng ngay để không bỏ lỡ đơn hàng.</p>
                            </div>
                            <a href="inventory.php#warning" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition text-sm whitespace-nowrap">
                                Kiểm tra ngay
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Alert nếu có sản phẩm ĐANG BÁN và SẮP HẾT HÀNG -->
                    <?php if ($active_low_stock > 0): ?>
                    <div class="mt-4 bg-orange-50 border-l-4 border-orange-500 p-4 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-orange-600 mr-3 text-xl"></i>
                            <div class="flex-1">
                                <p class="text-orange-700 font-medium">⚠️ Có <?php echo $active_low_stock; ?> sản phẩm ĐANG BÁN sắp hết hàng!</p>
                                <p class="text-orange-600 text-sm">Chuẩn bị nhập hàng bổ sung.</p>
                            </div>
                            <a href="inventory.php#warning" class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition text-sm whitespace-nowrap">
                                Kiểm tra
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Card 1: Tổng người dùng -->
                    <div class="bg-gray-50 rounded-xl p-6 stat-card transition">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-sm text-gray-500 font-medium">Tổng người dùng</span>
                            <div class="w-12 h-12 rounded-full stat-icon-bg flex items-center justify-center text-white">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-800 mb-2"><?php echo number_format($total_users); ?></div>
                        <div class="text-sm flex items-center <?php echo $user_change >= 0 ? 'change-positive' : 'change-negative'; ?>">
                            <i class="fas fa-arrow-<?php echo $user_change >= 0 ? 'up' : 'down'; ?> mr-1"></i>
                            <span><?php echo abs(round($user_change, 1)); ?>% so với tháng trước</span>
                        </div>
                    </div>
                    
                    <!-- Card 2: Tổng sản phẩm -->
                    <div class="bg-gray-50 rounded-xl p-6 stat-card transition">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-sm text-gray-500 font-medium">Tổng sản phẩm</span>
                            <div class="w-12 h-12 rounded-full stat-icon-bg flex items-center justify-center text-white">
                                <i class="fas fa-box text-xl"></i>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-800 mb-2"><?php echo number_format($total_products); ?></div>
                        <div class="text-sm flex items-center <?php echo $product_change >= 0 ? 'change-positive' : 'change-negative'; ?>">
                            <i class="fas fa-arrow-<?php echo $product_change >= 0 ? 'up' : 'down'; ?> mr-1"></i>
                            <span><?php echo abs(round($product_change, 1)); ?>% so với tháng trước</span>
                        </div>
                    </div>
                    
                    <!-- Card 3: Đơn hàng hôm nay -->
                    <div class="bg-gray-50 rounded-xl p-6 stat-card transition">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-sm text-gray-500 font-medium">Đơn hàng hôm nay</span>
                            <div class="w-12 h-12 rounded-full stat-icon-bg flex items-center justify-center text-white">
                                <i class="fas fa-shopping-cart text-xl"></i>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-800 mb-2"><?php echo number_format($orders_today); ?></div>
                        <div class="text-sm flex items-center <?php echo $order_change >= 0 ? 'change-positive' : 'change-negative'; ?>">
                            <i class="fas fa-arrow-<?php echo $order_change >= 0 ? 'up' : 'down'; ?> mr-1"></i>
                            <span><?php echo abs(round($order_change, 1)); ?>% so với hôm qua</span>
                        </div>
                    </div>
                    
                    <!-- Card 4: Doanh thu tháng -->
                    <div class="bg-gray-50 rounded-xl p-6 stat-card transition">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-sm text-gray-500 font-medium">Doanh thu tháng <?php echo date('m/Y'); ?></span>
                            <div class="w-12 h-12 rounded-full stat-icon-bg flex items-center justify-center text-white">
                                <i class="fas fa-chart-line text-xl"></i>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-800 mb-2"><?php echo $revenue_formatted; ?>đ</div>
                        <div class="text-sm flex items-center <?php echo $revenue_change >= 0 ? 'change-positive' : 'change-negative'; ?>">
                            <i class="fas fa-arrow-<?php echo $revenue_change >= 0 ? 'up' : 'down'; ?> mr-1"></i>
                            <span><?php echo abs(round($revenue_change, 1)); ?>% so với tháng trước</span>
                        </div>
                    </div>
                </div>
                
                <!-- Thống kê nhanh tồn kho -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-xl p-4 border border-green-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm text-green-600 font-medium">Đang bán - Còn hàng</p>
                                <p class="text-2xl font-bold text-green-700"><?php echo number_format($good_stock_products); ?></p>
                                <p class="text-xs text-gray-500 mt-1">Tồn > ngưỡng</p>
                            </div>
                            <i class="fas fa-check-circle text-green-500 text-3xl"></i>
                        </div>
                    </div>
                    <div class="bg-gradient-to-r from-orange-50 to-orange-100 rounded-xl p-4 border border-orange-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm text-orange-600 font-medium">Sắp hết hàng</p>
                                <p class="text-2xl font-bold text-orange-700"><?php echo number_format($active_low_stock); ?></p>
                                <p class="text-xs text-gray-500 mt-1">Đang bán, tồn ≤ ngưỡng</p>
                            </div>
                            <i class="fas fa-exclamation-triangle text-orange-500 text-3xl"></i>
                        </div>
                    </div>
                    <div class="bg-gradient-to-r from-red-50 to-red-100 rounded-xl p-4 border border-red-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm text-red-600 font-medium">Đã hết hàng</p>
                                <p class="text-2xl font-bold text-red-700"><?php echo number_format($active_out_of_stock); ?></p>
                                <p class="text-xs text-gray-500 mt-1">Đang bán, tồn kho = 0</p>
                            </div>
                            <i class="fas fa-times-circle text-red-500 text-3xl"></i>
                        </div>
                    </div>
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 border border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm text-gray-600 font-medium">Ngừng bán</p>
                                <p class="text-2xl font-bold text-gray-700"><?php echo number_format($inactive_products); ?></p>
                                <p class="text-xs text-gray-500 mt-1">Trạng thái = 0</p>
                            </div>
                            <i class="fas fa-ban text-gray-500 text-3xl"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Charts và Recent Orders Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Biểu đồ doanh thu 7 ngày gần nhất -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-chart-line text-primary mr-2"></i>
                            Doanh thu 7 ngày qua
                        </h3>
                        <canvas id="revenueChart" height="250"></canvas>
                    </div>
                    
                    <!-- Top sản phẩm bán chạy -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-trophy text-primary mr-2"></i>
                            Top sản phẩm bán chạy tháng này
                        </h3>
                        <div class="space-y-3">
                            <?php if (count($top_products) > 0): ?>
                                <?php foreach ($top_products as $index => $product): ?>
                                <div class="flex items-center justify-between p-3 bg-white rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full <?php echo $index == 0 ? 'bg-yellow-100 text-yellow-600' : ($index == 1 ? 'bg-gray-100 text-gray-600' : ($index == 2 ? 'bg-orange-100 text-orange-600' : 'bg-blue-100 text-blue-600')); ?> flex items-center justify-center font-bold">
                                            <?php echo $index + 1; ?>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($product['TenSP']); ?></p>
                                            <p class="text-xs text-gray-500">Mã: SP<?php echo str_pad($product['SanPham_id'], 4, '0', STR_PAD_LEFT); ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-primary"><?php echo number_format($product['total_sold']); ?></p>
                                        <p class="text-xs text-gray-500">sản phẩm</p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-center text-gray-500 py-8">Chưa có dữ liệu bán hàng trong tháng này</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Đơn hàng gần đây -->
                <div class="bg-gray-50 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-clock text-primary mr-2"></i>
                        Đơn hàng gần đây
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-2">Mã ĐH</th>
                                    <th class="text-left py-3 px-2">Khách hàng</th>
                                    <th class="text-left py-3 px-2">Ngày đặt</th>
                                    <th class="text-right py-3 px-2">Tổng tiền</th>
                                    <th class="text-center py-3 px-2">Trạng thái</th>
                                    <th class="text-center py-3 px-2">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($recent_orders) > 0): ?>
                                    <?php foreach ($recent_orders as $order): ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                                        <td class="py-3 px-2 font-mono">#<?php echo $order['DonHang_id']; ?></td>
                                        <td class="py-3 px-2"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td class="py-3 px-2"><?php echo date('d/m/Y H:i', strtotime($order['NgayDat'])); ?></td>
                                        <td class="py-3 px-2 text-right font-medium"><?php echo number_format($order['TongTien'], 0, ',', '.'); ?>đ</td>
                                        <td class="py-3 px-2 text-center">
                                            <?php
                                            $statusClass = '';
                                            $statusText = '';
                                            switch ($order['TrangThai']) {
                                                case 0:
                                                    $statusClass = 'status-pending';
                                                    $statusText = 'Chờ xác nhận';
                                                    break;
                                                case 1:
                                                    $statusClass = 'status-confirmed';
                                                    $statusText = 'Đã xác nhận';
                                                    break;
                                                case 2:
                                                    $statusClass = 'status-delivered';
                                                    $statusText = 'Đã giao';
                                                    break;
                                                case 3:
                                                    $statusClass = 'status-cancelled';
                                                    $statusText = 'Đã hủy';
                                                    break;
                                                default:
                                                    $statusClass = 'status-pending';
                                                    $statusText = 'Chờ xác nhận';
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                        </td>
                                        <td class="py-3 px-2 text-center">
                                            <a href="orders.php?view=<?php echo $order['DonHang_id']; ?>" class="text-primary hover:text-secondary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-8 text-gray-500">
                                            <i class="fas fa-inbox text-4xl mb-2 block"></i>
                                            Chưa có đơn hàng nào
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (count($recent_orders) > 0): ?>
                    <div class="mt-4 text-right">
                        <a href="orders.php" class="text-primary hover:text-secondary text-sm">
                            Xem tất cả đơn hàng <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Biểu đồ doanh thu 7 ngày qua
        <?php
        $revenue_data = [];
        $date_labels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $date_labels[] = date('d/m', strtotime($date));
            
            $sql_daily_revenue = "SELECT SUM(ctdh.SoLuong * ctdh.Gia) as total 
                                  FROM donhang d 
                                  JOIN chitiethoadon ctdh ON d.DonHang_id = ctdh.DonHang_id 
                                  WHERE d.TrangThai IN (1, 2) 
                                  AND DATE(d.NgayDat) = '$date'";
            $result_daily = $conn->query($sql_daily_revenue);
            $daily_total = $result_daily->fetch_assoc()['total'] ?? 0;
            $revenue_data[] = $daily_total;
        }
        ?>
        
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($date_labels); ?>,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: <?php echo json_encode($revenue_data); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#764ba2',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let value = context.raw;
                                return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(value) + 'đ';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) {
                                    return (value / 1000000) + 'M';
                                } else if (value >= 1000) {
                                    return (value / 1000) + 'K';
                                }
                                return value;
                            }
                        }
                    }
                }
            }
        });
        
        function logout() {
            if (confirm('Bạn có chắc muốn đăng xuất?')) {
                window.location.href = 'logout.php';
            }
        }
    </script>
</body>
</html>