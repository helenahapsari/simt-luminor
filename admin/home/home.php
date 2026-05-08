<?php 
session_start();

// 1. Set Timezone agar jam server pas dengan Jakarta
date_default_timezone_set('Asia/Jakarta');

if(!isset($_SESSION['login'])){
  header('Location: ../../auth/login.php?pesan=belum_login');
  exit;
}
else if($_SESSION['role'] != 'Admin'){
  header('Location: ../../auth/login.php?pesan=tolak_akses');
  exit;
}

include('../../config.php');
$judul = 'Home';
include('../layout/header.php');

$tanggal_hari_ini = date('Y-m-d');

/* --- LOGIKA HITUNG DATA (FIXED) --- */
// 1. Total trainee aktif (Hanya Trainee & Aktif)
$q_total = mysqli_query($connection, "SELECT COUNT(*) AS total FROM users WHERE status = 'Aktif' AND role = 'Trainee'");
$total_trainee_aktif = (int)(mysqli_fetch_assoc($q_total)['total'] ?? 0);

// 2. Hitung Tepat Waktu (Hanya Trainee)
$q_tepat = mysqli_query($connection, "SELECT COUNT(DISTINCT p.id_trainee) AS jml 
    FROM presensi p 
    JOIN users u ON p.id_trainee = u.id 
    WHERE p.tanggal_masuk = '$tanggal_hari_ini' AND p.status = 'Hadir' AND u.role = 'Trainee'");
$jml_tepat = (int)(mysqli_fetch_assoc($q_tepat)['jml'] ?? 0);

// 3. Hitung Terlambat (Hanya Trainee)
$q_telat = mysqli_query($connection, "SELECT COUNT(DISTINCT p.id_trainee) AS jml 
    FROM presensi p 
    JOIN users u ON p.id_trainee = u.id 
    WHERE p.tanggal_masuk = '$tanggal_hari_ini' AND p.status = 'Terlambat' AND u.role = 'Trainee'");
$jml_telat = (int)(mysqli_fetch_assoc($q_telat)['jml'] ?? 0);

$total_kehadiran = $jml_tepat + $jml_telat;

// 4. Jumlah Sakit/Izin (Hanya Trainee)
$q_sic = mysqli_query($connection, "SELECT COUNT(DISTINCT k.id_trainee) AS jml 
    FROM ketidakhadiran k 
    JOIN users u ON k.id_trainee = u.id 
    WHERE k.tanggal = '$tanggal_hari_ini' AND u.role = 'Trainee'");
$jumlah_sakit_izin_cuti = (int)(mysqli_fetch_assoc($q_sic)['jml'] ?? 0);

// 5. JUMLAH ALPA (PASTI SINKRON)
$jumlah_alpa = $total_trainee_aktif - ($total_kehadiran + $jumlah_sakit_izin_cuti);
if ($jumlah_alpa < 0) $jumlah_alpa = 0;

// 6. Jumlah Alpa (Total Aktif - Semua yang ada datanya)
$jumlah_alpa = $total_trainee_aktif - ($total_kehadiran + $jumlah_sakit_izin_cuti);
if ($jumlah_alpa < 0) $jumlah_alpa = 0;
?>

<style>
  .home-hero { text-align:center; padding: 25px 0 15px; }
  .home-clock {
    font-size: 64px; font-weight: 900; letter-spacing: 2px; margin: 0;
    background: linear-gradient(135deg, #0b5aa6 0%, #0b2a4a 50%, #000000 100%);
    -webkit-background-clip: text; background-clip: text; color: transparent;
    text-shadow: 0 10px 25px rgba(11,90,166,.18);
  }

  /* --- TAMBAHAN KHUSUS DARK MODE (JANGAN HAPUS YG ATAS) --- */
  [data-bs-theme="dark"] .home-clock {
    background: none !important; /* Buat matiin gradient sementara */
    -webkit-text-fill-color: #ffffff !important; /* Paksa warna teks jadi putih */
    color: #ffffff !important;
    text-shadow: 0 0 15px rgba(255,255,255,.3); /* Kasih efek glow putih */
  }

  [data-bs-theme="dark"] .home-date {
    color: #ffffff !important; /* Biar tanggalnya juga ikutan terang */
  }

  .home-date { margin-top: 8px; font-size: 18px; font-weight: 700; color: #6b7280; }
  .server-badge { font-size: 11px; color: #9ca3af; font-style: italic; margin-top: 5px; display: block; }
  
  /* Layout Grid Kotak */
  .card-stat { border-radius: 12px; border: 1px solid #e5e7eb; transition: transform 0.2s; height: 100%; }
  .card-stat:hover { transform: translateY(-3px); }
  .avatar-stat { width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; border-radius: 8px; margin-bottom: 12px; }

  @media (max-width:576px) { .home-clock { font-size: 48px; } }
</style>

<div class="page-wrapper">
  <div class="page-body">
    <div class="container-xl">
      
      <div class="home-hero">
        <div id="display-clock" class="home-clock"><?= date('H:i:s'); ?></div>
        <div class="home-date"><?= date('l, d F Y'); ?></div>
        <span class="server-badge">Waktu terverifikasi oleh Waktu Server Jakarta (WIB)</span>
      </div>

      <div class="row row-cards mt-3">
        
        <div class="col-sm-6 col-lg-2">
          <div class="card card-stat">
            <div class="card-body text-center">
              <div class="avatar-stat bg-primary text-white mx-auto">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              </div>
              <div class="font-weight-medium">Total Trainee Aktif</div>
              <div class="h3 m-0"><?= $total_trainee_aktif; ?></div>
            </div>
          </div>
        </div>

        <div class="col-sm-6 col-lg-3">
          <div class="card card-stat">
            <div class="card-body text-center">
              <div class="avatar-stat bg-success text-white mx-auto">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
              </div>
              <div class="font-weight-medium text-success">Total Hadir</div>
              <div class="h3 m-0"><?= $total_kehadiran; ?></div>
            </div>
          </div>
        </div>

        <div class="col-sm-6 col-lg-2">
          <div class="card card-stat">
            <div class="card-body text-center">
              <div class="avatar-stat bg-warning text-white mx-auto">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              </div>
              <div class="font-weight-medium text-warning">Terlambat</div>
              <div class="h3 m-0"><?= $jml_telat; ?></div>
            </div>
          </div>
        </div>

        <div class="col-sm-6 col-lg-2">
          <div class="card card-stat">
            <div class="card-body text-center">
              <div class="avatar-stat bg-danger text-white mx-auto">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
              </div>
              <div class="font-weight-medium text-danger">Alpa/Tidak Hadir</div>
              <div class="h3 m-0"><?= $jumlah_alpa; ?></div>
            </div>
          </div>
        </div>

        <div class="col-sm-6 col-lg-3">
          <div class="card card-stat">
            <div class="card-body text-center">
              <div class="avatar-stat bg-info text-white mx-auto">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
              </div>
              <div class="font-weight-medium text-info">Sakit & Izin</div>
              <div class="h3 m-0"><?= $jumlah_sakit_izin_cuti; ?></div>
            </div>
          </div>
        </div>

      </div> </div>
  </div>
</div>

<script>
  /* KUNCI ANTI-CHEAT: 
     Mengambil jam, menit, dan detik dalam bentuk angka (Integer) dari PHP.
     JavaScript hanya bertugas menambahkan angka tersebut tanpa menanyakan 
     waktu sistem di laptop/perangkat user.
  */
  let h = <?= (int)date('H') ?>;
  let m = <?= (int)date('i') ?>;
  let s = <?= (int)date('s') ?>;

  function updateTime() {
    s++; 
    if (s >= 60) { s = 0; m++; }
    if (m >= 60) { m = 0; h++; }
    if (h >= 24) { h = 0; }
    
    let displayH = String(h).padStart(2, '0');
    let displayM = String(m).padStart(2, '0');
    let displayS = String(s).padStart(2, '0');
    
    document.getElementById('display-clock').innerText = displayH + ":" + displayM + ":" + displayS;
  }

  // Update visual setiap 1 detik
  setInterval(updateTime, 1000);
</script>

<?php include('../layout/footer.php'); ?>
