/**
 * 布局管理器
 * 侧边栏折叠、面包屑更新、活动切换、移动端适配
 */
;(function(global) {
    'use strict';

    var Layout = {
        sidebar: null,
        sidebarCollapsed: false,
        isMobile: false,

        /**
         * 初始化布局
         */
        init: function() {
            this.sidebar = document.getElementById('app-sidebar');
            this._detectMobile();
            this._bindToggle();
            this._bindOverlay();
            this._bindActivitySwitch();
            this._bindUserMenu();
            this._bindResize();

            // 恢复侧边栏状态
            var saved = localStorage.getItem('hd_sidebar_collapsed');
            if (saved === '1' && !this.isMobile) {
                this.collapseSidebar();
            }
        },

        /**
         * 检测移动端
         */
        _detectMobile: function() {
            this.isMobile = window.innerWidth <= 768;
        },

        /**
         * 绑定折叠按钮
         */
        _bindToggle: function() {
            var self = this;
            var btn = document.getElementById('sidebar-toggle');
            if (!btn) return;
            btn.addEventListener('click', function() {
                if (self.isMobile) {
                    self.toggleMobileSidebar();
                } else {
                    self.toggleCollapse();
                }
            });
        },

        /**
         * 绑定遮罩层点击关闭
         */
        _bindOverlay: function() {
            var self = this;
            var overlay = document.getElementById('sidebar-overlay');
            if (!overlay) return;
            overlay.addEventListener('click', function() {
                self.closeMobileSidebar();
            });
        },

        /**
         * 切换侧边栏折叠
         */
        toggleCollapse: function() {
            if (this.sidebarCollapsed) {
                this.expandSidebar();
            } else {
                this.collapseSidebar();
            }
        },

        collapseSidebar: function() {
            this.sidebarCollapsed = true;
            this.sidebar.classList.add('collapsed');
            localStorage.setItem('hd_sidebar_collapsed', '1');
        },

        expandSidebar: function() {
            this.sidebarCollapsed = false;
            this.sidebar.classList.remove('collapsed');
            localStorage.setItem('hd_sidebar_collapsed', '0');
        },

        /**
         * 移动端侧边栏
         */
        toggleMobileSidebar: function() {
            if (this.sidebar.classList.contains('mobile-open')) {
                this.closeMobileSidebar();
            } else {
                this.openMobileSidebar();
            }
        },

        openMobileSidebar: function() {
            this.sidebar.classList.add('mobile-open');
            document.getElementById('sidebar-overlay').classList.add('show');
        },

        closeMobileSidebar: function() {
            this.sidebar.classList.remove('mobile-open');
            document.getElementById('sidebar-overlay').classList.remove('show');
        },

        /**
         * 响应窗口大小变化
         */
        _bindResize: function() {
            var self = this;
            var resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    self._detectMobile();
                    if (!self.isMobile) {
                        self.closeMobileSidebar();
                        self.sidebar.classList.remove('mobile-open');
                    }
                }, 200);
            });
        },

        /**
         * 更新面包屑
         */
        setBreadcrumb: function(text) {
            var el = document.getElementById('breadcrumb-text');
            if (el) el.textContent = text;
        },

        /**
         * 活动切换下拉
         */
        _bindActivitySwitch: function() {
            var self = this;
            var switchBtn = document.getElementById('activity-switch');
            var dropdown = document.getElementById('activity-dropdown');
            var searchInput = document.getElementById('activity-search');
            if (!switchBtn || !dropdown) return;

            switchBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                var visible = dropdown.style.display !== 'none';
                self._closeAllDropdowns();
                if (!visible) {
                    dropdown.style.display = 'block';
                    // 定位
                    var rect = switchBtn.getBoundingClientRect();
                    dropdown.style.left = rect.left + 'px';
                    dropdown.style.top = (rect.bottom + 8) + 'px';
                    dropdown.style.right = 'auto';
                    dropdown.style.position = 'fixed';
                    if (searchInput) searchInput.focus();
                }
            });

            // 搜索过滤
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    var val = this.value.toLowerCase();
                    var items = document.querySelectorAll('#activity-list li');
                    items.forEach(function(li) {
                        var name = (li.textContent || '').toLowerCase();
                        li.style.display = name.indexOf(val) > -1 ? '' : 'none';
                    });
                });
            }

            // 点击其他区域关闭
            document.addEventListener('click', function() {
                self._closeAllDropdowns();
            });
        },

        /**
         * 渲染活动列表
         */
        renderActivities: function(activities, currentId) {
            var list = document.getElementById('activity-list');
            if (!list) return;
            var html = '';
            // 新建活动按钮
            html += '<li class="add-activity-btn" id="add-activity-btn"><i class="fas fa-plus-circle"></i> 新建活动</li>';
            for (var i = 0; i < activities.length; i++) {
                var act = activities[i];
                var isActive = String(act.id) === String(currentId);
                var statusClass = act.status === 1 ? 'on' : 'off';
                html += '<li data-id="' + act.id + '" class="' + (isActive ? 'active' : '') + '">';
                html += '<span class="activity-status ' + statusClass + '"></span>';
                html += '<span>' + this._escHtml(act.name || act.title || '未命名活动') + '</span>';
                html += '</li>';
            }
            list.innerHTML = html;

            // 绑定新建活动按钮
            var addBtn = document.getElementById('add-activity-btn');
            if (addBtn) {
                addBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (global.App && global.App.showCreateActivity) {
                        global.App.showCreateActivity();
                    }
                });
            }

            // 绑定切换事件
            var self = this;
            list.querySelectorAll('li[data-id]').forEach(function(li) {
                li.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var id = this.getAttribute('data-id');
                    if (id && global.App) {
                        global.App.switchActivity(id);
                        self._closeAllDropdowns();
                    }
                });
            });
        },

        /**
         * 更新当前活动名称显示
         */
        setCurrentActivity: function(name) {
            var el = document.getElementById('current-activity-name');
            if (el) el.textContent = name || '选择活动';
        },

        /**
         * 用户菜单下拉
         */
        _bindUserMenu: function() {
            var self = this;
            var userBtn = document.getElementById('user-menu');
            var dropdown = document.getElementById('user-dropdown');
            if (!userBtn || !dropdown) return;

            userBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                var visible = dropdown.style.display !== 'none';
                self._closeAllDropdowns();
                if (!visible) {
                    dropdown.style.display = 'block';
                }
            });

            // 退出登录
            document.getElementById('btn-logout').addEventListener('click', function() {
                layui.layer.confirm('确定要退出登录吗？', { icon: 3, title: '提示' }, function(idx) {
                    layui.layer.close(idx);
                    if (global.App) global.App.logout();
                });
            });

            // 旧版后台
            var oldAdminBtn = document.getElementById('btn-old-admin');
            if (oldAdminBtn) {
                oldAdminBtn.addEventListener('click', function() {
                    window.open('/huodong/myadmin/index.php', '_blank');
                });
            }
        },

        /**
         * 设置用户名
         */
        setUserName: function(name) {
            var el = document.getElementById('user-name');
            if (el) el.textContent = name || '用户';
        },

        /**
         * 关闭所有下拉菜单
         */
        _closeAllDropdowns: function() {
            document.getElementById('activity-dropdown').style.display = 'none';
            document.getElementById('user-dropdown').style.display = 'none';
        },

        /**
         * HTML 转义
         */
        _escHtml: function(str) {
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },

        /**
         * 显示应用主体，隐藏登录
         */
        showApp: function() {
            document.getElementById('login-container').style.display = 'none';
            document.getElementById('app-container').style.display = 'flex';
        },

        /**
         * 显示登录，隐藏应用主体
         */
        showLogin: function() {
            document.getElementById('login-container').style.display = 'flex';
            document.getElementById('app-container').style.display = 'none';
        },

        /**
         * 在内容区加载 HTML
         */
        setContent: function(html) {
            document.getElementById('content-area').innerHTML = html;
        },

        /**
         * 获取内容区容器
         */
        getContentEl: function() {
            return document.getElementById('content-area');
        }
    };

    global.Layout = Layout;
})(window);
