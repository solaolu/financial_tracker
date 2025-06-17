<?php
// Page to edit an existing one-time transaction.

require_once 'header.php';
require_once 'functions.php';

$message = '';
$user_id = $_SESSION['user_id'];
$transaction = null;

// Incremental Change: Get user settings for currency display
$user_settings = get_user_settings($user_id);
$currency_symbol = htmlspecialchars($user_settings['currency_symbol'] ?? '$');

if (!isset($_GET['id'])) {
    $message = "<p class='text-red-600 font-semibold mb-4'>No transaction ID provided.</p>";
} else {
    $transaction_id = (int)sanitize_input($_GET['id']);
    // Fetch the transaction by ID. We do NOT use get_transaction_by_id here
    // because that function also checks ownership, and we need to verify write
    // permission separately for shared data.
    try {
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
        $stmt->execute([$transaction_id]);
        $transaction = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching transaction for edit: " . $e->getMessage());
        $transaction = false;
    }


    if (!$transaction) {
        $message = "<p class='text-red-600 font-semibold mb-4'>Transaction not found.</p>";
    } else {
        // Check if current user has write permission for this transaction's owner
        if (!has_write_permission($transaction['user_id'], $user_id)) {
            $message = "<p class='text-red-600 font-semibold mb-4'>You do not have permission to edit this transaction.</p>";
            $transaction = false; // Prevent form from showing
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_transaction'])) {
    $transaction_id = (int)sanitize_input($_POST['transaction_id']);
    $type = sanitize_input($_POST['type']);
    $amount = (float)sanitize_input($_POST['amount']);
    $category = sanitize_input($_POST['category']);
    $description = sanitize_input($_POST['description']);
    $date = sanitize_input($_POST['transaction_date']);

    // Re-fetch transaction owner to check write permission again
    $original_transaction = null;
    try {
        $stmt = $pdo->prepare("SELECT user_id FROM transactions WHERE id = ?");
        $stmt->execute([$transaction_id]);
        $original_transaction = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error re-fetching transaction owner for permission check: " . $e->getMessage());
        $message = "<p class='text-red-600 font-semibold mb-4'>An error occurred during permission check.</p>";
    }

    if ($original_transaction && has_write_permission($original_transaction['user_id'], $user_id)) {
        if (update_transaction($transaction_id, $original_transaction['user_id'], $type, $amount, $category, $description, $date)) {
            $message = "<p class='text-green-600 font-semibold mb-4'>Transaction updated successfully!</p>";
            // Re-fetch the transaction to show updated data on the form
            $transaction = get_transaction_by_id($transaction_id, $original_transaction['user_id']); // Use the owner's ID
        } else {
            $message = "<p class='text-red-600 font-semibold mb-4'>Failed to update transaction.</p>";
        }
    } else {
        $message = "<p class='text-red-600 font-semibold mb-4'>You do not have permission to update this transaction.</p>";
    }
}
?>

<h2 class="text-3xl font-semibold text-gray-800 mb-6">Edit Transaction</h2>

<?php if (!empty($message)) echo $message; ?>

<?php if ($transaction): ?>
    <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
        <form action="edit_transaction.php?id=<?php echo htmlspecialchars($transaction['id']); ?>" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="hidden" name="transaction_id" value="<?php echo htmlspecialchars($transaction['id']); ?>">
            <div>
                <label for="type" class="block text-gray-700 text-sm font-medium mb-1">Type:</label>
                <select id="type" name="type" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <option value="income" <?php echo ($transaction['type'] === 'income') ? 'selected' : ''; ?>>Income</option>
                    <option value="expense" <?php echo ($transaction['type'] === 'expense') ? 'selected' : ''; ?>>Expense</option>
                </select>
            </div>
            <div>
                <label for="amount" class="block text-gray-700 text-sm font-medium mb-1">Amount:</label>
                <div class="flex">
                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                        <?php echo $currency_symbol; ?>
                    </span>
                    <input type="number" id="amount" name="amount" step="0.01" min="0.01" required class="flex-1 p-2 border border-gray-300 rounded-r-md focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($transaction['amount']); ?>">
                </div>
            </div>
            <div>
                <label for="category" class="block text-gray-700 text-sm font-medium mb-1">Category:</label>
                <input type="text" id="category" name="category" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($transaction['category']); ?>">
            </div>
            <div>
                <label for="description" class="block text-gray-700 text-sm font-medium mb-1">Description (Optional):</label>
                <input type="text" id="description" name="description" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($transaction['description']); ?>">
            </div>
            <div class="md:col-span-2">
                <label for="transaction_date" class="block text-gray-700 text-sm font-medium mb-1">Date:</label>
                <input type="date" id="transaction_date" name="transaction_date" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($transaction['transaction_date']); ?>">
            </div>
            <div class="md:col-span-2">
                <button type="submit" name="update_transaction" class="w-full bg-blue-600 text-white p-3 rounded-md hover:bg-blue-700 transition duration-300 shadow-md">Update Transaction</button>
            </div>
        </form>
    </div>
<?php else: ?>
    <p class="text-gray-600">Please select a transaction to edit from the <a href="transactions.php" class="text-blue-600 hover:underline">Transactions page</a>.</p>
<?php endif; ?>

<?php require_once 'footer.php'; ?>