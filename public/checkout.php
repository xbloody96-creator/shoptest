<?php

require_once 'init.php';

use App\Helpers\Auth;

$pdo = \App\Config\Database::getInstance()->getConnection();
$user = Auth::user();

// Получение товаров из корзины для оформления
if ($user) {
    $stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.main_image 
        FROM cart c 
        LEFT JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = :user_id");
    $stmt->execute(['user_id' => $user['id']]);
    $cartItems = $stmt->fetchAll();
} else {
    $cartItems = $_SESSION['cart'] ?? [];
}

// Если корзина пуста - перенаправляем
if (empty($cartItems)) {
    header('Location: /cart.php');
    exit;
}

// Подсчет общей суммы
$totalAmount = 0;
foreach ($cartItems as $item) {
    $totalAmount += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
}

$error = null;
$success = null;

// Обработка формы заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = trim($_POST['customer_name'] ?? '');
    $customerEmail = trim($_POST['customer_email'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $deliveryMethod = $_POST['delivery_method'] ?? 'email';
    $deliveryAddress = trim($_POST['delivery_address'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'card';
    $notes = trim($_POST['notes'] ?? '');
    
    // Валидация
    if (empty($customerName) || empty($customerEmail)) {
        $error = 'Заполните обязательные поля (Имя и Email)';
    } elseif (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный формат email';
    } else {
        // Создание заказа
        $orderNumber = 'ORD-' . strtoupper(uniqid());
        
        try {
            $pdo->beginTransaction();
            
            // Вставка заказа
            $stmt = $pdo->prepare("INSERT INTO orders 
                (user_id, order_number, status, total_amount, payment_method, delivery_method, 
                delivery_address, customer_email, customer_phone, customer_name, notes) 
                VALUES (:user_id, :order_number, 'pending', :total_amount, :payment_method, 
                :delivery_method, :delivery_address, :customer_email, :customer_phone, :customer_name, :notes)");
            
            $stmt->execute([
                'user_id' => $user['id'] ?? null,
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'delivery_method' => $deliveryMethod,
                'delivery_address' => $deliveryAddress,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
                'customer_name' => $customerName,
                'notes' => $notes
            ]);
            
            $orderId = $pdo->lastInsertId();
            
            // Вставка элементов заказа
            foreach ($cartItems as $item) {
                $stmt = $pdo->prepare("INSERT INTO order_items 
                    (order_id, product_id, name, quantity, price, total) 
                    VALUES (:order_id, :product_id, :name, :quantity, :price, :total)");
                
                $stmt->execute([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'] ?? null,
                    'name' => $item['name'],
                    'quantity' => $item['quantity'] ?? 1,
                    'price' => $item['price'],
                    'total' => ($item['price'] ?? 0) * ($item['quantity'] ?? 1)
                ]);
            }
            
            // Очистка корзины
            if ($user) {
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = :user_id");
                $stmt->execute(['user_id' => $user['id']]);
            } else {
                unset($_SESSION['cart']);
            }
            
            $pdo->commit();
            $success = [
                'order_number' => $orderNumber,
                'order_id' => $orderId
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Ошибка при создании заказа: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Оформление заказа';
include __DIR__ . '/../src/views/layouts/header.php';
?>

<style>
    .checkout-container {
        padding: 40px 20px;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .checkout-grid {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 30px;
    }
    
    @media (max-width: 900px) {
        .checkout-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .checkout-form {
        background: var(--bg-color);
        border-radius: var(--radius);
        padding: 30px;
        box-shadow: var(--shadow);
    }
    
    .checkout-summary {
        background: var(--bg-secondary);
        border-radius: var(--radius);
        padding: 25px;
        box-shadow: var(--shadow);
        position: sticky;
        top: 20px;
        height: fit-content;
    }
    
    .checkout-title {
        font-size: 1.8rem;
        color: var(--primary-color);
        margin-bottom: 25px;
    }
    
    .form-section {
        margin-bottom: 30px;
    }
    
    .section-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .section-title::before {
        content: '';
        width: 4px;
        height: 20px;
        background: var(--primary-color);
        border-radius: 2px;
    }
    
    .delivery-options, .payment-options {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .option-card {
        border: 2px solid var(--border-color);
        border-radius: var(--radius);
        padding: 20px;
        cursor: pointer;
        transition: var(--transition);
        text-align: center;
    }
    
    .option-card:hover {
        border-color: var(--primary-color);
        background: var(--bg-secondary);
    }
    
    .option-card.selected {
        border-color: var(--primary-color);
        background: rgba(108, 92, 231, 0.1);
    }
    
    .option-card input[type="radio"] {
        display: none;
    }
    
    .option-icon {
        font-size: 2.5rem;
        margin-bottom: 10px;
    }
    
    .option-label {
        font-weight: 600;
        color: var(--text-primary);
    }
    
    .option-description {
        font-size: 0.85rem;
        color: var(--text-muted);
        margin-top: 5px;
    }
    
    .alert {
        padding: 15px;
        border-radius: var(--radius-sm);
        margin-bottom: 20px;
    }
    
    .alert-error {
        background: rgba(214, 48, 49, 0.1);
        color: var(--danger-color);
        border: 1px solid var(--danger-color);
    }
    
    .alert-success {
        background: rgba(0, 184, 148, 0.1);
        color: var(--success-color);
        border: 1px solid var(--success-color);
    }
    
    .order-success {
        text-align: center;
        padding: 60px 20px;
    }
    
    .success-icon {
        font-size: 5rem;
        margin-bottom: 20px;
    }
    
    .order-number {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-color);
        margin: 20px 0;
    }
    
    .summary-item {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid var(--border-color);
    }
    
    .summary-item:last-child {
        border-bottom: none;
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--primary-color);
        padding-top: 20px;
    }
    
    .confirm-btn {
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
        margin-top: 25px;
    }
    
    .confirm-btn:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
    }
    
    .agreement {
        margin-top: 20px;
        font-size: 0.9rem;
        color: var(--text-muted);
        text-align: center;
    }
    
    .cart-items-mini {
        max-height: 300px;
        overflow-y: auto;
        margin-bottom: 20px;
    }
    
    .cart-item-mini {
        display: flex;
        gap: 15px;
        padding: 12px 0;
        border-bottom: 1px solid var(--border-color);
    }
    
    .cart-item-mini img {
        width: 60px;
        height: 60px;
        border-radius: var(--radius-sm);
        object-fit: cover;
    }
    
    .cart-item-mini-info {
        flex: 1;
    }
    
    .cart-item-mini-name {
        font-weight: 600;
        font-size: 0.95rem;
        margin-bottom: 5px;
    }
    
    .cart-item-mini-price {
        color: var(--primary-color);
        font-weight: 700;
    }
</style>

<div class="checkout-container">
    <?php if ($success): ?>
    <!-- Успешное оформление -->
    <div class="order-success fade-in">
        <div class="success-icon">✅</div>
        <h1 class="checkout-title">Заказ успешно оформлен!</h1>
        <p style="color: var(--text-muted); font-size: 1.1rem;">Спасибо за ваш заказ. Мы отправили подтверждение на <?= htmlspecialchars($customerEmail) ?></p>
        <div class="order-number">Номер заказа: <?= htmlspecialchars($success['order_number']) ?></div>
        <p style="color: var(--text-muted); margin-bottom: 30px;">Цифровые товары будут отправлены на указанный email после оплаты.</p>
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <a href="/profile.php" class="btn btn-primary" style="padding: 15px 30px;">📋 Мои заказы</a>
            <a href="/products.php" class="btn btn-outline" style="padding: 15px 30px;">→ Продолжить покупки</a>
        </div>
    </div>
    
    <?php else: ?>
    <h1 class="checkout-title">🛒 Оформление заказа</h1>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="checkout-grid">
        <!-- Форма оформления -->
        <div class="checkout-form fade-in">
            <form method="POST" action="">
                <!-- Контактные данные -->
                <div class="form-section">
                    <h2 class="section-title">Контактные данные</h2>
                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label class="form-label">Имя *</label>
                            <input type="text" name="customer_name" class="form-control" 
                                value="<?= htmlspecialchars($user['full_name'] ?? $_POST['customer_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email *</label>
                            <input type="email" name="customer_email" class="form-control" 
                                value="<?= htmlspecialchars($user['email'] ?? $_POST['customer_email'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 15px;">
                        <label class="form-label">Телефон</label>
                        <input type="tel" name="customer_phone" class="form-control" 
                            value="<?= htmlspecialchars($user['phone'] ?? $_POST['customer_phone'] ?? '') ?>" 
                            placeholder="+7 (___) ___-__-__">
                    </div>
                </div>
                
                <!-- Способ доставки -->
                <div class="form-section">
                    <h2 class="section-title">Способ получения</h2>
                    <div class="delivery-options">
                        <label class="option-card selected">
                            <input type="radio" name="delivery_method" value="email" checked onchange="selectOption(this)">
                            <div class="option-icon">📧</div>
                            <div class="option-label">На Email</div>
                            <div class="option-description">Для цифровых товаров</div>
                        </label>
                        <label class="option-card">
                            <input type="radio" name="delivery_method" value="pickup" onchange="selectOption(this)">
                            <div class="option-icon">🏪</div>
                            <div class="option-label">Самовывоз</div>
                            <div class="option-description">В офисе компании</div>
                        </label>
                    </div>
                </div>
                
                <!-- Способ оплаты -->
                <div class="form-section">
                    <h2 class="section-title">Способ оплаты</h2>
                    <div class="payment-options">
                        <label class="option-card selected">
                            <input type="radio" name="payment_method" value="card" checked onchange="selectOption(this)">
                            <div class="option-icon">💳</div>
                            <div class="option-label">Карта</div>
                            <div class="option-description">Visa, MasterCard, MIR</div>
                        </label>
                        <label class="option-card">
                            <input type="radio" name="payment_method" value="qiwi" onchange="selectOption(this)">
                            <div class="option-icon">🟠</div>
                            <div class="option-label">QIWI</div>
                            <div class="option-description">Электронный кошелек</div>
                        </label>
                        <label class="option-card">
                            <input type="radio" name="payment_method" value="yandex" onchange="selectOption(this)">
                            <div class="option-icon">🔴</div>
                            <div class="option-label">YooMoney</div>
                            <div class="option-description">Яндекс.Деньги</div>
                        </label>
                        <label class="option-card">
                            <input type="radio" name="payment_method" value="crypto" onchange="selectOption(this)">
                            <div class="option-icon">₿</div>
                            <div class="option-label">Криптовалюта</div>
                            <div class="option-description">BTC, ETH, USDT</div>
                        </label>
                    </div>
                </div>
                
                <!-- Комментарии -->
                <div class="form-section">
                    <h2 class="section-title">Комментарий к заказу</h2>
                    <textarea name="notes" class="form-control" rows="3" 
                        placeholder="Дополнительная информация..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                </div>
                
                <button type="submit" class="confirm-btn">
                    ✅ Подтвердить заказ
                </button>
                
                <p class="agreement">
                    Нажимая кнопку "Подтвердить заказ", вы соглашаетесь с 
                    <a href="#" style="color: var(--primary-color);">условиями оферты</a>
                </p>
            </form>
        </div>
        
        <!-- Итоговая сумма -->
        <div class="checkout-summary fade-in">
            <h2 style="margin-bottom: 20px; color: var(--primary-color);">Ваш заказ</h2>
            
            <div class="cart-items-mini">
                <?php foreach ($cartItems as $item): ?>
                <div class="cart-item-mini">
                    <img src="<?= htmlspecialchars($item['main_image'] ?? 'https://via.placeholder.com/60x60/6c5ce7/ffffff?text=Product') ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                    <div class="cart-item-mini-info">
                        <div class="cart-item-mini-name"><?= htmlspecialchars($item['name']) ?></div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">×<?= $item['quantity'] ?? 1 ?></div>
                        <div class="cart-item-mini-price"><?= number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 0, '.', ' ') ?> ₽</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="summary-item">
                <span>Товары:</span>
                <span><?= number_format($totalAmount, 0, '.', ' ') ?> ₽</span>
            </div>
            <div class="summary-item">
                <span>Скидка:</span>
                <span style="color: var(--success-color);">-0 ₽</span>
            </div>
            <div class="summary-item">
                <span>Итого:</span>
                <span><?= number_format($totalAmount, 0, '.', ' ') ?> ₽</span>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function selectOption(element) {
    document.querySelectorAll('.option-card').forEach(card => {
        card.classList.remove('selected');
    });
    element.closest('.option-card').classList.add('selected');
}
</script>

<?php include __DIR__ . '/../src/views/layouts/footer.php'; ?>
