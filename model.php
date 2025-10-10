<?php

class Simple_Model {

    public static function get_siswa_by_nama_kelas($nama_kelas) {
        global $wpdb;

        // Pastikan semua table prefix pakai wp_ atau bubs_ sesuai konfigurasi
        // $siswa_table = $wpdb->prefix . 'siswa';
        $siswa_table = 'bubs_siswa';
        // $kelas_table = $wpdb->prefix . 'kelas';
        $kelas_table = 'bubs_kelas';

        $query = $wpdb->prepare("
            SELECT 
                s.id,
                s.nama_lengkap,
                k.nama_kelas
            FROM 
                {$siswa_table} s
            INNER JOIN 
                {$kelas_table} k ON s.id_kelas = k.id
            WHERE 
                k.nama_kelas = %s
        ", $nama_kelas);

        $results = $wpdb->get_results($query, ARRAY_A);

        return $results;
    }
}
