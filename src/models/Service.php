<?php

namespace App\Models;

use PDO;
use App\Config\Database;

class Service
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(array $filters = []): array
    {
        $sql = "SELECT s.*, c.name as category_name FROM services s
                LEFT JOIN categories c ON s.category_id = c.id WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['available'])) {
            $sql .= " AND s.available_slots > 0";
        }
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND s.category_id = ?";
            $params[] = $filters['category_id'];
        }
        
        $sql .= " ORDER BY s.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, c.name as category_name 
            FROM services s
            LEFT JOIN categories c ON s.category_id = c.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        $service = $stmt->fetch();
        return $service ?: null;
    }

    public function getPopular(int $limit = 6): array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, COUNT(r.id) as reviews_count, AVG(r.rating) as avg_rating
            FROM services s
            LEFT JOIN reviews r ON s.id = r.service_id
            GROUP BY s.id
            ORDER BY reviews_count DESC, avg_rating DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO services (name, description, price, image, category_id, available_slots, duration) 
            VALUES (:name, :description, :price, :image, :category_id, :available_slots, :duration)
        ");
        
        $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'],
            ':price' => $data['price'],
            ':image' => $data['image'] ?? null,
            ':category_id' => $data['category_id'] ?? null,
            ':available_slots' => $data['available_slots'] ?? 0,
            ':duration' => $data['duration'] ?? 60
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            if ($value !== null && $key !== 'id') {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE services SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM services WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getReviews(int $serviceId): array
    {
        $stmt = $this->db->prepare("
            SELECT r.*, u.nickname, u.avatar 
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.service_id = ? AND r.approved = 1
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$serviceId]);
        return $stmt->fetchAll();
    }

    public function addReview(int $serviceId, int $userId, int $rating, string $comment): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO reviews (service_id, user_id, rating, comment) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$serviceId, $userId, $rating, $comment]);
    }
}
