<?php
session_start();
require_once __DIR__ . '/../control/connect.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php'); 
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Quản trị viên';
$admin_role = $_SESSION['admin_role'] ?? '';
$admin_username = $_SESSION['admin_username'] ?? '';
$message = ''; 
$messageType = '';

// Lấy danh sách sản phẩm
$sanphams = $conn->query("SELECT SanPham_id, TenSP, GiaNhapTB, GiaBan, SoLuongTon, PhanTramLoiNhuan, NCC_id FROM sanpham WHERE TrangThai = 1 OR Trangthai = 0 ORDER BY TenSP");

// Tự động tạo mã phiếu nhập
$ngayHienTai = date('Ymd');
$countQuery = $conn->query("SELECT COUNT(*) as so_luong FROM phieunhap WHERE DATE(NgayNhap) = CURDATE()");
$countRow = $countQuery->fetch_assoc();
$soThuTu = str_pad(($countRow['so_luong'] + 1), 3, '0', STR_PAD_LEFT);
$maPhieuTuDong = 'PN-' . $ngayHienTai . '-' . $soThuTu;

// Tự động tạo mã lô hàng
$maLoHangTuDong = 'LOT-' . date('Ymd') . '-' . strtoupper(substr($admin_username ?: $admin_name, 0, 3)) . '-' . $soThuTu;

// XỬ LÝ LƯU PHIẾU NHẬP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_import'])) {
    $nguoiNhap = trim($_POST['nguoiNhap'] ?? ($admin_username ?: $admin_name));
    $ngayNhap = $_POST['ngayNhap'] ?? date('Y-m-d');
    $items = $_POST['items'] ?? [];

    if (empty($items)) {
        $message = 'Vui lòng thêm ít nhất 1 sản phẩm vào phiếu nhập!';
        $messageType = 'error';
    } else {
        $conn->begin_transaction();
        try {
            // 1. Tạo phiếu nhập chính
            $stmt = $conn->prepare("INSERT INTO phieunhap (NguoiNhap, NgayNhap, SoLuong) VALUES (?, ?, 0)");
            $stmt->bind_param("ss", $nguoiNhap, $ngayNhap);
            $stmt->execute();
            $phieuNhap_id = $conn->insert_id;
            $stmt->close();

            // 2. Xử lý từng mặt hàng
            foreach ($items as $item) {
                $sanPham_id = intval($item['sanpham_id'] ?? 0);
                $soLuongNhap = intval($item['so_luong'] ?? 0);
                $giaNhapMoi = floatval($item['gia_nhap'] ?? 0);
                $maLoHang = $item['ma_lo_hang'] ?? $maLoHangTuDong;

                if ($soLuongNhap <= 0 || $giaNhapMoi < 0 || !$sanPham_id) continue;

                // Lưu chi tiết phiếu nhập
                $stmt = $conn->prepare("INSERT INTO chitietphieunhap (PhieuNhap_id, SanPham_id, SoLuong, Gia_Nhap, MaLoHang) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiids", $phieuNhap_id, $sanPham_id, $soLuongNhap, $giaNhapMoi, $maLoHang);
                $stmt->execute();
                $stmt->close();

                // Ghi log vào tracuutonkho
                $stmt = $conn->prepare("INSERT INTO tracuutonkho (SP_id, TrangThai_NhapXuat, SoLuong, MaThamChieu_NhapXuat, ThoiGianTraCuu) VALUES (?, 'NHAP', ?, ?, NOW())");
                $stmt->bind_param("iii", $sanPham_id, $soLuongNhap, $phieuNhap_id);
                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();
            $message = '✅ Nhập hàng thành công! Hệ thống đã tự động cập nhật giá và tồn kho.';
            $messageType = 'success';
            
            // Chuyển hướng để tránh submit lại form
            header("Location: import.php?success=1");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = '❌ Lỗi: ' . $e->getMessage();
            $messageType = 'error';
            error_log("Import Error: " . $e->getMessage());
        }
    }
}

