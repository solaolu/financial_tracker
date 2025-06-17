<?php
// Page to view and add new transactions, now also handling recurring transactions.
// This file replaces the previous transactions.php and recurring.php.

require_once 'header.php';
require_once 'functions.php';

$message = '';
$user_id = $_SESSION['user_id'];
$user_info = get_user_by_id($user_id);
$user_currency_symbol = get_currency_symbol($user_info['currency'] ?? 'USD');

// Pagination settings
$transactions_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $transactions_per_page;

// Filtering and Sorting parameters
$selected_month = $_GET['month'] ?? date('Y-m');
$search_term = $_GET['search'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'transaction_date';
$sort_order = $_GET['sort_order'] ?? 'DESC';

// Handle Add/Edit Transaction/Recurring Template
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_one_time_transaction'])) {
        $type = sanitize_input($_POST['type']);
        $amount = (float)sanitize_input($_POST['amount']);
        $category = sanitize_input($_POST['category']);
        $description = sanitize_input($_POST['description']);
        $date = sanitize_input($_POST['transaction_date']);

        if (add_transaction($user_id, $type, $amount, $category, $description, $date)) {
            // Fix for blank page redirect: Ensure only header is sent and then exit
            header('Location: transactions.php?msg=added');
            exit();
        } else {
            $message = "<p class='text-red-600 font-semibold mb-4'>Failed to add one-time transaction.</p>";
        }
    } elseif (isset($_POST['add_recurring_template'])) {
        $type = sanitize_input($_POST['type']);
        $amount = (float)sanitize_input($_POST['amount']);
        $category = sanitize_input($_POST['category']);
        $description = sanitize_input($_POST['description']);
        $frequency = sanitize_input($_POST['frequency']);
        $start_date = sanitize_input($_POST['start_date']);
        $end_date = !empty($_POST['end_date']) ? sanitize_input($_POST['end_date']) : null;

        if (add_recurring_transaction_template($user_id, $type, $amount, $category, $description, $frequency, $start_date, $end_date)) {
            generate_recurring_transactions($user_id); // Immediately generate for current date if applicable
            // Fix for blank page redirect: Ensure only header is sent and then exit
            header('Location: transactions.php?msg=recurring_added');
            exit();
        } else {
            $message = "<p class='text-red-600 font-semibold mb-4'>Failed to add recurring transaction template.</p>";
        }
    } elseif (isset($_POST['update_transaction'])) {
        // This logic is mostly handled by edit_transaction.php, but keeping for completeness
        // if a form on this page directly submits an update.
        $transaction_id = (int)sanitize_input($_POST['transaction_id']);
        $type = sanitize_input($_POST['type']);
        $amount = (float)sanitize_input($_POST['amount']);
        $category = sanitize_input($_POST['category']);
        $description = sanitize_input($_POST['description']);
        $date = sanitize_input($_POST['transaction_date']);

        // Need to fetch original transaction owner to ensure permission check
        $original_transaction = null;
        try {
            global $pdo; // Ensure $pdo is accessible here
            $stmt = $pdo->prepare("SELECT user_id FROM transactions WHERE id = ?");
            $stmt->execute([$transaction_id]);
            $original_transaction = $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error re-fetching transaction owner for permission check: " . $e->getMessage());
            $message = "<p class='text-red-600 font-semibold mb-4'>An error occurred during permission check.</p>";
        }

        if ($original_transaction && has_write_permission($original_transaction['user_id'], $user_id)) {
            if (update_transaction($transaction_id, $original_transaction['user_id'], $type, $amount, $category, $description, $date)) {
                header('Location: transactions.php?msg=updated');
                exit();
            } else {
                $message = "<p class='text-red-600 font-semibold mb-4'>Failed to update transaction.</p>";
            }
        } else {
            $message = "<p class='text-red-600 font-semibold mb-4'>You do not have permission to update this transaction.</p>";
        }
    }
}

