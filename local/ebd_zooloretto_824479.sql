-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: mysql
-- Generation Time: Jan 23, 2026 at 02:44 AM
-- Server version: 5.7.44-log
-- PHP Version: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ebd_zooloretto_824479`
--
CREATE DATABASE IF NOT EXISTS `ebd_zooloretto_824479` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `ebd_zooloretto_824479`;

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
(1, NULL, 0, 0, 'C', 'LASTSET', 0, 0),
(2, NULL, 0, 0, 'C', 'LASTSET', 0, 0),
(3, NULL, 0, 2405407, 'C', 'PLAYED', 1, 1),
(4, NULL, 0, 0, 'C', 'AVAILABLE', 0, 0),
(5, NULL, 0, 0, 'C', 'LASTSET', 0, 0),
(6, NULL, 0, 0, 'C', 'LASTSET', 0, 0),
(7, NULL, 0, 0, 'C', 'AVAILABLE', 0, 0),
(8, NULL, 0, 0, 'CM', 'AVAILABLE', 0, 0),
(9, NULL, 0, 0, 'CM', 'AVAILABLE', 0, 0),
(10, NULL, 0, 0, 'CF', 'AVAILABLE', 0, 0),
(11, NULL, 0, 0, 'CF', 'LASTSET', 0, 0),
(12, NULL, 0, 0, 'E', 'AVAILABLE', 0, 0),
(13, NULL, 0, 0, 'E', 'DISCARDED', 2, 1),
(14, NULL, 0, 0, 'E', 'AVAILABLE', 0, 0),
(15, NULL, 0, 2405406, 'E', 'PLAYED', 3, 1),
(16, NULL, 0, 0, 'E', 'AVAILABLE', 0, 0),
(17, NULL, 0, 2405407, 'E', 'PLAYED', 3, 1),
(18, NULL, 0, 2405406, 'E', 'PLAYED', 3, 2),
(19, NULL, 0, 0, 'EM', 'WAGON', 2, 2),
(20, NULL, 0, 0, 'EM', 'AVAILABLE', 0, 0),
(21, NULL, 0, 0, 'EF', 'LASTSET', 0, 0),
(22, NULL, 0, 0, 'EF', 'AVAILABLE', 0, 0),
(23, NULL, 0, 2405407, 'F', 'STALL', 0, 0),
(24, NULL, 0, 0, 'F', 'AVAILABLE', 0, 0),
(25, NULL, 0, 0, 'F', 'AVAILABLE', 0, 0),
(26, NULL, 0, 0, 'F', 'LASTSET', 0, 0),
(27, NULL, 0, 0, 'F', 'AVAILABLE', 0, 0),
(28, NULL, 0, 0, 'F', 'AVAILABLE', 0, 0),
(29, NULL, 0, 0, 'F', 'LASTSET', 0, 0),
(32, NULL, 0, 0, 'FF', 'LASTSET', 0, 0),
(33, NULL, 0, 2405406, 'FF', 'PLAYED', 2, 1),
(34, NULL, 0, 0, 'K', 'AVAILABLE', 0, 0),
(35, NULL, 0, 0, 'K', 'DISCARDED', 2, 2),
(36, NULL, 0, 0, 'K', 'AVAILABLE', 0, 0),
(37, NULL, 0, 0, 'K', 'LASTSET', 0, 0),
(38, NULL, 0, 0, 'K', 'DISCARD', 0, 0),
(39, NULL, 0, 2405406, 'K', 'STALL', 0, 0),
(40, NULL, 0, 0, 'K', 'AVAILABLE', 0, 0),
(41, NULL, 0, 0, 'KM', 'AVAILABLE', 0, 0),
(42, NULL, 0, 0, 'KM', 'DISCARDED', 2, 1),
(43, NULL, 0, 2405407, 'KF', 'PLAYED', 2, 1),
(44, NULL, 0, 0, 'KF', 'DISCARDED', 3, 1),
(45, NULL, 0, 0, 'L', 'AVAILABLE', 0, 0),
(46, NULL, 0, 2405406, 'L', 'STALL', 0, 0),
(47, NULL, 0, 0, 'L', 'AVAILABLE', 0, 0),
(48, NULL, 0, 2405406, 'L', 'PLAYED', 1, 2),
(49, NULL, 0, 0, 'L', 'DISCARDED', 2, 2),
(50, NULL, 0, 0, 'L', 'AVAILABLE', 0, 0),
(51, NULL, 0, 2405407, 'L', 'PLAYED', 4, 1),
(52, NULL, 0, 0, 'LM', 'AVAILABLE', 0, 0),
(53, NULL, 0, 0, 'LM', 'AVAILABLE', 0, 0),
(54, NULL, 0, 0, 'LF', 'AVAILABLE', 0, 0),
(55, NULL, 0, 2405406, 'LF', 'PLAYED', 1, 1),
(89, NULL, 0, 0, 'Coin', 'AVAILABLE', 0, 0),
(90, NULL, 0, 0, 'Coin', 'LASTSET', 0, 0),
(91, NULL, 0, 0, 'Coin', 'DISCARDED', 1, 3),
(92, NULL, 0, 0, 'Coin', 'DISCARDED', 1, 1),
(93, NULL, 0, 0, 'Coin', 'DISCARDED', 2, 2),
(94, NULL, 0, 0, 'Coin', 'DISCARDED', 1, 2),
(95, NULL, 0, 0, 'Coin', 'DISCARDED', 2, 2),
(96, NULL, 0, 0, 'Coin', 'DISCARDED', 3, 1),
(97, NULL, 0, 0, 'Coin', 'DISCARDED', 1, 3),
(98, NULL, 0, 0, 'Coin', 'DISCARDED', 2, 2),
(99, NULL, 0, 0, 'Coin', 'DISCARDED', 1, 3),
(100, NULL, 0, 0, 'Coin', 'AVAILABLE', 0, 0),
(101, NULL, 0, 0, 'StallA', 'AVAILABLE', 0, 0),
(102, NULL, 0, 0, 'StallA', 'AVAILABLE', 0, 0),
(103, NULL, 0, 0, 'StallA', 'LASTSET', 0, 0),
(104, NULL, 0, 0, 'StallB', 'AVAILABLE', 0, 0),
(105, NULL, 0, 0, 'StallB', 'LASTSET', 0, 0),
(106, NULL, 0, 0, 'StallB', 'AVAILABLE', 0, 0),
(107, NULL, 0, 0, 'StallC', 'AVAILABLE', 0, 0),
(108, NULL, 0, 0, 'StallC', 'LASTSET', 0, 0),
(109, NULL, 0, 0, 'StallC', 'LASTSET', 0, 0),
(110, NULL, 0, 2405406, 'StallD', 'PLAYED', 6, 2),
(111, NULL, 0, 2405406, 'StallD', 'PLAYED', 6, 1),
(112, NULL, 0, 2405407, 'StallD', 'PLAYED', 6, 2);

-- --------------------------------------------------------

--
-- Table structure for table `bga_globals`
--

CREATE TABLE `bga_globals` (
  `name` varchar(50) NOT NULL,
  `value` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `bga_user_preferences`
--

CREATE TABLE `bga_user_preferences` (
  `pgp_player` int(10) UNSIGNED NOT NULL,
  `pgp_preference_id` int(10) UNSIGNED NOT NULL,
  `pgp_value` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `bga_user_preferences`
--

INSERT INTO `bga_user_preferences` (`pgp_player`, `pgp_preference_id`, `pgp_value`) VALUES
(2405406, 100, 1),
(2405406, 200, 0),
(2405407, 100, 0),
(2405407, 200, 0);

-- --------------------------------------------------------

--
-- Table structure for table `global`
--

CREATE TABLE `global` (
  `global_id` int(10) UNSIGNED NOT NULL,
  `global_value` bigint(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `global`
--

INSERT INTO `global` (`global_id`, `global_value`) VALUES
(0, 1769133181),
(1, 8),
(2, 2405406),
(3, 120),
(4, 5314),
(5, 2405406),
(6, 52),
(7, 40),
(8, 180),
(9, 40),
(100, 2),
(200, 1),
(201, 0),
(208, 0),
(300, 999999999),
(301, 0),
(302, 0),
(304, 0),
(305, 0),
(306, 0),
(400, 0);

-- --------------------------------------------------------

--
-- Table structure for table `player`
--

CREATE TABLE `player` (
  `player_no` int(10) UNSIGNED NOT NULL,
  `player_id` int(10) UNSIGNED NOT NULL COMMENT 'Reference to metagame player id',
  `player_canal` varchar(32) NOT NULL COMMENT 'Player comet d "secret" canal',
  `player_name` varchar(32) NOT NULL,
  `player_avatar` varchar(10) NOT NULL,
  `player_color` varchar(6) NOT NULL,
  `player_score` int(10) NOT NULL DEFAULT '0',
  `player_score_aux` int(10) NOT NULL DEFAULT '0',
  `player_zombie` int(1) NOT NULL DEFAULT '0' COMMENT '1 = player is a zombie',
  `player_ai` int(1) NOT NULL DEFAULT '0' COMMENT '1 = player is an AI',
  `player_eliminated` int(1) NOT NULL DEFAULT '0' COMMENT '1 = player has been eliminated',
  `player_next_notif_no` int(10) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Next notification no to be sent to player',
  `player_enter_game` int(1) NOT NULL DEFAULT '0' COMMENT '1 = player load game view at least once',
  `player_over_time` int(1) NOT NULL DEFAULT '0',
  `player_is_multiactive` int(1) NOT NULL DEFAULT '0',
  `player_start_reflexion_time` datetime DEFAULT NULL COMMENT 'Time when the player reflexion time starts. NULL if its not this player turn',
  `player_remaining_reflexion_time` int(11) DEFAULT NULL COMMENT 'Remaining reflexion time. This does not include reflexion time for current move.',
  `player_beginner` varbinary(32) DEFAULT NULL,
  `player_state` int(10) UNSIGNED DEFAULT NULL,
  `money` int(10) DEFAULT NULL,
  `unblockedzoo` int(10) DEFAULT NULL,
  `skipped` varchar(32) DEFAULT NULL,
  `lastround` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `player`
--

INSERT INTO `player` (`player_no`, `player_id`, `player_canal`, `player_name`, `player_avatar`, `player_color`, `player_score`, `player_score_aux`, `player_zombie`, `player_ai`, `player_eliminated`, `player_next_notif_no`, `player_enter_game`, `player_over_time`, `player_is_multiactive`, `player_start_reflexion_time`, `player_remaining_reflexion_time`, `player_beginner`, `player_state`, `money`, `unblockedzoo`, `skipped`, `lastround`) VALUES
(1, 2405407, 'b815d57bade34da592d4ee1ae450f3aa', 'vagabond1', '000000', 'ff0000', 0, 0, 0, 0, 0, 1, 1, 1, 0, NULL, -286, NULL, NULL, 3, 1, 'N', 'N'),
(2, 2405406, 'c6169273db51f9cdb4f7e27bd8b68250', 'vagabond0', '000000', '008000', 0, 0, 0, 0, 0, 1, 1, 1, 0, '2026-01-23 03:53:01', -1525, NULL, NULL, 2, 0, 'N', 'N');

-- --------------------------------------------------------

--
-- Table structure for table `wagons`
--

CREATE TABLE `wagons` (
  `id` int(10) NOT NULL,
  `size` int(10) NOT NULL,
  `val1` varchar(32) DEFAULT NULL,
  `val2` varchar(32) DEFAULT NULL,
  `val3` varchar(32) DEFAULT NULL,
  `status` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `wagons`
--

INSERT INTO `wagons` (`id`, `size`, `val1`, `val2`, `val3`, `status`) VALUES
(1, 3, '', '', '', 'AVAILABLE'),
(2, 2, '', '19', '', 'AVAILABLE'),
(3, 1, '', '', '', 'AVAILABLE');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `animals`
--
ALTER TABLE `animals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bga_globals`
--
ALTER TABLE `bga_globals`
  ADD PRIMARY KEY (`name`);

--
-- Indexes for table `bga_user_preferences`
--
ALTER TABLE `bga_user_preferences`
  ADD PRIMARY KEY (`pgp_player`,`pgp_preference_id`);

--
-- Indexes for table `global`
--
ALTER TABLE `global`
  ADD PRIMARY KEY (`global_id`);

--
-- Indexes for table `player`
--
ALTER TABLE `player`
  ADD PRIMARY KEY (`player_no`),
  ADD UNIQUE KEY `player_id` (`player_id`);

--
-- Indexes for table `wagons`
--
ALTER TABLE `wagons`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `player`
--
ALTER TABLE `player`
  MODIFY `player_no` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
