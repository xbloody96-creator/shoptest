/**
 * Переключение темы и режима доступности
 */

// Инициализация темы при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    initTheme();
    initAccessibility();
});

// === Управление темой (светлая/темная) ===
function initTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme);
    
    // Обработчик переключателя темы
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
        updateThemeIcon(themeToggle, savedTheme);
    }
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    setTheme(newTheme);
    localStorage.setItem('theme', newTheme);
    
    // Обновляем иконку
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        updateThemeIcon(themeToggle, newTheme);
    }
}

function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    
    // Добавляем класс для плавных переходов
    document.body.classList.add('theme-transition');
    
    // Сохраняем в localStorage
    localStorage.setItem('theme', theme);
}

function updateThemeIcon(button, theme) {
    if (!button) return;
    
    if (theme === 'dark') {
        button.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="5"/>
                <line x1="12" y1="1" x2="12" y2="3"/>
                <line x1="12" y1="21" x2="12" y2="23"/>
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                <line x1="1" y1="12" x2="3" y2="12"/>
                <line x1="21" y1="12" x2="23" y2="12"/>
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
            </svg>
        `;
        button.setAttribute('aria-label', 'Включить светлую тему');
    } else {
        button.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
            </svg>
        `;
        button.setAttribute('aria-label', 'Включить темную тему');
    }
}

// === Управление режимом доступности ===
function initAccessibility() {
    const savedAccessibility = localStorage.getItem('accessibility') || '{}';
    const settings = JSON.parse(savedAccessibility);
    
    // Применяем сохраненные настройки
    if (settings.enabled) {
        enableAccessibilityMode();
    }
    if (settings.highContrast) {
        document.body.classList.add('high-contrast');
    }
    if (settings.fontSize) {
        setFontSize(settings.fontSize);
    }
    
    // Создаем кнопку переключения если её нет
    createAccessibilityButton();
    
    // Обработчики
    setupAccessibilityHandlers();
}

function createAccessibilityButton() {
    if (document.querySelector('.accessibility-toggle')) return;
    
    const button = document.createElement('button');
    button.className = 'accessibility-toggle';
    button.setAttribute('aria-label', 'Настройки доступности');
    button.innerHTML = `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <circle cx="12" cy="12" r="4"/>
            <line x1="21.17" y1="8" x2="12" y2="8"/>
            <line x1="3.95" y1="6.06" x2="8.54" y2="14"/>
            <line x1="10.88" y1="21.94" x2="15.46" y2="14"/>
        </svg>
    `;
    
    button.addEventListener('click', toggleAccessibilityPanel);
    document.body.appendChild(button);
    
    // Создаем панель
    createAccessibilityPanel();
}

function createAccessibilityPanel() {
    if (document.querySelector('.accessibility-panel')) return;
    
    const panel = document.createElement('div');
    panel.className = 'accessibility-panel';
    panel.innerHTML = `
        <h4>Настройки доступности</h4>
        
        <div class="accessibility-option">
            <label>Режим слабовидящих</label>
            <label class="accessibility-switch">
                <input type="checkbox" id="accessibilityEnabled">
                <span class="accessibility-slider"></span>
            </label>
        </div>
        
        <div class="accessibility-option">
            <label>Высокая контрастность</label>
            <label class="accessibility-switch">
                <input type="checkbox" id="highContrastEnabled">
                <span class="accessibility-slider"></span>
            </label>
        </div>
        
        <div class="accessibility-option">
            <label>Размер шрифта</label>
        </div>
        <div class="font-size-controls">
            <button class="font-size-btn" data-size="small">A-</button>
            <button class="font-size-btn active" data-size="medium">A</button>
            <button class="font-size-btn" data-size="large">A+</button>
            <button class="font-size-btn" data-size="xlarge">A++</button>
        </div>
    `;
    
    document.body.appendChild(panel);
}

function toggleAccessibilityPanel() {
    const panel = document.querySelector('.accessibility-panel');
    if (panel) {
        panel.classList.toggle('active');
    }
}

function setupAccessibilityHandlers() {
    // Режим слабовидящих
    const accessibilityCheckbox = document.getElementById('accessibilityEnabled');
    if (accessibilityCheckbox) {
        const isEnabled = localStorage.getItem('accessibility') && 
                         JSON.parse(localStorage.getItem('accessibility')).enabled;
        accessibilityCheckbox.checked = isEnabled || false;
        
        accessibilityCheckbox.addEventListener('change', function() {
            if (this.checked) {
                enableAccessibilityMode();
            } else {
                disableAccessibilityMode();
            }
            saveAccessibilitySettings();
        });
    }
    
    // Высокая контрастность
    const contrastCheckbox = document.getElementById('highContrastEnabled');
    if (contrastCheckbox) {
        const isHighContrast = localStorage.getItem('accessibility') && 
                              JSON.parse(localStorage.getItem('accessibility')).highContrast;
        contrastCheckbox.checked = isHighContrast || false;
        
        contrastCheckbox.addEventListener('change', function() {
            document.body.classList.toggle('high-contrast', this.checked);
            saveAccessibilitySettings();
        });
    }
    
    // Размер шрифта
    document.querySelectorAll('.font-size-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.font-size-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            setFontSize(this.dataset.size);
            saveAccessibilitySettings();
        });
    });
    
    // Закрытие панели при клике вне
    document.addEventListener('click', function(e) {
        const panel = document.querySelector('.accessibility-panel');
        const toggle = document.querySelector('.accessibility-toggle');
        
        if (panel && !panel.contains(e.target) && !toggle.contains(e.target)) {
            panel.classList.remove('active');
        }
    });
}

function enableAccessibilityMode() {
    document.body.classList.add('accessibility-mode');
    saveAccessibilitySettings();
}

function disableAccessibilityMode() {
    document.body.classList.remove('accessibility-mode');
    document.body.classList.remove('high-contrast');
    saveAccessibilitySettings();
}

function setFontSize(size) {
    const fontSizes = {
        small: '14px',
        medium: '18px',
        large: '22px',
        xlarge: '26px'
    };
    
    document.documentElement.style.setProperty('--base-font-size', fontSizes[size] || '18px');
    
    if (document.body.classList.contains('accessibility-mode')) {
        document.body.style.fontSize = fontSizes[size] || '18px';
    }
}

function saveAccessibilitySettings() {
    const settings = {
        enabled: document.body.classList.contains('accessibility-mode'),
        highContrast: document.body.classList.contains('high-contrast'),
        fontSize: document.querySelector('.font-size-btn.active')?.dataset.size || 'medium'
    };
    
    localStorage.setItem('accessibility', JSON.stringify(settings));
}

// === Экспорт функций для использования в других скриптах ===
window.themeUtils = {
    toggleTheme,
    setTheme
};

window.accessibilityUtils = {
    enableAccessibilityMode,
    disableAccessibilityMode,
    setFontSize,
    toggleAccessibilityPanel
};
