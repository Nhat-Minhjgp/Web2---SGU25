<?php
session_start();
require_once __DIR__ . '/../control/connect.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php'); exit();
}
$admin_name = $_SESSION['admin_name'] ?? 'Quản trị viên';
$admin_username = $_SESSION['admin_username'] ?? '';
$message = ''; $messageType = '';

// ✅ CHỈ LẤY DANH SÁCH SẢN PHẨM (BỎ DANH SÁCH NCC)
$sanphams = $conn->query("SELECT SanPham_id, TenSP, GiaNhapTB, GiaBan, SoLuongTon, PhanTramLoiNhuan, NCC_id FROM sanpham WHERE TrangThai = 1 ORDER BY TenSP");

// === TỰ ĐỘNG TẠO MÃ PHIẾU NHẬP ===
$ngayHienTai = date('Ymd');
$countQuery = $conn->query("SELECT COUNT(*) as so_luong FROM phieunhap WHERE DATE(NgayNhap) = CURDATE()");
$countRow = $countQuery->fetch_assoc();
$soThuTu = str_pad(($countRow['so_luong'] + 1), 3, '0', STR_PAD_LEFT);
$maPhieuTuDong = 'PN-' . $ngayHienTai . '-' . $soThuTu;

// === TỰ ĐỘNG TẠO MÃ LÔ HÀNG ===
$maLoHangTuDong = 'LOT-' . date('Ymd') . '-' . strtoupper(substr($admin_username ?: $admin_name, 0, 3)) . '-' . $soThuTu;

