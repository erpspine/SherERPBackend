-- MariaDB dump 10.19  Distrib 10.4.22-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: sher
-- ------------------------------------------------------
-- Server version	10.4.22-MariaDB

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
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('laravel-cache-spatie.permission.cache','a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:56:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:14:\"dashboard.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:10:\"users.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:12:\"users.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:12:\"users.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:12:\"users.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:12:\"clients.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:14:\"clients.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:14:\"clients.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:14:\"clients.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:10:\"leads.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:5;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:12:\"leads.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:12:\"leads.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:12:\"leads.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:15:\"quotations.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:17:\"quotations.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:17:\"quotations.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:17:\"quotations.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:22:\"proforma-invoices.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:13:\"invoices.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:3;i:2;i:5;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:15:\"invoices.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:15:\"invoices.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:15:\"invoices.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:22;a:4:{s:1:\"a\";i:23;s:1:\"b\";s:16:\"invoices.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:23;a:4:{s:1:\"a\";i:24;s:1:\"b\";s:21:\"invoice-payments.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:3;i:2;i:5;}}i:24;a:4:{s:1:\"a\";i:25;s:1:\"b\";s:23:\"invoice-payments.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:25;a:4:{s:1:\"a\";i:26;s:1:\"b\";s:13:\"settings.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:3;i:2;i:5;}}i:26;a:4:{s:1:\"a\";i:27;s:1:\"b\";s:15:\"settings.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:27;a:4:{s:1:\"a\";i:28;s:1:\"b\";s:10:\"parks.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:5;}}i:28;a:4:{s:1:\"a\";i:29;s:1:\"b\";s:12:\"parks.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:29;a:4:{s:1:\"a\";i:30;s:1:\"b\";s:12:\"parks.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:30;a:4:{s:1:\"a\";i:31;s:1:\"b\";s:12:\"parks.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:31;a:4:{s:1:\"a\";i:32;s:1:\"b\";s:15:\"park-rates.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:5;}}i:32;a:4:{s:1:\"a\";i:33;s:1:\"b\";s:17:\"park-rates.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:33;a:4:{s:1:\"a\";i:34;s:1:\"b\";s:17:\"park-rates.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:34;a:4:{s:1:\"a\";i:35;s:1:\"b\";s:17:\"park-rates.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:35;a:4:{s:1:\"a\";i:36;s:1:\"b\";s:21:\"concession-rates.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:5;}}i:36;a:4:{s:1:\"a\";i:37;s:1:\"b\";s:23:\"concession-rates.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:37;a:4:{s:1:\"a\";i:38;s:1:\"b\";s:23:\"concession-rates.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:38;a:4:{s:1:\"a\";i:39;s:1:\"b\";s:23:\"concession-rates.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:39;a:4:{s:1:\"a\";i:40;s:1:\"b\";s:20:\"transport-rates.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:5;}}i:40;a:4:{s:1:\"a\";i:41;s:1:\"b\";s:22:\"transport-rates.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:41;a:4:{s:1:\"a\";i:42;s:1:\"b\";s:22:\"transport-rates.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:42;a:4:{s:1:\"a\";i:43;s:1:\"b\";s:22:\"transport-rates.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:43;a:4:{s:1:\"a\";i:44;s:1:\"b\";s:13:\"vehicles.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:5;}}i:44;a:4:{s:1:\"a\";i:45;s:1:\"b\";s:15:\"vehicles.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:45;a:4:{s:1:\"a\";i:46;s:1:\"b\";s:15:\"vehicles.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:46;a:4:{s:1:\"a\";i:47;s:1:\"b\";s:15:\"vehicles.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:47;a:4:{s:1:\"a\";i:48;s:1:\"b\";s:15:\"vehicles.assign\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:48;a:4:{s:1:\"a\";i:49;s:1:\"b\";s:14:\"job-cards.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:5;}}i:49;a:4:{s:1:\"a\";i:50;s:1:\"b\";s:16:\"job-cards.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:50;a:4:{s:1:\"a\";i:51;s:1:\"b\";s:16:\"job-cards.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:51;a:4:{s:1:\"a\";i:52;s:1:\"b\";s:16:\"job-cards.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:52;a:4:{s:1:\"a\";i:53;s:1:\"b\";s:23:\"safari-allocations.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:5;}}i:53;a:4:{s:1:\"a\";i:54;s:1:\"b\";s:25:\"safari-allocations.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:54;a:4:{s:1:\"a\";i:55;s:1:\"b\";s:25:\"safari-allocations.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:55;a:4:{s:1:\"a\";i:56;s:1:\"b\";s:25:\"safari-allocations.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}}s:5:\"roles\";a:5:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:5:\"Admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:10:\"Operations\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:7:\"Finance\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:6:\"Driver\";s:1:\"c\";s:3:\"web\";}i:4;a:3:{s:1:\"a\";i:5;s:1:\"b\";s:6:\"Viewer\";s:1:\"c\";s:3:\"web\";}}}',1774935817),('shererp-cache-spatie.permission.cache','a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:56:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:14:\"dashboard.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:10:\"users.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:12:\"users.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:12:\"users.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:12:\"users.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:12:\"clients.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:14:\"clients.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:14:\"clients.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:14:\"clients.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:10:\"leads.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:5;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:12:\"leads.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:12:\"leads.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:12:\"leads.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:15:\"quotations.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:17:\"quotations.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:17:\"quotations.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:17:\"quotations.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:22:\"proforma-invoices.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:13:\"invoices.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:3;i:2;i:5;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:15:\"invoices.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:15:\"invoices.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:15:\"invoices.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:22;a:4:{s:1:\"a\";i:23;s:1:\"b\";s:16:\"invoices.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:23;a:4:{s:1:\"a\";i:24;s:1:\"b\";s:21:\"invoice-payments.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:3;i:2;i:5;}}i:24;a:4:{s:1:\"a\";i:25;s:1:\"b\";s:23:\"invoice-payments.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:25;a:4:{s:1:\"a\";i:26;s:1:\"b\";s:13:\"settings.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:5;}}i:26;a:4:{s:1:\"a\";i:27;s:1:\"b\";s:15:\"settings.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:27;a:4:{s:1:\"a\";i:28;s:1:\"b\";s:10:\"parks.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:5;}}i:28;a:4:{s:1:\"a\";i:29;s:1:\"b\";s:12:\"parks.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:29;a:4:{s:1:\"a\";i:30;s:1:\"b\";s:12:\"parks.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:30;a:4:{s:1:\"a\";i:31;s:1:\"b\";s:12:\"parks.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:31;a:4:{s:1:\"a\";i:32;s:1:\"b\";s:15:\"park-rates.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:5;}}i:32;a:4:{s:1:\"a\";i:33;s:1:\"b\";s:17:\"park-rates.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:33;a:4:{s:1:\"a\";i:34;s:1:\"b\";s:17:\"park-rates.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:34;a:4:{s:1:\"a\";i:35;s:1:\"b\";s:17:\"park-rates.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:35;a:4:{s:1:\"a\";i:36;s:1:\"b\";s:21:\"concession-rates.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:5;}}i:36;a:4:{s:1:\"a\";i:37;s:1:\"b\";s:23:\"concession-rates.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:37;a:4:{s:1:\"a\";i:38;s:1:\"b\";s:23:\"concession-rates.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:38;a:4:{s:1:\"a\";i:39;s:1:\"b\";s:23:\"concession-rates.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:39;a:4:{s:1:\"a\";i:40;s:1:\"b\";s:20:\"transport-rates.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:5;}}i:40;a:4:{s:1:\"a\";i:41;s:1:\"b\";s:22:\"transport-rates.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:41;a:4:{s:1:\"a\";i:42;s:1:\"b\";s:22:\"transport-rates.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:42;a:4:{s:1:\"a\";i:43;s:1:\"b\";s:22:\"transport-rates.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:43;a:4:{s:1:\"a\";i:44;s:1:\"b\";s:13:\"vehicles.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:5;}}i:44;a:4:{s:1:\"a\";i:45;s:1:\"b\";s:15:\"vehicles.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:45;a:4:{s:1:\"a\";i:46;s:1:\"b\";s:15:\"vehicles.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:46;a:4:{s:1:\"a\";i:47;s:1:\"b\";s:15:\"vehicles.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:47;a:4:{s:1:\"a\";i:48;s:1:\"b\";s:15:\"vehicles.assign\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:48;a:4:{s:1:\"a\";i:49;s:1:\"b\";s:14:\"job-cards.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:5;}}i:49;a:4:{s:1:\"a\";i:50;s:1:\"b\";s:16:\"job-cards.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:50;a:4:{s:1:\"a\";i:51;s:1:\"b\";s:16:\"job-cards.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:51;a:4:{s:1:\"a\";i:52;s:1:\"b\";s:16:\"job-cards.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:52;a:4:{s:1:\"a\";i:53;s:1:\"b\";s:23:\"safari-allocations.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:4;i:3;i:5;}}i:53;a:4:{s:1:\"a\";i:54;s:1:\"b\";s:25:\"safari-allocations.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:54;a:4:{s:1:\"a\";i:55;s:1:\"b\";s:25:\"safari-allocations.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:55;a:4:{s:1:\"a\";i:56;s:1:\"b\";s:25:\"safari-allocations.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}}s:5:\"roles\";a:5:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:5:\"Admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:10:\"Operations\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:7:\"Finance\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:6:\"Driver\";s:1:\"c\";s:3:\"web\";}i:4;a:3:{s:1:\"a\";i:5;s:1:\"b\";s:6:\"Viewer\";s:1:\"c\";s:3:\"web\";}}}',1776598511);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clients_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `concession_rates`
--

