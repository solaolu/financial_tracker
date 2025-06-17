<?php
// User settings page, including dark mode, currency, and data sharing.

require_once 'header.php';
require_once 'functions.php';

$message = '';
$user_id = $_SESSION['user_id'];
$user_preferences = get_user_preferences($user_id);
$user_currency = $user_preferences['currency'];
$is_dark_mode_enabled = $user_preferences['dark_mode_enabled'];

// Handle preference updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_preferences'])) {
    $new_currency = sanitize_input($_POST['currency']);
    $new_dark_mode = isset($_POST['dark_mode']) ? 1 : 0;

    if (update_user_preferences($user_id, $new_currency, $new_dark_mode)) {
        $message = "<p class='text-green-600 font-semibold mb-4'>Preferences updated successfully!</p>";
        // Update session and refresh window.userPreferences to apply theme immediately
        $_SESSION['user_currency'] = $new_currency; // Might not be used directly, but good practice
        $_SESSION['dark_mode_enabled'] = $new_dark_mode;

        // Use localStorage to communicate changes across tabs/windows or upon refresh
        echo "<script>";
        echo "window.userPreferences = { currencySymbol: '" . get_currency_symbol($new_currency) . "', isDarkModeEnabled: " . ($new_dark_mode ? 'true' : 'false') . " };";
        echo "localStorage.setItem('userPreferences', JSON.stringify(window.userPreferences));";
        echo "applyTheme();"; // Call the function from header.php
        echo "</script>";

        // Re-fetch to ensure the form shows the very latest saved state
        $user_preferences = get_user_preferences($user_id);
        $user_currency = $user_preferences['currency'];
        $is_dark_mode_enabled = $user_preferences['dark_mode_enabled'];

    } else {
        $message = "<p class='text-red-600 font-semibold mb-4'>Failed to update preferences.</p>";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_preferences_from_header'])) {
    // This block handles updates from the header's dark mode toggle specifically
    $new_dark_mode = isset($_POST['dark_mode']) ? (int)$_POST['dark_mode'] : 0;
    // Get current currency from DB to persist it
    $current_prefs = get_user_preferences($user_id);
    $current_currency = $current_prefs['currency'];

    if (update_user_preferences($user_id, $current_currency, $new_dark_mode)) {
        // Success, but don't output HTML/CSS directly here, just a simple response if needed
        http_response_code(200);
        exit();
    } else {
        http_response_code(500);
        exit();
    }
}


// Handle Add/Update/Delete Share (Moved from share_data.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_share'])) {
        $shared_with_user_id = (int)sanitize_input($_POST['shared_with_user_id']);
        $permission_level = sanitize_input($_POST['permission_level']);

        if ($shared_with_user_id === $user_id) {
            $message = "<p class='text-red-600 font-semibold mb-4'>You cannot share data with yourself.</p>";
        } elseif (add_data_share($user_id, $shared_with_user_id, $permission_level)) {
            $message = "<p class='text-green-600 font-semibold mb-4'>Data sharing added successfully!</p>";
        } else {
            $message = "<p class='text-red-600 font-semibold mb-4'>Failed to add data sharing. Perhaps you are already sharing with this user?</p>";
        }
    } elseif (isset($_POST['update_share'])) {
        $share_id = (int)sanitize_input($_POST['share_id']);
        $permission_level = sanitize_input($_POST['permission_level']);

        if (update_data_share($share_id, $user_id, $permission_level)) {
            $message = "<p class='text-green-600 font-semibold mb-4'>Data sharing updated successfully!</p>";
        } else {
            $message = "<p class='text-red-600 font-semibold mb-4'>Failed to update data sharing or you don't own this share entry.</p>";
        }
    }
} elseif (isset($_GET['action']) && $_GET['action'] === 'delete_share' && isset($_GET['id'])) {
    $share_id = (int)sanitize_input($_GET['id']);
    if (delete_data_share($share_id, $user_id)) {
        $message = "<p class='text-green-600 font-semibold mb-4'>Data sharing deleted successfully!</p>";
        header('Location: settings.php'); // Redirect to clean URL
        exit();
    } else {
        $message = "<p class='text-red-600 font-semibold mb-4'>Failed to delete data sharing or you don't own this share entry.</p>";
    }
}

// Get all other registered users for the dropdown
$other_users = get_all_other_users($user_id);

// Get sharing arrangements made by the current user
$my_shares = get_shares_by_owner($user_id);

// Get data shared with the current user
$shared_with_me = get_shares_for_user($user_id);

?>

<h2 class="text-3xl font-semibold text-gray-800 dark:text-white mb-6 flex items-center"><i class="fa-solid fa-gear mr-3"></i> Settings</h2>

<?php if (!empty($message)) echo $message; ?>

