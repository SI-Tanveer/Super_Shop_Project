<?php
// php/orderlist.php — lists the logged-in user's orders with expandable line items
declare(strict_types=1);
session_start();

/* Optional guard: force login to see orders */
if (empty($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$DB_HOST='127.0.0.1'; $DB_PORT=3306; $DB_USER='root'; $DB_PASS=''; $DB_NAME='addproductdb';
try {
  $conn = new mysqli($DB_HOST,$DB_USER,$DB_PASS,$DB_NAME,$DB_PORT);
  $conn->set_charset('utf8mb4');
} catch(Throwable $e) {
  http_response_code(500);
  exit('Database connection failed.');
}

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$userId = (int)$_SESSION['user_id'];


$st = $conn->prepare("
  SELECT id, order_code, delivery, subtotal, delivery_fee, vat, total, created_at
  FROM orders
  WHERE user_id = ?
  ORDER BY id DESC
");
$st->bind_param("i", $userId);
$st->execute();
$orders = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();


$itemsByOrder = [];
if (!empty($orders)) {
  $ids = array_column($orders, 'id');
  $in  = implode(',', array_fill(0, count($ids), '?'));
  $typ = str_repeat('i', count($ids));
  $q = $conn->prepare("
    SELECT order_id, product_id, name, qty, unit_price, line_total
    FROM order_items
    WHERE order_id IN ($in)
    ORDER BY id ASC
  ");
  $q->bind_param($typ, ...$ids);
  $q->execute();
  $rs = $q->get_result();
  while ($row = $rs->fetch_assoc()) {
    $oid = (int)$row['order_id'];
    if (!isset($itemsByOrder[$oid])) $itemsByOrder[$oid] = [];
    $itemsByOrder[$oid][] = $row;
  }
  $q->close();
}
$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=1200"/>
  <title>My Orders</title>
  <link rel="stylesheet" href="../css/orderlist.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
  <header class="ol-site">
    <nav class="ol-nav">
      <a href="dashboard.php" class="logo">Supershop</a>
      <div class="spacer"></div>
      <a class="btn" href="dashboard.php">← Back to shop</a>
      <a class="btn danger" href="logout.php">Logout</a>
    </nav>
  </header>

  <main class="ol-container">
    <h1>My Orders</h1>

    <?php if (empty($orders)): ?>
      <div class="empty">You don’t have any orders yet.</div>
    <?php else: ?>
      <section class="orders">
        <?php foreach ($orders as $o): ?>
          <article class="order-card">
            <div class="order-head">
              <div class="left">
                <div class="code">#<?= e($o['order_code']) ?></div>
                <div class="meta">Placed on <?= e(date('d M Y, h:i A', strtotime((string)$o['created_at']))) ?></div>
              </div>
              <div class="right">
                <div class="total">৳ <?= e(number_format((float)$o['total'],2)) ?></div>
                <div class="delivery"><?= e($o['delivery']) ?></div>
              </div>
            </div>

            <details class="items">
              <summary><i class="fa-solid fa-box-open"></i> Show items</summary>
              <?php $lines = $itemsByOrder[(int)$o['id']] ?? []; ?>
              <?php if (empty($lines)): ?>
                <div class="noitems">No items found.</div>
              <?php else: ?>
                <div class="table">
                  <div class="t-head">
                    <div>Item</div><div>Qty</div><div>Unit</div><div>Line Total</div>
                  </div>
                  <?php foreach ($lines as $it): ?>
                    <div class="t-row">
                      <div><?= e($it['name']) ?></div>
                      <div>×<?= (int)$it['qty'] ?></div>
                      <div>৳ <?= e(number_format((float)$it['unit_price'],2)) ?></div>
                      <div>৳ <?= e(number_format((float)$it['line_total'],2)) ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </details>

            <div class="totals">
              <div><span>Subtotal</span><span>৳ <?= e(number_format((float)$o['subtotal'],2)) ?></span></div>
              <div><span>Delivery</span><span>৳ <?= e(number_format((float)$o['delivery_fee'],2)) ?></span></div>
              <div><span>VAT (5%)</span><span>৳ <?= e(number_format((float)$o['vat'],2)) ?></span></div>
              <div class="grand"><span>Total</span><span>৳ <?= e(number_format((float)$o['total'],2)) ?></span></div>
            </div>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>
  </main>
</body>
</html>
