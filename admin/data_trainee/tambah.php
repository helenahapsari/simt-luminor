<?php 
ob_start();
session_start();

if(!isset($_SESSION['login'])){
  header('Location: ../../auth/login.php?pesan=belum_login');
  exit;
}
else if($_SESSION['role'] != 'Admin'){
  header('Location: ../../auth/login.php?pesan=tolak_akses');
  exit;
}

$judul = 'Tambah Data Trainee';

include('../layout/header.php');
require_once('../../config.php');

$ambil_divisi = mysqli_query($connection, "SELECT * FROM divisi ORDER BY nama_divisi ASC");
$ambil_lok_presensi = mysqli_query($connection, "SELECT * FROM lokasi_presensi ORDER BY nama_lokasi ASC");

if(isset($_POST['submit'])){
  $ambil_nip = mysqli_query($connection, "SELECT nip FROM trainee ORDER BY nip DESC LIMIT 1");
  if(mysqli_num_rows($ambil_nip) > 0){
    $row = mysqli_fetch_assoc($ambil_nip);
    $nip_db = $row['nip'];
    $nip_db = explode('-', $nip_db);
    $no_baru = (int)$nip_db[1] + 1;
    $nip_baru = 'PEG-' . str_pad($no_baru, 4, 0, STR_PAD_LEFT);
  } else {
    $nip_baru = "PEG-0001";
  }

  $nip = $nip_baru;
  $nama = htmlspecialchars($_POST['nama']);
  $jenis_kelamin = htmlspecialchars($_POST['jenis_kelamin']);
  $alamat = htmlspecialchars($_POST['alamat']);
  $no_handphone = htmlspecialchars($_POST['no_handphone']);
  $divisi = htmlspecialchars($_POST['divisi']); 
  $username = htmlspecialchars($_POST['username']);
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $role = htmlspecialchars($_POST['role']);
  $status = htmlspecialchars($_POST['status']);
  $lokasi_presensi = htmlspecialchars($_POST['lokasi_presensi']);

  // Handle Foto
  $nama_file = "";
  if(!empty($_FILES['foto']['name'])){
    $file = $_FILES['foto'];
    $nama_file = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_dir = '../../assets/img/foto_user/'.$nama_file;
    move_uploaded_file($file_tmp, $file_dir);
  }

  if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(empty($nama)) $pesan_kesalahan[] = "Nama wajib diisi!";
    if(empty($jenis_kelamin)) $pesan_kesalahan[] = "Jenis kelamin wajib diisi!";
    if(empty($alamat)) $pesan_kesalahan[] = "Alamat wajib diisi!";
    if(empty($no_handphone)) $pesan_kesalahan[] = "No Handphone wajib diisi!";
    if(empty($divisi)) $pesan_kesalahan[] = "Divisi wajib diisi!";
    if(empty($status)) $pesan_kesalahan[] = "Status wajib diisi!";
    if(empty($username)) $pesan_kesalahan[] = "Username wajib diisi!";
    if(empty($_POST['password'])) $pesan_kesalahan[] = "Password wajib diisi!";
    if(empty($role)) $pesan_kesalahan[] = "Role wajib diisi!";
    if(empty($lokasi_presensi)) $pesan_kesalahan[] = "Lokasi Presensi wajib diisi!";
    if($_POST['password'] != $_POST['ulangi_password']) $pesan_kesalahan[] = "Password tidak sama!";

    // --- KUNCI PERBAIKAN: Validasi Cek Username Duplikat ---
    if(!empty($username)){
      $cek_username = mysqli_query($connection, "SELECT username FROM users WHERE username = '$username'");
      if(mysqli_num_rows($cek_username) > 0){
        $pesan_kesalahan[] = "Gagal! Username <strong>{$username}</strong> sudah terdaftar di sistem. Gunakan username lain!";
      }
    }

    if(!empty($pesan_kesalahan)){
      $_SESSION['validasi'] = implode('<br>', $pesan_kesalahan);
    } else { 
      // Query INSERT ke tabel trainee (Aman dijalankan jika lolos cek username)
      $trainee = mysqli_query($connection, "INSERT INTO trainee(nip, nama, jenis_kelamin, alamat, no_handphone, nama_divisi, lokasi_presensi, foto) 
                   VALUES('$nip', '$nama', '$jenis_kelamin', '$alamat', '$no_handphone', '$divisi', '$lokasi_presensi', '$nama_file')");
      
      $id_trainee = mysqli_insert_id($connection);
      
      // Query INSERT ke tabel users
      $user = mysqli_query($connection, "INSERT INTO users(id_trainee, username, password, status, role) 
              VALUES('$id_trainee', '$username', '$password', '$status', '$role')");

      $_SESSION['berhasil'] = 'Data berhasil disimpan!';
      header('Location: trainee.php');
      exit;
    }
  }
}
?>

<div class="page-body">
  <div class="container-xl">
    <?php if(isset($_SESSION['validasi'])): ?>
      <div class="alert alert-danger alert-dismissible" role="alert">
        <div class="d-flex">
          <div><?= $_SESSION['validasi']; ?></div>
        </div>
        <?php unset($_SESSION['validasi']); ?>
      </div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
      <div class="row">
        <div class="col-md-6">
          <div class="card">
            <div class="card-body">
              <div class="mb-3">
                <label>Nama</label>
                <input type="text" class="form-control" name="nama" value="<?= $_POST['nama'] ?? '' ?>">
              </div>
              <div class="mb-3">
                <label>Jenis Kelamin</label>
                <select name="jenis_kelamin" class="form-control">
                  <option value="">-- Pilih Jenis Kelamin --</option>
                  <option value="Laki-laki" <?= (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                  <option value="Perempuan" <?= (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                </select>
              </div>
              <div class="mb-3">
                <label>Alamat</label>
                <textarea name="alamat" class="form-control"><?= $_POST['alamat'] ?? '' ?></textarea>
              </div>
              <div class="mb-3">
                <label>No Handphone</label>
                <input type="text" class="form-control" name="no_handphone" value="<?= $_POST['no_handphone'] ?? '' ?>">
              </div>
              <div class="mb-3">
                <label>Divisi</label>
                <select name="divisi" class="form-control">
                  <option value="">-- Pilih Divisi --</option>
                  <?php while($row_divisi = mysqli_fetch_assoc($ambil_divisi)): ?>
                    <option value="<?= $row_divisi['nama_divisi'] ?>" <?= (isset($_POST['divisi']) && $_POST['divisi'] == $row_divisi['nama_divisi']) ? 'selected' : '' ?>><?= $row_divisi['nama_divisi']; ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="mb-3">
                <label>Status</label>
                <select name="status" class="form-control">
                  <option value="">-- Pilih Status --</option>
                  <option value="Aktif" <?= (isset($_POST['status']) && $_POST['status'] == 'Aktif') ? 'selected' : '' ?>>Aktif</option>
                  <option value="Tidak Aktif" <?= (isset($_POST['status']) && $_POST['status'] == 'Tidak Aktif') ? 'selected' : '' ?>>Tidak Aktif</option>
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
                <input type="text" class="form-control" name="username" value="<?= $_POST['username'] ?? '' ?>">
              </div>
              <div class="mb-3">
                <label>Password</label>
                <input type="password" class="form-control" name="password">
              </div>
              <div class="mb-3">
                <label>Ulangi Password</label>
                <input type="password" class="form-control" name="ulangi_password">
              </div>
              <div class="mb-3">
                <label>Role</label>
                <select name="role" class="form-control">
                  <option value="">-- Pilih Role --</option>
                  <option value="Admin" <?= (isset($_POST['role']) && $_POST['role'] == 'Admin') ? 'selected' : '' ?>>Admin</option>
                  <option value="Trainee" <?= (isset($_POST['role']) && $_POST['role'] == 'Trainee') ? 'selected' : '' ?>>Trainee</option>
                </select>
              </div>
              <div class="mb-3">
                <label>Lokasi Presensi</label>
                <select name="lokasi_presensi" class="form-control">
                  <option value="">-- Pilih Lokasi --</option>
                  <?php while($row_lokasi = mysqli_fetch_assoc($ambil_lok_presensi)): ?>
                    <option value="<?= $row_lokasi['nama_lokasi']; ?>" <?= (isset($_POST['lokasi_presensi']) && $_POST['lokasi_presensi'] == $row_lokasi['nama_lokasi']) ? 'selected' : '' ?>><?= $row_lokasi['nama_lokasi']; ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="mb-3">
                <label>Foto</label>
                <input type="file" class="form-control" name="foto" accept="image/png, image/jpeg, image/jpg">
              </div>
              <button type="submit" class="btn btn-primary" name="submit">Simpan</button>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<?php include('../layout/footer.php'); ?>
