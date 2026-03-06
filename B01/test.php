<!DOCTYPE html>
<html lang="vi" prefix="og: https://ogp.me/ns#">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NVBPlay - Showroom Đồ Thể Thao Cầu Lông & Pickleball Chính Hãng</title>
    <meta name="description"
        content="NVBPlay chuyên cung cấp đồ cầu lông và pickleball cao cấp, từ vợt, giày, đến phụ kiện chính hãng. Nâng cao trải nghiệm của bạn tại NVBPlay.">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome for icons (optional, you can use custom SVGs) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        /* Custom styles that can't be done with Tailwind */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Hide scrollbar for categories slider */
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>

    <link rel="icon" href="https://nvbplay.vn/wp-content/uploads/2024/06/LOGO-NVB-PLAY-NEW-05-RED-100x100.png"
		sizes="32x32">
</head>

<body class="font-sans antialiased bg-gray-50">

    <!-- Popup Overlay -->
    <div id="popup_overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50"></div>

    <!-- Main Wrapper -->
    <div id="wrapper" class="min-h-screen flex flex-col">

        <!-- Header -->
        <header id="header" class="sticky top-0 z-40 bg-white shadow-sm">
            <div class="header-wrapper">

                
                <!-- Main Header -->
                <div id="masthead" class="py-2 md:py-3 border-b">
                    <div class="container mx-auto px-4 flex items-center justify-between">

                        <!-- Mobile Menu Toggle (Left) -->
                        <div class="md:hidden">
                            <button class="menu-toggle p-2">
                                <img src="./img/icons/menu.svg" class="fas fa-bars text-2xl"></i>
                            </button>
                        </div>




                        <!-- Desktop Left Menu (hidden on mobile) -->
                        <div class="hidden md:flex items-center flex-1 ml-6">
    <ul class="flex items-center space-x-4">
        <li class="relative" id="mega-menu-container">
            <!-- Mega Menu Trigger -->
            <button id="mega-menu-trigger" class="button-menu flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200 transition">
                <img src="./img/icons/menu.svg" class="w-5 h-5 mr-2" alt="menu">
                <span>Danh mục</span>
            </button>

            <!-- Mega Menu Dropdown - Ẩn/Hiện bằng JavaScript -->
            <div id="mega-menu-dropdown" class="absolute left-0 top-full mt-2 w-[900px] bg-white rounded-lg shadow-xl hidden z-50">
                <div class="flex p-4">
                    <!-- Left Sidebar - Icon Menu -->
                    <div class="w-64 border-r border-gray-200 pr-4">
                        <!-- Cầu Lông - Active -->
                        <div class="icon-box-menu active bg-red-50 rounded-lg p-3 mb-1 cursor-pointer hover:bg-red-50 transition flex items-start" data-menu="badminton">
                            <div class="w-8 h-8 flex-shrink-0 mr-3">
                                <img src="https://nvbplay.vn/wp-content/uploads/2024/10/badminton-No.svg" alt="Cầu Lông" class="w-full h-full">
                            </div>
                            <div>
                                <p class="font-bold text-red-600">Cầu Lông</p>
                                <p class="text-xs text-gray-500">Trang bị cầu lông chuyên nghiệp</p>
                            </div>
                        </div>

                        <!-- Pickleball -->
                        <div class="icon-box-menu p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start" data-menu="pickleball">
                            <div class="w-8 h-8 flex-shrink-0 mr-3">
                                <img src="https://nvbplay.vn/wp-content/uploads/2024/10/pickleball-No.svg" alt="Pickleball" class="w-full h-full">
                            </div>
                            <div>
                                <p class="font-bold">Pickleball</p>
                                <p class="text-xs text-gray-500">Trang bị pickleball hàng đầu</p>
                            </div>
                        </div>

                        <!-- Giày -->
                        <div class="icon-box-menu p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start" data-menu="giay">
                            <div class="w-8 h-8 flex-shrink-0 mr-3">
                                <img src="https://nvbplay.vn/wp-content/uploads/2024/10/jogging-No.svg" alt="Giày" class="w-full h-full">
                            </div>
                            <div>
                                <p class="font-bold">Giày</p>
                                <p class="text-xs text-gray-500">Giày thể thao tối ưu hoá vận động</p>
                            </div>
                        </div>

                        <!-- Chăm sóc sức khoẻ -->
                        <a href="https://nvbplay.vn/product-category/san-pham-cham-soc-suc-khoe" class="block p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start">
                            <div class="w-6 h-6 flex-shrink-0 mr-3">
                                <img src="https://nvbplay.vn/wp-content/uploads/2024/10/healthcare-No.svg" alt="Chăm sóc sức khoẻ" class="w-full h-full">
                            </div>
                            <div>
                                <p class="font-bold">Chăm sóc sức khoẻ</p>
                            </div>
                        </a>

                        <!-- Dịch vụ -->
                        <a href="https://nvbplay.vn/product-category/dich-vu" class="block p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start">
                            <div class="w-6 h-6 flex-shrink-0 mr-3">
                                <img src="https://nvbplay.vn/wp-content/uploads/2024/10/customer-service-No.svg" alt="Dịch vụ" class="w-full h-full">
                            </div>
                            <div>
                                <p class="font-bold">Dịch vụ</p>
                            </div>
                        </a>

                        <!-- Tin Tức -->
                        <div class="icon-box-menu p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start" data-menu="news">
                            <div class="w-6 h-6 flex-shrink-0 mr-3">
                                <img src="https://nvbplay.vn/wp-content/uploads/2024/10/news-No.svg" alt="Tin Tức" class="w-full h-full">
                            </div>
                            <div>
                                <p class="font-bold">Tin Tức</p>
                                <p class="text-xs text-gray-500">Xu hướng mới, sự kiện hot, giảm giá sốc!</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right Content - Mega Menu Inner -->
                    <div class="flex-1 pl-4">
                        <!-- Content for Cầu Lông (default active) -->
                        <div id="content-badminton" class="menu-content">
                            <!-- Thương hiệu nổi bật -->
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-bold">Thương hiệu nổi bật</h3>
                                    <a href="/shop" class="text-sm text-red-600 hover:text-red-700 flex items-center">
                                        Xem tất cả <i class="fas fa-chevron-right ml-1 text-xs"></i>
                                    </a>
                                </div>
                                <div class="grid grid-cols-4 gap-2">
                                    <!-- Yonex -->
                                    <a href="https://nvbplay.vn/shop?_brand=yonex" class="flex flex-col items-center text-center group">
                                        <div class="w-12 h-12 mb-1">
                                            <img src="https://nvbplay.vn/wp-content/uploads/2024/10/logo-300x214-1-150x150.webp" alt="Yonex" class="w-full h-full object-contain">
                                        </div>
                                        <span class="text-xs">YONEX</span>
                                    </a>
                                    <!-- Adidas -->
                                    <a href="https://nvbplay.vn/shop?_brand=adidas" class="flex flex-col items-center text-center group">
                                        <div class="w-12 h-12 mb-1">
                                            <img src="https://nvbplay.vn/wp-content/uploads/2024/10/ave6by86s-300x300-1-150x150.webp" alt="Adidas" class="w-full h-full object-contain">
                                        </div>
                                        <span class="text-xs">ADIDAS</span>
                                    </a>
                                    <!-- Li-Ning -->
                                    <a href="https://nvbplay.vn/shop?_brand=li-ning" class="flex flex-col items-center text-center group">
                                        <div class="w-12 h-12 mb-1">
                                            <img src="https://nvbplay.vn/wp-content/uploads/2024/10/Logo-li-ning-300x173-1-150x150.webp" alt="Li-Ning" class="w-full h-full object-contain">
                                        </div>
                                        <span class="text-xs">LI-NING</span>
                                    </a>
                                    <!-- DS -->
                                    <a href="https://nvbplay.vn/shop?_brand=ds" class="flex flex-col items-center text-center group">
                                        <div class="w-12 h-12 mb-1">
                                            <img src="https://nvbplay.vn/wp-content/uploads/2024/10/logo-ds-300x300-1-150x150.jpg" alt="DS" class="w-full h-full object-contain">
                                        </div>
                                        <span class="text-xs">DS</span>
                                    </a>
                                    <!-- REDSON -->
                                    <a href="https://nvbplay.vn/shop?_brand=REDSON" class="flex flex-col items-center text-center group">
                                        <div class="w-12 h-12 mb-1">
                                            <img src="https://nvbplay.vn/wp-content/uploads/2024/10/d464ecc6daed33689bda2bf9a0e93f5e-150x150-1.png" alt="REDSON" class="w-full h-full object-contain">
                                        </div>
                                        <span class="text-xs">REDSON</span>
                                    </a>
                                    <!-- KAMITO -->
                                    <a href="https://nvbplay.vn/shop?_brand=kamito" class="flex flex-col items-center text-center group">
                                        <div class="w-12 h-12 mb-1">
                                            <img src="https://nvbplay.vn/wp-content/uploads/2024/10/logo-kamito-1-300x150-1-150x150.png" alt="KAMITO" class="w-full h-full object-contain">
                                        </div>
                                        <span class="text-xs">KAMITO</span>
                                    </a>
                                    <!-- TOALSON -->
                                    <a href="https://nvbplay.vn/shop?_brand=toalson" class="flex flex-col items-center text-center group">
                                        <div class="w-12 h-12 mb-1">
                                            <img src="https://nvbplay.vn/wp-content/uploads/2024/10/Toalson-300x150-1-150x150.png" alt="TOALSON" class="w-full h-full object-contain">
                                        </div>
                                        <span class="text-xs">TOALSON</span>
                                    </a>
                                    <!-- YD SPORT -->
                                    <a href="https://nvbplay.vn/shop?_brand=yd-sport" class="flex flex-col items-center text-center group">
                                        <div class="w-12 h-12 mb-1">
                                            <img src="https://nvbplay.vn/wp-content/uploads/2024/10/YD-Sport-logo-300x61-1-150x61.png" alt="YD SPORT" class="w-full h-full object-contain">
                                        </div>
                                        <span class="text-xs">YD SPORT</span>
                                    </a>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 my-3"></div>

                            <!-- Theo sản phẩm -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-bold">Theo sản phẩm</h3>
                                    <a href="/shop" class="text-sm text-red-600 hover:text-red-700 flex items-center">
                                        Xem tất cả <i class="fas fa-chevron-right ml-1 text-xs"></i>
                                    </a>
                                </div>
                                
                                <div class="grid grid-cols-3 gap-4">
                                    <!-- Vợt cầu lông -->
                                    <div>
                                        <a href="/product-category/vot-cau-long" class="font-semibold text-sm hover:text-red-600">Vợt cầu lông</a>
                                        <ul class="mt-2 space-y-1">
                                            <li><a href="https://nvbplay.vn/product-category/vot-cau-long?_brand=yonex" class="text-xs text-gray-600 hover:text-red-600">Vợt cầu lông Yonex</a></li>
                                            <li><a href="https://nvbplay.vn/product-category/vot-cau-long?_brand=adidas" class="text-xs text-gray-600 hover:text-red-600">Vợt cầu lông Adidas</a></li>
                                            <li><a href="https://nvbplay.vn/product-category/vot-cau-long?_brand=li-ning" class="text-xs text-gray-600 hover:text-red-600">Vợt cầu lông Li-ning</a></li>
                                            <li><a href="https://nvbplay.vn/product-category/vot-cau-long?_brand=toalson" class="text-xs text-gray-600 hover:text-red-600">Vợt cầu lông Toalson</a></li>
                                            <li><a href="/product-category/vot-cau-long" class="text-xs text-red-600 hover:text-red-700">Xem thêm <i class="fas fa-chevron-right ml-1 text-xs"></i></a></li>
                                        </ul>
                                    </div>

                                    <!-- Áo cầu lông -->
                                    <div>
                                        <a href="https://nvbplay.vn/product-category/ao-the-thao/ao-cau-long" class="font-semibold text-sm hover:text-red-600">Áo cầu lông</a>
                                        <ul class="mt-2 space-y-1">
                                            <li><a href="https://nvbplay.vn/product-category/ao-the-thao/ao-cau-long?_brand=yonex" class="text-xs text-gray-600 hover:text-red-600">Áo cầu lông Yonex</a></li>
                                            <li><a href="https://nvbplay.vn/product-category/ao-the-thao/ao-cau-long?_brand=ds" class="text-xs text-gray-600 hover:text-red-600">Áo cầu lông DS</a></li>
                                            <li><a href="https://nvbplay.vn/product-category/ao-the-thao/ao-cau-long" class="text-xs text-red-600 hover:text-red-700">Xem thêm <i class="fas fa-chevron-right ml-1 text-xs"></i></a></li>
                                        </ul>
                                    </div>

                                    <!-- Quần cầu lông -->
                                    <div>
                                        <a href="https://nvbplay.vn/product-category/quan-cau-long" class="font-semibold text-sm hover:text-red-600">Quần cầu lông</a>
                                        <ul class="mt-2 space-y-1">
                                            <li><a href="https://nvbplay.vn/product-category/quan-cau-long?_brand=yonex" class="text-xs text-gray-600 hover:text-red-600">Quần cầu lông Yonex</a></li>
                                            <li><a href="https://nvbplay.vn/product-category/quan-cau-long?_brand=kamito" class="text-xs text-gray-600 hover:text-red-600">Quần cầu lông Kamito</a></li>
                                            <li><a href="https://nvbplay.vn/product-category/quan-cau-long" class="text-xs text-red-600 hover:text-red-700">Xem thêm <i class="fas fa-chevron-right ml-1 text-xs"></i></a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Content for Pickleball (hidden by default) -->
                        <div id="content-pickleball" class="menu-content hidden">
                            <div class="text-center py-10 text-gray-500">
                                <p>Nội dung Pickleball sẽ hiển thị ở đây</p>
                                <!-- Thêm nội dung Pickleball tương tự -->
                            </div>
                        </div>

                        <!-- Content for Giày (hidden by default) -->
                        <div id="content-giay" class="menu-content hidden">
                            <div class="text-center py-10 text-gray-500">
                                <p>Nội dung Giày sẽ hiển thị ở đây</p>
                                <!-- Thêm nội dung Giày tương tự -->
                            </div>
                        </div>

                        <!-- Content for Tin Tức (hidden by default) -->
                        <div id="content-news" class="menu-content hidden">
                            <div class="text-center py-10 text-gray-500">
                                <p>Nội dung Tin Tức sẽ hiển thị ở đây</p>
                                <!-- Thêm nội dung Tin Tức tương tự -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </li>
        
        <li>
            <a href="https://nvbplay.vn/shop" class="flex items-center text-gray-700 hover:text-red-600 font-medium">
                <img src="./img/icons/shop.svg" class="w-5 h-5 flex-shrink-0 mr-2">
                <span>CỬA HÀNG</span>
            </a>
        </li>
    </ul>
