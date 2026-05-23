<?php
// Layout helper - renders sidebar + topbar shell
// Usage: include this file after setting $pageTitle and $activePage

require_once __DIR__ . '/../functions/auth.php';
requireLogin();
$user = getCurrentUser();

$navItems = [
    ['href' => 'index.php',    'icon' => '📊', 'label' => 'Dashboard',       'page' => 'dashboard'],
    ['href' => 'karyawan.php', 'icon' => '👥', 'label' => 'Karyawan',         'page' => 'karyawan',  'admin' => true],
    ['href' => 'kriteria.php', 'icon' => '📋', 'label' => 'Kriteria & Bobot', 'page' => 'kriteria',  'admin' => true],
    ['href' => 'penilaian.php','icon' => '✏️', 'label' => 'Input Penilaian',  'page' => 'penilaian', 'admin' => true],
    ['href' => 'hasil.php',    'icon' => '🏆', 'label' => 'Hasil MOORA',      'page' => 'hasil'],
    ['href' => 'laporan.php',  'icon' => '📄', 'label' => 'Laporan PDF',      'page' => 'laporan'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'SPK Karyawan') ?> — SPK Karyawan Terbaik</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏦</text></svg>">
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">BRI</div>
        <h6>SPK Karyawan Terbaik</h6>
        <small>Bank BRI KCP Arundina</small>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Menu Utama</div>
        <?php foreach ($navItems as $item): ?>
            <?php if (!empty($item['admin']) && !isAdmin()) continue; ?>
            <a href="<?= BASE_URL ?>/<?= $item['href'] ?>"
               class="nav-link <?= ($activePage ?? '') === $item['page'] ? 'active' : '' ?>">
                <span><?= $item['icon'] ?></span>
                <span><?= $item['label'] ?></span>
                <?php if (!empty($item['admin'])): ?>
                    <span class="badge-role">Admin</span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>

        <div class="nav-section-title" style="margin-top:16px;">Akun</div>
        <a href="<?= BASE_URL ?>/logout.php" class="nav-link" onclick="return confirm('Yakin ingin keluar?')">
            <span>🚪</span>
            <span>Logout</span>
        </a>
    </nav>
</aside>

<!-- Main Wrapper -->
<div class="main-wrapper">
    <!-- Topbar -->
    <header class="topbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <button onclick="toggleSidebar()" style="display:none;background:none;border:none;font-size:22px;cursor:pointer;" id="sidebar-toggle">☰</button>
            <span class="topbar-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></span>
        </div>
        <div class="topbar-right">
            <div class="user-badge">
                <div class="user-avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($user['username']) ?></div>
                    <div class="user-role"><?= htmlspecialchars($user['role']) ?></div>
                </div>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <main class="page-content">
