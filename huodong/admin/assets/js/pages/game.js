/**
 * 游戏互动页面模块
 * 包含：摇一摇设置、摇一摇模板、数钱游戏、猴子爬树、游戏排行
 */
;(function(global) {
    'use strict';

    var GamePage = {
        // ========== 摇一摇设置 ==========
        renderShake: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title">摇一摇竞技设置</div>' +
                '<form class="layui-form" lay-filter="shakeConfig">' +
                '<div class="form-section"><div class="section-title">游戏参数</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">游戏时长</label><div class="layui-input-inline"><input type="number" name="duration" id="shake-duration" class="layui-input" value="30" min="5"></div>' +
                '<div class="layui-input-inline" style="width:120px;"><select name="durationtype" id="shake-durationtype"><option value="seconds">秒</option><option value="minutes">分钟</option></select></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">获奖名额</label><div class="layui-input-inline"><input type="number" name="toprank" id="shake-toprank" class="layui-input" value="3" min="1"></div><div class="layui-form-mid">名</div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">最大参与</label><div class="layui-input-inline"><input type="number" name="maxplayers" id="shake-maxplayers" class="layui-input" value="0" min="0"></div><div class="layui-form-mid">0为不限</div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">重复获奖</label><div class="layui-input-block"><input type="checkbox" name="winningagain" id="shake-winningagain" lay-skin="switch" lay-text="允许|不允许"></div></div>' +
                '</div>' +
                '<div class="form-section"><div class="section-title">显示设置</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">显示方式</label><div class="layui-input-block"><select name="showstyle" id="shake-showstyle"><option value="1">昵称</option><option value="2">姓名</option><option value="3">手机号</option></select></div></div>' +
                '</div>' +
                '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" onclick="GamePage._saveShake()"><i class="fas fa-save"></i> 保存配置</button></div></div>' +
                '</form></div>';

            Layout.setContent(html);
            layui.form.render(null, 'shakeConfig');

            Api.getShakeConfig(actId).then(function(res) {
                var d = res.data || {};
                if (d.duration) document.getElementById('shake-duration').value = d.duration;
                if (d.durationtype) { document.getElementById('shake-durationtype').value = d.durationtype; layui.form.render('select', 'shakeConfig'); }
                if (d.toprank) document.getElementById('shake-toprank').value = d.toprank;
                if (d.maxplayers !== undefined) document.getElementById('shake-maxplayers').value = d.maxplayers;
                if (d.winningagain) { document.getElementById('shake-winningagain').checked = true; layui.form.render('checkbox', 'shakeConfig'); }
                if (d.showstyle) { document.getElementById('shake-showstyle').value = d.showstyle; layui.form.render('select', 'shakeConfig'); }
            }).catch(function() {});
        },

        _saveShake: function() {
            var actId = App.getCurrentActivityId();
            var data = {
                duration: parseInt(document.getElementById('shake-duration').value) || 30,
                durationtype: document.getElementById('shake-durationtype').value,
                toprank: parseInt(document.getElementById('shake-toprank').value) || 3,
                maxplayers: parseInt(document.getElementById('shake-maxplayers').value) || 0,
                winningagain: document.getElementById('shake-winningagain').checked ? 1 : 0,
                showstyle: document.getElementById('shake-showstyle').value
            };
            Api.updateShakeConfig(actId, data).then(function() {
                layui.layer.msg('保存成功', { icon: 1 });
            });
        },

        // ========== 摇一摇模板 ==========
        renderShakeThemes: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title">摇一摇模板</div>' +
                '<div id="shake-themes-list" class="loading-state"><i class="fas fa-spinner"></i> 加载中...</div></div>';
            Layout.setContent(html);

            Api.getShakeThemes(actId).then(function(res) {
                var list = res.data || [];
                if (!Array.isArray(list)) list = [];
                var container = document.getElementById('shake-themes-list');
                if (list.length === 0) {
                    container.innerHTML = '<div class="empty-state"><i class="fas fa-palette"></i><p>暂无模板</p></div>';
                    return;
                }
                var h = '<div class="switch-grid">';
                list.forEach(function(t) {
                    h += '<div class="switch-card" style="cursor:pointer;" onclick="GamePage._selectShakeTheme(' + actId + ',' + t.id + ')">' +
                        '<div class="sw-icon" style="background:' + (t.selected ? '#43A047' : '#1E88E5') + ';"><i class="fas ' + (t.selected ? 'fa-check' : 'fa-palette') + '"></i></div>' +
                        '<div class="sw-info"><div class="sw-name">' + (t.name || t.theme_name || '模板' + t.id) + '</div>' +
                        '<div class="sw-desc">' + (t.selected ? '当前使用' : '点击选用') + '</div></div></div>';
                });
                h += '</div>';
                container.innerHTML = h;
            }).catch(function() {
                var c = document.getElementById('shake-themes-list');
                if (c) c.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>加载失败</p></div>';
            });
        },

        _selectShakeTheme: function(actId, themeId) {
            Api.updateShakeTheme(actId, themeId, { selected: 1 }).then(function() {
                layui.layer.msg('切换成功', { icon: 1 });
                GamePage.renderShakeThemes();
            });
        },

        // ========== 数钱游戏 ==========
        renderShuqian: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title">数钱游戏设置</div>' +
                '<form class="layui-form" lay-filter="shuqianConfig">' +
                '<div class="layui-form-item"><label class="layui-form-label">游戏时长</label><div class="layui-input-inline"><input type="number" name="sq_duration" class="layui-input" value="30" min="5"></div><div class="layui-form-mid">秒</div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">获奖名额</label><div class="layui-input-inline"><input type="number" name="sq_toprank" class="layui-input" value="3" min="1"></div><div class="layui-form-mid">名</div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">最大参与</label><div class="layui-input-inline"><input type="number" name="sq_maxplayers" class="layui-input" value="0" min="0"></div><div class="layui-form-mid">0为不限</div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">重复获奖</label><div class="layui-input-block"><input type="checkbox" name="sq_winningagain" lay-skin="switch" lay-text="允许|不允许"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">显示方式</label><div class="layui-input-block"><select name="sq_showstyle"><option value="1">昵称</option><option value="2">姓名</option><option value="3">手机号</option></select></div></div>' +
                '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" onclick="GamePage._saveShuqian()"><i class="fas fa-save"></i> 保存配置</button></div></div>' +
                '</form></div>';
            Layout.setContent(html);
            layui.form.render(null, 'shuqianConfig');

            Api.getGameConfig(actId).then(function(res) {
                var d = res.data || {};
                var sq = d.shuqian || d;
                if (sq.duration) document.querySelector('[name="sq_duration"]').value = sq.duration;
                if (sq.toprank) document.querySelector('[name="sq_toprank"]').value = sq.toprank;
            }).catch(function() {});
        },

        _saveShuqian: function() {
            var actId = App.getCurrentActivityId();
            var data = {
                game_type: 'shuqian',
                duration: parseInt(document.querySelector('[name="sq_duration"]').value) || 30,
                toprank: parseInt(document.querySelector('[name="sq_toprank"]').value) || 3,
                maxplayers: parseInt(document.querySelector('[name="sq_maxplayers"]').value) || 0,
                winningagain: document.querySelector('[name="sq_winningagain"]').checked ? 1 : 0,
                showstyle: document.querySelector('[name="sq_showstyle"]').value
            };
            Api.updateGameConfig(actId, data).then(function() {
                layui.layer.msg('保存成功', { icon: 1 });
            });
        },

        // ========== 猴子爬树 ==========
        renderPashu: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title">猴子爬树设置</div>' +
                '<form class="layui-form" lay-filter="pashuConfig">' +
                '<div class="layui-form-item"><label class="layui-form-label">游戏次数</label><div class="layui-input-inline"><input type="number" name="ps_times" class="layui-input" value="3" min="1"></div><div class="layui-form-mid">次</div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">获奖名额</label><div class="layui-input-inline"><input type="number" name="ps_toprank" class="layui-input" value="3" min="1"></div><div class="layui-form-mid">名</div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">最大参与</label><div class="layui-input-inline"><input type="number" name="ps_maxplayers" class="layui-input" value="0" min="0"></div><div class="layui-form-mid">0为不限</div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">重复获奖</label><div class="layui-input-block"><input type="checkbox" name="ps_winningagain" lay-skin="switch" lay-text="允许|不允许"></div></div>' +
                '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" onclick="GamePage._savePashu()"><i class="fas fa-save"></i> 保存配置</button></div></div>' +
                '</form></div>';
            Layout.setContent(html);
            layui.form.render(null, 'pashuConfig');
        },

        _savePashu: function() {
            var actId = App.getCurrentActivityId();
            var data = {
                game_type: 'pashu',
                times: parseInt(document.querySelector('[name="ps_times"]').value) || 3,
                toprank: parseInt(document.querySelector('[name="ps_toprank"]').value) || 3,
                maxplayers: parseInt(document.querySelector('[name="ps_maxplayers"]').value) || 0,
                winningagain: document.querySelector('[name="ps_winningagain"]').checked ? 1 : 0
            };
            Api.updateGameConfig(actId, data).then(function() {
                layui.layer.msg('保存成功', { icon: 1 });
            });
        },

        // ========== 游戏排行 ==========
        renderRanking: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title"><span>游戏排行榜</span><div>' +
                '<button class="btn btn-warning btn-sm" onclick="GamePage._resetRanking(\'shake\')"><i class="fas fa-redo"></i> 重置摇一摇</button> ' +
                '<button class="btn btn-warning btn-sm" onclick="GamePage._resetRanking(\'game\')"><i class="fas fa-redo"></i> 重置游戏</button>' +
                '</div></div>' +
                '<div class="tab-header">' +
                '<div class="tab-item active" onclick="GamePage._switchTab(this,\'shake-ranking\')">摇一摇排行</div>' +
                '<div class="tab-item" onclick="GamePage._switchTab(this,\'game-ranking\')">游戏排行</div></div>' +
                '<div id="shake-ranking"><table id="shake-ranking-table" lay-filter="shakeRankingTable"></table></div>' +
                '<div id="game-ranking" style="display:none;"><table id="game-ranking-table" lay-filter="gameRankingTable"></table></div>' +
                '</div>';
            Layout.setContent(html);

            layui.table.render({
                elem: '#shake-ranking-table',
                url: '/api/hd/speed/' + actId + '/shake/ranking',
                headers: { 'Authorization': 'Bearer ' + Api.getToken() },
                parseData: function(res) {
                    var list = res.data ? (res.data.list || res.data) : [];
                    return { code: 0, msg: '', count: Array.isArray(list) ? list.length : 0, data: list };
                },
                cols: [[
                    { type: 'numbers', title: '排名', width: 80 },
                    { field: 'nickname', title: '昵称', width: 150 },
                    { field: 'score', title: '成绩', width: 120 },
                    { field: 'datetime', title: '时间', width: 170 }
                ]],
                page: false,
                text: { none: '暂无排行数据' }
            });
        },

        _switchTab: function(el, tabId) {
            el.parentElement.querySelectorAll('.tab-item').forEach(function(t) { t.classList.remove('active'); });
            el.classList.add('active');
            document.getElementById('shake-ranking').style.display = tabId === 'shake-ranking' ? '' : 'none';
            document.getElementById('game-ranking').style.display = tabId === 'game-ranking' ? '' : 'none';
        },

        _resetRanking: function(type) {
            var actId = App.getCurrentActivityId();
            layui.layer.confirm('确定重置排行榜？此操作不可恢复！', { icon: 3 }, function(idx) {
                var promise = type === 'shake' ? Api.resetShake(actId) : Api.resetGame(actId);
                promise.then(function() {
                    layui.layer.close(idx);
                    layui.layer.msg('重置成功', { icon: 1 });
                    GamePage.renderRanking();
                });
            });
        }
    };

    global.GamePage = GamePage;
})(window);
