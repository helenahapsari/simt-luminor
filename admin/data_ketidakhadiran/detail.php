<?php
ob_start();
session_start();

if(!isset($_SESSION['login'])){
  header('Location: ../../auth/login.php?pesan=belum_login');
}
else if($_SESSION['role'] != 'Admin'){
  header('Location: ../../auth/login.php?pesan=tolak_akses');
}

$judul = 'Detail Ketidakhadiran';
include('../layout/header.php');

if(isset($_POST['update'])){
  $id = $_POST['id'];
  $status_pengajuan = $_POST['status_pengajuan'];

  $result = mysqli_query($connection, "UPDATE ketidakhadiran SET status_pengajuan = '$status_pengajuan' WHERE id= '$id'");

  $_SESSION['berhasil'] = 'Status pengajuan berhasil diupdate';
  header('Location: ketidakhadiran.php');
  exit;
}

$id = $_GET['id'];
$result = mysqli_query($connection, "SELECT * FROM ketidakhadiran WHERE id = '$id'");

while($data = mysqli_fetch_array($result)){
  $keterangan = $data['keterangan'];
  $deskripsi = $data['deskripsi'];
  $file = $data['file'];
  $tanggal = $data['tanggal'];
  $status_pengajuan = $data['status_pengajuan'];
}
?>

<!-- Page body -->
<div class="page-body">
  <div class="container-xl">

    <div class="card col-md-6">
      <div class="card-body">
        <form action="" method="POST">
          <input type="hidden" name="id" value="<?= $id ?>">
          <div class="mb-3">
            <label for="">Tanggal</label>
            <input type="date" name="tanggal" id="" class="form-control" value="<?= $tanggal ?>" readonly>
          </div>
          <div class="mb-3">
            <label for="">Keterangan</label>
            <input type="text" name="keterangan" id="" class="form-control" value="<?= $keterangan ?>" readonly>
          </div>

          <div class="mb-3">
            <label for="">Status Pengajuan</label>
            <select name="status_pengajuan" id="" class="form-control">
              <option value="">-- Pilih Status --</option>
              <option <?= ($status_pengajuan === 'Pending') ? 'selected' : '' ?> value="Pending">Pending</option>
              <option <?= ($status_pengajuan === 'Rejected') ? 'selected' : '' ?> value="Rejected">Rejected</option>
              <option <?= ($status_pengajuan === 'Approve') ? 'selected' : '' ?> value="Approve">Approve</option>
            </select>
          </div>

          <button type="submit" name="update" class="btn btn-primary">Update</button>

        </form>

      </div>
    </div>



  </div>
</div>


<?php include('../layout/footer.php'); ?>