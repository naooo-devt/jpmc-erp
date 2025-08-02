<?php
session_start();
if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}
require_once 'db_connect.php';

// Fetch user details from session for display.
$username = htmlspecialchars($_SESSION['username']);
$role = htmlspecialchars($_SESSION['role']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Administration - James Polymer ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="icon" href="images/logo.png">
</head>
<body>
    <!--SideBar MENU -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <i class="fas fa-users" style="font-size: 1.5rem; color: var(--dark-blue);"></i>
                <h1 class="header-title">System Administration</h1>
            </div>
            <div class="header-right">
                <div class="user-profile">
                    <i class="fas fa-user-shield"></i>
                    <span><?php echo ucfirst($role); ?></span>
                </div>
            </div>
        </div>
        <div class="content">
            <div class="module-content active" id="System-Administration">
                <div class="content-section">
                    <div class="section-header">
                        <h2>System Administration</h2>
                        <p>This section is currently under development.</p>
                    </div>
                    <div class="empty-state">
                        <i class="fas fa-users" style="font-size: 4rem; color: var(--gray); margin-bottom: 1rem;"></i>
                        <h3>System Administration Module</h3>
                        <p>Employee management, recruitment, and HR functions will be available here.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        // Initialize sidebar functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const sidebar = document.querySelector('.sidebar');
            const supplyChainDropdown = document.getElementById('supplyChainDropdown');
            const supplyChainDropdownMenu = document.getElementById('supplyChainDropdownMenu');

            // Mobile menu toggle
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }

            // Close mobile menu when clicking outside
            document.addEventListener('click', function(event) {
                if (sidebar && sidebar.classList.contains('active')) {
                    if (!sidebar.contains(event.target) && !mobileMenuToggle.contains(event.target)) {
                        sidebar.classList.remove('active');
                    }
                }
            });

            // Supply Chain dropdown functionality
            if (supplyChainDropdown) {
                supplyChainDropdown.addEventListener('click', function() {
                    supplyChainDropdownMenu.classList.toggle('active');
                });
            }

            // Handle window resize
            function handleResize() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('active');
                }
            }

            window.addEventListener('resize', handleResize);
            handleResize();
        });
    </script>
</body>

</html> 
