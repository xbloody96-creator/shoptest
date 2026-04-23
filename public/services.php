<?php
// services.php - Страница услуг

session_start();
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/helpers/functions.php';
require_once __DIR__ . '/../src/helpers/Auth.php';

use App\Controllers\ServiceController;

$controller = new ServiceController();
$services = $controller->index();
$pageTitle = 'Услуги';

include __DIR__ . '/../src/views/layouts/header.php';
?>

<style>
    .services-page {
        min-height: 80vh;
        padding: 40px 20px;
    }
    
    .services-header {
        text-align: center;
        margin-bottom: 50px;
    }
    
    .services-title {
        font-size: 2.5rem;
        color: var(--text-color);
        margin-bottom: 15px;
    }
    
    .services-subtitle {
        color: var(--text-muted);
        font-size: 1.1rem;
        max-width: 600px;
        margin: 0 auto;
    }
    
    .services-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 30px;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .service-card {
        background: var(--bg-color);
        border-radius: var(--radius);
        overflow: hidden;
        transition: var(--transition);
        box-shadow: var(--shadow-md);
    }
    
    .service-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-lg);
    }
    
    .service-image {
        width: 100%;
        height: 220px;
        object-fit: cover;
    }
    
    .service-content {
        padding: 25px;
    }
    
    .service-title {
        font-size: 1.3rem;
        color: var(--text-color);
        margin: 0 0 10px;
    }
    
    .service-description {
        color: var(--text-muted);
        font-size: 0.95rem;
        line-height: 1.6;
        margin: 0 0 20px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .service-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 15px;
        border-top: 1px solid var(--border-color);
    }
    
    .service-price {
        color: var(--primary-color);
        font-weight: 700;
        font-size: 1.3rem;
    }
    
    .service-rating {
        display: flex;
        align-items: center;
        gap: 5px;
        color: #f1c40f;
    }
    
    .no-services {
        text-align: center;
        padding: 80px 20px;
        color: var(--text-muted);
    }
    
    .no-services-icon {
        font-size: 4rem;
        margin-bottom: 20px;
        opacity: 0.5;
    }
    
    @media (max-width: 768px) {
        .services-grid {
            grid-template-columns: 1fr;
        }
        
        .services-title {
            font-size: 2rem;
        }
    }
</style>

<div class="services-page fade-in">
    <div class="services-header">
        <h1 class="services-title">⚡ Наши услуги</h1>
        <p class="services-subtitle">
            Профессиональная помощь в играх, бустинг, обучение и другие услуги от опытных игроков
        </p>
    </div>
    
    <?php if (empty($services)): ?>
    <div class="no-services">
        <div class="no-services-icon">🎮</div>
        <h2>Услуги скоро появятся</h2>
        <p>Мы正在添加新的服务，请稍后再来查看</p>
    </div>
    <?php else: ?>
    <div class="services-grid">
        <?php foreach ($services as $service): ?>
        <div class="service-card glass-card">
            <a href="/service-detail.php?id=<?= $service['id'] ?>" style="text-decoration: none; color: inherit;">
                <img 
                    src="<?= htmlspecialchars($service['image_url'] ?? 'https://via.placeholder.com/400x250') ?>" 
                    alt="<?= htmlspecialchars($service['name']) ?>"
                    class="service-image"
                >
                <div class="service-content">
                    <h3 class="service-title"><?= htmlspecialchars($service['name']) ?></h3>
                    <p class="service-description">
                        <?= htmlspecialchars(mb_substr($service['description'] ?? '', 0, 150)) ?>...
                    </p>
                    <div class="service-meta">
                        <span class="service-price">
                            от <?= number_format($service['price'], 0, '.', ' ') ?> ₽
                        </span>
                        <?php if (!empty($service['rating'])): ?>
                        <div class="service-rating">
                            ⭐ <?= number_format($service['rating'], 1) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../src/views/layouts/footer.php'; ?>
