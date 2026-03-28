<?php
require_once './control/connect.php';

echo "<h2>Test Kết Nối Database</h2>";

if ($conn) {
    echo "✅ Kết nối thành công!<br>";
    echo "Database: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "<br>";
    
    // Test số sản phẩm phụ kiện
    $result = $conn->query("SELECT COUNT(*) as total FROM sanpham WHERE Danhmuc_id = 5 AND TrangThai = 1");
    $row = $result->fetch_assoc();
    echo "✅ Số phụ kiện: " . $row['total'] . "<br>";
} else {
    echo "❌ Kết nối thất bại!<br>";
    echo "Error: " . mysqli_connect_error() . "<br>";
}
?>