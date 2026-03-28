<?php
// view/cart.php
session_start();
require_once '../control/connect.php';

$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}
// Xử lý buy_now mode (nếu có)
if (isset($_SESSION['buy_now_cart']) && is_array($_SESSION['buy_now_cart'])) {
    $cart_count += array_sum($_SESSION['buy_now_cart']);
}

// === KIỂM TRA AJAX REQUEST ===
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// === XỬ LÝ CÁC ACTION (POST) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($is_ajax) {
        header('Content-Type: application/json');
    }

    $response = ['success' => false, 'message' => '', 'cart_count' => 0];

    // ✅ 1. XỬ LÝ MUA NGAY
    if (isset($_POST['buy_now']) && $_POST['buy_now'] == '1') {
        $product_id = (int) ($_POST['product_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 1);

        if ($product_id > 0 && $quantity > 0) {
            $stmt = $conn->prepare("SELECT SoLuongTon FROM sanpham WHERE SanPham_id = ? AND TrangThai = 1");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $stock = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($stock && $quantity <= $stock['SoLuongTon']) {
                //  Lưu riêng, KHÔNG đụng vào $_SESSION['cart']
                $_SESSION['buy_now_cart'] = [
                    $product_id => $quantity
                ];

                if ($is_ajax) {
                    echo json_encode(['success' => true, 'redirect' => 'checkout.php?mode=buy_now']);
                    exit();
                } else {
                    header("Location: checkout.php?mode=buy_now");
                    exit();
                }
            } else {
                if ($is_ajax) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Sản phẩm không đủ số lượng']);
                    exit();
                }
            }
        }
    }

    //  2. THÊM VÀO GIỎ HÀNG THƯỜNG
    if (isset($_POST['add_to_cart'])) {
        $product_id = (int) $_POST['product_id'];
        $quantity = (int) ($_POST['quantity'] ?? 1);

        if ($product_id > 0 && $quantity > 0) {
            $stmt = $conn->prepare("SELECT SoLuongTon FROM sanpham WHERE SanPham_id = ? AND TrangThai = 1");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $stock = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($stock && $quantity <= $stock['SoLuongTon']) {
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id] += $quantity;
                } else {
                    $_SESSION['cart'][$product_id] = $quantity;
                }

                if ($is_ajax) {
                    $response['success'] = true;
                    $response['message'] = 'Đã thêm sản phẩm vào giỏ hàng!';
                    $response['cart_count'] = array_sum($_SESSION['cart']);
                    echo json_encode($response);
                    exit();
                } else {
                    header("Location: cart.php?added=1");
                    exit();
                }
            } else {
                if ($is_ajax) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Sản phẩm không đủ số lượng']);
                    exit();
                }
            }
        }
    }

    // 3. Cập nhật số lượng
    if (isset($_POST['update_cart'])) {
        $quantities = $_POST['quantity'] ?? [];
        foreach ($quantities as $product_id => $qty) {
            $product_id = (int) $product_id;
            $qty = (int) $qty;
            if ($qty > 0) {
                $_SESSION['cart'][$product_id] = $qty;
            } else {
                unset($_SESSION['cart'][$product_id]);
            }
        }

        if ($is_ajax) {
            $response['success'] = true;
            $response['cart_count'] = array_sum($_SESSION['cart']);
            echo json_encode($response);
            exit();
        } else {
            header("Location: cart.php?updated=1");
            exit();
        }
    }

    // 4. Xóa sản phẩm
    if (isset($_POST['remove_item'])) {
        $product_id = (int) ($_POST['product_id'] ?? 0);
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
        }

        if ($is_ajax) {
            $response['success'] = true;
            $response['cart_count'] = array_sum($_SESSION['cart']);
            echo json_encode($response);
            exit();
        } else {
            header("Location: cart.php?removed=1");
            exit();
        }
    }

    // 5. Xóa tất cả
    if (isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = [];
        if ($is_ajax) {
            $response['success'] = true;
            $response['cart_count'] = 0;
            echo json_encode($response);
            exit();
        } else {
            header("Location: cart.php?cleared=1");
            exit();
        }
    }

    // Nếu là AJAX nhưng không match action nào
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit();
    }
}

