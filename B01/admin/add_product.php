<?php
session_start();
require_once __DIR__ . '/../control/connect.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php'); exit();
}
$admin_name = $_SESSION['admin_name'] ?? 'Quản trị viên';
$message = ''; $messageType = '';

// Lấy danh sách dropdown
$nhacungcaps = $conn->query("SELECT NCC_id, Ten_NCC FROM nhacungcap ORDER BY Ten_NCC");
$danhmucs = $conn->query("SELECT Danhmuc_id, Ten_danhmuc FROM danhmuc ORDER BY Ten_danhmuc");
$thuonghieus = $conn->query("SELECT Ma_thuonghieu, Ten_thuonghieu FROM thuonghieu ORDER BY Ten_thuonghieu");

// Xử lý lưu sản phẩm mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $tenSP = trim($_POST['tenSP'] ?? '');
    $danhmuc_id = intval($_POST['danhmuc_id'] ?? 0);
    $ncc_id = intval($_POST['ncc_id'] ?? 0);
    $ma_thuonghieu = intval($_POST['thuonghieu_id'] ?? 0);
    $mota = trim($_POST['mota'] ?? '');
    $phanTramLoiNhuan = floatval($_POST['phan_tram_loi'] ?? 20);
    
    // Upload hình ảnh
    $imageUrl = '';
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
        $uploadDir = __DIR__ . '/../uploads/products/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $newName = 'PROD-' . date('YmdHis') . '-' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newName)) {
            $imageUrl = 'uploads/products/' . $newName;
        }
    }

    if (empty($tenSP) || !$danhmuc_id || !$ncc_id) {
        $message = '❌ Vui lòng điền đầy đủ thông tin bắt buộc!';
        $messageType = 'error';
    } else {
        try {
            // ✅ Lưu sản phẩm với giá NULL (chưa có giá nhập/giá bán)
            $stmt = $conn->prepare("INSERT INTO sanpham (TenSP, Danhmuc_id, NCC_id, Ma_thuonghieu, MoTa, image_url, PhanTramLoiNhuan, TrangThai, SoLuongTon, TaoNgay) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0, NOW())");
            $stmt->bind_param("siisssd", $tenSP, $danhmuc_id, $ncc_id, $ma_thuonghieu, $mota, $imageUrl, $phanTramLoiNhuan);
            $stmt->execute();
            $stmt->close();
            
            $message = '✅ Tạo sản phẩm thành công! Giờ bạn có thể nhập hàng cho sản phẩm này.';
            $messageType = 'success';
        } catch (Exception $e) {
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
<title>Thêm sản phẩm mới - Admin</title>
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
</head>
<body class="bg-gray-50 font-sans text-gray-800">
<!-- HEADER (giữ nguyên như file cũ) -->
<header class="bg-white shadow-md sticky top-0 z-50 h-[70px] flex items-center w-full">
    <div class="w-full px-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-custom">NVBPlay Admin Panel</h1>
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gradient-custom flex items-center justify-center text-white font-bold"><?php echo strtoupper(substr($admin_name, 0, 1)); ?></div>
                <div><p class="text-sm font-semibold"><?php echo htmlspecialchars($admin_name); ?> <span class="text-xs bg-gradient-custom text-white px-2 py-0.5 rounded">Admin</span></p></div>
            </div>
            <button onclick="logout()" class="text-red-500 hover:text-red-700 transition"><i class="fas fa-sign-out-alt"></i> Đăng xuất</button>
        </div>
    </div>
</header>

<div class="flex w-full min-h-[calc(100vh-70px)]">
    <!-- SIDEBAR (giữ nguyên) -->
    <aside class="w-64 bg-white shadow-lg hidden lg:block border-r">
        <div class="p-6 border-b"><h3 class="text-gray-500 text-xs font-bold uppercase">Chức năng</h3></div>
        <nav class="p-4 space-y-2">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50"><i class="fas fa-home w-5"></i> Dashboard</a>
            <a href="product.php" class="flex items-center gap-3 px-4 py-3 bg-gradient-custom text-white rounded-lg"><i class="fas fa-box w-5"></i> Sản phẩm</a>
            <a href="import.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50"><i class="fas fa-arrow-down w-5"></i> Nhập hàng</a>
            <a href="orders.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50"><i class="fas fa-receipt w-5"></i> Đơn hàng</a>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="flex-1 p-6 lg:p-8 bg-gray-50">
        <form method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-lg p-6 lg:p-8 max-w-3xl mx-auto">
            <div class="flex justify-between items-center mb-8 pb-6 border-b">
                <h2 class="text-2xl font-bold text-gray-800"><i class="fas fa-plus-circle text-primary"></i> Thêm sản phẩm mới</h2>
                <a href="product.php" class="text-gray-600 hover:text-primary"><i class="fas fa-arrow-left"></i> Quay lại</a>
            </div>

            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType==='success'?'bg-green-50 text-green-700 border-green-200':'bg-red-50 text-red-700 border-red-200'; ?> border">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Tên sản phẩm -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tên sản phẩm *</label>
                    <input type="text" name="tenSP" required class="w-full px-4 py-2.5 rounded-lg border focus:outline-none focus:border-primary">
                </div>

                <!-- Danh mục -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Danh mục *</label>
                    <select name="danhmuc_id" required class="w-full px-4 py-2.5 rounded-lg border focus:outline-none focus:border-primary">
                        <option value="">-- Chọn danh mục --</option>
                        <?php while($dm = $danhmucs->fetch_assoc()): ?>
                        <option value="<?php echo $dm['Danhmuc_id']; ?>"><?php echo htmlspecialchars($dm['Ten_danhmuc']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Thương hiệu -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Thương hiệu</label>
                    <select name="thuonghieu_id" class="w-full px-4 py-2.5 rounded-lg border focus:outline-none focus:border-primary">
                        <option value="0">-- Chọn thương hiệu --</option>
                        <?php while($th = $thuonghieus->fetch_assoc()): ?>
                        <option value="<?php echo $th['Ma_thuonghieu']; ?>"><?php echo htmlspecialchars($th['Ten_thuonghieu']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Nhà cung cấp -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nhà cung cấp *</label>
                    <select name="ncc_id" required class="w-full px-4 py-2.5 rounded-lg border focus:outline-none focus:border-primary">
                        <option value="">-- Chọn NCC --</option>
                        <?php while($ncc = $nhacungcaps->fetch_assoc()): ?>
                        <option value="<?php echo $ncc['NCC_id']; ?>"><?php echo htmlspecialchars($ncc['Ten_NCC']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- % Lợi nhuận -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">% Lợi nhuận dự kiến *</label>
                    <input type="number" name="phan_tram_loi" min="0" max="200" value="20" class="w-full px-4 py-2.5 rounded-lg border focus:outline-none focus:border-primary">
                    <p class="text-xs text-gray-500 mt-1">Giá bán sẽ = Giá nhập trung bình × (1 + <?php echo htmlspecialchars($_POST['phan_tram_loi'] ?? 20); ?>%)</p>
                </div>

                <!-- Hình ảnh -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">📷 Hình ảnh sản phẩm</label>
                    <input type="file" name="image" accept="image/*" class="w-full px-4 py-2.5 rounded-lg border" onchange="previewImage(this)">
                    <img id="imagePreview" class="mt-3 max-w-xs rounded-lg hidden" src="" alt="Preview">
                </div>

                <!-- Mô tả -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Mô tả</label>
                    <textarea name="mota" rows="3" class="w-full px-4 py-2.5 rounded-lg border focus:outline-none focus:border-primary" placeholder="Mô tả ngắn về sản phẩm..."></textarea>
                </div>
            </div>

            <!-- Lưu ý quan trọng -->
            <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800">
                <i class="fas fa-info-circle"></i> <strong>Lưu ý:</strong> Sản phẩm này chưa có giá nhập/giá bán. 
                Bạn cần vào trang <strong>Nhập hàng</strong> để nhập lô hàng đầu tiên, hệ thống sẽ tự động tính giá bán theo công thức đã thiết lập.
            </div>

            <!-- Nút hành động -->
            <div class="mt-8 flex gap-3 justify-end">
                <button type="button" onclick="history.back()" class="px-6 py-2.5 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 transition">Hủy</button>
                <button type="submit" name="save_product" class="px-6 py-2.5 rounded-lg bg-gradient-custom text-white font-medium hover:opacity-90 transition shadow-lg">
                    <i class="fas fa-save"></i> Lưu sản phẩm
                </button>
            </div>
        </form>
    </main>
</div>
<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.classList.add('hidden');
    }
}
function logout() {
    if(confirm('Bạn có chắc chắn muốn đăng xuất?')) {
        window.location.href = 'logout.php';
    }
}
</script>
</body>
</html>