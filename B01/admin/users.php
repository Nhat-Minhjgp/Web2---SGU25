<?php
session_start();
require_once __DIR__ . '/../control/connect.php';
require_once __DIR__ . '/function.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

// Xử lý các action từ URL
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    
    if ($_GET['action'] == 'lock') {
        toggleUserStatus($conn, $user_id, 'blocked');
        $message = 'Đã khóa tài khoản thành công!';
    } elseif ($_GET['action'] == 'unlock') {
        toggleUserStatus($conn, $user_id, 'active');
        $message = 'Đã mở khóa tài khoản thành công!';
    }
}

// Xử lý thêm user từ form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $data = [
        'username' => $_POST['username'],
        'password' => $_POST['password'],
        'ho_ten' => $_POST['fullname'],
        'email' => $_POST['email'],
        'sdt' => $_POST['phone'],
        'role' => $_POST['role'],
        'status' => 'active'
    ];
    
    if (addUser($conn, $data)) {
        $message = 'Thêm tài khoản thành công!';
    } else {
        $error = 'Có lỗi xảy ra khi thêm tài khoản!';
    }
}

// Xử lý cập nhật user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $user_id = $_POST['user_id'];
    $data = [
        'ho_ten' => $_POST['fullname'],
        'email' => $_POST['email'],
        'sdt' => $_POST['phone'],
        'role' => $_POST['role'],
        'status' => $_POST['status']
    ];
    
    if (updateUser($conn, $user_id, $data)) {
        $message = 'Cập nhật tài khoản thành công!';
    } else {
        $error = 'Có lỗi xảy ra khi cập nhật!';
    }
}

