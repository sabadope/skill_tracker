// Dark mode functionality
document.addEventListener('DOMContentLoaded', function() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const darkModeToggleMobile = document.getElementById('darkModeToggleMobile');
    const html = document.documentElement;
    
    // Check for saved dark mode preference or default to light mode
    const darkMode = localStorage.getItem('darkMode') === 'true';
    
    // Apply dark mode on page load
    if (darkMode) {
        html.classList.add('dark');
        updateToggleIcons(true);
    }
    
    // Toggle dark mode for desktop
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            const isDark = html.classList.toggle('dark');
            localStorage.setItem('darkMode', isDark);
            updateToggleIcons(isDark);
        });
    }
    
    // Toggle dark mode for mobile
    if (darkModeToggleMobile) {
        darkModeToggleMobile.addEventListener('click', function() {
            const isDark = html.classList.toggle('dark');
            localStorage.setItem('darkMode', isDark);
            updateToggleIcons(isDark);
        });
    }
    
    function updateToggleIcons(isDark) {
        const sunIcons = document.querySelectorAll('.sun-icon');
        const moonIcons = document.querySelectorAll('.moon-icon');
        
        sunIcons.forEach(icon => {
            if (isDark) {
                icon.classList.remove('hidden');
            } else {
                icon.classList.add('hidden');
            }
        });
        
        moonIcons.forEach(icon => {
            if (isDark) {
                icon.classList.add('hidden');
            } else {
                icon.classList.remove('hidden');
            }
        });
    }
}); 