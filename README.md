# simpleShop

## features

- display all items on one page
- options like size, color can be added to items
- each item consists of id, name, picture, description, minimum porto, price, inventory (in stock)
- bundles can contain multiple items for different prices
- each bundle contains own prices and amount
- configuration and stock management in mysql
- basket to select multiple bundles with different options (check examples)
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

## Examples

- Execute sql statements in example_seed_shop.sql or example_seed_langwacken.sql to create a pre-filled database