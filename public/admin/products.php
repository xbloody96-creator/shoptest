<?php
// admin/products.php - Управление товарами

session_start();
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/helpers/functions.php';
require_once __DIR__ . '/../../src/helpers/Auth.php';

use App\Helpers\Auth;
use App\Helpers\Functions;
use App\Models\Product;
use App\Models\Category;

Auth::requireAdmin();

$productModel = new Product();
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
            'stock' => (int)($_POST['stock'] ?? 0),
            'type' => $_POST['type'] ?? 'game'
        ];
        
        if (!empty($_FILES['image']['name'])) {
            $data['image'] = Functions::uploadFile($_FILES['image'], 'products');
        }
        
        try {
            $productModel->create($data);
            $message = 'Товар успешно создан';
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
            'stock' => (int)($_POST['stock'] ?? 0)
        ];
        
        if (!empty($_FILES['image']['name'])) {
            $data['image'] = Functions::uploadFile($_FILES['image'], 'products');
        }
        
        try {
            $productModel->update($id, $data);
            $message = 'Товар успешно обновлен';
        } catch (\Exception $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $productModel->delete($id);
            $message = 'Товар успешно удален';
        } catch (\Exception $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    }
}

$products = $productModel->getAll();
$categories = $categoryModel->getAll();

$pageTitle = 'Управление товарами';

ob_start();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="font-size: 1.5rem; color: var(--text-primary);">📦 Товары</h2>
    <button class="btn btn-primary" onclick="openModal('createModal')">+ Добавить товар</button>
</div>

<!-- Таблица товаров -->
<table class="data-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Изображение</th>
            <th>Название</th>
            <th>Категория</th>
            <th>Цена</th>
            <th>Остаток</th>
            <th>Статус</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($products as $product): ?>
        <tr>
            <td><?= $product['id'] ?></td>
            <td>
                <img src="<?= htmlspecialchars($product['image'] ?? 'https://via.placeholder.com/50x50?text=No+Image') ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>" 
                     style="width: 50px; height: 50px; object-fit: cover; border-radius: var(--radius-sm);">
            </td>
            <td><?= htmlspecialchars($product['name']) ?></td>
            <td><?= htmlspecialchars($product['category_name'] ?? 'Без категории') ?></td>
            <td><?= number_format($product['price'], 0, '.', ' ') ?> ₽</td>
            <td><?= $product['stock'] ?? 0 ?></td>
            <td>
                <span class="status-badge <?= $product['status'] ? 'status-active' : 'status-inactive' ?>">
                    <?= $product['status'] ? 'Активен' : 'Неактивен' ?>
                </span>
            </td>
            <td>
                <button class="btn btn-outline btn-sm" onclick="editItem(<?= $product['id'] ?>, '<?= addslashes($product['name']) ?>', <?= $product['price'] ?>, '<?= addslashes($product['description'] ?? '') ?>', <?= $product['stock'] ?? 0 ?>, <?= $product['category_id'] ?? 0 ?>)">✏️</button>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить товар?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Модальное окно создания -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Добавить товар</h3>
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
                    <label class="form-label">Остаток</label>
                    <input type="number" name="stock" class="form-control" value="0" min="0">
                </div>
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
                <label class="form-label">Тип</label>
                <select name="type" class="form-control">
                    <option value="game">Игра</option>
                    <option value="account">Аккаунт</option>
                    <option value="key">Ключ</option>
                    <option value="dlc">DLC</option>
                    <option value="subscription">Подписка</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Изображение</label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary">Создать товар</button>
        </form>
    </div>
</div>

<!-- Модальное окно редактирования -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Редактировать товар</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit-id">
            <div class="form-group">
                <label class="form-label">Название *</label>
                <input type="text" name="name" id="edit-name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Описание</label>
                <textarea name="description" id="edit-description" class="form-control"></textarea>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label">Цена (₽) *</label>
                    <input type="number" name="price" id="edit-price" class="form-control" required min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Остаток</label>
                    <input type="number" name="stock" id="edit-stock" class="form-control" min="0">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Категория</label>
                <select name="category_id" id="edit-category-id" class="form-control">
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
            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
