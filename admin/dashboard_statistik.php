<?php
session_start();
ob_start();
require_once('../config.php'); // Path ke config lo
include('layout/header.php'); // Path ke header admin
?>

<style>
  /* KITA TARGETKAN LANGSUNG CARD YANG ADA DI DASHBOARD */
  [data-bs-theme="dark"] .card, 
  [data-bs-theme="dark"] .bg-white {
      background-color: #1b2434 !important; /* Warna gelap Tabler */
      color: #ffffff !important;
  }

  /* KHUSUS BUAT INPUT TANGGALNYA */
  [data-bs-theme="dark"] .form-control,
  [data-bs-theme="dark"] input[type="date"] {
      background-color: #141b29 !important;
      color: #ffffff !important;
      border: 1px solid #2d3a54 !important;
  }

  /* BIAR TULISAN PERIODE GAK ILANG */
  [data-bs-theme="dark"] label, 
  [data-bs-theme="dark"] .form-label,
  [data-bs-theme="dark"] strong {
      color: #ffffff !important;
  }

  /* ICON KALENDER JADI PUTIH */
  [data-bs-theme="dark"] input[type="date"]::-webkit-calendar-picker-indicator {
      filter: invert(1) brightness(100%) !important;
  }
</style>

<?php

// Proteksi: Harus Login Admin
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'Admin') {
    header('Location: ../../auth/login.php?pesan=tolak_akses');
    exit;
}

// Inisialisasi Filter & Waktu
$filter_tanggal = $_GET['tanggal'] ?? date('Y-m-d'); 
$filter_bulan = date('m', strtotime($filter_tanggal));
$filter_tahun = date('Y', strtotime($filter_tanggal));

// --- 1. DATA SNAPSHOT HARI INI (FIX TOTAL FINAL) ---

$tepat = 0;
$telat = 0;
$izin = 0;
$hadir_ids = [];