// === KIỂM TRA ĐĂNG NHẬP ===
if (!$is_ajax) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?redirect=cart");
        exit();
    }
    if (isset($_SESSION['role']) && $_SESSION['role'] == 1) {
        session_destroy();
        setcookie('remember_user', '', time() - 3600, '/');
        header("Location: login.php?error=staff_not_allowed");
        exit();
    }
}

// === KHỞI TẠO GIỎ HÀNG ===
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// === LẤY THÔNG TIN SẢN PHẨM ===
$cart_items = [];
$total_amount = 0;
$total_items = 0;
$cart_danhmuc_ids = [];

if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $stmt = $conn->prepare("SELECT s.*, d.Ten_danhmuc, d.Danhmuc_id
        FROM sanpham s
        LEFT JOIN danhmuc d ON s.Danhmuc_id = d.Danhmuc_id
        WHERE s.SanPham_id IN ($placeholders) AND s.TrangThai = 1");
    $types = str_repeat('i', count($product_ids));
    $stmt->bind_param($types, ...$product_ids);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($product = $result->fetch_assoc()) {
        $product['SoLuong'] = $_SESSION['cart'][$product['SanPham_id']];
        $product['Thanhtien'] = $product['SoLuong'] * $product['GiaBan'];
        $total_amount += $product['Thanhtien'];
        $total_items += $product['SoLuong'];
        if (!empty($product['Danhmuc_id']) && !in_array($product['Danhmuc_id'], $cart_danhmuc_ids)) {
            $cart_danhmuc_ids[] = $product['Danhmuc_id'];
        }
        $cart_items[] = $product;
    }
    $stmt->close();
}

// === LẤY SẢN PHẨM RECOMMEND ===
$recommended_products = [];
if (!empty($cart_danhmuc_ids)) {
    $danhmuc_placeholders = implode(',', array_fill(0, count($cart_danhmuc_ids), '?'));
    $exclude_ids = array_keys($_SESSION['cart']);
    $exclude_placeholders = !empty($exclude_ids) ? implode(',', array_fill(0, count($exclude_ids), '?')) : '0';

    $recommend_sql = "SELECT s.*, d.Ten_danhmuc
        FROM sanpham s
        LEFT JOIN danhmuc d ON s.Danhmuc_id = d.Danhmuc_id
        WHERE s.Danhmuc_id IN ($danhmuc_placeholders)
        AND s.TrangThai = 1
        AND s.SanPham_id NOT IN ($exclude_placeholders)
        ORDER BY RAND()
        LIMIT 5";

    $stmt = $conn->prepare($recommend_sql);
    $types = str_repeat('i', count($cart_danhmuc_ids));
    if (!empty($exclude_ids)) {
        $types .= str_repeat('i', count($exclude_ids));
        $stmt->bind_param($types, ...$cart_danhmuc_ids, ...$exclude_ids);
    } else {
        $stmt->bind_param($types, ...$cart_danhmuc_ids);
    }
    $stmt->execute();
    $recommended_products = $stmt->get_result();
    $stmt->close();
}

// Format giá
function formatPrice($price)
{
    return number_format($price, 0, ',', '.') . '₫';
}

function calculateDiscount($import_price, $sell_price)
{
    if ($import_price > $sell_price && $import_price > 0) {
        return round(($import_price - $sell_price) / $import_price * 100);
    }
    return 0;
}

