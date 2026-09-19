/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.6.23-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: cwa_db
-- ------------------------------------------------------
-- Server version	10.6.23-MariaDB-0ubuntu0.22.04.1

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
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(30) NOT NULL,
  `table_name` varchar(80) DEFAULT NULL,
  `record_id` bigint(20) unsigned DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_table` (`table_name`,`record_id`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:03:29'),(2,1,'LOGOUT','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:05:59'),(3,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:06:01'),(4,1,'CREATE','users',2,NULL,'{\"username\":\"sccadmin\",\"role_id\":4}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:13:11'),(5,1,'LOGOUT','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:13:19'),(6,2,'LOGIN','users',2,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:13:22'),(7,2,'LOGOUT','users',2,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:13:29'),(8,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:13:39'),(9,1,'LOGOUT','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:14:23'),(10,2,'LOGIN','users',2,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:14:31'),(11,2,'UPDATE','users',2,NULL,'{\"password_changed\":true}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:15:35'),(12,2,'CREATE','parishes',1,NULL,'{\"name\":\"Holy Cross Catholic Church\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:58:52'),(13,2,'LOGOUT','users',2,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:01:23'),(14,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:01:28'),(15,1,'CREATE','centers',1,NULL,'{\"name\":\"Main Center\",\"welfare_enabled\":1}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:02:14'),(16,1,'CREATE','centers',2,NULL,'{\"name\":\"ST. GERTRUDE\",\"welfare_enabled\":0}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:03:22'),(17,1,'CREATE','centers',3,NULL,'{\"name\":\"ST. PETERS\",\"welfare_enabled\":0}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:03:34'),(18,1,'CREATE','centers',4,NULL,'{\"name\":\"ST. GREGORY\",\"welfare_enabled\":0}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:03:46'),(19,1,'UPDATE','centers',1,'{\"name\":\"Main Center\",\"welfare_enabled\":1}','{\"name\":\"MAIN CENTER\",\"welfare_enabled\":1}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:04:03'),(20,1,'UPDATE','users',1,NULL,'{\"status\":\"INACTIVE\",\"role_id\":1}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:12:39'),(21,1,'UPDATE','users',1,NULL,'{\"status\":\"LOCKED\",\"role_id\":1}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:15:22'),(22,1,'LOGOUT','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:19:30'),(23,2,'LOGIN','users',2,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:19:41'),(24,2,'LOGOUT','users',2,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:29:10'),(25,2,'LOGIN','users',2,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:29:27'),(26,2,'LOGOUT','users',2,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:29:31'),(27,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:30:55'),(28,1,'CREATE','jumuiyas',1,NULL,'{\"center_id\":1,\"name\":\"ST. MARTIN JUMUIYA\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:36:03'),(29,1,'CREATE','jumuiyas',2,NULL,'{\"center_id\":1,\"name\":\"ST. STEPHEN JUMUIYA\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:36:19'),(30,1,'CREATE','jumuiyas',3,NULL,'{\"center_id\":3,\"name\":\"ST. PETER MOGOON JUMUIYA\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:36:42'),(31,1,'CREATE','jumuiyas',4,NULL,'{\"center_id\":4,\"name\":\"ST. GREGORY JUMUIYA\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:37:05'),(32,1,'CREATE','jumuiyas',5,NULL,'{\"center_id\":2,\"name\":\"ST. GERTRUDE JUMUIYA\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:37:20'),(33,1,'UPDATE','jumuiyas',1,'{\"status\":\"ACTIVE\"}','{\"status\":\"INACTIVE\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:37:51'),(34,1,'UPDATE','jumuiyas',1,'{\"status\":\"INACTIVE\"}','{\"status\":\"ACTIVE\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:37:54'),(35,1,'UPDATE','jumuiyas',1,'{\"status\":\"ACTIVE\"}','{\"status\":\"INACTIVE\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:37:55'),(36,1,'UPDATE','jumuiyas',1,'{\"status\":\"INACTIVE\"}','{\"status\":\"ACTIVE\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:37:56'),(37,1,'UPDATE','jumuiyas',1,'{\"status\":\"ACTIVE\"}','{\"status\":\"INACTIVE\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:37:57'),(38,1,'UPDATE','jumuiyas',1,'{\"status\":\"INACTIVE\"}','{\"status\":\"ACTIVE\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:37:58'),(39,1,'UPDATE','users',2,NULL,'{\"status\":\"ACTIVE\",\"role_id\":1}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:39:13'),(40,1,'UPDATE','users',1,NULL,'{\"status\":\"ACTIVE\",\"role_id\":1}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:39:23'),(41,1,'UPDATE','users',1,NULL,'{\"status\":\"ACTIVE\",\"role_id\":1}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:39:47'),(42,1,'UPDATE','users',2,NULL,'{\"status\":\"INACTIVE\",\"role_id\":1}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:39:57'),(43,1,'UPDATE','users',2,NULL,'{\"status\":\"ACTIVE\",\"role_id\":1}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:40:09'),(44,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 19:37:00'),(45,1,'CREATE','financial_years',2,NULL,'{\"year\":2025,\"start_date\":\"2025-01-01\",\"end_date\":\"2025-12-31\",\"is_current\":0}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 19:43:32'),(46,1,'CREATE','financial_years',3,NULL,'{\"year\":2024,\"start_date\":\"2024-01-01\",\"end_date\":\"2024-12-31\",\"is_current\":0}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 19:43:58'),(47,1,'UPDATE','contribution_schedules',NULL,NULL,'{\"year\":2026,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 19:46:18'),(48,1,'UPDATE','contribution_schedules',NULL,NULL,'{\"year\":2026,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 19:46:26');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `centers`
--

DROP TABLE IF EXISTS `centers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `centers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parish_id` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `welfare_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_center_name_parish` (`parish_id`,`name`),
  KEY `idx_center_parish` (`parish_id`),
  CONSTRAINT `fk_center_parish` FOREIGN KEY (`parish_id`) REFERENCES `parishes` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `centers`
