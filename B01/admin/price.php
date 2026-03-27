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
        $new_price = $product['GiaNhapTB'] * (1 + $profit_percent / 100);
        
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
        $new_price = $product['GiaNhapTB'] * (1 + $profit_percent / 100);
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

// Lấy danh sách sản phẩm
$sql = "SELECT sp.*, dm.Ten_danhmuc 
        FROM sanpham sp
        LEFT JOIN danhmuc dm ON sp.Danhmuc_id = dm.Danhmuc_id
        ORDER BY sp.SanPham_id DESC";
$products = $conn->query($sql);

// Lấy danh sách danh mục
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
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn { animation: fadeIn 0.3s ease-out; }
    </style>
</head>
<body class="bg-gray-100 font-sans min-h-screen">

    <!-- HEADER -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="flex justify-between items-center px-6 py-4">
            <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">NVBPlay Admin Panel</h1>
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-3 bg-gray-100 px-4 py-2 rounded-lg">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-indigo-600 to-purple-600 flex items-center justify-center text-white font-bold">
                        <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
                    </div>
                    <div>
                        <p class="font-semibold text-sm text-gray-800">
                            <?php echo htmlspecialchars($admin_username); ?>
                        </p>
                        <p class="text-xs text-gray-500">Quản trị viên</p>
                    </div>
                </div>
                <a href="logout.php" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold px-4 py-2 rounded-lg hover:opacity-90 transition shadow-md">
                    <i class="fas fa-sign-out-alt mr-2"></i>Đăng xuất
                </a>
            </div>
        </div>
    </header>

    <div class="flex">
        <!-- SIDEBAR -->
        <aside class="w-64 bg-white shadow-lg min-h-screen">
            <div class="p-4 border-b border-gray-200">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Danh mục chức năng</h3>
            </div>
            <nav class="p-2 space-y-1">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-2.5 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-indigo-600 transition">
                    <i class="fas fa-home w-5 text-gray-400"></i> Dashboard
                </a>
                <a href="users.php" class="flex items-center gap-3 px-4 py-2.5 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-indigo-600 transition">
                    <i class="fas fa-users w-5 text-gray-400"></i> Quản lý người dùng
                </a>
                <a href="categories.php" class="flex items-center gap-3 px-4 py-2.5 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-indigo-600 transition">
                    <i class="fas fa-tags w-5 text-gray-400"></i> Quản lý danh mục
                </a>
                <a href="product.php" class="flex items-center gap-3 px-4 py-2.5 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-indigo-600 transition">
                    <i class="fas fa-box w-5 text-gray-400"></i> Quản lý sản phẩm
                </a>
                <a href="import.php" class="flex items-center gap-3 px-4 py-2.5 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-indigo-600 transition">
                    <i class="fas fa-arrow-down w-5 text-gray-400"></i> Quản lý nhập hàng
                </a>
                <a href="price.php" class="flex items-center gap-3 px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg shadow-md transition">
                    <i class="fas fa-tag w-5 text-white"></i> Quản lý giá bán
                </a>
                <a href="orders.php" class="flex items-center gap-3 px-4 py-2.5 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-indigo-600 transition">
                    <i class="fas fa-receipt w-5 text-gray-400"></i> Quản lý đơn hàng
                </a>
                <a href="inventory.php" class="flex items-center gap-3 px-4 py-2.5 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-indigo-600 transition">
                    <i class="fas fa-warehouse w-5 text-gray-400"></i> Tồn kho & Báo cáo
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-8">
            <div class="bg-white rounded-xl shadow-lg p-6 animate-fadeIn">
                <div class="flex justify-between items-center mb-6 pb-4 border-b">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-tag text-indigo-600 mr-2"></i>Quản lý giá bán
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

                <!-- PHẦN 1: QUẢN LÝ THEO SẢN PHẨM -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-box text-indigo-500 mr-2"></i>
                        Theo sản phẩm
                        <span class="text-sm text-gray-400 ml-2 font-normal">(Cập nhật tỷ lệ lợi nhuận)</span>
                    </h3>
                    
                    <div class="overflow-x-auto border border-gray-200 rounded-xl">
                        <table class="w-full min-w-[900px]">
                            <thead class="bg-gradient-to-r from-indigo-600 to-purple-600">
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
                            <tbody class="divide-y divide-gray-200">
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
                                        <td class="px-4 py-3 font-mono text-sm">SP<?php echo str_pad($row['SanPham_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                        <td class="px-4 py-3 font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($row['TenSP']); ?></td>
                                        <td class="px-4 py-3 text-gray-600 text-sm"><?php echo htmlspecialchars($row['Ten_danhmuc'] ?? 'Chưa có'); ?></td>
                                        <td class="px-4 py-3 text-right font-mono text-sm"><?php echo number_format($row['GiaNhapTB'], 0, ',', '.'); ?>đ</td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="font-semibold text-indigo-600"><?php echo $row['PhanTramLoiNhuan']; ?>%</span>
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold text-indigo-600 text-sm">
                                            <?php echo number_format($row['GiaBan'], 0, ',', '.'); ?>đ
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button onclick="openProfitModal(<?php echo $row['SanPham_id']; ?>, '<?php echo addslashes($row['TenSP']); ?>', <?php echo $row['PhanTramLoiNhuan']; ?>)" 
                                                    class="text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 p-2 rounded-lg transition" title="Cập nhật tỷ lệ lợi nhuận">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-12 text-gray-400">
                                            <i class="fas fa-box-open text-4xl mb-2 block"></i>
                                            Chưa có sản phẩm nào.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- PHẦN 2: QUẢN LÝ THEO LOẠI SẢN PHẨM -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-tags text-indigo-500 mr-2"></i>
                        Theo loại sản phẩm
                        <span class="text-sm text-gray-400 ml-2 font-normal">(Áp dụng tỷ lệ lợi nhuận cho cả loại)</span>
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

                <!-- PHẦN 3: TRA CỨU GIÁ -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-search text-indigo-500 mr-2"></i>
                        Tra cứu giá
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <input type="text" id="searchProduct" placeholder="🔍 Tìm theo mã hoặc tên sản phẩm..." 
                               class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        <select id="searchCategory" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                            <option value="">-- Tất cả danh mục --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['Danhmuc_id']; ?>"><?php echo htmlspecialchars($cat['Ten_danhmuc']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button onclick="searchPrice()" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-4 py-2 rounded-lg hover:opacity-90 transition">
                            <i class="fas fa-search mr-2"></i>Tra cứu
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto border border-gray-200 rounded-xl">
                        <table class="w-full min-w-[800px]" id="searchTable">
                            <thead class="bg-gradient-to-r from-indigo-600 to-purple-600">
                                <tr>
                                    <th class="px-4 py-3 text-center text-white text-sm font-semibold">Hình</th>
                                    <th class="px-4 py-3 text-left text-white text-sm font-semibold">Mã SP</th>
                                    <th class="px-4 py-3 text-left text-white text-sm font-semibold">Tên sản phẩm</th>
                                    <th class="px-4 py-3 text-left text-white text-sm font-semibold">Danh mục</th>
                                    <th class="px-4 py-3 text-right text-white text-sm font-semibold">Giá vốn</th>
                                    <th class="px-4 py-3 text-right text-white text-sm font-semibold">Tỷ lệ LN</th>
                                    <th class="px-4 py-3 text-right text-white text-sm font-semibold">Giá bán</th>
                                </tr>
                            </thead>
                            <tbody id="searchTableBody" class="divide-y divide-gray-200">
                                <?php
                                $all_sql = "SELECT sp.*, dm.Ten_danhmuc FROM sanpham sp LEFT JOIN danhmuc dm ON sp.Danhmuc_id = dm.Danhmuc_id ORDER BY sp.SanPham_id DESC";
                                $all_products = $conn->query($all_sql);
                                while ($row = $all_products->fetch_assoc()):
                                ?>
                                <tr class="hover:bg-gray-50">
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
                                    <td class="px-4 py-3 font-mono text-sm">SP<?php echo str_pad($row['SanPham_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td class="px-4 py-3 font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($row['TenSP']); ?></td>
                                    <td class="px-4 py-3 text-gray-600 text-sm"><?php echo htmlspecialchars($row['Ten_danhmuc'] ?? 'Chưa có'); ?></td>
                                    <td class="px-4 py-3 text-right font-mono text-sm"><?php echo number_format($row['GiaNhapTB'], 0, ',', '.'); ?>đ</td>
                                    <td class="px-4 py-3 text-right"><?php echo $row['PhanTramLoiNhuan']; ?>%</td>
                                    <td class="px-4 py-3 text-right font-semibold text-indigo-600 text-sm"><?php echo number_format($row['GiaBan'], 0, ',', '.'); ?>đ</td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL CẬP NHẬT TỶ LỆ LỢI NHUẬN THEO SẢN PHẨM -->
    <div id="profitModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-md mx-4 animate-fadeIn">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-4 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-semibold"><i class="fas fa-percent mr-2"></i>Cập nhật tỷ lệ lợi nhuận</h3>
                <button onclick="closeModal('profitModal')" class="text-white hover:text-gray-200 text-xl">&times;</button>
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
                    <p class="text-xs text-gray-500 mt-1">Giá bán sẽ được tính tự động = Giá vốn × (1 + % lợi nhuận/100)</p>
                </div>
                <div class="flex justify-end space-x-3 mt-4">
                    <button type="button" onclick="closeModal('profitModal')" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Hủy</button>
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:opacity-90">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL CẬP NHẬT TỶ LỆ LỢI NHUẬN THEO LOẠI -->
    <div id="categoryProfitModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-md mx-4 animate-fadeIn">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-4 rounded-t-xl flex justify-between items-center">
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
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:opacity-90">Áp dụng</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL XEM ẢNH LỚN -->
    <div id="imageModal" class="fixed inset-0 bg-black/90 hidden items-center justify-center z-[2000]" onclick="closeImageModal()">
        <span class="absolute top-5 right-10 text-white text-5xl cursor-pointer hover:text-gray-300" onclick="closeImageModal()">&times;</span>
        <div class="max-w-[90%] max-h-[90%]" onclick="event.stopPropagation()">
            <img id="largeImage" src="" alt="" class="max-w-full max-h-[90vh] object-contain rounded-lg">
            <p id="imageCaption" class="text-white text-center mt-2"></p>
        </div>
    </div>

    <script>
        function openProfitModal(productId, productName, currentProfit) {
            document.getElementById('profit_product_id').value = productId;
            document.getElementById('profit_product_name').value = productName;
            document.getElementById('profit_percent').value = currentProfit;
            document.getElementById('profitModal').classList.remove('hidden');
            document.getElementById('profitModal').classList.add('flex');
        }
        
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
        
        function searchPrice() {
            let keyword = document.getElementById('searchProduct').value.toLowerCase();
            let categoryId = document.getElementById('searchCategory').value;
            let rows = document.querySelectorAll('#searchTableBody tr');
            
            rows.forEach(row => {
                let productName = row.cells[2]?.textContent.toLowerCase() || '';
                let productCode = row.cells[1]?.textContent.toLowerCase() || '';
                let productCategory = row.cells[3]?.textContent || '';
                
                let matchKeyword = keyword === '' || productName.includes(keyword) || productCode.includes(keyword);
                let matchCategory = categoryId === '' || productCategory === document.querySelector(`#searchCategory option[value="${categoryId}"]`)?.textContent;
                
                row.style.display = (matchKeyword && matchCategory) ? '' : 'none';
            });
        }
        
        window.onclick = function(event) {
            if (event.target.id && (event.target.id === 'profitModal' || event.target.id === 'categoryProfitModal')) {
                closeModal(event.target.id);
            }
        }
        
        document.getElementById('searchProduct').addEventListener('keyup', searchPrice);
        document.getElementById('searchCategory').addEventListener('change', searchPrice);
        
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