<?php

namespace App\Helpers;

class Auth
{
    private static ?array $currentUser = null;
    
    public static function login(string $emailOrLogin, string $password): bool
    {
        $db = \App\Config\Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM users WHERE (email = :login OR login = :login) AND is_active = 1");
        $stmt->execute(['login' => $emailOrLogin]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            self::$currentUser = $user;
            self::createSession($user['id']);
            return true;
        }
        
        return false;
    }
    
    public static function logout(): void
    {
        session_destroy();
        self::$currentUser = null;
    }
    
    public static function register(array $data): array
    {
        $validator = Validator::make($data);
        
        $validator
            ->required('email', 'Email обязателен')
            ->email('email', 'Некорректный email')
            ->required('login', 'Логин обязателен')
            ->minLength('login', 3, 'Логин должен быть не менее 3 символов')
            ->maxLength('login', 50, 'Логин должен быть не более 50 символов')
            ->required('full_name', 'ФИО обязательно')
            ->required('nickname', 'Никнейм обязателен')
            ->required('birth_date', 'Дата рождения обязательна')
            ->date('birth_date', 'Некорректный формат даты')
            ->custom('birth_date', fn($date) => {
                $year = (int)explode('-', $date)[0];
                return $year >= 1940;
            }, 'Дата рождения не может быть старше 1940 года')
            ->required('gender', 'Пол обязателен')
            ->custom('gender', fn($g) => in_array($g, ['male', 'female', 'other']), 'Некорректное значение пола')
            ->required('password', 'Пароль обязателен')
            ->minLength('password', 6, 'Пароль должен быть не менее 6 символов')
            ->required('password_confirm', 'Подтверждение пароля обязательно')
            ->matches('password_confirm', 'password', 'Пароли не совпадают');
        
        if ($validator->fails()) {
            return ['success' => false, 'errors' => $validator->errors()];
        }
        
        $db = \App\Config\Database::getInstance()->getConnection();
        
        // Проверка на существование email или login
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email OR login = :login");
        $stmt->execute(['email' => $data['email'], 'login' => $data['login']]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'errors' => ['email' => ['Пользователь с таким email или логином уже существует']]];
        }
        
        // Обработка аватарки
        $avatarPath = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $avatarPath = self::uploadAvatar($_FILES['avatar']);
        }
        
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("
            INSERT INTO users (email, login, password_hash, full_name, nickname, birth_date, gender, avatar)
            VALUES (:email, :login, :password, :full_name, :nickname, :birth_date, :gender, :avatar)
        ");
        
        $stmt->execute([
            'email' => $data['email'],
            'login' => $data['login'],
            'password' => $passwordHash,
            'full_name' => $data['full_name'],
            'nickname' => $data['nickname'],
            'birth_date' => $data['birth_date'],
            'gender' => $data['gender'],
            'avatar' => $avatarPath
        ]);
        
        return ['success' => true, 'user_id' => $db->lastInsertId()];
    }
    
    private static function uploadAvatar(array $file): ?string
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            return null;
        }
        
        if ($file['size'] > $maxSize) {
            return null;
        }
        
        $uploadDir = __DIR__ . '/../../public/assets/images/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('avatar_') . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return 'assets/images/uploads/' . $filename;
        }
        
        return null;
    }
    
    private static function createSession(int $userId): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $db = \App\Config\Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            INSERT INTO sessions (user_id, token, ip_address, user_agent, expires_at)
            VALUES (:user_id, :token, :ip, :agent, :expires)
        ");
        
        $stmt->execute([
            'user_id' => $userId,
            'token' => $token,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'expires' => $expiresAt
        ]);
        
        $_SESSION['auth_token'] = $token;
        $_SESSION['user_id'] = $userId;
    }
    
    public static function check(): bool
    {
        if (self::$currentUser !== null) {
            return true;
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['auth_token'])) {
            return false;
        }
        
        $db = \App\Config\Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT u.* FROM users u
            JOIN sessions s ON u.id = s.user_id
            WHERE s.token = :token AND s.expires_at > NOW() AND u.is_active = 1
        ");
        
        $stmt->execute(['token' => $_SESSION['auth_token']]);
        $user = $stmt->fetch();
        
        if ($user) {
            self::$currentUser = $user;
            return true;
        }
        
        return false;
    }
    
    public static function user(): ?array
    {
        if (self::check()) {
            return self::$currentUser;
        }
        return null;
    }
    
    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user && ($user['is_admin'] ?? false);
    }
    
    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /login.php');
            exit;
        }
    }
    
    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            header('Location: /');
            exit;
        }
    }
}
