<?php
/**
 * API для управления заказами
 * GET /api/orders.php - получение заказов (admin или user)
 * POST /api/orders.php - создание/обновление заказа
 */

require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/helpers/auth.php';
require_once __DIR__ . '/../../src/models/Order.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    $orderModel = new Order($pdo);

    if ($method === 'GET') {
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Требуется авторизация']);
            exit;
        }

        $userId = getCurrentUserId();
        $isAdmin = isAdmin();

        if ($action === 'list') {
            // Список заказов пользователя или всех (для админа)
            if ($isAdmin && isset($_GET['all']) && $_GET['all'] == '1') {
                $orders = $orderModel->getAllWithFilters($_GET);
            } else {
                $orders = $orderModel->getByUser($userId);
            }
            echo json_encode(['success' => true, 'data' => $orders]);
            
        } elseif ($action === 'detail' && isset($_GET['id'])) {
            // Детали заказа
            $orderId = (int)$_GET['id'];
            $order = $orderModel->getById($orderId);
            
            // Проверка прав доступа
            if (!$order || (!$isAdmin && $order['user_id'] != $userId)) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Заказ не найден']);
                exit;
            }
            
            $items = $orderModel->getItems($orderId);
            $order['items'] = $items;
            echo json_encode(['success' => true, 'data' => $order]);
            
        } elseif ($action === 'status') {
            // Статусы заказов
            $statuses = $orderModel->getStatuses();
            echo json_encode(['success' => true, 'data' => $statuses]);
            
        } else {
            echo json_encode(['success' => false, 'error' => 'Неверное действие']);
        }
        
    } elseif ($method === 'POST') {
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Требуется авторизация']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $userId = getCurrentUserId();
        $isAdmin = isAdmin();

        if ($action === 'create') {
            // Создание нового заказа
            if (empty($input['items']) || !is_array($input['items'])) {
                throw new Exception('Корзина пуста');
            }
            
            $required = ['delivery_method', 'payment_method'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Поле '$field' обязательно для заполнения");
                }
            }
            
            $orderId = $orderModel->create([
                'user_id' => $userId,
                'items' => $input['items'],
                'delivery_method' => $input['delivery_method'],
                'payment_method' => $input['payment_method'],
                'delivery_address' => $input['delivery_address'] ?? null,
                'comment' => $input['comment'] ?? null
            ]);
            
            echo json_encode(['success' => true, 'id' => $orderId]);
            
        } elseif ($action === 'update_status' && $isAdmin) {
            // Обновление статуса заказа (только админ)
            if (!isset($input['id']) || !isset($input['status'])) {
                throw new Exception('Необходимо указать ID заказа и статус');
            }
            
            $updated = $orderModel->updateStatus((int)$input['id'], $input['status']);
            echo json_encode(['success' => $updated]);
            
        } elseif ($action === 'cancel' && isset($input['id'])) {
            // Отмена заказа пользователем
            $orderId = (int)$input['id'];
            $order = $orderModel->getById($orderId);
            
            if (!$order || $order['user_id'] != $userId) {
                throw new Exception('Заказ не найден');
            }
            
            if ($order['status'] !== 'pending' && $order['status'] !== 'processing') {
                throw new Exception('Нельзя отменить заказ с текущим статусом');
            }
            
            $cancelled = $orderModel->updateStatus($orderId, 'cancelled');
            echo json_encode(['success' => $cancelled]);
            
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
