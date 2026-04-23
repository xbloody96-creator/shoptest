<?php
/**
 * API для управления товарами
 * GET /api/products.php - получение списка товаров
 * POST /api/products.php - создание/обновление товара (admin)
 */

require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/helpers/auth.php';
require_once __DIR__ . '/../../src/models/Product.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    $productModel = new Product($pdo);

    if ($method === 'GET') {
        if ($action === 'list') {
            // Получение списка товаров с фильтрацией
            $filters = [
                'category' => $_GET['category'] ?? null,
                'min_price' => $_GET['min_price'] ?? null,
                'max_price' => $_GET['max_price'] ?? null,
                'in_stock' => $_GET['in_stock'] ?? null,
                'search' => $_GET['search'] ?? null,
                'sort' => $_GET['sort'] ?? 'created_at',
                'order' => $_GET['order'] ?? 'DESC'
            ];
            
            $products = $productModel->getWithFilters($filters);
            echo json_encode(['success' => true, 'data' => $products]);
            
        } elseif ($action === 'detail' && isset($_GET['id'])) {
            // Получение детальной информации о товаре
            $product = $productModel->getById((int)$_GET['id']);
            if ($product) {
                echo json_encode(['success' => true, 'data' => $product]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Товар не найден']);
            }
            
        } elseif ($action === 'popular') {
            // Популярные товары
            $products = $productModel->getPopular(8);
            echo json_encode(['success' => true, 'data' => $products]);
            
        } elseif ($action === 'new') {
            // Новые поступления
            $products = $productModel->getNew(8);
            echo json_encode(['success' => true, 'data' => $products]);
            
        } elseif ($action === 'sale') {
            // Товары со скидкой
            $products = $productModel->getOnSale(8);
            echo json_encode(['success' => true, 'data' => $products]);
            
        } else {
            echo json_encode(['success' => false, 'error' => 'Неверное действие']);
        }
        
    } elseif ($method === 'POST') {
        // Проверка прав администратора
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Доступ запрещен']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($action === 'create') {
            // Создание нового товара
            $required = ['name', 'price', 'category_id', 'description'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Поле '$field' обязательно для заполнения");
                }
            }
            
            $productId = $productModel->create($input);
            echo json_encode(['success' => true, 'id' => $productId]);
            
        } elseif ($action === 'update' && isset($input['id'])) {
            // Обновление товара
            $updated = $productModel->update((int)$input['id'], $input);
            echo json_encode(['success' => $updated]);
            
        } elseif ($action === 'delete' && isset($input['id'])) {
            // Удаление товара
            $deleted = $productModel->delete((int)$input['id']);
            echo json_encode(['success' => $deleted]);
            
        } else {
            echo json_encode(['success' => false, 'error' => 'Неверное действие']);
        }
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Метод не поддерживается']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