// Bắt thông báo thành công từ URL
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = '✅ Nhập hàng thành công! Hệ thống đã tự động cập nhật giá và tồn kho.';
    $messageType = 'success';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tạo phiếu nhập - Admin</title>
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
@keyframes slideIn { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.animate-slide-in { animation: slideIn 0.3s ease-out; }
.item-card { transition: all 0.2s; }
.item-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }

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
            <i class="fas fa-home w-5 text-center"></i> Dashboard
        </a>
        <a href="users.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
            <i class="fas fa-users w-5 text-center"></i> Quản lý người dùng
        </a>
        <a href="product.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
            <i class="fas fa-box w-5 text-center"></i> Quản lý sản phẩm
        </a>
        <a href="import.php" class="flex items-center gap-3 px-4 py-3 bg-gradient-custom text-white rounded-lg shadow-md transition transform hover:-translate-y-0.5">
            <i class="fas fa-arrow-down w-5 text-center"></i> Quản lý nhập hàng
        </a>
        <a href="price.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
            <i class="fas fa-tag w-5 text-center"></i> Quản lý giá bán
        </a>
        <a href="orders.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
            <i class="fas fa-receipt w-5 text-center"></i> Quản lý đơn hàng
        </a>
        <a href="inventory.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
            <i class="fas fa-warehouse w-5 text-center"></i> Tồn kho & Báo cáo
        </a>
    </nav>
