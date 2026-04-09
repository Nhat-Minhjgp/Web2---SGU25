<?php
// view/product.php
session_start();
require_once '../control/connect.php';
require_once '../control/check_remember_login.php';

// === KIỂM TRA ĐĂNG NHẬP ===
$is_logged_in = isset($_SESSION['user_id']);
$user_info = null;
if ($is_logged_in) {
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


$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}




// Lấy ID sản phẩm từ URL
$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($product_id <= 0) {
    header('Location: shop.php');
    exit();
}

// Lấy thông tin sản phẩm
$sql = "SELECT s.*, d.Ten_danhmuc, d.slug as danhmuc_slug,
    th.Ten_thuonghieu, th.slug as thuonghieu_slug,
    ncc.Ten_NCC
    FROM sanpham s
    LEFT JOIN danhmuc d ON s.Danhmuc_id = d.Danhmuc_id
    LEFT JOIN thuonghieu th ON s.Ma_thuonghieu = th.Ma_thuonghieu
    LEFT JOIN nhacungcap ncc ON s.NCC_id = ncc.NCC_id
    WHERE s.SanPham_id = ? AND s.TrangThai = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: shop.php');
    exit();
}

// Lấy hình ảnh từ image_url trong bảng sanpham
$product_images = [
    ['DuongDan' => $product['image_url']]
];

// Lấy sản phẩm cùng danh mục (related products)
$related_sql = "SELECT s.*, d.slug as danhmuc_slug
    FROM sanpham s
    LEFT JOIN danhmuc d ON s.Danhmuc_id = d.Danhmuc_id
    WHERE s.Danhmuc_id = ?
    AND s.SanPham_id != ?
    AND s.TrangThai = 1
    ORDER BY RAND()
    LIMIT 5";
$related_stmt = $conn->prepare($related_sql);
$related_stmt->bind_param("ii", $product['Danhmuc_id'], $product_id);
$related_stmt->execute();
$related_products = $related_stmt->get_result();

// Format giá
function formatPrice($price)
{
    return number_format($price, 0, ',', '.') . ' ₫';
}

$is_in_stock = $product['SoLuongTon'] > 0;
$max_qty = $is_in_stock ? $product['SoLuongTon'] : 0;
$disabled_attr = $is_in_stock ? '' : 'disabled';
$btn_class = $is_in_stock
    ? 'bg-red-600 hover:bg-red-700 cursor-pointer'
    : 'bg-gray-400 cursor-not-allowed';

// Tính phần trăm giảm giá
function calculateDiscount($import_price, $sell_price)
{
    if ($import_price > $sell_price && $import_price > 0) {
        return round(($import_price - $sell_price) / $import_price * 100);
    }
    return 0;
}

