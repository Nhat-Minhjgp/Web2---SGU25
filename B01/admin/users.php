<?php
session_start();
require_once __DIR__ . '/../control/connect.php';
require_once __DIR__ . '/../control/function.php';

// Lấy thông tin admin
$admin_name = $_SESSION['admin_name'] ?? 'Quản trị viên';
$admin_role = $_SESSION['admin_role'] ?? '';
$admin_username = $_SESSION['admin_username'] ?? '';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

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

// ========== HÀM VALIDATE TÊN ĐĂNG NHẬP ==========
function validateUsername($username, &$errors) {
    $username = trim($username);
    if (empty($username)) {
        $errors[] = "Tên đăng nhập không được để trống";
        return false;
    }
    if (strlen($username) < 3) {
        $errors[] = "Tên đăng nhập phải có ít nhất 3 ký tự";
        return false;
    }
    if (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        $errors[] = "Tên đăng nhập chỉ chứa chữ và số";
        return false;
    }
    if (hasSQLInjection($username)) {
        $errors[] = "Tên đăng nhập chứa ký tự không an toàn";
        return false;
    }
    return true;
}

// ========== HÀM VALIDATE MẬT KHẨU ==========
function validatePassword($password, &$errors) {
    if (empty($password)) {
        $errors[] = "Mật khẩu không được để trống";
        return false;
    }
    if (strlen($password) < 6) {
        $errors[] = "Mật khẩu phải có ít nhất 6 ký tự";
        return false;
    }
    return true;
}

// ========== HÀM VALIDATE HỌ TÊN ==========
function validateFullname($fullname, &$errors) {
    $fullname = trim($fullname);
    if (!empty($fullname) && strlen($fullname) < 2) {
        $errors[] = "Họ tên phải có ít nhất 2 ký tự";
        return false;
    }
    if (!empty($fullname) && hasSQLInjection($fullname)) {
        $errors[] = "Họ tên chứa ký tự không an toàn";
        return false;
    }
    return true;
}

// ========== HÀM VALIDATE EMAIL ==========
function validateEmail($email, &$errors) {
    $email = trim($email);
    if (!empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email không hợp lệ";
            return false;
        }
        if (hasSQLInjection($email)) {
            $errors[] = "Email chứa ký tự không an toàn";
            return false;
        }
    }
    return true;
}

// ========== HÀM VALIDATE SỐ ĐIỆN THOẠI ==========
function validatePhone($phone, &$errors) {
    $phone = trim($phone);
    if (!empty($phone)) {
        if (!preg_match('/^09[0-9]{8}$/', $phone)) {
            $errors[] = "Số điện thoại phải có 10 số, bắt đầu bằng 09";
            return false;
        }
        if (hasSQLInjection($phone)) {
            $errors[] = "Số điện thoại chứa ký tự không an toàn";
            return false;
        }
    }
    return true;
}

// Xử lý các action từ URL (Khóa/Mở khóa)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    
    if ($_GET['action'] == 'lock') {
        toggleUserStatus($conn, $user_id, '0');
        $_SESSION['message'] = 'Đã khóa tài khoản thành công!';
        header('Location: users.php');
        exit();
    } elseif ($_GET['action'] == 'unlock') {
        toggleUserStatus($conn, $user_id, '1');
        $_SESSION['message'] = 'Đã mở khóa tài khoản thành công!';
        header('Location: users.php');
        exit();
    }
}

// Lấy thông báo từ Session
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Xử lý thêm user từ form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $errors = [];
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $ho_ten = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $sdt = trim($_POST['phone']);
    $role = intval($_POST['role']);
    
    // Server-side validation
    $valid = true;
    $valid = $valid && validateUsername($username, $errors);
    $valid = $valid && validatePassword($password, $errors);
    $valid = $valid && validateFullname($ho_ten, $errors);
    $valid = $valid && validateEmail($email, $errors);
    $valid = $valid && validatePhone($sdt, $errors);
    
    if (!$valid) {
        $error = implode('<br>', $errors);
    } else {
        // Kiểm tra username đã tồn tại
        $check = $conn->prepare("SELECT User_id FROM users WHERE Username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $error = 'Tên đăng nhập đã tồn tại!';
        } else {
            $data = [
                'username' => $username,
                'password' => $password,
                'ho_ten' => $ho_ten,
                'email' => $email,
                'sdt' => $sdt,
                'role' => $role,
                'status' => 1
            ];
            
            if (addUser($conn, $data)) {
                $message = 'Thêm tài khoản thành công!';
            } else {
                $error = 'Có lỗi xảy ra khi thêm tài khoản!';
            }
        }
        $check->close();
    }
}

