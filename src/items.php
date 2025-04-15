<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/models/Item.php';

$auth = new Auth();

// Redirect to login if not authenticated
if (!$auth->isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

$userId = $auth->getCurrentUserId();
$username = $auth->getCurrentUsername();

// Get current category if specified
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;
$action = $_GET['action'] ?? 'list';

// Get items for the current user
$itemModel = new Item();
$items = $itemModel->getItems($userId, $categoryId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Items</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script>
        // Apply sidebar state immediately before any rendering
        (function() {
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                document.documentElement.classList.add('sidebar-collapsed-root');
            }
        })();
    </script>
    <style>
        html.sidebar-collapsed-root .sidebar {
            width: 60px !important;
        }
        html.sidebar-collapsed-root .container {
            grid-template-columns: 60px 1fr !important;
        }
    </style>
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
                <a href="/items.php" class="sidebar-item active">
                    <i class="fas fa-list"></i>
                    <span>All Items</span>
                </a>
                <div class="sidebar-section">
                    <h3>Categories</h3>
                    <div class="sidebar-categories">
                        <!-- Categories will be loaded here via JavaScript -->
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
                    <h1>
                        <?php if ($categoryId): ?>
                            <span class="category-name">Loading category...</span>
                        <?php else: ?>
                            All Items
                        <?php endif; ?>
                    </h1>
                </div>
                <div class="actions">
                    <!-- Theme Picker -->
                    <div class="theme-picker" title="Change Theme Color">
                        <div class="theme-color-indicator"></div>
                        <input type="color" class="color-input" style="visibility: hidden; width: 0; height: 0; position: absolute;">
                    </div>
                    <button class="btn btn-primary" onclick="location.href='/items.php?action=new'">
                        <i class="fas fa-plus"></i>
                        <span>Add Item</span>
                    </button>
                </div>
            </div>

            <div class="content fade-in">
                <?php if ($action === 'new' || $action === 'edit'): ?>
                    <div class="card">
                        <?php include __DIR__ . '/components/item-form.php'; ?>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Location</th>
                                    <th>Rating</th>
                                    <th>Tags</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($items)): ?>
                                    <tr>
                                        <td colspan="6" class="empty-state">
                                            <div class="empty-state-content">
                                                <i class="fas fa-box-open"></i>
                                                <p>No items found in this view.</p>
                                                <button class="btn btn-sm btn-primary" onclick="location.href='/items.php?action=new'">
                                                    <i class="fas fa-plus"></i>
                                                    <span>Add Item</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($items as $item): ?>
                                        <tr data-id="<?php echo $item['id']; ?>">
                                            <td>
                                                <div class="item-name">
                                                    <span><?php echo htmlspecialchars($item['name']); ?></span>
                                                    <?php if ($item['description']): ?>
                                                        <small class="item-description-tooltip" title="<?php echo htmlspecialchars($item['description']); ?>">
                                                            <?php echo htmlspecialchars(substr($item['description'], 0, 50)) . (strlen($item['description']) > 50 ? '...' : ''); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($item['category_name']): ?>
                                                    <div class="category-name">
                                                        <i class="<?php echo htmlspecialchars($item['category_icon'] ?? 'fas fa-folder'); ?>" 
                                                           style="color: <?php echo htmlspecialchars($item['category_color'] ?? '#8b5cf6'); ?>"></i>
                                                        <span><?php echo htmlspecialchars($item['category_name']); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($item['location_name']): ?>
                                                    <div class="location-name">
                                                        <i class="fas fa-map-marker-alt"></i>
                                                        <span><?php echo htmlspecialchars($item['location_name']); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="rating">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?php echo $i <= ($item['rating'] ?? 0) ? 'active' : ''; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="tags">
                                                    <?php if (!empty($item['tags'])): ?>
                                                        <?php foreach ($item['tags'] as $tag): ?>
                                                            <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="table-actions">
                                                    <button class="btn-icon" onclick="editItem(<?php echo $item['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn-icon" onclick="deleteItem(<?php echo $item['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="/assets/js/app.js"></script>
    <script src="/assets/js/theme.js"></script>
</body>
</html>
