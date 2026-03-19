SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS b01_nhahodau
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE b01_nhahodau;

--
-- Table structure for table `chitiethoadon`
--
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
  `Ten_danhmuc` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -------------------------------------------------------

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
-- Table structure for table `phieunhap`
--
CREATE TABLE `phieunhap` (
  `NhapHang_id` int(11) NOT NULL,
  `NCC_id` int(11) DEFAULT NULL,
  `NguoiNhap` varchar(100) DEFAULT NULL,
  `NgayNhap` date DEFAULT NULL,
  `SoLuong` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `GiaBan` decimal(15,2) DEFAULT NULL,
  `PhanTramLoiNhuan` decimal(5,2) DEFAULT NULL,
  `TrangThai` tinyint(1) DEFAULT 1,  -- Sửa ở đây: chuyển từ varchar sang tinyint(1)
  `TaoNgay` datetime DEFAULT current_timestamp(),
  `SoLuongTon` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `Ten_thuonghieu` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `role` boolean DEFAULT 0, 
  `status` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  ADD PRIMARY KEY (`NhapHang_id`),
  ADD KEY `NCC_id` (`NCC_id`);

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
ALTER TABLE `chitiethoadon`
  MODIFY `ChiTietDonHang_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `chitietphieunhap`
  MODIFY `ChiTiet_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `chitietphieuxuat`
  MODIFY `ChiTiet_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `danhmuc`
  MODIFY `Danhmuc_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `diachigh`
  MODIFY `add_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `donhang`
  MODIFY `DonHang_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `giohang`
  MODIFY `GioHang_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `nhacungcap`
  MODIFY `NCC_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `phieunhap`
  MODIFY `NhapHang_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `phieuxuat`
  MODIFY `PhieuXuat_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `sanpham`
  MODIFY `SanPham_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `sp_tronggiohang`
  MODIFY `SP_GioHang_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `thuonghieu`
  MODIFY `Ma_thuonghieu` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `tracuutonkho`
  MODIFY `TraCuu_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `User_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--
ALTER TABLE `chitiethoadon`
  ADD CONSTRAINT `chitiethoadon_ibfk_1` FOREIGN KEY (`DonHang_id`) REFERENCES `donhang` (`DonHang_id`),
  ADD CONSTRAINT `chitiethoadon_ibfk_2` FOREIGN KEY (`SanPham_id`) REFERENCES `sanpham` (`SanPham_id`);

ALTER TABLE `chitietphieunhap`
  ADD CONSTRAINT `chitietphieunhap_ibfk_1` FOREIGN KEY (`PhieuNhap_id`) REFERENCES `phieunhap` (`NhapHang_id`),
  ADD CONSTRAINT `chitietphieunhap_ibfk_2` FOREIGN KEY (`SanPham_id`) REFERENCES `sanpham` (`SanPham_id`);

ALTER TABLE `chitietphieuxuat`
  ADD CONSTRAINT `chitietphieuxuat_ibfk_1` FOREIGN KEY (`PhieuXuat_id`) REFERENCES `phieuxuat` (`PhieuXuat_id`),
  ADD CONSTRAINT `chitietphieuxuat_ibfk_2` FOREIGN KEY (`SP_id`) REFERENCES `sanpham` (`SanPham_id`);

ALTER TABLE `diachigh`
  ADD CONSTRAINT `diachigh_ibfk_1` FOREIGN KEY (`User_id`) REFERENCES `users` (`User_id`);

ALTER TABLE `donhang`
  ADD CONSTRAINT `donhang_ibfk_1` FOREIGN KEY (`User_id`) REFERENCES `users` (`User_id`),
  ADD CONSTRAINT `donhang_ibfk_2` FOREIGN KEY (`DiaChi_id`) REFERENCES `diachigh` (`add_id`);

ALTER TABLE `giohang`
  ADD CONSTRAINT `giohang_ibfk_1` FOREIGN KEY (`User_id`) REFERENCES `users` (`User_id`);

ALTER TABLE `phieunhap`
  ADD CONSTRAINT `phieunhap_ibfk_1` FOREIGN KEY (`NCC_id`) REFERENCES `nhacungcap` (`NCC_id`);

ALTER TABLE `phieuxuat`
  ADD CONSTRAINT `phieuxuat_ibfk_1` FOREIGN KEY (`DonHang_id`) REFERENCES `donhang` (`DonHang_id`),
  ADD CONSTRAINT `phieuxuat_ibfk_2` FOREIGN KEY (`NguoiXuat_id`) REFERENCES `users` (`User_id`);

ALTER TABLE `sanpham`
  ADD CONSTRAINT `sanpham_ibfk_1` FOREIGN KEY (`Danhmuc_id`) REFERENCES `danhmuc` (`Danhmuc_id`),
  ADD CONSTRAINT `sanpham_ibfk_2` FOREIGN KEY (`NCC_id`) REFERENCES `nhacungcap` (`NCC_id`),
  ADD CONSTRAINT `sanpham_ibfk_3` FOREIGN KEY (`Ma_thuonghieu`) REFERENCES `thuonghieu` (`Ma_thuonghieu`);

ALTER TABLE `sp_tronggiohang`
  ADD CONSTRAINT `sp_tronggiohang_ibfk_1` FOREIGN KEY (`GioHang_id`) REFERENCES `giohang` (`GioHang_id`),
  ADD CONSTRAINT `sp_tronggiohang_ibfk_2` FOREIGN KEY (`SanPham_id`) REFERENCES `sanpham` (`SanPham_id`);

ALTER TABLE `tracuutonkho`
  ADD CONSTRAINT `tracuutonkho_ibfk_1` FOREIGN KEY (`SP_id`) REFERENCES `sanpham` (`SanPham_id`);



  -- Thêm cột slug cho danh mục
ALTER TABLE `danhmuc` ADD COLUMN `slug` VARCHAR(100) AFTER `Ten_danhmuc`;

-- Cập nhật slug cho danh mục hiện có
UPDATE `danhmuc` SET `slug` = 'vot-cau-long' WHERE `Danhmuc_id` = 4;
UPDATE `danhmuc` SET `slug` = 'phu-kien' WHERE `Danhmuc_id` = 5;
UPDATE `danhmuc` SET `slug` = 'vot-pickleball' WHERE `Danhmuc_id` = 6;

-- Thêm cột slug cho thương hiệu
ALTER TABLE `thuonghieu` ADD COLUMN `slug` VARCHAR(100) AFTER `Ten_thuonghieu`;

COMMIT;