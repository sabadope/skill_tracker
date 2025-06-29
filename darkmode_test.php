<!DOCTYPE html>
<html lang="en" class="transition-colors duration-300">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dark Mode Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <script src="assets/js/darkmode.js"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="fixed top-4 right-4 z-50">
        <button id="darkModeToggle" class="p-3 rounded-lg bg-white dark:bg-gray-800 shadow-lg" title="Toggle dark mode">
            <svg class="sun-icon h-6 w-6 text-yellow-500 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <svg class="moon-icon h-6 w-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
        </button>
    </div>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-8 text-center">Dark Mode Test</h1>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <p class="text-gray-600 dark:text-gray-300">This page tests the dark mode functionality. Click the toggle button in the top right to switch between light and dark modes.</p>
        </div>
    </div>
</body>
</html> 