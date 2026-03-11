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
                    <td><input type="text" name="lo[]"></td>
                    <td><button type="button" onclick="removeRow(this)">Xóa</button></td>
                </tr>
            </tbody>
        </table>
        <br>
        <button type="button" onclick="addRow()">+ Thêm sản phẩm</button>
        <button type="submit" name="btn_save">Lưu phiếu nhập</button>
    </form>

    <script>
    function addRow() {
        var table = document.getElementById("productTable").getElementsByTagName('tbody')[0];
        var row = table.insertRow(-1);
        row.innerHTML = table.rows[0].innerHTML;
    }
    function removeRow(btn) {
        var row = btn.parentNode.parentNode;
        if(document.getElementById("productTable").rows.length > 2) row.parentNode.removeChild(row);
    }
    function liveSearch(input) {
    let keyword = input.value;
    let resultDiv = input.parentElement.querySelector('.search-results');
    let idHidden = input.parentElement.querySelector('.sp-id-hidden');

    if (keyword.length < 1) {
        resultDiv.style.display = 'none';
        idHidden.value = ""; // Xóa ID nếu người dùng xóa chữ
        return;
    }

    // Gửi yêu cầu đến file PHP để lấy danh sách sản phẩm
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

// Hàm này chạy khi bạn bấm vào một tên sản phẩm trong danh sách gợi ý
function selectProduct(element, id, name) {
    let parent = element.parentElement.parentElement;
    parent.querySelector('.search-input').value = name; // Điền tên vào ô input
    parent.querySelector('.sp-id-hidden').value = id;  // Lưu ID vào input ẩn để gửi về database
    element.parentElement.style.display = 'none';     // Ẩn danh sách gợi ý đi
}

// Đóng danh sách gợi ý khi bấm ra ngoài
document.addEventListener("click", function (e) {
    if (!e.target.classList.contains("search-input")) {
        let allResults = document.querySelectorAll(".search-results");
        allResults.forEach(div => div.style.display = "none");
    }
});
    </script>
    
</body>
</html>