<!DOCTYPE html>
<html lang="ru" data-theme="light" data-accessibility="normal">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'GameStore' ?> - <?= $GLOBALS['siteName'] ?></title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-top">
                <a href="/" class="logo">
                    <img src="https://via.placeholder.com/50x50/6c5ce7/ffffff?text=G" alt="GameStore Logo">
                    <span><?= $GLOBALS['siteName'] ?></span>
                </a>
                
                <nav>
                    <ul class="nav-menu">
                        <li><a href="/register.php">Регистрация</a></li>
                        <li><a href="/login.php">Авторизация</a></li>
                        <li><a href="/profile.php">Личный кабинет</a></li>
                        <li><a href="/#about">О нас</a></li>
                        <li><a href="/#promotions">Акции</a></li>
                        <li><a href="/#search">Поиск</a></li>
                        <li><a href="/#contacts">Контакты</a></li>
                    </ul>
                </nav>
                
                <div class="header-controls">
                    <div class="search-bar">
                        <input type="text" class="search-input" placeholder="Поиск товаров...">
                        <button class="btn btn-primary">🔍</button>
                    </div>
                    
                    <button class="theme-toggle" title="Переключить тему">🌙</button>
                    <button class="accessibility-toggle" title="Режим для слабовидящих">🔍</button>
                    
                    <a href="/cart.php" class="btn btn-secondary" style="position: relative;">
                        🛒 Корзина
                        <span class="cart-count" style="position: absolute; top: -8px; right: -8px; background: var(--primary-color); color: white; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; display: flex; align-items: center; justify-content: center;">0</span>
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <main>
