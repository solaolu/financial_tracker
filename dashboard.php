<?php
// The main dashboard page with financial overview, charts, upcoming bills, and projections.

require_once 'header.php'; // Includes session start and basic HTML head/header
require_once 'functions.php'; // Includes all helper functions

$user_id = $_SESSION['user_id'];
$user_info = get_user_by_id($user_id); // Fetch user info to get the name
$user_name = htmlspecialchars($user_info['name'] ?? 'User');
$user_currency_symbol = get_currency_symbol($user_info['currency'] ?? 'USD');

$accessible_user_ids = get_accessible_user_ids($user_id);
$upcoming_bills = get_upcoming_bills($user_id);

// Get next month for projection
$next_month = date('Y-m', strtotime('+1 month'));
$projected_summary = get_projected_summary_for_month($user_id, $next_month);

// Data for calendar (all bills, not just upcoming)
$all_bills_for_calendar = get_all_bills($user_id); // Fetch all bills for calendar highlighting

?>

    <h2 class="text-3xl font-semibold text-gray-800 dark:text-white mb-6 flex items-center"><i class="fa-solid fa-chart-pie mr-3"></i> Welcome, <?php echo $user_name; ?>!</h2>

    <!-- Month Selection & Controls -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg mb-8">
        <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0 md:space-x-4">
            <div class="flex items-center space-x-2">
                <label for="currentMonthSelect" class="block text-gray-700 dark:text-gray-300 text-sm font-medium">View Month:</label>
                <select id="currentMonthSelect" class="p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <!-- Options will be populated by JavaScript -->
                </select>
            </div>
            <div class="flex items-center space-x-2">
                <label for="compareMonthSelect" class="block text-gray-700 dark:text-gray-300 text-sm font-medium">Compare With:</label>
                <select id="compareMonthSelect" class="p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">No Comparison</option>
                    <!-- Options will be populated by JavaScript -->
                </select>
            </div>
            <button id="applyFiltersBtn" class="px-5 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-300 shadow-md flex items-center">
                <i class="fa-solid fa-filter mr-2"></i> Apply Filters
            </button>
            <a id="downloadReportBtn" href="#" target="_blank" class="px-5 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition duration-300 shadow-md flex items-center">
                <i class="fa-solid fa-download mr-2"></i> Download Report
            </a>
        </div>
    </div>

    <!-- Main Content Area - Two Columns for Dashboard elements -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Summary Cards, Charts, Projected Financials -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg flex flex-col items-center justify-center text-center">
                    <span class="text-4xl text-green-600 dark:text-green-400 mb-2"><?php echo $user_currency_symbol; ?><span id="totalIncome">0.00</span></span>
                    <p class="text-gray-600 dark:text-gray-300 text-lg font-medium">Total Income</p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg flex flex-col items-center justify-center text-center">
                    <span class="text-4xl text-red-600 dark:text-red-400 mb-2"><?php echo $user_currency_symbol; ?><span id="totalExpenses">0.00</span></span>
                    <p class="text-gray-600 dark:text-gray-300 text-lg font-medium">Total Expenses</p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg flex flex-col items-center justify-center text-center">
                    <span class="text-4xl text-blue-600 dark:text-blue-400 mb-2"><?php echo $user_currency_symbol; ?><span id="netSavings">0.00</span></span>
                    <p class="text-gray-600 dark:text-gray-300 text-lg font-medium">Net Savings</p>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Income vs Expense Chart -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
                    <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-4 flex items-center"><i class="fa-solid fa-chart-column mr-2"></i> Income vs. Expenses</h3>
                    <div class="h-64"> <!-- Fixed height for chart -->
                        <canvas id="incomeExpenseChart"></canvas>
                    </div>
                </div>

                <!-- Category Spending Chart -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
                    <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-4 flex items-center"><i class="fa-solid fa-chart-pie mr-2"></i> Spending by Category</h3>
                    <div class="h-64"> <!-- Fixed height for chart -->
                        <canvas id="categorySpendingChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Projected Financials Section (Moved here from side panel) -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
                <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-4 flex items-center"><i class="fa-solid fa-calculator mr-2"></i> Projected Financials for <?php echo date('F Y', strtotime($next_month)); ?></h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex flex-col items-center justify-center text-center p-4 bg-green-50 rounded-lg shadow-sm dark:bg-green-700/30">
                        <p class="text-2xl font-bold text-green-700 dark:text-green-300"><?php echo $user_currency_symbol . number_format($projected_summary['projectedIncome'], 2); ?></p>
                        <p class="text-gray-600 text-md dark:text-gray-400">Projected Income</p>
                    </div>
                    <div class="flex flex-col items-center justify-center text-center p-4 bg-red-50 rounded-lg shadow-sm dark:bg-red-700/30">
                        <p class="text-2xl font-bold text-red-700 dark:text-red-300"><?php echo $user_currency_symbol . number_format($projected_summary['projectedExpenses'], 2); ?></p>
                        <p class="text-gray-600 text-md dark:text-gray-400">Projected Expenses</p>
                    </div>
                    <div class="flex flex-col items-center justify-center text-center p-4 bg-blue-50 rounded-lg shadow-sm dark:bg-blue-700/30">
                        <?php
                            $projected_net = $projected_summary['projectedIncome'] - $projected_summary['projectedExpenses'];
                            $color_class = ($projected_net >= 0) ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300';
                        ?>
                        <p class="text-3xl font-bold <?php echo $color_class; ?>">
                            <?php echo $user_currency_symbol . number_format($projected_net, 2); ?>
                        </p>
                        <p class="text-gray-600 text-lg dark:text-gray-400">Projected Net Balance</p>
                        <?php if ($projected_net < 0): ?>
                            <p class="text-sm text-orange-600 mt-2 dark:text-orange-300">Heads up! Your projected expenses exceed your income next month.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    *Projections are based on your recurring templates and average expenses from the last 3 months.
                </p>
            </div>
        </div>

        <!-- Right Column: Side Panel (Calendar, Upcoming Bills, Spending Advice) -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Calendar Section -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
                <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-4 flex items-center"><i class="fa-solid fa-calendar-days mr-2"></i> Bills Calendar</h3>
                <div class="flex justify-between items-center mb-4">
                    <button id="prevMonthBtn" class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500">Prev</button>
                    <span id="calendarMonthYear" class="font-semibold text-lg text-gray-800 dark:text-white"></span>
                    <button id="nextMonthBtn" class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500">Next</button>
                </div>
                <div class="calendar-grid mb-2">
                    <div class="weekday-label">Sun</div>
                    <div class="weekday-label">Mon</div>
                    <div class="weekday-label">Tue</div>
                    <div class="weekday-label">Wed</div>
                    <div class="weekday-label">Thu</div>
                    <div class="weekday-label">Fri</div>
                    <div class="weekday-label">Sat</div>
                </div>
                <div id="calendarDays" class="calendar-grid">
                    <!-- Calendar days will be rendered here by JS -->
                </div>
            </div>

            <!-- Upcoming Bills Section -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
                <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-4 flex items-center"><i class="fa-solid fa-hourglass-half mr-2"></i> Upcoming Bills (< 30 Days)</h3>
                <?php if (empty($upcoming_bills)): ?>
                    <p class="text-gray-600 dark:text-gray-400">No upcoming bills in the next 30 days. You're all clear!</p>
                <?php else: ?>
                    <ul class="space-y-3 max-h-60 overflow-y-auto custom-scrollbar">
                        <?php foreach ($upcoming_bills as $bill): ?>
                            <li class="flex items-center justify-between p-3 bg-gray-50 rounded-md shadow-sm dark:bg-gray-700">
                                <div>
                                    <p class="font-medium text-gray-800 dark:text-white"><?php echo htmlspecialchars($bill['description']); ?></p>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">Due: <?php echo htmlspecialchars($bill['due_date']); ?> &bull; Amount: <?php echo $user_currency_symbol . number_format($bill['amount'], 2); ?></p>
                                </div>
                                <a href="bills.php?action=edit&id=<?php echo htmlspecialchars($bill['id']); ?>" class="text-indigo-600 hover:underline text-sm dark:text-indigo-400">Manage</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <p class="mt-4 text-sm text-gray-600 text-right dark:text-gray-400">
                    <a href="bills.php" class="text-blue-600 hover:underline dark:text-blue-400">View All Bills</a>
                </p>
            </div>

            <!-- Spending Advice Section -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
                <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-4 flex items-center"><i class="fa-solid fa-lightbulb mr-2"></i> Spending Insights & Advice</h3>
                <div id="spendingAdvice" class="text-gray-700 dark:text-gray-300 leading-relaxed">
                    <p>Select months to compare and get personalized spending advice!</p>
                </div>
            </div>
        </div>
    </div>


