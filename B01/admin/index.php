<?php
session_start();
require_once __DIR__ . '/../control/connect.php';

// Nếu đã đăng nhập thì chuyển sang dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập tên đăng nhập và mật khẩu!';
    } else {
        // Tìm user theo Username
        $sql = "SELECT * FROM users WHERE Username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user) {
            // Kiểm tra mật khẩu
            if (password_verify($password, $user['password'])) {
                
                // Kiểm tra Quyền (role = 'admin')
                if ($user['role'] == '1') {
                    
                    // Kiểm tra Trạng thái
                    if ($user['status'] == 'active') {
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_id'] = $user['User_id'];
                        $_SESSION['admin_name'] = $user['Ho_ten'];
                        $_SESSION['admin_username'] = $user['Username'];
                        $_SESSION['admin_role'] = $user['role'];
                        
                        header('Location: dashboard.php');
                        exit();
                    } else {
                        $error = 'Tài khoản đã bị khóa!';
                    }
                } else {
                    $error = 'Bạn không có quyền truy cập Admin!';
                }
            } else {
                $error = 'Sai mật khẩu!';
            }
        } else {
            $error = 'Tên đăng nhập không tồn tại!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn {
            animation: fadeIn 0.3s ease-out;
        }
        /* Giữ màu gradient cũ */
        .bg-gradient-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .btn-gradient-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .btn-gradient-custom:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46a1 100%);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-custom font-sans">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 mx-4 animate-fadeIn">
        <!-- Header -->
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Admin Login</h2>
            <p class="text-gray-500 text-sm">Đăng nhập để quản lý hệ thống</p>
        </div>
        
        <!-- Error Message -->
        <?php if ($error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded flex items-center animate-fadeIn">
            <i class="fas fa-exclamation-circle mr-3 text-red-500"></i>
            <span class="text-sm"><?php echo htmlspecialchars($error); ?></span>
        </div>
        <?php endif; ?>
        
        <!-- Login Form -->
        <form method="POST" action="">
            <div class="mb-6">
                <label for="username" class="block text-gray-700 text-sm font-semibold mb-2">
                    <i class="fas fa-user mr-2" style="color: #667eea;"></i>Tên đăng nhập
                </label>
                <input type="text" id="username" name="username" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#667eea] focus:border-transparent transition duration-200"
                       placeholder="Nhập tên đăng nhập" required>
            </div>
            
            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-semibold mb-2">
                    <i class="fas fa-lock mr-2" style="color: #667eea;"></i>Mật khẩu
                </label>
                <input type="password" id="password" name="password" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#667eea] focus:border-transparent transition duration-200"
                       placeholder="Nhập mật khẩu" required>
            </div>
            
            <button type="submit" 
                    class="w-full btn-gradient-custom text-white font-semibold py-3 px-4 rounded-lg transform hover:-translate-y-0.5 transition duration-200 shadow-lg hover:shadow-xl">
                <i class="fas fa-sign-in-alt mr-2"></i> Đăng nhập
            </button>
        </form>
        
        <!-- Footer -->
        <div class="text-center mt-6">
            <p class="text-gray-500 text-sm">
                <i class="far fa-copyright mr-1"></i> Hệ thống quản lý NVBPlay
            </p>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>