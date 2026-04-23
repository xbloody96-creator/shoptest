<?php
/**
 * Компонент карточки товара
 */
$product = $product ?? null;
if (!$product) return;
?>

<div class="product-card" data-product-id="<?= $product['id'] ?>">
    <div class="product-image-wrapper">
        <img src="<?= htmlspecialchars($product['image'] ?? 'https://picsum.photos/300/200?random=' . $product['id']) ?>" 
             alt="<?= htmlspecialchars($product['name']) ?>" 
             class="product-image">
        <?php if (!empty($product['discount'])): ?>
            <span class="product-badge discount">-<?= $product['discount'] ?>%</span>
        <?php endif; ?>
        <?php if (empty($product['in_stock'])): ?>
            <span class="product-badge out-of-stock">Нет в наличии</span>
        <?php endif; ?>
    </div>
    
    <div class="product-info">
        <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
        <p class="product-category"><?= htmlspecialchars($product['category_name'] ?? 'Без категории') ?></p>
        
        <div class="product-rating">
            <?php 
            $rating = $product['rating'] ?? 0;
            for ($i = 1; $i <= 5; $i++):
            ?>
                <svg class="star <?= $i <= $rating ? 'filled' : '' ?>" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
            <?php endfor; ?>
            <span class="rating-value">(<?= $product['reviews_count'] ?? 0 ?>)</span>
        </div>
        
        <p class="product-description"><?= htmlspecialchars(mb_substr($product['description'] ?? '', 0, 100)) ?><?= mb_strlen($product['description'] ?? '') > 100 ? '...' : '' ?></p>
        
        <div class="product-footer">
            <div class="product-price">
                <?php if (!empty($product['old_price'])): ?>
                    <span class="old-price"><?= number_format($product['old_price'], 0, ',', ' ') ?> ₽</span>
                <?php endif; ?>
                <span class="current-price"><?= number_format($product['price'], 0, ',', ' ') ?> ₽</span>
            </div>
            
            <div class="product-actions">
                <button class="btn btn-sm btn-cart" onclick="addToCart(<?= $product['id'] ?>)" <?= empty($product['in_stock']) ? 'disabled' : '' ?>>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                    В корзину
                </button>
                <button class="btn btn-sm btn-favorite" onclick="toggleFavorite(<?= $product['id'] ?>)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.product-card {
    background: var(--card-bg);
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.product-image-wrapper {
    position: relative;
    overflow: hidden;
    height: 200px;
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.product-card:hover .product-image {
    transform: scale(1.05);
}

.product-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: bold;
    color: white;
}

.product-badge.discount {
    background: #ef4444;
}

.product-badge.out-of-stock {
    background: #6b7280;
}

.product-info {
    padding: 1.5rem;
}

.product-title {
    font-size: 1.2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
    color: var(--text-color);
}

.product-category {
    font-size: 0.9rem;
    color: var(--text-muted);
    margin-bottom: 0.5rem;
}

.product-rating {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    margin-bottom: 0.5rem;
}

.star {
    color: #d1d5db;
}

.star.filled {
    color: #fbbf24;
}

.rating-value {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-left: 0.25rem;
}

.product-description {
    font-size: 0.9rem;
    color: var(--text-muted);
    line-height: 1.5;
    margin-bottom: 1rem;
}

.product-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.product-price {
    display: flex;
    flex-direction: column;
}

.old-price {
    font-size: 0.9rem;
    text-decoration: line-through;
    color: var(--text-muted);
}

.current-price {
    font-size: 1.3rem;
    font-weight: bold;
    color: var(--primary-color);
}

.product-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

.btn-cart {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-favorite {
    padding: 0.5rem;
    background: var(--border-color);
    border-radius: 8px;
}

.btn-favorite:hover {
    background: #ef4444;
    color: white;
}

.btn-cart:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
