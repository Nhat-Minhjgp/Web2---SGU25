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

// Xử lý cập nhật trạng thái đơn hàng
if (isset($_GET['action']) && isset($_GET['id']) && isset($_GET['status'])) {
    $order_id = intval($_GET['id']);
    $new_status = intval($_GET['status']);
    
    $sql = "SELECT TrangThai FROM donhang WHERE DonHang_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current = $result->fetch_assoc();
    $current_status = intval($current['TrangThai']);
    
    $status_names = [0 => 'Chờ xử lý', 1 => 'Đã xác nhận', 2 => 'Đã giao', 3 => 'Đã hủy'];
    
    if ($current_status == 2 || $current_status == 3) {
        $error = "Đơn hàng đã hoàn tất, không thể thay đổi trạng thái!";
    } else {
        $valid_transitions = [0 => [1, 3], 1 => [2, 3]];
        
        if (in_array($new_status, $valid_transitions[$current_status])) {
            $update = $conn->prepare("UPDATE donhang SET TrangThai = ? WHERE DonHang_id = ?");
            $update->bind_param("ii", $new_status, $order_id);
            if ($update->execute()) {
                $message = "Đã cập nhật trạng thái đơn hàng thành " . $status_names[$new_status];
            } else {
                $error = "Có lỗi xảy ra!";
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
        dc.Ten_nguoi_nhan, dc.SDT_nhan, dc.Duong, dc.Quan, dc.Tinh_thanhpho, dc.Dia_chi_chitiet
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
        
        .menu-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 8px;
            color: #4b5563;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 14px;
        }
        .menu-btn i {
            width: 20px;
            color: #9ca3af;
        }
        .menu-btn:hover {
            background-color: #f3f4f6;
            color: #667eea;
        }
        .menu-btn:hover i {
            color: #667eea;
        }
        .menu-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .menu-btn.active i {
            color: white;
        }
        
        .badge-0 { background: #fff3cd; color: #856404; }
        .badge-1 { background: #cfe2ff; color: #084298; }
        .badge-2 { background: #d1e7dd; color: #0f5132; }
        .badge-3 { background: #f8d7da; color: #721c24; }
        
        .status-btn {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
        }
        .btn-confirm { background: #0d6efd; color: white; }
        .btn-deliver { background: #198754; color: white; }
        .btn-cancel { background: #dc3545; color: white; }
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
        
        .sidebar {
            width: 280px;
            background: white;
            border-right: 1px solid #e5e7eb;
            padding: 20px 0;
        }
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 20px;
        }
        .sidebar-header h3 {
            font-size: 12px;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .sidebar-nav {
            display: flex;
            flex-direction: column;
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

    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="flex justify-between items-center px-6 py-4">
            <h1 class="text-2xl font-bold text-gradient-custom">NVBPlay Admin Panel</h1>
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-3 bg-gray-100 px-4 py-2 rounded-lg">
                    <div class="w-10 h-10 rounded-full bg-gradient-custom flex items-center justify-center text-white font-bold">
                        <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
                    </div>
                    <div>
                        <p class="font-semibold text-sm text-gray-800"><?php echo htmlspecialchars($admin_username); ?></p>
                        <p class="text-xs text-gray-500">Quản trị viên</p>
                    </div>
                </div>
                <a href="logout.php" class="bg-gradient-custom text-white font-semibold px-4 py-2 rounded-lg hover:opacity-90 transition shadow-md">
                    <i class="fas fa-sign-out-alt mr-2"></i>Đăng xuất
                </a>
            </div>
        </div>
    </header>

    <div class="flex">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>Danh mục chức năng</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="menu-btn"><i class="fas fa-home"></i> Dashboard</a>
                <a href="users.php" class="menu-btn"><i class="fas fa-users"></i> Quản lý người dùng</a>
                <a href="categories.php" class="menu-btn"><i class="fas fa-tags"></i> Quản lý danh mục</a>
                <a href="product.php" class="menu-btn"><i class="fas fa-box"></i> Quản lý sản phẩm</a>
                <a href="import.php" class="menu-btn"><i class="fas fa-arrow-down"></i> Quản lý nhập hàng</a>
                <a href="price.php" class="menu-btn"><i class="fas fa-tag"></i> Quản lý giá bán</a>
                <a href="orders.php" class="menu-btn active"><i class="fas fa-receipt"></i> Quản lý đơn hàng</a>
                <a href="inventory.php" class="menu-btn"><i class="fas fa-warehouse"></i> Tồn kho & Báo cáo</a>
            </nav>
        </aside>

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

                <div class="filter-bar">
                    <input type="text" id="searchOrder" placeholder="🔍 Tìm theo mã, tên khách, SĐT...">
                    <div class="flex gap-2">
                        <input type="date" id="fromDate">
                        <span class="self-center">-</span>
                        <input type="date" id="toDate">
                    </div>
                    <select id="statusFilter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="0">Chờ xử lý</option>
                        <option value="1">Đã xác nhận</option>
                        <option value="2">Đã giao</option>
                        <option value="3">Đã hủy</option>
                    </select>
                    <select id="sortWard">
                        <option value="">Sắp xếp theo phường</option>
                        <option value="asc">A → Z</option>
                        <option value="desc">Z → A</option>
                    </select>
                </div>

                <div class="overflow-x-auto border rounded-lg">
                    <table class="w-full" id="ordersTable">
                        <thead>
                            <tr class="bg-gradient-custom text-white">
                                <th class="p-3">Mã đơn</th>
                                <th class="p-3">Khách hàng</th>
                                <th class="p-3">SĐT</th>
                                <th class="p-3">Địa chỉ</th>
                                <th class="p-3">Phường</th>
                                <th class="p-3 text-right">Tổng tiền</th>
                                <th class="p-3">Ngày đặt</th>
                                <th class="p-3">Trạng thái</th>
                                <th class="p-3 text-center">Thao tác</th>
                             </thead>
                        <tbody id="ordersTableBody">
                            <?php foreach ($orders as $order): ?>
                            <?php $status = intval($order['TrangThai']); $isLocked = ($status == 2 || $status == 3); ?>
                            <tr class="hover:bg-gray-50" data-ward="<?php echo htmlspecialchars($order['Quan'] ?? ''); ?>">
                                <td class="font-mono">#ORD<?php echo str_pad($order['DonHang_id'], 5, '0', STR_PAD_LEFT); ?>    </td>
                                <td><?php echo htmlspecialchars($order['customer_name'] ?? $order['Ten_nguoi_nhan'] ?? 'Khách lẻ'); ?>    </td>
                                <td><?php echo htmlspecialchars($order['customer_phone'] ?? $order['SDT_nhan'] ?? ''); ?>    </td>
                                <td><?php 
                                    $address = [];
                                    if ($order['Duong']) $address[] = $order['Duong'];
                                    if ($order['Dia_chi_chitiet']) $address[] = $order['Dia_chi_chitiet'];
                                    echo htmlspecialchars(implode(', ', $address));
                                ?>    </td>
                                <td><?php echo htmlspecialchars($order['Quan'] ?? 'Chưa có'); ?>    </td>
                                <td class="text-right font-semibold text-indigo-600"><?php echo number_format($order['TongTien'], 0, ',', '.'); ?>đ    </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['NgayDat'])); ?>    </td>
                                <td>
                                    <span class="px-3 py-1 rounded-full text-xs font-medium badge-<?php echo $status; ?>">
                                        <?php echo $status == 0 ? '⏳ Chờ xử lý' : ($status == 1 ? '✓ Đã xác nhận' : ($status == 2 ? '✅ Đã giao' : '✗ Đã hủy')); ?>
                                    </span>
                                 </td>
                                <td class="text-center">
                                    <div class="action-buttons">
                                        <button onclick="viewOrderDetail(<?php echo $order['DonHang_id']; ?>)" class="text-blue-500 hover:text-blue-700 px-2 py-1" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($isLocked): ?>
                                            <span class="locked-badge"><i class="fas fa-lock"></i> Đã khóa</span>
                                        <?php elseif ($status == 0): ?>
                                            <a href="?action=update&id=<?php echo $order['DonHang_id']; ?>&status=1" class="status-btn btn-confirm" onclick="return confirm('Xác nhận đơn hàng?')"><i class="fas fa-check"></i> Xác nhận</a>
                                            <a href="?action=update&id=<?php echo $order['DonHang_id']; ?>&status=3" class="status-btn btn-cancel" onclick="return confirm('Hủy đơn hàng?')"><i class="fas fa-times"></i> Hủy</a>
                                        <?php elseif ($status == 1): ?>
                                            <a href="?action=update&id=<?php echo $order['DonHang_id']; ?>&status=2" class="status-btn btn-deliver" onclick="return confirm('Xác nhận đã giao hàng?')"><i class="fas fa-check-double"></i> Đã giao</a>
                                            <a href="?action=update&id=<?php echo $order['DonHang_id']; ?>&status=3" class="status-btn btn-cancel" onclick="return confirm('Hủy đơn hàng?')"><i class="fas fa-times"></i> Hủy</a>
                                        <?php endif; ?>
                                    </div>
                                 </td>
                             </tr>
                            <?php endforeach; ?>
                        </tbody>
                     </table>
                </div>
                <?php if (empty($orders)): ?>
                    <div class="text-center py-8 text-gray-500"><i class="fas fa-box-open text-4xl mb-2 block"></i>Chưa có đơn hàng nào.</div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div id="orderDetailModal" class="modal">
        <div class="modal-content">
            <div class="bg-gradient-custom text-white px-6 py-4 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-semibold"><i class="fas fa-info-circle mr-2"></i>Chi tiết đơn hàng</h3>
                <button onclick="closeModal()" class="text-white hover:text-gray-200 text-xl">&times;</button>
            </div>
            <div class="p-6" id="orderDetailContent"><div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl"></i> Đang tải...</div></div>
            <div class="px-6 py-4 border-t flex justify-end"><button onclick="closeModal()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Đóng</button></div>
        </div>
    </div>

    <script>
        function viewOrderDetail(orderId) {
            const modal = document.getElementById('orderDetailModal');
            const content = document.getElementById('orderDetailContent');
            modal.classList.add('show');
            content.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl"></i> Đang tải...</div>';
            fetch(`?get_detail=1&id=${orderId}`).then(res => res.json()).then(data => {
                if (data.length > 0) {
                    let html = `<div class="mb-4"><h4 class="font-semibold mb-2">Danh sách sản phẩm</h4><table class="w-full text-sm"><thead class="bg-gray-100"><tr><th class="p-2 text-left">Sản phẩm</th><th class="p-2 text-right">SL</th><th class="p-2 text-right">Đơn giá</th><th class="p-2 text-right">Thành tiền</th></tr></thead><tbody>`;
                    let total = 0;
                    data.forEach(item => {
                        let subtotal = item.SoLuong * item.Gia;
                        total += subtotal;
                        html += `<tr class="border-b"><td class="p-2"><div class="flex items-center gap-2">${item.image_url ? `<img src="../${item.image_url}" class="product-img-thumb">` : '<div class="w-10 h-10 bg-gray-100 rounded flex items-center justify-center"><i class="fas fa-image text-gray-400"></i></div>'}<span>${item.TenSP}</span></div></td><td class="p-2 text-right">${item.SoLuong}</td><td class="p-2 text-right">${new Intl.NumberFormat('vi-VN').format(item.Gia)}đ</td><td class="p-2 text-right">${new Intl.NumberFormat('vi-VN').format(subtotal)}đ</td></tr>`;
                    });
                    html += `<tr class="bg-gray-50 font-semibold"><td colspan="3" class="p-2 text-right">Tổng cộng:</td><td class="p-2 text-right text-indigo-600">${new Intl.NumberFormat('vi-VN').format(total)}đ</td></tr></tbody></table></div>`;
                    content.innerHTML = html;
                } else { content.innerHTML = '<div class="text-center py-8 text-gray-500">Không có chi tiết đơn hàng</div>'; }
            }).catch(() => { content.innerHTML = '<div class="text-center py-8 text-red-500">Có lỗi xảy ra!</div>'; });
        }
        function closeModal() { document.getElementById('orderDetailModal').classList.remove('show'); }
        function filterByDate() {
            const from = document.getElementById('fromDate').value, to = document.getElementById('toDate').value;
            document.querySelectorAll('#ordersTableBody tr').forEach(row => {
                const date = row.cells[6]?.textContent.trim().split(' ')[0];
                if (!date) return;
                const [d,m,y] = date.split('/');
                const rowDate = new Date(`${y}-${m}-${d}`);
                let show = true;
                if (from && rowDate < new Date(from)) show = false;
                if (to && rowDate > new Date(to)) show = false;
                row.style.display = show ? '' : 'none';
            });
        }
        function filterByStatus() {
            const status = document.getElementById('statusFilter').value;
            document.querySelectorAll('#ordersTableBody tr').forEach(row => {
                const statusSpan = row.cells[7]?.querySelector('span');
                let val = '';
                if (statusSpan?.textContent.includes('Chờ xử lý')) val = '0';
                else if (statusSpan?.textContent.includes('Đã xác nhận')) val = '1';
                else if (statusSpan?.textContent.includes('Đã giao')) val = '2';
                else if (statusSpan?.textContent.includes('Đã hủy')) val = '3';
                row.style.display = (status === '' || val === status) ? '' : 'none';
            });
        }
        function sortByWard() {
            const order = document.getElementById('sortWard').value;
            if (!order) return;
            const tbody = document.getElementById('ordersTableBody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            rows.sort((a,b) => {
                const wa = a.getAttribute('data-ward') || '', wb = b.getAttribute('data-ward') || '';
                return order === 'asc' ? wa.localeCompare(wb, 'vi') : wb.localeCompare(wa, 'vi');
            });
            rows.forEach(r => tbody.appendChild(r));
        }
        document.getElementById('searchOrder').addEventListener('keyup', function() {
            const val = this.value.toLowerCase();
            document.querySelectorAll('#ordersTableBody tr').forEach(row => {
                const code = row.cells[0]?.textContent.toLowerCase() || '';
                const name = row.cells[1]?.textContent.toLowerCase() || '';
                const phone = row.cells[2]?.textContent.toLowerCase() || '';
                row.style.display = (code.includes(val) || name.includes(val) || phone.includes(val)) ? '' : 'none';
            });
        });
        document.getElementById('fromDate').addEventListener('change', filterByDate);
        document.getElementById('toDate').addEventListener('change', filterByDate);
        document.getElementById('statusFilter').addEventListener('change', filterByStatus);
        document.getElementById('sortWard').addEventListener('change', sortByWard);
        function logout() { if(confirm('Đăng xuất?')) window.location.href='logout.php'; }
        window.onclick = e => { if(e.target.classList.contains('modal')) closeModal(); };
    </script>
</body>
</html>