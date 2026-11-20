<?php
require_once __DIR__ . '/midlewire.php';
require_once __DIR__ . '/koneksi.php';

// Escape output agar aman dari XSS
function e($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function rupiah($angka)
{
    return "Rp " . number_format((float) $angka, 0, ',', '.');
}

// ---------- Helper database (prepared statement, aman dari SQL injection) ----------

function db_run($sql, $params = [])
{
    global $connect;
    $stmt = mysqli_prepare($connect, $sql);
    if ($params) {
        mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
    }
    mysqli_stmt_execute($stmt);
    return $stmt;
}

// Ambil banyak baris
function db_all($sql, $params = [])
{
    $stmt = db_run($sql, $params);
    $res  = mysqli_stmt_get_result($stmt);
    $rows = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt);
    return $rows;
}

// Ambil satu baris
function db_one($sql, $params = [])
{
    $rows = db_all($sql, $params);
    return $rows[0] ?? null;
}

// INSERT / UPDATE / DELETE
function db_exec($sql, $params = [])
{
    $stmt = db_run($sql, $params);
    mysqli_stmt_close($stmt);
}

// ---------- Flash message ----------

function set_flash($tipe, $pesan)
{
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function tampil_flash()
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alert alert-' . e($f['tipe']) . ' alert-dismissible fade show" role="alert">'
            . e($f['pesan'])
            . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// ---------- Tampilan (navbar sesuai level) ----------

function layout_awal($judul = '', $menu = '')
{
    $level = (int) $_SESSION['level'];
    $aktif = fn($m) => $menu === $m ? ' active' : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul ?? 'Sistem Penjualan') ?> - Sistem Penjualan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color:#1a4a8a;">
    <div class="container">
        <a class="navbar-brand" href="index.php">&#128717; Sistem Penjualan</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuUtama">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuUtama">
            <!-- 1 => Navigasi (berbeda sesuai level) -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link<?= $aktif('home') ?>" href="index.php">Home</a></li>

                <?php if ($level === 1): // hanya owner ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle<?= $aktif('master') ?>" href="#" data-bs-toggle="dropdown">Data Master</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="master.php?m=barang">Data Barang</a></li>
                        <li><a class="dropdown-item" href="master.php?m=supplier">Data Supplier</a></li>
                        <li><a class="dropdown-item" href="master.php?m=pelanggan">Data Pelanggan</a></li>
                        <li><a class="dropdown-item" href="master.php?m=user">Data User</a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <li class="nav-item"><a class="nav-link<?= $aktif('transaksi') ?>" href="transaksi.php">Transaksi</a></li>
                <li class="nav-item"><a class="nav-link<?= $aktif('laporan') ?>" href="laporan.php">Laporan</a></li>
            </ul>

            <!-- 2 / 3 => Nama user sesuai login + logout -->
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <?= e($_SESSION['nama']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted small">Level: <?= e(nama_level($level)) ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="logout.php" method="post" class="m-0">
                                <button type="submit" class="dropdown-item">Logout</button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
<?php tampil_flash(); ?>
<?php
}

function layout_akhir()
{
?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}
