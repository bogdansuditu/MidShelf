/**
 * MidShelf - Common JavaScript functionality
 */

// Initialize sidebar functionality
function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const container = document.querySelector('.container');
    const toggleBtn = document.getElementById('toggleSidebar');
    const html = document.documentElement;
    
    // Apply sidebar state (this is now redundant with our inline script, but kept for robustness)
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        sidebar.classList.add('collapsed');
        container.classList.add('sidebar-collapsed');
        html.classList.add('sidebar-collapsed-root');
    }
    
    // Toggle sidebar when button is clicked
    toggleBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        sidebar.classList.toggle('collapsed');
        container.classList.toggle('sidebar-collapsed');
        html.classList.toggle('sidebar-collapsed-root');
        
        // Save state to localStorage
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
    });
}

// Load categories into sidebar
async function loadSidebarCategories() {
    try {
        const response = await fetch('/api/categories.php');
        const categories = await response.json();
        
        const categoriesContainer = document.querySelector('.sidebar-categories');
        if (!categoriesContainer) return;
        
        if (categories.length === 0) {
            categoriesContainer.innerHTML = '';
            return;
        }

        // Clear existing categories
        categoriesContainer.innerHTML = '';

        // Get current category from URL if any
        const urlParams = new URLSearchParams(window.location.search);
        const currentCategoryId = urlParams.get('category');
        
        categories.forEach(category => {
            const item = document.createElement('a');
            item.href = `/items.php?category=${category.id}`;
            item.className = 'sidebar-item';
            
            // Mark as active if this is the current category
            if (currentCategoryId && currentCategoryId == category.id) {
                item.classList.add('active');
            }
            
            item.innerHTML = `
                <i class="${category.icon}"></i>
                <span>${category.name}</span>
            `;
            item.style.setProperty('--category-color', category.color);
            categoriesContainer.appendChild(item);
        });
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

// Initialize the application
document.addEventListener('DOMContentLoaded', () => {
    // Initialize sidebar
    initSidebar();
    
    // Load categories in sidebar
    loadSidebarCategories();
    
    // If we're on a category page, load the category name
    const urlParams = new URLSearchParams(window.location.search);
    const categoryId = urlParams.get('category');
    if (categoryId) {
        fetchCategoryName(categoryId);
    }
});

// Fetch category name for category pages
async function fetchCategoryName(categoryId) {
    try {
        const response = await fetch(`/api/categories.php?id=${categoryId}`);
        const category = await response.json();
        
        if (category && category.name) {
            const categoryNameElement = document.querySelector('.category-name');
            if (categoryNameElement) {
                categoryNameElement.textContent = category.name;
            }
        }
    } catch (error) {
        console.error('Error fetching category name:', error);
    }
}

// Delete item function
async function deleteItem(id) {
    if (!confirm('Are you sure you want to delete this item?')) {
        return;
    }

    try {
        const response = await fetch(`/api/items.php?id=${id}`, {
            method: 'DELETE'
        });

        if (response.ok) {
            location.reload();
        } else {
            alert('Failed to delete item');
        }
    } catch (error) {
        console.error('Error deleting item:', error);
        alert('Failed to delete item');
    }
}

// Edit item function
function editItem(id) {
    location.href = `/items.php?action=edit&id=${id}`;
}
