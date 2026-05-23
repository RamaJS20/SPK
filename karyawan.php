<?php
$pageTitle  = 'Manajemen Karyawan';
$activePage = 'karyawan';

require_once __DIR__ . '/includes/layout.php';
requireAdmin();

$db = getDB();
$errors = [];
$success = '';

// ---- HANDLE POST ACTIONS ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $id      = (int)($_POST['id'] ?? 0);
    $nama    = sanitize($_POST['nama'] ?? '');
    $nip     = sanitize($_POST['nip'] ?? '');
    $divisi  = sanitize($_POST['divisi'] ?? '');
    $jabatan = sanitize($_POST['jabatan'] ?? '');

    // Validate
    if (empty($nama))    $errors[] = 'Nama wajib diisi.';
    if (empty($nip))     $errors[] = 'NIP wajib diisi.';
    if (!in_array($divisi, ['Bisnis', 'Operasional'])) $errors[] = 'Divisi tidak valid.';
    if (empty($jabatan)) $errors[] = 'Jabatan wajib diisi.';

    if (empty($errors)) {
        if ($action === 'add') {
            // Check NIP unique
            $check = $db->prepare("SELECT id FROM karyawan WHERE nip = ?");
            $check->execute([$nip]);
            if ($check->fetch()) {
                $errors[] = 'NIP sudah terdaftar.';
            } else {
                $stmt = $db->prepare("INSERT INTO karyawan (nama, nip, divisi, jabatan) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nama, $nip, $divisi, $jabatan]);
                header('Location: karyawan.php?success=1&msg=' . urlencode('Karyawan berhasil ditambahkan.'));
                exit;
            }
        } elseif ($action === 'edit' && $id > 0) {
            $check = $db->prepare("SELECT id FROM karyawan WHERE nip = ? AND id != ?");
            $check->execute([$nip, $id]);
            if ($check->fetch()) {
                $errors[] = 'NIP sudah digunakan karyawan lain.';
            } else {
                $stmt = $db->prepare("UPDATE karyawan SET nama=?, nip=?, divisi=?, jabatan=? WHERE id=?");
                $stmt->execute([$nama, $nip, $divisi, $jabatan, $id]);
                header('Location: karyawan.php?success=1&msg=' . urlencode('Data karyawan berhasil diperbarui.'));
                exit;
            }
        }
    }
}

// ---- HANDLE DELETE ----
if (isset($_GET['delete']) && isAdmin()) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM karyawan WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: karyawan.php?deleted=1');
    exit;
}

// ---- FETCH DATA ----
$filterDivisi = sanitize($_GET['divisi'] ?? '');
$sql = "SELECT * FROM karyawan";
$params = [];
if (in_array($filterDivisi, ['Bisnis', 'Operasional'])) {
    $sql .= " WHERE divisi = ?";
    $params[] = $filterDivisi;
}
$sql .= " ORDER BY divisi, nama ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$karyawanList = $stmt->fetchAll();

// Edit data
$editData = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM karyawan WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editData = $stmt->fetch();
}
?>

<div class="page-header">
    <h1>👥 Manajemen Karyawan</h1>
    <p>Kelola data karyawan Bank BRI KCP Arundina.</p>
</div>

