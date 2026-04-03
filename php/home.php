<?php
declare(strict_types=1);
session_start();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

$stats = [
    'pending_amount'   => 20,
    'completed_amount' => 22,
    'orders_placed'    => 3,
    'products_added'   => 6,
    'total_users'      => 5,
    'total_admin'      => 1,
    'total_accounts'   => 4,
    'total_staff'      => 3,
];

if (!empty($_SESSION['stats']) && is_array($_SESSION['stats'])) {
    $stats = array_merge($stats, $_SESSION['stats']);
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf']) || !hash_equals($csrf, (string)$_POST['csrf'])) {
        $errors['csrf'] = 'Invalid request. Reload the page.';
    } else {
        foreach ($stats as $k => $v) {
            $raw = $_POST[$k] ?? null;

            if ($raw === null || $raw === '' || filter_var($raw, FILTER_VALIDATE_INT) === false || (int)$raw < 0) {
                $errors[$k] = 'Enter a non-negative integer.';
            } else {
                $stats[$k] = (int)$raw;
            }
        }

        if (!$errors) {
            $_SESSION['stats'] = $stats;
            $success = true;
        }
    }
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Dashboard</title>

    <link rel="stylesheet" href="../css/homr.css" />
    <link rel="stylesheet" href="../css/addd.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

    <style>
        .form-card{
            max-width:720px;
            margin:24px 0;
            padding:16px;
            border:1px solid #ddd;
            border-radius:10px;
            background:#fff
        }
        .grid{
            display:grid;
            gap:12px;
            grid-template-columns:repeat(2,1fr)
        }
        .row{
            display:flex;
            flex-direction:column
        }
        .row label{
            font-weight:600;
            margin-bottom:6px
        }
        .row input{
            padding:10px;
            border:1px solid #bbb;
            border-radius:6px
        }
        .error{
            color:#b00020;
            font-size:.9rem;
            margin-top:6px
        }
        .success{
            color:#0a7f33;
            margin:0 0 8px 0;
            font-weight:600
        }
        .btn.small{
            padding:8px 14px;
            font-size:.95rem
        }
    </style>
</head>
<body>
<header class="site">
  <nav class="nav">
    <a href="admin.php" class="logo">Admin<span>Panel</span></a>
    <a href="home.php" class="active">Home</a>
    <a href="admin_products.php">Products</a>
    <a href="order.php">Orders</a>
    <a href="user.php">Users</a>
    <a href="message.php">Messages</a>
    <div class="icons">
      <a href="logout.php" class="btn logout-btn"><i class="fas fa-right-from-bracket"></i> Logout</a>
      <button id="user-btn" class="icon-btn" aria-label="User"><i class="fas fa-user"></i></button>
    </div>
  </nav>
</header>
<main class="container">
    <h2 class="title">Dashboard</h2>

    <section class="dashboard-grid">
        <article class="stat-card">
            <div class="stat-value">৳<?= e((string)$stats['pending_amount']) ?><span class="unit">/-</span></div>
            <div class="stat-label">Total Pendings</div>
            <div class="actions"><a href="order.php" class="btn ghost">See Orders</a></div>
        </article>

        <article class="stat-card">
            <div class="stat-value">৳<?= e((string)$stats['completed_amount']) ?><span class="unit">/-</span></div>
            <div class="stat-label">Completed Orders</div>
            <div class="actions"><a href="order.php" class="btn ghost">See Orders</a></div>
        </article>

        <article class="stat-card">
            <div class="stat-value"><?= e((string)$stats['orders_placed']) ?></div>
            <div class="stat-label">Orders Placed</div>
            <div class="actions"><a href="order.php" class="btn ghost">See Orders</a></div>
        </article>

        <article class="stat-card">
            <div class="stat-value"><?= e((string)$stats['products_added']) ?></div>
            <div class="stat-label">Product Added</div>
            <div class="actions"><a class="btn" href="admin_products.php">See Products</a></div>
        </article>

        <article class="stat-card">
            <div class="stat-value"><?= e((string)$stats['total_users']) ?></div>
            <div class="stat-label">Total Users</div>
            <div class="actions"><a class="btn ghost" href="user.php">See Accounts</a></div>
        </article>

        <article class="stat-card">
            <div class="stat-value"><?= e((string)$stats['total_admin']) ?></div>
            <div class="stat-label">Total Admin</div>
            <div class="actions"><a class="btn ghost" href="user.php">See Accounts</a></div>
        </article>

        <article class="stat-card">
            <div class="stat-value"><?= e((string)$stats['total_accounts']) ?></div>
            <div class="stat-label">Total Accounts</div>
            <div class="actions"><a class="btn ghost" href="user.php">See Accounts</a></div>
        </article>

        <article class="stat-card">
            <div class="stat-value"><?= e((string)$stats['total_staff']) ?></div>
            <div class="stat-label">Total Staff</div>
            <div class="actions"><a class="btn ghost" href="user.php">See Accounts</a></div>
        </article>
    </section>

    <section class="form-card" id="edit-stats">
        <?php if ($success): ?>
            <p class="success"><i class="fa-solid fa-circle-check"></i> Dashboard updated successfully.</p>
        <?php endif; ?>

        <form method="post" novalidate>
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

            <div class="grid">
                <?php foreach ($stats as $k => $v): ?>
                    <div class="row">
                        <label for="<?= e($k) ?>"><?= ucwords(str_replace('_', ' ', $k)) ?></label>
                        <input
                            type="number"
                            id="<?= e($k) ?>"
                            name="<?= e($k) ?>"
                            min="0"
                            step="1"
                            value="<?= e((string)$v) ?>"
                            required
                        >
                        <?php if (isset($errors[$k])): ?>
                            <div class="error"><?= e($errors[$k]) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (isset($errors['csrf'])): ?>
                <div class="error"><?= e($errors['csrf']) ?></div>
            <?php endif; ?>

            <button class="btn small" type="submit" style="margin-top:12px;">
                <i class="fa-solid fa-floppy-disk"></i> Save Dashboard
            </button>
        </form>
    </section>
</main>
</body>
</html>