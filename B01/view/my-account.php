<?php
// view/my-account.php
session_start();
require_once '../control/connect.php';

// === KIỂM TRA ĐĂNG NHẬP BẮT BUỘC ===
if (!isset($_SESSION['user_id'])) {
    // Chưa đăng nhập → chuyển về trang đăng nhập
    header("Location: login.php?redirect=my-account");
    exit();
}

// === CHẶN ROLE 1 (Staff/Admin) ===
if (isset($_SESSION['role']) && $_SESSION['role'] == 1) {
    session_destroy();
    setcookie('remember_user', '', time() - 3600, '/');
    header("Location: login.php?error=staff_not_allowed");
    exit();
}

// === LẤY THÔNG TIN USER TỪ DATABASE (Cập nhật mới nhất) ===
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT User_id, Username, Ho_ten, email, SDT, role, status, created_at FROM users WHERE User_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    // User không tồn tại trong DB → logout
    session_destroy();
    header("Location: login.php");
    exit();
}
$stmt->close();

// Cập nhật session với data mới nhất từ DB
$_SESSION['username'] = $user['Username'];
$_SESSION['ho_ten'] = $user['Ho_ten'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];

// === XỬ LÝ CẬP NHẬT THÔNG TIN (POST) ===
$update_success = '';
$update_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
    $ho_ten = trim(htmlspecialchars($_POST['ho_ten'] ?? ''));
    $sdt = trim(preg_replace('/[^0-9]/', '', $_POST['sdt'] ?? ''));

    // Validation
    if (empty($ho_ten)) {
        $update_errors[] = "Họ và tên không được để trống";
    }
    if (empty($sdt)) {
        $update_errors[] = "Số điện thoại không được để trống";
    } elseif (!preg_match('/^0[0-9]{9}$/', $sdt)) {
        $update_errors[] = "Số điện thoại phải bắt đầu bằng 0 và có 10 số";
    }

    // Update database nếu không có lỗi
    if (empty($update_errors)) {
        try {
            $stmt = $conn->prepare("UPDATE users SET Ho_ten = ?, SDT = ? WHERE User_id = ?");
            $stmt->bind_param("ssi", $ho_ten, $sdt, $user_id);
            if ($stmt->execute()) {
                $update_success = "Cập nhật thông tin thành công!";
                // Cập nhật lại data hiển thị
                $user['Ho_ten'] = $ho_ten;
                $user['SDT'] = $sdt;
                $_SESSION['ho_ten'] = $ho_ten;
            } else {
                $update_errors[] = "Lỗi hệ thống: " . $conn->error;
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Update Error: " . $e->getMessage());
            $update_errors[] = "Lỗi hệ thống. Vui lòng thử lại sau.";
        }
    }
}

