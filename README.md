# 📦 Smart Warehouse Management System (WMS)
### 🏛️ Capital University | CS251: Software Engineering 1 (Spring Semester 2025-2026)

Hey there! Welcome to the repository of our group project for **CS251 Software Engineering 1** at Capital University. 

We developed a production-ready, highly secure **Smart Warehouse Management System (WMS)** using pure PHP with a custom **Model-View-Controller (MVC)** architecture. The system is designed to simulate a modern, high-efficiency warehouse with IoT integration, automated logistics, real-time telemetry, and smart order fulfillment workflows.

---

## 💡 What is this project about?
Modern warehouses face major challenges: stock expiration, slow picking routes, shipping errors, and supply chain bottlenecks. Our WMS addresses these by dividing responsibilities into distinct user roles (Managers, Pickers, Packers, and Suppliers) and automating complex tasks like:
* Automated FEFO (First-Expired, First-Out) stock selection.
* IoT sensor simulations (measuring shelf capacity and weight).
* Smart batch picking algorithms to optimize picker travel time.
* Real-time packing verification and barcode label scanning.
* Dynamic procurement state machines and supplier performance auditing.

---

## ✨ Key Features & Implemented Use Cases
We built this project step-by-step, implementing 20 core use cases (UCs) across the backend:

1. **Inventory Telemetry & Health Dashboard (UC-01 & UC-06):** Real-time monitoring of shelf weight/capacity with dynamic alert triggers.
2. **Zonal Storage & Expiry Watchdog (UC-02, UC-03 & UC-15):** Expiry date tracking using FEFO guidelines, custom manager override capabilities, and automated Cross-Docking to skip storage for urgent orders.
3. **Automated Procurement & PO Flow (UC-05 & UC-16):** Triggering Purchase Orders (POs) automatically when stock drops, complete with PDF generation and Supplier dispatch updates.
4. **Supplier Portal & Analytics (UC-04 & UC-20):** Dedicated portal for suppliers to manage dispatches, choose carriers, and view their Performance Audits.
5. **Smart Batch Picking (UC-10):** Route-optimized picking list for Pickers, grouping items by warehouse zones to minimize footsteps.
6. **Double-Scan Packing Station (UC-11, UC-12 & UC-13):** Dynamic item packing with weight verification, physical box size selection, and barcode label printing/scanning to eliminate shipping errors.
7. **Order State Machine (UC-14):** A robust transaction-locked state machine tracking orders from *Pending* -> *Picking* -> *Packed* -> *Shipped*.
8. **Emergency System Override (UC-07):** A manager-only panic button to freeze operations during emergencies.
9. **Archiving & Retaining Data (UC-09):** Job scheduler simulation to compress and move old orders to history tables to keep the database fast.
10. **Role-Based Access Control (RBAC):** Strict security filters on every page to prevent unauthorized access.

---

## 🛠️ The Tech Stack We Used
We intentionally avoided heavy frameworks to master the core fundamentals of web architecture:
* **Backend:** Pure PHP (MVC Architecture) with OOP and strict session management.
* **Database:** MySQL/MariaDB with transactional integrity (InnoDB locks) for concurrent picking/packing.
* **Frontend:** Clean CSS grids, responsive layouts, and Javascript for dynamic fetch calls (like alerts and real-time scanning simulation).

---

## 📂 Codebase Structure
Our project follows a strict MVC pattern:
```text
├── index.php                 # Core Router & Page Dispatcher
├── config/                   # Database credentials and configuration
├── core/                     # Authentication, helper functions, and security filters
├── controllers/              # Core business logic (Auth, Inventory, Orders, Packing, Picking, etc.)
├── models/                   # Database queries, active records, and business entities
├── views/                    # Divided by roles: admin, auth, dashboard, orders, picking, packing, storage, supplier
├── assets/                   # CSS stylesheets, JS scripts, and images
├── docs/                     # University documentation, DB schemas, and use case diagrams
└── tests/                    # Simulation scripts to verify business logic
```

---

## 🚀 Getting Started Locally
To run this project on your local machine:
1. Make sure you have a local server environment installed (like **XAMPP**, **WampServer**, or **Laragon**) with PHP 8.0+ and MySQL.
2. Clone this repository into your local server directory (e.g., `htdocs` for XAMPP):
   ```bash
   git clone https://github.com/MazenAlhadah/SWE-01.git
   ```
3. Import the database schema (found in the `config/` or `docs/` folder) into your phpMyAdmin.
4. Update your database connection details in `config/Database.php`.
5. Open your browser and navigate to `http://localhost/SWE-01`.
6. Use the seed accounts provided in the documentation to log in as a Manager, Picker, Packer, or Supplier!

---
Thanks for checking out our project! If you have any questions or feedback, feel free to open an issue or reach out to us! 🎓
