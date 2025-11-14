<?php

class Absensi_Controller {

    public static function handle_request($request) {
        return rest_ensure_response([
            'message' => 'API Absensi Bubs - Endpoint tersedia',
            'endpoints' => [
                '/jadwal-siswa' => 'GET - Mendapatkan jadwal dan data siswa berdasarkan kriteria',
                '/mata-pelajaran' => 'GET - Mendapatkan mata pelajaran berdasarkan kelas dan hari'
            ],
            'time' => current_time('mysql'),
        ]);
    }

    public static function get_jadwal_siswa_by_kriteria($request) {
        try {
            $nama_kelas = $request->get_param('kelas');
            $hari = $request->get_param('hari');
            $mata_pelajaran = $request->get_param('mapel');

            // 🔹 Validasi parameter wajib
            $missing_params = [];
            if (!$nama_kelas) $missing_params[] = 'kelas';
            if (!$hari) $missing_params[] = 'hari';
            if (!$mata_pelajaran) $missing_params[] = 'mapel';

            if (!empty($missing_params)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Parameter berikut wajib diisi: ' . implode(', ', $missing_params)
                ], 400);
            }

            // 🔹 Validasi hari
            $hari_valid = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            if (!in_array($hari, $hari_valid)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Hari harus salah satu dari: ' . implode(', ', $hari_valid)
                ], 400);
            }

            // 🔹 Sanitize input
            $nama_kelas_clean = sanitize_text_field($nama_kelas);
            $hari_clean = sanitize_text_field($hari);
            $mata_pelajaran_clean = sanitize_text_field($mata_pelajaran);

            // 🔹 Panggil model
            $data = Absensi_Model::get_jadwal_siswa_by_kriteria(
                $nama_kelas_clean,
                $hari_clean,
                $mata_pelajaran_clean
            );

            $guru = Absensi_Model::get_guru_jadwal_siswa_by_kriteria(
                $nama_kelas_clean,
                $hari_clean,
                $mata_pelajaran_clean
            );

            // 🔹 Cek jika salah satu query gagal (WP_Error)
            if (is_wp_error($data)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Gagal mengambil data siswa.',
                    'error'   => $data->get_error_message(),
                ], 500);
            }

            if (is_wp_error($guru)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Gagal mengambil data guru.',
                    'error'   => $guru->get_error_message(),
                ], 500);
            }

            // 🔹 Pastikan hasil guru ada datanya
            $guru_nama = isset($guru[0]['nama']) ? $guru[0]['nama'] : '-';

            return new WP_REST_Response([
                'success' => true,
                'jumlah_siswa' => count($data),
                'kriteria' => [
                    'kelas' => $nama_kelas_clean,
                    'hari' => $hari_clean,
                    'mata_pelajaran' => $mata_pelajaran_clean,
                    'guru' => $guru_nama,
                ],
                'data' => $data,
            ], 200);

        } catch (Exception $e) {
            // 🔹 Tangkap error PHP tak terduga
            error_log('Controller Error: ' . $e->getMessage());
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Terjadi kesalahan tak terduga di server.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    public static function get_mata_pelajaran_by_kelas_hari($request) {
        $nama_kelas = $request->get_param('kelas');
        $hari = $request->get_param('hari');

        // Validasi parameter wajib
        if (!$nama_kelas || !$hari) {
            return new WP_Error(
                'missing_parameters',
                'Parameter kelas dan hari wajib diisi',
                ['status' => 400]
            );
        }

        // Validasi hari
        $hari_valid = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        if (!in_array($hari, $hari_valid)) {
            return new WP_Error(
                'invalid_hari',
                'Hari harus salah satu dari: ' . implode(', ', $hari_valid),
                ['status' => 400]
            );
        }

        // Sanitize input
        $nama_kelas_clean = sanitize_text_field($nama_kelas);
        $hari_clean = sanitize_text_field($hari);

        $data = Absensi_Model::get_mata_pelajaran_by_kelas_hari($nama_kelas_clean, $hari_clean);

        $mata_pelajaran_list = array_map(function($item) {
            return $item['mata_pelajaran'];
        }, $data);

        return rest_ensure_response([
            'success' => true,
            'kriteria' => [
                'kelas' => $nama_kelas_clean,
                'hari' => $hari_clean
            ],
            'mata_pelajaran' => $mata_pelajaran_list,
        ]);
    }

    public static function insert_absensi_sekolah($request)
    {
        try {
            $data = $request->get_json_params();

            if (empty($data)) {
                throw new Exception('Data tidak boleh kosong.');
            }

            $result_insert = Absensi_Model::insert_absensi_sekolah($data);

            return new WP_REST_Response([
                'success' => true,
                'message' => 'Data generus berhasil ditambahkan.',
                'result' => $result_insert,
            ], 201);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'success' => false,
                'error' => true,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    // =============================================
    // CONTROLLER BARU UNTUK ABSENSI KEGIATAN
    // =============================================

    /**
     * Get semua jenis kegiatan
     */
    public static function get_jenis_kegiatan($request) {
        try {
            $data = Absensi_Model::get_jenis_kegiatan();

            return rest_ensure_response([
                'success' => true,
                'data' => $data,
                'jumlah' => count($data)
            ]);
        } catch (Exception $e) {
            return new WP_Error(
                'server_error',
                'Terjadi kesalahan server: ' . $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Get kelas boarding
     */
    public static function get_kelas_boarding($request) {
        try {
            $data = Absensi_Model::get_kelas_boarding();

            return rest_ensure_response([
                'success' => true,
                'data' => $data,
                'jumlah' => count($data)
            ]);
        } catch (Exception $e) {
            return new WP_Error(
                'server_error',
                'Terjadi kesalahan server: ' . $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Get semua kamar
     */
    public static function get_kamar($request) {
        try {
            $data = Absensi_Model::get_kamar();

            return rest_ensure_response([
                'success' => true,
                'data' => $data,
                'jumlah' => count($data)
            ]);
        } catch (Exception $e) {
            return new WP_Error(
                'server_error',
                'Terjadi kesalahan server: ' . $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Get siswa untuk kegiatan
     */
    public static function get_siswa_kegiatan($request) {
        $id_kegiatan = $request->get_param('kegiatan');
        $id_kelas = $request->get_param('kelas');
        $id_kamar = $request->get_param('kamar');

        // Validasi parameter
        if (!$id_kegiatan) {
            return new WP_Error(
                'missing_parameters',
                'Parameter kegiatan wajib diisi',
                ['status' => 400]
            );
        }

        // Sanitize input
        $id_kegiatan_clean = intval($id_kegiatan);
        $id_kelas_clean = $id_kelas ? intval($id_kelas) : NULL;
        $id_kamar_clean = $id_kamar ? intval($id_kamar) : NULL;

        try {
            // Tentukan jenis query berdasarkan parameter
            if ($id_kelas_clean !== NULL) {
                // Kegiatan SEKOLAH (berdasarkan kelas)
                $data = Absensi_Model::get_siswa_kegiatan_sekolah($id_kelas_clean, $id_kegiatan_clean);
            } elseif ($id_kamar_clean !== NULL) {
                // Kegiatan PONDOK (berdasarkan kamar)
                $data = Absensi_Model::get_siswa_kegiatan_pondok($id_kamar_clean, $id_kegiatan_clean);
            } else {
                return new WP_Error(
                    'missing_parameters',
                    'Parameter kelas atau kamar wajib diisi',
                    ['status' => 400]
                );
            }

            // Handle error dari model
            if (is_wp_error($data)) {
                return $data;
            }

            return rest_ensure_response([
                'success' => true,
                'jumlah_siswa' => count($data),
                'data' => $data,
            ]);
        } catch (Exception $e) {
            return new WP_Error(
                'server_error',
                'Terjadi kesalahan server: ' . $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Insert absensi kegiatan
     */
    public static function insert_absensi_kegiatan($request) {
        try {
            $data = $request->get_json_params();

            if (empty($data)) {
                throw new Exception('Data tidak boleh kosong.');
            }

            $result_insert = Absensi_Model::insert_absensi_kegiatan($data);

            if (is_wp_error($result_insert)) {
                return $result_insert;
            }

            return new WP_REST_Response([
                'success' => true,
                'message' => 'Data absensi kegiatan berhasil disimpan.',
                'result' => $result_insert,
            ], 201);
        } catch (Exception $e) {
            error_log('API Insert absensi kegiatan: ' . $e->getMessage());

            return new WP_REST_Response([
                'success' => false,
                'error' => true,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
// Tambahkan di controller.php (setelah fungsi yang sudah ada)

/**
 * Handle user login
 */
public static function login_user($request) {
    try {
        $data = $request->get_json_params();
        
        $username = sanitize_text_field($data['username'] ?? '');
        $password = sanitize_text_field($data['password'] ?? '');

        // Validasi input
        if (empty($username) || empty($password)) {
            throw new Exception('Username dan password wajib diisi.');
        }

        $user = Absensi_Model::verify_login($username, $password);

        if (is_wp_error($user)) {
            return $user;
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Login berhasil',
            'user' => $user
        ], 200);

    } catch (Exception $e) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $e->getMessage()
        ], 400);
    }
}

// Tambahkan di controller.php

/**
 * Get presensi history for siswa
 */
public static function get_presensi_siswa(WP_REST_Request $request) {
    try {
        $user = self::get_current_user($request);

        if (!$user) {
            return new WP_Error('no_user_data', 'User data not found in request headers', ['status' => 400]);
        }
        
        if (!$user || $user['role'] !== 'SISWA') {
            throw new Exception('Akses ditolak. Hanya untuk siswa.');
        }
        
        $bulan = $request->get_param('bulan') ?: date('m');
        $tahun = $request->get_param('tahun') ?: date('Y');

        $data = Absensi_Model::get_presensi_siswa_model($user['id_siswa'], $bulan, $tahun);
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $data,
            'periode' => [
                'bulan' => intval($bulan),
                'tahun' => intval($tahun)
            ]
        ], 200);
        
    } catch (Exception $e) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $e->getMessage()
        ], 400);
    }
}

/**
 * Get rekap presensi kelas untuk guru
 */
public static function get_rekap_presensi_kelas($request) {
    try {
        $user = self::get_current_user();
        
        if (!$user || $user['role'] !== 'GURU') {
            throw new Exception('Akses ditolak. Hanya untuk guru.');
        }
        
        $id_kelas = $request->get_param('kelas');
        $bulan = $request->get_param('bulan') ?: date('m');
        $tahun = $request->get_param('tahun') ?: date('Y');
        
        if (!$id_kelas) {
            throw new Exception('Parameter kelas wajib diisi.');
        }
        
        $data = Absensi_Model::get_rekap_presensi_kelas($id_kelas, $user['id_guru'], $bulan, $tahun);
        
        if (is_wp_error($data)) {
            return $data;
        }
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $data,
            'periode' => [
                'bulan' => intval($bulan),
                'tahun' => intval($tahun)
            ]
        ], 200);
        
    } catch (Exception $e) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $e->getMessage()
        ], 400);
    }
}

/**
 * Helper function to get current user from localStorage data
 * Note: Ini sederhana dulu, nanti bisa enhance dengan proper session/token
 */
// private static function get_current_user() {
//     // Untuk MVP, kita terima user data via headers
//     // Nanti bisa enhance dengan proper authentication
//     $user_data = isset($_SERVER['HTTP_X_USER_DATA']) ? $_SERVER['HTTP_X_USER_DATA'] : '';
    
//     if ($user_data) {
//         return json_decode(stripslashes($user_data), true);
//     }
    
//     return null;
// }
private static function get_current_user(WP_REST_Request $request) {
    // Ambil header X-User-Data
    $user_data_json = $request->get_header('X-User-Data');

    // Jika header tidak ada, kembalikan null
    if (!$user_data_json) {
        return null;
    }

    // Decode JSON menjadi array
    $user_data = json_decode($user_data_json, true);

    // Cek apakah JSON valid
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null; // atau bisa lempar error
    }

    return $user_data;
}



// Tambahkan di controller.php

/**
 * Get rekap presensi kelas detail untuk guru
 */
public static function get_rekap_presensi_kelas_detailed($request) {
    try {
        $user = self::get_current_user($request);

        if (!$user) {
            return new WP_Error('no_user_data', 'User data not found in request headers', ['status' => 400]);
        }
        
        if (!$user || $user['role'] !== 'GURU') {
            throw new Exception('Akses ditolak. Hanya untuk guru.');
        }
        
        $id_kelas = $request->get_param('kelas');
        $bulan = $request->get_param('bulan') ?: date('m');
        $tahun = $request->get_param('tahun') ?: date('Y');
        $mapel = $request->get_param('mapel');

        if (!$id_kelas) {
            throw new Exception('Parameter kelas wajib diisi.');
        }
        if (!$bulan) {
            throw new Exception('Parameter bulan wajib diisi.');
        }
        if (!$tahun) {
            throw new Exception('Parameter tahun wajib diisi.');
        }
        if (!$mapel) {
            throw new Exception('Parameter mapel wajib diisi.');
        }

        $data = Absensi_Model::get_rekap_presensi_kelas_detailed(
            $id_kelas, $user['id_guru'], $bulan, $tahun, $mapel
        );

        if (is_wp_error($data)) {
            return $data;
        }

        // Get additional info for response
        $kelas_info = Absensi_Model::get_nama_kelas($id_kelas);
        
        $mapel_list = Absensi_Model::get_mata_pelajaran_guru($user['id_guru'], $id_kelas);

        // return new WP_REST_Response([
        //     'success' => true,
        //     'data' => $mapel_list,
        //     'message' => 'ini response coba-coba mapel list'
        // ], 200);
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $data,
            'info' => [
                'kelas' => $kelas_info['nama_kelas'] ?? '',
                'bulan' => intval($bulan),
                'tahun' => intval($tahun),
                'mapel' => $mapel,
                'total_siswa' => count($data)
            ],
            'filters' => [
                'mapel_list' => array_column($mapel_list, 'mata_pelajaran')
            ]
        ], 200);
        
    } catch (Exception $e) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $e->getMessage()
        ], 400);
    }
}