<?php require_once 'footer.php'; // Includes basic HTML footer ?>

<!-- Chart.js CDN for interactive charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const currentUserId = <?php echo $_SESSION['user_id'] ?? 'null'; ?>; // Pass PHP session user_id to JS
    const userCurrencySymbol = window.userPreferences.currencySymbol;
    console.log("Dashboard loaded. currentUserId:", currentUserId, "Currency:", userCurrencySymbol);

    // --- Chart Instances ---
    let incomeExpenseChartInstance;
    let categorySpendingChartInstance;

    // --- Calendar Variables ---
    let currentCalendarDate = new Date(); // Tracks the month shown in the calendar

    // --- Bill Data for Calendar ---
    const allBills = <?php echo json_encode($all_bills_for_calendar); ?>;
    const billDates = allBills.map(bill => ({
        date: bill.due_date,
        paid: bill.is_paid == 1
    }));
    console.log("Bill dates for calendar:", billDates);

    // --- Helper Functions ---

    // Function to generate month options for select dropdowns
    function populateMonthSelects() {
        console.log("populateMonthSelects called.");
        const currentMonthSelect = document.getElementById('currentMonthSelect');
        const compareMonthSelect = document.getElementById('compareMonthSelect');

        const today = new Date();
        const currentYear = today.getFullYear();
        const currentMonth = today.getMonth(); // 0-indexed

        // Clear existing options
        currentMonthSelect.innerHTML = '';
        compareMonthSelect.innerHTML = '<option value="">No Comparison</option>';


        for (let i = 0; i < 24; i++) { // Generate options for the last 24 months
            const date = new Date(currentYear, currentMonth - i, 1);
            const value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
            const text = date.toLocaleString('en-US', { month: 'long', year: 'numeric' });

            const optionCurrent = new Option(text, value);
            const optionCompare = new Option(text, value);

            currentMonthSelect.add(optionCurrent);
            compareMonthSelect.add(optionCompare);

            // Set current month as default selected for currentMonthSelect
            if (i === 0) {
                currentMonthSelect.value = value;
                console.log("Default current month set to:", value);
            }
        }
        console.log("Month selects populated.");
    }

    // Function to fetch data via AJAX from PHP API endpoints
    async function fetchFinancialData(monthKey) {
        console.log("Attempting to fetch data for month:", monthKey, "for user_id:", currentUserId);
        if (!currentUserId) {
            console.error("User not authenticated. Cannot fetch data.");
            return { totalIncome: 0, totalExpenses: 0, incomeBreakdown: {}, expenseBreakdown: {} };
        }
        try {
            const response = await fetch(`api/get_monthly_data.php?month=${monthKey}&user_id=${currentUserId}`);
            console.log("API Response status:", response.status);
            if (!response.ok) {
                const errorText = await response.text();
                console.error(`HTTP error! status: ${response.status}, text: ${errorText}`);
                throw new Error(`HTTP error! status: ${response.status} - ${errorText}`);
            }
            const data = await response.json();
            console.log("Fetched data (dashboard):", data);
            if (data.error) {
                console.error("API Error (dashboard):", data.error);
                return { totalIncome: 0, totalExpenses: 0, incomeBreakdown: {}, expenseBreakdown: {} };
            }
            return {
                totalIncome: parseFloat(data.total_income || 0),
                totalExpenses: parseFloat(data.total_expenses || 0),
                expenseBreakdown: data.category_breakdown || {}
            };
        } catch (error) {
            console.error("Fetch error (dashboard):", error);
            // Show user a message or fallback data on UI
            return { totalIncome: 0, totalExpenses: 0, incomeBreakdown: {}, expenseBreakdown: {} };
        }
    }

    // Function to update summary cards
    function updateSummaryCards(data) {
        console.log("Updating summary cards with data:", data);
        // Using toLocaleString for currency formatting in JS
        document.getElementById('totalIncome').textContent = data.totalIncome.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('totalExpenses').textContent = data.totalExpenses.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const netSavingsValue = data.totalIncome - data.totalExpenses;
        document.getElementById('netSavings').textContent = netSavingsValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // Apply color based on net savings
        const netSavingsElement = document.getElementById('netSavings').parentElement; // Get the parent div for text-color classes
        // Clear existing classes
        netSavingsElement.classList.remove('text-green-600', 'text-red-600', 'text-blue-600', 'dark:text-green-400', 'dark:text-red-400', 'dark:text-blue-400');
        if (netSavingsValue > 0) {
            netSavingsElement.classList.add('text-green-600');
        } else if (netSavingsValue < 0) {
            netSavingsElement.classList.add('text-red-600');
        } else {
            netSavingsElement.classList.add('text-blue-600');
        }
        // Re-apply dark mode specific classes
        if (window.userPreferences.isDarkModeEnabled) {
            if (netSavingsValue > 0) {
                netSavingsElement.classList.add('dark:text-green-400');
            } else if (netSavingsValue < 0) {
                netSavingsElement.classList.add('dark:text-red-400');
            } else {
                netSavingsElement.classList.add('dark:text-blue-400');
            }
        }
    }

    // Function to render Income vs Expense Bar Chart
    function renderIncomeExpenseChart(currentMonthData, compareMonthData = null) {
        console.log("Rendering Income vs Expense Chart.");
        const ctx = document.getElementById('incomeExpenseChart').getContext('2d');

        if (incomeExpenseChartInstance) {
            incomeExpenseChartInstance.destroy();
        }

        const currentMonthLabel = document.getElementById('currentMonthSelect').options[document.getElementById('currentMonthSelect').selectedIndex].text;
        const compareMonthLabel = compareMonthData ? document.getElementById('compareMonthSelect').options[document.getElementById('compareMonthSelect').selectedIndex].text : '';

        const labels = compareMonthData ? [currentMonthLabel, compareMonthLabel] : [currentMonthLabel];

        const datasets = [
            {
                label: 'Income',
                data: [currentMonthData.totalIncome, ...(compareMonthData ? [compareMonthData.totalIncome] : [])],
                backgroundColor: 'rgba(75, 192, 192, 0.7)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            },
            {
                label: 'Expenses',
                data: [currentMonthData.totalExpenses, ...(compareMonthData ? [compareMonthData.totalExpenses] : [])],
                backgroundColor: 'rgba(255, 99, 132, 0.7)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }
        ];

        incomeExpenseChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                return userCurrencySymbol + value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${userCurrencySymbol}${context.parsed.y.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                            }
                        }
                    }
                }
            }
        });
    }

        // Function to render Category Spending Pie Chart
        function renderCategorySpendingChart(data) {
            console.log("Rendering Category Spending Chart.");
            const ctx = document.getElementById('categorySpendingChart').getContext('2d');

            if (categorySpendingChartInstance) {
                categorySpendingChartInstance.destroy();
            }

            const categories = Object.keys(data.expenseBreakdown);
            const amounts = Object.values(data.expenseBreakdown);
            const backgroundColors = [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#F7464A', '#46BFBD', '#FDB45C', '#949FB1'
            ]; // More distinct colors
            const borderColors = backgroundColors.map(color => color.replace('0.7', '1'));


            categorySpendingChartInstance = new Chart(ctx, {
                type: 'doughnut', // Doughnut chart for better aesthetic
                data: {
                    labels: categories,
                    datasets: [{
                        data: amounts,
                        backgroundColor: backgroundColors.slice(0, categories.length),
                        borderColor: borderColors.slice(0, categories.length),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return `${label}: ${userCurrencySymbol}${value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Function to generate spending advice
        function generateSpendingAdvice(currentMonthData, compareMonthData, currentMonthLabel, compareMonthLabel) {
            console.log("Generating spending advice.");
            const adviceDiv = document.getElementById('spendingAdvice');
            adviceDiv.innerHTML = ''; // Clear previous advice

            if (!compareMonthData || (currentMonthData.totalExpenses === 0 && compareMonthData.totalExpenses === 0 && currentMonthData.totalIncome === 0 && compareMonthData.totalIncome === 0)) {
                adviceDiv.innerHTML = `<p class="text-gray-600 dark:text-gray-400">Select a comparison month to get personalized spending advice, or add more transactions to see insights!</p>`;
                return;
            }

            const currentExpenses = currentMonthData.totalExpenses;
            const compareExpenses = compareMonthData.totalExpenses;
            const currentIncome = currentMonthData.totalIncome;
            const compareIncome = compareMonthData.totalIncome;


            let adviceMessages = [];

            // Overall Spending Advice
            if (currentExpenses > compareExpenses) {
                const diff = currentExpenses - compareExpenses;
                const percentage = compareExpenses > 0 ? (diff / compareExpenses * 100).toFixed(1) : 'N/A';
                adviceMessages.push(`<p><span class="font-semibold text-red-600 dark:text-red-300">Heads Up:</span> Your total expenses increased by <span class="font-bold text-red-600 dark:text-red-300">${userCurrencySymbol}${diff.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span> (${percentage}%) compared to ${compareMonthLabel}. Consider reviewing your budget.</p>`);
            } else if (currentExpenses < compareExpenses) {
                const diff = compareExpenses - currentExpenses;
                const percentage = compareExpenses > 0 ? (diff / compareExpenses * 100).toFixed(1) : 'N/A';
                adviceMessages.push(`<p><span class="font-semibold text-green-600 dark:text-green-300">Great Job!</span> Your total expenses decreased by <span class="font-bold text-green-600 dark:text-green-300">${userCurrencySymbol}${diff.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span> (${percentage}%) compared to ${compareMonthLabel}.</p>`);
            } else {
                adviceMessages.push(`<p><span class="font-semibold text-blue-600 dark:text-blue-300">Consistent Spending:</span> Your spending this month is consistent with ${compareMonthLabel}.</p>`);
            }

            // Income Advice
            if (currentIncome > compareIncome) {
                const diff = currentIncome - compareIncome;
                const percentage = compareIncome > 0 ? (diff / currentIncome * 100).toFixed(1) : 'N/A'; // Corrected percentage calculation for income
                adviceMessages.push(`<p><span class="font-semibold text-green-600 dark:text-green-300">Income Boost:</span> Your income increased by <span class="font-bold text-green-600 dark:text-green-300">${userCurrencySymbol}${diff.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span> (${percentage}%) compared to ${compareMonthLabel}.</p>`);
            } else if (currentIncome < compareIncome) {
                const diff = compareIncome - currentIncome;
                const percentage = compareIncome > 0 ? (diff / compareIncome * 100).toFixed(1) : 'N/A';
                adviceMessages.push(`<p><span class="font-semibold text-orange-600 dark:text-orange-300">Income Dip:</span> Your income decreased by <span class="font-bold text-orange-600 dark:text-orange-300">${userCurrencySymbol}${diff.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span> (${percentage}%) compared to ${compareMonthLabel}.</p>`);
            }

            // Category-specific advice (compare top categories)
            const allCategories = new Set([...Object.keys(currentMonthData.expenseBreakdown), ...Object.keys(compareMonthData.expenseBreakdown)]);

            allCategories.forEach(category => {
                const currentAmount = currentMonthData.expenseBreakdown[category] || 0;
                const compareAmount = compareMonthData.expenseBreakdown[category] || 0;

                if (compareAmount > 0 && currentAmount > compareAmount * 1.20) { // If category spending increased by more than 20%
                    const diff = currentAmount - compareAmount;
                    const percentage = (diff / compareAmount * 100).toFixed(1);
                    adviceMessages.push(`<p><span class="font-semibold text-red-600 dark:text-red-300">Alert for ${category}:</span> Your spending on '${category}' increased by <span class="font-bold text-red-600 dark:text-red-300">${userCurrencySymbol}${diff.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span> (${percentage}%) compared to ${compareMonthLabel}. This is a significant rise.</p>`);
                } else if (compareAmount > 0 && currentAmount < compareAmount * 0.80) { // If category spending decreased by more than 20%
                    const diff = compareAmount - currentAmount;
                    const percentage = (diff / compareAmount * 100).toFixed(1);
                    adviceMessages.push(`<p><span class="font-semibold text-green-600 dark:text-green-300">Good Job on ${category}:</span> You reduced spending on '${category}' by <span class="font-bold text-green-600 dark:text-green-300">${userCurrencySymbol}${diff.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span> (${percentage}%) compared to ${compareMonthLabel}.</p>`);
                } else if (compareAmount === 0 && currentAmount > 0) {
                    adviceMessages.push(`<p><span class="font-semibold text-orange-600 dark:text-orange-300">New Category Spend:</span> You started spending on '${category}' this month: <span class="font-bold text-orange-600 dark:text-orange-300">${userCurrencySymbol}${currentAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>.</p>`);
                }
            });


            if (adviceMessages.length === 0) {
                adviceMessages.push('<p class="text-gray-600 dark:text-gray-400">No specific advice available for the selected months. Keep tracking your finances!</p>');
            }

            adviceDiv.innerHTML = adviceMessages.join('');
        }


        // --- Calendar Functions ---
        function renderCalendar() {
            const calendarMonthYearSpan = document.getElementById('calendarMonthYear');
            const calendarDaysDiv = document.getElementById('calendarDays');

            calendarDaysDiv.innerHTML = ''; // Clear previous days

            const year = currentCalendarDate.getFullYear();
            const month = currentCalendarDate.getMonth(); // 0-indexed

            calendarMonthYearSpan.textContent = currentCalendarDate.toLocaleString('en-US', { month: 'long', year: 'numeric' });

            // Get the first day of the month
            const firstDayOfMonth = new Date(year, month, 1);
            // Get the day of the week for the first day (0 = Sunday, 6 = Saturday)
            const startingDay = firstDayOfMonth.getDay();
            // Get the number of days in the month
            const daysInMonth = new Date(year, month + 1, 0).getDate();

            // Get today's date for highlighting
            const today = new Date();
            const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

            // Add empty divs for the days before the 1st of the month
            for (let i = 0; i < startingDay; i++) {
                const emptyDayDiv = document.createElement('div');
                emptyDayDiv.classList.add('calendar-day');
                calendarDaysDiv.appendChild(emptyDayDiv);
            }

            // Add days of the month
            for (let day = 1; day <= daysInMonth; day++) {
                const dayDiv = document.createElement('div');
                dayDiv.classList.add('calendar-day');
                dayDiv.textContent = day;

                const currentDayStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

                if (currentDayStr === todayStr) {
                    dayDiv.classList.add('today');
                }

                // Check for bills on this date
                const billsOnThisDay = billDates.filter(bill => bill.date === currentDayStr);
                if (billsOnThisDay.length > 0) {
                    dayDiv.classList.add('has-bills');
                    // Add an indicator
                    const indicator = document.createElement('div');
                    indicator.classList.add('bill-indicator');
                    dayDiv.appendChild(indicator);

                    // Check if any bill on this day is unpaid
                    const anyUnpaid = billsOnThisDay.some(bill => !bill.paid);
                    if (anyUnpaid) {
                        dayDiv.classList.add('highlighted'); // Use highlighted for outstanding bills
                    } else {
                        dayDiv.classList.add('highlighted-paid'); // Use for all bills paid
                    }
                }

                calendarDaysDiv.appendChild(dayDiv);
            }
        }

        document.getElementById('prevMonthBtn').addEventListener('click', () => {
            currentCalendarDate.setMonth(currentCalendarDate.getMonth() - 1);
            renderCalendar();
        });

        document.getElementById('nextMonthBtn').addEventListener('click', () => {
            currentCalendarDate.setMonth(currentCalendarDate.getMonth() + 1);
            renderCalendar();
        });


        // --- Main Data Loading and Rendering Function ---
        async function loadDashboardData() {
            console.log("loadDashboardData called.");
            const currentMonthKey = document.getElementById('currentMonthSelect').value;
            const compareMonthKey = document.getElementById('compareMonthSelect').value;

            // Fetch data for current month
            const currentMonthData = await fetchFinancialData(currentMonthKey);
            updateSummaryCards(currentMonthData);
            renderCategorySpendingChart(currentMonthData);

            let compareMonthData = null;
            if (compareMonthKey && compareMonthKey !== currentMonthKey) { // Ensure not comparing to self
                compareMonthData = await fetchFinancialData(compareMonthKey);
            }
            renderIncomeExpenseChart(currentMonthData, compareMonthData);

            const currentMonthLabel = document.getElementById('currentMonthSelect').options[document.getElementById('currentMonthSelect').selectedIndex].text;
            const compareMonthLabel = compareMonthKey && compareMonthKey !== currentMonthKey ? document.getElementById('compareMonthSelect').options[document.getElementById('compareMonthSelect').selectedIndex].text : null;
            generateSpendingAdvice(currentMonthData, compareMonthData, currentMonthLabel, compareMonthLabel);

            // Update download report button link
            const downloadReportBtn = document.getElementById('downloadReportBtn');
            downloadReportBtn.href = `api/generate_report.php?month=${currentMonthKey}&user_id=${currentUserId}`;
        }

        // --- Event Listeners ---
        document.addEventListener('DOMContentLoaded', () => {
            populateMonthSelects();
            renderCalendar(); // Initial calendar render
            document.getElementById('applyFiltersBtn').addEventListener('click', loadDashboardData);
            loadDashboardData(); // Initial load of dashboard data
        });
</script>