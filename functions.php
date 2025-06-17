<?php
// This file contains all the core PHP functions for the application.

require_once 'db_connect.php'; // Include the database connection

/**
 * Validates user input to prevent SQL injection and XSS.
 * @param string $data The input string to sanitize.
 * @return string The sanitized string.
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Hashes a password for secure storage.
 * @param string $password The plain text password.
 * @return string The hashed password.
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verifies a password against a hash.
 * @param string $password The plain text password.
 * @param string $hash The hashed password from the database.
 * @return bool True if the password matches, false otherwise.
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Registers a new user.
 * @param string $username The desired username.
 * @param string $password The user's password.
 * @param string $name The user's full name.
 * @return bool True on success, false on failure (e.g., username taken).
 */
function register_user($username, $password, $name) {
    global $pdo;
    try {
        $hashed_password = hash_password($password);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, name) VALUES (?, ?, ?)");
        return $stmt->execute([$username, $hashed_password, $name]);
    } catch (PDOException $e) {
        // Log the error, e.g., duplicate username
        error_log("User registration error: " . $e->getMessage());
        return false;
    }
}

/**
 * Logs in a user.
 * @param string $username The username.
 * @param string $password The password.
 * @return int|false User ID on success, false on failure.
 */
function login_user($username, $password) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && verify_password($password, $user['password'])) {
            return $user['id'];
        }
        return false;
    } catch (PDOException $e) {
        error_log("User login error: " . $e->getMessage());
        return false;
    }
}

/**
 * Adds a new transaction (income or expense).
 * @param int $user_id The ID of the user.
 * @param string $type 'income' or 'expense'.
 * @param float $amount The transaction amount.
 * @param string $category The transaction category.
 * @param string $description Optional description.
 * @param string $date The date of the transaction (YYYY-MM-DD).
 * @return bool True on success, false on failure.
 */
function add_transaction($user_id, $type, $amount, $category, $description, $date) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, category, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$user_id, $type, $amount, $category, $description, $date]);
    } catch (PDOException $e) {
        error_log("Add transaction error: " . $e->getMessage());
        return false;
    }
}

/**
 * Updates an existing transaction.
 * @param int $transaction_id The ID of the transaction to update.
 * @param int $user_id The ID of the user (for security check).
 * @param string $type 'income' or 'expense'.
 * @param float $amount The transaction amount.
 * @param string $category The transaction category.
 * @param string $description Optional description.
 * @param string $date The date of the transaction (YYYY-MM-DD).
 * @return bool True on success, false on failure.
 */
function update_transaction($transaction_id, $user_id, $type, $amount, $category, $description, $date) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE transactions SET type = ?, amount = ?, category = ?, description = ?, transaction_date = ? WHERE id = ? AND user_id = ?");
        return $stmt->execute([$type, $amount, $category, $description, $date, $transaction_id, $user_id]);
    } catch (PDOException $e) {
        error_log("Update transaction error: " . $e->getMessage());
        return false;
    }
}

/**
 * Deletes a transaction.
 * @param int $transaction_id The ID of the transaction to delete.
 * @param int $user_id The ID of the user (for security check).
 * @return bool True on success, false on failure.
 */
function delete_transaction($transaction_id, $user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
        return $stmt->execute([$transaction_id, $user_id]);
    } catch (PDOException $e) {
        error_log("Delete transaction error: " . $e->getMessage());
        return false;
    }
}

/**
 * Retrieves transactions for a user (or multiple users) with filtering, sorting, and pagination.
 * @param array $user_ids An array of user IDs whose transactions to retrieve.
 * @param string $month 'YYYY-MM' format (optional, default to all months if empty).
 * @param string $search_term Search term for description/category.
 * @param string $sort_by Column to sort by.
 * @param string $sort_order 'ASC' or 'DESC'.
 * @param int $limit Number of results per page.
 * @param int $offset Offset for pagination.
 * @return array A list of transactions.
 */
function get_filtered_sorted_paginated_transactions(array $user_ids, $month = '', $search_term = '', $sort_by = 'transaction_date', $sort_order = 'DESC', $limit = 10, $offset = 0) {
    global $pdo;
    if (empty($user_ids)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
    $sql = "SELECT * FROM transactions WHERE user_id IN ($placeholders)";
    $params = $user_ids;

    if (!empty($month)) {
        $sql .= " AND STRFTIME('%Y-%m', transaction_date) = ?";
        $params[] = $month;
    }

    if (!empty($search_term)) {
        $sql .= " AND (description LIKE ? OR category LIKE ?)";
        $params[] = '%' . $search_term . '%';
        $params[] = '%' . $search_term . '%';
    }

    // Validate sort_by to prevent SQL injection for column names
    $allowed_sort_columns = ['transaction_date', 'amount', 'category', 'type'];
    if (!in_array($sort_by, $allowed_sort_columns)) {
        $sort_by = 'transaction_date'; // Default to a safe column
    }

    // Validate sort_order
    $sort_order = (strtoupper($sort_order) === 'ASC') ? 'ASC' : 'DESC';

    $sql .= " ORDER BY $sort_by $sort_order LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get filtered/sorted/paginated transactions error: " . $e->getMessage());
        return [];
    }
}