/**
 * Get kelas yang diajar guru
 */
public static function get_kelas_guru($request) {
    try {
        $user = self::get_current_user($request);

        if (!$user) {
            return new WP_Error('no_user_data', 'User data not found in request headers', ['status' => 400]);
        }
        
        if (!$user || $user['role'] !== 'GURU') {
            throw new Exception('Akses ditolak. Hanya untuk guru.');
        }
        
        $data = Absensi_Model::get_kelas_guru($user['id_guru']);
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $data
        ], 200);
        
    } catch (Exception $e) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $e->getMessage()
        ], 400);
    }
}

public static function get_kelas_dan_mapel_guru($request) {
    try {
        $data = Absensi_Model::bubs_get_jadwal_by_guru_id($request['id_guru']);
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $data
        ], 200);
        
    } catch (Exception $e) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $e->getMessage()
        ], 400);
    }
}

/**
 * Export rekap presensi to Excel
 */
public static function export_rekap_excel($request) {
    try {
        $user = self::get_current_user();
        
        if (!$user || $user['role'] !== 'GURU') {
            throw new Exception('Akses ditolak. Hanya untuk guru.');
        }
        
        $id_kelas = $request->get_param('kelas');
        $bulan = $request->get_param('bulan') ?: date('m');
        $tahun = $request->get_param('tahun') ?: date('Y');
        $mapel = $request->get_param('mapel');
        
        if (!$id_kelas) {
            throw new Exception('Parameter kelas wajib diisi.');
        }
        
        $data = Absensi_Model::get_rekap_presensi_kelas_detailed(
            $id_kelas, $user['id_guru'], $bulan, $tahun, $mapel
        );
        
        if (is_wp_error($data)) {
            return $data;
        }
        
        // Generate Excel file
        $filename = self::generate_excel_file($data, $bulan, $tahun, $mapel);
        
        return new WP_REST_Response([
            'success' => true,
            'file_url' => $filename,
            'message' => 'File Excel berhasil di-generate'
        ], 200);
        
    } catch (Exception $e) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $e->getMessage()
        ], 400);
    }
}

