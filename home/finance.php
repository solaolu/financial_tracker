<?php require_once __DIR__ . '/../src/controllers/finance.php'; ?>
<link rel="stylesheet" href="/css/style.css">
<div class="container">
<h2>Add Finance Record</h2>
<form method="post">
    <input name="month" type="month" required />
    <input name="income" type="number" step="0.01" placeholder="Income" required />
    <input name="expenses" type="number" step="0.01" placeholder="Expenses" required />
    <label><input type="checkbox" name="is_recurring" /> Recurring</label>
    <button>Save</button>
</form>
<a href="/index.php">← Back to Dashboard</a>
</div>