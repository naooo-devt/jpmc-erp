document.addEventListener('DOMContentLoaded', function() {
    // Initialize the entire ERP system
    initERP();
    
    // Additional initialization for transactions page
    if (document.getElementById('transactionTable')) {
        console.log('Transactions page detected, initializing...');
        setTimeout(() => {
            initTransactions();
        }, 200);
    }
    
    // Enhanced Sidebar Inventory Dropdown logic
    const inventoryDropdown = document.getElementById('inventoryDropdown');
    const inventoryDropdownMenu = document.getElementById('inventoryDropdownMenu');
    
    if (inventoryDropdown && inventoryDropdownMenu) {
        console.log('Inventory dropdown elements found, initializing...');
        console.log('Dropdown menu initial state:', {
            classes: inventoryDropdownMenu.className,
            maxHeight: inventoryDropdownMenu.style.maxHeight,
            display: window.getComputedStyle(inventoryDropdownMenu).display
        });
        
        // Remove any existing event listeners
        const newDropdown = inventoryDropdown.cloneNode(true);
        inventoryDropdown.parentNode.replaceChild(newDropdown, inventoryDropdown);
        
        newDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Inventory dropdown clicked');
            console.log('Before toggle - Menu classes:', inventoryDropdownMenu.className);
            console.log('Before toggle - Dropdown classes:', this.className);
            
            // Force the toggle
            const wasOpen = inventoryDropdownMenu.classList.contains('open');
            console.log('Was open before toggle:', wasOpen);
            
            if (wasOpen) {
                inventoryDropdownMenu.classList.remove('open');
                this.classList.remove('open');
            } else {
                inventoryDropdownMenu.classList.add('open');
                this.classList.add('open');
            }
            
            console.log('After toggle - Menu classes:', inventoryDropdownMenu.className);
            console.log('After toggle - Dropdown classes:', this.className);
            
            // Toggle chevron rotation
            const chevron = this.querySelector('.fa-chevron-down');
            if (chevron) {
                chevron.classList.toggle('fa-rotate-180');
            }
            
            console.log('Dropdown state after click:', {
                hasOpenClass: inventoryDropdownMenu.classList.contains('open'),
                maxHeight: inventoryDropdownMenu.style.maxHeight,
                computedMaxHeight: window.getComputedStyle(inventoryDropdownMenu).maxHeight,
                opacity: window.getComputedStyle(inventoryDropdownMenu).opacity,
                visibility: window.getComputedStyle(inventoryDropdownMenu).visibility
            });
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!newDropdown.contains(e.target) && !inventoryDropdownMenu.contains(e.target)) {
                inventoryDropdownMenu.classList.remove('open');
                newDropdown.classList.remove('open');
                const chevron = newDropdown.querySelector('.fa-chevron-down');
                if (chevron) {
                    chevron.classList.remove('fa-rotate-180');
                }
            }
        });
        
        // Test function to manually open dropdown
        window.testDropdown = function() {
            console.log('Manually testing dropdown...');
            inventoryDropdownMenu.classList.add('open');
            newDropdown.classList.add('open');
            const chevron = newDropdown.querySelector('.fa-chevron-down');
            if (chevron) {
                chevron.classList.add('fa-rotate-180');
            }
            console.log('Dropdown manually opened');
        };
        
    } else {
        console.error('Inventory dropdown elements not found:', {
            dropdown: !!inventoryDropdown,
            menu: !!inventoryDropdownMenu
        });
    }
    
    // Enhanced Sidebar Supply Chain Dropdown logic
    const supplyChainDropdown = document.getElementById('supplyChainDropdown');
    const supplyChainDropdownMenu = document.getElementById('supplyChainDropdownMenu');
    
    if (supplyChainDropdown && supplyChainDropdownMenu) {
        console.log('Supply Chain dropdown elements found, initializing...');
        
        // Remove any existing event listeners
        const newSupplyChainDropdown = supplyChainDropdown.cloneNode(true);
        supplyChainDropdown.parentNode.replaceChild(newSupplyChainDropdown, supplyChainDropdown);
        
        newSupplyChainDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Supply Chain dropdown clicked');
            
            // Force the toggle
            const wasOpen = supplyChainDropdownMenu.classList.contains('open');
            
            if (wasOpen) {
                supplyChainDropdownMenu.classList.remove('open');
                this.classList.remove('open');
                const chevron = this.querySelector('.fa-chevron-up');
                if (chevron) {
                    chevron.classList.remove('fa-chevron-up');
                    chevron.classList.add('fa-chevron-down');
                }
            } else {
                supplyChainDropdownMenu.classList.add('open');
                this.classList.add('open');
                const chevron = this.querySelector('.fa-chevron-down');
                if (chevron) {
                    chevron.classList.remove('fa-chevron-down');
                    chevron.classList.add('fa-chevron-up');
                }
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!newSupplyChainDropdown.contains(e.target) && !supplyChainDropdownMenu.contains(e.target)) {
                supplyChainDropdownMenu.classList.remove('open');
                newSupplyChainDropdown.classList.remove('open');
                const chevron = newSupplyChainDropdown.querySelector('.fa-chevron-up');
                if (chevron) {
                    chevron.classList.remove('fa-chevron-up');
                    chevron.classList.add('fa-chevron-down');
                }
            }
        });
        
    } else {
        console.error('Supply Chain dropdown elements not found:', {
            dropdown: !!supplyChainDropdown,
            menu: !!supplyChainDropdownMenu
        });
    }
});

