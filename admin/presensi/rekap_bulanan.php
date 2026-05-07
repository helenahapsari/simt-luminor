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

$judul = 'Rekap Presensi Bulanan';
include('../layout/header.php'); 
include_once('../../config.php');

if(empty($_GET['filter_bulan'])){
  $bulan_sekarang = date('Y-m');
  $result = mysqli_query($connection, "
    SELECT presensi.*, trainee.nama, trainee.lokasi_presensi 
    FROM presensi 
    JOIN trainee ON trainee.id = presensi.id_trainee 
    WHERE DATE_FORMAT(tanggal_masuk, '%Y-%m') = '$bulan_sekarang' 
    ORDER BY tanggal_masuk DESC
  ");
  $bulan = $bulan_sekarang;
}
else{
  $tahun_bulan = $_GET['filter_tahun'].'-'.$_GET['filter_bulan'];
  $result = mysqli_query($connection, "
    SELECT presensi.*, trainee.nama, trainee.lokasi_presensi 
    FROM presensi 
    JOIN trainee ON trainee.id = presensi.id_trainee 
    WHERE DATE_FORMAT(tanggal_masuk, '%Y-%m') = '$tahun_bulan' 
    ORDER BY tanggal_masuk DESC
  ");
  $bulan = $tahun_bulan;
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
            <select name="filter_bulan" class="form-control">
              <option value="">-- Pilih Bulan --</option>
              <?php
              $bulanList = [
                "01" => "Januari", "02" => "Februari", "03" => "Maret", "04" => "April",
                "05" => "Mei", "06" => "Juni", "07" => "Juli", "08" => "Agustus",
                "09" => "September", "10" => "Oktober", "11" => "November", "12" => "Desember"
              ];
              foreach($bulanList as $key => $value){
                $selected = (isset($_GET['filter_bulan']) && $_GET['filter_bulan'] == $key) ? 'selected' : '';
                echo "<option value='$key' $selected>$value</option>";
              }
              ?>
            </select>

            <?php $tahunSekarang = date('Y'); ?>
            <select name="filter_tahun" class="form-control mx-2">
              <option value="">-- Pilih Tahun --</option>
              <?php for ($i = 2; $i >= 0; $i--): 
                $tahun = $tahunSekarang - $i;
                $selected = (isset($_GET['filter_tahun']) && $_GET['filter_tahun'] == $tahun) ? 'selected' : '';
              ?>
                <option value="<?= $tahun ?>" <?= $selected ?>><?= $tahun ?></option>
              <?php endfor; ?>
            </select>

            <button type="submit" class="btn btn-primary">Tampilkan</button>
            <a href="rekap_bulanan.php" class="btn btn-success mx-2">Refresh</a>
          </div>
        </form>
      </div>
    </div>

    <span>Rekap Presensi Bulan <?= date('F Y', strtotime($bulan)); ?></span>

    <div class="table-responsive mt-2">
      <table class="table table-bordered text-center align-middle">
        <thead class="table-primary">
          <tr>
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
        </thead>
        <tbody>
        <?php if(mysqli_num_rows($result) === 0){ ?>
          <tr>
            <td colspan="9">Data rekap presensi masih kosong.</td>
          </tr>
        <?php } ?>

        <?php 
        $no = 1;
        foreach($result as $rekap):
          // 1. Ambil Data Dasar
          $jam_masuk_raw  = trim((string)($rekap['jam_masuk'] ?? ''));
          $jam_keluar_raw = trim((string)($rekap['jam_keluar'] ?? '00:00:00'));
          $tanggal_raw    = trim((string)($rekap['tanggal_masuk'] ?? ''));
          $hari_ini       = date('Y-m-d');

          // 2. Ambil Aturan Jam Masuk Kantor & Hitung Toleransi (40 Menit)
          $lokasi_presensi = $rekap['lokasi_presensi'];
          $lokasi_q = mysqli_query($connection, "SELECT jam_masuk FROM lokasi_presensi WHERE nama_lokasi = '$lokasi_presensi'");
          $lokasi_res = mysqli_fetch_array($lokasi_q);
          $jam_masuk_kantor = $lokasi_res['jam_masuk'];
          $jam_batas_telat = date('H:i:s', strtotime($jam_masuk_kantor . ' +40 minutes'));

          // 3. Tentukan Status Terlambat
          $jam_masuk_trainee = date('H:i:s', strtotime($jam_masuk_raw));
          $is_telat = strtotime($jam_masuk_trainee) > strtotime($jam_batas_telat);
          
          $diff_terlambat = strtotime($jam_masuk_trainee) - strtotime($jam_batas_telat);
          $jam_telat = floor($diff_terlambat / 3600);
          $menit_telat = floor(($diff_terlambat % 3600) / 60);

          // 4. Inisialisasi Tampilan Kolom
          $jam_pulang_display = "";
          $foto_keluar_display = "";
          $total_jam_display = "";
          $status_final_badge = "";

          // --- LOGIKA UTAMA PENENTUAN STATUS ---
          if ($jam_keluar_raw !== '00:00:00' && !empty($jam_keluar_raw)) {
              // KONDISI: SUDAH PRESENSI PULANG
              $jam_pulang_display = $jam_keluar_raw;
              
              // Hitung Total Jam Kerja
              $ts_masuk  = strtotime($tanggal_raw . ' ' . $jam_masuk_raw);
              $ts_keluar = strtotime($tanggal_raw . ' ' . $jam_keluar_raw);
              if ($ts_keluar < $ts_masuk) { $ts_keluar = strtotime('+1 day', $ts_keluar); }
              $selisih = $ts_keluar - $ts_masuk;
              $total_jam_display = floor($selisih / 3600) . " jam " . floor(($selisih % 3600) / 60) . " menit";

              // Badge dengan Teks Putih
              if (!$is_telat) {
                  $status_final_badge = "<span class='badge bg-success text-white'>On Time</span>";
              } else {
                  $status_final_badge = "<span class='badge bg-warning text-white'>Terlambat</span><br><small class='text-muted'>Telat: {$jam_telat}j {$menit_telat}m</small>";
              }
          } else {
              // KONDISI: BELUM PRESENSI PULANG
              if ($tanggal_raw < $hari_ini) {
                  // SUB-KONDISI: LEWAT HARI (Vonis Alpa)
                  $jam_pulang_display = "-";
                  $status_final_badge = "<span class='badge bg-danger text-white'>Alpa</span><br><small class='text-danger' style='font-size:10px;'>Tidak presensi pulang</small>";
                  $foto_keluar_display = "<span class='text-muted' style='font-size:11px;'>Tidak melakukan presensi pulang.</span>";
                  $total_jam_display = "<span class='text-muted' style='font-size:11px;'>Tidak presensi pulang</span>";
              } else {
                  // SUB-KONDISI: HARI INI (Sedang Bekerja)
                  $jam_pulang_display = ""; 
                  $foto_keluar_display = "<span class='text-muted' style='font-size:11px;'>Belum melakukan presensi pulang.</span>";
                  $total_jam_display = "Sedang bekerja";
                  
                  if (!$is_telat) {
                      $status_final_badge = "<span class='badge bg-success text-white'>On Time</span>";
                  } else {
                      $status_final_badge = "<span class='badge bg-warning text-white'>Terlambat</span><br><small class='text-muted'>Telat: {$jam_telat}j {$menit_telat}m</small>";
                  }
              }
          }
        ?>
          <tr>
            <td><?= $no++; ?></td>
            <td><?= htmlspecialchars($rekap['nama']); ?></td>
            <td><?= date('d F Y', strtotime($rekap['tanggal_masuk'])); ?></td>
            <td><?= $jam_masuk_raw; ?></td>
            <td>
              <?php 
                $path_masuk = "../../trainee/presensi/foto/".$rekap['foto_masuk'];
                if(!empty($rekap['foto_masuk']) && file_exists($path_masuk)): ?>
                <img src="<?= $path_masuk; ?>" alt="foto masuk" width="60" style="border-radius:5px; object-fit:cover;">
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td><?= $jam_pulang_display; ?></td>
            <td>
              <?php 
                $path_keluar = "../../trainee/presensi/foto/".$rekap['foto_keluar'];
                if(!empty($rekap['foto_keluar']) && file_exists($path_keluar)): ?>
                <img src="<?= $path_keluar; ?>" alt="foto pulang" width="60" style="border-radius:5px; object-fit:cover;">
              <?php else: echo $foto_keluar_display; endif; ?>
            </td>
            <td><?= $total_jam_display; ?></td>
            <td><?= $status_final_badge; ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Export Excel -->
<div class="modal" id="exampleModal" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Export Excel Rekap Presensi Bulanan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="<?= base_url('admin/presensi/rekap_bulanan_excel.php') ?>" method="POST">
        <div class="modal-body">
          <div class="mb-3">
            <label>Bulan</label>
            <select name="filter_bulan" class="form-control" required>
              <option value="">-- Pilih Bulan --</option>
              <?php foreach($bulanList as $key => $value): ?>
                <option value="<?= $key ?>"><?= $value ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label>Tahun</label>
            <select name="filter_tahun" class="form-control" required>
              <option value="">-- Pilih Tahun --</option>
              <?php for ($i = 2; $i >= 0; $i--): 
                $tahun = $tahunSekarang - $i; ?>
                <option value="<?= $tahun ?>"><?= $tahun ?></option>
              <?php endfor; ?>
            </select>
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
