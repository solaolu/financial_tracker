<?php
// API endpoint to generate a monthly summary report (HTML for print/PDF).

session_start();
// Set content type to HTML, as we are generating an HTML page for printing.
// No specific PDF library is used directly here to keep the solution self-contained.
header('Content-Type: text/html');
// Optional: Suggest a filename for download if user saves the HTML
header('Content-Disposition: attachment; filename="financial_report_' . ($_GET['month'] ?? 'summary') . '.html"');

require_once '../functions.php';

if (!isset($_SESSION['user_id'])) {
    echo "<h1>Error: User not authenticated.</h1>";
    exit();
}

$current_user_id = $_SESSION['user_id'];
$month = $_GET['month'] ?? date('Y-m'); // Default to current month if not provided

$user_info = get_user_by_id($current_user_id);
$user_name = htmlspecialchars($user_info['name'] ?? 'User');
$user_currency_symbol = get_currency_symbol($user_info['currency'] ?? 'USD');

// Determine which user's data can be accessed.
$user_ids_to_fetch = get_accessible_user_ids($current_user_id);

if (empty($user_ids_to_fetch)) {
    echo "<h1>Error: No accessible users found to generate report for.</h1>";
    exit();
}

// Fetch monthly summary and category breakdown
$summary = get_monthly_summary($user_ids_to_fetch, $month);
$category_breakdown = get_category_breakdown($user_ids_to_fetch, $month);
$all_transactions = get_filtered_sorted_paginated_transactions($user_ids_to_fetch, $month, '', 'transaction_date', 'ASC', 999999, 0); // Fetch all for the month

$total_income = $summary['totalIncome'];
$total_expenses = $summary['totalExpenses'];
$net_balance = $total_income - $total_expenses;

$month_label = date('F Y', strtotime($month . '-01'));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Report - <?php echo $month_label; ?></title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 20px;
            color: #333;
            line-height: 1.6;
        }
        h1, h2, h3 {
            color: #1f2937;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .summary-card {
            background-color: #f9f9f9;
            border: 1px solid #eee;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .summary-card span {
            font-size: 1.5em;
            font-weight: bold;
        }
        .income { color: #10B981; } /* Green */
        .expense { color: #EF4444; } /* Red */
        .net { color: #3B82F6; } /* Blue */
        .net-negative { color: #EF4444; } /* Red for negative net */

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .text-income { color: #10B981; }
        .text-expense { color: #EF4444; }

        .category-breakdown-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
        }
        .category-breakdown-item:last-child {
            border-bottom: none;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .container {
                border: none;
                box-shadow: none;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header style="text-align: center; margin-bottom: 30px;">
            <h1 style="margin-bottom: 5px;">Financial Summary Report</h1>
            <p style="font-size: 1.1em; color: #555;">For <?php echo $month_label; ?></p>
            <p style="font-size: 0.9em; color: #777;">Generated for <?php echo $user_name; ?> on <?php echo date('Y-m-d H:i:s'); ?></p>
        </header>

        <section style="margin-bottom: 30px;">
            <h2 style="margin-bottom: 15px;">Overview</h2>
            <div class="summary-card">
                <div>Total Income:</div>
                <span class="income"><?php echo $user_currency_symbol . number_format($total_income, 2); ?></span>
            </div>
            <div class="summary-card">
                <div>Total Expenses:</div>
                <span class="expense"><?php echo $user_currency_symbol . number_format($total_expenses, 2); ?></span>
            </div>
            <div class="summary-card">
                <div>Net Balance:</div>
                <span class="<?php echo ($net_balance >= 0) ? 'net' : 'net-negative'; ?>">
                    <?php echo $user_currency_symbol . number_format($net_balance, 2); ?>
                </span>
            </div>
        </section>

        <section style="margin-bottom: 30px;">
            <h2 style="margin-bottom: 15px;">Spending by Category (Expenses)</h2>
            <?php if (empty($category_breakdown)): ?>
                <p>No expense data available for this month to break down by category.</p>
            <?php else: ?>
                <div style="background-color: #f9f9f9; border: 1px solid #eee; padding: 15px; border-radius: 8px;">
                    <?php foreach ($category_breakdown as $category => $amount): ?>
                        <div class="category-breakdown-item">
                            <span><?php echo htmlspecialchars($category); ?>:</span>
                            <span class="expense"><?php echo $user_currency_symbol . number_format($amount, 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section>
            <h2 style="margin-bottom: 15px;">All Transactions for <?php echo $month_label; ?></h2>
            <?php if (empty($all_transactions)): ?>
                <p>No transactions recorded for this month.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Category</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_transactions as $transaction): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($transaction['transaction_date']); ?></td>
                                <td class="<?php echo $transaction['type'] === 'income' ? 'text-income' : 'text-expense'; ?>">
                                    <?php echo ucfirst(htmlspecialchars($transaction['type'])); ?>
                                </td>
                                <td><?php echo $user_currency_symbol . number_format(htmlspecialchars($transaction['amount']), 2); ?></td>
                                <td><?php echo htmlspecialchars($transaction['category']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <footer style="text-align: center; margin-top: 50px; font-size: 0.8em; color: #777;">
            <p>This report is for informational purposes only and does not constitute financial advice.</p>
        </footer>
    </div>
    <!-- To prompt user to print, uncomment the script below if needed for specific browser behavior -->
    <!-- <script>window.onload = function() { window.print(); };</script> -->
</body>
</html>