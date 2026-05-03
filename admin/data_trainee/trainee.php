<?php 
session_start();

if(!isset($_SESSION['login'])){
  header('Location: ../../auth/login.php?pesan=belum_login');
}
else if($_SESSION['role'] != 'Admin'){
  header('Location: ../../auth/login.php?pesan=tolak_akses');
}

$judul = 'Data Trainee';

include('../layout/header.php');
require_once('../../config.php');

$result = mysqli_query($connection, "
  SELECT users.id_trainee, users.username, users.password, users.status, users.role, trainee.*
  FROM users
  JOIN trainee ON trainee.id = users.id_trainee
  WHERE users.role IN ('trainee','Trainee')
    AND users.status = 'Aktif'
  ORDER BY trainee.nip ASC
");

// $trainee = mysqli_fetch_array($result);
// echo "<pre>";
// print_r($trainee);
// echo "</pre>";
?>

<!-- Page body -->
<div class="page-body">
  <div class="container-xl">
    <a href="<?= base_url('admin/data_trainee/tambah.php') ?>" class="btn btn-primary"><span class="text"><i class="fa-solid fa-circle-plus"></i> Tambah Data</span></a>
    <table class="table table-bordered mt-3">
      <tr class="text-center">
        <th>No.</th>
        <th>NIP</th>
        <th>Nama</th>
        <th>Username</th>
        <th>Divisi</th>
        <th>Role</th>
        <th>Aksi</th>
      </tr>

      <?php if(mysqli_num_rows($result) === 0){ ?>
        <tr>
          <td colspan="7">Data kosong, silakan tambahkan data baru.</td>
        </tr>
      <?php }
      else{ ?>
        <?php
        $no = 1;
        while($trainee = mysqli_fetch_array($result)): ?>
          <tr>
            <td><?= $no++; ?></td>
            <td><?= $trainee['nip']; ?></td>
            <td><?= $trainee['nama']; ?></td>
            <td><?= $trainee['username']; ?></td>
            <td><?= $trainee['nama_divisi']; ?></td>
            <td>Trainee</td>
            <td class="text-center">
              <a href="<?= base_url('admin/data_trainee/detail.php?id='.$trainee['id']) ?>" class="badge bg-primary">Detail</a>
              <a href="<?= base_url('admin/data_trainee/edit.php?id='.$trainee['id']) ?>" class="badge bg-success">Edit</a>
              <a href="<?= base_url('admin/data_trainee/hapus.php?id='.$trainee['id']) ?>" class="badge bg-danger tombol-hapus">Hapus</a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php } ?>

      
    </table>
  </div>
</div>

<?php include('../layout/footer.php'); ?>