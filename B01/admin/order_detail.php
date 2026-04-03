<?php
session_start();
require_once __DIR__ . '/../control/connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Quản trị viên';
$admin_role = $_SESSION['admin_role'] ?? '';
$admin_username = $_SESSION['admin_username'] ?? '';
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    header('Location: orders.php');
    exit();
}

// Lấy thông tin đơn hàng
$sql = "SELECT d.*, u.Ho_ten as customer_name, u.SDT as customer_phone,
        dc.Ten_nguoi_nhan, dc.SDT_nhan, dc.Quan, dc.Tinh_thanhpho, dc.Duong, dc.Dia_chi_chitiet
        FROM donhang d
        LEFT JOIN users u ON d.User_id = u.User_id
        LEFT JOIN diachigh dc ON d.DiaChi_id = dc.add_id
        WHERE d.DonHang_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: orders.php');
    exit();
}

// Lấy chi tiết sản phẩm trong đơn hàng
$sql_detail = "SELECT c.*, s.TenSP, s.image_url FROM chitiethoadon c
               LEFT JOIN sanpham s ON c.SanPham_id = s.SanPham_id
               WHERE c.DonHang_id = ?";
$stmt = $conn->prepare($sql_detail);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$order_code = "ORD" . str_pad($order['DonHang_id'], 5, '0', STR_PAD_LEFT);
$status_text = ['0' => 'Chờ xử lý', '1' => 'Đã xác nhận', '2' => 'Đã giao', '3' => 'Đã hủy'];
$status_class = ['0' => 'bg-yellow-100 text-yellow-800', '1' => 'bg-blue-100 text-blue-800', '2' => 'bg-green-100 text-green-800', '3' => 'bg-red-100 text-red-800'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?php echo $order_code; ?> - Admin</title>
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
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fadeIn { animation: fadeIn 0.3s ease-out; }
        
        /* Sidebar styles */
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
        }
        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 4px;
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
        .menu-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .menu-btn.active i {
            color: white;
        }
    </style>
      <link rel="icon" type="image/svg+xml" href="../img/icons/favicon.png" sizes="32x32">
