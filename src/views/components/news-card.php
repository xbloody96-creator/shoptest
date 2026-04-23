<?php
/**
 * Компонент карточки новости
 */
$news = $news ?? null;
if (!$news) return;
?>

<div class="news-card" data-news-id="<?= $news['id'] ?>">
    <div class="news-image-wrapper">
        <img src="<?= htmlspecialchars($news['image'] ?? 'https://picsum.photos/400/250?random=' . $news['id']) ?>" 
             alt="<?= htmlspecialchars($news['title']) ?>" 
             class="news-image">
        <?php if (!empty($news['is_promo'])): ?>
            <span class="news-badge promo">Акция</span>
        <?php endif; ?>
        <span class="news-date"><?= date('d.m.Y', strtotime($news['created_at'])) ?></span>
    </div>
    
    <div class="news-info">
        <h3 class="news-title"><?= htmlspecialchars($news['title']) ?></h3>
        
        <div class="news-meta">
            <span class="news-author">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
                <?= htmlspecialchars($news['author_name'] ?? 'Администратор') ?>
            </span>
            <span class="news-views">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                </svg>
                <?= $news['views'] ?? 0 ?>
            </span>
        </div>
        
        <p class="news-description"><?= htmlspecialchars(mb_substr($news['description'] ?? '', 0, 120)) ?><?= mb_strlen($news['description'] ?? '') > 120 ? '...' : '' ?></p>
        
        <div class="news-footer">
            <div class="news-rating">
                <?php 
                $rating = $news['rating'] ?? 0;
                for ($i = 1; $i <= 5; $i++):
                ?>
                    <svg class="star <?= $i <= $rating ? 'filled' : '' ?>" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                <?php endfor; ?>
                <span class="rating-count">(<?= $news['rating_count'] ?? 0 ?>)</span>
            </div>
            
            <a href="/news-detail.php?id=<?= $news['id'] ?>" class="btn btn-sm btn-primary">Читать далее</a>
        </div>
    </div>
</div>

<style>
.news-card {
    background: var(--card-bg);
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.news-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.news-image-wrapper {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.news-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.news-card:hover .news-image {
    transform: scale(1.05);
}

.news-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: bold;
    color: white;
    background: #ef4444;
}

.news-date {
    position: absolute;
    bottom: 10px;
    right: 10px;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.8rem;
}

.news-info {
    padding: 1.5rem;
}

.news-title {
    font-size: 1.2rem;
    font-weight: bold;
    margin-bottom: 0.75rem;
    color: var(--text-color);
    line-height: 1.4;
}

.news-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-bottom: 0.75rem;
}

.news-author,
.news-views {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.news-description {
    font-size: 0.95rem;
    color: var(--text-muted);
    line-height: 1.6;
    margin-bottom: 1rem;
}

.news-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.news-rating {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.star {
    color: #d1d5db;
}

.star.filled {
    color: #fbbf24;
}

.rating-count {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-left: 0.25rem;
}
</style>
