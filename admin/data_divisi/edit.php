<?php 
session_start();
ob_start();

if(!isset($_SESSION['login'])){
  header('Location: ../../auth/login.php?pesan=belum_login');
  exit;
}
else if($_SESSION['role'] != 'Admin'){
  header('Location: ../../auth/login.php?pesan=tolak_akses');
  exit;
}

$judul = 'Edit Data Divisi';
include('../layout/header.php');
require_once('../../config.php');

// 1. Ambil ID dari URL atau POST
$id = isset($_GET['id']) ? $_GET['id'] : (isset($_POST['id']) ? $_POST['id'] : null);

// 2. Logika Update (Diproses pas tombol Update diklik)
if(isset($_POST['update'])){
  $id = $_POST['id'];
  // Pakai 'divisi' sesuai name di tag <input> bawah
  $nama_divisi_input = isset($_POST['divisi']) ? $_POST['divisi'] : '';

  if(empty(trim($nama_divisi_input))){
    $_SESSION['validasi'] = 'Nama divisi wajib diisi!';
  }
  else{
    $divisi_clean = htmlspecialchars(trim($nama_divisi_input));
    // Sesuaikan 'nama_divisi' dengan kolom di DB lo (tadi kita pake nama_divisi)
    $query = "UPDATE divisi SET nama_divisi = '$divisi_clean' WHERE id = '$id'";
    $result = mysqli_query($connection, $query);

    if($result){
      $_SESSION['berhasil'] = 'Data berhasil diupdate!';
      header('Location: divisi.php');
      exit;
    } else {
      die("Error Update: " . mysqli_error($connection));
    }
  }
}

// 3. Tarik data lama buat ditampilin di form (pakai ID yang tadi)
$result = mysqli_query($connection, "SELECT * FROM divisi WHERE id = '$id'");
$data = mysqli_fetch_array($result);

// Jika data gak ketemu (id salah)
if(!$data) {
    echo "Data tidak ditemukan!";
    exit;
}
?>

<div class="page-body">
  <div class="container-xl">
    <div class="card col-md-6">
      <div class="card-body">
        <form action="" method="POST">
          <div class="mb-3">
            <label for="">Nama Divisi</label>
            <input type="hidden" value="<?= $id ?>" name="id">
            <input type="text" class="form-control" name="divisi" value="<?= $data['nama_divisi'] ?>">
          </div>
          <button type="submit" name="update" class="btn btn-primary">Update</button>
          <a href="divisi.php" class="btn btn-secondary">Kembali</a>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include('../layout/footer.php'); ?>
