<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$host = 'localhost';
$db   = 'supermart';
$user = 'root';
$pass = '';
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
$pdo = new PDO($dsn, $user, $pass, $options);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  $stmt = $pdo->prepare('SELECT id, name, email, password_hash FROM users WHERE email = ? LIMIT 1');
  $stmt->execute([$email]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    header('Location: index.php');
    exit;
  } else {
    $error = 'Invalid email or password';
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <title>Login – Smart Supermarket</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f4f8fb; margin:0; padding:40px; }
    .card { max-width:420px; margin:40px auto; background:#fff; border-radius:12px; padding:28px; box-shadow:0 10px 30px rgba(0,0,0,.08); }
    h1 { margin:0 0 12px; color:#005A9C; text-align:center; }
    label { display:block; margin:14px 0 6px; font-weight:600; }
    input { width:100%; padding:12px; border:1px solid #ccd6e0; border-radius:8px; font-size:15px; }
    button { width:100%; margin-top:16px; background:#005A9C; color:#fff; border:0; padding:12px; border-radius:8px; font-size:16px; cursor:pointer; }
    button:hover { background:#003d6b; }
    .error { background:#ffe8e8; color:#9c1a00; padding:10px; border-radius:8px; margin-bottom:12px; }
    .muted { text-align:center; margin-top:12px; color:#6b7b8c; font-size:14px; }
  </style>
</head>
<body>
  <div class="card">
    <h1>Sign in</h1>
    <?php if($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
      <label>Email</label>
      <input type="email" name="email" placeholder="you@example.com" required />

      <label>Password</label>
      <input type="password" name="password" placeholder="••••••••" required />

      <button type="submit">Login</button>
    </form>
    <div class="muted">Smart Supermarket Dashboard</div>
  </div>
</body>
</html>
