<?php
declare(strict_types=1);

session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

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

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

function e(?string $s): string {
  return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function test_input(string $data): string {
  $data = trim($data);
  $data = stripslashes($data);
  return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/* project base url */
$BASE_URL = '/Full_project/Supershop_webtech_project/';

function img_url_from_db(?string $p): string {
  global $BASE_URL;

  if (!$p || $p === '') {
    return $BASE_URL . 'project images/placeholder.png';
  }

  if (preg_match('~^https?://~i', $p)) {
    return $p;
  }

  $p = preg_replace('~^(\.\./|\./)+~', '', $p);
  return rtrim($BASE_URL, '/') . '/' . ltrim($p, '/');
}

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

function cart_count(): int {
  return array_sum($_SESSION['cart'] ?? []);
}

$search = '';
$searchErr = '';
$flash = ['error' => '', 'success' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if (!isset($_POST['csrf']) || !hash_equals($csrf, (string)$_POST['csrf'])) {
    $flash['error'] = 'Invalid request.';
  } else {
    if ($action === 'search') {
      $search = isset($_POST['search']) ? test_input((string)$_POST['search']) : '';

      if ($search === '') {
        $searchErr = 'Please enter a search term.';
      } elseif (mb_strlen($search) < 2) {
        $searchErr = 'Search must be at least 2 characters.';
      } elseif (!preg_match("/^[\\p{L}\\p{N}\\s\-\&\,\.\(\)\/]+$/u", $search)) {
        $searchErr = 'Only letters, numbers and basic punctuation are allowed.';
      }

    } elseif ($action === 'add_to_cart') {
      $pid = (int)($_POST['product_id'] ?? 0);
      $qty = max(1, (int)($_POST['qty'] ?? 1));

      if ($pid <= 0) {
        $flash['error'] = 'Invalid product.';
      } else {
        try {
          $conn->begin_transaction();

          $stmt = $conn->prepare("SELECT id, stock FROM products WHERE id = ? FOR UPDATE");
          $stmt->bind_param("i", $pid);
          $stmt->execute();
          $row = $stmt->get_result()->fetch_assoc();
          $stmt->close();

          if (!$row) {
            $conn->rollback();
            $flash['error'] = 'Product not found.';
          } elseif ((int)$row['stock'] < $qty) {
            $conn->rollback();
            $flash['error'] = 'Not enough stock.';
          } else {
            $newStock = (int)$row['stock'] - $qty;
            $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
            $stmt->bind_param("ii", $newStock, $pid);
            $stmt->execute();
            $stmt->close();
            $conn->commit();

            if (!isset($_SESSION['cart'][$pid])) $_SESSION['cart'][$pid] = 0;
            $_SESSION['cart'][$pid] += $qty;

            $flash['success'] = 'Added to cart.';
          }
        } catch (Throwable $e) {
          try { $conn->rollback(); } catch (Throwable $e2) {}
          $flash['error'] = 'Could not add to cart. Try again.';
        }
      }

    } elseif ($action === 'update_cart') {
      $pid = (int)($_POST['product_id'] ?? 0);
      $delta = (int)($_POST['delta'] ?? 0);

      if ($pid <= 0 || $delta === 0 || !isset($_SESSION['cart'][$pid])) {
        $flash['error'] = 'Invalid cart update.';
      } else {
        $current = (int)$_SESSION['cart'][$pid];

        if ($delta > 0) {
          try {
            $conn->begin_transaction();

            $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $pid);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row) {
              $conn->rollback();
              $flash['error'] = 'Product not found.';
            } elseif ((int)$row['stock'] < $delta) {
              $conn->rollback();
              $flash['error'] = 'Not enough stock.';
            } else {
              $newStock = (int)$row['stock'] - $delta;
              $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
              $stmt->bind_param("ii", $newStock, $pid);
              $stmt->execute();
              $stmt->close();
              $conn->commit();

              $_SESSION['cart'][$pid] = $current + $delta;
              $flash['success'] = 'Quantity updated.';
            }
          } catch (Throwable $e) {
            try { $conn->rollback(); } catch (Throwable $e2) {}
            $flash['error'] = 'Update failed.';
          }
        } else {
          $deltaAbs = abs($delta);
          $toReduce = min($current, $deltaAbs);

          if ($toReduce <= 0) {
            $flash['error'] = 'Nothing to decrease.';
          } else {
            try {
              $conn->begin_transaction();

              $stmt = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
              $stmt->bind_param("ii", $toReduce, $pid);
              $stmt->execute();
              $stmt->close();
              $conn->commit();

              $_SESSION['cart'][$pid] = $current - $toReduce;
              if ($_SESSION['cart'][$pid] <= 0) unset($_SESSION['cart'][$pid]);

              $flash['success'] = 'Quantity updated.';
            } catch (Throwable $e) {
              try { $conn->rollback(); } catch (Throwable $e2) {}
              $flash['error'] = 'Update failed.';
            }
          }
        }
      }

    } elseif ($action === 'remove_from_cart') {
      $pid = (int)($_POST['product_id'] ?? 0);

      if ($pid > 0 && isset($_SESSION['cart'][$pid])) {
        $qty = (int)$_SESSION['cart'][$pid];

        try {
          $conn->begin_transaction();
          $stmt = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
          $stmt->bind_param("ii", $qty, $pid);
          $stmt->execute();
          $stmt->close();
          $conn->commit();

          unset($_SESSION['cart'][$pid]);
          $flash['success'] = 'Item removed.';
        } catch (Throwable $e) {
          try { $conn->rollback(); } catch (Throwable $e2) {}
          $flash['error'] = 'Remove failed.';
        }
      }

    } elseif ($action === 'clear_cart') {
      if (!empty($_SESSION['cart'])) {
        try {
          $conn->begin_transaction();

          foreach ($_SESSION['cart'] as $pid => $qty) {
            $pid = (int)$pid;
            $qty = (int)$qty;

            if ($pid > 0 && $qty > 0) {
              $stmt = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
              $stmt->bind_param("ii", $qty, $pid);
              $stmt->execute();
              $stmt->close();
            }
          }

          $conn->commit();
          $_SESSION['cart'] = [];
          $flash['success'] = 'Cart cleared.';
        } catch (Throwable $e) {
          try { $conn->rollback(); } catch (Throwable $e2) {}
          $flash['error'] = 'Clear failed.';
        }
      }
    }
  }
}

