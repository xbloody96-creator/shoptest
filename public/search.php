<?php
// search.php - Страница поиска

session_start();
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/helpers/functions.php';
require_once __DIR__ . '/../src/helpers/Auth.php';

use App\Controllers\SearchController;

$controller = new SearchController();
$results = $controller->search();

$query = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? 'all';
$pageTitle = 'Поиск' . ($query ? ': ' . htmlspecialchars($query) : '');

include __DIR__ . '/../src/views/layouts/header.php';
?>

<style>
    .search-page {
        min-height: 80vh;
        padding: 40px 20px;
    }
    
    .search-header {
        max-width: 1200px;
        margin: 0 auto 40px;
    }
    
    .search-form-large {
        display: flex;
        gap: 15px;
        max-width: 800px;
        margin: 0 auto;
    }
    
    .search-input-large {
        flex: 1;
        padding: 15px 25px;
        font-size: 1.1rem;
        border: 2px solid var(--border-color);
        border-radius: var(--radius);
        background: var(--bg-color);
        color: var(--text-color);
    }
    
    .search-input-large:focus {
        outline: none;
        border-color: var(--primary-color);
    }
    
    .search-filters {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .filter-btn {
        padding: 8px 20px;
        border: 2px solid var(--border-color);
        background: var(--bg-color);
        color: var(--text-color);
        border-radius: var(--radius);
        cursor: pointer;
        transition: var(--transition);
        text-decoration: none;
        font-size: 0.9rem;
    }
    
    .filter-btn:hover {
        border-color: var(--primary-color);
        color: var(--primary-color);
    }
    
    .filter-btn.active {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }
    
    .search-results {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .results-section {
        margin-bottom: 50px;
    }
    
    .section-title {
        font-size: 1.5rem;
        margin-bottom: 25px;
        color: var(--text-color);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .section-title::before {
        content: '';
        width: 4px;
        height: 24px;
        background: var(--primary-color);
        border-radius: 2px;
    }
    
    .results-count {
        color: var(--text-muted);
        font-size: 0.9rem;
        margin-left: auto;
    }
    
    .no-results {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-muted);
    }
    
    .no-results-icon {
        font-size: 4rem;
        margin-bottom: 20px;
        opacity: 0.5;
    }
    
    .empty-query {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-query h2 {
        color: var(--text-color);
        margin-bottom: 15px;
    }
    
    .empty-query p {
        color: var(--text-muted);
        max-width: 500px;
        margin: 0 auto;
    }
</style>

<div class="search-page fade-in">
    <div class="search-header">
        <h1 style="text-align: center; margin-bottom: 30px; color: var(--text-color);">
            🔍 Поиск по сайту
        </h1>
        
        <form class="search-form-large" method="GET" action="">
            <input 
                type="text" 
                name="q" 
                class="search-input-large" 
                placeholder="Что вы ищете? (игры, ключи, аккаунты, новости)"
                value="<?= htmlspecialchars($query) ?>"
                required
            >
            <button type="submit" class="btn btn-primary">Найти</button>
        </form>
        
        <?php if (!empty($query)): ?>
        <div class="search-filters">
            <a href="?q=<?= urlencode($query) ?>&type=all" 
               class="filter-btn <?= $type === 'all' ? 'active' : '' ?>">
                Всё
            </a>
            <a href="?q=<?= urlencode($query) ?>&type=products" 
               class="filter-btn <?= $type === 'products' ? 'active' : '' ?>">
                🎮 Товары
            </a>
            <a href="?q=<?= urlencode($query) ?>&type=news" 
               class="filter-btn <?= $type === 'news' ? 'active' : '' ?>">
                📰 Новости
            </a>
            <a href="?q=<?= urlencode($query) ?>&type=services" 
               class="filter-btn <?= $type === 'services' ? 'active' : '' ?>">
                ⚡ Услуги
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="search-results">
        <?php if (empty($query)): ?>
        <div class="empty-query">
            <div class="no-results-icon">🔍</div>
            <h2>Введите поисковый запрос</h2>
            <p>Ищите игры, ключи активации, аккаунты, новости и услуги по всему сайту</p>
        </div>
        <?php else: ?>
        
        <?php $hasResults = !empty($results['products']) || !empty($results['news']) || !empty($results['services']); ?>
        
        <?php if (!$hasResults): ?>
        <div class="no-results">
            <div class="no-results-icon">😕</div>
            <h2>Ничего не найдено</h2>
            <p>По запросу "<?= htmlspecialchars($query) ?>" не найдено результатов.</p>
            <p style="margin-top: 15px;">Попробуйте изменить запрос или выбрать другую категорию.</p>
        </div>
        <?php else: ?>
        
        <!-- Товары -->
        <?php if ($type === 'all' || $type === 'products'): ?>
        <div class="results-section">
            <h2 class="section-title">
                🎮 Товары
                <span class="results-count"><?= count($results['products']) ?> найдено</span>
            </h2>
            
            <?php if (empty($results['products'])): ?>
            <p style="color: var(--text-muted); padding: 20px 0;">Нет результатов в этой категории</p>
            <?php else: ?>
            <div class="products-grid">
                <?php foreach ($results['products'] as $product): ?>
                <div class="product-card glass-card" style="padding: 0; overflow: hidden;">
                    <a href="/product-detail.php?id=<?= $product['id'] ?>" style="text-decoration: none; color: inherit;">
                        <img src="<?= htmlspecialchars($product['image_url'] ?? 'https://via.placeholder.com/300x200') ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>"
                             style="width: 100%; height: 180px; object-fit: cover;">
                        <div style="padding: 20px;">
                            <h3 style="margin: 0 0 10px; font-size: 1.1rem; color: var(--text-color);">
                                <?= htmlspecialchars($product['name']) ?>
                            </h3>
                            <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0 0 15px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?= htmlspecialchars(mb_substr($product['description'] ?? '', 0, 100)) ?>...
                            </p>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: var(--primary-color); font-weight: 700; font-size: 1.2rem;">
                                    <?= number_format($product['price'], 0, '.', ' ') ?> ₽
                                </span>
                                <span class="btn btn-sm btn-primary">Подробнее →</span>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Новости -->
        <?php if ($type === 'all' || $type === 'news'): ?>
        <div class="results-section">
            <h2 class="section-title">
                📰 Новости
                <span class="results-count"><?= count($results['news']) ?> найдено</span>
            </h2>
            
            <?php if (empty($results['news'])): ?>
            <p style="color: var(--text-muted); padding: 20px 0;">Нет результатов в этой категории</p>
            <?php else: ?>
            <div class="news-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px;">
                <?php foreach ($results['news'] as $news): ?>
                <div class="news-card glass-card" style="padding: 0; overflow: hidden;">
                    <a href="/news-detail.php?id=<?= $news['id'] ?>" style="text-decoration: none; color: inherit;">
                        <img src="<?= htmlspecialchars($news['image_url'] ?? 'https://via.placeholder.com/400x250') ?>" 
                             alt="<?= htmlspecialchars($news['title']) ?>"
                             style="width: 100%; height: 200px; object-fit: cover;">
                        <div style="padding: 20px;">
                            <h3 style="margin: 0 0 10px; font-size: 1.2rem; color: var(--text-color);">
                                <?= htmlspecialchars($news['title']) ?>
                            </h3>
                            <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0 0 15px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                <?= htmlspecialchars(mb_substr(strip_tags($news['content'] ?? ''), 0, 150)) ?>...
                            </p>
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; color: var(--text-muted);">
                                <span><?= date('d.m.Y', strtotime($news['created_at'])) ?></span>
                                <span class="btn btn-sm btn-primary">Читать →</span>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Услуги -->
        <?php if ($type === 'all' || $type === 'services'): ?>
        <div class="results-section">
            <h2 class="section-title">
                ⚡ Услуги
                <span class="results-count"><?= count($results['services']) ?> найдено</span>
            </h2>
            
            <?php if (empty($results['services'])): ?>
            <p style="color: var(--text-muted); padding: 20px 0;">Нет результатов в этой категории</p>
            <?php else: ?>
            <div class="services-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px;">
                <?php foreach ($results['services'] as $service): ?>
                <div class="service-card glass-card" style="padding: 25px;">
                    <a href="/service-detail.php?id=<?= $service['id'] ?>" style="text-decoration: none; color: inherit;">
                        <h3 style="margin: 0 0 10px; font-size: 1.2rem; color: var(--text-color);">
                            <?= htmlspecialchars($service['name']) ?>
                        </h3>
                        <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0 0 15px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <?= htmlspecialchars(mb_substr($service['description'] ?? '', 0, 100)) ?>...
                        </p>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: var(--primary-color); font-weight: 700;">
                                от <?= number_format($service['price'], 0, '.', ' ') ?> ₽
                            </span>
                            <span class="btn btn-sm btn-primary">Записаться →</span>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../src/views/layouts/footer.php'; ?>
