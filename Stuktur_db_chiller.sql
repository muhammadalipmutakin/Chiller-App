/*
SQLyog Community
MySQL - 8.0.36 : Database - chiller-app
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`chiller-app` /*!40100 DEFAULT CHARACTER SET utf8mb3 */ /*!80016 DEFAULT ENCRYPTION='N' */;

/*Table structure for table `adjust_tmp` */

DROP TABLE IF EXISTS `adjust_tmp`;

CREATE TABLE `adjust_tmp` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_input` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `adjustment_date` date NOT NULL,
  `storage` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `adjustment_type` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `row_unique_id` bigint NOT NULL,
  `id_in` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `barcode` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `item` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `subitem` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ekor_stock_current` decimal(10,2) DEFAULT NULL,
  `berat_stock_current` decimal(10,2) DEFAULT NULL,
  `ekor_new` decimal(10,2) DEFAULT NULL,
  `berat_new` decimal(10,2) DEFAULT NULL,
  `keterangan` text COLLATE utf8mb4_general_ci,
  `is_edited` tinyint(1) DEFAULT '0',
  `is_details_collapsed` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6775 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `items` */

DROP TABLE IF EXISTS `items`;

CREATE TABLE `items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sku` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `item` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `kategory` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `isActive` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`)
) ENGINE=InnoDB AUTO_INCREMENT=5132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `kategori_laporan` */

DROP TABLE IF EXISTS `kategori_laporan`;

CREATE TABLE `kategori_laporan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `kategori` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=234 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `locations` */

DROP TABLE IF EXISTS `locations`;

CREATE TABLE `locations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lokasi` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `log_activity` */

DROP TABLE IF EXISTS `log_activity`;

CREATE TABLE `log_activity` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_user` int DEFAULT NULL,
  `action_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `table_name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `data_old` text COLLATE utf8mb4_general_ci,
  `data_new` text COLLATE utf8mb4_general_ci,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15551 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `log_download` */

DROP TABLE IF EXISTS `log_download`;

CREATE TABLE `log_download` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jenis_dokumen` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=123 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `pending_transactions` */

DROP TABLE IF EXISTS `pending_transactions`;

CREATE TABLE `pending_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `barcode_out` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `barcode_in` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_in` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date` date DEFAULT NULL,
  `from_location` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `to_location` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `item` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subitem` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ekor_pack` int DEFAULT NULL,
  `quantity_kg` decimal(10,2) DEFAULT NULL,
  `keterangan` text COLLATE utf8mb4_general_ci,
  `user` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `pending_count` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `reason_adjustment` */

DROP TABLE IF EXISTS `reason_adjustment`;

CREATE TABLE `reason_adjustment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_transaction` int DEFAULT NULL,
  `ekor_pack` int DEFAULT NULL,
  `quantity_kg` decimal(10,2) DEFAULT NULL,
  `reason` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `id_user` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `so_hasil` */

DROP TABLE IF EXISTS `so_hasil`;

CREATE TABLE `so_hasil` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `storage` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ekor_pack` int DEFAULT '0',
  `quantity_kg` decimal(10,2) DEFAULT '0.00',
  `selisih_ekor_pack` int DEFAULT NULL,
  `selisih_quantity_kg` decimal(10,2) DEFAULT NULL,
  `keterangan` text COLLATE utf8mb4_general_ci,
  `opname_date` date NOT NULL,
  `user_hitung` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `user_proses` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `so_hitung` */

DROP TABLE IF EXISTS `so_hitung`;

CREATE TABLE `so_hitung` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `storage` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ekor_pack` int DEFAULT '0',
  `quantity_kg` decimal(10,2) DEFAULT '0.00',
  `opname_date` date NOT NULL,
  `user_hitung` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `so_stock_awal` */

DROP TABLE IF EXISTS `so_stock_awal`;

CREATE TABLE `so_stock_awal` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `storage` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ekor_pack` int DEFAULT '0',
  `quantity_kg` decimal(10,2) DEFAULT '0.00',
  `opname_date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `subitems` */

DROP TABLE IF EXISTS `subitems`;

CREATE TABLE `subitems` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subitem` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=241 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `transactions` */

DROP TABLE IF EXISTS `transactions`;

CREATE TABLE `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` date DEFAULT NULL,
  `from_location` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `to_location` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `storage` enum('Chiller 1','Chiller 2','Chiller 3') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `item` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ekor_pack` int DEFAULT NULL,
  `quantity_kg` decimal(10,2) NOT NULL,
  `transaction_type` enum('adjustment','adjustment-new','gabung-in','gabung-new','gabung-out','in','other','out','transfer-in','transfer-out') COLLATE utf8mb4_general_ci NOT NULL,
  `keterangan` text COLLATE utf8mb4_general_ci,
  `user` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `barcode` int DEFAULT NULL,
  `id_in` int DEFAULT NULL,
  `adjustment_type` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_id_in` (`id_in`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_barcode` (`barcode`),
  KEY `idx_tanggal` (`date`),
  KEY `idx_storage` (`storage`),
  KEY `idx_item` (`item`)
) ENGINE=InnoDB AUTO_INCREMENT=277173 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `users` */

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('superadmin','admin','staff') COLLATE utf8mb4_general_ci DEFAULT 'staff',
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `photo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
