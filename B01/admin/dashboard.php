<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- HEADER -->
    <header class="header">
        <h1 class="header-title">Admin Panel - Xin chào quản trị viên</h1>
        <div class="header-right">
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <p class="user-name" id="adminName">Quản Lý 1</p>
                    <p class="user-role" id="adminRole">Quản trị viên</p>
                </div>
            </div>
            <button onclick="logout()" class="logout-btn">
                Đăng xuất
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
                <!-- Dashboard -->
                <a href="dashboard.php" class="menu-btn">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>

                <!-- Quản lý người dùng -->
                <a href="users.php" class="menu-btn">
                    <i class="fas fa-users"></i>
                    Quản lý người dùng
                </a>

                <!-- Quản lý danh mục -->
                <a href="categories.php" class="menu-btn">
                    <i class="fas fa-tags"></i>
                    Quản lý danh mục
                </a>

                <!-- Quản lý sản phẩm -->
                <a href="products.php" class="menu-btn">
                    <i class="fas fa-box"></i>
                    Quản lý sản phẩm
                </a>

                <!-- Quản lý nhập hàng -->
                <a href="import.php" class="menu-btn">
                    <i class="fas fa-arrow-down"></i>
                    Quản lý nhập hàng
                </a>

                <!-- Quản lý giá bán -->
                <a href="price.php" class="menu-btn">
                    <i class="fas fa-tag"></i>
                    Quản lý giá bán
                </a>

                <!-- Quản lý đơn hàng -->
                <a href="orders.php" class="menu-btn">
                    <i class="fas fa-receipt"></i>
                    Quản lý đơn hàng
                </a>

                <!-- Tồn kho & Báo cáo -->
                <a href="inventory.php" class="menu-btn">
                    <i class="fas fa-warehouse"></i>
                    Tồn kho & Báo cáo
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content" id="mainContent">
            <!-- Nội dung sẽ được load từ file PHP riêng -->
            <?php
            // Xác định trang hiện tại để active menu
            $current_page = basename($_SERVER['PHP_SELF']);
            
            // Nếu đang ở index.html thì hiển thị dashboard
            if ($current_page == 'index.html' || $current_page == '') {
                include 'dashboard.php';
            }
            ?>
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
    <script src="dashboard.js"></script>
</body>
</html>