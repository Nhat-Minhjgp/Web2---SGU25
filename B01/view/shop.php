<!DOCTYPE html>
<html lang="vi" prefix="og: https://ogp.me/ns#">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shop | NVBPlay </title>
<meta name="description" content="NVBPlay chuyên cung cấp đồ cầu lông và pickleball cao cấp, từ vợt, giày, đến phụ kiện chính hãng. Nâng cao trải nghiệm của bạn tại NVBPlay.">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<!-- noUiSlider CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.0/nouislider.min.css" rel="stylesheet">

<style>
/* Custom styles */
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #888; border-radius: 3px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #555; }
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
.rotate-180 { transform: rotate(180deg); }
.category-toggle.active { background-color: #fef2f2; color: #dc2626; }
.category-submenu { transition: all 0.3s ease; }
body.menu-open { overflow: hidden; }
body.filter-open { overflow: hidden; }

/* Mobile Filter Drawer Styles (chỉ dùng cho mobile) */
#mobile-filter-drawer {
    transition: transform 0.3s ease-in-out;
}
#mobile-filter-drawer.open { transform: translateX(0); }
#mobile-filter-drawer.closed { transform: translateX(100%); }
#mobile-filter-overlay {
    transition: opacity 0.3s ease;
}
#mobile-filter-overlay.hidden { opacity: 0; pointer-events: none; }
#mobile-filter-overlay.visible { opacity: 1; pointer-events: auto; }

