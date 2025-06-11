<?php
session_start();
// Simplified login logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    if ($username === 'admin' && $password === 'admin') {
        $_SESSION['user'] = $username;
        header('Location: index.php');
    } else {
        echo 'Invalid credentials';
    }
}
?>
<form method='post'><input name='username'><input name='password' type='password'><button>Login</button></form>