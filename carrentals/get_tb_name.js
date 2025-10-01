document.addEventListener('DOMContentLoaded', (event) => {
    const tableName = document.querySelectorAll(".nav-button");
    const currentTable = new URLSearchParams(window.location.search).get('table') || 'Rentals';

    tableName.forEach(data => {
        let buttonTable;
        
                if (data.id === 'vehicles-data') {
            buttonTable = 'Vehicles';
        } else if (data.id === 'customers-data') {
            buttonTable = 'Customers';
        } else if (data.id === 'rentals-data') {
            buttonTable = 'Rentals';
        } else if (data.id === 'issues-data') {
            buttonTable = 'Issues';
        } else if (data.id === 'distance-data') {
            buttonTable = 'Distances';
        } else if (data.id === 'payments-data') {
            buttonTable = 'Payments';
        } else if (data.id === 'users-data') {
            buttonTable = 'Users';
        } else {
                        const navTextSpan = data.querySelector('.nav-text');
            if (navTextSpan) {
                buttonTable = navTextSpan.textContent.trim();
            } else {
                buttonTable = data.textContent.trim().replace(/[\u{1F000}-\u{1F6FF}]|[\u{1F900}-\u{1F9FF}]|[\u{2600}-\u{26FF}]|[\u{2700}-\u{27BF}]/gu, '').trim();
            }
        }
        
                if (buttonTable === currentTable) {
            data.classList.add('active');
        }

                data.addEventListener('click', event => {
            event.preventDefault();
            
                        tableName.forEach(btn => btn.classList.remove('active'));
                        data.classList.add('active');
            
                        window.location.href = 'dashboard.php?table=' + buttonTable;
        });
    });

        const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    if (sidebarToggle && sidebar && mainContent) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            sidebar.classList.toggle('minimized');
            mainContent.classList.toggle('expanded');
            
            const icon = sidebarToggle.querySelector('i');
            if (sidebar.classList.contains('minimized')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-chevron-right');
            } else {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-bars');
            }
        });
        
                sidebar.addEventListener('click', function(e) {
            if (sidebar.classList.contains('minimized') && 
                !e.target.closest('.nav-button') && 
                !e.target.closest('.sidebar-toggle') &&
                !e.target.closest('.user-info') &&
                !e.target.closest('.logout-link')) {
                sidebar.classList.remove('minimized');
                mainContent.classList.remove('expanded');
                
                const icon = sidebarToggle.querySelector('i');
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-bars');
            }
        });
    }
});