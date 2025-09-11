-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 08 Sep 2025 pada 04.02
-- Versi server: 10.4.28-MariaDB
-- Versi PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tespartner`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `individual_promocodes`
--

CREATE TABLE `individual_promocodes` (
  `id` int(11) NOT NULL,
  `promo_code` varchar(50) NOT NULL,
  `nama_lengkap` varchar(255) NOT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `referral_awal` varchar(255) DEFAULT NULL,
  `profil_jaringan` text DEFAULT NULL,
  `segment_industri_fokus` varchar(255) DEFAULT NULL,
  `promo_suggestion` varchar(100) DEFAULT NULL,
  `active_yn` tinyint(1) DEFAULT 1,
  `discount_pct` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `institusi_partner`
--

CREATE TABLE `institusi_partner` (
  `id` int(11) NOT NULL,
  `kode_institusi_partner` varchar(50) NOT NULL,
  `nama_institusi` varchar(255) NOT NULL,
  `nama_partner` varchar(255) NOT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `referral_awal` varchar(255) DEFAULT NULL,
  `profil_jaringan` text DEFAULT NULL,
  `segment_industri_fokus` varchar(255) DEFAULT NULL,
  `promo_suggestion` varchar(100) DEFAULT NULL,
  `active_status` tinyint(1) DEFAULT 1,
  `discount_pct` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `rtn_ac_institusi_partner`
--

CREATE TABLE `rtn_ac_institusi_partner` (
  `kode_institusi_partner` varchar(50) DEFAULT NULL,
  `nama_institusi` varchar(255) DEFAULT NULL,
  `nama_partner` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(25) DEFAULT NULL,
  `referral_awal` text DEFAULT NULL,
  `profil_jaringan` text DEFAULT NULL,
  `segment_industri_fokus` text DEFAULT NULL,
  `promo_suggestion` varchar(4) DEFAULT NULL,
  `ACTIVE_STATUS` varchar(1) DEFAULT NULL,
  `discount_pct` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `rtn_ac_institusi_partner`
--

INSERT INTO `rtn_ac_institusi_partner` (`kode_institusi_partner`, `nama_institusi`, `nama_partner`, `email`, `password`, `referral_awal`, `profil_jaringan`, `segment_industri_fokus`, `promo_suggestion`, `ACTIVE_STATUS`, `discount_pct`) VALUES
(NULL, 'institusitus', '', 'omkegams@gmail.com', '', '', '123123asdasd', 'Keuangin', 'OMKE', '0', 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `rtn_ac_promocodes`
--

CREATE TABLE `rtn_ac_promocodes` (
  `PROMO_CODE` varchar(50) DEFAULT NULL,
  `DISCOUNT_PCT` int(11) DEFAULT NULL,
  `ACTIVE_YN` varchar(1) DEFAULT NULL,
  `nama_lengkap` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(25) DEFAULT NULL,
  `referral_awal` text DEFAULT NULL,
  `profil_jaringan` text DEFAULT NULL,
  `segment_industri_fokus` text DEFAULT NULL,
  `promo_suggestion` varchar(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `individual_promocodes`
--
ALTER TABLE `individual_promocodes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `promo_code` (`promo_code`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `institusi_partner`
--
ALTER TABLE `institusi_partner`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_institusi_partner` (`kode_institusi_partner`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `rtn_ac_institusi_partner`
--
ALTER TABLE `rtn_ac_institusi_partner`
  ADD PRIMARY KEY (`email`);

--
-- Indeks untuk tabel `rtn_ac_promocodes`
--
ALTER TABLE `rtn_ac_promocodes`
  ADD PRIMARY KEY (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `individual_promocodes`
--
ALTER TABLE `individual_promocodes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `institusi_partner`
--
ALTER TABLE `institusi_partner`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
