/**
 * 互动抽奖页面模块
 * 包含：抽奖轮次、奖品设置、中奖名单、内定名单、抽奖主题、手机端抽奖、导入抽奖
 */
;(function(global) {
    'use strict';

    var LotteryPage = {
        // ========== 抽奖轮次 ==========
        renderRounds: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title"><span>抽奖轮次</span><button class="btn btn-primary btn-sm" onclick="LotteryPage._addRound()"><i class="fas fa-plus"></i> 添加轮次</button></div>' +
                '<table id="lottery-rounds-table" lay-filter="lotteryRoundsTable"></table></div>';
            Layout.setContent(html);
            this._initRoundsTable(actId);
        },

        _initRoundsTable: function(actId) {
            if (!document.getElementById('lotteryRoundsBar')) {
                var s = document.createElement('script');
                s.type = 'text/html'; s.id = 'lotteryRoundsBar';
                s.innerHTML = '<a class="layui-btn layui-btn-xs" lay-event="edit"><i class="fas fa-edit"></i> 编辑</a>' +
                    '<a class="layui-btn layui-btn-warm layui-btn-xs" lay-event="reset"><i class="fas fa-redo"></i> 重置</a>' +
                    '<a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del"><i class="fas fa-trash"></i> 删除</a>';
                document.body.appendChild(s);
            }

            layui.table.render({
                elem: '#lottery-rounds-table',
                url: '/api/hd/lottery/' + actId + '/rounds',
                headers: { 'Authorization': 'Bearer ' + Api.getToken() },
                parseData: function(res) {
                    var list = res.data ? (res.data.list || res.data) : [];
                    return { code: 0, msg: '', count: Array.isArray(list) ? list.length : 0, data: list };
                },
                cols: [[
                    { field: 'id', title: 'ID', width: 70 },
                    { field: 'title', title: '轮次名称', width: 180 },
                    { field: 'show_type', title: '展示类型', width: 120 },
                    { field: 'win_again', title: '重复中奖', width: 100, templet: function(d) { return d.win_again ? '<span style="color:#43A047">允许</span>' : '<span style="color:#999">不允许</span>'; } },
                    { field: 'status', title: '状态', width: 100, templet: function(d) {
                        var map = { 0: '<span style="color:#999">未开始</span>', 1: '<span style="color:#1E88E5">进行中</span>', 2: '<span style="color:#43A047">已结束</span>' };
                        return map[d.status] || '未知';
                    }},
                    { title: '操作', width: 200, toolbar: '#lotteryRoundsBar' }
                ]],
                page: false,
                text: { none: '暂无抽奖轮次' }
            });

            layui.table.on('tool(lotteryRoundsTable)', function(obj) {
                if (obj.event === 'edit') LotteryPage._editRound(actId, obj.data);
                else if (obj.event === 'reset') {
                    layui.layer.confirm('确定重置该轮次？所有中奖记录将清空！', { icon: 3 }, function(idx) {
                        Api.resetLotteryRound(actId, obj.data.id).then(function() {
                            layui.layer.close(idx);
                            layui.table.reload('lottery-rounds-table');
                            layui.layer.msg('重置成功', { icon: 1 });
                        });
                    });
                } else if (obj.event === 'del') {
                    layui.layer.confirm('确定删除该轮次？', { icon: 3 }, function(idx) {
                        Api.deleteLotteryRound(actId, obj.data.id).then(function() {
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
                type: 1, title: '添加抽奖轮次', area: ['500px', '380px'],
                content: '<div style="padding:20px;"><form class="layui-form" lay-filter="addRound">' +
                    '<div class="layui-form-item"><label class="layui-form-label">轮次名称</label><div class="layui-input-block"><input type="text" name="title" class="layui-input" required></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">展示类型</label><div class="layui-input-block"><select name="show_type"><option value="normal">普通抽奖</option><option value="3d">3D抽奖</option><option value="egg">砸金蛋</option><option value="box">抽奖箱</option></select></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">重复中奖</label><div class="layui-input-block"><input type="checkbox" name="win_again" lay-skin="switch" lay-text="允许|不允许"></div></div>' +
                    '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" id="btn-save-round"><i class="fas fa-save"></i> 保存</button></div></div>' +
                    '</form></div>',
                success: function(layero) {
                    layui.form.render(null, 'addRound');
                    layero.find('#btn-save-round').on('click', function() {
                        var data = {
                            title: layero.find('[name="title"]').val(),
                            show_type: layero.find('[name="show_type"]').val(),
                            win_again: layero.find('[name="win_again"]').is(':checked') ? 1 : 0
                        };
                        if (!data.title) return layui.layer.msg('请输入轮次名称', { icon: 2 });
                        Api.createLotteryRound(actId, data).then(function() {
                            layui.layer.closeAll();
                            layui.table.reload('lottery-rounds-table');
                            layui.layer.msg('添加成功', { icon: 1 });
                        });
                    });
                }
            });
        },

        _editRound: function(actId, rowData) {
            layui.layer.open({
                type: 1, title: '编辑抽奖轮次', area: ['500px', '380px'],
                content: '<div style="padding:20px;"><form class="layui-form" lay-filter="editRound">' +
                    '<div class="layui-form-item"><label class="layui-form-label">轮次名称</label><div class="layui-input-block"><input type="text" name="title" class="layui-input" value="' + (rowData.title || '') + '"></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">展示类型</label><div class="layui-input-block"><select name="show_type">' +
                    '<option value="normal"' + (rowData.show_type === 'normal' ? ' selected' : '') + '>普通抽奖</option>' +
                    '<option value="3d"' + (rowData.show_type === '3d' ? ' selected' : '') + '>3D抽奖</option>' +
                    '<option value="egg"' + (rowData.show_type === 'egg' ? ' selected' : '') + '>砸金蛋</option>' +
                    '<option value="box"' + (rowData.show_type === 'box' ? ' selected' : '') + '>抽奖箱</option>' +
                    '</select></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">重复中奖</label><div class="layui-input-block"><input type="checkbox" name="win_again" lay-skin="switch" lay-text="允许|不允许"' + (rowData.win_again ? ' checked' : '') + '></div></div>' +
                    '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" id="btn-update-round"><i class="fas fa-save"></i> 保存</button></div></div>' +
                    '</form></div>',
                success: function(layero) {
                    layui.form.render(null, 'editRound');
                    layero.find('#btn-update-round').on('click', function() {
                        var data = {
                            title: layero.find('[name="title"]').val(),
                            show_type: layero.find('[name="show_type"]').val(),
                            win_again: layero.find('[name="win_again"]').is(':checked') ? 1 : 0
                        };
                        Api.updateLotteryRound(actId, rowData.id, data).then(function() {
                            layui.layer.closeAll();
                            layui.table.reload('lottery-rounds-table');
                            layui.layer.msg('更新成功', { icon: 1 });
                        });
                    });
                }
            });
        },

        // ========== 奖品设置 ==========
        renderPrizes: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title"><span>奖品设置</span><button class="btn btn-primary btn-sm" onclick="LotteryPage._addPrize()"><i class="fas fa-plus"></i> 添加奖品</button></div>' +
                '<table id="lottery-prizes-table" lay-filter="lotteryPrizesTable"></table></div>';
            Layout.setContent(html);
            this._initPrizesTable(actId);
        },

        _initPrizesTable: function(actId) {
            if (!document.getElementById('lotteryPrizesBar')) {
                var s = document.createElement('script');
                s.type = 'text/html'; s.id = 'lotteryPrizesBar';
                s.innerHTML = '<a class="layui-btn layui-btn-xs" lay-event="edit"><i class="fas fa-edit"></i> 编辑</a><a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del"><i class="fas fa-trash"></i> 删除</a>';
                document.body.appendChild(s);
            }

            layui.table.render({
                elem: '#lottery-prizes-table',
                url: '/api/hd/lottery/' + actId + '/prizes',
                headers: { 'Authorization': 'Bearer ' + Api.getToken() },
                parseData: function(res) {
                    var list = res.data ? (res.data.list || res.data) : [];
                    return { code: 0, msg: '', count: Array.isArray(list) ? list.length : 0, data: list };
                },
                cols: [[
                    { field: 'imageid', title: '图片', width: 80, templet: function(d) {
                        var img = d.imageid || d.image;
                        return img ? '<img class="img-preview" src="' + img + '">' : '<i class="fas fa-image" style="font-size:24px;color:#ddd;"></i>';
                    }},
                    { field: 'prizename', title: '奖品名称', width: 160 },
                    { field: 'type', title: '奖品级别', width: 100, templet: function(d) {
                        var map = { 1: '一等奖', 2: '二等奖', 3: '三等奖', 4: '四等奖', 5: '五等奖' };
                        return map[d.type] || d.type || '-';
                    }},
                    { field: 'num', title: '数量', width: 80 },
                    { field: 'leftnum', title: '剩余', width: 80 },
                    { field: 'draw_count', title: '每次抽取', width: 100 },
                    { title: '操作', width: 150, toolbar: '#lotteryPrizesBar' }
                ]],
                page: false,
                text: { none: '暂无奖品' }
            });

            layui.table.on('tool(lotteryPrizesTable)', function(obj) {
                if (obj.event === 'edit') LotteryPage._editPrize(actId, obj.data);
                else if (obj.event === 'del') {
                    layui.layer.confirm('确定删除该奖品？', { icon: 3 }, function(idx) {
                        Api.deleteLotteryPrize(actId, obj.data.id).then(function() {
                            layui.layer.close(idx);
                            obj.del();
                            layui.layer.msg('删除成功', { icon: 1 });
                        });
                    });
                }
            });
        },

        _addPrize: function() {
            var actId = App.getCurrentActivityId();
            layui.layer.open({
                type: 1, title: '添加奖品', area: ['500px', '480px'],
                content: '<div style="padding:20px;"><form class="layui-form" lay-filter="addPrize">' +
                    '<div class="layui-form-item"><label class="layui-form-label">奖品名称</label><div class="layui-input-block"><input type="text" name="prizename" class="layui-input" required></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">奖品级别</label><div class="layui-input-block"><select name="type"><option value="1">一等奖</option><option value="2">二等奖</option><option value="3">三等奖</option><option value="4">四等奖</option><option value="5">五等奖</option></select></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">数量</label><div class="layui-input-inline"><input type="number" name="num" class="layui-input" value="1" min="1"></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">每次抽取</label><div class="layui-input-inline"><input type="number" name="draw_count" class="layui-input" value="1" min="1"></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">奖品图片</label><div class="layui-input-block"><input type="text" name="imageid" class="layui-input" placeholder="图片URL"></div></div>' +
                    '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" id="btn-save-prize"><i class="fas fa-save"></i> 保存</button></div></div>' +
                    '</form></div>',
                success: function(layero) {
                    layui.form.render(null, 'addPrize');
                    layero.find('#btn-save-prize').on('click', function() {
                        var data = {
                            prizename: layero.find('[name="prizename"]').val(),
                            type: layero.find('[name="type"]').val(),
                            num: parseInt(layero.find('[name="num"]').val()) || 1,
                            draw_count: parseInt(layero.find('[name="draw_count"]').val()) || 1,
                            imageid: layero.find('[name="imageid"]').val()
                        };
                        if (!data.prizename) return layui.layer.msg('请输入奖品名称', { icon: 2 });
                        Api.createLotteryPrize(actId, data).then(function() {
                            layui.layer.closeAll();
                            layui.table.reload('lottery-prizes-table');
                            layui.layer.msg('添加成功', { icon: 1 });
                        });
                    });
                }
            });
        },

        _editPrize: function(actId, rowData) {
            layui.layer.open({
                type: 1, title: '编辑奖品', area: ['500px', '480px'],
                content: '<div style="padding:20px;"><form class="layui-form" lay-filter="editPrize">' +
                    '<div class="layui-form-item"><label class="layui-form-label">奖品名称</label><div class="layui-input-block"><input type="text" name="prizename" class="layui-input" value="' + (rowData.prizename || '') + '"></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">奖品级别</label><div class="layui-input-block"><select name="type">' +
                    [1,2,3,4,5].map(function(v) { return '<option value="' + v + '"' + (String(rowData.type) === String(v) ? ' selected' : '') + '>' + ['','一等奖','二等奖','三等奖','四等奖','五等奖'][v] + '</option>'; }).join('') +
                    '</select></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">数量</label><div class="layui-input-inline"><input type="number" name="num" class="layui-input" value="' + (rowData.num || 1) + '" min="1"></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">每次抽取</label><div class="layui-input-inline"><input type="number" name="draw_count" class="layui-input" value="' + (rowData.draw_count || 1) + '" min="1"></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">奖品图片</label><div class="layui-input-block"><input type="text" name="imageid" class="layui-input" value="' + (rowData.imageid || rowData.image || '') + '"></div></div>' +
                    '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" id="btn-update-prize"><i class="fas fa-save"></i> 保存</button></div></div>' +
                    '</form></div>',
                success: function(layero) {
                    layui.form.render(null, 'editPrize');
                    layero.find('#btn-update-prize').on('click', function() {
                        var data = {
                            prizename: layero.find('[name="prizename"]').val(),
                            type: layero.find('[name="type"]').val(),
                            num: parseInt(layero.find('[name="num"]').val()) || 1,
                            draw_count: parseInt(layero.find('[name="draw_count"]').val()) || 1,
                            imageid: layero.find('[name="imageid"]').val()
                        };
                        Api.updateLotteryPrize(actId, rowData.id, data).then(function() {
                            layui.layer.closeAll();
                            layui.table.reload('lottery-prizes-table');
                            layui.layer.msg('更新成功', { icon: 1 });
                        });
                    });
                }
            });
        },

        // ========== 中奖名单 ==========
        renderWinners: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>中奖名单</span>' +
                '<div><button class="btn btn-primary btn-sm" onclick="Api.exportLottery(' + actId + ')"><i class="fas fa-download"></i> 导出</button>' +
                '<button class="btn btn-danger btn-sm" style="margin-left:8px;" onclick="LotteryPage._clearWinners()"><i class="fas fa-trash"></i> 清空</button></div></div>' +
                '<table id="lottery-winners-table" lay-filter="lotteryWinnersTable"></table></div>';
            Layout.setContent(html);
            this._initWinnersTable(actId);
        },

        _initWinnersTable: function(actId) {
            if (!document.getElementById('lotteryWinnersBar')) {
                var s = document.createElement('script');
                s.type = 'text/html'; s.id = 'lotteryWinnersBar';
                s.innerHTML = '<a class="layui-btn layui-btn-xs" lay-event="give"><i class="fas fa-gift"></i> 发奖</a>' +
                    '<a class="layui-btn layui-btn-warm layui-btn-xs" lay-event="cancel"><i class="fas fa-undo"></i> 取消</a>' +
                    '<a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del"><i class="fas fa-trash"></i> 删除</a>';
                document.body.appendChild(s);
            }

            layui.table.render({
                elem: '#lottery-winners-table',
                url: '/api/hd/lottery/' + actId + '/winners',
                headers: { 'Hd-Token': Api.getToken() },
                parseData: function(res) {
                    var list = res.data ? (res.data.list || res.data) : [];
                    return { code: 0, msg: '', count: res.data ? (res.data.count || list.length) : 0, data: list };
                },
                cols: [[
                    { field: 'id', title: 'ID', width: 60 },
                    { field: 'avatar', title: '头像', width: 60, templet: function(d) {
                        return d.avatar ? '<img src="' + d.avatar + '" style="width:32px;height:32px;border-radius:50%;">' : '<i class="fas fa-user-circle" style="font-size:24px;color:#ddd;"></i>';
                    }},
                    { field: 'nickname', title: '昵称', width: 120 },
                    { field: 'phone', title: '手机号', width: 120 },
                    { field: 'prize_name', title: '奖品', width: 120 },
                    { field: 'round_name', title: '轮次', width: 120 },
                    { field: 'show_type', title: '类型', width: 90, templet: function(d) {
                        var map = { normal: '普通', '3d': '3D', egg: '砸金蛋', box: '抽奖箱' };
                        return map[d.show_type] || d.show_type || '-';
                    }},
                    { field: 'status', title: '状态', width: 80, templet: function(d) {
                        return d.status == 3 ? '<span style="color:#43A047">已发奖</span>' : '<span style="color:#FF9800">未发奖</span>';
                    }},
                    { field: 'win_time', title: '中奖时间', width: 160, templet: function(d) {
                        if (!d.win_time) return '-';
                        var t = new Date(d.win_time * 1000);
                        var pad = function(n) { return n < 10 ? '0' + n : n; };
                        return t.getFullYear() + '-' + pad(t.getMonth()+1) + '-' + pad(t.getDate()) + ' ' + pad(t.getHours()) + ':' + pad(t.getMinutes());
                    }},
                    { title: '操作', width: 180, toolbar: '#lotteryWinnersBar' }
                ]],
                page: true,
                limit: 50,
                text: { none: '暂无中奖记录' }
            });

            layui.table.on('tool(lotteryWinnersTable)', function(obj) {
                if (obj.event === 'give') {
                    Api.giveLotteryPrize(actId, obj.data.id).then(function() {
                        layui.table.reload('lottery-winners-table');
                        layui.layer.msg('发奖成功', { icon: 1 });
                    });
                } else if (obj.event === 'cancel') {
                    Api.cancelLotteryPrize(actId, obj.data.id).then(function() {
                        layui.table.reload('lottery-winners-table');
                        layui.layer.msg('已取消发奖', { icon: 1 });
                    });
                } else if (obj.event === 'del') {
                    layui.layer.confirm('确定删除该中奖记录？', { icon: 3 }, function(idx) {
                        Api.deleteLotteryWinner(actId, obj.data.id).then(function() {
                            layui.layer.close(idx);
                            obj.del();
                            layui.layer.msg('删除成功', { icon: 1 });
                        });
                    });
                }
            });
        },

        _clearWinners: function() {
            var actId = App.getCurrentActivityId();
            layui.layer.confirm('确定清空所有中奖记录？此操作不可恢复！', { icon: 3 }, function(idx) {
                Api.clearLotteryWinners(actId).then(function() {
                    layui.layer.close(idx);
                    layui.table.reload('lottery-winners-table');
                    layui.layer.msg('已清空', { icon: 1 });
                });
            });
        },

        // ========== 内定名单 ==========
        renderDesignated: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>内定名单</span>' +
                '<button class="btn btn-primary btn-sm" onclick="LotteryPage._addDesignated()"><i class="fas fa-plus"></i> 添加内定</button></div>' +
                '<table id="lottery-designated-table" lay-filter="lotteryDesignatedTable"></table></div>';
            Layout.setContent(html);
            this._initDesignatedTable(actId);
        },

        _initDesignatedTable: function(actId) {
            if (!document.getElementById('lotteryDesignatedBar')) {
                var s = document.createElement('script');
                s.type = 'text/html'; s.id = 'lotteryDesignatedBar';
                s.innerHTML = '<a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="cancel"><i class="fas fa-times"></i> 取消内定</a>';
                document.body.appendChild(s);
            }

            layui.table.render({
                elem: '#lottery-designated-table',
                url: '/api/hd/lottery/' + actId + '/designated',
                headers: { 'Hd-Token': Api.getToken() },
                parseData: function(res) {
                    var list = res.data ? (res.data.list || res.data) : [];
                    return { code: 0, msg: '', count: res.data ? (res.data.count || list.length) : 0, data: list };
                },
                cols: [[
                    { field: 'id', title: 'ID', width: 60 },
                    { field: 'avatar', title: '头像', width: 60, templet: function(d) {
                        return d.avatar ? '<img src="' + d.avatar + '" style="width:32px;height:32px;border-radius:50%;">' : '<i class="fas fa-user-circle" style="font-size:24px;color:#ddd;"></i>';
                    }},
                    { field: 'nickname', title: '昵称', width: 140 },
                    { field: 'phone', title: '手机号', width: 130 },
                    { field: 'prize_name', title: '关联奖品', width: 140 },
                    { field: 'designated', title: '内定类型', width: 100, templet: function(d) {
                        return d.designated == 2 ? '<span style="color:#E53935">必中</span>' : '<span style="color:#999">不中</span>';
                    }},
                    { title: '操作', width: 120, toolbar: '#lotteryDesignatedBar' }
                ]],
                page: true,
                limit: 50,
                text: { none: '暂无内定记录' }
            });

            layui.table.on('tool(lotteryDesignatedTable)', function(obj) {
                if (obj.event === 'cancel') {
                    layui.layer.confirm('确定取消该内定？', { icon: 3 }, function(idx) {
                        Api.cancelLotteryDesignated(actId, obj.data.id).then(function() {
                            layui.layer.close(idx);
                            obj.del();
                            layui.layer.msg('已取消内定', { icon: 1 });
                        });
                    });
                }
            });
        },

        _addDesignated: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return;

            // 先加载奖品列表用于下拉
            Api.getLotteryPrizes(actId).then(function(res) {
                var prizes = (res.data && res.data.list) ? res.data.list : [];
                var prizeOpts = '<option value="0">不绑定奖品</option>';
                prizes.forEach(function(p) {
                    prizeOpts += '<option value="' + p.id + '">' + (p.prizename || p.name || '未命名') + '</option>';
                });

                layui.layer.open({
                    type: 1, title: '添加内定', area: ['520px', '460px'],
                    content: '<div style="padding:20px;"><form class="layui-form" lay-filter="addDesignated">' +
                        '<div class="layui-form-item"><label class="layui-form-label">搜索用户</label><div class="layui-input-block">' +
                        '<input type="text" id="designated-search" class="layui-input" placeholder="输入昵称/手机号搜索">' +
                        '<div id="designated-user-list" style="max-height:120px;overflow-y:auto;border:1px solid #e6e6e6;margin-top:5px;display:none;"></div>' +
                        '<input type="hidden" id="designated-user-id" value="0">' +
                        '</div></div>' +
                        '<div class="layui-form-item"><label class="layui-form-label">内定类型</label><div class="layui-input-block">' +
                        '<select name="designated"><option value="2">必中</option><option value="3">不中</option></select></div></div>' +
                        '<div class="layui-form-item"><label class="layui-form-label">关联奖品</label><div class="layui-input-block">' +
                        '<select name="prize_id">' + prizeOpts + '</select></div></div>' +
                        '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" id="btn-save-designated"><i class="fas fa-save"></i> 保存</button></div></div>' +
                        '</form></div>',
                    success: function(layero) {
                        layui.form.render(null, 'addDesignated');
                        var searchTimer = null;
                        layero.find('#designated-search').on('input', function() {
                            var kw = this.value.trim();
                            clearTimeout(searchTimer);
                            if (!kw) { layero.find('#designated-user-list').hide(); return; }
                            searchTimer = setTimeout(function() {
                                Api.searchLotteryUsers(actId, kw).then(function(res) {
                                    var users = (res.data && res.data.list) ? res.data.list : [];
                                    var listEl = layero.find('#designated-user-list');
                                    if (users.length === 0) { listEl.html('<div style="padding:8px;color:#999;">未找到用户</div>').show(); return; }
                                    var h = '';
                                    users.forEach(function(u) {
                                        h += '<div class="designated-user-item" data-id="' + u.id + '" style="padding:6px 10px;cursor:pointer;border-bottom:1px solid #f0f0f0;">' +
                                            '<strong>' + (u.nickname || u.signname || '-') + '</strong> <span style="color:#999;">' + (u.phone || '') + '</span></div>';
                                    });
                                    listEl.html(h).show();
                                    listEl.find('.designated-user-item').on('click', function() {
                                        var uid = $(this).data('id');
                                        var name = $(this).find('strong').text();
                                        layero.find('#designated-user-id').val(uid);
                                        layero.find('#designated-search').val(name);
                                        listEl.hide();
                                    });
                                });
                            }, 300);
                        });

                        layero.find('#btn-save-designated').on('click', function() {
                            var userId = parseInt(layero.find('#designated-user-id').val()) || 0;
                            if (!userId) return layui.layer.msg('请搜索并选择用户', { icon: 2 });
                            var data = {
                                user_id: userId,
                                designated: parseInt(layero.find('[name="designated"]').val()),
                                prize_id: parseInt(layero.find('[name="prize_id"]').val()) || 0
                            };
                            Api.addLotteryDesignated(actId, data).then(function() {
                                layui.layer.closeAll();
                                layui.table.reload('lottery-designated-table');
                                layui.layer.msg('内定设置成功', { icon: 1 });
                            });
                        });
                    }
                });
            });
        },

        // ========== 抽奖主题 ==========
        renderThemes: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');
            var html = '<div class="content-card"><div class="card-title">抽奖主题</div>' +
                '<div id="lottery-themes-list" class="loading-state"><i class="fas fa-spinner"></i> 加载中...</div></div>';
            Layout.setContent(html);

            Api.getLotteryThemes(actId).then(function(res) {
                var list = res.data || [];
                if (!Array.isArray(list)) list = [];
                var container = document.getElementById('lottery-themes-list');
                if (list.length === 0) {
                    container.innerHTML = '<div class="empty-state"><i class="fas fa-palette"></i><p>暂无主题</p></div>';
                    return;
                }
                var h = '<div class="switch-grid">';
                list.forEach(function(t) {
                    h += '<div class="switch-card"><div class="sw-icon"><i class="fas fa-palette"></i></div><div class="sw-info"><div class="sw-name">' + (t.theme_name || t.name || '未命名') + '</div><div class="sw-desc">' + (t.theme_path || '') + '</div></div></div>';
                });
                h += '</div>';
                container.innerHTML = h;
            }).catch(function() {
                var c = document.getElementById('lottery-themes-list');
                if (c) c.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>加载失败</p></div>';
            });
        },

        // ========== 手机端抽奖 ==========
        renderChoujiang: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');
            var html = '<div class="content-card"><div class="card-title">手机端抽奖配置</div>' +
                '<form class="layui-form" lay-filter="choujiang">' +
                '<div class="layui-form-item"><label class="layui-form-label">功能开关</label><div class="layui-input-block"><input type="checkbox" name="cj_enabled" lay-skin="switch" lay-text="开启|关闭"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">抽奖次数</label><div class="layui-input-inline"><input type="number" name="cj_times" class="layui-input" value="1" min="1"></div><div class="layui-form-mid">次/人</div></div>' +
                '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" onclick="LotteryPage._saveChoujiang()"><i class="fas fa-save"></i> 保存</button></div></div>' +
                '</form></div>';
            Layout.setContent(html);
            layui.form.render(null, 'choujiang');

            Api.getChoujiangConfig(actId).then(function(res) {
                var d = res.data || {};
                if (d.enabled !== undefined) {
                    var el = document.querySelector('[name="cj_enabled"]');
                    if (el) { el.checked = !!d.enabled; layui.form.render('checkbox', 'choujiang'); }
                }
                if (d.times) document.querySelector('[name="cj_times"]').value = d.times;
            }).catch(function() {});
        },

        _saveChoujiang: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return;
            var data = {
                enabled: document.querySelector('[name="cj_enabled"]').checked ? 1 : 0,
                times: parseInt(document.querySelector('[name="cj_times"]').value) || 1
            };
            Api.updateChoujiangConfig(actId, data).then(function() {
                layui.layer.msg('保存成功', { icon: 1 });
            });
        },

        // ========== 导入抽奖 ==========
        renderImport: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');
            var html = '<div class="content-card"><div class="card-title">导入抽奖名单</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">批量导入</label><div class="layui-input-block"><textarea id="import-text" class="layui-textarea" placeholder="每行一个，格式：姓名,手机号" rows="8"></textarea></div></div>' +
                '<div class="layui-form-item"><div class="layui-input-block">' +
                '<button class="btn btn-primary" onclick="LotteryPage._doImport()"><i class="fas fa-upload"></i> 导入</button>' +
                '<button class="btn btn-danger" style="margin-left:10px;" onclick="LotteryPage._clearImport()"><i class="fas fa-trash"></i> 清空</button>' +
                '</div></div></div>';
            Layout.setContent(html);
        },

        _doImport: function() {
            var actId = App.getCurrentActivityId();
            var text = document.getElementById('import-text').value;
            if (!text.trim()) return layui.layer.msg('请输入导入数据', { icon: 2 });
            Api.batchImport(actId, { data: text }).then(function() {
                layui.layer.msg('导入成功', { icon: 1 });
            });
        },

        _clearImport: function() {
            var actId = App.getCurrentActivityId();
            layui.layer.confirm('确定清空导入名单？', { icon: 3 }, function(idx) {
                Api.clearImportList(actId).then(function() {
                    layui.layer.close(idx);
                    layui.layer.msg('已清空', { icon: 1 });
                });
            });
        },

        // ========== 幸运手机号 ==========
        renderLuckyPhone: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>幸运手机号记录</span></div>' +
                '<table id="lucky-phone-table" lay-filter="luckyPhoneTable"></table></div>';
            Layout.setContent(html);

            layui.table.render({
                elem: '#lucky-phone-table',
                url: '/api/hd/lottery/' + actId + '/lucky-phone',
                headers: { 'Hd-Token': Api.getToken() },
                parseData: function(res) {
                    var list = res.data ? (res.data.list || res.data) : [];
                    return { code: 0, msg: '', count: res.data ? (res.data.count || list.length) : 0, data: list };
                },
                cols: [[
                    { field: 'id', title: 'ID', width: 70 },
                    { field: 'nickname', title: '昵称', width: 140 },
                    { field: 'phone', title: '手机号', width: 140 },
                    { field: 'prize_name', title: '奖品', width: 160, templet: function(d) { return d.prize_name || '-'; } },
                    { field: 'status', title: '状态', width: 100, templet: function(d) {
                        return d.status == 3 ? '<span style="color:#43A047">已发奖</span>' : '<span style="color:#FF9800">未发奖</span>';
                    }},
                    { field: 'win_time', title: '中奖时间', width: 160, templet: function(d) {
                        if (!d.win_time) return '-';
                        var t = new Date(d.win_time * 1000);
                        var pad = function(n) { return n < 10 ? '0' + n : n; };
                        return t.getFullYear() + '-' + pad(t.getMonth()+1) + '-' + pad(t.getDate()) + ' ' + pad(t.getHours()) + ':' + pad(t.getMinutes());
                    }}
                ]],
                page: true,
                limit: 50,
                text: { none: '暂无幸运手机号记录' }
            });
        },

        // ========== 幸运号码 ==========
        renderLuckyNumber: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title">幸运号码设置</div>' +
                '<form class="layui-form" lay-filter="luckyNumber" style="padding:20px;">' +
                '<div class="layui-form-item"><label class="layui-form-label">功能开关</label><div class="layui-input-block"><input type="checkbox" name="ln_enabled" lay-skin="switch" lay-text="开启|关闭"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">号码位数</label><div class="layui-input-inline"><input type="number" name="ln_digit" class="layui-input" value="3" min="1" max="6"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">最小值</label><div class="layui-input-inline"><input type="number" name="ln_min" class="layui-input" value="0"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">最大值</label><div class="layui-input-inline"><input type="number" name="ln_max" class="layui-input" value="999"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">奖品名称</label><div class="layui-input-block"><input type="text" name="ln_prize_name" class="layui-input" placeholder="幸运奖品名称"></div></div>' +
                '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" onclick="LotteryPage._saveLuckyNumber()"><i class="fas fa-save"></i> 保存配置</button></div></div>' +
                '</form></div>' +
                '<div class="content-card" style="margin-top:16px;">' +
                '<div class="card-title">中奖记录</div>' +
                '<table id="lucky-number-records-table" lay-filter="luckyNumberRecordsTable"></table></div>';
            Layout.setContent(html);
            layui.form.render(null, 'luckyNumber');

            // 加载配置
            Api.getLuckyNumberConfig(actId).then(function(res) {
                var d = res.data || {};
                var el = document.querySelector('[name="ln_enabled"]');
                if (el) { el.checked = !!d.enabled; layui.form.render('checkbox', 'luckyNumber'); }
                if (d.digit !== undefined) document.querySelector('[name="ln_digit"]').value = d.digit;
                if (d.min !== undefined) document.querySelector('[name="ln_min"]').value = d.min;
                if (d.max !== undefined) document.querySelector('[name="ln_max"]').value = d.max;
                if (d.prize_name) document.querySelector('[name="ln_prize_name"]').value = d.prize_name;
            }).catch(function() {});

            // 加载记录表格
            layui.table.render({
                elem: '#lucky-number-records-table',
                url: '/api/hd/lottery/' + actId + '/lucky-number/records',
                headers: { 'Hd-Token': Api.getToken() },
                parseData: function(res) {
                    var list = res.data ? (res.data.list || res.data) : [];
                    return { code: 0, msg: '', count: res.data ? (res.data.count || list.length) : 0, data: list };
                },
                cols: [[
                    { field: 'id', title: 'ID', width: 70 },
                    { field: 'nickname', title: '昵称', width: 140 },
                    { field: 'verify_code', title: '幸运号码', width: 120 },
                    { field: 'status', title: '状态', width: 100, templet: function(d) {
                        return d.status == 3 ? '<span style="color:#43A047">已发奖</span>' : '<span style="color:#FF9800">未发奖</span>';
                    }},
                    { field: 'win_time', title: '中奖时间', width: 160, templet: function(d) {
                        if (!d.win_time) return '-';
                        var t = new Date(d.win_time * 1000);
                        var pad = function(n) { return n < 10 ? '0' + n : n; };
                        return t.getFullYear() + '-' + pad(t.getMonth()+1) + '-' + pad(t.getDate()) + ' ' + pad(t.getHours()) + ':' + pad(t.getMinutes());
                    }}
                ]],
                page: true,
                limit: 50,
                text: { none: '暂无中奖记录' }
            });
        },

        _saveLuckyNumber: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return;
            var data = {
                enabled: document.querySelector('[name="ln_enabled"]').checked ? 1 : 0,
                digit: parseInt(document.querySelector('[name="ln_digit"]').value) || 3,
                min: parseInt(document.querySelector('[name="ln_min"]').value) || 0,
                max: parseInt(document.querySelector('[name="ln_max"]').value) || 999,
                prize_name: document.querySelector('[name="ln_prize_name"]').value
            };
            Api.updateLuckyNumberConfig(actId, data).then(function() {
                layui.layer.msg('保存成功', { icon: 1 });
            });
        }
    };

    global.LotteryPage = LotteryPage;
})(window);
