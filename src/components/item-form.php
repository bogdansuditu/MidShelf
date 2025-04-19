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

    <div class="form-row">
        <div class="form-group form-group-half">
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

        <div class="form-group form-group-half">
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
        <div class="rating-input rating" id="ratingInput">
            <?php
            // Add the 'rating' class here to ensure existing CSS rules apply
            $current_rating = $item ? (int)$item['rating'] : 0;
            for ($i = 1; $i <= 5; $i++):
                // Determine the correct Font Awesome class for the icon
                $icon_class = ($i <= $current_rating) ? 'fas fa-star' : 'far fa-star';
                // Keep the active class on the button in case JS uses it
                $button_class = ($i <= $current_rating) ? 'active' : '';
            ?>
                <button type="button" class="<?php echo $button_class; ?>"
                        onclick="setRating(<?php echo $i; ?>)">
                    <i class="<?php echo $icon_class; ?>"></i>
                </button>
            <?php endfor; ?>
        </div>
        <input type="hidden" name="rating" id="ratingHidden" value="<?php echo $current_rating; ?>">
    </div>

    <div class="form-actions">
        <button type="button" id="cancelItemForm" class="btn btn-secondary">Cancel</button>
        <button type="submit" class="btn btn-primary">
            <?php echo $item ? 'Update Item' : 'Add Item'; ?>
        </button>
    </div>
</form>