/* noUiSlider Customization */
.noUi-connect { background: #dc2626; }
.noUi-handle { border: 2px solid #dc2626; box-shadow: none; }
.noUi-target { border: none; box-shadow: none; background: #e5e7eb; height: 6px; }
.noUi-handle { width: 20px; height: 20px; border-radius: 50%; top: -7px; right: -10px; }
</style>
 <link rel="icon" type="image/svg+xml" href="../img/icons/favicon.png"
        sizes="32x32">
</head>
<body class="font-sans antialiased bg-gray-50">

<!-- Popup Overlay -->
<div id="popup_overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50"></div>

<!-- Mobile Filter Overlay & Drawer (CHỈ CHO MOBILE) -->
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

    <!-- Drawer Content (Scrollable) - Copy nội dung filter vào đây -->
    <div class="flex-1 overflow-y-auto p-5 custom-scrollbar space-y-6" id="mobile-filter-content">
        <!-- Nội dung sẽ được clone từ desktop sidebar bằng JS -->
    </div>

    <!-- Drawer Footer -->
    <div class="p-4 border-t bg-white sticky bottom-0 z-10 flex gap-3">
        <button type="button" id="reset-mobile-filter" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 py-3 rounded-lg font-medium transition">
            Reset
        </button>
        <button type="button" id="apply-mobile-filter" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg font-medium transition shadow-lg shadow-red-200">
            Apply
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
                    <button class="menu-toggle p-2"><img src="../img/icons/menu.svg" class="fas fa-bars text-2xl"></button>
                </div>
                <div class="hidden md:flex items-center flex-1 ml-6">
                    <ul class="flex items-center space-x-4">
                        <li class="relative" id="mega-menu-container">
                            <button id="mega-menu-trigger" class="button-menu flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200 transition">
                                <img src="../img/icons/menu.svg" class="w-5 h-5 mr-2" alt="menu">
                                <span>Danh mục</span>
                            </button>
                            <div id="mega-menu-dropdown" class="absolute left-0 top-full mt-2 w-[900px] bg-white rounded-lg shadow-xl hidden z-50">
                                <div class="flex p-4">
                                    <div class="w-64 border-r border-gray-200 pr-4">
                                        <div class="icon-box-menu active bg-red-50 rounded-lg p-3 mb-1 cursor-pointer hover:bg-red-50 transition flex items-start" data-menu="badminton">
                                            <div class="w-8 h-8 flex-shrink-0 mr-3"><img src="https://nvbplay.vn/wp-content/uploads/2024/10/badminton-No.svg" alt="Cầu Lông" class="w-full h-full"></div>
                                            <div><p class="font-bold text-red-600">Cầu Lông</p><p class="text-xs text-gray-500">Trang bị cầu lông chuyên nghiệp</p></div>
                                        </div>
                                        <div class="icon-box-menu p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start" data-menu="pickleball">
                                            <div class="w-8 h-8 flex-shrink-0 mr-3"><img src="https://nvbplay.vn/wp-content/uploads/2024/10/pickleball-No.svg" alt="Pickleball" class="w-full h-full"></div>
                                            <div><p class="font-bold">Pickleball</p><p class="text-xs text-gray-500">Trang bị pickleball hàng đầu</p></div>
                                        </div>
                                    </div>
                                    <div class="flex-1 pl-4">
                                        <div id="content-badminton" class="menu-content">
                                            <div class="mb-4">
                                                <div class="flex items-center justify-between mb-2"><h3 class="font-bold">Thương hiệu nổi bật</h3></div>
                                                <div class="grid grid-cols-4 gap-2">
                                                    <a href="#" class="flex flex-col items-center text-center group"><div class="w-12 h-12 mb-1"><img src="https://nvbplay.vn/wp-content/uploads/2024/10/logo-300x214-1-150x150.webp" alt="Yonex" class="w-full h-full object-contain"></div><span class="text-xs">YONEX</span></a>
                                                    <a href="#" class="flex flex-col items-center text-center group"><div class="w-12 h-12 mb-1"><img src="https://nvbplay.vn/wp-content/uploads/2024/10/ave6by86s-300x300-1-150x150.webp" alt="Adidas" class="w-full h-full object-contain"></div><span class="text-xs">ADIDAS</span></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li><a href="./shop.php" class="flex items-center text-gray-700 hover:text-red-600 font-medium"><img src="../img/icons/store.svg" class="w-5 h-5 flex-shrink-0 mr-2"><span>CỬA HÀNG</span></a></li>
                    </ul>
                </div>
                <div id="logo" class="flex-shrink-1 absolute left-1/2 transform -translate-x-1/2">
                    <a href="../index.php" title="NVBPlay" rel="home"><img width="100" height="40" src="https://nvbplay.vn/wp-content/uploads/2024/08/LOGO-NVB-PLAY-NEW-04-RED.png" alt="NVBPlay" class="h-12 md:h-14 w-auto transform scale-75"></a>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <div class="address-book"><a href="/my-account/address-book?back=true" class="flex items-center text-gray-700 hover:text-red-600"><i class="fas fa-map-marker-alt mr-1"></i><span class="shipping-address text-sm"><span class="text">Chọn địa chỉ</span></span></a></div>
                    <div class="h-5 w-px bg-gray-300"></div>
                    <div class="search-header relative"><button class="search-toggle p-2"><i class="fas fa-search text-gray-700 hover:text-red-600"></i></button></div>
                    <a href="https://nvbplay.vn/my-account" class="p-2"><i class="far fa-user text-gray-700 hover:text-red-600 text-xl"></i></a>
                    <a href="https://nvbplay.vn/cart" class="relative p-2"><i class="fas fa-shopping-basket text-gray-700 hover:text-red-600 text-xl"></i><span class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">0</span></a>
                </div>
                <div class="md:hidden flex items-center space-x-3">
                    <button class="search-toggle p-1"><i class="fas fa-search text-xl"></i></button>
                    <a href="https://nvbplay.vn/cart" class="relative p-1"><i class="fas fa-shopping-basket text-xl"></i><span class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">0</span></a>
                </div>
            </div>
        </div>
        <div id="wide-nav" class="bg-gray-900 text-white py-2">
            <div class="container mx-auto px-4 text-center">
                <div class="top-hot"><a href="https://nvbplay.vn/product-tag/control-collection" class="text-white hover:text-yellow-300 transition text-sm md:text-base">⚡ VỢT YONEX NANOFLARE 1000 GAME - RESTOCKED ⚡</a></div>
            </div>
        </div>
    </div>
</header>

<!-- Hidden H1 for SEO -->
<h1 class="hidden">NVBPlay chuyên cung cấp đồ cầu lông và pickleball cao cấp, từ vợt, giày, đến phụ kiện chính hãng. Nâng cao trải nghiệm của bạn tại NVBPlay.</h1>

<main>
    <div class="container mx-auto px-4 py-6 md:py-8">
        <!-- Breadcrumb -->
        <div class="text-sm text-gray-500 mb-4">
            <span>Trang chủ</span> <i class="fas fa-chevron-right mx-2 text-xs"></i>
            <span class="text-gray-700">Sản phẩm</span>
        </div>

        <!-- Main Content: Sidebar + Product Grid (Original Layout) -->
        <div class="flex flex-col lg:flex-row gap-6">
            
            <!-- Sidebar Filter - Desktop ONLY (Reverted) -->
            <div class="hidden lg:block lg:w-72 flex-shrink-0">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 sticky top-24" id="desktop-sidebar">
                    <div class="p-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-lg flex items-center">
                                <i class="fas fa-filter mr-2 text-gray-500"></i>
                                Bộ lọc tìm kiếm
                            </h3>
                            <button type="button" id="reset-desktop-filter" class="text-sm text-red-600 hover:text-red-700">Xóa tất cả</button>
                        </div>
                    </div>
                    <div class="p-4 space-y-4 max-h-[calc(100vh-200px)] overflow-y-auto custom-scrollbar">
                        
                        <!-- Danh mục -->
                        <div class="border-b border-gray-100 pb-4">
                            <h4 class="font-medium mb-2">Danh mục</h4>
                            <div class="space-y-2 ml-1">
                                <label class="flex items-center justify-between cursor-pointer">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="categories[]" value="vot-cau-long" class="filter-checkbox rounded text-red-600 focus:ring-red-500 mr-2">
                                        <span class="text-sm">Vợt cầu lông</span>
                                    </div>
                                    <span class="text-xs text-gray-500">(231)</span>
                                </label>
                                <label class="flex items-center justify-between cursor-pointer">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="categories[]" value="pickleball" class="filter-checkbox rounded text-red-600 focus:ring-red-500 mr-2">
                                        <span class="text-sm">Pickleball</span>
                                    </div>
                                    <span class="text-xs text-gray-500">(186)</span>
                                </label>
                                <label class="flex items-center justify-between cursor-pointer">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="categories[]" value="giay" class="filter-checkbox rounded text-red-600 focus:ring-red-500 mr-2">
                                        <span class="text-sm">Giày</span>
                                    </div>
                                    <span class="text-xs text-gray-500">(156)</span>
                                </label>
                                <label class="flex items-center justify-between cursor-pointer">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="categories[]" value="ao" class="filter-checkbox rounded text-red-600 focus:ring-red-500 mr-2">
                                        <span class="text-sm">Áo thể thao</span>
                                    </div>
                                    <span class="text-xs text-gray-500">(183)</span>
                                </label>
                            </div>
                        </div>

                        <!-- Đối tượng -->
                        <div class="border-b border-gray-100 pb-4">
                            <h4 class="font-medium mb-2">Đối tượng</h4>
                            <div class="space-y-2">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="gender" value="all" class="filter-radio text-red-600 focus:ring-red-500 mr-2" checked>
                                    <span class="text-sm">Tất cả</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="gender" value="nam" class="filter-radio text-red-600 focus:ring-red-500 mr-2">
                                    <span class="text-sm">Nam</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="gender" value="nu" class="filter-radio text-red-600 focus:ring-red-500 mr-2">
                                    <span class="text-sm">Nữ</span>
                                </label>
                            </div>
                        </div>

                        <!-- Thương hiệu -->
                        <div class="border-b border-gray-100 pb-4">
                            <h4 class="font-medium mb-2">Thương hiệu</h4>
                            <div class="space-y-2 max-h-48 overflow-y-auto">
                                <label class="flex items-center justify-between cursor-pointer">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="brands[]" value="yonex" class="filter-checkbox rounded text-red-600 focus:ring-red-500 mr-2">
                                        <span class="text-sm">Yonex</span>
                                    </div>
                                    <span class="text-xs text-gray-500">(417)</span>
                                </label>
                                <label class="flex items-center justify-between cursor-pointer">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="brands[]" value="li-ning" class="filter-checkbox rounded text-red-600 focus:ring-red-500 mr-2">
                                        <span class="text-sm">Li-Ning</span>
                                    </div>
                                    <span class="text-xs text-gray-500">(73)</span>
                                </label>
                                <label class="flex items-center justify-between cursor-pointer">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="brands[]" value="kamito" class="filter-checkbox rounded text-red-600 focus:ring-red-500 mr-2">
                                        <span class="text-sm">KAMITO</span>
                                    </div>
                                    <span class="text-xs text-gray-500">(106)</span>
                                </label>
                                <label class="flex items-center justify-between cursor-pointer">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="brands[]" value="joola" class="filter-checkbox rounded text-red-600 focus:ring-red-500 mr-2">
                                        <span class="text-sm">JOOLA</span>
                                    </div>
                                    <span class="text-xs text-gray-500">(35)</span>
                                </label>
                            </div>
                        </div>

                        <!-- Khoảng giá (noUiSlider) - Desktop -->
                        <div>
                            <h4 class="font-medium mb-3">Khoảng giá</h4>
                            <div id="price-slider-desktop" class="mb-4"></div>
                            <div class="flex items-center justify-between text-sm font-medium text-gray-700 mb-2">
                                <div class="flex items-center">
                                    <span class="mr-1">Từ:</span>
                                    <input type="text" id="price-min-desktop" class="w-20 p-1 border border-gray-300 rounded text-right text-xs focus:border-red-500 focus:outline-none" value="0">
                                    <span class="ml-1">₫</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="mr-1">Đến:</span>
                                    <input type="text" id="price-max-desktop" class="w-20 p-1 border border-gray-300 rounded text-right text-xs focus:border-red-500 focus:outline-none" value="50000000">
                                    <span class="ml-1">₫</span>
                                </div>
                            </div>
                            <button type="button" id="apply-desktop-filter" class="w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded text-sm font-medium transition">
                                Apply
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content - Product Grid -->
            <div class="flex-1">
                <!-- Header with sort and count -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="text-sm text-gray-600">
                            Hiển thị <span class="font-medium">1-12</span> trong <span class="font-medium">1.054</span> kết quả
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-600 hidden sm:inline">Sắp xếp theo:</span>
                            <select class="border border-gray-300 rounded-lg p-2 text-sm focus:outline-none focus:border-red-500">
                                <option>Mới nhất</option>
                                <option>Giá thấp nhất</option>
                                <option>Giá cao nhất</option>
                                <option>Tên A-Z</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Mobile Filter Button (ONLY visible on mobile) -->
                <div class="lg:hidden mb-4">
                    <button id="open-mobile-filter-btn" class="w-full bg-white border border-gray-300 rounded-lg py-3 px-4 flex items-center justify-center gap-2 hover:bg-gray-50 transition">
                        <i class="fas fa-filter text-gray-600"></i>
                        <span>Bộ lọc sản phẩm</span>
                    </button>
                </div>

                <!-- Product Grid -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <!-- Product 1 -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden group hover:shadow-md transition">
                        <div class="relative">
                            <div class="absolute top-2 left-2 z-10"><span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full">Hàng mới</span></div>
                            <div class="absolute top-2 right-2 z-10"><span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">-13%</span></div>
                            <div class="aspect-square bg-gray-100 flex items-center justify-center p-4">
                                <div class="w-full h-full bg-gradient-to-br from-gray-200 to-gray-300 rounded animate-pulse"></div>
                            </div>
                        </div>
                        <div class="p-3">
                            <h3 class="font-medium text-sm mb-2 line-clamp-2 h-10"><a href="#" class="hover:text-red-600">Giày Yonex Power Cushion 65 Z VA Men</a></h3>
                            <div class="flex items-center gap-2"><span class="text-red-600 font-bold">3.189.000₫</span></div>
                        </div>
                    </div>
                    <!-- Product 2 -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden group hover:shadow-md transition">
                        <div class="relative">
                            <div class="absolute top-2 left-2 z-10"><span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full">Hàng mới</span></div>
                            <div class="aspect-square bg-gray-100 flex items-center justify-center p-4">
                                <div class="w-full h-full bg-gradient-to-br from-gray-200 to-gray-300 rounded animate-pulse"></div>
                            </div>
                        </div>
                        <div class="p-3">
                            <h3 class="font-medium text-sm mb-2 line-clamp-2 h-10"><a href="#" class="hover:text-red-600">Giày Yonex Power Cushion 65 Z VA Women</a></h3>
                            <div class="flex items-center gap-2"><span class="text-red-600 font-bold">3.189.000₫</span></div>
                        </div>
                    </div>
                    <!-- Product 3 -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden group hover:shadow-md transition">
                        <div class="relative">
                            <div class="absolute top-2 left-2 z-10"><span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full">Hàng mới</span></div>
                            <div class="aspect-square bg-gray-100 flex items-center justify-center p-4">
                                <div class="w-full h-full bg-gradient-to-br from-gray-200 to-gray-300 rounded animate-pulse"></div>
                            </div>
                        </div>
                        <div class="p-3">
                            <h3 class="font-medium text-sm mb-2 line-clamp-2 h-10"><a href="#" class="hover:text-red-600">Giày Yonex Power Cushion 65 Z VA Wide</a></h3>
                            <div class="flex items-center gap-2"><span class="text-red-600 font-bold">3.189.000₫</span></div>
                        </div>
                    </div>
                    <!-- Product 4 -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden group hover:shadow-md transition">
                        <div class="relative">
                            <div class="absolute top-2 left-2 z-10"><span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full">Hàng mới</span></div>
                            <div class="absolute top-2 right-2 z-10"><span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">-13%</span></div>
                            <div class="aspect-square bg-gray-100 flex items-center justify-center p-4">
                                <div class="w-full h-full bg-gradient-to-br from-gray-200 to-gray-300 rounded animate-pulse"></div>
                            </div>
                        </div>
                        <div class="p-3">
                            <h3 class="font-medium text-sm mb-2 line-clamp-2 h-10"><a href="#" class="hover:text-red-600">Vợt Pickleball Joola Agassi Pro V</a></h3>
                            <div class="flex items-center gap-2"><span class="text-red-600 font-bold">6.890.000₫</span><span class="text-gray-400 text-xs line-through">7.890.000₫</span></div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="flex justify-center mt-8">
                    <div class="flex items-center gap-1">
                        <button class="w-10 h-10 flex items-center justify-center rounded-lg border border-gray-300 bg-white hover:bg-gray-50 disabled:opacity-50" disabled><i class="fas fa-chevron-left text-sm"></i></button>
                        <button class="w-10 h-10 flex items-center justify-center rounded-lg bg-red-600 text-white font-medium">1</button>
                        <button class="w-10 h-10 flex items-center justify-center rounded-lg border border-gray-300 bg-white hover:bg-gray-50">2</button>
                        <button class="w-10 h-10 flex items-center justify-center rounded-lg border border-gray-300 bg-white hover:bg-gray-50">3</button>
                        <span class="w-10 h-10 flex items-center justify-center">...</span>
                        <button class="w-10 h-10 flex items-center justify-center rounded-lg border border-gray-300 bg-white hover:bg-gray-50">53</button>
                        <button class="w-10 h-10 flex items-center justify-center rounded-lg border border-gray-300 bg-white hover:bg-gray-50"><i class="fas fa-chevron-right text-sm"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Footer -->
<footer id="footer" class="bg-black text-white">
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="pl-5">
                <h3 class="text-4xl font-bold mb-4">Boost<br>your power</h3>
                <div class="flex space-x-3 mb-4">
                    <a href="https://www.facebook.com/nvbplay" target="_blank" class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.tiktok.com/@nvbplay.vn" target="_blank" class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition"><i class="fab fa-tiktok"></i></a>
                    <a href="https://s.shopee.vn/6AV9qQcpMz" target="_blank" class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition"><i class="fas fa-shopping-bag"></i></a>
                </div>
            </div>
            <div>
                <h3 class="text-xl font-bold mb-4">Thông tin khác</h3>
                <ul class="space-y-2">
                    <li><a href="" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH BẢO MẬT</a></li>
                    <li><a href="" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH THANH TOÁN</a></li>
                    <li><a href="a" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH BẢO HÀNH ĐỔI TRẢ</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-xl font-bold mb-4">Về chúng tôi</h3>
                <ul class="space-y-3">
                    <li><a href="https://maps.app.goo.gl/mwqaes9hQJks8FSu5" target="_blank" class="flex"><span class="font-medium w-20">Địa chỉ:</span><span class="text-gray-400">62 Lê Bình, Tân An, Cần Thơ</span></a></li>
                    <li><div class="flex"><span class="font-medium w-20">Giờ làm việc:</span><span class="text-gray-400">08:00 - 21:00</span></div></li>
                    <li><a href="tel:0987.879.243" class="flex"><span class="font-medium w-20">Hotline:</span><span class="text-gray-400">0987.879.243</span></a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-800 my-6"></div>
        <div class="flex flex-col md:flex-row justify-between items-center">
            <div class="text-gray-500 text-sm mb-4 md:mb-0">
                <p>©2025 CÔNG TY CỔ PHẦN NVB PLAY</p>
            </div>
            <a href="http://online.gov.vn/Home/WebDetails/129261" target="_blank"><img src="https://nvbplay.vn/wp-content/uploads/2024/09/Logo-Bo-Cong-Thuong-Xanh.png" alt="Bộ Công Thương" class="h-12 w-auto"></a>
        </div>
    </div>
    <!-- Mobile Bottom Navigation -->
    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 text-gray-700 z-50">
        <div class="grid grid-cols-5 gap-1 p-2">
            <a href="/" class="flex flex-col items-center text-red-600"><i class="fas fa-home text-xl"></i><span class="text-xs">Trang chủ</span></a>
            <a href="/blogs" class="flex flex-col items-center"><i class="fas fa-newspaper text-xl"></i><span class="text-xs">Bảng tin</span></a>
            <a href="#" class="flex flex-col items-center"><i class="fas fa-bell text-xl"></i><span class="text-xs">Thông báo</span></a>
            <a href="https://nvbplay.vn/cart" class="flex flex-col items-center"><i class="fas fa-shopping-cart text-xl"></i><span class="text-xs">Giỏ hàng</span></a>
            <a href="https://nvbplay.vn/my-account" class="flex flex-col items-center"><i class="fas fa-user text-xl"></i><span class="text-xs">Tài khoản</span></a>
        </div>
    </div>
</footer>

<!-- noUiSlider JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.0/nouislider.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // === MOBILE FILTER DRAWER LOGIC ===
    const mobileOpenBtn = document.getElementById('open-mobile-filter-btn');
    const mobileCloseBtn = document.getElementById('close-mobile-filter');
    const mobileDrawer = document.getElementById('mobile-filter-drawer');
    const mobileOverlay = document.getElementById('mobile-filter-overlay');
    const desktopSidebar = document.getElementById('desktop-sidebar');
    const mobileFilterContent = document.getElementById('mobile-filter-content');

    // Clone desktop sidebar content to mobile drawer (for sync)
    if (desktopSidebar && mobileFilterContent) {
        // Clone the inner content (excluding header/footer)
        const filterContent = desktopSidebar.querySelector('.p-4.space-y-4');
        if (filterContent) {
            mobileFilterContent.innerHTML = filterContent.outerHTML;
        }
    }

    function openMobileFilter() {
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


    // === PRICE SLIDER FUNCTION (Shared for Desktop & Mobile) ===
    function initPriceSlider(sliderId, minInputId, maxInputId) {
        const slider = document.getElementById(sliderId);
        const minInput = document.getElementById(minInputId);
        const maxInput = document.getElementById(maxInputId);
        
        if (!slider) return null;

        noUiSlider.create(slider, {
            start: [0, 50000000],
            connect: true,
            range: { 'min': 0, 'max': 50000000 },
            step: 10000,
            format: {
                to: function(value) { return Math.round(value); },
                from: function(value) { return Number(value); }
            }
        });

        // Sync slider -> input
        slider.noUiSlider.on('update', function(values, handle) {
            const value = Math.round(values[handle]);
            if (handle === 0) {
                if (minInput) minInput.value = value.toLocaleString('vi-VN');
            } else {
                if (maxInput) maxInput.value = value.toLocaleString('vi-VN');
            }
        });

        // Sync input -> slider
        if (minInput) {
            minInput.addEventListener('change', function() {
                let val = parseInt(this.value.replace(/\./g, ''));
                if (isNaN(val)) val = 0;
                slider.noUiSlider.set([val, null]);
            });
        }
        if (maxInput) {
            maxInput.addEventListener('change', function() {
                let val = parseInt(this.value.replace(/\./g, ''));
                if (isNaN(val)) val = 50000000;
                slider.noUiSlider.set([null, val]);
            });
        }

        return slider;
    }

    // Init sliders
    const desktopSlider = initPriceSlider('price-slider-desktop', 'price-min-desktop', 'price-max-desktop');
    const mobileSlider = initPriceSlider('price-slider-mobile', 'price-min-mobile', 'price-max-mobile');


    // === APPLY FILTER FUNCTION (Reload Page) ===
    function applyFilter(source) {
        // Get slider values (prefer desktop if available)
        const slider = desktopSlider || mobileSlider;
        let minPrice = 0, maxPrice = 50000000;
        
        if (slider) {
            const values = slider.noUiSlider.get();
            minPrice = Math.round(values[0]);
            maxPrice = Math.round(values[1]);
        }

        // Build URL params
        const url = new URL(window.location.href);
        const params = new URLSearchParams(url.search);
        
        params.set('min_price', minPrice);
        params.set('max_price', maxPrice);

        // Categories
        const checkedCats = Array.from(document.querySelectorAll('input[name="categories[]"]:checked'))
                                .map(cb => cb.value);
        if (checkedCats.length > 0) {
            params.set('categories', checkedCats.join(','));
        } else {
            params.delete('categories');
        }

        // Brands
        const checkedBrands = Array.from(document.querySelectorAll('input[name="brands[]"]:checked'))
                                  .map(cb => cb.value);
        if (checkedBrands.length > 0) {
            params.set('brands', checkedBrands.join(','));
        } else {
            params.delete('brands');
        }

        // Gender
        const gender = document.querySelector('input[name="gender"]:checked');
        if (gender && gender.value !== 'all') {
            params.set('gender', gender.value);
        } else {
            params.delete('gender');
        }

        // Reload
        url.search = params.toString();
        window.location.href = url.toString();
    }

    // === RESET FILTER FUNCTION ===
    function resetFilter() {
        // Reset sliders
        if (desktopSlider) desktopSlider.noUiSlider.set([0, 50000000]);
        if (mobileSlider) mobileSlider.noUiSlider.set([0, 50000000]);
        
        // Reset checkboxes & radios
        document.querySelectorAll('.filter-checkbox').forEach(cb => cb.checked = false);
        const radios = document.querySelectorAll('.filter-radio');
        if (radios[0]) radios[0].checked = true;

        // Reload clean URL
        const url = new URL(window.location.href);
        url.search = '';
        window.location.href = url.toString();
    }

    // Event listeners for Apply/Reset buttons
    const applyDesktopBtn = document.getElementById('apply-desktop-filter');
    const applyMobileBtn = document.getElementById('apply-mobile-filter');
    const resetDesktopBtn = document.getElementById('reset-desktop-filter');
    const resetMobileBtn = document.getElementById('reset-mobile-filter');

    if (applyDesktopBtn) applyDesktopBtn.addEventListener('click', () => applyFilter('desktop'));
    if (applyMobileBtn) applyMobileBtn.addEventListener('click', () => { applyFilter('mobile'); closeMobileFilter(); });
    if (resetDesktopBtn) resetDesktopBtn.addEventListener('click', resetFilter);
    if (resetMobileBtn) resetMobileBtn.addEventListener('click', () => { resetFilter(); closeMobileFilter(); });

});
</script>
</body>




  <!-- Mobile Menu (Hidden by default) -->
    <div id="main-menu"
        class="fixed inset-0 bg-white z-50 transform -translate-x-full transition duration-300 md:hidden overflow-y-auto">
        <div class="p-4">
            <!-- Header với nút đóng -->
            <div class="flex justify-between items-center mb-6">
                <img src="../img/icons/logonvb.png" height="30" width="50" class="relative-top-left transform scale-75 ">
                    <button class="close-menu p-2 hover:bg-gray-100 rounded-full transition">
                <i class="fas fa-times text-2xl text-gray-600"></i>
                </button>
            </div>



            <!-- User Actions -->
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                <a href="https://nvbplay.vn/my-account" class="flex items-center text-gray-700">
                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                        <i class="far fa-user text-xl text-gray-600"></i>
                    </div>
                    <div>
                        <div class="font-medium">Tài khoản</div>
                        <span class="text-sm text-gray-500">Đăng nhập / Đăng ký</span>
                    </div>
                </a>
            </div>

            <!-- Mobile Menu Items - Danh mục chính -->
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-2">Danh mục</h3>

                <!-- Cầu Lông -->
                <div class="mb-2">
                    <button class="w-full flex items-center justify-between p-3 bg-gray-50 rounded-lg category-toggle"
                        data-category="badminton">
                        <div class="flex items-center">
                            <div class="w-8 h-8 mr-3 flex-shrink-0">
                                <img src="https://nvbplay.vn/wp-content/uploads/2024/10/badminton-No.svg" alt="Cầu Lông"
                                    class="w-full h-full">
                            </div>
                            <span class="font-medium">Cầu Lông</span>
                        </div>
                        <i class="fas fa-chevron-down text-sm text-gray-500 transition-transform"></i>
                    </button>

                    <!-- Submenu Cầu Lông -->
                    <div class="pl-11 pr-3 mt-2 space-y-2 hidden category-submenu" id="submenu-badminton">
                        <!-- Vợt cầu lông -->
                        <div>
                            <a href="/product-category/vot-cau-long" class="block py-2 text-gray-700 font-medium">Vợt
                                cầu lông</a>
                            <div class="pl-4 mt-1 space-y-1">
                                <a href="https://nvbplay.vn/product-category/vot-cau-long?_brand=yonex"
                                    class="block py-1 text-sm text-gray-600">Vợt Yonex</a>
                                <a href="https://nvbplay.vn/product-category/vot-cau-long?_brand=adidas"
                                    class="block py-1 text-sm text-gray-600">Vợt Adidas</a>
                                <a href="https://nvbplay.vn/product-category/vot-cau-long?_brand=li-ning"
                                    class="block py-1 text-sm text-gray-600">Vợt Li-ning</a>
                                <a href="https://nvbplay.vn/product-category/vot-cau-long?_brand=toalson"
                                    class="block py-1 text-sm text-gray-600">Vợt Toalson</a>
                                <a href="https://nvbplay.vn/product-category/vot-cau-long"
                                    class="block py-1 text-sm text-red-600">Xem thêm</a>
                            </div>
                        </div>

                        <!-- Áo cầu lông -->
                        <div>
                            <a href="https://nvbplay.vn/product-category/ao-the-thao/ao-cau-long"
                                class="block py-2 text-gray-700 font-medium">Áo cầu lông</a>
                            <div class="pl-4 mt-1 space-y-1">
                                <a href="https://nvbplay.vn/product-category/ao-the-thao/ao-cau-long?_brand=yonex"
                                    class="block py-1 text-sm text-gray-600">Áo Yonex</a>
                                <a href="https://nvbplay.vn/product-category/ao-the-thao/ao-cau-long?_brand=ds"
                                    class="block py-1 text-sm text-gray-600">Áo DS</a>
                                <a href="https://nvbplay.vn/product-category/ao-the-thao/ao-cau-long?_brand=kamito"
                                    class="block py-1 text-sm text-gray-600">Áo Kamito</a>
                                <a href="https://nvbplay.vn/product-category/ao-the-thao/ao-cau-long"
                                    class="block py-1 text-sm text-red-600">Xem thêm</a>
                            </div>
                        </div>

                        <!-- Quần cầu lông -->
                        <div>
                            <a href="https://nvbplay.vn/product-category/quan-cau-long"
                                class="block py-2 text-gray-700 font-medium">Quần cầu lông</a>
                            <div class="pl-4 mt-1 space-y-1">
                                <a href="https://nvbplay.vn/product-category/quan-cau-long?_brand=yonex"
                                    class="block py-1 text-sm text-gray-600">Quần Yonex</a>
                                <a href="https://nvbplay.vn/product-category/quan-cau-long?_brand=kamito"
                                    class="block py-1 text-sm text-gray-600">Quần Kamito</a>
                                <a href="https://nvbplay.vn/product-category/quan-cau-long?_brand=adidas"
                                    class="block py-1 text-sm text-gray-600">Quần Adidas</a>
                            </div>
                        </div>

                        <!-- Túi vợt -->
                        <div>
                            <a href="https://nvbplay.vn/product-category/tui-vot-cau-long"
                                class="block py-2 text-gray-700 font-medium">Túi vợt</a>
                        </div>

                        <!-- Balo -->
                        <div>
                            <a href="https://nvbplay.vn/product-category/balo-cau-long"
                                class="block py-2 text-gray-700 font-medium">Balo</a>
                        </div>

                        <!-- Phụ kiện -->
                        <div>
                            <a href="https://nvbplay.vn/product-category/phu-kien-cau-long"
                                class="block py-2 text-gray-700 font-medium">Phụ kiện</a>
                            <div class="pl-4 mt-1 space-y-1">
                                <a href="https://nvbplay.vn/product-category/phu-kien-cau-long/cuoc-dan-vot-cau-long"
                                    class="block py-1 text-sm text-gray-600">Cước đan vợt</a>
                                <a href="https://nvbplay.vn/product-category/phu-kien-cau-long/vo-cau-long"
                                    class="block py-1 text-sm text-gray-600">Vớ cầu lông</a>
                                <a href="https://nvbplay.vn/product-category/phu-kien-cau-long/qua-cau-long"
                                    class="block py-1 text-sm text-gray-600">Quả cầu lông</a>
                                <a href="https://nvbplay.vn/product-category/phu-kien-cau-long/quan-can"
                                    class="block py-1 text-sm text-gray-600">Quấn cán</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pickleball -->
                <div class="mb-2">
                    <button class="w-full flex items-center justify-between p-3 bg-gray-50 rounded-lg category-toggle"
                        data-category="pickleball">
                        <div class="flex items-center">
                            <div class="w-8 h-8 mr-3 flex-shrink-0">
                                <img src="https://nvbplay.vn/wp-content/uploads/2024/10/pickleball-No.svg"
                                    alt="Pickleball" class="w-full h-full">
                            </div>
                            <span class="font-medium">Pickleball</span>
                        </div>
                        <i class="fas fa-chevron-down text-sm text-gray-500 transition-transform"></i>
                    </button>

                    <!-- Submenu Pickleball -->
                    <div class="pl-11 pr-3 mt-2 space-y-2 hidden category-submenu" id="submenu-pickleball">
                        <div>
                            <a href="https://nvbplay.vn/product-category/pickleball/vot-pickleball"
                                class="block py-2 text-gray-700 font-medium">Vợt Pickleball</a>
                            <div class="pl-4 mt-1 space-y-1">
                                <a href="https://nvbplay.vn/product-category/pickleball/vot-pickleball?_brand=joola"
                                    class="block py-1 text-sm text-gray-600">Vợt Joola</a>
                                <a href="https://nvbplay.vn/product-category/pickleball/vot-pickleball?_brand=selkirk"
                                    class="block py-1 text-sm text-gray-600">Vợt Selkirk</a>
                                <a href="https://nvbplay.vn/product-category/pickleball/vot-pickleball?_brand=wika"
                                    class="block py-1 text-sm text-gray-600">Vợt Wika</a>
                                <a href="https://nvbplay.vn/product-category/pickleball/vot-pickleball"
                                    class="block py-1 text-sm text-red-600">Xem thêm</a>
                            </div>
                        </div>
                        <div>
                            <a href="https://nvbplay.vn/product-category/pickleball/phu-kien-pickleball"
                                class="block py-2 text-gray-700 font-medium">Phụ kiện Pickleball</a>
                        </div>
                        <div>
                            <a href="https://nvbplay.vn/product-category/pickleball/balo-tui-pickleball"
                                class="block py-2 text-gray-700 font-medium">Balo - Túi Pickleball</a>
                        </div>
                    </div>
                </div>

                <!-- Giày -->
                <div class="mb-2">
                    <button class="w-full flex items-center justify-between p-3 bg-gray-50 rounded-lg category-toggle"
                        data-category="giay">
                        <div class="flex items-center">
                            <div class="w-8 h-8 mr-3 flex-shrink-0">
                                <img src="https://nvbplay.vn/wp-content/uploads/2024/10/jogging-No.svg" alt="Giày"
                                    class="w-full h-full">
                            </div>
                            <span class="font-medium">Giày</span>
                        </div>
                        <i class="fas fa-chevron-down text-sm text-gray-500 transition-transform"></i>
                    </button>

                    <!-- Submenu Giày -->
                    <div class="pl-11 pr-3 mt-2 space-y-2 hidden category-submenu" id="submenu-giay">
                        <div>
                            <a href="https://nvbplay.vn/product-category/giay?_brand=yonex"
                                class="block py-2 text-gray-700">Giày Yonex</a>
                        </div>
                        <div>
                            <a href="https://nvbplay.vn/product-category/giay?_brand=adidas"
                                class="block py-2 text-gray-700">Giày Adidas</a>
                        </div>
                        <div>
                            <a href="https://nvbplay.vn/product-category/giay?_brand=mizuno"
                                class="block py-2 text-gray-700">Giày Mizuno</a>
                        </div>
                        <div>
                            <a href="https://nvbplay.vn/product-category/giay?_brand=asics"
                                class="block py-2 text-gray-700">Giày Asics</a>
                        </div>
                        <div>
                            <a href="https://nvbplay.vn/product-category/giay?_brand=kamito"
                                class="block py-2 text-gray-700">Giày Kamito</a>
                        </div>
                    </div>
                </div>

                <!-- Chăm sóc sức khoẻ -->
                <a href="https://nvbplay.vn/product-category/san-pham-cham-soc-suc-khoe"
                    class="flex items-center p-3 hover:bg-gray-50 rounded-lg mb-2">
                    <div class="w-6 h-6 mr-3 flex-shrink-0">
                        <img src="https://nvbplay.vn/wp-content/uploads/2024/10/healthcare-No.svg"
                            alt="Chăm sóc sức khoẻ" class="w-full h-full">
                    </div>
                    <span class="font-medium">Chăm sóc sức khoẻ</span>
                </a>

                <!-- Dịch vụ -->
                <a href="https://nvbplay.vn/product-category/dich-vu"
                    class="flex items-center p-3 hover:bg-gray-50 rounded-lg mb-2">
                    <div class="w-6 h-6 mr-3 flex-shrink-0">
                        <img src="https://nvbplay.vn/wp-content/uploads/2024/10/customer-service-No.svg" alt="Dịch vụ"
                            class="w-full h-full">
                    </div>
                    <span class="font-medium">Dịch vụ</span>
                </a>

                <!-- Tin Tức -->
                <div class="mb-2">
                    <button class="w-full flex items-center justify-between p-3 bg-gray-50 rounded-lg category-toggle"
                        data-category="news">
                        <div class="flex items-center">
                            <div class="w-6 h-6 mr-3 flex-shrink-0">
                                <img src="https://nvbplay.vn/wp-content/uploads/2024/10/news-No.svg" alt="Tin Tức"
                                    class="w-full h-full">
                            </div>
                            <span class="font-medium">Tin Tức</span>
                        </div>
                        <i class="fas fa-chevron-down text-sm text-gray-500 transition-transform"></i>
                    </button>

                    <!-- Submenu Tin Tức -->
                    <div class="pl-11 pr-3 mt-2 space-y-2 hidden category-submenu" id="submenu-news">
                        <div>
                            <a href="https://nvbplay.vn/thong-tin" class="block py-2 text-gray-700">Thông tin</a>
                        </div>
                        <div>
                            <a href="https://nvbplay.vn/cau-long" class="block py-2 text-gray-700">Cầu lông</a>
                        </div>
                        <div>
                            <a href="https://nvbplay.vn/pickleball" class="block py-2 text-gray-700">Pickleball</a>
                        </div>
                    </div>
                </div>

                <!-- Tuyển dụng -->
                <a href="https://nvbplay.vn/tuyen-dung" class="flex items-center p-3 hover:bg-gray-50 rounded-lg mb-2">
                    <div class="w-6 h-6 mr-3 flex-shrink-0">
                        <img src="https://nvbplay.vn/wp-content/uploads/2024/10/hiring.svg" alt="Tuyển dụng"
                            class="w-full h-full">
                    </div>
                    <span class="font-medium">Tuyển dụng</span>
                </a>
            </div>

            <!-- Link phụ -->
            <div class="mt-6 pt-4 border-t border-gray-200">
                <a href="https://nvbplay.vn/khuyen-mai" class="block py-2 text-gray-600 hover:text-red-600">Khuyến
                    mãi</a>
                <a href="/blogs" class="block py-2 text-gray-600 hover:text-red-600">Blogs</a>
            </div>

            <!-- Thông tin liên hệ -->
            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center mb-2">
                    <i class="fas fa-map-marker-alt text-red-600 w-5 mr-2"></i>
                    <span class="text-sm text-gray-600">62 Lê Bình, Tân An, Cần Thơ</span>
                </div>
                <div class="flex items-center mb-2">
                    <i class="fas fa-phone-alt text-red-600 w-5 mr-2"></i>
                    <a href="tel:0987.879.243" class="text-sm text-gray-600">0987.879.243</a>
                </div>
                <div class="flex items-center">
                    <i class="far fa-clock text-red-600 w-5 mr-2"></i>
                    <span class="text-sm text-gray-600">08:00 - 21:00</span>
                </div>
            </div>
        </div>
    </div>
    <!-- JavaScript for mobile menu toggle -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menuToggle = document.querySelector('.menu-toggle');
            const closeMenu = document.querySelector('.close-menu');
            const mobileMenu = document.getElementById('main-menu');

            if (menuToggle) {
                menuToggle.addEventListener('click', function () {
                    mobileMenu.classList.remove('-translate-x-full');
                });
            }

            if (closeMenu) {
                closeMenu.addEventListener('click', function () {
                    mobileMenu.classList.add('-translate-x-full');
                });
            }

            // Category dropdown toggle
            const categoryButton = document.querySelector('.relative button');
            if (categoryButton) {
                categoryButton.addEventListener('click', function () {
                    const subMenu = this.nextElementSibling;
                    subMenu.classList.toggle('hidden');
                    this.querySelector('i').classList.toggle('fa-chevron-down');
                    this.querySelector('i').classList.toggle('fa-chevron-up');
                });
            }
        });
    </script>

    <!-- javascript for desktop menu-->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menuTrigger = document.getElementById('mega-menu-trigger');
            const menuDropdown = document.getElementById('mega-menu-dropdown');
            const menuItems = document.querySelectorAll('.icon-box-menu[data-menu]');
            const menuContents = document.querySelectorAll('.menu-content');

            // Toggle menu khi click vào nút Danh mục
            if (menuTrigger) {
                menuTrigger.addEventListener('click', function (e) {
                    e.stopPropagation();
                    menuDropdown.classList.toggle('hidden');
                });
            }

            // Xử lý click vào các item trong menu sidebar
            menuItems.forEach(item => {
                item.addEventListener('click', function (e) {
                    e.stopPropagation();

                    // Lấy id của menu cần hiển thị
                    const menuId = this.getAttribute('data-menu');

                    // Remove active class từ tất cả items
                    menuItems.forEach(el => {
                        el.classList.remove('active', 'bg-red-50');
                        const titleEl = el.querySelector('.font-bold');
                        if (titleEl) titleEl.classList.remove('text-red-600');
                    });

                    // Add active class cho item được click
                    this.classList.add('active', 'bg-red-50');
                    const activeTitle = this.querySelector('.font-bold');
                    if (activeTitle) activeTitle.classList.add('text-red-600');

                    // Ẩn tất cả content
                    menuContents.forEach(content => {
                        content.classList.add('hidden');
                    });

                    // Hiển thị content tương ứng
                    const activeContent = document.getElementById(`content-${menuId}`);
                    if (activeContent) {
                        activeContent.classList.remove('hidden');
                    }
                });
            });

            // Đóng menu khi click ra ngoài
            document.addEventListener('click', function (e) {
                if (!menuDropdown.contains(e.target) && !menuTrigger.contains(e.target)) {
                    menuDropdown.classList.add('hidden');
                }
            });

            // Ngăn chặn đóng menu khi click vào bên trong menu
            menuDropdown.addEventListener('click', function (e) {
                e.stopPropagation();
            });

            // Mobile menu toggle
            const menuToggle = document.querySelector('.menu-toggle');
            const closeMenu = document.querySelector('.close-menu');
            const mobileMenu = document.getElementById('main-menu');

            if (menuToggle) {
                menuToggle.addEventListener('click', function () {
                    mobileMenu.classList.remove('-translate-x-full');
                });
            }

            if (closeMenu) {
                closeMenu.addEventListener('click', function () {
                    mobileMenu.classList.add('-translate-x-full');
                });
            }

            // Category dropdown toggle cho mobile
            const categoryButton = document.querySelector('.relative button');
            if (categoryButton) {
                categoryButton.addEventListener('click', function () {
                    const subMenu = this.nextElementSibling;
                    subMenu.classList.toggle('hidden');
                    this.querySelector('i').classList.toggle('fa-chevron-down');
                    this.querySelector('i').classList.toggle('fa-chevron-up');
                });
            }
        });
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Mobile menu toggle
            const menuToggle = document.querySelector('.menu-toggle');
            const closeMenu = document.querySelector('.close-menu');
            const mobileMenu = document.getElementById('main-menu');

            if (menuToggle) {
                menuToggle.addEventListener('click', function () {
                    mobileMenu.classList.remove('-translate-x-full');
                    document.body.style.overflow = 'hidden'; // Prevent scrolling when menu open
                });
            }

            if (closeMenu) {
                closeMenu.addEventListener('click', function () {
                    mobileMenu.classList.add('-translate-x-full');
                    document.body.style.overflow = ''; // Restore scrolling
                });
            }

            // Category dropdown toggles
            const categoryToggles = document.querySelectorAll('.category-toggle');

            categoryToggles.forEach(toggle => {
                toggle.addEventListener('click', function () {
                    const category = this.getAttribute('data-category');
                    const submenu = document.getElementById(`submenu-${category}`);
                    const icon = this.querySelector('.fa-chevron-down');

                    // Toggle submenu
                    if (submenu) {
                        submenu.classList.toggle('hidden');

                        // Rotate icon
                        if (icon) {
                            icon.classList.toggle('rotate-180');
                        }

                        // Toggle active state
                        this.classList.toggle('bg-red-50');
                        this.classList.toggle('text-red-600');
                    }
                });
            });

            // Close menu when clicking outside (optional)
            document.addEventListener('click', function (e) {
                if (mobileMenu && !mobileMenu.contains(e.target) && !menuToggle.contains(e.target)) {
                    mobileMenu.classList.add('-translate-x-full');
                    document.body.style.overflow = '';
                }
            });
        });
    </script>
    <!-- Add padding bottom for mobile to account for bottom nav -->
    <style>
        @media (max-width: 768px) {
            body {
                padding-bottom: 70px;
            }
        }
    </style>
</body>


</html>