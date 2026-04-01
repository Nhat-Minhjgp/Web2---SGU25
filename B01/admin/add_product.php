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

// ========== HÀM KIỂM TRA SQL INJECTION ==========
function hasSQLInjection($value) {
    $patterns = [
        '/\b(SELECT|INSERT|UPDATE|DELETE|DROP|TRUNCATE|ALTER|CREATE|EXEC|EXECUTE|UNION)\b/i',
        '/(--|\/\*|\*\/|#)/',
        '/\bOR\b\s+[\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+|\bAND\b\s+[\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+/i',
        '/\bxp_\w+|sp_\w+/i',
        '/\b(WAITFOR|BENCHMARK|SLEEP)\b/i',
        '/%00|%27|%22/i',
        '/;/'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $value)) {
            return true;
        }
    }
    return false;
}

// ========== HÀM VALIDATE TÊN SẢN PHẨM ==========
function validateProductName($name, &$errors) {
    $name = trim($name);
    if (empty($name)) {
        $errors[] = "Tên sản phẩm không được để trống";
        return false;
    }
    if (strlen($name) < 3) {
        $errors[] = "Tên sản phẩm phải có ít nhất 3 ký tự";
        return false;
    }
    if (strlen($name) > 200) {
        $errors[] = "Tên sản phẩm không được vượt quá 200 ký tự";
        return false;
    }
    if (hasSQLInjection($name)) {
        $errors[] = "Tên sản phẩm chứa ký tự không an toàn";
        return false;
    }
    return true;
}

// ========== HÀM VALIDATE MÔ TẢ ==========
function validateDescription($desc, &$errors) {
    $desc = trim($desc);
    if (!empty($desc) && hasSQLInjection($desc)) {
        $errors[] = "Mô tả chứa ký tự không an toàn";
        return false;
    }
    return true;
}

// Lấy danh sách dropdown
$nhacungcaps = $conn->query("SELECT NCC_id, Ten_NCC FROM nhacungcap ORDER BY Ten_NCC");
$danhmucs = $conn->query("SELECT Danhmuc_id, Ten_danhmuc FROM danhmuc ORDER BY Ten_danhmuc");
$thuonghieus = $conn->query("SELECT Ma_thuonghieu, Ten_thuonghieu FROM thuonghieu ORDER BY Ten_thuonghieu");