/**
 * Gets the total count of transactions for pagination.
 * @param array $user_ids An array of user IDs.
 * @param string $month 'YYYY-MM' format.
 * @param string $search_term Search term.
 * @return int Total number of transactions.
 */
function get_total_transaction_count(array $user_ids, $month = '', $search_term = '') {
    global $pdo;
    if (empty($user_ids)) {
        return 0;
    }

    $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
    $sql = "SELECT COUNT(*) FROM transactions WHERE user_id IN ($placeholders)";
    $params = $user_ids;

    if (!empty($month)) {
        $sql .= " AND STRFTIME('%Y-%m', transaction_date) = ?";
        $params[] = $month;
    }

    if (!empty($search_term)) {
        $sql .= " AND (description LIKE ? OR category LIKE ?)";
        $params[] = '%' . $search_term . '%';
        $params[] = '%' . $search_term . '%';
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Get total transaction count error: " . $e->getMessage());
        return 0;
    }
}


/**
 * Gets a single transaction by ID and user ID.
 * @param int $transaction_id The ID of the transaction.
 * @param int $user_id The ID of the user.
 * @return array|false The transaction data or false if not found/not owned by user.
 */
function get_transaction_by_id($transaction_id, $user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
        $stmt->execute([$transaction_id, $user_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get transaction by ID error: " . $e->getMessage());
        return false;
    }
}


/**
 * Gets a monthly summary (total income, total expenses) for a user (or multiple users).
 * @param array $user_ids An array of user IDs for the summary.
 * @param string $month 'YYYY-MM' format.
 * @return array Associative array with total income and expenses.
 */
function get_monthly_summary(array $user_ids, $month) {
    global $pdo;
    if (empty($user_ids)) {
        return ['totalIncome' => 0, 'totalExpenses' => 0];
    }
    $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
    try {
        $stmt = $pdo->prepare("
            SELECT
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS total_income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS total_expenses
            FROM transactions
            WHERE user_id IN ($placeholders) AND STRFTIME('%Y-%m', transaction_date) = ?
        ");
        $stmt->execute(array_merge($user_ids, [$month]));
        $summary = $stmt->fetch();
        return [
            'totalIncome' => (float)($summary['total_income'] ?? 0),
            'totalExpenses' => (float)($summary['total_expenses'] ?? 0)
        ];
    } catch (PDOException $e) {
        error_log("Get monthly summary error: " . $e->getMessage());
        return ['totalIncome' => 0, 'totalExpenses' => 0];
    }
}

/**
 * Gets expense breakdown by category for a user (or multiple users) for a specific month.
 * @param array $user_ids An array of user IDs for the breakdown.
 * @param string $month 'YYYY-MM' format.
 * @return array Associative array with category as key and total amount as value.
 */
function get_category_breakdown(array $user_ids, $month) {
    global $pdo;
    if (empty($user_ids)) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
    try {
        $stmt = $pdo->prepare("
            SELECT category, SUM(amount) AS total_amount
            FROM transactions
            WHERE user_id IN ($placeholders) AND type = 'expense' AND STRFTIME('%Y-%m', transaction_date) = ?
            GROUP BY category
            ORDER BY total_amount DESC
        ");
        $stmt->execute(array_merge($user_ids, [$month]));
        $breakdown = [];
        while ($row = $stmt->fetch()) {
            $breakdown[$row['category']] = (float)$row['total_amount'];
        }
        return $breakdown;
    } catch (PDOException $e) {
        error_log("Get category breakdown error: " . $e->getMessage());
        return [];
    }
}

/**
 * Adds a new recurring transaction template.
 * @param int $user_id
 * @param string $type 'income' or 'expense'.
 * @param float $amount
 * @param string $category
 * @param string $description
 * @param string $frequency 'daily', 'weekly', 'bi-weekly', 'fortnightly', 'semi-monthly', 'monthly', 'yearly'.
 * @param string $start_date
 * @param string|null $end_date
 * @return bool True on success, false on failure.
 */
function add_recurring_transaction_template($user_id, $type, $amount, $category, $description, $frequency, $start_date, $end_date = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO recurring_transactions_templates (user_id, type, amount, category, description, frequency, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$user_id, $type, $amount, $category, $description, $frequency, $start_date, $end_date]);
    } catch (PDOException $e) {
        error_log("Add recurring transaction template error: " . $e->getMessage());
        return false;
    }
}

/**
 * Updates an existing recurring transaction template.
 * @param int $template_id The ID of the template to update.
 * @param int $user_id The ID of the user (for security check).
 * @param string $type 'income' or 'expense'.
 * @param float $amount
 * @param string $category
 * @param string $description
 * @param string $frequency 'daily', 'weekly', 'bi-weekly', 'fortnightly', 'semi-monthly', 'monthly', 'yearly'.
 * @param string $start_date
 * @param string|null $end_date
 * @return bool True on success, false on failure.
 */
function update_recurring_transaction_template($template_id, $user_id, $type, $amount, $category, $description, $frequency, $start_date, $end_date = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE recurring_transactions_templates SET type = ?, amount = ?, category = ?, description = ?, frequency = ?, start_date = ?, end_date = ? WHERE id = ? AND user_id = ?");
        return $stmt->execute([$type, $amount, $category, $description, $frequency, $start_date, $end_date, $template_id, $user_id]);
    } catch (PDOException $e) {
        error_log("Update recurring transaction template error: " . $e->getMessage());
        return false;
    }
}

