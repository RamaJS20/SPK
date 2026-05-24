<?php
$pageTitle  = 'Laporan PDF';
$activePage = 'laporan';

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/functions/moora.php';

$db = getDB();
$periodeList = getPeriodeList();
$calculatedPeriodes = getCalculatedPeriodeList();

$selectedPeriode = sanitize($_GET['periode'] ?? ($calculatedPeriodes[0] ?? ''));
$selectedDivisi  = sanitize($_GET['divisi'] ?? '');

$hasilList = [];
if ($selectedPeriode) {
    $hasilList = getHasilMOORA($selectedPeriode, $selectedDivisi ?: null);
}
?>

<div class="page-header" style="display:flex;flex-direction:column;gap:4px;">
    <h1 style="display:flex;align-items:center;gap:10px;"><i data-lucide="file-text" style="width:28px;height:28px;color:var(--bri-blue);"></i> Laporan PDF</h1>
    <p>Generate dan unduh laporan hasil penilaian karyawan terbaik.</p>
</div>

<!-- Filter -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-body">
        <form method="GET" style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group" style="margin:0;flex:1;min-width:180px;">
                <label class="form-label">Pilih Periode</label>
                <select name="periode" class="form-select">
                    <option value="">-- Pilih Periode --</option>
                    <?php foreach ($calculatedPeriodes as $p): ?>
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
            <button type="submit" class="btn btn-outline" style="display:inline-flex;align-items:center;gap:6px;">
                <i data-lucide="search" style="width:14px;height:14px;"></i> Tampilkan
            </button>
        </form>
    </div>
</div>

<?php if (empty($calculatedPeriodes)): ?>
    <div class="alert alert-warning" style="display:flex;align-items:center;gap:8px;">
        <i data-lucide="alert-triangle" style="width:18px;height:18px;flex-shrink:0;"></i>
        <span>Belum ada hasil perhitungan MOORA. <a href="<?= BASE_URL ?>/hasil.php" style="color:inherit;font-weight:700;">Hitung MOORA</a> terlebih dahulu.</span>
    </div>
<?php elseif ($selectedPeriode && empty($hasilList)): ?>
    <div class="alert alert-info" style="display:flex;align-items:center;gap:8px;">
        <i data-lucide="info" style="width:18px;height:18px;flex-shrink:0;"></i>
        <span>Tidak ada data untuk periode dan filter yang dipilih.</span>
    </div>
<?php elseif (!empty($hasilList)): ?>

