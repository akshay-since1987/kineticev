/**
 * Admin logout helper script
 * Ensures the logout functionality works correctly even if there are JavaScript errors
 */
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Initializing logout fix');
        
        // Find all logout links
        const logoutLinks = document.querySelectorAll('a[href="logout"], a[href="logout.php"]');
        
        logoutLinks.forEach(link => {
            console.log('Found logout link:', link.href);
            
            // Add a direct click handler with high priority
            link.addEventListener('click', function(e) {
                console.log('Logout link clicked');
                
                // Prevent any potential issues with other event handlers
                e.stopPropagation();
                
                // Clear any cached session data
                try {
                    if (window.sessionStorage) {
                        sessionStorage.clear();
                    }
                    if (window.localStorage) {
                        // Clear only admin-related localStorage items
                        for (let i = 0; i < localStorage.length; i++) {
                            const key = localStorage.key(i);
                            if (key && (key.includes('admin') || key.includes('Auth'))) {
                                localStorage.removeItem(key);
                            }
                        }
                    }
                } catch (err) {
                    console.warn('Error clearing storage during logout:', err);
                }
                
                // Navigate to logout page
                window.location.href = 'logout.php';
                
                return false;
            }, true); // Use capture phase to run before other handlers
        });
    });
})();