/**
 * Deletes a recurring transaction template.
 * @param int $template_id The ID of the template to delete.
 * @param int $user_id The ID of the user (for security check).
 * @return bool True on success, false on failure.
 */
function delete_recurring_transaction_template($template_id, $user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM recurring_transactions_templates WHERE id = ? AND user_id = ?");
        return $stmt->execute([$template_id, $user_id]);
    } catch (PDOException $e) {
        error_log("Delete recurring transaction template error: " . $e->getMessage());
        return false;
    }
}


/**
 * Retrieves all recurring transaction templates for a user.
 * @param int $user_id
 * @return array
 */
function get_recurring_transaction_templates($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM recurring_transactions_templates WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get recurring transaction templates error: " . $e->getMessage());
        return [];
    }
}

/**
 * Function to generate transactions from recurring templates.
 * This function would typically be run as a daily/weekly cron job.
 * @param int|null $user_id Optional: Generate for a specific user. If null, generate for all.
 */
function generate_recurring_transactions($user_id = null) {
    global $pdo;
    $current_date = date('Y-m-d');

    $query = "SELECT * FROM recurring_transactions_templates WHERE start_date <= ?";
    $params = [$current_date];

    if ($user_id !== null) {
        $query .= " AND user_id = ?";
        $params[] = $user_id;
    }

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $templates = $stmt->fetchAll();

        foreach ($templates as $template) {
            $last_generated_date = $template['last_generated_date'] ? $template['last_generated_date'] : $template['start_date'];
            $next_generation_date = '';

            // Calculate next generation date based on frequency
            $dt_last_generated = new DateTime($last_generated_date);
            $dt_next_generation = clone $dt_last_generated; // Start from last generated date

            switch ($template['frequency']) {
                case 'daily':
                    $dt_next_generation->modify('+1 day');
                    break;
                case 'weekly':
                    $dt_next_generation->modify('+1 week');
                    break;
                case 'bi-weekly': // Every 2 weeks
                    $dt_next_generation->modify('+2 weeks');
                    break;
                case 'fortnightly': // Same as bi-weekly
                    $dt_next_generation->modify('+2 weeks');
                    break;
                case 'semi-monthly': // Twice a month - tricky, usually 1st and 15th, or 15th and last day
                    // This implementation assumes a fixed number of days for simplicity.
                    // A more robust implementation might consider specific dates (e.g., 1st and 15th).
                    // For now, we will add 15 days, and if it crosses month boundary, adjust to 15th or end of month.
                    $current_day = (int)$dt_last_generated->format('d');
                    if ($current_day < 15) {
                        $dt_next_generation->setDate($dt_last_generated->format('Y'), $dt_last_generated->format('m'), 15);
                    } else {
                        $dt_next_generation->modify('+1 month');
                        $dt_next_generation->setDate($dt_next_generation->format('Y'), $dt_next_generation->format('m'), 1); // Go to 1st of next month
                    }
                    break;
                case 'monthly':
                    // Handle month-end accurately (e.g., if last generated was Jan 31, next is Feb 28/29)
                    $day_of_month = $dt_last_generated->format('j');
                    $dt_next_generation->modify('+1 month');
                    $target_day_of_next_month = $dt_next_generation->format('t'); // Max day in the month
                    if ($day_of_month > $target_day_of_next_month) {
                        $dt_next_generation->setDate($dt_next_generation->format('Y'), $dt_next_generation->format('m'), $target_day_of_next_month);
                    } else {
                        $dt_next_generation->setDate($dt_next_generation->format('Y'), $dt_next_generation->format('m'), $day_of_month);
                    }
                    break;
                case 'yearly':
                    $dt_next_generation->modify('+1 year');
                    break;
            }
            $next_generation_date = $dt_next_generation->format('Y-m-d');


            // Only generate if next date is on or before current date, and not past end_date
            // AND ensure we don't generate future transactions (next_generation_date <= current_date)
            if ($next_generation_date <= $current_date &&
                ($template['end_date'] == null || $next_generation_date <= $template['end_date'])) {

                // Check if transaction for this date already exists to prevent duplicates
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND type = ? AND amount = ? AND category = ? AND description LIKE ? AND transaction_date = ?");
                $check_stmt->execute([$template['user_id'], $template['type'], $template['amount'], $template['category'], $template['description'] . " (Recurring from template ID: " . $template['id'] . ")%", $next_generation_date]);
                $exists = $check_stmt->fetchColumn();

                if (!$exists) {
                    add_transaction(
                        $template['user_id'],
                        $template['type'],
                        $template['amount'],
                        $template['category'],
                        $template['description'] . " (Recurring from template ID: " . $template['id'] . ")", // Add template ID for better tracking
                        $next_generation_date
                    );

                    // Update last generated date in template
                    $update_stmt = $pdo->prepare("UPDATE recurring_transactions_templates SET last_generated_date = ? WHERE id = ?");
                    $update_stmt->execute([$next_generation_date, $template['id']]);
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Generate recurring transactions error: " . $e->getMessage());
    }
}

/**
 * Finds a user by username.
 * @param string $username
 * @return array|false User data or false if not found.
 */
function get_user_by_username($username) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, username, name FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get user by username error: " . $e->getMessage());
        return false;
    }
}

