/**
 * 抽屉式菜单组件
 * 手风琴模式、折叠态 hover 弹出、移动端抽屉覆盖
 */
;(function(global) {
    'use strict';

    /**
     * 菜单配置数据
     * 8 个功能域，归并原有 20+ 菜单
     */
    var MENU_DATA = [
        {
            id: 'dashboard',
            text: '仪表盘',
            icon: 'fa-tachometer-alt',
            route: '/dashboard'
        },
        {
            id: 'sign',
            text: '签到管理',
            icon: 'fa-user-check',
            children: [
                { id: 'sign-config',  text: '签到设置', route: '/sign/config' },
                { id: 'sign-list',    text: '签到名单', route: '/sign/list' },
                { id: 'sign-mobile',  text: '手机签到页', route: '/sign/mobile' },
                { id: 'sign-3d',      text: '3D签到',   route: '/sign/3d' }
            ]
        },
        {
            id: 'lottery',
            text: '互动抽奖',
            icon: 'fa-gift',
            children: [
                { id: 'lottery-rounds',     text: '抽奖轮次', route: '/lottery/rounds' },
                { id: 'lottery-prizes',     text: '奖品设置', route: '/lottery/prizes' },
                { id: 'lottery-winners',    text: '中奖名单', route: '/lottery/winners' },
                { id: 'lottery-designated', text: '内定名单', route: '/lottery/designated' },
                { id: 'lottery-themes',     text: '抽奖主题', route: '/lottery/themes' },
                { id: 'lottery-choujiang',  text: '手机端抽奖', route: '/lottery/choujiang' },
                { id: 'lottery-import',     text: '导入抽奖', route: '/lottery/import' }
            ]
        },
        {
            id: 'game',
            text: '游戏互动',
            icon: 'fa-gamepad',
            children: [
                { id: 'game-shake',        text: '摇一摇设置', route: '/game/shake' },
                { id: 'game-shake-themes', text: '摇一摇模板', route: '/game/shake-themes' },
                { id: 'game-shuqian',      text: '数钱游戏',   route: '/game/shuqian' },
                { id: 'game-pashu',        text: '猴子爬树',   route: '/game/pashu' },
                { id: 'game-ranking',      text: '游戏排行',   route: '/game/ranking' }
            ]
        },
        {
            id: 'wall',
            text: '消息互动',
            icon: 'fa-comments',
            children: [
                { id: 'wall-config',   text: '上墙设置', route: '/wall/config' },
                { id: 'wall-danmu',    text: '弹幕设置', route: '/wall/danmu' },
                { id: 'wall-messages', text: '消息列表', route: '/wall/messages' },
                { id: 'wall-notice',   text: '发布公告', route: '/wall/notice' }
            ]
        },
        {
            id: 'content',
            text: '投票相册',
            icon: 'fa-poll',
            children: [
                { id: 'content-vote',  text: '投票管理', route: '/content/vote' },
                { id: 'content-album', text: '相册管理', route: '/content/album' }
            ]
        },
        {
            id: 'redpacket',
            text: '红包管理',
            icon: 'fa-envelope',
            children: [
                { id: 'redpacket-config',  text: '红包设置', route: '/redpacket/config' },
                { id: 'redpacket-rounds',  text: '红包轮次', route: '/redpacket/rounds' },
                { id: 'redpacket-records', text: '中奖记录', route: '/redpacket/records' }
            ]
        },
        {
            id: 'system',
            text: '系统设置',
            icon: 'fa-cog',
            children: [
                { id: 'system-theme',      text: '主题展示', route: '/system/theme' },
                { id: 'system-background', text: '背景图',   route: '/system/background' },
                { id: 'system-music',      text: '背景音乐', route: '/system/music' },
                { id: 'system-switch',     text: '功能开关', route: '/system/switch' },
                { id: 'system-basic',      text: '基本设置', route: '/system/basic' },
                { id: 'system-security',   text: '内容安全', route: '/system/security' }
            ]
        }
    ];

    function SideMenu(containerEl) {
        this.container = typeof containerEl === 'string' ? document.getElementById(containerEl) : containerEl;
        this.data = MENU_DATA;
        this.activeRoute = '';
        this.onNavigate = null; // callback(route, menuItem)
    }

    SideMenu.prototype = {
        /**
         * 渲染菜单 DOM
         */
        render: function() {
            var html = '';
            for (var i = 0; i < this.data.length; i++) {
                html += this._renderItem(this.data[i]);
            }
            this.container.innerHTML = html;
            this._bindEvents();
        },

        _renderItem: function(item) {
            var hasChildren = item.children && item.children.length > 0;
            var isOpen = this._isGroupOpen(item);
            var isActive = !hasChildren && item.route === this.activeRoute;

            var html = '<div class="menu-item' + (isOpen ? ' open' : '') + '" data-id="' + item.id + '">';
            html += '<div class="menu-link' + (isActive ? ' active' : '') + '" data-route="' + (item.route || '') + '">';
            html += '<i class="menu-icon fas ' + item.icon + '"></i>';
            html += '<span class="menu-text">' + item.text + '</span>';
            if (hasChildren) {
                html += '<i class="menu-arrow fas fa-chevron-right"></i>';
            }
            html += '</div>';

            if (hasChildren) {
                html += '<div class="submenu">';
                for (var j = 0; j < item.children.length; j++) {
                    var child = item.children[j];
                    var childActive = child.route === this.activeRoute;
                    html += '<div class="submenu-item" data-id="' + child.id + '">';
                    html += '<div class="menu-link' + (childActive ? ' active' : '') + '" data-route="' + child.route + '">';
                    html += '<span class="menu-text">' + child.text + '</span>';
                    html += '</div></div>';
                }
                html += '</div>';

                // 折叠态弹出菜单
                html += '<div class="submenu-popup">';
                for (var k = 0; k < item.children.length; k++) {
                    var c = item.children[k];
                    var cActive = c.route === this.activeRoute;
                    html += '<div class="submenu-item" data-id="' + c.id + '">';
                    html += '<div class="menu-link' + (cActive ? ' active' : '') + '" data-route="' + c.route + '">';
                    html += '<span class="menu-text">' + c.text + '</span>';
                    html += '</div></div>';
                }
                html += '</div>';
            }

            html += '</div>';
            return html;
        },

        _isGroupOpen: function(item) {
            if (!item.children) return false;
            for (var i = 0; i < item.children.length; i++) {
                if (item.children[i].route === this.activeRoute) return true;
            }
            return false;
        },

        _bindEvents: function() {
            var self = this;
            // 一级菜单点击
            this.container.querySelectorAll('.menu-item > .menu-link').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    var menuItem = this.closest('.menu-item');
                    var route = this.getAttribute('data-route');
                    var submenu = menuItem.querySelector('.submenu');

                    if (submenu) {
                        // 手风琴模式：收起其他已展开项
                        var siblings = menuItem.parentElement.querySelectorAll('.menu-item.open');
                        siblings.forEach(function(sib) {
                            if (sib !== menuItem) sib.classList.remove('open');
                        });
                        menuItem.classList.toggle('open');
                    } else if (route) {
                        self._setActive(route);
                        if (self.onNavigate) self.onNavigate(route);
                    }
                });
            });

            // 二级菜单点击（包括抽屉内和弹出式）
            this.container.querySelectorAll('.submenu-item .menu-link, .submenu-popup .submenu-item .menu-link').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var route = this.getAttribute('data-route');
                    if (route) {
                        self._setActive(route);
                        if (self.onNavigate) self.onNavigate(route);
                    }
                });
            });
        },

        /**
         * 设置当前选中路由
         */
        setActive: function(route) {
            this.activeRoute = route;
            this._setActive(route);
        },

        _setActive: function(route) {
            this.activeRoute = route;
            // 清除所有 active
            this.container.querySelectorAll('.menu-link.active').forEach(function(el) {
                el.classList.remove('active');
            });
            // 设置新的 active
            this.container.querySelectorAll('.menu-link[data-route="' + route + '"]').forEach(function(el) {
                el.classList.add('active');
            });
            // 确保父级展开
            var activeItem = this.container.querySelector('.submenu-item .menu-link[data-route="' + route + '"]');
            if (activeItem) {
                var parent = activeItem.closest('.menu-item');
                if (parent && !parent.classList.contains('open')) {
                    // 先收起其他
                    this.container.querySelectorAll('.menu-item.open').forEach(function(el) {
                        el.classList.remove('open');
                    });
                    parent.classList.add('open');
                }
            }
        },

        /**
         * 根据路由获取面包屑文本
         */
        getBreadcrumb: function(route) {
            for (var i = 0; i < this.data.length; i++) {
                var item = this.data[i];
                if (item.route === route) {
                    return item.text;
                }
                if (item.children) {
                    for (var j = 0; j < item.children.length; j++) {
                        if (item.children[j].route === route) {
                            return item.text + ' > ' + item.children[j].text;
                        }
                    }
                }
            }
            return '首页';
        }
    };

    global.SideMenu = SideMenu;
    global.MENU_DATA = MENU_DATA;
})(window);
