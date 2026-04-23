<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Functions;
use App\Config\Database;

class SearchController
{
    public function search(): array
    {
        $query = trim($_GET['q'] ?? '');
        $type = $_GET['type'] ?? 'all'; // all, products, news, services
        
        $results = [
            'products' => [],
            'news' => [],
            'services' => []
        ];
        
        if (empty($query)) {
            return $results;
        }
        
        try {
            $pdo = Database::getInstance()->getConnection();
            
            // Поиск товаров
            if ($type === 'all' || $type === 'products') {
                $stmt = $pdo->prepare("
                    SELECT p.*, c.name as category_name 
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.name LIKE ? OR p.description LIKE ?
                    LIMIT 20
                ");
                $searchTerm = "%$query%";
                $stmt->execute([$searchTerm, $searchTerm]);
                $results['products'] = $stmt->fetchAll();
            }
            
            // Поиск новостей
            if ($type === 'all' || $type === 'news') {
                $stmt = $pdo->prepare("
                    SELECT * FROM news
                    WHERE title LIKE ? OR content LIKE ?
                    LIMIT 20
                ");
                $searchTerm = "%$query%";
                $stmt->execute([$searchTerm, $searchTerm]);
                $results['news'] = $stmt->fetchAll();
            }
            
            // Поиск услуг
            if ($type === 'all' || $type === 'services') {
                $stmt = $pdo->prepare("
                    SELECT s.*, c.name as category_name
                    FROM services s
                    LEFT JOIN categories c ON s.category_id = c.id
                    WHERE s.name LIKE ? OR s.description LIKE ?
                    LIMIT 20
                ");
                $searchTerm = "%$query%";
                $stmt->execute([$searchTerm, $searchTerm]);
                $results['services'] = $stmt->fetchAll();
            }
        } catch (\Exception $e) {
            error_log("Ошибка поиска: " . $e->getMessage());
        }
        
        return $results;
    }
}
