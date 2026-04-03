<?php
declare(strict_types=1);
session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$DB_HOST = '127.0.0.1';
$DB_PORT = 3306;
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'addproductdb';

try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
    $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
    http_response_code(500);
    exit('Database connection failed.');
}

function e($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function okStatus($s): bool {
    return in_array($s, ['pending','processing','paid','delivered','cancelled'], true);
}

function okPayment($p): bool {
    return $p === null || $p === '' || in_array($p, ['unpaid','paid'], true);
}

$errors = [];
$success = '';

/* DELETE ORDER */
if (isset($_GET['delete'])) {
    $orderCode = trim((string)$_GET['delete']);

    if ($orderCode !== '') {
        $q1 = $conn->prepare("SELECT id FROM orders WHERE order_code=? LIMIT 1");
        $q1->bind_param('s', $orderCode);
        $q1->execute();
        $r1 = $q1->get_result()->fetch_assoc();
        $q1->close();

        if ($r1) {
            $oid = (int)$r1['id'];

            $delItems = $conn->prepare("DELETE FROM order_items WHERE order_id=?");
            $delItems->bind_param('i', $oid);
            $delItems->execute();
            $delItems->close();

            $del = $conn->prepare("DELETE FROM orders WHERE id=?");
            $del->bind_param('i', $oid);
            $del->execute();
            $del->close();
        }
    }

    header("Location: order.php");
    exit;
}

/* UPDATE ORDER */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderCode = isset($_POST['id']) ? trim((string)$_POST['id']) : '';
    $status    = isset($_POST['status']) ? trim((string)$_POST['status']) : '';
    $payment   = isset($_POST['payment']) ? trim((string)$_POST['payment']) : '';

    if ($orderCode === '') $errors[] = 'Invalid order.';
    if (!okStatus($status)) $errors[] = 'Invalid status.';
    if (!okPayment($payment)) $errors[] = 'Invalid payment status.';

    if (!$errors) {
        $chk = $conn->prepare("SELECT id FROM orders WHERE order_code=? LIMIT 1");
        $chk->bind_param('s', $orderCode);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$row) {
            $errors[] = 'Order not found.';
        } else {
            if ($payment === '') {
                $upd = $conn->prepare("UPDATE orders SET status=? WHERE order_code=?");
                $upd->bind_param('ss', $status, $orderCode);
            } else {
                $upd = $conn->prepare("UPDATE orders SET status=?, payment=? WHERE order_code=?");
                $upd->bind_param('sss', $status, $payment, $orderCode);
            }

            $upd->execute();
            $upd->close();

            $success = "Order " . $orderCode . " updated successfully.";
        }
    }
}

/* LOAD ORDERS */
$sql = "
    SELECT
        o.id,
        o.order_code,
        COALESCE(o.status, 'pending') AS status,
        COALESCE(o.payment, 'unpaid') AS payment,
        DATE_FORMAT(o.created_at, '%b %d, %Y • %H:%i') AS placed_fmt,
        o.full_name,
        o.email,
        o.address,
        o.delivery,
        o.total,
        COALESCE(
            TRIM(BOTH ', ' FROM GROUP_CONCAT(CONCAT(oi.name, ' x', oi.qty) ORDER BY oi.id SEPARATOR ', ')),
            ''
        ) AS items_list
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    GROUP BY
        o.id, o.order_code, o.status, o.payment, o.created_at,
        o.full_name, o.email, o.address, o.delivery, o.total
    ORDER BY o.id DESC
";

$res = $conn->query($sql);

$orders = [];
while ($o = $res->fetch_assoc()) {
    $orders[] = [
        'id'        => (string)$o['order_code'],
        'status'    => (string)$o['status'],
        'payment'   => (string)$o['payment'],
        'placed'    => (string)$o['placed_fmt'],
        'name'      => (string)$o['full_name'],
        'email'     => (string)$o['email'],
        'address'   => (string)$o['address'],
        'payMethod' => (string)$o['delivery'],
        'items'     => $o['items_list'] !== '' ? (string)$o['items_list'] : '-',
        'total'     => (float)$o['total']
    ];
}

