function toggleTheme() {
    const darkStyle = document.getElementById('dark-style');
    const isDarkMode = darkStyle.disabled;

    // Toggle dark mode
    darkStyle.disabled = !isDarkMode;

    // Save theme preference in localStorage
    if (isDarkMode) {
        localStorage.setItem('theme', 'dark');
    } else {
        localStorage.setItem('theme', 'light');
    }
}

// Check for saved theme preference
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.getElementById('dark-style').disabled = false;
    }
});

