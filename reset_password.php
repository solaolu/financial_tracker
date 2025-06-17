<?php
// Page to reset the password using a token.

session_start();
require_once 'functions.php';

$message = '';
$token = $_GET['token'] ?? '';
$user_id = false;

if (!empty($token)) {
    $user_id = validate_reset_token($token);
    if (!$user_id) {
        $message = "<p class='text-red-600 font-semibold'>Invalid or expired password reset token.</p>";
    }
} else {
    $message = "<p class='text-red-600 font-semibold'>No password reset token provided.</p>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $token = sanitize_input($_POST['token']);
    $new_password = sanitize_input($_POST['new_password']);
    $confirm_password = sanitize_input($_POST['confirm_password']);

    if ($new_password !== $confirm_password) {
        $message = "<p class='text-red-600 font-semibold'>Passwords do not match.</p>";
    } else {
        $user_id = validate_reset_token($token); // Re-validate the token on POST
        if ($user_id) {
            if (update_password_with_token($user_id, $new_password, $token)) {
                $message = "<p class='text-green-600 font-semibold'>Your password has been reset successfully! You can now <a href='index.php' class='text-blue-600 hover:underline'>log in</a>.</p>";
            } else {
                $message = "<p class='text-red-600 font-semibold'>Failed to reset password. Please try again.</p>";
            }
        } else {
            $message = "<p class='text-red-600 font-semibold'>Invalid or expired password reset token.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
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

        <?php if ($user_id && empty($message)): // Only show form if token is valid and no error message ?>
            <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 border-b pb-2 flex items-center"><i class="fa-solid fa-lock mr-2"></i> Set New Password</h3>
            <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST" class="space-y-4">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div>
                    <label for="new_password" class="block text-gray-600 dark:text-gray-300 text-sm font-medium mb-1">New Password:</label>
                    <input type="password" id="new_password" name="new_password" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label for="confirm_password" class="block text-gray-600 dark:text-gray-300 text-sm font-medium mb-1">Confirm New Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <button type="submit" name="reset_password" class="w-full bg-blue-600 text-white p-2 rounded-md hover:bg-blue-700 transition duration-300 shadow-md">Reset Password</button>
            </form>
        <?php elseif (empty($message)): ?>
            <p class="text-gray-600 dark:text-gray-300">Please provide a valid token to reset your password.</p>
        <?php endif; ?>

        <div class="mt-6 text-center">
            <p class="text-gray-600 dark:text-gray-300">Go back to <a href="index.php" class="text-blue-600 hover:underline font-semibold dark:text-blue-400">Login</a></p>
        </div>
    </div>
</body>
</html>