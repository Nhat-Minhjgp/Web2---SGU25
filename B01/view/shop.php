<?php
// view/shop.php
session_start();
require_once '../control/connect.php';

// Xử lý filter từ URL
$where_conditions = ["s.TrangThai = 1"];
$params = [];
$types = "";

// ✅ TÌM KIẾM THEO TÊN SẢN PHẨM
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : '';
if (!empty($search_keyword)) {
    $where_conditions[] = "s.TenSP LIKE ?";
    $params[] = "%{$search_keyword}%";
    $types .= "s";
}

// Filter theo danh mục (MULTIPLE - sử dụng array)
if (isset($_GET['danhmuc']) && is_array($_GET['danhmuc']) && !empty($_GET['danhmuc'])) {
    $danhmuc_placeholders = implode(',', array_fill(0, count($_GET['danhmuc']), '?'));
    $where_conditions[] = "d.slug IN ($danhmuc_placeholders)";
    foreach ($_GET['danhmuc'] as $slug) {
        $params[] = $slug;
        $types .= "s";
    }
}

// Filter theo thương hiệu (MULTIPLE - sử dụng array)
if (isset($_GET['thuonghieu']) && is_array($_GET['thuonghieu']) && !empty($_GET['thuonghieu'])) {
    $thuonghieu_placeholders = implode(',', array_fill(0, count($_GET['thuonghieu']), '?'));
    $where_conditions[] = "th.slug IN ($thuonghieu_placeholders)";
    foreach ($_GET['thuonghieu'] as $slug) {
        $params[] = $slug;
        $types .= "s";
    }
}

// Filter theo giá
$min_price = isset($_GET['min_price']) ? (int) $_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (int) $_GET['max_price'] : 50000000;
if ($min_price > 0 || $max_price < 50000000) {
    $where_conditions[] = "s.GiaBan BETWEEN ? AND ?";
    $params[] = $min_price;
    $params[] = $max_price;
    $types .= "ii";
}

// Xử lý sắp xếp
$sort_order = "s.SanPham_id DESC";
if (isset($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'price_asc':
            $sort_order = "s.GiaBan ASC";
            break;
        case 'price_desc':
            $sort_order = "s.GiaBan DESC";
            break;
        case 'name_asc':
            $sort_order = "s.TenSP ASC";
            break;
        default:
            $sort_order = "s.SanPham_id DESC";
    }
}

// Xây dựng câu truy vấn
$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Đếm tổng số sản phẩm
$count_sql = "SELECT COUNT(*) as total FROM sanpham s
LEFT JOIN danhmuc d ON s.Danhmuc_id = d.Danhmuc_id
LEFT JOIN thuonghieu th ON s.Ma_thuonghieu = th.Ma_thuonghieu
$where_sql";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_products = $count_result->fetch_assoc()['total'];

// Pagination
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;
$total_pages = ceil($total_products / $limit);

// Lấy sản phẩm
$sql = "SELECT s.*, d.Ten_danhmuc, d.slug as danhmuc_slug,
th.Ten_thuonghieu, th.slug as thuonghieu_slug,
ncc.Ten_NCC
FROM sanpham s
LEFT JOIN danhmuc d ON s.Danhmuc_id = d.Danhmuc_id
LEFT JOIN thuonghieu th ON s.Ma_thuonghieu = th.Ma_thuonghieu
LEFT JOIN nhacungcap ncc ON s.NCC_id = ncc.NCC_id
$where_sql
ORDER BY $sort_order
LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $all_params = array_merge($params, [$limit, $offset]);
    $stmt->bind_param($types . "ii", ...$all_params);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$products = $stmt->get_result();

// Lấy danh sách danh mục cho filter
$categories_sql = "SELECT d.*,
(SELECT COUNT(*) FROM sanpham WHERE Danhmuc_id = d.Danhmuc_id AND TrangThai = 1) as product_count
FROM danhmuc d
ORDER BY Ten_danhmuc";
$categories_result = $conn->query($categories_sql);

// Lấy danh sách thương hiệu cho filter
$brands_sql = "SELECT th.*,
(SELECT COUNT(*) FROM sanpham WHERE Ma_thuonghieu = th.Ma_thuonghieu AND TrangThai = 1) as product_count
FROM thuonghieu th
ORDER BY Ten_thuonghieu";
$brands_result = $conn->query($brands_sql);
$brands_list = [];
while ($brand = $brands_result->fetch_assoc()) {
    $brands_list[] = $brand;
}

// Format giá
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . '₫';
}

// Tính phần trăm giảm giá
function calculateDiscount($import_price, $sell_price) {
    if ($import_price > $sell_price && $import_price > 0) {
        return round(($import_price - $sell_price) / $import_price * 100);
    }
    return 0;
}

// Helper function để build URL với filter
function buildFilterUrl($additional_params = []) {
    $params = $_GET;
    foreach ($additional_params as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }
    return 'shop.php?' . http_build_query($params);
}

