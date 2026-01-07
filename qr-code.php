<?php
// require_once('path/to/qrcode/library.php');  // Adjust path as needed

function bubs_download_qr(){
    // require_once('./phpqrcode/index.php');

    require_once plugin_dir_path(__FILE__) . 'phpqrcode/qrlib.php';
    
    // $test = plugin_dir_path(__FILE__) . "phpqrcode/qrlib.php";

    // return rest_ensure_response([
    //     'success' => true,
    //     'zip_path' => $test,
    // ]);

//   $kelas_id = intval($_GET['kelas_id']);
//   $base_dir = WP_CONTENT_DIR . "/uploads/qr/kelas-$kelas_id";
  $base_dir = WP_CONTENT_DIR . "/uploads/qr";
  
  if (!file_exists($base_dir)) {
      mkdir($base_dir, 0755, true);
  }
  
  global $wpdb;
  $siswa_table = 'bubs_siswa';

//   $siswa = $wpdb->get_results("SELECT nik, nama_lengkap FROM $siswa_table WHERE kelas_id = $kelas_id");
  $siswa = $wpdb->get_results("SELECT nik, nama_lengkap FROM $siswa_table");
  
  foreach ($siswa as $s) {
      $filename = $base_dir . "/{$s->nama_lengkap} - " . "{$s->nik}.png";
  
      if (!file_exists($filename)) {
          // generate token
        //   $payload = "absen/{$s->nik}";

        $website_frontend = "https://bubstarakan.com";
        
          $payload = $website_frontend . "/{$s->nik}";
          
          // generate qr
          QRcode::png($payload, $filename, QR_ECLEVEL_L, 6);
      }
  }
  
  // setelah semua ada → zip
//   $zip_path = "$base_dir/qr-kelas-$kelas_id.zip";
  $zip_path = "$base_dir/qr.zip";
  zipFolder($base_dir, $zip_path);
//   zipFolderUsingExec($base_dir, $zip_path);
  
  // return file zip
    return rest_ensure_response([
        'success' => true,
        'zip_path' => $zip_path,
    ]);
}

function zipFolderUsingExec($folderPath, $zipFilePath) {
    $command = "zip -r $zipFilePath $folderPath";
    exec($command);
}

function zipFolder($folderPath, $zipFilePath) {
    $zip = new ZipArchive();
    if ($zip->open($zipFilePath, ZIPARCHIVE::CREATE) !== TRUE) {
        exit("Cannot open <$zipFilePath>\n");
    }

    // Menambahkan folder ke dalam file ZIP
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($folderPath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($folderPath) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }

    $zip->close();
}

