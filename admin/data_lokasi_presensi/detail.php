<?php 
session_start();

if(!isset($_SESSION['login'])){
  header('Location: ../../auth/login.php?pesan=belum_login');
}
else if($_SESSION['role'] != 'Admin'){
  header('Location: ../../auth/login.php?pesan=tolak_akses');
}

$judul = 'Detail Lokasi Presensi';

include('../layout/header.php');
require_once('../../config.php');

$id = $_GET['id'];
$result = mysqli_query($connection, "SELECT * FROM lokasi_presensi WHERE id = '$id'");

while($lokasi = mysqli_fetch_array($result)){
  $nama_lokasi = $lokasi['nama_lokasi'];
  $alamat_lokasi = $lokasi['alamat_lokasi'];
  $tipe_lokasi = $lokasi['tipe_lokasi'];
  $latitude = $lokasi['latitude'];
  $longitude = $lokasi['longitude'];
  $radius = $lokasi['radius'];
  $zona_waktu = $lokasi['zona_waktu'];
  $jam_masuk = $lokasi['jam_masuk'];
  $jam_pulang = $lokasi['jam_pulang'];
}
?>

<!-- Page body -->
<div class="page-body">
  <div class="container-xl">

    <div class="row">
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <table class="table">
              <tr>
                <td>Nama Lokasi</td>
                <td><?= $nama_lokasi; ?></td>
              </tr>
              <tr>
                <td>Alamat Lokasi</td>
                <td><?= $alamat_lokasi; ?></td>
              </tr>
              <tr>
                <td>Tipe Lokasi</td>
                <td><?= $tipe_lokasi; ?></td>
              </tr>
              <tr>
                <td>Latitude</td>
                <td><?= $latitude; ?></td>
              </tr>
              <tr>
                <td>Longitude</td>
                <td><?= $longitude; ?></td>
              </tr>
              <tr>
                <td>Radius (meter)</td>
                <td><?= $radius; ?></td>
              </tr>
              <tr>
                <td>Zona Waktu</td>
                <td><?= $zona_waktu; ?></td>
              </tr>
              <tr>
                <td>Jam Masuk</td>
                <td><?= $jam_masuk; ?></td>
              </tr>
              <tr>
                <td>Jam Pulang</td>
                <td><?= $jam_pulang; ?></td>
              </tr>
            </table>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <iframe src="https://www.google.com/maps/embed?pb=!1m17!1m12!1m3!1d249.34686240938314!2d<?= $longitude ?>!3d<?= $latitude ?>!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m2!1m1!2s!5e0!3m2!1sen!2sid!4v1728989728890!5m2!1sen!2sid" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
          </div>
        </div>
      </div>
    </div>


  </div>
</div>


<?php include('../layout/footer.php'); ?>