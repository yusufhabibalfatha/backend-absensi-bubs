<?php

class Absensi_Model {

    public static function get_jadwal_siswa_by_kriteria($nama_kelas, $hari, $mata_pelajaran) {
        global $wpdb;

        $jadwal_table = 'bubs_jadwal';
        $kelas_table = 'bubs_kelas';
        $siswa_table = 'bubs_siswa';

        $query = $wpdb->prepare("
            SELECT 
                j.id AS id_jadwal,
                s.id AS id_siswa,
                s.nama_lengkap,
                s.nik,
                NULL AS tanggal,
                NULL AS status,
                NULL AS keterangan,
                s.jenis_kelamin
            FROM 
                {$jadwal_table} j
            INNER JOIN 
                {$kelas_table} k ON j.id_kelas = k.id
            INNER JOIN 
                {$siswa_table} s ON k.id = s.id_kelas
            WHERE 
                k.nama_kelas = %s AND
                j.hari = %s AND
                j.mata_pelajaran = %s
            ORDER BY 
                s.nama_lengkap ASC
        ", $nama_kelas, $hari, $mata_pelajaran);

        $results = $wpdb->get_results($query, ARRAY_A);

        if ($wpdb->last_error) {
            error_log('Database Error: ' . $wpdb->last_error);
            return new WP_Error(
                'db_query_failed',
                'Terjadi kesalahan pada query database.',
                ['error' => $wpdb->last_error]
            );
        }


        return $results;
    }

    public static function get_guru_jadwal_siswa_by_kriteria($nama_kelas, $hari, $mata_pelajaran) {
        global $wpdb;

        $jadwal_table = 'bubs_jadwal';
        $kelas_table = 'bubs_kelas';
        $guru_table = 'bubs_guru';

        $query = $wpdb->prepare("
            SELECT 
                g.nama
            FROM 
                {$jadwal_table} j
            INNER JOIN 
                {$kelas_table} k ON j.id_kelas = k.id
            INNER JOIN 
                {$guru_table} g ON g.id = j.id_guru
            WHERE 
                k.nama_kelas = %s AND
                j.hari = %s AND
                j.mata_pelajaran = %s
        ", $nama_kelas, $hari, $mata_pelajaran);

        $results = $wpdb->get_results($query, ARRAY_A);

        if ($wpdb->last_error) {
            error_log('Database Error: ' . $wpdb->last_error);
            return new WP_Error(
                'db_query_failed',
                'Terjadi kesalahan pada query database.',
                ['error' => $wpdb->last_error]
            );
        }


        return $results;
    }

    public static function get_hari_available() {
        return ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    }

    public static function get_mata_pelajaran_by_kelas_hari($nama_kelas, $hari) {
        global $wpdb;

        $jadwal_table = 'bubs_jadwal';
        $kelas_table = 'bubs_kelas';

        $query = $wpdb->prepare("
            SELECT DISTINCT 
                j.mata_pelajaran
            FROM 
                {$jadwal_table} j
            INNER JOIN 
                {$kelas_table} k ON j.id_kelas = k.id
            WHERE 
                k.nama_kelas = %s AND
                j.hari = %s
            ORDER BY 
                j.mata_pelajaran ASC
        ", $nama_kelas, $hari);

        $results = $wpdb->get_results($query, ARRAY_A);

        return $results;
    }

    public static function insert_absensi_sekolah($data) {
        global $wpdb;
        $prefix = 'bubs_';

        if (!is_array($data)) {
            throw new Exception('Data harus berupa array.');
        }

        $inserted_ids = [];

        foreach ($data as $item) {
            // Validasi minimal
            if (empty($item['id_siswa']) || empty($item['id_jadwal']) || empty($item['status'])) {
                continue; // Skip data yang tidak valid
            }

            // Validasi & sanitasi tanggal
            $tanggal = sanitize_text_field($item['tanggal'] ?? '');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
                $tanggal = current_time('Y-m-d');
            }

            $inserted = $wpdb->insert("{$prefix}absensi_sekolah", [
                'id_siswa'     => intval($item['id_siswa']),
                'id_jadwal'    => intval($item['id_jadwal']),
                'tanggal'      => $tanggal,
                'status'       => sanitize_text_field($item['status']),
                'keterangan'   => sanitize_text_field($item['keterangan'] ?? ''),
            ]);

            if ($inserted !== false) {
                $inserted_ids[] = $wpdb->insert_id;
            }
        }

        if (empty($inserted_ids)) {
            return new WP_Error('insert_failed', 'Tidak ada data yang berhasil disimpan.', ['status' => 500]);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => count($inserted_ids) . ' data absensi berhasil disimpan.',
            'inserted_ids' => $inserted_ids
        ], 200);
    }


     // =============================================
    // FUNGSI BARU UNTUK ABSENSI KEGIATAN
    // =============================================

    /**
     * Get semua jenis kegiatan
     */
    public static function get_jenis_kegiatan() {
        global $wpdb;
        $table = 'bubs_jenis_kegiatan';

        $query = "SELECT * FROM {$table} ORDER BY kategori, nama_kegiatan";
        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Get kelas yang memiliki siswa boarding
     */
    public static function get_kelas_boarding() {
        global $wpdb;
        $kelas_table = 'bubs_kelas';
        $siswa_table = 'bubs_siswa';

        $query = "
            SELECT DISTINCT 
                k.id, 
                k.nama_kelas 
            FROM 
                {$kelas_table} k
            INNER JOIN 
                {$siswa_table} s ON k.id = s.id_kelas
            WHERE 
                s.status_siswa = 'BOARDING'
            ORDER BY 
                k.nama_kelas ASC
        ";

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Get semua kamar
     */
    public static function get_kamar() {
        global $wpdb;
        $table = 'bubs_kamar';

        $query = "SELECT * FROM {$table} ORDER BY nama_kamar";
        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Get siswa untuk kegiatan SEKOLAH (berdasarkan kelas)
     */
    public static function get_siswa_kegiatan_sekolah($id_kelas, $id_kegiatan) {
        global $wpdb;
        $siswa_table = 'bubs_siswa';
        $kelas_table = 'bubs_kelas';
        $kegiatan_table = 'bubs_jenis_kegiatan';

        // Validasi kegiatan sekolah
        $kegiatan = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$kegiatan_table} WHERE id = %d AND kategori = 'SEKOLAH'",
            $id_kegiatan
        ), ARRAY_A);

        if (!$kegiatan) {
            return new WP_Error('invalid_kegiatan', 'Kegiatan tidak valid atau bukan kategori sekolah.');
        }

        $query = $wpdb->prepare("
            SELECT 
                s.id AS id_siswa,
                s.nama_lengkap,
                s.nik,
                s.jenis_kelamin,
                k.id AS id_kelas,
                k.nama_kelas,
                NULL AS id_kamar,
                NULL AS nama_kamar,
                %d AS id_kegiatan,
                %s AS nama_kegiatan,
                NULL AS tanggal,
                NULL AS status,
                NULL AS keterangan
            FROM 
                {$siswa_table} s
            INNER JOIN 
                {$kelas_table} k ON s.id_kelas = k.id
            WHERE 
                s.id_kelas = %d AND
                s.status_siswa = 'BOARDING'
            ORDER BY 
                s.nama_lengkap ASC
        ", $id_kegiatan, $kegiatan['nama_kegiatan'], $id_kelas);

        $results = $wpdb->get_results($query, ARRAY_A);

        return $results;
    }

    /**
     * Get siswa untuk kegiatan PONDOK (berdasarkan kamar)
     */
    public static function get_siswa_kegiatan_pondok($id_kamar, $id_kegiatan) {
        global $wpdb;
        $siswa_table = 'bubs_siswa';
        $kamar_table = 'bubs_kamar';
        $kegiatan_table = 'bubs_jenis_kegiatan';

        // Validasi kegiatan pondok
        $kegiatan = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$kegiatan_table} WHERE id = %d AND kategori = 'PONDOK'",
            $id_kegiatan
        ), ARRAY_A);

