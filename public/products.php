<?php

require_once 'init.php';

use App\Helpers\Auth;

$pdo = \App\Config\Database::getInstance()->getConnection();

// Получение категорий
$stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order");
$categories = $stmt->fetchAll();

// Фильтры и сортировка
$where = ['p.is_available = 1'];
$params = [];

if (!empty($_GET['category'])) {
    $where[] = 'p.category_id = :category';
    $params['category'] = $_GET['category'];
}

if (!empty($_GET['q'])) {
    $where[] = '(p.name LIKE :search OR p.short_description LIKE :search)';
    $params['search'] = '%' . $_GET['q'] . '%';
}

if (isset($_GET['min_price']) && $_GET['min_price'] !== '') {
    $where[] = 'p.price >= :min_price';
    $params['min_price'] = floatval($_GET['min_price']);
}

if (isset($_GET['max_price']) && $_GET['max_price'] !== '') {
    $where[] = 'p.price <= :max_price';
    $params['max_price'] = floatval($_GET['max_price']);
}

if (isset($_GET['in_stock']) && $_GET['in_stock'] == '1') {
    $where[] = 'p.stock_quantity > 0';
}

if (isset($_GET['promo']) && $_GET['promo'] == '1') {
    $where[] = 'p.is_promo = 1';
}

$orderBy = 'p.created_at DESC';
if (!empty($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'price_asc': $orderBy = 'p.price ASC'; break;
        case 'price_desc': $orderBy = 'p.price DESC'; break;
        case 'rating': $orderBy = 'p.rating DESC'; break;
        case 'popular': $orderBy = 'p.view_count DESC'; break;
        case 'newest': $orderBy = 'p.created_at DESC'; break;
    }
}

$whereClause = implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE $whereClause 
    ORDER BY $orderBy");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Если товаров нет в БД, генерируем тестовые
if (empty($products)) {
    $products = [];
    for ($i = 1; $i <= 24; $i++) {
        $products[] = [
            'id' => $i,
            'name' => ['Cyberpunk 2077', 'Elden Ring', 'God of War', 'Spider-Man', 'Horizon Zero Dawn', 'The Witcher 3', 'Red Dead Redemption 2', 'GTA V', 'Minecraft', 'Fortnite V-Bucks'][$i % 10] . ' #' . $i,
            'short_description' => 'Цифровой товар с мгновенной доставкой',
            'price' => rand(500, 5000),
            'old_price' => rand(5000, 7000),
            'stock_quantity' => rand(0, 100),
            'is_available' => true,
            'rating' => round(rand(35, 50) / 10, 1),
            'review_count' => rand(0, 500),
            'main_image' => 'https://picsum.photos/seed/product' . $i . '/400/300',
            'category_name' => ['Игры', 'Ключи', 'Аккаунты', 'Подарочные карты'][$i % 4],
            'slug' => 'product-' . $i,
            'is_promo' => $i % 5 == 0
        ];
    }
}

$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 12;
$totalProducts = count($products);
$totalPages = ceil($totalProducts / $itemsPerPage);
$products = array_slice($products, ($currentPage - 1) * $itemsPerPage, $itemsPerPage);

$currentUser = Auth::user();
$pageTitle = 'Каталог товаров';

include __DIR__ . '/../src/views/layouts/header.php';
?>

