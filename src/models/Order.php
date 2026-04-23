<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Order
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create(int $userId, array $data): int
    {
        $orderNumber = \App\Helpers\Functions::generateOrderNumber();
        
        $stmt = $this->db->prepare("
            INSERT INTO orders (
                user_id, order_number, status, total_amount, discount_amount,
                payment_method, payment_status, delivery_method, delivery_address,
                customer_email, customer_phone, customer_name, notes
            ) VALUES (
                :user_id, :order_number, 'pending', :total_amount, :discount_amount,
                :payment_method, 'pending', :delivery_method, :delivery_address,
                :customer_email, :customer_phone, :customer_name, :notes
            )
        ");
        
        $stmt->execute([
            'user_id' => $userId,
            'order_number' => $orderNumber,
            'total_amount' => $data['total_amount'],
            'discount_amount' => $data['discount_amount'] ?? 0,
            'payment_method' => $data['payment_method'] ?? null,
            'delivery_method' => $data['delivery_method'] ?? null,
            'delivery_address' => $data['delivery_address'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'customer_name' => $data['customer_name'] ?? null,
            'notes' => $data['notes'] ?? null
        ]);
        
        $orderId = (int)$this->db->lastInsertId();
        
        // Добавляем элементы заказа
        if (!empty($data['items'])) {
            $itemStmt = $this->db->prepare("
                INSERT INTO order_items (order_id, product_id, service_id, name, price, quantity, subtotal, service_date, service_time)
                VALUES (:order_id, :product_id, :service_id, :name, :price, :quantity, :subtotal, :service_date, :service_time)
            ");
            
            foreach ($data['items'] as $item) {
                $itemStmt->execute([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'] ?? null,
                    'service_id' => $item['service_id'] ?? null,
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['price'] * $item['quantity'],
                    'service_date' => $item['service_date'] ?? null,
                    'service_time' => $item['service_time'] ?? null
                ]);
            }
        }
        
        return $orderId;
    }
    
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    public function getByOrderNumber(string $orderNumber): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE order_number = :order_number");
        $stmt->execute(['order_number' => $orderNumber]);
        return $stmt->fetch();
    }
    
    public function getUserOrders(int $userId, int $limit = 10, int $offset = 0): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM orders 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getItems(int $orderId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
        $stmt->execute(['order_id' => $orderId]);
        return $stmt->fetchAll();
    }
    
    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE orders SET status = :status WHERE id = :id");
        return $stmt->execute([
            'status' => $status,
            'id' => $id
        ]);
    }
    
    public function updatePaymentStatus(int $id, string $paymentStatus): bool
    {
        $stmt = $this->db->prepare("UPDATE orders SET payment_status = :payment_status WHERE id = :id");
        return $stmt->execute([
            'payment_status' => $paymentStatus,
            'id' => $id
        ]);
    }
    
    public function getAll(int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare("
            SELECT o.*, u.email, u.full_name 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            ORDER BY o.created_at DESC 
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getTotalCount(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM orders");
        return (int)$stmt->fetchColumn();
    }
}
