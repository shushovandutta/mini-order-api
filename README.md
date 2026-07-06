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
