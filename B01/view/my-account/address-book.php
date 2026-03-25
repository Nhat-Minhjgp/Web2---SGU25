<?php
// view/my-account/address-book.php
session_start();
require_once '../../control/connect.php';

// === KIỂM TRA ĐĂNG NHẬP BẮT BUỘC ===
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?redirect=address-book");
    exit();
}

// === CHẶN ROLE 1 (Staff/Admin) ===
if (isset($_SESSION['role']) && $_SESSION['role'] == 1) {
    session_destroy();
    setcookie('remember_user', '', time() - 3600, '/');
    header("Location: ../login.php?error=staff_not_allowed");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$errors = [];

// === XỬ LÝ CÁC ACTION ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Thêm địa chỉ mới
    if (isset($_POST['add_address'])) {
        $ten_nguoi_nhan = trim(htmlspecialchars($_POST['ten_nguoi_nhan'] ?? ''));
        $sdt_nhan = trim(preg_replace('/[^0-9]/', '', $_POST['sdt_nhan'] ?? ''));
        $tinh_thanhpho = trim(htmlspecialchars($_POST['tinh_thanhpho'] ?? ''));
        $quan = trim(htmlspecialchars($_POST['quan'] ?? ''));
        $duong = trim(htmlspecialchars($_POST['duong'] ?? ''));
        $dia_chi_chitiet = trim(htmlspecialchars($_POST['dia_chi_chitiet'] ?? ''));
        $mac_dinh = isset($_POST['mac_dinh']) ? 1 : 0;

        // Validation
        if (empty($ten_nguoi_nhan))
            $errors[] = "Tên người nhận không được để trống";
        if (empty($sdt_nhan))
            $errors[] = "Số điện thoại không được để trống";
        elseif (!preg_match('/^0[0-9]{9}$/', $sdt_nhan))
            $errors[] = "Số điện thoại phải bắt đầu bằng 0 và có 10 số";
        if (empty($dia_chi_chitiet))
            $errors[] = "Địa chỉ chi tiết không được để trống";

        if (empty($errors)) {
            // Nếu set mặc định, unset các địa chỉ khác trước
            if ($mac_dinh == 1) {
                $stmt = $conn->prepare("UPDATE diachigh SET Mac_dinh = 0 WHERE User_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            }

            $stmt = $conn->prepare("INSERT INTO diachigh (User_id, Ten_nguoi_nhan, SDT_nhan, Tinh_thanhpho, Quan, Duong, Dia_chi_chitiet, Mac_dinh) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssi", $user_id, $ten_nguoi_nhan, $sdt_nhan, $tinh_thanhpho, $quan, $duong, $dia_chi_chitiet, $mac_dinh);
            if ($stmt->execute()) {
                $success = "Thêm địa chỉ thành công!";
            } else {
                $errors[] = "Lỗi hệ thống: " . $conn->error;
            }
            $stmt->close();
        }
    }

    // Cập nhật địa chỉ
    if (isset($_POST['update_address'])) {
        $add_id = (int) $_POST['add_id'];
        $ten_nguoi_nhan = trim(htmlspecialchars($_POST['ten_nguoi_nhan'] ?? ''));
        $sdt_nhan = trim(preg_replace('/[^0-9]/', '', $_POST['sdt_nhan'] ?? ''));
        $tinh_thanhpho = trim(htmlspecialchars($_POST['tinh_thanhpho'] ?? ''));
        $quan = trim(htmlspecialchars($_POST['quan'] ?? ''));
        $duong = trim(htmlspecialchars($_POST['duong'] ?? ''));
        $dia_chi_chitiet = trim(htmlspecialchars($_POST['dia_chi_chitiet'] ?? ''));
        $mac_dinh = isset($_POST['mac_dinh']) ? 1 : 0;

        // Validation
        if (empty($ten_nguoi_nhan))
            $errors[] = "Tên người nhận không được để trống";
        if (empty($sdt_nhan))
            $errors[] = "Số điện thoại không được để trống";
        elseif (!preg_match('/^0[0-9]{9}$/', $sdt_nhan))
            $errors[] = "Số điện thoại phải bắt đầu bằng 0 và có 10 số";
        if (empty($dia_chi_chitiet))
            $errors[] = "Địa chỉ chi tiết không được để trống";

        if (empty($errors)) {
            // Kiểm tra địa chỉ thuộc về user này
            $stmt = $conn->prepare("SELECT add_id FROM diachigh WHERE add_id = ? AND User_id = ?");
            $stmt->bind_param("ii", $add_id, $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $stmt->close();

                // Nếu set mặc định, unset các địa chỉ khác trước
                if ($mac_dinh == 1) {
                    $stmt = $conn->prepare("UPDATE diachigh SET Mac_dinh = 0 WHERE User_id = ? AND add_id != ?");
                    $stmt->bind_param("ii", $user_id, $add_id);
                    $stmt->execute();
                    $stmt->close();
                }

                $stmt = $conn->prepare("UPDATE diachigh SET Ten_nguoi_nhan = ?, SDT_nhan = ?, Tinh_thanhpho = ?, Quan = ?, Duong = ?, Dia_chi_chitiet = ?, Mac_dinh = ? WHERE add_id = ? AND User_id = ?");
                $stmt->bind_param("ssssssiii", $ten_nguoi_nhan, $sdt_nhan, $tinh_thanhpho, $quan, $duong, $dia_chi_chitiet, $mac_dinh, $add_id);
                if ($stmt->execute()) {
                    $success = "Cập nhật địa chỉ thành công!";
                } else {
                    $errors[] = "Lỗi hệ thống: " . $conn->error;
                }
                $stmt->close();
            } else {
                $errors[] = "Địa chỉ không tồn tại hoặc không thuộc về bạn";
                $stmt->close();
            }
        }
    }

    // Xóa địa chỉ
    if (isset($_POST['delete_address'])) {
        $add_id = (int) $_POST['delete_id'];

        // Kiểm tra địa chỉ thuộc về user này
        $stmt = $conn->prepare("SELECT add_id FROM diachigh WHERE add_id = ? AND User_id = ?");
        $stmt->bind_param("ii", $add_id, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM diachigh WHERE add_id = ? AND User_id = ?");
            $stmt->bind_param("ii", $add_id, $user_id);
            if ($stmt->execute()) {
                $success = "Xóa địa chỉ thành công!";
            } else {
                $errors[] = "Lỗi hệ thống: " . $conn->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Địa chỉ không tồn tại hoặc không thuộc về bạn";
            $stmt->close();
        }
    }

    // Set địa chỉ mặc định
    if (isset($_POST['set_default'])) {
        $add_id = (int) $_POST['default_id'];

        // Kiểm tra địa chỉ thuộc về user này
        $stmt = $conn->prepare("SELECT add_id FROM diachigh WHERE add_id = ? AND User_id = ?");
        $stmt->bind_param("ii", $add_id, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt->close();

            // Unset tất cả địa chỉ khác
            $stmt = $conn->prepare("UPDATE diachigh SET Mac_dinh = 0 WHERE User_id = ? AND add_id != ?");
            $stmt->bind_param("ii", $user_id, $add_id);
            $stmt->execute();
            $stmt->close();

            // Set địa chỉ này làm mặc định
            $stmt = $conn->prepare("UPDATE diachigh SET Mac_dinh = 1 WHERE add_id = ? AND User_id = ?");
            $stmt->bind_param("ii", $add_id, $user_id);
            if ($stmt->execute()) {
                $success = "Đã đặt làm địa chỉ mặc định!";
            } else {
                $errors[] = "Lỗi hệ thống: " . $conn->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Địa chỉ không tồn tại hoặc không thuộc về bạn";
            $stmt->close();
        }
    }
}

// === LẤY DANH SÁCH ĐỊA CHỈ ===
$stmt = $conn->prepare("SELECT * FROM diachigh WHERE User_id = ? ORDER BY Mac_dinh DESC, add_id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$addresses = $stmt->get_result();
$stmt->close();

// Lấy thông tin user cho header
$user_info = [
    'username' => $_SESSION['username'] ?? '',
    'ho_ten' => $_SESSION['ho_ten'] ?? '',
    'email' => $_SESSION['email'] ?? ''
];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sổ địa chỉ | NVBPlay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Custom styles that can't be done with Tailwind */
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

        /* Mobile Account Menu Styles */
        #mobile-account-menu {
            transition: transform 0.3s ease-in-out;
        }

        #mobile-account-overlay {
            transition: opacity 0.3s ease-in-out;
        }

        /* Popup Styles */
        .popup-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            display: none;
        }

        .popup-overlay.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .popup-content {
            background: white;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
    </style>
    <link rel="icon" type="image/svg+xml" href="../../img/icons/favicon.png" sizes="32x32">
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
                                        <div class="flex p-4">
                                            <div class="w-64 border-r border-gray-200 pr-4">
                                                <div class="icon-box-menu active bg-red-50 rounded-lg p-3 mb-1 cursor-pointer hover:bg-red-50 transition flex items-start"
                                                    data-menu="badminton">
                                                    <div class="w-8 h-8 flex-shrink-0 mr-3">
                                                        <img src="https://nvbplay.vn/wp-content/uploads/2024/10/badminton-No.svg"
                                                            alt="Cầu Lông" class="w-full h-full">
                                                    </div>
                                                    <div>
                                                        <p class="font-bold text-red-600">Cầu Lông</p>
                                                        <p class="text-xs text-gray-500">Trang bị cầu lông chuyên nghiệp
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="icon-box-menu p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start"
                                                    data-menu="pickleball">
                                                    <div class="w-8 h-8 flex-shrink-0 mr-3">
                                                        <img src="https://nvbplay.vn/wp-content/uploads/2024/10/pickleball-No.svg"
                                                            alt="Pickleball" class="w-full h-full">
                                                    </div>
                                                    <div>
                                                        <p class="font-bold">Pickleball</p>
                                                        <p class="text-xs text-gray-500">Trang bị pickleball hàng đầu
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="icon-box-menu p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start"
                                                    data-menu="giay">
                                                    <div class="w-8 h-8 flex-shrink-0 mr-3">
                                                        <img src="https://nvbplay.vn/wp-content/uploads/2024/10/jogging-No.svg"
                                                            alt="Giày" class="w-full h-full">
                                                    </div>
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
                                <li><a href="../shop.php"
                                        class="flex items-center text-gray-700 hover:text-red-600 font-medium"><img
                                            src="../../img/icons/store.svg" class="w-5 h-5 flex-shrink-0 mr-2"><span>CỬA
                                            HÀNG</span></a></li>
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
                                <button class="search-toggle p-2"><i
                                        class="fas fa-search text-gray-700 hover:text-red-600"></i></button>
                            </div>

                            <!-- Account Dropdown -->
                            <div class="user-dropdown relative">
                                <button id="userToggle"
                                    class="flex items-center space-x-2 hover:bg-gray-100 px-3 py-2 rounded-lg transition">
                                    <img src="../../img/icons/account.svg" class="w-6 h-6" alt="Account">
                                    <span
                                        class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($user_info['username']); ?></span>
                                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                </button>
                                <div id="userMenu"
                                    class="user-menu absolute right-0 top-full mt-2 bg-white rounded-lg shadow-lg border hidden z-50 min-w-[200px]">
                                    <div class="px-4 py-3 border-b">
                                        <div class="flex items-center space-x-3">
                                            <img src="../../img/icons/account.svg" class="w-10 h-10" alt="Account">
                                            <div>
                                                <p class="text-sm font-medium">
                                                    <?php echo htmlspecialchars($user_info['ho_ten']); ?></p>
                                                <p class="text-xs text-gray-500">
                                                    <?php echo htmlspecialchars($user_info['email']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <a href="../my-account.php" class="block px-4 py-2 hover:bg-gray-50 text-sm">Tài
                                        khoản của tôi</a>
                                    <a href="./orders.php" class="block px-4 py-2 hover:bg-gray-50 text-sm">Đơn hàng</a>
                                    <a href="./address-book.php" class="block px-4 py-2 hover:bg-gray-50 text-sm">Sổ địa
                                        chỉ</a>
                                    <div class="border-t my-1"></div>
                                    <a href="../../control/logout.php"
                                        class="block px-4 py-2 hover:bg-gray-50 text-sm text-red-600">Đăng xuất</a>
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
                            <button class="search-toggle p-1"><i class="fas fa-search text-xl"></i></button>
                            <a href="../my-account.php" class="p-1"><img src="../../img/icons/account.svg"
                                    class="w-6 h-6" alt="Account"></a>
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
                        <div class="flex items-center">
                            <h1 class="text-lg font-semibold">Sổ địa chỉ</h1>
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
                        <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 mb-4 md:mb-6 relative">
                            <div class="flex items-center space-x-3 md:space-x-4">
                                <img src="../../img/icons/account.svg" alt="User avatar"
                                    class="w-14 h-14 md:w-16 md:h-16 rounded-full border-2 border-gray-200">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <h3 class="font-semibold text-gray-900">
                                                <?php echo htmlspecialchars($user_info['ho_ten']); ?></h3>
                                        </div>
                                        <div class="rank">
                                            <img src="../../img/icons/subscription.svg" class="w-25 h-25">
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($user_info['email']); ?></p>
                                </div>
                            </div>
                            <button class="lg:hidden absolute top-4 right-4 p-2" onclick="toggleMobileAccountMenu()">
                                <img src="https://nvbplay.vn/wp-content/themes/nvbplayvn/assets/icon/close.svg"
                                    alt="Close" class="w-5 h-5">
                            </button>
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
                                        class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-[#FF3F1A] transition">
                                        <img src="../../img/icons/clipboard.svg" class="w-5 h-5 mr-3" alt="Orders">
                                        <span class="text-sm md:text-base">Quản lý đơn hàng</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="./address-book.php"
                                        class="flex items-center px-4 py-3 text-[#FF3F1A] bg-red-50 font-medium border-l-4 border-[#FF3F1A]">
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
                        <div class="woocommerce">
                            <div class="woocommerce-MyAccount-content">
                                <div class="woocommerce-notices-wrapper">
                                    <!-- Thông báo thành công -->
                                    <?php if ($success): ?>
                                        <div class="alert-success p-3 rounded mb-4 text-sm">
                                            <i
                                                class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Thông báo lỗi -->
                                    <?php if (!empty($errors)): ?>
                                        <div class="alert-error p-3 rounded mb-4 text-sm">
                                            <ul class="list-disc list-inside">
                                                <?php foreach ($errors as $error): ?>
                                                    <li><?php echo htmlspecialchars($error); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div id="address_book_section">
                                    <!-- Address List -->
                                    <div class="address-list">
                                        <div class="is-page">
                                            <div class="is-page inner-top-large">
                                                <div class="affiliate-header">
                                                    <div class="affiliate-header-title">
                                                        <div class="flex items-center justify-between mb-6">
                                                            <div class="flex items-center gap-2">
                                                                <h1 class="text-xl font-semibold">Sổ địa chỉ</h1>
                                                            </div>
                                                            <div class="flex items-center gap-3">
                                                                <button onclick="openAddPopup()"
                                                                    class="inline-flex items-center px-4 py-2 bg-[#FF3F1A] text-white rounded-lg hover:bg-red-700 transition cursor-pointer">
                                                                    <i class="fas fa-plus mr-2"></i>Thêm địa chỉ mới
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="inner-center mt-6">
                                            <!-- Danh sách địa chỉ -->
                                            <div class="space-y-4">
                                                <?php if ($addresses->num_rows > 0): ?>
                                                    <?php while ($addr = $addresses->fetch_assoc()): ?>
                                                        <div
                                                            class="address-item border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                                            <div class="flex items-start justify-between">
                                                                <div class="flex-1 relative">
                                                                    <div class="flex items-center gap-3 mb-2">
                                                                        <span
                                                                            class="font-semibold text-gray-900"><?php echo htmlspecialchars($addr['Ten_nguoi_nhan']); ?></span>
                                                                        <span class="text-sm text-gray-500">|
                                                                            <?php echo htmlspecialchars($addr['SDT_nhan']); ?></span>
                                                                        <?php if ($addr['Mac_dinh'] == 1): ?>
                                                                            <span
                                                                                class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full text-center">Mặc
                                                                                định</span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <p class="text-gray-700 mb-2">
                                                                        <?php
                                                                        $full_address = [];
                                                                        if (!empty($addr['Duong']))
                                                                            $full_address[] = $addr['Duong'];
                                                                        if (!empty($addr['Quan']))
                                                                            $full_address[] = $addr['Quan'];
                                                                        if (!empty($addr['Tinh_thanhpho']))
                                                                            $full_address[] = $addr['Tinh_thanhpho'];
                                                                        if (!empty($addr['Dia_chi_chitiet']))
                                                                            $full_address[] = $addr['Dia_chi_chitiet'];
                                                                        echo htmlspecialchars(implode(', ', $full_address));
                                                                        ?>
                                                                    </p>
                                                                </div>
                                                                <div class="flex items-center gap-2 relative">
                                                                    <?php if ($addr['Mac_dinh'] != 1): ?>
                                                                        <button
                                                                            onclick="setDefaultAddress(<?php echo $addr['add_id']; ?>)"
                                                                            class="p-2 text-gray-500 hover:text-blue-600 transition"
                                                                            title="Đặt làm mặc định">
                                                                            <i class="fas fa-star"></i>
                                                                        </button>
                                                                    <?php endif; ?>
                                                                    <button
                                                                        onclick="openEditPopup(<?php echo htmlspecialchars(json_encode($addr)); ?>)"
                                                                        class="p-2 text-gray-500 hover:text-blue-600 transition"
                                                                        title="Sửa">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button
                                                                        onclick="deleteAddress(<?php echo $addr['add_id']; ?>)"
                                                                        class="p-2 text-gray-500 hover:text-red-600 transition"
                                                                        title="Xóa">
                                                                        <i class="fas fa-trash-alt"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <p class="addressbook text-center py-8 text-gray-500">Chưa có địa chỉ
                                                        nào</p>
                                                <?php endif; ?>
                                            </div>
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
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                    <div class="flex items-center space-x-3">
                        <img src="../../img/icons/account.svg" alt="User avatar"
                            class="w-12 h-12 rounded-full border-2 border-gray-200">
                        <div>
                            <h3 class="font-semibold text-gray-900">
                                <?php echo htmlspecialchars($user_info['ho_ten']); ?></h3>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($user_info['email']); ?></p>
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
                                class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">
                                <img src="../../img/icons/clipboard.svg" class="w-5 h-5 mr-3" alt="Orders">
                                <span>Quản lý đơn hàng</span>
                            </a>
                        </li>
                        <li>
                            <a href="./address-book.php"
                                class="flex items-center px-4 py-3 bg-red-50 text-[#FF3F1A] font-medium rounded-lg">
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

        <!-- Add/Edit Address Popup -->
        <div id="addressPopup" class="popup-overlay">
            <div class="popup-content p-6">
                <div class="flex items-center justify-between mb-4 border-b pb-3">
                    <h3 id="popupTitle" class="text-lg font-semibold">Thêm địa chỉ mới</h3>
                    <button onclick="closePopup()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <form method="POST" action="" id="addressForm">
                    <input type="hidden" id="add_id" name="add_id" value="">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tên người nhận <span
                                    class="text-red-500">*</span></label>
                            <input type="text" id="ten_nguoi_nhan" name="ten_nguoi_nhan"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                                oninput="this.value = this.value.replace(/[0-9]/g, '')" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại <span
                                    class="text-red-500">*</span></label>
                            <input type="text" id="sdt_nhan" name="sdt_nhan"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                                pattern="^0[0-9]{9}$"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 10)" maxlength="10"
                                required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tỉnh/Thành phố</label>
                            <input type="text" id="tinh_thanhpho" name="tinh_thanhpho"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quận/Huyện</label>
                            <input type="text" id="quan" name="quan"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Đường</label>
                            <input type="text" id="duong" name="duong"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ chi tiết <span
                                    class="text-red-500">*</span></label>
                            <textarea id="dia_chi_chitiet" name="dia_chi_chitiet" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                                required></textarea>
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" id="mac_dinh" name="mac_dinh" class="mr-2">
                                <span class="text-sm text-gray-700">Đặt làm địa chỉ mặc định</span>
                            </label>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t">
                        <button type="button" onclick="closePopup()"
                            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">Hủy</button>
                        <button type="submit" id="submitBtn" name="add_address"
                            class="px-4 py-2 bg-[#FF3F1A] text-white rounded-lg hover:bg-red-700 transition">Thêm địa
                            chỉ</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Popup -->
        <div id="deletePopup" class="popup-overlay">
            <div class="popup-content p-6 max-w-md">
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-4"></i>
                    <h3 class="text-lg font-semibold mb-2">Xác nhận xóa</h3>
                    <p class="text-gray-600 mb-6">Bạn có chắc chắn muốn xóa địa chỉ này không?</p>
                    <form method="POST" action="" id="deleteForm">
                        <input type="hidden" id="delete_id" name="delete_id" value="">
                        <div class="flex items-center justify-center gap-3">
                            <button type="button" onclick="closeDeletePopup()"
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">Hủy</button>
                            <button type="submit" name="delete_address"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">Xóa</button>
                        </div>
                    </form>
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
                        <div class="font-medium"><?php echo htmlspecialchars($user_info['username']); ?></div><span
                            class="text-sm text-gray-500"><?php echo htmlspecialchars($user_info['email']); ?></span>
                    </div>
                </div>
                <a href="../../control/logout.php" class="text-red-600 text-sm font-medium">Đăng xuất</a>
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
                    userMenu.classList.toggle('hidden');
                });
                document.addEventListener('click', function (e) {
                    if (!userToggle.contains(e.target) && !userMenu.contains(e.target)) {
                        userMenu.classList.add('hidden');
                    }
                });
            }

            // Mobile menu toggle
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
        });

        // === MOBILE ACCOUNT MENU TOGGLE ===
        function toggleMobileAccountMenu() {
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
        }

        // === POPUP FUNCTIONS ===
        function openAddPopup() {
            document.getElementById('popupTitle').textContent = 'Thêm địa chỉ mới';
            document.getElementById('addressForm').reset();
            document.getElementById('add_id').value = '';
            document.getElementById('submitBtn').name = 'add_address';
            document.getElementById('submitBtn').textContent = 'Thêm địa chỉ';
            document.getElementById('addressPopup').classList.add('active');
        }

        function openEditPopup(addr) {
            document.getElementById('popupTitle').textContent = 'Cập nhật địa chỉ';
            document.getElementById('add_id').value = addr.add_id;
            document.getElementById('ten_nguoi_nhan').value = addr.Ten_nguoi_nhan;
            document.getElementById('sdt_nhan').value = addr.SDT_nhan;
            document.getElementById('tinh_thanhpho').value = addr.Tinh_thanhpho || '';
            document.getElementById('quan').value = addr.Quan || '';
            document.getElementById('duong').value = addr.Duong || '';
            document.getElementById('dia_chi_chitiet').value = addr.Dia_chi_chitiet || '';
            document.getElementById('mac_dinh').checked = addr.Mac_dinh == 1;
            document.getElementById('submitBtn').name = 'update_address';
            document.getElementById('submitBtn').textContent = 'Cập nhật';
            document.getElementById('addressPopup').classList.add('active');
        }

        function closePopup() {
            document.getElementById('addressPopup').classList.remove('active');
        }

        function deleteAddress(addId) {
            document.getElementById('delete_id').value = addId;
            document.getElementById('deletePopup').classList.add('active');
        }

        function closeDeletePopup() {
            document.getElementById('deletePopup').classList.remove('active');
        }

        function setDefaultAddress(addId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="default_id" value="' + addId + '"><input type="hidden" name="set_default" value="1">';
            document.body.appendChild(form);
            form.submit();
        }

        // Close popup when clicking outside
        document.getElementById('addressPopup').addEventListener('click', function (e) {
            if (e.target === this) closePopup();
        });

        document.getElementById('deletePopup').addEventListener('click', function (e) {
            if (e.target === this) closeDeletePopup();
        });
    </script>

    <!-- JavaScript Menu (GIỮ NGUYÊN) -->
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