// Xử lý lưu phiếu nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_import'])) {
    // ✅ ĐÃ BỎ: $ncc_id = intval($_POST['ncc_id'] ?? 0);
    $nguoiNhap = trim($_POST['nguoiNhap'] ?? ($admin_username ?: $admin_name));
    $ngayNhap = $_POST['ngayNhap'] ?? date('Y-m-d');
    $items = $_POST['items'] ?? [];

    if (empty($items)) {
        $message = 'Vui lòng thêm ít nhất 1 sản phẩm vào phiếu nhập!';
        $messageType = 'error';
    } else {
        $conn->begin_transaction();
        try {
            // ✅ 1. Tạo phiếu nhập chính (ĐÃ BỎ CỘT NCC_id)
            $stmt = $conn->prepare("INSERT INTO phieunhap (NguoiNhap, NgayNhap, SoLuong) VALUES (?, ?, 0)");
            $stmt->bind_param("ss", $nguoiNhap, $ngayNhap);
            $stmt->execute();
            $phieuNhap_id = $conn->insert_id;
            $stmt->close();

            $tongSoLuong = 0;

            // ✅ 2. Xử lý từng mặt hàng
            foreach ($items as $item) {
                $sanPham_id = intval($item['sanpham_id'] ?? 0);
                $soLuongNhap = intval($item['so_luong'] ?? 0);
                $giaNhapMoi = floatval($item['gia_nhap'] ?? 0);
                $maLoHang = $maLoHangTuDong;

                if ($soLuongNhap <= 0 || $giaNhapMoi < 0 || !$sanPham_id) continue;

                // === CẬP NHẬT SẢN PHẨM: Tính giá bình quân + giá bán ===
                $stmt = $conn->prepare("SELECT SoLuongTon, GiaNhapTB, PhanTramLoiNhuan FROM sanpham WHERE SanPham_id = ? AND TrangThai = 1");
                $stmt->bind_param("i", $sanPham_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $sp = $res->fetch_assoc();
                $stmt->close();

                if ($sp) {
                    $soLuongTonCu = intval($sp['SoLuongTon']);
                    $giaNhapCu = floatval($sp['GiaNhapTB'] ?? 0);
                    $phanTramLoiNhuan = floatval($sp['PhanTramLoiNhuan'] ?? 20);
                    
                    // 🎯 CÔNG THỨC BÌNH QUÂN GIA QUYỀN:
                    $tongSoLuongMoi = $soLuongTonCu + $soLuongNhap;
                    $giaNhapMoiTB = $tongSoLuongMoi > 0 
                        ? ($soLuongTonCu * $giaNhapCu + $soLuongNhap * $giaNhapMoi) / $tongSoLuongMoi 
                        : $giaNhapMoi;

                    // 🎯 CÔNG THỨC TÍNH GIÁ BÁN:
                    $giaBanMoi = $giaNhapMoiTB * (1 + $phanTramLoiNhuan / 100);

                    // Cập nhật sản phẩm với giá nhập TB mới + giá bán tính tự động
                    $stmt = $conn->prepare("UPDATE sanpham SET SoLuongTon = ?, GiaNhapTB = ?, GiaBan = ? WHERE SanPham_id = ?");
                    $stmt->bind_param("iddi", $tongSoLuongMoi, $giaNhapMoiTB, $giaBanMoi, $sanPham_id);
                    $stmt->execute();
                    $stmt->close();
                }

                // ✅ 3. Lưu chi tiết phiếu nhập
                $stmt = $conn->prepare("INSERT INTO chitietphieunhap (PhieuNhap_id, SanPham_id, SoLuong, Gia_Nhap, MaLoHang) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiids", $phieuNhap_id, $sanPham_id, $soLuongNhap, $giaNhapMoi, $maLoHang);
                $stmt->execute();
                $stmt->close();

                $tongSoLuong += $soLuongNhap;

                // ✅ 4. Ghi log vào tracuutonkho
                if ($soLuongNhap > 0) {
                    $stmt = $conn->prepare("INSERT INTO tracuutonkho (SP_id, TrangThai_NhapXuat, SoLuong, MaThamChieu_NhapXuat, ThoiGianTraCuu) VALUES (?, 'NHAP', ?, ?, NOW())");
                    $stmt->bind_param("iii", $sanPham_id, $soLuongNhap, $phieuNhap_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            // ✅ 5. Cập nhật tổng số lượng cho phiếu nhập
            $stmt = $conn->prepare("UPDATE phieunhap SET SoLuong = ? WHERE NhapHang_id = ?");
            $stmt->bind_param("ii", $tongSoLuong, $phieuNhap_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $message = '✅ Nhập hàng thành công! Giá bán đã được cập nhật tự động.';
            $messageType = 'success';
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
</style>
</head>
<body class="bg-gray-50 font-sans text-gray-800">
<!-- HEADER -->
<header class="bg-white shadow-md sticky top-0 z-50 h-[70px] flex items-center w-full">
    <div class="w-full px-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-custom">NVBPlay Admin Panel</h1>
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gradient-custom flex items-center justify-center text-white font-bold"><?php echo strtoupper(substr($admin_name, 0, 1)); ?></div>
                <div><p class="text-sm font-semibold"><?php echo htmlspecialchars($admin_name); ?> <span class="text-xs bg-gradient-custom text-white px-2 py-0.5 rounded">Admin</span></p><p class="text-xs text-gray-500"><?php echo htmlspecialchars($admin_username); ?></p></div>
            </div>
            <button onclick="logout()" class="text-red-500 hover:text-red-700 transition"><i class="fas fa-sign-out-alt"></i> Đăng xuất</button>
        </div>
    </div>
</header>

<div class="flex w-full min-h-[calc(100vh-70px)]">
    <!-- SIDEBAR -->
    <aside class="w-64 bg-white shadow-lg min-h-screen">
            <div class="p-4 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Danh mục chức năng</h3>
            </div>
            <nav class="p-2">
                <a href="dashboard.php" class="menu-btn flex items-center space-x-3 px-4 py-3 rounded-lg mb-1 transition duration-200 active">
                    <i class="fas fa-home w-5 text-gray-500"></i>
                    <span>Dashboard</span>
                </a>
                <a href="users.php" class="menu-btn flex items-center space-x-3 px-4 py-3 rounded-lg mb-1 transition duration-200 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-users w-5 text-gray-500"></i>
                    <span>Quản lý người dùng</span>
                </a>
                                
                <a href="product.php" class="menu-btn flex items-center space-x-3 px-4 py-3 rounded-lg mb-1 transition duration-200 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-box w-5 text-gray-500"></i>
                    <span>Quản lý sản phẩm</span>
                </a>
                <a href="import.php" class="menu-btn flex items-center space-x-3 px-4 py-3 rounded-lg mb-1 transition duration-200 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-arrow-down w-5 text-gray-500"></i>
                    <span>Quản lý nhập hàng</span>
                </a>
                <a href="orders.php" class="menu-btn flex items-center space-x-3 px-4 py-3 rounded-lg mb-1 transition duration-200 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-receipt w-5 text-gray-500"></i>
                    <span>Quản lý đơn hàng</span>
                </a>
                <a href="inventory.php" class="menu-btn flex items-center space-x-3 px-4 py-3 rounded-lg mb-1 transition duration-200 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-warehouse w-5 text-gray-500"></i>
                    <span>Tồn kho & Báo cáo</span>
                </a>
            </nav>
        </aside>

    <!-- MAIN -->
    <main class="flex-1 p-6 lg:p-8 bg-gray-50">
        <form method="POST" id="importForm" class="bg-white rounded-xl shadow-lg p-6 lg:p-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 pb-6 border-b gap-4">
                <h2 class="text-2xl font-bold text-gray-800"><i class="fas fa-plus-circle text-primary"></i> Tạo phiếu nhập mới</h2>
                <a href="import.php" class="text-gray-600 hover:text-primary"><i class="fas fa-arrow-left"></i> Quay lại</a>
            </div>

            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType==='success'?'bg-green-50 text-green-700 border-green-200':'bg-red-50 text-red-700 border-red-200'; ?> border flex items-center gap-3 animate-slide-in">
                <i class="fas fa-<?php echo $messageType==='success'?'check-circle':'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <!-- Thông tin phiếu -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 p-4 bg-gray-50 rounded-lg">
                <!-- ✅ ĐÃ XÓA: Ô Nhà cung cấp -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Mã phiếu nhập</label>
                    <input type="text" value="<?php echo htmlspecialchars($maPhieuTuDong); ?>" readonly class="w-full px-4 py-2.5 rounded-lg border bg-gray-100 text-gray-500 cursor-not-allowed font-mono text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Người nhập</label>
                    <input type="text" value="<?php echo htmlspecialchars($admin_username ?: $admin_name); ?>" readonly class="w-full px-4 py-2.5 rounded-lg border bg-gray-100 text-gray-500 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Ngày nhập *</label>
                    <input type="date" name="ngayNhap" value="<?php echo date('Y-m-d'); ?>" required class="w-full px-4 py-2.5 rounded-lg border focus:outline-none focus:border-primary">
                </div>
            </div>

            <!-- Danh sách mặt hàng -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">📦 Danh sách mặt hàng nhập</h3>
                    <button type="button" onclick="addItemRow()" class="bg-blue-100 text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-200 transition text-sm font-medium">
                        <i class="fas fa-plus"></i> Thêm sản phẩm
                    </button>
                </div>
                <div id="itemsContainer" class="space-y-4">
                    <!-- Item rows will be added here by JS -->
                </div>
            </div>

            <!-- Tổng kết -->
            <div class="border-t pt-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="text-gray-600">
                    <p>Tổng số lượng: <span id="totalQty" class="font-bold text-gray-800">0</span> sản phẩm</p>
                    <p>Tổng giá trị: <span id="totalValue" class="font-bold text-primary text-lg">0</span>đ</p>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="history.back()" class="px-6 py-2.5 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 transition">Hủy</button>
                    <button type="submit" name="save_import" class="px-6 py-2.5 rounded-lg bg-gradient-custom text-white font-medium hover:opacity-90 transition shadow-lg">
                        <i class="fas fa-save"></i> Lưu phiếu nhập
                    </button>
                </div>
            </div>
        </form>
    </main>
</div>

<!-- Template CHỈ cho sản phẩm có sẵn -->
<template id="itemTemplate">
    <div class="item-card border rounded-xl p-4 bg-white animate-slide-in">
        <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-gray-800">📦 Sản phẩm</h4>
            <button type="button" onclick="removeItem(this)" class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Chọn sản phẩm *</label>
                <select name="items[][sanpham_id]" class="product-select w-full px-3 py-2 rounded-lg border text-sm" required onchange="onProductSelect(this)">
                    <option value="">-- Chọn sản phẩm --</option>
                    <?php $sanphams->data_seek(0); while($sp = $sanphams->fetch_assoc()): ?>
                    <option value="<?php echo $sp['SanPham_id']; ?>" 
                            data-gianhap="<?php echo $sp['GiaNhapTB'] ?? 0; ?>" 
                            data-giaban="<?php echo $sp['GiaBan'] ?? 0; ?>" 
                            data-ton="<?php echo $sp['SoLuongTon']; ?>"
                            data-loi="<?php echo $sp['PhanTramLoiNhuan'] ?? 20; ?>">
                        <?php echo htmlspecialchars($sp['TenSP']); ?> 
                        (Tồn: <?php echo $sp['SoLuongTon']; ?> | %Lợi: <?php echo $sp['PhanTramLoiNhuan'] ?? 20; ?>%)
                    </option>
                    <?php endwhile; ?>
                </select>
                <p class="text-xs text-gray-500 mt-1">💡 Giá bán sẽ được tính tự động khi lưu</p>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Số lượng nhập *</label>
                <input type="number" name="items[][so_luong]" class="qty-input w-full px-3 py-2 rounded-lg border text-sm" min="1" value="1" oninput="calcItemTotal(this)">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Giá nhập mới (VNĐ) *</label>
                <input type="number" name="items[][gia_nhap]" class="price-input w-full px-3 py-2 rounded-lg border text-sm" min="0" step="0.01" value="0" oninput="calcItemTotal(this); calcSellingPrice(this)">
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
                <p class="text-xs text-gray-500">🎯 Giá bán = Giá nhập TB × (1 + %Lợi)</p>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Mã lô hàng</label>
                <input type="text" name="items[][ma_lo_hang]" class="w-full px-3 py-2 rounded-lg border text-sm bg-gray-100 text-gray-500 cursor-not-allowed font-mono" readonly value="<?php echo htmlspecialchars($maLoHangTuDong); ?>">
            </div>
            <div class="flex items-end">
                <p class="text-sm text-gray-600">Thành tiền: <span class="item-total font-bold text-primary">0</span>đ</p>
            </div>
        </div>
    </div>
</template>

<script>
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
}

function addItemRow() {
    const container = document.getElementById('itemsContainer');
    const template = document.getElementById('itemTemplate');
    const clone = template.content.cloneNode(true);
    container.appendChild(clone);
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
    const profit = parseFloat(card.querySelector('.profit-display').dataset.value) || 20;
    const sellingPrice = price * (1 + profit / 100);
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
            card.querySelector('.profit-display').value = profit + '%';
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
    if(confirm('Bạn có chắc chắn muốn đăng xuất?')) {
        window.location.href = 'logout.php';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    calcTotal();
});
</script>
</body>
</html>