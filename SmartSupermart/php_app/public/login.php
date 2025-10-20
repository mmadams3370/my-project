<?php
session_start();

// Simple hardcoded credentials (can be replaced with DB)
$USERNAME = "admin";
$PASSWORD = "12345";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    if ($user === $USERNAME && $pass === $PASSWORD) {
        $_SESSION['logged_in'] = true;
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Login - Smart Supermarket Forecasting</title>
  <style>
    body { font-family: Arial; background:#f4f8fb; display:flex; height:100vh; justify-content:center; align-items:center; }
    .login-box { background:white; padding:30px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.1); width:300px; }
    h2 { color:#005A9C; text-align:center; }
    input { width:100%; padding:10px; margin:10px 0; border-radius:8px; border:1px solid #ccc; }
    button { width:100%; padding:10px; background:#005A9C; color:white; border:none; border-radius:8px; cursor:pointer; }
    button:hover { background:#003d6b; }
    .error { color:red; text-align:center; }
  </style>
</head>
<body>
  <div class="login-box">
    <h2>Login</h2>
    <form method="POST">
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
      <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    </form>
  </div>
</body>
</html>