// Ambil semua presensi hari ini
$q_all = mysqli_query($connection, "
SELECT 
    p.*, 
    t.lokasi_presensi,
    l.jam_masuk as jam_kantor
FROM presensi p
JOIN trainee t ON t.id = p.id_trainee
JOIN lokasi_presensi l ON l.nama_lokasi = t.lokasi_presensi
WHERE DATE(p.tanggal_masuk) = '$filter_tanggal'
");

while ($row = mysqli_fetch_assoc($q_all)) {

    $id_trainee = $row['id_trainee'];
    $jam_masuk = $row['jam_masuk'];
    $jam_keluar = $row['jam_keluar'];
    $tanggal = $row['tanggal_masuk'];
    $jam_kantor = $row['jam_kantor'];

    $hari_ini = date('Y-m-d');

    // Tandain dia pernah presensi
    $hadir_ids[] = $id_trainee;

    // CEK: kalau sudah lewat hari dan tidak presensi pulang → ALPA
    if ($tanggal < $hari_ini && (empty($jam_keluar) || $jam_keluar == '00:00:00')) {
        continue; // SKIP → nanti dihitung sebagai alpa
    }

    // Kalau ada jam masuk → hitung hadir
    if (!empty($jam_masuk)) {

        $batas_telat = date('H:i:s', strtotime("+40 minutes", strtotime($jam_kantor)));
        $is_telat = strtotime($jam_masuk) > strtotime($batas_telat);

        if ($is_telat) {
            $telat++;
        } else {
            $tepat++;
        }

    }
}

// Ambil IZIN / SAKIT (yang APPROVE)
$q_izin = mysqli_query($connection, "
SELECT DISTINCT id_trainee
FROM ketidakhadiran
WHERE status_pengajuan = 'Approve'
AND DATE(tanggal) = '$filter_tanggal'
");

$izin_ids = [];
while ($row = mysqli_fetch_assoc($q_izin)) {
    $izin_ids[] = $row['id_trainee'];
}

$izin = count($izin_ids);

// Total trainee
$q_total = mysqli_query($connection, "SELECT COUNT(*) as total FROM trainee");
$total_trainee = mysqli_fetch_assoc($q_total)['total'];

// HITUNG ALPA (FINAL PALING AMAN)
$alpa = 0;

$q_all_trainee = mysqli_query($connection, "SELECT id FROM trainee");

while ($t = mysqli_fetch_assoc($q_all_trainee)) {
    $id = $t['id'];

    if (!in_array($id, $hadir_ids) && !in_array($id, $izin_ids)) {
        $alpa++;
    }
}

// Tambahkan baris ini SEBELUM query untuk memastikan format bulan selalu 2 digit (01, 02, dst)
$m_safe = sprintf("%02d", $filter_bulan); 

// --- 2. DATA PER DIVISI (UNTUK CHART KIRI & KANAN) ---
$q_div = mysqli_query($connection, "SELECT 
    t.nama_divisi,
    COUNT(CASE WHEN p.status = 'Tepat Waktu' 
        AND (p.jam_keluar IS NOT NULL AND p.jam_keluar != '00:00:00') THEN 1 END) as h_tepat,
    COUNT(CASE WHEN p.status LIKE '%Terlambat%' 
        AND (p.jam_keluar IS NOT NULL AND p.jam_keluar != '00:00:00') THEN 1 END) as h_telat
    FROM trainee t
    LEFT JOIN presensi p ON t.id = p.id_trainee 
        AND DATE(p.tanggal_masuk) = '$filter_tanggal'
    WHERE t.nama_divisi != 'HRD Manager'
    GROUP BY t.nama_divisi");

$divisi_data = [];
while ($row = mysqli_fetch_assoc($q_div)) {
    $div = $row['nama_divisi'];

    if (!isset($divisi_data[$div])) {
        $divisi_data[$div] = ['tepat' => 0, 'telat' => 0];
    }

    $jam_masuk = $row['jam_masuk'];
    $jam_kantor = $row['jam_kantor'];

    // LOGIKA FIX: Cukup cek jam_masuk. Begitu absen masuk, langsung masuk chart!
    if (!empty($jam_masuk)) {
        $batas_telat = date('H:i:s', strtotime($jam_kantor . ' +40 minutes'));
        $is_telat = strtotime($jam_masuk) > strtotime($batas_telat);

        if ($is_telat) {
            $divisi_data[$div]['telat']++;
        } else {
            $divisi_data[$div]['tepat']++;
        }
    }
}

// Convert ke format Chart.js (Visual tetap sama seperti yang lo minta)
// --- Convert ke format Chart.js ---
$labels_div = [];
$data_tepat_div = [];
$data_telat_div = [];
$data_total_div = [];

// Looping semua hasil dari kueri awal (agar semua divisi tetap muncul di sumbu X)
foreach ($divisi_data as $div => $val) {
    $labels_div[] = $div; // Nama divisi (Accounting, Sales, dll) tetap masuk
    $data_tepat_div[] = (int)$val['tepat']; // Isi 0 kalau belum ada data
    $data_telat_div[] = (int)$val['telat']; // Isi 0 kalau belum ada data
    $data_total_div[] = (int)($val['tepat'] + $val['telat']);
}

// --- 4. TOP 5 TRAINEE PALING RAJIN (DENGAN LOGIKA DISIPLIN) ---
$q_top = mysqli_query($connection, "SELECT 
    trainee.nama, 
    COUNT(presensi.id) as total_hadir,
    COUNT(CASE WHEN presensi.status LIKE '%Terlambat%' THEN 1 END) as total_telat
    FROM trainee
    INNER JOIN presensi ON trainee.id = presensi.id_trainee 
    WHERE MONTH(presensi.tanggal_masuk) = '$filter_bulan'
    AND YEAR(presensi.tanggal_masuk) = '$filter_tahun'
    GROUP BY trainee.id, trainee.nama
    ORDER BY total_hadir DESC, total_telat ASC
    LIMIT 5");

// Inisialisasi array kosong biar JS nggak error kalau data 0
$top_nama = [];
$top_hadir = [];
$top_telat = [];

if (mysqli_num_rows($q_top) > 0) {
    while($t = mysqli_fetch_assoc($q_top)) {
        $top_nama[] = $t['nama'];
        $top_hadir[] = $t['total_hadir'];
        $top_telat[] = $t['total_telat'];
    }
}

// --- 6. TREND KEHADIRAN 7 HARI TERAKHIR (DINAMIS) ---
$trend_labels = [];
$trend_hadir = [];

// AMBIL TANGGAL DARI FILTER (Jika tidak ada, baru pakai hari ini)
$tanggal_filter = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

for ($i = 6; $i >= 0; $i--) {
    // Menghitung mundur 6 hari dari TANGGAL FILTER, bukan dari hari ini
    $tanggal_step = date('Y-m-d', strtotime("$tanggal_filter -$i days"));

    $q_trend = mysqli_query($connection, "SELECT COUNT(*) as total 
        FROM presensi 
        WHERE DATE(tanggal_masuk) = '$tanggal_step'"); // Pakai DATE() biar aman jika kolomnya datetime

    $d_trend = mysqli_fetch_assoc($q_trend);

    $trend_labels[] = date('d M', strtotime($tanggal_step));
    $trend_hadir[] = (int)$d_trend['total'];
}

// --- 7. ANALISIS JAM MASUK (BAR 15 MENIT) ---
$q_total = mysqli_query($connection, "SELECT COUNT(*) as total FROM trainee"); // sesuaikan nama tabel lo
$d_total = mysqli_fetch_assoc($q_total);
$max_trainee = $d_total['total'];

$q_jam = mysqli_query($connection, "SELECT 
    COUNT(CASE WHEN jam_masuk BETWEEN '07:30:00' AND '07:45:59' THEN 1 END) as s1,
    COUNT(CASE WHEN jam_masuk BETWEEN '07:46:00' AND '08:00:59' THEN 1 END) as s2,
    COUNT(CASE WHEN jam_masuk BETWEEN '08:01:00' AND '08:15:59' THEN 1 END) as s3,
    COUNT(CASE WHEN jam_masuk BETWEEN '08:16:00' AND '08:30:59' THEN 1 END) as s4,
    COUNT(CASE WHEN jam_masuk > '08:30:59' THEN 1 END) as s5
    FROM presensi WHERE tanggal_masuk = '$filter_tanggal'");
$d_jam = mysqli_fetch_assoc($q_jam);

// ASLINYA: status = 'Hadir' (INI SALAH, GANTI JADI 'On Time')
// --- 8. DATA BULANAN (REPLACE BAGIAN INI) ---
$q_bulanan_fix = mysqli_query($connection, "SELECT  
    COUNT(CASE WHEN status = 'Hadir' THEN 1 END) as total_hadir_fix,  
    COUNT(CASE WHEN status LIKE '%Terlambat%' THEN 1 END) as total_telat_fix
    FROM presensi
    WHERE MONTH(tanggal_masuk) = '$filter_bulan'
    AND YEAR(tanggal_masuk) = '$filter_tahun'");

$d_bulanan_ok = mysqli_fetch_assoc($q_bulanan_fix);

// KITA PAKE NAMA BARU BIAR GAK TABRAKAN SAMA VARIABEL DI ATAS
$tepat_bulan_final = (int)$d_bulanan_ok['total_hadir_fix'];
$telat_bulan_final = (int)$d_bulanan_ok['total_telat_fix'];

?>


<div class="page-header d-print-none mb-3">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">Dashboard Monitoring & Statistik Presensi</h2>
                <div class="text-muted small mt-1">
                    Data ditarik secara aktual pada: <?= date('d/m/Y H:i:s'); ?> WIB
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card mb-4 shadow-sm border-0 bg-light">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-center">
                    <div class="col-auto"><strong>Periode:</strong></div>
                    <div class="col-md-3">
                        <input type="date" name="tanggal" class="form-control" value="<?= $filter_tanggal ?>">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Update Data</button>
                    </div>
            </div>
        </div>

        <div class="row row-cards">
            <div class="col-md-4">
                <div class="card shadow-sm"><div class="card-body">
                    <h3 class="card-title text-center">Ringkasan Status Kehadiran (Hari Ini)</h3>
                    <canvas id="chartSnapshot" style="max-height: 250px;"></canvas>
                </div></div>
            </div>
            
            <div class="col-md-8">
                <div class="card shadow-sm"><div class="card-body">
                    <h3 class="card-title">Tingkat Kehadiran Antar Divisi (Hari Ini)</h3>
                    <canvas id="chartDivisi" style="max-height: 250px;"></canvas>
                </div></div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm"><div class="card-body">
                    <h3 class="card-title text-center">Distribusi Komposisi Kehadiran Divisi (Hari Ini)</h3>
                    <canvas id="chartPieDivisi" style="max-height: 250px;"></canvas>
                </div></div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm"><div class="card-body">
                    <h3 class="card-title">Top 5 Performa Kehadiran & Kedisiplinan Trainee (Bulan Ini)</h3>
                    <canvas id="chartTopTrainee" style="max-height: 275px;"></canvas>
                </div></div>
            </div>

            <div class="col-md-12">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h3 class="card-title">Trend Kehadiran 7 Hari Terakhir</h3>
                    <canvas id="chartTrend" style="max-height: 300px;"></canvas>
                </div>
            </div>

        <div class="row">

            <div class="row">
                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h3 class="card-title text-center fw-bold">Trend Ketepatan Waktu Masuk (Hari Ini)</h3>
                            <div style="height: 300px; width: 100%;">
                                <canvas id="chartJam"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body"> <h3 class="card-title text-center fw-bold">Persentase Kedisiplinan Trainee (Bulan Ini)</h3>
                            <div class="d-flex justify-content-center align-items-center" style="height: 300px; width: 100%;">
                                <canvas id="chartDisiplin"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

<script>
// Fungsi biar angka sumbu Y nggak ada komanya (0.1, 0.2 dsb)
const yAxisConfig = {
    beginAtZero: true,
    ticks: {
        stepSize: 1,
        callback: function(value) { if (value % 1 === 0) { return value; } }
    }
};

// 1. Snapshot (Doughnut) -- Pakai (Hari Ini)
new Chart(document.getElementById('chartSnapshot'), {
    type: 'doughnut',
    plugins: [ChartDataLabels],
    data: {
        labels: ['Tepat Waktu', 'Terlambat', 'Izin/Sakit', 'Alpa'],
        datasets: [{
            data: [
                <?= $tepat ?>,
                <?= $telat ?>,
                <?= $izin ?>,
                <?= $alpa ?>
            ],
            backgroundColor: [
                '#2fb344',
                '#f59f00',
                '#467fcf',
                '#d63939'
            ]
        }]
    },
    options: {
        plugins: {
            datalabels: {
                color: '#fff', // Warna teks putih agar kontras
                font: {
                    weight: 'bold',
                    size: 13
                },
                formatter: (value, ctx) => {
                    // 1. Hitung total semua data (Tepat Waktu + Terlambat + Alpa)
                    let sum = 0;
                    let dataArr = ctx.chart.data.datasets[0].data;
                    dataArr.map(data => {
                        sum += data;
                    });

                    // 2. Hitung persentase
                    let percentage = (value * 100 / sum).toFixed(1) + "%";

                    // 3. LOGIKA UI: Jika nilainya 0, kembalikan string kosong agar tidak tampil
                    return value > 0 ? percentage : '';
                }
            },
            legend: {
                position: 'top',
            }
        }
    }
});


// 2. Disiplin Divisi (Bar) - Pakai (Hari Ini)
new Chart(document.getElementById('chartDivisi'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels_div) ?>,
        datasets: [
            { label: 'Tepat Waktu', data: <?= json_encode($data_tepat_div) ?>, backgroundColor: '#2fb344', borderRadius: 5 },
            { label: 'Terlambat', data: <?= json_encode($data_telat_div) ?>, backgroundColor: '#ff8400', borderRadius: 5 }
        ]
    },
    options: { scales: { y: yAxisConfig } }
});


// 3. Distribusi Komposisi Kehadiran Divisi (Hari Ini) - VERSI MAKSIMAL & RAPI
new Chart(document.getElementById('chartPieDivisi'), {
    type: 'pie',
    plugins: [ChartDataLabels],
    data: {
        labels: <?= json_encode($labels_div) ?>,
        datasets: [{
            data: <?= json_encode($data_total_div) ?>, 
            backgroundColor: [
                '#3f23de', '#ff8400', '#2fb344', '#cc00ff', 
                '#d63939', '#ffa2b9', '#674e24', '#33b6b4'
            ] 
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { 
                display: true, 
                position: 'top',
                labels: {
                    boxWidth: 11,
                    font: { 
                        size: 12,
                        weight: 'normal' 
                    },
                    padding: 8
                }
            },
            datalabels: {
                color: '#fff',
                font: { 
                    weight: 'bold', 
                    size: 13
                },
                anchor: 'center',
                align: 'center',
                formatter: (value, ctx) => {
                    let sum = 0;
                    let dataArr = ctx.chart.data.datasets[0].data;
                    dataArr.map(data => { sum += Number(data); });
                    if (sum === 0) return '';
                    let percentage = (value * 100 / sum);
                    if (percentage < 5) return ''; 
                    return percentage.toFixed(1) + "%";
                }
            }
        }
    }
});


// 4. Top 5 Trainee (TETEP BULANAN)
new Chart(document.getElementById('chartTopTrainee'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($top_nama) ?>,
        datasets: [
            {
                label: 'Hari Hadir',
                data: <?= json_encode($top_hadir) ?>,
                backgroundColor: '#467fcf',
                borderRadius: 5
            },
            {
                label: 'Total Terlambat',
                data: <?= json_encode($top_telat) ?>,
                backgroundColor: '#ff8400',
                borderRadius: 5
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: yAxisConfig // Pakai config yang udah lo buat di baris 143
        }
    }
});

// 6. Trend Kehadiran 7 Hari Terakhir (Line Chart)
new Chart(document.getElementById('chartTrend'), {
    type: 'line',
    data: {
        labels: <?= json_encode($trend_labels) ?>,
        datasets: [{
            label: 'Jumlah Hadir',
            data: <?= json_encode($trend_hadir) ?>,
            borderColor: '#467fcf',
            backgroundColor: 'rgba(70, 127, 207, 0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointBackgroundColor: '#467fcf'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        if (value % 2 === 0) return value;
                    }
                }
            }
        }
    }
});

// 7. Jam Masuk (Bar) - Pakai (Hari Ini)
new Chart(document.getElementById('chartJam'), {
    type: 'bar',
    data: {
        labels: ['07:30-07:45', '07:46-08:00', '08:01-08:15', '08:16-08:30', '> 08:30'],
        datasets: [{
            data: [
                <?= (int)$d_jam['s1'] ?>, 
                <?= (int)$d_jam['s2'] ?>, 
                <?= (int)$d_jam['s3'] ?>, 
                <?= (int)$d_jam['s4'] ?>, 
                <?= (int)$d_jam['s5'] ?>
            ],
            backgroundColor: [
                '#1abc9c', // Teal/Tosca (07:30-07:45) -> Bedanya kontras sama hijau!
                '#2ecc71', // Hijau (07:46-08:00)
                '#f1c40f', // Kuning (08:01-08:15)
                '#e67e22', // Oranye (08:16-08:30)
                '#e74c3c'  // Merah (> 08:30)
            ],
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                min: 0,
                // Kita bulatkan ke atas ke kelipatan 5 terdekat
                max: <?= (ceil($max_trainee / 5) * 5) ?>, 
                ticks: { 
                    stepSize: 5 
                }
            }
        }
    }
});

new Chart(document.getElementById('chartDisiplin'), {
    // ... kode lain ...
    data: {
        labels: ['Tepat Waktu', 'Terlambat'],
        datasets: [{
            // PAKAI VARIABEL BARU TADI
            data: [<?= $tepat_bulan_final ?>, <?= $telat_bulan_final ?>], 
            backgroundColor: ['#2fb344', '#f59f00']
        }]
    },
    // ... kode lain ...
    options: {
        cutout: '65%',
        plugins: {
            legend: {
                position: 'bottom'
            },
            datalabels: {
                color: '#fff',
                font: { weight: 'bold', size: 13 },
                // BIAR CHART YANG NGITUNG PERSENNYA SECARA OTOMATIS
                formatter: (value, ctx) => {
                    let sum = 0;
                    let dataArr = ctx.chart.data.datasets[0].data;
                    dataArr.map(data => { sum += data; });
                    // Kalau datanya 0, jangan tampilkan label biar nggak error NaN%
                    if (sum === 0) return null; 
                    let percentage = (value * 100 / sum).toFixed(0) + "%";
                    return percentage;
                }
            }
        }
    }
});

</script>


<?php include('layout/footer.php'); ?>

