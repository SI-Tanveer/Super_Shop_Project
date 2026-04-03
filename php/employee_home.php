<?php
session_start();

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

$stats = [
    'pending_orders'   => 12,
    'completed_orders' => 8,
    'total_orders'     => 20,
    'products_total'   => 50,
];

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Employee Dashboard</title>
<link rel="stylesheet" href="../css/employee_home.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
<header class="site">
<nav class="nav">
    <a href="employee_home.php" class="logo">Employee<span>Panel</span></a>
    <a href="employee_home.php" class="active">Home</a>
    <a href="orders.php">Orders</a>
    <a href="products.php">Products</a>
    <a href="messages.php">Messages</a>
    <a href="setting.php">Settings</a>
    <div class="icons">
        <a href="logout.php" class="btn logout-btn"><i class="fas fa-right-from-bracket"></i> Logout</a>
        <button id="user-btn" class="icon-btn" aria-label="User"><i class="fas fa-user"></i></button>
    </div>
</nav>
</header>

<main class="container">
<h2 class="title">Employee Dashboard</h2>

<section class="dashboard-grid">
    <article class="stat-card">
        <div class="stat-value"><?= e((string)$stats['pending_orders']) ?></div>
        <div class="stat-label">Pending Orders</div>
        <div class="actions"><a href="orders.php" class="btn ghost">See Orders</a></div>
    </article>

    <article class="stat-card">
        <div class="stat-value"><?= e((string)$stats['completed_orders']) ?></div>
        <div class="stat-label">Completed Orders</div>
        <div class="actions"><a href="orders.php" class="btn ghost">See Orders</a></div>
    </article>

    <article class="stat-card">
        <div class="stat-value"><?= e((string)$stats['total_orders']) ?></div>
        <div class="stat-label">Total Orders</div>
        <div class="actions"><a href="orders.php" class="btn ghost">See Orders</a></div>
    </article>

    <article class="stat-card">
        <div class="stat-value"><?= e((string)$stats['products_total']) ?></div>
        <div class="stat-label">Products Available</div>
        <div class="actions"><a href="products.php" class="btn ghost">See Products</a></div>
    </article>
</section>
</main>
</body>
</html>
