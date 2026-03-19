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
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Cấu hình màu sắc giống design cũ -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#667eea',
                        secondary: '#764ba2',
                    },
                    backgroundImage: {
                        'gradient-custom': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    }
                }
            }
        }
    </script>
    <style>
        /* Chỉ giữ lại animation cho modal, không giữ lại style layout */
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .animate-slide-in {
            animation: slideIn 0.3s ease-out forwards;
        }
        /* Ẩn thanh cuộn nhưng vẫn cuộn được */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-800">

    <!-- HEADER -->
    <header class="bg-white shadow-md sticky top-0 z-50 h-[70px] flex items-center w-full">
        <div class="w-full px-6 flex justify-between items-center">
            <h1 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-custom">
                NVBPlay Admin Panel
            </h1>
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-custom flex items-center justify-center text-white font-bold shadow-lg">
                        <?php echo strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)); ?>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">
                            <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>
                            <span class="ml-2 text-xs bg-gradient-custom text-white px-2 py-0.5 rounded-full">Admin</span>
                        </p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? ''); ?></p>
                    </div>
                </div>
                <a href="logout.php" class="flex items-center gap-2 text-red-500 hover:text-red-700 transition font-medium">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </div>
    </header>

    <!-- CONTAINER CHÍNH -->
    <div class="flex w-full min-h-[calc(100vh-70px)]">
        
        <!-- SIDEBAR -->
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
                    <i class="fas fa-tags w-5 text-center"></i> Quản lý danh mục
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

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6 lg:p-8 overflow-x-hidden bg-gray-50">
            <div class="bg-white rounded-xl shadow-lg p-6 lg:p-8 min-h-full">
                <!-- Page Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 pb-6 border-b-2 border-gray-100 gap-4">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="fas fa-users text-primary"></i> Quản lý người dùng
                    </h2>
                    <button onclick="openModal('addModal')" class="bg-gradient-custom hover:opacity-90 text-white px-6 py-2.5 rounded-lg font-medium shadow-lg hover:shadow-xl transition transform hover:-translate-y-0.5 flex items-center gap-2">
                        <i class="fas fa-user-plus"></i> Thêm tài khoản
                    </button>
                </div>

                <!-- Messages -->
                <?php if (isset($message)): ?>
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3 animate-slide-in">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $message; ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3 animate-slide-in">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <!-- Filter Bar -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <input type="text" id="searchInput" placeholder="🔍 Tìm kiếm theo tên, email..." 
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                    <select id="roleFilter" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition bg-white">
                        <option value="">Tất cả vai trò</option>
                        <option value="admin">Admin</option>
                        <option value="staff">Nhân viên</option>
                        <option value="customer">Khách hàng</option>
                    </select>
                    <select id="statusFilter" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition bg-white">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active">Đang hoạt động</option>
                        <option value="blocked">Đã khóa</option>
                    </select>
                </div>

                <!-- Users Table -->
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
                                    <?php 
                                    $roleClass = '';
                                    $roleName = '';
                                    if ($user['role'] === 'admin') {
                                        $roleClass = 'bg-gradient-custom text-white';
                                        $roleName = 'Admin';
                                    } elseif ($user['role'] === 'staff') {
                                        $roleClass = 'bg-cyan-500 text-white';
                                        $roleName = 'Nhân viên';
                                    } else {
                                        $roleClass = 'bg-gray-500 text-white';
                                        $roleName = 'Khách hàng';
                                    }
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $roleClass; ?>"><?php echo $roleName; ?></span>
                                </td>
                                <td class="p-4">
                                    <?php if ($user['status'] === 'active'): ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 border border-green-200">Đang hoạt động</span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 border border-red-200">Đã khóa</span>
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
                                        <button onclick="resetPassword(<?php echo $user['User_id']; ?>)" class="w-8 h-8 rounded bg-orange-500 hover:bg-orange-600 text-white flex items-center justify-center transition shadow-sm" title="Đặt lại mật khẩu">
                                            <i class="fas fa-key text-xs"></i>
                                        </button>
                                        <?php if ($user['status'] === 'active'): ?>
                                            <a href="?action=lock&id=<?php echo $user['User_id']; ?>" class="w-8 h-8 rounded bg-red-500 hover:bg-red-600 text-white flex items-center justify-center transition shadow-sm" title="Khóa tài khoản" onclick="return confirm('Bạn có chắc muốn KHÓA tài khoản này?')">
                                                <i class="fas fa-ban text-xs"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="?action=unlock&id=<?php echo $user['User_id']; ?>" class="w-8 h-8 rounded bg-green-500 hover:bg-green-600 text-white flex items-center justify-center transition shadow-sm" title="Mở khóa tài khoản" onclick="return confirm('Bạn có chắc muốn MỞ KHÓA tài khoản này?')">
                                                <i class="fas fa-check-circle text-xs"></i>
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
    <div id="addModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[1000] backdrop-blur-sm">
        <div class="bg-white rounded-xl w-full max-w-lg mx-4 shadow-2xl animate-slide-in flex flex-col max-h-[90vh]">
            <div class="bg-gradient-custom text-white p-5 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-bold flex items-center gap-2"><i class="fas fa-user-plus"></i> Thêm tài khoản mới</h3>
                <button onclick="closeModal('addModal')" class="text-white hover:text-gray-200 transition text-xl"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" class="flex-1 overflow-y-auto p-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tên đăng nhập <span class="text-red-500">*</span></label>
                        <input type="text" name="username" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Mật khẩu <span class="text-red-500">*</span></label>
                        <input type="password" name="password" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Họ tên</label>
                        <input type="text" name="fullname" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Số điện thoại</label>
                        <input type="text" name="phone" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Vai trò</label>
                        <select name="role" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition bg-white">
                            <option value="customer">Khách hàng</option>
                            <option value="staff">Nhân viên</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" onclick="closeModal('addModal')" class="px-5 py-2.5 rounded-lg bg-gray-500 text-white hover:bg-gray-600 transition font-medium">Hủy</button>
                    <button type="submit" name="add_user" class="px-5 py-2.5 rounded-lg bg-gradient-custom text-white hover:opacity-90 transition font-medium shadow-lg">Lưu tài khoản</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL XEM CHI TIẾT -->
    <div id="viewModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[1000] backdrop-blur-sm">
        <div class="bg-white rounded-xl w-full max-w-lg mx-4 shadow-2xl animate-slide-in flex flex-col max-h-[90vh]">
            <div class="bg-gradient-custom text-white p-5 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-bold flex items-center gap-2"><i class="fas fa-info-circle"></i> Chi tiết tài khoản</h3>
                <button onclick="closeModal('viewModal')" class="text-white hover:text-gray-200 transition text-xl"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6 overflow-y-auto" id="viewContent">
                <!-- Nội dung sẽ load bằng JavaScript -->
            </div>
            <div class="p-5 border-t border-gray-100 flex justify-end bg-gray-50 rounded-b-xl">
                <button onclick="closeModal('viewModal')" class="px-5 py-2.5 rounded-lg bg-gray-500 text-white hover:bg-gray-600 transition font-medium">Đóng</button>
            </div>
        </div>
    </div>

    <!-- MODAL CHỈNH SỬA -->
    <div id="editModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[1000] backdrop-blur-sm">
        <div class="bg-white rounded-xl w-full max-w-lg mx-4 shadow-2xl animate-slide-in flex flex-col max-h-[90vh]">
            <div class="bg-gradient-custom text-white p-5 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-bold flex items-center gap-2"><i class="fas fa-edit"></i> Chỉnh sửa tài khoản</h3>
                <button onclick="closeModal('editModal')" class="text-white hover:text-gray-200 transition text-xl"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" class="flex-1 overflow-y-auto p-6">
                <input type="hidden" name="user_id" id="editUserId">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tên đăng nhập</label>
                        <input type="text" id="editUsername" readonly class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Họ tên</label>
                        <input type="text" name="fullname" id="editFullname" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="editEmail" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Số điện thoại</label>
                        <input type="text" name="phone" id="editPhone" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Vai trò</label>
                        <select name="role" id="editRole" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition bg-white">
                            <option value="customer">Khách hàng</option>
                            <option value="staff">Nhân viên</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Trạng thái</label>
                        <select name="status" id="editStatus" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition bg-white">
                            <option value="active">Đang hoạt động</option>
                            <option value="blocked">Đã khóa</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" onclick="closeModal('editModal')" class="px-5 py-2.5 rounded-lg bg-gray-500 text-white hover:bg-gray-600 transition font-medium">Hủy</button>
                    <button type="submit" name="update_user" class="px-5 py-2.5 rounded-lg bg-gradient-custom text-white hover:opacity-90 transition font-medium shadow-lg">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL ĐẶT LẠI MẬT KHẨU -->
    <div id="resetModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[1000] backdrop-blur-sm">
        <div class="bg-white rounded-xl w-full max-w-md mx-4 shadow-2xl animate-slide-in">
            <div class="bg-gradient-custom text-white p-5 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-bold flex items-center gap-2"><i class="fas fa-key"></i> Đặt lại mật khẩu</h3>
                <button onclick="closeModal('resetModal')" class="text-white hover:text-gray-200 transition text-xl"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="user_id" id="resetUserId">
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Mật khẩu mới</label>
                    <input type="password" name="new_password" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" onclick="closeModal('resetModal')" class="px-5 py-2.5 rounded-lg bg-gray-500 text-white hover:bg-gray-600 transition font-medium">Hủy</button>
                    <button type="submit" name="reset_password" class="px-5 py-2.5 rounded-lg bg-yellow-400 text-gray-800 hover:bg-yellow-500 transition font-medium shadow-lg">Đặt lại</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPT -->
    <script>
        const users = <?php echo $users_json; ?>;

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

        function viewUser(userId) {
            const user = users.find(u => u.User_id == userId);
            
            if (user) {
                const statusText = user.status === 'active' ? 'Đang hoạt động' : 'Đã khóa';
                const statusColor = user.status === 'active' ? 'text-green-600' : 'text-red-600';
                
                let roleText = '';
                if (user.role === 'admin') roleText = 'Admin';
                else if (user.role === 'staff') roleText = 'Nhân viên';
                else roleText = 'Khách hàng';
                
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
                document.getElementById('editRole').value = user.role;
                document.getElementById('editStatus').value = user.status;
                openModal('editModal');
            } else {
                alert('Không tìm thấy người dùng!');
            }
        }

        function resetPassword(userId) {
            document.getElementById('resetUserId').value = userId;
            openModal('resetModal');
        }

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

                let matchSearch = searchValue === '' || username.includes(searchValue) || fullname.includes(searchValue) || email.includes(searchValue);
                let matchRole = roleValue === '' || role.includes(roleValue);
                let matchStatus = statusValue === '' || 
                    (statusValue === 'active' && status === 'Đang hoạt động') ||
                    (statusValue === 'blocked' && status === 'Đã khóa');

                row.style.display = (matchSearch && matchRole && matchStatus) ? '' : 'none';
            });
        }

        window.onclick = function(event) {
            if (event.target.id && event.target.id.endsWith('Modal') && event.target.classList.contains('flex')) {
                closeModal(event.target.id);
            }
        }
    </script>
</body>
</html>