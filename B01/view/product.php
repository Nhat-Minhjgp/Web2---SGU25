<!DOCTYPE html>
<html lang="vi">

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

        /* Thêm vào phần <style> */
        .rotate-180 {
            transform: rotate(180deg);
        }

        .category-toggle.active {
            background-color: #fef2f2;
            color: #dc2626;
        }

        /* Smooth transition for submenu */
        .category-submenu {
            transition: all 0.3s ease;
        }

        /* Prevent body scroll when menu open */
        body.menu-open {
            overflow: hidden;
        }
    </style>

    <link rel="icon" type="image/svg+xml" href="../img/icons/favicon.png" sizes="32x32">
</head>

<body class="font-sans antialiased bg-gray-50">

    <!-- Popup Overlay -->
    <div id="popup_overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50"></div>

    <!-- Main Wrapper -->
    <div id="wrapper" class="min-h-screen flex flex-col">

        <!-- Header -->
        <header id="header" class="sticky top-0 z-40 bg-white shadow-sm">
            <div class="header-wrapper">

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


            <!-- Main Header -->
            <div id="masthead" class="py-2 md:py-3 border-b">
                <div class="container mx-auto px-4 flex items-center justify-between">

                    <!-- Mobile Menu Toggle (Left) -->
                    <div class="md:hidden">
                        <button class="menu-toggle p-2">
                            <img src="../img/icons/menu.svg" class="fas fa-bars text-2xl"></i>
                        </button>
                    </div>




                    <!-- Desktop Left Menu (hidden on mobile) -->
                    <div class="hidden md:flex items-center flex-1 ml-6">
                        <ul class="flex items-center space-x-4">
                            <li class="relative" id="mega-menu-container">
                                <!-- Mega Menu Trigger -->
                                <button id="mega-menu-trigger"
                                    class="button-menu flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200 transition">
                                    <img src="../img/icons/menu.svg" class="w-5 h-5 mr-2" alt="menu">
                                    <span>Danh mục</span>
                                </button>

                                <!-- Mega Menu Dropdown - Ẩn/Hiện bằng JavaScript -->
                                <div id="mega-menu-dropdown"
                                    class="absolute left-0 top-full mt-2 w-[900px] bg-white rounded-lg shadow-xl hidden z-50">
                                    <div class="flex p-4">
                                        <!-- Left Sidebar - Icon Menu -->
                                        <div class="w-64 border-r border-gray-200 pr-4">
                                            <!-- Cầu Lông - Active -->
                                            <div class="icon-box-menu active bg-red-50 rounded-lg p-3 mb-1 cursor-pointer hover:bg-red-50 transition flex items-start"
                                                data-menu="badminton">
                                                <div class="w-8 h-8 flex-shrink-0 mr-3">
                                                    <img src="https://nvbplay.vn/wp-content/uploads/2024/10/badminton-No.svg"
                                                        alt="Cầu Lông" class="w-full h-full">
                                                </div>
                                                <div>
                                                    <p class="font-bold text-red-600">Cầu Lông</p>
                                                    <p class="text-xs text-gray-500">Trang bị cầu lông chuyên nghiệp
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Pickleball -->
                                            <div class="icon-box-menu p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start"
                                                data-menu="pickleball">
                                                <div class="w-8 h-8 flex-shrink-0 mr-3">
                                                    <img src="https://nvbplay.vn/wp-content/uploads/2024/10/pickleball-No.svg"
                                                        alt="Pickleball" class="w-full h-full">
                                                </div>
                                                <div>
                                                    <p class="font-bold">Pickleball</p>
                                                    <p class="text-xs text-gray-500">Trang bị pickleball hàng đầu
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Giày -->
                                            <div class="icon-box-menu p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start"
                                                data-menu="giay">
                                                <div class="w-8 h-8 flex-shrink-0 mr-3">
                                                    <img src="https://nvbplay.vn/wp-content/uploads/2024/10/jogging-No.svg"
                                                        alt="Giày" class="w-full h-full">
                                                </div>
                                                <div>
                                                    <p class="font-bold">Giày</p>
                                                    <p class="text-xs text-gray-500">Giày thể thao tối ưu hoá vận
                                                        động</p>
                                                </div>
                                            </div>

                                            <!-- Chăm sóc sức khoẻ -->
                                            <a href="https://nvbplay.vn/product-category/san-pham-cham-soc-suc-khoe"
                                                class="block p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start">
                                                <div class="w-6 h-6 flex-shrink-0 mr-3">
                                                    <img src="https://nvbplay.vn/wp-content/uploads/2024/10/healthcare-No.svg"
                                                        alt="Chăm sóc sức khoẻ" class="w-full h-full">
                                                </div>
                                                <div>
                                                    <p class="font-bold">Chăm sóc sức khoẻ</p>
                                                </div>
                                            </a>

                                            <!-- Dịch vụ -->
                                            <a href="https://nvbplay.vn/product-category/dich-vu"
                                                class="block p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start">
                                                <div class="w-6 h-6 flex-shrink-0 mr-3">
                                                    <img src="https://nvbplay.vn/wp-content/uploads/2024/10/customer-service-No.svg"
                                                        alt="Dịch vụ" class="w-full h-full">
                                                </div>
                                                <div>
                                                    <p class="font-bold">Dịch vụ</p>
                                                </div>
                                            </a>

                                            <!-- Tin Tức -->
                                            <div class="icon-box-menu p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start"
                                                data-menu="news">
                                                <div class="w-6 h-6 flex-shrink-0 mr-3">
                                                    <img src="https://nvbplay.vn/wp-content/uploads/2024/10/news-No.svg"
                                                        alt="Tin Tức" class="w-full h-full">
                                                </div>
                                                <div>
                                                    <p class="font-bold">Tin Tức</p>
                                                    <p class="text-xs text-gray-500">Xu hướng mới, sự kiện hot, giảm
                                                        giá sốc!</p>
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
                                                        <a href="./view/shop.php"
                                                            class="text-sm text-red-600 hover:text-red-700 flex items-center">
                                                            Xem tất cả <i class="fas fa-chevron-right ml-1 text-xs"></i>
                                                        </a>
                                                    </div>
                                                    <div class="grid grid-cols-4 gap-2">
                                                        <!-- Yonex -->
                                                        <a href="https://nvbplay.vn/shop?_brand=yonex"
                                                            class="flex flex-col items-center text-center group">
                                                            <div class="w-12 h-12 mb-1">
                                                                <img src="https://nvbplay.vn/wp-content/uploads/2024/10/logo-300x214-1-150x150.webp"
                                                                    alt="Yonex" class="w-full h-full object-contain">
                                                            </div>
                                                            <span class="text-xs">YONEX</span>
                                                        </a>
                                                        <!-- Adidas -->
                                                        <a href="https://nvbplay.vn/shop?_brand=adidas"
                                                            class="flex flex-col items-center text-center group">
                                                            <div class="w-12 h-12 mb-1">
                                                                <img src="https://nvbplay.vn/wp-content/uploads/2024/10/ave6by86s-300x300-1-150x150.webp"
                                                                    alt="Adidas" class="w-full h-full object-contain">
                                                            </div>
                                                            <span class="text-xs">ADIDAS</span>
                                                        </a>
                                                        <!-- Li-Ning -->
                                                        <a href="https://nvbplay.vn/shop?_brand=li-ning"
                                                            class="flex flex-col items-center text-center group">
                                                            <div class="w-12 h-12 mb-1">
                                                                <img src="https://nvbplay.vn/wp-content/uploads/2024/10/Logo-li-ning-300x173-1-150x150.webp"
                                                                    alt="Li-Ning" class="w-full h-full object-contain">
                                                            </div>
                                                            <span class="text-xs">LI-NING</span>
                                                        </a>
                                                        <!-- DS -->
                                                        <a href="https://nvbplay.vn/shop?_brand=ds"
                                                            class="flex flex-col items-center text-center group">
                                                            <div class="w-12 h-12 mb-1">
                                                                <img src="https://nvbplay.vn/wp-content/uploads/2024/10/logo-ds-300x300-1-150x150.jpg"
                                                                    alt="DS" class="w-full h-full object-contain">
                                                            </div>
                                                            <span class="text-xs">DS</span>
                                                        </a>
                                                        <!-- REDSON -->
                                                        <a href="https://nvbplay.vn/shop?_brand=REDSON"
                                                            class="flex flex-col items-center text-center group">
                                                            <div class="w-12 h-12 mb-1">
                                                                <img src="https://nvbplay.vn/wp-content/uploads/2024/10/d464ecc6daed33689bda2bf9a0e93f5e-150x150-1.png"
                                                                    alt="REDSON" class="w-full h-full object-contain">
                                                            </div>
                                                            <span class="text-xs">REDSON</span>
                                                        </a>
                                                        <!-- KAMITO -->
                                                        <a href="https://nvbplay.vn/shop?_brand=kamito"
                                                            class="flex flex-col items-center text-center group">
                                                            <div class="w-12 h-12 mb-1">
                                                                <img src="https://nvbplay.vn/wp-content/uploads/2024/10/logo-kamito-1-300x150-1-150x150.png"
                                                                    alt="KAMITO" class="w-full h-full object-contain">
                                                            </div>
                                                            <span class="text-xs">KAMITO</span>
                                                        </a>
                                                        <!-- TOALSON -->
                                                        <a href="https://nvbplay.vn/shop?_brand=toalson"
                                                            class="flex flex-col items-center text-center group">
                                                            <div class="w-12 h-12 mb-1">
                                                                <img src="https://nvbplay.vn/wp-content/uploads/2024/10/Toalson-300x150-1-150x150.png"
                                                                    alt="TOALSON" class="w-full h-full object-contain">
                                                            </div>
                                                            <span class="text-xs">TOALSON</span>
                                                        </a>
                                                        <!-- YD SPORT -->
                                                        <a href="https://nvbplay.vn/shop?_brand=yd-sport"
                                                            class="flex flex-col items-center text-center group">
                                                            <div class="w-12 h-12 mb-1">
                                                                <img src="https://nvbplay.vn/wp-content/uploads/2024/10/YD-Sport-logo-300x61-1-150x61.png"
                                                                    alt="YD SPORT" class="w-full h-full object-contain">
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
                                                        <a href="./view/shop.php"
                                                            class="text-sm text-red-600 hover:text-red-700 flex items-center">
                                                            Xem tất cả <i class="fas fa-chevron-right ml-1 text-xs"></i>
                                                        </a>
                                                    </div>

                                                    <div class="grid grid-cols-3 gap-4">
                                                        <!-- Vợt cầu lông -->
                                                        <div>
                                                            <a href="/product-category/vot-cau-long"
                                                                class="font-semibold text-sm hover:text-red-600">Vợt
                                                                cầu lông</a>
                                                            <ul class="mt-2 space-y-1">
                                                                <li><a href="https://nvbplay.vn/product-category/vot-cau-long?_brand=yonex"
                                                                        class="text-xs text-gray-600 hover:text-red-600">Vợt
                                                                        cầu lông Yonex</a></li>
                                                                <li><a href="https://nvbplay.vn/product-category/vot-cau-long?_brand=adidas"
                                                                        class="text-xs text-gray-600 hover:text-red-600">Vợt
                                                                        cầu lông Adidas</a></li>
                                                                <li><a href="https://nvbplay.vn/product-category/vot-cau-long?_brand=li-ning"
                                                                        class="text-xs text-gray-600 hover:text-red-600">Vợt
                                                                        cầu lông Li-ning</a></li>
                                                                <li><a href="https://nvbplay.vn/product-category/vot-cau-long?_brand=toalson"
                                                                        class="text-xs text-gray-600 hover:text-red-600">Vợt
                                                                        cầu lông Toalson</a></li>
                                                                <li><a href="/product-category/vot-cau-long"
                                                                        class="text-xs text-red-600 hover:text-red-700">Xem
                                                                        thêm <i
                                                                            class="fas fa-chevron-right ml-1 text-xs"></i></a>
                                                                </li>
                                                            </ul>
                                                        </div>

                                                        <!-- Áo cầu lông -->
                                                        <div>
                                                            <a href="https://nvbplay.vn/product-category/ao-the-thao/ao-cau-long"
                                                                class="font-semibold text-sm hover:text-red-600">Áo
                                                                cầu lông</a>
                                                            <ul class="mt-2 space-y-1">
                                                                <li><a href="https://nvbplay.vn/product-category/ao-the-thao/ao-cau-long?_brand=yonex"
                                                                        class="text-xs text-gray-600 hover:text-red-600">Áo
                                                                        cầu lông Yonex</a></li>
                                                                <li><a href="https://nvbplay.vn/product-category/ao-the-thao/ao-cau-long?_brand=ds"
                                                                        class="text-xs text-gray-600 hover:text-red-600">Áo
                                                                        cầu lông DS</a></li>
                                                                <li><a href="https://nvbplay.vn/product-category/ao-the-thao/ao-cau-long"
                                                                        class="text-xs text-red-600 hover:text-red-700">Xem
                                                                        thêm <i
                                                                            class="fas fa-chevron-right ml-1 text-xs"></i></a>
                                                                </li>
                                                            </ul>
                                                        </div>

                                                        <!-- Quần cầu lông -->
                                                        <div>
                                                            <a href="https://nvbplay.vn/product-category/quan-cau-long"
                                                                class="font-semibold text-sm hover:text-red-600">Quần
                                                                cầu lông</a>
                                                            <ul class="mt-2 space-y-1">
                                                                <li><a href="https://nvbplay.vn/product-category/quan-cau-long?_brand=yonex"
                                                                        class="text-xs text-gray-600 hover:text-red-600">Quần
                                                                        cầu lông Yonex</a></li>
                                                                <li><a href="https://nvbplay.vn/product-category/quan-cau-long?_brand=kamito"
                                                                        class="text-xs text-gray-600 hover:text-red-600">Quần
                                                                        cầu lông Kamito</a></li>
                                                                <li><a href="https://nvbplay.vn/product-category/quan-cau-long"
                                                                        class="text-xs text-red-600 hover:text-red-700">Xem
                                                                        thêm <i
                                                                            class="fas fa-chevron-right ml-1 text-xs"></i></a>
                                                                </li>
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
                                <a href="./view/shop.php"
                                    class="flex items-center text-gray-700 hover:text-red-600 font-medium">
                                    <img src="../img/icons/store.svg" class="w-5 h-5 flex-shrink-0 mr-2">
                                    <span>CỬA HÀNG</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Logo (Center on mobile, left on desktop) -->
                    <div id="logo" class="flex-shrink-1 absolute left-1/2 transform -translate-x-1/2">
                        <a href="../index.php" title="NVBPlay" rel="home">
                            <img width="100" height="40"
                                src="../img/icons/logonvb.png"
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
                        <a href="./my-account.php" class="p-2">
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


        </header>




        <!-- Main Content -->
        <main class="flex-grow bg-gray-50 py-8 md:p-[30px]">
            <div class="container mx-auto px-4">
             
                <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 mb-12">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">

                        <!-- Left: Product Gallery -->
                        <div class="space-y-4">
                            <!-- Main Image -->
                            <div class="relative group overflow-hidden rounded-lg bg-gray-100 aspect-square">
                                <span
                                    class="absolute top-2 left-2 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded z-10">-7%</span>
                                <img src="https://nvbplay.vn/wp-content/uploads/2024/07/Vot-cau-long-Yonex-Astrox-88D-Game-1.jpg"
                                    alt="Vợt cầu lông Yonex Astrox 88D Game"
                                    class="w-full h-full object-contain mix-blend-multiply group-hover:scale-105 transition-transform duration-300">
                                <!-- Zoom Icon Placeholder -->
                                <div
                                    class="absolute bottom-2 left-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button class="bg-white p-2 rounded-full shadow hover:text-red-600"><i
                                            class="fas fa-expand"></i></button>
                                </div>
                            </div>
                            <!-- Thumbnails -->
                            <div class="grid grid-cols-4 gap-2">
                                <button class="border-2 border-red-600 rounded overflow-hidden"><img
                                        src="https://nvbplay.vn/wp-content/uploads/2024/07/Vot-cau-long-Yonex-Astrox-88D-Game-1.jpg"
                                        class="w-full h-auto"></button>
                                <button class="border border-gray-200 rounded overflow-hidden hover:border-red-600"><img
                                        src="https://nvbplay.vn/wp-content/uploads/2024/07/Vot-cau-long-Yonex-Astrox-88D-Game-5.jpg"
                                        class="w-full h-auto"></button>
                                <button class="border border-gray-200 rounded overflow-hidden hover:border-red-600"><img
                                        src="https://nvbplay.vn/wp-content/uploads/2024/07/Vot-cau-long-Yonex-Astrox-88D-Game-3.jpg"
                                        class="w-full h-auto"></button>
                                <button class="border border-gray-200 rounded overflow-hidden hover:border-red-600"><img
                                        src="https://nvbplay.vn/wp-content/uploads/2024/07/Vot-cau-long-Yonex-Astrox-88D-Game-4.jpg"
                                        class="w-full h-auto"></button>
                            </div>
                        </div>

                        <!-- Right: Product Info -->
                        <div class="flex flex-col">
                      
                         

                            <!-- Title -->
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mt-4 mb-4">Vợt cầu lông Yonex Astrox 88D
                                Game</h1>

                            <!-- Short Description -->
                            <div class="text-gray-600 mb-6 mt-6 space-y-4">
                                <h4 class="font-bold text-gray-900">Đặc điểm nổi bật:</h4>
                                <ul class="list-disc pl-5 space-y-1 text-sm">
                                    <li>Mã SP: Astrox 88D Game</li>
                                    <li>Màu sắc: Silver</li>
                                    <li>Khung: HM Graphite, CSR, Tungsten</li>
                                    <li>Trọng lượng: 4U (83g)</li>
                                </ul>
                            </div>

                            <!-- Price -->
                            <div class="mb-6">
                                <div class="flex items-end gap-3">
                                    <span class="text-3xl font-bold text-red-600">1 ₫</span>
                                    <span class="text-lg text-gray-400 line-through mb-1">2.100.000 ₫</span>
                                </div>
                                <div class="text-sm text-green-600 mt-1 flex items-center">
                                    <i class="fas fa-check-circle mr-1"></i> Còn hàng
                                </div>
                            </div>

                           

                            <!-- Add to Cart Form -->
                            <form class="mt-auto space-y-4">
                                <!-- Quantity -->
                                <div class="flex items-center gap-4">
                                    <span class="font-medium text-gray-700">Số lượng:</span>
                                    <div class="flex items-center border border-gray-300 rounded">
                                        <button type="button"
                                            class="px-3 py-2 hover:bg-gray-100 text-gray-600">-</button>
                                        <input type="number" value="1" min="1"
                                            class="w-12 text-center border-none focus:ring-0 p-0" readonly>
                                        <button type="button"
                                            class="px-3 py-2 hover:bg-gray-100 text-gray-600">+</button>
                                    </div>
                                </div>

                                <!-- Buttons -->
                                <div class="grid grid-cols-2 gap-4">
                                    <button type="button"
                                        class="w-full bg-white border-2 border-red-600 text-red-600 font-bold py-3 rounded-xl hover:bg-red-50 transition">
                                        Thêm vào giỏ
                                    </button>
                                    <button type="button"
                                        class="w-full bg-red-600 text-white font-bold py-3 rounded-xl hover:bg-red-700 transition shadow-lg shadow-red-200">
                                        Mua ngay
                                    </button>
                                </div>
                            </form>

                           
                        </div>
                    </div>
                </div>

                <!-- Tabs Section (Description & Info) -->
                <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 mb-12">
                    <div class="border-b border-gray-200 mb-6">
                        <nav class="flex gap-6">
                            <button class="pb-3 border-b-2 border-red-600 text-red-600 font-bold">Mô tả</button>
                          
                    </div>
                    <div class="prose max-w-none text-gray-700">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Vợt cầu lông Yonex Astrox 88D Game</h3>
                        <p class="mb-4">Vợt cầu lông Yonex Astrox 88D Game là lựa chọn hoàn hảo cho những ai yêu thích
                            phong cách chơi tấn công với độ chính xác cao...</p>
                        <h4 class="font-bold text-gray-900 mt-6">1. Power-Assist Bumper</h4>
                        <p class="mb-4">Ứng dụng lý thuyết đối trọng, trọng lượng được phân bố đều từ cuối tay cầm, đỉnh
                            khung và toàn bộ mối nối...</p>
                        <img src="https://nvbplay.vn/wp-content/uploads/2024/07/Vot-Yonex-Astrox-Nextage-11-300x160.jpg"
                            class="w-full md:w-2/3 mx-auto rounded my-4">
                        <h4 class="font-bold text-gray-900 mt-6">2. Định Lượng ISOMETRIC™</h4>
                        <p>Công nghệ ISOMETRIC™ của Yonex giúp tăng điểm ngọt lên 7%, mang lại khả năng kiểm soát tốt
                            hơn...</p>
                    </div>
                </div>

                <!-- Related Products
                <div class="mb-12">
                    <h3 class="text-xl font-bold text-gray-900 mb-6 uppercase">Sản phẩm tương tự</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        Product Item 1
                        <div class="group">
                            <div class="bg-white rounded-lg overflow-hidden shadow-sm mb-2 relative aspect-square">
                                <img src="https://nvbplay.vn/wp-content/uploads/2024/06/vot-cau-long-redson-rg-200-768x768.jpg"
                                    class="w-full h-full object-contain mix-blend-multiply group-hover:scale-105 transition-transform">
                                <span
                                    class="absolute top-2 left-2 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded">-45%</span>
                            </div>
                            <h4 class="text-sm font-medium text-gray-900 line-clamp-2 h-10 mb-1"><a href="#"
                                    class="hover:text-red-600">Vợt cầu lông Redson RG 200</a></h4>
                            <div class="text-red-600 font-bold">2.590.000 ₫</div>
                        </div>
                        Product Item 2
                        <div class="group">
                            <div class="bg-white rounded-lg overflow-hidden shadow-sm mb-2 relative aspect-square">
                                <img src="https://nvbplay.vn/wp-content/uploads/2024/07/Vot-cau-long-Redson-US-22-1-768x768.jpg"
                                    class="w-full h-full object-contain mix-blend-multiply group-hover:scale-105 transition-transform">
                                <span
                                    class="absolute top-2 left-2 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded">-44%</span>
                            </div>
                            <h4 class="text-sm font-medium text-gray-900 line-clamp-2 h-10 mb-1"><a href="#"
                                    class="hover:text-red-600">Vợt cầu lông Redson US-22</a></h4>
                            <div class="text-red-600 font-bold">2.590.000 ₫</div>
                        </div>
                        Product Item 3
                        <div class="group">
                            <div class="bg-white rounded-lg overflow-hidden shadow-sm mb-2 relative aspect-square">
                                <img src="https://nvbplay.vn/wp-content/uploads/2024/07/Vot-cau-long-Yonex-Astrox-77-Pro-1-768x768.jpg"
                                    class="w-full h-full object-contain mix-blend-multiply group-hover:scale-105 transition-transform">
                                <span
                                    class="absolute top-2 left-2 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded">-10%</span>
                            </div>
                            <h4 class="text-sm font-medium text-gray-900 line-clamp-2 h-10 mb-1"><a href="#"
                                    class="hover:text-red-600">Vợt cầu lông Yonex Astrox 77 Pro</a></h4>
                            <div class="text-red-600 font-bold">4.349.000 ₫</div>
                        </div>
                        Product Item 4
                        <div class="group">
                            <div class="bg-white rounded-lg overflow-hidden shadow-sm mb-2 relative aspect-square">
                                <img src="https://nvbplay.vn/wp-content/uploads/2024/08/vot-cau-long-li-ning-halbertec-2000-white-gray-1-768x768.jpg"
                                    class="w-full h-full object-contain mix-blend-multiply group-hover:scale-105 transition-transform">
                                <span
                                    class="absolute top-2 left-2 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded">-18%</span>
                            </div>
                            <h4 class="text-sm font-medium text-gray-900 line-clamp-2 h-10 mb-1"><a href="#"
                                    class="hover:text-red-600">Vợt cầu lông Li-ning HALBERTEC 2000</a></h4>
                            <div class="text-red-600 font-bold">1.139.000 ₫</div>
                        </div>
                        Product Item 5
                        <div class="group">
                            <div class="bg-white rounded-lg overflow-hidden shadow-sm mb-2 relative aspect-square">
                                <img src="https://nvbplay.vn/wp-content/uploads/2024/08/Vot-cau-long-Yonex-Nanoflare-1000Z-2-768x768.jpg"
                                    class="w-full h-full object-contain mix-blend-multiply group-hover:scale-105 transition-transform">
                                <span
                                    class="absolute top-2 left-2 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded">-10%</span>
                            </div>
                            <h4 class="text-sm font-medium text-gray-900 line-clamp-2 h-10 mb-1"><a href="#"
                                    class="hover:text-red-600">Vợt cầu lông Yonex Nanoflare 1000Z</a></h4>
                            <div class="text-red-600 font-bold">5.199.000 ₫</div>
                        </div>
                    </div>
                </div>
            </div> -->
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
                            <li><a href="a" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH BẢO HÀNH ĐỔI
                                    TRẢ</a>
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


    </div>
    </footer>
    </div>

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
                <a href="./my-account.php" class="flex items-center text-gray-700">
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

</body>

</html>