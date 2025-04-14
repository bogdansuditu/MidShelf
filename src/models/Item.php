<?php
require_once __DIR__ . '/../config/database.php';

class Item {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getItems($userId, $categoryId = null) {
        $sql = "
            SELECT 
                i.*,
                c.name as category_name,
                c.icon as category_icon,
                c.color as category_color,
                l.name as location_name,
                GROUP_CONCAT(t.name) as tags
            FROM items i
            LEFT JOIN categories c ON i.category_id = c.id
            LEFT JOIN locations l ON i.location_id = l.id
            LEFT JOIN items_tags it ON i.id = it.item_id
            LEFT JOIN tags t ON it.tag_id = t.id
            WHERE i.user_id = ?
        ";
        $params = [$userId];

        if ($categoryId) {
            $sql .= " AND i.category_id = ?";
            $params[] = $categoryId;
        }

        $sql .= " GROUP BY i.id ORDER BY i.created_at DESC";

        try {
            $items = $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert tags string to array
            foreach ($items as &$item) {
                $item['tags'] = $item['tags'] ? explode(',', $item['tags']) : [];
            }
            
            return $items;
        } catch (Exception $e) {
            error_log("Error fetching items: " . $e->getMessage());
            return [];
        }
    }

    public function getItem($id, $userId) {
        $sql = "
            SELECT 
                i.*,
                c.name as category_name,
                c.icon as category_icon,
                c.color as category_color,
                l.name as location_name,
                GROUP_CONCAT(t.name) as tags
            FROM items i
            LEFT JOIN categories c ON i.category_id = c.id
            LEFT JOIN locations l ON i.location_id = l.id
            LEFT JOIN items_tags it ON i.id = it.item_id
            LEFT JOIN tags t ON it.tag_id = t.id
            WHERE i.id = ? AND i.user_id = ?
            GROUP BY i.id
        ";

        try {
            $item = $this->db->query($sql, [$id, $userId])->fetch(PDO::FETCH_ASSOC);
            if ($item) {
                $item['tags'] = $item['tags'] ? explode(',', $item['tags']) : [];
            }
            return $item;
        } catch (Exception $e) {
            error_log("Error fetching item: " . $e->getMessage());
            return null;
        }
    }

    public function createItem($data) {
        try {
            $this->db->getConnection()->beginTransaction();

            // Insert item
            $sql = "
                INSERT INTO items (
                    user_id, name, description, category_id, 
                    location_id, rating, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ";
            
            $stmt = $this->db->query($sql, [
                $data['user_id'],
                $data['name'],
                $data['description'],
                $data['category_id'],
                $data['location_id'],
                $data['rating']
            ]);

            $itemId = $this->db->getConnection()->lastInsertId();

            // Handle tags
            if (!empty($data['tags'])) {
                foreach ($data['tags'] as $tag) {
                    // First try to find existing tag
                    $existingTag = $this->db->query(
                        "SELECT id FROM tags WHERE user_id = ? AND name = ?",
                        [$data['user_id'], $tag]
                    )->fetch(PDO::FETCH_ASSOC);

                    $tagId = null;
                    if ($existingTag) {
                        $tagId = $existingTag['id'];
                    } else {
                        // Create new tag
                        $this->db->query(
                            "INSERT INTO tags (user_id, name) VALUES (?, ?)",
                            [$data['user_id'], $tag]
                        );
                        $tagId = $this->db->getConnection()->lastInsertId();
                    }

                    // Link tag to item
                    $this->db->query(
                        "INSERT INTO items_tags (item_id, tag_id) VALUES (?, ?)",
                        [$itemId, $tagId]
                    );
                }
            }

            $this->db->getConnection()->commit();
            return $itemId;
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            error_log("Error creating item: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateItem($id, $data) {
        try {
            $this->db->getConnection()->beginTransaction();

            // Update item
            $sql = "
                UPDATE items 
                SET name = ?, description = ?, category_id = ?,
                    location_id = ?, rating = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?
            ";
            
            $this->db->query($sql, [
                $data['name'],
                $data['description'],
                $data['category_id'],
                $data['location_id'],
                $data['rating'],
                $id,
                $data['user_id']
            ]);

            // Remove existing tags
            $this->db->query(
                "DELETE FROM items_tags WHERE item_id = ?",
                [$id]
            );

            // Add new tags
            if (!empty($data['tags'])) {
                foreach ($data['tags'] as $tag) {
                    // First try to find existing tag
                    $existingTag = $this->db->query(
                        "SELECT id FROM tags WHERE user_id = ? AND name = ?",
                        [$data['user_id'], $tag]
                    )->fetch(PDO::FETCH_ASSOC);

                    $tagId = null;
                    if ($existingTag) {
                        $tagId = $existingTag['id'];
                    } else {
                        // Create new tag
                        $this->db->query(
                            "INSERT INTO tags (user_id, name) VALUES (?, ?)",
                            [$data['user_id'], $tag]
                        );
                        $tagId = $this->db->getConnection()->lastInsertId();
                    }

                    // Link tag to item
                    $this->db->query(
                        "INSERT INTO items_tags (item_id, tag_id) VALUES (?, ?)",
                        [$id, $tagId]
                    );
                }
            }

            $this->db->getConnection()->commit();
            return true;
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            error_log("Error updating item: " . $e->getMessage());
            throw $e;
        }
    }

    public function deleteItem($id, $userId) {
        try {
            $this->db->getConnection()->beginTransaction();

            // Remove tags associations
            $this->db->query(
                "DELETE FROM items_tags WHERE item_id = ?",
                [$id]
            );

            // Delete item
            $this->db->query(
                "DELETE FROM items WHERE id = ? AND user_id = ?",
                [$id, $userId]
            );

            $this->db->getConnection()->commit();
            return true;
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            error_log("Error deleting item: " . $e->getMessage());
            throw $e;
        }
    }
}
