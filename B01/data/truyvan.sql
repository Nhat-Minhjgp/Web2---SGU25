--Tìm kiếm cơ bản--
SELECT * FROM sanpham 
WHERE TenSP LIKE '%tên sản phẩm%';
--Tìm kiếm nâng cao--
SELECT * FROM sanpham 
WHERE TenSP LIKE '%Tên sản phẩm%' 
  AND Danhmuc_id = 1 --theo id của danh mục-- 
  AND GiaBan BETWEEN 1000000 AND 3000000;--khoảng từ...ĐÉN--    
  --Xem tóm tắt thông tin đơn đặt hàng--
  SELECT 
    dh.DonHang_id ,
    sp.TenSP ,
    ct.SoLuong ,
    ct.Gia ,
    (ct.SoLuong * ct.Gia)
FROM chitiethoadon ct
JOIN sanpham sp ON ct.SanPham_id = sp.SanPham_id
JOIN donhang dh ON ct.DonHang_id = dh.DonHang_id
WHERE dh.DonHang_id = 1; -- Thay ? bằng mã đơn hàng muốn xem
--Xem tất cả đơn đặt hàng của một khách hàng--
SELECT dh.*, u.Ho_ten 
FROM donhang dh
JOIN users u ON dh.User_id = u.User_id
WHERE u.User_id = 3
ORDER BY dh.NgayDat DESC;
