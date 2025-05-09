<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/models/Item.php';
require_once __DIR__ . '/models/Category.php';
require_once __DIR__ . '/models/Location.php';
require_once __DIR__ . '/models/Tag.php';

$auth = new Auth();

// Redirect to login if not authenticated
if (!$auth->isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

// Get current user info
$userId = $auth->getCurrentUserId();
$username = $auth->getCurrentUsername();

// Get all items, categories, and locations
$itemModel = new Item();
$categoryModel = new Category();
$locationModel = new Location();

// Handle tag filtering
if (isset($_GET['tag'])) {
    // Sanitize tag before storing in session
    $_SESSION['selected_tag'] = htmlspecialchars(strip_tags($_GET['tag']), ENT_QUOTES, 'UTF-8');
} else if (isset($_GET['reset_tag'])) {
    unset($_SESSION['selected_tag']);
}
$selectedTag = $_SESSION['selected_tag'] ?? null;

// Get all models data
$items = $itemModel->getItems($userId, null, 10, $selectedTag); // Pass null for categoryId and 10 for limit
$categories = $categoryModel->getCategories($userId, true); // Get categories with counts
$locations = $locationModel->getLocations($userId, true); // Get locations with counts
$tagModel = new Tag();
$tags = $tagModel->getTags($userId); // Get all user's tags

// Get total items count
$totalItems = count($itemModel->getItems($userId));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css?v=2">
    <link rel="stylesheet" href="/assets/css/style.css">
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
                <a href="/" class="sidebar-item active">
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
                    <h1>Welcome, <?php echo htmlspecialchars($username); ?></h1>
                </div>
                <div class="actions">
                    <?php if ($selectedTag): ?>
                    <a href="?reset_tag=1" class="tag tag-selected" title="Click to clear filter"><?php echo htmlspecialchars($selectedTag); ?></a>
                    <?php endif; ?>
                    <button class="btn btn-primary" onclick="location.href='/items.php?action=new'">
                        <i class="fas fa-plus"></i>
                        <span>Add Item</span>
                    </button>
                    <button class="btn" onclick="location.href='/auth/logout.php'">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </button>
                </div>
            </div>

            <div class="content fade-in">
                <!-- Categories Section -->
                <section class="home-section">
                    <div class="sidebar-section">
                        <h3>Categories</h3>
                    </div>
                    
                    <div class="cards-grid">
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <a class="card" href="/items.php?category=<?php echo urlencode($category['id']); ?>">
                                    <div class="card-header">
                                        <i class="<?php echo htmlspecialchars($category['icon'] ?? 'fas fa-folder'); ?>" style="color: <?php echo htmlspecialchars($category['color'] ?? '#ccc'); ?>"></i>
                                        <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                                        <span class="item-count"><?php echo (int)$category['item_count']; ?> items</span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-tags"></i>
                                <p>No categories found yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
                
                <!-- Locations Section -->
                <section class="home-section">
                    <div class="sidebar-section">
                        <h3>Locations</h3>
                    </div>
                    
                    <div class="cards-grid">
                        <?php if (!empty($locations)): ?>
                            <?php foreach ($locations as $location): ?>
                                <div class="card">
                                    <div class="card-header">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <h3><?php echo htmlspecialchars($location['name']); ?></h3>
                                        <span class="item-count"><?php echo (int)$location['item_count']; ?> items</span>
                                    </div>
                                    <?php if ($location['description']): ?>
                                        <div class="card-body">
                                            <p><?php echo htmlspecialchars($location['description']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-map-marker-alt"></i>
                                <p>No locations found yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Tags Section -->
                <section class="home-section">
                    <div class="sidebar-section">
                        <h3>Tags</h3>
                    </div>
                    
                    <div class="tags-grid">
                        <?php if (!empty($tags)): ?>
                            <?php foreach ($tags as $tag): ?>
                                <a href="/items.php?tag=<?php echo urlencode($tag['name']); ?>" class="tag">
                                    <?php echo htmlspecialchars($tag['name']); ?>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-tags"></i>
                                <p>No tags found yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
                
                <!-- All Items Section -->
                <section class="home-section">
                    <div class="sidebar-section">
                        <h3>Most recent items <span class="total-items">(<?php echo $totalItems; ?> total)</span></h3>
                    </div>
                    
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="item-name">
                                                <span><?php echo htmlspecialchars($item['name']); ?></span>
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
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($items)): ?>
                                    <tr>
                                        <td colspan="6" class="empty-state">
                                            <div class="empty-state-content">
                                                <i class="fas fa-box-open"></i>
                                                <p>No items found. Click the "Add Item" button to get started!</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script src="/assets/js/app.js"></script>
    <script src="/assets/js/theme.js"></script>
</body>
</html>
