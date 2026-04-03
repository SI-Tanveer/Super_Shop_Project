<?php
session_start();


if (!isset($_SESSION['messages'])) {
    $_SESSION['messages'] = [
        ['id'=>'MSG-001','name'=>'Arshad Islam','email'=>'arshad@example.com','subject'=>'Order Delay','message'=>'My order has not arrived yet. Please check.','date'=>'Sep 20, 2025'],
        ['id'=>'MSG-002','name'=>'Salman Arefin','email'=>'salman@example.com','subject'=>'Product Inquiry','message'=>'Is the Tomato fresh and organic?','date'=>'Sep 21, 2025'],
        ['id'=>'MSG-003','name'=>'Taj Uddin','email'=>'taj@example.com','subject'=>'Payment Issue','message'=>'I paid online but the status shows unpaid.','date'=>'Sep 22, 2025'],
    ];
}

$messages = $_SESSION['messages'];

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Messages • Employee Panel</title>
    <link rel="stylesheet" href="../css/employee_messages.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
<header class="site">
    <nav class="nav">
        <a href="employee_home.php" class="logo">Employee<span>Panel</span></a>
        <a href="employee_home.php">Home</a>
        <a href="orders.php">Orders</a>
        <a href="products.php">Products</a>
        <a href="messages.php" class="active">Messages</a>
        <a href="setting.php">Settings</a>
        <div class="icons">
            <a href="logout.php" class="btn logout-btn"><i class="fas fa-right-from-bracket"></i> Logout</a>
        <button id="user-btn" class="icon-btn" aria-label="User"><i class="fas fa-user"></i></button>
        </div>
    </nav>
</header>

<main class="container">
    <h1 class="title">User Messages</h1>

    <section class="messages-grid">
        <?php foreach($messages as $m): ?>
        <article class="message-card card">
            <header class="message-head">
                <div class="id">#<?= e($m['id']) ?></div>
                <div class="date"><?= e($m['date']) ?></div>
            </header>
            <div class="message-meta">
                <p><span class="label">Name</span><span class="value"><?= e($m['name']) ?></span></p>
                <p><span class="label">Email</span><span class="value"><?= e($m['email']) ?></span></p>
                <p><span class="label">Subject</span><span class="value"><?= e($m['subject']) ?></span></p>
                <p><span class="label">Message</span><span class="value"><?= e($m['message']) ?></span></p>
            </div>
            <footer class="message-actions">
                <button class="btn small"><i class="fa-solid fa-reply"></i> Reply</button>
            </footer>
        </article>
        <?php endforeach; ?>
    </section>
</main>
</body>
</html>
