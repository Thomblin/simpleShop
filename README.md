# simpleShop

A lightweight, self-hosted e‑commerce shop for small businesses and side projects. simpleShop focuses on the essentials: managing products, bundles, and orders in a straightforward way without the complexity of large platforms. It’s easy to set up, developer-friendly, and ready to run in a few minutes with MySQL and PHP.

## features

- Modern, mobile‑first responsive UI with card‑based product grid
- All products visible with clear images, descriptions, and pricing
- Flexible product options (e.g. size, color, grind type) per item
- Rich product data: ID, name, image, description, shipping tier, price, stock level
- Bundles that combine multiple items with their own pricing and quantities
- Centralized configuration and inventory management in MySQL
- Basket supports multiple products and bundles with different options
- Real‑time basket updates for totals, shipping costs, and item counts
- Order preview before confirmation with full cost breakdown
- Automatic order emails sent to both customer and shop owner
- Multi‑language support (currently German and English, easily extensible)
- Docker‑ready setup and comprehensive automated tests for reliability

## Install
1. Start the environment (recommended)
   - Install Docker and Docker Compose
   - From the project root, run:
     - `make up` – start PHP (shop) and MySQL containers
     - `make logs` – follow container logs (optional)

2. Initialize the database
   - Ensure the `shop_mysql` (or `test_mysql` for testing) service is running:
     - `docker compose up -d shop_mysql`
   - From the project root, import the schema and/or sample data:
     - Import `migrate.sql` into the main shop database:
       - `docker compose exec -T shop_mysql sh -c "mysql -uuser -puser shop < /var/lib/mysql/migrate.sql"`
     - Or import the demo data (e.g. `example_seed_shop.sql` or `example_seed_langwacken.sql`):
       - `docker compose exec -T shop_mysql sh -c "mysql -uuser -puser shop < /var/lib/mysql/example_seed_shop.sql"`

3. Configure the application
   - Copy `config.php.skeleton` to `config.php`:
     - `cp config.php.skeleton config.php`
   - Edit `config.php` and adjust all configuration values:
     - Database credentials (host, user, password, database name)
     - Email addresses (shop owner email and display name)
     - Currency symbol
     - Language preference ('de' or 'en')
     - Customer form field requirements
     - Inventory display settings
   - The `config.php` file will be automatically loaded by `lib/Config.php`
   - it already contains the values needed to run with the local docker stack

4. Configure translations (optional)
   - Review files in `translations/*.php`
   - Adjust wording, add new languages, or customize messages as needed

5. Install PHP dependencies
   - Run `make install` (preferred, via Docker)
   - Or inside the PHP container: `docker compose exec -T shop composer install --no-interaction`
   - Or locally (without Docker): `composer install`

6. Run tests (optional but recommended)
   - PHP tests:
     - `make test`
   - PHP Coverage:
     - `make test-coverage`
   - JavaScript tests:
     - `make test-js`
   - JavaScript Coverage:
     - `make test-js-coverage`

7. Access the shop
   - After `make up`, open `http://localhost:11180` in your browser
   - Use your MySQL client to manage products, bundles, and orders as needed

## Examples

- Execute sql statements in example_seed_shop.sql or example_seed_langwacken.sql to create a pre-filled database