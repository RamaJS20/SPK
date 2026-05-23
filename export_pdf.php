<?php
/**
 * PDF Export using FPDF
 * Generates ranking report for SPK Karyawan Terbaik
 */
require_once __DIR__ . '/functions/auth.php';
requireLogin();
require_once __DIR__ . '/functions/moora.php';

$periode = sanitize($_GET['periode'] ?? '');
$divisi  = sanitize($_GET['divisi'] ?? '');

if (empty($periode)) {
    header('Location: laporan.php');
    exit;
}

$hasilList = getHasilMOORA($periode, $divisi ?: null);

if (empty($hasilList)) {
    header('Location: laporan.php?error=no_data');
    exit;
}

// Check if FPDF is available
$fpdfPath = __DIR__ . '/vendor/fpdf/fpdf.php';
if (!file_exists($fpdfPath)) {
    // Fallback: generate HTML-based PDF via browser print
    header('Location: laporan.php?periode=' . urlencode($periode) . '&divisi=' . urlencode($divisi) . '&print=1');
    exit;
}

// Set FPDF font path
define('FPDF_FONTPATH', __DIR__ . '/vendor/fpdf/font/');
require_once $fpdfPath;

class PDF extends FPDF {
    public string $periode = '';
    public string $divisi  = '';

    function Header() {
        // BRI Blue header bar
        $this->SetFillColor(0, 48, 135);
        $this->Rect(0, 0, 210, 35, 'F');

        // Logo placeholder
        $this->SetFillColor(255, 215, 0);
        $this->RoundedRect(10, 5, 25, 25, 3, 'F');
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(0, 48, 135);
        $this->SetXY(10, 12);
        $this->Cell(25, 10, 'BRI', 0, 0, 'C');

        // Title
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 14);
        $this->SetXY(40, 7);
        $this->Cell(0, 7, 'BANK RAKYAT INDONESIA', 0, 1, 'L');
        $this->SetFont('Arial', '', 10);
        $this->SetX(40);
        $this->Cell(0, 5, 'Kantor Cabang Pembantu Arundina', 0, 1, 'L');
        $this->SetFont('Arial', 'I', 9);
        $this->SetX(40);
        $this->Cell(0, 5, 'Laporan Penilaian Karyawan Terbaik - Metode MOORA', 0, 1, 'L');

        $this->Ln(8);

        // Sub-header info
        $this->SetTextColor(50, 50, 50);
        $this->SetFont('Arial', '', 9);
        $this->Cell(30, 5, 'Periode', 0, 0);
        $this->Cell(5, 5, ':', 0, 0);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(50, 5, $this->periode, 0, 0);
        $this->SetFont('Arial', '', 9);
        $this->Cell(30, 5, 'Divisi', 0, 0);
        $this->Cell(5, 5, ':', 0, 0);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(0, 5, $this->divisi ?: 'Semua Divisi', 0, 1);

        $this->SetFont('Arial', '', 9);
        $this->Cell(30, 5, 'Tanggal Cetak', 0, 0);
        $this->Cell(5, 5, ':', 0, 0);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(0, 5, date('d F Y'), 0, 1);

        $this->Ln(3);
        $this->SetDrawColor(0, 48, 135);
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(4);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 10, 'SPK Karyawan Terbaik - Bank BRI KCP Arundina | Halaman ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function RoundedRect($x, $y, $w, $h, $r, $style = '') {
        $k = $this->k;
        $hp = $this->h;
        if ($style == 'F') $op = 'f';
        elseif ($style == 'FD' || $style == 'DF') $op = 'B';
        else $op = 'S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x+$r)*$k, ($hp-$y)*$k));
        $xc = $x+$w-$r; $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k, ($hp-$y)*$k));
        $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
        $xc = $x+$w-$r; $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l', ($x+$w)*$k, ($hp-$yc)*$k));
        $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x+$r; $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k, ($hp-($y+$h))*$k));
        $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
        $xc = $x+$r; $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', $x*$k, ($hp-$yc)*$k));
        $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3) {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1*$this->k, ($h-$y1)*$this->k,
            $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
    }
}

