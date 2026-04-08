<?php
// view/my-account/address-book.php
session_start();
require_once '../../control/connect.php';
require_once '../../control/check_remember_login.php';

// === ✅ HELPER: CHECK SQL INJECTION (ĐỂ ĐẦU FILE) ===
function checkSQLInjectionInput($value)
{
    $patterns = [
        '/(\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b|\bUNION\b|\bTRUNCATE\b|\bEXEC\b)/i',
        '/(--|\/\*|\*\/|#)/',
        '/(\bOR\b|\bAND\b)\s*([\'"]|\d+=\d+)/i',
        '/;/',
        '/\bxp_\w+/i',
        '/(\bWAITFOR\b|\bBENCHMARK\b|\bSLEEP\b)/i'
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $value))
            return true;
    }
    return false;
}

$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}


if (!isset($_SESSION['user_id'])) {
    $_SESSION['login_required'] = 'Vui lòng đăng nhập để tiếp tục';
    header("Location: ../login.php?redirect=my-account");
    exit();
}

// === CHẶN ROLE 1 (Staff/Admin) ===
if (isset($_SESSION['role']) && $_SESSION['role'] == 1) {
    session_destroy();
    $secure_cookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] == 443);
    setcookie('auth_remember', '', time() - 3600, '/', '', $secure_cookie, true);
    header("Location: login.php?error=staff_not_allowed");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$errors = [];
$is_logged_in = true;
$user_info = [
    'user_id' => $_SESSION['user_id'] ?? '',
    'username' => $_SESSION['username'] ?? '',
    'ho_ten' => $_SESSION['ho_ten'] ?? '',
    'email' => $_SESSION['email'] ?? '',
    'role' => $_SESSION['role'] ?? 0
];

