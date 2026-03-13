<?php
// 1. Kết nối DB
$host = 'localhost'; $db = 'b01_nhahodau'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("Lỗi: " . $e->getMessage()); }

// 2. Lấy danh sách sản phẩm để dùng cho JS (nếu cần)
$products = $pdo->query("SELECT SanPham_id, TenSP FROM sanpham")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Quản lý Nhập Hàng | MASH</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 250px; --primary-color: #2c3e50; --accent-color: #3498db; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background: #f4f7f6; display: flex; }
        
        /* Sidebar Design */
        .sidebar { width: var(--sidebar-width); background: var(--primary-color); color: white; height: 100vh; position: fixed; }
        .sidebar-header { padding: 20px; text-align: center; background: #1a252f; font-size: 24px; font-weight: bold; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; }
        .sidebar-menu li { padding: 15px 20px; border-bottom: 1px solid #34495e; cursor: pointer; transition: 0.3s; }
        .sidebar-menu li:hover { background: var(--accent-color); }
        .sidebar-menu li i { margin-right: 10px; }
        .sidebar-menu li.active { background: var(--accent-color); border-left: 5px solid #fff; }

        /* Main Content Area */
        .main-content { margin-left: var(--sidebar-width); flex: 1; padding: 30px; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }

        /* Form styling */
        .form-header { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .input-group { display: flex; flex-direction: column; }
        .input-group label { font-weight: bold; margin-bottom: 5px; color: #666; }
        input, select { padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        
        /* Table styling */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f8f9fa; color: #333; padding: 12px; border: 1px solid #dee2e6; }
        td { padding: 10px; border: 1px solid #dee2e6; vertical-align: middle; }
        
        /* Search results popup */
        .search-results { position: absolute; background: white; border: 1px solid #ddd; width: 100%; max-height: 200px; overflow-y: auto; z-index: 1000; box-shadow: 0 4px 8px rgba(0,0,0,0.1); display: none; }
        .search-results div { padding: 10px; cursor: pointer; }
        .search-results div:hover { background: #f1f1f1; }

        /* Buttons */
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; transition: 0.2s; }
        .btn-add { background: #27ae60; color: white; }
        .btn-add:hover { background: #219150; }
        .btn-save { background: #3498db; color: white; width: 100%; font-size: 16px; margin-top: 20px; }
        .btn-save:hover { background: #2980b9; }
        .btn-del { background: #e74c3c; color: white; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">NVB ADMIN</div>
    <ul class="sidebar-menu">
        <li><i class="fas fa-home"></i> Tổng quan</li>
        <li><i class="fas fa-box"></i> Sản phẩm</li>
        <li class="active"><i class="fas fa-file-import"></i> Tạo Phiếu Nhập</li>
        <li><i class="fas fa-history"></i> Lịch sử nhập</li>
        <li><i class="fas fa-users"></i> Khách hàng</li>
    </ul>
</div>

<div class="main-content">
    <div class="card">
        <h2><i class="fas fa-plus-circle"></i> Tạo Phiếu Nhập Hàng Mới</h2>
        
        <form action="xuly_nhap.php" method="POST">
            <div class="form-header">
                <div class="input-group">
                    <label>Người thực hiện:</label>
                    <input type="text" name="nguoi_nhap" value="Admin" required>
                </div>
                <div class="input-group">
                    <label>Ngày nhập kho:</label>
                    <input type="date" name="ngay_nhap" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>

            <div style="background: #eef2f7; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <label><b>Nhập dựa trên phiếu cũ:</b></label>
                <select id="copy_from_phieu" onchange="copyPhieuData(this.value)" style="width: 100%; margin-top:5px;">
                    <option value="">-- Chọn mã phiếu cũ để điền nhanh dữ liệu --</option>
                    <?php 
                    $old_phieus = $pdo->query("SELECT NhapHang_id, NgayNhap FROM phieunhap ORDER BY NhapHang_id DESC LIMIT 10")->fetchAll();
                    foreach($old_phieus as $op): ?>
                        <option value="<?= $op['NhapHang_id'] ?>">Phiếu #<?= $op['NhapHang_id'] ?> (Ngày: <?= $op['NgayNhap'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <h3>Chi tiết mặt hàng</h3>
            <table id="productTable">
                <thead>
                    <tr>
                        <th width="35%">Sản phẩm</th>
                        <th width="15%">Số lượng</th>
                        <th width="20%">Giá nhập (VNĐ)</th>
                        <th width="20%">Mã lô hàng</th>
                        <th width="10%"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div style="position: relative;">
                                <input type="text" class="search-input" placeholder="Tìm tên bánh..." onkeyup="liveSearch(this)" autocomplete="off" style="width: 93%;">
                                <input type="hidden" name="sp_id[]" class="sp-id-hidden">
                                <div class="search-results"></div>
                            </div>
                        </td>
                        <td><input type="number" name="sl[]" min="1" required style="width: 80%;"></td>
                        <td><input type="number" name="gia[]" min="0" required style="width: 85%;"></td>
                        <td><input type="text" name="lo[]" value="LO-<?php echo date('Ymd'); ?>" readonly style="background: #f4f4f4; width: 90%; text-align: center;"></td>
                        <td><button type="button" class="btn btn-del" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
                    </tr>
                </tbody>
            </table>

            <button type="button" class="btn btn-add" onclick="addRow()" style="margin-top: 15px;">
                <i class="fas fa-plus"></i> Thêm sản phẩm
            </button>
            
            <button type="submit" name="btn_save" class="btn btn-save">
                <i class="fas fa-save"></i> LƯU PHIẾU NHẬP VÀO HỆ THỐNG
            </button>
        </form>
    </div>
</div>

<script>
// JS của Ấn - Đã được tối ưu đồng bộ với Layout mới
function generateBatchCode() {
    let dateInput = document.querySelector('input[name="ngay_nhap"]').value;
    return "LO-" + dateInput.replace(/-/g, ""); 
}

function addRow() {
    var table = document.getElementById("productTable").getElementsByTagName('tbody')[0];
    var row = table.insertRow(-1);
    row.innerHTML = table.rows[0].innerHTML;
    var inputs = row.getElementsByTagName('input');
    let autoBatch = generateBatchCode();
    for (var i = 0; i < inputs.length; i++) {
        if(inputs[i].name === "lo[]") inputs[i].value = autoBatch;
        else if(inputs[i].type === 'hidden') inputs[i].value = "";
        else inputs[i].value = "";
    }
    row.querySelector('.search-results').style.display = 'none';
}

function removeRow(btn) {
    var table = document.getElementById("productTable").getElementsByTagName('tbody')[0];
    if(table.rows.length > 1) btn.closest('tr').remove();
}

function liveSearch(input) {
    let keyword = input.value;
    let resultDiv = input.parentElement.querySelector('.search-results');
    let idHidden = input.parentElement.querySelector('.sp-id-hidden');
    if (keyword.length < 1) { resultDiv.style.display = 'none'; return; }

    fetch(`ajax_tim_sanpham.php?key=${keyword}`)
        .then(response => response.json())
        .then(data => {
            let html = '';
            data.forEach(item => {
                html += `<div onclick="selectProduct(this, ${item.SanPham_id}, '${item.TenSP}')">${item.TenSP}</div>`;
            });
            resultDiv.innerHTML = html || '<div style="color:red">Không tìm thấy</div>';
            resultDiv.style.display = 'block';
        });
}

function selectProduct(element, id, name) {
    let parent = element.parentElement.parentElement;
    parent.querySelector('.search-input').value = name;
    parent.querySelector('.sp-id-hidden').value = id;
    element.parentElement.style.display = 'none';
}

function copyPhieuData(phieuId) {
    if (!phieuId) return;
    if (!confirm("Copy dữ liệu từ phiếu #" + phieuId + "?")) return;
    fetch(`get_phieu_detail.php?id=${phieuId}`)
        .then(response => response.json())
        .then(data => {
            let tableBody = document.querySelector("#productTable tbody");
            tableBody.innerHTML = "";
            let autoBatch = generateBatchCode();
            data.forEach(item => {
                let newRow = `<tr>
                    <td><div style="position:relative;"><input type="text" class="search-input" value="${item.TenSP}" onkeyup="liveSearch(this)" style="width:93%;"><input type="hidden" name="sp_id[]" value="${item.SanPham_id}"><div class="search-results"></div></div></td>
                    <td><input type="number" name="sl[]" value="${item.SoLuong}" style="width:80%;"></td>
                    <td><input type="number" name="gia[]" value="${item.Gia_Nhap}" style="width:85%;"></td>
                    <td><input type="text" name="lo[]" value="${autoBatch}" readonly style="background:#f4f4f4; width:90%; text-align:center;"></td>
                    <td><button type="button" class="btn btn-del" onclick="removeRow(this)"><i class="fas fa-trash"></i></button></td>
                </tr>`;
                tableBody.insertAdjacentHTML('beforeend', newRow);
            });
        });
}

document.addEventListener("click", e => {
    if (!e.target.classList.contains("search-input")) 
        document.querySelectorAll(".search-results").forEach(d => d.style.display = "none");
});
</script>

</body>
</html>