<?php
ob_start();
session_start();

if (!isset($_SESSION['login'])) {
  header('Location: ../../auth/login.php?pesan=belum_login');
  exit;
}
else if (!in_array($_SESSION['role'], ['trainee','Trainee'])) {
  header('Location: ../../auth/login.php?pesan=tolak_akses');
  exit;
}

include_once('../../config.php');

$id_trainee = (int)($_SESSION['id'] ?? 0);
$file_foto  = $_POST['photo'] ?? '';

if ($id_trainee <= 0) {
  $_SESSION['gagal'] = 'Session trainee tidak valid.';
  header('Location: presensi_keluar.php');
  exit;
}

if (!$file_foto) {
  $_SESSION['gagal'] = 'Foto belum ada. Silakan ambil foto lagi.';
  header('Location: presensi_keluar.php');
  exit;
}

/* 1) WAKTU KELUAR DARI SERVER (ANTI GANTI JAM HP) */
$tanggal_keluar = date('Y-m-d');
$jam_keluar     = date('H:i:s');

/* 2) CEK ADA PRESENSI MASUK HARI INI (YANG BELUM KELUAR) */
$cek = mysqli_query($connection, "
  SELECT id
  FROM presensi
  WHERE id_trainee = $id_trainee
    AND tanggal_masuk = '$tanggal_keluar'
    AND (jam_keluar IS NULL OR jam_keluar = '00:00:00')
  ORDER BY id DESC
  LIMIT 1
");

$row = $cek ? mysqli_fetch_assoc($cek) : null;
if (!$row) {
  $_SESSION['gagal'] = 'Tidak ada presensi masuk hari ini yang bisa dipresensi pulang.';
  header('Location: ../home/home.php');
  exit;
}

$id_presensi = (int)$row['id'];

/* 3) SIMPAN FOTO BASE64 */
$foto = $file_foto;
$foto = preg_replace('#^data:image/\w+;base64,#i', '', $foto);
$foto = str_replace(' ', '+', $foto);

$data = base64_decode($foto, true);
if ($data === false) {
  $_SESSION['gagal'] = 'Format foto tidak valid.';
  header('Location: presensi_keluar.php');
  exit;
}

if (!is_dir('foto')) {
  mkdir('foto', 0777, true);
}

$file = 'keluar_' . date('Y-m-d_H-i-s') . '_' . substr((string)microtime(true), -6) . '.png';
$nama_file = 'foto/' . $file;

if (!file_put_contents($nama_file, $data)) {
  $_SESSION['gagal'] = 'Gagal menyimpan foto pulang.';
  header('Location: presensi_keluar.php');
  exit;
}

/* 4) UPDATE PRESENSI (SERVER TIME) */
$result = mysqli_query($connection, "
  UPDATE presensi SET
    tanggal_keluar = '$tanggal_keluar',
    jam_keluar     = '$jam_keluar',
    foto_keluar    = '$file'
  WHERE id = $id_presensi
  LIMIT 1
");

if ($result) {
  $_SESSION['berhasil'] = 'Presensi pulang berhasil!';
} else {
  $_SESSION['gagal'] = 'Presensi pulang gagal! ' . mysqli_error($connection);
}

header('Location: ../home/home.php');
exit;
?>