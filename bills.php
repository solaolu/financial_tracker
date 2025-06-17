<?php
// Page to manage bills (due dates and reminders).

require_once 'header.php';
require_once 'functions.php';

$message = '';
$user_id = $_SESSION['user_id'];
$user_info = get_user_by_id($user_id);
$user_currency_symbol = get_currency_symbol($user_info['currency'] ?? 'USD');
$edit_bill = null;

// Handle Add/Update/Delete Bills
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_bill'])) {
        $description = sanitize_input($_POST['description']);
        $amount = (float)sanitize_input($_POST['amount']);
        $due_date = sanitize_input($_POST['due_date']);
        $category = sanitize_input($_POST['category']);
        $recurring_frequency = sanitize_input($_POST['recurring_frequency']);

        if (add_bill($user_id, $description, $amount, $due_date, $category, $recurring_frequency)) {
            $message = "<p class='text-green-600 font-semibold mb-4'>Bill added successfully!</p>";
        } else {
            $message = "<p class='text-red-600 font-semibold mb-4'>Failed to add bill.</p>";
        }
    } elseif (isset($_POST['update_bill'])) {
        $bill_id = (int)sanitize_input($_POST['bill_id']);
        $description = sanitize_input($_POST['description']);
        $amount = (float)sanitize_input($_POST['amount']);
        $due_date = sanitize_input($_POST['due_date']);
        $category = sanitize_input($_POST['category']);
        $is_paid = isset($_POST['is_paid']) ? 1 : 0;
        $recurring_frequency = sanitize_input($_POST['recurring_frequency']);

        if (update_bill($bill_id, $user_id, $description, $amount, $due_date, $category, $is_paid, $recurring_frequency)) {
            $message = "<p class='text-green-600 font-semibold mb-4'>Bill updated successfully!</p>";
            // Re-fetch to show updated data
            $edit_bill = get_bill_by_id($bill_id, $user_id);
        } else {
            $message = "<p class='text-red-600 font-semibold mb-4'>Failed to update bill or you don't own it.</p>";
        }
    } elseif (isset($_POST['mark_paid'])) {
        $bill_id = (int)sanitize_input($_POST['bill_id']);
        $is_paid = (int)sanitize_input($_POST['status']); // 0 or 1
        if (mark_bill_as_paid($bill_id, $user_id, $is_paid)) {
            $message = "<p class='text-green-600 font-semibold mb-4'>Bill status updated successfully!</p>";
        } else {
            $message = "<p class='text-red-600 font-semibold mb-4'>Failed to update bill status.</p>";
        }
    }
} elseif (isset($_GET['action'])) {
    if ($_GET['action'] === 'edit' && isset($_GET['id'])) {
        $bill_id = (int)sanitize_input($_GET['id']);
        $edit_bill = get_bill_by_id($bill_id, $user_id);
        if (!$edit_bill) {
            $message = "<p class='text-red-600 font-semibold mb-4'>Bill not found or you don't own it.</p>";
        }
    } elseif ($_GET['action'] === 'delete' && isset($_GET['id'])) {
        $bill_id = (int)sanitize_input($_GET['id']);
        if (delete_bill($bill_id, $user_id)) {
            $message = "<p class='text-green-600 font-semibold mb-4'>Bill deleted successfully!</p>";
            header('Location: bills.php'); // Redirect to clean URL
            exit();
        } else {
            $message = "<p class='text-red-600 font-semibold mb-4'>Failed to delete bill or you don't own it.</p>";
        }
    }
}

$all_bills = get_all_bills($user_id);

?>

<h2 class="text-3xl font-semibold text-gray-800 dark:text-white mb-6 flex items-center"><i class="fa-solid fa-file-invoice-dollar mr-3"></i> Manage Your Bills</h2>

<?php if (!empty($message)) echo $message; ?>

