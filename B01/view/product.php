<?php
// view/product.php
session_start();
require_once '../control/connect.php';

// === KIỂM TRA ĐĂNG NHẬP (Giống index.php) ===
$is_logged_in = isset($_SESSION['user_id']);
$user_info = null;

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
    <meta name="description" content="<?php echo htmlspecialchars($product['TenSP']); ?>">
    <!-- Tailwind CSS CDN -->
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

        body.menu-open {
            overflow: hidden;
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
                <!-- Bottom Header / Wide Nav -->
                <div id="wide-nav" class="bg-gray-900 text-white py-2">
                    <div class="container mx-auto px-4 text-center">
                        <div class="top-hot">
                            <a href="#" class="text-white hover:text-yellow-300 transition text-sm md:text-base">
                                ⚡ VỢT YONEX NANOFLARE 1000 GAME - RESTOCKED ⚡
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Main Header -->
                <div id="masthead" class="py-2 md:py-3 border-b">
                    <div class="container mx-auto px-4 flex items-center justify-between">
                        <!-- Mobile Menu Toggle -->
                        <div class="md:hidden">
                            <button class="menu-toggle p-2">
                                <i class="fas fa-bars text-2xl"></i>
                            </button>
                        </div>

                        <!-- Desktop Left Menu -->
                        <div class="hidden md:flex items-center flex-1 ml-6">
                            <ul class="flex items-center space-x-4">
                                <li class="relative" id="mega-menu-container">
                                    <button id="mega-menu-trigger"
                                        class="button-menu flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200 transition">
                                        <i class="fas fa-bars mr-2"></i>
                                        <span>Danh mục</span>
                                    </button>
                                    <div id="mega-menu-dropdown"
                                        class="absolute left-0 top-full mt-2 w-[900px] bg-white rounded-lg shadow-xl hidden z-50">
                                    </div>
                                </li>
                                <li>
                                    <a href="shop.php"
                                        class="flex items-center text-gray-700 hover:text-red-600 font-medium">
                                        <img src="../img/icons/store.svg" class="w-5 h-5 flex-shrink-0 mr-2">
                                        <span>CỬA HÀNG</span>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <!-- Logo - Centered -->
                        <div id="logo" class="flex-shrink-1 absolute left-1/2 transform -translate-x-1/2">
                            <a href="../index.php" title="NVBPlay" rel="home">
                                <img width="100" height="40" src="../img/icons/logonvb.png" alt="NVBPlay"
                                    class="h-12 md:h-14 w-auto transform scale-75">
                            </a>
                        </div>

                        <!-- Desktop Right Elements -->
                        <div class="hidden md:flex items-center space-x-4">
                            <!-- Address Book (chỉ hiển thị khi đã đăng nhập) -->
                            <?php if ($is_logged_in): ?>
                                <div class="address-book">
                                    <a href="my-account/address-book.php"
                                        class="flex items-center text-gray-700 hover:text-red-600">
                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                        <span class="shipping-address text-sm">
                                            <span class="text">Chọn địa chỉ</span>
                                        </span>
                                    </a>
                                </div>
                                <div class="h-5 w-px bg-gray-300"></div>
                            <?php endif; ?>

                            <!-- Search button -->
                            <div class="search-header relative">
                                <button class="search-toggle p-2">
                                    <i class="fas fa-search text-gray-700 hover:text-red-600"></i>
                                </button>
                            </div>

                            <!-- Account Dropdown (THAY ĐỔI THEO TRẠNG THÁI ĐĂNG NHẬP) -->
                            <div class="user-dropdown">
                                <?php if ($is_logged_in): ?>
                                    <!-- Đã đăng nhập -->
                                    <button id="userToggle"
                                        class="flex items-center space-x-2 hover:bg-gray-100 px-3 py-2 rounded-lg transition">
                                        <img src="../img/icons/account.svg" class="w-6 h-6" alt="Account">
                                        <span
                                            class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($user_info['username']); ?></span>
                                        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                    </button>
                                    <!-- User Dropdown Menu -->
                                    <div id="userMenu" class="user-menu">
                                        <!-- User Info -->
                                        <div class="px-4 py-3 border-b border-gray-100">
                                            <div class="flex items-center space-x-3">
                                                <img src="../img/icons/account.svg" class="w-10 h-10" alt="Account">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-800">
                                                        <?php echo htmlspecialchars($user_info['ho_ten']); ?>
                                                        <?php if ($user_info['role'] == 1): ?>
                                                            <span
                                                                class="ml-2 px-2 py-1 text-xs rounded-full text-white role-badge-staff">Staff</span>
                                                        <?php endif; ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500">
                                                        <?php echo htmlspecialchars($user_info['email']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Menu Items -->
                                        <a href="my-account.php" class="user-menu-item">
                                            <i class="fas fa-user"></i><span>Tài khoản của tôi</span>
                                        </a>
                                        <a href="my-account/orders.php" class="user-menu-item">
                                            <i class="fas fa-shopping-bag"></i><span>Đơn hàng</span>
                                        </a>
                                        <a href="my-account/address-book.php" class="user-menu-item">
                                            <i class="fas fa-map-marker-alt"></i><span>Sổ địa chỉ</span>
                                        </a>
                                        <div class="user-menu-divider"></div>
                                        <a href="../control/logout.php" class="user-menu-item logout">
                                            <i class="fas fa-sign-out-alt"></i><span>Đăng xuất</span>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <!-- Chưa đăng nhập -->
                                    <a href="login.php" class="flex items-center text-gray-700 hover:text-red-600">
                                        <i class="far fa-user text-xl"></i>
                                        <span class="text-sm ml-1">Đăng nhập</span>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <!-- Cart -->
                            <a href="cart.php" class="relative p-2">
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
                            <!-- Account mobile -->
                            <?php if ($is_logged_in): ?>
                                <a href="my-account.php" class="p-1">
                                    <img src="../img/icons/account.svg" class="w-6 h-6" alt="Account">
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="p-1">
                                    <i class="far fa-user text-xl"></i>
                                </a>
                            <?php endif; ?>
                            <a href="cart.php" class="relative p-1">
                                <i class="fas fa-shopping-basket text-xl"></i>
                                <span
                                    class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">0</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

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
                                        <span>NCC: <?php echo htmlspecialchars($product['Ten_NCC']); ?></span>
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

                            <!-- Add to Cart Form -->
                            <form class="mt-auto space-y-4" method="POST" action="cart.php">
                                <input type="hidden" name="product_id" value="<?php echo $product['SanPham_id']; ?>">

                                <!-- Quantity -->
                                <div class="flex items-center gap-4">
                                    <span class="font-medium text-gray-700">Số lượng:</span>
                                    <div class="flex items-center border border-gray-300 rounded">
                                        <button type="button" onclick="decreaseQty()"
                                            class="px-3 py-2 hover:bg-gray-100 text-gray-600">-</button>
                                        <input type="number" name="quantity" id="quantity" value="1" min="1"
                                            max="<?php echo $product['SoLuongTon']; ?>"
                                            class="w-16 text-center border-none focus:ring-0 p-0" readonly>
                                        <button type="button" onclick="increaseQty()"
                                            class="px-3 py-2 hover:bg-gray-100 text-gray-600">+</button>
                                    </div>
                                </div>

                                <!-- Buttons -->
                                <div class="grid grid-cols-2 gap-4">
                                    <form method="POST" action="cart.php">
                                        <input type="hidden" name="product_id"
                                            value="<?php echo $product['SanPham_id']; ?>">
                                        <input type="hidden" name="add_to_cart" value="1">


                                        <button type="submit"
                                            class="w-full bg-red-600 text-white py-3 rounded-lg font-semibold">
                                            <i class="fas fa-shopping-cart mr-2"></i>Thêm vào giỏ
                                        </button>
                                    </form>
                                    <button type="submit" name="buy_now" value="1"
                                        class="w-full bg-red-600 text-white font-bold py-3 rounded-xl hover:bg-red-700 transition shadow-lg shadow-red-200">
                                        Mua ngay
                                    </button>
                                </div>
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
                        <h3 class="text-xl font-bold text-gray-900 mb-6 uppercase">Sản phẩm tương tự</h3>
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
                                        <div class="text-red-600 font-bold"><?php echo formatPrice($related['GiaBan']); ?></div>
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
        <footer id="footer" class="bg-black text-white mt-12">
            <div class="container mx-auto px-4 py-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="pl-5">
                        <h3 class="text-4xl font-bold mb-4">Boost<br>your power</h3>
                        <div class="flex space-x-3 mb-4">
                            <a href="https://www.facebook.com/nvbplay" target="_blank"
                                class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://www.tiktok.com/@nvbplay.vn" target="_blank"
                                class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition">
                                <i class="fab fa-tiktok"></i>
                            </a>
                            <a href="https://s.shopee.vn/6AV9qQcpMz" target="_blank"
                                class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition">
                                <i class="fas fa-shopping-bag"></i>
                            </a>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xl font-bold mb-4">Thông tin khác</h3>
                        <ul class="space-y-2">
                            <li><a href="#" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH BẢO MẬT</a>
                            </li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH THANH TOÁN</a>
                            </li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH BẢO HÀNH ĐỔI
                                    TRẢ</a></li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-xl font-bold mb-4">Về chúng tôi</h3>
                        <ul class="space-y-3">
                            <li>
                                <a href="https://maps.app.goo.gl/mwqaes9hQJks8FSu5" target="_blank" class="flex">
                                    <span class="font-medium w-20">Địa chỉ:</span>
                                    <span class="text-gray-400">62 Lê Bình, Tân An, Cần Thơ</span>
                                </a>
                            </li>
                            <li>
                                <div class="flex">
                                    <span class="font-medium w-20">Giờ làm việc:</span>
                                    <span class="text-gray-400">08:00 - 21:00</span>
                                </div>
                            </li>
                            <li>
                                <a href="tel:0987.879.243" class="flex">
                                    <span class="font-medium w-20">Hotline:</span>
                                    <span class="text-gray-400">0987.879.243</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="border-t border-gray-800 my-6"></div>

                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-gray-500 text-sm mb-4 md:mb-0">
                        <p>©2025 CÔNG TY CỔ PHẦN NVB PLAY</p>
                    </div>
                    <a href="http://online.gov.vn/Home/WebDetails/129261" target="_blank">
                        <img src="https://nvbplay.vn/wp-content/uploads/2024/09/Logo-Bo-Cong-Thuong-Xanh.png"
                            alt="Bộ Công Thương" class="h-12 w-auto">
                    </a>
                </div>
            </div>
        </footer>
    </div>

    <!-- Mobile Menu -->
    <div id="main-menu"
        class="fixed inset-0 bg-white z-50 transform -translate-x-full transition duration-300 md:hidden overflow-y-auto">
        <div class="p-4">
            <div class="flex justify-between items-center mb-6">
                <img src="../img/icons/logonvb.png" height="30" width="50" class="transform scale-75">
                <button class="close-menu p-2 hover:bg-gray-100 rounded-full transition">
                    <i class="fas fa-times text-2xl text-gray-600"></i>
                </button>
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
                    <a href="login.php" class="flex items-center text-gray-700">
                        <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                            <i class="far fa-user text-xl text-gray-600"></i>
                        </div>
                        <div>
                            <div class="font-medium">Tài khoản</div>
                            <span class="text-sm text-gray-500">Đăng nhập / Đăng ký</span>
                        </div>
                    </a>
                <?php endif; ?>
            </div>
        </div>
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
            let currentValue = parseInt(qtyInput.value);
            if (currentValue > 1) {
                qtyInput.value = currentValue - 1;
            }
        }

        function increaseQty() {
            const qtyInput = document.getElementById('quantity');
            const maxQty = parseInt(qtyInput.max);
            let currentValue = parseInt(qtyInput.value);
            if (currentValue < maxQty) {
                qtyInput.value = currentValue + 1;
            }
        }

        // Add to cart via AJAX
        function addToCart() {
            const form = document.querySelector('form');
            const formData = new FormData(form);

            fetch('cart.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Đã thêm sản phẩm vào giỏ hàng!');
                    } else {
                        alert('Có lỗi xảy ra. Vui lòng thử lại!');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra. Vui lòng thử lại!');
                });
        }

        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function () {
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
        });
    </script>
</body>

</html>