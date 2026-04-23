<?php

require_once 'init.php';

use App\Helpers\Auth;

$pdo = \App\Config\Database::getInstance()->getConnection();
$user = Auth::user();

// Получение товаров в корзине
if ($user) {
    $stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.main_image, p.slug 
        FROM cart c 
        LEFT JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = :user_id");
    $stmt->execute(['user_id' => $user['id']]);
    $cartItems = $stmt->fetchAll();
} else {
    // Для неавторизованных - из сессии
    $cartItems = $_SESSION['cart'] ?? [];
}

// Если корзина пуста, создаем тестовые данные для демонстрации
if (empty($cartItems)) {
    $cartItems = [
        [
            'id' => 1,
            'product_id' => 1,
            'quantity' => 1,
            'name' => 'Cyberpunk 2077',
            'price' => 1999,
            'main_image' => 'https://picsum.photos/seed/cyberpunk/300/220',
            'slug' => 'cyberpunk-2077'
        ],
        [
            'id' => 2,
            'product_id' => 2,
            'quantity' => 2,
            'name' => 'Elden Ring',
            'price' => 2499,
            'main_image' => 'https://picsum.photos/seed/eldenring/300/220',
            'slug' => 'elden-ring'
        ]
    ];
}

// Подсчет общей суммы
$totalAmount = 0;
foreach ($cartItems as $item) {
    $totalAmount += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
}

$pageTitle = 'Корзина';
include __DIR__ . '/../src/views/layouts/header.php';
?>

