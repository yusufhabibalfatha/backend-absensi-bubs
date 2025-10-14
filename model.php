<?php

class Absensi_Model {

    public static function get_jadwal_siswa_by_kriteria($nama_kelas, $hari, $mata_pelajaran) {
        global $wpdb;

        $jadwal_table = 'bubs_jadwal';
        $kelas_table = 'bubs_kelas';
        $siswa_table = 'bubs_siswa';

        $query = $wpdb->prepare("
            SELECT 
                j.id AS id_jadwal,
                s.id AS id_siswa,
                s.nama_lengkap,
                NULL AS tanggal,
                NULL AS status,
                NULL AS keterangan,
                s.jenis_kelamin
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

    public static function insert_absensi_sekolah($data) {
        global $wpdb;
        $prefix = 'bubs_';

        if (!is_array($data)) {
            return new WP_Error('invalid_data', 'Data harus berupa array.', ['status' => 400]);
        }

        $inserted_ids = [];

        foreach ($data as $item) {
            // Validasi minimal
            if (empty($item['id_siswa']) || empty($item['id_jadwal']) || empty($item['status'])) {
                continue; // Skip data yang tidak valid
            }

            // Validasi & sanitasi tanggal
            $tanggal = sanitize_text_field($item['tanggal'] ?? '');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
                $tanggal = current_time('Y-m-d'); // fallback tanggal sekarang
            }

            $inserted = $wpdb->insert("{$prefix}absensi_sekolah", [
                'id_siswa'     => intval($item['id_siswa']),
                'id_jadwal'    => intval($item['id_jadwal']),
                'tanggal'      => $tanggal,
                'status'       => sanitize_text_field($item['status']),
                'keterangan'   => sanitize_text_field($item['keterangan'] ?? ''),
            ]);

            if ($inserted !== false) {
                $inserted_ids[] = $wpdb->insert_id;
            }
        }

        if (empty($inserted_ids)) {
            return new WP_Error('insert_failed', 'Tidak ada data yang berhasil disimpan.', ['status' => 500]);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => count($inserted_ids) . ' data absensi berhasil disimpan.',
            'inserted_ids' => $inserted_ids
        ], 200);
    }


}