<?php
// admin/services.php - Управление услугами

session_start();
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/helpers/functions.php';
require_once __DIR__ . '/../../src/helpers/Auth.php';

use App\Helpers\Auth;
use App\Helpers\Functions;
use App\Models\Service;
use App\Models\Category;

Auth::requireAdmin();

$serviceModel = new Service();
$categoryModel = new Category();

$message = '';
$error = '';

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'price' => (float)($_POST['price'] ?? 0),
            'category_id' => (int)($_POST['category_id'] ?? 0),
            'available_slots' => (int)($_POST['available_slots'] ?? 0),
            'duration' => (int)($_POST['duration'] ?? 60)
        ];
        
        if (!empty($_FILES['image']['name'])) {
            $data['image'] = Functions::uploadFile($_FILES['image'], 'services');
        }
        
        try {
            $serviceModel->create($data);
            $message = 'Услуга успешно создана';
        } catch (\Exception $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'price' => (float)($_POST['price'] ?? 0),
            'category_id' => (int)($_POST['category_id'] ?? 0),
            'available_slots' => (int)($_POST['available_slots'] ?? 0),
            'duration' => (int)($_POST['duration'] ?? 60)
        ];
        
        if (!empty($_FILES['image']['name'])) {
            $data['image'] = Functions::uploadFile($_FILES['image'], 'services');
        }
        
        try {
            $serviceModel->update($id, $data);
            $message = 'Услуга успешно обновлена';
        } catch (\Exception $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $serviceModel->delete($id);
            $message = 'Услуга успешно удалена';
        } catch (\Exception $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    }
}

$services = $serviceModel->getAll();
$categories = $categoryModel->getAll();

$pageTitle = 'Управление услугами';

ob_start();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="font-size: 1.5rem; color: var(--text-primary);">🛠️ Услуги</h2>
    <button class="btn btn-primary" onclick="openModal('createModal')">+ Добавить услугу</button>
</div>

<table class="data-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Изображение</th>
            <th>Название</th>
            <th>Цена</th>
            <th>Длительность</th>
            <th>Свободно мест</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($services as $service): ?>
        <tr>
            <td><?= $service['id'] ?></td>
            <td>
                <img src="<?= htmlspecialchars($service['image'] ?? 'https://via.placeholder.com/50x50?text=Service') ?>" 
                     alt="<?= htmlspecialchars($service['name']) ?>" 
                     style="width: 50px; height: 50px; object-fit: cover; border-radius: var(--radius-sm);">
            </td>
            <td><?= htmlspecialchars($service['name']) ?></td>
            <td><?= number_format($service['price'], 0, '.', ' ') ?> ₽</td>
            <td><?= $service['duration'] ?? 60 ?> мин</td>
            <td><?= $service['available_slots'] ?? 0 ?></td>
            <td>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить услугу?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $service['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Модальное окно создания услуги -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Добавить услугу</h3>
            <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label class="form-label">Название *</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Описание</label>
                <textarea name="description" class="form-control"></textarea>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label">Цена (₽) *</label>
                    <input type="number" name="price" class="form-control" required min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Длительность (мин)</label>
                    <input type="number" name="duration" class="form-control" value="60" min="15">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Доступные места</label>
                <input type="number" name="available_slots" class="form-control" value="10" min="0">
            </div>
            <div class="form-group">
                <label class="form-label">Категория</label>
                <select name="category_id" class="form-control">
                    <option value="0">Без категории</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Изображение</label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary">Создать услугу</button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
