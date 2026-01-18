-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: mysql
-- Generation Time: Jan 15, 2026 at 03:23 PM
-- Server version: 5.7.44-log
-- PHP Version: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ebd_zooloretto_821546`
--

-- --------------------------------------------------------

--
-- Table structure for table `animals`
--

CREATE TABLE `animals` (
  `id` int(10) NOT NULL,
  `idsel` int(10) DEFAULT NULL,
  `idorder` int(10) DEFAULT NULL,
  `player_id` int(10) UNSIGNED DEFAULT NULL,
  `val` varchar(32) DEFAULT NULL,
  `status` varchar(32) DEFAULT NULL,
  `x` int(10) NOT NULL,
  `y` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `animals`
--

INSERT INTO `animals` (`id`, `idsel`, `idorder`, `player_id`, `val`, `status`, `x`, `y`) VALUES
(1, NULL, 0, 2405406, 'C', 'PLAYED', 1, 2),
(2, NULL, 0, 2405406, 'C', 'PLAYED', 1, 3),
(3, NULL, 0, 0, 'C', 'AVAILABLE', 0, 0),
(4, NULL, 0, 2405407, 'C', 'PLAYED', 1, 2),
(5, NULL, 0, 0, 'C', 'AVAILABLE', 0, 0),
(6, NULL, 0, 0, 'C', 'LASTSET', 0, 0),
(7, NULL, 0, 2405407, 'C', 'PLAYED', 1, 1),
(9, NULL, 0, 0, 'CM', 'DISCARDED', 2, 1),
(11, NULL, 0, 0, 'CF', 'DISCARDED', 3, 1),
(12, NULL, 0, 0, 'E', 'LASTSET', 0, 0),
(13, NULL, 0, 2405407, 'E', 'PLAYED', 4, 2),
(14, NULL, 0, 0, 'E', 'AVAILABLE', 0, 0),
(15, NULL, 0, 2405407, 'E', 'PLAYED', 4, 4),
(16, NULL, 0, 2405407, 'E', 'PLAYED', 4, 3),
(17, NULL, 0, 2405406, 'E', 'PLAYED', 4, 2),
(18, NULL, 0, 0, 'E', 'DISCARDED', 3, 1),
(19, NULL, 0, 2405406, 'EM', 'PLAYED', 4, 1),
(20, NULL, 0, 0, 'EM', 'AVAILABLE', 0, 0),
(21, NULL, 0, 0, 'EF', 'LASTSET', 0, 0),
(22, NULL, 0, 2405407, 'EF', 'PLAYED', 4, 1),
(23, NULL, 0, 2405407, 'F', 'STALL', 0, 0),
(24, NULL, 0, 0, 'F', 'DISCARDED', 2, 2),
(25, NULL, 0, 0, 'F', 'LASTSET', 0, 0),
(26, NULL, 0, 2405406, 'F', 'PLAYED', 2, 3),
(27, NULL, 0, 0, 'F', 'DISCARDED', 3, 1),
(28, NULL, 0, 2405406, 'F', 'PLAYED', 2, 4),
(29, NULL, 0, 0, 'F', 'LASTSET', 0, 0),
(32, NULL, 0, 2405406, 'FF', 'PLAYED', 2, 1),
(33, NULL, 0, 2405406, 'FF', 'PLAYED', 2, 2),
(34, NULL, 0, 0, 'K', 'AVAILABLE', 0, 0),
(35, NULL, 0, 0, 'K', 'AVAILABLE', 0, 0),
(36, NULL, 0, 2405407, 'K', 'PLAYED', 3, 2),
(37, NULL, 0, 2405406, 'K', 'PLAYED', 3, 2),
(38, NULL, 0, 0, 'K', 'LASTSET', 0, 0),
(39, NULL, 0, 0, 'K', 'LASTSET', 0, 0),
(40, NULL, 0, 2405407, 'K', 'PLAYED', 3, 3),
(42, NULL, 0, 2405407, 'KM', 'PLAYED', 3, 1),
(43, NULL, 0, 0, 'KF', 'LASTSET', 0, 0),
(45, NULL, 0, 2405406, 'L', 'STALL', 0, 0),
(46, NULL, 0, 2405407, 'L', 'PLAYED', 2, 1),
(47, NULL, 0, 0, 'L', 'AVAILABLE', 0, 0),
(48, NULL, 0, 2405406, 'L', 'STALL', 0, 0),
(49, NULL, 0, 0, 'L', 'AVAILABLE', 0, 0),
(50, NULL, 0, 2405407, 'L', 'PLAYED', 2, 4),
(51, NULL, 0, 0, 'L', 'DISCARDED', 2, 2),
(52, NULL, 0, 0, 'LM', 'LASTSET', 0, 0),
(53, NULL, 0, 0, 'LM', 'AVAILABLE', 0, 0),
(54, NULL, 0, 2405407, 'LF', 'PLAYED', 2, 2),
(55, NULL, 0, 2405407, 'LF', 'PLAYED', 2, 3),
(89, NULL, 0, 0, 'Coin', 'WAGON', 3, 1),
(90, NULL, 0, 0, 'Coin', 'DISCARDED', 3, 1),
(91, NULL, 0, 0, 'Coin', 'DISCARDED', 3, 1),
(92, NULL, 0, 0, 'Coin', 'LASTSET', 0, 0),
(93, NULL, 0, 0, 'Coin', 'DISCARDED', 2, 2),
(94, NULL, 0, 0, 'Coin', 'LASTSET', 0, 0),
(95, NULL, 0, 0, 'Coin', 'DISCARDED', 2, 2),
(96, NULL, 0, 0, 'Coin', 'AVAILABLE', 0, 0),
(97, NULL, 0, 0, 'Coin', 'LASTSET', 0, 0),
(98, NULL, 0, 0, 'Coin', 'DISCARDED', 2, 1),
(99, NULL, 0, 0, 'Coin', 'AVAILABLE', 0, 0),
(100, NULL, 0, 0, 'Coin', 'DISCARDED', 3, 1),
(101, NULL, 0, 0, 'StallA', 'LASTSET', 0, 0),
(102, NULL, 0, 0, 'StallA', 'LASTSET', 0, 0),
(103, NULL, 0, 2405407, 'StallA', 'PLAYED', 6, 2),
(104, NULL, 0, 2405406, 'StallB', 'PLAYED', 6, 1),
(105, NULL, 0, 2405407, 'StallB', 'PLAYED', 6, 1),
(106, NULL, 0, 2405407, 'StallB', 'PLAYED', 6, 3),
(107, NULL, 0, 0, 'StallC', 'DISCARDED', 2, 1),
(108, NULL, 0, 0, 'StallC', 'AVAILABLE', 0, 0),
(109, NULL, 0, 2405407, 'StallC', 'PLAYED', 6, 5),
(110, NULL, 0, 0, 'StallD', 'LASTSET', 0, 0),
(111, NULL, 0, 2405407, 'StallD', 'PLAYED', 6, 4),
(112, NULL, 0, 0, 'StallD', 'DISCARDED', 3, 1),
(113, NULL, 0, 2405406, 'CK', 'PLAYED', 1, 5),
(114, NULL, 0, 2405406, 'KK', 'PLAYED', 3, 4),
(308, NULL, 0, 2405406, 'CM', 'PLAYED', 1, 4),
(310, NULL, 0, 2405406, 'CF', 'PLAYED', 1, 1),
(341, NULL, 0, 2405406, 'KM', 'PLAYED', 3, 1),
(344, NULL, 0, 2405406, 'KF', 'PLAYED', 3, 3);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `animals`
--
ALTER TABLE `animals`
  ADD PRIMARY KEY (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