/**
 * Finds a user by ID.
 * @param int $user_id
 * @return array|false User data or false if not found.
 */
function get_user_by_id($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, username, name, currency, dark_mode_enabled FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get user by ID error: " . $e->getMessage());
        return false;
    }
}

/**
 * Retrieves all registered users (excluding the current user).
 * @param int $current_user_id The ID of the currently logged-in user.
 * @return array List of user arrays (id, username, name).
 */
function get_all_other_users($current_user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, username, name FROM users WHERE id != ? ORDER BY name ASC");
        $stmt->execute([$current_user_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get all other users error: " . $e->getMessage());
        return [];
    }
}

/**
 * Generates a unique password reset token.
 * @return string
 */
function generate_reset_token() {
    return bin2hex(random_bytes(32)); // 64 character hex string
}

/**
 * Stores a password reset token in the database.
 * @param int $user_id
 * @param string $token
 * @param int $expires_in_seconds Token expiration time in seconds (e.g., 3600 for 1 hour).
 * @return bool
 */
function store_reset_token($user_id, $token, $expires_in_seconds = 3600) {
    global $pdo;
    try {
        // Delete any existing tokens for this user first
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
        $stmt->execute([$user_id]);

        $expires_at = date('Y-m-d H:i:s', time() + $expires_in_seconds);
        $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
        return $stmt->execute([$user_id, $token, $expires_at]);
    } catch (PDOException $e) {
        error_log("Store reset token error: " . $e->getMessage());
        return false;
    }
}

/**
 * Validates a password reset token.
 * @param string $token
 * @return int|false User ID if token is valid and not expired, false otherwise.
 */
function validate_reset_token($token) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        $record = $stmt->fetch();

        if ($record) {
            $expires_at = new DateTime($record['expires_at']);
            $now = new DateTime();
            if ($now < $expires_at) {
                return $record['user_id'];
            }
        }
        return false;
    } catch (PDOException $e) {
        error_log("Validate reset token error: " . $e->getMessage());
        return false;
    }
}

/**
 * Updates a user's password and invalidates the reset token.
 * @param int $user_id
 * @param string $new_password
 * @param string $token
 * @return bool True on success, false on failure.
 */
function update_password_with_token($user_id, $new_password, $token) {
    global $pdo;
    try {
        $pdo->beginTransaction(); // Start a transaction

        $hashed_password = hash_password($new_password);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update_user_success = $stmt->execute([$hashed_password, $user_id]);

        if ($update_user_success) {
            // Invalidate the token after use
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
            $delete_token_success = $stmt->execute([$token]);

            if ($delete_token_success) {
                $pdo->commit(); // Commit the transaction
                return true;
            }
        }
        $pdo->rollBack(); // Rollback if any step failed
        return false;

    } catch (PDOException $e) {
        $pdo->rollBack(); // Rollback on exception
        error_log("Update password with token error: " . $e->getMessage());
        return false;
    }
}

/**
 * Adds a new data sharing entry.
 * @param int $owner_user_id The ID of the user who owns the data.
 * @param int $shared_with_user_id The ID of the user with whom data is shared.
 * @param string $permission_level 'read_only' or 'read_write'.
 * @return bool True on success, false on failure.
 */
