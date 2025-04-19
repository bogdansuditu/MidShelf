<?php
// src/api/settings.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

$auth = new Auth();
$db = Database::getInstance();

// --- Authentication Check ---
if (!$auth->isLoggedIn()) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

// --- Request Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Only POST is accepted.']);
    exit;
}

// --- Input Handling ---
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($input['setting_key']) || !isset($input['setting_value'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload or missing keys. Expected: {"setting_key": "...", "setting_value": "..."}']);
    exit;
}

$userId = $auth->getCurrentUserId();
$key = trim($input['setting_key']);
$value = $input['setting_value']; // Keep original type for validation

// --- Input Validation ---
$allowedKeys = ['accent_color', 'skip_item_delete_confirm'];
if (!in_array($key, $allowedKeys)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid setting key.']);
    exit;
}

// Validate value based on key
$validatedValue = null;
switch ($key) {
    case 'accent_color':
        // Basic hex color validation (#rrggbb)
        if (preg_match('/^#[a-fA-F0-9]{6}$/', $value)) {
            $validatedValue = $value;
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid accent color format. Expected #rrggbb.']);
            exit;
        }
        break;
    case 'skip_item_delete_confirm':
        // Convert to boolean, then store as 0 or 1
        $boolValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($boolValue === null) {
             http_response_code(400);
             echo json_encode(['success' => false, 'message' => 'Invalid value for skip_item_delete_confirm. Expected true or false.']);
             exit;
        }
         $validatedValue = $boolValue ? 1 : 0; // Store as integer
         // Update session boolean value immediately
         $_SESSION['user_settings'][$key] = $boolValue;
        break;
}


// --- Database Update (UPSERT) ---
try {
    // INSERT OR REPLACE handles both new settings and updates
    $stmt = $db->query(
        "INSERT OR REPLACE INTO user_settings (user_id, setting_key, setting_value) VALUES (?, ?, ?)",
        [$userId, $key, (string)$validatedValue] // Ensure value is string for DB
    );

    // Update the session value if it wasn't the boolean one updated earlier
    if ($key !== 'skip_item_delete_confirm') {
        $_SESSION['user_settings'][$key] = $validatedValue;
    }


    http_response_code(200); // OK
    echo json_encode(['success' => true, 'message' => 'Setting updated successfully.']);

} catch (Exception $e) {
    error_log("Setting update failed for user {$userId}, key {$key}: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Failed to update setting due to a server error.']);
}

exit; // End script
