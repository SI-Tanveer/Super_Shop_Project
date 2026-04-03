<?php
session_start();
if (!isset($_SESSION['messages'])) {
  $_SESSION['messages']=[[
    'id'=>1,'name'=>'Taj Uddin','email'=>'taj.super.long.email@example.com',
    'subject'=>'Order #2025-0001 not delivered','text'=>'Hi, my order hasn’t arrived yet. The tracking shows pending since yesterday. Could you please check?','status'=>'unread','time'=>'2025-09-09 09:40','avatar'=>'../images/about-img-2.png'
  ]];
}
$messages=&$_SESSION['messages']; $errors=[]; $success='';
function e(string $s): string { return htmlspecialchars($s,ENT_QUOTES,'UTF-8'); }

if (isset($_GET['delete'])) { $id=(int)$_GET['delete']; $messages=array_values(array_filter($messages,fn($m)=>$m['id']!==$id)); header("Location: message.php"); exit; }
if (isset($_GET['mark'])) { $id=(int)$_GET['mark']; foreach($messages as &$m){ if($m['id']===$id){ $m['status']=$m['status']==='unread'?'read':'unread'; }} unset($m); header("Location: message.php"); exit; }

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $subject=trim($_POST['subject']??''); $text=trim($_POST['text']??'');
  if ($name==='') $errors['name']='Name is required.';
  if ($email==='' || !filter_var($email,FILTER_VALIDATE_EMAIL)) $errors['email']='Valid email is required.';
  if ($subject==='') $errors['subject']='Subject is required.';
  if ($text==='' || strlen($text)<5) $errors['text']='Message must be at least 5 characters.';
  if (!$errors) {
    $newId=empty($messages)?1:max(array_column($messages,'id'))+1;
    $messages[]=['id'=>$newId,'name'=>e($name),'email'=>e($email),'subject'=>e($subject),'text'=>e($text),'status'=>'unread','time'=>date("Y-m-d H:i"),'avatar'=>'../images/default-avatar.png'];
    $success="Message sent successfully.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Messages • Admin Panel</title>
  <link rel="stylesheet" href="../css/message.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>.error{color:#b00020;font-size:.9rem;margin-top:4px}.success{color:#0a7f33;font-weight:600;margin-bottom:10px}.compose{max-width:500px;margin:20px auto;padding:16px;border:1px solid #ccc;border-radius:8px;background:#fff}.compose label{display:block;margin-bottom:8px;font-weight:600}.compose input,.compose textarea{width:100%;padding:8px;margin-bottom:10px;border:1px solid #bbb;border-radius:6px}</style>
</head>
<body>
<header class="site">
  <nav class="nav">
    <a href="admin.php" class="logo">Admin<span>Panel</span></a>
    <a href="home.php">Home</a>
    <a href="admin_products.php">Products</a>
    <a href="order.php">Orders</a>
    <a href="user.php">Users</a>
    <a href="message.php" class="active">Messages</a>
    <div class="icons">
       <a href="logout.php" class="btn logout-btn"><i class="fas fa-right-from-bracket"></i> Logout</a>
        <button id="user-btn" class="icon-btn" aria-label="User"><i class="fas fa-user"></i></button>
      </div>
  </nav>
</header>

<main class="container">
  <h1 class="title">Messages</h1>

  <section class="compose">
    <?php if($success): ?><div class="success"><?= e($success) ?></div><?php endif; ?>
    <form method="post" novalidate>
      <label>Name<input type="text" name="name" value="<?= e($_POST['name']??'') ?>" required></label><?php if(isset($errors['name'])): ?><div class="error"><?= e($errors['name']) ?></div><?php endif; ?>
      <label>Email<input type="email" name="email" value="<?= e($_POST['email']??'') ?>" required></label><?php if(isset($errors['email'])): ?><div class="error"><?= e($errors['email']) ?></div><?php endif; ?>
      <label>Subject<input type="text" name="subject" value="<?= e($_POST['subject']??'') ?>" required></label><?php if(isset($errors['subject'])): ?><div class="error"><?= e($errors['subject']) ?></div><?php endif; ?>
      <label>Message<textarea name="text" rows="4" required><?= e($_POST['text']??'') ?></textarea></label><?php if(isset($errors['text'])): ?><div class="error"><?= e($errors['text']) ?></div><?php endif; ?>
      <button class="btn" type="submit"><i class="fa-solid fa-envelope"></i> Send</button>
    </form>
  </section>

  <section class="messages-grid">
    <?php foreach(array_reverse($messages) as $m): ?>
    <article class="message-card card">
      <header class="msg-head">
        <div class="who"><img class="avatar" src="<?= e($m['avatar']) ?>" alt="user">
          <div class="id"><div class="name"><?= e($m['name']) ?></div><div class="email"><?= e($m['email']) ?></div></div>
        </div>
        <div class="chips"><span class="badge <?= $m['status']==='unread'?'unread':'read' ?>"><?= strtoupper($m['status']) ?></span><time class="time"><?= e($m['time']) ?></time></div>
      </header>
      <div class="msg-body"><div class="subject"><?= e($m['subject']) ?></div><p class="text"><?= e($m['text']) ?></p></div>
      <footer class="msg-actions">
        <div class="left"><a href="#" class="btn ghost"><i class="fa-solid fa-eye"></i> View</a><a href="#" class="btn"><i class="fa-solid fa-reply"></i> Reply</a></div>
        <div class="right"><a class="btn ghost" href="?mark=<?= e((string)$m['id']) ?>"><i class="fa-regular fa-envelope-open"></i> Toggle Read</a><a class="btn danger" href="?delete=<?= e((string)$m['id']) ?>" onclick="return confirm('Delete this message?')"><i class="fa-solid fa-trash"></i> Delete</a></div>
      </footer>
    </article>
    <?php endforeach; ?>
  </section>
</main>
</body>
</html>
