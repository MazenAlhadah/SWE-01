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

***

<div dir="rtl">

# 📦 نظام إدارة المستودعات الذكي (WMS)
### 🏛️ جامعة كابيتال | مادة: هندسة البرمجيات 1 (الفصل الدراسي الربيعي 2025-2026)

يا هلا بك! أهلاً بك في مستودع المشروع الجماعي الخاص بنا لمادة **Software Engineering 1 (CS251)** في جامعة كابيتال.

قمنا بتطوير **نظام ذكي لإدارة المستودعات (WMS)** جاهز للعمل والإنتاج بالكامل باستخدام لغة PHP الصافية وبتصميم معماري مخصص يعتمد على نمط **Model-View-Controller (MVC)**. تم تصميم النظام لمحاكاة مستودع حديث وعالي الكفاءة يدعم تكامل إنترنت الأشياء (IoT)، واللوجستيات المؤتمتة، والقياسات الحركية الفورية، ودورات عمل تجهيز الطلبات الذكية.

---

## 💡 فكرة وعماد المشروع
تواجه المستودعات الحديثة تحديات ضخمة مثل: انتهاء صلاحية البضائع على الرفوف، وبطء مسارات تجميع الطلبات، وأخطاء التعبئة والشحن، واختناقات سلاسل الإمداد. يقوم نظامنا بحل هذه المشاكل من خلال تقسيم الصلاحيات إلى أدوار واضحة (المدير، المجمّع، المعبّئ، والمورّد) وأتمتة العمليات المعقدة مثل:
* اختيار البضائع آلياً بناءً على قاعدة **FEFO (ما ينتهي أولاً، يخرج أولاً)** لمنع تلف المخزون.
* محاكاة مستشعرات إنترنت الأشياء (IoT) لقياس سعة الأرفف الحجمية والوزنية.
* خوارزمية ذكية لتجميع الطلبات في دفعات (Batch Picking Route) لتقليل مسافات المشي للمجمّعين.
* التحقق الفوري من التعبئة عبر مطابقة الأوزان ومسح الباركود الخاص بالملصقات التعريفية للطرود.
* ماكينة حالات مؤتمتة للشراء ومتابعة أداء الموردين.

---

## ✨ أهم الميزات وحالات الاستخدام التي قمنا ببرمجتها
لقد قمنا ببناء هذا المشروع بشكل تدريجي وتراكمي، حيث قمنا بتنفيذ 20 حالة استخدام (Use Case) رئيسية بالكامل في الخلفية البرمجية:

1. **لوحة القياسات الفورية وحالة المخزون (UC-01 & UC-06):** مراقبة فورية لوزن الأرفف وسعتها وإرسال تنبيهات تلقائية عند تجاوز الحد المسموح.
2. **التخزين النطاقي ومراقبة انتهاء الصلاحية (UC-02, UC-03 & UC-15):** فحص تواريخ انتهاء الصلاحية آلياً مع خاصية تجاوز الصلاحيات للمدير، ودعم ميزة **العبور المباشر (Cross-Docking)** لنقل البضائع المستعجلة فوراً للشحن دون الحاجة لتخزينها.
3. **أتمتة المشتريات وتدفق طلبات التوريد (UC-05 & UC-16):** توليد طلبات شراء تلقائية عند نقص المخزون، وتحويلها لملفات PDF وتوفير بوابة للمورد لتحديث حالة الشحن.
4. **بوابة وتحليلات الموردين (UC-04 & UC-20):** لوحة تحكم خاصة بالموردين لتأكيد الطلبات وتعيين شركات الشحن وعرض تقارير تقييم أدائهم (Audit Report).
5. **التجمع الذكي على دفعات (UC-10):** مسار تجميع ذكي ومحسن للمجمّع (Picker Route) يجمع البضائع بناءً على الممرات والرفوف لتقليل زمن الحركة.
6. **محطة التعبئة والتأكيد المزدوج (UC-11, UC-12 & UC-13):** فحص وتعبئة الطرود بمطابقة الأوزان واختيار أحجام الصناديق المناسبة، وطباعة ملصقات الشحن ومسحها بالباركود للتأكد التام من خلو الشحنة من الأخطاء.
7. **ماكينة حالات الطلبات (UC-14):** تدفق برمجي آمن ومحمي ضد التزامن يتتبع الطلبات بدقة من *قيد الانتظار* -> *قيد التجميع* -> *مغلّف* -> *تم الشحن*.
8. **نظام الطوارئ (UC-07):** لوحة تحكم للمدير لتفعيل وضع الطوارئ وتجميد كافة العمليات في المستودع فوراً عند حدوث أي خلل.
9. **أرشفة البيانات وحفظ السجلات (UC-09):** محاكي لجدولة البيانات وضغط الطلبات القديمة ونقلها لجداول أرشيفية للحفاظ على سرعة قاعدة البيانات.
10. **التحكم بالصلاحيات (RBAC):** فلترة برمجية صارمة على كل صفحة للتأكد من عدم وصول أي مستخدم لصفحة لا تتبع لدوره الوظيفي.