function add_data_share($owner_user_id, $shared_with_user_id, $permission_level) {
    global $pdo;
    try {
        // Prevent sharing with self
        if ($owner_user_id == $shared_with_user_id) {
            return false;
        }

        $stmt = $pdo->prepare("INSERT INTO data_shares (owner_user_id, shared_with_user_id, permission_level) VALUES (?, ?, ?)");
        return $stmt->execute([$owner_user_id, $shared_with_user_id, $permission_level]);
    } catch (PDOException $e) {
        // Handle unique constraint violation (share already exists) or other errors
        error_log("Add data share error: " . $e->getMessage());
        return false;
    }
}

/**
 * Updates an existing data sharing entry.
 * @param int $share_id The ID of the share entry.
 * @param int $owner_user_id The ID of the user who owns the share (for security).
 * @param string $permission_level 'read_only' or 'read_write'.
 * @return bool True on success, false on failure.
 */
function update_data_share($share_id, $owner_user_id, $permission_level) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE data_shares SET permission_level = ? WHERE id = ? AND owner_user_id = ?");
        return $stmt->execute([$permission_level, $share_id, $owner_user_id]);
    } catch (PDOException $e) {
        error_log("Update data share error: " . $e->getMessage());
        return false;
    }
}

/**
 * Deletes a data sharing entry.
 * @param int $share_id The ID of the share entry.
 * @param int $owner_user_id The ID of the user who owns the share (for security).
 * @return bool True on success, false on failure.
 */
function delete_data_share($share_id, $owner_user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM data_shares WHERE id = ? AND owner_user_id = ?");
        return $stmt->execute([$share_id, $owner_user_id]);
    } catch (PDOException $e) {
        error_log("Delete data share error: " . $e->getMessage());
        return false;
    }
}

/**
 * Gets all data sharing entries created by a specific user.
 * @param int $owner_user_id The ID of the user who owns the shares.
 * @return array List of sharing entries.
 */