$where = '';
$params = [];
$types  = '';

if ($search !== '' && !$searchErr) {
  $where = "WHERE name LIKE CONCAT('%', ?, '%') OR COALESCE(details,'') LIKE CONCAT('%', ?, '%')";
  $params[] = $search;
  $params[] = $search;
  $types .= 'ss';
}

$sql = "
  SELECT
    id,
    name,
    price,
    image,
    COALESCE(details, '') AS details,
    COALESCE(stock, 0) AS stock
  FROM products
  $where
  ORDER BY id DESC
";

$stmt = $conn->prepare($sql);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$products = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$cart = $_SESSION['cart'] ?? [];
$cartItems = [];
$cartSubtotal = 0.0;

if (!empty($cart)) {
  $ids = array_keys($cart);
  $ids = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));

  if (!empty($ids)) {
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $typ = str_repeat('i', count($ids));
    $sql = "SELECT id, name, price, image FROM products WHERE id IN ($in)";
    $st2 = $conn->prepare($sql);
    $st2->bind_param($typ, ...$ids);
    $st2->execute();
    $r2 = $st2->get_result();

    while ($row = $r2->fetch_assoc()) {
      $pid = (int)$row['id'];
      $qty = (int)($cart[$pid] ?? 0);
      if ($qty <= 0) continue;

      $line = (float)$row['price'] * $qty;
      $cartItems[] = [
        'id'    => $pid,
        'name'  => (string)$row['name'],
        'price' => (float)$row['price'],
        'qty'   => $qty,
        'image' => (string)$row['image'],
        'total' => $line,
      ];
      $cartSubtotal += $line;
    }

    $st2->close();
  }
}

$conn->close();

