<?php
require_once __DIR__ . '/../config/database.php';

class Item {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getItems($userId, $categoryId = null, $limit = null, $tag = null) {
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

        if ($tag) {
            $sql .= " AND EXISTS (SELECT 1 FROM items_tags it2 
                      JOIN tags t2 ON it2.tag_id = t2.id 
                      WHERE it2.item_id = i.id AND t2.name = ?)";
            $params[] = $tag;
        }

        $sql .= " GROUP BY i.id ORDER BY i.created_at DESC";

        if ($limit !== null) {
            $sql .= " LIMIT ?";
            $params[] = (int)$limit;
        }

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
                    location_id, link, rating, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ";
            
            // Ensure rating is between 0 and 5
            $rating = isset($data['rating']) ? intval($data['rating']) : 0;
            $rating = max(0, min(5, $rating));

            $stmt = $this->db->query($sql, [
                $data['user_id'],
                $data['name'],
                $data['description'],
                $data['category_id'],
                $data['location_id'],
                $data['link'] ?? null,
                $rating ?? 0
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
                    location_id = ?, link = ?, rating = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?
            ";
            
            $this->db->query($sql, [
                $data['name'],
                $data['description'],
                $data['category_id'],
                $data['location_id'],
                $data['link'] ?? null,
                isset($data['rating']) ? max(0, min(5, intval($data['rating']))) : 0,
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

    // Method to fetch all items with details for exporting
    public function getItemsForExport($userId) {
        $sql = "
            SELECT 
                i.name, 
                i.description, 
                c.name as category_name, 
                l.name as location_name, 
                i.link,
                i.rating, 
                GROUP_CONCAT(t.name) as tags, 
                i.created_at, 
                i.updated_at
            FROM items i
            LEFT JOIN categories c ON i.category_id = c.id
            LEFT JOIN locations l ON i.location_id = l.id
            LEFT JOIN items_tags it ON i.id = it.item_id
            LEFT JOIN tags t ON it.tag_id = t.id
            WHERE i.user_id = ?
            GROUP BY i.id
            ORDER BY i.created_at DESC
        ";
        
        try {
            // Fetch all items directly
            $items = $this->db->query($sql, [$userId])->fetchAll(PDO::FETCH_ASSOC);
            return $items;
        } catch (Exception $e) {
            error_log("Error fetching items for export: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Deletes all items and their tag associations for a specific user.
     */
    public function deleteAllItemsForUser($userId) {
        try {
            $this->db->getConnection()->beginTransaction();

            // Step 1: Find all item IDs for the user
            $sql_find_items = "SELECT id FROM items WHERE user_id = ?";
            $itemIds = $this->db->query($sql_find_items, [$userId])->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($itemIds)) {
                // Step 2: Delete associations from items_tags
                // Create placeholders for the IN clause
                $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
                $sql_delete_tags = "DELETE FROM items_tags WHERE item_id IN ({$placeholders})";
                $this->db->query($sql_delete_tags, $itemIds);

                // Step 3: Delete the items themselves
                $sql_delete_items = "DELETE FROM items WHERE user_id = ?"; // Redundant check, but safe
                $this->db->query($sql_delete_items, [$userId]);
            }

            $this->db->getConnection()->commit();
            return true;
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            error_log("Error deleting all items for user {$userId}: " . $e->getMessage());
            throw $e;
        }
    }
}
