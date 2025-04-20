<?php
require_once __DIR__ . '/../config/database.php';

class Category {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getCategories($userId, $withCounts = false) {
        if (!$withCounts) {
            $sql = "SELECT * FROM categories WHERE user_id = ? ORDER BY name ASC";
            try {
                return $this->db->query($sql, [$userId])->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Error fetching categories: " . $e->getMessage());
                return [];
            }
        }

        $sql = "SELECT c.*, COUNT(i.id) as item_count 
               FROM categories c 
               LEFT JOIN items i ON c.id = i.category_id 
               WHERE c.user_id = ? 
               GROUP BY c.id 
               ORDER BY c.name ASC";
        try {
            return $this->db->query($sql, [$userId])->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching categories with counts: " . $e->getMessage());
            return [];
        }
    }

    public function getCategory($id, $userId) {
        $sql = "SELECT * FROM categories WHERE id = ? AND user_id = ?";
        try {
            return $this->db->query($sql, [$id, $userId])->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching category: " . $e->getMessage());
            return null;
        }
    }

    public function createCategory($data) {
        $sql = "INSERT INTO categories (user_id, name, icon, color) VALUES (?, ?, ?, ?)";
        try {
            $this->db->query($sql, [
                $data['user_id'],
                $data['name'],
                $data['icon'],
                $data['color']
            ]);
            return $this->db->getConnection()->lastInsertId();
        } catch (Exception $e) {
            error_log("Error creating category: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateCategory($id, $data) {
        $sql = "UPDATE categories SET name = ?, icon = ?, color = ? WHERE id = ? AND user_id = ?";
        try {
            $this->db->query($sql, [
                $data['name'],
                $data['icon'],
                $data['color'],
                $id,
                $data['user_id']
            ]);
            return true;
        } catch (Exception $e) {
            error_log("Error updating category: " . $e->getMessage());
            throw $e;
        }
    }

    public function deleteCategory($id, $userId) {
        try {
            $this->db->getConnection()->beginTransaction();

            // Update items to remove category
            $this->db->query(
                "UPDATE items SET category_id = NULL WHERE category_id = ? AND user_id = ?",
                [$id, $userId]
            );

            // Delete category
            $this->db->query(
                "DELETE FROM categories WHERE id = ? AND user_id = ?",
                [$id, $userId]
            );

            $this->db->getConnection()->commit();
            return true;
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            error_log("Error deleting category: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Finds a category by name for a specific user, or creates it if it doesn't exist.
     * Returns the category ID.
     */
    public function getCategoryByNameOrCreate($name, $userId) {
        // Trim the name to avoid issues with whitespace
        $trimmedName = trim($name);
        if (empty($trimmedName)) {
            return null; // Cannot process empty category name
        }

        // Check if category exists
        $sql_find = "SELECT id FROM categories WHERE user_id = ? AND LOWER(name) = LOWER(?)";
        try {
            $category = $this->db->query($sql_find, [$userId, $trimmedName])->fetch(PDO::FETCH_ASSOC);
            
            if ($category) {
                return $category['id']; // Return existing category ID
            }

            // Category doesn't exist, create it
            $default_icon = 'fas fa-folder'; // Default icon
            $default_color = '#cccccc';      // Default color
            
            $sql_create = "INSERT INTO categories (user_id, name, icon, color) VALUES (?, ?, ?, ?)";
            $this->db->query($sql_create, [
                $userId,
                $trimmedName, // Use the trimmed name
                $default_icon,
                $default_color
            ]);
            return $this->db->getConnection()->lastInsertId(); // Return new category ID
        } catch (Exception $e) {
            error_log("Error finding or creating category '{$trimmedName}': " . $e->getMessage());
            throw $e; // Re-throw the exception to be handled by the importer
        }
    }

    /**
     * Deletes all categories for a specific user.
     * Assumes items using these categories have already been handled (deleted or category_id set to NULL).
     */
    public function deleteAllCategoriesForUser($userId) {
        $sql = "DELETE FROM categories WHERE user_id = ?";
        try {
            $stmt = $this->db->query($sql, [$userId]);
            return $stmt->rowCount(); // Return number of deleted rows
        } catch (Exception $e) {
            error_log("Error deleting all categories for user {$userId}: " . $e->getMessage());
            throw $e;
        }
    }
}
