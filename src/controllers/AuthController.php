<?php

namespace App\Controllers;

use App\Config\Database;
use PDOException;

class AuthController
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Регистрация пользователя
     */
    public function register($data)
    {
        // Валидация данных
        $errors = [];
        
        if (empty($data['email'])) {
            $errors[] = 'Email обязателен для заполнения';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Некорректный формат email';
        }
        
        if (empty($data['login'])) {
            $errors[] = 'Логин обязателен для заполнения';
        }
        
        if (empty($data['fio'])) {
            $errors[] = 'ФИО обязательно для заполнения';
        }
        
        if (empty($data['nickname'])) {
            $errors[] = 'Никнейм обязателен для заполнения';
        }
        
        if (empty($data['birth_date'])) {
            $errors[] = 'Дата рождения обязательна';
        } else {
            $birthDate = new \DateTime($data['birth_date']);
            $minDate = new \DateTime('1940-12-31');
            if ($birthDate < $minDate) {
                $errors[] = 'Дата рождения не может быть старше 1940 года';
            }
        }
        
        if (empty($data['gender'])) {
            $errors[] = 'Пол обязателен для выбора';
        }
        
        if (empty($data['password'])) {
            $errors[] = 'Пароль обязателен';
        } elseif (strlen($data['password']) < 6) {
            $errors[] = 'Пароль должен содержать минимум 6 символов';
        }
        
        if ($data['password'] !== ($data['password_confirm'] ?? '')) {
            $errors[] = 'Пароли не совпадают';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Проверка на существование пользователя с таким email или логином
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? OR login = ?");
        $stmt->execute([$data['email'], $data['login']]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'errors' => ['Пользователь с таким email или логином уже существует']];
        }
        
        // Хэширование пароля
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Обработка аватарки
        $avatarPath = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $avatarPath = $this->uploadAvatar($_FILES['avatar']);
        }
        
        // Сохранение пользователя в БД
        try {
            $stmt = $this->db->prepare("
                INSERT INTO users (email, login, fio, nickname, birth_date, gender, password, avatar) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['email'],
                $data['login'],
                $data['fio'],
                $data['nickname'],
                $data['birth_date'],
                $data['gender'],
                $passwordHash,
                $avatarPath
            ]);
            
            return ['success' => true, 'user_id' => $this->db->lastInsertId()];
            
        } catch (PDOException $e) {
            return ['success' => false, 'errors' => ['Ошибка при регистрации: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Авторизация пользователя
     */
    public function login($email, $password, $remember = false)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? OR login = ?");
            $stmt->execute([$email, $email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'error' => 'Пользователь не найден'];
            }
            
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'error' => 'Неверный пароль'];
            }
            
            // Создание сессии
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_login'] = $user['login'];
            $_SESSION['user_email'] = $user['email'];
            
            // Сохранение сессии в БД
            $this->saveSession($user['id']);
            
            // Remember me
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (86400 * 30), '/');
                
                $stmt = $this->db->prepare("INSERT INTO remember_tokens (user_id, token) VALUES (?, ?)");
                $stmt->execute([$user['id'], $token]);
            }
            
            return ['success' => true, 'user' => $user];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Ошибка авторизации: ' . $e->getMessage()];
        }
    }
    
    /**
     * Выход из системы
     */
    public function logout()
    {
        // Удаление токена remember me
        if (isset($_COOKIE['remember_token'])) {
            $stmt = $this->db->prepare("DELETE FROM remember_tokens WHERE token = ?");
            $stmt->execute([$_COOKIE['remember_token']]);
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // Очистка сессии
        session_destroy();
        
        return true;
    }
    
    /**
     * Восстановление пароля
     */
    public function forgotPassword($email)
    {
        try {
            $stmt = $this->db->prepare("SELECT id, email FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'error' => 'Пользователь с таким email не найден'];
            }
            
            // Генерация токена сброса
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt = $this->db->prepare("
                INSERT INTO password_resets (user_id, token, expires_at) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE token = ?, expires_at = ?
            ");
            $stmt->execute([$user['id'], $token, $expiresAt, $token, $expiresAt]);
            
            // Отправка email (в реальном проекте)
            // $this->sendResetEmail($user['email'], $token);
            
            return [
                'success' => true, 
                'message' => 'Инструкции по сбросу пароля отправлены на email',
                'debug_token' => $token // Для тестирования
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Ошибка: ' . $e->getMessage()];
        }
    }
    
    /**
     * Сброс пароля
     */
    public function resetPassword($token, $newPassword)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT pr.user_id, pr.expires_at 
                FROM password_resets pr 
                WHERE pr.token = ? AND pr.expires_at > NOW()
            ");
            $stmt->execute([$token]);
            $reset = $stmt->fetch();
            
            if (!$reset) {
                return ['success' => false, 'error' => 'Токен недействителен или истек'];
            }
            
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $this->db->beginTransaction();
            
            // Обновление пароля
            $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$passwordHash, $reset['user_id']]);
            
            // Удаление использованного токена
            $stmt = $this->db->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);
            
            $this->db->commit();
            
            return ['success' => true, 'message' => 'Пароль успешно изменен'];
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Ошибка: ' . $e->getMessage()];
        }
    }
    
    /**
     * Загрузка аватарки
     */
    private function uploadAvatar($file)
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new \Exception('Недопустимый формат файла');
        }
        
        if ($file['size'] > $maxSize) {
            throw new \Exception('Файл слишком большой');
        }
        
        $uploadDir = __DIR__ . '/../../public/assets/images/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('avatar_') . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new \Exception('Ошибка при загрузке файла');
        }
        
        return 'assets/images/uploads/avatars/' . $filename;
    }
    
    /**
     * Сохранение сессии в БД
     */
    private function saveSession($userId)
    {
        try {
            $sessionId = session_id();
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            $stmt = $this->db->prepare("
                INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, last_activity) 
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE last_activity = NOW()
            ");
            $stmt->execute([$userId, $sessionId, $ipAddress, $userAgent]);
            
        } catch (PDOException $e) {
            // Логирование ошибки
        }
    }
    
    /**
     * Получение текущего пользователя
     */
    public function getCurrentUser()
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
}