<!-- Preview -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <h5 style="display:flex;align-items:center;gap:8px;"><i data-lucide="eye" style="width:18px;height:18px;color:var(--bri-blue);"></i> Preview Laporan — <?= htmlspecialchars($selectedPeriode) ?></h5>
        <div style="display:flex;gap:8px;">
            <a href="export_pdf.php?periode=<?= urlencode($selectedPeriode) ?>&divisi=<?= urlencode($selectedDivisi) ?>"
               class="btn btn-primary"
               style="display:inline-flex;align-items:center;gap:6px;"
               onclick="Spinner.show('Membuat PDF...')">
                <i data-lucide="download" style="width:14px;height:14px;"></i> Download PDF
            </a>
            <button class="btn btn-secondary" style="display:inline-flex;align-items:center;gap:6px;" onclick="window.print()">
                <i data-lucide="printer" style="width:14px;height:14px;"></i> Print
            </button>
        </div>
    </div>

    <!-- Print-friendly preview -->
    <div class="card-body" id="laporan-preview" style="font-family:'Times New Roman',serif;">
        <!-- Header -->
        <div style="text-align:center;border-bottom:3px double #003087;padding-bottom:16px;margin-bottom:20px;">
            <div style="display:flex;align-items:center;justify-content:center;gap:20px;margin-bottom:12px;">
                <img src="<?= BASE_URL ?>/bri.png" alt="BRI Logo" style="height: 52px; width: auto; object-fit: contain; filter: drop-shadow(2px 0 0 #000) drop-shadow(-2px 0 0 #000) drop-shadow(0 2px 0 #000) drop-shadow(0 -2px 0 #000) drop-shadow(1.5px 1.5px 0 #000) drop-shadow(-1.5px 1.5px 0 #000) drop-shadow(1.5px -1.5px 0 #000) drop-shadow(-1.5px -1.5px 0 #000);">
                <div>
                    <div style="font-size:18px;font-weight:700;color:#003087;">BANK RAKYAT INDONESIA</div>
                    <div style="font-size:14px;color:#555;">Kantor Cabang Pembantu Arundina</div>
                    <div style="font-size:12px;color:#777;">Jl. Arundina No. 1, Kota</div>
                </div>
            </div>
            <div style="font-size:16px;font-weight:700;color:#003087;text-transform:uppercase;letter-spacing:1px;">
                Laporan Penilaian Karyawan Terbaik
            </div>
            <div style="font-size:13px;color:#555;margin-top:4px;">
                Metode MOORA (Multi-Objective Optimization on the basis of Ratio Analysis)
            </div>
        </div>

        <!-- Info -->
        <table style="width:100%;font-size:13px;margin-bottom:20px;border-collapse:collapse;">
            <tr>
                <td style="width:150px;padding:4px 0;color:#555;">Periode</td>
                <td style="padding:4px 0;">: <strong><?= htmlspecialchars($selectedPeriode) ?></strong></td>
                <td style="width:150px;padding:4px 0;color:#555;">Divisi</td>
                <td style="padding:4px 0;">: <strong><?= $selectedDivisi ?: 'Semua Divisi' ?></strong></td>
            </tr>
            <tr>
                <td style="padding:4px 0;color:#555;">Tanggal Cetak</td>
                <td style="padding:4px 0;">: <strong><?= date('d F Y') ?></strong></td>
                <td style="padding:4px 0;color:#555;">Total Karyawan</td>
                <td style="padding:4px 0;">: <strong><?= count($hasilList) ?> orang</strong></td>
            </tr>
        </table>

        <!-- Results Table -->
        <table style="width:100%;border-collapse:collapse;font-size:13px;margin-bottom:24px;">
            <thead>
                <tr style="background:#003087;color:#fff;">
                    <th style="padding:10px 12px;text-align:center;border:1px solid #002060;">No</th>
                    <th style="padding:10px 12px;text-align:left;border:1px solid #002060;">Nama Karyawan</th>
                    <th style="padding:10px 12px;text-align:center;border:1px solid #002060;">NIP</th>
                    <th style="padding:10px 12px;text-align:center;border:1px solid #002060;">Divisi</th>
                    <th style="padding:10px 12px;text-align:left;border:1px solid #002060;">Jabatan</th>
                    <th style="padding:10px 12px;text-align:center;border:1px solid #002060;">Skor MOORA</th>
                    <th style="padding:10px 12px;text-align:center;border:1px solid #002060;">Peringkat</th>
                    <th style="padding:10px 12px;text-align:center;border:1px solid #002060;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hasilList as $i => $row): ?>
                <tr style="background:<?= $row['peringkat'] === 1 ? '#fffbeb' : ($i % 2 === 0 ? '#fff' : '#f9fafb') ?>;">
                    <td style="padding:9px 12px;text-align:center;border:1px solid #e2e8f0;"><?= $i + 1 ?></td>
                    <td style="padding:9px 12px;border:1px solid #e2e8f0;font-weight:<?= $row['peringkat'] === 1 ? '700' : 'normal' ?>;">
                        <?= htmlspecialchars($row['nama']) ?>
                    </td>
                    <td style="padding:9px 12px;text-align:center;border:1px solid #e2e8f0;font-size:11px;">
                        <?= htmlspecialchars($row['nip']) ?>
                    </td>
                    <td style="padding:9px 12px;text-align:center;border:1px solid #e2e8f0;">
                        <?= htmlspecialchars($row['divisi']) ?>
                    </td>
                    <td style="padding:9px 12px;border:1px solid #e2e8f0;"><?= htmlspecialchars($row['jabatan']) ?></td>
                    <td style="padding:9px 12px;text-align:center;border:1px solid #e2e8f0;font-family:monospace;">
                        <?= number_format((float)$row['skor_akhir'], 6) ?>
                    </td>
                    <td style="padding:9px 12px;text-align:center;border:1px solid #e2e8f0;font-weight:700;">
                        <?= $row['peringkat'] ?>
                    </td>
                    <td style="padding:9px 12px;text-align:center;border:1px solid #e2e8f0;">
                        <?php if ($row['peringkat'] === 1): ?>
                            <strong style="color:#7b6000;">Terbaik</strong>
                        <?php elseif ($row['peringkat'] <= 3): ?>
                            Top 3
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Signatures -->
        <div style="margin-top:40px;">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;text-align:center;font-size:13px;">
                <div>
                    <div style="margin-bottom:60px;">Dibuat oleh,</div>
                    <div style="border-top:1px solid #333;padding-top:6px;">
                        <strong>Admin SPK</strong><br>
                        <span style="color:#555;">Staf Administrasi</span>
                    </div>
                </div>
                <div>
                    <div style="margin-bottom:60px;">Diperiksa oleh,</div>
                    <div style="border-top:1px solid #333;padding-top:6px;">
                        <strong>Kepala Bagian SDM</strong><br>
                        <span style="color:#555;">Bank BRI KCP Arundina</span>
                    </div>
                </div>
                <div>
                    <div style="margin-bottom:60px;">Disetujui oleh,</div>
                    <div style="border-top:1px solid #333;padding-top:6px;">
                        <strong>Pimpinan Cabang</strong><br>
                        <span style="color:#555;">Bank BRI KCP Arundina</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div style="margin-top:32px;padding-top:12px;border-top:1px solid #e2e8f0;text-align:center;font-size:11px;color:#999;">
            Dokumen ini digenerate secara otomatis oleh Sistem Pendukung Keputusan Karyawan Terbaik — Bank BRI KCP Arundina
        </div>
    </div>
</div>

<?php endif; ?>

<style>
@media print {
    .sidebar, .topbar, .main-wrapper > header, .card-header .btn,
    .filter-bar, form, .page-header p, .alert { display: none !important; }
    .main-wrapper { margin-left: 0 !important; }
    .page-content { padding: 0 !important; }
    .card { box-shadow: none !important; border: none !important; }
    #laporan-preview { padding: 0 !important; }
}
</style>

<?php require_once __DIR__ . '/includes/layout_end.php'; ?>