/**
 * Main initialization function. Calls all other init functions.
 */
function initERP() {
    console.log('Initializing ERP system...');
    initSidebarMenu();
    initDashboard();
    initRawMaterials();
    initFinishedGoods();
    initTransactions();
    initReports();
    initModalsAndForms();
    initDatePickers();
}

// =================================================================================
// SIDEBAR AND NAVIGATION
// =================================================================================

function initSidebarMenu() {
    const menuItems = document.querySelectorAll('.menu-item');
    const moduleContents = document.querySelectorAll('.module-content');
    const headerTitle = document.querySelector('.header-title');

    console.log('Initializing sidebar menu with', menuItems.length, 'menu items');

    menuItems.forEach((item, index) => {
        console.log(`Menu item ${index}:`, item.textContent.trim(), 'data-module:', item.dataset.module);
        
        if (item.dataset.module) {
            item.addEventListener('click', function(e) {
                console.log('Menu item clicked:', this.textContent.trim());
                
                if (this.classList.contains('menu-dropdown')) {
                    e.stopPropagation();
                    toggleDropdown(this);
                    return;
                }
                
                const module = this.dataset.module;
                if (this.classList.contains('active')) return;

                menuItems.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                
                if (headerTitle) {
                    headerTitle.textContent = this.querySelector('span').textContent;
                }
                
                moduleContents.forEach(content => content.classList.remove('active'));
                const targetModule = document.getElementById(module);
                if (targetModule) {
                    targetModule.classList.add('active');
                    console.log('Switched to module:', module);
                    
                    // Reinitialize modules when switching
                    if (module === 'transactions') {
                        setTimeout(() => {
                            console.log('Reinitializing transactions module...');
                            initTransactions();
                        }, 100);
                    }
                }
            });
        }
    });

    // Initialize inventory dropdown specifically
    const inventoryDropdown = document.getElementById('inventoryDropdown');
    if (inventoryDropdown) {
        console.log('Inventory dropdown found, adding click listener');
        inventoryDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleDropdown(this);
        });
    } else {
        console.error('Inventory dropdown not found!');
    }

    // --- Logout Functionality ---
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            // Redirect to login.html
            window.location.href = 'login.php';
        });
    }
}

function toggleDropdown(dropdownElement) {
    const dropdownMenu = dropdownElement.nextElementSibling;
    console.log('Toggle dropdown called for:', dropdownElement);
    console.log('Dropdown menu found:', !!dropdownMenu);
    
    if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
        dropdownMenu.classList.toggle('open');
        const chevron = dropdownElement.querySelector('.fa-chevron-down');
        if (chevron) {
            chevron.classList.toggle('fa-rotate-180');
        }
        console.log('Dropdown toggled, open state:', dropdownMenu.classList.contains('open'));
    } else {
        console.error('Dropdown menu not found or invalid structure');
    }
}


