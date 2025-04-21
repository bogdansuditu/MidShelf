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
    <title><?php echo APP_NAME ?? 'App'; ?> - Settings</title>
    <script>
        window.userSettings = <?php
            // Fetch settings from session, provide defaults if not set or empty
            $settings = $_SESSION['user_settings'] ?? [];
            $defaults = [
                'accent_color' => '#8b5cf6',
                'skip_item_delete_confirm' => false
            ];
            // Ensure boolean type for the toggle after merging
            $finalSettings = array_merge($defaults, $settings);
            $finalSettings['skip_item_delete_confirm'] = filter_var($finalSettings['skip_item_delete_confirm'], FILTER_VALIDATE_BOOLEAN);
            echo json_encode($finalSettings);
        ?>;
    </script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" href="/Midshelf.png" type="image/png">
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
                <h1>Appearance</h1>
                <div class="settings-item">
                    <label for="accent-color">Accent Color</label>
                    <div class="settings-color-picker theme-picker" title="Change Theme Color">
                        <div class="settings-color-indicator theme-color-indicator"></div>
                        <input type="color" class="color-input" value="<?php echo defined('ACCENT_COLOR') ? ACCENT_COLOR : '#8b5cf6'; ?>" style="visibility: hidden; width: 0; height: 0; position: absolute;" id="accentColorPicker">
                    </div>
                </div>
                <!-- Add more appearance settings here if needed -->
            </section>

            <!-- Data Section -->
            <section class="settings-section">
                <h1>Data Management</h1>
                <div class="settings-item">
                    <label>Import Data from CSV</label>
                    <form id="import-form" action="import.php" method="post" enctype="multipart/form-data">
                        <input type="file" name="import_file" accept=".csv" required>
                        <button type="submit" class="btn btn-primary">Import & Replace Data</button>
                    </form>
                </div>
                <div style="margin-left: var(--spacing-md); margin-top: calc(-1 * var(--spacing-md)); /* Adjust alignment */ padding-bottom: var(--spacing-md); font-size: 0.85rem; color: var(--color-text-secondary);">
                    <i class="fas fa-exclamation-triangle" style="color: var(--color-warning);"></i>
                    <strong>Warning:</strong> Importing will replace all current data.
                </div>
                <div class="settings-item">
                    <label>Export All Data</label>
                    <a href="export.php" class="btn btn-primary">Export as CSV</a>
                </div>
            </section>

            <!-- Data Admin Section -->
            <section class="settings-section">
                <h1>Data Admin</h1>
                <div class="settings-item">
                    <label>Export Database</label>
                    <a href="/api/database.php?action=export" class="btn btn-primary">Export as JSON</a>
                </div>
                <div style="margin-left: var(--spacing-md); margin-top: calc(-1 * var(--spacing-md)); padding-bottom: var(--spacing-md); font-size: 0.85rem; color: var(--color-text-secondary);">
                    <i class="fas fa-info-circle"></i> Exports all database tables including users and settings.
                </div>
                <div class="settings-item">
                    <label>Import Database</label>
                    <form id="database-import-form" action="/api/database.php?action=import" method="post" enctype="multipart/form-data">
                        <input type="file" name="backup_file" accept=".json" required>
                        <button type="submit" class="btn btn-warning">Import Database Backup</button>
                    </form>
                </div>
                <div style="margin-left: var(--spacing-md); margin-top: calc(-1 * var(--spacing-md)); padding-bottom: var(--spacing-md); font-size: 0.85rem; color: var(--color-text-secondary);">
                    <i class="fas fa-exclamation-triangle" style="color: var(--color-warning);"></i>
                    <strong>Warning:</strong> This will completely replace ALL data in the database, including users and settings.
                </div>
            </section>

            <!-- Danger Zone -->
            <section class="settings-section">
                <h1>Danger Zone</h1>
                <div class="settings-item">
                    <label for="skipItemDeleteConfirmToggle">Skip Item Delete Confirmation</label>
                    <label class="toggle-switch">
                        <input type="checkbox" id="skipItemDeleteConfirmToggle">
                        <span class="slider round"></span>
                    </label>
                </div>
                <div style="margin-left: var(--spacing-md); margin-top: calc(-1 * var(--spacing-md)); /* Adjust alignment */ padding-bottom: var(--spacing-md); font-size: 0.85rem; color: var(--color-text-secondary);">
                    <i class="fas fa-info-circle"></i> If enabled, items on the 'All Items' page will be deleted immediately without a confirmation prompt.
                </div>
                <div class="settings-item">
                    <label style="color: var(--color-danger);">Reset user data</label>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Handle database import form
            const databaseImportForm = document.getElementById('database-import-form');
            if (databaseImportForm) {
                databaseImportForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    
                    Swal.fire({
                        title: 'Are you absolutely sure?',
                        html: `<div style="text-align: left;">
                            <p><strong>Warning:</strong> This action will:</p>
                            <ul style="margin-top: 0.5em;">
                                <li>Delete ALL existing data from the database</li>
                                <li>Replace ALL users and their settings</li>
                                <li>Reset ALL auto-increment counters</li>
                            </ul>
                            <p style="margin-top: 1em;">This action cannot be undone!</p>
                            </div>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, replace everything',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: 'var(--color-danger)',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading state
                            Swal.fire({
                                title: 'Importing Database...',
                                html: 'Please do not close this window.',
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                allowEnterKey: false,
                                showConfirmButton: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            const formData = new FormData(databaseImportForm);
                            fetch('/api/database.php?action=import', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: 'Success!',
                                        text: 'Database has been restored. You will be logged out.',
                                        icon: 'success',
                                        allowOutsideClick: false,
                                        allowEscapeKey: false,
                                        allowEnterKey: false,
                                    }).then(() => {
                                        window.location.href = '/login.php';
                                    });
                                } else {
                                    throw new Error(data.message || 'Import failed');
                                }
                            })
                            .catch(error => {
                                Swal.fire(
                                    'Error!',
                                    error.message || 'Failed to import database',
                                    'error'
                                );
                            });
                        }
                    });
                });
            }
            const colorPicker = document.getElementById('accentColorPicker');
            const skipItemDeleteConfirmToggle = document.getElementById('skipItemDeleteConfirmToggle');
            const feedbackContainer = document.getElementById('feedback-message'); // Assuming you have a container for messages

            // Helper function to update setting via API
            async function updateSetting(key, value) {
                try {
                    const response = await fetch('/api/settings.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ setting_key: key, setting_value: value })
                    });

                    const result = await response.json();

                    if (response.ok && result.success) {
                        // Optionally show brief success feedback
                        console.log(`Setting '${key}' updated successfully.`);
                        // You could use a small temporary message here instead of Swal
                    } else {
                        // Show error using Swal
                        Swal.fire(
                            'Error!',
                            `Failed to update setting: ${result.message || 'Unknown server error'}`,
                            'error'
                        );
                        // Revert UI if needed (might be complex)
                    }
                } catch (error) {
                    console.error('Error updating setting:', error);
                    Swal.fire(
                        'Network Error!',
                        'Failed to communicate with the server to update setting.',
                        'error'
                    );
                }
            }

            // Initialize and handle Accent Color Picker
            if (colorPicker) {
                // Initialize from window.userSettings
                const initialColor = window.userSettings.accent_color || '#8b5cf6'; // Use default if missing
                colorPicker.value = initialColor;
                // Apply initial color immediately (if theme.js doesn't already do it based on window.userSettings)
                // document.documentElement.style.setProperty('--color-primary', initialColor); // Consider if theme.js handles this

                // Save state via API on change (debounced)
                let debounceTimer;
                colorPicker.addEventListener('input', function() {
                    const newColor = this.value;
                    // Apply color immediately for visual feedback
                    document.documentElement.style.setProperty('--color-primary', newColor);
                    // Debounce API call
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        updateSetting('accent_color', newColor);
                    }, 500); // Send update 500ms after user stops changing color
                });
            }

            // Handle Skip Item Delete Confirmation Toggle
            if (skipItemDeleteConfirmToggle) {
                // Initialize toggle state from window.userSettings
                const skipConfirm = window.userSettings.skip_item_delete_confirm || false; // Default false
                skipItemDeleteConfirmToggle.checked = skipConfirm;

                // Save state via API on change
                skipItemDeleteConfirmToggle.addEventListener('change', function() {
                    updateSetting('skip_item_delete_confirm', this.checked);
                });
            }

            // Get the forms
            const importForm = document.getElementById('import-form');
            const deleteForm = document.getElementById('delete-form');

            // Add confirmation for Import Data form
            if (importForm) {
                importForm.addEventListener('submit', function(event) {
                    event.preventDefault(); // Prevent default submission
                    Swal.fire({
                        title: 'Are you sure?',
                        html: "Importing will <strong>replace all</strong> your current items, categories, locations, and tags. This cannot be undone.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, import and replace!',
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

            // Add confirmation for Delete All Data form
            if (deleteForm) {
                deleteForm.addEventListener('submit', function(event) {
                    event.preventDefault(); // Prevent default submission
                    Swal.fire({
                        title: 'ARE YOU ABSOLUTELY SURE?',
                        html: "This action will permanently delete <strong>ALL</strong> your data, including items, categories, locations, and tags. <strong style='color: var(--color-danger);'>This cannot be undone.</strong>",
                        icon: 'error', // Use error icon for high severity
                        showCancelButton: true,
                        confirmButtonText: 'Yes, DELETE EVERYTHING!',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: 'var(--color-danger)', // Use danger color for confirm
                        cancelButtonColor: 'var(--color-surface-raised)'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // If confirmed, submit the form programmatically
                            deleteForm.submit();
                        }
                    });
                });
            }

            // Display Feedback Messages from Session (Existing logic)
            <?php
            $successMessage = '';
            if (isset($_SESSION['feedback_success'])) {
                $successMessage = $_SESSION['feedback_success'];
                unset($_SESSION['feedback_success']);
            }
            $errorMessage = '';
            if (isset($_SESSION['feedback_error'])) {
                $errorMessage = $_SESSION['feedback_error'];
                unset($_SESSION['feedback_error']);
            }
            ?>

            const successMsg = <?php echo $successMessage ?: 'null'; ?>;
            const errorMsg = <?php echo addslashes($errorMessage) ?: 'null'; ?>;

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
