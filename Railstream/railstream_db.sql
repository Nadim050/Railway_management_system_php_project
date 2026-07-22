-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 02, 2026 at 09:21 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `railstream_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `train_id` int(11) DEFAULT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `transaction_id` varchar(50) DEFAULT NULL,
  `passenger_name` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `berth_pref` varchar(50) DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `train_id`, `booking_date`, `transaction_id`, `passenger_name`, `age`, `gender`, `berth_pref`, `payment_method`) VALUES
(1, 2, 12, '2026-05-28 10:30:00', 'TXN100001', 'Vinay Kumar', 24, 'Male', 'Lower Berth', 'GPay'),
(2, 2, 4,  '2026-05-28 11:15:00', 'TXN100002', 'Vinay Kumar', 24, 'Male', 'Upper Berth', 'PhonePe'),
(3, 2, 1,  '2026-05-29 09:00:00', 'TXN100003', 'Vinay Kumar', 24, 'Male', 'Side Lower', 'UPI'),
(4, 2, 1,  '2026-05-29 09:00:00', 'TXN100003', 'Priya Sharma', 22, 'Female', 'Lower Berth', 'UPI');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `message`, `status`, `created_at`) VALUES
(1, 'VINAY', 'yashuoffical09@gmail.com', 'feedback', 'Read', '2026-02-06 20:38:54'),
(2, 'vinay', 'vinay@123', 'booking', 'Read', '2026-03-23 08:52:10'),
(3, 'Vinay Kumar', 'vinay@railstream.com', 'Hi, I wanted to check if my booking TXN100003 is confirmed for the Mumbai to Delhi Express 101. Please let me know!', 'Unread', '2026-05-29 08:15:00');

-- --------------------------------------------------------

--
-- Table structure for table `search_history`
--

CREATE TABLE `search_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `origin` varchar(100) DEFAULT NULL,
  `destination` varchar(100) DEFAULT NULL,
  `search_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `search_history`
--

INSERT INTO `search_history` (`id`, `user_id`, `origin`, `destination`, `search_time`) VALUES
(1, 1, 'hospet', 'bengaluru', '2026-02-06 20:46:57'),
(2, NULL, 'hospet', 'bengaluru', '2026-02-06 20:53:15'),
(3, NULL, 'hospet', 'bengaluru', '2026-03-23 08:53:30'),
(4, 2, 'hospet', 'bengaluru', '2026-03-28 06:36:40'),
(5, 2, 'hospet', 'bengaluru', '2026-03-28 06:44:49');

-- --------------------------------------------------------

--
-- Table structure for table `trains`
--

CREATE TABLE `trains` (
  `id` int(11) NOT NULL,
  `train_name` varchar(100) NOT NULL,
  `origin` varchar(100) NOT NULL,
  `destination` varchar(100) NOT NULL,
  `departure_time` time NOT NULL,
  `travel_date` date NOT NULL DEFAULT '2026-02-07',
  `available_seats` int(11) DEFAULT 50,
  `price` decimal(10,2) NOT NULL,
  `stops` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trains`
--

