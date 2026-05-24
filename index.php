<?php
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/functions/moora.php';

$db = getDB();

// Stats
$totalKaryawan = $db->query("SELECT COUNT(*) FROM karyawan")->fetchColumn();
$totalKriteria = $db->query("SELECT COUNT(*) FROM kriteria")->fetchColumn();
$periodeList   = getPeriodeList();
$periodeAktif  = $periodeList[0] ?? null;

// Bobot total
$bobotTotal = $db->query("SELECT SUM(bobot) FROM kriteria")->fetchColumn();

// Top 5 karyawan from latest calculated period
$top5 = [];
$calculatedPeriodes = getCalculatedPeriodeList();
$latestCalc = $calculatedPeriodes[0] ?? null;

if ($latestCalc) {
    $stmt = $db->prepare(
        "SELECT h.peringkat, h.skor_akhir, k.nama, k.divisi
         FROM hasil_moora h
         INNER JOIN karyawan k ON k.id = h.karyawan_id
         WHERE h.periode = ?
         ORDER BY h.peringkat ASC
         LIMIT 5"
    );
    $stmt->execute([$latestCalc]);
    $top5 = $stmt->fetchAll();
}

// Recent penilaian count
$totalPenilaian = $db->query("SELECT COUNT(DISTINCT periode) FROM penilaian")->fetchColumn();
?>

<div class="page-header" style="display:flex;flex-direction:column;gap:4px;">
    <h1 style="display:flex;align-items:center;gap:10px;"><i data-lucide="layout-dashboard" style="width:28px;height:28px;color:var(--bri-blue);"></i> Dashboard</h1>
    <p>Selamat datang, <strong><?= htmlspecialchars($user['username']) ?></strong>. Berikut ringkasan sistem SPK Karyawan Terbaik.</p>
</div>

<!-- Stat Cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:28px;">
    <div class="stat-card blue">
        <div class="stat-icon blue"><i data-lucide="users"></i></div>
        <div class="stat-value"><?= $totalKaryawan ?></div>
        <div class="stat-label">Total Karyawan</div>
    </div>
    <div class="stat-card gold">
        <div class="stat-icon gold"><i data-lucide="clipboard-list"></i></div>
        <div class="stat-value"><?= $totalKriteria ?></div>
        <div class="stat-label">Total Kriteria</div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon green"><i data-lucide="calendar"></i></div>
        <div class="stat-value"><?= count($periodeList) ?></div>
        <div class="stat-label">Periode Penilaian</div>
    </div>
    <div class="stat-card <?= (abs((float)$bobotTotal - 1.0) < 0.0001) ? 'green' : 'red' ?>">
        <div class="stat-icon <?= (abs((float)$bobotTotal - 1.0) < 0.0001) ? 'green' : 'red' ?>"><i data-lucide="scale"></i></div>
        <div class="stat-value"><?= number_format((float)$bobotTotal, 2) ?></div>
        <div class="stat-label">Total Bobot Kriteria</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
    <!-- Chart Top 5 -->
    <div class="card">
        <div class="card-header">
            <h5 style="display:flex;align-items:center;gap:8px;"><i data-lucide="award" style="width:18px;height:18px;color:var(--bri-blue);"></i> Top 5 Karyawan Terbaik</h5>
            <?php if ($latestCalc): ?>
                <span style="font-size:12px;color:#718096;">
                    <?= htmlspecialchars($latestCalc) ?>
                </span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (empty($top5)): ?>
                <div class="empty-state">
                    <i data-lucide="bar-chart-3" style="width:36px;height:36px;color:#a0aec0;margin-bottom:12px;"></i>
                    <p>Belum ada hasil perhitungan MOORA.<br>
                    <a href="<?= BASE_URL ?>/hasil.php" style="color:var(--bri-blue);">Hitung sekarang →</a></p>
                </div>
            <?php else: ?>
                <div class="chart-container">
                    <canvas id="chartTop5"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <h5 style="display:flex;align-items:center;gap:8px;"><i data-lucide="zap" style="width:18px;height:18px;color:var(--bri-blue);"></i> Akses Cepat</h5>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <?php if (isAdmin()): ?>
                <a href="<?= BASE_URL ?>/karyawan.php" class="btn btn-outline" style="justify-content:center;padding:16px 12px;flex-direction:column;gap:8px;height:auto;">
                    <i data-lucide="users" style="width:24px;height:24px;"></i>
                    <span>Karyawan</span>
                </a>
                <a href="<?= BASE_URL ?>/kriteria.php" class="btn btn-outline" style="justify-content:center;padding:16px 12px;flex-direction:column;gap:8px;height:auto;">
                    <i data-lucide="clipboard-list" style="width:24px;height:24px;"></i>
                    <span>Kriteria</span>
                </a>
                <a href="<?= BASE_URL ?>/penilaian.php" class="btn btn-primary" style="justify-content:center;padding:16px 12px;flex-direction:column;gap:8px;height:auto;">
                    <i data-lucide="pen-tool" style="width:24px;height:24px;"></i>
                    <span>Input Nilai</span>
                </a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/hasil.php" class="btn btn-gold" style="justify-content:center;padding:16px 12px;flex-direction:column;gap:8px;height:auto;">
                    <i data-lucide="trophy" style="width:24px;height:24px;"></i>
                    <span>Hasil MOORA</span>
                </a>
                <a href="<?= BASE_URL ?>/laporan.php" class="btn btn-success" style="justify-content:center;padding:16px 12px;flex-direction:column;gap:8px;height:auto;">
                    <i data-lucide="file-text" style="width:24px;height:24px;"></i>
                    <span>Laporan PDF</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Top 5 Table -->
