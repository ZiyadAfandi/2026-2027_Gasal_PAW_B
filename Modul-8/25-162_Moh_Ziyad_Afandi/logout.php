<?php
// Unset session untuk logout, lalu arahkan ke halaman login
require_once 'midlewire.php';

$_SESSION = [];                 // unset semua variabel session
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

header("Location: login.php");
exit();
