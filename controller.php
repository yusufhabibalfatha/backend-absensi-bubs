<?php

class Absensi_Controller {

    public static function handle_request($request) {
        return rest_ensure_response([
            'message' => 'API Absensi Bubs - Endpoint tersedia',
            'endpoints' => [
                '/jadwal-siswa' => 'GET - Mendapatkan jadwal dan data siswa berdasarkan kriteria',
                '/mata-pelajaran' => 'GET - Mendapatkan mata pelajaran berdasarkan kelas dan hari'
            ],
            'time' => current_time('mysql'),
        ]);
    }

    public static function get_jadwal_siswa_by_kriteria($request) {
        $nama_kelas = $request->get_param('kelas');
        $hari = $request->get_param('hari');
        $mata_pelajaran = $request->get_param('mapel');

        // Validasi parameter wajib
        $missing_params = [];
        if (!$nama_kelas) $missing_params[] = 'kelas';
        if (!$hari) $missing_params[] = 'hari';
        if (!$mata_pelajaran) $missing_params[] = 'mapel';

        if (!empty($missing_params)) {
            return new WP_Error(
                'missing_parameters',
                'Parameter berikut wajib diisi: ' . implode(', ', $missing_params),
                ['status' => 400]
            );
        }

        // Validasi hari
        $hari_valid = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        if (!in_array($hari, $hari_valid)) {
            return new WP_Error(
                'invalid_hari',
                'Hari harus salah satu dari: ' . implode(', ', $hari_valid),
                ['status' => 400]
            );
        }

        // Sanitize input
        $nama_kelas_clean = sanitize_text_field($nama_kelas);
        $hari_clean = sanitize_text_field($hari);
        $mata_pelajaran_clean = sanitize_text_field($mata_pelajaran);

        $data = Absensi_Model::get_jadwal_siswa_by_kriteria(
            $nama_kelas_clean, 
            $hari_clean, 
            $mata_pelajaran_clean
        );

        $guru = Absensi_Model::get_guru_jadwal_siswa_by_kriteria(
            $nama_kelas_clean, 
            $hari_clean, 
            $mata_pelajaran_clean
        );

        return rest_ensure_response([
            'success' => true,
            'jumlah_siswa' => count($data),
            'kriteria' => [
                'kelas' => $nama_kelas_clean,
                'hari' => $hari_clean,
                'mata_pelajaran' => $mata_pelajaran_clean,
                'guru' => $guru[0]['nama']
            ],
            'data' => $data,
        ]);
    }

    public static function get_mata_pelajaran_by_kelas_hari($request) {
        $nama_kelas = $request->get_param('kelas');
        $hari = $request->get_param('hari');

        // Validasi parameter wajib
        if (!$nama_kelas || !$hari) {
            return new WP_Error(
                'missing_parameters',
                'Parameter kelas dan hari wajib diisi',
                ['status' => 400]
            );
        }

        // Validasi hari
        $hari_valid = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        if (!in_array($hari, $hari_valid)) {
            return new WP_Error(
                'invalid_hari',
                'Hari harus salah satu dari: ' . implode(', ', $hari_valid),
                ['status' => 400]
            );
        }

        // Sanitize input
        $nama_kelas_clean = sanitize_text_field($nama_kelas);
        $hari_clean = sanitize_text_field($hari);

        $data = Absensi_Model::get_mata_pelajaran_by_kelas_hari($nama_kelas_clean, $hari_clean);

        $mata_pelajaran_list = array_map(function($item) {
            return $item['mata_pelajaran'];
        }, $data);

        return rest_ensure_response([
            'success' => true,
            'kriteria' => [
                'kelas' => $nama_kelas_clean,
                'hari' => $hari_clean
            ],
            'mata_pelajaran' => $mata_pelajaran_list,
        ]);
    }

    public static function insert_absensi_sekolah($request)
    {
        try {
            $data = $request->get_json_params();

            if (empty($data)) {
                throw new Exception('Data tidak boleh kosong.');
            }

            $result_insert = Absensi_Model::insert_absensi_sekolah($data);

            return new WP_REST_Response([
                'success' => true,
                'message' => 'Data generus berhasil ditambahkan.',
                'result' => $result_insert,
            ], 201);
        } catch (Exception $e) {
            error_log('API Insert absen: ' . $e->getMessage());

            return new WP_REST_Response([
                'success' => false,
                'error' => true,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}