<?php
/**
 * Страница "О нас"
 */
?>

<section class="about-section">
    <div class="container">
        <div class="section-header">
            <h1 class="section-title">О компании GameStore</h1>
            <p class="section-subtitle">Ваш надежный партнер в мире цифровых развлечений</p>
        </div>
        
        <div class="about-content">
            <div class="about-text">
                <h2>Кто мы?</h2>
                <p>
                    GameStore - это современный интернет-магазин цифровых товаров, основанный в 2024 году. 
                    Мы специализируемся на продаже игр, игровых аккаунтов, ключей активации и сопутствующих услуг.
                </p>
                
                <h3>Наша миссия</h3>
                <p>
                    Предоставлять игрокам быстрый и безопасный доступ к лучшим цифровым продуктам по выгодным ценам.
                    Мы стремимся сделать процесс покупки максимально простым и удобным для каждого клиента.
                </p>
                
                <h3>Почему выбирают нас?</h3>
                <ul class="about-features">
                    <li>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        <span>Гарантия безопасности всех покупок</span>
                    </li>
                    <li>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <span>Мгновенная доставка товаров</span>
                    </li>
                    <li>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/>
                        </svg>
                        <span>Проверенные продавцы и товары</span>
                    </li>
                    <li>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
                        </svg>
                        <span>Удобные способы оплаты</span>
                    </li>
                    <li>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                        <span>Поддержка 24/7</span>
                    </li>
                    <li>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                        <span>Лучшие цены на рынке</span>
                    </li>
                </ul>
                
                <h3>Наши показатели</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">50,000+</div>
                        <div class="stat-label">Довольных клиентов</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">10,000+</div>
                        <div class="stat-label">Товаров в каталоге</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">99%</div>
                        <div class="stat-label">Положительных отзывов</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Работа поддержки</div>
                    </div>
                </div>
            </div>
            
            <div class="about-image">
                <img src="https://picsum.photos/600/800?random=100" alt="О компании" class="about-main-image">
            </div>
        </div>
        
        <div class="team-section">
            <h2>Наша команда</h2>
            <p class="team-description">
                Профессионалы, которые делают ваш игровой опыт лучше каждый день
            </p>
            
            <div class="team-grid">
                <div class="team-member">
                    <img src="https://picsum.photos/200/200?random=1" alt="CEO" class="member-avatar">
                    <h4>Александр Петров</h4>
                    <p class="member-role">Основатель и CEO</p>
                </div>
                <div class="team-member">
                    <img src="https://picsum.photos/200/200?random=2" alt="CTO" class="member-avatar">
                    <h4>Дмитрий Иванов</h4>
                    <p class="member-role">Технический директор</p>
                </div>
                <div class="team-member">
                    <img src="https://picsum.photos/200/200?random=3" alt="CMO" class="member-avatar">
                    <h4>Елена Смирнова</h4>
                    <p class="member-role">Маркетинг директор</p>
                </div>
                <div class="team-member">
                    <img src="https://picsum.photos/200/200?random=4" alt="Support Lead" class="member-avatar">
                    <h4>Максим Козлов</h4>
                    <p class="member-role">Руководитель поддержки</p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.about-section {
    padding: 4rem 0;
}

.section-header {
    text-align: center;
    margin-bottom: 3rem;
}

.section-title {
    font-size: 2.5rem;
    font-weight: bold;
    color: var(--text-color);
    margin-bottom: 1rem;
}

.section-subtitle {
    font-size: 1.2rem;
    color: var(--text-muted);
}

.about-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    margin-bottom: 4rem;
    align-items: start;
}

.about-text h2 {
    font-size: 1.8rem;
    color: var(--text-color);
    margin-bottom: 1.5rem;
}

.about-text h3 {
    font-size: 1.4rem;
    color: var(--text-color);
    margin: 2rem 0 1rem;
}

.about-text p {
    color: var(--text-muted);
    line-height: 1.8;
    margin-bottom: 1rem;
}

.about-features {
    list-style: none;
    padding: 0;
    margin: 1.5rem 0;
}

.about-features li {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    color: var(--text-color);
}

.about-features svg {
    color: var(--primary-color);
    flex-shrink: 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-top: 2rem;
}

.stat-card {
    background: var(--card-bg);
    padding: 1.5rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.9rem;
    color: var(--text-muted);
}

.about-image {
    position: sticky;
    top: 2rem;
}

.about-main-image {
    width: 100%;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.team-section {
    text-align: center;
    padding-top: 3rem;
    border-top: 1px solid var(--border-color);
}

.team-section h2 {
    font-size: 2rem;
    color: var(--text-color);
    margin-bottom: 0.5rem;
}

.team-description {
    color: var(--text-muted);
    margin-bottom: 2rem;
}

.team-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2rem;
}

.team-member {
    text-align: center;
}

.member-avatar {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 1rem;
    border: 4px solid var(--primary-color);
}

.team-member h4 {
    font-size: 1.1rem;
    color: var(--text-color);
    margin-bottom: 0.25rem;
}

.member-role {
    color: var(--text-muted);
    font-size: 0.9rem;
}

@media (max-width: 1024px) {
    .about-content {
        grid-template-columns: 1fr;
    }
    
    .about-image {
        position: static;
        order: -1;
    }
    
    .team-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .team-grid {
        grid-template-columns: 1fr;
    }
    
    .section-title {
        font-size: 2rem;
    }
}
</style>
