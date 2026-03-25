<?php
/**
 * Checkout Page - NVBPlay Style 
 */
session_start();
require_once '../control/connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// === KIỂM TRA ĐĂNG NHẬP ===
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 0) != 0) {
	header("Location: login.php?redirect=checkout");
	exit();
}

$user_id = (int) $_SESSION['user_id'];
$success = '';
$errors = [];

// ===  LẤY THÔNG TIN USER  ===
$user_info = null;
$stmt = $conn->prepare("SELECT User_id, Username, Ho_ten, email, SDT FROM users WHERE User_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user_info) {
	session_destroy();
	header("Location: login.php?redirect=checkout");
	exit();
}

// === LẤY ĐỊA CHỈ MẶC ĐỊNH ===
$default_address = null;
$default_full_address = '';
$stmt = $conn->prepare("SELECT * FROM diachigh WHERE User_id = ? AND Mac_dinh = 1 LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
	$default_address = $result->fetch_assoc();
	$parts = array_filter([
		$default_address['Duong'],
		$default_address['Quan'],
		$default_address['Tinh_thanhpho'],
		$default_address['Dia_chi_chitiet']
	]);
	$default_full_address = implode(', ', $parts);
}
$stmt->close();

// === KHỞI TẠO BIẾN CART ===
$cart_items = [];
$cart_total = 0;
$total_items = 0;

// Load cart from session
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
	$ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
	if (!empty($ids)) {
		$products = $conn->query("SELECT * FROM sanpham WHERE SanPham_id IN ($ids) AND TrangThai = 1");

		while ($product = $products->fetch_assoc()) {
			$qty = $_SESSION['cart'][$product['SanPham_id']] ?? 1;
			$product['quantity'] = $qty;
			$gia_ban = (float) ($product['GiaBan'] ?? 0);
			$product['subtotal'] = $gia_ban * $qty;
			$cart_total += $product['subtotal'];
			$total_items += $qty;
			$cart_items[] = $product;
		}
	}
}

$order_total = $cart_total;

// === Hàm formatPrice ===
function formatPrice($price)
{
	$price = (float) ($price ?? 0);
	return number_format($price, 0, ',', '.') . '₫';
}

