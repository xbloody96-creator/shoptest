<?php

namespace App\Controllers;

use App\Config\Database;
use PDOException;

class NewsController
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Получение списка новостей с фильтрацией и пагинацией
     */
    public function getNews($filters = [], $page = 1, $perPage = 10)
    {
        $where = ['n.status = ?'];
        $params = [1];
        
        // Фильтр по категории
        if (!empty($filters['category'])) {
            $where[] = 'n.category_id = ?';
            $params[] = $filters['category'];
        }
        
        // Фильтр по актуальности
        if (isset($filters['actual']) && $filters['actual']) {
            $where[] = 'n.actual = 1';
        }
        
        // Фильтр по дате публикации
        if (!empty($filters['date_from'])) {
            $where[] = 'n.published_at >= ?';
            $params[] = $filters['date_from'];
        }
        
        // Поиск по заголовку
        if (!empty($filters['search'])) {
            $where[] = '(n.title LIKE ? OR n.content LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Сортировка
        $orderBy = 'n.published_at DESC';
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'rating':
                    $orderBy = 'n.rating DESC';
                    break;
                case 'views':
                    $orderBy = 'n.views DESC';
                    break;
                case 'title_asc':
                    $orderBy = 'n.title ASC';
                    break;
            }
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Общее количество новостей
        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM news n WHERE $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Пагинация
        $offset = ($page - 1) * $perPage;
        
        $stmt = $this->db->prepare("
            SELECT n.*, c.name as category_name, u.nickname as author_name,
                   (SELECT COUNT(*) FROM reviews r WHERE r.news_id = n.id AND r.status = 1) as comment_count
            FROM news n
            LEFT JOIN categories c ON n.category_id = c.id
            LEFT JOIN users u ON n.author_id = u.id
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
        $newsList = $stmt->fetchAll();
        
        return [
            'news' => $newsList,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Получение новости по ID
     */
    public function getNewsById($id)
    {
        $stmt = $this->db->prepare("
            SELECT n.*, c.name as category_name, u.nickname as author_name,
                   (SELECT AVG(rating) FROM reviews WHERE news_id = n.id AND status = 1) as avg_rating,
                   (SELECT COUNT(*) FROM reviews WHERE news_id = n.id AND status = 1) as comment_count
            FROM news n
            LEFT JOIN categories c ON n.category_id = c.id
            LEFT JOIN users u ON n.author_id = u.id
            WHERE n.id = ? AND n.status = 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Получение популярных новостей
     */
    public function getPopularNews($limit = 6)
    {
        $stmt = $this->db->prepare("
            SELECT n.*, c.name as category_name
            FROM news n
            LEFT JOIN categories c ON n.category_id = c.id
            WHERE n.status = 1 AND n.is_popular = 1
            ORDER BY n.views DESC, n.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Получение новостей для слайдера
     */
    public function getSliderNews($limit = 5)
    {
        $stmt = $this->db->prepare("
            SELECT n.*, c.name as category_name
            FROM news n
            LEFT JOIN categories c ON n.category_id = c.id
            WHERE n.status = 1 AND n.is_featured = 1
            ORDER BY n.published_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Добавление комментария/отзыва к новости
     */
    public function addComment($newsId, $userId, $data)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO reviews (news_id, user_id, rating, comment, status) 
                VALUES (?, ?, ?, ?, 0)
            ");
            $stmt->execute([
                $newsId,
                $userId,
                $data['rating'] ?? 5,
                $data['comment']
            ]);
            
            return ['success' => true, 'review_id' => $this->db->lastInsertId()];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Получение комментариев для новости
     */
    public function getNewsComments($newsId, $approvedOnly = true)
    {
        $statusCondition = $approvedOnly ? 'AND r.status = 1' : '';
        
        $stmt = $this->db->prepare("
            SELECT r.*, u.nickname, u.avatar
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.news_id = ? $statusCondition
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$newsId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Оценка новости
     */
    public function rateNews($newsId, $userId, $rating)
    {
        try {
            // Проверка, не оценивал ли уже пользователь эту новость
            $stmt = $this->db->prepare("SELECT id FROM news_ratings WHERE news_id = ? AND user_id = ?");
            $stmt->execute([$newsId, $userId]);
            
            if ($stmt->fetch()) {
                // Обновление оценки
                $stmt = $this->db->prepare("UPDATE news_ratings SET rating = ? WHERE news_id = ? AND user_id = ?");
                $stmt->execute([$rating, $newsId, $userId]);
            } else {
                // Новая оценка
                $stmt = $this->db->prepare("INSERT INTO news_ratings (news_id, user_id, rating) VALUES (?, ?, ?)");
                $stmt->execute([$newsId, $userId, $rating]);
            }
            
            // Пересчет среднего рейтинга
            $stmt = $this->db->prepare("
                UPDATE news 
                SET rating = (SELECT AVG(rating) FROM news_ratings WHERE news_id = ?)
                WHERE id = ?
            ");
            $stmt->execute([$newsId, $newsId]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Обновление просмотров новости
     */
    public function incrementViews($newsId)
    {
        $stmt = $this->db->prepare("UPDATE news SET views = views + 1 WHERE id = ?");
        $stmt->execute([$newsId]);
    }
    
    /**
     * Админ: Создание новости
     */
    public function createNews($data)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO news 
                (title, content, short_description, image, author_id, category_id, 
                 tags, status, is_popular, is_featured, actual) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $tagsJson = !empty($data['tags']) ? json_encode($data['tags']) : null;
            
            $stmt->execute([
                $data['title'],
                $data['content'],
                $data['short_description'] ?? null,
                $data['image'],
                $data['author_id'] ?? 1,
                $data['category_id'] ?? null,
                $tagsJson,
                $data['status'] ?? 1,
                $data['is_popular'] ?? 0,
                $data['is_featured'] ?? 0,
                $data['actual'] ?? 1
            ]);
            
            return ['success' => true, 'news_id' => $this->db->lastInsertId()];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Админ: Обновление новости
     */
    public function updateNews($id, $data)
    {
        try {
            $fields = [];
            $params = [];
            
            foreach ($data as $key => $value) {
                if (in_array($key, ['title', 'content', 'short_description', 'image', 
                                    'category_id', 'status', 'is_popular', 'is_featured', 'actual'])) {
                    $fields[] = "$key = ?";
                    $params[] = $value;
                }
            }
            
            if (!empty($fields)) {
                if (isset($data['tags'])) {
                    $fields[] = "tags = ?";
                    $params[] = json_encode($data['tags']);
                }
                
                $params[] = $id;
                $sql = "UPDATE news SET " . implode(', ', $fields) . " WHERE id = ?";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Админ: Удаление новости
     */
    public function deleteNews($id)
    {
        try {
            $stmt = $this->db->prepare("UPDATE news SET status = 0 WHERE id = ?");
            $stmt->execute([$id]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