<?php if (!empty($top5)): ?>
<div class="card">
    <div class="card-header">
        <h5 style="display:flex;align-items:center;gap:8px;"><i data-lucide="medal" style="width:18px;height:18px;color:var(--bri-blue);"></i> Peringkat Karyawan Terbaik — <?= htmlspecialchars($latestCalc) ?></h5>
        <a href="<?= BASE_URL ?>/hasil.php" class="btn btn-sm btn-outline">Lihat Semua →</a>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Peringkat</th>
                    <th>Nama Karyawan</th>
                    <th>Divisi</th>
                    <th>Skor MOORA</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top5 as $row): ?>
                <tr>
                    <td>
                        <div class="rank-badge rank-<?= $row['peringkat'] <= 3 ? $row['peringkat'] : 'other' ?>">
                            <?= $row['peringkat'] ?>
                        </div>
                    </td>
                    <td><strong><?= htmlspecialchars($row['nama']) ?></strong></td>
                    <td>
                        <span class="badge badge-<?= strtolower($row['divisi']) ?>">
                            <?= htmlspecialchars($row['divisi']) ?>
                        </span>
                    </td>
                    <td><code><?= number_format((float)$row['skor_akhir'], 6) ?></code></td>
                    <td>
                        <?php if ($row['peringkat'] === 1): ?>
                            <span class="badge badge-terbaik" style="display:inline-flex;align-items:center;gap:4px;"><i data-lucide="trophy" style="width:12px;height:12px;"></i> Terbaik</span>
                        <?php elseif ($row['peringkat'] <= 3): ?>
                            <span class="badge" style="background:#f0fff4;color:#22543d;">Top 3</span>
                        <?php else: ?>
                            <span class="badge" style="background:#f7fafc;color:#718096;">Top 5</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php
$extraScripts = '';
if (!empty($top5)):
    $labels = array_map(fn($r) => $r['nama'], $top5);
    $scores = array_map(fn($r) => round((float)$r['skor_akhir'], 6), $top5);
    $labelsJson = json_encode($labels);
    $scoresJson = json_encode($scores);
    $extraScripts = <<<HTML
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('chartTop5').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: {$labelsJson},
        datasets: [{
            label: 'Skor MOORA',
            data: {$scoresJson},
            backgroundColor: [
                'rgba(255,215,0,0.8)',
                'rgba(192,192,192,0.8)',
                'rgba(205,127,50,0.8)',
                'rgba(0,48,135,0.6)',
                'rgba(0,48,135,0.4)',
            ],
            borderColor: [
                '#FFD700','#C0C0C0','#CD7F32','#003087','#003087'
            ],
            borderWidth: 2,
            borderRadius: 8,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => 'Skor: ' + ctx.raw.toFixed(6)
                }
            }
        },
        scales: {
            y: {
                beginAtZero: false,
                grid: { color: '#f0f4f8' },
                ticks: { font: { size: 11 } }
            },
            x: {
                grid: { display: false },
                ticks: { font: { size: 11 } }
            }
        }
    }
});
</script>
HTML;
endif;

require_once __DIR__ . '/includes/layout_end.php';
?>
