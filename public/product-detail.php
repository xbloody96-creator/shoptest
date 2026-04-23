<?php

require_once 'init.php';

use App\Helpers\Auth;

$pdo = \App\Config\Database::getInstance()->getConnection();
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: /products.php');
    exit;
}

// Получение товара
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.slug = :slug");
$stmt->execute(['slug' => $slug]);
$product = $stmt->fetch();

// Если товара нет в БД, создаем тестовые данные
if (!$product) {
    $product = [
        'id' => 1,
        'name' => 'Cyberpunk 2077',
        'slug' => $slug,
        'description' => '<p>Cyberpunk 2077 — это ролевая игра в открытом мире будущего, где вы играете за наемника Ви, стремящегося получить бессмертие.</p><p>Особенности:</p><ul><li>Открытый мир Найт-Сити</li><li>Глубокая система прокачки персонажа</li><li>Нелинейный сюжет с множеством концовок</li><li>Продвинутая боевая система</li></ul>',
        'short_description' => 'Ролевая игра в открытом мире будущего',
        'price' => 1999,
        'old_price' => 2999,
        'stock_quantity' => 50,
        'is_available' => true,
        'rating' => 4.5,
        'review_count' => 1250,
        'main_image' => 'https://picsum.photos/seed/cyberpunk/800/600',
        'images' => json_encode([
            'https://picsum.photos/seed/cyberpunk1/800/600',
            'https://picsum.photos/seed/cyberpunk2/800/600',
            'https://picsum.photos/seed/cyberpunk3/800/600'
        ]),
        'characteristics' => json_encode([
            'Платформа' => 'PC (Steam)',
            'Жанр' => 'RPG, Action',
            'Язык' => 'Русский, Английский',
            'Возрастной рейтинг' => '18+',
            'Тип доставки' => 'Цифровой ключ'
        ]),
        'category_name' => 'Игры'
    ];
}

// Увеличение счетчика просмотров
if ($product) {
    $stmt = $pdo->prepare("UPDATE products SET view_count = view_count + 1 WHERE id = :id");
    $stmt->execute(['id' => $product['id']]);
    
    // Добавление в историю просмотров для авторизованных пользователей
    $user = Auth::user();
    if ($user) {
        $stmt = $pdo->prepare("INSERT INTO view_history (user_id, product_id) VALUES (:user_id, :product_id) ON DUPLICATE KEY UPDATE viewed_at = CURRENT_TIMESTAMP");
        $stmt->execute(['user_id' => $user['id'], 'product_id' => $product['id']]);
    }
}