// Xử lý cập nhật user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $errors = [];
    
    $user_id = intval($_POST['user_id']);
    $ho_ten = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $sdt = trim($_POST['phone']);
    $role = intval($_POST['role']);
    $status = intval($_POST['status']);
    
    // Server-side validation
    $valid = true;
    $valid = $valid && validateFullname($ho_ten, $errors);
    $valid = $valid && validateEmail($email, $errors);
    $valid = $valid && validatePhone($sdt, $errors);
    
    if (!$valid) {
        $error = implode('<br>', $errors);
    } else {
        $data = [
            'ho_ten' => $ho_ten,
            'email' => $email,
            'sdt' => $sdt,
            'role' => $role,
            'status' => $status
        ];
        
        if (updateUser($conn, $user_id, $data)) {
            $message = 'Cập nhật tài khoản thành công!';
        } else {
            $error = 'Có lỗi xảy ra khi cập nhật!';
        }
    }
}

// Xử lý reset mật khẩu về mặc định 123456
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_to_default'])) {
    $user_id = intval($_POST['user_id']);
    $default_password = '123456';
    
    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE User_id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);
    
    if ($stmt->execute()) {
        $message = 'Đã đặt lại mật khẩu thành <strong>123456</strong> cho tài khoản này!';
    } else {
        $error = 'Có lỗi xảy ra khi đặt lại mật khẩu!';
    }
    $stmt->close();
}

// Lấy danh sách người dùng
$users = getUsers($conn);

