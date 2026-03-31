<?php
session_start();
require_once __DIR__ . '/../control/connect.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Quản trị viên';
$admin_username = $_SESSION['admin_username'] ?? '';

// Hàm tạo slug
function createSlug($str) {
    $str = trim(mb_strtolower($str, 'UTF-8'));
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    $str = preg_replace('/[\s-]+/', '-', $str);
    return trim($str, '-');
}

// Xử lý thêm danh mục
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $ten_danhmuc = trim($_POST['ten_danhmuc']);
    $slug = createSlug($ten_danhmuc);
    
    // Xử lý upload ảnh
    $imageUrl = '';
    if (!empty($_FILES['image_url']['name']) && $_FILES['image_url']['error'] === 0) {
        $uploadDir = __DIR__ . '/../img/icons/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $ext = pathinfo($_FILES['image_url']['name'], PATHINFO_EXTENSION);
        $newName = 'CAT-' . date('YmdHis') . '-' . uniqid() . '.' . $ext;
        
        if (move_uploaded_file($_FILES['image_url']['tmp_name'], $uploadDir . $newName)) {
            $imageUrl = 'img/icons/' . $newName;
        }
    }
    
    if (!empty($ten_danhmuc)) {
        $stmt = $conn->prepare("INSERT INTO danhmuc (Ten_danhmuc, slug, image_url) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $ten_danhmuc, $slug, $imageUrl);
        if ($stmt->execute()) {
            $message = 'Thêm danh mục thành công!';
            $messageType = 'success';
        } else {
            $message = 'Có lỗi xảy ra khi lưu vào database!';
            $messageType = 'error';
        }
    } else {
        $message = 'Vui lòng nhập tên danh mục!';
        $messageType = 'error';
    }
}

// Xử lý sửa danh mục
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $id = intval($_POST['category_id']);
    $ten_danhmuc = trim($_POST['ten_danhmuc']);
    $slug = createSlug($ten_danhmuc);
    
    if (!empty($ten_danhmuc)) {
        // Kiểm tra xem có upload ảnh mới không
        if (!empty($_FILES['image_url']['name']) && $_FILES['image_url']['error'] === 0) {
            $uploadDir = __DIR__ . '/../img/icons/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $ext = pathinfo($_FILES['image_url']['name'], PATHINFO_EXTENSION);
            $newName = 'CAT-' . date('YmdHis') . '-' . uniqid() . '.' . $ext;
            
            if (move_uploaded_file($_FILES['image_url']['tmp_name'], $uploadDir . $newName)) {
                $imageUrl = 'img/icons/' . $newName;
                // Có ảnh mới -> Update cả tên, slug và ảnh
                $stmt = $conn->prepare("UPDATE danhmuc SET Ten_danhmuc = ?, slug = ?, image_url = ? WHERE Danhmuc_id = ?");
                $stmt->bind_param("sssi", $ten_danhmuc, $slug, $imageUrl, $id);
            }
        } else {
            // Không có ảnh mới -> Chỉ update tên và slug
            $stmt = $conn->prepare("UPDATE danhmuc SET Ten_danhmuc = ?, slug = ? WHERE Danhmuc_id = ?");
            $stmt->bind_param("ssi", $ten_danhmuc, $slug, $id);
        }
        
        if ($stmt->execute()) {
            $message = 'Cập nhật danh mục thành công!';
            $messageType = 'success';
        } else {
            $message = 'Có lỗi xảy ra!';
            $messageType = 'error';
        }
    } else {
        $message = 'Vui lòng nhập tên danh mục!';
        $messageType = 'error';
    }
}

// Xử lý xóa danh mục
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Kiểm tra danh mục có sản phẩm không
    $check = $conn->prepare("SELECT COUNT(*) as total FROM sanpham WHERE Danhmuc_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $count = $check->get_result()->fetch_assoc()['total'];
    
    if ($count > 0) {
        $message = 'Không thể xóa danh mục này vì đang có sản phẩm!';
        $messageType = 'error';
    } else {
        $stmt = $conn->prepare("DELETE FROM danhmuc WHERE Danhmuc_id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'Xóa danh mục thành công!';
            $messageType = 'success';
        } else {
            $message = 'Có lỗi xảy ra!';
            $messageType = 'error';
        }
    }
}

