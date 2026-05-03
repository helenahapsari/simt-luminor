<?php 
session_start();
ob_start();

if(!isset($_SESSION['login'])){
  header('Location: ../../auth/login.php?pesan=belum_login');
}
else if($_SESSION['role'] != 'Admin'){
  header('Location: ../../auth/login.php?pesan=tolak_akses');
}

$judul = 'Tambah Lokasi Presensi';

include('../layout/header.php');
require_once('../../config.php');

if(isset($_POST['submit'])){

  // echo "<pre>";
  // print_r($_POST);
  // echo "</pre>";
  // die;

  $nama_lokasi = htmlspecialchars($_POST['nama_lokasi']);
  $alamat_lokasi = htmlspecialchars($_POST['alamat_lokasi']);
  $tipe_lokasi = htmlspecialchars($_POST['tipe_lokasi']);
  $latitude = htmlspecialchars($_POST['latitude']);
  $longitude = htmlspecialchars($_POST['longitude']);
  $radius = htmlspecialchars($_POST['radius']);
  $zona_waktu = htmlspecialchars($_POST['zona_waktu']);
  $jam_masuk = htmlspecialchars($_POST['jam_masuk']);
  $jam_pulang = htmlspecialchars($_POST['jam_pulang']);

  if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(empty($nama_lokasi)){
      $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Nama lokasi wajib diisi!";
    }
    if(empty($alamat_lokasi)){
      $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Alamat lokasi wajib diisi!";
    }
    if(empty($tipe_lokasi)){
      $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Tipe lokasi wajib diisi!";
    }
    if(empty($latitude)){
      $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Latitude wajib diisi!";
    }
    if(empty($longitude)){
      $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Longitude wajib diisi!";
    }
    if(empty($radius)){
      $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Radius wajib diisi!";
    }
    if(empty($zona_waktu)){
      $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Zona waktu wajib diisi!";
    }
    if(empty($jam_masuk)){
      $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Jam masuk wajib diisi!";
    }
    if(empty($jam_pulang)){
      $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Jam pulang wajib diisi!";
    }

    if(!empty($pesan_kesalahan)){
      $_SESSION['validasi'] = implode('<br>',$pesan_kesalahan);
    }
    else{
      $result = mysqli_query($connection, "INSERT INTO lokasi_presensi(nama_lokasi, alamat_lokasi, tipe_lokasi, latitude, longitude, radius, zona_waktu, jam_masuk, jam_pulang) VALUES('$nama_lokasi', '$alamat_lokasi', '$tipe_lokasi', '$latitude', '$longitude', '$radius', '$zona_waktu', '$jam_masuk', '$jam_pulang')");
    
      $_SESSION['berhasil'] = 'Data berhasil disimpan!';
      header('Location: lokasi_presensi.php');
      exit;
    }
  }

}

?>

<!-- Page body -->
<div class="page-body">
  <div class="container-xl">

  <div class="card col-md-6">
    <div class="card-body">
      <form action="<?= base_url('admin/data_lokasi_presensi/tambah.php') ?>" method="POST">
        <div class="mb-3">
          <label for="">Nama Lokasi</label>
          <input type="text" class="form-control" name="nama_lokasi" value="<?= isset($_POST['nama_lokasi']) ? $_POST['nama_lokasi'] : '' ?>">
        </div>
        <div class="mb-3">
          <label for="">Alamat Lokasi</label>
          <input type="text" class="form-control" name="alamat_lokasi" value="<?= isset($_POST['alamat_lokasi']) ? $_POST['alamat_lokasi'] : '' ?>">
        </div>
        <div class="mb-3">
          <label for="">Tipe Lokasi</label>
          <select name="tipe_lokasi" id="" class="form-control">
            <option value="">-- Pilih Tipe Lokasi --</option>
            <option <?= (isset($_POST['tipe_lokasi']) && $_POST['tipe_lokasi'] == 'Pusat') ? 'selected' : '' ?> value="Pusat">Pusat</option>
            <option <?= (isset($_POST['tipe_lokasi']) && $_POST['tipe_lokasi'] == 'Cabang') ? 'selected' : '' ?> value="Cabang">Cabang</option>
          </select>
        </div>
        <div class="mb-3">
          <label for="">Latitude</label>
          <input type="text" class="form-control" name="latitude" value="<?= isset($_POST['latitude']) ? $_POST['latitude'] : '' ?>">
        </div>
        <div class="mb-3">
          <label for="">Longitude</label>
          <input type="text" class="form-control" name="longitude" value="<?= isset($_POST['longitude']) ? $_POST['longitude'] : '' ?>">
        </div>
        <div class="mb-3">
          <label for="">Radius</label>
          <input type="text" class="form-control" name="radius" value="<?= isset($_POST['radius']) ? $_POST['radius'] : '' ?>">
        </div>
        <div class="mb-3">
          <label for="">Zona Waktu</label>
          <select name="zona_waktu" id="" class="form-control">
            <option value="">-- Pilih Zona Waktu --</option>
            <option <?= (isset($_POST['zona_waktu']) && $_POST['zona_waktu'] == 'WIB') ? 'selected' : '' ?> value="WIB">WIB</option>
            <option <?= (isset($_POST['zona_waktu']) && $_POST['zona_waktu'] == 'WITA') ? 'selected' : '' ?>  value="WITA">WITA</option>
            <option <?= (isset($_POST['zona_waktu']) && $_POST['zona_waktu'] == 'WIT') ? 'selected' : '' ?>  value="WIT">WIT</option>
          </select>
        </div>
        <div class="mb-3">
          <label for="">Jam Masuk</label>
          <input type="time" class="form-control" name="jam_masuk" value="<?= isset($_POST['jam_masuk']) ? $_POST['jam_masuk'] : '' ?>">
        </div>
        <div class="mb-3">
          <label for="">Jam Pulang</label>
          <input type="time" class="form-control" name="jam_pulang" value="<?= isset($_POST['jam_pulang']) ? $_POST['jam_pulang'] : '' ?>">
        </div>

        <button type="submit" class="btn btn-primary" name="submit">Simpan</button>

      </form>
    </div>
  </div>


  </div>
</div>


<?php include('../layout/footer.php'); ?>
