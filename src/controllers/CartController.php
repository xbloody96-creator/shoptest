<?php

namespace App\Controllers;

use App\Config\Database;
use PDOException;

class CartController
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Добавление товара в корзину
     */
    public function addToCart($userId, $productId, $quantity = 1, $productType = 'game')
    {
        try {
            // Проверка наличия товара
            if ($productType === 'game' || $productType === 'key') {
                $stmt = $this->db->prepare("SELECT stock, price FROM products WHERE id = ? AND status = 1");
                $stmt->execute([$productId]);
                $product = $stmt->fetch();
                
                if (!$product) {
                    return ['success' => false, 'error' => 'Товар не найден'];
                }
                
                if ($product['stock'] < $quantity) {
                    return ['success' => false, 'error' => 'Недостаточно товара на складе'];
                }
            }
            
            // Проверка, есть ли уже товар в корзине
            $stmt = $this->db->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
            $cartItem = $stmt->fetch();
            
            if ($cartItem) {
                // Обновление количества
                $newQuantity = $cartItem['quantity'] + $quantity;
                $stmt = $this->db->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newQuantity, $cartItem['id']]);
            } else {
                // Добавление нового товара
                $stmt = $this->db->prepare("
                    INSERT INTO cart (user_id, product_id, quantity, product_type, added_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$userId, $productId, $quantity, $productType]);
            }
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Получение содержимого корзины
     */
    public function getCart($userId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, p.name, p.image, p.price, p.discount, p.stock, p.type as product_type,
                       (c.quantity * p.price * (1 - COALESCE(p.discount, 0) / 100)) as item_total
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ? AND p.status = 1
                ORDER BY c.added_at DESC
            ");
            $stmt->execute([$userId]);
            $items = $stmt->fetchAll();
            
            // Расчет общей суммы
            $totalAmount = 0;
            foreach ($items as &$item) {
                $totalAmount += $item['item_total'];
            }
            
            return [
                'items' => $items,
                'total_amount' => $totalAmount,
                'total_items' => count($items)
            ];
            
        } catch (PDOException $e) {
            return ['items' => [], 'total_amount' => 0, 'total_items' => 0];
        }
    }
    
    /**
     * Обновление количества товара в корзине
     */
    public function updateQuantity($userId, $cartId, $quantity)
    {
        try {
            if ($quantity <= 0) {
                return $this->removeFromCart($userId, $cartId);
            }
            
            // Проверка доступного количества
            $stmt = $this->db->prepare("
                SELECT c.product_id, p.stock 
                FROM cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.id = ? AND c.user_id = ?
            ");
            $stmt->execute([$cartId, $userId]);
            $item = $stmt->fetch();
            
            if (!$item) {
                return ['success' => false, 'error' => 'Товар не найден в корзине'];
            }
            
            if ($item['stock'] < $quantity) {
                return ['success' => false, 'error' => 'Недостаточно товара на складе'];
            }
            
            $stmt = $this->db->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
            $stmt->execute([$quantity, $cartId, $userId]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Удаление товара из корзины
     */
    public function removeFromCart($userId, $cartId)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->execute([$cartId, $userId]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Очистка корзины
     */
    public function clearCart($userId)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Получение количества товаров в корзине (для отображения в шапке)
     */
    public function getCartCount($userId)
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count, SUM(quantity) as total_quantity FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return [
                'count' => (int)$result['count'],
                'total_quantity' => (int)$result['total_quantity'] ?? 0
            ];
            
        } catch (PDOException $e) {
            return ['count' => 0, 'total_quantity' => 0];
        }
    }
}
