/**
 * Sidebar functionality for the Moodle Analytics Dashboard
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const sidebar = document.querySelector('.sidebar');
    
    if (mobileMenuButton && sidebar) {
        mobileMenuButton.addEventListener('click', function() {
            sidebar.classList.toggle('hidden');
            sidebar.classList.toggle('block');
        });
    }
    
    // Toggle submenus
    const menuItems = document.querySelectorAll('.menu-item');
    
    menuItems.forEach(item => {
        const button = item.querySelector('.menu-button');
        const submenu = item.querySelector('.submenu');
        
        if (button && submenu) {
            button.addEventListener('click', () => {
                // Close all other open submenus
                document.querySelectorAll('.submenu').forEach(menu => {
                    if (menu !== submenu) {
                        menu.classList.add('hidden');
                    }
                });
                
                // Toggle current submenu
                submenu.classList.toggle('hidden');
                
                // Rotate chevron icon
                const chevron = button.querySelector('.chevron');
                if (chevron) {
                    chevron.classList.toggle('transform');
                    chevron.classList.toggle('rotate-180');
                }
            });
        }
    });
    
    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.sidebar') && !event.target.closest('#mobile-menu-button')) {
            if (window.innerWidth < 1024) {
                sidebar.classList.add('hidden');
                sidebar.classList.remove('block');
            }
        }
    });
    
    // Close mobile menu when a menu item is clicked
    const menuLinks = document.querySelectorAll('.sidebar a');
    menuLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 1024) {
                sidebar.classList.add('hidden');
                sidebar.classList.remove('block');
            }
        });
    });
});

// Handle window resize
window.addEventListener('resize', function() {
    const sidebar = document.querySelector('.sidebar');
    if (window.innerWidth >= 1024) {
        sidebar.classList.remove('hidden');
        sidebar.classList.add('block');
    }
});