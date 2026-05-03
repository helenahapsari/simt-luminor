<?php 
ob_start();
session_start();

if(!isset($_SESSION['login'])){
  header('Location: ../../auth/login.php?pesan=belum_login');
}
else if($_SESSION['role'] != 'Admin'){
  header('Location: ../../auth/login.php?pesan=tolak_akses');
}

$judul = 'Edit Trainee';
include('../layout/header.php');
require_once('../../config.php');

// Ambil ID dari URL atau POST
$id = isset($_GET['id']) ? $_GET['id'] : $_POST['id'];

// Query SELECT data lama
$result = mysqli_query($connection, "SELECT users.id_trainee, users.username, users.password, users.status, users.role, trainee.* FROM users JOIN trainee ON trainee.id = users.id_trainee WHERE trainee.id = '$id'");

while($trainee = mysqli_fetch_array($result)){
  $nama = $trainee['nama'];
  $jenis_kelamin = $trainee['jenis_kelamin'];
  $alamat = $trainee['alamat'];
  $no_handphone = $trainee['no_handphone'];
  $divisi = $trainee['nama_divisi'];
  $username = $trainee['username'];
  $password_db = $trainee['password']; // Sumber password lama dari database
  $status = $trainee['status'];
  $lokasi_presensi = $trainee['lokasi_presensi'];
  $role = $trainee['role'];
  $foto = $trainee['foto'];
}

$ambil_divisi = mysqli_query($connection, "SELECT * FROM divisi ORDER BY nama_divisi ASC");
$ambil_lok_presensi = mysqli_query($connection, "SELECT * FROM lokasi_presensi ORDER BY nama_lokasi ASC");

if(isset($_POST['edit'])){
  $id = $_POST['id'];
  $nama = htmlspecialchars($_POST['nama']);
  $jenis_kelamin = htmlspecialchars($_POST['jenis_kelamin']);
  $alamat = htmlspecialchars($_POST['alamat']);
  $no_handphone = htmlspecialchars($_POST['no_handphone']);
  $divisi = htmlspecialchars($_POST['divisi']);
  $username = htmlspecialchars($_POST['username']);
  $status = htmlspecialchars($_POST['status']);
  $role = htmlspecialchars($_POST['role']);
  $lokasi_presensi = htmlspecialchars($_POST['lokasi_presensi']);

  // --- LOGIKA PASSWORD (BIAR GAK GAGAL LOGIN) ---
  if(empty($_POST['password'])){
    // Jika kolom password kosong, ambil password lama dari input hidden
    $password_fix = $_POST['password_lama'];
  } else {
    // Jika diisi, buat hash baru
    if($_POST['password'] != $_POST['ulangi_password']){
       $pesan_kesalahan[] = "Password konfirmasi tidak cocok!";
    }
    $password_fix = password_hash($_POST['password'], PASSWORD_DEFAULT);
  }

  // --- LOGIKA FOTO ---
  if($_FILES['foto_baru']['error'] === 4){
    $nama_file = $_POST['foto_lama'];
  } else {
    $file = $_FILES['foto_baru'];
    $nama_file = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_dir = '../../assets/img/foto_user/'.$nama_file;
    move_uploaded_file($file_tmp, $file_dir);
  }

  if(empty($pesan_kesalahan)){
    // UPDATE TABEL TRAINEE
    mysqli_query($connection, "UPDATE trainee SET
      nama = '$nama',
      jenis_kelamin = '$jenis_kelamin',
      alamat = '$alamat',
      no_handphone = '$no_handphone',
      nama_divisi = '$divisi',
      lokasi_presensi = '$lokasi_presensi',
      foto = '$nama_file'
    WHERE id = '$id'");

    // UPDATE TABEL USERS
    mysqli_query($connection, "UPDATE users SET
      username = '$username',
      password = '$password_fix',
      status = '$status',
      role = '$role'
    WHERE id_trainee = '$id'");

    $_SESSION['berhasil'] = 'Data berhasil diupdate!';
    header('Location: trainee.php');
    exit;
  } else {
    $_SESSION['validasi'] = implode('<br>', $pesan_kesalahan);
  }
}
?>

<div class="page-body">
  <div class="container-xl">
    <form action="" method="POST" enctype="multipart/form-data">
      <div class="row">
        <div class="col-md-6">
          <div class="card">
            <div class="card-body">
              <input type="hidden" name="id" value="<?= $id ?>">
              <div class="mb-3">
                <label>Nama</label>
                <input type="text" class="form-control" name="nama" value="<?= $nama ?>">
              </div>
              <div class="mb-3">
                <label>Jenis Kelamin</label>
                <select name="jenis_kelamin" class="form-control">
                  <option value="Laki-laki" <?= ($jenis_kelamin == 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                  <option value="Perempuan" <?= ($jenis_kelamin == 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                </select>
              </div>
              <div class="mb-3">
                <label>Alamat</label>
                <textarea name="alamat" class="form-control"><?= $alamat ?></textarea>
              </div>
              <div class="mb-3">
                <label>No Handphone</label>
                <input type="text" class="form-control" name="no_handphone" value="<?= $no_handphone ?>">
              </div>
              <div class="mb-3">
                <label>Divisi</label>
                <select name="divisi" class="form-control">
                  <?php foreach($ambil_divisi as $data): ?>
                    <option value="<?= $data['nama_divisi'] ?>" <?= ($divisi == $data['nama_divisi']) ? 'selected' : ''; ?>>
                      <?= $data['nama_divisi']; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label>Status</label>
                <select name="status" class="form-control">
                  <option value="Aktif" <?= ($status == 'Aktif') ? 'selected' : '' ?>>Aktif</option>
                  <option value="Tidak Aktif" <?= ($status == 'Tidak Aktif') ? 'selected' : '' ?>>Tidak Aktif</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card">
            <div class="card-body">
              <div class="mb-3">
                <label>Username</label>
                <input type="text" class="form-control" name="username" value="<?= $username ?>">
              </div>
              <div class="mb-3">
                <label>Password (Kosongkan jika tidak ganti)</label>
                <input type="hidden" name="password_lama" value="<?= $password_db ?>">
                <input type="password" class="form-control" name="password">
              </div>
              <div class="mb-3">
                <label>Ulangi Password</label>
                <input type="password" class="form-control" name="ulangi_password">
              </div>
              <div class="mb-3">
                <label>Role</label>
                <input type="text" class="form-control" value="Trainee" readonly>
                <input type="hidden" name="role" value="Trainee">
              </div>
              <div class="mb-3">
                <label>Lokasi Presensi</label>
                <select name="lokasi_presensi" class="form-control">
                  <?php foreach($ambil_lok_presensi as $data): ?>
                    <option value="<?= $data['nama_lokasi']; ?>" <?= ($lokasi_presensi == $data['nama_lokasi']) ? 'selected' : ''; ?>>
                      <?= $data['nama_lokasi']; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label>Foto</label>
                <input type="hidden" value="<?= $foto ?>" name="foto_lama">
                <input type="file" class="form-control" name="foto_baru">
              </div>
              <button type="submit" class="btn btn-primary" name="edit">Update</button>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<?php include('../layout/footer.php'); ?>