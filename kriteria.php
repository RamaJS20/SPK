<?php
$pageTitle  = 'Manajemen Kriteria & Bobot';
$activePage = 'kriteria';

require_once __DIR__ . '/includes/layout.php';
requireAdmin();

$db = getDB();
$errors = [];

// ---- HANDLE POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action       = $_POST['action'] ?? '';
    $id           = (int)($_POST['id'] ?? 0);
    $nama_kriteria = sanitize($_POST['nama_kriteria'] ?? '');
    $bobot        = (float)($_POST['bobot'] ?? 0);
    $tipe         = sanitize($_POST['tipe'] ?? '');

    if (empty($nama_kriteria)) $errors[] = 'Nama kriteria wajib diisi.';
    if ($bobot <= 0 || $bobot > 1) $errors[] = 'Bobot harus antara 0.0001 dan 1.0.';
    if (!in_array($tipe, ['benefit', 'cost'])) $errors[] = 'Tipe tidak valid.';

    if (empty($errors)) {
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO kriteria (nama_kriteria, bobot, tipe) VALUES (?, ?, ?)");
            $stmt->execute([$nama_kriteria, $bobot, $tipe]);
            header('Location: kriteria.php?success=1&msg=' . urlencode('Kriteria berhasil ditambahkan.'));
            exit;
        } elseif ($action === 'edit' && $id > 0) {
            $stmt = $db->prepare("UPDATE kriteria SET nama_kriteria=?, bobot=?, tipe=? WHERE id=?");
            $stmt->execute([$nama_kriteria, $bobot, $tipe, $id]);
            header('Location: kriteria.php?success=1&msg=' . urlencode('Kriteria berhasil diperbarui.'));
            exit;
        }
    }
}

// ---- HANDLE DELETE ----
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM kriteria WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: kriteria.php?deleted=1');
    exit;
}

// ---- FETCH ----
$kriteriaList = $db->query("SELECT * FROM kriteria ORDER BY id ASC")->fetchAll();
$bobotTotal   = array_sum(array_column($kriteriaList, 'bobot'));
$bobotValid   = abs($bobotTotal - 1.0) < 0.0001;
?>

<div class="page-header">
    <h1>📋 Manajemen Kriteria & Bobot</h1>
    <p>Kelola kriteria penilaian dan bobot untuk perhitungan MOORA.</p>
</div>

<!-- Bobot Warning -->
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <div class="bobot-total <?= $bobotValid ? 'valid' : 'invalid' ?>">
            ⚖️ Total Bobot:
            <strong id="bobot-total-display"><?= number_format($bobotTotal, 4) ?></strong>
            <?= $bobotValid ? '✓ Valid' : '⚠ Harus = 1.0' ?>
        </div>
        <?php if (!$bobotValid && !empty($kriteriaList)): ?>
            <div class="alert alert-warning" style="margin:0;padding:8px 14px;">
                ⚠️ Total bobot kriteria harus sama dengan <strong>1.0</strong> agar perhitungan MOORA akurat.
            </div>
        <?php endif; ?>
    </div>
    <button class="btn btn-primary" onclick="Modal.open('modal-kriteria')">
        ➕ Tambah Kriteria
    </button>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <span>⚠️</span>
        <div><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    </div>
