<?php
session_start();
require_once __DIR__ . '/../control/connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php'); exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Quản trị viên';
$admin_username = $_SESSION['admin_username'] ?? '';

// Lấy danh sách phiếu nhập
$sql = "SELECT pn.*, 
        (SELECT COUNT(*) FROM chitietphieunhap WHERE PhieuNhap_id = pn.NhapHang_id) as so_mat_hang,
        COALESCE((SELECT SUM(SoLuong * Gia_Nhap) FROM chitietphieunhap WHERE PhieuNhap_id = pn.NhapHang_id), 0) as tong_gia_tri
        FROM phieunhap pn
        ORDER BY pn.NgayNhap DESC, pn.NhapHang_id DESC";

$result = $conn->query($sql);

if (!$result) {
    error_log("IMPORT_LIST_QUERY_ERROR: " . $conn->error);
    $error_message = "Lỗi truy vấn: " . htmlspecialchars($conn->error);
}
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
    <style>
        /* CSS hỗ trợ hiệu ứng trượt mượt mà cho nội dung chi tiết */
        .detail-content {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows 0.3s ease-out;
        }
        .detail-content.open {
            grid-template-rows: 1fr;
        }
        .detail-inner {
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-800">

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
        <aside class="w-64 bg-white shadow-lg hidden lg:block flex-shrink-0 border-r border-gray-100">
            <div class="p-6 border-b border-gray-100"><h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Danh mục chức năng</h3></div>
            <nav class="p-4 space-y-2">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i class="fas fa-home w-5"></i> Dashboard</a>
                <a href="users.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i class="fas fa-users w-5"></i> Quản lý người dùng</a>
                <a href="product.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i class="fas fa-box w-5"></i> Quản lý sản phẩm</a>
                <a href="import.php" class="flex items-center gap-3 px-4 py-3 bg-gradient-custom text-white rounded-lg shadow-md"><i class="fas fa-arrow-down w-5"></i> Quản lý nhập hàng</a><a href="price.php" class="menu-btn flex items-center space-x-3 px-4 py-3 rounded-lg mb-1 transition duration-200 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-tag w-5 text-gray-500"></i>
                    <span>Quản lý giá bán</span>
                </a>
                <a href="orders.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i class="fas fa-receipt w-5"></i> Quản lý đơn hàng</a>
                <a href="inventory.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i class="fas fa-warehouse w-5"></i> Tồn kho & Báo cáo</a>
            </nav>
        </aside>

        <main class="flex-1 p-6 lg:p-8 overflow-x-hidden bg-gray-50">
            <div class="bg-white rounded-xl shadow-lg p-6 lg:p-8 min-h-full">
                
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 pb-6 border-b-2 border-gray-100 gap-4">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3"><i class="fas fa-arrow-down text-primary"></i> Quản lý nhập hàng</h2>
                    <a href="add_import.php" class="bg-gradient-custom hover:opacity-90 text-white px-6 py-2.5 rounded-lg font-medium shadow-lg transition flex items-center gap-2">
                        <i class="fas fa-plus"></i> Tạo phiếu nhập mới
                    </a>
                </div>

                <?php if (isset($error_message)): ?>
                <div class="mb-6 p-4 rounded-lg bg-red-50 text-red-700 border border-red-200 flex items-center gap-3">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
                <?php endif; ?>

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gradient-custom text-white">
                                <th class="p-4 font-medium">Mã phiếu</th>
                                <th class="p-4 font-medium">Người nhập</th>
                                <th class="p-4 font-medium">Ngày nhập</th>
                                <th class="p-4 font-medium">Số mặt hàng</th>
                                <th class="p-4 font-medium">Tổng giá trị</th>
                                <th class="p-4 font-medium text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (isset($result) && $result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): 
                                    $phieu_id = $row['NhapHang_id'];
                                    $ngayNhap = $row['NgayNhap'] ? date('d/m/Y', strtotime($row['NgayNhap'])) : 'N/A';
                                    $tongGiaTri = floatval($row['tong_gia_tri'] ?? 0);
                                ?>
                                <tr class="hover:bg-blue-50/30 transition cursor-pointer" onclick="toggleDetails(<?php echo $phieu_id; ?>)">
                                    <td class="p-4 font-medium text-primary">#PN<?php echo str_pad($phieu_id, 6, '0', STR_PAD_LEFT); ?></td>
                                    <td class="p-4 text-gray-700"><?php echo htmlspecialchars($row['NguoiNhap'] ?? 'N/A'); ?></td>
                                    <td class="p-4 text-gray-700"><?php echo $ngayNhap; ?></td>
                                    <td class="p-4"><span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700"><?php echo intval($row['so_mat_hang']); ?> sản phẩm</span></td>
                                    <td class="p-4 font-bold text-gray-800"><?php echo number_format($tongGiaTri, 0, ',', '.'); ?>đ</td>
                                    <td class="p-4 text-center">
                                        <button type="button" class="text-blue-500 hover:text-blue-800 mx-1 transition-transform duration-200" id="icon-btn-<?php echo $phieu_id; ?>" title="Xem chi tiết">
                                            <i class="fas fa-eye text-lg" id="icon-<?php echo $phieu_id; ?>"></i>
                                        </button>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="6" class="p-0 border-none">
                                        <div id="detail-<?php echo $phieu_id; ?>" class="detail-content bg-gray-50">
                                            <div class="detail-inner">
                                                <div class="p-6 border-b border-gray-200 shadow-inner">
                                                    <h4 class="font-bold text-gray-700 mb-4 border-b pb-2"><i class="fas fa-box-open mr-2"></i>Chi tiết phiếu nhập #PN<?php echo str_pad($phieu_id, 6, '0', STR_PAD_LEFT); ?></h4>
                                                    
                                                    <table class="w-full text-sm text-left border rounded-lg overflow-hidden">
                                                        <thead class="bg-gray-200 text-gray-600">
                                                            <tr>
                                                                <th class="p-3">Sản phẩm</th>
                                                                <th class="p-3">Số lượng</th>
                                                                <th class="p-3">Giá nhập</th>
                                                                <th class="p-3">Thành tiền</th>
                                                                <th class="p-3">Mã lô hàng</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-200 bg-white">
                                                            <?php 
                                                            // Truy vấn chi tiết cho phiếu nhập hiện tại
                                                            $detail_sql = "SELECT ct.*, sp.TenSP 
                                                                           FROM chitietphieunhap ct 
                                                                           LEFT JOIN sanpham sp ON ct.SanPham_id = sp.SanPham_id 
                                                                           WHERE ct.PhieuNhap_id = $phieu_id";
                                                            $detail_result = $conn->query($detail_sql);
                                                            if($detail_result && $detail_result->num_rows > 0):
                                                                while($dt = $detail_result->fetch_assoc()):
                                                                    $thanhTien = $dt['SoLuong'] * $dt['Gia_Nhap'];
                                                            ?>
                                                            <tr class="hover:bg-gray-50">
                                                                <td class="p-3 font-medium text-gray-800"><?php echo htmlspecialchars($dt['TenSP'] ?? 'Sản phẩm đã xóa'); ?></td>
                                                                <td class="p-3"><?php echo $dt['SoLuong']; ?></td>
                                                                <td class="p-3"><?php echo number_format($dt['Gia_Nhap'], 0, ',', '.'); ?>đ</td>
                                                                <td class="p-3 font-semibold text-primary"><?php echo number_format($thanhTien, 0, ',', '.'); ?>đ</td>
                                                                <td class="p-3 text-xs font-mono text-gray-500"><?php echo htmlspecialchars($dt['MaLoHang'] ?? '-'); ?></td>
                                                            </tr>
                                                            <?php 
                                                                endwhile; 
                                                            else: 
                                                            ?>
                                                            <tr><td colspan="5" class="p-3 text-center text-gray-500">Không có dữ liệu chi tiết.</td></tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php elseif (!isset($error_message)): ?>
                                <tr><td colspan="6" class="text-center py-12 text-gray-500"><i class="fas fa-inbox text-4xl mb-3"></i><p>Chưa có phiếu nhập nào.</p></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        function logout() { 
            if (confirm('Bạn có chắc muốn đăng xuất?')) window.location.href = 'logout.php'; 
        }

        // Script để trượt mở chi tiết phiếu nhập
        function toggleDetails(phieuId) {
            const detailWrapper = document.getElementById('detail-' + phieuId);
            const icon = document.getElementById('icon-' + phieuId);
            const btn = document.getElementById('icon-btn-' + phieuId);
            
            // Toggle class open để kích hoạt animation CSS Grid
            detailWrapper.classList.toggle('open');
            
            // Đổi icon con mắt
            if (detailWrapper.classList.contains('open')) {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                btn.classList.add('text-red-500'); // Đổi màu để nhấn mạnh đang mở
            } else {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                btn.classList.remove('text-red-500');
            }
        }
    </script>
</body>
</html>