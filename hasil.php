<?php
$pageTitle  = 'Hasil MOORA';
$activePage = 'hasil';

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/functions/moora.php';

$db = getDB();

$periodeList = getPeriodeList();
$selectedPeriode = sanitize($_GET['periode'] ?? ($periodeList[0] ?? ''));
$selectedDivisi  = sanitize($_GET['divisi'] ?? '');

$moora_result = null;
$hasilList    = [];
$kriteriaList = $db->query("SELECT * FROM kriteria ORDER BY id ASC")->fetchAll();

// ---- HANDLE HITUNG MOORA ----
if (isset($_GET['hitung']) && !empty($selectedPeriode)) {
    $moora_result = hitungMOORA($selectedPeriode);
    if (isset($moora_result['error'])) {
        $moora_error = $moora_result['error'];
        $moora_result = null;
    }
}

// ---- FETCH SAVED RESULTS ----
if ($selectedPeriode) {
    $hasilList = getHasilMOORA($selectedPeriode, $selectedDivisi ?: null);
}

// Check if results exist for this period
$hasResults = !empty($hasilList);
?>

<div class="page-header">
    <h1>🏆 Hasil Perhitungan MOORA</h1>
    <p>Peringkat karyawan terbaik berdasarkan metode MOORA.</p>
</div>

<!-- Filter + Hitung -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-body">
        <form method="GET" style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group" style="margin:0;flex:1;min-width:180px;">
                <label class="form-label">Pilih Periode</label>
                <select name="periode" class="form-select">
                    <option value="">-- Pilih Periode --</option>
                    <?php foreach ($periodeList as $p): ?>
                        <option value="<?= htmlspecialchars($p) ?>" <?= $selectedPeriode === $p ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin:0;flex:1;min-width:160px;">
                <label class="form-label">Filter Divisi</label>
                <select name="divisi" class="form-select">
                    <option value="">Semua Divisi</option>
                    <option value="Bisnis" <?= $selectedDivisi === 'Bisnis' ? 'selected' : '' ?>>Bisnis</option>
                    <option value="Operasional" <?= $selectedDivisi === 'Operasional' ? 'selected' : '' ?>>Operasional</option>
                </select>
            </div>
            <button type="submit" class="btn btn-outline">🔍 Tampilkan</button>
            <?php if ($selectedPeriode): ?>
                <a href="hasil.php?periode=<?= urlencode($selectedPeriode) ?>&divisi=<?= urlencode($selectedDivisi) ?>&hitung=1"
                   class="btn btn-gold"
                   onclick="Spinner.show('Menghitung MOORA...')">
                    ⚙️ Hitung MOORA
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if (isset($moora_error)): ?>
    <div class="alert alert-danger">⚠️ <?= htmlspecialchars($moora_error) ?></div>
<?php elseif (isset($moora_result) && !empty($moora_result['results'])): ?>
    <div class="alert alert-success">✅ Perhitungan MOORA berhasil! <?= count($moora_result['results']) ?> karyawan telah diranking.</div>
<?php endif; ?>

<?php if (empty($periodeList)): ?>
    <div class="alert alert-warning">
        ⚠️ Belum ada data penilaian. <a href="<?= BASE_URL ?>/penilaian.php" style="color:inherit;font-weight:700;">Input penilaian</a> terlebih dahulu.
    </div>
<?php elseif ($selectedPeriode && !$hasResults): ?>
    <div class="alert alert-info">
        ℹ️ Belum ada hasil untuk periode <strong><?= htmlspecialchars($selectedPeriode) ?></strong>.
        Klik <strong>"Hitung MOORA"</strong> untuk memproses.
    </div>
<?php endif; ?>

