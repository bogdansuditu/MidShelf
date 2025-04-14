<?php
require_once __DIR__ . '/../config/database.php';

class Location {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getLocations($userId) {
        $sql = "SELECT * FROM locations WHERE user_id = ? ORDER BY name ASC";
        try {
            return $this->db->query($sql, [$userId])->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching locations: " . $e->getMessage());
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
}
