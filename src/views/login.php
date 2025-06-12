<!DOCTYPE html><html><head><title>login.php</title></head><body><h2>Login</h2><form method="post">
Username: <input name="username"><br>
Password: <input name="password" type="password"><br>
<button type="submit">Login</button></form>
<a href="index.php?action=register">Register</a>
<?php if (!empty($error)) echo "<p>$error</p>"; ?></body></html>