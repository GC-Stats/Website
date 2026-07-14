
# 💻 GC Stats Website

The official website for **GC Stats**, providing a clean view of matches, teams, players, tournaments, and all our data

---

| Build Status |                                        Latest Version                                        |                                                                       Status                                                                        | PHP Version |
|:---:|:--------------------------------------------------------------------------------------------:|:---------------------------------------------------------------------------------------------------------------------------------------------------:|:---:|
| [![CI/CD Pipeline](https://github.com/GC-Stats/Website/actions/workflows/main.yml/badge.svg)](https://github.com/GC-Stats/Website/actions/workflows/main.yml) | ![GitHub release](https://img.shields.io/github/v/release/GC-Stats/website) | [![Better Stack Badge](https://uptime.betterstack.com/status-badges/v1/monitor/2m8ir.svg)](https://uptime.betterstack.com/?utm_source=status_badge) | 8.4
[![Translation](http://translate.gc-stats.app/widget/website/svg-badge.svg)](http://translate.gc-stats.app/engage/website/)
---

## 📋 Presentation
This repository contains the main component of GC Stats, our website

It's designed to give you the most informations as possible, in a simple & comprehensive way

## 🤝 License
This project is licensed under the [GC-Stats License v1.0](./LICENSE.md).

## 🛠 Tech Stack

- **Framework:** [Laravel 13+](https://laravel.com)
- **Styling:** [Tailwind CSS](https://tailwindcss.com)
- **Database:** MySQL 8.0+ / MariaDB 10.11+
- **Cache & Queue:** Redis
- **Frontend:** Blade & Alpine.js
- **Valorant API**: Riot Games Official API

> [!CAUTION]
> **Compatibility Warning:** This project is **not compatible with SQLite**. We use MySQL-specific JSON operations and complex relationship grouping that SQLite does not support.

## ⚙️ Installation

### Option 1: Docker (Laravel Sail) - Recommended
The easiest way to get started without installing PHP or MySQL locally.

1. **Clone the repo:**
   ```bash
   git clone https://github.com/GC-Stats/Website.git
   cd Website
   ```
2. **Copy .env**
   ```bash
   cp .env.example .env
   ```
   Edit the files, and set your own variable

> [!CAUTION]
> You need to deploy your own Redis & Database (MariaDb or MySQL)


3. **Launch it via Docker**
   ```bash
   docker compose up -d
   ```

4. **Clear cache and make the migration**
   ```bash
    docker compose exec -T gc_production_app php artisan config:cache
    docker compose exec -T gc_production_app php artisan route:cache
    docker compose exec -T gc_production_app php artisan view:cache
    docker compose exec -T gc_production_app php artisan migrate --force
   ```

### Option 2: Manual Installation (From Source)
1. **Requirements:** PHP 8.4+, Composer, Node.js, MySQL, Redis.
2. **Commands:**
   ```bash
   composer install
   npm install
   cp .env.example .env
   php artisan key:generate
   php artisan migrate
   npm run build
   php artisan serve
   ```

---

## 🧪 Testing
We use Pest for our testing suite. To run the tests:
```bash
php artisan test
```

## 🤝 Contributing
Interested in helping? Please refer to our [CONTRIBUTING.md](https://github.com/GC-Stats/Website/blob/main/CONTRIBUTING.md) for guidelines on how to submit pull requests.