---

## 🛠️ التقنيات التي استخدمناها
لقد فضلنا العمل بالتقنيات الصافية دون الاعتماد على إطارات عمل ثقيلة لنتقن تماماً أساسيات تصميم وبناء الأنظمة وهندستها:
* **الخلفية البرمجية (Backend):** لغة PHP الصافية بنمط MVC والبرمجة كائنية التوجه (OOP) وإدارة الجلسات بشكل آمن.
* **قاعدة البيانات:** MySQL/MariaDB مع استخدام الأقفال الحصرية وقفل المعاملات (Transactional Locks - InnoDB) لمنع تضارب البيانات أثناء التجميع والتعبئة المتزامنة.
* **الواجهات الرسومية (Frontend):** تنسيقات CSS Grid و Flexbox متجاوبة تماماً، مع جافا سكريبت لتنفيذ طلبات الـ Fetch الفورية لمحاكاة المسح الضوئي والتنبيهات.

---

## 📂 هيكل تنظيم الكود
يتبع مشروعنا نمط MVC بدقة عالية:
* **الملف الرئيسي (`index.php`):** الموجه الرئيسي والمسؤول عن توجيه المستخدمين بناءً على الصفحة والحدث.
* **مجلد `config/`:** ملفات إعدادات والاتصال بقاعدة البيانات.
* **مجلد `core/`:** برمجيات التحقق من الهوية، الحماية، وفلاتر الأمان.
* **مجلد `controllers/`:** منطق العمليات الرئيسي (المتحكمات).
* **مجلد `models/`:** الاستعلامات والتعامل المباشر مع جداول قاعدة البيانات.
* **مجلد `views/`:** واجهات العرض مقسمة حسب الأدوار (المدير، المجمع، المعبئ، المورد، التسجيل).
* **مجلد `assets/`:** ملفات التصميم CSS وجافا سكريبت والصور والأيقونات.
* **مجلد `docs/`:** وثائق المشروع ومخططات جداول قاعدة البيانات ومخططات حالات الاستخدام (UML).
* **مجلد `tests/`:** سيناريوهات برمجية لاختبار كفاءة ودقة عمل خوارزميات النظام.

---

## 🚀 كيفية تشغيل المشروع محلياً
يمكنك تشغيل المشروع على جهازك باتباع الخطوات البسيطة التالية:
1. تأكد من تثبيت بيئة سيرفر محلي مثل (**XAMPP** أو **Laragon** أو **WampServer**) يدعم PHP 8.0+ وقاعدة بيانات MySQL.
2. قم بتحميل أو عمل Clone للمستودع داخل مجلد السيرفر المحلي الخاص بك (مثلاً مجلد `htdocs` في XAMPP):
   ```bash
   git clone https://github.com/MazenAlhadah/SWE-01.git
   ```
3. قم باستيراد قاعدة البيانات الموجودة في المجلد `config/` أو `docs/` إلى الـ phpMyAdmin الخاص بك.
4. عدل بيانات الاتصال بقاعدة البيانات في ملف `config/Database.php` إذا لزم الأمر.
5. افتح المتصفح وتوجه للرابط التالي: `http://localhost/SWE-01`.
6. استخدم بيانات الحسابات التجريبية المذكورة في التوثيق لتسجيل الدخول بأي من الصلاحيات الأربعة المتاحة وتجربة النظام بالكامل!

---
شكراً لمرورك واهتمامك بمشروعنا! لو عندك أي استفسار أو اقتراح، متترددش تفتح Issue أو تتواصل معانا فوراً. 🎓

</div>
