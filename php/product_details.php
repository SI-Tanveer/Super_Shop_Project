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

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];


function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function preview_path(?string $p): string {
  if (!$p) return '../project images/placeholder.png';
  return $p;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['id'] ?? 0);
if ($id <= 0) { header("Location: products.php"); exit; }


$stmt = $conn->prepare("
  SELECT id, name, price, image,
         COALESCE(details, '') AS details,
         COALESCE(stock, 0)    AS stock,
         created_at
  FROM products
  WHERE id = ?
  LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) { header("Location: products.php"); exit; }


$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  
  if (!isset($_POST['csrf']) || !hash_equals($csrf, (string)$_POST['csrf'])) {
    $errors['csrf'] = 'Invalid request.';
  }

  $stockRaw = trim((string)($_POST['stock'] ?? ''));
  if ($stockRaw === '') {
    $errors['stock'] = 'Stock is required.';
  } elseif (!ctype_digit($stockRaw)) {
   
    $errors['stock'] = 'Enter a valid non-negative integer.';
  } else {
    $stock = (int)$stockRaw;
  }

  if (!$errors) {
    $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ? LIMIT 1");
    $stmt->bind_param("ii", $stock, $id);
    $stmt->execute();
    $stmt->close();

   
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
    $csrf = $_SESSION['csrf'];

    $stmt = $conn->prepare("
      SELECT id, name, price, image,
             COALESCE(details, '') AS details,
             COALESCE(stock, 0)    AS stock,
             created_at
      FROM products
      WHERE id = ?
      LIMIT 1
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $success = 'Stock updated successfully.';
  }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= e($product['name']) ?> • Product Details</title>
  <link rel="stylesheet" href="../css/employee_product_details.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    .notice{margin:8px 0; padding:8px 10px; border-radius:6px;}
    .success{background:#e8fff0; color:#0a7a2f; border:1px solid #bfe6cc;}
    .error{background:#ffecec; color:#b00020; border:1px solid #ffc7c7;}
    .stock-form{margin-top:12px; display:flex; gap:8px; align-items:center; flex-wrap:wrap}
    .stock-form input{padding:8px; border:1px solid #aaa; border-radius:6px; width:120px}
    .btn.primary{padding:8px 12px; border:0; border-radius:8px; background:#1517ff; color:#fff; cursor:pointer}
    .btn.ghost{padding:8px 12px; border:1px solid #ccc; border-radius:8px; background:#fff; text-decoration:none}
  </style>
</head>
<body>
<header class="site">
  <nav class="nav">
    <a href="employee_home.php" class="logo">Employee<span>Panel</span></a>
    <a href="employee_home.php">Home</a>
    <a href="orders.php">Orders</a>
    <a href="products.php" class="active">Products</a>
    <a href="messages.php">Messages</a>
    <a href="users.php">Users</a>
    <div class="icons">
      <a href="../logout.php" class="btn logout-btn"><i class="fas fa-right-from-bracket"></i> Logout</a>
      <button id="user-btn" class="icon-btn"><i class="fas fa-user"></i></button>
    </div>
  </nav>
</header>

<main class="container">
  <section class="product-detail-card">
    <img src="<?= e(preview_path($product['image'])) ?>" alt="<?= e($product['name']) ?>" class="product-image"/>
    <div class="product-info">
      <h2 class="product-name"><?= e($product['name']) ?></h2>
      <p class="product-desc"><?= e($product['details']) ?></p>
      <p class="product-price">$<?= e(number_format((float)$product['price'], 2)) ?>/-</p>

      <?php if(!empty($success)): ?>
        <div class="notice success"><?= e($success) ?></div>
      <?php endif; ?>
      <?php if(isset($errors['csrf'])): ?>
        <div class="notice error"><?= e($errors['csrf']) ?></div>
      <?php endif; ?>
      <?php if(isset($errors['stock'])): ?>
        <div class="notice error"><?= e($errors['stock']) ?></div>
      <?php endif; ?>

   
      <p class="product-stock">Current Stock: <strong><?= e((string)$product['stock']) ?></strong></p>

    
      <form method="post" class="stock-form" novalidate action="product_details.php?id=<?= e((string)$product['id']) ?>">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="id" value="<?= e((string)$product['id']) ?>">
        <label for="stock" style="font-weight:600">Update Stock</label>
        <input type="number" id="stock" name="stock" min="0" step="1" value="<?= e((string)$product['stock']) ?>" required>
        <button type="submit" class="btn primary"><i class="fa-solid fa-boxes-stacked"></i> Save</button>
        <a href="products.php" class="btn ghost"><i class="fas fa-arrow-left"></i> Back to Products</a>
      </form>

    </div>
  </section>
</main>
</body>
</html>
