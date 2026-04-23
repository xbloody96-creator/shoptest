<?php
// admin/news.php - Управление новостями

session_start();
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/helpers/functions.php';
require_once __DIR__ . '/../../src/helpers/Auth.php';

use App\Helpers\Auth;
use App\Helpers\Functions;
use App\Models\News;

Auth::requireAdmin();

$newsModel = new News();

$message = '';
$error = '';

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $data = [
            'title' => $_POST['title'] ?? '',
            'content' => $_POST['content'] ?? '',
            'author_id' => Auth::id(),
            'rating' => (int)($_POST['rating'] ?? 0)
        ];
        
        if (!empty($_FILES['image']['name'])) {
            $data['image'] = Functions::uploadFile($_FILES['image'], 'news');
        }
        
        try {
            $newsModel->create($data);
            $message = 'Новость успешно создана';
        } catch (\Exception $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'title' => $_POST['title'] ?? '',
            'content' => $_POST['content'] ?? '',
            'rating' => (int)($_POST['rating'] ?? 0)
        ];
        
        if (!empty($_FILES['image']['name'])) {
            $data['image'] = Functions::uploadFile($_FILES['image'], 'news');
        }
        
        try {
            $newsModel->update($id, $data);
            $message = 'Новость успешно обновлена';
        } catch (\Exception $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $newsModel->delete($id);
            $message = 'Новость успешно удалена';
        } catch (\Exception $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    }
}

$newsList = $newsModel->getAll();

$pageTitle = 'Управление новостями';

ob_start();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="font-size: 1.5rem; color: var(--text-primary);">📰 Новости</h2>
    <button class="btn btn-primary" onclick="openModal('createModal')">+ Добавить новость</button>
</div>

<table class="data-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Изображение</th>
            <th>Заголовок</th>
            <th>Автор</th>
            <th>Рейтинг</th>
            <th>Дата</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($newsList as $item): ?>
        <tr>
            <td><?= $item['id'] ?></td>
            <td>
                <img src="<?= htmlspecialchars($item['image'] ?? 'https://via.placeholder.com/50x50?text=News') ?>" 
                     alt="<?= htmlspecialchars($item['title']) ?>" 
                     style="width: 50px; height: 50px; object-fit: cover; border-radius: var(--radius-sm);">
            </td>
            <td><?= htmlspecialchars($item['title']) ?></td>
            <td><?= htmlspecialchars($item['author_name'] ?? 'Админ') ?></td>
            <td>⭐ <?= $item['rating'] ?? 0 ?></td>
            <td><?= date('d.m.Y', strtotime($item['created_at'])) ?></td>
            <td>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить новость?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Модальное окно создания новости -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Добавить новость</h3>
            <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label class="form-label">Заголовок *</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Содержание *</label>
                <textarea name="content" class="form-control" required></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Рейтинг (0-5)</label>
                <input type="number" name="rating" class="form-control" min="0" max="5" value="0">
            </div>
            <div class="form-group">
                <label class="form-label">Изображение</label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary">Создать новость</button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
