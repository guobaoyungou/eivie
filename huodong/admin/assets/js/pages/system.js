/**
 * 系统设置页面模块
 * 包含：主题展示、背景图、背景音乐、功能开关、基本设置、内容安全
 */
;(function(global) {
    'use strict';

    var SystemPage = {
        // ========== 主题展示 ==========
        renderTheme: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title">主题展示</div>' +
                '<div class="tab-header">' +
                '<div class="tab-item active" onclick="SystemPage._switchThemeTab(this,\'signtheme-panel\')">签到主题</div>' +
                '<div class="tab-item" onclick="SystemPage._switchThemeTab(this,\'kaimu-panel\')">开幕墙</div>' +
                '<div class="tab-item" onclick="SystemPage._switchThemeTab(this,\'bimu-panel\')">闭幕墙</div></div>' +
                '<div id="signtheme-panel">' +
                '<form class="layui-form" lay-filter="signThemeForm">' +
                '<div class="layui-form-item"><label class="layui-form-label">签到墙样式</label><div class="layui-input-block">' +
                '<select name="sign_theme_style" lay-filter="signThemeStyleSelect">' +
                '<option value="classic">样式一（经典瀑布流）</option>' +
                '<option value="matrix">样式二（矩阵墙）</option>' +
                '</select>' +
                '</div></div>' +
                '<div id="matrix-options" style="display:none;">' +
                '<div class="layui-form-item"><label class="layui-form-label">头像入场</label><div class="layui-input-block">' +
                '<select name="sign_theme_entrance"><option value="bounce">弹入缩放</option><option value="fade">淡入</option><option value="none">无动画</option></select>' +
                '</div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">滚动效果</label><div class="layui-input-block">' +
                '<select name="sign_theme_scroll"><option value="smooth">平滑滚动</option><option value="none">不滚动</option></select>' +
                '</div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">Toast通知</label><div class="layui-input-block">' +
                '<input type="checkbox" name="sign_theme_toast_enabled" value="1" lay-skin="switch" lay-text="开|关" checked>' +
                '</div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">中央大头像</label><div class="layui-input-block">' +
                '<input type="checkbox" name="sign_theme_center_avatar" value="1" lay-skin="switch" lay-text="开|关" checked>' +
                '</div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">流光边框</label><div class="layui-input-block">' +
                '<input type="checkbox" name="sign_theme_glow_border" value="1" lay-skin="switch" lay-text="开|关" checked>' +
                '</div></div>' +
                '</div>' +
                '<div class="layui-form-item" style="margin-top:16px;"><div class="layui-input-block">' +
                '<button type="button" class="btn btn-primary" onclick="SystemPage._saveSignTheme()"><i class="fas fa-save"></i> 保存签到主题</button>' +
                '</div></div>' +
                '</form></div>' +
                '<div id="kaimu-panel" style="display:none;">' +
                '<form class="layui-form" lay-filter="kaimuForm">' +
                '<div class="layui-form-item"><label class="layui-form-label">开幕图片</label><div class="layui-input-block"><input type="text" name="kaimu_image" class="layui-input" placeholder="开幕墙图片URL"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">显示方式</label><div class="layui-input-block"><select name="kaimu_display"><option value="center">居中显示</option><option value="fullscreen">全屏显示</option><option value="none">不显示</option></select></div></div>' +
                '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" onclick="SystemPage._saveKaimu()"><i class="fas fa-save"></i> 保存</button></div></div>' +
                '</form></div>' +
                '<div id="bimu-panel" style="display:none;">' +
                '<form class="layui-form" lay-filter="bimuForm">' +
                '<div class="layui-form-item"><label class="layui-form-label">闭幕图片</label><div class="layui-input-block"><input type="text" name="bimu_image" class="layui-input" placeholder="闭幕墙图片URL"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">显示方式</label><div class="layui-input-block"><select name="bimu_display"><option value="center">居中显示</option><option value="fullscreen">全屏显示</option><option value="none">不显示</option></select></div></div>' +
                '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" onclick="SystemPage._saveBimu()"><i class="fas fa-save"></i> 保存</button></div></div>' +
                '</form></div></div>';

            Layout.setContent(html);
            layui.form.render();

            // 监听样式切换，显示/隐藏矩阵墙选项
            layui.form.on('select(signThemeStyleSelect)', function(data) {
                document.getElementById('matrix-options').style.display = data.value === 'matrix' ? '' : 'none';
            });

            // 加载签到主题配置
            Api.getSignThemeConfig(actId).then(function(res) {
                var d = res.data || {};
                if (d.sign_theme_style) {
                    document.querySelector('[name="sign_theme_style"]').value = d.sign_theme_style;
                    layui.form.render('select', 'signThemeForm');
                    document.getElementById('matrix-options').style.display = d.sign_theme_style === 'matrix' ? '' : 'none';
                }
                if (d.sign_theme_entrance) {
                    document.querySelector('[name="sign_theme_entrance"]').value = d.sign_theme_entrance;
                    layui.form.render('select', 'signThemeForm');
                }
                if (d.sign_theme_scroll) {
                    document.querySelector('[name="sign_theme_scroll"]').value = d.sign_theme_scroll;
                    layui.form.render('select', 'signThemeForm');
                }
                var toastEl = document.querySelector('[name="sign_theme_toast_enabled"]');
                if (toastEl) { toastEl.checked = d.sign_theme_toast_enabled !== '0'; layui.form.render('checkbox', 'signThemeForm'); }
                var centerEl = document.querySelector('[name="sign_theme_center_avatar"]');
                if (centerEl) { centerEl.checked = d.sign_theme_center_avatar !== '0'; layui.form.render('checkbox', 'signThemeForm'); }
                var glowEl = document.querySelector('[name="sign_theme_glow_border"]');
                if (glowEl) { glowEl.checked = d.sign_theme_glow_border !== '0'; layui.form.render('checkbox', 'signThemeForm'); }
            }).catch(function() {});

            Api.getKaimuConfig(actId).then(function(res) {
                var d = res.data || {};
                if (d.image) document.querySelector('[name="kaimu_image"]').value = d.image;
                if (d.display) { document.querySelector('[name="kaimu_display"]').value = d.display; layui.form.render('select', 'kaimuForm'); }
            }).catch(function() {});

            Api.getBimuConfig(actId).then(function(res) {
                var d = res.data || {};
                if (d.image) document.querySelector('[name="bimu_image"]').value = d.image;
                if (d.display) { document.querySelector('[name="bimu_display"]').value = d.display; layui.form.render('select', 'bimuForm'); }
            }).catch(function() {});
        },

        _switchThemeTab: function(el, panelId) {
            el.parentElement.querySelectorAll('.tab-item').forEach(function(t) { t.classList.remove('active'); });
            el.classList.add('active');
            ['signtheme-panel', 'kaimu-panel', 'bimu-panel'].forEach(function(id) {
                var p = document.getElementById(id);
                if (p) p.style.display = id === panelId ? '' : 'none';
            });
        },

        _saveKaimu: function() {
            var actId = App.getCurrentActivityId();
            Api.updateKaimuConfig(actId, {
                image: document.querySelector('[name="kaimu_image"]').value,
                display: document.querySelector('[name="kaimu_display"]').value
            }).then(function() { layui.layer.msg('保存成功', { icon: 1 }); });
        },

        _saveBimu: function() {
            var actId = App.getCurrentActivityId();
            Api.updateBimuConfig(actId, {
                image: document.querySelector('[name="bimu_image"]').value,
                display: document.querySelector('[name="bimu_display"]').value
            }).then(function() { layui.layer.msg('保存成功', { icon: 1 }); });
        },

        _saveSignTheme: function() {
            var actId = App.getCurrentActivityId();
            var data = {
                sign_theme_style: document.querySelector('[name="sign_theme_style"]').value,
                sign_theme_entrance: document.querySelector('[name="sign_theme_entrance"]').value,
                sign_theme_scroll: document.querySelector('[name="sign_theme_scroll"]').value,
                sign_theme_toast_enabled: document.querySelector('[name="sign_theme_toast_enabled"]').checked ? '1' : '0',
                sign_theme_center_avatar: document.querySelector('[name="sign_theme_center_avatar"]').checked ? '1' : '0',
                sign_theme_glow_border: document.querySelector('[name="sign_theme_glow_border"]').checked ? '1' : '0'
            };
            Api.updateSignThemeConfig(actId, data).then(function() {
                layui.layer.msg('签到主题保存成功', { icon: 1 });
            });
        },

        // ========== 背景图 ==========
        renderBackground: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title"><span>背景图管理</span></div>' +
                '<p style="color:#999;font-size:13px;margin:-8px 0 16px 0;">按功能模块分类管理背景图/视频，支持 jpg、png、webp、mp4 格式，最大 10MB</p>' +
                '<div id="bg-module-list" class="loading-state"><i class="fas fa-spinner"></i> 加载中...</div></div>';
            Layout.setContent(html);
            this._loadBackgroundModules(actId);
        },

        _loadBackgroundModules: function(actId) {
            Api.getBackgrounds(actId).then(function(res) {
                var list = res.data || [];
                if (!Array.isArray(list)) list = [];
                var container = document.getElementById('bg-module-list');
                if (list.length === 0) {
                    container.innerHTML = '<div class="empty-state"><i class="fas fa-image"></i><p>暂无背景模块数据</p></div>';
                    return;
                }
                var h = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;">';
                list.forEach(function(bg) {
                    var hasMaterial = parseInt(bg.has_material || 0) === 1;
                    var isVideo = parseInt(bg.bgtype) === 2;
                    var src = bg.attachmentpath || '';
                    var previewHtml;

                    if (hasMaterial && src) {
                        // 有素材：显示图片或视频
                        if (isVideo) {
                            previewHtml = '<video src="' + src + '" autoplay loop muted playsinline style="width:100%;height:100%;object-fit:cover;"></video>';
                        } else {
                            previewHtml = '<img src="' + src + '" style="width:100%;height:100%;object-fit:cover;">';
                        }
                    } else {
                        // 无素材：纯色背景 + 提示文字
                        previewHtml = '<div style="width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;color:rgba(255,255,255,0.4);">' +
                            '<i class="fas fa-image" style="font-size:32px;margin-bottom:8px;"></i>' +
                            '<span style="font-size:12px;">未设置背景素材</span></div>';
                    }

                    h += '<div class="bg-module-card" style="background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);">' +
                        '<div style="position:relative;aspect-ratio:16/9;background:#1a1a2e;overflow:hidden;">' +
                        previewHtml +
                        '</div>' +
                        '<div style="padding:12px 16px;">' +
                        '<div style="font-size:15px;font-weight:600;color:#333;margin-bottom:4px;">' + (bg.name || '背景图') + '</div>' +
                        '<div style="font-size:12px;color:#999;margin-bottom:12px;">' + (hasMaterial ? (isVideo ? '视频素材' : '图片素材') : '纯色背景') + '</div>' +
                        '<div style="display:flex;gap:8px;">' +
                        '<label class="btn btn-primary btn-sm" style="cursor:pointer;margin:0;flex:1;text-align:center;">' +
                        '<i class="fas fa-upload"></i> ' + (hasMaterial ? '更换素材' : '上传素材') +
                        '<input type="file" accept="image/jpeg,image/png,image/webp,video/mp4" style="display:none;" onchange="SystemPage._handleBgFileSelect(this,\'' + bg.plugname + '\',' + actId + ')">' +
                        '</label>';

                    // 只有有素材时才显示删除按钮
                    if (hasMaterial) {
                        h += '<button class="btn btn-danger btn-sm" style="flex:1;" onclick="SystemPage._deleteBgMaterial(\'' + bg.plugname + '\',' + actId + ',\'' + (bg.name || '').replace(/'/g, "\\'") + '\')">' +
                            '<i class="fas fa-trash-alt"></i> 删除素材</button>';
                    }

                    h += '</div></div></div>';
                });
                h += '</div>';
                container.innerHTML = h;
            }).catch(function(e) {
                var c = document.getElementById('bg-module-list');
                if (c) c.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>加载失败</p></div>';
            });
        },

        _handleBgFileSelect: function(inputEl, plugname, actId) {
            var file = inputEl.files[0];
            if (!file) return;

            // 验证文件格式
            var allowTypes = ['image/jpeg', 'image/png', 'image/webp', 'video/mp4'];
            if (allowTypes.indexOf(file.type) === -1) {
                layui.layer.msg('不支持的文件格式，仅支持 jpg/png/webp/mp4', { icon: 2 });
                inputEl.value = '';
                return;
            }

            // 验证文件大小
            if (file.size > 10 * 1024 * 1024) {
                layui.layer.msg('文件大小不能超过 10MB', { icon: 2 });
                inputEl.value = '';
                return;
            }

            var loadIdx = layui.layer.load(1, { shade: [0.3, '#000'] });

            var formData = new FormData();
            formData.append('file', file);
            formData.append('plugname', plugname);
            formData.append('activity_id', actId);

            Api.uploadBackground(formData).then(function(res) {
                layui.layer.close(loadIdx);
                layui.layer.msg('上传成功', { icon: 1 });
                SystemPage._loadBackgroundModules(actId);
            }).catch(function() {
                layui.layer.close(loadIdx);
            });

            inputEl.value = '';
        },

        _deleteBgMaterial: function(plugname, actId, name) {
            layui.layer.confirm('确定删除「' + name + '」的背景素材？<br><span style="color:#999;font-size:12px;">删除后将使用纯色背景替代</span>', { icon: 3 }, function(idx) {
                layui.layer.close(idx);
                var loadIdx = layui.layer.load(1, { shade: [0.3, '#000'] });
                Api.resetBackground(actId, plugname).then(function() {
                    layui.layer.close(loadIdx);
                    layui.layer.msg('素材已删除', { icon: 1 });
                    SystemPage._loadBackgroundModules(actId);
                }).catch(function() {
                    layui.layer.close(loadIdx);
                });
            });
        },

        // ========== 背景音乐（按功能模块分卡片管理，weixin_music 表） ==========
        renderMusic: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title"><span>背景音乐管理</span></div>' +
                '<p style="color:#999;font-size:13px;margin:-8px 0 16px 0;">按功能模块分类管理背景音乐，支持 mp3 格式，最大 20MB</p>' +
                '<div id="bgmusic-module-list" class="loading-state"><i class="fas fa-spinner"></i> 加载中...</div></div>';
            Layout.setContent(html);
            this._loadBgMusicModules(actId);
        },

        _loadBgMusicModules: function(actId) {
            Api.getBgMusics(actId).then(function(res) {
                var list = res.data || [];
                if (!Array.isArray(list)) list = [];
                var container = document.getElementById('bgmusic-module-list');
                if (list.length === 0) {
                    container.innerHTML = '<div class="empty-state"><i class="fas fa-music"></i><p>暂无背景音乐模块数据</p></div>';
                    return;
                }
                var h = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;">';
                list.forEach(function(m) {
                    var isOn = parseInt(m.bgmusicstatus) === 1;
                    var musicPath = m.bgmusicpath || '';
                    var hasCustom = parseInt(m.bgmusic || 0) > 0;

                    h += '<div class="bg-module-card" style="background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);">' +
                        '<div style="padding:16px;">' +
                        // 标题栏 + 开关
                        '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">' +
                        '<div style="font-size:15px;font-weight:600;color:#333;">' +
                        '<i class="fas fa-music" style="color:#FB8C00;margin-right:6px;"></i>' + (m.name || '背景乐') +
                        '</div>' +
                        '<div><input type="checkbox" lay-skin="switch" lay-text="开|关"' + (isOn ? ' checked' : '') +
                        ' lay-filter="bgMusicToggle" data-plugname="' + m.plugname + '"></div>' +
                        '</div>' +
                        // 模块标识
                        '<div style="font-size:12px;color:#999;margin-bottom:12px;">模块标识：' + (m.plugname || '-') +
                        (hasCustom ? '' : ' <span style="background:rgba(0,0,0,0.06);color:#999;font-size:11px;padding:1px 6px;border-radius:3px;margin-left:6px;">默认</span>') +
                        '</div>' +
                        // 试听播放器
                        (musicPath ? '<div style="margin-bottom:12px;"><audio controls preload="none" style="width:100%;height:36px;" src="' + musicPath + '"></audio></div>' : '') +
                        // 上传按钮
                        '<div style="display:flex;gap:8px;">' +
                        '<label class="btn btn-primary btn-sm" style="cursor:pointer;margin:0;flex:1;text-align:center;">' +
                        '<i class="fas fa-upload"></i> 选择文件' +
                        '<input type="file" accept="audio/mpeg,audio/mp3,.mp3" style="display:none;" onchange="SystemPage._handleBgMusicFileSelect(this,\'' + m.plugname + '\',' + actId + ')">' +
                        '</label>' +
                        '</div>' +
                        '</div></div>';
                });
                h += '</div>';
                container.innerHTML = h;
                layui.form.render('checkbox');

                // 绑定开关事件
                layui.form.on('switch(bgMusicToggle)', function(data) {
                    var plugname = data.elem.getAttribute('data-plugname');
                    var newStatus = data.elem.checked ? 1 : 2;
                    Api.toggleBgMusic(actId, plugname, newStatus).then(function() {
                        layui.layer.msg((newStatus === 1 ? '已开启' : '已关闭') + '背景音乐', { icon: 1 });
                    }).catch(function() {
                        data.elem.checked = !data.elem.checked;
                        layui.form.render('checkbox');
                    });
                });
            }).catch(function(e) {
                var c = document.getElementById('bgmusic-module-list');
                if (c) c.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>加载失败</p></div>';
            });
        },

        _handleBgMusicFileSelect: function(inputEl, plugname, actId) {
            var file = inputEl.files[0];
            if (!file) return;

            // 验证文件格式
            if (file.type !== 'audio/mpeg' && file.type !== 'audio/mp3' && !file.name.toLowerCase().endsWith('.mp3')) {
                layui.layer.msg('仅支持 mp3 格式', { icon: 2 });
                inputEl.value = '';
                return;
            }

            // 验证文件大小
            if (file.size > 20 * 1024 * 1024) {
                layui.layer.msg('文件大小不能超过 20MB', { icon: 2 });
                inputEl.value = '';
                return;
            }

            var loadIdx = layui.layer.load(1, { shade: [0.3, '#000'] });

            var formData = new FormData();
            formData.append('file', file);
            formData.append('plugname', plugname);

            Api.uploadBgMusic(actId, formData).then(function(res) {
                layui.layer.close(loadIdx);
                layui.layer.msg('上传成功', { icon: 1 });
                SystemPage._loadBgMusicModules(actId);
            }).catch(function() {
                layui.layer.close(loadIdx);
            });

            inputEl.value = '';
        },

        // ========== 功能开关 ==========
        renderSwitch: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title">功能开关</div>' +
                '<div id="switch-grid" class="loading-state"><i class="fas fa-spinner"></i> 加载中...</div></div>';
            Layout.setContent(html);

            var switchDefs = [
                { code: 'qdq', name: '签到墙', icon: 'fa-user-check', color: '#1E88E5' },
                { code: 'threedimensionalsign', name: '3D签到', icon: 'fa-cube', color: '#7C4DFF' },
                { code: 'wall', name: '微信上墙', icon: 'fa-comments', color: '#43A047' },
                { code: 'vote', name: '投票', icon: 'fa-poll', color: '#FB8C00' },
                { code: 'lottery', name: '抽奖', icon: 'fa-gift', color: '#E53935' },
                { code: 'game', name: '游戏', icon: 'fa-gamepad', color: '#00BCD4' },
                { code: 'xiangce', name: '相册', icon: 'fa-images', color: '#8BC34A' },
                { code: 'redpacket', name: '红包雨', icon: 'fa-envelope', color: '#FF5722' },
                { code: 'kaimu', name: '开幕墙', icon: 'fa-play-circle', color: '#3F51B5' },
                { code: 'bimu', name: '闭幕墙', icon: 'fa-stop-circle', color: '#795548' }
            ];

            Api.getSwitchList(actId).then(function(res) {
                var data = res.data || {};
                var container = document.getElementById('switch-grid');
                var h = '<div class="switch-grid">';
                switchDefs.forEach(function(sw) {
                    var isOn = data[sw.code] !== undefined ? !!data[sw.code] : true;
                    h += '<div class="switch-card">' +
                        '<div class="sw-icon" style="background:' + sw.color + ';"><i class="fas ' + sw.icon + '"></i></div>' +
                        '<div class="sw-info"><div class="sw-name">' + sw.name + '</div><div class="sw-desc">' + sw.code + '</div></div>' +
                        '<div><input type="checkbox" lay-skin="switch" lay-text="开|关"' + (isOn ? ' checked' : '') +
                        ' lay-filter="switchToggle" data-code="' + sw.code + '"></div></div>';
                });
                h += '</div>';
                container.innerHTML = h;
                layui.form.render('checkbox');

                layui.form.on('switch(switchToggle)', function(data) {
                    var code = data.elem.getAttribute('data-code');
                    Api.toggleSwitch(actId, code).then(function() {
                        layui.layer.msg((data.elem.checked ? '已开启' : '已关闭') + '：' + code, { icon: 1 });
                    }).catch(function() {
                        data.elem.checked = !data.elem.checked;
                        layui.form.render('checkbox');
                    });
                });
            }).catch(function() {
                var c = document.getElementById('switch-grid');
                if (c) c.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>加载失败</p></div>';
            });
        },

        // ========== 基本设置 ==========
        renderBasic: function() {
            var html = '<div class="content-card"><div class="card-title">基本设置</div>' +
                '<form class="layui-form" lay-filter="basicSettings">' +
                '<div class="form-section"><div class="section-title">商户信息</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">商户名称</label><div class="layui-input-block"><input type="text" name="bs_name" class="layui-input"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">联系人</label><div class="layui-input-block"><input type="text" name="bs_contact" class="layui-input"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">联系电话</label><div class="layui-input-block"><input type="text" name="bs_phone" class="layui-input"></div></div>' +
                '</div>' +
                '<div class="form-section"><div class="section-title">修改密码</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">当前密码</label><div class="layui-input-block"><input type="password" name="bs_old_pwd" class="layui-input"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">新密码</label><div class="layui-input-block"><input type="password" name="bs_new_pwd" class="layui-input"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">确认密码</label><div class="layui-input-block"><input type="password" name="bs_confirm_pwd" class="layui-input"></div></div>' +
                '</div>' +
                '<div class="layui-form-item"><div class="layui-input-block">' +
                '<button type="button" class="btn btn-primary" onclick="SystemPage._saveBasic()"><i class="fas fa-save"></i> 保存信息</button> ' +
                '<button type="button" class="btn btn-warning" onclick="SystemPage._changePassword()"><i class="fas fa-key"></i> 修改密码</button>' +
                '</div></div></form></div>';

            Layout.setContent(html);
            layui.form.render(null, 'basicSettings');

            Api.getSettings().then(function(res) {
                var d = res.data || {};
                if (d.name) document.querySelector('[name="bs_name"]').value = d.name;
                if (d.contact_name || d.contact) document.querySelector('[name="bs_contact"]').value = d.contact_name || d.contact;
                if (d.phone) document.querySelector('[name="bs_phone"]').value = d.phone;
            }).catch(function() {});
        },

        _saveBasic: function() {
            var data = {
                name: document.querySelector('[name="bs_name"]').value,
                contact_name: document.querySelector('[name="bs_contact"]').value,
                phone: document.querySelector('[name="bs_phone"]').value
            };
            Api.updateBusiness(data).then(function() {
                layui.layer.msg('保存成功', { icon: 1 });
            });
        },

        _changePassword: function() {
            var oldPwd = document.querySelector('[name="bs_old_pwd"]').value;
            var newPwd = document.querySelector('[name="bs_new_pwd"]').value;
            var confirmPwd = document.querySelector('[name="bs_confirm_pwd"]').value;
            if (!oldPwd) return layui.layer.msg('请输入当前密码', { icon: 2 });
            if (!newPwd || newPwd.length < 6) return layui.layer.msg('新密码至少6位', { icon: 2 });
            if (newPwd !== confirmPwd) return layui.layer.msg('两次密码不一致', { icon: 2 });
            Api.changePassword({ old_password: oldPwd, new_password: newPwd }).then(function() {
                layui.layer.msg('密码修改成功', { icon: 1 });
                document.querySelector('[name="bs_old_pwd"]').value = '';
                document.querySelector('[name="bs_new_pwd"]').value = '';
                document.querySelector('[name="bs_confirm_pwd"]').value = '';
            });
        },

        // ========== 内容安全 ==========
        renderSecurity: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title">内容安全配置</div>' +
                '<form class="layui-form" lay-filter="securityConfig">' +
                '<div class="layui-form-item"><label class="layui-form-label">内容审核</label><div class="layui-input-block"><input type="checkbox" name="sec_audit" lay-skin="switch" lay-text="开启|关闭"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">敏感词过滤</label><div class="layui-input-block"><input type="checkbox" name="sec_keyword_filter" lay-skin="switch" lay-text="开启|关闭"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">全局禁言</label><div class="layui-input-block"><input type="checkbox" name="sec_global_mute" lay-skin="switch" lay-text="开启|关闭"></div></div>' +
                '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" onclick="SystemPage._saveSecurity()"><i class="fas fa-save"></i> 保存配置</button></div></div>' +
                '</form></div>';

            Layout.setContent(html);
            layui.form.render(null, 'securityConfig');

            Api.getSecurityConfig(actId).then(function(res) {
                var d = res.data || {};
                if (d.audit !== undefined) { document.querySelector('[name="sec_audit"]').checked = !!d.audit; }
                if (d.keyword_filter !== undefined) { document.querySelector('[name="sec_keyword_filter"]').checked = !!d.keyword_filter; }
                if (d.global_mute !== undefined) { document.querySelector('[name="sec_global_mute"]').checked = !!d.global_mute; }
                layui.form.render('checkbox', 'securityConfig');
            }).catch(function() {});
        },

        _saveSecurity: function() {
            var actId = App.getCurrentActivityId();
            var data = {
                audit: document.querySelector('[name="sec_audit"]').checked ? 1 : 0,
                keyword_filter: document.querySelector('[name="sec_keyword_filter"]').checked ? 1 : 0,
                global_mute: document.querySelector('[name="sec_global_mute"]').checked ? 1 : 0
            };
            Api.updateSecurityConfig(actId, data).then(function() {
                layui.layer.msg('保存成功', { icon: 1 });
            });
        }
    };

    global.SystemPage = SystemPage;
})(window);
