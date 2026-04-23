<?php
// api/service-book.php - API для записи на услугу

session_start();
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/helpers/functions.php';
require_once __DIR__ . '/../../src/helpers/Auth.php';

use App\Helpers\Auth;
use App\Helpers\Functions;
use App\Config\Database;

// Только для авторизованных пользователей
Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Functions::redirect('/services.php');
}

$serviceId = $_POST['service_id'] ?? 0;
$bookingDate = $_POST['booking_date'] ?? '';
$comment = $_POST['comment'] ?? '';

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Получаем услугу
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$serviceId]);
    $service = $stmt->fetch();
    
    if (!$service) {
        throw new Exception('Услуга не найдена');
    }
    
    if ($service['available_slots'] <= 0) {
        throw new Exception('Нет доступных мест для записи');
    }
    
    if (empty($bookingDate)) {
        throw new Exception('Выберите дату и время записи');
    }
    
    // Создаем заказ на услугу
    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, total, status, type, notes, created_at) 
        VALUES (?, ?, 'pending', 'service', ?, NOW())
    ");
    $stmt->execute([Auth::id(), $service['price'], $comment]);
    $orderId = (int)$pdo->lastInsertId();
    
    // Добавляем элемент заказа
    $stmt = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, service_id, quantity, price, booking_date) 
        VALUES (?, NULL, ?, 1, ?, ?)
    ");
    $stmt->execute([$orderId, $serviceId, $service['price'], $bookingDate]);
    
    // Уменьшаем количество доступных слотов
    $stmt = $pdo->prepare("UPDATE services SET available_slots = available_slots - 1 WHERE id = ?");
    $stmt->execute([$serviceId]);
    
    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => 'Запись на услугу успешно оформлена! Ожидайте подтверждения.'
    ];
    
    Functions::redirect('/profile.php?tab=orders');
    
} catch (\Exception $e) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Ошибка при оформлении записи: ' . $e->getMessage()
    ];
    Functions::redirect('/service-detail.php?id=' . $serviceId);
}
