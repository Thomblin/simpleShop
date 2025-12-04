--- version 1

CREATE TABLE IF NOT EXISTS `items` (
    `item_id` INT UNSIGNED AUTO_INCREMENT,
    `name` VARCHAR(256) NOT NULL COMMENT "title",
    `picture` TEXT COMMENT "absolute URL to product image",
    `description` TEXT COMMENT "description",
    `min_porto` DECIMAL(10, 2) DEFAULT 0 COMMENT "minimum porto to be paid for this item",
    PRIMARY KEY (`item_id`)
) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 COLLATE = utf8_bin;

CREATE TABLE IF NOT EXISTS `bundles` (
    `bundle_id` INT UNSIGNED AUTO_INCREMENT,
    `item_id` INT UNSIGNED NOT NULL COMMENT "items.id",
    `name` VARCHAR(256) NOT NULL COMMENT "subtitle",
    `price` DECIMAL(10, 2) DEFAULT 0 COMMENT "price per item",
    `min_count` INT DEFAULT 0 COMMENT "min amount to buy",
    `max_count` INT DEFAULT 1 COMMENT "max amount to buy",
    `inventory` INT DEFAULT 0 COMMENT "items left in stock",
    PRIMARY KEY (`bundle_id`),
    KEY (`item_id`),
    CONSTRAINT `fk_item_id` FOREIGN KEY `fk_item_id` (`item_id`) REFERENCES `items` (`item_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 COLLATE = utf8_bin;

--- version 2

-- Add option groups table (defines types like "Size", "Color")
CREATE TABLE IF NOT EXISTS `option_groups` (
    `option_group_id` INT UNSIGNED AUTO_INCREMENT,
    `name` VARCHAR(256) NOT NULL COMMENT "option group name (e.g., Size, Color)",
    `display_order` INT DEFAULT 0 COMMENT "display order for frontend",
    PRIMARY KEY (`option_group_id`)
) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 COLLATE = utf8_bin;

-- Add options table (defines specific values like "Small", "Red")
CREATE TABLE IF NOT EXISTS `options` (
    `option_id` INT UNSIGNED AUTO_INCREMENT,
    `option_group_id` INT UNSIGNED NOT NULL COMMENT "option_groups.option_group_id",
    `name` VARCHAR(256) NOT NULL COMMENT "option value (e.g., Small, Red)",
    `description` TEXT DEFAULT '' COMMENT "option description (e.g., 'Small Pack (250g)')",
    `display_order` INT DEFAULT 0 COMMENT "display order within group",
    PRIMARY KEY (`option_id`),
    KEY (`option_group_id`),
    CONSTRAINT `fk_option_group_id` FOREIGN KEY (`option_group_id`) REFERENCES `option_groups` (`option_group_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 COLLATE = utf8_bin;

-- Add bundle_options junction table (links bundles to their option combinations)
CREATE TABLE IF NOT EXISTS `bundle_options` (
    `bundle_option_id` INT UNSIGNED AUTO_INCREMENT,
    `bundle_id` INT UNSIGNED NOT NULL COMMENT "bundles.bundle_id",
    `option_id` INT UNSIGNED NOT NULL COMMENT "options.option_id",
    `price` DECIMAL(10, 2) DEFAULT NULL COMMENT "override price per item for this bundle+option",
    `min_count` INT DEFAULT NULL COMMENT "override min amount to buy for this bundle+option",
    `max_count` INT DEFAULT NULL COMMENT "override max amount to buy for this bundle+option",
    `inventory` INT DEFAULT NULL COMMENT "inventory for this bundle+option (if NULL, falls back to bundle)",
    PRIMARY KEY (`bundle_option_id`),
    KEY (`bundle_id`),
    KEY (`option_id`),
    CONSTRAINT `fk_bundle_option_bundle_id` FOREIGN KEY (`bundle_id`) REFERENCES `bundles` (`bundle_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_bundle_option_option_id` FOREIGN KEY (`option_id`) REFERENCES `options` (`option_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 COLLATE = utf8_bin;