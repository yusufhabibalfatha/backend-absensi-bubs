<?php
/*
Plugin Name: Absensi Bubs
Plugin URI: https://example.com/my-plugin
Description: Plugin sederhana buatan saya.
Version: 1.0
Author: Nama Kamu
Author URI: https://example.com
License: GPL2
*/
// komen baru

defined('ABSPATH') or die('No script kiddies please!');
defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'autoload.php';
require_once plugin_dir_path(__FILE__) . 'init.php';



// Contoh fungsi sederhana
function my_plugin_hello_world() {
    echo "<p>Hello from My Plugin!</p>";
}

// Hook fungsi ke 'wp_footer'
add_action('wp_footer', 'my_plugin_hello_world');
