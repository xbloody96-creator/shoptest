<?php

namespace App\Helpers;

class Auth
{
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_login'] = $user['login'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_nickname'] = $user['nickname'];
        $_SESSION['user_avatar'] = $user['avatar'];
        $_SESSION['logged_in'] = true;
        
        // Сохраняем сессию в БД
        self::saveSession($user['id']);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'login' => $_SESSION['user_login'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'nickname' => $_SESSION['user_nickname'] ?? null,
            'avatar' => $_SESSION['user_avatar'] ?? null
        ];
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
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
            header('Location: /index.php');
            exit;
        }
    }

    private static function saveSession(int $userId): void
    {
        try {
            $pdo = \App\Config\Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO sessions (user_id, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            $stmt->execute([$userId, $ipAddress, $userAgent]);
        } catch (\Exception $e) {
            // Логируем ошибку, но не прерываем вход
            error_log("Ошибка сохранения сессии: " . $e->getMessage());
        }
    }

    public static function redirectIfLogged(string $url = '/index.php'): void
    {
        if (self::check()) {
            header("Location: $url");
            exit;
        }
    }
}

// Функции-помощники
function isLoggedIn(): bool
{
    return Auth::check();
}

function currentUser(): ?array
{
    return Auth::user();
}

function currentUserId(): ?int
{
    return Auth::id();
}

function requireLogin(): void
{
    Auth::requireLogin();
}

function requireAdmin(): void
{
    Auth::requireAdmin();
}
