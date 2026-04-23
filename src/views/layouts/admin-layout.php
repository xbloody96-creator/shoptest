<!DOCTYPE html>
<html lang="ru" data-theme="<?= $_COOKIE['theme'] ?? 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Админ-панель' ?> - GameStore</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/dark-theme.css">
    <link rel="stylesheet" href="/assets/css/accessibility.css">
    <style>
        .admin-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 260px;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            padding: 1.5rem;
            overflow-y: auto;
            z-index: 100;
        }
        
        .admin-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
        }
        
        .admin-logo img {
            width: 40px;
            height: 40px;
        }
        
        .admin-logo span {
            font-size: 1.25rem;
            font-weight: bold;
            color: var(--text-color);
        }
        
        .admin-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .admin-nav-item {
            margin-bottom: 0.5rem;
        }
        
        .admin-nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .admin-nav-link:hover,
        .admin-nav-link.active {
            background: var(--primary-color);
            color: white;
        }
        
        .admin-nav-link svg {
            width: 20px;
            height: 20px;
        }
        
        .admin-content {
            margin-left: 260px;
            padding: 2rem;
            min-height: 100vh;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .admin-header h1 {
            font-size: 1.75rem;
            color: var(--text-color);
            margin: 0;
        }
        
        .admin-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .admin-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .admin-user-info {
            text-align: right;
        }
        
        .admin-user-name {
            font-weight: 600;
            color: var(--text-color);
            font-size: 0.95rem;
        }
        
        .admin-user-role {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-card-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .stat-card-icon.blue { background: rgba(99, 102, 241, 0.1); color: #6366f1; }
        .stat-card-icon.green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .stat-card-icon.orange { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .stat-card-icon.pink { background: rgba(236, 72, 153, 0.1); color: #ec4899; }
        
        .stat-card-value {
            font-size: 1.75rem;
            font-weight: bold;
            color: var(--text-color);
            margin-bottom: 0.25rem;
        }
        
        .stat-card-label {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .table-container {
            background: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .table-header h2 {
            margin: 0;
            font-size: 1.25rem;
            color: var(--text-color);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        th {
            background: var(--bg-secondary);
            font-weight: 600;
            color: var(--text-color);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            color: var(--text-muted);
        }
        
        tr:hover {
            background: var(--bg-secondary);
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-badge.success { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-badge.warning { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .status-badge.danger { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .status-badge.info { background: rgba(99, 102, 241, 0.1); color: #6366f1; }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-icon {
            padding: 0.5rem;
            border: none;
            background: var(--border-color);
            color: var(--text-color);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-icon:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-icon.delete:hover {
            background: #ef4444;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-header h3 {
            margin: 0;
            color: var(--text-color);
        }
        
        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--bg-secondary);
            color: var(--text-color);
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            
            .admin-sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .admin-content {
                margin-left: 0;
            }
            
            .admin-mobile-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Боковая панель -->
    <aside class="admin-sidebar">
        <div class="admin-logo">
            <img src="/assets/images/logo.svg" alt="GameStore">
            <span>Admin Panel</span>
        </div>
        
        <ul class="admin-nav">
            <li class="admin-nav-item">
                <a href="/admin/index.php" class="admin-nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                    </svg>
                    Дашборд
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="/admin/products.php" class="admin-nav-link <?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/>
                        <path d="M16 10a4 4 0 0 1-8 0"/>
                    </svg>
                    Товары
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="/admin/services.php" class="admin-nav-link <?= basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                    </svg>
                    Услуги
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="/admin/news.php" class="admin-nav-link <?= basename($_SERVER['PHP_SELF']) == 'news.php' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 20H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v1m2 13a2 2 0 0 1-2-2V7m2 13a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                    </svg>
                    Новости
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="/admin/orders.php" class="admin-nav-link <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                    Заказы
                </a>
            </li>
        </ul>
    </aside>
    
    <!-- Основной контент -->
    <main class="admin-content">
        <header class="admin-header">
            <h1><?= $pageTitle ?? 'Админ-панель' ?></h1>
            
            <div class="admin-user">
                <div class="admin-user-info">
                    <div class="admin-user-name"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Администратор') ?></div>
                    <div class="admin-user-role">Администратор</div>
                </div>
                <img src="https://picsum.photos/40/40?random=admin" alt="Avatar" class="admin-user-avatar">
                <a href="/logout.php" class="btn btn-sm btn-outline-primary" style="margin-left: 1rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                </a>
            </div>
        </header>
        
        <?= $content ?? '' ?>
    </main>
    
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/theme.js"></script>
</body>
</html>
