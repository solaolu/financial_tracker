<?php
// This file contains the HTML header, including Tailwind CSS CDN and custom styles.
session_start();
// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'functions.php'; // Ensure functions are available for preferences

$user_id = $_SESSION['user_id'];
$user_info = get_user_by_id($user_id); // Fetch user info including currency and dark mode
$user_currency_symbol = get_currency_symbol($user_info['currency'] ?? 'USD');
$is_dark_mode_enabled = $user_info['dark_mode_enabled'] ?? false;

// Apply dark mode class to HTML tag based on user preference
$html_class = $is_dark_mode_enabled ? 'dark' : '';
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $html_class; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Tracker</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6; /* Light gray background */
            color: #1f2937; /* Default text color */
        }
        .dark body {
            background-color: #1a202c; /* Dark mode background */
            color: #e2e8f0; /* Dark mode text color */
        }
        /* Adjusted panel/card backgrounds for light mode */
        .bg-white {
            background-color: #ffffff;
        }
        .dark .bg-white {
            background-color: #2d3748; /* Dark mode card background */
        }
        /* Adjusted text colors for better contrast */
        .text-gray-800 {
            color: #1f2937; /* Darker text for light mode headings/titles */
        }
        .dark .text-gray-800 {
            color: #e2e8f0; /* Light text for dark mode headings */
        }
        .text-gray-700 {
            color: #374151; /* Standard text color in light mode */
        }
        .dark .text-gray-700 {
            color: #cbd5e0; /* Lighter text for dark mode labels/text */
        }
        .text-gray-600 {
            color: #4b5563; /* Lighter text in light mode */
        }
        .dark .text-gray-600 {
            color: #a0aec0; /* Even lighter text in dark mode */
        }
        .border-gray-300 {
            border-color: #d1d5db; /* Light border for light mode */
        }
        .dark .border-gray-300 {
            border-color: #4a5568; /* Darker border for dark mode */
        }
        /* Input, Select, Textarea Styling */
        input, select, textarea {
            background-color: #ffffff; /* Light background for inputs in light mode */
            color: #1f2937; /* Dark text for inputs in light mode */
            border-color: #d1d5db;
        }
        .dark input, .dark select, .dark textarea {
            background-color: #4a5568; /* Dark background for inputs in dark mode */
            color: #e2e8f0; /* Light text for inputs in dark mode */
            border-color: #616e7f;
        }
        .dark input::placeholder, .dark textarea::placeholder {
            color: #cbd5e0;
        }
        /* Table Styling */
        table {
            color: #1f2937;
        }
        .dark table {
            color: #e2e8f0;
        }
        table thead th {
            color: #6b7280;
            background-color: #f9fafb; /* Light header background for light mode */
        }
        .dark table thead th {
            color: #a0aec0;
            background-color: #4a5568; /* Darker header background for dark mode */
        }
        table tbody tr {
            background-color: #ffffff; /* Light row background for light mode */
        }
        .dark table tbody tr {
            background-color: #2d3748; /* Darker row background for dark mode */
        }
        table tbody td {
            border-color: #e5e7eb; /* Light border for table cells */
        }
        .dark table tbody td {
            border-color: #4a5568; /* Darker border for table cells */
        }
        /* Specific background colors (ensure they are light in light mode) */
        .bg-gray-50 { /* Used for table header and some list items */
            background-color: #f9fafb; /* Very light gray */
        }
        .dark .bg-gray-50 {
            background-color: #4a5568;
        }
        .bg-gray-100 { /* For elements like code blocks */
            background-color: #f3f4f6;
        }
        .dark .bg-gray-100 {
            background-color: #4a5568;
            color: #e2e8f0;
        }
        .bg-gray-200 { /* For buttons, etc. */
            background-color: #e5e7eb;
        }
        .dark .bg-gray-200 {
            background-color: #616e7f;
        }
        /* Success/Danger/Info Colors */
        .bg-green-100 {
            background-color: #d1fae5;
            color: #065f46;
        }
        .dark .bg-green-100 {
            background-color: #276749; /* Darker green */
            color: #9ae6b4;
        }
        /* Specific text colors for dark mode context */
        .dark .text-green-600 { color: #81c784; }
        .dark .text-red-600 { color: #ef9a9a; }
        .dark .text-blue-600 { color: #64b5f6; }
        .dark .text-indigo-600 { color: #7986cb; }
        .dark .text-orange-600 { color: #ffb74d; }

        /* Custom scrollbar for better appearance */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #e0e0e0;
            border-radius: 10px;
        }
        .dark ::-webkit-scrollbar-track {
            background: #4a5568;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        .dark ::-webkit-scrollbar-thumb {
            background: #a0aec0;
        }
        .dark ::-webkit-scrollbar-thumb:hover {
            background: #cbd5e0;
        }

        /* Calendar Styling */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
            text-align: center;
        }
        .calendar-day {
            padding: 8px 4px;
            border-radius: 8px;
            background-color: #f8fafc; /* Lighter background for days */
            color: #4b5563;
            font-size: 0.875rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 50px; /* Ensure days have enough height */
        }
        .dark .calendar-day {
            background-color: #4a5568;
            color: #e2e8f0;
        }
        .calendar-day.today {
            background-color: #bfdbfe; /* blue-200 */
            font-weight: 600;
            color: #1e40af; /* blue-800 */
        }
        .dark .calendar-day.today {
            background-color: #3182ce; /* blue-600 */
            color: #e2e8f0;
        }
        .calendar-day.highlighted {
            background-color: #fde68a; /* amber-200 */
            color: #92400e; /* amber-800 */
            font-weight: 600;
            border: 1px solid #d97706; /* amber-600 */
        }
        .dark .calendar-day.highlighted {
            background-color: #d97706; /* amber-600 */
            color: #fef3c7; /* amber-50 */
        }
        .calendar-day.highlighted-paid {
            background-color: #bbf7d0; /* green-200 */
            color: #166534; /* green-800 */
            font-weight: 600;
            border: 1px solid #16a34a; /* green-600 */
            text-decoration: line-through;
        }
        .dark .calendar-day.highlighted-paid {
            background-color: #10b981; /* green-500 */
            color: #ecfdf5; /* green-50 */
            text-decoration: line-through;
        }
        .calendar-day.has-bills .bill-indicator {
            width: 8px;
            height: 8px;
            background-color: #ef4444; /* red-500 */
            border-radius: 50%;
            margin-top: 4px;
        }
        .calendar-day.has-bills.highlighted .bill-indicator {
            background-color: #f97316; /* orange-500 */
        }
        .calendar-day.has-bills.highlighted-paid .bill-indicator {
            background-color: #22c55e; /* green-500 */
        }

        .weekday-label {
            font-weight: 600;
            color: #4b5563;
            padding: 8px 0;
            font-size: 0.875rem;
        }
        .dark .weekday-label {
            color: #cbd5e0;
        }

        /* Dark Mode Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 28px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            -webkit-transition: .4s;
            transition: .4s;
            border-radius: 28px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            -webkit-transition: .4s;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #2196F3;
        }

        .dark input:checked + .slider {
             background-color: #64b5f6; /* Lighter blue for dark mode */
        }

        input:focus + .slider {
            box-shadow: 0 0 1px #2196F3;
        }

        input:checked + .slider:before {
            -webkit-transform: translateX(22px);
            -ms-transform: translateX(22px);
            transform: translateX(22px);
        }

        /* Round sliders */
        .slider.round {
            border-radius: 28px;
        }

        .slider.round:before {
            border-radius: 50%;
        }

        /* Hamburger Menu Specific Styles */
        .mobile-nav-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 40; /* Above regular content, below mobile menu */
            display: none; /* Hidden by default */
        }
        .mobile-nav {
            position: fixed;
            top: 0;
            right: -250px; /* Hidden initially */
            width: 250px;
            height: 100%;
            background-color: #2d3748; /* Dark background for mobile menu */
            color: #e2e8f0;
            box-shadow: -2px 0 5px rgba(0,0,0,0.5);
            transition: right 0.3s ease-in-out;
            z-index: 50; /* Above overlay */
            padding-top: 60px; /* Space for close button */
            display: flex;
            flex-direction: column;
        }
        .mobile-nav.open {
            right: 0; /* Slide in */
        }
        .mobile-nav a {
            padding: 15px 20px;
            border-bottom: 1px solid #4a5568;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            color: #e2e8f0;
            transition: background-color 0.2s ease;
        }
        .mobile-nav a:hover {
            background-color: #4a5568;
        }
        .mobile-nav a .fa-solid {
            margin-right: 10px;
            width: 20px; /* Fixed width for icon alignment */
            text-align: center;
        }
        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 1.8em;
            color: #e2e8f0;
            cursor: pointer;
        }

        /* Responsive Navbar adjustments */
        @media (max-width: 768px) {
            nav {
                display: none; /* Hide regular navbar on mobile */
            }
            .hamburger-button {
                display: block; /* Show hamburger button on mobile */
            }
        }
        @media (min-width: 769px) {
            nav {
                display: flex !important; /* Always show regular navbar on desktop */
            }
            .hamburger-button, .mobile-nav-overlay, .mobile-nav {
                display: none; /* Hide mobile elements on desktop */
            }
        }
    </style>
    <script>
        // Store user preferences in a global JS object for easier access
        window.userPreferences = {
            currencySymbol: "<?php echo $user_currency_symbol; ?>",
            isDarkModeEnabled: <?php echo $is_dark_mode_enabled ? 'true' : 'false'; ?>
        };

        // Function to apply dark/light mode based on preference
        function applyTheme() {
            if (window.userPreferences.isDarkModeEnabled) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }

        // Apply theme immediately on script load
        applyTheme();

        // Listen for changes from settings page (if any) to dynamically update
        window.addEventListener('storage', (event) => {
            if (event.key === 'userPreferences') {
                window.userPreferences = JSON.parse(event.newValue);
                applyTheme();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const hamburgerBtn = document.getElementById('hamburgerBtn');
            const mobileNav = document.getElementById('mobileNav');
            const mobileNavOverlay = document.getElementById('mobileNavOverlay');
            const closeBtn = document.getElementById('closeBtn');
            const darkModeToggle = document.getElementById('darkModeToggle');

            function toggleMobileMenu() {
                mobileNav.classList.toggle('open');
                mobileNavOverlay.style.display = mobileNav.classList.contains('open') ? 'block' : 'none';
            }

            hamburgerBtn.addEventListener('click', toggleMobileMenu);
            closeBtn.addEventListener('click', toggleMobileMenu);
            mobileNavOverlay.addEventListener('click', toggleMobileMenu); // Close when clicking outside

            // Dark Mode Toggle Logic
            if (darkModeToggle) {
                darkModeToggle.checked = window.userPreferences.isDarkModeEnabled; // Set initial state
                darkModeToggle.addEventListener('change', async function() {
                    const newDarkModeState = this.checked;
                    // Optimistically update UI
                    window.userPreferences.isDarkModeEnabled = newDarkModeState;
                    applyTheme();

                    // Send preference to server to save
                    try {
                        const formData = new FormData();
                        formData.append('update_preferences_from_header', '1');
                        formData.append('dark_mode', newDarkModeState ? '1' : '0');
                        formData.append('currency', '<?php echo $user_currency_symbol; ?>'); // Keep current currency

                        const response = await fetch('settings.php', {
                            method: 'POST',
                            body: formData
                        });
                        if (!response.ok) {
                            console.error('Failed to save dark mode preference to server.');
                            // Revert UI if server update fails
                            window.userPreferences.isDarkModeEnabled = !newDarkModeState;
                            applyTheme();
                            this.checked = !newDarkModeState;
                        }
                         // Update localStorage after successful server update
                        localStorage.setItem('userPreferences', JSON.stringify(window.userPreferences));
                    } catch (error) {
                        console.error('Error saving dark mode preference:', error);
                        // Revert UI on network error
                        window.userPreferences.isDarkModeEnabled = !newDarkModeState;
                        applyTheme();
                        this.checked = !newDarkModeState;
                    }
                });
            }
        });
    </script>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Financial Tracker</h1>
            <div class="flex items-center space-x-4">
                <!-- Dark Mode Toggle -->
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-sun text-yellow-300"></i>
                    <label class="toggle-switch">
                        <input type="checkbox" id="darkModeToggle">
                        <span class="slider round"></span>
                    </label>
                    <i class="fa-solid fa-moon text-blue-900"></i>
                </div>

                <!-- Hamburger menu button for mobile -->
                <button id="hamburgerBtn" class="hamburger-button text-white text-2xl focus:outline-none hidden">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <nav class="hidden md:flex space-x-4">
                    <a href="dashboard.php" class="text-white hover:text-blue-200 px-3 py-2 rounded-md transition duration-300 flex items-center">
                        <i class="fa-solid fa-chart-line mr-2"></i> Dashboard
                    </a>
                    <a href="transactions.php" class="text-white hover:text-blue-200 px-3 py-2 rounded-md transition duration-300 flex items-center">
                        <i class="fa-solid fa-right-left mr-2"></i> Transactions
                    </a>
                    <a href="bills.php" class="text-white hover:text-blue-200 px-3 py-2 rounded-md transition duration-300 flex items-center">
                        <i class="fa-solid fa-file-invoice-dollar mr-2"></i> Bills
                    </a>
                    <a href="import_data.php" class="text-white hover:text-blue-200 px-3 py-2 rounded-md transition duration-300 flex items-center">
                        <i class="fa-solid fa-upload mr-2"></i> Import Data
                    </a>
                    <a href="settings.php" class="text-white hover:text-blue-200 px-3 py-2 rounded-md transition duration-300 flex items-center">
                        <i class="fa-solid fa-gear mr-2"></i> Settings
                    </a>
                    <a href="logout.php" class="text-white hover:text-blue-200 px-3 py-2 rounded-md transition duration-300 flex items-center">
                        <i class="fa-solid fa-right-from-bracket mr-2"></i> Logout
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Mobile Navigation Overlay -->
    <div id="mobileNavOverlay" class="mobile-nav-overlay"></div>

    <!-- Mobile Navigation Menu -->
    <div id="mobileNav" class="mobile-nav">
        <button id="closeBtn" class="close-btn">&times;</button>
        <a href="dashboard.php" class="flex items-center"><i class="fa-solid fa-chart-line mr-2"></i> Dashboard</a>
        <a href="transactions.php" class="flex items-center"><i class="fa-solid fa-right-left mr-2"></i> Transactions</a>
        <a href="bills.php" class="flex items-center"><i class="fa-solid fa-file-invoice-dollar mr-2"></i> Bills</a>
        <a href="import_data.php" class="flex items-center"><i class="fa-solid fa-upload mr-2"></i> Import Data</a>
        <a href="settings.php" class="flex items-center"><i class="fa-solid fa-gear mr-2"></i> Settings</a>
        <a href="logout.php" class="flex items-center"><i class="fa-solid fa-right-from-bracket mr-2"></i> Logout</a>
    </div>

    <!-- Main Content will go here (in respective pages) -->
    <main class="flex-grow container mx-auto p-6">