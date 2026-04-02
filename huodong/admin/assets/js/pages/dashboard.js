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
                '<div class="quick-action" onclick="DashboardPage.gzhSettings()"><div class="qa-icon"><i class="fab fa-weixin"></i></div><div class="qa-text">公众号设置</div></div>' +
                '</div></div>';

            html += '<div class="content-card"><div class="card-title">各页面入口地址</div>' +
                '<div class="url-cards">' +
                '<div class="url-card"><span class="url-label">大屏幕地址</span><span class="url-value" id="url-screen">加载中...</span><button class="url-copy" onclick="DashboardPage.copyUrl(\'url-screen\')"><i class="fas fa-copy"></i> 复制</button></div>' +
                '<div class="url-card"><span class="url-label">手机端签到地址</span><span class="url-value" id="url-mobile">加载中...</span><button class="url-copy" onclick="DashboardPage.copyUrl(\'url-mobile\')"><i class="fas fa-copy"></i> 复制</button></div>' +
                '<div class="url-card"><span class="url-label">后台管理地址</span><span class="url-value" id="url-admin">' + window.location.href.split('#')[0] + '</span><button class="url-copy" onclick="DashboardPage.copyUrl(\'url-admin\')"><i class="fas fa-copy"></i> 复制</button></div>' +
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
         * 加载入口地址（参考旧 huodong 系统格式）
         */
        loadUrls: function(actId) {
            var self = this;
            // 同时获取活动详情和手机端地址
            Promise.all([
                Api.getActivity(actId),
                Api.getMobileUrls(actId)
            ]).then(function(results) {
                var actData = results[0].data || results[0];
                var mobileData = results[1].data || results[1];

                // 大屏地址：优先用新系统 /s/{access_code}
                var screenUrl = '';
                if (actData && actData.access_code) {
                    screenUrl = 'https://wxhd.eivie.cn/s/' + actData.access_code;
                }
                var screenEl = document.getElementById('url-screen');
                if (screenEl) screenEl.textContent = screenUrl || '未配置';

                // 手机端签到地址
                var mobileSignUrl = '';
                if (mobileData && mobileData.qrcode_text) {
                    mobileSignUrl = mobileData.qrcode_text || '';
                }
                var mobileEl = document.getElementById('url-mobile');
                if (mobileEl) mobileEl.textContent = mobileSignUrl || '未配置';

                // 缓存到实例以便其他方法使用
                self._screenUrl = screenUrl;
                self._mobileSignUrl = mobileSignUrl;
                self._mobileUrls = mobileData;
                self._accessCode = actData ? actData.access_code : '';
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
            if (this._screenUrl) {
                window.open(this._screenUrl, '_blank');
            } else {
                Api.getActivity(actId).then(function(res) {
                    var data = res.data || res;
                    if (data && data.access_code) {
                        window.open('https://wxhd.eivie.cn/s/' + data.access_code, '_blank');
                    }
                });
            }
        },

        openMobile: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return layui.layer.msg('请先选择活动', { icon: 2 });
            var self = this;

            var showUrlsPopup = function(mobileData) {
                var urls = mobileData.urls || [];
                var html = '<div style="padding:20px;">';
                html += '<div style="margin-bottom:12px;font-size:13px;color:#666;">手机端各功能入口地址：</div>';
                for (var i = 0; i < urls.length; i++) {
                    html += '<div style="margin-bottom:10px;padding:8px 12px;background:#f7f8fa;border-radius:6px;">';
                    html += '<div style="font-size:12px;color:#999;margin-bottom:3px;">' + urls[i].label + '</div>';
                    html += '<div style="font-size:13px;color:#333;word-break:break-all;" id="mu-' + urls[i].key + '">' + urls[i].url + '</div>';
                    html += '</div>';
                }
                html += '<button class="layui-btn layui-btn-normal layui-btn-sm" style="width:100%;margin-top:10px;" onclick="DashboardPage.copyUrl(\'url-mobile\')"><i class="fas fa-copy"></i> 复制签到地址</button>';
                html += '</div>';

                layui.layer.open({
                    type: 1,
                    title: '<i class="fas fa-mobile-alt" style="margin-right:6px;"></i>手机端入口地址',
                    area: ['500px', '420px'],
                    content: html
                });
            };

            if (self._mobileUrls && self._mobileUrls.access_code) {
                showUrlsPopup(self._mobileUrls);
            } else {
                Api.getMobileUrls(actId).then(function(res) {
                    var data = res.data || res;
                    self._mobileUrls = data;
                    showUrlsPopup(data);
                });
            }
        },

        showQrCode: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return layui.layer.msg('请先选择活动', { icon: 2 });
            var self = this;

            var generateQr = function(text) {
                var html = '<div style="padding:20px;text-align:center;">';
                html += '<div id="qr-code-container" style="display:inline-block;padding:15px;background:#fff;border:1px solid #eee;border-radius:8px;"></div>';
                html += '<div style="margin-top:12px;font-size:12px;color:#666;word-break:break-all;max-width:320px;margin-left:auto;margin-right:auto;">' + text + '</div>';
                html += '<div style="margin-top:10px;">';
                html += '<button class="layui-btn layui-btn-normal layui-btn-sm" onclick="DashboardPage.downloadQrCode()"><i class="fas fa-download"></i> 下载二维码</button>';
                html += '</div>';
                html += '</div>';

                layui.layer.open({
                    type: 1,
                    title: '<i class="fas fa-qrcode" style="margin-right:6px;"></i>活动二维码',
                    area: ['420px', '480px'],
                    content: html,
                    success: function() {
                        var container = document.getElementById('qr-code-container');
                        if (container && typeof QRCode !== 'undefined') {
                            new QRCode(container, {
                                text: text,
                                width: 256,
                                height: 256,
                                colorDark: '#000000',
                                colorLight: '#ffffff',
                                correctLevel: QRCode.CorrectLevel.Q
                            });
                        } else {
                            container.innerHTML = '<p style="color:red;">二维码组件加载失败</p>';
                        }
                    }
                });
            };

            if (self._mobileSignUrl) {
                generateQr(self._mobileSignUrl);
            } else {
                Api.getMobileUrls(actId).then(function(res) {
                    var data = res.data || res;
                    self._mobileUrls = data;
                    self._mobileSignUrl = data.qrcode_text || '';
                    if (self._mobileSignUrl) {
                        generateQr(self._mobileSignUrl);
                    } else {
                        layui.layer.msg('无法生成二维码，请检查配置', { icon: 2 });
                    }
                });
            }
        },

        downloadQrCode: function() {
            var container = document.getElementById('qr-code-container');
            if (!container) return;
            var canvas = container.querySelector('canvas');
            if (canvas) {
                var link = document.createElement('a');
                link.download = '活动二维码.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            } else {
                var img = container.querySelector('img');
                if (img) {
                    var link = document.createElement('a');
                    link.download = '活动二维码.png';
                    link.href = img.src;
                    link.click();
                }
            }
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
        },

        /**
         * 公众号设置（微信服务器配置 + AppID/AppSecret）
         */
        gzhSettings: function() {
            var loadIdx = layui.layer.load(2);
            Api.getSettings().then(function(res) {
                layui.layer.close(loadIdx);
                var data = res.data || res;
                var wxfw = data.wxfw || {};
                var wxServer = data.wx_server || {};

                var callbackUrl = wxServer.callback_url || 'https://wxhd.eivie.cn/api/hd/wx/callback';
                var token = wxServer.token || '';
                var encodingMode = wxServer.encoding_mode || '明文模式';
                var appid = wxfw.appid || '';
                var appsecret = wxfw.appsecret || '';

                var contentHtml = '<div style="padding:20px 24px;">' +
                    '<div style="margin-bottom:16px;background:#f0f9eb;border:1px solid #e1f3d8;border-radius:8px;padding:14px 16px;">' +
                    '<div style="font-weight:bold;font-size:14px;color:#67c23a;margin-bottom:10px;"><i class="fas fa-server" style="margin-right:6px;"></i>微信公众号服务器配置</div>' +
                    '<div style="font-size:12px;color:#999;margin-bottom:10px;">请将以下信息填写到微信公众号后台「开发 > 基本配置 > 服务器配置」中</div>' +
                    '<div style="margin-bottom:8px;">' +
                    '<label style="display:block;font-size:12px;color:#666;margin-bottom:2px;">服务器地址(URL)</label>' +
                    '<div style="display:flex;align-items:center;gap:6px;"><input type="text" id="gzh-callback-url" class="layui-input" value="' + callbackUrl + '" readonly style="flex:1;font-size:12px;background:#fff;"><button type="button" class="layui-btn layui-btn-xs layui-btn-normal" onclick="DashboardPage.copyField(\'gzh-callback-url\')"><i class="fas fa-copy"></i></button></div>' +
                    '</div>' +
                    '<div style="margin-bottom:8px;">' +
                    '<label style="display:block;font-size:12px;color:#666;margin-bottom:2px;">令牌(Token)</label>' +
                    '<div style="display:flex;align-items:center;gap:6px;"><input type="text" id="gzh-token" class="layui-input" value="' + token + '" readonly style="flex:1;font-size:12px;background:#fff;"><button type="button" class="layui-btn layui-btn-xs layui-btn-normal" onclick="DashboardPage.copyField(\'gzh-token\')"><i class="fas fa-copy"></i></button></div>' +
                    '</div>' +
                    '<div style="font-size:11px;color:#999;margin-top:6px;"><i class="fas fa-info-circle"></i> 消息加密方式请选择「' + encodingMode + '」</div>' +
                    '</div>' +
                    '<div style="margin-bottom:14px;">' +
                    '<label style="display:block;font-size:13px;font-weight:bold;color:#333;margin-bottom:6px;"><i class="fab fa-weixin" style="color:#07c160;margin-right:4px;"></i>公众号AppID</label>' +
                    '<input type="text" id="gzh-appid" class="layui-input" value="' + appid + '" placeholder="请输入公众号AppID">' +
                    '</div>' +
                    '<div style="margin-bottom:16px;">' +
                    '<label style="display:block;font-size:13px;font-weight:bold;color:#333;margin-bottom:6px;"><i class="fas fa-key" style="color:#e6a23c;margin-right:4px;"></i>公众号AppSecret</label>' +
                    '<input type="password" id="gzh-appsecret" class="layui-input" value="' + appsecret + '" placeholder="请输入公众号AppSecret">' +
                    '<p style="font-size:11px;color:#999;margin-top:4px;">已配置的AppSecret不会明文显示，重新填写即可更新</p>' +
                    '</div>' +
                    '<button class="layui-btn layui-btn-normal" style="width:100%;" id="gzh-save-btn"><i class="fas fa-save"></i> 保存公众号配置</button>' +
                    '</div>';

                var layerIdx = layui.layer.open({
                    type: 1,
                    title: '<i class="fab fa-weixin" style="margin-right:6px;color:#07c160;"></i>公众号设置',
                    area: ['500px', '520px'],
                    content: contentHtml,
                    success: function() {
                        document.getElementById('gzh-save-btn').addEventListener('click', function() {
                            var newAppid = document.getElementById('gzh-appid').value.trim();
                            var newSecret = document.getElementById('gzh-appsecret').value.trim();

                            if (!newAppid) {
                                return layui.layer.msg('请输入AppID', { icon: 2 });
                            }

                            Api.updateWxConfig({ appid: newAppid, appsecret: newSecret }).then(function() {
                                layui.layer.close(layerIdx);
                                layui.layer.msg('公众号配置已保存', { icon: 1 });
                            });
                        });
                    }
                });
            }).catch(function() {
                layui.layer.close(loadIdx);
            });
        },

        /**
         * 复制输入框内容
         */
        copyField: function(fieldId) {
            var el = document.getElementById(fieldId);
            if (!el) return;
            var text = el.value;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    layui.layer.msg('已复制到剪贴板', { icon: 1 });
                }).catch(function() {
                    el.select();
                    document.execCommand('copy');
                    layui.layer.msg('已复制到剪贴板', { icon: 1 });
                });
            } else {
                el.select();
                document.execCommand('copy');
                layui.layer.msg('已复制到剪贴板', { icon: 1 });
            }
        }
    };

    global.DashboardPage = DashboardPage;
})(window);
