<?php
session_start();
require_once __DIR__ . '/../control/connect.php';
require_once __DIR__ . '/../control/function.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Quản trị viên';
$admin_role = $_SESSION['admin_role'] ?? '';
$admin_username = $_SESSION['admin_username'] ?? '';

// ============================================
// XỬ LÝ CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG
// ============================================
if (isset($_GET['action']) && isset($_GET['id']) && isset($_GET['status'])) {
    $order_id = intval($_GET['id']);
    $new_status = $_GET['status'];
    
    // Lấy trạng thái hiện tại
    $sql = "SELECT TrangThai FROM donhang WHERE DonHang_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current = $result->fetch_assoc();
    $current_status = $current['TrangThai'];
    
    // Nếu đã ở trạng thái cuối (đã giao hoặc đã hủy) thì không cho sửa
    if ($current_status == 'delivered' || $current_status == 'cancelled') {
        $error = "Đơn hàng đã hoàn tất, không thể thay đổi trạng thái!";
    } else {
        // Kiểm tra chuyển trạng thái hợp lệ
        $valid_transitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['shipping', 'cancelled'],
            'shipping' => ['delivered', 'cancelled']
        ];
        
        if (in_array($new_status, $valid_transitions[$current_status])) {
            $update = $conn->prepare("UPDATE donhang SET TrangThai = ? WHERE DonHang_id = ?");
            $update->bind_param("si", $new_status, $order_id);
            if ($update->execute()) {
                $message = "Đã cập nhật trạng thái đơn hàng thành công!";
            } else {
                $error = "Có lỗi xảy ra!";
            }
        } else {
            $error = "Không thể chuyển trạng thái này!";
        }
    }
    
    // Chuyển hướng về trang orders để tránh submit lại
    header('Location: orders.php');
    exit();
}

// ============================================
// LẤY DANH SÁCH ĐƠN HÀNG
// ============================================
$sql = "SELECT d.*, u.Ho_ten as customer_name, u.SDT as customer_phone,
        dc.Ten_nguoi_nhan, dc.SDT_nhan, dc.Duong, dc.Quan, dc.Tinh_thanhpho, dc.Dia_chi_chitiet
        FROM donhang d
        LEFT JOIN users u ON d.User_id = u.User_id
        LEFT JOIN diachigh dc ON d.DiaChi_id = dc.add_id
        ORDER BY d.NgayDat DESC";
$result = $conn->query($sql);
$orders = $result->fetch_all(MYSQLI_ASSOC);

