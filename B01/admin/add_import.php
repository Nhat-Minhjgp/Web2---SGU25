<?php
session_start();
require_once __DIR__ . '/../control/connect.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php'); exit();
}
$admin_name = $_SESSION['admin_name'] ?? 'Quản trị viên';
$admin_username = $_SESSION['admin_username'] ?? '';
$message = ''; $messageType = '';

// Lấy danh sách NCC và sản phẩm có sẵn
$nhacungcaps = $conn->query("SELECT NCC_id, Ten_NCC FROM nhacungcap ORDER BY Ten_NCC");
$sanphams = $conn->query("SELECT SanPham_id, TenSP, GiaNhapTB, GiaBan, SoLuongTon FROM sanpham WHERE TrangThai = 1 ORDER BY TenSP");

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
    $ncc_id = intval($_POST['ncc_id'] ?? 0);
    $maPhieu = trim($_POST['NhapHang_id'] ?? $maPhieuTuDong);
    $nguoiNhap = trim($_POST['nguoiNhap'] ?? ($admin_username ?: $admin_name));
    $ngayNhap = $_POST['ngayNhap'] ?? date('Y-m-d');
    $items = $_POST['items'] ?? [];

    // Xử lý upload hình ảnh
    $uploadedImages = [];
    if (!empty($_FILES['item_images']['name'][0])) {
        $uploadDir = __DIR__ . '/../uploads/products/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        foreach ($_FILES['item_images']['name'] as $key => $name) {
            if ($_FILES['item_images']['error'][$key] === 0) {
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $newName = 'IMG-' . date('YmdHis') . '-' . $key . '.' . $ext;
                if (move_uploaded_file($_FILES['item_images']['tmp_name'][$key], $uploadDir . $newName)) {
                    $uploadedImages[$key] = 'uploads/products/' . $newName;
                }
            }
        }
    }

    if (empty($items)) {
        $message = 'Vui lòng thêm ít nhất 1 sản phẩm vào phiếu nhập!';
        $messageType = 'error';
    } else {
        $conn->begin_transaction();
        try {
            // 1. Tạo phiếu nhập chính
            $stmt = $conn->prepare("INSERT INTO phieunhap (NhapHang_id, NCC_id, NguoiNhap, NgayNhap, SoLuong) VALUES (?, ?, ?, ?, 0)");
            $stmt->bind_param("siss", $maPhieu, $ncc_id, $nguoiNhap, $ngayNhap);
            $stmt->execute();
            $phieuNhap_id = $conn->insert_id;
            $stmt->close();

            $tongSoLuong = 0; $tongGiaTri = 0;

            // 2. Xử lý từng mặt hàng
            foreach ($items as $index => $item) {
                $isNewProduct = ($item['is_new'] ?? '0') === '1';
                $soLuongNhap = intval($item['so_luong'] ?? 0);
                $giaNhapMoi = floatval($item['gia_nhap'] ?? 0);
                $phanTramLoiNhuan = floatval($item['phan_tram_loi'] ?? 20);
                $maLoHang = $maLoHangTuDong; // Tự động dùng mã lô hàng tự động

                if ($soLuongNhap <= 0 || $giaNhapMoi < 0) continue;

                if ($isNewProduct) {
                    // === TỰ ĐỘNG TẠO SẢN PHẨM MỚI ===
                    $tenSP = trim($item['ten_sp_moi'] ?? '');
                    $danhmuc_id = intval($item['danhmuc_id'] ?? 0);
                    $ma_thuonghieu = intval($item['thuonghieu_id'] ?? 0);
                    $mota = trim($item['mota_moi'] ?? '');
                    $imageUrl = $uploadedImages[$index] ?? '';
                    
                    $giaBan = $giaNhapMoi * (1 + $phanTramLoiNhuan / 100);

                    $stmt = $conn->prepare("INSERT INTO sanpham (TenSP, Danhmuc_id, NCC_id, Ma_thuonghieu, MoTa, image_url, GiaNhapTB, GiaBan, PhanTramLoiNhuan, TrangThai, SoLuongTon, TaoNgay) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())");
                    $stmt->bind_param("siisssdddi", $tenSP, $danhmuc_id, $ncc_id, $ma_thuonghieu, $mota, $imageUrl, $giaNhapMoi, $giaBan, $phanTramLoiNhuan, $soLuongNhap);
                    $stmt->execute();
                    $sanPham_id = $conn->insert_id;
                    $stmt->close();
                } else {
                    // === CẬP NHẬT SẢN PHẨM CÓ SẴN ===
                    $sanPham_id = intval($item['sanpham_id'] ?? 0);
                    $stmt = $conn->prepare("SELECT SoLuongTon, GiaNhapTB FROM sanpham WHERE SanPham_id = ?");
                    $stmt->bind_param("i", $sanPham_id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $sp = $res->fetch_assoc();
                    $stmt->close();

                    if ($sp) {
                        $soLuongTonCu = intval($sp['SoLuongTon']);
                        $giaNhapCu = floatval($sp['GiaNhapTB']);
                        
                        $tongSoLuongMoi = $soLuongTonCu + $soLuongNhap;
                        $giaNhapMoiTB = ($soLuongTonCu * $giaNhapCu + $soLuongNhap * $giaNhapMoi) / $tongSoLuongMoi;

                        $stmt = $conn->prepare("UPDATE sanpham SET SoLuongTon = ?, GiaNhapTB = ? WHERE SanPham_id = ?");
                        $stmt->bind_param("idi", $tongSoLuongMoi, $giaNhapMoiTB, $sanPham_id);
                        $stmt->execute();
                        $stmt->close();

                        if (isset($item['cap_nhat_gia_ban']) && $item['cap_nhat_gia_ban'] === '1') {
                            $giaBanMoi = $giaNhapMoiTB * (1 + $phanTramLoiNhuan / 100);
                            $stmt = $conn->prepare("UPDATE sanpham SET PhanTramLoiNhuan = ?, GiaBan = ? WHERE SanPham_id = ?");
                            $stmt->bind_param("ddi", $phanTramLoiNhuan, $giaBanMoi, $sanPham_id);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }

                // 3. Lưu chi tiết phiếu nhập
                $stmt = $conn->prepare("INSERT INTO chitietphieunhap (PhieuNhap_id, SanPham_id, SoLuong, Gia_Nhap, MaLoHang) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiids", $phieuNhap_id, $sanPham_id, $soLuongNhap, $giaNhapMoi, $maLoHang);
                $stmt->execute();
                $stmt->close();

                $tongSoLuong += $soLuongNhap;
                $tongGiaTri += $soLuongNhap * $giaNhapMoi;

                // 4. Ghi log vào tracuutonkho
                if ($soLuongNhap > 0) {
                    $stmt = $conn->prepare("INSERT INTO tracuutonkho (SP_id, TrangThai_NhapXuat, SoLuong, MaThamChieu_NhapXuat, ThoiGianTraCuu) VALUES (?, 'NHAP', ?, ?, NOW())");
                    $stmt->bind_param("iii", $sanPham_id, $soLuongNhap, $phieuNhap_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            // 5. Cập nhật tổng số lượng cho phiếu nhập
            $stmt = $conn->prepare("UPDATE phieunhap SET SoLuong = ? WHERE NhapHang_id = ?");
            $stmt->bind_param("ii", $tongSoLuong, $phieuNhap_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $message = '✅ Tạo phiếu nhập thành công! Mã phiếu: ' . $maPhieu;
            $messageType = 'success';
            header("Location: import.php?success=1");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $message = '❌ Lỗi: ' . $e->getMessage();
            $messageType = 'error';
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
.image-preview { width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px dashed #ddd; }
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
    <aside class="w-64 bg-white shadow-lg hidden lg:block border-r">
        <div class="p-6 border-b"><h3 class="text-gray-500 text-xs font-bold uppercase">Chức năng</h3></div>
        <nav class="p-4 space-y-2">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50"><i class="fas fa-home w-5"></i> Dashboard</a>
            <a href="product.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50"><i class="fas fa-box w-5"></i> Sản phẩm</a>
            <a href="import.php" class="flex items-center gap-3 px-4 py-3 bg-gradient-custom text-white rounded-lg"><i class="fas fa-arrow-down w-5"></i> Nhập hàng</a>
            <a href="orders.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50"><i class="fas fa-receipt w-5"></i> Đơn hàng</a>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="flex-1 p-6 lg:p-8 bg-gray-50">
        <form method="POST" id="importForm" enctype="multipart/form-data" class="bg-white rounded-xl shadow-lg p-6 lg:p-8">
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
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8 p-4 bg-gray-50 rounded-lg">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Mã phiếu nhập</label>
                    <input type="text" name="NhapHang_id" value="<?php echo htmlspecialchars($maPhieuTuDong); ?>" readonly class="w-full px-4 py-2.5 rounded-lg border bg-gray-100 text-gray-500 cursor-not-allowed font-mono text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nhà cung cấp *</label>
                    <select name="ncc_id" required class="w-full px-4 py-2.5 rounded-lg border focus:outline-none focus:border-primary">
                        <option value="">-- Chọn NCC --</option>
                        <?php while($ncc = $nhacungcaps->fetch_assoc()): ?>
                        <option value="<?php echo $ncc['NCC_id']; ?>"><?php echo htmlspecialchars($ncc['Ten_NCC']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Người nhập</label>
                    <input type="text" name="nguoiNhap" value="<?php echo htmlspecialchars($admin_username ?: $admin_name); ?>" readonly class="w-full px-4 py-2.5 rounded-lg border bg-gray-100 text-gray-500 cursor-not-allowed">
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
                    <div class="flex gap-2">
                        <button type="button" onclick="addItemRow('existing')" class="bg-blue-100 text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-200 transition text-sm font-medium">
                            <i class="fas fa-plus"></i> Thêm sản phẩm có sẵn
                        </button>
                        <button type="button" onclick="addItemRow('new')" class="bg-green-100 text-green-700 px-4 py-2 rounded-lg hover:bg-green-200 transition text-sm font-medium">
                            <i class="fas fa-sparkles"></i> Thêm sản phẩm MỚI
                        </button>
                    </div>
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

<!-- Template cho item row (hidden) -->
<template id="itemTemplateExisting">
    <div class="item-card border rounded-xl p-4 bg-white animate-slide-in" data-type="existing">
        <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-gray-800">📦 Sản phẩm có sẵn</h4>
            <button type="button" onclick="removeItem(this)" class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
        </div>
        <input type="hidden" name="items[][is_new]" value="0">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Chọn sản phẩm *</label>
                <select name="items[][sanpham_id]" class="product-select w-full px-3 py-2 rounded-lg border text-sm" required onchange="onProductSelect(this)">
                    <option value="">-- Chọn sản phẩm --</option>
                    <?php $sanphams->data_seek(0); while($sp = $sanphams->fetch_assoc()): ?>
                    <option value="<?php echo $sp['SanPham_id']; ?>" data-gianhap="<?php echo $sp['GiaNhapTB']; ?>" data-giaban="<?php echo $sp['GiaBan']; ?>" data-ton="<?php echo $sp['SoLuongTon']; ?>">
                        <?php echo htmlspecialchars($sp['TenSP']); ?> (Tồn: <?php echo $sp['SoLuongTon']; ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Số lượng nhập *</label>
                <input type="number" name="items[][so_luong]" class="qty-input w-full px-3 py-2 rounded-lg border text-sm" min="1" value="1" oninput="calcItemTotal(this)">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Giá nhập (VNĐ) *</label>
                <input type="number" name="items[][gia_nhap]" class="price-input w-full px-3 py-2 rounded-lg border text-sm" min="0" step="0.01" value="0" oninput="calcItemTotal(this); calcSellingPrice(this)">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 p-3 bg-blue-50 rounded-lg">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">% Lợi nhuận</label>
                <input type="number" name="items[][phan_tram_loi]" class="profit-input w-full px-3 py-2 rounded-lg border text-sm" min="0" max="100" value="20" oninput="calcSellingPrice(this)">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Giá bán dự kiến</label>
                <input type="text" class="selling-price w-full px-3 py-2 rounded-lg border text-sm bg-gray-100" readonly value="0đ">
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="items[][cap_nhat_gia_ban]" value="1" checked class="rounded text-primary">
                    <span class="text-gray-700">Cập nhật giá bán</span>
                </label>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Mã lô hàng</label>
                <input type="text" name="items[][ma_lo_hang]" class="w-full px-3 py-2 rounded-lg border text-sm bg-gray-100 text-gray-500 cursor-not-allowed" readonly value="<?php echo htmlspecialchars($maLoHangTuDong); ?>">
            </div>
            <div class="flex items-end">
                <p class="text-sm text-gray-600">Thành tiền: <span class="item-total font-bold text-primary">0</span>đ</p>
            </div>
        </div>
    </div>
</template>

<template id="itemTemplateNew">
    <div class="item-card border-2 border-green-200 rounded-xl p-4 bg-green-50/50 animate-slide-in" data-type="new">
        <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-green-800">✨ Sản phẩm MỚI</h4>
            <button type="button" onclick="removeItem(this)" class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
        </div>
        <input type="hidden" name="items[][is_new]" value="1">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Tên sản phẩm mới *</label>
                <input type="text" name="items[][ten_sp_moi]" class="w-full px-3 py-2 rounded-lg border text-sm" required placeholder="Nhập tên sản phẩm...">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Danh mục</label>
                <select name="items[][danhmuc_id]" class="w-full px-3 py-2 rounded-lg border text-sm">
                    <option value="0">-- Chọn danh mục --</option>
                    <?php
                    $danhmucs = $conn->query("SELECT Danhmuc_id, Ten_danhmuc FROM danhmuc");
                    while($dm = $danhmucs->fetch_assoc()): ?>
                    <option value="<?php echo $dm['Danhmuc_id']; ?>"><?php echo htmlspecialchars($dm['Ten_danhmuc']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Thương hiệu</label>
                <select name="items[][thuonghieu_id]" class="w-full px-3 py-2 rounded-lg border text-sm">
                    <option value="0">-- Chọn thương hiệu --</option>
                    <?php
                    $thuonghieus = $conn->query("SELECT Ma_thuonghieu, Ten_thuonghieu FROM thuonghieu");
                    while($th = $thuonghieus->fetch_assoc()): ?>
                    <option value="<?php echo $th['Ma_thuonghieu']; ?>"><?php echo htmlspecialchars($th['Ten_thuonghieu']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Mô tả</label>
                <input type="text" name="items[][mota_moi]" class="w-full px-3 py-2 rounded-lg border text-sm" placeholder="Mô tả ngắn...">
            </div>
        </div>
        <!-- === ĐÃ THÊM: Trường upload hình ảnh === -->
        <div class="mb-4 p-3 bg-white rounded-lg border">
            <label class="block text-xs font-medium text-gray-700 mb-2">📷 Hình ảnh sản phẩm</label>
            <div class="flex items-center gap-4">
                <input type="file" name="item_images[]" accept="image/*" class="image-input w-full px-3 py-2 rounded-lg border text-sm" onchange="previewImage(this)">
                <img class="image-preview hidden" src="" alt="Preview">
            </div>
            <p class="text-xs text-gray-500 mt-1">Định dạng: JPG, PNG, GIF. Tối đa 5MB</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 p-3 bg-white rounded-lg border">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Số lượng nhập *</label>
                <input type="number" name="items[][so_luong]" class="qty-input w-full px-3 py-2 rounded-lg border text-sm" min="1" value="1" oninput="calcItemTotal(this)">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Giá nhập (VNĐ) *</label>
                <input type="number" name="items[][gia_nhap]" class="price-input w-full px-3 py-2 rounded-lg border text-sm" min="0" step="0.01" value="0" oninput="calcItemTotal(this); calcSellingPrice(this)">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">% Lợi nhuận</label>
                <input type="number" name="items[][phan_tram_loi]" class="profit-input w-full px-3 py-2 rounded-lg border text-sm" min="0" max="100" value="20" oninput="calcSellingPrice(this)">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Giá bán dự kiến</label>
                <input type="text" class="selling-price w-full px-3 py-2 rounded-lg border text-sm bg-gray-100" readonly value="0đ">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Mã lô hàng</label>
                <!-- === ĐÃ SỬA: Mã lô hàng tự động, readonly === -->
                <input type="text" name="items[][ma_lo_hang]" class="w-full px-3 py-2 rounded-lg border text-sm bg-gray-100 text-gray-500 cursor-not-allowed font-mono" readonly value="<?php echo htmlspecialchars($maLoHangTuDong); ?>">
            </div>
            <div class="flex items-end">
                <p class="text-sm text-gray-600">Thành tiền: <span class="item-total font-bold text-primary">0</span>đ</p>
            </div>
        </div>
    </div>
</template>

<script>
// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
}

// Add item row
function addItemRow(type) {
    const container = document.getElementById('itemsContainer');
    const templateId = type === 'new' ? 'itemTemplateNew' : 'itemTemplateExisting';
    const template = document.getElementById(templateId);
    const clone = template.content.cloneNode(true);
    
    // Cập nhật mã lô hàng tự động cho mỗi item mới
    if (type === 'new') {
        const maLoHangInput = clone.querySelector('input[name="items[][ma_lo_hang]"]');
        if (maLoHangInput) {
            maLoHangInput.value = 'LOT-' + new Date().toISOString().slice(0,10).replace(/-/g,'') + '-' + Math.floor(Math.random() * 1000);
        }
    }
    
    container.appendChild(clone);
}

// Remove item row
function removeItem(btn) {
    const card = btn.closest('.item-card');
    card.remove();
    calcTotal();
}

// Preview image
function previewImage(input) {
    const card = input.closest('.item-card');
    const preview = card.querySelector('.image-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Calculate item total (Qty * Price)
function calcItemTotal(input) {
    const card = input.closest('.item-card');
    const qty = parseFloat(card.querySelector('.qty-input').value) || 0;
    const price = parseFloat(card.querySelector('.price-input').value) || 0;
    const total = qty * price;
    card.querySelector('.item-total').textContent = new Intl.NumberFormat('vi-VN').format(total);
    calcTotal();
}

// Calculate selling price
function calcSellingPrice(input) {
    const card = input.closest('.item-card');
    const price = parseFloat(card.querySelector('.price-input').value) || 0;
    const profit = parseFloat(card.querySelector('.profit-input').value) || 0;
    const sellingPrice = price * (1 + profit / 100);
    card.querySelector('.selling-price').value = formatCurrency(sellingPrice);
}

// On product select (auto fill price)
function onProductSelect(select) {
    const card = select.closest('.item-card');
    const option = select.options[select.selectedIndex];
    if (option.value) {
        const giaNhap = option.getAttribute('data-gianhap');
        const giaBan = option.getAttribute('data-giaban');
        if(giaNhap) card.querySelector('.price-input').value = giaNhap;
        calcItemTotal(card.querySelector('.qty-input'));
        calcSellingPrice(card.querySelector('.price-input'));
    }
}

// Calculate Grand Total
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

// Logout
function logout() {
    if(confirm('Bạn có chắc chắn muốn đăng xuất?')) {
        window.location.href = 'logout.php';
    }
}

// Init totals on load
document.addEventListener('DOMContentLoaded', () => {
    calcTotal();
});
</script>
</body>
</html>