// Xử lý lưu sản phẩm mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $errors = [];
    
    $tenSP = trim($_POST['tenSP'] ?? '');
    $danhmuc_id = intval($_POST['danhmuc_id'] ?? 0);
    $ncc_id = intval($_POST['ncc_id'] ?? 0);
    $ma_thuonghieu = intval($_POST['thuonghieu_id'] ?? 0);
    $mota = trim($_POST['mota'] ?? '');
    $phanTramLoiNhuan_input = floatval($_POST['phan_tram_loi'] ?? 20);
    
    // Server-side validation
    $valid = true;
    $valid = $valid && validateProductName($tenSP, $errors);
    $valid = $valid && validateDescription($mota, $errors);
    
    // Kiểm tra % lợi nhuận hợp lệ
    if ($phanTramLoiNhuan_input < 0 || $phanTramLoiNhuan_input > 200) {
        $errors[] = "Tỷ lệ lợi nhuận phải từ 0% đến 200%";
        $valid = false;
    }
    $phanTramLoiNhuan = $phanTramLoiNhuan_input / 100;
    
    // Kiểm tra danh mục hợp lệ
    if ($danhmuc_id <= 0) {
        $errors[] = "Vui lòng chọn danh mục";
        $valid = false;
    }
    
    // Kiểm tra nhà cung cấp hợp lệ
    if ($ncc_id <= 0) {
        $errors[] = "Vui lòng chọn nhà cung cấp";
        $valid = false;
    }
    
    // Upload hình ảnh
    $imageUrl = '';
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
        $uploadDir = __DIR__ . '/../img/sanpham/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($ext, $allowedTypes)) {
            $errors[] = "Định dạng ảnh không hợp lệ! Chỉ chấp nhận: jpg, jpeg, png, gif, webp";
            $valid = false;
        } else {
            $newName = 'PROD-' . date('YmdHis') . '-' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newName)) {
                $imageUrl = 'img/sanpham/' . $newName;
            } else {
                $errors[] = "Lỗi upload ảnh!";
                $valid = false;
            }
        }
    }

    if (!$valid) {
        $message = '❌ ' . implode('<br>❌ ', $errors);
        $messageType = 'error';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO sanpham (TenSP, Danhmuc_id, NCC_id, Ma_thuonghieu, MoTa, image_url, PhanTramLoiNhuan, TrangThai, SoLuongTon, TaoNgay) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0, NOW())");
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
    <style>
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
        
        /* Validation styles */
        .input-invalid { border-color: #ef4444 !important; }
        .input-valid { border-color: #10b981 !important; }
        .field-error { background: #fef2f2; padding: 4px 8px; border-radius: 6px; margin-top: 4px; font-size: 11px; color: #dc2626; }
        .field-error i { margin-right: 4px; }
        .error-text { color: #ef4444; font-size: 11px; margin-top: 4px; display: none; }
        
        /* SQL Injection Warning */
        .sql-injection-warning {
            background-color: #fef2f2;
            border: 2px solid #dc2626;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: none;
        }
        .sql-injection-warning i {
            margin-right: 8px;
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
                <a href="categories.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-list w-5 text-center"></i> Quản lý danh mục
                </a>
                <a href="product.php" class="flex items-center gap-3 px-4 py-3 bg-gradient-custom text-white rounded-lg shadow-md transition transform hover:-translate-y-0.5">
                    <i class="fas fa-box w-5 text-center"></i> Quản lý sản phẩm
                </a>
                <a href="import.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
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

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6 lg:p-8 overflow-x-hidden bg-gray-50">
            <form method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-lg p-6 lg:p-8 max-w-3xl mx-auto" id="addProductForm">
                <div class="flex justify-between items-center mb-8 pb-6 border-b">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="fas fa-plus-circle text-primary"></i> Thêm sản phẩm mới
                    </h2>
                    <a href="product.php" class="text-gray-600 hover:text-primary transition flex items-center gap-1">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>

                <!-- SQL Injection Warning -->
                <div id="sqlInjectionWarning" class="sql-injection-warning">
                    <i class="fas fa-shield-alt"></i>
                    <strong>Cảnh báo bảo mật:</strong> <span id="sqlInjectionMessage">Phát hiện ký tự không an toàn</span>
                </div>

                <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'; ?> border">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Tên sản phẩm -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tên sản phẩm <span class="text-red-500">*</span></label>
                        <input type="text" name="tenSP" id="product_name" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                        <div class="error-text" id="product_name-error">Tên sản phẩm phải có ít nhất 3 ký tự</div>
                    </div>

                    <!-- Danh mục -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Danh mục <span class="text-red-500">*</span></label>
                        <select name="danhmuc_id" id="category" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition bg-white">
                            <option value="">-- Chọn danh mục --</option>
                            <?php while($dm = $danhmucs->fetch_assoc()): ?>
                            <option value="<?php echo $dm['Danhmuc_id']; ?>"><?php echo htmlspecialchars($dm['Ten_danhmuc']); ?></option>
                            <?php endwhile; ?>
                        </select>
                        <div class="error-text" id="category-error">Vui lòng chọn danh mục</div>
                    </div>

                    <!-- Thương hiệu -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Thương hiệu</label>
                        <select name="thuonghieu_id" id="brand" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition bg-white">
                            <option value="0">-- Chọn thương hiệu --</option>
                            <?php while($th = $thuonghieus->fetch_assoc()): ?>
                            <option value="<?php echo $th['Ma_thuonghieu']; ?>"><?php echo htmlspecialchars($th['Ten_thuonghieu']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Nhà cung cấp -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nhà cung cấp <span class="text-red-500">*</span></label>
                        <select name="ncc_id" id="supplier" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition bg-white">
                            <option value="">-- Chọn NCC --</option>
                            <?php while($ncc = $nhacungcaps->fetch_assoc()): ?>
                            <option value="<?php echo $ncc['NCC_id']; ?>"><?php echo htmlspecialchars($ncc['Ten_NCC']); ?></option>
                            <?php endwhile; ?>
                        </select>
                        <div class="error-text" id="supplier-error">Vui lòng chọn nhà cung cấp</div>
                    </div>

                    <!-- % Lợi nhuận -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">% Lợi nhuận dự kiến <span class="text-red-500">*</span></label>
                        <input type="number" name="phan_tram_loi" id="profit" min="0" max="200" value="20" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                        <p class="text-xs text-gray-500 mt-1">Giá bán sẽ = Giá nhập trung bình × (1 + % Lợi nhuận/100)</p>
                        <div class="error-text" id="profit-error">Tỷ lệ lợi nhuận phải từ 0% đến 200%</div>
                    </div>

                    <!-- Hình ảnh -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">📷 Hình ảnh sản phẩm</label>
                        <input type="file" name="image" accept="image/*" id="product_image" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition" onchange="previewImage(this)">
                        <div class="error-text" id="image-error">Định dạng ảnh không hợp lệ</div>
                        <img id="imagePreview" class="mt-3 max-w-xs rounded-lg hidden" src="" alt="Preview">
                    </div>

                    <!-- Mô tả -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Mô tả</label>
                        <textarea name="mota" id="description" rows="3" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition" placeholder="Mô tả ngắn về sản phẩm..."></textarea>
                        <div class="error-text" id="description-error">Mô tả chứa ký tự không an toàn</div>
                    </div>
                </div>

                <!-- Lưu ý quan trọng -->
                <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800">
                    <i class="fas fa-info-circle"></i> <strong>Lưu ý:</strong> Sản phẩm này chưa có giá nhập/giá bán. 
                    Bạn cần vào trang <strong>Nhập hàng</strong> để nhập lô hàng đầu tiên, hệ thống sẽ tự động tính giá bán theo công thức đã thiết lập.
                </div>

                <!-- Nút hành động -->
                <div class="mt-8 flex gap-3 justify-end">
                    <button type="button" onclick="history.back()" class="px-6 py-2.5 rounded-lg bg-gray-500 text-white hover:bg-gray-600 transition font-medium">
                        <i class="fas fa-times mr-2"></i> Hủy
                    </button>
                    <button type="submit" name="save_product" id="submitBtn" class="px-6 py-2.5 rounded-lg bg-gradient-custom text-white font-medium hover:opacity-90 transition shadow-lg">
                        <i class="fas fa-save mr-2"></i> Lưu sản phẩm
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script>
        // SQL Injection Detection Patterns
        const sqlInjectionPatterns = [
            /\b(SELECT|INSERT|UPDATE|DELETE|DROP|TRUNCATE|ALTER|CREATE|EXEC|EXECUTE|UNION)\b/i,
            /(--|\/\*|\*\/|#)/,
            /\bOR\b\s+['"]?\d+['"]?\s*=\s*['"]?\d+|\bAND\b\s+['"]?\d+['"]?\s*=\s*['"]?\d+/i,
            /\bxp_\w+|sp_\w+/i,
            /\b(WAITFOR|BENCHMARK|SLEEP)\b/i,
            /%00|%27|%22/i
        ];

        function hasSQLInjection(value) {
            if (!value || !/[;'"\\<>%]/.test(value)) return false;
            for (let pattern of sqlInjectionPatterns) {
                if (pattern.test(value)) return true;
            }
            return false;
        }

        function showError(input, message, errorId) {
            const error = document.getElementById(errorId);
            if (error) {
                error.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
                error.style.display = 'block';
            }
            input.classList.add('input-invalid');
            input.classList.remove('border-gray-300');
        }

        function hideError(input, errorId) {
            const error = document.getElementById(errorId);
            if (error) error.style.display = 'none';
            input.classList.remove('input-invalid');
        }

        // Validate Product Name
        function validateProductName() {
            const input = document.getElementById('product_name');
            const value = input.value.trim();
            
            if (value.length === 0) {
                showError(input, 'Tên sản phẩm không được để trống', 'product_name-error');
                return false;
            }
            if (value.length < 3) {
                showError(input, 'Tên sản phẩm phải có ít nhất 3 ký tự', 'product_name-error');
                return false;
            }
            if (value.length > 200) {
                showError(input, 'Tên sản phẩm không được vượt quá 200 ký tự', 'product_name-error');
                return false;
            }
            if (hasSQLInjection(value)) {
                showError(input, 'Tên sản phẩm chứa ký tự không an toàn', 'product_name-error');
                return false;
            }
            hideError(input, 'product_name-error');
            return true;
        }

        // Validate Category
        function validateCategory() {
            const select = document.getElementById('category');
            const value = select.value;
            
            if (value === '' || value == '0') {
                showError(select, 'Vui lòng chọn danh mục', 'category-error');
                return false;
            }
            hideError(select, 'category-error');
            return true;
        }

        // Validate Supplier
        function validateSupplier() {
            const select = document.getElementById('supplier');
            const value = select.value;
            
            if (value === '' || value == '0') {
                showError(select, 'Vui lòng chọn nhà cung cấp', 'supplier-error');
                return false;
            }
            hideError(select, 'supplier-error');
            return true;
        }

        // Validate Profit
        function validateProfit() {
            const input = document.getElementById('profit');
            const value = parseFloat(input.value);
            
            if (isNaN(value) || value < 0 || value > 200) {
                showError(input, 'Tỷ lệ lợi nhuận phải từ 0% đến 200%', 'profit-error');
                return false;
            }
            hideError(input, 'profit-error');
            return true;
        }

        // Validate Description
        function validateDescription() {
            const input = document.getElementById('description');
            const value = input.value.trim();
            
            if (value.length > 0 && hasSQLInjection(value)) {
                showError(input, 'Mô tả chứa ký tự không an toàn', 'description-error');
                return false;
            }
            hideError(input, 'description-error');
            return true;
        }

        // Validate Image
        function validateImage() {
            const input = document.getElementById('product_image');
            if (input.files.length > 0) {
                const file = input.files[0];
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    showError(input, 'Định dạng ảnh không hợp lệ! Chỉ chấp nhận: JPG, PNG, GIF, WEBP', 'image-error');
                    return false;
                }
                if (file.size > 5 * 1024 * 1024) {
                    showError(input, 'Kích thước ảnh không được vượt quá 5MB', 'image-error');
                    return false;
                }
            }
            hideError(input, 'image-error');
            return true;
        }

        // Check all validations
        function checkFormValidity() {
            const isValid = validateProductName() && 
                           validateCategory() && 
                           validateSupplier() && 
                           validateProfit() && 
                           validateDescription() && 
                           validateImage();
            
            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.disabled = !isValid;
                submitBtn.classList.toggle('opacity-50', !isValid);
                submitBtn.classList.toggle('cursor-not-allowed', !isValid);
            }
            return isValid;
        }

        // Add event listeners
        document.getElementById('product_name')?.addEventListener('input', function() {
            validateProductName();
            checkFormValidity();
        });
        
        document.getElementById('category')?.addEventListener('change', function() {
            validateCategory();
            checkFormValidity();
        });
        
        document.getElementById('supplier')?.addEventListener('change', function() {
            validateSupplier();
            checkFormValidity();
        });
        
        document.getElementById('profit')?.addEventListener('input', function() {
            validateProfit();
            checkFormValidity();
        });
        
        document.getElementById('description')?.addEventListener('input', function() {
            validateDescription();
            checkFormValidity();
        });
        
        document.getElementById('product_image')?.addEventListener('change', function() {
            validateImage();
            checkFormValidity();
        });

        // Form submit validation
        const form = document.getElementById('addProductForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (!checkFormValidity()) {
                    e.preventDefault();
                    const firstError = form.querySelector('.input-invalid');
                    if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        }

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
            if (confirm('Bạn có chắc muốn đăng xuất?')) {
                window.location.href = 'logout.php';
            }
        }
        
        // Initial validation
        setTimeout(checkFormValidity, 100);
    </script>
</body>
</html>