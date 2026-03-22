<?php
session_start();
require_once __DIR__ . '/../control/connect.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

// Lấy thông tin admin
$admin_name = $_SESSION['admin_name'] ?? 'Quản trị viên';
$admin_role = $_SESSION['admin_role'] ?? '';
$admin_username = $_SESSION['admin_username'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - Admin</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        /* Giữ màu gradient cũ */
        .bg-gradient-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .text-gradient-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .stat-icon-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .role-badge-admin {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .role-badge-staff {
            background: #28a745;
        }
        .menu-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .menu-btn.active i {
            color: white;
        }
        .menu-btn:hover {
            background: #f3f4f6;
        }
        .stat-change {
            color: #10b981;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn {
            animation: fadeIn 0.3s ease-out;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans min-h-screen">
    <!-- HEADER -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="flex justify-between items-center px-6 py-4">
            <h1 class="text-2xl font-bold text-gradient-custom">NVBPlay Admin Panel</h1>
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-3 bg-gray-100 px-4 py-2 rounded-lg">
                    <div class="w-10 h-10 rounded-full bg-gradient-custom flex items-center justify-center text-white font-bold">
                        <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                    </div>
                    <div>
                        <p class="font-semibold text-sm text-gray-800">
                            <?php echo htmlspecialchars($admin_name); ?>
                            <?php if ($admin_role === 'admin'): ?>
                                <span class="ml-2 px-2 py-1 text-xs rounded-full text-white role-badge-admin">Admin</span>
                            <?php else: ?>
                                <span class="ml-2 px-2 py-1 text-xs rounded-full text-white role-badge-staff">Staff</span>
                            <?php endif; ?>
                        </p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($admin_username); ?></p>
                    </div>
                </div>
                <button onclick="logout()" class="bg-gradient-custom text-white font-semibold px-4 py-2 rounded-lg hover:opacity-90 transition duration-200 shadow-md hover:shadow-lg">
                    <i class="fas fa-sign-out-alt mr-2"></i>Đăng xuất
                </button>
            </div>
        </div>
    </header>

    <div class="flex">
        <!-- SIDEBAR -->
        <aside class="w-64 bg-white shadow-lg min-h-screen">
            <div class="p-4 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Danh mục chức năng</h3>
            </div>
            <nav class="p-2">
                <a href="dashboard.php" class="menu-btn flex items-center space-x-3 px-4 py-3 rounded-lg mb-1 transition duration-200 active">
                    <i class="fas fa-home w-5 text-gray-500"></i>
                    <span>Dashboard</span>
                </a>
                <a href="users.php" class="menu-btn flex items-center space-x-3 px-4 py-3 rounded-lg mb-1 transition duration-200 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-users w-5 text-gray-500"></i>
                    <span>Quản lý người dùng</span>
                </a>
                                
                <a href="product.php" class="menu-btn flex items-center space-x-3 px-4 py-3 rounded-lg mb-1 transition duration-200 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-box w-5 text-gray-500"></i>
                    <span>Quản lý sản phẩm</span>
                </a>
                <a href="import.php" class="menu-btn flex items-center space-x-3 px-4 py-3 rounded-lg mb-1 transition duration-200 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-arrow-down w-5 text-gray-500"></i>
                    <span>Quản lý nhập hàng</span>
                </a>
                <a href="price.php" class="menu-btn flex items-center space-x-3 px-4 py-3 rounded-lg mb-1 transition duration-200 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-tag w-5 text-gray-500"></i>
                    <span>Quản lý giá bán</span>
                </a>
                <a href="orders.php" class="menu-btn flex items-center space-x-3 px-4 py-3 rounded-lg mb-1 transition duration-200 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-receipt w-5 text-gray-500"></i>
                    <span>Quản lý đơn hàng</span>
                </a>
                <a href="inventory.php" class="menu-btn flex items-center space-x-3 px-4 py-3 rounded-lg mb-1 transition duration-200 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-warehouse w-5 text-gray-500"></i>
                    <span>Tồn kho & Báo cáo</span>
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-8">
            <div class="bg-white rounded-xl shadow-lg p-8 animate-fadeIn">
                <h2 class="text-3xl font-bold text-gradient-custom mb-4">Xin chào, <?php echo htmlspecialchars($admin_name); ?>!</h2>
                <p class="text-gray-600 mb-2">Chào mừng bạn quay trở lại hệ thống quản lý NVBPlay. Hôm nay là ngày <?php echo date('d/m/Y'); ?>.</p>
                <p class="text-gray-600 mb-8">Bạn đang đăng nhập với vai trò: 
                    <strong class="<?php echo $admin_role === 'admin' ? 'text-gradient-custom' : 'text-green-600'; ?>">
                        <?php echo $admin_role === 'admin' ? 'Quản trị viên' : 'Nhân viên'; ?>
                    </strong>
                </p>
                
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Card 1 -->
                    <div class="bg-gray-50 rounded-xl p-6 hover:shadow-lg transition duration-200">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-sm text-gray-500 font-medium">Tổng người dùng</span>
                            <div class="w-12 h-12 rounded-full stat-icon-bg flex items-center justify-center text-white">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-800 mb-2">1,234</div>
                        <div class="stat-change text-sm flex items-center">
                            <i class="fas fa-arrow-up mr-1"></i>
                            <span>+12% so với tháng trước</span>
                        </div>
                    </div>
                    
                    <!-- Card 2 -->
                    <div class="bg-gray-50 rounded-xl p-6 hover:shadow-lg transition duration-200">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-sm text-gray-500 font-medium">Tổng sản phẩm</span>
                            <div class="w-12 h-12 rounded-full stat-icon-bg flex items-center justify-center text-white">
                                <i class="fas fa-box text-xl"></i>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-800 mb-2">567</div>
                        <div class="stat-change text-sm flex items-center">
                            <i class="fas fa-arrow-up mr-1"></i>
                            <span>+5% so với tháng trước</span>
                        </div>
                    </div>
                    
                    <!-- Card 3 -->
                    <div class="bg-gray-50 rounded-xl p-6 hover:shadow-lg transition duration-200">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-sm text-gray-500 font-medium">Đơn hàng hôm nay</span>
                            <div class="w-12 h-12 rounded-full stat-icon-bg flex items-center justify-center text-white">
                                <i class="fas fa-shopping-cart text-xl"></i>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-800 mb-2">89</div>
                        <div class="stat-change text-sm flex items-center">
                            <i class="fas fa-arrow-up mr-1"></i>
                            <span>+23% so với hôm qua</span>
                        </div>
                    </div>
                    
                    <!-- Card 4 -->
                    <div class="bg-gray-50 rounded-xl p-6 hover:shadow-lg transition duration-200">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-sm text-gray-500 font-medium">Doanh thu tháng</span>
                            <div class="w-12 h-12 rounded-full stat-icon-bg flex items-center justify-center text-white">
                                <i class="fas fa-chart-line text-xl"></i>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-800 mb-2">123.4M</div>
                        <div class="stat-change text-sm flex items-center">
                            <i class="fas fa-arrow-up mr-1"></i>
                            <span>+18% so với tháng trước</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL -->
    <div id="modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-md max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gradient-custom" id="modalTitle"></h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition duration-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6" id="modalBody"></div>
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-200" id="modalFooter"></div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        function closeModal() {
            document.getElementById('modal').classList.remove('flex', 'hidden');
            document.getElementById('modal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function openModal(title, content, footer = '') {
            document.getElementById('modalTitle').innerHTML = title;
            document.getElementById('modalBody').innerHTML = content;
            document.getElementById('modalFooter').innerHTML = footer;
            document.getElementById('modal').classList.remove('hidden');
            document.getElementById('modal').classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function logout() {
            if (confirm('Bạn có chắc muốn đăng xuất?')) {
                window.location.href = 'logout.php';
            }
        }

        // Click outside modal
        window.onclick = function(event) {
            const modal = document.getElementById('modal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>