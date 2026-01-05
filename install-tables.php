<?php
defined('ABSPATH') || exit;

function bubs_create_all_tables() {
    global $wpdb;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $charset_collate = $wpdb->get_charset_collate();

    $prefix = $wpdb->prefix;

    // =============================
    // List semua tabel (SQL CREATE)
    // =============================

    $tables = [];

    // 1. bubs_absensi_kegiatan
    $tables[] = "CREATE TABLE {$prefix}bubs_absensi_kegiatan (
        id INT NOT NULL AUTO_INCREMENT,
        id_siswa INT NOT NULL,
        id_kegiatan INT NOT NULL,
        id_kelas INT DEFAULT NULL,
        id_kamar INT DEFAULT NULL,
        tanggal DATE NOT NULL,
        status ENUM('Hadir','Izin','Sakit','Alpa') NOT NULL,
        keterangan TEXT,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // 2. bubs_absensi_sekolah
    $tables[] = "CREATE TABLE {$prefix}bubs_absensi_sekolah (
        id INT NOT NULL AUTO_INCREMENT,
        id_siswa INT DEFAULT NULL,
        id_jadwal INT DEFAULT NULL,
        tanggal DATE NOT NULL,
        status ENUM('Hadir','Sakit','Izin','Alpa','Terlambat') NOT NULL,
        keterangan TEXT,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // 3. bubs_guru
    $tables[] = "CREATE TABLE {$prefix}bubs_guru (
        id INT NOT NULL AUTO_INCREMENT,
        nama VARCHAR(100) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // 4. bubs_jadwal
    $tables[] = "CREATE TABLE {$prefix}bubs_jadwal (
        id INT NOT NULL AUTO_INCREMENT,
        id_kelas INT DEFAULT NULL,
        id_guru INT DEFAULT NULL,
        hari ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
        mata_pelajaran VARCHAR(100) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // 5. bubs_jenis_kegiatan
    $tables[] = "CREATE TABLE {$prefix}bubs_jenis_kegiatan (
        id INT NOT NULL AUTO_INCREMENT,
        nama_kegiatan VARCHAR(100) NOT NULL,
        kategori ENUM('SEKOLAH','PONDOK') NOT NULL,
        waktu_pelaksanaan TIME DEFAULT NULL,
        deskripsi TEXT,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // 6. bubs_kamar
    $tables[] = "CREATE TABLE {$prefix}bubs_kamar (
        id INT NOT NULL AUTO_INCREMENT,
        nama_kamar VARCHAR(50) NOT NULL,
        kapasitas INT DEFAULT NULL,
        jenis_kelamin ENUM('L','P') NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // 7. bubs_kelas
    $tables[] = "CREATE TABLE {$prefix}bubs_kelas (
        id INT NOT NULL AUTO_INCREMENT,
        nama_kelas VARCHAR(50) NOT NULL,
        id_guru INT DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // 8. bubs_materi
    $tables[] = "CREATE TABLE {$prefix}bubs_materi (
        id INT NOT NULL AUTO_INCREMENT,
        id_guru INT NOT NULL,
        id_mata_pelajaran INT NOT NULL,
        judul_materi VARCHAR(255) NOT NULL,
        deskripsi TEXT,
        file_path VARCHAR(500) NOT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        tipe_file VARCHAR(50) DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // 9. bubs_nilai
    $tables[] = "CREATE TABLE {$prefix}bubs_nilai (
        id INT NOT NULL AUTO_INCREMENT,
        id_submission INT NOT NULL,
        id_guru INT NOT NULL,
        nilai INT NOT NULL CHECK (nilai >= 0 AND nilai <= 100),
        catatan_guru TEXT,
        graded_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // 10. bubs_siswa
    $tables[] = "CREATE TABLE {$prefix}bubs_siswa (
        id INT NOT NULL AUTO_INCREMENT,
        nik VARCHAR(255) DEFAULT NULL,
        nama_lengkap VARCHAR(100) NOT NULL,
        id_kelas INT DEFAULT NULL,
        jenis_kelamin ENUM('L','P') NOT NULL,
        status_siswa ENUM('BOARDING','REGULER') NOT NULL DEFAULT 'BOARDING',
        id_kamar INT DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // 11. bubs_submission
    $tables[] = "CREATE TABLE {$prefix}bubs_submission (
        id INT NOT NULL AUTO_INCREMENT,
        id_tugas INT NOT NULL,
        id_siswa INT NOT NULL,
        jawaban_text TEXT,
        file_path VARCHAR(500) DEFAULT NULL,
        submitted_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        status ENUM('submitted','late','graded') DEFAULT 'submitted',
        tipe_file VARCHAR(50) DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // 12. bubs_tugas
    $tables[] = "CREATE TABLE {$prefix}bubs_tugas (
        id INT NOT NULL AUTO_INCREMENT,
        id_guru INT NOT NULL,
        id_mata_pelajaran INT NOT NULL,
        judul_tugas VARCHAR(255) NOT NULL,
        deskripsi_text TEXT,
        file_path VARCHAR(500) DEFAULT NULL,
        deadline_datetime DATETIME NOT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        status ENUM('draft','published') DEFAULT 'draft',
        bobot_nilai INT DEFAULT 100,
        tipe_file VARCHAR(50) DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // 13. bubs_users
    $tables[] = "CREATE TABLE {$prefix}bubs_users (
        id INT NOT NULL AUTO_INCREMENT,
        username VARCHAR(150) NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('GURU','SISWA') NOT NULL,
        id_guru INT DEFAULT NULL,
        id_siswa INT DEFAULT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY username (username),
        PRIMARY KEY (id)
    ) $charset_collate;";

    // ==================================
    // Execute semua query dengan dbDelta
    // ==================================
    foreach ($tables as $sql) {
        dbDelta($sql);
    }
}

