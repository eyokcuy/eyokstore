-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 28 Apr 2026 pada 19.52
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `eyokstore`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'MOBA', 'Multiplayer Online Battle Arena games', '2026-04-28 15:04:33'),
(2, 'Battle Royale', 'Last-man-standing survival games', '2026-04-28 15:04:33'),
(3, 'RPG', 'Role-Playing Games', '2026-04-28 15:04:33');

-- --------------------------------------------------------

--
-- Struktur dari tabel `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `publisher` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `games`
--

INSERT INTO `games` (`id`, `category_id`, `name`, `thumbnail`, `publisher`, `status`, `created_at`) VALUES
(1, 1, 'Mobile Legends: Bang Bang', NULL, 'Moonton', 'active', '2026-04-28 15:04:33'),
(2, 1, 'League of Legends: Wild Rift', NULL, 'Riot Games', 'active', '2026-04-28 15:04:33'),
(3, 2, 'PUBG Mobile', NULL, 'Tencent Games', 'active', '2026-04-28 15:04:33'),
(4, 2, 'Free Fire', NULL, 'Garena', 'active', '2026-04-28 15:04:33'),
(5, 3, 'Genshin Impact', NULL, 'miHoYo', 'active', '2026-04-28 15:04:33');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `invoice_code` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `game_uid` varchar(50) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(15,2) NOT NULL,
  `total` decimal(15,2) NOT NULL,
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transactions`
--

INSERT INTO `transactions` (`id`, `invoice_code`, `user_id`, `game_id`, `customer_name`, `game_uid`, `item_name`, `quantity`, `price`, `total`, `status`, `notes`, `created_at`) VALUES
(1, 'INV-20250101-A1B2C', 1, 1, 'John Doe', '123456789', 'Weekly Diamond Pass', 2, 50000.00, 100000.00, 'success', 'Paid via bank transfer', '2026-04-22 15:04:33'),
(2, 'INV-20250101-D3E4F', 1, 2, 'Jane Smith', '987654321', 'Crystal Pack', 1, 150000.00, 150000.00, 'pending', 'Waiting for payment', '2026-04-23 15:04:33'),
(3, 'INV-20250102-G5H6I', 2, 3, 'Bob Johnson', '555666777', 'UC Pack 1000', 3, 200000.00, 600000.00, 'success', 'Repeat customer', '2026-04-23 15:04:33'),
(4, 'INV-20250102-J7K8L', 1, 4, 'Alice Brown', '111222333', 'Diamond 500', 1, 75000.00, 75000.00, 'failed', 'Payment declined', '2026-04-24 15:04:33'),
(5, 'INV-20250103-M9N0O', 2, 1, 'Charlie Wilson', '444555666', 'Starlight Member', 1, 120000.00, 120000.00, 'success', NULL, '2026-04-24 15:04:33'),
(6, 'INV-20250103-P1Q2R', 1, 5, 'Diana Prince', '777888999', 'Genesis Crystal 300', 2, 80000.00, 160000.00, 'pending', 'Customer will pay tomorrow', '2026-04-25 15:04:33'),
(7, 'INV-20250104-S3T4U', 2, 2, 'Evan Wright', '333444555', 'Wild Core 100', 5, 25000.00, 125000.00, 'success', 'Bulk order', '2026-04-25 15:04:33'),
(8, 'INV-20250104-V5W6X', 1, 3, 'Fiona Green', '666777888', 'Royal Pass', 1, 180000.00, 180000.00, 'success', NULL, '2026-04-26 15:04:33'),
(9, 'INV-20250105-Y7Z8A', 2, 4, 'George Hall', '222333444', 'Elite Pass', 2, 90000.00, 180000.00, 'failed', 'Wrong game UID', '2026-04-27 15:04:33'),
(10, 'INV-20250105-B9C0D', 1, 5, 'Hannah Lee', '999000111', 'Blessing of the Welkin Moon', 3, 65000.00, 195000.00, 'success', 'Monthly subscription', '2026-04-28 15:04:33');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `role` enum('admin','operator') DEFAULT 'operator',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indeks untuk tabel `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_code` (`invoice_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `game_id` (`game_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `games`
--
ALTER TABLE `games`
  ADD CONSTRAINT `games_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Ketidakleluasaan untuk tabel `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
