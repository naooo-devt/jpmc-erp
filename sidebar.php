<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$supplyChainPages = ['supply_chain.php', 'suppliers.php'];
$isSupplyChainPage = in_array($currentPage, $supplyChainPages);
?>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="company-logo">
            <a href="index.php">
                <img src="images/logo.png" alt="Company Logo"
                     style="width: 60px; height: 60px; border-radius: 12px; object-fit: contain; display: block;">
            </a>
        </div>
        <div class="company-name">James Polymer</div>
        <div class="company-subtitle">Manufacturing Corporation</div>
    </div>

    <div class="sidebar-menu">
        <div class="menu-section">
            <div class="menu-section-title">Main Navigation</div>

            <a href="index.php" class="menu-item <?= ($currentPage == 'index.php') ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>

            <a href="finances.php" class="menu-item <?= ($currentPage == 'finances.php') ? 'active' : '' ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span>Finances</span>
            </a>

            <a href="human_resources.php" class="menu-item <?= ($currentPage == 'human_resources.php') ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Human Resources</span>
            </a>

            <!-- Dropdown Toggle -->
            <div class="menu-item menu-dropdown" id="inventoryDropdown">
                <i class="fas fa-link"></i>
                <span>Supply Chain</span>
                <i class="fas fa-chevron-down"></i>
            </div>

            <!-- Dropdown Menu -->
           <div class="dropdown-menu" id="inventoryDropdownMenu">
                <a href="supply_chain.php" class="menu-item <?= ($currentPage == 'supply_chain.php') ? 'active' : '' ?>">
                    <i class="fas fa-industry"></i>
                    <span>Manufacturing</span>
                </a>
                <a href="suppliers.php" class="menu-item <?= ($currentPage == 'suppliers.php') ? 'active' : '' ?>">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Transactions</span>
                </a>
            </div>

            <a href="customer_service.php" class="menu-item <?= ($currentPage == 'customer_service.php') ? 'active' : '' ?>">
                <i class="fas fa-headset"></i>
                <span>Customer Service</span>
            </a>

            <a href="reports.php" class="menu-item <?= ($currentPage == 'reports.php') ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
        </div>

        <div class="menu-section">
            <div class="menu-section-title">System</div>

            <a href="finished_goods.php" class="menu-item <?= ($currentPage == 'finished_goods.php') ? 'active' : '' ?>">
                <i class="fas fa-cog"></i>
                <span>System Administration</span>
            </a>

            <a href="logout.php" class="menu-item <?= ($currentPage == 'logout.php') ? 'active' : '' ?>" id="logoutBtn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</div>

<?php include 'chatbot_panel.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropdownToggle = document.getElementById('inventoryDropdown');
    const dropdownMenu = document.getElementById('inventoryDropdownMenu');

    // Pages considered part of the Supply Chain dropdown
    const childPages = ['supply_chain.php', 'suppliers.php'];
    const currentPage = window.location.pathname.split('/').pop();

    // Check if user manually toggled the dropdown
    let state = sessionStorage.getItem('supplyChainOpen');

    // If user hasn't toggled manually yet, default to open if on a child page
    if (state === null && childPages.includes(currentPage)) {
        sessionStorage.setItem('supplyChainOpen', 'true');
        state = 'true';
    }

    if (state === 'true') {
        dropdownToggle.classList.add('open', 'active');
        dropdownMenu.classList.add('open');
    }

    dropdownToggle.addEventListener('click', function () {
        const isOpen = dropdownMenu.classList.toggle('open');
        dropdownToggle.classList.toggle('open');
        dropdownToggle.classList.toggle('active', isOpen);
        sessionStorage.setItem('supplyChainOpen', isOpen.toString());
    });
});
</script>
