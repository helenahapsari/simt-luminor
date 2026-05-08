<?php
ob_start();
session_start();
include_once('../../config.php');

// Set waktu ke Jakarta biar jam server bener
date_default_timezone_set('Asia/Jakarta');
$lokasi_user = $_SESSION['lokasi_presensi'];

// Ambil jam masuk yang disetting HR di database
$query_lokasi = mysqli_query($connection, "SELECT jam_masuk FROM lokasi_presensi WHERE nama_lokasi = '$lokasi_user'");
$data_lokasi = mysqli_fetch_array($query_lokasi);
$jam_buka_db = $data_lokasi['jam_masuk'];

// Proteksi: Gak boleh absen sebelum jam buka (Server Side Check)
if (date('H:i:s') < $jam_buka_db) {
    $_SESSION['gagal'] = 'Akses ditolak! Belum waktunya presensi masuk.';
    header('Location: ../home/home.php');
    exit;
}

if (!isset($_SESSION['login'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$id_trainee = (int)($_POST['id'] ?? 0); // Ambil ID dari POST yang dikirim AJAX
$file_foto  = $_POST['photo'] ?? '';

if ($id_trainee <= 0) {
    $_SESSION['gagal'] = 'ID trainee tidak valid.';
    header('Location: ../home/home.php');
    exit;
}

if (!$file_foto) {
    $_SESSION['gagal'] = 'Foto belum ada. Silakan ambil foto lagi.';
    header('Location: presensi_masuk.php');
    exit;
}

/* 1) WAKTU DARI SERVER & LOGIKA DISIPLIN PERMANEN (+40 Menit) */
$tanggal_masuk = date('Y-m-d');
$jam_masuk_skrg = date('H:i:s'); 

// Menggunakan toleransi 40 Menit sesuai kesepakatan agar sinkron dengan file lain
$batas_disiplin = date('H:i:s', strtotime($jam_buka_db . ' +40 minutes'));

// Tentukan status untuk kolom 'status' dan kolom baru 'status_disiplin'
if ($jam_masuk_skrg > $batas_disiplin) {
    $status_final = "Terlambat";
    $status_disiplin_final = "Terlambat";
} else {
    $status_final = "Hadir";
    $status_disiplin_final = "Tepat Waktu";
}

/* 2) CEGAH DOUBLE PRESENSI */
$cek = mysqli_query($connection, "
    SELECT id 
    FROM presensi 
    WHERE id_trainee = $id_trainee 
    AND tanggal_masuk = '$tanggal_masuk' 
    LIMIT 1
");

if ($cek && mysqli_num_rows($cek) > 0) {
    $_SESSION['gagal'] = 'Kamu sudah presensi masuk hari ini.';
    header('Location: ../home/home.php');
    exit;
}

/* 3) SIMPAN FOTO */
$foto = $file_foto;
$foto = preg_replace('#^data:image/\w+;base64,#i', '', $foto);
$foto = str_replace(' ', '+', $foto);
$data = base64_decode($foto, true);

if (!is_dir('foto')) {
    mkdir('foto', 0777, true);
}

// Penamaan file lebih rapi
$file = 'masuk_' . $id_trainee . '_' . date('Ymd_His') . '.png';
$nama_file = 'foto/' . $file;

if (!file_put_contents($nama_file, $data)) {
    $_SESSION['gagal'] = 'Gagal menyimpan foto masuk.';
    header('Location: presensi_masuk.php');
    exit;
}

/* 4) INSERT PRESENSI (SUDAH TERMASUK KOLOM status_disiplin) */
$result = mysqli_query($connection, "
    INSERT INTO presensi (id_trainee, tanggal_masuk, jam_masuk, foto_masuk, status, status_disiplin)
    VALUES ($id_trainee, '$tanggal_masuk', '$jam_masuk_skrg', '$file', '$status_final', '$status_disiplin_final')
");

if ($result) {
    $_SESSION['berhasil'] = 'Presensi masuk berhasil! Status: ' . $status_final;
} else {
    $_SESSION['gagal'] = 'Presensi masuk gagal! ' . mysqli_error($connection);
}

header('Location: ../home/home.php');
exit;
?>
