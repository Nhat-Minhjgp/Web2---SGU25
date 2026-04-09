<?php
session_start();
require_once __DIR__ . '/../control/connect.php';

// Nếu đã đăng nhập thì chuyển sang dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

// ========== HÀM KIỂM TRA SQL INJECTION ==========
function hasSQLInjection($value) {
    $patterns = [
        '/\b(SELECT|INSERT|UPDATE|DELETE|DROP|TRUNCATE|ALTER|CREATE|EXEC|EXECUTE|UNION)\b/i',
        '/(--|\/\*|\*\/|#)/',
        '/\bOR\b\s+[\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+|\bAND\b\s+[\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+/i',
        '/\bxp_\w+|sp_\w+/i',
        '/\b(WAITFOR|BENCHMARK|SLEEP)\b/i',
        '/%00|%27|%22/i',
        '/;/'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $value)) {
            return true;
        }
    }
    return false;
}

// ========== HÀM VALIDATE USERNAME ==========
function validateUsername($username, &$error) {
    $username = trim($username);
    if (empty($username)) {
        $error = 'Vui lòng nhập tên đăng nhập!';
        return false;
    }
    if (strlen($username) < 3) {
        $error = 'Tên đăng nhập phải có ít nhất 3 ký tự!';
        return false;
    }
    if (strlen($username) > 50) {
        $error = 'Tên đăng nhập không được vượt quá 50 ký tự!';
        return false;
    }
    if (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        $error = 'Tên đăng nhập chỉ chứa chữ và số!';
        return false;
    }
    if (hasSQLInjection($username)) {
        $error = 'Tên đăng nhập chứa ký tự không an toàn!';
        return false;
    }
    return true;
}

