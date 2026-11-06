
DROP DATABASE IF EXISTS praktikum6;
CREATE DATABASE praktikum6 CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE praktikum6;

-- ================= STRUKTUR TABEL =================

CREATE TABLE supplier (
  id INT NOT NULL AUTO_INCREMENT,
  nama VARCHAR(100) NOT NULL,
  telp VARCHAR(12),
  alamat TEXT,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

CREATE TABLE pelanggan (
  id VARCHAR(20) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  jenis_kelamin ENUM('L','P') NOT NULL,
  telp VARCHAR(12),
  alamat TEXT,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

CREATE TABLE user (
  id_user TINYINT NOT NULL AUTO_INCREMENT,
  username VARCHAR(30) NOT NULL,
  password VARCHAR(35) NOT NULL,
  nama VARCHAR(50),
  alamat VARCHAR(150),
  hp VARCHAR(20),
  level TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_user),
  UNIQUE KEY uq_username (username)
) ENGINE=InnoDB;

CREATE TABLE barang (
  id INT NOT NULL AUTO_INCREMENT,
  kode_barang VARCHAR(10) NOT NULL,
  nama_barang VARCHAR(100) NOT NULL,
  harga INT NOT NULL,
  stok INT NOT NULL DEFAULT 0,
  supplier_id INT,
  PRIMARY KEY (id),
  UNIQUE KEY uq_kode (kode_barang),
  CONSTRAINT fk_barang_supplier FOREIGN KEY (supplier_id)
    REFERENCES supplier(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE transaksi (
  id INT NOT NULL AUTO_INCREMENT,
  waktu_transaksi DATE NOT NULL,
  keterangan TEXT,
  total INT NOT NULL DEFAULT 0,
  pelanggan_id VARCHAR(20),
  PRIMARY KEY (id),
  CONSTRAINT fk_transaksi_pelanggan FOREIGN KEY (pelanggan_id)
    REFERENCES pelanggan(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Primary key gabungan (transaksi_id, barang_id): satu barang hanya
-- boleh muncul sekali per transaksi (pengaman tambahan di level database).
CREATE TABLE transaksi_detail (
  transaksi_id INT NOT NULL,
  barang_id INT NOT NULL,
  harga INT NOT NULL,
  qty INT NOT NULL,
  PRIMARY KEY (transaksi_id, barang_id),
  CONSTRAINT fk_detail_transaksi FOREIGN KEY (transaksi_id)
    REFERENCES transaksi(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_detail_barang FOREIGN KEY (barang_id)
    REFERENCES barang(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pembayaran (
  id INT NOT NULL AUTO_INCREMENT,
  waktu_bayar DATETIME NOT NULL,
  total INT NOT NULL,
  metode ENUM('TUNAI','TRANSFER','EDC') NOT NULL,
  transaksi_id INT NOT NULL,
  PRIMARY KEY (id),
  CONSTRAINT fk_bayar_transaksi FOREIGN KEY (transaksi_id)
    REFERENCES transaksi(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ================= DATA MASTER (min. 5) =================

INSERT INTO supplier (id, nama, telp, alamat) VALUES
(1, 'PT Sumber Pangan',    '022711001', 'Jl. Soekarno-Hatta No. 10, Bandung'),
(2, 'CV Minyak Makmur',    '022711002', 'Jl. Cibaduyut No. 25, Bandung'),
(3, 'UD Telur Sejahtera',  '022711003', 'Jl. Lembang No. 7, Bandung Barat'),
(4, 'PT Kopi Nusantara',   '022711004', 'Jl. Dago No. 88, Bandung'),
(5, 'CV Aneka Kebutuhan',  '022711005', 'Jl. Pasteur No. 12, Bandung');

INSERT INTO pelanggan (id, nama, jenis_kelamin, telp, alamat) VALUES
('PLG001', 'Andi Pratama',   'L', '081200000001', 'Jl. Merdeka No. 1, Bandung'),
('PLG002', 'Siti Aminah',    'P', '081200000002', 'Jl. Braga No. 15, Bandung'),
('PLG003', 'Budi Santoso',   'L', '081200000003', 'Jl. Riau No. 30, Bandung'),
('PLG004', 'Dewi Lestari',   'P', '081200000004', 'Jl. Cihampelas No. 5, Bandung'),
('PLG005', 'Rizky Maulana',  'L', '081200000005', 'Jl. Setiabudi No. 21, Bandung');

INSERT INTO user (id_user, username, password, nama, alamat, hp, level) VALUES
(1, 'admin',   MD5('admin123'),   'Administrator', 'Jl. Kantor No. 1, Bandung',  '081300000001', 1),
(2, 'kasir1',  MD5('kasir123'),   'Kasir Satu',    'Jl. Kantor No. 2, Bandung',  '081300000002', 2),
(3, 'kasir2',  MD5('kasir456'),   'Kasir Dua',     'Jl. Kantor No. 3, Bandung',  '081300000003', 2),
(4, 'gudang1', MD5('gudang123'),  'Petugas Gudang','Jl. Gudang No. 4, Bandung',  '081300000004', 3),
(5, 'owner',   MD5('owner123'),   'Pemilik Toko',  'Jl. Pemilik No. 5, Bandung', '081300000005', 1);

INSERT INTO barang (id, kode_barang, nama_barang, harga, stok, supplier_id) VALUES
(1, 'BRG001', 'Beras 5 kg',          65000, 50, 1),
(2, 'BRG002', 'Minyak Goreng 2 L',   38000, 40, 2),
(3, 'BRG003', 'Gula Pasir 1 kg',     17000, 60, 1),
(4, 'BRG004', 'Telur Ayam 1 kg',     29000, 30, 3),
(5, 'BRG005', 'Kopi Bubuk 200 g',    22000, 45, 4),
(6, 'BRG006', 'Teh Celup isi 25',     9000, 80, 5),
(7, 'BRG007', 'Sabun Mandi Batang',   6000,100, 5);

-- ================= DATA TRANSAKSI (min. 10) =================

INSERT INTO transaksi (id, waktu_transaksi, keterangan, total, pelanggan_id) VALUES
(1,  '2026-11-01', 'Belanja bulanan',        147000, 'PLG001'),
(2,  '2026-11-01', 'Belanja dapur',           82000, 'PLG002'),
(3,  '2026-11-02', 'Pesanan telur',           87000, 'PLG003'),
(4,  '2026-11-02', 'Belanja mingguan',       135000, 'PLG004'),
(5,  '2026-11-03', 'Stok warung kopi',        98000, 'PLG005'),
(6,  '2026-11-03', 'Pesanan gula',            85000, 'PLG001'),
(7,  '2026-11-04', 'Keperluan acara',        119000, 'PLG002'),
(8,  '2026-11-04', 'Pembelian beras',        195000, 'PLG003'),
(9,  '2026-11-05', 'Belanja campur',         118000, 'PLG004'),
(10, '2026-11-05', 'Belanja harian',          69000, 'PLG005');

INSERT INTO transaksi_detail (transaksi_id, barang_id, harga, qty) VALUES
(1, 1, 65000, 2), (1, 3, 17000, 1),
(2, 2, 38000, 1), (2, 5, 22000, 2),
(3, 4, 29000, 3),
(4, 1, 65000, 1), (4, 6,  9000, 4), (4, 3, 17000, 2),
(5, 5, 22000, 1), (5, 2, 38000, 2),
(6, 3, 17000, 5),
(7, 6,  9000,10), (7, 4, 29000, 1),
(8, 1, 65000, 3),
(9, 2, 38000, 1), (9, 4, 29000, 2), (9, 5, 22000, 1),
(10,6,  9000, 2), (10,3, 17000, 3);

INSERT INTO pembayaran (waktu_bayar, total, metode, transaksi_id) VALUES
('2026-11-01 10:15:00', 147000, 'TUNAI',    1),
('2026-11-01 14:30:00',  82000, 'TRANSFER', 2),
('2026-11-02 09:05:00',  87000, 'TUNAI',    3),
('2026-11-02 16:45:00', 135000, 'EDC',      4),
('2026-11-03 11:20:00',  98000, 'TRANSFER', 5),
('2026-11-03 13:00:00',  85000, 'TUNAI',    6),
('2026-11-04 10:10:00', 119000, 'EDC',      7),
('2026-11-04 15:50:00', 195000, 'TRANSFER', 8),
('2026-11-05 12:25:00', 118000, 'TUNAI',    9),
('2026-11-05 17:00:00',  69000, 'TUNAI',   10);
