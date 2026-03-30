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
    `TrangThai` tinyint (1) DEFAULT 0,
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
  (3, 'Minh Sport');


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


ALTER TABLE `sanpham` ADD COLUMN `CanhBaoTon` INT DEFAULT 10 AFTER `SoLuongTon`;




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

    -- ============================================
-- THÊM CỘT TRẠNG THÁI CHO BẢNG phieunhap
-- ============================================
ALTER TABLE `phieunhap` 
ADD COLUMN IF NOT EXISTS `TrangThai` ENUM('pending', 'completed') DEFAULT 'pending' AFTER `SoLuong`;

-- ============================================
-- TRIGGER MỚI - CHỈ CẬP NHẬT SỐ LƯỢNG TRONG PHIẾU
-- ============================================

-- Trigger INSERT
DELIMITER $$
CREATE TRIGGER trg_chitietphieunhap_insert
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

-- Trigger UPDATE
DELIMITER $$
CREATE TRIGGER trg_chitietphieunhap_update
AFTER UPDATE ON chitietphieunhap
FOR EACH ROW
BEGIN
    IF OLD.PhieuNhap_id != NEW.PhieuNhap_id THEN
        UPDATE phieunhap
        SET SoLuong = (
            SELECT IFNULL(SUM(SoLuong), 0)
            FROM chitietphieunhap
            WHERE PhieuNhap_id = OLD.PhieuNhap_id
        )
        WHERE NhapHang_id = OLD.PhieuNhap_id;
    END IF;
    
    UPDATE phieunhap
    SET SoLuong = (
        SELECT IFNULL(SUM(SoLuong), 0)
        FROM chitietphieunhap
        WHERE PhieuNhap_id = NEW.PhieuNhap_id
    )
    WHERE NhapHang_id = NEW.PhieuNhap_id;
END$$
DELIMITER ;

-- Trigger DELETE
DELIMITER $$
CREATE TRIGGER trg_chitietphieunhap_delete
AFTER DELETE ON chitietphieunhap
FOR EACH ROW
BEGIN
    UPDATE phieunhap
    SET SoLuong = (
        SELECT IFNULL(SUM(SoLuong), 0)
        FROM chitietphieunhap
        WHERE PhieuNhap_id = OLD.PhieuNhap_id
    )
    WHERE NhapHang_id = OLD.PhieuNhap_id;
END$$
DELIMITER ;
    
-- ============================================
-- TẠO TRIGGER CHO ĐƠN HÀNG
-- ============================================

DROP TRIGGER IF EXISTS trg_donhang_confirmed;
DROP TRIGGER IF EXISTS trg_donhang_cancelled;
DROP TRIGGER IF EXISTS trg_donhang_delivered;

DELIMITER $$

CREATE TRIGGER trg_donhang_confirmed
AFTER UPDATE ON donhang
FOR EACH ROW
BEGIN
    DECLARE v_phieu_xuat_id INT;
    
    IF OLD.TrangThai = 0 AND NEW.TrangThai = 1 THEN
        
        INSERT INTO phieuxuat (DonHang_id, NgayXuat, NguoiXuat_id)
        VALUES (NEW.DonHang_id, NOW(), 1);
        
        SET v_phieu_xuat_id = LAST_INSERT_ID();
        
        INSERT INTO chitietphieuxuat (PhieuXuat_id, SP_id, SoLuong, GiaNhap)
        SELECT v_phieu_xuat_id, ct.SanPham_id, ct.SoLuong, sp.GiaNhapTB
        FROM chitiethoadon ct
        JOIN sanpham sp ON ct.SanPham_id = sp.SanPham_id
        WHERE ct.DonHang_id = NEW.DonHang_id;
        
        UPDATE sanpham sp
        JOIN chitiethoadon ct ON sp.SanPham_id = ct.SanPham_id
        SET sp.SoLuongTon = sp.SoLuongTon - ct.SoLuong
        WHERE ct.DonHang_id = NEW.DonHang_id;
        
    END IF;
