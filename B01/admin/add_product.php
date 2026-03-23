<?php
session_start();
require_once __DIR__ . '/../control/connect.php';

// Kiểm tra kết nối database
if (!isset($conn) || $conn->connect_error) {
    die("Lỗi kết nối database: " . ($conn->connect_error ?? "Không thể kết nối"));
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Quản trị viên';
$admin_role = $_SESSION['admin_role'] ?? '';
$admin_username = $_SESSION['admin_username'] ?? '';

// Lấy danh sách danh mục và thương hiệu và nhà cung cấp cho dropdown
$categories = [];
$brands = [];
$suppliers = [];

// Lấy danh mục
$sql_categories = "SELECT Danhmuc_id, Ten_danhmuc FROM danhmuc ORDER BY Ten_danhmuc";
$result_categories = $conn->query($sql_categories);
if ($result_categories && $result_categories->num_rows > 0) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Lấy thương hiệu
$sql_brands = "SELECT Ma_thuonghieu, Ten_thuonghieu FROM thuonghieu ORDER BY Ten_thuonghieu";
$result_brands = $conn->query($sql_brands);
if ($result_brands && $result_brands->num_rows > 0) {
    while ($row = $result_brands->fetch_assoc()) {
        $brands[] = $row;
    }
}

// Lấy nhà cung cấp
$sql_suppliers = "SELECT NCC_id, Ten_NCC FROM nhacungcap ORDER BY Ten_NCC";
$result_suppliers = $conn->query($sql_suppliers);
if ($result_suppliers && $result_suppliers->num_rows > 0) {
    while ($row = $result_suppliers->fetch_assoc()) {
        $suppliers[] = $row;
    }
}

// Xử lý thêm sản phẩm
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ten_sp = trim($_POST['TenSP'] ?? '');
    $danhmuc_id = $_POST['Danhmuc_id'] ?? 0;
    $thuonghieu_id = $_POST['Ma_thuonghieu'] ?? 0;
    $ncc_id = $_POST['NCC_id'] ?? 0;
    $gia_nhap = floatval($_POST['GiaNhap'] ?? 0);
    $gia_ban = floatval($_POST['GiaBan'] ?? 0);
    $so_luong_ton = intval($_POST['SoLuongTon'] ?? 0);
    $mo_ta = trim($_POST['MoTa'] ?? '');
    $trang_thai = intval($_POST['TrangThai'] ?? 1);
    
    // Tính phần trăm lợi nhuận (nếu có giá nhập)
    $phan_tram_loi_nhuan = 0;
    if ($gia_nhap > 0 && $gia_ban > 0) {
        $phan_tram_loi_nhuan = ($gia_ban - $gia_nhap) / $gia_nhap;
    }

    // Validate
    if (empty($ten_sp)) {
        $error = 'Vui lòng nhập tên sản phẩm';
    } elseif ($danhmuc_id <= 0) {
        $error = 'Vui lòng chọn danh mục';
    } elseif ($thuonghieu_id <= 0) {
        $error = 'Vui lòng chọn thương hiệu';
    } elseif ($ncc_id <= 0) {
        $error = 'Vui lòng chọn nhà cung cấp';
    } elseif ($gia_ban <= 0) {
        $error = 'Giá bán phải lớn hơn 0';
    } else {
        // Xử lý upload ảnh
        $image_url = '';
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/products/';
            
            // Tạo thư mục nếu chưa tồn tại
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                    $image_url = '/uploads/products/' . $new_filename; // Thay đổi đường dẫn phù hợp với cấu trúc
                } else {
                    $error = 'Có lỗi xảy ra khi tải ảnh lên. Vui lòng thử lại.';
                }
            } else {
                $error = 'Chỉ chấp nhận file ảnh: JPG, JPEG, PNG, GIF, WEBP';
            }
        }
        
        if (empty($error)) {
            // Thêm sản phẩm vào database (không có MoTaChiTiet và created_at)
            $sql = "INSERT INTO sanpham (TenSP, Danhmuc_id, Ma_thuonghieu, GiaNhapTB, GiaBan, SoLuongTon, TrangThai, PhanTramLoiNhuan, image_url, NCC_id, MoTa) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            
            if ($stmt === false) {
                $error = "Lỗi prepare statement: " . $conn->error;
            } else {
                $stmt->bind_param("siiiddiisss", 
                    $ten_sp, 
                    $danhmuc_id, 
                    $thuonghieu_id, 
                    $gia_nhap, 
                    $gia_ban, 
                    $so_luong_ton, 
                    $trang_thai, 
                    $phan_tram_loi_nhuan, 
                    $image_url, 
                    $ncc_id, 
                    $mo_ta
                );
                
                if ($stmt->execute()) {
                    $success = 'Thêm sản phẩm thành công!';
                    // Reset form
                    $_POST = [];
                    // Reset file input (không thể reset bằng PHP, chỉ redirect)
                    echo '<script>window.location.href = "add_product.php?success=1";</script>';
                    exit();
                } else {
                    $error = 'Có lỗi xảy ra: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// Kiểm tra success parameter để hiển thị thông báo
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success = 'Thêm sản phẩm thành công!';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm sản phẩm - Admin</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#667eea',
                        secondary: '#764ba2',
                    },
                    backgroundImage: {
                        'gradient-custom': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    }
                }
            }
        }
    </script>
    <style>
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-800">

    <!-- HEADER -->
    <header class="bg-white shadow-md sticky top-0 z-50 h-[70px] flex items-center w-full">
        <div class="w-full px-6 flex justify-between items-center">
            <h1 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-custom">
                NVBPlay Admin Panel
            </h1>
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-custom flex items-center justify-center text-white font-bold shadow-lg">
                        <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">
                            <?php echo htmlspecialchars($admin_name); ?>
                            <?php if ($admin_role === 'admin'): ?>
                                <span class="ml-2 text-xs bg-gradient-custom text-white px-2 py-0.5 rounded-full">Admin</span>
                            <?php else: ?>
                                <span class="ml-2 text-xs bg-green-500 text-white px-2 py-0.5 rounded-full">Staff</span>
                            <?php endif; ?>
                        </p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($admin_username); ?></p>
                    </div>
                </div>
                <button onclick="logout()" class="flex items-center gap-2 text-red-500 hover:text-red-700 transition font-medium">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </button>
            </div>
        </div>
    </header>

    <!-- CONTAINER CHÍNH -->
    <div class="flex w-full min-h-[calc(100vh-70px)]">
        
        <!-- SIDEBAR -->
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
            <div class="bg-white rounded-xl shadow-lg p-6 lg:p-8">
                <!-- Page Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 pb-6 border-b-2 border-gray-100 gap-4">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="fas fa-plus-circle text-primary"></i> Thêm sản phẩm mới
                    </h2>
                    <a href="product.php" class="bg-gray-500 hover:bg-gray-600 text-white px-5 py-2 rounded-lg font-medium transition flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i> Quay lại danh sách
                    </a>
                </div>

                <!-- Hiển thị thông báo -->
                <?php if ($error): ?>
                    <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg" role="alert">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg" role="alert">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            <span><?php echo htmlspecialchars($success); ?></span>
                        </div>
                        <div class="mt-3">
                            <a href="product.php" class="text-green-700 underline font-medium">Xem danh sách sản phẩm</a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Form thêm sản phẩm -->
                <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Cột trái -->
                        <div class="space-y-5">
                            <!-- Tên sản phẩm -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Tên sản phẩm <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="TenSP" value="<?php echo htmlspecialchars($_POST['TenSP'] ?? ''); ?>" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition"
                                       placeholder="Nhập tên sản phẩm" required>
                            </div>

                            <!-- Danh mục -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Danh mục <span class="text-red-500">*</span>
                                </label>
                                <select name="Danhmuc_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition" required>
                                    <option value="">-- Chọn danh mục --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['Danhmuc_id']; ?>" <?php echo (($_POST['Danhmuc_id'] ?? '') == $cat['Danhmuc_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['Ten_danhmuc']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Thương hiệu -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Thương hiệu <span class="text-red-500">*</span>
                                </label>
                                <select name="Ma_thuonghieu" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition" required>
                                    <option value="">-- Chọn thương hiệu --</option>
                                    <?php foreach ($brands as $brand): ?>
                                        <option value="<?php echo $brand['Ma_thuonghieu']; ?>" <?php echo (($_POST['Ma_thuonghieu'] ?? '') == $brand['Ma_thuonghieu']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($brand['Ten_thuonghieu']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Nhà cung cấp -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Nhà cung cấp <span class="text-red-500">*</span>
                                </label>
                                <select name="NCC_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition" required>
                                    <option value="">-- Chọn nhà cung cấp --</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?php echo $supplier['NCC_id']; ?>" <?php echo (($_POST['NCC_id'] ?? '') == $supplier['NCC_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($supplier['Ten_NCC']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Giá nhập -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Giá nhập
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2.5 text-gray-500">₫</span>
                                    <input type="number" name="GiaNhap" value="<?php echo htmlspecialchars($_POST['GiaNhap'] ?? ''); ?>" 
                                           class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition"
                                           placeholder="0" step="1000" min="0">
                                </div>
                            </div>

                            <!-- Giá bán -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Giá bán <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2.5 text-gray-500">₫</span>
                                    <input type="number" name="GiaBan" value="<?php echo htmlspecialchars($_POST['GiaBan'] ?? ''); ?>" 
                                           class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition"
                                           placeholder="0" step="1000" min="0" required>
                                </div>
                            </div>

                            <!-- Số lượng tồn -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Số lượng tồn kho
                                </label>
                                <input type="number" name="SoLuongTon" value="<?php echo htmlspecialchars($_POST['SoLuongTon'] ?? '0'); ?>" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition"
                                       placeholder="0" min="0">
                            </div>

                            <!-- Trạng thái -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Trạng thái
                                </label>
                                <select name="TrangThai" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                                    <option value="1" <?php echo (($_POST['TrangThai'] ?? '1') == 1) ? 'selected' : ''; ?>>Đang bán</option>
                                    <option value="0" <?php echo (($_POST['TrangThai'] ?? '') == 0) ? 'selected' : ''; ?>>Ngừng bán</option>
                                </select>
                            </div>
                        </div>

                        <!-- Cột phải -->
                        <div class="space-y-5">
                            <!-- Ảnh sản phẩm -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Hình ảnh sản phẩm
                                </label>
                                <div class="mt-1 flex items-center gap-4">
                                    <div class="flex-1">
                                        <input type="file" name="product_image" id="product_image" accept="image/*" 
                                               class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-secondary transition cursor-pointer"
                                               onchange="previewImage(this)">
                                    </div>
                                    <div id="imagePreviewContainer" class="hidden">
                                        <img id="imagePreview" class="image-preview" alt="Preview">
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Chấp nhận JPG, JPEG, PNG, GIF, WEBP.</p>
                            </div>

                            <!-- Mô tả -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Mô tả sản phẩm
                                </label>
                                <textarea name="MoTa" rows="6" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition"
                                          placeholder="Mô tả chi tiết về sản phẩm..."><?php echo htmlspecialchars($_POST['MoTa'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                        <a href="product.php" class="px-6 py-2.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition font-medium">
                            Hủy bỏ
                        </a>
                        <button type="submit" class="px-6 py-2.5 rounded-lg bg-gradient-custom text-white font-medium shadow-lg hover:shadow-xl transition transform hover:-translate-y-0.5">
                            <i class="fas fa-save mr-2"></i> Thêm sản phẩm
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Preview image before upload
        function previewImage(input) {
            const previewContainer = document.getElementById('imagePreviewContainer');
            const preview = document.getElementById('imagePreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                previewContainer.classList.add('hidden');
                preview.src = '';
            }
        }

        // Logout function
        function logout() {
            if (confirm('Bạn có chắc muốn đăng xuất?')) {
                window.location.href = 'logout.php';
            }
        }
    </script>
</body>
</html>