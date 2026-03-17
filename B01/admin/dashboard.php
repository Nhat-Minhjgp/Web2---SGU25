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
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- HEADER -->
    <header class="header">
        <h1 class="header-title">NVBPlay Admin Panel</h1>
        <div class="header-right">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                </div>
                <div>
                    <p class="user-name">
                        <?php echo htmlspecialchars($admin_name); ?>
                        <?php if ($admin_role === 'admin'): ?>
                            <span class="role-badge role-admin">Admin</span>
                        <?php else: ?>
                            <span class="role-badge role-staff">Staff</span>
                        <?php endif; ?>
                    </p>
                    <p class="user-role"><?php echo htmlspecialchars($admin_username); ?></p>
                </div>
            </div>
            <button onclick="logout()" class="logout-btn">
                <i class="fas fa-sign-out-alt mr-2"></i>Đăng xuất
            </button>
        </div>
    </header>

    <div class="main-container">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>Danh mục chức năng</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="menu-btn active">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="users.php" class="menu-btn">
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
            <div class="welcome-card">
                <h2>Xin chào, <?php echo htmlspecialchars($admin_name); ?>!</h2>
                <p>Chào mừng bạn quay trở lại hệ thống quản lý NVBPlay. Hôm nay là ngày <?php echo date('d/m/Y'); ?>.</p>
                <p>Bạn đang đăng nhập với vai trò: <strong><?php echo $admin_role === 'admin' ? 'Quản trị viên' : 'Nhân viên'; ?></strong></p>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-title">Tổng người dùng</span>
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="stat-value">1,234</div>
                        <div class="stat-change">
                            <i class="fas fa-arrow-up"></i> +12%
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-title">Tổng sản phẩm</span>
                            <div class="stat-icon">
                                <i class="fas fa-box"></i>
                            </div>
                        </div>
                        <div class="stat-value">567</div>
                        <div class="stat-change">
                            <i class="fas fa-arrow-up"></i> +5%
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-title">Đơn hàng hôm nay</span>
                            <div class="stat-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                        <div class="stat-value">89</div>
                        <div class="stat-change">
                            <i class="fas fa-arrow-up"></i> +23%
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-title">Doanh thu tháng</span>
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                        <div class="stat-value">123.4M</div>
                        <div class="stat-change">
                            <i class="fas fa-arrow-up"></i> +18%
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle"></h3>
                <button onclick="closeModal()" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="modalBody"></div>
            <div class="modal-footer" id="modalFooter"></div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="script.js"></script>
</body>
</html>