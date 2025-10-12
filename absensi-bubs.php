<?php
/*
Plugin Name: Absensi Bubs
Plugin URI: https://example.com/absensi-bubs
Description: Plugin untuk sistem absensi sekolah Bubs.
Version: 1.0
Author: Nama Anda
License: GPL2
*/

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'model.php';
require_once plugin_dir_path(__FILE__) . 'controller.php';


// API
// GET /wp-json/absensi-bubs/v1/
// GET /wp-json/absensi-bubs/v1/jadwal-siswa?kelas=10 IPA 1&hari=Senin&mapel=Matematika
// GET /wp-json/absensi-bubs/v1/mata-pelajaran?kelas=10 IPA 1&hari=Senin

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
});