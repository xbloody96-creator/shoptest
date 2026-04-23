<!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Админ-панель') ?> - GameStore Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        :root { --sidebar-width: 280px; }
        body { display: flex; min-height: 100vh; }
        .admin-sidebar {
            width: var(--sidebar-width);
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            padding: 20px;
            z-index: 100;
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
        .admin-logo span { font-weight: 700; font-size: 1.3rem; }
        .admin-menu { list-style: none; padding: 0; margin: 0; }
        .admin-menu li { margin-bottom: 5px; }
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
        .admin-menu-icon { font-size: 1.3rem; }
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
        .admin-title { font-size: 2rem; color: var(--text-primary); }
        .alert {
            padding: 15px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
        }
        .alert-success { background: rgba(0, 184, 148, 0.1); color: var(--success-color); border: 1px solid var(--success-color); }
        .alert-error { background: rgba(214, 48, 49, 0.1); color: var(--danger-color); border: 1px solid var(--danger-color); }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary); }
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            background: var(--bg-color);
            color: var(--text-primary);
            font-size: 1rem;
        }
        .form-control:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1); }
        textarea.form-control { min-height: 120px; resize: vertical; }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background: var(--primary-color); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-danger { background: var(--danger-color); color: white; }
        .btn-danger:hover { background: #c0392b; }
        .btn-outline { background: transparent; border: 1px solid var(--border-color); color: var(--text-primary); }
        .btn-outline:hover { border-color: var(--primary-color); color: var(--primary-color); }
        .btn-sm { padding: 6px 12px; font-size: 0.85rem; }
        .data-table { width: 100%; border-collapse: collapse; background: var(--bg-color); border-radius: var(--radius); overflow: hidden; }
        .data-table th, .data-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border-color); }
        .data-table th { background: var(--bg-secondary); font-weight: 600; }
        .data-table tr:hover { background: var(--bg-secondary); }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .status-active { background: rgba(0, 184, 148, 0.2); color: #00b894; }
        .status-inactive { background: rgba(214, 48, 49, 0.2); color: #d63031; }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: var(--bg-color);
            border-radius: var(--radius);
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-title { font-size: 1.5rem; color: var(--text-primary); }
        .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); }
        @media (max-width: 768px) {
            .admin-sidebar { transform: translateX(-100%); }
            .admin-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <aside class="admin-sidebar">
        <a href="/admin/" class="admin-logo">
            <img src="https://via.placeholder.com/45x45/6c5ce7/ffffff?text=G" alt="Logo">
            <span>GameStore Admin</span>
        </a>
        <ul class="admin-menu">
            <li><a href="/admin/" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>"><span class="admin-menu-icon">📊</span> Дашборд</a></li>
            <li><a href="/admin/products.php" class="<?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>"><span class="admin-menu-icon">🎮</span> Товары</a></li>
            <li><a href="/admin/news.php" class="<?= basename($_SERVER['PHP_SELF']) == 'news.php' ? 'active' : '' ?>"><span class="admin-menu-icon">📰</span> Новости</a></li>
            <li><a href="/admin/services.php" class="<?= basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : '' ?>"><span class="admin-menu-icon">🛠️</span> Услуги</a></li>
            <li><a href="/admin/orders.php" class="<?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>"><span class="admin-menu-icon">📦</span> Заказы</a></li>
            <li><a href="/"><span class="admin-menu-icon">🏠</span> На сайт</a></li>
            <li><a href="/logout.php"><span class="admin-menu-icon">🚪</span> Выход</a></li>
        </ul>
    </aside>

    <main class="admin-content">
        <div class="admin-header">
            <h1 class="admin-title"><?= htmlspecialchars($pageTitle ?? 'Админ-панель') ?></h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <span style="color: var(--text-muted);"><?= htmlspecialchars(\App\Helpers\Auth::user()['nickname'] ?? 'Admin') ?></span>
                <a href="/logout.php" class="btn btn-outline btn-sm">Выход</a>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php echo $content ?? ''; ?>
    </main>

    <script>
        function openModal(modalId) { document.getElementById(modalId).classList.add('active'); }
        function closeModal(modalId) { document.getElementById(modalId).classList.remove('active'); }
        function editItem(id, name, price, description, stock, categoryId) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-price').value = price;
            document.getElementById('edit-description').value = description || '';
            document.getElementById('edit-stock').value = stock || 0;
            document.getElementById('edit-category-id').value = categoryId || '';
            openModal('editModal');
        }
    </script>
</body>
</html>
