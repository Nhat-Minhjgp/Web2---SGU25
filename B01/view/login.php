<?php
session_start();
// --- KẾT NỐI DATABASE (MySQLi) ---
require_once '../control/connect.php';

// Khởi tạo biến tránh warning
$errors = [];
$success = '';
$form_data = ['username' => '', 'password' => ''];

// === SERVER-SIDE SQL INJECTION PATTERNS ===
$sqlInjectionPatterns = [
    '/(\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b|\bUNION\b|\bEXEC\b|\bTRUNCATE\b)/i',
    '/(--|\/\*|\*\/|#|;)/',
    '/(\bOR\b|\bAND\b)\s*([\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+|[\'"]+[\'"]*)/i',
    '/\b(WAITFOR|BENCHMARK|SLEEP|xp_|sp_)\b/i',
    '/[\'"]\s*OR\s*[\'"]?1[\'"]?\s*=\s*[\'"]?1/i',
    '/%00|%27|%22|%3B/i'  // URL-encoded dangerous chars
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

// --- XỬ LÝ FORM SUBMIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu & sanitize
    $raw_username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // === SERVER-SIDE SQL INJECTION CHECK ===
    if (checkSQLInjectionServer($raw_username) || checkSQLInjectionServer($password)) {
        $errors[] = "Phát hiện ký tự không an toàn. Vui lòng nhập lại.";
    } else {
        // Sanitize sau khi đã check SQL injection
        $form_data['username'] = trim(preg_replace('/[^a-zA-Z0-9]/', '', $raw_username));
    }

    // Validation cơ bản
    if (empty($form_data['username']) && empty($errors)) {
        $errors[] = "Tên đăng nhập không được để trống";
    }
    if (empty($password) && empty($errors)) {
        $errors[] = "Mật khẩu không được để trống";
    }

    // Xử lý database nếu không có lỗi
    if (empty($errors)) {
        try {
            // ✅ PREPARED STATEMENT - Lớp bảo vệ chính chống SQL Injection
            $stmt = $conn->prepare("SELECT User_id, Username, password, Ho_ten, email, SDT, role, status FROM users WHERE Username = ?");
            $stmt->bind_param("s", $form_data['username']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Kiểm tra status
                    if ($user['status'] !== 1) {
                        $errors[] = "Tài khoản của bạn đã bị khóa";
                    }
                    //  CHẶN ROLE 1 (Staff/Admin) không vào user area
                    elseif ($user['role'] == 1) {
                        $errors[] = "Tài khoản Staff/Admin không được đăng nhập vào trang user.";
                    } else {
                        // Đăng nhập thành công - Tạo session
                        $_SESSION['user_id'] = $user['User_id'];
                        $_SESSION['username'] = $user['Username'];
                        $_SESSION['ho_ten'] = $user['Ho_ten'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];

                        // Remember me
                        if ($remember) {
                            setcookie('remember_user', $form_data['username'], [
                                'expires' => time() + (86400 * 30),
                                'path' => '/',
                                'httponly' => true,
                                'samesite' => 'Lax'
                            ]);
                        }

                        $success = "Đăng nhập thành công! Chuyển hướng...";
                        header("Refresh: 2; URL=../index.php");
                        exit();
                    }
                } else {
                    $errors[] = "Tên đăng nhập hoặc mật khẩu không đúng";
                }
            } else {
                $errors[] = "Tên đăng nhập hoặc mật khẩu không đúng";
            }
            $stmt->close();
        } catch (Exception $e) {
            // Không hiển thị lỗi chi tiết cho user
            error_log("Login Error: " . $e->getMessage());
            $errors[] = "Lỗi hệ thống. Vui lòng thử lại sau.";
        }
    }
}

