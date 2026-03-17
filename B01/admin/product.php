<?php
session_start();
require_once __DIR__ . '/../control/connect.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Quản trị viên';
$admin_role = $_SESSION['admin_role'] ?? '';
$admin_username = $_SESSION['admin_username'] ?? '';

// Lấy danh sách sản phẩm từ database
$sql = "SELECT sp.*, dm.Ten_danhmuc, th.Ten_thuonghieu 
        FROM sanpham sp
        LEFT JOIN danhmuc dm ON sp.Danhmuc_id = dm.Danhmuc_id
        LEFT JOIN thuonghieu th ON sp.Ma_thuonghieu = th.Ma_thuonghieu
        ORDER BY sp.SanPham_id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Container chính */
        .products-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        /* Page Header */
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

        /* Action Bar */
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
        }

        .search-box {
            position: relative;
            flex: 1;
            max-width: 400px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 35px 10px 15px;
            border-radius: 20px;
            border: 1px solid #ddd;
            font-size: 14px;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }

        .search-box i {
            position: absolute;
            right: 15px;
            top: 12px;
            color: #aaa;
        }

        .btn-primary {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        /* Table */
        .table-wrapper {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
        }

        .product-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 500;
        }

        .product-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            color: #333;
            vertical-align: middle;
        }

        .product-table tr:hover {
            background: #f8f9ff;
        }

        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            background: #eee;
        }

        /* Badges */
        .badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-stock {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge-out {
            background: #ffebee;
            color: #c62828;
        }

        /* Action Buttons */
        .action-group {
            display: flex;
            gap: 8px;
        }

        .btn-edit {
            color: #3498db;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 18px;
        }

        .btn-delete {
            color: #e74c3c;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 18px;
        }

        .btn-edit:hover, .btn-delete:hover {
            opacity: 0.8;
        }

        /* Role Badge */
        .role-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }

        .role-admin {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .role-staff {
            background: #28a745;
            color: white;
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
            max-width: 400px;
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
            text-align: center;
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
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
                <i class="fas fa-sign-out-alt"></i> Đăng xuất
            </button>
        </div>
    </header>

    <div class="main-container">
        <!-- SIDEBAR - ĐẦY ĐỦ CÁC MỤC -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>Danh mục chức năng</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="menu-btn">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="users.php" class="menu-btn">
                    <i class="fas fa-users"></i> Quản lý người dùng
                </a>
                <a href="categories.php" class="menu-btn">
                    <i class="fas fa-tags"></i> Quản lý danh mục
                </a>
                <a href="product.php" class="menu-btn active">
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
            <div class="products-container">
                <!-- Page Header -->
                <div class="page-header">
                    <h2><i class="fas fa-box"></i> Quản lý sản phẩm</h2>
                </div>

                <!-- Action Bar -->
                <div class="action-bar">
                    <div class="search-box">
                        <input type="text" id="productSearch" placeholder="Tìm kiếm sản phẩm..." onkeyup="filterProducts()">
                        <i class="fas fa-search"></i>
                    </div>
                    <a href="add_product.php" class="btn-primary">
                        <i class="fas fa-plus"></i> Thêm sản phẩm
                    </a>
                </div>

                <!-- Products Table -->
                <div class="table-wrapper">
                    <table class="product-table" id="myTable">
                        <thead>
                            <tr>
                                <th>Hình ảnh</th>
                                <th>Tên sản phẩm</th>
                                <th>Danh mục</th>
                                <th>Thương hiệu</th>
                                <th>Giá bán</th>
                                <th>Tồn kho</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <img src="../uploads/<?php echo $row['image_url'] ? $row['image_url'] : 'no-image.png'; ?>" class="product-img">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['TenSP']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['Ten_danhmuc'] ?? 'Chưa có'); ?></td>
                                    <td><?php echo htmlspecialchars($row['Ten_thuonghieu'] ?? 'Chưa có'); ?></td>
                                    <td><?php echo number_format($row['GiaBan'], 0, ',', '.'); ?>đ</td>
                                    <td>
                                        <?php if($row['SoLuongTon'] > 0): ?>
                                            <span class="badge badge-stock"><?php echo $row['SoLuongTon']; ?> sản phẩm</span>
                                        <?php else: ?>
                                            <span class="badge badge-out">Hết hàng</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn-edit" title="Sửa" onclick="window.location.href='edit_product.php?id=<?php echo $row['SanPham_id']; ?>'">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-delete" title="Xóa" onclick="deleteProduct(<?php echo $row['SanPham_id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                                        <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                        Chưa có sản phẩm nào.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL XÁC NHẬN XÓA -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Xác nhận xóa</h3>
                <button onclick="closeDeleteModal()" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa sản phẩm này?</p>
                <p style="color: #666; font-size: 14px; margin-top: 10px;">Thao tác này không thể hoàn tác!</p>
            </div>
            <div class="modal-footer">
                <button onclick="closeDeleteModal()" class="btn btn-secondary">Hủy</button>
                <button id="confirmDeleteBtn" class="btn btn-danger">Xóa</button>
            </div>
        </div>
    </div>

    <!-- JavaScript GIỐNG FILE BẠN GỬI -->
    <script>
        // Hàm lọc tìm kiếm nhanh (GIỮ NGUYÊN)
        function filterProducts() {
            let input = document.getElementById("productSearch");
            let filter = input.value.toUpperCase();
            let table = document.getElementById("myTable");
            let tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                let td = tr[i].getElementsByTagName("td")[1]; // Cột tên sản phẩm
                if (td) {
                    let txtValue = td.textContent || td.innerText;
                    tr[i].style.display = txtValue.toUpperCase().indexOf(filter) > -1 ? "" : "none";
                }
            }
        }

        // Hàm xóa sản phẩm (GIỮ NGUYÊN)
        let deleteId = null;

        function deleteProduct(id) {
            deleteId = id;
            document.getElementById('deleteModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            deleteId = null;
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (deleteId) {
                window.location.href = 'delete_product.php?id=' + deleteId;
            }
        });

        // Hàm logout (GIỮ NGUYÊN)
        function logout() {
            if (confirm('Bạn có chắc muốn đăng xuất?')) {
                window.location.href = 'logout.php';
            }
        }

        // Click outside modal
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        }
    </script>
</body>
</html>