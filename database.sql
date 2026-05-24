-- SPK Karyawan Terbaik - Bank BRI KCP Arundina
-- Database Schema & Seed Data

CREATE DATABASE IF NOT EXISTS spk_karyawan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE spk_karyawan;

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'pimpinan') NOT NULL DEFAULT 'pimpinan',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Karyawan table
CREATE TABLE IF NOT EXISTS `karyawan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama` VARCHAR(100) NOT NULL,
    `nip` VARCHAR(20) NOT NULL UNIQUE,
    `divisi` ENUM('Bisnis', 'Operasional') NOT NULL,
    `jabatan` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Kriteria table
CREATE TABLE IF NOT EXISTS `kriteria` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_kriteria` VARCHAR(100) NOT NULL,
    `bobot` DECIMAL(5,4) NOT NULL,
    `tipe` ENUM('benefit', 'cost') NOT NULL DEFAULT 'benefit',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Penilaian table
CREATE TABLE IF NOT EXISTS `penilaian` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `karyawan_id` INT NOT NULL,
    `kriteria_id` INT NOT NULL,
    `nilai` DECIMAL(5,2) NOT NULL,
    `periode` VARCHAR(7) NOT NULL COMMENT 'Format: YYYY-MM',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`kriteria_id`) REFERENCES `kriteria`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_penilaian` (`karyawan_id`, `kriteria_id`, `periode`)
) ENGINE=InnoDB;

-- Hasil MOORA table
CREATE TABLE IF NOT EXISTS `hasil_moora` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `karyawan_id` INT NOT NULL,
    `skor_akhir` DECIMAL(10,6) NOT NULL,
    `peringkat` INT NOT NULL,
    `periode` VARCHAR(7) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_hasil` (`karyawan_id`, `periode`)
) ENGINE=InnoDB;

-- =====================
-- SEED DATA
-- =====================

-- Default users
-- IMPORTANT: Do NOT use these placeholder hashes in production.
-- Run setup.php?token=setup_spk_2024 to properly seed users with bcrypt hashes.
-- admin password = 'admin123'
-- pimpinan password = 'pimpinan123'
-- These hashes are generated with password_hash() and are valid:
INSERT INTO `users` (`username`, `password`, `role`) VALUES
('admin',    '$2y$10$gUCHGTcTDNejAnzHzvMlz.BDmodEbV/TZqOAEVg.yG9E9GSlZ/aUu', 'admin'),
('pimpinan', '$2y$10$hVLCyu76xA8dhxpD0NwVCOVp57F1vAXZmKUGmYKlUpjtSPdbjGG1O', 'pimpinan')
ON DUPLICATE KEY UPDATE password=VALUES(password);

-- Sample Kriteria (bobot total = 1.0)
INSERT INTO `kriteria` (`nama_kriteria`, `bobot`, `tipe`) VALUES
('KPI Achievement', 0.3000, 'benefit'),
('Absensi', 0.2000, 'cost'),
('Kedisiplinan', 0.2000, 'benefit'),
('Kerjasama Tim', 0.1500, 'benefit'),
('Kualitas Kerja', 0.1500, 'benefit');

-- Sample Karyawan (4 Bisnis, 4 Operasional)
INSERT INTO `karyawan` (`nama`, `nip`, `divisi`, `jabatan`) VALUES
('Budi Santoso', 'BRI-2021-001', 'Bisnis', 'Account Officer'),
('Siti Rahayu', 'BRI-2021-002', 'Bisnis', 'Relationship Manager'),
('Ahmad Fauzi', 'BRI-2021-003', 'Bisnis', 'Sales Officer'),
('Dewi Lestari', 'BRI-2021-004', 'Bisnis', 'Account Officer'),
('Rudi Hartono', 'BRI-2021-005', 'Operasional', 'Teller'),
('Rina Wulandari', 'BRI-2021-006', 'Operasional', 'Customer Service'),
('Hendra Gunawan', 'BRI-2021-007', 'Operasional', 'Back Office'),
('Maya Sari', 'BRI-2021-008', 'Operasional', 'Teller');
