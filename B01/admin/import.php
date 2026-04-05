<?php
session_start();
require_once __DIR__ . '/../control/connect.php';
require_once __DIR__ . '/../control/function.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Quản trị viên';
$admin_username = $_SESSION['admin_username'] ?? '';

// ============================================
// XỬ LÝ HOÀN THÀNH PHIẾU NHẬP (Cập nhật tồn kho)
// ============================================
if (isset($_GET['action']) && $_GET['action'] == 'complete' && isset($_GET['id'])) {
    $phieu_id = intval($_GET['id']);
    
    // Kiểm tra phiếu nhập có tồn tại không
    $check = $conn->prepare("SELECT TrangThai FROM phieunhap WHERE NhapHang_id = ?");
    $check->bind_param("i", $phieu_id);
    $check->execute();
    $result = $check->get_result();
    $receipt = $result->fetch_assoc();
    
    if ($receipt) {
        if ($receipt['TrangThai'] == 'completed') {
            $error = "Phiếu nhập đã được hoàn thành trước đó!";
        } else {
            // Bắt đầu transaction
            $conn->begin_transaction();
            
            try {
                // Lấy danh sách sản phẩm trong phiếu nhập
                $sql_items = "SELECT SanPham_id, SoLuong, Gia_Nhap FROM chitietphieunhap WHERE PhieuNhap_id = ?";
                $stmt = $conn->prepare($sql_items);
                $stmt->bind_param("i", $phieu_id);
                $stmt->execute();
                $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                // Cập nhật tồn kho và giá nhập trung bình cho từng sản phẩm
                foreach ($items as $item) {
                    $sanpham_id = $item['SanPham_id'];
                    $so_luong_nhap = $item['SoLuong'];
                    $gia_nhap = $item['Gia_Nhap'];
                    
                    // Lấy thông tin sản phẩm hiện tại
                    $sp = getProductById($conn, $sanpham_id);
                    $ton_hien_tai = $sp['SoLuongTon'] ?? 0;
                    $gia_nhap_tb_hien_tai = $sp['GiaNhapTB'] ?? 0;
                    
                    // Tính giá nhập trung bình mới
                    if ($ton_hien_tai > 0) {
                        $gia_nhap_moi = ($ton_hien_tai * $gia_nhap_tb_hien_tai + $so_luong_nhap * $gia_nhap) / ($ton_hien_tai + $so_luong_nhap);
                    } else {
                        $gia_nhap_moi = $gia_nhap;
                    }
                    
                    // Cập nhật tồn kho và giá nhập trung bình
                    $ton_moi = $ton_hien_tai + $so_luong_nhap;
                    $update_sp = $conn->prepare("UPDATE sanpham SET SoLuongTon = ?, GiaNhapTB = ? WHERE SanPham_id = ?");
                    $update_sp->bind_param("idi", $ton_moi, $gia_nhap_moi, $sanpham_id);
                    $update_sp->execute();
                    
                    // Cập nhật giá bán dựa trên giá nhập mới và tỷ lệ lợi nhuận
                    $phan_tram_loi_nhuan = $sp['PhanTramLoiNhuan'] ?? 0;
                    $gia_ban_moi = round($gia_nhap_moi * (1 + $phan_tram_loi_nhuan ));
                    $update_gia = $conn->prepare("UPDATE sanpham SET GiaBan = ? WHERE SanPham_id = ?");
                    $update_gia->bind_param("di", $gia_ban_moi, $sanpham_id);
                    $update_gia->execute();
                }
                
                // Cập nhật trạng thái phiếu nhập thành completed
                $update_phieu = $conn->prepare("UPDATE phieunhap SET TrangThai = 'completed' WHERE NhapHang_id = ?");
                $update_phieu->bind_param("i", $phieu_id);
                $update_phieu->execute();
                
                $conn->commit();
                $message = "Đã hoàn thành phiếu nhập và cập nhật tồn kho thành công!";
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Có lỗi xảy ra: " . $e->getMessage();
            }
        }
    } else {
        $error = "Không tìm thấy phiếu nhập!";
    }
    
    header('Location: import.php');
    exit();
}

