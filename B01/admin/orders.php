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
        dc.Ten_nguoi_nhan, dc.SDT_nhan, dc.Quan, dc.Tinh_thanhpho, dc.Duong, dc.Dia_chi_chitiet
        FROM donhang d
        LEFT JOIN users u ON d.User_id = u.User_id
        LEFT JOIN diachigh dc ON d.DiaChi_id = dc.add_id
        ORDER BY d.NgayDat DESC";
$result = $conn->query($sql);
$orders = $result->fetch_all(MYSQLI_ASSOC);
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
        
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal.show { display: flex; }
        
        .track-modal-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 900px;
            max-height: 85vh;
            overflow-y: auto;
            animation: fadeIn 0.3s ease-out;
        }
        
        .timeline-item {
            position: relative;
            padding-left: 40px;
            min-height: 60px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 4px;
            top: 20px;
            bottom: -24px;
            width: 2px;
            background: #e5e7eb;
        }
        .timeline-item:last-child::before {
            display: none;
        }
        .timeline-dot {
            position: absolute;
            left: 0;
            top: 0;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #e5e7eb;
            border: 3px solid #fff;
            box-shadow: 0 0 0 2px #e5e7eb;
        }
        .timeline-item.completed .timeline-dot {
            background: #16a34a;
            box-shadow: 0 0 0 2px #16a34a;
        }
        .timeline-item.cancelled .timeline-dot {
            background: #dc2626;
            box-shadow: 0 0 0 2px #dc2626;
        }
        .timeline-item.active .timeline-dot {
            background: #f59e0b;
            box-shadow: 0 0 0 2px #f59e0b;
        }
        
        .copy-btn {
            cursor: pointer;
            transition: all 0.2s;
        }
        .copy-btn:hover {
            background: #e5e7eb;
        }
        
        .product-img-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-0 { background: #fef3c7; color: #92400e; }
        .status-1 { background: #dbeafe; color: #1e40af; }
        .status-2 { background: #d1fae5; color: #065f46; }
        .status-3 { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="bg-gray-100 font-sans min-h-screen">

    <!-- HEADER -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="flex justify-between items-center px-6 py-4">
            <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">NVBPlay Admin Panel</h1>
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-3 bg-gray-100 px-4 py-2 rounded-lg">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-indigo-600 to-purple-600 flex items-center justify-center text-white font-bold">
                        <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
                    </div>
                    <div>
                        <p class="font-semibold text-sm text-gray-800">
                            <?php echo htmlspecialchars($admin_username); ?>
                        </p>
                        <p class="text-xs text-gray-500">Quản trị viên</p>
                    </div>
                </div>
                <a href="logout.php" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold px-4 py-2 rounded-lg hover:opacity-90 transition shadow-md">
                    <i class="fas fa-sign-out-alt mr-2"></i>Đăng xuất
                </a>
            </div>
        </div>
    </header>

    <div class="flex">
        <!-- SIDEBAR -->
        <aside class="w-64 bg-white shadow-lg min-h-screen">
            <div class="p-4 border-b border-gray-200">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Danh mục chức năng</h3>
            </div>
            <nav class="p-2 space-y-1">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-2.5 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-indigo-600 transition">
                    <i class="fas fa-home w-5 text-gray-400"></i> Dashboard
                </a>
                <a href="users.php" class="flex items-center gap-3 px-4 py-2.5 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-indigo-600 transition">
                    <i class="fas fa-users w-5 text-gray-400"></i> Quản lý người dùng
                </a>
                <a href="categories.php" class="flex items-center gap-3 px-4 py-2.5 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-indigo-600 transition">
                    <i class="fas fa-tags w-5 text-gray-400"></i> Quản lý danh mục
                </a>
                <a href="product.php" class="flex items-center gap-3 px-4 py-2.5 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-indigo-600 transition">
                    <i class="fas fa-box w-5 text-gray-400"></i> Quản lý sản phẩm
                </a>
                <a href="import.php" class="flex items-center gap-3 px-4 py-2.5 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-indigo-600 transition">
                    <i class="fas fa-arrow-down w-5 text-gray-400"></i> Quản lý nhập hàng
                </a>
                <a href="price.php" class="flex items-center gap-3 px-4 py-2.5 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-indigo-600 transition">
                    <i class="fas fa-tag w-5 text-gray-400"></i> Quản lý giá bán
                </a>
                <a href="orders.php" class="flex items-center gap-3 px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg shadow-md transition">
                    <i class="fas fa-receipt w-5 text-white"></i> Quản lý đơn hàng
                </a>
                <a href="inventory.php" class="flex items-center gap-3 px-4 py-2.5 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-indigo-600 transition">
                    <i class="fas fa-warehouse w-5 text-gray-400"></i> Tồn kho & Báo cáo
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-8">
            <div class="bg-white rounded-xl shadow-lg p-6 animate-fadeIn">
                <div class="flex justify-between items-center mb-6 pb-4 border-b">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-receipt text-indigo-600 mr-2"></i>Quản lý đơn hàng
                    </h2>
                </div>

                <?php if (isset($message)): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">
                        <i class="fas fa-check-circle mr-2"></i><?php echo $message; ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Bộ lọc -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <input type="text" id="searchOrder" placeholder="🔍 Tìm theo ID, tên khách, SĐT..." 
                           class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    <div class="flex gap-2">
                        <input type="date" id="fromDate" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none flex-1">
                        <span class="self-center text-gray-400">-</span>
                        <input type="date" id="toDate" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none flex-1">
                    </div>
                    <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        <option value="">Tất cả trạng thái</option>
                        <option value="0">Chờ xử lý</option>
                        <option value="1">Đã xác nhận</option>
                        <option value="2">Đã giao</option>
                        <option value="3">Đã hủy</option>
                    </select>
                    <select id="sortWard" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        <option value="">Sắp xếp theo phường</option>
                        <option value="asc">A → Z</option>
                        <option value="desc">Z → A</option>
                    </select>
                </div>

                <!-- Bảng đơn hàng -->
                <div class="overflow-x-auto border border-gray-200 rounded-xl">
                    <table class="w-full min-w-[1000px]" id="ordersTable">
                        <thead class="bg-gradient-to-r from-indigo-600 to-purple-600">
                            应
                                <th class="px-4 py-3 text-left text-white text-sm font-semibold">ID</th>
                                <th class="px-4 py-3 text-left text-white text-sm font-semibold">Mã đơn</th>
                                <th class="px-4 py-3 text-left text-white text-sm font-semibold">Khách hàng</th>
                                <th class="px-4 py-3 text-left text-white text-sm font-semibold">SĐT</th>
                                <th class="px-4 py-3 text-left text-white text-sm font-semibold">Phường</th>
                                <th class="px-4 py-3 text-right text-white text-sm font-semibold">Tổng tiền</th>
                                <th class="px-4 py-3 text-left text-white text-sm font-semibold">Ngày đặt</th>
                                <th class="px-4 py-3 text-left text-white text-sm font-semibold">Trạng thái</th>
                                <th class="px-4 py-3 text-center text-white text-sm font-semibold">Thao tác</th>
                            </thead>
                        <tbody id="ordersTableBody" class="divide-y divide-gray-200">
                            <?php foreach ($orders as $order): ?>
                            <?php 
                                $status = intval($order['TrangThai']); 
                                $isLocked = ($status == 2 || $status == 3);
                                $order_display_code = "ORD" . str_pad($order['DonHang_id'], 5, '0', STR_PAD_LEFT);
                            ?>
                            <tr class="hover:bg-gray-50 transition" data-ward="<?php echo htmlspecialchars($order['Quan'] ?? ''); ?>">
                                <td class="px-4 py-3 text-gray-600 text-sm"><?php echo $order['DonHang_id']; ?>    </td>
                                <td class="px-4 py-3 font-mono text-sm">#<?php echo $order_display_code; ?>    </td>
                                <td class="px-4 py-3 text-gray-800"><?php echo htmlspecialchars($order['customer_name'] ?? $order['Ten_nguoi_nhan'] ?? 'Khách lẻ'); ?>    </td>
                                <td class="px-4 py-3 text-gray-600"><?php echo htmlspecialchars($order['customer_phone'] ?? $order['SDT_nhan'] ?? ''); ?>    </td>
                                <td class="px-4 py-3 text-gray-600"><?php echo htmlspecialchars($order['Quan'] ?? 'Chưa có'); ?>    </td>
                                <td class="px-4 py-3 text-right font-semibold text-indigo-600"><?php echo number_format($order['TongTien'], 0, ',', '.'); ?>đ    </td>
                                <td class="px-4 py-3 text-gray-600 text-sm"><?php echo date('d/m/Y H:i', strtotime($order['NgayDat'])); ?>    </td>
                                <td class="px-4 py-3">
                                    <span class="status-badge status-<?php echo $status; ?>">
                                        <?php echo $status == 0 ? '⏳ Chờ xử lý' : ($status == 1 ? '✓ Đã xác nhận' : ($status == 2 ? '✅ Đã giao' : '✗ Đã hủy')); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1 flex-wrap">
                                        <!-- NÚT TRACK - Xem chi tiết đơn hàng (Modal) -->
                                        <button onclick="viewOrderDetail(<?php echo $order['DonHang_id']; ?>)" 
                                                class="text-green-500 hover:text-green-700 p-1.5 rounded-lg hover:bg-green-50 transition inline-flex items-center gap-1" 
                                                title="Xem chi tiết đơn hàng">
                                            <i class="fas fa-eye"></i> Track
                                        </button>
                                        
                                        <!-- Các nút chuyển trạng thái -->
                                        <?php if ($isLocked): ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-500 text-white rounded-lg text-xs">
                                                <i class="fas fa-lock"></i> Đã khóa
                                            </span>
                                        <?php elseif ($status == 0): ?>
                                            <a href="?action=update&id=<?php echo $order['DonHang_id']; ?>&status=1" 
                                               class="inline-flex items-center gap-1 px-3 py-1 bg-blue-500 text-white rounded-lg text-xs hover:bg-blue-600 transition" 
                                               onclick="return confirm('Xác nhận đơn hàng?')">
                                                <i class="fas fa-check"></i> Xác nhận
                                            </a>
                                            <a href="?action=update&id=<?php echo $order['DonHang_id']; ?>&status=3" 
                                               class="inline-flex items-center gap-1 px-3 py-1 bg-red-500 text-white rounded-lg text-xs hover:bg-red-600 transition" 
                                               onclick="return confirm('Hủy đơn hàng?')">
                                                <i class="fas fa-times"></i> Hủy
                                            </a>
                                        <?php elseif ($status == 1): ?>
                                            <a href="?action=update&id=<?php echo $order['DonHang_id']; ?>&status=2" 
                                               class="inline-flex items-center gap-1 px-3 py-1 bg-green-500 text-white rounded-lg text-xs hover:bg-green-600 transition" 
                                               onclick="return confirm('Xác nhận đã giao hàng?')">
                                                <i class="fas fa-check-double"></i> Đã giao
                                            </a>
                                            <a href="?action=update&id=<?php echo $order['DonHang_id']; ?>&status=3" 
                                               class="inline-flex items-center gap-1 px-3 py-1 bg-red-500 text-white rounded-lg text-xs hover:bg-red-600 transition" 
                                               onclick="return confirm('Hủy đơn hàng?')">
                                                <i class="fas fa-times"></i> Hủy
                                            </a>
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

    <!-- MODAL CHI TIẾT ĐƠN HÀNG (TRACK MODAL) -->
    <div id="trackModal" class="modal">
        <div class="track-modal-content">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-4 rounded-t-xl flex justify-between items-center sticky top-0">
                <h3 class="text-xl font-semibold"><i class="fas fa-truck mr-2"></i>Chi tiết đơn hàng</h3>
                <button onclick="closeTrackModal()" class="text-white hover:text-gray-200 text-2xl">&times;</button>
            </div>
            <div class="p-6" id="trackModalContent">
                <div class="text-center py-12">
                    <i class="fas fa-spinner fa-spin text-3xl text-indigo-600"></i>
                    <p class="text-gray-500 mt-3">Đang tải thông tin đơn hàng...</p>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end sticky bottom-0 bg-white rounded-b-xl">
                <button onclick="closeTrackModal()" class="px-5 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">Đóng</button>
            </div>
        </div>
    </div>

    <script>
        // Hàm mở modal chi tiết đơn hàng
        function viewOrderDetail(orderId) {
            const modal = document.getElementById('trackModal');
            const content = document.getElementById('trackModalContent');
            
            modal.classList.add('show');
            content.innerHTML = '<div class="text-center py-12"><i class="fas fa-spinner fa-spin text-3xl text-indigo-600"></i><p class="text-gray-500 mt-3">Đang tải thông tin đơn hàng...</p></div>';
            
            // Gọi API lấy chi tiết đơn hàng
            fetch(`get_order_detail.php?id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const order = data.order;
                        const items = data.items;
                        const address = data.address;
                        
                        // Format trạng thái
                        const statusText = order.TrangThai == 0 ? 'Chờ xử lý' : (order.TrangThai == 1 ? 'Đã xác nhận' : (order.TrangThai == 2 ? 'Đã giao' : 'Đã hủy'));
                        const statusClass = order.TrangThai == 0 ? 'status-0' : (order.TrangThai == 1 ? 'status-1' : (order.TrangThai == 2 ? 'status-2' : 'status-3'));
                        
                        // Tạo HTML chi tiết đơn hàng
                        let itemsHtml = '';
                        let total = 0;
                        
                        items.forEach(item => {
                            const subtotal = item.SoLuong * item.Gia;
                            total += subtotal;
                            itemsHtml += `
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-3">
                                        <div class="flex items-center gap-3">
                                            ${item.image_url ? `<img src="../${item.image_url}" class="product-img-thumb">` : '<div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center"><i class="fas fa-image text-gray-400"></i></div>'}
                                            <span class="font-medium">${item.TenSP}</span>
                                        </div>
                                    </td>
                                    <td class="p-3 text-center">${item.SoLuong}</td>
                                    <td class="p-3 text-right">${formatMoney(item.Gia)}</td>
                                    <td class="p-3 text-right text-indigo-600 font-semibold">${formatMoney(subtotal)}</td>
                                </tr>
                            `;
                        });
                        
                        // Địa chỉ
                        let addressHtml = '';
                        if (address) {
                            const addrParts = [address.Duong, address.Dia_chi_chitiet, address.Quan, address.Tinh_thanhpho].filter(p => p);
                            addressHtml = addrParts.join(', ');
                        } else if (order.DiaChi_id) {
                            addressHtml = 'Đã lưu trong đơn hàng';
                        } else {
                            addressHtml = 'Chưa có địa chỉ';
                        }
                        
                        // Timeline trạng thái
                        const currentStatus = parseInt(order.TrangThai);
                        const isCancelled = (currentStatus === 3);
                        
                        let timelineHtml = `
                            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                                <h4 class="font-semibold text-gray-800 mb-3">📋 Tiến trình đơn hàng</h4>
                                <div class="space-y-2">
                                    <div class="timeline-item ${currentStatus >= 0 ? 'completed' : ''}">
                                        <div class="timeline-dot"></div>
                                        <p class="font-medium text-gray-900">📦 Đơn hàng đã đặt</p>
                                        <p class="text-sm text-gray-500">${formatDate(order.NgayDat)}</p>
                                    </div>
                                    <div class="timeline-item ${currentStatus >= 1 && !isCancelled ? 'completed' : (currentStatus == 1 ? 'active' : '')}">
                                        <div class="timeline-dot"></div>
                                        <p class="font-medium text-gray-900">✓ Đã xác nhận</p>
                                        <p class="text-sm text-gray-500">${currentStatus >= 1 ? 'Đã xác nhận đơn hàng' : 'Chờ xác nhận'}</p>
                                    </div>
                                    <div class="timeline-item ${currentStatus >= 2 && !isCancelled ? 'completed' : ''}">
                                        <div class="timeline-dot"></div>
                                        <p class="font-medium text-gray-900">🚚 Đang giao hàng</p>
                                        <p class="text-sm text-gray-500">${currentStatus >= 2 ? 'Đơn hàng đang trên đường giao' : 'Chờ vận chuyển'}</p>
                                    </div>
                                    <div class="timeline-item ${currentStatus == 2 ? 'completed' : (isCancelled ? 'cancelled' : '')}">
                                        <div class="timeline-dot"></div>
                                        <p class="font-medium text-gray-900">${isCancelled ? '✗ Đã hủy' : '✅ Hoàn thành'}</p>
                                        <p class="text-sm text-gray-500">${isCancelled ? 'Đơn hàng đã bị hủy' : (currentStatus == 2 ? 'Đã giao hàng thành công' : 'Chờ giao hàng')}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        const html = `
                            <div class="mb-6 pb-4 border-b">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-gray-500 text-sm">Mã đơn hàng</p>
                                        <p class="text-xl font-bold text-gray-800">#ORD${String(order.DonHang_id).padStart(5, '0')}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-gray-500 text-sm">Trạng thái</p>
                                        <span class="status-badge ${statusClass}">${statusText}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                        <i class="fas fa-user text-indigo-500"></i> Thông tin khách hàng
                                    </h4>
                                    <p class="font-medium">${order.customer_name || order.Ten_nguoi_nhan || 'Khách lẻ'}</p>
                                    <p class="text-gray-600 text-sm">${order.customer_phone || order.SDT_nhan || ''}</p>
                                    ${order.customer_email ? `<p class="text-gray-600 text-sm">${order.customer_email}</p>` : ''}
                                </div>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                        <i class="fas fa-map-marker-alt text-indigo-500"></i> Địa chỉ giao hàng
                                    </h4>
                                    <p class="text-gray-700">${addressHtml}</p>
                                    <p class="text-sm text-gray-500 mt-1">Phương thức: ${order.PhuongThucTT || 'COD'}</p>
                                </div>
                            </div>
                            
                            ${timelineHtml}
                            
                            <h4 class="font-semibold text-gray-800 mb-3">🛍️ Sản phẩm đã đặt</h4>
                            <div class="overflow-x-auto border rounded-lg mb-4">
                                <table class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="p-3 text-left text-sm font-medium text-gray-600">Sản phẩm</th>
                                            <th class="p-3 text-center text-sm font-medium text-gray-600">SL</th>
                                            <th class="p-3 text-right text-sm font-medium text-gray-600">Đơn giá</th>
                                            <th class="p-3 text-right text-sm font-medium text-gray-600">Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        ${itemsHtml}
                                    </tbody>
                                    <tfoot class="bg-gray-50">
                                        <tr>
                                            <td colspan="3" class="p-3 text-right font-semibold">Tổng cộng:</td>
                                            <td class="p-3 text-right font-bold text-lg text-indigo-600">${formatMoney(total)}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            
                            <div class="bg-blue-50 rounded-lg p-3 text-sm text-blue-700 flex items-center gap-2">
                                <i class="fas fa-info-circle"></i>
                                <span>Link tra cứu: <code class="bg-white px-2 py-1 rounded">http://localhost/Web2---SGU25/B01/view/track-order.php?code=${order.linkTraCuu ? order.linkTraCuu.split('code=')[1] : order.DonHang_id}</code></span>
                                <button onclick="copyToClipboard('http://localhost/Web2---SGU25/B01/view/track-order.php?code=${order.linkTraCuu ? order.linkTraCuu.split('code=')[1] : order.DonHang_id}')" class="ml-2 text-blue-600 hover:text-blue-800 copy-btn px-2 py-1 rounded">
                                    <i class="fas fa-copy"></i> Sao chép
                                </button>
                            </div>
                        `;
                        
                        content.innerHTML = html;
                    } else {
                        content.innerHTML = `<div class="text-center py-12 text-red-500"><i class="fas fa-exclamation-circle text-4xl mb-2 block"></i>${data.message || 'Không thể tải thông tin đơn hàng'}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    content.innerHTML = '<div class="text-center py-12 text-red-500"><i class="fas fa-exclamation-circle text-4xl mb-2 block"></i>Có lỗi xảy ra khi tải dữ liệu!</div>';
                });
        }
        
        function closeTrackModal() {
            document.getElementById('trackModal').classList.remove('show');
        }
        
        function formatMoney(amount) {
            return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
        }
        
        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute:'2-digit'});
        }
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Đã sao chép link tra cứu!');
            }).catch(() => {
                alert('Không thể sao chép, vui lòng copy thủ công.');
            });
        }
        
        // Filter functions
        function filterByDate() {
            const from = document.getElementById('fromDate').value;
            const to = document.getElementById('toDate').value;
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
                const wa = a.getAttribute('data-ward') || '';
                const wb = b.getAttribute('data-ward') || '';
                return order === 'asc' ? wa.localeCompare(wb, 'vi') : wb.localeCompare(wa, 'vi');
            });
            rows.forEach(r => tbody.appendChild(r));
        }
        
        document.getElementById('searchOrder').addEventListener('keyup', function() {
            const val = this.value.toLowerCase();
            document.querySelectorAll('#ordersTableBody tr').forEach(row => {
                const id = row.cells[0]?.textContent.toLowerCase() || '';
                const name = row.cells[2]?.textContent.toLowerCase() || '';
                const phone = row.cells[3]?.textContent.toLowerCase() || '';
                row.style.display = (id.includes(val) || name.includes(val) || phone.includes(val)) ? '' : 'none';
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
                closeTrackModal();
            }
        }
    </script>
</body>
</html>