<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Review
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getProductReviews(int $productId, string $status = 'approved'): array
    {
        $stmt = $this->db->prepare("
            SELECT r.*, u.nickname, u.avatar 
            FROM reviews r 
            LEFT JOIN users u ON r.user_id = u.id 
            WHERE r.product_id = :product_id AND r.status = :status 
            ORDER BY r.created_at DESC
        ");
        $stmt->execute(['product_id' => $productId, 'status' => $status]);
        return $stmt->fetchAll();
    }
    
    public function getServiceReviews(int $serviceId, string $status = 'approved'): array
    {
        $stmt = $this->db->prepare("
            SELECT r.*, u.nickname, u.avatar 
            FROM reviews r 
            LEFT JOIN users u ON r.user_id = u.id 
            WHERE r.service_id = :service_id AND r.status = :status 
            ORDER BY r.created_at DESC
        ");
        $stmt->execute(['service_id' => $serviceId, 'status' => $status]);
        return $stmt->fetchAll();
    }
    
    public function getNewsReviews(int $newsId, string $status = 'approved'): array
    {
        $stmt = $this->db->prepare("
            SELECT r.*, u.nickname, u.avatar 
            FROM reviews r 
            LEFT JOIN users u ON r.user_id = u.id 
            WHERE r.news_id = :news_id AND r.status = :status 
            ORDER BY r.created_at DESC
        ");
        $stmt->execute(['news_id' => $newsId, 'status' => $status]);
        return $stmt->fetchAll();
    }
    
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO reviews (user_id, product_id, service_id, news_id, rating, comment, status)
            VALUES (:user_id, :product_id, :service_id, :news_id, :rating, :comment, 'pending')
        ");
        
        $stmt->execute([
            'user_id' => $data['user_id'],
            'product_id' => $data['product_id'] ?? null,
            'service_id' => $data['service_id'] ?? null,
            'news_id' => $data['news_id'] ?? null,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null
        ]);
        
        $reviewId = (int)$this->db->lastInsertId();
        
        // Обновляем рейтинг товара/услуги/новости
        $this->updateRelatedRating(
            $data['product_id'] ?? null,
            $data['service_id'] ?? null,
            $data['news_id'] ?? null
        );
        
        return $reviewId;
    }
    
    public function approve(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE reviews SET status = 'approved' WHERE id = :id");
        $result = $stmt->execute(['id' => $id]);
        
        if ($result) {
            $review = $this->getById($id);
            $this->updateRelatedRating(
                $review['product_id'] ?? null,
                $review['service_id'] ?? null,
                $review['news_id'] ?? null
            );
        }
        
        return $result;
    }
    
    public function reject(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE reviews SET status = 'rejected' WHERE id = :id");
        $result = $stmt->execute(['id' => $id]);
        
        if ($result) {
            $review = $this->getById($id);
            $this->updateRelatedRating(
                $review['product_id'] ?? null,
                $review['service_id'] ?? null,
                $review['news_id'] ?? null
            );
        }
        
        return $result;
    }
    
    private function updateRelatedRating(?int $productId, ?int $serviceId, ?int $newsId): void
    {
        if ($productId) {
            $this->updateProductRating($productId);
        } elseif ($serviceId) {
            $this->updateServiceRating($serviceId);
        } elseif ($newsId) {
            $this->updateNewsRating($newsId);
        }
    }
    
    private function updateProductRating(int $productId): void
    {
        $stmt = $this->db->prepare("
            UPDATE products p
            SET p.rating = (
                SELECT COALESCE(AVG(r.rating), 0) 
                FROM reviews r 
                WHERE r.product_id = :product_id AND r.status = 'approved'
            ),
            p.review_count = (
                SELECT COUNT(*) 
                FROM reviews r 
                WHERE r.product_id = :product_id AND r.status = 'approved'
            )
            WHERE p.id = :product_id
        ");
        $stmt->execute(['product_id' => $productId]);
    }
    
    private function updateServiceRating(int $serviceId): void
    {
        $stmt = $this->db->prepare("
            UPDATE services s
            SET s.rating = (
                SELECT COALESCE(AVG(r.rating), 0) 
                FROM reviews r 
                WHERE r.service_id = :service_id AND r.status = 'approved'
            ),
            s.review_count = (
                SELECT COUNT(*) 
                FROM reviews r 
                WHERE r.service_id = :service_id AND r.status = 'approved'
            )
            WHERE s.id = :service_id
        ");
        $stmt->execute(['service_id' => $serviceId]);
    }
    
    private function updateNewsRating(int $newsId): void
    {
        $stmt = $this->db->prepare("
            UPDATE news n
            SET n.rating = (
                SELECT COALESCE(AVG(r.rating), 0) 
                FROM reviews r 
                WHERE r.news_id = :news_id AND r.status = 'approved'
            )
            WHERE n.id = :news_id
        ");
        $stmt->execute(['news_id' => $newsId]);
    }
    
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM reviews WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    public function getPendingReviews(int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT r.*, u.nickname, p.name as product_name, s.name as service_name, n.title as news_title
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN products p ON r.product_id = p.id
            LEFT JOIN services s ON r.service_id = s.id
            LEFT JOIN news n ON r.news_id = n.id
            WHERE r.status = 'pending'
            ORDER BY r.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
