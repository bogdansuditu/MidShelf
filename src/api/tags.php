<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../models/Tag.php';

header('Content-Type: application/json');

$auth = new Auth();

// Check if user is authenticated
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $auth->getCurrentUserId();
$tagModel = new Tag();

// Get search term if provided
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Get tags for the current user
if (!empty($search)) {
    $tags = $tagModel->searchTags($userId, $search);
} else {
    $tags = $tagModel->getTags($userId);
}

echo json_encode($tags);
