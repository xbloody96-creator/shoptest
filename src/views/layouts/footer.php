    </main>
    
    <footer class="footer" id="contacts">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Контакты</h4>
                    <div class="contact-item">
                        <span>📞</span>
                        <a href="tel:+79991234567">+7 (999) 123-45-67</a>
                    </div>
                    <div class="contact-item">
                        <span>✉️</span>
                        <a href="mailto:info@gamestore.com">info@gamestore.com</a>
                    </div>
                    <div class="contact-item">
                        <span>📍</span>
                        <span>г. Москва, ул. Примерная, д. 1</span>
                    </div>
                    
                    <div class="social-links">
                        <a href="#" class="social-link" title="VK">VK</a>
                        <a href="#" class="social-link" title="Telegram">TG</a>
                        <a href="#" class="social-link" title="Discord">DC</a>
                        <a href="#" class="social-link" title="YouTube">YT</a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>Категории</h4>
                    <ul>
                        <li><a href="/products.php?category=games">Игры</a></li>
                        <li><a href="/products.php?category=keys">Ключи активации</a></li>
                        <li><a href="/products.php?category=accounts">Аккаунты</a></li>
                        <li><a href="/products.php?category=gift-cards">Подарочные карты</a></li>
                        <li><a href="/products.php?category=dlc">DLC и Дополнения</a></li>
                        <li><a href="/products.php?category=software">Софт</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Информация</h4>
                    <ul>
                        <li><a href="/#about">О нас</a></li>
                        <li><a href="/news.php">Новости</a></li>
                        <li><a href="/#promotions">Акции</a></li>
                        <li><a href="/services.php">Услуги</a></li>
                        <li><a href="/reviews.php">Отзывы</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Мы на карте</h4>
                    <div class="map-container">
                        <iframe src="https://www.openstreetmap.org/export/embed.html?bbox=37.6173,55.7558,37.6173,55.7558&layer=mapnik" 
                                style="width: 100%; height: 100%; border: 0;" allowfullscreen="" loading="lazy">
                        </iframe>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= $GLOBALS['siteName'] ?>. Все права защищены.</p>
            </div>
        </div>
    </footer>
    
    <script src="/assets/js/main.js"></script>
</body>
</html>
