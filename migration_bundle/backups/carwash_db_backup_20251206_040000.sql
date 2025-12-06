-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: carwash_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Temporary table structure for view `active_bookings_view`
--

DROP TABLE IF EXISTS `active_bookings_view`;
/*!50001 DROP VIEW IF EXISTS `active_bookings_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `active_bookings_view` AS SELECT
 1 AS `id`,
  1 AS `booking_date`,
  1 AS `booking_time`,
  1 AS `status`,
  1 AS `total_price`,
  1 AS `vehicle_type`,
  1 AS `vehicle_plate`,
  1 AS `customer_name`,
  1 AS `customer_email`,
  1 AS `customer_phone`,
  1 AS `carwash_name`,
  1 AS `carwash_address`,
  1 AS `service_name`,
  1 AS `service_duration` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `actor_id` int(11) DEFAULT NULL,
  `actor_role` varchar(50) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` varchar(50) NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `request_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_actor_id` (`actor_id`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail for security and compliance';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `booking_services`
--

DROP TABLE IF EXISTS `booking_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `booking_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_service_unique` (`booking_id`,`service_id`),
  KEY `service_id` (`service_id`),
  CONSTRAINT `booking_services_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `booking_services_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking_services`
--

LOCK TABLES `booking_services` WRITE;
/*!40000 ALTER TABLE `booking_services` DISABLE KEYS */;
/*!40000 ALTER TABLE `booking_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `booking_status`
--

DROP TABLE IF EXISTS `booking_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `booking_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) DEFAULT NULL,
  `label` varchar(100) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking_status`
--

LOCK TABLES `booking_status` WRITE;
/*!40000 ALTER TABLE `booking_status` DISABLE KEYS */;
/*!40000 ALTER TABLE `booking_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_number` varchar(20) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `carwash_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `vehicle_type` enum('sedan','suv','truck','van','motorcycle') NOT NULL,
  `vehicle_plate` varchar(20) DEFAULT NULL,
  `vehicle_model` varchar(100) DEFAULT NULL,
  `vehicle_color` varchar(50) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `status` enum('pending','confirmed','in_progress','completed','cancelled') DEFAULT 'pending',
  `review_status` enum('pending','reviewed') DEFAULT 'pending' COMMENT 'Whether customer has left a review',
  `total_price` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','refunded') DEFAULT 'pending',
  `payment_method` enum('cash','card','online') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `service_id` (`service_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_carwash` (`carwash_id`),
  KEY `idx_date` (`booking_date`),
  KEY `idx_status` (`status`),
  KEY `idx_review_status` (`review_status`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`carwash_id`) REFERENCES `carwashes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (7,'BK2025000007',14,7,19,'2025-12-04','11:00:00','sedan',NULL,NULL,NULL,NULL,NULL,'cancelled','pending',50.00,'pending',NULL,'changing to require approval',NULL,NULL,'2025-11-22 01:25:49','2025-12-06 00:17:49'),(8,'BK2025000008',14,7,18,'2025-11-24','21:24:00','sedan',NULL,NULL,NULL,NULL,NULL,'cancelled','pending',200.00,'pending',NULL,'',NULL,NULL,'2025-11-22 01:50:00','2025-12-06 00:17:49'),(9,'BK2025000009',14,7,17,'2025-11-28','01:28:00','sedan',NULL,NULL,NULL,NULL,NULL,'cancelled','pending',45.00,'pending',NULL,'',NULL,NULL,'2025-11-22 01:50:59','2025-12-06 00:17:49'),(12,'BK2025000012',14,7,18,'2025-11-30','22:19:00','sedan',NULL,NULL,NULL,NULL,NULL,'cancelled','pending',200.00,'pending',NULL,'',NULL,NULL,'2025-11-23 00:46:13','2025-12-06 00:17:49'),(13,'BK2025000013',14,7,19,'2025-11-19','03:02:00','sedan',NULL,NULL,NULL,NULL,NULL,'pending','pending',1000.00,'pending',NULL,'',NULL,NULL,'2025-11-23 01:27:20','2025-12-06 00:17:49'),(14,'BK2025000014',14,7,19,'2025-12-27','04:09:00','sedan',NULL,NULL,NULL,NULL,NULL,'confirmed','pending',1000.00,'pending',NULL,'',NULL,NULL,'2025-11-23 23:36:12','2025-12-06 00:17:49'),(15,'BK2025000015',14,7,19,'2025-12-27','04:09:00','sedan',NULL,NULL,NULL,NULL,NULL,'confirmed','pending',1000.00,'pending',NULL,'',NULL,NULL,'2025-11-23 23:46:38','2025-12-06 00:17:49'),(16,'BK2025000016',14,7,18,'2025-11-25','05:28:00','','asdasd','sawqsd','kirmizi',NULL,NULL,'pending','pending',200.00,'pending',NULL,'',NULL,NULL,'2025-11-23 23:56:53','2025-12-06 00:17:49'),(17,'BK2025000017',14,7,19,'2025-11-26','06:33:00','','98 atg 6584','verli','green',NULL,NULL,'confirmed','pending',1000.00,'pending',NULL,'',NULL,NULL,'2025-11-23 23:58:39','2025-12-06 00:17:49'),(18,'BK2025000018',14,7,19,'2025-11-30','04:55:00','','35 HZL 556','Passat 1.5 TSI','Siyah',NULL,NULL,'confirmed','pending',1000.00,'pending',NULL,'',NULL,NULL,'2025-11-24 00:26:01','2025-12-06 00:17:49'),(20,'BK2025000020',14,7,19,'2025-11-28','04:42:00','','98 atg 6584','verli','green',NULL,NULL,'pending','pending',1000.00,'pending',NULL,'',NULL,NULL,'2025-11-26 00:08:07','2025-12-06 00:17:49'),(21,'BK2025000021',14,7,18,'2025-11-20','13:45:00','','asdasd','sawqsd','kirmizi',NULL,NULL,'pending','pending',200.00,'pending',NULL,'',NULL,NULL,'2025-11-26 19:10:32','2025-12-06 00:17:49'),(22,'BK2025000022',14,2,5,'2025-11-20','14:00:00','sedan','34ABC123','Honda Civic','Blue',NULL,NULL,'completed','reviewed',12.00,'paid','card',NULL,NULL,'2025-11-28 11:04:17','2025-11-28 11:04:17','2025-12-06 00:17:49'),(23,'BK2025000023',14,2,5,'2025-11-20','14:00:00','sedan','34ABC123','Honda Civic','Blue',NULL,NULL,'completed','pending',12.00,'paid','card',NULL,NULL,'2025-11-28 11:04:33','2025-11-28 11:04:33','2025-12-06 00:17:49'),(24,'BK2025000024',14,7,17,'2025-11-30','09:00:00','sedan','34ABC123','Honda Civic','Blue',NULL,NULL,'confirmed','pending',45.00,'pending','cash','Please use eco-friendly products',NULL,NULL,'2025-11-28 11:12:17','2025-12-06 00:17:49'),(25,'BK2025000025',14,7,18,'2025-12-02','12:00:00','suv','06XYZ456','Toyota RAV4','White',NULL,NULL,'completed','pending',200.00,'paid','card','Interior cleaning needed',NULL,'2025-12-02 11:25:53','2025-11-28 11:12:17','2025-12-06 00:17:49'),(26,'BK2025000026',14,7,19,'2025-12-04','15:00:00','sedan','35DEF789','BMW 3 Series','Black',NULL,NULL,'completed','pending',1000.00,'paid','online','Extra attention to wheels',NULL,'2025-12-06 00:31:13','2025-11-28 11:12:17','2025-12-06 00:31:13'),(27,'BK2025000027',14,7,17,'2025-11-25','10:00:00','truck','16GHI321','Ford F-150','Red',NULL,NULL,'cancelled','pending',45.00,'refunded','cash',NULL,'Customer changed plans',NULL,'2025-11-28 11:12:17','2025-12-06 00:17:49'),(28,'BK2025000028',14,7,18,'2025-11-22','14:00:00','van','41JKL654','Mercedes Sprinter','Silver',NULL,NULL,'cancelled','pending',200.00,'refunded','card','VIP service requested','Weather conditions',NULL,'2025-11-28 11:12:17','2025-12-06 00:17:49'),(29,'BK2025000029',14,7,17,'2025-11-21','09:00:00','suv','34MNO987','Jeep Wrangler','Green',NULL,NULL,'completed','pending',45.00,'paid','cash','First time customer',NULL,'2025-11-21 06:30:00','2025-11-28 11:12:17','2025-12-06 00:17:49'),(30,'BK2025000030',14,7,18,'2025-11-14','11:00:00','sedan','34PQR135','Tesla Model 3','White',NULL,NULL,'completed','pending',200.00,'paid','card','Regular customer - monthly service',NULL,'2025-11-14 08:30:00','2025-11-28 11:12:18','2025-12-06 00:17:49'),(31,'BK2025000031',14,7,19,'2025-11-07','13:00:00','sedan','06STU246','Audi A4','Gray',NULL,NULL,'completed','pending',1000.00,'paid','online',NULL,NULL,'2025-11-07 10:30:00','2025-11-28 11:12:18','2025-12-06 00:17:49'),(32,'BK2025000032',14,7,17,'2025-10-31','15:00:00','suv','35VWX357','Volvo XC90','Blue',NULL,NULL,'completed','pending',45.00,'paid','cash','VIP service requested',NULL,'2025-10-31 12:30:00','2025-11-28 11:12:18','2025-12-06 00:17:49'),(33,'BK2025000033',14,7,17,'2025-11-30','09:00:00','sedan','34ABC123','Honda Civic','Blue',NULL,NULL,'confirmed','pending',45.00,'pending','cash','Please use eco-friendly products',NULL,NULL,'2025-11-28 11:12:27','2025-12-06 00:17:49'),(34,'BK2025000034',14,7,18,'2025-12-02','12:00:00','suv','06XYZ456','Toyota RAV4','White',NULL,NULL,'completed','reviewed',200.00,'paid','card','Interior cleaning needed',NULL,'2025-12-02 11:25:53','2025-11-28 11:12:27','2025-12-06 00:17:49'),(35,'BK2025000035',14,7,19,'2025-12-04','15:00:00','sedan','35DEF789','BMW 3 Series','Black',NULL,NULL,'completed','reviewed',1000.00,'paid','online','Extra attention to wheels',NULL,'2025-12-06 00:31:13','2025-11-28 11:12:27','2025-12-06 00:31:42'),(36,'BK2025000036',14,7,17,'2025-11-25','10:00:00','truck','16GHI321','Ford F-150','Red',NULL,NULL,'cancelled','pending',45.00,'refunded','cash',NULL,'Customer changed plans',NULL,'2025-11-28 11:12:27','2025-12-06 00:17:49'),(37,'BK2025000037',14,7,18,'2025-11-22','14:00:00','van','41JKL654','Mercedes Sprinter','Silver',NULL,NULL,'cancelled','pending',200.00,'refunded','card','VIP service requested','Weather conditions',NULL,'2025-11-28 11:12:27','2025-12-06 00:17:49'),(38,'BK2025000038',14,7,17,'2025-11-21','09:00:00','suv','34MNO987','Jeep Wrangler','Green',NULL,NULL,'completed','reviewed',45.00,'paid','cash','First time customer',NULL,'2025-11-21 06:30:00','2025-11-28 11:12:27','2025-12-06 00:17:49'),(39,'BK2025000039',14,7,18,'2025-11-14','11:00:00','sedan','34PQR135','Tesla Model 3','White',NULL,NULL,'completed','reviewed',200.00,'paid','card','Regular customer - monthly service',NULL,'2025-11-14 08:30:00','2025-11-28 11:12:27','2025-12-06 00:17:49'),(40,'BK2025000040',14,7,19,'2025-11-07','13:00:00','sedan','06STU246','Audi A4','Gray',NULL,NULL,'completed','reviewed',1000.00,'paid','online',NULL,NULL,'2025-11-07 10:30:00','2025-11-28 11:12:27','2025-12-06 00:17:49'),(41,'BK2025000041',14,7,17,'2025-10-31','15:00:00','suv','35VWX357','Volvo XC90','Blue',NULL,NULL,'completed','reviewed',45.00,'paid','cash','VIP service requested',NULL,'2025-10-31 12:30:00','2025-11-28 11:12:27','2025-12-06 00:17:49'),(42,'BK2025000042',14,7,19,'2025-11-26','06:21:00','','07 ADC 789','IX','Metalik',NULL,NULL,'pending','pending',1000.00,'pending',NULL,'',NULL,NULL,'2025-11-30 00:01:26','2025-12-06 00:17:49'),(43,'BK2025000043',14,7,19,'2025-12-18','05:46:00','','98 atg 6584','verli','green',NULL,NULL,'confirmed','pending',1000.00,'pending',NULL,'',NULL,NULL,'2025-11-30 23:12:34','2025-12-06 00:17:49'),(44,'BK2025000044',27,7,19,'2025-12-17','07:21:00','sedan','98 akv 000',NULL,NULL,NULL,NULL,'pending','pending',1000.00,'pending',NULL,'sdfdfsdf',NULL,NULL,'2025-12-04 00:18:11','2025-12-06 00:17:49'),(45,'BK2025000045',27,7,19,'2025-12-10','05:25:00','sedan','98 akv 111',NULL,NULL,NULL,NULL,'confirmed','pending',1000.00,'pending',NULL,'asdasdasd',NULL,NULL,'2025-12-04 00:23:57','2025-12-06 00:17:49'),(46,'BK2025000046',27,7,19,'2025-12-12','06:47:00','sedan','98 akv 000',NULL,NULL,NULL,NULL,'confirmed','pending',1000.00,'pending',NULL,'adasdasd',NULL,NULL,'2025-12-04 00:43:37','2025-12-06 00:17:49'),(47,'BK2025000047',27,7,19,'2025-12-12','06:45:00','sedan','98 akv 777',NULL,NULL,NULL,NULL,'confirmed','pending',1000.00,'pending',NULL,'tryrtyrty',NULL,NULL,'2025-12-04 00:45:43','2025-12-06 00:17:49');
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carwash_profiles`
--

DROP TABLE IF EXISTS `carwash_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `carwash_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `business_license` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `address` text NOT NULL,
  `district` varchar(191) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) NOT NULL DEFAULT 'Turkey',
  `postal_code` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `opening_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`opening_hours`)),
  `featured_image` varchar(255) DEFAULT NULL,
  `gallery_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gallery_images`)),
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `social_media` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_media`)),
  `average_rating` decimal(3,2) DEFAULT NULL,
  `total_reviews` int(11) NOT NULL DEFAULT 0,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `city_index` (`city`),
  KEY `rating_index` (`average_rating`),
  KEY `verified_index` (`verified`),
  FULLTEXT KEY `search_index` (`business_name`,`description`),
  CONSTRAINT `carwash_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carwash_profiles`
--

LOCK TABLES `carwash_profiles` WRITE;
/*!40000 ALTER TABLE `carwash_profiles` DISABLE KEYS */;
INSERT INTO `carwash_profiles` VALUES (6,27,'├ûzil Oto Y─▒kama',NULL,NULL,'Atat├╝rk Caddesi No: 123',NULL,'─░stanbul','─░stanbul','Turkey',NULL,NULL,NULL,NULL,NULL,NULL,'ozil@gmail.com','+90 216 555 0123',NULL,NULL,NULL,0,1,'2025-11-28 00:19:59','2025-11-28 00:19:59',NULL);
/*!40000 ALTER TABLE `carwash_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `carwash_stats_view`
--

DROP TABLE IF EXISTS `carwash_stats_view`;
/*!50001 DROP VIEW IF EXISTS `carwash_stats_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `carwash_stats_view` AS SELECT
 1 AS `id`,
  1 AS `name`,
  1 AS `city`,
  1 AS `rating`,
  1 AS `total_reviews`,
  1 AS `total_bookings`,
  1 AS `completed_bookings`,
  1 AS `total_revenue`,
  1 AS `total_services` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `carwash_status_backup`
--

DROP TABLE IF EXISTS `carwash_status_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `carwash_status_backup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `carwash_id` int(11) NOT NULL,
  `old_status` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carwash_status_backup`
--

LOCK TABLES `carwash_status_backup` WRITE;
/*!40000 ALTER TABLE `carwash_status_backup` DISABLE KEYS */;
/*!40000 ALTER TABLE `carwash_status_backup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carwashes`
--

DROP TABLE IF EXISTS `carwashes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `carwashes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'USA',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `opening_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`opening_hours`)),
  `image` varchar(255) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `owner_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `tax_number` varchar(50) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `owner_name` varchar(100) DEFAULT NULL,
  `owner_phone` varchar(20) DEFAULT NULL,
  `owner_birth_date` date DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `exterior_price` decimal(10,2) DEFAULT 0.00,
  `interior_price` decimal(10,2) DEFAULT 0.00,
  `detailing_price` decimal(10,2) DEFAULT 0.00,
  `opening_time` time DEFAULT NULL,
  `closing_time` time DEFAULT NULL,
  `capacity` int(11) DEFAULT 0,
  `profile_image` varchar(255) DEFAULT NULL,
  `logo_image` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `tc_kimlik` varchar(11) DEFAULT NULL COMMENT 'Turkish ID Number',
  `logo_path` varchar(255) DEFAULT NULL,
  `profile_image_path` varchar(255) DEFAULT NULL,
  `mobile_phone` varchar(50) DEFAULT NULL,
  `social_media` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_media`)),
  `working_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`working_hours`)),
  `services` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`services`)),
  `postal_code` varchar(20) DEFAULT NULL,
  `certificate_path` varchar(255) DEFAULT NULL,
  `rating_average` decimal(3,2) DEFAULT 0.00,
  `rating_count` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`),
  KEY `idx_city` (`city`),
  KEY `idx_rating` (`rating`),
  KEY `idx_active` (`is_active`),
  CONSTRAINT `carwashes_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carwashes`
--

LOCK TABLES `carwashes` WRITE;
/*!40000 ALTER TABLE `carwashes` DISABLE KEYS */;
INSERT INTO `carwashes` VALUES (2,'Express Auto Spa','Fast and efficient car wash with eco-friendly products','456 Oak Avenue','Los Angeles','CA','90001','USA',34.05220000,-118.24370000,'+1-555-2000','express@autospa.com',NULL,'{\"monday\": \"7:00-21:00\", \"tuesday\": \"7:00-21:00\", \"wednesday\": \"7:00-21:00\", \"thursday\": \"7:00-21:00\", \"friday\": \"7:00-23:00\", \"saturday\": \"8:00-23:00\", \"sunday\": \"8:00-20:00\"}',NULL,5.00,1,1,1,'2025-10-13 13:30:12','2025-12-06 00:17:49',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL,NULL,'pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'90001',NULL,5.00,1),(7,'├ûzil Oto Y─▒kama',NULL,'Yukar─▒ Pazarc─▒, 4075. Sk., 07600 Manavgat/Antalya','antalya',NULL,NULL,'T├╝rkiye',NULL,NULL,'05364654194','ozil@gmail.com',NULL,NULL,NULL,4.17,6,1,NULL,'2025-11-19 14:13:56','2025-12-06 00:31:42',27,'9584','6546546',NULL,NULL,NULL,'2020-11-12','manavgat',0.00,0.00,0.00,NULL,NULL,0,NULL,'/backend/uploads/business_logo/8204639.jpg','A├º─▒k',NULL,'logo_27_1764113843.jpg',NULL,'05548322802',NULL,'{\"monday\":{\"start\":\"10:30\",\"end\":\"06:34\"},\"tuesday\":{\"start\":\"10:05\",\"end\":\"20:00\"},\"wednesday\":{\"start\":\"08:00\",\"end\":\"20:00\"},\"thursday\":{\"start\":\"08:00\",\"end\":\"20:00\"},\"friday\":{\"start\":\"08:00\",\"end\":\"20:00\"},\"saturday\":{\"start\":\"09:00\",\"end\":\"18:00\"},\"sunday\":{\"start\":\"11:03\",\"end\":\"11:04\"}}',NULL,'07600',NULL,4.40,5);
/*!40000 ALTER TABLE `carwashes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_profiles`
--

DROP TABLE IF EXISTS `customer_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `car_brand` varchar(50) DEFAULT NULL,
  `car_model` varchar(50) DEFAULT NULL,
  `car_year` year(4) DEFAULT NULL,
  `car_color` varchar(30) DEFAULT NULL,
  `license_plate` varchar(20) DEFAULT NULL,
  `notifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notifications`)),
  `preferred_services` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferred_services`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `customer_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_profiles`
--

LOCK TABLES `customer_profiles` WRITE;
/*!40000 ALTER TABLE `customer_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `favorites`
--

DROP TABLE IF EXISTS `favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `favorites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `carwash_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_carwash` (`user_id`,`carwash_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_carwash_id` (`carwash_id`),
  CONSTRAINT `fk_favorites_carwash` FOREIGN KEY (`carwash_id`) REFERENCES `carwashes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_favorites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Customer favorite carwashes';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `favorites`
--

LOCK TABLES `favorites` WRITE;
/*!40000 ALTER TABLE `favorites` DISABLE KEYS */;
/*!40000 ALTER TABLE `favorites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `is_read_index` (`is_read`),
  KEY `ref_index` (`reference_id`,`reference_type`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `payment_method` enum('credit_card','cash','online_transfer','mobile_payment') NOT NULL,
  `status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `payment_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `receipt_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `transaction_id` (`transaction_id`),
  KEY `status_index` (`status`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promotions`
--

DROP TABLE IF EXISTS `promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promotions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `carwash_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `min_purchase` decimal(10,2) DEFAULT NULL,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `carwash_id` (`carwash_id`),
  KEY `code` (`code`),
  KEY `date_range_index` (`start_date`,`end_date`),
  KEY `active_index` (`is_active`),
  CONSTRAINT `promotions_ibfk_1` FOREIGN KEY (`carwash_id`) REFERENCES `carwash_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promotions`
--

LOCK TABLES `promotions` WRITE;
/*!40000 ALTER TABLE `promotions` DISABLE KEYS */;
/*!40000 ALTER TABLE `promotions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `carwash_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `title` varchar(100) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `response` text DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `is_visible` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_carwash` (`carwash_id`),
  KEY `idx_rating` (`rating`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`carwash_id`) REFERENCES `carwashes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (8,14,7,41,5,NULL,'├çok memnun kald─▒m, hizmet m├╝kemmeldi ve ara├º ├ºok temiz oldu. Te┼ƒekk├╝rler!',NULL,NULL,0,1,'2025-12-01 00:19:47','2025-12-01 00:19:47'),(9,14,7,40,4,NULL,'&quot;Personel ├ºok ilgiliydi ve ara├º y─▒kama ├ºok profesyoneldi. Zaman─▒nda ve sorunsuz bir hizmet ald─▒m. Kesinlikle tavsiye ederim.&quot;',NULL,NULL,0,1,'2025-12-01 00:20:58','2025-12-01 00:20:58'),(10,14,7,39,4,NULL,'&quot;Harika bir deneyimdi! Ara├º tam istedi─ƒim gibi temizlendi ve ├ºal─▒┼ƒanlar g├╝ler y├╝zl├╝yd├╝. Tekrar gelirim.&quot;',NULL,NULL,0,1,'2025-12-01 00:22:33','2025-12-01 00:22:33'),(11,14,7,38,5,NULL,'&quot;Hizmet h─▒zl─▒ ve kaliteliydi. ├çok te┼ƒekk├╝rler!&quot;',NULL,NULL,0,1,'2025-12-01 11:07:46','2025-12-01 11:07:46'),(12,14,2,22,5,NULL,'&quot;Personel ├ºok ilgiliydi ve ara├º y─▒kama ├ºok profesyoneldi. Zaman─▒nda ve sorunsuz bir hizmet ald─▒m. Kesinlikle tavsiye ederim.&quot;',NULL,NULL,0,1,'2025-12-01 11:08:35','2025-12-01 11:08:35'),(13,14,7,34,4,NULL,'cok iyi',NULL,NULL,0,1,'2025-12-03 01:31:48','2025-12-03 01:31:48'),(14,14,7,35,3,NULL,'iyi',NULL,NULL,0,1,'2025-12-06 00:31:42','2025-12-06 00:31:42');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER update_carwash_rating_insert AFTER INSERT ON reviews

FOR EACH ROW

BEGIN

    UPDATE carwashes 

    SET rating = (

        SELECT AVG(rating) 

        FROM reviews 

        WHERE carwash_id = NEW.carwash_id AND is_visible = TRUE

    ),

    total_reviews = (

        SELECT COUNT(*) 

        FROM reviews 

        WHERE carwash_id = NEW.carwash_id AND is_visible = TRUE

    )

    WHERE id = NEW.carwash_id;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER update_carwash_rating_update AFTER UPDATE ON reviews

FOR EACH ROW

BEGIN

    UPDATE carwashes 

    SET rating = (

        SELECT AVG(rating) 

        FROM reviews 

        WHERE carwash_id = NEW.carwash_id AND is_visible = TRUE

    ),

    total_reviews = (

        SELECT COUNT(*) 

        FROM reviews 

        WHERE carwash_id = NEW.carwash_id AND is_visible = TRUE

    )

    WHERE id = NEW.carwash_id;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER update_carwash_rating_delete AFTER DELETE ON reviews

FOR EACH ROW

BEGIN

    UPDATE carwashes 

    SET rating = COALESCE((

        SELECT AVG(rating) 

        FROM reviews 

        WHERE carwash_id = OLD.carwash_id AND is_visible = TRUE

    ), 0),

    total_reviews = (

        SELECT COUNT(*) 

        FROM reviews 

        WHERE carwash_id = OLD.carwash_id AND is_visible = TRUE

    )

    WHERE id = OLD.carwash_id;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `security_settings`
--

DROP TABLE IF EXISTS `security_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Application security configuration';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_settings`
--

LOCK TABLES `security_settings` WRITE;
/*!40000 ALTER TABLE `security_settings` DISABLE KEYS */;
INSERT INTO `security_settings` VALUES (1,'max_login_attempts','5','Maximum failed login attempts before lockout','2025-12-06 00:06:53'),(2,'login_timeout_minutes','15','Lockout duration after max attempts','2025-12-06 00:06:53'),(3,'password_min_length','8','Minimum password length','2025-12-06 00:06:53'),(4,'session_lifetime_minutes','30','Session timeout in minutes','2025-12-06 00:06:53'),(5,'require_2fa_for_admin','false','Require 2FA for admin accounts','2025-12-06 00:06:53');
/*!40000 ALTER TABLE `security_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_categories`
--

DROP TABLE IF EXISTS `service_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_categories`
--

LOCK TABLES `service_categories` WRITE;
/*!40000 ALTER TABLE `service_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `carwash_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `duration` int(11) NOT NULL COMMENT 'Duration in minutes',
  `category` enum('basic','standard','premium','deluxe') DEFAULT 'basic',
  `image` varchar(255) DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_carwash` (`carwash_id`),
  KEY `idx_category` (`category`),
  KEY `idx_price` (`price`),
  CONSTRAINT `services_ibfk_1` FOREIGN KEY (`carwash_id`) REFERENCES `carwashes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (5,2,'Express Wash','Quick exterior wash',12.00,NULL,10,'basic',NULL,'[\"Automated wash\", \"Quick dry\"]',1,'2025-10-13 13:30:12','2025-10-13 13:30:12',0),(6,2,'Eco Clean','Environmentally friendly complete clean',35.00,NULL,45,'standard',NULL,'[\"Eco-friendly products\", \"Exterior wash\", \"Interior cleaning\", \"Biodegradable wax\"]',1,'2025-10-13 13:30:12','2025-10-13 13:30:12',0),(7,2,'Premium Spa','Luxury detailing service',85.00,NULL,100,'premium',NULL,'[\"Full detailing\", \"Premium wax\", \"Interior treatment\", \"Odor elimination\"]',1,'2025-10-13 13:30:12','2025-10-13 13:30:12',0),(17,7,'iyi yikamasi','koltuk-baraj-dirikssyon',45.00,'active',30,'basic',NULL,NULL,1,'2025-11-19 21:58:09','2025-11-19 22:22:38',0),(18,7,'daha iyi yikama','dfsdf',200.00,'active',20,'basic',NULL,NULL,1,'2025-11-19 22:50:09','2025-11-19 22:50:09',0),(19,7,'best','hershey',1000.00,'active',45,'basic',NULL,NULL,1,'2025-11-19 23:14:57','2025-11-19 23:14:57',0);
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'site_name','CarWash Pro','string','Website name',1,'2025-10-13 13:30:12','2025-10-13 13:30:12'),(2,'site_email','contact@carwash.com','string','Contact email address',1,'2025-10-13 13:30:12','2025-10-13 13:30:12'),(3,'site_phone','+1-555-0000','string','Contact phone number',1,'2025-10-13 13:30:12','2025-10-13 13:30:12'),(4,'booking_advance_days','30','number','How many days in advance bookings can be made',0,'2025-10-13 13:30:12','2025-10-13 13:30:12'),(5,'cancellation_hours','24','number','Hours before booking when cancellation is allowed',0,'2025-10-13 13:30:12','2025-10-13 13:30:12'),(6,'tax_rate','0.08','number','Tax rate for services',0,'2025-10-13 13:30:12','2025-10-13 13:30:12'),(7,'currency','USD','string','Currency code',1,'2025-10-13 13:30:12','2025-10-13 13:30:12'),(8,'timezone','America/New_York','string','Default timezone',0,'2025-10-13 13:30:12','2025-10-13 13:30:12'),(9,'enable_reviews','1','boolean','Enable customer reviews',0,'2025-10-13 13:30:12','2025-10-13 13:30:12'),(10,'reviews_require_booking','1','boolean','Require completed booking to leave review',0,'2025-10-13 13:30:12','2025-10-13 13:30:12'),(11,'enable_notifications','1','boolean','Enable email notifications',0,'2025-10-13 13:30:12','2025-10-13 13:30:12'),(12,'maintenance_mode','0','boolean','Enable maintenance mode',0,'2025-10-13 13:30:12','2025-10-13 13:30:12');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_members`
--

DROP TABLE IF EXISTS `staff_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT 'Link to users table if staff has login',
  `carwash_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `status` enum('active','inactive','on_leave') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_carwash_id` (`carwash_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_staff_carwash` FOREIGN KEY (`carwash_id`) REFERENCES `carwashes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_staff_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Staff members per carwash location';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_members`
--

LOCK TABLES `staff_members` WRITE;
/*!40000 ALTER TABLE `staff_members` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `time_slots`
--

DROP TABLE IF EXISTS `time_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `time_slots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `carwash_id` int(11) NOT NULL,
  `day_of_week` tinyint(1) NOT NULL COMMENT '0=Sunday, 1=Monday, ..., 6=Saturday',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `carwash_id` (`carwash_id`),
  KEY `day_time_index` (`day_of_week`,`start_time`,`end_time`),
  CONSTRAINT `time_slots_ibfk_1` FOREIGN KEY (`carwash_id`) REFERENCES `carwash_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `time_slots`
--

LOCK TABLES `time_slots` WRITE;
/*!40000 ALTER TABLE `time_slots` DISABLE KEYS */;
/*!40000 ALTER TABLE `time_slots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ui_labels`
--

DROP TABLE IF EXISTS `ui_labels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ui_labels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label_key` varchar(100) NOT NULL COMMENT 'Technical identifier (snake_case)',
  `language_code` varchar(5) NOT NULL DEFAULT 'tr' COMMENT 'ISO 639-1 code',
  `label_value` varchar(500) NOT NULL COMMENT 'Translated text',
  `context` varchar(100) DEFAULT NULL COMMENT 'Form/page context',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_label_lang` (`label_key`,`language_code`),
  KEY `idx_context` (`context`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='UI labels and translations - keeps technical identifiers separate from display text';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ui_labels`
--

LOCK TABLES `ui_labels` WRITE;
/*!40000 ALTER TABLE `ui_labels` DISABLE KEYS */;
INSERT INTO `ui_labels` VALUES (1,'customer_name','tr','Ad Soyad','customer_registration','2025-12-06 00:17:49','2025-12-06 00:17:49'),(2,'customer_email','tr','E-posta','customer_registration','2025-12-06 00:17:49','2025-12-06 00:17:49'),(3,'customer_password','tr','┼₧ifre','customer_registration','2025-12-06 00:17:49','2025-12-06 00:17:49'),(4,'customer_phone','tr','Telefon','customer_registration','2025-12-06 00:17:49','2025-12-06 00:17:49'),(5,'register_button','tr','Kay─▒t Ol','customer_registration','2025-12-06 00:17:49','2025-12-06 00:17:49'),(6,'business_name','tr','─░┼ƒletme Ad─▒','carwash_registration','2025-12-06 00:17:49','2025-12-06 00:17:49'),(7,'owner_name','tr','Sahip Ad─▒ Soyad─▒','carwash_registration','2025-12-06 00:17:49','2025-12-06 00:17:49'),(8,'owner_phone','tr','Sahip Telefon','carwash_registration','2025-12-06 00:17:49','2025-12-06 00:17:49'),(9,'tax_number','tr','Vergi Numaras─▒','carwash_registration','2025-12-06 00:17:49','2025-12-06 00:17:49'),(10,'license_number','tr','Ruhsat Numaras─▒','carwash_registration','2025-12-06 00:17:49','2025-12-06 00:17:49'),(11,'birth_date','tr','Do─ƒum Tarihi','carwash_registration','2025-12-06 00:17:49','2025-12-06 00:17:49'),(12,'business_address','tr','Adres','carwash_registration','2025-12-06 00:17:49','2025-12-06 00:17:49'),(13,'city','tr','┼₧ehir','carwash_registration','2025-12-06 00:17:49','2025-12-06 00:17:49'),(14,'district','tr','─░l├ºe','carwash_registration','2025-12-06 00:17:49','2025-12-06 00:17:49'),(15,'profile_image','tr','Profil Foto─ƒraf─▒','carwash_registration','2025-12-06 00:17:49','2025-12-06 00:17:49'),(16,'logo_image','tr','─░┼ƒletme Logosu','carwash_registration','2025-12-06 00:17:49','2025-12-06 00:17:49'),(17,'exterior_wash','tr','D─▒┼ƒ Y─▒kama','services','2025-12-06 00:17:49','2025-12-06 00:17:49'),(18,'interior_wash','tr','─░├º Y─▒kama','services','2025-12-06 00:17:49','2025-12-06 00:17:49'),(19,'booking_location','tr','Konum','booking','2025-12-06 00:17:49','2025-12-06 00:17:49'),(20,'booking_service','tr','Hizmet','booking','2025-12-06 00:17:49','2025-12-06 00:17:49'),(21,'booking_vehicle','tr','Ara├º','booking','2025-12-06 00:17:49','2025-12-06 00:17:49'),(22,'booking_date','tr','Tarih','booking','2025-12-06 00:17:49','2025-12-06 00:17:49'),(23,'booking_time','tr','Saat','booking','2025-12-06 00:17:49','2025-12-06 00:17:49'),(24,'booking_notes','tr','Notlar','booking','2025-12-06 00:17:49','2025-12-06 00:17:49'),(25,'create_reservation','tr','Yeni Rezervasyon Olu┼ƒtur','booking','2025-12-06 00:17:49','2025-12-06 00:17:49'),(26,'status_pending','tr','Bekliyor','status','2025-12-06 00:17:49','2025-12-06 00:17:49'),(27,'status_confirmed','tr','Onayland─▒','status','2025-12-06 00:17:49','2025-12-06 00:17:49'),(28,'status_in_progress','tr','─░┼ƒlemde','status','2025-12-06 00:17:49','2025-12-06 00:17:49'),(29,'status_completed','tr','Tamamland─▒','status','2025-12-06 00:17:49','2025-12-06 00:17:49'),(30,'status_cancelled','tr','─░ptal','status','2025-12-06 00:17:49','2025-12-06 00:17:49'),(31,'status_no_show','tr','Gelmedi','status','2025-12-06 00:17:49','2025-12-06 00:17:49'),(32,'review_rating','tr','De─ƒerlendirme','review','2025-12-06 00:17:49','2025-12-06 00:17:49'),(33,'review_comment','tr','Yorum','review','2025-12-06 00:17:49','2025-12-06 00:17:49'),(34,'review_submit','tr','G├╢nder','review','2025-12-06 00:17:49','2025-12-06 00:17:49'),(35,'vehicle_brand','tr','Marka','vehicle','2025-12-06 00:17:49','2025-12-06 00:17:49'),(36,'vehicle_model','tr','Model','vehicle','2025-12-06 00:17:49','2025-12-06 00:17:49'),(37,'vehicle_plate','tr','Plaka','vehicle','2025-12-06 00:17:49','2025-12-06 00:17:49'),(38,'vehicle_year','tr','Y─▒l','vehicle','2025-12-06 00:17:49','2025-12-06 00:17:49'),(39,'vehicle_color','tr','Renk','vehicle','2025-12-06 00:17:49','2025-12-06 00:17:49'),(40,'customer_name','en','Full Name','customer_registration','2025-12-06 00:17:49','2025-12-06 00:17:49'),(41,'customer_email','en','Email','customer_registration','2025-12-06 00:17:49','2025-12-06 00:17:49'),(42,'customer_password','en','Password','customer_registration','2025-12-06 00:17:49','2025-12-06 00:17:49'),(43,'customer_phone','en','Phone','customer_registration','2025-12-06 00:17:49','2025-12-06 00:17:49'),(44,'register_button','en','Register','customer_registration','2025-12-06 00:17:49','2025-12-06 00:17:49'),(45,'business_name','en','Business Name','carwash_registration','2025-12-06 00:17:49','2025-12-06 00:17:49'),(46,'owner_name','en','Owner Name','carwash_registration','2025-12-06 00:17:49','2025-12-06 00:17:49'),(47,'booking_location','en','Location','booking','2025-12-06 00:17:49','2025-12-06 00:17:49'),(48,'booking_service','en','Service','booking','2025-12-06 00:17:49','2025-12-06 00:17:49'),(49,'booking_date','en','Date','booking','2025-12-06 00:17:49','2025-12-06 00:17:49'),(50,'booking_time','en','Time','booking','2025-12-06 00:17:49','2025-12-06 00:17:49');
/*!40000 ALTER TABLE `ui_labels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_profiles`
--

DROP TABLE IF EXISTS `user_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferences`)),
  `notification_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notification_settings`)),
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL COMMENT 'Profile image path',
  `phone` varchar(20) DEFAULT NULL,
  `home_phone` varchar(20) DEFAULT NULL,
  `national_id` varchar(20) DEFAULT NULL,
  `driver_license` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_profiles`
--

LOCK TABLES `user_profiles` WRITE;
/*!40000 ALTER TABLE `user_profiles` DISABLE KEYS */;
INSERT INTO `user_profiles` VALUES (1,14,'A. Pazarc─▒\r\nZ├╝beyde Han─▒m Cd. No:60','Antalyai',NULL,'turkey',NULL,NULL,NULL,'{\"favorites\":[\"1\"]}',NULL,NULL,'2025-11-07 13:43:00','2025-12-06 00:32:08','uploads/profiles/profile_14_1764457348.jpg','+90554832288','83228299','99656172222','DB 7894566'),(20,27,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-02 11:34:04','2025-12-03 19:53:15','uploads/profiles/profile_27_1764791595.jpg','05364654194',NULL,NULL,NULL);
/*!40000 ALTER TABLE `user_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_vehicles`
--

DROP TABLE IF EXISTS `user_vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_vehicles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `license_plate` varchar(50) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `vehicle_type` enum('sedan','suv','hatchback','pickup','van','motorcycle','other') DEFAULT 'sedan',
  `is_default` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=126 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_vehicles`
--

LOCK TABLES `user_vehicles` WRITE;
/*!40000 ALTER TABLE `user_vehicles` DISABLE KEYS */;
INSERT INTO `user_vehicles` VALUES (115,14,'volvo','coroola','35 afd 69548',NULL,'beyazi','uploads/vehicles/1763125415_341a031617c3a2c3.jpg','2025-11-14 16:03:35','2025-11-26 20:31:29','sedan',0),(116,14,'sadasd','sawqsd','asdasd',2014,'kirmizi','uploads/vehicles/1763208940_4337b8c0f8f2522f.jpg','2025-11-15 15:15:39','2025-11-17 01:28:12','sedan',0),(117,14,'danaa','verli','98 atg 6584',2018,'green','uploads/vehicles/1763941925_785f10d3cbaa6e09.jpg','2025-11-23 03:27:32','2025-11-27 14:29:24','sedan',0),(118,14,'Renault','Megane Sedan Touch','06 KRT 987',2019,'Gri Metalik','uploads/vehicles/1763941829_dac819d1e1a193ae.jpg','2025-11-24 02:50:29',NULL,'sedan',0),(119,14,'Volkswagen','Passat 1.5 TSI','35 HZL 556',2021,'Siyahi','uploads/vehicles/1763941888_7d28fd8a78ac820d.jpg','2025-11-24 02:51:28','2025-11-30 03:45:53','sedan',0),(121,14,'BMW','IX','07 ADC 789',2025,'Metalik','uploads/vehicles/vehicle_692a2e9ec6677_1764372126.png','2025-11-29 02:52:06','2025-11-29 02:52:06','sedan',0),(122,14,'Test Brand','Test Model','TEST9738',2025,'greeni','uploads/vehicles/vehicle_692a37636db78_1764374371.jpg','2025-11-29 02:52:13','2025-11-29 03:29:31','sedan',0);
/*!40000 ALTER TABLE `user_vehicles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image_path` varchar(255) DEFAULT NULL,
  `home_phone` varchar(20) DEFAULT NULL COMMENT 'Home phone number',
  `national_id` varchar(20) DEFAULT NULL COMMENT 'National ID number',
  `driver_license` varchar(20) DEFAULT NULL COMMENT 'Driver license number (optional)',
  `role` enum('admin','customer','staff','carwash') DEFAULT 'customer',
  `profile_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified` tinyint(1) DEFAULT 0,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_role` (`role`),
  KEY `idx_national_id` (`national_id`),
  KEY `idx_driver_license` (`driver_license`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','smoke+test@example.test','Smoke Test','$2y$10$q1Zmy9g2Yg8P0NNaGWJkTupZTiEtNZ9722rpQP94Qzyi4/2DCtuZm','System Administrator','5550001111',NULL,'021234567','12345678901','','admin',NULL,1,1,NULL,'2025-10-13 13:30:12','2025-12-06 00:07:57','2025-10-20 00:21:39',NULL,NULL),(14,'ali karaji','hasan@carwash.com','hasan','$2y$10$76oMrRFegpNSsceL1iPmW.TXSVdbxYvblXriBRovnvdxCwGYsT/zi','hasan','+905548322802',NULL,'83228233','99656178988','d 7894561','customer','uploads/profiles/profile_14_1764379912.jpg',1,0,NULL,'2025-10-25 12:50:53','2025-11-29 22:56:49',NULL,NULL,NULL),(27,'ozil','ozil@gmail.com','├ûzil Oto Y─▒kama','$2y$10$/LBmMTymrfvuTlKKXwRRUuu1jGlQqQYTsBekmK0b6.XaMMbt3MWnq','├ûzil Oto Y─▒kama','05364654194',NULL,NULL,NULL,NULL,'carwash','uploads/profiles/profile_27_1764721785.jpg',1,0,NULL,'2025-11-19 14:13:56','2025-12-03 00:29:45',NULL,NULL,NULL),(28,'kral@gmail.com','kral@gmail.com',NULL,'$2y$10$q1Zmy9g2Yg8P0NNaGWJkTupZTiEtNZ9722rpQP94Qzyi4/2DCtuZm','kral',NULL,NULL,NULL,NULL,NULL,'admin',NULL,1,0,NULL,'2025-12-01 11:32:26','2025-12-06 00:07:57',NULL,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `model` varchar(100) NOT NULL,
  `year` year(4) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `license_plate` varchar(20) DEFAULT NULL,
  `vehicle_type` enum('sedan','suv','truck','compact','motorcycle','van','other') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `license_plate` (`license_plate`),
  CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicles`
--

LOCK TABLES `vehicles` WRITE;
/*!40000 ALTER TABLE `vehicles` DISABLE KEYS */;
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'carwash_db'
--

--
-- Final view structure for view `active_bookings_view`
--

/*!50001 DROP VIEW IF EXISTS `active_bookings_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `active_bookings_view` AS select `b`.`id` AS `id`,`b`.`booking_date` AS `booking_date`,`b`.`booking_time` AS `booking_time`,`b`.`status` AS `status`,`b`.`total_price` AS `total_price`,`b`.`vehicle_type` AS `vehicle_type`,`b`.`vehicle_plate` AS `vehicle_plate`,`u`.`full_name` AS `customer_name`,`u`.`email` AS `customer_email`,`u`.`phone` AS `customer_phone`,`c`.`name` AS `carwash_name`,`c`.`address` AS `carwash_address`,`s`.`name` AS `service_name`,`s`.`duration` AS `service_duration` from (((`bookings` `b` join `users` `u` on(`b`.`user_id` = `u`.`id`)) join `carwashes` `c` on(`b`.`carwash_id` = `c`.`id`)) join `services` `s` on(`b`.`service_id` = `s`.`id`)) where `b`.`status` in ('pending','confirmed','in_progress') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `carwash_stats_view`
--

/*!50001 DROP VIEW IF EXISTS `carwash_stats_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `carwash_stats_view` AS select `c`.`id` AS `id`,`c`.`name` AS `name`,`c`.`city` AS `city`,`c`.`rating` AS `rating`,`c`.`total_reviews` AS `total_reviews`,count(distinct `b`.`id`) AS `total_bookings`,count(distinct case when `b`.`status` = 'completed' then `b`.`id` end) AS `completed_bookings`,coalesce(sum(case when `b`.`status` = 'completed' then `b`.`total_price` end),0) AS `total_revenue`,count(distinct `s`.`id`) AS `total_services` from ((`carwashes` `c` left join `bookings` `b` on(`c`.`id` = `b`.`carwash_id`)) left join `services` `s` on(`c`.`id` = `s`.`carwash_id`)) group by `c`.`id`,`c`.`name`,`c`.`city`,`c`.`rating`,`c`.`total_reviews` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-06  4:00:01