// Helper để kiểm tra filter đang active
function isActiveFilter($type, $value) {
    if ($type === 'danhmuc' || $type === 'thuonghieu') {
        return isset($_GET[$type]) && is_array($_GET[$type]) && in_array($value, $_GET[$type]);
    }
    return false;
}

// Helper lấy tên hiển thị cho filter tag
function getFilterDisplayName($type, $slug) {
    global $conn;
    if ($type === 'danhmuc') {
        $stmt = $conn->prepare("SELECT Ten_danhmuc FROM danhmuc WHERE slug = ?");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return $row['Ten_danhmuc'];
        }
    } elseif ($type === 'thuonghieu') {
        $stmt = $conn->prepare("SELECT Ten_thuonghieu FROM thuonghieu WHERE slug = ?");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return $row['Ten_thuonghieu'];
        }
    }
    return $slug;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cửa hàng - NVBPlay</title>
<meta name="description" content="NVBPlay chuyên cung cấp đồ cầu lông và pickleball cao cấp, từ vợt, giày, đến phụ kiện chính hãng.">
<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #888; border-radius: 3px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #555; }
body.filter-open { overflow: hidden; }

/* Mobile Filter Drawer */
#mobile-filter-drawer { transition: transform 0.3s ease-in-out; }
#mobile-filter-drawer.open { transform: translateX(0); }
#mobile-filter-drawer.closed { transform: translateX(100%); }
#mobile-filter-overlay { transition: opacity 0.3s ease; }
#mobile-filter-overlay.hidden { opacity: 0; pointer-events: none; }
#mobile-filter-overlay.visible { opacity: 1; pointer-events: auto; }