<!-- User Preferences Section -->
<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg mb-8">
    <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 border-b border-gray-200 dark:border-gray-700 pb-2 flex items-center"><i class="fa-solid fa-sliders-h mr-2"></i> User Preferences</h3>
    <form action="settings.php" method="POST" class="space-y-4">
        <div>
            <label for="currency" class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-1">Preferred Currency:</label>
            <select id="currency" name="currency" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="USD" <?php echo ($user_currency === 'USD') ? 'selected' : ''; ?>>USD ($)</option>
                <option value="EUR" <?php echo ($user_currency === 'EUR') ? 'selected' : ''; ?>>EUR (€)</option>
                <option value="GBP" <?php echo ($user_currency === 'GBP') ? 'selected' : ''; ?>>GBP (£)</option>
                <option value="JPY" <?php echo ($user_currency === 'JPY') ? 'selected' : ''; ?>>JPY (¥)</option>
                <option value="CAD" <?php echo ($user_currency === 'CAD') ? 'selected' : ''; ?>>CAD (C$)</option>
                <option value="AUD" <?php echo ($user_currency === 'AUD') ? 'selected' : ''; ?>>AUD (A$)</option>
                <option value="CHF" <?php echo ($user_currency === 'CHF') ? 'selected' : ''; ?>>CHF (CHF)</option>
                <option value="CNY" <?php echo ($user_currency === 'CNY') ? 'selected' : ''; ?>>CNY (¥)</option>
                <option value="INR" <?php echo ($user_currency === 'INR') ? 'selected' : ''; ?>>INR (₹)</option>
                <option value="BRL" <?php echo ($user_currency === 'BRL') ? 'selected' : ''; ?>>BRL (R$)</option>
                <option value="RUB" <?php echo ($user_currency === 'RUB') ? 'selected' : ''; ?>>RUB (₽)</option>
                <option value="ZAR" <?php echo ($user_currency === 'ZAR') ? 'selected' : ''; ?>>ZAR (R)</option>
            </select>
        </div>
        <div class="flex items-center mt-4">
            <input type="checkbox" id="dark_mode" name="dark_mode" class="form-checkbox h-5 w-5 text-blue-600 rounded dark:accent-blue-600" <?php echo $is_dark_mode_enabled ? 'checked' : ''; ?>>
            <label for="dark_mode" class="ml-2 text-gray-700 dark:text-gray-300 font-medium">Enable Dark Mode</label>
        </div>
        <div>
            <button type="submit" name="update_preferences" class="w-full bg-blue-600 text-white p-3 rounded-md hover:bg-blue-700 transition duration-300 shadow-md flex items-center justify-center">
                <i class="fa-solid fa-save mr-2"></i> Update Preferences
            </button>
        </div>
    </form>
</div>

<!-- Data Sharing Section (Moved from share_data.php) -->
<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg mb-8">
    <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 border-b border-gray-200 dark:border-gray-700 pb-2 flex items-center"><i class="fa-solid fa-share-alt mr-2"></i> Share Your Data</h3>
    <form action="settings.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="shared_with_user_id" class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-1">Share With:</label>
            <select id="shared_with_user_id" name="shared_with_user_id" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">Select a user</option>
                <?php if (empty($other_users)): ?>
                    <option value="" disabled>No other users to share with.</option>
                <?php else: ?>
                    <?php foreach ($other_users as $user): ?>
                        <option value="<?php echo htmlspecialchars($user['id']); ?>">
                            <?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['username']); ?>)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <div>
            <label for="permission_level" class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-1">Permission Level:</label>
            <select id="permission_level" name="permission_level" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="read_only">Read Only</option>
                <option value="read_write">Read & Write</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <button type="submit" name="add_share" class="w-full bg-blue-600 text-white p-3 rounded-md hover:bg-blue-700 transition duration-300 shadow-md flex items-center justify-center">
                <i class="fa-solid fa-plus mr-2"></i> Add Share
            </button>
        </div>
    </form>
</div>

<!-- Shares You Have Made -->
<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg mb-8">
    <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 border-b border-gray-200 dark:border-gray-700 pb-2 flex items-center"><i class="fa-solid fa-handshake mr-2"></i> Data You Are Sharing</h3>
    <?php if (empty($my_shares)): ?>
        <p class="text-gray-600 dark:text-gray-400">You are not currently sharing your data with anyone.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Shared With</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Permission</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($my_shares as $share): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($share['name']); ?> (<?php echo htmlspecialchars($share['username']); ?>)</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <form action="settings.php" method="POST" class="inline-block">
                                    <input type="hidden" name="share_id" value="<?php echo htmlspecialchars($share['id']); ?>">
                                    <select name="permission_level" onchange="this.form.submit()" class="p-1 border border-gray-300 rounded-md text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="read_only" <?php echo ($share['permission_level'] === 'read_only') ? 'selected' : ''; ?>>Read Only</option>
                                        <option value="read_write" <?php echo ($share['permission_level'] === 'read_write') ? 'selected' : ''; ?>>Read & Write</option>
                                    </select>
                                    <noscript><button type="submit" name="update_share" class="ml-2 px-3 py-1 bg-indigo-500 text-white text-xs rounded-md">Update</button></noscript>
                                </form>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="settings.php?action=delete_share&id=<?php echo htmlspecialchars($share['id']); ?>" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" onclick="return confirm('Are you sure you want to stop sharing with this user?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Data Shared With You -->
<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
    <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 border-b border-gray-200 dark:border-gray-700 pb-2 flex items-center"><i class="fa-solid fa-users mr-2"></i> Data Shared With You</h3>
    <?php if (empty($shared_with_me)): ?>
        <p class="text-gray-600 dark:text-gray-400">No one is currently sharing their data with you.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Owner Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Permission</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($shared_with_me as $share): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($share['owner_name']); ?> (<?php echo htmlspecialchars($share['owner_username']); ?>)</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($share['permission_level']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>