// Format ngày tạo tài khoản
$created_date = !empty($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : 'N/A';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản của tôi | NVBPlay</title>
    <meta name="description" content="Quản lý thông tin tài khoản, đơn hàng và địa chỉ của bạn tại NVBPlay.">
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

        .input-valid {
            border-color: #16a34a !important;
        }

        .input-invalid {
            border-color: #dc2626 !important;
        }

        .error-text {
            color: #dc2626;
            font-size: 11px;
            margin-top: 2px;
            display: none;
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
                                        <div class="flex p-4">
                                            <div class="w-64 border-r border-gray-200 pr-4">
                                                <div class="icon-box-menu active bg-red-50 rounded-lg p-3 mb-1 cursor-pointer hover:bg-red-50 transition flex items-start"
                                                    data-menu="badminton">
                                                    <div class="w-8 h-8 flex-shrink-0 mr-3"><img
                                                            src="https://nvbplay.vn/wp-content/uploads/2024/10/badminton-No.svg"
                                                            alt="Cầu Lông" class="w-full h-full"></div>
                                                    <div>
                                                        <p class="font-bold text-red-600">Cầu Lông</p>
                                                        <p class="text-xs text-gray-500">Trang bị cầu lông chuyên nghiệp
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="icon-box-menu p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start"
                                                    data-menu="pickleball">
                                                    <div class="w-8 h-8 flex-shrink-0 mr-3"><img
                                                            src="https://nvbplay.vn/wp-content/uploads/2024/10/pickleball-No.svg"
                                                            alt="Pickleball" class="w-full h-full"></div>
                                                    <div>
                                                        <p class="font-bold">Pickleball</p>
                                                        <p class="text-xs text-gray-500">Trang bị pickleball hàng đầu
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="icon-box-menu p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start"
                                                    data-menu="giay">
                                                    <div class="w-8 h-8 flex-shrink-0 mr-3"><img
                                                            src="https://nvbplay.vn/wp-content/uploads/2024/10/jogging-No.svg"
                                                            alt="Giày" class="w-full h-full"></div>
                                                    <div>
                                                        <p class="font-bold">Giày</p>
                                                        <p class="text-xs text-gray-500">Giày thể thao tối ưu hoá vận
                                                            động</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex-1 pl-4">
                                                <div id="content-badminton" class="menu-content"></div>
                                                <div id="content-pickleball" class="menu-content hidden"></div>
                                                <div id="content-giay" class="menu-content hidden"></div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li><a href="shop.php"
                                        class="flex items-center text-gray-700 hover:text-red-600 font-medium"><img
                                            src="../img/icons/store.svg" class="w-5 h-5 flex-shrink-0 mr-2"><span>CỬA
                                            HÀNG</span></a></li>
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

                            <!-- Account Dropdown (ĐÃ ĐĂNG NHẬP) -->
                            <div class="user-dropdown">
                                <button id="userToggle"
                                    class="flex items-center space-x-2 hover:bg-gray-100 px-3 py-2 rounded-lg transition">
                                    <img src="../img/icons/account.svg" class="w-6 h-6" alt="Account">
                                    <span
                                        class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($user['Username']); ?></span>
                                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                </button>
                                <!-- User Dropdown Menu -->
                                <div id="userMenu" class="user-menu">
                                    <div class="px-4 py-3 border-b border-gray-100">
                                        <div class="flex items-center space-x-3">
                                            <img src="../img/icons/account.svg" class="w-10 h-10" alt="Account">
                                            <div>
                                                <p class="text-sm font-medium text-gray-800">
                                                    <?php echo htmlspecialchars($user['Ho_ten']); ?>
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    <?php echo htmlspecialchars($user['email']); ?></p>
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
                                    class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">0</span>
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
                                    class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">0</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="flex-1">
            <!-- Mobile Account Header (visible on mobile only) -->
            <div class="lg:hidden bg-white border-b border-gray-200">
                <div class="container mx-auto px-4 py-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <h1 class="text-lg font-semibold">Tài khoản của tôi</h1>
                        </div>
                        <button class="show-menu p-2" onclick="toggleMobileAccountMenu()">
                            <img src="https://nvbplay.vn/wp-content/themes/nvbplayvn/assets/icon/dot-line.svg"
                                alt="Menu" class="w-6 h-6">
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="container mx-auto px-4 py-4 md:py-8">
                <div class="flex flex-col lg:flex-row gap-4 md:gap-8">
                    <!-- Sidebar -->
                    <div class="lg:w-1/4">
                        <!-- User Info Card -->
                        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                            <div class="flex items-center space-x-4">
                                <!-- Avatar -->
                                <img src="../img/icons/account.svg" alt="User avatar"
                                    class="w-16 h-16 rounded-full border-2 border-gray-200">
                                <!-- User Info -->
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <h3 class="font-semibold text-gray-900">
                                                <?php echo htmlspecialchars($user['Ho_ten']); ?></h3>
                                        </div>
                                        <!-- Rank Icon -->
                                        <div class="rank">
                                            <img src="../img/icons/subscription.svg" class="w-25 h-25" alt="Rank">
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-500">Thành viên từ <?php echo $created_date; ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Menu -->
                        <nav class="bg-white rounded-lg shadow-sm overflow-hidden hidden lg:block">
                            <ul class="divide-y divide-gray-200">
                                <li>
                                    <a href="./my-account.php"
                                        class="flex items-center px-4 py-3 bg-red-50 text-[#FF3F1A] font-medium border-l-4 border-[#FF3F1A]">
                                        <img src="../img/icons/account.svg" class="w-5 h-5 mr-3" alt="Account">
                                        Thông tin tài khoản
                                    </a>
                                </li>
                                <li>
                                    <a href="./my-account/orders.php"
                                        class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-[#FF3F1A] transition">
                                        <img src="../img/icons/clipboard.svg" class="w-5 h-5 mr-3" alt="Orders">
                                        Quản lý đơn hàng
                                    </a>
                                </li>
                                <li>
                                    <a href="./my-account/address-book.php"
                                        class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-[#FF3F1A] transition">
                                        <img src="../img/icons/diachi.svg" class="w-5 h-5 mr-3" alt="Address">
                                        Sổ địa chỉ
                                    </a>
                                </li>
                                <li>
                                    <a href="../control/logout.php"
                                        class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-[#FF3F1A] transition">
                                        <img src="../img/icons/logout.svg" class="w-5 h-5 mr-3" alt="Logout">
                                        Đăng xuất
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>

                    <!-- Main Content Area -->
                    <div class="lg:w-3/4">
                        <!-- Edit Account Form -->
                        <div class="bg-white rounded-lg shadow-sm p-6 md:p-8">
                            <h3 class="text-xl font-semibold text-gray-900 mb-6">Thông tin cá nhân</h3>

                            <!-- Thông báo lỗi -->
                            <?php if (!empty($update_errors)): ?>
                                <div class="alert-error p-3 rounded mb-4 text-sm">
                                    <ul class="list-disc list-inside">
                                        <?php foreach ($update_errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <!-- Thông báo thành công -->
                            <?php if ($update_success): ?>
                                <div class="alert-success p-3 rounded mb-4 text-sm">
                                    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($update_success); ?>
                                </div>
                            <?php endif; ?>

                            <form class="space-y-6" method="POST" action="">
                                <!-- Họ và tên (RENDER TỪ DATABASE) -->
                                <div>
                                    <label for="ho_ten" class="block text-sm font-medium text-gray-700 mb-1">
                                        Họ và tên <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="ho_ten" name="ho_ten"
                                        value="<?php echo htmlspecialchars($user['Ho_ten']); ?>" maxlength="50"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition"
                                        oninput="this.value = this.value.replace(/[0-9]/g, '')" required>
                                </div>

                                <!-- Số điện thoại (RENDER TỪ DATABASE) -->
                                <div>
                                    <label for="sdt" class="block text-sm font-medium text-gray-700 mb-1">
                                        Số điện thoại <span class="text-red-500">*</span>
                                    </label>
                                    <input type="tel" id="sdt" name="sdt"
                                        value="<?php echo htmlspecialchars($user['SDT'] ?? ''); ?>" maxlength="10"
                                        pattern="^0[0-9]{9}$"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                                </div>

                                <!-- Email (READONLY - không chỉnh sửa) -->
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                        Địa chỉ email <span class="text-red-500">*</span>
                                    </label>
                                    <input type="email" id="email" name="email"
                                        value="<?php echo htmlspecialchars($user['email']); ?>" readonly
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed">
                                    <p class="text-xs text-gray-400 mt-1">Email không thể thay đổi. Liên hệ hỗ trợ nếu
                                        cần.</p>
                                </div>

                                <!-- Tên đăng nhập (READONLY) -->
                                <div>
                                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                                        Tên đăng nhập
                                    </label>
                                    <input type="text" id="username"
                                        value="<?php echo htmlspecialchars($user['Username']); ?>" readonly
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed">
                                </div>

                                <!-- Submit Button -->
                                <div class="pt-4 flex justify-end">
                                    <button type="submit" name="update_info"
                                        class="px-6 py-3 bg-[#FF3F1A] text-white font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition whitespace-nowrap">
                                        Cập nhật thông tin
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Account Stats Section -->
                        <div class="bg-white rounded-lg shadow-sm p-6 md:p-8 mt-6">
                            <h3 class="text-xl font-semibold text-gray-900 mb-4">Thống kê tài khoản</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="text-center p-4 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold text-red-600">0</div>
                                    <div class="text-sm text-gray-600">Đơn hàng</div>
                                </div>
                                <div class="text-center p-4 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold text-red-600">0</div>
                                    <div class="text-sm text-gray-600">Đã giao</div>
                                </div>
                                <div class="text-center p-4 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold text-red-600">0</div>
                                    <div class="text-sm text-gray-600">Đang xử lý</div>
                                </div>
                                <div class="text-center p-4 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold text-red-600">0₫</div>
                                    <div class="text-sm text-gray-600">Tổng chi tiêu</div>
                                </div>
                            </div>
                        </div>
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
                <!-- Mobile Menu Header -->
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                    <div class="flex items-center space-x-3">
                        <img src="../img/icons/account.svg" alt="User avatar"
                            class="w-12 h-12 rounded-full border-2 border-gray-200">
                        <div>
                            <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($user['Ho_ten']); ?>
                            </h3>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                    </div>
                    <button onclick="toggleMobileAccountMenu()" class="p-2">
                        <i class="fas fa-times text-xl text-gray-600"></i>
                    </button>
                </div>
                <!-- Mobile Menu Navigation -->
                <nav>
                    <ul class="space-y-2">
                        <li>
                            <a href="./my-account.php"
                                class="flex items-center px-4 py-3 bg-red-50 text-[#FF3F1A] font-medium rounded-lg">
                                <img src="../img/icons/account.svg" class="w-5 h-5 mr-3" alt="Account">
                                <span>Thông tin tài khoản</span>
                            </a>
                        </li>
                        <li>
                            <a href="./my-account/orders.php"
                                class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">
                                <img src="../img/icons/clipboard.svg" class="w-5 h-5 mr-3" alt="Orders">
                                <span>Quản lý đơn hàng</span>
                            </a>
                        </li>
                        <li>
                            <a href="./my-account/address-book.php"
                                class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">
                                <img src="../img/icons/diachi.svg" class="w-5 h-5 mr-3" alt="Address">
                                <span>Sổ địa chỉ</span>
                            </a>
                        </li>
                        <li>
                            <a href="../control/logout.php"
                                class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">
                                <img src="../img/icons/logout.svg" class="w-5 h-5 mr-3" alt="Logout">
                                <span>Đăng xuất</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
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
                        <li><a href="#" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH BẢO MẬT</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH THANH TOÁN</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH BẢO HÀNH ĐỔI
                                TRẢ</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH VẬN CHUYỂN</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">THOẢ THUẬN SỬ DỤNG</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Về chúng tôi</h3>
                    <ul class="space-y-3">
                        <li><a href="https://maps.app.goo.gl/mwqaes9hQJks8FSu5" target="_blank" class="flex"><span
                                    class="font-medium w-20">Địa chỉ:</span><span class="text-gray-400">62 Lê Bình, Tân
                                    An, Cần Thơ</span></a></li>
                        <li>
                            <div class="flex"><span class="font-medium w-20">Giờ làm việc:</span><span
                                    class="text-gray-400">08:00 - 21:00</span></div>
                        </li>
                        <li><a href="tel:0987.879.243" class="flex"><span class="font-medium w-20">Hotline:</span><span
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

    <!-- Mobile Menu (GIỮ NGUYÊN) -->
    <div id="main-menu"
        class="fixed inset-0 bg-white z-50 transform -translate-x-full transition duration-300 md:hidden overflow-y-auto">
        <div class="p-4">
            <div class="flex justify-between items-center mb-6">
                <img src="../img/icons/logonvb.png" height="30" width="50" class="transform scale-75">
                <button class="close-menu p-2 hover:bg-gray-100 rounded-full transition"><i
                        class="fas fa-times text-2xl text-gray-600"></i></button>
            </div>
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                <div class="flex items-center text-gray-700">
                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                        <img src="../img/icons/account.svg" class="w-6 h-6" alt="Account">
                    </div>
                    <div>
                        <div class="font-medium"><?php echo htmlspecialchars($user['Username']); ?></div>
                        <span class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                </div>
                <a href="../control/logout.php" class="text-red-600 text-sm font-medium">Đăng xuất</a>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
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

            // === MOBILE ACCOUNT MENU TOGGLE ===
            window.toggleMobileAccountMenu = function () {
                const mobileMenu = document.getElementById('mobile-account-menu');
                const overlay = document.getElementById('mobile-account-overlay');
                const body = document.body;
                if (mobileMenu.classList.contains('-translate-x-full')) {
                    mobileMenu.classList.remove('-translate-x-full');
                    overlay.classList.remove('hidden');
                    body.style.overflow = 'hidden';
                } else {
                    mobileMenu.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                    body.style.overflow = '';
                }
            };

            // Close mobile menu when clicking outside
            document.addEventListener('click', function (e) {
                const mobileMenu = document.getElementById('mobile-account-menu');
                const overlay = document.getElementById('mobile-account-overlay');
                const showMenuBtn = document.querySelector('.show-menu');
                if (mobileMenu && !mobileMenu.contains(e.target) &&
                    !overlay.contains(e.target) &&
                    showMenuBtn && !showMenuBtn.contains(e.target)) {
                    mobileMenu.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                    document.body.style.overflow = '';
                }
            });

            // === PHONE INPUT VALIDATION ===
            const sdtInput = document.getElementById('sdt');
            if (sdtInput) {
                sdtInput.addEventListener('input', function () {
                    // Chỉ giữ số
                    this.value = this.value.replace(/[^0-9]/g, '');
                    // Tự động thêm số 0 ở đầu nếu chưa có
                    if (this.value.length > 0 && this.value.charAt(0) !== '0') {
                        this.value = '0' + this.value;
                    }
                    // Giới hạn 10 số
                    if (this.value.length > 10) {
                        this.value = this.value.substring(0, 10);
                    }
                });
            }

            // === NAME INPUT VALIDATION ===
            const hoTenInput = document.getElementById('ho_ten');
            if (hoTenInput) {
                hoTenInput.addEventListener('input', function () {
                    // Chỉ giữ chữ và khoảng trắng
                    this.value = this.value.replace(/[0-9]/g, '');
                });
            }

            // === MOBILE MENU TOGGLE (GIỮ NGUYÊN) ===
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

    <!-- JavaScript Menu (GIỮ NGUYÊN 100%) -->
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