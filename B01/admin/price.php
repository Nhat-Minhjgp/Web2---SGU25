<?php
session_start();
require_once __DIR__ . '/../control/connect.php';
require_once __DIR__ . '/../control/function.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Quản trị viên';
$admin_role = $_SESSION['admin_role'] ?? '';
$admin_username = $_SESSION['admin_username'] ?? '';

// Xử lý cập nhật tỷ lệ lợi nhuận cho sản phẩm
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product_profit'])) {
    $product_id = intval($_POST['product_id']);
    $profit_percent = floatval($_POST['profit_percent']);
    
    $product = getProductById($conn, $product_id);
    if ($product) {
        $new_price = $product['GiaNhapTB'] * (1 + $profit_percent );
        
        $sql = "UPDATE sanpham SET PhanTramLoiNhuan = ?, GiaBan = ? WHERE SanPham_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ddi", $profit_percent, $new_price, $product_id);
        
        if ($stmt->execute()) {
            $message = 'Cập nhật tỷ lệ lợi nhuận thành công!';
        } else {
            $error = 'Có lỗi xảy ra!';
        }
    }
}

// Xử lý cập nhật tỷ lệ lợi nhuận cho loại sản phẩm
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_category_profit'])) {
    $category_id = intval($_POST['category_id']);
    $profit_percent = floatval($_POST['category_profit']);
    
    $sql = "SELECT SanPham_id, GiaNhapTB FROM sanpham WHERE Danhmuc_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $products = $stmt->get_result();
    
    $success_count = 0;
    while ($product = $products->fetch_assoc()) {
        $new_price = $product['GiaNhapTB'] * (1 + $profit_percent );
        $update = $conn->prepare("UPDATE sanpham SET PhanTramLoiNhuan = ?, GiaBan = ? WHERE SanPham_id = ?");
        $update->bind_param("ddi", $profit_percent, $new_price, $product['SanPham_id']);
        if ($update->execute()) {
            $success_count++;
        }
    }
    
    if ($success_count > 0) {
        $message = "Đã cập nhật tỷ lệ lợi nhuận cho $success_count sản phẩm!";
    } else {
        $error = 'Không có sản phẩm nào trong loại này!';
    }
}

// ==========================================
// XỬ LÝ TÌM KIẾM SẢN PHẨM BẰNG SQL
// ==========================================
$search_keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$search_category = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

// Câu truy vấn mặc định
$sql_products = "SELECT sp.*, dm.Ten_danhmuc 
                 FROM sanpham sp
                 LEFT JOIN danhmuc dm ON sp.Danhmuc_id = dm.Danhmuc_id
                 WHERE 1=1"; // Trick nhỏ để dễ dàng nối thêm điều kiện AND

$params = [];
$types = "";

// 1. Nếu có tìm kiếm bằng từ khóa (Tên SP hoặc Mã SP)
if (!empty($search_keyword)) {
    // Nếu người dùng gõ "SP0005", ta cắt chữ "SP" ra để lấy số 5 tìm theo ID
    $search_id = preg_replace('/[^0-9]/', '', $search_keyword); 
    
    if (!empty($search_id)) {
        $sql_products .= " AND (sp.TenSP LIKE ? OR sp.SanPham_id = ?)";
        $search_term = "%" . $search_keyword . "%";
        $params[] = $search_term;
        $params[] = $search_id;
        $types .= "si";
    } else {
        $sql_products .= " AND sp.TenSP LIKE ?";
        $search_term = "%" . $search_keyword . "%";
        $params[] = $search_term;
        $types .= "s";
    }
}

// 2. Nếu có lọc theo Danh mục
if ($search_category > 0) {
    $sql_products .= " AND sp.Danhmuc_id = ?";
    $params[] = $search_category;
    $types .= "i";
}

$sql_products .= " ORDER BY sp.SanPham_id DESC";

// Thực thi truy vấn
$stmt = $conn->prepare($sql_products);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();

