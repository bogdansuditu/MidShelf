<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';

$auth = new Auth();

// Ensure user is authenticated
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $auth->getCurrentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'export') {
    try {
        $db = Database::getInstance();
        $data = [
            'schema_version' => '1.0',
            'export_date' => date('Y-m-d H:i:s'),
            'tables' => []
        ];

        // Export all tables
        $tables = ['users', 'categories', 'locations', 'tags', 'items', 'items_tags', 'user_settings'];
        
        foreach ($tables as $table) {
            $stmt = $db->query("SELECT * FROM $table", []);
            $data['tables'][$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="midshelf_backup_' . date('Y-m-d_His') . '.json"');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Export failed: ' . $e->getMessage()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'import') {
    try {
        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error');
        }

        $content = file_get_contents($_FILES['backup_file']['tmp_name']);
        $data = json_decode($content, true);

        if (!$data || !isset($data['schema_version']) || !isset($data['tables'])) {
            throw new Exception('Invalid backup file format');
        }

        $db = Database::getInstance();
        $db->getConnection()->beginTransaction();

        // Drop all existing data
        $tables = ['items_tags', 'user_settings', 'items', 'tags', 'locations', 'categories', 'users'];
        foreach ($tables as $table) {
            $db->query("DELETE FROM $table", []);
            $db->query("DELETE FROM sqlite_sequence WHERE name = ?", [$table]);
        }

        // Import data in correct order
        $importOrder = ['users', 'categories', 'locations', 'tags', 'items', 'items_tags', 'user_settings'];
        
        foreach ($importOrder as $table) {
            if (!empty($data['tables'][$table])) {
                foreach ($data['tables'][$table] as $row) {
                    $columns = implode(', ', array_keys($row));
                    $values = implode(', ', array_fill(0, count($row), '?'));
                    $sql = "INSERT INTO $table ($columns) VALUES ($values)";
                    $db->query($sql, array_values($row));
                }
            }
        }

        $db->getConnection()->commit();
        
        // Destroy the session since user data might have changed
        session_start();
        session_destroy();
        
        echo json_encode(['success' => true, 'message' => 'Database restored successfully']);
    } catch (Exception $e) {
        if ($db->getConnection()->inTransaction()) {
            $db->getConnection()->rollBack();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Import failed: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request']);
