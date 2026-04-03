<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/config.php';

$errors = array_fill_keys(['fullname','email','username','password','confirm','phone','gender'], '');
$vals   = array_fill_keys(['fullname','email','username','password','confirm','phone','gender'], '');

function clean($s){
    return htmlspecialchars(trim($s ?? ''), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($vals as $k => $_) {
        $vals[$k] = clean($_POST[$k] ?? '');
    }

    if ($vals['fullname'] === '') {
        $errors['fullname'] = 'Full name is required';
    }

    if ($vals['email'] === '' || !filter_var($vals['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Valid email required';
    }

    if ($vals['username'] === '' || strlen($vals['username']) < 4) {
        $errors['username'] = 'Username must be at least 4 characters';
    }

    if ($vals['password'] === '' || strlen($vals['password']) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }

    if ($vals['confirm'] === '' || $vals['confirm'] !== $vals['password']) {
        $errors['confirm'] = 'Passwords do not match';
    }

    if (!preg_match('/^\d{11}$/', $vals['phone'])) {
        $errors['phone'] = 'Phone must be 11 digits';
    }

    if ($vals['gender'] === '') {
        $errors['gender'] = 'Gender is required';
    }

    $hasError = array_filter($errors);

    if (!$hasError) {
        $check = $conn->prepare("SELECT id FROM users WHERE email=? OR username=? LIMIT 1");
        $check->bind_param("ss", $vals['email'], $vals['username']);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $errors['email'] = 'Email or username already exists';
        } else {
            $hash = password_hash($vals['password'], PASSWORD_DEFAULT);

            $insert = $conn->prepare("
                INSERT INTO users (fullname, email, username, password_hash, phone, gender)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $insert->bind_param(
                "ssssss",
                $vals['fullname'],
                $vals['email'],
                $vals['username'],
                $hash,
                $vals['phone'],
                $vals['gender']
            );

            if ($insert->execute()) {
                $insert->close();
                header('Location: login.php?registered=1');
                exit;
            } else {
                $errors['email'] = 'Registration failed';
            }

            $insert->close();
        }

        $check->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=1200">
    <title>Register</title>
    <link rel="stylesheet" href="../css/registration.css">
</head>
<body>

<div class="container">
    <div class="card topbar">
        <div class="brand">Create Account</div>
        <a href="login.php">⬅️ Back</a>
    </div>

    <section class="card form-box">
        <h2>Register</h2>

        <form method="post">
            <input type="text" name="fullname" placeholder="Full Name" value="<?= $vals['fullname'] ?>">
            <span style="color:red"><?= $errors['fullname'] ?></span>

            <input type="email" name="email" placeholder="Email" value="<?= $vals['email'] ?>">
            <span style="color:red"><?= $errors['email'] ?></span>

            <input type="text" name="username" placeholder="Username" value="<?= $vals['username'] ?>">
            <span style="color:red"><?= $errors['username'] ?></span>

            <input type="password" name="password" placeholder="Password">
            <span style="color:red"><?= $errors['password'] ?></span>

            <input type="password" name="confirm" placeholder="Confirm Password">
            <span style="color:red"><?= $errors['confirm'] ?></span>

            <input type="text" name="phone" placeholder="Phone" value="<?= $vals['phone'] ?>">
            <span style="color:red"><?= $errors['phone'] ?></span>

            <select name="gender">
                <option value="">Select Gender</option>
                <option value="male" <?= $vals['gender'] == 'male' ? 'selected' : '' ?>>Male</option>
                <option value="female" <?= $vals['gender'] == 'female' ? 'selected' : '' ?>>Female</option>
                <option value="other" <?= $vals['gender'] == 'other' ? 'selected' : '' ?>>Other</option>
            </select>
            <span style="color:red"><?= $errors['gender'] ?></span>

            <button type="submit">Register</button>

            <p>Already have account? <a href="login.php">Login</a></p>
        </form>
    </section>
</div>

</body>
</html>