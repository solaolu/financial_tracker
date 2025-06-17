<?php
// API endpoint to fetch monthly financial data for charts and summaries.

session_start();
header('Content-Type: application/json');

require_once '../functions.php'; // Adjust path if needed (e.g., ../functions.php if in a subfolder)

$response = ['error' => 'An unknown error occurred.'];

if (!isset($_SESSION['user_id'])) {
    $response = ['error' => 'User not authenticated.'];
    echo json_encode($response);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$month = $_GET['month'] ?? date('Y-m'); // Default to current month if not provided
$requested_user_id = $_GET['user_id'] ?? null; // Can be null if front-end doesn't specify (e.g., for self data)

// Determine which user's data can be accessed.
// If a specific user_id is requested via GET, ensure it's accessible by the current_user_id.
// Otherwise, fetch all accessible user IDs for aggregation.
$user_ids_to_fetch = [];
$accessible_users = get_accessible_user_ids($current_user_id);

if ($requested_user_id !== null) {
    // Cast to int to ensure type consistency with array values
    $requested_user_id = (int)$requested_user_id;
    // Check if the requested_user_id is in the list of accessible_users
    if (in_array($requested_user_id, $accessible_users)) {
        $user_ids_to_fetch = [$requested_user_id];
    } else {
        // If specific user data requested but not accessible, return error
        $response = ['error' => 'Not authorized to view data for the requested user.'];
        echo json_encode($response);
        exit();
    }
} else {
    // If no specific user_id is requested, fetch data for ALL accessible users
    $user_ids_to_fetch = $accessible_users;
}


if (empty($user_ids_to_fetch)) {
    $response = ['error' => 'No accessible users found to fetch data for.'];
    echo json_encode($response);
    exit();
}

// Fetch monthly summary
$summary = get_monthly_summary($user_ids_to_fetch, $month);

// Fetch category breakdown (for expenses)
$category_breakdown = get_category_breakdown($user_ids_to_fetch, $month);

if ($summary !== false && $category_breakdown !== false) {
    $response = [
        'total_income' => $summary['totalIncome'],
        'total_expenses' => $summary['totalExpenses'],
        'category_breakdown' => $category_breakdown,
        'month' => $month
    ];
} else {
    $response = ['error' => 'Failed to retrieve financial data.'];
}

echo json_encode($response);
?>