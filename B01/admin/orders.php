<?php
session_start();
require_once __DIR__ . '/../control/connect.php';
require_once __DIR__ . '/../control/function.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

$admin_username = $_SESSION['admin_username'] ?? '';
$admin_id = $_SESSION['admin_id'] ?? 1;

// ============================================
// XỬ LÝ CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG + TẠO PHIẾU XUẤT
// ============================================
if (isset($_GET['action']) && isset($_GET['id']) && isset($_GET['status'])) {
    $order_id = intval($_GET['id']);
    $new_status = intval($_GET['status']);

    $sql = "SELECT TrangThai FROM donhang WHERE DonHang_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    $current_status = intval($current['TrangThai']);

    $status_names = [0 => 'Chờ xử lý', 1 => 'Đã xác nhận', 2 => 'Đã giao', 3 => 'Đã hủy'];

    if ($current_status == 2 || $current_status == 3) {
        $error = "Đơn hàng đã hoàn tất, không thể thay đổi trạng thái!";
    } else {
        $valid_transitions = [0 => [1, 3], 1 => [2, 3]];

        if (in_array($new_status, $valid_transitions[$current_status])) {
            $conn->begin_transaction();

            try {
                // Cập nhật trạng thái đơn hàng
                $update = $conn->prepare("UPDATE donhang SET TrangThai = ? WHERE DonHang_id = ?");
                $update->bind_param("ii", $new_status, $order_id);
                $update->execute();

                // NẾU XÁC NHẬN ĐƠN HÀNG (Chuyển từ 0 -> 1) -> TẠO PHIẾU XUẤT
                if ($current_status == 0 && $new_status == 1) {

                    // 1. Tạo phiếu xuất
                    $insert_px = $conn->prepare("INSERT INTO phieuxuat (DonHang_id, NgayXuat, NguoiXuat_id) VALUES (?, NOW(), ?)");
                    $insert_px->bind_param("ii", $order_id, $admin_id);
                    $insert_px->execute();
                    $phieu_xuat_id = $conn->insert_id;

                    // 2. Lấy chi tiết đơn hàng
                    $detail_sql = "SELECT SanPham_id, SoLuong, Gia FROM chitiethoadon WHERE DonHang_id = ?";
                    $detail_stmt = $conn->prepare($detail_sql);
                    $detail_stmt->bind_param("i", $order_id);
                    $detail_stmt->execute();
                    $items = $detail_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                    // 3. Thêm chi tiết phiếu xuất và cập nhật tồn kho
                    foreach ($items as $item) {
                        // Lấy giá nhập hiện tại của sản phẩm
                        $sp_sql = "SELECT GiaNhapTB, SoLuongTon FROM sanpham WHERE SanPham_id = ?";
                        $sp_stmt = $conn->prepare($sp_sql);
                        $sp_stmt->bind_param("i", $item['SanPham_id']);
                        $sp_stmt->execute();
                        $sp = $sp_stmt->get_result()->fetch_assoc();

                        // Thêm chi tiết phiếu xuất
                        $insert_ct = $conn->prepare("INSERT INTO chitietphieuxuat (PhieuXuat_id, SP_id, SoLuong, GiaNhap) VALUES (?, ?, ?, ?)");
                        $insert_ct->bind_param("iiid", $phieu_xuat_id, $item['SanPham_id'], $item['SoLuong'], $sp['GiaNhapTB']);
                        $insert_ct->execute();

                        // Cập nhật tồn kho (giảm)
                        $ton_moi = $sp['SoLuongTon'] - $item['SoLuong'];
                        $update_ton = $conn->prepare("UPDATE sanpham SET SoLuongTon = ? WHERE SanPham_id = ?");
                        $update_ton->bind_param("ii", $ton_moi, $item['SanPham_id']);
                        $update_ton->execute();

                        // Ghi log tra cứu tồn kho
                        $log_sql = "INSERT INTO tracuutonkho (SP_id, TrangThai_NhapXuat, SoLuong, MaThamChieu_NhapXuat) 
                                    VALUES (?, 'Xuất', ?, ?)";
                        $log_stmt = $conn->prepare($log_sql);
                        $log_stmt->bind_param("iii", $item['SanPham_id'], $item['SoLuong'], $order_id);
                        $log_stmt->execute();
                    }

                    $message = "Đã xác nhận đơn hàng, tạo phiếu xuất và cập nhật tồn kho thành công!";
                }
                // NẾU ĐÃ GIAO HÀNG (Chuyển từ 1 -> 2)
                elseif ($current_status == 1 && $new_status == 2) {
                    $update_px = $conn->prepare("UPDATE phieuxuat SET TrangThai = 'DaGiao' WHERE DonHang_id = ?");
                    $update_px->bind_param("i", $order_id);
                    $update_px->execute();
                    $message = "Đã cập nhật đơn hàng thành Đã giao!";
                }
                // NẾU HỦY ĐƠN HÀNG (Chuyển từ 0 hoặc 1 sang 3) -> HOÀN TỒN KHO
                elseif ($new_status == 3) {
                    $check_px = $conn->prepare("SELECT PhieuXuat_id FROM phieuxuat WHERE DonHang_id = ?");
                    $check_px->bind_param("i", $order_id);
                    $check_px->execute();
                    $px_result = $check_px->get_result();

                    if ($px_result->num_rows > 0) {
                        $px = $px_result->fetch_assoc();
                        $phieu_xuat_id = $px['PhieuXuat_id'];

                        $ct_sql = "SELECT SP_id, SoLuong FROM chitietphieuxuat WHERE PhieuXuat_id = ?";
                        $ct_stmt = $conn->prepare($ct_sql);
                        $ct_stmt->bind_param("i", $phieu_xuat_id);
                        $ct_stmt->execute();
                        $items = $ct_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                        foreach ($items as $item) {
                            $update_ton = $conn->prepare("UPDATE sanpham SET SoLuongTon = SoLuongTon + ? WHERE SanPham_id = ?");
                            $update_ton->bind_param("ii", $item['SoLuong'], $item['SP_id']);
                            $update_ton->execute();

                            $log_sql = "INSERT INTO tracuutonkho (SP_id, TrangThai_NhapXuat, SoLuong, MaThamChieu_NhapXuat) 
                                        VALUES (?, 'Hủy đơn', ?, ?)";
                            $log_stmt = $conn->prepare($log_sql);
                            $log_stmt->bind_param("iii", $item['SP_id'], $item['SoLuong'], $order_id);
                            $log_stmt->execute();
                        }

                        $conn->prepare("DELETE FROM chitietphieuxuat WHERE PhieuXuat_id = ?")->bind_param("i", $phieu_xuat_id)->execute();
                        $conn->prepare("DELETE FROM phieuxuat WHERE PhieuXuat_id = ?")->bind_param("i", $phieu_xuat_id)->execute();
                    }
                    $message = "Đã hủy đơn hàng và hoàn lại tồn kho!";
                }

                $conn->commit();

            } catch (Exception $e) {
                $conn->rollback();
                $error = "Lỗi: " . $e->getMessage();
            }
        } else {
            $error = "Không thể chuyển trạng thái này!";
        }
    }

    header('Location: orders.php');
    exit();
}