// === XỬ LÝ CÁC ACTION ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ✅ THÊM ĐỊA CHỈ MỚI
    if (isset($_POST['add_address'])) {
        // ✅ 1. CHECK SQL INJECTION TRƯỚC KHI SANITIZE (TRÊN RAW DATA)
        $raw_inputs = [
            'ten_nguoi_nhan' => $_POST['ten_nguoi_nhan'] ?? '',
            'tinh_thanhpho' => $_POST['tinh_thanhpho'] ?? '',
            'quan' => $_POST['quan'] ?? '',
            'duong' => $_POST['duong'] ?? '',
            'dia_chi_chitiet' => $_POST['dia_chi_chitiet'] ?? ''
        ];

        foreach ($raw_inputs as $field => $value) {
            if (checkSQLInjectionInput($value)) {
                $errors[] = "Phát hiện ký tự không an toàn trong trường " . $field;
                break;
            }
        }

        // ✅ 2. SANITIZE SAU KHI CHECK
        $ten_nguoi_nhan = trim(htmlspecialchars($_POST['ten_nguoi_nhan'] ?? '', ENT_QUOTES, 'UTF-8'));
        $sdt_nhan = trim(preg_replace('/[^0-9]/', '', $_POST['sdt_nhan'] ?? ''));
        $tinh_thanhpho = trim(htmlspecialchars($_POST['tinh_thanhpho'] ?? '', ENT_QUOTES, 'UTF-8'));
        $quan = trim(htmlspecialchars($_POST['quan'] ?? '', ENT_QUOTES, 'UTF-8'));
        $duong = trim(htmlspecialchars($_POST['duong'] ?? '', ENT_QUOTES, 'UTF-8'));
        $dia_chi_chitiet = trim(htmlspecialchars($_POST['dia_chi_chitiet'] ?? '', ENT_QUOTES, 'UTF-8'));
        $mac_dinh = isset($_POST['mac_dinh']) ? 1 : 0;

        // ✅ 3. VALIDATION
        if (empty($errors)) {
            if (empty($ten_nguoi_nhan))
                $errors[] = "Tên người nhận không được để trống";
            if (empty($sdt_nhan))
                $errors[] = "Số điện thoại không được để trống";
            elseif (!preg_match('/^0[0-9]{9}$/', $sdt_nhan))
                $errors[] = "Số điện thoại phải bắt đầu bằng 0 và có 10 số";
            if (empty($dia_chi_chitiet))
                $errors[] = "Địa chỉ chi tiết không được để trống";
        }

        // ✅ 4. INSERT
        if (empty($errors)) {
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

        // Check SQL Injection
        if (
            checkSQLInjectionInput($ten_nguoi_nhan) ||
            checkSQLInjectionInput($tinh_thanhpho) ||
            checkSQLInjectionInput($quan) ||
            checkSQLInjectionInput($duong) ||
            checkSQLInjectionInput($dia_chi_chitiet)
        ) {
            $errors[] = "Phát hiện ký tự không an toàn. Vui lòng nhập lại.";
        }

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
                // ✅ SỬA DÒNG NÀY - THÊM $user_id VÀO CUỐI
                $stmt = $conn->prepare("UPDATE diachigh SET Ten_nguoi_nhan = ?, SDT_nhan = ?, Tinh_thanhpho = ?, Quan = ?, Duong = ?, Dia_chi_chitiet = ?, Mac_dinh = ? WHERE add_id = ? AND User_id = ?");
                $stmt->bind_param("ssssssiii", $ten_nguoi_nhan, $sdt_nhan, $tinh_thanhpho, $quan, $duong, $dia_chi_chitiet, $mac_dinh, $add_id, $user_id);
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
        $stmt = $conn->prepare("SELECT add_id FROM diachigh WHERE add_id = ? AND User_id = ?");
        $stmt->bind_param("ii", $add_id, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt->close();
            $stmt = $conn->prepare("UPDATE diachigh SET Mac_dinh = 0 WHERE User_id = ? AND add_id != ?");
            $stmt->bind_param("ii", $user_id, $add_id);
            $stmt->execute();
            $stmt->close();
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

        /* SQL Injection Warning */
        .sql-injection-warning {
            background-color: #fef2f2;
            border: 2px solid #dc2626;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: none;
        }

        .sql-injection-warning i {
            margin-right: 8px;
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
                                        <img src="../../img/icons/menu.svg" class="w-6 h-6" alt="menu">
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
                                                        <img src="../../img/icons/menu.svg" class="w-5 h-5 mr-2"
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
                                                                        <img src="../../img/icons/logo-caulong.png"
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
                                                                        <img src="../../img/icons/logo-pickleball.png"
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
                                                                        <img src="../../img/icons/logo-giay.png"
                                                                            alt="Giày" class="w-full h-full">
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
                                                                            <a href="../shop.php"
                                                                                class="text-sm text-red-600 hover:text-red-700 flex items-center">
                                                                                Xem tất cả <i
                                                                                    class="fas fa-chevron-right ml-1 text-xs"></i>
                                                                            </a>
                                                                        </div>
                                                                        <div class="grid grid-cols-4 gap-2">
                                                                            <!-- YONEX -->
                                                                            <a href="../shop.php?thuonghieu[]=yonex"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../../img/icons/logo-yonex.webp"
                                                                                        alt="Yonex"
                                                                                        class="w-full h-full object-contain">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">YONEX</span>
                                                                            </a>

                                                                            <!-- ADIDAS -->
                                                                            <a href="../shop.php?thuonghieu[]=adidas"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../../img/icons/logo-adidas.webp"
                                                                                        alt="Adidas"
                                                                                        class="w-full h-full object-contain">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">ADIDAS</span>
                                                                            </a>

                                                                            <!-- LI-NING -->
                                                                            <a href="../shop.php?thuonghieu[]=li-ning"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../../img/icons/Logo-li-ning.png"
                                                                                        alt="Li-Ning"
                                                                                        class="w-full h-full object-contain">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">LI-NING</span>
                                                                            </a>

                                                                            <!-- VICTOR -->
                                                                            <a href="../shop.php?thuonghieu[]=victor"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../../img/icons/logo-victor.png"
                                                                                        alt="Victor"
                                                                                        class="w-full h-full object-contain">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">VICTOR</span>
                                                                            </a>

                                                                            <!-- KAMITO -->
                                                                            <a href="../shop.php?thuonghieu[]=kamito"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../../img/icons/logo-kamito.png"
                                                                                        alt="KAMITO"
                                                                                        class="w-full h-full object-contain">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">KAMITO</span>
                                                                            </a>

                                                                            <!-- MIZUNO -->
                                                                            <a href="../shop.php?thuonghieu[]=mizuno"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../../img/icons/logo-mizuno.png"
                                                                                        alt="Mizuno"
                                                                                        class="w-full h-full object-contain">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">MIZUNO</span>
                                                                            </a>

                                                                            <!-- KUMPOO -->
                                                                            <a href="../shop.php?thuonghieu[]=kumpoo"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../../img/icons/logo-kumpoo.png"
                                                                                        alt="Kumpoo"
                                                                                        class="w-full h-full object-contain">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">KUMPOO</span>
                                                                            </a>

                                                                            <!-- VENSON -->
                                                                            <a href="../shop.php?thuonghieu[]=venson"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../../img/icons/logo-venson.png"
                                                                                        alt="Venson"
                                                                                        class="w-full h-full object-contain">
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
                                                                            <a href="../shop.php?danhmuc[]=vot-cau-long"
                                                                                class="text-sm text-red-600 hover:text-red-700 flex items-center">
                                                                                Xem tất cả <i
                                                                                    class="fas fa-chevron-right ml-1 text-xs"></i>
                                                                            </a>
                                                                        </div>
                                                                        <div class="grid grid-cols-3 gap-4">
                                                                            <!-- Vợt cầu lông - 8 thương hiệu + Xem thêm -->
                                                                            <div>
                                                                                <a href="../shop.php?danhmuc[]=vot-cau-long"
                                                                                    class="font-semibold text-sm hover:text-red-600">Vợt
                                                                                    cầu lông</a>
                                                                                <ul class="mt-2 space-y-1">
                                                                                    <li><a href="../shop.php?danhmuc[]=vot-cau-long&thuonghieu[]=yonex"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Vợt
                                                                                            cầu lông Yonex</a></li>
                                                                                    <li><a href="../shop.php?danhmuc[]=vot-cau-long&thuonghieu[]=li-ning"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Vợt
                                                                                            cầu lông Li-Ning</a></li>
                                                                                    <li><a href="../shop.php?danhmuc[]=vot-cau-long&thuonghieu[]=adidas"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Vợt
                                                                                            cầu lông Adidas</a></li>
                                                                                    <li><a href="../shop.php?danhmuc[]=vot-cau-long&thuonghieu[]=victor"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Vợt
                                                                                            cầu lông Victor</a></li>
                                                                                    <li><a href="../shop.php?danhmuc[]=vot-cau-long"
                                                                                            class="text-xs text-red-600 hover:text-red-700 font-medium">Xem
                                                                                            thêm <i
                                                                                                class="fas fa-chevron-right ml-1 text-[10px]"></i></a>
                                                                                    </li>
                                                                                </ul>
                                                                            </div>

                                                                            <!-- Balo cầu lông - 8 thương hiệu + Xem thêm -->
                                                                            <div>
                                                                                <a href="../shop.php?danhmuc[]=ba-l"
                                                                                    class="font-semibold text-sm hover:text-red-600">Balo
                                                                                    cầu lông</a>
                                                                                <ul class="mt-2 space-y-1">
                                                                                    <li><a href="../shop.php?danhmuc[]=ba-l&thuonghieu[]=yonex"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Balo
                                                                                            Yonex</a></li>
                                                                                    <li><a href="../shop.php?danhmuc[]=ba-l&thuonghieu[]=li-ning"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Balo
                                                                                            Li-Ning</a></li>
                                                                                    <li><a href="../shop.php?danhmuc[]=ba-l&thuonghieu[]=adidas"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Balo
                                                                                            Adidas</a></li>
                                                                                    <li><a href="../shop.php?danhmuc[]=ba-l&thuonghieu[]=victor"
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
                                                                                <a href="../shop.php?danhmuc[]=phu-kien"
                                                                                    class="font-semibold text-sm hover:text-red-600">Phụ
                                                                                    kiện</a>
                                                                                <ul class="mt-2 space-y-1">
                                                                                    <li><a href="../shop.php?danhmuc[]=phu-kien"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Tất
                                                                                            cả phụ kiện</a></li>
                                                                                    <li><a href="../shop.php?danhmuc[]=phu-kien&search=quả+cầu"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Quả
                                                                                            cầu lông</a></li>
                                                                                    <li><a href="../shop.php?danhmuc[]=phu-kien&search=cước+đan"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Cước
                                                                                            đan vợt</a></li>
                                                                                    <li><a href="../shop.php?danhmuc[]=phu-kien&search=quấn+cán"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Quấn
                                                                                            cán</a></li>
                                                                                    <li><a href="../shop.php?danhmuc[]=phu-kien"
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
                                                                            <a href="../shop.php?danhmuc[]=vot-pickleball"
                                                                                class="text-sm text-red-600 hover:text-red-700 flex items-center">
                                                                                Xem tất cả <i
                                                                                    class="fas fa-chevron-right ml-1 text-xs"></i>
                                                                            </a>
                                                                        </div>
                                                                        <div class="grid grid-cols-4 gap-2">
                                                                            <!-- JOOLA -->
                                                                            <a href="../shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=joola"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../../img/icons/logo-joola.png"
                                                                                        alt="JOOLA"
                                                                                        class="w-full h-full object-contain">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">JOOLA</span>
                                                                            </a>

                                                                            <!-- SELKIRK -->
                                                                            <a href="../shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=selkirk"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../../img/icons/logo-selkirk.webp"
                                                                                        alt="SELKIRK"
                                                                                        class="w-full h-full object-contain">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">SELKIRK</span>
                                                                            </a>

                                                                            <!-- KAMITO -->
                                                                            <a href="../shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=kamito"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../../img/icons/logo-kamito.png"
                                                                                        alt="KAMITO"
                                                                                        class="w-full h-full object-contain">
                                                                                </div>
                                                                                <span
                                                                                    class="text-sm font-medium">KAMITO</span>
                                                                            </a>

                                                                            <!-- WIKA -->
                                                                            <a href="../shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=wika"
                                                                                class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                <div
                                                                                    class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                    <img src="../../img/icons/logo-wika.png"
                                                                                        alt="WIKA"
                                                                                        class="w-full h-full object-contain">
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
                                                                            <a href="../shop.php?danhmuc[]=vot-pickleball"
                                                                                class="text-sm text-red-600 hover:text-red-700 flex items-center">
                                                                                Xem tất cả <i
                                                                                    class="fas fa-chevron-right ml-1 text-xs"></i>
                                                                            </a>
                                                                        </div>
                                                                        <div class="grid grid-cols-3 gap-4">
                                                                            <!-- Vợt Pickleball - 4 thương hiệu + Xem thêm -->
                                                                            <div>
                                                                                <a href="../shop.php?danhmuc[]=vot-pickleball"
                                                                                    class="font-semibold text-sm hover:text-red-600">Vợt
                                                                                    Pickleball</a>
                                                                                <ul class="mt-2 space-y-1">
                                                                                    <li><a href="../shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=joola"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Vợt
                                                                                            Joola</a></li>
                                                                                    <li><a href="../shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=selkirk"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Vợt
                                                                                            Selkirk</a></li>
                                                                                    <li><a href="../shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=kamito"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Vợt
                                                                                            Kamito</a></li>
                                                                                    <li><a href="../shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=wika"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Vợt
                                                                                            Wika</a></li>
                                                                                    <li><a href="../shop.php?danhmuc[]=vot-pickleball"
                                                                                            class="text-xs text-red-600 hover:text-red-700 font-medium">Xem
                                                                                            thêm <i
                                                                                                class="fas fa-chevron-right ml-1 text-[10px]"></i></a>
                                                                                    </li>
                                                                                </ul>
                                                                            </div>

                                                                            <!-- Balo/Túi Pickleball - 4 thương hiệu + Xem thêm -->
                                                                            <div>
                                                                                <a href="../shop.php?danhmuc[]=ba-l"
                                                                                    class="font-semibold text-sm hover:text-red-600">Balo
                                                                                    - Túi Pickleball</a>
                                                                                <ul class="mt-2 space-y-1">
                                                                                    <li><a href="../shop.php?danhmuc[]=ba-l&thuonghieu[]=joola"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Balo
                                                                                            Joola</a></li>
                                                                                    <li><a href="../shop.php?danhmuc[]=ba-l&thuonghieu[]=selkirk"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Balo
                                                                                            Selkirk</a></li>
                                                                                    <li><a href="../shop.php?danhmuc[]=ba-l&thuonghieu[]=kamito"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Balo
                                                                                            Kamito</a></li>
                                                                                    <li><a href="../shop.php?danhmuc[]=ba-l&thuonghieu[]=wika"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Balo
                                                                                            Wika</a></li>
                                                                                    <li><a href="../shop.php?danhmuc[]=ba-l"
                                                                                            class="text-xs text-red-600 hover:text-red-700 font-medium">Xem
                                                                                            thêm <i
                                                                                                class="fas fa-chevron-right ml-1 text-[10px]"></i></a>
                                                                                    </li>
                                                                                </ul>
                                                                            </div>

                                                                            <!-- Phụ kiện Pickleball -->
                                                                            <div>
                                                                                <a href="../view/shop.php?danhmuc[]=phu-kien-pickleball"
                                                                                    class="font-semibold text-sm hover:text-red-600">Phụ
                                                                                    kiện Pickleball</a>
                                                                                <ul class="mt-2 space-y-1">
                                                                                    <li><a href="../shop.php?danhmuc[]=phu-kien-pickleball&search=bóng"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Bóng
                                                                                            Pickleball</a></li>
                                                                                    <li><a href="../shop.php?danhmuc[]=phu-kien-pickleball&search=lưới"
                                                                                            class="text-xs text-gray-600 hover:text-red-600">Lưới
                                                                                            Pickleball</a></li>
                                                                                    <li><a href="../shop.php?danhmuc[]=phu-kien-pickleball"
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
                                        <li><a href="../shop.php"
                                                class="flex items-center text-gray-700 hover:text-red-600 font-medium"><img
                                                    src="../../img/icons/store.svg"
                                                    class="w-5 h-5 flex-shrink-0 mr-2"><span>CỬA HÀNG</span></a></li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Cột giữa: LOGO – được căn giữa hoàn hảo nhờ grid -->
                            <div id="logo" class="flex justify-center items-center">
                                <a href="../../index.php" title="NVBPlay" rel="home">
                                    <img width="100" height="40" src="../../img/icons/logonvb.png" alt="NVBPlay"
                                        class="h-12 md:h-14 w-auto transform scale-75">
                                </a>
                            </div>

                            <!-- Cột phải: các thành phần desktop + mobile (căn phải) -->
                            <div class="flex items-center justify-end">
                                <!-- Desktop Right Elements (ẩn trên mobile) -->
                                <div class="hidden md:flex items-center space-x-4">
                                    <!-- Address Book (chỉ hiển thị khi đã đăng nhập) - giả lập biến is_logged_in = false để demo, nếu true sẽ hiện -->



                                    <div class="address-book">
                                        <a href="./address-book.php"
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
                                                <img src="../../img/icons/account.svg" class="w-6 h-6" alt="Account">
                                                <span
                                                    class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($user_info['username']); ?></span>
                                                <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                            </button>
                                            <div id="userMenu" class="user-menu">
                                                <div class="px-4 py-3 border-b border-gray-100">
                                                    <div class="flex items-center space-x-3">
                                                        <img src="../../img/icons/account.svg" class="w-10 h-10"
                                                            alt="Account">
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
                                                <a href="../my-account.php" class="user-menu-item"><i
                                                        class="fas fa-user"></i><span>Tài khoản của tôi</span></a>
                                                <a href="./orders.php" class="user-menu-item"><i
                                                        class="fas fa-shopping-bag"></i><span>Đơn hàng</span></a>
                                                <a href="../address-book.php" class="user-menu-item"><i
                                                        class="fas fa-map-marker-alt"></i><span>Sổ địa chỉ</span></a>
                                                <div class="user-menu-divider"></div>
                                                <a href="../../control/logout.php" class="user-menu-item logout"><i
                                                        class="fas fa-sign-out-alt"></i><span>Đăng xuất</span></a>
                                            </div>
                                        <?php else: ?>
                                            <a href="../login.php"
                                                class="flex items-center text-gray-700 hover:text-red-600">
                                                <i class="far fa-user text-xl"></i>
                                                <span class="text-sm ml-1">Đăng nhập</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Cart -->
                                    <a href="../cart.php" class="relative p-2">
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
                                            <img src="../../img/icons/account.svg" class="w-6 h-6" alt="Account">
                                        </a>
                                    <?php else: ?>
                                        <a href="../login.php" class="p-1">
                                            <i class="far fa-user text-xl text-gray-700"></i>
                                        </a>
                                    <?php endif; ?>

                                    <!-- Cart Mobile với badge động -->
                                    <a href="../cart.php" class="relative p-1">
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
                                                <?php echo htmlspecialchars($user_info['ho_ten']); ?>
                                            </h3>
                                        </div>
                                        <div class="rank">
                                            <img src="../../img/icons/subscription.svg" class="w-25 h-25">
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($user_info['email']); ?>
                                    </p>
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
                                <?php echo htmlspecialchars($user_info['ho_ten']); ?>
                            </h3>
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

                    </ul>
                </nav>
            </div>
        </div>

        <!-- Add/Edit Address Popup -->
        <div id="addressPopup" class="popup-overlay">
            <div class="popup-content p-6">
                <div class="flex items-center justify-between mb-4 border-b pb-3">
                    <h3 id="popupTitle" class="text-lg font-semibold">Thêm địa chỉ mới</h3>
                    <div id="sqlInjectionWarning" class="sql-injection-warning">
                        <i class="fas fa-shield-alt"></i>
                        <strong>Cảnh báo bảo mật:</strong> <span id="sqlInjectionMessage">Phát hiện ký tự không an
                            toàn</span>
                    </div>
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
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tỉnh/Thành phố
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="tinh_thanhpho" name="tinh_thanhpho"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                                required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phường<span
                                    class="text-red-500">*</span></label>
                            <input type="text" id="quan" name="quan"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                                required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Đường<span
                                    class="text-red-500">*</span></label>
                            <input type="text" id="duong" name="duong"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                                required>
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
        <footer id="footer" class="bg-black text-white">
            <div class="container mx-auto px-4 py-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="pl-5">
                        <h3 class="text-4xl font-bold mb-4">Boost<br>your power</h3>
                        <div class="flex space-x-3 mb-4">
                            <a href="" target="_blank"
                                class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition"><i
                                    class="fab fa-facebook-f"></i></a>
                            <a href="" target="_blank"
                                class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition"><i
                                    class="fab fa-tiktok"></i></a>
                            <a href="" target="_blank"
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
                            <li><a href="" target="_blank" class="flex"><span class="font-medium w-20">Địa
                                        chỉ:</span><span class="text-gray-400">62 Lê Bình,
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
                    <a href="" target="_blank"><img src="./img/icons/logoBCT.png" alt="Bộ Công Thương"
                            class="h-12 w-auto"></a>
                </div>
            </div>
        </footer>
    </div>

    <!-- Mobile Menu -->
    <div id="main-menu"
        class="fixed inset-0 bg-white z-50 transform -translate-x-full transition duration-300 md:hidden overflow-y-auto">
        <div class="p-4">
            <div class="flex justify-between items-center mb-6">
                <img src="../../img/icons/logonvb.png" height="30" width="50"
                    class="relative-top-left transform scale-75">
                <button class="close-menu p-2 hover:bg-gray-100 rounded-full transition"><i
                        class="fas fa-times text-2xl text-gray-600"></i></button>
            </div>
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                <?php if ($is_logged_in): ?>
                    <div class="flex items-center text-gray-700">
                        <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                            <img src="../../img/icons/account.svg" class="w-6 h-6" alt="Account">
                        </div>
                        <div>
                            <div class="font-medium"><?php echo htmlspecialchars($user_info['username']); ?></div>
                            <span class="text-sm text-gray-500"><?php echo htmlspecialchars($user_info['email']); ?></span>
                        </div>
                    </div>
                    <a href="../../control/logout.php" class="text-red-600 text-sm font-medium">Đăng xuất</a>
                <?php else: ?>
                    <a href="../login.php" class="flex items-center text-gray-700">
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
                                <img src="../../img/icons/logo-caulong.png" alt="Cầu Lông" class="w-full h-full">
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
                                <a href="../shop.php?danhmuc[]=vot-cau-long&thuonghieu[]=yonex"
                                    class="block py-1 text-sm text-gray-600">Vợt Yonex</a>
                                <a href="../shop.php?danhmuc[]=vot-cau-long&thuonghieu[]=li-ning"
                                    class="block py-1 text-sm text-gray-600">Vợt Li-Ning</a>
                                <a href="../shop.php?danhmuc[]=vot-cau-long&thuonghieu[]=adidas"
                                    class="block py-1 text-sm text-gray-600">Vợt Adidas</a>
                                <a href="../shop.php?danhmuc[]=vot-cau-long&thuonghieu[]=victor"
                                    class="block py-1 text-sm text-gray-600">Vợt Victor</a>
                                <a href="../shop.php?danhmuc[]=vot-cau-long" class="block py-1 text-sm text-red-600">Xem
                                    thêm</a>
                            </div>
                        </div>

                        <!-- Áo cầu lông -->
                        <div>
                            <a href="../shop.php?danhmuc[]=ao-cau-long" class="block py-2 text-gray-700 font-medium">Áo
                                cầu lông</a>
                            <div class="pl-4 mt-1 space-y-1">
                                <a href="../shop.php?danhmuc[]=ao-cau-long&thuonghieu[]=yonex"
                                    class="block py-1 text-sm text-gray-600">Áo Yonex</a>
                                <a href="../shop.php?danhmuc[]=ao-cau-long&thuonghieu[]=ds"
                                    class="block py-1 text-sm text-gray-600">Áo DS</a>
                                <a href="../shop.php?danhmuc[]=ao-cau-long&thuonghieu[]=kamito"
                                    class="block py-1 text-sm text-gray-600">Áo Kamito</a>
                                <a href="../shop.php?danhmuc[]=ao-cau-long" class="block py-1 text-sm text-red-600">Xem
                                    thêm</a>
                            </div>
                        </div>

                        <!-- Quần cầu lông -->
                        <div>
                            <a href="../shop.php?danhmuc[]=quan-cau-long"
                                class="block py-2 text-gray-700 font-medium">Quần cầu lông</a>
                            <div class="pl-4 mt-1 space-y-1">
                                <a href="../shop.php?danhmuc[]=quan-cau-long&thuonghieu[]=yonex"
                                    class="block py-1 text-sm text-gray-600">Quần Yonex</a>
                                <a href="../shop.php?danhmuc[]=quan-cau-long&thuonghieu[]=kamito"
                                    class="block py-1 text-sm text-gray-600">Quần Kamito</a>
                                <a href="../shop.php?danhmuc[]=quan-cau-long&thuonghieu[]=adidas"
                                    class="block py-1 text-sm text-gray-600">Quần Adidas</a>
                            </div>
                        </div>

                        <!-- Túi vợt -->
                        <div>
                            <a href="../shop.php?danhmuc[]=tui-vot-cau-long"
                                class="block py-2 text-gray-700 font-medium">Túi vợt</a>
                        </div>

                        <!-- Balo -->
                        <div>
                            <a href="../shop.php?danhmuc[]=ba-l" class="block py-2 text-gray-700 font-medium">Balo</a>
                        </div>

                        <!-- Phụ kiện -->
                        <div>
                            <a href="../shop.php?danhmuc[]=phu-kien" class="block py-2 text-gray-700 font-medium">Phụ
                                kiện</a>
                            <div class="pl-4 mt-1 space-y-1">
                                <a href="../shop.php?danhmuc[]=phu-kien&search=cước+đan"
                                    class="block py-1 text-sm text-gray-600">Cước đan vợt</a>
                                <a href="../shop.php?danhmuc[]=phu-kien&search=quấn+cán"
                                    class="block py-1 text-sm text-gray-600">Quấn cán</a>
                                <a href="../shop.php?danhmuc[]=phu-kien&search=quả+cầu"
                                    class="block py-1 text-sm text-gray-600">Quả cầu lông</a>
                                <a href="../shop.php?danhmuc[]=phu-kien" class="block py-1 text-sm text-red-600">Xem
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
                                <img src="../../img/icons/logo-pickleball.png" alt="Pickleball" class="w-full h-full">
                            </div>
                            <span class="font-medium">Pickleball</span>
                        </div>
                        <i class="fas fa-chevron-down text-sm text-gray-500 transition-transform"></i>
                    </button>

                    <div class="pl-11 pr-3 mt-2 space-y-2 hidden category-submenu" id="submenu-pickleball">
                        <div>
                            <a href="../shop.php?danhmuc[]=vot-pickleball"
                                class="block py-2 text-gray-700 font-medium">Vợt Pickleball</a>
                            <div class="pl-4 mt-1 space-y-1">
                                <a href="../shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=joola"
                                    class="block py-1 text-sm text-gray-600">Vợt Joola</a>
                                <a href="../shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=selkirk"
                                    class="block py-1 text-sm text-gray-600">Vợt Selkirk</a>
                                <a href="../shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=kamito"
                                    class="block py-1 text-sm text-gray-600">Vợt Kamito</a>
                                <a href="../shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=wika"
                                    class="block py-1 text-sm text-gray-600">Vợt Wika</a>
                                <a href="../shop.php?danhmuc[]=vot-pickleball"
                                    class="block py-1 text-sm text-red-600">Xem thêm</a>
                            </div>
                        </div>
                        <div>
                            <a href="../shop.php?danhmuc[]=phu-kien-pickleball"
                                class="block py-2 text-gray-700 font-medium">Phụ kiện Pickleball</a>
                            <div class="pl-4 mt-1 space-y-1">
                                <a href="../shop.php?danhmuc[]=phu-kien-pickleball&search=bóng"
                                    class="block py-1 text-sm text-gray-600">Bóng Pickleball</a>
                                <a href="../shop.php?danhmuc[]=phu-kien-pickleball&search=lưới"
                                    class="block py-1 text-sm text-gray-600">Lưới Pickleball</a>
                                <a href="../shop.php?danhmuc[]=phu-kien-pickleball"
                                    class="block py-1 text-sm text-red-600">Xem thêm</a>
                            </div>
                        </div>
                        <div>
                            <a href="../shop.php?danhmuc[]=ba-l" class="block py-2 text-gray-700 font-medium">Balo - Túi
                                Pickleball</a>
                        </div>
                    </div>
                </div>

                <!-- Giày -->
                <div class="mb-2">
                    <button class="w-full flex items-center justify-between p-3 bg-gray-50 rounded-lg category-toggle"
                        data-category="giay">
                        <div class="flex items-center">
                            <div class="w-8 h-8 mr-3 flex-shrink-0">
                                <img src="../../img/icons/logo-giay.png" alt="Giày" class="w-full h-full">
                            </div>
                            <span class="font-medium">Giày</span>
                        </div>
                        <i class="fas fa-chevron-down text-sm text-gray-500 transition-transform"></i>
                    </button>

                    <div class="pl-11 pr-3 mt-2 space-y-2 hidden category-submenu" id="submenu-giay">
                        <div><a href="../shop.php?danhmuc[]=giay&thuonghieu[]=yonex"
                                class="block py-2 text-gray-700">Giày Yonex</a></div>
                        <div><a href="../shop.php?danhmuc[]=giay&thuonghieu[]=adidas"
                                class="block py-2 text-gray-700">Giày Adidas</a></div>
                        <div><a href="../shop.php?danhmuc[]=giay&thuonghieu[]=mizuno"
                                class="block py-2 text-gray-700">Giày Mizuno</a></div>
                        <div><a href="../shop.php?danhmuc[]=giay&thuonghieu[]=asics"
                                class="block py-2 text-gray-700">Giày Asics</a></div>
                        <div><a href="../shop.php?danhmuc[]=giay&thuonghieu[]=kamito"
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
        <?php require_once '../../control/chatbot.php'; ?>
        <script>

            function toggleMobileAccountMenu() {
                const mobileMenu = document.getElementById('mobile-account-menu');
                const overlay = document.getElementById('mobile-account-overlay');
                if (mobileMenu && overlay) {
                    mobileMenu.classList.toggle('-translate-x-full');
                    overlay.classList.toggle('hidden');
                }
            }

            // Popup functions
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
                        const response = await fetch(`../../control/search-suggest.php?q=${encodeURIComponent(query)}`);
                        const result = await response.json();
                        if (result.success && result.data.length > 0) {
                            const limitedResults = result.data.slice(0, 8);
                            suggestionsList.innerHTML = limitedResults.map(product => `
                <a href="../${product.url}" class="suggestion-item">
                    <img src="../../${product.image}" alt="${product.name}" loading="lazy"
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
                    <a href="../shop.php?search=${encodeURIComponent(query)}" class="view-all-link">
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
                                window.location.href = `../shop.php?search=${encodeURIComponent(query)}`;
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

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // === SQL INJECTION DETECTION ===
                const sqlInjectionPatterns = [
                    /\b(SELECT|INSERT|UPDATE|DELETE|DROP|TRUNCATE|ALTER|CREATE|EXEC|EXECUTE|UNION)\b/i,
                    /(--|\/\*|\*\/|#)/,
                    /\bOR\b\s+['"]?\d+['"]?\s*=\s*['"]?\d+|\bAND\b\s+['"]?\d+['"]?\s*=\s*['"]?\d+/i,
                    /\bxp_\w+|sp_\w+/i,
                    /\b(WAITFOR|BENCHMARK|SLEEP)\b/i,
                    /%00|%27|%22/i
                ];

                function checkSQLInjection(value, fieldName) {
                    if (!/[;'"\\<>%]/.test(value)) return false;
                    for (let pattern of sqlInjectionPatterns) {
                        if (pattern.test(value)) {
                            showSQLInjectionWarning(`Phát hiện ký tự không an toàn trong ${fieldName}`);
                            return true;
                        }
                    }
                    return false;
                }

                function showSQLInjectionWarning(message) {
                    const warning = document.getElementById('sqlInjectionWarning');
                    const msg = document.getElementById('sqlInjectionMessage');
                    const submitBtn = document.getElementById('submitBtn');
                    if (warning) {
                        warning.style.display = 'block';
                        if (msg) msg.textContent = message;
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                        }
                    }
                }

                function hideSQLInjectionWarning() {
                    const warning = document.getElementById('sqlInjectionWarning');
                    const submitBtn = document.getElementById('submitBtn');
                    if (warning) warning.style.display = 'none';
                    if (submitBtn && !submitBtn.disabled) {
                        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                }

                // === EVENT LISTENERS CHO ADDRESS FORM ===
                const addressInputs = ['ten_nguoi_nhan', 'tinh_thanhpho', 'quan', 'duong', 'dia_chi_chitiet'];
                addressInputs.forEach(id => {
                    const input = document.getElementById(id);
                    if (input) {
                        input.addEventListener('input', function () {
                            if (/[;'"\\<>%]/.test(this.value)) {
                                checkSQLInjection(this.value, this.name);
                            } else {
                                hideSQLInjectionWarning();
                            }
                        });
                        input.addEventListener('blur', function () {
                            hideSQLInjectionWarning();
                        });
                    }
                });

                // === CLOSE POPUP -> HIDE WARNING ===
                const addressPopup = document.getElementById('addressPopup');
                if (addressPopup) {
                    addressPopup.addEventListener('click', function (e) {
                        if (e.target === this) hideSQLInjectionWarning();
                    });
                }

                // === FORM SUBMIT FINAL CHECK ===
                const addressForm = document.getElementById('addressForm');
                if (addressForm) {
                    addressForm.addEventListener('submit', function (e) {
                        const inputs = addressInputs.map(id => document.getElementById(id)).filter(i => i);
                        for (let input of inputs) {
                            if (input && checkSQLInjection(input.value, input.name)) {
                                e.preventDefault();
                                input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                return false;
                            }
                        }
                    });
                }
            });
        </script>

     <script>
(function() {
    // ⚠️ HARDCODE ĐƯỜNG DẪN TẠI ĐÂY (Sửa cho phù hợp với từng thư mục)
    const LOGOUT_URL = '../../control/logout.php'; 

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