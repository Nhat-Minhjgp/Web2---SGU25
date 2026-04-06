<?php
session_start();
require_once __DIR__ . '/../control/connect.php';
require_once __DIR__ . '/../control/function.php';

// Enable mysqli error reporting để try-catch hoạt động đúng
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

$admin_username = $_SESSION['admin_username'] ?? '';
$admin_id = $_SESSION['admin_id'] ?? 1;

// ========== CSRF Token ==========
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function validateCSRF($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ========== HÀM KIỂM TRA SQL INJECTION ==========
function hasSQLInjection($value)
{
    if (!is_string($value))
        return false;
    $patterns = [
        '/\b(SELECT|INSERT|UPDATE|DELETE|DROP|TRUNCATE|ALTER|CREATE|EXEC|EXECUTE|UNION)\b/i',
        '/(--|\/\*|\*\/|#)/',
        '/\bOR\b\s+[\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+|\bAND\b\s+[\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+/i',
        '/\bxp_\w+|sp_\w+/i',
        '/\b(WAITFOR|BENCHMARK|SLEEP)\b/i',
        '/%00|%27|%22/i',
        '/;/'
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $value))
            return true;
    }
    return false;
}


// ========== XỬ LÝ CẬP NHẬT TRẠNG THÁI (POST METHOD) ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    // Validate CSRF
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Lỗi bảo mật: Token không hợp lệ!";
        header('Location: orders.php');
        exit();
    }

    $order_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $new_status = filter_input(INPUT_POST, 'status', FILTER_VALIDATE_INT);

    if (!$order_id || $new_status === null || $new_status < 0 || $new_status > 3) {
        $_SESSION['error'] = "Dữ liệu không hợp lệ!";
        header('Location: orders.php');
        exit();
    }

    try {
        $conn->begin_transaction();

        // Lấy trạng thái hiện tại
        $stmt = $conn->prepare("SELECT TrangThai FROM donhang WHERE DonHang_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current = $result->fetch_assoc();

        if (!$current) {
            throw new Exception("Không tìm thấy đơn hàng!");
        }

        $current_status = intval($current['TrangThai']);

        // Kiểm tra đơn hàng đã khóa
        if ($current_status == 2 || $current_status == 3) {
            throw new Exception("Đơn hàng đã hoàn tất, không thể thay đổi trạng thái!");
        }

        // Valid transitions
        $valid_transitions = [0 => [1, 3], 1 => [2, 3]];
        if (!isset($valid_transitions[$current_status]) || !in_array($new_status, $valid_transitions[$current_status])) {
            throw new Exception("Không thể chuyển trạng thái này!");
        }

        // ✅CHỈ CẬP NHẬT TRẠNG THÁI - TRIGGER SẼ TỰ XỬ LÝ PHẦN CÒN LẠI
        $update = $conn->prepare("UPDATE donhang SET TrangThai = ?, NgayCapNhat = NOW() WHERE DonHang_id = ?");
        $update->bind_param("ii", $new_status, $order_id);
        $update->execute();

        // Message tùy theo hành động
        $messages = [
            1 => "✅ Đã xác nhận đơn hàng (trigger sẽ tạo phiếu xuất & trừ tồn kho)",
            2 => "✅ Đã cập nhật đơn hàng thành Đã giao",
            3 => "✅ Đã hủy đơn hàng (trigger sẽ hoàn lại tồn kho)"
        ];
        $_SESSION['message'] = $messages[$new_status] ?? "✅ Cập nhật thành công!";

        $conn->commit();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }

    header('Location: orders.php');
    exit();
}

// ========== AJAX: LẤY CHI TIẾT ĐƠN HÀNG ==========
if (isset($_GET['get_detail']) && isset($_GET['id'])) {
    header('Content-Type: application/json');

    $order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$order_id || $order_id <= 0) {
        echo json_encode(['error' => 'ID không hợp lệ']);
        exit();
    }

    try {
        $sql_detail = "SELECT c.*, s.TenSP, s.image_url, s.Gia 
                       FROM chitiethoadon c
                       LEFT JOIN sanpham s ON c.SanPham_id = s.SanPham_id
                       WHERE c.DonHang_id = ?";
        $stmt = $conn->prepare($sql_detail);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode($details);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Lỗi server: ' . $e->getMessage()]);
    }
    exit();
}

