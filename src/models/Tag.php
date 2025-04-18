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
    
    /**
     * Finds a tag by name for a specific user, or creates it if it doesn't exist.
     * Returns the tag ID.
     */
    public function getTagByNameOrCreate($name, $userId) {
        // Trim the name
        $trimmedName = trim($name);
        if (empty($trimmedName)) {
            return null; // Cannot process empty tag name
        }

        // Check if tag exists for the user
        // Note: Tags might not be strictly user-specific in the current schema? 
        // Assuming we check/create based on name globally but associate via items_tags later.
        // Let's find based on name first.
        $sql_find = "SELECT id FROM tags WHERE LOWER(name) = LOWER(?)";
        try {
            $stmt_find = $this->db->prepare($sql_find);
            $stmt_find->execute([$trimmedName]);
            $tag = $stmt_find->fetch(PDO::FETCH_ASSOC);
            
            if ($tag) {
                return $tag['id']; // Return existing tag ID
            }

            // Tag doesn't exist, create it
            // Associate the user_id when creating the tag
            $sql_create = "INSERT INTO tags (user_id, name) VALUES (?, ?)";
            $stmt_create = $this->db->prepare($sql_create);
            $stmt_create->execute([
                $userId, 
                $trimmedName // Use the trimmed name
            ]);
            return $this->db->lastInsertId(); // Return new tag ID
        } catch (Exception $e) {
            error_log("Error finding or creating tag '{$trimmedName}': " . $e->getMessage());
            throw $e; // Re-throw the exception
        }
    }

    /**
     * Deletes all tags created by a specific user.
     * Assumes items_tags links have already been removed.
     * Note: This might delete tags that are technically unused but were created by the user.
     */
    public function deleteAllTagsForUser($userId) {
        // We only delete tags explicitly associated with the user_id.
        // If tags were shared across users (no user_id column), this logic would be different.
        $sql = "DELETE FROM tags WHERE user_id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->rowCount(); // Return number of deleted rows
        } catch (Exception $e) {
            error_log("Error deleting all tags for user {$userId}: " . $e->getMessage());
            throw $e;
        }
    }
}
