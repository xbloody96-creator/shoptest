<?php

namespace App\Controllers;

use App\Config\Database;
use PDOException;

class OrderController
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Создание заказа
     */
    public function createOrder($userId, $data)
    {
        try {
            $this->db->beginTransaction();
            
            // Получение товаров из корзины
            $stmt = $this->db->prepare("SELECT * FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);
            $cartItems = $stmt->fetchAll();
            
            if (empty($cartItems)) {
                return ['success' => false, 'error' => 'Корзина пуста'];
            }
            
            // Расчет общей суммы
            $totalAmount = 0;
            foreach ($cartItems as $item) {
                $totalAmount += $item['price'] * $item['quantity'];
            }
            
            // Создание заказа
            $stmt = $this->db->prepare("
                INSERT INTO orders 
                (user_id, total_amount, delivery_method, delivery_address, 
                 payment_method, payment_status, status, notes) 
                VALUES (?, ?, ?, ?, ?, 'pending', 'pending', ?)
            ");
            $stmt->execute([
                $userId,
                $totalAmount,
                $data['delivery_method'] ?? 'digital',
                $data['delivery_address'] ?? null,
                $data['payment_method'] ?? 'card',
                $data['notes'] ?? null
            ]);
            
            $orderId = $this->db->lastInsertId();
            
            // Добавление элементов заказа
            $stmt = $this->db->prepare("
                INSERT INTO order_items 
                (order_id, product_id, quantity, price, subtotal) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($cartItems as $item) {
                $subtotal = $item['price'] * $item['quantity'];
                $stmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price'],
                    $subtotal
                ]);
                
                // Обновление количества товара на складе
                if ($item['product_type'] === 'game' || $item['product_type'] === 'key') {
                    $updateStmt = $this->db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                    $updateStmt->execute([$item['quantity'], $item['product_id']]);
                }
            }
            
            // Очистка корзины
            $stmt = $this->db->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            $this->db->commit();
            
            return ['success' => true, 'order_id' => $orderId];
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Ошибка создания заказа: ' . $e->getMessage()];
        }
    }
    
    /**
     * Получение заказа по ID
     */
    public function getOrderById($orderId, $userId = null)
    {
        try {
            $where = "o.id = ?";
            $params = [$orderId];
            
            if ($userId !== null) {
                $where .= " AND o.user_id = ?";
                $params[] = $userId;
            }
            
            $stmt = $this->db->prepare("
                SELECT o.*, u.email, u.fio, u.nickname
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE $where
            ");
            $stmt->execute($params);
            $order = $stmt->fetch();
            
            if (!$order) {
                return null;
            }
            
            // Получение элементов заказа
            $stmt = $this->db->prepare("
                SELECT oi.*, p.name, p.image, p.type
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$orderId]);
            $order['items'] = $stmt->fetchAll();
            
            return $order;
            
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Получение заказов пользователя
     */
    public function getUserOrders($userId, $limit = 10)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT o.*, 
                       (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count
                FROM orders o
                WHERE o.user_id = ?
                ORDER BY o.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            $orders = $stmt->fetchAll();
            
            // Получение элементов для каждого заказа
            foreach ($orders as &$order) {
                $stmt = $this->db->prepare("
                    SELECT oi.*, p.name, p.image
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = ?
                    LIMIT 3
                ");
                $stmt->execute([$order['id']]);
                $order['preview_items'] = $stmt->fetchAll();
            }
            
            return $orders;
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Получение текущего активного заказа
     */
    public function getCurrentOrder($userId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM orders 
            WHERE user_id = ? AND status IN ('pending', 'processing')
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    /**
     * Обновление статуса заказа (Админ)
     */
    public function updateOrderStatus($orderId, $status)
    {
        try {
            $allowedStatuses = ['pending', 'processing', 'paid', 'shipped', 'completed', 'cancelled'];
            
            if (!in_array($status, $allowedStatuses)) {
                return ['success' => false, 'error' => 'Недопустимый статус'];
            }
            
            $stmt = $this->db->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $orderId]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Админ: Получение всех заказов
     */
    public function getAllOrders($filters = [], $page = 1, $perPage = 20)
    {
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where[] = 'o.status = ?';
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['payment_status'])) {
            $where[] = 'o.payment_status = ?';
            $params[] = $filters['payment_status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = 'o.created_at >= ?';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = 'o.created_at <= ?';
            $params[] = $filters['date_to'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Общее количество
        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM orders o WHERE $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Пагинация
        $offset = ($page - 1) * $perPage;
        
        $stmt = $this->db->prepare("
            SELECT o.*, u.email, u.fio,
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE $whereClause
            ORDER BY o.created_at DESC
            LIMIT :offset, :limit
        ");
        
        foreach ($params as $key => $param) {
            $stmt->bindValue($key + 1, $param);
        }
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        
        $stmt->execute();
        $orders = $stmt->fetchAll();
        
        return [
            'orders' => $orders,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Оплата заказа
     */
    public function payOrder($orderId, $userId)
    {
        try {
            $order = $this->getOrderById($orderId, $userId);
            
            if (!$order) {
                return ['success' => false, 'error' => 'Заказ не найден'];
            }
            
            if ($order['payment_status'] === 'paid') {
                return ['success' => false, 'error' => 'Заказ уже оплачен'];
            }
            
            // Имитация оплаты (в реальном проекте - интеграция с платежной системой)
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                UPDATE orders 
                SET payment_status = 'paid', paid_at = NOW(), status = 'processing'
                WHERE id = ?
            ");
            $stmt->execute([$orderId]);
            
            $this->db->commit();
            
            return ['success' => true, 'message' => 'Оплата прошла успешно'];
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Ошибка оплаты: ' . $e->getMessage()];
        }
    }
}
