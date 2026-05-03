<?php
ob_start();
session_start();
include_once('../../config.php');
require('../../assets/vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$id = $_SESSION['id'];
$tgl_dari = $_POST['tanggal_dari'];
$tgl_sampai = $_POST['tanggal_sampai'];
$hari_ini = date('Y-m-d');

// Ambil Aturan Lokasi
$lokasi_user = $_SESSION['lokasi_presensi'];
$l_q = mysqli_query($connection, "SELECT jam_masuk FROM lokasi_presensi WHERE nama_lokasi = '$lokasi_user'");
$l_d = mysqli_fetch_array($l_q);
$jam_kantor = $l_d['jam_masuk'];
$jam_batas = date('H:i:s', strtotime($jam_kantor . ' +40 minutes'));

$result = mysqli_query($connection, "SELECT * FROM presensi WHERE id_trainee = '$id' AND tanggal_masuk BETWEEN '$tgl_dari' AND '$tgl_sampai' ORDER BY tanggal_masuk DESC");

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Header Judul
$sheet->setCellValue('A1', 'LAPORAN PRESENSI PRIBADI');
$sheet->mergeCells('A1:H1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->setCellValue('A2', 'Periode: '.$tgl_dari.' s/d '.$tgl_sampai);

// Kolom Header Tabel
$headers = ['NO', 'TANGGAL', 'JAM MASUK', 'JAM PULANG', 'TOTAL JAM KERJA', 'STATUS', 'FOTO MASUK', 'FOTO PULANG'];
$sheet->fromArray($headers, NULL, 'A4');
$sheet->getStyle('A4:H4')->getFont()->setBold(true);
$sheet->getStyle('A4:H4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');

$row = 5;
$no = 1;
while($data = mysqli_fetch_array($result)){
    $is_telat = strtotime($data['jam_masuk']) > strtotime($jam_batas);
    $diff_t = strtotime($data['jam_masuk']) - strtotime($jam_batas);
    $jam_t = floor($diff_t / 3600);
    $min_t = floor(($diff_t % 3600) / 60);
    
    // Logika Status yang SAMA dengan WEB
    if ($data['jam_keluar'] !== '00:00:00' && !empty($data['jam_keluar'])) {
        $ts_m = strtotime($data['tanggal_masuk'].' '.$data['jam_masuk']);
        $ts_k = strtotime($data['tanggal_masuk'].' '.$data['jam_keluar']);
        if($ts_k < $ts_m) $ts_k = strtotime('+1 day', $ts_k);
        $diff = $ts_k - $ts_m;
        $total_kerja = floor($diff/3600)." jam ".floor(($diff%3600)/60)." menit";
        $status = ($is_telat) ? "Terlambat (Telat: {$jam_t}j {$min_t}m)" : "On Time";
        $jam_p = $data['jam_keluar'];
    } else {
        if ($data['tanggal_masuk'] < $hari_ini) {
            $total_kerja = "Tidak presensi pulang"; $status = "ALPA"; $jam_p = "-";
        } else {
            $total_kerja = "Sedang bekerja"; $status = ($is_telat) ? "Terlambat" : "On Time"; $jam_p = "Belum melakukan presensi pulang";
        }
    }

    $sheet->setCellValue('A'.$row, $no);
    $sheet->setCellValue('B'.$row, $data['tanggal_masuk']);
    $sheet->setCellValue('C'.$row, $data['jam_masuk']);
    $sheet->setCellValue('D'.$row, $jam_p);
    $sheet->setCellValue('E'.$row, $total_kerja);
    $sheet->setCellValue('F'.$row, $status);

    // Sisipkan Foto Masuk
    if(!empty($data['foto_masuk']) && file_exists('foto/'.$data['foto_masuk'])){
        $dM = new Drawing(); $dM->setPath('foto/'.$data['foto_masuk']);
        $dM->setHeight(50); $dM->setCoordinates('G'.$row); $dM->setWorksheet($sheet);
    }
    // Sisipkan Foto Pulang
    if(!empty($data['foto_keluar']) && file_exists('foto/'.$data['foto_keluar'])){
        $dK = new Drawing(); $dK->setPath('foto/'.$data['foto_keluar']);
        $dK->setHeight(50); $dK->setCoordinates('H'.$row); $dK->setWorksheet($sheet);
    }

    $sheet->getRowDimension($row)->setRowHeight(60);
    $no++; $row++;
}

// Styling Border & Alignment
$lastRow = $row - 1;
$sheet->getStyle('A4:H'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('A4:H'.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Auto size kolom
foreach (range('A', 'H') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

$nama_user = str_replace(' ', '_', $_SESSION['nama']);
$filename = "Rekap_Presensi_" . $nama_user . "_" . date('d-m-Y') . ".xlsx";

ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$filename.'"');
(new Xlsx($spreadsheet))->save('php://output');
exit;