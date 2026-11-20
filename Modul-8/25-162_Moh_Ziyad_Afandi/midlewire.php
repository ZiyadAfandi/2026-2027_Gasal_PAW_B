<?php
// Middleware: pengelolaan session & proteksi halaman.
// Level user: 1 = owner, 2 = kasir

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function sudah_login()
{
    return !empty($_SESSION['login']);
}

function nama_level($level)
{
    return (int) $level === 1 ? 'Owner' : 'Kasir';
}

// Proteksi halaman: user yang belum login dilempar ke login.php
function wajib_login()
{
    if (!sudah_login()) {
        header("Location: login.php");
        exit();
    }
    // Agar halaman tidak bisa dibuka lewat tombol "Back" setelah logout
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Pragma: no-cache");
}

// Proteksi berdasarkan level (mis. Data Master hanya untuk owner)
function wajib_level($level)
{
    wajib_login();
    if ((int) $_SESSION['level'] !== (int) $level) {
        $_SESSION['flash'] = [
            'tipe'  => 'danger',
            'pesan' => 'Anda tidak memiliki hak akses ke halaman tersebut.',
        ];
        header("Location: index.php");
        exit();
    }
}

// Proteksi halaman login: user yang sudah login tidak boleh membukanya
function wajib_tamu()
{
    if (sudah_login()) {
        header("Location: index.php");
        exit();
    }
}
