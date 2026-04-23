<?php

require_once 'init.php';

use App\Helpers\Auth;

$pdo = \App\Config\Database::getInstance()->getConnection();
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: /news.php');
    exit;
}

// Получение новости
$stmt = $pdo->prepare("SELECT n.*, u.nickname as author_name, u.avatar_path as author_avatar FROM news n 
    LEFT JOIN users u ON n.author_id = u.id 
    WHERE n.slug = :slug");
$stmt->execute(['slug' => $slug]);
$news = $stmt->fetch();

// Если новости нет в БД, создаем тестовые данные
if (!$news) {
    $news = [
        'id' => 1,
        'title' => 'Новые игры этой недели: обзор релизов',
        'slug' => $slug,
        'short_description' => 'Самые ожидаемые игровые новинки этой недели',
        'content' => '<h2>Введение</h2><p>На этой неделе вышло несколько долгожданных игр, которые привлекли внимание миллионов игроков по всему миру.</p><h2>Топ-5 новинок</h2><ol><li><strong>Cyberpunk 2077: Phantom Liberty</strong> - новое дополнение к культовой RPG</li><li><strong>Spider-Man 2</strong> - продолжение популярного экшена от Insomniac</li><li><strong>Alan Wake 2</strong> - сиквел легендарного триллера</li><li><strong>Super Mario Bros. Wonder</strong> - новая часть классической серии</li><li><strong>Assassin\'s Creed Mirage</strong> - возвращение к истокам серии</li></ol><h2>Заключение</h2><p>Эта неделя оказалась богатой на интересные релизы. Каждая из представленных игр заслуживает внимания и имеет свои уникальные особенности.</p>',
        'main_image' => 'https://picsum.photos/seed/newsdetail/1000/600',
        'author_name' => 'AdminMaster',
        'author_avatar' => null,
        'view_count' => rand(100, 5000),
        'rating' => 4.5,
        'rating_count' => 125,
        'is_promo' => true,
        'published_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
    ];
    
    // Увеличение счетчика просмотров
    if (isset($news['id'])) {
        $stmt = $pdo->prepare("UPDATE news SET view_count = view_count + 1 WHERE id = :id");
        $stmt->execute(['id' => $news['id']]);
    }
}

$pageTitle = htmlspecialchars($news['title']);
include __DIR__ . '/../src/views/layouts/header.php';
?>

<style>
    .news-detail-container {
        padding: 40px 20px;
        max-width: 900px;
        margin: 0 auto;
    }
    
    .news-header-image {
        width: 100%;
        border-radius: var(--radius);
        overflow: hidden;
        margin-bottom: 30px;
        box-shadow: var(--shadow-lg);
    }
    
    .news-header-image img {
        width: 100%;
        height: auto;
        display: block;
    }
    
    .news-badge {
        display: inline-block;
        background: var(--primary-color);
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 15px;
    }
    
    .news-badge.promo {
        background: var(--danger-color);
    }
    
    .news-title {
        font-size: 2.5rem;
        color: var(--text-primary);
        margin-bottom: 20px;
        line-height: 1.3;
    }
    
    .news-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 0;
        border-top: 1px solid var(--border-color);
        border-bottom: 1px solid var(--border-color);
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .news-author {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .author-avatar-large {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 1.2rem;
    }
    
    .author-info .author-name {
        font-weight: 600;
        color: var(--text-primary);
    }
    
    .author-info .publish-date {
        font-size: 0.9rem;
        color: var(--text-muted);
    }
    
    .news-stats {
        display: flex;
        gap: 20px;
    }
    
    .stat-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-muted);
    }
    
    .news-content {
        background: var(--bg-color);
        border-radius: var(--radius);
        padding: 40px;
        box-shadow: var(--shadow);
        margin-bottom: 30px;
        line-height: 1.8;
        color: var(--text-primary);
    }
    
    .news-content h2 {
        color: var(--primary-color);
        margin-top: 30px;
        margin-bottom: 15px;
    }
    
    .news-content h2:first-child {
        margin-top: 0;
    }
    
    .news-content p {
        margin-bottom: 20px;
    }
    
    .news-content ol, .news-content ul {
        margin-bottom: 20px;
        padding-left: 25px;
    }
    
    .news-content li {
        margin-bottom: 10px;
    }
    
    .news-actions {
        display: flex;
        gap: 15px;
        margin-bottom: 40px;
        flex-wrap: wrap;
    }
    
    .rating-section {
        background: var(--bg-secondary);
        border-radius: var(--radius);
        padding: 30px;
        text-align: center;
        margin-bottom: 40px;
    }
    
    .rating-stars {
        font-size: 3rem;
        color: #f1c40f;
        margin-bottom: 15px;
    }
    
    .rating-text {
        color: var(--text-muted);
        margin-bottom: 20px;
    }
    
    .rating-buttons {
        display: flex;
        justify-content: center;
        gap: 15px;
    }
    
    .rating-btn {
        padding: 12px 25px;
        border: 2px solid var(--border-color);
        background: var(--bg-color);
        border-radius: var(--radius);
        cursor: pointer;
        font-size: 1.5rem;
        transition: var(--transition);
    }
    
    .rating-btn:hover {
        border-color: #f1c40f;
        background: rgba(241, 196, 15, 0.1);
    }
    
    .share-section {
        background: var(--bg-secondary);
        border-radius: var(--radius);
        padding: 25px;
        margin-bottom: 40px;
    }
    
    .share-section h3 {
        margin-bottom: 15px;
        color: var(--text-primary);
    }
    
    .share-buttons {
        display: flex;
        gap: 10px;
    }
    
    .share-btn {
        padding: 10px 20px;
        border: none;
        border-radius: var(--radius-sm);
        color: white;
        cursor: pointer;
        transition: var(--transition);
        font-weight: 600;
    }
    
    .share-vk { background: #0077FF; }
    .share-tg { background: #24A1DE; }
    .share-wa { background: #25D366; }
    
    .related-news {
        margin-top: 50px;
    }
    
    .related-title {
        font-size: 1.8rem;
        color: var(--primary-color);
        margin-bottom: 25px;
    }
    
    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--text-muted);
        text-decoration: none;
        margin-bottom: 20px;
        transition: var(--transition);
    }
    
    .back-link:hover {
        color: var(--primary-color);
    }
</style>

<div class="news-detail-container">
    <a href="/news.php" class="back-link">← Назад к новостям</a>
    
    <div class="news-header-image fade-in">
        <img src="<?= htmlspecialchars($news['main_image'] ?? 'https://picsum.photos/seed/news/1000/600') ?>" alt="<?= htmlspecialchars($news['title']) ?>">
    </div>
    
    <?php if ($news['is_promo']): ?>
    <span class="news-badge promo">🔥 Акция</span>
    <?php endif; ?>
    
    <h1 class="news-title fade-in"><?= htmlspecialchars($news['title']) ?></h1>
    
    <div class="news-meta fade-in">
        <div class="news-author">
            <div class="author-avatar-large"><?= strtoupper(substr($news['author_name'] ?? 'A', 0, 1)) ?></div>
            <div class="author-info">
                <div class="author-name"><?= htmlspecialchars($news['author_name'] ?? 'Автор') ?></div>
                <div class="publish-date"><?= date('d.m.Y H:i', strtotime($news['published_at'] ?? $news['created_at'])) ?></div>
            </div>
        </div>
        
        <div class="news-stats">
            <span class="stat-item">👁️ <?= $news['view_count'] ?? 0 ?></span>
            <span class="stat-item">⭐ <?= $news['rating'] ?? 0 ?> (<?= $news['rating_count'] ?? 0 ?>)</span>
            <span class="stat-item">💬 <?= rand(10, 100) ?></span>
        </div>
    </div>
    
    <article class="news-content fade-in">
        <?= $news['content'] ?? 'Содержимое новости отсутствует' ?>
    </article>
    
    <!-- Оценка новости -->
    <div class="rating-section fade-in">
        <h3 style="margin-bottom: 15px;">Оцените эту новость</h3>
        <div class="rating-stars">★★★★☆</div>
        <p class="rating-text">Средняя оценка: <strong><?= $news['rating'] ?? 0 ?></strong> из 5 (<?= $news['rating_count'] ?? 0 ?> оценок)</p>
        <div class="rating-buttons">
            <button class="rating-btn" onclick="rateNews(5)" title="Отлично">👍</button>
            <button class="rating-btn" onclick="rateNews(1)" title="Плохо">👎</button>
        </div>
    </div>
    
    <!-- Поделиться -->
    <div class="share-section fade-in">
        <h3>Поделиться новостью</h3>
        <div class="share-buttons">
            <button class="share-btn share-vk" onclick="shareToVK()">ВКонтакте</button>
            <button class="share-btn share-tg" onclick="shareToTG()">Telegram</button>
            <button class="share-btn share-wa" onclick="shareToWA()">WhatsApp</button>
        </div>
    </div>
    
    <!-- Похожие новости -->
    <div class="related-news fade-in">
        <h2 class="related-title">Читайте также</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <?php for ($i = 1; $i <= 3; $i++): ?>
            <a href="/news-detail.php?slug=news-<?= $i ?>" style="text-decoration: none;">
                <div style="background: var(--bg-secondary); border-radius: var(--radius); overflow: hidden; transition: var(--transition);">
                    <img src="https://picsum.photos/seed/related<?= $i ?>/400/250" alt="Related News" style="width: 100%; height: 150px; object-fit: cover;">
                    <div style="padding: 15px;">
                        <h4 style="color: var(--text-primary); font-size: 1rem; margin-bottom: 8px;">Похожая новость #<?= $i ?></h4>
                        <p style="color: var(--text-muted); font-size: 0.85rem;">Краткое описание похожей новости...</p>
                    </div>
                </div>
            </a>
            <?php endfor; ?>
        </div>
    </div>
</div>

<script>
function rateNews(rating) {
    showToast('Спасибо за оценку!', 'success');
}

function shareToVK() {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent('<?= addslashes($news['title']) ?>');
    window.open(`https://vk.com/share.php?url=${url}&title=${title}`, '_blank', 'width=600,height=400');
}

function shareToTG() {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent('<?= addslashes($news['title']) ?>');
    window.open(`https://t.me/share/url?url=${url}&text=${title}`, '_blank', 'width=600,height=400');
}

function shareToWA() {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent('<?= addslashes($news['title']) ?>');
    window.open(`https://api.whatsapp.com/send?text=${title}%20${url}`, '_blank', 'width=600,height=400');
}
</script>

<?php include __DIR__ . '/../src/views/layouts/footer.php'; ?>
