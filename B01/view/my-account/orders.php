<?php
/**
 * Order Management Page - NVBPlay
 * Hiển thị danh sách đơn hàng của user
 */
session_start();
require_once '../../control/connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// === 1. KIỂM TRA ĐĂNG NHẬP ===
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 0) != 0) {
    header("Location: ../../login.php?redirect=orders");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// === 2. LẤY THÔNG TIN USER ===
$user_info = null;
$stmt = $conn->prepare("SELECT User_id, username, ho_ten, email, SDT FROM users WHERE User_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

// === 3. XỬ LÝ FILTER & SEARCH ===
$status_filter = $_GET['status'] ?? 'all';
$search_query = trim($_GET['search'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// === 4. XÂY DỰNG QUERY ===
$where_clauses = ["d.User_id = ?"];
$params = [$user_id];
$types = "i";

// Filter by status
$status_map = [
    'pending-payment' => 'Chờ thanh toán',
    'processing' => 'Chờ xác nhận',
    'shipping' => 'Đang giao',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy'
];

if ($status_filter !== 'all' && isset($status_map[$status_filter])) {
    $where_clauses[] = "d.TrangThai = ?";
    $params[] = $status_map[$status_filter];
    $types .= "s";
}

// Search by order ID or product name
if (!empty($search_query)) {
    $where_clauses[] = "(d.DonHang_id LIKE ? OR sp.TenSP LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$where_sql = implode(" AND ", $where_clauses);

// === 5. ĐẾM TỔNG SỐ ĐƠN HÀNG ===
$count_stmt = $conn->prepare("
    SELECT COUNT(DISTINCT d.DonHang_id) as total 
    FROM donhang d 
    LEFT JOIN chitiethoadon ctdh ON d.DonHang_id = ctdh.DonHang_id 
    LEFT JOIN sanpham sp ON ctdh.SanPham_id = sp.SanPham_id 
    WHERE $where_sql
");
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_orders = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

$total_pages = ceil($total_orders / $per_page);

// === 6. LẤY DANH SÁCH ĐƠN HÀNG ===
$orders_stmt = $conn->prepare("
    SELECT d.*, 
    dh.Ten_nguoi_nhan, dh.SDT_nhan, dh.Duong, dh.Quan, dh.Tinh_thanhpho, dh.Dia_chi_chitiet,
    GROUP_CONCAT(sp.TenSP SEPARATOR '||') as product_names,
    GROUP_CONCAT(ctdh.SoLuong SEPARATOR '||') as product_qty,
    GROUP_CONCAT(ctdh.Gia SEPARATOR '||') as product_price,
    GROUP_CONCAT(sp.image_url SEPARATOR '||') as product_images
    FROM donhang d 
    LEFT JOIN diachigh dh ON d.DiaChi_id = dh.add_id 
    LEFT JOIN chitiethoadon ctdh ON d.DonHang_id = ctdh.DonHang_id 
    LEFT JOIN sanpham sp ON ctdh.SanPham_id = sp.SanPham_id 
    WHERE $where_sql 
    GROUP BY d.DonHang_id 
    ORDER BY d.NgayDat DESC 
    LIMIT ? OFFSET ?
");

$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

$orders_stmt->bind_param($types, ...$params);
$orders_stmt->execute();
$orders = $orders_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$orders_stmt->close();

// === 7. HÀM HỖ TRỢ ===
function formatPrice($price)
{
    return number_format((float) $price, 0, ',', '.') . '₫';
}

function formatDate($date)
{
    return date('d/m/Y', strtotime($date));
}

function getStatusBadge($status)
{
    $colors = [
        'Chờ xác nhận' => 'bg-yellow-100 text-yellow-800',
        'Đã xác nhận' => 'bg-blue-100 text-blue-800',
        'Đang giao' => 'bg-purple-100 text-purple-800',
        'Hoàn thành' => 'bg-green-100 text-green-800',
        'Đã hủy' => 'bg-red-100 text-red-800',
        'Chờ thanh toán' => 'bg-orange-100 text-orange-800'
    ];
    $color = $colors[$status] ?? 'bg-gray-100 text-gray-800';
    return "<span class='px-3 py-1 rounded-full text-sm font-medium $color'>$status</span>";
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
    <title>Quản Lý Đơn Hàng | NVBPlay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/svg+xml" href="../../img/icons/favicon.png">
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }

        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .tab-btn.active {
            color: #FF3F1A;
            border-bottom-color: #FF3F1A;
        }

        .order-details {
            display: none;
        }

        .order-details.expanded {
            display: block;
        }

        /* === USER DROPDOWN STYLES === */
        .user-dropdown {
            position: relative;
        }

        .user-menu {
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 8px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            border: 1px solid #f3f4f6;
            min-width: 220px;
            z-index: 50;
            display: none;
            animation: slideDown 0.2s ease;
        }

        .user-menu.active {
            display: block;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .user-menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            color: #374151;
            text-decoration: none;
            transition: background 0.2s;
            font-size: 14px;
        }

        .user-menu-item:hover {
            background: #f9fafb;
        }

        .user-menu-item i {
            width: 18px;
            color: #6b7280;
        }

        .user-menu-divider {
            border-top: 1px solid #f3f4f6;
            margin: 4px 0;
        }

        .user-menu-item.logout {
            color: #dc2626;
        }

        .user-menu-item.logout i {
            color: #dc2626;
        }
    </style>
</head>

<body class="font-sans antialiased bg-gray-50">
    <!-- Main Wrapper -->
    <div id="wrapper" class="min-h-screen flex flex-col">
        <!-- Header -->
        <header id="header" class="sticky top-0 z-40 bg-white shadow-sm">
            <div class="header-wrapper">
                <div id="masthead" class="py-2 md:py-3 border-b">
                    <div class="container mx-auto px-4 flex items-center justify-between">
                        <!-- Mobile Menu Toggle -->
                        <div class="md:hidden">
                            <button class="menu-toggle p-2">
                                <img src="../../img/icons/menu.svg" class="fas fa-bars text-2xl">
                            </button>
                        </div>

                        <!-- Desktop Left Menu -->
                        <div class="hidden md:flex items-center flex-1 ml-6">
                            <ul class="flex items-center space-x-4">
                                <li class="relative" id="mega-menu-container">
                                    <button id="mega-menu-trigger"
                                        class="button-menu flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200 transition">
                                        <img src="../../img/icons/menu.svg" class="w-5 h-5 mr-2" alt="menu">
                                        <span>Danh mục</span>
                                    </button>
                                    <div id="mega-menu-dropdown"
                                        class="absolute left-0 top-full mt-2 w-[900px] bg-white rounded-lg shadow-xl hidden z-50">
                                        <!-- Mega menu content -->
                                    </div>
                                </li>
                                <li>
                                    <a href="../shop.php"
                                        class="flex items-center text-gray-700 hover:text-red-600 font-medium">
                                        <img src="../../img/icons/store.svg" class="w-5 h-5 flex-shrink-0 mr-2">
                                        <span>CỬA HÀNG</span>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <!-- Logo -->
                        <div id="logo" class="flex-shrink-1 absolute left-1/2 transform -translate-x-1/2">
                            <a href="../../index.php" title="NVBPlay" rel="home">
                                <img width="100" height="40" src="../../img/icons/logonvb.png" alt="NVBPlay"
                                    class="h-12 md:h-14 w-auto transform scale-75">
                            </a>
                        </div>

                        <!-- Desktop Right Elements -->
                        <div class="hidden md:flex items-center space-x-4">
                            <!-- Address Book -->
                            <div class="address-book">
                                <a href="./address-book.php" class="flex items-center text-gray-700 hover:text-red-600">
                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                    <span class="shipping-address text-sm"><span class="text">Chọn địa chỉ</span></span>
                                </a>
                            </div>
                            <div class="h-5 w-px bg-gray-300"></div>

                            <!-- Search -->
                            <div class="search-header relative">
                                <button class="search-toggle p-2">
                                    <i class="fas fa-search text-gray-700 hover:text-red-600"></i>
                                </button>
                            </div>

                            <!-- ✅ USER DROPDOWN (ĐÃ SỬA) -->
                            <div class="user-dropdown">
                                <button id="userToggle"
                                    class="flex items-center space-x-2 hover:bg-gray-100 px-3 py-2 rounded-lg transition">
                                    <img src="../../img/icons/account.svg" class="w-6 h-6" alt="Account">
                                    <span class="text-sm font-medium text-gray-700">
                                        <?php echo htmlspecialchars($user_info['username']); ?>
                                    </span>
                                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                </button>

                                <!-- User Menu Dropdown -->
                                <div id="userMenu" class="user-menu">
                                    <div class="px-4 py-3 border-b border-gray-100">
                                        <div class="flex items-center space-x-3">
                                            <img src="../../img/icons/account.svg" class="w-10 h-10" alt="Account">
                                            <div>
                                                <p class="text-sm font-medium text-gray-800">
                                                    <?php echo htmlspecialchars($user_info['ho_ten']); ?>
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    <?php echo htmlspecialchars($user_info['email']); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <a href="./my-account.php" class="user-menu-item">
                                        <i class="fas fa-user"></i>
                                        <span>Tài khoản của tôi</span>
                                    </a>
                                    <a href="./orders.php" class="user-menu-item">
                                        <i class="fas fa-shopping-bag"></i>
                                        <span>Đơn hàng</span>
                                    </a>
                                    <a href="./address-book.php" class="user-menu-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span>Sổ địa chỉ</span>
                                    </a>
                                    <div class="user-menu-divider"></div>
                                    <a href="../../control/logout.php" class="user-menu-item logout">
                                        <i class="fas fa-sign-out-alt"></i>
                                        <span>Đăng xuất</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Cart -->
                            <a href="../cart.php" class="relative p-2">
                                <i class="fas fa-shopping-basket text-gray-700 hover:text-red-600 text-xl"></i>
                                <span
                                    class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">0</span>
                            </a>
                        </div>

                        <!-- Mobile Right Elements -->
                        <div class="md:hidden flex items-center space-x-3">
                            <button class="search-toggle p-1">
                                <i class="fas fa-search text-xl"></i>
                            </button>
                            <a href="../my-account.php" class="p-1">
                                <img src="../../img/icons/account.svg" class="w-6 h-6" alt="Account">
                            </a>
                            <a href="../cart.php" class="relative p-1">
                                <i class="fas fa-shopping-basket text-xl"></i>
                                <span
                                    class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">0</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="flex-1">
            <!-- Mobile Account Header -->
            <div class="lg:hidden bg-white border-b border-gray-200">
                <div class="container mx-auto px-4 py-3">
                    <div class="flex items-center justify-between">
                        <h1 class="text-lg font-semibold">Quản lý đơn hàng</h1>
                        <button class="show-menu p-2" onclick="toggleMobileAccountMenu()">
                            <img src="../../img/icons/3dot.svg" alt="Menu" class="w-6 h-6">
                        </button>
                    </div>
                </div>
            </div>

            <div class="container mx-auto px-4 py-4 md:py-8 m-[50px]">
                <div class="flex flex-col lg:flex-row gap-4 md:gap-8">
                    <!-- Sidebar -->
                    <div class="lg:w-1/4">
                        <!-- User Info Card -->
                        <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 mb-4 md:mb-6">
                            <div class="flex items-center space-x-3 md:space-x-4">
                                <img src="../../img/icons/account.svg" alt="User avatar"
                                    class="w-14 h-14 md:w-16 md:h-16 rounded-full border-2 border-gray-200">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900">
                                        <?php echo htmlspecialchars($user_info['ho_ten'] ?? 'Khách hàng'); ?>
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($user_info['email'] ?? ''); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Menu -->
                        <nav class="bg-white rounded-lg shadow-sm overflow-hidden hidden lg:block">
                            <ul class="divide-y divide-gray-200">
                                <li>
                                    <a href="../my-account.php"
                                        class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-[#FF3F1A] transition">
                                        <img src="../../img/icons/account.svg" class="w-5 h-5 mr-3" alt="Account">
                                        <span class="text-sm md:text-base">Thông tin tài khoản</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="./orders.php"
                                        class="flex items-center px-4 py-3 bg-red-50 text-[#FF3F1A] font-medium border-l-4 border-[#FF3F1A]">
                                        <img src="../../img/icons/clipboard.svg" class="w-5 h-5 mr-3" alt="Orders">
                                        <span class="text-sm md:text-base">Quản lý đơn hàng</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="./address-book.php"
                                        class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-[#FF3F1A] transition">
                                        <img src="../../img/icons/diachi.svg" class="w-5 h-5 mr-3" alt="Address">
                                        <span class="text-sm md:text-base">Sổ địa chỉ</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="../../control/logout.php"
                                        class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-[#FF3F1A] transition">
                                        <img src="../../img/icons/logout.svg" class="w-5 h-5 mr-3" alt="Logout">
                                        <span class="text-sm md:text-base">Đăng xuất</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>

                    <!-- Main Content Area -->
                    <div class="lg:w-3/4">
                        <!-- Order Management Header -->
                        <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 lg:p-8 mb-6">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <h3 class="text-lg md:text-xl font-semibold text-gray-900">Đơn hàng của tôi</h3>
                                <!-- Search Orders -->
                                <form method="GET" class="flex flex-col sm:flex-row gap-3">
                                    <div class="relative flex-1">
                                        <input type="text" name="search"
                                            placeholder="Tìm kiếm đơn hàng (Mã đơn, tên sản phẩm...)"
                                            value="<?php echo htmlspecialchars($search_query); ?>"
                                            class="w-full px-4 py-2 md:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition pl-10 text-sm md:text-base">
                                        <i
                                            class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                    </div>
                                    <button type="submit"
                                        class="px-4 md:px-6 py-2 md:py-3 bg-[#FF3F1A] text-white rounded-lg hover:bg-red-700 transition whitespace-nowrap text-sm md:text-base">
                                        Tìm kiếm
                                    </button>
                                </form>
                            </div>

                            <!-- Order Status Tabs -->
                            <div class="flex overflow-x-auto scrollbar-hide mt-6 border-b border-gray-200"
                                id="order-tabs">
                                <a href="?status=all&search=<?php echo urlencode($search_query); ?>"
                                    class="tab-btn px-4 py-2 text-sm font-medium whitespace-nowrap transition <?php echo $status_filter === 'all' ? 'active' : 'text-gray-600 hover:text-[#FF3F1A]'; ?>">
                                    Tất cả (<?php echo $total_orders; ?>)
                                </a>
                                <?php foreach ($status_map as $key => $label): ?>
                                    <a href="?status=<?php echo $key; ?>&search=<?php echo urlencode($search_query); ?>"
                                        class="tab-btn px-4 py-2 text-sm font-medium whitespace-nowrap transition <?php echo $status_filter === $key ? 'active' : 'text-gray-600 hover:text-[#FF3F1A]'; ?>">
                                        <?php echo $label; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Orders List -->
                        <div id="orders-list" class="space-y-4">
                            <?php if (empty($orders)): ?>
                                <!-- Empty State -->
                                <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                                    <div
                                        class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-shopping-bag text-4xl text-gray-400"></i>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Chưa có đơn hàng nào</h3>
                                    <p class="text-gray-600 mb-6">Bạn chưa đặt đơn hàng nào. Hãy bắt đầu mua sắm ngay!</p>
                                    <a href="../../index.php"
                                        class="inline-block px-6 py-3 bg-[#FF3F1A] text-white rounded-lg hover:bg-red-700 transition">
                                        Tiếp tục mua sắm
                                    </a>
                                </div>
                            <?php else: ?>
                                <!-- Order Items -->
                                <?php foreach ($orders as $order): ?>
                                    <?php
                                    // Parse product data
                                    $product_names = explode('||', $order['product_names'] ?? '');
                                    $product_qty = explode('||', $order['product_qty'] ?? '');
                                    $product_price = explode('||', $order['product_price'] ?? '');
                                    $product_images = explode('||', $order['product_images'] ?? '');
                                    $products = [];
                                    for ($i = 0; $i < count($product_names); $i++) {
                                        if (!empty($product_names[$i])) {
                                            $products[] = [
                                                'name' => $product_names[$i],
                                                'qty' => $product_qty[$i] ?? 1,
                                                'price' => $product_price[$i] ?? 0,
                                                'image' => $product_images[$i] ?? ''
                                            ];
                                        }
                                    }
                                    ?>
                                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 hover:shadow-md transition">
                                        <!-- Order Header -->
                                        <div
                                            class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-gray-100">
                                            <div class="flex items-center gap-4">
                                                <div>
                                                    <span class="text-sm text-gray-500">Mã đơn hàng</span>
                                                    <p class="font-semibold text-gray-900">#<?php echo $order['DonHang_id']; ?>
                                                    </p>
                                                </div>
                                                <div class="hidden sm:block w-px h-8 bg-gray-200"></div>
                                                <div>
                                                    <span class="text-sm text-gray-500">Ngày đặt</span>
                                                    <p class="font-medium"><?php echo formatDate($order['NgayDat']); ?></p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <?php echo getStatusBadge($order['TrangThai']); ?>
                                                <button class="text-gray-400 hover:text-gray-600"
                                                    onclick="toggleOrderDetails(<?php echo $order['DonHang_id']; ?>)">
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Order Products -->
                                        <div class="py-4 space-y-3 order-details"
                                            id="order-details-<?php echo $order['DonHang_id']; ?>">
                                            <?php foreach ($products as $product): ?>
                                                <div class="flex items-start gap-4">
                                                    <img src="../../<?php echo htmlspecialchars($product['image']); ?>"
                                                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                        class="w-16 h-16 object-cover rounded-lg border border-gray-200">
                                                    <div class="flex-1">
                                                        <h4 class="font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($product['name']); ?>
                                                        </h4>
                                                        <div class="flex items-center justify-between mt-1">
                                                            <span class="text-sm text-gray-600">Số lượng:
                                                                <?php echo $product['qty']; ?></span>
                                                            <span
                                                                class="font-semibold text-[#FF3F1A]"><?php echo formatPrice($product['price'] * $product['qty']); ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <!-- Order Footer -->
                                        <div
                                            class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-gray-100">
                                            <div class="flex items-center gap-2">
                                                <span class="text-gray-600">Tổng tiền:</span>
                                                <span
                                                    class="text-xl font-bold text-[#FF3F1A]"><?php echo formatPrice($order['TongTien']); ?></span>
                                            </div>
                                            <div class="flex gap-2">
                                                <?php if ($order['linkTraCuu']): ?>
                                                    <a href="../track-order.php?code=<?php echo urlencode(str_replace('/view/track-order.php?code=', '', $order['linkTraCuu'])); ?>"
                                                        class="px-4 py-2 text-sm bg-[#FF3F1A] text-white rounded-lg hover:bg-red-700 transition">
                                                        Xem
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($order['TrangThai'] === 'Hoàn thành'): ?>
                                                    <button
                                                        class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                                        Mua lại
                                                    </button>
                                                    <button
                                                        class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                                        Đánh giá
                                                    </button>
                                                <?php elseif ($order['TrangThai'] === 'Chờ xác nhận'): ?>
                                                    <button
                                                        class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                                                        onclick="cancelOrder(<?php echo $order['DonHang_id']; ?>)">
                                                        Hủy đơn
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div id="orders-pagination" class="flex justify-center mt-8">
                                <nav class="flex items-center gap-2 flex-wrap justify-center">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search_query); ?>"
                                            class="w-10 h-10 flex items-center justify-center rounded-lg border border-gray-300 hover:bg-gray-50 transition">
                                            <i class="fas fa-chevron-left text-sm"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <?php if ($i == $page): ?>
                                            <span
                                                class="w-10 h-10 flex items-center justify-center rounded-lg bg-[#FF3F1A] text-white"><?php echo $i; ?></span>
                                        <?php elseif ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                            <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search_query); ?>"
                                                class="w-10 h-10 flex items-center justify-center rounded-lg border border-gray-300 hover:bg-gray-50 transition">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                            <span class="w-10 h-10 flex items-center justify-center">...</span>
                                        <?php endif; ?>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search_query); ?>"
                                            class="w-10 h-10 flex items-center justify-center rounded-lg border border-gray-300 hover:bg-gray-50 transition">
                                            <i class="fas fa-chevron-right text-sm"></i>
                                        </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>

        <!-- Mobile Account Menu Overlay -->
        <div id="mobile-account-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"
            onclick="toggleMobileAccountMenu()"></div>

        <!-- Mobile Account Slide Menu -->
        <div id="mobile-account-menu"
            class="fixed top-0 left-0 h-full w-80 bg-white z-50 transform -translate-x-full transition-transform duration-300 lg:hidden">
            <div class="p-4">
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                    <div class="flex items-center space-x-3">
                        <img src="../../img/icons/account.svg" alt="User avatar"
                            class="w-12 h-12 rounded-full border-2 border-gray-200">
                        <div>
                            <h3 class="font-semibold text-gray-900">
                                <?php echo htmlspecialchars($user_info['ho_ten']); ?>
                            </h3>
                            <p class="text-xs text-gray-500">
                                <?php echo htmlspecialchars($user_info['email']); ?>
                            </p>
                        </div>
                    </div>
                    <button onclick="toggleMobileAccountMenu()" class="p-2">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <nav>
                    <ul class="space-y-2">
                        <li>
                            <a href="../my-account.php"
                                class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">
                                <img src="../../img/icons/account.svg" class="w-5 h-5 mr-3" alt="Account">
                                <span>Thông tin tài khoản</span>
                            </a>
                        </li>
                        <li>
                            <a href="./orders.php"
                                class=" flex items-center px-4 py-3 bg-red-50 text-[#FF3F1A] font-medium rounded-lg">
                                <img src="../../img/icons/clipboard.svg" class="w-5 h-5 mr-3" alt="Orders">
                                <span>Quản lý đơn hàng</span>
                            </a>
                        </li>
                        <li>
                            <a href="./address-book.php"
                                class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">
                                <img src="../../img/icons/diachi.svg" class="w-5 h-5 mr-3" alt="Address">
                                <span>Sổ địa chỉ</span>
                            </a>
                        </li>
                        <li>
                            <a href="../../control/logout.php"
                                class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">
                                <img src="../../img/icons/logout.svg" class="w-5 h-5 mr-3" alt="Logout">
                                <span>Đăng xuất</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>




        <!-- Footer -->
        <footer id="footer" class="bg-black text-white mt-12">
            <div class="container mx-auto px-4 py-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="pl-5">
                        <h3 class="text-4xl font-bold mb-4">Boost<br>your power</h3>
                        <div class="flex space-x-3 mb-4">
                            <a href="https://www.facebook.com/nvbplay" target="_blank"
                                class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition"><i
                                    class="fab fa-facebook-f"></i></a>
                            <a href="https://www.tiktok.com/@nvbplay.vn" target="_blank"
                                class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition"><i
                                    class="fab fa-tiktok"></i></a>
                            <a href="https://s.shopee.vn/6AV9qQcpMz" target="_blank"
                                class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition"><i
                                    class="fas fa-shopping-bag"></i></a>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold mb-4">Thông tin khác</h3>
                        <ul class="space-y-2">
                            <li><a href="" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH BẢO MẬT</a></li>
                            <li><a href="" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH THANH TOÁN</a>
                            </li>
                            <li><a href="" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH BẢO HÀNH ĐỔI
                                    TRẢ</a></li>
                            <li><a href="" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH VẬN CHUYỂN</a>
                            </li>
                            <li><a href="" class="text-gray-400 hover:text-white transition">THOẢ THUẬN SỬ DỤNG</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold mb-4">Về chúng tôi</h3>
                        <ul class="space-y-3">
                            <li><a href="https://maps.app.goo.gl/mwqaes9hQJks8FSu5" target="_blank" class="flex"><span
                                        class="font-medium w-20">Địa chỉ:</span><span class="text-gray-400">62 Lê Bình,
                                        Tân An, Cần Thơ</span></a></li>
                            <li>
                                <div class="flex"><span class="font-medium w-20">Giờ làm việc:</span><span
                                        class="text-gray-400">08:00 - 21:00</span></div>
                            </li>
                            <li><a href="tel:0987.879.243" class="flex"><span
                                        class="font-medium w-20">Hotline:</span><span
                                        class="text-gray-400">0987.879.243</span></a></li>
                            <li><a href="mailto:info@nvbplay.vn" class="flex"><span
                                        class="font-medium w-20">Email:</span><span
                                        class="text-gray-400">info@nvbplay.vn</span></a></li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-gray-800 my-6"></div>
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-gray-500 text-sm mb-4 md:mb-0">
                        <p>©2025 CÔNG TY CỔ PHẦN NVB PLAY</p>
                        <p>GPĐKKD số 1801779686 do Sở KHĐT TP. Cần Thơ cấp ngày 22 tháng 01 năm 2025</p>
                    </div>
                    <a href="http://online.gov.vn/Home/WebDetails/129261" target="_blank"><img
                            src="https://nvbplay.vn/wp-content/uploads/2024/09/Logo-Bo-Cong-Thuong-Xanh.png"
                            alt="Bộ Công Thương" class="h-12 w-auto"></a>
                </div>
            </div>
        </footer>
    </div>

    <!-- Mobile Menu -->
    <div id="main-menu"
        class="fixed inset-0 bg-white z-50 transform -translate-x-full transition duration-300 md:hidden overflow-y-auto">
        <div class="p-4">
            <div class="flex justify-between items-center mb-6">
                <img src="../../img/icons/logonvb.png" height="30" width="50" class="transform scale-75">
                <button class="close-menu p-2 hover:bg-gray-100 rounded-full transition"><i
                        class="fas fa-times text-2xl text-gray-600"></i></button>
            </div>
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                <div class="flex items-center text-gray-700">
                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3"><img
                            src="../../img/icons/account.svg" class="w-6 h-6" alt="Account"></div>
                    <div>
                        <div class="font-medium">
                            <?php echo htmlspecialchars($user_info['username']); ?>
                        </div><span class="text-sm text-gray-500">
                            <?php echo htmlspecialchars($user_info['email']); ?>
                        </span>
                    </div>
                </div>
                <a href="../../control/logout.php" class="text-red-600 text-sm font-medium">Đăng xuất</a>
            </div>
        </div>
    </div>

    <script>
        // Toggle order details
        function toggleOrderDetails(orderId) {
            const details = document.getElementById(`order-details-${orderId}`);
            const btn = event.currentTarget;
            if (details.classList.contains('expanded')) {
                details.classList.remove('expanded');
                btn.querySelector('i').classList.remove('fa-chevron-up');
                btn.querySelector('i').classList.add('fa-chevron-down');
            } else {
                details.classList.add('expanded');
                btn.querySelector('i').classList.remove('fa-chevron-down');
                btn.querySelector('i').classList.add('fa-chevron-up');
            }
        }

        // Cancel order
        function cancelOrder(orderId) {
            if (confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')) {
                // Implement cancel logic here
                alert('Chức năng hủy đơn đang được phát triển');
            }
        }

        // User dropdown
        const userToggle = document.getElementById('userToggle');
        const userMenu = document.getElementById('userMenu');
        if (userToggle && userMenu) {
            userToggle.addEventListener('click', function (e) {
                e.stopPropagation();
                userMenu.classList.toggle('hidden');
            });
            document.addEventListener('click', function (e) {
                if (!userToggle.contains(e.target) && !userMenu.contains(e.target)) {
                    userMenu.classList.add('hidden');
                }
            });
        }

        // Mobile menu toggle
        function toggleMobileAccountMenu() {
            const mobileMenu = document.getElementById('mobile-account-menu');
            const overlay = document.getElementById('mobile-account-overlay');
            if (mobileMenu && overlay) {
                mobileMenu.classList.toggle('-translate-x-full');
                overlay.classList.toggle('hidden');
            }
        }
    </script>
    <!-- // mega menu -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menuTrigger = document.getElementById('mega-menu-trigger');
            const menuDropdown = document.getElementById('mega-menu-dropdown');
            const menuItems = document.querySelectorAll('.icon-box-menu[data-menu]');
            const menuContents = document.querySelectorAll('.menu-content');
            if (menuTrigger) {
                menuTrigger.addEventListener('click', function (e) {
                    e.stopPropagation();
                    menuDropdown.classList.toggle('hidden');
                });
            }
            menuItems.forEach(item => {
                item.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const menuId = this.getAttribute('data-menu');
                    menuItems.forEach(el => {
                        el.classList.remove('active', 'bg-red-50');
                        const titleEl = el.querySelector('.font-bold');
                        if (titleEl) titleEl.classList.remove('text-red-600');
                    });
                    this.classList.add('active', 'bg-red-50');
                    const activeTitle = this.querySelector('.font-bold');
                    if (activeTitle) activeTitle.classList.add('text-red-600');
                    menuContents.forEach(content => { content.classList.add('hidden'); });
                    const activeContent = document.getElementById(`content-${menuId}`);
                    if (activeContent) { activeContent.classList.remove('hidden'); }
                });
            });
            document.addEventListener('click', function (e) {
                if (!menuDropdown.contains(e.target) && !menuTrigger.contains(e.target)) {
                    menuDropdown.classList.add('hidden');
                }
            });
            menuDropdown.addEventListener('click', function (e) { e.stopPropagation(); });
        });
</body >

</html >