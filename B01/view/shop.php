<?php
// view/shop.php
session_start();
require_once '../control/connect.php';

// === KIỂM TRA ĐĂNG NHẬP  ===
$is_logged_in = isset($_SESSION['user_id']);
$user_info = null;

$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}
// Xử lý buy_now mode (nếu có)
if (isset($_SESSION['buy_now_cart']) && is_array($_SESSION['buy_now_cart'])) {
    $cart_count += array_sum($_SESSION['buy_now_cart']);
}

if ($is_logged_in) {
    // Chặn role=1 (Staff/Admin) không được vào khu vực user
    if (isset($_SESSION['role']) && $_SESSION['role'] == 1) {
        session_destroy();
        setcookie('remember_user', '', time() - 3600, '/');
        header("Location: login.php?error=staff_not_allowed");
        exit();
    }

    $user_info = [
        'user_id' => $_SESSION['user_id'] ?? '',
        'username' => $_SESSION['username'] ?? '',
        'ho_ten' => $_SESSION['ho_ten'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? 0
    ];
}

// === SERVER-SIDE SQL INJECTION PATTERNS FOR SEARCH ===
$sqlInjectionPatterns = [
    '/(\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b|\bUNION\b|\bEXEC\b|\bTRUNCATE\b)/i',
    '/(--|\/\*|\*\/|#|;)/',
    '/(\bOR\b|\bAND\b)\s*([\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+|[\'"]+[\'"]*)/i',
    '/\b(WAITFOR|BENCHMARK|SLEEP|xp_|sp_)\b/i',
    '/[\'"]\s*OR\s*[\'"]?1[\'"]?\s*=\s*[\'"]?1/i',
    '/%00|%27|%22|%3B/i'
];

function checkSQLInjectionServer($value)
{
    global $sqlInjectionPatterns;
    foreach ($sqlInjectionPatterns as $pattern) {
        if (preg_match($pattern, $value)) {
            return true;
        }
    }
    return false;
}

// Xử lý filter từ URL
$where_conditions = ["s.TrangThai = 1"];
$params = [];
$types = "";

//  TÌM KIẾM THEO TÊN SẢN PHẨM + SQL INJECTION CHECK
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : '';

// Server-side SQL injection check cho search
if (!empty($search_keyword) && checkSQLInjectionServer($search_keyword)) {
    $search_keyword = ''; // Reset nếu phát hiện injection
    $errors[] = "Từ khóa tìm kiếm chứa ký tự không an toàn";
}

if (!empty($search_keyword)) {
    $where_conditions[] = "s.TenSP LIKE ?";
    $params[] = "%{$search_keyword}%";
    $types .= "s";
}

// Filter theo danh mục (MULTIPLE - sử dụng array)
if (isset($_GET['danhmuc']) && is_array($_GET['danhmuc']) && !empty($_GET['danhmuc'])) {
    $danhmuc_placeholders = implode(',', array_fill(0, count($_GET['danhmuc']), '?'));
    $where_conditions[] = "d.slug IN ($danhmuc_placeholders)";
    foreach ($_GET['danhmuc'] as $slug) {
        $params[] = $slug;
        $types .= "s";
    }
}

// Filter theo thương hiệu (MULTIPLE - sử dụng array)
if (isset($_GET['thuonghieu']) && is_array($_GET['thuonghieu']) && !empty($_GET['thuonghieu'])) {
    $thuonghieu_placeholders = implode(',', array_fill(0, count($_GET['thuonghieu']), '?'));
    $where_conditions[] = "th.slug IN ($thuonghieu_placeholders)";
    foreach ($_GET['thuonghieu'] as $slug) {
        $params[] = $slug;
        $types .= "s";
    }
}

// Filter theo giá
$min_price = isset($_GET['min_price']) ? (int) $_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (int) $_GET['max_price'] : 50000000;
if ($min_price > 0 || $max_price < 50000000) {
    $where_conditions[] = "s.GiaBan BETWEEN ? AND ?";
    $params[] = $min_price;
    $params[] = $max_price;
    $types .= "ii";
}

// Xử lý sắp xếp
$sort_order = "s.SanPham_id DESC";
if (isset($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'price_asc':
            $sort_order = "s.GiaBan ASC";
            break;
        case 'price_desc':
            $sort_order = "s.GiaBan DESC";
            break;
        case 'name_asc':
            $sort_order = "s.TenSP ASC";
            break;
        default:
            $sort_order = "s.SanPham_id DESC";
    }
}

// Xây dựng câu truy vấn
$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Đếm tổng số sản phẩm
$count_sql = "SELECT COUNT(*) as total FROM sanpham s
LEFT JOIN danhmuc d ON s.Danhmuc_id = d.Danhmuc_id
LEFT JOIN thuonghieu th ON s.Ma_thuonghieu = th.Ma_thuonghieu
$where_sql";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_products = $count_result->fetch_assoc()['total'];

// Pagination
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;
$total_pages = ceil($total_products / $limit);

// Lấy sản phẩm (Prepared Statement - Chống SQL Injection)
$sql = "SELECT s.*, d.Ten_danhmuc, d.slug as danhmuc_slug,
th.Ten_thuonghieu, th.slug as thuonghieu_slug,
ncc.Ten_NCC
FROM sanpham s
LEFT JOIN danhmuc d ON s.Danhmuc_id = d.Danhmuc_id
LEFT JOIN thuonghieu th ON s.Ma_thuonghieu = th.Ma_thuonghieu
LEFT JOIN nhacungcap ncc ON s.NCC_id = ncc.NCC_id
$where_sql
ORDER BY $sort_order
LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $all_params = array_merge($params, [$limit, $offset]);
    $stmt->bind_param($types . "ii", ...$all_params);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$products = $stmt->get_result();

// Lấy danh sách danh mục cho filter
$categories_sql = "SELECT d.*,
(SELECT COUNT(*) FROM sanpham WHERE Danhmuc_id = d.Danhmuc_id AND TrangThai = 1) as product_count
FROM danhmuc d
ORDER BY Ten_danhmuc";
$categories_result = $conn->query($categories_sql);

// Lấy danh sách thương hiệu cho filter
$brands_sql = "SELECT th.*,
(SELECT COUNT(*) FROM sanpham WHERE Ma_thuonghieu = th.Ma_thuonghieu AND TrangThai = 1) as product_count
FROM thuonghieu th
ORDER BY Ten_thuonghieu";
$brands_result = $conn->query($brands_sql);
$brands_list = [];
while ($brand = $brands_result->fetch_assoc()) {
    $brands_list[] = $brand;
}

// Format giá
function formatPrice($price)
{
    return number_format($price, 0, ',', '.') . '₫';
}

// Tính phần trăm giảm giá
function calculateDiscount($import_price, $sell_price)
{
    if ($import_price > $sell_price && $import_price > 0) {
        return round(($import_price - $sell_price) / $import_price * 100);
    }
    return 0;
}

// Helper function để build URL với filter
function buildFilterUrl($additional_params = [])
{
    $params = $_GET;
    foreach ($additional_params as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }
    return 'shop.php?' . http_build_query($params);
}

// Helper để kiểm tra filter đang active
function isActiveFilter($type, $value)
{
    if ($type === 'danhmuc' || $type === 'thuonghieu') {
        return isset($_GET[$type]) && is_array($_GET[$type]) && in_array($value, $_GET[$type]);
    }
    return false;
}

// Helper lấy tên hiển thị cho filter tag
function getFilterDisplayName($type, $slug)
{
    global $conn;
    if ($type === 'danhmuc') {
        $stmt = $conn->prepare("SELECT Ten_danhmuc FROM danhmuc WHERE slug = ?");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return $row['Ten_danhmuc'];
        }
    } elseif ($type === 'thuonghieu') {
        $stmt = $conn->prepare("SELECT Ten_thuonghieu FROM thuonghieu WHERE slug = ?");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return $row['Ten_thuonghieu'];
        }
    }
    return $slug;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cửa hàng - NVBPlay</title>
    <meta name="description"
        content="NVBPlay chuyên cung cấp đồ cầu lông và pickleball cao cấp, từ vợt, giày, đến phụ kiện chính hãng.">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        body.filter-open {
            overflow: hidden;
        }

        #mobile-filter-drawer {
            transition: transform 0.3s ease-in-out;
        }

        #mobile-filter-drawer.open {
            transform: translateX(0);
        }

        #mobile-filter-drawer.closed {
            transform: translateX(100%);
        }

        #mobile-filter-overlay {
            transition: opacity 0.3s ease;
        }

        #mobile-filter-overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }

        #mobile-filter-overlay.visible {
            opacity: 1;
            pointer-events: auto;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .filter-btn {
            position: relative;
            cursor: pointer;
            transition: all 0.2s ease;
            display: block;
            width: 100%;
        }

        .filter-btn input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .filter-btn .btn-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 13px;
            color: #374151;
            transition: all 0.2s ease;
            width: 100%;
            min-height: 44px;
            box-sizing: border-box;
        }

        .filter-btn input[type="checkbox"]:checked+.btn-content {
            background: #fef2f2;
            border-color: #dc2626;
            color: #dc2626;
            font-weight: 500;
        }

        .filter-btn:hover .btn-content {
            border-color: #dc2626;
            background: #fef2f2;
        }

        .filter-btn .btn-label {
            display: flex;
            align-items: center;
            gap: 6px;
            flex: 1;
            min-width: 0;
        }

        .filter-btn .btn-label span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .filter-btn .btn-count {
            font-size: 11px;
            color: #6b7280;
            background: #e5e7eb;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 6px;
            flex-shrink: 0;
            min-width: 24px;
            text-align: center;
        }

        .filter-btn input[type="checkbox"]:checked+.btn-content .btn-count {
            background: #fecaca;
            color: #dc2626;
        }

        .filter-btn .check-icon {
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.2s ease;
            color: #dc2626;
            font-size: 14px;
            flex-shrink: 0;
            margin-left: 4px;
        }

        .filter-btn input[type="checkbox"]:checked+.btn-content .check-icon {
            opacity: 1;
            transform: scale(1);
        }

        .sort-dropdown {
            position: relative;
        }

        .sort-select {
            appearance: none;
            padding: 8px 36px 8px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 13px;
            background: white;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .sort-select:focus {
            outline: none;
            border-color: #dc2626;
        }

        .sort-dropdown::after {
            content: '\f107';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            pointer-events: none;
        }

        .filter-btn-grid {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-btn-grid .filter-btn {
            display: block;
            width: 100%;
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

        /* Role badge styles */
        .role-badge-staff {
            background: #dc2626;
        }

        .role-badge-admin {
            background: #7c3aed;
        }

        .role-badge-user {
            background: #6b7280;
        }

        @media (max-width: 768px) {
            .suggestion-item img {
                width: 40px;
                height: 40px;
            }

            .suggestion-info h4 {
                font-size: 14px;
            }

            .suggestion-info .price {
                font-size: 13px;
            }
        }

        /* === CSS CHO SEARCH INPUT  ===*/
        #searchInput {
            background-color: #EEEEEE;
            transition: all 0.2s ease;
        }

        #searchInput:focus {
            background-color: #EEEEEE;
            border-color: #ffffff;
            box-shadow: 0 0 0 3px rgb(255, 255, 255);
        }

        #searchInput::placeholder {
            color: #1a1919;
            font-size: 15px;
            font-weight: 400;
        }

        /* Nút search và đóng trong header */
        .search-action-btn {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            transition: all 0.2s;
            cursor: pointer;
        }

        .search-action-btn:hover {
            background-color: #ffffff;
        }

        .close-btn {
            color: #ffffff;
        }

        .close-btn:hover {
            background-color: #ffffff;
            color: white;
        }
    </style>
    <link rel="icon" type="image/svg+xml" href="../img/icons/favicon.png" sizes="32x32">
