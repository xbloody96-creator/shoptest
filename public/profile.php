<?php

require_once 'init.php';

use App\Helpers\Auth;
use App\Models\Order;
use App\Models\Favorite;

// Проверка авторизации
Auth::requireLogin();

$user = Auth::user();

// Получение данных о заказах
$orderModel = new Order();
$currentOrders = [];
$previousOrders = $orderModel->getUserOrders($user['id'], 5);

// Получение сессий пользователя
$db = \App\Config\Database::getInstance()->getConnection();
$stmt = $db->prepare("
    SELECT * FROM sessions 
    WHERE user_id = :user_id 
    ORDER BY last_activity DESC
");
$stmt->execute(['user_id' => $user['id']]);
$sessions = $stmt->fetchAll();

// Получение избранных товаров
$stmt = $db->prepare("
    SELECT f.*, p.name, p.price, p.images 
    FROM favorites f
    LEFT JOIN products p ON f.product_id = p.id
    WHERE f.user_id = :user_id AND f.product_id IS NOT NULL
    LIMIT 5
");
$stmt->execute(['user_id' => $user['id']]);
$favorites = $stmt->fetchAll();

// Получение истории просмотров
$stmt = $db->prepare("
    SELECT vh.*, p.name, p.price, p.images 
    FROM view_history vh
    LEFT JOIN products p ON vh.product_id = p.id
    WHERE vh.user_id = :user_id AND vh.product_id IS NOT NULL
    ORDER BY vh.viewed_at DESC
    LIMIT 5
");
$stmt->execute(['user_id' => $user['id']]);
$viewHistory = $stmt->fetchAll();

$pageTitle = 'Личный кабинет';

// Модифицируем header для авторизованного пользователя
ob_start();
?>
<!DOCTYPE html>
<html lang="ru" data-theme="light" data-accessibility="normal">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= $GLOBALS['siteName'] ?></title>
    <link rel="stylesheet" href="/assets/css/main.css">
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
                        <li><a href="/">Главная</a></li>
                        <li><a href="/products.php">Товары</a></li>
                        <li><a href="/news.php">Новости</a></li>
                        <li><a href="/#about">О нас</a></li>
                        <li><a href="/#contacts">Контакты</a></li>
                    </ul>
                </nav>
                
                <div class="header-controls">
                    <button class="theme-toggle" title="Переключить тему">🌙</button>
                    <button class="accessibility-toggle" title="Режим для слабовидящих">🔍</button>
                    
                    <a href="/cart.php" class="btn btn-secondary" style="position: relative;">
                        🛒 Корзина
                        <span class="cart-count" style="position: absolute; top: -8px; right: -8px; background: var(--primary-color); color: white; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; display: flex; align-items: center; justify-content: center;">0</span>
                    </a>
                    
                    <a href="/logout.php" class="btn btn-danger">Выход</a>
                </div>
            </div>
        </div>
    </header>
    
    <main>
<?php
$headerContent = ob_get_clean();
echo $headerContent;
?>

<style>
    .profile-container {
        padding: 40px 20px;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .profile-header {
        display: flex;
        gap: 30px;
        align-items: center;
        background: var(--bg-secondary);
        padding: 30px;
        border-radius: var(--radius);
        margin-bottom: 30px;
    }
    
    .profile-avatar {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid var(--primary-color);
        box-shadow: var(--shadow-lg);
    }
    
    .profile-info h1 {
        margin-bottom: 10px;
        color: var(--primary-color);
    }
    
    .profile-info p {
        margin: 5px 0;
        color: var(--text-muted);
    }
    
    .profile-section {
        background: var(--bg-color);
        border-radius: var(--radius);
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: var(--shadow);
    }
    
    .profile-section h2 {
        margin-bottom: 20px;
        color: var(--primary-color);
        font-size: 1.5rem;
        border-bottom: 2px solid var(--border-color);
        padding-bottom: 10px;
    }
    
    .sessions-table, .orders-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .sessions-table th, .sessions-table td,
    .orders-table th, .orders-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }
    
    .sessions-table th, .orders-table th {
        background: var(--bg-secondary);
        font-weight: 600;
    }
    
    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .status-active {
        background: rgba(0, 184, 148, 0.1);
        color: var(--success-color);
    }
    
    .status-pending {
        background: rgba(253, 203, 110, 0.1);
        color: var(--warning-color);
    }
    
    .status-completed {
        background: rgba(108, 92, 231, 0.1);
        color: var(--primary-color);
    }
    
    .favorites-grid, .history-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .favorite-item, .history-item {
        background: var(--bg-secondary);
        border-radius: var(--radius-sm);
        overflow: hidden;
        transition: var(--transition);
    }
    
    .favorite-item:hover, .history-item:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow);
    }
    
    .favorite-item img, .history-item img {
        width: 100%;
        height: 120px;
        object-fit: cover;
    }
    
    .favorite-item-info, .history-item-info {
        padding: 12px;
    }
    
    .favorite-item-title, .history-item-title {
        font-size: 0.95rem;
        font-weight: 600;
        margin-bottom: 5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .favorite-item-price, .history-item-price {
        color: var(--primary-color);
        font-weight: bold;
    }
</style>

<div class="profile-container">
    <!-- Шапка профиля -->
    <div class="profile-header fade-in">
        <img 
            src="<?= $user['avatar'] ? '/' . htmlspecialchars($user['avatar']) : 'https://via.placeholder.com/150x150/6c5ce7/ffffff?text=' . urlencode(substr($user['nickname'], 0, 1)) ?>" 
            alt="Аватар" 
            class="profile-avatar"
        >
        <div class="profile-info">
            <h1><?= htmlspecialchars($user['full_name']) ?></h1>
            <p><strong>Никнейм:</strong> <?= htmlspecialchars($user['nickname']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            <p><strong>Дата рождения:</strong> <?= date('d.m.Y', strtotime($user['birth_date'])) ?></p>
            <p><strong>Пол:</strong> <?= $user['gender'] === 'male' ? 'Мужской' : ($user['gender'] === 'female' ? 'Женский' : 'Другой') ?></p>
        </div>
    </div>
    
    <!-- Активные сессии -->
    <div class="profile-section fade-in">
        <h2>🔐 Сеансы и устройства</h2>
        <table class="sessions-table">
            <thead>
                <tr>
                    <th>IP адрес</th>
                    <th>Устройство</th>
                    <th>Последняя активность</th>
                    <th>Истекает</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $session): ?>
                <tr>
                    <td><?= htmlspecialchars($session['ip_address'] ?? 'Неизвестно') ?></td>
                    <td><?= htmlspecialchars(mb_substr($session['user_agent'] ?? 'Неизвестно', 0, 50)) ?>...</td>
                    <td><?= date('d.m.Y H:i', strtotime($session['last_activity'])) ?></td>
                    <td><?= date('d.m.Y H:i', strtotime($session['expires_at'])) ?></td>
                    <td>
                        <span class="status-badge <?= strtotime($session['expires_at']) > time() ? 'status-active' : 'status-pending' ?>">
                            <?= strtotime($session['expires_at']) > time() ? 'Активен' : 'Истек' ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Текущие заказы -->
    <?php if (!empty($currentOrders)): ?>
    <div class="profile-section fade-in">
        <h2>📦 Текущий заказ</h2>
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Номер заказа</th>
                    <th>Дата</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($currentOrders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['order_number']) ?></td>
                    <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                    <td><?= number_format($order['total_amount'], 0, ',', ' ') ?> ₽</td>
                    <td>
                        <span class="status-badge status-<?= $order['status'] ?>">
                            <?= $order['status'] === 'pending' ? 'В обработке' : ($order['status'] === 'processing' ? 'Обрабатывается' : $order['status']) ?>
                        </span>
                    </td>
                    <td>
                        <a href="/order-detail.php?id=<?= $order['id'] ?>" class="btn btn-outline" style="padding: 5px 15px; font-size: 0.9rem;">Подробнее</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <!-- Избранные товары -->
    <?php if (!empty($favorites)): ?>
    <div class="profile-section fade-in">
        <h2>❤️ Избранное</h2>
        <div class="favorites-grid">
            <?php foreach ($favorites as $item): 
                $images = json_decode($item['images'], true) ?? [];
                $imageUrl = !empty($images) ? $images[0] : 'https://via.placeholder.com/200x120/6c5ce7/ffffff?text=Product';
            ?>
            <div class="favorite-item">
                <img src="<?= $imageUrl ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                <div class="favorite-item-info">
                    <div class="favorite-item-title"><?= htmlspecialchars($item['name']) ?></div>
                    <div class="favorite-item-price"><?= number_format($item['price'], 0, ',', ' ') ?> ₽</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- История просмотров -->
    <?php if (!empty($viewHistory)): ?>
    <div class="profile-section fade-in">
        <h2>👁️ Последние просмотры</h2>
        <div class="history-grid">
            <?php foreach ($viewHistory as $item): 
                $images = json_decode($item['images'], true) ?? [];
                $imageUrl = !empty($images) ? $images[0] : 'https://via.placeholder.com/200x120/00cec9/ffffff?text=Product';
            ?>
            <div class="history-item">
                <img src="<?= $imageUrl ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                <div class="history-item-info">
                    <div class="history-item-title"><?= htmlspecialchars($item['name']) ?></div>
                    <div class="history-item-price"><?= number_format($item['price'], 0, ',', ' ') ?> ₽</div>
                    <small style="color: var(--text-muted);"><?= date('d.m.Y H:i', strtotime($item['viewed_at'])) ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Предыдущие заказы -->
    <div class="profile-section fade-in">
        <h2>📋 История заказов</h2>
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Номер заказа</th>
                    <th>Дата</th>
                    <th>Сумма</th>
                    <th>Статус оплаты</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($previousOrders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['order_number']) ?></td>
                    <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                    <td><?= number_format($order['total_amount'], 0, ',', ' ') ?> ₽</td>
                    <td>
                        <span class="status-badge status-<?= $order['payment_status'] ?>">
                            <?= $order['payment_status'] === 'paid' ? 'Оплачен' : ($order['payment_status'] === 'pending' ? 'Ожидает оплаты' : $order['payment_status']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-badge status-<?= $order['status'] ?>">
                            <?= $order['status'] === 'completed' ? 'Завершен' : ($order['status'] === 'cancelled' ? 'Отменен' : $order['status']) ?>
                        </span>
                    </td>
                    <td>
                        <a href="/order-detail.php?id=<?= $order['id'] ?>" class="btn btn-outline" style="padding: 5px 15px; font-size: 0.9rem;">Подробнее</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../src/views/layouts/footer.php'; ?>
