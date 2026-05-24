<?php
require_once __DIR__ . '/functions/auth.php';

startSession();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } elseif (login($username, $password)) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    } else {
        $error = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SPK Karyawan Terbaik</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <img src="<?= BASE_URL ?>/bri.png" alt="BRI Logo" style="height: 52px; width: auto; margin-bottom: 16px; object-fit: contain; filter: drop-shadow(2px 0 0 #cbd5e1) drop-shadow(-2px 0 0 #cbd5e1) drop-shadow(0 2px 0 #cbd5e1) drop-shadow(0 -2px 0 #cbd5e1) drop-shadow(1.5px 1.5px 0 #cbd5e1) drop-shadow(-1.5px 1.5px 0 #cbd5e1) drop-shadow(1.5px -1.5px 0 #cbd5e1) drop-shadow(-1.5px -1.5px 0 #cbd5e1) drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));">
            <h4>SPK Karyawan Terbaik</h4>
            <p>Bank BRI KCP Arundina</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" style="display:flex;align-items:center;gap:8px;">
                <i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-control"
                    placeholder="Masukkan username"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    required
                    autocomplete="username"
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div style="position:relative;">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Masukkan password"
                        required
                        autocomplete="current-password"
                        style="padding-right:44px;"
                    >
                    <button type="button"
                        onclick="togglePassword()"
                        style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;display:flex;align-items:center;color:#718096;"
                        id="toggle-pw">
                        <i data-lucide="eye" id="toggle-pw-icon" style="width:18px;height:18px;"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100" style="justify-content:center;padding:12px;gap:8px;">
                <i data-lucide="lock" style="width:16px;height:16px;"></i> Masuk
            </button>
        </form>

        <div style="margin-top:24px;padding-top:20px;border-top:1px solid #e2e8f0;text-align:center;">
            <p style="font-size:12px;color:#a0aec0;margin:0;">
                Sistem Pendukung Keputusan Karyawan Terbaik<br>
                Menggunakan Metode MOORA
            </p>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const pw = document.getElementById('password');
    const icon = document.getElementById('toggle-pw-icon');
    if (pw.type === 'password') {
        pw.type = 'text';
        icon.setAttribute('data-lucide', 'eye-off');
    } else {
        pw.type = 'password';
        icon.setAttribute('data-lucide', 'eye');
    }
    lucide.createIcons();
}
lucide.createIcons();
</script>
</body>
</html>