// =================================================================================
// DASHBOARD MODULE
// =================================================================================

function initDashboard() {
    if (document.getElementById('inventoryMovementChart') && document.getElementById('stockLevelsChart')) {
        initDashboardCharts();
    }

    // --- Dynamic Stat Cards ---
    updateDashboardStats();

    document.querySelectorAll('#dashboard .chart-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            this.parentElement.querySelectorAll('.chart-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const chartId = this.closest('.chart-container').querySelector('canvas').id;
            updateDashboardCharts(this.textContent.trim(), chartId);
        });
    });

    const viewAllBtn = document.querySelector('#dashboard .table-actions .btn-primary');
    if (viewAllBtn) {
        viewAllBtn.addEventListener('click', function() {
            const transactionMenuItem = document.querySelector('.menu-item[data-module="transactions"]');
            if(transactionMenuItem) transactionMenuItem.click();
        });
    }
}

// --- Demo Data for Stats Calculation ---
function getDemoTransactions() {
    // Simulate fetching recent transactions from the table
    const rows = document.querySelectorAll('#dashboard table tbody tr');
    let transactions = [];
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 7) {
            transactions.push({
                date: cells[0].textContent,
                id: cells[1].textContent,
                material: cells[2].textContent,
                type: cells[3].textContent.includes('OUT') ? 'OUT' : 'IN',
                quantity: parseInt(cells[4].textContent) || 0,
                location: cells[5].textContent,
                balance: parseInt(cells[6].textContent) || 0
            });
        }
    });
    return transactions;
}

function getDemoRawMaterials() {
    // Simulate fetching raw materials from the table
    const rows = document.querySelectorAll('#rawMaterialTable tbody tr');
    let materials = [];
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 7) {
            materials.push({
                name: cells[1].textContent,
                stock: parseFloat(cells[4].textContent.replace(/[^0-9.]/g, '')) || 0,
                status: cells[6].textContent.trim(),
                unit: (cells[4].textContent.match(/kg|Bags|Pieces/i) || [''])[0]
            });
        }
    });
    return materials;
}

function updateDashboardStats() {
    // Calculate stats from demo data
    const materials = getDemoRawMaterials();
    const transactions = getDemoTransactions();

    // Total Inventory Value (simulate with stock * unit cost, assume unit cost = 100 for demo)
    let totalValue = 0;
    materials.forEach(mat => {
        totalValue += mat.stock * 100; // Replace 100 with actual unit cost if available
    });

    // Materials In/Out (this month, simulate by counting IN/OUT in transactions)
    let materialsIn = 0, materialsOut = 0;
    transactions.forEach(trx => {
        if (trx.type === 'IN') materialsIn += trx.quantity;
        if (trx.type === 'OUT') materialsOut += trx.quantity;
    });

    // Low Stock Items (status contains 'Out of Stock' or 'Low Stock')
    let lowStockCount = materials.filter(mat =>
        mat.status.toLowerCase().includes('out of stock') ||
        mat.status.toLowerCase().includes('low stock')
    ).length;

    // Update DOM
    const statCards = document.querySelectorAll('.dashboard-grid .stat-card');
    if (statCards.length >= 4) {
        statCards[0].querySelector('.stat-value').textContent = '₱' + totalValue.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
        statCards[1].querySelector('.stat-value').textContent = materialsIn + ' Bags';
        statCards[2].querySelector('.stat-value').textContent = materialsOut + ' Bags';
        statCards[3].querySelector('.stat-value').textContent = lowStockCount;
    }
}

