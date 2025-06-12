<?php
session_start();
require_once __DIR__ . '/../src/models/User.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = User::login($_POST['username'], $_POST['password']);
    if ($user) {
        $_SESSION['user'] = $user;
        header("Location: /index.php");
        exit;
    } else {
        $error = "Invalid credentials";
    }
}
?><link rel="stylesheet" href="/css/style.css">
<div class="container">
<h2>Login</h2>
<form method="post">
    <input name="username" placeholder="Username" required />
    <input name="password" type="password" placeholder="Password" required />
    <button>Login</button>
</form>
<p style="color:red"><?= $error ?></p>
<p>No account? <a href="/register.php">Register</a></p>
</div>