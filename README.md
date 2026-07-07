# 🚀 Mini Order Processing & E-Commerce API

<p align="center">

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Redis](https://img.shields.io/badge/Redis-Docker-DC382D?style=for-the-badge&logo=redis&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Alpine-2496ED?style=for-the-badge&logo=docker&logoColor=white)

</p>

---

## 📖 Overview

A **high-performance**, **asynchronous** Mini E-Commerce Order API built using **Laravel 12**.

This project demonstrates production-level backend architecture including:

- ⚡ Global Redis Memory Caching
- 🔄 Asynchronous Queue Processing
- 🐳 Dockerized Redis
- 🖥️ Hybrid Windows + Linux Virtualization
- 🔒 Database Transactions
- 📦 Background Order Processing
- 📧 Queue-based Email Notifications

---

# 🏗️ System Architecture

```
                        Client
                           │
                           ▼
                 Laravel 12 REST API
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
        ▼                  ▼                  ▼
     MySQL             Redis Cache       Redis Queue
        │                  │                  │
        └──────────────────┼──────────────────┘
                           │
                           ▼
                  Queue Worker (Laravel)
                           │
                           ▼
                     Mailtrap SMTP
```

---

# 🚀 Architectural Decisions (Senior Level)

## 1️⃣ Global Product Cache

### Problem

If every user receives their own cache:

```
User A -> products_page_1
User B -> products_page_1
User C -> products_page_1
```

Redis memory gets duplicated unnecessarily.

---

### Solution

A **Global Cache Strategy**

```
products_page_1
products_page_2
products_page_search_laptop
```

Every user shares the same cache.

### ✅ Benefits

- Very High Cache Hit Ratio
- Lower Memory Consumption
- Faster API Responses
- Prevents Redis OOM

---

## 2️⃣ Smart Redis Cache Eviction

### ❌ Traditional Approach

```php
Cache::flush();
```

This removes **everything**:

- Sessions
- Auth Tokens
- Config Cache
- Product Cache

---

### ✅ Implemented Approach

Pattern-based cache removal:

```
products_page_*
```

Only Product Cache is removed.

Everything else remains untouched.

### Benefits

- No Session Loss
- No Token Loss
- Safer Production Deployment

---

## 3️⃣ Hybrid Virtualization Architecture

The project intentionally separates infrastructure.

```
Windows Host
│
├── PHP
├── Laravel
├── Composer
└── MySQL

            │

            ▼

Ubuntu Virtual Machine
│
└── Docker

        │

        ▼

Redis Alpine Container
```

---

### Networking

VirtualBox

```
Bridged Adapter
```

Host Windows and Ubuntu VM stay inside the same LAN.

Example

```
Windows Host
192.168.1.20

Ubuntu VM
192.168.1.15
```

Laravel directly connects to Redis through LAN.

---

## 4️⃣ Redis Client Optimization

Instead of using

```
phpredis
```

the application uses

```
predis
```

### Why?

Windows often throws

```
Class "Redis" not found
```

Predis is pure PHP and works consistently across operating systems.

---

## 5️⃣ Transaction Safe Queue Dispatch

### Wrong Flow

```
BEGIN TRANSACTION

Create Order

Dispatch Job

COMMIT
```

Possible race condition:

```
Worker
↓

Reads Order

↓

Order doesn't exist yet

↓

ModelNotFoundException
```

---

### Correct Flow

```
BEGIN TRANSACTION

↓

Create Order

↓

COMMIT

↓

Dispatch Queue
```

This guarantees:

- Data Consistency
- Zero Race Conditions
- Reliable Queue Processing

---

# ⚙️ Local Development Setup

## 📋 Prerequisites

- PHP 8.2+
- Composer
- MySQL
- Oracle VirtualBox
- Ubuntu VM
- Docker

---

# 🐳 Redis Setup

You can run Redis in **two different ways** depending on your development environment.

---

# Option 1 — Redis in Local Docker (Recommended)

If Docker is installed on your local machine (Windows/Linux/macOS), run Redis using the following command:

```bash
docker run -d \
--name redis-local \
-p 6379:6379 \
redis:alpine \
redis-server --requirepass "your_secure_password"
```

Verify that the container is running:

```bash
docker ps
```

Example output:

```text
CONTAINER ID   IMAGE          PORTS
xxxxxxxxxxxx   redis:alpine   0.0.0.0:6379->6379/tcp
```

Configure your Laravel `.env` file:

```env
QUEUE_CONNECTION=redis
CACHE_STORE=redis

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=your_secure_password

REDIS_DB=0
REDIS_CACHE_DB=1
```

> **Note**
>
> If Laravel and Redis are running on the same machine, use:
>
> ```
> REDIS_HOST=127.0.0.1
> ```

---

# Option 2 — Redis in Ubuntu VM (Docker)

If you're using **Oracle VM VirtualBox** with an Ubuntu virtual machine, ensure the VM network is configured as **Bridged Adapter**.

Run Redis inside Docker:

```bash
docker run -d \
--name redis-local \
-p 6379:6379 \
redis:alpine \
redis-server --requirepass "your_secure_password"
```

Find your Ubuntu VM IP address:

```bash
hostname -I
```

Example:

```text
192.168.1.15
```

Update Laravel's `.env` file on the Windows host:

```env
QUEUE_CONNECTION=redis
CACHE_STORE=redis

REDIS_CLIENT=predis
REDIS_HOST=192.168.1.15
REDIS_PORT=6379
REDIS_PASSWORD=your_secure_password

REDIS_DB=0
REDIS_CACHE_DB=1
```

> **Note**
>
> Replace `192.168.1.15` with the IP address of your Ubuntu VM.
>
> Make sure both the Windows host and Ubuntu VM are connected to the same network through the **Bridged Adapter**.

# 💻 Step 2 — Install Laravel

```bash
composer install
```

```bash
cp .env.example .env
```

```bash
php artisan key:generate
```

---

# ⚙️ Step 3 — Configure Environment

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mini_order_db
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=redis
CACHE_STORE=redis

REDIS_CLIENT=predis
REDIS_HOST=192.168.1.15
REDIS_PASSWORD=your_secure_password
REDIS_PORT=6379

REDIS_DB=0
REDIS_CACHE_DB=1

MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```

---

# 🗄️ Step 4 — Database

```bash
php artisan migrate --seed
```

---

# ▶️ Step 5 — Start Services

### Terminal 1

```bash
php artisan serve
```

---

### Terminal 2

```bash
php artisan queue:work
```

---

# 🧪 Redis Debugging

Enter Redis

```bash
docker exec -it redis-local redis-cli
```

Authenticate

```bash
AUTH your_secure_password
```

Select Cache Database

```bash
SELECT 1
```

List Cache Keys

```bash
KEYS *
```

Expected

```
laravel_database_laravel_cache:products_page_1
```

---

# 📧 Queue Verification

Call

```
POST /api/v1/orders
```

Expected Flow

```
Client

↓

Laravel API

↓

Database Transaction

↓

Commit

↓

Dispatch Queue

↓

Redis Queue

↓

Queue Worker

↓

Mailtrap
```

Worker Output

```
Processing: App\Jobs\ProcessOrderJob

Processed
```

Mailtrap

✅ HTML Invoice

- Customer Details
- Order Items
- Total Amount
- Dynamic Invoice Table

---

# 📈 Performance Highlights

| Feature | Benefit |
|----------|----------|
| Global Redis Cache | Higher Cache Hit Rate |
| Tag-based Cache Removal | No Full Redis Flush |
| Queue Workers | Non-blocking API |
| Transactions | Consistent Database |
| Docker Redis | Lightweight Infrastructure |
| Predis Driver | Cross-platform Compatibility |
| Hybrid VM Networking | Production-like Environment |

---

# 📂 Project Structure

```
app/
 ├── Jobs/
 ├── Helpers/
 ├── Mail/
 ├── Models/
 ├── Http/
 │    ├── Controllers/
 │    └── Requests/
 └── Services/

routes/

database/

resources/views/emails/
```

---

# 🚀 Technologies Used

- Laravel 12
- PHP 8.2+
- Redis
- Docker
- Ubuntu
- VirtualBox
- MySQL
- Mailtrap
- Composer
- Predis

---

# ⭐ Key Takeaways

✅ High Performance

✅ Production-ready Caching

✅ Asynchronous Queue Processing

✅ Dockerized Redis

✅ Cross-OS Networking

✅ Memory Efficient

✅ Transaction Safe

✅ Scalable Architecture

---

<p align="center">

### ⭐ If you found this project useful, consider giving it a Star!

Built with ❤️ using Laravel 12

</p>