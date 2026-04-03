<?php
session_start();

if (!isset($_SESSION['employee_profile'])) {
    $_SESSION['employee_profile'] = [
        'name' => 'Joe Root',
        'email' => 'root@example.com',
        'username' => 'johndoe',
        'phone' => '0123456789',
    ];
}

$profile = &$_SESSION['employee_profile'];
$success = '';
$errors = [];

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';

    if (!$name) $errors['name'] = 'Name is required.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email required.';
    if (!$phone) $errors['phone'] = 'Phone is required.';

    if (!$errors) {
        $profile['name'] = $name;
        $profile['email'] = $email;
        $profile['phone'] = $phone;
        $success = 'Profile updated successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Settings • Employee Panel</title>
    <link rel="stylesheet" href="../css/employee_setting.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
<header class="site">
    <nav class="nav">
        <a href="employee_home.php" class="logo">Employee<span>Panel</span></a>
        <a href="employee_home.php">Home</a>
        <a href="orders.php">Orders</a>
        <a href="products.php">Products</a>
        <a href="messages.php">Messages</a>
        <a href="setting.php" class="active">Settings</a>
        <div class="icons">
           <a href="logout.php" class="btn logout-btn"><i class="fas fa-right-from-bracket"></i> Logout</a>
            <button id="user-btn" class="icon-btn"><i class="fas fa-user"></i></button>
        </div>
    </nav>
</header>

<main class="container">
    <h1 class="title">Profile Settings</h1>

    <?php if ($success): ?>
        <div class="success"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="error"><?= e(implode(', ', $errors)) ?></div>
    <?php endif; ?>

    <form class="settings-form" method="post">
        <label>Name
            <input type="text" name="name" value="<?= e($profile['name']) ?>" required>
        </label>
        <label>Email
            <input type="email" name="email" value="<?= e($profile['email']) ?>" required>
        </label>
        <label>Phone
            <input type="text" name="phone" value="<?= e($profile['phone']) ?>" required>
        </label>
        <button class="btn" type="submit"><i class="fas fa-save"></i> Save Changes</button>
    </form>
</main>
</body>
</html>