<!-- Filter + Add Button -->
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <div class="filter-bar" style="margin-bottom:0;">
        <form method="GET" style="display:flex;gap:10px;align-items:center;">
            <select name="divisi" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Divisi</option>
                <option value="Bisnis" <?= $filterDivisi === 'Bisnis' ? 'selected' : '' ?>>Bisnis</option>
                <option value="Operasional" <?= $filterDivisi === 'Operasional' ? 'selected' : '' ?>>Operasional</option>
            </select>
            <?php if ($filterDivisi): ?>
                <a href="karyawan.php" class="btn btn-secondary btn-sm">✕ Reset</a>
            <?php endif; ?>
        </form>
    </div>
    <button class="btn btn-primary" onclick="Modal.open('modal-karyawan')">
        ➕ Tambah Karyawan
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
        <h5>📋 Daftar Karyawan</h5>
        <span style="font-size:13px;color:#718096;"><?= count($karyawanList) ?> karyawan</span>
    </div>
    <div class="table-wrapper">
        <?php if (empty($karyawanList)): ?>
            <div class="empty-state">
                <i>👥</i>
                <p>Belum ada data karyawan.</p>
            </div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>NIP</th>
                    <th>Nama</th>
                    <th>Divisi</th>
                    <th>Jabatan</th>
                    <th>Terdaftar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($karyawanList as $i => $k): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><code><?= htmlspecialchars($k['nip']) ?></code></td>
                    <td><strong><?= htmlspecialchars($k['nama']) ?></strong></td>
                    <td>
                        <span class="badge badge-<?= strtolower($k['divisi']) ?>">
                            <?= htmlspecialchars($k['divisi']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($k['jabatan']) ?></td>
                    <td style="font-size:12px;color:#718096;"><?= date('d/m/Y', strtotime($k['created_at'])) ?></td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <button class="btn btn-sm btn-outline"
                                onclick="openEditModal(<?= htmlspecialchars(json_encode($k)) ?>)">
                                ✏️ Edit
                            </button>
                            <button class="btn btn-sm btn-danger"
                                onclick="confirmDelete('karyawan.php?delete=<?= $k['id'] ?>', '<?= htmlspecialchars(addslashes($k['nama'])) ?>')">
                                🗑️
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="modal-karyawan">
    <div class="modal-box">
        <div class="modal-header">
            <h5 id="modal-karyawan-title">➕ Tambah Karyawan</h5>
            <button class="modal-close" onclick="Modal.close('modal-karyawan')">×</button>
        </div>
        <form method="POST" id="form-karyawan">
            <div class="modal-body">
                <input type="hidden" name="action" id="form-action" value="add">
                <input type="hidden" name="id" id="form-id" value="0">

                <div class="form-group">
                    <label class="form-label">Nama Lengkap <span style="color:red">*</span></label>
                    <input type="text" name="nama" id="form-nama" class="form-control" placeholder="Nama lengkap karyawan" required>
                </div>
                <div class="form-group">
                    <label class="form-label">NIP <span style="color:red">*</span></label>
                    <input type="text" name="nip" id="form-nip" class="form-control" placeholder="Contoh: BRI-2021-001" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Divisi <span style="color:red">*</span></label>
                        <select name="divisi" id="form-divisi" class="form-select" required>
                            <option value="">Pilih Divisi</option>
                            <option value="Bisnis">Bisnis</option>
                            <option value="Operasional">Operasional</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jabatan <span style="color:red">*</span></label>
                        <input type="text" name="jabatan" id="form-jabatan" class="form-control" placeholder="Jabatan" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="Modal.close('modal-karyawan')">Batal</button>
                <button type="submit" class="btn btn-primary" id="btn-submit-karyawan">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php
$extraScripts = <<<'HTML'
<script>
function openEditModal(data) {
    document.getElementById('modal-karyawan-title').textContent = '✏️ Edit Karyawan';
    document.getElementById('form-action').value = 'edit';
    document.getElementById('form-id').value = data.id;
    document.getElementById('form-nama').value = data.nama;
    document.getElementById('form-nip').value = data.nip;
    document.getElementById('form-divisi').value = data.divisi;
    document.getElementById('form-jabatan').value = data.jabatan;
    document.getElementById('btn-submit-karyawan').textContent = '💾 Perbarui';
    Modal.open('modal-karyawan');
}

// Reset modal on close
document.getElementById('modal-karyawan').addEventListener('click', function(e) {
    if (e.target === this) resetKaryawanModal();
});
document.querySelector('#modal-karyawan .modal-close').addEventListener('click', resetKaryawanModal);

function resetKaryawanModal() {
    document.getElementById('modal-karyawan-title').textContent = '➕ Tambah Karyawan';
    document.getElementById('form-action').value = 'add';
    document.getElementById('form-id').value = '0';
    document.getElementById('form-karyawan').reset();
    document.getElementById('btn-submit-karyawan').textContent = '💾 Simpan';
}
</script>
HTML;

require_once __DIR__ . '/includes/layout_end.php';
?>
