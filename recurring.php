<?php
// FILE: recurring.php
// Page to manage recurring transactions.

require_once 'header.php';
require_once 'functions.php';

$message = '';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_recurring'])) {
    $type = sanitize_input($_POST['type']);
    $amount = (float)sanitize_input($_POST['amount']);
    $category = sanitize_input($_POST['category']);
    $description = sanitize_input($_POST['description']);
    $frequency = sanitize_input($_POST['frequency']);
    $start_date = sanitize_input($_POST['start_date']);
    $end_date = !empty($_POST['end_date']) ? sanitize_input($_POST['end_date']) : null;

    if (add_recurring_transaction_template($user_id, $type, $amount, $category, $description, $frequency, $start_date, $end_date)) {
        $message = "<p class='text-green-600 font-semibold mb-4'>Recurring transaction added successfully!</p>";
    } else {
        $message = "<p class='text-red-600 font-semibold mb-4'>Failed to add recurring transaction.</p>";
    }
}

// Get recurring transaction templates for the user
$recurring_templates = get_recurring_transaction_templates($user_id);
?>

<h2 class="text-3xl font-semibold text-gray-800 mb-6">Manage Recurring Transactions</h2>

<?php if (!empty($message)) echo $message; ?>

<!-- Add Recurring Transaction Form -->
<div class="bg-white p-6 rounded-lg shadow-lg mb-8">
    <h3 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Add New Recurring Transaction</h3>
    <form action="recurring.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="type" class="block text-gray-700 text-sm font-medium mb-1">Type:</label>
            <select id="type" name="type" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                <option value="income">Income</option>
                <option value="expense">Expense</option>
            </select>
        </div>
        <div>
            <label for="amount" class="block text-gray-700 text-sm font-medium mb-1">Amount:</label>
            <input type="number" id="amount" name="amount" step="0.01" min="0.01" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label for="category" class="block text-gray-700 text-sm font-medium mb-1">Category:</label>
            <input type="text" id="category" name="category" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="e.g., Salary, Rent, Subscription">
        </div>
        <div>
            <label for="description" class="block text-gray-700 text-sm font-medium mb-1">Description (Optional):</label>
            <input type="text" id="description" name="description" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label for="frequency" class="block text-gray-700 text-sm font-medium mb-1">Frequency:</label>
            <select id="frequency" name="frequency" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly" selected>Monthly</option>
                <option value="yearly">Yearly</option>
            </select>
        </div>
        <div>
            <label for="start_date" class="block text-gray-700 text-sm font-medium mb-1">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div class="md:col-span-2">
            <label for="end_date" class="block text-gray-700 text-sm font-medium mb-1">End Date (Optional):</label>
            <input type="date" id="end_date" name="end_date" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div class="md:col-span-2">
            <button type="submit" name="add_recurring" class="w-full bg-green-600 text-white p-3 rounded-md hover:bg-green-700 transition duration-300 shadow-md">Add Recurring Transaction</button>
        </div>
    </form>
</div>

<!-- Recurring Transaction Templates List -->
<div class="bg-white p-6 rounded-lg shadow-lg">
    <h3 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">Your Recurring Templates</h3>
    <?php if (empty($recurring_templates)): ?>
        <p class="text-gray-600">No recurring transaction templates set up yet.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Frequency</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Generated</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($recurring_templates as $template): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $template['type'] === 'income' ? 'text-green-600' : 'text-red-600'; ?> font-medium">
                                <?php echo ucfirst(htmlspecialchars($template['type'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$<?php echo number_format(htmlspecialchars($template['amount']), 2); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($template['category']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo ucfirst(htmlspecialchars($template['frequency'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($template['start_date']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($template['end_date'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($template['last_generated_date'] ?? 'Never'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>