<?php
session_start();
require_once __DIR__ . '/../control/connect.php';
require_once __DIR__ . '/../control/function.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

// Lấy ID sản phẩm
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($product_id == 0) {
    header('Location: product.php');
    exit();
}

// Lấy thông tin sản phẩm
$product = getProductById($conn, $product_id);
if (!$product) {
    header('Location: product.php');
    exit();
}

// Lấy danh sách danh mục, thương hiệu, nhà cung cấp
$categories = getCategories($conn);
$brands = getBrands($conn);
$suppliers = getSuppliers($conn);

$error = '';
$success = '';

// Xử lý xóa hình ảnh
if (isset($_POST['remove_image'])) {
    $target_dir = "../";
    if ($product['image_url'] && file_exists($target_dir . $product['image_url'])) {
        unlink($target_dir . $product['image_url']);
    }
    
    $update = $conn->prepare("UPDATE sanpham SET image_url = NULL WHERE SanPham_id = ?");
    $update->bind_param("i", $product_id);
    if ($update->execute()) {
        $success = 'Đã xóa hình ảnh thành công!';
        $product = getProductById($conn, $product_id);
    } else {
        $error = 'Có lỗi xảy ra khi xóa hình ảnh!';
    }
}

// Xử lý cập nhật sản phẩm
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    // Lấy tỷ lệ lợi nhuận từ form (dạng thập phân)
    $phan_tram_loi_nhuan = floatval($_POST['phan_tram_loi_nhuan']); // Giữ nguyên dạng thập phân
    
    $data = [
        'ten_sp' => $_POST['ten_sp'],
        'danhmuc_id' => $_POST['danhmuc_id'] ?: null,
        'ncc_id' => $_POST['ncc_id'] ?: null,
        'thuonghieu_id' => $_POST['thuonghieu_id'] ?: null,
        'mota' => $_POST['mota'],
        'phan_tram_loi_nhuan' => $phan_tram_loi_nhuan,
        'trang_thai' => $_POST['trang_thai'],
        'don_vi' => $_POST['don_vi']
    ];
    
    // Tính giá bán từ giá nhập và tỷ lệ lợi nhuận
    $data['gia_ban'] = $product['GiaNhapTB'] * (1 + $data['phan_tram_loi_nhuan']);
    
    // Xử lý upload hình mới
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_extension, $allowed_types)) {
            $file_name = time() . '_' . uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $file_name;
            
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                // Xóa hình cũ nếu có
                if ($product['image_url'] && file_exists($target_dir . $product['image_url'])) {
                    unlink($target_dir . $product['image_url']);
                }
                $data['image_url'] = $file_name;
            } else {
                $error = 'Lỗi upload hình ảnh!';
            }
        } else {
            $error = 'Chỉ chấp nhận file ảnh (jpg, jpeg, png, gif, webp)!';
        }
    } else {
        $data['image_url'] = $product['image_url'];
    }
    
    if (empty($error)) {
        // Cập nhật sản phẩm
        $sql = "UPDATE sanpham SET 
                TenSP = ?, 
                Danhmuc_id = ?, 
                NCC_id = ?, 
                Ma_thuonghieu = ?, 
                MoTa = ?, 
                image_url = ?, 
                GiaBan = ?, 
                PhanTramLoiNhuan = ?, 
                TrangThai = ? 
                WHERE SanPham_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siiisssdsi", 
            $data['ten_sp'],
            $data['danhmuc_id'],
            $data['ncc_id'],
            $data['thuonghieu_id'],
            $data['mota'],
            $data['image_url'],
            $data['gia_ban'],
            $data['phan_tram_loi_nhuan'],
            $data['trang_thai'],
            $product_id
        );
        
        if ($stmt->execute()) {
            $success = 'Cập nhật sản phẩm thành công!';
            $product = getProductById($conn, $product_id);
        } else {
            $error = 'Có lỗi xảy ra khi cập nhật!';
        }
    }
}