// Lấy danh sách danh mục
$categories = $conn->query("SELECT * FROM danhmuc ORDER BY Danhmuc_id DESC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý danh mục - Admin</title>
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
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .animate-slide-in { animation: slideIn 0.3s ease-out; }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-800">

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
                <button onclick="logout()" class="bg-gradient-custom text-white font-semibold px-4 py-2 rounded-lg hover:opacity-90 transition">
                    <i class="fas fa-sign-out-alt mr-2"></i>Đăng xuất
                </button>
            </div>
        </div>
    </header>

    <div class="flex w-full min-h-[calc(100vh-70px)]">
        
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
                <a href="categories.php" class="flex items-center gap-3 px-4 py-3 bg-gradient-custom text-white rounded-lg shadow-md">
                    <i class="fas fa-list w-5 text-center"></i> Quản lý danh mục
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

        <main class="flex-1 p-6 lg:p-8 overflow-x-hidden bg-gray-50">
            <div class="bg-white rounded-xl shadow-lg p-6 lg:p-8 min-h-full">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 pb-6 border-b-2 border-gray-100 gap-4">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="fas fa-list text-primary"></i> Quản lý danh mục sản phẩm
                    </h2>
                    <button onclick="openAddModal()" class="bg-gradient-custom hover:opacity-90 text-white px-6 py-2.5 rounded-lg font-medium shadow-lg transition flex items-center gap-2">
                        <i class="fas fa-plus"></i> Thêm danh mục
                    </button>
                </div>

                <?php if (isset($message)): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'; ?> border flex items-center gap-3 animate-slide-in">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gradient-custom text-white">
                                <th class="p-4 font-medium text-sm">Hình ảnh</th>
                                <th class="p-4 font-medium text-sm">ID</th>
                                <th class="p-4 font-medium text-sm">Tên danh mục</th>
                                <th class="p-4 font-medium text-sm">Slug</th>
                                <th class="p-4 font-medium text-sm">Số sản phẩm</th>
                                <th class="p-4 font-medium text-sm text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php while($cat = $categories->fetch_assoc()): 
                                $count_sql = $conn->prepare("SELECT COUNT(*) as total FROM sanpham WHERE Danhmuc_id = ?");
                                $count_sql->bind_param("i", $cat['Danhmuc_id']);
                                $count_sql->execute();
                                $product_count = $count_sql->get_result()->fetch_assoc()['total'];
                            ?>
                            <tr class="hover:bg-blue-50/50 transition">
                                <td class="p-4">
                                    <?php if (!empty($cat['image_url'])): ?>
                                        <img src="../<?php echo $cat['image_url']; ?>" alt="<?php echo htmlspecialchars($cat['Ten_danhmuc']); ?>" class="w-12 h-12 object-cover rounded-lg border border-gray-200">
                                    <?php else: ?>
                                        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-image text-gray-400 text-xl"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-gray-600"><?php echo $cat['Danhmuc_id']; ?></td>
                                <td class="p-4 font-medium text-gray-800"><?php echo htmlspecialchars($cat['Ten_danhmuc']); ?></td>
                                <td class="p-4 text-gray-500 font-mono text-sm"><?php echo htmlspecialchars($cat['slug'] ?? '-'); ?></td>
                                <td class="p-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                        <?php echo $product_count; ?> sản phẩm
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button onclick="editCategory(<?php echo $cat['Danhmuc_id']; ?>, '<?php echo addslashes($cat['Ten_danhmuc']); ?>', '<?php echo isset($cat['image_url']) ? addslashes($cat['image_url']) : ''; ?>')" 
                                                class="w-8 h-8 rounded bg-yellow-400 hover:bg-yellow-500 text-gray-800 flex items-center justify-center transition" title="Sửa">
                                            <i class="fas fa-edit text-xs"></i>
                                        </button>
                                        <?php if ($product_count == 0): ?>
                                        <a href="?action=delete&id=<?php echo $cat['Danhmuc_id']; ?>" 
                                           onclick="return confirm('Xóa danh mục này?')"
                                           class="w-8 h-8 rounded bg-red-500 hover:bg-red-600 text-white flex items-center justify-center transition" title="Xóa">
                                            <i class="fas fa-trash text-xs"></i>
                                        </a>
                                        <?php else: ?>
                                        <span class="w-8 h-8 rounded bg-gray-300 text-gray-500 flex items-center justify-center cursor-not-allowed" title="Không thể xóa vì có sản phẩm">
                                            <i class="fas fa-trash text-xs"></i>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                             </tr>
                            <?php endwhile; ?>
                        </tbody>
                     </table>
                </div>
            </div>
        </main>
    </div>

    <div id="addModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[1000] backdrop-blur-sm">
        <div class="bg-white rounded-xl w-full max-w-md mx-4 shadow-2xl animate-slide-in">
            <div class="bg-gradient-custom text-white p-5 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-bold flex items-center gap-2"><i class="fas fa-plus"></i> Thêm danh mục</h3>
                <button onclick="closeModal('addModal')" class="text-white hover:text-gray-200 text-xl">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data" class="p-6">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tên danh mục <span class="text-red-500">*</span></label>
                    <input type="text" name="ten_danhmuc" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                    <p class="text-xs text-gray-500 mt-1">Ví dụ: Vợt cầu lông, Phụ kiện</p>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Hình ảnh danh mục</label>
                    <input type="file" name="image_url" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="previewAddImage(this)">
                    <div class="mt-3 hidden" id="addImgPreviewContainer">
                        <img id="addImgPreview" src="" class="h-24 w-auto rounded-lg border border-gray-200 shadow-sm">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeModal('addModal')" class="px-5 py-2.5 rounded-lg bg-gray-500 text-white hover:bg-gray-600 transition">Hủy</button>
                    <button type="submit" name="add_category" class="px-5 py-2.5 rounded-lg bg-gradient-custom text-white hover:opacity-90 transition shadow-lg">Thêm danh mục</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[1000] backdrop-blur-sm">
        <div class="bg-white rounded-xl w-full max-w-md mx-4 shadow-2xl animate-slide-in">
            <div class="bg-gradient-custom text-white p-5 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-bold flex items-center gap-2"><i class="fas fa-edit"></i> Sửa danh mục</h3>
                <button onclick="closeModal('editModal')" class="text-white hover:text-gray-200 text-xl">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data" class="p-6">
                <input type="hidden" name="category_id" id="edit_category_id">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tên danh mục <span class="text-red-500">*</span></label>
                    <input type="text" name="ten_danhmuc" id="edit_category_name" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Đổi hình ảnh (để trống nếu không đổi)</label>
                    <input type="file" name="image_url" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="previewEditImage(this)">
                    <div class="mt-3 flex gap-4 items-center">
                        <div id="currentImgContainer" class="hidden">
                            <p class="text-xs text-gray-500 mb-1">Ảnh hiện tại:</p>
                            <img id="currentImg" src="" class="h-16 w-auto rounded-lg border border-gray-200 opacity-70">
                        </div>
                        <div id="editImgPreviewContainer" class="hidden">
                            <p class="text-xs text-green-600 mb-1 font-semibold">Ảnh mới sẽ thay thế:</p>
                            <img id="editImgPreview" src="" class="h-16 w-auto rounded-lg border-2 border-green-500 shadow-sm">
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeModal('editModal')" class="px-5 py-2.5 rounded-lg bg-gray-500 text-white hover:bg-gray-600 transition">Hủy</button>
                    <button type="submit" name="edit_category" class="px-5 py-2.5 rounded-lg bg-gradient-custom text-white hover:opacity-90 transition shadow-lg">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
            document.getElementById('addModal').classList.add('flex');
            document.body.style.overflow = 'hidden';
            
            // Reset form
            document.getElementById('addImgPreviewContainer').classList.add('hidden');
            document.querySelector('#addModal form').reset();
        }

        function editCategory(id, name, imgUrl) {
            document.getElementById('edit_category_id').value = id;
            document.getElementById('edit_category_name').value = name;
            
            // Reset preview ảnh mới
            document.getElementById('editImgPreviewContainer').classList.add('hidden');
            document.querySelector('input[name="image_url"]').value = '';
            
            // Hiển thị ảnh hiện tại nếu có
            const currentImgContainer = document.getElementById('currentImgContainer');
            if (imgUrl && imgUrl !== '') {
                document.getElementById('currentImg').src = '../' + imgUrl;
                currentImgContainer.classList.remove('hidden');
            } else {
                currentImgContainer.classList.add('hidden');
            }

            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }
        
        // Xem trước ảnh khi Thêm
        function previewAddImage(input) {
            const previewContainer = document.getElementById('addImgPreviewContainer');
            const preview = document.getElementById('addImgPreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                previewContainer.classList.add('hidden');
            }
        }
        
        // Xem trước ảnh khi Sửa
        function previewEditImage(input) {
            const previewContainer = document.getElementById('editImgPreviewContainer');
            const preview = document.getElementById('editImgPreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                    previewContainer.classList.add('block');
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                previewContainer.classList.add('hidden');
                previewContainer.classList.remove('block');
            }
        }

        function logout() {
            if (confirm('Bạn có chắc muốn đăng xuất?')) {
                window.location.href = 'logout.php';
            }
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('fixed') && event.target.classList.contains('inset-0')) {
                closeModal(event.target.id);
            }
        }
    </script>
</body>
</html>