<!-- Add/Edit Bill Form -->
<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg mb-8">
    <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 border-b border-gray-200 dark:border-gray-700 pb-2 flex items-center">
        <i class="fa-solid fa-<?php echo ($edit_bill ? 'edit' : 'plus-square'); ?> mr-2"></i> <?php echo ($edit_bill ? 'Edit Bill' : 'Add New Bill'); ?>
    </h3>
    <form action="bills.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php if ($edit_bill): ?>
            <input type="hidden" name="bill_id" value="<?php echo htmlspecialchars($edit_bill['id']); ?>">
        <?php endif; ?>

        <div>
            <label for="description" class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-1">Description:</label>
            <input type="text" id="description" name="description" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="<?php echo htmlspecialchars($edit_bill['description'] ?? ''); ?>" placeholder="e.g., Electricity Bill, Rent">
        </div>
        <div>
            <label for="amount" class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-1">Amount:</label>
            <input type="number" id="amount" name="amount" step="0.01" min="0.01" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="<?php echo htmlspecialchars($edit_bill['amount'] ?? ''); ?>">
        </div>
        <div>
            <label for="due_date" class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-1">Due Date:</label>
            <input type="date" id="due_date" name="due_date" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="<?php echo htmlspecialchars($edit_bill['due_date'] ?? date('Y-m-d')); ?>">
        </div>
        <div>
            <label for="category" class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-1">Category (Optional):</label>
            <input type="text" id="category" name="category" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="<?php echo htmlspecialchars($edit_bill['category'] ?? ''); ?>" placeholder="e.g., Utilities, Housing">
        </div>
        <div>
            <label for="recurring_frequency" class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-1">Recurring Frequency:</label>
            <select id="recurring_frequency" name="recurring_frequency" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">One-Time</option>
                <option value="daily" <?php echo (($edit_bill['recurring_frequency'] ?? '') === 'daily') ? 'selected' : ''; ?>>Daily</option>
                <option value="weekly" <?php echo (($edit_bill['recurring_frequency'] ?? '') === 'weekly') ? 'selected' : ''; ?>>Weekly</option>
                <option value="bi-weekly" <?php echo (($edit_bill['recurring_frequency'] ?? '') === 'bi-weekly') ? 'selected' : ''; ?>>Bi-Weekly (Every 2 Weeks)</option>
                <option value="fortnightly" <?php echo (($edit_bill['recurring_frequency'] ?? '') === 'fortnightly') ? 'selected' : ''; ?>>Fortnightly</option>
                <option value="semi-monthly" <?php echo (($edit_bill['recurring_frequency'] ?? '') === 'semi-monthly') ? 'selected' : ''; ?>>Semi-Monthly (Twice a Month)</option>
                <option value="monthly" <?php echo (($edit_bill['recurring_frequency'] ?? '') === 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                <option value="yearly" <?php echo (($edit_bill['recurring_frequency'] ?? '') === 'yearly') ? 'selected' : ''; ?>>Yearly</option>
            </select>
        </div>
        <?php if ($edit_bill): ?>
        <div class="flex items-center mt-2">
            <input type="checkbox" id="is_paid" name="is_paid" class="form-checkbox h-5 w-5 text-green-600 rounded dark:accent-green-600" <?php echo ($edit_bill['is_paid'] == 1) ? 'checked' : ''; ?>>
            <label for="is_paid" class="ml-2 text-gray-700 dark:text-gray-300 font-medium">Mark as Paid</label>
        </div>
        <?php endif; ?>

        <div class="md:col-span-2">
            <button type="submit" name="<?php echo ($edit_bill ? 'update_bill' : 'add_bill'); ?>" class="w-full bg-blue-600 text-white p-3 rounded-md hover:bg-blue-700 transition duration-300 shadow-md flex items-center justify-center">
                <i class="fa-solid fa-<?php echo ($edit_bill ? 'save' : 'plus'); ?> mr-2"></i> <?php echo ($edit_bill ? 'Update Bill' : 'Add Bill'); ?>
            </button>
            <?php if ($edit_bill): ?>
                <a href="bills.php" class="block text-center mt-3 text-blue-600 hover:underline dark:text-blue-400">Cancel Edit</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- All Bills List -->
<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
    <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 border-b border-gray-200 dark:border-gray-700 pb-2 flex items-center"><i class="fa-solid fa-clipboard-list mr-2"></i> All Your Bills</h3>
    <?php if (empty($all_bills)): ?>
        <p class="text-gray-600 dark:text-gray-400">No bills added yet.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Description</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Due Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Frequency</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($all_bills as $bill): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($bill['description']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo $user_currency_symbol . number_format($bill['amount'], 2); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <?php
                                    $today = new DateTime();
                                    $dueDate = new DateTime($bill['due_date']);
                                    if ($bill['is_paid'] == 0 && $dueDate < $today) {
                                        echo '<span class="text-red-500 font-semibold dark:text-red-400">' . htmlspecialchars($bill['due_date']) . ' (Overdue)</span>';
                                    } elseif ($bill['is_paid'] == 0 && $dueDate->diff($today)->days <= 7) {
                                        echo '<span class="text-orange-500 font-semibold dark:text-orange-400">' . htmlspecialchars($bill['due_date']) . ' (Due Soon)</span>';
                                    } else {
                                        echo htmlspecialchars($bill['due_date']);
                                    }
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <?php
                                    $freq_display = $bill['recurring_frequency'] ? ucfirst(str_replace('-', ' ', $bill['recurring_frequency'])) : 'One-Time';
                                    echo $freq_display;
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php if ($bill['is_paid']): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100">Paid</span>
                                <?php else: ?>
                                    <form action="bills.php" method="POST" class="inline-block">
                                        <input type="hidden" name="bill_id" value="<?php echo htmlspecialchars($bill['id']); ?>">
                                        <input type="hidden" name="status" value="1">
                                        <button type="submit" name="mark_paid" class="px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600">Mark Paid</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="bills.php?action=edit&id=<?php echo htmlspecialchars($bill['id']); ?>" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-4">Edit</a>
                                <a href="bills.php?action=delete&id=<?php echo htmlspecialchars($bill['id']); ?>" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" onclick="return confirm('Are you sure you want to delete this bill?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>