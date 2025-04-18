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

$itemModel = new Item();

// Fetch items for export
$items = $itemModel->getItemsForExport($userId);

// Set headers for CSV download
$filename = "midshelf_export_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Open output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM if needed (for Excel compatibility)
fputs($output, "\xEF\xBB\xBF"); 

// Write CSV header
$header = ['name', 'description', 'category_name', 'location_name', 'rating', 'tags', 'created_at', 'updated_at'];
fputcsv($output, $header);

// Write item rows
if (!empty($items)) {
    foreach ($items as $item) {
        // Ensure all expected keys exist, even if null, to match header order

        // Clean up tags string: split, trim each tag, rejoin
        $tagsString = $item['tags'] ?? '';
        $trimmedTagsArray = array_filter(array_map('trim', explode(',', $tagsString)));
        $cleanedTagsString = implode(',', $trimmedTagsArray); // Use a simple comma

        $row = [
            $item['name'] ?? '',
            $item['description'] ?? '',
            $item['category_name'] ?? '',
            $item['location_name'] ?? '',
            $item['rating'] ?? 0,
            $cleanedTagsString, // Use cleaned tags string
            $item['created_at'] ?? '',
            $item['updated_at'] ?? ''
        ];
        fputcsv($output, $row);
    }
}

fclose($output);
exit;