$discount = calculateDiscount($product['GiaNhapTB'], $product['GiaBan']);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['TenSP']); ?> - NVBPlay</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
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

        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* === SEARCH === */
        #searchHeader {
            display: none;
        }

        body.search-active #defaultHeader {
            display: none;
        }

        body.search-active #searchHeader {
            display: flex;
        }

        body.search-active #searchOverlay {
            opacity: 1;
            pointer-events: auto;
        }

        #searchSuggestions {
            position: absolute;
            left: 0;
            right: 0;
            top: 100%;
            margin-top: 8px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            border: 1px solid #f3f4f6;
            overflow-y: auto;
            max-height: 400px;
            z-index: 50;
            display: none;
            animation: slideDown 0.2s ease;
        }

        #searchSuggestions.active {
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

        .suggestion-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.2s;
            text-decoration: none;
            color: inherit;
        }

        .suggestion-item:hover {
            background: #f9fafb;
        }

        .suggestion-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            background: #f3f4f6;
            flex-shrink: 0;
        }

        .suggestion-info {
            flex: 1;
            min-width: 0;
        }

        .suggestion-info h4 {
            font-size: 14px;
            font-weight: 500;
            color: #1f2937;
            margin: 0 0 4px 0;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .price-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .price-wrapper .price {
            font-size: 15px;
            font-weight: 600;
            color: #dc2626;
        }

        .price-wrapper .old-price {
            font-size: 13px;
            color: #9ca3af;
            text-decoration: line-through;
        }

        .price-wrapper .discount-badge {
            font-size: 11px;
            font-weight: 600;
            color: #dc2626;
            background: #fef2f2;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .view-all-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px;
            background: #f9fafb;
            color: #dc2626;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            border-top: 1px solid #f3f4f6;
        }

        .view-all-link:hover {
            background: #f3f4f6;
        }

        .no-results {
            padding: 32px 24px;
            text-align: center;
            color: #6b7280;
        }

        button:disabled {
            opacity: 0.7;
            pointer-events: none;
            user-select: none;
        }

        button:disabled:hover {
            transform: none !important;
            box-shadow: none !important;
        }
         /* === GUEST USER DROPDOWN === */
        .guest-user-menu {
            min-width: 100px;
        }

        .guest-user-menu .user-menu-item {
            justify-content: left;
            font-weight: 500;
        }

        .guest-user-menu .user-menu-item i {
            width: 20px;
            text-align: center;
        }
    </style>
    <link rel="icon" type="image/svg+xml" href="../img/icons/favicon.png" sizes="32x32">
</head>

<body class="font-sans antialiased bg-gray-50">
    <!-- Popup Overlay -->
    <div id="popup_overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50"></div>
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
                                                                                        onerror="this.src='../img/icons/placeholder-brand.svg'">
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
                                                                                        onerror="this.src='../img/icons/placeholder-brand.svg'">
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
                                                                                        onerror="this.src='../img/icons/placeholder-brand.svg'">
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
                                                                                        onerror="this.src='../img/icons/placeholder-brand.svg'">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">VICTOR</span>
                                                                            </a>

                                                                            <!-- KAMITO -->
                                                                            <a href=".shop.php?thuonghieu[]=kamito"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../img/icons/logo-kamito.png"
                                                                                        alt="KAMITO"
                                                                                        class="w-full h-full object-contain"
                                                                                        onerror="this.src='../img/icons/placeholder-brand.svg'">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">KAMITO</span>
                                                                            </a>

                                                                            <!-- MIZUNO -->
                                                                            <a href=".shop.php?thuonghieu[]=mizuno"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../img/icons/logo-mizuno.png"
                                                                                        alt="Mizuno"
                                                                                        class="w-full h-full object-contain"
                                                                                        onerror="this.src='../img/icons/placeholder-brand.svg'">
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
                                                                                        onerror="this.src='../img/icons/placeholder-brand.svg'">
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
                                                                                        onerror="this.src='../img/icons/placeholder-brand.svg'">
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
                                                                                <a href="./shop.php?danhmuc[]=ba-l"
                                                                                    class="font-semibold text-sm hover:text-red-600">Balo
                                                                                    cầu lông</a>
                                                                                <ul class="mt-2 space-y-1">
                                                                                    <li><a href="./shop.php?danhmuc[]=ba-l&thuonghieu[]=yonex"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Balo
                                                                                            Yonex</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=ba-l&thuonghieu[]=li-ning"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Balo
                                                                                            Li-Ning</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=ba-l&thuonghieu[]=adidas"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Balo
                                                                                            Adidas</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=ba-l&thuonghieu[]=victor"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Balo
                                                                                            Victor</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=ba-l"
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
                                                                                        onerror="this.src='../img/icons/placeholder-brand.svg'">
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
                                                                                        onerror="this.src='../img/icons/placeholder-brand.svg'">
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
                                                                                        onerror="this.src='../img/icons/placeholder-brand.svg'">
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
                                                                                        onerror="this.src='../img/icons/placeholder-brand.svg'">
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
                                                                                <a href="./shop.php?danhmuc[]=ba-l"
                                                                                    class="font-semibold text-sm hover:text-red-600">Balo
                                                                                    - Túi Pickleball</a>
                                                                                <ul class="mt-2 space-y-1">
                                                                                    <li><a href="./shop.php?danhmuc[]=ba-l&thuonghieu[]=joola"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Balo
                                                                                            Joola</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=ba-l&thuonghieu[]=selkirk"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Balo
                                                                                            Selkirk</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=ba-l&thuonghieu[]=kamito"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Balo
                                                                                            Kamito</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=ba-l&thuonghieu[]=wika"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Balo
                                                                                            Wika</a></li>
                                                                                    <li><a href="./shop.php?danhmuc[]=ba-l"
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
                                        <a href="./my-account/address-book.php"
                                            class="flex items-center text-gray-700 hover:text-red-600">
                                            <i class="fas fa-map-marker-alt mr-1"></i>
                                            <span class="shipping-address text-sm"><span class="text">Chọn địa
                                                    chỉ</span></span>
                                        </a>
                                    </div>
                                    <div class="h-5 w-px bg-gray-300"></div>


                                    <!-- Search button -->
                                    <button id="searchToggle"
                                        class="search-toggle p-2 text-gray-700 hover:text-red-600">
                                        <i class="fas fa-search text-xl"></i>
                                    </button>

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
                                            <!-- Guest User Dropdown -->
                                            <button id="guestUserToggle"
                                                class="flex items-center space-x-2 hover:bg-gray-100 px-3 py-2 rounded-lg transition"
                                                type="button">
                                                <img src="../img/icons/account.svg" class="w-6 h-6" alt="Account">
                                                <span class="text-sm font-medium text-gray-700 hidden sm:inline">Tài
                                                    khoản</span>
                                                <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                            </button>
                                            <div id="guestUserMenu" class="user-menu guest-user-menu">
                                                <a href="./login.php" class="user-menu-item">
                                                    <i class="fas fa-sign-in-alt p-1"></i>
                                                    <span>Đăng nhập</span>
                                                </a>
                                                <div class="user-menu-divider"></div>
                                                <a href="./register.php" class="user-menu-item">
                                                    <i class="fas fa-user-plus p-1"></i>
                                                    <span>Đăng ký</span>
                                                </a>
                                            </div>
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
                                    <button id="searchToggleMobile" class="search-toggle p-1">
                                        <i class="fas fa-search text-xl text-gray-700"></i>
                                    </button>
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
                                <div id="searchSuggestions" class="absolute top-full left-0 right-0 z-50">
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



        <!-- Main Content -->
        <main class="flex-grow bg-gray-50 py-8 md:p-[30px]">
            <div class="container mx-auto px-4">
                <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 mb-12">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
                        <!-- Left: Product Gallery -->
                        <div class="space-y-4">
                            <!-- Main Image -->
                            <div class="relative group overflow-hidden rounded-lg bg-gray-100 aspect-square">
                                <img src="../<?php echo htmlspecialchars($product_images[0]['DuongDan']); ?>"
                                    alt="<?php echo htmlspecialchars($product['TenSP']); ?>"
                                    class="w-full h-full object-contain mix-blend-multiply group-hover:scale-105 transition-transform duration-300"
                                    id="main-product-image">
                            </div>
                            <!-- Thumbnails - Chỉ hiển thị nếu có nhiều ảnh -->
                            <?php if (count($product_images) > 1): ?>
                                <div class="grid grid-cols-4 gap-2">
                                    <?php foreach ($product_images as $index => $image): ?>
                                        <button
                                            class="border-2 <?php echo $index === 0 ? 'border-red-600' : 'border-gray-200'; ?> rounded overflow-hidden hover:border-red-600 thumbnail-btn"
                                            onclick="changeImage('<?php echo htmlspecialchars($image['DuongDan']); ?>', this)">
                                            <img src="<?php echo htmlspecialchars($image['DuongDan']); ?>"
                                                class="w-full h-auto">
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <!-- Right: Product Info -->
                        <div class="flex flex-col">
                            <!-- Title -->
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mt-4 mb-4">
                                <?php echo htmlspecialchars($product['TenSP']); ?>
                            </h1>
                            <!-- Brand & Category -->
                            <div class="flex items-center gap-4 mb-4 text-sm text-gray-600">
                                <?php if ($product['Ten_thuonghieu']): ?>
                                    <div class="flex items-center">
                                        <i class="fas fa-tag mr-2 text-red-600"></i>
                                        <span>Thương hiệu:
                                            <strong><?php echo htmlspecialchars($product['Ten_thuonghieu']); ?></strong></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($product['Ten_NCC']): ?>
                                    <div class="flex items-center">
                                        <i class="fas fa-truck mr-2 text-red-600"></i>
                                        <span>NCC:
                                            <?php echo htmlspecialchars($product['Ten_NCC']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <!-- Product Description -->
                            <?php if (!empty($product['MoTa'])): ?>
                                <div class="text-gray-600 mb-6 mt-6 rounded-lg">
                                    <h4 class="font-bold text-gray-900 mb-2">Mô tả sản phẩm:</h4>
                                    <div class="text-sm leading-relaxed prose prose-sm max-w-none">
                                        <?php echo htmlspecialchars($product['MoTa']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <!-- Short Description -->
                            <div class="text-gray-600 mb-6 mt-6 space-y-4">
                                <h4 class="font-bold text-gray-900">Chính sách:</h4>
                                <ul class="list-disc pl-5 space-y-1 text-sm">
                                    <li>Không bảo hành 2 năm </li>
                                    <li>Không bảo hành lưới đứt</li>
                                    <li>Không đổi trả</li>
                                    <li>Không đi kèm/bảo hành túi vợt</li>
                                </ul>
                            </div>
                            <!-- Price -->
                            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-end gap-3 mb-2">
                                    <span
                                        class="text-3xl font-bold text-red-600"><?php echo formatPrice($product['GiaBan']); ?></span>
                                    <?php if ($discount > 0): ?>
                                        <span
                                            class="text-lg text-gray-400 line-through mb-1"><?php echo formatPrice($product['GiaNhapTB']); ?></span>
                                        <span
                                            class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">-<?php echo $discount; ?>%</span>
                                    <?php endif; ?>
                                </div>
                                <div
                                    class="text-sm <?php echo $product['SoLuongTon'] > 0 ? 'text-green-600' : 'text-red-600'; ?> mt-2 flex items-center">
                                    <i
                                        class="fas fa-<?php echo $product['SoLuongTon'] > 0 ? 'check-circle' : 'times-circle'; ?> mr-1"></i>
                                    <?php if ($product['SoLuongTon'] > 0): ?>
                                        Còn hàng (<?php echo $product['SoLuongTon']; ?> sản phẩm)
                                    <?php else: ?>
                                        Hết hàng
                                    <?php endif; ?>
                                </div>
                                <?php if ($product['SoLuongTon'] < 10 && $product['SoLuongTon'] > 0): ?>
                                    <div class="text-xs text-orange-500 mt-1">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>Chỉ còn
                                        <?php echo $product['SoLuongTon']; ?> sản phẩm
                                    </div>
                                <?php endif; ?>
                            </div>
                            <!--  FORM ĐÃ THÊM ID="addToCartForm" -->
                            <form id="addToCartForm" class="mt-auto space-y-4" method="POST" action="cart.php">
                                <input type="hidden" name="product_id" value="<?php echo $product['SanPham_id']; ?>">

                                <!-- Quantity -->
                                <div class="flex items-center gap-4">
                                    <span class="font-medium text-gray-700">Số lượng:</span>
                                    <div
                                        class="flex items-center border border-gray-300 rounded <?php echo !$is_in_stock ? 'bg-gray-100' : ''; ?>">
                                        <button type="button" onclick="decreaseQty()"
                                            class="px-3 py-2 hover:bg-gray-100 text-gray-600 <?php echo !$is_in_stock ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                            <?php echo $disabled_attr; ?>>-</button>
                                        <input type="number" name="quantity" id="quantity" value="1" min="1"
                                            max="<?php echo $max_qty; ?>"
                                            class="w-16 text-center border-none focus:ring-0 p-0 <?php echo !$is_in_stock ? 'bg-gray-100 text-gray-500' : ''; ?>"
                                            readonly <?php echo $disabled_attr; ?>>
                                        <button type="button" onclick="increaseQty()"
                                            class="px-3 py-2 hover:bg-gray-100 text-gray-600 <?php echo !$is_in_stock ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                            <?php echo $disabled_attr; ?>>+</button>
                                    </div>
                                </div>
                                <!-- Buttons -->
                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Thêm vào giỏ -->
                                    <button type="submit" name="add_to_cart" value="1"
                                        class="w-full text-white py-3 rounded-lg font-semibold transition flex items-center justify-center <?php echo $btn_class; ?>"
                                        <?php echo $disabled_attr; ?> <?php if (!$is_in_stock): ?>title="Sản phẩm hiện đã hết hàng" <?php endif; ?>>
                                        <i class="fas fa-shopping-cart mr-2"></i>Thêm vào giỏ
                                    </button>
                                    <!-- Mua ngay (ĐÃ CẬP NHẬT) -->
                                    <button type="button" id="buyNowBtn"
                                        class="w-full text-white font-bold py-3 rounded-xl transition shadow-lg <?php echo $is_in_stock ? 'shadow-red-200 hover:bg-red-700' : 'shadow-gray-200'; ?> <?php echo $btn_class; ?>"
                                        <?php echo $disabled_attr; ?> <?php if (!$is_in_stock): ?>title="Sản phẩm hiện đã hết hàng" <?php endif; ?> onclick="buyNow()">
                                        Mua ngay
                                    </button>
                                </div>
                                <!-- Thông báo khi hết hàng -->
                                <?php if (!$is_in_stock): ?>
                                    <p class="text-center text-sm text-red-600 font-medium">
                                        <i class="fas fa-info-circle mr-1"></i>Sản phẩm tạm thời hết
                                        hàng. Vui lòng quay lại
                                        sau!
                                    </p>
                                <?php endif; ?>
                            </form>
                            <!-- Additional Info -->
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500">Mã sản phẩm:</span>
                                        <span
                                            class="ml-2 font-medium"><?php echo htmlspecialchars($product['SanPham_id']); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Danh mục:</span>
                                        <span
                                            class="ml-2 font-medium"><?php echo htmlspecialchars($product['Ten_danhmuc'] ?? 'N/A'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Related Products -->
                <?php if ($related_products->num_rows > 0): ?>
                    <div class="mb-12">
                        <h3 class="text-xl font-bold text-gray-900 mb-6 uppercase">Sản phẩm tương tự
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                            <?php while ($related = $related_products->fetch_assoc()):
                                $related_discount = calculateDiscount($related['GiaNhapTB'], $related['GiaBan']);
                                ?>
                                <!-- Product Item -->
                                <div class="group">
                                    <div class="bg-white rounded-lg overflow-hidden shadow-sm mb-2 relative aspect-square">
                                        <?php if ($related_discount > 0): ?>
                                            <div class="absolute top-2 right-2 z-10">
                                                <span
                                                    class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">-<?php echo $related_discount; ?>%</span>
                                            </div>
                                        <?php endif; ?>
                                        <a href="product.php?id=<?php echo $related['SanPham_id']; ?>"
                                            class="hover:text-red-600">
                                            <img src="../<?php echo htmlspecialchars($related['image_url']); ?>" class="w-full h-full object-contain mix-blend-multiply group-hover:scale-105
                            transition-transform">
                                        </a>
                                    </div>
                                    <h4 class="text-sm font-medium text-gray-900 line-clamp-2 h-10 mb-1">
                                        <a href="product.php?id=<?php echo $related['SanPham_id']; ?>"
                                            class="hover:text-red-600">
                                            <?php echo htmlspecialchars($related['TenSP']); ?>
                                        </a>
                                    </h4>
                                    <div class="flex items-center gap-2">
                                        <div class="text-red-600 font-bold">
                                            <?php echo formatPrice($related['GiaBan']); ?>
                                        </div>
                                        <?php if ($related_discount > 0): ?>
                                            <div class="text-gray-400 text-xs line-through">
                                                <?php echo formatPrice($related['GiaNhapTB']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>


          <!-- Footer -->
        <footer id="footer" class="bg-black text-white">
            <div class="container mx-auto px-4 py-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="pl-5">
                        <h3 class="text-4xl font-bold mb-4">Boost<br>your power</h3>
                        <div class="flex space-x-3 mb-4">
                           <P href="" target="_blank"
                                class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition"><i
                                    class="fab fa-facebook-f"></i></P>
                            <p href="" target="_blank"
                                class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition"><i
                                    class="fab fa-tiktok"></i></p>
                            <p href="" target="_blank"
                                class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition"><i
                                    class="fas fa-shopping-bag"></i></p>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold mb-4">Thông tin khác</h3>
                        <ul class="space-y-2">
                            <li><p href="" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH BẢO MẬT</p></li>
                            <li><p href="" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH THANH TOÁN</p>
                            </li>
                            <li><p href="" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH BẢO HÀNH ĐỔI
                                    TRẢ</p></li>
                            <li><p href="" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH VẬN CHUYỂN</p>
                            </li>
                            <li><p href="" class="text-gray-400 hover:text-white transition">THOẢ THUẬN SỬ DỤNG</p></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold mb-4">Về chúng tôi</h3>
                        <ul class="space-y-3">
                            <li><p href="" target="_blank" class="flex"><span class="font-medium w-20">Địa
                                        chỉ:</span><span class="text-gray-400">62 Lê Bình,
                                        Tân An, Cần Thơ</span></p></li>
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
                    <span href="" target="_blank"><img src="../img/icons/logoBCT.png" alt="Bộ Công Thương"
                            class="h-12 w-auto"></span>
                </div>
            </div>
        </footer>



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
                                <a href="./shop.php?danhmuc[]=ba-l"
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
                                <a href="./shop.php?danhmuc[]=ba-l"
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



                <?php require_once '../control/chatbot.php'; ?>
            </div>
            <!-- JavaScript -->
            <script>
                // Change product image
                function changeImage(src, btn) {
                    document.getElementById('main-product-image').src = src;
                    document.querySelectorAll('.thumbnail-btn').forEach(b => {
                        b.classList.remove('border-red-600');
                        b.classList.add('border-gray-200');
                    });
                    btn.classList.remove('border-gray-200');
                    btn.classList.add('border-red-600');
                }

                // Quantity controls
                function decreaseQty() {
                    const qtyInput = document.getElementById('quantity');
                    if (qtyInput.disabled) return;
                    let currentValue = parseInt(qtyInput.value);
                    if (currentValue > 1) {
                        qtyInput.value = currentValue - 1;
                    }
                }

                function increaseQty() {
                    const qtyInput = document.getElementById('quantity');
                    if (qtyInput.disabled) return;
                    const maxQty = parseInt(qtyInput.max);
                    let currentValue = parseInt(qtyInput.value);
                    if (currentValue < maxQty && maxQty > 0) {
                        qtyInput.value = currentValue + 1;
                    }
                }

                function buyNow() {

                    const form = document.getElementById('addToCartForm');
                    if (!form) {
                        alert('Lỗi: Không tìm thấy form!');
                        return;
                    }

                    const formData = new FormData(form);
                    formData.append('buy_now', '1');

                    const buyBtn = document.getElementById('buyNowBtn');
                    const originalText = buyBtn.innerHTML;
                    buyBtn.disabled = true;
                    buyBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang xử lý...';


                    fetch('cart.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'include'
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success && data.redirect) {
                                const redirectUrl = new URL(data.redirect, window.location.href);
                                redirectUrl.searchParams.set('t', Date.now());
                                window.location.href = redirectUrl.toString();
                            } else {
                                throw new Error(data.message || 'Lỗi không xác định');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            alert('Có lỗi: ' + err.message);
                            buyBtn.disabled = false;
                            buyBtn.innerHTML = originalText;
                        });
                }


                //  SHOW TOAST WHEN ADDED TO CART - REDIRECT VỀ CART.PHP
                window.addEventListener('DOMContentLoaded', function () {
                    const urlParams = new URLSearchParams(window.location.search);

                    if (urlParams.get('added') === '1') {
                        const toast = document.createElement('div');
                        toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in';
                        toast.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Đã thêm vào giỏ hàng!';
                        document.body.appendChild(toast);
                        setTimeout(() => toast.remove(), 3000);
                        window.history.replaceState({}, document.title, window.location.pathname);
                    }

                    if (urlParams.get('error') === '1') {
                        const toast = document.createElement('div');
                        toast.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
                        toast.innerHTML = '<i class="fas fa-times-circle mr-2"></i>Có lỗi xảy ra!';
                        document.body.appendChild(toast);
                        setTimeout(() => toast.remove(), 3000);
                        window.history.replaceState({}, document.title, window.location.pathname);
                    }
                });



                // Validate add to cart
                function validateAddToCart() {
                    const qtyInput = document.getElementById('quantity');
                    if (qtyInput && qtyInput.disabled) {
                        alert('Sản phẩm hiện đã hết hàng!');
                        return false;
                    }
                    const qty = parseInt(qtyInput?.value || 1);
                    if (qty < 1 || isNaN(qty)) {
                        alert('Số lượng không hợp lệ!');
                        return false;
                    }
                    return true;
                }




            </script>


            <script>
                document.addEventListener('DOMContentLoaded', function () {

                      // ========== USER DROPDOWN TOGGLE ==========
                    const userToggle = document.getElementById('userToggle');
                    const userMenu = document.getElementById('userMenu');
                    const guestUserToggle = document.getElementById('guestUserToggle');
                    const guestUserMenu = document.getElementById('guestUserMenu');

                    // Dropdown cho user đã đăng nhập (click để toggle)
                    if (userToggle && userMenu) {
                        userToggle.addEventListener('click', function (e) {
                            e.stopPropagation();
                            userMenu.classList.toggle('active');
                            // Đóng guest menu nếu đang mở
                            if (guestUserMenu) guestUserMenu.classList.remove('active');
                        });
                    }

                    // Dropdown cho guest user (hover để hiện, click để đóng)
                    if (guestUserToggle && guestUserMenu) {
                        let guestMenuTimeout;

                        // Hiển thị dropdown khi hover
                        guestUserToggle.addEventListener('mouseenter', function () {
                            clearTimeout(guestMenuTimeout);
                            guestUserMenu.classList.add('active');
                        });

                        // Ẩn dropdown khi rời khỏi button (có delay để tránh flicker)
                        guestUserToggle.addEventListener('mouseleave', function () {
                            guestMenuTimeout = setTimeout(() => {
                                guestUserMenu.classList.remove('active');
                            }, 200);
                        });

                        // Giữ dropdown mở khi hover vào menu
                        guestUserMenu.addEventListener('mouseenter', function () {
                            clearTimeout(guestMenuTimeout);
                        });

                        // Ẩn dropdown khi rời khỏi menu
                        guestUserMenu.addEventListener('mouseleave', function () {
                            guestUserMenu.classList.remove('active');
                        });

                        // Đóng khi click ra ngoài
                        guestUserToggle.addEventListener('click', function (e) {
                            e.stopPropagation();
                        });
                    }

                    // Đóng tất cả dropdown khi click ra ngoài
                    document.addEventListener('click', function (e) {
                        // Đóng user menu
                        if (userMenu && !userToggle?.contains(e.target) && !userMenu.contains(e.target)) {
                            userMenu.classList.remove('active');
                        }
                        // Đóng guest user menu
                        if (guestUserMenu && !guestUserToggle?.contains(e.target) && !guestUserMenu.contains(e.target)) {
                            guestUserMenu.classList.remove('active');
                        }
                    });

                    // === MEGA MENU ===
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

                    // === MOBILE MENU TOGGLE ===
                    const menuToggle = document.querySelector('.menu-toggle');
                    const closeMenu = document.querySelector('.close-menu');
                    const mobileMenu = document.getElementById('main-menu');

                    if (menuToggle) {
                        menuToggle.addEventListener('click', function () {
                            mobileMenu.classList.remove('-translate-x-full');
                            document.body.style.overflow = 'hidden';
                        });
                    }
                    if (closeMenu) {
                        closeMenu.addEventListener('click', function () {
                            mobileMenu.classList.add('-translate-x-full');
                            document.body.style.overflow = '';
                        });
                    }

                    // ========== SEARCH FUNCTIONALITY (ĐÃ SỬA PATH) ==========
                    const searchToggle = document.getElementById('searchToggle');
                    const searchToggleMobile = document.getElementById('searchToggleMobile');
                    const closeSearchBtn = document.getElementById('closeSearchBtn');
                    const searchOverlay = document.getElementById('searchOverlay');
                    const searchInput = document.getElementById('searchInput');
                    const suggestionsContainer = document.getElementById('searchSuggestions');
                    const suggestionsList = document.getElementById('suggestionsList');

                    function debounce(func, delay) {
                        let timeoutId;
                        return function (...args) {
                            clearTimeout(timeoutId);
                            timeoutId = setTimeout(() => func.apply(this, args), delay);
                        };
                    }

                    async function fetchSearchSuggestions(query) {
                        if (!query || query.length < 1) {
                            suggestionsContainer?.classList.remove('active');
                            return;
                        }
                        try {

                            const response = await fetch(`../control/search-suggest.php?q=${encodeURIComponent(query)}`);
                            const result = await response.json();
                            if (result.success && result.data.length > 0) {
                                const limitedResults = result.data.slice(0, 8);
                                suggestionsList.innerHTML = limitedResults.map(product => `
            <a href="${product.url}" class="suggestion-item">
           
                <img src="../${product.image}" alt="${product.name}" loading="lazy"
                     onerror="this.src='../img/sanpham/placeholder.png'">
                <div class="suggestion-info">
                    <h4>${product.name}</h4>
                    <div class="price-wrapper">
                        <span class="price">${product.price}</span>
                        ${product.old_price ? `<span class="old-price">${product.old_price}</span>` : ''}
                        ${product.discount > 0 ? `<span class="discount-badge">-${product.discount}%</span>` : ''}
                    </div>
                </div>
            </a>`).join('');
                                if (result.count > 8) {

                                    suggestionsList.innerHTML += `
                <a href="./shop.php?search=${encodeURIComponent(query)}" class="view-all-link">
                    <i class="fas fa-search"></i> Xem tất cả ${result.count} kết quả
                </a>`;
                                }
                                suggestionsContainer.classList.add('active');
                            } else {
                                suggestionsList.innerHTML = `<div class="no-results"><p>Không tìm thấy sản phẩm</p></div>`;
                                suggestionsContainer.classList.add('active');
                            }
                        } catch (error) {
                            console.error('Lỗi tìm kiếm:', error);
                        }
                    }

                    const debouncedSearch = debounce(fetchSearchSuggestions, 300);

                    function enableSearch() {
                        document.body.classList.add('search-active');
                        const defaultHeader = document.getElementById('defaultHeader');
                        const searchHeader = document.getElementById('searchHeader');
                        if (defaultHeader) defaultHeader.classList.add('hidden');
                        if (searchHeader) {
                            searchHeader.classList.remove('hidden');
                            searchHeader.classList.add('flex');
                        }
                        const searchOverlay = document.getElementById('searchOverlay');
                        if (searchOverlay) {
                            searchOverlay.classList.remove('opacity-0', 'pointer-events-none');
                            searchOverlay.classList.add('opacity-100', 'pointer-events-auto');
                        }
                        setTimeout(() => searchInput?.focus(), 100);
                    }

                    function disableSearch() {
                        document.body.classList.remove('search-active');
                        suggestionsContainer?.classList.remove('active');
                        const defaultHeader = document.getElementById('defaultHeader');
                        const searchHeader = document.getElementById('searchHeader');
                        if (defaultHeader) defaultHeader.classList.remove('hidden');
                        if (searchHeader) {
                            searchHeader.classList.add('hidden');
                            searchHeader.classList.remove('flex');
                        }
                        const searchOverlay = document.getElementById('searchOverlay');
                        if (searchOverlay) {
                            searchOverlay.classList.add('opacity-0', 'pointer-events-none');
                            searchOverlay.classList.remove('opacity-100', 'pointer-events-auto');
                        }
                        if (searchInput) searchInput.value = '';
                    }

                    if (searchToggle) searchToggle.addEventListener('click', enableSearch);
                    if (searchToggleMobile) searchToggleMobile.addEventListener('click', enableSearch);
                    if (closeSearchBtn) closeSearchBtn.addEventListener('click', disableSearch);
                    if (searchOverlay) searchOverlay.addEventListener('click', disableSearch);

                    document.addEventListener('keydown', function (e) {
                        if (e.key === 'Escape' && document.body.classList.contains('search-active')) {
                            disableSearch();
                        }
                    });

                    if (searchInput) {
                        searchInput.addEventListener('input', function (e) {
                            debouncedSearch(e.target.value.trim());
                        });
                        searchInput.addEventListener('keypress', function (e) {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                const query = searchInput.value.trim();
                                if (query) {
                                    // ✅ SỬA: ./shop.php (không có view/)
                                    window.location.href = `./shop.php?search=${encodeURIComponent(query)}`;
                                }
                            }
                        });
                    }

                    document.addEventListener('click', function (e) {
                        if (searchInput && suggestionsContainer &&
                            !searchInput.contains(e.target) &&
                            !suggestionsContainer.contains(e.target)) {
                            suggestionsContainer.classList.remove('active');
                        }
                    });

                }); 
            </script>
       <script>
(function() {
    // ⚠️ HARDCODE ĐƯỜNG DẪN TẠI ĐÂY (Sửa cho phù hợp với từng thư mục)
    const LOGOUT_URL = '../control/logout.php'; 

    // 1. Lắng nghe tín hiệu đăng xuất từ các tab/window khác
    window.addEventListener('storage', function(e) {
        if (e.key === 'nvbplay_logout_sync') {
            // Tab khác nhận được lệnh -> chuyển hướng đến logout.php
            window.location.href = LOGOUT_URL;
        }
    });

    // 2. Khi người dùng click vào link đăng xuất ở tab hiện tại
    document.addEventListener('click', function(e) {
        // Bắt tất cả link trỏ đến logout.php (kể cả trong dropdown/mobile menu)
        const logoutLink = e.target.closest('a[href*="logout.php"]');
        if (logoutLink) {
            // Gửi tín hiệu sang localStorage để các tab khác nhận được
            localStorage.setItem('nvbplay_logout_sync', Date.now().toString());
            // Tab hiện tại sẽ tự thực hiện redirect theo href của link (không cần preventDefault)
        }
    });
})();
</script>


</body>

</html>