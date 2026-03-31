/**
 * 仪表盘页面模块
 */
;(function(global) {
    'use strict';

    var DashboardPage = {
        /**
         * 渲染仪表盘
         */
        render: function() {
            var actId = App.getCurrentActivityId();
            var html = '<div class="stat-cards">' +
                '<div class="stat-card"><div class="stat-icon blue"><i class="fas fa-user-check"></i></div><div class="stat-info"><div class="stat-value" id="stat-sign">--</div><div class="stat-label">签到人数</div></div></div>' +
                '<div class="stat-card"><div class="stat-icon green"><i class="fas fa-comments"></i></div><div class="stat-info"><div class="stat-value" id="stat-wall">--</div><div class="stat-label">上墙消息</div></div></div>' +
                '<div class="stat-card"><div class="stat-icon orange"><i class="fas fa-gift"></i></div><div class="stat-info"><div class="stat-value" id="stat-lottery">--</div><div class="stat-label">抽奖轮次</div></div></div>' +
                '<div class="stat-card"><div class="stat-icon red"><i class="fas fa-heartbeat"></i></div><div class="stat-info"><div class="stat-value" id="stat-status">--</div><div class="stat-label">活动状态</div></div></div>' +
                '</div>';

            html += '<div class="content-card"><div class="card-title">快捷操作</div>' +
                '<div class="quick-actions">' +
                '<div class="quick-action" onclick="App.showCreateActivity()"><div class="qa-icon"><i class="fas fa-plus-circle"></i></div><div class="qa-text">新建活动</div></div>' +
                '<div class="quick-action" onclick="DashboardPage.openScreen()"><div class="qa-icon"><i class="fas fa-tv"></i></div><div class="qa-text">大屏幕预览</div></div>' +
                '<div class="quick-action" onclick="DashboardPage.openMobile()"><div class="qa-icon"><i class="fas fa-mobile-alt"></i></div><div class="qa-text">手机端链接</div></div>' +
                '<div class="quick-action" onclick="DashboardPage.showQrCode()"><div class="qa-icon"><i class="fas fa-qrcode"></i></div><div class="qa-text">活动二维码</div></div>' +
                '<div class="quick-action" onclick="DashboardPage.exportData()"><div class="qa-icon"><i class="fas fa-download"></i></div><div class="qa-text">数据导出</div></div>' +
                '<div class="quick-action" onclick="DashboardPage.screenPassword()"><div class="qa-icon"><i class="fas fa-lock"></i></div><div class="qa-text">大屏密码设置</div></div>' +
                '</div></div>';

            html += '<div class="content-card"><div class="card-title">入口地址</div>' +
                '<div class="url-cards">' +
                '<div class="url-card"><span class="url-label">大屏地址</span><span class="url-value" id="url-screen">加载中...</span><button class="url-copy" onclick="DashboardPage.copyUrl(\'url-screen\')">复制</button></div>' +
                '<div class="url-card"><span class="url-label">手机端地址</span><span class="url-value" id="url-mobile">加载中...</span><button class="url-copy" onclick="DashboardPage.copyUrl(\'url-mobile\')">复制</button></div>' +
                '<div class="url-card"><span class="url-label">后台地址</span><span class="url-value" id="url-admin">' + window.location.href.split('#')[0] + '</span><button class="url-copy" onclick="DashboardPage.copyUrl(\'url-admin\')">复制</button></div>' +
                '</div></div>';

            Layout.setContent(html);

            if (actId) {
                this.loadStats(actId);
                this.loadUrls(actId);
            }
        },

        /**
         * 加载统计数据
         */
        loadStats: function(actId) {
            Api.getActivityStats(actId).then(function(res) {
                var data = res.data || res;
                if (data) {
                    var signEl = document.getElementById('stat-sign');
                    var wallEl = document.getElementById('stat-wall');
                    var lotteryEl = document.getElementById('stat-lottery');
                    var statusEl = document.getElementById('stat-status');

                    if (signEl) signEl.textContent = (data.sign_count || 0) + '/' + (data.sign_total || 0);
                    if (wallEl) wallEl.textContent = (data.wall_count || 0) + '条';
                    if (lotteryEl) lotteryEl.textContent = (data.lottery_rounds || 0) + '轮';
                    if (statusEl) {
                        var statusMap = { 0: '未开始', 1: '🟢 进行中', 2: '已结束' };
                        statusEl.textContent = statusMap[data.status] || '未知';
                    }
                }
            }).catch(function() {});
        },

        /**
         * 加载入口地址
         */
        loadUrls: function(actId) {
            Api.getActivity(actId).then(function(res) {
                var data = res.data || res;
                if (data && data.access_code) {
                    var baseUrl = window.location.origin;
                    var screenUrl = baseUrl + '/s/' + data.access_code;
                    var mobileUrl = screenUrl;
                    var screenEl = document.getElementById('url-screen');
                    var mobileEl = document.getElementById('url-mobile');
                    if (screenEl) screenEl.textContent = screenUrl;
                    if (mobileEl) mobileEl.textContent = mobileUrl;
                }
            }).catch(function() {});
        },

        /**
         * 复制 URL
         */
        copyUrl: function(elId) {
            var el = document.getElementById(elId);
            if (!el) return;
            var text = el.textContent;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    layui.layer.msg('已复制到剪贴板', { icon: 1 });
                });
            } else {
                var input = document.createElement('input');
                input.value = text;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                layui.layer.msg('已复制到剪贴板', { icon: 1 });
            }
        },

        openScreen: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return layui.layer.msg('请先选择活动', { icon: 2 });
            Api.getActivity(actId).then(function(res) {
                var data = res.data || res;
                if (data && data.access_code) {
                    window.open('/s/' + data.access_code, '_blank');
                }
            });
        },

        openMobile: function() {
            this.openScreen();
        },

        showQrCode: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return layui.layer.msg('请先选择活动', { icon: 2 });
            layui.layer.msg('二维码生成中...', { icon: 16, shade: 0.3, time: 2000 });
        },

        exportData: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return layui.layer.msg('请先选择活动', { icon: 2 });
            layui.layer.open({
                type: 1,
                title: '数据导出',
                area: ['400px', '280px'],
                content: '<div style="padding:20px;">' +
                    '<button class="btn btn-primary" style="width:100%;margin-bottom:10px;" onclick="Api.exportParticipants(' + actId + ')"><i class="fas fa-users"></i> 导出签到数据</button>' +
                    '<button class="btn btn-success" style="width:100%;margin-bottom:10px;" onclick="Api.exportMessages(' + actId + ')"><i class="fas fa-comments"></i> 导出消息数据</button>' +
                    '<button class="btn btn-warning" style="width:100%;" onclick="Api.exportLottery(' + actId + ')"><i class="fas fa-gift"></i> 导出抽奖数据</button>' +
                    '</div>'
            });
        },

        /**
         * 大屏密码设置
         */
        screenPassword: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return layui.layer.msg('请先选择活动', { icon: 2 });

            var loadIdx = layui.layer.load(2);
            Api.getScreenPassword(actId).then(function(res) {
                layui.layer.close(loadIdx);
                var data = res.data || res;
                var enabled = data.screen_password_enabled !== undefined ? data.screen_password_enabled : 1;
                var password = data.screen_password || 'eivie';

                var contentHtml = '<div style="padding:20px 30px;">' +
                    '<div style="margin-bottom:18px;">' +
                    '<label style="display:block;margin-bottom:6px;font-weight:bold;font-size:14px;">密码功能</label>' +
                    '<div style="display:flex;align-items:center;gap:10px;">' +
                    '<input type="checkbox" id="pwd-enabled" lay-skin="switch" lay-text="启用|关闭"' + (enabled ? ' checked' : '') + '>' +
                    '<span id="pwd-status-text" style="color:#999;font-size:12px;">' + (enabled ? '已启用：访问大屏需输入密码' : '已关闭：访问大屏无需密码') + '</span>' +
                    '</div>' +
                    '</div>' +
                    '<div id="pwd-input-area" style="margin-bottom:18px;' + (enabled ? '' : 'display:none;') + '">' +
                    '<label style="display:block;margin-bottom:6px;font-weight:bold;font-size:14px;">管理密码</label>' +
                    '<input type="text" id="pwd-value" class="layui-input" value="' + password + '" placeholder="请输入大屏管理密码" style="width:100%;">' +
                    '<p style="color:#999;font-size:12px;margin-top:4px;">默认密码为 eivie，可自行修改</p>' +
                    '</div>' +
                    '<button class="layui-btn layui-btn-normal" style="width:100%;" id="pwd-save-btn">保存设置</button>' +
                    '</div>';

                var layerIdx = layui.layer.open({
                    type: 1,
                    title: '<i class="fas fa-lock" style="margin-right:6px;"></i>大屏密码设置',
                    area: ['420px', '300px'],
                    content: contentHtml,
                    success: function(layero) {
                        // 绑定开关事件
                        var form = layui.form;
                        form.render('checkbox', layero.find('.layui-layer-content'));

                        var chk = layero.find('#pwd-enabled');
                        var inputArea = layero.find('#pwd-input-area');
                        var statusText = layero.find('#pwd-status-text');

                        chk.on('change', function() {
                            var isChecked = this.checked;
                            if (isChecked) {
                                inputArea.show();
                                statusText.text('已启用：访问大屏需输入密码');
                            } else {
                                inputArea.hide();
                                statusText.text('已关闭：访问大屏无需密码');
                            }
                        });

                        // 绑定保存按钮
                        layero.find('#pwd-save-btn').on('click', function() {
                            var isEnabled = layero.find('#pwd-enabled').is(':checked') ? 1 : 0;
                            var pwdVal = layero.find('#pwd-value').val().trim();

                            if (isEnabled && !pwdVal) {
                                return layui.layer.msg('请输入密码', { icon: 2 });
                            }

                            var saveData = {
                                screen_password_enabled: isEnabled
                            };
                            if (pwdVal) {
                                saveData.screen_password = pwdVal;
                            }

                            Api.updateScreenPassword(actId, saveData).then(function() {
                                layui.layer.close(layerIdx);
                                layui.layer.msg('密码设置已保存', { icon: 1 });
                            });
                        });
                    }
                });
            }).catch(function() {
                layui.layer.close(loadIdx);
            });
        }
    };

    global.DashboardPage = DashboardPage;
})(window);