$pdf = new PDF('P', 'mm', 'A4');
$pdf->periode = $periode;
$pdf->divisi  = $divisi;
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

// Table header
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(0, 48, 135);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetDrawColor(0, 32, 96);

$cols = [
    ['label' => 'No',          'w' => 10, 'align' => 'C'],
    ['label' => 'Nama Karyawan','w' => 45, 'align' => 'L'],
    ['label' => 'NIP',          'w' => 30, 'align' => 'C'],
    ['label' => 'Divisi',       'w' => 22, 'align' => 'C'],
    ['label' => 'Jabatan',      'w' => 35, 'align' => 'L'],
    ['label' => 'Skor MOORA',   'w' => 28, 'align' => 'C'],
    ['label' => 'Peringkat',    'w' => 20, 'align' => 'C'],
];

foreach ($cols as $col) {
    $pdf->Cell($col['w'], 8, $col['label'], 1, 0, $col['align'], true);
}
$pdf->Ln();

// Table rows
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(30, 30, 30);

foreach ($hasilList as $i => $row) {
    $isFirst = $row['peringkat'] === 1;
    $isEven  = $i % 2 === 0;

    if ($isFirst) {
        $pdf->SetFillColor(255, 251, 235);
    } elseif ($isEven) {
        $pdf->SetFillColor(255, 255, 255);
    } else {
        $pdf->SetFillColor(247, 250, 252);
    }

    $fill = true;
    if ($isFirst) $pdf->SetFont('Arial', 'B', 8);
    else $pdf->SetFont('Arial', '', 8);

    $pdf->Cell(10, 7, $i + 1, 1, 0, 'C', $fill);
    $pdf->Cell(45, 7, $row['nama'], 1, 0, 'L', $fill);
    $pdf->Cell(30, 7, $row['nip'], 1, 0, 'C', $fill);
    $pdf->Cell(22, 7, $row['divisi'], 1, 0, 'C', $fill);
    $pdf->Cell(35, 7, $row['jabatan'], 1, 0, 'L', $fill);
    $pdf->Cell(28, 7, number_format((float)$row['skor_akhir'], 6), 1, 0, 'C', $fill);

    $keterangan = $row['peringkat'] === 1 ? 'TERBAIK' : '#' . $row['peringkat'];
    $pdf->Cell(20, 7, $keterangan, 1, 1, 'C', $fill);
}

// Signature section
$pdf->Ln(15);
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(50, 50, 50);

$sigY = $pdf->GetY();
$sigCols = [
    ['title' => 'Dibuat oleh,',    'name' => 'Admin SPK',         'role' => 'Staf Administrasi'],
    ['title' => 'Diperiksa oleh,', 'name' => 'Kepala Bagian SDM', 'role' => 'Bank BRI KCP Arundina'],
    ['title' => 'Disetujui oleh,', 'name' => 'Pimpinan Cabang',   'role' => 'Bank BRI KCP Arundina'],
];

$sigWidth = 60;
$startX = 15;
foreach ($sigCols as $idx => $sig) {
    $x = $startX + ($idx * ($sigWidth + 5));
    $pdf->SetXY($x, $sigY);
    $pdf->Cell($sigWidth, 5, $sig['title'], 0, 1, 'C');
    $pdf->SetXY($x, $pdf->GetY() + 18);
    $pdf->SetDrawColor(80, 80, 80);
    $pdf->Line($x, $pdf->GetY(), $x + $sigWidth, $pdf->GetY());
    $pdf->SetXY($x, $pdf->GetY() + 1);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell($sigWidth, 5, $sig['name'], 0, 1, 'C');
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY($x, $pdf->GetY());
    $pdf->Cell($sigWidth, 4, $sig['role'], 0, 1, 'C');
}

$filename = 'Laporan_SPK_' . $periode . ($divisi ? '_' . $divisi : '') . '.pdf';
$pdf->Output('D', $filename);
exit;
