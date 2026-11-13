-- Struktur tabel transaksi sesuai diagram pada soal + data contoh untuk pengujian
CREATE TABLE IF NOT EXISTS transaksi (
    id              VARCHAR(20) PRIMARY KEY,
    waktu_transaksi DATE NOT NULL,
    keterangan      TEXT,
    total           INT NOT NULL,
    pelanggan_id    VARCHAR(20) NOT NULL
);

INSERT INTO transaksi (id, waktu_transaksi, keterangan, total, pelanggan_id) VALUES
('TRX001', '2023-09-01', 'Penjualan', 39000, 'P01'),
('TRX002', '2023-09-02', 'Penjualan', 52000, 'P02'),
('TRX003', '2023-09-03', 'Penjualan', 13000, 'P03'),
('TRX004', '2023-09-04', 'Penjualan', 13000, 'P04'),
('TRX005', '2023-09-05', 'Penjualan', 26000, 'P05'),
('TRX006', '2023-09-05', 'Penjualan', 0,     'P05'),
('TRX007', '2023-09-06', 'Penjualan', 13000, 'P06'),
('TRX008', '2023-09-09', 'Penjualan', 13000, 'P07');