function initDashboardCharts() {
    const inventoryCtx = document.getElementById('inventoryMovementChart').getContext('2d');
    window.inventoryMovementChart = new Chart(inventoryCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [
                {
                    label: 'Materials In', data: [120, 190, 170, 210, 220, 180],
                    borderColor: '#10B981', backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2, tension: 0.3, fill: true
                },
                {
                    label: 'Materials Out', data: [80, 120, 150, 180, 190, 170],
                    borderColor: '#EF4444', backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2, tension: 0.3, fill: true
                }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' }, tooltip: { mode: 'index', intersect: false } }, scales: { y: { beginAtZero: true, title: { display: true, text: 'Quantity (Bags)' } } } }
    });
    
    const stockCtx = document.getElementById('stockLevelsChart').getContext('2d');
    window.stockLevelsChart = new Chart(stockCtx, {
        type: 'bar',
        data: {
            labels: ['PP Propilinas', 'Nylon', 'ABS', 'Polystyrene', 'HIPS H-Impact'],
            datasets: [{
                label: 'Current Stock', data: [9, 1, 1, 9, 70],
                backgroundColor: ['rgba(59, 130, 246, 0.7)', 'rgba(239, 68, 68, 0.7)', 'rgba(239, 68, 68, 0.7)', 'rgba(234, 179, 8, 0.7)', 'rgba(16, 185, 129, 0.7)'],
                borderWidth: 1
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => `${c.dataset.label}: ${c.raw} Bags` } } }, scales: { y: { beginAtZero: true, title: { display: true, text: 'Quantity (Bags)' } } } }
    });
}

function updateDashboardCharts(period, chartId) {
    if (chartId === 'inventoryMovementChart') {
        let newDataIn, newDataOut, newLabels;
        switch(period.toLowerCase()) {
            case 'monthly': newLabels = ['Month 1', 'Month 2', 'Month 3']; newDataIn = [220, 240, 210]; newDataOut = [180, 200, 170]; break;
            case 'quarterly': newLabels = ['Q1', 'Q2', 'Q3', 'Q4']; newDataIn = [650, 700, 680, 720]; newDataOut = [550, 600, 580, 620]; break;
            default: newLabels = ['Week 1', 'Week 2', 'Week 3', 'Week 4']; newDataIn = [50, 60, 70, 40]; newDataOut = [40, 50, 60, 30];
        }
        window.inventoryMovementChart.data.labels = newLabels;
        window.inventoryMovementChart.data.datasets[0].data = newDataIn;
        window.inventoryMovementChart.data.datasets[1].data = newDataOut;
        window.inventoryMovementChart.update();
    }
    if (chartId === 'stockLevelsChart') {
        if (period.toLowerCase() === 'critical') {
            window.stockLevelsChart.data.labels = ['Nylon', 'ABS'];
            window.stockLevelsChart.data.datasets[0].data = [1, 1];
        } else {
            window.stockLevelsChart.data.labels = ['PP Propilinas', 'Nylon', 'ABS', 'Polystyrene', 'HIPS H-Impact'];
            window.stockLevelsChart.data.datasets[0].data = [9, 1, 1, 9, 70];
        }
        window.stockLevelsChart.update();
    }
}

// =================================================================================
// RAW MATERIALS MODULE
// =================================================================================

function initRawMaterials() {
    const searchInput = document.getElementById('rawMaterialSearchInput');
    const table = document.getElementById('rawMaterialTable');
    if (searchInput && table) {
        const tableRows = table.querySelectorAll('tbody tr');
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            tableRows.forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
            });
        });
    }
}

// =================================================================================
// FINISHED GOODS MODULE
// =================================================================================

function initFinishedGoods() {
    const searchInput = document.getElementById('productSearchInput');
    const table = document.getElementById('productTable');
    if (searchInput && table) {
        const tableRows = table.querySelectorAll('tbody tr');
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            tableRows.forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
            });
        });
    }
}

// =================================================================================
// TRANSACTIONS MODULE
// =================================================================================

