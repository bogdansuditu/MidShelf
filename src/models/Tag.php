<?php
require_once __DIR__ . '/../config/database.php';

class Tag {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all tags for a specific user
     * 
     * @param int $userId The user ID
     * @return array Array of tags
     */
    public function getTags($userId) {
        $query = "
            SELECT DISTINCT t.id, t.name
            FROM tags t
            JOIN items_tags it ON t.id = it.tag_id
            JOIN items i ON it.item_id = i.id
            WHERE i.user_id = ?
            ORDER BY t.name ASC
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Search tags by name (for autocomplete)
     * 
     * @param int $userId The user ID
     * @param string $search Search term
     * @return array Array of matching tags
     */
    public function searchTags($userId, $search = '') {
        $query = "
            SELECT DISTINCT t.id, t.name
            FROM tags t
            JOIN items_tags it ON t.id = it.tag_id
            JOIN items i ON it.item_id = i.id
            WHERE i.user_id = ?
            AND t.name LIKE ?
            ORDER BY t.name ASC
            LIMIT 10
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, "%$search%"]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
