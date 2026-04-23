<?php

namespace App\Controllers;

use App\Config\Database;
use PDOException;

class ProductController
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Получение списка товаров с фильтрацией и пагинацией
     */
    public function getProducts($filters = [], $page = 1, $perPage = 12)
    {
        $where = ['p.status = ?'];
        $params = [1];
        
        // Фильтр по категории
        if (!empty($filters['category'])) {
            $where[] = 'p.category_id = ?';
            $params[] = $filters['category'];
        }
        
        // Фильтр по наличию
        if (isset($filters['in_stock']) && $filters['in_stock']) {
            $where[] = 'p.stock > 0';
        }
        
        // Фильтр по цене
        if (!empty($filters['min_price'])) {
            $where[] = 'p.price >= ?';
            $params[] = $filters['min_price'];
        }
        if (!empty($filters['max_price'])) {
            $where[] = 'p.price <= ?';
            $params[] = $filters['max_price'];
        }
        
        // Фильтр по акциям
        if (isset($filters['on_sale']) && $filters['on_sale']) {
            $where[] = 'p.discount > 0';
        }
        
        // Поиск по названию
        if (!empty($filters['search'])) {
            $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Сортировка
        $orderBy = 'p.created_at DESC';
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'price_asc':
                    $orderBy = 'p.price ASC';
                    break;
                case 'price_desc':
                    $orderBy = 'p.price DESC';
                    break;
                case 'rating':
                    $orderBy = 'p.rating DESC';
                    break;
                case 'name_asc':
                    $orderBy = 'p.name ASC';
                    break;
                case 'name_desc':
                    $orderBy = 'p.name DESC';
                    break;
            }
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Общее количество товаров
        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM products p WHERE $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Пагинация
        $offset = ($page - 1) * $perPage;
        
        $stmt = $this->db->prepare("
            SELECT p.*, c.name as category_name, 
                   (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id) as review_count
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE $whereClause
            ORDER BY $orderBy
            LIMIT :offset, :limit
        ");
        
        foreach ($params as $key => $param) {
            $stmt->bindValue($key + 1, $param);
        }
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        
        $stmt->execute();
        $products = $stmt->fetchAll();
        
        return [
            'products' => $products,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Получение товара по ID
     */
    public function getProductById($id)
    {
        $stmt = $this->db->prepare("
            SELECT p.*, c.name as category_name,
                   (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating,
                   (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) as review_count
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = ? AND p.status = 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Получение популярных товаров
     */
    public function getPopularProducts($limit = 6)
    {
        $stmt = $this->db->prepare("
            SELECT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 1 AND p.is_popular = 1
            ORDER BY p.views DESC, p.sales_count DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Получение товаров для слайдера
     */
    public function getSliderProducts($limit = 5)
    {
        $stmt = $this->db->prepare("
            SELECT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 1 AND p.is_featured = 1
            ORDER BY p.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Добавление отзыва к товару
     */
    public function addReview($productId, $userId, $data)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO reviews (product_id, user_id, rating, comment, status) 
                VALUES (?, ?, ?, ?, 0)
            ");
            $stmt->execute([
                $productId,
                $userId,
                $data['rating'],
                $data['comment']
            ]);
            
            return ['success' => true, 'review_id' => $this->db->lastInsertId()];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Получение отзывов для товара
     */
    public function getProductReviews($productId, $approvedOnly = true)
    {
        $statusCondition = $approvedOnly ? 'AND r.status = 1' : '';
        
        $stmt = $this->db->prepare("
            SELECT r.*, u.nickname, u.avatar
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.product_id = ? $statusCondition
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Обновление просмотров товара
     */
    public function incrementViews($productId)
    {
        $stmt = $this->db->prepare("UPDATE products SET views = views + 1 WHERE id = ?");
        $stmt->execute([$productId]);
    }
    
    /**
     * Получение всех категорий
     */
    public function getCategories()
    {
        $stmt = $this->db->query("SELECT * FROM categories WHERE status = 1 ORDER BY name");
        return $stmt->fetchAll();
    }
    
    /**
     * Админ: Создание товара
     */
    public function createProduct($data)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO products 
                (name, description, price, discount, stock, category_id, image, images, 
                 platform, type, features, status, is_popular, is_featured) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $imagesJson = !empty($data['images']) ? json_encode($data['images']) : null;
            $featuresJson = !empty($data['features']) ? json_encode($data['features']) : null;
            
            $stmt->execute([
                $data['name'],
                $data['description'],
                $data['price'],
                $data['discount'] ?? 0,
                $data['stock'] ?? 0,
                $data['category_id'],
                $data['image'],
                $imagesJson,
                $data['platform'] ?? null,
                $data['type'] ?? 'game',
                $featuresJson,
                $data['status'] ?? 1,
                $data['is_popular'] ?? 0,
                $data['is_featured'] ?? 0
            ]);
            
            return ['success' => true, 'product_id' => $this->db->lastInsertId()];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Админ: Обновление товара
     */
    public function updateProduct($id, $data)
    {
        try {
            $fields = [];
            $params = [];
            
            foreach ($data as $key => $value) {
                if (in_array($key, ['name', 'description', 'price', 'discount', 'stock', 
                                    'category_id', 'image', 'platform', 'type', 'status', 
                                    'is_popular', 'is_featured'])) {
                    $fields[] = "$key = ?";
                    $params[] = $value;
                }
            }
            
            if (!empty($fields)) {
                if (isset($data['images'])) {
                    $fields[] = "images = ?";
                    $params[] = json_encode($data['images']);
                }
                if (isset($data['features'])) {
                    $fields[] = "features = ?";
                    $params[] = json_encode($data['features']);
                }
                
                $params[] = $id;
                $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = ?";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Админ: Удаление товара
     */
    public function deleteProduct($id)
    {
        try {
            // Мягкое удаление - установка статуса в 0
            $stmt = $this->db->prepare("UPDATE products SET status = 0 WHERE id = ?");
            $stmt->execute([$id]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
