<?php
// service-detail.php - Страница детали услуги

session_start();
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/helpers/functions.php';
require_once __DIR__ . '/../src/helpers/Auth.php';

use App\Controllers\ServiceController;
use App\Models\Service;
use App\Config\Database;

$id = $_GET['id'] ?? 0;
$controller = new ServiceController();
$serviceModel = new Service();

$service = $serviceModel->findById((int)$id);

if (!$service) {
    http_response_code(404);
    $pageTitle = 'Услуга не найдена';
    include __DIR__ . '/../src/views/layouts/header.php';
    ?>
    <div style="min-height: 60vh; display: flex; align-items: center; justify-content: center; text-align: center;">
        <div>
            <h1 style="font-size: 3rem; color: var(--text-color); margin-bottom: 20px;">😕</h1>
            <h2 style="color: var(--text-color); margin-bottom: 15px;">Услуга не найдена</h2>
            <p style="color: var(--text-muted); margin-bottom: 30px;">Запрошенная услуга не существует или была удалена</p>
            <a href="/services.php" class="btn btn-primary">← Вернуться к услугам</a>
        </div>
    </div>
    <?php
    include __DIR__ . '/../src/views/layouts/footer.php';
    exit;
}

$reviews = $serviceModel->getReviews((int)$id);
$relatedServices = $serviceModel->getPopular(4);

// Сохраняем просмотр
if (Auth::check()) {
    try {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO service_views (user_id, service_id, viewed_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([Auth::id(), $id]);
    } catch (\Exception $e) {
        // Игнорируем ошибку
    }
}

$pageTitle = htmlspecialchars($service['name']);
include __DIR__ . '/../src/views/layouts/header.php';
?>

<style>
    .service-detail-page {
        min-height: 80vh;
        padding: 40px 20px;
    }
    
    .service-detail-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .service-detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        margin-bottom: 50px;
    }
    
    .service-detail-image {
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow-lg);
    }
    
    .service-detail-image img {
        width: 100%;
        height: 400px;
        object-fit: cover;
    }
    
    .service-detail-info h1 {
        font-size: 2.5rem;
        color: var(--text-color);
        margin: 0 0 15px;
    }
    
    .service-detail-price {
        font-size: 2rem;
        color: var(--primary-color);
        font-weight: 700;
        margin: 20px 0;
    }
    
    .service-detail-description {
        color: var(--text-muted);
        line-height: 1.8;
        margin: 25px 0;
    }
    
    .service-detail-meta {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin: 25px 0;
        padding: 20px;
        background: var(--bg-secondary);
        border-radius: var(--radius);
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .meta-label {
        color: var(--text-muted);
        font-size: 0.9rem;
    }
    
    .meta-value {
        color: var(--text-color);
        font-weight: 600;
    }
    
    .service-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }
    
    .booking-form {
        background: var(--bg-secondary);
        padding: 25px;
        border-radius: var(--radius);
        margin-top: 30px;
    }
    
    .reviews-section {
        margin-top: 60px;
    }
    
    .reviews-title {
        font-size: 1.8rem;
        color: var(--text-color);
        margin-bottom: 30px;
    }
    
    .review-card {
        background: var(--bg-color);
        padding: 20px;
        border-radius: var(--radius);
        margin-bottom: 20px;
        box-shadow: var(--shadow-md);
    }
    
    .review-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .review-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .review-author {
        font-weight: 600;
        color: var(--text-color);
    }
    
    .review-rating {
        color: #f1c40f;
        margin-left: auto;
    }
    
    .review-text {
        color: var(--text-muted);
        line-height: 1.6;
    }
    
    .no-reviews {
        text-align: center;
        padding: 40px;
        color: var(--text-muted);
    }
    
    .add-review-form {
        background: var(--bg-secondary);
        padding: 25px;
        border-radius: var(--radius);
        margin-top: 30px;
    }
    
    @media (max-width: 768px) {
        .service-detail-grid {
            grid-template-columns: 1fr;
        }
        
        .service-detail-actions {
            flex-direction: column;
        }
    }
</style>

