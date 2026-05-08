<?php 
ob_start();
session_start();

if(!isset($_SESSION['login'])){
  header('Location: ../../auth/login.php?pesan=belum_login');
  exit;
} else if(!in_array($_SESSION['role'], ['trainee','Trainee'])){
  header('Location: ../../auth/login.php?pesan=tolak_akses');
  exit;
}

$judul = 'Rekap Presensi';
include('../layout/header.php'); 
include_once('../../config.php');

$id = $_SESSION['id'];
$hari_ini = date('Y-m-d');

// Query Data berdasarkan Filter atau Default
if(empty($_GET['tanggal_dari'])){
  $result = mysqli_query($connection, "SELECT * FROM presensi WHERE id_trainee = '$id' ORDER BY tanggal_masuk DESC");
} else {
  $tanggal_dari = $_GET['tanggal_dari'];
  $tanggal_sampai = $_GET['tanggal_sampai'];
  $result = mysqli_query($connection, "SELECT * FROM presensi WHERE id_trainee = '$id' AND tanggal_masuk BETWEEN '$tanggal_dari' AND '$tanggal_sampai' ORDER BY tanggal_masuk DESC");
}

// Ambil Jam Masuk Kantor dari Lokasi (Toleransi 40 Menit)
$lokasi_user = $_SESSION['lokasi_presensi'];
$l_query = mysqli_query($connection, "SELECT jam_masuk FROM lokasi_presensi WHERE nama_lokasi = '$lokasi_user'");
$l_data = mysqli_fetch_array($l_query);
$jam_masuk_kantor = $l_data['jam_masuk'] ?? '07:30:00';
$jam_batas_telat = date('H:i:s', strtotime($jam_masuk_kantor . ' +40 minutes'));
?>

<div class="page-body">
  <div class="container-xl">

    <div class="row mb-3">
      <div class="col-md-2">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
          Export Excel
        </button>
      </div>

      <div class="col-md-8">
        <form action="" method="GET">
          <div class="input-group">
            <input type="date" class="form-control" name="tanggal_dari">
            <input type="date" class="form-control mx-2" name="tanggal_sampai">
            <button type="submit" class="btn btn-primary">Tampilkan</button>
            <a href="rekap_presensi.php" class="btn btn-success mx-2">Refresh</a>
          </div>
        </form>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-bordered text-center align-middle bg-white">
        <thead class="table-primary">
          <tr>
            <th>NO.</th>
            <th>TANGGAL</th>
            <th>JAM MASUK</th>
            <th>FOTO MASUK</th>
            <th>JAM PULANG</th>
            <th>FOTO PULANG</th>
            <th>TOTAL JAM</th>
            <th>STATUS</th>
          </tr>
        </thead>
        <tbody>
          <?php if(mysqli_num_rows($result) === 0) : ?>
            <tr><td colspan="8" class="text-center">Data rekap presensi masih kosong.</td></tr>
          <?php endif; ?>

          <?php 
          $no = 1;
          while($rekap = mysqli_fetch_array($result)):
            $jam_masuk_raw  = trim((string)($rekap['jam_masuk'] ?? ''));
            $jam_keluar_raw = trim((string)($rekap['jam_keluar'] ?? '00:00:00'));
            $tanggal_raw    = trim((string)($rekap['tanggal_masuk'] ?? ''));

            // LOGIKA HITUNG TERLAMBAT
            $jam_masuk_trainee = date('H:i:s', strtotime($jam_masuk_raw));
            $is_telat = strtotime($jam_masuk_trainee) > strtotime($jam_batas_telat);
            $diff_terlambat = strtotime($jam_masuk_trainee) - strtotime($jam_batas_telat);
            $jam_telat = floor($diff_terlambat / 3600);
            $menit_telat = floor(($diff_terlambat % 3600) / 60);

            // PENENTUAN TAMPILAN (SINKRON DENGAN ADMIN)
            if ($jam_keluar_raw !== '00:00:00' && !empty($jam_keluar_raw)) {
                // KONDISI SUDAH PRESENSI PULANG
                $jam_pulang_display = $jam_keluar_raw;
                
                $ts_masuk = strtotime($tanggal_raw . ' ' . $jam_masuk_raw);
                $ts_keluar = strtotime($tanggal_raw . ' ' . $jam_keluar_raw);
                if ($ts_keluar < $ts_masuk) { $ts_keluar = strtotime('+1 day', $ts_keluar); }
                $selisih = $ts_keluar - $ts_masuk;
                $total_jam_display = floor($selisih / 3600) . " jam " . floor(($selisih % 3600) / 60) . " menit";
                
                $foto_keluar_display = '<img src="foto/'.$rekap['foto_keluar'].'" width="60" style="border-radius:5px;">';
                
                $status_badge = (!$is_telat) ? "<span class='badge bg-success text-white'>On Time</span>" : "<span class='badge bg-warning text-white'>Terlambat</span>";
                $detail_telat = ($is_telat) ? "<br><small class='text-muted'>Telat: {$jam_telat}j {$menit_telat}m</small>" : "";

            } else {
                // KONDISI BELUM PRESENSI PULANG
                if ($tanggal_raw < $hari_ini) {
                    // JIKA SUDAH GANTI HARI -> ALPA
                    $jam_pulang_display = "-";
                    $total_jam_display = "Tidak presensi pulang";
                    $foto_keluar_display = "<span class='text-danger' style='font-size:11px;'>Tidak presensi pulang</span>";
                    $status_badge = "<span class='badge bg-danger text-white'>Alpa</span><br><small class='text-danger' style='font-size:10px;'>Tidak presensi pulang</small>";
                    $detail_telat = "";
                } else {
                    // JIKA MASIH HARI INI -> SEDANG BEKERJA
                    $jam_pulang_display = "";
                    $total_jam_display = "Sedang bekerja";
                    $foto_keluar_display = "<small class='text-muted'>Belum melakukan presensi pulang</small>";
                    $status_badge = (!$is_telat) ? "<span class='badge bg-success text-white'>On Time</span>" : "<span class='badge bg-warning text-white'>Terlambat</span>";
                    $detail_telat = ($is_telat) ? "<br><small class='text-muted'>Telat: {$jam_telat}j {$menit_telat}m</small>" : "";
                }
            }
          ?>
            <tr>
              <td><?= $no++; ?></td>
              <td><?= date('d F Y', strtotime($rekap['tanggal_masuk'])); ?></td>
              <td><?= $jam_masuk_raw; ?></td>
              <td>
                <img src="foto/<?= $rekap['foto_masuk']; ?>" width="60" style="border-radius:5px;">
              </td>
              <td><?= $jam_pulang_display; ?></td>
              <td><?= $foto_keluar_display; ?></td>
              <td><?= $total_jam_display; ?></td>
              <td>
                <?= $status_badge; ?>
                <?= $detail_telat; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="exampleModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Export Excel Rekap Pribadi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="rekap_excel.php" method="POST">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Tanggal Awal</label>
            <input type="date" class="form-control" name="tanggal_dari" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Tanggal Akhir</label>
            <input type="date" class="form-control" name="tanggal_sampai" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Mulai Export</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include('../layout/footer.php'); ?>