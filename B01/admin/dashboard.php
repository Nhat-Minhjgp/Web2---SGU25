<?php
session_start();
require_once __DIR__ . '/../control/connect.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

// Lấy thông tin admin
$admin_name = $_SESSION['admin_name'] ?? '';
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
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn {
            animation: fadeIn 0.3s ease-out;
        }
        
        .stat-icon-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stat-change {
            color: #10b981;
        }
    </style>

    <link rel="icon" type="image/svg+xml" href="../img/icons/favicon.png" sizes="32x32">
</head>
<body class="bg-gray-50 font-sans text-gray-800 min-h-screen">

    <!-- HEADER -->
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
        <!-- SIDEBAR - GIỐNG IMPORT.PHP, PRICE.PHP, ORDERS.PHP -->
        <aside class="w-64 bg-white shadow-lg hidden lg:block flex-shrink-0 border-r border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Danh mục chức năng</h3>
            </div>
            <nav class="p-4 space-y-2">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 bg-gradient-custom text-white rounded-lg shadow-md">
                    <i class="fas fa-home w-5"></i> Dashboard
                </a>
                <a href="users.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-users w-5"></i> Quản lý người dùng
                </a>
                <a href="product.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-box w-5"></i> Quản lý sản phẩm
                </a>
                <a href="import.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-arrow-down w-5"></i> Quản lý nhập hàng
                </a>
                <a href="price.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-tag w-5"></i> Quản lý giá bán
                </a>
                <a href="orders.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-receipt w-5"></i> Quản lý đơn hàng
                </a>
                <a href="inventory.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-warehouse w-5"></i> Tồn kho & Báo cáo
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6 lg:p-8 overflow-x-hidden bg-gray-50">
            <div class="bg-white rounded-xl shadow-lg p-6 lg:p-8 min-h-full animate-fadeIn">
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Xin chào, <?php echo htmlspecialchars($admin_username); ?>!</h2>
                    <p class="text-gray-600">Chào mừng bạn quay trở lại hệ thống quản lý NVBPlay. Hôm nay là ngày <?php echo date('d/m/Y'); ?>.</p>
                </div>
                
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
    <div id="modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-md max-h-[80vh] overflow-y-auto">
            <div class="bg-gradient-custom text-white px-6 py-4 rounded-t-xl flex justify-between items-center sticky top-0">
                <h3 class="text-lg font-semibold" id="modalTitle"></h3>
                <button onclick="closeModal()" class="text-white hover:text-gray-200 text-2xl">&times;</button>
            </div>
            <div class="p-6" id="modalBody"></div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end sticky bottom-0 bg-white rounded-b-xl" id="modalFooter"></div>
        </div>
    </div>

    <script>
        function closeModal() {
            const modal = document.getElementById('modal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function openModal(title, content, footer = '') {
            document.getElementById('modalTitle').innerHTML = title;
            document.getElementById('modalBody').innerHTML = content;
            document.getElementById('modalFooter').innerHTML = footer;
            const modal = document.getElementById('modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
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