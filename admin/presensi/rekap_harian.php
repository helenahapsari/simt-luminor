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

$judul = 'Rekap Presensi Harian';

include('../layout/header.php'); 

if(empty($_GET['tanggal_dari'])){
  $tanggal_hari_ini = date('Y-m-d');
  $result = mysqli_query($connection, "
    SELECT presensi.*, trainee.nama, trainee.lokasi_presensi 
    FROM presensi 
    JOIN trainee ON trainee.id = presensi.id_trainee 
    WHERE tanggal_masuk = '$tanggal_hari_ini' 
    ORDER BY tanggal_masuk DESC
  ");
  $tanggal = date('d F Y', strtotime($tanggal_hari_ini));
} else {
  $tanggal_dari = $_GET['tanggal_dari'];
  $tanggal_sampai = $_GET['tanggal_sampai'];
  $result = mysqli_query($connection, "
    SELECT presensi.*, trainee.nama, trainee.lokasi_presensi 
    FROM presensi 
    JOIN trainee ON trainee.id = presensi.id_trainee 
    WHERE tanggal_masuk BETWEEN '$tanggal_dari' AND '$tanggal_sampai' 
    ORDER BY tanggal_masuk DESC
  ");
  $tanggal = date('d F Y', strtotime($tanggal_dari)).' sampai '.date('d F Y', strtotime($tanggal_sampai));
}
?>

<div class="page-body">
  <div class="container-xl">

    <div class="row">
      <div class="col-md-2">
        <button type="button" class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#exampleModal">
          Export Excel
        </button>
      </div>

      <div class="col-md-8">
        <form action="" method="GET">
          <div class="input-group">
            <input type="date" class="form-control" name="tanggal_dari" required>
            <input type="date" class="form-control mx-2" name="tanggal_sampai" required>
            <button type="submit" class="btn btn-primary">Tampilkan</button>
            <a href="rekap_harian.php" class="btn btn-success mx-2">Refresh</a>
          </div>
        </form>
      </div>
    </div>

    <span>Rekap presensi tanggal <?= $tanggal; ?></span>
    <table class="table table-bordered mt-3">
      <tr class="text-center align-middle">
        <th>No.</th>
        <th>Nama</th>
        <th>Tanggal</th>
        <th>Jam Masuk</th>
        <th>Foto Masuk</th>
        <th>Jam Pulang</th>
        <th>Foto Pulang</th>
        <th>Total Jam</th>
        <th>Status</th>
      </tr>

      <?php if(mysqli_num_rows($result) === 0){ ?>
        <tr>
          <td colspan="9" class="text-center">Data rekap presensi masih kosong.</td>
        </tr>
      <?php } ?>

      <?php 
      $no = 1;
      foreach($result as $rekap):
        // 1. Ambil Data Dasar
        $jam_masuk_raw  = trim((string)($rekap['jam_masuk'] ?? ''));
        $jam_keluar_raw = trim((string)($rekap['jam_keluar'] ?? ''));
        $tanggal_raw    = trim((string)($rekap['tanggal_masuk'] ?? ''));
        $hari_ini       = date('Y-m-d');

        // 2. Aturan Jam Masuk & Toleransi 40 Menit
        $lokasi_presensi = $rekap['lokasi_presensi'];
        $lokasi = mysqli_query($connection, "SELECT jam_masuk FROM lokasi_presensi WHERE nama_lokasi = '$lokasi_presensi'");
        $lokasi_result = mysqli_fetch_array($lokasi);
        $jam_masuk_kantor = $lokasi_result['jam_masuk'];
        $jam_batas_telat = date('H:i:s', strtotime($jam_masuk_kantor . ' +40 minutes'));

        // 3. Hitung Keterlambatan
        $jam_masuk_trainee = date('H:i:s', strtotime($jam_masuk_raw));
        $is_telat = strtotime($jam_masuk_trainee) > strtotime($jam_batas_telat);
        
        $diff_terlambat = strtotime($jam_masuk_trainee) - strtotime($jam_batas_telat);
        $jam_telat = floor($diff_terlambat / 3600);
        $menit_telat = floor(($diff_terlambat % 3600) / 60);

        // 4. Inisialisasi Variabel Tampilan
        $jam_pulang_display = "";
        $foto_keluar_display = "";
        $total_jam_display = "";
        $status_final_badge = "";

        // --- LOGIKA FINAL STATUS & BADGE (TEXT WHITE) ---
        if ($jam_keluar_raw !== '00:00:00' && !empty($jam_keluar_raw)) {
            // KONDISI: SUDAH PULANG
            $jam_pulang_display = $jam_keluar_raw;
            
            // Hitung Total Jam Kerja
            $ts_masuk  = strtotime($tanggal_raw . ' ' . $jam_masuk_raw);
            $ts_keluar = strtotime($tanggal_raw . ' ' . $jam_keluar_raw);
            if ($ts_keluar < $ts_masuk) { $ts_keluar = strtotime('+1 day', $ts_keluar); }
            $selisih = $ts_keluar - $ts_masuk;
            $total_jam_display = floor($selisih / 3600) . " jam " . floor(($selisih % 3600) / 60) . " menit";

            if (!$is_telat) {
                $status_final_badge = "<span class='badge bg-success text-white'>On Time</span>";
            } else {
                $status_final_badge = "<span class='badge bg-warning text-white'>Terlambat</span><br><small class='text-muted'>Telat: {$jam_telat}j {$menit_telat}m</small>";
            }
        } else {
            // KONDISI: BELUM PULANG
            if ($tanggal_raw < $hari_ini) {
                // Vonis Alpa (Lewat hari)
                $jam_pulang_display = "-";
                $status_final_badge = "<span class='badge bg-danger text-white'>Alpa</span><br><small class='text-danger' style='font-size:10px;'>Tidak presensi pulang</small>";
                $foto_keluar_display = "<span class='text-muted' style='font-size:11px;'>Tidak presensi pulang.</span>";
                $total_jam_display = "<span class='text-muted' style='font-size:11px;'>Tidak presensi pulang</span>";
            } else {
                // Sedang Bekerja (Hari ini)
                $jam_pulang_display = ""; 
                $foto_keluar_display = "<span class='text-muted' style='font-size:11px;'>Belum melakukan presesi pulang.</span>";
                $total_jam_display = "Sedang bekerja";
                
                if (!$is_telat) {
                    $status_final_badge = "<span class='badge bg-success text-white'>On Time</span>";
                } else {
                    $status_final_badge = "<span class='badge bg-warning text-white'>Terlambat</span><br><small class='text-muted'>Telat: {$jam_telat}j {$menit_telat}m</small>";
                }
            }
        }
      ?>
        <tr class="text-center align-middle">
          <td><?= $no++; ?></td>
          <td><?= htmlspecialchars($rekap['nama']); ?></td>
          <td><?= date('d F Y', strtotime($rekap['tanggal_masuk'])); ?></td>
          <td><?= $jam_masuk_raw; ?></td>

          <td>
            <?php 
              $path_masuk = '../../trainee/presensi/foto/'.$rekap['foto_masuk'];
              if(!empty($rekap['foto_masuk']) && file_exists($path_masuk)): ?>
                <img src="<?= $path_masuk; ?>" alt="Foto" width="60" height="60" style="object-fit:cover;border-radius:8px;">
            <?php else: echo "-"; endif; ?>
          </td>

          <td><?= $jam_pulang_display; ?></td>

          <td>
            <?php 
              $path_keluar = '../../trainee/presensi/foto/'.$rekap['foto_keluar'];
              if(!empty($rekap['foto_keluar']) && file_exists($path_keluar)): ?>
                <img src="<?= $path_keluar; ?>" alt="Foto" width="60" height="60" style="object-fit:cover;border-radius:8px;">
            <?php else: echo $foto_keluar_display; endif; ?>
          </td>

          <td><?= $total_jam_display; ?></td>
          <td><?= $status_final_badge; ?></td>
        </tr>
      <?php endforeach; ?>
    </table>

  </div>
</div>

<!-- Modal Export Excel -->
<div class="modal" id="exampleModal" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Export Excel Rekap Presensi Harian</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form action="<?= base_url('admin/presensi/rekap_harian_excel.php') ?>" method="POST">
        <div class="modal-body">
          <div class="mb-3">
            <label for="">Tanggal Awal</label>
            <input type="date" class="form-control" name="tanggal_dari" required>
          </div>
          <div class="mb-3">
            <label for="">Tanggal Akhir</label>
            <input type="date" class="form-control" name="tanggal_sampai" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn me-auto" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Export</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include('../layout/footer.php'); ?>