END$$

CREATE TRIGGER trg_donhang_cancelled
AFTER UPDATE ON donhang
FOR EACH ROW
BEGIN
    IF NEW.TrangThai = 3 AND OLD.TrangThai != 3 THEN
        
        DELETE FROM chitietphieuxuat 
        WHERE PhieuXuat_id IN (
            SELECT PhieuXuat_id FROM phieuxuat WHERE DonHang_id = NEW.DonHang_id
        );
        DELETE FROM phieuxuat WHERE DonHang_id = NEW.DonHang_id;
        
        UPDATE sanpham sp
        JOIN chitiethoadon ct ON sp.SanPham_id = ct.SanPham_id
        SET sp.SoLuongTon = sp.SoLuongTon + ct.SoLuong
        WHERE ct.DonHang_id = NEW.DonHang_id;
        
    END IF;
END$$

CREATE TRIGGER trg_donhang_delivered
AFTER UPDATE ON donhang
FOR EACH ROW
BEGIN
    IF NEW.TrangThai = 2 AND OLD.TrangThai != 2 THEN
        UPDATE phieuxuat 
        SET NgayXuat = NOW()
        WHERE DonHang_id = NEW.DonHang_id;
    END IF;
END$$

DELIMITER ;






  -- Tính trực tiếp không cần quét lại toàn bộ

  INSERT INTO `thuonghieu` (`Ma_thuonghieu`, `Ten_thuonghieu`, `slug`) VALUES
  (1, 'Li-Ning', 'li-ning'),
  (2, 'Yonex', 'yonex'),
  (3, 'Victor', 'victor'),
  (4, 'Venson', 'venson'),
  (5, 'Mizuno', 'mizuno'),
  (6, 'Kumpoo', 'kumpoo');


  INSERT INTO `danhmuc` (`Ten_danhmuc`, `slug`) VALUES
  ('Vợt cầu lông', 'vot-cau-long'),
  ('Phụ kiện', 'phu-kien'),
  ('Vợt Pickleball', 'vot-pickleball');

 INSERT INTO `sanpham` (`SanPham_id`, `TenSP`, `Danhmuc_id`, `NCC_id`, `Ma_thuonghieu`, `MoTa`, `image_url`, `GiaNhapTB`, `GiaBan`, `PhanTramLoiNhuan`, `TrangThai`, `TaoNgay`, `SoLuongTon`, `CanhBaoTon`) VALUES
