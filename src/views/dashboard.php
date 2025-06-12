<!DOCTYPE html><html><head><title>dashboard.php</title></head><body><h2>Dashboard</h2><a href="index.php?action=logout">Logout</a>
<h3>Add Finance</h3><form method="post" action="index.php?action=finance">
Month: <input name="month"><br>Budget: <input name="budget"><br>Expenses: <input name="expenses"><br>
<button type="submit">Save</button></form>
<h3>Chart</h3><canvas id="chart"></canvas>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('chart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($data, 'month')); ?>,
        datasets: [{
            label: 'Budget',
            data: <?php echo json_encode(array_column($data, 'budget')); ?>,
            backgroundColor: 'green'
        }, {
            label: 'Expenses',
            data: <?php echo json_encode(array_column($data, 'expenses')); ?>,
            backgroundColor: 'red'
        }]
    }
});
</script></body></html>