--

LOCK TABLES `centers` WRITE;
/*!40000 ALTER TABLE `centers` DISABLE KEYS */;
INSERT INTO `centers` VALUES (1,1,'MAIN CENTER',NULL,1,'ACTIVE','2026-09-18 18:02:14','2026-09-18 18:04:03'),(2,1,'ST. GERTRUDE',NULL,0,'ACTIVE','2026-09-18 18:03:22','2026-09-18 18:03:22'),(3,1,'ST. PETERS',NULL,0,'ACTIVE','2026-09-18 18:03:34','2026-09-18 18:03:34'),(4,1,'ST. GREGORY',NULL,0,'ACTIVE','2026-09-18 18:03:46','2026-09-18 18:03:46');
/*!40000 ALTER TABLE `centers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contribution_activity`
--

DROP TABLE IF EXISTS `contribution_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contribution_activity` (
  `year` smallint(5) unsigned NOT NULL,
  `contribution_type` varchar(30) NOT NULL,
  `first_payment_at` datetime DEFAULT NULL,
  `last_payment_at` datetime DEFAULT NULL,
  `payment_count` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`year`,`contribution_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contribution_activity`
--

LOCK TABLES `contribution_activity` WRITE;
/*!40000 ALTER TABLE `contribution_activity` DISABLE KEYS */;
/*!40000 ALTER TABLE `contribution_activity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contribution_schedule_locks`
--

DROP TABLE IF EXISTS `contribution_schedule_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contribution_schedule_locks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `year` smallint(5) unsigned NOT NULL,
  `contribution_type` varchar(30) NOT NULL,
  `unlocked_by` int(10) unsigned DEFAULT NULL,
  `unlocked_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lock` (`year`,`contribution_type`),
  KEY `idx_lock_year` (`year`),
  KEY `fk_lock_user` (`unlocked_by`),
  CONSTRAINT `fk_lock_user` FOREIGN KEY (`unlocked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contribution_schedule_locks`
--

LOCK TABLES `contribution_schedule_locks` WRITE;
/*!40000 ALTER TABLE `contribution_schedule_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `contribution_schedule_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contribution_schedules`
--

DROP TABLE IF EXISTS `contribution_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contribution_schedules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `year` smallint(5) unsigned NOT NULL,
  `contribution_type` varchar(30) NOT NULL,
  `component` varchar(30) NOT NULL,
  `month` tinyint(3) unsigned NOT NULL,
  `required_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_schedule` (`year`,`contribution_type`,`component`,`month`),
  KEY `idx_sched_lookup` (`year`,`contribution_type`,`component`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contribution_schedules`
--

LOCK TABLES `contribution_schedules` WRITE;
/*!40000 ALTER TABLE `contribution_schedules` DISABLE KEYS */;
INSERT INTO `contribution_schedules` VALUES (13,2026,'WELFARE','WELFARE',1,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(14,2026,'WELFARE','WELFARE',2,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(15,2026,'WELFARE','WELFARE',3,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(16,2026,'WELFARE','WELFARE',4,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(17,2026,'WELFARE','WELFARE',5,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(18,2026,'WELFARE','WELFARE',6,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(19,2026,'WELFARE','WELFARE',7,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(20,2026,'WELFARE','WELFARE',8,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(21,2026,'WELFARE','WELFARE',9,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(22,2026,'WELFARE','WELFARE',10,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(23,2026,'WELFARE','WELFARE',11,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(24,2026,'WELFARE','WELFARE',12,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(37,2026,'REGISTRATION','REGISTRATION',1,30.00,'2026-09-18 19:46:26','2026-09-18 19:46:26'),(38,2026,'REGISTRATION','REGISTRATION',2,30.00,'2026-09-18 19:46:26','2026-09-18 19:46:26'),(39,2026,'REGISTRATION','REGISTRATION',3,30.00,'2026-09-18 19:46:26','2026-09-18 19:46:26'),(40,2026,'REGISTRATION','REGISTRATION',4,30.00,'2026-09-18 19:46:26','2026-09-18 19:46:26'),(41,2026,'REGISTRATION','REGISTRATION',5,30.00,'2026-09-18 19:46:26','2026-09-18 19:46:26'),(42,2026,'REGISTRATION','REGISTRATION',6,30.00,'2026-09-18 19:46:26','2026-09-18 19:46:26'),(43,2026,'REGISTRATION','REGISTRATION',7,30.00,'2026-09-18 19:46:26','2026-09-18 19:46:26'),(44,2026,'REGISTRATION','REGISTRATION',8,30.00,'2026-09-18 19:46:26','2026-09-18 19:46:26'),(45,2026,'REGISTRATION','REGISTRATION',9,30.00,'2026-09-18 19:46:26','2026-09-18 19:46:26'),(46,2026,'REGISTRATION','REGISTRATION',10,30.00,'2026-09-18 19:46:26','2026-09-18 19:46:26'),(47,2026,'REGISTRATION','REGISTRATION',11,30.00,'2026-09-18 19:46:26','2026-09-18 19:46:26'),(48,2026,'REGISTRATION','REGISTRATION',12,70.00,'2026-09-18 19:46:26','2026-09-18 19:46:26');
/*!40000 ALTER TABLE `contribution_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contribution_types`
--

DROP TABLE IF EXISTS `contribution_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contribution_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `name` varchar(80) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contribution_types`
--

LOCK TABLES `contribution_types` WRITE;
/*!40000 ALTER TABLE `contribution_types` DISABLE KEYS */;
INSERT INTO `contribution_types` VALUES (1,'RENEWAL','Annual Renewal',1,'2026-09-18 16:19:48'),(2,'REGISTRATION','Registration Contribution',1,'2026-09-18 16:19:48'),(3,'CARD','New Member Card',1,'2026-09-18 16:19:48'),(4,'WELFARE','Welfare Contribution',1,'2026-09-18 16:19:48'),(5,'OTHER','Other',1,'2026-09-18 16:19:48');
/*!40000 ALTER TABLE `contribution_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `financial_years`
--

DROP TABLE IF EXISTS `financial_years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `financial_years` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `year` smallint(5) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('OPEN','CLOSED') NOT NULL DEFAULT 'OPEN',
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `year` (`year`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `financial_years`
--

LOCK TABLES `financial_years` WRITE;
/*!40000 ALTER TABLE `financial_years` DISABLE KEYS */;
INSERT INTO `financial_years` VALUES (1,2026,'2026-01-01','2026-12-31','OPEN',1,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(2,2025,'2025-01-01','2025-12-31','OPEN',0,'2026-09-18 19:43:32','2026-09-18 19:43:32'),(3,2024,'2024-01-01','2024-12-31','OPEN',0,'2026-09-18 19:43:58','2026-09-18 19:43:58');
/*!40000 ALTER TABLE `financial_years` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jumuiyas`
--

DROP TABLE IF EXISTS `jumuiyas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jumuiyas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `center_id` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_jumuiya_name_center` (`center_id`,`name`),
  KEY `idx_jumuiya_center` (`center_id`),
  CONSTRAINT `fk_jumuiya_center` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jumuiyas`
--

LOCK TABLES `jumuiyas` WRITE;
/*!40000 ALTER TABLE `jumuiyas` DISABLE KEYS */;
INSERT INTO `jumuiyas` VALUES (1,1,'ST. MARTIN JUMUIYA',NULL,'ACTIVE','2026-09-18 18:36:03','2026-09-18 18:37:58'),(2,1,'ST. STEPHEN JUMUIYA',NULL,'ACTIVE','2026-09-18 18:36:19','2026-09-18 18:36:19'),(3,3,'ST. PETER MOGOON JUMUIYA',NULL,'ACTIVE','2026-09-18 18:36:42','2026-09-18 18:36:42'),(4,4,'ST. GREGORY JUMUIYA',NULL,'ACTIVE','2026-09-18 18:37:05','2026-09-18 18:37:05'),(5,2,'ST. GERTRUDE JUMUIYA',NULL,'ACTIVE','2026-09-18 18:37:20','2026-09-18 18:37:20');
/*!40000 ALTER TABLE `jumuiyas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parishes`
--

DROP TABLE IF EXISTS `parishes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `parishes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `diocese` varchar(150) DEFAULT NULL,
  `physical_address` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `single_guard` tinyint(4) GENERATED ALWAYS AS (1) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_single_parish` (`single_guard`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parishes`
--

LOCK TABLES `parishes` WRITE;
/*!40000 ALTER TABLE `parishes` DISABLE KEYS */;
INSERT INTO `parishes` VALUES (1,'Holy Cross Catholic Church','Catholic Diocese of Nakuru','Shabab',NULL,NULL,'2026-09-18 17:58:52','2026-09-18 17:58:52',1);
/*!40000 ALTER TABLE `parishes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'SUPER_ADMIN','Full system access','2026-09-18 16:19:48'),(2,'PARISH_ADMIN','Parish-level management','2026-09-18 16:19:48'),(3,'CENTER_ADMIN','Center-level management','2026-09-18 16:19:48'),(4,'JUMUIYA_ADMIN','Jumuiya-level data entry & reports','2026-09-18 16:19:48'),(5,'VIEWER','Read-only reports','2026-09-18 16:19:48');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(80) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'renewal_amount','100','Annual renewal fee','2026-09-18 16:19:48'),(2,'registration_annual','400','Annual registration component (excludes renewal & card)','2026-09-18 16:19:48'),(3,'card_amount','50','New member card fee','2026-09-18 16:19:48'),(4,'welfare_annual','600','Annual welfare total','2026-09-18 16:19:48'),(5,'welfare_monthly','50','Monthly welfare amount','2026-09-18 16:19:48'),(6,'member_code_prefix','CWA','Prefix for member codes','2026-09-18 16:19:48'),(7,'member_code_padding','6','Digits for member code','2026-09-18 16:19:48'),(8,'receipt_prefix','CWA','Receipt number prefix','2026-09-18 16:19:48'),(9,'system_name','Catholic Women Association','System display name','2026-09-18 16:19:48');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(60) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  `center_id` int(10) unsigned DEFAULT NULL,
  `jumuiya_id` int(10) unsigned DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE','LOCKED') NOT NULL DEFAULT 'ACTIVE',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_users_role` (`role_id`),
  KEY `idx_users_center` (`center_id`),
  KEY `idx_users_jumuiya` (`jumuiya_id`),
  CONSTRAINT `fk_users_center` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_users_jumuiya` FOREIGN KEY (`jumuiya_id`) REFERENCES `jumuiyas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'superadmin','System Administrator',NULL,'superadmin','$2y$10$Vo8gB/cRERUb9MqMtKQsOue3ovv3mJYc5.JCbSiQcwXk9IWQC05WW',1,NULL,NULL,'ACTIVE','2026-09-18 19:37:00','2026-09-18 16:38:22','2026-09-18 19:37:00'),(2,'sccadmin','System Administrator',NULL,'superadmin','$2y$10$2Kndl75dZ3Q6uVohZ..3NudShJvgL1JUqwzWbo61kW5LBRrFQk6zC',1,NULL,NULL,'ACTIVE','2026-09-18 18:29:27','2026-09-18 17:13:11','2026-09-18 18:40:09');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-18 19:49:56
