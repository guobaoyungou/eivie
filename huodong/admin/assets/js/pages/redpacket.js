/**
 * 红包管理页面模块
 * 包含：红包设置、红包轮次、中奖记录
 */
;(function(global) {
    'use strict';

    var RedpacketPage = {
        // ========== 红包设置 ==========
        renderConfig: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title">红包基本设置</div>' +
                '<form class="layui-form" lay-filter="redpacketConfig">' +
                '<div class="layui-form-item"><label class="layui-form-label">功能开关</label><div class="layui-input-block"><input type="checkbox" name="rp_enabled" lay-skin="switch" lay-text="开启|关闭"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">红包总金额</label><div class="layui-input-inline"><input type="number" name="rp_total_amount" class="layui-input" value="0" min="0" step="0.01"></div><div class="layui-form-mid">元</div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">单个最大</label><div class="layui-input-inline"><input type="number" name="rp_max_amount" class="layui-input" value="10" min="0.01" step="0.01"></div><div class="layui-form-mid">元</div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">单个最小</label><div class="layui-input-inline"><input type="number" name="rp_min_amount" class="layui-input" value="0.01" min="0.01" step="0.01"></div><div class="layui-form-mid">元</div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">红包个数</label><div class="layui-input-inline"><input type="number" name="rp_count" class="layui-input" value="100" min="1"></div><div class="layui-form-mid">个</div></div>' +
                '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" onclick="RedpacketPage._saveConfig()"><i class="fas fa-save"></i> 保存配置</button></div></div>' +
                '</form></div>';

            Layout.setContent(html);
            layui.form.render(null, 'redpacketConfig');

            Api.getRedpacketConfig(actId).then(function(res) {
                var d = res.data || {};
                if (d.enabled !== undefined) {
                    document.querySelector('[name="rp_enabled"]').checked = !!d.enabled;
                    layui.form.render('checkbox', 'redpacketConfig');
                }
                if (d.total_amount) document.querySelector('[name="rp_total_amount"]').value = d.total_amount;
                if (d.max_amount) document.querySelector('[name="rp_max_amount"]').value = d.max_amount;
                if (d.min_amount) document.querySelector('[name="rp_min_amount"]').value = d.min_amount;
                if (d.count) document.querySelector('[name="rp_count"]').value = d.count;
            }).catch(function() {});
        },

        _saveConfig: function() {
            var actId = App.getCurrentActivityId();
            var data = {
                enabled: document.querySelector('[name="rp_enabled"]').checked ? 1 : 0,
                total_amount: parseFloat(document.querySelector('[name="rp_total_amount"]').value) || 0,
                max_amount: parseFloat(document.querySelector('[name="rp_max_amount"]').value) || 10,
                min_amount: parseFloat(document.querySelector('[name="rp_min_amount"]').value) || 0.01,
                count: parseInt(document.querySelector('[name="rp_count"]').value) || 100
            };
            Api.updateRedpacketConfig(actId, data).then(function() {
                layui.layer.msg('保存成功', { icon: 1 });
            });
        },

        // ========== 红包轮次 ==========
        renderRounds: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title"><span>红包轮次</span><button class="btn btn-primary btn-sm" onclick="RedpacketPage._addRound()"><i class="fas fa-plus"></i> 添加轮次</button></div>' +
                '<table id="redpacket-rounds-table" lay-filter="rpRoundsTable"></table></div>';
            Layout.setContent(html);

            if (!document.getElementById('rpRoundsBar')) {
                var s = document.createElement('script');
                s.type = 'text/html'; s.id = 'rpRoundsBar';
                s.innerHTML = '<a class="layui-btn layui-btn-xs" lay-event="edit"><i class="fas fa-edit"></i> 编辑</a><a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del"><i class="fas fa-trash"></i> 删除</a>';
                document.body.appendChild(s);
            }

            layui.table.render({
                elem: '#redpacket-rounds-table',
                url: '/api/hd/redpacket/' + actId + '/rounds',
                headers: { 'Authorization': 'Bearer ' + Api.getToken() },
                parseData: function(res) {
                    var list = res.data ? (res.data.list || res.data) : [];
                    return { code: 0, msg: '', count: Array.isArray(list) ? list.length : 0, data: list };
                },
                cols: [[
                    { field: 'id', title: 'ID', width: 70 },
                    { field: 'title', title: '轮次名称', width: 180 },
                    { field: 'total_amount', title: '总金额(元)', width: 120 },
                    { field: 'count', title: '红包数', width: 100 },
                    { field: 'status', title: '状态', width: 100, templet: function(d) {
                        var map = { 0: '<span style="color:#999">未开始</span>', 1: '<span style="color:#1E88E5">进行中</span>', 2: '<span style="color:#43A047">已结束</span>' };
                        return map[d.status] || '未知';
                    }},
                    { title: '操作', width: 150, toolbar: '#rpRoundsBar' }
                ]],
                page: false,
                text: { none: '暂无红包轮次' }
            });

            layui.table.on('tool(rpRoundsTable)', function(obj) {
                if (obj.event === 'edit') RedpacketPage._editRound(actId, obj.data);
                else if (obj.event === 'del') {
                    layui.layer.confirm('确定删除该红包轮次？', { icon: 3 }, function(idx) {
                        Api.deleteRedpacketRound(actId, obj.data.id).then(function() {
                            layui.layer.close(idx);
                            obj.del();
                            layui.layer.msg('删除成功', { icon: 1 });
                        });
                    });
                }
            });
        },

        _addRound: function() {
            var actId = App.getCurrentActivityId();
            layui.layer.open({
                type: 1, title: '添加红包轮次', area: ['500px', '360px'],
                content: '<div style="padding:20px;"><form class="layui-form" lay-filter="addRpRound">' +
                    '<div class="layui-form-item"><label class="layui-form-label">轮次名称</label><div class="layui-input-block"><input type="text" name="title" class="layui-input" required></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">总金额</label><div class="layui-input-inline"><input type="number" name="total_amount" class="layui-input" value="100" min="1" step="0.01"></div><div class="layui-form-mid">元</div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">红包数</label><div class="layui-input-inline"><input type="number" name="count" class="layui-input" value="50" min="1"></div><div class="layui-form-mid">个</div></div>' +
                    '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" id="btn-save-rp-round"><i class="fas fa-save"></i> 保存</button></div></div>' +
                    '</form></div>',
                success: function(layero) {
                    layui.form.render(null, 'addRpRound');
                    layero.find('#btn-save-rp-round').on('click', function() {
                        var data = {
                            title: layero.find('[name="title"]').val(),
                            total_amount: parseFloat(layero.find('[name="total_amount"]').val()) || 100,
                            count: parseInt(layero.find('[name="count"]').val()) || 50
                        };
                        if (!data.title) return layui.layer.msg('请输入轮次名称', { icon: 2 });
                        Api.createRedpacketRound(actId, data).then(function() {
                            layui.layer.closeAll();
                            layui.table.reload('redpacket-rounds-table');
                            layui.layer.msg('添加成功', { icon: 1 });
                        });
                    });
                }
            });
        },

        _editRound: function(actId, rowData) {
            layui.layer.open({
                type: 1, title: '编辑红包轮次', area: ['500px', '360px'],
                content: '<div style="padding:20px;"><form class="layui-form" lay-filter="editRpRound">' +
                    '<div class="layui-form-item"><label class="layui-form-label">轮次名称</label><div class="layui-input-block"><input type="text" name="title" class="layui-input" value="' + (rowData.title || '') + '"></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">总金额</label><div class="layui-input-inline"><input type="number" name="total_amount" class="layui-input" value="' + (rowData.total_amount || 100) + '" min="1" step="0.01"></div><div class="layui-form-mid">元</div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">红包数</label><div class="layui-input-inline"><input type="number" name="count" class="layui-input" value="' + (rowData.count || 50) + '" min="1"></div><div class="layui-form-mid">个</div></div>' +
                    '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" id="btn-update-rp-round"><i class="fas fa-save"></i> 保存</button></div></div>' +
                    '</form></div>',
                success: function(layero) {
                    layui.form.render(null, 'editRpRound');
                    layero.find('#btn-update-rp-round').on('click', function() {
                        var data = {
                            title: layero.find('[name="title"]').val(),
                            total_amount: parseFloat(layero.find('[name="total_amount"]').val()) || 100,
                            count: parseInt(layero.find('[name="count"]').val()) || 50
                        };
                        Api.updateRedpacketRound(actId, rowData.id, data).then(function() {
                            layui.layer.closeAll();
                            layui.table.reload('redpacket-rounds-table');
                            layui.layer.msg('更新成功', { icon: 1 });
                        });
                    });
                }
            });
        },

        // ========== 中奖记录 ==========
        renderRecords: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title"><span>中奖记录</span></div>' +
                '<table id="redpacket-records-table" lay-filter="rpRecordsTable"></table></div>';
            Layout.setContent(html);

            layui.table.render({
                elem: '#redpacket-records-table',
                url: '/api/hd/redpacket/' + actId + '/records',
                headers: { 'Authorization': 'Bearer ' + Api.getToken() },
                parseData: function(res) {
                    var list = res.data ? (res.data.list || res.data) : [];
                    return { code: 0, msg: '', count: Array.isArray(list) ? list.length : 0, data: list };
                },
                cols: [[
                    { type: 'numbers', title: '序号', width: 70 },
                    { field: 'nickname', title: '昵称', width: 120 },
                    { field: 'phone', title: '手机号', width: 130 },
                    { field: 'amount', title: '金额(元)', width: 100 },
                    { field: 'round_title', title: '所属轮次', width: 150 },
                    { field: 'datetime', title: '领取时间', width: 170 },
                    { field: 'status', title: '状态', width: 100, templet: function(d) {
                        return d.status === 1 ? '<span style="color:#43A047">已领取</span>' : '<span style="color:#FB8C00">待领取</span>';
                    }}
                ]],
                page: true,
                limit: 20,
                text: { none: '暂无中奖记录' }
            });
        }
    };

    global.RedpacketPage = RedpacketPage;
})(window);
