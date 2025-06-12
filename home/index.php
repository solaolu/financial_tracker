<?php
session_start();
require_once __DIR__ . '/../src/models/Finance.php';
if (!isset($_SESSION['user'])) { header("Location: /login.php"); exit; }
$latest = Finance::getLatestMonth($_SESSION['user']['id']);
$all = Finance::getByUser($_SESSION['user']['id']);
?><link rel="stylesheet" href="/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="container">
<h2>Dashboard</h2>
<p>Welcome, <?= $_SESSION['user']['username'] ?> | <a href="/logout.php">Logout</a></p>
<a href="/finance.php">+ Add Monthly Record</a>
<?php if ($latest): ?>
<h3>Latest: <?= htmlspecialchars($latest['month']) ?></h3>
<canvas id="pieChart" width="400" height="400"></canvas>
<script>
const ctx = document.getElementById('pieChart').getContext('2d');
new Chart(ctx, {
    type: 'pie',
    data: {
        labels: ['Income', 'Expenses'],
        datasets: [{
            data: [<?= $latest['income'] ?>, <?= $latest['expenses'] ?>],
            backgroundColor: ['#4CAF50', '#f44336']
        }]
    }
});
</script>
<?php endif; ?>
<h3>History</h3>
<canvas id="barChart" height="100"></canvas>
<script>
const btx = document.getElementById('barChart').getContext('2d');
new Chart(btx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($all, 'month')) ?>,
        datasets: [
            {
                label: 'Income',
                data: <?= json_encode(array_column($all, 'income')) ?>,
                backgroundColor: '#4CAF50'
            },
            {
                label: 'Expenses',
                data: <?= json_encode(array_column($all, 'expenses')) ?>,
                backgroundColor: '#f44336'
            }
        ]
    }
});
</script>
</div>