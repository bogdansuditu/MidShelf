<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../models/Category.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $auth->getCurrentUserId();
$categoryModel = new Category();

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $category = $categoryModel->getCategory($_GET['id'], $userId);
                if ($category) {
                    echo json_encode($category);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Category not found']);
                }
            } else {
                $categories = $categoryModel->getCategories($userId);
                echo json_encode($categories);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid request data']);
                break;
            }

            $data['user_id'] = $userId;
            $categoryId = $categoryModel->createCategory($data);
            
            http_response_code(201);
            echo json_encode(['id' => $categoryId]);
            break;

        case 'PUT':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Category ID is required']);
                break;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid request data']);
                break;
            }

            $data['user_id'] = $userId;
            $success = $categoryModel->updateCategory($_GET['id'], $data);
            
            if ($success) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Category not found']);
            }
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Category ID is required']);
                break;
            }

            $success = $categoryModel->deleteCategory($_GET['id'], $userId);
            
            if ($success) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Category not found']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
