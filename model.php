<?php

class Absensi_Model {

    public static function get_jadwal_siswa_by_kriteria($nama_kelas, $hari, $mata_pelajaran) {
        global $wpdb;

        $jadwal_table = 'bubs_jadwal';
        $kelas_table = 'bubs_kelas';
        $siswa_table = 'bubs_siswa';

        $query = $wpdb->prepare("
            SELECT 
                j.id AS jadwal_id,
                s.id AS siswa_id,
                s.nama_lengkap,
                s.jenis_kelamin,
                k.nama_kelas,
                j.hari,
                j.mata_pelajaran
            FROM 
                {$jadwal_table} j
            INNER JOIN 
                {$kelas_table} k ON j.id_kelas = k.id
            INNER JOIN 
                {$siswa_table} s ON k.id = s.id_kelas
            WHERE 
                k.nama_kelas = %s AND
                j.hari = %s AND
                j.mata_pelajaran = %s
            ORDER BY 
                s.nama_lengkap ASC
        ", $nama_kelas, $hari, $mata_pelajaran);

        $results = $wpdb->get_results($query, ARRAY_A);

        return $results;
    }

    public static function get_hari_available() {
        return ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    }

    public static function get_mata_pelajaran_by_kelas_hari($nama_kelas, $hari) {
        global $wpdb;

        $jadwal_table = 'bubs_jadwal';
        $kelas_table = 'bubs_kelas';

        $query = $wpdb->prepare("
            SELECT DISTINCT 
                j.mata_pelajaran
            FROM 
                {$jadwal_table} j
            INNER JOIN 
                {$kelas_table} k ON j.id_kelas = k.id
            WHERE 
                k.nama_kelas = %s AND
                j.hari = %s
            ORDER BY 
                j.mata_pelajaran ASC
        ", $nama_kelas, $hari);

        $results = $wpdb->get_results($query, ARRAY_A);

        return $results;
    }
}