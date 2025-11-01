-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 29, 2025 at 06:02 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nnic`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_penugasan`
--

CREATE TABLE `admin_penugasan` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `kategori_id` int(11) NOT NULL,
  `gedung_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_penugasan`
--

INSERT INTO `admin_penugasan` (`id`, `user_id`, `kategori_id`, `gedung_id`) VALUES
(23, 28, 1, 8),
(24, 27, 1, 8),
(25, 27, 2, 8),
(26, 27, 3, 8),
(27, 27, 4, 8),
(28, 27, 5, 8),
(29, 27, 6, 8),
(30, 27, 7, 8),
(31, 29, 1, 1),
(32, 29, 2, 1),
(33, 29, 3, 1),
(34, 29, 4, 1),
(35, 29, 5, 1),
(36, 29, 6, 1),
(37, 29, 7, 1),
(38, 30, 1, 2),
(39, 30, 2, 2),
(40, 30, 3, 2),
(41, 30, 4, 2),
(42, 30, 5, 2),
(43, 30, 6, 2),
(44, 30, 7, 2),
(45, 31, 1, 3),
(46, 31, 2, 3),
(47, 31, 3, 3),
(48, 31, 4, 3),
(49, 31, 5, 3),
(50, 31, 6, 3),
(51, 31, 7, 3),
(52, 32, 1, 1),
(53, 33, 1, 4),
(54, 33, 2, 4),
(55, 33, 3, 4),
(56, 33, 4, 4),
(57, 33, 5, 4),
(58, 33, 6, 4),
(59, 33, 7, 4);

-- --------------------------------------------------------

--
-- Table structure for table `article`
--