// Tự động điền username nếu có remember cookie
if (empty($form_data['username']) && isset($_COOKIE['remember_user'])) {
    $form_data['username'] = htmlspecialchars($_COOKIE['remember_user']);
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | NVBPlay</title>
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

        /* Password toggle */
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6b7280;
        }

        .password-toggle:hover {
            color: #dc2626;
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
                                        alt="Banner Login" title="My account" class="w-full h-full object-cover"
                                        style="min-height: 500px;">
                                </div>

                                <!-- Login Form -->
                                <div class="md:w-1/4 flex items-center mt-3 justify-center md:p-6 bg-white">
                                    <div class="w-full">
                                        <h1 class="text-center text-lg font-medium mb-4">Đăng nhập</h1>
                                      


                                        <!-- Thông báo lỗi -->
                                        <?php if (!empty($errors)): ?>
                                            <div class="alert-error p-3 rounded mb-4 text-sm">
                                                <ul class="list-disc list-inside"><?php foreach ($errors as $error): ?>
                                                        <li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($success): ?>
                                            <div class="alert-success p-3 rounded mb-4 text-sm"><i
                                                    class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Thông báo thành công -->
                                        <?php if ($success): ?>
                                            <div class="alert-success p-3 rounded mb-4 text-sm">
                                                <i class="fas fa-check-circle mr-2"></i>
                                                <?php echo htmlspecialchars($success); ?>
                                            </div>
                                        <?php endif; ?>

                                        <form id="loginForm" class="space-y-4" method="POST" action="" novalidate>
                                            <!-- Tên đăng nhập -->
                                            <div>
                                                <input type="text" id="username" name="username"
                                                    placeholder="Tên đăng nhập *"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm"
                                                    value="<?php echo htmlspecialchars($form_data['username']); ?>"
                                                    required>
                                                <div class="error-text" id="username-error">Tên đăng nhập không được để
                                                    trống</div>
                                            </div>

                                            <!-- Mật khẩu -->
                                            <div class="relative">
                                                <input type="password" id="password" name="password"
                                                    placeholder="Mật khẩu *"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm"
                                                    required>
                                                <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                                                <div class="error-text" id="password-error">Mật khẩu không được để trống
                                                </div>
                                            </div>

                                            <!-- Remember Me -->
                                            <div class="flex items-center gap-2 text-xs">
                                                <input type="checkbox" name="remember" id="remember" class="mt-1">
                                                <label for="remember" class="text-gray-600">Ghi nhớ đăng nhập</label>
                                            </div>

                                            <!-- Nút đăng nhập -->
                                            <button type="submit" id="submitBtn"
                                                class="w-full bg-[#FF3F1A] text-white text-sm py-2.5 rounded hover:bg-red-600 font-medium transition"
                                                disabled>
                                                ĐĂNG NHẬP
                                            </button>

                                            <!-- Links -->
                                            <div class="flex justify-between text-xs pt-2">
                                                <a href="#" class="text-gray-500 hover:text-red-600">Quên mật khẩu?</a>
                                                <a href="./register.php"
                                                    class="text-[#FF3F1A] font-bold hover:underline">Đăng ký</a>
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

        <!-- JavaScript Form Validation -->
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('loginForm');
                const username = document.getElementById('username');
                const password = document.getElementById('password');
                const submitBtn = document.getElementById('submitBtn');
                const togglePassword = document.getElementById('togglePassword');

                // Toggle password visibility
                if (togglePassword) {
                    togglePassword.addEventListener('click', function () {
                        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                        password.setAttribute('type', type);
                        this.classList.toggle('fa-eye');
                        this.classList.toggle('fa-eye-slash');
                    });
                }

                // Validation functions
                function validateUsername() {
                    const value = username.value.trim();
                    const error = document.getElementById('username-error');
                    if (value.length === 0) {
                        username.classList.add('input-invalid');
                        username.classList.remove('input-valid');
                        error.style.display = 'block';
                        return false;
                    }
                    username.classList.add('input-valid');
                    username.classList.remove('input-invalid');
                    error.style.display = 'none';
                    return true;
                }

                function validatePassword() {
                    const value = password.value;
                    const error = document.getElementById('password-error');
                    if (value.length === 0) {
                        password.classList.add('input-invalid');
                        password.classList.remove('input-valid');
                        error.style.display = 'block';
                        return false;
                    }
                    password.classList.add('input-valid');
                    password.classList.remove('input-invalid');
                    error.style.display = 'none';
                    return true;
                }

                function checkFormValidity() {
                    const isValid = validateUsername() && validatePassword();
                    submitBtn.disabled = !isValid;
                    submitBtn.classList.toggle('opacity-50', !isValid);
                    submitBtn.classList.toggle('cursor-not-allowed', !isValid);
                    return isValid;
                }

                // Event listeners
                username.addEventListener('blur', validateUsername);
                username.addEventListener('input', checkFormValidity);
                password.addEventListener('blur', validatePassword);
                password.addEventListener('input', checkFormValidity);

                // Form submit
                form.addEventListener('submit', function (e) {
                    if (!checkFormValidity()) {
                        e.preventDefault();
                        const firstError = form.querySelector('.input-invalid');
                        if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });

                // Initial check
                checkFormValidity();
            });
        </script>

        <!-- JavaScript Menu (GIỮ NGUYÊN 100% từ file gốc) -->
        <script>
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
                const categoryButton = document.querySelector('.relative button');
                if (categoryButton) {
                    categoryButton.addEventListener('click', function () {
                        const subMenu = this.nextElementSibling;
                        subMenu.classList.toggle('hidden');
                        this.querySelector('i').classList.toggle('fa-chevron-down');
                        this.querySelector('i').classList.toggle('fa-chevron-up');
                    });
                }
            });
        </script>

        <!-- JavaScript Desktop Menu (GIỮ NGUYÊN 100% từ file gốc) -->
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
                const menuToggle = document.querySelector('.menu-toggle');
                const closeMenu = document.querySelector('.close-menu');
                const mobileMenu = document.getElementById('main-menu');
                if (menuToggle) { menuToggle.addEventListener('click', function () { mobileMenu.classList.remove('-translate-x-full'); }); }
                if (closeMenu) { closeMenu.addEventListener('click', function () { mobileMenu.classList.add('-translate-x-full'); }); }
                const categoryButton = document.querySelector('.relative button');
                if (categoryButton) {
                    categoryButton.addEventListener('click', function () {
                        const subMenu = this.nextElementSibling;
                        subMenu.classList.toggle('hidden');
                        this.querySelector('i').classList.toggle('fa-chevron-down');
                        this.querySelector('i').classList.toggle('fa-chevron-up');
                    });
                }
            });
        </script>


        <script>
        document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('loginForm');
        const username = document.getElementById('username');
        const password = document.getElementById('password');
        const submitBtn = document.getElementById('submitBtn');
        const togglePassword = document.getElementById('togglePassword');
        const sqlInjectionWarning = document.getElementById('sqlInjectionWarning');
        const sqlInjectionMessage = document.getElementById('sqlInjectionMessage');

        // === CLIENT-SIDE SQL INJECTION PATTERNS ===
        const sqlPatterns = [
        /\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|EXEC|EXECUTE)\b/i,
        /(--|\/\*|\*\/|#|;)/,
        /['"]\s*(OR|AND)\s*['"]?\d+['"]?\s*=\s*['"]?\d+['"]?/i,
        /\b(WAITFOR|BENCHMARK|SLEEP|xp_|sp_)\b/i,
        /%00|%27|%22|%3B/i
        ];

        function checkSQLInjection(value, fieldName) {
        for (let pattern of sqlPatterns) {
        if (pattern.test(value)) {
        showSQLWarning(`Phát hiện ký tự không an toàn trong ${fieldName}`);
        return true;
        }
        }
        return false;
        }

        function showSQLWarning(msg) {
        sqlWarning.style.display = 'block';
        sqlWarningMsg.textContent = msg;
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }

        function hideSQLWarning() {
        sqlWarning.style.display = 'none';
        if (validateUsername() && validatePassword()) {
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
        }

        // Toggle password visibility
        if (togglePassword) {
        togglePassword.addEventListener('click', function() {
        const type = password.type === 'password' ? 'text' : 'password';
        password.type = type;
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
        });
        }

        function validateUsername() {
        const value = username.value.trim();
        const error = document.getElementById('username-error');

        if (checkSQLInjection(value, 'tên đăng nhập')) {
        username.classList.add('input-invalid');
        username.classList.remove('input-valid');
        return false;
        }

        if (value.length === 0) {
        username.classList.add('input-invalid');
        username.classList.remove('input-valid');
        error.style.display = 'block';
        return false;
        }
        username.classList.add('input-valid');
        username.classList.remove('input-invalid');
        error.style.display = 'none';
        hideSQLWarning();
        return true;
        }

        function validatePassword() {
        const value = password.value;
        const error = document.getElementById('password-error');

        if (checkSQLInjection(value, 'mật khẩu')) {
        password.classList.add('input-invalid');
        password.classList.remove('input-valid');
        return false;
        }

        if (value.length === 0) {
        password.classList.add('input-invalid');
        password.classList.remove('input-valid');
        error.style.display = 'block';
        return false;
        }
        password.classList.add('input-valid');
        password.classList.remove('input-invalid');
        error.style.display = 'none';
        hideSQLWarning();
        return true;
        }

        function checkFormValidity() {
        const isValid = validateUsername() && validatePassword();
        submitBtn.disabled = !isValid;
        submitBtn.classList.toggle('opacity-50', !isValid);
        submitBtn.classList.toggle('cursor-not-allowed', !isValid);
        return isValid;
        }

        // Event listeners
        username.addEventListener('blur', validateUsername);
        username.addEventListener('input', function() {
        if (/[;'"\\<>%]/.test(this.value)) checkSQLInjection(this.value, 'tên đăng nhập');
            checkFormValidity();
            });

            password.addEventListener('blur', validatePassword);
            password.addEventListener('input', function() {
            if (/[;'"\\<>%]/.test(this.value)) checkSQLInjection(this.value, 'mật khẩu');
                checkFormValidity();
                });

                // Form submit - final check
                form.addEventListener('submit', function(e) {
                if (checkSQLInjection(username.value, 'tên đăng nhập') || checkSQLInjection(password.value, 'mật khẩu'))
                {
                e.preventDefault();
                return false;
                }
                if (!checkFormValidity()) {
                e.preventDefault();
                const firstError = form.querySelector('.input-invalid');
                if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                });

                // Initial check
                checkFormValidity();
                });
                </script>
</body>

</html>