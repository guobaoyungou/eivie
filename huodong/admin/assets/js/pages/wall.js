/**
 * 消息互动页面模块
 * 包含：上墙设置、弹幕设置、消息列表、发布公告
 */
;(function(global) {
    'use strict';

    var WallPage = {
        // ========== 上墙设置 ==========
        renderConfig: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title">上墙设置</div>' +
                '<form class="layui-form" lay-filter="wallConfig">' +
                '<div class="form-section"><div class="section-title">基本信息</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">活动名称</label><div class="layui-input-block"><input type="text" name="wall_title" class="layui-input" placeholder="显示在大屏上方的活动名称"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">版权信息</label><div class="layui-input-block"><input type="text" name="wall_copyright" class="layui-input" placeholder="底部版权文字"></div></div>' +
                '</div>' +
                '<div class="form-section"><div class="section-title">消息审核</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">需要审核</label><div class="layui-input-block"><input type="checkbox" name="wall_audit" lay-skin="switch" lay-text="是|否"></div></div>' +
                '</div>' +
                '<div class="form-section"><div class="section-title">显示控制</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">菜单颜色</label><div class="layui-input-inline"><input type="color" name="wall_menu_color" class="layui-input" value="#1E88E5" style="height:38px;padding:2px;"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">签到计数</label><div class="layui-input-block"><input type="checkbox" name="wall_sign_count" lay-skin="switch" lay-text="显示|隐藏" checked></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">名称显示</label><div class="layui-input-block">' +
                '<input type="radio" name="wall_name_style" value="1" title="昵称" checked>' +
                '<input type="radio" name="wall_name_style" value="2" title="姓名">' +
                '<input type="radio" name="wall_name_style" value="3" title="手机号">' +
                '</div></div></div>' +
                '<div class="form-section"><div class="section-title">二维码设置</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">二维码图片</label><div class="layui-input-block"><input type="text" name="wall_qrcode_img" class="layui-input" placeholder="自定义二维码图片URL"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">顶部文字</label><div class="layui-input-block"><input type="text" name="wall_qrcode_text" class="layui-input" placeholder="二维码上方显示的文字"></div></div>' +
                '</div>' +
                '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" onclick="WallPage._saveConfig()"><i class="fas fa-save"></i> 保存配置</button></div></div>' +
                '</form></div>';

            Layout.setContent(html);
            layui.form.render(null, 'wallConfig');

            Api.getWallConfig(actId).then(function(res) {
                var d = res.data || {};
                if (d.title) document.querySelector('[name="wall_title"]').value = d.title;
                if (d.copyright) document.querySelector('[name="wall_copyright"]').value = d.copyright;
                if (d.audit !== undefined) {
                    document.querySelector('[name="wall_audit"]').checked = !!d.audit;
                    layui.form.render('checkbox', 'wallConfig');
                }
                if (d.menu_color) document.querySelector('[name="wall_menu_color"]').value = d.menu_color;
            }).catch(function() {});
        },

        _saveConfig: function() {
            var actId = App.getCurrentActivityId();
            var data = {
                title: document.querySelector('[name="wall_title"]').value,
                copyright: document.querySelector('[name="wall_copyright"]').value,
                audit: document.querySelector('[name="wall_audit"]').checked ? 1 : 0,
                menu_color: document.querySelector('[name="wall_menu_color"]').value,
                sign_count: document.querySelector('[name="wall_sign_count"]').checked ? 1 : 0,
                name_style: document.querySelector('[name="wall_name_style"]:checked').value,
                qrcode_img: document.querySelector('[name="wall_qrcode_img"]').value,
                qrcode_text: document.querySelector('[name="wall_qrcode_text"]').value
            };
            Api.updateWallConfig(actId, data).then(function() {
                layui.layer.msg('保存成功', { icon: 1 });
            });
        },

        // ========== 弹幕设置 ==========
        renderDanmu: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title">弹幕设置</div>' +
                '<form class="layui-form" lay-filter="danmuConfig">' +
                '<div class="layui-form-item"><label class="layui-form-label">弹幕开关</label><div class="layui-input-block"><input type="checkbox" name="danmu_enabled" lay-skin="switch" lay-text="开启|关闭"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">弹幕速度</label><div class="layui-input-block"><select name="danmu_speed"><option value="slow">慢速</option><option value="normal">正常</option><option value="fast">快速</option></select></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">字体大小</label><div class="layui-input-inline"><input type="number" name="danmu_font_size" class="layui-input" value="24" min="12" max="48"></div><div class="layui-form-mid">px</div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">弹幕透明度</label><div class="layui-input-inline"><input type="number" name="danmu_opacity" class="layui-input" value="80" min="10" max="100" step="10"></div><div class="layui-form-mid">%</div></div>' +
                '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" onclick="WallPage._saveDanmu()"><i class="fas fa-save"></i> 保存配置</button></div></div>' +
                '</form></div>';

            Layout.setContent(html);
            layui.form.render(null, 'danmuConfig');

            Api.getDanmuConfig(actId).then(function(res) {
                var d = res.data || {};
                if (d.enabled !== undefined) {
                    document.querySelector('[name="danmu_enabled"]').checked = !!d.enabled;
                    layui.form.render('checkbox', 'danmuConfig');
                }
                if (d.speed) { document.querySelector('[name="danmu_speed"]').value = d.speed; layui.form.render('select', 'danmuConfig'); }
                if (d.font_size) document.querySelector('[name="danmu_font_size"]').value = d.font_size;
                if (d.opacity) document.querySelector('[name="danmu_opacity"]').value = d.opacity;
            }).catch(function() {});
        },

        _saveDanmu: function() {
            var actId = App.getCurrentActivityId();
            var data = {
                enabled: document.querySelector('[name="danmu_enabled"]').checked ? 1 : 0,
                speed: document.querySelector('[name="danmu_speed"]').value,
                font_size: parseInt(document.querySelector('[name="danmu_font_size"]').value) || 24,
                opacity: parseInt(document.querySelector('[name="danmu_opacity"]').value) || 80
            };
            Api.updateDanmuConfig(actId, data).then(function() {
                layui.layer.msg('保存成功', { icon: 1 });
            });
        },

        // ========== 消息列表 ==========
        renderMessages: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title"><span>消息列表</span></div>' +
                '<div class="toolbar"><div class="toolbar-left">' +
                '<select id="msg-status-filter" class="layui-input" style="width:140px;height:36px;"><option value="">全部</option><option value="0">待审核</option><option value="1">已通过</option><option value="2">已拒绝</option></select>' +
                '<button class="btn btn-default" onclick="WallPage._filterMessages()"><i class="fas fa-filter"></i> 筛选</button>' +
                '</div><div class="toolbar-right">' +
                '<button class="btn btn-success" onclick="WallPage._batchApprove()"><i class="fas fa-check-double"></i> 批量通过</button>' +
                '<button class="btn btn-primary" onclick="Api.exportMessages(' + actId + ')"><i class="fas fa-download"></i> 导出</button>' +
                '</div></div>' +
                '<table id="wall-messages-table" lay-filter="wallMessagesTable"></table></div>';

            Layout.setContent(html);

            if (!document.getElementById('wallMsgBar')) {
                var s = document.createElement('script');
                s.type = 'text/html'; s.id = 'wallMsgBar';
                s.innerHTML = '<a class="layui-btn layui-btn-xs" lay-event="approve"><i class="fas fa-check"></i></a>' +
                    '<a class="layui-btn layui-btn-warm layui-btn-xs" lay-event="top"><i class="fas fa-thumbtack"></i></a>' +
                    '<a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del"><i class="fas fa-trash"></i></a>';
                document.body.appendChild(s);
            }

            layui.table.render({
                elem: '#wall-messages-table',
                url: '/api/hd/wall/' + actId + '/messages',
                headers: { 'Authorization': 'Bearer ' + Api.getToken() },
                parseData: function(res) {
                    var list = res.data ? (res.data.list || res.data) : [];
                    return { code: 0, msg: '', count: res.data ? (res.data.total || list.length) : 0, data: list };
                },
                cols: [[
                    { type: 'checkbox', width: 50 },
                    { field: 'nickname', title: '昵称', width: 120 },
                    { field: 'content', title: '消息内容', minWidth: 200 },
                    { field: 'status', title: '状态', width: 90, templet: function(d) {
                        var map = { 0: '<span style="color:#FB8C00">待审核</span>', 1: '<span style="color:#43A047">已通过</span>', 2: '<span style="color:#E53935">已拒绝</span>' };
                        return map[d.status] || '未知';
                    }},
                    { field: 'is_top', title: '置顶', width: 70, templet: function(d) { return d.is_top ? '<i class="fas fa-thumbtack" style="color:#1E88E5;"></i>' : ''; } },
                    { field: 'datetime', title: '时间', width: 160 },
                    { title: '操作', width: 130, toolbar: '#wallMsgBar' }
                ]],
                page: true,
                limit: 20,
                text: { none: '暂无消息' }
            });

            layui.table.on('tool(wallMessagesTable)', function(obj) {
                if (obj.event === 'approve') {
                    Api.approveMessage(actId, obj.data.id).then(function() {
                        layui.layer.msg('审核通过', { icon: 1 });
                        layui.table.reload('wall-messages-table');
                    });
                } else if (obj.event === 'top') {
                    Api.toggleMessageTop(actId, obj.data.id).then(function() {
                        layui.layer.msg('操作成功', { icon: 1 });
                        layui.table.reload('wall-messages-table');
                    });
                } else if (obj.event === 'del') {
                    layui.layer.confirm('确定删除该消息？', { icon: 3 }, function(idx) {
                        Api.deleteMessage(actId, obj.data.id).then(function() {
                            layui.layer.close(idx);
                            obj.del();
                        });
                    });
                }
            });
        },

        _filterMessages: function() {
            var status = document.getElementById('msg-status-filter').value;
            layui.table.reload('wall-messages-table', { where: { status: status }, page: { curr: 1 } });
        },

        _batchApprove: function() {
            var actId = App.getCurrentActivityId();
            layui.layer.confirm('确定批量通过所有待审核消息？', { icon: 3 }, function(idx) {
                Api.batchApproveMessages(actId).then(function() {
                    layui.layer.close(idx);
                    layui.layer.msg('批量审核成功', { icon: 1 });
                    layui.table.reload('wall-messages-table');
                });
            });
        },

        // ========== 发布公告 ==========
        renderNotice: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title">发布公告</div>' +
                '<form class="layui-form" lay-filter="noticeForm">' +
                '<div class="layui-form-item"><label class="layui-form-label">公告内容</label><div class="layui-input-block"><textarea name="notice_content" class="layui-textarea" rows="5" placeholder="输入要在大屏上滚动显示的公告内容"></textarea></div></div>' +
                '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" onclick="WallPage._publishNotice()"><i class="fas fa-bullhorn"></i> 发布公告</button></div></div>' +
                '</form></div>';
            Layout.setContent(html);
            layui.form.render(null, 'noticeForm');
        },

        _publishNotice: function() {
            var actId = App.getCurrentActivityId();
            var content = document.querySelector('[name="notice_content"]').value;
            if (!content.trim()) return layui.layer.msg('请输入公告内容', { icon: 2 });
            Api.publishNotice(actId, { content: content }).then(function() {
                layui.layer.msg('公告已发布', { icon: 1 });
                document.querySelector('[name="notice_content"]').value = '';
            });
        }
    };

    global.WallPage = WallPage;
})(window);
