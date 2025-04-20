<?php
require_once __DIR__ . '/../config/database.php';

class Location {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getLocations($userId, $withCounts = false) {
        if (!$withCounts) {
            $sql = "SELECT * FROM locations WHERE user_id = ? ORDER BY name ASC";
            try {
                return $this->db->query($sql, [$userId])->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Error fetching locations: " . $e->getMessage());
                return [];
            }
        }

        $sql = "SELECT l.*, COUNT(i.id) as item_count 
               FROM locations l 
               LEFT JOIN items i ON l.id = i.location_id 
               WHERE l.user_id = ? 
               GROUP BY l.id 
               ORDER BY l.name ASC";
        try {
            return $this->db->query($sql, [$userId])->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching locations with counts: " . $e->getMessage());
            return [];
        }
    }

    public function getLocation($id, $userId) {
        $sql = "SELECT * FROM locations WHERE id = ? AND user_id = ?";
        try {
            return $this->db->query($sql, [$id, $userId])->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching location: " . $e->getMessage());
            return null;
        }
    }

    public function createLocation($data) {
        $sql = "INSERT INTO locations (user_id, name, description) VALUES (?, ?, ?)";
        try {
            $this->db->query($sql, [
                $data['user_id'],
                $data['name'],
                $data['description'] ?? null
            ]);
            return $this->db->getConnection()->lastInsertId();
        } catch (Exception $e) {
            error_log("Error creating location: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateLocation($id, $data) {
        $sql = "UPDATE locations SET name = ?, description = ? WHERE id = ? AND user_id = ?";
        try {
            $this->db->query($sql, [
                $data['name'],
                $data['description'] ?? null,
                $id,
                $data['user_id']
            ]);
            return true;
        } catch (Exception $e) {
            error_log("Error updating location: " . $e->getMessage());
            throw $e;
        }
    }

    public function deleteLocation($id, $userId) {
        try {
            $this->db->getConnection()->beginTransaction();

            // Update items to remove location
            $this->db->query(
                "UPDATE items SET location_id = NULL WHERE location_id = ? AND user_id = ?",
                [$id, $userId]
            );

            // Delete location
            $this->db->query(
                "DELETE FROM locations WHERE id = ? AND user_id = ?",
                [$id, $userId]
            );

            $this->db->getConnection()->commit();
            return true;
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            error_log("Error deleting location: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Finds a location by name for a specific user, or creates it if it doesn't exist.
     * Returns the location ID.
     */
    public function getLocationByNameOrCreate($name, $userId) {
        // Trim the name
        $trimmedName = trim($name);
        if (empty($trimmedName)) {
            return null; // Cannot process empty location name
        }

        // Check if location exists
        $sql_find = "SELECT id FROM locations WHERE user_id = ? AND LOWER(name) = LOWER(?)";
        try {
            $location = $this->db->query($sql_find, [$userId, $trimmedName])->fetch(PDO::FETCH_ASSOC);
            
            if ($location) {
                return $location['id']; // Return existing location ID
            }

            // Location doesn't exist, create it with null description
            $sql_create = "INSERT INTO locations (user_id, name, description) VALUES (?, ?, NULL)";
            $this->db->query($sql_create, [
                $userId,
                $trimmedName // Use the trimmed name
            ]);
            return $this->db->getConnection()->lastInsertId(); // Return new location ID
        } catch (Exception $e) {
            error_log("Error finding or creating location '{$trimmedName}': " . $e->getMessage());
            throw $e; // Re-throw the exception
        }
    }

    /**
     * Deletes all locations for a specific user.
     * Assumes items using these locations have already been handled (deleted or location_id set to NULL).
     */
    public function deleteAllLocationsForUser($userId) {
        $sql = "DELETE FROM locations WHERE user_id = ?";
        try {
            $stmt = $this->db->query($sql, [$userId]);
            return $stmt->rowCount(); // Return number of deleted rows
        } catch (Exception $e) {
            error_log("Error deleting all locations for user {$userId}: " . $e->getMessage());
            throw $e;
        }
    }
}