// === XỬ LÝ FORM SUBMIT ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
	$payment_method = $_POST['payment_method'] ?? 'cod';

	// Validation
	$fullname = trim($_POST['fullname'] ?? '');
	$phone = trim($_POST['phone'] ?? '');
	$address = trim($_POST['address'] ?? '');
	$someoneReceive = isset($_POST['someoneReceive']) && $_POST['someoneReceive'] == '1';
	$recipient_name = $someoneReceive ? trim($_POST['recipient_name'] ?? '') : '';
	$recipient_phone = $someoneReceive ? trim($_POST['recipient_phone'] ?? '') : '';

	if (empty($fullname) || strlen($fullname) < 2) {
		$errors[] = "Họ tên không hợp lệ";
	}
	if (!preg_match('/^0[0-9]{9}$/', $phone)) {
		$errors[] = "Số điện thoại phải bắt đầu bằng 0 và có 10 số";
	}
	if (empty($address) || strlen($address) < 10) {
		$errors[] = "Địa chỉ không hợp lệ (cần ít nhất 10 ký tự)";
	}
	if (empty($cart_items)) {
		$errors[] = "Giỏ hàng trống";
	}
	// Nếu có người nhận khác, kiểm tra thông tin người nhận
	if ($someoneReceive) {
		if (empty($recipient_name) || strlen($recipient_name) < 2) {
			$errors[] = "Tên người nhận không hợp lệ";
		}
		if (!preg_match('/^0[0-9]{9}$/', $recipient_phone)) {
			$errors[] = "Số điện thoại người nhận phải bắt đầu bằng 0 và có 10 số";
		}
	}

	// Kiểm tra tồn kho
	foreach ($cart_items as $item) {
		$qty = $item['quantity'];
		if ($qty > $item['SoLuongTon']) {
			$errors[] = "Sản phẩm '{$item['TenSP']}' chỉ còn {$item['SoLuongTon']} trong kho, bạn đặt $qty";
		}
	}
	if (empty($errors)) {
		$tracking_code = 'NVB' . date('Ymd') . strtoupper(substr(md5(uniqid($user_id . time(), true)), 0, 8));
		$link_tra_cuu = '/view/track-order.php?code=' . $tracking_code;

		$conn->begin_transaction();
		try {
			// 1. Xử lý địa chỉ
			$selected_address_id = !empty($_POST['selected_address_id']) ? (int) $_POST['selected_address_id'] : null;

			// 🆕 Nếu không có địa chỉ được chọn từ sổ (người dùng nhập tay)
			if ($selected_address_id === null) {
				// Xác định tên và SĐT từ form
				$addr_name = trim($_POST['fullname'] ?? '');
				$addr_phone = trim($_POST['phone'] ?? '');

				// Tách địa chỉ thành các thành phần
				$parts = explode(',', $address, 2);
				$duong = trim($parts[0] ?? '');
				$chi_tiet = isset($parts[1]) ? trim($parts[1]) : '';

				// Insert địa chỉ mới
				$stmt = $conn->prepare("INSERT INTO diachigh (User_id, Ten_nguoi_nhan, SDT_nhan, Duong, Quan, Tinh_thanhpho, Dia_chi_chitiet, Mac_dinh) VALUES (?, ?, ?, ?, '', '', ?, 0)");
				$stmt->bind_param("issss", $user_id, $addr_name, $addr_phone, $duong, $chi_tiet);
				if (!$stmt->execute()) {
					throw new Exception("Không thể lưu địa chỉ mới: " . $stmt->error);
				}
				$selected_address_id = $stmt->insert_id;
				$stmt->close();
			}

			// 2. Chèn đơn hàng
			$order_total_int = (int) round($order_total);
			$stmt = $conn->prepare("INSERT INTO donhang (User_id, DiaChi_id, PhuongThucTT, TongTien, NgayDat, TrangThai, linkTraCuu) VALUES (?, ?, ?, ?, NOW(), 'Chờ xác nhận', ?)");
			$stmt->bind_param("iisds", $user_id, $selected_address_id, $payment_method, $order_total_int, $link_tra_cuu);
			if (!$stmt->execute()) {
				throw new Exception("Insert donhang failed: " . $stmt->error);
			}
			$don_hang_id = $stmt->insert_id;
			$stmt->close();

			// 3. Chèn chi tiết đơn hàng
			$stmt_detail = $conn->prepare("INSERT INTO chitiethoadon (DonHang_id, SanPham_id, SoLuong, Gia) VALUES (?, ?, ?, ?)");
			foreach ($cart_items as $item) {
				$gia = (float) ($item['GiaBan'] ?? 0);
				$qty = (int) ($item['quantity'] ?? 1);
				$stmt_detail->bind_param("iiid", $don_hang_id, $item['SanPham_id'], $qty, $gia);
				$stmt_detail->execute();
			}
			$stmt_detail->close();

			// 4. Cập nhật tồn kho
			$stmt_stock = $conn->prepare("UPDATE sanpham SET SoLuongTon = SoLuongTon - ? WHERE SanPham_id = ? AND SoLuongTon >= ?");
			foreach ($cart_items as $item) {
				$qty = (int) ($item['quantity'] ?? 1);
				$stmt_stock->bind_param("iii", $qty, $item['SanPham_id'], $qty);
				$stmt_stock->execute();
			}
			$stmt_stock->close();

			$conn->commit();

			// Redirect
			header("Location: order-confirmation.php?order_id=" . $don_hang_id . "&code=" . $tracking_code);
			exit();

		} catch (Exception $e) {
			$conn->rollback();
			error_log("Checkout error: " . $e->getMessage());
			$errors[] = "Lỗi hệ thống: " . $e->getMessage();
		}
	}
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
	<title>Thanh Toán | NVBPlay</title>

	<!-- Security Headers -->
	<meta http-equiv="X-Content-Type-Options" content="nosniff">
	<meta http-equiv="X-Frame-Options" content="SAMEORIGIN">

	<!-- Tailwind CSS -->
	<script src="https://cdn.tailwindcss.com"></script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

	<style>
		/* Custom Styles */
		.popup-overlay {
			display: none !important;

			position: fixed;
			inset: 0;
			background: rgba(0, 0, 0, 0.5);
			z-index: 99999 !important;

			align-items: center;
			justify-content: center;
		}

		.popup-overlay.active {
			display: flex !important;

		}

		.popup-content {
			background: white;
			border-radius: 16px;
			max-width: 520px;
			width: 95%;
			max-height: 90vh;
			overflow-y: auto;
			z-index: 100000;

			position: relative;
		}

		.address-item {
			transition: all 0.2s;
			cursor: pointer;
		}

		.address-item:hover {
			border-color: #FF3F1A;
			background: #fff5f3;
		}

		.address-item.selected {
			border-color: #FF3F1A;
			background: #fff5f3;
		}

		.form-row input.error {
			border-color: #dc3545;
		}

		.error-message {
			color: #dc3545;
			font-size: 13px;
			margin-top: 4px;
			display: none;
		}

		.success-message {
			color: #16a34a;
			font-size: 13px;
			margin-top: 4px;
			display: none;
		}

		.loading {
			position: relative;
			pointer-events: none;
			opacity: 0.8;
		}

		.loading:after {
			content: "";
			position: absolute;
			width: 20px;
			height: 20px;
			border: 2px solid #fff;
			border-top-color: transparent;
			border-radius: 50%;
			animation: spin 0.8s linear infinite;
			top: 50%;
			left: 50%;
			margin: -10px;
		}

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

		@keyframes spin {
			to {
				transform: rotate(360deg);
			}
		}

		@media (max-width: 768px) {
			.grid-2 {
				grid-template-columns: 1fr;
			}
		}

		/* Bank Info Styles */
		.bank-info {
			display: none;
		}

		.bank-info.active {
			display: block;
		}

		.copy-btn.copied {
			background: #16a34a;
		}

		.payment-method-item.active {
			border-color: #FF3F1A;
			background: #fff5f3;
		}

		/* Custom Scrollbar */
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
	</style>
	<link rel="icon" type="image/svg+xml" href="../img/icons/favicon.png">
