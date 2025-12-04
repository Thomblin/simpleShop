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
) ENGINE = InnoDB AUTO_INCREMENT = 7 DEFAULT CHARSET = utf8 COLLATE = utf8_bin;
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
) ENGINE = InnoDB AUTO_INCREMENT = 7 DEFAULT CHARSET = utf8 COLLATE = utf8_bin;
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
        'Small Pack (250g)',
        12.99,
        1,
        5,
        49
    ),
    (
        2,
        1,
        'Medium Pack (500g)',
        22.99,
        1,
        3,
        28
    ),
    (
        3,
        1,
        'Large Pack (1kg)',
        39.99,
        1,
        2,
        13
    ),
    (
        4,
        2,
        'Dark Chocolate (70% cocoa)',
        5.99,
        1,
        10,
        99
    ),
    (
        5,
        2,
        'Milk Chocolate (40% cocoa)',
        5.99,
        1,
        10,
        77
    ),
    (
        6,
        2,
        'White Chocolate',
        5.99,
        1,
        10,
        60
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
) ENGINE = InnoDB AUTO_INCREMENT = 3 DEFAULT CHARSET = utf8 COLLATE = utf8_bin;
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
        'Organic Coffee Beans',
        'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=400',
        'Premium arabica coffee beans from sustainable farms',
        5.00
    ),
    (
        2,
        'Artisan Chocolate Bar',
        'https://images.unsplash.com/photo-1549007994-cb92caebd54b?w=400',
        'Handcrafted chocolate with premium cocoa',
        3.00
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
INSERT INTO `option_groups` VALUES (1, 'Size', 1), (2, 'Color', 2);
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
) ENGINE = InnoDB AUTO_INCREMENT = 7 DEFAULT CHARSET = utf8 COLLATE = utf8_bin;
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
        'Small',
        1,
        'Small Pack (250g)'
    ),
    (
        2,
        1,
        'Medium',
        2,
        'Medium Pack (500g)'
    ),
    (
        3,
        1,
        'Large',
        3,
        'Large Pack (1kg)'
    ),
    (
        4,
        2,
        'Dark',
        1,
        'Dark Chocolate (70% cocoa)'
    ),
    (
        5,
        2,
        'Milk',
        2,
        'Milk Chocolate (40% cocoa)'
    ),
    (
        6,
        2,
        'White',
        3,
        'White Chocolate'
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

-- Dump completed on 2025-12-02 20:40:29