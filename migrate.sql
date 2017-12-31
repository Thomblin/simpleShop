CREATE TABLE IF NOT EXISTS `items` (
    `item_id` INT UNSIGNED AUTO_INCREMENT,
    `name` VARCHAR(256) NOT NULL COMMENT "title",
    `picture` TEXT DEFAULT '' COMMENT "absolute URL to product image",
    `description` TEXT DEFAULT '' COMMENT "description",
    `min_porto` DECIMAL(10, 2) DEFAULT 0 COMMENT "minimum porto to be paid for this item",
    PRIMARY KEY (`item_id`)
) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 COLLATE = utf8_bin;

CREATE TABLE IF NOT EXISTS `bundles` (
    `bundle_id` INT UNSIGNED AUTO_INCREMENT,
    `item_id` INT UNSIGNED NOT NULL COMMENT "items.id",
    `name` VARCHAR(256) NOT NULL COMMENT "subtitle",
    `price` DECIMAL(10, 2) DEFAULT 0 COMMENT "price per item",
    `min_count` INT DEFAULT 0 COMMENT "min amount to buy",
    `max_count` INT DEFAULT 1  COMMENT "max amount to buy",
    PRIMARY KEY (`bundle_id`),
    KEY (`item_id`),
    CONSTRAINT `fk_item_id` FOREIGN KEY `fk_item_id` (`item_id`) REFERENCES `items` (`item_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 COLLATE = utf8_bin;