</head>
<body class="bg-gray-50 font-sans text-gray-800">

    <!-- HEADER - ĐỒNG BỘ VỚI DASHBOARD -->
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
        
        <!-- SIDEBAR - ĐỒNG BỘ VỚI DASHBOARD -->
        <aside class="w-64 bg-white shadow-lg hidden lg:block flex-shrink-0 border-r border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Danh mục chức năng</h3>
            </div>
            <nav class="p-4 space-y-2">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-home w-5"></i> Dashboard
                </a>
                <a href="users.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-users w-5"></i> Quản lý người dùng
                </a>
                <a href="categories.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-list w-5 text-center"></i> Quản lý danh mục
                </a>
                <a href="product.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-box w-5"></i> Quản lý sản phẩm
                </a>
                <a href="import.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-arrow-down w-5"></i> Quản lý nhập hàng
                </a>
                <a href="price.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-tag w-5"></i> Quản lý giá bán
                </a>
                <a href="orders.php" class="flex items-center gap-3 px-4 py-3 bg-gradient-custom text-white rounded-lg shadow-md">
                    <i class="fas fa-receipt w-5"></i> Quản lý đơn hàng
                </a>
                <a href="inventory.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-warehouse w-5"></i> Tồn kho & Báo cáo
                </a>
            </nav>
        </aside>

        <main class="flex-1 p-6 lg:p-8 bg-gray-50">
            <div class="bg-white rounded-xl shadow-lg p-6 lg:p-8">
                <!-- Header -->
                <div class="flex justify-between items-center mb-6 pb-4 border-b">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-receipt text-primary mr-2"></i>Chi tiết đơn hàng
                        </h2>
                        <p class="text-gray-500 mt-1">Mã đơn: <span class="font-mono font-semibold">#<?php echo $order_code; ?></span></p>
                    </div>
                    <a href="orders.php" class="text-gray-600 hover:text-primary transition flex items-center gap-1">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>

                <!-- Thông tin đơn hàng -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-700 mb-2"><i class="fas fa-user mr-2 text-primary"></i>Thông tin khách hàng</h3>
                        <p class="text-sm"><span class="text-gray-500">Họ tên:</span> <?php echo htmlspecialchars($order['customer_name'] ?? $order['Ten_nguoi_nhan'] ?? 'Khách lẻ'); ?></p>
                        <p class="text-sm mt-1"><span class="text-gray-500">SĐT:</span> <?php echo htmlspecialchars($order['customer_phone'] ?? $order['SDT_nhan'] ?? ''); ?></p>
                        <?php if (!empty($order['email'])): ?>
                        <p class="text-sm mt-1"><span class="text-gray-500">Email:</span> <?php echo htmlspecialchars($order['email']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-700 mb-2"><i class="fas fa-map-marker-alt mr-2 text-primary"></i>Địa chỉ giao hàng</h3>
                        <p class="text-sm"><?php echo htmlspecialchars($order['Duong'] ?? $order['Dia_chi_chitiet'] ?? ''); ?></p>
                        <p class="text-sm"><?php echo htmlspecialchars($order['Quan'] ?? ''); ?>, <?php echo htmlspecialchars($order['Tinh_thanhpho'] ?? ''); ?></p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-700 mb-2"><i class="fas fa-info-circle mr-2 text-primary"></i>Thông tin đơn hàng</h3>
                        <p class="text-sm"><span class="text-gray-500">Ngày đặt:</span> <?php echo date('d/m/Y H:i', strtotime($order['NgayDat'])); ?></p>
                        <p class="text-sm mt-1"><span class="text-gray-500">Thanh toán:</span> <?php echo $order['PhuongThucTT'] == 'cod' ? 'COD (Thanh toán khi nhận hàng)' : 'Chuyển khoản'; ?></p>
                        <p class="text-sm mt-1"><span class="text-gray-500">Trạng thái:</span> <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $status_class[$order['TrangThai']]; ?>"><?php echo $status_text[$order['TrangThai']]; ?></span></p>
                    </div>
                </div>

                <!-- Danh sách sản phẩm -->
                <h3 class="font-semibold text-gray-800 mb-3"><i class="fas fa-box-open mr-2 text-primary"></i>Sản phẩm đã đặt</h3>
                <div class="overflow-x-auto border border-gray-200 rounded-xl">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100">
                        
                                <th class="p-3 text-left">Sản phẩm</th>
                                <th class="p-3 text-right">Số lượng</th>
                                <th class="p-3 text-right">Đơn giá</th>
                                <th class="p-3 text-right">Thành tiền</th>
                            </thead>
                        <tbody>
                            <?php 
                            $total = 0;
                            foreach ($details as $item):
                                $subtotal = $item['SoLuong'] * $item['Gia'];
                                $total += $subtotal;
                            ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-3">
                                    <div class="flex items-center gap-3">
                                        <?php if ($item['image_url']): ?>
                                            <img src="../<?php echo $item['image_url']; ?>" class="w-10 h-10 object-cover rounded-lg">
                                        <?php else: ?>
                                            <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center"><i class="fas fa-image text-gray-400"></i></div>
                                        <?php endif; ?>
                                        <span class="font-medium"><?php echo htmlspecialchars($item['TenSP']); ?></span>
                                    </div>
                                  </td>
                                <td class="p-3 text-right"><?php echo $item['SoLuong']; ?></td>
                                <td class="p-3 text-right"><?php echo number_format($item['Gia'], 0, ',', '.'); ?>đ</td>
                                <td class="p-3 text-right font-medium text-indigo-600"><?php echo number_format($subtotal, 0, ',', '.'); ?>đ</td>
                              </tr>
                            <?php endforeach; ?>
                            <tr class="bg-gray-50 font-semibold">
                                <td colspan="3" class="p-3 text-right">Tổng cộng:</td>
                                <td class="p-3 text-right text-indigo-700 text-lg"><?php echo number_format($total, 0, ',', '.'); ?>đ</td>
                              </tr>
                        </tbody>
                      </table>
                </div>

                <!-- Ghi chú -->
                <?php if (!empty($order['GhiChu'])): ?>
                <div class="mt-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                    <p class="text-sm text-yellow-700"><i class="fas fa-sticky-note mr-2"></i><strong>Ghi chú:</strong> <?php echo htmlspecialchars($order['GhiChu']); ?></p>
                </div>
                <?php endif; ?>

                <!-- Nút hành động -->
                <div class="mt-6 flex gap-3 justify-end">
                    <a href="orders.php" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">Quay lại</a>
                </div>
            </div>
        </main>
    </div>

    <script>
        function logout() { 
            if (confirm('Bạn có chắc muốn đăng xuất?')) {
                window.location.href = 'logout.php';
            }
        }
    </script>
</body>
</html>