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

INSERT INTO `danhmuc` (`Danhmuc_id`, `Ten_danhmuc`, `slug`) VALUES
(4, 'Vợt cầu lông', 'vot-cau-long'),
(5, 'Phụ kiện', 'phu-kien'),
(6, 'Vợt Pickleball', 'vot-pickleball');

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

INSERT INTO `phieunhap` (`NhapHang_id`, `NguoiNhap`, `NgayNhap`, `SoLuong`) VALUES
(11, 'user', '2026-03-23', 0),
(12, 'user', '2026-03-23', 0),
(13, 'user', '2026-03-23', 0),
(14, 'user', '2026-03-23', 0),
(15, 'user', '2026-03-23', 0);

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
  `GiaBan` decimal(15,2) GENERATED ALWAYS AS (
    `GiaNhapTB` * (1 + `PhanTramLoiNhuan`)
) STORED,
  `PhanTramLoiNhuan` decimal(5,2) DEFAULT NULL,
  `TrangThai` tinyint(1) DEFAULT 1,
  `TaoNgay` datetime DEFAULT current_timestamp(),
  `SoLuongTon` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sanpham`
--

INSERT INTO `sanpham` (`SanPham_id`, `TenSP`, `Danhmuc_id`, `NCC_id`, `Ma_thuonghieu`, `MoTa`, `image_url`, `GiaNhapTB`, `GiaBan`, `PhanTramLoiNhuan`, `TrangThai`, `TaoNgay`, `SoLuongTon`) VALUES
(1, 'Yonex Astrox 100ZZ', 4, 4, 2, 'đắt vãi ò', '/img/sanpham/Vot-cau-long-Yonex-Astrox-100ZZ.png', 3200000, NULL , 0.15, 1, '2026-03-20 22:16:21', 10),
(2, 'Li-Ning Halbertec 9000', 4, 5, 1, 'đắt vãi ò', '/img/sanpham/vot-cau-long-li-ning-halbertec-9000.png', 2500000, NULL , 0.15, 1, '2026-03-20 22:16:21', 15),
(3, 'Victor Thruster F Enhanced', 4, 6, 3, 'đắt vãi ò', '/img/sanpham/vot-cau-long-victor-thruster-ryuga.png', 2800000,NULL, 0.15, 1, '2026-03-20 22:16:21', 8),
(4, 'Yonex Nanoflare 1000 game', 4, 4, 2, 'đắt vãi ò', '/img/sanpham/vot-cau-long-yonex-nanoflare-1000-game.png', 3100000, NULL , 0.15, 1, '2026-03-20 22:16:21', 12),
(5, 'Li-Ning Aeronaut 9000', 4, 5, 1, 'đắt vãi ò', '/img/sanpham/aeronaut-9000i.png', 2900000, NULL , 0.15, 1, '2026-03-20 22:16:21', 7),
(6, 'Quả cầu lông Yonex AS50', 5, 4, 2, 'đắt vãi ò', '/img/sanpham/ong-cau-yonex-as50-speed-2.png', 450000,NULL , 0.15, 1, '2026-03-20 22:16:21', 50),
(7, 'Quả cầu lông Hải Yến S70', 5, 6, 3, 'đắt vãi ò', '/img/sanpham/ong-cau-long-hai-yen-s70.png', 180000,NULL , 0.15, 1, '2026-03-20 22:16:21', 100),
(8, 'Quả cầu lông vinastar', 5, 6, 1, 'đắt vãi ò', '/img/sanpham/ong-cau-vina-start-xanh.png', 350000,NULL , 0.15, 1, '2026-03-20 22:16:21', 60),
(9, 'Quả cầu Li-ning AYQN024-4', 5, 5, 3, 'đắt vãi ò', '/img/sanpham/cau-lining.png', 320000,NULL , 0.15, 1, '2026-03-20 22:16:21', 45),
(10, 'Vợt Pickleball Selkirk Vanguard', 6, 6, 3, 'đắt vãi ò', '/img/sanpham/vot-pickleball-selkirk-luxx-control-air.png', 4500000,NULL , 0.15, 1, '2026-03-20 22:16:21', 5),
(11, 'Vợt Pickleball Joola Perseus', 6, 6, 2, 'đắt vãi ò', '/img/sanpham/perseus-pro-v-ben-johns-blaze-red.png', 5200000,NULL , 0.15, 1, '2026-03-20 22:16:21', 4),
(12, 'Vợt Pickleball JOOLA Ben Johns', 6, 6, 1, 'đắt vãi ò', '/img/sanpham/joola-ben-johns-hyperion.png', 2100000, NULL, 0.15, 1, '2026-03-20 22:16:21', 10),
(13, 'Vợt Pickleball Soxter Impact', 6, 6, 3, 'đắt vãi ò', '/img/sanpham/vot-pickleball-soxter-impact-pro-2.png', 3800000,NULL , 0.15, 1, '2026-03-20 22:16:21', 6),
(14, 'Hoàng chou', 5, 6, 3, 'sjdflsflsjlfksd', '/img/products/PROD-20260323134545-69c135f98a177.jpg', NULL, NULL, 20.00, 1, '2026-03-23 19:45:45', 0);

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

INSERT INTO `users` (`User_id`, `Username`, `password`, `Ho_ten`, `email`, `SDT`, `role`, `status`, `created_at`) VALUES
(1, 'user', '$2y$10$WyfbWCYPDFLPz2HbRfYDa.POvoakT/E71k.3Qhbe2Fay/NAx0ZH3i', NULL, NULL, NULL, 1, '1', '2026-03-20 22:30:01'),
(3, 'Tisdoo', '$2y$10$KVCLdId9zeX.m9V6n.KSBuIAhB3dHadrgsIs.o7q3sdNr54kMgZUq', 'hoàng ấn', 'bodow@gmail.com', '0598898588', 0, '1', '2026-03-23 10:47:04'),
(4, 'beiu', '$2y$10$amSCnKvV/3fwmiwpx8GO9ui9YfkXdt3W4qZCXi/BQEpwaSEF50Lhy', 'hoàng ấn', 'bodowq@gmail.com', '0598898588', 0, '1', '2026-03-23 10:49:34');

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
  MODIFY `ChiTiet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `chitietphieuxuat`
--
ALTER TABLE `chitietphieuxuat`
  MODIFY `ChiTiet_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `danhmuc`
--
ALTER TABLE `danhmuc`
  MODIFY `Danhmuc_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  MODIFY `NCC_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `phieunhap`
--
ALTER TABLE `phieunhap`
  MODIFY `NhapHang_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `phieuxuat`
--
ALTER TABLE `phieuxuat`
  MODIFY `PhieuXuat_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sanpham`
--
ALTER TABLE `sanpham`
  MODIFY `SanPham_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `sp_tronggiohang`
--
ALTER TABLE `sp_tronggiohang`
  MODIFY `SP_GioHang_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `thuonghieu`
--
ALTER TABLE `thuonghieu`
  MODIFY `Ma_thuonghieu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tracuutonkho`
--
ALTER TABLE `tracuutonkho`
  MODIFY `TraCuu_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `User_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
