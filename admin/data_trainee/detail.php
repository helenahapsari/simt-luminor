<?php 
session_start();

if(!isset($_SESSION['login'])){
  header('Location: ../../auth/login.php?pesan=belum_login');
  exit;
}
else if($_SESSION['role'] != 'Admin'){
  header('Location: ../../auth/login.php?pesan=tolak_akses');
  exit;
}

$judul = 'Detail Data Trainee';
include('../layout/header.php');
require_once('../../config.php');

$id = $_GET['id'];

// --- 1. AMBIL DATA PROFIL ---
$result = mysqli_query($connection, "SELECT users.id_trainee, users.username, users.password, users.status, users.role, trainee.* FROM users JOIN trainee ON trainee.id = users.id_trainee WHERE trainee.id = $id");

while($trainee = mysqli_fetch_array($result)){
  $id_trainee      = $trainee['id_trainee']; // Penting untuk query statistik
  $nama            = $trainee['nama'];
  $jenis_kelamin   = $trainee['jenis_kelamin'];
  $alamat          = $trainee['alamat'];
  $no_handphone    = $trainee['no_handphone'];
  $divisi          = $trainee['nama_divisi'];
  $username        = $trainee['username'];
  $status          = $trainee['status'];
  $lokasi_presensi = $trainee['lokasi_presensi'];
  $role            = $trainee['role'];
  $foto            = $trainee['foto'];
}

// --- 2. LOGIKA HITUNG STATISTIK (Sesuai Request Lo) ---

// 1. HITUNG HADIR (Tetap sama)
$q_hadir = mysqli_query($connection, "SELECT COUNT(*) as total FROM presensi WHERE id_trainee = '$id_trainee' AND jam_keluar IS NOT NULL AND jam_keluar != '' AND jam_keluar != '-'");
$total_hadir = mysqli_fetch_assoc($q_hadir)['total'];

// 2. HITUNG TERLAMBAT (Tetap sama)
$q_telat = mysqli_query($connection, "SELECT COUNT(*) as total FROM presensi WHERE id_trainee = '$id_trainee' AND status LIKE '%Terlambat%' AND jam_keluar IS NOT NULL AND jam_keluar != '' AND jam_keluar != '-'");
$total_telat = mysqli_fetch_assoc($q_telat)['total'];

// 3. HITUNG ALPA (Logika Baru: Yang Lupa Pulang + Yang Izinnya Belum/Gak di-ACC)

// Hitung baris presensi yang jam pulangnya '-' (Lupa Pulang)
$q_lupa = mysqli_query($connection, "SELECT COUNT(*) as total FROM presensi WHERE id_trainee = '$id_trainee' AND (jam_keluar IS NULL OR jam_keluar = '' OR jam_keluar = '-')");
$total_lupa = mysqli_fetch_assoc($q_lupa)['total'];

// Hitung izin yang statusnya 'Pending' atau 'Rejected'
$q_izin_gagal = mysqli_query($connection, "SELECT COUNT(*) as total FROM ketidakhadiran WHERE id_trainee = '$id_trainee' AND (status_pengajuan = 'Pending' OR status_pengajuan = 'Rejected')");
$total_gagal = mysqli_fetch_assoc($q_izin_gagal)['total'] ?? 0;

// Gabungkan jadi Total Alpa
$total_alpa = $total_lupa + $total_gagal;
?>


<div class="page-body">
  <div class="container-xl">
    <div class="row">
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <table class="table">
              <tr><td>Nama</td><td>: <?= $nama; ?></td></tr>
              <tr><td>Jenis Kelamin</td><td>: <?= $jenis_kelamin; ?></td></tr>
              <tr><td>Alamat</td><td>: <?= $alamat; ?></td></tr>
              <tr><td>No. Handphone</td><td>: <?= $no_handphone; ?></td></tr>
              <tr><td>Divisi</td><td>: <?= $divisi; ?></td></tr>
              <tr><td>Username</td><td>: <?= $username; ?></td></tr>
              <tr><td>Role</td><td>: <?= (ucfirst($role)); ?></td></tr>
              <tr><td>Lokasi Presensi</td><td>: <?= $lokasi_presensi; ?></td></tr>
              <tr><td>Status</td><td>: <?= $status; ?></td></tr>
            </table>
          </div>
        </div>
      </div>

      <div class="col-md-6 text-center">
        <img style="width:300px; border-radius:15px; border: 5px solid #fff; box-shadow: 0 4px 8px rgba(0,0,0,0.1);" src="<?= base_url('assets/img/foto_user/'.$foto) ?>" alt="Foto Trainee">
      </div>
    </div>

    <h3 class="mt-4 mb-3 font-weight-bold">Statistik Presensi</h3>
    <div class="row">
      <div class="col-md-4 mb-3">
        <div class="card bg-success-lt shadow-sm py-2" style="border-left: 5px solid #28a745;">
          <div class="card-body">
            <div class="text-uppercase text-muted font-weight-bold mb-1" style="font-size: 11px;">Total Hadir</div>
            <div class="h2 mb-0 font-weight-bold text-success"><?= $total_hadir; ?> <small>Hari</small></div>
          </div>
        </div>
      </div>

      <div class="col-md-4 mb-3">
        <div class="card bg-warning-lt shadow-sm py-2" style="border-left: 5px solid #ffc107;">
          <div class="card-body">
            <div class="text-uppercase text-muted font-weight-bold mb-1" style="font-size: 11px;">Total Terlambat</div>
            <div class="h2 mb-0 font-weight-bold text-warning"><?= $total_telat; ?> <small>Hari</small></div>
          </div>
        </div>
      </div>

      <div class="col-md-4 mb-3">
        <div class="card bg-danger-lt shadow-sm py-2" style="border-left: 5px solid #dc3545;">
          <div class="card-body">
            <div class="text-uppercase text-muted font-weight-bold mb-1" style="font-size: 11px;">Total Alpa</div>
            <div class="h2 mb-0 font-weight-bold text-danger"><?= $total_alpa; ?> <small>Hari</small></div>
          </div>
        </div>
      </div>
    </div>
    
  </div>
</div>

<?php include('../layout/footer.php'); ?>