function get_shares_by_owner($owner_user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT ds.id, ds.shared_with_user_id, u.username, u.name, ds.permission_level
                               FROM data_shares ds
                               JOIN users u ON ds.shared_with_user_id = u.id
                               WHERE ds.owner_user_id = ?
                               ORDER BY u.name ASC");
        $stmt->execute([$owner_user_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get shares by owner error: " . $e->getMessage());
        return [];
    }
}

/**
 * Gets all data sharing entries where a specific user is the recipient (data shared with them).
 * @param int $shared_with_user_id The ID of the user who is the recipient of the shares.
 * @return array List of sharing entries.
 */
function get_shares_for_user($shared_with_user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT ds.id, ds.owner_user_id, u.username AS owner_username, u.name AS owner_name, ds.permission_level
                               FROM data_shares ds
                               JOIN users u ON ds.owner_user_id = u.id
                               WHERE ds.shared_with_user_id = ?
                               ORDER BY u.name ASC");
        $stmt->execute([$shared_with_user_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get shares for user error: " . $e->getMessage());
        return [];
    }
}

/**
 * Determines all user IDs whose data the current user can access (their own + shared data).
 * @param int $current_user_id The ID of the currently logged-in user.
 * @return array An array of user IDs.
 */
function get_accessible_user_ids($current_user_id) {
    global $pdo;
    $accessible_ids = [$current_user_id]; // Always include their own data

    try {
        // Find data that has been shared *with* the current user
        $stmt = $pdo->prepare("SELECT DISTINCT owner_user_id FROM data_shares WHERE shared_with_user_id = ?");
        $stmt->execute([$current_user_id]);
        $shared_owners = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $accessible_ids = array_unique(array_merge($accessible_ids, $shared_owners));

        return $accessible_ids;
    } catch (PDOException $e) {
        error_log("Get accessible user IDs error: " . $e->getMessage());
        return [$current_user_id]; // Fallback to only own data in case of error
    }
}

/**
 * Checks if a user has 'read_write' permission for another user's data.
 * @param int $owner_user_id The ID of the data owner.
 * @param int $current_user_id The ID of the user attempting to write.
 * @return bool True if 'read_write' permission is granted (or if owner is self), false otherwise.
 */
function has_write_permission($owner_user_id, $current_user_id) {
    global $pdo;
    // Owner always has write permission on their own data
    if ($owner_user_id == $current_user_id) {
        return true;
    }

    try {
        $stmt = $pdo->prepare("SELECT permission_level FROM data_shares WHERE owner_user_id = ? AND shared_with_user_id = ?");
        $stmt->execute([$owner_user_id, $current_user_id]);
        $share = $stmt->fetch();

        return ($share && $share['permission_level'] === 'read_write');
    } catch (PDOException $e) {
        error_log("Has write permission error: " . $e->getMessage());
        return false; // Assume no write permission on error
    }
}

/**
 * Handles CSV file upload and parses transactions.
 * Expected CSV columns: date, type, amount, category, description (optional).
 * @param int $user_id The ID of the user uploading the data.
 * @param array $file_info The $_FILES array entry for the uploaded file.
 * @return array An array containing 'success' (bool) and 'message' (string).
 */
function handle_csv_upload($user_id, $file_info) {
    if ($file_info['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error: ' . $file_info['error']];
    }

    $file_path = $file_info['tmp_name'];
    $mime_type = mime_content_type($file_path);

    // Basic MIME type check for CSV
    if ($mime_type !== 'text/csv' && $mime_type !== 'application/vnd.ms-excel' && $mime_type !== 'text/plain') { // Added text/plain for broader compatibility
        return ['success' => false, 'message' => 'Invalid file type. Please upload a CSV file.'];
    }

    $imported_count = 0;
    $failed_count = 0;
    $errors = [];

    if (($handle = fopen($file_path, "r")) !== FALSE) {
        // Read the header row (assuming first row is header)
        $header = fgetcsv($handle);
        if ($header === FALSE) {
            return ['success' => false, 'message' => 'Could not read CSV header or empty file.'];
        }

        // Map header columns to expected database fields
        $column_map = [];
        $expected_columns = ['date', 'type', 'amount', 'category', 'description'];
        foreach ($expected_columns as $expected) {
            $found_index = array_search($expected, array_map('strtolower', $header));
            if ($found_index !== false) {
                $column_map[$expected] = $found_index;
            }
        }

        // Check for essential columns
        if (!isset($column_map['date']) || !isset($column_map['type']) || !isset($column_map['amount']) || !isset($column_map['category'])) {
            return ['success' => false, 'message' => 'Missing essential columns in CSV: date, type, amount, category are required.'];
        }


        $row_number = 1; // Start from 1 for data rows after header
        while (($data = fgetcsv($handle)) !== FALSE) {
            $row_number++;
            // A more robust check might be needed here depending on CSV variability
            if (count($data) < count($column_map) - (isset($column_map['description']) ? 1 : 0)) {
                 $errors[] = "Row $row_number: Malformed row (not enough expected columns). Skipped.";
                 $failed_count++;
                 continue;
            }


            $transaction_date = trim($data[$column_map['date']]);
            $type = strtolower(trim($data[$column_map['type']]));
            $amount = (float)trim($data[$column_map['amount']]);
            $category = trim($data[$column_map['category']]);
            $description = isset($column_map['description']) ? trim($data[$column_map['description']]) : '';

            // Basic validation
            if (!in_array($type, ['income', 'expense'])) {
                $errors[] = "Row $row_number: Invalid type '$type'. Must be 'income' or 'expense'. Skipped.";
                $failed_count++;
                continue;
            }
            if ($amount <= 0) {
                $errors[] = "Row $row_number: Amount must be positive. Skipped.";
                $failed_count++;
                continue;
            }
            // Try to parse date
            $parsed_date = strtotime($transaction_date);
            if (empty($transaction_date) || $parsed_date === false) {
                $errors[] = "Row $row_number: Invalid date format for '$transaction_date'. Expected 'YYYY-MM-DD' or similar. Skipped.";
                $failed_count++;
                continue;
            }
            $transaction_date_formatted = date('Y-m-d', $parsed_date);

            if (empty($category)) {
                $errors[] = "Row $row_number: Category cannot be empty. Skipped.";
                $failed_count++;
                continue;
            }

            // Attempt to add transaction
            if (add_transaction($user_id, $type, $amount, $category, $description, $transaction_date_formatted)) {
                $imported_count++;
            } else {
                $errors[] = "Row $row_number: Failed to insert transaction into database (possible database error or duplicate). Skipped.";
                $failed_count++;
            }
        }
        fclose($handle);
    } else {
        return ['success' => false, 'message' => 'Could not open uploaded file.'];
    }

    $summary_message = "Import completed. Successfully imported: $imported_count. Failed to import: $failed_count.";
    if (!empty($errors)) {
        $summary_message .= "<br>Details:<ul><li>" . implode('</li><li>', $errors) . "</li></ul>";
    }

    return ['success' => ($imported_count > 0 && $failed_count == 0), 'message' => $summary_message, 'errors' => $errors];
}


/**
 * Adds a new bill.
 * @param int $user_id
 * @param string $description
 * @param float $amount
 * @param string $due_date
 * @param string|null $category
 * @param string $recurring_frequency 'daily', 'weekly', 'monthly', 'yearly', or ''
 * @return bool True on success, false on failure.
 */
function add_bill($user_id, $description, $amount, $due_date, $category = null, $recurring_frequency = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO bills (user_id, description, amount, due_date, category, recurring_frequency) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$user_id, $description, $amount, $due_date, $category, $recurring_frequency]);
    } catch (PDOException $e) {
        error_log("Add bill error: " . $e->getMessage());
        return false;
    }
}

/**
 * Updates an existing bill.
 * @param int $bill_id
 * @param int $user_id
 * @param string $description
 * @param float $amount
 * @param string $due_date
 * @param string|null $category
 * @param int $is_paid 0 or 1
 * @param string $recurring_frequency
 * @return bool True on success, false on failure.
 */
