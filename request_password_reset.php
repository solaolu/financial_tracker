<?php
// Page to request a password reset link.

session_start();
require_once 'functions.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $user = get_user_by_username($username);

    if ($user) {
        $token = generate_reset_token();
        if (store_reset_token($user['id'], $token)) {
            // In a real application, you would send this link via email.
            // For this demonstration, we'll display it directly.
            // Ensure $_SERVER['HTTP_HOST'] is configured correctly for your environment (e.g., localhost or your domain)
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            // Adjust this path if your app is in a subfolder (e.g., /financial_tracker_app/)
            $app_path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
            $reset_link = $protocol . "://" . $host . $app_path . "/reset_password.php?token=" . $token;

            $message = "<p class='text-green-600 font-semibold'>A password reset link has been generated. For demonstration purposes, here it is: <a href='" . htmlspecialchars($reset_link) . "' class='text-blue-600 hover:underline break-all'>" . htmlspecialchars($reset_link) . "</a></p>";
            $message .= "<p class='text-gray-600 mt-2'>In a real app, this link would be sent to the user's registered email address.</p>";
        } else {
            $message = "<p class='text-red-600 font-semibold'>Failed to generate reset link. Please try again.</p>";
        }
    } else {
        $message = "<p class='text-red-600 font-semibold'>Username not found.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Password Reset</title>
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

        <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 border-b pb-2 flex items-center"><i class="fa-solid fa-key mr-2"></i> Request Password Reset</h3>
        <p class="text-gray-600 dark:text-gray-300 mb-4">Enter your username to receive a password reset link.</p>
        <form action="request_password_reset.php" method="POST" class="space-y-4">
            <div>
                <label for="username" class="block text-gray-600 dark:text-gray-300 text-sm font-medium mb-1">Username:</label>
                <input type="text" id="username" name="username" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded-md hover:bg-blue-700 transition duration-300 shadow-md">Request Reset Link</button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-gray-600 dark:text-gray-300">Remembered your password? <a href="index.php" class="text-blue-600 hover:underline font-semibold dark:text-blue-400">Login here</a></p>
        </div>
    </div>
</body>
</html>