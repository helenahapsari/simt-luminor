<?php 
session_start();

if(!isset($_SESSION['login'])){
  header('Location: ../../auth/login.php?pesan=belum_login');
}
else if($_SESSION['role'] != 'Admin'){
  header('Location: ../../auth/login.php?pesan=tolak_akses');
}

$judul = 'Data Divisi';

include('../layout/header.php');
require_once('../../config.php');

$result = mysqli_query($connection, "SELECT * FROM divisi ORDER BY id DESC");

?>

<!-- Page body -->
<div class="page-body">
  <div class="container-xl">
    <a href="<?= base_url('admin/data_divisi/tambah.php') ?>" class="btn btn-primary"><span class="text"><i class="fa-solid fa-circle-plus"></i> Tambah Data</span></a>

    <div class="row row-deck row-cards mt-2">
      <table class="table table-bordered">
        <tr class="text-center">
          <th>No.</th>
          <th>Nama Divisi</th>
          <th>Aksi</th>
        </tr>

        <?php if(mysqli_num_rows($result) === 0): ?>
          <tr>
            <td colspan="3">Data masih kosong, silakan tambahkan data baru...</td>
          </tr>

        <?php else: ?>

          <?php 
          $no = 1;
          while($divisi = mysqli_fetch_assoc($result)):
          // print_r($divisi);

          // foreach($result as $data):
          ?>
            <tr>
              <td><?= $no++; ?></td>
              <td><?= $divisi['nama_divisi']; ?></td>
              <td class="text-center">
                <a href="<?= base_url('admin/data_divisi/edit.php?id='.$divisi['id']) ?>" class="badge bg-primary">Edit</a>
                <a href="<?= base_url('admin/data_divisi/hapus.php?id='.$divisi['id']) ?>" class="badge bg-danger tombol-hapus">Hapus</a>
              </td>
            </tr>
          <?php endwhile; ?>

        <?php endif; ?>

      </table>
    </div>
  </div>
</div>

<?php include('../layout/footer.php'); ?>
        