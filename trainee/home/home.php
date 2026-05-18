<?php 
session_start();

if(!isset($_SESSION['login'])){
  header('Location: ../../auth/login.php?pesan=belum_login');
  exit;
}
else if(!in_array($_SESSION['role'], ['trainee','Trainee'])){
  header('Location: ../../auth/login.php?pesan=tolak_akses');
  exit;
}

$judul = 'Home';
include('../layout/header.php'); 

?>

<?php
$id_trainee = $_SESSION['id'];

// 1. KOTAK HADIR (Murni yang datang dan absen pulang)
$q_hadir = mysqli_query($connection, "SELECT COUNT(*) as total FROM presensi WHERE id_trainee = '$id_trainee' AND jam_keluar IS NOT NULL AND jam_keluar != '' AND jam_keluar != '-'");
$total_hadir = mysqli_fetch_assoc($q_hadir)['total'];

// 2. KOTAK TERLAMBAT (Murni yang telat dan absen pulang)
$q_telat = mysqli_query($connection, "SELECT COUNT(*) as total FROM presensi WHERE id_trainee = '$id_trainee' AND status LIKE '%Terlambat%' AND jam_keluar IS NOT NULL AND jam_keluar != '' AND jam_keluar != '-'");
$total_telat = mysqli_fetch_assoc($q_telat)['total'];

// 3. KOTAK ALPA (Murni kasus Lupa Absen Pulang)
$q_alpa = mysqli_query($connection, "SELECT COUNT(*) as total FROM presensi WHERE id_trainee = '$id_trainee' AND (jam_keluar IS NULL OR jam_keluar = '' OR jam_keluar = '-')");
$total_alpa = mysqli_fetch_assoc($q_alpa)['total'];
?>