<style>
    .catalog-container {
        padding: 40px 20px;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .catalog-header {
        margin-bottom: 30px;
    }
    
    .catalog-title {
        font-size: 2rem;
        color: var(--primary-color);
        margin-bottom: 10px;
    }
    
    .filters-section {
        background: var(--bg-secondary);
        border-radius: var(--radius);
        padding: 25px;
        margin-bottom: 30px;
    }
    
    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .filter-label {
        font-weight: 600;
        color: var(--text-primary);
        font-size: 0.9rem;
    }
    
    .filter-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    
    .price-inputs {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .price-inputs input {
        width: 100%;
        padding: 10px;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        background: var(--bg-color);
        color: var(--text-primary);
    }
    
    .filter-btn {
        padding: 12px 24px;
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: var(--radius);
        cursor: pointer;
        font-weight: 600;
        transition: var(--transition);
        margin-top: 10px;
    }
    
    .filter-btn:hover {
        background: var(--primary-dark);
    }
    
    .reset-filters {
        padding: 12px 24px;
        background: transparent;
        color: var(--text-muted);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        cursor: pointer;
        font-weight: 600;
        transition: var(--transition);
        margin-top: 10px;
        margin-left: 10px;
    }
    
    .reset-filters:hover {
        border-color: var(--danger-color);
        color: var(--danger-color);
    }
    
    .sort-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .results-count {
        color: var(--text-muted);
    }
    
    .sort-select {
        padding: 10px 15px;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        background: var(--bg-color);
        color: var(--text-primary);
        cursor: pointer;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 40px;
        flex-wrap: wrap;
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

<div class="catalog-container">
    <div class="catalog-header">
        <h1 class="catalog-title">🎮 Каталог товаров</h1>
        <p style="color: var(--text-muted);">Игры, ключи активации, аккаунты и подарочные карты</p>
    </div>
    
    <!-- Фильтры -->
    <div class="filters-section fade-in">
        <form method="GET" action="" id="filterForm">
            <div class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">Категория</label>
                    <select name="category" class="form-control">
                        <option value="">Все категории</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (($_GET['category'] ?? '') == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Цена (₽)</label>
                    <div class="price-inputs">
                        <input type="number" name="min_price" placeholder="От" value="<?= htmlspecialchars($_GET['min_price'] ?? '') ?>" min="0">
                        <span>-</span>
                        <input type="number" name="max_price" placeholder="До" value="<?= htmlspecialchars($_GET['max_price'] ?? '') ?>" min="0">
                    </div>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Фильтры</label>
                    <label class="filter-checkbox">
                        <input type="checkbox" name="in_stock" value="1" <?= (isset($_GET['in_stock']) && $_GET['in_stock'] == '1') ? 'checked' : '' ?>>
                        В наличии
                    </label>
                    <label class="filter-checkbox">
                        <input type="checkbox" name="promo" value="1" <?= (isset($_GET['promo']) && $_GET['promo'] == '1') ? 'checked' : '' ?>>
                        Акции
                    </label>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">&nbsp;</label>
                    <button type="submit" class="filter-btn">🔍 Применить</button>
                    <a href="/products.php" class="reset-filters">Сбросить</a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Сортировка -->
    <div class="sort-bar">
        <span class="results-count">Найдено товаров: <strong><?= $totalProducts ?></strong></span>
        <select name="sort" class="sort-select" onchange="location.href='?<?= http_build_query(array_merge($_GET, ['sort' => $this->value])) ?>'">
            <option value="newest" <?= (($_GET['sort'] ?? '') == 'newest' || empty($_GET['sort'])) ? 'selected' : '' ?>>По новизне</option>
            <option value="price_asc" <?= (($_GET['sort'] ?? '') == 'price_asc') ? 'selected' : '' ?>>Цена: по возрастанию</option>
            <option value="price_desc" <?= (($_GET['sort'] ?? '') == 'price_desc') ? 'selected' : '' ?>>Цена: по убыванию</option>
            <option value="rating" <?= (($_GET['sort'] ?? '') == 'rating') ? 'selected' : '' ?>>По рейтингу</option>
            <option value="popular" <?= (($_GET['sort'] ?? '') == 'popular') ? 'selected' : '' ?>>Популярные</option>
        </select>
    </div>
    
    <!-- Товары -->
    <div class="products-grid">
        <?php foreach ($products as $product): ?>
        <div class="product-card fade-in">
            <div class="product-image">
                <img src="<?= htmlspecialchars($product['main_image'] ?? 'https://via.placeholder.com/400x300/6c5ce7/ffffff?text=Product') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                <?php if ($product['old_price']): ?>
                <span class="product-badge">-<?= round((1 - $product['price']/$product['old_price']) * 100) ?>%</span>
                <?php endif; ?>
                <?php if (($product['stock_quantity'] ?? 0) == 0): ?>
                <span class="product-badge" style="background: var(--danger-color);">Нет в наличии</span>
                <?php endif; ?>
                <div class="product-actions">
                    <button class="action-btn" title="В избранное" onclick="toggleFavorite('product', <?= $product['id'] ?>)">❤️</button>
                    <a href="/product-detail.php?slug=<?= htmlspecialchars($product['slug']) ?>" class="action-btn" title="Быстрый просмотр">👁️</a>
                </div>
            </div>
            <div class="product-info">
                <div class="product-category"><?= htmlspecialchars($product['category_name'] ?? 'Игры') ?></div>
                <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                <div class="product-rating">
                    <span class="stars">★<?= str_repeat('★', floor($product['rating'] ?? 0)) ?>☆☆☆☆☆</span>
                    <span class="rating-count">(<?= $product['review_count'] ?? 0 ?>)</span>
                </div>
                <div class="product-price">
                    <span class="current-price"><?= number_format($product['price'], 0, '.', ' ') ?> ₽</span>
                    <?php if ($product['old_price']): ?>
                    <span class="old-price"><?= number_format($product['old_price'], 0, '.', ' ') ?> ₽</span>
                    <?php endif; ?>
                </div>
                <div class="product-footer">
                    <button class="btn-cart" <?= (($product['stock_quantity'] ?? 0) == 0) ? 'disabled' : '' ?> onclick="addToCart(<?= $product['id'] ?>)">
                        🛒 <?= (($product['stock_quantity'] ?? 0) == 0) ? 'Нет в наличии' : 'В корзину' ?>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Пагинация -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($currentPage > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>">← Назад</a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == $currentPage): ?>
            <span class="active"><?= $i ?></span>
            <?php else: ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($currentPage < $totalPages): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>">Вперед →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../src/views/layouts/footer.php'; ?>
