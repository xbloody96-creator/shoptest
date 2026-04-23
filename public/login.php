<?php

require_once 'init.php';

use App\Helpers\Auth;
use App\Helpers\Validator;

$error = null;
$success = null;

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailOrLogin = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($emailOrLogin) || empty($password)) {
        $error = 'Введите email/логин и пароль';
    } else {
        if (Auth::login($emailOrLogin, $password)) {
            if ($remember) {
                // Устанавливаем длительный куки
                setcookie('remember_token', $_SESSION['auth_token'], time() + (30 * 24 * 60 * 60), '/');
            }
            
            // Перенаправление на главную или туда, откуда пришел пользователь
            $redirect = $_GET['redirect'] ?? '/profile.php';
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Неверный email/логин или пароль';
        }
    }
}

$pageTitle = 'Авторизация';
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
        margin-bottom: 30px;
        font-size: 2rem;
        color: var(--primary-color);
    }
    
    .auth-alert {
        padding: 15px;
        border-radius: var(--radius-sm);
        margin-bottom: 20px;
    }
    
    .auth-alert.error {
        background: rgba(214, 48, 49, 0.1);
        color: var(--danger-color);
        border: 1px solid var(--danger-color);
    }
    
    .auth-alert.success {
        background: rgba(0, 184, 148, 0.1);
        color: var(--success-color);
        border: 1px solid var(--success-color);
    }
    
    .form-options {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    
    .forgot-password {
        color: var(--primary-color);
        font-weight: 500;
    }
    
    .forgot-password:hover {
        text-decoration: underline;
    }
    
    .auth-footer {
        text-align: center;
        margin-top: 20px;
        color: var(--text-muted);
    }
    
    .auth-footer a {
        color: var(--primary-color);
        font-weight: 600;
    }
</style>

<div class="auth-container">
    <div class="auth-card fade-in">
        <h1 class="auth-title">Вход в аккаунт</h1>
        
        <?php if ($error): ?>
        <div class="auth-alert error">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="auth-alert success">
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="email">Email или логин</label>
                <input 
                    type="text" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="Введите email или логин"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                >
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Пароль</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="Введите пароль"
                    required
                >
            </div>
            
            <div class="form-options">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" value="1">
                    <span>Запомнить меня</span>
                </label>
                <a href="/forgot-password.php" class="forgot-password">Забыли пароль?</a>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Войти
            </button>
        </form>
        
        <div class="auth-footer">
            Нет аккаунта? <a href="/register.php">Зарегистрироваться</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../src/views/layouts/footer.php'; ?>
