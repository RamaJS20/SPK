<?php
require_once __DIR__ . '/../config/db.php';

/**
 * Hitung MOORA untuk periode tertentu
 * 
 * @param string $periode Format YYYY-MM
 * @param string|null $divisi Filter by divisi (optional)
 * @return array Sorted results with ranking
 */
function hitungMOORA(string $periode, ?string $divisi = null): array {
    $db = getDB();

    // 1. Fetch all kriteria
    $kriteria = $db->query("SELECT * FROM kriteria ORDER BY id ASC")->fetchAll();
    if (empty($kriteria)) {
        return ['error' => 'Tidak ada kriteria yang tersedia.'];
    }

    // 2. Fetch karyawan with penilaian for this periode
    $sql = "SELECT DISTINCT k.id, k.nama, k.divisi, k.jabatan
            FROM karyawan k
            INNER JOIN penilaian p ON p.karyawan_id = k.id
            WHERE p.periode = ?";
    $params = [$periode];

    if ($divisi) {
        $sql .= " AND k.divisi = ?";
        $params[] = $divisi;
    }
    $sql .= " ORDER BY k.id ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $karyawanList = $stmt->fetchAll();

    if (empty($karyawanList)) {
        return ['error' => 'Tidak ada data penilaian untuk periode ini.'];
    }

    // 3. Build decision matrix X[karyawan_id][kriteria_id] = nilai
    $matrix = [];
    foreach ($karyawanList as $k) {
        $matrix[$k['id']] = [];
        foreach ($kriteria as $kr) {
            $matrix[$k['id']][$kr['id']] = 0;
        }
    }

    $sqlNilai = "SELECT karyawan_id, kriteria_id, nilai FROM penilaian WHERE periode = ?";
    $stmtNilai = $db->prepare($sqlNilai);
    $stmtNilai->execute([$periode]);
    $nilaiRows = $stmtNilai->fetchAll();

    foreach ($nilaiRows as $row) {
        if (isset($matrix[$row['karyawan_id']][$row['kriteria_id']])) {
            $matrix[$row['karyawan_id']][$row['kriteria_id']] = (float)$row['nilai'];
        }
    }

    // 4. Normalisasi: r[i][j] = x[i][j] / sqrt(sum(x[k][j]^2))
    $normalized = [];
    foreach ($karyawanList as $k) {
        $normalized[$k['id']] = [];
    }

    foreach ($kriteria as $kr) {
        $sumSquares = 0;
        foreach ($karyawanList as $k) {
            $sumSquares += pow($matrix[$k['id']][$kr['id']], 2);
        }
        $sqrtSum = ($sumSquares > 0) ? sqrt($sumSquares) : 1;

        foreach ($karyawanList as $k) {
            $normalized[$k['id']][$kr['id']] = $matrix[$k['id']][$kr['id']] / $sqrtSum;
        }
    }

    // 5. Weighted normalization: v[i][j] = w[j] * r[i][j]
    $weighted = [];
    foreach ($karyawanList as $k) {
        $weighted[$k['id']] = [];
        foreach ($kriteria as $kr) {
            $weighted[$k['id']][$kr['id']] = (float)$kr['bobot'] * $normalized[$k['id']][$kr['id']];
        }
    }

    // 6. Calculate Yi = sum(benefit) - sum(cost)
    $scores = [];
    foreach ($karyawanList as $k) {
        $yi = 0;
        foreach ($kriteria as $kr) {
            if ($kr['tipe'] === 'benefit') {
                $yi += $weighted[$k['id']][$kr['id']];
            } else {
                $yi -= $weighted[$k['id']][$kr['id']];
            }
        }
        $scores[$k['id']] = [
            'karyawan_id' => $k['id'],
            'nama'        => $k['nama'],
            'divisi'      => $k['divisi'],
            'jabatan'     => $k['jabatan'],
            'skor_akhir'  => $yi,
            'matrix'      => $matrix[$k['id']],
            'normalized'  => $normalized[$k['id']],
            'weighted'    => $weighted[$k['id']],
        ];
    }

    // 7. Sort by Yi descending
    usort($scores, fn($a, $b) => $b['skor_akhir'] <=> $a['skor_akhir']);

    // 8. Assign ranking
    foreach ($scores as $idx => &$score) {
        $score['peringkat'] = $idx + 1;
    }
    unset($score);

    // 9. Save/update hasil_moora table
    saveHasilMOORA($scores, $periode);

    return [
        'results'  => $scores,
        'kriteria' => $kriteria,
        'periode'  => $periode,
    ];
}

/**
 * Save MOORA results to database
 */
function saveHasilMOORA(array $scores, string $periode): void {
    $db = getDB();

    // Delete existing results for this periode
    $stmt = $db->prepare("DELETE FROM hasil_moora WHERE periode = ?");
    $stmt->execute([$periode]);

    // Insert new results
    $stmt = $db->prepare(
        "INSERT INTO hasil_moora (karyawan_id, skor_akhir, peringkat, periode) VALUES (?, ?, ?, ?)"
    );
    foreach ($scores as $score) {
        $stmt->execute([
            $score['karyawan_id'],
            $score['skor_akhir'],
            $score['peringkat'],
            $periode,
        ]);
    }
}

/**
 * Get saved MOORA results from DB
 */
function getHasilMOORA(string $periode, ?string $divisi = null): array {
    $db = getDB();
    $sql = "SELECT h.*, k.nama, k.divisi, k.jabatan, k.nip
            FROM hasil_moora h
            INNER JOIN karyawan k ON k.id = h.karyawan_id
            WHERE h.periode = ?";
    $params = [$periode];

    if ($divisi) {
        $sql .= " AND k.divisi = ?";
        $params[] = $divisi;
    }
    $sql .= " ORDER BY h.peringkat ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get list of available periods from penilaian
 */
function getPeriodeList(): array {
    $db = getDB();
    $rows = $db->query("SELECT DISTINCT periode FROM penilaian ORDER BY periode DESC")->fetchAll();
    return array_column($rows, 'periode');
}

/**
 * Get list of periods that have been calculated
 */
function getCalculatedPeriodeList(): array {
    $db = getDB();
    $rows = $db->query("SELECT DISTINCT periode FROM hasil_moora ORDER BY periode DESC")->fetchAll();
    return array_column($rows, 'periode');
}
