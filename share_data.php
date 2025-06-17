<?php
// Page to manage data sharing permissions.

require_once 'header.php';
require_once 'functions.php';

$message = '';
$user_id = $_SESSION['user_id'];

// Incremental Change: Get user settings for currency display
$user_settings = get_user_settings($user_id);
$currency_symbol = htmlspecialchars($user_settings['currency_symbol'] ?? '$');


// Handle Add/Update/Delete Share
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
        header('Location: share_data.php'); // Redirect to clean URL
        exit();
    } else {
        $message = "<p class='text-red-600 font-semibold mb-4'>Failed to delete data sharing or you don't own this share entry.</p>";
    }
}

// Get all other registered users for the dropdown
$other_users = get_all_other_users($user_id);
// Temporary debug output for other users
echo "<script>console.log('Users fetched for dropdown:', " . json_encode($other_users) . ");</script>";


// Get sharing arrangements made by the current user
$my_shares = get_shares_by_owner($user_id);

// Get data shared with the current user
$shared_with_me = get_shares_for_user($user_id);

?>

<h2 class="text-3xl font-semibold text-gray-800 mb-6">Manage Data Sharing</h2>

<?php if (!empty($message)) echo $message; ?>

<!-- Add New Share -->
<div class="bg-white p-6 rounded-lg shadow-lg mb-8">
    <h3 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Share Your Data</h3>
    <form action="share_data.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="shared_with_user_id" class="block text-gray-700 text-sm font-medium mb-1">Share With:</label>
            <select id="shared_with_user_id" name="shared_with_user_id" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
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
            <label for="permission_level" class="block text-gray-700 text-sm font-medium mb-1">Permission Level:</label>
            <select id="permission_level" name="permission_level" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                <option value="read_only">Read Only</option>
                <option value="read_write">Read & Write</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <button type="submit" name="add_share" class="w-full bg-blue-600 text-white p-3 rounded-md hover:bg-blue-700 transition duration-300 shadow-md">Add Share</button>
        </div>
    </form>
</div>

<!-- Shares You Have Made -->
<div class="bg-white p-6 rounded-lg shadow-lg mb-8">
    <h3 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Data You Are Sharing</h3>
    <?php if (empty($my_shares)): ?>
        <p class="text-gray-600">You are not currently sharing your data with anyone.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shared With</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permission</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($my_shares as $share): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($share['name']); ?> (<?php echo htmlspecialchars($share['username']); ?>)</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <form action="share_data.php" method="POST" class="inline-block">
                                    <input type="hidden" name="share_id" value="<?php echo htmlspecialchars($share['id']); ?>">
                                    <select name="permission_level" onchange="this.form.submit()" class="p-1 border border-gray-300 rounded-md text-sm">
                                        <option value="read_only" <?php echo ($share['permission_level'] === 'read_only') ? 'selected' : ''; ?>>Read Only</option>
                                        <option value="read_write" <?php echo ($share['permission_level'] === 'read_write') ? 'selected' : ''; ?>>Read & Write</option>
                                    </select>
                                    <noscript><button type="submit" name="update_share" class="ml-2 px-3 py-1 bg-indigo-500 text-white text-xs rounded-md">Update</button></noscript>
                                </form>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="share_data.php?action=delete_share&id=<?php echo htmlspecialchars($share['id']); ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to stop sharing with this user?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Data Shared With You -->
<div class="bg-white p-6 rounded-lg shadow-lg">
    <h3 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Data Shared With You</h3>
    <?php if (empty($shared_with_me)): ?>
        <p class="text-gray-600">No one is currently sharing their data with you.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permission</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($shared_with_me as $share): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($share['owner_name']); ?> (<?php echo htmlspecialchars($share['owner_username']); ?>)</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($share['permission_level']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>


<?php require_once 'footer.php'; ?>