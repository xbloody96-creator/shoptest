<?php

namespace App\Helpers;

class Functions
{
    public static function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = preg_replace('~[^\p{Latin}\d]+~u', '-', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = preg_replace('~-+~', '-', $text);
        $text = trim($text, '-');
        return $text;
    }
    
    public static function formatPrice(float $price): string
    {
        return number_format($price, 2, ',', ' ') . ' ₽';
    }
    
    public static function formatDate(string $date, string $format = 'd.m.Y H:i'): string
    {
        return date($format, strtotime($date));
    }
    
    public static function generateOrderNumber(): string
    {
        return 'ORD-' . strtoupper(uniqid());
    }
    
    public static function getRatingStars(float $rating): string
    {
        $fullStars = floor($rating);
        $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
        $emptyStars = 5 - $fullStars - $halfStar;
        
        $stars = str_repeat('★', (int)$fullStars);
        if ($halfStar) {
            $stars .= '½';
        }
        $stars .= str_repeat('☆', (int)$emptyStars);
        
        return $stars;
    }
    
    public static function timeAgo(string $datetime): string
    {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return 'только что';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return "$mins мин. назад";
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return "$hours ч. назад";
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return "$days дн. назад";
        } else {
            return self::formatDate($datetime, 'd.m.Y');
        }
    }
    
    public static function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }
    
    public static function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    public static function sanitize(string $data): string
    {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    public static function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "$protocol://$host";
    }
    
    public static function asset(string $path): string
    {
        return self::getBaseUrl() . '/' . ltrim($path, '/');
    }
    
    public static function url(string $path): string
    {
        return self::getBaseUrl() . '/' . ltrim($path, '/');
    }
    
    public static function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    public static function getCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    public static function verifyCsrfToken(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
