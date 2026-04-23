<?php

require_once 'init.php';

use App\Helpers\Auth;

// Проверка на администратора
Auth::requireAdmin();

$pdo = \App\Config\Database::getInstance()->getConnection();

// Статистика
$stats = [];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$stats['users'] = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
$stats['products'] = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status != 'completed' AND status != 'cancelled'");
$stats['orders'] = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid'");
$stats['revenue'] = $stmt->fetch()['total'] ?? 0;

// Последние заказы
$stmt = $pdo->query("SELECT o.*, u.email, u.nickname FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC LIMIT 10");
$recentOrders = $stmt->fetchAll();

$pageTitle = 'Админ-панель';
?>
<!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - GameStore Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        :root {
            --sidebar-width: 280px;
        }
        
        body {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            width: var(--sidebar-width);
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            padding: 20px;
        }
        
        .admin-logo {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            margin-bottom: 30px;
            text-decoration: none;
            color: var(--text-primary);
        }
        
        .admin-logo img {
            width: 45px;
            height: 45px;
            border-radius: var(--radius);
        }
        
        .admin-logo span {
            font-weight: 700;
            font-size: 1.3rem;
        }
        
        .admin-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .admin-menu li {
            margin-bottom: 5px;
        }
        
        .admin-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            color: var(--text-primary);
            transition: var(--transition);
            font-weight: 500;
        }
        
        .admin-menu a:hover, .admin-menu a.active {
            background: var(--primary-color);
            color: white;
        }
        
        .admin-menu-icon {
            font-size: 1.3rem;
        }
        
        .admin-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 30px;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .admin-title {
            font-size: 2rem;
            color: var(--text-primary);
        }
        
        .admin-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: var(--bg-color);
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
        }
        
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .stat-icon {
            font-size: 2.5rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .stat-label {
            color: var(--text-muted);
            font-size: 0.95rem;
        }
        
        .table-container {
            background: var(--bg-color);
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            overflow-x: auto;
        }
        
        .table-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text-primary);
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .data-table th {
            background: var(--bg-secondary);
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .data-table tr:hover {
            background: var(--bg-secondary);
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-pending { background: rgba(253, 203, 110, 0.2); color: #f39c12; }
        .status-processing { background: rgba(52, 152, 219, 0.2); color: #3498db; }
        .status-paid { background: rgba(0, 184, 148, 0.2); color: #00b894; }
        .status-completed { background: rgba(108, 92, 231, 0.2); color: #6c5ce7; }
        .status-cancelled { background: rgba(214, 48, 49, 0.2); color: #d63031; }
        
        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 0.85rem;
            transition: var(--transition);
        }
        
        .btn-view {
            background: rgba(108, 92, 231, 0.1);
            color: var(--primary-color);
        }
        
        .btn-view:hover {
            background: var(--primary-color);
            color: white;
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                z-index: 1000;
            }
            
            .admin-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Боковое меню -->
    <aside class="admin-sidebar">
        <a href="/admin/" class="admin-logo">
            <img src="https://via.placeholder.com/45x45/6c5ce7/ffffff?text=G" alt="Logo">
            <span>GameStore Admin</span>
        </a>
        
        <ul class="admin-menu">
            <li><a href="/admin/" class="active"><span class="admin-menu-icon">📊</span> Дашборд</a></li>
            <li><a href="/admin/products.php"><span class="admin-menu-icon">🎮</span> Товары</a></li>
            <li><a href="/admin/news.php"><span class="admin-menu-icon">📰</span> Новости</a></li>
            <li><a href="/admin/services.php"><span class="admin-menu-icon">🛠️</span> Услуги</a></li>
            <li><a href="/admin/orders.php"><span class="admin-menu-icon">📦</span> Заказы</a></li>
            <li><a href="/admin/users.php"><span class="admin-menu-icon">👥</span> Пользователи</a></li>
            <li><a href="/admin/reviews.php"><span class="admin-menu-icon">💬</span> Отзывы</a></li>
            <li><a href="/"><span class="admin-menu-icon">🏠</span> На сайт</a></li>
            <li><a href="/logout.php"><span class="admin-menu-icon">🚪</span> Выход</a></li>
        </ul>
    </aside>
    
    <!-- Основной контент -->
    <main class="admin-content">
        <div class="admin-header">
            <h1 class="admin-title">Дашборд</h1>
            <div class="admin-user">
                <div style="text-align: right;">
                    <div style="font-weight: 600;"><?= htmlspecialchars(Auth::user()['nickname'] ?? 'Admin') ?></div>
                    <div style="font-size: 0.85rem; color: var(--text-muted);">Администратор</div>
                </div>
                <div class="user-avatar"><?= strtoupper(substr(Auth::user()['nickname'] ?? 'A', 0, 1)) ?></div>
            </div>
        </div>
        
        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-value"><?= $stats['users'] ?></div>
                        <div class="stat-label">Пользователей</div>
                    </div>
                    <div class="stat-icon">👥</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-value"><?= $stats['products'] ?></div>
                        <div class="stat-label">Товаров</div>
                    </div>
                    <div class="stat-icon">🎮</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-value"><?= $stats['orders'] ?></div>
                        <div class="stat-label">Активных заказов</div>
                    </div>
                    <div class="stat-icon">📦</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <div class="stat-value"><?= number_format($stats['revenue'], 0, '.', ' ') ?> ₽</div>
                        <div class="stat-label">Выручка</div>
                    </div>
                    <div class="stat-icon">💰</div>
                </div>
            </div>
        </div>
        
        <!-- Последние заказы -->
        <div class="table-container">
            <h2 class="table-title">Последние заказы</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Номер заказа</th>
                        <th>Клиент</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th>Дата</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['order_number']) ?></td>
                        <td><?= htmlspecialchars($order['email'] ?? $order['customer_email']) ?></td>
                        <td><?= number_format($order['total_amount'], 0, '.', ' ') ?> ₽</td>
                        <td>
                            <span class="status-badge status-<?= $order['status'] ?>">
                                <?= $order['status'] ?>
                            </span>
                        </td>
                        <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                        <td>
                            <button class="btn-action btn-view">Просмотр</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
