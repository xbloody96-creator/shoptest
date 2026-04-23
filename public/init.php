<?php

// Загрузка переменных окружения
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Автозагрузка классов
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Вспомогательные функции
require_once __DIR__ . '/../src/helpers/functions.php';

// Старт сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Глобальные переменные для шаблонов
$GLOBALS['siteName'] = $_ENV['SITE_NAME'] ?? 'GameStore';
$GLOBALS['siteUrl'] = $_ENV['SITE_URL'] ?? 'http://localhost';
