<?php
// TUGAS ENDPOINTS
add_action('rest_api_init', function () {
    // CREATE TUGAS
    // register_rest_route('bubs/v1', '/tugas/create', array(
    //     'methods' => 'POST',
    //     'callback' => 'bubs_create_tugas',
    //     'permission_callback' => '__return_true',
    // ));

    // GET TUGAS BY GURU
    // register_rest_route('bubs/v1', '/tugas/guru/(?P<id_guru>\d+)', array(
    //     'methods' => 'GET',
    //     'callback' => 'bubs_get_tugas_by_guru',
    //     'permission_callback' => '__return_true',
    // ));

    // GET TUGAS FOR SISWA
    register_rest_route('bubs/v1', '/tugas/siswa/(?P<id_siswa>\d+)', array(
        'methods' => 'GET',
        'callback' => 'bubs_get_tugas_for_siswa',
        'permission_callback' => '__return_true',
    ));
});

// CREATE TUGAS
function bubs_create_tugas(WP_REST_Request $request) {
    global $wpdb;
    
    $id_guru = $request['id_guru'];
    $id_mata_pelajaran = $request['id_mata_pelajaran'];
    $judul_tugas = sanitize_text_field($request['judul_tugas']);
    $deskripsi_text = sanitize_textarea_field($request['deskripsi_text']);
    $deadline_datetime = sanitize_text_field($request['deadline_datetime']);
    $bobot_nilai = intval($request['bobot_nilai']);

    // Ambil file yang dikirim (FormData)
    $files = $request->get_file_params();

    if (empty($files['file_tugas'])) {
        return new WP_REST_Response(['message' => 'File tidak ditemukan'], 400);
    }

    $file = $files['file_tugas'];
    
    // Handle file upload jika ada
    // $file_path = '';
    // if (!empty($_FILES['file_tugas'])) {
    //     $upload = wp_handle_upload($_FILES['file_tugas'], array('test_form' => false));
    //     if (isset($upload['file'])) {
    //         $file_path = $upload['file'];
    //     }
    // }

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
        'bubs_tugas',
        array(
            'id_guru' => $id_guru,
            'id_mata_pelajaran' => $id_mata_pelajaran,
            'judul_tugas' => $judul_tugas,
            'deskripsi_text' => $deskripsi_text,
            'file_path' => $file_path,
            'deadline_datetime' => $deadline_datetime,
            'bobot_nilai' => $bobot_nilai,
            'status' => 'published',
            'tipe_file' => $tipe_file
        ),
        array('%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
    );
    
    if ($result) {
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Tugas berhasil dibuat',
            'id_tugas' => $wpdb->insert_id
        ));
    } else {
        return rest_ensure_response(array(
            'success' => false,
            'message' => 'Gagal membuat tugas'
        ), 400);
    }
}

// GET TUGAS BY GURU
function bubs_get_tugas_by_guru(WP_REST_Request $request) {
    global $wpdb;
    
    $id_guru = $request['id_guru'];

    $query = $wpdb->prepare("
        SELECT t.*, j.mata_pelajaran 
        FROM bubs_tugas t 
        LEFT JOIN bubs_jadwal j ON t.id_mata_pelajaran = j.id 
        WHERE t.id_guru = %d 
        ORDER BY t.created_at DESC
    ", $id_guru);

    $tugas = $wpdb->get_results($query);

    // return new WP_REST_Response([
    //     'success' => true,
    //     "data" => $tugas
    // ], 200);
    
    return rest_ensure_response($tugas);
}

// GET TUGAS FOR SISWA
function bubs_get_tugas_for_siswa(WP_REST_REQUEST $request) {
    global $wpdb;
    
    $id_siswa = $request['id_siswa'];

    // Get kelas siswa dulu
    $siswa = $wpdb->get_row($wpdb->prepare("
        SELECT id_kelas FROM bubs_siswa WHERE id = %d
    ", $id_siswa));
    
    if (!$siswa) {
        return rest_ensure_response(array());
    }

    // return new WP_REST_Response([
    //     'success' => true,
    //     'data' => $siswa
    // ], 200);
    
    // Get tugas berdasarkan jadwal di kelas siswa
    $tugas = $wpdb->get_results($wpdb->prepare("
        SELECT t.*, j.mata_pelajaran, g.nama as nama_guru,
               s.status as status_submission,
               n.nilai as nilai_akhir
        FROM bubs_tugas t
        LEFT JOIN bubs_jadwal j ON t.id_mata_pelajaran = j.id
        LEFT JOIN bubs_guru g ON t.id_guru = g.id
        LEFT JOIN bubs_submission s ON t.id = s.id_tugas AND s.id_siswa = %d
        LEFT JOIN bubs_nilai n ON s.id = n.id_submission
        WHERE j.id_kelas = %d 
        AND t.status = 'published'
        ORDER BY t.deadline_datetime ASC
    ", $id_siswa, $siswa->id_kelas));
    
    return rest_ensure_response($tugas);
}
?>