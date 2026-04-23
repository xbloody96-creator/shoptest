<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Product
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll(array $filters = [], int $limit = 12, int $offset = 0): array
    {
        $where = ['p.is_active = 1'];
        $params = [];
        
        if (!empty($filters['category_id'])) {
            $where[] = 'p.category_id = :category_id';
            $params['category_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = '(p.name LIKE :search OR p.short_description LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        if (isset($filters['min_price'])) {
            $where[] = 'p.price >= :min_price';
            $params['min_price'] = $filters['min_price'];
        }
        
        if (isset($filters['max_price'])) {
            $where[] = 'p.price <= :max_price';
            $params['max_price'] = $filters['max_price'];
        }
        
        if (isset($filters['in_stock']) && $filters['in_stock']) {
            $where[] = 'p.stock_quantity > 0';
        }
        
        if (isset($filters['is_promotion']) && $filters['is_promotion']) {
            $where[] = 'p.is_promotion = 1';
        }
        
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
                case 'popular':
                    $orderBy = 'p.view_count DESC';
                    break;
            }
        }
        
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE " . implode(' AND ', $where) . 
                " ORDER BY $orderBy LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getFeatured(int $limit = 6): array
    {
        $stmt = $this->db->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.is_active = 1 AND p.is_featured = 1 
            ORDER BY p.created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getPromotions(int $limit = 6): array
    {
        $stmt = $this->db->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.is_active = 1 AND p.is_promotion = 1 
            ORDER BY p.created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT p.*, c.name as category_name, c.slug as category_slug 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = :id AND p.is_active = 1
        ");
        $stmt->execute(['id' => $id]);
        
        $product = $stmt->fetch();
        if ($product) {
            $this->incrementViewCount($id);
        }
        
        return $product;
    }
    
    public function getBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare("
            SELECT p.*, c.name as category_name, c.slug as category_slug 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.slug = :slug AND p.is_active = 1
        ");
        $stmt->execute(['slug' => $slug]);
        
        $product = $stmt->fetch();
        if ($product) {
            $this->incrementViewCount($product['id']);
        }
        
        return $product;
    }
    
    public function incrementViewCount(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE products SET view_count = view_count + 1 WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
    
    public function getCategories(): array
    {
        $stmt = $this->db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order");
        return $stmt->fetchAll();
    }
    
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO products (
                category_id, name, slug, description, short_description, price, old_price,
                stock_quantity, is_digital, is_featured, is_promotion, promotion_discount,
                images, specifications, is_active
            ) VALUES (
                :category_id, :name, :slug, :description, :short_description, :price, :old_price,
                :stock_quantity, :is_digital, :is_featured, :is_promotion, :promotion_discount,
                :images, :specifications, :is_active
            )
        ");
        
        $stmt->execute([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => $data['slug'] ?? \App\Helpers\Functions::slugify($data['name']),
            'description' => $data['description'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'price' => $data['price'],
            'old_price' => $data['old_price'] ?? null,
            'stock_quantity' => $data['stock_quantity'] ?? 0,
            'is_digital' => $data['is_digital'] ?? 1,
            'is_featured' => $data['is_featured'] ?? 0,
            'is_promotion' => $data['is_promotion'] ?? 0,
            'promotion_discount' => $data['promotion_discount'] ?? 0,
            'images' => json_encode($data['images'] ?? []),
            'specifications' => json_encode($data['specifications'] ?? []),
            'is_active' => $data['is_active'] ?? 1
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE products SET
                category_id = :category_id,
                name = :name,
                slug = :slug,
                description = :description,
                short_description = :short_description,
                price = :price,
                old_price = :old_price,
                stock_quantity = :stock_quantity,
                is_digital = :is_digital,
                is_featured = :is_featured,
                is_promotion = :is_promotion,
                promotion_discount = :promotion_discount,
                images = :images,
                specifications = :specifications,
                is_active = :is_active
            WHERE id = :id
        ");
        
        return $stmt->execute([
            'id' => $id,
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'price' => $data['price'],
            'old_price' => $data['old_price'] ?? null,
            'stock_quantity' => $data['stock_quantity'] ?? 0,
            'is_digital' => $data['is_digital'] ?? 1,
            'is_featured' => $data['is_featured'] ?? 0,
            'is_promotion' => $data['is_promotion'] ?? 0,
            'promotion_discount' => $data['promotion_discount'] ?? 0,
            'images' => json_encode($data['images'] ?? []),
            'specifications' => json_encode($data['specifications'] ?? []),
            'is_active' => $data['is_active'] ?? 1
        ]);
    }
    
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM products WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
    
    public function getTotalCount(array $filters = []): int
    {
        $where = ['p.is_active = 1'];
        $params = [];
        
        if (!empty($filters['category_id'])) {
            $where[] = 'p.category_id = :category_id';
            $params['category_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = '(p.name LIKE :search OR p.short_description LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        $sql = "SELECT COUNT(*) FROM products p WHERE " . implode(' AND ', $where);
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->execute();
        
        return (int)$stmt->fetchColumn();
    }
}