// ============================================
// LẤY CHI TIẾT ĐƠN HÀNG (cho modal)
// ============================================
if (isset($_GET['get_detail']) && isset($_GET['id'])) {
    $order_id = intval($_GET['id']);
    
    $sql_detail = "SELECT c.*, s.TenSP, s.image_url 
                   FROM chitiethoadon c
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
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn { animation: fadeIn 0.3s ease-out; }
        
        .bg-gradient-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .text-gradient-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .menu-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .menu-btn.active i { color: white; }
        
        /* Badge trạng thái */
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-confirmed { background: #cfe2ff; color: #084298; }
        .badge-shipping { background: #cff4fc; color: #055160; }
        .badge-delivered { background: #d1e7dd; color: #0f5132; }
        .badge-cancelled { background: #f8d7da; color: #721c24; }
        
        /* Nút chuyển trạng thái */
        .status-btn {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            margin: 0 2px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
        }
        .btn-confirm { background: #0d6efd; color: white; }
        .btn-confirm:hover { background: #0b5ed7; }
        .btn-shipping { background: #0dcaf0; color: #000; }
        .btn-shipping:hover { background: #0bb5d8; }
        .btn-deliver { background: #198754; color: white; }
        .btn-deliver:hover { background: #157347; }
        .btn-cancel { background: #dc3545; color: white; }
        .btn-cancel:hover { background: #bb2d3b; }
        .btn-disabled {
            background: #6c757d;
            color: white;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
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
        .modal.show { display: flex; }
        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 700px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            animation: fadeIn 0.3s ease-out;
        }
        
        .product-img-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        tr:hover { background: #f9fafb; }
        
        .filter-bar {
            display: grid;
            grid-template-columns: 2fr 1.5fr 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }
        .filter-bar input, .filter-bar select {
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .locked-badge {
            background: #6c757d;
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            justify-content: center;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans min-h-screen">

    <!-- HEADER -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="flex justify-between items-center px-6 py-4">
            <h1 class="text-2xl font-bold text-gradient-custom">NVBPlay Admin Panel</h1>
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-3 bg-gray-100 px-4 py-2 rounded-lg">
                    <div class="w-10 h-10 rounded-full bg-gradient-custom flex items-center justify-center text-white font-bold">
                        <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
                    </div>
                    <div>
                        <p class="font-semibold text-sm text-gray-800">
                            <?php echo htmlspecialchars($admin_username); ?>
                        </p>
                        <p class="text-xs text-gray-500">Quản trị viên</p>
                    </div>
                </div>
                <button onclick="logout()" class="bg-gradient-custom text-white font-semibold px-4 py-2 rounded-lg hover:opacity-90 transition shadow-md">
                    <i class="fas fa-sign-out-alt mr-2"></i>Đăng xuất
                </button>
            </div>
        </div>
    </header>

    <div class="flex">
        <!-- SIDEBAR -->
        <aside class="w-64 bg-white shadow-lg min-h-screen">
            <div class="p-4 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-500 uppercase">Danh mục chức năng</h3>
            </div>
            <nav class="p-2">
                <a href="dashboard.php" class="menu-btn flex items-center space-x-3 px-4 py-3 rounded-lg mb-1 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-home w-5 text-gray-500"></i><span>Dashboard</span>
                </a>
                <a href="users.php" class="menu-btn flex items-center space-x-3 px-4 py-3 rounded-lg mb-1 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-users w-5 text-gray-500"></i><span>Quản lý người dùng</span>
                </a>
                <a href="categories.php" class="menu-btn flex items-center space-x-3 px-4 py-3 rounded-lg mb-1 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-tags w-5 text-gray-500"></i><span>Quản lý danh mục</span>
                </a>
                <a href="product.php" class="menu-btn flex items-center space-x-3 px-4 py-3 rounded-lg mb-1 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-box w-5 text-gray-500"></i><span>Quản lý sản phẩm</span>
                </a>
                <a href="import.php" class="menu-btn flex items-center space-x-3 px-4 py-3 rounded-lg mb-1 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-arrow-down w-5 text-gray-500"></i><span>Quản lý nhập hàng</span>
                </a>
                <a href="price.php" class="menu-btn flex items-center space-x-3 px-4 py-3 rounded-lg mb-1 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-tag w-5 text-gray-500"></i><span>Quản lý giá bán</span>
                </a>
                <a href="orders.php" class="menu-btn active flex items-center space-x-3 px-4 py-3 rounded-lg mb-1">
                    <i class="fas fa-receipt w-5 text-white"></i><span>Quản lý đơn hàng</span>
                </a>
                <a href="inventory.php" class="menu-btn flex items-center space-x-3 px-4 py-3 rounded-lg mb-1 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-warehouse w-5 text-gray-500"></i><span>Tồn kho & Báo cáo</span>
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-8">
            <div class="bg-white rounded-xl shadow-lg p-6 animate-fadeIn">
                <div class="flex justify-between items-center mb-6 pb-4 border-b">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-receipt text-gradient-custom mr-2"></i>Quản lý đơn hàng
                    </h2>
                </div>

                <?php if (isset($message)): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">
                        <i class="fas fa-check-circle mr-2"></i><?php echo $message; ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Bộ lọc -->
                <div class="filter-bar">
                    <input type="text" id="searchOrder" placeholder="🔍 Tìm theo mã, tên khách, SĐT..." class="border-gray-200">
                    <div class="flex gap-2">
                        <input type="date" id="fromDate" class="border-gray-200">
                        <span class="self-center">-</span>
                        <input type="date" id="toDate" class="border-gray-200">
                    </div>
                    <select id="statusFilter" class="border-gray-200">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending">Chưa xử lý</option>
                        <option value="confirmed">Đã xác nhận</option>
                        <option value="shipping">Đang giao</option>
                        <option value="delivered">Đã giao thành công</option>
                        <option value="cancelled">Đã hủy</option>
                    </select>
                    <select id="sortWard" class="border-gray-200">
                        <option value="">Sắp xếp theo phường</option>
                        <option value="asc">A → Z</option>
                        <option value="desc">Z → A</option>
                    </select>
                </div>

                <!-- Bảng đơn hàng -->
                <div class="overflow-x-auto border rounded-lg">
                    <table class="w-full" id="ordersTable">
                        <thead>
                            <tr class="bg-gradient-custom text-white">
                                <th class="p-4 text-left">Mã đơn</th>
                                <th class="p-4 text-left">Khách hàng</th>
                                <th class="p-4 text-left">SĐT</th>
                                <th class="p-4 text-left">Địa chỉ</th>
                                <th class="p-4 text-left">Phường</th>
                                <th class="p-4 text-right">Tổng tiền</th>
                                <th class="p-4 text-left">Ngày đặt</th>
                                <th class="p-4 text-left">Trạng thái</th>
                                <th class="p-4 text-center">Thao tác</th>
                             </thead>
                        <tbody id="ordersTableBody">
                            <?php foreach ($orders as $order): ?>
                            <?php 
                                $status = $order['TrangThai'];
                                $isLocked = ($status == 'delivered' || $status == 'cancelled');
                            ?>
                            <tr class="hover:bg-gray-50" data-ward="<?php echo htmlspecialchars($order['Quan'] ?? ''); ?>">
                                <td class="p-4 font-mono">#ORD<?php echo str_pad($order['DonHang_id'], 5, '0', STR_PAD_LEFT); ?>    </td>
                                <td class="p-4"><?php echo htmlspecialchars($order['customer_name'] ?? $order['Ten_nguoi_nhan'] ?? 'Khách lẻ'); ?>    </td>
                                <td class="p-4"><?php echo htmlspecialchars($order['customer_phone'] ?? $order['SDT_nhan'] ?? ''); ?>    </td>
                                <td class="p-4">
                                    <?php 
                                    $address = [];
                                    if ($order['Duong']) $address[] = $order['Duong'];
                                    if ($order['Dia_chi_chitiet']) $address[] = $order['Dia_chi_chitiet'];
                                    echo htmlspecialchars(implode(', ', $address));
                                    ?>
                                 </td>
                                <td class="p-4"><?php echo htmlspecialchars($order['Quan'] ?? 'Chưa có'); ?>    </td>
                                <td class="p-4 text-right font-semibold text-indigo-600"><?php echo number_format($order['TongTien'], 0, ',', '.'); ?>đ    </td>
                                <td class="p-4"><?php echo date('d/m/Y H:i', strtotime($order['NgayDat'])); ?>    </td>
                                <td class="p-4">
                                    <?php
                                    $badgeClass = '';
                                    $statusText = '';
                                    switch($status) {
                                        case 'pending': $badgeClass = 'badge-pending'; $statusText = '⏳ Chưa xử lý'; break;
                                        case 'confirmed': $badgeClass = 'badge-confirmed'; $statusText = '✓ Đã xác nhận'; break;
                                        case 'shipping': $badgeClass = 'badge-shipping'; $statusText = '🚚 Đang giao'; break;
                                        case 'delivered': $badgeClass = 'badge-delivered'; $statusText = '✅ Đã giao thành công'; break;
                                        case 'cancelled': $badgeClass = 'badge-cancelled'; $statusText = '✗ Đã hủy'; break;
                                    }
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $badgeClass; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                 </td>
                                <td class="p-4 text-center">
                                    <div class="action-buttons">
                                        <!-- Nút xem chi tiết -->
                                        <button onclick="viewOrderDetail(<?php echo $order['DonHang_id']; ?>)" class="text-blue-500 hover:text-blue-700 px-2 py-1" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <?php if ($isLocked): ?>
                                            <!-- Đã giao hoặc đã hủy -> khóa -->
                                            <span class="locked-badge">
                                                <i class="fas fa-lock"></i> Đã khóa
                                            </span>
                                        <?php else: ?>
                                            <!-- Nút chuyển trạng thái theo từng giai đoạn -->
                                            <?php if ($status == 'pending'): ?>
                                                <a href="?action=update&id=<?php echo $order['DonHang_id']; ?>&status=confirmed" 
                                                   class="status-btn btn-confirm" 
                                                   onclick="return confirm('Xác nhận đơn hàng #ORD<?php echo str_pad($order['DonHang_id'], 5, '0', STR_PAD_LEFT); ?>?')">
                                                    <i class="fas fa-check"></i> Xác nhận
                                                </a>
                                                <a href="?action=update&id=<?php echo $order['DonHang_id']; ?>&status=cancelled" 
                                                   class="status-btn btn-cancel" 
                                                   onclick="return confirm('Hủy đơn hàng #ORD<?php echo str_pad($order['DonHang_id'], 5, '0', STR_PAD_LEFT); ?>?')">
                                                    <i class="fas fa-times"></i> Hủy
                                                </a>
                                            <?php elseif ($status == 'confirmed'): ?>
                                                <a href="?action=update&id=<?php echo $order['DonHang_id']; ?>&status=shipping" 
                                                   class="status-btn btn-shipping" 
                                                   onclick="return confirm('Chuyển đơn hàng #ORD<?php echo str_pad($order['DonHang_id'], 5, '0', STR_PAD_LEFT); ?> sang trạng thái ĐANG GIAO?')">
                                                    <i class="fas fa-truck"></i> Giao hàng
                                                </a>
                                                <a href="?action=update&id=<?php echo $order['DonHang_id']; ?>&status=cancelled" 
                                                   class="status-btn btn-cancel" 
                                                   onclick="return confirm('Hủy đơn hàng #ORD<?php echo str_pad($order['DonHang_id'], 5, '0', STR_PAD_LEFT); ?>?')">
                                                    <i class="fas fa-times"></i> Hủy
                                                </a>
                                            <?php elseif ($status == 'shipping'): ?>
                                                <a href="?action=update&id=<?php echo $order['DonHang_id']; ?>&status=delivered" 
                                                   class="status-btn btn-deliver" 
                                                   onclick="return confirm('Xác nhận đã giao hàng thành công đơn hàng #ORD<?php echo str_pad($order['DonHang_id'], 5, '0', STR_PAD_LEFT); ?>?')">
                                                    <i class="fas fa-check-double"></i> Đã giao
                                                </a>
                                                <a href="?action=update&id=<?php echo $order['DonHang_id']; ?>&status=cancelled" 
                                                   class="status-btn btn-cancel" 
                                                   onclick="return confirm('Hủy đơn hàng #ORD<?php echo str_pad($order['DonHang_id'], 5, '0', STR_PAD_LEFT); ?>?')">
                                                    <i class="fas fa-times"></i> Hủy
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                 </td>
                              </tr>
                            <?php endforeach; ?>
                        </tbody>
                     </table>
                </div>
                
                <?php if (empty($orders)): ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-box-open text-4xl mb-2 block"></i>
                        Chưa có đơn hàng nào.
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- MODAL CHI TIẾT ĐƠN HÀNG -->
    <div id="orderDetailModal" class="modal">
        <div class="modal-content">
            <div class="bg-gradient-custom text-white px-6 py-4 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-semibold"><i class="fas fa-info-circle mr-2"></i>Chi tiết đơn hàng</h3>
                <button onclick="closeModal()" class="text-white hover:text-gray-200 text-xl">&times;</button>
            </div>
            <div class="p-6" id="orderDetailContent">
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-2xl"></i> Đang tải...
                </div>
            </div>
            <div class="px-6 py-4 border-t flex justify-end">
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Đóng</button>
            </div>
        </div>
    </div>

    <script>
        // Lấy dữ liệu chi tiết đơn hàng
        function viewOrderDetail(orderId) {
            const modal = document.getElementById('orderDetailModal');
            const content = document.getElementById('orderDetailContent');
            
            modal.classList.add('show');
            content.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl"></i> Đang tải...</div>';
            
            fetch(`?get_detail=1&id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        let html = `
                            <div class="mb-4">
                                <h4 class="font-semibold text-gray-700 mb-2">Danh sách sản phẩm</h4>
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="p-2 text-left">Sản phẩm</th>
                                            <th class="p-2 text-right">SL</th>
                                            <th class="p-2 text-right">Đơn giá</th>
                                            <th class="p-2 text-right">Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        let total = 0;
                        data.forEach(item => {
                            let subtotal = item.SoLuong * item.Gia;
                            total += subtotal;
                            html += `
                                <tr class="border-b">
                                    <td class="p-2">
                                        <div class="flex items-center gap-2">
                                            ${item.image_url ? `<img src="../${item.image_url}" class="product-img-thumb">` : '<div class="w-10 h-10 bg-gray-100 rounded flex items-center justify-center"><i class="fas fa-image text-gray-400"></i></div>'}
                                            <span>${item.TenSP}</span>
                                        </div>
                                    </td>
                                    <td class="p-2 text-right">${item.SoLuong}</td>
                                    <td class="p-2 text-right">${new Intl.NumberFormat('vi-VN').format(item.Gia)}đ</td>
                                    <td class="p-2 text-right">${new Intl.NumberFormat('vi-VN').format(subtotal)}đ</td>
                                </tr>
                            `;
                        });
                        html += `
                                    <tr class="bg-gray-50 font-semibold">
                                        <td colspan="3" class="p-2 text-right">Tổng cộng:</td>
                                        <td class="p-2 text-right text-indigo-600">${new Intl.NumberFormat('vi-VN').format(total)}đ</td>
                                    </tr>
                                </tbody>
                            </table>
                        `;
                        content.innerHTML = html;
                    } else {
                        content.innerHTML = '<div class="text-center py-8 text-gray-500">Không có chi tiết đơn hàng</div>';
                    }
                })
                .catch(error => {
                    content.innerHTML = '<div class="text-center py-8 text-red-500">Có lỗi xảy ra khi tải dữ liệu!</div>';
                });
        }
        
        function closeModal() {
            document.getElementById('orderDetailModal').classList.remove('show');
        }
        
        // Lọc theo thời gian
        function filterByDate() {
            const fromDate = document.getElementById('fromDate').value;
            const toDate = document.getElementById('toDate').value;
            const rows = document.querySelectorAll('#ordersTableBody tr');
            
            rows.forEach(row => {
                const dateCell = row.cells[6]?.textContent.trim();
                if (!dateCell) return;
                const rowDate = dateCell.split(' ')[0];
                const [day, month, year] = rowDate.split('/');
                const rowDateObj = new Date(`${year}-${month}-${day}`);
                
                let show = true;
                if (fromDate && rowDateObj < new Date(fromDate)) show = false;
                if (toDate && rowDateObj > new Date(toDate)) show = false;
                
                row.style.display = show ? '' : 'none';
            });
        }
        
        // Lọc theo trạng thái
        function filterByStatus() {
            const status = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('#ordersTableBody tr');
            
            rows.forEach(row => {
                const statusSpan = row.cells[7]?.querySelector('span');
                const currentStatus = statusSpan ? statusSpan.textContent.trim() : '';
                let statusValue = '';
                if (currentStatus.includes('Chưa xử lý')) statusValue = 'pending';
                else if (currentStatus.includes('Đã xác nhận')) statusValue = 'confirmed';
                else if (currentStatus.includes('Đang giao')) statusValue = 'shipping';
                else if (currentStatus.includes('Đã giao thành công')) statusValue = 'delivered';
                else if (currentStatus.includes('Đã hủy')) statusValue = 'cancelled';
                
                if (status === '' || statusValue === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        // Sắp xếp theo phường
        function sortByWard() {
            const sortOrder = document.getElementById('sortWard').value;
            const tbody = document.getElementById('ordersTableBody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            if (sortOrder === '') return;
            
            rows.sort((a, b) => {
                const wardA = a.getAttribute('data-ward') || '';
                const wardB = b.getAttribute('data-ward') || '';
                
                if (sortOrder === 'asc') {
                    return wardA.localeCompare(wardB, 'vi');
                } else {
                    return wardB.localeCompare(wardA, 'vi');
                }
            });
            
            rows.forEach(row => tbody.appendChild(row));
        }
        
        // Tìm kiếm
        document.getElementById('searchOrder').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#ordersTableBody tr');
            
            rows.forEach(row => {
                const code = row.cells[0]?.textContent.toLowerCase() || '';
                const name = row.cells[1]?.textContent.toLowerCase() || '';
                const phone = row.cells[2]?.textContent.toLowerCase() || '';
                
                if (code.includes(searchValue) || name.includes(searchValue) || phone.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        document.getElementById('fromDate').addEventListener('change', filterByDate);
        document.getElementById('toDate').addEventListener('change', filterByDate);
        document.getElementById('statusFilter').addEventListener('change', filterByStatus);
        document.getElementById('sortWard').addEventListener('change', sortByWard);
        
        function logout() {
            if (confirm('Bạn có chắc muốn đăng xuất?')) {
                window.location.href = 'logout.php';
            }
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>