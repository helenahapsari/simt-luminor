<?php
ob_start();
session_start();

if(!isset($_SESSION['login'])){
  header('Location: ../../auth/login.php?pesan=belum_login');
}
else if(!in_array($_SESSION['role'], ['trainee','Trainee'])){
  header('Location: ../../auth/login.php?pesan=tolak_akses');
}

$judul = 'Ketidakhadiran';
include('../layout/header.php');  

$id = $_SESSION['id'];
$result = mysqli_query($connection, "SELECT * FROM ketidakhadiran WHERE id_trainee = '$id' ORDER BY id DESC");

?>

<!-- Page body -->
<div class="page-body">
  <div class="container-xl">
    <a href="<?= base_url('trainee/ketidakhadiran/pengajuan_ketidakhadiran.php') ?>" class="btn btn-primary mb-2">Tambah Data</a>
    <table class="table table-bordered">
      <tr class="text-center">
        <th>No</th>
        <th>Tanggal</th>
        <th>Keterangan</th>
        <th>Deskripsi</th>
        <th>File</th>
        <th>Status Pengajuan</th>
        <th>Aksi</th>
      </tr>

      <?php if(mysqli_num_rows($result) === 0){ ?>
        <tr>
          <td colspan="7">Data ketidakhadiran masih kosong.</td>
        </tr>
      <?php }
      else{
        $no = 1;
        while($data = mysqli_fetch_array($result)): ?>
        <tr>
          <td><?= $no++; ?></td>
          <td><?= date('d F Y', strtotime($data['tanggal'])) ?></td>
          <td><?= $data['keterangan']; ?></td>
          <td><?= $data['deskripsi']; ?></td>
          <td class="text-center">
            <a target="_blank" href="<?= base_url('assets/file_ketidakhadiran/'.$data['file']) ?>" class="badge badge-pill bg-primary" download>Download</a>
          </td>
          <td><?= $data['status_pengajuan']; ?></td>
          <td class="text-center">
            <a href="edit.php?id=<?= $data['id'] ?>" class="badge bg-success">Update</a>
            <a href="hapus.php?id=<?= $data['id'] ?>" class="badge bg-danger tombol-hapus">Hapus</a>
          </td>
        </tr>
        
        <?php endwhile;?>
      <?php } ?>

    </table>
  </div>
</div>

<?php include('../layout/footer.php'); ?>