<?php
session_start();
require_once __DIR__ . '/../src/config/database.php';

$pdo = getDB();

$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_available = 1 ORDER BY p.view_count DESC LIMIT 5");
$stmt->execute();
$sliderProducts = $stmt->fetchAll();

if (empty($sliderProducts)) {
    $sliderProducts = [
        ['id' => 1, 'name' => 'Cyberpunk 2077', 'short_description' => 'Ролевая игра в открытом мире будущего', 'price' => 1999, 'old_price' => 2999, 'main_image' => 'https://picsum.photos/seed/cyberpunk/600/400', 'category_name' => 'Игры', 'slug' => 'cyberpunk-2077'],
        ['id' => 2, 'name' => 'Elden Ring', 'short_description' => 'Эпическая action/RPG от FromSoftware', 'price' => 2499, 'old_price' => null, 'main_image' => 'https://picsum.photos/seed/eldenring/600/400', 'category_name' => 'Игры', 'slug' => 'elden-ring'],
        ['id' => 3, 'name' => 'Steam Gift Card $50', 'short_description' => 'Подарочная карта Steam на $50', 'price' => 4500, 'old_price' => 5000, 'main_image' => 'https://picsum.photos/seed/steamcard/600/400', 'category_name' => 'Подарочные карты', 'slug' => 'steam-gift-card-50']
    ];
}

$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_available = 1 ORDER BY p.created_at DESC LIMIT 8");
$stmt->execute();
$newProducts = $stmt->fetchAll();

if (empty($newProducts)) {
    $newProducts = [];
    for ($i = 1; $i <= 8; $i++) {
        $newProducts[] = ['id' => $i + 10, 'name' => 'Товар #' . $i, 'short_description' => 'Описание товара ' . $i, 'price' => rand(500, 5000), 'old_price' => rand(5000, 7000), 'main_image' => 'https://picsum.photos/seed/product' . $i . '/300/220', 'category_name' => 'Игры', 'slug' => 'product-' . $i, 'rating' => round(rand(35, 50) / 10, 1), 'review_count' => rand(0, 100)];
    }
}