</aside>
        <main class="flex-1 p-6 lg:p-8 overflow-x-hidden bg-gray-50">
            <form method="POST" id="importForm" class="bg-white rounded-xl shadow-lg p-6 lg:p-8">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 pb-6 border-b gap-4">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="fas fa-plus-circle text-primary"></i> Tạo phiếu nhập mới
                    </h2>
                    <a href="import.php" class="text-gray-600 hover:text-primary transition flex items-center gap-1">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>

                <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'; ?> border flex items-center gap-3 animate-slide-in">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 p-4 bg-gray-50 rounded-lg">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Mã phiếu nhập</label>
                        <input type="text" value="<?php echo htmlspecialchars($maPhieuTuDong); ?>" readonly class="w-full px-4 py-2.5 rounded-lg border bg-gray-100 text-gray-500 cursor-not-allowed font-mono text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Người nhập</label>
                        <input type="text" value="<?php echo htmlspecialchars($admin_username ?: $admin_name); ?>" readonly class="w-full px-4 py-2.5 rounded-lg border bg-gray-100 text-gray-500 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Ngày nhập <span class="text-red-500">*</span></label>
                        <input type="date" name="ngayNhap" value="<?php echo date('Y-m-d'); ?>" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                    </div>
                </div>

                <div class="mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">📦 Danh sách mặt hàng nhập</h3>
                        <button type="button" onclick="addItemRow()" class="bg-blue-100 text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-200 transition text-sm font-medium">
                            <i class="fas fa-plus"></i> Thêm sản phẩm
                        </button>
                    </div>
                    <div id="itemsContainer" class="space-y-4">
                    </div>
                </div>

                <div class="border-t pt-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-gray-600">
                        <p>Tổng số lượng: <span id="totalQty" class="font-bold text-gray-800">0</span> sản phẩm</p>
                        <p>Tổng giá trị: <span id="totalValue" class="font-bold text-primary text-lg">0</span>đ</p>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="history.back()" class="px-6 py-2.5 rounded-lg bg-gray-500 text-white hover:bg-gray-600 transition font-medium">
                            <i class="fas fa-times mr-2"></i> Hủy
                        </button>
                        <button type="submit" name="save_import" class="px-6 py-2.5 rounded-lg bg-gradient-custom text-white font-medium hover:opacity-90 transition shadow-lg">
                            <i class="fas fa-save mr-2"></i> Lưu phiếu nhập
                        </button>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <template id="itemTemplate">
        <div class="item-card border rounded-xl p-4 bg-white animate-slide-in">
            <div class="flex justify-between items-start mb-3">
                <h4 class="font-semibold text-gray-800">📦 Sản phẩm</h4>
                <button type="button" onclick="removeItem(this)" class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div class="lg:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Chọn sản phẩm <span class="text-red-500">*</span></label>
                    <select name="items[INDEX][sanpham_id]" class="product-select w-full px-3 py-2 rounded-lg border text-sm" required onchange="onProductSelect(this)">
                        <option value="">-- Chọn sản phẩm --</option>
                        <?php $sanphams->data_seek(0); while($sp = $sanphams->fetch_assoc()): ?>
                        <option value="<?php echo $sp['SanPham_id']; ?>" 
                                data-gianhap="<?php echo $sp['GiaNhapTB'] ?? 0; ?>" 
                                data-giaban="<?php echo $sp['GiaBan'] ?? 0; ?>" 
                                data-ton="<?php echo $sp['SoLuongTon']; ?>"
                                data-loi="<?php echo $sp['PhanTramLoiNhuan'] ?? 0.2; ?>">
                            <?php echo htmlspecialchars($sp['TenSP']); ?> 
                            (Tồn: <?php echo $sp['SoLuongTon']; ?> | %Lợi: <?php echo ($sp['PhanTramLoiNhuan'] * 100) ?? 20; ?>%)
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">💡 Giá bán sẽ được tính tự động khi lưu</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Số lượng nhập <span class="text-red-500">*</span></label>
                    <input type="number" name="items[INDEX][so_luong]" class="qty-input w-full px-3 py-2 rounded-lg border text-sm" min="1" value="1" oninput="calcItemTotal(this)">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Giá nhập mới (VNĐ) <span class="text-red-500">*</span></label>
                    <input type="number" name="items[INDEX][gia_nhap]" class="price-input w-full px-3 py-2 rounded-lg border text-sm" min="0" step="0.01" value="0" oninput="calcItemTotal(this); calcSellingPrice(this)">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 p-3 bg-blue-50 rounded-lg">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">% Lợi nhuận (từ sản phẩm)</label>
                    <input type="text" class="profit-display w-full px-3 py-2 rounded-lg border text-sm bg-gray-100" readonly value="20%">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Giá bán dự kiến</label>
                    <input type="text" class="selling-price w-full px-3 py-2 rounded-lg border text-sm bg-gray-100" readonly value="0đ">
                </div>
                <div class="flex items-end">
                    <p class="text-xs text-gray-500">🎯 Giá bán sẽ do hệ thống SQL tự tính toán</p>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Mã lô hàng</label>
                    <input type="text" name="items[INDEX][ma_lo_hang]" class="w-full px-3 py-2 rounded-lg border text-sm bg-gray-100 text-gray-500 cursor-not-allowed font-mono" readonly value="<?php echo htmlspecialchars($maLoHangTuDong); ?>">
                </div>
                <div class="flex items-end">
                    <p class="text-sm text-gray-600">Thành tiền: <span class="item-total font-bold text-primary">0</span>đ</p>
                </div>
            </div>
        </div>
    </template>

    <script>
        let itemIndex = 0;

        function formatCurrency(amount) {
            return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
        }

        function addItemRow() {
            const container = document.getElementById('itemsContainer');
            const template = document.getElementById('itemTemplate');
            const clone = template.content.cloneNode(true);

            const selects = clone.querySelectorAll('select[name*="INDEX"]');
            const inputs = clone.querySelectorAll('input[name*="INDEX"]');
            
            selects.forEach(el => el.name = el.name.replace('INDEX', itemIndex));
            inputs.forEach(el => el.name = el.name.replace('INDEX', itemIndex));

            container.appendChild(clone);
            itemIndex++;
            calcTotal();
        }

        function removeItem(btn) {
            const card = btn.closest('.item-card');
            card.remove();
            calcTotal();
        }

        function calcItemTotal(input) {
            const card = input.closest('.item-card');
            const qty = parseFloat(card.querySelector('.qty-input').value) || 0;
            const price = parseFloat(card.querySelector('.price-input').value) || 0;
            const total = qty * price;
            card.querySelector('.item-total').textContent = new Intl.NumberFormat('vi-VN').format(total);
            calcTotal();
        }

        function calcSellingPrice(input) {
            const card = input.closest('.item-card');
            const price = parseFloat(card.querySelector('.price-input').value) || 0;
            const profitStr = card.querySelector('.profit-display').dataset.value || 0.2;
            const profit = parseFloat(profitStr);
            
            const sellingPrice = price * (1 + profit);
            card.querySelector('.selling-price').value = formatCurrency(sellingPrice);
        }

        function onProductSelect(select) {
            const card = select.closest('.item-card');
            const option = select.options[select.selectedIndex];
            if (option.value) {
                const giaNhap = option.getAttribute('data-gianhap');
                const profit = option.getAttribute('data-loi');
                
                if(giaNhap && giaNhap !== '0') card.querySelector('.price-input').value = giaNhap;
                
                if(profit) {
                    const displayProfit = (parseFloat(profit) * 100).toFixed(0) + '%';
                    card.querySelector('.profit-display').value = displayProfit;
                    card.querySelector('.profit-display').dataset.value = profit;
                }
                
                calcItemTotal(card.querySelector('.qty-input'));
                calcSellingPrice(card.querySelector('.price-input'));
            }
        }

        function calcTotal() {
            let totalQty = 0;
            let totalValue = 0;
            document.querySelectorAll('.item-card').forEach(card => {
                const qty = parseFloat(card.querySelector('.qty-input').value) || 0;
                const price = parseFloat(card.querySelector('.price-input').value) || 0;
                totalQty += qty;
                totalValue += qty * price;
            });
            document.getElementById('totalQty').textContent = totalQty;
            document.getElementById('totalValue').textContent = formatCurrency(totalValue);
        }

        function logout() {
            if(confirm('Bạn có chắc muốn đăng xuất?')) {
                window.location.href = 'logout.php';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            addItemRow();
        });
    </script>
</body>
</html>