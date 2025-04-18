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

// Double-check if the request method is POST for safety
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['settings_error'] = 'Invalid request method for deleting data.';
    header('Location: /settings.php');
    exit;
}

// Optional: Add CSRF token check here for better security

$userId = $auth->getCurrentUserId();

$itemModel = new Item();
$categoryModel = new Category();
$locationModel = new Location();
$tagModel = new Tag();

$success = true;
$errors = [];

try {
    // Delete in order: Items (handles items_tags), Tags, Categories, Locations
    
    // 1. Delete Items and their Tag associations
    $itemModel->deleteAllItemsForUser($userId);
    
    // 2. Delete Tags created by the user
    $tagModel->deleteAllTagsForUser($userId);

    // 3. Delete Categories created by the user
    $categoryModel->deleteAllCategoriesForUser($userId);

    // 4. Delete Locations created by the user
    $locationModel->deleteAllLocationsForUser($userId);

} catch (Exception $e) {
    $success = false;
    $errors[] = "An error occurred during deletion: " . $e->getMessage();
    // Log the detailed error for debugging
    error_log("Error deleting all data for user {$userId}: " . $e->getMessage());
}

if ($success) {
    $_SESSION['settings_success'] = "All your data (items, categories, locations, tags) has been successfully deleted.";
} else {
    $_SESSION['settings_error'] = "Failed to delete all data. Errors: <br>" . implode('<br>', $errors);
}

// Redirect back to settings page
header('Location: /settings.php');
exit;
