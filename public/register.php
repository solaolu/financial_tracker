<?php
require_once __DIR__ . '/../src/models/User.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (User::register($_POST['username'], $_POST['password'])) {
        header("Location: /login.php");
        exit;
    } else {
        $error = "Username may already be taken.";
    }
}
?><link rel="stylesheet" href="/css/style.css">
<div class="container">
<h2>Register</h2>
<form method="post">
    <input name="username" placeholder="Username" required />
    <input name="password" type="password" placeholder="Password" required />
    <button>Register</button>
</form>
<p style="color:red"><?= $error ?></p>
<p>Already have an account? <a href="/login.php">Login</a></p>
</div>