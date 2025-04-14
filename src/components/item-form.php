<?php
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Location.php';

$itemId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$item = null;

if ($itemId) {
    $itemModel = new Item();
    $item = $itemModel->getItem($itemId, $userId);
    if (!$item) {
        header('Location: /items.php');
        exit;
    }
}

// Get categories and locations for dropdowns
$categoryModel = new Category();
$categories = $categoryModel->getCategories($userId);

$locationModel = new Location();
$locations = $locationModel->getLocations($userId);
?>

<form id="itemForm" class="item-form" onsubmit="handleSubmit(event)">
    <input type="hidden" name="id" value="<?php echo $itemId; ?>">
    
    <div class="form-group">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" required
               value="<?php echo $item ? htmlspecialchars($item['name']) : ''; ?>"
               placeholder="Enter item name">
    </div>

    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4"
                  placeholder="Enter item description"><?php echo $item ? htmlspecialchars($item['description']) : ''; ?></textarea>
    </div>

    <div class="form-group">
        <label for="category">Category</label>
        <select id="category" name="category_id">
            <option value="">Select a category</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category['id']; ?>"
                        <?php echo $item && $item['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="location">Location</label>
        <select id="location" name="location_id">
            <option value="">Select a location</option>
            <?php foreach ($locations as $location): ?>
                <option value="<?php echo $location['id']; ?>"
                        <?php echo $item && $item['location_id'] == $location['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($location['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="tags">Tags</label>
        <div class="tags-input" id="tagsInput">
            <?php if ($item && !empty($item['tags'])): ?>
                <?php foreach ($item['tags'] as $tag): ?>
                    <span class="tag">
                        <?php echo htmlspecialchars($tag); ?>
                        <button type="button" onclick="removeTag(this)">×</button>
                    </span>
                <?php endforeach; ?>
            <?php endif; ?>
            <input type="text" id="tagInput" placeholder="Add tags or select from suggestions">
            <div id="tagSuggestions" class="tag-suggestions"></div>
        </div>
        <input type="hidden" name="tags" id="tagsHidden" value="<?php echo $item ? implode(',', $item['tags']) : ''; ?>">
    </div>

    <div class="form-group">
        <label for="rating">Rating</label>
        <div class="rating-input" id="ratingInput">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <button type="button" class="<?php echo $item && $item['rating'] >= $i ? 'active' : ''; ?>"
                        onclick="setRating(<?php echo $i; ?>)">
                    <i class="fas fa-star"></i>
                </button>
            <?php endfor; ?>
        </div>
        <input type="hidden" name="rating" id="ratingHidden" value="<?php echo $item ? $item['rating'] : '0'; ?>">
    </div>

    <div class="form-actions">
        <button type="button" class="btn" onclick="location.href='/items.php'">Cancel</button>
        <button type="submit" class="btn btn-primary">
            <?php echo $item ? 'Update Item' : 'Add Item'; ?>
        </button>
    </div>
</form>

<script>
    // Handle tags input with autocomplete
    const tagsInput = document.getElementById('tagsInput');
    const tagInput = document.getElementById('tagInput');
    const tagsHidden = document.getElementById('tagsHidden');
    const tagSuggestions = document.getElementById('tagSuggestions');
    let currentTags = [];
    
    // Initialize current tags from hidden input
    if (tagsHidden.value) {
        currentTags = tagsHidden.value.split(',');
    }

    function updateTags() {
        const tags = Array.from(tagsInput.querySelectorAll('.tag'))
            .map(tag => tag.textContent.trim().replace('×', ''));
        tagsHidden.value = tags.join(',');
        currentTags = tags;
    }

    function addTag(value) {
        // Don't add if already exists
        if (currentTags.includes(value)) {
            return;
        }
        
        const tag = document.createElement('span');
        tag.className = 'tag';
        tag.innerHTML = `
            ${value}
            <button type="button" onclick="removeTag(this)">×</button>
        `;
        tagsInput.insertBefore(tag, tagInput);
        updateTags();
        tagInput.value = '';
        hideSuggestions();
    }

    function removeTag(button) {
        button.parentElement.remove();
        updateTags();
    }
    
    // Fetch tag suggestions
    async function fetchTagSuggestions(search) {
        try {
            const response = await fetch(`/api/tags.php?search=${encodeURIComponent(search)}`);
            if (response.ok) {
                return await response.json();
            }
            return [];
        } catch (error) {
            console.error('Error fetching tag suggestions:', error);
            return [];
        }
    }
    
    // Show tag suggestions
    function showSuggestions(suggestions) {
        tagSuggestions.innerHTML = '';
        
        if (suggestions.length === 0) {
            hideSuggestions();
            return;
        }
        
        suggestions.forEach(tag => {
            // Skip tags that are already added
            if (currentTags.includes(tag.name)) {
                return;
            }
            
            const suggestion = document.createElement('div');
            suggestion.className = 'tag-suggestion';
            suggestion.textContent = tag.name;
            suggestion.addEventListener('click', () => {
                addTag(tag.name);
            });
            tagSuggestions.appendChild(suggestion);
        });
        
        tagSuggestions.style.display = 'block';
    }
    
    // Hide suggestions
    function hideSuggestions() {
        tagSuggestions.style.display = 'none';
    }
    
    // Handle input for autocomplete
    let debounceTimer;
    tagInput.addEventListener('input', (e) => {
        const value = e.target.value.trim();
        
        clearTimeout(debounceTimer);
        
        if (value.length < 1) {
            hideSuggestions();
            return;
        }
        
        debounceTimer = setTimeout(async () => {
            const suggestions = await fetchTagSuggestions(value);
            showSuggestions(suggestions);
        }, 300);
    });
    
    // Handle keyboard navigation
    tagInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const value = e.target.value.trim();
            if (value) {
                addTag(value);
            }
        } else if (e.key === 'Escape') {
            hideSuggestions();
        }
    });
    
    // Close suggestions when clicking outside
    document.addEventListener('click', (e) => {
        if (!tagsInput.contains(e.target)) {
            hideSuggestions();
        }
    });

    // Handle rating input
    function setRating(value) {
        document.getElementById('ratingHidden').value = value;
        const stars = document.querySelectorAll('#ratingInput button');
        stars.forEach((star, index) => {
            star.classList.toggle('active', index < value);
        });
    }

    // Handle form submission
    async function handleSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const data = {
            name: formData.get('name'),
            description: formData.get('description'),
            category_id: formData.get('category_id') || null,
            location_id: formData.get('location_id') || null,
            rating: parseInt(formData.get('rating')) || 0,
            tags: formData.get('tags') ? formData.get('tags').split(',') : []
        };

        const itemId = formData.get('id');
        const method = itemId ? 'PUT' : 'POST';
        const url = `/api/items.php${itemId ? `?id=${itemId}` : ''}`;

        try {
            const response = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            if (response.ok) {
                location.href = '/items.php';
            } else {
                const error = await response.json();
                alert(error.message || 'Failed to save item');
            }
        } catch (error) {
            console.error('Error saving item:', error);
            alert('Failed to save item');
        }
    }
</script>
