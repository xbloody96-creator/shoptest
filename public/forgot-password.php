<?php
// forgot-password.php - Восстановление пароля

require_once 'init.php';

use App\Helpers\Auth;

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Введите email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный формат email';
    } else {
        // Проверка наличия пользователя
        $pdo = \App\Config\Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT id, email FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Генерация токена сброса
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at) ON DUPLICATE KEY UPDATE token = :token2, expires_at = :expires_at2");
            $stmt->execute([
                'user_id' => $user['id'],
                'token' => hash('sha256', $token),
                'expires_at' => $expiresAt,
                'token2' => hash('sha256', $token),
                'expires_at2' => $expiresAt
            ]);
            
            // В реальном проекте здесь была бы отправка email
            // Для демонстрации покажем ссылку
            $resetLink = 'http://' . $_SERVER['HTTP_HOST'] . '/reset-password.php?token=' . $token;
            $success = 'На ваш email отправлена инструкция по сбросу пароля.<br><br><strong>Для демонстрации:</strong><br><a href="' . htmlspecialchars($resetLink) . '">' . htmlspecialchars($resetLink) . '</a>';
        } else {
            // Не показываем, существует ли пользователь (безопасность)
            $success = 'Если пользователь с таким email существует, инструкция по сбросу пароля будет отправлена.';
        }
    }
}

$pageTitle = 'Восстановление пароля';
include __DIR__ . '/../src/views/layouts/header.php';
?>

<style>
    .auth-container {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }
    
    .auth-card {
        background: var(--bg-color);
        border-radius: var(--radius);
        box-shadow: var(--shadow-lg);
        padding: 40px;
        width: 100%;
        max-width: 450px;
    }
    
    .auth-title {
        text-align: center;
        margin-bottom: 20px;
        font-size: 2rem;
        color: var(--primary-color);
    }
    
    .auth-subtitle {
        text-align: center;
        color: var(--text-muted);
        margin-bottom: 30px;
        line-height: 1.6;
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
    
    .back-link {
        text-align: center;
        margin-top: 20px;
    }
    
    .back-link a {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 600;
    }
    
    .back-link a:hover {
        text-decoration: underline;
    }
</style>

<div class="auth-container">
    <div class="auth-card fade-in">
        <h1 class="auth-title">🔑 Восстановление пароля</h1>
        <p class="auth-subtitle">Введите email, указанный при регистрации. Мы отправим на него инструкцию по восстановлению пароля.</p>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="example@mail.ru"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                >
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Отправить инструкцию
            </button>
        </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="/login.php">← Вернуться ко входу</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../src/views/layouts/footer.php'; ?>
