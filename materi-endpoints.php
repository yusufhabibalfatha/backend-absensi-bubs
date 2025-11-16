<?php
// MATERI ENDPOINTS
add_action('rest_api_init', function () {
    // GET MATERI BY GURU
    register_rest_route('bubs/v1', '/materi/guru/(?P<id_guru>\d+)', array(
        'methods' => 'GET',
        'callback' => 'bubs_get_materi_by_guru', // tidak ada
        'permission_callback' => '__return_true',
    ));

    // GET MATERI FOR SISWA
    register_rest_route('bubs/v1', '/materi/siswa/(?P<id_kelas>\d+)', array(
        'methods' => 'GET',
        'callback' => 'bubs_get_materi_for_siswa',
        'permission_callback' => '__return_true',
    ));
});

// GET MATERI BY GURU
function bubs_get_materi_by_guru($request) {
    global $wpdb;
    
    $id_guru = $request['id_guru'];
    
    $materi = $wpdb->get_results($wpdb->prepare("
        SELECT m.*, j.mata_pelajaran
        FROM bubs_materi m
        LEFT JOIN bubs_jadwal j ON m.id_mata_pelajaran = j.id
        WHERE m.id_guru = %d
        ORDER BY m.created_at DESC
    ", $id_guru));
    
    return rest_ensure_response($materi);
}
    function bubs_upload_materi(WP_REST_Request $request) {
        global $wpdb;
        
        $id_guru = $request['id_guru'];
        $nama_mapel_dan_nama_kelas = $request['id_mata_pelajaran'];
        $judul_materi = sanitize_text_field($request['judul_materi']);
        $deskripsi = sanitize_textarea_field($request['deskripsi']);

        $parts = explode(' - ', $nama_mapel_dan_nama_kelas);
        $id_mapels = $wpdb->get_results($wpdb->prepare("select j.id
            from bubs_jadwal as j
            join bubs_kelas as k on j.id_kelas = k.id
        where j.mata_pelajaran = %s AND k.nama_kelas = %s", $parts[0], $parts[1]));
        
        // Ambil file yang dikirim (FormData)
        $files = $request->get_file_params();

        if (empty($files['file_materi'])) {
            return new WP_REST_Response(['message' => 'File tidak ditemukan'], 400);
        }

        $file = $files['file_materi'];

        // Gunakan fungsi media upload WordPress
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Upload ke direktori wp-content/uploads
        $upload = wp_handle_upload($file, ['test_form' => false]);
        
        // $upload = wp_handle_upload($_FILES['file_materi'], array('test_form' => false));
        if (isset($upload['error'])) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => 'Upload gagal: ' . $upload['error']
            ), 400);
        }
        
        $file_path = $upload['url'];
        $tipe_file = pathinfo($file_path, PATHINFO_EXTENSION);
        
        $result = $wpdb->insert(
            'bubs_materi',
            array(
                'id_guru' => $id_guru,
                'id_mata_pelajaran' => $id_mapels[0]->id,
                'judul_materi' => $judul_materi,
                'deskripsi' => $deskripsi,
                'file_path' => $file_path,
                'tipe_file' => $tipe_file
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Materi berhasil diupload',
                'id_materi' => $wpdb->insert_id
            ));
        } else {
            return rest_ensure_response(array(
                'success' => false,
                'message' => 'Gagal upload materi'
            ), 400);
        }
    }

// GET MATERI FOR SISWA
function bubs_get_materi_for_siswa($request) {
    global $wpdb;
    
    $id_kelas = $request['id_kelas'];

    // return new WP_REST_Response([
    //     'success' => true,
    //     'data' => $id_kelas
    // ], 200);
    
    $materi = $wpdb->get_results($wpdb->prepare("
        SELECT m.*, j.mata_pelajaran, g.nama as nama_guru
        FROM bubs_materi m
        LEFT JOIN bubs_jadwal j ON m.id_mata_pelajaran = j.id
        LEFT JOIN bubs_guru g ON m.id_guru = g.id
        WHERE j.id_kelas = %d
        ORDER BY m.created_at DESC
    ", $id_kelas));
    
    return rest_ensure_response($materi);
}
?>