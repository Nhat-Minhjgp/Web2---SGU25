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
$stmt = $conn->prepare("SELECT User_id, Username, Ho_ten, email, SDT FROM users WHERE User_id = ?");
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
function formatPrice($price) {
    return number_format((float)$price, 0, ',', '.') . '₫';
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function getStatusBadge($status) {
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

function getStatusKey($status) {
    $map = [
        'Chờ thanh toán' => 'pending-payment',
        'Chờ xác nhận' => 'processing',
        'Đang giao' => 'shipping',
        'Hoàn thành' => 'completed',
        'Đã hủy' => 'cancelled'
     
    ];
    return $map[$status] ?? 'all';
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
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #888; border-radius: 3px; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        .tab-btn.active { color: #FF3F1A; border-bottom-color: #FF3F1A; }
        .order-details { display: none; }
        .order-details.expanded { display: block; }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50">

<!-- Header (Giữ nguyên từ template) -->
<header id="header" class="sticky top-0 z-40 bg-white shadow-sm">
    <div class="header-wrapper">
        <div id="masthead" class="py-2 md:py-3 border-b">
            <div class="container mx-auto px-4 flex items-center justify-between">
                <div class="md:hidden">
                    <button class="menu-toggle p-2">
                        <img src="../../img/icons/menu.svg" class="w-6 h-6">
                    </button>
                </div>
                <div class="hidden md:flex items-center flex-1 ml-6">
                    <ul class="flex items-center space-x-4">
                        <li>
                            <a href="../../index.php" class="flex items-center text-gray-700 hover:text-red-600 font-medium">
                                <img src="../../img/icons/store.svg" class="w-5 h-5 flex-shrink-0 mr-2">
                                <span>CỬA HÀNG</span>
                            </a>
                        </li>
                    </ul>
                </div>
                <div id="logo" class="flex-shrink-1 absolute left-1/2 transform -translate-x-1/2">
                    <a href="../../index.php" title="NVBPlay">
                        <img width="100" height="40" src="../../img/icons/logonvb.png" alt="NVBPlay" class="h-12 md:h-14 w-auto transform scale-75">
                    </a>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <div class="user-dropdown relative">
                        <button id="userToggle" class="flex items-center space-x-2 hover:bg-gray-100 px-3 py-2 rounded-lg transition">
                            <img src="../../img/icons/account.svg" class="w-6 h-6" alt="Account">
                            <span class="text-sm font-medium text-gray-700">
                                <?php echo htmlspecialchars($user_info['Username'] ?? 'User'); ?>
                            </span>
                            <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                        </button>
                        <div id="userMenu" class="user-menu absolute right-0 top-full mt-2 bg-white rounded-lg shadow-lg border min-w-[200px] hidden">
                            <a href="../my-account.php" class="block px-4 py-3 hover:bg-gray-50">Tài khoản</a>
                            <a href="./orders.php" class="block px-4 py-3 hover:bg-gray-50">Đơn hàng</a>
                            <a href="./address-book.php" class="block px-4 py-3 hover:bg-gray-50">Sổ địa chỉ</a>
                            <div class="border-t my-1"></div>
                            <a href="../../control/logout.php" class="block px-4 py-3 text-red-600 hover:bg-red-50">Đăng xuất</a>
                        </div>
                    </div>
                    <a href="../cart.php" class="relative p-2">
                        <i class="fas fa-shopping-basket text-gray-700 hover:text-red-600 text-xl"></i>
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
                        <img src="../../img/icons/account.svg" alt="User avatar" class="w-14 h-14 md:w-16 md:h-16 rounded-full border-2 border-gray-200">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($user_info['Ho_ten'] ?? 'Khách hàng'); ?></h3>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user_info['email'] ?? ''); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Navigation Menu -->
                <nav class="bg-white rounded-lg shadow-sm overflow-hidden hidden lg:block">
                    <ul class="divide-y divide-gray-200">
                        <li>
                            <a href="../my-account.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-[#FF3F1A] transition">
                                <img src="../../img/icons/account.svg" class="w-5 h-5 mr-3" alt="Account">
                                <span class="text-sm md:text-base">Thông tin tài khoản</span>
                            </a>
                        </li>
                        <li>
                            <a href="./orders.php" class="flex items-center px-4 py-3 bg-red-50 text-[#FF3F1A] font-medium border-l-4 border-[#FF3F1A]">
                                <img src="../../img/icons/clipboard.svg" class="w-5 h-5 mr-3" alt="Orders">
                                <span class="text-sm md:text-base">Quản lý đơn hàng</span>
                            </a>
                        </li>
                        <li>
                            <a href="./address-book.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-[#FF3F1A] transition">
                                <img src="../../img/icons/diachi.svg" class="w-5 h-5 mr-3" alt="Address">
                                <span class="text-sm md:text-base">Sổ địa chỉ</span>
                            </a>
                        </li>
                        <li>
                            <a href="../../control/logout.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-[#FF3F1A] transition">
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
                                <input type="text" name="search" placeholder="Tìm kiếm đơn hàng (Mã đơn, tên sản phẩm...)"
                                    value="<?php echo htmlspecialchars($search_query); ?>"
                                    class="w-full px-4 py-2 md:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition pl-10 text-sm md:text-base">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            </div>
                            <button type="submit" class="px-4 md:px-6 py-2 md:py-3 bg-[#FF3F1A] text-white rounded-lg hover:bg-red-700 transition whitespace-nowrap text-sm md:text-base">
                                Tìm kiếm
                            </button>
                        </form>
                    </div>

                    <!-- Order Status Tabs -->
                    <div class="flex overflow-x-auto scrollbar-hide mt-6 border-b border-gray-200" id="order-tabs">
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
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-shopping-bag text-4xl text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Chưa có đơn hàng nào</h3>
                        <p class="text-gray-600 mb-6">Bạn chưa đặt đơn hàng nào. Hãy bắt đầu mua sắm ngay!</p>
                        <a href="../../index.php" class="inline-block px-6 py-3 bg-[#FF3F1A] text-white rounded-lg hover:bg-red-700 transition">
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
                        <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-gray-100">
                            <div class="flex items-center gap-4">
                                <div>
                                    <span class="text-sm text-gray-500">Mã đơn hàng</span>
                                    <p class="font-semibold text-gray-900">#<?php echo $order['DonHang_id']; ?></p>
                                </div>
                                <div class="hidden sm:block w-px h-8 bg-gray-200"></div>
                                <div>
                                    <span class="text-sm text-gray-500">Ngày đặt</span>
                                    <p class="font-medium"><?php echo formatDate($order['NgayDat']); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <?php echo getStatusBadge($order['TrangThai']); ?>
                                <button class="text-gray-400 hover:text-gray-600" onclick="toggleOrderDetails(<?php echo $order['DonHang_id']; ?>)">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Order Products -->
                        <div class="py-4 space-y-3 order-details" id="order-details-<?php echo $order['DonHang_id']; ?>">
                            <?php foreach ($products as $product): ?>
                            <div class="flex items-start gap-4">
                                <img src="../../<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>"
                                    class="w-16 h-16 object-cover rounded-lg border border-gray-200">
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></h4>
                                    <div class="flex items-center justify-between mt-1">
                                        <span class="text-sm text-gray-600">Số lượng: <?php echo $product['qty']; ?></span>
                                        <span class="font-semibold text-[#FF3F1A]"><?php echo formatPrice($product['price'] * $product['qty']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Order Footer -->
                        <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-gray-100">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-600">Tổng tiền:</span>
                                <span class="text-xl font-bold text-[#FF3F1A]"><?php echo formatPrice($order['TongTien']); ?></span>
                            </div>
                            <div class="flex gap-2">
                                <?php if ($order['linkTraCuu']): ?>
                                <a href="../track-order.php?code=<?php echo urlencode(str_replace('/view/track-order.php?code=', '', $order['linkTraCuu'])); ?>" 
                                   class="px-4 py-2 text-sm bg-[#FF3F1A] text-white rounded-lg hover:bg-red-700 transition">
                                    Xem
                                </a>
                                <?php endif; ?>
                                <?php if ($order['TrangThai'] === 'Hoàn thành'): ?>
                                <button class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                    Mua lại
                                </button>
                                <button class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                    Đánh giá
                                </button>
                                <?php elseif ($order['TrangThai'] === 'Chờ xác nhận'): ?>
                                <button class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition" onclick="cancelOrder(<?php echo $order['DonHang_id']; ?>)">
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
                            <span class="w-10 h-10 flex items-center justify-center rounded-lg bg-[#FF3F1A] text-white"><?php echo $i; ?></span>
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

<!-- Footer (Giữ nguyên từ template) -->
<footer id="footer" class="bg-black text-white mt-12">
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="pl-5">
                <h3 class="text-4xl font-bold mb-4">Boost<br>your power</h3>
                <div class="flex space-x-3 mb-4">
                    <a href="https://www.facebook.com/nvbplay" target="_blank" class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://www.tiktok.com/@nvbplay.vn" target="_blank" class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition">
                        <i class="fab fa-tiktok"></i>
                    </a>
                </div>
            </div>
            <div>
                <h3 class="text-xl font-bold mb-4">Thông tin khác</h3>
                <ul class="space-y-2">
                    <li><a href="#" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH BẢO MẬT</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH THANH TOÁN</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH BẢO HÀNH ĐỔI TRẢ</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-xl font-bold mb-4">Về chúng tôi</h3>
                <ul class="space-y-3">
                    <li><a href="https://maps.app.goo.gl/mwqaes9hQJks8FSu5" target="_blank" class="flex">
                        <span class="font-medium w-20">Địa chỉ:</span>
                        <span class="text-gray-400">62 Lê Bình, Tân An, Cần Thơ</span>
                    </a></li>
                    <li><a href="tel:0987.879.243" class="flex">
                        <span class="font-medium w-20">Hotline:</span>
                        <span class="text-gray-400">0987.879.243</span>
                    </a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-800 my-6"></div>
        <div class="text-gray-500 text-sm text-center">
            <p>©2025 CÔNG TY CỔ PHẦN NVB PLAY</p>
        </div>
    </div>
</footer>

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
    userToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        userMenu.classList.toggle('hidden');
    });
    document.addEventListener('click', function(e) {
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
</body>
</html>