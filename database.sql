-- HotelAurora Database Schema
-- Created for HotelAurora Hotel Booking System

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Database: HotelAurora
CREATE DATABASE IF NOT EXISTS `HotelAurora` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `HotelAurora`;

-- --------------------------------------------------------


CREATE TABLE `pengguna` (
  `id_pengguna` int(11) NOT NULL AUTO_INCREMENT,
  `nama_depan` varchar(50) NOT NULL,
  `nama_belakang` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `hak_akses` enum('user','admin') NOT NULL DEFAULT 'user',
  `tanggal_daftar` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pengguna`),
  KEY `idx_email` (`email`),
  KEY `idx_hak_akses` (`hak_akses`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------


CREATE TABLE `hotel` (
  `id_hotel` int(11) NOT NULL AUTO_INCREMENT,
  `nama_hotel` varchar(100) NOT NULL,
  `lokasi` varchar(100) NOT NULL,
  `harga_per_malam` int(11) NOT NULL,
  `deskripsi` text NOT NULL,
  `stok_kamar` int(11) NOT NULL DEFAULT 0,
  `foto` varchar(255) DEFAULT NULL,
  `tanggal_dibuat` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_hotel`),
  KEY `idx_lokasi` (`lokasi`),
  KEY `idx_harga` (`harga_per_malam`),
  KEY `idx_stok` (`stok_kamar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
 
CREATE TABLE `pemesanan` (
  `id_pemesanan` int(11) NOT NULL AUTO_INCREMENT,
  `id_pengguna` int(11) NOT NULL,
  `id_hotel` int(11) NOT NULL,
  `tanggal_checkin` date NOT NULL,
  `tanggal_checkout` date NOT NULL,
  `jumlah_orang` int(11) NOT NULL,
  `jumlah_kamar` int(11) NOT NULL,
  `total_harga` int(11) NOT NULL,
  `status` enum('pending','berhasil','batal') NOT NULL DEFAULT 'pending',
  `kode_booking` varchar(20) DEFAULT NULL,
  `tanggal_pemesanan` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pemesanan`),
  KEY `idx_pengguna` (`id_pengguna`),
  KEY `idx_hotel` (`id_hotel`),
  KEY `idx_status` (`status`),
  KEY `idx_kode_booking` (`kode_booking`),
  KEY `idx_tanggal_checkin` (`tanggal_checkin`),
  CONSTRAINT `fk_pemesanan_pengguna` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE CASCADE,
  CONSTRAINT `fk_pemesanan_hotel` FOREIGN KEY (`id_hotel`) REFERENCES `hotel` (`id_hotel`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------


CREATE TABLE `pembayaran` (
  `id_pembayaran` int(11) NOT NULL AUTO_INCREMENT,
  `id_pemesanan` int(11) NOT NULL,
  `metode` enum('Dana','OVO','GoPay','ShopeePay') NOT NULL,
  `nomor_akun` varchar(20) NOT NULL,
  `tanggal_pembayaran` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status_pembayaran` enum('berhasil','gagal','pending') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id_pembayaran`),
  KEY `idx_pemesanan` (`id_pemesanan`),
  KEY `idx_status_pembayaran` (`status_pembayaran`),
  CONSTRAINT `fk_pembayaran_pemesanan` FOREIGN KEY (`id_pemesanan`) REFERENCES `pemesanan` (`id_pemesanan`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

CREATE TABLE `api_key` (
  `id_key` int(11) NOT NULL AUTO_INCREMENT,
  `key_value` varchar(255) NOT NULL,
  `id_pengguna` int(11) NOT NULL,
  `hak_akses` enum('user','admin') NOT NULL DEFAULT 'user',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `tanggal_dibuat` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_key`),
  KEY `idx_key_value` (`key_value`),
  KEY `idx_pengguna_api` (`id_pengguna`),
  CONSTRAINT `fk_api_key_pengguna` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

INSERT INTO `pengguna` (`nama_depan`, `nama_belakang`, `email`, `password`, `hak_akses`) VALUES
('Admin', 'HotelAurora', 'admin@HotelAurora.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');


INSERT INTO `pengguna` (`nama_depan`, `nama_belakang`, `email`, `password`, `hak_akses`) VALUES
('Budi', 'Santoso', 'budi@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

INSERT INTO `hotel` (`nama_hotel`, `lokasi`, `harga_per_malam`, `deskripsi`, `stok_kamar`, `foto`) VALUES
('Hotel Mawar', 'Bandung', 350000, 'Hotel mewah di pusat kota Bandung dengan fasilitas lengkap. Dilengkapi dengan kolam renang, spa, dan restaurant. Lokasi strategis dekat dengan factory outlet dan tempat wisata.', 10, 'https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'),
('Hotel Melati', 'Jakarta', 500000, 'Hotel bisnis modern di Jakarta dengan akses mudah ke pusat bisnis. Fasilitas meeting room, business center, dan gym. Dekat dengan stasiun MRT dan mall.', 7, 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'),
('Hotel Anggrek', 'Surabaya', 425000, 'Hotel keluarga yang nyaman dengan pemandangan kota Surabaya. Dilengkapi playground anak, kolam renang keluarga, dan restaurant dengan menu lokal dan internasional.', 12, 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'),
('Hotel Cempaka', 'Yogyakarta', 300000, 'Hotel heritage dengan nuansa tradisional Jawa di pusat kota Yogyakarta. Dekat dengan Malioboro, Kraton, dan Taman Sari. Menyajikan pengalaman budaya yang autentik.', 8, 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'),
('Hotel Flamboyan', 'Bali', 750000, 'Resort mewah di pantai Bali dengan pemandangan laut yang menakjubkan. Fasilitas spa, water sport, dan restaurant tepi pantai. Perfect untuk honeymoon dan liburan keluarga.', 15, 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'),
('Hotel Sakura', 'Medan', 380000, 'Hotel modern dengan pelayanan prima di pusat kota Medan. Dekat dengan kuliner khas Medan dan pusat perbelanjaan. Ideal untuk wisata kuliner dan bisnis.', 9, 'https://images.unsplash.com/photo-1564501049412-61c2a3083791?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80');

INSERT INTO `pemesanan` (`id_pengguna`, `id_hotel`, `tanggal_checkin`, `tanggal_checkout`, `jumlah_orang`, `jumlah_kamar`, `total_harga`, `status`, `kode_booking`) VALUES
(2, 1, '2025-10-01', '2025-10-05', 2, 1, 1400000, 'berhasil', 'HFT12345');

-- Insert sample payment
INSERT INTO `pembayaran` (`id_pemesanan`, `metode`, `nomor_akun`, `status_pembayaran`) VALUES
(1, 'Dana', '08123456789', 'berhasil');



-- Triggers for automatic operations

DELIMITER $$


CREATE TRIGGER `update_stock_after_booking` 
AFTER UPDATE ON `pemesanan` 
FOR EACH ROW 
BEGIN
    IF NEW.status = 'berhasil' AND OLD.status != 'berhasil' THEN
        UPDATE hotel 
        SET stok_kamar = stok_kamar - NEW.jumlah_kamar 
        WHERE id_hotel = NEW.id_hotel;
    END IF;
END$$


CREATE TRIGGER `restore_stock_after_cancel` 
AFTER UPDATE ON `pemesanan` 
FOR EACH ROW 
BEGIN
    IF NEW.status = 'batal' AND OLD.status = 'berhasil' THEN
        UPDATE hotel 
        SET stok_kamar = stok_kamar + NEW.jumlah_kamar 
        WHERE id_hotel = NEW.id_hotel;
    END IF;
END$$


CREATE TRIGGER `cleanup_bookings_after_hotel_delete` 
AFTER DELETE ON `hotel` 
FOR EACH ROW 
BEGIN
    DELETE FROM pemesanan WHERE id_hotel = OLD.id_hotel;
END$$

DELIMITER ;

CREATE INDEX `idx_pemesanan_tanggal` ON `pemesanan` (`tanggal_pemesanan`);
CREATE INDEX `idx_hotel_nama` ON `hotel` (`nama_hotel`);
CREATE INDEX `idx_pengguna_nama` ON `pengguna` (`nama_depan`, `nama_belakang`);

CREATE VIEW `v_booking_summary` AS
SELECT 
    p.id_pemesanan,
    p.kode_booking,
    CONCAT(u.nama_depan, ' ', u.nama_belakang) AS nama_tamu,
    u.email,
    h.nama_hotel,
    h.lokasi,
    p.tanggal_checkin,
    p.tanggal_checkout,
    DATEDIFF(p.tanggal_checkout, p.tanggal_checkin) AS jumlah_malam,
    p.jumlah_kamar,
    p.jumlah_orang,
    p.total_harga,
    p.status,
    pay.metode AS metode_pembayaran,
    pay.nomor_akun,
    p.tanggal_pemesanan
FROM pemesanan p
JOIN pengguna u ON p.id_pengguna = u.id_pengguna
JOIN hotel h ON p.id_hotel = h.id_hotel
LEFT JOIN pembayaran pay ON p.id_pemesanan = pay.id_pemesanan;

CREATE VIEW `v_hotel_stats` AS
SELECT 
    h.id_hotel,
    h.nama_hotel,
    h.lokasi,
    h.harga_per_malam,
    h.stok_kamar,
    COUNT(p.id_pemesanan) AS total_bookings,
    COUNT(CASE WHEN p.status = 'berhasil' THEN 1 END) AS successful_bookings,
    SUM(CASE WHEN p.status = 'berhasil' THEN p.total_harga ELSE 0 END) AS total_revenue,
    AVG(CASE WHEN p.status = 'berhasil' THEN p.total_harga END) AS avg_booking_value
FROM hotel h
LEFT JOIN pemesanan p ON h.id_hotel = p.id_hotel
GROUP BY h.id_hotel;

COMMIT;
