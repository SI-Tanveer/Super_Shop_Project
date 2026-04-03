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

function e(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/* change this only if your project folder name is different */
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

/* DELETE PRODUCT */
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
  }
  header("Location: admin_products.php");
  exit;
}

/* LOAD PRODUCTS */
$products = [];
$res = $conn->query("SELECT id, name, price, image, created_at FROM products ORDER BY id DESC");
if ($res) {
  $products = $res->fetch_all(MYSQLI_ASSOC);
  $res->close();
}

$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Product Listing • Supershop</title>
<link rel="stylesheet" href="../css/pro.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
  .product .thumb{
    width:100%;
    aspect-ratio:1/1;
    border-radius:10px;
    overflow:hidden;
    background:#0a112a;
    border:1px solid var(--border);
    margin-bottom:8px
  }
  .product .thumb img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block
  }
  .form-card{
    max-width:520px;
    margin:20px 0;
    padding:16px;
    border:1px solid #ddd;
    border-radius:8px;
    background:#fff
  }
  .row{
    margin-bottom:12px;
    display:flex;
    flex-direction:column
  }
  .row label{
    margin-bottom:6px;
    font-weight:600
  }
  .row input{
    padding:8px;
    border:1px solid #aaa;
    border-radius:6px
  }
  .error{
    color:#b00020;
    font-size:.9rem;
    margin-top:4px
  }
</style>
</head>
<body>
<header class="site">
  <nav class="nav">
    <a href="admin.php" class="logo">Admin<span>Panel</span></a>
    <a href="home.php">Home</a>
    <a class="active" href="admin_products.php">Products</a>
    <a href="order.php">Orders</a>
    <a href="user.php">Users</a>
    <a href="message.php">Messages</a>
    <div class="icons">
      <a href="logout.php" class="btn logout-btn"><i class="fas fa-right-from-bracket"></i> Logout</a>
      <button id="user-btn" class="icon-btn" aria-label="User"><i class="fas fa-user"></i></button>
    </div>
  </nav>
</header>

<div class="container">
  <div class="layout">
    <aside class="sidebar card">
      <h3>Add Product</h3>
      <form method="post" class="form-card" novalidate>
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <div class="row">
          <label for="name">Name</label>
          <input type="text" id="name" name="name" minlength="2" maxlength="80" required>
        </div>
        <div class="row">
          <label for="price">Price (৳)</label>
          <input type="number" id="price" name="price" min="0" step="0.01" required>
        </div>
        <div class="row">
          <label for="image">Image</label>
          <input type="text" id="image" name="image" placeholder="uploads/example.jpg" disabled>
        </div>

        <a href="addpro.php" class="btn ghost" style="margin-left:8px;">+ Add Product</a>
      </form>
    </aside>

    <main>
      <div class="header-row"><h2>Products</h2></div>

      <div class="grid cols-3">
        <?php foreach($products as $p): ?>
          <div class="card product">
            <div class="thumb">
              <img src="<?= e(img_url_from_db((string)$p['image'])) ?>" alt="<?= e((string)$p['name']) ?>" onerror="this.src='<?= e($BASE_URL . 'project images/placeholder.png') ?>'">
            </div>

            <strong><?= e((string)$p['name']) ?></strong>
            <span class="muted">৳<?= number_format((float)$p['price'], 2) ?></span>

            <div class="actions">
              <a class="btn" href="update.php?id=<?= e((string)$p['id']) ?>">Update</a>
              <a class="btn danger" href="?delete=<?= e((string)$p['id']) ?>" onclick="return confirm('Delete this product?')">Delete</a>
            </div>
          </div>
        <?php endforeach; ?>

        <?php if (empty($products)): ?>
          <p class="muted">No products found.</p>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
</body>
</html>