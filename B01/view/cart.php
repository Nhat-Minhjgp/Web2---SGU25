<?php
// view/cart.php
session_start();

// === 🔐 KIỂM TRA ĐĂNG NHẬP BẮT BUỘC ===
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=cart");
    exit();
}

// === 🚫 CHẶN ROLE 1 (Staff/Admin) ===
if (isset($_SESSION['role']) && $_SESSION['role'] == 1) {
    session_destroy();
    setcookie('remember_user', '', time() - 3600, '/');
    header("Location: login.php?error=staff_not_allowed");
    exit();
}

require_once '../control/connect.php';

$success = '';
$errors = [];

// === 🛒 KHỞI TẠO GIỎ HÀNG TỪ SESSION ===
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// === XỬ LÝ CÁC ACTION ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Thêm sản phẩm vào giỏ
    if (isset($_POST['add_to_cart'])) {
        $product_id = (int) $_POST['product_id'];
        $quantity = (int) ($_POST['quantity'] ?? 1);

        if ($product_id > 0 && $quantity > 0) {
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = $quantity;
            }
            $success = "Đã thêm sản phẩm vào giỏ hàng!";
        }
    }

    // Cập nhật số lượng
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
        $success = "Cập nhật giỏ hàng thành công!";
    }

    // Xóa sản phẩm
    if (isset($_POST['remove_item'])) {
        $product_id = (int) $_POST['product_id'];
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
            $success = "Đã xóa sản phẩm khỏi giỏ hàng!";
        }
    }

    // Xóa tất cả
    if (isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = [];
        $success = "Đã xóa tất cả giỏ hàng!";
    }
}

// === 📦 LẤY THÔNG TIN SẢN PHẨM TỪ DATABASE ===
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