$is_logged_in = isset($_SESSION['user_id']);
$user_info = [
    'user_id' => $_SESSION['user_id'] ?? '',
    'username' => $_SESSION['username'] ?? '',
    'ho_ten' => $_SESSION['ho_ten'] ?? '',
    'email' => $_SESSION['email'] ?? '',
    'role' => $_SESSION['role'] ?? 0
];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng | NVBPlay</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .qty-input {
            width: 60px;
            text-align: center;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
        }

        .qty-btn {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
        }

        .qty-btn:hover {
            background: #f3f4f6;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .alert-success {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }

        .alert-error {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
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
#searchHeader { display: none; }
body.search-active #defaultHeader { display: none; }
body.search-active #searchHeader { display: flex; }

body.search-active #searchOverlay {
    opacity: 1;
    pointer-events: auto;
}

#searchSuggestions {
    position: absolute;
    left: 0; right: 0;
    top: 100%;
    margin-top: 8px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
    border: 1px solid #f3f4f6;
    overflow-y: auto;
    max-height: 400px;
    z-index: 50;
    display: none;
    animation: slideDown 0.2s ease;
}
#searchSuggestions.active { display: block; }

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to   { opacity: 1; transform: translateY(0); }
}

.suggestion-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.2s;
    text-decoration: none; color: inherit;
}
.suggestion-item:hover { background: #f9fafb; }
.suggestion-item img {
    width: 60px; height: 60px;
    object-fit: cover; border-radius: 8px;
    background: #f3f4f6; flex-shrink: 0;
}
.suggestion-info { flex: 1; min-width: 0; }
.suggestion-info h4 {
    font-size: 14px; font-weight: 500; color: #1f2937;
    margin: 0 0 4px 0;
    overflow: hidden; display: -webkit-box;
    -webkit-line-clamp: 2; -webkit-box-orient: vertical;
}
.price-wrapper { display: flex; align-items: center; gap: 8px; }
.price-wrapper .price { font-size: 15px; font-weight: 600; color: #dc2626; }
.price-wrapper .old-price { font-size: 13px; color: #9ca3af; text-decoration: line-through; }
.price-wrapper .discount-badge {
    font-size: 11px; font-weight: 600; color: #dc2626;
    background: #fef2f2; padding: 2px 6px; border-radius: 4px;
}
.view-all-link {
    display: flex; align-items: center; justify-content: center;
    gap: 8px; padding: 14px; background: #f9fafb;
    color: #dc2626; font-size: 14px; font-weight: 500;
    text-decoration: none; border-top: 1px solid #f3f4f6;
}
.view-all-link:hover { background: #f3f4f6; }
.no-results { padding: 32px 24px; text-align: center; color: #6b7280; }
    </style>
</head>
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
                                <div id="searchSuggestions"
                                    class="absolute top-full left-0 right-0 z-50">
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


        <!-- MAIN CONTENT -->
        <main class="flex-grow bg-gray-50 py-8">
            <div class="container mx-auto px-4">
                <!-- Success/Error Messages -->
                <?php if (isset($_GET['added']) && $_GET['added'] == '1'): ?>
                    <div class="alert-success p-3 rounded mb-4 text-sm">
                        <i class="fas fa-check-circle mr-2"></i>Đã thêm sản phẩm vào giỏ hàng!
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
                    <div class="alert-success p-3 rounded mb-4 text-sm">
                        <i class="fas fa-check-circle mr-2"></i>Đã cập nhật giỏ hàng!
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['removed']) && $_GET['removed'] == '1'): ?>
                    <div class="alert-success p-3 rounded mb-4 text-sm">
                        <i class="fas fa-check-circle mr-2"></i>Đã xóa sản phẩm!
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['cleared']) && $_GET['cleared'] == '1'): ?>
                    <div class="alert-success p-3 rounded mb-4 text-sm">
                        <i class="fas fa-check-circle mr-2"></i>Đã xóa toàn bộ giỏ hàng!
                    </div>
                <?php endif; ?>

                <div class="flex flex-col lg:flex-row gap-4">
                    <!-- Cart Items (Left Column) -->
                    <div class="w-full lg:w-2/3">
                        <form method="POST" action="" id="cartForm">
                            <!-- Cart Header (Desktop) -->
                            <div
                                class="hidden md:grid grid-cols-[4fr,0.85fr,1.3fr,1.9fr] gap-4 bg-white p-4 rounded-t-lg shadow-sm border-b border-gray-200 text-sm font-semibold text-gray-600">
                                <span>Sản phẩm</span>
                                <span class="text-center">Đơn giá</span>
                                <span class="text-center">Số lượng</span>
                                <span class="text-center">Thành tiền</span>
                            </div>

                            <!-- Cart Items List -->
                            <div class="bg-white rounded-b-lg shadow-sm divide-y divide-gray-200">
                                <?php if (!empty($cart_items)): ?>
                                    <?php foreach ($cart_items as $item): ?>
                                        <!-- Product Item -->
                                        <div
                                            class="p-4 md:p-0 md:grid md:grid-cols-[4fr,0.7fr,1.85fr,0.6fr] md:gap-4 md:items-center">
                                            <!-- Product Info -->
                                            <div class="flex gap-4">
                                                <img src="../<?php echo htmlspecialchars($item['image_url']); ?>"
                                                    alt="<?php echo htmlspecialchars($item['TenSP']); ?>"
                                                    class="w-20 h-20 object-cover rounded">
                                                <div>
                                                    <a href="product.php?id=<?php echo $item['SanPham_id']; ?>"
                                                        class="font-medium text-gray-800 hover:text-red-600 mt-2 line-clamp-2">
                                                        <?php echo htmlspecialchars($item['TenSP']); ?>
                                                    </a>
                                                    <!-- Mobile price & quantity -->
                                                    <div class="md:hidden flex items-center justify-between mt-2">
                                                        <span class="text-sm font-medium text-gray-900">
                                                            <?php echo formatPrice($item['GiaBan']); ?>
                                                        </span>
                                                        <div class="flex items-center border border-gray-300 rounded">
                                                            <button type="button" class="qty-btn w-8 h-8 text-gray-600"
                                                                onclick="updateQty(this, -1)">-</button>
                                                            <input type="number"
                                                                name="quantity[<?php echo $item['SanPham_id']; ?>]"
                                                                value="<?php echo $item['SoLuong']; ?>" min="1"
                                                                max="<?php echo (int) $item['SoLuongTon']; ?>"
                                                                data-stock="<?php echo (int) $item['SoLuongTon']; ?>"
                                                                data-product-id="<?php echo $item['SanPham_id']; ?>"
                                                                class="qty-input w-12 text-center border-0  focus:ring-0 p-0">
                                                            <button type="button" class="qty-btn w-8 h-8 text-gray-600"
                                                                onclick="updateQty(this, 1)">+</button>
                                                        </div>
                                                        <span class="font-semibold text-red-600">
                                                            <?php echo formatPrice($item['Thanhtien']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Price (Desktop) -->
                                            <div class="hidden md:block text-center text-gray-700">
                                                <?php echo formatPrice($item['GiaBan']); ?>
                                            </div>
                                            <!-- Quantity (Desktop) -->
                                            <div class="hidden md:flex justify-center">
                                                <div class="flex items-center border border-gray-300 rounded">
                                                    <button type="button" class="qty-btn w-8 h-8 text-gray-600"
                                                        onclick="updateQty(this, -1)">-</button>
                                                    <input type="number" name="quantity[<?php echo $item['SanPham_id']; ?>]"
                                                        value="<?php echo $item['SoLuong']; ?>" min="1"
                                                        max="<?php echo (int) $item['SoLuongTon']; ?>"
                                                        data-stock="<?php echo (int) $item['SoLuongTon']; ?>"
                                                        data-product-id="<?php echo $item['SanPham_id']; ?>"
                                                        class="qty-input w-12 text-center border-0 focus:ring-0 p-0">
                                                    <button type="button" class="qty-btn w-8 h-8 text-gray-600"
                                                        onclick="updateQty(this, 1)">+</button>
                                                </div>
                                            </div>
                                            <!-- Subtotal & Remove (Desktop) -->
                                            <div class="hidden md:flex justify-end items-center gap-4">
                                                <span class="font-semibold text-red-600">
                                                    <?php echo formatPrice($item['Thanhtien']); ?>
                                                </span>
                                                <button type="button" onclick="removeItem(<?php echo $item['SanPham_id']; ?>)"
                                                    class="text-gray-400 hover:text-red-600 ml-5 mr-5" title="Xóa">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                            <!-- Remove button for mobile -->
                                            <div class="md:hidden flex justify-end mt-2">
                                                <button type="button" onclick="removeItem(<?php echo $item['SanPham_id']; ?>)"
                                                    class="text-gray-400 hover:text-red-600 text-sm">
                                                    <i class="fas fa-trash-alt mr-1"></i> Xóa
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="p-8 text-center">
                                        <i class="fas fa-shopping-basket text-5xl text-gray-300 mb-4"></i>
                                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Giỏ hàng trống</h3>
                                        <p class="text-gray-500 mb-4">Chưa có sản phẩm nào trong giỏ hàng</p>
                                        <a href="../view/shop.php"
                                            class="inline-block px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-semibold">
                                            <i class="fas fa-shopping-bag mr-2"></i>Mua sắm ngay
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </form>

                        <!-- SẢN PHẨM RECOMMEND THEO DANH MỤC -->
                        <?php if ($recommended_products && $recommended_products->num_rows > 0): ?>
                            <div class="mt-12">
                                <h3 class="text-lg font-bold mb-4 flex items-center">
                                    <i class="text-yellow-500 mr-2"></i>
                                    Sản phẩm mua cùng
                                </h3>
                                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                                    <?php while ($product = $recommended_products->fetch_assoc()):
                                        $discount = calculateDiscount($product['GiaNhapTB'] ?? 0, $product['GiaBan']);
                                        ?>
                                        <div
                                            class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden group hover:shadow-lg transition duration-300">
                                            <a href="product.php?id=<?php echo $product['SanPham_id']; ?>">
                                                <div class="relative">
                                                    <div class="aspect-square bg-gray-100 flex items-center justify-center p-4">
                                                        <img src="../<?php echo htmlspecialchars($product['image_url']); ?>"
                                                            alt="<?php echo htmlspecialchars($product['TenSP']); ?>"
                                                            class="w-full h-full object-contain mix-blend-multiply group-hover:scale-110 transition-transform duration-300">
                                                    </div>
                                                </div>
                                                <div class="p-3">
                                                    <h4
                                                        class="font-medium text-sm mb-2 line-clamp-2 h-10 hover:text-red-600 transition">
                                                        <?php echo htmlspecialchars($product['TenSP']); ?>
                                                    </h4>
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <span
                                                            class="text-red-600 font-bold"><?php echo formatPrice($product['GiaBan']); ?></span>
                                                        <?php if ($discount > 0): ?>
                                                            <span
                                                                class="text-gray-400 text-xs line-through"><?php echo formatPrice($product['GiaNhapTB'] ?? 0); ?></span>
                                                            <span
                                                                class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">-<?php echo $discount; ?>%</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Right Column: Order Summary -->
                    <div class="w-full lg:w-1/3">
                        <div class="bg-white rounded-lg shadow-sm p-4 sticky top-24">
                            <h3 class="font-semibold text-lg mb-4">Tổng cộng giỏ hàng</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between text-gray-600">
                                    <span>Tạm tính</span>
                                    <span><?php echo formatPrice($total_amount); ?></span>
                                </div>
                                <div class="border-t border-gray-200 pt-3 flex justify-between font-bold text-lg">
                                    <span>Tổng cộng</span>
                                    <span class="text-red-600"><?php echo formatPrice($total_amount); ?></span>
                                </div>
                            </div>
                            <div class="mt-6">
                                <a href="./checkout.php"
                                    class="block w-full bg-red-600 hover:bg-red-700 text-white text-center py-3 rounded-lg font-semibold transition">
                                    <i class="fas fa-credit-card mr-2"></i>Tiến hành thanh toán
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer  -->
        <footer id="footer" class="bg-black text-white">
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
                <img src="../img/icons/logonvb.png" height="30" width="50" class="relative-top-left transform scale-75">
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
                            <span class="text-sm text-gray-500"><?php echo htmlspecialchars($user_info['email']); ?></span>
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
                    <button class="w-full flex items-center justify-between p-3 bg-gray-50 rounded-lg category-toggle"
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
                            <a href="./shop.php?danhmuc[]=vot-cau-long" class="block py-2 text-gray-700 font-medium">Vợt
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
                                <a href="./shop.php?danhmuc[]=vot-cau-long" class="block py-1 text-sm text-red-600">Xem
                                    thêm</a>
                            </div>
                        </div>

                        <!-- Áo cầu lông -->
                        <div>
                            <a href="./shop.php?danhmuc[]=ao-cau-long" class="block py-2 text-gray-700 font-medium">Áo
                                cầu lông</a>
                            <div class="pl-4 mt-1 space-y-1">
                                <a href="./shop.php?danhmuc[]=ao-cau-long&thuonghieu[]=yonex"
                                    class="block py-1 text-sm text-gray-600">Áo Yonex</a>
                                <a href="./shop.php?danhmuc[]=ao-cau-long&thuonghieu[]=ds"
                                    class="block py-1 text-sm text-gray-600">Áo DS</a>
                                <a href="./shop.php?danhmuc[]=ao-cau-long&thuonghieu[]=kamito"
                                    class="block py-1 text-sm text-gray-600">Áo Kamito</a>
                                <a href="./shop.php?danhmuc[]=ao-cau-long" class="block py-1 text-sm text-red-600">Xem
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
                    <button class="w-full flex items-center justify-between p-3 bg-gray-50 rounded-lg category-toggle"
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
                    <button class="w-full flex items-center justify-between p-3 bg-gray-50 rounded-lg category-toggle"
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
                console.log('DOM loaded – cart.js started');

                // === QUANTITY UPDATE ===
                window.updateQty = function (btn, change) {
                    const input = btn.parentNode.querySelector('input');
                    const productId = input.dataset.productId;
                    const stock = parseInt(input.dataset.stock) || 999;
                    let val = parseInt(input.value);

                    if (isNaN(val)) val = 1;
                    let newVal = val + change;

                    if (newVal < 1) newVal = 1;
                    if (newVal > stock) newVal = stock;

                    input.value = newVal;

                    // Gửi AJAX update
                    fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: `update_cart=1&quantity[${productId}]=${newVal}`
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            }
                        })
                        .catch(err => {
                            console.error('Lỗi cập nhật số lượng:', err);
                            location.reload();
                        });
                };

                // === REMOVE ITEM ===
                window.removeItem = function (productId) {
                    if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')) {
                        fetch('', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: `remove_item=1&product_id=${productId}`
                        })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    location.reload();
                                }
                            })
                            .catch(err => {
                                console.error('Lỗi xóa sản phẩm:', err);
                                location.reload();
                            });
                    }
                };

                // === CLEAR CART ===
                window.clearCart = function () {
                    if (confirm('Bạn có chắc chắn muốn xóa tất cả giỏ hàng?')) {
                        fetch('', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: `clear_cart=1`
                        })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    location.reload();
                                }
                            })
                            .catch(err => {
                                console.error('Lỗi xóa giỏ hàng:', err);
                                location.reload();
                            });
                    }
                };


            });

        </script>

        <script>
            document.addEventListener('DOMContentLoaded', function () {

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

                // ========== SEARCH FUNCTIONALITY (ĐÃ SỬA) ==========
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
                         onerror="this.src='./img/sanpham/placeholder.png'">
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

            });</script>
</body>

</html>