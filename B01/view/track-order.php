<?php
/**
 * Order Tracking Page - NVBPlay
 * Hiển thị chi tiết đơn hàng từ mã tra cứu
 */
session_start();
require_once '../control/connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// === 1. LẤY MÃ TRA CỨU TỪ URL ===
$tracking_code = $_GET['code'] ?? '';
$error = '';
$order = null;
$order_items = [];
$shipping_address = null;

if (empty($tracking_code)) {
    $error = "Mã tra cứu không hợp lệ";
} else {
    // === 2. TÌM ĐƠN HÀNG THEO linkTraCuu ===
    $stmt = $conn->prepare("SELECT * FROM donhang WHERE linkTraCuu LIKE ?");
    $search_code = "%code=" . $tracking_code;
    $stmt->bind_param("s", $search_code);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        $error = "Không tìm thấy đơn hàng với mã: " . htmlspecialchars($tracking_code);
    } else {
        // === 3. LẤY ĐỊA CHỈ GIAO HÀNG ===
        if ($order['DiaChi_id']) {
            $stmt = $conn->prepare("SELECT * FROM diachigh WHERE add_id = ?");
            $stmt->bind_param("i", $order['DiaChi_id']);
            $stmt->execute();
            $shipping_address = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
        
        // === 4. LẤY CHI TIẾT ĐƠN HÀNG ===
        $stmt = $conn->prepare("
            SELECT ctdh.*, sp.TenSP, sp.image_url 
            FROM chitiethoadon ctdh 
            JOIN sanpham sp ON ctdh.SanPham_id = sp.SanPham_id 
            WHERE ctdh.DonHang_id = ?
        ");
        $stmt->bind_param("i", $order['DonHang_id']);
        $stmt->execute();
        $order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// === 5. HÀM HỖ TRỢ ===
function formatPrice($price) {
    return number_format((float)$price, 0, ',', '.') . '₫';
}

function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}

function getStatusBadge($status) {
    $colors = [
        'Chờ xác nhận' => 'bg-yellow-100 text-yellow-800',
        'Đã xác nhận' => 'bg-blue-100 text-blue-800',
        'Đang giao' => 'bg-purple-100 text-purple-800',
        'Hoàn thành' => 'bg-green-100 text-green-800',
        'Đã hủy' => 'bg-red-100 text-red-800'
    ];
    $color = $colors[$status] ?? 'bg-gray-100 text-gray-800';
    return "<span class='px-3 py-1 rounded-full text-sm font-medium $color'>$status</span>";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theo Dõi Đơn Hàng | NVBPlay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/svg+xml" href="../img/icons/favicon.png">
    <style>
        .timeline-item { position: relative; padding-left: 40px; }
        .timeline-item::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0;
            width: 2px; background: #e5e7eb;
        }
        .timeline-item::after {
            content: ''; position: absolute; left: -4px; top: 0;
            width: 10px; height: 10px; border-radius: 50%;
            background: #e5e7eb;
        }
        .timeline-item.active::after { background: #16a34a; }
        .timeline-item.completed::after { background: #16a34a; }
    </style>
</head>
<body class="bg-gray-50 font-sans">

<!-- Header -->
<header class="bg-white shadow-sm py-3">
    <div class="container mx-auto px-4 flex justify-between items-center">
        <a href="../index.php" class="flex items-center gap-2">
            <img src="../img/icons/logonvb.png" alt="NVBPlay" class="h-10">
        </a>
        <a href="../index.php" class="text-gray-600 hover:text-[#FF3F1A]">
            <i class="fas fa-home mr-2"></i>Về trang chủ
        </a>
    </div>
</header>

<!-- Main Content -->
<main class="container mx-auto px-4 py-8 max-w-4xl">
    
    <?php if ($error): ?>
    <!-- Error State -->
    <div class="bg-white rounded-xl shadow p-8 text-center">
        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-times text-4xl text-red-600"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Không tìm thấy đơn hàng</h1>
        <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($error); ?></p>
        <a href="../index.php" class="inline-block px-6 py-3 bg-[#FF3F1A] text-white rounded-lg hover:bg-red-600">
            Về trang chủ
        </a>
    </div>
    <?php else: ?>
    
    <!-- Success State -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <div class="flex items-center justify-between mb-6 pb-6 border-b">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-box text-[#FF3F1A] mr-2"></i>Chi tiết đơn hàng
                </h1>
                <p class="text-gray-600">Mã đơn: <span class="font-mono font-bold">#<?php echo $order['DonHang_id']; ?></span></p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-500 mb-3">Trạng thái</p>
                <?php echo getStatusBadge($order['TrangThai']); ?>
            </div>
        </div>

        <!-- Order Info Grid -->
        <div class="grid md:grid-cols-2 gap-6 mb-6">
            <!-- Shipping Info -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <i class="fas fa-map-marker-alt text-[#FF3F1A]"></i> Địa chỉ giao hàng
                </h3>
                <?php if ($shipping_address): ?>
                <p class="font-medium"><?php echo htmlspecialchars($shipping_address['Ten_nguoi_nhan']); ?></p>
                <p class="text-gray-600"><?php echo htmlspecialchars($shipping_address['SDT_nhan']); ?></p>
                <p class="text-gray-700 mt-2">
                    <?php 
                    $addr = array_filter([
                        $shipping_address['Duong'],
                        $shipping_address['Quan'],
                        $shipping_address['Tinh_thanhpho'],
                        $shipping_address['Dia_chi_chitiet']
                    ]);
                    echo htmlspecialchars(implode(', ', $addr));
                    ?>
                </p>
                <?php else: ?>
                <p class="text-gray-500">Không có thông tin địa chỉ</p>
                <?php endif; ?>
            </div>

            <!-- Payment & Date Info -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <i class="fas fa-credit-card text-[#FF3F1A]"></i> Thanh toán & Thời gian
                </h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Phương thức:</span>
                        <span class="font-medium">
                            <?php 
                            $methods = [
                                'cod' => 'COD - Thanh toán khi nhận hàng',
                                'banking' => 'Chuyển khoản ngân hàng',
                                'appota' => 'Thanh toán trực tuyến'
                            ];
                            echo $methods[$order['PhuongThucTT']] ?? $order['PhuongThucTT'];
                            ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Ngày đặt:</span>
                        <span class="font-medium"><?php echo formatDate($order['NgayDat']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Mã tra cứu:</span>
                        <span class="font-mono text-xs"><?php echo htmlspecialchars($tracking_code); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <h3 class="font-semibold text-gray-900 mb-3"> Sản phẩm đã đặt</h3>
        <div class="border rounded-lg overflow-hidden mb-6">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Sản phẩm</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-600">SL</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-600">Đơn giá</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-600">Thành tiền</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" 
                                     class="w-12 h-12 object-cover rounded border">
                                <span class="text-sm font-medium"><?php echo htmlspecialchars($item['TenSP']); ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center text-sm"><?php echo $item['SoLuong']; ?></td>
                        <td class="px-4 py-3 text-right text-sm"><?php echo formatPrice($item['Gia']); ?></td>
                        <td class="px-4 py-3 text-right text-sm font-semibold text-[#FF3F1A]">
                            <?php echo formatPrice($item['Gia'] * $item['SoLuong']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="3" class="px-4 py-3 text-right font-medium">Tổng cộng:</td>
                        <td class="px-4 py-3 text-right font-bold text-lg text-[#FF3F1A]">
                            <?php echo formatPrice($order['TongTien']); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Order Status Timeline -->
        <h3 class="font-semibold text-gray-900 mb-3"> Tiến trình đơn hàng</h3>
        <div class="bg-gray-50 rounded-lg p-6 mb-6">
            <div class="timeline-item <?php echo in_array($order['TrangThai'], ['Chờ xác nhận','Đã xác nhận','Đang giao','Hoàn thành']) ? 'completed' : ''; ?>">
                <p class="font-medium text-gray-900">Đơn hàng đã đặt</p>
                <p class="text-sm text-gray-500"><?php echo formatDate($order['NgayDat']); ?></p>
            </div>
            <div class="timeline-item mt-4 <?php echo in_array($order['TrangThai'], ['Đã xác nhận','Đang giao','Hoàn thành']) ? 'completed' : ''; ?>">
                <p class="font-medium text-gray-900">Đã xác nhận</p>
                <p class="text-sm text-gray-500">Chờ xử lý</p>
            </div>
            <div class="timeline-item mt-4 <?php echo in_array($order['TrangThai'], ['Đang giao','Hoàn thành']) ? 'completed' : ''; ?>">
                <p class="font-medium text-gray-900">Đang giao hàng</p>
                <p class="text-sm text-gray-500">Chờ vận chuyển</p>
            </div>
            <div class="timeline-item mt-4 <?php echo $order['TrangThai'] === 'Hoàn thành' ? 'completed' : ''; ?>">
                <p class="font-medium text-gray-900">Hoàn thành</p>
                <p class="text-sm text-gray-500">Chờ giao thành công</p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="../index.php" class="flex-1 py-3 bg-[#FF3F1A] text-white font-semibold rounded-lg text-center hover:bg-red-700 transition">
                Tiếp tục mua sắm
            </a>
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $order['User_id']): ?>
            <a href="./my-account/orders.php" class="flex-1 py-3 bg-gray-100 text-gray-800 font-semibold rounded-lg text-center hover:bg-gray-200 transition">
                Xem tất cả đơn hàng
            </a>
            <?php endif; ?>
            <a href="tel:0987879243" class="flex-1 py-3 bg-gray-100 text-gray-800 font-semibold rounded-lg text-center hover:bg-gray-200 transition">
                <i class="fas fa-phone mr-2"></i>Hỗ trợ: 09XX.XXX.XXX
            </a>
        </div>
    </div>
    <?php endif; ?>

</main>

<!-- Footer -->
<footer class="bg-black text-white py-6 mt-12">
    <div class="container mx-auto px-4 text-center text-sm text-gray-400">
        <p>©2025 CÔNG TY CỔ PHẦN NVB PLAY</p>
        <p class="mt-1">GPĐKKD số 1801779686 do Sở KHĐT TP. Cần Thơ cấp</p>
    </div>
</footer>

</body>
</html>