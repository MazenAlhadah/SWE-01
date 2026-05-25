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

## 🔍 In-Depth Subsystem Explanation

To help you understand how this system works, we divided it into four main logical subsystems:

### 1. 🌡️ IoT Telemetry & Zonal Storage
* **IoT Sensor Simulation:** The warehouse is divided into specific physical zones (e.g., Cold Storage, Hazmat, General). We simulate weight and volume sensors on the shelves. If a shelf is overloaded or runs out of volume capacity, the system triggers real-time visual warnings on the dashboard and blocks any new incoming storage allocations to prevent safety hazards.
* **FEFO Expiry Watchdog:** When storing perishable or time-sensitive goods, the system automatically tags them with batch IDs and expiry dates. When an order is placed, the system dynamically locks and assigns the oldest items that are closest to expiration (First-Expired, First-Out) so nothing goes to waste on the shelves.
* **Emergency Override:** If a physical hazard occurs (e.g., simulated temperature spike in cold zone), a Manager can hit the "Emergency Override" button. This dynamically freezes all active picking and packing tasks in that zone, locking transactions in the database until safety is restored.

### 2. 🚛 Automated Procurement & Supplier Portal
* **Low-Stock Triggers:** When inventory of any product drops below a predefined safety threshold, the system flags it as "Low Stock" and queues an automated procurement request.
* **Purchase Order (PO) State Machine:** Managers can review the queued request, select from verified suppliers based on historical performance ratings, and click to auto-generate a professional PDF Purchase Order.
* **Supplier Collaboration:** Once generated, the PO instantly appears in the custom **Supplier Portal**. The supplier logs in, confirms the order, assigns a carrier, and updates the dispatch status. 
* **Supplier Audit:** The system logs exact dispatch times and compares them to agreed delivery windows. This calculates a dynamic "On-Time Delivery Rate" and "Quality Rating" for each supplier, displayed to the Manager during future order placements.

### 3. 🏃‍♂️ Route-Optimized Batch Picking
* **Batching Orders:** Instead of pickers walking back and forth across the warehouse for single orders, the system automatically groups multiple pending orders into a single "Picking Batch".
* **Routing Algorithm:** The system organizes the picking list by warehouse physical coordinates (Zone -> Aisle -> Shelf -> Bin). It generates an optimized step-by-step path for the Picker. The picker follows a single logical loop through the warehouse, scanning/confirming items on their screen at each stop until the batch is complete.

### 4. 📦 The Double-Check Packing Station
* **Packing Verification:** Once the Picker delivers the items to the packing station, the Packer takes over. The Packer must verify that the items match the order exactly.
* **Physical Box Selector:** The system calculates the total cubic volume of the items and recommends the absolute best-fit box size (Small, Medium, or Large) to reduce shipping costs and waste.
* **Double-Scan Security:** The packer scans the physical barcode printed on the box, prints the shipping label, and does a final scan verification. The system updates the order state to `Shipped` and locks the transaction so it cannot be double-processed.

---

## 🛠️ The Tech Stack We Used
We intentionally avoided heavy frameworks to master the core fundamentals of web architecture:
* **Backend:** Pure PHP (MVC Architecture) with OOP and strict session management.
* **Database:** MySQL/MariaDB with transactional integrity (InnoDB locks) to handle concurrent picking and packing requests safely.
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
