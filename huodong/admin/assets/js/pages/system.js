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
                '<div class="tab-item active" onclick="SystemPage._switchThemeTab(this,\'kaimu-panel\')">开幕墙</div>' +
                '<div class="tab-item" onclick="SystemPage._switchThemeTab(this,\'bimu-panel\')">闭幕墙</div></div>' +
                '<div id="kaimu-panel">' +
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
            document.getElementById('kaimu-panel').style.display = panelId === 'kaimu-panel' ? '' : 'none';
            document.getElementById('bimu-panel').style.display = panelId === 'bimu-panel' ? '' : 'none';
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

        // ========== 背景图 ==========
        renderBackground: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title"><span>背景图管理</span>' +
                '<button class="btn btn-primary btn-sm" onclick="SystemPage._addBackground()"><i class="fas fa-plus"></i> 添加背景</button></div>' +
                '<div id="bg-list" class="loading-state"><i class="fas fa-spinner"></i> 加载中...</div></div>';
            Layout.setContent(html);
            this._loadBackgrounds(actId);
        },

        _loadBackgrounds: function(actId) {
            Api.getBackgrounds(actId).then(function(res) {
                var list = res.data || [];
                if (!Array.isArray(list)) list = [];
                var container = document.getElementById('bg-list');
                if (list.length === 0) {
                    container.innerHTML = '<div class="empty-state"><i class="fas fa-image"></i><p>暂无背景图</p></div>';
                    return;
                }
                var h = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;">';
                list.forEach(function(bg) {
                    var src = bg.url || bg.image || '';
                    h += '<div style="position:relative;border-radius:8px;overflow:hidden;aspect-ratio:16/9;background:#f5f5f5;box-shadow:0 2px 8px rgba(0,0,0,0.1);">' +
                        '<img src="' + src + '" style="width:100%;height:100%;object-fit:cover;">' +
                        '<div style="position:absolute;bottom:0;left:0;right:0;padding:8px;background:linear-gradient(transparent,rgba(0,0,0,0.6));color:#fff;font-size:12px;">' + (bg.name || '背景图') + '</div>' +
                        '<button onclick="SystemPage._deleteBackground(' + actId + ',' + bg.id + ')" style="position:absolute;top:6px;right:6px;background:rgba(229,57,53,0.8);color:#fff;border:none;border-radius:50%;width:28px;height:28px;cursor:pointer;"><i class="fas fa-times"></i></button>' +
                        '</div>';
                });
                h += '</div>';
                container.innerHTML = h;
            }).catch(function() {
                var c = document.getElementById('bg-list');
                if (c) c.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>加载失败</p></div>';
            });
        },

        _addBackground: function() {
            var actId = App.getCurrentActivityId();
            layui.layer.open({
                type: 1, title: '添加背景图', area: ['450px', '250px'],
                content: '<div style="padding:20px;">' +
                    '<div class="layui-form-item"><label class="layui-form-label">图片URL</label><div class="layui-input-block"><input type="text" id="bg-url" class="layui-input" placeholder="背景图片地址"></div></div>' +
                    '<div class="layui-form-item"><div class="layui-input-block"><button class="btn btn-primary" id="btn-add-bg"><i class="fas fa-save"></i> 添加</button></div></div></div>',
                success: function(layero) {
                    layero.find('#btn-add-bg').on('click', function() {
                        var url = layero.find('#bg-url').val();
                        if (!url) return layui.layer.msg('请输入图片地址', { icon: 2 });
                        Api.addBackground(actId, { url: url }).then(function() {
                            layui.layer.closeAll();
                            layui.layer.msg('添加成功', { icon: 1 });
                            SystemPage._loadBackgrounds(actId);
                        });
                    });
                }
            });
        },

        _deleteBackground: function(actId, bgId) {
            layui.layer.confirm('确定删除该背景图？', { icon: 3 }, function(idx) {
                Api.deleteBackground(actId, bgId).then(function() {
                    layui.layer.close(idx);
                    SystemPage._loadBackgrounds(actId);
                });
            });
        },

        // ========== 背景音乐 ==========
        renderMusic: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title"><span>背景音乐管理</span>' +
                '<button class="btn btn-primary btn-sm" onclick="SystemPage._addMusic()"><i class="fas fa-plus"></i> 添加音乐</button></div>' +
                '<div id="music-list" class="loading-state"><i class="fas fa-spinner"></i> 加载中...</div></div>';
            Layout.setContent(html);

            Api.getMusics(actId).then(function(res) {
                var list = res.data || [];
                if (!Array.isArray(list)) list = [];
                var container = document.getElementById('music-list');
                if (list.length === 0) {
                    container.innerHTML = '<div class="empty-state"><i class="fas fa-music"></i><p>暂无背景音乐</p></div>';
                    return;
                }
                var h = '<div class="switch-grid">';
                list.forEach(function(m) {
                    h += '<div class="switch-card"><div class="sw-icon" style="background:#FB8C00;"><i class="fas fa-music"></i></div>' +
                        '<div class="sw-info" style="flex:1;"><div class="sw-name">' + (m.name || m.title || '音乐') + '</div>' +
                        '<div class="sw-desc">' + (m.url || '') + '</div></div>' +
                        '<button onclick="SystemPage._deleteMusic(' + actId + ',' + m.id + ')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>' +
                        '</div>';
                });
                h += '</div>';
                container.innerHTML = h;
            }).catch(function() {
                var c = document.getElementById('music-list');
                if (c) c.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>加载失败</p></div>';
            });
        },

        _addMusic: function() {
            var actId = App.getCurrentActivityId();
            layui.layer.open({
                type: 1, title: '添加背景音乐', area: ['450px', '280px'],
                content: '<div style="padding:20px;">' +
                    '<div class="layui-form-item"><label class="layui-form-label">音乐名称</label><div class="layui-input-block"><input type="text" id="music-name" class="layui-input" placeholder="音乐名称"></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">音乐URL</label><div class="layui-input-block"><input type="text" id="music-url" class="layui-input" placeholder="音乐文件地址"></div></div>' +
                    '<div class="layui-form-item"><div class="layui-input-block"><button class="btn btn-primary" id="btn-add-music"><i class="fas fa-save"></i> 添加</button></div></div></div>',
                success: function(layero) {
                    layero.find('#btn-add-music').on('click', function() {
                        var name = layero.find('#music-name').val();
                        var url = layero.find('#music-url').val();
                        if (!url) return layui.layer.msg('请输入音乐地址', { icon: 2 });
                        Api.addMusic(actId, { name: name, url: url }).then(function() {
                            layui.layer.closeAll();
                            layui.layer.msg('添加成功', { icon: 1 });
                            SystemPage.renderMusic();
                        });
                    });
                }
            });
        },

        _deleteMusic: function(actId, musicId) {
            layui.layer.confirm('确定删除该音乐？', { icon: 3 }, function(idx) {
                Api.deleteMusic(actId, musicId).then(function() {
                    layui.layer.close(idx);
                    SystemPage.renderMusic();
                });
            });
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