// Lấy danh sách danh mục cho các Dropdown
$categories = getCategories($conn);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý giá bán - Admin</title>
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
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn { animation: fadeIn 0.3s ease-out; }
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
                <button onclick="logout()" class="bg-gradient-custom text-white font-semibold px-4 py-2 rounded-lg hover:opacity-90 transition duration-200 shadow-md hover:shadow-lg">
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
                    <i class="fas fa-home w-5"></i> Dashboard
                </a>
                <a href="users.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-users w-5"></i> Quản lý người dùng
                </a>
                <a href="product.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-box w-5"></i> Quản lý sản phẩm
                </a>
                <a href="import.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-arrow-down w-5"></i> Quản lý nhập hàng
                </a>
                <a href="price.php" class="flex items-center gap-3 px-4 py-3 bg-gradient-custom text-white rounded-lg shadow-md">
                    <i class="fas fa-tag w-5"></i> Quản lý giá bán
                </a>
                <a href="orders.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-receipt w-5"></i> Quản lý đơn hàng
                </a>
                <a href="inventory.php" class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-warehouse w-5"></i> Tồn kho & Báo cáo
                </a>
            </nav>
        </aside>

        <main class="flex-1 p-6 lg:p-8 overflow-x-hidden bg-gray-50">
            <div class="bg-white rounded-xl shadow-lg p-6 lg:p-8 min-h-full">
                <div class="flex justify-between items-center mb-6 pb-4 border-b">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-tag text-primary mr-2"></i>Quản lý giá bán
                    </h2>
                </div>

                <?php if (isset($message)): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">
                        <i class="fas fa-check-circle mr-2"></i><?php echo $message; ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="mb-10 border-b border-gray-100 pb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-tags text-indigo-500 mr-2"></i>
                        Cập nhật theo loại sản phẩm
                        <span class="text-sm text-gray-400 ml-2 font-normal">(Áp dụng tỷ lệ lợi nhuận cho toàn bộ danh mục)</span>
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($categories as $cat): ?>
                        <div class="border border-gray-200 rounded-xl p-4 hover:shadow-md transition">
                            <div class="flex justify-between items-start mb-3">
                                <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($cat['Ten_danhmuc']); ?></h4>
                                <button onclick="openCategoryProfitModal(<?php echo $cat['Danhmuc_id']; ?>, '<?php echo addslashes($cat['Ten_danhmuc']); ?>')" 
                                        class="text-indigo-600 hover:text-indigo-800 text-sm flex items-center gap-1">
                                    <i class="fas fa-edit"></i> Cập nhật
                                </button>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php
                                $count_sql = "SELECT COUNT(*) as total FROM sanpham WHERE Danhmuc_id = ?";
                                $count_stmt = $conn->prepare($count_sql);
                                $count_stmt->bind_param("i", $cat['Danhmuc_id']);
                                $count_stmt->execute();
                                $count = $count_stmt->get_result()->fetch_assoc();
                                ?>
                                <i class="fas fa-box mr-1"></i> <?php echo $count['total']; ?> sản phẩm
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-box text-indigo-500 mr-2"></i>
                        Quản lý chi tiết từng sản phẩm
                    </h3>
                    
                    <form method="GET" action="price.php" class="flex flex-col sm:flex-row gap-4 mb-6">
                        <div class="relative w-full sm:w-1/2">
                            <input type="text" name="keyword" value="<?php echo htmlspecialchars($search_keyword); ?>" placeholder="🔍 Nhập mã hoặc tên sản phẩm..." 
                                   class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition shadow-sm">
                            <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
                        </div>
                        <div class="w-full sm:w-1/3">
                            <select name="category_id" onchange="this.form.submit()" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-white shadow-sm cursor-pointer">
                                <option value="0">-- Tất cả danh mục --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['Danhmuc_id']; ?>" <?php echo ($search_category == $cat['Danhmuc_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['Ten_danhmuc']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="w-full sm:w-auto flex gap-2">
                            <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg hover:bg-indigo-700 transition shadow-sm font-medium w-full sm:w-auto">
                                Tìm kiếm
                            </button>
                            <?php if(!empty($search_keyword) || $search_category > 0): ?>
                                <a href="price.php" class="bg-gray-500 text-white px-4 py-2.5 rounded-lg hover:bg-gray-600 transition shadow-sm flex items-center justify-center" title="Xóa bộ lọc">
                                    <i class="fas fa-times"></i>
                                </a>

                            <?php endif; ?>
                        </div>
                    </form>
                    
                    <div class="overflow-x-auto border border-gray-200 rounded-xl shadow-sm">
                        <table class="w-full min-w-[900px]">
                            <thead class="bg-gradient-custom text-white">
                                <tr>
                                    <th class="px-4 py-3 text-center text-white text-sm font-semibold">Hình</th>
                                    <th class="px-4 py-3 text-left text-white text-sm font-semibold">Mã SP</th>
                                    <th class="px-4 py-3 text-left text-white text-sm font-semibold">Tên sản phẩm</th>
                                    <th class="px-4 py-3 text-left text-white text-sm font-semibold">Danh mục</th>
                                    <th class="px-4 py-3 text-right text-white text-sm font-semibold">Giá vốn</th>
                                    <th class="px-4 py-3 text-right text-white text-sm font-semibold">Tỷ lệ LN (%)</th>
                                    <th class="px-4 py-3 text-right text-white text-sm font-semibold">Giá bán</th>
                                    <th class="px-4 py-3 text-center text-white text-sm font-semibold">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="productTableBody" class="divide-y divide-gray-200">
                                <?php if ($products && $products->num_rows > 0): ?>
                                    <?php while($row = $products->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-4 py-3 text-center">
                                            <?php if ($row['image_url']): ?>
                                                <img src="../<?php echo $row['image_url']; ?>" 
                                                     class="w-12 h-12 object-cover rounded-lg border border-gray-200 mx-auto cursor-pointer hover:opacity-80 transition"
                                                     onclick="showLargeImage('../<?php echo $row['image_url']; ?>', '<?php echo htmlspecialchars($row['TenSP']); ?>')">
                                            <?php else: ?>
                                                <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center mx-auto">
                                                    <i class="fas fa-image text-gray-400 text-xl"></i>
                                                </div>
                                            <?php endif; ?>
                                         </td>
                                        <td class="px-4 py-3 font-mono text-sm">SP<?php echo str_pad($row['SanPham_id'], 4, '0', STR_PAD_LEFT); ?> </td>
                                        <td class="px-4 py-3 font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($row['TenSP']); ?> </td>
                                        <td class="px-4 py-3 text-gray-600 text-sm"><?php echo htmlspecialchars($row['Ten_danhmuc'] ?? 'Chưa có'); ?> </td>
                                        <td class="px-4 py-3 text-right font-mono text-sm"><?php echo number_format($row['GiaNhapTB'], 0, ',', '.'); ?>đ</td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="font-semibold text-indigo-600"><?php echo ($row['PhanTramLoiNhuan'] * 100); ?>%</span>
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold text-indigo-600 text-sm">
                                            <?php echo number_format($row['GiaBan'], 0, ',', '.'); ?>đ
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button onclick="openProfitModal(<?php echo $row['SanPham_id']; ?>, '<?php echo addslashes($row['TenSP']); ?>', <?php echo ($row['PhanTramLoiNhuan'] * 100); ?>, <?php echo $row['GiaNhapTB']; ?>)" 
        class="text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 p-2 rounded-lg transition" title="Cập nhật tỷ lệ lợi nhuận">
    <i class="fas fa-edit"></i>
</button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-12 text-gray-400">
                                            <i class="fas fa-search text-4xl mb-3 block text-gray-300"></i>
                                            Không tìm thấy sản phẩm nào phù hợp với bộ lọc.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <div id="profitModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-md mx-4 animate-fadeIn">
            <div class="bg-gradient-custom text-white px-6 py-4 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-semibold"><i class="fas fa-percent mr-2"></i>Cập nhật tỷ lệ lợi nhuận</h3>
                <button onclick="closeModal('profitModal')" class="text-white hover:text-gray-200 text-xl">&times;</button>
            </div>
            <input type="hidden" id="profit_base_price" value="0">

<div class="mb-4">
    <label class="block text-gray-700 font-medium mb-2">Giá bán dự kiến</label>
    <input type="text" id="profit_expected_price" readonly class="w-full px-3 py-2 border rounded-lg bg-gray-100 text-indigo-600 font-bold text-lg">
</div>
            <form method="POST" class="p-6">
                <input type="hidden" name="update_product_profit" value="1">
                <input type="hidden" name="product_id" id="profit_product_id">
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Sản phẩm</label>
                    <input type="text" id="profit_product_name" readonly class="w-full px-3 py-2 border rounded-lg bg-gray-100">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Tỷ lệ lợi nhuận (%)</label>
                    <input type="number" name="profit_percent" id="profit_percent" step="0.01" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-gray-500 mt-1">Giá bán sẽ được tính tự động = Giá vốn × (1 + % lợi nhuận)</p>
                </div>
                <div class="flex justify-end space-x-3 mt-4">
                    <button type="button" onclick="closeModal('profitModal')" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Hủy</button>
                    <button type="submit" class="px-4 py-2 bg-gradient-custom text-white rounded-lg hover:opacity-90">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>

    <div id="categoryProfitModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-md mx-4 animate-fadeIn">
            <div class="bg-gradient-custom text-white px-6 py-4 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-semibold"><i class="fas fa-percent mr-2"></i>Áp dụng tỷ lệ lợi nhuận cho loại sản phẩm</h3>
                <button onclick="closeModal('categoryProfitModal')" class="text-white hover:text-gray-200 text-xl">&times;</button>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="update_category_profit" value="1">
                <input type="hidden" name="category_id" id="category_id">
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Loại sản phẩm</label>
                    <input type="text" id="category_name" readonly class="w-full px-3 py-2 border rounded-lg bg-gray-100">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">Tỷ lệ lợi nhuận (%)</label>
                    <input type="number" name="category_profit" id="category_profit" step="0.01" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-gray-500 mt-1">Sẽ áp dụng cho TẤT CẢ sản phẩm trong loại này</p>
                </div>
                <div class="flex justify-end space-x-3 mt-4">
                    <button type="button" onclick="closeModal('categoryProfitModal')" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Hủy</button>
                    <button type="submit" class="px-4 py-2 bg-gradient-custom text-white rounded-lg hover:opacity-90">Áp dụng</button>
                </div>
            </form>
        </div>
    </div>

    <div id="imageModal" class="fixed inset-0 bg-black/90 hidden items-center justify-center z-[2000]" onclick="closeImageModal()">
        <span class="absolute top-5 right-10 text-white text-5xl cursor-pointer hover:text-gray-300" onclick="closeImageModal()">&times;</span>
        <div class="max-w-[90%] max-h-[90%]" onclick="event.stopPropagation()">
            <img id="largeImage" src="" alt="" class="max-w-full max-h-[90vh] object-contain rounded-lg">
            <p id="imageCaption" class="text-white text-center mt-2"></p>
        </div>
    </div>

    <script>
        // Thay thế hàm cũ bằng hàm mới có thêm biến basePrice
function openProfitModal(productId, productName, currentProfit, basePrice) {
    document.getElementById('profit_product_id').value = productId;
    document.getElementById('profit_product_name').value = productName;
    document.getElementById('profit_percent').value = currentProfit;
    document.getElementById('profit_base_price').value = basePrice; // Lưu giá vốn
    
    calcExpectedPrice(); // Gọi hàm tính ngay khi mở form
    
    document.getElementById('profitModal').classList.remove('hidden');
    document.getElementById('profitModal').classList.add('flex');
}

// Hàm tính toán giá dự kiến và format tiền Việt Nam
function calcExpectedPrice() {
    let basePrice = parseFloat(document.getElementById('profit_base_price').value) || 0;
    let profitPercent = parseFloat(document.getElementById('profit_percent').value) || 0;
    
    let expectedPrice = basePrice * (1 + (profitPercent / 100));
    
    document.getElementById('profit_expected_price').value = new Intl.NumberFormat('vi-VN').format(expectedPrice) + 'đ';
}

// Lắng nghe sự kiện người dùng gõ phím vào ô Tỷ lệ lợi nhuận thì sẽ tính lại giá
document.getElementById('profit_percent').addEventListener('input', calcExpectedPrice);
        function openCategoryProfitModal(categoryId, categoryName) {
            document.getElementById('category_id').value = categoryId;
            document.getElementById('category_name').value = categoryName;
            document.getElementById('category_profit').value = '';
            document.getElementById('categoryProfitModal').classList.remove('hidden');
            document.getElementById('categoryProfitModal').classList.add('flex');
        }
        
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
        
        function showLargeImage(imageUrl, productName) {
            const modal = document.getElementById('imageModal');
            const img = document.getElementById('largeImage');
            const caption = document.getElementById('imageCaption');
            img.src = imageUrl;
            caption.textContent = productName;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
        
        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
        
        window.onclick = function(event) {
            if (event.target.id && (event.target.id === 'profitModal' || event.target.id === 'categoryProfitModal')) {
                closeModal(event.target.id);
            }
        }
        
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeImageModal();
                closeModal('profitModal');
                closeModal('categoryProfitModal');
            }
        });
        
        function logout() {
            if (confirm('Bạn có chắc muốn đăng xuất?')) {
                window.location.href = 'logout.php';
            }
        }
    </script>
</body>
</html>