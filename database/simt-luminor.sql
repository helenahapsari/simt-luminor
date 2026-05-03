-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 13 Mar 2026 pada 18.04
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `simt-luminor`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `divisi`
--

CREATE TABLE `divisi` (
  `id` int(11) NOT NULL,
  `nama_divisi` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `divisi`
--

INSERT INTO `divisi` (`id`, `nama_divisi`) VALUES
(4, 'Accounting'),
(5, 'Sales Marketing'),
(6, 'Human Resource Development'),
(7, 'Housekeeping'),
(8, 'Engineering'),
(9, 'FB Product'),
(10, 'FB Service'),
(11, 'Front Office');

-- --------------------------------------------------------

--
-- Struktur dari tabel `ketidakhadiran`
--

CREATE TABLE `ketidakhadiran` (
  `id` int(11) NOT NULL,
  `id_trainee` int(11) NOT NULL,
  `keterangan` varchar(225) NOT NULL,
  `tanggal` date NOT NULL,
  `deskripsi` varchar(225) NOT NULL,
  `file` varchar(225) NOT NULL,
  `status_pengajuan` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `ketidakhadiran`
--

INSERT INTO `ketidakhadiran` (`id`, `id_trainee`, `keterangan`, `tanggal`, `deskripsi`, `file`, `status_pengajuan`) VALUES
(13, 39, 'Izin', '2026-03-13', 'Izin tidak hadir karena ada bimbingan di kampus. ', 'EnergiCare.png', 'Approve'),
(14, 18, 'Sakit', '2026-03-12', 'Sakit tipes', 'Canva 500 Design Milestone Badge CERTIFICATE.png', 'Approve');

-- --------------------------------------------------------

--
-- Struktur dari tabel `lokasi_presensi`
--

CREATE TABLE `lokasi_presensi` (
  `id` int(11) NOT NULL,
  `nama_lokasi` varchar(225) NOT NULL,
  `alamat_lokasi` varchar(225) NOT NULL,
  `tipe_lokasi` varchar(225) NOT NULL,
  `latitude` varchar(100) NOT NULL,
  `longitude` varchar(100) NOT NULL,
  `radius` int(11) NOT NULL,
  `zona_waktu` char(4) NOT NULL,
  `jam_masuk` time NOT NULL,
  `jam_pulang` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `lokasi_presensi`
--

INSERT INTO `lokasi_presensi` (`id`, `nama_lokasi`, `alamat_lokasi`, `tipe_lokasi`, `latitude`, `longitude`, `radius`, `zona_waktu`, `jam_masuk`, `jam_pulang`) VALUES
(5, 'Luminor Hotel Bogor Padjadjaran', 'Jl. Cidangiang No.9, RT.004/RW.05, Tegallega, Kecamatan Bogor Tengah, Kota Bogor, Jawa Barat 16127', 'Pusat', '-6.60236512982613', '106.8078548847271', 100000, 'WIB', '07:30:00', '17:00:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `presensi`
--

CREATE TABLE `presensi` (
  `id` int(11) NOT NULL,
  `id_trainee` int(11) NOT NULL,
  `tanggal_masuk` date NOT NULL,
  `jam_masuk` time NOT NULL,
  `foto_masuk` varchar(225) NOT NULL,
  `tanggal_keluar` date DEFAULT NULL,
  `jam_keluar` time DEFAULT NULL,
  `foto_keluar` varchar(225) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `presensi`
--

INSERT INTO `presensi` (`id`, `id_trainee`, `tanggal_masuk`, `jam_masuk`, `foto_masuk`, `tanggal_keluar`, `jam_keluar`, `foto_keluar`) VALUES
(21, 14, '2026-03-05', '13:21:01', 'masuk_2026-03-05_13-21-01_1.4697.png', NULL, NULL, NULL),
(22, 14, '2026-03-06', '15:53:19', 'masuk_2026-03-06_15-53-19_9.9921.png', NULL, NULL, NULL),
(23, 14, '2026-03-12', '22:23:02', 'masuk_2026-03-12_22-23-02_2.6248.png', '2026-03-12', '22:23:18', 'keluar_2026-03-12_22-23-18_8.6342.png'),
(24, 39, '2026-03-12', '23:01:13', 'masuk_2026-03-12_23-01-13_3.2078.png', NULL, NULL, NULL),
(25, 15, '2026-03-12', '23:09:57', 'masuk_2026-03-12_23-09-57_7.8485.png', '2026-03-12', '23:10:24', 'keluar_2026-03-12_23-10-24_4.7923.png');

-- --------------------------------------------------------

--
-- Struktur dari tabel `trainee`
--

CREATE TABLE `trainee` (
  `id` int(11) NOT NULL,
  `nip` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jenis_kelamin` varchar(20) NOT NULL,
  `alamat` varchar(225) NOT NULL,
  `no_handphone` varchar(20) NOT NULL,
  `nama_divisi` varchar(50) NOT NULL,
  `lokasi_presensi` varchar(50) NOT NULL,
  `foto` varchar(225) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `trainee`
--

INSERT INTO `trainee` (`id`, `nip`, `nama`, `jenis_kelamin`, `alamat`, `no_handphone`, `nama_divisi`, `lokasi_presensi`, `foto`) VALUES
(1, 'PEG-0001', 'Marisha Fitriyani', 'Perempuan', 'Jl. Cidangiang No.9, RT.004/RW.05, Tegallega, Kecamatan Bogor Tengah, Kota Bogor, Jawa Barat 16127', '081122223333', 'HRD Manager', 'Luminor Hotel Bogor Padjadjaran', 'admin-hrd.png'),
(14, 'PEG-0003', 'Syva Alyani', 'Perempuan', 'Kota Bogor, Jawa Barat', '085890245163', 'Sales Marketing', 'Luminor Hotel Bogor Padjadjaran', 'Syva.png'),
(15, 'PEG-0004', 'Keiko Thabitha Simanjuntak', 'Perempuan', 'Kota Bogor, Jawa Barat', '082315499761', 'Sales Marketing', 'Luminor Hotel Bogor Padjadjaran', 'Keiko.png'),
(16, 'PEG-0005', 'Syahrul Syaputra', 'Laki-laki', 'Kota Bogor, Jawa Barat', '085288325060', 'Front Office', 'Luminor Hotel Bogor Padjadjaran', 'Syahrul.png'),
(17, 'PEG-0006', 'Salwa Nurkaylanny', 'Perempuan', 'Kota Bogor, Jawa Barat', '085288325060', 'Engineering', 'Luminor Hotel Bogor Padjadjaran', 'Salwa.png'),
(18, 'PEG-0007', 'Arsenaldy', 'Laki-laki', 'Kota Bogor, Jawa Barat', '085288325060', 'Accounting', 'Luminor Hotel Bogor Padjadjaran', 'Arsenaldy.png'),
(19, 'PEG-0008', 'Shafiya Rahmah', 'Perempuan', 'Kota Bogor, Jawa Barat', '085288325060', 'Sales Marketing', 'Luminor Hotel Bogor Padjadjaran', 'Shafiya.png'),
(20, 'PEG-0009', 'Humaira Nur Habibah', 'Perempuan', 'Kota Bogor, Jawa Barat', '085288325060', 'Sales Marketing', 'Luminor Hotel Bogor Padjadjaran', 'Humaira.png'),
(21, 'PEG-0010', 'Darin Najidan', 'Laki-laki', 'Kota Bogor, Jawa Barat', '085288325060', 'Human Resource Development', 'Luminor Hotel Bogor Padjadjaran', 'Darin.png'),
(22, 'PEG-0011', 'Steven Raymond', 'Laki-laki', 'Kota Bogor, Jawa Barat', '085288325060', 'Accounting', 'Luminor Hotel Bogor Padjadjaran', 'Steven.png'),
(23, 'PEG-0012', 'Siti Alya Nur B', 'Perempuan', 'Kota Bogor, Jawa Barat', '085288325060', 'Engineering', 'Luminor Hotel Bogor Padjadjaran', 'Siti_Alya.png'),
(24, 'PEG-0013', 'Irene Nabila R', 'Perempuan', 'Kota Bogor, Jawa Barat', '085288325060', 'FB Product', 'Luminor Hotel Bogor Padjadjaran', 'Irene.png'),
(25, 'PEG-0014', 'Diandra Arinzqi', 'Perempuan', 'Kota Bogor, Jawa Barat', '085288325060', 'Housekeeping', 'Luminor Hotel Bogor Padjadjaran', 'Diandra.png'),
(26, 'PEG-0015', 'Putri Humaira', 'Perempuan', 'Kota Bogor, Jawa Barat', '085288325060', 'Housekeeping', 'Luminor Hotel Bogor Padjadjaran', 'Putri-Humaira.png'),
(27, 'PEG-0016', 'Alfiwa I', 'Perempuan', 'Kota Bogor, Jawa Barat', '085288325060', 'Accounting', 'Luminor Hotel Bogor Padjadjaran', 'Alfiwa.png'),
(28, 'PEG-0017', 'Alya Putri', 'Perempuan', 'Kota Bogor, Jawa Barat', '085288325060', 'Sales Marketing', 'Luminor Hotel Bogor Padjadjaran', 'Alya-Putri.png'),
(29, 'PEG-0018', 'Rara', 'Perempuan', 'Kota Bogor, Jawa Barat', '085288325060', 'FB Service', 'Luminor Hotel Bogor Padjadjaran', 'Rara.png'),
(30, 'PEG-0019', 'Zidan', 'Laki-laki', 'Kota Bogor, Jawa Barat', '085288325060', 'Accounting', 'Luminor Hotel Bogor Padjadjaran', 'Zidan.png'),
(31, 'PEG-0020', 'Ria Putri', 'Perempuan', 'Kota Bogor, Jawa Barat', '085288325060', 'Front Office', 'Luminor Hotel Bogor Padjadjaran', 'Ria-Putri.png'),
(32, 'PEG-0021', 'Riyani', 'Perempuan', 'Kota Bogor, Jawa Barat', '085288325060', 'Human Resource Development', 'Luminor Hotel Bogor Padjadjaran', 'Riyani.png'),
(33, 'PEG-0022', 'Denisa A', 'Perempuan', 'Kota Bogor, Jawa Barat', '085288325060', 'Human Resource Development', 'Luminor Hotel Bogor Padjadjaran', 'Denisa.png'),
(39, 'PEG-0023', 'Helena Dewi Hapsari', 'Perempuan', 'Jalan Sancang Dalam No. 37, Kelurahan Babakan, Kecamatan Bogor Tengah, Kota Bogor, Provinsi Jawa Barat, Indonesia, 16128. ', '085288325060', 'Sales Marketing', 'Luminor Hotel Bogor Padjadjaran', 'Semi-Formal Photo - Helena Dewi Hapsari.jpg'),
(42, 'PEG-0025', 'Kyky', 'Laki-laki', 'France', '085288325061', 'Human Resource Development', 'Luminor Hotel Bogor Padjadjaran', 'Semi-Formal Photo - Helena Dewi Hapsari.jpg'),
(43, 'PEG-0026', 'Brahim', 'Laki-laki', 'TUR', '085288325061', 'FB Service', 'Luminor Hotel Bogor Padjadjaran', 'Semi Formal Photo - Helena Dewi Hapsari.jpg');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `id_trainee` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(225) NOT NULL,
  `status` varchar(20) NOT NULL,
  `role` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `id_trainee`, `username`, `password`, `status`, `role`) VALUES
(1, 1, 'admin', '$2y$10$vo25rbPvUx2zMfwNRbb.a.hLIY...QGr1cD4v1JkdUe4Km0yEaBMi', 'Aktif', 'Admin'),
(14, 14, 'syvaalyani', '$2y$10$F4/blttF1P91ucjARahw9uCP1DHzLMMnJx7DQYCBr3SpXET5szqRm', 'Aktif', 'Trainee'),
(15, 15, 'keikothabitha', '$2y$10$KzXLKon9w6C5vV2s9z8b/.VELWw3tY.8ouIcAWYKM36O6QkoGShxi', 'Aktif', 'Trainee'),
(16, 16, 'syahrulsyaputra', '$2y$10$KKb0ik5XubeF7Dw9ck1Scu4fUUOWNOeJVa5INVbQ5gX.RKmS9TEaa', 'Aktif', 'Trainee'),
(17, 17, 'salwanurkaylanny', '$2y$10$uTFfHAoGYlD7ItkYpRnYOedhf9pi5NlVjcU41cHAHqLX4lLNqo2P.', 'Aktif', 'Trainee'),
(18, 18, 'arsenaldy', '$2y$10$sHRZAOONj/0Yw0fgZ0XPce.KlXKjCNVGxtorfWPaSJU3dzfMqFZ0G', 'Aktif', 'Trainee'),
(19, 19, 'shafiyarahmah', '$2y$10$qeKzfbWHo/k5K6hQL7CggOSjMw2EchbyOR.6mkC/.4FcODeyDE/MK', 'Aktif', 'Trainee'),
(20, 20, 'humairanur', '$2y$10$XyT3JuVFfFP9B/KcaPxccepfhKlc2Y5jeXtqFultA8OCT7DMVxjoa', 'Aktif', 'Trainee'),
(21, 21, 'darinnajidan', '$2y$10$ndOXipoYQiKYnaeL6n2Gx.ko5GsJl0zu506dGvi0px4jKF9AuEtRS', 'Aktif', 'Trainee'),
(22, 22, 'stevenraymond', '$2y$10$..3pxdVUSvIxeOuGDPLD5.mPnfZmL4LdGx6iXp6kQ7WsWb2ulTg8K', 'Aktif', 'Trainee'),
(23, 23, 'sitialyanur', '$2y$10$fxRm27ceAunmJtcXNGuF8eoKFKrAPO4hfWGF45tznYTXLvyHXge8m', 'Aktif', 'Trainee'),
(24, 24, 'irenenabila', '$2y$10$cNg4PhzuBcR7d4eMwsEu/eZhebFNc3rUyyIJuwbVNQyUkMa5EJHJG', 'Aktif', 'Trainee'),
(25, 25, 'diandraarinzqi', '$2y$10$wNsqRcAqqt9/OXR936ZBrOcNI83wMtjuhs8KnPAgpS0ajc52UoJAe', 'Aktif', 'Trainee'),
(26, 26, 'putrihumaira', '$2y$10$WoLvnVrUL/mIAfzQJo14hetnNX7wOH8fP/19vQkSzRIZZc0k.in.S', 'Aktif', 'Trainee'),
(27, 27, 'alfiwai', '$2y$10$h9yXn9v4rF442smMvcjzJu695D2VbwAXwaV0L.Bq7gi0GmxtBG6jq', 'Aktif', 'Trainee'),
(28, 28, 'alyaputri', '$2y$10$HbG9mV15uPs9X4axU6uiMuWkCwijwdZlrTp0lB4VKtTs.ZZ2D29.y', 'Aktif', 'Trainee'),
(29, 29, 'rarafbs', '$2y$10$2LxhWZ4OHZl4j0/YttP87eqIGsMr8mgkjPFyUy8SEtP6FvCUhxUMO', 'Aktif', 'Trainee'),
(30, 30, 'zidanacc', '$2y$10$9y74FSNrKdJAQrWkBAAQEu3HuPHwuop4WQFcv3XpxrWCKpzGQuiMu', 'Aktif', 'Trainee'),
(31, 31, 'riaputri', '$2y$10$7GJBkVvhboj8Fhd7AFHc7uMG7A5pXPSsB/9FdUO4RfZUIGE.tzH1i', 'Aktif', 'Trainee'),
(32, 32, 'riyanihrd', '$2y$10$VGfnd4/jXPp1AJUl96VDT.Z2.7rS2pb.yBmo4EL4XfYOOjdVtksja', 'Aktif', 'Trainee'),
(33, 33, 'denisahrd', '$2y$10$X1B7I2ny79P5DrdfNTZVT./pMdxTbogPh7lTWXcycEVvBbMd6ASs6', 'Aktif', 'Trainee'),
(39, 39, 'helenadewi', '$2y$10$w2IvB8v0HUeYw1gKzwGfp.ZylKpHbwyI7D4Y5YVxJhUU6mGEwR1E2', 'Aktif', 'Trainee');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `divisi`
--
ALTER TABLE `divisi`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `ketidakhadiran`
--
ALTER TABLE `ketidakhadiran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pegawai` (`id_trainee`);

--
-- Indeks untuk tabel `lokasi_presensi`
--
ALTER TABLE `lokasi_presensi`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `presensi`
--
ALTER TABLE `presensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pegawai` (`id_trainee`);

--
-- Indeks untuk tabel `trainee`
--
ALTER TABLE `trainee`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pegawai` (`id_trainee`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `divisi`
--
ALTER TABLE `divisi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `ketidakhadiran`
--
ALTER TABLE `ketidakhadiran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `lokasi_presensi`
--
ALTER TABLE `lokasi_presensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `presensi`
--
ALTER TABLE `presensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT untuk tabel `trainee`
--
ALTER TABLE `trainee`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `ketidakhadiran`
--
ALTER TABLE `ketidakhadiran`
  ADD CONSTRAINT `ketidakhadiran_ibfk_1` FOREIGN KEY (`id_trainee`) REFERENCES `trainee` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `presensi`
--
ALTER TABLE `presensi`
  ADD CONSTRAINT `presensi_ibfk_1` FOREIGN KEY (`id_trainee`) REFERENCES `trainee` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`id_trainee`) REFERENCES `trainee` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
