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

// Handle tag filtering
if (isset($_GET['tag'])) {
    // Sanitize tag before storing in session
    $_SESSION['selected_tag'] = htmlspecialchars(strip_tags($_GET['tag']), ENT_QUOTES, 'UTF-8');
} else if (isset($_GET['reset_tag'])) {
    unset($_SESSION['selected_tag']);
}
$selectedTag = $_SESSION['selected_tag'] ?? null;

// Get items for the current user
$itemModel = new Item();
$items = $itemModel->getItems($userId, $categoryId, null, $selectedTag);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Items</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css?v=2">
    <link rel="icon" href="/Midshelf.png" type="image/png">
    <!-- Add SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
                <a href="/?reset_tag=1" class="sidebar-item">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="/items.php?reset_tag=1" class="sidebar-item active">
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
                    <h1>
                        <?php if ($categoryId): ?>
                            <span class="category-name">Loading category...</span>
                        <?php else: ?>
                            All Items
                        <?php endif; ?>
                    </h1>
                </div>
                <div class="actions">
                    <?php if ($selectedTag): ?>
                    <a href="?reset_tag=1" class="tag tag-selected" title="Click to clear filter"><?php echo htmlspecialchars($selectedTag); ?></a>
                    <?php endif; ?>
                    <button class="btn btn-primary" onclick="openItemModal(null, 'add')">
                        <i class="fas fa-plus"></i>
                        <span>Add Item</span>
                    </button>
                </div>
            </div>

            <div class="content fade-in">
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Link</th>
                                <th>Rating</th>
                                <th>Tags</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="7" class="empty-state">
                                        <div class="empty-state-content">
                                            <i class="fas fa-box-open"></i>
                                            <p>No items found in this view.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td>
                                            <a class="item-cell-link" href="javascript:void(0);" onclick="openItemModal(<?php echo htmlspecialchars((string)$item['id'], ENT_QUOTES, 'UTF-8'); ?>, 'edit')">
                                                <div class="item-main-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                                <?php if ($item['description']): ?>
                                                    <div class="item-description"><?php echo htmlspecialchars(substr($item['description'], 0, 50)) . (strlen($item['description']) > 50 ? '...' : ''); ?></div>
                                                <?php endif; ?>
                                            </a>
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
                                            <?php if (!empty($item['link'])): ?>
                                                <div class="item-link">
                                                    <a href="<?php echo htmlspecialchars($item['link']); ?>" target="_blank" rel="noopener noreferrer" class="accent-link">
                                                        <i class="fa-solid fa-link" title="Open link"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                        <div class="rating">
                                            <?php for ($i = 1; $i <= (int)$item['rating']; $i++): ?>
                                                <i class="fas fa-star"></i>
                                            <?php endfor; ?>
                                            <?php for ($i = (int)$item['rating'] + 1; $i <= 5; $i++): ?>
                                                <i class="far fa-star"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </td>
                                        <td>
                                            <div class="tags<?php echo $selectedTag ? ' has-selected-tag' : ''; ?>">
                                                <?php if (!empty($item['tags'])): ?>
                                                    <?php foreach ($item['tags'] as $tag): ?>
                                                        <a href="?tag=<?php echo urlencode($tag); ?>" class="tag<?php echo $selectedTag === $tag ? ' tag-selected' : ''; ?>"><?php echo htmlspecialchars($tag); ?></a>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <button class="btn-icon" onclick="openItemModal(<?php echo $item['id']; ?>, 'edit')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-icon" onclick="duplicateItem(<?php echo $item['id']; ?>)" title="Duplicate Item">
                                                    <i class="fas fa-copy"></i>
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
            </div>
        </main>
    </div>

    <!-- Item Modal -->
    <div class="modal" id="itemModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="itemModalTitle">Add Item</h2>
                <button class="btn-icon" onclick="closeItemModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php 
            // Initialize empty item for the form
            $item = null;
            include __DIR__ . '/components/item-form.php'; 
            ?>
        </div>
    </div>

    <script src="/assets/js/app.js"></script>
    <script src="/assets/js/theme.js"></script>
    <!-- Add SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/assets/js/items.js"></script>

</body>
</html>
