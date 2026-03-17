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
        $sql = "SELECT * FROM users WHERE Username = ? AND role IN ('admin', 'staff')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user) {
            if (password_verify($password, $user['password'])) {
                if ($user['status'] === 'active') {
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
                $error = 'Mật khẩu không đúng!';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>Admin Login</h2>
            <p>Đăng nhập để quản lý hệ thống</p>
        </div>
        
        <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user mr-2"></i>Tên đăng nhập
                </label>
                <input type="text" id="username" name="username" placeholder="Nhập tên đăng nhập" required>
            </div>
            
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock mr-2"></i>Mật khẩu
                </label>
                <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required>
            </div>
            
            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt mr-2"></i> Đăng nhập
            </button>
        </form>
        
        <div class="login-footer">
            <p>Hệ thống quản lý NVBPlay</p>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>