// ========== HÀM VALIDATE PASSWORD ==========
function validatePassword($password, &$error) {
    if (empty($password)) {
        $error = 'Vui lòng nhập mật khẩu!';
        return false;
    }
    if (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự!';
        return false;
    }
    // Kiểm tra password có SQL injection (dù password đã được hash, vẫn check để an toàn)
    if (hasSQLInjection($password)) {
        $error = 'Mật khẩu chứa ký tự không an toàn!';
        return false;
    }
    return true;
}

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    $valid = true;
    if (!validateUsername($username, $error)) {
        $valid = false;
    }
    if (!validatePassword($password, $error) && $valid) {
        $valid = false;
    }
    
    if ($valid) {
        // Tìm user theo Username (dùng prepared statement - đã an toàn)
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
                    if ($user['status'] == '1') {
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_id'] = $user['User_id'];
                        $_SESSION['admin_name'] = $user['Ho_ten'];
                        $_SESSION['admin_username'] = $user['Username'];
                        $_SESSION['admin_role'] = $user['role'];
                        
                        header('Location: dashboard.php');
                        exit();
                    } else {
                        $error = 'Tài khoản đã bị khóa! Vui lòng liên hệ quản trị viên.';
                    }
                } else {
                    $error = 'Bạn không có quyền truy cập Admin!';
                }
            } else {
                $error = 'Sai thông tin đăng nhập! Vui lòng thử lại.';
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
    <title>Đăng nhập Admin - NVBPlay</title>
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
        
        /* Validation styles */
        .input-invalid {
            border-color: #ef4444 !important;
            border-width: 2px !important;
        }
        .input-valid {
            border-color: #10b981 !important;
            border-width: 2px !important;
        }
        .field-error {
            background: #fef2f2;
            padding: 4px 8px;
            border-radius: 6px;
            margin-top: 4px;
            font-size: 11px;
            color: #dc2626;
        }
        .field-error i {
            margin-right: 4px;
            font-size: 10px;
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
<body class="min-h-screen flex items-center justify-center bg-gradient-custom font-sans">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 mx-4 animate-fadeIn">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="flex justify-center mb-4">
                <img src="../img/icons/logonvb.png " alt="NVBPlay" class="h-16">
            </div>
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Admin Login</h2>
            <p class="text-gray-500 text-sm">Đăng nhập để quản lý hệ thống</p>
        </div>
        
        <!-- SQL Injection Warning -->
        <div id="sqlInjectionWarning" class="sql-injection-warning">
            <i class="fas fa-shield-alt"></i>
            <strong>Cảnh báo bảo mật:</strong> <span id="sqlInjectionMessage">Phát hiện ký tự không an toàn</span>
        </div>
        
        <!-- Error Message -->
        <?php if ($error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded flex items-center animate-fadeIn">
            <i class="fas fa-exclamation-circle mr-3 text-red-500"></i>
            <span class="text-sm"><?php echo htmlspecialchars($error); ?></span>
        </div>
        <?php endif; ?>
        
        <!-- Login Form -->
        <form method="POST" action="" id="loginForm">
            <div class="mb-6">
                <label for="username" class="block text-gray-700 text-sm font-semibold mb-2">
                    <i class="fas fa-user mr-2" style="color: #667eea;"></i>Tên đăng nhập
                </label>
                <input type="text" id="username" name="username" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#667eea] focus:border-transparent transition duration-200"
                       placeholder="Nhập tên đăng nhập" required>
                <div class="error-text" id="username-error" style="display:none; color:#ef4444; font-size:11px; margin-top:4px;"></div>
            </div>
            
            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-semibold mb-2">
                    <i class="fas fa-lock mr-2" style="color: #667eea;"></i>Mật khẩu
                </label>
                <input type="password" id="password" name="password" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#667eea] focus:border-transparent transition duration-200"
                       placeholder="Nhập mật khẩu" required>
                <div class="error-text" id="password-error" style="display:none; color:#ef4444; font-size:11px; margin-top:4px;"></div>
            </div>
            
            <button type="submit" id="submitBtn"
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

    <script>
        // SQL Injection Detection Patterns
        const sqlInjectionPatterns = [
            /\b(SELECT|INSERT|UPDATE|DELETE|DROP|TRUNCATE|ALTER|CREATE|EXEC|EXECUTE|UNION)\b/i,
            /(--|\/\*|\*\/|#)/,
            /\bOR\b\s+['"]?\d+['"]?\s*=\s*['"]?\d+|\bAND\b\s+['"]?\d+['"]?\s*=\s*['"]?\d+/i,
            /\bxp_\w+|sp_\w+/i,
            /\b(WAITFOR|BENCHMARK|SLEEP)\b/i,
            /%00|%27|%22/i
        ];

        function hasSQLInjection(value) {
            if (!value || !/[;'"\\<>%]/.test(value)) return false;
            for (let pattern of sqlInjectionPatterns) {
                if (pattern.test(value)) return true;
            }
            return false;
        }

        function showError(input, message, errorId) {
            const errorDiv = document.getElementById(errorId);
            if (errorDiv) {
                errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
                errorDiv.style.display = 'block';
            }
            input.classList.add('input-invalid');
            input.classList.remove('input-valid');
        }

        function hideError(input, errorId) {
            const errorDiv = document.getElementById(errorId);
            if (errorDiv) errorDiv.style.display = 'none';
            input.classList.remove('input-invalid');
        }

        function showSQLWarning(message) {
            const warning = document.getElementById('sqlInjectionWarning');
            const messageSpan = document.getElementById('sqlInjectionMessage');
            if (warning && messageSpan) {
                messageSpan.textContent = message;
                warning.style.display = 'block';
            }
        }

        function hideSQLWarning() {
            const warning = document.getElementById('sqlInjectionWarning');
            if (warning) warning.style.display = 'none';
        }

        // Validate Username
        function validateUsername() {
            const input = document.getElementById('username');
            const value = input.value.trim();
            
            if (value.length === 0) {
                showError(input, 'Tên đăng nhập không được để trống', 'username-error');
                return false;
            }
            if (value.length < 3) {
                showError(input, 'Tên đăng nhập phải có ít nhất 3 ký tự', 'username-error');
                return false;
            }
            if (value.length > 50) {
                showError(input, 'Tên đăng nhập không được vượt quá 50 ký tự', 'username-error');
                return false;
            }
            if (!/^[a-zA-Z0-9]+$/.test(value)) {
                showError(input, 'Tên đăng nhập chỉ chứa chữ và số', 'username-error');
                return false;
            }
            if (hasSQLInjection(value)) {
                showError(input, 'Tên đăng nhập chứa ký tự không an toàn', 'username-error');
                showSQLWarning('Phát hiện ký tự không an toàn trong tên đăng nhập!');
                return false;
            }
            hideError(input, 'username-error');
            hideSQLWarning();
            return true;
        }

        // Validate Password
        function validatePassword() {
            const input = document.getElementById('password');
            const value = input.value;
            
            if (value.length === 0) {
                showError(input, 'Mật khẩu không được để trống', 'password-error');
                return false;
            }
            if (value.length < 6) {
                showError(input, 'Mật khẩu phải có ít nhất 6 ký tự', 'password-error');
                return false;
            }
            if (hasSQLInjection(value)) {
                showError(input, 'Mật khẩu chứa ký tự không an toàn', 'password-error');
                showSQLWarning('Phát hiện ký tự không an toàn trong mật khẩu!');
                return false;
            }
            hideError(input, 'password-error');
            return true;
        }

        // Check form validity
        function checkFormValidity() {
            const isUsernameValid = validateUsername();
            const isPasswordValid = validatePassword();
            const isValid = isUsernameValid && isPasswordValid;
            
            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.disabled = !isValid;
                submitBtn.classList.toggle('opacity-50', !isValid);
                submitBtn.classList.toggle('cursor-not-allowed', !isValid);
            }
            return isValid;
        }

        // Event listeners
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        
        usernameInput.addEventListener('input', function() {
            validateUsername();
            checkFormValidity();
        });
        
        passwordInput.addEventListener('input', function() {
            validatePassword();
            checkFormValidity();
        });
        
        // Form submit
        const form = document.getElementById('loginForm');
        form.addEventListener('submit', function(e) {
            if (!checkFormValidity()) {
                e.preventDefault();
                const firstError = document.querySelector('.input-invalid');
                if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
        
        // Initial validation
        setTimeout(checkFormValidity, 100);
    </script>
</body>
</html>