INSERT INTO `trains` (`id`, `train_name`, `origin`, `destination`, `departure_time`, `travel_date`, `available_seats`, `price`) VALUES
(1, 'Express 101', 'Mumbai', 'Delhi', '10:00:00', '2026-06-01', 43, 1200.00),
(2, 'RailStream Bullet', 'Bangalore', 'Chennai', '14:30:00', '2026-06-02', 12, 850.00),
(3, 'Transit Plus', 'Mumbai', 'Pune', '08:15:00', '2026-06-03', 50, 400.00),
(4, 'Hampi Express', 'Hospet', 'Bengaluru', '21:00:00', '2026-06-04', 49, 450.00),
(5, 'Hampi Express', 'Hospet', 'Bengaluru', '21:00:00', '2026-06-10', 50, 450.00),
(6, 'Hampi Express', 'Hospet', 'Bengaluru', '21:00:00', '2026-06-15', 50, 450.00),
(7, 'Hampi Express', 'Hospet', 'Bengaluru', '21:00:00', '2026-07-14', 50, 450.00),
(8, 'Hampi Express', 'Hospet', 'Bengaluru', '21:00:00', '2026-08-16', 50, 450.00),
(9, 'Hampi Express', 'Hospet', 'Bengaluru', '21:00:00', '2026-06-18', 50, 450.00),
(10, 'Hampi Express', 'Hospet', 'Bengaluru', '21:00:00', '2026-06-20', 50, 450.00),
(11, 'SINDHANUR EXPRESS', 'hospet', 'bengaluru', '09:30:00', '2026-06-05', 50, 400.00),
(12, 'SINDHANUR EXPRESS', 'hospet', 'bengaluru', '20:30:00', '2026-06-07', 50, 350.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`) VALUES
(1, 'admin', '1234', 'admin'),
(2, 'vinay', '1234', 'vinay');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `train_id` (`train_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `search_history`
--
ALTER TABLE `search_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `trains`
--
ALTER TABLE `trains`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `search_history`
--
ALTER TABLE `search_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `trains`
--
ALTER TABLE `trains`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`train_id`) REFERENCES `trains` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Add stops column if upgrading existing DB
ALTER TABLE `trains` ADD COLUMN IF NOT EXISTS `stops` text DEFAULT NULL;

-- ========================================================
-- Daily Trains Feature
-- ========================================================

CREATE TABLE IF NOT EXISTS `daily_trains` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `train_name` varchar(100) NOT NULL,
  `train_number` varchar(20) DEFAULT NULL,
  `origin` varchar(100) NOT NULL,
  `destination` varchar(100) NOT NULL,
  `departure_time` time NOT NULL,
  `arrival_time` time DEFAULT NULL,
  `available_seats` int(11) DEFAULT 100,
  `price` decimal(10,2) NOT NULL,
  `stops` text DEFAULT NULL,
  `train_type` varchar(50) DEFAULT 'Express',
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `daily_trains` (`train_name`, `train_number`, `origin`, `destination`, `departure_time`, `arrival_time`, `available_seats`, `price`, `stops`, `train_type`) VALUES
('Rajdhani Express', '12951', 'Mumbai', 'Delhi', '16:35:00', '08:35:00', 120, 1850.00, 'Surat, Vadodara, Ratlam, Kota', 'Rajdhani'),
('Shatabdi Express', '12009', 'Mumbai', 'Pune', '06:00:00', '08:00:00', 80, 650.00, 'Dadar, Thane, Kalyan', 'Shatabdi'),
('Hampi Express', '16591', 'Hospet', 'Bengaluru', '21:00:00', '05:30:00', 200, 450.00, 'Ginigera, Bellary, Adoni, Guntakal, Dharmavaram, Hindupur', 'Express'),
('Brindavan Express', '12639', 'Chennai', 'Bengaluru', '07:50:00', '12:55:00', 90, 520.00, 'Katpadi, Ambur, Jolarpettai, Krishnarajapuram', 'Express'),
('Mysore Express', '12614', 'Delhi', 'Mysore', '22:15:00', '07:00:00', 150, 2100.00, 'Mathura, Agra, Gwalior, Bhopal, Nagpur, Hyderabad, Bangalore', 'Superfast'),
('Garib Rath', '12910', 'Hazrat Nizamuddin', 'Bangalore', '15:20:00', '05:30:00', 300, 980.00, 'Agra, Gwalior, Jhansi, Bhopal, Nagpur', 'Garib Rath'),
('Jan Shatabdi', '12051', 'Dadar', 'Madgaon', '05:20:00', '11:55:00', 100, 480.00, 'Thane, Kalyan, Ratnagiri, Kankavali', 'Jan Shatabdi');

-- Bookings table update: add daily_train_id support
ALTER TABLE `bookings` 
  ADD COLUMN IF NOT EXISTS `daily_train_id` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `journey_date` date DEFAULT NULL;