$showCart = isset($_GET['cart']) && $_GET['cart'] == '1';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=1200">
  <title> Daily Basket – Customer Portal</title>
  <link rel="stylesheet" href="../css/dashboard.css">
  <style>
    .form-error{color:#ffb3b3;font-size:12px;margin-top:6px}
    .searchbar input.input-error{border-color:#e57373;box-shadow:0 0 0 3px rgba(229,115,115,.2)}
    .notice{margin:8px 0;padding:8px 10px;border-radius:6px}
    .success{background:#e8fff0;color:#0a7a2f;border:1px solid #bfe6cc}
    .error{background:#ffecec;color:#b00020;border:1px solid #ffc7c7}
    .p-qty{width:64px;padding:6px;border:1px solid #aaa;border-radius:6px;margin-left:8px}
    .p-btn button{all:unset;display:inline-block;background:#1517ff;color:#fff;padding:6px 10px;border-radius:8px;cursor:pointer}
    .badge{background:#ff5252;color:#fff;border-radius:999px;padding:0 6px;font-size:12px;margin-left:6px}
    .muted{color:#999}
    .p-pic{width:100%;height:200px;object-fit:cover;border-radius:10px}
    .cart-panel{position:fixed;right:16px;top:80px;width:360px;max-height:70vh;overflow:auto;background:#fff;border:1px solid #ddd;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.12);padding:12px;z-index:9999}
    .cart-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
    .cart-item{display:flex;gap:10px;align-items:center;border-bottom:1px solid #eee;padding:8px 0}
    .cart-item img{width:56px;height:56px;object-fit:cover;border-radius:8px;border:1px solid #eee}
    .cart-item .meta{flex:1}
    .cart-row{display:flex;justify-content:space-between;align-items:center;margin-top:6px}
    .cart-qty form{display:inline}
    .cart-qty button{border:1px solid #ccc;padding:2px 8px;border-radius:6px;background:#fff;cursor:pointer}
    .cart-actions{display:flex;justify-content:space-between;gap:8px;margin-top:10px}
    .btn-link{border:0;background:none;color:#1517ff;cursor:pointer;padding:0}
    .btn-danger{border:1px solid #ff8a80;background:#ffebee;color:#c62828;border-radius:6px;padding:6px 10px;cursor:pointer}
    .btn-outline{border:1px solid #ccc;background:#fff;border-radius:6px;padding:6px 10px;cursor:pointer}
    .cart-empty{color:#777;padding:10px 0}
  </style>
</head>
<body>
<div class="container">

  <div class="card topbar">
    <div class="brand">BOOB Supershop</div>
    <div class="top-actions">
      <a class="btn-icon" title="Cart" href="?cart=1">
        <div class="emoji">🛒</div>
        <div class="badge"><?= (int)cart_count() ?></div>
      </a>
      <div class="btn-icon" title="Account">
        <div class="emoji">👤</div>
      </div>
      <a href="logout.php" class="btn-icon" title="Logout">
        <div class="emoji">🚪</div>
      </a>
    </div>
  </div>

  <?php if ($flash['error']): ?>
    <div class="notice error"><?= e($flash['error']) ?></div>
  <?php elseif ($flash['success']): ?>
    <div class="notice success"><?= e($flash['success']) ?></div>
  <?php endif; ?>

  <form class="card searchbar" method="post" action="">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="action" value="search">
    <input
      type="text"
      name="search"
      placeholder="Search products…"
      value="<?= e($search) ?>"
      class="<?= $searchErr ? 'input-error' : '' ?>">
    <button class="btn" type="submit">Search</button>
  </form>

  <?php if ($searchErr): ?>
    <div class="form-error"><?= e($searchErr) ?></div>
  <?php endif; ?>

  <div class="layout">

    <div class="card sidebar">
      <div class="side-title">Filters</div>

      <div class="side-block">
        <div class="side-label">Category</div>
        <div class="side-buttons">
          <div class="chip">All</div>
          <div class="chip">Fruits</div>
          <div class="chip">Vegetables</div>
          <div class="chip">Fish</div>
          <div class="chip">Meat</div>
        </div>
      </div>

      <div class="side-block">
        <div class="side-label">Sort by</div>
        <div class="side-buttons">
          <div class="chip">Popular</div>
          <div class="chip">Price: Low</div>
          <div class="chip">Price: High</div>
          <div class="chip">Top Rated</div>
        </div>
      </div>
    </div>

    <div class="card products">
      <div class="products-head">
        <div class="products-title"><strong>Products</strong></div>
        <div class="muted"><?= count($products) ?> items</div>
      </div>

      <div class="grid">
        <?php if (empty($products)): ?>
          <p class="muted" style="padding:12px">No products found.</p>
        <?php else: ?>
          <?php foreach ($products as $p): ?>
            <div class="p-card">
              <img class="p-pic" src="<?= e(img_url_from_db($p['image'] ?? null)) ?>" alt="<?= e($p['name'] ?? '') ?>">
              <div class="p-body">
                <div class="p-name"><?= e($p['name'] ?? '') ?></div>
                <div class="p-meta"><?= e((string)($p['details'] ?? '')) ?></div>
                <div class="p-price">৳ <?= e(number_format((float)($p['price'] ?? 0), 2)) ?></div>

                <div class="p-btn">
                  <?php if ((int)($p['stock'] ?? 0) <= 0): ?>
                    <span class="muted">Out of stock</span>
                  <?php else: ?>
                    <form method="post" action="" style="display:flex;align-items:center">
                      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                      <input type="hidden" name="action" value="add_to_cart">
                      <input type="hidden" name="product_id" value="<?= e((string)$p['id']) ?>">
                      <label style="margin-right:6px" for="qty<?= (int)$p['id'] ?>">Qty</label>
                      <input id="qty<?= (int)$p['id'] ?>" class="p-qty" type="number" name="qty" min="1" max="<?= (int)$p['stock'] ?>" value="1">
                      <button type="submit" title="Add to Cart">Add to Cart</button>
                    </form>
                  <?php endif; ?>
                </div>

                <div class="muted" style="margin-top:6px">Stock: <?= (int)($p['stock'] ?? 0) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="card purchase">
    <a href="payment.php" class="btn-primary">Proceed to Purchase</a>
  </div>

  <?php if ($showCart): ?>
    <div class="cart-panel">
      <div class="cart-head">
        <strong>Your Cart</strong>
        <a class="btn-link" href="dashboard.php">✕ Close</a>
      </div>

      <?php if (empty($cartItems)): ?>
        <div class="cart-empty">Cart is empty.</div>
      <?php else: ?>
        <?php foreach ($cartItems as $ci): ?>
          <div class="cart-item">
            <img src="<?= e(img_url_from_db($ci['image'])) ?>" alt="<?= e($ci['name']) ?>">
            <div class="meta">
              <div><strong><?= e($ci['name']) ?></strong></div>
              <div class="cart-row">
                <div>৳ <?= e(number_format($ci['price'], 2)) ?> × <?= (int)$ci['qty'] ?> = <strong>৳ <?= e(number_format($ci['total'], 2)) ?></strong></div>
                <div class="cart-qty">
                  <form method="post" action="?cart=1" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="action" value="update_cart">
                    <input type="hidden" name="product_id" value="<?= (int)$ci['id'] ?>">
                    <input type="hidden" name="delta" value="-1">
                    <button title="Decrease">–</button>
                  </form>

                  <form method="post" action="?cart=1" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="action" value="update_cart">
                    <input type="hidden" name="product_id" value="<?= (int)$ci['id'] ?>">
                    <input type="hidden" name="delta" value="1">
                    <button title="Increase">+</button>
                  </form>
                </div>
              </div>

              <div class="cart-row" style="margin-top:6px">
                <form method="post" action="?cart=1">
                  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                  <input type="hidden" name="action" value="remove_from_cart">
                  <input type="hidden" name="product_id" value="<?= (int)$ci['id'] ?>">
                  <button class="btn-danger" type="submit">Remove</button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

        <div class="cart-row" style="margin-top:10px">
          <div><strong>Subtotal</strong></div>
          <div><strong>৳ <?= e(number_format($cartSubtotal, 2)) ?></strong></div>
        </div>

        <div class="cart-actions">
          <form method="post" action="?cart=1">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="clear_cart">
            <button class="btn-outline" type="submit">Clear Cart</button>
          </form>
          <a class="btn-outline" href="payment.php">Go to Payment →</a>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div>
</body>
</html>