<?php
// admin/orders.php - Управление заказами

session_start();
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/helpers/functions.php';
require_once __DIR__ . '/../../src/helpers/Auth.php';

use App\Helpers\Auth;
use App\Helpers\Functions;
use App\Models\Order;
use App\Config\Database;

Auth::requireAdmin();

$orderModel = new Order();

$message = '';
$error = '';

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'pending';
        
        try {
            $orderModel->updateStatus($id, $status);
            $message = 'Статус заказа успешно обновлен';
        } catch (\Exception $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $orderModel->delete($id);
            $message = 'Заказ успешно удален';
        } catch (\Exception $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    }
}

$orders = $orderModel->getAllWithDetails();

$pageTitle = 'Управление заказами';

ob_start();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="font-size: 1.5rem; color: var(--text-primary);">📦 Заказы</h2>
</div>

<table class="data-table">
    <thead>
        <tr>
            <th>№ заказа</th>
            <th>Клиент</th>
            <th>Сумма</th>
            <th>Статус оплаты</th>
            <th>Статус</th>
            <th>Дата</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($orders as $order): ?>
        <tr>
            <td><?= htmlspecialchars($order['order_number']) ?></td>
            <td><?= htmlspecialchars($order['customer_email'] ?? $order['email'] ?? 'Гость') ?></td>
            <td><?= number_format($order['total_amount'], 0, '.', ' ') ?> ₽</td>
            <td>
                <span class="status-badge status-<?= $order['payment_status'] ?>">
                    <?= $order['payment_status'] === 'paid' ? 'Оплачен' : ($order['payment_status'] === 'pending' ? 'Ожидает' : $order['payment_status']) ?>
                </span>
            </td>
            <td>
                <span class="status-badge status-<?= $order['status'] ?>">
                    <?= $order['status'] === 'completed' ? 'Завершен' : ($order['status'] === 'cancelled' ? 'Отменен' : $order['status']) ?>
                </span>
            </td>
            <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
            <td>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" value="<?= $order['id'] ?>">
                    <select name="status" class="form-control" style="padding: 5px; width: auto; display: inline-block;" onchange="this.form.submit()">
                        <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>В обработке</option>
                        <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Обрабатывается</option>
                        <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Завершен</option>
                        <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Отменен</option>
                    </select>
                </form>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить заказ?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $order['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