function update_bill($bill_id, $user_id, $description, $amount, $due_date, $category, $is_paid, $recurring_frequency) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE bills SET description = ?, amount = ?, due_date = ?, category = ?, is_paid = ?, recurring_frequency = ? WHERE id = ? AND user_id = ?");
        return $stmt->execute([$description, $amount, $due_date, $category, $is_paid, $recurring_frequency, $bill_id, $user_id]);
    } catch (PDOException $e) {
        error_log("Update bill error: " . $e->getMessage());
        return false;
    }
}

/**
 * Marks a bill as paid or unpaid.
 * @param int $bill_id
 * @param int $user_id
 * @param int $is_paid 0 or 1
 * @return bool True on success, false on failure.
 */
function mark_bill_as_paid($bill_id, $user_id, $is_paid) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE bills SET is_paid = ? WHERE id = ? AND user_id = ?");
        return $stmt->execute([$is_paid, $bill_id, $user_id]);
    } catch (PDOException $e) {
        error_log("Mark bill as paid error: " . $e->getMessage());
        return false;
    }
}

/**
 * Deletes a bill.
 * @param int $bill_id
 * @param int $user_id
 * @return bool True on success, false on failure.
 */
function delete_bill($bill_id, $user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM bills WHERE id = ? AND user_id = ?");
        return $stmt->execute([$bill_id, $user_id]);
    } catch (PDOException $e) {
        error_log("Delete bill error: " . $e->getMessage());
        return false;
    }
}

/**
 * Gets a single bill by ID and user ID.
 * @param int $bill_id
 * @param int $user_id
 * @return array|false The bill data or false if not found/not owned by user.
 */
function get_bill_by_id($bill_id, $user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM bills WHERE id = ? AND user_id = ?");
        $stmt->execute([$bill_id, $user_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get bill by ID error: " . $e->getMessage());
        return false;
    }
}

/**
 * Retrieves all bills for a user.
 * @param int $user_id
 * @return array List of bills.
 */
function get_all_bills($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM bills WHERE user_id = ? ORDER BY due_date ASC, is_paid ASC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get all bills error: " . $e->getMessage());
        return [];
    }
}

/**
 * Retrieves upcoming unpaid bills for a user within a specified number of days.
 * @param int $user_id
 * @param int $days_ahead
 * @return array List of upcoming unpaid bills.
 */
