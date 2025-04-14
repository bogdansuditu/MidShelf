<?php
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function login($username, $password) {
        try {
            $stmt = $this->db->query(
                "SELECT id, username, password_hash FROM users WHERE username = ?",
                [$username]
            );
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Update last login
                $this->db->query(
                    "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?",
                    [$user['id']]
                );

                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['last_activity'] = time();
                
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
