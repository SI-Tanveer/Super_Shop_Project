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
    } elseif (!preg_match("/^[\p{L} .'\-]{2,}$/u", $vals['fullname'])) {
        $errors['fullname'] = 'Only letters and spaces allowed';
    }

    if ($vals['email'] === '') {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($vals['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }

    if ($vals['username'] === '') {
        $errors['username'] = 'Username is required';
    } elseif (strlen($vals['username']) < 4) {
        $errors['username'] = 'Must be at least 4 characters';
    }

    if ($vals['password'] === '') {
        $errors['password'] = 'Password is required';
    } elseif (strlen($vals['password']) < 6) {
        $errors['password'] = 'Must be at least 6 characters';
    }

    if ($vals['confirm'] === '') {
        $errors['confirm'] = 'Please confirm your password';
    } elseif ($vals['confirm'] !== $vals['password']) {
        $errors['confirm'] = 'Passwords do not match';
    }

    if ($vals['phone'] === '') {
        $errors['phone'] = 'Phone number is required';
    } elseif (!preg_match('/^\d{11}$/', $vals['phone'])) {
        $errors['phone'] = 'Phone must be 11 digits';
    }

    if ($vals['gender'] === '') {
        $errors['gender'] = 'Gender is required';
    }

    if (!array_filter($errors)) {
        $check = $conn->prepare("SELECT id FROM users WHERE email=? OR username=? LIMIT 1");
        if (!$check) {
            $errors['username'] = 'Database error: ' . $conn->error;
        } else {
            $check->bind_param("ss", $vals['email'], $vals['username']);
            $check->execute();
            $exists = $check->get_result()->num_rows > 0;
            $check->close();

            if ($exists) {
                $errors['email'] = 'Email or username already exists';
            } else {
                $conn->begin_transaction();

                try {
                    $hash = password_hash($vals['password'], PASSWORD_DEFAULT);

                    $u = $conn->prepare("
                        INSERT INTO users (fullname, email, username, password_hash, phone, gender)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    if (!$u) {
                        throw new Exception($conn->error);
                    }

                    $u->bind_param(
                        "ssssss",
                        $vals['fullname'],
                        $vals['email'],
                        $vals['username'],
                        $hash,
                        $vals['phone'],
                        $vals['gender']
                    );
                    $u->execute();
                    $userId = $u->insert_id;
                    $u->close();

                    /* FIXED: only user_id */
                    $e = $conn->prepare("INSERT INTO employees (user_id) VALUES (?)");
                    if (!$e) {
                        throw new Exception($conn->error);
                    }

                    $e->bind_param("i", $userId);
                    $e->execute();
                    $e->close();

                    $conn->commit();

                    header('Location: login.php?registered=employee');
                    exit;

                } catch (Throwable $ex) {
                    $conn->rollback();
                    $errors['username'] = 'Error: ' . $ex->getMessage();
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=1200">
    <title>Supershop – Register (Employee)</title>
    <link rel="stylesheet" href="../css/registration.css">
</head>
<body>
    <div class="container">
        <div class="card topbar">
            <div class="brand">Create Employee Account</div>
            <div class="top-actions">
                <a href="login.php" class="btn-icon" title="Back to Login">⬅️</a>
            </div>
        </div>

        <section class="card form-box">
            <h2 class="form-title">Employee Register</h2>

            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="register-form">

                <div class="field">
                    <label class="label" for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" class="input"
                           value="<?php echo $vals['fullname']; ?>" placeholder="e.g., Rahim Uddin">
                    <span style="color:#ff6b6b;"><?php echo $errors['fullname']; ?></span>
                </div>

                <div class="field">
                    <label class="label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="input"
                           value="<?php echo $vals['email']; ?>" placeholder="e.g., you@example.com">
                    <span style="color:#ff6b6b;"><?php echo $errors['email']; ?></span>
                </div>

                <div class="field">
                    <label class="label" for="username">Username</label>
                    <input type="text" id="username" name="username" class="input"
                           value="<?php echo $vals['username']; ?>" placeholder="Choose a username">
                    <span style="color:#ff6b6b;"><?php echo $errors['username']; ?></span>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label class="label" for="password">Password</label>
                        <input type="password" id="password" name="password" class="input" placeholder="********">
                        <span style="color:#ff6b6b;"><?php echo $errors['password']; ?></span>
                    </div>

                    <div class="field">
                        <label class="label" for="confirm">Confirm Password</label>
                        <input type="password" id="confirm" name="confirm" class="input" placeholder="********">
                        <span style="color:#ff6b6b;"><?php echo $errors['confirm']; ?></span>
                    </div>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label class="label" for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" class="input"
                               value="<?php echo $vals['phone']; ?>" placeholder="01XXXXXXXXX">
                        <span style="color:#ff6b6b;"><?php echo $errors['phone']; ?></span>
                    </div>

                    <div class="field">
                        <label class="label">Gender</label>
                        <select name="gender" class="input">
                            <option value="" disabled <?php echo ($vals['gender'] === '') ? 'selected' : ''; ?>>Select</option>
                            <option value="male" <?php echo ($vals['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo ($vals['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo ($vals['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <span style="color:#ff6b6b;"><?php echo $errors['gender']; ?></span>
                    </div>
                </div>

                <div class="purchase">
                    <button type="submit" class="btn-primary">Register</button>
                </div>

                <p class="note">
                    Already have an account? <a href="login.php">Login here</a>.
                </p>
            </form>
        </section>
    </div>
</body>
</html>