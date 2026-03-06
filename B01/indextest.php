<?php
// 1. Kết nối Database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "b01_nhahodau";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

// 2. Cấu hình phân trang
$limit = 5; // Số sản phẩm hiển thị trên 1 trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// 3. Lấy tổng số sản phẩm để tính số trang
$total_sql = "SELECT COUNT(*) FROM sanpham";
$total_result = mysqli_query($conn, $total_sql);
$total_rows = mysqli_fetch_array($total_result)[0];
$total_pages = ceil($total_rows / $limit);

// 4. Lấy dữ liệu sản phẩm cho trang hiện tại
$sql = "SELECT * FROM sanpham LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Test Phân Trang Sản Phẩm</title>
    <style>
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .pagination { display: flex; gap: 10px; }
        .pagination a { padding: 8px 12px; border: 1px solid #ccc; text-decoration: none; color: black; }
        .pagination a.active { background-color: #007bff; color: white; border-color: #007bff; }
    </style>
</head>
<body>

    <h2>Danh sách Sản phẩm (Trang <?php echo $page; ?>)</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên Sản Phẩm</th>
                <th>Giá Bán</th>
                <th>Số Lượng Tồn</th>
                
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['SanPham_id']; ?></td>
                <td><?php echo $row['TenSP']; ?></td>
                <td><?php echo number_format($row['GiaBan'], 0, ',', '.'); ?> đ</td>
                <td><?php echo $row['SoLuongTon']; ?></td>
                
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="pagination">
        <?php for($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>

</body>
</html>