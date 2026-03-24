-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 23, 2026 at 03:09 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `b01_nhahodau`
--

-- --------------------------------------------------------

--
-- Table structure for table `chitiethoadon`
--


CREATE DATABASE IF NOT EXISTS b01_nhahodau
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE b01_nhahodau;
CREATE TABLE `chitiethoadon` (
  `ChiTietDonHang_id` int(11) NOT NULL,
  `DonHang_id` int(11) DEFAULT NULL,
  `SanPham_id` int(11) DEFAULT NULL,
  `SoLuong` int(11) DEFAULT NULL,
  `Gia` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chitietphieunhap`
--

CREATE TABLE `chitietphieunhap` (
  `ChiTiet_id` int(11) NOT NULL,
  `PhieuNhap_id` int(11) DEFAULT NULL,
  `SanPham_id` int(11) DEFAULT NULL,
  `SoLuong` int(11) DEFAULT NULL,
  `Gia_Nhap` decimal(15,2) DEFAULT NULL,
  `MaLoHang` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chitietphieuxuat`
--

CREATE TABLE `chitietphieuxuat` (
  `ChiTiet_id` int(11) NOT NULL,
  `PhieuXuat_id` int(11) DEFAULT NULL,
  `SP_id` int(11) DEFAULT NULL,
  `SoLuong` int(11) DEFAULT NULL,
  `GiaNhap` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;





-- --------------------------------------------------------

--
-- Table structure for table `danhmuc`
--

CREATE TABLE `danhmuc` (
  `Danhmuc_id` int(11) NOT NULL,
  `Ten_danhmuc` varchar(100) NOT NULL,
  `slug` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `danhmuc`
--



-- --------------------------------------------------------

--
-- Table structure for table `diachigh`
--

CREATE TABLE `diachigh` (
  `add_id` int(11) NOT NULL,
  `User_id` int(11) DEFAULT NULL,
  `Ten_nguoi_nhan` varchar(100) DEFAULT NULL,
  `SDT_nhan` varchar(15) DEFAULT NULL,
  `Tinh_thanhpho` varchar(50) DEFAULT NULL,
  `Quan` varchar(50) DEFAULT NULL,
  `Duong` varchar(100) DEFAULT NULL,
  `Dia_chi_chitiet` text DEFAULT NULL,
  `Mac_dinh` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `donhang`
--

CREATE TABLE `donhang` (
  `DonHang_id` int(11) NOT NULL,
  `User_id` int(11) DEFAULT NULL,
  `DiaChi_id` int(11) DEFAULT NULL,
  `PhuongThucTT` varchar(50) DEFAULT NULL,
  `TongTien` int(11) DEFAULT NULL,
  `NgayDat` date DEFAULT NULL,
  `TrangThai` varchar(50) DEFAULT NULL,
  `linkTraCuu` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `giohang`
--

CREATE TABLE `giohang` (
  `GioHang_id` int(11) NOT NULL,
  `User_id` int(11) DEFAULT NULL,
  `ThoiGianTao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nhacungcap`
--

CREATE TABLE `nhacungcap` (
  `NCC_id` int(11) NOT NULL,
  `Ten_NCC` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nhacungcap`
--

INSERT INTO `nhacungcap` (`NCC_id`, `Ten_NCC`) VALUES
(1, 'Sunrise'),
(2, 'Elipsport'),
(3, 'Minh Sport'),
(4, 'Sunrise'),
(5, 'Elipsport'),
(6, 'Minh Sport'),
(7, 'Sunrise'),
(8, 'Elipsport'),
(9, 'Minh Sport');

-- --------------------------------------------------------

--
-- Table structure for table `phieunhap`
--

CREATE TABLE `phieunhap` (
  `NhapHang_id` int(11) NOT NULL,
  `NguoiNhap` varchar(100) DEFAULT NULL,
  `NgayNhap` date DEFAULT NULL,
  `SoLuong` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `phieunhap`
--




-- --------------------------------------------------------

--
-- Table structure for table `phieuxuat`
--

CREATE TABLE `phieuxuat` (
  `PhieuXuat_id` int(11) NOT NULL,
  `DonHang_id` int(11) DEFAULT NULL,
  `NgayXuat` datetime DEFAULT NULL,
  `NguoiXuat_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sanpham`
--

CREATE TABLE `sanpham` (
  `SanPham_id` int(11) NOT NULL,
  `TenSP` varchar(200) NOT NULL,
  `Danhmuc_id` int(11) DEFAULT NULL,
  `NCC_id` int(11) DEFAULT NULL,
  `Ma_thuonghieu` int(11) DEFAULT NULL,
  `MoTa` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `GiaNhapTB` decimal(15,0) DEFAULT NULL,
  `GiaBan` DECIMAL(15,2) DEFAULT NULL,
  `PhanTramLoiNhuan` decimal(5,2) DEFAULT NULL,
  `TrangThai` tinyint(1) DEFAULT 1,
  `TaoNgay` datetime DEFAULT current_timestamp(),
  `SoLuongTon` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sanpham`
--


-- --------------------------------------------------------

--
-- Table structure for table `sp_tronggiohang`
--

CREATE TABLE `sp_tronggiohang` (
  `SP_GioHang_id` int(11) NOT NULL,
  `GioHang_id` int(11) DEFAULT NULL,
  `SanPham_id` int(11) DEFAULT NULL,
  `SoLuong` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `thuonghieu`
--

CREATE TABLE `thuonghieu` (
  `Ma_thuonghieu` int(11) NOT NULL,
  `Ten_thuonghieu` varchar(100) NOT NULL,
  `slug` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thuonghieu`
--

INSERT INTO `thuonghieu` (`Ma_thuonghieu`, `Ten_thuonghieu`, `slug`) VALUES
(1, 'Li-Ning', 'li-ning'),
(2, 'Yonex', 'yonex'),
(3, 'Victor', 'victor');

-- --------------------------------------------------------

--
-- Table structure for table `tracuutonkho`
--

CREATE TABLE `tracuutonkho` (
  `TraCuu_id` int(11) NOT NULL,
  `SP_id` int(11) DEFAULT NULL,
  `TrangThai_NhapXuat` varchar(50) DEFAULT NULL,
  `SoLuong` int(11) DEFAULT NULL,
  `MaThamChieu_NhapXuat` int(11) DEFAULT NULL,
  `ThoiGianTraCuu` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `User_id` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `Ho_ten` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `SDT` varchar(15) DEFAULT NULL,
  `role` tinyint(1) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--


--
-- Indexes for dumped tables
--

--
-- Indexes for table `chitiethoadon`
--
ALTER TABLE `chitiethoadon`
  ADD PRIMARY KEY (`ChiTietDonHang_id`),
  ADD KEY `DonHang_id` (`DonHang_id`),
  ADD KEY `SanPham_id` (`SanPham_id`);

--
-- Indexes for table `chitietphieunhap`
--
ALTER TABLE `chitietphieunhap`
  ADD PRIMARY KEY (`ChiTiet_id`),
  ADD KEY `PhieuNhap_id` (`PhieuNhap_id`),
  ADD KEY `SanPham_id` (`SanPham_id`);

--
-- Indexes for table `chitietphieuxuat`
--
ALTER TABLE `chitietphieuxuat`
  ADD PRIMARY KEY (`ChiTiet_id`),
  ADD KEY `PhieuXuat_id` (`PhieuXuat_id`),
  ADD KEY `SP_id` (`SP_id`);

--
-- Indexes for table `danhmuc`
--
ALTER TABLE `danhmuc`
  ADD PRIMARY KEY (`Danhmuc_id`);

--
-- Indexes for table `diachigh`
--
ALTER TABLE `diachigh`
  ADD PRIMARY KEY (`add_id`),
  ADD KEY `User_id` (`User_id`);

--
-- Indexes for table `donhang`
--
ALTER TABLE `donhang`
  ADD PRIMARY KEY (`DonHang_id`),
  ADD KEY `User_id` (`User_id`),
  ADD KEY `DiaChi_id` (`DiaChi_id`);

--
-- Indexes for table `giohang`
--
ALTER TABLE `giohang`
  ADD PRIMARY KEY (`GioHang_id`),
  ADD KEY `User_id` (`User_id`);

--
-- Indexes for table `nhacungcap`
--
ALTER TABLE `nhacungcap`
  ADD PRIMARY KEY (`NCC_id`);

--
-- Indexes for table `phieunhap`
--
ALTER TABLE `phieunhap`
  ADD PRIMARY KEY (`NhapHang_id`);

--
-- Indexes for table `phieuxuat`
--
ALTER TABLE `phieuxuat`
  ADD PRIMARY KEY (`PhieuXuat_id`),
  ADD KEY `DonHang_id` (`DonHang_id`),
  ADD KEY `NguoiXuat_id` (`NguoiXuat_id`);

--
-- Indexes for table `sanpham`
--
ALTER TABLE `sanpham`
  ADD PRIMARY KEY (`SanPham_id`),
  ADD KEY `Loai_id` (`Danhmuc_id`),
  ADD KEY `NCC_id` (`NCC_id`),
  ADD KEY `Ma_thuonghieu` (`Ma_thuonghieu`);
 
   

--
-- Indexes for table `sp_tronggiohang`
--
ALTER TABLE `sp_tronggiohang`
  ADD PRIMARY KEY (`SP_GioHang_id`),
  ADD KEY `GioHang_id` (`GioHang_id`),
  ADD KEY `SanPham_id` (`SanPham_id`);

--
-- Indexes for table `thuonghieu`
--
ALTER TABLE `thuonghieu`
  ADD PRIMARY KEY (`Ma_thuonghieu`);

--
-- Indexes for table `tracuutonkho`
--
ALTER TABLE `tracuutonkho`
  ADD PRIMARY KEY (`TraCuu_id`),
  ADD KEY `SP_id` (`SP_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`User_id`),
  ADD UNIQUE KEY `Username` (`Username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chitiethoadon`
--
ALTER TABLE `chitiethoadon`
  MODIFY `ChiTietDonHang_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chitietphieunhap`
--
ALTER TABLE `chitietphieunhap`
  MODIFY `ChiTiet_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chitietphieuxuat`
--
ALTER TABLE `chitietphieuxuat`
  MODIFY `ChiTiet_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `danhmuc`
--
ALTER TABLE `danhmuc`
  MODIFY `Danhmuc_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `diachigh`
--
ALTER TABLE `diachigh`
  MODIFY `add_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `donhang`
--
ALTER TABLE `donhang`
  MODIFY `DonHang_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `giohang`
--
ALTER TABLE `giohang`
  MODIFY `GioHang_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nhacungcap`
--
ALTER TABLE `nhacungcap`
  MODIFY `NCC_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `phieunhap`
--
ALTER TABLE `phieunhap`
  MODIFY `NhapHang_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `phieuxuat`
--
ALTER TABLE `phieuxuat`
  MODIFY `PhieuXuat_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sanpham`
--
ALTER TABLE `sanpham`
  MODIFY `SanPham_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sp_tronggiohang`
--
ALTER TABLE `sp_tronggiohang`
  MODIFY `SP_GioHang_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `thuonghieu`
--
ALTER TABLE `thuonghieu`
  MODIFY `Ma_thuonghieu` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tracuutonkho`
--
ALTER TABLE `tracuutonkho`
  MODIFY `TraCuu_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `User_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chitiethoadon`
--
ALTER TABLE `chitiethoadon`
  ADD CONSTRAINT `chitiethoadon_ibfk_1` FOREIGN KEY (`DonHang_id`) REFERENCES `donhang` (`DonHang_id`),
  ADD CONSTRAINT `chitiethoadon_ibfk_2` FOREIGN KEY (`SanPham_id`) REFERENCES `sanpham` (`SanPham_id`);

--
-- Constraints for table `chitietphieunhap`
--
ALTER TABLE `chitietphieunhap`
  ADD CONSTRAINT `chitietphieunhap_ibfk_1` FOREIGN KEY (`PhieuNhap_id`) REFERENCES `phieunhap` (`NhapHang_id`),
  ADD CONSTRAINT `chitietphieunhap_ibfk_2` FOREIGN KEY (`SanPham_id`) REFERENCES `sanpham` (`SanPham_id`);

--
-- Constraints for table `chitietphieuxuat`
--
ALTER TABLE `chitietphieuxuat`
  ADD CONSTRAINT `chitietphieuxuat_ibfk_1` FOREIGN KEY (`PhieuXuat_id`) REFERENCES `phieuxuat` (`PhieuXuat_id`),
  ADD CONSTRAINT `chitietphieuxuat_ibfk_2` FOREIGN KEY (`SP_id`) REFERENCES `sanpham` (`SanPham_id`);

--
-- Constraints for table `diachigh`
--
ALTER TABLE `diachigh`
  ADD CONSTRAINT `diachigh_ibfk_1` FOREIGN KEY (`User_id`) REFERENCES `users` (`User_id`);

--
-- Constraints for table `donhang`
--
ALTER TABLE `donhang`
  ADD CONSTRAINT `donhang_ibfk_1` FOREIGN KEY (`User_id`) REFERENCES `users` (`User_id`),
  ADD CONSTRAINT `donhang_ibfk_2` FOREIGN KEY (`DiaChi_id`) REFERENCES `diachigh` (`add_id`);

--
-- Constraints for table `giohang`
--
ALTER TABLE `giohang`
  ADD CONSTRAINT `giohang_ibfk_1` FOREIGN KEY (`User_id`) REFERENCES `users` (`User_id`);

--
-- Constraints for table `phieuxuat`
--
ALTER TABLE `phieuxuat`
  ADD CONSTRAINT `phieuxuat_ibfk_1` FOREIGN KEY (`DonHang_id`) REFERENCES `donhang` (`DonHang_id`),
  ADD CONSTRAINT `phieuxuat_ibfk_2` FOREIGN KEY (`NguoiXuat_id`) REFERENCES `users` (`User_id`);

--
-- Constraints for table `sanpham`
--
ALTER TABLE `sanpham`
  ADD CONSTRAINT `sanpham_ibfk_1` FOREIGN KEY (`Danhmuc_id`) REFERENCES `danhmuc` (`Danhmuc_id`),
  ADD CONSTRAINT `sanpham_ibfk_2` FOREIGN KEY (`NCC_id`) REFERENCES `nhacungcap` (`NCC_id`),
  ADD CONSTRAINT `sanpham_ibfk_3` FOREIGN KEY (`Ma_thuonghieu`) REFERENCES `thuonghieu` (`Ma_thuonghieu`);

--
-- Constraints for table `sp_tronggiohang`
--
ALTER TABLE `sp_tronggiohang`
  ADD CONSTRAINT `sp_tronggiohang_ibfk_1` FOREIGN KEY (`GioHang_id`) REFERENCES `giohang` (`GioHang_id`),
  ADD CONSTRAINT `sp_tronggiohang_ibfk_2` FOREIGN KEY (`SanPham_id`) REFERENCES `sanpham` (`SanPham_id`);

--
-- Constraints for table `tracuutonkho`
--
ALTER TABLE `tracuutonkho`
  ADD CONSTRAINT `tracuutonkho_ibfk_1` FOREIGN KEY (`SP_id`) REFERENCES `sanpham` (`SanPham_id`);
COMMIT;







DELIMITER $$

DELIMITER $$

CREATE FUNCTION fn_GiaNhapTB(p_SanPham_id INT) 
RETURNS DECIMAL(15,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE total_cost DECIMAL(15,2) DEFAULT 0;
    DECLARE total_qty INT DEFAULT 0;
    DECLARE avg_price DECIMAL(15,2) DEFAULT 0;

    SELECT SUM(SoLuong * Gia_Nhap), SUM(SoLuong)
    INTO total_cost, total_qty
    FROM chitietphieunhap
    WHERE SanPham_id = p_SanPham_id;

    IF total_qty > 0 THEN
        SET avg_price = total_cost / total_qty;
    END IF;

    RETURN avg_price;
END$$

DELIMITER ;







DELIMITER $$


-- 2. Hàm làm tròn theo quy tắc 500đ
DELIMITER $$

CREATE FUNCTION fn_round_500(price DECIMAL(15,2))
RETURNS DECIMAL(15,2)
DETERMINISTIC
BEGIN
    DECLARE remainder DECIMAL(15,2);
    DECLARE rounded DECIMAL(15,2);
    
    IF price IS NULL THEN
        RETURN NULL;
    END IF;
    
    -- Lấy phần dư khi chia cho 1000
    SET remainder = MOD(price, 1000);
    
    IF remainder > 500 THEN
        SET rounded = price + (1000 - remainder);
    ELSE
        SET rounded = price - remainder;
    END IF;
    
    RETURN rounded;
END$$

DELIMITER ;

-- 3. Trigger INSERT
DELIMITER $$

CREATE TRIGGER trg_chitietphieunhap_insert
AFTER INSERT ON chitietphieunhap
FOR EACH ROW
BEGIN
    UPDATE sanpham 
    SET 
        SoLuongTon = SoLuongTon + NEW.SoLuong,
        GiaNhapTB = (SoLuongTon * GiaNhapTB + NEW.SoLuong * NEW.Gia_Nhap) / (SoLuongTon + NEW.SoLuong)
    WHERE SanPham_id = NEW.SanPham_id;
    
    -- Cập nhật giá bán đã làm tròn
    UPDATE sanpham 
    SET GiaBan = fn_round_500(GiaNhapTB * (1 + PhanTramLoiNhuan))
    WHERE SanPham_id = NEW.SanPham_id;
END$$

DELIMITER ;

-- 4. Trigger UPDATE (khi sửa chi tiết phiếu nhập)
DELIMITER $$

CREATE TRIGGER trg_chitietphieunhap_update
AFTER UPDATE ON chitietphieunhap
FOR EACH ROW
BEGIN
    DECLARE new_avg DECIMAL(15,2);
    DECLARE new_total_qty INT;

    SELECT IFNULL(SUM(SoLuong), 0) INTO new_total_qty
    FROM chitietphieunhap
    WHERE SanPham_id = NEW.SanPham_id;

    SET new_avg = fn_GiaNhapTB(NEW.SanPham_id);

    UPDATE sanpham 
    SET 
        GiaNhapTB = new_avg,
        SoLuongTon = new_total_qty,
        GiaBan = fn_round_500(new_avg * (1 + PhanTramLoiNhuan))
    WHERE SanPham_id = NEW.SanPham_id;
END$$

DELIMITER ;

-- 6. Trigger khi thay đổi PhanTramLoiNhuan (nếu có cập nhật thủ công)
DELIMITER $$

CREATE TRIGGER trg_sanpham_update_phantramloinhuan
BEFORE UPDATE ON sanpham
FOR EACH ROW
BEGIN
    IF NEW.PhanTramLoiNhuan != OLD.PhanTramLoiNhuan THEN
        SET NEW.GiaBan = fn_round_500(NEW.GiaNhapTB * (1 + NEW.PhanTramLoiNhuan));
    END IF;
END$$

DELIMITER ;


DELIMITER $$
CREATE TRIGGER trg_chitietphieunhap_insert_update_phieu
AFTER INSERT ON chitietphieunhap
FOR EACH ROW
BEGIN
    UPDATE phieunhap
    SET SoLuong = (
        SELECT IFNULL(SUM(SoLuong), 0)
        FROM chitietphieunhap
        WHERE PhieuNhap_id = NEW.PhieuNhap_id
    )
    WHERE NhapHang_id = NEW.PhieuNhap_id;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_chitietphieunhap_update_update_phieu
AFTER UPDATE ON chitietphieunhap
FOR EACH ROW
BEGIN
    -- Cập nhật cho phiếu cũ nếu thay đổi PhieuNhap_id
    IF OLD.PhieuNhap_id != NEW.PhieuNhap_id THEN
        UPDATE phieunhap
        SET SoLuong = (
            SELECT IFNULL(SUM(SoLuong), 0)
            FROM chitietphieunhap
            WHERE PhieuNhap_id = OLD.PhieuNhap_id
        )
        WHERE NhapHang_id = OLD.PhieuNhap_id;
    END IF;
    
    -- Cập nhật cho phiếu mới
    UPDATE phieunhap
    SET SoLuong = (
        SELECT IFNULL(SUM(SoLuong), 0)
        FROM chitietphieunhap
        WHERE PhieuNhap_id = NEW.PhieuNhap_id
    )
    WHERE NhapHang_id = NEW.PhieuNhap_id;
END$$
DELIMITER ;

-- Tính trực tiếp không cần quét lại toàn bộ

INSERT INTO `danhmuc` (`Ten_danhmuc`, `slug`) VALUES
('Vợt cầu lông', 'vot-cau-long'),
('Phụ kiện', 'phu-kien'),
('Vợt Pickleball', 'vot-pickleball');

INSERT INTO `sanpham` (`SanPham_id`, `TenSP`, `Danhmuc_id`, `NCC_id`, `Ma_thuonghieu`, `MoTa`, `image_url`, `GiaNhapTB`, `GiaBan`, `PhanTramLoiNhuan`, `TrangThai`, `TaoNgay`, `SoLuongTon`) VALUES
(1, 'Yonex Astrox 100ZZ', 1, 4, 2, 'đắt vãi ò', '/img/sanpham/Vot-cau-long-Yonex-Astrox-100ZZ.png', 3200000, NULL , 0.15, 1, '2026-03-20 22:16:21', 10),
(2, 'Li-Ning Halbertec 9000', 1, 5, 1, 'đắt vãi ò', '/img/sanpham/vot-cau-long-li-ning-halbertec-9000.png', 2500000, NULL , 0.15, 1, '2026-03-20 22:16:21', 15),
(3, 'Victor Thruster F Enhanced', 1, 6, 3, 'đắt vãi ò', '/img/sanpham/vot-cau-long-victor-thruster-ryuga.png', 2800000,NULL, 0.15, 1, '2026-03-20 22:16:21', 8),
(4, 'Yonex Nanoflare 1000 game', 1, 4, 2, 'đắt vãi ò', '/img/sanpham/vot-cau-long-yonex-nanoflare-1000-game.png', 3100000, NULL , 0.15, 1, '2026-03-20 22:16:21', 12),
(5, 'Li-Ning Aeronaut 9000', 1, 5, 1, 'đắt vãi ò', '/img/sanpham/aeronaut-9000i.png', 2900000, NULL , 0.15, 1, '2026-03-20 22:16:21', 7),
(6, 'Quả cầu lông Yonex AS50', 2, 4, 2, 'đắt vãi ò', '/img/sanpham/ong-cau-yonex-as50-speed-2.png', 450000,NULL , 0.15, 1, '2026-03-20 22:16:21', 50),
(7, 'Quả cầu lông Hải Yến S70', 2, 6, 3, 'đắt vãi ò', '/img/sanpham/ong-cau-long-hai-yen-s70.png', 180000,NULL , 0.15, 1, '2026-03-20 22:16:21', 100),
(8, 'Quả cầu lông vinastar', 2, 6, 1, 'đắt vãi ò', '/img/sanpham/ong-cau-vina-start-xanh.png', 350000,NULL , 0.15, 1, '2026-03-20 22:16:21', 60),
(9, 'Quả cầu Li-ning AYQN024-4', 2, 5, 3, 'đắt vãi ò', '/img/sanpham/cau-lining.png', 320000,NULL , 0.15, 1, '2026-03-20 22:16:21', 45),
(10, 'Vợt Pickleball Selkirk Vanguard', 3, 6, 3, 'đắt vãi ò', '/img/sanpham/vot-pickleball-selkirk-luxx-control-air.png', 4500000,NULL , 0.15, 1, '2026-03-20 22:16:21', 5),
(11, 'Vợt Pickleball Joola Perseus', 3, 6, 2, 'đắt vãi ò', '/img/sanpham/perseus-pro-v-ben-johns-blaze-red.png', 5200000,NULL , 0.15, 1, '2026-03-20 22:16:21', 4),
(12, 'Vợt Pickleball JOOLA Ben Johns', 3, 6, 1, 'đắt vãi ò', '/img/sanpham/joola-ben-johns-hyperion.png', 2100000, NULL, 0.15, 1, '2026-03-20 22:16:21', 10),
(13, 'Vợt Pickleball Soxter Impact', 3, 6, 3, 'đắt vãi ò', '/img/sanpham/vot-pickleball-soxter-impact-pro-2.png', 3800000,NULL , 0.15, 1, '2026-03-20 22:16:21', 6),
(14, 'Hoàng chou', 2, 6, 3, 'sjdflsflsjlfksd', '/img/products/PROD-20260323134545-69c135f98a177.jpg', NULL, NULL, 20.00, 1, '2026-03-23 19:45:45', 0);


INSERT INTO `chitietphieunhap` (`SanPham_id`	,`SoLuong`,	`Gia_Nhap`) VALUES 
(8,10,280000),
(8,50,275000),
(8,100,350000),
(1,10,3200000),
(2,15,2500000),
(3,8,2800000),
(4,12,3100000),
(5,7,2900000),
(6,50,450000),
(7,10,180000),
(9,45,320000),
(10,5,4500000),
(11,4,5200000),
(12,10,2100000),
(13,6,3800000);



INSERT INTO `phieunhap` ( `NguoiNhap`, `NgayNhap`, `SoLuong`) VALUES
('user', '2026-03-23', 0),
('user', '2026-03-23', 0),
('user', '2026-03-23', 0),
('user', '2026-03-23', 0),
('user', '2026-03-23', 0),
('user', '2026-03-23', 0),
('user', '2026-03-23', 0),
('user', '2026-03-23', 0),
('user', '2026-03-23', 0),
('user', '2026-03-23', 0),
('user', '2026-03-23', 0),
('user', '2026-03-23', 0),
('user', '2026-03-23', 0),
('user', '2026-03-23', 0);

INSERT INTO `users` (`User_id`, `Username`, `password`, `Ho_ten`, `email`, `SDT`, `role`, `status`, `created_at`) VALUES
(1, 'user', '$2y$10$WyfbWCYPDFLPz2HbRfYDa.POvoakT/E71k.3Qhbe2Fay/NAx0ZH3i', NULL, NULL, NULL, 1, '1', '2026-03-20 22:30:01'),
(2, 'sang', '$2a$10$hFgtrSjzowGIvrIU90F86.ZBN87TLRbv1R4V3GLY9G5dKnaIR7qj.', 'Sang Ngu', 'ea@gmail.com', '0909090909', 0, '1', '2026-03-20 22:30:01'),
(3, 'Tisdoo', '$2y$10$KVCLdId9zeX.m9V6n.KSBuIAhB3dHadrgsIs.o7q3sdNr54kMgZUq', 'hoàng ấn', 'bodow@gmail.com', '0598898588', 0, '1', '2026-03-23 10:47:04'),
(4, 'beiu', '$2y$10$amSCnKvV/3fwmiwpx8GO9ui9YfkXdt3W4qZCXi/BQEpwaSEF50Lhy', 'hoàng ấn', 'bodowq@gmail.com', '0598898588', 0, '1', '2026-03-23 10:49:34');


UPDATE sanpham sp
SET 
    GiaNhapTB = fn_GiaNhapTB(sp.SanPham_id),
    SoLuongTon = (SELECT IFNULL(SUM(SoLuong), 0) FROM chitietphieunhap WHERE SanPham_id = sp.SanPham_id),
    GiaBan = fn_round_500(fn_GiaNhapTB(sp.SanPham_id) * (1 + sp.PhanTramLoiNhuan));

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
