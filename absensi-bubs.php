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

defined('ABSPATH') or die('No script kiddies please!');

// Contoh fungsi sederhana
function my_plugin_hello_world() {
    echo "<p>Hello from My Plugin!</p>";
}

// Hook fungsi ke 'wp_footer'
add_action('wp_footer', 'my_plugin_hello_world');