$res->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Orders • Admin Panel</title>
    <link rel="stylesheet" href="../css/order.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <style>
        .error{color:#b00020;font-size:.9rem;margin:0 0 12px}
        .success{color:#0a7f33;font-weight:600;margin-bottom:12px}
        .update-form{
            display:flex;
            flex-direction:column;
            gap:8px;
            padding:10px;
            border:1px solid #1b4332;
            border-radius:10px;
            background:#f6fff8
        }
        .update-form label{
            display:flex;
            flex-direction:column;
            gap:4px;
            font-size:.92rem;
            color:#1b4332;
            font-weight:700
        }
        .update-form select{
            padding:8px 10px;
            border:1px solid #1b4332;
            border-radius:8px;
            background:#fff;
            color:#1b4332
        }
    </style>
</head>
<body>

<header class="site">
    <nav class="nav">
        <a href="admin.php" class="logo">Admin<span>Panel</span></a>
        <a href="home.php">Home</a>
        <a href="admin_products.php">Products</a>
        <a href="order.php" class="active">Orders</a>
        <a href="user.php">Users</a>
        <a href="message.php">Messages</a>

        <div class="icons">
            <a href="logout.php" class="btn logout-btn">
                <i class="fas fa-right-from-bracket"></i> Logout
            </a>
            <button id="user-btn" class="icon-btn" aria-label="User">
                <i class="fas fa-user"></i>
            </button>
        </div>
    </nav>
</header>

<main class="container">
    <h1 class="title">Placed Orders</h1>

    <?php if (!empty($success)) { ?>
        <div class="success"><?php echo e($success); ?></div>
    <?php } ?>

    <?php if (!empty($errors)) { ?>
        <div class="error"><?php echo e(implode(', ', $errors)); ?></div>
    <?php } ?>

    <section class="orders-grid">
        <?php foreach ($orders as $o) { ?>
            <article class="order-card card" data-status="<?php echo e($o['status']); ?>" data-payment="<?php echo e($o['payment']); ?>">
                <header class="order-head">
                    <div class="id">#<?php echo e($o['id']); ?></div>
                    <div class="chips">
                        <span class="chip status <?php echo e($o['status']); ?>"><?php echo ucfirst($o['status']); ?></span>
                        <span class="chip pay <?php echo e($o['payment']); ?>"><?php echo ucfirst($o['payment']); ?></span>
                    </div>
                </header>

                <div class="order-meta">
                    <p><span class="label">Placed on</span><span class="value"><?php echo e($o['placed']); ?></span></p>
                    <p><span class="label">Name</span><span class="value"><?php echo e($o['name']); ?></span></p>
                    <p><span class="label">Email</span><span class="value"><?php echo e($o['email']); ?></span></p>
                    <p><span class="label">Address</span><span class="value"><?php echo e($o['address']); ?></span></p>
                    <p><span class="label">Delivery</span><span class="value"><?php echo e($o['payMethod']); ?></span></p>
                    <p><span class="label">Items</span><span class="value"><?php echo e($o['items']); ?></span></p>
                    <p><span class="label">Total</span><span class="value"><strong>৳ <?php echo e(number_format($o['total'], 2)); ?>/-</strong></span></p>
                </div>

                <footer class="order-actions">
                    <div class="left">
                        <button class="btn ghost small" type="button"><i class="fa-solid fa-print"></i> Invoice</button>
                        <button class="btn ghost small" type="button"><i class="fa-solid fa-location-dot"></i> Track</button>
                    </div>

                    <div class="right">
                        <form method="post" class="update-form">
                            <input type="hidden" name="id" value="<?php echo e($o['id']); ?>">

                            <label>Status
                                <select name="status" required>
                                    <?php foreach (['pending','processing','paid','delivered','cancelled'] as $s) { ?>
                                        <option value="<?php echo $s; ?>" <?php echo ($o['status'] === $s ? 'selected' : ''); ?>>
                                            <?php echo ucfirst($s); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </label>

                            <label>Payment
                                <select name="payment">
                                    <option value="">(no change)</option>
                                    <option value="unpaid" <?php echo ($o['payment'] === 'unpaid' ? 'selected' : ''); ?>>Unpaid</option>
                                    <option value="paid" <?php echo ($o['payment'] === 'paid' ? 'selected' : ''); ?>>Paid</option>
                                </select>
                            </label>

                            <button class="btn small" type="submit">
                                <i class="fa-solid fa-bars-progress"></i> Update
                            </button>
                        </form>

                        <a href="?delete=<?php echo e($o['id']); ?>" class="btn danger small" onclick="return confirm('Delete this order?')">
                            <i class="fa-solid fa-trash"></i> Delete
                        </a>
                    </div>
                </footer>
            </article>
        <?php } ?>

        <?php if (empty($orders)) { ?>
            <p>No orders found.</p>
        <?php } ?>
    </section>
</main>

<footer class="site-footer">
    <p>&copy; 2026 Admin Panel</p>
</footer>

</body>
</html>