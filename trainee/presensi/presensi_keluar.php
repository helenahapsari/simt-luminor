<script src="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.26/webcam.min.js" integrity="sha512-dQIiHSl2hr3NWKKLycPndtpbh5iaHLo6MwrXm7F0FM5e+kL2U16oE9uIwPHUl6fQBeCthiEuV/rzP3MiAB8Vfw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

<!-- Make sure you put this AFTER Leaflet's CSS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<style>
  #map{
    height: 300px;
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
ob_start();
session_start();

if(!isset($_SESSION['login'])){
  header('Location: ../../auth/login.php?pesan=belum_login');
}
else if(!in_array($_SESSION['role'], ['trainee','Trainee'])){
  header('Location: ../../auth/login.php?pesan=tolak_akses');
}

$judul = 'Presensi Pulang';

include('../layout/header.php'); 

if(isset($_POST['keluar'])){
  $id = $_POST['id'];
  $latitude_trainee = $_POST['latitude_trainee'];
  $longitude_trainee = $_POST['longitude_trainee'];
  $latitude_kantor = $_POST['latitude_kantor'];
  $longitude_kantor = $_POST['longitude_kantor'];
  $radius = $_POST['radius'];
  $zona_waktu = $_POST['zona_waktu'];
  $tgl_keluar = $_POST['tgl_keluar'];
  $jam_keluar = $_POST['jam_keluar'];
}

// 1) Kalau halaman dibuka tanpa submit form "keluar"
if(!isset($_POST['keluar'])){
  header('Location: ../home/home.php');
  exit;
}

// 2) Kalau lokasi trainee kosong
if(empty($latitude_trainee) || empty($longitude_trainee)){
  $_SESSION['gagal'] = 'Lokasi anda belum aktif!';
  header('Location: ../home/home.php');
  exit;
}

// 3) Kalau koordinat kantor kosong
if(empty($latitude_kantor) || empty($longitude_kantor)){
  $_SESSION['gagal'] = 'Koordinat kantor belum disetting!';
  header('Location: ../home/home.php');
  exit;
}

// 4) Ubah semua jadi ANGKA (float) biar aman di PHP 8
$latitude_trainee  = (float) str_replace(',', '.', $latitude_trainee);
$longitude_trainee = (float) str_replace(',', '.', $longitude_trainee);
$latitude_kantor   = (float) str_replace(',', '.', $latitude_kantor);
$longitude_kantor  = (float) str_replace(',', '.', $longitude_kantor);
$radius            = (float) str_replace(',', '.', $radius);

$perbedaan_koordinat = $longitude_trainee - $longitude_kantor;
$jarak = sin(deg2rad($latitude_trainee)) * sin(deg2rad($latitude_kantor)) + cos(deg2rad($latitude_trainee)) * cos(deg2rad($latitude_kantor)) * cos(deg2rad($perbedaan_koordinat));
$jarak = acos($jarak);
$jarak = rad2deg($jarak);
$mil = $jarak * 60 * 1.1515;
$jarak_km = $mil * 1.609344;
$jarak_meter = $jarak_km * 1000;

// echo $jarak_meter;
// echo "<br>";
// echo $radius;
// die;

if($jarak_meter > $radius){
  $_SESSION['gagal'] = 'Anda berada di luar area kantor';
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
              <input type="hidden" id="id" value="<?= $id?>">
              <input type="hidden" id="tanggal_keluar" value="<?= $tgl_keluar ?>">
              <input type="hidden" id="jam_keluar" value="<?= $jam_keluar ?>">
              <div id="my_camera"></div>
              <div id="my_result"></div>
              <div><?= date('d F Y', strtotime($tgl_keluar)).' - '.$jam_keluar; ?></div>
              <button class="btn btn-grad-red mt-2" id="ambil-foto">Keluar</button>
            </div>
          </div>
        </div>
        
      </div>
    </div>
  </div>

<?php } ?>



<script language="JavaScript">
  Webcam.set({
    width: 320,
    height: 240,
    dest_width: 320,
    dest_height: 240,
    image_format: 'jpeg',
    jpeg_quality: 90,
    force_flash: false
  });
  Webcam.attach( '#my_camera' );
  
  document.getElementById('ambil-foto').addEventListener('click', function(){
    let id = document.getElementById('id').value;
    let tanggal_keluar = document.getElementById('tanggal_keluar').value;
    let jam_keluar = document.getElementById('jam_keluar').value;
    Webcam.snap( function(data_uri) {
      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function() {
        document.getElementById('my_result').innerHTML = '<img src="'+data_uri+'"/>';
        if (xhttp.readyState == 4 && xhttp.status == 200) {
          window.location.href = '../home/home.php';
        }
      };
      xhttp.open("POST", "presensi_keluar_aksi.php", true);
      xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
      xhttp.send(
        'photo=' + encodeURIComponent(data_uri) +
        '&id=' + id +
        '&tanggal_keluar=' + tanggal_keluar +
        '&jam_keluar=' + jam_keluar
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