// Получение отзывов
$stmt = $pdo->prepare("SELECT r.*, u.nickname, u.avatar_path FROM reviews r 
    LEFT JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = :product_id AND r.status = 'approved'
    ORDER BY r.created_at DESC");
$stmt->execute(['product_id' => $product['id'] ?? 0]);
$reviews = $stmt->fetchAll();

// Если отзывов нет, создаем тестовые
if (empty($reviews) && $product) {
    $reviews = [
        [
            'id' => 1,
            'rating' => 5,
            'comment' => 'Отличная игра! Графика на высоте, сюжет захватывает с первых минут. Рекомендую всем любителям киберпанка!',
            'nickname' => 'GameMaster2024',
            'avatar_path' => null,
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ],
        [
            'id' => 2,
            'rating' => 4,
            'comment' => 'Хорошая игра, но есть небольшие баги. В целом доволен покупкой.',
            'nickname' => 'PlayerOne',
            'avatar_path' => null,
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
        ],
        [
            'id' => 3,
            'rating' => 5,
            'comment' => 'Лучшая RPG последних лет! Стоит каждого рубля.',
            'nickname' => 'CyberFan',
            'avatar_path' => null,
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 week'))
        ]
    ];
}

$pageTitle = htmlspecialchars($product['name']);
include __DIR__ . '/../src/views/layouts/header.php';
?>

<style>
    .product-detail-container {
        padding: 40px 20px;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .product-detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        margin-bottom: 40px;
    }
    
    @media (max-width: 768px) {
        .product-detail-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .product-gallery {
        position: sticky;
        top: 20px;
    }
    
    .main-image {
        width: 100%;
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow-lg);
        margin-bottom: 20px;
    }
    
    .main-image img {
        width: 100%;
        height: auto;
        display: block;
    }
    
    .thumbnail-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }
    
    .thumbnail {
        border-radius: var(--radius-sm);
        overflow: hidden;
        cursor: pointer;
        border: 2px solid transparent;
        transition: var(--transition);
    }
    
    .thumbnail:hover, .thumbnail.active {
        border-color: var(--primary-color);
    }
    
    .thumbnail img {
        width: 100%;
        height: 80px;
        object-fit: cover;
        display: block;
    }
    
    .product-info-detail h1 {
        font-size: 2rem;
        color: var(--primary-color);
        margin-bottom: 10px;
    }
    
    .product-meta {
        display: flex;
        gap: 15px;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    
    .product-category-badge {
        background: var(--bg-secondary);
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
        color: var(--text-muted);
    }
    
    .product-rating-large {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .stars-large {
        font-size: 1.5rem;
        color: #f1c40f;
    }
    
    .price-block {
        background: var(--bg-secondary);
        padding: 25px;
        border-radius: var(--radius);
        margin-bottom: 25px;
    }
    
    .current-price-large {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--primary-color);
    }
    
    .old-price-large {
        font-size: 1.5rem;
        color: var(--text-muted);
        text-decoration: line-through;
        margin-left: 15px;
    }
    
    .discount-badge {
        background: var(--danger-color);
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-weight: 600;
        margin-left: 10px;
    }
    
    .action-buttons {
        display: flex;
        gap: 15px;
        margin-top: 20px;
        flex-wrap: wrap;
    }
    
    .btn-action {
        flex: 1;
        min-width: 150px;
        padding: 15px 25px;
        border: none;
        border-radius: var(--radius);
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        font-size: 1rem;
    }
    
    .btn-buy {
        background: var(--primary-color);
        color: white;
    }
    
    .btn-buy:hover {
        background: var(--primary-dark);
    }
    
    .btn-cart-large {
        background: var(--success-color);
        color: white;
    }
    
    .btn-cart-large:hover {
        background: #00b894;
    }
    
    .btn-favorite {
        background: var(--bg-color);
        border: 2px solid var(--border-color);
        color: var(--text-primary);
    }
    
    .btn-favorite:hover {
        border-color: var(--danger-color);
        color: var(--danger-color);
    }
    
    .product-description, .product-characteristics, .product-reviews {
        background: var(--bg-color);
        border-radius: var(--radius);
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: var(--shadow);
    }
    
    .section-title {
        font-size: 1.5rem;
        color: var(--primary-color);
        margin-bottom: 20px;
        border-bottom: 2px solid var(--border-color);
        padding-bottom: 10px;
    }
    
    .characteristics-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .characteristics-table tr {
        border-bottom: 1px solid var(--border-color);
    }
    
    .characteristics-table tr:last-child {
        border-bottom: none;
    }
    
    .characteristics-table td {
        padding: 12px 0;
    }
    
    .characteristics-table td:first-child {
        font-weight: 600;
        color: var(--text-muted);
        width: 40%;
    }
    
    .review-card {
        background: var(--bg-secondary);
        border-radius: var(--radius-sm);
        padding: 20px;
        margin-bottom: 15px;
    }
    
    .review-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .reviewer-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .reviewer-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .reviewer-name {
        font-weight: 600;
        color: var(--text-primary);
    }
    
    .review-date {
        color: var(--text-muted);
        font-size: 0.9rem;
    }
    
    .review-rating {
        color: #f1c40f;
        font-size: 1.2rem;
    }
    
    .review-comment {
        color: var(--text-primary);
        line-height: 1.6;
    }
    
    .review-form {
        background: var(--bg-secondary);
        border-radius: var(--radius-sm);
        padding: 20px;
        margin-top: 20px;
    }
    
    .star-rating {
        display: flex;
        gap: 5px;
        margin-bottom: 15px;
    }
    
    .star-rating input {
        display: none;
    }
    
    .star-rating label {
        font-size: 2rem;
        color: var(--text-muted);
        cursor: pointer;
        transition: var(--transition);
    }
    
    .star-rating label:hover,
    .star-rating label:hover ~ label,
    .star-rating input:checked ~ label {
        color: #f1c40f;
    }
    
    .stock-status {
        display: inline-block;
        padding: 5px 15px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .stock-in {
        background: rgba(0, 184, 148, 0.1);
        color: var(--success-color);
    }
    
    .stock-out {
        background: rgba(214, 48, 49, 0.1);
        color: var(--danger-color);
    }
</style>

<div class="product-detail-container">
    <div class="product-detail-grid">
        <!-- Галерея -->
        <div class="product-gallery fade-in">
            <div class="main-image">
                <img id="mainImage" src="<?= htmlspecialchars($product['main_image'] ?? 'https://via.placeholder.com/800x600/6c5ce7/ffffff?text=Product') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            </div>
            <?php 
            $images = json_decode($product['images'] ?? '[]', true) ?: [];
            if (!empty($images)):
            ?>
            <div class="thumbnail-grid">
                <?php foreach ($images as $index => $image): ?>
                <div class="thumbnail <?= $index === 0 ? 'active' : '' ?>" onclick="changeImage('<?= htmlspecialchars($image) ?>', this)">
                    <img src="<?= htmlspecialchars($image) ?>" alt="Thumbnail">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Информация о товаре -->
        <div class="product-info-detail fade-in">
            <h1><?= htmlspecialchars($product['name']) ?></h1>
            
            <div class="product-meta">
                <span class="product-category-badge"><?= htmlspecialchars($product['category_name'] ?? 'Игры') ?></span>
                <div class="product-rating-large">
                    <span class="stars-large"><?= str_repeat('★', floor($product['rating'] ?? 0)) ?><?= str_repeat('☆', 5 - floor($product['rating'] ?? 0)) ?></span>
                    <span>(<?= $product['review_count'] ?? 0 ?> отзывов)</span>
                </div>
                <span class="stock-status <?= ($product['stock_quantity'] ?? 0) > 0 ? 'stock-in' : 'stock-out' ?>">
                    <?= ($product['stock_quantity'] ?? 0) > 0 ? '✓ В наличии' : '✗ Нет в наличии' ?>
                </span>
            </div>
            
            <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: 25px;">
                <?= htmlspecialchars($product['short_description'] ?? '') ?>
            </p>
            
            <div class="price-block">
                <div>
                    <span class="current-price-large"><?= number_format($product['price'], 0, '.', ' ') ?> ₽</span>
                    <?php if ($product['old_price']): ?>
                    <span class="old-price-large"><?= number_format($product['old_price'], 0, '.', ' ') ?> ₽</span>
                    <span class="discount-badge">-<?= round((1 - $product['price']/$product['old_price']) * 100) ?>%</span>
                    <?php endif; ?>
                </div>
                
                <div class="action-buttons">
                    <button class="btn-action btn-buy" <?= (($product['stock_quantity'] ?? 0) == 0) ? 'disabled' : '' ?> onclick="buyNow(<?= $product['id'] ?>)">
                        ⚡ Купить сейчас
                    </button>
                    <button class="btn-action btn-cart-large" <?= (($product['stock_quantity'] ?? 0) == 0) ? 'disabled' : '' ?> onclick="addToCart(<?= $product['id'] ?>)">
                        🛒 В корзину
                    </button>
                    <button class="btn-action btn-favorite" onclick="toggleFavorite('product', <?= $product['id'] ?>)">
                        ❤️ В избранное
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Описание -->
    <div class="product-description fade-in">
        <h2 class="section-title">📖 Описание</h2>
        <div style="line-height: 1.8; color: var(--text-primary);">
            <?= $product['description'] ?? 'Описание отсутствует' ?>
        </div>
    </div>
    
    <!-- Характеристики -->
    <?php 
    $characteristics = json_decode($product['characteristics'] ?? '[]', true);
    if (!empty($characteristics)):
    ?>
    <div class="product-characteristics fade-in">
        <h2 class="section-title">⚙️ Характеристики</h2>
        <table class="characteristics-table">
            <?php foreach ($characteristics as $key => $value): ?>
            <tr>
                <td><?= htmlspecialchars($key) ?></td>
                <td><?= htmlspecialchars($value) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>
    
    <!-- Отзывы -->
    <div class="product-reviews fade-in">
        <h2 class="section-title">💬 Отзывы (<?= count($reviews) ?>)</h2>
        
        <?php foreach ($reviews as $review): ?>
        <div class="review-card">
            <div class="review-header">
                <div class="reviewer-info">
                    <img src="<?= $review['avatar_path'] ? '/' . htmlspecialchars($review['avatar_path']) : 'https://via.placeholder.com/50x50/6c5ce7/ffffff?text=' . urlencode(substr($review['nickname'], 0, 1)) ?>" alt="Avatar" class="reviewer-avatar">
                    <div>
                        <div class="reviewer-name"><?= htmlspecialchars($review['nickname']) ?></div>
                        <div class="review-date"><?= date('d.m.Y', strtotime($review['created_at'])) ?></div>
                    </div>
                </div>
                <div class="review-rating"><?= str_repeat('★', $review['rating']) ?><?= str_repeat('☆', 5 - $review['rating']) ?></div>
            </div>
            <div class="review-comment"><?= nl2br(htmlspecialchars($review['comment'])) ?></div>
        </div>
        <?php endforeach; ?>
        
        <!-- Форма добавления отзыва (для авторизованных) -->
        <?php if (Auth::check()): ?>
        <div class="review-form">
            <h3 style="margin-bottom: 15px;">Оставить отзыв</h3>
            <form method="POST" action="/api/reviews.php">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <div class="star-rating">
                    <input type="radio" name="rating" id="star5" value="5" required>
                    <label for="star5">★</label>
                    <input type="radio" name="rating" id="star4" value="4">
                    <label for="star4">★</label>
                    <input type="radio" name="rating" id="star3" value="3">
                    <label for="star3">★</label>
                    <input type="radio" name="rating" id="star2" value="2">
                    <label for="star2">★</label>
                    <input type="radio" name="rating" id="star1" value="1">
                    <label for="star1">★</label>
                </div>
                <div class="form-group">
                    <textarea name="comment" class="form-control" rows="4" placeholder="Напишите ваш отзыв..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Отправить на модерацию</button>
            </form>
        </div>
        <?php else: ?>
        <p style="text-align: center; color: var(--text-muted); margin-top: 20px;">
            <a href="/login.php">Войдите</a>, чтобы оставить отзыв
        </p>
        <?php endif; ?>
    </div>
</div>

<script>
function changeImage(src, thumbnail) {
    document.getElementById('mainImage').src = src;
    document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
    thumbnail.classList.add('active');
}

function buyNow(productId) {
    addToCart(productId);
    setTimeout(() => {
        window.location.href = '/checkout.php';
    }, 500);
}
</script>

<?php include __DIR__ . '/../src/views/layouts/footer.php'; ?>