function initTransactions() {
    console.log('Initializing transactions module...');
    
    // Check if we're on the transactions page
    const transactionTable = document.getElementById('transactionTable');
    if (!transactionTable) {
        console.log('Not on transactions page, skipping initialization');
        return;
    }
    
    const applyFiltersBtn = document.getElementById('applyTransactionFilters');
    const clearFiltersBtn = document.getElementById('clearTransactionFilters');
    const transTypeFilter = document.getElementById('transTypeFilter');
    const transMaterialFilter = document.getElementById('transMaterialFilter');
    
    console.log('Found elements:', {
        applyFiltersBtn: !!applyFiltersBtn,
        clearFiltersBtn: !!clearFiltersBtn,
        transTypeFilter: !!transTypeFilter,
        transMaterialFilter: !!transMaterialFilter,
        transactionTable: !!transactionTable
    });
    
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', function() {
            // Only call filterTransactions if we're on the transactions page
            if (document.getElementById('transactionTable')) {
                filterTransactions();
            }
        });
        console.log('Added click listener to apply filters button');
    }
    
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', clearTransactionFilters);
        console.log('Added click listener to clear filters button');
    }
    
    // Filter button functionality
    const filterBtns = document.querySelectorAll('.filter-btn');
    console.log('Found filter buttons:', filterBtns.length);
    
    if (filterBtns.length > 0) {
        filterBtns.forEach((btn, index) => {
            console.log(`Initializing filter button ${index}:`, btn.textContent.trim());
            btn.addEventListener('click', function() {
                const filterType = this.getAttribute('data-type');
                const filterValue = this.getAttribute('data-filter');
                
                console.log('Filter button clicked:', filterType, '=', filterValue);
                
                // Remove active class from all buttons of the same type
                filterBtns.forEach(b => {
                    if (b.getAttribute('data-type') === filterType) {
                        b.classList.remove('active');
                    }
                });
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Update hidden input
                const hiddenInput = document.getElementById(filterType + 'Filter');
                if (hiddenInput) {
                    hiddenInput.value = filterValue;
                    console.log('Updated hidden input:', hiddenInput.value);
                }
                
                // Apply filters
                if (document.getElementById('transactionTable')) {
                    filterTransactions();
                }
                
                console.log('Filter changed:', filterType, '=', filterValue);
            });
        });
        console.log('Filter buttons initialized successfully');
    } else {
        console.error('No filter buttons found!');
    }
    
    if (transMaterialFilter) {
        console.log('Material filter found with options:', transMaterialFilter.options.length);
        
        transMaterialFilter.addEventListener('change', function() {
            console.log('Material filter changed to:', this.value);
            // Only call filterTransactions if we're on the transactions page
            if (document.getElementById('transactionTable')) {
                filterTransactions();
            }
        });
        
        // Add click event to ensure dropdown opens
        transMaterialFilter.addEventListener('click', function() {
            console.log('Material filter clicked');
        });
        
        console.log('Added change listener to material filter');
    } else {
        console.log('Material filter not found');
    }
    
    // Transaction Modal functionality
    const addTransactionBtn = document.getElementById('addTransactionBtn');
    const addTransactionModal = document.getElementById('addTransactionModal');
    const cancelAddTransactionModal = document.getElementById('cancelAddTransactionModal');
    const cancelAddTransactionBtn = document.getElementById('cancelAddTransactionBtn');
    
    if (addTransactionBtn && addTransactionModal) {
        addTransactionBtn.addEventListener('click', function() {
            addTransactionModal.style.display = 'block';
        });
    }
    
    if (cancelAddTransactionModal && addTransactionModal) {
        cancelAddTransactionModal.addEventListener('click', function() {
            addTransactionModal.style.display = 'none';
        });
    }
    
    if (cancelAddTransactionBtn && addTransactionModal) {
        cancelAddTransactionBtn.addEventListener('click', function() {
            addTransactionModal.style.display = 'none';
        });
    }
    
    if (addTransactionModal) {
        addTransactionModal.addEventListener('click', function(e) {
            if (e.target === addTransactionModal) {
                addTransactionModal.style.display = 'none';
            }
        });
    }
    
    // Transaction Type Toggle functionality
    const transactionTypeToggle = document.getElementById('transactionTypeToggle');
    const transTypeHidden = document.getElementById('transType');
    
    if (transactionTypeToggle && transTypeHidden) {
        // Set initial state (unchecked = Material Out)
        transactionTypeToggle.checked = false;
        transTypeHidden.value = 'OUT';
        
        transactionTypeToggle.addEventListener('change', function() {
            if (this.checked) {
                transTypeHidden.value = 'IN';
                console.log('Transaction type changed to: IN');
            } else {
                transTypeHidden.value = 'OUT';
                console.log('Transaction type changed to: OUT');
            }
        });
        
        console.log('Transaction type toggle initialized');
    }
    
    console.log('Transactions module initialized successfully');
}