// Chuyển dữ liệu users sang JSON để dùng trong JavaScript
$users_json = json_encode($users);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: '#667eea', secondary: '#764ba2' },
                    backgroundImage: { 'gradient-custom': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }
                }
            }
        }
    </script>
    <style>
        @keyframes slideIn { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .animate-slide-in { animation: slideIn 0.3s ease-out forwards; }
        
        .sidebar {
            width: 280px;
            background: white;
            border-right: 1px solid #e5e7eb;
            padding: 20px 0;
        }
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 20px;
        }
        .sidebar-header h3 {
            font-size: 12px;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
        }
        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .menu-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 8px;
            color: #4b5563;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 14px;
        }
        .menu-btn i {
            width: 20px;
            color: #9ca3af;
        }
        .menu-btn:hover {
            background-color: #f3f4f6;
            color: #667eea;
        }
        .menu-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .menu-btn.active i {
            color: white;
        }
        
        /* Validation styles */
        .input-valid { border-color: #10b981 !important; }
        .input-invalid { border-color: #ef4444 !important; }
        .error-text { color: #ef4444; font-size: 11px; margin-top: 4px; display: none; }
        .field-error { background: #fef2f2; padding: 4px 8px; border-radius: 6px; margin-top: 4px; font-size: 11px; color: #dc2626; }
        .field-error i { margin-right: 4px; font-size: 10px; }
        .sql-injection-warning { background-color: #fef2f2; border: 2px solid #dc2626; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 16px; display: none; }
        .sql-injection-warning i { margin-right: 8px; }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-800">

    <!-- HEADER - ĐỒNG BỘ VỚI DASHBOARD -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="flex justify-between items-center px-6 py-4">
            <h1 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-custom">NVBPlay Admin Panel</h1>
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-3 bg-gray-100 px-4 py-2 rounded-lg">
                    <div class="w-10 h-10 rounded-full bg-gradient-custom flex items-center justify-center text-white font-bold">
                        <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($admin_username); ?></p>
                    </div>
                </div>
                <button onclick="logout()" class="bg-gradient-custom text-white font-semibold px-4 py-2 rounded-lg hover:opacity-90 transition duration-200 shadow-md hover:shadow-lg">
                    <i class="fas fa-sign-out-alt mr-2"></i>Đăng xuất
                </button>
            </div>
        </div>
    </header>

    <div class="flex w-full min-h-[calc(100vh-70px)]">
        
        <!-- SIDEBAR - ĐỒNG BỘ VỚI DASHBOARD -->
        <aside class="w-64 bg-white shadow-lg hidden lg:block flex-shrink-0 border-r border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Danh mục chức năng</h3>
            </div>
            <nav class="p-4 space-y-2">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-home w-5 text-center"></i> Dashboard
                </a>
                <a href="users.php" class="flex items-center gap-3 px-4 py-3 bg-gradient-custom text-white rounded-lg shadow-md transition transform hover:-translate-y-0.5">
                    <i class="fas fa-users w-5 text-center"></i> Quản lý người dùng
                </a>
                <a href="categories.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-list w-5 text-center"></i> Quản lý danh mục
                </a>
                <a href="product.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-box w-5 text-center"></i> Quản lý sản phẩm
                </a>
                <a href="import.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-arrow-down w-5 text-center"></i> Quản lý nhập hàng
                </a>
                <a href="price.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-tag w-5 text-center"></i> Quản lý giá bán
                </a>
                <a href="orders.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-receipt w-5 text-center"></i> Quản lý đơn hàng
                </a>
                <a href="inventory.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-warehouse w-5 text-center"></i> Tồn kho & Báo cáo
                </a>
            </nav>
        </aside>

        <main class="flex-1 p-6 lg:p-8 overflow-x-hidden bg-gray-50">
            <div class="bg-white rounded-xl shadow-lg p-6 lg:p-8 min-h-full">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 pb-6 border-b-2 border-gray-100 gap-4">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="fas fa-users text-primary"></i> Quản lý người dùng
                    </h2>
                    <button onclick="openModal('addModal')" class="bg-gradient-custom hover:opacity-90 text-white px-6 py-2.5 rounded-lg font-medium shadow-lg hover:shadow-xl transition transform hover:-translate-y-0.5 flex items-center gap-2">
                        <i class="fas fa-user-plus"></i> Thêm tài khoản
                    </button>
                </div>

                <?php if (isset($message)): ?>
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3 animate-slide-in">
                        <i class="fas fa-check-circle"></i><span><?php echo $message; ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3 animate-slide-in">
                        <i class="fas fa-exclamation-circle"></i><span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <input type="text" id="searchInput" placeholder="🔍 Tìm kiếm theo tên, email..." class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                    <select id="roleFilter" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition bg-white">
                        <option value="">Tất cả vai trò</option>
                        <option value="nhân viên">Nhân viên</option>
                        <option value="khách hàng">Khách hàng</option>
                    </select>
                    <select id="statusFilter" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition bg-white">
                        <option value="">Tất cả trạng thái</option>
                        <option value="đang hoạt động">Đang hoạt động</option>
                        <option value="đã khóa">Đã khóa</option>
                    </select>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
                    <table id="usersTable" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gradient-custom text-white">
                                <th class="p-4 font-medium text-sm">ID</th>
                                <th class="p-4 font-medium text-sm">Username</th>
                                <th class="p-4 font-medium text-sm">Họ tên</th>
                                <th class="p-4 font-medium text-sm">Email</th>
                                <th class="p-4 font-medium text-sm">Vai trò</th>
                                <th class="p-4 font-medium text-sm">Trạng thái</th>
                                <th class="p-4 font-medium text-sm">Ngày tạo</th>
                                <th class="p-4 font-medium text-sm text-center">Thao tác</th>
                               </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-blue-50/50 transition duration-150">
                                <td class="p-4 text-gray-600"><?php echo $user['User_id']; ?></td>
                                <td class="p-4 font-medium text-gray-800"><?php echo htmlspecialchars($user['Username']); ?></td>
                                <td class="p-4 text-gray-600"><?php echo htmlspecialchars($user['Ho_ten'] ?? ''); ?></td>
                                <td class="p-4 text-gray-600"><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                <td class="p-4">
                                    <?php if ($user['role'] == 1): ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-gradient-custom text-white">Nhân viên</span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-500 text-white">Khách hàng</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4">
                                    <?php if ($user['status'] == 1): ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 border border-green-200">
                                            <i class="fas fa-check-circle mr-1 text-xs"></i> Đang hoạt động
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 border border-red-200">
                                            <i class="fas fa-ban mr-1 text-xs"></i> Đã khóa
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-gray-600 text-sm"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td class="p-4">
                                    <div class="flex items-center justify-center gap-2 flex-wrap">
                                        <button onclick="viewUser(<?php echo $user['User_id']; ?>)" class="w-8 h-8 rounded bg-cyan-500 hover:bg-cyan-600 text-white flex items-center justify-center transition shadow-sm" title="Xem chi tiết">
                                            <i class="fas fa-eye text-xs"></i>
                                        </button>
                                        <button onclick="editUser(<?php echo $user['User_id']; ?>)" class="w-8 h-8 rounded bg-yellow-400 hover:bg-yellow-500 text-gray-800 flex items-center justify-center transition shadow-sm" title="Chỉnh sửa">
                                            <i class="fas fa-edit text-xs"></i>
                                        </button>
                                        <button onclick="resetPasswordDefault(<?php echo $user['User_id']; ?>, '<?php echo htmlspecialchars($user['Username']); ?>')" 
                                                class="w-8 h-8 rounded bg-purple-500 hover:bg-purple-600 text-white flex items-center justify-center transition shadow-sm" title="Đặt lại mật khẩu thành 123456">
                                            <i class="fas fa-key text-xs"></i>
                                        </button>
                                        <?php if ($user['status'] == 1): ?>
                                            <button onclick="lockUser(<?php echo $user['User_id']; ?>, '<?php echo htmlspecialchars($user['Username']); ?>')" 
                                                    class="w-8 h-8 rounded bg-red-500 hover:bg-red-600 text-white flex items-center justify-center transition shadow-sm" title="Khóa tài khoản">
                                                <i class="fas fa-ban text-xs"></i>
                                            </button>
                                        <?php else: ?>
                                            <button onclick="unlockUser(<?php echo $user['User_id']; ?>, '<?php echo htmlspecialchars($user['Username']); ?>')" 
                                                    class="w-8 h-8 rounded bg-green-500 hover:bg-green-600 text-white flex items-center justify-center transition shadow-sm" title="Mở khóa tài khoản">
                                                <i class="fas fa-check-circle text-xs"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal thêm tài khoản (giữ nguyên nội dung) -->
    <div id="addModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[1000] backdrop-blur-sm">
        <div class="bg-white rounded-xl w-full max-w-lg mx-4 shadow-2xl animate-slide-in flex flex-col max-h-[90vh]">
            <div class="bg-gradient-custom text-white p-5 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-bold flex items-center gap-2"><i class="fas fa-user-plus"></i> Thêm tài khoản mới</h3>
                <button onclick="closeModal('addModal')" class="text-white hover:text-gray-200 transition text-xl"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" class="flex-1 overflow-y-auto p-6" id="addUserForm">
                <div id="sqlInjectionWarning" class="sql-injection-warning">
                    <i class="fas fa-shield-alt"></i>
                    <strong>Cảnh báo bảo mật:</strong> <span id="sqlInjectionMessage">Phát hiện ký tự không an toàn</span>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tên đăng nhập <span class="text-red-500">*</span></label>
                        <input type="text" name="username" id="add_username" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                        <div class="error-text" id="username-error">Tên đăng nhập phải có ít nhất 3 ký tự, chỉ chứa chữ và số</div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Mật khẩu <span class="text-red-500">*</span></label>
                        <input type="password" name="password" id="add_password" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                        <div class="error-text" id="password-error">Mật khẩu phải có ít nhất 6 ký tự</div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Họ tên</label>
                        <input type="text" name="fullname" id="add_fullname" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                        <div class="error-text" id="fullname-error">Họ tên phải có ít nhất 2 ký tự</div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="add_email" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                        <div class="error-text" id="email-error">Email không hợp lệ</div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Số điện thoại</label>
                        <input type="text" name="phone" id="add_phone" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition" maxlength="10">
                        <div class="error-text" id="phone-error">Số điện thoại phải có 10 số, bắt đầu bằng 09</div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Vai trò</label>
                        <select name="role" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition bg-white">
                            <option value="0">Khách hàng</option>
                            <option value="1">Nhân viên</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" onclick="closeModal('addModal')" class="px-5 py-2.5 rounded-lg bg-gray-500 text-white hover:bg-gray-600 transition font-medium">Hủy</button>
                    <button type="submit" name="add_user" id="addSubmitBtn" class="px-5 py-2.5 rounded-lg bg-gradient-custom text-white hover:opacity-90 transition font-medium shadow-lg">Lưu tài khoản</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal xem chi tiết (giữ nguyên) -->
    <div id="viewModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[1000] backdrop-blur-sm">
        <div class="bg-white rounded-xl w-full max-w-lg mx-4 shadow-2xl animate-slide-in flex flex-col max-h-[90vh]">
            <div class="bg-gradient-custom text-white p-5 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-bold flex items-center gap-2"><i class="fas fa-info-circle"></i> Chi tiết tài khoản</h3>
                <button onclick="closeModal('viewModal')" class="text-white hover:text-gray-200 transition text-xl"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 overflow-y-auto" id="viewContent"></div>
            <div class="p-5 border-t border-gray-100 flex justify-end bg-gray-50 rounded-b-xl">
                <button onclick="closeModal('viewModal')" class="px-5 py-2.5 rounded-lg bg-gray-500 text-white hover:bg-gray-600 transition font-medium">Đóng</button>
            </div>
        </div>
    </div>

    <!-- Modal chỉnh sửa tài khoản (giữ nguyên) -->
    <div id="editModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[1000] backdrop-blur-sm">
        <div class="bg-white rounded-xl w-full max-w-lg mx-4 shadow-2xl animate-slide-in flex flex-col max-h-[90vh]">
            <div class="bg-gradient-custom text-white p-5 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-bold flex items-center gap-2"><i class="fas fa-edit"></i> Chỉnh sửa tài khoản</h3>
                <button onclick="closeModal('editModal')" class="text-white hover:text-gray-200 transition text-xl"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" class="flex-1 overflow-y-auto p-6" id="editUserForm">
                <input type="hidden" name="user_id" id="editUserId">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tên đăng nhập</label>
                        <input type="text" id="editUsername" readonly class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Họ tên</label>
                        <input type="text" name="fullname" id="editFullname" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                        <div class="error-text" id="edit_fullname-error">Họ tên phải có ít nhất 2 ký tự</div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="editEmail" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                        <div class="error-text" id="edit_email-error">Email không hợp lệ</div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Số điện thoại</label>
                        <input type="text" name="phone" id="editPhone" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition" maxlength="10">
                        <div class="error-text" id="edit_phone-error">Số điện thoại phải có 10 số, bắt đầu bằng 09</div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Vai trò</label>
                        <select name="role" id="editRole" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition bg-white">
                            <option value="0">Khách hàng</option>
                            <option value="1">Nhân viên</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Trạng thái</label>
                        <select name="status" id="editStatus" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition bg-white">
                            <option value="1">Đang hoạt động</option>
                            <option value="0">Đã khóa</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" onclick="closeModal('editModal')" class="px-5 py-2.5 rounded-lg bg-gray-500 text-white hover:bg-gray-600 transition font-medium">Hủy</button>
                    <button type="submit" name="update_user" id="editSubmitBtn" class="px-5 py-2.5 rounded-lg bg-gradient-custom text-white hover:opacity-90 transition font-medium shadow-lg">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>

    <form id="resetDefaultForm" method="POST" style="display:none;">
        <input type="hidden" name="user_id" id="resetDefaultUserId">
        <input type="hidden" name="reset_to_default" value="1">
    </form>

    <script>
        const users = <?php echo $users_json; ?>;

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

        function showSQLWarning(input, message) {
            let warning = input.parentElement.querySelector('.field-error');
            if (!warning) {
                warning = document.createElement('div');
                warning.className = 'field-error text-red-500 text-xs mt-1 flex items-center gap-1';
                input.parentElement.appendChild(warning);
            }
            warning.innerHTML = `<i class="fas fa-shield-alt"></i> ${message}`;
            warning.style.display = 'block';
            input.classList.add('border-red-500');
        }

        function hideSQLWarning(input) {
            const warning = input.parentElement.querySelector('.field-error');
            if (warning) warning.remove();
            input.classList.remove('border-red-500');
        }

        // Validation functions (giống trang đăng ký)
        function validateUsername(input, errorId) {
            const value = input.value.trim();
            const error = document.getElementById(errorId);
            
            if (value.length === 0) {
                input.classList.add('input-invalid');
                if (error) { error.textContent = 'Tên đăng nhập không được để trống'; error.style.display = 'block'; }
                return false;
            }
            if (value.length < 3) {
                input.classList.add('input-invalid');
                if (error) { error.textContent = 'Tên đăng nhập phải có ít nhất 3 ký tự'; error.style.display = 'block'; }
                return false;
            }
            if (!/^[a-zA-Z0-9]+$/.test(value)) {
                input.classList.add('input-invalid');
                if (error) { error.textContent = 'Tên đăng nhập chỉ chứa chữ và số'; error.style.display = 'block'; }
                return false;
            }
            if (hasSQLInjection(value)) {
                input.classList.add('input-invalid');
                if (error) { error.textContent = 'Tên đăng nhập chứa ký tự không an toàn'; error.style.display = 'block'; }
                return false;
            }
            input.classList.add('input-valid');
            input.classList.remove('input-invalid');
            if (error) error.style.display = 'none';
            return true;
        }

        function validatePassword(input, errorId) {
            const value = input.value;
            const error = document.getElementById(errorId);
            
            if (value.length === 0) {
                input.classList.add('input-invalid');
                if (error) { error.textContent = 'Mật khẩu không được để trống'; error.style.display = 'block'; }
                return false;
            }
            if (value.length < 6) {
                input.classList.add('input-invalid');
                if (error) { error.textContent = 'Mật khẩu phải có ít nhất 6 ký tự'; error.style.display = 'block'; }
                return false;
            }
            input.classList.add('input-valid');
            input.classList.remove('input-invalid');
            if (error) error.style.display = 'none';
            return true;
        }

        function validateFullname(input, errorId) {
            const value = input.value.trim();
            const error = document.getElementById(errorId);
            
            if (value.length > 0 && value.length < 2) {
                input.classList.add('input-invalid');
                if (error) { error.textContent = 'Họ tên phải có ít nhất 2 ký tự'; error.style.display = 'block'; }
                return false;
            }
            input.classList.add('input-valid');
            input.classList.remove('input-invalid');
            if (error) error.style.display = 'none';
            return true;
        }

        function validateEmail(input, errorId) {
            const value = input.value.trim();
            const error = document.getElementById(errorId);
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (value.length > 0) {
                if (!emailRegex.test(value)) {
                    input.classList.add('input-invalid');
                    if (error) { error.textContent = 'Email không hợp lệ'; error.style.display = 'block'; }
                    return false;
                }
                if (hasSQLInjection(value)) {
                    input.classList.add('input-invalid');
                    if (error) { error.textContent = 'Email chứa ký tự không an toàn'; error.style.display = 'block'; }
                    return false;
                }
            }
            input.classList.add('input-valid');
            input.classList.remove('input-invalid');
            if (error) error.style.display = 'none';
            return true;
        }

        function validatePhone(input, errorId) {
            const value = input.value.trim();
            const error = document.getElementById(errorId);
            const phoneRegex = /^0[0-9]{9}$/;
            
            if (value.length > 0) {
                if (!phoneRegex.test(value)) {
                    input.classList.add('input-invalid');
                    if (error) { error.textContent = 'Số điện thoại phải có 10 số, bắt đầu bằng 09'; error.style.display = 'block'; }
                    return false;
                }
                if (hasSQLInjection(value)) {
                    input.classList.add('input-invalid');
                    if (error) { error.textContent = 'Số điện thoại chứa ký tự không an toàn'; error.style.display = 'block'; }
                    return false;
                }
            }
            input.classList.add('input-valid');
            input.classList.remove('input-invalid');
            if (error) error.style.display = 'none';
            return true;
        }

        // Add Form Validation
        const addForm = document.getElementById('addUserForm');
        if (addForm) {
            const addUsername = document.getElementById('add_username');
            const addPassword = document.getElementById('add_password');
            const addFullname = document.getElementById('add_fullname');
            const addEmail = document.getElementById('add_email');
            const addPhone = document.getElementById('add_phone');
            
            addUsername?.addEventListener('input', () => validateUsername(addUsername, 'username-error'));
            addPassword?.addEventListener('input', () => validatePassword(addPassword, 'password-error'));
            addFullname?.addEventListener('input', () => validateFullname(addFullname, 'fullname-error'));
            addEmail?.addEventListener('input', () => validateEmail(addEmail, 'email-error'));
            addPhone?.addEventListener('input', () => validatePhone(addPhone, 'phone-error'));
            
            addForm.addEventListener('submit', function(e) {
                const isUsernameValid = validateUsername(addUsername, 'username-error');
                const isPasswordValid = validatePassword(addPassword, 'password-error');
                const isFullnameValid = validateFullname(addFullname, 'fullname-error');
                const isEmailValid = validateEmail(addEmail, 'email-error');
                const isPhoneValid = validatePhone(addPhone, 'phone-error');
                
                if (!isUsernameValid || !isPasswordValid || !isFullnameValid || !isEmailValid || !isPhoneValid) {
                    e.preventDefault();
                    const firstError = addForm.querySelector('.input-invalid');
                    if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        }

        // Edit Form Validation
        const editForm = document.getElementById('editUserForm');
        if (editForm) {
            const editFullname = document.getElementById('editFullname');
            const editEmail = document.getElementById('editEmail');
            const editPhone = document.getElementById('editPhone');
            
            editFullname?.addEventListener('input', () => validateFullname(editFullname, 'edit_fullname-error'));
            editEmail?.addEventListener('input', () => validateEmail(editEmail, 'edit_email-error'));
            editPhone?.addEventListener('input', () => validatePhone(editPhone, 'edit_phone-error'));
            
            editForm.addEventListener('submit', function(e) {
                const isFullnameValid = validateFullname(editFullname, 'edit_fullname-error');
                const isEmailValid = validateEmail(editEmail, 'edit_email-error');
                const isPhoneValid = validatePhone(editPhone, 'edit_phone-error');
                
                if (!isFullnameValid || !isEmailValid || !isPhoneValid) {
                    e.preventDefault();
                    const firstError = editForm.querySelector('.input-invalid');
                    if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        }

        // Search Input Validation
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                if (hasSQLInjection(e.target.value)) {
                    showSQLWarning(e.target, '⚠️ Phát hiện ký tự không an toàn trong tìm kiếm!');
                } else {
                    hideSQLWarning(e.target);
                }
            });
        }

        // Other functions
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }

        function lockUser(userId, username) {
            if (confirm(`⚠️ Bạn có chắc muốn KHÓA tài khoản "${username}"?`)) {
                window.location.href = `?action=lock&id=${userId}`;
            }
        }

        function unlockUser(userId, username) {
            if (confirm(`✅ Bạn có chắc muốn MỞ KHÓA tài khoản "${username}"?`)) {
                window.location.href = `?action=unlock&id=${userId}`;
            }
        }

        function resetPasswordDefault(userId, username) {
            if (confirm(`⚠️ Bạn có chắc muốn ĐẶT LẠI mật khẩu của tài khoản "${username}" thành "123456"?`)) {
                document.getElementById('resetDefaultUserId').value = userId;
                document.getElementById('resetDefaultForm').submit();
            }
        }

        function viewUser(userId) {
            const user = users.find(u => u.User_id == userId);
            if (user) {
                const statusText = (user.status == 1) ? 'Đang hoạt động' : 'Đã khóa';
                const statusColor = (user.status == 1) ? 'text-green-600' : 'text-red-600';
                const roleText = (user.role == 1) ? 'Nhân viên' : 'Khách hàng';
                
                const html = `
                    <div class="grid grid-cols-[100px_1fr] gap-3 text-sm">
                        <div class="font-semibold text-gray-600">ID:</div>
                        <div class="text-gray-800">${user.User_id}</div>
                        <div class="font-semibold text-gray-600">Username:</div>
                        <div class="text-gray-800">${user.Username}</div>
                        <div class="font-semibold text-gray-600">Họ tên:</div>
                        <div class="text-gray-800">${user.Ho_ten || 'Chưa cập nhật'}</div>
                        <div class="font-semibold text-gray-600">Email:</div>
                        <div class="text-gray-800">${user.email || 'Chưa cập nhật'}</div>
                        <div class="font-semibold text-gray-600">Số điện thoại:</div>
                        <div class="text-gray-800">${user.SDT || 'Chưa cập nhật'}</div>
                        <div class="font-semibold text-gray-600">Vai trò:</div>
                        <div class="text-gray-800">${roleText}</div>
                        <div class="font-semibold text-gray-600">Trạng thái:</div>
                        <div class="${statusColor} font-medium">${statusText}</div>
                        <div class="font-semibold text-gray-600">Ngày tạo:</div>
                        <div class="text-gray-800">${new Date(user.created_at).toLocaleDateString('vi-VN')}</div>
                    </div>
                `;
                document.getElementById('viewContent').innerHTML = html;
                openModal('viewModal');
            } else {
                alert('Không tìm thấy thông tin người dùng!');
            }
        }

        function editUser(userId) {
            const user = users.find(u => u.User_id == userId);
            if (user) {
                document.getElementById('editUserId').value = user.User_id;
                document.getElementById('editUsername').value = user.Username;
                document.getElementById('editFullname').value = user.Ho_ten || '';
                document.getElementById('editEmail').value = user.email || '';
                document.getElementById('editPhone').value = user.SDT || '';
                document.getElementById('editRole').value = (user.role == 1) ? '1' : '0';
                document.getElementById('editStatus').value = (user.status == 1) ? '1' : '0';
                openModal('editModal');
            } else {
                alert('Không tìm thấy người dùng!');
            }
        }

        function filterTable() {
            const searchValue = document.getElementById('searchInput').value.toLowerCase();
            const roleValue = document.getElementById('roleFilter').value.toLowerCase();
            const statusValue = document.getElementById('statusFilter').value.toLowerCase();
            const rows = document.querySelectorAll('#usersTable tbody tr');

            rows.forEach(row => {
                const username = row.cells[1].textContent.toLowerCase();
                const fullname = row.cells[2].textContent.toLowerCase();
                const email = row.cells[3].textContent.toLowerCase();
                const role = row.cells[4].textContent.trim().toLowerCase();
                const status = row.cells[5].textContent.trim().toLowerCase();

                let matchSearch = searchValue === '' || username.includes(searchValue) || fullname.includes(searchValue) || email.includes(searchValue);
                let matchRole = roleValue === '' || role.includes(roleValue);
                let matchStatus = statusValue === '' || status.includes(statusValue);

                row.style.display = (matchSearch && matchRole && matchStatus) ? '' : 'none';
            });
        }

        document.getElementById('searchInput')?.addEventListener('keyup', filterTable);
        document.getElementById('roleFilter')?.addEventListener('change', filterTable);
        document.getElementById('statusFilter')?.addEventListener('change', filterTable);

        function logout() {
            if (confirm('Bạn có chắc muốn đăng xuất?')) {
                window.location.href = 'logout.php';
            }
        }

        window.onclick = function(event) {
            if (event.target.id && event.target.id.endsWith('Modal') && event.target.classList.contains('flex')) {
                closeModal(event.target.id);
            }
        }
    </script>
</body>
</html>