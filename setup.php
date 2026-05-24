<?php
/**
 * Setup Script — SPK Karyawan Terbaik
 * Run this ONCE to initialize the database and seed default data.
 * DELETE or RENAME this file after setup is complete.
 */

// Security: simple token check (change this before deploying)
$setupToken = 'setup_spk_2024';
if (!isset($_GET['token']) || $_GET['token'] !== $setupToken) {
    die('<h2>Access Denied</h2><p>Provide the correct setup token: <code>setup.php?token=setup_spk_2024</code></p>');
}

require_once __DIR__ . '/config/db.php';

$db = getDB();
$log = [];

function runSQL(PDO $db, string $sql, string $label): void {
    global $log;
    try {
        $db->exec($sql);
        $log[] = "✅ $label";
    } catch (PDOException $e) {
        $log[] = "⚠️ $label — " . $e->getMessage();
    }
}

// ---- CREATE TABLES ----
runSQL($db, "CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'pimpinan') NOT NULL DEFAULT 'pimpinan',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB", "Create table: users");

runSQL($db, "CREATE TABLE IF NOT EXISTS `karyawan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama` VARCHAR(100) NOT NULL,
    `nip` VARCHAR(20) NOT NULL UNIQUE,
    `divisi` ENUM('Bisnis', 'Operasional') NOT NULL,
    `jabatan` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB", "Create table: karyawan");

runSQL($db, "CREATE TABLE IF NOT EXISTS `kriteria` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_kriteria` VARCHAR(100) NOT NULL,
    `bobot` DECIMAL(5,4) NOT NULL,
    `tipe` ENUM('benefit', 'cost') NOT NULL DEFAULT 'benefit',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB", "Create table: kriteria");

runSQL($db, "CREATE TABLE IF NOT EXISTS `penilaian` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `karyawan_id` INT NOT NULL,
    `kriteria_id` INT NOT NULL,
    `nilai` DECIMAL(5,2) NOT NULL,
    `periode` VARCHAR(7) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`kriteria_id`) REFERENCES `kriteria`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_penilaian` (`karyawan_id`, `kriteria_id`, `periode`)
) ENGINE=InnoDB", "Create table: penilaian");

runSQL($db, "CREATE TABLE IF NOT EXISTS `hasil_moora` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `karyawan_id` INT NOT NULL,
    `skor_akhir` DECIMAL(10,6) NOT NULL,
    `peringkat` INT NOT NULL,
    `periode` VARCHAR(7) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_hasil` (`karyawan_id`, `periode`)
) ENGINE=InnoDB", "Create table: hasil_moora");

// ---- SEED USERS ----
$adminHash    = password_hash('admin123',    PASSWORD_DEFAULT);
$pimpinanHash = password_hash('pimpinan123', PASSWORD_DEFAULT);

$stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role)");
$stmt->execute(['admin',    $adminHash,    'admin']);
$log[] = "✅ Seed user: admin (password: admin123)";
$stmt->execute(['pimpinan', $pimpinanHash, 'pimpinan']);
$log[] = "✅ Seed user: pimpinan (password: pimpinan123)";

// ---- SEED KRITERIA ----
$kriteriaCount = $db->query("SELECT COUNT(*) FROM kriteria")->fetchColumn();
if ($kriteriaCount == 0) {
    $kriteriaData = [
        ['KPI Achievement', 0.3000, 'benefit'],
        ['Absensi',         0.2000, 'cost'],
        ['Kedisiplinan',    0.2000, 'benefit'],
        ['Kerjasama Tim',   0.1500, 'benefit'],
        ['Kualitas Kerja',  0.1500, 'benefit'],
    ];
    $stmt = $db->prepare("INSERT INTO kriteria (nama_kriteria, bobot, tipe) VALUES (?, ?, ?)");
    foreach ($kriteriaData as $kr) {
        $stmt->execute($kr);
    }
    $log[] = "✅ Seed kriteria: 5 kriteria (total bobot = 1.0)";
} else {
    $log[] = "ℹ️ Kriteria already exists, skipping seed.";
}

