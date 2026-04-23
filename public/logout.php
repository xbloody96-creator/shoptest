<?php
// logout.php - Выход из аккаунта

session_start();

// Удаление всех данных сессии
$_SESSION = array();

// Удаление cookie сессии
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Удаление remember_token
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Разрушение сессии
session_destroy();

// Перенаправление на главную страницу
header('Location: /');
exit;