// Lấy danh sách đơn hàng
$sql = "SELECT d.*, u.Ho_ten as customer_name, u.SDT as customer_phone,
        dc.Ten_nguoi_nhan, dc.SDT_nhan, dc.Quan, dc.Tinh_thanhpho, dc.Duong, dc.Dia_chi_chitiet
        FROM donhang d
        LEFT JOIN users u ON d.User_id = u.User_id
        LEFT JOIN diachigh dc ON d.DiaChi_id = dc.add_id
        ORDER BY d.NgayDat DESC";
$result = $conn->query($sql);
$orders = $result->fetch_all(MYSQLI_ASSOC);

if (isset($_GET['get_detail']) && isset($_GET['id'])) {
    $order_id = intval($_GET['id']);
    $sql_detail = "SELECT c.*, s.TenSP, s.image_url FROM chitiethoadon c
                   LEFT JOIN sanpham s ON c.SanPham_id = s.SanPham_id
                   WHERE c.DonHang_id = ?";
    $stmt = $conn->prepare($sql_detail);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($details);
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng - Admin</title>
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
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeIn {
            animation: fadeIn 0.3s ease-out;
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal.show {
            display: flex;
        }

        .track-modal-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 900px;
            max-height: 85vh;
            overflow-y: auto;
            animation: fadeIn 0.3s ease-out;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-0 {
            background: #fef3c7;
            color: #92400e;
        }

        .status-1 {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-2 {
            background: #d1fae5;
            color: #065f46;
        }

        .status-3 {
            background: #fee2e2;
            color: #991b1b;
        }

        .action-btn {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
        }

        .btn-confirm {
            background: #3b82f6;
            color: white;
        }

        .btn-deliver {
            background: #10b981;
            color: white;
        }

        .btn-cancel {
            background: #ef4444;
            color: white;
        }

        .btn-disabled {
            background: #9ca3af;
            color: white;
            cursor: not-allowed;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans text-gray-800">

    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="flex justify-between items-center px-6 py-4">
            <h1 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-custom">NVBPlay Admin Panel</h1>
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-3 bg-gray-100 px-4 py-2 rounded-lg">
                    <div
                        class="w-10 h-10 rounded-full bg-gradient-custom flex items-center justify-center text-white font-bold">
                        <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($admin_username); ?></p>
                    </div>
                </div>
                <button onclick="logout()"
                    class="bg-gradient-custom text-white font-semibold px-4 py-2 rounded-lg hover:opacity-90 transition shadow-md">
                    <i class="fas fa-sign-out-alt mr-2"></i>Đăng xuất
                </button>
            </div>
        </div>
    </header>

    <div class="flex w-full min-h-[calc(100vh-70px)]">
        <aside class="w-64 bg-white shadow-lg hidden lg:block flex-shrink-0 border-r border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Danh mục chức năng</h3>
            </div>
            <nav class="p-4 space-y-2">
                <a href="dashboard.php"
                    class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i
                        class="fas fa-home w-5"></i> Dashboard</a>
                <a href="users.php"
                    class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i
                        class="fas fa-users w-5"></i> Quản lý người dùng</a>
                <a href="categories.php"
                    class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-list w-5 text-center"></i> Quản lý danh mục
                </a>
                <a href="product.php"
                    class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i
                        class="fas fa-box w-5"></i> Quản lý sản phẩm</a>
                <a href="import.php"
                    class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i
                        class="fas fa-arrow-down w-5"></i> Quản lý nhập hàng</a>
                <a href="price.php"
                    class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i
                        class="fas fa-tag w-5"></i> Quản lý giá bán</a>
                <a href="orders.php"
                    class="flex items-center gap-3 px-4 py-3 bg-gradient-custom text-white rounded-lg shadow-md"><i
                        class="fas fa-receipt w-5"></i> Quản lý đơn hàng</a>
                <a href="inventory.php"
                    class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i
                        class="fas fa-warehouse w-5"></i> Tồn kho & Báo cáo</a>
            </nav>
        </aside>

        <main class="flex-1 p-6 lg:p-8 overflow-x-hidden bg-gray-50">
            <div class="bg-white rounded-xl shadow-lg p-6 lg:p-8 min-h-full">
                <div class="flex justify-between items-center mb-6 pb-4 border-b">
                    <h2 class="text-2xl font-bold text-gray-800"><i class="fas fa-receipt text-primary mr-2"></i>Quản lý
                        đơn hàng</h2>
                </div>

                <?php if (isset($message)): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded"><i
                            class="fas fa-check-circle mr-2"></i><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded"><i
                            class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Bộ lọc -->
                <div class="flex flex-wrap gap-4 mb-6 items-end">
                    <input type="text" id="searchOrder" placeholder="🔍 Tìm theo ID, tên khách, SĐT..."
                        class="px-4 py-2 border border-gray-300 rounded-lg w-full sm:w-auto flex-grow min-w-[200px]">

                    <div class="flex gap-2 flex-grow min-w-[100px]">
                        <input type="date" id="fromDate" class="px-2 py-2 border border-gray-300 rounded-lg flex-1">
                        <span class="self-center">–</span>
                        <input type="date" id="toDate" class="px-2 py-2 border border-gray-300 rounded-lg flex-1">
                    </div>

                    <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg w-full sm:w-auto">
                        <option value="">Tất cả trạng thái</option>
                        <option value="0">Chờ xử lý</option>
                        <option value="1">Đã xác nhận</option>
                        <option value="2">Đã giao</option>
                        <option value="3">Đã hủy</option>
                    </select>

                    <select id="sortWard" class="px-4 py-2 border border-gray-300 rounded-lg w-full sm:w-auto">
                        <option value="">Sắp xếp theo phường</option>
                        <option value="asc">A → Z</option>
                        <option value="desc">Z → A</option>
                    </select>
                </div>

                <div class="overflow-x-auto border border-gray-200 rounded-xl">
                    <table class="w-full min-w-[1000px]" id="ordersTable">
                        <thead class="bg-gradient-custom text-white">
                            <tr>
                                <th class="px-4 py-3 text-left">ID</th>
                                <th class="px-4 py-3 text-left">Mã đơn</th>
                                <th class="px-4 py-3 text-left">Khách hàng</th>
                                <th class="px-4 py-3 text-left">SĐT</th>
                                <th class="px-4 py-3 text-left">Phường</th>
                                <th class="px-4 py-3 text-right">Tổng tiền</th>
                                <th class="px-4 py-3 text-left">Ngày đặt</th>
                                <th class="px-4 py-3 text-left">Trạng thái</th>
                                <th class="px-4 py-3 text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="ordersTableBody">
                            <?php foreach ($orders as $order):
                                $status = intval($order['TrangThai']);
                                $isLocked = ($status == 2 || $status == 3);
                                $order_code = "ORD" . str_pad($order['DonHang_id'], 5, '0', STR_PAD_LEFT); ?>
                                <tr class="hover:bg-gray-50 transition"
                                    data-ward="<?php echo htmlspecialchars($order['Quan'] ?? ''); ?>">
                                    <td class="px-4 py-3"><?php echo $order['DonHang_id']; ?></td>
                                    <td class="px-4 py-3 font-mono">#<?php echo $order_code; ?></td>
                                    <td class="px-4 py-3">
                                        <?php echo htmlspecialchars($order['customer_name'] ?? $order['Ten_nguoi_nhan'] ?? 'Khách lẻ'); ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php echo htmlspecialchars($order['customer_phone'] ?? $order['SDT_nhan'] ?? ''); ?>
                                    </td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($order['Quan'] ?? 'Chưa có'); ?></td>
                                    <td class="px-4 py-3 text-right font-semibold text-indigo-600">
                                        <?php echo number_format($order['TongTien'], 0, ',', '.'); ?>đ
                                    </td>
                                    <td class="px-4 py-3"><?php echo date('d/m/Y H:i', strtotime($order['NgayDat'])); ?>
                                    </td>
                                    <td class="px-4 py-3"><span
                                            class="status-badge status-<?php echo $status; ?>"><?php echo $status == 0 ? '⏳ Chờ xử lý' : ($status == 1 ? '✓ Đã xác nhận' : ($status == 2 ? '✅ Đã giao' : '✗ Đã hủy')); ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button onclick="viewOrderDetail(<?php echo $order['DonHang_id']; ?>)"
                                                class="text-blue-500 hover:text-blue-700 p-1" title="Xem chi tiết"><i
                                                    class="fas fa-eye"></i></button>
                                            <?php if ($isLocked): ?>
                                                <span class="action-btn btn-disabled"><i class="fas fa-lock"></i> Đã khóa</span>
                                            <?php elseif ($status == 0): ?>



                                                <a href="?action=update&id=<?php echo $order['DonHang_id']; ?>&status=1"
                                                    class="action-btn btn-confirm" onclick="return confirm('Xác nhận đơn hàng?
                                                Hành động này sẽ tạo phiếu xuất và trừ tồn kho!')"><i
                                                        class="fas fa-check"></i> Xác nhận</a>
                                                <a href="?action=update&id=<?php echo $order['DonHang_id']; ?>&status=3"
                                                    class="action-btn btn-cancel" onclick="return confirm('Hủy đơn hàng?')"><i
                                                        class="fas fa-times"></i> Hủy</a>
                                            <?php elseif ($status == 1): ?>



                                                <a href="?action=update&id=<?php echo $order['DonHang_id']; ?>&status=2"
                                                    class="action-btn btn-deliver" onclick="return confirm('Xác nhận đã giao h
à                                               ng?')"><i class="fas fa-check-double"></i> Đã giao</a>
                                                <a href="?action=update&id=<?php echo $order['DonHang_id']; ?>&status=3"
                                                    class="action-btn btn-cancel" onclick="return confirm('Hủy đơn hàng?')"><i
                                                        class="fas fa-times"></i> Hủy</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>















    <div id="trackModal" class="modal">
        <div class="track-modal-content">
            <div class="bg-gradient-custom text-white px-6 py-4 rounded-t-xl flex justify-between">
                <h3 class="text-xl font-semibold"><i class="fas fa-truck mr-2"></i>Chi tiết đơn hàng</h3><button
                    onclick="closeModal()" class="text-white text-2xl">&times;</button>
            </div>
            <div class="p-6" id="trackModalContent">
                <div class="text-center py-12"><i class="fas fa-spinner fa-spin text-3xl"></i>
                    <p>Đang tải...</p>
                </div>
            </div>
            <div class="px-6 py-4 border-t flex justify-end"><button onclick="closeModal()"
                    class="px-4 py-2 bg-gray-500 text-white rounded-lg">Đóng</button></div>
        </div>
    </div>

    <script>
        function viewOrderDetail(orderId) {
            const modal = document.getElementById('trackModal');
            const content = document.getElementById('trackModalContent');
            modal.classList.add('show');
            content.innerHTML = '<div class="text-center py-12"><i class="fas fa-spinner fa-spin text-3xl"></i><p>Đang tải...</p></div>';

            fetch(`?get_detail=1&id=${orderId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.length > 0) {
                        let html = '<h4 class="font-semibold mb-3">Sản phẩm đã đặt</h4><table class="w-full text-sm"><thead class="bg-gray-100"><tr><th class="p-2">Sản phẩm</th><th class="p-2 text-right">SL</th><th class="p-2 text-right">Đơn giá</th><th class="p-2 text-right">Thành tiền</th></tr></thead><tbody>';
                        let total = 0;
                        data.forEach(item => {
                            let sub = item.SoLuong * item.Gia;
                            total += sub;
                            html += `<tr>
                            <td class="p-2"><div class="flex gap-2">
                                ${item.image_url ? `<img src="../${item.image_url}" class="w-10 h-10 object-cover rounded">` : '<div class="w-10 h-10 bg-gray-100 rounded flex items-center justify-center"><i class="fas fa-image"></i></div>'}
                                <span>${item.TenSP}</span>
                            </div></td>
                            <td class="p-2 text-right">${item.SoLuong}</td>
                            <td class="p-2 text-right">${new Intl.NumberFormat('vi-VN').format(item.Gia)}đ</td>
                            <td class="p-2 text-right">${new Intl.NumberFormat('vi-VN').format(sub)}đ</td>
                        </tr>`;
                        });
                        html += `<tr class="bg-gray-50 font-semibold">
                        <td colspan="3" class="p-2 text-right">Tổng cộng:</td>
                        <td class="p-2 text-right text-indigo-600">${new Intl.NumberFormat('vi-VN').format(total)}đ</td>
                    </tr></tbody></table>`;
                        content.innerHTML = html;
                    } else {
                        content.innerHTML = '<div class="text-center py-12 text-gray-500">Không có chi tiết</div>';
                    }
                })
                .catch(() => {
                    content.innerHTML = '<div class="text-center py-12 text-red-500">Lỗi tải dữ liệu</div>';
                });
        }

        function closeModal() {
            document.getElementById('trackModal').classList.remove('show');
        }

        function filterByDate() {
            let f = document.getElementById('fromDate').value;
            let t = document.getElementById('toDate').value;
            document.querySelectorAll('#ordersTableBody tr').forEach(r => {
                let d = r.cells[6]?.textContent.split(' ')[0];
                if (!d) return;
                let [day, month, year] = d.split('/');
                let rd = new Date(`${year}-${month}-${day}`);
                let show = true;
                if (f && rd < new Date(f)) show = false;
                if (t && rd > new Date(t)) show = false;
                r.style.display = show ? '' : 'none';
            });
        }

        function filterByStatus() {
            let s = document.getElementById('statusFilter').value;
            document.querySelectorAll('#ordersTableBody tr').forEach(r => {
                let sp = r.cells[7]?.querySelector('span');
                let val = '';
                if (sp?.textContent.includes('Chờ xử lý')) val = '0';
                else if (sp?.textContent.includes('Đã xác nhận')) val = '1';
                else if (sp?.textContent.includes('Đã giao')) val = '2';
                else if (sp?.textContent.includes('Đã hủy')) val = '3';
                r.style.display = (s === '' || val === s) ? '' : 'none';
            });
        }

        function sortByWard() {
            let o = document.getElementById('sortWard').value;
            if (!o) return;
            let tbody = document.getElementById('ordersTableBody');
            let rows = Array.from(tbody.querySelectorAll('tr'));
            rows.sort((a, b) => {
                let wa = a.getAttribute('data-ward') || '';
                let wb = b.getAttribute('data-ward') || '';
                return o === 'asc' ? wa.localeCompare(wb, 'vi') : wb.localeCompare(wa, 'vi');
            });
            rows.forEach(r => tbody.appendChild(r));
        }

        // Search
        document.getElementById('searchOrder').addEventListener('keyup', function () {
            let v = this.value.toLowerCase();
            document.querySelectorAll('#ordersTableBody tr').forEach(r => {
                let id = r.cells[0]?.textContent.toLowerCase() || '';
                let name = r.cells[2]?.textContent.toLowerCase() || '';
                let phone = r.cells[3]?.textContent.toLowerCase() || '';
                r.style.display = (id.includes(v) || name.includes(v) || phone.includes(v)) ? '' : 'none';
            });
        });

        // Event listeners
        document.getElementById('fromDate').addEventListener('change', filterByDate);
        document.getElementById('toDate').addEventListener('change', filterByDate);
        document.getElementById('statusFilter').addEventListener('change', filterByStatus);
        document.getElementById('sortWard').addEventListener('change', sortByWard);

        function logout() {
            if (confirm('Đăng xuất?')) window.location.href = 'logout.php';
        }

        window.onclick = e => {
            if (e.target.classList.contains('modal')) closeModal();
        }
    </script>
    t>
</body>

</html>