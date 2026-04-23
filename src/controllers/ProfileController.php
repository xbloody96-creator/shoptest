<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Functions;
use App\Models\User;
use App\Config\Database;

class ProfileController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function show(): void
    {
        Auth::requireLogin();
        
        $userId = Auth::id();
        $user = $this->userModel->findById($userId);
        
        if (!$user) {
            Auth::logout();
            Functions::redirect('/login.php');
        }
        
        $sessions = $this->userModel->getLastSessions($userId);
        $orders = $this->userModel->getUserOrders($userId);
        $currentOrder = $this->userModel->getCurrentOrder($userId);
        $favorites = $this->userModel->getFavorites($userId);
        $lastViews = $this->userModel->getLastViews($userId);
        
        include __DIR__ . '/../../public/profile.php';
    }

    public function update(): void
    {
        Auth::requireLogin();
        
        $userId = Auth::id();
        $data = $_POST;
        
        // Валидация данных
        $errors = [];
        
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Некорректный email';
        }
        
        if (!empty($data['birth_date'])) {
            $birthDate = new \DateTime($data['birth_date']);
            $minDate = new \DateTime('1940-01-01');
            if ($birthDate < $minDate) {
                $errors[] = 'Дата рождения не может быть раньше 1940 года';
            }
        }
        
        if (!empty($errors)) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => implode('<br>', $errors)
            ];
            Functions::redirect('/profile.php');
        }
        
        // Обновление данных
        $updateData = [
            'full_name' => $data['full_name'] ?? null,
            'nickname' => $data['nickname'] ?? null,
            'email' => $data['email'] ?? null,
            'gender' => $data['gender'] ?? null,
            'birth_date' => $data['birth_date'] ?? null
        ];
        
        // Обработка аватара
        if (!empty($_FILES['avatar']['name'])) {
            $avatarPath = $this->uploadAvatar($_FILES['avatar']);
            if ($avatarPath) {
                $this->userModel->updateAvatar($userId, $avatarPath);
            }
        }
        
        $this->userModel->update($userId, array_filter($updateData));
        
        // Обновление сессии
        $updatedUser = $this->userModel->findById($userId);
        if ($updatedUser) {
            $_SESSION['user_nickname'] = $updatedUser['nickname'];
            $_SESSION['user_email'] = $updatedUser['email'];
            $_SESSION['user_avatar'] = $updatedUser['avatar'];
        }
        
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Профиль успешно обновлен'
        ];
        
        Functions::redirect('/profile.php');
    }

    public function changePassword(): void
    {
        Auth::requireLogin();
        
        $userId = Auth::id();
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $user = $this->userModel->findById($userId);
        
        if (!password_verify($currentPassword, $user['password'])) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Текущий пароль неверен'
            ];
            Functions::redirect('/profile.php');
        }
        
        if (strlen($newPassword) < 6) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Новый пароль должен быть не менее 6 символов'
            ];
            Functions::redirect('/profile.php');
        }
        
        if ($newPassword !== $confirmPassword) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Пароли не совпадают'
            ];
            Functions::redirect('/profile.php');
        }
        
        $this->userModel->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]);
        
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Пароль успешно изменен'
        ];
        
        Functions::redirect('/profile.php');
    }

    private function uploadAvatar(array $file): ?string
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        if (!in_array($file['type'], $allowedTypes)) {
            return null;
        }
        
        if ($file['size'] > $maxSize) {
            return null;
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . Auth::id() . '_' . time() . '.' . $extension;
        $uploadPath = __DIR__ . '/../../public/assets/images/uploads';
        
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath . '/' . $filename)) {
            return '/assets/images/uploads/' . $filename;
        }
        
        return null;
    }

    public function addToFavorites(int $newsId): bool
    {
        Auth::requireLogin();
        
        try {
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO favorites (user_id, news_id) 
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE user_id = user_id
            ");
            
            return $stmt->execute([Auth::id(), $newsId]);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function removeFromFavorites(int $newsId): bool
    {
        Auth::requireLogin();
        
        try {
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND news_id = ?");
            return $stmt->execute([Auth::id(), $newsId]);
        } catch (\Exception $e) {
            return false;
        }
    }
}
