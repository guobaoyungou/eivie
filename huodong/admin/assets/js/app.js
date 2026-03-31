/**
 * 应用主入口
 * 初始化路由、菜单、布局，管理全局状态
 * 支持扫码登录 + 密码登录 + 手机绑定流程
 */
;(function(global) {
    'use strict';

    var ACTIVITY_KEY = 'hd_current_activity';

    var App = {
        router: null,
        menu: null,
        currentActivityId: null,
        currentActivityName: '',
        userInfo: null,

        // 扫码登录状态
        _qrSceneId: null,
        _qrTimer: null,
        _qrExpireTimer: null,
        _bindToken: null,
        _bindNickname: '',
        _bindAvatar: '',
        _smsCountdown: 0,
        _smsTimer: null,

        /**
         * 启动应用
         */
        init: function() {
            var self = this;

            // 检查登录状态
            if (!Api.getToken()) {
                this.showLogin();
                return;
            }

            // 验证 token 有效性
            Api.getProfile().then(function(res) {
                self.userInfo = res.data || {};
                self._bootApp();
            }).catch(function() {
                Api.clearToken();
                self.showLogin();
            });
        },

        /**
         * 启动主应用
         */
        _bootApp: function() {
            var self = this;

            // 停止扫码轮询
            this._stopQrPolling();

            // 显示主界面
            Layout.showApp();
            Layout.init();
            Layout.setUserName(this.userInfo.business_name || this.userInfo.name || this.userInfo.phone || '用户');

            // 初始化菜单
            this.menu = new SideMenu('sidebar-menu');
            this.menu.onNavigate = function(route) {
                self.router.push(route);
                // 移动端关闭侧边栏
                if (Layout.isMobile) Layout.closeMobileSidebar();
            };
            this.menu.render();

            // 加载活动列表
            this._loadActivities();

            // 注册路由
            this._setupRouter();
        },

        /**
         * 注册所有路由
         */
        _setupRouter: function() {
            var self = this;
            this.router = new HashRouter();

            // 路由前置守卫
            this.router.guard(function(to, from, next) {
                // 更新菜单选中状态
                self.menu.setActive(to.path);
                // 更新面包屑
                Layout.setBreadcrumb(self.menu.getBreadcrumb(to.path));
                next();
            });

            // ---- 仪表盘 ----
            this.router.on('/dashboard', function() {
                DashboardPage.render();
            });

            // ---- 签到管理 ----
            this.router.on('/sign/config', function() { SignPage.renderConfig(); });
            this.router.on('/sign/list', function() { SignPage.renderList(); });
            this.router.on('/sign/mobile', function() { SignPage.renderMobile(); });
            this.router.on('/sign/3d', function() { SignPage.render3d(); });

            // ---- 互动抽奖 ----
            this.router.on('/lottery/rounds', function() { LotteryPage.renderRounds(); });
            this.router.on('/lottery/prizes', function() { LotteryPage.renderPrizes(); });
            this.router.on('/lottery/winners', function() { LotteryPage.renderWinners(); });
            this.router.on('/lottery/designated', function() { LotteryPage.renderDesignated(); });
            this.router.on('/lottery/themes', function() { LotteryPage.renderThemes(); });
            this.router.on('/lottery/choujiang', function() { LotteryPage.renderChoujiang(); });
            this.router.on('/lottery/import', function() { LotteryPage.renderImport(); });

            // ---- 游戏互动 ----
            this.router.on('/game/shake', function() { GamePage.renderShake(); });
            this.router.on('/game/shake-themes', function() { GamePage.renderShakeThemes(); });
            this.router.on('/game/shuqian', function() { GamePage.renderShuqian(); });
            this.router.on('/game/pashu', function() { GamePage.renderPashu(); });
            this.router.on('/game/ranking', function() { GamePage.renderRanking(); });

            // ---- 消息互动 ----
            this.router.on('/wall/config', function() { WallPage.renderConfig(); });
            this.router.on('/wall/danmu', function() { WallPage.renderDanmu(); });
            this.router.on('/wall/messages', function() { WallPage.renderMessages(); });
            this.router.on('/wall/notice', function() { WallPage.renderNotice(); });

            // ---- 投票相册 ----
            this.router.on('/content/vote', function() { ContentPage.renderVote(); });
            this.router.on('/content/album', function() { ContentPage.renderAlbum(); });

            // ---- 红包管理 ----
            this.router.on('/redpacket/config', function() { RedpacketPage.renderConfig(); });
            this.router.on('/redpacket/rounds', function() { RedpacketPage.renderRounds(); });
            this.router.on('/redpacket/records', function() { RedpacketPage.renderRecords(); });

            // ---- 系统设置 ----
            this.router.on('/system/theme', function() { SystemPage.renderTheme(); });
            this.router.on('/system/background', function() { SystemPage.renderBackground(); });
            this.router.on('/system/music', function() { SystemPage.renderMusic(); });
            this.router.on('/system/switch', function() { SystemPage.renderSwitch(); });
            this.router.on('/system/basic', function() { SystemPage.renderBasic(); });
            this.router.on('/system/security', function() { SystemPage.renderSecurity(); });

            // 404 处理
            this.router.notFound(function() {
                Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>页面不存在</p></div>');
            });

            // 启动路由
            this.router.start();
        },

        /**
         * 加载活动列表
         */
        _loadActivities: function() {
            var self = this;
            Api.getActivities().then(function(res) {
                var list = res.data || [];
                if (!Array.isArray(list)) {
                    list = list.list || [];
                }

                // 恢复上次选择的活动
                var savedId = localStorage.getItem(ACTIVITY_KEY);
                if (savedId && list.some(function(a) { return String(a.id) === String(savedId); })) {
                    self.currentActivityId = savedId;
                } else if (list.length > 0) {
                    self.currentActivityId = String(list[0].id);
                }

                // 设置当前活动名称
                var current = list.find(function(a) { return String(a.id) === String(self.currentActivityId); });
                if (current) {
                    self.currentActivityName = current.name || current.title || '未命名活动';
                }

                Layout.setCurrentActivity(self.currentActivityName);
                Layout.renderActivities(list, self.currentActivityId);
            }).catch(function() {});
        },

        /**
         * 切换活动
         */
        switchActivity: function(actId) {
            this.currentActivityId = String(actId);
            localStorage.setItem(ACTIVITY_KEY, this.currentActivityId);

            var items = document.querySelectorAll('#activity-list li');
            items.forEach(function(li) {
                li.classList.toggle('active', li.getAttribute('data-id') === String(actId));
            });

            var activeLi = document.querySelector('#activity-list li[data-id="' + actId + '"]');
            if (activeLi) {
                this.currentActivityName = activeLi.textContent.trim();
                Layout.setCurrentActivity(this.currentActivityName);
            }

            var currentRoute = this.router.current();
            if (currentRoute) {
                this.router._resolve();
            }
        },

        /**
         * 获取当前活动 ID
         */
        getCurrentActivityId: function() {
            return this.currentActivityId;
        },

        /**
         * 显示创建活动弹窗（含公司/门店选择）
         */
        showCreateActivity: function() {
            var self = this;
            // 先加载门店列表
            Api.getStores({ limit: 100 }).then(function(res) {
                var stores = (res.data && res.data.list) ? res.data.list : (res.data || []);
                self._openCreateDialog(stores);
            }).catch(function() {
                self._openCreateDialog([]);
            });
        },

        _openCreateDialog: function(stores) {
            var self = this;
            // 构建门店选项
            var storeOptions = '<option value="0">不绑定公司/门店</option>';
            if (stores && stores.length > 0) {
                for (var i = 0; i < stores.length; i++) {
                    storeOptions += '<option value="' + stores[i].id + '">' + (stores[i].name || stores[i].title || '未命名') + '</option>';
                }
            }

            var formHtml = '<div style="padding:20px 30px;">' +
                '<form class="layui-form" lay-filter="createActivityForm">' +
                '<div class="layui-form-item">' +
                    '<label class="layui-form-label">活动名称</label>' +
                    '<div class="layui-input-block">' +
                        '<input type="text" name="title" class="layui-input" placeholder="请输入活动名称" required lay-verify="required">' +
                    '</div>' +
                '</div>' +
                '<div class="layui-form-item">' +
                    '<label class="layui-form-label">开始时间</label>' +
                    '<div class="layui-input-block">' +
                        '<input type="text" name="started_at" id="act-start-time" class="layui-input" placeholder="选择开始时间" readonly>' +
                    '</div>' +
                '</div>' +
                '<div class="layui-form-item">' +
                    '<label class="layui-form-label">结束时间</label>' +
                    '<div class="layui-input-block">' +
                        '<input type="text" name="ended_at" id="act-end-time" class="layui-input" placeholder="选择结束时间" readonly>' +
                    '</div>' +
                '</div>' +
                '<div class="layui-form-item">' +
                    '<label class="layui-form-label">绑定公司</label>' +
                    '<div class="layui-input-block">' +
                        '<select name="mdid" lay-filter="storeSelect">' + storeOptions + '</select>' +
                    '</div>' +
                '</div>' +
                '</form>' +
                '</div>';

            var layerIdx = layui.layer.open({
                type: 1,
                title: '新建活动',
                area: ['500px', '420px'],
                content: formHtml,
                btn: ['创建', '取消'],
                yes: function(index) {
                    var title = document.querySelector('[lay-filter="createActivityForm"] input[name="title"]').value.trim();
                    var startedAt = document.querySelector('[lay-filter="createActivityForm"] input[name="started_at"]').value;
                    var endedAt = document.querySelector('[lay-filter="createActivityForm"] input[name="ended_at"]').value;
                    var mdid = document.querySelector('[lay-filter="createActivityForm"] select[name="mdid"]').value;

                    if (!title) {
                        layui.layer.msg('请输入活动名称', { icon: 2 });
                        return;
                    }

                    var data = { title: title, mdid: parseInt(mdid) || 0 };
                    if (startedAt) data.started_at = startedAt;
                    if (endedAt) data.ended_at = endedAt;

                    Api.createActivity(data).then(function(res) {
                        layui.layer.close(index);
                        layui.layer.msg('活动创建成功', { icon: 1 });
                        // 刷新活动列表并切换到新活动
                        var newId = (res.data && res.data.id) ? res.data.id : null;
                        self._loadActivities();
                        if (newId) {
                            setTimeout(function() {
                                self.switchActivity(newId);
                            }, 500);
                        }
                    }).catch(function(err) {
                        layui.layer.msg((err && err.msg) || '创建失败', { icon: 2 });
                    });
                },
                success: function() {
                    // 初始化日期时间选择器
                    layui.laydate.render({
                        elem: '#act-start-time',
                        type: 'datetime',
                        format: 'yyyy-MM-dd HH:mm:ss'
                    });
                    layui.laydate.render({
                        elem: '#act-end-time',
                        type: 'datetime',
                        format: 'yyyy-MM-dd HH:mm:ss'
                    });
                    layui.form.render('select', 'createActivityForm');
                }
            });
        },

        // ============================================================
        // 登录页面管理
        // ============================================================

        /**
         * 显示登录页
         */
        showLogin: function() {
            Layout.showLogin();
            this._bindLoginTabs();
            this._bindPasswordLogin();
            this._bindPhoneBind();
            // 默认显示扫码登录，自动加载二维码
            this._switchLoginTab('qrcode');
        },

        /**
         * 绑定登录 Tab 切换
         */
        _bindLoginTabs: function() {
            var self = this;
            var tabs = document.querySelectorAll('#login-tabs .login-tab');
            tabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    var tabName = this.getAttribute('data-tab');
                    self._switchLoginTab(tabName);
                });
            });
        },

        /**
         * 切换登录面板
         */
        _switchLoginTab: function(tabName) {
            // 更新 Tab 激活状态
            var tabs = document.querySelectorAll('#login-tabs .login-tab');
            tabs.forEach(function(t) {
                t.classList.toggle('active', t.getAttribute('data-tab') === tabName);
            });

            // 显示对应面板
            var panels = document.querySelectorAll('.login-panel');
            panels.forEach(function(p) { p.classList.remove('active'); });

            var loginTabs = document.getElementById('login-tabs');

            if (tabName === 'qrcode') {
                document.getElementById('panel-qrcode').classList.add('active');
                if (loginTabs) loginTabs.style.display = 'flex';
                this._startQrLogin();
            } else if (tabName === 'password') {
                document.getElementById('panel-password').classList.add('active');
                if (loginTabs) loginTabs.style.display = 'flex';
                this._stopQrPolling();
            } else if (tabName === 'bind') {
                document.getElementById('panel-bind').classList.add('active');
                if (loginTabs) loginTabs.style.display = 'none';
                this._stopQrPolling();
            }
        },

        // ============================================================
        // 扫码登录流程
        // ============================================================

        /**
         * 开始扫码登录 - 获取二维码并开始轮询
         */
        _startQrLogin: function() {
            var self = this;
            this._stopQrPolling();

            // 显示加载状态
            var loading = document.getElementById('qr-loading');
            var imgEl = document.getElementById('qr-image');
            var expired = document.getElementById('qr-expired');
            var statusMsg = document.getElementById('qr-status-msg');
            if (loading) loading.style.display = 'block';
            if (imgEl) imgEl.style.display = 'none';
            if (expired) expired.style.display = 'none';
            if (statusMsg) statusMsg.style.display = 'none';

            // 请求二维码
            Api.getQrCode().then(function(res) {
                var data = res.data || {};
                self._qrSceneId = data.scene_id;
                var qrUrl = data.qr_url;
                var expire = data.expire || 300;

                if (loading) loading.style.display = 'none';
                if (imgEl) {
                    imgEl.src = qrUrl;
                    imgEl.style.display = 'block';
                }

                // 绑定刷新按钮
                var refreshBtn = document.getElementById('qr-refresh-btn');
                if (refreshBtn) {
                    refreshBtn.onclick = function() { self._startQrLogin(); };
                }

                // 设置过期计时器
                self._qrExpireTimer = setTimeout(function() {
                    self._stopQrPolling();
                    if (expired) expired.style.display = 'flex';
                }, expire * 1000);

                // 开始轮询扫码状态
                self._qrTimer = setInterval(function() {
                    self._pollQrStatus();
                }, 2000);
            }).catch(function(err) {
                if (loading) loading.style.display = 'none';
                if (expired) expired.style.display = 'flex';
                var expP = expired ? expired.querySelector('p') : null;
                if (expP) expP.textContent = '二维码加载失败';
            });
        },

        /**
         * 轮询扫码状态
         */
        _pollQrStatus: function() {
            var self = this;
            if (!this._qrSceneId) return;

            Api.checkQrCode(this._qrSceneId).then(function(res) {
                var data = res.data || {};
                var status = data.status;

                if (status === 'confirmed') {
                    // 已绑定用户，直接登录
                    self._stopQrPolling();
                    self._showQrStatus('登录成功，正在进入...', 'success');

                    if (data.token) {
                        Api.setToken(data.token);
                        self.userInfo = { name: data.name || '', bid: data.bid, user_id: data.user_id };
                        setTimeout(function() {
                            self._bootApp();
                        }, 500);
                    }
                } else if (status === 'need_bind') {
                    // 未绑定用户，跳转手机绑定页面
                    self._stopQrPolling();
                    self._bindToken = data.bind_token;
                    self._bindNickname = data.wx_nickname || '';
                    self._bindAvatar = data.wx_avatar || '';
                    self._showBindPanel();
                } else if (status === 'expired') {
                    self._stopQrPolling();
                    var expired = document.getElementById('qr-expired');
                    if (expired) expired.style.display = 'flex';
                }
                // status === 'pending' 继续轮询
            }).catch(function() {
                // 请求失败，继续轮询
            });
        },

        /**
         * 停止扫码轮询
         */
        _stopQrPolling: function() {
            if (this._qrTimer) { clearInterval(this._qrTimer); this._qrTimer = null; }
            if (this._qrExpireTimer) { clearTimeout(this._qrExpireTimer); this._qrExpireTimer = null; }
        },

        /**
         * 显示扫码状态消息
         */
        _showQrStatus: function(msg, type) {
            var el = document.getElementById('qr-status-msg');
            if (el) {
                el.textContent = msg;
                el.className = 'qr-status-msg ' + (type || 'info');
                el.style.display = 'block';
            }
        },

        // ============================================================
        // 手机号绑定流程
        // ============================================================

        /**
         * 显示手机绑定面板
         */
        _showBindPanel: function() {
            // 填充微信信息
            var avatarEl = document.getElementById('bind-avatar');
            var nickEl = document.getElementById('bind-nickname');
            if (avatarEl) {
                avatarEl.src = this._bindAvatar || 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect fill="%23e0e0e0" width="64" height="64" rx="32"/><text x="32" y="38" font-size="24" text-anchor="middle" fill="%23999">☺</text></svg>';
                avatarEl.style.display = 'block';
            }
            if (nickEl) {
                nickEl.textContent = this._bindNickname || '微信用户';
            }

            // 切换到绑定面板
            this._switchLoginTab('bind');
        },

        /**
         * 绑定手机号表单逻辑
         */
        _bindPhoneBind: function() {
            var self = this;

            // 发送验证码按钮
            var sendBtn = document.getElementById('send-code-btn');
            if (sendBtn && !sendBtn._bound) {
                sendBtn._bound = true;
                sendBtn.addEventListener('click', function() {
                    var phone = (document.getElementById('bind-phone').value || '').trim();
                    if (!/^1[3-9]\d{9}$/.test(phone)) {
                        self._showBindError('请输入正确的手机号');
                        return;
                    }
                    sendBtn.disabled = true;
                    Api.sendBindCode(phone).then(function() {
                        self._startSmsCountdown(sendBtn);
                    }).catch(function(err) {
                        sendBtn.disabled = false;
                        self._showBindError((err && err.msg) || '发送失败');
                    });
                });
            }

            // 绑定提交
            var bindForm = document.getElementById('bind-form');
            if (bindForm && !bindForm._bound) {
                bindForm._bound = true;
                bindForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var phone = (document.getElementById('bind-phone').value || '').trim();
                    var code = (document.getElementById('bind-code').value || '').trim();
                    var company = (document.getElementById('bind-company').value || '').trim();
                    var errorEl = document.getElementById('bind-error');
                    var btn = document.getElementById('bind-btn');

                    if (!/^1[3-9]\d{9}$/.test(phone)) {
                        self._showBindError('请输入正确的手机号');
                        return;
                    }
                    if (!code || code.length < 4) {
                        self._showBindError('请输入验证码');
                        return;
                    }

                    if (errorEl) errorEl.style.display = 'none';
                    btn.disabled = true;
                    btn.querySelector('span').textContent = '注册中...';

                    Api.wxBind({
                        bind_token: self._bindToken,
                        phone: phone,
                        sms_code: code,
                        name: company
                    }).then(function(res) {
                        var data = res.data || res;
                        if (data.token) {
                            Api.setToken(data.token);
                            self.userInfo = { name: data.name || '', bid: data.bid, user_id: data.user_id };
                            layui.layer.msg('注册成功！', { icon: 1 });
                            setTimeout(function() {
                                self._bootApp();
                            }, 800);
                        } else {
                            self._showBindError(res.msg || '注册失败');
                        }
                    }).catch(function(err) {
                        self._showBindError((err && err.msg) || '注册失败，请重试');
                    }).finally(function() {
                        btn.disabled = false;
                        btn.querySelector('span').textContent = '完成注册';
                    });
                });
            }
        },

        /**
         * 显示绑定错误信息
         */
        _showBindError: function(msg) {
            var el = document.getElementById('bind-error');
            if (el) {
                el.textContent = msg;
                el.style.display = 'block';
            }
        },

        /**
         * 短信验证码倒计时
         */
        _startSmsCountdown: function(btn) {
            var self = this;
            this._smsCountdown = 60;
            btn.disabled = true;
            btn.textContent = this._smsCountdown + 's 后重发';

            if (this._smsTimer) clearInterval(this._smsTimer);
            this._smsTimer = setInterval(function() {
                self._smsCountdown--;
                if (self._smsCountdown <= 0) {
                    clearInterval(self._smsTimer);
                    self._smsTimer = null;
                    btn.disabled = false;
                    btn.textContent = '发送验证码';
                } else {
                    btn.textContent = self._smsCountdown + 's 后重发';
                }
            }, 1000);
        },

        // ============================================================
        // 密码登录流程
        // ============================================================

        /**
         * 绑定密码登录表单
         */
        _bindPasswordLogin: function() {
            var self = this;
            var form = document.getElementById('login-form');
            if (!form) return;

            // 防止重复绑定
            if (form._bound) return;
            form._bound = true;

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var username = document.getElementById('login-username').value.trim();
                var password = document.getElementById('login-password').value.trim();
                var errorEl = document.getElementById('login-error');
                var btn = document.getElementById('login-btn');

                if (!username) { errorEl.textContent = '请输入用户名'; errorEl.style.display = 'block'; return; }
                if (!password) { errorEl.textContent = '请输入密码'; errorEl.style.display = 'block'; return; }

                errorEl.style.display = 'none';
                btn.disabled = true;
                btn.querySelector('span').textContent = '登录中...';

                Api.login(username, password).then(function(res) {
                    var data = res.data || res;
                    if (data.token) {
                        Api.setToken(data.token);
                        self.userInfo = data;
                        self._bootApp();
                    } else {
                        errorEl.textContent = res.msg || '登录失败';
                        errorEl.style.display = 'block';
                    }
                }).catch(function(err) {
                    var msg = (err && err.msg) ? err.msg : '登录失败，请检查账号密码';
                    errorEl.textContent = msg;
                    errorEl.style.display = 'block';
                }).finally(function() {
                    btn.disabled = false;
                    btn.querySelector('span').textContent = '登 录';
                });
            });
        },

        /**
         * 退出登录
         */
        logout: function() {
            this._stopQrPolling();
            Api.logout().catch(function() {});
            Api.clearToken();
            localStorage.removeItem(ACTIVITY_KEY);
            this.userInfo = null;
            this.currentActivityId = null;
            this.showLogin();
        }
    };

    global.App = App;

    // DOM 加载完成后启动
    document.addEventListener('DOMContentLoaded', function() {
        App.init();
    });
})(window);
