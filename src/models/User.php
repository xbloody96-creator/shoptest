<?php

namespace App\Models;

use PDO;
use App\Config\Database;

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findByLogin(string $login): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (email, login, full_name, nickname, birth_date, gender, password, avatar) 
            VALUES (:email, :login, :full_name, :nickname, :birth_date, :gender, :password, :avatar)
        ");
        
        $stmt->execute([
            ':email' => $data['email'],
            ':login' => $data['login'],
            ':full_name' => $data['full_name'],
            ':nickname' => $data['nickname'],
            ':birth_date' => $data['birth_date'],
            ':gender' => $data['gender'],
            ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':avatar' => $data['avatar'] ?? null
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            if ($value !== null) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    public function updateAvatar(int $id, string $avatar): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        return $stmt->execute([$avatar, $id]);
    }

    public function getLastSessions(int $userId, int $limit = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM sessions 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public function getUserOrders(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT o.*, COUNT(oi.id) as items_count 
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getCurrentOrder(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM orders 
            WHERE user_id = ? AND status = 'pending'
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $order = $stmt->fetch();
        return $order ?: null;
    }

    public function getFavorites(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT n.* FROM favorites f
            JOIN news n ON f.news_id = n.id
            WHERE f.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getLastViews(int $userId, int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT p.* FROM product_views pv
            JOIN products p ON pv.product_id = p.id
            WHERE pv.user_id = ?
            ORDER BY pv.viewed_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
}
