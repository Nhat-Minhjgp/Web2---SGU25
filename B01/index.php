    <?php
    session_start();

    // --- KIỂM TRA ĐĂNG NHẬP ---
    $is_logged_in = isset($_SESSION['user_id']);
    $user_info = null;

    if ($is_logged_in) {
        // Chặn role=1 (Staff/Admin) không được vào khu vực user
        if (isset($_SESSION['role']) && $_SESSION['role'] == 1) {
            session_destroy();
            setcookie('remember_user', '', time() - 3600, '/');
            header("Location: ./view/login.php?error=staff_not_allowed");
            exit();
        }

        $user_info = [
            'user_id' => $_SESSION['user_id'] ?? '',
            'username' => $_SESSION['username'] ?? '',
            'ho_ten' => $_SESSION['ho_ten'] ?? '',
            'email' => $_SESSION['email'] ?? '',
            'role' => $_SESSION['role'] ?? 0
        ];
    }
    ?>
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
        <!-- Font Awesome for icons -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <style>
            #searchInput::-webkit-search-cancel-button {
                -webkit-appearance: none;
                appearance: none;
                display: none;
            }

            #searchInput::-webkit-search-decoration {
                display: none;
            }

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

            .scrollbar-hide::-webkit-scrollbar {
                display: none;
            }

            .scrollbar-hide {
                -ms-overflow-style: none;
                scrollbar-width: none;
            }

            .rotate-180 {
                transform: rotate(180deg);
            }

            .category-toggle.active {
                background-color: #fef2f2;
                color: #dc2626;
            }

            .category-submenu {
                transition: all 0.3s ease;
            }

            body.menu-open {
                overflow: hidden;
            }

            /* === CSS CHO SEARCH HEADER === */
            #searchHeader {
                display: none;
            }

            body.search-active #defaultHeader {
                display: none;
            }

            body.search-active #searchHeader {
                display: flex;
            }

            #searchOverlay {
                transition: opacity 0.3s ease;
                z-index: 30;
            }

            body.search-active #searchOverlay {
                opacity: 1;
                pointer-events: auto;
            }

            /* === CSS CHO SEARCH SUGGESTIONS === */
            #searchSuggestions {
                position: absolute;
                left: 0;
                right: 0;
                top: 100%;
                margin-top: 8px;
                background: white;
                border-radius: 16px;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
                border: 1px solid #f3f4f6;
                overflow: hidden;
                z-index: 50;
                display: none;
                animation: slideDown 0.2s ease;
                max-height: 400px;
                overflow-y: auto;
            }

            #searchSuggestions.active {
                display: block;
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

            /* Cập nhật CSS cho suggestion item */
            .suggestion-item {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px 16px;
                border-bottom: 1px solid #f3f4f6;
                transition: background 0.2s;
                cursor: pointer;
                text-decoration: none;
                color: inherit;
            }

            .suggestion-item:last-child {
                border-bottom: none;
            }

            .suggestion-item:hover {
                background: #f9fafb;
            }

            .suggestion-item img {
                width: 60px;
                height: 60px;
                object-fit: cover;
                border-radius: 8px;
                background: #f3f4f6;
                flex-shrink: 0;
            }

            .suggestion-info {
                flex: 1;
                min-width: 0;
            }

            .suggestion-info h4 {
                font-size: 14px;
                font-weight: 500;
                color: #1f2937;
                margin: 0 0 6px 0;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
                line-height: 1.4;
            }

            .price-wrapper {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 4px;
            }

            .price-wrapper .price {
                font-size: 15px;
                font-weight: 600;
                color: #dc2626;
            }

            .price-wrapper .old-price {
                font-size: 13px;
                color: #9ca3af;
                text-decoration: line-through;
            }

            .price-wrapper .discount-badge {
                font-size: 11px;
                font-weight: 600;
                color: #dc2626;
                background: #fef2f2;
                padding: 2px 6px;
                border-radius: 4px;
            }

            .meta-info {
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: 12px;
                color: #6b7280;
                margin-bottom: 4px;
            }

            .meta-info .brand {
                color: #9ca3af;
            }

            .stock-status {
                font-size: 11px;
                display: flex;
                align-items: center;
                gap: 4px;
            }

            .stock-status.in-stock {
                color: #10b981;
            }

            .stock-status.out-of-stock {
                color: #ef4444;
            }

            .view-all-link {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 14px;
                background: #f9fafb;
                color: #dc2626;
                font-size: 14px;
                font-weight: 500;
                text-decoration: none;
                border-top: 1px solid #f3f4f6;
                transition: background 0.2s;
            }

            .view-all-link:hover {
                background: #f3f4f6;
            }

            .no-results {
                padding: 32px 24px;
                text-align: center;
            }

            .no-results p {
                margin: 0;
            }

            /* === USER DROPDOWN STYLES === */
            .user-dropdown {
                position: relative;
            }

            .user-menu {
                position: absolute;
                right: 0;
                top: 100%;
                margin-top: 8px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
                border: 1px solid #f3f4f6;
                min-width: 220px;
                z-index: 50;
                display: none;
                animation: slideDown 0.2s ease;
            }

            .user-menu.active {
                display: block;
            }

            .user-menu-item {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 12px 16px;
                color: #374151;
                text-decoration: none;
                transition: background 0.2s;
                font-size: 14px;
            }

            .user-menu-item:hover {
                background: #f9fafb;
            }

            .user-menu-item i {
                width: 18px;
                color: #6b7280;
            }

            .user-menu-divider {
                border-top: 1px solid #f3f4f6;
                margin: 4px 0;
            }

            .user-menu-item.logout {
                color: #dc2626;
            }

            .user-menu-item.logout i {
                color: #dc2626;
            }

            /* Role badge styles */
            .role-badge-staff {
                background: #dc2626;
            }

            .role-badge-admin {
                background: #7c3aed;
            }

            .role-badge-user {
                background: #6b7280;
            }

            @media (max-width: 768px) {
                .suggestion-item img {
                    width: 40px;
                    height: 40px;
                }

                .suggestion-info h4 {
                    font-size: 14px;
                }

                .suggestion-info .price {
                    font-size: 13px;
                }
            }

            /* === CSS CHO SEARCH INPUT  ===*/
            #searchInput {
                background-color: #EEEEEE;
                transition: all 0.2s ease;
            }

            #searchInput:focus {
                background-color: #EEEEEE;
                border-color: #ffffff;
                box-shadow: 0 0 0 3px rgb(255, 255, 255);
            }

            #searchInput::placeholder {
                color: #1a1919;
                font-size: 15px;
                font-weight: 400;
            }

            /* Nút search và đóng trong header */
            .search-action-btn {
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 9999px;
                transition: all 0.2s;
                cursor: pointer;
            }

            .search-action-btn:hover {
                background-color: #ffffff;
            }

            .close-btn {
                color: #ffffff;
            }

            .close-btn:hover {
                background-color: #ffffff;
                color: white;
            }
        </style>
        <link rel="icon" type="image/svg+xml" href="./img/icons/favicon.png" sizes="32x32">
    </head>

    <body class="font-sans antialiased bg-gray-50">
        <!-- Popup Overlay -->
        <div id="popup_overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50"></div>

        <!-- Main Wrapper -->
        <div id="wrapper" class="min-h-screen flex flex-col">
            <!-- Header -->
            <header id="header" class="sticky top-0 z-40 bg-white shadow-sm">
                <div class="header-wrapper">
                    <!-- Bottom Header / Wide Nav (quảng cáo trên cùng) -->
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

                    <!-- Main Header -->
                    <div id="masthead" class="py-2 md:py-3 border-b">
                        <div class="container mx-auto px-4">
                            <!-- ========== DEFAULT HEADER (luôn hiển thị trừ khi search mở) ========== -->
                            <div id="defaultHeader" class="grid grid-cols-[1fr,auto,1fr] items-center gap-0 relative">

                                <!-- Cột trái: menu mobile + menu desktop (căn trái) -->
                                <div class="flex items-center justify-start">
                                    <!-- Mobile Menu Toggle (chỉ hiện trên mobile) -->
                                    <div class="md:hidden">
                                        <button class="menu-toggle p-2 focus:outline-none">
                                            <img src="./img/icons/menu.svg" class="w-6 h-6" alt="menu">
                                        </button>
                                    </div>

                                    <!-- Desktop Left Menu (chỉ hiện trên desktop) -->
                                    <div class="hidden md:flex items-center ml-0 lg:ml-2">
                                        <ul class="flex items-center space-x-4">
                                            <!-- Mega Menu Trigger (GIỮ NGUYÊN) -->
                                            <div class="hidden md:flex items-center ml-0 lg:ml-2">
                                                <ul class="flex items-center space-x-4">
                                                    <li class="relative" id="mega-menu-container">
                                                        <button id="mega-menu-trigger" class="button-menu flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200 transition">
                                                            <img src="./img/icons/menu.svg" class="w-5 h-5 mr-2" alt="menu">
                                                            <span>Danh mục</span>
                                                        </button>

                                                        <!-- Mega Menu Dropdown (CHỈ SỬA PHẦN NÀY) -->
                                                        <div id="mega-menu-dropdown" class="absolute left-0 top-full mt-2 w-[900px] bg-white rounded-lg shadow-xl hidden z-50">
                                                            <div class="flex p-4">
                                                                <!-- Left Sidebar - Icon Menu -->
                                                                <div class="w-64 border-r border-gray-200 pr-4">
                                                                    <!-- Cầu Lông - Active -->
                                                                    <div class="icon-box-menu active bg-red-50 rounded-lg p-3 mb-1 cursor-pointer hover:bg-red-50 transition flex items-start" data-menu="badminton">
                                                                        <div class="w-8 h-8 flex-shrink-0 mr-3">
                                                                            <img src="./img/icons/logo-caulong.png" alt="Cầu Lông" class="w-full h-full">
                                                                        </div>
                                                                        <div>
                                                                            <p class="font-bold text-red-600">Cầu Lông</p>
                                                                            <p class="text-xs text-gray-500">Trang bị cầu lông chuyên nghiệp</p>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Pickleball -->
                                                                    <div class="icon-box-menu p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start" data-menu="pickleball">
                                                                        <div class="w-8 h-8 flex-shrink-0 mr-3">
                                                                            <img src="./img/icons/logo-pickleball.png" alt="Pickleball" class="w-full h-full">
                                                                        </div>
                                                                        <div>
                                                                            <p class="font-bold">Pickleball</p>
                                                                            <p class="text-xs text-gray-500">Trang bị pickleball hàng đầu</p>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Giày -->
                                                                    <div class="icon-box-menu p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start" data-menu="giay">
                                                                        <div class="w-8 h-8 flex-shrink-0 mr-3">
                                                                            <img src="./img/icons/logo-giay.png" alt="Giày" class="w-full h-full">
                                                                        </div>
                                                                        <div>
                                                                            <p class="font-bold">Giày</p>
                                                                            <p class="text-xs text-gray-500">Giày thể thao tối ưu hoá vận động</p>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Các mục khác giữ nguyên -->
                                                                    <a href="https://nvbplay.vn/product-category/san-pham-cham-soc-suc-khoe" class="block p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start">
                                                                        <div class="w-6 h-6 flex-shrink-0 mr-3">
                                                                            <img src="https://nvbplay.vn/wp-content/uploads/2024/10/healthcare-No.svg" alt="Chăm sóc sức khoẻ" class="w-full h-full">
                                                                        </div>
                                                                        <div>
                                                                            <p class="font-bold">Chăm sóc sức khoẻ</p>
                                                                        </div>
                                                                    </a>
                                                                    <a href="https://nvbplay.vn/product-category/dich-vu" class="block p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start">
                                                                        <div class="w-6 h-6 flex-shrink-0 mr-3">
                                                                            <img src="https://nvbplay.vn/wp-content/uploads/2024/10/customer-service-No.svg" alt="Dịch vụ" class="w-full h-full">
                                                                        </div>
                                                                        <div>
                                                                            <p class="font-bold">Dịch vụ</p>
                                                                        </div>
                                                                    </a>
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

                                                                <!-- Right Content -->
                                                                <div class="flex-1 pl-4">
                                                                    <!-- Content Badminton -->
                                                                    <div id="content-badminton" class="menu-content">
                                                                        <!-- Thương hiệu nổi bật - 8 HÃNG -->
                                                                        <div class="mb-4">
                                                                            <div class="flex items-center justify-between mb-2">
                                                                                <h3 class="font-bold">Thương hiệu nổi bật</h3>
                                                                                <a href="./view/shop.php" class="text-sm text-red-600 hover:text-red-700 flex items-center">
                                                                                    Xem tất cả <i class="fas fa-chevron-right ml-1 text-xs"></i>
                                                                                </a>
                                                                            </div>
                                                                            <div class="grid grid-cols-4 gap-2">
                                                                                <!-- YONEX -->
                                                                                <a href="./view/shop.php?thuonghieu[]=yonex" class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                    <div class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                        <img src="./img/icons/logo-yonex.webp" alt="Yonex" class="w-full h-full object-contain" onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                    </div>
                                                                                    <span class="text-sm font-medium">YONEX</span>
                                                                                </a>

                                                                                <!-- ADIDAS -->
                                                                                <a href="./view/shop.php?thuonghieu[]=adidas" class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                    <div class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                        <img src="./img/icons/logo-adidas.webp" alt="Adidas" class="w-full h-full object-contain" onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                    </div>
                                                                                    <span class="text-sm font-medium">ADIDAS</span>
                                                                                </a>

                                                                                <!-- LI-NING -->
                                                                                <a href="./view/shop.php?thuonghieu[]=li-ning" class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                    <div class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                        <img src="./img/icons/Logo-li-ning.png" alt="Li-Ning" class="w-full h-full object-contain" onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                    </div>
                                                                                    <span class="text-sm font-medium">LI-NING</span>
                                                                                </a>

                                                                                <!-- VICTOR -->
                                                                                <a href="./view/shop.php?thuonghieu[]=victor" class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                    <div class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                        <img src="./img/icons/logo-victor.png" alt="Victor" class="w-full h-full object-contain" onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                    </div>
                                                                                    <span class="text-sm font-medium">VICTOR</span>
                                                                                </a>

                                                                                <!-- KAMITO -->
                                                                                <a href="./view/shop.php?thuonghieu[]=kamito" class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                    <div class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                        <img src="./img/icons/logo-kamito.png" alt="KAMITO" class="w-full h-full object-contain" onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                    </div>
                                                                                    <span class="text-sm font-medium">KAMITO</span>
                                                                                </a>

                                                                                <!-- MIZUNO -->
                                                                                <a href="./view/shop.php?thuonghieu[]=mizuno" class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                    <div class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                        <img src="./img/icons/logo-mizuno.png" alt="Mizuno" class="w-full h-full object-contain" onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                    </div>
                                                                                    <span class="text-sm font-medium">MIZUNO</span>
                                                                                </a>

                                                                                <!-- KUMPOO -->
                                                                                <a href="./view/shop.php?thuonghieu[]=kumpoo" class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                    <div class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                        <img src="./img/icons/logo-kumpoo.png" alt="Kumpoo" class="w-full h-full object-contain" onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                    </div>
                                                                                    <span class="text-sm font-medium">KUMPOO</span>
                                                                                </a>

                                                                                <!-- VENSON -->
                                                                                <a href="./view/shop.php?thuonghieu[]=venson" class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                    <div class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                        <img src="./img/icons/logo-venson.png" alt="Venson" class="w-full h-full object-contain" onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                    </div>
                                                                                    <span class="text-sm font-medium">VENSON</span>
                                                                                </a>
                                                                            </div>
                                                                        </div>

                                                                        <div class="border-t border-gray-200 my-3"></div>

                                                                        <!-- Theo sản phẩm - CẦU LÔNG -->
                                                                        <div>
                                                                            <div class="flex items-center justify-between mb-2">
                                                                                <h3 class="font-bold">Theo sản phẩm</h3>
                                                                                <a href="./view/shop.php?danhmuc[]=vot-cau-long" class="text-sm text-red-600 hover:text-red-700 flex items-center">
                                                                                    Xem tất cả <i class="fas fa-chevron-right ml-1 text-xs"></i>
                                                                                </a>
                                                                            </div>
                                                                            <div class="grid grid-cols-3 gap-4">
                                                                                <!-- Vợt cầu lông - 8 thương hiệu + Xem thêm -->
                                                                                <div>
                                                                                    <a href="./view/shop.php?danhmuc[]=vot-cau-long" class="font-semibold text-sm hover:text-red-600">Vợt cầu lông</a>
                                                                                    <ul class="mt-2 space-y-1">
                                                                                        <li><a href="./view/shop.php?danhmuc[]=vot-cau-long&thuonghieu[]=yonex" class="text-xs text-gray-600 hover:text-red-600">Vợt cầu lông Yonex</a></li>
                                                                                        <li><a href="./view/shop.php?danhmuc[]=vot-cau-long&thuonghieu[]=li-ning" class="text-xs text-gray-600 hover:text-red-600">Vợt cầu lông Li-Ning</a></li>
                                                                                        <li><a href="./view/shop.php?danhmuc[]=vot-cau-long&thuonghieu[]=adidas" class="text-xs text-gray-600 hover:text-red-600">Vợt cầu lông Adidas</a></li>
                                                                                        <li><a href="./view/shop.php?danhmuc[]=vot-cau-long&thuonghieu[]=victor" class="text-xs text-gray-600 hover:text-red-600">Vợt cầu lông Victor</a></li>
                                                                                        <li><a href="./view/shop.php?danhmuc[]=vot-cau-long" class="text-xs text-red-600 hover:text-red-700 font-medium">Xem thêm <i class="fas fa-chevron-right ml-1 text-[10px]"></i></a></li>
                                                                                    </ul>
                                                                                </div>

                                                                                <!-- Balo cầu lông - 8 thương hiệu + Xem thêm -->
                                                                                <div>
                                                                                    <a href="./view/shop.php?danhmuc[]=balo-cau-long" class="font-semibold text-sm hover:text-red-600">Balo cầu lông</a>
                                                                                    <ul class="mt-2 space-y-1">
                                                                                        <li><a href="./view/shop.php?danhmuc[]=balo-cau-long&thuonghieu[]=yonex" class="text-xs text-gray-600 hover:text-red-600">Balo Yonex</a></li>
                                                                                        <li><a href="./view/shop.php?danhmuc[]=balo-cau-long&thuonghieu[]=li-ning" class="text-xs text-gray-600 hover:text-red-600">Balo Li-Ning</a></li>
                                                                                        <li><a href="./view/shop.php?danhmuc[]=balo-cau-long&thuonghieu[]=adidas" class="text-xs text-gray-600 hover:text-red-600">Balo Adidas</a></li>
                                                                                        <li><a href="./view/shop.php?danhmuc[]=balo-cau-long&thuonghieu[]=victor" class="text-xs text-gray-600 hover:text-red-600">Balo Victor</a></li>
                                                                                        <li><a href="./view/shop.php?danhmuc[]=balo-cau-long" class="text-xs text-red-600 hover:text-red-700 font-medium">Xem thêm <i class="fas fa-chevron-right ml-1 text-[10px]"></i></a></li>
                                                                                    </ul>
                                                                                </div>

                                                                                <!-- Phụ kiện cầu lông - Giữ nguyên -->
                                                                                <div>
                                                                                    <a href="./view/shop.php?danhmuc[]=phu-kien" class="font-semibold text-sm hover:text-red-600">Phụ kiện</a>
                                                                                    <ul class="mt-2 space-y-1">
                                                                                        <li><a href="./view/shop.php?danhmuc[]=phu-kien" class="text-xs text-gray-600 hover:text-red-600">Tất cả phụ kiện</a></li>
                                                                                        <li><a href="./view/shop.php?danhmuc[]=phu-kien&search=quả+cầu" class="text-xs text-gray-600 hover:text-red-600">Quả cầu lông</a></li>
                                                                                        <li><a href="./view/shop.php?danhmuc[]=phu-kien&search=cước+đan" class="text-xs text-gray-600 hover:text-red-600">Cước đan vợt</a></li>
                                                                                        <li><a href="./view/shop.php?danhmuc[]=phu-kien&search=quấn+cán" class="text-xs text-gray-600 hover:text-red-600">Quấn cán</a></li>
                                                                                        <li><a href="./view/shop.php?danhmuc[]=phu-kien" class="text-xs text-red-600 hover:text-red-700 font-medium">Xem thêm <i class="fas fa-chevron-right ml-1 text-[10px]"></i></a></li>
                                                                                    </ul>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Content for Pickleball -->
                                                                    <div id="content-pickleball" class="menu-content hidden">
                                                                        <!-- Thương hiệu nổi bật - 4 HÃNG (CÂN ĐỐI) -->
                                                                        <div class="mb-4">
                                                                            <div class="flex items-center justify-between mb-2">
                                                                                <h3 class="font-bold">Thương hiệu nổi bật</h3>
                                                                                <a href="./view/shop.php?danhmuc[]=vot-pickleball" class="text-sm text-red-600 hover:text-red-700 flex items-center">
                                                                                    Xem tất cả <i class="fas fa-chevron-right ml-1 text-xs"></i>
                                                                                </a>
                                                                            </div>
                                                                            <div class="grid grid-cols-4 gap-2">
                                                                                <!-- JOOLA -->
                                                                                <a href="./view/shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=joola" class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                    <div class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                        <img src="./img/icons/logo-joola.png" alt="JOOLA" class="w-full h-full object-contain" onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                    </div>
                                                                                    <span class="text-sm font-medium">JOOLA</span>
                                                                                </a>

                                                                                <!-- SELKIRK -->
                                                                                <a href="./view/shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=selkirk" class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                    <div class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                        <img src="./img/icons/logo-selkirk.webp" alt="SELKIRK" class="w-full h-full object-contain" onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                    </div>
                                                                                    <span class="text-sm font-medium">SELKIRK</span>
                                                                                </a>

                                                                                <!-- KAMITO -->
                                                                                <a href="./view/shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=kamito" class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                    <div class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                        <img src="./img/icons/logo-kamito.png" alt="KAMITO" class="w-full h-full object-contain" onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                    </div>
                                                                                    <span class="text-sm font-medium">KAMITO</span>
                                                                                </a>

                                                                                <!-- WIKA -->
                                                                                <a href="./view/shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=wika" class="flex items-center bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition group">
                                                                                    <div class="w-10 h-10 flex-shrink-0 mr-2">
                                                                                        <img src="./img/icons/logo-wika.png" alt="WIKA" class="w-full h-full object-contain" onerror="this.src='./img/icons/placeholder-brand.svg'">
                                                                                    </div>
                                                                                    <span class="text-sm font-medium">WIKA</span>
                                                                                </a>
                                                                            </div>
                                                                        </div>

                                                                        <div class="border-t border-gray-200 my-3"></div>

                                                                        <!-- Theo sản phẩm - PICKLEBALL -->
                                                                        <div>
                                                                            <div class="flex items-center justify-between mb-2">
                                                                                <h3 class="font-bold">Theo sản phẩm</h3>
                                                                                <a href="./view/shop.php?danhmuc[]=vot-pickleball" class="text-sm text-red-600 hover:text-red-700 flex items-center">
                                                                                    Xem tất cả <i class="fas fa-chevron-right ml-1 text-xs"></i>
                                                                                </a>
                                                                            </div>
                                                                            <div class="grid grid-cols-3 gap-4">
                                                                                <!-- Vợt Pickleball - 4 thương hiệu + Xem thêm -->
                                                                                <div>
                                                                                    <a href="./view/shop.php?danhmuc[]=vot-pickleball" class="font-semibold text-sm hover:text-red-600">Vợt Pickleball</a>
                                                                                    <ul class="mt-2 space-y-1">
                                                                                        <li><a href="./view/shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=joola" class="text-xs text-gray-600 hover:text-red-600">Vợt Joola</a></li>
                                                                                        <li><a href="./view/shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=selkirk" class="text-xs text-gray-600 hover:text-red-600">Vợt Selkirk</a></li>
                                                                                        <li><a href="./view/shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=kamito" class="text-xs text-gray-600 hover:text-red-600">Vợt Kamito</a></li>
                                                                                        <li><a href="./view/shop.php?danhmuc[]=vot-pickleball&thuonghieu[]=wika" class="text-xs text-gray-600 hover:text-red-600">Vợt Wika</a></li>
                                                                                        <li><a href="./view/shop.php?danhmuc[]=vot-pickleball" class="text-xs text-red-600 hover:text-red-700 font-medium">Xem thêm <i class="fas fa-chevron-right ml-1 text-[10px]"></i></a></li>
                                                                                    </ul>
                                                                                </div>

                                                                                <!-- Balo/Túi Pickleball - 4 thương hiệu + Xem thêm -->
                                                                                <div>
                                                                                    <a href="./view/shop.php?danhmuc[]=balo-tui-pickleball" class="font-semibold text-sm hover:text-red-600">Balo - Túi Pickleball</a>
                                                                                    <ul class="mt-2 space-y-1">
                                                                                        <li><a href="./view/shop.php?danhmuc[]=balo-tui-pickleball&thuonghieu[]=joola" class="text-xs text-gray-600 hover:text-red-600">Balo Joola</a></li>
                                                                                        <li><a href="./view/shop.php?danhmuc[]=balo-tui-pickleball&thuonghieu[]=selkirk" class="text-xs text-gray-600 hover:text-red-600">Balo Selkirk</a></li>
                                                                                        <li><a href="./view/shop.php?danhmuc[]=balo-tui-pickleball&thuonghieu[]=kamito" class="text-xs text-gray-600 hover:text-red-600">Balo Kamito</a></li>
                                                                                        <li><a href="./view/shop.php?danhmuc[]=balo-tui-pickleball&thuonghieu[]=wika" class="text-xs text-gray-600 hover:text-red-600">Balo Wika</a></li>
                                                                                        <li><a href="./view/shop.php?danhmuc[]=balo-tui-pickleball" class="text-xs text-red-600 hover:text-red-700 font-medium">Xem thêm <i class="fas fa-chevron-right ml-1 text-[10px]"></i></a></li>
                                                                                    </ul>
                                                                                </div>

                                                                                <!-- Phụ kiện Pickleball -->
                                                                                <div>
                                                                                    <a href="./view/shop.php?danhmuc[]=phu-kien-pickleball" class="font-semibold text-sm hover:text-red-600">Phụ kiện Pickleball</a>
                                                                                    <ul class="mt-2 space-y-1">
                                                                                        <li><a href="./view/shop.php?danhmuc[]=phu-kien-pickleball&search=bóng" class="text-xs text-gray-600 hover:text-red-600">Bóng Pickleball</a></li>
                                                                                        <li><a href="./view/shop.php?danhmuc[]=phu-kien-pickleball&search=lưới" class="text-xs text-gray-600 hover:text-red-600">Lưới Pickleball</a></li>
                                                                                        <li><a href="./view/shop.php?danhmuc[]=phu-kien-pickleball" class="text-xs text-red-600 hover:text-red-700 font-medium">Xem thêm <i class="fas fa-chevron-right ml-1 text-[10px]"></i></a></li>
                                                                                    </ul>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Content Giày -->
                                                                    <div id="content-giay" class="menu-content hidden">
                                                                        <div class="text-center py-16">
                                                                            <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                                                                                <i class="fas fa-shoe-prints text-3xl text-gray-400"></i>
                                                                            </div>
                                                                            <h3 class="text-lg font-bold text-gray-700 mb-2">Sản Phẩm Sớm Ra Mắt</h3>
                                                                            <p class="text-gray-500 text-sm">Chúng tôi đang chuẩn bị những mẫu giày thể thao chất lượng nhất. Hãy quay lại sau nhé!</p>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Content News -->
                                                                    <div id="content-news" class="menu-content hidden">
                                                                        <div class="text-center py-10 text-gray-500">
                                                                            <p>Nội dung Tin Tức sẽ hiển thị ở đây</p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </li>
                                                </ul>
                                            </div>
                                            <li><a href="./view/shop.php"
                                                    class="flex items-center text-gray-700 hover:text-red-600 font-medium"><img
                                                        src="./img/icons/store.svg"
                                                        class="w-5 h-5 flex-shrink-0 mr-2"><span>CỬA HÀNG</span></a></li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Cột giữa: LOGO – được căn giữa hoàn hảo nhờ grid -->
                                <div id="logo" class="flex justify-center items-center">
                                    <a href="./index.php" title="NVBPlay" rel="home">
                                        <img width="100" height="40" src="./img/icons/logonvb.png" alt="NVBPlay"
                                            class="h-12 md:h-14 w-auto transform scale-75">
                                    </a>
                                </div>

                                <!-- Cột phải: các thành phần desktop + mobile (căn phải) -->
                                <div class="flex items-center justify-end">
                                    <!-- Desktop Right Elements (ẩn trên mobile) -->
                                    <div class="hidden md:flex items-center space-x-4">
                                        <!-- Address Book (chỉ hiển thị khi đã đăng nhập) - giả lập biến is_logged_in = false để demo, nếu true sẽ hiện -->


                                        <?php if ($is_logged_in): ?>
                                            <div class="address-book">
                                                <a href="./view/my-account/address-book.php"
                                                    class="flex items-center text-gray-700 hover:text-red-600">
                                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                                    <span class="shipping-address text-sm"><span class="text">Chọn địa
                                                            chỉ</span></span>
                                                </a>
                                            </div>
                                            <div class="h-5 w-px bg-gray-300"></div>
                                        <?php endif; ?>

                                        <!-- Search button -->
                                        <button id="searchToggle"
                                            class="search-toggle p-2 text-gray-700 hover:text-red-600">
                                            <i class="fas fa-search text-xl"></i>
                                        </button>

                                        <!-- Account Dropdown -->
                                        <div class="user-dropdown relative">
                                            <?php if ($is_logged_in): ?>
                                                <button id="userToggle"
                                                    class="flex items-center space-x-2 hover:bg-gray-100 px-3 py-2 rounded-lg transition">
                                                    <img src="./img/icons/account.svg" class="w-6 h-6" alt="Account">
                                                    <span
                                                        class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($user_info['username']); ?></span>
                                                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                                </button>
                                                <div id="userMenu" class="user-menu">
                                                    <div class="px-4 py-3 border-b border-gray-100">
                                                        <div class="flex items-center space-x-3">
                                                            <img src="./img/icons/account.svg" class="w-10 h-10" alt="Account">
                                                            <div>
                                                                <p class="text-sm font-medium text-gray-800">
                                                                    <?php echo htmlspecialchars($user_info['username']); ?></p>
                                                                <p class="text-xs text-gray-500">
                                                                    <?php echo htmlspecialchars($user_info['email']); ?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <a href="./view/my-account.php" class="user-menu-item"><i
                                                            class="fas fa-user"></i><span>Tài khoản của tôi</span></a>
                                                    <a href="./view/my-account/orders.php" class="user-menu-item"><i
                                                            class="fas fa-shopping-bag"></i><span>Đơn hàng</span></a>
                                                    <a href="./view/my-account/address-book.php" class="user-menu-item"><i
                                                            class="fas fa-map-marker-alt"></i><span>Sổ địa chỉ</span></a>
                                                    <div class="user-menu-divider"></div>
                                                    <a href="./control/logout.php" class="user-menu-item logout"><i
                                                            class="fas fa-sign-out-alt"></i><span>Đăng xuất</span></a>
                                                </div>
                                            <?php else: ?>
                                                <a href="./view/login.php"
                                                    class="flex items-center text-gray-700 hover:text-red-600">
                                                    <i class="far fa-user text-xl"></i>
                                                    <span class="text-sm ml-1">Đăng nhập</span>
                                                </a>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Cart -->
                                        <a href="./view/cart.php" class="relative p-2">
                                            <i class="fas fa-shopping-basket text-gray-700 hover:text-red-600 text-xl"></i>
                                            <span
                                                class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">0</span>
                                        </a>
                                    </div>

                                    <!-- Mobile Right Elements (chỉ hiện trên mobile) -->
                                    <div class="md:hidden flex items-center space-x-3">
                                        <button id="searchToggleMobile" class="search-toggle p-1">
                                            <i class="fas fa-search text-xl text-gray-700"></i>
                                        </button>
                                        <?php if ($is_logged_in): ?>
                                            <a href="./view/my-account.php" class="p-1">
                                                <img src="./img/icons/account.svg" class="w-6 h-6" alt="Account">
                                            </a>
                                        <?php else: ?>
                                            <a href="./view/login.php" class="p-1">
                                                <i class="far fa-user text-xl text-gray-700"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="./view/cart.php" class="relative p-1">
                                            <i class="fas fa-shopping-basket text-xl text-gray-700"></i>
                                            <span
                                                class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">0</span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- ========== SEARCH HEADER (ẩn ban đầu, hiện khi bấm search) ========== -->
                            <div id="searchHeader" class="hidden items-center justify-center py-2">
                                <div class="w-full max-w-[800px] relative">
                                    <input type="text"
                                        id="searchInput"
                                        class="w-full px-5 pr-14 py-3 text-base border-2 border-gray-200 rounded-full focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-600/20 transition-all bg-gray-50 focus:bg-white"
                                        placeholder="Tên sản phẩm, hãng..."
                                        value=""
                                        name="search"
                                        autocomplete="off">
                                    <button type="submit"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center text-gray-500 hover:text-black transition-all rounded-full">
                                        <i class="fas fa-search text-lg"></i>
                                    </button>
                                    <button id="closeSearchBtn"
                                        class="absolute -right-12 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center bg-gray-200  justify-center text-gray-500 hover:text-black transition-all rounded-full">
                                        <i class="fas fa-times text-lg"></i>
                                    </button>

                                    <!-- Dropdown gợi ý tìm kiếm -->
                                    <div id="searchSuggestions"
                                        class="absolute top-full left-0 right-0 mt-2 bg-white border border-gray-100 rounded-2xl shadow-xl overflow-hidden hidden z-50">
                                        <div id="suggestionsList" class="max-h-96 overflow-y-auto custom-scrollbar"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            </header>
            <!-- Search Overlay -->
            <div id="searchOverlay"
                class="fixed inset-0 bg-black/50 opacity-0 pointer-events-none transition-opacity duration-300 z-30"></div>

            <!-- Main Content -->
            <main id="main" class="flex-grow">
                <div id="content" class="content-area">
                    <div class="container mx-auto px-4 py-6 md:py-8">
                        <!-- Hero Banner Section -->
                        <section class="banner-hero mb-6 md:mb-8">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="md:col-span-2 rounded-lg overflow-hidden shadow-lg h-full">
                                    <a href="./view/shop.php"
                                        class="block h-full">
                                        <img src="https://nvbplay.vn/wp-content/uploads/2026/02/MUA-DEAL-RON-RANG-scaled.png"
                                            alt="MUA DEAL RON RANG" class="w-full h-full object-cover">
                                    </a>
                                </div>
                                <div class="flex flex-col space-y-4 h-full">
                                    <div class="rounded-lg overflow-hidden shadow-lg flex-1">
                                        <a href="./view/shop.php"
                                            class="block h-full">
                                            <img src="https://nvbplay.vn/wp-content/uploads/2026/02/ThayDoThaGa-KhongLoVeGia-2x1-1.png"
                                                alt="ThayDoThaGa" class="w-full h-full object-cover">
                                        </a>
                                    </div>
                                    <div class="rounded-lg overflow-hidden shadow-lg flex-1">
                                        <a href="./view/shop.php"
                                            class="block h-full">
                                            <img src="https://nvbplay.vn/wp-content/uploads/2026/02/ron-rang-xuan-sang-san-deal-hoanh-trang-1200x600-1-1.png"
                                                alt="Ron rang xuan sang" class="w-full h-full object-cover">
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Section Danh Mục - Lấy từ Database -->
                        <?php
                        // Lấy kết nối database (đảm bảo đã có ở đầu file)
                        if (!isset($conn)) {
                            require_once './control/connect.php';
                        }

                        // Lấy 3 danh mục từ database (ID 1, 2, 3 theo SQL dump)
                        $categories_sql = "SELECT d.Danhmuc_id, d.Ten_danhmuc, d.slug,
    (SELECT image_url FROM sanpham WHERE Danhmuc_id = d.Danhmuc_id AND TrangThai = 1 AND image_url IS NOT NULL LIMIT 1) as sample_image,
    (SELECT COUNT(*) FROM sanpham WHERE Danhmuc_id = d.Danhmuc_id AND TrangThai = 1) as product_count
    FROM danhmuc d
    WHERE d.Danhmuc_id IN (1, 2, 3)
    ORDER BY d.Danhmuc_id";
                        $categories_result = $conn->query($categories_sql);
                        ?>

                        <!-- Section Danh Mục -->
                        <section class="home-product mb-8">
                            <div class="mb-4">
                                <h2 class="text-xl md:text-2xl font-bold relative inline-block after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-12 after:h-1 after:bg-red-600 pb-2">
                                    Danh mục
                                </h2>
                            </div>

                            <!-- Grid 3 cột -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <?php while ($category = $categories_result->fetch_assoc()): ?>
                                    <div class="flex-shrink-0">
                                        <a href="./view/shop.php?danhmuc[]=<?php echo htmlspecialchars($category['slug']); ?>" class="block group">
                                            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-300">
                                                <div class="aspect-square overflow-hidden p-4 bg-gray-50">
                                                    <?php if (!empty($category['sample_image'])): ?>
                                                        <img src="./<?php echo htmlspecialchars($category['sample_image']); ?>"
                                                            alt="<?php echo htmlspecialchars($category['Ten_danhmuc']); ?>"
                                                            class="w-full h-full object-contain group-hover:scale-105 transition duration-300"
                                                            onerror="this.src='./img/sanpham/placeholder.png'">
                                                    <?php else: ?>
                                                        <img src="./img/sanpham/placeholder.png"
                                                            alt="<?php echo htmlspecialchars($category['Ten_danhmuc']); ?>"
                                                            class="w-full h-full object-contain">
                                                    <?php endif; ?>
                                                </div>
                                                <div class="p-4 text-center">
                                                    <h3 class="font-semibold text-base text-gray-800 group-hover:text-red-600 transition">
                                                        <?php echo htmlspecialchars($category['Ten_danhmuc']); ?>
                                                    </h3>
                                                    <p class="text-sm text-gray-500 mt-1">
                                                        <?php echo $category['product_count']; ?> sản phẩm
                                                    </p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </section>



                        <!-- Featured Products Section - Áo NVBPLAY -->
                        <!-- Section Vợt Cầu Lông -->
                        <section class="home-product mb-8">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-xl md:text-2xl font-bold relative inline-block after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-12 after:h-1 after:bg-red-600 pb-2">
                                    VỢT CẦU LÔNG NỔI BẬT
                                </h2>
                                <a href="./view/shop.php?danhmuc[]=vot-cau-long" class="text-red-600 hover:text-red-700 font-medium flex items-center">
                                    Xem tất cả <i class="fas fa-chevron-right ml-1 text-sm"></i>
                                </a>
                            </div>

                            <?php
                            // Lấy vợt cầu lông nổi bật từ database (Danhmuc_id = 1)
                            $vot_sql = "SELECT s.*, d.Ten_danhmuc, th.Ten_thuonghieu
                FROM sanpham s
                LEFT JOIN danhmuc d ON s.Danhmuc_id = d.Danhmuc_id
                LEFT JOIN thuonghieu th ON s.Ma_thuonghieu = th.Ma_thuonghieu
                WHERE s.Danhmuc_id = 1 AND s.TrangThai = 1
                ORDER BY s.TaoNgay DESC
                LIMIT 5";

                            $vot_result = $conn->query($vot_sql);
                            ?>

                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                <?php while ($product = $vot_result->fetch_assoc()):
                                    // Tính phần trăm giảm giá
                                    $discount = 0;
                                    if ($product['GiaNhapTB'] > $product['GiaBan'] && $product['GiaNhapTB'] > 0) {
                                        $discount = round(($product['GiaNhapTB'] - $product['GiaBan']) / $product['GiaNhapTB'] * 100);
                                    }
                                ?>
                                    <div class="bg-white rounded-lg shadow-md overflow-hidden group hover:shadow-lg transition duration-300">
                                        <div class="relative">
                                            <?php if ($discount > 0): ?>
                                                <div class="absolute top-2 left-2 z-10">
                                                    <span class="bg-red-600 text-white text-xs px-2 py-1 rounded-full">-<?php echo $discount; ?>%</span>
                                                </div>
                                            <?php endif; ?>

                                            <a href="./view/product.php?id=<?php echo $product['SanPham_id']; ?>" class="block aspect-square overflow-hidden">
                                                <?php if (!empty($product['image_url'])): ?>
                                                    <img src="./<?php echo htmlspecialchars($product['image_url']); ?>"
                                                        alt="<?php echo htmlspecialchars($product['TenSP']); ?>"
                                                        class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                                        onerror="this.src='./img/sanpham/placeholder.png'">
                                                <?php else: ?>
                                                    <img src="./img/sanpham/placeholder.png"
                                                        alt="<?php echo htmlspecialchars($product['TenSP']); ?>"
                                                        class="w-full h-full object-cover">
                                                <?php endif; ?>
                                            </a>
                                        </div>

                                        <div class="p-3">
                                            <h3 class="font-medium text-sm mb-2 line-clamp-2 h-10">
                                                <a href="./view/product.php?id=<?php echo $product['SanPham_id']; ?>"
                                                    class="hover:text-red-600">
                                                    <?php echo htmlspecialchars($product['TenSP']); ?>
                                                </a>
                                            </h3>

                                            <div class="flex items-center space-x-2">
                                                <span class="text-red-600 font-bold">
                                                    <?php echo number_format($product['GiaBan'], 0, ',', '.'); ?>₫
                                                </span>
                                                <?php if ($discount > 0): ?>
                                                    <span class="text-gray-400 text-sm line-through">
                                                        <?php echo number_format($product['GiaNhapTB'], 0, ',', '.'); ?>₫
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($product['SoLuongTon'] <= 0): ?>
                                                <div class="text-xs text-red-500 mt-1">Hết hàng</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
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

                       <!-- Section Phụ Kiện Nổi Bật -->
