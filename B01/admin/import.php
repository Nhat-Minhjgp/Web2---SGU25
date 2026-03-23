<?php
session_start();
require_once __DIR__ . '/../control/connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php'); exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Quản trị viên';
$admin_username = $_SESSION['admin_username'] ?? '';

// Lấy danh sách phiếu nhập
$sql = "SELECT pn.*, ncc.Ten_NCC, 
        (SELECT COUNT(*) FROM chitietphieunhap WHERE PhieuNhap_id = pn.NhapHang_id) as so_mat_hang,
        (SELECT SUM(SoLuong * Gia_Nhap) FROM chitietphieunhap WHERE PhieuNhap_id = pn.NhapHang_id) as tong_gia_tri
        FROM phieunhap pn
        LEFT JOIN nhacungcap ncc ON pn.NCC_id = ncc.NCC_id
        ORDER BY pn.NgayNhap DESC, pn.NhapHang_id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý nhập hàng - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
</head>
<body class="bg-gray-50 font-sans text-gray-800">

    <!-- HEADER (giống product.php) -->
    <header class="bg-white shadow-md sticky top-0 z-50 h-[70px] flex items-center w-full">
        <div class="w-full px-6 flex justify-between items-center">
            <h1 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-custom">NVBPlay Admin Panel</h1>
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-custom flex items-center justify-center text-white font-bold shadow-lg">
                        <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($admin_name); ?> <span class="ml-2 text-xs bg-gradient-custom text-white px-2 py-0.5 rounded-full">Admin</span></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($admin_username); ?></p>
                    </div>
                </div>
                <button onclick="logout()" class="flex items-center gap-2 text-red-500 hover:text-red-700 transition font-medium"><i class="fas fa-sign-out-alt"></i> Đăng xuất</button>
            </div>
        </div>
    </header>

    <div class="flex w-full min-h-[calc(100vh-70px)]">
        <!-- SIDEBAR -->
        <aside class="w-64 bg-white shadow-lg hidden lg:block flex-shrink-0 border-r border-gray-100">
            <div class="p-6 border-b border-gray-100"><h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Danh mục chức năng</h3></div>
            <nav class="p-4 space-y-2">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i class="fas fa-home w-5"></i> Dashboard</a>
                <a href="users.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i class="fas fa-users w-5"></i> Quản lý người dùng</a>
                <a href="product.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i class="fas fa-box w-5"></i> Quản lý sản phẩm</a>
                <a href="import.php" class="flex items-center gap-3 px-4 py-3 bg-gradient-custom text-white rounded-lg shadow-md"><i class="fas fa-arrow-down w-5"></i> Quản lý nhập hàng</a>
                <a href="price.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i class="fas fa-tag w-5"></i> Quản lý giá bán</a>
                <a href="orders.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i class="fas fa-receipt w-5"></i> Quản lý đơn hàng</a>
                <a href="inventory.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i class="fas fa-warehouse w-5"></i> Tồn kho & Báo cáo</a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6 lg:p-8 overflow-x-hidden bg-gray-50">
            <div class="bg-white rounded-xl shadow-lg p-6 lg:p-8 min-h-full">
                
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 pb-6 border-b-2 border-gray-100 gap-4">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3"><i class="fas fa-arrow-down text-primary"></i> Quản lý nhập hàng</h2>
                    <a href="add_import.php" class="bg-gradient-custom hover:opacity-90 text-white px-6 py-2.5 rounded-lg font-medium shadow-lg transition flex items-center gap-2">
                        <i class="fas fa-plus"></i> Tạo phiếu nhập mới
                    </a>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gradient-custom text-white">
                                <th class="p-4 font-medium">Mã phiếu</th>
                                <th class="p-4 font-medium">Nhà cung cấp</th>
                                <th class="p-4 font-medium">Người nhập</th>
                                <th class="p-4 font-medium">Ngày nhập</th>
                                <th class="p-4 font-medium">Số mặt hàng</th>
                                <th class="p-4 font-medium">Tổng giá trị</th>
                                <th class="p-4 font-medium text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-blue-50/50 transition">
                                    <td class="p-4 font-medium">#PN<?php echo str_pad($row['NhapHang_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td class="p-4 text-gray-700"><?php echo htmlspecialchars($row['Ten_NCC'] ?? 'N/A'); ?></td>
                                    <td class="p-4 text-gray-700"><?php echo htmlspecialchars($row['NguoiNhap'] ?? 'N/A'); ?></td>
                                    <td class="p-4 text-gray-700"><?php echo date('d/m/Y', strtotime($row['NgayNhap'])); ?></td>
                                    <td class="p-4"><span class="px-3 py-1 rounded-full text-xs bg-blue-50 text-blue-700"><?php echo $row['so_mat_hang']; ?> sản phẩm</span></td>
                                    <td class="p-4 font-medium text-gray-800"><?php echo number_format($row['tong_gia_tri'] ?? 0, 0, ',', '.'); ?>đ</td>
                                    <td class="p-4 text-center">
                                        <a href="view_import.php?id=<?php echo $row['NhapHang_id']; ?>" class="text-blue-500 hover:text-blue-700 mx-1" title="Xem"><i class="fas fa-eye"></i></a>
                                        <a href="edit_import.php?id=<?php echo $row['NhapHang_id']; ?>" class="text-yellow-500 hover:text-yellow-700 mx-1" title="Sửa"><i class="fas fa-edit"></i></a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center py-12 text-gray-500"><i class="fas fa-inbox text-4xl mb-3"></i><p>Chưa có phiếu nhập nào.</p></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        function logout() { if (confirm('Bạn có chắc muốn đăng xuất?')) window.location.href = 'logout.php'; }
    </script>
</body>
</html>