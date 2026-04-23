<?php

require_once 'init.php';

use App\Helpers\Auth;
use App\Helpers\Validator;

$errors = [];
$success = false;

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = Auth::register($_POST);
    
    if ($result['success']) {
        $success = true;
        // Автоматическая авторизация после регистрации
        header('Location: /login.php?registered=1');
        exit;
    } else {
        $errors = $result['errors'];
    }
}

$pageTitle = 'Регистрация';
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
        max-width: 550px;
    }
    
    .auth-title {
        text-align: center;
        margin-bottom: 30px;
        font-size: 2rem;
        color: var(--primary-color);
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    
    .auth-alert {
        padding: 15px;
        border-radius: var(--radius-sm);
        margin-bottom: 20px;
    }
    
    .auth-alert.success {
        background: rgba(0, 184, 148, 0.1);
        color: var(--success-color);
        border: 1px solid var(--success-color);
    }
    
    .error-messages {
        background: rgba(214, 48, 49, 0.1);
        border: 1px solid var(--danger-color);
        border-radius: var(--radius-sm);
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .error-messages ul {
        margin: 0;
        padding-left: 20px;
        color: var(--danger-color);
    }
    
    .error-messages li {
        margin-bottom: 5px;
    }
    
    .avatar-upload {
        border: 2px dashed var(--border-color);
        border-radius: var(--radius);
        padding: 30px;
        text-align: center;
        cursor: pointer;
        transition: var(--transition);
        margin-bottom: 20px;
    }
    
    .avatar-upload:hover {
        border-color: var(--primary-color);
        background: var(--bg-secondary);
    }
    
    .avatar-preview {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        margin: 0 auto 15px;
        display: block;
        border: 3px solid var(--border-color);
    }
    
    #avatarInput {
        display: none;
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
    
    @media (max-width: 600px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="auth-container">
    <div class="auth-card fade-in">
        <h1 class="auth-title">Регистрация аккаунта</h1>
        
        <?php if ($success): ?>
        <div class="auth-alert success">
            Регистрация успешна! Перенаправление...
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
        <div class="error-messages">
            <strong>⚠️ Исправьте ошибки:</strong>
            <ul>
                <?php foreach ($errors as $fieldErrors): ?>
                    <?php foreach ($fieldErrors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <!-- Загрузка аватарки -->
            <div class="avatar-upload" onclick="document.getElementById('avatarInput').click()">
                <img id="avatarPreview" class="avatar-preview" src="https://via.placeholder.com/120x120/dfe6e9/636e72?text=Avatar" alt="Аватар">
                <p>📷 Нажмите для загрузки аватарки</p>
                <small style="color: var(--text-muted)">JPG, PNG, GIF до 5MB</small>
                <input type="file" id="avatarInput" name="avatar" accept="image/*" onchange="previewAvatar(this)">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email">E-mail *</label>
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
            
            <div class="form-group">
                <label class="form-label" for="login">Логин *</label>
                <input 
                    type="text" 
                    id="login" 
                    name="login" 
                    class="form-control" 
                    placeholder="Придумайте логин"
                    value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
                    required
                    minlength="3"
                    maxlength="50"
                >
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="full_name">ФИО *</label>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        class="form-control" 
                        placeholder="Иванов Иван Иванович"
                        value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="nickname">Никнейм *</label>
                    <input 
                        type="text" 
                        id="nickname" 
                        name="nickname" 
                        class="form-control" 
                        placeholder="GameMaster"
                        value="<?= htmlspecialchars($_POST['nickname'] ?? '') ?>"
                        required
                    >
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="birth_date">Дата рождения *</label>
                    <input 
                        type="date" 
                        id="birth_date" 
                        name="birth_date" 
                        class="form-control" 
                        value="<?= htmlspecialchars($_POST['birth_date'] ?? '') ?>"
                        required
                        max="<?= date('Y-m-d', strtotime('-13 years')) ?>"
                        min="1940-01-01"
                    >
                    <small style="color: var(--text-muted)">Не старше 1940 года</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="gender">Пол *</label>
                    <select id="gender" name="gender" class="form-control" required>
                        <option value="">Выберите пол</option>
                        <option value="male" <?= ($_POST['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Мужской</option>
                        <option value="female" <?= ($_POST['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Женский</option>
                        <option value="other" <?= ($_POST['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Другой</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="password">Пароль *</label>
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
                    <label class="form-label" for="password_confirm">Повтор пароля *</label>
                    <input 
                        type="password" 
                        id="password_confirm" 
                        name="password_confirm" 
                        class="form-control" 
                        placeholder="Повторите пароль"
                        required
                    >
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                Зарегистрироваться
            </button>
        </form>
        
        <div class="auth-footer">
            Уже есть аккаунт? <a href="/login.php">Войти</a>
        </div>
    </div>
</div>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include __DIR__ . '/../src/views/layouts/footer.php'; ?>
