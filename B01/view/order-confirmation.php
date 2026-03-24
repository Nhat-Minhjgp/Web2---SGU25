<?php
session_start();
require_once '../control/connect.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$order_id = (int) $_GET['order_id'];
$tracking_code = $_GET['code'] ?? '';

$stmt = $conn->prepare("SELECT * FROM donhang WHERE DonHang_id = ? AND User_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header("Location: ../index.php");
    exit();
}

$stmt = $conn->prepare("SELECT ctdh.*, sp.TenSP, sp.image_url FROM chitiethoadon ctdh JOIN sanpham sp ON ctdh.SanPham_id = sp.SanPham_id WHERE ctdh.DonHang_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function formatPrice($price)
{
    return number_format((float) $price, 0, ',', '.') . '₫';
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác Nhận Đơn Hàng | NVBPlay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/svg+xml" href="../img/icons/favicon.png" sizes="32x32">
</head>

<body class="bg-gray-50">
    <main class="container mx-auto px-4 py-8 max-w-2xl">
        <div class="bg-white rounded-xl shadow p-6 text-center">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-check text-4xl text-green-600"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Đặt hàng thành công!</h1>
            <p class="text-gray-600 mb-6">Mã đơn hàng: <span
                    class="font-mono font-bold text-[#FF3F1A]">#<?php echo $order['DonHang_id']; ?></span></p>

            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <p class="text-sm text-gray-600 mb-2">Mã tra cứu</p>
                <code class="text-lg font-mono"><?php echo htmlspecialchars($tracking_code); ?></code>
            </div>

            <div class="space-y-2 mb-6">
                <?php foreach ($order_items as $item): ?>
                    <div class="flex justify-between text-sm">
                        <span><?php echo htmlspecialchars($item['TenSP']); ?> (x<?php echo $item['SoLuong']; ?>)</span>
                        <span class="font-medium"><?php echo formatPrice($item['Gia'] * $item['SoLuong']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="border-t pt-4 mb-6">
                <div class="flex justify-between font-bold text-lg text-[#FF3F1A]">
                    <span>Tổng cộng</span>
                    <span><?php echo formatPrice($order['TongTien']); ?></span>
                </div>
            </div>

            <div class="flex gap-3">
                <a href="../index.php"
                    class="flex-1 py-3 bg-[#FF3F1A] text-white rounded-lg font-semibold hover:bg-red-700">Tiếp tục mua
                    sắm</a>
                <a href="./my-account/orders.php"
                    class="flex-1 py-3 bg-gray-100 text-gray-800 rounded-lg font-semibold hover:bg-gray-200">Xem đơn
                    hàng</a>
            </div>
        </div>
    </main>
</body>

</html>