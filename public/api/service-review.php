<?php
// api/service-review.php - API для добавления отзыва на услугу

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
$rating = $_POST['rating'] ?? 0;
$comment = $_POST['comment'] ?? '';

// Валидация
if ($rating < 1 || $rating > 5) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Рейтинг должен быть от 1 до 5'
    ];
    Functions::redirect('/service-detail.php?id=' . $serviceId);
}

if (empty($comment) || strlen(trim($comment)) < 10) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Отзыв должен содержать минимум 10 символов'
    ];
    Functions::redirect('/service-detail.php?id=' . $serviceId);
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Проверяем, существует ли услуга
    $stmt = $pdo->prepare("SELECT id FROM services WHERE id = ?");
    $stmt->execute([$serviceId]);
    if (!$stmt->fetch()) {
        throw new Exception('Услуга не найдена');
    }
    
    // Добавляем отзыв (требуется модерация)
    $stmt = $pdo->prepare("
        INSERT INTO reviews (service_id, user_id, rating, comment, approved, created_at) 
        VALUES (?, ?, ?, ?, 0, NOW())
    ");
    $stmt->execute([$serviceId, Auth::id(), $rating, trim($comment)]);
    
    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => 'Отзыв успешно отправлен на модерацию'
    ];
    
} catch (\Exception $e) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Ошибка при отправке отзыва: ' . $e->getMessage()
    ];
}

Functions::redirect('/service-detail.php?id=' . $serviceId);
