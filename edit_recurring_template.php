<?php
// Page to edit an existing recurring transaction template.

require_once 'header.php';
require_once 'functions.php';

$message = '';
$user_id = $_SESSION['user_id'];
$template = null;

// Incremental Change: Get user settings for currency display
$user_settings = get_user_settings($user_id);
$currency_symbol = htmlspecialchars($user_settings['currency_symbol'] ?? '$');

if (!isset($_GET['id'])) {
    $message = "<p class='text-red-600 font-semibold mb-4'>No recurring template ID provided.</p>";
} else {
    $template_id = (int)sanitize_input($_GET['id']);
    // Fetch a single recurring template by ID and user ID (need a new function in functions.php for this)
    $stmt = $pdo->prepare("SELECT * FROM recurring_transactions_templates WHERE id = ? AND user_id = ?");
    $stmt->execute([$template_id, $user_id]);
    $template = $stmt->fetch();

    if (!$template) {
        $message = "<p class='text-red-600 font-semibold mb-4'>Recurring template not found or you don't have permission to edit it.</p>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_recurring_template'])) {
    $template_id = (int)sanitize_input($_POST['template_id']);
    $type = sanitize_input($_POST['type']);
    $amount = (float)sanitize_input($_POST['amount']);
    $category = sanitize_input($_POST['category']);
    $description = sanitize_input($_POST['description']);
    $frequency = sanitize_input($_POST['frequency']);
    $start_date = sanitize_input($_POST['start_date']);
    $end_date = !empty($_POST['end_date']) ? sanitize_input($_POST['end_date']) : null;

    if (update_recurring_transaction_template($template_id, $user_id, $type, $amount, $category, $description, $frequency, $start_date, $end_date)) {
        $message = "<p class='text-green-600 font-semibold mb-4'>Recurring template updated successfully!</p>";
        // Re-fetch the template to show updated data on the form
        $stmt = $pdo->prepare("SELECT * FROM recurring_transactions_templates WHERE id = ? AND user_id = ?");
        $stmt->execute([$template_id, $user_id]);
        $template = $stmt->fetch();
    } else {
        $message = "<p class='text-red-600 font-semibold mb-4'>Failed to update recurring template or you don't own it.</p>";
    }
}
?>

<h2 class="text-3xl font-semibold text-gray-800 mb-6">Edit Recurring Template</h2>

<?php if (!empty($message)) echo $message; ?>

<?php if ($template): ?>
    <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
        <form action="edit_recurring_template.php?id=<?php echo htmlspecialchars($template['id']); ?>" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="hidden" name="template_id" value="<?php echo htmlspecialchars($template['id']); ?>">
            <div>
                <label for="type" class="block text-gray-700 text-sm font-medium mb-1">Type:</label>
                <select id="type" name="type" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <option value="income" <?php echo ($template['type'] === 'income') ? 'selected' : ''; ?>>Income</option>
                    <option value="expense" <?php echo ($template['type'] === 'expense') ? 'selected' : ''; ?>>Expense</option>
                </select>
            </div>
            <div>
                <label for="amount" class="block text-gray-700 text-sm font-medium mb-1">Amount:</label>
                <div class="flex">
                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                        <?php echo $currency_symbol; ?>
                    </span>
                    <input type="number" id="amount" name="amount" step="0.01" min="0.01" required class="flex-1 p-2 border border-gray-300 rounded-r-md focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($template['amount']); ?>">
                </div>
            </div>
            <div>
                <label for="category" class="block text-gray-700 text-sm font-medium mb-1">Category:</label>
                <input type="text" id="category" name="category" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($template['category']); ?>">
            </div>
            <div>
                <label for="description" class="block text-gray-700 text-sm font-medium mb-1">Description (Optional):</label>
                <input type="text" id="description" name="description" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($template['description']); ?>">
            </div>
            <div>
                <label for="frequency" class="block text-gray-700 text-sm font-medium mb-1">Frequency:</label>
                <select id="frequency" name="frequency" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <option value="daily" <?php echo ($template['frequency'] === 'daily') ? 'selected' : ''; ?>>Daily</option>
                    <option value="weekly" <?php echo ($template['frequency'] === 'weekly') ? 'selected' : ''; ?>>Weekly</option>
                    <option value="monthly" <?php echo ($template['frequency'] === 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                    <option value="yearly" <?php echo ($template['frequency'] === 'yearly') ? 'selected' : ''; ?>>Yearly</option>
                </select>
            </div>
            <div>
                <label for="start_date" class="block text-gray-700 text-sm font-medium mb-1">Start Date:</label>
                <input type="date" id="start_date" name="start_date" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($template['start_date']); ?>">
            </div>
            <div class="md:col-span-2">
                <label for="end_date" class="block text-gray-700 text-sm font-medium mb-1">End Date (Optional):</label>
                <input type="date" id="end_date" name="end_date" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($template['end_date'] ?? ''); ?>">
            </div>
            <div class="md:col-span-2">
                <button type="submit" name="update_recurring_template" class="w-full bg-blue-600 text-white p-3 rounded-md hover:bg-blue-700 transition duration-300 shadow-md">Update Recurring Template</button>
            </div>
        </form>
    </div>
<?php else: ?>
    <p class="text-gray-600">Please select a recurring template to edit from the <a href="transactions.php" class="text-blue-600 hover:underline">Transactions page</a>.</p>
<?php endif; ?>

<?php require_once 'footer.php'; ?>