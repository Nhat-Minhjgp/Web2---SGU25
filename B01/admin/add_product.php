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
    $phanTramLoiNhuan = floatval($_POST['phan_tram_loi'] ?? 20) / 100;

    // Upload hình ảnh
    $imageUrl = '';
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
        $uploadDir = __DIR__ . '/../img/sanpham/';
        if (!file_exists($uploadDir))
            mkdir($uploadDir, 0777, true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $newName = 'PROD-' . date('YmdHis') . '-' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newName)) {
            $imageUrl = 'img/sanpham/' . $newName;
        }
    }

    if (empty($tenSP) || !$danhmuc_id || !$ncc_id) {
        $message = '❌ Vui lòng điền đầy đủ thông tin bắt buộc!';
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

        /* ===== AJAX SEARCH STYLES ===== */
        .search-wrapper {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            background: white;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        }

        .search-input:read-only {
            background: #f9fafb;
            cursor: pointer;
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            max-height: 220px;
            overflow-y: auto;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            margin-top: 4px;
            display: none;
        }

        .search-results.show {
            display: block;
            animation: slideDown 0.2s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .search-result-item {
            padding: 10px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.15s;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-item:hover,
        .search-result-item.active {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
        }

        .search-result-name {
            font-weight: 500;
            color: #1f2937;
            font-size: 14px;
        }

        .search-result-extra {
            font-size: 11px;
            color: #6b7280;
            margin-top: 2px;
        }

        .search-loading,
        .search-no-result {
            padding: 12px;
            text-align: center;
            font-size: 13px;
            color: #6b7280;
        }

        .search-no-result {
            color: #9ca3af;
            font-style: italic;
        }

        .hidden-id-input {
            display: none;
        }

        /* Selected badge */
        .selected-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: linear-gradient(135deg, #667eea15, #764ba215);
            border: 1px solid #667eea30;
            border-radius: 20px;
            font-size: 12px;
            color: #667eea;
            margin-top: 6px;
        }

        .selected-badge .remove-btn {
            cursor: pointer;
            color: #9ca3af;
            transition: color 0.2s;
        }

        .selected-badge .remove-btn:hover {
            color: #ef4444;
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
                    <div
                        class="w-10 h-10 rounded-full bg-gradient-custom flex items-center justify-center text-white font-bold">
                        <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($admin_username); ?></p>
                    </div>
                </div>
                <button onclick="logout()"
                    class="bg-gradient-custom text-white font-semibold px-4 py-2 rounded-lg hover:opacity-90 transition duration-200 shadow-md hover:shadow-lg">
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
                <a href="dashboard.php"
                    class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-home w-5 text-center"></i> Dashboard
                </a>
                <a href="users.php"
                    class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-users w-5 text-center"></i> Quản lý người dùng
                </a>
                <a href="product.php"
                    class="flex items-center gap-3 px-4 py-3 bg-gradient-custom text-white rounded-lg shadow-md transition transform hover:-translate-y-0.5">
                    <i class="fas fa-box w-5 text-center"></i> Quản lý sản phẩm
                </a>
                <a href="import.php"
                    class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-arrow-down w-5 text-center"></i> Quản lý nhập hàng
                </a>
                <a href="price.php"
                    class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-tag w-5 text-center"></i> Quản lý giá bán
                </a>
                <a href="orders.php"
                    class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-receipt w-5 text-center"></i> Quản lý đơn hàng
                </a>
                <a href="inventory.php"
                    class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition">
                    <i class="fas fa-warehouse w-5 text-center"></i> Tồn kho & Báo cáo
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-6 lg:p-8 overflow-x-hidden bg-gray-50">
            <form method="POST" enctype="multipart/form-data"
                class="bg-white rounded-xl shadow-lg p-6 lg:p-8 max-w-3xl mx-auto">
                <div class="flex justify-between items-center mb-8 pb-6 border-b">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="fas fa-plus-circle text-primary"></i> Thêm sản phẩm mới
                    </h2>
                    <a href="product.php" class="text-gray-600 hover:text-primary transition flex items-center gap-1">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>

                <?php if ($message): ?>
                    <div
                        class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'; ?> border">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Tên sản phẩm -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tên sản phẩm <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="tenSP" required
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                    </div>

                    <!-- Danh mục -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Danh mục <span class="text-red-500">*</span>
                        </label>
                        <div class="search-wrapper">
                            <input type="text" class="ajax-search-input search-input" data-type="category"
                                data-required="true" placeholder="Nhập tên danh mục..." autocomplete="off">
                            <input type="hidden" name="danhmuc_id" class="hidden-id-input" required>
                            <div class="search-results"></div>
                        </div>
                        <div class="selected-badge hidden">
                            <span class="selected-name"></span>
                            <i class="fas fa-times remove-btn" onclick="clearSelection(this)"></i>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">💡 Nhập ≥2 ký tự để tìm kiếm</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Thương hiệu</label>
                        <div class="search-wrapper">
                            <input type="text" class="ajax-search-input search-input" data-type="brand"
                                data-required="false" placeholder="Nhập tên thương hiệu..." autocomplete="off">
                            <input type="hidden" name="thuonghieu_id" class="hidden-id-input" value="0">
                            <div class="search-results"></div>
                        </div>
                        <div class="selected-badge hidden">
                            <span class="selected-name"></span>
                            <i class="fas fa-times remove-btn" onclick="clearSelection(this)"></i>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Nhà cung cấp <span class="text-red-500">*</span>
                        </label>
                        <div class="search-wrapper">
                            <input type="text" class="ajax-search-input search-input" data-type="supplier"
                                data-required="true" placeholder="Nhập tên NCC hoặc SDT..." autocomplete="off">
                            <input type="hidden" name="ncc_id" class="hidden-id-input" required>
                            <div class="search-results"></div>
                        </div>
                        <div class="selected-badge hidden">
                            <span class="selected-name"></span>
                            <i class="fas fa-times remove-btn" onclick="clearSelection(this)"></i>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">💡 Có thể tìm theo tên hoặc số điện thoại</p>
                    </div>

                    <!-- % Lợi nhuận -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">% Lợi nhuận dự kiến <span
                                class="text-red-500">*</span></label>
                        <input type="number" name="phan_tram_loi" min="0" max="200" value="20"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
                        <p class="text-xs text-gray-500 mt-1">Giá bán sẽ = Giá nhập trung bình × (1 + % Lợi nhuận/100)
                        </p>
                    </div>

                    <!-- Hình ảnh -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">📷 Hình ảnh sản phẩm</label>
                        <input type="file" name="image" accept="image/*"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition"
                            onchange="previewImage(this)">
                        <img id="imagePreview" class="mt-3 max-w-xs rounded-lg hidden" src="" alt="Preview">
                    </div>

                    <!-- Mô tả -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Mô tả</label>
                        <textarea name="mota" rows="3"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition"
                            placeholder="Mô tả ngắn về sản phẩm..."></textarea>
                    </div>
                </div>

                <!-- Lưu ý quan trọng -->
                <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800">
                    <i class="fas fa-info-circle"></i> <strong>Lưu ý:</strong> Sản phẩm này chưa có giá nhập/giá bán.
                    Bạn cần vào trang <strong>Nhập hàng</strong> để nhập lô hàng đầu tiên, hệ thống sẽ tự động tính giá
                    bán theo công thức đã thiết lập.
                </div>

                <!-- Nút hành động -->
                <div class="mt-8 flex gap-3 justify-end">
                    <button type="button" onclick="history.back()"
                        class="px-6 py-2.5 rounded-lg bg-gray-500 text-white hover:bg-gray-600 transition font-medium">
                        <i class="fas fa-times mr-2"></i> Hủy
                    </button>
                    <button type="submit" name="save_product"
                        class="px-6 py-2.5 rounded-lg bg-gradient-custom text-white font-medium hover:opacity-90 transition shadow-lg">
                        <i class="fas fa-save mr-2"></i> Lưu sản phẩm
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
                reader.onload = function (e) {
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
    </script>

    <script>
        // ===== AJAX SEARCH FUNCTIONS =====

        // Debounce để giảm số lần gọi API
        function debounce(func, wait = 300) {
            let timeout;
            return function executedFunction(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        // Tìm kiếm AJAX
        async function handleAjaxSearch(input) {
            const keyword = input.value.trim();
            const type = input.dataset.type;
            const resultsDiv = input.closest('.search-wrapper').querySelector('.search-results');

            if (keyword.length < 2) {
                resultsDiv.classList.remove('show');
                resultsDiv.innerHTML = '';
                return;
            }

            resultsDiv.innerHTML = '<div class="search-loading"><i class="fas fa-spinner fa-spin mr-2"></i>Đang tìm...</div>';
            resultsDiv.classList.add('show');

            try {
                const response = await fetch(`../control/search-entities.php?type=${type}&q=${encodeURIComponent(keyword)}&limit=10`);
                const results = await response.json();
                renderSearchResults(results, resultsDiv, input);
            } catch (error) {
                console.error('Search error:', error);
                resultsDiv.innerHTML = '<div class="search-no-result">❌ Lỗi kết nối</div>';
            }
        }

        // Render kết quả tìm kiếm
        function renderSearchResults(results, container, input) {
            if (!results || results.length === 0) {
                container.innerHTML = '<div class="search-no-result">🔍 Không tìm thấy kết quả</div>';
                return;
            }

            container.innerHTML = results.map((item, idx) => `
        <div class="search-result-item" 
             data-id="${item.id}" 
             data-name="${escapeHtml(item.name)}"
             data-type="${item.type}"
             onclick="selectEntity(this, '${input.dataset.type}')">
            <div class="search-result-name">${escapeHtml(item.name)}</div>
            ${item.extra ? `<div class="search-result-extra">${escapeHtml(item.extra)}</div>` : ''}
        </div>
    `).join('');
        }

        // Chọn entity từ kết quả
        function selectEntity(element, type) {
            const wrapper = element.closest('.search-wrapper');
            const input = wrapper.querySelector('.ajax-search-input');
            const hiddenInput = wrapper.querySelector('.hidden-id-input');
            const badge = wrapper.nextElementSibling?.classList?.contains('selected-badge')
                ? wrapper.nextElementSibling
                : wrapper.querySelector('.selected-badge');

            const id = element.dataset.id;
            const name = element.dataset.name;

            // Cập nhật giá trị
            hiddenInput.value = id;
            input.value = name;
            input.readOnly = true;

            // Hiển thị badge
            if (badge) {
                badge.classList.remove('hidden');
                badge.querySelector('.selected-name').textContent = name;
            }

            // Đóng dropdown
            wrapper.querySelector('.search-results').classList.remove('show');

            // Validate nếu required
            if (input.dataset.required === 'true') {
                input.setCustomValidity('');
            }
        }

        // Xóa lựa chọn
        function clearSelection(btn) {
            const badge = btn.closest('.selected-badge');
            const wrapper = badge.previousElementSibling;
            const input = wrapper.querySelector('.ajax-search-input');
            const hiddenInput = wrapper.querySelector('.hidden-id-input');

            input.value = '';
            input.readOnly = false;
            hiddenInput.value = input.dataset.required === 'true' ? '' : '0';
            badge.classList.add('hidden');

            // Focus lại input để người dùng có thể tìm tiếp
            input.focus();
        }

        // Xử lý phím điều hướng
        function handleSearchKeydown(event, input) {
            const resultsDiv = input.closest('.search-wrapper').querySelector('.search-results');
            const items = resultsDiv.querySelectorAll('.search-result-item');
            if (items.length === 0) return;

            const activeIndex = Array.from(items).findIndex(i => i.classList.contains('active'));

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                const next = activeIndex + 1 < items.length ? activeIndex + 1 : 0;
                updateActive(items, activeIndex, next);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                const prev = activeIndex - 1 >= 0 ? activeIndex - 1 : items.length - 1;
                updateActive(items, activeIndex, prev);
            } else if (event.key === 'Enter') {
                event.preventDefault();
                if (activeIndex >= 0 && items[activeIndex]) {
                    items[activeIndex].click();
                }
            } else if (event.key === 'Escape') {
                resultsDiv.classList.remove('show');
            }
        }

        function updateActive(items, oldIdx, newIdx) {
            if (items[oldIdx]) items[oldIdx].classList.remove('active');
            if (items[newIdx]) {
                items[newIdx].classList.add('active');
                items[newIdx].scrollIntoView({ block: 'nearest' });
            }
        }

        // Escape HTML để tránh XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Debounced search handler
        const debouncedSearch = debounce(handleAjaxSearch, 300);

        // Khởi tạo event listeners khi DOM ready
        document.addEventListener('DOMContentLoaded', function () {
            // Gắn event cho tất cả input search
            document.querySelectorAll('.ajax-search-input').forEach(input => {
                input.addEventListener('input', (e) => debouncedSearch(e.target));
                input.addEventListener('keydown', (e) => handleSearchKeydown(e, e.target));
                input.addEventListener('focus', function () {
                    if (this.value.length >= 2) {
                        handleAjaxSearch(this);
                    }
                });
            });

            // Đóng dropdown khi click outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.search-wrapper')) {
                    document.querySelectorAll('.search-results').forEach(div => {
                        div.classList.remove('show');
                    });
                }
            });

            // Preview image (giữ nguyên từ code gốc)
            const imageInput = document.querySelector('input[name="image"]');
            if (imageInput) {
                imageInput.addEventListener('change', function (e) {
                    const preview = document.getElementById('imagePreview');
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function (ev) {
                            preview.src = ev.target.result;
                            preview.classList.remove('hidden');
                        };
                        reader.readAsDataURL(this.files[0]);
                    } else {
                        preview.classList.add('hidden');
                    }
                });
            }
        });

        // Logout function (giữ nguyên)
        function logout() {
            if (confirm('Bạn có chắc muốn đăng xuất?')) {
                window.location.href = 'logout.php';
            }
        }
    </script>

    <script>
        // Validate form trước khi submit
        document.querySelector('form[method="POST"]').addEventListener('submit', function (e) {
            let valid = true;

            document.querySelectorAll('.ajax-search-input[data-required="true"]').forEach(input => {
                const wrapper = input.closest('.search-wrapper');
                const hiddenInput = wrapper.querySelector('.hidden-id-input');

                if (!hiddenInput.value || hiddenInput.value === '0') {
                    e.preventDefault();
                    valid = false;
                    input.classList.add('border-red-500', 'ring-1', 'ring-red-500');

                    // Hiển thị message error
                    let errorMsg = wrapper.querySelector('.error-msg');
                    if (!errorMsg) {
                        errorMsg = document.createElement('p');
                        errorMsg.className = 'error-msg text-xs text-red-500 mt-1';
                        wrapper.appendChild(errorMsg);
                    }
                    errorMsg.textContent = '⚠️ Vui lòng chọn giá trị từ danh sách tìm kiếm';

                    // Focus vào input
                    input.focus();
                } else {
                    input.classList.remove('border-red-500', 'ring-1', 'ring-red-500');
                    const errorMsg = wrapper.querySelector('.error-msg');
                    if (errorMsg) errorMsg.remove();
                }
            });

            if (!valid) {
                // Scroll đến field lỗi đầu tiên
                const firstError = document.querySelector('.ajax-search-input.border-red-500');
                if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    </script>
</body>

</html>