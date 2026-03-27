<?php
// track.php - Trang tra cứu đơn hàng cho khách hàng
require_once __DIR__ . '/control/connect.php';

$order_code = isset($_GET['code']) ? $_GET['code'] : '';
$order = null;
$order_details = [];

if ($order_code) {
    // Lấy mã đơn từ code (ORD00001 -> lấy số 1)
    $order_id = intval(str_replace('ORD', '', $order_code));
    
    if ($order_id > 0) {
        // Lấy thông tin đơn hàng
        $sql = "SELECT d.*, u.Ho_ten as customer_name, u.SDT as customer_phone,
                dc.Ten_nguoi_nhan, dc.SDT_nhan, dc.Duong, dc.Quan, dc.Tinh_thanhpho, dc.Dia_chi_chitiet
                FROM donhang d
                LEFT JOIN users u ON d.User_id = u.User_id
                LEFT JOIN diachigh dc ON d.DiaChi_id = dc.add_id
                WHERE d.DonHang_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        
        if ($order) {
            // Lấy chi tiết sản phẩm
            $sql_detail = "SELECT c.*, s.TenSP, s.image_url 
                           FROM chitiethoadon c
                           LEFT JOIN sanpham s ON c.SanPham_id = s.SanPham_id
                           WHERE c.DonHang_id = ?";
            $stmt = $conn->prepare($sql_detail);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $order_details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tra cứu đơn hàng - NVBPlay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">

    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Tra cứu đơn hàng</h1>
            <p class="text-gray-500 mt-2">Nhập mã đơn hàng để kiểm tra trạng thái</p>
        </div>

        <!-- Form tra cứu -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <form method="GET" class="flex gap-4">
                <input type="text" name="code" placeholder="Nhập mã đơn hàng (VD: ORD00001)" 
                       value="<?php echo htmlspecialchars($order_code); ?>"
                       class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:opacity-90 transition">
                    <i class="fas fa-search mr-2"></i>Tra cứu
                </button>
            </form>
        </div>

        <?php if ($order_code && $order): ?>
            <!-- Thông tin đơn hàng -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
                    <h2 class="text-xl font-semibold text-white">Đơn hàng #<?php echo $order_code; ?></h2>
                </div>
                
                <div class="p-6">
                    <!-- Trạng thái đơn hàng -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Trạng thái hiện tại:</span>
                            <?php
                            $status = intval($order['TrangThai']);
                            $status_color = $status == 0 ? 'bg-yellow-100 text-yellow-700' : ($status == 1 ? 'bg-blue-100 text-blue-700' : ($status == 2 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'));
                            $status_text = $status == 0 ? '⏳ Chờ xử lý' : ($status == 1 ? '✓ Đã xác nhận' : ($status == 2 ? '✅ Đã giao' : '✗ Đã hủy'));
                            ?>
                            <span class="px-4 py-2 rounded-full text-sm font-semibold <?php echo $status_color; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Thông tin khách hàng -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-2">Thông tin khách hàng</h3>
                            <p><span class="text-gray-500">Họ tên:</span> <?php echo htmlspecialchars($order['customer_name'] ?? $order['Ten_nguoi_nhan'] ?? 'Khách lẻ'); ?></p>
                            <p><span class="text-gray-500">SĐT:</span> <?php echo htmlspecialchars($order['customer_phone'] ?? $order['SDT_nhan'] ?? ''); ?></p>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-2">Địa chỉ giao hàng</h3>
                            <p><?php 
                                $address = [];
                                if ($order['Duong']) $address[] = $order['Duong'];
                                if ($order['Dia_chi_chitiet']) $address[] = $order['Dia_chi_chitiet'];
                                if ($order['Quan']) $address[] = $order['Quan'];
                                if ($order['Tinh_thanhpho']) $address[] = $order['Tinh_thanhpho'];
                                echo htmlspecialchars(implode(', ', $address));
                            ?></p>
                        </div>
                    </div>
                    
                    <!-- Danh sách sản phẩm -->
                    <h3 class="font-semibold text-gray-700 mb-3">Sản phẩm đã đặt</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-3 text-left">Sản phẩm</th>
                                    <th class="p-3 text-right">SL</th>
                                    <th class="p-3 text-right">Đơn giá</th>
                                    <th class="p-3 text-right">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total = 0;
                                foreach ($order_details as $item): 
                                    $subtotal = $item['SoLuong'] * $item['Gia'];
                                    $total += $subtotal;
                                ?>
                                <tr class="border-b">
                                    <td class="p-3">
                                        <div class="flex items-center gap-3">
                                            <?php if ($item['image_url']): ?>
                                                <img src="uploads/<?php echo $item['image_url']; ?>" class="w-10 h-10 object-cover rounded">
                                            <?php else: ?>
                                                <div class="w-10 h-10 bg-gray-100 rounded flex items-center justify-center">
                                                    <i class="fas fa-image text-gray-400"></i>
                                                </div>
                                            <?php endif; ?>
                                            <span><?php echo htmlspecialchars($item['TenSP']); ?></span>
                                        </div>
                                     </td>
                                    <td class="p-3 text-right"><?php echo $item['SoLuong']; ?></td>
                                    <td class="p-3 text-right"><?php echo number_format($item['Gia'], 0, ',', '.'); ?>đ</td>
                                    <td class="p-3 text-right"><?php echo number_format($subtotal, 0, ',', '.'); ?>đ</td>
                                 </tr>
                                <?php endforeach; ?>
                                <tr class="bg-gray-50 font-semibold">
                                    <td colspan="3" class="p-3 text-right">Tổng cộng:</td>
                                    <td class="p-3 text-right text-indigo-600"><?php echo number_format($total, 0, ',', '.'); ?>đ</td>
                                 </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-6 text-center text-gray-500 text-sm">
                        <p>Mọi thắc mắc vui lòng liên hệ hotline: <strong>0987.879.243</strong></p>
                    </div>
                </div>
            </div>
        <?php elseif ($order_code): ?>
            <!-- Không tìm thấy đơn hàng -->
            <div class="bg-white rounded-xl shadow-md p-8 text-center">
                <i class="fas fa-search text-5xl text-gray-300 mb-4 block"></i>
                <p class="text-gray-500">Không tìm thấy đơn hàng với mã <strong><?php echo htmlspecialchars($order_code); ?></strong></p>
                <p class="text-gray-400 text-sm mt-2">Vui lòng kiểm tra lại mã đơn hàng</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>