<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Functions;
use App\Models\Service;
use App\Config\Database;

class ServiceController
{
    private Service $serviceModel;

    public function __construct()
    {
        $this->serviceModel = new Service();
    }

    public function index(): array
    {
        $filters = [
            'available' => $_GET['available'] ?? null,
            'category_id' => $_GET['category_id'] ?? null
        ];
        
        return $this->serviceModel->getAll($filters);
    }

    public function show(int $id): void
    {
        $service = $this->serviceModel->findById($id);
        
        if (!$service) {
            http_response_code(404);
            include __DIR__ . '/../../public/404.php';
            return;
        }
        
        $reviews = $this->serviceModel->getReviews($id);
        $relatedServices = $this->serviceModel->getPopular(4);
        
        // Сохраняем просмотр
        if (Auth::check()) {
            try {
                $pdo = Database::getInstance()->getConnection();
                $stmt = $pdo->prepare("
                    INSERT INTO service_views (user_id, service_id, viewed_at) 
                    VALUES (?, ?, NOW())
                ");
                $stmt->execute([Auth::id(), $id]);
            } catch (\Exception $e) {
                // Игнорируем ошибку
            }
        }
        
        include __DIR__ . '/../../public/service-detail.php';
    }

    public function book(): void
    {
        Auth::requireLogin();
        
        $serviceId = $_POST['service_id'] ?? 0;
        $bookingDate = $_POST['booking_date'] ?? '';
        $comment = $_POST['comment'] ?? '';
        
        $service = $this->serviceModel->findById($serviceId);
        
        if (!$service || $service['available_slots'] <= 0) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Услуга недоступна для записи'
            ];
            Functions::redirect('/services.php');
            return;
        }
        
        try {
            $pdo = Database::getInstance()->getConnection();
            
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
                'message' => 'Запись на услугу успешно оформлена'
            ];
            
            Functions::redirect('/profile.php');
        } catch (\Exception $e) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Ошибка при оформлении записи: ' . $e->getMessage()
            ];
            Functions::redirect('/services.php');
        }
    }

    public function addReview(): void
    {
        Auth::requireLogin();
        
        $serviceId = $_POST['service_id'] ?? 0;
        $rating = $_POST['rating'] ?? 0;
        $comment = $_POST['comment'] ?? '';
        
        if ($rating < 1 || $rating > 5) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Рейтинг должен быть от 1 до 5'
            ];
            Functions::redirect('/service-detail.php?id=' . $serviceId);
            return;
        }
        
        if (empty($comment)) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Введите текст отзыва'
            ];
            Functions::redirect('/service-detail.php?id=' . $serviceId);
            return;
        }
        
        $this->serviceModel->addReview($serviceId, Auth::id(), $rating, $comment);
        
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Отзыв отправлен на модерацию'
        ];
        
        Functions::redirect('/service-detail.php?id=' . $serviceId);
    }
}
