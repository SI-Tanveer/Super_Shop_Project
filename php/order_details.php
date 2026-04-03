<?php
session_start();


$id = $_GET['id'] ?? '';


$orders = $_SESSION['orders'] ?? [];
$order = null;

foreach ($orders as $o) {
    if ($o['id'] === $id) {
        $order = $o;
        break;
    }
}

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

if (!$order) {
    header("Location: orders.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?= e($order['id']) ?> • Employee Panel</title>
    <link rel="stylesheet" href="../css/employee_order_details.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
<header class="site">
    <nav class="nav">
        <a href="employee_home.php" class="logo">Employee<span>Panel</span></a>
        <a href="employee_home.php">Home</a>
        <a href="orders.php" class="active">Orders</a>
        <a href="products.php">Products</a>
        <a href="messages.php">Messages</a>
        <a href="users.php">Users</a>
        <div class="icons">
            <a href="logout.php" class="btn logout-btn"><i class="fas fa-right-from-bracket"></i> Logout</a>
        <button id="user-btn" class="icon-btn" aria-label="User"><i class="fas fa-user"></i></button>
        </div>
    </nav>
</header>

<main class="container">
    <section class="order-detail-card">
        <header class="order-header">
            <h2>Order ID: <?= e($order['id']) ?></h2>
            <div class="chips">
                <span class="chip status <?= e($order['status']) ?>"><?= ucfirst($order['status']) ?></span>
                <span class="chip payment <?= e($order['payment']) ?>"><?= ucfirst($order['payment']) ?></span>
            </div>
        </header>

        <div class="order-info">
            <p><strong>Placed on:</strong> <?= e($order['placed']) ?></p>
            <p><strong>Name:</strong> <?= e($order['name']) ?></p>
            <p><strong>Email:</strong> <?= e($order['email']) ?></p>
            <p><strong>Address:</strong> <?= e($order['address']) ?></p>
            <p><strong>Payment Method:</strong> <?= e($order['payMethod']) ?></p>
            <p><strong>Items:</strong> <?= e($order['items']) ?></p>
            <p><strong>Total:</strong> $<?= e((string)$order['total']) ?>/-</p>
        </div>

        <div class="actions">
            <a href="orders.php" class="btn ghost"><i class="fas fa-arrow-left"></i> Back to Orders</a>
        </div>
    </section>
</main>
</body>
</html>
