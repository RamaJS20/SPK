# SPK Karyawan Terbaik — Bank BRI KCP Arundina

Sistem Pendukung Keputusan Karyawan Terbaik menggunakan metode **MOORA** (Multi-Objective Optimization on the basis of Ratio Analysis).

## Tech Stack
- **Frontend**: HTML, CSS, JavaScript (custom, no framework)
- **Backend**: PHP Native
- **Database**: MySQL
- **PDF Export**: FPDF (included in `vendor/fpdf/`)
- **Charts**: Chart.js (CDN)

## Instalasi

### 1. Persyaratan
- PHP 7.4+ atau 8.x
- MySQL 5.7+ atau MariaDB 10.3+
- Web server: Apache (XAMPP/WAMP) atau Nginx

### 2. Setup Database
```sql
CREATE DATABASE spk_karyawan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```
Kemudian import `database.sql` untuk membuat struktur tabel.

### 3. Konfigurasi Database
Edit file `config/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'spk_karyawan');
define('DB_USER', 'root');
define('DB_PASS', '');  // sesuaikan password MySQL Anda
```

### 4. Inisialisasi Data
Buka browser dan akses:
```
http://localhost/spk-karyawan/setup.php?token=setup_spk_2024
```
Script ini akan:
- Membuat semua tabel
- Menambahkan user default (admin & pimpinan)
- Menambahkan 5 kriteria default
- Menambahkan 8 karyawan sample
- Menambahkan data penilaian sample

**⚠️ Hapus atau rename `setup.php` setelah setup selesai!**

### 5. Akses Aplikasi
```
http://localhost/spk-karyawan/
```

## Akun Default
| Username  | Password     | Role     |
|-----------|-------------|----------|
| admin     | admin123    | Admin    |
| pimpinan  | pimpinan123 | Pimpinan |

## Fitur
- **Dashboard**: Ringkasan statistik + chart top 5 karyawan
- **Manajemen Karyawan**: CRUD karyawan dengan filter divisi
- **Manajemen Kriteria**: CRUD kriteria + validasi total bobot = 1.0
- **Input Penilaian**: Grid input nilai per periode
- **Hasil MOORA**: Perhitungan otomatis + detail langkah-langkah
- **Laporan PDF**: Export PDF dengan tanda tangan

## Algoritma MOORA
1. Bangun matriks keputusan X[i][j]
2. Normalisasi: `r[i][j] = x[i][j] / √(Σx[k][j]²)`
3. Pembobotan: `v[i][j] = w[j] × r[i][j]`
4. Hitung Yi: `Yi = Σ(benefit) - Σ(cost)`
5. Ranking berdasarkan Yi descending

## Struktur File
```
spk-karyawan/
├── index.php          # Dashboard
├── login.php          # Halaman login
├── logout.php         # Logout
├── karyawan.php       # Manajemen karyawan
├── kriteria.php       # Manajemen kriteria
├── penilaian.php      # Input penilaian
├── hasil.php          # Hasil MOORA
├── laporan.php        # Preview laporan
├── export_pdf.php     # Generate PDF
├── setup.php          # Setup awal (hapus setelah digunakan)
├── config/
│   └── db.php         # Koneksi PDO
├── functions/
│   ├── auth.php       # Session & auth helpers
│   └── moora.php      # Algoritma MOORA
├── assets/
│   ├── css/style.css  # Custom CSS
│   └── js/main.js     # JavaScript utilities
├── includes/
│   ├── layout.php     # Header + sidebar
│   └── layout_end.php # Footer + scripts
├── vendor/fpdf/       # FPDF library
└── database.sql       # Schema SQL
```
