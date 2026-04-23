<?php
/**
 * API для авторизации и управления пользователями
 */

require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/helpers/auth.php';
require_once __DIR__ . '/../../src/models/User.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    $userModel = new User($pdo);

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if ($action === 'login') {
            // Авторизация
            if (empty($input['email']) || empty($input['password'])) {
                throw new Exception('Введите email и пароль');
            }

            $user = $userModel->findByEmail($input['email']);
            
            if (!$user || !password_verify($input['password'], $user['password'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Неверный email или пароль']);
                exit;
            }

            // Создание сессии
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            // Обновление последнего входа
            $userModel->updateLastLogin($user['id']);

            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'name' => $user['full_name'],
                    'nickname' => $user['nickname'],
                    'avatar' => $user['avatar'],
                    'role' => $user['role']
                ]
            ]);

        } elseif ($action === 'register') {
            // Регистрация нового пользователя
            $required = ['email', 'login', 'full_name', 'nickname', 'birth_date', 'gender', 'password', 'password_confirm'];
            
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Поле '$field' обязательно для заполнения");
                }
            }

            if ($input['password'] !== $input['password_confirm']) {
                throw new Exception('Пароли не совпадают');
            }

            if (strlen($input['password']) < 6) {
                throw new Exception('Пароль должен быть не менее 6 символов');
            }

            // Проверка даты рождения (не старше 1940 года)
            $birthDate = new DateTime($input['birth_date']);
            $minDate = new DateTime('1940-12-31');
            if ($birthDate < $minDate) {
                throw new Exception('Дата рождения не может быть раньше 1940 года');
            }

            // Проверка email на уникальность
            if ($userModel->findByEmail($input['email'])) {
                throw new Exception('Email уже зарегистрирован');
            }

            // Проверка логина на уникальность
            if ($userModel->findByLogin($input['login'])) {
                throw new Exception('Логин уже занят');
            }

            // Хэширование пароля
            $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);

            // Обработка аватара
            $avatarPath = null;
            if (!empty($input['avatar_data'])) {
                // Ожидается base64 строка
                $avatarData = preg_replace('#^data:image/\w+;base64,#i', '', $input['avatar_data']);
                $avatarData = base64_decode($avatarData);
                
                if ($avatarData !== false) {
                    $uploadDir = __DIR__ . '/../../' . getenv('UPLOAD_DIR');
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $filename = uniqid() . '.jpg';
                    file_put_contents($uploadDir . '/' . $filename, $avatarData);
                    $avatarPath = 'assets/images/uploads/' . $filename;
                }
            }

            $userId = $userModel->create([
                'email' => $input['email'],
                'login' => $input['login'],
                'full_name' => $input['full_name'],
                'nickname' => $input['nickname'],
                'birth_date' => $input['birth_date'],
                'gender' => $input['gender'],
                'password' => $hashedPassword,
                'avatar' => $avatarPath
            ]);

            // Автоматическая авторизация после регистрации
            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_email'] = $input['email'];
            $_SESSION['user_role'] = 'user';

            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $userId,
                    'email' => $input['email'],
                    'name' => $input['full_name'],
                    'nickname' => $input['nickname'],
                    'role' => 'user'
                ]
            ]);

        } elseif ($action === 'logout') {
            // Выход из системы
            session_destroy();
            echo json_encode(['success' => true]);

        } elseif ($action === 'check') {
            // Проверка авторизации
            if (isLoggedIn()) {
                $user = $userModel->getById(getCurrentUserId());
                echo json_encode([
                    'success' => true,
                    'authenticated' => true,
                    'user' => [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'name' => $user['full_name'],
                        'nickname' => $user['nickname'],
                        'avatar' => $user['avatar'],
                        'role' => $user['role']
                    ]
                ]);
            } else {
                echo json_encode(['success' => true, 'authenticated' => false]);
            }

        } elseif ($action === 'forgot_password' && !empty($input['email'])) {
            // Запрос на восстановление пароля
            $user = $userModel->findByEmail($input['email']);
            
            if ($user) {
                // Генерация токена
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Сохранение токена в БД (нужно добавить таблицу password_resets)
                $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?) 
                                      ON DUPLICATE KEY UPDATE token=?, expires_at=?");
                $stmt->execute([$input['email'], $token, $expiresAt, $token, $expiresAt]);
                
                // В реальном проекте здесь была бы отправка email
                // Для демонстрации возвращаем токен
                echo json_encode([
                    'success' => true, 
                    'message' => 'Инструкции отправлены на email',
                    'debug_token' => $token // Удалить в продакшене
                ]);
            } else {
                // Не показываем是否存在 пользователя для безопасности
                echo json_encode(['success' => true, 'message' => 'Если email существует, инструкции отправлены']);
            }

        } elseif ($action === 'reset_password' && !empty($input['token']) && !empty($input['password'])) {
            // Сброс пароля по токену
            if (strlen($input['password']) < 6) {
                throw new Exception('Пароль должен быть не менее 6 символов');
            }

            $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
            $stmt->execute([$input['token']]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reset) {
                throw new Exception('Неверный или истекший токен');
            }

            $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
            $userModel->updatePasswordByEmail($reset['email'], $hashedPassword);

            // Удаление использованного токена
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$reset['email']]);

            echo json_encode(['success' => true, 'message' => 'Пароль успешно изменен']);

        } else {
            echo json_encode(['success' => false, 'error' => 'Неверное действие']);
        }

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Метод не поддерживается']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
