<?php
require_once 'fungsi.php';

// Jika user sudah login, tidak boleh mengakses halaman login
wajib_tamu();

$error    = "";
$username = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validasi login
    if ($username === '' || $password === '') {
        $error = "Username dan password wajib diisi.";
    } else {
        $user = db_one("SELECT * FROM user WHERE username = ?", [$username]);

        if (!$user) {
            $error = "Username tidak ditemukan.";
        } elseif (!password_verify($password, $user['password'])) {
            $error = "Password salah.";
        } else {
            // Set session
            session_regenerate_id(true);
            $_SESSION['login']    = true;
            $_SESSION['id_user']  = $user['id_user'];
            $_SESSION['nama']     = $user['nama'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['level']    = (int) $user['level'];

            header("Location: index.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Sistem Penjualan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container" style="max-width:420px; margin-top:12vh;">
    <div class="card shadow-sm">
        <div class="card-header text-white text-center" style="background-color:#1a4a8a;">
            <h5 class="mb-0 py-1">&#128717; Sistem Penjualan</h5>
        </div>
        <div class="card-body p-4">
            <h6 class="mb-3 text-center text-muted">Silakan login untuk melanjutkan</h6>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username"
                           value="<?= e($username) ?>" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