// Hiển thị tỷ lệ lợi nhuận dưới dạng thập phân
$phan_tram_hien_thi = $product['PhanTramLoiNhuan'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa sản phẩm - <?php echo htmlspecialchars($product['TenSP']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .bg-gradient-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .product-img-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #374151;
        }
        .form-control {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            ring: 2px solid #667eea;
        }
        .form-control[readonly] {
            background-color: #f3f4f6;
            cursor: not-allowed;
        }
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        .info-badge {
            background-color: #e5e7eb;
            color: #374151;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 10px;
        }
        .price-calculated {
            background-color: #f3f4f6;
            border-color: #d1d5db;
            color: #1f2937;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-custom px-6 py-4 flex justify-between items-center">
                <h1 class="text-xl font-bold text-white">
                    <i class="fas fa-edit mr-2"></i>Sửa sản phẩm
                </h1>
                <a href="product.php" class="text-white hover:text-gray-200 transition">
                    <i class="fas fa-times text-xl"></i>
                </a>
            </div>
            
            <div class="p-6">
                <!-- Thông báo -->
                <?php if ($success): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded flex items-center justify-between">
                        <div>
                            <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
                        </div>
                        <a href="product.php" class="text-green-700 underline text-sm">Quay lại danh sách</a>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Form sửa sản phẩm -->
                <form method="POST" enctype="multipart/form-data" class="space-y-4" id="productForm">
                    <input type="hidden" name="update_product" value="1">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Tên sản phẩm <span class="text-red-500">*</span></label>
                            <input type="text" name="ten_sp" value="<?php echo htmlspecialchars($product['TenSP']); ?>" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mã sản phẩm</label>
                            <input type="text" value="SP<?php echo str_pad($product['SanPham_id'], 4, '0', STR_PAD_LEFT); ?>" readonly class="form-control bg-gray-100">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Danh mục</label>
                            <select name="danhmuc_id" class="form-control">
                                <option value="">-- Chọn danh mục --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['Danhmuc_id']; ?>" 
                                        <?php echo ($product['Danhmuc_id'] == $cat['Danhmuc_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['Ten_danhmuc']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Đơn vị tính</label>
                            <select name="don_vi" class="form-control">
                                <option value="cái" <?php echo ($product['DonVi'] ?? 'cái') == 'cái' ? 'selected' : ''; ?>>Cái</option>
                                <option value="đôi" <?php echo ($product['DonVi'] ?? '') == 'đôi' ? 'selected' : ''; ?>>Đôi</option>
                                <option value="bộ" <?php echo ($product['DonVi'] ?? '') == 'bộ' ? 'selected' : ''; ?>>Bộ</option>
                                <option value="chiếc" <?php echo ($product['DonVi'] ?? '') == 'chiếc' ? 'selected' : ''; ?>>Chiếc</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Thương hiệu</label>
                            <select name="thuonghieu_id" class="form-control">
                                <option value="">-- Chọn thương hiệu --</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?php echo $brand['Ma_thuonghieu']; ?>" 
                                        <?php echo ($product['Ma_thuonghieu'] == $brand['Ma_thuonghieu']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($brand['Ten_thuonghieu']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nhà cung cấp</label>
                            <select name="ncc_id" class="form-control">
                                <option value="">-- Chọn nhà cung cấp --</option>
                                <?php foreach ($suppliers as $sup): ?>
                                    <option value="<?php echo $sup['NCC_id']; ?>" 
                                        <?php echo ($product['NCC_id'] == $sup['NCC_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sup['Ten_NCC']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Mô tả sản phẩm</label>
                        <textarea name="mota" class="form-control" rows="4"><?php echo htmlspecialchars($product['MoTa'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4">
                        <div class="form-group">
                            <label class="form-label">
                                Giá nhập (VNĐ)
                                <span class="info-badge">Không thể sửa</span>
                            </label>
                            <input type="text" value="<?php echo number_format($product['GiaNhapTB'], 0, ',', '.'); ?>đ" readonly class="form-control bg-gray-100">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tỷ lệ lợi nhuận (dạng thập phân)</label>
                            <input type="number" name="phan_tram_loi_nhuan" id="phan_tram" value="<?php echo $phan_tram_hien_thi; ?>" step="0.01" class="form-control" oninput="tinhGiaBan()">
                            <p class="text-xs text-blue-500 mt-1">💡 Nhập dạng thập phân, ví dụ: 0.1 = 10%, 0.15 = 15%, 0.2 = 20%</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                Giá bán (VNĐ)
                                <span class="info-badge">Tự động tính</span>
                            </label>
                            <input type="text" id="gia_ban" value="<?php echo number_format($product['GiaBan'], 0, ',', '.'); ?>đ" readonly class="form-control price-calculated" style="background-color: #f3f4f6; color: #1f2937;">
                            <p class="text-xs text-gray-500 mt-1">* Giá bán = Giá nhập × (1 + tỷ lệ lợi nhuận)</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">
                                Số lượng tồn kho
                                <span class="info-badge">Không thể sửa</span>
                            </label>
                            <input type="text" value="<?php echo $product['SoLuongTon']; ?> sản phẩm" readonly class="form-control bg-gray-100">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Trạng thái</label>
                            <select name="trang_thai" class="form-control">
                                <option value="1" <?php echo ($product['TrangThai'] == 1) ? 'selected' : ''; ?>>Đang bán (Hiển thị)</option>
                                <option value="0" <?php echo ($product['TrangThai'] == 0) ? 'selected' : ''; ?>>Ẩn (Không bán)</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Hình ảnh -->
                    <div class="border-t border-gray-200 pt-4 mt-2">
                        <label class="form-label">Hình ảnh sản phẩm</label>
                        
                        <?php if ($product['image_url']): ?>
                            <div class="mb-3 p-4 bg-gray-50 rounded-lg flex items-center space-x-4">
                                <img src="../<?php echo $product['image_url']; ?>" class="product-img-preview" alt="Product image">
                                <div>
                                    <p class="text-sm text-gray-600">Hình ảnh hiện tại</p>
                                    <button type="submit" name="remove_image" value="1" class="mt-2 text-red-500 hover:text-red-700 text-sm flex items-center" onclick="return confirm('Bạn có chắc muốn xóa hình ảnh này?')">
                                        <i class="fas fa-trash-alt mr-1"></i>Bỏ hình ảnh
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="mb-3 p-4 bg-gray-50 rounded-lg text-center text-gray-500">
                                <i class="fas fa-image text-3xl mb-2 block"></i>
                                <p>Chưa có hình ảnh</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Thay đổi hình ảnh</label>
                            <input type="file" name="image" accept="image/*" class="form-control">
                            <p class="text-xs text-gray-500 mt-1">Để trống nếu không muốn thay đổi. Hỗ trợ: JPG, PNG, GIF, WEBP</p>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 mt-4">
                        <a href="product.php" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                            <i class="fas fa-times mr-2"></i>Hủy
                        </a>
                        <button type="submit" class="px-4 py-2 bg-gradient-custom text-white rounded-lg hover:opacity-90 transition">
                            <i class="fas fa-save mr-2"></i>Cập nhật sản phẩm
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Giữ giá nhập gốc để tính giá bán
        const giaNhapGoc = <?php echo $product['GiaNhapTB']; ?>;
        
        function tinhGiaBan() {
            let phanTram = parseFloat(document.getElementById('phan_tram').value);
            
            // Kiểm tra giá trị hợp lệ
            if (isNaN(phanTram)) {
                phanTram = 0;
            }
            
            if (giaNhapGoc > 0) {
                // Công thức tính giá bán: Giá nhập × (1 + tỷ lệ lợi nhuận)
                let giaBan = giaNhapGoc * (1 + phanTram);
                // Làm tròn và hiển thị
                document.getElementById('gia_ban').value = Math.round(giaBan).toLocaleString('vi-VN') + 'đ';
            } else {
                document.getElementById('gia_ban').value = '0đ';
            }
        }
        
        // Tự động tính khi trang load
        document.addEventListener('DOMContentLoaded', function() {
            tinhGiaBan();
        });
    </script>
</body>
</html>