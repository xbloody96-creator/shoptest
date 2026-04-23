<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class News
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll(array $filters = [], int $limit = 10, int $offset = 0): array
    {
        $where = ['n.is_active = 1'];
        $params = [];
        
        if (!empty($filters['search'])) {
            $where[] = '(n.title LIKE :search OR n.content LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        if (isset($filters['is_featured']) && $filters['is_featured']) {
            $where[] = 'n.is_featured = 1';
        }
        
        $orderBy = 'n.published_at DESC';
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'rating':
                    $orderBy = 'n.rating DESC';
                    break;
                case 'popular':
                    $orderBy = 'n.view_count DESC';
                    break;
            }
        }
        
        $sql = "SELECT n.*, u.nickname as author_name 
                FROM news n 
                LEFT JOIN users u ON n.author_id = u.id 
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
    
    public function getFeatured(int $limit = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT n.*, u.nickname as author_name 
            FROM news n 
            LEFT JOIN users u ON n.author_id = u.id 
            WHERE n.is_active = 1 AND n.is_featured = 1 
            ORDER BY n.published_at DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT n.*, u.nickname as author_name, u.avatar as author_avatar 
            FROM news n 
            LEFT JOIN users u ON n.author_id = u.id 
            WHERE n.id = :id AND n.is_active = 1
        ");
        $stmt->execute(['id' => $id]);
        
        $news = $stmt->fetch();
        if ($news) {
            $this->incrementViewCount($id);
        }
        
        return $news;
    }
    
    public function getBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare("
            SELECT n.*, u.nickname as author_name, u.avatar as author_avatar 
            FROM news n 
            LEFT JOIN users u ON n.author_id = u.id 
            WHERE n.slug = :slug AND n.is_active = 1
        ");
        $stmt->execute(['slug' => $slug]);
        
        $news = $stmt->fetch();
        if ($news) {
            $this->incrementViewCount($news['id']);
        }
        
        return $news;
    }
    
    public function incrementViewCount(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE news SET view_count = view_count + 1 WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
    
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO news (
                title, slug, content, short_description, image, author_id,
                is_featured, is_active, published_at
            ) VALUES (
                :title, :slug, :content, :short_description, :image, :author_id,
                :is_featured, :is_active, :published_at
            )
        ");
        
        $stmt->execute([
            'title' => $data['title'],
            'slug' => $data['slug'] ?? \App\Helpers\Functions::slugify($data['title']),
            'content' => $data['content'],
            'short_description' => $data['short_description'] ?? null,
            'image' => $data['image'] ?? null,
            'author_id' => $data['author_id'] ?? null,
            'is_featured' => $data['is_featured'] ?? 0,
            'is_active' => $data['is_active'] ?? 1,
            'published_at' => $data['published_at'] ?? date('Y-m-d H:i:s')
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE news SET
                title = :title,
                slug = :slug,
                content = :content,
                short_description = :short_description,
                image = :image,
                is_featured = :is_featured,
                is_active = :is_active,
                published_at = :published_at
            WHERE id = :id
        ");
        
        return $stmt->execute([
            'id' => $id,
            'title' => $data['title'],
            'slug' => $data['slug'],
            'content' => $data['content'],
            'short_description' => $data['short_description'] ?? null,
            'image' => $data['image'] ?? null,
            'is_featured' => $data['is_featured'] ?? 0,
            'is_active' => $data['is_active'] ?? 1,
            'published_at' => $data['published_at'] ?? date('Y-m-d H:i:s')
        ]);
    }
    
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM news WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
    
    public function getTotalCount(array $filters = []): int
    {
        $where = ['n.is_active = 1'];
        $params = [];
        
        if (!empty($filters['search'])) {
            $where[] = '(n.title LIKE :search OR n.content LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        $sql = "SELECT COUNT(*) FROM news n WHERE " . implode(' AND ', $where);
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->execute();
        
        return (int)$stmt->fetchColumn();
    }
}
