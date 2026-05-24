<?php
$pageTitle  = 'Input Penilaian';
$activePage = 'penilaian';

require_once __DIR__ . '/includes/layout.php';
requireAdmin();

$db = getDB();
$errors  = [];
$success = '';

// ---- HANDLE SAVE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_penilaian'])) {
    $periode = sanitize($_POST['periode'] ?? '');
    $values  = $_POST['nilai'] ?? [];

    if (empty($periode) || !preg_match('/^\d{4}-\d{2}$/', $periode)) {
        $errors[] = 'Periode tidak valid. Format: YYYY-MM';
    }

    if (empty($errors) && !empty($values)) {
        $stmt = $db->prepare(
            "INSERT INTO penilaian (karyawan_id, kriteria_id, nilai, periode)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)"
        );
        foreach ($values as $karyawanId => $kriteriaValues) {
            foreach ($kriteriaValues as $kriteriaId => $nilai) {
                $nilai = max(0, min(100, (float)$nilai));
                $stmt->execute([(int)$karyawanId, (int)$kriteriaId, $nilai, $periode]);
            }
        }
        header('Location: penilaian.php?success=1&msg=' . urlencode('Penilaian berhasil disimpan.') . '&periode=' . urlencode($periode));
        exit;
    }
}

// ---- HANDLE DELETE PERIODE ----
if (isset($_GET['delete_periode'])) {
    $periode = sanitize($_GET['delete_periode']);
    $stmt = $db->prepare("DELETE FROM penilaian WHERE periode = ?");
    $stmt->execute([$periode]);
    // Also delete hasil
    $stmt2 = $db->prepare("DELETE FROM hasil_moora WHERE periode = ?");
    $stmt2->execute([$periode]);
    header('Location: penilaian.php?deleted=1');
    exit;
}

// ---- FETCH FILTERS ----
$selectedPeriode = sanitize($_GET['periode'] ?? '');
$selectedDivisi  = sanitize($_GET['divisi'] ?? '');

// Fetch kriteria
$kriteriaList = $db->query("SELECT * FROM kriteria ORDER BY id ASC")->fetchAll();

// Fetch karyawan
$sqlK = "SELECT * FROM karyawan";
$paramsK = [];
if (in_array($selectedDivisi, ['Bisnis', 'Operasional'])) {
    $sqlK .= " WHERE divisi = ?";
    $paramsK[] = $selectedDivisi;
}
$sqlK .= " ORDER BY divisi, nama ASC";
$stmtK = $db->prepare($sqlK);
$stmtK->execute($paramsK);
$karyawanList = $stmtK->fetchAll();

// Fetch existing values for selected periode
$existingValues = [];
if ($selectedPeriode) {
    $stmtV = $db->prepare("SELECT karyawan_id, kriteria_id, nilai FROM penilaian WHERE periode = ?");
    $stmtV->execute([$selectedPeriode]);
    foreach ($stmtV->fetchAll() as $row) {
        $existingValues[$row['karyawan_id']][$row['kriteria_id']] = $row['nilai'];
    }
}

// Existing periods
$periodeList = $db->query("SELECT DISTINCT periode FROM penilaian ORDER BY periode DESC")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="page-header" style="display:flex;flex-direction:column;gap:4px;">
    <h1 style="display:flex;align-items:center;gap:10px;"><i data-lucide="pen-tool" style="width:28px;height:28px;color:var(--bri-blue);"></i> Input Penilaian Karyawan</h1>
    <p>Masukkan nilai penilaian karyawan per periode.</p>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger" style="display:flex;align-items:center;gap:8px;">
        <i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
        <div><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    </div>
<?php endif; ?>

<?php if (empty($kriteriaList)): ?>
    <div class="alert alert-warning" style="display:flex;align-items:center;gap:8px;">
        <i data-lucide="alert-triangle" style="width:18px;height:18px;flex-shrink:0;"></i>
        <span>Belum ada kriteria. <a href="<?= BASE_URL ?>/kriteria.php" style="color:inherit;font-weight:700;">Tambahkan kriteria</a> terlebih dahulu.</span>
    </div>
<?php elseif (empty($karyawanList)): ?>
    <div class="alert alert-warning" style="display:flex;align-items:center;gap:8px;">
        <i data-lucide="alert-triangle" style="width:18px;height:18px;flex-shrink:0;"></i>
        <span>Belum ada karyawan. <a href="<?= BASE_URL ?>/karyawan.php" style="color:inherit;font-weight:700;">Tambahkan karyawan</a> terlebih dahulu.</span>
    </div>
<?php else: ?>

<!-- Filter Form -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-body">
        <form method="GET" style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group" style="margin:0;flex:1;min-width:180px;">
                <label class="form-label">Periode (Bulan/Tahun)</label>
                <input type="month" name="periode" class="form-control"
                    value="<?= htmlspecialchars($selectedPeriode) ?>"
                    placeholder="YYYY-MM">
            </div>
            <div class="form-group" style="margin:0;flex:1;min-width:160px;">
                <label class="form-label">Filter Divisi</label>
                <select name="divisi" class="form-select">
                    <option value="">Semua Divisi</option>
                    <option value="Bisnis" <?= $selectedDivisi === 'Bisnis' ? 'selected' : '' ?>>Bisnis</option>
                    <option value="Operasional" <?= $selectedDivisi === 'Operasional' ? 'selected' : '' ?>>Operasional</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;">
                <i data-lucide="search" style="width:14px;height:14px;"></i> Tampilkan
            </button>
        </form>
    </div>
