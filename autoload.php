<?php

spl_autoload_register(function ($class) {
    // Namespace prefix
    $prefix = 'MyPlugin\\';

    // Apakah class memakai prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return; // Bukan class dari plugin ini
    }

    // Ambil nama class setelah namespace
    $relative_class = substr($class, $len);

    // Ganti namespace separator dengan directory separator
    $file = plugin_dir_path(__FILE__) . str_replace('\\', '/', $relative_class) . '.php';

    // Jika file-nya ada, require
    if (file_exists($file)) {
        require $file;
    }
});
