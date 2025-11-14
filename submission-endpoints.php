<?php
// SUBMISSION ENDPOINTS
add_action('rest_api_init', function () {
    // CREATE SUBMISSION
    // register_rest_route('bubs/v1', '/submission/create', array(
    //     'methods' => 'POST',
    //     'callback' => 'bubs_create_submission',
    //     'permission_callback' => '__return_true',
    // ));

    // GET SUBMISSION BY TUGAS
    // register_rest_route('bubs/v1', '/submission/tugas/(?P<id_tugas>\d+)', array(
    //     'methods' => 'GET',
    //     'callback' => 'bubs_get_submission_by_tugas', // tidak ada
    //     'permission_callback' => '__return_true',
    // ));
});

// GET SUBMISSION BY TUGAS
function bubs_get_submission_by_tugas($request) {
    global $wpdb;
    
    $id_tugas = $request['id_tugas'];
    
    $submissions = $wpdb->get_results($wpdb->prepare("
        SELECT s.*, 
               sw.nama_lengkap as nama_siswa,
               k.nama_kelas as kelas_siswa,
               n.nilai as nilai_akhir,
               n.catatan_guru,
               n.graded_at
        FROM bubs_submission s
        LEFT JOIN bubs_siswa sw ON s.id_siswa = sw.id
        LEFT JOIN bubs_kelas k ON sw.id_kelas = k.id
        LEFT JOIN bubs_nilai n ON s.id = n.id_submission
        WHERE s.id_tugas = %d
        ORDER BY s.submitted_at DESC
    ", $id_tugas));
    
    // return new WP_REST_Response([
    //     'success' => true,
    //     "data" => $submissions
    // ], 200);

    return rest_ensure_response($submissions);
}
// CREATE SUBMISSION
function bubs_create_submission( $request) {
    global $wpdb;
    
    $id_tugas = $request['id_tugas'];
    $id_siswa = $request['id_siswa'];
    $jawaban_text = sanitize_textarea_field($request['jawaban_text']);
    
    // Cek apakah sudah submit
    $existing = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM bubs_submission 
        WHERE id_tugas = %d AND id_siswa = %d
    ", $id_tugas, $id_siswa));

    if ($existing > 0) {
        return rest_ensure_response(array(
            'success' => false,
            'message' => 'Anda sudah mengumpulkan tugas ini'
        ), 400);
    }

    // Ambil file yang dikirim (FormData)
    $files = $request->get_file_params();

    if (empty($files['file_jawaban'])) {
        return new WP_REST_Response(['message' => 'File tidak ditemukan'], 400);
    }

    $file = $files['file_jawaban'];
    
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

    // return new WP_REST_Response([
    //     'success' => true,
    //     "data" => $tipe_file
    // ], 200);
    
    // Cek deadline untuk status late
    $tugas = $wpdb->get_row($wpdb->prepare("
        SELECT deadline_datetime FROM bubs_tugas WHERE id = %d
    ", $id_tugas));
    
    $status = 'submitted';
    if ($tugas && current_time('mysql') > $tugas->deadline_datetime) {
        $status = 'late';
    }
    
    $result = $wpdb->insert(
        'bubs_submission',
        array(
            'id_tugas' => $id_tugas,
            'id_siswa' => $id_siswa,
            'jawaban_text' => $jawaban_text,
            'file_path' => $file_path,
            'status' => $status,
            'tipe_file' => $tipe_file
        ),
        array('%d', '%d', '%s', '%s', '%s', '%s')
    );
    
    if ($result) {
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Tugas berhasil dikumpulkan',
            'id_submission' => $wpdb->insert_id
        ));
    } else {
        return rest_ensure_response(array(
            'success' => false,
            'message' => 'Gagal mengumpulkan tugas'
        ), 400);
    }
}

// BERI NILAI
function bubs_beri_nilai(WP_REST_Request $request) {
    global $wpdb;
    
    $id_submission = $request['id_submission'];
    $id_guru = $request['id_guru'];
    $nilai = intval($request['nilai']);
    $catatan_guru = sanitize_textarea_field($request['catatan_guru']);

    // return new WP_REST_Response([
    //     'success' => true,
    //     "data" => $id_submission
    // ], 200);
    
    // Cek apakah sudah ada nilai
    $existing_nilai = $wpdb->get_var($wpdb->prepare("
        SELECT id FROM bubs_nilai WHERE id_submission = %d
    ", $id_submission));
    
    if ($existing_nilai) {
        // Update nilai existing
        $result = $wpdb->update(
            'bubs_nilai',
            array(
                'nilai' => $nilai,
                'catatan_guru' => $catatan_guru,
                'graded_at' => current_time('mysql')
            ),
            array('id_submission' => $id_submission),
            array('%d', '%s', '%s'),
            array('%d')
        );
    } else {
        // Insert nilai baru
        $result = $wpdb->insert(
            'bubs_nilai',
            array(
                'id_submission' => $id_submission,
                'id_guru' => $id_guru,
                'nilai' => $nilai,
                'catatan_guru' => $catatan_guru
            ),
            array('%d', '%d', '%d', '%s')
        );
    }
    
    // Update status submission
    if ($result) {
        $wpdb->update(
            'bubs_submission',
            array('status' => 'graded'),
            array('id' => $id_submission),
            array('%s'),
            array('%d')
        );
    }
    
    if ($result !== false) {
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Nilai berhasil disimpan'
        ));
    } else {
        return rest_ensure_response(array(
            'success' => false,
            'message' => 'Gagal menyimpan nilai'
        ), 400);
    }
}
?>