// Handle Delete Transaction
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $transaction_id = (int)sanitize_input($_GET['id']);
    // Fetch transaction owner to check write permission
    $transaction_to_delete = null;
    try {
        global $pdo; // Ensure $pdo is accessible here
        $stmt = $pdo->prepare("SELECT user_id FROM transactions WHERE id = ?");
        $stmt->execute([$transaction_id]);
        $transaction_to_delete = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching transaction for delete permission check: " . $e->getMessage());
    }

    if ($transaction_to_delete && has_write_permission($transaction_to_delete['user_id'], $user_id)) {
        if (delete_transaction($transaction_id, $transaction_to_delete['user_id'])) {
            header('Location: transactions.php?' . http_build_query(array_diff_key($_GET, ['action' => '', 'id' => ''])) . '&msg=deleted'); // Remove action/id from query
            exit();
        } else {
            $message = "<p class='text-red-600 font-semibold mb-4'>Failed to delete transaction.</p>";
        }
    } else {
        $message = "<p class='text-red-600 font-semibold mb-4'>You do not have permission to delete this transaction.</p>";
    }
}
// Handle Delete Recurring Template
if (isset($_GET['action']) && $_GET['action'] === 'delete_template' && isset($_GET['id'])) {
    $template_id = (int)sanitize_input($_GET['id']);
    if (delete_recurring_transaction_template($template_id, $user_id)) {
        header('Location: transactions.php?' . http_build_query(array_diff_key($_GET, ['action' => '', 'id' => ''])) . '&msg=template_deleted'); // Redirect to clean URL
        exit();
    } else {
        $message = "<p class='text-red-600 font-semibold mb-4'>Failed to delete recurring template or you don't own it.</p>";
    }
}

// Display messages based on URL params
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'added':
            $message = "<p class='text-green-600 font-semibold mb-4'>One-time transaction added successfully!</p>";
            break;
        case 'recurring_added':
            $message = "<p class='text-green-600 font-semibold mb-4'>Recurring transaction template added successfully!</p>";
            break;
        case 'updated':
            $message = "<p class='text-green-600 font-semibold mb-4'>Transaction updated successfully!</p>";
            break;
        case 'deleted':
            $message = "<p class='text-green-600 font-semibold mb-4'>Transaction deleted successfully!</p>";
            break;
        case 'template_deleted':
            $message = "<p class='text-green-600 font-semibold mb-4'>Recurring template deleted successfully!</p>";
            break;
    }
}

// Get transactions for the current month to display
$accessible_user_ids = get_accessible_user_ids($user_id);
$transactions = get_filtered_sorted_paginated_transactions(
    $accessible_user_ids,
    $selected_month,
    $search_term,
    $sort_by,
    $sort_order,
    $transactions_per_page,
    $offset
);
$total_transactions = get_total_transaction_count(
    $accessible_user_ids,
    $selected_month,
    $search_term
);
$total_pages = ceil($total_transactions / $transactions_per_page);

$recurring_templates = get_recurring_transaction_templates($user_id);

?>

<h2 class="text-3xl font-semibold text-gray-800 dark:text-white mb-6 flex items-center"><i class="fa-solid fa-right-left mr-3"></i> Manage Your Transactions</h2>

<?php if (!empty($message)) echo $message; ?>

<!-- Transaction Add Form (One-time and Recurring) -->
<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg mb-8">
    <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 border-b border-gray-200 dark:border-gray-700 pb-2 flex items-center"><i class="fa-solid fa-plus-circle mr-2"></i> Add New Transaction</h3>
    <form action="transactions.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="type" class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-1">Type:</label>
            <select id="type" name="type" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="income">Income</option>
                <option value="expense">Expense</option>
            </select>
        </div>
        <div>
            <label for="amount" class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-1">Amount:</label>
            <input type="number" id="amount" name="amount" step="0.01" min="0.01" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
        <div>
            <label for="category" class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-1">Category:</label>
            <input type="text" id="category" name="category" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="e.g., Food, Salary, Rent">
        </div>
        <div>
            <label for="description" class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-1">Description (Optional):</label>
            <input type="text" id="description" name="description" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
        <div class="md:col-span-2">
            <label for="transaction_date" class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-1">Date:</label>
            <input type="date" id="transaction_date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>

        <div class="md:col-span-2 flex items-center space-x-2 mb-4">
            <input type="checkbox" id="is_recurring_checkbox" name="is_recurring" class="form-checkbox h-5 w-5 text-blue-600 rounded dark:accent-blue-600">
            <label for="is_recurring_checkbox" class="text-gray-700 dark:text-gray-300 font-medium">Is this a recurring transaction?</label>
        </div>

        <!-- Recurring Fields (Initially Hidden) -->
        <div id="recurring_fields" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
            <div>
                <label for="frequency" class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-1">Frequency:</label>
                <select id="frequency" name="frequency" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="bi-weekly">Bi-Weekly (Every 2 Weeks)</option>
                    <option value="fortnightly">Fortnightly</option>
                    <option value="semi-monthly">Semi-Monthly (Twice a Month)</option>
                    <option value="monthly" selected>Monthly</option>
                    <option value="yearly">Yearly</option>
                </select>
            </div>
            <div>
                <label for="start_date" class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-1">Start Date:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo date('Y-m-d'); ?>" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div class="md:col-span-2">
                <label for="end_date" class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-1">End Date (Optional):</label>
                <input type="date" id="end_date" name="end_date" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
        </div>

        <div class="md:col-span-2">
            <button type="submit" name="add_one_time_transaction" id="addOneTimeBtn" class="w-full bg-blue-600 text-white p-3 rounded-md hover:bg-blue-700 transition duration-300 shadow-md">Add One-Time Transaction</button>
            <button type="submit" name="add_recurring_template" id="addRecurringBtn" class="w-full bg-green-600 text-white p-3 rounded-md hover:bg-green-700 transition duration-300 shadow-md hidden">Add Recurring Template</button>
        </div>
    </form>
