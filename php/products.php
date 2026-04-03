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
 

function e(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$docRootFs = rtrim(str_replace('\\','/', (string)($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
$projDirFs = rtrim(str_replace('\\','/', (string)realpath(__DIR__ . '/..')), '/');
$PROJECT_BASE = '';
if ($docRootFs !== '' && $projDirFs !== '' && str_starts_with($projDirFs, $docRootFs)) {
  $PROJECT_BASE = substr($projDirFs, strlen($docRootFs));
  if ($PROJECT_BASE === '') $PROJECT_BASE = '/';
}

function img_url(?string $p): string {
  global $PROJECT_BASE;

  if (!$p || $p === '') return rtrim($PROJECT_BASE, '/') . '/project images/placeholder.png';
 

  if (preg_match('~^https?://~i', $p)) return $p;
 
  
  if ($PROJECT_BASE !== '' && $PROJECT_BASE !== '/' && str_starts_with($p, $PROJECT_BASE . '/')) {
    return $p;
  }
 
  if (preg_match('~^/uploads/~', $p)) {
  
    return rtrim($PROJECT_BASE, '/') . $p;
  }
  if (preg_match('~^uploads/~', $p)) {
    
    return rtrim($PROJECT_BASE, '/') . '/' . $p;
  }
  if (preg_match('~^\.\./uploads/~', $p)) {

    return rtrim($PROJECT_BASE, '/') . '/uploads/' . substr($p, 11);
  }
  if (preg_match('~^\./uploads/~', $p)) {
  
    return rtrim($PROJECT_BASE, '/') . '/uploads/' . substr($p, 10);
  }
 
  if (preg_match('~^\.\./project images/~', $p)) {
    return rtrim($PROJECT_BASE, '/') . '/project images/' . substr($p, 17);
  }
  if (preg_match('~^\./project images/~', $p)) {
    return rtrim($PROJECT_BASE, '/') . '/project images/' . substr($p, 16);
  }
  if (preg_match('~^/project images/~', $p)) {
    return rtrim($PROJECT_BASE, '/') . $p;
  }
  if (preg_match('~^project images/~', $p)) {
    return rtrim($PROJECT_BASE, '/') . '/' . $p;
  }
 
  
  if (preg_match('~^\.\./~', $p)) return rtrim($PROJECT_BASE, '/') . '/' . substr($p, 3);
  if (preg_match('~^\./~', $p))   return rtrim($PROJECT_BASE, '/') . '/' . substr($p, 2);
 

  return rtrim($PROJECT_BASE, '/') . '/' . ltrim($p, '/');
}
 

$sql = "
  SELECT
    id,
    name,
    price,
    image,
    COALESCE(details, '') AS description,
    COALESCE(stock, 0)    AS stock,
    created_at
  FROM products
  ORDER BY id DESC
";
$res = $conn->query($sql);
$products = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
if ($res) $res->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Products • Employee Panel</title>
<link rel="stylesheet" href="../css/employee_products.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
<header class="site">
<nav class="nav">
<a href="employee_home.php" class="logo">Employee<span>Panel</span></a>
<a href="employee_home.php">Home</a>
<a href="orders.php">Orders</a>
<a href="products.php" class="active">Products</a>
<a href="messages.php">Messages</a>
<a href="setting.php">Settings</a>
<div class="icons">
  <a href="logout.php" class="btn logout-btn"><i class="fas fa-right-from-bracket"></i> Logout</a>
        <button id="user-btn" class="icon-btn" aria-label="User"><i class="fas fa-user"></i></button>
</div>
</nav>
</header>
 
<main class="container">
<h2 class="title">Products List</h2>
 
  <section class="products-grid">
<?php if (empty($products)): ?>
<p class="muted">No products found.</p>
<?php else: ?>
<?php foreach ($products as $p): ?>
<article class="product-card">
<img src="<?= e(img_url($p['image'] ?? null)) ?>" alt="<?= e($p['name'] ?? '') ?>" class="product-image"/>
<div class="product-details">
<h3 class="product-name"><?= e($p['name'] ?? '') ?></h3>
<p class="product-desc"><?= e($p['description'] ?? '') ?></p>
<p class="product-stock">Stock: <?= e((string)($p['stock'] ?? '0')) ?></p>
<p class="product-price">৳<?= e(number_format((float)($p['price'] ?? 0), 2)) ?>/-</p>
<div class="actions">
<a href="product_details.php?id=<?= e((string)($p['id'] ?? '')) ?>" class="btn small">
<i class="fas fa-eye"></i> View
</a>
</div>
</div>
</article>
<?php endforeach; ?>
<?php endif; ?>
</section>
</main>
</body>
</html>
