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
                { id: 'sign-mobile',  text: '手机签到页', route: '/sign/mobile' }
            ]
        },
        {
            id: 'sign-features',
            text: '签到功能',
            icon: 'fa-magic',
            children: [
                { id: 'sign-wall',      text: '签到墙',   route: '/sign/wall' },
                { id: 'sign-3d',        text: '3D签到',   route: '/sign/3d' },
                { id: 'sign-avatar',    text: '头像墙',   route: '/sign/avatar' },
                { id: 'sign-bubble',    text: '泡泡签到', route: '/sign/bubble' }
            ]
        },
        {
            id: 'lottery',
            text: '互动抽奖',
            icon: 'fa-gift',
            children: [
                {
                    id: 'lottery-screen',
                    text: '大屏抽奖',
                    children: [
                        { id: 'lottery-screen-settings', text: '抽奖设置', route: '/lottery/screen/settings' },
                        { id: 'lottery-screen-prizes', text: '奖品设置', route: '/lottery/screen/prizes' },
                        { id: 'lottery-screen-winners', text: '中奖名单', route: '/lottery/screen/winners' },
                        { id: 'lottery-screen-designated', text: '内定名单', route: '/lottery/screen/designated' }
                    ]
                },
                {
                    id: 'lottery-import',
                    text: '导入抽奖',
                    children: [
                        { id: 'lottery-import-settings', text: '抽奖设置', route: '/lottery/import/settings' },
                        { id: 'lottery-import-list', text: '导入名单', route: '/lottery/import/list' },
                        { id: 'lottery-import-prizes', text: '奖品设置', route: '/lottery/import/prizes' },
                        { id: 'lottery-import-winners', text: '中奖名单', route: '/lottery/import/winners' },
                        { id: 'lottery-import-designated', text: '内定名单', route: '/lottery/import/designated' }
                    ]
                },
                {
                    id: 'lottery-photo',
                    text: '照片抽奖',
                    children: [
                        { id: 'lottery-photo-settings', text: '抽奖设置', route: '/lottery/photo/settings' },
                        { id: 'lottery-photo-import', text: '导入照片', route: '/lottery/photo/import' },
                        { id: 'lottery-photo-prizes', text: '奖品设置', route: '/lottery/photo/prizes' },
                        { id: 'lottery-photo-winners', text: '中奖名单', route: '/lottery/photo/winners' },
                        { id: 'lottery-photo-designated', text: '内定名单', route: '/lottery/photo/designated' }
                    ]
                },
                {
                    id: 'lottery-barrage',
                    text: '弹幕抽奖',
                    children: [
                        { id: 'lottery-barrage-settings', text: '抽奖设置', route: '/lottery/barrage/settings' },
                        { id: 'lottery-barrage-prizes', text: '奖品设置', route: '/lottery/barrage/prizes' },
                        { id: 'lottery-barrage-winners', text: '中奖名单', route: '/lottery/barrage/winners' }
                    ]
                },
                {
                    id: 'lottery-box',
                    text: '抽奖箱',
                    children: [
                        { id: 'lottery-box-settings', text: '抽奖设置', route: '/lottery/box/settings' },
                        { id: 'lottery-box-prizes', text: '奖品设置', route: '/lottery/box/prizes' },
                        { id: 'lottery-box-winners', text: '中奖名单', route: '/lottery/box/winners' },
                        { id: 'lottery-box-designated', text: '内定名单', route: '/lottery/box/designated' }
                    ]
                },
                {
                    id: 'lottery-egg',
                    text: '砸金蛋',
                    children: [
                        { id: 'lottery-egg-settings', text: '抽奖设置', route: '/lottery/egg/settings' },
                        { id: 'lottery-egg-prizes', text: '奖品设置', route: '/lottery/egg/prizes' },
                        { id: 'lottery-egg-winners', text: '中奖名单', route: '/lottery/egg/winners' },
                        { id: 'lottery-egg-designated', text: '内定名单', route: '/lottery/egg/designated' }
                    ]
                },

                {
                    id: 'lottery-choujiang',
                    text: '手机端抽奖',
                    children: [
                        { id: 'lottery-choujiang-settings', text: '抽奖设置', route: '/lottery/choujiang/settings' },
                        { id: 'lottery-choujiang-prizes', text: '奖品设置', route: '/lottery/choujiang/prizes' },
                        { id: 'lottery-choujiang-winners', text: '中奖名单', route: '/lottery/choujiang/winners' },
                        { id: 'lottery-choujiang-designated', text: '内定名单', route: '/lottery/choujiang/designated' }
                    ]
                },
                {
                    id: 'lottery-lucky-phone',
                    text: '幸运手机号',
                    children: [
                        { id: 'lottery-lucky-phone-prizes', text: '奖品设置', route: '/lottery/lucky-phone/prizes' },
                        { id: 'lottery-lucky-phone-winners', text: '中奖名单', route: '/lottery/lucky-phone/winners' },
                        { id: 'lottery-lucky-phone-designated', text: '内定名单', route: '/lottery/lucky-phone/designated' }
                    ]
                },
                {
                    id: 'lottery-lucky-number',
                    text: '幸运号码',
                    children: [
                        { id: 'lottery-lucky-number-settings', text: '幸运号码设置', route: '/lottery/lucky-number/settings' },
                        { id: 'lottery-lucky-number-winners', text: '中奖号码', route: '/lottery/lucky-number/winners' },
                        { id: 'lottery-lucky-number-designated', text: '内定号码', route: '/lottery/lucky-number/designated' }
                    ]
                }
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
                { id: 'system-display',    text: '大屏显示设置', route: '/system/display' },
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
            if (item.icon) {
                html += '<i class="menu-icon fas ' + item.icon + '"></i>';
            }
            html += '<span class="menu-text">' + item.text + '</span>';
            if (hasChildren) {
                html += '<i class="menu-arrow fas fa-chevron-right"></i>';
            }
            html += '</div>';

            if (hasChildren) {
                html += '<div class="submenu">';
                for (var j = 0; j < item.children.length; j++) {
                    var child = item.children[j];
                    var childHasChildren = child.children && child.children.length > 0;
                    var childActive = child.route === this.activeRoute;
                    var childIsOpen = this._isGroupOpen(child);

                    if (childHasChildren) {
                        // 三级菜单
                        html += '<div class="submenu-item has-submenu' + (childIsOpen ? ' open' : '') + '" data-id="' + child.id + '">';
                        html += '<div class="menu-link' + (childActive ? ' active' : '') + '" data-route="' + (child.route || '') + '">';
                        html += '<span class="menu-text">' + child.text + '</span>';
                        html += '<i class="menu-arrow fas fa-chevron-right"></i>';
                        html += '</div>';
                        html += '<div class="submenu submenu-level-3">';
                        for (var k = 0; k < child.children.length; k++) {
                            var grandchild = child.children[k];
                            var grandchildActive = grandchild.route === this.activeRoute;
                            html += '<div class="submenu-item" data-id="' + grandchild.id + '">';
                            html += '<div class="menu-link' + (grandchildActive ? ' active' : '') + '" data-route="' + grandchild.route + '">';
                            html += '<span class="menu-text">' + grandchild.text + '</span>';
                            html += '</div></div>';
                        }
                        html += '</div>';
                        html += '</div>';
                    } else {
                        // 二级菜单
                        html += '<div class="submenu-item" data-id="' + child.id + '">';
                        html += '<div class="menu-link' + (childActive ? ' active' : '') + '" data-route="' + child.route + '">';
                        html += '<span class="menu-text">' + child.text + '</span>';
                        html += '</div></div>';
                    }
                }
                html += '</div>';

                // 折叠态弹出菜单
                html += '<div class="submenu-popup">';
                for (var k = 0; k < item.children.length; k++) {
                    var c = item.children[k];
                    var cActive = c.route === this.activeRoute;
                    html += '<div class="submenu-item" data-id="' + c.id + '">';
                    html += '<div class="menu-link' + (cActive ? ' active' : '') + '" data-route="' + (c.route || '') + '">';
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
                var child = item.children[i];
                if (child.route === this.activeRoute) return true;
                // 检查三级菜单
                if (child.children) {
                    for (var j = 0; j < child.children.length; j++) {
                        if (child.children[j].route === this.activeRoute) return true;
                    }
                }
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
            this.container.querySelectorAll('.submenu-item > .menu-link, .submenu-popup .submenu-item .menu-link').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var menuItem = this.closest('.submenu-item');
                    var route = this.getAttribute('data-route');
                    var submenu = menuItem.querySelector('.submenu');

                    if (submenu) {
                        // 切换三级菜单展开状态
                        menuItem.classList.toggle('open');
                    } else if (route) {
                        self._setActive(route);
                        if (self.onNavigate) self.onNavigate(route);
                    }
                });
            });

            // 三级菜单点击
            this.container.querySelectorAll('.submenu-level-3 .submenu-item .menu-link').forEach(function(link) {
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
                // 检查是否是三级菜单
                var grandparent = activeItem.closest('.submenu-item.has-submenu');
                if (grandparent && !grandparent.classList.contains('open')) {
                    grandparent.classList.add('open');
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
                        var child = item.children[j];
                        if (child.route === route) {
                            return item.text + ' > ' + child.text;
                        }
                        // 检查三级菜单
                        if (child.children) {
                            for (var k = 0; k < child.children.length; k++) {
                                if (child.children[k].route === route) {
                                    return item.text + ' > ' + child.text + ' > ' + child.children[k].text;
                                }
                            }
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
