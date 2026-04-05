-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 31, 2026 at 09:40 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12


SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
  CREATE DATABASE IF NOT EXISTS b01_nhahodau
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

  USE b01_nhahodau;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `b01_nhahodau`
--

DELIMITER $$
--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_GiaNhapTB` (`p_SanPham_id` INT) RETURNS DECIMAL(15,2) DETERMINISTIC READS SQL DATA BEGIN
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

CREATE DEFINER=`root`@`localhost` FUNCTION `fn_round_500` (`price` DECIMAL(15,2)) RETURNS DECIMAL(15,2) DETERMINISTIC BEGIN
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

-- --------------------------------------------------------

--
-- Table structure for table `chitiethoadon`
--

CREATE TABLE `chitiethoadon` (
  `ChiTietDonHang_id` int(11) NOT NULL,
  `DonHang_id` int(11) DEFAULT NULL,
  `SanPham_id` int(11) DEFAULT NULL,
  `SoLuong` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `Gia` decimal(15,2) NOT NULL,
  `ThanhTien` decimal(15,2) GENERATED ALWAYS AS (`SoLuong` * `Gia`) STORED
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

--
-- Dumping data for table `chitietphieunhap`
--

INSERT INTO `chitietphieunhap` (`ChiTiet_id`, `PhieuNhap_id`, `SanPham_id`, `SoLuong`, `Gia_Nhap`, `MaLoHang`) VALUES
(1, 1, 1, 10, 3200000.00, 'LH001'),
(2, 2, 2, 10, 2500000.00, 'LH002'),
(3, 3, 3, 10, 2800000.00, 'LH003'),
(4, 4, 4, 10, 3100000.00, 'LH004'),
(5, 5, 5, 10, 2900000.00, 'LH005'),
(6, 6, 6, 10, 450000.00, 'LH006'),
(7, 7, 7, 10, 180000.00, 'LH007'),
(8, 8, 8, 10, 322188.00, 'LH008'),
(9, 9, 9, 10, 320000.00, 'LH009'),
(10, 10, 10, 10, 4500000.00, 'LH010'),
(11, 11, 11, 10, 5200000.00, 'LH011'),
(12, 12, 12, 10, 2100000.00, 'LH012'),
(13, 13, 13, 10, 3800000.00, 'LH013'),
(14, 14, 14, 10, 1500000.00, 'LH014'),
(15, 15, 15, 10, 400000.00, 'LH015'),
(16, 16, 16, 10, 1000000.00, 'LH016'),
(17, 17, 17, 10, 900000.00, 'LH017'),
(18, 18, 18, 10, 200000.00, 'LH018'),
(19, 19, 19, 10, 5000000.00, 'LH019'),
(20, 20, 25, 20, 200000.00, 'LOT-20260331-USE-020'),
(21, 20, 23, 15, 200000.00, 'LOT-20260331-USE-020'),
(22, 21, 24, 12, 150000.00, 'LOT-20260331-USE-021'),
(23, 21, 22, 13, 215000.00, 'LOT-20260331-USE-021'),
(24, 21, 27, 9, 70000.00, 'LOT-20260331-USE-021'),
(25, 22, 20, 20, 600000.00, 'LOT-20260331-USE-022'),
(26, 23, 21, 25, 2000000.00, 'LOT-20260331-USE-023'),
(27, 23, 26, 100, 50000.00, 'LOT-20260331-USE-023');

--
-- Triggers `chitietphieunhap`
--
DELIMITER $$
CREATE TRIGGER `trg_chitietphieunhap_delete` AFTER DELETE ON `chitietphieunhap` FOR EACH ROW BEGIN
    UPDATE phieunhap
    SET SoLuong = (
        SELECT IFNULL(SUM(SoLuong), 0)
        FROM chitietphieunhap
        WHERE PhieuNhap_id = OLD.PhieuNhap_id
    )
    WHERE NhapHang_id = OLD.PhieuNhap_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_chitietphieunhap_insert` AFTER INSERT ON `chitietphieunhap` FOR EACH ROW BEGIN
    UPDATE phieunhap
    SET SoLuong = (
        SELECT IFNULL(SUM(SoLuong), 0)
        FROM chitietphieunhap
        WHERE PhieuNhap_id = NEW.PhieuNhap_id
    )
    WHERE NhapHang_id = NEW.PhieuNhap_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_chitietphieunhap_update` AFTER UPDATE ON `chitietphieunhap` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;

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
  `slug` varchar(100) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL COMMENT 'Đường dẫn hình danh mục'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `danhmuc`
--

INSERT INTO `danhmuc` (`Danhmuc_id`, `Ten_danhmuc`, `slug`, `image_url`) VALUES
(1, 'Vợt cầu lông', 'vot-cau-long', 'img/icons/logo-caulong.png'),
(2, 'Phụ kiện', 'phu-kien', 'https://nvbplay.vn/wp-content/uploads/2024/10/customer-service-No.svg'),
(3, 'Vợt Pickleball', 'vot-pickleball', 'img/icons/logo-pickleball.png'),
(4, 'Ba lô', 'ba-l', 'img/icons/CAT-20260331084725-69cb6dfd16cbf.png');

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
  `PhuongThucTT` enum('cod','banking') DEFAULT 'cod',
  `TongTien` decimal(15,2) DEFAULT NULL,
  `NgayDat` datetime DEFAULT current_timestamp(),
  `NgayCapNhat` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `TrangThai` tinyint(1) DEFAULT 0,
  `linkTraCuu` varchar(255) DEFAULT NULL,
  `GhiChu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `donhang`
