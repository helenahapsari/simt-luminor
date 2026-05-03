<?php 
session_start();
ob_start();

// 1. PROTEKSI HALAMAN
if(!isset($_SESSION['login'])){
  header('Location: ../../auth/login.php?pesan=belum_login');
  exit;
}
else if($_SESSION['role'] != 'Admin'){
  header('Location: ../../auth/login.php?pesan=tolak_akses');
  exit;
}

$judul = 'Tambah Data Divisi';

include('../layout/header.php');
require_once('../../config.php');

// 2. LOGIKA PEMROSESAN DATA
if(isset($_POST['submit'])){
  
  $input_divisi = isset($_POST['divisi']) ? $_POST['divisi'] : '';

  if(empty(trim($input_divisi))){
    $_SESSION['validasi'] = 'Nama divisi wajib diisi!';
  }
  else{
    $divisi_clean = htmlspecialchars(trim($input_divisi));
    
    /* CATATAN PENTING: 
       Gue ganti nama kolomnya jadi 'nama_divisi'. 
       Kalau di database lo nama kolomnya cuma 'nama' atau 'divisi_name', 
       silakan ganti tulisan 'nama_divisi' di bawah ini sesuai kolom di DB lo.
    */
    $sql = "INSERT INTO divisi (nama_divisi) VALUES ('$divisi_clean')";
    $result = mysqli_query($connection, $sql);
    
    if($result){
      $_SESSION['berhasil'] = 'Data berhasil disimpan!';
      header('Location: divisi.php');
      exit;
    } else {
      // Baris ini bakal ngasih tau lo kalau nama kolomnya salah lagi
      die("Error SQL: " . mysqli_error($connection));
    }
  }
}
?>

<div class="page-body">
  <div class="container-xl">
    <div class="card col-md-6">
      <div class="card-body">
        
        <form action="" method="POST">
          <div class="mb-3">
            <label class="form-label">Nama Divisi</label>
            <input type="text" class="form-control" name="divisi" placeholder="Masukkan nama divisi" autocomplete="off">
          </div>
          
          <div class="mt-3">
            <button type="submit" name="submit" class="btn btn-primary">Simpan</button>
            <a href="divisi.php" class="btn btn-secondary">Kembali</a>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>

<?php include('../layout/footer.php'); ?>
