# Mini Order Processing & E-Commerce API

A high-performance, asynchronous Mini E-Commerce Order API built with **Laravel 12**. This project demonstrates advanced backend architecture, including global memory caching, multi-OS hybrid virtualization networking, database consistency through transactions, and decoupled background worker queues using **Dockerized Redis**.

---

## 🚀 Architectural & R&D Features (Seniors Perspective)

During the development of this project, several architectural decisions were researched and implemented to ensure the system is production-ready, highly scalable, and memory-efficient:

### 1. Global Page Cache vs. User-Specific Cache
* **The Approach:** Product listings and search filters are cached globally (`products_page_*`) rather than uniquely per user ID.
* **The Rationale:** Since product data (names, prices, stocks) is identical for all users, generating unique caches per user would exponentially duplicate data, leading to **Redis Out-Of-Memory (OOM)** errors. 
* **The Benefit:** High *Cache Hit Rate*. If User A searches for a product, the DB is hit once. If User B executes the exact search a second later, they receive a response in **< 15ms** directly from Redis without touching the MySQL database.

### 2. Tailored Redis Cache Eviction (No Cache-Flushing)
* **The Approach:** Instead of using a destructive `Cache::flush()`, a selective tag-based eviction mechanism was implemented inside the `clearProductCache()` helper method.
* **The Rationale:** `Cache::flush()` completely wipes out the entire Redis database, erasing other crucial application data like active user sessions, configuration parameters, or temporary auth tokens.
* **The Benefit:** Upon product creation/updates, the app uses a Redis connection to dynamically scan and destroy *only* key patterns matching `*products_page_*`.

### 3. Multi-OS Hybrid Virtualization (Windows Host to Linux VM via Docker)
* **The Approach:** The application runs on the Windows host machine, while the Redis Server runs inside an **Alpine Docker Container** hosted on an **Ubuntu Linux Virtual Machine (Oracle VM VirtualBox)**.
* **Networking Strategy:** Network bridging was configured (`Bridged Adapter`) to route cross-OS traffic, ensuring the host and the VM belong to the same local subnet. 
* **Client Driver Optimization:** To seamlessly connect the Windows PHP engine with the Linux Docker Redis without relying on the native Windows C-extension (`phpredis`), the application uses the pure-PHP **`predis`** driver config, eliminating `Class "Redis" not found` bottlenecks.

### 4. Database Transaction Isolation & Decoupled Queue Execution
* **The Approach:** Order placement is guarded by `DB::transaction()`. However, the background worker (`ProcessOrderJob::dispatch($order)`) is dispatched strictly **after** a successful `DB::commit()`.
* **The Rationale:** Dispatching background jobs inside a transaction creates a race condition. If the Redis worker picks up the job faster than MySQL can finalize the commit, the worker will fail with a `ModelNotFoundException`. 
* **The Benefit:** Guarantees absolute data consistency while leveraging asynchronous task delegating for long-running email operations.

---

## 🛠️ Project Setup Instructions

Follow these steps to configure and run the application locally in a hybrid environment (Windows Host + Ubuntu VM Docker).

### Prerequisites
* PHP 8.2+ installed on Windows (WAMP/XAMPP)
* Composer installed on Windows
* Oracle VM VirtualBox running an Ubuntu Instance with Docker installed.

### Step 1: Ubuntu VM Docker Setup
1. Open your Ubuntu VM Terminal.
2. Ensure your Virtual Machine Network is set to **Bridged Adapter** in VirtualBox Settings.
3. Run the Redis container using the official alpine image with password protection:
   ```bash
   docker run -d --name redis-local -p 6379:6379 redis:alpine --requirepass "your_secure_password"


Find the local IP address of your Ubuntu VM by running the following command in your Ubuntu Terminal:

Bash
hostname -I
(Note down this IP, e.g., 192.168.1.15)

Step 2: Clone & Local Installation (Windows Host)
Navigate to your project directory in the Windows terminal:

Bash
cd mini-order-api
composer install
Copy the environment configuration file:

Bash
cp .env.example .env
Generate the application key:

Bash
php artisan key:generate
Step 3: Configure Environment Variables (.env)
Open the .env file on your Windows host and set up the Database, Redis VM IP, and Queue Drivers:

Code snippet
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mini_order_db
DB_USERNAME=root
DB_PASSWORD=

# Redis Config (Points to Ubuntu VM Docker)
QUEUE_CONNECTION=redis
CACHE_STORE=redis

REDIS_CLIENT=predis
REDIS_HOST=192.168.1.15  # <--- Replace with your Ubuntu VM IP
REDIS_PASSWORD=your_secure_password # <--- Match your Docker password
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1         # <--- Laravel Cache utilizes DB Index 1

# Mail Server Config (Mailtrap/SMTP)
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
Step 4: Run Migrations & Seeders
Run migrations to generate database tables along with mock products and users data:

Bash
php artisan migrate --seed
Step 5: Start Application Services
To run the complete asynchronous flow, open two separate Windows command line terminals:

Terminal 1 (Serve Application):

Bash
php artisan serve
Terminal 2 (Process Background Queues):

Bash
php artisan queue:work
🧪 Verification & Testing (How to Debug Redis)
1. Step-by-Step Guide to Check Cache in Redis CLI
To verify that Laravel is writing the cache data inside the isolated Ubuntu Docker environment, follow these steps:

Open your Ubuntu VM Terminal and connect to the running container's Redis CLI:

Bash
docker exec -it redis-local redis-cli
Authenticate securely inside the CLI to avoid password command-line warnings:

Plaintext
127.0.0.1:6379> auth your_secure_password
Switch to Database Index 1 (Since Laravel's Cache engine explicitly targets index 1 as configured in REDIS_CACHE_DB):

Plaintext
127.0.0.1:6379> select 1
Fetch all active cache keys:

Plaintext
127.0.0.1:6379> keys *
Expected Output after hitting GET /api/v1/products:

laravel_database_laravel_cache:products_page_1

2. Verification of Background Mail Queue
Place an order via the Checkout API (POST /api/v1/orders).

The API response returns instantly (~20-50ms), without being blocked by network mail latencies.

Watch Terminal 2 (Queue Worker) on Windows; you will instantly see Processing: App\Jobs\ProcessOrderJob followed by Processed.

Check your Mailtrap Inbox to find the elegantly structured HTML invoice email containing dynamic item tables.