<?php if ($hasResults): ?>
<!-- Results Table -->
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <h5>🏆 Peringkat Karyawan — <?= htmlspecialchars($selectedPeriode) ?></h5>
        <div style="display:flex;gap:8px;">
            <a href="laporan.php?periode=<?= urlencode($selectedPeriode) ?>&divisi=<?= urlencode($selectedDivisi) ?>"
               class="btn btn-sm btn-success">📄 Export PDF</a>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Peringkat</th>
                    <th>Nama Karyawan</th>
                    <th>NIP</th>
                    <th>Divisi</th>
                    <th>Jabatan</th>
                    <th>Skor MOORA</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hasilList as $row): ?>
                <tr <?= $row['peringkat'] === 1 ? 'style="background:rgba(255,215,0,0.05);"' : '' ?>>
                    <td>
                        <div class="rank-badge rank-<?= $row['peringkat'] <= 3 ? $row['peringkat'] : 'other' ?>">
                            <?= $row['peringkat'] ?>
                        </div>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($row['nama']) ?></strong>
                    </td>
                    <td><code style="font-size:12px;"><?= htmlspecialchars($row['nip']) ?></code></td>
                    <td>
                        <span class="badge badge-<?= strtolower($row['divisi']) ?>">
                            <?= htmlspecialchars($row['divisi']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($row['jabatan']) ?></td>
                    <td>
                        <code style="font-size:13px;font-weight:700;color:var(--bri-blue);">
                            <?= number_format((float)$row['skor_akhir'], 6) ?>
                        </code>
                    </td>
                    <td>
                        <?php if ($row['peringkat'] === 1): ?>
                            <span class="badge badge-terbaik">🏆 Terbaik</span>
                        <?php elseif ($row['peringkat'] === 2): ?>
                            <span class="badge" style="background:#f0f0f0;color:#555;">🥈 Runner Up</span>
                        <?php elseif ($row['peringkat'] === 3): ?>
                            <span class="badge" style="background:#fff3e0;color:#7b4f00;">🥉 Peringkat 3</span>
                        <?php else: ?>
                            <span class="badge" style="background:#f7fafc;color:#718096;">Peringkat <?= $row['peringkat'] ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MOORA Detail Steps -->
<?php if (isset($moora_result) && !empty($moora_result['results'])): ?>
<div class="card">
    <div class="card-header">
        <h5>📐 Detail Perhitungan MOORA</h5>
        <button class="btn btn-sm btn-secondary" onclick="toggleDetail()">Tampilkan/Sembunyikan</button>
    </div>
    <div id="moora-detail" style="display:none;">
        <!-- Decision Matrix -->
        <div style="padding:20px 24px;border-bottom:1px solid #e2e8f0;">
            <h6 style="font-weight:700;color:var(--bri-blue);margin:0 0 12px;">1. Matriks Keputusan (X)</h6>
            <div style="overflow-x:auto;">
                <table class="table" style="font-size:12px;">
                    <thead>
                        <tr>
                            <th>Karyawan</th>
                            <?php foreach ($kriteriaList as $kr): ?>
                                <th style="text-align:center;"><?= htmlspecialchars($kr['nama_kriteria']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($moora_result['results'] as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['nama']) ?></td>
                            <?php foreach ($kriteriaList as $kr): ?>
                                <td style="text-align:center;"><?= number_format((float)($r['matrix'][$kr['id']] ?? 0), 2) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Normalized Matrix -->
        <div style="padding:20px 24px;border-bottom:1px solid #e2e8f0;">
            <h6 style="font-weight:700;color:var(--bri-blue);margin:0 0 12px;">2. Matriks Ternormalisasi (r<sub>ij</sub>)</h6>
            <p style="font-size:12px;color:#718096;margin:0 0 10px;">r<sub>ij</sub> = x<sub>ij</sub> / √(Σx<sub>kj</sub>²)</p>
            <div style="overflow-x:auto;">
                <table class="table" style="font-size:12px;">
                    <thead>
                        <tr>
                            <th>Karyawan</th>
                            <?php foreach ($kriteriaList as $kr): ?>
                                <th style="text-align:center;"><?= htmlspecialchars($kr['nama_kriteria']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($moora_result['results'] as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['nama']) ?></td>
                            <?php foreach ($kriteriaList as $kr): ?>
                                <td style="text-align:center;"><?= number_format((float)($r['normalized'][$kr['id']] ?? 0), 6) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Weighted Normalized -->
        <div style="padding:20px 24px;border-bottom:1px solid #e2e8f0;">
            <h6 style="font-weight:700;color:var(--bri-blue);margin:0 0 12px;">3. Matriks Ternormalisasi Terbobot (v<sub>ij</sub>)</h6>
            <p style="font-size:12px;color:#718096;margin:0 0 10px;">v<sub>ij</sub> = w<sub>j</sub> × r<sub>ij</sub></p>
            <div style="overflow-x:auto;">
                <table class="table" style="font-size:12px;">
                    <thead>
                        <tr>
                            <th>Karyawan</th>
                            <?php foreach ($kriteriaList as $kr): ?>
                                <th style="text-align:center;">
                                    <?= htmlspecialchars($kr['nama_kriteria']) ?>
                                    <span class="badge badge-<?= $kr['tipe'] ?>" style="font-size:9px;"><?= $kr['tipe'] ?></span>
                                </th>
                            <?php endforeach; ?>
                            <th style="text-align:center;">Yi (Skor)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($moora_result['results'] as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['nama']) ?></td>
                            <?php foreach ($kriteriaList as $kr): ?>
                                <td style="text-align:center;"><?= number_format((float)($r['weighted'][$kr['id']] ?? 0), 6) ?></td>
                            <?php endforeach; ?>
                            <td style="text-align:center;font-weight:700;color:var(--bri-blue);">
                                <?= number_format((float)$r['skor_akhir'], 6) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Formula -->
        <div style="padding:20px 24px;">
            <h6 style="font-weight:700;color:var(--bri-blue);margin:0 0 12px;">4. Rumus Yi</h6>
            <div class="alert alert-info" style="font-family:monospace;font-size:13px;">
                Yi = Σ(bobot × r<sub>ij</sub>) untuk kriteria Benefit − Σ(bobot × r<sub>ij</sub>) untuk kriteria Cost
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php endif; // end if hasResults ?>

<?php
$extraScripts = <<<'HTML'
<script>
function toggleDetail() {
    const el = document.getElementById('moora-detail');
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
HTML;

require_once __DIR__ . '/includes/layout_end.php';
?>
