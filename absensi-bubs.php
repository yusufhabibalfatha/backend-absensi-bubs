<?php
/*
Plugin Name: Absensi Bubs
Plugin URI: https://example.com/my-plugin
Description: Plugin sederhana buatan saya.
Version: 1.0
Author: Nama Kamu
License: GPL2
*/

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'model.php';
require_once plugin_dir_path(__FILE__) . 'controller.php';

add_action('rest_api_init', function () {
    register_rest_route('headless/v1', '/hello', [
        'methods' => 'GET',
        'callback' => ['Simple_Controller', 'handle_request'],
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('headless/v1', '/siswa-by-kelas', [
        'methods' => 'GET',
        'callback' => ['Simple_Controller', 'get_siswa_by_nama_kelas'],
        'permission_callback' => '__return_true',
    ]);
});