</div>

                        <!-- Logo (Center on mobile, left on desktop) -->
                        <div id="logo" class="flex-shrink-1 absolute left-1/2 transform -translate-x-1/2">
                            <a href="https://nvbplay.vn/" title="NVBPlay" rel="home">
                                <img width="100" height="40"
                                    src="https://nvbplay.vn/wp-content/uploads/2024/08/LOGO-NVB-PLAY-NEW-04-RED.png"
                                    alt="NVBPlay" class="h-12 md:h-14 w-auto transform scale-75">
                            </a>
                        </div>

                        <!-- Desktop Right Elements -->
                        <div class="hidden md:flex items-center space-x-4">
                            <!-- Address Book -->
                            <div class="address-book">
                                <a href="/my-account/address-book?back=true"
                                    class="flex items-center text-gray-700 hover:text-red-600">
                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                    <span class="shipping-address text-sm">
                                        <span class="text">Chọn địa chỉ</span>
                                    </span>
                                </a>
                            </div>

                            <div class="h-5 w-px bg-gray-300"></div>

                            <!-- Search -->
                            <div class="search-header relative">
                                <button class="search-toggle p-2">
                                    <i class="fas fa-search text-gray-700 hover:text-red-600"></i>
                                </button>
                            </div>

                            <!-- Account -->
                            <a href="https://nvbplay.vn/my-account" class="p-2">
                                <i class="far fa-user text-gray-700 hover:text-red-600 text-xl"></i>
                            </a>

                            <!-- Cart -->
                            <a href="https://nvbplay.vn/cart" class="relative p-2">
                                <i class="fas fa-shopping-basket text-gray-700 hover:text-red-600 text-xl"></i>
                                <span
                                    class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">0</span>
                            </a>
                        </div>

                        <!-- Mobile Right Elements -->
                        <div class="md:hidden flex items-center space-x-3">
                            <button class="search-toggle p-1">
                                <i class="fas fa-search text-xl"></i>
                            </button>
                            <a href="https://nvbplay.vn/cart" class="relative p-1">
                                <i class="fas fa-shopping-basket text-xl"></i>
                                <span
                                    class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">0</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Bottom Header / Wide Nav -->
                <div id="wide-nav" class="bg-gray-900 text-white py-2">
                    <div class="container mx-auto px-4 text-center">
                        <div class="top-hot">
                            <a href="https://nvbplay.vn/product-tag/control-collection"
                                class="text-white hover:text-yellow-300 transition text-sm md:text-base">
                                ⚡ VỢT YONEX NANOFLARE 1000 GAME - RESTOCKED ⚡
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Hidden H1 for SEO -->
        <h1 class="hidden">NVBPlay chuyên cung cấp đồ cầu lông và pickleball cao cấp, từ vợt, giày, đến phụ kiện chính
            hãng. Nâng cao trải nghiệm của bạn tại NVBPlay.</h1>

        <!-- Main Content -->
        <main id="main" class="flex-grow">
            <div id="content" class="content-area">
                <div class="container mx-auto px-4 py-6 md:py-8">

                    <!-- Hero Banner Section -->
                    <section class="m-20 banner-hero mb-6 md:mb-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 transfrom scale-80">
                            <!-- Main Banner -->
                            <div class="md:col-span-2 rounded-lg overflow-hidden shadow-md ">
                                <a href="https://nvbplay.vn/product-tag/san-deal-sieu-hoi-giam-gia-len-den-45">
                                    <img src="https://nvbplay.vn/wp-content/uploads/2026/02/MUA-DEAL-RON-RANG-scaled.png"
                                        alt="MUA DEAL RON RANG" class="w-full h-auto object-cover ">
                                </a>
                            </div>

                            <!-- Sub Banners -->
                            <div class="rounded-lg overflow-hidden shadow-md ">
                                <a href="https://nvbplay.vn/ctkm-t3-thay-do-tha-ga-khong-lo-ve-gia.html">
                                    <img src="https://nvbplay.vn/wp-content/uploads/2026/02/ThayDoThaGa-KhongLoVeGia-2x1-1.png"
                                        alt="ThayDoThaGa" class="w-full h-auto object-cover">
                                </a>
                            </div>

                            <div class="rounded-lg overflow-hidden shadow-md">
                                <a href="https://nvbplay.vn/ron-rang-xuan-sang-san-deal-hoanh-trang.html">
                                    <img src="https://nvbplay.vn/wp-content/uploads/2026/02/ron-rang-xuan-sang-san-deal-hoanh-trang-1200x600-1-1.png"
                                        alt="Ron rang xuan sang" class="w-full h-auto object-cover">
                                </a>
                            </div>
                        </div>
                    </section>

                    <!-- Categories Section -->
                    <section class="home-product mb-8">
                        <div class="mb-4">
                            <h2
                                class="text-xl md:text-2xl font-bold relative inline-block after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-12 after:h-1 after:bg-red-600 pb-2">
                                Danh mục</h2>
                        </div>

                            <!-- Categories Slider (horizontal scroll on mobile, grid on desktop) -->
                            <div class="categories-slider overflow-x-auto scrollbar-hide">
                                <div class="flex md:grid md:grid-cols-4 lg:grid-cols-4 gap-4 pb-4 justify-items-center ">
                                    <!-- Category Item 1 -->
                                    <div class="flex-shrink-0 w-36 md:w-auto">
                                        <a href="https://nvbplay.vn/product-category/vot-cau-long" class="block group">
                                            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                                                <div class="aspect-square overflow-hidden p-1">
                                                    <img src="https://nvbplay.vn/wp-content/uploads/2024/12/vot-yonex-nanoflare-nextage-dark-gray-4u5z-4.webp"
                                                        alt="Vợt cầu lông"
                                                        class="w-full h-full rounded-lg object-cover group-hover:scale-105 transition duration-300 ">
                                                </div>
                                                <div class="p-3 text-center">
                                                    <h3 class="font-medium text-sm">Vợt cầu lông</h3>
                                                </div>
                                            </div>
                                        </a>
                                    </div>

                                    <!-- Category Item 2 -->
                                    <div class="flex-shrink-0 w-36 md:w-auto ">
                                        <a href="https://nvbplay.vn/product-category/pickleball/vot-pickleball"
                                            class="block group">
                                            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                                                <div class="aspect-square overflow-hidden  p-1">
                                                    <img src="https://nvbplay.vn/wp-content/uploads/2025/01/image-2.png"
                                                        alt="Vợt Pickleball"
                                                        class="w-full h-full object-cover rounded-lg group-hover:scale-105 transition duration-300">
                                                </div>
                                                <div class="p-3 text-center">
                                                    <h3 class="font-medium text-sm">Vợt Pickleball</h3>
                                                </div>
                                            </div>
                                        </a>
                                    </div>

                                    <!-- Category Item 3 -->
                                    <div class="flex-shrink-0 w-36 md:w-auto">
                                        <a href="https://nvbplay.vn/product-category/phu-kien-cau-long" class="block group">
                                            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                                                <div class="aspect-square  overflow-hidden p-1">
                                                    <img src="https://nvbplay.vn/wp-content/uploads/2025/01/PK-cau-long.png"
                                                        alt="Phụ kiện cầu lông"
                                                        class="w-full h-full object-cover rounded-lg group-hover:scale-105 transition duration-300">
                                                </div>
                                                <div class="p-3 text-center">
                                                    <h3 class="font-medium text-sm">Phụ kiện cầu lông</h3>
                                                </div>
                                            </div>
                                        </a>
                                    </div>

                                    <!-- Category Item 4 -->
                                    <div class="flex-shrink-0 w-36 md:w-auto">
                                        <a href="https://nvbplay.vn/product-category/tui-vot-cau-long" class="block group">
                                            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                                                <div class="aspect-square overflow-hidden p-1">
                                                    <img src="https://nvbplay.vn/wp-content/uploads/2025/01/Tui-vot-cau-long.png"
                                                        alt="Túi vợt cầu lông"
                                                        class="w-full h-full object-cover rounded-lg group-hover:scale-105 transition duration-300">
                                                </div>
                                                <div class="p-3 text-center">
                                                    <h3 class="font-medium text-sm">Túi vợt cầu lông</h3>
                                                </div>
                                            </div>
                                        </a>
                                    </div>

                                    <!-- Add more categories as needed -->
                                </div>
                            </div>
                    </section>

                    <!-- Featured Products Section - Áo NVBPLAY -->
                    <section class="home-product-recommended mb-8">
                        <div class="flex items-center justify-between mb-4">
                            <h2
                                class="text-xl md:text-2xl font-bold relative inline-block after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-12 after:h-1 after:bg-red-600 pb-2">
                                ÁO NVBPLAY</h2>
                            <a href="https://nvbplay.vn/shop"
                                class="text-red-600 hover:text-red-700 font-medium flex items-center">
                                Xem tất cả <i class="fas fa-chevron-right ml-1 text-sm"></i>
                            </a>
                        </div>

                        <!-- Products Grid -->
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                            <!-- Product Card 1 -->
                            <div class="bg-white rounded-lg shadow-md overflow-hidden group">
                                <div class="relative">
                                    <div class="absolute top-2 left-2 z-10">
                                        <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full">Hàng mới
                                            về</span>
                                    </div>
                                    <a href="https://nvbplay.vn/product/ao-the-thao-nvbplay-smash"
                                        class="block aspect-square overflow-hidden">
                                        <img src="https://nvbplay.vn/wp-content/uploads/2026/02/AO-THE-THAO-NVBPLAY-SMASH-768x768.jpg"
                                            alt="Áo thể thao NVBPlay Smash"
                                            class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                    </a>
                                </div>
                                <div class="p-3">
                                    <h3 class="font-medium text-sm mb-2 line-clamp-2 h-10">
                                        <a href="https://nvbplay.vn/product/ao-the-thao-nvbplay-smash"
                                            class="hover:text-red-600">Áo thể thao NVBPlay Smash</a>
                                    </h3>
                                    <div class="text-red-600 font-bold">168.000₫</div>
                                </div>
                            </div>

                            <!-- Product Card 2 -->
                            <div class="bg-white rounded-lg shadow-md overflow-hidden group">
                                <div class="relative">
                                    <div class="absolute top-2 left-2 z-10">
                                        <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full">Hàng mới
                                            về</span>
                                    </div>
                                    <a href="https://nvbplay.vn/product/ao-the-thao-nvbplay-drive"
                                        class="block aspect-square overflow-hidden">
                                        <img src="https://nvbplay.vn/wp-content/uploads/2026/02/AO-THE-THAO-NVBPLAY-DRIVE-768x768.jpg"
                                            alt="Áo thể thao NVBPlay Drive"
                                            class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                    </a>
                                </div>
                                <div class="p-3">
                                    <h3 class="font-medium text-sm mb-2 line-clamp-2 h-10">
                                        <a href="https://nvbplay.vn/product/ao-the-thao-nvbplay-drive"
                                            class="hover:text-red-600">Áo thể thao NVBPlay Drive</a>
                                    </h3>
                                    <div class="text-red-600 font-bold">168.000₫</div>
                                </div>
                            </div>

                            <!-- Product Card 3 -->
                            <div class="bg-white rounded-lg shadow-md overflow-hidden group">
                                <div class="relative">
                                    <div class="absolute top-2 left-2 z-10">
                                        <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full">Hàng mới
                                            về</span>
                                    </div>
                                    <a href="https://nvbplay.vn/product/ao-the-thao-nvbplay-clear"
                                        class="block aspect-square overflow-hidden">
                                        <img src="https://nvbplay.vn/wp-content/uploads/2026/02/AO-THE-THAO-NVBPLAY-CLEAR-768x768.jpg"
                                            alt="Áo thể thao NVBPlay Clear"
                                            class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                    </a>
                                </div>
                                <div class="p-3">
                                    <h3 class="font-medium text-sm mb-2 line-clamp-2 h-10">
                                        <a href="https://nvbplay.vn/product/ao-the-thao-nvbplay-clear"
                                            class="hover:text-red-600">Áo thể thao NVBPlay Clear</a>
                                    </h3>
                                    <div class="text-red-600 font-bold">168.000₫</div>
                                </div>
                            </div>

                            <!-- Product Card 4 -->
                            <div class="bg-white rounded-lg shadow-md overflow-hidden group">
                                <div class="relative">
                                    <div class="absolute top-2 left-2 z-10">
                                        <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full">Hàng mới
                                            về</span>
                                    </div>
                                    <a href="https://nvbplay.vn/product/ao-the-thao-nvbplay-drop"
                                        class="block aspect-square overflow-hidden">
                                        <img src="https://nvbplay.vn/wp-content/uploads/2026/02/AO-THE-THAO-NVBPLAY-DROP-768x768.jpg"
                                            alt="Áo thể thao NVBPlay Drop"
                                            class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                    </a>
                                </div>
                                <div class="p-3">
                                    <h3 class="font-medium text-sm mb-2 line-clamp-2 h-10">
                                        <a href="https://nvbplay.vn/product/ao-the-thao-nvbplay-drop"
                                            class="hover:text-red-600">Áo thể thao NVBPlay Drop</a>
                                    </h3>
                                    <div class="text-red-600 font-bold">168.000₫</div>
                                </div>
                            </div>

                            <!-- Product Card 5 -->
                            <div class="bg-white rounded-lg shadow-md overflow-hidden group">
                                <div class="relative">
                                    <a href="https://nvbplay.vn/product/ao-the-thao-nvbplay-aura"
                                        class="block aspect-square overflow-hidden">
                                        <img src="https://nvbplay.vn/wp-content/uploads/2025/10/nvbplay-aura-3-768x768.jpg"
                                            alt="Áo thể thao NVBPlay Aura"
                                            class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                    </a>
                                </div>
                                <div class="p-3">
                                    <h3 class="font-medium text-sm mb-2 line-clamp-2 h-10">
                                        <a href="https://nvbplay.vn/product/ao-the-thao-nvbplay-aura"
                                            class="hover:text-red-600">Áo thể thao NVBPlay Aura</a>
                                    </h3>
                                    <div class="text-red-600 font-bold">168.000₫</div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Banner Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                        <div class="rounded-lg overflow-hidden shadow-md">
                            <a href="https://nvbplay.vn/7-doi-giay-cau-long-sieu-pham-khong-the-bo-qua.html">
                                <img src="https://nvbplay.vn/wp-content/uploads/2025/01/7-doi-giay-cau-long-sieu-pham-khong-the-bo-qua-1-1024x512.jpg"
                                    alt="7 đôi giày cầu lông siêu phẩm" class="w-full h-auto object-cover">
                            </a>
                        </div>
                        <div class="rounded-lg overflow-hidden shadow-md">
                            <a href="https://nvbplay.vn/kham-pha-vot-cau-long-chuyen-nghiep.html">
                                <img src="https://nvbplay.vn/wp-content/uploads/2025/01/kham-pha-vot-cau-long-chuyen-nghiep-1-1024x512.jpg"
                                    alt="Khám phá vợt cầu lông chuyên nghiệp" class="w-full h-auto object-cover">
                            </a>
                        </div>
                    </div>

                    <!-- Services Section -->
                    <section class="mb-8">
                        <div class="mb-4">
                            <h2
                                class="text-xl md:text-2xl font-bold relative inline-block after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-12 after:h-1 after:bg-red-600 pb-2">
                                Dịch vụ</h2>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <!-- Service 1 -->
                            <div class="rounded-lg overflow-hidden shadow-md">
                                <a href="https://nvbplay.vn/product/dich-vu-in-ten-thuong-hieu-logo-len-vot">
                                    <img src="https://nvbplay.vn/wp-content/uploads/2025/02/dich-vu-in-ten-logo-len-vot-scaled.jpg"
                                        alt="In tên lên vợt" class="w-full h-auto object-cover">
                                </a>
                            </div>

                            <!-- Service 2 -->
                            <div class="rounded-lg overflow-hidden shadow-md">
                                <a href="https://nvbplay.vn/product/dich-vu-thay-gen-bo">
                                    <img src="https://nvbplay.vn/wp-content/uploads/2024/11/dich-vu-thay-gen.jpg"
                                        alt="Thay gen vợt" class="w-full h-auto object-cover">
                                </a>
                            </div>

                            <!-- Service 3 -->
                            <div class="rounded-lg overflow-hidden shadow-md">
                                <a href="https://nvbplay.vn/product/dich-vu-dan-luoi-vot">
                                    <img src="https://nvbplay.vn/wp-content/uploads/2024/11/dich-vu-dan-luoi-vot.jpg"
                                        alt="Đan lưới vợt" class="w-full h-auto object-cover">
                                </a>
                            </div>

                            <!-- Service 4 -->
                            <div class="rounded-lg overflow-hidden shadow-md">
                                <a href="https://nvbplay.vn/product/dich-vu-ca-nhan-hoa-in-ten-len-ao">
                                    <img src="https://nvbplay.vn/wp-content/uploads/2025/02/dich-vu-in-ten-ao.jpg"
                                        alt="In tên áo" class="w-full h-auto object-cover">
                                </a>
                            </div>
                        </div>
                    </section>

                    <!-- Sale Products Section -->
                    <section class="mb-8">
                        <div class="flex items-center justify-between mb-4">
                            <h2
                                class="text-xl md:text-2xl font-bold relative inline-block after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-12 after:h-1 after:bg-red-600 pb-2">
                                GIẢM GIÁ LÊN ĐẾN 45%</h2>
                            <a href="https://nvbplay.vn/product-tag/top-sale"
                                class="text-red-600 hover:text-red-700 font-medium flex items-center">
                                Xem tất cả <i class="fas fa-chevron-right ml-1 text-sm"></i>
                            </a>
                        </div>

                        <!-- Sale Products Grid -->
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                            <!-- Sale Product 1 -->
                            <div class="bg-white rounded-lg shadow-md overflow-hidden group">
                                <div class="relative">
                                    <div class="absolute top-2 left-2 z-10">
                                        <span class="bg-red-600 text-white text-xs px-2 py-1 rounded-full">-50%</span>
                                    </div>
                                    <a href="https://nvbplay.vn/product/ao-the-thao-nvbplay-boost-your-power"
                                        class="block aspect-square overflow-hidden">
                                        <img src="https://nvbplay.vn/wp-content/uploads/2025/01/ao-the-thao-nvbplay-boost-your-power-768x768.jpg"
                                            alt="Áo thể thao NVBPlay Boost Your Power"
                                            class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                    </a>
                                </div>
                                <div class="p-3">
                                    <h3 class="font-medium text-sm mb-2 line-clamp-2 h-10">
                                        <a href="https://nvbplay.vn/product/ao-the-thao-nvbplay-boost-your-power"
                                            class="hover:text-red-600">Áo thể thao NVBPlay Boost Your Power</a>
                                    </h3>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-red-600 font-bold">99.000₫</span>
                                        <span class="text-gray-400 text-sm line-through">199.000₫</span>
                                    </div>
                                </div>
                            </div>

                            <!-- More sale products would go here -->
                        </div>
                    </section>

                    <!-- New Arrivals Section -->
                    <section class="mb-8">
                        <div class="flex items-center justify-between mb-4">
                            <h2
                                class="text-xl md:text-2xl font-bold relative inline-block after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-12 after:h-1 after:bg-red-600 pb-2">
                                Hàng mới đổ bộ</h2>
                            <a href="https://nvbplay.vn/shop"
                                class="text-red-600 hover:text-red-700 font-medium flex items-center">
                                Xem tất cả <i class="fas fa-chevron-right ml-1 text-sm"></i>
                            </a>
                        </div>

                        <!-- New Arrivals Grid -->
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                            <!-- New Product 1 -->
                            <div class="bg-white rounded-lg shadow-md overflow-hidden group">
                                <div class="relative">
                                    <div class="absolute top-2 left-2 z-10 flex flex-col space-y-1">
                                        <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full">Hàng mới
                                            về</span>
                                        <span class="bg-red-600 text-white text-xs px-2 py-1 rounded-full">-10%</span>
                                    </div>
                                    <a href="https://nvbplay.vn/product/sypik-triton-5-pro-ultimate-tim"
                                        class="block aspect-square overflow-hidden">
                                        <img src="https://nvbplay.vn/wp-content/uploads/2026/03/vot-pickleball-sypik-triton-5-pro-ultimate-3-768x768.jpg"
                                            alt="Vợt Pickleball Sypik Triton"
                                            class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                    </a>
                                </div>
                                <div class="p-3">
                                    <h3 class="font-medium text-sm mb-2 line-clamp-2 h-10">
                                        <a href="https://nvbplay.vn/product/sypik-triton-5-pro-ultimate-tim"
                                            class="hover:text-red-600">Vợt Pickleball Sypik Triton 5 Pro Ultimate –
                                            Tím</a>
                                    </h3>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-red-600 font-bold">3.590.000₫</span>
                                        <span class="text-gray-400 text-sm line-through">3.990.000₫</span>
                                    </div>
                                </div>
                            </div>

                            <!-- More new products would go here -->
                        </div>
                    </section>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer id="footer" class="bg-black text-white">
            <div class="container mx-auto px-4 py-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Column 1: Brand -->
                    <div class="pl-5">
                        <h3 class="text-4xl font-bold mb-4">Boost<br>your power</h3>
                        <div class="flex space-x-3 mb-4">
                            <a href="https://www.facebook.com/nvbplay" target="_blank"
                                class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://www.tiktok.com/@nvbplay.vn" target="_blank"
                                class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition">
                                <i class="fab fa-tiktok"></i>
                            </a>
                            <a href="https://s.shopee.vn/6AV9qQcpMz" target="_blank"
                                class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition">
                                <i class="fas fa-shopping-bag"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Column 2: Policies -->
                    <div>
                        <h3 class="text-xl font-bold mb-4">Thông tin khác</h3>
                        <ul class="space-y-2">
                            <li><a href="" class="text-gray-400 hover:text-white transition">CHÍNH
                                    SÁCH BẢO MẬT</a></li>
                            <li><a href="" class="text-gray-400 hover:text-white transition">CHÍNH
                                    SÁCH THANH TOÁN</a></li>
                            <li><a href="a"
                                    class="text-gray-400 hover:text-white transition">CHÍNH SÁCH BẢO HÀNH ĐỔI TRẢ</a>
                            </li>
                            <li><a href="" class="text-gray-400 hover:text-white transition">CHÍNH
                                    SÁCH VẬN CHUYỂN</a></li>
                            <li><a href="" class="text-gray-400 hover:text-white transition">THOẢ
                                    THUẬN SỬ DỤNG</a></li>
                        </ul>
                    </div>

                    <!-- Column 3: Contact -->
                    <div>
                        <h3 class="text-xl font-bold mb-4">Về chúng tôi</h3>
                        <ul class="space-y-3">
                            <li>
                                <a href="https://maps.app.goo.gl/mwqaes9hQJks8FSu5" target="_blank" class="flex">
                                    <span class="font-medium w-20">Địa chỉ:</span>
                                    <span class="text-gray-400">62 Lê Bình, Tân An, Cần Thơ</span>
                                </a>
                            </li>
                            <li>
                                <div class="flex">
                                    <span class="font-medium w-20">Giờ làm việc:</span>
                                    <span class="text-gray-400">08:00 - 21:00</span>
                                </div>
                            </li>
                            <li>
                                <a href="tel:0987.879.243" class="flex">
                                    <span class="font-medium w-20">Hotline:</span>
                                    <span class="text-gray-400">0987.879.243</span>
                                </a>
                            </li>
                            <li>
                                <a href="mailto:info@nvbplay.vn" class="flex">
                                    <span class="font-medium w-20">Email:</span>
                                    <span class="text-gray-400">info@nvbplay.vn</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Divider -->
                <div class="border-t border-gray-800 my-6"></div>

                <!-- Bottom Footer -->
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-gray-500 text-sm mb-4 md:mb-0">
                        <p>©2025 CÔNG TY CỔ PHẦN NVB PLAY</p>
                        <p>GPĐKKD số 1801779686 do Sở KHĐT TP. Cần Thơ cấp ngày 22 tháng 01 năm 2025</p>
                    </div>
                    <a href="http://online.gov.vn/Home/WebDetails/129261" target="_blank">
                        <img src="https://nvbplay.vn/wp-content/uploads/2024/09/Logo-Bo-Cong-Thuong-Xanh.png"
                            alt="Bộ Công Thương" class="h-12 w-auto">
                    </a>
                </div>
            </div>

            <!-- Mobile Bottom Navigation -->
            <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 text-gray-700 z-50">
                <div class="grid grid-cols-5 gap-1 p-2">
                    <a href="/" class="flex flex-col items-center text-red-600">
                        <i class="fas fa-home text-xl"></i>
                        <span class="text-xs">Trang chủ</span>
                    </a>
                    <a href="/blogs" class="flex flex-col items-center">
                        <i class="fas fa-newspaper text-xl"></i>
                        <span class="text-xs">Bảng tin</span>
                    </a>
                    <a href="#" class="flex flex-col items-center">
                        <i class="fas fa-bell text-xl"></i>
                        <span class="text-xs">Thông báo</span>
                    </a>
                    <a href="https://nvbplay.vn/cart" class="flex flex-col items-center">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <span class="text-xs">Giỏ hàng</span>
                    </a>
                    <a href="https://nvbplay.vn/my-account" class="flex flex-col items-center">
                        <i class="fas fa-user text-xl"></i>
                        <span class="text-xs">Tài khoản</span>
                    </a>
                </div>
            </div>
        </footer>
    </div>

    <!-- Mobile Menu (Hidden by default) -->
    <div id="main-menu"
        class="fixed inset-0 bg-white z-50 transform -translate-x-full transition duration-300 md:hidden overflow-y-auto">
        <div class="p-4">
            <div class="flex justify-between items-center mb-4">
                <div class="text-lg font-bold">Menu</div>
                <button class="close-menu p-2">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <!-- Mobile Search -->
            <div class="mb-4">
                <div class="relative">
                    <input type="search" placeholder="Tìm kiếm sản phẩm..."
                        class="w-full border border-gray-300 rounded-lg py-2 px-4 pr-10">
                    <button class="absolute right-3 top-2">
                        <i class="fas fa-search text-gray-400"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu Items -->
            <ul class="space-y-2">
                <li><a href="https://nvbplay.vn/?s=v%E1%BB%A3t%20c%E1%BA%A7u%20l%C3%B4ng&amp;post_type=product&amp;_brand=li-ning"
                        class="block py-2 border-b border-gray-100">Trang chủ</a></li>
                <li><a href="https://nvbplay.vn/?s=v%E1%BB%A3t%20c%E1%BA%A7u%20l%C3%B4ng&amp;post_type=product&amp;_brand=li-ning"
                        class="block py-2 border-b border-gray-100">Cửa hàng</a></li>

                <!-- Dropdown example -->
                <li class="relative">
                    <button class="w-full text-left py-2 border-b border-gray-100 flex justify-between items-center">
                        Danh mục sản phẩm
                        <i class="fas fa-chevron-down text-sm"></i>
                    </button>
                    <ul class="pl-4 mt-2 space-y-2 hidden">
                        <li><a href="https://nvbplay.vn/product-category/vot-cau-long" class="block py-1 text-sm">Vợt
                                cầu lông</a></li>
                        <li><a href="https://nvbplay.vn/product-category/ao-the-thao/ao-cau-long"
                                class="block py-1 text-sm">Áo cầu lông</a></li>
                        <li><a href="https://nvbplay.vn/product-category/quan-cau-long" class="block py-1 text-sm">Quần
                                cầu lông</a></li>
                        <li><a href="https://nvbplay.vn/product-category/giay" class="block py-1 text-sm">Giày</a></li>
                    </ul>
                </li>

                <li><a href="https://nvbplay.vn/khuyen-mai" class="block py-2 border-b border-gray-100">Khuyến mãi</a>
                </li>
                <li><a href="https://nvbplay.vn/blogs" class="block py-2 border-b border-gray-100">Blogs</a></li>
            </ul>
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