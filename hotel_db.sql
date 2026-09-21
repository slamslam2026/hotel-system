-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 21 سبتمبر 2026 الساعة 20:03
-- إصدار الخادم: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hotel_db`
--

-- --------------------------------------------------------

--
-- بنية الجدول `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `room_id` int(11) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `guests` int(11) DEFAULT 1,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `customer_name`, `customer_phone`, `id_number`, `nationality`, `address`, `notes`, `room_id`, `check_in`, `check_out`, `guests`, `total_price`, `status`, `created_at`) VALUES
(8, 4, 'اسماعيل سلام', '774986454', '147852369', 'يمني', 'اب', '', 13, '2026-09-18', '2026-09-20', 2, 360.00, 'confirmed', '2026-09-18 19:58:38'),
(9, 4, 'ناصر', '122463987', '155', 'يمني', 'اب', '', 12, '2026-09-19', '2026-09-22', 2, 300.00, 'confirmed', '2026-09-19 19:54:34'),
(10, 4, 'محمد سلام', '1245766', '25454545454', 'يمني/ذكر', 'اب/جبله', 'ماسافر', 11, '2026-09-20', '2026-09-22', 1, 200.00, 'confirmed', '2026-09-19 22:48:28'),
(11, 4, 'علي صالح', '7412563', '254', 'يمني', 'اب', '', 13, '2026-09-20', '2026-09-22', 1, 360.00, 'confirmed', '2026-09-20 12:51:12');

-- --------------------------------------------------------

--
-- بنية الجدول `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `room_type_id` int(11) NOT NULL,
  `floor` int(11) DEFAULT 1,
  `status` enum('available','occupied','maintenance') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `room_type_id`, `floor`, `status`) VALUES
(11, '101', 1, 1, 'occupied'),
(12, '102', 1, 1, 'occupied'),
(13, '103', 2, 1, 'occupied'),
(14, '201', 2, 2, 'available'),
(15, '202', 3, 2, 'available'),
(16, '203', 3, 2, 'available');

-- --------------------------------------------------------

--
-- بنية الجدول `room_types`
--

CREATE TABLE `room_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 2,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `room_types`
--

INSERT INTO `room_types` (`id`, `name`, `description`, `price_per_night`, `capacity`, `image`) VALUES
(1, 'غرفة مفردة', 'غرفة بسرير واحد مناسبة لشخص واحد', 100.00, 1, NULL),
(2, 'غرفة مزدوجة', 'غرفة بسريرين مناسبة لشخصين', 180.00, 2, NULL),
(3, 'جناح فاخر', 'جناح واسع مع صالة جلوس وإطلالة', 350.00, 4, NULL),
(5, 'غرفه هادئي', 'فاخره', 1.01, 2, NULL),
(6, 'غرفه مزدوجة', 'غرفه بي سرييرين', 5.00, 2, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
(1, 'اسماعيل سلام', 'ahmed@test.com', '774986454', '$2y$10$UjRh5udIZmLmwQrt3l4rfOQSt6YLWy65fsMZhQt1FACUbb7rMHi1u', 'admin', '2026-09-15 18:53:35'),
(2, 'محمد علي', 'mohammed@test.com', '0501234568', '$2y$10$8eUT45dUqe/86KEDkjQEzuCfULzqngzhNIBr2JMxmUWrNdf49hXzO', 'customer', '2026-09-15 22:54:00'),
(3, 'مدير الفندق', 'admin@test.com', '0500000000', '$2y$10$ZteirSdNXC71cYRB3.VFHeeIMCzeG/qtINPb.PGiAo06vcekDel6e', 'customer', '2026-09-17 17:59:41'),
(4, 'مدير النظام', 'admin2@test.com', '0500000000', '$2y$10$V1WHdHr5rYP.fzWAGceOU./be.riuYzb2OHBiszCSNWHdvnPy6mfK', 'admin', '2026-09-17 21:39:17'),
(5, 'ناصر سلام', 'customer_1789690792@hotel.local', '21454', '$2y$10$m/qvNS7E1ef9tLf1yiZKSerRyWrcs9a8ZMmwR4aNjqO7vnXYIZVGi', 'customer', '2026-09-18 00:19:52'),
(6, 'اسماعيل سلام', 'slamslam@gmail.com', '774986454', '$2y$10$Lh3pcnviO0ScDDUcd81IuOWucQclFLHddoLxzzujfPoGL5Y4tLf.u', 'customer', '2026-09-19 21:36:09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`),
  ADD KEY `room_type_id` (`room_type_id`);

--
-- Indexes for table `room_types`
--
ALTER TABLE `room_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `room_types`
--
ALTER TABLE `room_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- قيود الجداول المُلقاة.
--

--
-- قيود الجداول `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
