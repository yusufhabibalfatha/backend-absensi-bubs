<?php
/*
Plugin Name: Absensi Bubs V1
Plugin URI: https://example.com/absensi-bubs
Description: Plugin untuk sistem absensi sekolah Bubs.
Version: 1.1
Author: Nama Anda
License: GPL2
*/

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'model.php';
require_once plugin_dir_path(__FILE__) . 'controller.php';

// TAMBAHKAN INI - FITUR TUGAS & MATERI
require_once plugin_dir_path(__FILE__) . 'tugas-endpoints.php';
require_once plugin_dir_path(__FILE__) . 'materi-endpoints.php';
require_once plugin_dir_path(__FILE__) . 'submission-endpoints.php';

// API
// GET /wp-json/absensi-bubs/v1/
// GET /wp-json/absensi-bubs/v1/jadwal-siswa?kelas=Kelas 7&hari=Sabtu&mapel=Informatika
// GET /wp-json/absensi-bubs/v1/mata-pelajaran?kelas=10 IPA 1&hari=Senin
// GET /wp-json/absensi-bubs/v1/jenis-kegiatan
// GET /wp-json/absensi-bubs/v1/kelas-boarding
// GET /wp-json/absensi-bubs/v1/kamar
// GET /wp-json/absensi-bubs/v1/siswa-kegiatan?kegiatan=1&kelas=5
// GET /wp-json/absensi-bubs/v1/siswa-kegiatan?kegiatan=3&kamar=2
// POST /wp-json/absensi-bubs/v1/insert/absensi-kegiatan

