<?php
session_start();
if (!isset($_SESSION['users'])) $_SESSION['users']=[];
$errors=[];$success=false;

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $phone=trim($_POST['phone']??''); $role=trim($_POST['role']??''); $password=$_POST['password']??'';
  if ($name==='') $errors['name']='Full name is required.'; elseif (strlen($name)<3) $errors['name']='Name must be at least 3 characters.';
  if ($email===''||!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors['email']='Enter a valid email address.';
  if ($phone===''||!preg_match('/^\+8801[0-9]{9}$/',$phone)) $errors['phone']='Phone must be +8801XXXXXXXXX.';
  if ($role===''||!in_array($role,['ADMIN','SELLER','CUSTOMER'],true)) $errors['role']='Select a valid role.';
  if ($password===''||strlen($password)<6) $errors['password']='Password must be at least 6 characters.';
  if (!$errors) {
    $newId=empty($_SESSION['users'])?1:(max(array_column($_SESSION['users'],'id'))+1);
    $_SESSION['users'][]=['id'=>$newId,'name'=>htmlspecialchars($name,ENT_QUOTES),'email'=>htmlspecialchars($email,ENT_QUOTES),'phone'=>htmlspecialchars($phone,ENT_QUOTES),'role'=>$role,'joined'=>date('d-M-Y'),'password'=>password_hash($password,PASSWORD_DEFAULT)];
    header("Location: user.php"); exit;
  }
}
function e(string $s): string { return htmlspecialchars($s,ENT_QUOTES,'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Add User • Admin Panel</title>
  <link rel="stylesheet" href="../css/adduser.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>.error{color:#b00020;font-size:.9rem;margin:4px 0}label span{display:block;margin-bottom:6px;font-weight:600}</style>
</head>
<body>
<header class="site">
  <nav class="nav">
    <a href="admin.php" class="logo">Admin<span>Panel</span></a>
    <a href="home.php">Home</a>
    <a href="admin_products.php">Products</a>
    <a href="order.php">Orders</a>
    <a href="user.php" class="active">Users</a>
    <div class="icons">
        <a href="logout.php" class="btn logout-btn"><i class="fas fa-right-from-bracket"></i> Logout</a>
        <button id="user-btn" class="icon-btn" aria-label="User"><i class="fas fa-user"></i></button>
      </div>
  </nav>
</header>

<main class="container">
  <h1 class="title">Add New User</h1>
  <section class="card form-card">
    <form method="POST" class="form" novalidate>
      <label><span>Full Name</span><input type="text" name="name" value="<?= e($_POST['name']??'') ?>" required><?php if(isset($errors['name'])): ?><div class="error"><?= e($errors['name']) ?></div><?php endif; ?></label>
      <label><span>Email</span><input type="email" name="email" value="<?= e($_POST['email']??'') ?>" required><?php if(isset($errors['email'])): ?><div class="error"><?= e($errors['email']) ?></div><?php endif; ?></label>
      <label><span>Phone</span><input type="text" name="phone" placeholder="+8801XXXXXXXXX" value="<?= e($_POST['phone']??'') ?>" required><?php if(isset($errors['phone'])): ?><div class="error"><?= e($errors['phone']) ?></div><?php endif; ?></label>
      <label><span>Role</span><select name="role" required>
        <option value="" disabled <?= empty($_POST['role'])?'selected':'' ?>>Select role</option>
        <?php foreach(['ADMIN','SELLER','CUSTOMER'] as $r): ?><option value="<?= $r ?>" <?= (($_POST['role']??'')===$r)?'selected':'' ?>><?= ucfirst(strtolower($r)) ?></option><?php endforeach; ?>
      </select><?php if(isset($errors['role'])): ?><div class="error"><?= e($errors['role']) ?></div><?php endif; ?></label>
      <label><span>Password</span><input type="password" name="password" required><?php if(isset($errors['password'])): ?><div class="error"><?= e($errors['password']) ?></div><?php endif; ?></label>
      <div class="actions"><button type="submit" class="btn"><i class="fa-solid fa-user-plus"></i> Save</button><a href="user.php" class="btn ghost"><i class="fa-solid fa-times"></i> Cancel</a></div>
    </form>
  </section>
</main>
</body>
</html>
