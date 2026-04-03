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

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

function clean($s) {
    return htmlspecialchars(trim($s ?? ''), ENT_QUOTES, 'UTF-8');
}

function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$errors = array_fill_keys(
    ['full_name','phone','email','address','city','postcode','delivery','card_name','card_number','expiry','cvc'],
    ''
);

$vals = array_fill_keys(
    ['full_name','phone','email','address','city','postcode','delivery','card_name','card_number','expiry','cvc','promo'],
    ''
);

$success = false;
$orderId = null;

$cart = $_SESSION['cart'] ?? [];
$items = [];
$subtotal = 0.00;

if (!empty($cart)) {
    $ids = array_keys($cart);
    $ids = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));

    if (!empty($ids)) {
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $typ = str_repeat('i', count($ids));

        $sql = "SELECT id, name, price FROM products WHERE id IN ($in)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($typ, ...$ids);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $pid = (int)$row['id'];
            $qty = (int)($cart[$pid] ?? 0);
            if ($qty <= 0) continue;

            $unit = (float)$row['price'];
            $line = $unit * $qty;

            $items[] = [
                'pid'   => $pid,
                'name'  => (string)$row['name'],
                'qty'   => $qty,
                'unit'  => $unit,
                'total' => $line,
            ];

            $subtotal += $line;
        }
        $stmt->close();
    }
}

