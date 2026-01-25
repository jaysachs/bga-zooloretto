-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: mysql
-- Generation Time: Jan 23, 2026 at 07:30 PM
-- Server version: 5.7.44-log
-- PHP Version: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ebd_zooloretto_835900`
--
CREATE DATABASE IF NOT EXISTS `ebd_zooloretto_835900` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `ebd_zooloretto_835900`;

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
(1, NULL, 0, 0, 'C', 'AVAILABLE', 0, 0),
(2, NULL, 0, 0, 'C', 'AVAILABLE', 0, 0),
(3, NULL, 0, 0, 'C', 'AVAILABLE', 0, 0),
(4, NULL, 0, 0, 'C', 'AVAILABLE', 0, 0),
(5, NULL, 0, 0, 'C', 'AVAILABLE', 0, 0),
(6, NULL, 0, 0, 'C', 'AVAILABLE', 0, 0),
(7, NULL, 0, 0, 'C', 'AVAILABLE', 0, 0),
(8, NULL, 0, 0, 'CM', 'AVAILABLE', 0, 0),
(9, NULL, 0, 0, 'CM', 'LASTSET', 0, 0),
(10, NULL, 0, 0, 'CF', 'AVAILABLE', 0, 0),
(11, NULL, 0, 0, 'CF', 'AVAILABLE', 0, 0),
(12, NULL, 0, 0, 'E', 'LASTSET', 0, 0),
(13, NULL, 0, 0, 'E', 'AVAILABLE', 0, 0),
(14, NULL, 0, 0, 'E', 'AVAILABLE', 0, 0),
(15, NULL, 0, 0, 'E', 'LASTSET', 0, 0),
(16, NULL, 0, 0, 'E', 'AVAILABLE', 0, 0),
(17, NULL, 0, 0, 'E', 'AVAILABLE', 0, 0),
(18, NULL, 0, 0, 'E', 'LASTSET', 0, 0),
(19, NULL, 0, 2405406, 'EM', 'PLAYED', 2, 1),
(20, NULL, 0, 0, 'EM', 'AVAILABLE', 0, 0),
(21, NULL, 0, 0, 'EF', 'AVAILABLE', 0, 0),
(22, NULL, 0, 0, 'EF', 'AVAILABLE', 0, 0),
(23, NULL, 0, 0, 'F', 'AVAILABLE', 0, 0),
(24, NULL, 0, 0, 'F', 'AVAILABLE', 0, 0),
(25, NULL, 0, 0, 'F', 'AVAILABLE', 0, 0),
(26, NULL, 0, 0, 'F', 'AVAILABLE', 0, 0),
(27, NULL, 0, 0, 'F', 'AVAILABLE', 0, 0),
(28, NULL, 0, 0, 'F', 'AVAILABLE', 0, 0),
(29, NULL, 0, 0, 'F', 'LASTSET', 0, 0),
(32, NULL, 0, 0, 'FF', 'AVAILABLE', 0, 0),
(33, NULL, 0, 0, 'FF', 'AVAILABLE', 0, 0),
(34, NULL, 0, 0, 'K', 'AVAILABLE', 0, 0),
(35, NULL, 0, 0, 'K', 'LASTSET', 0, 0),
(36, NULL, 0, 0, 'K', 'AVAILABLE', 0, 0),
(37, NULL, 0, 0, 'K', 'AVAILABLE', 0, 0),
(38, NULL, 0, 0, 'K', 'AVAILABLE', 0, 0),
(39, NULL, 0, 0, 'K', 'LASTSET', 0, 0),
(40, NULL, 0, 0, 'K', 'AVAILABLE', 0, 0),
(41, NULL, 0, 0, 'KM', 'AVAILABLE', 0, 0),
(42, NULL, 0, 0, 'KM', 'LASTSET', 0, 0),
(43, NULL, 0, 0, 'KF', 'AVAILABLE', 0, 0),
(44, NULL, 0, 0, 'KF', 'AVAILABLE', 0, 0),
(45, NULL, 0, 0, 'L', 'WAGON', 1, 3),
(46, NULL, 0, 0, 'L', 'AVAILABLE', 0, 0),
(47, NULL, 0, 0, 'L', 'AVAILABLE', 0, 0),
(48, NULL, 0, 0, 'L', 'AVAILABLE', 0, 0),
(49, NULL, 0, 0, 'L', 'AVAILABLE', 0, 0),
(50, NULL, 0, 0, 'L', 'AVAILABLE', 0, 0),
(51, NULL, 0, 0, 'L', 'AVAILABLE', 0, 0),
(52, NULL, 0, 0, 'LM', 'AVAILABLE', 0, 0),
(53, NULL, 0, 0, 'LM', 'LASTSET', 0, 0),
(54, NULL, 0, 0, 'LF', 'LASTSET', 0, 0),
(55, NULL, 0, 0, 'LF', 'AVAILABLE', 0, 0),
(89, NULL, 0, 0, 'Coin', 'AVAILABLE', 0, 0),
(90, NULL, 0, 0, 'Coin', 'LASTSET', 0, 0),
(91, NULL, 0, 0, 'Coin', 'LASTSET', 0, 0),
(92, NULL, 0, 0, 'Coin', 'AVAILABLE', 0, 0),
(93, NULL, 0, 0, 'Coin', 'AVAILABLE', 0, 0),
(94, NULL, 0, 0, 'Coin', 'AVAILABLE', 0, 0),
(95, NULL, 0, 0, 'Coin', 'AVAILABLE', 0, 0),
(96, NULL, 0, 0, 'Coin', 'AVAILABLE', 0, 0),
(97, NULL, 0, 0, 'Coin', 'LASTSET', 0, 0),
(98, NULL, 0, 0, 'Coin', 'AVAILABLE', 0, 0),
(99, NULL, 0, 0, 'Coin', 'LASTSET', 0, 0),
(100, NULL, 0, 0, 'Coin', 'AVAILABLE', 0, 0),
(101, NULL, 0, 0, 'StallA', 'AVAILABLE', 0, 0),
(102, NULL, 0, 0, 'StallA', 'AVAILABLE', 0, 0),
(103, NULL, 0, 0, 'StallA', 'AVAILABLE', 0, 0),
(104, NULL, 0, 0, 'StallB', 'AVAILABLE', 0, 0),
(105, NULL, 0, 0, 'StallB', 'AVAILABLE', 0, 0),
(106, NULL, 0, 0, 'StallB', 'AVAILABLE', 0, 0),
(107, NULL, 0, 0, 'StallC', 'AVAILABLE', 0, 0),
(108, NULL, 0, 0, 'StallC', 'AVAILABLE', 0, 0),
(109, NULL, 0, 0, 'StallC', 'AVAILABLE', 0, 0),
(110, NULL, 0, 0, 'StallD', 'AVAILABLE', 0, 0),
(111, NULL, 0, 0, 'StallD', 'AVAILABLE', 0, 0),
(112, NULL, 0, 0, 'StallD', 'LASTSET', 0, 0);

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
-- Table structure for table `gamelog`
--

CREATE TABLE `gamelog` (
  `gamelog_packet_id` int(10) UNSIGNED NOT NULL,
  `gamelog_move_id` int(10) UNSIGNED DEFAULT NULL,
  `gamelog_private` tinyint(1) NOT NULL,
  `gamelog_time` datetime NOT NULL,
  `gamelog_player` int(10) UNSIGNED DEFAULT NULL COMMENT 'null if main channel',
  `gamelog_current_player` int(10) UNSIGNED DEFAULT NULL COMMENT 'player that sent the request that leads to this notif',
  `gamelog_notification` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `gamelog`
--

INSERT INTO `gamelog` (`gamelog_packet_id`, `gamelog_move_id`, `gamelog_private`, `gamelog_time`, `gamelog_player`, `gamelog_current_player`, `gamelog_notification`) VALUES
(1, 1, 0, '2026-01-23 21:27:33', NULL, 0, '[{\"uid\":\"6973cba52fb48\",\"type\":\"gameStateChange\",\"log\":\"\",\"args\":{\"name\":\"gameSetup\",\"description\":\"\",\"type\":\"manager\",\"action\":\"stGameSetup\",\"transitions\":{\"\":2},\"descriptionmyturn\":\"\",\"args\":[],\"possibleactions\":null,\"updateGameProgression\":false,\"initialprivate\":null,\"active_player\":2405406,\"reflexion\":{\"total\":{\"2405406\":null,\"2405407\":null}}}},{\"uid\":\"6973cba531db2\",\"type\":\"gameStateChange\",\"log\":\"\",\"args\":{\"id\":2,\"active_player\":2405406,\"args\":{\"active_player_id\":\"2405406\",\"money\":\"2\",\"unblockedzoo\":\"0\",\"wagons\":[{\"id\":\"1\",\"size\":\"3\",\"val1\":\"\",\"val2\":\"\",\"val3\":\"\"},{\"id\":\"2\",\"size\":\"2\",\"val1\":\"\",\"val2\":\"\",\"val3\":\"\"},{\"id\":\"3\",\"size\":\"1\",\"val1\":\"\",\"val2\":\"\",\"val3\":\"\"}]},\"type\":\"activeplayer\",\"reflexion\":{\"total\":{\"2405406\":null,\"2405407\":null}},\"updateGameProgression\":0}}]'),
(2, 2, 0, '2026-01-23 21:27:40', NULL, 2405406, '[{\"uid\":\"6973cbac6b045\",\"type\":\"DrawTile\",\"log\":\"${player_name} drew a ${translatedval} tile.\",\"args\":{\"player_id\":\"2405406\",\"player_no\":\"1\",\"id\":\"19\",\"val\":\"EM\",\"tilesleft\":\"61\",\"tilesleft2\":\"15\",\"translatedval\":\"Male Elephant\",\"player_name\":\"vagabond0\",\"i18n\":[\"translatedval\"]},\"h\":\"2fba7e\"},{\"uid\":\"6973cbac6c55c\",\"type\":\"gameStateChange\",\"log\":\"\",\"args\":{\"id\":3,\"active_player\":\"2405406\",\"args\":[],\"type\":\"activeplayer\",\"reflexion\":{\"total\":{\"2405406\":174,\"2405407\":\"180\"}},\"updateGameProgression\":1},\"lock_uuid\":\"5b090051-9655-42f8-8897-fdf72808259d\"}]'),
(3, 3, 0, '2026-01-23 21:27:44', NULL, 2405406, '[{\"uid\":\"6973cbb0e98fb\",\"type\":\"PlaceTile\",\"log\":\"${player_name} placed the ${translatedval} tile on the ${pos} space of the ${wag} wagon.\",\"args\":{\"player_id\":\"2405406\",\"player_no\":\"1\",\"id\":\"19\",\"val\":\"EM\",\"x\":\"2\",\"y\":\"1\",\"translatedval\":\"Male Elephant\",\"pos\":\"first\",\"wag\":\"second\",\"player_name\":\"vagabond0\",\"i18n\":[\"translatedval\",\"pos\",\"wag\"]},\"h\":\"706593\"},{\"uid\":\"6973cbb0ea9d5\",\"type\":\"gameStateChange\",\"log\":\"\",\"args\":{\"id\":4,\"active_player\":\"2405406\",\"args\":[],\"type\":\"game\",\"reflexion\":{\"total\":{\"2405406\":171,\"2405407\":\"180\"}},\"updateGameProgression\":1}},{\"uid\":\"6973cbb0eae1a\",\"type\":\"updateReflexionTime\",\"log\":\"\",\"args\":{\"player_id\":\"2405406\",\"delta\":\"40\",\"max\":\"180\"}},{\"uid\":\"6973cbb0ecbe0\",\"type\":\"gameStateChange\",\"log\":\"\",\"args\":{\"id\":2,\"active_player\":2405407,\"args\":{\"active_player_id\":\"2405407\",\"money\":\"2\",\"unblockedzoo\":\"0\",\"wagons\":[{\"id\":\"1\",\"size\":\"3\",\"val1\":\"\",\"val2\":\"\",\"val3\":\"\"},{\"id\":\"2\",\"size\":\"2\",\"val1\":\"19\",\"val2\":\"\",\"val3\":\"\"},{\"id\":\"3\",\"size\":\"1\",\"val1\":\"\",\"val2\":\"\",\"val3\":\"\"}]},\"type\":\"activeplayer\",\"reflexion\":{\"total\":{\"2405406\":\"180\",\"2405407\":\"180\"}},\"updateGameProgression\":1},\"lock_uuid\":\"0771a5d7-a815-4a1b-86ae-b57ba5825eea\"}]'),
(4, 4, 0, '2026-01-23 21:27:53', NULL, 2405407, '[{\"uid\":\"6973cbb9bb2df\",\"type\":\"DrawTile\",\"log\":\"${player_name} drew a ${translatedval} tile.\",\"args\":{\"player_id\":\"2405407\",\"player_no\":\"2\",\"id\":\"45\",\"val\":\"L\",\"tilesleft\":\"60\",\"tilesleft2\":\"15\",\"translatedval\":\"Leopard\",\"player_name\":\"vagabond1\",\"i18n\":[\"translatedval\"]},\"h\":\"68bb37\"},{\"uid\":\"6973cbb9bc606\",\"type\":\"gameStateChange\",\"log\":\"\",\"args\":{\"id\":3,\"active_player\":\"2405407\",\"args\":[],\"type\":\"activeplayer\",\"reflexion\":{\"total\":{\"2405406\":\"180\",\"2405407\":172}},\"updateGameProgression\":2},\"lock_uuid\":\"bd7c1530-881f-4167-84b6-2c78df55d394\"}]'),
(5, 5, 0, '2026-01-23 21:27:58', NULL, 2405407, '[{\"uid\":\"6973cbbec10a5\",\"type\":\"PlaceTile\",\"log\":\"${player_name} placed the ${translatedval} tile on the ${pos} space of the ${wag} wagon.\",\"args\":{\"player_id\":\"2405407\",\"player_no\":\"2\",\"id\":\"45\",\"val\":\"L\",\"x\":\"1\",\"y\":\"3\",\"translatedval\":\"Leopard\",\"pos\":\"third\",\"wag\":\"first\",\"player_name\":\"vagabond1\",\"i18n\":[\"translatedval\",\"pos\",\"wag\"]},\"h\":\"23736a\"},{\"uid\":\"6973cbbec2b0c\",\"type\":\"gameStateChange\",\"log\":\"\",\"args\":{\"id\":4,\"active_player\":\"2405407\",\"args\":[],\"type\":\"game\",\"reflexion\":{\"total\":{\"2405406\":\"180\",\"2405407\":168}},\"updateGameProgression\":2}},{\"uid\":\"6973cbbec316c\",\"type\":\"updateReflexionTime\",\"log\":\"\",\"args\":{\"player_id\":\"2405407\",\"delta\":\"40\",\"max\":\"180\"}},{\"uid\":\"6973cbbec5370\",\"type\":\"gameStateChange\",\"log\":\"\",\"args\":{\"id\":2,\"active_player\":2405406,\"args\":{\"active_player_id\":\"2405406\",\"money\":\"2\",\"unblockedzoo\":\"0\",\"wagons\":[{\"id\":\"1\",\"size\":\"3\",\"val1\":\"\",\"val2\":\"\",\"val3\":\"45\"},{\"id\":\"2\",\"size\":\"2\",\"val1\":\"19\",\"val2\":\"\",\"val3\":\"\"},{\"id\":\"3\",\"size\":\"1\",\"val1\":\"\",\"val2\":\"\",\"val3\":\"\"}]},\"type\":\"activeplayer\",\"reflexion\":{\"total\":{\"2405406\":\"180\",\"2405407\":\"180\"}},\"updateGameProgression\":2},\"lock_uuid\":\"141ac734-318b-4f53-8136-e34fe70d3440\"}]'),
(6, 6, 0, '2026-01-23 21:28:07', NULL, 2405406, '[{\"uid\":\"6973cbc7bcdd1\",\"type\":\"TakeWagon\",\"log\":\"${player_name} took a wagon with ${wag}.\",\"args\":{\"player_id\":\"2405406\",\"player_no\":\"1\",\"id1\":\"19\",\"id2\":\"\",\"id3\":\"\",\"val1\":\"EM\",\"val2\":null,\"val3\":null,\"x\":\"2\",\"wag\":\"Male Elephant\",\"wagontiles\":[{\"wagontile\":\"tile_0_19_EM_2_1\",\"id\":\"19\"}],\"player_name\":\"vagabond0\",\"i18n\":[\"wag\"]},\"h\":\"f289d7\"},{\"uid\":\"6973cbc7be6a5\",\"type\":\"gameStateChange\",\"log\":\"\",\"args\":{\"id\":5,\"active_player\":\"2405406\",\"args\":[],\"type\":\"activeplayer\",\"reflexion\":{\"total\":{\"2405406\":172,\"2405407\":\"180\"}},\"updateGameProgression\":2},\"lock_uuid\":\"a0219c64-f78e-4d8a-8409-33f3f4302521\"}]'),
(7, 7, 0, '2026-01-23 21:28:11', NULL, 2405406, '[{\"uid\":\"6973cbcbb1e81\",\"type\":\"ArrangeTiles\",\"log\":\"${player_name} placed the ${translatedval} in his ${pos} enclosure.\",\"args\":{\"player_id\":\"2405406\",\"player_no\":\"1\",\"tileid\":\"19\",\"wagonid\":\"2\",\"posid\":\"1\",\"val\":\"EM\",\"x\":\"2\",\"y\":\"1\",\"pid\":\"0\",\"translatedval\":\"Male Elephant\",\"pos\":\"second\",\"player_name\":\"vagabond0\",\"i18n\":[\"translatedval\",\"pos\"]},\"lock_uuid\":\"a5ed4d9c-0607-4af1-8df1-ea9808b5ef1b\",\"h\":\"8c2aa5\"}]'),
(8, 8, 0, '2026-01-23 21:28:16', NULL, 2405406, '[{\"uid\":\"6973cbd0d67ae\",\"type\":\"ConfirmArrangement\",\"log\":\"${player_name} confirmed the arrangement of his zoo.\",\"args\":{\"player_id\":\"2405406\",\"player_no\":\"1\",\"wagonid\":\"2\",\"player_name\":\"vagabond0\"},\"h\":\"b07c0c\"},{\"uid\":\"6973cbd0d7fb9\",\"type\":\"gameStateChange\",\"log\":\"\",\"args\":{\"id\":4,\"active_player\":\"2405406\",\"args\":[],\"type\":\"game\",\"reflexion\":{\"total\":{\"2405406\":164,\"2405407\":\"180\"}},\"updateGameProgression\":2}},{\"uid\":\"6973cbd0d861e\",\"type\":\"updateReflexionTime\",\"log\":\"\",\"args\":{\"player_id\":\"2405406\",\"delta\":\"40\",\"max\":\"180\"}},{\"uid\":\"6973cbd0dabb9\",\"type\":\"gameStateChange\",\"log\":\"\",\"args\":{\"id\":2,\"active_player\":2405407,\"args\":{\"active_player_id\":\"2405407\",\"money\":\"2\",\"unblockedzoo\":\"0\",\"wagons\":[{\"id\":\"1\",\"size\":\"3\",\"val1\":\"\",\"val2\":\"\",\"val3\":\"45\"},{\"id\":\"3\",\"size\":\"1\",\"val1\":\"\",\"val2\":\"\",\"val3\":\"\"}]},\"type\":\"activeplayer\",\"reflexion\":{\"total\":{\"2405406\":\"180\",\"2405407\":\"180\"}},\"updateGameProgression\":2},\"lock_uuid\":\"49576ee2-ce64-4dfb-84c8-535e20db9781\"}]'),
(9, NULL, 0, '2026-01-23 21:28:34', NULL, 2405406, '[{\"uid\":\"6973cbe29807e\",\"type\":\"wakeupPlayers\",\"log\":\"\",\"args\":[]}]'),
(10, NULL, 0, '2026-01-23 21:28:35', NULL, 2405407, '[{\"uid\":\"6973cbe39e862\",\"type\":\"wakeupPlayers\",\"log\":\"\",\"args\":[]}]');

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
(0, 1769196496),
(1, 2),
(2, 2405407),
(3, 9),
(4, 5314),
(5, 2405406),
(6, 4),
(7, 2),
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
  `player_zombie` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = player is a zombie',
  `player_ai` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = player is an AI',
  `player_eliminated` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = player has been eliminated',
  `player_next_notif_no` int(10) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Next notification no to be sent to player',
  `player_enter_game` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = player load game view at least once',
  `player_over_time` tinyint(1) NOT NULL DEFAULT '0',
  `player_is_multiactive` tinyint(1) NOT NULL DEFAULT '0',
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
(1, 2405406, '16f52183ea1d7116c2f84ef12ded2f45', 'vagabond0', '000000', 'ff0000', 0, 0, 0, 0, 0, 1, 1, 0, 0, NULL, 180, NULL, NULL, 2, 0, 'Y', 'N'),
(2, 2405407, 'a2de620d33098ca85ff839644fe6d1bc', 'vagabond1', '000000', '008000', 0, 0, 0, 0, 0, 1, 1, 0, 0, '2026-01-23 21:28:16', 180, NULL, NULL, 2, 0, 'N', 'N');

-- --------------------------------------------------------

--
-- Table structure for table `stats`
--

CREATE TABLE `stats` (
  `stats_id` int(10) UNSIGNED NOT NULL,
  `stats_type` smallint(5) UNSIGNED NOT NULL,
  `stats_player_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'if NULL: stat global to table',
  `stats_value` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `stats`
