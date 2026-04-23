<?php

namespace App\Controllers;

use App\Config\Database;
use PDOException;

class AdminController
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Проверка прав администратора
     */
    public function isAdmin()
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        return $user && in_array($user['role'], ['admin', 'moderator']);
    }
    
    /**
     * Получение статистики для админ-панели
     */
    public function getDashboardStats()
    {
        try {
            $stats = [];
            
            // Количество пользователей
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM users");
            $stats['users_count'] = $stmt->fetch()['count'];
            
            // Количество товаров
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM products WHERE status = 1");
            $stats['products_count'] = $stmt->fetch()['count'];
            
            // Количество заказов
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM orders");
            $stats['orders_count'] = $stmt->fetch()['count'];
            
            // Заказы в ожидании
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
            $stats['pending_orders'] = $stmt->fetch()['count'];
            
            // Новости
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM news WHERE status = 1");
            $stats['news_count'] = $stmt->fetch()['count'];
            
            // Отзывы на модерации
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM reviews WHERE status = 0");
            $stats['pending_reviews'] = $stmt->fetch()['count'];
            
            // Выручка за сегодня
            $stmt = $this->db->query("
                SELECT COALESCE(SUM(total_amount), 0) as total 
                FROM orders 
                WHERE payment_status = 'paid' AND DATE(created_at) = CURDATE()
            ");
            $stats['today_revenue'] = $stmt->fetch()['total'];
            
            // Выручка за месяц
            $stmt = $this->db->query("
                SELECT COALESCE(SUM(total_amount), 0) as total 
                FROM orders 
                WHERE payment_status = 'paid' AND MONTH(created_at) = MONTH(CURDATE())
            ");
            $stats['month_revenue'] = $stmt->fetch()['total'];
            
            return $stats;
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Получение последних заказов
     */
    public function getRecentOrders($limit = 10)
    {
        $stmt = $this->db->prepare("
            SELECT o.*, u.email, u.fio
            FROM orders o
            JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Получение отзывов на модерации
     */
    public function getPendingReviews($limit = 20)
    {
        $stmt = $this->db->prepare("
            SELECT r.*, u.nickname, p.name as product_name, n.title as news_title
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            LEFT JOIN products p ON r.product_id = p.id
            LEFT JOIN news n ON r.news_id = n.id
            WHERE r.status = 0
            ORDER BY r.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Одобрение/отклонение отзыва
     */
    public function moderateReview($reviewId, $approve)
    {
        try {
            $status = $approve ? 1 : 2; // 1 - одобрено, 2 - отклонено
            $stmt = $this->db->prepare("UPDATE reviews SET status = ?, moderated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $reviewId]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Получение всех пользователей (админ)
     */
    public function getUsers($filters = [], $page = 1, $perPage = 20)
    {
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['role'])) {
            $where[] = 'role = ?';
            $params[] = $filters['role'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = '(email LIKE ? OR login LIKE ? OR fio LIKE ? OR nickname LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        $whereClause = implode(' AND ', $where);
        
        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM users WHERE $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        $offset = ($page - 1) * $perPage;
        
        $stmt = $this->db->prepare("
            SELECT * FROM users
            WHERE $whereClause
            ORDER BY created_at DESC
            LIMIT :offset, :limit
        ");
        
        foreach ($params as $key => $param) {
            $stmt->bindValue($key + 1, $param);
        }
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        
        $stmt->execute();
        
        return [
            'users' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Обновление роли пользователя
     */
    public function updateUserRole($userId, $role)
    {
        try {
            $allowedRoles = ['user', 'moderator', 'admin'];
            
            if (!in_array($role, $allowedRoles)) {
                return ['success' => false, 'error' => 'Недопустимая роль'];
            }
            
            $stmt = $this->db->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$role, $userId]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Удаление пользователя
     */
    public function deleteUser($userId)
    {
        try {
            // Нельзя удалить самого себя
            if ($userId == $_SESSION['user_id']) {
                return ['success' => false, 'error' => 'Нельзя удалить самого себя'];
            }
            
            $stmt = $this->db->prepare("UPDATE users SET status = 0 WHERE id = ?");
            $stmt->execute([$userId]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Получение списка услуг
     */
    public function getServices($filters = [])
    {
        $where = ['s.status = ?'];
        $params = [1];
        
        if (!empty($filters['category'])) {
            $where[] = 's.category_id = ?';
            $params[] = $filters['category'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->prepare("
            SELECT s.*, c.name as category_name
            FROM services s
            LEFT JOIN categories c ON s.category_id = c.id
            WHERE $whereClause
            ORDER BY s.created_at DESC
        ");
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Создание услуги
     */
    public function createService($data)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO services 
                (name, description, price, duration, category_id, image, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['name'],
                $data['description'],
                $data['price'],
                $data['duration'] ?? null,
                $data['category_id'] ?? null,
                $data['image'],
                $data['status'] ?? 1
            ]);
            
            return ['success' => true, 'service_id' => $this->db->lastInsertId()];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Удаление услуги
     */
    public function deleteService($id)
    {
        try {
            $stmt = $this->db->prepare("UPDATE services SET status = 0 WHERE id = ?");
            $stmt->execute([$id]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
