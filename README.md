Supershop Management System (SSMS)
An all-in-one supermarket manager for Admins, Employees, and Customers. Fast inventory, simple checkout, clean UI, and role-based access.

Table of Contents
1.	Overview
2.	Why SSMS
3.	Tech Stack
4.	Features
5.	Project Structure
6.	Installation
7.	Configuration
8.	Database Setup
9.	Running the App
10.	Test Accounts
11.	How to Use
12.	Contributing
13.	Team


1.Overview

SSMS is a lightweight retail system that helps:
•	Admins manage staff, inventory, and orders
•	Employees process sales and check stock
•	Customers browse products, add to cart, and place orders
Built for small/medium stores that need something simpler than big ERPs.

2.Why SSMS

Common pain points we target:
•	Manual stock mistakes
•	Slow/unclear checkout flow
•	Scattered sales records
SSMS brings a single, clean workflow with role-based access and a modern UI.

3.Tech Stack

•	Frontend: HTML, CSS, JavaScript
•	Backend: PHP (procedural + mysqli)
•	Database: MySQL / MariaDB
•	Local Dev: XAMPP / WAMP / MAMP
•	(Optional) Composer / Laravel not required; can be added later.

4.Features

Role Matrix
Capability	Admin	Employee	Customer
Secure Login	✅	✅	✅
Manage Products	✅	✅	❌
View / Update Orders	✅	✅	❌
Manage Users (CRUD)	✅	❌	❌
Browse Products	✅	✅	✅
Cart & Checkout	✅	✅	✅
Order History	✅	✅	✅
Highlights
•	Role-based redirects (admin dashboard, employee panel, customer pages)
•	Clean, responsive UI
•	Simple product & order flows
•	Session-based cart; order summary & VAT calculations
•	Starter SQL for products, users, orders

5.Project Structure
Full_project/
├── css/
│   ├── index.css
│   ├── login.css
│   ├── payment.css
│   ├── order.css
│   ├── registration.css
│   └── user.css
├── img/
├── php/
│   ├── config.php
│   ├── index.php
│   ├── login.php
│   ├── regustration.php
│   ├── registrationem.php
│   ├── home.php
│   ├── dashboard.php
│   ├── payment.php
│   ├── orderlist.php
│   ├── order.php
│   ├── user.php
│   ├── admin_products.php
│   ├── logout.php
│   └── ...
├── sql/
│   └── addproductdb.sql
└── README.md

6.Installation

Prerequisites
•	PHP 8.0+
•	MySQL/MariaDB
•	XAMPP / WAMP / MAMP
1) Clone
git clone https://github.com/Taj22-47271-1/Supershop_webtech_project.git
cd Supershop_webtech_project
2) Move to web root
For XAMPP (Windows): copy the folder into C:\xampp\htdocs\Full_project\

7.Configuration

Create php/config.php:
<?php
// php/config.php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$DB_HOST = '127.0.0.1';
$DB_PORT = 3306;
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'addproductdb';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
$conn->set_charset('utf8mb4');

8.Database Setup

1.	Open phpMyAdmin → create a database named addproductdb.
2.	Import your sql/addproductdb.sql (or run the schema below).
-- USERS
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fullname VARCHAR(120) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(20),
  gender ENUM('male','female','other') DEFAULT 'other',
  role ENUM('ADMIN','EMPLOYEE','CUSTOMER') DEFAULT 'CUSTOMER',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PRODUCTS
CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  image VARCHAR(255),
  details TEXT,
  stock INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ORDERS
CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_code VARCHAR(40) NOT NULL UNIQUE,
  user_id INT UNSIGNED NULL,
  status ENUM('pending','processing','paid','delivered','cancelled') NOT NULL DEFAULT 'pending',
  payment ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid',
  placed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  customer_name  VARCHAR(120) NOT NULL,
  customer_email VARCHAR(120) NOT NULL,
  address        VARCHAR(255) NOT NULL,
  pay_method     VARCHAR(60)  NOT NULL,
  total          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ORDER ITEMS
CREATE TABLE IF NOT EXISTS order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id   INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NULL,
  product_name VARCHAR(150) NOT NULL,
  qty INT UNSIGNED NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  line_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
If you see errors like “Unknown column o.status”, add the missing columns above.

9.Running the App

With XAMPP
•	Start Apache and MySQL in the XAMPP Control Panel
•	Visit: http://localhost/Full_project/php/index.php (landing page)
•	Login: http://localhost/Full_project/php/login.php
PHP built-in server (optional)
php -S localhost:8000 -t php
# then visit http://localhost:8000/index.php

10.Test Accounts

Change these in production.
•	Admin — username: admin, password: 123456 (hardcoded shortcut in login.php)
•	Employee — username: salman, password: 123456
•	Customer — username: apple, password: 123456

11.How to Use

1.	Landing: index.php → click 🔑 Login
2.	Register (optional): Customer: regustration.php, Employee: registrationem.php
3.	Login: login.php
4.	Shop: Add products to cart → payment.php
5.	Orders: Customer: orderlist.php • Admin: order.php

12.Contributing

1.	Fork the repo
2.	Create a feature branch: git checkout -b feature-name
3.	Commit: git commit -m "feat: add X"
4.	Push: git push origin feature-name
5.	Open a Pull Request

13.Team

•	Md. Mahamodul Hasan Taj — Admin — github.com/Taj22-47271-1
•	Md. Arshad Islam — Employee — github.com/arshad055
•	Salman Arefin — Customer — github.com/salmanarefin