function filterTransactions() {
    console.log('filterTransactions function called!');
    
    // Check if we're on the transactions page
    const transactionTable = document.getElementById('transactionTable');
    if (!transactionTable) {
        console.log('Not on transactions page, skipping filter');
        return;
    }
    
    const transTypeFilter = document.getElementById('transTypeFilter');
    const transMaterialFilter = document.getElementById('transMaterialFilter');
    
    console.log('Elements found in filterTransactions:', {
        transactionTable: !!transactionTable,
        transTypeFilter: !!transTypeFilter,
        transMaterialFilter: !!transMaterialFilter
    });
    
    // Check if elements exist
    if (!transTypeFilter || !transMaterialFilter) {
        console.error('Filter elements not found:', {
            transTypeFilter: !!transTypeFilter,
            transMaterialFilter: !!transMaterialFilter
        });
        return;
    }
    
    const transType = transTypeFilter.value;
    const transMaterial = transMaterialFilter.value;
    
    console.log('Filtering with:', { transType, transMaterial });
    
    const rows = document.querySelectorAll('#transactionTable tbody tr');
    let visibleCount = 0;
    
    rows.forEach((row, index) => {
        let showRow = true;
        
        // Filter by transaction type (column 3 - Type)
        if (transType !== 'all') {
            const typeBadge = row.cells[3].querySelector('.badge');
            if (typeBadge) {
                const type = typeBadge.textContent.toLowerCase();
                console.log(`Row ${index}: Type = ${type}, Filter = ${transType}`);
                if (transType !== type) {
                    showRow = false;
                }
            }
        }
        
        // Filter by material (column 1 - Material)
        if (transMaterial !== 'all') {
            const materialCell = row.cells[1].textContent.trim();
            // Get the material name from the database to match against the material ID
            const selectedOption = transMaterialFilter.options[transMaterialFilter.selectedIndex];
            const selectedMaterialName = selectedOption.textContent.trim();
            
            console.log(`Row ${index}: Material = "${materialCell}", Filter = "${selectedMaterialName}"`);
            
            if (materialCell !== selectedMaterialName) {
                showRow = false;
            }
        }
        
        row.style.display = showRow ? '' : 'none';
        if (showRow) visibleCount++;
    });
    
    console.log(`Showing ${visibleCount} of ${rows.length} transactions`);
    
    // Update filter status
    updateFilterStatus(transType, transMaterial, visibleCount, rows.length);
}

function clearTransactionFilters() {
    // Reset filter dropdowns to "all"
    const transTypeFilter = document.getElementById('transTypeFilter');
    const transMaterialFilter = document.getElementById('transMaterialFilter');
    
    if (transTypeFilter) transTypeFilter.value = 'all';
    if (transMaterialFilter) transMaterialFilter.value = 'all';
    
    // Show all rows
    const rows = document.querySelectorAll('#transactionTable tbody tr');
    rows.forEach(row => {
        row.style.display = '';
    });
    
    // Hide filter status
    const filterStatus = document.getElementById('filterStatus');
    if (filterStatus) filterStatus.style.display = 'none';
    
    // Update filter status to show all transactions
    updateFilterStatus('all', 'all', rows.length, rows.length);
}

function updateFilterStatus(transType, transMaterial, visibleCount, totalCount) {
    const filterStatus = document.getElementById('filterStatus');
    const filterStatusText = document.getElementById('filterStatusText');
    
    if (!filterStatus || !filterStatusText) return;
    
    const filters = [];
    if (transType !== 'all') {
        filters.push(`Type: ${transType.toUpperCase()}`);
    }
    if (transMaterial !== 'all') {
        const materialSelect = document.getElementById('transMaterialFilter');
        const selectedOption = materialSelect.options[materialSelect.selectedIndex];
        filters.push(`Material: ${selectedOption.textContent}`);
    }
    
    if (filters.length > 0) {
        filterStatus.style.display = 'block';
        filterStatusText.textContent = `Showing ${visibleCount} of ${totalCount} transactions (${filters.join(', ')})`;
    } else {
        filterStatus.style.display = 'none';
    }
}

