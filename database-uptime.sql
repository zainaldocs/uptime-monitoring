-- Uptime Monitoring System Database Dump
-- Generated: 2026-07-03 22:28:33

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table structure for table `devices`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `devices`;
CREATE TABLE `devices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `group_id` int DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `port` int DEFAULT NULL,
  `check_type` enum('ping','tcp','http') COLLATE utf8mb4_unicode_ci DEFAULT 'ping',
  `status` enum('UP','DOWN','UNKNOWN') COLLATE utf8mb4_unicode_ci DEFAULT 'UNKNOWN',
  `last_status_change` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`),
  CONSTRAINT `devices_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `devices`

INSERT INTO `devices` (`id`, `group_id`, `name`, `ip_address`, `port`, `check_type`, `status`, `last_status_change`, `created_at`) VALUES ('1', '3', 'Google Public DNS', '8.8.8.8', '53', 'tcp', 'UP', '2026-07-01 20:27:29', '2026-07-04 01:27:29');
INSERT INTO `devices` (`id`, `group_id`, `name`, `ip_address`, `port`, `check_type`, `status`, `last_status_change`, `created_at`) VALUES ('2', '3', 'Cloudflare DNS', '1.1.1.1', NULL, 'ping', 'UP', '2026-07-02 20:27:29', '2026-07-04 01:27:29');
INSERT INTO `devices` (`id`, `group_id`, `name`, `ip_address`, `port`, `check_type`, `status`, `last_status_change`, `created_at`) VALUES ('3', '2', 'Local Web Server', '127.0.0.1', '80', 'http', 'UP', '2026-07-03 08:27:29', '2026-07-04 01:27:29');
INSERT INTO `devices` (`id`, `group_id`, `name`, `ip_address`, `port`, `check_type`, `status`, `last_status_change`, `created_at`) VALUES ('4', '2', 'Offline Dummy Host', '192.168.11.1', NULL, 'http', 'UP', '2026-07-04 01:54:14', '2026-07-04 01:27:29');
INSERT INTO `devices` (`id`, `group_id`, `name`, `ip_address`, `port`, `check_type`, `status`, `last_status_change`, `created_at`) VALUES ('5', '1', 'Google Homepage', 'www.google.com', '443', 'http', 'UP', '2026-07-04 01:29:30', '2026-07-04 01:27:29');
INSERT INTO `devices` (`id`, `group_id`, `name`, `ip_address`, `port`, `check_type`, `status`, `last_status_change`, `created_at`) VALUES ('7', '4', 'Website GP', 'guardianpharmatama.com', '443', 'http', 'UP', '2026-07-04 02:53:30', '2026-07-04 01:50:15');

-- --------------------------------------------------------
-- Table structure for table `groups`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `group_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_name` (`group_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `groups`

INSERT INTO `groups` (`id`, `group_name`, `created_at`) VALUES ('1', 'Public Services', '2026-07-04 01:27:29');
INSERT INTO `groups` (`id`, `group_name`, `created_at`) VALUES ('2', 'Internal Servers', '2026-07-04 01:27:29');
INSERT INTO `groups` (`id`, `group_name`, `created_at`) VALUES ('3', 'DNS Infrastructure', '2026-07-04 01:27:29');
INSERT INTO `groups` (`id`, `group_name`, `created_at`) VALUES ('4', 'Server Office', '2026-07-04 01:34:08');

-- --------------------------------------------------------
-- Table structure for table `login_attempts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(100) NOT NULL,
  `attempted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `status_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `status_logs`;
CREATE TABLE `status_logs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `device_id` int NOT NULL,
  `status` enum('UP','DOWN') COLLATE utf8mb4_unicode_ci NOT NULL,
  `latency` float DEFAULT NULL COMMENT 'Latency in milliseconds',
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_device_time` (`device_id`,`timestamp`),
  CONSTRAINT `status_logs_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=183 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `status_logs`

INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('1', '1', 'UP', '20.9', '2026-07-02 20:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('2', '2', 'UP', '10.3', '2026-07-02 20:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('3', '3', 'UP', '2', '2026-07-02 20:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('4', '4', 'UP', '16.9', '2026-07-02 20:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('5', '5', 'UP', '43.5', '2026-07-02 20:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('6', '1', 'UP', '15.5', '2026-07-02 21:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('7', '2', 'UP', '10.1', '2026-07-02 21:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('8', '3', 'UP', '4.8', '2026-07-02 21:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('9', '4', 'UP', '15.4', '2026-07-02 21:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('10', '5', 'UP', '51.6', '2026-07-02 21:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('11', '1', 'UP', '19', '2026-07-02 22:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('12', '2', 'UP', '9.2', '2026-07-02 22:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('13', '3', 'UP', '4.3', '2026-07-02 22:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('14', '4', 'UP', '21', '2026-07-02 22:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('15', '5', 'UP', '55.2', '2026-07-02 22:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('16', '1', 'UP', '17.2', '2026-07-02 23:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('17', '2', 'UP', '13.6', '2026-07-02 23:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('18', '3', 'UP', '3.5', '2026-07-02 23:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('19', '4', 'UP', '18', '2026-07-02 23:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('20', '5', 'UP', '37.1', '2026-07-02 23:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('21', '1', 'UP', '13.9', '2026-07-03 00:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('22', '2', 'UP', '9.1', '2026-07-03 00:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('23', '3', 'UP', '2.5', '2026-07-03 00:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('24', '4', 'UP', '12.1', '2026-07-03 00:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('25', '5', 'UP', '30.7', '2026-07-03 00:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('26', '1', 'UP', '10.7', '2026-07-03 01:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('27', '2', 'UP', '13', '2026-07-03 01:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('28', '3', 'UP', '4.7', '2026-07-03 01:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('29', '4', 'UP', '12.4', '2026-07-03 01:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('30', '5', 'UP', '50.9', '2026-07-03 01:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('31', '1', 'UP', '10.1', '2026-07-03 02:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('32', '2', 'UP', '13.3', '2026-07-03 02:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('33', '3', 'UP', '4.9', '2026-07-03 02:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('34', '4', 'UP', '16.3', '2026-07-03 02:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('35', '5', 'UP', '54.3', '2026-07-03 02:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('36', '1', 'UP', '14.1', '2026-07-03 03:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('37', '2', 'UP', '10.7', '2026-07-03 03:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('38', '3', 'UP', '2.7', '2026-07-03 03:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('39', '4', 'UP', '16', '2026-07-03 03:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('40', '5', 'UP', '48.3', '2026-07-03 03:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('41', '1', 'UP', '18.7', '2026-07-03 04:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('42', '2', 'UP', '17.2', '2026-07-03 04:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('43', '3', 'UP', '1.9', '2026-07-03 04:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('44', '4', 'UP', '19.9', '2026-07-03 04:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('45', '5', 'UP', '41.4', '2026-07-03 04:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('46', '1', 'UP', '19.3', '2026-07-03 05:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('47', '2', 'UP', '13.8', '2026-07-03 05:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('48', '3', 'UP', '2.1', '2026-07-03 05:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('49', '4', 'UP', '19.5', '2026-07-03 05:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('50', '5', 'UP', '44.8', '2026-07-03 05:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('51', '1', 'UP', '18', '2026-07-03 06:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('52', '2', 'UP', '9.1', '2026-07-03 06:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('53', '3', 'UP', '4.7', '2026-07-03 06:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('54', '4', 'UP', '12.9', '2026-07-03 06:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('55', '5', 'UP', '35.6', '2026-07-03 06:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('56', '1', 'UP', '19.3', '2026-07-03 07:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('57', '2', 'UP', '8.1', '2026-07-03 07:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('58', '3', 'UP', '4.1', '2026-07-03 07:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('59', '4', 'UP', '21.1', '2026-07-03 07:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('60', '5', 'UP', '34.8', '2026-07-03 07:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('61', '1', 'UP', '24.4', '2026-07-03 08:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('62', '2', 'UP', '16.2', '2026-07-03 08:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('63', '3', 'UP', '3.2', '2026-07-03 08:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('64', '4', 'UP', '23.5', '2026-07-03 08:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('65', '5', 'UP', '44.4', '2026-07-03 08:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('66', '1', 'UP', '12.5', '2026-07-03 09:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('67', '2', 'UP', '13.9', '2026-07-03 09:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('68', '3', 'UP', '4', '2026-07-03 09:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('69', '4', 'UP', '15.3', '2026-07-03 09:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('70', '5', 'UP', '30.1', '2026-07-03 09:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('71', '1', 'UP', '14', '2026-07-03 10:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('72', '2', 'UP', '13.9', '2026-07-03 10:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('73', '3', 'UP', '2.6', '2026-07-03 10:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('74', '4', 'UP', '15.5', '2026-07-03 10:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('75', '5', 'UP', '48.8', '2026-07-03 10:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('76', '1', 'UP', '21.8', '2026-07-03 11:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('77', '2', 'UP', '11.5', '2026-07-03 11:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('78', '3', 'UP', '4.2', '2026-07-03 11:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('79', '4', 'UP', '13.1', '2026-07-03 11:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('80', '5', 'UP', '45.2', '2026-07-03 11:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('81', '1', 'UP', '17.6', '2026-07-03 12:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('82', '2', 'UP', '15.7', '2026-07-03 12:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('83', '3', 'UP', '1.3', '2026-07-03 12:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('84', '4', 'UP', '23.3', '2026-07-03 12:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('85', '5', 'UP', '41.7', '2026-07-03 12:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('86', '1', 'UP', '24.6', '2026-07-03 13:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('87', '2', 'UP', '10.6', '2026-07-03 13:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('88', '3', 'UP', '3.1', '2026-07-03 13:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('89', '4', 'UP', '18.2', '2026-07-03 13:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('90', '5', 'UP', '57.4', '2026-07-03 13:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('91', '1', 'UP', '24.7', '2026-07-03 14:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('92', '2', 'UP', '13.5', '2026-07-03 14:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('93', '3', 'UP', '2', '2026-07-03 14:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('94', '4', 'UP', '14.6', '2026-07-03 14:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('95', '5', 'UP', '47.1', '2026-07-03 14:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('96', '1', 'UP', '24.7', '2026-07-03 15:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('97', '2', 'UP', '10', '2026-07-03 15:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('98', '3', 'UP', '4.1', '2026-07-03 15:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('99', '4', 'UP', '13', '2026-07-03 15:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('100', '5', 'UP', '58', '2026-07-03 15:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('101', '1', 'UP', '18.4', '2026-07-03 16:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('102', '2', 'UP', '12.2', '2026-07-03 16:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('103', '3', 'UP', '1.7', '2026-07-03 16:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('104', '4', 'UP', '23.4', '2026-07-03 16:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('105', '5', 'UP', '42.2', '2026-07-03 16:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('106', '1', 'UP', '21.2', '2026-07-03 17:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('107', '2', 'UP', '11.8', '2026-07-03 17:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('108', '3', 'UP', '1.3', '2026-07-03 17:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('109', '4', 'DOWN', NULL, '2026-07-03 17:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('110', '5', 'UP', '50.5', '2026-07-03 17:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('111', '1', 'UP', '15', '2026-07-03 18:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('112', '2', 'UP', '14.6', '2026-07-03 18:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('113', '3', 'UP', '2.4', '2026-07-03 18:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('114', '4', 'DOWN', NULL, '2026-07-03 18:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('115', '5', 'UP', '41.7', '2026-07-03 18:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('116', '1', 'UP', '20.3', '2026-07-03 19:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('117', '2', 'UP', '17.2', '2026-07-03 19:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('118', '3', 'UP', '1.7', '2026-07-03 19:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('119', '4', 'DOWN', NULL, '2026-07-03 19:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('120', '5', 'UP', '57.8', '2026-07-03 19:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('121', '1', 'UP', '11.2', '2026-07-03 20:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('122', '2', 'UP', '16.8', '2026-07-03 20:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('123', '3', 'UP', '2.3', '2026-07-03 20:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('124', '4', 'DOWN', NULL, '2026-07-03 20:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('125', '5', 'UP', '30.7', '2026-07-03 20:27:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('126', '1', 'UP', '34.51', '2026-07-04 01:29:08');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('127', '2', 'UP', '38', '2026-07-04 01:29:08');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('128', '3', 'UP', '73.86', '2026-07-04 01:29:08');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('129', '4', 'DOWN', NULL, '2026-07-04 01:29:11');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('130', '5', 'DOWN', NULL, '2026-07-04 01:29:11');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('131', '1', 'UP', '44.01', '2026-07-04 01:29:26');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('132', '2', 'UP', '45', '2026-07-04 01:29:26');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('133', '3', 'UP', '2.01', '2026-07-04 01:29:26');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('134', '4', 'DOWN', NULL, '2026-07-04 01:29:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('135', '5', 'UP', '319.54', '2026-07-04 01:29:30');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('136', '1', 'UP', '188.57', '2026-07-04 01:45:28');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('137', '2', 'UP', '143', '2026-07-04 01:45:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('138', '3', 'UP', '3.01', '2026-07-04 01:45:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('139', '4', 'DOWN', NULL, '2026-07-04 01:45:32');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('140', '5', 'UP', '298.34', '2026-07-04 01:45:32');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('142', '7', 'UP', '1198.3', '2026-07-04 01:50:16');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('143', '4', 'UP', '6.5', '2026-07-04 01:54:14');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('144', '1', 'UP', '46.59', '2026-07-04 02:11:16');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('145', '2', 'UP', '46', '2026-07-04 02:11:16');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('146', '3', 'UP', '2.6', '2026-07-04 02:11:16');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('147', '4', 'UP', '6.64', '2026-07-04 02:11:16');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('148', '5', 'UP', '271.1', '2026-07-04 02:11:16');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('149', '7', 'UP', '1158.48', '2026-07-04 02:11:17');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('150', '1', 'UP', '46.15', '2026-07-04 02:14:33');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('151', '2', 'UP', '39', '2026-07-04 02:14:33');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('152', '3', 'UP', '6.46', '2026-07-04 02:14:33');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('153', '4', 'UP', '6.75', '2026-07-04 02:14:33');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('154', '5', 'UP', '290.74', '2026-07-04 02:14:34');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('155', '7', 'UP', '1031.81', '2026-07-04 02:14:35');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('156', '1', 'UP', '39.78', '2026-07-04 02:18:24');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('157', '2', 'UP', '45', '2026-07-04 02:18:24');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('158', '3', 'UP', '2.02', '2026-07-04 02:18:24');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('159', '4', 'UP', '6.91', '2026-07-04 02:18:24');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('160', '5', 'UP', '307.23', '2026-07-04 02:18:24');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('161', '7', 'UP', '1107.34', '2026-07-04 02:18:25');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('162', '1', 'UP', '39.7', '2026-07-04 02:52:13');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('163', '2', 'UP', '36', '2026-07-04 02:52:13');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('164', '3', 'UP', '4.73', '2026-07-04 02:52:13');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('165', '4', 'UP', '6.84', '2026-07-04 02:52:13');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('166', '5', 'UP', '312.32', '2026-07-04 02:52:13');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('167', '7', 'DOWN', NULL, '2026-07-04 02:52:16');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('168', '1', 'UP', '31.38', '2026-07-04 02:53:28');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('169', '2', 'UP', '38', '2026-07-04 02:53:28');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('170', '3', 'UP', '2.53', '2026-07-04 02:53:28');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('171', '4', 'UP', '6.43', '2026-07-04 02:53:28');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('172', '5', 'UP', '267.87', '2026-07-04 02:53:29');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('173', '7', 'UP', '1038.09', '2026-07-04 02:53:30');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('177', '1', 'UP', '42.25', '2026-07-04 03:18:34');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('178', '2', 'UP', '39', '2026-07-04 03:18:34');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('179', '3', 'UP', '2.26', '2026-07-04 03:18:34');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('180', '4', 'UP', '6.86', '2026-07-04 03:18:34');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('181', '5', 'UP', '267.77', '2026-07-04 03:18:35');
INSERT INTO `status_logs` (`id`, `device_id`, `status`, `latency`, `timestamp`) VALUES ('182', '7', 'UP', '986.36', '2026-07-04 03:18:36');

-- --------------------------------------------------------
-- Table structure for table `system_settings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `system_settings`

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`) VALUES ('1', 'smtp_host', 'smtp.gmail.com');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`) VALUES ('2', 'smtp_port', '587');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`) VALUES ('3', 'smtp_auth', 'true');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`) VALUES ('4', 'smtp_user', 'zainaldocs@gmail.com');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`) VALUES ('5', 'smtp_pass', 'vmzj hyls jfar hirp');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`) VALUES ('6', 'alert_target_email', 'nal.zainalarifin@gmail.com');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`) VALUES ('7', 'theme_mode', 'light');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`) VALUES ('8', 'email_trigger_down', '1');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`) VALUES ('9', 'email_trigger_up', '1');
INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`) VALUES ('10', 'email_trigger_daily_report', '1');

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('Admin','Staff') COLLATE utf8mb4_unicode_ci DEFAULT 'Staff',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `users`

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `email`, `created_at`) VALUES ('1', 'admin', '$2y$10$BEyHFXrI9U6i/5yIohT4UuewfYWs5RnNZf9rh7P2ulC2QEJjrGr76', 'Admin', 'admin@example.com', '2026-07-04 01:27:29');
INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `email`, `created_at`) VALUES ('2', 'staff', '$2y$10$BEyHFXrI9U6i/5yIohT4UuewfYWs5RnNZf9rh7P2ulC2QEJjrGr76', 'Staff', 'staff@example.com', '2026-07-04 01:27:29');
INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `email`, `created_at`) VALUES ('3', 'zainal', '$2y$10$RajaAkcEnIPRNTTl0El//emq4hVS5KouqMJo87qb09PKx3qrjnpC2', 'Admin', 'zainaldocs@gmail.com', '2026-07-04 01:35:45');
INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `email`, `created_at`) VALUES ('4', 'arifin', '$2y$10$yRx.WOOJNQQhcBuG0e8Zg.wVbbdNhtpycPP0jSPC26.QxoLLGoOfe', 'Staff', 'zainaldocs@gmail.com', '2026-07-04 01:35:57');

SET FOREIGN_KEY_CHECKS = 1;
