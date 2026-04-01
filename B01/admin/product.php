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
    <script src="https://cdn.tailwindcss.com"></script>
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
        /* Animation cho Modal */
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .animate-slide-in {
            animation: slideIn 0.3s ease-out forwards;
        }
        
        /* Loading spinner */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Toast message */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            animation: slideInRight 0.3s ease-out;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Sidebar styles */
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

        <main class="flex-1 p-6 lg:p-8 overflow-x-hidden bg-gray-50">
            <div class="bg-white rounded-xl shadow-lg p-6 lg:p-8 min-h-full">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 pb-6 border-b-2 border-gray-100 gap-4">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="fas fa-box text-primary"></i> Quản lý sản phẩm
                    </h2>
                    <a href="add_product.php" class="bg-gradient-custom hover:opacity-90 text-white px-6 py-2.5 rounded-lg font-medium shadow-lg hover:shadow-xl transition transform hover:-translate-y-0.5 flex items-center gap-2">
                        <i class="fas fa-plus"></i> Thêm sản phẩm
                    </a>
                </div>

                <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                    <div class="relative w-full sm:max-w-md">
                        <input type="text" id="productSearch" placeholder="🔍 Tìm kiếm sản phẩm..." onkeyup="filterProducts()"
                               class="w-full pl-10 pr-4 py-2.5 rounded-full border border-gray-300 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition shadow-sm">
                        <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
                    <table class="w-full text-left border-collapse" id="myTable">
                        <thead>
                            <tr class="bg-gradient-custom text-white">
                                <th class="p-4 font-medium text-sm">Hình ảnh</th>
                                <th class="p-4 font-medium text-sm">Tên sản phẩm</th>
                                <th class="p-4 font-medium text-sm">Danh mục</th>
                                <th class="p-4 font-medium text-sm">Thương hiệu</th>
                                <th class="p-4 font-medium text-sm">Giá bán</th>
                                <th class="p-4 font-medium text-sm text-center">Tồn kho</th>
                                <th class="p-4 font-medium text-sm text-center">Trạng thái</th>
                                <th class="p-4 font-medium text-sm text-center">Thao tác</th>
                             </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100" id="productTableBody">
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr id="product-row-<?php echo $row['SanPham_id']; ?>" class="hover:bg-blue-50/50 transition duration-150">
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
                                    <td class="p-4 text-center">
                                        <?php if($row['SoLuongTon'] > 0): ?>
                                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                                <?php echo $row['SoLuongTon']; ?> 
                                            </span>
                                        <?php else: ?>
                                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">
                                                Hết hàng
                                            </span>
                                        <?php endif; ?>
                                     </td>
                                    <td class="p-4 text-center">
                                        <?php if(isset($row['TrangThai']) && $row['TrangThai'] == 1): ?>
                                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">
                                                <i class="fas fa-check-circle mr-1"></i>Đang bán
                                            </span>
                                        <?php else: ?>
                                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                                <i class="fas fa-eye-slash mr-1"></i>Đã ẩn
                                            </span>
                                        <?php endif; ?>
                                     </td>
                                    <td class="p-4">
                                        <div class="flex items-center justify-center gap-3">
                                            <button onclick="window.location.href='edit_product.php?id=<?php echo $row['SanPham_id']; ?>'" 
                                                    class="text-blue-500 hover:text-blue-700 transition transform hover:scale-110" title="Sửa">
                                                <i class="fas fa-edit text-lg"></i>
                                            </button>
                                            <button onclick="deleteProduct(<?php echo $row['SanPham_id']; ?>, '<?php echo addslashes($row['TenSP']); ?>')" 
                                                    class="text-red-500 hover:text-red-700 transition transform hover:scale-110" title="Xóa">
                                                <i class="fas fa-trash text-lg"></i>
                                            </button>
                                        </div>
                                     </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-12 text-gray-500">
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

    <!-- Modal xác nhận xóa -->
    <div id="deleteModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[1000] backdrop-blur-sm">
        <div class="bg-white rounded-xl w-full max-w-md mx-4 shadow-2xl animate-slide-in">
            <div class="bg-gradient-custom text-white p-5 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-bold flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle"></i> Xác nhận xóa
                </h3>
                <button onclick="closeDeleteModal()" class="text-white hover:text-gray-200 transition text-xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-8 text-center">
                <i class="fas fa-trash-alt text-red-500 text-5xl mb-4"></i>
                <p class="text-gray-800 font-medium text-lg mb-2">Bạn có chắc chắn muốn xóa sản phẩm?</p>
                <p id="productName" class="text-primary font-semibold text-lg mb-2"></p>
                <p class="text-gray-500 text-sm">Thao tác này sẽ xóa vĩnh viễn sản phẩm khỏi hệ thống!</p>
                <p class="text-red-500 text-xs mt-2">Lưu ý: Sản phẩm đã có trong đơn nhập hàng sẽ không thể xóa.</p>
            </div>
            <div class="p-5 border-t border-gray-100 flex justify-center gap-3 bg-gray-50 rounded-b-xl">
                <button onclick="closeDeleteModal()" class="px-6 py-2.5 rounded-lg bg-gray-500 text-white hover:bg-gray-600 transition font-medium">
                    <i class="fas fa-times mr-2"></i>Hủy
                </button>
                <button id="confirmDeleteBtn" class="px-6 py-2.5 rounded-lg bg-red-500 text-white hover:bg-red-600 transition font-medium shadow-lg">
                    <i class="fas fa-trash mr-2"></i>Xóa
                </button>
            </div>
        </div>
    </div>

    <!-- Toast thông báo -->
    <div id="toast" class="toast hidden">
        <div class="bg-white rounded-lg shadow-lg p-4 min-w-[300px] border-l-4 border-green-500">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                <div>
                    <p class="font-semibold text-gray-800">Thành công!</p>
                    <p id="toastMessage" class="text-sm text-gray-600"></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Hàm lọc tìm kiếm nhanh
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

        // Hàm xóa sản phẩm
        let deleteId = null;

        function deleteProduct(id, productName) {
            deleteId = id;
            document.getElementById('productName').innerHTML = productName;
            document.getElementById('deleteModal').classList.remove('hidden');
            document.getElementById('deleteModal').classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('deleteModal').classList.remove('flex');
            document.body.style.overflow = 'auto';
            deleteId = null;
        }

        // Xử lý xóa bằng AJAX
        document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
    if (!deleteId) return;
    
    // Hiển thị loading
    const btn = this;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<div class="loading-spinner"></div> Đang xóa...';
    btn.disabled = true;
    
    try {
        const response = await fetch('delete_product.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + deleteId
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Kiểm tra xem sản phẩm đã được ẩn hay xóa hẳn
            // Dựa vào message để biết
            if (result.message.includes('đã được ẩn')) {
                // Sản phẩm bị ẩn - cập nhật lại trạng thái hiển thị
                const row = document.getElementById('product-row-' + deleteId);
                if (row) {
                    // Cập nhật cột trạng thái (cột thứ 7 - index 6)
                    const statusCell = row.cells[6];
                    statusCell.innerHTML = `
                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                            <i class="fas fa-eye-slash mr-1"></i>Đã ẩn
                        </span>
                    `;
                    
                    // Tùy chọn: thay đổi màu badge tồn kho nếu muốn
                    // Không xóa dòng, chỉ cập nhật trạng thái
                }
                showToast(result.message, 'warning');
            } else if (result.message.includes('xóa vĩnh viễn')) {
                // Xóa hẳn - remove dòng
                const row = document.getElementById('product-row-' + deleteId);
                if (row) {
                    row.remove();
                }
                showToast(result.message, 'success');
            } else {
                // Trường hợp khác
                showToast(result.message, 'info');
            }
            
            // Đóng modal
            closeDeleteModal();
            
            // Nếu không còn sản phẩm nào, hiển thị thông báo
            const tbody = document.getElementById('productTableBody');
            if (tbody.children.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-12 text-gray-500">
                            <i class="fas fa-box-open text-5xl mb-4 block text-gray-300"></i>
                            <p class="text-lg">Chưa có sản phẩm nào.</p>
                        </td>
                    </tr>
                `;
            }
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Có lỗi xảy ra khi xóa sản phẩm!', 'error');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
});

        // Hàm hiển thị toast
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            const borderColor = type === 'success' ? 'border-green-500' : 'border-red-500';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            const iconColor = type === 'success' ? 'text-green-500' : 'text-red-500';
            
            toastMessage.innerHTML = message;
            toast.className = 'toast fixed top-20 right-5 z-50';
            toast.style.display = 'block';
            
            // Thay đổi màu border và icon
            const toastDiv = toast.querySelector('.bg-white');
            toastDiv.className = `bg-white rounded-lg shadow-lg p-4 min-w-[300px] border-l-4 ${borderColor}`;
            const iconElement = toast.querySelector('.fa-check-circle');
            if (iconElement) {
                iconElement.className = `fas ${icon} ${iconColor} text-xl mr-3`;
            }
            
            // Tự động ẩn sau 3 giây
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }

        // Hàm logout
        function logout() {
            if (confirm('Bạn có chắc muốn đăng xuất?')) {
                window.location.href = 'logout.php';
            }
        }

        // Click outside modal
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeDeleteModal();
            }
        }
        
        // ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>