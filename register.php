<?php
// User registration page.

session_start();
require_once 'functions.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = sanitize_input($_POST['username']);
    $password = sanitize_input($_POST['password']);
    $name = sanitize_input($_POST['name']); // New: Get name input

    if (register_user($username, $password, $name)) { // New: Pass name to function
        $message = "<p class='text-green-600 font-semibold'>Registration successful! You can now <a href='index.php' class='text-blue-600 hover:underline'>log in</a>.</p>";
    } else {
        $message = "<p class='text-red-600 font-semibold'>Registration failed. Username might already exist.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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

        <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 border-b pb-2 flex items-center"><i class="fa-solid fa-user-plus mr-2"></i> Register</h3>
        <form action="register.php" method="POST" class="space-y-4">
            <div>
                <label for="reg_username" class="block text-gray-600 dark:text-gray-300 text-sm font-medium mb-1">Username:</label>
                <input type="text" id="reg_username" name="username" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div>
                <label for="reg_name" class="block text-gray-600 dark:text-gray-300 text-sm font-medium mb-1">Full Name:</label>
                <input type="text" id="reg_name" name="name" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div>
                <label for="reg_password" class="block text-gray-600 dark:text-gray-300 text-sm font-medium mb-1">Password:</label>
                <input type="password" id="reg_password" name="password" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <button type="submit" name="register" class="w-full bg-green-600 text-white p-2 rounded-md hover:bg-green-700 transition duration-300 shadow-md">Register</button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-gray-600 dark:text-gray-300">Already have an account? <a href="index.php" class="text-blue-600 hover:underline font-semibold dark:text-blue-400">Login here</a></p>
        </div>
    </div>
</body>
</html>