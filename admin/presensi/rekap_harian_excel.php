<?php 
ob_start();
session_start();

// 1. Proteksi Halaman & Koneksi
if(!isset($_SESSION['login'])){
  header('Location: ../../auth/login.php?pesan=belum_login');
  exit;
} else if($_SESSION['role'] != 'Admin'){
  header('Location: ../../auth/login.php?pesan=tolak_akses');
  exit;
}

include_once('../../config.php');
require('../../assets/vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

// 2. Ambil Parameter Tanggal
$tanggal_dari = $_POST['tanggal_dari'];
$tanggal_sampai = $_POST['tanggal_sampai'];

// 3. Query Data Presensi - AMBIL STATUS LANGSUNG DARI DB
$result = mysqli_query($connection, "
  SELECT presensi.*, trainee.nama, trainee.nip 
  FROM presensi 
  JOIN trainee ON trainee.id = presensi.id_trainee 
  WHERE tanggal_masuk BETWEEN '$tanggal_dari' AND '$tanggal_sampai' 
  ORDER BY tanggal_masuk DESC
");

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// 4. Header Laporan
$sheet->setCellValue('A1', 'REKAP PRESENSI TRAINEE');
$sheet->setCellValue('A2', 'Periode: ' . $tanggal_dari . ' s/d ' . $tanggal_sampai);
$sheet->mergeCells('A1:K1');
$sheet->mergeCells('A2:K2');

$columns = ['NO', 'NAMA', 'NIP', 'TANGGAL MASUK', 'JAM MASUK', 'FOTO MASUK', 'TANGGAL KELUAR', 'JAM KELUAR', 'FOTO PULANG', 'TOTAL JAM KERJA', 'STATUS'];
$sheet->fromArray($columns, NULL, 'A5');
$sheet->getStyle('A5:K5')->getFont()->setBold(true);

$no = 1;
$row = 6;
$hari_ini = date('Y-m-d');

// 5. Looping Data
while($data = mysqli_fetch_array($result)){
    $jam_masuk_raw  = trim((string)($data['jam_masuk'] ?? ''));
    $jam_keluar_raw = trim((string)($data['jam_keluar'] ?? '00:00:00'));
    $tanggal_raw    = trim((string)($data['tanggal_masuk'] ?? ''));
    $status_db      = $data['status']; // JANGKAR KITA

    $val_jam_pulang = "-";
    $val_total_jam = "";
    $val_ket_foto_k = "";

    // Logika Total Jam Kerja
    if ($jam_keluar_raw !== '00:00:00' && !empty($jam_keluar_raw)) {
        $val_jam_pulang = $jam_keluar_raw;
        $ts_masuk = strtotime($tanggal_raw . ' ' . $jam_masuk_raw);
        $ts_keluar = strtotime($tanggal_raw . ' ' . $jam_keluar_raw);
        if ($ts_keluar < $ts_masuk) { $ts_keluar = strtotime('+1 day', $ts_keluar); }
        $selisih = $ts_keluar - $ts_masuk;
        $val_total_jam = floor($selisih / 3600) . " jam " . floor(($selisih % 3600) / 60) . " menit";
    } else {
        if ($tanggal_raw < $hari_ini) {
            $val_total_jam = "Tidak presensi pulang";
            $val_ket_foto_k = "Tidak presensi pulang";
        } else {
            $val_total_jam = "Sedang bekerja";
            $val_ket_foto_k = "Belum presensi pulang";
        }
    }

    // ISI DATA KE EXCEL
    $sheet->setCellValue('A'.$row, $no);
    $sheet->setCellValue('B'.$row, $data['nama']);
    $sheet->setCellValue('C'.$row, $data['nip']);
    $sheet->setCellValue('D'.$row, $data['tanggal_masuk']);
    $sheet->setCellValue('E'.$row, $data['jam_masuk']);
    $sheet->setCellValue('G'.$row, ($data['tanggal_keluar'] == '0000-00-00' ? '-' : $data['tanggal_keluar']));
    $sheet->setCellValue('H'.$row, $val_jam_pulang); 
    $sheet->setCellValue('J'.$row, $val_total_jam); 
    $sheet->setCellValue('K'.$row, $status_db); // STATUS DIAMBIL LANGSUNG DARI DB

    // Foto Masuk
    if(!empty($data['foto_masuk']) && file_exists('../../trainee/presensi/foto/'.$data['foto_masuk'])){
        $drawM = new Drawing();
        $drawM->setPath('../../trainee/presensi/foto/'.$data['foto_masuk']);
        $drawM->setCoordinates('F'.$row); $drawM->setHeight(50); $drawM->setWorksheet($sheet);
    } else { $sheet->setCellValue('F'.$row, 'Tidak ada foto'); }

    // Foto Pulang
    if(!empty($data['foto_keluar']) && file_exists('../../trainee/presensi/foto/'.$data['foto_keluar'])){
        $drawK = new Drawing();
        $drawK->setPath('../../trainee/presensi/foto/'.$data['foto_keluar']);
        $drawK->setCoordinates('I'.$row); $drawK->setHeight(50); $drawK->setWorksheet($sheet);
    } else { $sheet->setCellValue('I'.$row, $val_ket_foto_k); }

    $sheet->getRowDimension($row)->setRowHeight(60);
    $no++; $row++;
}

foreach(range('A','K') as $col){ $sheet->getColumnDimension($col)->setAutoSize(true); }

// --- PENAMAAN FILE ---
$tgl_file_dari = date('d-m-Y', strtotime($tanggal_dari));
$tgl_file_sampai = date('d-m-Y', strtotime($tanggal_sampai));

$nama_file = ($tanggal_dari == $tanggal_sampai) 
    ? "Rekap_Presensi_Harian_" . $tgl_file_dari . ".xlsx"
    : "Rekap_Presensi_" . $tgl_file_dari . "_sd_" . $tgl_file_sampai . ".xlsx";

ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$nama_file.'"');
header('Cache-Control: max-age=0');
(new Xlsx($spreadsheet))->save('php://output');
exit;
