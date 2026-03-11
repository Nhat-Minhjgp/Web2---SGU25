<?php
// Kết nối DB
$host = 'localhost'; $db = 'b01_nhahodau'; $user = 'root'; $pass = '';
$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);

// Lấy danh sách sản phẩm để người dùng chọn
$products = $pdo->query("SELECT SanPham_id, TenSP, GiaBan FROM sanpham")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tạo Phiếu Nhập</title>
    <style>
        .product-row { border-bottom: 1px solid #ccc; padding: 10px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    </style>
</head>
<body>
    <h2>Tạo Phiếu Nhập Hàng</h2>
    <form action="xuly_nhap.php" method="POST">
        <label>Người nhập:</label>
        <input type="text" name="nguoi_nhap" required>
        <label>Ngày nhập:</label>
        <input type="date" name="ngay_nhap" value="<?php echo date('Y-m-d'); ?>" required>
        <div style="margin-bottom: 20px; background: #eef2f7; padding: 15px; border-radius: 5px; border: 1px solid #d1d9e6;">
    <label><b>Nhập dựa trên phiếu cũ (nếu có):</b></label>
    <select id="copy_from_phieu" onchange="copyPhieuData(this.value)" style="padding: 5px;">
        <option value="">-- Chọn mã phiếu để lấy dữ liệu --</option>
        <?php 
        // Lấy danh sách các phiếu đã nhập trước đây
        $old_phieus = $pdo->query("SELECT NhapHang_id, NgayNhap FROM phieunhap ORDER BY NhapHang_id DESC LIMIT 20")->fetchAll();
        foreach($old_phieus as $op): ?>
            <option value="<?= $op['NhapHang_id'] ?>">Phiếu số: <?= $op['NhapHang_id'] ?> (Ngày: <?= $op['NgayNhap'] ?>)</option>
        <?php endforeach; ?>
    </select>
    <small style="display:block; color: gray;">* Chọn phiếu cũ để hệ thống tự điền danh sách sản phẩm nhanh.</small>
</div>
        <h3>Danh sách sản phẩm nhập</h3>
        <table id="productTable">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Số lượng</th>
                    <th>Giá nhập</th>
                    <th>Mã lô hàng</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                    <div style="position: relative;">
        <input type="text" 
               class="search-input" 
               placeholder="Gõ tên sản phẩm..." 
               onkeyup="liveSearch(this)" 
               autocomplete="off" 
               style="width: 100%; padding: 5px;">
        
        <input type="hidden" name="sp_id[]" class="sp-id-hidden">
        
        <div class="search-results" style="position: absolute; background: white; border: 1px solid #ccc; width: 100%; display: none; z-index: 1000; text-align: left;"></div>
    </div>
                    </td>
                    <td><input type="number" name="sl[]" min="1" required></td>
                    <td><input type="number" name="gia[]" min="0"   required style="width: 120px;"></td>
                    <td><input type="text" 
           name="lo[]" 
           id="ma_lo_macdinh"
           value="LO-<?php echo date('Ymd'); ?>" 
           readonly 
           style="background: #f4f4f4; width: 130px; border: 1px solid #ddd; text-align: center;"></td>
                    <td><button type="button" onclick="removeRow(this)">Xóa</button></td>
                </tr>
            </tbody>
        </table>
        <br>
        <button type="button" onclick="addRow()">+ Thêm sản phẩm</button>
        <button type="submit" name="btn_save">Lưu phiếu nhập</button>
    </form>

    <script>
// 1. Hàm tạo mã lô theo ngày (Dùng chung)
function generateBatchCode() {
    let dateInput = document.querySelector('input[name="ngay_nhap"]').value;
    if (!dateInput) return "";
    // Chuyển "2026-03-11" thành "20260311"
    let formattedDate = dateInput.replace(/-/g, ""); 
    return "LO-" + formattedDate;
}

// 2. Sửa hàm thêm hàng để tự điền mã lô mới
function addRow() {
    var table = document.getElementById("productTable").getElementsByTagName('tbody')[0];
    var row = table.insertRow(-1);
    row.innerHTML = table.rows[0].innerHTML;
    
    // Reset dữ liệu nhưng GIỮ LẠI mã lô tự động
    var inputs = row.getElementsByTagName('input');
    let autoBatch = generateBatchCode();

    for (var i = 0; i < inputs.length; i++) {
        if(inputs[i].name === "lo[]") {
            inputs[i].value = autoBatch; // Điền mã lô tự động vào hàng mới
        } else if(inputs[i].type === 'hidden') {
            inputs[i].value = "";
        } else {
            inputs[i].value = "";
        }
    }
    // Ẩn bảng kết quả tìm kiếm của hàng mới
    row.querySelector('.search-results').style.display = 'none';
}

// 3. Lắng nghe sự kiện đổi ngày nhập để đổi luôn mã lô toàn bộ bảng
document.querySelector('input[name="ngay_nhap"]').addEventListener("change", function() {
    let newBatch = generateBatchCode();
    let allBatchInputs = document.getElementsByName("lo[]");
    allBatchInputs.forEach(input => {
        input.value = newBatch;
    });
});

function liveSearch(input) {
    let keyword = input.value;
    let resultDiv = input.parentElement.querySelector('.search-results');
    let idHidden = input.parentElement.querySelector('.sp-id-hidden');

    if (keyword.length < 1) {
        resultDiv.style.display = 'none';
        idHidden.value = "";
        return;
    }

    fetch(`ajax_tim_sanpham.php?key=${keyword}`)
        .then(response => response.json())
        .then(data => {
            let html = '';
            if(data.length > 0) {
                data.forEach(item => {
                    html += `<div style="padding: 10px; cursor: pointer; border-bottom: 1px solid #eee;" 
                                 onclick="selectProduct(this, ${item.SanPham_id}, '${item.TenSP}')">
                                 ${item.TenSP}
                             </div>`;
                });
                resultDiv.innerHTML = html;
                resultDiv.style.display = 'block';
            } else {
                resultDiv.innerHTML = '<div style="padding:10px;">Không tìm thấy</div>';
                resultDiv.style.display = 'block';
            }
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

    if (!confirm("Hệ thống sẽ lấy danh sách sản phẩm từ phiếu #" + phieuId + " và cập nhật Mã lô theo ngày hiện tại. Bạn đồng ý chứ?")) return;

    fetch(`get_phieu_detail.php?id=${phieuId}`)
        .then(response => response.json())
        .then(data => {
            let tableBody = document.querySelector("#productTable tbody");
            tableBody.innerHTML = ""; // Xóa sạch các dòng hiện tại

            // Lấy mã lô mới theo ngày nhập đang chọn trên form
            let autoBatch = generateBatchCode();

            data.forEach(item => {
                let newRow = `
                <tr>
                    <td>
                        <div style="position: relative;">
                            <input type="text" class="search-input" value="${item.TenSP}" onkeyup="liveSearch(this)" autocomplete="off" style="width: 100%; padding: 5px;">
                            <input type="hidden" name="sp_id[]" class="sp-id-hidden" value="${item.SanPham_id}">
                            <div class="search-results" style="position: absolute; background: white; border: 1px solid #ccc; width: 100%; display: none; z-index: 1000;"></div>
                        </div>
                    </td>
                    <td>
                        <input type="number" name="sl[]" value="${item.SoLuong}" min="1" oninput="if(this.value <= 0) this.value = 1;" required style="width: 80px;">
                    </td>
                    <td>
                        <input type="number" name="gia[]" value="${item.Gia_Nhap}" min="0" step="0.01" oninput="if(this.value < 0) this.value = 0;" required style="width: 120px;">
                    </td>
                    <td>
                        <input type="text" name="lo[]" value="${autoBatch}" readonly style="background: #f4f4f4; width: 130px; border: 1px solid #ddd; text-align: center;">
                    </td>
                    <td><button type="button" onclick="removeRow(this)">Xóa</button></td>
                </tr>`;
                tableBody.insertAdjacentHTML('beforeend', newRow);
            });
        })
        .catch(err => alert("Lỗi khi tải dữ liệu phiếu cũ!"));
}
</script>
    
</body>
</html>