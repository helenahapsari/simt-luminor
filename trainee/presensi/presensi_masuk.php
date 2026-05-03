<style>
  #map{
    height: 300px;
  }

  .btn-grad-blue{
    border: none !important;
    color: #fff !important;
    font-weight: 700;
    border-radius: 8px !important;
    padding: .65rem 1.4rem;

    /* Gradasi normal */
    background: linear-gradient(135deg, #0b5aa6 0%, #0b2a4a 55%, #000 100%) !important;

    transition: all .3s ease;
    box-shadow: 0 10px 24px rgba(11, 90, 166, .25);
  }

/* Hover kebalik */
  .btn-grad-blue:hover{
    background: linear-gradient(135deg, #000 0%, #0b2a4a 55%, #0b5aa6 100%) !important;
    transform: translateY(-2px);
    box-shadow: 0 14px 30px rgba(11, 90, 166, .35);
  }
</style>

<?php 
ob_start();
session_start();
include_once('../../config.php'); 

// --- PROTEKSI JAM DINAMIS (IKUT ATURAN ADMIN) ---
date_default_timezone_set('Asia/Jakarta');
$server_time_now = date('Y-m-d H:i:s'); // VARIABEL BARU: Patokan server
$lokasi_user = $_SESSION['lokasi_presensi'];

// Ambil jam masuk persis sesuai yang diinput Admin di database
$query_lokasi = mysqli_query($connection, "SELECT jam_masuk FROM lokasi_presensi WHERE nama_lokasi = '$lokasi_user'");
$data_lokasi = mysqli_fetch_array($query_lokasi);
$jam_buka_absen = $data_lokasi['jam_masuk']; 

$jam_sekarang = date('H:i:s');

// Jika jam sekarang lebih pagi dari jam di database, langsung tendang
if ($jam_sekarang < $jam_buka_absen) {
    $_SESSION['gagal'] = "Presensi belum dibuka! Sesuai aturan waktu, presensi baru dibuka pukul ". date('H:i', strtotime($jam_buka_absen)) ." WIB.";
    header('Location: ../home/home.php');
    exit;
}
// --- END PROTEKSI ---

if(!isset($_SESSION['login'])){
  header('Location: ../../auth/login.php?pesan=belum_login');
}
else if(!in_array($_SESSION['role'], ['trainee','Trainee'])){
  header('Location: ../../auth/login.php?pesan=tolak_akses');
}

$judul = 'Presensi Masuk';

include('../layout/header.php'); 

if(isset($_POST['masuk'])){
  $latitude_trainee = $_POST['latitude_trainee'];
  $longitude_trainee = $_POST['longitude_trainee'];
  $latitude_kantor = $_POST['latitude_kantor'];
  $longitude_kantor = $_POST['longitude_kantor'];
  $radius = $_POST['radius'];
  $zona_waktu = $_POST['zona_waktu'];
  $tgl_masuk = $_POST['tgl_masuk'];
  $jam_masuk = $_POST['jam_masuk'];
}

if(empty($latitude_trainee) || empty($longitude_trainee)){
  $_SESSION['gagal'] = 'Lokasi anda belum aktif!';
  header('Location: ../home/home.php');
  exit;
}

if(empty($latitude_kantor) || empty($longitude_kantor)){
  $_SESSION['gagal'] = 'Koordinat kantor belum disetting!';
  header('Location: ../home/home.php');
  exit;
}

$perbedaan_koordinat = $longitude_trainee - $longitude_kantor;
$jarak = sin(deg2rad($latitude_trainee)) * sin(deg2rad($latitude_kantor)) + cos(deg2rad($latitude_trainee)) * cos(deg2rad($latitude_kantor)) * cos(deg2rad($perbedaan_koordinat));
$jarak = acos($jarak);
$jarak = rad2deg($jarak);
$mil = $jarak * 60 * 1.1515;
$jarak_km = $mil * 1.609344;
$jarak_meter = $jarak_km * 1000;

if($jarak_meter > $radius){
  $_SESSION['gagal'] = 'Anda berada diluar area kantor';
  header('Location: ../home/home.php');
  exit;
}
else if($jarak_meter <= $radius){ ?>
  <div class="page-body">
    <div class="container-xl">
      <div class="row">

        <div class="col-md-6">
          <div class="card">
            <div class="card-body">
              <div id="map"></div>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card text-center">
            <div class="card-body" style="margin: auto">
              <input type="hidden" id="id" value="<?= $_SESSION['id'] ?>">
              <input type="hidden" id="tanggal_masuk" value="<?= $tgl_masuk ?>">
              <input type="hidden" id="jam_masuk" value="<?= $jam_masuk ?>">
              <div id="my_camera"></div>
              <div id="my_result"></div>
              <div class="fw-bold">
                  <?= date('d F Y', strtotime($tgl_masuk)); ?> - <span id="clock-presensi"></span>
              </div>
              <button type="button" id="btn-home-masuk" class="btn btn-grad-blue mt-3">Masuk</button>
            </div>
          </div>
        </div>
        
      </div>
    </div>
  </div>

<?php } ?>

<script language="JavaScript">
  // --- LOGIKA SINKRONISASI SERVER ---
  const serverTime = new Date("<?= $server_time_now ?>").getTime();
  const localTime = new Date().getTime();
  const diffTime = serverTime - localTime;

  function updateClock() {
      // Gunakan waktu lokal yang sudah dikoreksi selisihnya dengan server
      const now = new Date(new Date().getTime() + diffTime);
      const jam = String(now.getHours()).padStart(2, '0');
      const menit = String(now.getMinutes()).padStart(2, '0');
      const detik = String(now.getSeconds()).padStart(2, '0');
      
      const currentTimeStr = jam + ":" + menit + ":" + detik;
      document.getElementById('clock-presensi').innerHTML = currentTimeStr;
      
      // Update juga input hidden-nya biar data yang masuk ke database akurat
      document.getElementById('jam_masuk').value = currentTimeStr;
  }
  setInterval(updateClock, 1000);
  updateClock();
  // --- END LOGIKA SINKRONISASI ---

  Webcam.set({
    width: 320, height: 240, dest_width: 320, dest_height: 240,
    image_format: 'jpeg', jpeg_quality: 90, force_flash: false
  });
  Webcam.attach( '#my_camera' );
  
  document.getElementById('btn-home-masuk')?.addEventListener('click', function(){
    let id = document.getElementById('id').value;
    let tanggal_masuk = document.getElementById('tanggal_masuk').value;
    let jam_masuk = document.getElementById('jam_masuk').value;
    Webcam.snap( function(data_uri) {
      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function() {
        if (xhttp.readyState == 4 && xhttp.status == 200) {
          window.location.href = '../home/home.php';
        }
      };
      xhttp.open("POST", "presensi_masuk_aksi.php", true);
      xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
      xhttp.send(
        'photo=' + encodeURIComponent(data_uri) +
        '&id=' + id +
        '&tanggal_masuk=' + tanggal_masuk +
        '&jam_masuk=' + jam_masuk
      );
    });
  });

  // Map leaflet js
  let latitude_ktr = <?= $latitude_kantor ?>;
  let longitude_ktr = <?= $longitude_kantor ?>;
  let latitude_peg = <?= $latitude_trainee ?>;
  let longitude_peg = <?= $longitude_trainee ?>;
  let map = L.map('map').setView([latitude_ktr, longitude_ktr], 13);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
  }).addTo(map);

  let marker = L.marker([latitude_ktr, longitude_ktr]).addTo(map);

  let circle = L.circle([latitude_peg, longitude_peg], {
      color: 'red',
      fillColor: '#f03',
      fillOpacity: 0.5,
      radius: 500
  }).addTo(map).bindPopup('Lokasi anda saat ini').openPopup();
</script>

<?php include('../layout/footer.php'); ?>
