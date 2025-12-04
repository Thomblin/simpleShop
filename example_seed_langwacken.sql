-- MySQL dump 10.13  Distrib 5.5.62, for linux-glibc2.12 (x86_64)
--
-- Host: localhost    Database: shop
-- ------------------------------------------------------
-- Server version	5.5.62

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */
;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */
;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */
;
/*!40101 SET NAMES utf8 */
;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */
;
/*!40103 SET TIME_ZONE='+00:00' */
;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */
;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */
;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */
;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */
;

--
-- Table structure for table `bundle_options`
--

DROP TABLE IF EXISTS `bundle_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */
;
/*!40101 SET character_set_client = utf8 */
;
CREATE TABLE `bundle_options` (
    `bundle_option_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `bundle_id` int(10) unsigned NOT NULL COMMENT 'bundles.bundle_id',
    `option_id` int(10) unsigned NOT NULL COMMENT 'options.option_id',
    `price` decimal(10, 2) DEFAULT NULL COMMENT 'override price per item for this bundle+option',
    `min_count` int(11) DEFAULT NULL COMMENT 'override min amount to buy for this bundle+option',
    `max_count` int(11) DEFAULT NULL COMMENT 'override max amount to buy for this bundle+option',
    `inventory` int(11) DEFAULT NULL COMMENT 'inventory for this bundle+option (if NULL, falls back to bundle)',
    PRIMARY KEY (`bundle_option_id`),
    KEY `bundle_id` (`bundle_id`),
    KEY `option_id` (`option_id`),
    CONSTRAINT `fk_bundle_option_bundle_id` FOREIGN KEY (`bundle_id`) REFERENCES `bundles` (`bundle_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_bundle_option_option_id` FOREIGN KEY (`option_id`) REFERENCES `options` (`option_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 20 DEFAULT CHARSET = utf8 COLLATE = utf8_bin;
/*!40101 SET character_set_client = @saved_cs_client */
;

--
-- Dumping data for table `bundle_options`
--

LOCK TABLES `bundle_options` WRITE;
/*!40000 ALTER TABLE `bundle_options` DISABLE KEYS */
;
INSERT INTO
    `bundle_options` (
        `bundle_option_id`,
        `bundle_id`,
        `option_id`,
        `price`,
        `min_count`,
        `max_count`,
        `inventory`
    )
VALUES (
        1,
        1,
        1,
        NULL,
        NULL,
        NULL,
        NULL
    ),
    (
        2,
        2,
        2,
        NULL,
        NULL,
        NULL,
        NULL
    ),
    (
        3,
        3,
        3,
        NULL,
        NULL,
        NULL,
        NULL
    ),
    (
        4,
        4,
        4,
        NULL,
        NULL,
        NULL,
        NULL
    ),
    (
        5,
        5,
        5,
        NULL,
        NULL,
        NULL,
        NULL
    ),
    (
        6,
        6,
        6,
        NULL,
        NULL,
        NULL,
        NULL
    ),
    (
        7,
        7,
        7,
        NULL,
        NULL,
        NULL,
        NULL
    ),
    (
        8,
        8,
        4,
        NULL,
        NULL,
        NULL,
        NULL
    ),
    (
        9,
        9,
        5,
        NULL,
        NULL,
        NULL,
        NULL
    ),
    (
        10,
        10,
        6,
        NULL,
        NULL,
        NULL,
        NULL
    ),
    (
        11,
        11,
        7,
        NULL,
        NULL,
        NULL,
        NULL
    ),
    (
        12,
        12,
        4,
        NULL,
        NULL,
        NULL,
        NULL
    ),
    (
        13,
        13,
        5,
        NULL,
        NULL,
        NULL,
        NULL
    ),
    (
        14,
        14,
        6,
        NULL,
        NULL,
        NULL,
        NULL
    ),
    (
        15,
        15,
        7,
        NULL,
        NULL,
        NULL,
        NULL
    ),
    (
        16,
        16,
        4,
        NULL,
        NULL,
        NULL,
        NULL
    ),
    (
        17,
        17,
        5,
        NULL,
        NULL,
        NULL,
        NULL
    ),
    (
        18,
        18,
        6,
        NULL,
        NULL,
        NULL,
        NULL
    ),
    (
        19,
        19,
        7,
        NULL,
        NULL,
        NULL,
        NULL
    );
/*!40000 ALTER TABLE `bundle_options` ENABLE KEYS */
;
UNLOCK TABLES;

--
-- Table structure for table `bundles`
--

DROP TABLE IF EXISTS `bundles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */
;
/*!40101 SET character_set_client = utf8 */
;
CREATE TABLE `bundles` (
    `bundle_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `item_id` int(10) unsigned NOT NULL COMMENT 'items.id',
    `name` varchar(256) COLLATE utf8_bin NOT NULL COMMENT 'subtitle',
    `price` decimal(10, 2) DEFAULT '0.00' COMMENT 'price per item',
    `min_count` int(11) DEFAULT '0' COMMENT 'min amount to buy',
    `max_count` int(11) DEFAULT '1' COMMENT 'max amount to buy',
    `inventory` int(11) DEFAULT '0' COMMENT 'items left in stock',
    PRIMARY KEY (`bundle_id`),
    KEY `item_id` (`item_id`),
    CONSTRAINT `fk_item_id` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`) ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 20 DEFAULT CHARSET = utf8 COLLATE = utf8_bin;
/*!40101 SET character_set_client = @saved_cs_client */
;

--
-- Dumping data for table `bundles`
--

LOCK TABLES `bundles` WRITE;
/*!40000 ALTER TABLE `bundles` DISABLE KEYS */
;
INSERT INTO
    `bundles`
VALUES (
        1,
        1,
        'Thursday Pass (1 day)',
        50.00,
        1,
        10,
        200
    ),
    (
        2,
        1,
        'Weekend Pass (Friday + Saturday + Sunday)',
        120.00,
        1,
        5,
        150
    ),
    (
        3,
        1,
        'Full Festival Pass (Thursday - Sunday)',
        150.00,
        1,
        5,
        100
    ),
    (
        4,
        2,
        'Size Small',
        25.00,
        1,
        10,
        80
    ),
    (
        5,
        2,
        'Size Medium',
        25.00,
        1,
        10,
        100
    ),
    (
        6,
        2,
        'Size Large',
        25.00,
        1,
        10,
        100
    ),
    (
        7,
        2,
        'Size Extra Large',
        25.00,
        1,
        10,
        60
    ),
    (
        8,
        3,
        'Size Small',
        67.50,
        1,
        5,
        50
    ),
    (
        9,
        3,
        'Size Medium',
        67.50,
        1,
        5,
        60
    ),
    (
        10,
        3,
        'Size Large',
        67.50,
        1,
        5,
        60
    ),
    (
        11,
        3,
        'Size Extra Large',
        67.50,
        1,
        5,
        30
    ),
    (
        12,
        4,
        'Size Small',
        130.50,
        1,
        5,
        40
    ),
    (
        13,
        4,
        'Size Medium',
        130.50,
        1,
        5,
        50
    ),
    (
        14,
        4,
        'Size Large',
        130.50,
        1,
        5,
        50
    ),
    (
        15,
        4,
        'Size Extra Large',
        130.50,
        1,
        5,
        25
    ),
    (
        16,
        5,
        'Size Small',
        157.50,
        1,
        5,
        30
    ),
    (
        17,
        5,
        'Size Medium',
        157.50,
        1,
        5,
        40
    ),
    (
        18,
        5,
        'Size Large',
        157.50,
        1,
        5,
        40
    ),
    (
        19,
        5,
        'Size Extra Large',
        157.50,
        1,
        5,
        20
    );
/*!40000 ALTER TABLE `bundles` ENABLE KEYS */
;
UNLOCK TABLES;

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */
;
/*!40101 SET character_set_client = utf8 */
;
CREATE TABLE `items` (
    `item_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(256) COLLATE utf8_bin NOT NULL COMMENT 'title',
    `picture` text COLLATE utf8_bin COMMENT 'absolute URL to product image',
    `description` text COLLATE utf8_bin COMMENT 'description',
    `min_porto` decimal(10, 2) DEFAULT '0.00' COMMENT 'minimum porto to be paid for this item',
    PRIMARY KEY (`item_id`)
) ENGINE = InnoDB AUTO_INCREMENT = 6 DEFAULT CHARSET = utf8 COLLATE = utf8_bin;
/*!40101 SET character_set_client = @saved_cs_client */
;

--
-- Dumping data for table `items`
--

LOCK TABLES `items` WRITE;
/*!40000 ALTER TABLE `items` DISABLE KEYS */
;
INSERT INTO
    `items`
VALUES (
        1,
        'Langwacken Festival Tickets',
        'https://images.unsplash.com/photo-1459749411175-04bf5292ceea?w=400',
        'Experience the legendary Langwacken Festival - Germany\'s premier rock and metal event!',
        0.00
    ),
    (
        2,
        'Langwacken Fan T-Shirt',
        'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400',
        'Official Langwacken Festival merchandise - premium quality cotton t-shirt',
        0.00
    ),
    (
        3,
        'Thursday Pass + T-Shirt Bundle',
        'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=400',
        'Get your Thursday pass with an official t-shirt at 10% discount!',
        0.00
    ),
    (
        4,
        'Weekend Pass + T-Shirt Bundle',
        'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=400',
        'Weekend pass (Fri-Sun) with official t-shirt - save 10%!',
        0.00
    ),
    (
        5,
        'Full Festival Pass + T-Shirt Bundle',
        'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=400',
        'Complete festival experience with t-shirt - best value with 10% off!',
        0.00
    );
/*!40000 ALTER TABLE `items` ENABLE KEYS */
;
UNLOCK TABLES;

--
-- Table structure for table `option_groups`
--

DROP TABLE IF EXISTS `option_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */
;
/*!40101 SET character_set_client = utf8 */
;
CREATE TABLE `option_groups` (
    `option_group_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(256) COLLATE utf8_bin NOT NULL COMMENT 'option group name (e.g., Size, Color)',
    `display_order` int(11) DEFAULT '0' COMMENT 'display order for frontend',
    PRIMARY KEY (`option_group_id`)
) ENGINE = InnoDB AUTO_INCREMENT = 3 DEFAULT CHARSET = utf8 COLLATE = utf8_bin;
/*!40101 SET character_set_client = @saved_cs_client */
;

--
-- Dumping data for table `option_groups`
--

LOCK TABLES `option_groups` WRITE;
/*!40000 ALTER TABLE `option_groups` DISABLE KEYS */
;
INSERT INTO `option_groups` VALUES (1, 'Pass Type', 1), (2, 'Size', 2);
/*!40000 ALTER TABLE `option_groups` ENABLE KEYS */
;
UNLOCK TABLES;

--
-- Table structure for table `options`
--

DROP TABLE IF EXISTS `options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */
;
/*!40101 SET character_set_client = utf8 */
;
CREATE TABLE `options` (
    `option_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `option_group_id` int(10) unsigned NOT NULL COMMENT 'option_groups.option_group_id',
    `name` varchar(256) COLLATE utf8_bin NOT NULL COMMENT 'option value (e.g., Small, Red)',
    `display_order` int(11) DEFAULT '0' COMMENT 'display order within group',
    `description` text COLLATE utf8_bin COMMENT 'option description (e.g., ''Small Pack (250g)'')',
    PRIMARY KEY (`option_id`),
    KEY `option_group_id` (`option_group_id`),
    CONSTRAINT `fk_option_group_id` FOREIGN KEY (`option_group_id`) REFERENCES `option_groups` (`option_group_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 8 DEFAULT CHARSET = utf8 COLLATE = utf8_bin;
/*!40101 SET character_set_client = @saved_cs_client */
;

--
-- Dumping data for table `options`
--

LOCK TABLES `options` WRITE;
/*!40000 ALTER TABLE `options` DISABLE KEYS */
;
INSERT INTO
    `options`
VALUES (
        1,
        1,
        'Thursday',
        1,
        'Thursday Pass (1 day)'
    ),
    (
        2,
        1,
        'Weekend',
        2,
        'Weekend Pass (Friday + Saturday + Sunday)'
    ),
    (
        3,
        1,
        'All Days',
        3,
        'Full Festival Pass (Thursday - Sunday)'
    ),
    (4, 2, 'S', 1, 'Size Small'),
    (5, 2, 'M', 2, 'Size Medium'),
    (6, 2, 'L', 3, 'Size Large'),
    (
        7,
        2,
        'XL',
        4,
        'Size Extra Large'
    );
/*!40000 ALTER TABLE `options` ENABLE KEYS */
;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */
;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */
;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */
;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */
;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */
;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */
;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */
;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */
;

-- Dump completed on 2025-12-02 20:55:14