        if (!$kegiatan) {
            return new WP_Error('invalid_kegiatan', 'Kegiatan tidak valid atau bukan kategori pondok.');
        }

        $query = $wpdb->prepare("
            SELECT 
                s.id AS id_siswa,
                s.nama_lengkap,
                s.nik,
                s.jenis_kelamin,
                s.id_kelas,
                k.nama_kelas,
                km.id AS id_kamar,
                km.nama_kamar,
                %d AS id_kegiatan,
                %s AS nama_kegiatan,
                NULL AS tanggal,
                NULL AS status,
                NULL AS keterangan
            FROM 
                {$siswa_table} s
            INNER JOIN 
                {$kamar_table} km ON s.id_kamar = km.id
            INNER JOIN 
                bubs_kelas k ON s.id_kelas = k.id
            WHERE 
                s.id_kamar = %d
            ORDER BY 
                s.nama_lengkap ASC
        ", $id_kegiatan, $kegiatan['nama_kegiatan'], $id_kamar);

        $results = $wpdb->get_results($query, ARRAY_A);

        return $results;
    }

    /**
     * Insert absensi kegiatan
     */
    public static function insert_absensi_kegiatan($data) {
        global $wpdb;
        $prefix = 'bubs_';

        if (!is_array($data)) {
            return new WP_Error('invalid_data', 'Data harus berupa array.', ['status' => 400]);
        }

        $inserted_ids = [];
        $today = current_time('Y-m-d');

        foreach ($data as $item) {
            // Validasi minimal
            if (empty($item['id_siswa']) || empty($item['id_kegiatan']) || empty($item['status'])) {
                continue; // Skip data yang tidak valid
            }

            // Tentukan apakah kegiatan sekolah atau pondok
            $id_kelas = !empty($item['id_kelas']) ? intval($item['id_kelas']) : NULL;
            $id_kamar = !empty($item['id_kamar']) ? intval($item['id_kamar']) : NULL;

            // Validasi: harus salah satu yang diisi
            if ($id_kelas === NULL && $id_kamar === NULL) {
                continue; // Skip data invalid
            }

            $insert_data = [
                'id_siswa'     => intval($item['id_siswa']),
                'id_kegiatan'  => intval($item['id_kegiatan']),
                'id_kelas'     => $id_kelas,
                'id_kamar'     => $id_kamar,
                'tanggal'      => $today,
                'status'       => sanitize_text_field($item['status']),
                'keterangan'   => sanitize_text_field($item['keterangan'] ?? ''),
            ];

            $inserted = $wpdb->insert("{$prefix}absensi_kegiatan", $insert_data);

            if ($inserted !== false) {
                $inserted_ids[] = $wpdb->insert_id;
            }
        }

        if (empty($inserted_ids)) {
            return new WP_Error('insert_failed', 'Tidak ada data absensi kegiatan yang berhasil disimpan.', ['status' => 500]);
        }

        return [
            'success' => true,
            'message' => count($inserted_ids) . ' data absensi kegiatan berhasil disimpan.',
            'inserted_ids' => $inserted_ids
        ];
    }

// Tambahkan di model.php (setelah fungsi yang sudah ada)

/**
 * Verify user login
 */
public static function verify_login($username, $password) {
    global $wpdb;
    $users_table = 'bubs_users';

    // Cari user by username
    $user = $wpdb->get_row($wpdb->prepare(
        "SELECT username, role, id_siswa, id_guru FROM {$users_table} WHERE username = %s", 
        $username
    ), ARRAY_A);

    if (!$user) {
        return new WP_Error('user_not_found', 'Username tidak ditemukan.');
    }

    // Verify password
    // if (password_verify($password, $user['password'])) {
    //     return new WP_Error('invalid_password', 'Password salah.');
    // }
    // if ($password === $user['password']) {
    //     return new WP_Error('invalid_password', 'Password salah.');
    // }
    
    // Get additional user data based on role
    if ($user['role'] === 'SISWA') {
        $siswa_data = $wpdb->get_row($wpdb->prepare(
            "SELECT s.nama_lengkap, k.nama_kelas, k.id as id_kelas 
             FROM bubs_siswa s 
             LEFT JOIN bubs_kelas k ON s.id_kelas = k.id 
             WHERE s.id = %d", 
            $user['id_siswa']
        ), ARRAY_A);
        
        $user['nama_lengkap'] = $siswa_data['nama_lengkap'] ?? '';
        $user['kelas'] = $siswa_data['nama_kelas'] ?? '';
        $user['id_kelas'] = $siswa_data['id_kelas'];
        
    } elseif ($user['role'] === 'GURU') {
        $guru_data = $wpdb->get_row($wpdb->prepare(
            "SELECT nama FROM bubs_guru WHERE id = %d", 
            $user['id_guru']
        ), ARRAY_A);
        
        $user['nama_lengkap'] = $guru_data['nama'] ?? '';
    }

    // Remove password from response
    unset($user['password']);

    return $user;
}

// Tambahkan di model.php

/**
 * Get presensi history for siswa
 */
public static function get_presensi_siswa($id_siswa, $bulan = null, $tahun = null) {
    global $wpdb;
    
    // Default to current month/year if not specified
    if (!$bulan) $bulan = date('m');
    if (!$tahun) $tahun = date('Y');
    
    $presensi_sekolah = self::get_presensi_sekolah_siswa($id_siswa, $bulan, $tahun);
    $presensi_kegiatan = self::get_presensi_kegiatan_siswa($id_siswa, $bulan, $tahun);
    
    return [
        'presensi_sekolah' => $presensi_sekolah,
        'presensi_kegiatan' => $presensi_kegiatan,
        'statistik' => self::get_statistik_presensi_siswa($id_siswa, $bulan, $tahun)
    ];
}

/**
 * Get presensi sekolah for siswa
 */
private static function get_presensi_sekolah_siswa($id_siswa, $bulan, $tahun) {
    global $wpdb;
    
    $query = $wpdb->prepare("
        SELECT 
            a.tanggal,
            a.status,
            a.keterangan,
            j.mata_pelajaran,
            j.hari,
            k.nama_kelas,
            g.nama as nama_guru
        FROM bubs_absensi_sekolah a
        INNER JOIN bubs_jadwal j ON a.id_jadwal = j.id
        INNER JOIN bubs_kelas k ON j.id_kelas = k.id
        INNER JOIN bubs_guru g ON j.id_guru = g.id
        WHERE a.id_siswa = %d
        AND MONTH(a.tanggal) = %d
        AND YEAR(a.tanggal) = %d
        ORDER BY a.tanggal DESC
    ", $id_siswa, $bulan, $tahun);
    
    return $wpdb->get_results($query, ARRAY_A);
}

/**
 * Get presensi kegiatan for siswa
 */
private static function get_presensi_kegiatan_siswa($id_siswa, $bulan, $tahun) {
    global $wpdb;
    
    $query = $wpdb->prepare("
        SELECT 
            a.tanggal,
            a.status,
            a.keterangan,
            k.nama_kegiatan,
            k.kategori,
            kl.nama_kelas,
            km.nama_kamar
        FROM bubs_absensi_kegiatan a
        INNER JOIN bubs_jenis_kegiatan k ON a.id_kegiatan = k.id
        LEFT JOIN bubs_kelas kl ON a.id_kelas = kl.id
        LEFT JOIN bubs_kamar km ON a.id_kamar = km.id
        WHERE a.id_siswa = %d
        AND MONTH(a.tanggal) = %d
        AND YEAR(a.tanggal) = %d
        ORDER BY a.tanggal DESC
    ", $id_siswa, $bulan, $tahun);
    
    return $wpdb->get_results($query, ARRAY_A);
}

/**
 * Get statistik presensi siswa
 */
private static function get_statistik_presensi_siswa($id_siswa, $bulan, $tahun) {
    global $wpdb;
    
    // Statistik presensi sekolah
    $query_sekolah = $wpdb->prepare("
        SELECT 
            status,
            COUNT(*) as jumlah
        FROM bubs_absensi_sekolah 
        WHERE id_siswa = %d
        AND MONTH(tanggal) = %d
        AND YEAR(tanggal) = %d
        GROUP BY status
    ", $id_siswa, $bulan, $tahun);
    
    $stat_sekolah = $wpdb->get_results($query_sekolah, ARRAY_A);
    
    // Statistik presensi kegiatan
    $query_kegiatan = $wpdb->prepare("
        SELECT 
            status,
            COUNT(*) as jumlah
        FROM bubs_absensi_kegiatan 
        WHERE id_siswa = %d
        AND MONTH(tanggal) = %d
        AND YEAR(tanggal) = %d
        GROUP BY status
    ", $id_siswa, $bulan, $tahun);
    
    $stat_kegiatan = $wpdb->get_results($query_kegiatan, ARRAY_A);
    
    return [
        'sekolah' => $stat_sekolah,
        'kegiatan' => $stat_kegiatan
    ];
}

/**
 * Get rekap presensi kelas untuk guru
 */
public static function get_rekap_presensi_kelas($id_kelas, $id_guru, $bulan = null, $tahun = null) {
    global $wpdb;
    
    if (!$bulan) $bulan = date('m');
    if (!$tahun) $tahun = date('Y');
    
    // Validasi bahwa guru memang mengajar kelas ini
    $validasi = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM bubs_jadwal 
        WHERE id_kelas = %d AND id_guru = %d
    ", $id_kelas, $id_guru));
    
    if (!$validasi) {
        return new WP_Error('unauthorized', 'Anda tidak mengajar kelas ini.');
    }
    
    $query = $wpdb->prepare("
        SELECT 
            s.id as id_siswa,
            s.nama_lengkap,
            COUNT(CASE WHEN a.status = 'Hadir' THEN 1 END) as hadir,
            COUNT(CASE WHEN a.status = 'Izin' THEN 1 END) as izin,
            COUNT(CASE WHEN a.status = 'Sakit' THEN 1 END) as sakit,
            COUNT(CASE WHEN a.status = 'Alpa' THEN 1 END) as alpa,
            COUNT(*) as total
        FROM bubs_siswa s
        LEFT JOIN bubs_absensi_sekolah a ON s.id = a.id_siswa 
            AND MONTH(a.tanggal) = %d 
            AND YEAR(a.tanggal) = %d
        LEFT JOIN bubs_jadwal j ON a.id_jadwal = j.id 
            AND j.id_guru = %d
        WHERE s.id_kelas = %d
        GROUP BY s.id, s.nama_lengkap
        ORDER BY s.nama_lengkap
    ", $bulan, $tahun, $id_guru, $id_kelas);
    
    return $wpdb->get_results($query, ARRAY_A);
}

// Tambahkan di model.php

public static function get_nama_kelas($id_kelas){
    global $wpdb;

    $data = $wpdb->get_row($wpdb->prepare("
        SELECT nama_kelas FROM bubs_kelas WHERE id = %d
    ", $id_kelas), ARRAY_A);

    return $data;
}
/**
 * Get rekap presensi kelas untuk guru dengan detail
 */
public static function get_rekap_presensi_kelas_detailed($id_kelas, $id_guru, $bulan = null, $tahun = null, $mapel = null) {
    global $wpdb;

    if (!$bulan) $bulan = date('m');
    if (!$tahun) $tahun = date('Y');

    // Validasi bahwa guru memang mengajar kelas ini
    $validasi = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM bubs_jadwal 
        WHERE id_kelas = %d AND id_guru = %d
        AND (%s IS NULL OR mata_pelajaran = %s)
    ", $id_kelas, $id_guru, $mapel, $mapel));

    if (!$validasi) {
        return new WP_Error('unauthorized', 'Anda tidak mengajar kelas ini atau mata pelajaran tidak ditemukan.');
    }
    
    $mapel_condition = $mapel ? "AND j.mata_pelajaran = '" . esc_sql($mapel) . "'" : "";
    
    $query = $wpdb->prepare("
        SELECT 
            s.id as id_siswa,
            s.nama_lengkap,
            s.nik,
            COUNT(CASE WHEN a.status = 'Hadir' THEN 1 END) as hadir,
            COUNT(CASE WHEN a.status = 'Izin' THEN 1 END) as izin,
            COUNT(CASE WHEN a.status = 'Sakit' THEN 1 END) as sakit,
            COUNT(CASE WHEN a.status = 'Alpa' THEN 1 END) as alpa,
            COUNT(*) as total_pertemuan,
            j.mata_pelajaran,
            k.nama_kelas
        FROM bubs_siswa s
        LEFT JOIN bubs_absensi_sekolah a ON s.id = a.id_siswa 
            AND MONTH(a.tanggal) = %d 
            AND YEAR(a.tanggal) = %d
        LEFT JOIN bubs_jadwal j ON a.id_jadwal = j.id 
            AND j.id_guru = %d
            {$mapel_condition}
        LEFT JOIN bubs_kelas k ON s.id_kelas = k.id
        WHERE s.id_kelas = %d
        GROUP BY s.id, s.nama_lengkap, s.nik, j.mata_pelajaran, k.nama_kelas
        ORDER BY s.nama_lengkap
    ", $bulan, $tahun, $id_guru, $id_kelas);
    
    $results = $wpdb->get_results($query, ARRAY_A);
    
    // Calculate percentages
    foreach ($results as &$row) {
        $total = $row['total_pertemuan'] ?: 1; // Avoid division by zero
        $row['presentase'] = round(($row['hadir'] / $total) * 100, 1);
    }
    
    return $results;
}

/**
 * Get mata pelajaran yang diajar guru di kelas tertentu
 */
public static function get_mata_pelajaran_guru($id_guru, $id_kelas) {
    global $wpdb;
    
    $query = $wpdb->prepare("
        SELECT DISTINCT mata_pelajaran 
        FROM bubs_jadwal 
        WHERE id_guru = %d AND id_kelas = %d
        ORDER BY mata_pelajaran
    ", $id_guru, $id_kelas);
    
    return $wpdb->get_results($query, ARRAY_A);
}

/**
 * Get kelas yang diajar oleh guru
 */
// public static function get_kelas_guru($id_guru) {
//     global $wpdb;
    
//     $query = $wpdb->prepare("
//         SELECT DISTINCT 
//             k.id,
//             k.nama_kelas
//         FROM bubs_jadwal j
//         INNER JOIN bubs_kelas k ON j.id_kelas = k.id
//         WHERE j.id_guru = %d
//         ORDER BY k.nama_kelas
//     ", $id_guru);
    
//     return $wpdb->get_results($query, ARRAY_A);
// }


    // Model function untuk mengambil jadwal by guru ID
    public static function bubs_get_jadwal_by_guru_id($guru_id) {
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT 
                g.nama AS nama_guru,
                k.nama_kelas,
                j.mata_pelajaran,
                j.hari
            FROM bubs_jadwal AS j
            JOIN bubs_guru AS g ON g.id = j.id_guru
            JOIN bubs_kelas AS k ON k.id = j.id_kelas
            WHERE g.id = %d
            ORDER BY j.hari
        ", $guru_id);

        $results = $wpdb->get_results($query, ARRAY_A);
        
        return $results;
    }


}