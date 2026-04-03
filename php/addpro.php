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
  exit('Connection failed: Database connection failed.');
}

if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

$errors = [];
$old = [
  'name'  => $_POST['name'] ?? '',
  'price' => $_POST['price'] ?? '',
];

function e(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if (!isset($_POST['csrf']) || !hash_equals($csrf, (string)$_POST['csrf'])) {
    $errors['csrf'] = 'Invalid request. Please reload the page.';
  }

  $name = trim((string)($_POST['name'] ?? ''));
  if ($name === '') {
    $errors['name'] = 'Product name is required.';
  } elseif (mb_strlen($name) < 2) {
    $errors['name'] = 'Name must be at least 2 characters.';
  } elseif (mb_strlen($name) > 80) {
    $errors['name'] = 'Name must be 80 characters or less.';
  }

  $priceRaw = (string)($_POST['price'] ?? '');
  if ($priceRaw === '') {
    $errors['price'] = 'Price is required.';
  } elseif (!is_numeric($priceRaw) || (float)$priceRaw < 0) {
    $errors['price'] = 'Enter a valid non-negative number.';
  } else {
    $price = round((float)$priceRaw, 2);
  }

  $imagePath = '';

  if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
    $errors['image'] = 'Image is required.';
  } else {
    $f = $_FILES['image'];

    if ($f['error'] !== UPLOAD_ERR_OK) {
      $errors['image'] = 'Image upload failed (code ' . $f['error'] . ').';
    } elseif ($f['size'] > 10 * 1024 * 1024) {
      $errors['image'] = 'Max file size is 10MB.';
    } else {
      $mime = mime_content_type($f['tmp_name']);
      $ext  = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));

      $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
      $allowedExts  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

      if (!in_array($mime, $allowedMimes, true) || !in_array($ext, $allowedExts, true)) {
        $errors['image'] = 'Only JPG, PNG, GIF, or WEBP allowed.';
      } else {
        $uploadDir = '/opt/lampp/htdocs/Full_project/Supershop_webtech_project/uploads';

        if (!is_dir($uploadDir)) {
          $errors['image'] = 'Uploads folder not found. Please create it manually.';
        } elseif (!is_writable($uploadDir)) {
          $errors['image'] = 'Uploads folder is not writable.';
        } else {
          $baseName = pathinfo($f['name'], PATHINFO_FILENAME);
          $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);

          if ($baseName === '' || $baseName === null) {
            $baseName = 'product';
          }

          $final = $baseName . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
          $dest  = $uploadDir . '/' . $final;

          if (!move_uploaded_file($f['tmp_name'], $dest)) {
            $errors['image'] = 'Failed to save image.';
          } else {
            $imagePath = 'uploads/' . $final;
          }
        }
      }
    }
  }

  if (!$errors) {
    $stmt = $conn->prepare("INSERT INTO products (name, price, image) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $name, $price, $imagePath);

    if ($stmt->execute()) {
      $stmt->close();
      header('Location: admin_products.php');
      exit;
    } else {
      $errors['db'] = 'Failed to add product to the database.';
      $stmt->close();
    }
  }
}

$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Add Product</title>
  <link rel="stylesheet" href="../css/pro.css" />
  <style>
    .error{color:#b00020;font-size:.9rem;margin-top:4px}
    .row{margin-bottom:12px;display:flex;flex-direction:column}
    .row label{margin-bottom:6px;font-weight:600}
    .row input{padding:8px;border:1px solid #aaa;border-radius:6px}
  </style>
</head>
<body>
<header class="site">
  <nav class="nav">
    <a href="admin.php" class="logo">Admin<span>Panel</span></a>
    <a href="admin_products.php">Products</a>
  </nav>
</header>

<div class="container">
  <div class="card">
    <h2>Add New Product</h2>

    <?php if (isset($errors['csrf'])): ?>
      <div class="error"><?= e($errors['csrf']) ?></div>
    <?php endif; ?>

    <?php if (isset($errors['db'])): ?>
      <div class="error"><?= e($errors['db']) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" novalidate>
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

      <div class="row">
        <label>Product Name</label>
        <input type="text" name="name" value="<?= e($old['name']) ?>" minlength="2" maxlength="80" required />
        <?php if (isset($errors['name'])): ?><div class="error"><?= e($errors['name']) ?></div><?php endif; ?>
      </div>

      <div class="row">
        <label>Price ($)</label>
        <input type="number" name="price" min="0" step="0.01" value="<?= e($old['price']) ?>" required />
        <?php if (isset($errors['price'])): ?><div class="error"><?= e($errors['price']) ?></div><?php endif; ?>
      </div>

      <div class="row">
        <label>Image</label>
        <input type="file" name="image" accept="image/*" required />
        <?php if (isset($errors['image'])): ?><div class="error"><?= e($errors['image']) ?></div><?php endif; ?>
      </div>

      <button type="submit" class="btn primary">Add Product</button>
    </form>
  </div>
</div>
</body>
</html>