CREATE TABLE `article` (
  `id` int(11) NOT NULL,
  `judul` text NOT NULL,
  `isi` text NOT NULL,
  `gambar` text NOT NULL,
  `tanggal` datetime NOT NULL,
  `username` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `aspirasi`
--

CREATE TABLE `aspirasi` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `nim` varchar(15) NOT NULL,
  `jurusan` varchar(50) DEFAULT NULL,
  `kategori_id` int(11) NOT NULL,
  `gedung_id` int(11) NOT NULL,
  `isi_aspirasi` text NOT NULL,
  `status` enum('Menunggu','Diproses','Selesai') DEFAULT 'Menunggu',
  `is_flagged` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=Normal, 1=Di-flag oleh Monitor',
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `aspirasi`
--

INSERT INTO `aspirasi` (`id`, `nama`, `nim`, `jurusan`, `kategori_id`, `gedung_id`, `isi_aspirasi`, `status`, `is_flagged`, `tanggal`) VALUES
(31, 'rienn', '123', 'ti', 1, 8, 'panas', 'Selesai', 0, '2025-10-28 19:23:08'),
(32, 'rienn', 'aef', 'safe', 1, 1, 'sdfg', 'Selesai', 0, '2025-10-28 19:29:30'),
(33, 'rienn', '2134', 'wetr', 1, 8, 'asewe', 'Selesai', 0, '2025-10-28 19:42:59'),
(34, 'rienn', 'awte', '234', 1, 8, 'easrger', 'Selesai', 0, '2025-10-28 19:43:08'),
(35, 'rienn', '123', 'sdrt', 1, 8, 'drgehtrerw', 'Selesai', 0, '2025-10-28 19:46:16'),
(36, 'rienn', '1324', '314', 1, 1, '546456', 'Menunggu', 0, '2025-10-28 19:57:36'),
(37, 'rienn', '1243', '5645', 4, 1, 'awertret', 'Menunggu', 0, '2025-10-28 19:58:18');

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `judul` text NOT NULL,
  `gambar` text NOT NULL,
  `tanggal` date NOT NULL,
  `username` varchar(50) NOT NULL,
  `nama` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gedung`
--

CREATE TABLE `gedung` (
  `id` int(11) NOT NULL,
  `nama_gedung` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gedung`
--

INSERT INTO `gedung` (`id`, `nama_gedung`) VALUES
(1, 'Gedung A'),
(2, 'Gedung B'),
(3, 'Gedung C'),
(4, 'Gedung D'),
(5, 'Gedung E'),
(6, 'Gedung F'),
(7, 'Gedung G'),
(8, 'Gedung H');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `divisi_lama` varchar(100) DEFAULT NULL COMMENT 'Data divisi dari sistem lama'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id`, `nama_kategori`, `divisi_lama`) VALUES
(1, 'Ruang Kelas', 'Biro Umum & Sarpras'),
(2, 'Sarana & Prasarana Penunjang KBM', 'Biro Umum & Sarpras'),
(3, 'Kebersihan & Keamanan', 'Biro Umum & Sarpras'),
(4, 'Area Parkir', 'Keamanan & Parkir'),
(5, 'Layanan Akademik', 'BAA'),
(6, 'Administrasi Kampus', 'BAUK'),
(7, 'Dosen / Tenaga Kependidikan', 'SDM / Fakultas');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','mahasiswa') NOT NULL,
  `is_superadmin` tinyint(1) NOT NULL DEFAULT 0,
  `can_ubah_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=Monitor, 1=Bisa Ubah Status'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `password`, `role`, `is_superadmin`, `can_ubah_status`) VALUES
(1, 'admin', '0192023a7bbd73250516f069df18b500', 'admin', 1, 0),
(2, 'rienn', 'ab654285884385bffbdf510b24fa38ae', 'mahasiswa', 0, 0),
(27, 'admin_H', '$2y$10$AljEmj2OdIoRFZzJ/RBZUu/nyBkBChjYt4dAuqetugyVKtzeJzjU.', 'admin', 0, 0),
(28, 'RK.H', '$2y$10$gf6IQNcp.viQSQP9NuJvZuOSIFpZm/0BaMqPftFMsVOjxnWm7DD8i', 'admin', 0, 1),
(29, 'admin_A', '$2y$10$g1MOMyV35hPcjpC7Ul4myOsdW0jndWva1Dw3srWtA1hQUMQJV20KC', 'admin', 0, 0),
(30, 'admin_B', '$2y$10$MzbjjKn07AIlnkr1l17uW.SWCyP5J6GtVH5tt03f2j8JKpZrL6Gwi', 'admin', 0, 0),
(31, 'admin_C', '$2y$10$aUzkOY9/3FHMD3bce/hsWe/SZxUupYn8YTNqGxBkywkYZC.hdJ51e', 'admin', 0, 0),
(32, 'RK.A', '$2y$10$d8Rf.PPtuLHo1fNbFjoHWO9pOUprrwGQ8sZmX/vyrlw624VKfAp4y', 'admin', 0, 1),
(33, 'admin_D', '$2y$10$v7D58OuTtd1HFDldaHRCp.dV.Ce3hd875C46vJWnTlNmkB4re0O56', 'admin', 0, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_penugasan`
--
ALTER TABLE `admin_penugasan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `kategori_id` (`kategori_id`),
  ADD KEY `gedung_id` (`gedung_id`);

--
-- Indexes for table `article`
--
ALTER TABLE `article`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `aspirasi`
--
ALTER TABLE `aspirasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kategori_id` (`kategori_id`),
  ADD KEY `gedung_id` (`gedung_id`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gedung`
--
ALTER TABLE `gedung`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_penugasan`
--
ALTER TABLE `admin_penugasan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `article`
--
ALTER TABLE `article`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `aspirasi`
--
ALTER TABLE `aspirasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `gedung`
--
ALTER TABLE `gedung`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_penugasan`
--
ALTER TABLE `admin_penugasan`
  ADD CONSTRAINT `admin_penugasan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `admin_penugasan_ibfk_2` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `admin_penugasan_ibfk_3` FOREIGN KEY (`gedung_id`) REFERENCES `gedung` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `aspirasi`
--
ALTER TABLE `aspirasi`
  ADD CONSTRAINT `aspirasi_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`),
  ADD CONSTRAINT `aspirasi_ibfk_2` FOREIGN KEY (`gedung_id`) REFERENCES `gedung` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
