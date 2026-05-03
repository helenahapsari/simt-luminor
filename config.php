<?php

// Paksa timezone PHP ke WIB
date_default_timezone_set('Asia/Jakarta');

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'simt-luminor';

$connection = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$connection) {
  die('Koneksi ke database gagal! ' . mysqli_connect_error());
}

// Paksa timezone MySQL ke WIB (buat NOW(), CURDATE(), CURTIME() konsisten)
mysqli_query($connection, "SET time_zone = '+07:00'");

// Charset aman
mysqli_set_charset($connection, "utf8mb4");

function base_url($url = null){
  $is_https = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
  );

  $protocol = $is_https ? "https://" : "http://";
  $host = $_SERVER['HTTP_HOST'];

  $base = rtrim($protocol . $host, '/') . '/simt-luminor';

  if ($url) {
    return $base . '/' . ltrim($url, '/');
  }

  return $base . '/';
}

// ==========================================================
// --- BAGIAN TAMBAHAN OTOMATIS (TIDAK MERUBAH CODE LAMA) ---
// ==========================================================

/**
 * Fungsi ini bertugas menulis pesan ke file aktivitas.log
 */
function catat_ke_terminal($pesan) {
    $nama_file = __DIR__ . "/aktivitas.log";
    $waktu = date('H:i:s');
    $isi_log = "[" . $waktu . "] " . $pesan . PHP_EOL;
    file_put_contents($nama_file, $isi_log, FILE_APPEND);
}

/**
 * OTOMATISASI 1: Lapor setiap kali halaman diakses
 */
$halaman_saat_ini = $_SERVER['REQUEST_URI'];
catat_ke_terminal("PENGUNJUNG: Sedang membuka " . $halaman_saat_ini);

/**
 * OTOMATISASI 2: Lapor jika ada pengiriman data (Login/Simpan/Hapus)
 * Ini akan otomatis mendeteksi jika ada tombol yang diklik (metode POST)
 */
if (!empty($_POST)) {
    catat_ke_terminal("AKSI: Seseorang sedang mengirim/input data (POST)!");
}