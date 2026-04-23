<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Cart
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getItems(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, p.name as product_name, p.price as product_price, p.images as product_images,
                   s.name as service_name, s.price as service_price, s.duration_minutes
            FROM cart c
            LEFT JOIN products p ON c.product_id = p.id
            LEFT JOIN services s ON c.service_id = s.id
            WHERE c.user_id = :user_id
            ORDER BY c.created_at DESC
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }
    
    public function addItem(int $userId, ?int $productId = null, ?int $serviceId = null, 
                           int $quantity = 1, ?string $serviceDate = null, ?string $serviceTime = null): bool
    {
        if ($productId) {
            // Проверка наличия товара
            $stmt = $this->db->prepare("SELECT stock_quantity FROM products WHERE id = :id");
            $stmt->execute(['id' => $productId]);
            $product = $stmt->fetch();
            
            if (!$product || $product['stock_quantity'] < $quantity) {
                return false;
            }
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO cart (user_id, product_id, service_id, quantity, service_date, service_time)
            VALUES (:user_id, :product_id, :service_id, :quantity, :service_date, :service_time)
            ON DUPLICATE KEY UPDATE quantity = quantity + :quantity
        ");
        
        return $stmt->execute([
            'user_id' => $userId,
            'product_id' => $productId,
            'service_id' => $serviceId,
            'quantity' => $quantity,
            'service_date' => $serviceDate,
            'service_time' => $serviceTime
        ]);
    }
    
    public function updateQuantity(int $userId, int $cartItemId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return $this->removeItem($userId, $cartItemId);
        }
        
        $stmt = $this->db->prepare("UPDATE cart SET quantity = :quantity WHERE id = :id AND user_id = :user_id");
        return $stmt->execute([
            'quantity' => $quantity,
            'id' => $cartItemId,
            'user_id' => $userId
        ]);
    }
    
    public function removeItem(int $userId, int $cartItemId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM cart WHERE id = :id AND user_id = :user_id");
        return $stmt->execute([
            'id' => $cartItemId,
            'user_id' => $userId
        ]);
    }
    
    public function clear(int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM cart WHERE user_id = :user_id");
        return $stmt->execute(['user_id' => $userId]);
    }
    
    public function getTotal(int $userId): float
    {
        $items = $this->getItems($userId);
        $total = 0;
        
        foreach ($items as $item) {
            $price = $item['product_price'] ?? $item['service_price'] ?? 0;
            $total += $price * $item['quantity'];
        }
        
        return $total;
    }
    
    public function getCount(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM cart WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }
}