<style>
    .cart-container {
        padding: 40px 20px;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .cart-grid {
        display: grid;
        grid-template-columns: 1fr 350px;
        gap: 30px;
    }
    
    @media (max-width: 900px) {
        .cart-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .cart-items {
        background: var(--bg-color);
        border-radius: var(--radius);
        padding: 25px;
        box-shadow: var(--shadow);
    }
    
    .cart-summary {
        background: var(--bg-secondary);
        border-radius: var(--radius);
        padding: 25px;
        box-shadow: var(--shadow);
        position: sticky;
        top: 20px;
    }
    
    .cart-title {
        font-size: 1.8rem;
        color: var(--primary-color);
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .cart-item {
        display: grid;
        grid-template-columns: 100px 1fr auto auto;
        gap: 20px;
        align-items: center;
        padding: 20px 0;
        border-bottom: 1px solid var(--border-color);
    }
    
    .cart-item:last-child {
        border-bottom: none;
    }
    
    .cart-item-image {
        width: 100px;
        height: 100px;
        border-radius: var(--radius-sm);
        overflow: hidden;
    }
    
    .cart-item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .cart-item-info h3 {
        font-size: 1.1rem;
        margin-bottom: 8px;
        color: var(--text-primary);
    }
    
    .cart-item-category {
        color: var(--text-muted);
        font-size: 0.9rem;
        margin-bottom: 10px;
    }
    
    .cart-item-price {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--primary-color);
    }
    
    .quantity-control {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .quantity-btn {
        width: 35px;
        height: 35px;
        border: 1px solid var(--border-color);
        background: var(--bg-color);
        border-radius: var(--radius-sm);
        cursor: pointer;
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
    }
    
    .quantity-btn:hover {
        border-color: var(--primary-color);
        color: var(--primary-color);
    }
    
    .quantity-input {
        width: 50px;
        text-align: center;
        padding: 8px;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        background: var(--bg-color);
        color: var(--text-primary);
    }
    
    .remove-btn {
        padding: 10px 15px;
        background: rgba(214, 48, 49, 0.1);
        color: var(--danger-color);
        border: none;
        border-radius: var(--radius-sm);
        cursor: pointer;
        font-weight: 600;
        transition: var(--transition);
    }
    
    .remove-btn:hover {
        background: var(--danger-color);
        color: white;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 15px 0;
        border-bottom: 1px solid var(--border-color);
    }
    
    .summary-row:last-child {
        border-bottom: none;
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--primary-color);
        padding-top: 20px;
    }
    
    .checkout-btn {
        width: 100%;
        padding: 18px;
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: var(--radius);
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: var(--transition);
        margin-top: 20px;
    }
    
    .checkout-btn:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
    }
    
    .continue-shopping {
        display: block;
        text-align: center;
        margin-top: 15px;
        color: var(--text-muted);
        text-decoration: none;
    }
    
    .continue-shopping:hover {
        color: var(--primary-color);
    }
    
    .news-recommendation {
        margin-top: 30px;
        padding-top: 30px;
        border-top: 2px solid var(--border-color);
    }
    
    .news-recommendation h3 {
        color: var(--primary-color);
        margin-bottom: 15px;
    }
    
    .news-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
    }
    
    .news-card-small {
        background: var(--bg-color);
        border-radius: var(--radius-sm);
        overflow: hidden;
        transition: var(--transition);
    }
    
    .news-card-small:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow);
    }
    
    .news-card-small img {
        width: 100%;
        height: 120px;
        object-fit: cover;
    }
    
    .news-card-small-content {
        padding: 12px;
    }
    
    .news-card-small-title {
        font-size: 0.95rem;
        font-weight: 600;
        margin-bottom: 8px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .empty-cart {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-cart-icon {
        font-size: 5rem;
        margin-bottom: 20px;
    }
    
    .empty-cart h2 {
        color: var(--text-primary);
        margin-bottom: 15px;
    }
    
    .empty-cart p {
        color: var(--text-muted);
        margin-bottom: 30px;
    }
</style>

<div class="cart-container">
    <h1 class="cart-title">🛒 Корзина</h1>
    
    <?php if (!empty($cartItems)): ?>
    <div class="cart-grid">
        <!-- Товары в корзине -->
        <div class="cart-items fade-in">
            <?php foreach ($cartItems as $item): ?>
            <div class="cart-item">
                <div class="cart-item-image">
                    <img src="<?= htmlspecialchars($item['main_image'] ?? 'https://via.placeholder.com/100x100/6c5ce7/ffffff?text=Product') ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                </div>
                <div class="cart-item-info">
                    <h3><?= htmlspecialchars($item['name']) ?></h3>
                    <div class="cart-item-category">Цифровой товар</div>
                    <div class="cart-item-price"><?= number_format($item['price'], 0, '.', ' ') ?> ₽</div>
                </div>
                <div class="quantity-control">
                    <button class="quantity-btn" onclick="updateQuantity(<?= $item['product_id'] ?>, <?= ($item['quantity'] ?? 1) - 1 ?>)">−</button>
                    <input type="number" class="quantity-input" value="<?= $item['quantity'] ?? 1 ?>" min="1" onchange="updateQuantity(<?= $item['product_id'] ?>, this.value)">
                    <button class="quantity-btn" onclick="updateQuantity(<?= $item['product_id'] ?>, <?= ($item['quantity'] ?? 1) + 1 ?>)">+</button>
                </div>
                <button class="remove-btn" onclick="removeFromCart(<?= $item['product_id'] ?>)">🗑️ Удалить</button>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Итоговая сумма -->
        <div class="cart-summary fade-in">
            <h2 style="margin-bottom: 20px; color: var(--primary-color);">Итого</h2>
            
            <div class="summary-row">
                <span>Товары (<?= count($cartItems) ?>):</span>
                <span><?= number_format($totalAmount, 0, '.', ' ') ?> ₽</span>
            </div>
            
            <div class="summary-row">
                <span>Скидка:</span>
                <span style="color: var(--success-color);">-0 ₽</span>
            </div>
            
            <div class="summary-row">
                <span>Итого к оплате:</span>
                <span><?= number_format($totalAmount, 0, '.', ' ') ?> ₽</span>
            </div>
            
            <a href="/checkout.php" class="checkout-btn">
                💳 Оформить заказ
            </a>
            
            <a href="/products.php" class="continue-shopping">→ Продолжить покупки</a>
            
            <!-- Рекомендации новостей -->
            <div class="news-recommendation">
                <h3>📰 Новости по интересам</h3>
                <div class="news-grid">
                    <?php for ($i = 1; $i <= 2; $i++): ?>
                    <div class="news-card-small">
                        <img src="https://picsum.photos/seed/news<?= $i ?>/300/200" alt="News">
                        <div class="news-card-small-content">
                            <div class="news-card-small-title">Новые игры этой недели: обзор релизов</div>
                            <a href="/news-detail.php?id=<?= $i ?>" style="color: var(--primary-color); font-size: 0.85rem;">Читать далее →</a>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
                <a href="/news.php" style="display: block; text-align: center; margin-top: 15px; color: var(--primary-color);">Все новости →</a>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Пустая корзина -->
    <div class="empty-cart fade-in">
        <div class="empty-cart-icon">🛒</div>
        <h2>Ваша корзина пуста</h2>
        <p>Добавьте товары, чтобы оформить заказ</p>
        <a href="/products.php" class="btn btn-primary" style="padding: 15px 40px; font-size: 1.1rem;">
            🎮 Перейти в каталог
        </a>
    </div>
    <?php endif; ?>
</div>

<script>
function updateQuantity(productId, quantity) {
    if (quantity < 1) {
        removeFromCart(productId);
        return;
    }
    
    fetch('/api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update',
            product_id: productId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showToast('Ошибка обновления количества', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Ошибка соединения', 'error');
    });
}

function removeFromCart(productId) {
    fetch('/api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'remove',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showToast('Ошибка удаления товара', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Ошибка соединения', 'error');
    });
}
</script>

<?php include __DIR__ . '/../src/views/layouts/footer.php'; ?>