$deliveryFee = 0.00;
$vat = round($subtotal * 0.05, 2);
$total = $subtotal + $deliveryFee + $vat;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf']) || !hash_equals($csrf, (string)$_POST['csrf'])) {
        die('Invalid request.');
    }

    foreach ($vals as $k => $_) {
        $vals[$k] = clean($_POST[$k] ?? '');
    }

    if ($vals['full_name'] === '') {
        $errors['full_name'] = 'Full name is required';
    } elseif (!preg_match("/^[\p{L} .'\-]{2,}$/u", $vals['full_name'])) {
        $errors['full_name'] = 'Only letters/spaces/.\'- allowed';
    }

    if ($vals['phone'] === '') {
        $errors['phone'] = 'Phone is required';
    } elseif (!preg_match('/^\+?\d{10,15}$/', $vals['phone'])) {
        $errors['phone'] = 'Enter 10–15 digits';
    }

    if ($vals['email'] !== '' && !filter_var($vals['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email';
    }

    if ($vals['address'] === '') {
        $errors['address'] = 'Address is required';
    } elseif (mb_strlen($vals['address']) < 5) {
        $errors['address'] = 'Address seems too short';
    }

    if ($vals['city'] === '') {
        $errors['city'] = 'City is required';
    } elseif (!preg_match("/^[\p{L} .'\-]{2,}$/u", $vals['city'])) {
        $errors['city'] = 'City should be letters/spaces only';
    }

    if ($vals['postcode'] === '') {
        $errors['postcode'] = 'Postcode is required';
    } elseif (!preg_match('/^[A-Za-z0-9\- ]{3,10}$/', $vals['postcode'])) {
        $errors['postcode'] = '3–10 letters/numbers/dash/space';
    }

    if ($vals['delivery'] === '') {
        $errors['delivery'] = 'Choose a delivery option';
    }

    if ($vals['card_name'] === '') {
        $errors['card_name'] = 'Name on card is required';
    }

    if ($vals['card_number'] === '') {
        $errors['card_number'] = 'Card number is required';
    } else {
        $digits = preg_replace('/\D+/', '', $vals['card_number']);
        if (!preg_match('/^\d{13,19}$/', $digits)) {
            $errors['card_number'] = 'Enter a valid card number (13–19 digits)';
        }
    }

    if ($vals['expiry'] === '') {
        $errors['expiry'] = 'Expiry is required';
    } elseif (!preg_match('/^(0[1-9]|1[0-2])\s*\/\s*([0-9]{2})$/', $vals['expiry'], $m)) {
        $errors['expiry'] = 'Use MM/YY';
    } else {
        $mm = (int)$m[1];
        $yy = (int)$m[2] + 2000;
        $last = cal_days_in_month(CAL_GREGORIAN, $mm, $yy);
        if (strtotime("$yy-$mm-$last 23:59:59") < time()) {
            $errors['expiry'] = 'Card expired';
        }
    }

    if ($vals['cvc'] === '') {
        $errors['cvc'] = 'CVC is required';
    } elseif (!preg_match('/^\d{3,4}$/', $vals['cvc'])) {
        $errors['cvc'] = '3–4 digits';
    }

    if (empty($items)) {
        $errors['delivery'] = 'Your cart is empty.';
    }

    $deliveryFee = 0.00;
    if ($vals['delivery'] === 'Standard (৳ 60, 2-3 days)') {
        $deliveryFee = 60.00;
    } elseif ($vals['delivery'] === 'Express (৳ 120, 24-48 hours)') {
        $deliveryFee = 120.00;
    }

    $vat = round($subtotal * 0.05, 2);
    $total = $subtotal + $deliveryFee + $vat;

    $hasError = array_filter($errors);

    if (!$hasError) {
        $conn->begin_transaction();

        try {
            $uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
            $order_code = 'ORD-' . strtoupper(bin2hex(random_bytes(3)));

            $st = $conn->prepare("
                INSERT INTO orders
                (order_code, user_id, full_name, phone, email, address, city, postcode, delivery,
                 subtotal, delivery_fee, vat, total)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $st->bind_param(
                "sisssssssdddd",
                $order_code,
                $uid,
                $vals['full_name'],
                $vals['phone'],
                $vals['email'],
                $vals['address'],
                $vals['city'],
                $vals['postcode'],
                $vals['delivery'],
                $subtotal,
                $deliveryFee,
                $vat,
                $total
            );

            $st->execute();
            $newOrderId = $st->insert_id;
            $st->close();

            if (!empty($items)) {
                $sti = $conn->prepare("
                    INSERT INTO order_items (order_id, product_id, name, qty, unit_price, line_total)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                foreach ($items as $it) {
                    $pid  = (int)$it['pid'];
                    $name = (string)$it['name'];
                    $qty  = (int)$it['qty'];
                    $unit = (float)$it['unit'];
                    $line = (float)$it['total'];

                    $sti->bind_param("iisidd", $newOrderId, $pid, $name, $qty, $unit, $line);
                    $sti->execute();
                }

                $sti->close();
            }

            $conn->commit();

            $_SESSION['cart'] = [];
            $success = true;
            $orderId = $order_code;

        } catch (Throwable $e) {
            $conn->rollback();
            $errors['delivery'] = 'Could not place order: ' . $e->getMessage();
        }
    }
}

$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=1200">
    <title>Supershop – Payment</title>
    <link rel="stylesheet" href="../css/payment.css">
    <style>
        .notice{margin:12px 0;padding:12px;border-radius:10px}
        .success{background:#e8fff0;border:1px solid #bfe6cc;color:#0a7a2f}
        .btn{display:inline-block;padding:8px 12px;border-radius:8px;border:1px solid #ccc;background:#fff;text-decoration:none}
        .btn-primary{background:#1517ff;color:#fff;border:0}
    </style>
</head>
<body>

<?php if ($success): ?>
    <div class="container">
        <div class="card topbar">
            <div class="brand">Payment</div>
            <div class="top-actions">
                <a href="dashboard.php" class="btn-icon" title="Back to Products">⬅️</a>
                <a href="logout.php" class="btn" title="Logout">🚪 Logout</a>
            </div>
        </div>

        <div class="notice success">
            <h2 style="margin:0 0 6px 0;">Payment successful ✅</h2>
            <p style="margin:0;">Thank you! Your order <strong><?= e($orderId ?? '') ?></strong> has been placed successfully.</p>
        </div>

        <div style="display:flex; gap:8px; margin-top:8px;">
            <a class="btn" href="dashboard.php">← Back to shop</a>
            <a class="btn-primary" href="orderlist.php">View my orders</a>
        </div>
    </div>
<?php else: ?>
    <form method="post" action="<?php echo e($_SERVER['PHP_SELF']); ?>">
        <input type="hidden" name="csrf" value="<?php echo e($csrf); ?>">

        <div class="container">
            <div class="card topbar">
                <div class="brand">Payment</div>
                <div class="top-actions">
                    <a href="dashboard.php" class="btn-icon" title="Back to Products">⬅️</a>
                    <a href="logout.php" class="btn" title="Logout">🚪 Logout</a>
                </div>
            </div>

            <div class="card stepper">
                <div class="step done">Cart</div>
                <div class="step done">Details</div>
                <div class="step active">Payment</div>
                <div class="step">Confirmation</div>
            </div>

            <div class="layout-2col">

                <div class="card pay-form">
                    <div class="section">
                        <div class="section-title">Contact</div>
                        <div class="row">
                            <div class="field">
                                <div class="label">Full name</div>
                                <input class="input" type="text" name="full_name" value="<?php echo e($vals['full_name']); ?>">
                                <span style="color:#ff6b6b;">&nbsp;<?php echo e($errors['full_name']); ?></span>
                            </div>
                            <div class="field">
                                <div class="label">Phone</div>
                                <input class="input" type="tel" name="phone" value="<?php echo e($vals['phone']); ?>">
                                <span style="color:#ff6b6b;">&nbsp;<?php echo e($errors['phone']); ?></span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="field">
                                <div class="label">Email (optional)</div>
                                <input class="input" type="email" name="email" value="<?php echo e($vals['email']); ?>">
                                <span style="color:#ff6b6b;">&nbsp;<?php echo e($errors['email']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="section">
                        <div class="section-title">Shipping Address</div>
                        <div class="row">
                            <div class="field">
                                <div class="label">Address line</div>
                                <input class="input" type="text" name="address" value="<?php echo e($vals['address']); ?>">
                                <span style="color:#ff6b6b;">&nbsp;<?php echo e($errors['address']); ?></span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="field">
                                <div class="label">City</div>
                                <input class="input" type="text" name="city" value="<?php echo e($vals['city']); ?>">
                                <span style="color:#ff6b6b;">&nbsp;<?php echo e($errors['city']); ?></span>
                            </div>
                            <div class="field">
                                <div class="label">Postcode</div>
                                <input class="input" type="text" name="postcode" value="<?php echo e($vals['postcode']); ?>">
                                <span style="color:#ff6b6b;">&nbsp;<?php echo e($errors['postcode']); ?></span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="field">
                                <div class="label">Delivery option</div>
                                <select class="input" name="delivery">
                                    <option value="">-- Select --</option>
                                    <option <?php echo ($vals['delivery'] === "Standard (৳ 60, 2-3 days)") ? 'selected' : ''; ?>>Standard (৳ 60, 2-3 days)</option>
                                    <option <?php echo ($vals['delivery'] === "Express (৳ 120, 24-48 hours)") ? 'selected' : ''; ?>>Express (৳ 120, 24-48 hours)</option>
                                </select>
                                <span style="color:#ff6b6b;">&nbsp;<?php echo e($errors['delivery']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="section">
                        <div class="section-title">Payment Method</div>

                        <div class="card-box">
                            <div class="row">
                                <div class="field">
                                    <div class="label">Name on card</div>
                                    <input class="input" type="text" name="card_name" value="<?php echo e($vals['card_name']); ?>">
                                    <span style="color:#ff6b6b;">&nbsp;<?php echo e($errors['card_name']); ?></span>
                                </div>
                            </div>

                            <div class="row">
                                <div class="field">
                                    <div class="label">Card number</div>
                                    <input class="input" type="text" name="card_number" value="<?php echo e($vals['card_number']); ?>">
                                    <span style="color:#ff6b6b;">&nbsp;<?php echo e($errors['card_number']); ?></span>
                                </div>
                            </div>

                            <div class="row">
                                <div class="field">
                                    <div class="label">Expiry</div>
                                    <input class="input" type="text" name="expiry" placeholder="MM/YY" value="<?php echo e($vals['expiry']); ?>">
                                    <span style="color:#ff6b6b;">&nbsp;<?php echo e($errors['expiry']); ?></span>
                                </div>

                                <div class="field">
                                    <div class="label">CVC</div>
                                    <input class="input" type="text" name="cvc" value="<?php echo e($vals['cvc']); ?>">
                                    <span style="color:#ff6b6b;">&nbsp;<?php echo e($errors['cvc']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="purchase">
                        <button type="submit" class="btn-primary">Pay Now</button>
                    </div>
                </div>

                <div class="card order-summary">
                    <div class="section-title">Order Summary</div>

                    <div class="summary-list">
                        <?php if (empty($items)): ?>
                            <div class="summary-item">
                                <div class="s-name">Your cart is empty.</div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($items as $it): ?>
                                <div class="summary-item">
                                    <div class="s-name"><?php echo e($it['name']); ?></div>
                                    <div class="s-qty">×<?php echo (int)$it['qty']; ?></div>
                                    <div class="s-price">৳ <?php echo e(number_format($it['total'], 2)); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="promo">
                        <div class="label">Promo code</div>
                        <div class="promo-row">
                            <input class="input" type="text" name="promo" placeholder="Enter code" value="<?php echo e($vals['promo']); ?>">
                            <div class="btn">Apply</div>
                        </div>
                    </div>

                    <div class="totals">
                        <div class="t-row">
                            <div class="t-label">Subtotal</div>
                            <div class="t-value">৳ <?php echo e(number_format($subtotal, 2)); ?></div>
                        </div>
                        <div class="t-row">
                            <div class="t-label">Delivery</div>
                            <div class="t-value">
                                <?php
                                if ($deliveryFee > 0) echo '৳ ' . e(number_format($deliveryFee, 2));
                                else echo '<span style="color:#999">Select delivery</span>';
                                ?>
                            </div>
                        </div>
                        <div class="t-row">
                            <div class="t-label">VAT (5%)</div>
                            <div class="t-value">৳ <?php echo e(number_format($vat, 2)); ?></div>
                        </div>
                        <div class="t-row total">
                            <div class="t-label">Total</div>
                            <div class="t-value">৳ <?php echo e(number_format($total, 2)); ?></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
<?php endif; ?>

</body>
</html>