function get_upcoming_bills($user_id, $days_ahead = 30) {
    global $pdo;
    $current_date = date('Y-m-d');
    $future_date = date('Y-m-d', strtotime("+$days_ahead days"));

    try {
        $stmt = $pdo->prepare("SELECT * FROM bills WHERE user_id = ? AND is_paid = 0 AND due_date BETWEEN ? AND ? ORDER BY due_date ASC");
        $stmt->execute([$user_id, $current_date, $future_date]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get upcoming bills error: " . $e->getMessage());
        return [];
    }
}


/**
 * Projects next month's financials based on recurring transactions and historical average expenses.
 * @param int $user_id The ID of the user.
 * @param string $month_to_project 'YYYY-MM' format of the month to project.
 * @param int $history_months Number of past months to average for non-recurring expenses.
 * @return array Associative array with projected income and expenses.
 */
function get_projected_summary_for_month($user_id, $month_to_project, $history_months = 3) {
    global $pdo;
    $projected_income = 0;
    $projected_expenses = 0;

    // 1. Project from Recurring Transaction Templates
    try {
        $templates = get_recurring_transaction_templates($user_id);
        $projected_start_date = new DateTime($month_to_project . '-01');
        $projected_end_date = new DateTime($projected_start_date->format('Y-m-t')); // Last day of the month

        foreach ($templates as $template) {
            $template_start_dt = new DateTime($template['start_date']);
            $template_end_dt = $template['end_date'] ? new DateTime($template['end_date']) : null;

            // Only consider templates that are active within the projected month
            if ($template_start_dt <= $projected_end_date && ($template_end_dt === null || $template_end_dt >= $projected_start_date)) {
                $amount = $template['amount'];

                switch ($template['frequency']) {
                    case 'daily':
                        // Count days in the month
                        $interval = new DateInterval('P1D');
                        $period = new DatePeriod(
                            max($template_start_dt, $projected_start_date),
                            $interval,
                            $projected_end_date->modify('+1 day') // Make end date inclusive
                        );
                        $count = 0;
                        foreach ($period as $date) {
                            $count++;
                        }
                        if ($template['type'] === 'income') {
                            $projected_income += $amount * $count;
                        } else {
                            $projected_expenses += $amount * $count;
                        }
                        break;
                    case 'weekly':
                        // Approximate 4 occurrences for monthly (more precise if needed)
                        $weeks_in_month = floor($projected_end_date->diff($projected_start_date)->days / 7);
                        if ($template['type'] === 'income') {
                            $projected_income += $amount * $weeks_in_month;
                        } else {
                            $projected_expenses += $amount * $weeks_in_month;
                        }
                        break;
                    case 'bi-weekly': // Every 2 weeks, approx 2 occurrences per month
                    case 'fortnightly':
                        $bi_weeks_in_month = floor($projected_end_date->diff($projected_start_date)->days / 14);
                         if ($template['type'] === 'income') {
                            $projected_income += $amount * $bi_weeks_in_month;
                        } else {
                            $projected_expenses += $amount * $bi_weeks_in_month;
                        }
                        break;
                    case 'semi-monthly': // Twice a month, approx 2 occurrences
                         if ($template['type'] === 'income') {
                            $projected_income += $amount * 2;
                        } else {
                            $projected_expenses += $amount * 2;
                        }
                        break;
                    case 'monthly':
                        // Always one occurrence per month if active
                        if ($template['type'] === 'income') {
                            $projected_income += $amount;
                        } else {
                            $projected_expenses += $amount;
                        }
                        break;
                    case 'yearly':
                        // Only if the month of the template start date matches the projected month
                        if ($template_start_dt->format('m') === $projected_start_date->format('m')) {
                            if ($template['type'] === 'income') {
                                $projected_income += $amount;
                            } else {
                                $projected_expenses += $amount;
                            }
                        }
                        break;
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Error projecting from recurring templates: " . $e->getMessage());
    }


    // 2. Project non-recurring expenses based on historical average
    // Get historical data for a few months back
    $historical_months_data = [];
    $current_dt = new DateTime(date('Y-m-01'));
    // Make sure we start from the previous month to get history, not current month
    $temp_dt = clone $current_dt; // Create a mutable copy
    for ($i = 0; $i < $history_months; $i++) {
        $historical_month_key = $temp_dt->modify('-1 month')->format('Y-m');
        $historical_summary = get_monthly_summary([$user_id], $historical_month_key); // Only current user's past data
        $historical_category_breakdown = get_category_breakdown([$user_id], $historical_month_key);

        $historical_months_data[] = [
            'totalExpenses' => $historical_summary['totalExpenses'],
            'categoryBreakdown' => $historical_category_breakdown
        ];
    }

    $overall_historical_expenses = 0;
    $category_historical_sums = [];

    foreach ($historical_months_data as $month_data) {
        $overall_historical_expenses += $month_data['totalExpenses'];
        foreach ($month_data['categoryBreakdown'] as $category => $amount) {
            $category_historical_sums[$category] = ($category_historical_sums[$category] ?? 0) + $amount;
        }
    }

    // Average non-recurring expenses
    if ($history_months > 0 && count($historical_months_data) > 0) {
        $average_historical_expenses = $overall_historical_expenses / count($historical_months_data);
        // This is a simplification; a more precise approach would be to calculate
        // non-recurring expenses from historical data directly, but for now,
        // we assume the average accounts for a mix.
        $projected_expenses += $average_historical_expenses;
    }


    return [
        'projectedIncome' => $projected_income,
        'projectedExpenses' => $projected_expenses,
        // More detailed breakdown could be added here
    ];
}

/**
 * Gets user preferences (currency, dark mode).
 * @param int $user_id
 * @return array Associative array with 'currency' and 'dark_mode_enabled'.
 */
function get_user_preferences($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT currency, dark_mode_enabled FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $prefs = $stmt->fetch();
        return [
            'currency' => $prefs['currency'] ?? 'USD',
            'dark_mode_enabled' => (bool)($prefs['dark_mode_enabled'] ?? false)
        ];
    } catch (PDOException $e) {
        error_log("Get user preferences error: " . $e->getMessage());
        return ['currency' => 'USD', 'dark_mode_enabled' => false];
    }
}

/**
 * Updates user preferences.
 * @param int $user_id
 * @param string $currency
 * @param bool $dark_mode_enabled
 * @return bool True on success, false on failure.
 */
function update_user_preferences($user_id, $currency, $dark_mode_enabled) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE users SET currency = ?, dark_mode_enabled = ? WHERE id = ?");
        return $stmt->execute([$currency, (int)$dark_mode_enabled, $user_id]);
    } catch (PDOException $e) {
        error_log("Update user preferences error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get currency symbol based on currency code.
 * @param string $currencyCode
 * @return string
 */
function get_currency_symbol($currencyCode) {
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
        'CAD' => 'C$',
        'AUD' => 'A$',
        'CHF' => 'CHF',
        'CNY' => '¥',
        'INR' => '₹',
        'BRL' => 'R$',
        'RUB' => '₽',
        'ZAR' => 'R',
        // Add more as needed
    ];
    return $symbols[strtoupper($currencyCode)] ?? '$'; // Default to '$'
}

?>