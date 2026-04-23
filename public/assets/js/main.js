// ========================================
// GameStore - Основной JavaScript
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    initThemeToggle();
    initAccessibilityToggle();
    initSlider();
    initHeaderScroll();
    initFormValidation();
    initAvatarUpload();
});

// Переключение темы
function initThemeToggle() {
    const themeToggle = document.querySelector('.theme-toggle');
    if (!themeToggle) return;
    
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(themeToggle, savedTheme);
    
    themeToggle.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeIcon(this, newTheme);
    });
}

function updateThemeIcon(button, theme) {
    button.innerHTML = theme === 'dark' ? '☀️' : '🌙';
    button.setAttribute('title', theme === 'dark' ? 'Светлая тема' : 'Темная тема');
}

// Режим для слабовидящих
function initAccessibilityToggle() {
    const accToggle = document.querySelector('.accessibility-toggle');
    if (!accToggle) return;
    
    const savedMode = localStorage.getItem('accessibility') || 'normal';
    document.documentElement.setAttribute('data-accessibility', savedMode);
    
    accToggle.addEventListener('click', function() {
        const modes = ['normal', 'large-text', 'high-contrast'];
        const currentMode = document.documentElement.getAttribute('data-accessibility') || 'normal';
        const currentIndex = modes.indexOf(currentMode);
        const newMode = modes[(currentIndex + 1) % modes.length];
        
        document.documentElement.setAttribute('data-accessibility', newMode);
        localStorage.setItem('accessibility', newMode);
        
        const titles = {
            'normal': 'Обычный режим',
            'large-text': 'Крупный текст',
            'high-contrast': 'Высокая контрастность'
        };
        
        this.setAttribute('title', titles[newMode]);
        showToast('Режим: ' + titles[newMode], 'success');
    });
}

// Слайдер
let currentSlide = 0;
let slideInterval;

function initSlider() {
    const slides = document.querySelectorAll('.slide');
    if (slides.length === 0) return;
    
    showSlide(0);
    startAutoSlide();
    
    const prevBtn = document.querySelector('.slider-btn.prev');
    const nextBtn = document.querySelector('.slider-btn.next');
    
    if (prevBtn) prevBtn.addEventListener('click', () => changeSlide(-1));
    if (nextBtn) nextBtn.addEventListener('click', () => changeSlide(1));
    
    document.querySelectorAll('.indicator').forEach((indicator, index) => {
        indicator.addEventListener('click', () => {
            showSlide(index);
            resetAutoSlide();
        });
    });
    
    const sliderContainer = document.querySelector('.slider-container');
    if (sliderContainer) {
        sliderContainer.addEventListener('mouseenter', stopAutoSlide);
        sliderContainer.addEventListener('mouseleave', startAutoSlide);
    }
}

function showSlide(index) {
    const slides = document.querySelectorAll('.slide');
    const indicators = document.querySelectorAll('.indicator');
    
    if (index >= slides.length) index = 0;
    if (index < 0) index = slides.length - 1;
    
    slides.forEach(slide => slide.classList.remove('active'));
    indicators.forEach(ind => ind.classList.remove('active'));
    
    slides[index].classList.add('active');
    if (indicators[index]) indicators[index].classList.add('active');
    
    currentSlide = index;
}

function changeSlide(direction) {
    showSlide(currentSlide + direction);
    resetAutoSlide();
}

function startAutoSlide() {
    slideInterval = setInterval(() => changeSlide(1), 5000);
}

function stopAutoSlide() {
    clearInterval(slideInterval);
}

function resetAutoSlide() {
    stopAutoSlide();
    startAutoSlide();
}

// Шапка при скролле
function initHeaderScroll() {
    const header = document.querySelector('.header');
    if (!header) return;
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
}

// Валидация форм
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
        
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                if (this.classList.contains('error')) {
                    validateField(this);
                }
            });
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    const type = field.type;
    let isValid = true;
    
    clearFieldError(field);
    
    if (field.required && !value) {
        showError(field, 'Это поле обязательно для заполнения');
        return false;
    }
    
    if (type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            showError(field, 'Введите корректный email');
            return false;
        }
    }
    
    if (type === 'password' && value) {
        if (value.length < 6) {
            showError(field, 'Пароль должен содержать минимум 6 символов');
            return false;
        }
    }
    
    if (field.name === 'password_confirm' && value) {
        const password = document.querySelector('input[name="password"]');
        if (password && value !== password.value) {
            showError(field, 'Пароли не совпадают');
            return false;
        }
    }
    
    if (type === 'date' && value) {
        const birthYear = new Date(value).getFullYear();
        if (birthYear < 1940) {
            showError(field, 'Год рождения не может быть раньше 1940');
            return false;
        }
    }
    
    return isValid;
}

function showError(field, message) {
    field.classList.add('error');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.innerHTML = '⚠️ ' + message;
    
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    field.classList.remove('error');
    const existingError = field.parentNode.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
}

// Загрузка аватара
function initAvatarUpload() {
    const avatarUpload = document.querySelector('.avatar-upload');
    if (!avatarUpload) return;
    
    const fileInput = avatarUpload.querySelector('input[type="file"]');
    const preview = avatarUpload.querySelector('.avatar-preview');
    
    if (!fileInput || !preview) return;
    
    avatarUpload.addEventListener('click', function(e) {
        if (e.target !== fileInput) {
            fileInput.click();
        }
    });
    
    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            if (!file.type.startsWith('image/')) {
                showToast('Выберите изображение', 'error');
                return;
            }
            
            if (file.size > 5 * 1024 * 1024) {
                showToast('Размер файла не должен превышать 5MB', 'error');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                avatarUpload.classList.add('has-image');
            };
            reader.readAsDataURL(file);
        }
    });
}

// Toast уведомления
function showToast(message, type) {
    if (type === undefined) type = 'success';
    
    let container = document.querySelector('.toast-container');
    
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = 'toast ' + type;
    toast.innerHTML = '<span>' + (type === 'success' ? '✅' : type === 'error' ? '❌' : '⚠️') + '</span><span>' + message + '</span>';
    
    container.appendChild(toast);
    
    setTimeout(function() {
        toast.style.animation = 'slideInRight 0.3s ease reverse';
        setTimeout(function() { toast.remove(); }, 300);
    }, 3000);
}

// Добавление в корзину
async function addToCart(productId, quantity) {
    if (quantity === undefined) quantity = 1;
    
    try {
        const response = await fetch('/api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', product_id: productId, quantity: quantity })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Товар добавлен в корзину', 'success');
            updateCartCount(result.cart_count);
        } else {
            showToast(result.message || 'Ошибка добавления', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Произошла ошибка', 'error');
    }
}

function updateCartCount(count) {
    const cartBadge = document.querySelector('.cart-count');
    if (cartBadge) {
        cartBadge.textContent = count;
        cartBadge.style.display = count > 0 ? 'block' : 'none';
    }
}

// Избранное
async function toggleFavorite(itemType, itemId) {
    try {
        const response = await fetch('/api/favorites.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'toggle', type: itemType, id: itemId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.added ? 'Добавлено в избранное' : 'Удалено из избранного', 'success');
        } else {
            showToast(result.message || 'Ошибка', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Произошла ошибка', 'error');
    }
}

// Глобальные функции
window.addToCart = addToCart;
window.toggleFavorite = toggleFavorite;
window.showToast = showToast;
