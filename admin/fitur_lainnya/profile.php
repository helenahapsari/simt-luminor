<?php 
ob_start();
session_start();

if(!isset($_SESSION['login'])){
  header('Location: ../../auth/login.php?pesan=belum_login');
  exit;
}
else if($_SESSION['role'] != 'Admin'){
  header('Location: ../../auth/login.php?pesan=tolak_akses');
  exit;
}

$judul = 'Profile';

include('../layout/header.php');
require_once('../../config.php');

$id_user = $_SESSION['id'];

// 1. Ambil data asli dari tabel users bawaan lu
$query_user = mysqli_query($connection, "SELECT * FROM users WHERE id = '$id_user'");
$user_data = mysqli_fetch_assoc($query_user);

if ($user_data) {
  $username = $user_data['username'];
  $role     = $user_data['role'];
  $status   = $user_data['status'];
  
  // 2. Cek apakah dia Trainee atau Admin
  if (!empty($user_data['id_trainee'])) {
    // JIKA TRAINEE: Ambil data lengkap dari tabel trainee
    $id_trainee = $user_data['id_trainee'];
    $query_trainee = mysqli_query($connection, "SELECT * FROM trainee WHERE id = '$id_trainee'");
    $trainee = mysqli_fetch_assoc($query_trainee);

    $nama            = $trainee['nama'];
    $jenis_kelamin   = $trainee['jenis_kelamin'];
    $alamat          = $trainee['alamat'];
    $no_handphone    = $trainee['no_handphone'];
    $divisi          = $trainee['nama_divisi'];
    $lokasi_presensi = $trainee['lokasi_presensi'];
  }
} else {
  echo "<script>alert('Sesi user tidak valid, silakan login ulang.'); window.location.href='../../auth/login.php';</script>";
  exit;
}
?>

<div class="page-body">
  <div class="container-xl">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-vcenter card-table text-left">
                
                <?php if ($role == 'Admin') : ?>
                  <tr>
                    <td width="30%">Username</td>
                    <td width="5%">:</td>
                    <td><strong><?= $username; ?></strong></td>
                  </tr>
                  <tr>
                    <td>Role Pengguna</td>
                    <td>:</td>
                    <td><span class="badge bg-blue text-blue-fg"><?= $role; ?></span></td>
                  </tr>
                  <tr>
                    <td>Status</td>
                    <td>:</td>
                    <td><span class="badge bg-green text-green-fg"><?= $status; ?></span></td>
                  </tr>

                <?php else : ?>
                  <tr>
                    <td width="30%">Nama Lengkap</td>
                    <td width="5%">:</td>
                    <td><strong><?= $nama; ?></strong></td>
                  </tr>
                  <tr>
                    <td>Jenis Kelamin</td>
                    <td>:</td>
                    <td><?= $jenis_kelamin; ?></td>
                  </tr>
                  <tr>
                    <td>Alamat</td>
                    <td>:</td>
                    <td><?= $alamat; ?></td>
                  </tr>
                  <tr>
                    <td>No. Handphone</td>
                    <td>:</td>
                    <td><?= $no_handphone; ?></td>
                  </tr>
                  <tr>
                    <td>Divisi Magang</td>
                    <td>:</td>
                    <td><?= $divisi; ?></td>
                  </tr>
                  <tr>
                    <td>Lokasi Presensi</td>
                    <td>:</td>
                    <td><?= $lokasi_presensi; ?></td>
                  </tr>
                <?php endif; ?>

              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include('../layout/footer.php'); ?>
