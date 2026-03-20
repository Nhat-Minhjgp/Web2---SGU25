--  dữ liệu bảng thương hiệu--
 INSERT INTO `thuonghieu` (`Ten_thuonghieu`) VALUES 
('Li-Ning'),
('Yonex'),
('Victor');
-- dữ liệu bảng nhà cung cấp--
INSERT INTO `nhacungcap` (`Ten_NCC`) VALUES 
('Sunrise'),
('Elipsport'),
('Minh Sport');


-- add danh mục --

INSERT INTO `danhmuc` (`Danhmuc_id`,`Ten_danhmuc`) VALUES
(4,'Vợt cầu lông'),
(5,'Phụ kiện'),
(6,'Vợt Pickleball');



-- Cập nhật slug cho thuonghieu hiện có


UPDATE `thuonghieu` SET `slug` = 'li-ning' WHERE `Ma_thuonghieu` = 1;
UPDATE `thuonghieu` SET `slug` = 'yonex' WHERE `Ma_thuonghieu` = 2;
UPDATE `thuonghieu` SET `slug` = 'victor' WHERE `Ma_thuonghieu` = 3;


-- Dữ liệu cho bảng sản phẩm với TrangThai là 1 (đang bán)
INSERT INTO `sanpham` (`TenSP`, `Danhmuc_id`, `Ma_thuonghieu`, `GiaNhapTB`, `GiaBan`, `SoLuongTon`, `TrangThai`, `PhanTramLoiNhuan`, `image_url`, `NCC_id`, `MoTa`) VALUES
('Yonex Astrox 100ZZ', 4, 2, 3200000, 4150000, 10, 1, 0.15, '/img/sanpham/Vot-cau-long-Yonex-Astrox-100ZZ.png', 4, 'đắt vãi ò'),
('Li-Ning Halbertec 9000', 4, 1, 2500000, 3200000, 15, 1, 0.15, '/img/sanpham/vot-cau-long-li-ning-halbertec-9000.png', 5, 'đắt vãi ò'),
('Victor Thruster F Enhanced', 4, 3, 2800000, 3650000, 8, 1, 0.15, '/img/sanpham/vot-cau-long-victor-thruster-ryuga.png', 6, 'đắt vãi ò'),
('Yonex Nanoflare 1000 game', 4, 2, 3100000, 4050000, 12, 1, 0.15, '/img/sanpham/vot-cau-long-yonex-nanoflare-1000-game.png', 4, 'đắt vãi ò'),
('Li-Ning Aeronaut 9000', 4, 1, 2900000, 3800000, 7, 1, 0.15, '/img/sanpham/aeronaut-9000i.png', 5, 'đắt vãi ò'),
('Quả cầu lông Yonex AS50', 5, 2, 450000, 580000, 50, 1, 0.15, '/img/sanpham/ong-cau-yonex-as50-speed-2.png', 4, 'đắt vãi ò'),
('Quả cầu lông Hải Yến S70', 5, 3, 180000, 250000, 100, 1, 0.15, '/img/sanpham/ong-cau-long-hai-yen-s70.png', 6, 'đắt vãi ò'),
('Quả cầu lông vinastar', 5, 1, 350000, 450000, 60, 1, 0.15, '/img/sanpham/ong-cau-vina-start-xanh.png', 6, 'đắt vãi ò'),
('Quả cầu Li-ning AYQN024-4', 5, 3, 320000, 410000, 45, 1, 0.15, '/img/sanpham/cau-lining.png', 5, 'đắt vãi ò'),
('Vợt Pickleball Selkirk Vanguard', 6, 3, 4500000, 5800000, 5, 1, 0.15, '/img/sanpham/vot-pickleball-selkirk-luxx-control-air.png', 6, 'đắt vãi ò'),
('Vợt Pickleball Joola Perseus', 6, 2, 5200000, 6500000, 4, 1, 0.15, '/img/sanpham/perseus-pro-v-ben-johns-blaze-red.png', 6, 'đắt vãi ò'),
('Vợt Pickleball JOOLA Ben Johns', 6, 1, 2100000, 2900000, 10, 1, 0.15, '/img/sanpham/joola-ben-johns-hyperion.png', 6, 'đắt vãi ò'),
('Vợt Pickleball Soxter Impact', 6, 3, 3800000, 4950000, 6, 1, 0.15, '/img/sanpham/vot-pickleball-soxter-impact-pro-2.png', 6, 'đắt vãi ò');