</head>

<body class="font-sans antialiased bg-gray-50">
    <!-- Popup Overlay -->
    <div id="popup_overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50"></div>

    <!-- Mobile Filter Overlay & Drawer -->
    <div id="mobile-filter-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>
    <div id="mobile-filter-drawer"
        class="fixed inset-y-0 right-0 z-50 w-full lg:hidden bg-white shadow-2xl closed flex flex-col h-full">
        <div class="p-4 border-b flex items-center justify-between bg-white sticky top-0 z-10">
            <h3 class="font-bold text-lg flex items-center"><i class="fas fa-sliders-h mr-2 text-red-600"></i> Bộ lọc
                sản phẩm</h3>
            <button id="close-mobile-filter" class="p-2 hover:bg-gray-100 rounded-full transition"><i
                    class="fas fa-times text-gray-500 text-lg"></i></button>
        </div>
        <div class="flex-1 overflow-y-auto p-5 custom-scrollbar space-y-4" id="mobile-filter-content"></div>
        <div class="p-4 border-t bg-white sticky bottom-0 z-10 flex gap-3">
            <button type="button" id="reset-mobile-filter"
                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 py-3 rounded-lg font-medium transition"><i
                    class="fas fa-undo mr-2"></i>Reset</button>
            <button type="button" id="apply-mobile-filter"
                class="flex-1 bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg font-medium transition shadow-lg shadow-red-200"><i
                    class="fas fa-check mr-2"></i>Áp dụng</button>
        </div>
    </div>

    <!-- Main Wrapper -->
    <div id="wrapper" class="min-h-screen flex flex-col">
        <!-- Header -->
        <header id="header" class="sticky top-0 z-40 bg-white shadow-sm">
            <div class="header-wrapper">
                <!-- Bottom Header / Wide Nav (quảng cáo trên cùng) -->
                <div id="wide-nav" class="bg-gray-900 text-white py-2">
                    <div class="container mx-auto px-4 text-center">
                        <div class="top-hot">
                            <a href="./product.php?id=4"
                                class="text-white hover:text-yellow-300 transition text-sm md:text-base">
                                ⚡ VỢT YONEX NANOFLARE 1000 GAME - RESTOCKED ⚡
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Main Header -->
                <div id="masthead" class="py-2 md:py-3 border-b">
                    <div class="container mx-auto px-4">
                        <!-- ========== DEFAULT HEADER (luôn hiển thị trừ khi search mở) ========== -->
                        <div id="defaultHeader" class="grid grid-cols-[1fr,auto,1fr] items-center gap-0 relative">

                            <!-- Cột trái: menu mobile + menu desktop (căn trái) -->
                            <div class="flex items-center justify-start">
                                <!-- Mobile Menu Toggle (chỉ hiện trên mobile) -->
                                <div class="md:hidden">
                                    <button class="menu-toggle p-2 focus:outline-none">
                                        <img src="../img/icons/menu.svg" class="w-6 h-6" alt="menu">
                                    </button>
                                </div>

                                <!-- Desktop Left Menu (chỉ hiện trên desktop) -->
                                <div class="hidden md:flex items-center ml-0 lg:ml-2">
                                    <ul class="flex items-center space-x-4">
                                        <!-- Mega Menu Trigger (GIỮ NGUYÊN) -->
                                        <div class="hidden md:flex items-center ml-0 lg:ml-2">
                                            <ul class="flex items-center space-x-4">
                                                <li class="relative" id="mega-menu-container">
                                                    <button id="mega-menu-trigger"
                                                        class="button-menu flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200 transition">
                                                        <img src="../img/icons/menu.svg" class="w-5 h-5 mr-2"
                                                            alt="menu">
                                                        <span>Danh mục</span>
                                                    </button>

                                                    <!-- Mega Menu Dropdown (CHỈ SỬA PHẦN NÀY) -->
                                                    <div id="mega-menu-dropdown"
                                                        class="absolute left-0 top-full mt-2 w-[900px] bg-white rounded-lg shadow-xl hidden z-50">
                                                        <div class="flex p-4">
                                                            <!-- Left Sidebar - Icon Menu -->
                                                            <div class="w-64 border-r border-gray-200 pr-4">
                                                                <!-- Cầu Lông - Active -->
                                                                <div class="icon-box-menu active bg-red-50 rounded-lg p-3 mb-1 cursor-pointer hover:bg-red-50 transition flex items-start"
                                                                    data-menu="badminton">
                                                                    <div class="w-8 h-8 flex-shrink-0 mr-3">
                                                                        <img src="../img/icons/logo-caulong.png"
                                                                            alt="Cầu Lông" class="w-full h-full">
                                                                    </div>
                                                                    <div>
                                                                        <p class="font-bold text-red-600">Cầu Lông</p>
                                                                        <p class="text-xs text-gray-500">Trang bị cầu
                                                                            lông chuyên nghiệp</p>
                                                                    </div>
                                                                </div>

                                                                <!-- Pickleball -->
                                                                <div class="icon-box-menu p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start"
                                                                    data-menu="pickleball">
                                                                    <div class="w-8 h-8 flex-shrink-0 mr-3">
                                                                        <img src="../img/icons/logo-pickleball.png"
                                                                            alt="Pickleball" class="w-full h-full">
                                                                    </div>
                                                                    <div>
                                                                        <p class="font-bold">Pickleball</p>
                                                                        <p class="text-xs text-gray-500">Trang bị
                                                                            pickleball hàng đầu</p>
                                                                    </div>
                                                                </div>

                                                                <!-- Giày -->
                                                                <div class="icon-box-menu p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start"
                                                                    data-menu="giay">
                                                                    <div class="w-8 h-8 flex-shrink-0 mr-3">
                                                                        <img src="../img/icons/logo-giay.png" alt="Giày"
                                                                            class="w-full h-full">
                                                                    </div>
                                                                    <div>
                                                                        <p class="font-bold">Giày</p>
                                                                        <p class="text-xs text-gray-500">Giày thể thao
                                                                            tối ưu hoá vận động</p>
                                                                    </div>
                                                                </div>


                                                            </div>

                                                            <!-- Right Content -->
                                                            <div class="flex-1 pl-4">
                                                                <!-- Content Badminton -->
                                                                <div id="content-badminton" class="menu-content">
                                                                    <!-- Thương hiệu nổi bật - 8 HÃNG -->
                                                                    <div class="mb-4">
                                                                        <div
                                                                            class="flex items-center justify-between mb-2">
                                                                            <h3 class="font-bold">Thương hiệu nổi bật
                                                                            </h3>
                                                                            <a href="./shop.php"
                                                                                class="text-sm text-red-600 hover:text-red-700 flex items-center">
                                                                                Xem tất cả <i
                                                                                    class="fas fa-chevron-right ml-1 text-xs"></i>
                                                                            </a>
                                                                        </div>
                                                                        <div class="grid grid-cols-4 gap-2">
                                                                            <!-- YONEX -->
                                                                            <a href="./shop.php?thuonghieu[]=yonex"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../img/icons/logo-yonex.webp"
                                                                                        alt="Yonex"
                                                                                        class="w-full h-full object-contain"
                                                                                        onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">YONEX</span>
                                                                            </a>

                                                                            <!-- ADIDAS -->
                                                                            <a href="./shop.php?thuonghieu[]=adidas"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../img/icons/logo-adidas.webp"
                                                                                        alt="Adidas"
                                                                                        class="w-full h-full object-contain"
                                                                                        onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">ADIDAS</span>
                                                                            </a>

                                                                            <!-- LI-NING -->
                                                                            <a href="./shop.php?thuonghieu[]=li-ning"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../img/icons/Logo-li-ning.png"
                                                                                        alt="Li-Ning"
                                                                                        class="w-full h-full object-contain"
                                                                                        onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">LI-NING</span>
                                                                            </a>

                                                                            <!-- VICTOR -->
                                                                            <a href="./shop.php?thuonghieu[]=victor"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../img/icons/logo-victor.png"
                                                                                        alt="Victor"
                                                                                        class="w-full h-full object-contain"
                                                                                        onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">VICTOR</span>
                                                                            </a>

                                                                            <!-- KAMITO -->
                                                                            <a href="./shop.php?thuonghieu[]=kamito"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../img/icons/logo-kamito.png"
                                                                                        alt="KAMITO"
                                                                                        class="w-full h-full object-contain"
                                                                                        onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">KAMITO</span>
                                                                            </a>

                                                                            <!-- MIZUNO -->
                                                                            <a href="./shop.php?thuonghieu[]=mizuno"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../img/icons/logo-mizuno.png"
                                                                                        alt="Mizuno"
                                                                                        class="w-full h-full object-contain"
                                                                                        onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">MIZUNO</span>
                                                                            </a>

                                                                            <!-- KUMPOO -->
                                                                            <a href="./shop.php?thuonghieu[]=kumpoo"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../img/icons/logo-kumpoo.png"
                                                                                        alt="Kumpoo"
                                                                                        class="w-full h-full object-contain"
                                                                                        onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">KUMPOO</span>
                                                                            </a>

                                                                            <!-- VENSON -->
                                                                            <a href="./shop.php?thuonghieu[]=venson"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../img/icons/logo-venson.png"
                                                                                        alt="Venson"
                                                                                        class="w-full h-full object-contain"
                                                                                        onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">VENSON</span>
                                                                            </a>
                                                                        </div>
                                                                    </div>

                                                                    <div class="border-t border-gray-200 my-3"></div>

                                                                    <!-- Theo sản phẩm - CẦU LÔNG -->
                                                                    <div>
                                                                        <div
                                                                            class="flex items-center justify-between mb-2">
                                                                            <h3 class="font-bold">Theo sản phẩm</h3>
                                                                            <a href="./shop.php?danhmuc[]=vot-cau-long"
                                                                                class="text-sm text-red-600 hover:text-red-700 flex items-center">
                                                                                Xem tất cả <i
                                                                                    class="fas fa-chevron-right ml-1 text-xs"></i>
                                                                            </a>
                                                                        </div>
                                                                        <div class="grid grid-cols-3 gap-4">
                                                                            <!-- Vợt cầu lông - 8 thương hiệu + Xem thêm -->
                                                                            <div>
                                                                                <a href="./shop.php?danhmuc[]=vot-cau-long"
                                                                                    class="font-semibold text-sm hover:text-red-600">Vợt
                                                                                    cầu lông</a>
                                                                                <ul class="mt-2 space-y-1">
                                                                                    <li><a href="./shop.php?danhmuc[]=vot-cau-long&thuonghieu[]=yonex"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Vợt
                                                                                            cầu lông Yonex</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=vot-cau-long&thuonghieu[]=li-ning"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Vợt
                                                                                            cầu lông Li-Ning</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=vot-cau-long&thuonghieu[]=adidas"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Vợt
                                                                                            cầu lông Adidas</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=vot-cau-long&thuonghieu[]=victor"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Vợt
                                                                                            cầu lông Victor</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=vot-cau-long"
                                                                                            class="text-xs text-red-600 hover:text-red-700 font-medium">Xem
                                                                                            thêm <i
                                                                                                class="fas fa-chevron-right ml-1 text-[10px]"></i></a>
                                                                                    </li>
                                                                                </ul>
                                                                            </div>

                                                                            <!-- Balo cầu lông - 8 thương hiệu + Xem thêm -->
                                                                            <div>
                                                                                <a href="./shop.php?danhmuc[]=balo-cau-long"
                                                                                    class="font-semibold text-sm hover:text-red-600">Balo
                                                                                    cầu lông</a>
                                                                                <ul class="mt-2 space-y-1">
                                                                                    <li><a href="./shop.php?danhmuc[]=balo-cau-long&thuonghieu[]=yonex"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Balo
                                                                                            Yonex</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=balo-cau-long&thuonghieu[]=li-ning"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Balo
                                                                                            Li-Ning</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=balo-cau-long&thuonghieu[]=adidas"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Balo
                                                                                            Adidas</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=balo-cau-long&thuonghieu[]=victor"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Balo
                                                                                            Victor</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=balo-cau-long"
                                                                                            class="text-xs text-red-600 hover:text-red-700 font-medium">Xem
                                                                                            thêm <i
                                                                                                class="fas fa-chevron-right ml-1 text-[10px]"></i></a>
                                                                                    </li>
                                                                                </ul>
                                                                            </div>

                                                                            <!-- Phụ kiện cầu lông - Giữ nguyên -->
                                                                            <div>
                                                                                <a href="./shop.php?danhmuc[]=phu-kien"
                                                                                    class="font-semibold text-sm hover:text-red-600">Phụ
                                                                                    kiện</a>
                                                                                <ul class="mt-2 space-y-1">
                                                                                    <li><a href="./shop.php?danhmuc[]=phu-kien"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Tất
                                                                                            cả phụ kiện</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=phu-kien&search=quả+cầu"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Quả
                                                                                            cầu lông</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=phu-kien&search=cước+đan"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Cước
                                                                                            đan vợt</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=phu-kien&search=quấn+cán"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Quấn
                                                                                            cán</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=phu-kien"
                                                                                            class="text-xs text-red-600 hover:text-red-700 font-medium">Xem
                                                                                            thêm <i
                                                                                                class="fas fa-chevron-right ml-1 text-[10px]"></i></a>
                                                                                    </li>
                                                                                </ul>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- Content for Pickleball -->
                                                                <div id="content-pickleball"
                                                                    class="menu-content hidden">
                                                                    <!-- Thương hiệu nổi bật - 4 HÃNG (CÂN ĐỐI) -->
                                                                    <div class="mb-4">
                                                                        <div
                                                                            class="flex items-center justify-between mb-2">
                                                                            <h3 class="font-bold">Thương hiệu nổi bật
                                                                            </h3>
                                                                            <a href="./shop.php?danhmuc[]=vot-pickleball"
                                                                                class="text-sm text-red-600 hover:text-red-700 flex items-center">
                                                                                Xem tất cả <i
                                                                                    class="fas fa-chevron-right ml-1 text-xs"></i>
                                                                            </a>
                                                                        </div>
                                                                        <div class="grid grid-cols-4 gap-2">
                                                                            <!-- JOOLA -->
                                                                            <a href="./shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=joola"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../img/icons/logo-joola.png"
                                                                                        alt="JOOLA"
                                                                                        class="w-full h-full object-contain"
                                                                                        onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">JOOLA</span>
                                                                            </a>

                                                                            <!-- SELKIRK -->
                                                                            <a href="./shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=selkirk"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../img/icons/logo-selkirk.webp"
                                                                                        alt="SELKIRK"
                                                                                        class="w-full h-full object-contain"
                                                                                        onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">SELKIRK</span>
                                                                            </a>

                                                                            <!-- KAMITO -->
                                                                            <a href="./shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=kamito"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../img/icons/logo-kamito.png"
                                                                                        alt="KAMITO"
                                                                                        class="w-full h-full object-contain"
                                                                                        onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">KAMITO</span>
                                                                            </a>

                                                                            <!-- WIKA -->
                                                                            <a href="./shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=wika"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../img/icons/logo-wika.png"
                                                                                        alt="WIKA"
                                                                                        class="w-full h-full object-contain"
                                                                                        onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">WIKA</span>
                                                                            </a>
                                                                        </div>
                                                                    </div>

                                                                    <div class="border-t border-gray-200 my-3"></div>

                                                                    <!-- Theo sản phẩm - PICKLEBALL -->
                                                                    <div>
                                                                        <div
                                                                            class="flex items-center justify-between mb-2">
                                                                            <h3 class="font-bold">Theo sản phẩm</h3>
                                                                            <a href="./shop.php?danhmuc[]=vot-pickleball"
                                                                                class="text-sm text-red-600 hover:text-red-700 flex items-center">
                                                                                Xem tất cả <i
                                                                                    class="fas fa-chevron-right ml-1 text-xs"></i>
                                                                            </a>
                                                                        </div>
                                                                        <div class="grid grid-cols-3 gap-4">
                                                                            <!-- Vợt Pickleball - 4 thương hiệu + Xem thêm -->
                                                                            <div>
                                                                                <a href="./shop.php?danhmuc[]=vot-pickleball"
                                                                                    class="font-semibold text-sm hover:text-red-600">Vợt
                                                                                    Pickleball</a>
                                                                                <ul class="mt-2 space-y-1">
                                                                                    <li><a href="./shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=joola"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Vợt
                                                                                            Joola</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=selkirk"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Vợt
                                                                                            Selkirk</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=kamito"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Vợt
                                                                                            Kamito</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=wika"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Vợt
                                                                                            Wika</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=vot-pickleball"
                                                                                            class="text-xs text-red-600 hover:text-red-700 font-medium">Xem
                                                                                            thêm <i
                                                                                                class="fas fa-chevron-right ml-1 text-[10px]"></i></a>
                                                                                    </li>
                                                                                </ul>
                                                                            </div>

                                                                            <!-- Balo/Túi Pickleball - 4 thương hiệu + Xem thêm -->
                                                                            <div>
                                                                                <a href="./shop.php?danhmuc[]=balo-tui-pickleball"
                                                                                    class="font-semibold text-sm hover:text-red-600">Balo
                                                                                    - Túi Pickleball</a>
                                                                                <ul class="mt-2 space-y-1">
                                                                                    <li><a href="./shop.php?danhmuc[]=balo-tui-pickleball&thuonghieu[]=joola"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Balo
                                                                                            Joola</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=balo-tui-pickleball&thuonghieu[]=selkirk"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Balo
                                                                                            Selkirk</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=balo-tui-pickleball&thuonghieu[]=kamito"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Balo
                                                                                            Kamito</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=balo-tui-pickleball&thuonghieu[]=wika"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Balo
                                                                                            Wika</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=balo-tui-pickleball"
                                                                                            class="text-xs text-red-600 hover:text-red-700 font-medium">Xem
                                                                                            thêm <i
                                                                                                class="fas fa-chevron-right ml-1 text-[10px]"></i></a>
                                                                                    </li>
                                                                                </ul>
                                                                            </div>

                                                                            <!-- Phụ kiện Pickleball -->
                                                                            <div>
                                                                                <a href="./view/shop.php?danhmuc[]=phu-kien-pickleball"
                                                                                    class="font-semibold text-sm hover:text-red-600">Phụ
                                                                                    kiện Pickleball</a>
                                                                                <ul class="mt-2 space-y-1">
                                                                                    <li><a href="./shop.php?danhmuc[]=phu-kien-pickleball&search=bóng"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Bóng
                                                                                            Pickleball</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=phu-kien-pickleball&search=lưới"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Lưới
                                                                                            Pickleball</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=phu-kien-pickleball"
                                                                                            class="text-xs text-red-600 hover:text-red-700 font-medium">Xem
                                                                                            thêm <i
                                                                                                class="fas fa-chevron-right ml-1 text-[10px]"></i></a>
                                                                                    </li>
                                                                                </ul>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- Content Giày -->
                                                                <div id="content-giay" class="menu-content hidden">
                                                                    <div class="text-center py-16">
                                                                        <div
                                                                            class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                                                                            <i
                                                                                class="fas fa-shoe-prints text-3xl text-gray-400"></i>
                                                                        </div>
                                                                        <h3
                                                                            class="text-lg font-bold text-gray-700 mb-2">
                                                                            Sản Phẩm Sớm Ra Mắt</h3>
                                                                        <p class="text-gray-500 text-sm">Chúng tôi đang
                                                                            chuẩn bị những mẫu giày thể thao chất lượng
                                                                            nhất. Hãy quay lại sau nhé!</p>
                                                                    </div>
                                                                </div>


                                                            </div>
                                                        </div>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                        <li><a href="./shop.php"
                                                class="flex items-center text-gray-700 hover:text-red-600 font-medium"><img
                                                    src="../img/icons/store.svg"
                                                    class="w-5 h-5 flex-shrink-0 mr-2"><span>CỬA HÀNG</span></a></li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Cột giữa: LOGO – được căn giữa hoàn hảo nhờ grid -->
                            <div id="logo" class="flex justify-center items-center">
                                <a href="../index.php" title="NVBPlay" rel="home">
                                    <img width="100" height="40" src="../img/icons/logonvb.png" alt="NVBPlay"
                                        class="h-12 md:h-14 w-auto transform scale-75">
                                </a>
                            </div>

                            <!-- Cột phải: các thành phần desktop + mobile (căn phải) -->
                            <div class="flex items-center justify-end">
                                <!-- Desktop Right Elements (ẩn trên mobile) -->
                                <div class="hidden md:flex items-center space-x-4">
                                    <!-- Address Book (chỉ hiển thị khi đã đăng nhập) - giả lập biến is_logged_in = false để demo, nếu true sẽ hiện -->



                                    <div class="address-book">
                                        <a href="../view/my-account/address-book.php"
                                            class="flex items-center text-gray-700 hover:text-red-600">
                                            <i class="fas fa-map-marker-alt mr-1"></i>
                                            <span class="shipping-address text-sm"><span class="text">Chọn địa
                                                    chỉ</span></span>
                                        </a>
                                    </div>
                                    <div class="h-5 w-px bg-gray-300"></div>


                                    <!-- Search button
                                    <button id="searchToggle"
                                        class="search-toggle p-2 text-gray-700 hover:text-red-600">
                                        <i class="fas fa-search text-xl"></i>
                                    </button> -->

                                    <!-- Account Dropdown -->
                                    <div class="user-dropdown relative">
                                        <?php if ($is_logged_in): ?>
                                            <button id="userToggle"
                                                class="flex items-center space-x-2 hover:bg-gray-100 px-3 py-2 rounded-lg transition">
                                                <img src="../img/icons/account.svg" class="w-6 h-6" alt="Account">
                                                <span
                                                    class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($user_info['username']); ?></span>
                                                <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                            </button>
                                            <div id="userMenu" class="user-menu">
                                                <div class="px-4 py-3 border-b border-gray-100">
                                                    <div class="flex items-center space-x-3">
                                                        <img src="../img/icons/account.svg" class="w-10 h-10" alt="Account">
                                                        <div>
                                                            <p class="text-sm font-medium text-gray-800">
                                                                <?php echo htmlspecialchars($user_info['username']); ?>
                                                            </p>
                                                            <p class="text-xs text-gray-500">
                                                                <?php echo htmlspecialchars($user_info['email']); ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <a href="./my-account.php" class="user-menu-item"><i
                                                        class="fas fa-user"></i><span>Tài khoản của tôi</span></a>
                                                <a href="./my-account/orders.php" class="user-menu-item"><i
                                                        class="fas fa-shopping-bag"></i><span>Đơn hàng</span></a>
                                                <a href="./my-account/address-book.php" class="user-menu-item"><i
                                                        class="fas fa-map-marker-alt"></i><span>Sổ địa chỉ</span></a>
                                                <div class="user-menu-divider"></div>
                                                <a href="../control/logout.php" class="user-menu-item logout"><i
                                                        class="fas fa-sign-out-alt"></i><span>Đăng xuất</span></a>
                                            </div>
                                        <?php else: ?>
                                            <a href="./login.php"
                                                class="flex items-center text-gray-700 hover:text-red-600">
                                                <i class="far fa-user text-xl"></i>
                                                <span class="text-sm ml-1">Đăng nhập</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Cart -->
                                    <a href="./cart.php" class="relative p-2">
                                        <i class="fas fa-shopping-basket text-gray-700 hover:text-red-600 text-xl"></i>
                                        <span
                                            class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center transition-transform hover:scale-110">
                                            <?php echo $cart_count > 99 ? '99+' : $cart_count; ?>
                                        </span>
                                    </a>
                                </div>

                                <!-- Mobile Right Elements (chỉ hiện trên mobile) -->
                                <div class="md:hidden flex items-center space-x-3">
                                    <!-- <button id="searchToggleMobile" class="search-toggle p-1">
                                        <i class="fas fa-search text-xl text-gray-700"></i>
                                    </button> -->
                                    <?php if ($is_logged_in): ?>
                                        <a href="./my-account.php" class="p-1">
                                            <img src="../img/icons/account.svg" class="w-6 h-6" alt="Account">
                                        </a>
                                    <?php else: ?>
                                        <a href="./login.php" class="p-1">
                                            <i class="far fa-user text-xl text-gray-700"></i>
                                        </a>
                                    <?php endif; ?>

                                    <!-- Cart Mobile với badge động -->
                                    <a href="./cart.php" class="relative p-1">
                                        <i class="fas fa-shopping-basket text-xl"></i>
                                        <span
                                            class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">
                                            <?php echo $cart_count > 99 ? '99+' : $cart_count; ?>
                                        </span>
                                    </a>
                                </div>

                            </div>
                        </div>

                        <!-- ========== SEARCH HEADER (ẩn ban đầu, hiện khi bấm search) ========== -->
                        <div id="searchHeader" class="hidden items-center justify-center py-2">
                            <div class="w-full max-w-[800px] relative">
                                <input type="text" id="searchInput"
                                    class="w-full px-5 pr-14 py-3 text-base border-2 border-gray-200 rounded-full focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-600/20 transition-all bg-gray-50 focus:bg-white"
                                    placeholder="Tên sản phẩm, hãng..." value="" name="search" autocomplete="off">
                                <button type="submit"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center text-gray-500 hover:text-black transition-all rounded-full">
                                    <i class="fas fa-search text-lg"></i>
                                </button>
                                <button id="closeSearchBtn"
                                    class="absolute -right-12 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center bg-gray-200  justify-center text-gray-500 hover:text-black transition-all rounded-full">
                                    <i class="fas fa-times text-lg"></i>
                                </button>

                                <!-- Dropdown gợi ý tìm kiếm -->
                                <div id="searchSuggestions"
                                    class="absolute top-full left-0 right-0 mt-2 bg-white border border-gray-100 rounded-2xl shadow-xl overflow-hidden hidden z-50">
                                    <div id="suggestionsList" class="max-h-96 overflow-y-auto custom-scrollbar">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </header>
        <!-- Search Overlay -->
        <div id="searchOverlay"
            class="fixed inset-0 bg-black/50 opacity-0 pointer-events-none transition-opacity duration-300 z-30"></div>



        <main>
            <div class="container mx-auto px-4 py-6 md:py-8">
                <!-- Active Filters Tags -->
                <?php if (!empty($search_keyword) || isset($_GET['danhmuc']) || isset($_GET['thuonghieu']) || isset($_GET['min_price']) || isset($_GET['max_price'])): ?>
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-600">Bộ lọc đang áp dụng:</span>
                            <a href="shop.php" class="text-sm text-red-600 hover:text-red-700 font-medium"><i
                                    class="fas fa-times-circle mr-1"></i>Xóa tất cả</a>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <?php if (!empty($search_keyword)): ?>
                                <div
                                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-red-50 text-red-600 rounded-full text-sm font-medium">
                                    <span><i class="fas fa-search mr-1"></i>Tìm:
                                        "<?php echo htmlspecialchars($search_keyword); ?>"</span>
                                    <button onclick="removeFilter('search')"
                                        class="w-5 h-5 bg-red-600 text-white rounded-full flex items-center justify-center hover:bg-red-700 transition"><i
                                            class="fas fa-times text-xs"></i></button>
                                </div>
                            <?php endif; ?>
                            <?php if (isset($_GET['danhmuc']) && is_array($_GET['danhmuc'])): ?>
                                <?php foreach ($_GET['danhmuc'] as $slug): ?>
                                    <div
                                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-red-50 text-red-600 rounded-full text-sm font-medium">
                                        <span><i
                                                class="fas fa-folder mr-1"></i><?php echo htmlspecialchars(getFilterDisplayName('danhmuc', $slug)); ?></span>
                                        <button onclick="removeSingleFilter('danhmuc', '<?php echo $slug; ?>')"
                                            class="w-5 h-5 bg-red-600 text-white rounded-full flex items-center justify-center hover:bg-red-700 transition"><i
                                                class="fas fa-times text-xs"></i></button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if (isset($_GET['thuonghieu']) && is_array($_GET['thuonghieu'])): ?>
                                <?php foreach ($_GET['thuonghieu'] as $slug): ?>
                                    <div
                                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-red-50 text-red-600 rounded-full text-sm font-medium">
                                        <span><i
                                                class="fas fa-tag mr-1"></i><?php echo htmlspecialchars(getFilterDisplayName('thuonghieu', $slug)); ?></span>
                                        <button onclick="removeSingleFilter('thuonghieu', '<?php echo $slug; ?>')"
                                            class="w-5 h-5 bg-red-600 text-white rounded-full flex items-center justify-center hover:bg-red-700 transition"><i
                                                class="fas fa-times text-xs"></i></button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if (isset($_GET['min_price']) || isset($_GET['max_price'])): ?>
                                <div
                                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-red-50 text-red-600 rounded-full text-sm font-medium">
                                    <span><i class="fas fa-money-bill mr-1"></i>Giá: <?php echo formatPrice($min_price); ?> -
                                        <?php echo formatPrice($max_price); ?></span>
                                    <button onclick="removeFilter('price')"
                                        class="w-5 h-5 bg-red-600 text-white rounded-full flex items-center justify-center hover:bg-red-700 transition"><i
                                            class="fas fa-times text-xs"></i></button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Main Content: Sidebar + Product Grid -->
                <div class="flex flex-col lg:flex-row gap-6">
                    <!-- Sidebar Filter - Desktop ONLY -->
                    <div class="hidden lg:block lg:w-80 flex-shrink-0">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 sticky top-24"
                            id="desktop-sidebar">
                            <div class="p-5 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-bold text-lg flex items-center"><i
                                            class="fas fa-filter mr-2 text-red-600"></i>Bộ lọc tìm kiếm</h3>
                                    <button type="button" id="reset-desktop-filter"
                                        class="text-sm text-red-600 hover:text-red-700 font-medium transition"><i
                                            class="fas fa-undo mr-1"></i>Reset</button>
                                </div>
                            </div>
                            <div class="p-5 space-y-4 max-h-[calc(100vh-200px)] overflow-y-auto custom-scrollbar"
                                id="desktop-filter-content">
                                <!-- Tìm kiếm sản phẩm -->
                                <div class="border-b border-gray-100 pb-4">
                                    <h4 class="font-semibold text-gray-800 mb-3 flex items-center"><i
                                            class="fas fa-search text-red-600 mr-2"></i>Tìm kiếm sản phẩm</h4>
                                    <div class="relative">
                                        <input type="text" id="search-input"
                                            class="w-full pl-4 pr-10 py-2.5 border-2 border-gray-200 rounded-lg text-sm focus:border-red-500 focus:outline-none transition"
                                            placeholder="Nhập tên sản phẩm..."
                                            value="<?php echo htmlspecialchars($search_keyword); ?>">
                                        <?php if (!empty($search_keyword)): ?>
                                            <button type="button" onclick="clearSearch()"
                                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-600 transition p-1"><i
                                                    class="fas fa-times"></i></button>
                                        <?php endif; ?>
                                    </div>
                                    <!-- SQL Injection Warning -->
                                    <div id="searchSqlWarning" class="search-sql-warning" style="display: none;"><i
                                            class="fas fa-shield-alt"></i><span id="searchSqlMsg">Phát hiện ký tự không
                                            an toàn</span></div>
                                    <p class="text-xs text-gray-500 mt-2"><i class="fas fa-info-circle mr-1"></i>Nhấn
                                        Enter hoặc nút "Áp dụng bộ lọc" để tìm</p>
                                </div>

                                <!-- Danh mục -->
                                <div class="border-b border-gray-100 pb-4">
                                    <h4 class="font-semibold text-gray-800 mb-3 flex items-center"><i
                                            class="fas fa-folder text-red-600 mr-2"></i>Danh mục sản phẩm<span
                                            class="text-xs font-normal text-gray-500 ml-auto">(Chọn nhiều)</span></h4>
                                    <div class="filter-btn-grid">
                                        <?php $categories_result->data_seek(0);
                                        while ($category = $categories_result->fetch_assoc()): ?>
                                            <label class="filter-btn">
                                                <input type="checkbox" name="danhmuc[]"
                                                    value="<?php echo $category['slug']; ?>" <?php echo isActiveFilter('danhmuc', $category['slug']) ? 'checked' : ''; ?>>
                                                <div class="btn-content">
                                                    <div class="btn-label"><i
                                                            class="fas fa-circle text-[6px] <?php echo isActiveFilter('danhmuc', $category['slug']) ? 'text-red-600' : 'text-gray-300'; ?>"></i><span
                                                            class="text-sm"
                                                            title="<?php echo htmlspecialchars($category['Ten_danhmuc']); ?>"><?php echo htmlspecialchars($category['Ten_danhmuc']); ?></span>
                                                    </div>
                                                    <div class="flex items-center gap-1"><span
                                                            class="btn-count"><?php echo $category['product_count']; ?></span><i
                                                            class="fas fa-check-circle check-icon"></i></div>
                                                </div>
                                            </label>
                                        <?php endwhile; ?>
                                    </div>
                                </div>

                                <!-- Thương hiệu -->
                                <div class="border-b border-gray-100 pb-4">
                                    <h4 class="font-semibold text-gray-800 mb-3 flex items-center"><i
                                            class="fas fa-tag text-red-600 mr-2"></i>Thương hiệu<span
                                            class="text-xs font-normal text-gray-500 ml-auto">(Chọn nhiều)</span></h4>
                                    <div class="filter-btn-grid">
                                        <?php foreach ($brands_list as $brand): ?>
                                            <label class="filter-btn">
                                                <input type="checkbox" name="thuonghieu[]"
                                                    value="<?php echo $brand['slug']; ?>" <?php echo isActiveFilter('thuonghieu', $brand['slug']) ? 'checked' : ''; ?>>
                                                <div class="btn-content">
                                                    <div class="btn-label"><i
                                                            class="fas fa-circle text-[6px] <?php echo isActiveFilter('thuonghieu', $brand['slug']) ? 'text-red-600' : 'text-gray-300'; ?>"></i><span
                                                            class="text-sm"
                                                            title="<?php echo htmlspecialchars($brand['Ten_thuonghieu']); ?>"><?php echo htmlspecialchars($brand['Ten_thuonghieu']); ?></span>
                                                    </div>
                                                    <div class="flex items-center gap-1"><span
                                                            class="btn-count"><?php echo $brand['product_count']; ?></span><i
                                                            class="fas fa-check-circle check-icon"></i></div>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Khoảng giá -->
                                <div>
                                    <h4 class="font-semibold text-gray-800 mb-3 flex items-center"><i
                                            class="fas fa-money-bill-wave text-red-600 mr-2"></i>Khoảng giá</h4>
                                    <div class="flex items-center gap-3 mb-4">
                                        <div class="flex-1"><label
                                                class="text-xs text-gray-500 mb-1 block">Từ</label><input type="text"
                                                id="price-min"
                                                class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg text-right text-sm focus:border-red-500 focus:outline-none transition price-input"
                                                value="<?php echo number_format($min_price, 0, ',', '.'); ?>"
                                                placeholder="0₫" min="0"></div>
                                        <span class="text-gray-400 pt-5">-</span>
                                        <div class="flex-1"><label
                                                class="text-xs text-gray-500 mb-1 block">Đến</label><input type="text"
                                                id="price-max"
                                                class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg text-right text-sm focus:border-red-500 focus:outline-none transition price-input"
                                                value="<?php echo number_format($max_price, 0, ',', '.'); ?>"
                                                placeholder="50.000.000₫" min="0" max="50000000"></div>
                                    </div>
                                    <button type="button" id="apply-filter"
                                        class="w-full bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg text-sm font-semibold transition shadow-md shadow-red-200"><i
                                            class="fas fa-filter mr-2"></i>Áp dụng bộ lọc</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content - Product Grid -->
                    <div class="flex-1">
                        <!-- Header with sort and count -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div class="text-sm text-gray-600">Hiển thị <?php if ($total_products > 0): ?><span
                                            class="font-semibold text-gray-900"><?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $total_products); ?></span><?php else: ?><span
                                            class="font-semibold text-gray-900">0</span><?php endif; ?> trong <span
                                        class="font-semibold text-gray-900"><?php echo $total_products; ?></span> kết
                                    quả <?php if (!empty($search_keyword)): ?><span class="text-red-600"> cho
                                            "<?php echo htmlspecialchars($search_keyword); ?>"</span><?php endif; ?></div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-600 hidden sm:inline"><i
                                            class="fas fa-sort mr-1"></i>Sắp xếp:</span>
                                    <div class="sort-dropdown">
                                        <select id="sort-select" class="sort-select">
                                            <option value="newest" <?php echo (!isset($_GET['sort']) || $_GET['sort'] == 'newest') ? 'selected' : ''; ?>>Mới nhất</option>
                                            <option value="price_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price_asc') ? 'selected' : ''; ?>>Giá thấp nhất</option>
                                            <option value="price_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price_desc') ? 'selected' : ''; ?>>Giá cao nhất</option>
                                            <option value="name_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'name_asc') ? 'selected' : ''; ?>>Tên A-Z</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile Filter Button -->
                        <div class="lg:hidden mb-4">
                            <button id="open-mobile-filter-btn"
                                class="w-full bg-white border-2 border-gray-200 rounded-xl py-3 px-4 flex items-center justify-center gap-2 hover:border-red-500 hover:bg-red-50 transition shadow-sm">
                                <i class="fas fa-filter text-red-600"></i><span class="font-semibold">Bộ lọc sản
                                    phẩm</span>
                                <?php if (!empty($search_keyword) || isset($_GET['danhmuc']) || isset($_GET['thuonghieu']) || isset($_GET['min_price'])): ?>
                                    <span class="bg-red-600 text-white text-xs px-2 py-0.5 rounded-full ml-1">Active</span>
                                <?php endif; ?>
                            </button>
                        </div>

                        <!-- Product Grid -->
                        <?php if ($products->num_rows > 0): ?>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                <?php while ($product = $products->fetch_assoc()):
                                    $discount = calculateDiscount($product['GiaNhapTB'], $product['GiaBan']);
                                    ?>
                                    <div
                                        class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden group hover:shadow-lg transition duration-300">
                                        <a href="product.php?id=<?php echo $product['SanPham_id']; ?>">
                                            <div class="relative">
                                                <?php if ($product['SoLuongTon'] <= 0): ?>
                                                    <div class="absolute top-2 left-2 z-10"><span
                                                            class="bg-gray-500 text-white text-xs px-2 py-1 rounded-full font-semibold">Hết
                                                            hàng</span></div>
                                                <?php endif; ?>
                                                <div class="aspect-square bg-gray-100 flex items-center justify-center p-4">
                                                    <img src="../<?php echo htmlspecialchars($product['image_url']); ?>"
                                                        alt="<?php echo htmlspecialchars($product['TenSP']); ?>"
                                                        class="w-full h-full object-contain mix-blend-multiply group-hover:scale-110 transition-transform duration-300"
                                                        onerror="this.src='../img/sanpham/placeholder.png'">
                                                </div>
                                            </div>
                                            <div class="p-3">
                                                <h3
                                                    class="font-medium text-sm mb-2 line-clamp-2 h-10 hover:text-red-600 transition">
                                                    <?php echo htmlspecialchars($product['TenSP']); ?>
                                                </h3>
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span
                                                        class="text-red-600 font-bold"><?php echo formatPrice($product['GiaBan']); ?></span>
                                                    <?php if ($discount > 0): ?><span
                                                            class="text-gray-400 text-xs line-through"><?php echo formatPrice($product['GiaNhapTB']); ?></span><?php endif; ?>
                                                </div>
                                                <?php if ($product['SoLuongTon'] < 10 && $product['SoLuongTon'] > 0): ?>
                                                    <div class="text-xs text-orange-500 mt-1 font-semibold"><i
                                                            class="fas fa-exclamation-triangle mr-1"></i>Chỉ còn
                                                        <?php echo $product['SoLuongTon']; ?> sản phẩm
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    </div>
                                <?php endwhile; ?>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <div class="flex justify-center mt-8">
                                    <div class="flex items-center gap-1">
                                        <a href="<?php echo buildFilterUrl(['page' => $page - 1]); ?>"
                                            class="w-10 h-10 flex items-center justify-center rounded-lg border-2 border-gray-200 bg-white hover:bg-gray-50 hover:border-red-300 transition <?php echo ($page <= 1) ? 'pointer-events-none opacity-50' : ''; ?>"><i
                                                class="fas fa-chevron-left text-sm"></i></a>
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <?php if ($i == $page): ?>
                                                <span
                                                    class="w-10 h-10 flex items-center justify-center rounded-lg bg-red-600 text-white font-semibold shadow-md shadow-red-200"><?php echo $i; ?></span>
                                            <?php elseif ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                                <a href="<?php echo buildFilterUrl(['page' => $i]); ?>"
                                                    class="w-10 h-10 flex items-center justify-center rounded-lg border-2 border-gray-200 bg-white hover:bg-gray-50 hover:border-red-300 transition"><?php echo $i; ?></a>
                                            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                                <span class="w-10 h-10 flex items-center justify-center">...</span>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        <a href="<?php echo buildFilterUrl(['page' => $page + 1]); ?>"
                                            class="w-10 h-10 flex items-center justify-center rounded-lg border-2 border-gray-200 bg-white hover:bg-gray-50 hover:border-red-300 transition <?php echo ($page >= $total_pages) ? 'pointer-events-none opacity-50' : ''; ?>"><i
                                                class="fas fa-chevron-right text-sm"></i></a>
                                    </div>
                                </div>
                            <?php endif; ?>

                        <?php else: ?>
                            <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                                <i class="fas fa-box-open text-5xl text-gray-300 mb-4"></i>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Không tìm thấy sản phẩm</h3>
                                <p class="text-gray-500 mb-4"><?php if (!empty($search_keyword)): ?>Không có sản phẩm nào
                                        phù hợp với từ khóa
                                        "<?php echo htmlspecialchars($search_keyword); ?>".<?php else: ?>Không có sản phẩm nào
                                        phù hợp với bộ lọc của bạn.<?php endif; ?></p>
                                <a href="shop.php"
                                    class="inline-block px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-semibold shadow-md shadow-red-200"><i
                                        class="fas fa-times-circle mr-2"></i>Xóa bộ lọc</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>

        <!-- Mobile Menu -->
        <div id="main-menu"
            class="fixed inset-0 bg-white z-50 transform -translate-x-full transition duration-300 md:hidden overflow-y-auto">
            <div class="p-4">
                <div class="flex justify-between items-center mb-6">
                    <img src="../img/icons/logonvb.png" height="30" width="50"
                        class="relative-top-left transform scale-75">
                    <button class="close-menu p-2 hover:bg-gray-100 rounded-full transition"><i
                            class="fas fa-times text-2xl text-gray-600"></i></button>
                </div>
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                    <?php if ($is_logged_in): ?>
                        <div class="flex items-center text-gray-700">
                            <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                                <img src="../img/icons/account.svg" class="w-6 h-6" alt="Account">
                            </div>
                            <div>
                                <div class="font-medium"><?php echo htmlspecialchars($user_info['username']); ?></div>
                                <span
                                    class="text-sm text-gray-500"><?php echo htmlspecialchars($user_info['email']); ?></span>
                            </div>
                        </div>
                        <a href="../control/logout.php" class="text-red-600 text-sm font-medium">Đăng xuất</a>
                    <?php else: ?>
                        <a href="./login.php" class="flex items-center text-gray-700">
                            <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3"><i
                                    class="far fa-user text-xl text-gray-600"></i></div>
                            <div>
                                <div class="font-medium">Tài khoản</div><span class="text-sm text-gray-500">Đăng nhập / Đăng
                                    ký</span>
                            </div>
                        </a>
                    <?php endif; ?>
                </div>
                <!-- Mobile Menu Items - Danh mục chính -->
                <div class="mb-4">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-2">Danh mục</h3>

                    <!-- Cầu Lông -->
                    <div class="mb-2">
                        <button
                            class="w-full flex items-center justify-between p-3 bg-gray-50 rounded-lg category-toggle"
                            data-category="badminton">
                            <div class="flex items-center">
                                <div class="w-8 h-8 mr-3 flex-shrink-0">
                                    <img src="../img/icons/logo-caulong.png" alt="Cầu Lông" class="w-full h-full">
                                </div>
                                <span class="font-medium">Cầu Lông</span>
                            </div>
                            <i class="fas fa-chevron-down text-sm text-gray-500 transition-transform"></i>
                        </button>

                        <!-- Submenu Cầu Lông -->
                        <div class="pl-11 pr-3 mt-2 space-y-2 hidden category-submenu" id="submenu-badminton">
                            <!-- Vợt cầu lông -->
                            <div>
                                <a href="./shop.php?danhmuc[]=vot-cau-long"
                                    class="block py-2 text-gray-700 font-medium">Vợt
                                    cầu lông</a>
                                <div class="pl-4 mt-1 space-y-1">
                                    <a href="./shop.php?danhmuc[]=vot-cau-long&thuonghieu[]=yonex"
                                        class="block py-1 text-sm text-gray-600">Vợt Yonex</a>
                                    <a href="./shop.php?danhmuc[]=vot-cau-long&thuonghieu[]=li-ning"
                                        class="block py-1 text-sm text-gray-600">Vợt Li-Ning</a>
                                    <a href="./shop.php?danhmuc[]=vot-cau-long&thuonghieu[]=adidas"
                                        class="block py-1 text-sm text-gray-600">Vợt Adidas</a>
                                    <a href="./shop.php?danhmuc[]=vot-cau-long&thuonghieu[]=victor"
                                        class="block py-1 text-sm text-gray-600">Vợt Victor</a>
                                    <a href="./shop.php?danhmuc[]=vot-cau-long"
                                        class="block py-1 text-sm text-red-600">Xem
                                        thêm</a>
                                </div>
                            </div>

                            <!-- Áo cầu lông -->
                            <div>
                                <a href="./shop.php?danhmuc[]=ao-cau-long"
                                    class="block py-2 text-gray-700 font-medium">Áo
                                    cầu lông</a>
                                <div class="pl-4 mt-1 space-y-1">
                                    <a href="./shop.php?danhmuc[]=ao-cau-long&thuonghieu[]=yonex"
                                        class="block py-1 text-sm text-gray-600">Áo Yonex</a>
                                    <a href="./shop.php?danhmuc[]=ao-cau-long&thuonghieu[]=ds"
                                        class="block py-1 text-sm text-gray-600">Áo DS</a>
                                    <a href="./shop.php?danhmuc[]=ao-cau-long&thuonghieu[]=kamito"
                                        class="block py-1 text-sm text-gray-600">Áo Kamito</a>
                                    <a href="./shop.php?danhmuc[]=ao-cau-long"
                                        class="block py-1 text-sm text-red-600">Xem
                                        thêm</a>
                                </div>
                            </div>

                            <!-- Quần cầu lông -->
                            <div>
                                <a href="./shop.php?danhmuc[]=quan-cau-long"
                                    class="block py-2 text-gray-700 font-medium">Quần cầu lông</a>
                                <div class="pl-4 mt-1 space-y-1">
                                    <a href="./shop.php?danhmuc[]=quan-cau-long&thuonghieu[]=yonex"
                                        class="block py-1 text-sm text-gray-600">Quần Yonex</a>
                                    <a href="./shop.php?danhmuc[]=quan-cau-long&thuonghieu[]=kamito"
                                        class="block py-1 text-sm text-gray-600">Quần Kamito</a>
                                    <a href="./shop.php?danhmuc[]=quan-cau-long&thuonghieu[]=adidas"
                                        class="block py-1 text-sm text-gray-600">Quần Adidas</a>
                                </div>
                            </div>

                            <!-- Túi vợt -->
                            <div>
                                <a href="./shop.php?danhmuc[]=tui-vot-cau-long"
                                    class="block py-2 text-gray-700 font-medium">Túi vợt</a>
                            </div>

                            <!-- Balo -->
                            <div>
                                <a href="./shop.php?danhmuc[]=balo-cau-long"
                                    class="block py-2 text-gray-700 font-medium">Balo</a>
                            </div>

                            <!-- Phụ kiện -->
                            <div>
                                <a href="./shop.php?danhmuc[]=phu-kien" class="block py-2 text-gray-700 font-medium">Phụ
                                    kiện</a>
                                <div class="pl-4 mt-1 space-y-1">
                                    <a href="./shop.php?danhmuc[]=phu-kien&search=cước+đan"
                                        class="block py-1 text-sm text-gray-600">Cước đan vợt</a>
                                    <a href="./shop.php?danhmuc[]=phu-kien&search=quấn+cán"
                                        class="block py-1 text-sm text-gray-600">Quấn cán</a>
                                    <a href="./shop.php?danhmuc[]=phu-kien&search=quả+cầu"
                                        class="block py-1 text-sm text-gray-600">Quả cầu lông</a>
                                    <a href="./shop.php?danhmuc[]=phu-kien" class="block py-1 text-sm text-red-600">Xem
                                        thêm</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pickleball -->
                    <div class="mb-2">
                        <button
                            class="w-full flex items-center justify-between p-3 bg-gray-50 rounded-lg category-toggle"
                            data-category="pickleball">
                            <div class="flex items-center">
                                <div class="w-8 h-8 mr-3 flex-shrink-0">
                                    <img src="../img/icons/logo-pickleball.png" alt="Pickleball" class="w-full h-full">
                                </div>
                                <span class="font-medium">Pickleball</span>
                            </div>
                            <i class="fas fa-chevron-down text-sm text-gray-500 transition-transform"></i>
                        </button>

                        <div class="pl-11 pr-3 mt-2 space-y-2 hidden category-submenu" id="submenu-pickleball">
                            <div>
                                <a href="./shop.php?danhmuc[]=vot-pickleball"
                                    class="block py-2 text-gray-700 font-medium">Vợt Pickleball</a>
                                <div class="pl-4 mt-1 space-y-1">
                                    <a href="./shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=joola"
                                        class="block py-1 text-sm text-gray-600">Vợt Joola</a>
                                    <a href="./shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=selkirk"
                                        class="block py-1 text-sm text-gray-600">Vợt Selkirk</a>
                                    <a href="./shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=kamito"
                                        class="block py-1 text-sm text-gray-600">Vợt Kamito</a>
                                    <a href="./shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=wika"
                                        class="block py-1 text-sm text-gray-600">Vợt Wika</a>
                                    <a href="./shop.php?danhmuc[]=vot-pickleball"
                                        class="block py-1 text-sm text-red-600">Xem thêm</a>
                                </div>
                            </div>
                            <div>
                                <a href="./shop.php?danhmuc[]=phu-kien-pickleball"
                                    class="block py-2 text-gray-700 font-medium">Phụ kiện Pickleball</a>
                                <div class="pl-4 mt-1 space-y-1">
                                    <a href="./shop.php?danhmuc[]=phu-kien-pickleball&search=bóng"
                                        class="block py-1 text-sm text-gray-600">Bóng Pickleball</a>
                                    <a href="./shop.php?danhmuc[]=phu-kien-pickleball&search=lưới"
                                        class="block py-1 text-sm text-gray-600">Lưới Pickleball</a>
                                    <a href="./shop.php?danhmuc[]=phu-kien-pickleball"
                                        class="block py-1 text-sm text-red-600">Xem thêm</a>
                                </div>
                            </div>
                            <div>
                                <a href="./shop.php?danhmuc[]=balo-tui-pickleball"
                                    class="block py-2 text-gray-700 font-medium">Balo - Túi Pickleball</a>
                            </div>
                        </div>
                    </div>

                    <!-- Giày -->
                    <div class="mb-2">
                        <button
                            class="w-full flex items-center justify-between p-3 bg-gray-50 rounded-lg category-toggle"
                            data-category="giay">
                            <div class="flex items-center">
                                <div class="w-8 h-8 mr-3 flex-shrink-0">
                                    <img src="../img/icons/logo-giay.png" alt="Giày" class="w-full h-full">
                                </div>
                                <span class="font-medium">Giày</span>
                            </div>
                            <i class="fas fa-chevron-down text-sm text-gray-500 transition-transform"></i>
                        </button>

                        <div class="pl-11 pr-3 mt-2 space-y-2 hidden category-submenu" id="submenu-giay">
                            <div><a href="./shop.php?danhmuc[]=giay&thuonghieu[]=yonex"
                                    class="block py-2 text-gray-700">Giày Yonex</a></div>
                            <div><a href="./shop.php?danhmuc[]=giay&thuonghieu[]=adidas"
                                    class="block py-2 text-gray-700">Giày Adidas</a></div>
                            <div><a href="./shop.php?danhmuc[]=giay&thuonghieu[]=mizuno"
                                    class="block py-2 text-gray-700">Giày Mizuno</a></div>
                            <div><a href="./shop.php?danhmuc[]=giay&thuonghieu[]=asics"
                                    class="block py-2 text-gray-700">Giày Asics</a></div>
                            <div><a href="./shop.php?danhmuc[]=giay&thuonghieu[]=kamito"
                                    class="block py-2 text-gray-700">Giày Kamito</a></div>
                        </div>
                    </div>



                </div>

                <!-- Thông tin liên hệ Mobile -->
                <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-map-marker-alt text-red-600 w-5 mr-2"></i>
                        <span class="text-sm text-gray-600">62 Lê Bình, Tân An, Cần Thơ</span>
                    </div>
                    <div class="flex items-center mb-2">
                        <i class="fas fa-phone-alt text-red-600 w-5 mr-2"></i>
                        <a href="tel:0987.879.243" class="text-sm text-gray-600">0987.879.243</a>
                    </div>
                    <div class="flex items-center">
                        <i class="far fa-clock text-red-600 w-5 mr-2"></i>
                        <span class="text-sm text-gray-600">08:00 - 21:00</span>
                    </div>
                </div>
            </div>

            <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            console.log('🚀 DOM loaded – script started');

            // === SQL INJECTION PATTERNS FOR SEARCH ===
            const sqlPatterns = [
                /\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|EXEC|TRUNCATE)\b/i,
                /(--|\/\*|\*\/|#|;)/,
                /['"]\s*(OR|AND)\s*['"]?\d+['"]?\s*=\s*['"]?\d+['"]?/i,
                /\b(WAITFOR|BENCHMARK|SLEEP|xp_|sp_)\b/i,
                /%00|%27|%22|%3B/i
            ];

            function checkSQLInjectionSearch(value) {
                for (let pattern of sqlPatterns) {
                    if (pattern.test(value)) return true;
                }
                return false;
            }

            function showSearchSqlWarning(msg) {
                const warning = document.getElementById('searchSqlWarning');
                const msgEl = document.getElementById('searchSqlMsg');
                if (warning && msgEl) {
                    warning.style.display = 'block';
                    msgEl.textContent = msg;
                }
            }

            function hideSearchSqlWarning() {
                const warning = document.getElementById('searchSqlWarning');
                if (warning) warning.style.display = 'none';
            }

            // === USER DROPDOWN TOGGLE ===
            const userToggle = document.getElementById('userToggle');
            const userMenu = document.getElementById('userMenu');
            if (userToggle && userMenu) {
                userToggle.addEventListener('click', function (e) {
                    e.stopPropagation();
                    userMenu.classList.toggle('active');
                });
                document.addEventListener('click', function (e) {
                    if (!userToggle.contains(e.target) && !userMenu.contains(e.target)) {
                        userMenu.classList.remove('active');
                    }
                });
            }

            // === SEARCH INPUT SQL INJECTION CHECK ===
            const searchInput = document.getElementById('search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    if (checkSQLInjectionSearch(this.value)) {
                        showSearchSqlWarning('Từ khóa chứa ký tự không an toàn');
                    } else {
                        hideSearchSqlWarning();
                    }
                });
                searchInput.addEventListener('keypress', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (checkSQLInjectionSearch(this.value)) {
                            showSearchSqlWarning('Vui lòng nhập từ khóa hợp lệ');
                            return;
                        }
                        applyFilter();
                    }
                });
            }
            
            // === MOBILE FILTER DRAWER ===
            const mobileOpenBtn = document.getElementById('open-mobile-filter-btn');
            const mobileCloseBtn = document.getElementById('close-mobile-filter');
            const mobileDrawer = document.getElementById('mobile-filter-drawer');
            const mobileOverlay = document.getElementById('mobile-filter-overlay');
            const desktopFilterContent = document.getElementById('desktop-filter-content');
            const mobileFilterContent = document.getElementById('mobile-filter-content');

            function openMobileFilter() {
                if (desktopFilterContent && mobileFilterContent) {
                    mobileFilterContent.innerHTML = desktopFilterContent.innerHTML;
                    const mobilePriceInputs = mobileFilterContent.querySelectorAll('.price-input');
                    mobilePriceInputs.forEach(input => attachPriceInputValidation(input));
                }
                mobileDrawer.classList.remove('closed');
                mobileDrawer.classList.add('open');
                mobileOverlay.classList.remove('hidden');
                mobileOverlay.classList.add('visible');
                document.body.classList.add('filter-open');
            }

            function closeMobileFilter() {
                mobileDrawer.classList.remove('open');
                mobileDrawer.classList.add('closed');
                mobileOverlay.classList.remove('visible');
                mobileOverlay.classList.add('hidden');
                document.body.classList.remove('filter-open');
            }

            if (mobileOpenBtn) mobileOpenBtn.addEventListener('click', openMobileFilter);
            if (mobileCloseBtn) mobileCloseBtn.addEventListener('click', closeMobileFilter);
            if (mobileOverlay) mobileOverlay.addEventListener('click', closeMobileFilter);

            // === PRICE INPUT VALIDATION ===
            function attachPriceInputValidation(input) {
                input.addEventListener('keydown', function (e) {
                    if ([46, 8, 9, 27, 13, 37, 38, 39, 40].indexOf(e.keyCode) !== -1 ||
                        (e.keyCode === 65 && e.ctrlKey === true) || (e.keyCode === 67 && e.ctrlKey === true) ||
                        (e.keyCode === 86 && e.ctrlKey === true) || (e.keyCode === 88 && e.ctrlKey === true)) {
                        return;
                    }
                    if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                        e.preventDefault();
                    }
                });
                input.addEventListener('paste', function (e) {
                    const paste = (e.clipboardData || window.clipboardData).getData('text');
                    if (!/^\d*$/.test(paste)) e.preventDefault();
                });
                input.addEventListener('blur', function () {
                    let value = this.value.replace(/\./g, '');
                    if (value === '' || isNaN(value)) value = '0';
                    let numValue = parseInt(value);
                    if (numValue < 0) numValue = 0;
                    if (numValue > 50000000) numValue = 50000000;
                    this.value = numValue.toLocaleString('vi-VN');
                });
            }

            const desktopPriceInputs = document.querySelectorAll('.price-input');
            desktopPriceInputs.forEach(input => attachPriceInputValidation(input));

            // === APPLY FILTER FUNCTION ===
            function applyFilter() {
                console.log('🎯 applyFilter called');
                const params = new URLSearchParams();

                const searchInput = document.getElementById('search-input');
                if (searchInput && searchInput.value.trim() !== '') {
                    if (checkSQLInjectionSearch(searchInput.value.trim())) {
                        showSearchSqlWarning('Vui lòng nhập từ khóa hợp lệ');
                        return;
                    }
                    params.set('search', searchInput.value.trim());
                }

                const selectedCats = document.querySelectorAll('input[name="danhmuc[]"]:checked');
                selectedCats.forEach(cat => params.append('danhmuc[]', cat.value));

                const selectedBrands = document.querySelectorAll('input[name="thuonghieu[]"]:checked');
                selectedBrands.forEach(brand => params.append('thuonghieu[]', brand.value));

                const minPrice = document.getElementById('price-min');
                const maxPrice = document.getElementById('price-max');
                if (minPrice && maxPrice) {
                    const minVal = parseInt(minPrice.value.replace(/\./g, ''));
                    const maxVal = parseInt(maxPrice.value.replace(/\./g, ''));
                    if (minVal >= 0 && minVal <= 50000000) params.set('min_price', minVal);
                    if (maxVal >= 0 && maxVal <= 50000000) params.set('max_price', maxVal);
                }

                const sortSelect = document.getElementById('sort-select');
                if (sortSelect && sortSelect.value !== 'newest') params.set('sort', sortSelect.value);

                const newUrl = 'shop.php?' + params.toString();
                console.log('🔄 Redirecting to:', newUrl);
                window.location.href = newUrl;
            }

            // === RESET FILTER FUNCTION ===
            function resetFilter() {
                const searchInput = document.getElementById('search-input');
                if (searchInput) searchInput.value = '';
                const minPrice = document.getElementById('price-min');
                const maxPrice = document.getElementById('price-max');
                if (minPrice) minPrice.value = '0';
                if (maxPrice) maxPrice.value = '50.000.000';
                document.querySelectorAll('input[name="danhmuc[]"]').forEach(cb => cb.checked = false);
                document.querySelectorAll('input[name="thuonghieu[]"]').forEach(cb => cb.checked = false);
                window.location.href = 'shop.php';
            }

            // === GLOBAL FUNCTIONS ===
            window.clearSearch = function () {
                const searchInput = document.getElementById('search-input');
                if (searchInput) searchInput.value = '';
                applyFilter();
            };

            window.removeFilter = function (filterType) {
                const url = new URL(window.location.href);
                const params = new URLSearchParams(url.search);
                if (filterType === 'danhmuc') params.delete('danhmuc[]');
                else if (filterType === 'thuonghieu') params.delete('thuonghieu[]');
                else if (filterType === 'price') { params.delete('min_price'); params.delete('max_price'); }
                else if (filterType === 'search') params.delete('search');
                window.location.href = 'shop.php?' + params.toString();
            };

            window.removeSingleFilter = function (filterType, value) {
                const url = new URL(window.location.href);
                const params = new URLSearchParams(url.search);
                let values = params.getAll(filterType + '[]');
                values = values.filter(v => v !== value);
                params.delete(filterType + '[]');
                values.forEach(v => params.append(filterType + '[]', v));
                window.location.href = 'shop.php?' + params.toString();
            };

            // === EVENT LISTENERS ===
            const applyBtn = document.getElementById('apply-filter');
            if (applyBtn) applyBtn.addEventListener('click', function (e) { e.preventDefault(); applyFilter(); });

            const applyMobileBtn = document.getElementById('apply-mobile-filter');
            if (applyMobileBtn) applyMobileBtn.addEventListener('click', function (e) { e.preventDefault(); applyFilter(); closeMobileFilter(); });

            const resetBtn = document.getElementById('reset-desktop-filter');
            if (resetBtn) resetBtn.addEventListener('click', function (e) { e.preventDefault(); resetFilter(); });

            const resetMobileBtn = document.getElementById('reset-mobile-filter');
            if (resetMobileBtn) resetMobileBtn.addEventListener('click', function (e) { e.preventDefault(); resetFilter(); closeMobileFilter(); });

            const sortSelect = document.getElementById('sort-select');
            if (sortSelect) {
                sortSelect.addEventListener('change', function () {
                    const url = new URL(window.location.href);
                    const params = new URLSearchParams(url.search);
                    if (this.value !== 'newest') params.set('sort', this.value);
                    else params.delete('sort');
                    window.location.href = 'shop.php?' + params.toString();
                });
            }

            // Mobile menu toggle
            const menuToggle = document.querySelector('.menu-toggle');
            const closeMenu = document.querySelector('.close-menu');
            const mobileMenu = document.getElementById('main-menu');
            if (menuToggle) menuToggle.addEventListener('click', function () { mobileMenu.classList.remove('-translate-x-full'); document.body.style.overflow = 'hidden'; });
            if (closeMenu) closeMenu.addEventListener('click', function () { mobileMenu.classList.add('-translate-x-full'); document.body.style.overflow = ''; });

            console.log('✅ All event listeners registered');
        });
    </script>

    <!-- JavaScript Menu  -->
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



    </script>


</body>

</html>