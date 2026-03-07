/**
 * theme.js — 主题切换逻辑（单按钮切换）
 */
(function(){
    var STORAGE_KEY = 'theme_mode';

    function getTheme(){
        return localStorage.getItem(STORAGE_KEY) || 'light';
    }

    function applyTheme(mode){
        document.documentElement.setAttribute('data-theme', mode);
        localStorage.setItem(STORAGE_KEY, mode);
        // 更新按钮图标和文字
        var icon = document.getElementById('themeIcon');
        var text = document.getElementById('themeText');
        if(icon) icon.textContent = (mode === 'dark') ? '\uD83C\uDF19' : '\u2600';
        if(text) text.textContent = (mode === 'dark') ? '\u6DF1\u8272' : '\u6D45\u8272';
    }

    function toggleTheme(){
        var current = getTheme();
        applyTheme(current === 'light' ? 'dark' : 'light');
    }

    // 初始化
    applyTheme(getTheme());

    // 绑定事件（页面加载完成后）
    document.addEventListener('DOMContentLoaded', function(){
        applyTheme(getTheme());
        var btn = document.getElementById('themeToggleBtn');
        if(btn){
            btn.addEventListener('click', toggleTheme);
        }
    });

    window.ThemeManager = { apply: applyTheme, get: getTheme, toggle: toggleTheme };
})();
