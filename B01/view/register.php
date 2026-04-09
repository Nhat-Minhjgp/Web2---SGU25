<?php
session_start();
require_once '../control/connect.php';

require_once '../control/check_remember_login.php';
$errors = [];
$success = '';
$form_data = [
    'username' => '',
    'ho_ten' => '',
    'email' => '',
    'sdt' => '',
    'tinh_thanhpho' => '',
    'quan_huyen' => '',
    'dia_chi_chitiet' => ''
];

// Nếu đã đăng nhập thì redirect về trang chủ
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // === 1. SERVER-SIDE SANITIZATION ===
    $form_data['username'] = trim(preg_replace('/[^a-zA-Z0-9]/', '', $_POST['username'] ?? ''));
    $form_data['ho_ten'] = trim(htmlspecialchars($_POST['ho_ten'] ?? '', ENT_QUOTES, 'UTF-8'));
    $form_data['email'] = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $form_data['sdt'] = trim(preg_replace('/[^0-9]/', '', $_POST['sdt'] ?? ''));
    $form_data['tinh_thanhpho'] = trim(htmlspecialchars($_POST['tinh_thanhpho'] ?? '', ENT_QUOTES, 'UTF-8'));
    $form_data['quan_huyen'] = trim(htmlspecialchars($_POST['quan_huyen'] ?? '', ENT_QUOTES, 'UTF-8'));
    $form_data['dia_chi_chitiet'] = trim(htmlspecialchars($_POST['dia_chi_chitiet'] ?? '', ENT_QUOTES, 'UTF-8'));

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);

    // === 2. SERVER-SIDE VALIDATION ===
    $sqlInjectionPatterns = [
        '/(\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b|\bUNION\b|\bTRUNCATE\b|\bEXEC\b)/i',
        '/(--|\/\*|\*\/|#)/',
        '/(\bOR\b|\bAND\b)\s*([\'"]|\d+=\d+)/i',
        '/;/',
        '/\bxp_\w+/i',
        '/(\bWAITFOR\b|\bBENCHMARK\b|\bSLEEP\b)/i'
    ];

    foreach ($sqlInjectionPatterns as $pattern) {
        if (
            preg_match($pattern, $_POST['username'] ?? '') ||
            preg_match($pattern, $_POST['ho_ten'] ?? '') ||
            preg_match($pattern, $_POST['email'] ?? '')
        ) {
            $errors[] = "Phát hiện ký tự không an toàn. Vui lòng nhập lại.";
            break;
        }
    }

    // Validation cơ bản
    if (empty($form_data['username']))
        $errors[] = "Tên đăng nhập không được để trống";
    elseif (strlen($form_data['username']) < 3)
        $errors[] = "Tên đăng nhập phải có ít nhất 3 ký tự";
    elseif (!preg_match('/^[a-zA-Z0-9]+$/', $form_data['username']))
        $errors[] = "Tên đăng nhập chỉ chứa chữ, số";

    if (empty($form_data['ho_ten']))
        $errors[] = "Họ và tên không được để trống";

    if (empty($form_data['email']))
        $errors[] = "Email không được để trống";
    elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL))
        $errors[] = "Email không hợp lệ";

    //  VALIDATE SỐ ĐIỆN THOẠI VIỆT NAM (Bắt đầu bằng 09, 10 số)
    if (empty($form_data['sdt']))
        $errors[] = "Số điện thoại không được để trống";
    elseif (!preg_match('/^09[0-9]{8}$/', $form_data['sdt']))
        $errors[] = "Số điện thoại phải bắt đầu bằng 09 và có 10 số";

    if (empty($password))
        $errors[] = "Mật khẩu không được để trống";
    elseif (strlen($password) < 6)
        $errors[] = "Mật khẩu phải có ít nhất 6 ký tự";

    if ($password !== $confirm_password)
        $errors[] = "Mật khẩu không khớp";


    //  VALIDATE ĐỊA CHỈ - KHÔNG ĐƯỢC ĐỂ TRỐNG
    if (empty($form_data['tinh_thanhpho']))
        $errors[] = "Tỉnh/Thành phố không được để trống";
    elseif (strlen($form_data['tinh_thanhpho']) < 2)
        $errors[] = "Tỉnh/Thành phố phải có ít nhất 2 ký tự";

    if (empty($form_data['quan_huyen']))
        $errors[] = "Quận/Huyện không được để trống";
    elseif (strlen($form_data['quan_huyen']) < 2)
        $errors[] = "Quận/Huyện phải có ít nhất 2 ký tự";

    if (empty($form_data['dia_chi_chitiet']))
        $errors[] = "Địa chỉ chi tiết không được để trống";
    elseif (strlen($form_data['dia_chi_chitiet']) < 5)
        $errors[] = "Địa chỉ chi tiết phải có ít nhất 5 ký tự";


    if (!$terms)
        $errors[] = "Bạn phải đồng ý với điều khoản sử dụng";

    // === 3. DATABASE OPERATIONS ===
    if (empty($errors)) {
        try {
            // Kiểm tra Username trùng
            $stmt = $conn->prepare("SELECT User_id FROM users WHERE Username = ?");
            $stmt->bind_param("s", $form_data['username']);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = "Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác.";
            }
            $stmt->close();

            // Kiểm tra Email trùng
            if (empty($errors)) {
                $stmt = $conn->prepare("SELECT User_id FROM users WHERE email = ?");
                $stmt->bind_param("s", $form_data['email']);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $errors[] = "Email đã được đăng ký. Vui lòng sử dụng email khác.";
                }
                $stmt->close();
            }

            //  KIỂM TRA SỐ ĐIỆN THOẠI TRÙNG
            if (empty($errors)) {
                $stmt = $conn->prepare("SELECT User_id FROM users WHERE SDT = ?");
                $stmt->bind_param("s", $form_data['sdt']);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $errors[] = "Số điện thoại đã được đăng ký. Vui lòng sử dụng số khác.";
                }
                $stmt->close();
            }

            // Insert vào DB users
            if (empty($errors)) {
                $conn->begin_transaction();

                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = 0;
                $status = 1;

                $stmt = $conn->prepare("INSERT INTO users (Username, password, Ho_ten, email, SDT, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param(
                    "sssssis",
                    $form_data['username'],
                    $hashed_password,
                    $form_data['ho_ten'],
                    $form_data['email'],
                    $form_data['sdt'],
                    $role,
                    $status
                );

                if (!$stmt->execute()) {
                    throw new Exception("Lỗi đăng ký tài khoản: " . $stmt->error);
                }

                $user_id = $stmt->insert_id;
                $stmt->close();

                //  TẠO ĐỊA CHỈ MẶC ĐỊNH
                $stmt_addr = $conn->prepare("INSERT INTO diachigh (User_id, Ten_nguoi_nhan, SDT_nhan, Duong, Quan, Tinh_thanhpho, Dia_chi_chitiet, Mac_dinh) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                $stmt_addr->bind_param(
                    "issssss",
                    $user_id,
                    $form_data['ho_ten'],
                    $form_data['sdt'],
                    $form_data['dia_chi_chitiet'],
                    $form_data['quan_huyen'],
                    $form_data['tinh_thanhpho'],
                    $form_data['dia_chi_chitiet']
                );

                if (!$stmt_addr->execute()) {
                    throw new Exception("Lỗi lưu địa chỉ: " . $stmt_addr->error);
                }
                $stmt_addr->close();

                $conn->commit();

                $success = "Đăng ký thành công! Chuyển hướng...";
                $form_data = ['username' => '', 'ho_ten' => '', 'email' => '', 'sdt' => '', 'tinh_thanhpho' => '', 'quan_huyen' => '', 'dia_chi_chitiet' => ''];
                header("Refresh: 2; URL=./login.php");
                exit();
            }
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Register Error: " . $e->getMessage());
            $errors[] = "Lỗi hệ thống. Vui lòng thử lại sau.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | NVBPlay</title>
    <meta name="description"
        content="NVBPlay chuyên cung cấp đồ cầu lông và pickleball cao cấp, từ vợt, giày, đến phụ kiện chính hãng. Nâng cao trải nghiệm của bạn tại NVBPlay.">
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





        /* Input validation styles */
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

                                    <a href="./login.php" class="p-1">
                                        <i class="far fa-user text-xl text-gray-700"></i>
                                    </a>


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


        <main id="main" class="bg-white mt-20 mb-20">
            <div class="page-wrapper my-account">
                <div class="container mx-auto px-5" role="main">
                    <div class="woocommerce">
                        <div class="account-container lightbox-inner max-w-6xl mx-auto">
                            <div class="flex flex-col md:flex-row">
                                <!-- Banner Image - giảm width xuống để form rộng hơn -->
                                <div class="hidden md:block banner-login md:w-3/4 mr-10">
                                    <img decoding="async"
                                        src="https://nvbplay.vn/wp-content/themes/nvbplayvn/assets/img/Login-Place.png"
                                        alt="Banner Register" title="My account" class="w-full h-full "
                                        style="min-width: 600px">
                                </div>

                                <!-- Register Form -->
                                <div class="md:w-1/4 flex items-center mt-3 justify-center md:p-4 bg-white">
                                    <div class="w-full">
                                        <h1 class="text-center text-lg font-medium mb-4">Đăng ký tài khoản</h1>
                                        <?php if (!empty($errors)): ?>
                                            <div class="alert-error p-3 rounded mb-4 text-sm">
                                               
                                                <ul class=" list-inside">
                                                    <?php foreach ($errors as $error): ?>
                                                        <li>
                                                            <?php echo htmlspecialchars($error); ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                        <!-- SQL Injection Warning -->
                                        <div id="sqlInjectionWarning" class="sql-injection-warning">
                                            <i class="fas fa-shield-alt"></i>
                                            <strong>Cảnh báo bảo mật:</strong> <span id="sqlInjectionMessage">Phát hiện
                                                ký tự không an toàn</span>
                                        </div><!-- Form đăng ký - chiếm 2/3 chiều rộng còn lại -->
                                        <form id="registerForm" class="space-y-4  pb-8" method="POST" action=""
                                            novalidate>
                                            <!-- Tên đăng nhập -->
                                            <h1></h1>
                                            <div>
                                                <input type="text" id="username" name="username"
                                                    placeholder="Tên đăng nhập *"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm"
                                                    value="<?php echo htmlspecialchars($form_data['username']); ?>"
                                                    required>
                                                <div class="error-text" id="username-error">Tên đăng nhập phải có ít
                                                    nhất 3 ký
                                                    tự</div>
                                            </div>

                                            <!-- Họ tên -->
                                            <div>
                                                <input type="text" id="ho_ten" name="ho_ten" placeholder="Họ và tên *"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm"
                                                    value="<?php echo htmlspecialchars($form_data['ho_ten']); ?>"
                                                    required>
                                                <div class="error-text" id="ho_ten-error">Họ và tên không được để trống
                                                </div>
                                            </div>

                                            <!-- Email -->
                                            <div>
                                                <input type="email" id="email" name="email" placeholder="Email *"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm"
                                                    value="<?php echo htmlspecialchars($form_data['email']); ?>"
                                                    required>
                                                <div class="error-text" id="email-error">Email không hợp lệ</div>
                                            </div>

                                            <!-- Số điện thoại -->
                                            <div>
                                                <input type="tel" id="sdt" name="sdt"
                                                    placeholder="Số điện thoại * (09xxxxxxxx)"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm"
                                                    value="<?php echo htmlspecialchars($form_data['sdt']); ?>"
                                                    maxlength="10" required>
                                                <div class="error-text" id="sdt-error">Số điện thoại phải bắt đầu bằng
                                                    09 và có
                                                    10 số</div>
                                            </div>
                                            <!-- Mật khẩu -->
                                            <div>
                                                <input type="password" id="password" name="password"
                                                    placeholder="Mật khẩu *"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm"
                                                    required>
                                                <div class="error-text" id="password-error">Mật khẩu phải có ít nhất 6
                                                    ký tự
                                                </div>
                                            </div>

                                            <!-- Xác nhận mật khẩu -->
                                            <div>
                                                <input type="password" id="confirm_password" name="confirm_password"
                                                    placeholder="Xác nhận mật khẩu *"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm"
                                                    required>
                                                <div class="error-text" id="confirm_password-error">Mật khẩu xác nhận
                                                    không khớp
                                                </div>
                                            </div>

                                            <!-- === ĐỊA CHỈ GIAO HÀNG === -->

                                            <div class="border-t pt-4">
                                                <h4 class="font-medium text-gray-700 mb-3"> Địa chỉ giao hàng</h4>

                                                <!-- Tỉnh/Thành phố -->
                                                <div class="mb-3">
                                                    <input type="text" id="tinh_thanhpho" name="tinh_thanhpho"
                                                        placeholder="Tỉnh/Thành phố *"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded text-sm"
                                                        value="<?php echo htmlspecialchars($form_data['tinh_thanhpho']); ?>"
                                                        required>
                                                    <div class="error-text" id="tinh_error">Tỉnh/Thành phố không được để
                                                        trống</div>
                                                </div>

                                                <!-- Quận/Huyện -->
                                                <div class="mb-3">
                                                    <input type="text" id="quan_huyen" name="quan_huyen"
                                                        placeholder="Quận/Huyện *"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded text-sm"
                                                        value="<?php echo htmlspecialchars($form_data['quan_huyen']); ?>"
                                                        required>
                                                    <div class="error-text" id="quan_error">Quận/Huyện không được để
                                                        trống</div>
                                                </div>

                                                <!-- Địa chỉ chi tiết -->
                                                <div class="mb-3">
                                                    <input type="text" id="dia_chi_chitiet" name="dia_chi_chitiet"
                                                        placeholder="Địa chỉ chi tiết * (Số nhà, tên đường...)"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded text-sm"
                                                        value="<?php echo htmlspecialchars($form_data['dia_chi_chitiet']); ?>"
                                                        required>
                                                    <div class="error-text" id="chitiet_error">Địa chỉ chi tiết không
                                                        được để trống</div>
                                                </div>
                                            </div>



                                            <!-- Điều khoản -->
                                            <div class="flex items-start gap-2 text-xs">
                                                <input type="checkbox" name="terms" id="terms" class="mt-1" required>
                                                <label for="terms" class="text-gray-600">Tôi đồng ý với <span
                                                        href=""
                                                        class="text-[#FF3F1A] hover:underline">điều khoản sử dụng và
                                                        chính sách
                                                        bảo mật</span></label>
                                            </div>
                                            <div class="error-text" id="terms-error">Bạn phải đồng ý với điều khoản
                                            </div>

                                            <!-- Nút đăng ký -->
                                            <button type="submit" id="submitBtn"
                                                class="w-full bg-[#FF3F1A] text-white text-sm py-2.5 rounded hover:bg-red-600 font-medium transition"
                                                disabled>
                                                ĐĂNG KÝ
                                            </button>

                                            <!-- Link đăng nhập -->
                                            <div class="flex justify-center text-xs pt-2">
                                                <span class="text-gray-500">Đã có tài khoản?</span>
                                                <a href="./login.php" class="text-[#FF3F1A] font-bold ml-1">Đăng
                                                    nhập</a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
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

                    <a href="./login.php" class="flex items-center text-gray-700">
                        <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3"><i
                                class="far fa-user text-xl text-gray-600"></i></div>
                        <div>
                            <div class="font-medium">Tài khoản</div><span class="text-sm text-gray-500">Đăng nhập / Đăng
                                ký</span>
                        </div>
                    </a>

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
            </div>

            <?php require_once '../control/chatbot.php'; ?>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const form = document.getElementById('registerForm');
                    const username = document.getElementById('username');
                    const hoTen = document.getElementById('ho_ten');
                    const email = document.getElementById('email');
                    const sdt = document.getElementById('sdt');
                    const password = document.getElementById('password');
                    const confirmPassword = document.getElementById('confirm_password');
                    const tinhInput = document.getElementById('tinh_thanhpho');
                    const quanInput = document.getElementById('quan_huyen');
                    const chitietInput = document.getElementById('dia_chi_chitiet');
                    const terms = document.getElementById('terms');
                    const submitBtn = document.getElementById('submitBtn');
                    const sqlInjectionWarning = document.getElementById('sqlInjectionWarning');
                    const sqlInjectionMessage = document.getElementById('sqlInjectionMessage');

                    // === SQL INJECTION DETECTION PATTERNS (đã tinh chỉnh) ===
                    const sqlInjectionPatterns = [
                        /\b(SELECT|INSERT|UPDATE|DELETE|DROP|TRUNCATE|ALTER|CREATE|EXEC|EXECUTE|UNION)\b/i,
                        /(--|\/\*|\*\/|#)/,
                        /\bOR\b\s+['"]?\d+['"]?\s*=\s*['"]?\d+|\bAND\b\s+['"]?\d+['"]?\s*=\s*['"]?\d+/i,
                        /\bxp_\w+|sp_\w+/i,
                        /\b(WAITFOR|BENCHMARK|SLEEP)\b/i,
                        /%00|%27|%22/i
                    ];

                    function checkSQLInjection(value, fieldName) {
                        // Chỉ check nếu value có ký tự đặc biệt (tránh false positive với số/letter thường)
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
                        if (sqlInjectionWarning) {
                            sqlInjectionWarning.style.display = 'block';
                            if (sqlInjectionMessage) sqlInjectionMessage.textContent = message;
                            if (submitBtn) {
                                submitBtn.disabled = true;
                                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                            }
                        }
                    }

                    function hideSQLInjectionWarning() {
                        if (sqlInjectionWarning) {
                            sqlInjectionWarning.style.display = 'none';
                            if (submitBtn && !submitBtn.disabled) {
                                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                            }
                        }
                    }

                    // === VALIDATION FUNCTIONS ===
                    function validateUsername() {
                        if (!username) return true;
                        const value = username.value.trim();
                        const error = document.getElementById('username-error');

                        if (value.length === 0) {
                            username.classList.add('input-invalid');
                            username.classList.remove('input-valid');
                            if (error) { error.textContent = 'Tên đăng nhập không được để trống'; error.style.display = 'block'; }
                            return false;
                        }
                        if (value.length < 3) {
                            username.classList.add('input-invalid');
                            username.classList.remove('input-valid');
                            if (error) { error.textContent = 'Tên đăng nhập phải có ít nhất 3 ký tự'; error.style.display = 'block'; }
                            return false;
                        }
                        if (!/^[a-zA-Z0-9]+$/.test(value)) {
                            username.classList.add('input-invalid');
                            username.classList.remove('input-valid');
                            if (error) { error.textContent = 'Chỉ chứa chữ, số'; error.style.display = 'block'; }
                            return false;
                        }
                        username.classList.add('input-valid');
                        username.classList.remove('input-invalid');
                        if (error) error.style.display = 'none';
                        hideSQLInjectionWarning();
                        return true;
                    }

                    function validateHoTen() {
                        if (!hoTen) return true;
                        const value = hoTen.value.trim();
                        const error = document.getElementById('ho_ten-error');

                        if (value.length === 0) {
                            hoTen.classList.add('input-invalid');
                            hoTen.classList.remove('input-valid');
                            if (error) { error.style.display = 'block'; }
                            return false;
                        }
                        hoTen.classList.add('input-valid');
                        hoTen.classList.remove('input-invalid');
                        if (error) error.style.display = 'none';
                        hideSQLInjectionWarning();
                        return true;
                    }

                    function validateEmail() {
                        if (!email) return true;
                        const value = email.value.trim();
                        const error = document.getElementById('email-error');
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                        if (!emailRegex.test(value)) {
                            email.classList.add('input-invalid');
                            email.classList.remove('input-valid');
                            if (error) { error.style.display = 'block'; }
                            return false;
                        }
                        email.classList.add('input-valid');
                        email.classList.remove('input-invalid');
                        if (error) error.style.display = 'none';
                        hideSQLInjectionWarning();
                        return true;
                    }

                    function validateSdt() {
                        if (!sdt) return true;
                        const rawValue = sdt.value.trim();
                        const value = rawValue.replace(/[^0-9]/g, '');
                        const error = document.getElementById('sdt-error');

                        // Check SQL injection
                        if (checkSQLInjection(rawValue, 'số điện thoại')) {
                            sdt.classList.add('input-invalid');
                            sdt.classList.remove('input-valid');
                            return false;
                        }

                        // Tự động xóa ký tự không phải số
                        if (rawValue !== value) {
                            sdt.value = value;
                        }

                        // Kiểm tra định dạng: bắt đầu bằng 09, đúng 10 số
                        const phoneRegex = /^09[0-9]{8}$/;
                        if (!phoneRegex.test(value)) {
                            sdt.classList.add('input-invalid');
                            sdt.classList.remove('input-valid');
                            if (error) {
                                error.textContent = 'Số điện thoại phải bắt đầu bằng 09 và có đúng 10 số';
                                error.style.display = 'block';
                            }
                            return false;
                        }

                        sdt.classList.add('input-valid');
                        sdt.classList.remove('input-invalid');
                        if (error) error.style.display = 'none';
                        hideSQLInjectionWarning();
                        return true;
                    }
                    function validatePassword() {
                        if (!password) return true;
                        const value = password.value;
                        const error = document.getElementById('password-error');
                        const strengthBar = document.getElementById('password-strength-bar');
                        const strengthText = document.getElementById('password-strength-text');

                        if (value.length === 0) {
                            if (strengthBar) strengthBar.className = 'password-strength';
                            if (strengthText) strengthText.textContent = '';
                            return false;
                        }

                        if (value.length < 6) {
                            password.classList.add('input-invalid');
                            password.classList.remove('input-valid');
                            if (error) error.style.display = 'block';
                            return false;
                        }

                        password.classList.add('input-valid');
                        password.classList.remove('input-invalid');
                        if (error) error.style.display = 'none';



                        hideSQLInjectionWarning();
                        validateConfirmPassword();
                        return true;
                    }

                    function validateConfirmPassword() {
                        if (!confirmPassword || !password) return true;
                        const value = confirmPassword.value;
                        const error = document.getElementById('confirm_password-error');

                        if (value !== password.value || value === '') {
                            confirmPassword.classList.add('input-invalid');
                            confirmPassword.classList.remove('input-valid');
                            if (error && value !== '') error.style.display = 'block';
                            return false;
                        }
                        confirmPassword.classList.add('input-valid');
                        confirmPassword.classList.remove('input-invalid');
                        if (error) error.style.display = 'none';
                        return true;
                    }
                    // === VALIDATE ĐỊA CHỈ - KHÔNG ĐƯỢC ĐỂ TRỐNG ===
                    function validateTinhThanhPho() {
                        const input = document.getElementById('tinh_thanhpho');
                        if (!input) return true;
                        const value = input.value.trim();
                        const error = document.getElementById('tinh_error');

                        //  Ràng buộc không được để trống
                        if (value.length === 0) {
                            input.classList.add('input-invalid');
                            input.classList.remove('input-valid');
                            if (error) {
                                error.textContent = 'Tỉnh/Thành phố không được để trống';
                                error.style.display = 'block';
                            }
                            return false;
                        }

                        if (value.length < 2) {
                            input.classList.add('input-invalid');
                            input.classList.remove('input-valid');
                            if (error) {
                                error.textContent = 'Tỉnh/Thành phố phải có ít nhất 2 ký tự';
                                error.style.display = 'block';
                            }
                            return false;
                        }

                        input.classList.add('input-valid');
                        input.classList.remove('input-invalid');
                        if (error) error.style.display = 'none';
                        return true;
                    }

                    function validateQuanHuyen() {
                        const input = document.getElementById('quan_huyen');
                        if (!input) return true;
                        const value = input.value.trim();
                        const error = document.getElementById('quan_error');

                        //  Ràng buộc không được để trống
                        if (value.length === 0) {
                            input.classList.add('input-invalid');
                            input.classList.remove('input-valid');
                            if (error) {
                                error.textContent = 'Quận/Huyện không được để trống';
                                error.style.display = 'block';
                            }
                            return false;
                        }

                        if (value.length < 2) {
                            input.classList.add('input-invalid');
                            input.classList.remove('input-valid');
                            if (error) {
                                error.textContent = 'Quận/Huyện phải có ít nhất 2 ký tự';
                                error.style.display = 'block';
                            }
                            return false;
                        }

                        input.classList.add('input-valid');
                        input.classList.remove('input-invalid');
                        if (error) error.style.display = 'none';
                        return true;
                    }

                    function validateDiaChiChiTiet() {
                        const input = document.getElementById('dia_chi_chitiet');
                        if (!input) return true;
                        const value = input.value.trim();
                        const error = document.getElementById('chitiet_error');

                        //  Ràng buộc không được để trống
                        if (value.length === 0) {
                            input.classList.add('input-invalid');
                            input.classList.remove('input-valid');
                            if (error) {
                                error.textContent = 'Địa chỉ chi tiết không được để trống';
                                error.style.display = 'block';
                            }
                            return false;
                        }

                        if (value.length < 5) {
                            input.classList.add('input-invalid');
                            input.classList.remove('input-valid');
                            if (error) {
                                error.textContent = 'Địa chỉ chi tiết phải có ít nhất 5 ký tự';
                                error.style.display = 'block';
                            }
                            return false;
                        }

                        input.classList.add('input-valid');
                        input.classList.remove('input-invalid');
                        if (error) error.style.display = 'none';
                        return true;
                    }

                    function validateTerms() {
                        if (!terms) return true;
                        const error = document.getElementById('terms-error');
                        if (!terms.checked) {
                            if (error) error.style.display = 'block';
                            return false;
                        }
                        if (error) error.style.display = 'none';
                        return true;
                    }

                    function checkFormValidity() {
                        const isValid =
                            validateUsername() &&
                            validateHoTen() &&
                            validateEmail() &&
                            validateSdt() &&
                            validateTinhThanhPho() &&
                            validateQuanHuyen() &&
                            validateDiaChiChiTiet() &&
                            validatePassword() &&
                            validateConfirmPassword() &&
                            validateTerms();

                        if (submitBtn) {
                            submitBtn.disabled = !isValid;
                            submitBtn.classList.toggle('opacity-50', !isValid);
                            submitBtn.classList.toggle('cursor-not-allowed', !isValid);
                        }
                        return isValid;
                    }

                    // === EVENT LISTENERS ===
                    if (username) {
                        username.addEventListener('blur', validateUsername);
                        username.addEventListener('input', function () {
                            // Chỉ check SQL injection nếu có ký tự đặc biệt
                            if (/[;'"\\<>%]/.test(this.value)) {
                                checkSQLInjection(this.value, 'tên đăng nhập');
                            }
                            checkFormValidity();
                        });
                    }

                    if (hoTen) {
                        hoTen.addEventListener('blur', validateHoTen);
                        hoTen.addEventListener('input', checkFormValidity);
                    }

                    if (email) {
                        email.addEventListener('blur', validateEmail);
                        email.addEventListener('input', checkFormValidity);
                    }

                    if (sdt) {
                        sdt.addEventListener('blur', validateSdt);
                        sdt.addEventListener('input', function () {
                            // Auto-format: chỉ giữ số
                            this.value = this.value.replace(/[^0-9]/g, '');
                            validateSdt();          // <-- thêm dòng này để cập nhật lỗi ngay
                            checkFormValidity();
                        });
                    }

                    if (password) {
                        password.addEventListener('input', function () {
                            validatePassword();
                            checkFormValidity();
                        });
                    }

                    if (confirmPassword) {
                        confirmPassword.addEventListener('input', function () {
                            validateConfirmPassword();
                            checkFormValidity();
                        });
                    }
                    if (tinhInput) {
                        tinhInput.addEventListener('blur', validateTinhThanhPho);
                        tinhInput.addEventListener('input', function () {
                            validateTinhThanhPho();
                            checkFormValidity();
                        });
                    }

                    if (quanInput) {
                        quanInput.addEventListener('blur', validateQuanHuyen);
                        quanInput.addEventListener('input', function () {
                            validateQuanHuyen();
                            checkFormValidity();
                        });
                    }

                    if (chitietInput) {
                        chitietInput.addEventListener('blur', validateDiaChiChiTiet);
                        chitietInput.addEventListener('input', function () {
                            validateDiaChiChiTiet();
                            checkFormValidity();
                        });
                    }

                    if (terms) {
                        terms.addEventListener('change', function () {
                            validateTerms();
                            checkFormValidity();
                        });
                    }

                    // Form submit với final check
                    if (form) {
                        form.addEventListener('submit', function (e) {
                            // Final SQL injection check
                            const inputs = [username, hoTen, email, sdt, password].filter(i => i);
                            for (let input of inputs) {
                                if (input && checkSQLInjection(input.value, input.name)) {
                                    e.preventDefault();
                                    input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    return false;
                                }
                            }

                            if (!checkFormValidity()) {
                                e.preventDefault();
                                const firstError = form.querySelector('.input-invalid');
                                if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        });
                    }


                    setTimeout(checkFormValidity, 100);
                });


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
                            const response = await fetch(`./control/search-suggest.php?q=${encodeURIComponent(query)}`);
                            const result = await response.json();
                            if (result.success && result.data.length > 0) {
                                const limitedResults = result.data.slice(0, 8);
                                suggestionsList.innerHTML = limitedResults.map(product => `
                <a href="${product.url}" class="suggestion-item">
                    <img src="./${product.image}" alt="${product.name}" loading="lazy"
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
                                    // ✅ Sửa path: bỏ "view/" vì đang ở trong view/
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
</body>

</html>