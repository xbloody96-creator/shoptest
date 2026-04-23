<?php
$currentUser = $_SESSION['user'] ?? null;
$isAuth = isset($currentUser);
?>
<header class="header">
    <div class="header-container">
        <a href="/" class="logo">
            <div class="logo-icon">🎮</div>
            <span>GameStore</span>
        </a>
        
        <nav>
            <ul class="nav-menu">
                <li><a href="/" class="nav-link">Главная</a></li>
                <li><a href="/#about" class="nav-link">О нас</a></li>
                <li><a href="/#promotions" class="nav-link">Акции</a></li>
                <li><a href="/products.php" class="nav-link">Товары</a></li>
                <li><a href="/news.php" class="nav-link">Новости</a></li>
                <li><a href="/#search" class="nav-link">Поиск</a></li>
            </ul>
        </nav>
        
        <div class="header-actions">
            <button class="theme-toggle" title="Темная тема">🌙</button>
            <button class="accessibility-toggle" title="Режим для слабовидящих">🔍</button>
            
            <?php if ($isAuth): ?>
                <a href="/profile.php" class="btn btn-secondary">👤 Кабинет</a>
                <a href="/cart.php" class="btn btn-primary">🛒 Корзина</a>
            <?php else: ?>
                <a href="/login.php" class="btn btn-secondary">Вход</a>
                <a href="/register.php" class="btn btn-primary">Регистрация</a>
            <?php endif; ?>
        </div>
    </div>
</header>