// ============================================
// XỬ LÝ XÓA PHIẾU NHẬP (CHỈ XÓA ĐƯỢC KHI CHƯA HOÀN THÀNH)
// ============================================
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $phieu_id = intval($_GET['id']);
    
    // Kiểm tra trạng thái
    $check = $conn->prepare("SELECT TrangThai FROM phieunhap WHERE NhapHang_id = ?");
    $check->bind_param("i", $phieu_id);
    $check->execute();
    $result = $check->get_result();
    $receipt = $result->fetch_assoc();
    
    if ($receipt && $receipt['TrangThai'] == 'pending') {
        // Xóa chi tiết phiếu nhập trước
        $del_detail = $conn->prepare("DELETE FROM chitietphieunhap WHERE PhieuNhap_id = ?");
        $del_detail->bind_param("i", $phieu_id);
        $del_detail->execute();
        
        // Xóa phiếu nhập
        $del_phieu = $conn->prepare("DELETE FROM phieunhap WHERE NhapHang_id = ?");
        $del_phieu->bind_param("i", $phieu_id);
        $del_phieu->execute();
        
        $message = "Đã xóa phiếu nhập!";
    } else {
        $error = "Không thể xóa phiếu nhập đã hoàn thành!";
    }
    
    header('Location: import.php');
    exit();
}

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
        
        .badge-pending {
            background: #fef3c7;
            color: #92400e;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-completed {
            background: #d1fae5;
            color: #065f46;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .action-btn {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-view { background: #3b82f6; color: white; }
        .btn-view:hover { background: #2563eb; }
        .btn-complete { background: #10b981; color: white; }
        .btn-complete:hover { background: #059669; }
        .btn-delete { background: #ef4444; color: white; }
        .btn-delete:hover { background: #dc2626; }
        .btn-disabled { background: #9ca3af; color: white; cursor: not-allowed; }
    </style>
      <link rel="icon" type="image/svg+xml" href="../img/icons/favicon.png" sizes="32x32">
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
                <a href="categories.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
    <i class="fas fa-list w-5 text-center"></i> Quản lý danh mục
</a>
                <a href="product.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i class="fas fa-box w-5"></i> Quản lý sản phẩm</a>
                <a href="import.php" class="flex items-center gap-3 px-4 py-3 bg-gradient-custom text-white rounded-lg shadow-md"><i class="fas fa-arrow-down w-5"></i> Quản lý nhập hàng</a>
                <a href="price.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i class="fas fa-tag w-5"></i> Quản lý giá bán</a>
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

                <?php if (isset($message)): ?>
                <div class="mb-6 p-4 rounded-lg bg-green-50 text-green-700 border border-green-200 flex items-center gap-3">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                <div class="mb-6 p-4 rounded-lg bg-red-50 text-red-700 border border-red-200 flex items-center gap-3">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>

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
                                <th class="p-4 font-medium">Trạng thái</th>
                                <th class="p-4 font-medium text-center">Thao tác</th>
                             </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (isset($result) && $result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): 
                                    $phieu_id = $row['NhapHang_id'];
                                    $ngayNhap = $row['NgayNhap'] ? date('d/m/Y', strtotime($row['NgayNhap'])) : 'N/A';
                                    $tongGiaTri = floatval($row['tong_gia_tri'] ?? 0);
                                    $trang_thai = $row['TrangThai'] ?? 'pending';
                                    $is_completed = ($trang_thai == 'completed');
                                ?>
                                <tr class="hover:bg-blue-50/30 transition">
                                    <td class="p-4 font-mono text-primary font-medium">#<?php echo str_pad($phieu_id, 1, '0', STR_PAD_LEFT); ?>     </td>
                                    <td class="p-4 text-gray-700"><?php echo htmlspecialchars($row['NguoiNhap'] ?? 'N/A'); ?>     </td>
                                    <td class="p-4 text-gray-700"><?php echo $ngayNhap; ?>     </td>
                                    <td class="p-4"><span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700"><?php echo intval($row['so_mat_hang']); ?> sản phẩm</span> </td>
                                    <td class="p-4 font-bold text-gray-800"><?php echo number_format($tongGiaTri, 0, ',', '.'); ?>đ</td>
                                    <td class="p-4">
                                        <?php if ($is_completed): ?>
                                            <span class="badge-completed"><i class="fas fa-check-circle mr-1"></i> Hoàn thành</span>
                                        <?php else: ?>
                                            <span class="badge-pending"><i class="fas fa-clock mr-1"></i> Chờ duyệt</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- Nút xem chi tiết -->
                                            <button onclick="toggleDetails(<?php echo $phieu_id; ?>)" class="action-btn btn-view" id="detail-btn-<?php echo $phieu_id; ?>" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if (!$is_completed): ?>
                                                <!-- Nút hoàn thành -->
                                                <a href="?action=complete&id=<?php echo $phieu_id; ?>" class="action-btn btn-complete" onclick="return confirm('Xác nhận hoàn thành phiếu nhập? Hành động này sẽ cập nhật tồn kho và không thể hoàn tác!')">
                                                    <i class="fas fa-check"></i> Hoàn thành
                                                </a>
                                                <!-- Nút xóa -->
                                                <a href="?action=delete&id=<?php echo $phieu_id; ?>" class="action-btn btn-delete" onclick="return confirm('Xóa phiếu nhập này?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="action-btn btn-disabled" title="Đã hoàn thành, không thể sửa">
                                                    <i class="fas fa-lock"></i> Đã khóa
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                 </tr>

                                <!-- Dòng chi tiết (ẩn) -->
                                <tr class="detail-row-<?php echo $phieu_id; ?>" style="display: none;">
                                    <td colspan="7" class="p-0">
                                        <div class="bg-gray-50 p-4 border-t border-gray-200">
                                            <h4 class="font-bold text-gray-700 mb-3"><i class="fas fa-box-open mr-2"></i>Chi tiết phiếu nhập #PN<?php echo str_pad($phieu_id, 6, '0', STR_PAD_LEFT); ?></h4>
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-sm border rounded-lg">
                                                    <thead class="bg-gray-200">
                                                    
                                                            <th class="p-2 text-left">Sản phẩm</th>
                                                            <th class="p-2 text-right">Số lượng</th>
                                                            <th class="p-2 text-right">Giá nhập</th>
                                                            <th class="p-2 text-right">Thành tiền</th>
                                                            <th class="p-2 text-left">Mã lô hàng</th>
                                                         </thead>
                                                        <tbody>
                                                            <?php 
                                                            $detail_sql = "SELECT ct.*, sp.TenSP 
                                                                           FROM chitietphieunhap ct 
                                                                           LEFT JOIN sanpham sp ON ct.SanPham_id = sp.SanPham_id 
                                                                           WHERE ct.PhieuNhap_id = $phieu_id";
                                                            $detail_result = $conn->query($detail_sql);
                                                            if($detail_result && $detail_result->num_rows > 0):
                                                                while($dt = $detail_result->fetch_assoc()):
                                                                    $thanhTien = $dt['SoLuong'] * $dt['Gia_Nhap'];
                                                            ?>
                                                            <tr class="border-b">
                                                                <td class="p-2 font-medium"><?php echo htmlspecialchars($dt['TenSP'] ?? 'Sản phẩm đã xóa'); ?></td>
                                                                <td class="p-2 text-right"><?php echo $dt['SoLuong']; ?></td>
                                                                <td class="p-2 text-right"><?php echo number_format($dt['Gia_Nhap'], 0, ',', '.'); ?>đ</td>
                                                                <td class="p-2 text-right font-semibold text-primary"><?php echo number_format($thanhTien, 0, ',', '.'); ?>đ</td>
                                                                <td class="p-2 text-xs font-mono"><?php echo htmlspecialchars($dt['MaLoHang'] ?? '-'); ?></td>
                                                             </tr>
                                                            <?php 
                                                                endwhile; 
                                                            else: 
                                                            ?>
                                                            <tr><td colspan="5" class="p-2 text-center text-gray-500">Không có dữ liệu chi tiết</td></tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                     </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php elseif (!isset($error_message)): ?>
                                <tr><td colspan="7" class="text-center py-12 text-gray-500"><i class="fas fa-inbox text-4xl mb-3"></i><p>Chưa có phiếu nhập nào.</p></td></tr>
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

        // Script để hiển thị/ẩn chi tiết phiếu nhập
        function toggleDetails(phieuId) {
            const detailRow = document.querySelector(`.detail-row-${phieuId}`);
            const btn = document.getElementById(`detail-btn-${phieuId}`);
            
            if (detailRow.style.display === 'none' || detailRow.style.display === '') {
                detailRow.style.display = 'table-row';
                btn.classList.add('bg-blue-700');
                btn.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                detailRow.style.display = 'none';
                btn.classList.remove('bg-blue-700');
                btn.innerHTML = '<i class="fas fa-eye"></i>';
            }
        }
    </script>
</body>
</html>