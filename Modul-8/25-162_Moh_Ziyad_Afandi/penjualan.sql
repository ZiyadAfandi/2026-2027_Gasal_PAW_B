-- Database Sistem Penjualan
-- Import file ini lewat phpMyAdmin (tab Import). Database dibuat otomatis.

CREATE DATABASE IF NOT EXISTS `penjualan` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `penjualan`;

DROP TABLE IF EXISTS `detail_transaksi`;
DROP TABLE IF EXISTS `transaksi`;
DROP TABLE IF EXISTS `barang`;
DROP TABLE IF EXISTS `pelanggan`;
DROP TABLE IF EXISTS `supplier`;
DROP TABLE IF EXISTS `user`;

-- level: 1 = owner, 2 = kasir
CREATE TABLE `user` (
  `id_user`  int(11) NOT NULL AUTO_INCREMENT,
  `nama`     varchar(100) NOT NULL,
  `username` varchar(60)  NOT NULL,
  `password` varchar(255) NOT NULL,
  `level`    tinyint(1)   NOT NULL DEFAULT 2,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB;

CREATE TABLE `supplier` (
  `id_supplier`   int(11) NOT NULL AUTO_INCREMENT,
  `nama_supplier` varchar(100) NOT NULL,
  `telepon`       varchar(20)  DEFAULT NULL,
  `alamat`        varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_supplier`)
) ENGINE=InnoDB;

CREATE TABLE `pelanggan` (
  `id_pelanggan`   int(11) NOT NULL AUTO_INCREMENT,
  `nama_pelanggan` varchar(100) NOT NULL,
  `telepon`        varchar(20)  DEFAULT NULL,
  `alamat`         varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_pelanggan`)
) ENGINE=InnoDB;

CREATE TABLE `barang` (
  `id_barang`   int(11) NOT NULL AUTO_INCREMENT,
  `id_supplier` int(11) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `harga`       int(11) NOT NULL DEFAULT 0,
  `stok`        int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_barang`),
  CONSTRAINT `fk_barang_supplier` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`)
) ENGINE=InnoDB;

CREATE TABLE `transaksi` (
  `id_transaksi` int(11) NOT NULL AUTO_INCREMENT,
  `tanggal`      datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_pelanggan` int(11) DEFAULT NULL,
  `id_user`      int(11) NOT NULL,
  `total`        int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_transaksi`),
  CONSTRAINT `fk_transaksi_pelanggan` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`) ON DELETE SET NULL,
  CONSTRAINT `fk_transaksi_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`)
) ENGINE=InnoDB;

CREATE TABLE `detail_transaksi` (
  `id_detail`    int(11) NOT NULL AUTO_INCREMENT,
  `id_transaksi` int(11) NOT NULL,
  `id_barang`    int(11) NOT NULL,
  `harga`        int(11) NOT NULL,
  `qty`          int(11) NOT NULL,
  `subtotal`     int(11) NOT NULL,
  PRIMARY KEY (`id_detail`),
  CONSTRAINT `fk_detail_transaksi` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`) ON DELETE CASCADE,
  CONSTRAINT `fk_detail_barang` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`)
) ENGINE=InnoDB;

-- Data awal
-- Login owner : username "owner" / password "owner123"
-- Login kasir : username "kasir" / password "kasir123"
INSERT INTO `user` (`nama`, `username`, `password`, `level`) VALUES
('Budi Santoso', 'owner', '$2y$10$3pcv/mMbvmO1kahXJhr/muww9dk2xrlkc8nb2eCOiWzZ6X6OHpvXK', 1),
('Wati Lestari', 'kasir', '$2y$10$MttBs0ezeKG.52rDOtIsYOlqj8pU1e4Vy6pte08NGgDv0p6aRC6vW', 2);

INSERT INTO `supplier` (`nama_supplier`, `telepon`, `alamat`) VALUES
('PT Sumber Makmur', '081234567890', 'Jl. Merdeka No. 10, Bandung'),
('CV Jaya Abadi', '082233445566', 'Jl. Sudirman No. 5, Jakarta'),
('UD Sinar Terang', '085566778899', 'Jl. Diponegoro No. 21, Surabaya');

INSERT INTO `pelanggan` (`nama_pelanggan`, `telepon`, `alamat`) VALUES
('Andi Wijaya', '081300001111', 'Jl. Melati No. 3'),
('Siti Aminah', '081300002222', 'Jl. Mawar No. 8'),
('Rudi Hartono', '081300003333', 'Jl. Kenanga No. 15');

INSERT INTO `barang` (`id_supplier`, `nama_barang`, `harga`, `stok`) VALUES
(1, 'Beras 5 Kg', 65000, 50),
(1, 'Minyak Goreng 2 L', 38000, 40),
(2, 'Gula Pasir 1 Kg', 17000, 60),
(2, 'Kopi Bubuk 200 g', 15000, 35),
(3, 'Mie Instan', 3500, 200),
(3, 'Teh Celup (25 pcs)', 8500, 80);
