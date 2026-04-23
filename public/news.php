<?php

require_once 'init.php';

use App\Helpers\Auth;

$pdo = \App\Config\Database::getInstance()->getConnection();

// Получение новостей
$stmt = $pdo->query("SELECT n.*, u.nickname as author_name FROM news n 
    LEFT JOIN users u ON n.author_id = u.id 
    WHERE n.is_published = 1 
    ORDER BY n.published_at DESC, n.created_at DESC");
$stmt->execute();
$newsList = $stmt->fetchAll();

// Если новостей нет в БД, создаем тестовые
if (empty($newsList)) {
    $newsList = [
        [
            'id' => 1,
            'title' => 'Новые игры этой недели: обзор релизов',
            'slug' => 'new-games-this-week',
            'short_description' => 'Самые ожидаемые игровые новинки этой недели',
            'content' => '<p>На этой неделе вышло несколько долгожданных игр...</p>',
            'main_image' => 'https://picsum.photos/seed/news1/800/500',
            'author_name' => 'AdminMaster',
            'view_count' => rand(100, 5000),
            'rating' => round(rand(35, 50) / 10, 1),
            'rating_count' => rand(10, 200),
            'is_promo' => true,
            'published_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ],
        [
            'id' => 2,
            'title' => 'Большая распродажа Steam: скидки до 90%',
            'slug' => 'steam-sale-90-percent',
            'short_description' => 'Steam запустил масштабную распродажу популярных игр',
            'content' => '<p>Valve объявила о начале большой распродажи...</p>',
            'main_image' => 'https://picsum.photos/seed/news2/800/500',
            'author_name' => 'GameNews',
            'view_count' => rand(100, 5000),
            'rating' => round(rand(35, 50) / 10, 1),
            'rating_count' => rand(10, 200),
            'is_promo' => false,
            'published_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
            'created_at' => date('Y-m-d H:i:s', strtotime('-4 days'))
        ],
        [
            'id' => 3,
            'title' => 'Анонсирована новая часть Cyberpunk',
            'slug' => 'cyberpunk-sequel-announced',
            'short_description' => 'CD Projekt RED официально анонсировала сиквел',
            'content' => '<p>Польская студия CD Projekt RED сделала долгожданный анонс...</p>',
            'main_image' => 'https://picsum.photos/seed/news3/800/500',
            'author_name' => 'AdminMaster',
            'view_count' => rand(100, 5000),
            'rating' => round(rand(35, 50) / 10, 1),
            'rating_count' => rand(10, 200),
            'is_promo' => true,
            'published_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'created_at' => date('Y-m-d H:i:s', strtotime('-6 days'))
        ],
        [
            'id' => 4,
            'title' => 'Обновление цен на ключи активации',
            'slug' => 'price-update-keys',
            'short_description' => 'Изменение стоимости цифровых ключей',
            'content' => '<p>В связи с изменением курса валют мы вынуждены...</p>',
            'main_image' => 'https://picsum.photos/seed/news4/800/500',
            'author_name' => 'Support',
            'view_count' => rand(100, 5000),
            'rating' => round(rand(35, 50) / 10, 1),
            'rating_count' => rand(10, 200),
            'is_promo' => false,
            'published_at' => date('Y-m-d H:i:s', strtotime('-1 week')),
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 week'))
        ]
    ];
}

$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 9;
$totalNews = count($newsList);
$totalPages = ceil($totalNews / $itemsPerPage);
$newsPage = array_slice($newsList, ($currentPage - 1) * $itemsPerPage, $itemsPerPage);

$pageTitle = 'Новости';
include __DIR__ . '/../src/views/layouts/header.php';
?>

<style>
    .news-container {
        padding: 40px 20px;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .news-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .news-title {
        font-size: 2.5rem;
        color: var(--primary-color);
        margin-bottom: 10px;
    }
    
    .news-subtitle {
        color: var(--text-muted);
        font-size: 1.1rem;
    }
    
    .news-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 30px;
        margin-bottom: 40px;
    }
    
    @media (max-width: 768px) {
        .news-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .news-card {
        background: var(--bg-color);
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: var(--transition);
    }
    
    .news-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-lg);
    }
    
    .news-card-image {
        position: relative;
        height: 220px;
        overflow: hidden;
    }
    
    .news-card-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: var(--transition);
    }
    
    .news-card:hover .news-card-image img {
        transform: scale(1.1);
    }
    
    .news-badge {
        position: absolute;
        top: 15px;
        left: 15px;
        background: var(--primary-color);
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .news-badge.promo {
        background: var(--danger-color);
    }
    
    .news-card-content {
        padding: 25px;
    }
    
    .news-card-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        font-size: 0.85rem;
        color: var(--text-muted);
    }
    
    .news-card-title {
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 12px;
        color: var(--text-primary);
        line-height: 1.4;
    }
    
    .news-card-excerpt {
        color: var(--text-secondary);
        line-height: 1.7;
        margin-bottom: 20px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .news-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 15px;
        border-top: 1px solid var(--border-color);
    }
    
    .news-author {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .author-avatar {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 0.9rem;
    }
    
    .author-name {
        font-weight: 600;
        color: var(--text-primary);
        font-size: 0.9rem;
    }
    
    .news-stats {
        display: flex;
        gap: 15px;
        color: var(--text-muted);
        font-size: 0.85rem;
    }
    
    .stat-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .btn-read-more {
        display: inline-block;
        padding: 10px 20px;
        background: var(--primary-color);
        color: white;
        text-decoration: none;
        border-radius: var(--radius-sm);
        font-weight: 600;
        transition: var(--transition);
        margin-top: 15px;
    }
    
    .btn-read-more:hover {
        background: var(--primary-dark);
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 40px;
    }
    
    .pagination a, .pagination span {
        padding: 10px 15px;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        text-decoration: none;
        color: var(--text-primary);
        transition: var(--transition);
    }
    
    .pagination a:hover {
        border-color: var(--primary-color);
        color: var(--primary-color);
    }
    
    .pagination .active {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }
</style>

<div class="news-container">
    <div class="news-header fade-in">
        <h1 class="news-title">📰 Новости</h1>
        <p class="news-subtitle">Последние новости из мира игр и технологий</p>
    </div>
    
    <div class="news-grid">
        <?php foreach ($newsPage as $news): ?>
        <article class="news-card fade-in">
            <div class="news-card-image">
                <img src="<?= htmlspecialchars($news['main_image'] ?? 'https://picsum.photos/seed/news/800/500') ?>" alt="<?= htmlspecialchars($news['title']) ?>">
                <?php if ($news['is_promo']): ?>
                <span class="news-badge promo">🔥 Акция</span>
                <?php endif; ?>
            </div>
            <div class="news-card-content">
                <div class="news-card-meta">
                    <span>📅 <?= date('d.m.Y', strtotime($news['published_at'] ?? $news['created_at'])) ?></span>
                    <span>⭐ <?= $news['rating'] ?? 0 ?> (<?= $news['rating_count'] ?? 0 ?>)</span>
                </div>
                <h2 class="news-card-title"><?= htmlspecialchars($news['title']) ?></h2>
                <p class="news-card-excerpt"><?= htmlspecialchars($news['short_description'] ?? '') ?></p>
                
                <div class="news-card-footer">
                    <div class="news-author">
                        <div class="author-avatar"><?= strtoupper(substr($news['author_name'] ?? 'A', 0, 1)) ?></div>
                        <span class="author-name"><?= htmlspecialchars($news['author_name'] ?? 'Автор') ?></span>
                    </div>
                    <div class="news-stats">
                        <span class="stat-item">👁️ <?= $news['view_count'] ?? 0 ?></span>
                        <span class="stat-item">💬 <?= rand(5, 50) ?></span>
                    </div>
                </div>
                
                <a href="/news-detail.php?slug=<?= htmlspecialchars($news['slug']) ?>" class="btn-read-more">Читать далее →</a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    
    <!-- Пагинация -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($currentPage > 1): ?>
        <a href="?page=<?= $currentPage - 1 ?>">← Назад</a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == $currentPage): ?>
            <span class="active"><?= $i ?></span>
            <?php else: ?>
            <a href="?page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($currentPage < $totalPages): ?>
        <a href="?page=<?= $currentPage + 1 ?>">Вперед →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../src/views/layouts/footer.php'; ?>
