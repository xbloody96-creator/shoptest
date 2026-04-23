-- Начальные данные для GameStore

-- Администратор (пароль: admin123)
INSERT INTO users (email, login, full_name, nickname, birth_date, gender, password, is_admin, created_at) 
VALUES 
('admin@gamestore.com', 'admin', 'Администратор Системы', 'Admin', '1990-01-01', 'male', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW()),
('test@test.com', 'testuser', 'Тестовый Пользователь', 'Tester', '1995-05-15', 'male', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, NOW());

-- Категории
INSERT INTO categories (name, slug, description) VALUES
('Игры', 'games', 'Компьютерные игры для PC и консолей'),
('Ключи активации', 'keys', 'Ключи для Steam, Origin, Uplay и других платформ'),
('Аккаунты', 'accounts', 'Готовые аккаунты с играми'),
('Подарочные карты', 'gift-cards', 'Подарочные карты пополнения'),
('DLC и Дополнения', 'dlc', 'Дополнения к играм'),
('Софт', 'software', 'Программное обеспечение'),
('Подписки', 'subscriptions', 'Подписки на сервисы');

-- Товары (примеры)
INSERT INTO products (name, description, price, image, category_id, stock, type, rating) VALUES
('Cyberpunk 2077', 'Ролевая игра в открытом мире от CD Projekt RED', 1999.00, '/assets/images/products/cyberpunk.jpg', 1, 100, 'game', 4.5),
('The Witcher 3: Wild Hunt', 'Легендарная RPG о ведьмаке Геральте', 999.00, '/assets/images/products/witcher3.jpg', 1, 150, 'game', 5.0),
('Steam Key - GTA V', 'Ключ активации для Steam', 1499.00, '/assets/images/products/gtav.jpg', 2, 500, 'key', 4.8),
('Origin Account - FIFA 24', 'Аккаунт с игрой FIFA 24', 2499.00, '/assets/images/products/fifa24.jpg', 3, 50, 'account', 4.2),
('PlayStation Store Card 1000₽', 'Подарочная карта для PS Store', 1000.00, '/assets/images/products/ps-card.jpg', 4, 1000, 'gift-card', 4.9),
('Xbox Game Pass Ultimate 3 месяца', 'Подписка на Game Pass Ultimate', 1699.00, '/assets/images/products/gamepass.jpg', 7, 200, 'subscription', 4.7);

-- Новости
INSERT INTO news (title, content, image, author_id, rating, published_at) VALUES
('Большая летняя распродажа!', 'Скидки до 80% на тысячи игр! Успейте купить любимые игры по выгодным ценам. Распродажа продлится до конца месяца.', '/assets/images/news/sale.jpg', 1, 5, NOW()),
('Новое поступление ключей Steam', 'В наличии появились ключи для новых релизов. Успейте приобрести по стартовым ценам!', '/assets/images/news/steam-keys.jpg', 1, 4, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('Обновление системы отзывов', 'Мы улучшили систему модерации отзывов. Теперь ваши отзывы проходят проверку быстрее.', '/assets/images/news/reviews.jpg', 1, 3, DATE_SUB(NOW(), INTERVAL 7 DAY));

-- Услуги
INSERT INTO services (name, description, price, image, category_id, available_slots, duration) VALUES
('Установка игры', 'Профессиональная установка и настройка игры на ваш ПК', 500.00, '/assets/images/services/install.jpg', 1, 10, 60),
('Настройка аккаунта', 'Помощь в настройке и привязке игрового аккаунта', 300.00, '/assets/images/services/account-setup.jpg', 3, 15, 30),
('Консультация геймера', 'Индивидуальная консультация по прохождению сложных моментов', 800.00, '/assets/images/services/consultation.jpg', 1, 5, 90);

-- Отзывы
INSERT INTO reviews (product_id, user_id, rating, comment, approved, created_at) VALUES
(1, 2, 5, 'Отличная игра! Графика на высоте, сюжет захватывает.', 1, NOW()),
(2, 2, 5, 'Шедевр от CD Projekt. Рекомендую всем!', 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(3, 2, 4, 'Ключ активировался без проблем. Быстрая доставка.', 1, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Акции
INSERT INTO promotions (title, description, discount_percent, start_date, end_date, image) VALUES
('Летняя распродажа', 'Скидки на все игры жанра RPG', 50, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), '/assets/images/promos/summer-sale.jpg'),
('Новым клиентам', 'Скидка 10% на первый заказ', 10, NOW(), DATE_ADD(NOW(), INTERVAL 365 DAY), '/assets/images/promos/new-client.jpg');
