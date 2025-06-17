<?php
// Page to import transactions from a CSV file.

require_once 'header.php';
require_once 'functions.php';

$message = '';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $upload_result = handle_csv_upload($user_id, $_FILES['csv_file']);
    $message = ($upload_result['success']) ?
        "<p class='text-green-600 font-semibold mb-4'>" . htmlspecialchars($upload_result['message']) . "</p>" :
        "<p class='text-red-600 font-semibold mb-4'>" . htmlspecialchars($upload_result['message']) . "</p>";
}
?>

<h2 class="text-3xl font-semibold text-gray-800 dark:text-white mb-6 flex items-center"><i class="fa-solid fa-upload mr-3"></i> Import Transactions from CSV</h2>

<?php if (!empty($message)) echo $message; ?>

<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg mb-8">
    <p class="text-gray-700 dark:text-gray-300 mb-4">
        Upload a CSV file containing your income and expense transactions.
        The CSV file should have the following column headers (case-insensitive):
        <code class="font-mono bg-gray-100 p-1 rounded dark:bg-gray-700">date</code>,
        <code class="font-mono bg-gray-100 p-1 rounded dark:bg-gray-700">type</code> (income/expense),
        <code class="font-mono bg-gray-100 p-1 rounded dark:bg-gray-700">amount</code>,
        <code class="font-mono bg-gray-100 p-1 rounded dark:bg-gray-700">category</code>.
        An optional <code class="font-mono bg-gray-100 p-1 rounded dark:bg-gray-700">description</code> column is also supported.
    </p>
    <p class="text-gray-700 dark:text-gray-300 mb-6">
        Date format should be recognizable by PHP's `strtotime()`, e.g., YYYY-MM-DD.
    </p>

    <form action="import_data.php" method="POST" enctype="multipart/form-data" class="space-y-4">
        <div>
            <label for="csv_file" class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-1">Select CSV File:</label>
            <input type="file" id="csv_file" name="csv_file" accept=".csv" required class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900 dark:file:text-blue-200 dark:hover:file:bg-blue-800">
        </div>
        <button type="submit" class="w-full bg-blue-600 text-white p-3 rounded-md hover:bg-blue-700 transition duration-300 shadow-md flex items-center justify-center">
            <i class="fa-solid fa-file-import mr-2"></i> Import Transactions
        </button>
    </form>
</div>

<?php require_once 'footer.php'; ?>