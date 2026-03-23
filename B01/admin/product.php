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

// Lấy danh sách sản phẩm từ database
$sql = "SELECT sp.*, dm.Ten_danhmuc, th.Ten_thuonghieu 
        FROM sanpham sp
        LEFT JOIN danhmuc dm ON sp.Danhmuc_id = dm.Danhmuc_id
        LEFT JOIN thuonghieu th ON sp.Ma_thuonghieu = th.Ma_thuonghieu
        ORDER BY sp.SanPham_id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm - Admin</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Cấu hình màu sắc -->
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
        /* Animation cho Modal */
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .animate-slide-in {
            animation: slideIn 0.3s ease-out forwards;
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
            <div class="bg-white rounded-xl shadow-lg p-6 lg:p-8 min-h-full">
                <!-- Page Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 pb-6 border-b-2 border-gray-100 gap-4">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="fas fa-box text-primary"></i> Quản lý sản phẩm
                    </h2>
                    <!-- Nút thêm sản phẩm -->
                    <a href="add_product.php" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2.5 rounded-lg font-medium shadow-lg hover:shadow-xl transition transform hover:-translate-y-0.5 flex items-center gap-2">
                        <i class="fas fa-plus"></i> Thêm sản phẩm
                    </a>
                </div>

                <!-- Action Bar (Search) -->
                <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                    <div class="relative w-full sm:max-w-md">
                        <input type="text" id="productSearch" placeholder="Tìm kiếm sản phẩm..." onkeyup="filterProducts()"
                               class="w-full pl-10 pr-4 py-2.5 rounded-full border border-gray-300 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition shadow-sm">
                        <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
                    <table class="w-full text-left border-collapse" id="myTable">
                        <thead>
                            <tr class="bg-gradient-custom text-white">
                                <th class="p-4 font-medium text-sm">Hình ảnh</th>
                                <th class="p-4 font-medium text-sm">Tên sản phẩm</th>
                                <th class="p-4 font-medium text-sm">Danh mục</th>
                                <th class="p-4 font-medium text-sm">Thương hiệu</th>
                                <th class="p-4 font-medium text-sm">Giá bán</th>
                                <th class="p-4 font-medium text-sm">Tồn kho</th>
                                <th class="p-4 font-medium text-sm text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-blue-50/50 transition duration-150">
                                    <td class="p-4">
                                        <img src="../<?php echo $row['image_url'] ? $row['image_url'] : 'no-image.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($row['TenSP']); ?>" 
                                             class="w-12 h-12 object-cover rounded-lg border border-gray-200 shadow-sm bg-gray-50">
                                    </td>
                                    <td class="p-4 font-semibold text-gray-800">
                                        <?php echo htmlspecialchars($row['TenSP']); ?>
                                    </td>
                                    <td class="p-4 text-gray-600">
                                        <?php echo htmlspecialchars($row['Ten_danhmuc'] ?? 'Chưa có'); ?>
                                    </td>
                                    <td class="p-4 text-gray-600">
                                        <?php echo htmlspecialchars($row['Ten_thuonghieu'] ?? 'Chưa có'); ?>
                                    </td>
                                    <td class="p-4 font-medium text-gray-700">
                                        <?php echo number_format($row['GiaBan'], 0, ',', '.'); ?>đ
                                    </td>
                                    <td class="p-4">
                                        <?php if($row['SoLuongTon'] > 0): ?>
                                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                                <?php echo $row['SoLuongTon']; ?> sản phẩm
                                            </span>
                                        <?php else: ?>
                                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">
                                                Hết hàng
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex items-center justify-center gap-3">
                                            <button onclick="window.location.href='edit_product.php?id=<?php echo $row['SanPham_id']; ?>'" 
                                                    class="text-blue-500 hover:text-blue-700 transition transform hover:scale-110" title="Sửa">
                                                <i class="fas fa-edit text-lg"></i>
                                            </button>
                                            <button onclick="deleteProduct(<?php echo $row['SanPham_id']; ?>)" 
                                                    class="text-red-500 hover:text-red-700 transition transform hover:scale-110" title="Xóa">
                                                <i class="fas fa-trash text-lg"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-12 text-gray-500">
                                        <i class="fas fa-box-open text-5xl mb-4 block text-gray-300"></i>
                                        <p class="text-lg">Chưa có sản phẩm nào.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL XÁC NHẬN XÓA -->
    <div id="deleteModal" class="modal fixed inset-0 bg-black/50 hidden items-center justify-center z-[1000] backdrop-blur-sm">
        <div class="modal-content bg-white rounded-xl w-full max-w-md mx-4 shadow-2xl animate-slide-in">
            <div class="modal-header bg-gradient-custom text-white p-5 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-bold flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle"></i> Xác nhận xóa
                </h3>
                <button onclick="closeDeleteModal()" class="modal-close text-white hover:text-gray-200 transition text-xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body p-8 text-center">
                <p class="text-gray-800 font-medium text-lg mb-2">Bạn có chắc chắn muốn xóa sản phẩm này?</p>
                <p style="color: #666; font-size: 14px; margin-top: 10px;">Thao tác này không thể hoàn tác!</p>
            </div>
            <div class="modal-footer p-5 border-t border-gray-100 flex justify-center gap-3 bg-gray-50 rounded-b-xl">
                <button onclick="closeDeleteModal()" class="btn btn-secondary px-6 py-2.5 rounded-lg bg-gray-500 text-white hover:bg-gray-600 transition font-medium">Hủy</button>
                <button id="confirmDeleteBtn" class="btn btn-danger px-6 py-2.5 rounded-lg bg-red-500 text-white hover:bg-red-600 transition font-medium shadow-lg">Xóa</button>
            </div>
        </div>
    </div>

    <!-- JavaScript GIỐNG FILE BẠN GỬI - KHÔNG ĐỔI GÌ -->
    <script>
        // Hàm lọc tìm kiếm nhanh (GIỮ NGUYÊN)
        function filterProducts() {
            let input = document.getElementById("productSearch");
            let filter = input.value.toUpperCase();
            let table = document.getElementById("myTable");
            let tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                let td = tr[i].getElementsByTagName("td")[1]; // Cột tên sản phẩm
                if (td) {
                    let txtValue = td.textContent || td.innerText;
                    tr[i].style.display = txtValue.toUpperCase().indexOf(filter) > -1 ? "" : "none";
                }
            }
        }

        // Hàm xóa sản phẩm (GIỮ NGUYÊN)
        let deleteId = null;

        function deleteProduct(id) {
            deleteId = id;
            document.getElementById('deleteModal').classList.add('show');
            document.getElementById('deleteModal').classList.remove('hidden');
            document.getElementById('deleteModal').classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('deleteModal').classList.remove('flex');
            document.body.style.overflow = 'auto';
            deleteId = null;
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (deleteId) {
                window.location.href = 'delete_product.php?id=' + deleteId;
            }
        });

        // Hàm logout (GIỮ NGUYÊN)
        function logout() {
            if (confirm('Bạn có chắc muốn đăng xuất?')) {
                window.location.href = 'logout.php';
            }
        }

        // Click outside modal
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
                event.target.classList.add('hidden');
                event.target.classList.remove('flex');
                document.body.style.overflow = 'auto';
            }
        }
    </script>
</body>
</html>