--

INSERT INTO `stats` (`stats_id`, `stats_type`, `stats_player_id`, `stats_value`) VALUES
(1, 10, 2405406, 0),
(2, 10, 2405407, 0),
(3, 11, 2405406, 0),
(4, 11, 2405407, 0),
(5, 12, 2405406, 0),
(6, 12, 2405407, 0),
(7, 13, 2405406, 0),
(8, 13, 2405407, 0),
(9, 15, 2405406, 0),
(10, 15, 2405407, 0),
(11, 16, 2405406, 0),
(12, 16, 2405407, 0),
(13, 17, 2405406, 0),
(14, 17, 2405407, 0),
(15, 18, 2405406, 0),
(16, 18, 2405407, 0),
(17, 20, 2405406, 0),
(18, 20, 2405407, 0),
(19, 21, 2405406, 0),
(20, 21, 2405407, 0),
(21, 22, 2405406, 0),
(22, 22, 2405407, 0),
(23, 23, 2405406, 0),
(24, 23, 2405407, 0),
(25, 25, 2405406, 0),
(26, 25, 2405407, 0),
(27, 26, 2405406, 0),
(28, 26, 2405407, 0),
(29, 27, 2405406, 0),
(30, 27, 2405407, 0),
(31, 28, 2405406, 0),
(32, 28, 2405407, 0),
(33, 29, 2405406, 0),
(34, 29, 2405407, 0),
(35, 30, 2405406, 2),
(36, 30, 2405407, 2),
(37, 1, 2405406, 25),
(38, 1, 2405407, 12),
(39, 2, 2405406, 2),
(40, 2, 2405407, 1);

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
(1, 3, '', '', '45', 'AVAILABLE'),
(2, 2, '', '', '', 'PLAYED'),
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
-- Indexes for table `gamelog`
--
ALTER TABLE `gamelog`
  ADD PRIMARY KEY (`gamelog_packet_id`);

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
-- Indexes for table `stats`
--
ALTER TABLE `stats`
  ADD PRIMARY KEY (`stats_id`),
  ADD UNIQUE KEY `stats_table_id` (`stats_type`,`stats_player_id`),
  ADD KEY `stats_player_id` (`stats_player_id`);

--
-- Indexes for table `wagons`
--
ALTER TABLE `wagons`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `gamelog`
--
ALTER TABLE `gamelog`
  MODIFY `gamelog_packet_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `player`
--
ALTER TABLE `player`
  MODIFY `player_no` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stats`
--
ALTER TABLE `stats`
  MODIFY `stats_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