DROP TABLE IF EXISTS `concession_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `concession_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `park_id` bigint(20) unsigned NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate` decimal(10,2) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `concession_rates_park_id_type_category_unique` (`park_id`,`type`,`category`),
  CONSTRAINT `concession_rates_park_id_foreign` FOREIGN KEY (`park_id`) REFERENCES `parks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `concession_rates`
--

LOCK TABLES `concession_rates` WRITE;
/*!40000 ALTER TABLE `concession_rates` DISABLE KEYS */;
INSERT INTO `concession_rates` VALUES (1,1,'non_resident','adult',49.56,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(2,1,'non_resident','child',24.78,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(3,1,'resident','adult',49.56,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(4,1,'resident','child',24.78,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(5,1,'citizen','adult',15.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(6,1,'citizen','child',0.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(7,2,'non_resident','adult',61.95,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(8,2,'non_resident','child',24.78,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(9,2,'resident','adult',61.95,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(10,2,'resident','child',24.78,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(11,2,'citizen','adult',15.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(12,2,'citizen','child',0.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(13,3,'non_resident','adult',61.95,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(14,3,'non_resident','child',24.78,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(15,3,'resident','adult',61.95,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(16,3,'resident','child',24.78,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(17,3,'citizen','adult',15.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(18,3,'citizen','child',0.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(19,4,'non_resident','adult',74.34,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(20,4,'non_resident','child',24.78,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(21,4,'resident','adult',74.34,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(22,4,'resident','child',24.78,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(23,4,'citizen','adult',15.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(24,4,'citizen','child',0.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(25,5,'non_resident','adult',74.34,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(26,5,'non_resident','child',24.78,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(27,5,'resident','adult',74.34,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(28,5,'resident','child',24.78,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(29,5,'citizen','adult',15.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(30,5,'citizen','child',0.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(31,6,'non_resident','adult',37.17,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(32,6,'non_resident','child',12.39,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(33,6,'resident','adult',37.17,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(34,6,'resident','child',12.39,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(35,6,'citizen','adult',15.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(36,6,'citizen','child',0.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(37,7,'non_resident','adult',30.98,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(38,7,'non_resident','child',12.39,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(39,7,'resident','adult',30.98,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(40,7,'resident','child',12.39,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(41,7,'citizen','adult',15.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(42,7,'citizen','child',0.00,'2026-04-02 04:44:02','2026-04-02 04:44:02');
/*!40000 ALTER TABLE `concession_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_payments`
--

DROP TABLE IF EXISTS `invoice_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_payments_invoice_id_index` (`invoice_id`),
  KEY `invoice_payments_date_index` (`date`),
  CONSTRAINT `invoice_payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_payments`
--

LOCK TABLES `invoice_payments` WRITE;
/*!40000 ALTER TABLE `invoice_payments` DISABLE KEYS */;
INSERT INTO `invoice_payments` VALUES (1,1,'2026-03-29',400.00,'Bank Transfer','324242424',NULL,'2026-03-29 06:49:17','2026-03-29 06:49:17'),(2,1,'2026-03-29',364.64,'Bank Transfer','56888',NULL,'2026-03-29 07:02:17','2026-03-29 07:02:17');
/*!40000 ALTER TABLE `invoice_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `proforma_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_no` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quickbooks_ref` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `issue_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `total` decimal(12,2) NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_invoice_no_unique` (`invoice_no`),
  KEY `invoices_proforma_invoice_id_index` (`proforma_invoice_id`),
  KEY `invoices_issue_date_index` (`issue_date`),
  KEY `invoices_due_date_index` (`due_date`),
  KEY `invoices_status_index` (`status`),
  CONSTRAINT `invoices_proforma_invoice_id_foreign` FOREIGN KEY (`proforma_invoice_id`) REFERENCES `proforma_invoices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (1,1,'2323','455444','Hanspaul Automechs Ltd','2026-03-29','2026-03-29',764.64,'pending',NULL,'2026-03-29 06:48:57','2026-03-29 06:48:57');
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_cards`
--

DROP TABLE IF EXISTS `job_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_cards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `job_card_no` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `booking_reference_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tour_operator_client_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adults` int(10) unsigned NOT NULL DEFAULT 0,
  `children` int(10) unsigned NOT NULL DEFAULT 0,
  `nationality` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guide_language` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'English',
  `safari_start_date` date NOT NULL,
  `safari_end_date` date NOT NULL,
  `number_of_days` smallint(5) unsigned NOT NULL DEFAULT 1,
  `route_summary` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `route_itinerary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`route_itinerary`)),
  `pickup_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dropoff_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `additional_details` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_cards_job_card_no_unique` (`job_card_no`),
  KEY `job_cards_lead_id_foreign` (`lead_id`),
  CONSTRAINT `job_cards_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_cards`
--

LOCK TABLES `job_cards` WRITE;
/*!40000 ALTER TABLE `job_cards` DISABLE KEYS */;
INSERT INTO `job_cards` VALUES (1,2,'JC-2026-0001','BK-2026-5659','Hanspaul Automechs Ltd','Duncan Osur','+255760299974','ict@hanspaul.co.tz',4,1,'Tanzania','English','2026-03-25','2026-04-10',17,'Serengeti,Ngorongoro',NULL,'Arusha','Serengeti','Test Project','2026-03-29 04:32:12','2026-03-29 04:32:12'),(2,3,'JC-2026-0002','BK-2026-2573','Safari Trails','Rajay','+255711291714','technogurudigitalsystems@gmail.com',4,2,'Kenya','English','2026-04-03','2026-04-07',5,'Serengeti,Manyara',NULL,'Namanga','Arusha',NULL,'2026-03-30 06:17:41','2026-03-30 06:17:41');
/*!40000 ALTER TABLE `job_cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leads`
--

DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_ref` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_company` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `agent_contact` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `agent_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `agent_phone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_country` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `route_parks` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pax_adults` int(10) unsigned NOT NULL DEFAULT 0,
  `pax_children` int(10) unsigned NOT NULL DEFAULT 0,
  `no_of_vehicles` int(10) unsigned NOT NULL DEFAULT 1,
  `special_requirements` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `booking_status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `quotation_sent_by` bigint(20) unsigned DEFAULT NULL,
  `quotation_sent_at` timestamp NULL DEFAULT NULL,
  `pi_sent_by` bigint(20) unsigned DEFAULT NULL,
  `pi_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leads_booking_ref_unique` (`booking_ref`),
  KEY `leads_quotation_sent_by_foreign` (`quotation_sent_by`),
  KEY `leads_pi_sent_by_foreign` (`pi_sent_by`),
  CONSTRAINT `leads_pi_sent_by_foreign` FOREIGN KEY (`pi_sent_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leads_quotation_sent_by_foreign` FOREIGN KEY (`quotation_sent_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leads`
--

LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
INSERT INTO `leads` VALUES (1,'BK-2026-7897','Technoguru Digital Systems Ltd','Duncan Osur','osurdancan@gmail.com','+255711292714','Kenya','2026-03-25','2026-04-10','Serengeti,Ngorongoro',4,1,1,'N/A','Pending',NULL,NULL,NULL,NULL,'2026-03-25 11:05:41','2026-03-25 11:05:41'),(2,'BK-2026-5659','Hanspaul Automechs Ltd','Duncan Osur','ict@hanspaul.co.tz','+255760299974','Tanzania','2026-03-25','2026-04-10','Serengeti,Ngorongoro',4,1,1,'Test Project','PI Sent',1,'2026-03-28 06:32:40',1,'2026-03-28 07:03:13','2026-03-25 11:12:50','2026-03-28 07:03:13'),(3,'BK-2026-2573','Safari Trails','Rajay','technogurudigitalsystems@gmail.com','+255711291714','Kenya','2026-04-03','2026-04-07','Serengeti,Manyara',4,2,1,NULL,'PI Sent',1,'2026-03-30 06:08:19',1,'2026-03-30 06:11:21','2026-03-30 06:02:33','2026-03-30 06:11:21'),(4,'BK-2026-4299','Safari Trails','Raja','technogurudigitalsystems@gmail.com','+255711291714','Kenya','2026-04-03','2026-04-07','Arusha,Ngorongoro,Serengeti,Arusha',2,0,1,'Put bottled water','Quotation Sent',1,'2026-04-02 05:51:56',NULL,NULL,'2026-04-02 05:30:05','2026-04-02 05:51:56');
/*!40000 ALTER TABLE `leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_03_25_112501_create_personal_access_tokens_table',2),(5,'2026_03_25_120000_add_profile_fields_to_users_table',3),(6,'2026_03_25_130000_create_settings_table',4),(7,'2026_03_25_131000_seed_default_company_settings',5),(8,'2026_03_25_140000_create_leads_table',6),(9,'2026_03_25_150000_create_quotations_table',7),(10,'2026_03_25_150100_create_quotation_line_items_table',7),(11,'2026_03_28_000000_create_parks_table',8),(12,'2026_03_28_000100_create_park_rates_table',9),(13,'2026_03_28_000200_create_concession_rates_table',10),(14,'2026_03_28_000300_create_transport_rates_table',10),(15,'2026_03_28_000400_alter_quotations_add_day_sections',11),(16,'2026_03_28_000500_alter_quotation_line_items_new_structure',11),(17,'2026_03_28_000600_alter_leads_add_quotation_sent_by',12),(18,'2026_03_28_000700_create_proforma_invoices_table',13),(19,'2026_03_28_000800_create_proforma_invoice_line_items_table',13),(20,'2026_03_28_000900_alter_leads_add_pi_sent_tracking',13),(21,'2026_03_28_001000_create_vehicles_table',14),(22,'2026_03_28_001100_alter_vehicles_add_status',15),(23,'2026_03_29_000000_create_job_cards_table',16),(24,'2026_03_29_160000_create_safari_allocations_table',17),(25,'2026_03_29_170000_create_invoices_table',18),(26,'2026_03_29_170100_create_invoice_payments_table',18),(27,'2026_03_29_180000_create_clients_table',19),(28,'2026_03_29_160756_create_permission_tables',20),(29,'2026_03_30_000000_add_status_to_invoices_table',20);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1),(1,'App\\Models\\User',2),(4,'App\\Models\\User',8);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `park_rates`
--

DROP TABLE IF EXISTS `park_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `park_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `park_id` bigint(20) unsigned NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate` decimal(12,2) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `park_rates_park_id_type_category_unique` (`park_id`,`type`,`category`),
  CONSTRAINT `park_rates_park_id_foreign` FOREIGN KEY (`park_id`) REFERENCES `parks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `park_rates`
--

LOCK TABLES `park_rates` WRITE;
/*!40000 ALTER TABLE `park_rates` DISABLE KEYS */;
INSERT INTO `park_rates` VALUES (1,1,'non_resident','adult',61.95,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(2,1,'non_resident','child',24.78,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(3,1,'resident','adult',31.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(4,1,'resident','child',0.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(5,1,'citizen','adult',6.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(6,1,'citizen','child',1.20,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(7,2,'non_resident','adult',61.95,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(8,2,'non_resident','child',24.78,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(9,2,'resident','adult',31.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(10,2,'resident','child',0.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(11,2,'citizen','adult',6.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(12,2,'citizen','child',1.20,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(13,3,'non_resident','adult',74.34,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(14,3,'non_resident','child',24.78,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(15,3,'resident','adult',37.17,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(16,3,'resident','child',0.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(17,3,'citizen','adult',6.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(18,3,'citizen','child',1.20,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(19,4,'non_resident','adult',86.73,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(20,4,'non_resident','child',24.78,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(21,4,'resident','adult',44.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(22,4,'resident','child',0.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(23,4,'citizen','adult',6.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(24,4,'citizen','child',1.20,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(25,5,'non_resident','adult',86.73,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(26,5,'non_resident','child',24.78,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(27,5,'resident','adult',44.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(28,5,'resident','child',0.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(29,5,'citizen','adult',6.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(30,5,'citizen','child',1.20,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(31,6,'non_resident','adult',37.17,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(32,6,'non_resident','child',24.78,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(33,6,'resident','adult',19.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(34,6,'resident','child',0.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(35,6,'citizen','adult',6.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(36,6,'citizen','child',1.20,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(37,7,'non_resident','adult',37.17,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(38,7,'non_resident','child',24.78,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(39,7,'resident','adult',19.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(40,7,'resident','child',0.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(41,7,'citizen','adult',6.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(42,7,'citizen','child',1.20,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(43,8,'non_resident','adult',37.17,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(44,8,'non_resident','child',12.39,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(45,8,'resident','adult',37.17,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(46,8,'resident','child',0.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(47,8,'citizen','adult',6.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(48,8,'citizen','child',1.20,'2026-04-02 04:44:02','2026-04-02 04:44:02');
/*!40000 ALTER TABLE `park_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parks`
--

DROP TABLE IF EXISTS `parks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `region` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `parks_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parks`
--

LOCK TABLES `parks` WRITE;
/*!40000 ALTER TABLE `parks` DISABLE KEYS */;
INSERT INTO `parks` VALUES (1,'Tarangire National Park','Manyara','Active','2026-04-02 04:44:02','2026-04-02 04:44:02'),(2,'Manyara National Park','Manyara','Active','2026-04-02 04:44:02','2026-04-02 04:44:02'),(3,'Ngorongoro Conservation Area','Arusha','Active','2026-04-02 04:44:02','2026-04-02 04:44:02'),(4,'Serengeti National Park','Mara','Active','2026-04-02 04:44:02','2026-04-02 04:44:02'),(5,'Nyerere National Park (Selous)','Lindi/Ruvuma','Active','2026-04-02 04:44:02','2026-04-02 04:44:02'),(6,'Ruaha National Park','Iringa','Active','2026-04-02 04:44:02','2026-04-02 04:44:02'),(7,'Mikumi National Park','Morogoro','Active','2026-04-02 04:44:02','2026-04-02 04:44:02'),(8,'Oldovai','Arusha','Active','2026-04-02 04:44:02','2026-04-02 04:44:02');
/*!40000 ALTER TABLE `parks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'dashboard.view','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(2,'users.view','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(3,'users.create','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(4,'users.update','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(5,'users.delete','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(6,'clients.view','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(7,'clients.create','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(8,'clients.update','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(9,'clients.delete','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(10,'leads.view','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(11,'leads.create','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(12,'leads.update','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(13,'leads.delete','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(14,'quotations.view','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(15,'quotations.create','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(16,'quotations.update','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(17,'quotations.delete','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(18,'proforma-invoices.view','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(19,'invoices.view','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(20,'invoices.create','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(21,'invoices.update','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(22,'invoices.delete','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(23,'invoices.approve','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(24,'invoice-payments.view','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(25,'invoice-payments.create','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(26,'settings.view','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(27,'settings.update','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(28,'parks.view','web','2026-03-30 02:42:37','2026-03-30 02:42:37'),(29,'parks.create','web','2026-03-30 02:42:38','2026-03-30 02:42:38'),(30,'parks.update','web','2026-03-30 02:42:38','2026-03-30 02:42:38'),(31,'parks.delete','web','2026-03-30 02:42:38','2026-03-30 02:42:38'),(32,'park-rates.view','web','2026-03-30 02:42:38','2026-03-30 02:42:38'),(33,'park-rates.create','web','2026-03-30 02:42:38','2026-03-30 02:42:38'),(34,'park-rates.update','web','2026-03-30 02:42:38','2026-03-30 02:42:38'),(35,'park-rates.delete','web','2026-03-30 02:42:38','2026-03-30 02:42:38'),(36,'concession-rates.view','web','2026-03-30 02:42:38','2026-03-30 02:42:38'),(37,'concession-rates.create','web','2026-03-30 02:42:38','2026-03-30 02:42:38'),(38,'concession-rates.update','web','2026-03-30 02:42:38','2026-03-30 02:42:38'),(39,'concession-rates.delete','web','2026-03-30 02:42:38','2026-03-30 02:42:38'),(40,'transport-rates.view','web','2026-03-30 02:42:38','2026-03-30 02:42:38'),(41,'transport-rates.create','web','2026-03-30 02:42:38','2026-03-30 02:42:38'),(42,'transport-rates.update','web','2026-03-30 02:42:38','2026-03-30 02:42:38'),(43,'transport-rates.delete','web','2026-03-30 02:42:38','2026-03-30 02:42:38'),(44,'vehicles.view','web','2026-03-30 02:42:38','2026-03-30 02:42:38'),(45,'vehicles.create','web','2026-03-30 02:42:38','2026-03-30 02:42:38'),(46,'vehicles.update','web','2026-03-30 02:42:38','2026-03-30 02:42:38'),(47,'vehicles.delete','web','2026-03-30 02:42:38','2026-03-30 02:42:38'),(48,'vehicles.assign','web','2026-03-30 02:42:38','2026-03-30 02:42:38'),(49,'job-cards.view','web','2026-03-30 02:42:38','2026-03-30 02:42:38'),(50,'job-cards.create','web','2026-03-30 02:42:38','2026-03-30 02:42:38'),(51,'job-cards.update','web','2026-03-30 02:42:39','2026-03-30 02:42:39'),(52,'job-cards.delete','web','2026-03-30 02:42:39','2026-03-30 02:42:39'),(53,'safari-allocations.view','web','2026-03-30 02:42:39','2026-03-30 02:42:39'),(54,'safari-allocations.create','web','2026-03-30 02:42:39','2026-03-30 02:42:39'),(55,'safari-allocations.update','web','2026-03-30 02:42:39','2026-03-30 02:42:39'),(56,'safari-allocations.delete','web','2026-03-30 02:42:39','2026-03-30 02:42:39');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\User',1,'api-token','5bf0952fe53185f179c006e40f88846e8bdd15907685b6b3c0ab3c8d5798e3b7','[\"*\"]','2026-04-18 08:39:21',NULL,'2026-03-25 08:36:10','2026-04-18 08:39:21'),(2,'App\\Models\\User',1,'api-token','22c79348884580e9f58d8fbaf785dc7ed07f7f381d1394232d0b57689150f9e4','[\"*\"]','2026-03-27 07:03:57',NULL,'2026-03-25 09:00:23','2026-03-27 07:03:57'),(3,'App\\Models\\User',1,'api-token','f5365b0e53e2697bc9d12921d25f8ee3178247c4d2cd5193cedd562690928568','[\"*\"]','2026-04-02 07:03:42',NULL,'2026-03-28 04:20:19','2026-04-02 07:03:42'),(5,'App\\Models\\User',1,'api-token','582786fd4ac6ac3677cba82ccbeca344d77464767b703ce4b2c6f02fcecb0087','[\"*\"]','2026-04-18 07:53:13',NULL,'2026-04-18 06:31:28','2026-04-18 07:53:13'),(6,'App\\Models\\User',1,'api-token','5fb27d784370e932aa93992a1f5b74e919d3b72cb786220ee46f2dcce105ac57','[\"*\"]','2026-04-18 08:38:28',NULL,'2026-04-18 07:43:19','2026-04-18 08:38:28');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proforma_invoice_line_items`
--

DROP TABLE IF EXISTS `proforma_invoice_line_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proforma_invoice_line_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `proforma_invoice_id` bigint(20) unsigned NOT NULL,
  `day_title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `day_description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty` decimal(10,2) NOT NULL DEFAULT 1.00,
  `rate` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proforma_invoice_line_items_proforma_invoice_id_foreign` (`proforma_invoice_id`),
  CONSTRAINT `proforma_invoice_line_items_proforma_invoice_id_foreign` FOREIGN KEY (`proforma_invoice_id`) REFERENCES `proforma_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proforma_invoice_line_items`
--

LOCK TABLES `proforma_invoice_line_items` WRITE;
/*!40000 ALTER TABLE `proforma_invoice_line_items` DISABLE KEYS */;
INSERT INTO `proforma_invoice_line_items` VALUES (1,1,'Day 1','From Arusha to Serengeti','Transport','Transport-Per pehicle per day net','Per person',1.00,200.00,200.00,'2026-03-28 07:03:13','2026-03-28 07:03:13'),(2,1,'Day 1','From Arusha to Serengeti','Park Fees','Serengeti National Park ΓÇö resident ΓÇö adult','Per person',1.00,18.00,18.00,'2026-03-28 07:03:13','2026-03-28 07:03:13'),(3,1,'Day 1','From Arusha to Serengeti','Concession Fees','Serengeti National Park ΓÇö resident ΓÇö adult','Per person',1.00,20.00,20.00,'2026-03-28 07:03:13','2026-03-28 07:03:13'),(4,1,'Day 2','From Serengeti to Arusha','Transport','Transport-Per pehicle per day net','Per person',1.00,200.00,200.00,'2026-03-28 07:03:13','2026-03-28 07:03:13'),(5,1,'Day 2','From Serengeti to Arusha','Park Fees','Serengeti National Park ΓÇö resident ΓÇö child','Per person',1.00,10.00,10.00,'2026-03-28 07:03:13','2026-03-28 07:03:13'),(6,1,'Day 2','From Serengeti to Arusha','Transport','Transport-Per pehicle per day net','Per person',1.00,200.00,200.00,'2026-03-28 07:03:13','2026-03-28 07:03:13'),(7,2,'Day 1','Serengeti','Transport','Serengeti','Per person',1.00,65.00,65.00,'2026-03-30 06:11:21','2026-03-30 06:11:21'),(8,2,'Day 1','Serengeti','Park Fees','Serengeti National Park ΓÇö resident ΓÇö adult','Per person',3.00,18.00,54.00,'2026-03-30 06:11:21','2026-03-30 06:11:21'),(9,2,'Day 1','Serengeti','Concession Fees','Serengeti National Park ΓÇö resident ΓÇö adult','Per person',2.00,20.00,40.00,'2026-03-30 06:11:21','2026-03-30 06:11:21'),(10,2,'Day 2','Manyara','Transport','Transport-Per pehicle per day net','Per person',1.00,200.00,200.00,'2026-03-30 06:11:21','2026-03-30 06:11:21');
/*!40000 ALTER TABLE `proforma_invoice_line_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proforma_invoices`
--

DROP TABLE IF EXISTS `proforma_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proforma_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `quotation_id` bigint(20) unsigned NOT NULL,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `client` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attention` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quote_date` date NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `day_sections` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`day_sections`)),
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Sent',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `proforma_invoices_quotation_id_unique` (`quotation_id`),
  KEY `proforma_invoices_lead_id_foreign` (`lead_id`),
  CONSTRAINT `proforma_invoices_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `proforma_invoices_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proforma_invoices`
--

LOCK TABLES `proforma_invoices` WRITE;
/*!40000 ALTER TABLE `proforma_invoices` DISABLE KEYS */;
INSERT INTO `proforma_invoices` VALUES (1,1,2,'Hanspaul Automechs Ltd','Duncan Osur','2026-03-25','Lead BK-2026-5659 | Serengeti,Ngorongoro | 2026-03-25 to 2026-04-10 | Test Project','[{\"dayTitle\":\"Day 1\",\"dayDescription\":\"From Arusha to Serengeti\",\"items\":[{\"item\":\"Transport\",\"description\":\"Transport-Per pehicle per day net\",\"unit\":\"Per person\",\"qty\":\"1\",\"rate\":\"200\"},{\"item\":\"Park Fees\",\"description\":\"Serengeti National Park \\u2014 resident \\u2014 adult\",\"unit\":\"Per person\",\"qty\":\"1\",\"rate\":\"18\"},{\"item\":\"Concession Fees\",\"description\":\"Serengeti National Park \\u2014 resident \\u2014 adult\",\"unit\":\"Per person\",\"qty\":\"1\",\"rate\":\"20\"}]},{\"dayTitle\":\"Day 2\",\"dayDescription\":\"From Serengeti to Arusha\",\"items\":[{\"item\":\"Transport\",\"description\":\"Transport-Per pehicle per day net\",\"unit\":\"Per person\",\"qty\":\"1\",\"rate\":\"200\"},{\"item\":\"Park Fees\",\"description\":\"Serengeti National Park \\u2014 resident \\u2014 child\",\"unit\":\"Per person\",\"qty\":\"1\",\"rate\":\"10\"},{\"item\":\"Transport\",\"description\":\"Transport-Per pehicle per day net\",\"unit\":\"Per person\",\"qty\":\"1\",\"rate\":\"200\"}]}]',648.00,116.64,764.64,'Sent','2026-03-28 07:03:13','2026-03-28 07:03:13'),(2,2,3,'Safari Trails','Rajay','2026-03-30','Lead BK-2026-2573 | Serengeti,Manyara | 2026-04-03 to 2026-04-07','[{\"dayTitle\":\"Day 1\",\"dayDescription\":\"Serengeti\",\"items\":[{\"item\":\"Transport\",\"description\":\"Serengeti\",\"unit\":\"Per person\",\"qty\":\"1\",\"rate\":\"65\"},{\"item\":\"Park Fees\",\"description\":\"Serengeti National Park \\u2014 resident \\u2014 adult\",\"unit\":\"Per person\",\"qty\":\"3\",\"rate\":\"18\"},{\"item\":\"Concession Fees\",\"description\":\"Serengeti National Park \\u2014 resident \\u2014 adult\",\"unit\":\"Per person\",\"qty\":\"2\",\"rate\":\"20\"}]},{\"dayTitle\":\"Day 2\",\"dayDescription\":\"Manyara\",\"items\":[{\"item\":\"Transport\",\"description\":\"Transport-Per pehicle per day net\",\"unit\":\"Per person\",\"qty\":\"1\",\"rate\":\"200\"}]}]',359.00,64.62,423.62,'Sent','2026-03-30 06:11:21','2026-03-30 06:11:21');
/*!40000 ALTER TABLE `proforma_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotation_line_items`
--

DROP TABLE IF EXISTS `quotation_line_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotation_line_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `quotation_id` bigint(20) unsigned NOT NULL,
  `day_title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `day_description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty` decimal(10,2) NOT NULL DEFAULT 1.00,
  `rate` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quotation_line_items_quotation_id_foreign` (`quotation_id`),
  CONSTRAINT `quotation_line_items_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotation_line_items`
--

LOCK TABLES `quotation_line_items` WRITE;
/*!40000 ALTER TABLE `quotation_line_items` DISABLE KEYS */;
INSERT INTO `quotation_line_items` VALUES (19,1,'Day 1','From Arusha to Serengeti','Transport','Transport-Per pehicle per day net','Per person',1.00,200.00,200.00,'2026-03-28 06:32:40','2026-03-28 06:32:40'),(20,1,'Day 1','From Arusha to Serengeti','Park Fees','Serengeti National Park ΓÇö resident ΓÇö adult','Per person',1.00,18.00,18.00,'2026-03-28 06:32:40','2026-03-28 06:32:40'),(21,1,'Day 1','From Arusha to Serengeti','Concession Fees','Serengeti National Park ΓÇö resident ΓÇö adult','Per person',1.00,20.00,20.00,'2026-03-28 06:32:40','2026-03-28 06:32:40'),(22,1,'Day 2','From Serengeti to Arusha','Transport','Transport-Per pehicle per day net','Per person',1.00,200.00,200.00,'2026-03-28 06:32:40','2026-03-28 06:32:40'),(23,1,'Day 2','From Serengeti to Arusha','Park Fees','Serengeti National Park ΓÇö resident ΓÇö child','Per person',1.00,10.00,10.00,'2026-03-28 06:32:40','2026-03-28 06:32:40'),(24,1,'Day 2','From Serengeti to Arusha','Transport','Transport-Per pehicle per day net','Per person',1.00,200.00,200.00,'2026-03-28 06:32:40','2026-03-28 06:32:40'),(25,2,'Day 1','Serengeti','Transport','Serengeti','Per person',1.00,65.00,65.00,'2026-03-30 06:08:19','2026-03-30 06:08:19'),(26,2,'Day 1','Serengeti','Park Fees','Serengeti National Park ΓÇö resident ΓÇö adult','Per person',3.00,18.00,54.00,'2026-03-30 06:08:19','2026-03-30 06:08:19'),(27,2,'Day 1','Serengeti','Concession Fees','Serengeti National Park ΓÇö resident ΓÇö adult','Per person',2.00,20.00,40.00,'2026-03-30 06:08:19','2026-03-30 06:08:19'),(28,2,'Day 2','Manyara','Transport','Transport-Per pehicle per day net','Per person',1.00,200.00,200.00,'2026-03-30 06:08:19','2026-03-30 06:08:19'),(29,3,'Day 1','Kilimanjaro Pickup','Transport','Arusha Kilimanjaro Pickup','Per vehicle',1.00,49.20,49.20,'2026-04-02 05:51:56','2026-04-02 05:51:56'),(30,3,'Day 1','Kilimanjaro Pickup','Transport','Drive to Ngorongoro farm House','Per person',1.00,193.52,193.52,'2026-04-02 05:51:56','2026-04-02 05:51:56'),(31,3,'Day 2','Ngorongoro park','Transport','Per vehicle per day net ΓÇô 4 days or more','Per vehicle',1.00,193.52,193.52,'2026-04-02 05:51:56','2026-04-02 05:51:56'),(32,3,'Day 2','Ngorongoro park','Park Fees','Ngorongoro park fees','Per person',2.00,60.95,121.90,'2026-04-02 05:51:56','2026-04-02 05:51:56'),(33,3,'Day 2','Ngorongoro park','Park Fees','Creater fees','Per vehicle',1.00,254.00,254.00,'2026-04-02 05:51:56','2026-04-02 05:51:56'),(34,3,'Day 3','Ngorongoro to Serengeti','Transport','Per vehicle per day net ΓÇô 4 days or more','Per vehicle',1.00,193.52,193.52,'2026-04-02 05:51:56','2026-04-02 05:51:56'),(35,3,'Day 3','Ngorongoro to Serengeti','Transport','Transit fees','Per person',2.00,61.00,122.00,'2026-04-02 05:51:56','2026-04-02 05:51:56'),(36,3,'Day 3','Ngorongoro to Serengeti','Park Fees','Serengeti National Park ΓÇö non_resident ΓÇö child','Per person',2.00,71.11,142.22,'2026-04-02 05:51:56','2026-04-02 05:51:56'),(37,3,'Day 3','Ngorongoro to Serengeti','Concession Fees','Serengeti concession','Per person',2.00,61.00,122.00,'2026-04-02 05:51:56','2026-04-02 05:51:56'),(38,3,'Day 4','Serengeti','Transport','Per vehicle per day net ΓÇô 4 days or more','Per vehicle',1.00,193.52,193.52,'2026-04-02 05:51:56','2026-04-02 05:51:56'),(39,3,'Day 4','Serengeti','Park Fees','Serengeti National Park ΓÇö non_resident ΓÇö adult','Per person',2.00,71.11,142.22,'2026-04-02 05:51:56','2026-04-02 05:51:56'),(40,3,'Day 4','Serengeti','Concession Fees','Serengeti National Park ΓÇö non_resident ΓÇö adult','Per person',2.00,61.00,122.00,'2026-04-02 05:51:56','2026-04-02 05:51:56'),(41,3,'Day 5','Drive to Arusha','Transport','Per vehicle per day net ΓÇô 4 days or more','Per vehicle',1.00,193.52,193.52,'2026-04-02 05:51:56','2026-04-02 05:51:56'),(42,3,'Day 5','Drive to Arusha','Transport','Arusha Kilimanjaro','Per vehicle',1.00,49.20,49.20,'2026-04-02 05:51:56','2026-04-02 05:51:56'),(43,3,'Day 5','Drive to Arusha','Transport','Park fees Ngorongoro-Transit','Per person',2.00,61.00,122.00,'2026-04-02 05:51:56','2026-04-02 05:51:56');
/*!40000 ALTER TABLE `quotation_line_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotations`
--

DROP TABLE IF EXISTS `quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned DEFAULT NULL,
  `client` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attention` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quote_date` date NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `day_sections` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`day_sections`)),
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quotations_lead_id_foreign` (`lead_id`),
  CONSTRAINT `quotations_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotations`
--

LOCK TABLES `quotations` WRITE;
/*!40000 ALTER TABLE `quotations` DISABLE KEYS */;
INSERT INTO `quotations` VALUES (1,2,'Hanspaul Automechs Ltd','Duncan Osur','2026-03-25','Lead BK-2026-5659 | Serengeti,Ngorongoro | 2026-03-25 to 2026-04-10 | Test Project','[{\"dayTitle\":\"Day 1\",\"dayDescription\":\"From Arusha to Serengeti\",\"items\":[{\"item\":\"Transport\",\"description\":\"Transport-Per pehicle per day net\",\"unit\":\"Per person\",\"qty\":\"1\",\"rate\":\"200\"},{\"item\":\"Park Fees\",\"description\":\"Serengeti National Park \\u2014 resident \\u2014 adult\",\"unit\":\"Per person\",\"qty\":\"1\",\"rate\":\"18\"},{\"item\":\"Concession Fees\",\"description\":\"Serengeti National Park \\u2014 resident \\u2014 adult\",\"unit\":\"Per person\",\"qty\":\"1\",\"rate\":\"20\"}]},{\"dayTitle\":\"Day 2\",\"dayDescription\":\"From Serengeti to Arusha\",\"items\":[{\"item\":\"Transport\",\"description\":\"Transport-Per pehicle per day net\",\"unit\":\"Per person\",\"qty\":\"1\",\"rate\":\"200\"},{\"item\":\"Park Fees\",\"description\":\"Serengeti National Park \\u2014 resident \\u2014 child\",\"unit\":\"Per person\",\"qty\":\"1\",\"rate\":\"10\"},{\"item\":\"Transport\",\"description\":\"Transport-Per pehicle per day net\",\"unit\":\"Per person\",\"qty\":\"1\",\"rate\":\"200\"}]}]',648.00,116.64,764.64,'Converted','2026-03-25 12:02:10','2026-03-28 07:03:13'),(2,3,'Safari Trails','Rajay','2026-03-30','Lead BK-2026-2573 | Serengeti,Manyara | 2026-04-03 to 2026-04-07','[{\"dayTitle\":\"Day 1\",\"dayDescription\":\"Serengeti\",\"items\":[{\"item\":\"Transport\",\"description\":\"Serengeti\",\"unit\":\"Per person\",\"qty\":\"1\",\"rate\":\"65\"},{\"item\":\"Park Fees\",\"description\":\"Serengeti National Park \\u2014 resident \\u2014 adult\",\"unit\":\"Per person\",\"qty\":\"3\",\"rate\":\"18\"},{\"item\":\"Concession Fees\",\"description\":\"Serengeti National Park \\u2014 resident \\u2014 adult\",\"unit\":\"Per person\",\"qty\":\"2\",\"rate\":\"20\"}]},{\"dayTitle\":\"Day 2\",\"dayDescription\":\"Manyara\",\"items\":[{\"item\":\"Transport\",\"description\":\"Transport-Per pehicle per day net\",\"unit\":\"Per person\",\"qty\":\"1\",\"rate\":\"200\"}]}]',359.00,64.62,423.62,'Converted','2026-03-30 06:08:19','2026-03-30 06:11:21'),(3,4,'Safari Trails','Raja','2026-03-20','Lead BK-2026-4299 | Arusha,Ngorongoro,Serengeti,Arusha | 2026-04-03 to 2026-04-07 | Put bottled water','[{\"dayTitle\":\"Day 1\",\"dayDescription\":\"Kilimanjaro Pickup\",\"items\":[{\"item\":\"Transport\",\"description\":\"Arusha Kilimanjaro Pickup\",\"unit\":\"Per vehicle\",\"qty\":\"1\",\"rate\":\"49.2\"},{\"item\":\"Transport\",\"description\":\"Drive to Ngorongoro farm House\",\"unit\":\"Per person\",\"qty\":\"1\",\"rate\":\"193.52\"}]},{\"dayTitle\":\"Day 2\",\"dayDescription\":\"Ngorongoro park\",\"items\":[{\"item\":\"Transport\",\"description\":\"Per vehicle per day net \\u2013 4 days or more\",\"unit\":\"Per vehicle\",\"qty\":\"1\",\"rate\":\"193.52\"},{\"item\":\"Park Fees\",\"description\":\"Ngorongoro park fees\",\"unit\":\"Per person\",\"qty\":\"2\",\"rate\":\"60.95\"},{\"item\":\"Park Fees\",\"description\":\"Creater fees\",\"unit\":\"Per vehicle\",\"qty\":\"1\",\"rate\":\"254\"}]},{\"dayTitle\":\"Day 3\",\"dayDescription\":\"Ngorongoro to Serengeti\",\"items\":[{\"item\":\"Transport\",\"description\":\"Per vehicle per day net \\u2013 4 days or more\",\"unit\":\"Per vehicle\",\"qty\":\"1\",\"rate\":\"193.52\"},{\"item\":\"Transport\",\"description\":\"Transit fees\",\"unit\":\"Per person\",\"qty\":\"2\",\"rate\":\"61\"},{\"item\":\"Park Fees\",\"description\":\"Serengeti National Park \\u2014 non_resident \\u2014 child\",\"unit\":\"Per person\",\"qty\":\"2\",\"rate\":\"71.11\"},{\"item\":\"Concession Fees\",\"description\":\"Serengeti concession\",\"unit\":\"Per person\",\"qty\":\"2\",\"rate\":\"61\"}]},{\"dayTitle\":\"Day 4\",\"dayDescription\":\"Serengeti\",\"items\":[{\"item\":\"Transport\",\"description\":\"Per vehicle per day net \\u2013 4 days or more\",\"unit\":\"Per vehicle\",\"qty\":\"1\",\"rate\":\"193.52\"},{\"item\":\"Park Fees\",\"description\":\"Serengeti National Park \\u2014 non_resident \\u2014 adult\",\"unit\":\"Per person\",\"qty\":\"2\",\"rate\":\"71.11\"},{\"item\":\"Concession Fees\",\"description\":\"Serengeti National Park \\u2014 non_resident \\u2014 adult\",\"unit\":\"Per person\",\"qty\":\"2\",\"rate\":\"61\"}]},{\"dayTitle\":\"Day 5\",\"dayDescription\":\"Drive to Arusha\",\"items\":[{\"item\":\"Transport\",\"description\":\"Per vehicle per day net \\u2013 4 days or more\",\"unit\":\"Per vehicle\",\"qty\":\"1\",\"rate\":\"193.52\"},{\"item\":\"Transport\",\"description\":\"Arusha Kilimanjaro\",\"unit\":\"Per vehicle\",\"qty\":\"1\",\"rate\":\"49.2\"},{\"item\":\"Transport\",\"description\":\"Park fees Ngorongoro-Transit\",\"unit\":\"Per person\",\"qty\":\"2\",\"rate\":\"61\"}]}]',2214.34,398.58,2612.92,'Sent','2026-04-02 05:51:56','2026-04-02 05:51:56');
/*!40000 ALTER TABLE `quotations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(2,1),(2,2),(3,1),(3,2),(4,1),(4,2),(5,1),(6,1),(6,2),(6,3),(6,5),(7,1),(7,2),(8,1),(8,2),(9,1),(10,1),(10,2),(10,5),(11,1),(11,2),(12,1),(12,2),(13,1),(14,1),(14,2),(14,3),(14,5),(15,1),(15,2),(16,1),(16,2),(17,1),(18,1),(18,2),(18,3),(18,5),(19,1),(19,3),(19,5),(20,1),(20,3),(21,1),(21,3),(22,1),(23,1),(23,3),(24,1),(24,3),(24,5),(25,1),(25,3),(26,1),(26,2),(26,3),(26,5),(27,1),(27,2),(27,3),(28,1),(28,2),(28,5),(29,1),(30,1),(31,1),(32,1),(32,2),(32,5),(33,1),(34,1),(35,1),(36,1),(36,2),(36,5),(37,1),(38,1),(39,1),(40,1),(40,2),(40,5),(41,1),(42,1),(43,1),(44,1),(44,2),(44,4),(44,5),(45,1),(46,1),(47,1),(48,1),(48,2),(49,1),(49,2),(49,4),(49,5),(50,1),(50,2),(51,1),(51,2),(52,1),(53,1),(53,2),(53,4),(53,5),(54,1),(54,2),(55,1),(55,2),(56,1);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Admin','web','2026-03-30 02:42:39','2026-03-30 02:42:39'),(2,'Operations','web','2026-03-30 02:42:39','2026-03-30 02:42:39'),(3,'Finance','web','2026-03-30 02:42:39','2026-03-30 02:42:39'),(4,'Driver','web','2026-03-30 02:42:39','2026-03-30 02:42:39'),(5,'Viewer','web','2026-03-30 02:42:39','2026-03-30 02:42:39');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `safari_allocations`
--

DROP TABLE IF EXISTS `safari_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `safari_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned NOT NULL,
  `proforma_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `vehicle_id` bigint(20) unsigned NOT NULL,
  `driver_id` bigint(20) unsigned NOT NULL,
  `status` enum('Assigned','Pending','In Progress','Completed','Cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Assigned',
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `safari_allocations_proforma_invoice_id_foreign` (`proforma_invoice_id`),
  KEY `safari_allocations_lead_id_index` (`lead_id`),
  KEY `safari_allocations_vehicle_id_index` (`vehicle_id`),
  KEY `safari_allocations_driver_id_index` (`driver_id`),
  KEY `safari_allocations_status_index` (`status`),
  CONSTRAINT `safari_allocations_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `safari_allocations_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `safari_allocations_proforma_invoice_id_foreign` FOREIGN KEY (`proforma_invoice_id`) REFERENCES `proforma_invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `safari_allocations_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `safari_allocations`
--

LOCK TABLES `safari_allocations` WRITE;
/*!40000 ALTER TABLE `safari_allocations` DISABLE KEYS */;
INSERT INTO `safari_allocations` VALUES (1,2,1,1,1,'Assigned',NULL,'2026-03-29 06:11:56','2026-03-29 06:11:56'),(2,3,2,1,8,'Assigned','test','2026-03-30 06:19:48','2026-03-30 06:19:48');
/*!40000 ALTER TABLE `safari_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('r28pI9W0Pv4Vpgf92dNeSr9omAC3Z3p6vPAnhxTU',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJCbXVqV1Zya1VManRFeVpWUk9rSDVpYWJCdGVzN1ZmQ0lKYzVveHdnIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1774974057);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('company_address','Sher Leasing HQ, Nyerere Road, Dar es Salaam, Tanzania','2026-03-25 10:29:12','2026-03-25 10:29:12'),('company_email','info@sher.co.tz','2026-03-25 10:29:12','2026-03-25 10:37:15'),('company_name','Sher East Africa  Ltd','2026-03-25 10:29:12','2026-03-25 10:36:32'),('company_phone','+255 754 123 456','2026-03-25 10:29:12','2026-03-25 10:29:12'),('default_currency','TZS','2026-03-25 10:29:12','2026-03-25 10:29:12'),('default_vat','18','2026-03-25 10:29:12','2026-03-25 10:29:12'),('logo','settings/tTk7XkQJfq4hANOFKfjMyF29Gce1n5TX8Nj7VlsX.png','2026-03-25 10:37:15','2026-03-25 10:37:15'),('tax_registration_number','TIN-123-456-789','2026-03-25 10:29:12','2026-03-25 10:29:12');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transport_rates`
--

DROP TABLE IF EXISTS `transport_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transport_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `particular` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate` decimal(10,2) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transport_rates_particular_unique` (`particular`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transport_rates`
--

LOCK TABLES `transport_rates` WRITE;
/*!40000 ALTER TABLE `transport_rates` DISABLE KEYS */;
INSERT INTO `transport_rates` VALUES (1,'Per vehicle per day net ΓÇô 3 days or less',350.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(2,'Per vehicle per day net ΓÇô 4 days or more',235.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(3,'Per vehicle per day net ΓÇô A/C Vehicle Supplement',50.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(4,'Crater Entry fee ΓÇô per vehicle net',262.50,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(5,'Additional for driving to North Serengeti ΓÇô per vehicle',150.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(6,'Additional for driving to West Serengeti',100.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(7,'Empty leg from Serengeti to Arusha or Arusha to Serengeti',120.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(8,'Pick up Isabania Boarder from Serengeti',300.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(9,'Pick up Namanga border ΓÇô from Arusha',120.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(10,'Bank Charges',50.00,'2026-04-02 04:44:02','2026-04-02 04:44:02'),(11,'Arusha Kilimanjaro Pickup',60.00,'2026-04-02 05:21:20','2026-04-02 05:21:20');
/*!40000 ALTER TABLE `transport_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Inactive',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin User','admin@sher.local','+255 700 000 001','Admin','Active',NULL,'$2y$12$2Cz9Zu14F4szeFoelKqCCOuLbVplBhbVCSEYtESx/sx7elMtgQUCS',NULL,'2026-03-25 08:20:48','2026-03-30 04:02:00'),(2,'Staff User','staff@sher.local','+255 700 000 002','Admin','Active',NULL,'$2y$12$g0LJGve77hrKiZCxc2AqR.65Q4cUJkaaru5a/CmSuwKaKiOe.hTEi',NULL,'2026-03-25 08:20:48','2026-03-30 04:01:45'),(8,'David Kavishe','technogurudigitalsystems@gmail.com','0711291714','Driver','Active',NULL,'$2y$12$XtPFCPb9DZhvSfbxQh1At.zlvIsrDKfCMOXa0DUDjDtHMjSCfAzqO',NULL,'2026-03-30 04:03:39','2026-03-30 04:03:39');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vehicles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plate_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `make` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `seats` tinyint(3) unsigned NOT NULL,
  `chassis` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Available',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicles_vehicle_no_unique` (`vehicle_no`),
  UNIQUE KEY `vehicles_plate_no_unique` (`plate_no`),
  UNIQUE KEY `vehicles_chassis_unique` (`chassis`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicles`
--

LOCK TABLES `vehicles` WRITE;
/*!40000 ALTER TABLE `vehicles` DISABLE KEYS */;
INSERT INTO `vehicles` VALUES (1,'001','T 001 KMZ','Totota','Land Cruiser',2026,7,'JKLMHJKLL','Available','2026-03-28 07:48:11','2026-03-28 07:48:11');
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-18 14:42:17