$currentUser = $_SESSION['user'] ?? null;
$pageTitle = 'GameStore - Магазин игр, ключей и аккаунтов';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
    <?php include __DIR__ . '/views/layouts/header.php'; ?>
    
    <main>
        <section class="hero-slider">
            <div class="slider-container">
                <?php foreach ($sliderProducts as $index => $product): ?>
                <div class="slide <?= $index === 0 ? 'active' : '' ?>">
                    <div class="slide-image">
                        <img src="<?= htmlspecialchars($product['main_image'] ?? 'https://picsum.photos/seed/game/600/400') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    </div>
                    <div class="slide-content">
                        <span class="slide-badge">🔥 Популярное</span>
                        <h2 class="slide-title"><?= htmlspecialchars($product['name']) ?></h2>
                        <p class="slide-description"><?= htmlspecialchars($product['short_description'] ?? '') ?></p>
                        <div class="product-price" style="margin-bottom: 1.5rem;">
                            <span class="current-price"><?= number_format($product['price'], 0, '.', ' ') ?> ₽</span>
                            <?php if ($product['old_price']): ?><span class="old-price"><?= number_format($product['old_price'], 0, '.', ' ') ?> ₽</span><?php endif; ?>
                        </div>
                        <div class="slider-controls">
                            <a href="/product-detail.php?slug=<?= htmlspecialchars($product['slug']) ?>" class="btn btn-primary">Подробнее →</a>
                            <button class="btn btn-outline" onclick="addToCart(<?= $product['id'] ?>)">В корзину</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="slider-indicators">
                    <?php foreach ($sliderProducts as $index => $product): ?>
                    <div class="indicator <?= $index === 0 ? 'active' : '' ?>"></div>
                    <?php endforeach; ?>
                </div>
                
                <div class="slider-controls" style="position: absolute; top: 50%; left: 2rem; transform: translateY(-50%);">
                    <button class="slider-btn prev">←</button>
                </div>
                <div class="slider-controls" style="position: absolute; top: 50%; right: 2rem; transform: translateY(-50%);">
                    <button class="slider-btn next">→</button>
                </div>
            </div>
        </section>
        
        <section id="about" class="section">
            <div class="section-header">
                <h2 class="section-title">О нас</h2>
                <p class="section-subtitle">Ваш надежный магазин цифровых товаров</p>
            </div>
            <div style="max-width: 800px; margin: 0 auto; text-align: center; color: var(--text-secondary); line-height: 1.8;">
                <p style="margin-bottom: 1rem;">GameStore — это современный магазин цифровых товаров, где вы можете приобрести игры, ключи активации, аккаунты и подарочные карты по лучшим ценам.</p>
                <p style="margin-bottom: 1rem;">Мы работаем с 2020 года и за это время обслужили более 100 000 довольных клиентов.</p>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; margin-top: 3rem;">
                    <div><div style="font-size: 2.5rem; font-weight: 800; color: var(--primary-color);">100K+</div><div style="color: var(--text-muted);">Довольных клиентов</div></div>
                    <div><div style="font-size: 2.5rem; font-weight: 800; color: var(--primary-color);">50K+</div><div style="color: var(--text-muted);">Проданных товаров</div></div>
                    <div><div style="font-size: 2.5rem; font-weight: 800; color: var(--primary-color);">24/7</div><div style="color: var(--text-muted);">Поддержка</div></div>
                    <div><div style="font-size: 2.5rem; font-weight: 800; color: var(--primary-color);">4.9</div><div style="color: var(--text-muted);">Рейтинг доверия</div></div>
                </div>
            </div>
        </section>
        
        <section id="promotions" class="section" style="background: var(--bg-primary);">
            <div class="section-header">
                <h2 class="section-title">🔥 Акции и скидки</h2>
                <p class="section-subtitle">Лучшие предложения этой недели</p>
            </div>
            <div class="products-grid">
                <?php foreach (array_slice($newProducts, 0, 4) as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?= htmlspecialchars($product['main_image'] ?? 'https://picsum.photos/seed/promo/300/220') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        <?php if ($product['old_price']): ?><span class="product-badge">-<?= round((1 - $product['price']/$product['old_price']) * 100) ?>%</span><?php endif; ?>
                        <div class="product-actions">
                            <button class="action-btn" title="В избранное" onclick="toggleFavorite('product', <?= $product['id'] ?>)">❤️</button>
                            <button class="action-btn" title="Быстрый просмотр">👁️</button>
                        </div>
                    </div>
                    <div class="product-info">
                        <div class="product-category"><?= htmlspecialchars($product['category_name'] ?? 'Игры') ?></div>
                        <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                        <div class="product-rating"><span class="stars">★★★★☆</span><span class="rating-count">(<?= $product['review_count'] ?? 0 ?>)</span></div>
                        <div class="product-price"><span class="current-price"><?= number_format($product['price'], 0, '.', ' ') ?> ₽</span><?php if ($product['old_price']): ?><span class="old-price"><?= number_format($product['old_price'], 0, '.', ' ') ?> ₽</span><?php endif; ?></div>
                        <div class="product-footer"><button class="btn-cart" onclick="addToCart(<?= $product['id'] ?>)">🛒 В корзину</button></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        
        <section id="search" class="search-section">
            <div class="search-container">
                <h2 style="text-align: center; color: white; margin-bottom: 2rem; font-size: 2rem;">Поиск товаров и новостей</h2>
                <form class="search-box" action="/products.php" method="GET">
                    <input type="text" class="search-input" name="q" placeholder="Найти игры, ключи, аккаунты..." required>
                    <button type="submit" class="search-btn">🔍 Поиск</button>
                </form>
            </div>
        </section>
        
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">Новинки</h2>
                <p class="section-subtitle">Последние поступления в нашем магазине</p>
            </div>
            <div class="products-grid">
                <?php foreach ($newProducts as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?= htmlspecialchars($product['main_image'] ?? 'https://picsum.photos/seed/new/300/220') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        <div class="product-actions"><button class="action-btn" title="В избранное" onclick="toggleFavorite('product', <?= $product['id'] ?>)">❤️</button></div>
                    </div>
                    <div class="product-info">
                        <div class="product-category"><?= htmlspecialchars($product['category_name'] ?? 'Игры') ?></div>
                        <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                        <div class="product-rating"><span class="stars">★★★★☆</span><span class="rating-count">(<?= $product['review_count'] ?? 0 ?>)</span></div>
                        <div class="product-price"><span class="current-price"><?= number_format($product['price'], 0, '.', ' ') ?> ₽</span></div>
                        <div class="product-footer"><a href="/product-detail.php?slug=<?= htmlspecialchars($product['slug'] ?? 'product-'.$product['id']) ?>" class="btn-cart" style="text-align: center; justify-content: center;">Подробнее</a></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align: center; margin-top: 3rem;">
                <a href="/products.php" class="btn btn-primary" style="padding: 1rem 3rem;">Смотреть все товары →</a>
            </div>
        </section>
    </main>
    
    <?php include __DIR__ . '/views/layouts/footer.php'; ?>
    
    <script src="/assets/js/main.js"></script>
</body>
</html>
