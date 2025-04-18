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

$userId = $auth->getCurrentUserId();

// --- Start: Delete existing data before import ---
$itemModel = new Item();
$categoryModel = new Category();
$locationModel = new Location();
$tagModel = new Tag();

$_SESSION['import_info'] = ''; // Initialize info message

try {
    $_SESSION['import_info'] .= "Attempting to delete existing data...<br>";
    // Delete in order: Items (handles items_tags), Tags, Categories, Locations
    $deletedItems = $itemModel->deleteAllItemsForUser($userId);
    $deletedTags = $tagModel->deleteAllTagsForUser($userId);
    $deletedCats = $categoryModel->deleteAllCategoriesForUser($userId);
    $deletedLocs = $locationModel->deleteAllLocationsForUser($userId);
    $_SESSION['import_info'] .= "Existing data deleted (Items: {$deletedItems}, Tags: {$deletedTags}, Categories: {$deletedCats}, Locations: {$deletedLocs}).<br>Starting import...<br>";

} catch (Exception $e) {
    // If deletion fails, stop the import and report error
    error_log("Error deleting data before import for user {$userId}: " . $e->getMessage());
    $_SESSION['settings_error'] = "Critical error: Failed to delete existing data before import. Import cancelled. Error: " . $e->getMessage();
    header('Location: /settings.php');
    exit;
}
// --- End: Delete existing data before import ---

// Check if file was uploaded
if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] == UPLOAD_ERR_OK) {
    
    $fileTmpPath = $_FILES['import_file']['tmp_name'];
    $fileName = $_FILES['import_file']['name'];
    $fileSize = $_FILES['import_file']['size'];
    $fileType = $_FILES['import_file']['type'];
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));

    $allowedfileExtensions = ['csv'];
    if (in_array($fileExtension, $allowedfileExtensions)) {
        
        // Reuse model instances from deletion phase
        $itemsImported = 0;
        $errors = [];
        $rowNumber = 0;

        // Open the CSV file
        if (($handle = fopen($fileTmpPath, 'r')) !== FALSE) {
            // Skip header row
            $header = fgetcsv($handle);
            $rowNumber++;

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rowNumber++;
                // Expected columns: name, description, category_name, location_name, rating, tags, created_at, updated_at
                if (count($data) >= 6) { // Check minimum required columns (ignore timestamps for now)
                    $itemName = trim($data[0]);
                    $itemDescription = trim($data[1]);
                    $categoryName = trim($data[2]);
                    $locationName = trim($data[3]);
                    $rating = !empty(trim($data[4])) ? (int)trim($data[4]) : 0;
                    $tagsString = trim($data[5]);

                    // Basic validation: Item name is required
                    if (empty($itemName)) {
                        $errors[] = "Row {$rowNumber}: Item name is missing.";
                        continue;
                    }

                    try {
                        // Get or create Category ID
                        $categoryId = !empty($categoryName) ? $categoryModel->getCategoryByNameOrCreate($categoryName, $userId) : null;

                        // Get or create Location ID
                        $locationId = !empty($locationName) ? $locationModel->getLocationByNameOrCreate($locationName, $userId) : null;

                        // Prepare tags array
                        $tagsArray = [];
                        if (!empty($tagsString)) {
                            $tagNames = explode(',', $tagsString);
                            foreach ($tagNames as $tagName) {
                                $trimmedTagName = trim($tagName);
                                if (!empty($trimmedTagName)) {
                                    $tagsArray[] = $trimmedTagName; // createItem handles finding/creating tags
                                }
                            }
                        }
                        
                        // Prepare data for createItem
                        $itemData = [
                            'user_id' => $userId,
                            'name' => $itemName,
                            'description' => $itemDescription,
                            'category_id' => $categoryId,
                            'location_id' => $locationId,
                            'rating' => $rating,
                            'tags' => $tagsArray
                            // created_at and updated_at will be set by DB/createItem
                        ];
                        
                        // Create the item
                        $itemId = $itemModel->createItem($itemData);
                        if ($itemId) {
                            $itemsImported++;
                        } else {
                             $errors[] = "Row {$rowNumber}: Failed to create item '{$itemName}'.";
                        }
                    } catch (Exception $e) {
                        $errors[] = "Row {$rowNumber}: Error processing item '{$itemName}' - " . $e->getMessage();
                    }
                } else {
                    $errors[] = "Row {$rowNumber}: Incorrect number of columns.";
                }
            }
            fclose($handle);

            // Set feedback message
            if ($itemsImported > 0) {
                $_SESSION['settings_success'] = "Successfully imported {$itemsImported} items.";
            }
            if (!empty($errors)) {
                $_SESSION['settings_error'] = "Import completed with errors: <br>" . implode('<br>', $errors);
            } elseif ($itemsImported == 0) {
                 $_SESSION['settings_error'] = "No items were imported. Please check the CSV file format and content.";
            }

        } else {
            $_SESSION['settings_error'] = "Error reading the uploaded CSV file.";
        }
    } else {
        $_SESSION['settings_error'] = "Upload failed. Allowed file types: " . implode(', ', $allowedfileExtensions);
    }
} else {
    $_SESSION['settings_error'] = "No file uploaded or upload error occurred.";
    if (isset($_FILES['import_file']['error'])) {
         $_SESSION['settings_error'] .= " Error code: " . $_FILES['import_file']['error'];
    }
}

// Append deletion/import info to the success message if applicable
if (isset($_SESSION['settings_success']) && !empty($_SESSION['import_info'])) {
    $_SESSION['settings_success'] = $_SESSION['import_info'] . $_SESSION['settings_success'];
} elseif (!isset($_SESSION['settings_success']) && !empty($_SESSION['import_info']) && empty($errors)) {
    // Handle case where deletion occurred but maybe no rows were in the CSV to import
     $_SESSION['settings_success'] = $_SESSION['import_info'] . "No new items were imported (file might be empty or only contain header).";
} else {
    // Prepend info to error message if success message isn't set
     $_SESSION['settings_error'] = ($_SESSION['import_info'] ?? '') . ($_SESSION['settings_error'] ?? 'An unknown import error occurred.');
}
unset($_SESSION['import_info']); // Clean up temporary info message

// Redirect back to settings page
header('Location: /settings.php');
exit;
