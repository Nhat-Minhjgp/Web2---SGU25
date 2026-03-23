<?php
session_start();
require_once '../control/connect.php';

$errors = [];
$success = '';
$form_data = ['username' => '', 'ho_ten' => '', 'email' => '', 'sdt' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // === 1. SERVER-SIDE SANITIZATION ===
    $form_data['username'] = trim(preg_replace('/[^a-zA-Z0-9]/', '', $_POST['username'] ?? ''));
    $form_data['ho_ten'] = trim(htmlspecialchars($_POST['ho_ten'] ?? '', ENT_QUOTES, 'UTF-8'));
    $form_data['email'] = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $form_data['sdt'] = trim(preg_replace('/[^0-9]/', '', $_POST['sdt'] ?? ''));
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

    if (empty($form_data['sdt']))
        $errors[] = "Số điện thoại không được để trống";
    elseif (!preg_match('/^0[0-9]{9}$/', $form_data['sdt']))
        $errors[] = "Số điện thoại phải bắt đầu bằng số 0 và có 10 số";

    if (empty($password))
        $errors[] = "Mật khẩu không được để trống";
    elseif (strlen($password) < 6)
        $errors[] = "Mật khẩu phải có ít nhất 6 ký tự";

    if ($password !== $confirm_password)
        $errors[] = "Mật khẩu không khớp";

    if (!$terms)
        $errors[] = "Bạn phải đồng ý với điều khoản sử dụng";

    // === 3. DATABASE OPERATIONS (Prepared Statements) ===
    if (empty($errors)) {
        try {
            // Kiểm tra Username trùng
            $stmt = $conn->prepare("SELECT User_id FROM users WHERE Username = ?");
            $stmt->bind_param("s", $form_data['username']);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = "Tên đăng nhập đã tồn tại";
            }
            $stmt->close();

            // Kiểm tra Email trùng
            if (empty($errors)) {
                $stmt = $conn->prepare("SELECT User_id FROM users WHERE email = ?");
                $stmt->bind_param("s", $form_data['email']);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $errors[] = "Email đã được đăng ký";
                }
                $stmt->close();
            }

            // Insert vào DB
            if (empty($errors)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = 0;
                $status = '1';

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

                if ($stmt->execute()) {
                    $success = "Đăng ký thành công! Chuyển hướng...";
                    $form_data = ['username' => '', 'ho_ten' => '', 'email' => '', 'sdt' => ''];
                    header("Refresh: 2; URL=./login.php");
                    exit();
                } else {
                    $errors[] = "Lỗi hệ thống: " . $conn->error;
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            // Không hiển thị lỗi chi tiết cho user (security)
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
    </style>
    <link rel="icon" type="image/svg+xml" href="../img/icons/favicon.png" sizes="32x32">
</head>

<body class="font-sans antialiased bg-gray-50">
    <!-- Popup Overlay -->
    <div id="popup_overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50"></div>

    <!-- Main Wrapper -->
    <div id="wrapper" class="min-h-screen flex flex-col">
        <!-- Header (GIỮ NGUYÊN 100%) -->
        <header id="header" class="sticky top-0 z-40 bg-white shadow-sm">
            <div class="header-wrapper">
                <div id="masthead" class="py-2 md:py-3 border-b">
                    <div class="container mx-auto px-4 flex items-center justify-between">
                        <div class="md:hidden">
                            <button class="menu-toggle p-2">
                                <img src="../img/icons/menu.svg" class="fas fa-bars text-2xl">
                            </button>
                        </div>
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
                                                <a href="https://nvbplay.vn/product-category/san-pham-cham-soc-suc-khoe"
                                                    class="block p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start">
                                                    <div class="w-6 h-6 flex-shrink-0 mr-3"><img
                                                            src="https://nvbplay.vn/wp-content/uploads/2024/10/healthcare-No.svg"
                                                            alt="Chăm sóc sức khoẻ" class="w-full h-full"></div>
                                                    <div>
                                                        <p class="font-bold">Chăm sóc sức khoẻ</p>
                                                    </div>
                                                </a>
                                                <a href="https://nvbplay.vn/product-category/dich-vu"
                                                    class="block p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start">
                                                    <div class="w-6 h-6 flex-shrink-0 mr-3"><img
                                                            src="https://nvbplay.vn/wp-content/uploads/2024/10/customer-service-No.svg"
                                                            alt="Dịch vụ" class="w-full h-full"></div>
                                                    <div>
                                                        <p class="font-bold">Dịch vụ</p>
                                                    </div>
                                                </a>
                                                <div class="icon-box-menu p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start"
                                                    data-menu="news">
                                                    <div class="w-6 h-6 flex-shrink-0 mr-3"><img
                                                            src="https://nvbplay.vn/wp-content/uploads/2024/10/news-No.svg"
                                                            alt="Tin Tức" class="w-full h-full"></div>
                                                    <div>
                                                        <p class="font-bold">Tin Tức</p>
                                                        <p class="text-xs text-gray-500">Xu hướng mới, sự kiện hot, giảm
                                                            giá sốc!</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex-1 pl-4">
                                                <div id="content-badminton" class="menu-content">
                                                    <div class="mb-4">
                                                        <div class="flex items-center justify-between mb-2">
                                                            <h3 class="font-bold">Thương hiệu nổi bật</h3>
                                                            <a href="../view/shop.php"
                                                                class="text-sm text-red-600 hover:text-red-700 flex items-center">Xem
                                                                tất cả <i
                                                                    class="fas fa-chevron-right ml-1 text-xs"></i></a>
                                                        </div>
                                                        <div class="grid grid-cols-4 gap-2">
                                                            <a href="https://nvbplay.vn/shop?_brand=yonex"
                                                                class="flex flex-col items-center text-center group">
                                                                <div class="w-12 h-12 mb-1"><img
                                                                        src="https://nvbplay.vn/wp-content/uploads/2024/10/logo-300x214-1-150x150.webp"
                                                                        alt="Yonex"
                                                                        class="w-full h-full object-contain"></div>
                                                                <span class="text-xs">YONEX</span>
                                                            </a>
                                                            <a href="https://nvbplay.vn/shop?_brand=adidas"
                                                                class="flex flex-col items-center text-center group">
                                                                <div class="w-12 h-12 mb-1"><img
                                                                        src="https://nvbplay.vn/wp-content/uploads/2024/10/ave6by86s-300x300-1-150x150.webp"
                                                                        alt="Adidas"
                                                                        class="w-full h-full object-contain"></div>
                                                                <span class="text-xs">ADIDAS</span>
                                                            </a>
                                                            <a href="https://nvbplay.vn/shop?_brand=li-ning"
                                                                class="flex flex-col items-center text-center group">
                                                                <div class="w-12 h-12 mb-1"><img
                                                                        src="https://nvbplay.vn/wp-content/uploads/2024/10/Logo-li-ning-300x173-1-150x150.webp"
                                                                        alt="Li-Ning"
                                                                        class="w-full h-full object-contain"></div>
                                                                <span class="text-xs">LI-NING</span>
                                                            </a>
                                                            <a href="https://nvbplay.vn/shop?_brand=ds"
                                                                class="flex flex-col items-center text-center group">
                                                                <div class="w-12 h-12 mb-1"><img
                                                                        src="https://nvbplay.vn/wp-content/uploads/2024/10/logo-ds-300x300-1-150x150.jpg"
                                                                        alt="DS" class="w-full h-full object-contain">
                                                                </div>
                                                                <span class="text-xs">DS</span>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="content-pickleball" class="menu-content hidden"></div>
                                                <div id="content-giay" class="menu-content hidden"></div>
                                                <div id="content-news" class="menu-content hidden"></div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li><a href="../view/shop.php"
                                        class="flex items-center text-gray-700 hover:text-red-600 font-medium"><img
                                            src="../img/icons/store.svg" class="w-5 h-5 flex-shrink-0 mr-2"><span>CỬA
                                            HÀNG</span></a></li>
                            </ul>
                        </div>
                        <div id="logo" class="flex-shrink-1 absolute left-1/2 transform -translate-x-1/2">
                            <a href="../index.php" title="NVBPlay" rel="home"><img width="100" height="40"
                                    src="../img/icons/logonvb.png" alt="NVBPlay"
                                    class="h-12 md:h-14 w-auto transform scale-75"></a>
                        </div>
                        <div class="hidden md:flex items-center space-x-4">
                            <div class="address-book"><a href="./my-account/address-book.php"
                                    class="flex items-center text-gray-700 hover:text-red-600"><i
                                        class="fas fa-map-marker-alt mr-1"></i><span
                                        class="shipping-address text-sm"><span class="text">Chọn địa
                                            chỉ</span></span></a></div>
                            <div class="h-5 w-px bg-gray-300"></div>
                            <div class="search-header relative"><button class="search-toggle p-2"><i
                                        class="fas fa-search text-gray-700 hover:text-red-600"></i></button></div>
                            <a href="https://nvbplay.vn/my-account" class="p-2"><i
                                    class="far fa-user text-gray-700 hover:text-red-600 text-xl"></i></a>
                            <a href="https://nvbplay.vn/cart" class="relative p-2"><i
                                    class="fas fa-shopping-basket text-gray-700 hover:text-red-600 text-xl"></i><span
                                    class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">0</span></a>
                        </div>
                        <div class="md:hidden flex items-center space-x-3">
                            <button class="search-toggle p-1"><i class="fas fa-search text-xl"></i></button>
                            <a href="https://nvbplay.vn/cart" class="relative p-1"><i
                                    class="fas fa-shopping-basket text-xl"></i><span
                                    class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">0</span></a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main id="main" class="bg-white mt-20 mb-20">
            <div class="page-wrapper my-account mb">
                <div class="container mx-auto px-5" role="main">
                    <div class="woocommerce">
                        <div class="account-container lightbox-inner max-w-6xl mx-auto">
                            <div class="flex flex-col md:flex-row">
                                <!-- Banner Image -->
                                <div class="hidden md:block banner-login md:w-3/4 mr-10">
                                    <img decoding="async"
                                        src="https://nvbplay.vn/wp-content/themes/nvbplayvn/assets/img/Login-Place.png"
                                        alt="Banner Register" title="My account" class="w-full h-full object-cover"
                                        style="min-height: 600px;">
                                </div>

                                <!-- Register Form -->
                                <div class="md:w-1/4 flex items-center mt-3 justify-center md:p-4 bg-white">
                                    <div class="w-full">
                                        <h1 class="text-center text-lg font-medium mb-4">Đăng ký tài khoản</h1>

                                        <!-- SQL Injection Warning -->
                                        <div id="sqlInjectionWarning" class="sql-injection-warning">
                                            <i class="fas fa-shield-alt"></i>
                                            <strong>Cảnh báo bảo mật:</strong> <span id="sqlInjectionMessage">Phát hiện
                                                ký tự không an toàn</span>
                                        </div>

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

                                        <!-- Thông báo thành công -->
                                        <?php if ($success): ?>
                                            <div class="alert-success p-3 rounded mb-4 text-sm">
                                                <i
                                                    class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                                            </div>
                                        <?php endif; ?>

                                        <form id="registerForm" class="space-y-4" method="POST" action="" novalidate>
                                            <!-- Tên đăng nhập -->
                                            <div>
                                                <input type="text" id="username" name="username"
                                                    placeholder="Tên đăng nhập *"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm"
                                                    value="<?php echo htmlspecialchars($form_data['username']); ?>"
                                                    required>
                                                <div class="error-text" id="username-error">Tên đăng nhập phải có ít
                                                    nhất 3 ký tự</div>
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
                                                <input type="tel" id="sdt" name="sdt" placeholder="Số điện thoại *"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm"
                                                    value="<?php echo htmlspecialchars($form_data['sdt']); ?>" required>
                                                <div class="error-text" id="sdt-error">Số điện thoại phải bắt đầu bằng 0
                                                    và có 10 số</div>
                                            </div>

                                            <!-- Mật khẩu -->
                                            <div>
                                                <input type="password" id="password" name="password"
                                                    placeholder="Mật khẩu *"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm"
                                                    required>
                                                <div class="password-strength" id="password-strength-bar"></div>
                                                <div class="strength-text" id="password-strength-text"></div>
                                                <div class="error-text" id="password-error">Mật khẩu phải có ít nhất 6
                                                    ký tự</div>
                                            </div>

                                            <!-- Xác nhận mật khẩu -->
                                            <div>
                                                <input type="password" id="confirm_password" name="confirm_password"
                                                    placeholder="Xác nhận mật khẩu *"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm"
                                                    required>
                                                <div class="error-text" id="confirm_password-error">Mật khẩu xác nhận
                                                    không khớp</div>
                                            </div>

                                            <!-- Điều khoản -->
                                            <div class="flex items-start gap-2 text-xs">
                                                <input type="checkbox" name="terms" id="terms" class="mt-1" required>
                                                <label for="terms" class="text-gray-600">Tôi đồng ý với <a
                                                        href="../chinh-sach-bao-mat.php"
                                                        class="text-[#FF3F1A] hover:underline">điều khoản sử dụng và
                                                        chính sách bảo mật</a></label>
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
                </div>
            </div>
        </main>

        <!-- Footer (GIỮ NGUYÊN 100%) -->
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

        <!-- Mobile Menu (GIỮ NGUYÊN 100%) -->
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
                    <a href="https://nvbplay.vn/my-account" class="flex items-center text-gray-700">
                        <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3"><i
                                class="far fa-user text-xl text-gray-600"></i></div>
                        <div>
                            <div class="font-medium">Tài khoản</div><span class="text-sm text-gray-500">Đăng nhập / Đăng
                                ký</span>
                        </div>
                    </a>
                </div>
                <!-- Mobile menu content giữ nguyên -->
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('registerForm');
                const username = document.getElementById('username');
                const hoTen = document.getElementById('ho_ten');
                const email = document.getElementById('email');
                const sdt = document.getElementById('sdt');
                const password = document.getElementById('password');
                const confirmPassword = document.getElementById('confirm_password');
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
                    // Lấy giá trị và chỉ giữ số
                    const rawValue = sdt.value.trim();
                    const value = rawValue.replace(/[^0-9]/g, '');
                    const error = document.getElementById('sdt-error');

                    // Check SQL injection trên raw value (trước khi sanitize)
                    if (checkSQLInjection(rawValue, 'số điện thoại')) {
                        sdt.classList.add('input-invalid');
                        sdt.classList.remove('input-valid');
                        return false;
                    }

                    // Update input value với số đã sanitize (UX tốt hơn)
                    if (rawValue !== value) {
                        sdt.value = value;
                    }

                    // Validate: 10 số, bắt đầu bằng 0
                    if (value.length !== 10) {
                        sdt.classList.add('input-invalid');
                        sdt.classList.remove('input-valid');
                        if (error) {
                            error.textContent = value.length < 10 ? 'Số điện thoại phải có đủ 10 số' : 'Số điện thoại không được quá 10 số';
                            error.style.display = 'block';
                        }
                        return false;
                    }

                    if (value.charAt(0) !== '0') {
                        sdt.classList.add('input-invalid');
                        sdt.classList.remove('input-valid');
                        if (error) {
                            error.textContent = 'Số điện thoại phải bắt đầu bằng số 0';
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
</body>

</html>