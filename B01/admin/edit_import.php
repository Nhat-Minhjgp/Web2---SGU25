<?php
session_start();
require_once __DIR__ . '/../control/connect.php';
require_once __DIR__ . '/../control/function.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Quản trị viên';
$admin_username = $_SESSION['admin_username'] ?? '';

// Lấy ID phiếu nhập từ URL
$phieu_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($phieu_id <= 0) {
    header('Location: import.php');
    exit();
}

// Kiểm tra phiếu nhập có tồn tại và chưa hoàn thành
$check = $conn->prepare("SELECT * FROM phieunhap WHERE NhapHang_id = ?");
$check->bind_param("i", $phieu_id);
$check->execute();
$result = $check->get_result();
$phieu = $result->fetch_assoc();

if (!$phieu) {
    header('Location: import.php?error=not_found');
    exit();
}

if ($phieu['TrangThai'] == 'completed') {
    header('Location: import.php?error=already_completed');
    exit();
}

$message = '';
$messageType = '';

// Lấy danh sách sản phẩm active
$sanphams = $conn->query("SELECT SanPham_id, TenSP, GiaNhapTB, GiaBan, SoLuongTon, PhanTramLoiNhuan, NCC_id FROM sanpham WHERE TrangThai = 1 OR Trangthai = 0 ORDER BY TenSP");

// Lấy danh sách chi tiết phiếu nhập hiện tại
$items_old = [];
$detail_sql = "SELECT ct.*, sp.TenSP, sp.GiaNhapTB, sp.PhanTramLoiNhuan 
               FROM chitietphieunhap ct 
               LEFT JOIN sanpham sp ON ct.SanPham_id = sp.SanPham_id 
               WHERE ct.PhieuNhap_id = ?";
$stmt = $conn->prepare($detail_sql);
$stmt->bind_param("i", $phieu_id);
$stmt->execute();
$items_old = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Mã lô hàng mặc định (giữ nguyên hoặc có thể sửa)
$maLoHangDefault = $items_old[0]['MaLoHang'] ?? ('LOT-' . date('Ymd') . '-' . strtoupper(substr($admin_username ?: $admin_name, 0, 3)) . '-' . str_pad($phieu_id, 3, '0', STR_PAD_LEFT));

