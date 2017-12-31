# simpleShop

## features

- display all items on one page
- each item consists of several bundles, id, name, picture, description, minimum porto
- possibility to add multiple bundles to every item (like different colours or sizes)
- each bundle contains own prices and amount
- configuration and stock management in mysql
- order preview
- email to customer and provider is send, if someone confirms an order
- current price and porto is displayed on bottom, after each (de)selection of an item
- translations available (currently de, en)

## Install

- Create a mysql-Database
- Execute sql statements in migrate.sql
- Add items and bundles into mysql tables
- Change lib/config.php to fit your needs
- Change translations/*.php to fit your needs