/* Line clamp */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Filter Button Styles */
.filter-btn {
    position: relative;
    cursor: pointer;
    transition: all 0.2s ease;
    display: block;
    width: 100%;
}
.filter-btn input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}
.filter-btn .btn-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 13px;
    color: #374151;
    transition: all 0.2s ease;
    width: 100%;
    min-height: 44px;
    box-sizing: border-box;
}
.filter-btn input[type="checkbox"]:checked + .btn-content {
    background: #fef2f2;
    border-color: #dc2626;
    color: #dc2626;
    font-weight: 500;
}
.filter-btn:hover .btn-content {
    border-color: #dc2626;
    background: #fef2f2;
}
.filter-btn .btn-label {
    display: flex;
    align-items: center;
    gap: 6px;
    flex: 1;
    min-width: 0;
}
.filter-btn .btn-label span {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.filter-btn .btn-count {
    font-size: 11px;
    color: #6b7280;
    background: #e5e7eb;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 6px;
    flex-shrink: 0;
    min-width: 24px;
    text-align: center;
}
.filter-btn input[type="checkbox"]:checked + .btn-content .btn-count {
    background: #fecaca;
    color: #dc2626;
}
.filter-btn .check-icon {
    opacity: 0;
    transform: scale(0.8);
    transition: all 0.2s ease;
    color: #dc2626;
    font-size: 14px;
    flex-shrink: 0;
    margin-left: 4px;
}
.filter-btn input[type="checkbox"]:checked + .btn-content .check-icon {
    opacity: 1;
    transform: scale(1);
}

/* Sort Dropdown */
.sort-dropdown {
    position: relative;
}
.sort-select {
    appearance: none;
    padding: 8px 36px 8px 12px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 13px;
    background: white;
    cursor: pointer;
    transition: all 0.2s ease;
}
.sort-select:focus {
    outline: none;
    border-color: #dc2626;
}
.sort-dropdown::after {
    content: '\f107';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    pointer-events: none;
}

/* ✅ FILTER BUTTON GRID - STACK DỌC (1 CỘT) */
.filter-btn-grid {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.filter-btn-grid .filter-btn {
    display: block;
    width: 100%;
}
</style>
<link rel="icon" type="image/svg+xml" href="../img/icons/favicon.png" sizes="32x32">
</head>
<body class="font-sans antialiased bg-gray-50">
<!-- Popup Overlay -->
<div id="popup_overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50"></div>

<!-- Mobile Filter Overlay & Drawer -->
<div id="mobile-filter-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>
<div id="mobile-filter-drawer" class="fixed inset-y-0 right-0 z-50 w-full lg:hidden bg-white shadow-2xl closed flex flex-col h-full">
    <!-- Drawer Header -->
    <div class="p-4 border-b flex items-center justify-between bg-white sticky top-0 z-10">
        <h3 class="font-bold text-lg flex items-center">
            <i class="fas fa-sliders-h mr-2 text-red-600"></i> Bộ lọc sản phẩm
        </h3>
        <button id="close-mobile-filter" class="p-2 hover:bg-gray-100 rounded-full transition">
            <i class="fas fa-times text-gray-500 text-lg"></i>
        </button>
    </div>
    <!-- Drawer Content (Scrollable) -->
    <div class="flex-1 overflow-y-auto p-5 custom-scrollbar space-y-4" id="mobile-filter-content">
        <!-- Nội dung sẽ được render bằng JS -->
    </div>
    <!-- Drawer Footer -->
    <div class="p-4 border-t bg-white sticky bottom-0 z-10 flex gap-3">
        <button type="button" id="reset-mobile-filter" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 py-3 rounded-lg font-medium transition">
            <i class="fas fa-undo mr-2"></i>Reset
        </button>
        <button type="button" id="apply-mobile-filter" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg font-medium transition shadow-lg shadow-red-200">
            <i class="fas fa-check mr-2"></i>Áp dụng
        </button>
    </div>
</div>

<!-- Main Wrapper -->
<div id="wrapper" class="min-h-screen flex flex-col">
<!-- Header -->
<header id="header" class="sticky top-0 z-40 bg-white shadow-sm">
    <div class="header-wrapper">
        <div id="masthead" class="py-2 md:py-3 border-b">
            <div class="container mx-auto px-4 flex items-center justify-between">
                <div class="md:hidden">
                    <button class="menu-toggle p-2">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
                <div class="hidden md:flex items-center flex-1 ml-6">
                    <ul class="flex items-center space-x-4">
                        <li class="relative" id="mega-menu-container">
                            <button id="mega-menu-trigger" class="button-menu flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200 transition">
                                <i class="fas fa-bars mr-2"></i>
                                <span>Danh mục</span>
                            </button>
                            <div id="mega-menu-dropdown" class="absolute left-0 top-full mt-2 w-[900px] bg-white rounded-lg shadow-xl hidden z-50"></div>
                        </li>
                        <li>
                            <a href="shop.php" class="flex items-center text-gray-700 hover:text-red-600 font-medium">
                                <img src="../img/icons/store.svg" class="mr-1">
                                <p>CỬA HÀNG</p>
                            </a>
                        </li>
                    </ul>
                </div>
                <div id="logo" class="flex-shrink-1 absolute left-1/2 transform -translate-x-1/2">
                    <a href="../index.php" title="NVBPlay">
                        <img width="100" height="40" src="../img/icons/logonvb.png" alt="NVBPlay" class="h-12 md:h-14 w-auto transform scale-75">
                    </a>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <a href="#" class="flex items-center text-gray-700 hover:text-red-600">
                        <i class="fas fa-map-marker-alt mr-1"></i>
                        <span class="text-sm">Chọn địa chỉ</span>
                    </a>
                    <div class="h-5 w-px bg-gray-300"></div>
                    <button class="search-toggle p-2">
                        <i class="fas fa-search text-gray-700 hover:text-red-600"></i>
                    </button>
                    <a href="my-account.php" class="p-2">
                        <i class="far fa-user text-gray-700 hover:text-red-600 text-xl"></i>
                    </a>
                    <a href="cart.php" class="relative p-2">
                        <i class="fas fa-shopping-basket text-gray-700 hover:text-red-600 text-xl"></i>
                        <span class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">0</span>
                    </a>
                </div>
                <div class="md:hidden flex items-center space-x-3">
                    <button class="search-toggle p-1">
                        <i class="fas fa-search text-xl"></i>
                    </button>
                    <a href="cart.php" class="relative p-1">
                        <i class="fas fa-shopping-basket text-xl"></i>
                        <span class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">0</span>
                    </a>
                </div>
            </div>
        </div>
        <div id="wide-nav" class="bg-gray-900 text-white py-2">
            <div class="container mx-auto px-4 text-center">
                <div class="top-hot">
                    <a href="#" class="text-white hover:text-yellow-300 transition text-sm md:text-base">
                        ⚡ VỢT YONEX NANOFLARE 1000 GAME - RESTOCKED ⚡
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<main>
<div class="container mx-auto px-4 py-6 md:py-8">
    <!-- Active Filters Tags -->
    <?php if (!empty($search_keyword) || isset($_GET['danhmuc']) || isset($_GET['thuonghieu']) || isset($_GET['min_price']) || isset($_GET['max_price'])): ?>
    <div class="mb-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-600">Bộ lọc đang áp dụng:</span>
            <a href="shop.php" class="text-sm text-red-600 hover:text-red-700 font-medium">
                <i class="fas fa-times-circle mr-1"></i>Xóa tất cả
            </a>
        </div>
        <div class="flex flex-wrap gap-2">
            <!-- ✅ Search Keyword Tag -->
            <?php if (!empty($search_keyword)): ?>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-red-50 text-red-600 rounded-full text-sm font-medium">
                <span><i class="fas fa-search mr-1"></i>Tìm: "<?php echo htmlspecialchars($search_keyword); ?>"</span>
                <button onclick="removeFilter('search')" class="w-5 h-5 bg-red-600 text-white rounded-full flex items-center justify-center hover:bg-red-700 transition">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['danhmuc']) && is_array($_GET['danhmuc'])): ?>
                <?php foreach ($_GET['danhmuc'] as $slug): ?>
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-red-50 text-red-600 rounded-full text-sm font-medium">
                    <span><i class="fas fa-folder mr-1"></i><?php echo htmlspecialchars(getFilterDisplayName('danhmuc', $slug)); ?></span>
                    <button onclick="removeSingleFilter('danhmuc', '<?php echo $slug; ?>')" class="w-5 h-5 bg-red-600 text-white rounded-full flex items-center justify-center hover:bg-red-700 transition">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if (isset($_GET['thuonghieu']) && is_array($_GET['thuonghieu'])): ?>
                <?php foreach ($_GET['thuonghieu'] as $slug): ?>
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-red-50 text-red-600 rounded-full text-sm font-medium">
                    <span><i class="fas fa-tag mr-1"></i><?php echo htmlspecialchars(getFilterDisplayName('thuonghieu', $slug)); ?></span>
                    <button onclick="removeSingleFilter('thuonghieu', '<?php echo $slug; ?>')" class="w-5 h-5 bg-red-600 text-white rounded-full flex items-center justify-center hover:bg-red-700 transition">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if (isset($_GET['min_price']) || isset($_GET['max_price'])): ?>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-red-50 text-red-600 rounded-full text-sm font-medium">
                <span><i class="fas fa-money-bill mr-1"></i>Giá: <?php echo formatPrice($min_price); ?> - <?php echo formatPrice($max_price); ?></span>
                <button onclick="removeFilter('price')" class="w-5 h-5 bg-red-600 text-white rounded-full flex items-center justify-center hover:bg-red-700 transition">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content: Sidebar + Product Grid -->
    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Sidebar Filter - Desktop ONLY -->
        <div class="hidden lg:block lg:w-80 flex-shrink-0">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 sticky top-24" id="desktop-sidebar">
                <div class="p-5 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-lg flex items-center">
                            <i class="fas fa-filter mr-2 text-red-600"></i>
                            Bộ lọc tìm kiếm
                        </h3>
                        <button type="button" id="reset-desktop-filter" class="text-sm text-red-600 hover:text-red-700 font-medium transition">
                            <i class="fas fa-undo mr-1"></i>Reset
                        </button>
                    </div>
                </div>
                <div class="p-5 space-y-4 max-h-[calc(100vh-200px)] overflow-y-auto custom-scrollbar" id="desktop-filter-content">
                    
                    <!-- ✅ Ô TÌM KIẾM THEO TÊN - TAILWIND (ĐÃ BỎ NÚT TÌM) -->
                    <div class="border-b border-gray-100 pb-4">
                        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-search text-red-600 mr-2"></i>
                            Tìm kiếm sản phẩm
                        </h4>
                        <div class="relative">
                            <input type="text" id="search-input" 
                                class="w-full pl-4 pr-10 py-2.5 border-2 border-gray-200 rounded-lg text-sm focus:border-red-500 focus:outline-none transition"
                                placeholder="Nhập tên sản phẩm..." 
                                value="<?php echo htmlspecialchars($search_keyword); ?>">
                            <?php if (!empty($search_keyword)): ?>
                            <button type="button" onclick="clearSearch()" 
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-600 transition p-1">
                                <i class="fas fa-times"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-gray-500 mt-2"><i class="fas fa-info-circle mr-1"></i>Nhấn Enter hoặc nút "Áp dụng bộ lọc" để tìm</p>
                    </div>

                    <!-- Danh mục -->
                    <div class="border-b border-gray-100 pb-4">
                        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-folder text-red-600 mr-2"></i>
                            Danh mục sản phẩm
                            <span class="text-xs font-normal text-gray-500 ml-auto">(Chọn nhiều)</span>
                        </h4>
                        <div class="filter-btn-grid">
                            <?php
                            $categories_result->data_seek(0);
                            while ($category = $categories_result->fetch_assoc()):
                            ?>
                            <label class="filter-btn">
                                <input type="checkbox" name="danhmuc[]" value="<?php echo $category['slug']; ?>"
                                    <?php echo isActiveFilter('danhmuc', $category['slug']) ? 'checked' : ''; ?>>
                                <div class="btn-content">
                                    <div class="btn-label">
                                        <i class="fas fa-circle text-[6px] <?php echo isActiveFilter('danhmuc', $category['slug']) ? 'text-red-600' : 'text-gray-300'; ?>"></i>
                                        <span class="text-sm" title="<?php echo htmlspecialchars($category['Ten_danhmuc']); ?>"><?php echo htmlspecialchars($category['Ten_danhmuc']); ?></span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="btn-count"><?php echo $category['product_count']; ?></span>
                                        <i class="fas fa-check-circle check-icon"></i>
                                    </div>
                                </div>
                            </label>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Thương hiệu -->
                    <div class="border-b border-gray-100 pb-4">
                        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-tag text-red-600 mr-2"></i>
                            Thương hiệu
                            <span class="text-xs font-normal text-gray-500 ml-auto">(Chọn nhiều)</span>
                        </h4>
                        <div class="filter-btn-grid">
                            <?php foreach ($brands_list as $brand): ?>
                            <label class="filter-btn">
                                <input type="checkbox" name="thuonghieu[]" value="<?php echo $brand['slug']; ?>"
                                    <?php echo isActiveFilter('thuonghieu', $brand['slug']) ? 'checked' : ''; ?>>
                                <div class="btn-content">
                                    <div class="btn-label">
                                        <i class="fas fa-circle text-[6px] <?php echo isActiveFilter('thuonghieu', $brand['slug']) ? 'text-red-600' : 'text-gray-300'; ?>"></i>
                                        <span class="text-sm" title="<?php echo htmlspecialchars($brand['Ten_thuonghieu']); ?>"><?php echo htmlspecialchars($brand['Ten_thuonghieu']); ?></span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="btn-count"><?php echo $brand['product_count']; ?></span>
                                        <i class="fas fa-check-circle check-icon"></i>
                                    </div>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Khoảng giá -->
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-money-bill-wave text-red-600 mr-2"></i>
                            Khoảng giá
                        </h4>
                        <div class="flex items-center gap-3 mb-4">
                            <div class="flex-1">
                                <label class="text-xs text-gray-500 mb-1 block">Từ</label>
                                <input type="text" id="price-min" 
                                    class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg text-right text-sm focus:border-red-500 focus:outline-none transition"
                                    value="<?php echo number_format($min_price, 0, ',', '.'); ?>" placeholder="0₫">
                            </div>
                            <span class="text-gray-400 pt-5">-</span>
                            <div class="flex-1">
                                <label class="text-xs text-gray-500 mb-1 block">Đến</label>
                                <input type="text" id="price-max" 
                                    class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg text-right text-sm focus:border-red-500 focus:outline-none transition"
                                    value="<?php echo number_format($max_price, 0, ',', '.'); ?>" placeholder="50.000.000₫">
                            </div>
                        </div>
                        <button type="button" id="apply-filter" 
                            class="w-full bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg text-sm font-semibold transition shadow-md shadow-red-200">
                            <i class="fas fa-filter mr-2"></i>Áp dụng bộ lọc
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <!-- Main Content - Product Grid -->
        <div class="flex-1">
            <!-- Header with sort and count -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="text-sm text-gray-600">
                        Hiển thị
                        <?php if ($total_products > 0): ?>
                        <span class="font-semibold text-gray-900"><?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $total_products); ?></span>
                        <?php else: ?>
                        <span class="font-semibold text-gray-900">0</span>
                        <?php endif; ?>
                        trong <span class="font-semibold text-gray-900"><?php echo $total_products; ?></span> kết quả
                        <?php if (!empty($search_keyword)): ?>
                        <span class="text-red-600"> cho "<?php echo htmlspecialchars($search_keyword); ?>"</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600 hidden sm:inline"><i class="fas fa-sort mr-1"></i>Sắp xếp:</span>
                        <div class="sort-dropdown">
                            <select id="sort-select" class="sort-select">
                                <option value="newest" <?php echo (!isset($_GET['sort']) || $_GET['sort'] == 'newest') ? 'selected' : ''; ?>>Mới nhất</option>
                                <option value="price_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price_asc') ? 'selected' : ''; ?>>Giá thấp nhất</option>
                                <option value="price_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price_desc') ? 'selected' : ''; ?>>Giá cao nhất</option>
                                <option value="name_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'name_asc') ? 'selected' : ''; ?>>Tên A-Z</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Filter Button -->
            <div class="lg:hidden mb-4">
                <button id="open-mobile-filter-btn" class="w-full bg-white border-2 border-gray-200 rounded-xl py-3 px-4 flex items-center justify-center gap-2 hover:border-red-500 hover:bg-red-50 transition shadow-sm">
                    <i class="fas fa-filter text-red-600"></i>
                    <span class="font-semibold">Bộ lọc sản phẩm</span>
                    <?php if (!empty($search_keyword) || isset($_GET['danhmuc']) || isset($_GET['thuonghieu']) || isset($_GET['min_price'])): ?>
                    <span class="bg-red-600 text-white text-xs px-2 py-0.5 rounded-full ml-1">Active</span>
                    <?php endif; ?>
                </button>
            </div>

            <!-- Product Grid -->
            <?php if ($products->num_rows > 0): ?>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php while ($product = $products->fetch_assoc()):
                $discount = calculateDiscount($product['GiaNhapTB'], $product['GiaBan']);
                ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden group hover:shadow-lg transition duration-300">
                    <a href="product.php?id=<?php echo $product['SanPham_id']; ?>">
                        <div class="relative">
                            <?php if ($product['SoLuongTon'] > 0): ?>
                                <?php if ($discount > 0): ?>
                                <div class="absolute top-2 right-2 z-10">
                                    <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full font-semibold">-<?php echo $discount; ?>%</span>
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                            <div class="absolute top-2 left-2 z-10">
                                <span class="bg-gray-500 text-white text-xs px-2 py-1 rounded-full font-semibold">Hết hàng</span>
                            </div>
                            <?php endif; ?>
                            <div class="aspect-square bg-gray-100 flex items-center justify-center p-4">
                                <img src="..<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['TenSP']); ?>"
                                    class="w-full h-full object-contain mix-blend-multiply group-hover:scale-110 transition-transform duration-300"
                                    onerror="this.src='../img/sanpham/placeholder.png'">
                            </div>
                        </div>
                        <div class="p-3">
                            <h3 class="font-medium text-sm mb-2 line-clamp-2 h-10 hover:text-red-600 transition">
                                <?php echo htmlspecialchars($product['TenSP']); ?>
                            </h3>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-red-600 font-bold"><?php echo formatPrice($product['GiaBan']); ?></span>
                                <?php if ($discount > 0): ?>
                                <span class="text-gray-400 text-xs line-through"><?php echo formatPrice($product['GiaNhapTB']); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($product['SoLuongTon'] < 10 && $product['SoLuongTon'] > 0): ?>
                            <div class="text-xs text-orange-500 mt-1 font-semibold">
                                <i class="fas fa-exclamation-triangle mr-1"></i>Chỉ còn <?php echo $product['SoLuongTon']; ?> sản phẩm
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="flex justify-center mt-8">
                <div class="flex items-center gap-1">
                    <a href="<?php echo buildFilterUrl(['page' => $page - 1]); ?>" class="w-10 h-10 flex items-center justify-center rounded-lg border-2 border-gray-200 bg-white hover:bg-gray-50 hover:border-red-300 transition <?php echo ($page <= 1) ? 'pointer-events-none opacity-50' : ''; ?>">
                        <i class="fas fa-chevron-left text-sm"></i>
                    </a>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                        <span class="w-10 h-10 flex items-center justify-center rounded-lg bg-red-600 text-white font-semibold shadow-md shadow-red-200"><?php echo $i; ?></span>
                        <?php elseif ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                        <a href="<?php echo buildFilterUrl(['page' => $i]); ?>" class="w-10 h-10 flex items-center justify-center rounded-lg border-2 border-gray-200 bg-white hover:bg-gray-50 hover:border-red-300 transition">
                            <?php echo $i; ?>
                        </a>
                        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                        <span class="w-10 h-10 flex items-center justify-center">...</span>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <a href="<?php echo buildFilterUrl(['page' => $page + 1]); ?>" class="w-10 h-10 flex items-center justify-center rounded-lg border-2 border-gray-200 bg-white hover:bg-gray-50 hover:border-red-300 transition <?php echo ($page >= $total_pages) ? 'pointer-events-none opacity-50' : ''; ?>">
                        <i class="fas fa-chevron-right text-sm"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                <i class="fas fa-box-open text-5xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Không tìm thấy sản phẩm</h3>
                <p class="text-gray-500 mb-4">
                    <?php if (!empty($search_keyword)): ?>
                    Không có sản phẩm nào phù hợp với từ khóa "<?php echo htmlspecialchars($search_keyword); ?>".
                    <?php else: ?>
                    Không có sản phẩm nào phù hợp với bộ lọc của bạn.
                    <?php endif; ?>
                </p>
                <a href="shop.php" class="inline-block px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-semibold shadow-md shadow-red-200">
                    <i class="fas fa-times-circle mr-2"></i>Xóa bộ lọc
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</main>