add_action('rest_api_init', function () {
    // Endpoint utama
    register_rest_route('absensi-bubs/v1', '/', [
        'methods' => 'GET',
        'callback' => ['Absensi_Controller', 'handle_request'],
        'permission_callback' => '__return_true',
    ]);

    // Endpoint untuk mendapatkan jadwal dan data siswa
    register_rest_route('absensi-bubs/v1', '/jadwal-siswa', [
        'methods' => 'GET',
        'callback' => ['Absensi_Controller', 'get_jadwal_siswa_by_kriteria'],
        'permission_callback' => '__return_true',
        'args' => [
            'kelas' => [
                'required' => true,
                'validate_callback' => function($param) {
                    return !empty($param);
                }
            ],
            'hari' => [
                'required' => true,
                'validate_callback' => function($param) {
                    $valid_hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                    return in_array($param, $valid_hari);
                }
            ],
            'mapel' => [
                'required' => true,
                'validate_callback' => function($param) {
                    return !empty($param);
                }
            ]
        ]
    ]);

    // Endpoint untuk mendapatkan mata pelajaran berdasarkan kelas dan hari
    register_rest_route('absensi-bubs/v1', '/mata-pelajaran', [
        'methods' => 'GET',
        'callback' => ['Absensi_Controller', 'get_mata_pelajaran_by_kelas_hari'],
        'permission_callback' => '__return_true',
        'args' => [
            'kelas' => [
                'required' => true,
                'validate_callback' => function($param) {
                    return !empty($param);
                }
            ],
            'hari' => [
                'required' => true,
                'validate_callback' => function($param) {
                    $valid_hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                    return in_array($param, $valid_hari);
                }
            ]
        ]
    ]);

    // =============================================
    // ENDPOINT BARU UNTUK ABSENSI KEGIATAN
    // =============================================

    // Get semua jenis kegiatan
    register_rest_route('absensi-bubs/v1', '/jenis-kegiatan', [
        'methods' => 'GET',
        'callback' => ['Absensi_Controller', 'get_jenis_kegiatan'],
        'permission_callback' => '__return_true',
    ]);

    // Get kelas boarding
    register_rest_route('absensi-bubs/v1', '/kelas-boarding', [
        'methods' => 'GET',
        'callback' => ['Absensi_Controller', 'get_kelas_boarding'],
        'permission_callback' => '__return_true',
    ]);

    // Get semua kamar
    register_rest_route('absensi-bubs/v1', '/kamar', [
        'methods' => 'GET',
        'callback' => ['Absensi_Controller', 'get_kamar'],
        'permission_callback' => '__return_true',
    ]);

    // Get siswa untuk kegiatan
    register_rest_route('absensi-bubs/v1', '/siswa-kegiatan', [
        'methods' => 'GET',
        'callback' => ['Absensi_Controller', 'get_siswa_kegiatan'],
        'permission_callback' => '__return_true',
        'args' => [
            'kegiatan' => [
                'required' => false,
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                }
            ],
            'kelas' => [
                'required' => false,
                'validate_callback' => function($param) {
                    return empty($param) || (is_numeric($param) && $param > 0);
                }
            ],
            'kamar' => [
                'required' => false,
                'validate_callback' => function($param) {
                    return empty($param) || (is_numeric($param) && $param > 0);
                }
            ]
        ]
    ]);

    // Insert absensi sekolah
    register_rest_route('absensi-bubs/insert', 'absensi-sekolah', [
        'methods' => 'POST',
        'callback' => ['Absensi_Controller', 'insert_absensi_sekolah'],
        'permission_callback' => '__return_true',
        'args' => []
    ]);

    // Insert absensi kegiatan
    register_rest_route('absensi-bubs/insert', 'absensi-kegiatan', [
        'methods' => 'POST',
        'callback' => ['Absensi_Controller', 'insert_absensi_kegiatan'],
        'permission_callback' => '__return_true',
        'args' => []
    ]);

    // Tambahkan di rest_api_init
    register_rest_route('absensi-bubs/v1', '/login', [
        'methods' => 'POST',
        'callback' => ['Absensi_Controller', 'login_user'],
        'permission_callback' => '__return_true',
    ]);

    // Presensi siswa
    register_rest_route('absensi-bubs/v1', '/presensi-siswa', [
        'methods' => 'GET',
        'callback' => ['Absensi_Controller', 'get_presensi_siswa'],
        'permission_callback' => '__return_true',
    ]);

    // Rekap presensi kelas untuk guru
    register_rest_route('absensi-bubs/v1', '/rekap-presensi-kelas', [
        'methods' => 'GET',
        'callback' => ['Absensi_Controller', 'get_rekap_presensi_kelas'],
        'permission_callback' => '__return_true',
    ]);

    // Tambahkan di rest_api_init

    // Rekap presensi detail untuk guru
    register_rest_route('absensi-bubs/v1', '/rekap-presensi-kelas-detailed', [
        'methods' => 'GET',
        'callback' => ['Absensi_Controller', 'get_rekap_presensi_kelas_detailed'],
        'permission_callback' => '__return_true',
    ]);

    // Kelas yang diajar guru
    register_rest_route('absensi-bubs/v1', '/kelas-guru', [
        'methods' => 'GET',
        'callback' => ['Absensi_Controller', 'get_kelas_dan_mapel_guru'],
        'permission_callback' => '__return_true',
    ]);

    // Export to Excel
    register_rest_route('absensi-bubs/v1', '/export-rekap-excel', [
        'methods' => 'GET',
        'callback' => ['Absensi_Controller', 'export_rekap_excel'],
        'permission_callback' => '__return_true',
    ]);

    // =============================================
    // ENDPOINT BARU UNTUK TUGAS & MATERI SYSTEM
    // =============================================

    // TUGAS ENDPOINTS
    register_rest_route('absensi-bubs/v1', '/tugas/create', [
        'methods' => 'POST',
        'callback' => 'bubs_create_tugas',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('absensi-bubs/v1', '/tugas/guru/(?P<id_guru>\d+)', [
        'methods' => 'GET',
        'callback' => 'bubs_get_tugas_by_guru',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('absensi-bubs/v1', '/tugas/siswa/(?P<id_siswa>\d+)', [
        'methods' => 'GET',
        'callback' => 'bubs_get_tugas_for_siswa',
        'permission_callback' => '__return_true',
    ]);

    // MATERI ENDPOINTS
    register_rest_route('absensi-bubs/v1', '/materi/upload', [
        'methods' => 'POST',
        'callback' => 'bubs_upload_materi',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('absensi-bubs/v1', '/materi/guru/(?P<id_guru>\d+)', [
        'methods' => 'GET',
        'callback' => 'bubs_get_materi_by_guru',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('absensi-bubs/v1', '/materi/siswa/(?P<id_kelas>\d+)', [
        'methods' => 'GET',
        'callback' => 'bubs_get_materi_for_siswa',
        'permission_callback' => '__return_true',
    ]);

    // SUBMISSION ENDPOINTS
    register_rest_route('absensi-bubs/v1', '/submission/create', [
        'methods' => 'POST',
        'callback' => 'bubs_create_submission',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('absensi-bubs/v1', '/submission/tugas/(?P<id_tugas>\d+)', [
        'methods' => 'GET',
        'callback' => 'bubs_get_submission_by_tugas',
        'permission_callback' => '__return_true',
    ]);
});


add_action('init', function() {
    header("Access-Control-Allow-Origin: http://localhost:5173");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    header("Access-Control-Allow-Headers: Authorization, Content-Type, X-User-Data");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header("HTTP/1.1 200 OK");
        exit;
    }
});
