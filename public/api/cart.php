<?php

require_once '../init.php';

header('Content-Type: application/json');

use App\Helpers\Auth;

$pdo = \App\Config\Database::getInstance()->getConnection();
$user = Auth::user();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        $productId = intval($_POST['product_id'] ?? 0);
        $quantity = max(1, intval($_POST['quantity'] ?? 1));
        
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Неверный ID товара']);
            exit;
        }
        
        if ($user) {
            // Для авторизованных - в БД
            $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = :user_id AND product_id = :product_id");
            $stmt->execute(['user_id' => $user['id'], 'product_id' => $productId]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + :qty WHERE id = :id");
                $stmt->execute(['qty' => $quantity, 'id' => $existing['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)");
                $stmt->execute(['user_id' => $user['id'], 'product_id' => $productId, 'quantity' => $quantity]);
            }
        } else {
            // Для гостей - в сессии
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            $found = false;
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['product_id'] == $productId) {
                    $item['quantity'] += $quantity;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $_SESSION['cart'][] = [
                    'product_id' => $productId,
                    'quantity' => $quantity
                ];
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Товар добавлен в корзину']);
        break;
        
    case 'update':
        $productId = intval($_POST['product_id'] ?? 0);
        $quantity = max(0, intval($_POST['quantity'] ?? 0));
        
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Неверный ID товара']);
            exit;
        }
        
        if ($quantity <= 0) {
            // Удаляем если количество 0
            if ($user) {
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = :user_id AND product_id = :product_id");
                $stmt->execute(['user_id' => $user['id'], 'product_id' => $productId]);
            } else {
                $_SESSION['cart'] = array_filter($_SESSION['cart'] ?? [], fn($item) => $item['product_id'] != $productId);
            }
        } else {
            if ($user) {
                $stmt = $pdo->prepare("UPDATE cart SET quantity = :qty WHERE user_id = :user_id AND product_id = :product_id");
                $stmt->execute(['qty' => $quantity, 'user_id' => $user['id'], 'product_id' => $productId]);
            } else {
                foreach ($_SESSION['cart'] as &$item) {
                    if ($item['product_id'] == $productId) {
                        $item['quantity'] = $quantity;
                        break;
                    }
                }
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Количество обновлено']);
        break;
        
    case 'remove':
        $productId = intval($_POST['product_id'] ?? 0);
        
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Неверный ID товара']);
            exit;
        }
        
        if ($user) {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = :user_id AND product_id = :product_id");
            $stmt->execute(['user_id' => $user['id'], 'product_id' => $productId]);
        } else {
            $_SESSION['cart'] = array_filter($_SESSION['cart'] ?? [], fn($item) => $item['product_id'] != $productId);
        }
        
        echo json_encode(['success' => true, 'message' => 'Товар удален из корзины']);
        break;
        
    case 'clear':
        if ($user) {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $user['id']]);
        } else {
            unset($_SESSION['cart']);
        }
        
        echo json_encode(['success' => true, 'message' => 'Корзина очищена']);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Неизвестное действие']);
}