</div>

<?php if ($selectedPeriode): ?>
<!-- Input Grid -->
<div class="card">
    <div class="card-header">
        <h5 style="display:flex;align-items:center;gap:8px;"><i data-lucide="file-spreadsheet" style="width:18px;height:18px;color:var(--bri-blue);"></i> Form Penilaian — <?= htmlspecialchars($selectedPeriode) ?></h5>
        <div style="display:flex;gap:8px;align-items:center;">
            <button type="button" class="btn btn-sm btn-secondary" onclick="fillAllValues(0)">Reset Semua</button>
            <?php if (in_array($selectedPeriode, $periodeList)): ?>
                <button class="btn btn-sm btn-danger" style="display:inline-flex;align-items:center;gap:4px;"
                    onclick="confirmDelete('penilaian.php?delete_periode=<?= urlencode($selectedPeriode) ?>', 'semua penilaian periode <?= htmlspecialchars($selectedPeriode) ?>')">
                    <i data-lucide="trash-2" style="width:12px;height:12px;"></i> Hapus Periode
                </button>
            <?php endif; ?>
        </div>
    </div>
    <form method="POST">
        <input type="hidden" name="periode" value="<?= htmlspecialchars($selectedPeriode) ?>">
        <input type="hidden" name="save_penilaian" value="1">

        <div style="overflow-x:auto;">
            <table class="table penilaian-table">
                <thead>
                    <tr>
                        <th style="min-width:180px;">Karyawan</th>
                        <th>Divisi</th>
                        <?php foreach ($kriteriaList as $kr): ?>
                            <th style="min-width:100px;text-align:center;">
                                <?= htmlspecialchars($kr['nama_kriteria']) ?>
                                <br>
                                <span class="badge badge-<?= $kr['tipe'] ?>" style="font-size:9px;">
                                    <?= $kr['tipe'] === 'benefit' ? '↑' : '↓' ?>
                                    <?= number_format((float)$kr['bobot'] * 100, 0) ?>%
                                </span>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($karyawanList as $k): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($k['nama']) ?></strong>
                            <div style="font-size:11px;color:#718096;"><?= htmlspecialchars($k['jabatan']) ?></div>
                        </td>
                        <td>
                            <span class="badge badge-<?= strtolower($k['divisi']) ?>">
                                <?= htmlspecialchars($k['divisi']) ?>
                            </span>
                        </td>
                        <?php foreach ($kriteriaList as $kr): ?>
                            <td style="text-align:center;">
                                <input
                                    type="number"
                                    name="nilai[<?= $k['id'] ?>][<?= $kr['id'] ?>]"
                                    value="<?= htmlspecialchars($existingValues[$k['id']][$kr['id']] ?? '') ?>"
                                    min="0" max="100" step="0.01"
                                    placeholder="0-100"
                                    required
                                >
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="padding:16px 24px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:10px;">
            <a href="penilaian.php" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;">
                <i data-lucide="save" style="width:14px;height:14px;"></i> Simpan Semua Penilaian
            </button>
        </div>
    </form>
</div>
<?php else: ?>
    <div class="alert alert-info" style="display:flex;align-items:center;gap:8px;">
        <i data-lucide="info" style="width:18px;height:18px;flex-shrink:0;"></i>
        <span>Pilih periode untuk menampilkan form input penilaian.</span>
    </div>
<?php endif; ?>

<!-- Existing Periods -->
<?php if (!empty($periodeList)): ?>
<div class="card" style="margin-top:20px;">
    <div class="card-header">
        <h5 style="display:flex;align-items:center;gap:8px;"><i data-lucide="calendar" style="width:18px;height:18px;color:var(--bri-blue);"></i> Periode Penilaian Tersimpan</h5>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Periode</th>
                    <th>Jumlah Data</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($periodeList as $p): ?>
                <?php
                    $cnt = $db->prepare("SELECT COUNT(*) FROM penilaian WHERE periode = ?");
                    $cnt->execute([$p]);
                    $jumlah = $cnt->fetchColumn();
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($p) ?></strong></td>
                    <td><?= $jumlah ?> entri</td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <a href="penilaian.php?periode=<?= urlencode($p) ?>" class="btn btn-sm btn-outline" style="display:inline-flex;align-items:center;gap:4px;">
                                <i data-lucide="edit-2" style="width:12px;height:12px;"></i> Edit
                            </a>
                            <a href="hasil.php?periode=<?= urlencode($p) ?>" class="btn btn-sm btn-gold" style="display:inline-flex;align-items:center;gap:4px;">
                                <i data-lucide="calculator" style="width:12px;height:12px;"></i> Hitung MOORA
                            </a>
                            <button class="btn btn-sm btn-danger btn-icon"
                                onclick="confirmDelete('penilaian.php?delete_periode=<?= urlencode($p) ?>', 'periode <?= htmlspecialchars($p) ?>')">
                                <i data-lucide="trash-2" style="width:12px;height:12px;"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php endif; // end if kriteria & karyawan exist ?>

<?php
require_once __DIR__ . '/includes/layout_end.php';
?>