// ---- SEED KARYAWAN ----
$karyawanCount = $db->query("SELECT COUNT(*) FROM karyawan")->fetchColumn();
if ($karyawanCount == 0) {
    $karyawanData = [
        ['Budi Santoso',    'BRI-2021-001', 'Bisnis',      'Account Officer'],
        ['Siti Rahayu',     'BRI-2021-002', 'Bisnis',      'Relationship Manager'],
        ['Ahmad Fauzi',     'BRI-2021-003', 'Bisnis',      'Sales Officer'],
        ['Dewi Lestari',    'BRI-2021-004', 'Bisnis',      'Account Officer'],
        ['Rudi Hartono',    'BRI-2021-005', 'Operasional', 'Teller'],
        ['Rina Wulandari',  'BRI-2021-006', 'Operasional', 'Customer Service'],
        ['Hendra Gunawan',  'BRI-2021-007', 'Operasional', 'Back Office'],
        ['Maya Sari',       'BRI-2021-008', 'Operasional', 'Teller'],
    ];
    $stmt = $db->prepare("INSERT INTO karyawan (nama, nip, divisi, jabatan) VALUES (?, ?, ?, ?)");
    foreach ($karyawanData as $k) {
        $stmt->execute($k);
    }
    $log[] = "✅ Seed karyawan: 8 karyawan (4 Bisnis, 4 Operasional)";
} else {
    $log[] = "ℹ️ Karyawan already exists, skipping seed.";
}

// ---- SEED SAMPLE PENILAIAN ----
$penilaianCount = $db->query("SELECT COUNT(*) FROM penilaian")->fetchColumn();
if ($penilaianCount == 0) {
    $periode = date('Y-m'); // current month
    $karyawanIds = $db->query("SELECT id FROM karyawan ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);
    $kriteriaIds = $db->query("SELECT id FROM kriteria ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);

    // Sample scores (realistic values)
    $sampleScores = [
        [85, 5, 88, 90, 87],   // Budi
        [92, 3, 90, 85, 91],   // Siti
        [78, 8, 75, 80, 76],   // Ahmad
        [88, 4, 85, 88, 89],   // Dewi
        [80, 6, 82, 78, 83],   // Rudi
        [95, 2, 93, 92, 94],   // Rina
        [72, 10, 70, 75, 73],  // Hendra
        [86, 5, 84, 87, 85],   // Maya
    ];

    $stmt = $db->prepare(
        "INSERT IGNORE INTO penilaian (karyawan_id, kriteria_id, nilai, periode) VALUES (?, ?, ?, ?)"
    );

    foreach ($karyawanIds as $ki => $kId) {
        foreach ($kriteriaIds as $kri => $krId) {
            $nilai = $sampleScores[$ki][$kri] ?? 75;
            $stmt->execute([$kId, $krId, $nilai, $periode]);
        }
    }
    $log[] = "✅ Seed penilaian: sample data untuk periode $periode";
} else {
    $log[] = "ℹ️ Penilaian already exists, skipping seed.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Setup — SPK Karyawan Terbaik</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; max-width: 700px; margin: 40px auto; padding: 20px; background: #f0f4f8; }
        .card { background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
        h2 { color: #003087; margin: 0 0 20px; }
        .log-item { padding: 8px 12px; margin: 6px 0; border-radius: 6px; font-size: 14px; background: #f7fafc; border-left: 3px solid #e2e8f0; }
        .log-item:has(✅) { border-color: #38a169; background: #f0fff4; }
        .log-item:has(⚠️) { border-color: #e53e3e; background: #fff5f5; }
        .log-item:has(ℹ️) { border-color: #3182ce; background: #ebf8ff; }
        .btn { display: inline-block; padding: 10px 20px; background: #003087; color: #fff; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 20px; }
        .warning { background: #fffbeb; border: 1px solid #f6e05e; border-radius: 8px; padding: 12px 16px; margin-top: 20px; font-size: 13px; color: #744210; }
    </style>
</head>
<body>
<div class="card">
    <h2>🔧 Setup SPK Karyawan Terbaik</h2>
    <p style="color:#718096;margin:0 0 20px;">Hasil inisialisasi database:</p>

    <?php foreach ($log as $item): ?>
        <div class="log-item"><?= htmlspecialchars($item) ?></div>
    <?php endforeach; ?>

    <div class="warning">
        ⚠️ <strong>Penting:</strong> Hapus atau rename file <code>setup.php</code> setelah setup selesai untuk keamanan.
    </div>

    <a href="login.php" class="btn">→ Pergi ke Halaman Login</a>
</div>
</body>
</html>