</div>

<!-- Transaction List with Filters, Sort, Search, Pagination -->
<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg mb-8">
    <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 border-b border-gray-200 dark:border-gray-700 pb-2 flex items-center"><i class="fa-solid fa-list-alt mr-2"></i> Your Transactions</h3>

    <!-- Filter, Sort, Search Controls -->
    <form action="transactions.php" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 items-end">
        <div>
            <label for="month_filter" class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-1">Filter by Month:</label>
            <select id="month_filter" name="month" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <?php
                $currentYear = date('Y');
                $currentMonth = date('m');
                for ($i = 0; $i < 24; $i++) { // Last 24 months
                    $monthValue = date('Y-m', strtotime("-$i months"));
                    $monthLabel = date('F Y', strtotime("-$i months"));
                    $selected = ($monthValue === $selected_month) ? 'selected' : '';
                    echo "<option value=\"{$monthValue}\" {$selected}>{$monthLabel}</option>";
                }
                ?>
            </select>
        </div>
        <div>
            <label for="search_input" class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-1">Search:</label>
            <input type="text" id="search_input" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Search description or category" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
        <div>
            <label for="sort_by" class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-1">Sort By:</label>
            <select id="sort_by" name="sort_by" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="transaction_date" <?php echo ($sort_by === 'transaction_date') ? 'selected' : ''; ?>>Date</option>
                <option value="amount" <?php echo ($sort_by === 'amount') ? 'selected' : ''; ?>>Amount</option>
                <option value="category" <?php echo ($sort_by === 'category') ? 'selected' : ''; ?>>Category</option>
                <option value="type" <?php echo ($sort_by === 'type') ? 'selected' : ''; ?>>Type</option>
            </select>
        </div>
        <div class="md:col-span-1">
            <label for="sort_order" class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-1">Order:</label>
            <select id="sort_order" name="sort_order" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="DESC" <?php echo ($sort_order === 'DESC') ? 'selected' : ''; ?>>Descending</option>
                <option value="ASC" <?php echo ($sort_order === 'ASC') ? 'selected' : ''; ?>>Ascending</option>
            </select>
        </div>
        <div class="md:col-span-1 flex items-center h-full">
            <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-300 shadow-md flex items-center justify-center">
                <i class="fa-solid fa-search mr-2"></i> Apply Filters
            </button>
        </div>
    </form>

    <?php if (empty($transactions)): ?>
        <p class="text-gray-600 dark:text-gray-400">No transactions found for the selected criteria.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Category</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Description</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($transactions as $transaction): ?>
                        <tr class="transaction-item">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($transaction['transaction_date']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $transaction['type'] === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?> font-medium">
                                <?php echo ucfirst(htmlspecialchars($transaction['type'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo $user_currency_symbol . number_format($transaction['amount'], 2); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($transaction['category']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-300">
                                <details>
                                    <summary class="cursor-pointer hover:underline">
                                        <?php echo (strlen($transaction['description']) > 50) ? htmlspecialchars(substr($transaction['description'], 0, 50)) . '...' : htmlspecialchars($transaction['description']); ?>
                                    </summary>
                                    <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                                        <?php echo htmlspecialchars($transaction['description']); ?>
                                    </p>
                                </details>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <?php
                                    // Only allow edit/delete if the current user has write permission for this transaction's owner
                                    if (has_write_permission($transaction['user_id'], $user_id)) :
                                ?>
                                <a href="edit_transaction.php?id=<?php echo $transaction['id']; ?>" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-4">Edit</a>
                                <a href="transactions.php?action=delete&id=<?php echo $transaction['id']; ?>&<?php echo http_build_query(array_diff_key($_GET, ['action' => '', 'id' => ''])); ?>" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" onclick="return confirm('Are you sure you want to delete this transaction?');">Delete</a>
                                <?php else: ?>
                                <span class="text-gray-400 dark:text-gray-500">View Only</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <nav class="mt-6 flex justify-center items-center space-x-2" aria-label="Pagination">
            <?php
            $current_query_params = $_GET;
            unset($current_query_params['page']); // Remove page from existing params
            $base_query_string = http_build_query($current_query_params);

            if ($current_page > 1):
                $prev_page_query = $base_query_string . '&page=' . ($current_page - 1);
            ?>
            <a href="transactions.php?<?php echo $prev_page_query; ?>" class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600">Previous</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php
                $page_query = $base_query_string . '&page=' . $i;
                $active_class = ($i === $current_page) ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600';
                ?>
                <a href="transactions.php?<?php echo $page_query; ?>" class="px-3 py-1 rounded-md <?php echo $active_class; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>

            <?php if ($current_page < $total_pages):
                $next_page_query = $base_query_string . '&page=' . ($current_page + 1);
            ?>
            <a href="transactions.php?<?php echo $next_page_query; ?>" class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600">Next</a>
            <?php endif; ?>
        </nav>

        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400 text-center">
            Showing <?php echo count($transactions); ?> of <?php echo $total_transactions; ?> transactions.
        </p>
    <?php endif; ?>
</div>

<!-- Recurring Templates List -->
<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
    <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4 border-b border-gray-200 dark:border-gray-700 pb-2 flex items-center"><i class="fa-solid fa-sync-alt mr-2"></i> Your Recurring Transaction Templates</h3>
    <?php if (empty($recurring_templates)): ?>
        <p class="text-gray-600 dark:text-gray-400">No recurring transaction templates set up yet. Add one above!</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Category</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Frequency</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Start Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">End Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Last Generated</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($recurring_templates as $template): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $template['type'] === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?> font-medium">
                                <?php echo ucfirst(htmlspecialchars($template['type'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo $user_currency_symbol . number_format($template['amount'], 2); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($template['category']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <?php
                                    $freq_display = ucfirst(str_replace('-', ' ', htmlspecialchars($template['frequency'])));
                                    echo $freq_display;
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($template['start_date']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($template['end_date'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($template['last_generated_date'] ?? 'Never'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="edit_recurring_template.php?id=<?php echo $template['id']; ?>" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-4">Edit</a>
                                <a href="transactions.php?action=delete_template&id=<?php echo $template['id']; ?>&<?php echo http_build_query(array_diff_key($_GET, ['action' => '', 'id' => ''])); ?>" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" onclick="return confirm('Are you sure you want to delete this recurring template? This will NOT delete past generated transactions.');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const isRecurringCheckbox = document.getElementById('is_recurring_checkbox');
        const recurringFields = document.getElementById('recurring_fields');
        const addOneTimeBtn = document.getElementById('addOneTimeBtn');
        const addRecurringBtn = document.getElementById('addRecurringBtn');
        const transactionDateInput = document.getElementById('transaction_date');
        const startDateInput = document.getElementById('start_date');

        function toggleRecurringFields() {
            if (isRecurringCheckbox.checked) {
                recurringFields.classList.remove('hidden');
                addOneTimeBtn.classList.add('hidden');
                addRecurringBtn.classList.remove('hidden');
                // Make recurring fields required when visible
                startDateInput.setAttribute('required', 'required');
                document.getElementById('frequency').setAttribute('required', 'required');

                // Remove required from transaction_date if recurring is selected
                transactionDateInput.removeAttribute('required');

            } else {
                recurringFields.classList.add('hidden');
                addOneTimeBtn.classList.remove('hidden');
                addRecurringBtn.classList.add('hidden');
                // Remove required from recurring fields when hidden
                startDateInput.removeAttribute('required');
                document.getElementById('frequency').removeAttribute('required');

                // Add required back to transaction_date if not recurring
                transactionDateInput.setAttribute('required', 'required');
            }
        }

        isRecurringCheckbox.addEventListener('change', toggleRecurringFields);
        toggleRecurringFields(); // Call on load to set initial state
    });
</script>

<?php require_once 'footer.php'; ?>