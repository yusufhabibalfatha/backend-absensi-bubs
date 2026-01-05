<?php
/*
Plugin Name: Absensi Bubs V2
Plugin URI: https://yusufhabib.site
Description: Plugin untuk sistem absensi sekolah Bubs.
Version: 1.1
Author: Yusuf Habib Alfatha, S.Kom., Gr
License: GPL2
*/

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'install-tables.php';
register_activation_hook(__FILE__, 'bubs_create_all_tables');

require_once plugin_dir_path(__FILE__) . 'model.php';
require_once plugin_dir_path(__FILE__) . 'controller.php';
require_once plugin_dir_path(__FILE__) . 'tugas-endpoints.php';
require_once plugin_dir_path(__FILE__) . 'materi-endpoints.php';
require_once plugin_dir_path(__FILE__) . 'submission-endpoints.php';
require_once plugin_dir_path(__FILE__) . 'qr-code.php';


add_action('rest_api_init', function () {
    /**
     * ? API ini tidak digunakan di Frontend
     * TODO: API bisa dihapus saja
     * */ 
    register_rest_route('absensi-bubs/v1', '/', [
        'methods' => 'GET',
        'callback' => ['Absensi_Controller', 'handle_request'],
        'permission_callback' => '__return_true',
    ]);


    /**
     * ? API untuk mengambil data kelas siswa yang akan diabsen berdasarkan params
     * * Fungsi Handle API ada didalam file controller.php
     * @param kelas nama kelas diambil dari data jadwal.js
     * @param hari nama hari diambil dari variable
     * @param mapel nama mapel diambil dari data jadwal.js
     * TODO: Refactor fungsi controller ke file baru
     * */ 
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


    // =============================================
    // API UNTUK ABSENSI KEGIATAN
    // =============================================

    /**
     * ? API untuk mengambil data jenis kegiatan yang ada
     * * Fungsi Handle API ada didalam file controller.php
     * TODO: Refactor fungsi controller ke file baru
     * */ 
    register_rest_route('absensi-bubs/v1', '/jenis-kegiatan', [
        'methods' => 'GET',
        'callback' => ['Absensi_Controller', 'get_jenis_kegiatan'],
        'permission_callback' => '__return_true',
    ]);


    /**
     * ? API untuk mengambil data kelas-kelas di boarding
     * * Fungsi Handle API ada didalam file controller.php
     * TODO: Refactor fungsi controller ke file baru
     * */ 
    register_rest_route('absensi-bubs/v1', '/kelas-boarding', [
        'methods' => 'GET',
        'callback' => ['Absensi_Controller', 'get_kelas_boarding'],
        'permission_callback' => '__return_true',
    ]);


    /**
     * ? API untuk mengambil data kamar-kamar di pondok
     * * Fungsi Handle API ada didalam file controller.php
     * TODO: Refactor fungsi controller ke file baru
     * */ 
    register_rest_route('absensi-bubs/v1', '/kamar', [
        'methods' => 'GET',
        'callback' => ['Absensi_Controller', 'get_kamar'],
        'permission_callback' => '__return_true',
    ]);


    /**
     * ? API untuk mengambil data yang ingin diabsen yaitu siswa atau reguler
     * * Fungsi Handle API ada didalam file controller.php
     * @param kegiatan.id 
     * @param kelas.id jika yang diabsen adalah kegiatan boarding
     * @param kamar.id jika yang diabsen adalah kegiatan pondok
     * TODO: Refactor fungsi controller ke file baru
     * */
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


    /**
     * ? API untuk memasukkan data absen mapel
     * * Fungsi Handle API ada didalam file controller.php
     * @param formData data keterangan absen setiap siswa
     * TODO: Refactor fungsi controller ke file baru
     * */
    register_rest_route('absensi-bubs/insert', 'absensi-sekolah', [
        'methods' => 'POST',
        'callback' => ['Absensi_Controller', 'insert_absensi_sekolah'],
        'permission_callback' => '__return_true',
        'args' => []
    ]);


    /**
     * ? API untuk memasukkan data absen kegiatan
     * * Fungsi Handle API ada didalam file controller.php
     * @param submissionData data keterangan absen setiap siswa
     * TODO: Refactor fungsi controller ke file baru
     * */
    register_rest_route('absensi-bubs/insert', 'absensi-kegiatan', [
        'methods' => 'POST',
        'callback' => ['Absensi_Controller', 'insert_absensi_kegiatan'],
        'permission_callback' => '__return_true',
        'args' => []
    ]);


    /**
     * ? API untuk login
     * * Fungsi Handle API ada didalam file controller.php
     * @param formData data username dan password
     * TODO: Refactor fungsi controller ke file baru
     * */
    register_rest_route('absensi-bubs/v1', '/login', [
        'methods' => 'POST',
        'callback' => ['Absensi_Controller', 'login_user'],
        'permission_callback' => '__return_true',
    ]);


    /**
     * ? API untuk rekap presensi di dashboard siswa
     * * Fungsi Handle API ada didalam file controller.php
     * @param selectedMonth
     * @param selectedYear
     * TODO: Refactor fungsi controller ke file baru
     * */
    register_rest_route('absensi-bubs/v1', '/presensi-siswa', [
        'methods' => 'GET',
        'callback' => ['Absensi_Controller', 'get_presensi_siswa'],
        'permission_callback' => '__return_true',
    ]);

    /**
     * ? API untuk rekap presensi di dashboard guru
     * * Fungsi Handle API ada didalam file controller.php
     * @param selectedKelas
     * @param selectedMonth
     * @param selectedYear
     * @param selectedMapel
     * TODO: Refactor fungsi controller ke file baru
     * */
    register_rest_route('absensi-bubs/v1', '/rekap-presensi-kelas-detailed', [
        'methods' => 'GET',
        'callback' => ['Absensi_Controller', 'get_rekap_presensi_kelas_detailed'],
        'permission_callback' => '__return_true',
    ]);


    /**
     * ? API untuk ambil data kelas-kelas yang diajar guru
     * * Fungsi Handle API ada didalam file controller.php
     * @param id_guru
     * TODO: Refactor fungsi controller ke file baru
     * */
    register_rest_route('absensi-bubs/v1', '/kelas-guru', [
        'methods' => 'GET',
        'callback' => ['Absensi_Controller', 'get_kelas_dan_mapel_guru'],
        'permission_callback' => '__return_true',
    ]);

    /**
     * ? API untuk export rekap absen didashboard guru
     * * Fungsi Handle API ada didalam file controller.php
     * ! Hasil export excel masih berantakan
     * @param selectedKelas
     * @param selectedMonth
     * @param selectedYear
     * @param selectedMapel
     * TODO: Refactor fungsi controller ke file baru
     * */
    register_rest_route('absensi-bubs/v1', '/export-rekap-excel', [
        'methods' => 'GET',
        'callback' => ['Absensi_Controller', 'export_rekap_excel'],
        'permission_callback' => '__return_true',
    ]);


    /**
     * ? API untuk upload/insert materi baru dari dashboard guru
     * @param submitData berisi data id_guru, judul_materi, deskripsi, id_mata_pelajaran, dan file
     * */
    register_rest_route('bubs/v1', '/materi/upload', array(
        'methods' => 'POST',
        'callback' => 'bubs_upload_materi',
        'permission_callback' => '__return_true',
    ));


    /**
     * ? API untuk upload/insert tugas baru dari dashboard guru
     * @param submitData berisi data id_guru, judul_tugas, deskripsi, id_mata_pelajaran, deadline_datetime, bobot_nilai, dan file
     * */
    register_rest_route('bubs/v1', '/tugas/create', array(
        'methods' => 'POST',
        'callback' => 'bubs_create_tugas',
        'permission_callback' => '__return_true',
    ));


    /**
     * ? API untuk upload/insert hasil pengerjaan tugas siswa
     * @param submitData berisi data id_tugas, id_siswa, jawaban_text, dan file
     * */
    register_rest_route('bubs/v1', '/submission/create', array(
        'methods' => 'POST',
        'callback' => 'bubs_create_submission',
        'permission_callback' => '__return_true',
    ));


    /**
     * ? API untuk ambil list data hasil pengerjaan tugas siswa
     * @param id_tugas diambil dari tugas yang diklik oleh guru
     * */
    register_rest_route('bubs/v1', '/submission/tugas/(?P<id_tugas>\d+)', array(
        'methods' => 'GET',
        'callback' => 'bubs_get_submission_by_tugas',
        'permission_callback' => '__return_true',
    ));


    /**
     * ? API untuk ambil data detail dari tugas
     * @param id_guru 
     * */
    register_rest_route('bubs/v1', '/tugas/guru/(?P<id_guru>\d+)', array(
        'methods' => 'GET',
        'callback' => 'bubs_get_tugas_by_guru',
        'permission_callback' => '__return_true',
    ));


    /**
     * ? API untuk memberikan nilai kepada submission pada setiap siswa
     * @param id_submission 
     * @param id_guru 
     * @param nilai 
     * @param catatan_guru 
     * */
    register_rest_route('absensi-bubs/v1', '/nilai/beri', array(
        'methods' => 'POST',
        'callback' => 'bubs_beri_nilai',
        'permission_callback' => '__return_true',
    ));

    /**
     * ? API untuk download qr code
     * */
    register_rest_route('absensi-bubs/v1', 'download-qr', array(
        'methods' => 'GET',
        'callback' => 'bubs_download_qr',
        'permission_callback' => '__return_true',
    ));

});


add_action('init', function() {
    header("Access-Control-Allow-Origin: https://bubs.sdit.web.id");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    header("Access-Control-Allow-Headers: Authorization, Content-Type, X-User-Data");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header("HTTP/1.1 200 OK");
        exit;
    }
});
