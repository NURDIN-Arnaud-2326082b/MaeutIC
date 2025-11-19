# M@ieutIC

## Introduction
La plateforme facilitant la discussion entre doctorants !

## Requirements
Ensure you have the following dependencies installed:
- PHP 8.1 or higher
- Composer
- Symfony CLI (optional but recommended)
- MySQL

## Installation
1. **Clone the repository**:
   ```sh
   git clone https://github.com/NURDIN-Arnaud-2326082b/MaeutIC.git
   cd your-symfony-project
   ```

2. **Install PHP dependencies**:
   ```sh
   composer install
   ```

4. **Set up environment variables**:
   ```sh
   cp .env.exemple .env
   ```
   Update the `.env` file with your database credentials.

5. **Database setup**:
   Create the database and run migrations:
   ```sh
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```
6. **Load fixtures**:

   ```sh
   php bin/console doctrine:fixtures:load
   ```

7. **Compile CSS**:
   ```sh
   php bin/console tailwind:build --watch
   ```


