/**
 * sidebar.js — 侧边栏折叠/展开/抽屉交互
 */
(function(){
    var sidebar, overlay, body;
    var COLLAPSED_KEY = 'sidebar_collapsed';

    function init(){
        sidebar = document.querySelector('.sidebar');
        overlay = document.querySelector('.sidebar-overlay');
        body = document.body;
        if(!sidebar) return;

        // 桌面端: 恢复折叠状态
        if(window.innerWidth >= 1440){
            if(localStorage.getItem(COLLAPSED_KEY) === '1'){
                sidebar.classList.add('collapsed');
                body.classList.add('sidebar-collapsed');
            }
        }

        // 初始化浮动子菜单
        initFloatSubMenus();

        bindEvents();
    }

    function bindEvents(){
        // 折叠按钮
        var collapseBtn = sidebar.querySelector('.collapse-btn');
        if(collapseBtn){
            collapseBtn.addEventListener('click', function(e){
                e.stopPropagation();
                toggleCollapse();
            });
        }

        // Logo点击：折叠态下展开
        var logoArea = sidebar.querySelector('.sidebar-logo');
        if(logoArea){
            logoArea.addEventListener('click', function(){
                if(sidebar.classList.contains('collapsed')){
                    toggleCollapse();
                }
            });
        }

        // 汉堡菜单
        document.querySelectorAll('.header-hamburger').forEach(function(btn){
            btn.addEventListener('click', openDrawer);
        });

        // 遮罩点击关闭
        if(overlay){
            overlay.addEventListener('click', closeDrawer);
        }

        // 抽屉关闭按钮
        var closeBtn = sidebar.querySelector('.drawer-close-btn');
        if(closeBtn){
            closeBtn.addEventListener('click', closeDrawer);
        }

        // 二级菜单展开/收起
        sidebar.querySelectorAll('.nav-toggle').forEach(function(toggle){
            toggle.addEventListener('click', function(e){
                e.preventDefault();
                var sub = this.parentElement.querySelector('.nav-sub');
                var arrow = this.querySelector('.nav-arrow');
                if(sub){
                    sub.classList.toggle('open');
                    if(arrow) arrow.classList.toggle('open');
                }
            });
        });

        // 触摸滑动手势（打开/关闭抽屉）
        var touchStartX = 0, touchDiff = 0;
        document.addEventListener('touchstart', function(e){
            touchStartX = e.touches[0].clientX;
        }, {passive: true});
        document.addEventListener('touchend', function(e){
            touchDiff = e.changedTouches[0].clientX - touchStartX;
            if(window.innerWidth < 1024){
                if(touchDiff > 60 && touchStartX < 30 && !sidebar.classList.contains('drawer-open')){
                    openDrawer();
                } else if(touchDiff < -60 && sidebar.classList.contains('drawer-open')){
                    closeDrawer();
                }
            }
        }, {passive: true});

        // 窗口resize时自动处理
        window.addEventListener('resize', function(){
            if(window.innerWidth >= 1024){
                closeDrawer();
            }
        });
    }

    function toggleCollapse(){
        sidebar.classList.toggle('collapsed');
        body.classList.toggle('sidebar-collapsed');
        localStorage.setItem(COLLAPSED_KEY, sidebar.classList.contains('collapsed') ? '1' : '0');
    }

    function openDrawer(){
        sidebar.classList.add('drawer-open');
        if(overlay) overlay.classList.add('show');
        body.style.overflow = 'hidden';
    }

    function closeDrawer(){
        sidebar.classList.remove('drawer-open');
        if(overlay) overlay.classList.remove('show');
        body.style.overflow = '';
    }

    /**
     * 初始化浮动子菜单（用于折叠态）
     * 动态复制 .nav-sub 内容到 .nav-float-sub，避免HTML重复
     */
    function initFloatSubMenus(){
        var hasSubItems = sidebar.querySelectorAll('.nav-item.has-sub');
        hasSubItems.forEach(function(item){
            var navSub = item.querySelector('.nav-sub');
            if(!navSub) return;

            // 检查是否已存在 nav-float-sub
            var floatSub = item.querySelector('.nav-float-sub');
            if(!floatSub){
                // 创建浮动子菜单容器
                floatSub = document.createElement('div');
                floatSub.className = 'nav-float-sub';
                item.appendChild(floatSub);
            }

            // 复制子菜单链接
            floatSub.innerHTML = navSub.innerHTML;
        });
    }

    document.addEventListener('DOMContentLoaded', init);

    window.SidebarManager = { open: openDrawer, close: closeDrawer, toggle: toggleCollapse };
})();
