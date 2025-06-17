<?php
// User login page.

session_start();
require_once 'functions.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = sanitize_input($_POST['username']);
    $password = sanitize_input($_POST['password']);

    $user_id = login_user($username, $password);
    if ($user_id) {
        $_SESSION['user_id'] = $user_id;
        // Fetch user preferences and store in session for theme
        $user_info = get_user_by_id($user_id);
        $_SESSION['dark_mode_enabled'] = $user_info['dark_mode_enabled'];
        header('Location: dashboard.php');
        exit();
    } else {
        $message = "<p class='text-red-600 font-semibold'>Invalid username or password.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .dark body { background-color: #1a202c; }
        .dark .bg-white { background-color: #2d3748; }
        .dark .text-gray-800 { color: #e2e8f0; }
        .dark .text-gray-700 { color: #cbd5e0; }
        .dark input { background-color: #4a5568; color: #e2e8f0; border-color: #616e7f; }
        .dark input::placeholder { color: #cbd5e0; }
        .dark .border-gray-300 { border-color: #4a5568; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md">
        <h2 class="text-3xl font-bold text-center text-gray-800 dark:text-white mb-6">Financial Tracker</h2>
        <?php if (!empty($message)) echo $message; ?>

        <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 border-b pb-2 flex items-center"><i class="fa-solid fa-right-to-bracket mr-2"></i> Login</h3>
        <form action="index.php" method="POST" class="space-y-4">
            <div>
                <label for="login_username" class="block text-gray-600 dark:text-gray-300 text-sm font-medium mb-1">Username:</label>
                <input type="text" id="login_username" name="username" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div>
                <label for="login_password" class="block text-gray-600 dark:text-gray-300 text-sm font-medium mb-1">Password:</label>
                <input type="password" id="login_password" name="password" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <button type="submit" name="login" class="w-full bg-blue-600 text-white p-2 rounded-md hover:bg-blue-700 transition duration-300 shadow-md">Login</button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-gray-600 dark:text-gray-300">Don't have an account? <a href="register.php" class="text-blue-600 hover:underline font-semibold dark:text-blue-400">Register here</a></p>
            <p class="text-gray-600 dark:text-gray-300 mt-2">
                <a href="request_password_reset.php" class="text-blue-600 hover:underline font-semibold dark:text-blue-400">Forgot Password?</a>
            </p>
        </div>
    </div>
</body>
</html>