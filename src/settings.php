<?php
// Correct includes based on index.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/auth/auth.php';

// Instantiate Auth class
$auth = new Auth();

// Redirect to login if not authenticated (using the Auth object)
if (!$auth->isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

// Get username using the Auth object
$userId = $auth->getCurrentUserId();
$username = $auth->getCurrentUsername();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - MidShelf</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Add any specific CSS for settings page if needed later -->
    <script>
        // Apply sidebar state immediately before any rendering
        (function() {
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                document.documentElement.classList.add('sidebar-collapsed-root');
            }
        })();
    </script>
    <style>
        .settings-section {
            background-color: var(--color-surface-raised);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--color-border);
        }
        .settings-section h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--color-text);
            margin-top: 0;
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-md);
            border-bottom: 1px solid var(--color-border);
        }
        .settings-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-md) 0;
            border-bottom: 1px solid var(--color-border-subtle);
        }
        .settings-item:last-child {
            border-bottom: none;
        }
        .settings-item label {
            font-weight: 500;
            color: var(--color-text-secondary);
        }
        /* Style for the color picker element */
        .settings-color-picker {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }
        .settings-color-indicator {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-md);
            border: 2px solid var(--color-border);
            cursor: pointer;
            transition: border-color var(--transition-speed);
        }
        .settings-color-indicator:hover {
             border-color: var(--color-primary);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar HTML structure (copied from index.php, modified for active state) -->
        <aside class="sidebar" id="sidebar">
            <div class="logo">
                <i class="fas fa-compact-disc"></i>
                <span><?php echo APP_NAME; ?></span>
            </div>
            <nav class="sidebar-items">
                <a href="/" class="sidebar-item">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="/items.php" class="sidebar-item">
                    <i class="fas fa-list"></i>
                    <span>All Items</span>
                </a>
                <div class="sidebar-section">
                    <h3>Categories</h3>
                    <div class="sidebar-categories">
                        <!-- Categories loaded via JS -->
                    </div>
                </div>
                <div class="sidebar-section">
                    <h3>Settings</h3>
                    <a href="/categories.php" class="sidebar-item">
                        <i class="fas fa-tags"></i>
                        <span>Categories</span>
                    </a>
                    <a href="/locations.php" class="sidebar-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Locations</span>
                    </a>
                    <a href="/settings.php" class="sidebar-item active"> <!-- Added active class -->
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <!-- Top Bar HTML structure (copied from index.php) -->
            <div class="top-bar">
                <div class="top-bar-left">
                    <button class="toggle-sidebar" id="toggleSidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1>Settings</h1> <!-- Changed heading -->
                </div>
                <div class="actions">
                    <!-- No actions needed on settings page top bar for now -->
                </div>
            </div>

            <!-- Appearance Section -->
            <section class="settings-section">
                <h2>Appearance</h2>
                <div class="settings-item">
                    <label for="accent-color">Accent Color</label>
                    <div class="settings-color-picker theme-picker" title="Change Theme Color">
                        <div class="settings-color-indicator theme-color-indicator"></div>
                        <input type="color" class="color-input" style="visibility: hidden; width: 0; height: 0; position: absolute;">
                    </div>
                </div>
                <!-- Add more appearance settings here if needed -->
            </section>

            <!-- Data Section -->
            <section class="settings-section">
                <h2>Data Management</h2>
                <div class="settings-item">
                    <label>Import Data</label>
                    <button class="btn btn-secondary" disabled>Import</button> <!-- Placeholder -->
                </div>
                <div class="settings-item">
                    <label>Export Data</label>
                    <button class="btn btn-secondary" disabled>Export</button> <!-- Placeholder -->
                </div>
            </section>

        </main>
    </div>

    <script src="/assets/js/app.js"></script>
    <script src="/assets/js/theme.js"></script>
    <!-- Add any specific JS for settings page if needed later -->
</body>
</html>