// ========== LẤY DANH SÁCH ĐƠN HÀNG ==========
try {
    $sql = "SELECT d.*, u.Ho_ten as customer_name, u.SDT as customer_phone,
            dc.Ten_nguoi_nhan, dc.SDT_nhan, dc.Quan, dc.Tinh_thanhpho, dc.Duong, dc.Dia_chi_chitiet
            FROM donhang d
            LEFT JOIN users u ON d.User_id = u.User_id
            LEFT JOIN diachigh dc ON d.DiaChi_id = dc.add_id
            ORDER BY d.NgayDat DESC";
    $result = $conn->query($sql);
    $orders = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
} catch (Exception $e) {
    $orders = [];
    $_SESSION['error'] = "Lỗi tải danh sách đơn hàng: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng - Admin</title>
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
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeIn {
            animation: fadeIn 0.3s ease-out;
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal.show {
            display: flex;
        }

        .track-modal-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 900px;
            max-height: 85vh;
            overflow-y: auto;
            animation: fadeIn 0.3s ease-out;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-0 {
            background: #fef3c7;
            color: #92400e;
        }

        .status-1 {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-2 {
            background: #d1fae5;
            color: #065f46;
        }

        .status-3 {
            background: #fee2e2;
            color: #991b1b;
        }

        .action-btn {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }

        .btn-confirm {
            background: #3b82f6;
            color: white;
        }

        .btn-deliver {
            background: #10b981;
            color: white;
        }

        .btn-cancel {
            background: #ef4444;
            color: white;
        }

        .btn-disabled {
            background: #9ca3af;
            color: white;
            cursor: not-allowed;
        }
    </style>
    <link rel="icon" type="image/svg+xml" href="../img/icons/favicon.png" sizes="32x32">
</head>

<body class="bg-gray-50 font-sans text-gray-800">

    <!-- HEADER -->
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
                    class="bg-gradient-custom text-white font-semibold px-4 py-2 rounded-lg hover:opacity-90 transition shadow-md">
                    <i class="fas fa-sign-out-alt mr-2"></i>Đăng xuất
                </button>
            </div>
        </div>
    </header>

    <div class="flex w-full min-h-[calc(100vh-70px)]">
        <!-- SIDEBAR -->
        <aside class="w-64 bg-white shadow-lg hidden lg:block flex-shrink-0 border-r border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Danh mục chức năng</h3>
            </div>
            <nav class="p-4 space-y-2">
                <a href="dashboard.php"
                    class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i
                        class="fas fa-home w-5"></i> Dashboard</a>
                <a href="users.php"
                    class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i
                        class="fas fa-users w-5"></i> Quản lý người dùng</a>
                <a href="categories.php"
                    class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i
                        class="fas fa-list w-5 text-center"></i> Quản lý danh mục</a>
                <a href="product.php"
                    class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i
                        class="fas fa-box w-5"></i> Quản lý sản phẩm</a>
                <a href="import.php"
                    class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i
                        class="fas fa-arrow-down w-5"></i> Quản lý nhập hàng</a>
                <a href="price.php"
                    class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i
                        class="fas fa-tag w-5"></i> Quản lý giá bán</a>
                <a href="orders.php"
                    class="flex items-center gap-3 px-4 py-3 bg-gradient-custom text-white rounded-lg shadow-md"><i
                        class="fas fa-receipt w-5"></i> Quản lý đơn hàng</a>
                <a href="inventory.php"
                    class="flex items-center gap-3 px-4 py-3 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-primary transition"><i
                        class="fas fa-warehouse w-5"></i> Tồn kho & Báo cáo</a>
            </nav>
        </aside>

        <main class="flex-1 p-6 lg:p-8 overflow-x-hidden bg-gray-50">
            <div class="bg-white rounded-xl shadow-lg p-6 lg:p-8 min-h-full">
                <div class="flex justify-between items-center mb-6 pb-4 border-b">
                    <h2 class="text-2xl font-bold text-gray-800"><i class="fas fa-receipt text-primary mr-2"></i>Quản lý
                        đơn hàng</h2>
                </div>

                <!-- Messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">
                        <i
                            class="fas fa-check-circle mr-2"></i><?php echo $_SESSION['message'];
                            unset($_SESSION['message']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
                        <i
                            class="fas fa-exclamation-circle mr-2"></i><?php echo $_SESSION['error'];
                            unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="flex flex-wrap gap-4 mb-6 items-end">
                    <input type="text" id="searchOrder" placeholder="🔍 Tìm theo ID, tên khách, SĐT..."
                        class="px-4 py-2 border border-gray-300 rounded-lg w-full sm:w-auto flex-grow min-w-[200px]">
                    <div class="flex gap-2 flex-grow min-w-[100px]">
                        <input type="date" id="fromDate" class="px-2 py-2 border border-gray-300 rounded-lg flex-1">
                        <span class="self-center">–</span>
                        <input type="date" id="toDate" class="px-2 py-2 border border-gray-300 rounded-lg flex-1">
                    </div>
                    <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg w-full sm:w-auto">
                        <option value="">Tất cả trạng thái</option>
                        <option value="0">Chờ xử lý</option>
                        <option value="1">Đã xác nhận</option>
                        <option value="2">Đã giao</option>
                        <option value="3">Đã hủy</option>
                    </select>
                    <select id="sortWard" class="px-4 py-2 border border-gray-300 rounded-lg w-full sm:w-auto">
                        <option value="">Sắp xếp theo phường</option>
                        <option value="asc">A → Z</option>
                        <option value="desc">Z → A</option>
                    </select>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto border border-gray-200 rounded-xl">
                    <table class="w-full min-w-[1000px]" id="ordersTable">
                        <thead class="bg-gradient-custom text-white">
                            <tr>
                                <th class="px-4 py-3 text-left">ID</th>
                                <th class="px-4 py-3 text-left">Mã đơn</th>
                                <th class="px-4 py-3 text-left">Khách hàng</th>
                                <th class="px-4 py-3 text-left">SĐT</th>
                                <th class="px-4 py-3 text-left">Phường</th>
                                <th class="px-4 py-3 text-right">Tổng tiền</th>
                                <th class="px-4 py-3 text-left">Ngày đặt</th>
                                <th class="px-4 py-3 text-left">Trạng thái</th>
                                <th class="px-4 py-3 text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="ordersTableBody">
                            <?php foreach ($orders as $order):
                                $status = intval($order['TrangThai']);
                                $isLocked = ($status == 2 || $status == 3);
                                $order_code = str_pad($order['DonHang_id'], 6, '0', STR_PAD_LEFT);
                                ?>
                                <tr class="hover:bg-gray-50 transition"
                                    data-ward="<?php echo htmlspecialchars($order['Quan'] ?? ''); ?>">
                                    <td class="px-4 py-3"><?php echo $order['DonHang_id']; ?></td>
                                    <td class="px-4 py-3 font-mono">#<?php echo $order_code; ?></td>
                                    <td class="px-4 py-3">
                                        <?php echo htmlspecialchars($order['customer_name'] ?? $order['Ten_nguoi_nhan'] ?? 'Khách lẻ'); ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php echo htmlspecialchars($order['customer_phone'] ?? $order['SDT_nhan'] ?? ''); ?>
                                    </td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($order['Quan'] ?? 'Chưa có'); ?></td>
                                    <td class="px-4 py-3 text-right font-semibold text-indigo-600">
                                        <?php echo number_format($order['TongTien'], 0, ',', '.'); ?>đ</td>
                                    <td class="px-4 py-3"><?php echo date('d/m/Y H:i', strtotime($order['NgayDat'])); ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="status-badge status-<?php echo $status; ?>">
                                            <?php echo $status == 0 ? '⏳ Chờ xử lý' : ($status == 1 ? '✓ Đã xác nhận' : ($status == 2 ? '✅ Đã giao' : '✗ Đã hủy')); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button onclick="viewOrderDetail(<?php echo $order['DonHang_id']; ?>)"
                                                class="text-blue-500 hover:text-blue-700 p-1" title="Xem chi tiết"><i
                                                    class="fas fa-eye"></i></button>

                                            <?php if ($isLocked): ?>
                                                <span class="action-btn btn-disabled"><i class="fas fa-lock"></i> Đã khóa</span>
                                            <?php elseif ($status == 0): ?>
                                                <form method="POST" style="display:inline;"
                                                    onsubmit="return confirm('Xác nhận đơn hàng? Hành động này sẽ tạo phiếu xuất và trừ tồn kho!')">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="id" value="<?php echo $order['DonHang_id']; ?>">
                                                    <input type="hidden" name="status" value="1">
                                                    <input type="hidden" name="csrf_token"
                                                        value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <button type="submit" class="action-btn btn-confirm"><i
                                                            class="fas fa-check"></i> Xác nhận</button>
                                                </form>
                                                <form method="POST" style="display:inline;"
                                                    onsubmit="return confirm('Hủy đơn hàng?')">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="id" value="<?php echo $order['DonHang_id']; ?>">
                                                    <input type="hidden" name="status" value="3">
                                                    <input type="hidden" name="csrf_token"
                                                        value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <button type="submit" class="action-btn btn-cancel"><i
                                                            class="fas fa-times"></i> Hủy</button>
                                                </form>
                                            <?php elseif ($status == 1): ?>
                                                <form method="POST" style="display:inline;"
                                                    onsubmit="return confirm('Xác nhận đã giao hàng?')">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="id" value="<?php echo $order['DonHang_id']; ?>">
                                                    <input type="hidden" name="status" value="2">
                                                    <input type="hidden" name="csrf_token"
                                                        value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <button type="submit" class="action-btn btn-deliver"><i
                                                            class="fas fa-check-double"></i> Đã giao</button>
                                                </form>
                                                <form method="POST" style="display:inline;"
                                                    onsubmit="return confirm('Hủy đơn hàng?')">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="id" value="<?php echo $order['DonHang_id']; ?>">
                                                    <input type="hidden" name="status" value="3">
                                                    <input type="hidden" name="csrf_token"
                                                        value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <button type="submit" class="action-btn btn-cancel"><i
                                                            class="fas fa-times"></i> Hủy</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal chi tiết đơn hàng -->
    <div id="trackModal" class="modal">
        <div class="track-modal-content">
            <div class="bg-gradient-custom text-white px-6 py-4 rounded-t-xl flex justify-between">
                <h3 class="text-xl font-semibold"><i class="fas fa-truck mr-2"></i>Chi tiết đơn hàng</h3>
                <button onclick="closeModal()" class="text-white text-2xl">&times;</button>
            </div>
            <div class="p-6" id="trackModalContent">
                <div class="text-center py-12"><i class="fas fa-spinner fa-spin text-3xl"></i>
                    <p>Đang tải...</p>
                </div>
            </div>
            <div class="px-6 py-4 border-t flex justify-end">
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-500 text-white rounded-lg">Đóng</button>
            </div>
        </div>
    </div>

    <script>
        // Validate search input for SQL injection patterns (client-side warning only)
        const sqlPatterns = [/\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION)\b/i, /(--|\/\*|\*\/)/, /['";]/];
        document.getElementById('searchOrder')?.addEventListener('input', function () {
            const val = this.value;
            const hasRisk = sqlPatterns.some(p => p.test(val));
            this.classList.toggle('border-red-500', hasRisk);
            let warn = document.getElementById('searchWarning');
            if (hasRisk && !warn) {
                warn = document.createElement('div');
                warn.id = 'searchWarning';
                warn.className = 'text-red-500 text-xs mt-1';
                warn.innerHTML = '<i class="fas fa-shield-alt"></i> Ký tự không an toàn!';
                this.parentNode.appendChild(warn);
            } else if (!hasRisk && warn) warn.remove();
        });

        function viewOrderDetail(orderId) {
            const modal = document.getElementById('trackModal');
            const content = document.getElementById('trackModalContent');
            modal.classList.add('show');
            content.innerHTML = '<div class="text-center py-12"><i class="fas fa-spinner fa-spin text-3xl"></i><p>Đang tải...</p></div>';

            fetch(`?get_detail=1&id=${orderId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    if (data.length > 0) {
                        let html = '<h4 class="font-semibold mb-3">Sản phẩm đã đặt</h4><table class="w-full text-sm"><thead class="bg-gray-100"><tr><th class="p-2 text-left">Sản phẩm</th><th class="p-2 text-right">SL</th><th class="p-2 text-right">Đơn giá</th><th class="p-2 text-right">Thành tiền</th></tr></thead><tbody>';
                        let total = 0;
                        data.forEach(item => {
                            const sub = (item.SoLuong || 0) * (item.Gia || 0);
                            total += sub;
                            html += `<tr><td class="p-2"><div class="flex gap-2 items-center">
                            ${item.image_url ? `<img src="../${item.image_url}" class="w-10 h-10 object-cover rounded">` : '<div class="w-10 h-10 bg-gray-100 rounded flex items-center justify-center"><i class="fas fa-image text-gray-400"></i></div>'}
                            <span>${item.TenSP || 'N/A'}</span>
                        </div></td>
                        <td class="p-2 text-right">${item.SoLuong || 0}</td>
                        <td class="p-2 text-right">${new Intl.NumberFormat('vi-VN').format(item.Gia || 0)}đ</td>
                        <td class="p-2 text-right">${new Intl.NumberFormat('vi-VN').format(sub)}đ</td></tr>`;
                        });
                        html += `<tr class="bg-gray-50 font-semibold"><td colspan="3" class="p-2 text-right">Tổng cộng:</td><td class="p-2 text-right text-indigo-600">${new Intl.NumberFormat('vi-VN').format(total)}đ</td></tr></tbody></table>`;
                        content.innerHTML = html;
                    } else content.innerHTML = '<div class="text-center py-12 text-gray-500">Không có chi tiết</div>';
                })
                .catch(err => {
                    content.innerHTML = `<div class="text-center py-12 text-red-500">Lỗi: ${err.message}</div>`;
                });
        }

        function closeModal() { document.getElementById('trackModal').classList.remove('show'); }
        window.onclick = e => { if (e.target.classList.contains('modal')) closeModal(); };

        // Filters
        function filterByDate() {
            const f = document.getElementById('fromDate').value;
            const t = document.getElementById('toDate').value;
            document.querySelectorAll('#ordersTableBody tr').forEach(r => {
                const dateText = r.cells[6]?.textContent?.split(' ')[0];
                if (!dateText) return;
                const [d, m, y] = dateText.split('/');
                const rowDate = new Date(y, m - 1, d);
                const show = (!f || rowDate >= new Date(f)) && (!t || rowDate <= new Date(t));
                r.style.display = show ? '' : 'none';
            });
        }
        function filterByStatus() {
            const s = document.getElementById('statusFilter').value;
            document.querySelectorAll('#ordersTableBody tr').forEach(r => {
                const badge = r.cells[7]?.querySelector('.status-badge');
                const statusMap = { 'Chờ xử lý': '0', 'Đã xác nhận': '1', 'Đã giao': '2', 'Đã hủy': '3' };
                const val = Object.keys(statusMap).find(k => badge?.textContent.includes(k)) || '';
                r.style.display = (s === '' || val === s) ? '' : 'none';
            });
        }
        function sortByWard() {
            const order = document.getElementById('sortWard').value;
            if (!order) return;
            const tbody = document.getElementById('ordersTableBody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            rows.sort((a, b) => {
                const wa = a.getAttribute('data-ward') || '', wb = b.getAttribute('data-ward') || '';
                return order === 'asc' ? wa.localeCompare(wb, 'vi') : wb.localeCompare(wa, 'vi');
            });
            rows.forEach(r => tbody.appendChild(r));
        }
        document.getElementById('searchOrder')?.addEventListener('keyup', function () {
            const v = this.value.toLowerCase();
            document.querySelectorAll('#ordersTableBody tr').forEach(r => {
                const text = [r.cells[0], r.cells[2], r.cells[3]].map(c => c?.textContent.toLowerCase() || '').join(' ');
                r.style.display = text.includes(v) ? '' : 'none';
            });
        });
        document.getElementById('fromDate')?.addEventListener('change', filterByDate);
        document.getElementById('toDate')?.addEventListener('change', filterByDate);
        document.getElementById('statusFilter')?.addEventListener('change', filterByStatus);
        document.getElementById('sortWard')?.addEventListener('change', sortByWard);

        function logout() { if (confirm('Đăng xuất?')) window.location.href = 'logout.php'; }
    </script>
</body>

</html>