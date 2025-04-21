<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/models/Location.php';

$auth = new Auth();

// Redirect to login if not authenticated
if (!$auth->isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

$userId = $auth->getCurrentUserId();
$username = $auth->getCurrentUsername();

$locationModel = new Location();
$locations = $locationModel->getLocations($userId, true); // Get locations with item counts
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Locations</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css?v=2">
    <link rel="icon" href="/Midshelf.png" type="image/png">
    <script>
        // Make PHP user settings available to JavaScript
        window.userSettings = <?php echo json_encode($_SESSION['user_settings'] ?? ['accent_color' => '#8b5cf6', 'skip_item_delete_confirm' => false]); ?>;
    </script>
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
        <!-- Sidebar -->
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
                    <h3>Settings</h3>
                    <a href="/categories.php" class="sidebar-item">
                        <i class="fas fa-tags"></i>
                        <span>Categories</span>
                    </a>
                    <a href="/locations.php" class="sidebar-item active">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Locations</span>
                    </a>
                    <a href="/settings.php" class="sidebar-item">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <div class="top-bar-left">
                    <button class="toggle-sidebar" id="toggleSidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1>Locations</h1>
                </div>
                <div class="actions">
                    <button class="btn btn-primary" onclick="openLocationModal()">
                        <i class="fas fa-plus"></i>
                        <span>Add Location</span>
                    </button>
                </div>
            </div>

            <div class="content">
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Items</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($locations as $location): ?>
                                <tr>
                                    <td>
                                        <div class="location-name">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo htmlspecialchars($location['name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($location['description'] ?? ''); ?></td>
                                    <td><?php echo (int)$location['item_count']; ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <button class="btn-icon" onclick="openLocationModal(<?php echo $location['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon" onclick="deleteLocation(<?php echo $location['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Location Modal -->
    <div class="modal" id="locationModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Location</h2>
                <button class="btn-icon" onclick="closeLocationModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="locationForm" onsubmit="handleSubmit(event)">
                <input type="hidden" id="locationId" name="id">
                
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required placeholder="Enter location name">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" 
                              placeholder="Enter location description"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn" onclick="closeLocationModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Location</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/assets/js/app.js"></script>
    <script src="/assets/js/locations.js"></script>
    <script src="/assets/js/theme.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Set up form submission handler
            const form = document.getElementById('locationForm');
            form.onsubmit = handleSubmit;
        });

        function openLocationModal(locationId = null) {
            const modal = document.getElementById('locationModal');
            const form = document.getElementById('locationForm');
            modal.classList.add('active');
            
            if (locationId) {
                document.getElementById('modalTitle').textContent = 'Edit Location';
                loadLocation(locationId);
            } else {
                document.getElementById('modalTitle').textContent = 'Add Location';
                form.reset();
                document.getElementById('locationId').value = '';
            }
        }

        function closeLocationModal() {
            const modal = document.getElementById('locationModal');
            const form = document.getElementById('locationForm');
            modal.classList.remove('active');
            form.reset();
        }

        function editLocation(locationId) {
            openLocationModal(locationId);
        }

        async function loadLocation(id) {
            try {
                const response = await fetch(`/api/locations.php?id=${id}`);
                if (!response.ok) {
                    throw new Error('Failed to load location');
                }
                const location = await response.json();
                
                document.getElementById('locationId').value = location.id;
                document.getElementById('name').value = location.name;
                document.getElementById('description').value = location.description || '';
            } catch (error) {
                console.error('Error loading location:', error);
                alert(error.message || 'Failed to load location');
            }
        }

        async function handleSubmit(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = {
                name: formData.get('name'),
                description: formData.get('description')
            };

            const locationId = formData.get('id');
            const method = locationId ? 'PUT' : 'POST';
            const url = `/api/locations.php${locationId ? `?id=${locationId}` : ''}`;

            try {
                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Failed to save location');
                }

                location.reload();
            } catch (error) {
                console.error('Error saving location:', error);
                alert(error.message || 'Failed to save location');
            }
        }

        async function deleteLocation(id) {
            if (!confirm('Are you sure you want to delete this location? Items in this location will be unassigned.')) {
                return;
            }

            try {
                const response = await fetch(`/api/locations.php?id=${id}`, {
                    method: 'DELETE'
                });

                if (!response.ok) {
                    throw new Error('Failed to delete location');
                }

                location.reload();
            } catch (error) {
                console.error('Error deleting location:', error);
                alert(error.message || 'Failed to delete location');
            }
        }
    </script>
</body>
</html>