// === 🎯 LẤY SẢN PHẨM RECOMMEND THEO DANH MỤC ===
$recommended_products = [];
if (!empty($cart_danhmuc_ids)) {
    $danhmuc_placeholders = implode(',', array_fill(0, count($cart_danhmuc_ids), '?'));

    // Loại trừ sản phẩm đã có trong giỏ
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

// Tính phần trăm giảm giá
function calculateDiscount($import_price, $sell_price)
{
    if ($import_price > $sell_price && $import_price > 0) {
        return round(($import_price - $sell_price) / $import_price * 100);
    }
    return 0;
}

// Lấy thông tin user cho header
$user_info = [
    'user_id' => $_SESSION['user_id'],
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
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Custom styles */
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

        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .rotate-180 {
            transform: rotate(180deg);
        }

        .category-toggle.active {
            background-color: #fef2f2;
            color: #dc2626;
        }

        .category-submenu {
            transition: all 0.3s ease;
        }

        body.menu-open {
            overflow: hidden;
        }

        .alert-error {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .alert-success {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }

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

        .role-badge-staff {
            background: #dc2626;
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
                <div id="masthead" class="py-2 md:py-3 border-b">
                    <div class="container mx-auto px-4 flex items-center justify-between">
                        <!-- Mobile Menu Toggle -->
                        <div class="md:hidden">
                            <button class="menu-toggle p-2">
                                <img src="../img/icons/menu.svg" class="fas fa-bars text-2xl">
                            </button>
                        </div>

                        <!-- Desktop Left Menu -->
                        <div class="hidden md:flex items-center flex-1 ml-6">
                            <ul class="flex items-center space-x-4">
                                <li class="relative" id="mega-menu-container">
                                    <button id="mega-menu-trigger"
                                        class="button-menu flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200 transition">
                                        <img src="../img/icons/menu.svg" class="w-5 h-5 mr-2" alt="menu">
                                        <span>Danh mục</span>
                                    </button>
                                    <div id="mega-menu-dropdown"
                                        class="absolute left-0 top-full mt-2 w-[900px] bg-white rounded-lg shadow-xl hidden z-50">
                                        <!-- Mega menu content giữ nguyên -->
                                    </div>
                                </li>
                                <li><a href="../view/shop.php"
                                        class="flex items-center text-gray-700 hover:text-red-600 font-medium">
                                        <img src="../img/icons/store.svg" class="w-5 h-5 flex-shrink-0 mr-2">
                                        <span>CỬA HÀNG</span>
                                    </a></li>
                            </ul>
                        </div>

                        <!-- Logo -->
                        <div id="logo" class="flex-shrink-1 absolute left-1/2 transform -translate-x-1/2">
                            <a href="../index.php" title="NVBPlay" rel="home">
                                <img width="100" height="40" src="../img/icons/logonvb.png" alt="NVBPlay"
                                    class="h-12 md:h-14 w-auto transform scale-75">
                            </a>
                        </div>

                        <!-- Desktop Right Elements -->
                        <div class="hidden md:flex items-center space-x-4">
                            <!-- Address Book -->
                            <div class="address-book">
                                <a href="./my-account/address-book.php"
                                    class="flex items-center text-gray-700 hover:text-red-600">
                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                    <span class="shipping-address text-sm"><span class="text">Chọn địa chỉ</span></span>
                                </a>
                            </div>
                            <div class="h-5 w-px bg-gray-300"></div>

                            <!-- Search -->
                            <div class="search-header relative">
                                <button class="search-toggle p-2"><i
                                        class="fas fa-search text-gray-700 hover:text-red-600"></i></button>
                            </div>

                            <!-- Account Dropdown -->
                            <div class="user-dropdown relative">
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
                                                    <?php echo htmlspecialchars($user_info['ho_ten']); ?></p>
                                                <p class="text-xs text-gray-500">
                                                    <?php echo htmlspecialchars($user_info['email']); ?></p>
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
                            </div>

                            <!-- Cart -->
                            <a href="./cart.php" class="relative p-2">
                                <i class="fas fa-shopping-basket text-gray-700 hover:text-red-600 text-xl"></i>
                                <span
                                    class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"><?php echo $total_items; ?></span>
                            </a>
                        </div>

                        <!-- Mobile Right Elements -->
                        <div class="md:hidden flex items-center space-x-3">
                            <button class="search-toggle p-1"><i class="fas fa-search text-xl"></i></button>
                            <a href="./my-account.php" class="p-1"><img src="../img/icons/account.svg" class="w-6 h-6"
                                    alt="Account"></a>
                            <a href="./cart.php" class="relative p-1">
                                <i class="fas fa-shopping-basket text-xl"></i>
                                <span
                                    class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center"><?php echo $total_items; ?></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="flex-grow bg-gray-50 py-8">
            <div class="container mx-auto px-4">
                <!-- Success/Error Messages -->
                <?php if ($success): ?>
                    <div class="alert-success p-3 rounded mb-4 text-sm">
                        <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert-error p-3 rounded mb-4 text-sm">
                        <ul class="list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="flex flex-col lg:flex-row gap-4">
                    <!-- Cart Items (Left Column) -->
                    <div class="w-full lg:w-2/3">
                        <form method="POST" action="" id="cartForm">
                            <!-- Cart Header (Desktop) -->
                            <div
                                class="hidden md:grid grid-cols-[4fr,0.85fr,1.3fr,1.5fr] gap-4 bg-white p-4 rounded-t-lg shadow-sm border-b border-gray-200 text-sm font-semibold text-gray-600">
                                <span>Sản phẩm</span>
                                <span class="text-center">Đơn giá</span>
                                <span class="text-center">Số lượng</span>
                                <span class="text-center">Thành tiền</span>
                            </div>

                            <!-- Cart Items List -->
                            <div class="bg-white rounded-b-lg shadow-sm divide-y divide-gray-200">
                                <?php if (!empty($cart_items)): ?>
                                    <?php foreach ($cart_items as $item):
                                        $discount = calculateDiscount($item['GiaNhapTB'] ?? 0, $item['GiaBan']);
                                        ?>
                                        <div
                                            class="p-4 md:p-0 md:grid md:grid-cols-[4fr,0.7fr,1.85fr,0.6fr] md:gap-4 md:items-center">
                                            <!-- Product Info -->
                                            <div class="flex gap-4">
                                                <img src="../<?php echo htmlspecialchars($item['image_url']); ?>"
                                                    alt="<?php echo htmlspecialchars($item['TenSP']); ?>"
                                                    class="w-20 h-20 object-cover rounded border">
                                                <div>
                                                    <a href="product.php?id=<?php echo $item['SanPham_id']; ?>"
                                                        class="font-medium text-gray-800 hover:text-red-600 line-clamp-2">
                                                        <?php echo htmlspecialchars($item['TenSP']); ?>
                                                    </a>
                                                    <!-- Mobile price & quantity -->
                                                    <div class="md:hidden flex items-center justify-between mt-2">
                                                        <span
                                                            class="text-sm font-medium text-gray-900"><?php echo formatPrice($item['GiaBan']); ?></span>
                                                        <div class="flex items-center border border-gray-300 rounded">
                                                            <button type="button" class="qty-btn w-8 h-8 text-gray-600"
                                                                onclick="updateQty(this, -1)">-</button>
                                                            <input type="number"
                                                                name="quantity[<?php echo $item['SanPham_id']; ?>]"
                                                                value="<?php echo $item['SoLuong']; ?>" min="1"
                                                                class="qty-input w-12 text-center border-0 focus:ring-0 p-0">
                                                            <button type="button" class="qty-btn w-8 h-8 text-gray-600"
                                                                onclick="updateQty(this, 1)">+</button>
                                                        </div>
                                                        <span
                                                            class="font-semibold text-red-600"><?php echo formatPrice($item['Thanhtien']); ?></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Price (Desktop) -->
                                            <div class="hidden md:block text-center text-gray-700">
                                                <?php echo formatPrice($item['GiaBan']); ?></div>

                                            <!-- Quantity (Desktop) -->
                                            <div class="hidden md:flex justify-center">
                                                <div class="flex items-center border border-gray-300 rounded">
                                                    <button type="button" class="qty-btn w-8 h-8 text-gray-600"
                                                        onclick="updateQty(this, -1)">-</button>
                                                    <input type="number" name="quantity[<?php echo $item['SanPham_id']; ?>]"
                                                        value="<?php echo $item['SoLuong']; ?>" min="1"
                                                        class="qty-input w-12 text-center border-0 focus:ring-0 p-0">
                                                    <button type="button" class="qty-btn w-8 h-8 text-gray-600"
                                                        onclick="updateQty(this, 1)">+</button>
                                                </div>
                                            </div>

                                            <!-- Subtotal & Remove (Desktop) -->
                                            <div class="hidden md:flex justify-end items-center gap-4">
                                                <span
                                                    class="font-semibold text-red-600"><?php echo formatPrice($item['Thanhtien']); ?></span>
                                                <button type="button" onclick="removeItem(<?php echo $item['SanPham_id']; ?>)"
                                                    class="text-gray-400 hover:text-red-600 ml-5 mr-5" title="Xóa">
                                                    <i class="fas fa-trash-alt "></i>
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

                            <!-- Continue Shopping & Actions -->
                            <?php if (!empty($cart_items)): ?>
                                <div class="mt-4 flex flex-col sm:flex-row gap-4 justify-between">
                                    <a href="../view/shop.php"
                                        class="inline-flex items-center text-red-600 hover:underline">
                                        <i class="fas fa-arrow-left mr-2"></i> Tiếp tục xem sản phẩm
                                    </a>
                                    <div class="flex gap-3">
                                        <button type="button" onclick="clearCart()"
                                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                                            <i class="fas fa-trash mr-2"></i>Xóa tất cả
                                        </button>
                                       
                                    </div>
                                </div>
                            <?php endif; ?>
                        </form>

                        <!--  SẢN PHẨM RECOMMEND THEO DANH MỤC -->
                        <?php if ($recommended_products && $recommended_products->num_rows > 0): ?>
                            <div class="mt-12">
                                <h3 class="text-lg font-bold mb-4 flex items-center">
                                    <i class="fas fa-star text-yellow-500 mr-2"></i>
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
                            <!-- Trust Badges -->
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <div class="flex items-center justify-center gap-4 text-xs text-gray-500">
                                    <div class="flex items-center"><i class="fas fa-shield-alt mr-1"></i>Thanh toán an
                                        toàn</div>
                                    <div class="flex items-center"><i class="fas fa-truck mr-1"></i>Giao hàng nhanh
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer id="footer" class="bg-black text-white mt-12">
            <!-- Footer content giữ nguyên -->
        </footer>
    </div>

    <!-- JavaScript -->
    <script>
        // === QUANTITY UPDATE ===
        function updateQty(btn, change) {
            const input = btn.parentNode.querySelector('input');
            let val = parseInt(input.value);
            if (isNaN(val)) val = 1;
            let newVal = val + change;
            if (newVal < 1) newVal = 1;
            input.value = newVal;
        }

        // === REMOVE ITEM ===
        function removeItem(productId) {
            if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="product_id" value="' + productId + '"><input type="hidden" name="remove_item" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        // === CLEAR CART ===
        function clearCart() {
            if (confirm('Bạn có chắc chắn muốn xóa tất cả giỏ hàng?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="clear_cart" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        // === USER DROPDOWN TOGGLE ===
        document.addEventListener('DOMContentLoaded', function () {
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

            // Mobile menu toggle
            const menuToggle = document.querySelector('.menu-toggle');
            const closeMenu = document.querySelector('.close-menu');
            const mainMobileMenu = document.getElementById('main-menu');
            if (menuToggle) {
                menuToggle.addEventListener('click', function () {
                    mainMobileMenu.classList.remove('-translate-x-full');
                    document.body.style.overflow = 'hidden';
                });
            }
            if (closeMenu) {
                closeMenu.addEventListener('click', function () {
                    mainMobileMenu.classList.add('-translate-x-full');
                    document.body.style.overflow = '';
                });
            }
        });
    </script>
</body>

</html>