// Xử lý reset mật khẩu
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $user_id = $_POST['user_id'];
    $new_password = $_POST['new_password'];
    
    if (changePassword($conn, $user_id, $new_password)) {
        $message = 'Đặt lại mật khẩu thành công!';
    } else {
        $error = 'Có lỗi xảy ra!';
    }
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
    <title>Quản lý người dùng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Container chính */
        .users-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        /* Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .page-header h2 {
            color: #333;
            font-size: 24px;
        }

        .page-header h2 i {
            color: #667eea;
            margin-right: 10px;
        }

        /* Buttons */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: #333;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        /* Filter Bar */
        .filter-bar {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }

        .filter-bar input,
        .filter-bar select {
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }

        .filter-bar input:focus,
        .filter-bar select:focus {
            outline: none;
            border-color: #667eea;
        }

        /* Table */
        .table-wrapper {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 500;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            color: #333;
        }

        tr:hover {
            background: #f8f9ff;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
        }

        .badge-blocked {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-admin {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .badge-staff {
            background: #17a2b8;
            color: white;
        }

        .badge-customer {
            background: #6c757d;
            color: white;
        }

        /* Action Buttons */
        .action-group {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .action-btn {
            width: 35px;
            height: 35px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            color: white;
            font-size: 14px;
        }

        .btn-view { background: #17a2b8; }
        .btn-edit { background: #ffc107; color: #333; }
        .btn-reset { background: #fd7e14; }
        .btn-lock { background: #dc3545; }
        .btn-unlock { background: #28a745; }

        .action-btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideIn 0.3s;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px 12px 0 0;
        }

        .modal-header h3 {
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group input[readonly] {
            background: #f5f5f5;
            cursor: not-allowed;
        }

        /* Message */
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 12px;
        }

        .info-label {
            font-weight: 500;
            color: #666;
        }

        .info-value {
            color: #333;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header class="header">
        <h1 class="header-title">NVBPlay Admin Panel</h1>
        <div class="header-right">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)); ?>
                </div>
                <div>
                    <p class="user-name">
                        <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>
                        <span class="role-badge role-admin">Admin</span>
                    </p>
                    <p class="user-role"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? ''); ?></p>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Đăng xuất
            </a>
        </div>
    </header>

    <div class="main-container">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>Danh mục chức năng</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="menu-btn">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="users.php" class="menu-btn active">
                    <i class="fas fa-users"></i> Quản lý người dùng
                </a>
                <a href="categories.php" class="menu-btn">
                    <i class="fas fa-tags"></i> Quản lý danh mục
                </a>
                <a href="products.php" class="menu-btn">
                    <i class="fas fa-box"></i> Quản lý sản phẩm
                </a>
                <a href="import.php" class="menu-btn">
                    <i class="fas fa-arrow-down"></i> Quản lý nhập hàng
                </a>
                <a href="price.php" class="menu-btn">
                    <i class="fas fa-tag"></i> Quản lý giá bán
                </a>
                <a href="orders.php" class="menu-btn">
                    <i class="fas fa-receipt"></i> Quản lý đơn hàng
                </a>
                <a href="inventory.php" class="menu-btn">
                    <i class="fas fa-warehouse"></i> Tồn kho & Báo cáo
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <div class="users-container">
                <!-- Page Header -->
                <div class="page-header">
                    <h2><i class="fas fa-users"></i> Quản lý người dùng</h2>
                    <button onclick="openModal('addModal')" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Thêm tài khoản
                    </button>
                </div>

                <!-- Hiển thị thông báo -->
                <?php if (isset($message)): ?>
                    <div class="message success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="message error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Filter Bar -->
                <div class="filter-bar">
                    <input type="text" id="searchInput" placeholder="🔍 Tìm kiếm theo tên, email...">
                    <select id="roleFilter">
                        <option value="">Tất cả vai trò</option>
                        <option value="admin">Admin</option>
                        <option value="staff">Nhân viên</option>
                        <option value="customer">Khách hàng</option>
                    </select>
                    <select id="statusFilter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active">Đang hoạt động</option>
                        <option value="blocked">Đã khóa</option>
                    </select>
                </div>

                <!-- Users Table -->
                <div class="table-wrapper">
                    <table id="usersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Họ tên</th>
                                <th>Email</th>
                                <th>Vai trò</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['User_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['Username']); ?></td>
                                <td><?php echo htmlspecialchars($user['Ho_ten'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                <td>
                                    <?php 
                                    $roleClass = '';
                                    $roleName = '';
                                    if ($user['role'] === 'admin') {
                                        $roleClass = 'badge-admin';
                                        $roleName = 'Admin';
                                    } elseif ($user['role'] === 'staff') {
                                        $roleClass = 'badge-staff';
                                        $roleName = 'Nhân viên';
                                    } else {
                                        $roleClass = 'badge-customer';
                                        $roleName = 'Khách hàng';
                                    }
                                    ?>
                                    <span class="badge <?php echo $roleClass; ?>"><?php echo $roleName; ?></span>
                                </td>
                                <td>
                                    <?php if ($user['status'] === 'active'): ?>
                                        <span class="badge badge-active">Đang hoạt động</span>
                                    <?php else: ?>
                                        <span class="badge badge-blocked">Đã khóa</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="action-group">
                                        <button onclick="viewUser(<?php echo $user['User_id']; ?>)" class="action-btn btn-view" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="editUser(<?php echo $user['User_id']; ?>)" class="action-btn btn-edit" title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="resetPassword(<?php echo $user['User_id']; ?>)" class="action-btn btn-reset" title="Đặt lại mật khẩu">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <?php if ($user['status'] === 'active'): ?>
                                            <a href="?action=lock&id=<?php echo $user['User_id']; ?>" class="action-btn btn-lock" title="Khóa tài khoản" onclick="return confirm('Bạn có chắc muốn KHÓA tài khoản này?')">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="?action=unlock&id=<?php echo $user['User_id']; ?>" class="action-btn btn-unlock" title="Mở khóa tài khoản" onclick="return confirm('Bạn có chắc muốn MỞ KHÓA tài khoản này?')">
                                                <i class="fas fa-check-circle"></i>
                                            </a>
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

    <!-- MODAL THÊM NGƯỜI DÙNG -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Thêm tài khoản mới</h3>
                <button onclick="closeModal('addModal')" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tên đăng nhập <span style="color: red;">*</span></label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Mật khẩu <span style="color: red;">*</span></label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Họ tên</label>
                        <input type="text" name="fullname">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email">
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="text" name="phone">
                    </div>
                    <div class="form-group">
                        <label>Vai trò</label>
                        <select name="role">
                            <option value="customer">Khách hàng</option>
                            <option value="staff">Nhân viên</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('addModal')" class="btn btn-secondary">Hủy</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Lưu tài khoản</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL XEM CHI TIẾT -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-info-circle"></i> Chi tiết tài khoản</h3>
                <button onclick="closeModal('viewModal')" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="viewContent">
                <!-- Nội dung sẽ load bằng JavaScript -->
            </div>
            <div class="modal-footer">
                <button onclick="closeModal('viewModal')" class="btn btn-secondary">Đóng</button>
            </div>
        </div>
    </div>

    <!-- MODAL CHỈNH SỬA -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Chỉnh sửa tài khoản</h3>
                <button onclick="closeModal('editModal')" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="editUserId">
                    <div class="form-group">
                        <label>Tên đăng nhập</label>
                        <input type="text" id="editUsername" readonly>
                    </div>
                    <div class="form-group">
                        <label>Họ tên</label>
                        <input type="text" name="fullname" id="editFullname">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="editEmail">
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="text" name="phone" id="editPhone">
                    </div>
                    <div class="form-group">
                        <label>Vai trò</label>
                        <select name="role" id="editRole">
                            <option value="customer">Khách hàng</option>
                            <option value="staff">Nhân viên</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select name="status" id="editStatus">
                            <option value="active">Đang hoạt động</option>
                            <option value="blocked">Đã khóa</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('editModal')" class="btn btn-secondary">Hủy</button>
                    <button type="submit" name="update_user" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL ĐẶT LẠI MẬT KHẨU -->
    <div id="resetModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-key"></i> Đặt lại mật khẩu</h3>
                <button onclick="closeModal('resetModal')" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="resetUserId">
                    <div class="form-group">
                        <label>Mật khẩu mới</label>
                        <input type="password" name="new_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('resetModal')" class="btn btn-secondary">Hủy</button>
                    <button type="submit" name="reset_password" class="btn btn-warning">Đặt lại</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPT -->
    <script>
        // ============================================
        // DỮ LIỆU USERS TỪ PHP
        // ============================================
        const users = <?php echo $users_json; ?>;
        console.log('Dữ liệu users:', users); // Kiểm tra xem có dữ liệu không

        // ============================================
        // MODAL FUNCTIONS
        // ============================================
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        // ============================================
        // VIEW USER DETAIL - SỬA LỖI KHÔNG HIỆN
        // ============================================
        function viewUser(userId) {
            console.log('Đang xem user có ID:', userId);
            
            // Tìm user trong mảng users
            const user = users.find(u => u.User_id == userId);
            
            if (user) {
                console.log('Tìm thấy user:', user);
                
                // Xác định trạng thái
                const statusText = user.status === 'active' ? 'Đang hoạt động' : 'Đã khóa';
                const statusColor = user.status === 'active' ? '#28a745' : '#dc3545';
                
                // Xác định vai trò
                let roleText = '';
                if (user.role === 'admin') roleText = 'Admin';
                else if (user.role === 'staff') roleText = 'Nhân viên';
                else roleText = 'Khách hàng';
                
                // Tạo nội dung HTML
                const html = `
                    <div class="info-grid">
                        <div class="info-label">ID:</div>
                        <div class="info-value">${user.User_id}</div>
                        
                        <div class="info-label">Username:</div>
                        <div class="info-value">${user.Username}</div>
                        
                        <div class="info-label">Họ tên:</div>
                        <div class="info-value">${user.Ho_ten || 'Chưa cập nhật'}</div>
                        
                        <div class="info-label">Email:</div>
                        <div class="info-value">${user.email || 'Chưa cập nhật'}</div>
                        
                        <div class="info-label">Số điện thoại:</div>
                        <div class="info-value">${user.SDT || 'Chưa cập nhật'}</div>
                        
                        <div class="info-label">Vai trò:</div>
                        <div class="info-value">${roleText}</div>
                        
                        <div class="info-label">Trạng thái:</div>
                        <div class="info-value" style="color: ${statusColor};">${statusText}</div>
                        
                        <div class="info-label">Ngày tạo:</div>
                        <div class="info-value">${new Date(user.created_at).toLocaleDateString('vi-VN')}</div>
                    </div>
                `;
                
                // Đưa vào modal
                document.getElementById('viewContent').innerHTML = html;
                
                // Mở modal
                openModal('viewModal');
            } else {
                console.log('Không tìm thấy user có ID:', userId);
                alert('Không tìm thấy thông tin người dùng!');
            }
        }

        // ============================================
        // EDIT USER
        // ============================================
        function editUser(userId) {
            console.log('Đang sửa user:', userId);
            
            const user = users.find(u => u.User_id == userId);
            
            if (user) {
                document.getElementById('editUserId').value = user.User_id;
                document.getElementById('editUsername').value = user.Username;
                document.getElementById('editFullname').value = user.Ho_ten || '';
                document.getElementById('editEmail').value = user.email || '';
                document.getElementById('editPhone').value = user.SDT || '';
                document.getElementById('editRole').value = user.role;
                document.getElementById('editStatus').value = user.status;
                
                openModal('editModal');
            } else {
                alert('Không tìm thấy người dùng!');
            }
        }

        // ============================================
        // RESET PASSWORD
        // ============================================
        function resetPassword(userId) {
            document.getElementById('resetUserId').value = userId;
            openModal('resetModal');
        }

        // ============================================
        // FILTER TABLE
        // ============================================
        document.getElementById('searchInput').addEventListener('keyup', filterTable);
        document.getElementById('roleFilter').addEventListener('change', filterTable);
        document.getElementById('statusFilter').addEventListener('change', filterTable);

        function filterTable() {
            const searchValue = document.getElementById('searchInput').value.toLowerCase();
            const roleValue = document.getElementById('roleFilter').value;
            const statusValue = document.getElementById('statusFilter').value;

            const rows = document.querySelectorAll('#usersTable tbody tr');

            rows.forEach(row => {
                const username = row.cells[1].textContent.toLowerCase();
                const fullname = row.cells[2].textContent.toLowerCase();
                const email = row.cells[3].textContent.toLowerCase();
                const role = row.cells[4].textContent.trim().toLowerCase();
                const status = row.cells[5].textContent.trim();

                let matchSearch = searchValue === '' || 
                    username.includes(searchValue) || 
                    fullname.includes(searchValue) || 
                    email.includes(searchValue);

                let matchRole = roleValue === '' || role.includes(roleValue);
                
                let matchStatus = statusValue === '' || 
                    (statusValue === 'active' && status === 'Đang hoạt động') ||
                    (statusValue === 'blocked' && status === 'Đã khóa');

                if (matchSearch && matchRole && matchStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // ============================================
        // CLICK OUTSIDE MODAL
        // ============================================
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        }

        // ============================================
        // KIỂM TRA KHI TRANG LOAD
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Trang đã load xong');
            console.log('Số lượng users:', users.length);
        });
    </script>
</body>
</html>