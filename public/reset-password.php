<?php
// reset-password.php - Сброс пароля по токену

require_once 'init.php';

use App\Helpers\Auth;

$error = null;
$success = null;
$token = $_GET['token'] ?? '';
$validToken = false;

if (empty($token)) {
    $error = 'Токен сброса не указан';
} else {
    // Проверка токена
    $pdo = \App\Config\Database::getInstance()->getConnection();
    $stmt = $pdo->prepare("
        SELECT pr.*, u.id as user_id 
        FROM password_resets pr
        JOIN users u ON pr.user_id = u.id
        WHERE pr.token = :token AND pr.expires_at > NOW()
    ");
    $stmt->execute(['token' => hash('sha256', $token)]);
    $reset = $stmt->fetch();
    
    if ($reset) {
        $validToken = true;
    } else {
        $error = 'Неверный или истекший токен сброса';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    
    if (empty($password)) {
        $error = 'Введите новый пароль';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Пароли не совпадают';
    } else {
        try {
            // Обновление пароля
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = :password, updated_at = NOW() WHERE id = :user_id");
            $stmt->execute([
                'password' => $hashedPassword,
                'user_id' => $reset['user_id']
            ]);
            
            // Удаление использованного токена
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $reset['user_id']]);
            
            $success = 'Пароль успешно изменен! Теперь вы можете войти с новым паролем.';
            $validToken = false;
        } catch (\Exception $e) {
            $error = 'Ошибка при обновлении пароля: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Сброс пароля';
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
</style>

<div class="auth-container">
    <div class="auth-card fade-in">
        <h1 class="auth-title">🔐 Новый пароль</h1>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
        <div class="back-link">
            <a href="/login.php">Перейти ко входу</a>
        </div>
        <?php else: ?>
        
        <?php if ($validToken): ?>
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="password">Новый пароль</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="Минимум 6 символов"
                    required
                    minlength="6"
                >
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password_confirm">Подтверждение пароля</label>
                <input 
                    type="password" 
                    id="password_confirm" 
                    name="password_confirm" 
                    class="form-control" 
                    placeholder="Повторите пароль"
                    required
                >
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Изменить пароль
            </button>
        </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="/login.php">← Вернуться ко входу</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../src/views/layouts/footer.php'; ?>
