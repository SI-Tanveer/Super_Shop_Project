<?php
session_start();
$name  = "Admin User";
$email = "admin@example.com";
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Panel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
  <header class="site">
    <nav class="nav">
      <a href="admin.php" class="logo">Admin<span>Panel</span></a>
      <a href="home.php">Home</a>
      <a href="admin_products.php">Products</a>
      <a href="order.php">Orders</a>
      <a href="user.php">Users</a>
      <a href="message.php">Messages</a>
      <div class="icons">
        <a href="logout.php" class="btn logout-btn"><i class="fas fa-right-from-bracket"></i> Logout</a>
        <button id="user-btn" class="icon-btn" aria-label="User"><i class="fas fa-user"></i></button>
      </div>
    </nav>
  </header>

  <aside class="profile" id="profilePanel" aria-hidden="true">
    <div class="profile-inner">
      <img src="../images/pic-4.png" alt="Profile" class="avatar">
      <div class="profile-info">
        <h3 class="name"><?= e($name) ?></h3>
        <p class="email muted"><?= e($email) ?></p>
      </div>
      <div class="profile-actions">
        <a href="#" class="btn">Update Profile</a>
        <a href="#" class="btn danger">Logout</a>
      </div>
      <div class="flex-gap">
        <a href="#" class="btn ghost">Login</a>
        <a href="#" class="btn ghost">Register</a>
      </div>
    </div>
  </aside>

  <main class="container" style="padding: 24px;">
    <h2 class="page-title">Welcome to Admin Panel</h2>
  </main>

  <footer class="site-footer">
    <p>Supershop Admin</p>
  </footer>

  <script>
    const btn = document.getElementById('user-btn');
    const panel = document.getElementById('profilePanel');
    if (btn && panel) btn.addEventListener('click', () => {
      panel.setAttribute('aria-hidden', panel.getAttribute('aria-hidden') === 'true' ? 'false' : 'true');
    });
  </script>
</body>
</html>
