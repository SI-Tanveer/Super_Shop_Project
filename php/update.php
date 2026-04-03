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
function sanitize_filename(string $name): string {
  $name = preg_replace('/[^\p{L}\p{N}\._-]+/u', '-', $name);
  $name = trim($name, '.- _');
  return $name === '' ? 'file' : $name;
}


$PROJECT_BASE = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');


$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['id'] ?? 0);
if ($id <= 0) { header('Location: admin_products.php'); exit; }

$stmt = $conn->prepare("SELECT id, name, price, image, created_at FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$product) { header('Location: admin_products.php'); exit; }


$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
  if (!isset($_POST['csrf']) || !hash_equals($csrf, (string)$_POST['csrf'])) {
    $errors['csrf'] = 'Invalid request.';
  }

  $name = trim((string)($_POST['name'] ?? $product['name']));
  if ($name === '')              $errors['name']  = 'Product name is required.';
  elseif (mb_strlen($name) < 2)  $errors['name']  = 'Name must be at least 2 characters.';
  elseif (mb_strlen($name) > 80) $errors['name']  = 'Name must be less than 80 characters.';

  $priceRaw = trim((string)($_POST['price'] ?? ''));
  if ($priceRaw === '')           $errors['price'] = 'Price is required.';
  elseif (!is_numeric($priceRaw)) $errors['price'] = 'Enter a valid number.';
  else {
    $price = round((float)$priceRaw, 2);
    if ($price < 0) $errors['price'] = 'Enter a non-negative price.';
  }


  $imagePath = $product['image']; 
  if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $f = $_FILES['image'];
    if ($f['error'] !== UPLOAD_ERR_OK) {
      $errors['image'] = 'Upload failed (code ' . (int)$f['error'] . ').';
    } elseif ($f['size'] > 2_000_000) {
      $errors['image'] = 'Max file size is 2MB.';
    } else {
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $mime  = $finfo->file($f['tmp_name']) ?: '';
      $info  = @getimagesize($f['tmp_name']);
      $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
      ];
      if (!$info || !isset($allowed[$mime])) {
        $errors['image'] = 'Only JPG, PNG, GIF, or WEBP allowed.';
      } else {
        $ext      = $allowed[$mime];
        $safeBase = sanitize_filename(pathinfo($f['name'], PATHINFO_FILENAME));
        $final    = $safeBase . '_' . time() . '.' . $ext;

        $projectRoot = realpath(__DIR__ . '/..') ?: (__DIR__ . '/..');
        $uploadDir   = $projectRoot . '/uploads';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
          $errors['image'] = 'Failed to create uploads directory.';
        } else {
          $dest = $uploadDir . '/' . $final;
          if (!is_uploaded_file($f['tmp_name'])) {
            $errors['image'] = 'Invalid upload.';
          } elseif (!move_uploaded_file($f['tmp_name'], $dest)) {
            $errors['image'] = 'Failed to save image.';
          } else {
            @chmod($dest, 0644);
   
            $imagePath = $PROJECT_BASE . '/uploads/' . $final;
          }
        }
      }
    }
  }

  if (!$errors) {
    $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, image = ? WHERE id = ? LIMIT 1");
    $stmt->bind_param("sdsi", $name, $price, $imagePath, $id);
    $stmt->execute();
    $stmt->close();

    // rotate CSRF & redirect
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
    header('Location: admin_products.php'); 
    exit;
  }
}

$conn->close();

$preview = $product['image'] ?: ($PROJECT_BASE . '/project images/placeholder.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Update Product</title>
  <link rel="stylesheet" href="../css/update.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>.error{color:#b00020;font-size:.9rem;margin:4px 0}label span{display:block;margin-bottom:6px;font-weight:600}</style>
</head>
<body>
<header class="site">
  <nav class="nav">
    <a href="admin.php" class="logo">Admin<span>Panel</span></a>
    <a href="home.php">Home</a>
    <a href="admin_products.php" class="active">Products</a>
    <a href="order.php">Orders</a>
    <a href="user.php">Users</a>
  </nav>
</header>

<main class="container">
  <section class="update-product card">
    <h1 class="title">Update Product</h1>
    <?php if(isset($errors['csrf'])): ?><div class="error"><?= e($errors['csrf']) ?></div><?php endif; ?>
    <?php if(isset($errors['name'])): ?><div class="error"><?= e($errors['name']) ?></div><?php endif; ?>
    <?php if(isset($errors['price'])): ?><div class="error"><?= e($errors['price']) ?></div><?php endif; ?>
    <?php if(isset($errors['image'])): ?><div class="error"><?= e($errors['image']) ?></div><?php endif; ?>

    <!-- Keep id in the action URL so $_GET['id'] is present on POST -->
    <form method="post" enctype="multipart/form-data" class="form" novalidate
          action="update.php?id=<?= e((string)$id) ?>">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="id" value="<?= e((string)$id) ?>">

      <div class="preview" style="margin-bottom:10px">
        <img src="<?= e($preview) ?>" alt="Preview" style="max-width:200px">
      </div>

      <label><span>Product Name</span>
        <input type="text" name="name" value="<?= e($product['name']) ?>" minlength="2" maxlength="80" required>
      </label>

      <label><span>Price (৳)</span>
        <input type="number" name="price" min="0" step="0.01" value="<?= e((string)$product['price']) ?>" required>
      </label>

      <label><span>Replace Image (optional)</span>
        <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif,.webp">
      </label>

      <div class="actions" style="margin-top:10px">
        <button type="submit" class="btn primary"><i class="fa-solid fa-save"></i> Update Product</button>
        <a href="admin_products.php" class="btn ghost"><i class="fa-solid fa-arrow-left"></i> Back</a>
      </div>
    </form>
  </section>
</main>
</body>
</html>
