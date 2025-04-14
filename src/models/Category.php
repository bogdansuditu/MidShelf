<?php
require_once __DIR__ . '/../config/database.php';

class Category {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getCategories($userId) {
        $sql = "SELECT * FROM categories WHERE user_id = ? ORDER BY name ASC";
        try {
            return $this->db->query($sql, [$userId])->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching categories: " . $e->getMessage());
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
}
