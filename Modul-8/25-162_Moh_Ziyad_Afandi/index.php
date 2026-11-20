<?php
require_once 'fungsi.php';
wajib_login();

$level = (int) $_SESSION['level'];

$hari_ini = db_one(
    "SELECT COUNT(*) AS jumlah, COALESCE(SUM(total),0) AS total FROM transaksi WHERE DATE(tanggal) = CURDATE()"
);

$judul = "Home";
$menu  = "home";
layout_awal($judul, $menu);
?>

<div class="mb-4">
    <h3>Selamat datang, <?= e($_SESSION['nama']) ?>!</h3>
    <p class="text-muted mb-0">Anda login sebagai <strong><?= e(nama_level($level)) ?></strong>.</p>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">Transaksi hari ini</div>
            <div class="fs-3 fw-semibold"><?= e($hari_ini['jumlah']) ?></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">Omzet hari ini</div>
            <div class="fs-3 fw-semibold"><?= e(rupiah($hari_ini['total'])) ?></div>
        </div></div>
    </div>

    <?php if ($level === 1):
        $jml_barang    = db_one("SELECT COUNT(*) AS n FROM barang")['n'];
        $jml_supplier  = db_one("SELECT COUNT(*) AS n FROM supplier")['n'];
        $jml_pelanggan = db_one("SELECT COUNT(*) AS n FROM pelanggan")['n'];
    ?>
    <div class="col-md-4">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">Data barang</div>
            <div class="fs-3 fw-semibold"><?= e($jml_barang) ?></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">Data supplier</div>
            <div class="fs-3 fw-semibold"><?= e($jml_supplier) ?></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">Data pelanggan</div>
            <div class="fs-3 fw-semibold"><?= e($jml_pelanggan) ?></div>
        </div></div>
    </div>
    <?php endif; ?>
</div>

<?php layout_akhir(); ?>