</head>

<body class="font-sans antialiased bg-gray-50">

	<!-- Popup Overlay -->
	<div id="popup_overlay" class="popup-overlay"></div>

	<!-- ============ HEADER ============ -->
	<header id="header" class="sticky top-0 z-40 bg-white shadow-sm">
		<div class="header-wrapper">
			<div id="masthead" class="py-2 md:py-3 border-b">
				<div class="container mx-auto px-4 flex items-center justify-between">
					<!-- Mobile Menu Toggle -->
					<div class="md:hidden"><button class="menu-toggle p-2"><img src="../img/icons/menu.svg"
								class="fas fa-bars text-2xl"></button></div>
					<!-- Desktop Left Menu -->
					<div class="hidden md:flex items-center flex-1 ml-6">
						<ul class="flex items-center space-x-4">
							<li class="relative" id="mega-menu-container">
								<button id="mega-menu-trigger"
									class="button-menu flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200 transition">
									<img src="../img/icons/menu.svg" class="w-5 h-5 mr-2" alt="menu"><span>Danh
										mục</span>
								</button>
								<div id="mega-menu-dropdown"
									class="absolute left-0 top-full mt-2 w-[900px] bg-white rounded-lg shadow-xl hidden z-50">
									<div class="flex p-4">
										<div class="w-64 border-r border-gray-200 pr-4">
											<div class="icon-box-menu active bg-red-50 rounded-lg p-3 mb-1 cursor-pointer hover:bg-red-50 transition flex items-start"
												data-menu="badminton">
												<div class="w-8 h-8 flex-shrink-0 mr-3">
													<img src="https://nvbplay.vn/wp-content/uploads/2024/10/badminton-No.svg"
														alt="Cầu Lông" class="w-full h-full">
												</div>
												<div>
													<p class="font-bold text-red-600">Cầu Lông</p>
													<p class="text-xs text-gray-500">Trang bị cầu lông chuyên nghiệp</p>
												</div>
											</div>
											<div class="icon-box-menu p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start"
												data-menu="pickleball">
												<div class="w-8 h-8 flex-shrink-0 mr-3">
													<img src="https://nvbplay.vn/wp-content/uploads/2024/10/pickleball-No.svg"
														alt="Pickleball" class="w-full h-full">
												</div>
												<div>
													<p class="font-bold">Pickleball</p>
													<p class="text-xs text-gray-500">Trang bị pickleball hàng đầu</p>
												</div>
											</div>
											<div class="icon-box-menu p-3 mb-1 cursor-pointer hover:bg-gray-50 transition flex items-start"
												data-menu="giay">
												<div class="w-8 h-8 flex-shrink-0 mr-3">
													<img src="https://nvbplay.vn/wp-content/uploads/2024/10/jogging-No.svg"
														alt="Giày" class="w-full h-full">
												</div>
												<div>
													<p class="font-bold">Giày</p>
													<p class="text-xs text-gray-500">Giày thể thao tối ưu hoá vận động
													</p>
												</div>
											</div>
										</div>
										<div class="flex-1 pl-4">
											<div id="content-badminton" class="menu-content"></div>
											<div id="content-pickleball" class="menu-content hidden"></div>
											<div id="content-giay" class="menu-content hidden"></div>
										</div>
									</div>
								</div>
							</li>
							<li><a href="../view/shop.php"
									class="flex items-center text-gray-700 hover:text-red-600 font-medium"><img
										src="../img/icons/store.svg" class="w-5 h-5 flex-shrink-0 mr-2"><span>CỬA
										HÀNG</span></a></li>
						</ul>
					</div>
					<!-- Logo -->
					<div id="logo" class="flex-shrink-1 absolute left-1/2 transform -translate-x-1/2">
						<a href="../index.php" title="NVBPlay" rel="home">
							<img width="100" height="40" src="../img/icons/logonvb.png" alt="NVBPlay"
								class="h-12 md:h-14 w-auto transform scale-75">
						</a>
					</div>
					<!-- Desktop Right Elements -->
					<div class="hidden md:flex items-center space-x-4">
						<div class="address-book">
							<a href="./my-account/address-book.php"
								class="flex items-center text-gray-700 hover:text-red-600">
								<i class="fas fa-map-marker-alt mr-1"></i>
								<span class="shipping-address text-sm"><span class="text">Chọn địa chỉ</span></span>
							</a>
						</div>
						<div class="h-5 w-px bg-gray-300"></div>
						<div class="search-header relative">
							<button class="search-toggle p-2"><i
									class="fas fa-search text-gray-700 hover:text-red-600"></i></button>
						</div>
						<div class="user-dropdown relative">
							<button id="userToggle"
								class="flex items-center space-x-2 hover:bg-gray-100 px-3 py-2 rounded-lg transition">
								<img src="../img/icons/account.svg" class="w-6 h-6" alt="Account">
								<span class="text-sm font-medium text-gray-700">
									<?php echo htmlspecialchars($user_info['Username'] ?? $user_info['username'] ?? 'User'); ?></span>
								<i class="fas fa-chevron-down text-xs text-gray-500"></i>
							</button>
							<div id="userMenu" class="user-menu">
								<div class="px-4 py-3 border-b border-gray-100">
									<div class="flex items-center space-x-3">
										<img src="../img/icons/account.svg" class="w-10 h-10" alt="Account">
										<div>
											<p class="text-sm font-medium text-gray-800">
												<?php echo htmlspecialchars($user_info['Ho_ten'] ?? $user_info['Ho_ten'] ?? 'Khách hàng'); ?>
											</p>
											<p class="text-xs text-gray-500">
												<?php echo htmlspecialchars($user_info['email'] ?? 'Chưa cập nhật'); ?>
											</p>
										</div>
									</div>
								</div>
								<a href="./my-account.php" class="user-menu-item"><i class="fas fa-user"></i><span>Tài
										khoản của tôi</span></a>
								<a href="./my-account/orders.php" class="user-menu-item"><i
										class="fas fa-shopping-bag"></i><span>Đơn hàng</span></a>
								<a href="./my-account/address-book.php" class="user-menu-item"><i
										class="fas fa-map-marker-alt"></i><span>Sổ địa chỉ</span></a>
								<div class="user-menu-divider"></div>
								<a href="../control/logout.php" class="user-menu-item logout"><i
										class="fas fa-sign-out-alt"></i><span>Đăng xuất</span></a>
							</div>
						</div>
						<a href="./cart.php" class="relative p-2">
							<i class="fas fa-shopping-basket text-gray-700 hover:text-red-600 text-xl"></i>
							<span
								class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"><?php echo $total_items; ?></span>
						</a>
					</div>
					<!-- Mobile Right Elements -->
					<div class="md:hidden flex items-center space-x-3">
						<button class="search-toggle p-1"><i class="fas fa-search text-xl"></i></button>
						<a href="./my-account.php" class="p-1"><img src="../img/icons/account.svg" class="w-6 h-6"
								alt="Account"></a>
						<a href="./cart.php" class="relative p-1">
							<i class="fas fa-shopping-basket text-xl"></i>
							<span
								class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center"><?php echo $total_items; ?></span>
						</a>
					</div>
				</div>
			</div>
		</div>
	</header>
	<!-- ============ END HEADER ============ -->

	<!-- MAIN CONTENT -->
	<main class="container mx-auto px-4 py-6 md:py-10">
		<div class="max-w-5xl mx-auto">

			<!-- Alerts -->
			<?php if ($success): ?>
				<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm">
					<i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
				</div>
			<?php endif; ?>
			<?php if (!empty($errors)): ?>
				<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">
					<ul class="list-disc list-inside">
						<?php foreach ($errors as $error): ?>
							<li><?php echo htmlspecialchars($error); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<div class="flex flex-col lg:flex-row gap-6">

				<!-- Left Column: Checkout Form -->
				<div class="lg:w-2/3 space-y-5">

					<!-- Delivery Address Section (Only Home Delivery) -->
					<div class="bg-white rounded-xl shadow-sm p-5 border">
						<div class="flex items-center justify-between mb-4">
							<h3 class="text-lg font-semibold text-gray-900"> Thông tin giao hàng</h3>
							<button type="button" id="open_address_book"
								class="text-[#FF3F1A] text-sm font-medium hover:underline flex items-center gap-1">
								<i class="fas fa-book"></i> Chọn từ sổ địa chỉ
							</button>
						</div>

						<form method="POST" action="" id="checkout_form">
							<div class="space-y-4">
								<!-- Name -->

								<div>
									<label class="block text-sm font-medium text-gray-700 mb-1">Họ và tên <span
											class="text-red-500">*</span></label>
									<input type="text" name="fullname" id="fullname"
										value="<?php echo htmlspecialchars($default_address['Ten_nguoi_nhan'] ?? $user_info['Ho_ten'] ?? ''); ?>"
										class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF3F1A] outline-none"
										oninput="this.value=this.value.replace(/[0-9]/g,'')" maxlength="50" required>
									<span class="error-message" id="name_error">Vui lòng nhập họ tên hợp lệ</span>
								</div>

								<!-- Phone -->
								<div>
									<label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại <span
											class="text-red-500">*</span></label>
									<input type="tel" name="phone" id="phone"
										value="<?php echo htmlspecialchars($default_address['SDT_nhan'] ?? $user_info['SDT'] ?? ''); ?>"
										class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF3F1A] outline-none"
										oninput="this.value=this.value.replace(/[^0-9]/g,'').substring(0,10)"
										maxlength="10" required>
									<span class="error-message" id="phone_error">Số điện thoại phải bắt đầu bằng 0 và có
										10 số</span>
								</div>

								<!-- Address -->
								<div>
									<label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ chi tiết <span
											class="text-red-500">*</span></label>
									<input type="text" name="address" id="address"
										value="<?php echo htmlspecialchars($default_full_address); ?>"
										class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF3F1A] outline-none"
										placeholder="Số nhà, tên đường, phường/xã..." maxlength="255" required>
									<span class="error-message" id="address_error">Vui lòng nhập địa chỉ chi tiết (ít
										nhất 10 ký tự)</span>
								</div>

								<!-- Hidden: Selected Address ID (có thể rỗng nếu nhập thủ công) -->
								<input type="hidden" name="selected_address_id" id="selected_address_id"
									value="<?php echo $default_address['add_id'] ?? ''; ?>">

								<!-- Someone Else Receive -->
								<div class="pt-2">
									<label class="flex items-center gap-2 cursor-pointer">
										<input type="checkbox" id="nhan_hang_giup" name="someoneReceive" value="1"
											class="w-4 h-4 text-[#FF3F1A] rounded">
										<span class="text-gray-700"> Nhờ người khác nhận hàng</span>
									</label>
									<div id="additional_fields" class="grid grid-2 gap-4 mt-3 pt-3 border-t hidden">
										<div>
											<label class="block text-sm font-medium text-gray-700 mb-1">Tên người
												nhận</label>
											<input type="text" name="recipient_name" id="recipient_name"
												class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF3F1A] outline-none"
												oninput="this.value=this.value.replace(/[0-9]/g,'')" maxlength="50">
										</div>
										<div>
											<label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại
												người nhận</label>
											<input type="tel" name="recipient_phone" id="recipient_phone"
												class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#FF3F1A] outline-none"
												oninput="this.value=this.value.replace(/[^0-9]/g,'').substring(0,10)"
												maxlength="10">
										</div>
									</div>
								</div>
							</div>

							<!-- Payment Method -->
							<div class="pt-4 border-t">
								<h4 class="font-medium text-gray-900 mb-3"> Phương thức thanh toán</h4>
								<div class="space-y-3">
									<!-- COD -->
									<label
										class="payment-method-item flex items-center gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition active"
										data-payment="cod">
										<input type="radio" name="payment_method" value="cod" checked
											class="text-[#FF3F1A] payment-radio">
										<span class="flex-1 font-medium">COD - Thanh toán khi nhận hàng</span>
										<i class="fas fa-money-bill-wave text-gray-400"></i>
									</label>

									<!-- Online Payment -->
									<label
										class="payment-method-item flex items-center gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition"
										data-payment="appota">
										<input type="radio" name="payment_method" value="banking"
											class="text-[#FF3F1A] payment-radio">
										<span class="flex-1 font-medium">Thanh toán trực tuyến</span>
										<i class="fas fa-credit-card text-gray-400"></i>
									</label>

									<!-- Bank Transfer Info (Hidden by default) -->
									<div id="bankInfo" class="bank-info mt-2 ml-6 p-4 bg-gray-50 rounded-lg border">
										<h3 class="font-semibold text-[#FF3F1A] mb-3 text-lg"><i
												class="fas fa-university mr-2"></i>Thông tin chuyển khoản</h3>
										<div class="space-y-3">
											<div class="flex items-center justify-between">
												<div>
													<p class="text-sm text-gray-600">Ngân hàng</p>
													<p class="font-medium">Ngân hàng TMCP Ngoại thương Việt Nam
														(Vietcombank)</p>
												</div>
											</div>
											<div class="flex items-center justify-between">
												<div>
													<p class="text-sm text-gray-600">Tên tài khoản</p>
													<p class="font-medium">NVB PLAY</p>
												</div>
											</div>
											<div class="flex items-center justify-between">
												<div>
													<p class="text-sm text-gray-600">Số tài khoản</p>
													<p class="font-medium text-lg tracking-wider" id="accountNumber">
														1030506778</p>
												</div>
												<button type="button"
													class="copy-btn bg-[#FF3F1A] text-white px-4 py-2 rounded-lg text-sm hover:bg-red-600 transition flex items-center gap-2"
													onclick="copyAccountNumber()">
													<i class="fas fa-copy"></i>
													<span>Sao chép</span>
												</button>
											</div>
											<div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
												<p class="text-sm text-yellow-800 font-medium"><i
														class="fas fa-exclamation-triangle mr-2"></i>Lưu ý quan trọng:
												</p>
												<p class="text-sm text-yellow-700 mt-1">Vui lòng chuyển khoản chính xác
													số tiền <span
														class="font-bold"><?php echo formatPrice($order_total); ?></span>
													và ghi rõ nội dung chuyển khoản theo mẫu: <span
														class="font-bold bg-white px-2 py-1 rounded">[Mã đơn hàng] +
														[Tên người đặt]</span></p>
											</div>
										</div>
									</div>
								</div>
							</div>

							<!-- Terms -->
							<div class="pt-4 border-t">
								<label class="flex items-start gap-2">
									<input type="checkbox" id="terms" required class="mt-1 text-[#FF3F1A] rounded">
									<span class="text-sm text-gray-600">Tôi đã đọc và đồng ý với <a href="#"
											class="text-[#FF3F1A] hover:underline">điều khoản</a> và <a href="#"
											class="text-[#FF3F1A] hover:underline">chính sách bảo mật</a></span>
								</label>
								<input type="hidden" name="place_order" value="1">
							</div>
						</form>
					</div>

				</div>

				<!-- Right Column: Order Summary -->
				<div class="lg:w-1/3">
					<div class="bg-white rounded-xl shadow-sm p-5 sticky top-24 border">
						<h3 class="text-lg font-semibold text-gray-900 mb-4"> Đơn hàng của bạn</h3>

						<!-- Cart Items from Session -->
						<div class="space-y-3 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
							<?php if (!empty($cart_items)): ?>
								<?php foreach ($cart_items as $item): ?>
									<div class="flex gap-3">
										<img src="../<?php echo htmlspecialchars($item['image_url']); ?>"
											alt="<?php echo htmlspecialchars($item['TenSP']); ?>"
											class="w-14 h-14 object-cover rounded border flex-shrink-0">
										<div class="flex-1 min-w-0">
											<p class="text-sm font-medium text-gray-800 truncate">
												<?php echo htmlspecialchars($item['TenSP']); ?>
											</p>
											<p class="text-xs text-gray-500">SL: <?php echo $item['quantity']; ?></p>
											<p class="text-sm font-semibold text-[#FF3F1A]">
												<?php echo formatPrice($item['subtotal']); ?>
											</p>
										</div>
									</div>
								<?php endforeach; ?>
							<?php else: ?>
								<p class="text-center text-gray-500 py-4">Giỏ hàng trống</p>
							<?php endif; ?>
						</div>

						<!-- Totals -->
						<div class="border-t mt-4 pt-4 space-y-2">
							<div class="flex justify-between text-gray-600 text-sm">
								<span>Tạm tính (<?php echo $total_items; ?> sản phẩm)</span>
								<span><?php echo formatPrice($cart_total); ?></span>
							</div>

							<div class="flex justify-between font-bold text-lg text-[#FF3F1A] pt-2 border-t">
								<span>Tổng cộng</span>
								<span><?php echo formatPrice($order_total); ?></span>
							</div>
						</div>

						<!-- Place Order Button -->
						<button type="button" onclick="document.getElementById('checkout_form').requestSubmit()"
							id="place_order_btn"
							class="w-full mt-5 py-3 bg-[#FF3F1A] text-white font-semibold rounded-lg hover:bg-red-700 transition flex items-center justify-center gap-2">
							<i class="fas fa-check-circle"></i>
							Đặt hàng (<?php echo formatPrice($order_total); ?>)
						</button>
						<p class="text-center text-xs text-gray-500 mt-2">
							<i class="fas fa-lock mr-1"></i> Thanh toán an toàn
						</p>
					</div>
				</div>

			</div>
		</div>
	</main>

	<!-- Address Book Popup  -->
	<div id="address_book_popup" class="popup-overlay">
		<div class="popup-content">
			<div class="flex items-center justify-between p-4 border-b">
				<h3 class="font-semibold text-gray-900"> Sổ địa chỉ</h3>
				<button type="button" id="close_address_book" class="text-gray-500 hover:text-gray-700">
					<i class="fas fa-times text-xl"></i>
				</button>
			</div>
			<div class="p-4 max-h-[60vh] overflow-y-auto">
				<!-- Address List -->
				<div id="address_list_view">
					<?php
					$stmt = $conn->prepare("SELECT * FROM diachigh WHERE User_id = ? ORDER BY Mac_dinh DESC, add_id DESC");
					$stmt->bind_param("i", $user_id);
					$stmt->execute();
					$addresses = $stmt->get_result();
					if ($addresses->num_rows > 0):
						while ($addr = $addresses->fetch_assoc()):
							$full_address = array_filter([$addr['Duong'], $addr['Quan'], $addr['Tinh_thanhpho'], $addr['Dia_chi_chitiet']]);
							$full_address_str = implode(', ', $full_address);
							?>
							<label class="address-item block border border-gray-200 rounded-lg p-3 mb-2 cursor-pointer"
								data-id="<?php echo $addr['add_id']; ?>"
								data-name="<?php echo htmlspecialchars($addr['Ten_nguoi_nhan']); ?>"
								data-phone="<?php echo htmlspecialchars($addr['SDT_nhan']); ?>"
								data-address="<?php echo htmlspecialchars($full_address_str); ?>">
								<input type="radio" name="popup_address" class="sr-only address_book_input"
									value="<?php echo $addr['add_id']; ?>" <?php if ($addr['Mac_dinh'] == 1)
										   echo 'checked'; ?>>
								<div class="flex items-start gap-3">
									<div
										class="w-5 h-5 mt-0.5 border-2 border-gray-300 rounded-full flex items-center justify-center flex-shrink-0">
										<div class="w-2.5 h-2.5 bg-[#FF3F1A] rounded-full opacity-0 radio-indicator"></div>
									</div>
									<div class="flex-1 min-w-0">
										<div class="flex items-center gap-2 mb-1 flex-wrap">
											<span
												class="font-medium text-gray-900"><?php echo htmlspecialchars($addr['Ten_nguoi_nhan']); ?></span>
											<?php if ($addr['Mac_dinh'] == 1): ?>
												<span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded">Mặc định</span>
											<?php endif; ?>
										</div>
										<span class="text-sm text-gray-500">
											<?php echo htmlspecialchars($addr['SDT_nhan']); ?>
										</span>
										<p class="text-sm text-gray-700 mt-1 truncate">
											<?php echo htmlspecialchars($full_address_str); ?>
										</p>
									</div>
								</div>
							</label>
						<?php endwhile; else: ?>
						<p class="text-center text-gray-500 py-6">Chưa có địa chỉ nào. Vui lòng thêm địa chỉ mới.</p>
					<?php endif;
					$stmt->close(); ?>
					<a href="./my-account/address-book.php"
						class="block w-full py-3 border-2 border-dashed border-gray-300 rounded-lg text-[#FF3F1A] font-medium text-center mt-2 hover:border-[#FF3F1A] hover:bg-red-50 transition">
						Quản lý địa chỉ
					</a>
				</div>
			</div>
			<div class="p-4 border-t">
				<button type="button" id="popup_apply"
					class="w-full py-3 bg-[#FF3F1A] text-white rounded-lg font-medium hover:bg-red-700 transition">
					Áp dụng địa chỉ đã chọn
				</button>
			</div>
		</div>
	</div>

	<!-- ============ FOOTER ============ -->
	<footer id="footer" class="bg-black text-white mt-12">
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
						<li><a href="" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH THANH TOÁN</a></li>
						<li><a href="" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH BẢO HÀNH ĐỔI TRẢ</a>
						</li>
						<li><a href="" class="text-gray-400 hover:text-white transition">CHÍNH SÁCH VẬN CHUYỂN</a></li>
						<li><a href="" class="text-gray-400 hover:text-white transition">THOẢ THUẬN SỬ DỤNG</a></li>
					</ul>
				</div>
				<div>
					<h3 class="text-xl font-bold mb-4">Về chúng tôi</h3>
					<ul class="space-y-3">
						<li><a href="https://maps.app.goo.gl/mwqaes9hQJks8FSu5" target="_blank" class="flex"><span
									class="font-medium w-20">Địa chỉ:</span><span class="text-gray-400">62 Lê Bình, Tân
									An, Cần Thơ</span></a></li>
						<li>
							<div class="flex"><span class="font-medium w-20">Giờ làm việc:</span><span
									class="text-gray-400">08:00 - 21:00</span></div>
						</li>
						<li><a href="tel:0987.879.243" class="flex"><span class="font-medium w-20">Hotline:</span><span
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
	<!-- ============ END FOOTER ============ -->

	<script>
		document.addEventListener('DOMContentLoaded', function () {
			'use strict';

			// === DOM Elements ===
			const els = {
				openAddressBook: document.getElementById('open_address_book'),
				closeAddressBook: document.getElementById('close_address_book'),
				popupOverlay: document.getElementById('address_book_popup'),
				popupApply: document.getElementById('popup_apply'),
				checkoutForm: document.getElementById('checkout_form'),
				placeOrderBtn: document.getElementById('place_order_btn'),
				someoneReceive: document.getElementById('nhan_hang_giup'),
				additionalFields: document.getElementById('additional_fields'),
				paymentRadios: document.querySelectorAll('.payment-radio'),
				paymentItems: document.querySelectorAll('.payment-method-item'),
				bankInfo: document.getElementById('bankInfo'),
				fullname: document.getElementById('fullname'),
				phone: document.getElementById('phone'),
				address: document.getElementById('address'),
				selectedAddressId: document.getElementById('selected_address_id')
			};

			// === Toggle Someone Receive ===
			if (els.someoneReceive && els.additionalFields) {
				els.someoneReceive.addEventListener('change', function () {
					els.additionalFields.classList.toggle('hidden', !this.checked);
				});
			}

			// === Payment Method Toggle ===
			els.paymentRadios.forEach(radio => {
				radio.addEventListener('change', function () {
					els.paymentItems.forEach(item => {
						item.classList.remove('active');
						if (item.dataset.payment === this.value) item.classList.add('active');
					});
					if (els.bankInfo) {
						els.bankInfo.style.display = (this.value === 'appota') ? 'block' : 'none';
					}
				});
			});

			// === Popup Functions ===
			function openPopup() {
				if (!els.popupOverlay) return;
				els.popupOverlay.classList.add('active');
				document.body.style.overflow = 'hidden';
				updateRadioIndicators();
			}

			function closePopup() {
				if (!els.popupOverlay) return;
				els.popupOverlay.classList.remove('active');
				document.body.style.overflow = '';
			}

			function updateRadioIndicators() {
				document.querySelectorAll('.address_book_input').forEach(radio => {
					const indicator = radio.parentElement.querySelector('.radio-indicator');
					const label = radio.closest('.address-item');
					if (radio.checked) {
						if (indicator) indicator.style.opacity = '1';
						if (label) label.classList.add('selected');
					} else {
						if (indicator) indicator.style.opacity = '0';
						if (label) label.classList.remove('selected');
					}
				});
			}

			// === Open / Close Popup ===
			if (els.openAddressBook) {
				els.openAddressBook.addEventListener('click', function (e) {
					e.preventDefault();
					e.stopPropagation();
					openPopup();
				});
			}

			if (els.closeAddressBook) {
				els.closeAddressBook.addEventListener('click', closePopup);
			}

			if (els.popupOverlay) {
				els.popupOverlay.addEventListener('click', function (e) {
					if (e.target === els.popupOverlay) closePopup();
				});
			}

			// === Select Address Item ===
			document.querySelectorAll('.address-item').forEach(item => {
				item.addEventListener('click', function () {
					const radio = this.querySelector('.address_book_input');
					if (radio) {
						radio.checked = true;
						updateRadioIndicators();
					}
				});
			});

			// === Apply Selected Address ===
			if (els.popupApply) {
				els.popupApply.addEventListener('click', function () {
					const selected = document.querySelector('.address_book_input:checked');
					if (!selected) {
						alert('Vui lòng chọn một địa chỉ');
						return;
					}
					const label = selected.closest('.address-item');
					if (label) {
						if (els.fullname) els.fullname.value = label.dataset.name || '';
						if (els.phone) els.phone.value = label.dataset.phone || '';
						if (els.address) els.address.value = label.dataset.address || '';
						if (els.selectedAddressId) els.selectedAddressId.value = selected.value || '';
					}
					closePopup();
				});
			}

			// === Checkout Form Validation ===
			if (els.checkoutForm) {
				els.checkoutForm.addEventListener('submit', function (e) {
					document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
					document.querySelectorAll('input.error').forEach(el => el.classList.remove('error'));
					let valid = true;

					const name = els.fullname?.value.trim();
					if (!name || name.length < 2) {
						document.getElementById('name_error').style.display = 'block';
						els.fullname.classList.add('error');
						valid = false;
					}

					const phone = els.phone?.value.trim();
					if (!/^0[0-9]{9}$/.test(phone)) {
						document.getElementById('phone_error').style.display = 'block';
						els.phone.classList.add('error');
						valid = false;
					}

					const address = els.address?.value.trim();
					if (!address || address.length < 10) {
						document.getElementById('address_error').style.display = 'block';
						els.address.classList.add('error');
						valid = false;
					}

					const terms = document.getElementById('terms');
					if (terms && !terms.checked) {
						alert('Vui lòng đồng ý với điều khoản');
						valid = false;
					}

					if (!valid) {
						e.preventDefault();
						return false;
					}

					if (els.placeOrderBtn) {
						els.placeOrderBtn.classList.add('loading');
						els.placeOrderBtn.disabled = true;
					}
				});
			}

			// === ESC to Close ===
			document.addEventListener('keydown', function (e) {
				if (e.key === 'Escape') closePopup();
			});

			// === User Dropdown ===
			const userToggle = document.getElementById('userToggle');
			const userMenu = document.getElementById('userMenu');
			if (userToggle && userMenu) {
				userToggle.addEventListener('click', function (e) {
					e.stopPropagation();
					userMenu.classList.toggle('active');
				});
				document.addEventListener('click', function (e) {
					if (!userToggle.contains(e.target) && !userMenu.contains(e.target)) {
						userMenu.classList.remove('active');
					}
				});
			}
		});

		// === Copy Account Number (outside DOMContentLoaded vì gọi từ onclick HTML) ===
		function copyAccountNumber() {
			const accountNumber = document.getElementById('accountNumber');
			if (!accountNumber) return;
			navigator.clipboard.writeText(accountNumber.textContent).then(() => {
				const btn = document.querySelector('.copy-btn');
				if (!btn) return;
				const orig = btn.innerHTML;
				btn.classList.add('copied');
				btn.innerHTML = '<i class="fas fa-check"></i><span>Đã sao chép</span>';
				setTimeout(() => {
					btn.classList.remove('copied');
					btn.innerHTML = orig;
				}, 2000);
			});
		}
	</script>
</body>

</html>