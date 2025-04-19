<?php
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function login($username, $password) {
        try {
            // Fetch user details
            $stmtUser = $this->db->query(
                "SELECT id, username, password_hash FROM users WHERE username = ?",
                [$username]
            );
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Update last login
                $this->db->query(
                    "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?",
                    [$user['id']]
                );

                // Fetch user settings
                $stmtSettings = $this->db->query(
                    "SELECT setting_key, setting_value FROM user_settings WHERE user_id = ?",
                    [$user['id']]
                );
                $settingsRaw = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR); // Get key-value pairs

                // Define default settings
                $defaultSettings = [
                    'accent_color' => '#8b5cf6', // Default purple
                    'skip_item_delete_confirm' => false
                ];

                // Merge fetched settings with defaults (fetched overwrite defaults)
                $userSettings = array_merge($defaultSettings, $settingsRaw);

                // Ensure boolean conversion for the toggle setting
                $userSettings['skip_item_delete_confirm'] = filter_var($userSettings['skip_item_delete_confirm'], FILTER_VALIDATE_BOOLEAN);

                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['last_activity'] = time();
                $_SESSION['user_settings'] = $userSettings; // Store combined settings

                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Login failed: " . $e->getMessage());
            return false;
        }
    }

    public function logout() {
        session_destroy();
    }

    public function isLoggedIn() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
            return false;
        }

        // Check session expiration
        if (time() - $_SESSION['last_activity'] > SESSION_DURATION) {
            $this->logout();
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    public function getCurrentUsername() {
        return $_SESSION['username'] ?? null;
    }
}
