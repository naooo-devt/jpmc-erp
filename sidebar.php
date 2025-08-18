<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentPage = basename($_SERVER['PHP_SELF']);
$supplyChainPages = ['supply_chain.php', 'transactions.php'];
$isSupplyChainPage = in_array($currentPage, $supplyChainPages);
$isCustomerServicePage = ($currentPage === 'customer_service.php');
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'employee'; // default to employee
?>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="company-logo">
            <a href="index.php">
                <img src="images/logo.png" alt="Company Logo"
                     style="width: 60px; height: 60px; border-radius: 12px; object-fit: contain; display: block;">
            </a>
        </div>
        <div class="company-name">James Polymers</div>
        <div class="company-subtitle">Manufacturing Corporation</div>
    </div>

    <div class="sidebar-menu">
        <div class="menu-section">
            <div class="menu-section-title">Main Navigation</div>

            <a href="index.php" class="menu-item <?= ($currentPage == 'index.php') ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>

            <!-- Inventory Dropdown (visible to all roles) -->
            <div class="menu-item menu-dropdown <?= $isSupplyChainPage ? 'open active' : '' ?>" id="inventoryDropdown">
                <i class="fas fa-link"></i>
                <span> Inventory </span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="dropdown-menu <?= $isSupplyChainPage ? 'open' : '' ?>" id="inventoryDropdownMenu">
                <a href="supply_chain.php" class="menu-item <?= ($currentPage == 'supply_chain.php') ? 'active' : '' ?>">
                    <i class="fas fa-industry"></i>
                    <span>Manufacturing</span>
                </a>
                <a href="transactions.php" class="menu-item <?= ($currentPage == 'transactions.php') ? 'active' : '' ?>">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Transactions</span>
                </a>
            </div>

            <?php if ($userRole === 'admin'): ?>
            <!-- Admin-only links -->
            <a href="finances.php" class="menu-item <?= ($currentPage == 'finances.php') ? 'active' : '' ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span>Finances</span>
            </a>
            <a href="human_resources.php" class="menu-item <?= ($currentPage == 'human_resources.php') ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Human Resources</span>
            </a>
            <a href="customer_service.php" class="menu-item <?= ($currentPage == 'customer_service.php') ? 'active' : '' ?>">
                <i class="fas fa-headset"></i>
                <span>Customer Related Management</span>
            </a>
            <a href="reports.php" class="menu-item <?= ($currentPage == 'reports.php') ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <?php endif; ?>
        </div>

        <div class="menu-section">
            <div class="menu-section-title">System</div>
            <?php if ($userRole === 'admin'): ?>
            <a href="finished_goods.php" class="menu-item <?= ($currentPage == 'finished_goods.php') ? 'active' : '' ?>">
                <i class="fas fa-cog"></i>
                <span>System Administration</span>
            </a>
            <?php endif; ?>
            <a href="logout.php" class="menu-item <?= ($currentPage == 'logout.php') ? 'active' : '' ?>" id="logoutBtn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</div>

<!-- Conditionally include chatbot -->
<?php if (!$isCustomerServicePage): ?>
    <?php include 'chatbot_panel.php'; ?>
<?php endif; ?>

<!-- Optional: Extra CSS to fully hide chatbot if it has a floating icon -->
<style>
<?php if ($isCustomerServicePage): ?>
    #chatbot-float-icon, .chatbot-float {
        display: none !important;
        pointer-events: none;
    }
<?php endif; ?>

#inventoryDropdown .fa-chevron-down {
    transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
}
#inventoryDropdown.open .fa-chevron-down,
#inventoryDropdown.active .fa-chevron-down {
    transform: rotate(180deg);
}
#inventoryDropdownMenu {
    transition: max-height 0.3s cubic-bezier(0.4,0,0.2,1), opacity 0.3s cubic-bezier(0.4,0,0.2,1);
    overflow: hidden;
    max-height: 0;
    opacity: 0;
    display: block !important; /* Always block for animation */
}
#inventoryDropdownMenu.open {
    max-height: 200px; /* Adjust as needed for content */
    opacity: 1;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropdownToggle = document.getElementById('inventoryDropdown');
    const dropdownMenu = document.getElementById('inventoryDropdownMenu');

    // PHP will echo true/false for isSupplyChainPage
    const isSupplyChainPage = <?= $isSupplyChainPage ? 'true' : 'false' ?>;

    // Always use sessionStorage to remember open/close state
    let state = sessionStorage.getItem('supplyChainOpen');

    function updateDropdownState(open) {
        if (open) {
            dropdownToggle.classList.add('open', 'active');
            dropdownMenu.classList.add('open');
        } else {
            dropdownToggle.classList.remove('open', 'active');
            dropdownMenu.classList.remove('open');
        }
    }

    // On page load, use PHP state if on supply chain page, else use sessionStorage
    if (isSupplyChainPage) {
        updateDropdownState(true);
        sessionStorage.setItem('supplyChainOpen', 'true');
    } else {
        updateDropdownState(state === 'true');
    }

    // Handle user toggle
    dropdownToggle.addEventListener('click', function (e) {
        e.stopPropagation();
        const isOpen = !dropdownMenu.classList.contains('open');
        updateDropdownState(isOpen);
        sessionStorage.setItem('supplyChainOpen', isOpen.toString());
    });

    // Prevent dropdown from closing when clicking inside the dropdown menu or its links
    dropdownMenu.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Optional: close dropdown if clicking outside (sidebar or dropdown)
    document.addEventListener('click', function (e) {
        if (!dropdownToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
            updateDropdownState(false);
            sessionStorage.setItem('supplyChainOpen', 'false');
        }
    });
});
</script>
});
</script>

