# 🧩 Laravel Backend Installation Guide

This guide explains how to set up and run the Laravel backend after cloning the repository.

---

## 🚀 Requirements

Make sure you have the following installed:

- **PHP ≥ 8.2**
- **Composer**
- **MySQL / MariaDB**
- **Node.js & npm** (if using Vite or frontend builds)
- **Git**

---

## 📦 1. Clone the Repository

```bash
git clone https://github.com/AdonisJr/subject-evaluation-be.git
cd subject-evaluation-be

composer install

If you encounter memory issues:
composer install --ignore-platform-reqs

```paste the .env values i sent

create database named "ocr_thesis"

run this after:

php artisan migrate:fresh --seed
php artisan serve