<?php

class Simple_Controller {

    public static function handle_request($request) {
        return rest_ensure_response([
            'message' => 'Halo dari Controller!',
            'time' => current_time('mysql'),
        ]);
    }

    public static function get_siswa_by_nama_kelas($request) {
        $nama_kelas = $request->get_param('kelas');

        if (!$nama_kelas) {
            return new WP_Error(
                'invalid_parameter',
                'Parameter ?kelas= wajib diisi.',
                ['status' => 400]
            );
        }

        $data = Simple_Model::get_siswa_by_nama_kelas(sanitize_text_field($nama_kelas));

        return rest_ensure_response([
            'success' => true,
            'jumlah' => count($data),
            'siswa' => $data,
        ]);
    }
}
