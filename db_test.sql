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
) ENGINE=InnoDB AUTO_INCREMENT=168 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:03:29'),(2,1,'LOGOUT','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:05:59'),(3,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:06:01'),(4,1,'CREATE','users',2,NULL,'{\"username\":\"sccadmin\",\"role_id\":4}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:13:11'),(5,1,'LOGOUT','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:13:19'),(6,2,'LOGIN','users',2,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:13:22'),(7,2,'LOGOUT','users',2,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:13:29'),(8,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:13:39'),(9,1,'LOGOUT','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:14:23'),(10,2,'LOGIN','users',2,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:14:31'),(11,2,'UPDATE','users',2,NULL,'{\"password_changed\":true}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:15:35'),(12,2,'CREATE','parishes',1,NULL,'{\"name\":\"Holy Cross Catholic Church\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 17:58:52'),(13,2,'LOGOUT','users',2,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:01:23'),(14,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:01:28'),(15,1,'CREATE','centers',1,NULL,'{\"name\":\"Main Center\",\"welfare_enabled\":1}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:02:14'),(16,1,'CREATE','centers',2,NULL,'{\"name\":\"ST. GERTRUDE\",\"welfare_enabled\":0}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:03:22'),(17,1,'CREATE','centers',3,NULL,'{\"name\":\"ST. PETERS\",\"welfare_enabled\":0}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:03:34'),(18,1,'CREATE','centers',4,NULL,'{\"name\":\"ST. GREGORY\",\"welfare_enabled\":0}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:03:46'),(19,1,'UPDATE','centers',1,'{\"name\":\"Main Center\",\"welfare_enabled\":1}','{\"name\":\"MAIN CENTER\",\"welfare_enabled\":1}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:04:03'),(20,1,'UPDATE','users',1,NULL,'{\"status\":\"INACTIVE\",\"role_id\":1}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:12:39'),(21,1,'UPDATE','users',1,NULL,'{\"status\":\"LOCKED\",\"role_id\":1}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:15:22'),(22,1,'LOGOUT','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:19:30'),(23,2,'LOGIN','users',2,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:19:41'),(24,2,'LOGOUT','users',2,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:29:10'),(25,2,'LOGIN','users',2,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:29:27'),(26,2,'LOGOUT','users',2,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:29:31'),(27,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:30:55'),(28,1,'CREATE','jumuiyas',1,NULL,'{\"center_id\":1,\"name\":\"ST. MARTIN JUMUIYA\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:36:03'),(29,1,'CREATE','jumuiyas',2,NULL,'{\"center_id\":1,\"name\":\"ST. STEPHEN JUMUIYA\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:36:19'),(30,1,'CREATE','jumuiyas',3,NULL,'{\"center_id\":3,\"name\":\"ST. PETER MOGOON JUMUIYA\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:36:42'),(31,1,'CREATE','jumuiyas',4,NULL,'{\"center_id\":4,\"name\":\"ST. GREGORY JUMUIYA\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:37:05'),(32,1,'CREATE','jumuiyas',5,NULL,'{\"center_id\":2,\"name\":\"ST. GERTRUDE JUMUIYA\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:37:20'),(33,1,'UPDATE','jumuiyas',1,'{\"status\":\"ACTIVE\"}','{\"status\":\"INACTIVE\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:37:51'),(34,1,'UPDATE','jumuiyas',1,'{\"status\":\"INACTIVE\"}','{\"status\":\"ACTIVE\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:37:54'),(35,1,'UPDATE','jumuiyas',1,'{\"status\":\"ACTIVE\"}','{\"status\":\"INACTIVE\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:37:55'),(36,1,'UPDATE','jumuiyas',1,'{\"status\":\"INACTIVE\"}','{\"status\":\"ACTIVE\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:37:56'),(37,1,'UPDATE','jumuiyas',1,'{\"status\":\"ACTIVE\"}','{\"status\":\"INACTIVE\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:37:57'),(38,1,'UPDATE','jumuiyas',1,'{\"status\":\"INACTIVE\"}','{\"status\":\"ACTIVE\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:37:58'),(39,1,'UPDATE','users',2,NULL,'{\"status\":\"ACTIVE\",\"role_id\":1}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:39:13'),(40,1,'UPDATE','users',1,NULL,'{\"status\":\"ACTIVE\",\"role_id\":1}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:39:23'),(41,1,'UPDATE','users',1,NULL,'{\"status\":\"ACTIVE\",\"role_id\":1}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:39:47'),(42,1,'UPDATE','users',2,NULL,'{\"status\":\"INACTIVE\",\"role_id\":1}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:39:57'),(43,1,'UPDATE','users',2,NULL,'{\"status\":\"ACTIVE\",\"role_id\":1}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 18:40:09'),(44,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 19:37:00'),(45,1,'CREATE','financial_years',2,NULL,'{\"year\":2025,\"start_date\":\"2025-01-01\",\"end_date\":\"2025-12-31\",\"is_current\":0}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 19:43:32'),(46,1,'CREATE','financial_years',3,NULL,'{\"year\":2024,\"start_date\":\"2024-01-01\",\"end_date\":\"2024-12-31\",\"is_current\":0}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 19:43:58'),(47,1,'UPDATE','contribution_schedules',NULL,NULL,'{\"year\":2026,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 19:46:18'),(48,1,'UPDATE','contribution_schedules',NULL,NULL,'{\"year\":2026,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 19:46:26'),(49,1,'UPDATE','contribution_schedules',NULL,NULL,'{\"year\":2024,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 19:51:26'),(50,1,'UPDATE','contribution_schedules',NULL,NULL,'{\"year\":2024,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 19:51:35'),(51,1,'UPDATE','contribution_schedules',NULL,NULL,'{\"year\":2024,\"type\":\"WELFARE\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 19:53:09'),(52,1,'UPDATE','contribution_schedules',NULL,NULL,'{\"year\":2024,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 19:53:36'),(53,1,'UPDATE','contribution_schedules',NULL,NULL,'{\"year\":2024,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 19:54:10'),(54,1,'UPDATE','contribution_schedules',NULL,NULL,'{\"year\":2024,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 19:54:11'),(55,1,'UPDATE','users',2,NULL,'{\"status\":\"ACTIVE\",\"role_id\":2}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 19:57:42'),(56,1,'LOGOUT','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 20:01:43'),(57,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 20:01:55'),(58,1,'UPDATE','users',2,NULL,'{\"status\":\"ACTIVE\",\"role_id\":2}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 20:02:38'),(59,1,'CREATE','users',3,NULL,'{\"username\":\"parishadmin\",\"role_id\":2}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 20:03:46'),(60,1,'LOGOUT','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 20:03:58'),(61,3,'LOGIN','users',3,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 20:04:00'),(62,3,'LOGOUT','users',3,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 20:04:47'),(63,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 20:04:57'),(64,1,'UPDATE','financial_years',2,NULL,'{\"is_current\":1}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 20:17:10'),(65,1,'UPDATE','financial_years',3,NULL,'{\"is_current\":1}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 20:17:13'),(66,1,'UPDATE','financial_years',1,NULL,'{\"is_current\":1}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 20:17:15'),(67,1,'UPDATE','financial_years',1,'{\"status\":\"OPEN\"}','{\"status\":\"CLOSED\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 20:17:17'),(68,1,'UPDATE','financial_years',1,'{\"status\":\"CLOSED\"}','{\"status\":\"OPEN\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 20:17:22'),(69,1,'UPDATE','financial_years',4,NULL,'{\"rolled_from\":2026,\"rolled_to\":2027,\"schedules_copied\":24}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 20:17:33'),(70,1,'UPDATE','financial_years',1,'{\"status\":\"CLOSED\"}','{\"status\":\"OPEN\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 20:17:48'),(71,1,'UPDATE','financial_years',1,NULL,'{\"is_current\":1}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-18 20:17:54'),(72,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 06:18:53'),(73,1,'CREATE','members',1,NULL,'{\"member_code\":\"CWA-000001\",\"full_name\":\"Mary Wanjiku\",\"join_date\":\"2026-09-19\",\"year\":2026}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 07:03:39'),(74,1,'UPDATE','members',1,'{\"status\":\"ACTIVE\"}','{\"status\":\"INACTIVE\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 07:05:02'),(75,1,'UPDATE','members',1,'{\"status\":\"INACTIVE\"}','{\"status\":\"ACTIVE\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 07:05:12'),(76,1,'TRANSFER','members',1,'{\"jumuiya_id\":1}','{\"jumuiya_id\":2,\"effective_date\":\"2026-09-19\",\"reason\":\"Relocate\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 07:05:37'),(77,1,'CREATE','members',2,NULL,'{\"member_code\":\"CWA-000002\",\"full_name\":\"Jane Doe\",\"join_date\":\"2026-01-01\",\"year\":2026}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 07:12:44'),(78,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 08:20:25'),(79,1,'UPDATE','members',2,'{\"full_name\":\"Jane Doe\",\"phone\":\"0700176071\"}','{\"full_name\":\"Jane Doe\",\"phone\":\"051292929\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 09:02:22'),(80,1,'TRANSFER','members',2,'{\"jumuiya_id\":5}','{\"jumuiya_id\":3,\"effective_date\":\"2026-09-19\",\"reason\":\"relocate\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 09:04:23'),(81,1,'UPDATE','members',1,'{\"status\":\"ACTIVE\"}','{\"status\":\"INACTIVE\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 09:04:58'),(82,1,'LOGOUT','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 09:12:41'),(83,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 09:13:29'),(84,1,'CREATE','users',4,NULL,'{\"username\":\"viewer\",\"role_id\":5}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 09:14:18'),(85,1,'UPDATE','users',2,NULL,'{\"status\":\"ACTIVE\",\"role_id\":1}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 09:14:44'),(86,1,'UPDATE','users',4,NULL,'{\"status\":\"ACTIVE\",\"role_id\":5}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 09:35:14'),(87,1,'UPDATE','users',4,NULL,'{\"status\":\"ACTIVE\",\"role_id\":5}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 09:38:30'),(88,1,'UPDATE','users',4,NULL,'{\"status\":\"ACTIVE\",\"role_id\":5}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 09:40:12'),(89,1,'UPDATE','users',4,NULL,'{\"status\":\"ACTIVE\",\"role_id\":5}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 09:40:44'),(90,1,'LOGOUT','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 09:41:04'),(91,4,'LOGIN','users',4,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 09:41:11'),(92,4,'LOGOUT','users',4,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 09:41:26'),(93,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 09:49:28'),(94,1,'UPDATE','members',1,'{\"status\":\"INACTIVE\"}','{\"status\":\"ACTIVE\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 10:37:57'),(95,1,'PAYMENT','payments',1,NULL,'{\"receipt_no\":\"CWA-2026-000001\",\"member_id\":1,\"year\":2026,\"amount\":200,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 10:39:02'),(96,1,'PAYMENT','payments',2,NULL,'{\"receipt_no\":\"CWA-2026-000002\",\"member_id\":1,\"year\":2026,\"amount\":1000,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 10:52:37'),(97,1,'PAYMENT','payments',3,NULL,'{\"receipt_no\":\"CWA-2026-000003\",\"member_id\":1,\"year\":2026,\"amount\":70,\"type\":\"WELFARE\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 10:54:05'),(98,1,'PAYMENT','payments',4,NULL,'{\"receipt_no\":\"CWA-2026-000004\",\"member_id\":1,\"year\":2026,\"amount\":600,\"type\":\"WELFARE\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 10:55:22'),(99,1,'PAYMENT','payments',5,NULL,'{\"receipt_no\":\"CWA-2026-000005\",\"member_id\":1,\"year\":2026,\"amount\":30,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 10:59:05'),(100,1,'REVERSAL','payments',5,'{\"status\":\"ACTIVE\"}','{\"status\":\"VOIDED\",\"reason\":\"wrong entry\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 11:03:29'),(101,1,'REVERSAL','payments',2,'{\"status\":\"ACTIVE\"}','{\"status\":\"VOIDED\",\"reason\":\"testing\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 11:05:20'),(102,1,'UPDATE','contribution_schedule_locks',NULL,NULL,'{\"year\":2026,\"type\":\"REGISTRATION\",\"reason\":\"December corrected to 80\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 11:12:04'),(103,1,'UPDATE','contribution_schedules',NULL,NULL,'{\"year\":2026,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 11:12:14'),(104,1,'LOGOUT','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 11:13:19'),(105,4,'LOGIN','users',4,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 11:13:30'),(106,4,'LOGOUT','users',4,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 11:15:22'),(107,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 11:15:25'),(108,1,'PAYMENT','payments',6,NULL,'{\"receipt_no\":\"CWA-2026-000006\",\"member_id\":2,\"year\":2026,\"amount\":1000,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 11:16:18'),(109,1,'PAYMENT','payments',7,NULL,'{\"receipt_no\":\"CWA-2026-000007\",\"member_id\":2,\"year\":2026,\"amount\":30,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 11:17:28'),(110,1,'CREATE','members',3,NULL,'{\"member_code\":\"CWA-000003\",\"full_name\":\"Grace K\",\"join_date\":\"2026-09-19\",\"year\":2026}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 12:07:27'),(111,1,'PAYMENT','payments',8,NULL,'{\"receipt_no\":\"CWA-2026-000008\",\"member_id\":3,\"year\":2026,\"amount\":200,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 12:28:50'),(112,1,'PAYMENT','payments',9,NULL,'{\"receipt_no\":\"CWA-2026-000009\",\"member_id\":3,\"year\":2026,\"amount\":30,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 12:40:32'),(113,1,'PAYMENT','payments',10,NULL,'{\"receipt_no\":\"CWA-2026-000010\",\"member_id\":3,\"year\":2026,\"amount\":100,\"type\":\"WELFARE\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 12:46:57'),(114,1,'CREATE','members',4,NULL,'{\"member_code\":\"CWA-000004\",\"full_name\":\"Wangari W\",\"join_date\":\"2026-04-01\",\"year\":2026}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 12:58:51'),(115,1,'ADJUSTMENT','payments',10,'{\"reason\":\"reallocation\"}','{\"old_advance\":0,\"new_advance\":0,\"new_sum\":100,\"unallocated\":0}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 13:03:10'),(116,1,'ADJUSTMENT','payments',10,'{\"reason\":\"reallocation\"}','{\"old_advance\":0,\"new_advance\":0,\"new_sum\":100,\"unallocated\":0}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 13:05:01'),(117,1,'REVERSAL','payments',9,'{\"status\":\"ACTIVE\"}','{\"status\":\"VOIDED\",\"reason\":\"a\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-19 13:17:05'),(118,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-20 09:04:44'),(119,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-21 07:37:29'),(120,1,'CREATE','members',5,NULL,'{\"member_code\":\"CWA-000005\",\"full_name\":\"Jane Doe\",\"join_date\":\"2026-06-01\",\"year\":2026}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-21 07:38:05'),(121,1,'PAYMENT','payments',11,NULL,'{\"receipt_no\":\"CWA-2026-000011\",\"member_id\":5,\"year\":2026,\"amount\":200,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-21 07:39:03'),(122,1,'CREATE','members',6,NULL,'{\"member_code\":\"CWA-000006\",\"full_name\":\"Waka\",\"join_date\":\"2026-09-21\",\"year\":2026}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-21 07:40:44'),(123,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-21 09:02:05'),(124,1,'PAYMENT','payments',12,NULL,'{\"receipt_no\":\"CWA-2026-000012\",\"member_id\":6,\"year\":2026,\"amount\":200,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-21 09:04:19'),(125,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-21 11:33:27'),(126,1,'CREATE','members',7,NULL,'{\"member_code\":\"CWA-000007\",\"full_name\":\"ANother Tes\",\"join_date\":\"2026-07-01\",\"year\":2026}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-21 11:34:24'),(127,1,'PAYMENT','payments',13,NULL,'{\"receipt_no\":\"CWA-2026-000013\",\"member_id\":7,\"year\":2026,\"amount\":200,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-21 11:35:49'),(128,1,'ADJUSTMENT','payments',13,'{\"reason\":\"reallocation\"}','{\"old_advance\":0,\"new_advance\":20,\"new_sum\":180,\"unallocated\":20}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-21 12:03:22'),(129,1,'ADJUSTMENT','payments',13,'{\"reason\":\"reallocation\"}','{\"old_advance\":20,\"new_advance\":0,\"new_sum\":200,\"unallocated\":0}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-21 12:06:38'),(130,1,'REVERSAL','payments',13,'{\"status\":\"ACTIVE\"}','{\"status\":\"VOIDED\",\"reason\":\"calc\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-21 12:07:32'),(131,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-21 12:39:33'),(132,1,'CREATE','members',1,NULL,'{\"member_code\":\"CWA-000008\",\"full_name\":\"Mary Wanjiku\",\"join_date\":\"2026-07-01\",\"year\":2026}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-21 12:40:26'),(133,1,'PAYMENT','payments',1,NULL,'{\"receipt_no\":\"CWA-2026-000001\",\"member_id\":1,\"year\":2026,\"amount\":200,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-21 12:41:08'),(134,1,'UPDATE','contribution_schedules',NULL,NULL,'{\"year\":2026,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-21 12:42:22'),(135,1,'PAYMENT','payments',2,NULL,'{\"receipt_no\":\"CWA-2026-000002\",\"member_id\":1,\"year\":2026,\"amount\":300,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-21 12:46:25'),(136,1,'ADJUSTMENT','payments',2,'{\"reason\":\"reallocation\"}','{\"old_advance\":0,\"new_advance\":10,\"new_sum\":290,\"unallocated\":10}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-21 12:48:08'),(137,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-21 14:23:16'),(138,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-22 01:26:55'),(139,1,'CREATE','members',2,NULL,'{\"member_code\":\"CWA-000009\",\"full_name\":\"Jane Doe\",\"join_date\":\"2026-05-01\",\"year\":2026}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-22 01:27:27'),(140,1,'PAYMENT','payments',3,NULL,'{\"receipt_no\":\"CWA-2026-000003\",\"member_id\":2,\"year\":2026,\"amount\":200,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-22 01:27:49'),(141,1,'ADJUSTMENT','payments',3,'{\"reason\":\"reallocation\"}','{\"old_advance\":0,\"new_advance\":10,\"new_sum\":190,\"unallocated\":10}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-22 01:35:19'),(142,1,'REVERSAL','payments',2,'{\"status\":\"ACTIVE\"}','{\"status\":\"VOIDED\",\"reason\":\"j\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-22 01:37:01'),(143,1,'REVERSAL','payments',3,'{\"status\":\"ACTIVE\"}','{\"status\":\"VOIDED\",\"reason\":\"j\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-22 01:37:13'),(144,1,'REVERSAL','payments',1,'{\"status\":\"ACTIVE\"}','{\"status\":\"VOIDED\",\"reason\":\"n\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-22 01:37:27'),(145,1,'PAYMENT','payments',4,NULL,'{\"receipt_no\":\"CWA-2026-000004\",\"member_id\":2,\"year\":2026,\"amount\":200,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-22 01:38:04'),(146,1,'ADJUSTMENT','payments',4,'{\"reason\":\"reallocation\"}','{\"old_advance\":0,\"new_advance\":0,\"new_sum\":200,\"unallocated\":0}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-22 01:38:55'),(147,1,'CREATE','members',1,NULL,'{\"member_code\":\"CWA-000010\",\"full_name\":\"Mary Wanjiku\",\"join_date\":\"2026-09-22\",\"year\":2026}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-22 02:06:43'),(148,1,'PAYMENT','payments',1,NULL,'{\"receipt_no\":\"CWA-2026-000001\",\"member_id\":1,\"year\":2026,\"amount\":200,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-22 02:07:16'),(149,1,'ADJUSTMENT','payments',1,'{\"reason\":\"reallocation\"}','{\"old_advance\":0,\"new_advance\":0,\"new_sum\":200,\"unallocated\":0}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-22 02:22:50'),(150,1,'REVERSAL','payments',1,'{\"status\":\"ACTIVE\"}','{\"status\":\"VOIDED\",\"reason\":\"k\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-22 02:52:33'),(151,1,'PAYMENT','payments',2,NULL,'{\"receipt_no\":\"CWA-2026-000002\",\"member_id\":1,\"year\":2026,\"amount\":200,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-22 02:53:08'),(152,1,'ADJUSTMENT','payments',2,'{\"reason\":\"reallocation\"}','{\"old_advance\":0,\"new_advance\":0,\"new_sum\":200,\"unallocated\":0}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-22 02:54:29'),(153,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-22 06:27:27'),(154,1,'PAYMENT','payments',3,NULL,'{\"receipt_no\":\"CWA-2026-000003\",\"member_id\":1,\"year\":2026,\"amount\":100,\"type\":\"WELFARE\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-22 08:18:03'),(155,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-22 12:40:46'),(156,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-22 15:39:15'),(157,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-23 01:24:41'),(158,1,'CREATE','members',2,NULL,'{\"member_code\":\"CWA-000011\",\"full_name\":\"Jane Doe\",\"join_date\":\"2026-09-23\",\"year\":2026}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-23 01:30:07'),(159,1,'PAYMENT','payments',4,NULL,'{\"receipt_no\":\"CWA-2026-000004\",\"member_id\":1,\"year\":2026,\"amount\":200,\"type\":\"REGISTRATION\"}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-23 01:31:41'),(160,1,'LOGOUT','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-23 02:36:03'),(161,4,'LOGIN','users',4,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-23 02:36:35'),(162,4,'LOGOUT','users',4,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-23 02:43:13'),(163,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-23 02:43:15'),(164,1,'EXPORT','workbook',NULL,NULL,'{\"year\":2026,\"center\":null,\"sheets\":[\"reg_matrix\",\"wel_matrix\"]}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-23 02:43:28'),(165,1,'EXPORT','workbook',NULL,NULL,'{\"year\":2026,\"center\":null,\"sheets\":[\"reg_matrix\"]}','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-23 02:43:55'),(166,1,'LOGOUT','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-23 03:00:34'),(167,1,'LOGIN','users',1,NULL,NULL,'::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','2026-09-23 03:00:36');
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
INSERT INTO `contribution_activity` VALUES (2026,'REGISTRATION','2026-09-18 19:55:26','2026-09-23 01:31:41',18),(2026,'WELFARE','2026-09-19 10:54:05','2026-09-22 08:18:03',4),(2027,'REGISTRATION','2026-09-18 19:50:06',NULL,1);
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contribution_schedule_locks`
--

LOCK TABLES `contribution_schedule_locks` WRITE;
/*!40000 ALTER TABLE `contribution_schedule_locks` DISABLE KEYS */;
INSERT INTO `contribution_schedule_locks` VALUES (1,2026,'REGISTRATION',1,'2026-09-19 11:12:04','December corrected to 80');
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
) ENGINE=InnoDB AUTO_INCREMENT=169 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contribution_schedules`
--

LOCK TABLES `contribution_schedules` WRITE;
/*!40000 ALTER TABLE `contribution_schedules` DISABLE KEYS */;
INSERT INTO `contribution_schedules` VALUES (13,2026,'WELFARE','WELFARE',1,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(14,2026,'WELFARE','WELFARE',2,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(15,2026,'WELFARE','WELFARE',3,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(16,2026,'WELFARE','WELFARE',4,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(17,2026,'WELFARE','WELFARE',5,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(18,2026,'WELFARE','WELFARE',6,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(19,2026,'WELFARE','WELFARE',7,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(20,2026,'WELFARE','WELFARE',8,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(21,2026,'WELFARE','WELFARE',9,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(22,2026,'WELFARE','WELFARE',10,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(23,2026,'WELFARE','WELFARE',11,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(24,2026,'WELFARE','WELFARE',12,50.00,'2026-09-18 16:19:48','2026-09-18 16:19:48'),(73,2024,'WELFARE','WELFARE',1,50.00,'2026-09-18 19:53:09','2026-09-18 19:53:09'),(74,2024,'WELFARE','WELFARE',2,50.00,'2026-09-18 19:53:09','2026-09-18 19:53:09'),(75,2024,'WELFARE','WELFARE',3,50.00,'2026-09-18 19:53:09','2026-09-18 19:53:09'),(76,2024,'WELFARE','WELFARE',4,50.00,'2026-09-18 19:53:09','2026-09-18 19:53:09'),(77,2024,'WELFARE','WELFARE',5,50.00,'2026-09-18 19:53:09','2026-09-18 19:53:09'),(78,2024,'WELFARE','WELFARE',6,50.00,'2026-09-18 19:53:09','2026-09-18 19:53:09'),(79,2024,'WELFARE','WELFARE',7,50.00,'2026-09-18 19:53:09','2026-09-18 19:53:09'),(80,2024,'WELFARE','WELFARE',8,50.00,'2026-09-18 19:53:09','2026-09-18 19:53:09'),(81,2024,'WELFARE','WELFARE',9,50.00,'2026-09-18 19:53:09','2026-09-18 19:53:09'),(82,2024,'WELFARE','WELFARE',10,50.00,'2026-09-18 19:53:09','2026-09-18 19:53:09'),(83,2024,'WELFARE','WELFARE',11,50.00,'2026-09-18 19:53:09','2026-09-18 19:53:09'),(84,2024,'WELFARE','WELFARE',12,50.00,'2026-09-18 19:53:09','2026-09-18 19:53:09'),(109,2024,'REGISTRATION','REGISTRATION',1,30.00,'2026-09-18 19:54:11','2026-09-18 19:54:11'),(110,2024,'REGISTRATION','REGISTRATION',2,30.00,'2026-09-18 19:54:11','2026-09-18 19:54:11'),(111,2024,'REGISTRATION','REGISTRATION',3,30.00,'2026-09-18 19:54:11','2026-09-18 19:54:11'),(112,2024,'REGISTRATION','REGISTRATION',4,30.00,'2026-09-18 19:54:11','2026-09-18 19:54:11'),(113,2024,'REGISTRATION','REGISTRATION',5,30.00,'2026-09-18 19:54:11','2026-09-18 19:54:11'),(114,2024,'REGISTRATION','REGISTRATION',6,30.00,'2026-09-18 19:54:11','2026-09-18 19:54:11'),(115,2024,'REGISTRATION','REGISTRATION',7,30.00,'2026-09-18 19:54:11','2026-09-18 19:54:11'),(116,2024,'REGISTRATION','REGISTRATION',8,30.00,'2026-09-18 19:54:11','2026-09-18 19:54:11'),(117,2024,'REGISTRATION','REGISTRATION',9,30.00,'2026-09-18 19:54:11','2026-09-18 19:54:11'),(118,2024,'REGISTRATION','REGISTRATION',10,30.00,'2026-09-18 19:54:11','2026-09-18 19:54:11'),(119,2024,'REGISTRATION','REGISTRATION',11,70.00,'2026-09-18 19:54:11','2026-09-18 19:54:11'),(120,2024,'REGISTRATION','REGISTRATION',12,30.00,'2026-09-18 19:54:11','2026-09-18 19:54:11'),(121,2027,'WELFARE','WELFARE',1,50.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(122,2027,'WELFARE','WELFARE',2,50.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(123,2027,'WELFARE','WELFARE',3,50.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(124,2027,'WELFARE','WELFARE',4,50.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(125,2027,'WELFARE','WELFARE',5,50.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(126,2027,'WELFARE','WELFARE',6,50.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(127,2027,'WELFARE','WELFARE',7,50.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(128,2027,'WELFARE','WELFARE',8,50.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(129,2027,'WELFARE','WELFARE',9,50.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(130,2027,'WELFARE','WELFARE',10,50.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(131,2027,'WELFARE','WELFARE',11,50.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(132,2027,'WELFARE','WELFARE',12,50.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(133,2027,'REGISTRATION','REGISTRATION',1,30.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(134,2027,'REGISTRATION','REGISTRATION',2,30.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(135,2027,'REGISTRATION','REGISTRATION',3,30.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(136,2027,'REGISTRATION','REGISTRATION',4,30.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(137,2027,'REGISTRATION','REGISTRATION',5,30.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(138,2027,'REGISTRATION','REGISTRATION',6,30.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(139,2027,'REGISTRATION','REGISTRATION',7,30.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(140,2027,'REGISTRATION','REGISTRATION',8,30.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(141,2027,'REGISTRATION','REGISTRATION',9,30.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(142,2027,'REGISTRATION','REGISTRATION',10,30.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(143,2027,'REGISTRATION','REGISTRATION',11,30.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(144,2027,'REGISTRATION','REGISTRATION',12,70.00,'2026-09-18 20:17:33','2026-09-18 20:17:33'),(157,2026,'REGISTRATION','REGISTRATION',1,30.00,'2026-09-21 12:42:22','2026-09-21 12:42:22'),(158,2026,'REGISTRATION','REGISTRATION',2,30.00,'2026-09-21 12:42:22','2026-09-21 12:42:22'),(159,2026,'REGISTRATION','REGISTRATION',3,30.00,'2026-09-21 12:42:22','2026-09-21 12:42:22'),(160,2026,'REGISTRATION','REGISTRATION',4,30.00,'2026-09-21 12:42:22','2026-09-21 12:42:22'),(161,2026,'REGISTRATION','REGISTRATION',5,30.00,'2026-09-21 12:42:22','2026-09-21 12:42:22'),(162,2026,'REGISTRATION','REGISTRATION',6,30.00,'2026-09-21 12:42:22','2026-09-21 12:42:22'),(163,2026,'REGISTRATION','REGISTRATION',7,30.00,'2026-09-21 12:42:22','2026-09-21 12:42:22'),(164,2026,'REGISTRATION','REGISTRATION',8,30.00,'2026-09-21 12:42:22','2026-09-21 12:42:22'),(165,2026,'REGISTRATION','REGISTRATION',9,30.00,'2026-09-21 12:42:22','2026-09-21 12:42:22'),(166,2026,'REGISTRATION','REGISTRATION',10,30.00,'2026-09-21 12:42:22','2026-09-21 12:42:22'),(167,2026,'REGISTRATION','REGISTRATION',11,30.00,'2026-09-21 12:42:22','2026-09-21 12:42:22'),(168,2026,'REGISTRATION','REGISTRATION',12,70.00,'2026-09-21 12:42:22','2026-09-21 12:42:22');
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `financial_years`
--

LOCK TABLES `financial_years` WRITE;
/*!40000 ALTER TABLE `financial_years` DISABLE KEYS */;
INSERT INTO `financial_years` VALUES (1,2026,'2026-01-01','2026-12-31','OPEN',1,'2026-09-18 16:19:48','2026-09-18 20:17:54'),(2,2025,'2025-01-01','2025-12-31','OPEN',0,'2026-09-18 19:43:32','2026-09-18 20:17:13'),(3,2024,'2024-01-01','2024-12-31','OPEN',0,'2026-09-18 19:43:58','2026-09-18 20:17:15'),(4,2027,'2027-01-01','2027-12-31','OPEN',0,'2026-09-18 20:17:33','2026-09-18 20:17:54');
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
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(120) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_la_ident` (`identifier`,`created_at`),
  KEY `idx_la_ip` (`ip_address`,`created_at`),
  KEY `idx_la_time` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
INSERT INTO `login_attempts` VALUES (1,'superadmin','::1',1,'2026-09-23 03:00:36');
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member_advances`
--

DROP TABLE IF EXISTS `member_advances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `member_advances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `contribution_type` enum('REGISTRATION','WELFARE','OTHER') NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_adv` (`member_id`,`year`,`contribution_type`),
  CONSTRAINT `fk_adv_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_advances`
--

LOCK TABLES `member_advances` WRITE;
/*!40000 ALTER TABLE `member_advances` DISABLE KEYS */;
INSERT INTO `member_advances` VALUES (1,1,2026,'REGISTRATION',-200.00,'2026-09-22 02:52:33');
/*!40000 ALTER TABLE `member_advances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member_code_seq`
--

DROP TABLE IF EXISTS `member_code_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `member_code_seq` (
  `prefix` varchar(10) NOT NULL,
  `last_value` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`prefix`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_code_seq`
--

LOCK TABLES `member_code_seq` WRITE;
/*!40000 ALTER TABLE `member_code_seq` DISABLE KEYS */;
INSERT INTO `member_code_seq` VALUES ('CWA',11);
/*!40000 ALTER TABLE `member_code_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member_jumuiya_history`
--

DROP TABLE IF EXISTS `member_jumuiya_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `member_jumuiya_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL,
  `old_jumuiya_id` int(10) unsigned DEFAULT NULL,
  `new_jumuiya_id` int(10) unsigned NOT NULL,
  `effective_date` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `changed_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_mjh_member` (`member_id`),
  KEY `idx_mjh_new` (`new_jumuiya_id`),
  KEY `fk_mjh_old` (`old_jumuiya_id`),
  KEY `fk_mjh_user` (`changed_by`),
  CONSTRAINT `fk_mjh_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_mjh_new` FOREIGN KEY (`new_jumuiya_id`) REFERENCES `jumuiyas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_mjh_old` FOREIGN KEY (`old_jumuiya_id`) REFERENCES `jumuiyas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_mjh_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_jumuiya_history`
--

LOCK TABLES `member_jumuiya_history` WRITE;
/*!40000 ALTER TABLE `member_jumuiya_history` DISABLE KEYS */;
INSERT INTO `member_jumuiya_history` VALUES (1,1,NULL,1,'2026-09-22','Initial registration',1,'2026-09-22 02:06:43'),(2,2,NULL,2,'2026-09-23','Initial registration',1,'2026-09-23 01:30:07');
/*!40000 ALTER TABLE `member_jumuiya_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member_registration`
--

DROP TABLE IF EXISTS `member_registration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `member_registration` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `renewal_required` decimal(10,2) NOT NULL DEFAULT 0.00,
  `registration_required` decimal(10,2) NOT NULL DEFAULT 0.00,
  `card_required` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('OPEN','COMPLETED','WAIVED') NOT NULL DEFAULT 'OPEN',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reg` (`member_id`,`year`),
  KEY `idx_reg_year` (`year`),
  CONSTRAINT `fk_reg_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_registration`
--

LOCK TABLES `member_registration` WRITE;
/*!40000 ALTER TABLE `member_registration` DISABLE KEYS */;
INSERT INTO `member_registration` VALUES (1,1,2026,100.00,400.00,50.00,'OPEN','2026-09-22 02:06:43','2026-09-22 02:06:43'),(2,2,2026,100.00,400.00,50.00,'OPEN','2026-09-23 01:30:07','2026-09-23 01:30:07');
/*!40000 ALTER TABLE `member_registration` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member_welfare`
--

DROP TABLE IF EXISTS `member_welfare`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `member_welfare` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `welfare_required` decimal(10,2) NOT NULL DEFAULT 0.00,
  `start_month` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `status` enum('OPEN','COMPLETED','WAIVED','NOT_APPLICABLE') NOT NULL DEFAULT 'OPEN',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wel` (`member_id`,`year`),
  KEY `idx_wel_year` (`year`),
  CONSTRAINT `fk_wel_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_welfare`
--

LOCK TABLES `member_welfare` WRITE;
/*!40000 ALTER TABLE `member_welfare` DISABLE KEYS */;
INSERT INTO `member_welfare` VALUES (1,1,2026,200.00,9,'OPEN','2026-09-22 02:06:43','2026-09-22 02:06:43'),(2,2,2026,200.00,9,'OPEN','2026-09-23 01:30:07','2026-09-23 01:30:07');
/*!40000 ALTER TABLE `member_welfare` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `members`
--

DROP TABLE IF EXISTS `members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_code` varchar(20) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `id_number` varchar(30) DEFAULT NULL,
  `gender` enum('MALE','FEMALE','OTHER') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `join_date` date NOT NULL,
  `join_month` tinyint(3) unsigned NOT NULL,
  `jumuiya_id` int(10) unsigned NOT NULL,
  `status` enum('ACTIVE','INACTIVE','TRANSFERRED','DECEASED','LEFT','SUSPENDED') NOT NULL DEFAULT 'ACTIVE',
  `photo` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `member_code` (`member_code`),
  UNIQUE KEY `uq_member_phone` (`phone`),
  KEY `idx_member_jumuiya` (`jumuiya_id`),
  KEY `idx_member_status` (`status`),
  KEY `idx_member_name` (`full_name`),
  KEY `fk_member_created_by` (`created_by`),
  KEY `fk_member_updated_by` (`updated_by`),
  CONSTRAINT `fk_member_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_member_jumuiya` FOREIGN KEY (`jumuiya_id`) REFERENCES `jumuiyas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_member_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `members`
--

LOCK TABLES `members` WRITE;
/*!40000 ALTER TABLE `members` DISABLE KEYS */;
INSERT INTO `members` VALUES (1,'CWA-000010','Mary Wanjiku','0700176071',NULL,'',NULL,'2026-09-22',9,1,'ACTIVE',NULL,NULL,1,1,'2026-09-22 02:06:43','2026-09-22 02:06:43'),(2,'CWA-000011','Jane Doe','1',NULL,'',NULL,'2026-09-23',9,2,'ACTIVE',NULL,NULL,1,1,'2026-09-23 01:30:07','2026-09-23 01:30:07');
/*!40000 ALTER TABLE `members` ENABLE KEYS */;
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
-- Table structure for table `payment_allocations`
--

DROP TABLE IF EXISTS `payment_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` bigint(20) unsigned NOT NULL,
  `member_id` int(10) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `contribution_type` enum('REGISTRATION','WELFARE','OTHER') NOT NULL,
  `component` varchar(30) NOT NULL,
  `month` tinyint(3) unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `allocation_method` enum('AUTO','MANUAL','ADJUSTMENT','REVERSAL') NOT NULL DEFAULT 'AUTO',
  `notes` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_alloc_payment` (`payment_id`),
  KEY `idx_alloc_member_year` (`member_id`,`year`,`contribution_type`),
  KEY `idx_alloc_component` (`component`),
  KEY `fk_alloc_user` (`created_by`),
  CONSTRAINT `fk_alloc_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_alloc_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_alloc_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_allocations`
--

LOCK TABLES `payment_allocations` WRITE;
/*!40000 ALTER TABLE `payment_allocations` DISABLE KEYS */;
INSERT INTO `payment_allocations` VALUES (1,1,1,2026,'REGISTRATION','RENEWAL',NULL,100.00,'AUTO',NULL,1,'2026-09-22 02:07:16'),(2,1,1,2026,'REGISTRATION','CARD',NULL,50.00,'AUTO',NULL,1,'2026-09-22 02:07:16'),(3,1,1,2026,'REGISTRATION','REGISTRATION',1,30.00,'AUTO',NULL,1,'2026-09-22 02:07:16'),(4,1,1,2026,'REGISTRATION','REGISTRATION',2,20.00,'AUTO',NULL,1,'2026-09-22 02:07:16'),(5,1,1,2026,'REGISTRATION','RENEWAL',NULL,-100.00,'ADJUSTMENT','Correction — reversal of prior line',1,'2026-09-22 02:22:50'),(6,1,1,2026,'REGISTRATION','CARD',NULL,-50.00,'ADJUSTMENT','Correction — reversal of prior line',1,'2026-09-22 02:22:50'),(7,1,1,2026,'REGISTRATION','REGISTRATION',NULL,-30.00,'ADJUSTMENT','Correction — reversal of prior line',1,'2026-09-22 02:22:50'),(8,1,1,2026,'REGISTRATION','REGISTRATION',NULL,-20.00,'ADJUSTMENT','Correction — reversal of prior line',1,'2026-09-22 02:22:50'),(9,1,1,2026,'REGISTRATION','RENEWAL',NULL,100.00,'MANUAL','Correction — new split',1,'2026-09-22 02:22:50'),(10,1,1,2026,'REGISTRATION','CARD',NULL,50.00,'MANUAL','Correction — new split',1,'2026-09-22 02:22:50'),(11,1,1,2026,'REGISTRATION','REGISTRATION',1,40.00,'MANUAL','Correction — new split',1,'2026-09-22 02:22:50'),(12,1,1,2026,'REGISTRATION','REGISTRATION',2,10.00,'MANUAL','Correction — new split',1,'2026-09-22 02:22:50'),(13,1,1,2026,'REGISTRATION','RENEWAL',NULL,-100.00,'REVERSAL','Reversal of allocation #1',1,'2026-09-22 02:52:33'),(14,1,1,2026,'REGISTRATION','CARD',NULL,-50.00,'REVERSAL','Reversal of allocation #2',1,'2026-09-22 02:52:33'),(15,1,1,2026,'REGISTRATION','REGISTRATION',1,-30.00,'REVERSAL','Reversal of allocation #3',1,'2026-09-22 02:52:33'),(16,1,1,2026,'REGISTRATION','REGISTRATION',2,-20.00,'REVERSAL','Reversal of allocation #4',1,'2026-09-22 02:52:33'),(17,1,1,2026,'REGISTRATION','RENEWAL',NULL,100.00,'REVERSAL','Reversal of allocation #5',1,'2026-09-22 02:52:33'),(18,1,1,2026,'REGISTRATION','CARD',NULL,50.00,'REVERSAL','Reversal of allocation #6',1,'2026-09-22 02:52:33'),(19,1,1,2026,'REGISTRATION','REGISTRATION',NULL,30.00,'REVERSAL','Reversal of allocation #7',1,'2026-09-22 02:52:33'),(20,1,1,2026,'REGISTRATION','REGISTRATION',NULL,20.00,'REVERSAL','Reversal of allocation #8',1,'2026-09-22 02:52:33'),(21,1,1,2026,'REGISTRATION','RENEWAL',NULL,-100.00,'REVERSAL','Reversal of allocation #9',1,'2026-09-22 02:52:33'),(22,1,1,2026,'REGISTRATION','CARD',NULL,-50.00,'REVERSAL','Reversal of allocation #10',1,'2026-09-22 02:52:33'),(23,1,1,2026,'REGISTRATION','REGISTRATION',1,-40.00,'REVERSAL','Reversal of allocation #11',1,'2026-09-22 02:52:33'),(24,1,1,2026,'REGISTRATION','REGISTRATION',2,-10.00,'REVERSAL','Reversal of allocation #12',1,'2026-09-22 02:52:33'),(25,2,1,2026,'REGISTRATION','RENEWAL',NULL,100.00,'AUTO',NULL,1,'2026-09-22 02:53:08'),(26,2,1,2026,'REGISTRATION','CARD',NULL,50.00,'AUTO',NULL,1,'2026-09-22 02:53:08'),(27,2,1,2026,'REGISTRATION','REGISTRATION',1,30.00,'AUTO',NULL,1,'2026-09-22 02:53:08'),(28,2,1,2026,'REGISTRATION','REGISTRATION',2,20.00,'AUTO',NULL,1,'2026-09-22 02:53:08'),(29,2,1,2026,'REGISTRATION','RENEWAL',NULL,-100.00,'ADJUSTMENT','Correction — reversal of original',1,'2026-09-22 02:54:28'),(30,2,1,2026,'REGISTRATION','CARD',NULL,-50.00,'ADJUSTMENT','Correction — reversal of original',1,'2026-09-22 02:54:29'),(31,2,1,2026,'REGISTRATION','REGISTRATION',1,-30.00,'ADJUSTMENT','Correction — reversal of original',1,'2026-09-22 02:54:29'),(32,2,1,2026,'REGISTRATION','REGISTRATION',2,-20.00,'ADJUSTMENT','Correction — reversal of original',1,'2026-09-22 02:54:29'),(33,2,1,2026,'REGISTRATION','RENEWAL',NULL,100.00,'MANUAL','Correction — new split',1,'2026-09-22 02:54:29'),(34,2,1,2026,'REGISTRATION','CARD',NULL,50.00,'MANUAL','Correction — new split',1,'2026-09-22 02:54:29'),(35,2,1,2026,'REGISTRATION','REGISTRATION',1,40.00,'MANUAL','Correction — new split',1,'2026-09-22 02:54:29'),(36,2,1,2026,'REGISTRATION','REGISTRATION',2,10.00,'MANUAL','Correction — new split',1,'2026-09-22 02:54:29'),(37,3,1,2026,'WELFARE','WELFARE',9,50.00,'AUTO',NULL,1,'2026-09-22 08:18:03'),(38,3,1,2026,'WELFARE','WELFARE',10,50.00,'AUTO',NULL,1,'2026-09-22 08:18:03'),(39,4,1,2026,'REGISTRATION','REGISTRATION',2,20.00,'AUTO',NULL,1,'2026-09-23 01:31:41'),(40,4,1,2026,'REGISTRATION','REGISTRATION',3,30.00,'AUTO',NULL,1,'2026-09-23 01:31:41'),(41,4,1,2026,'REGISTRATION','REGISTRATION',4,30.00,'AUTO',NULL,1,'2026-09-23 01:31:41'),(42,4,1,2026,'REGISTRATION','REGISTRATION',5,30.00,'AUTO',NULL,1,'2026-09-23 01:31:41'),(43,4,1,2026,'REGISTRATION','REGISTRATION',6,30.00,'AUTO',NULL,1,'2026-09-23 01:31:41'),(44,4,1,2026,'REGISTRATION','REGISTRATION',7,30.00,'AUTO',NULL,1,'2026-09-23 01:31:41'),(45,4,1,2026,'REGISTRATION','REGISTRATION',8,30.00,'AUTO',NULL,1,'2026-09-23 01:31:41');
/*!40000 ALTER TABLE `payment_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_voids`
--

DROP TABLE IF EXISTS `payment_voids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_voids` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` bigint(20) unsigned NOT NULL,
  `reason` varchar(255) NOT NULL,
  `voided_by` int(10) unsigned DEFAULT NULL,
  `voided_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_void_payment` (`payment_id`),
  KEY `fk_void_user` (`voided_by`),
  CONSTRAINT `fk_void_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_void_user` FOREIGN KEY (`voided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_voids`
--

LOCK TABLES `payment_voids` WRITE;
/*!40000 ALTER TABLE `payment_voids` DISABLE KEYS */;
INSERT INTO `payment_voids` VALUES (1,1,'k',1,'2026-09-22 02:52:33');
/*!40000 ALTER TABLE `payment_voids` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `receipt_no` varchar(30) NOT NULL,
  `member_id` int(10) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_type` enum('REGISTRATION','WELFARE','OTHER') NOT NULL,
  `payment_method` enum('CASH','MPESA','BANK','CHEQUE','OTHER') NOT NULL DEFAULT 'CASH',
  `reference_no` varchar(60) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('ACTIVE','VOIDED') NOT NULL DEFAULT 'ACTIVE',
  `entered_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_no` (`receipt_no`),
  KEY `idx_pay_member_year` (`member_id`,`year`),
  KEY `idx_pay_year_type` (`year`,`payment_type`),
  KEY `idx_pay_date` (`payment_date`),
  KEY `idx_pay_status` (`status`),
  KEY `fk_pay_user` (`entered_by`),
  CONSTRAINT `fk_pay_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pay_user` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,'CWA-2026-000001',1,2026,'2026-09-22',200.00,'REGISTRATION','CASH',NULL,NULL,'VOIDED',1,'2026-09-22 02:07:16','2026-09-22 02:52:33'),(2,'CWA-2026-000002',1,2026,'2026-09-22',200.00,'REGISTRATION','CASH',NULL,NULL,'ACTIVE',1,'2026-09-22 02:53:08','2026-09-22 02:53:08'),(3,'CWA-2026-000003',1,2026,'2026-09-22',100.00,'WELFARE','CASH',NULL,NULL,'ACTIVE',1,'2026-09-22 08:18:03','2026-09-22 08:18:03'),(4,'CWA-2026-000004',1,2026,'2026-09-23',200.00,'REGISTRATION','CASH',NULL,NULL,'ACTIVE',1,'2026-09-23 01:31:41','2026-09-23 01:31:41');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `receipt_seq`
--

DROP TABLE IF EXISTS `receipt_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `receipt_seq` (
  `year` smallint(5) unsigned NOT NULL,
  `last_value` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receipt_seq`
--

LOCK TABLES `receipt_seq` WRITE;
/*!40000 ALTER TABLE `receipt_seq` DISABLE KEYS */;
INSERT INTO `receipt_seq` VALUES (2026,4);
/*!40000 ALTER TABLE `receipt_seq` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'superadmin','System Administrator',NULL,'superadmin','$2y$10$Vo8gB/cRERUb9MqMtKQsOue3ovv3mJYc5.JCbSiQcwXk9IWQC05WW',1,NULL,NULL,'ACTIVE','2026-09-23 03:00:36','2026-09-18 16:38:22','2026-09-23 03:00:36'),(2,'sccadmin','System Administrator',NULL,'superadmin','$2y$10$Bb/yNU0AYGzoXfTnVqwjRube48c/7hGI14IWb9AsHRWfEzhr/Yjke',1,NULL,NULL,'ACTIVE','2026-09-18 18:29:27','2026-09-18 17:13:11','2026-09-19 09:14:44'),(3,'parishadmin','Parish Admin',NULL,'parishadmin','$2y$10$0VeMUPiBOpJC3hdBHLEovOMtydeoqxVOome4E7.Sd4AdgnbvXDW6K',2,NULL,NULL,'ACTIVE','2026-09-18 20:04:00','2026-09-18 20:03:46','2026-09-18 20:04:00'),(4,'viewer','Viewer Only',NULL,'superadmin','$2y$10$A7KSzfVkRRkJ/p2E4SrBJeirFlslhh37/EWcfOC1ahxFqtgmtSyDa',5,NULL,NULL,'ACTIVE','2026-09-23 02:36:35','2026-09-19 09:14:18','2026-09-23 02:36:35');
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

-- Dump completed on 2026-09-23  7:34:39