<!-- Footer -->
<footer id="footer" class="bg-black text-white mt-12">
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="pl-5">
                <h3 class="text-4xl font-bold mb-4">Boost<br>your power</h3>
                <div class="flex space-x-3 mb-4">
                    <a href="https://www.facebook.com/nvbplay" target="_blank" class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://www.tiktok.com/@nvbplay.vn" target="_blank" class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition">
                        <i class="fab fa-tiktok"></i>
                    </a>
                    <a href="https://s.shopee.vn/6AV9qQcpMz" target="_blank" class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition">
                        <i class="fas fa-shopping-bag"></i>
                    </a>
                </div>
            </div>
            <div>
                <h3 class="text-xl font-bold mb-4">Thông tin khác</h3>
                <ul class="space-y-2">
                    <li><a href="#" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH BẢO MẬT</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH THANH TOÁN</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH BẢO HÀNH ĐỔI TRẢ</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-xl font-bold mb-4">Về chúng tôi</h3>
                <ul class="space-y-3">
                    <li><a href="https://maps.app.goo.gl/mwqaes9hQJks8FSu5" target="_blank" class="flex"><span class="font-semibold w-20">Địa chỉ:</span><span class="text-gray-400">62 Lê Bình, Tân An, Cần Thơ</span></a></li>
                    <li><div class="flex"><span class="font-semibold w-20">Giờ làm việc:</span><span class="text-gray-400">08:00 - 21:00</span></div></li>
                    <li><a href="tel:0987.879.243" class="flex"><span class="font-semibold w-20">Hotline:</span><span class="text-gray-400">0987.879.243</span></a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-800 my-6"></div>
        <div class="flex flex-col md:flex-row justify-between items-center">
            <div class="text-gray-500 text-sm mb-4 md:mb-0">
                <p>©2025 CÔNG TY CỔ PHẦN NVB PLAY</p>
            </div>
            <a href="http://online.gov.vn/Home/WebDetails/129261" target="_blank">
                <img src="https://nvbplay.vn/wp-content/uploads/2024/09/Logo-Bo-Cong-Thuong-Xanh.png" alt="Bộ Công Thương" class="h-12 w-auto">
            </a>
        </div>
    </div>