// XỬ LÝ CẬP NHẬT PHIẾU NHẬP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_import'])) {
    $nguoiNhap = trim($_POST['nguoiNhap'] ?? ($admin_username ?: $admin_name));
    $ngayNhap = $_POST['ngayNhap'] ?? date('Y-m-d');
    $items = $_POST['items'] ?? [];
    
    if (empty($items)) {
        $message = 'Vui lòng thêm ít nhất 1 sản phẩm vào phiếu nhập!';
        $messageType = 'error';
    } else {
        $conn->begin_transaction();
        try {
            // 1. Cập nhật thông tin phiếu chính
            $stmt = $conn->prepare("UPDATE phieunhap SET NguoiNhap = ?, NgayNhap = ? WHERE NhapHang_id = ?");
            $stmt->bind_param("ssi", $nguoiNhap, $ngayNhap, $phieu_id);
            $stmt->execute();
            $stmt->close();
            
            // 2. Xóa chi tiết cũ
            $del = $conn->prepare("DELETE FROM chitietphieunhap WHERE PhieuNhap_id = ?");
            $del->bind_param("i", $phieu_id);
            $del->execute();
            $del->close();
            
            // 3. Thêm chi tiết mới
            foreach ($items as $item) {
                $sanPham_id = intval($item['sanpham_id'] ?? 0);
                $soLuongNhap = intval($item['so_luong'] ?? 0);
                $giaNhapMoi = floatval($item['gia_nhap'] ?? 0);
                $maLoHang = trim($item['ma_lo_hang'] ?? $maLoHangDefault);
                
                if ($soLuongNhap <= 0 || $giaNhapMoi < 0 || !$sanPham_id) continue;
                
                $stmt = $conn->prepare("INSERT INTO chitietphieunhap (PhieuNhap_id, SanPham_id, SoLuong, Gia_Nhap, MaLoHang) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiids", $phieu_id, $sanPham_id, $soLuongNhap, $giaNhapMoi, $maLoHang);
                $stmt->execute();
                $stmt->close();
            }
            
            // 4. Cập nhật lại log tồn kho (xóa log cũ + thêm log mới)
            $conn->query("DELETE FROM tracuutonkho WHERE MaThamChieu_NhapXuat = $phieu_id AND TrangThai_NhapXuat = 'NHAP'");
            foreach ($items as $item) {
                $sanPham_id = intval($item['sanpham_id'] ?? 0);
                $soLuongNhap = intval($item['so_luong'] ?? 0);
                if ($soLuongNhap > 0 && $sanPham_id > 0) {
                    $stmt = $conn->prepare("INSERT INTO tracuutonkho (SP_id, TrangThai_NhapXuat, SoLuong, MaThamChieu_NhapXuat, ThoiGianTraCuu) VALUES (?, 'NHAP', ?, ?, NOW())");
                    $stmt->bind_param("iii", $sanPham_id, $soLuongNhap, $phieu_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            
            $conn->commit();
            $message = '✅ Cập nhật phiếu nhập thành công!';
            $messageType = 'success';
            header("Location: import.php?success=edit");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = '❌ Lỗi: ' . $e->getMessage();
            $messageType = 'error';
            error_log("Edit Import Error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa phiếu nhập #<?php echo $phieu_id; ?> - Admin</title>
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
        .item-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
        .search-wrapper { position: relative; }
        .search-input { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; transition: all 0.2s; }
        .search-input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .search-results { position: absolute; top: 100%; left: 0; right: 0; max-height: 250px; overflow-y: auto; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15); z-index: 100; margin-top: 4px; display: none; }
        .search-results.show { display: block; }
        .search-result-item { padding: 10px 12px; cursor: pointer; border-bottom: 1px solid #f3f4f6; transition: background 0.15s; }
        .search-result-item:last-child { border-bottom: none; }
        .search-result-item:hover, .search-result-item.active { background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%); }
        .search-result-name { font-weight: 500; color: #1f2937; font-size: 14px; margin-bottom: 2px; }
        .search-result-meta { font-size: 11px; color: #6b7280; display: flex; gap: 8px; flex-wrap: wrap; }
        .search-result-meta span { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; }
        .search-loading { padding: 10px 12px; color: #6b7280; font-size: 13px; text-align: center; }
        .search-no-result { padding: 12px; color: #9ca3af; font-size: 13px; text-align: center; font-style: italic; }
    </style>
    <link rel="icon" type="image/svg+xml" href="../img/icons/favicon.png" sizes="32x32">
</head>
<body class="bg-gray-50 font-sans text-gray-800">

<!-- HEADER -->
<header class="bg-white shadow-md sticky top-0 z-50">
    <div class="flex justify-between items-center px-6 py-4">
        <h1 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-custom">NVBPlay Admin Panel</h1>
        <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-3 bg-gray-100 px-4 py-2 rounded-lg">
                <div class="w-10 h-10 rounded-full bg-gradient-custom flex items-center justify-center text-white font-bold">
                    <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
                </div>
                <div><p class="text-xs text-gray-500"><?php echo htmlspecialchars($admin_username); ?></p></div>
            </div>
            <button onclick="logout()" class="bg-gradient-custom text-white font-semibold px-4 py-2 rounded-lg hover:opacity-90 transition duration-200 shadow-md hover:shadow-lg">
                <i class="fas fa-sign-out-alt mr-2"></i>Đăng xuất
            </button>
        </div>
    </div>
</header>

<div class="flex w-full min-h-[calc(100vh-70px)]">
    <!-- SIDEBAR -->
    <aside class="w-64 bg-white shadow-lg hidden lg:block flex-shrink-0 border-r border-gray-100">
        <div class="p-6 border-b border-gray-100"><h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Danh mục chức năng</h3></div>
        <nav class="p-4 space-y-2">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i class="fas fa-home w-5"></i> Dashboard</a>
            <a href="users.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i class="fas fa-users w-5"></i> Quản lý người dùng</a>
            <a href="categories.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i class="fas fa-list w-5"></i> Quản lý danh mục</a>
            <a href="product.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i class="fas fa-box w-5"></i> Quản lý sản phẩm</a>
            <a href="import.php" class="flex items-center gap-3 px-4 py-3 bg-gradient-custom text-white rounded-lg shadow-md"><i class="fas fa-arrow-down w-5"></i> Quản lý nhập hàng</a>
            <a href="price.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i class="fas fa-tag w-5"></i> Quản lý giá bán</a>
            <a href="orders.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i class="fas fa-receipt w-5"></i> Quản lý đơn hàng</a>
            <a href="inventory.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i class="fas fa-warehouse w-5"></i> Tồn kho & Báo cáo</a>
        </nav>
    </aside>

    <main class="flex-1 p-6 lg:p-8 overflow-x-hidden bg-gray-50">
        <form method="POST" id="importForm" class="bg-white rounded-xl shadow-lg p-6 lg:p-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 pb-6 border-b gap-4">
                <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                    <i class="fas fa-edit text-primary"></i> Sửa phiếu nhập #PN<?php echo str_pad($phieu_id, 6, '0', STR_PAD_LEFT); ?>
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

            <!-- Thông tin phiếu -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 p-4 bg-gray-50 rounded-lg">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Mã phiếu nhập</label>
                    <input type="text" value="#PN<?php echo str_pad($phieu_id, 6, '0', STR_PAD_LEFT); ?>" readonly class="w-full px-4 py-2.5 rounded-lg border bg-gray-100 text-gray-500 cursor-not-allowed font-mono text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Người nhập</label>
                    <input type="text" value="<?php echo htmlspecialchars($phieu['NguoiNhap'] ?? ($admin_username ?: $admin_name)); ?>" name="nguoiNhap" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Ngày nhập <span class="text-red-500">*</span></label>
                    <input type="date" name="ngayNhap" value="<?php echo htmlspecialchars($phieu['NgayNhap'] ?? date('Y-m-d')); ?>" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:border-primary">
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
                    <?php foreach ($items_old as $idx => $item): ?>
                    <div class="item-card border rounded-xl p-4 bg-white animate-slide-in">
                        <div class="flex justify-between items-start mb-3">
                            <h4 class="font-semibold text-gray-800">📦 Sản phẩm</h4>
                            <button type="button" onclick="removeItem(this)" class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                            <div class="lg:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Tìm sản phẩm <span class="text-red-500">*</span></label>
                                <div class="search-wrapper">
                                    <input type="text" class="product-search-input search-input" placeholder="Nhập tên hoặc mã sản phẩm..." autocomplete="off" value="<?php echo htmlspecialchars($item['TenSP'] ?? ''); ?>" readonly>
                                    <input type="hidden" name="items[<?php echo $idx; ?>][sanpham_id]" class="product-id-input" value="<?php echo $item['SanPham_id']; ?>" required>
                                    <div class="search-results" id="results-<?php echo $idx; ?>"></div>
                                </div>
                                <div class="selected-product-info mt-2 p-2 bg-green-50 rounded-lg border border-green-200">
                                    <p class="text-sm font-medium text-green-800 selected-product-name"><?php echo htmlspecialchars($item['TenSP'] ?? ''); ?></p>
                                    <p class="text-xs text-green-600">
                                        Tồn kho: <span class="selected-product-stock font-semibold"><?php echo $item['SoLuongTon'] ?? 0; ?></span> |
                                        % Lợi nhuận: <span class="selected-product-profit font-semibold"><?php echo isset($item['PhanTramLoiNhuan']) ? round($item['PhanTramLoiNhuan'] * 100) : 20; ?>%</span>
                                    </p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Số lượng nhập <span class="text-red-500">*</span></label>
                                <input type="number" name="items[<?php echo $idx; ?>][so_luong]" class="qty-input w-full px-3 py-2 rounded-lg border text-sm" min="1" value="<?php echo $item['SoLuong']; ?>" oninput="calcItemTotal(this)">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Giá nhập mới (VNĐ) <span class="text-red-500">*</span></label>
                                <input type="number" name="items[<?php echo $idx; ?>][gia_nhap]" class="price-input w-full px-3 py-2 rounded-lg border text-sm" min="0" step="0.01" value="<?php echo $item['Gia_Nhap']; ?>" oninput="calcItemTotal(this)">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Mã lô hàng</label>
                                <input type="text" name="items[<?php echo $idx; ?>][ma_lo_hang]" class="w-full px-3 py-2 rounded-lg border text-sm font-mono" value="<?php echo htmlspecialchars($item['MaLoHang'] ?? $maLoHangDefault); ?>">
                            </div>
                            <div class="flex items-end">
                                <p class="text-sm text-gray-600">Thành tiền: <span class="item-total font-bold text-primary"><?php echo number_format(($item['SoLuong'] ?? 0) * ($item['Gia_Nhap'] ?? 0), 0, ',', '.'); ?></span>đ</p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tổng và nút lưu -->
            <div class="border-t pt-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="text-gray-600">
                    <p>Tổng số lượng: <span id="totalQty" class="font-bold text-gray-800">
                        <?php echo array_sum(array_column($items_old, 'SoLuong')); ?>
                    </span> sản phẩm</p>
                    <p>Tổng giá trị: <span id="totalValue" class="font-bold text-primary text-lg">
                        <?php echo number_format(array_sum(array_map(fn($i) => $i['SoLuong'] * $i['Gia_Nhap'], $items_old)), 0, ',', '.'); ?>
                    </span>đ</p>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="history.back()" class="px-6 py-2.5 rounded-lg bg-gray-500 text-white hover:bg-gray-600 transition font-medium">
                        <i class="fas fa-times mr-2"></i> Hủy
                    </button>
                    <button type="submit" name="update_import" class="px-6 py-2.5 rounded-lg bg-gradient-custom text-white font-medium hover:opacity-90 transition shadow-lg">
                        <i class="fas fa-save mr-2"></i> Cập nhật phiếu
                    </button>
                </div>
            </div>
        </form>
    </main>
</div>

<!-- TEMPLATE cho JS -->
<template id="itemTemplate">
    <div class="item-card border rounded-xl p-4 bg-white animate-slide-in">
        <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-gray-800">📦 Sản phẩm</h4>
            <button type="button" onclick="removeItem(this)" class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Tìm sản phẩm <span class="text-red-500">*</span></label>
                <div class="search-wrapper">
                    <input type="text" class="product-search-input search-input" placeholder="Nhập tên hoặc mã sản phẩm..." autocomplete="off" oninput="handleProductSearch(this)" onkeydown="handleSearchKeydown(event, this)">
                    <input type="hidden" name="items[INDEX][sanpham_id]" class="product-id-input" required>
                    <div class="search-results" id="results-INDEX"></div>
                </div>
                <div class="selected-product-info mt-2 p-2 bg-green-50 rounded-lg border border-green-200 hidden">
                    <p class="text-sm font-medium text-green-800 selected-product-name"></p>
                    <p class="text-xs text-green-600">Tồn kho: <span class="selected-product-stock font-semibold"></span> | % Lợi nhuận: <span class="selected-product-profit font-semibold"></span></p>
                </div>
                <p class="text-xs text-gray-500 mt-1">💡 Nhập ít nhất 2 ký tự để tìm kiếm</p>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Số lượng nhập <span class="text-red-500">*</span></label>
                <input type="number" name="items[INDEX][so_luong]" class="qty-input w-full px-3 py-2 rounded-lg border text-sm" min="1" value="1" oninput="calcItemTotal(this)">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Giá nhập mới (VNĐ) <span class="text-red-500">*</span></label>
                <input type="number" name="items[INDEX][gia_nhap]" class="price-input w-full px-3 py-2 rounded-lg border text-sm" min="0" step="0.01" value="0" oninput="calcItemTotal(this)">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Mã lô hàng</label>
                <input type="text" name="items[INDEX][ma_lo_hang]" class="w-full px-3 py-2 rounded-lg border text-sm font-mono" value="<?php echo htmlspecialchars($maLoHangDefault); ?>">
            </div>
            <div class="flex items-end">
                <p class="text-sm text-gray-600">Thành tiền: <span class="item-total font-bold text-primary">0</span>đ</p>
            </div>
        </div>
    </div>
</template>

<!-- JAVASCRIPT (giữ nguyên logic từ add_import.php) -->
<script>
let itemIndex = <?php echo count($items_old); ?>;

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
}

function addItemRow() {
    const container = document.getElementById('itemsContainer');
    const template = document.getElementById('itemTemplate');
    const clone = template.content.cloneNode(true);
    
    const allInputs = clone.querySelectorAll('input[name*="INDEX"], select[name*="INDEX"]');
    for (let i = 0; i < allInputs.length; i++) {
        const el = allInputs[i];
        el.name = el.name.replace('INDEX', itemIndex);
        if (el.classList && el.classList.contains('product-search-input')) {
            el.id = 'search-' + itemIndex;
        }
    }
    
    const resultsDiv = clone.querySelector('.search-results');
    if (resultsDiv) resultsDiv.id = 'results-' + itemIndex;
    
    container.appendChild(clone);
    
    const newSearchInput = container.lastElementChild.querySelector('.product-search-input');
    if (newSearchInput) {
        newSearchInput.addEventListener('input', function(e) { debouncedSearch(e.target); });
        newSearchInput.addEventListener('keydown', function(e) { handleSearchKeydown(e, e.target); });
        newSearchInput.addEventListener('focus', function() { if (this.value.length >= 2) handleProductSearch(this); });
    }
    
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

function calcTotal() {
    let totalQty = 0, totalValue = 0;
    document.querySelectorAll('.item-card').forEach(card => {
        const qty = parseFloat(card.querySelector('.qty-input').value) || 0;
        const price = parseFloat(card.querySelector('.price-input').value) || 0;
        totalQty += qty;
        totalValue += qty * price;
    });
    document.getElementById('totalQty').textContent = totalQty;
    document.getElementById('totalValue').textContent = formatCurrency(totalValue);
}

// Debounce
function debounce(func, wait = 300) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// Search sản phẩm
async function handleProductSearch(input) {
    const keyword = input.value.trim();
    const resultsDiv = input.closest('.search-wrapper').querySelector('.search-results');
    if (keyword.length < 2) { resultsDiv.classList.remove('show'); resultsDiv.innerHTML = ''; return; }
    
    resultsDiv.innerHTML = '<div class="search-loading"><i class="fas fa-spinner fa-spin"></i> Đang tìm...</div>';
    resultsDiv.classList.add('show');
    
    try {
        const response = await fetch(`../control/search-product.php?q=${encodeURIComponent(keyword)}&limit=10`);
        const products = await response.json();
        renderSearchResults(products, resultsDiv, input);
    } catch (error) {
        console.error('Search error:', error);
        resultsDiv.innerHTML = '<div class="search-no-result">❌ Lỗi kết nối</div>';
    }
}

function renderSearchResults(products, container, searchInput) {
    if (!products || products.length === 0) {
        container.innerHTML = '<div class="search-no-result">🔍 Không tìm thấy sản phẩm</div>';
        return;
    }
    container.innerHTML = products.map(p => 
        `<div class="search-result-item" data-id="${p.id}" data-name="${escapeHtml(p.name)}" data-gianhap="${p.gia_nhap}" data-ton="${p.ton}" data-loi="${p.loi}" onclick="selectProduct(this)">
            <div class="search-result-name">${escapeHtml(p.name)}</div>
            <div class="search-result-meta">
                <span>📦 Mã: ${p.id}</span>
                <span>📊 Tồn: ${p.ton}</span>
                <span>💰 Nhập: ${formatCurrency(p.gia_nhap)}</span>
                <span>🎯 Lợi: ${(p.loi * 100).toFixed(0)}%</span>
            </div>
        </div>`
    ).join('');
}

function selectProduct(element) {
    const card = element.closest('.item-card');
    if (!card) return;
    
    const resultsDiv = card.querySelector('.search-results');
    const searchInput = card.querySelector('.product-search-input');
    const productIdInput = card.querySelector('.product-id-input');
    const priceInput = card.querySelector('.price-input');
    const infoBox = card.querySelector('.selected-product-info');
    
    productIdInput.value = element.dataset.id;
    searchInput.value = element.dataset.name;
    searchInput.readOnly = true;
    
    if (priceInput && element.dataset.gianhap && element.dataset.gianhap !== '0') {
        priceInput.value = parseFloat(element.dataset.gianhap);
    }
    
    if (infoBox) {
        infoBox.classList.remove('hidden');
        infoBox.querySelector('.selected-product-name').textContent = element.dataset.name;
        infoBox.querySelector('.selected-product-stock').textContent = element.dataset.ton || '0';
        infoBox.querySelector('.selected-product-profit').textContent = (element.dataset.loi ? (parseFloat(element.dataset.loi) * 100).toFixed(0) : '20') + '%';
    }
    
    calcItemTotal(priceInput);
    if (resultsDiv) { resultsDiv.classList.remove('show'); resultsDiv.innerHTML = ''; }
}

function handleSearchKeydown(event, input) {
    const resultsDiv = input.closest('.search-wrapper').querySelector('.search-results');
    const items = resultsDiv.querySelectorAll('.search-result-item');
    const activeIndex = Array.from(items).findIndex(item => item.classList.contains('active'));
    
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        const next = activeIndex + 1 < items.length ? activeIndex + 1 : 0;
        updateActiveItem(items, activeIndex, next);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        const prev = activeIndex - 1 >= 0 ? activeIndex - 1 : items.length - 1;
        updateActiveItem(items, activeIndex, prev);
    } else if (event.key === 'Enter') {
        event.preventDefault();
        if (activeIndex >= 0 && items[activeIndex]) items[activeIndex].click();
    } else if (event.key === 'Escape') {
        resultsDiv.classList.remove('show');
    }
}

function updateActiveItem(items, oldIdx, newIdx) {
    if (items[oldIdx]) items[oldIdx].classList.remove('active');
    if (items[newIdx]) {
        items[newIdx].classList.add('active');
        items[newIdx].scrollIntoView({ block: 'nearest' });
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

const debouncedSearch = debounce(handleProductSearch, 300);

document.addEventListener('click', (e) => {
    if (!e.target.closest('.search-wrapper')) {
        document.querySelectorAll('.search-results').forEach(div => div.classList.remove('show'));
    }
});

function logout() {
    if (confirm('Bạn có chắc muốn đăng xuất?')) window.location.href = 'logout.php';
}

// Init
document.addEventListener('DOMContentLoaded', () => {
    calcTotal();
    // Gắn event cho các input search đã có sẵn
    document.querySelectorAll('.product-search-input').forEach(input => {
        if (!input.readOnly) {
            input.addEventListener('input', function(e) { debouncedSearch(e.target); });
            input.addEventListener('keydown', function(e) { handleSearchKeydown(e, e.target); });
        }
    });
});
</script>

</body>
</html>