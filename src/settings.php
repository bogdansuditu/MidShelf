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
    <title><?php echo APP_NAME; ?> - Settings</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <!-- Add SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
        
        /* Danger Button Style */
        .btn-danger {
            background-color: var(--color-danger);
            color: white; /* Ensure text is readable */
            border: 1px solid transparent;
        }
        .btn-danger:hover {
            background-color: var(--color-danger-hover);
            box-shadow: 0 0 8px 0 var(--color-danger-glow); /* Add glow similar to primary */
        }
        /* Ensure form inside settings-item aligns items */
        .settings-item form {
             display: flex;
             align-items: center; /* Align button/input vertically if needed */
             gap: var(--spacing-md);
         }
        .settings-item input[type="file"] {
            /* Basic styling for file input if needed */
            color: var(--color-text-secondary);
        }
        /* SweetAlert2 Customizations (Optional) */
        .swal2-popup {
            font-family: 'Inter', sans-serif;
            background-color: var(--color-surface);
            color: var(--color-text);
            border-radius: var(--radius-lg);
        }
        .swal2-title {
            color: var(--color-text) !important;
        }
        .swal2-html-container {
            color: var(--color-text-secondary) !important;
        }
        .swal2-confirm {
            background-color: var(--color-primary) !important;
            border-radius: var(--radius-md) !important;
        }
        .swal2-confirm:hover {
            background-color: var(--color-primary-hover) !important;
        }
        .swal2-cancel {
            background-color: var(--color-surface-raised) !important;
            color: var(--color-text-secondary) !important;
            border: 1px solid var(--color-border) !important;
            border-radius: var(--radius-md) !important;
        }
        .swal2-cancel:hover {
            background-color: var(--color-surface-hover) !important;
            border-color: var(--color-border-hover) !important;
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
                        <input type="color" class="color-input" value="<?php echo defined('ACCENT_COLOR') ? ACCENT_COLOR : '#8b5cf6'; ?>" style="visibility: hidden; width: 0; height: 0; position: absolute;">
                    </div>
                </div>
                <!-- Add more appearance settings here if needed -->
            </section>

            <!-- Data Section -->
            <section class="settings-section">
                <h2>Data Management</h2>
                <div class="settings-item">
                    <label>Import Data from CSV</label>
                    <form id="import-form" action="import.php" method="post" enctype="multipart/form-data">
                        <input type="file" name="import_file" accept=".csv" required>
                        <button type="submit" class="btn btn-secondary">Import & Replace Data</button>
                    </form>
                </div>
                <div style="margin-left: var(--spacing-md); margin-top: calc(-1 * var(--spacing-md)); /* Adjust alignment */ padding-bottom: var(--spacing-md); font-size: 0.85rem; color: var(--color-text-secondary);">
                    <i class="fas fa-exclamation-triangle" style="color: var(--color-warning);"></i>
                    <strong>Warning:</strong> Importing will replace all current data.
                </div>
                <div class="settings-item">
                    <label>Export All Data</label>
                    <a href="export.php" class="btn btn-secondary">Export as CSV</a>
                </div>
            </section>

            <!-- Danger Zone -->
            <section class="settings-section">
                <h2>Danger Zone</h2>
                <div class="settings-item">
                    <label style="color: var(--color-danger);">Delete All Data</label>
                    <form id="delete-form" action="delete_data.php" method="post">
                        <button type="submit" class="btn btn-danger">Delete All My Data</button>
                    </form>
                </div>
            </section>

        </main>
    </div>

    <script src="/assets/js/app.js"></script>
    <script src="/assets/js/theme.js"></script>
    <!-- Add SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Handle Import Confirmation
            const importForm = document.getElementById('import-form');
            if (importForm) {
                importForm.addEventListener('submit', function(event) {
                    event.preventDefault(); // Stop default submission
                    Swal.fire({
                        title: 'Confirm Import & Replace',
                        html: "This will <strong>DELETE ALL</strong> your existing items, categories, locations, and tags before importing the data from the selected file.<br><br>Are you sure you want to proceed?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Import & Replace!',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: 'var(--color-primary)',
                        cancelButtonColor: 'var(--color-surface-raised)'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // If confirmed, submit the form programmatically
                            importForm.submit(); 
                        }
                    });
                });
            }

            // Handle Delete Confirmation
            const deleteForm = document.getElementById('delete-form');
            if (deleteForm) {
                deleteForm.addEventListener('submit', function(event) {
                    event.preventDefault(); // Stop default submission
                    Swal.fire({
                        title: 'Delete All Data?',
                        html: "Are you absolutely sure? This will delete all your items, categories, locations, and tags <strong>permanently</strong>.<br><br>This action cannot be undone.",
                        icon: 'error', // Use error icon for high danger
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Delete Everything!',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: 'var(--color-danger)',
                        cancelButtonColor: 'var(--color-surface-raised)'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // If confirmed, submit the form programmatically
                            deleteForm.submit();
                        }
                    });
                });
            }

            // Display Feedback Messages from Session
            <?php
            $successMessage = '';
            if (isset($_SESSION['settings_success'])) {
                $successMessage = json_encode($_SESSION['settings_success']);
                unset($_SESSION['settings_success']);
            }
            $errorMessage = '';
            if (isset($_SESSION['settings_error'])) {
                $errorMessage = json_encode($_SESSION['settings_error']);
                unset($_SESSION['settings_error']);
            }
            ?>

            const successMsg = <?php echo $successMessage ?: 'null'; ?>;
            const errorMsg = <?php echo $errorMessage ?: 'null'; ?>;

            if (successMsg) {
                Swal.fire({
                    title: 'Success!',
                    html: successMsg,
                    icon: 'success',
                    confirmButtonColor: 'var(--color-primary)'
                });
            } else if (errorMsg) {
                Swal.fire({
                    title: 'Error!',
                    html: errorMsg,
                    icon: 'error',
                    confirmButtonColor: 'var(--color-danger)'
                });
            }
        });
    </script>
</body>
</html>