</footer>
</div>

<!-- Mobile Menu -->
<div id="main-menu" class="fixed inset-0 bg-white z-50 transform -translate-x-full transition duration-300 md:hidden overflow-y-auto">
    <div class="p-4">
        <div class="flex justify-between items-center mb-6">
            <img src="../img/icons/logonvb.png" height="30" width="50" class="transform scale-75">
            <button class="close-menu p-2 hover:bg-gray-100 rounded-full transition">
                <i class="fas fa-times text-2xl text-gray-600"></i>
            </button>
        </div>
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
            <a href="my-account.php" class="flex items-center text-gray-700">
                <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                    <i class="far fa-user text-xl text-gray-600"></i>
                </div>
                <div>
                    <div class="font-semibold">Tài khoản</div>
                    <span class="text-sm text-gray-500">Đăng nhập / Đăng ký</span>
                </div>
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    console.log('🚀 DOM loaded – script started');
    
    // === MOBILE FILTER DRAWER ===
    const mobileOpenBtn = document.getElementById('open-mobile-filter-btn');
    const mobileCloseBtn = document.getElementById('close-mobile-filter');
    const mobileDrawer = document.getElementById('mobile-filter-drawer');
    const mobileOverlay = document.getElementById('mobile-filter-overlay');
    const desktopFilterContent = document.getElementById('desktop-filter-content');
    const mobileFilterContent = document.getElementById('mobile-filter-content');
    
    function openMobileFilter() {
        // Clone desktop content to mobile (giữ nguyên ID và name)
        if (desktopFilterContent && mobileFilterContent) {
            mobileFilterContent.innerHTML = desktopFilterContent.innerHTML;
        }
        
        mobileDrawer.classList.remove('closed');
        mobileDrawer.classList.add('open');
        mobileOverlay.classList.remove('hidden');
        mobileOverlay.classList.add('visible');
        document.body.classList.add('filter-open');
    }
    
    function closeMobileFilter() {
        mobileDrawer.classList.remove('open');
        mobileDrawer.classList.add('closed');
        mobileOverlay.classList.remove('visible');
        mobileOverlay.classList.add('hidden');
        document.body.classList.remove('filter-open');
    }
    
    if (mobileOpenBtn) mobileOpenBtn.addEventListener('click', openMobileFilter);
    if (mobileCloseBtn) mobileCloseBtn.addEventListener('click', closeMobileFilter);
    if (mobileOverlay) mobileOverlay.addEventListener('click', closeMobileFilter);
    
    // === APPLY FILTER FUNCTION (DÙNG CHUNG CHO CẢ DESKTOP VÀ MOBILE) ===
    function applyFilter() {
        console.log('🎯 applyFilter called');
        const params = new URLSearchParams();
        
        // Get search keyword (dùng chung ID cho cả desktop và mobile)
        const searchInput = document.getElementById('search-input');
        if (searchInput && searchInput.value.trim() !== '') {
            params.set('search', searchInput.value.trim());
        }
        
        // Get multiple categories (dùng chung name cho cả desktop và mobile)
        const selectedCats = document.querySelectorAll('input[name="danhmuc[]"]:checked');
        console.log('Selected categories:', selectedCats.length);
        selectedCats.forEach(cat => {
            params.append('danhmuc[]', cat.value);
        });
        
        // Get multiple brands (dùng chung name cho cả desktop và mobile)
        const selectedBrands = document.querySelectorAll('input[name="thuonghieu[]"]:checked');
        console.log('Selected brands:', selectedBrands.length);
        selectedBrands.forEach(brand => {
            params.append('thuonghieu[]', brand.value);
        });
        
        // Get price values (dùng chung ID cho cả desktop và mobile)
        const minPrice = document.getElementById('price-min');
        const maxPrice = document.getElementById('price-max');
        if (minPrice && maxPrice) {
            const minVal = parseInt(minPrice.value.replace(/\./g, ''));
            const maxVal = parseInt(maxPrice.value.replace(/\./g, ''));
            console.log('Price range:', minVal, '-', maxVal);
            if (minVal > 0) {
                params.set('min_price', minVal);
            }
            if (maxVal < 50000000) {
                params.set('max_price', maxVal);
            }
        }
        
        // Get sort
        const sortSelect = document.getElementById('sort-select');
        if (sortSelect && sortSelect.value !== 'newest') {
            params.set('sort', sortSelect.value);
        }
        
        const newUrl = 'shop.php?' + params.toString();
        console.log('🔄 Redirecting to:', newUrl);
        window.location.href = newUrl;
    }
    
    // === RESET FILTER FUNCTION ===
    function resetFilter() {
        console.log('🔄 resetFilter called');
        
        // Reset search input
        const searchInput = document.getElementById('search-input');
        if (searchInput) searchInput.value = '';
        
        // Reset price inputs
        const minPrice = document.getElementById('price-min');
        const maxPrice = document.getElementById('price-max');
        if (minPrice) minPrice.value = '0';
        if (maxPrice) maxPrice.value = '50.000.000';
        
        // Reset all checkboxes
        document.querySelectorAll('input[name="danhmuc[]"]').forEach(cb => cb.checked = false);
        document.querySelectorAll('input[name="thuonghieu[]"]').forEach(cb => cb.checked = false);
        
        window.location.href = 'shop.php';
    }
    
    // === CLEAR SEARCH FUNCTION ===
    window.clearSearch = function() {
        const searchInput = document.getElementById('search-input');
        if (searchInput) searchInput.value = '';
        applyFilter();
    };
    
    // === REMOVE SINGLE FILTER ===
    window.removeFilter = function(filterType) {
        const url = new URL(window.location.href);
        const params = new URLSearchParams(url.search);
        
        if (filterType === 'danhmuc') {
            params.delete('danhmuc[]');
        } else if (filterType === 'thuonghieu') {
            params.delete('thuonghieu[]');
        } else if (filterType === 'price') {
            params.delete('min_price');
            params.delete('max_price');
        } else if (filterType === 'search') {
            params.delete('search');
        }
        
        window.location.href = 'shop.php?' + params.toString();
    };
    
    // === REMOVE SINGLE FILTER VALUE ===
    window.removeSingleFilter = function(filterType, value) {
        const url = new URL(window.location.href);
        const params = new URLSearchParams(url.search);
        
        let values = params.getAll(filterType + '[]');
        values = values.filter(v => v !== value);
        
        params.delete(filterType + '[]');
        values.forEach(v => params.append(filterType + '[]', v));
        
        window.location.href = 'shop.php?' + params.toString();
    };
    
    // === EVENT LISTENERS ===
    // Desktop apply button
    const applyBtn = document.getElementById('apply-filter');
    if (applyBtn) {
        applyBtn.addEventListener('click', function (e) {
            e.preventDefault();
            console.log('✅ Desktop apply button clicked');
            applyFilter();
        });
    } else {
        console.error('❌ Apply button NOT found');
    }
    
    //  Mobile apply button 
    const applyMobileBtn = document.getElementById('apply-mobile-filter');
    if (applyMobileBtn) {
        applyMobileBtn.addEventListener('click', function (e) {
            e.preventDefault();
            console.log('✅ Mobile apply button clicked');
            applyFilter();
            closeMobileFilter();
        });
    } else {
        console.error('❌ Mobile apply button NOT found');
    }
    
    // Reset button
    const resetBtn = document.getElementById('reset-desktop-filter');
    if (resetBtn) {
        resetBtn.addEventListener('click', function (e) {
            e.preventDefault();
            resetFilter();
        });
    }
    
    const resetMobileBtn = document.getElementById('reset-mobile-filter');
    if (resetMobileBtn) {
        resetMobileBtn.addEventListener('click', function (e) {
            e.preventDefault();
            resetFilter();
            closeMobileFilter();
        });
    }
    
    // Sort select
    const sortSelect = document.getElementById('sort-select');
    if (sortSelect) {
        sortSelect.addEventListener('change', function () {
            const url = new URL(window.location.href);
            const params = new URLSearchParams(url.search);
            if (this.value !== 'newest') {
                params.set('sort', this.value);
            } else {
                params.delete('sort');
            }
            window.location.href = 'shop.php?' + params.toString();
        });
    }
    
    // Enter key for search
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyFilter();
            }
        });
    }
    
    // Mobile menu toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const closeMenu = document.querySelector('.close-menu');
    const mobileMenu = document.getElementById('main-menu');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function () {
            mobileMenu.classList.remove('-translate-x-full');
            document.body.style.overflow = 'hidden';
        });
    }
    
    if (closeMenu) {
        closeMenu.addEventListener('click', function () {
            mobileMenu.classList.add('-translate-x-full');
            document.body.style.overflow = '';
        });
    }
    
    console.log('✅ All event listeners registered');
});
</script>
</body>
</html>