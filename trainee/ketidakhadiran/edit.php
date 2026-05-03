<?php
ob_start();
session_start();

if(!isset($_SESSION['login'])){
  header('Location: ../../auth/login.php?pesan=belum_login');
}
else if(!in_array($_SESSION['role'], ['trainee','Trainee'])){
  header('Location: ../../auth/login.php?pesan=tolak_akses');
}

$judul = 'Edit Pengajuan Ketidakhadiran';
include('../layout/header.php');  

if(isset($_POST['update'])){
  $id = $_POST['id'];
  $keterangan = $_POST['keterangan'];
  $tanggal = $_POST['tanggal'];
  $deskripsi = $_POST['deskripsi'];

  if($_FILES['file_baru']['error'] === 4){
    $file_lama = $_POST['file_lama'];
  }
  else{
    $file = $_FILES['file_baru'];
    $nama_file = $file['name'];
    $file_tmp = $file['tmp_name'];
    $ukuran_file = $file['size'];
    $file_dir = '../../assets/file_ketidakhadiran/'.$nama_file;

    $ambil_ext = pathinfo($nama_file, PATHINFO_EXTENSION);
    $ext_diizinkan = ['jpg', 'png', 'jpeg', 'pdf'];
    $max_size = 10 * 1024 * 1024;

    move_uploaded_file($file_tmp, $file_dir);
  }

  if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(empty($keterangan)){
      $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Keterangan wajib diisi!";
    }
    if(empty($tanggal)){
      $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Tanggal wajib diisi!";
    }
    if(empty($deskripsi)){
      $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Deskripsi wajib diisi!";
    }

    if($_FILES['file_baru']['error'] != 4){
      if(!in_array(strtolower($ambil_ext), $ext_diizinkan)){
        $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Hanya file jpg, jpeg, dan png yang diizinkan!";
      }
  
      if($ukuran_file > $max_size){
        $pesan_kesalahan[] = "<i class='fa-solid fa-check'></i> Ukuran file melebihi 10 MB!";
      }
    }

    if(!empty($pesan_kesalahan)){
      $_SESSION['validasi'] = implode('<br>', $pesan_kesalahan);
    }
    else{ 
      $result = mysqli_query($connection, "UPDATE ketidakhadiran SET keterangan = '$keterangan', deskripsi = '$deskripsi', tanggal = '$tanggal', file = '$nama_file' WHERE id = '$id'");

      $_SESSION['berhasil'] = 'Data berhasil disimpan!';
      header('Location: ketidakhadiran.php');
      exit;
    }
  }
}

$id = $_GET['id'];
$result = mysqli_query($connection, "SELECT * FROM ketidakhadiran WHERE id = '$id'");
// print_r($result);
// die;
while($data = mysqli_fetch_array($result)){
  $keterangan = $data['keterangan'];
  $deskripsi = $data['deskripsi'];
  $file = $data['file'];
  $tanggal = $data['tanggal'];
}

?>

<!-- Page body -->
<div class="page-body">
  <div class="container-xl">
    <div class="card col-md-6">
      <div class="card-body">
        <form action="" method="POST" enctype="multipart/form-data">
          <input type="hidden" value="<?= $_GET['id'] ?>" name="id">
          <div class="mb-3">
            <label for="">Keterangan</label>
            <select name="keterangan" id="" class="form-control">
              <option value="">-- Pilih Keterangan --</option>
              <option <?= ($keterangan === 'Izin') ? 'selected' : '' ?> value="Izin">Izin</option>
              <option <?= ($keterangan === 'Sakit') ? 'selected' : '' ?> value="Sakit">Sakit</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="">Deskripsi</label>
            <textarea name="deskripsi" class="form-control" cols="30" rows="4"><?= $deskripsi ?></textarea>
          </div>
          <div class="mb-3">
            <label for="">Tanggal</label>
            <input type="date" name="tanggal" id="" class="form-control" value="<?= $tanggal ?>">
          </div>
          <div class="mb-3">
            <label for="">Surat Keterangan</label>
            <input type="file" class="form-control" name="file_baru">
            <input type="hidden" name="file_lama" class="form-control" value="<?= $file ?>">
          </div>

          <button type="submit" class="btn btn-primary" name="update">Update</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include('../layout/footer.php'); ?>