<?php endif; ?>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <h5>📋 Daftar Kriteria</h5>
        <span style="font-size:13px;color:#718096;"><?= count($kriteriaList) ?> kriteria</span>
    </div>
    <div class="table-wrapper">
        <?php if (empty($kriteriaList)): ?>
            <div class="empty-state">
                <i>📋</i>
                <p>Belum ada kriteria. Tambahkan kriteria penilaian terlebih dahulu.</p>
            </div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Kriteria</th>
                    <th>Bobot</th>
                    <th>Bobot (%)</th>
                    <th>Tipe</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kriteriaList as $i => $kr): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($kr['nama_kriteria']) ?></strong></td>
                    <td>
                        <code><?= number_format((float)$kr['bobot'], 4) ?></code>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="flex:1;height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden;">
                                <div style="width:<?= min(100, (float)$kr['bobot'] * 100) ?>%;height:100%;background:var(--bri-blue);border-radius:4px;"></div>
                            </div>
                            <span style="font-size:12px;color:#718096;min-width:36px;"><?= number_format((float)$kr['bobot'] * 100, 1) ?>%</span>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-<?= $kr['tipe'] ?>">
                            <?= $kr['tipe'] === 'benefit' ? '↑ Benefit' : '↓ Cost' ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <button class="btn btn-sm btn-outline"
                                onclick="openEditKriteria(<?= htmlspecialchars(json_encode($kr)) ?>)">
                                ✏️ Edit
                            </button>
                            <button class="btn btn-sm btn-danger"
                                onclick="confirmDelete('kriteria.php?delete=<?= $kr['id'] ?>', '<?= htmlspecialchars(addslashes($kr['nama_kriteria'])) ?>')">
                                🗑️
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f7fafc;">
                    <td colspan="2" style="font-weight:700;padding:12px 16px;">Total</td>
                    <td><code style="font-weight:700;"><?= number_format($bobotTotal, 4) ?></code></td>
                    <td>
                        <span style="font-size:13px;font-weight:700;color:<?= $bobotValid ? '#22543d' : '#c53030' ?>;">
                            <?= $bobotValid ? '✓ Valid (100%)' : '⚠ ' . number_format($bobotTotal * 100, 1) . '%' ?>
                        </span>
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Info Box -->
<div class="card" style="margin-top:20px;">
    <div class="card-body">
        <h6 style="font-weight:700;color:var(--bri-blue);margin:0 0 12px;">ℹ️ Panduan Kriteria MOORA</h6>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;font-size:13px;color:#4a5568;">
            <div>
                <strong>Tipe Benefit (↑)</strong><br>
                Nilai yang lebih tinggi = lebih baik.<br>
                Contoh: KPI Achievement, Kualitas Kerja, Kerjasama Tim
            </div>
            <div>
                <strong>Tipe Cost (↓)</strong><br>
                Nilai yang lebih rendah = lebih baik.<br>
                Contoh: Absensi (jumlah ketidakhadiran), Pelanggaran
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="modal-kriteria">
    <div class="modal-box">
        <div class="modal-header">
            <h5 id="modal-kriteria-title">➕ Tambah Kriteria</h5>
            <button class="modal-close" onclick="Modal.close('modal-kriteria')">×</button>
        </div>
        <form method="POST" id="form-kriteria">
            <div class="modal-body">
                <input type="hidden" name="action" id="kr-action" value="add">
                <input type="hidden" name="id" id="kr-id" value="0">

                <div class="form-group">
                    <label class="form-label">Nama Kriteria <span style="color:red">*</span></label>
                    <input type="text" name="nama_kriteria" id="kr-nama" class="form-control"
                        placeholder="Contoh: KPI Achievement" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Bobot <span style="color:red">*</span></label>
                        <input type="number" name="bobot" id="kr-bobot" class="form-control bobot-input"
                            placeholder="0.0000" step="0.0001" min="0.0001" max="1" required>
                        <div class="form-hint">Nilai antara 0.0001 – 1.0</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipe <span style="color:red">*</span></label>
                        <select name="tipe" id="kr-tipe" class="form-select" required>
                            <option value="">Pilih Tipe</option>
                            <option value="benefit">↑ Benefit (lebih tinggi = lebih baik)</option>
                            <option value="cost">↓ Cost (lebih rendah = lebih baik)</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="Modal.close('modal-kriteria')">Batal</button>
                <button type="submit" class="btn btn-primary" id="btn-submit-kr">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php
$extraScripts = <<<'HTML'
<script>
function openEditKriteria(data) {
    document.getElementById('modal-kriteria-title').textContent = '✏️ Edit Kriteria';
    document.getElementById('kr-action').value = 'edit';
    document.getElementById('kr-id').value = data.id;
    document.getElementById('kr-nama').value = data.nama_kriteria;
    document.getElementById('kr-bobot').value = data.bobot;
    document.getElementById('kr-tipe').value = data.tipe;
    document.getElementById('btn-submit-kr').textContent = '💾 Perbarui';
    Modal.open('modal-kriteria');
}

document.querySelector('#modal-kriteria .modal-close').addEventListener('click', resetKriteriaModal);
function resetKriteriaModal() {
    document.getElementById('modal-kriteria-title').textContent = '➕ Tambah Kriteria';
    document.getElementById('kr-action').value = 'add';
    document.getElementById('kr-id').value = '0';
    document.getElementById('form-kriteria').reset();
    document.getElementById('btn-submit-kr').textContent = '💾 Simpan';
}
</script>
HTML;

require_once __DIR__ . '/includes/layout_end.php';
?>
