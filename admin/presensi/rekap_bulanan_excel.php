<?php 
ob_start();
session_start();

if(!isset($_SESSION['login']) || $_SESSION['role'] != 'Admin'){
  header('Location: ../../auth/login.php?pesan=tolak_akses');
  exit;
}

include_once('../../config.php');
require('../../assets/vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

// 1. AMBIL DATA POST (Disesuaikan dengan name="filter_bulan" di modal)
$bulan = $_POST['filter_bulan'];
$tahun = $_POST['filter_tahun'];

$nama_bulan_indo = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$bulan_teks = $nama_bulan_indo[$bulan] ?? 'Bulan';

// 2. Query Data
$result = mysqli_query($connection, "
  SELECT presensi.*, trainee.nama, trainee.lokasi_presensi, trainee.nip 
  FROM presensi 
  JOIN trainee ON trainee.id = presensi.id_trainee 
  WHERE MONTH(tanggal_masuk) = '$bulan' AND YEAR(tanggal_masuk) = '$tahun'
  ORDER BY tanggal_masuk DESC
");

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// 3. Header Sheet
$sheet->setCellValue('A1', 'REKAP PRESENSI BULANAN');
$sheet->setCellValue('A2', "Bulan: $bulan_teks | Tahun: $tahun");
$sheet->mergeCells('A1:K1');
$sheet->mergeCells('A2:K2');

$columns = ['NO', 'NAMA', 'NIP', 'TANGGAL MASUK', 'JAM MASUK', 'FOTO MASUK', 'TANGGAL KELUAR', 'JAM KELUAR', 'FOTO PULANG', 'TOTAL JAM KERJA', 'STATUS'];
$sheet->fromArray($columns, NULL, 'A5');
$sheet->getStyle('A5:K5')->getFont()->setBold(true);

$no = 1;
$row = 6;
$hari_ini = date('Y-m-d');

while($data = mysqli_fetch_array($result)){
    $jam_masuk_raw  = trim((string)($data['jam_masuk'] ?? ''));
    $jam_keluar_raw = trim((string)($data['jam_keluar'] ?? '00:00:00'));
    $tanggal_raw    = trim((string)($data['tanggal_masuk'] ?? ''));

    // Logika Lokasi & Toleransi
    $lokasi_presensi = $data['lokasi_presensi'];
    $lokasi_q = mysqli_query($connection, "SELECT jam_masuk FROM lokasi_presensi WHERE nama_lokasi = '$lokasi_presensi'");
    $lokasi_res = mysqli_fetch_array($lokasi_q);
    $jam_masuk_kantor = $lokasi_res['jam_masuk'];
    $jam_batas_telat = date('H:i:s', strtotime($jam_masuk_kantor . ' +40 minutes'));

    $jam_masuk_trainee = date('H:i:s', strtotime($jam_masuk_raw));
    $is_telat = strtotime($jam_masuk_trainee) > strtotime($jam_batas_telat);
    $diff_telat = strtotime($jam_masuk_trainee) - strtotime($jam_batas_telat);
    $jam_t = floor($diff_telat / 3600);
    $min_t = floor(($diff_telat % 3600) / 60);

    $val_jam_pulang = "-";
    $val_total_kerja = "";
    $val_status = "";
    $val_ket_foto_k = "";

    if ($jam_keluar_raw !== '00:00:00' && !empty($jam_keluar_raw)) {
        $val_jam_pulang = $jam_keluar_raw;
        $ts_masuk = strtotime($tanggal_raw . ' ' . $jam_masuk_raw);
        $ts_keluar = strtotime($tanggal_raw . ' ' . $jam_keluar_raw);
        if ($ts_keluar < $ts_masuk) { $ts_keluar = strtotime('+1 day', $ts_keluar); }
        $selisih = $ts_keluar - $ts_masuk;
        $val_total_kerja = floor($selisih / 3600) . " jam " . floor(($selisih % 3600) / 60) . " menit";
        $val_status = (!$is_telat) ? "On Time" : "Terlambat ({$jam_t}j {$min_t}m)";
    } else {
        if ($tanggal_raw < $hari_ini) {
            $val_total_kerja = "Tidak presensi pulang";
            $val_status = "ALPA";
            $val_ket_foto_k = "Tidak presensi pulang";
        } else {
            $val_total_kerja = "Sedang bekerja";
            $val_status = (!$is_telat) ? "On Time" : "Terlambat ({$jam_t}j {$min_t}m)";
            $val_ket_foto_k = "Belum melakukan presensi pulang";
        }
    }

    $sheet->setCellValue('A'.$row, $no);
    $sheet->setCellValue('B'.$row, $data['nama']);
    $sheet->setCellValue('C'.$row, $data['nip']);
    $sheet->setCellValue('D'.$row, $data['tanggal_masuk']);
    $sheet->setCellValue('E'.$row, $data['jam_masuk']);
    $sheet->setCellValue('G'.$row, ($data['tanggal_keluar'] == '0000-00-00' ? '-' : $data['tanggal_keluar']));
    $sheet->setCellValue('H'.$row, $val_jam_pulang); 
    $sheet->setCellValue('J'.$row, $val_total_kerja); 
    $sheet->setCellValue('K'.$row, $val_status); 

    if(!empty($data['foto_masuk']) && file_exists('../../trainee/presensi/foto/'.$data['foto_masuk'])){
        $drawM = new Drawing(); $drawM->setPath('../../trainee/presensi/foto/'.$data['foto_masuk']);
        $drawM->setCoordinates('F'.$row); $drawM->setHeight(50); $drawM->setWorksheet($sheet);
    } else { $sheet->setCellValue('F'.$row, 'Tidak ada foto'); }

    if(!empty($data['foto_keluar']) && file_exists('../../trainee/presensi/foto/'.$data['foto_keluar'])){
        $drawK = new Drawing(); $drawK->setPath('../../trainee/presensi/foto/'.$data['foto_keluar']);
        $drawK->setCoordinates('I'.$row); $drawK->setHeight(50); $drawK->setWorksheet($sheet);
    } else { $sheet->setCellValue('I'.$row, $val_ket_foto_k); }

    $sheet->getRowDimension($row)->setRowHeight(60);
    $no++; $row++;
}

foreach(range('A','K') as $col){ $sheet->getColumnDimension($col)->setAutoSize(true); }

// Nama file menggunakan variabel $bulan_teks dan $tahun yang sudah ditangkap dengan benar
$nama_file = "Rekap_Presensi_Bulanan_" . $bulan_teks . "_" . $tahun . ".xlsx";

ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $nama_file . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;