<section class="home-product mb-8">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl md:text-2xl font-bold relative inline-block after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-12 after:h-1 after:bg-red-600 pb-2">
            PHỤ KIỆN NỔI BẬT
        </h2>
        <a href="./view/shop.php?danhmuc[]=phu-kien" class="text-red-600 hover:text-red-700 font-medium flex items-center">
            Xem tất cả <i class="fas fa-chevron-right ml-1 text-sm"></i>
        </a>
    </div>
    
    <?php
    // Lấy phụ kiện từ database (dùng slug để xác định danh mục)
    $phukien_sql = "SELECT s.*, d.Ten_danhmuc, th.Ten_thuonghieu
                    FROM sanpham s
                    LEFT JOIN danhmuc d ON s.Danhmuc_id = d.Danhmuc_id
                    LEFT JOIN thuonghieu th ON s.Ma_thuonghieu = th.Ma_thuonghieu
                    WHERE d.slug = 'phu-kien' AND s.TrangThai = 1
                    ORDER BY s.TaoNgay DESC
                    LIMIT 4";
    
    $phukien_result = $conn->query($phukien_sql);
    ?>
    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php while ($product = $phukien_result->fetch_assoc()): 
            // Tính phần trăm giảm giá
            $discount = 0;
            if ($product['GiaNhapTB'] > $product['GiaBan'] && $product['GiaNhapTB'] > 0) {
                $discount = round(($product['GiaNhapTB'] - $product['GiaBan']) / $product['GiaNhapTB'] * 100);
            }
        ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden group hover:shadow-lg transition duration-300">
            <div class="relative">
                <?php if ($discount > 0): ?>
                <div class="absolute top-2 left-2 z-10">
                    <span class="bg-red-600 text-white text-xs px-2 py-1 rounded-full">-<?php echo $discount; ?>%</span>
                </div>
                <?php endif; ?>
                
                <?php if ($product['SoLuongTon'] <= 0): ?>
                <div class="absolute top-2 right-2 z-10">
                    <span class="bg-gray-500 text-white text-xs px-2 py-1 rounded-full">Hết hàng</span>
                </div>
                <?php endif; ?>
                
                <a href="./view/product.php?id=<?php echo $product['SanPham_id']; ?>" class="block aspect-square overflow-hidden">
                    <?php if (!empty($product['image_url'])): ?>
                        <img src="./<?php echo htmlspecialchars($product['image_url']); ?>"
                             alt="<?php echo htmlspecialchars($product['TenSP']); ?>"
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                             onerror="this.src='./img/sanpham/placeholder.png'">
                    <?php else: ?>
                        <img src="./img/sanpham/placeholder.png"
                             alt="<?php echo htmlspecialchars($product['TenSP']); ?>"
                             class="w-full h-full object-cover">
                    <?php endif; ?>
                </a>
            </div>
            
            <div class="p-3">
                <h3 class="font-medium text-sm mb-2 line-clamp-2 h-10">
                    <a href="./view/product.php?id=<?php echo $product['SanPham_id']; ?>" 
                       class="hover:text-red-600">
                        <?php echo htmlspecialchars($product['TenSP']); ?>
                    </a>
                </h3>
                
                <div class="flex items-center space-x-2">
                    <span class="text-red-600 font-bold">
                        <?php echo number_format($product['GiaBan'], 0, ',', '.'); ?>₫
                    </span>
                    <?php if ($discount > 0): ?>
                    <span class="text-gray-400 text-sm line-through">
                        <?php echo number_format($product['GiaNhapTB'], 0, ',', '.'); ?>₫
                    </span>
                    <?php endif; ?>
                </div>
                
                <?php if ($product['SoLuongTon'] <= 0): ?>
                <div class="text-xs text-red-500 mt-1">Hết hàng</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</section>

                        <!-- Sale Products Section 
                        <section class="mb-8">
                            <div class="flex items-center justify-between mb-4">
                                <h2
                                    class="text-xl md:text-2xl font-bold relative inline-block after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-12 after:h-1 after:bg-red-600 pb-2">
                                    GIẢM GIÁ LÊN ĐẾN 45%
                                </h2>
                                <a href="https://nvbplay.vn/product-tag/top-sale"
                                    class="text-red-600 hover:text-red-700 font-medium flex items-center">
                                    Xem tất cả <i class="fas fa-chevron-right ml-1 text-sm"></i>
                                </a>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                <!-- Sale Product 1 
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
                            </div>
                        </section>

                        <!-- New Arrivals Section 
                        <section class="mb-8">
                            <div class="flex items-center justify-between mb-4">
                                <h2
                                    class="text-xl md:text-2xl font-bold relative inline-block after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-12 after:h-1 after:bg-red-600 pb-2">
                                    Hàng mới đổ bộ
                                </h2>
                                <a href="./view/shop.php"
                                    class="text-red-600 hover:text-red-700 font-medium flex items-center">
                                    Xem tất cả <i class="fas fa-chevron-right ml-1 text-sm"></i>
                                </a>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                <!-- New Product 1 
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
                            </div>
                        </section> -->
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
                                <a href="https://www.facebook.com/nvbplay" target="_blank"
                                    class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition"><i
                                        class="fab fa-facebook-f"></i></a>
                                <a href="https://www.tiktok.com/@nvbplay.vn" target="_blank"
                                    class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition"><i
                                        class="fab fa-tiktok"></i></a>
                                <a href="https://s.shopee.vn/6AV9qQcpMz" target="_blank"
                                    class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition"><i
                                        class="fas fa-shopping-bag"></i></a>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold mb-4">Thông tin khác</h3>
                            <ul class="space-y-2">
                                <li><a href="" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH BẢO MẬT</a></li>
                                <li><a href="" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH THANH TOÁN</a>
                                </li>
                                <li><a href="" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH BẢO HÀNH ĐỔI
                                        TRẢ</a></li>
                                <li><a href="" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH VẬN CHUYỂN</a>
                                </li>
                                <li><a href="" class="text-gray-400 hover:text-white transition">THOẢ THUẬN SỬ DỤNG</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold mb-4">Về chúng tôi</h3>
                            <ul class="space-y-3">
                                <li><a href="https://maps.app.goo.gl/mwqaes9hQJks8FSu5" target="_blank" class="flex"><span
                                            class="font-medium w-20">Địa chỉ:</span><span class="text-gray-400">62 Lê Bình,
                                            Tân An, Cần Thơ</span></a></li>
                                <li>
                                    <div class="flex"><span class="font-medium w-20">Giờ làm việc:</span><span
                                            class="text-gray-400">08:00 - 21:00</span></div>
                                </li>
                                <li><a href="tel:0987.879.243" class="flex"><span
                                            class="font-medium w-20">Hotline:</span><span
                                            class="text-gray-400">0987.879.243</span></a></li>
                                <li><a href="mailto:info@nvbplay.vn" class="flex"><span
                                            class="font-medium w-20">Email:</span><span
                                            class="text-gray-400">info@nvbplay.vn</span></a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="border-t border-gray-800 my-6"></div>
                    <div class="flex flex-col md:flex-row justify-between items-center">
                        <div class="text-gray-500 text-sm mb-4 md:mb-0">
                            <p>©2025 CÔNG TY CỔ PHẦN NVB PLAY</p>
                            <p>GPĐKKD số 1801779686 do Sở KHĐT TP. Cần Thơ cấp ngày 22 tháng 01 năm 2025</p>
                        </div>
                        <a href="http://online.gov.vn/Home/WebDetails/129261" target="_blank"><img
                                src="https://nvbplay.vn/wp-content/uploads/2024/09/Logo-Bo-Cong-Thuong-Xanh.png"
                                alt="Bộ Công Thương" class="h-12 w-auto"></a>
                    </div>
                </div>
            </footer>
        </div>

        <!-- Mobile Menu -->
        <div id="main-menu"
            class="fixed inset-0 bg-white z-50 transform -translate-x-full transition duration-300 md:hidden overflow-y-auto">
            <div class="p-4">
                <div class="flex justify-between items-center mb-6">
                    <img src="./img/icons/logonvb.png" height="30" width="50" class="relative-top-left transform scale-75">
                    <button class="close-menu p-2 hover:bg-gray-100 rounded-full transition"><i
                            class="fas fa-times text-2xl text-gray-600"></i></button>
                </div>
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                    <?php if ($is_logged_in): ?>
                        <div class="flex items-center text-gray-700">
                            <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                                <img src="./img/icons/account.svg" class="w-6 h-6" alt="Account">
                            </div>
                            <div>
                                <div class="font-medium"><?php echo htmlspecialchars($user_info['username']); ?></div>
                                <span class="text-sm text-gray-500"><?php echo htmlspecialchars($user_info['email']); ?></span>
                            </div>
                        </div>
                        <a href="./control/logout.php" class="text-red-600 text-sm font-medium">Đăng xuất</a>
                    <?php else: ?>
                        <a href="./view/login.php" class="flex items-center text-gray-700">
                            <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3"><i
                                    class="far fa-user text-xl text-gray-600"></i></div>
                            <div>
                                <div class="font-medium">Tài khoản</div><span class="text-sm text-gray-500">Đăng nhập / Đăng
                                    ký</span>
                            </div>
                        </a>
                    <?php endif; ?>
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

                        <div class="pl-11 pr-3 mt-2 space-y-2 hidden category-submenu" id="submenu-giay">
                            <div><a href="https://nvbplay.vn/product-category/giay?_brand=yonex"
                                    class="block py-2 text-gray-700">Giày Yonex</a></div>
                            <div><a href="https://nvbplay.vn/product-category/giay?_brand=adidas"
                                    class="block py-2 text-gray-700">Giày Adidas</a></div>
                            <div><a href="https://nvbplay.vn/product-category/giay?_brand=mizuno"
                                    class="block py-2 text-gray-700">Giày Mizuno</a></div>
                            <div><a href="https://nvbplay.vn/product-category/giay?_brand=asics"
                                    class="block py-2 text-gray-700">Giày Asics</a></div>
                            <div><a href="https://nvbplay.vn/product-category/giay?_brand=kamito"
                                    class="block py-2 text-gray-700">Giày Kamito</a></div>
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

                        <div class="pl-11 pr-3 mt-2 space-y-2 hidden category-submenu" id="submenu-news">
                            <div><a href="https://nvbplay.vn/thong-tin" class="block py-2 text-gray-700">Thông tin</a></div>
                            <div><a href="https://nvbplay.vn/cau-long" class="block py-2 text-gray-700">Cầu lông</a></div>
                            <div><a href="https://nvbplay.vn/pickleball" class="block py-2 text-gray-700">Pickleball</a>
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

        <!-- JavaScript -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // ========== MOBILE MENU TOGGLE ==========
                const menuToggle = document.querySelector('.menu-toggle');
                const closeMenu = document.querySelector('.close-menu');
                const mobileMenu = document.getElementById('main-menu');
                if (menuToggle) {
                    menuToggle.addEventListener('click', function() {
                        mobileMenu.classList.remove('-translate-x-full');
                        document.body.style.overflow = 'hidden';
                    });
                }
                if (closeMenu) {
                    closeMenu.addEventListener('click', function() {
                        mobileMenu.classList.add('-translate-x-full');
                        document.body.style.overflow = '';
                    });
                }

                // ========== USER DROPDOWN TOGGLE ==========
                const userToggle = document.getElementById('userToggle');
                const userMenu = document.getElementById('userMenu');
                if (userToggle && userMenu) {
                    userToggle.addEventListener('click', function(e) {
                        e.stopPropagation();
                        userMenu.classList.toggle('active');
                    });
                    document.addEventListener('click', function(e) {
                        if (!userToggle.contains(e.target) && !userMenu.contains(e.target)) {
                            userMenu.classList.remove('active');
                        }
                    });
                }

                // ========== CATEGORY DROPDOWN TOGGLES (QUAN TRỌNG) ==========
                const categoryToggles = document.querySelectorAll('.category-toggle');
                categoryToggles.forEach(toggle => {
                    toggle.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const category = this.getAttribute('data-category');
                        const submenu = document.getElementById(`submenu-${category}`);
                        const icon = this.querySelector('.fa-chevron-down, .fa-chevron-up');

                        if (submenu) {
                            // Toggle hiển thị submenu
                            submenu.classList.toggle('hidden');

                            // Xoay icon
                            if (icon) {
                                if (submenu.classList.contains('hidden')) {
                                    icon.classList.remove('fa-chevron-up');
                                    icon.classList.add('fa-chevron-down');
                                } else {
                                    icon.classList.remove('fa-chevron-down');
                                    icon.classList.add('fa-chevron-up');
                                }
                                icon.classList.toggle('rotate-180');
                            }

                            // Toggle active state cho button
                            this.classList.toggle('bg-red-50');
                            this.classList.toggle('text-red-600');
                        }
                    });
                });

                // Đóng submenu khi click ra ngoài
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.category-toggle') && !e.target.closest('.category-submenu')) {
                        document.querySelectorAll('.category-submenu').forEach(submenu => {
                            submenu.classList.add('hidden');
                            const parentBtn = submenu.closest('.mb-2')?.querySelector('.category-toggle');
                            if (parentBtn) {
                                const icon = parentBtn.querySelector('.fa-chevron-down, .fa-chevron-up');
                                if (icon) {
                                    icon.classList.remove('fa-chevron-up', 'rotate-180');
                                    icon.classList.add('fa-chevron-down');
                                }
                                parentBtn.classList.remove('bg-red-50', 'text-red-600');
                            }
                        });
                    }
                });

                // ========== DESKTOP MEGA MENU ==========
                const menuTrigger = document.getElementById('mega-menu-trigger');
                const menuDropdown = document.getElementById('mega-menu-dropdown');
                const menuItems = document.querySelectorAll('.icon-box-menu[data-menu]');
                const menuContents = document.querySelectorAll('.menu-content');

                if (menuTrigger) {
                    menuTrigger.addEventListener('click', function(e) {
                        e.stopPropagation();
                        menuDropdown.classList.toggle('hidden');
                    });
                }

                menuItems.forEach(item => {
                    item.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const menuId = this.getAttribute('data-menu');

                        menuItems.forEach(el => {
                            el.classList.remove('active', 'bg-red-50');
                            const titleEl = el.querySelector('.font-bold');
                            if (titleEl) titleEl.classList.remove('text-red-600');
                        });

                        this.classList.add('active', 'bg-red-50');
                        const activeTitle = this.querySelector('.font-bold');
                        if (activeTitle) activeTitle.classList.add('text-red-600');

                        menuContents.forEach(content => content.classList.add('hidden'));
                        const activeContent = document.getElementById(`content-${menuId}`);
                        if (activeContent) activeContent.classList.remove('hidden');
                    });
                });

                document.addEventListener('click', function(e) {
                    if (!menuDropdown?.contains(e.target) && !menuTrigger?.contains(e.target)) {
                        menuDropdown?.classList.add('hidden');
                    }
                });

                if (menuDropdown) {
                    menuDropdown.addEventListener('click', function(e) {
                        e.stopPropagation();
                    });
                }


                // ========== SEARCH FUNCTIONALITY (CẬP NHẬT) ==========
                const searchToggle = document.getElementById('searchToggle');
                const searchToggleMobile = document.getElementById('searchToggleMobile');
                const closeSearchBtn = document.getElementById('closeSearchBtn');
                const searchOverlay = document.getElementById('searchOverlay');
                const searchInput = document.getElementById('searchInput');
                const suggestionsContainer = document.getElementById('searchSuggestions');
                const suggestionsList = document.getElementById('suggestionsList');

                // Debounce function để giảm số lần gọi API
                function debounce(func, delay) {
                    let timeoutId;
                    return function(...args) {
                        clearTimeout(timeoutId);
                        timeoutId = setTimeout(() => func.apply(this, args), delay);
                    };
                }

                // Hàm fetch sản phẩm từ database
                async function fetchSearchSuggestions(query) {
                    if (!query || query.length < 1) {
                        suggestionsContainer.classList.remove('active');
                        return;
                    }

                    try {
                        const response = await fetch(`./control/search-suggest.php?q=${encodeURIComponent(query)}`);
                        const result = await response.json();

                        if (result.success && result.data.length > 0) {
                            // Hiển thị tối đa 8 sản phẩm
                            const limitedResults = result.data.slice(0, 8);

                            suggestionsList.innerHTML = limitedResults.map(product => `
                <a href="${product.url}" class="suggestion-item">
                    <img src="./${product.image}" alt="${product.name}" loading="lazy"
                         onerror="this.src='./img/sanpham/placeholder.png'">
                    <div class="suggestion-info">
                        <h4>${product.name}</h4>
                        <div class="price-wrapper">
                            <span class="price">${product.price}</span>
                            ${product.old_price ? `<span class="old-price">${product.old_price}</span>` : ''}
                            ${product.discount > 0 ? `<span class="discount-badge">-${product.discount}%</span>` : ''}
                        </div>
                        <div class="meta-info">
                            <span class="category">${product.category}</span>
                            ${product.brand ? `<span class="brand">• ${product.brand}</span>` : ''}
                        </div>
                        <div class="stock-status ${product.inStock ? 'in-stock' : 'out-of-stock'}">
                            ${product.inStock ? 
                                `<i class="fas fa-check-circle"></i> Còn hàng (${product.stock_quantity})` : 
                                '<i class="fas fa-times-circle"></i> Hết hàng'
                            }
                        </div>
                    </div>
                </a>
            `).join('');

                            // Thêm link "Xem tất cả" nếu có nhiều hơn 8 sản phẩm
                            if (result.count > 8) {
                                suggestionsList.innerHTML += `
                    <a href="./view/shop.php?search=${encodeURIComponent(query)}" class="view-all-link">
                        <i class="fas fa-search"></i> Xem tất cả ${result.count} kết quả
                    </a>
                `;
                            }

                            suggestionsContainer.classList.add('active');
                        } else {
                            suggestionsList.innerHTML = `
                <div class="no-results">
                    <i class="fas fa-search mb-2 text-gray-400 text-2xl"></i>
                    <p class="text-gray-600 font-medium">Không tìm thấy sản phẩm</p>
                    <p class="text-xs text-gray-400 mt-1">Thử tìm với từ khóa khác</p>
                </div>
            `;
                            suggestionsContainer.classList.add('active');
                        }
                    } catch (error) {
                        console.error('Lỗi tìm kiếm:', error);
                        suggestionsList.innerHTML = `
            <div class="no-results">
                <i class="fas fa-exclamation-triangle mb-2 text-orange-400 text-2xl"></i>
                <p class="text-gray-600">Đã có lỗi xảy ra</p>
            </div>
        `;
                        suggestionsContainer.classList.add('active');
                    }
                }

                // Sử dụng debounce để tránh gọi API quá nhiều khi gõ
                const debouncedSearch = debounce(fetchSearchSuggestions, 300);

                // Mở search
                function enableSearch() {
                    document.body.classList.add('search-active');
                    setTimeout(() => searchInput.focus(), 100);
                }

                // Đóng search
                function disableSearch() {
                    document.body.classList.remove('search-active');
                    suggestionsContainer.classList.remove('active');
                    if (searchInput) searchInput.value = '';
                }

                // Event listeners
                if (searchToggle) searchToggle.addEventListener('click', enableSearch);
                if (searchToggleMobile) searchToggleMobile.addEventListener('click', enableSearch);
                if (closeSearchBtn) closeSearchBtn.addEventListener('click', disableSearch);
                if (searchOverlay) searchOverlay.addEventListener('click', disableSearch);

                // ESC key để đóng
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && document.body.classList.contains('search-active')) {
                        disableSearch();
                    }
                });

                // Input event - gọi API khi gõ
                if (searchInput) {
                    searchInput.addEventListener('input', function(e) {
                        debouncedSearch(e.target.value.trim());
                    });

                    // Nhấn Enter để tìm kiếm
                    searchInput.addEventListener('keypress', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            const query = searchInput.value.trim();
                            if (query) {
                                window.location.href = `./view/shop.php?search=${encodeURIComponent(query)}`;
                            }
                        }
                    });
                }

                // Click outside để đóng suggestions
                document.addEventListener('click', function(e) {
                    if (searchInput && suggestionsContainer &&
                        !searchInput.contains(e.target) &&
                        !suggestionsContainer.contains(e.target)) {
                        suggestionsContainer.classList.remove('active');
                    }
                });

            });
        </script>

    </body>

    </html>