<style>
  .btn-grad-blue{
    border: none !important;
    color: #fff !important;
    font-weight: 700;
    border-radius: 8px;
    padding: .65rem 1.2rem;

    background: linear-gradient(135deg, #0b5aa6 0%, #0b2a4a 55%, #000 100%) !important;
    background-size: 200% 200%;
    transition: transform .15s ease, background-position .25s ease, box-shadow .25s ease;
    box-shadow: 0 12px 28px rgba(11, 90, 166, .22);
  }

  .btn-grad-blue:hover{
    background: linear-gradient(135deg, #000 0%, #0b2a4a 55%, #0b5aa6 100%) !important;
    transform: translateY(-1px);
    box-shadow: 0 16px 34px rgba(11, 90, 166, .28);
  }

  .home-hero{
    text-align:center;
    padding: 18px 0 6px;
  }
  .home-clock{
    font-size: 58px;
    font-weight: 900;
    letter-spacing: 2px;
    margin: 0;

    background: linear-gradient(135deg, #0b5aa6 0%, #0b2a4a 50%, #000000 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;

    text-shadow: 0 10px 25px rgba(11,90,166,.18);
  }
  .home-date{
    margin-top: 6px;
    font-size: 16px;
    font-weight: 700;
    color: #6b7280;
  }

  @media (max-width:576px){
    .home-clock{ font-size: 44px; }
  }

  /* ===== DARK MODE JAM (TABLER / THEME PARAM) ===== */
  html[data-theme="dark"] .home-clock,
  body[data-theme="dark"] .home-clock,
  .theme-dark .home-clock,
  html[data-bs-theme="dark"] .home-clock,
  html.dark .home-clock,
  body.dark .home-clock {
    background: linear-gradient(135deg, #ffffff 0%, #e5e7eb 50%, #9ca3af 100%) !important;
    background-size: 200% 200% !important;
    background-position: 0% 50% !important;
    -webkit-background-clip: text !important;
    background-clip: text !important;
    color: transparent !important;
    text-shadow: 0 8px 25px rgba(255,255,255,.16) !important;
  }

  .btn-grad-red{
  border: none !important;
  color: #fff !important;
  font-weight: 700;
  border-radius: 8px;
  padding: .65rem 1.2rem;

  background: linear-gradient(135deg, #b42318 0%, #7a1b13 55%, #000 100%) !important;
  background-size: 200% 200%;
  transition: transform .15s ease, background-position .25s ease, box-shadow .25s ease;
  box-shadow: 0 12px 28px rgba(180, 35, 24, .22);
}
.btn-grad-red:hover{
  background: linear-gradient(135deg, #000 0%, #7a1b13 55%, #b42318 100%) !important;
  transform: translateY(-1px);
  box-shadow: 0 16px 34px rgba(180, 35, 24, .28);
}
</style>

<?php


// Ambil lokasi presensi dari session
$lokasi_presensi = $_SESSION['lokasi_presensi'];

$latitude_kantor = "";
$longitude_kantor = "";
$radius = "";
$zona_waktu = "WIB"; 
$jam_pulang = "17:00:00"; 

$result = mysqli_query($connection, "SELECT * FROM lokasi_presensi WHERE nama_lokasi = '$lokasi_presensi'");

if ($result && mysqli_num_rows($result) > 0) {
  while($lokasi = mysqli_fetch_array($result)){
      $latitude_kantor = $lokasi['latitude'];
      $longitude_kantor = $lokasi['longitude'];
      $radius = $lokasi['radius'];
      $zona_waktu = $lokasi['zona_waktu'];
      $jam_pulang = $lokasi['jam_pulang'];
      $jam_masuk_admin = $lokasi['jam_masuk'];
  }
}

$waktu_sekarang = date('H:i:s');
$tanggal_hari_ini = date('Y-m-d');

// Set zona waktu server
if($zona_waktu == 'WIB'){
  date_default_timezone_set('Asia/Jakarta');
}
elseif($zona_waktu == 'WITA'){
  date_default_timezone_set('Asia/Makassar');
}
elseif($zona_waktu == 'WIT'){
  date_default_timezone_set('Asia/Jayapura');
}

// Ambil waktu server untuk sinkronisasi JavaScript
$server_time_now = date('Y-m-d H:i:s');
?>

<style>
  .clock-glow {
    font-size: 60px;
    font-weight: bold;
    color: #2979ff;
    text-shadow: 0 0 20px rgba(41,121,255,0.6),
                 0 0 30px rgba(41,121,255,0.4);
    transition: all 0.3s ease;
  }
  .parent-date, .parent-clock{
    display: grid;
    grid-template-columns: auto auto auto auto auto;
    text-align: center;
    justify-content: center;
  }
  .parent-date { font-size: 20px; }
  .parent-clock { font-size: 30px; font-weight: bold; }

  .force-dark .home-clock{
    background: linear-gradient(135deg, #ffffff 0%, #e5e7eb 50%, #9ca3af 100%) !important;
    -webkit-background-clip: text !important;
    background-clip: text !important;
    color: transparent !important;
    text-shadow: 0 8px 25px rgba(255,255,255,.16) !important;
  }
</style>
<!-- 
<div class="page-body">
  <div class="container-xl">
    <div class="row mb-3 mt-2">
    <div class="col-4">
        <div class="card shadow-sm border-0" style="background-color: rgba(40, 167, 69, 0.1);">
            <div class="card-body p-2 text-center">
                <div class="text-uppercase text-muted font-weight-bold" style="font-size: 10px;">Hadir</div>
                <div class="h3 mb-0 text-success font-weight-bold"><?= $total_hadir; ?></div>
            </div>
        </div>
    </div>
    <div class="col-4">
    <div class="card shadow-sm border-0" style="background-color: rgba(255, 193, 7, 0.1);">
        <div class="card-body p-2 text-center">
            <div class="text-uppercase text-muted font-weight-bold" style="font-size: 10px;">Terlambat</div>
            <div class="h3 mb-0 text-warning font-weight-bold"><?php echo $total_telat; ?></div>
        </div>
    </div>
  </div>

  <div class="col-4">
      <div class="card shadow-sm border-0" style="background-color: rgba(220, 53, 69, 0.1);">
          <div class="card-body p-2 text-center">
              <div class="text-uppercase text-muted font-weight-bold" style="font-size: 10px;">Alpa</div>
              <div class="h3 mb-0 text-danger font-weight-bold"><?php echo $total_alpa; ?></div>
          </div>
      </div>
  </div>
</div> -->

    <div class="row mb-4">
      <div class="col">
        <div class="home-hero">
          <div id="clock" class="home-clock"></div>
          <div id="date" class="home-date"></div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-2"></div>
      <div class="col-md-4">
        <div class="card text-center h-100">
          <div class="card-header">Presensi Masuk</div>
          <div class="card-body">
            <?php 
            $id_trainee = $_SESSION['id'];
            $tanggal_hari_ini = date('Y-m-d');
            $cek_presensi_masuk = mysqli_query($connection, "SELECT * FROM presensi WHERE id_trainee = '$id_trainee' AND tanggal_masuk = '$tanggal_hari_ini'");
            ?>

            <?php 
            $id_trainee = $_SESSION['id'];
            $cek_presensi_masuk = mysqli_query($connection, "SELECT * FROM presensi WHERE id_trainee = '$id_trainee' AND tanggal_masuk = '$tanggal_hari_ini'");
            ?>

            <?php if(mysqli_num_rows($cek_presensi_masuk) == 0) : ?>
                
                <?php if (strtotime($waktu_sekarang) < strtotime($jam_masuk_admin)) : ?>
                    <div class="py-3 text-center">
                        <i class="fa-solid fa-lock fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted mb-1">Presensi Masuk Belum Dibuka</h4>
                        <p class="text-muted mb-0">Akses masuk tersedia pada pukul:</p>
                        <h3 class="fw-bold text-muted"><?= date('H:i', strtotime($jam_masuk_admin)) ?></h3>
                    </div>

                <?php else : ?>
                    <div class="parent-date">
                      <div id="tanggal-masuk"></div>
                      <div class="ms-2"></div>
                      <div id="bulan-masuk"></div>
                      <div class="ms-2"></div>
                      <div id="tahun-masuk"></div>
                    </div>
                    <div class="parent-clock">
                      <div id="jam-masuk"></div>
                      <div>:</div>
                      <div id="menit-masuk"></div>
                      <div>:</div>
                      <div id="detik-masuk"></div>
                    </div>

                    <form action="<?= base_url('trainee/presensi/presensi_masuk.php') ?>" method="POST" id="form-masuk">
                      <input type="hidden" name="latitude_trainee" class="latitude-trainee">
                      <input type="hidden" name="longitude_trainee" class="longitude-trainee">
                      <input type="hidden" name="accuracy" value="">
                      <input type="hidden" value="<?= $latitude_kantor ?>" name="latitude_kantor">
                      <input type="hidden" value="<?= $longitude_kantor ?>" name="longitude_kantor">
                      <input type="hidden" value="<?= $radius ?>" name="radius">
                      <input type="hidden" value="<?= $zona_waktu ?>" name="zona_waktu">
                      <input type="hidden" value="<?= date('Y-m-d') ?>" name="tgl_masuk">
                      <input type="hidden" value="" name="jam_masuk" id="input-jam-masuk">
                      <button type="button" id="btn-home-masuk" class="btn btn-grad-blue mt-3">Masuk</button>
                      <div id="msg-masuk" class="mt-2 small text-danger" style="display:none;"></div>
                      <input type="hidden" name="masuk" value="1">
                    </form>
                <?php endif; ?>

            <?php else : ?>
                <div class="py-3 text-center">
                    <i class="fa-solid fa-circle-check fa-4x text-success mb-3"></i>
                    <h4 class="text-success">Anda sudah melakukan <br> presensi masuk.</h4>
                </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 text-center"> <div class="card-header">Presensi Pulang</div>
          <div class="card-body">
            <?php 
            $ambil_data_presensi = mysqli_query($connection, "SELECT * FROM presensi WHERE id_trainee = '$id_trainee' AND tanggal_masuk = '$tanggal_hari_ini'");
            
            // LOGIKA BARU: PAKAI GEMBOK BIAR SERAGAM
            if(strtotime($waktu_sekarang) < strtotime($jam_pulang)){ ?>
              
              <div class="py-3 text-center">
                <i class="fa-solid fa-lock fa-4x text-muted mb-3"></i>
                <h4 class="text-muted mb-1">Presensi Keluar Belum Dibuka</h4>
                <p class="text-muted mb-0">Akses keluar tersedia pada pukul:</p>
                <h3 class="fw-bold text-muted"><?= date('H:i', strtotime($jam_pulang)) ?></h3>
              </div>

            <?php } 
            else if(strtotime($waktu_sekarang) >= strtotime($jam_pulang) && mysqli_num_rows($ambil_data_presensi) == 0){ ?>
              
              <div style="text-align: center; margin-top: 5px;">
                <div style="margin-bottom: 12px;">
                    <i class="fa-regular fa-circle-xmark fa-4x text-danger"></i>
                </div>
                
                <p style="color: #182433 !important; font-weight: 600; font-size: 14px; line-height: 1.4; margin: 0;">
                    Silakan lakukan presensi masuk <br> terlebih dahulu.
                </p>
              </div>

            <?php } 
            else {

              while($cek_presensi_keluar = mysqli_fetch_array($ambil_data_presensi)){ 
                if(empty($cek_presensi_keluar['jam_keluar']) || $cek_presensi_keluar['jam_keluar'] == '00:00:00'){ ?>
                  
                  <div class="parent-date">
                    <div id="tanggal-keluar"></div>
                    <div class="ms-2"></div>
                    <div id="bulan-keluar"></div>
                    <div class="ms-2"></div>
                    <div id="tahun-keluar"></div>
                  </div>
                  <div class="parent-clock">
                    <div id="jam-keluar"></div>
                    <div>:</div>
                    <div id="menit-keluar"></div>
                    <div>:</div>
                    <div id="detik-keluar"></div>
                  </div>

                  <form action="<?= base_url('trainee/presensi/presensi_keluar.php') ?>" method="POST" id="form-keluar">
                    <input type="hidden" name="id" value="<?= $cek_presensi_keluar['id'] ?>">
                    <input type="hidden" name="latitude_trainee" class="latitude-trainee">
                    <input type="hidden" name="longitude_trainee" class="longitude-trainee">
                    <input type="hidden" value="<?= $latitude_kantor ?>" name="latitude_kantor">
                    <input type="hidden" value="<?= $longitude_kantor ?>" name="longitude_kantor">
                    <input type="hidden" value="<?= $radius ?>" name="radius">
                    <input type="hidden" value="<?= $zona_waktu ?>" name="zona_waktu">
                    <input type="hidden" value="<?= date('Y-m-d') ?>" name="tgl_keluar">
                    <input type="hidden" value="" name="jam_keluar" id="input-jam-keluar">
                    <input type="hidden" name="keluar" value="1">
                    <button type="button" id="btn-home-keluar" class="btn btn-grad-red mt-3">Keluar</button>
                    <div id="msg-keluar" class="mt-2 small text-danger" style="display:none;"></div>
                  </form>

                <?php } else { ?>
                  <i class="fa-solid fa-circle-check fa-4x text-success mt-4 mb-3"></i>                  
                  <p class="text-success fw-bold pt-1">
                      Anda sudah melakukan <br> presensi pulang.
                  </p>
              <?php } ?>
                <?php } 
              } 
            ?>
          </div>
        </div>
      </div>
      <div class="col-md-2"></div>
    </div>
  </div>
</div>

<script>
  // === LOGIKA SINKRONISASI WAKTU SERVER (ANTI-CHEAT) ===
  const serverTime = new Date("<?= $server_time_now ?>").getTime();
  const localTime = new Date().getTime();
  const diffTime = serverTime - localTime; // Selisih waktu server vs device

  function getAdjustedTime() {
    return new Date(new Date().getTime() + diffTime);
  }

  function updateClock() {
    const now = getAdjustedTime();
    const jam = String(now.getHours()).padStart(2, '0');
    const menit = String(now.getMinutes()).padStart(2, '0');
    const detik = String(now.getSeconds()).padStart(2, '0');
    
    const clockEl = document.getElementById('clock');
    if(clockEl) clockEl.textContent = `${jam}:${menit}:${detik}`;

    const hariNama = ["Minggu","Senin","Selasa","Rabu","Kamis","Jumat","Sabtu"];
    const bulanNama = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
    const hari = hariNama[now.getDay()];
    const tanggal = now.getDate();
    const bulan = bulanNama[now.getMonth()];
    const tahun = now.getFullYear();
    
    const dateEl = document.getElementById('date');
    if(dateEl) dateEl.textContent = `${hari}, ${tanggal} ${bulan} ${tahun}`;

    // Update elemen Jam Masuk jika ada
    const namaBulanIndo = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','Nopember','Desember'];
    
    if(document.getElementById("jam-masuk")){
        document.getElementById("tanggal-masuk").innerHTML = tanggal;
        document.getElementById("bulan-masuk").innerHTML = namaBulanIndo[now.getMonth()];
        document.getElementById("tahun-masuk").innerHTML = tahun;
        document.getElementById("jam-masuk").innerHTML = jam;
        document.getElementById("menit-masuk").innerHTML = menit;
        document.getElementById("detik-masuk").innerHTML = detik;
        // Set nilai input hidden agar presisi saat submit
        document.getElementById("input-jam-masuk").value = `${jam}:${menit}:${detik}`;
    }

    // Update elemen Jam Keluar jika ada
    if(document.getElementById("jam-keluar")){
        document.getElementById("tanggal-keluar").innerHTML = tanggal;
        document.getElementById("bulan-keluar").innerHTML = namaBulanIndo[now.getMonth()];
        document.getElementById("tahun-keluar").innerHTML = tahun;
        document.getElementById("jam-keluar").innerHTML = jam;
        document.getElementById("menit-keluar").innerHTML = menit;
        document.getElementById("detik-keluar").innerHTML = detik;
        // Set nilai input hidden agar presisi saat submit
        document.getElementById("input-jam-keluar").value = `${jam}:${menit}:${detik}`;
    }
  }

  setInterval(updateClock, 1000);
  updateClock();


  // === GEOLOCATION LOGIC (TIDAK BERUBAH) ===
  document.getElementById('btn-home-masuk')?.addEventListener('click', function (e) {
    e.preventDefault();
    const btn = this;
    const form = btn.closest('form');
    const msgBox = document.getElementById('msg-masuk');
    if (!form) return;

    const setMsg = (text = '', type = 'danger') => {
      if (!msgBox) return;
      msgBox.className = `mt-2 small text-${type}`;
      msgBox.style.display = text ? 'block' : 'none';
      msgBox.textContent = text;
    };

    btn.disabled = true;
    const teks = btn.innerText;
    btn.innerText = 'Mengambil lokasi...';
    setMsg('');

    navigator.geolocation.getCurrentPosition(
      (pos) => {
        if (pos.coords.accuracy > 200) {
          setMsg(`GPS tidak akurat (${Math.round(pos.coords.accuracy)}m). Gunakan Lokasi Presisi.`);
          btn.disabled = false; btn.innerText = teks;
          return;
        }
        form.querySelector('input[name="latitude_trainee"]').value = pos.coords.latitude;
        form.querySelector('input[name="longitude_trainee"]').value = pos.coords.longitude;
        form.querySelector('input[name="accuracy"]').value = pos.coords.accuracy;
        form.submit();
      },
      (err) => {
        setMsg('Gagal mengambil lokasi. Pastikan GPS aktif.');
        btn.disabled = false; btn.innerText = teks;
      },
      { enableHighAccuracy: true, timeout: 15000 }
    );
  });

  document.getElementById('btn-home-keluar')?.addEventListener('click', function (e) {
    e.preventDefault();
    const btn = this;
    const form = btn.closest('form');
    const msgBox = document.getElementById('msg-keluar');
    if (!form) return;

    btn.disabled = true;
    const teks = btn.innerText;
    btn.innerText = 'Mengambil lokasi...';

    navigator.geolocation.getCurrentPosition(
      (pos) => {
        if (pos.coords.accuracy > 200) {
          if(msgBox) { msgBox.style.display = 'block'; msgBox.textContent = "GPS tidak akurat."; }
          btn.disabled = false; btn.innerText = teks;
          return;
        }
        form.querySelector('input[name="latitude_trainee"]').value = pos.coords.latitude;
        form.querySelector('input[name="longitude_trainee"]').value = pos.coords.longitude;
        form.submit();
      },
      (err) => {
        if(msgBox) { msgBox.style.display = 'block'; msgBox.textContent = "Gagal mengambil lokasi. Pastikan GPS aktif."; }
        btn.disabled = false; btn.innerText = teks;
      },
      { enableHighAccuracy: true, timeout: 15000 }
    );
  });

  (function () {
    const params = new URLSearchParams(window.location.search);
    if (params.get('theme') === 'dark') {
      document.documentElement.classList.add('force-dark');
      document.body.classList.add('force-dark');
    }
  })();
</script>

<?php include('../layout/footer.php'); ?>