<div class="service-detail-page fade-in">
    <div class="service-detail-container">
        <a href="/services.php" style="color: var(--text-muted); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px;">
            ← Назад к услугам
        </a>
        
        <div class="service-detail-grid">
            <div class="service-detail-image">
                <img src="<?= htmlspecialchars($service['image_url'] ?? 'https://via.placeholder.com/600x400') ?>" 
                     alt="<?= htmlspecialchars($service['name']) ?>">
            </div>
            
            <div class="service-detail-info">
                <h1><?= htmlspecialchars($service['name']) ?></h1>
                
                <?php if (!empty($service['category_name'])): ?>
                <span style="color: var(--primary-color); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;">
                    <?= htmlspecialchars($service['category_name']) ?>
                </span>
                <?php endif; ?>
                
                <div class="service-detail-price">
                    от <?= number_format($service['price'], 0, '.', ' ') ?> ₽
                </div>
                
                <div class="service-detail-description">
                    <?= nl2br(htmlspecialchars($service['description'])) ?>
                </div>
                
                <div class="service-detail-meta">
                    <div class="meta-item">
                        <span class="meta-label">⏱️ Длительность:</span>
                        <span class="meta-value"><?= $service['duration'] ?? 60 ?> мин</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">📅 Доступно слотов:</span>
                        <span class="meta-value" style="color: <?= ($service['available_slots'] ?? 0) > 0 ? 'var(--success-color)' : 'var(--danger-color)' ?>">
                            <?= $service['available_slots'] ?? 0 ?>
                        </span>
                    </div>
                    <?php if (!empty($service['avg_rating'])): ?>
                    <div class="meta-item">
                        <span class="meta-label">⭐ Рейтинг:</span>
                        <span class="meta-value"><?= number_format($service['avg_rating'], 1) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (Auth::check()): ?>
                <div class="service-actions">
                    <form method="POST" action="/api/service-book.php" style="flex: 1;">
                        <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                        <button type="submit" class="btn btn-primary" style="width: 100%;" <?= ($service['available_slots'] ?? 0) <= 0 ? 'disabled' : '' ?>>
                            <?= ($service['available_slots'] ?? 0) <= 0 ? 'Нет мест' : 'Записаться на услугу' ?>
                        </button>
                    </form>
                </div>
                
                <div class="booking-form">
                    <h3 style="margin: 0 0 15px; color: var(--text-color);">📅 Запись на услугу</h3>
                    <form method="POST" action="/api/service-book.php">
                        <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                        
                        <div class="form-group">
                            <label class="form-label" for="booking_date">Желаемая дата и время</label>
                            <input 
                                type="datetime-local" 
                                id="booking_date" 
                                name="booking_date" 
                                class="form-control"
                                required
                                min="<?= date('Y-m-d\TH:i', strtotime('+1 hour')) ?>"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="comment">Комментарий (необязательно)</label>
                            <textarea 
                                id="comment" 
                                name="comment" 
                                class="form-control" 
                                rows="3"
                                placeholder="Пожелания по времени или другие детали"
                            ></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;" <?= ($service['available_slots'] ?? 0) <= 0 ? 'disabled' : '' ?>>
                            Оформить запись
                        </button>
                    </form>
                </div>
                <?php else: ?>
                <div style="background: var(--bg-secondary); padding: 20px; border-radius: var(--radius); text-align: center;">
                    <p style="color: var(--text-muted); margin-bottom: 15px;">Для записи на услугу необходимо авторизоваться</p>
                    <a href="/login.php" class="btn btn-primary">Войти</a>
                    <a href="/register.php" class="btn btn-outline" style="margin-left: 10px;">Регистрация</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Отзывы -->
        <div class="reviews-section">
            <h2 class="reviews-title">Отзывы (<?= count($reviews) ?>)</h2>
            
            <?php if (empty($reviews)): ?>
            <div class="no-reviews">
                <p>Пока нет отзывов. Будьте первым!</p>
            </div>
            <?php else: ?>
            <?php foreach ($reviews as $review): ?>
            <div class="review-card">
                <div class="review-header">
                    <img 
                        src="<?= htmlspecialchars($review['avatar'] ?? 'https://via.placeholder.com/50') ?>" 
                        alt="<?= htmlspecialchars($review['nickname']) ?>"
                        class="review-avatar"
                    >
                    <span class="review-author"><?= htmlspecialchars($review['nickname']) ?></span>
                    <span class="review-rating">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <?= $i < $review['rating'] ? '⭐' : '☆' ?>
                        <?php endfor; ?>
                    </span>
                </div>
                <p class="review-text"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                <small style="color: var(--text-muted); display: block; margin-top: 10px;">
                    <?= date('d.m.Y H:i', strtotime($review['created_at'])) ?>
                </small>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (Auth::check()): ?>
            <div class="add-review-form">
                <h3 style="margin: 0 0 15px; color: var(--text-color);">✍️ Оставить отзыв</h3>
                <form method="POST" action="/api/service-review.php">
                    <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="rating">Оценка</label>
                        <select id="rating" name="rating" class="form-control" required>
                            <option value="">Выберите оценку</option>
                            <option value="5">⭐⭐⭐⭐⭐ (5)</option>
                            <option value="4">⭐⭐⭐⭐ (4)</option>
                            <option value="3">⭐⭐⭐ (3)</option>
                            <option value="2">⭐⭐ (2)</option>
                            <option value="1">⭐ (1)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="comment">Текст отзыва</label>
                        <textarea 
                            id="comment" 
                            name="comment" 
                            class="form-control" 
                            rows="4"
                            placeholder="Расскажите о вашем опыте использования услуги"
                            required
                        ></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Отправить отзыв</button>
                    <small style="color: var(--text-muted); display: block; margin-top: 10px;">
                        Отзыв появится после модерации
                    </small>
                </form>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Похожие услуги -->
        <?php if (!empty($relatedServices)): ?>
        <div style="margin-top: 60px;">
            <h2 style="font-size: 1.8rem; color: var(--text-color); margin-bottom: 30px;">Похожие услуги</h2>
            <div class="services-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px;">
                <?php foreach ($relatedServices as $related): ?>
                <?php if ($related['id'] != $service['id']): ?>
                <div class="service-card glass-card" style="padding: 20px;">
                    <a href="/service-detail.php?id=<?= $related['id'] ?>" style="text-decoration: none; color: inherit;">
                        <img 
                            src="<?= htmlspecialchars($related['image_url'] ?? 'https://via.placeholder.com/300x200') ?>" 
                            alt="<?= htmlspecialchars($related['name']) ?>"
                            style="width: 100%; height: 160px; object-fit: cover; border-radius: var(--radius-sm); margin-bottom: 15px;"
                        >
                        <h3 style="margin: 0 0 10px; font-size: 1.1rem; color: var(--text-color);">
                            <?= htmlspecialchars($related['name']) ?>
                        </h3>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: var(--primary-color); font-weight: 700;">
                                от <?= number_format($related['price'], 0, '.', ' ') ?> ₽
                            </span>
                            <span class="btn btn-sm btn-primary">Подробнее →</span>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../src/views/layouts/footer.php'; ?>
