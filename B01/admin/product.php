<?php
session_start();
require_once __DIR__ . '/../control/connect.php';


if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Quản trị viên';
$admin_role = $_SESSION['admin_role'] ?? '';

// 2. Lấy danh sách sản phẩm từ database b01_nhahodau
// JOIN với bảng danh mục và thương hiệu để hiển thị tên thay vì ID
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
    <title>Quản lý sản phẩm - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css"> <style>
        /* Bổ sung một số style riêng cho bảng sản phẩm */
        .table-container { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-top: 20px; }
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .search-box { position: relative; width: 300px; }
        .search-box input { width: 100%; padding: 10px 35px 10px 15px; border-radius: 20px; border: 1px solid #ddd; }
        .search-box i { position: absolute; right: 15px; top: 12px; color: #aaa; }
        
        .product-table { width: 100%; border-collapse: collapse; text-align: left; }
        .product-table th { padding: 15px; border-bottom: 2px solid #eee; color: #666; font-weight: 600; }
        .product-table td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; }
        .product-img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; background: #eee; }
        
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: 600; }
        .badge-stock { background: #e3f2fd; color: #1976d2; } /* Còn hàng */
        .badge-out { background: #ffebee; color: #c62828; }   /* Hết hàng */
        
        .btn-edit { color: #3498db; margin-right: 10px; cursor: pointer; border:none; background:none; font-size: 18px; }
        .btn-delete { color: #e74c3c; cursor: pointer; border:none; background:none; font-size: 18px; }
        .btn-primary { background: #3498db; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>
    <header class="header">
        <h1 class="header-title">NVBPlay Admin Panel</h1>
        <div class="header-right">
            <div class="user-info">
                <div class="user-avatar"><?php echo strtoupper(substr($admin_name, 0, 1)); ?></div>
                <div>
                    <p class="user-name"><?php echo htmlspecialchars($admin_name); ?> <span class="role-badge role-admin">Admin</span></p>
                </div>
            </div>
            <button onclick="logout()" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Đăng xuất</button>
        </div>
    </header>

    <div class="main-container">
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="menu-btn"><i class="fas fa-home"></i> Dashboard</a>
                <a href="users.php" class="menu-btn"><i class="fas fa-users"></i> Quản lý người dùng</a>
                <a href="products.php" class="menu-btn active"><i class="fas fa-box"></i> Quản lý sản phẩm</a>
                <a href="import.php" class="menu-btn"><i class="fas fa-arrow-down"></i> Quản lý nhập hàng</a>
                <a href="orders.php" class="menu-btn"><i class="fas fa-receipt"></i> Quản lý đơn hàng</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="table-container">
                <div class="action-bar">
                    <div class="search-box">
                        <input type="text" id="productSearch" placeholder="Tìm kiếm sản phẩm..." onkeyup="filterProducts()">
                        <i class="fas fa-search"></i>
                    </div>
                    <a href="add_product.php" class="btn-primary"><i class="fas fa-plus"></i> Thêm sản phẩm mới</a>
                </div>

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
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <img src="../uploads/<?php echo $row['image_url'] ? $row['image_url'] : 'no-image.png'; ?>" class="product-img">
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['TenSP']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($row['Ten_danhmuc']); ?></td>
                            <td><?php echo htmlspecialchars($row['Ten_thuonghieu']); ?></td>
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
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Hàm lọc tìm kiếm nhanh
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

        // Hàm xóa sản phẩm
        function deleteProduct(id) {
            if(confirm('Bạn có chắc chắn muốn xóa sản phẩm này? Thao tác này không thể hoàn tác.')) {
                window.location.href = 'delete_product.php?id=' + id;
            }
        }
    </script>
</body>
</html>