// =================================================================================
// MODALS AND FORMS
// =================================================================================

function initModalsAndForms() {
    console.log('Initializing modals and forms...');
    // This function can be expanded later for general modal/form handling
}

// =================================================================================
// DATE PICKERS
// =================================================================================

function initDatePickers() {
    console.log('Initializing date pickers...');
    // Initialize flatpickr date pickers if they exist
    const datePickers = document.querySelectorAll('.datepicker');
    if (datePickers.length > 0) {
        datePickers.forEach(picker => {
            if (typeof flatpickr !== 'undefined') {
                flatpickr(picker, {
                    dateFormat: "m/d/Y",
                    allowInput: true
                });
            }
        });
    }
}


// =================================================================================
// REPORTS MODULE
// =================================================================================

function initReports() {
    const reportPeriod = document.getElementById('reportPeriod');
    if (reportPeriod) {
        reportPeriod.addEventListener('change', function() {
            document.querySelectorAll('.custom-range').forEach(group => {
                group.style.display = this.value === 'custom' ? 'block' : 'none';
            });
        });
    }
    
    const generateReportBtn = document.querySelectorAll('#reports #generateReportBtn');
    generateReportBtn.forEach(btn => {
        btn.addEventListener('click', generateReport);
    });
    
    if (document.getElementById('reportChart')) {
        initReportChart();
    }
}

function generateReport() {
    const reportType = document.getElementById('reportType').value;
    const reportPeriod = document.getElementById('reportPeriod').value;
    const reportDateFrom = document.getElementById('reportDateFrom').value;
    const reportDateTo = document.getElementById('reportDateTo').value;
    
    const reportTitle = document.querySelector('.report-results h3');
    const reportSummaryItems = document.querySelectorAll('.summary-item p');
    
    let title = `${reportType.charAt(0).toUpperCase() + reportType.slice(1)} Report`;
    let periodText = ` - ${reportPeriod.charAt(0).toUpperCase() + reportPeriod.slice(1)}`;
    if (reportPeriod === 'custom') {
        periodText = ` - From ${reportDateFrom} to ${reportDateTo}`;
    }
    reportTitle.textContent = title + periodText;

    // Demo data - in a real app, this would be fetched
    reportSummaryItems[0].textContent = (Math.floor(Math.random() * 200) + 100) + " Bags";
    reportSummaryItems[1].textContent = (Math.floor(Math.random() * 50)) + " Bags";
    reportSummaryItems[2].textContent = (Math.floor(Math.random() * 50)) + " Bags";
    reportSummaryItems[3].textContent = (Math.floor(Math.random() * 10)) + " Items";

    updateReportChart(reportType);
}

function initReportChart() {
    const reportCtx = document.getElementById('reportChart').getContext('2d');
    window.reportChart = new Chart(reportCtx, {
        type: 'bar',
        data: {
            labels: ['PP Propilinas', 'Nylon', 'ABS', 'Polystyrene', 'HIPS H-Impact'],
            datasets: [{
                label: 'Current Stock',
                data: [9, 1, 1, 9, 70],
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
}

function updateReportChart(reportType) {
    const chart = window.reportChart;
    if (!chart) return;

    if (reportType === 'transactions') {
        chart.config.type = 'line';
        chart.data.labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
        chart.data.datasets = [
            { label: 'In', data: [20, 30, 25, 40], borderColor: 'var(--success)', fill: false },
            { label: 'Out', data: [15, 25, 20, 35], borderColor: 'var(--error)', fill: false }
        ];
    } else {
        chart.config.type = 'bar';
        chart.data.labels = ['PP', 'Nylon', 'ABS', 'PS', 'HIPS'];
        chart.data.datasets = [{
            label: 'Stock Level',
            data: [50, 20, 35, 25, 60],
            backgroundColor: 'rgba(59, 130, 246, 0.7)'
        }];
    }
    chart.update();
}


