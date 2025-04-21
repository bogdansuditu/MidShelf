<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/models/Category.php';

$auth = new Auth();

// Redirect to login if not authenticated
if (!$auth->isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

$userId = $auth->getCurrentUserId();
$username = $auth->getCurrentUsername();

$categoryModel = new Category();
$categories = $categoryModel->getCategories($userId, true); // Get categories with item counts
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Categories</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <a href="/categories.php" class="sidebar-item active">
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
                    <h1>Categories</h1>
                </div>
                <div class="actions">
                    <button class="btn btn-primary" onclick="openCategoryModal()">
                        <i class="fas fa-plus"></i>
                        <span>Add Category</span>
                    </button>
                </div>
            </div>

            <div class="content">
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Icon</th>
                                <th>Color</th>
                                <th>Items</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td>
                                        <div class="category-name">
                                            <i class="<?php echo htmlspecialchars($category['icon']); ?>" style="color: <?php echo htmlspecialchars($category['color']); ?>"></i>
                                            <span><?php echo htmlspecialchars($category['name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($category['icon']); ?></td>
                                    <td>
                                        <div class="color-preview" style="background-color: <?php echo htmlspecialchars($category['color']); ?>"></div>
                                    </td>
                                    <td><?php echo (int)$category['item_count']; ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <button class="btn-icon" onclick="openCategoryModal(<?php echo $category['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon" onclick="deleteCategory(<?php echo $category['id']; ?>)">
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

    <!-- Category Modal -->
    <div class="modal" id="categoryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Category</h2>
                <button class="btn-icon" onclick="closeCategoryModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="categoryForm" onsubmit="handleSubmit(event)">
                <input type="hidden" id="categoryId" name="id">
                
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required placeholder="Enter category name">
                </div>

                <div class="form-group">
                    <label for="icon">Icon</label>
                    <div class="icon-picker">
                        <input type="text" id="icon" name="icon" required placeholder="e.g. fas fa-book">
                        <div class="icon-preview">
                            <i id="iconPreview" class="fas fa-book"></i>
                        </div>
                    </div>
                    <small>Use Font Awesome icon classes (e.g. fas fa-book)</small>
                </div>

                <div class="form-group">
                    <label for="color">Color</label>
                    <input type="color" id="color" name="color" required value="#8b5cf6">
                </div>

                <div class="form-actions">
                    <button type="button" class="btn" onclick="closeCategoryModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/assets/js/app.js"></script>
    <script src="/assets/js/categories.js"></script>
    <script src="/assets/js/theme.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Set up form submission handler
            const form = document.getElementById('categoryForm');
            form.onsubmit = handleSubmit;

            // Set up icon preview
            const iconInput = document.getElementById('icon');
            iconInput.addEventListener('input', () => {
                document.getElementById('iconPreview').className = iconInput.value;
            });
        });

        function openCategoryModal(categoryId = null) {
            const modal = document.getElementById('categoryModal');
            const form = document.getElementById('categoryForm');
            modal.classList.add('active');
            
            if (categoryId) {
                document.getElementById('modalTitle').textContent = 'Edit Category';
                loadCategory(categoryId);
            } else {
                document.getElementById('modalTitle').textContent = 'Add Category';
                form.reset();
                document.getElementById('categoryId').value = '';
                document.getElementById('icon').value = 'fas fa-folder';
                document.getElementById('iconPreview').className = 'fas fa-folder';
            }
        }

        function closeCategoryModal() {
            const modal = document.getElementById('categoryModal');
            const form = document.getElementById('categoryForm');
            modal.classList.remove('active');
            form.reset();
        }

        function editCategory(categoryId) {
            openCategoryModal(categoryId);
        }
        async function loadCategory(id) {
            try {
                const response = await fetch(`/api/categories.php?id=${id}`);
                if (!response.ok) {
                    throw new Error('Failed to load category');
                }
                const category = await response.json();
                
                document.getElementById('categoryId').value = category.id;
                document.getElementById('name').value = category.name;
                document.getElementById('icon').value = category.icon;
                document.getElementById('color').value = category.color;
                document.getElementById('iconPreview').className = category.icon;
            } catch (error) {
                console.error('Error loading category:', error);
                alert(error.message || 'Failed to load category');
            }
        }

        async function handleSubmit(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = {
                name: formData.get('name'),
                icon: formData.get('icon'),
                color: formData.get('color')
            };

            const categoryId = formData.get('id');
            const method = categoryId ? 'PUT' : 'POST';
            const url = `/api/categories.php${categoryId ? `?id=${categoryId}` : ''}`;

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
                    throw new Error(error.message || 'Failed to save category');
                }

                location.reload();
            } catch (error) {
                console.error('Error saving category:', error);
                alert(error.message || 'Failed to save category');
            }
        }

        async function deleteCategory(id) {
            if (!confirm('Are you sure you want to delete this category? Items in this category will be uncategorized.')) {
                return;
            }

            try {
                const response = await fetch(`/api/categories.php?id=${id}`, {
                    method: 'DELETE'
                });

                if (!response.ok) {
                    throw new Error('Failed to delete category');
                }

                location.reload();
            } catch (error) {
                console.error('Error deleting category:', error);
                alert(error.message || 'Failed to delete category');
            }
        }
    </script>
</body>
</html>