/**
 * Generate Excel file (simplified version)
 * Note: Untuk production, bisa pakai library seperti PhpSpreadsheet
 */
private static function generate_excel_file($data, $bulan, $tahun, $mapel) {
    $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $month_name = $months[$bulan - 1] ?? 'Unknown';
    
    // Create CSV content (simplified - untuk production pakai PhpSpreadsheet)
    $csv_content = "Rekap Presensi - {$month_name} {$tahun}\n";
    $csv_content .= "Mata Pelajaran: " . ($mapel ?: 'Semua Mapel') . "\n\n";
    $csv_content .= "No,NIS,Nama Siswa,Hadir,Izin,Sakit,Alpa,Total,Presentase\n";
    
    $counter = 1;
    foreach ($data as $row) {
        $csv_content .= "{$counter},{$row['nik']},{$row['nama_lengkap']},{$row['hadir']},{$row['izin']},{$row['sakit']},{$row['alpa']},{$row['total_pertemuan']},{$row['presentase']}%\n";
        $counter++;
    }
    
    // Save file temporarily
    $filename = "rekap_presensi_{$bulan}_{$tahun}_" . time() . ".csv";
    $filepath = WP_CONTENT_DIR . '/uploads/' . $filename;
    
    file_put_contents($filepath, $csv_content);
    
    return content_url('/uploads/' . $filename);
}
}