<?php
ob_start();
session_start();
include_once('../../config.php');

// Set waktu ke Jakarta biar jam server bener
date_default_timezone_set('Asia/Jakarta');
$lokasi_user = $_SESSION['lokasi_presensi'];

// --- PERBAIKAN 1: Ambil jam_masuk DAN toleransi dari database ---
$query_lokasi = mysqli_query($connection, "SELECT jam_masuk, toleransi FROM lokasi_presensi WHERE nama_lokasi = '$lokasi_user'");
$data_lokasi = mysqli_fetch_array($query_lokasi);

$jam_buka_db = $data_lokasi['jam_masuk'];
$toleransi   = $data_lokasi['toleransi']; // Ambil nilai toleransi dinamis

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

$id_trainee = (int)($_POST['id'] ?? 0); 
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

/* 1) WAKTU DARI SERVER & LOGIKA TERLAMBAT (Dinamis sesuai Database) */
$tanggal_masuk = date('Y-m-d');
$jam_masuk_skrg = date('H:i:s'); 

// --- PERBAIKAN 2: Batas telat dihitung berdasarkan variabel $toleransi ---
$batas_terlambat = date('H:i:s', strtotime($jam_buka_db . " +$toleransi minutes"));

// Tentukan status secara otomatis
if ($jam_masuk_skrg > $batas_terlambat) {
    $status_final = "Terlambat";
} else {
    $status_final = "Hadir";
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

$file = 'masuk_' . $id_trainee . '_' . date('Ymd_His') . '.png';
$nama_file = 'foto/' . $file;

if (!file_put_contents($nama_file, $data)) {
    $_SESSION['gagal'] = 'Gagal menyimpan foto masuk.';
    header('Location: presensi_masuk.php');
    exit;
}

/* 4) INSERT PRESENSI */
$result = mysqli_query($connection, "
    INSERT INTO presensi (id_trainee, tanggal_masuk, jam_masuk, foto_masuk, status)
    VALUES ($id_trainee, '$tanggal_masuk', '$jam_masuk_skrg', '$file', '$status_final')
");

if ($result) {
    $_SESSION['berhasil'] = 'Presensi masuk berhasil! Status: ' . $status_final;
} else {
    $_SESSION['gagal'] = 'Presensi masuk gagal! ' . mysqli_error($connection);
}

header('Location: ../home/home.php');
exit;
?>
