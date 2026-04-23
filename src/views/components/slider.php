<?php
/**
 * Компонент слайдера для главной страницы
 * Отображает популярные товары, услуги и новости
 */
$sliderItems = $sliderItems ?? [];
?>

<div class="hero-slider" id="heroSlider">
    <div class="slider-container">
        <?php if (empty($sliderItems)): ?>
            <!-- Демо данные если нет данных из БД -->
            <div class="slide active" style="background-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="slide-content">
                    <img src="https://picsum.photos/800/400?random=1" alt="Slide 1" class="slide-image">
                    <div class="slide-info">
                        <h2 class="slide-title">Популярные игры</h2>
                        <p class="slide-description">Лучшие новинки игровой индустрии по выгодным ценам</p>
                        <a href="/products.php" class="btn btn-primary">Подробнее</a>
                    </div>
                </div>
            </div>
            <div class="slide" style="background-image: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="slide-content">
                    <img src="https://picsum.photos/800/400?random=2" alt="Slide 2" class="slide-image">
                    <div class="slide-info">
                        <h2 class="slide-title">Игровые аккаунты</h2>
                        <p class="slide-description">Проверенные аккаунты с гарантией безопасности</p>
                        <a href="/products.php?category=accounts" class="btn btn-primary">Подробнее</a>
                    </div>
                </div>
            </div>
            <div class="slide" style="background-image: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="slide-content">
                    <img src="https://picsum.photos/800/400?random=3" alt="Slide 3" class="slide-image">
                    <div class="slide-info">
                        <h2 class="slide-title">Ключи активации</h2>
                        <p class="slide-description">Официальные ключи для популярных платформ</p>
                        <a href="/products.php?category=keys" class="btn btn-primary">Подробнее</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($sliderItems as $index => $item): ?>
                <div class="slide <?= $index === 0 ? 'active' : '' ?>" 
                     style="background-image: linear-gradient(135deg, <?= $item['gradient_start'] ?? '#667eea' ?> 0%, <?= $item['gradient_end'] ?? '#764ba2' ?> 100%);">
                    <div class="slide-content">
                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="slide-image">
                        <div class="slide-info">
                            <h2 class="slide-title"><?= htmlspecialchars($item['title']) ?></h2>
                            <p class="slide-description"><?= htmlspecialchars($item['description']) ?></p>
                            <a href="<?= htmlspecialchars($item['link']) ?>" class="btn btn-primary">Подробнее</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Навигация слайдера -->
    <button class="slider-nav prev" id="sliderPrev" aria-label="Предыдущий слайд">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M15 18l-6-6 6-6"/>
        </svg>
    </button>
    <button class="slider-nav next" id="sliderNext" aria-label="Следующий слайд">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 18l6-6-6-6"/>
        </svg>
    </button>
    
    <!-- Индикация активных слайдов -->
    <div class="slider-indicators" id="sliderIndicators">
        <?php 
        $slideCount = empty($sliderItems) ? 3 : count($sliderItems);
        for ($i = 0; $i < $slideCount; $i++): 
        ?>
            <button class="indicator <?= $i === 0 ? 'active' : '' ?>" data-slide="<?= $i ?>" aria-label="Перейти к слайду <?= $i + 1 ?>"></button>
        <?php endfor; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const slider = document.getElementById('heroSlider');
    const slides = slider.querySelectorAll('.slide');
    const indicators = slider.querySelectorAll('.indicator');
    const prevBtn = document.getElementById('sliderPrev');
    const nextBtn = document.getElementById('sliderNext');
    let currentSlide = 0;
    let slideInterval;
    
    function showSlide(index) {
        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === index);
        });
        indicators.forEach((indicator, i) => {
            indicator.classList.toggle('active', i === index);
        });
        currentSlide = index;
    }
    
    function nextSlide() {
        const newIndex = (currentSlide + 1) % slides.length;
        showSlide(newIndex);
    }
    
    function prevSlide() {
        const newIndex = (currentSlide - 1 + slides.length) % slides.length;
        showSlide(newIndex);
    }
    
    function startAutoPlay() {
        slideInterval = setInterval(nextSlide, 5000);
    }
    
    function stopAutoPlay() {
        clearInterval(slideInterval);
    }
    
    // Обработчики событий
    nextBtn.addEventListener('click', () => {
        stopAutoPlay();
        nextSlide();
        startAutoPlay();
    });
    
    prevBtn.addEventListener('click', () => {
        stopAutoPlay();
        prevSlide();
        startAutoPlay();
    });
    
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', () => {
            stopAutoPlay();
            showSlide(index);
            startAutoPlay();
        });
    });
    
    // Автозапуск
    startAutoPlay();
    
    // Пауза при наведении
    slider.addEventListener('mouseenter', stopAutoPlay);
    slider.addEventListener('mouseleave', startAutoPlay);
});
</script>

<style>
.hero-slider {
    position: relative;
    width: 100%;
    height: 500px;
    overflow: hidden;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.slider-container {
    position: relative;
    width: 100%;
    height: 100%;
}

.slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 0.6s ease-in-out;
    display: flex;
    align-items: center;
    justify-content: center;
}

.slide.active {
    opacity: 1;
    z-index: 1;
}

.slide-content {
    display: flex;
    gap: 2rem;
    max-width: 1200px;
    padding: 2rem;
    align-items: center;
}

.slide-image {
    width: 500px;
    height: 300px;
    object-fit: cover;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.slide-info {
    flex: 1;
    color: white;
}

.slide-title {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

.slide-description {
    font-size: 1.2rem;
    margin-bottom: 1.5rem;
    line-height: 1.6;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
}

.slider-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: 1rem;
    cursor: pointer;
    border-radius: 50%;
    transition: all 0.3s;
    z-index: 10;
}

.slider-nav:hover {
    background: rgba(255,255,255,0.4);
    transform: translateY(-50%) scale(1.1);
}

.slider-nav.prev {
    left: 20px;
}

.slider-nav.next {
    right: 20px;
}

.slider-indicators {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 10px;
    z-index: 10;
}

.indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid white;
    background: transparent;
    cursor: pointer;
    transition: all 0.3s;
}

.indicator.active {
    background: white;
    transform: scale(1.2);
}

@media (max-width: 768px) {
    .slide-content {
        flex-direction: column;
        text-align: center;
    }
    
    .slide-image {
        width: 100%;
        height: 200px;
    }
    
    .slide-title {
        font-size: 1.8rem;
    }
    
    .slide-description {
        font-size: 1rem;
    }
}
</style>