(1, 'Yonex Astrox 100ZZ', 1, 1, 2, 'đắt vãi ò', 'img/sanpham/Vot-cau-long-Yonex-Astrox-100ZZ.png', 3200000, 3680000.00, 0.15, 1, '2026-03-20 22:16:21', 10, 5),
(2, 'Li-Ning Halbertec 9000', 1, 2, 1, 'đắt vãi ò', 'img/sanpham/vot-cau-long-li-ning-halbertec-9000.png', 2500000, 2875000.00, 0.15, 1, '2026-03-20 22:16:21', 15, 5),
(3, 'Victor Thruster F Enhanced', 1, 2, 3, 'đắt vãi ò', 'img/sanpham/vot-cau-long-victor-thruster-ryuga.png', 2800000, 3220000.00, 0.15, 1, '2026-03-20 22:16:21', 8, 5),
(4, 'Yonex Nanoflare 1000 game', 1, 3, 2, 'đắt vãi ò', 'img/sanpham/vot-cau-long-yonex-nanoflare-1000-game.png', 3100000, 3565000.00, 0.15, 1, '2026-03-20 22:16:21', 12, 5),
(5, 'Li-Ning Aeronaut 9000', 1, 2, 1, 'đắt vãi ò', 'img/sanpham/aeronaut-9000i.png', 2900000, 3335000.00, 0.15, 1, '2026-03-20 22:16:21', 7, 5),
(6, 'Quả cầu lông Yonex AS50', 2, 3, 2, 'đắt vãi ò', 'img/sanpham/ong-cau-yonex-as50-speed-2.png', 450000, 517000.00, 0.15, 1, '2026-03-20 22:16:21', 50, 5),
(7, 'Quả cầu lông Hải Yến S70', 2, 1, 3, 'đắt vãi ò', 'img/sanpham/ong-cau-long-hai-yen-s70.png', 180000, 207000.00, 0.15, 1, '2026-03-20 22:16:21', 10, 5),
(8, 'Quả cầu lông vinastar', 2, 2, 1, 'đắt vãi ò', 'img/sanpham/ong-cau-vina-start-xanh.png', 322188, 371000.00, 0.15, 1, '2026-03-20 22:16:21', 160, 5),
(9, 'Quả cầu Li-ning AYQN024-4', 2, 3, 3, 'đắt vãi ò', 'img/sanpham/cau-lining.png', 320000, 368000.00, 0.15, 1, '2026-03-20 22:16:21', 45, 5),
(10, 'Vợt Pickleball Selkirk Vanguard', 3, 2, 3, 'đắt vãi ò', 'img/sanpham/vot-pickleball-selkirk-luxx-control-air.png', 4500000, 5175000.00, 0.15, 1, '2026-03-20 22:16:21', 5, 5),
(11, 'Vợt Pickleball Joola Perseus', 3, 1, 2, 'đắt vãi ò', 'img/sanpham/perseus-pro-v-ben-johns-blaze-red.png', 5200000, 5980000.00, 0.15, 1, '2026-03-20 22:16:21', 4, 5),
(12, 'Vợt Pickleball JOOLA Ben Johns', 3, 2, 1, 'đắt vãi ò', 'img/sanpham/joola-ben-johns-hyperion.png', 2100000, 2415000.00, 0.15, 1, '2026-03-20 22:16:21', 10, 5),
(13, 'Vợt Pickleball Soxter Impact', 3, 3, 3, 'đắt vãi ò', 'img/sanpham/vot-pickleball-soxter-impact-pro-2.png', 3800000, 4370000.00, 0.15, 1, '2026-03-20 22:16:21', 6, 5),
(14, 'Vợt cầu lông 1000z', 1, 1, 2, 'Vợt cầu lông Yonex Nanoflare 1000Z có vùng Sweet spot khá rộng, giúp bạn có thể tập trung vào việc tăng cường sức mạnh và độ chính xác của các cú đánh. Điểm nổi bật nhất ở phiên bản mới này nằm ở thiết kế khung vợt khi Yonex tích hợp hai loại Aero Frame và Compact Frame', 'img/sanpham/PROD-20260328130506-69c7c3f2c9cab.webp', 1500000, 1695000.00, 0.13, 0, '2026-03-28 19:05:06', 20, 5),
(15, 'Vợt cầu lông yonex 100zz VA', 1, 1, 2, 'Vợt cầu lông Yonex Astrox 100ZZ VA là phiên bản đặc biệt “VA Signature” của dòng Astrox 100ZZ từ Yonex - cây vợt chuyên nghiệp này được thiết kế riêng theo phong cách cá nhân của vận động viên Viktor Axelsen. Nó thể hiện rõ phương châm \"Chúng ta cùng nhau phấn đấu\" của nhà vô địch Olympic. Vợt với màu sắc trắng xanh này là phiên bản giới hạn của mẫu vợt chủ lực thuộc dòng Astrox, với độ cân bằng cao ở đầu vợt và cán vợt cực kỳ cứng cáp, lý tưởng cho những người chơi tìm kiếm sức mạnh và độ chính xác tối đa.', 'img/sanpham/PROD-20260328131037-69c7c53d540ea.webp', 400000, 520000.00, 0.30, 1, '2026-03-28 19:10:37', 30, 5),
(16, 'Balo Cầu Lông Lining P-ABSU401-1 chính hãng', 1, 3, 1, 'Balo Cầu Lông Lining P-ABSU401-1 nổi bật với tông màu đen chủ đạo cho cái nhìn sang trọng với kiểu dáng thể thao, trẻ trung năng động phù hợp mọi hoàn cảnh từ đem tới sân cầu cho tới đem đi du lịch với nhiều túi nhỏ tiện dụng để đựng điện thoại, phụ kiện, đồ dùng cá nhân.', '1774703260_69c7d29c36e15.webp', 1000000, 1900000.00, 0.90, 1, '2026-03-28 20:00:08', 10, 5),
(17, 'Balo cầu lông Lining P-ABSV133-1 chính hãng', 2, 3, 1, '- Balo cầu lông Lining P-ABSV133-1 được thiết kế dành riêng cho người chơi cầu lông phong trào, học sinh, sinh viên và người trẻ yêu thích phong cách thể thao, unisex hiện đại. Với mức giá hợp lý, đây là một lựa chọn đáng cân nhắc trong phân khúc balo cầu lông tầm trung.', 'img/sanpham/PROD-20260328140900-69c7d2ec3ebf5.webp', 900000, 1080000.00, 0.20, 1, '2026-03-28 20:09:00', 10, 5),
(18, 'Dây cước căng vợt Yonex BG 80 Power', 2, 1, 3, '- Dây cước căng vợt Yonex BG 80 Power là loại cước với độ cứng cao, độ nảy tốt được khá nhiều các vận động viên thế giới sử dụng. Ngoài ra, cước được phủ một lớp phức hợp Titanium bên ngoài cho độ nhám ở bề mặt kết hợp cùng đường kính dây 0.68mm đảm bảo trong lúc sử dụng dây ít bị chạy và tụt kg tạo ra độ bền nhất định.', 'img/sanpham/PROD-20260328141835-69c7d52ba34e7.webp', 200000, 240000.00, 0.20, 0, '2026-03-28 20:18:35', 20, 5),
(19, 'Túi cầu lông Yonex BAG2326T02', 2, 1, 2, '- Được làm bằng chất liệu cao cấp đảm bảo độ bền, chống thấm hiệu quả, dễ dàng vệ sinh. Thiết kế với màu xanh cùng với các họa tiết được làm tỉ mỉ tạo nên phong cách nổi bật, năng động và hiện đại, có thể sử dụng trong các chuyến đi dã ngoại hoặc du lịch vô cùng tiện lợi.', 'img/sanpham/PROD-20260328142145-69c7d5e91603e.webp', 5000000, 5500000.00, 0.10, 1, '2026-03-28 20:21:45', 19, 5);

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

  -- === 1. Cập nhật bảng donhang ===
  ALTER TABLE `donhang`
    MODIFY `TongTien` DECIMAL(15,2) DEFAULT NULL,        -- Đổi từ INT sang DECIMAL để khớp với giá tiền
    MODIFY `NgayDat` DATETIME DEFAULT CURRENT_TIMESTAMP, -- Thêm giờ để theo dõi chính xác
    MODIFY `PhuongThucTT` ENUM('cod','banking') DEFAULT 'cod',
    ADD COLUMN `GhiChu` TEXT DEFAULT NULL AFTER `linkTraCuu`,  -- Thêm ghi chú đơn hàng
    ADD COLUMN `NgayCapNhat` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `NgayDat`;

  -- === 2. Cập nhật bảng chitiethoadon ===
  ALTER TABLE `chitiethoadon`
    MODIFY `Gia` DECIMAL(15,2) NOT NULL,                  -- Đảm bảo giá không NULL
    MODIFY `SoLuong` INT UNSIGNED NOT NULL DEFAULT 1,     -- Số lượng luôn >= 1
    ADD COLUMN `ThanhTien` DECIMAL(15,2) GENERATED ALWAYS AS (`SoLuong` * `Gia`) STORED AFTER `Gia`, -- Tự tính thành tiền
    ADD INDEX `idx_donhang` (`DonHang_id`),
    ADD INDEX `idx_sanpham` (`SanPham_id`);

  -- === 3. Thêm foreign key nếu chưa có ===
  ALTER TABLE `donhang`
    ADD CONSTRAINT `fk_donhang_user` FOREIGN KEY (`User_id`) REFERENCES `users`(`User_id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_donhang_diachi` FOREIGN KEY (`DiaChi_id`) REFERENCES `diachigh`(`add_id`) ON DELETE SET NULL;

  ALTER TABLE `chitiethoadon`
    ADD CONSTRAINT `fk_ctdh_donhang` FOREIGN KEY (`DonHang_id`) REFERENCES `donhang`(`DonHang_id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_ctdh_sanpham` FOREIGN KEY (`SanPham_id`) REFERENCES `sanpham`(`SanPham_id`) ON DELETE RESTRICT;

  UPDATE sanpham sp
  SET 
      GiaNhapTB = fn_GiaNhapTB(sp.SanPham_id),
      SoLuongTon = (SELECT IFNULL(SUM(SoLuong), 0) FROM chitietphieunhap WHERE SanPham_id = sp.SanPham_id),
      GiaBan = fn_round_500(fn_GiaNhapTB(sp.SanPham_id) * (1 + sp.PhanTramLoiNhuan));

  /*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
  /*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
  /*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
-- Thêm cột CanhBaoTon vào bảng sanpham

-- =====================================
-- 1. THÊM CỘT image_url VÀO BẢNG danhmuc
-- =====================================
ALTER TABLE `danhmuc` 
ADD COLUMN `image_url` varchar(255) DEFAULT NULL 
COMMENT 'Đường dẫn hình danh mục' 
AFTER `slug`;

-- =====================================
-- 2. THÊM CỘT image_url VÀO BẢNG thuonghieu
-- =====================================
ALTER TABLE `thuonghieu` 
ADD COLUMN `image_url` varchar(255) DEFAULT NULL 
COMMENT 'Đường dẫn logo thương hiệu' 
AFTER `slug`;

-- =====================================
-- 3. CẬP NHẬT image_url CHO DANH MỤC
-- =====================================
UPDATE `danhmuc` SET `image_url` = 'img/icons/logo-caulong.png' WHERE `slug` = 'vot-cau-long';
UPDATE `danhmuc` SET `image_url` = 'img/icons/logo-pickleball.png' WHERE `slug` = 'vot-pickleball';
UPDATE `danhmuc` SET `image_url` = 'img/icons/logo-phukien.png' WHERE `slug` = 'phu-kien';

-- =====================================
-- 4. CẬP NHẬT image_url CHO THƯƠNG HIỆU
-- =====================================
-- Cầu lông brands
UPDATE `thuonghieu` SET `image_url` = 'img/icons/logo-yonex.webp' WHERE `slug` = 'yonex';
UPDATE `thuonghieu` SET `image_url` = 'img/icons/Logo-li-ning.webp' WHERE `slug` = 'li-ning';
UPDATE `thuonghieu` SET `image_url` = 'img/icons/logo-victor.png' WHERE `slug` = 'victor';
UPDATE `thuonghieu` SET `image_url` = 'img/icons/logo-adidas.webp' WHERE `slug` = 'adidas';
UPDATE `thuonghieu` SET `image_url` = 'img/icons/logo-kamito.png' WHERE `slug` = 'kamito';
UPDATE `thuonghieu` SET `image_url` = 'img/icons/logo-mizuno.png' WHERE `slug` = 'mizuno';
UPDATE `thuonghieu` SET `image_url` = 'img/icons/logo-kumpoo.png' WHERE `slug` = 'kumpoo';
UPDATE `thuonghieu` SET `image_url` = 'img/icons/logo-venson.png' WHERE `slug` = 'venson';

-- Pickleball brands
UPDATE `thuonghieu` SET `image_url` = 'img/icons/logo-joola.png' WHERE `slug` = 'joola';
UPDATE `thuonghieu` SET `image_url` = 'img/icons/logo-selkirk.webp' WHERE `slug` = 'selkirk';
UPDATE `thuonghieu` SET `image_url` = 'img/icons/logo-wika.png' WHERE `slug` = 'wika';

-- =====================================
-- 5. THÊM THƯƠNG HIỆU MỚI (NẾU CHƯA CÓ)
-- =====================================
INSERT INTO `thuonghieu` (`Ten_thuonghieu`, `slug`, `image_url`) 
VALUES 
('Adidas', 'adidas', 'img/icons/logo-adidas.webp'),
('Kamito', 'kamito', 'img/icons/logo-kamito.png'),
('Mizuno', 'mizuno', 'img/icons/logo-mizuno.png'),
('Kumpoo', 'kumpoo', 'img/icons/logo-kumpoo.png'),
('Venson', 'venson', 'img/icons/logo-venson.png'),
('Joola', 'joola', 'img/icons/logo-joola.png'),
('Selkirk', 'selkirk', 'img/icons/logo-selkirk.webp'),
('Wika', 'wika', 'img/icons/logo-wika.png')
ON DUPLICATE KEY UPDATE 
    `slug` = VALUES(`slug`),
    `image_url` = VALUES(`image_url`);


-- =============================================
-- XÓA CÁC THƯƠNG HIỆU CŨ (NẾU CÓ) ĐỂ ĐẢM BẢO CHỈ CÒN 11 BRAND
-- =============================================
DELETE FROM `thuonghieu` 
WHERE `Ma_thuonghieu` NOT IN (1,2,3,4,5,6,7,8,12,13,14);

-- =============================================
-- RESET AUTO_INCREMENT (TUỲ CHỌN, ĐỂ TRÁNH ID BỊ NHẢY)
-- =============================================
ALTER TABLE `thuonghieu` AUTO_INCREMENT = 15;

-- =============================================
-- INSERT / CẬP NHẬT 11 THƯƠNG HIỆU CHÍNH XÁC
-- =============================================
INSERT INTO `thuonghieu` (`Ma_thuonghieu`, `Ten_thuonghieu`, `slug`, `image_url`) VALUES
(1, 'Li-Ning',   'li-ning',   'img/icons/Logo-li-ning.webp'),
(2, 'Yonex',     'yonex',     'img/icons/logo-yonex.webp'),
(3, 'Victor',    'victor',    'img/icons/logo-victor.png'),
(4, 'Venson',    'venson',    'img/icons/logo-venson.png'),
(5, 'Mizuno',    'mizuno',    'img/icons/logo-mizuno.png'),
(6, 'Kumpoo',    'kumpoo',    'img/icons/logo-kumpoo.png'),
(7, 'Adidas',    'adidas',    'img/icons/logo-adidas.webp'),
(8, 'Kamito',    'kamito',    'img/icons/logo-kamito.png'),
(12,'Joola',     'joola',     'img/icons/logo-joola.png'),
(13,'Selkirk',   'selkirk',   'img/icons/logo-selkirk.webp'),
(14,'Wika',      'wika',      'img/icons/logo-wika.png')
ON DUPLICATE KEY UPDATE
    `Ten_thuonghieu` = VALUES(`Ten_thuonghieu`),
    `slug`           = VALUES(`slug`),
    `image_url`      = VALUES(`image_url`);
-- =====================================
-- CẬP NHẬT THƯƠNG HIỆU CHO SẢN PHẨM
-- =====================================
UPDATE `sanpham` SET `Ma_thuonghieu` = 12 WHERE `SanPham_id` IN (11, 12);
UPDATE `sanpham` SET `Ma_thuonghieu` = 13 WHERE `SanPham_id` IN (10, 13);