--
DELIMITER $$
CREATE TRIGGER `trg_donhang_cancelled` AFTER UPDATE ON `donhang` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_donhang_confirmed` AFTER UPDATE ON `donhang` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_donhang_delivered` AFTER UPDATE ON `donhang` FOR EACH ROW BEGIN
    IF NEW.TrangThai = 2 AND OLD.TrangThai != 2 THEN
        UPDATE phieuxuat 
        SET NgayXuat = NOW()
        WHERE DonHang_id = NEW.DonHang_id;
    END IF;
END
$$
DELIMITER ;

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
  `SoLuong` int(11) DEFAULT NULL,
  `TrangThai` enum('pending','completed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `phieunhap`
--

INSERT INTO `phieunhap` (`NhapHang_id`, `NguoiNhap`, `NgayNhap`, `SoLuong`, `TrangThai`) VALUES
(1, 'user', '2026-03-31', 10, 'completed'),
(2, 'user', '2026-03-31', 10, 'completed'),
(3, 'user', '2026-03-31', 10, 'completed'),
(4, 'user', '2026-03-31', 10, 'completed'),
(5, 'user', '2026-03-31', 10, 'completed'),
(6, 'user', '2026-03-31', 10, 'completed'),
(7, 'user', '2026-03-31', 10, 'completed'),
(8, 'user', '2026-03-31', 10, 'completed'),
(9, 'user', '2026-03-31', 10, 'completed'),
(10, 'user', '2026-03-31', 10, 'completed'),
(11, 'user', '2026-03-31', 10, 'completed'),
(12, 'user', '2026-03-31', 10, 'completed'),
(13, 'user', '2026-03-31', 10, 'completed'),
(14, 'user', '2026-03-31', 10, 'completed'),
(15, 'user', '2026-03-31', 10, 'completed'),
(16, 'user', '2026-03-31', 10, 'completed'),
(17, 'user', '2026-03-31', 10, 'completed'),
(18, 'user', '2026-03-31', 10, 'completed'),
(19, 'user', '2026-03-31', 10, 'completed'),
(20, 'user', '2026-03-31', 35, 'completed'),
(21, 'user', '2026-03-31', 34, 'completed'),
(22, 'user', '2026-03-31', 20, 'completed'),
(23, 'user', '2026-03-31', 125, 'completed');

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
  `TrangThai` tinyint(1) DEFAULT 1,
  `TaoNgay` datetime DEFAULT current_timestamp(),
  `SoLuongTon` int(11) DEFAULT 0,
  `CanhBaoTon` int(11) DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sanpham`
--

INSERT INTO `sanpham` (`SanPham_id`, `TenSP`, `Danhmuc_id`, `NCC_id`, `Ma_thuonghieu`, `MoTa`, `image_url`, `GiaNhapTB`, `GiaBan`, `PhanTramLoiNhuan`, `TrangThai`, `TaoNgay`, `SoLuongTon`, `CanhBaoTon`) VALUES
(1, 'Vợt cầu lông Yonex Astrox 100ZZ navy', 1, 1, 2, 'Vợt cầu lông Yonex Astrox 100ZZ có khung vợt hình vuông ISOMETRIC được thiết kế để giữ chiều dài dây dọc và dây ngang tương đồng, tạo ra điểm ngọt (sweet spot) mở rộng theo mọi hướng. Công nghệ ISOMETRIC tiêu chuẩn mở rộng 4 góc khung vợt,', 'img/sanpham/Vot-cau-long-Yonex-Astrox-100ZZ.png', 3200000, 3680000.00, 0.15, 1, '2026-03-20 22:16:21', 10, 5),
(2, 'Vợt cầu lông Li-Ning Halbertec 9000', 1, 2, 1, 'Lining Halbertec 9000 sử dụng công nghệ ACC-RIF TECH, giúp cải thiện khả năng kiểm soát và chính xác trên khung vợt, hỗ trợ tối ưu cho các cú đánh sắc bén. Công nghệ hấp thụ sốc HDF SHOCK ABSORPTION SYSTEM cũng được tích hợp để giảm tối đa lực thất thoát, tạo cảm giác chắc chắn, mượt mà trong từng pha tấn công.', 'img/sanpham/vot-cau-long-li-ning-halbertec-9000.png', 2500000, 2875000.00, 0.15, 1, '2026-03-20 22:16:21', 10, 5),
(3, 'Vợt cầu lông Victor Thruster Ryuga II Pro CPS', 1, 2, 3, 'Vợt Cầu Lông Victor Thruster Ryuga II Pro CPS là phiên bản nâng cấp mới nhất của dòng Ryuga, vẫn giữ nguyên thiết kế đặc trưng với hình ảnh rồng in đậm ấn trên thân vợt. Tuy nhiên đã có thêm sự thay đổi nổi bật về thiết kế bên ngoài, với tông màu đặc biệt: Hồng Flamingo, Tím nho, Trắng sữa. Với thiết kế vẫn được giữ nguyên từ bản cũ nhưng màu sắc mới của Victor Thruster Ryuga II Pro CPS mang đến tác động thị giác mới mẻ cho phong cách cầu lông.', 'img/sanpham/vot-cau-long-victor-thruster-ryuga.png', 2800000, 3220000.00, 0.15, 1, '2026-03-20 22:16:21', 10, 5),
(4, 'Vợt cầu lông Yonex Nanoflare 1000 game', 1, 3, 2, 'Vợt cầu lông Yonex Nanoflare 1000 Game là dòng vợt thuộc phân khúc vợt tầm trung trong series dòng Nanoflare 1000 mới sắp được Yonex cho ra mắt vào tháng 6 năm 2023 dành cho người chơi có trình độ trung bình, yêu cầu sự kiểm soát, tốc độ và sức mạnh', 'img/sanpham/vot-cau-long-yonex-nanoflare-1000-game.png', 3100000, 3565000.00, 0.15, 1, '2026-03-20 22:16:21', 10, 5),
(5, 'Vợt cầu lông Li-Ning Aeronaut 9000', 1, 2, 1, ' Lining Aeronaut 9000 chính hãng trong phân khúc vợt cao cấp của Lining với danh tiếng đã đồng hành cùng tay vợt Shi Yuqi số 3 thế giới một thời (do bị chấn thương nên đã xuống top 9) - Trắng Vàng với phiên bản thuộc bản quốc tế của Trung Quốc đang được săn đón nhiều trong giới hâm mộ cầu lông Lining hiện nay.', 'img/sanpham/aeronaut-9000i.png', 2900000, 3335000.00, 0.15, 1, '2026-03-20 22:16:21', 10, 5),
(6, 'Quả cầu lông Yonex AS50', 2, 3, 2, 'Quả Cầu Lông Yonex AEROSENSA AS-50 Chính Hãng 2018 AS-50 là Yonex trên cùng của dòng lông cầu lông. Nó được làm bằng lông ngỗng đặc biệt. Cầu lông này được sử dụng trong hầu hết các giải đấu cầu lông lớn trên thế giới bao gồm giải đấu cầu lông quốc tế Grand Prix.', 'img/sanpham/ong-cau-yonex-as50-speed-2.png', 450000, 517000.00, 0.15, 1, '2026-03-20 22:16:21', 10, 5),
(7, 'Quả cầu lông Hải Yến S70', 2, 1, 3, '- Ống cầu lông chất lượng Hải Yến S70 là sản phẩm chất lượng cao, sản xuất trên dây chuyền tự động chiếm trên 80% theo tiêu chuẩn của liên đoàn cầu lông thế giới BWF với phương châm không ngừng đổi mới và cải tiến.', 'img/sanpham/ong-cau-long-hai-yen-s70.png', 180000, 207000.00, 0.15, 1, '2026-03-20 22:16:21', 10, 5),
(8, 'Quả cầu lông vinastar', 2, 2, 1, ' Ống cầu lông Vinastar có cấu tạo từ 16 lông vũ tự nhiên từ cánh, đuôi được chọn lọc từ các loại gia cầm đủ thời gian sinh trưởng. Được áp dụng công nghệ xử lý làm thẳng, giúp phần tán cầu có độ bền và độ ổn định trong quỹ đạo bay.', 'img/sanpham/ong-cau-vina-start-xanh.png', 322188, 371000.00, 0.15, 1, '2026-03-20 22:16:21', 10, 5),
(9, 'Quả cầu Li-ning AYQN024-4', 2, 3, 3, 'Ống cầu lông Li-ning AYQN024-4 là một trong những dòng cầu lông cao cấp của thương hiệu Li-ning, nổi tiếng với tốc độ cầu chuẩn xác và độ ổn định trong đường bay. Điều này mang đến cho người chơi những trải nghiệm tuyệt vời nhất trong mỗi trận đấu.\r\n\r\n', 'img/sanpham/cau-lining.png', 320000, 368000.00, 0.15, 1, '2026-03-20 22:16:21', 10, 5),
(10, 'Vợt Pickleball Selkirk Vanguard', 3, 2, 13, 'Vợt Pickleball Selkirk VANGUARD Pro là vợt toàn sân làm từ carbon thô cao cấp được thiết kế để có tính linh hoạt và hiệu suất cao cấp. Với bốn lớp sợi carbon thô 12K — gấp đôi tiêu chuẩn của các loại vợt khác — và cấu trúc chịu nhiệt, vợt tăng cường cảm giác, độ xoáy và sức mạnh.', 'img/sanpham/vot-pickleball-selkirk-luxx-control-air.png', 4500000, 5175000.00, 0.15, 1, '2026-03-20 22:16:21', 10, 5),
(11, 'Vợt Pickleball Joola Perseus', 3, 1, 12, 'Phiên bản 14mm được thiết kế cho người chơi thiên về tấn công và tốc độ. Vợt mang lại cảm giác đánh thật tay, phản hồi rõ ràng, giúp bạn kiểm soát bóng chính xác và tự tin thực hiện các cú drive mạnh mẽ, drop tinh tế hay reset nhanh khi đối mặt áp lực.', 'img/sanpham/perseus-pro-v-ben-johns-blaze-red.png', 5200000, 5980000.00, 0.15, 1, '2026-03-20 22:16:21', 10, 5),
(12, 'Vợt Pickleball JOOLA Ben Johns', 3, 2, 12, 'JOOLA Perseus Pro V mang đến thiết kế vợt pickleball nổi tiếng và được yêu thích nhất của JOOLA. Với các góc bo tròn ở phần đầu vợt kết hợp với dáng elongated, Perseus Pro V giúp giữ sweet spot luôn nằm ở trung tâm trong khi vẫn mở rộng tầm với khi đánh bóng.', 'img/sanpham/joola-ben-johns-hyperion.png', 2100000, 2415000.00, 0.15, 1, '2026-03-20 22:16:21', 10, 5),
(13, 'Vợt Pickleball Soxter Impact', 3, 3, 13, 'Pickleball Soxter Impact là bộ sản phẩm lý tưởng dành cho người mới làm quen với pickleball. Với đầy đủ phụ kiện cần thiết, bộ vợt giúp bạn bắt đầu chơi ngay lập tức và tự tin bước vào sân.\r\n\r\n', 'img/sanpham/vot-pickleball-soxter-impact-pro-2.png', 3800000, 4370000.00, 0.15, 1, '2026-03-20 22:16:21', 10, 5),
(14, 'Vợt cầu lông 1000z', 1, 1, 2, 'Vợt cầu lông Yonex Nanoflare 1000Z có vùng Sweet spot khá rộng, giúp bạn có thể tập trung vào việc tăng cường sức mạnh và độ chính xác của các cú đánh. Điểm nổi bật nhất ở phiên bản mới này nằm ở thiết kế khung vợt khi Yonex tích hợp hai loại Aero Frame và Compact Frame', 'img/sanpham/PROD-20260328130506-69c7c3f2c9cab.webp', 1500000, 1695000.00, 0.13, 0, '2026-03-28 19:05:06', 10, 5),
(15, 'Vợt cầu lông Yonex 100zz VA', 1, 1, 2, 'Vợt cầu lông Yonex Astrox 100ZZ VA là phiên bản đặc biệt “VA Signature” của dòng Astrox 100ZZ từ Yonex - cây vợt chuyên nghiệp này được thiết kế riêng theo phong cách cá nhân của vận động viên Viktor Axelsen. Nó thể hiện rõ phương châm \"Chúng ta cùng nhau phấn đấu\" của nhà vô địch Olympic. Vợt với màu sắc trắng xanh này là phiên bản giới hạn của mẫu vợt chủ lực thuộc dòng Astrox, với độ cân bằng cao ở đầu vợt và cán vợt cực kỳ cứng cáp, lý tưởng cho những người chơi tìm kiếm sức mạnh và độ chính xác tối đa.', 'img/sanpham/PROD-20260328131037-69c7c53d540ea.webp', 400000, 520000.00, 0.30, 1, '2026-03-28 19:10:37', 10, 5),
(16, 'Balo Cầu Lông Lining P-ABSU401-1 chính hãng', 4, 3, 1, 'Balo Cầu Lông Lining P-ABSU401-1 nổi bật với tông màu đen chủ đạo cho cái nhìn sang trọng với kiểu dáng thể thao, trẻ trung năng động phù hợp mọi hoàn cảnh từ đem tới sân cầu cho tới đem đi du lịch với nhiều túi nhỏ tiện dụng để đựng điện thoại, phụ kiện, đồ dùng cá nhân.', 'img/sanpham/balo-cau-long-lining-p-absu401-1-chinh-hang_1733448055.webp', 1000000, 1900000.00, 0.90, 1, '2026-03-28 20:00:08', 10, 5),
(17, 'Balo cầu lông Lining P-ABSV133-1 chính hãng', 4, 3, 1, '- Balo cầu lông Lining P-ABSV133-1 được thiết kế dành riêng cho người chơi cầu lông phong trào, học sinh, sinh viên và người trẻ yêu thích phong cách thể thao, unisex hiện đại. Với mức giá hợp lý, đây là một lựa chọn đáng cân nhắc trong phân khúc balo cầu lông tầm trung.', 'img/sanpham/PROD-20260328140900-69c7d2ec3ebf5.webp', 900000, 1080000.00, 0.20, 1, '2026-03-28 20:09:00', 10, 5),
(18, 'Dây cước căng vợt Yonex BG 80 Power', 2, 1, 3, '- Dây cước căng vợt Yonex BG 80 Power là loại cước với độ cứng cao, độ nảy tốt được khá nhiều các vận động viên thế giới sử dụng. Ngoài ra, cước được phủ một lớp phức hợp Titanium bên ngoài cho độ nhám ở bề mặt kết hợp cùng đường kính dây 0.68mm đảm bảo trong lúc sử dụng dây ít bị chạy và tụt kg tạo ra độ bền nhất định.', 'img/sanpham/PROD-20260328141835-69c7d52ba34e7.webp', 200000, 240000.00, 0.20, 1, '2026-03-28 20:18:35', 10, 5),
(19, 'Túi cầu lông Yonex BAG2326T02', 4, 1, 2, '- Được làm bằng chất liệu cao cấp đảm bảo độ bền, chống thấm hiệu quả, dễ dàng vệ sinh. Thiết kế với màu xanh cùng với các họa tiết được làm tỉ mỉ tạo nên phong cách nổi bật, năng động và hiện đại, có thể sử dụng trong các chuyến đi dã ngoại hoặc du lịch vô cùng tiện lợi.', 'img/sanpham/PROD-20260328142145-69c7d5e91603e.webp', 5000000, 5500000.00, 0.10, 1, '2026-03-28 20:21:45', 10, 5),
(20, 'Bao vợt Adidas X-Symbolic XS5 đen đỏ', 4, 2, 7, 'Túi Adidas XS5 Tournament Bag là sản phẩm cao cấp được thiết kế dành riêng cho những người đam mê thể thao. Với nhiều ngăn đựng tiện ích và kích thước lý tưởng, chiếc túi này mang lại sự tiện lợi và thoải mái trong mọi chuyến đi tập luyện hay thi đấu. Thiết kế thoáng khí và cấu trúc ổn định giúp bảo vệ đồ dùng cá nhân của bạn một cách tối ưu.', 'img/sanpham/PROD-20260331085034-69cb6eba10808.jpg', 600000, 720000.00, 0.20, 1, '2026-03-31 13:50:34', 20, 10),
(21, 'Vợt Pickleball Kamito Alpha-X Limited (16mm)', 3, 2, 8, 'Kamito Alpha-X Limited (16mm) là phiên bản vợt pickleball đặc biệt được Kamito ra mắt nhằm kỷ niệm chiến thắng ấn tượng của tay vợt Lý Hoàng Nam tại PPA Tour Asia – Hangzhou Open 2025, đồng thời chào đón Tết Bính Ngọ 2026. Đây là dòng Limited Edition đúng nghĩa, chỉ sản xuất 500 cây trên toàn quốc, mỗi cây được đánh số thứ tự riêng từ 001–500, mang giá trị sưu tầm cao và khẳng định dấu ấn cá nhân của người sở hữu.', 'img/sanpham/PROD-20260331085145-69cb6f01e0340.jpg', 2000000, 2400000.00, 0.20, 1, '2026-03-31 13:51:45', 25, 10),
(22, 'Dây Cước Đan Vợt Yonex BG80 Power', 2, 1, 2, 'Dây cước đan vợt Yonex BG80 Power là một trong những lựa chọn hàng đầu cho các tay vợt chuyên nghiệp và những người chơi cầu lông đam mê tìm kiếm hiệu suất cao. Với các công nghệ tiên tiến và thiết kế đặc biệt, BG80 Power mang lại những ưu điểm vượt trội\r\nYonex BG80 Power phù hợp cho các tay vợt chuyên nghiệp và bán chuyên, những người yêu cầu cao về lực đẩy và âm thanh sắc nét, đồng thời cần độ bền và kiểm soát tốt trong từng trận đấu.', 'img/sanpham/PROD-20260331085423-69cb6f9f9e68a.jpg', 215000, 258000.00, 0.20, 1, '2026-03-31 13:54:23', 13, 10),
(23, 'Dây cước đan vợt Yonex EXBOLT 63', 2, 1, 2, 'Yonex EXBOLT 63 là dòng dây cước đan vợt cao cấp, nổi bật với khả năng trợ lực vượt trội, đường kính siêu mỏng chỉ 0.63 mm và tích hợp công nghệ hiện đại, mang đến trải nghiệm chơi cầu lông hoàn hảo.', 'img/sanpham/PROD-20260331085513-69cb6fd187f93.jpg', 200000, 240000.00, 0.20, 1, '2026-03-31 13:55:13', 15, 10),
(24, 'Cước Cầu Lông Li-Ning No1', 2, 3, 1, 'Cước cầu lông Li-Ning No1 là lựa chọn hoàn hảo cho những tay vợt yêu thích lối đánh tấn công mạnh mẽ, tốc độ và chính xác. Với đường kính siêu mảnh chỉ 0.65mm, cước mang lại độ phản hồi nhanh vượt trội, giúp bạn dễ dàng tung ra những pha smash uy lực cùng âm thanh nổ cầu rõ ràng, đầy phấn khích.', 'img/sanpham/PROD-20260331085645-69cb702d0b4a2.jpg', 150000, 180000.00, 0.20, 1, '2026-03-31 13:56:45', 12, 10),
(25, 'Dây Cước Đan Vợt Yonex Exbolt 68', 2, 1, 2, 'Yonex Exbolt 68 là một loại dây vợt cầu lông cao cấp được thiết kế để nâng cao hiệu suất chơi cầu lông với nhiều tính năng nổi bật. Dưới đây là một số đặc điểm nổi bật của dây vợt Yonex Exbolt 68:\r\n\r\nCông Nghệ Tinh Xảo: Exbolt 68 sử dụng công nghệ tiên tiến từ Yonex, giúp cải thiện độ bền và hiệu suất của dây vợt. Công nghệ này bao gồm các lớp phủ đặc biệt và cấu trúc dây giúp tăng cường sự ổn định và độ đàn hồi của dây.\r\nKhả Năng Hấp Thụ Va Đập: Dây vợt Exbolt 68 nổi bật với khả năng hấp thụ va đập tốt, giảm thiểu rung lắc và cung cấp cảm giác đánh chắc chắn hơn. Điều này giúp tay vợt có thể thực hiện các cú đánh chính xác và mạnh mẽ hơn.', 'img/sanpham/PROD-20260331090324-69cb71bc9f70d.jpg', 200000, 240000.00, 0.20, 1, '2026-03-31 14:03:24', 20, 10),
(26, 'Băng gối dán iWin Keepa IKN204 – Đen', 2, 3, 14, 'iWin Keepa IKN204 là phụ kiện hỗ trợ bảo vệ khớp gối được thiết kế dành cho những người thường xuyên vận động hoặc chơi thể thao. Với khả năng cố định khớp gối, giảm áp lực và hạn chế chấn thương, sản phẩm giúp người dùng tự tin hơn khi tham gia các hoạt động như chạy bộ, đạp xe, cầu lông hay tập luyện thể thao hằng ngày.', 'img/sanpham/PROD-20260331090541-69cb7245198b4.jpg', 50000, 80000.00, 0.60, 1, '2026-03-31 14:05:41', 100, 10),
(27, 'Băng gót chân Yonex SRG 711', 2, 3, 14, 'Băng gót chân Yonex SRG 711 là phụ kiện không thể thiếu cho người chơi cầu lông, giúp bảo vệ gót chân và khớp cổ chân khỏi chấn thương. Sản phẩm vừa đảm bảo thẩm mỹ khỏe khoắn, vừa mang lại sự an toàn tối đa khi vận động.', 'img/sanpham/PROD-20260331090853-69cb7305b646a.jpg', 70000, 84000.00, 0.20, 1, '2026-03-31 14:08:53', 9, 10);

--
-- Triggers `sanpham`
--
DELIMITER $$
CREATE TRIGGER `trg_sanpham_update_phantramloinhuan` BEFORE UPDATE ON `sanpham` FOR EACH ROW BEGIN
      IF NEW.PhanTramLoiNhuan != OLD.PhanTramLoiNhuan THEN
          SET NEW.GiaBan = fn_round_500(NEW.GiaNhapTB * (1 + NEW.PhanTramLoiNhuan));
      END IF;
  END
$$
DELIMITER ;

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
  `slug` varchar(100) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL COMMENT 'Đường dẫn logo thương hiệu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thuonghieu`
--

INSERT INTO `thuonghieu` (`Ma_thuonghieu`, `Ten_thuonghieu`, `slug`, `image_url`) VALUES
(1, 'Li-Ning', 'li-ning', 'img/icons/Logo-li-ning.webp'),
(2, 'Yonex', 'yonex', 'img/icons/logo-yonex.webp'),
(3, 'Victor', 'victor', 'img/icons/logo-victor.png'),
(4, 'Venson', 'venson', 'img/icons/logo-venson.png'),
(5, 'Mizuno', 'mizuno', 'img/icons/logo-mizuno.png'),
(6, 'Kumpoo', 'kumpoo', 'img/icons/logo-kumpoo.png'),
(7, 'Adidas', 'adidas', 'img/icons/logo-adidas.webp'),
(8, 'Kamito', 'kamito', 'img/icons/logo-kamito.png'),
(12, 'Joola', 'joola', 'img/icons/logo-joola.png'),
(13, 'Selkirk', 'selkirk', 'img/icons/logo-selkirk.webp'),
(14, 'Wika', 'wika', 'img/icons/logo-wika.png');

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

--
-- Dumping data for table `tracuutonkho`
--

INSERT INTO `tracuutonkho` (`TraCuu_id`, `SP_id`, `TrangThai_NhapXuat`, `SoLuong`, `MaThamChieu_NhapXuat`, `ThoiGianTraCuu`) VALUES
(1, 25, 'NHAP', 20, 20, '2026-03-31 14:29:14'),
(2, 23, 'NHAP', 15, 20, '2026-03-31 14:29:14'),
(3, 24, 'NHAP', 12, 21, '2026-03-31 14:30:22'),
(4, 22, 'NHAP', 13, 21, '2026-03-31 14:30:22'),
(5, 27, 'NHAP', 9, 21, '2026-03-31 14:30:22'),
(6, 20, 'NHAP', 20, 22, '2026-03-31 14:31:04'),
(7, 21, 'NHAP', 25, 23, '2026-03-31 14:31:56'),
(8, 26, 'NHAP', 100, 23, '2026-03-31 14:31:56');

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
(1, 'user', '$2y$10$WyfbWCYPDFLPz2HbRfYDa.POvoakT/E71k.3Qhbe2Fay/NAx0ZH3i', NULL, NULL, NULL, 1, 1, '2026-03-20 22:30:01'),
(2, 'sang', '$2a$10$hFgtrSjzowGIvrIU90F86.ZBN87TLRbv1R4V3GLY9G5dKnaIR7qj.', 'Sang Ngu', 'ea@gmail.com', '0909090909', 0, 1, '2026-03-20 22:30:01'),
(3, 'Tisdoo', '$2y$10$KVCLdId9zeX.m9V6n.KSBuIAhB3dHadrgsIs.o7q3sdNr54kMgZUq', 'hoàng ấn', 'bodow@gmail.com', '0598898588', 0, 1, '2026-03-23 10:47:04'),
(4, 'beiu', '$2y$10$amSCnKvV/3fwmiwpx8GO9ui9YfkXdt3W4qZCXi/BQEpwaSEF50Lhy', 'hoàng ấn', 'bodowq@gmail.com', '0598898588', 0, 1, '2026-03-23 10:49:34');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chitiethoadon`
--
ALTER TABLE `chitiethoadon`
  ADD PRIMARY KEY (`ChiTietDonHang_id`),
  ADD KEY `DonHang_id` (`DonHang_id`),
  ADD KEY `SanPham_id` (`SanPham_id`),
  ADD KEY `idx_donhang` (`DonHang_id`),
  ADD KEY `idx_sanpham` (`SanPham_id`);

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
  MODIFY `ChiTiet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `chitietphieuxuat`
--
ALTER TABLE `chitietphieuxuat`
  MODIFY `ChiTiet_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `danhmuc`
--
ALTER TABLE `danhmuc`
  MODIFY `Danhmuc_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
  MODIFY `NCC_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `phieunhap`
--
ALTER TABLE `phieunhap`
  MODIFY `NhapHang_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `phieuxuat`
--
ALTER TABLE `phieuxuat`
  MODIFY `PhieuXuat_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sanpham`
--
ALTER TABLE `sanpham`
  MODIFY `SanPham_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `sp_tronggiohang`
--
ALTER TABLE `sp_tronggiohang`
  MODIFY `SP_GioHang_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `thuonghieu`
--
ALTER TABLE `thuonghieu`
  MODIFY `Ma_thuonghieu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `tracuutonkho`
--
ALTER TABLE `tracuutonkho`
  MODIFY `TraCuu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
  ADD CONSTRAINT `chitiethoadon_ibfk_2` FOREIGN KEY (`SanPham_id`) REFERENCES `sanpham` (`SanPham_id`),
  ADD CONSTRAINT `fk_ctdh_donhang` FOREIGN KEY (`DonHang_id`) REFERENCES `donhang` (`DonHang_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ctdh_sanpham` FOREIGN KEY (`SanPham_id`) REFERENCES `sanpham` (`SanPham_id`);

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
  ADD CONSTRAINT `donhang_ibfk_2` FOREIGN KEY (`DiaChi_id`) REFERENCES `diachigh` (`add_id`),
  ADD CONSTRAINT `fk_donhang_diachi` FOREIGN KEY (`DiaChi_id`) REFERENCES `diachigh` (`add_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_donhang_user` FOREIGN KEY (`User_id`) REFERENCES `users` (`User_id`) ON DELETE CASCADE;

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
