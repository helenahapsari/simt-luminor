<?php 
ob_start();
session_start();

if(!isset($_SESSION['login'])){
  header('Location: ../../auth/login.php?pesan=belum_login');
}
else if(!in_array($_SESSION['role'], ['trainee','Trainee'])){
  header('Location: ../../auth/login.php?pesan=tolak_akses');
}

$judul = 'Profile';

include('../layout/header.php');
require_once('../../config.php');

$id = $_SESSION['id'];
$result = mysqli_query($connection, "SELECT trainee.*, users.id_trainee, users.username, users.status, users.role FROM users JOIN trainee ON trainee.id = users.id_trainee WHERE trainee.id = '$id'");
// $trainee = mysqli_fetch_array($result);
// echo "<pre>";
// print_r($trainee);
// echo "</pre>";

while($trainee = mysqli_fetch_array($result)){
  $foto = $trainee['foto'];
  $nama = $trainee['nama'];
  $jenis_kelamin = $trainee['jenis_kelamin'];
  $alamat = $trainee['alamat'];
  $no_handphone = $trainee['no_handphone'];
  $divisi = $trainee['nama_divisi'];
  $username = $trainee['username'];
  $role = $trainee['role'];
  $lokasi_presensi = $trainee['lokasi_presensi'];
  $status = $trainee['status'];
}
?>

<!-- Page body -->
<div class="page-body">
  <div class="container-xl">

    <div class="row justify-content-md-center">
      <div class="col-md-4">
        <div class="card">
          <div class="card-body">
            <center>
              <img style="border-radius: 100%; width: 50%" src="<?= base_url('assets/img/foto_user/'.$foto) ?>" alt="">
            </center>
            <table class="table mt-3">
              <tr>
                <td>Nama</td>
                <td>: <?= $nama; ?></td>
              </tr>
              <tr>
                <td>Jenis Kelamin</td>
                <td>: <?= $jenis_kelamin; ?></td>
              </tr>
              <tr>
                <td>Alamat</td>
                <td>: <?= $alamat; ?></td>
              </tr>
              <tr>
                <td>No. Handphone</td>
                <td>: <?= $no_handphone; ?></td>
              </tr>
              <tr>
                <td>Divisi</td>
                <td>: <?= $divisi; ?></td>
              </tr>
              <tr>
                <td>Username</td>
                <td>: <?= $username; ?></td>
              </tr>
              <tr>
                <td>Role</td>
                <td>: <?= $role; ?></td>
              </tr>
              <tr>
                <td>Lokasi Presensi</td>
                <td>: <?= $lokasi_presensi; ?></td>
              </tr>
              <tr>
                <td>Status</td>
                <td>: <?= $status; ?></td>
              </tr>

            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<?php include('../layout/footer.php'); ?>