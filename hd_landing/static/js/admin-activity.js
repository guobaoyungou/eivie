/**
 * 艺为微信大屏互动 - 活动详情管理
 * 签到/抽奖/拼手速/红包/弹幕/主题/相册/投票/功能开关
 */

var currentActTab = 'sign';

function switchActTab(tab) {
    currentActTab = tab;
    var tabs = document.querySelectorAll('#actSubTabs .sub-tab');
    for (var i = 0; i < tabs.length; i++) {
        tabs[i].classList.toggle('active', tabs[i].getAttribute('data-tab') === tab);
    }
    loadActTab(tab);
}

function loadActTab(tab) {
    var aid = currentActivityId;
    if (!aid) return;
    var container = document.getElementById('actTabContent');

    var loaders = {
        'sign': loadSignTab,
        'lottery': loadLotteryTab,
        'speed': loadSpeedTab,
        'redpacket': loadRedpacketTab,
        'wall': loadWallTab,
        'theme': loadThemeTab,
        'album': loadAlbumTab,
        'vote': loadVoteTab,
        'security': loadSecurityTab,
        'brand': loadBrandTab,
        'switch': loadSwitchTab
    };

    if (loaders[tab]) {
        container.innerHTML = '<div class="card"><div class="card-body" style="text-align:center;padding:40px;color:#9ca3af">加载中...</div></div>';
        loaders[tab](aid, container);
    }
}

// ============================================================
// 签到管理
// ============================================================
function loadSignTab(aid, container) {
    HdApi.getSignConfig(aid).then(function(res) {
        var cfg = (res.code === 0 && res.data) ? res.data : {};
        var html = '';
        html += '<div class="card"><div class="card-header"><h3>✅ 签到设置</h3></div><div class="card-body">';
        html += '<div class="config-grid">';
        html += '<div class="form-group"><label class="form-label">签到方式</label><select class="form-input" id="signMode"><option value="scan"' + (cfg.mode==='scan'?' selected':'') + '>扫码签到</option><option value="code"' + (cfg.mode==='code'?' selected':'') + '>口令签到</option><option value="free"' + (cfg.mode==='free'?' selected':'') + '>自由签到</option></select></div>';
        html += '<div class="form-group"><label class="form-label">签到口令</label><input type="text" class="form-input" id="signCode" value="' + (cfg.sign_code||'') + '" placeholder="签到口令"></div>';
        html += '<div class="form-group"><label class="form-label">是否需要手机号</label><select class="form-input" id="signNeedPhone"><option value="1"' + (cfg.need_phone==1?' selected':'') + '>需要</option><option value="0"' + (cfg.need_phone==0?' selected':'') + '>不需要</option></select></div>';
        html += '<div class="form-group"><label class="form-label">签到动画</label><select class="form-input" id="signAnimation"><option value="default"' + (cfg.animation==='default'?' selected':'') + '>默认动画</option><option value="firework"' + (cfg.animation==='firework'?' selected':'') + '>烟花</option><option value="star"' + (cfg.animation==='star'?' selected':'') + '>星光</option></select></div>';
        html += '</div>';
        html += '<button class="btn btn-primary" onclick="saveSignConfig(' + aid + ')">保存签到设置</button>';
        html += '</div></div>';

        // 签到名单
        html += '<div class="card" style="margin-top:20px"><div class="card-header"><h3>📋 签到名单</h3>';
        html += '<div style="display:flex;gap:8px"><button class="btn btn-default btn-sm" onclick="loadSignList(' + aid + ')">刷新</button>';
        html += '<button class="btn btn-danger btn-sm" onclick="clearSignList(' + aid + ')">清空名单</button></div></div>';
        html += '<div class="card-body no-padding"><table class="data-table"><thead><tr><th>ID</th><th>昵称</th><th>手机号</th><th>签到时间</th><th>操作</th></tr></thead>';
        html += '<tbody id="signListBody"><tr><td colspan="5" class="empty-state">点击刷新加载</td></tr></tbody></table></div></div>';

        container.innerHTML = html;
        loadSignList(aid);
    });
}

function saveSignConfig(aid) {
    var data = {
        mode: document.getElementById('signMode').value,
        sign_code: document.getElementById('signCode').value,
        need_phone: document.getElementById('signNeedPhone').value,
        animation: document.getElementById('signAnimation').value
    };
    HdApi.updateSignConfig(aid, data).then(function(res) {
        showToast(res.code === 0 ? '签到设置已保存' : (res.msg||'保存失败'), res.code === 0 ? 'success' : 'error');
    });
}

function loadSignList(aid) {
    HdApi.getSignList(aid, 1).then(function(res) {
        var list = (res.code === 0 && res.data) ? (res.data.list || []) : [];
        if (list.length === 0) {
            document.getElementById('signListBody').innerHTML = '<tr><td colspan="5" class="empty-state">暂无签到记录</td></tr>';
            return;
        }
        var html = '';
        for (var i = 0; i < list.length; i++) {
            var p = list[i];
            html += '<tr><td>' + p.id + '</td><td>' + (p.nickname||p.name||'-') + '</td><td>' + (p.phone||'-') + '</td>';
            html += '<td>' + formatTime(p.createtime||p.sign_time) + '</td>';
            html += '<td><button class="btn btn-danger btn-sm" onclick="deleteSignP(' + aid + ',' + p.id + ')">删除</button></td></tr>';
        }
        document.getElementById('signListBody').innerHTML = html;
    });
}

function deleteSignP(aid, id) {
    if (!confirm('确定删除该签到记录？')) return;
    HdApi.deleteSignParticipant(aid, id).then(function(res) {
        if (res.code === 0) { showToast('已删除'); loadSignList(aid); }
        else showToast(res.msg||'删除失败', 'error');
    });
}

function clearSignList(aid) {
    if (!confirm('确定清空所有签到记录？此操作不可撤销！')) return;
    HdApi.clearSignList(aid).then(function(res) {
        if (res.code === 0) { showToast('已清空'); loadSignList(aid); }
        else showToast(res.msg||'操作失败', 'error');
    });
}

// ============================================================
// 抽奖管理
// ============================================================
function loadLotteryTab(aid, container) {
    Promise.all([
        HdApi.getPrizes(aid),
        HdApi.getRounds(aid),
        HdApi.getLotteryThemes(aid)
    ]).then(function(results) {
        var prizes = (results[0].code === 0 && results[0].data) ? (results[0].data.list || []) : [];
        var rounds = (results[1].code === 0 && results[1].data) ? (results[1].data.list || []) : [];
        var themes = (results[2].code === 0 && results[2].data) ? (results[2].data.list || []) : [];

        var html = '';

        // 奖品管理
        html += '<div class="card"><div class="card-header"><h3>🎁 奖品管理</h3>';
        html += '<button class="btn btn-primary btn-sm" onclick="showPrizeModal(' + aid + ')">+ 添加奖品</button></div>';
        html += '<div class="card-body">';
        if (prizes.length === 0) {
            html += '<div class="empty-state"><p>暂无奖品，请添加</p></div>';
        } else {
            html += '<div class="item-grid">';
            for (var i = 0; i < prizes.length; i++) {
                var p = prizes[i];
                html += '<div class="item-card">';
                html += '<div class="item-title">' + p.name + '</div>';
                html += '<div class="item-meta">总数: ' + p.total_num + ' | 已用: ' + (p.used_num||0) + '</div>';
                if (p.image) html += '<div class="item-meta"><img src="' + p.image + '" style="height:40px;border-radius:4px"></div>';
                html += '<div class="item-actions">';
                html += '<button class="btn btn-default btn-sm" onclick="editPrize(' + aid + ',' + p.id + ')">编辑</button>';
                html += '<button class="btn btn-danger btn-sm" onclick="deletePrize(' + aid + ',' + p.id + ')">删除</button>';
                html += '</div></div>';
            }
            html += '</div>';
        }
        html += '</div></div>';

        // 抽奖轮次
        html += '<div class="card" style="margin-top:20px"><div class="card-header"><h3>🎰 抽奖轮次</h3>';
        html += '<button class="btn btn-primary btn-sm" onclick="showRoundModal(' + aid + ')">+ 添加轮次</button></div>';
        html += '<div class="card-body no-padding"><table class="data-table"><thead><tr><th>轮次名称</th><th>每轮人数</th><th>关联奖品</th><th>允许重复</th><th>状态</th><th>操作</th></tr></thead><tbody>';
        if (rounds.length === 0) {
            html += '<tr><td colspan="6" class="empty-state">暂无轮次</td></tr>';
        } else {
            for (var i = 0; i < rounds.length; i++) {
                var r = rounds[i];
                html += '<tr><td>' + (r.round_name||'第'+(i+1)+'轮') + '</td>';
                html += '<td>' + (r.win_num||0) + '</td>';
                html += '<td>' + (r.prize_name||r.prize_id||'-') + '</td>';
                html += '<td>' + (r.is_repeat ? '是' : '否') + '</td>';
                html += '<td>' + (r.status==1 ? '<span class="badge badge-success">启用</span>' : '<span class="badge badge-danger">禁用</span>') + '</td>';
                html += '<td>';
                html += '<button class="btn btn-default btn-sm" onclick="editRound(' + aid + ',' + r.id + ')">编辑</button> ';
                html += '<button class="btn btn-default btn-sm" onclick="resetRound(' + aid + ',' + r.id + ')">重置</button> ';
                html += '<button class="btn btn-danger btn-sm" onclick="deleteRound(' + aid + ',' + r.id + ')">删除</button>';
                html += '</td></tr>';
            }
        }
        html += '</tbody></table></div></div>';

        // 抽奖主题
        html += '<div class="card" style="margin-top:20px"><div class="card-header"><h3>🎨 抽奖主题</h3>';
        html += '<button class="btn btn-primary btn-sm" onclick="showLotteryThemeModal(' + aid + ')">+ 添加主题</button></div>';
        html += '<div class="card-body">';
        if (themes.length === 0) {
            html += '<div class="empty-state"><p>暂无主题</p></div>';
        } else {
            html += '<div class="item-grid">';
            for (var i = 0; i < themes.length; i++) {
                var t = themes[i];
                html += '<div class="item-card"><div class="item-title">' + (t.name||t.theme_name||'主题'+(i+1)) + '</div>';
                if (t.bg_image) html += '<div class="item-meta"><img src="' + t.bg_image + '" style="height:50px;border-radius:4px"></div>';
                html += '<div class="item-actions">';
                html += '<button class="btn btn-default btn-sm" onclick="editLotteryTheme(' + aid + ',' + t.id + ')">编辑</button>';
                html += '<button class="btn btn-danger btn-sm" onclick="deleteLotteryTheme(' + aid + ',' + t.id + ')">删除</button>';
                html += '</div></div>';
            }
            html += '</div>';
        }
        html += '</div></div>';

        container.innerHTML = html;
    });
}

// Prize CRUD
function showPrizeModal(aid, data) {
    var d = data || {};
    var html = '<div class="modal-overlay show" id="prizeModal"><div class="modal"><div class="modal-header"><h3>' + (d.id?'编辑':'添加') + '奖品</h3><button class="modal-close" onclick="closeModal(\'prizeModal\')">&times;</button></div>';
    html += '<div class="modal-body">';
    html += '<input type="hidden" id="prizeEditId" value="' + (d.id||'') + '">';
    html += '<div class="form-group"><label class="form-label">奖品名称</label><input type="text" class="form-input" id="prizeFormName" value="' + (d.name||'') + '"></div>';
    html += '<div class="form-group"><label class="form-label">总数量</label><input type="number" class="form-input" id="prizeFormTotal" value="' + (d.total_num||10) + '"></div>';
    html += '<div class="form-group"><label class="form-label">图片URL</label><input type="text" class="form-input" id="prizeFormImage" value="' + (d.image||'') + '" placeholder="可选"></div>';
    html += '</div><div class="modal-footer"><button class="btn btn-default" onclick="closeModal(\'prizeModal\')">取消</button>';
    html += '<button class="btn btn-primary" onclick="savePrize(' + aid + ')">保存</button></div></div></div>';
    document.body.insertAdjacentHTML('beforeend', html);
}

function savePrize(aid) {
    var id = document.getElementById('prizeEditId').value;
    var data = {
        name: document.getElementById('prizeFormName').value,
        total_num: document.getElementById('prizeFormTotal').value,
        image: document.getElementById('prizeFormImage').value
    };
    if (!data.name) { showToast('请输入奖品名称', 'error'); return; }
    var p = id ? HdApi.updatePrize(aid, id, data) : HdApi.createPrize(aid, data);
    p.then(function(res) {
        if (res.code === 0) { showToast(id?'已更新':'已添加'); removeDynModal('prizeModal'); loadLotteryTab(aid, document.getElementById('actTabContent')); }
        else showToast(res.msg||'操作失败', 'error');
    });
}

function editPrize(aid, id) {
    HdApi.getPrizes(aid).then(function(res) {
        var list = (res.code===0 && res.data) ? (res.data.list||[]) : [];
        var found = list.find(function(x){return x.id==id;});
        if (found) showPrizeModal(aid, found);
    });
}

function deletePrize(aid, id) {
    if (!confirm('确定删除该奖品？')) return;
    HdApi.deletePrize(aid, id).then(function(res) {
        if (res.code===0) { showToast('已删除'); loadLotteryTab(aid, document.getElementById('actTabContent')); }
        else showToast(res.msg||'删除失败', 'error');
    });
}

// Round CRUD
function showRoundModal(aid, data) {
    var d = data || {};
    var html = '<div class="modal-overlay show" id="roundModal"><div class="modal"><div class="modal-header"><h3>' + (d.id?'编辑':'添加') + '轮次</h3><button class="modal-close" onclick="closeModal(\'roundModal\')">&times;</button></div>';
    html += '<div class="modal-body">';
    html += '<input type="hidden" id="roundEditId" value="' + (d.id||'') + '">';
    html += '<div class="form-group"><label class="form-label">轮次名称</label><input type="text" class="form-input" id="roundFormName" value="' + (d.round_name||'') + '"></div>';
    html += '<div class="form-group"><label class="form-label">中奖人数</label><input type="number" class="form-input" id="roundFormWinNum" value="' + (d.win_num||1) + '"></div>';
    html += '<div class="form-group"><label class="form-label">关联奖品ID</label><input type="number" class="form-input" id="roundFormPrize" value="' + (d.prize_id||'') + '"></div>';
    html += '<div class="form-group"><label class="form-label">允许重复中奖</label><select class="form-input" id="roundFormRepeat"><option value="0"' + (d.is_repeat==0?' selected':'') + '>否</option><option value="1"' + (d.is_repeat==1?' selected':'') + '>是</option></select></div>';
    html += '</div><div class="modal-footer"><button class="btn btn-default" onclick="closeModal(\'roundModal\')">取消</button>';
    html += '<button class="btn btn-primary" onclick="saveRound(' + aid + ')">保存</button></div></div></div>';
    document.body.insertAdjacentHTML('beforeend', html);
}

function saveRound(aid) {
    var id = document.getElementById('roundEditId').value;
    var data = {
        round_name: document.getElementById('roundFormName').value,
        win_num: document.getElementById('roundFormWinNum').value,
        prize_id: document.getElementById('roundFormPrize').value,
        is_repeat: document.getElementById('roundFormRepeat').value
    };
    var p = id ? HdApi.updateRound(aid, id, data) : HdApi.createRound(aid, data);
    p.then(function(res) {
        if (res.code===0) { showToast(id?'已更新':'已添加'); removeDynModal('roundModal'); loadLotteryTab(aid, document.getElementById('actTabContent')); }
        else showToast(res.msg||'操作失败', 'error');
    });
}

function editRound(aid, id) {
    HdApi.getRounds(aid).then(function(res) {
        var list = (res.code===0 && res.data) ? (res.data.list||[]) : [];
        var found = list.find(function(x){return x.id==id;});
        if (found) showRoundModal(aid, found);
    });
}

function deleteRound(aid, id) {
    if (!confirm('确定删除该轮次？')) return;
    HdApi.deleteRound(aid, id).then(function(res) {
        if (res.code===0) { showToast('已删除'); loadLotteryTab(aid, document.getElementById('actTabContent')); }
        else showToast(res.msg||'删除失败', 'error');
    });
}

function resetRound(aid, id) {
    if (!confirm('确定重置该轮次的中奖数据？')) return;
    HdApi.resetRound(aid, id).then(function(res) {
        showToast(res.code===0 ? '已重置' : (res.msg||'操作失败'), res.code===0 ? 'success' : 'error');
    });
}

// Lottery Theme
function showLotteryThemeModal(aid, data) {
    var d = data || {};
    var html = '<div class="modal-overlay show" id="lotteryThemeModal"><div class="modal"><div class="modal-header"><h3>' + (d.id?'编辑':'添加') + '抽奖主题</h3><button class="modal-close" onclick="closeModal(\'lotteryThemeModal\')">&times;</button></div>';
    html += '<div class="modal-body">';
    html += '<input type="hidden" id="ltEditId" value="' + (d.id||'') + '">';
    html += '<div class="form-group"><label class="form-label">主题名称</label><input type="text" class="form-input" id="ltFormName" value="' + (d.name||d.theme_name||'') + '"></div>';
    html += '<div class="form-group"><label class="form-label">背景图片URL</label><input type="text" class="form-input" id="ltFormBg" value="' + (d.bg_image||'') + '"></div>';
    html += '</div><div class="modal-footer"><button class="btn btn-default" onclick="closeModal(\'lotteryThemeModal\')">取消</button>';
    html += '<button class="btn btn-primary" onclick="saveLotteryTheme(' + aid + ')">保存</button></div></div></div>';
    document.body.insertAdjacentHTML('beforeend', html);
}

function saveLotteryTheme(aid) {
    var id = document.getElementById('ltEditId').value;
    var data = { name: document.getElementById('ltFormName').value, bg_image: document.getElementById('ltFormBg').value };
    var p = id ? HdApi.updateLotteryTheme(aid, id, data) : HdApi.createLotteryTheme(aid, data);
    p.then(function(res) {
        if (res.code===0) { showToast(id?'已更新':'已添加'); removeDynModal('lotteryThemeModal'); loadLotteryTab(aid, document.getElementById('actTabContent')); }
        else showToast(res.msg||'操作失败', 'error');
    });
}

function editLotteryTheme(aid, id) {
    HdApi.getLotteryThemes(aid).then(function(res) {
        var list = (res.code===0 && res.data) ? (res.data.list||[]) : [];
        var found = list.find(function(x){return x.id==id;});
        if (found) showLotteryThemeModal(aid, found);
    });
}

function deleteLotteryTheme(aid, id) {
    if (!confirm('确定删除该主题？')) return;
    HdApi.deleteLotteryTheme(aid, id).then(function(res) {
        if (res.code===0) { showToast('已删除'); loadLotteryTab(aid, document.getElementById('actTabContent')); }
        else showToast(res.msg||'删除失败', 'error');
    });
}

// ============================================================
// 拼手速
// ============================================================
function loadSpeedTab(aid, container) {
    Promise.all([
        HdApi.getShakeConfig(aid),
        HdApi.getGameConfig(aid)
    ]).then(function(results) {
        var shake = (results[0].code===0 && results[0].data) ? results[0].data : {};
        var game = (results[1].code===0 && results[1].data) ? results[1].data : {};

        var html = '';

        // 摇一摇竞技
        html += '<div class="card"><div class="card-header"><h3>📱 摇一摇竞技</h3>';
        html += '<div style="display:flex;gap:8px"><button class="btn btn-default btn-sm" onclick="showShakeRanking(' + aid + ')">排行榜</button>';
        html += '<button class="btn btn-danger btn-sm" onclick="resetShakeData(' + aid + ')">重置数据</button></div></div>';
        html += '<div class="card-body"><div class="config-grid">';
        html += '<div class="form-group"><label class="form-label">竞技时长(秒)</label><input type="number" class="form-input" id="shakeDuration" value="' + (shake.duration||30) + '"></div>';
        html += '<div class="form-group"><label class="form-label">最大参与人数</label><input type="number" class="form-input" id="shakeMaxPart" value="' + (shake.max_participants||100) + '"></div>';
        html += '<div class="form-group"><label class="form-label">获奖人数</label><input type="number" class="form-input" id="shakeMaxWin" value="' + (shake.max_winners||3) + '"></div>';
        html += '<div class="form-group"><label class="form-label">关联奖品ID</label><input type="number" class="form-input" id="shakePrizeId" value="' + (shake.prize_id||'') + '"></div>';
        html += '</div><button class="btn btn-primary" onclick="saveShakeConfig(' + aid + ')">保存摇一摇设置</button></div></div>';

        // 互动游戏
        html += '<div class="card" style="margin-top:20px"><div class="card-header"><h3>🎮 互动游戏</h3>';
        html += '<div style="display:flex;gap:8px"><button class="btn btn-default btn-sm" onclick="showGameRanking(' + aid + ')">排行榜</button>';
        html += '<button class="btn btn-danger btn-sm" onclick="resetGameData(' + aid + ')">重置数据</button></div></div>';
        html += '<div class="card-body"><div class="config-grid">';
        html += '<div class="form-group"><label class="form-label">游戏类型</label><select class="form-input" id="gameType">';
        var gameTypes = ['赛车','赛马','游泳','自行车','龟兔赛跑','太空冒险','马拉松','飞行大战','数钱','摇骰子','猴子爬树'];
        for (var i=0;i<gameTypes.length;i++) {
            html += '<option value="' + gameTypes[i] + '"' + (game.game_type===gameTypes[i]?' selected':'') + '>' + gameTypes[i] + '</option>';
        }
        html += '</select></div>';
        html += '<div class="form-group"><label class="form-label">游戏时长(秒)</label><input type="number" class="form-input" id="gameDuration" value="' + (game.duration||30) + '"></div>';
        html += '<div class="form-group"><label class="form-label">最大参与人数</label><input type="number" class="form-input" id="gameMaxPart" value="' + (game.max_participants||100) + '"></div>';
        html += '<div class="form-group"><label class="form-label">获奖人数</label><input type="number" class="form-input" id="gameMaxWin" value="' + (game.max_winners||3) + '"></div>';
        html += '</div><button class="btn btn-primary" onclick="saveGameConfig(' + aid + ')">保存游戏设置</button></div></div>';

        container.innerHTML = html;
    });
}

function saveShakeConfig(aid) {
    var data = {
        duration: document.getElementById('shakeDuration').value,
        max_participants: document.getElementById('shakeMaxPart').value,
        max_winners: document.getElementById('shakeMaxWin').value,
        prize_id: document.getElementById('shakePrizeId').value
    };
    HdApi.updateShakeConfig(aid, data).then(function(res) {
        showToast(res.code===0 ? '摇一摇设置已保存' : (res.msg||'保存失败'), res.code===0 ? 'success' : 'error');
    });
}

function saveGameConfig(aid) {
    var data = {
        game_type: document.getElementById('gameType').value,
        duration: document.getElementById('gameDuration').value,
        max_participants: document.getElementById('gameMaxPart').value,
        max_winners: document.getElementById('gameMaxWin').value
    };
    HdApi.updateGameConfig(aid, data).then(function(res) {
        showToast(res.code===0 ? '游戏设置已保存' : (res.msg||'保存失败'), res.code===0 ? 'success' : 'error');
    });
}

function showShakeRanking(aid) {
    HdApi.getShakeRanking(aid).then(function(res) {
        var list = (res.code===0 && res.data) ? (res.data.list||[]) : [];
        showRankingModal('摇一摇排行榜', list);
    });
}

function showGameRanking(aid) {
    HdApi.getGameRanking(aid).then(function(res) {
        var list = (res.code===0 && res.data) ? (res.data.list||[]) : [];
        showRankingModal('游戏排行榜', list);
    });
}

function showRankingModal(title, list) {
    var html = '<div class="modal-overlay show" id="rankingModal"><div class="modal"><div class="modal-header"><h3>' + title + '</h3><button class="modal-close" onclick="closeModal(\'rankingModal\')">&times;</button></div>';
    html += '<div class="modal-body">';
    if (list.length === 0) {
        html += '<div class="empty-state"><p>暂无排行数据</p></div>';
    } else {
        html += '<table class="data-table"><thead><tr><th>排名</th><th>昵称</th><th>分数</th></tr></thead><tbody>';
        for (var i=0; i<list.length; i++) {
            html += '<tr><td>' + (i+1) + '</td><td>' + (list[i].nickname||list[i].name||'-') + '</td><td>' + (list[i].score||0) + '</td></tr>';
        }
        html += '</tbody></table>';
    }
    html += '</div></div></div>';
    document.body.insertAdjacentHTML('beforeend', html);
}

function resetShakeData(aid) {
    if (!confirm('确定重置摇一摇数据？')) return;
    HdApi.resetShake(aid).then(function(res) {
        showToast(res.code===0 ? '已重置' : (res.msg||'操作失败'), res.code===0 ? 'success' : 'error');
    });
}

function resetGameData(aid) {
    if (!confirm('确定重置游戏数据？')) return;
    HdApi.resetGame(aid).then(function(res) {
        showToast(res.code===0 ? '已重置' : (res.msg||'操作失败'), res.code===0 ? 'success' : 'error');
    });
}

// ============================================================
// 红包互动
// ============================================================
function loadRedpacketTab(aid, container) {
    Promise.all([
        HdApi.getRedpacketConfig(aid),
        HdApi.getRedpacketRounds(aid)
    ]).then(function(results) {
        var cfg = (results[0].code===0 && results[0].data) ? results[0].data : {};
        var rounds = (results[1].code===0 && results[1].data) ? (results[1].data.list||[]) : [];

        var html = '';
        html += '<div class="card"><div class="card-header"><h3>🧧 红包配置</h3></div>';
        html += '<div class="card-body"><div class="config-grid">';
        html += '<div class="form-group"><label class="form-label">红包总金额(元)</label><input type="number" class="form-input" id="rpTotalAmt" value="' + (cfg.total_amount||100) + '" step="0.01"></div>';
        html += '<div class="form-group"><label class="form-label">红包总个数</label><input type="number" class="form-input" id="rpTotalNum" value="' + (cfg.total_num||50) + '"></div>';
        html += '<div class="form-group"><label class="form-label">最小金额(元)</label><input type="number" class="form-input" id="rpMinAmt" value="' + (cfg.min_amount||0.01) + '" step="0.01"></div>';
        html += '<div class="form-group"><label class="form-label">最大金额(元)</label><input type="number" class="form-input" id="rpMaxAmt" value="' + (cfg.max_amount||10) + '" step="0.01"></div>';
        html += '<div class="form-group"><label class="form-label">抢红包时长(秒)</label><input type="number" class="form-input" id="rpDuration" value="' + (cfg.duration||60) + '"></div>';
        html += '</div><button class="btn btn-primary" onclick="saveRedpacketConfig(' + aid + ')">保存红包配置</button></div></div>';

        // 红包轮次
        html += '<div class="card" style="margin-top:20px"><div class="card-header"><h3>🔄 红包轮次</h3>';
        html += '<button class="btn btn-primary btn-sm" onclick="showRpRoundModal(' + aid + ')">+ 添加轮次</button></div>';
        html += '<div class="card-body no-padding"><table class="data-table"><thead><tr><th>轮次名称</th><th>总金额</th><th>红包数</th><th>状态</th><th>操作</th></tr></thead><tbody>';
        if (rounds.length === 0) {
            html += '<tr><td colspan="5" class="empty-state">暂无轮次</td></tr>';
        } else {
            for (var i = 0; i < rounds.length; i++) {
                var r = rounds[i];
                html += '<tr><td>' + (r.round_name||'第'+(i+1)+'轮') + '</td><td>¥' + (r.total_amount||0) + '</td><td>' + (r.total_num||0) + '</td>';
                html += '<td>' + (r.status==1 ? '<span class="badge badge-success">启用</span>' : '<span class="badge badge-danger">禁用</span>') + '</td>';
                html += '<td><button class="btn btn-default btn-sm" onclick="editRpRound(' + aid + ',' + r.id + ')">编辑</button> ';
                html += '<button class="btn btn-danger btn-sm" onclick="deleteRpRound(' + aid + ',' + r.id + ')">删除</button></td></tr>';
            }
        }
        html += '</tbody></table></div></div>';

        // 中奖记录
        html += '<div class="card" style="margin-top:20px"><div class="card-header"><h3>📜 中奖记录</h3>';
        html += '<button class="btn btn-default btn-sm" onclick="loadRpRecords(' + aid + ')">刷新</button></div>';
        html += '<div class="card-body no-padding"><table class="data-table"><thead><tr><th>用户</th><th>金额</th><th>时间</th></tr></thead>';
        html += '<tbody id="rpRecordsBody"><tr><td colspan="3" class="empty-state">点击刷新查看</td></tr></tbody></table></div></div>';

        container.innerHTML = html;
        loadRpRecords(aid);
    });
}

function saveRedpacketConfig(aid) {
    var data = {
        total_amount: document.getElementById('rpTotalAmt').value,
        total_num: document.getElementById('rpTotalNum').value,
        min_amount: document.getElementById('rpMinAmt').value,
        max_amount: document.getElementById('rpMaxAmt').value,
        duration: document.getElementById('rpDuration').value
    };
    HdApi.updateRedpacketConfig(aid, data).then(function(res) {
        showToast(res.code===0 ? '红包配置已保存' : (res.msg||'保存失败'), res.code===0 ? 'success' : 'error');
    });
}

function loadRpRecords(aid) {
    HdApi.getRedpacketRecords(aid).then(function(res) {
        var list = (res.code===0 && res.data) ? (res.data.list||[]) : [];
        if (list.length === 0) {
            document.getElementById('rpRecordsBody').innerHTML = '<tr><td colspan="3" class="empty-state">暂无中奖记录</td></tr>';
            return;
        }
        var html = '';
        for (var i=0;i<list.length;i++) {
            html += '<tr><td>' + (list[i].nickname||list[i].user_id||'-') + '</td><td>¥' + (list[i].amount||0) + '</td><td>' + formatTime(list[i].createtime) + '</td></tr>';
        }
        document.getElementById('rpRecordsBody').innerHTML = html;
    });
}

function showRpRoundModal(aid, data) {
    var d = data || {};
    var html = '<div class="modal-overlay show" id="rpRoundModal"><div class="modal"><div class="modal-header"><h3>' + (d.id?'编辑':'添加') + '红包轮次</h3><button class="modal-close" onclick="closeModal(\'rpRoundModal\')">&times;</button></div>';
    html += '<div class="modal-body">';
    html += '<input type="hidden" id="rpRoundEditId" value="' + (d.id||'') + '">';
    html += '<div class="form-group"><label class="form-label">轮次名称</label><input type="text" class="form-input" id="rpRoundName" value="' + (d.round_name||'') + '"></div>';
    html += '<div class="form-group"><label class="form-label">总金额</label><input type="number" class="form-input" id="rpRoundAmt" value="' + (d.total_amount||100) + '" step="0.01"></div>';
    html += '<div class="form-group"><label class="form-label">红包数量</label><input type="number" class="form-input" id="rpRoundNum" value="' + (d.total_num||50) + '"></div>';
    html += '</div><div class="modal-footer"><button class="btn btn-default" onclick="closeModal(\'rpRoundModal\')">取消</button>';
    html += '<button class="btn btn-primary" onclick="saveRpRound(' + aid + ')">保存</button></div></div></div>';
    document.body.insertAdjacentHTML('beforeend', html);
}

function saveRpRound(aid) {
    var id = document.getElementById('rpRoundEditId').value;
    var data = { round_name: document.getElementById('rpRoundName').value, total_amount: document.getElementById('rpRoundAmt').value, total_num: document.getElementById('rpRoundNum').value };
    var p = id ? HdApi.updateRedpacketRound(aid, id, data) : HdApi.createRedpacketRound(aid, data);
    p.then(function(res) {
        if (res.code===0) { showToast(id?'已更新':'已添加'); removeDynModal('rpRoundModal'); loadRedpacketTab(aid, document.getElementById('actTabContent')); }
        else showToast(res.msg||'操作失败', 'error');
    });
}

function editRpRound(aid, id) {
    HdApi.getRedpacketRounds(aid).then(function(res) {
        var list = (res.code===0 && res.data) ? (res.data.list||[]) : [];
        var found = list.find(function(x){return x.id==id;});
        if (found) showRpRoundModal(aid, found);
    });
}

function deleteRpRound(aid, id) {
    if (!confirm('确定删除该轮次？')) return;
    HdApi.deleteRedpacketRound(aid, id).then(function(res) {
        if (res.code===0) { showToast('已删除'); loadRedpacketTab(aid, document.getElementById('actTabContent')); }
        else showToast(res.msg||'删除失败', 'error');
    });
}

// ============================================================
// 弹幕互动
// ============================================================
function loadWallTab(aid, container) {
    Promise.all([
        HdApi.getWallConfig(aid),
        HdApi.getDanmuConfig(aid)
    ]).then(function(results) {
        var wall = (results[0].code===0 && results[0].data) ? results[0].data : {};
        var danmu = (results[1].code===0 && results[1].data) ? results[1].data : {};
        var html = '';

        // 上墙设置
        html += '<div class="card"><div class="card-header"><h3>💬 上墙设置</h3></div><div class="card-body">';
        html += '<div class="config-grid">';
        html += '<div class="form-group"><label class="form-label">是否需要审核</label><select class="form-input" id="wallApprove"><option value="0"' + (wall.need_approve==0?' selected':'') + '>否(直接上墙)</option><option value="1"' + (wall.need_approve==1?' selected':'') + '>是(审核后上墙)</option></select></div>';
        html += '<div class="form-group"><label class="form-label">允许发图片</label><select class="form-input" id="wallAllowImg"><option value="0"' + (wall.allow_image==0?' selected':'') + '>否</option><option value="1"' + (wall.allow_image==1?' selected':'') + '>是</option></select></div>';
        html += '<div class="form-group"><label class="form-label">背景图片</label><input type="text" class="form-input" id="wallBgImage" value="' + (wall.bg_image||'') + '" placeholder="URL地址"></div>';
        html += '</div><button class="btn btn-primary" onclick="saveWallConfig(' + aid + ')">保存上墙设置</button></div></div>';

        // 弹幕配置
        html += '<div class="card" style="margin-top:20px"><div class="card-header"><h3>💭 弹幕配置</h3></div><div class="card-body">';
        html += '<div class="config-grid">';
        html += '<div class="form-group"><label class="form-label">弹幕速度</label><input type="number" class="form-input" id="danmuSpeed" value="' + (danmu.speed||5) + '"></div>';
        html += '<div class="form-group"><label class="form-label">字体大小</label><input type="number" class="form-input" id="danmuFontSize" value="' + (danmu.font_size||24) + '"></div>';
        html += '<div class="form-group"><label class="form-label">透明度(%)</label><input type="number" class="form-input" id="danmuOpacity" value="' + (danmu.opacity||100) + '" min="0" max="100"></div>';
        html += '</div><button class="btn btn-primary" onclick="saveDanmuConfig(' + aid + ')">保存弹幕配置</button></div></div>';

        // 消息列表
        html += '<div class="card" style="margin-top:20px"><div class="card-header"><h3>📨 消息列表</h3>';
        html += '<div style="display:flex;gap:8px">';
        html += '<button class="btn btn-default btn-sm" onclick="loadWallMessages(' + aid + ')">刷新</button>';
        html += '<button class="btn btn-primary btn-sm" onclick="showNoticeForm(' + aid + ')">发布公告</button></div></div>';
        html += '<div class="card-body"><div id="noticeFormArea"></div><div id="wallMsgList">加载中...</div></div></div>';

        container.innerHTML = html;
        loadWallMessages(aid);
    });
}

function saveWallConfig(aid) {
    HdApi.updateWallConfig(aid, {
        need_approve: document.getElementById('wallApprove').value,
        allow_image: document.getElementById('wallAllowImg').value,
        bg_image: document.getElementById('wallBgImage').value
    }).then(function(res) {
        showToast(res.code===0 ? '上墙设置已保存' : (res.msg||'保存失败'), res.code===0 ? 'success' : 'error');
    });
}

function saveDanmuConfig(aid) {
    HdApi.updateDanmuConfig(aid, {
        speed: document.getElementById('danmuSpeed').value,
        font_size: document.getElementById('danmuFontSize').value,
        opacity: document.getElementById('danmuOpacity').value
    }).then(function(res) {
        showToast(res.code===0 ? '弹幕配置已保存' : (res.msg||'保存失败'), res.code===0 ? 'success' : 'error');
    });
}

function loadWallMessages(aid) {
    HdApi.getWallMessages(aid, 1).then(function(res) {
        var list = (res.code===0 && res.data) ? (res.data.list||[]) : [];
        if (list.length === 0) {
            document.getElementById('wallMsgList').innerHTML = '<div class="empty-state"><p>暂无消息</p></div>';
            return;
        }
        var html = '';
        for (var i = 0; i < list.length; i++) {
            var m = list[i];
            var cls = m.is_approved == 1 ? 'approved' : 'pending';
            html += '<div class="msg-card ' + cls + '">';
            html += '<div class="msg-avatar">💬</div><div class="msg-body">';
            html += '<div class="msg-content">' + (m.content||'') + '</div>';
            html += '<div class="msg-footer"><span class="msg-time">' + formatTime(m.createtime) + (m.is_top ? ' 📌置顶' : '') + '</span>';
            html += '<div class="msg-actions">';
            if (m.is_approved != 1) html += '<button class="btn btn-success btn-sm" onclick="approveMsg(' + aid + ',' + m.id + ')">通过</button> ';
            html += '<button class="btn btn-default btn-sm" onclick="toggleMsgTop(' + aid + ',' + m.id + ')">' + (m.is_top ? '取消置顶' : '置顶') + '</button> ';
            html += '<button class="btn btn-danger btn-sm" onclick="delWallMsg(' + aid + ',' + m.id + ')">删除</button>';
            html += '</div></div></div></div>';
        }
        document.getElementById('wallMsgList').innerHTML = html;
    });
}

function approveMsg(aid, id) {
    HdApi.approveWallMessage(aid, id).then(function(res) {
        showToast(res.code===0 ? '已通过' : (res.msg||'操作失败'), res.code===0 ? 'success' : 'error');
        loadWallMessages(aid);
    });
}

function toggleMsgTop(aid, id) {
    HdApi.toggleWallTop(aid, id).then(function(res) {
        showToast(res.code===0 ? '操作成功' : (res.msg||'操作失败'), res.code===0 ? 'success' : 'error');
        loadWallMessages(aid);
    });
}

function delWallMsg(aid, id) {
    if (!confirm('确定删除该消息？')) return;
    HdApi.deleteWallMessage(aid, id).then(function(res) {
        if (res.code===0) { showToast('已删除'); loadWallMessages(aid); }
        else showToast(res.msg||'删除失败', 'error');
    });
}

function showNoticeForm(aid) {
    var html = '<div style="background:#eef2ff;border-radius:8px;padding:16px;margin-bottom:16px">';
    html += '<div class="form-group"><label class="form-label">公告内容</label>';
    html += '<textarea class="form-input" id="noticeContent" rows="3" placeholder="输入公告内容..."></textarea></div>';
    html += '<button class="btn btn-primary" onclick="publishNotice(' + aid + ')">发布公告</button> ';
    html += '<button class="btn btn-default" onclick="document.getElementById(\'noticeFormArea\').innerHTML=\'\'">取消</button></div>';
    document.getElementById('noticeFormArea').innerHTML = html;
}

function publishNotice(aid) {
    var content = document.getElementById('noticeContent').value.trim();
    if (!content) { showToast('请输入公告内容', 'error'); return; }
    HdApi.publishNotice(aid, {content: content}).then(function(res) {
        if (res.code===0) {
            showToast('公告已发布');
            document.getElementById('noticeFormArea').innerHTML = '';
            loadWallMessages(aid);
        } else showToast(res.msg||'发布失败', 'error');
    });
}

// ============================================================
// 主题展示
// ============================================================
function loadThemeTab(aid, container) {
    Promise.all([
        HdApi.getKaimuConfig(aid),
        HdApi.getBimuConfig(aid),
        HdApi.getBackgrounds(aid),
        HdApi.getMusics(aid)
    ]).then(function(results) {
        var kaimu = (results[0].code===0 && results[0].data) ? results[0].data : {};
        var bimu = (results[1].code===0 && results[1].data) ? results[1].data : {};
        var bgs = (results[2].code===0 && results[2].data) ? (results[2].data.list||[]) : [];
        var musics = (results[3].code===0 && results[3].data) ? (results[3].data.list||[]) : [];

        var html = '';
        // 开幕墙
        html += '<div class="card"><div class="card-header"><h3>🎤 开幕墙</h3></div><div class="card-body">';
        html += '<div class="config-grid">';
        html += '<div class="form-group"><label class="form-label">标题</label><input type="text" class="form-input" id="kaimuTitle" value="' + (kaimu.title||'') + '"></div>';
        html += '<div class="form-group"><label class="form-label">副标题</label><input type="text" class="form-input" id="kaimuSubtitle" value="' + (kaimu.subtitle||'') + '"></div>';
        html += '<div class="form-group"><label class="form-label">背景图</label><input type="text" class="form-input" id="kaimuBg" value="' + (kaimu.bg_image||'') + '" placeholder="URL"></div>';
        html += '<div class="form-group"><label class="form-label">背景音乐</label><input type="text" class="form-input" id="kaimuMusic" value="' + (kaimu.music_url||'') + '" placeholder="URL"></div>';
        html += '</div><button class="btn btn-primary" onclick="saveKaimuConfig(' + aid + ')">保存开幕墙设置</button></div></div>';

        // 闭幕墙
        html += '<div class="card" style="margin-top:20px"><div class="card-header"><h3>🎬 闭幕墙</h3></div><div class="card-body">';
        html += '<div class="config-grid">';
        html += '<div class="form-group"><label class="form-label">标题</label><input type="text" class="form-input" id="bimuTitle" value="' + (bimu.title||'') + '"></div>';
        html += '<div class="form-group"><label class="form-label">副标题</label><input type="text" class="form-input" id="bimuSubtitle" value="' + (bimu.subtitle||'') + '"></div>';
        html += '<div class="form-group"><label class="form-label">背景图</label><input type="text" class="form-input" id="bimuBg" value="' + (bimu.bg_image||'') + '" placeholder="URL"></div>';
        html += '</div><button class="btn btn-primary" onclick="saveBimuConfig(' + aid + ')">保存闭幕墙设置</button></div></div>';

        // 背景管理
        html += '<div class="card" style="margin-top:20px"><div class="card-header"><h3>🖼️ 背景管理</h3></div>';
        html += '<div class="card-body">';
        if (bgs.length === 0) {
            html += '<div class="empty-state"><p>暂无背景</p></div>';
        } else {
            html += '<div class="item-grid">';
            for (var i = 0; i < bgs.length; i++) {
                var b = bgs[i];
                html += '<div class="item-card"><div class="item-title">' + (b.name||b.scene||'背景'+(i+1)) + '</div>';
                if (b.image_url||b.url) html += '<div class="item-meta"><img src="' + (b.image_url||b.url) + '" style="max-height:60px;border-radius:4px"></div>';
                html += '<div class="item-actions"><button class="btn btn-danger btn-sm" onclick="deleteBg(' + aid + ',' + b.id + ')">删除</button></div></div>';
            }
            html += '</div>';
        }
        html += '</div></div>';

        // 音乐管理
        html += '<div class="card" style="margin-top:20px"><div class="card-header"><h3>🎵 音乐管理</h3></div>';
        html += '<div class="card-body">';
        if (musics.length === 0) {
            html += '<div class="empty-state"><p>暂无音乐</p></div>';
        } else {
            html += '<table class="data-table"><thead><tr><th>名称</th><th>URL</th><th>操作</th></tr></thead><tbody>';
            for (var i = 0; i < musics.length; i++) {
                var mu = musics[i];
                html += '<tr><td>' + (mu.name||mu.title||'音乐'+(i+1)) + '</td>';
                html += '<td style="max-width:200px;overflow:hidden;text-overflow:ellipsis">' + (mu.url||mu.music_url||'-') + '</td>';
                html += '<td><button class="btn btn-danger btn-sm" onclick="deleteMusic(' + aid + ',' + mu.id + ')">删除</button></td></tr>';
            }
            html += '</tbody></table>';
        }
        html += '</div></div>';

        container.innerHTML = html;
    });
}

function saveKaimuConfig(aid) {
    HdApi.updateKaimuConfig(aid, {
        title: document.getElementById('kaimuTitle').value,
        subtitle: document.getElementById('kaimuSubtitle').value,
        bg_image: document.getElementById('kaimuBg').value,
        music_url: document.getElementById('kaimuMusic').value
    }).then(function(res) {
        showToast(res.code===0 ? '开幕墙设置已保存' : (res.msg||'保存失败'), res.code===0 ? 'success' : 'error');
    });
}

function saveBimuConfig(aid) {
    HdApi.updateBimuConfig(aid, {
        title: document.getElementById('bimuTitle').value,
        subtitle: document.getElementById('bimuSubtitle').value,
        bg_image: document.getElementById('bimuBg').value
    }).then(function(res) {
        showToast(res.code===0 ? '闭幕墙设置已保存' : (res.msg||'保存失败'), res.code===0 ? 'success' : 'error');
    });
}

function deleteBg(aid, id) {
    if (!confirm('确定删除该背景？')) return;
    HdApi.deleteBgItem(aid, id).then(function(res) {
        if (res.code===0) { showToast('已删除'); loadThemeTab(aid, document.getElementById('actTabContent')); }
        else showToast(res.msg||'删除失败', 'error');
    });
}

function deleteMusic(aid, id) {
    if (!confirm('确定删除该音乐？')) return;
    HdApi.deleteMusicItem(aid, id).then(function(res) {
        if (res.code===0) { showToast('已删除'); loadThemeTab(aid, document.getElementById('actTabContent')); }
        else showToast(res.msg||'删除失败', 'error');
    });
}

// ============================================================
// 相册PPT
// ============================================================
function loadAlbumTab(aid, container) {
    Promise.all([
        HdApi.getAlbumConfig(aid),
        HdApi.getAlbumPhotos(aid)
    ]).then(function(results) {
        var cfg = (results[0].code===0 && results[0].data) ? results[0].data : {};
        var photos = (results[1].code===0 && results[1].data) ? (results[1].data.list||[]) : [];

        var html = '';
        html += '<div class="card"><div class="card-header"><h3>📷 相册设置</h3></div><div class="card-body">';
        html += '<div class="config-grid">';
        html += '<div class="form-group"><label class="form-label">播放速度(秒)</label><input type="number" class="form-input" id="albumSpeed" value="' + (cfg.speed||5) + '"></div>';
        html += '<div class="form-group"><label class="form-label">切换动画</label><select class="form-input" id="albumTransition"><option value="fade"' + (cfg.transition==='fade'?' selected':'') + '>淡入淡出</option><option value="slide"' + (cfg.transition==='slide'?' selected':'') + '>滑动</option><option value="zoom"' + (cfg.transition==='zoom'?' selected':'') + '>缩放</option></select></div>';
        html += '<div class="form-group"><label class="form-label">背景音乐</label><input type="text" class="form-input" id="albumMusic" value="' + (cfg.music_url||'') + '" placeholder="URL"></div>';
        html += '</div><button class="btn btn-primary" onclick="saveAlbumConfig(' + aid + ')">保存设置</button></div></div>';

        // 照片管理
        html += '<div class="card" style="margin-top:20px"><div class="card-header"><h3>🖼️ 照片管理 (' + photos.length + '张)</h3>';
        html += '<div style="display:flex;gap:8px">';
        html += '<button class="btn btn-primary btn-sm" onclick="showAddPhotoForm(' + aid + ')">添加照片</button>';
        html += '<button class="btn btn-danger btn-sm" onclick="clearAlbum(' + aid + ')">清空相册</button></div></div>';
        html += '<div class="card-body"><div id="photoFormArea"></div>';
        if (photos.length === 0) {
            html += '<div class="empty-state"><p>暂无照片，请添加</p></div>';
        } else {
            html += '<div class="item-grid">';
            for (var i = 0; i < photos.length; i++) {
                var p = photos[i];
                html += '<div class="item-card">';
                if (p.image_url||p.url) html += '<img src="' + (p.image_url||p.url) + '" style="width:100%;height:120px;object-fit:cover;border-radius:6px;margin-bottom:8px">';
                html += '<div class="item-actions"><button class="btn btn-danger btn-sm" onclick="deletePhoto(' + aid + ',' + p.id + ')">删除</button></div></div>';
            }
            html += '</div>';
        }
        html += '</div></div>';

        container.innerHTML = html;
    });
}

function saveAlbumConfig(aid) {
    HdApi.updateAlbumConfig(aid, {
        speed: document.getElementById('albumSpeed').value,
        transition: document.getElementById('albumTransition').value,
        music_url: document.getElementById('albumMusic').value
    }).then(function(res) {
        showToast(res.code===0 ? '相册设置已保存' : (res.msg||'保存失败'), res.code===0 ? 'success' : 'error');
    });
}

function showAddPhotoForm(aid) {
    var html = '<div style="background:#eef2ff;border-radius:8px;padding:16px;margin-bottom:16px">';
    html += '<div class="form-group"><label class="form-label">图片URL</label>';
    html += '<input type="text" class="form-input" id="photoUrl" placeholder="输入图片URL地址"></div>';
    html += '<button class="btn btn-primary" onclick="addPhoto(' + aid + ')">添加</button> ';
    html += '<button class="btn btn-default" onclick="document.getElementById(\'photoFormArea\').innerHTML=\'\'">取消</button></div>';
    document.getElementById('photoFormArea').innerHTML = html;
}

function addPhoto(aid) {
    var url = document.getElementById('photoUrl').value.trim();
    if (!url) { showToast('请输入图片URL', 'error'); return; }
    HdApi.addAlbumPhoto(aid, {image_url: url}).then(function(res) {
        if (res.code===0) { showToast('已添加'); loadAlbumTab(aid, document.getElementById('actTabContent')); }
        else showToast(res.msg||'添加失败', 'error');
    });
}

function deletePhoto(aid, id) {
    if (!confirm('确定删除该照片？')) return;
    HdApi.deleteAlbumPhoto(aid, id).then(function(res) {
        if (res.code===0) { showToast('已删除'); loadAlbumTab(aid, document.getElementById('actTabContent')); }
        else showToast(res.msg||'删除失败', 'error');
    });
}

function clearAlbum(aid) {
    if (!confirm('确定清空所有照片？此操作不可撤销！')) return;
    HdApi.clearAlbum(aid).then(function(res) {
        if (res.code===0) { showToast('已清空'); loadAlbumTab(aid, document.getElementById('actTabContent')); }
        else showToast(res.msg||'操作失败', 'error');
    });
}

// ============================================================
// 投票设置
// ============================================================
function loadVoteTab(aid, container) {
    Promise.all([
        HdApi.getVoteItems(aid),
        HdApi.getVoteStats(aid)
    ]).then(function(results) {
        var items = (results[0].code===0 && results[0].data) ? (results[0].data.list||[]) : [];
        var stats = (results[1].code===0 && results[1].data) ? results[1].data : {};

        var html = '';
        // 投票统计
        html += '<div class="card"><div class="card-header"><h3>🗳️ 投票统计</h3>';
        html += '<button class="btn btn-danger btn-sm" onclick="resetVotesAction(' + aid + ')">重置投票数据</button></div>';
        html += '<div class="card-body"><div style="display:flex;gap:24px;flex-wrap:wrap">';
        html += '<div><strong>总投票数：</strong>' + (stats.total_votes||0) + '</div>';
        html += '<div><strong>参与人数：</strong>' + (stats.total_voters||0) + '</div>';
        html += '</div></div></div>';

        // 投票选项
        html += '<div class="card" style="margin-top:20px"><div class="card-header"><h3>📝 投票选项</h3>';
        html += '<button class="btn btn-primary btn-sm" onclick="showVoteItemForm(' + aid + ')">添加选项</button></div>';
        html += '<div class="card-body"><div id="voteFormArea"></div>';
        if (items.length === 0) {
            html += '<div class="empty-state"><p>暂无投票选项</p></div>';
        } else {
            html += '<div class="item-grid">';
            for (var i = 0; i < items.length; i++) {
                var v = items[i];
                html += '<div class="item-card">';
                html += '<div class="item-title">' + v.title + '</div>';
                html += '<div class="item-meta">得票数：' + (v.vote_count||0) + '</div>';
                if (v.image) html += '<div class="item-meta"><img src="' + v.image + '" style="height:50px;border-radius:4px"></div>';
                html += '<div class="item-actions">';
                html += '<button class="btn btn-danger btn-sm" onclick="deleteVoteItem(' + aid + ',' + v.id + ')">删除</button>';
                html += '</div></div>';
            }
            html += '</div>';
        }
        html += '</div></div>';

        container.innerHTML = html;
    });
}

function showVoteItemForm(aid) {
    var html = '<div style="background:#eef2ff;border-radius:8px;padding:16px;margin-bottom:16px">';
    html += '<div class="form-group"><label class="form-label">选项标题</label>';
    html += '<input type="text" class="form-input" id="voteItemTitle" placeholder="输入选项标题"></div>';
    html += '<div class="form-group"><label class="form-label">图片URL(可选)</label>';
    html += '<input type="text" class="form-input" id="voteItemImage" placeholder="URL地址"></div>';
    html += '<button class="btn btn-primary" onclick="addVoteItem(' + aid + ')">添加</button> ';
    html += '<button class="btn btn-default" onclick="document.getElementById(\'voteFormArea\').innerHTML=\'\'">取消</button></div>';
    document.getElementById('voteFormArea').innerHTML = html;
}

function addVoteItem(aid) {
    var title = document.getElementById('voteItemTitle').value.trim();
    if (!title) { showToast('请输入选项标题', 'error'); return; }
    var data = { title: title, image: document.getElementById('voteItemImage').value.trim() };
    HdApi.createVoteItem(aid, data).then(function(res) {
        if (res.code===0) { showToast('已添加'); loadVoteTab(aid, document.getElementById('actTabContent')); }
        else showToast(res.msg||'添加失败', 'error');
    });
}

function deleteVoteItem(aid, id) {
    if (!confirm('确定删除该投票选项？')) return;
    HdApi.deleteVoteItem(aid, id).then(function(res) {
        if (res.code===0) { showToast('已删除'); loadVoteTab(aid, document.getElementById('actTabContent')); }
        else showToast(res.msg||'删除失败', 'error');
    });
}

function resetVotesAction(aid) {
    if (!confirm('确定重置所有投票数据？此操作不可撤销！')) return;
    HdApi.resetVotes(aid).then(function(res) {
        if (res.code===0) { showToast('已重置'); loadVoteTab(aid, document.getElementById('actTabContent')); }
        else showToast(res.msg||'操作失败', 'error');
    });
}

// ============================================================
// 功能开关
// ============================================================
function loadSwitchTab(aid, container) {
    HdApi.getFeatureSwitches(aid).then(function(res) {
        var list = (res.code===0 && res.data) ? (res.data.list||[]) : [];

        var html = '<div class="card"><div class="card-header"><h3>🔘 功能开关</h3></div>';
        html += '<div class="card-body" style="padding:0">';

        if (list.length === 0) {
            html += '<div class="empty-state"><p>暂无功能配置</p></div>';
        } else {
            html += '<div class="feature-switch-list">';
            for (var i = 0; i < list.length; i++) {
                var f = list[i];
                html += '<div class="feature-switch-item">';
                html += '<div><div class="feature-name">' + (f.feature_name||f.feature_code) + '</div>';
                html += '<div class="feature-code">' + f.feature_code + '</div></div>';
                html += '<label class="switch-toggle">';
                html += '<input type="checkbox"' + (f.enabled==1?' checked':'') + ' onchange="toggleFeatureSwitch(' + aid + ',\'' + f.feature_code + '\')">';
                html += '<span class="slider"></span></label>';
                html += '</div>';
            }
            html += '</div>';
        }
        html += '</div></div>';

        container.innerHTML = html;
    });
}

function toggleFeatureSwitch(aid, code) {
    HdApi.toggleFeature(aid, code).then(function(res) {
        showToast(res.code===0 ? (res.msg||'已切换') : (res.msg||'操作失败'), res.code===0 ? 'success' : 'error');
    });
}

// ============================================================
// 内容安全管理
// ============================================================
function loadSecurityTab(aid, container) {
    Promise.all([
        HdApi.getSecurityConfig(aid),
        HdApi.getKeywords(aid),
        HdApi.getBanList(aid)
    ]).then(function(results) {
        var cfg = (results[0].code === 0 && results[0].data) ? results[0].data : {};
        var keywords = (results[1].code === 0 && results[1].data) ? (results[1].data.list || []) : [];
        var bans = (results[2].code === 0 && results[2].data) ? (results[2].data.list || []) : [];

        var html = '';

        // 安全开关设置
        html += '<div class="card"><div class="card-header"><h3>🛡️ 安全设置</h3></div><div class="card-body">';
        html += '<div class="config-grid">';
        html += '<div class="form-group"><label class="form-label">内容过滤</label><select class="form-input" id="secFilterEnabled"><option value="1"' + (cfg.filter_enabled==1?' selected':'') + '>开启</option><option value="0"' + (cfg.filter_enabled==0?' selected':'') + '>关闭</option></select></div>';
        html += '<div class="form-group"><label class="form-label">人工审核</label><select class="form-input" id="secNeedApprove"><option value="0"' + (cfg.need_approve==0?' selected':'') + '>关闭(消息直接显示)</option><option value="1"' + (cfg.need_approve==1?' selected':'') + '>开启(先审后显)</option></select></div>';
        html += '<div class="form-group"><label class="form-label">最大消息长度</label><input type="number" class="form-input" id="secMaxLen" value="' + (cfg.max_msg_length||200) + '" min="10" max="500"></div>';
        html += '<div class="form-group"><label class="form-label">发送间隔(秒)</label><input type="number" class="form-input" id="secInterval" value="' + (cfg.msg_interval||3) + '" min="0" max="60"></div>';
        html += '</div>';
        html += '<div style="display:flex;gap:12px;margin-top:16px">';
        html += '<button class="btn btn-primary" onclick="saveSecurityConfig(' + aid + ')">保存设置</button>';
        html += '<button class="btn ' + (cfg.global_mute ? 'btn-danger' : 'btn-default') + '" id="btnGlobalMute" onclick="toggleGlobalMuteAction(' + aid + ')">' + (cfg.global_mute ? '🔇 解除全局禁言' : '🔈 一键全局禁言') + '</button>';
        html += '</div></div></div>';

        // 关键词管理
        html += '<div class="card" style="margin-top:20px"><div class="card-header"><h3>🚫 关键词过滤</h3>';
        html += '<div style="display:flex;gap:8px"><button class="btn btn-primary btn-sm" onclick="showAddKeywordForm(' + aid + ')">+ 添加</button>';
        html += '<button class="btn btn-default btn-sm" onclick="showBatchKeywordForm(' + aid + ')">批量添加</button></div></div>';
        html += '<div class="card-body"><div id="keywordFormArea"></div>';
        if (keywords.length === 0) {
            html += '<div class="empty-state"><p>暂无过滤关键词，系统已内置基础敏感词库</p></div>';
        } else {
            html += '<div class="card-body no-padding"><table class="data-table"><thead><tr><th>关键词</th><th>匹配方式</th><th>命中动作</th><th>状态</th><th>操作</th></tr></thead><tbody>';
            var matchTypes = {1:'包含匹配', 2:'完全匹配', 3:'正则匹配'};
            var actionTypes = {1:'替换为***', 2:'拒绝发送', 3:'转人工审核'};
            for (var i = 0; i < keywords.length; i++) {
                var kw = keywords[i];
                html += '<tr><td><code>' + kw.keyword + '</code></td>';
                html += '<td>' + (matchTypes[kw.match_type]||'包含') + '</td>';
                html += '<td>' + (actionTypes[kw.action]||'替换') + '</td>';
                html += '<td>' + (kw.enabled ? '<span class="badge badge-success">启用</span>' : '<span class="badge badge-danger">禁用</span>') + '</td>';
                html += '<td><button class="btn btn-default btn-sm" onclick="toggleKwAction(' + aid + ',' + kw.id + ')">' + (kw.enabled?'禁用':'启用') + '</button> ';
                html += '<button class="btn btn-danger btn-sm" onclick="deleteKwAction(' + aid + ',' + kw.id + ')">删除</button></td></tr>';
            }
            html += '</tbody></table></div>';
        }
        html += '</div></div>';

        // 用户禁言名单
        html += '<div class="card" style="margin-top:20px"><div class="card-header"><h3>🚷 禁言名单</h3>';
        html += '<button class="btn btn-primary btn-sm" onclick="showBanUserForm(' + aid + ')">+ 添加禁言</button></div>';
        html += '<div class="card-body"><div id="banFormArea"></div>';
        if (bans.length === 0) {
            html += '<div class="empty-state"><p>暂无禁言用户</p></div>';
        } else {
            html += '<table class="data-table"><thead><tr><th>用户标识</th><th>昵称</th><th>原因</th><th>类型</th><th>过期时间</th><th>操作</th></tr></thead><tbody>';
            for (var i = 0; i < bans.length; i++) {
                var b = bans[i];
                var banType = b.ban_type == 2 ? '屏蔽' : '禁言';
                var expStr = b.expired_at ? formatTime(b.expired_at) : '永久';
                html += '<tr><td><code>' + (b.openid||'').substring(0,12) + '...</code></td>';
                html += '<td>' + (b.nickname||'-') + '</td>';
                html += '<td>' + (b.reason||'-') + '</td>';
                html += '<td>' + banType + '</td>';
                html += '<td>' + expStr + '</td>';
                html += '<td><button class="btn btn-default btn-sm" onclick="unbanAction(' + aid + ',' + b.id + ')">解除</button></td></tr>';
            }
            html += '</tbody></table>';
        }
        html += '</div></div>';

        container.innerHTML = html;
    });
}

function saveSecurityConfig(aid) {
    var data = {
        filter_enabled: document.getElementById('secFilterEnabled').value,
        need_approve: document.getElementById('secNeedApprove').value,
        max_msg_length: document.getElementById('secMaxLen').value,
        msg_interval: document.getElementById('secInterval').value
    };
    HdApi.updateSecurityConfig(aid, data).then(function(res) {
        showToast(res.code === 0 ? '安全设置已保存' : (res.msg||'保存失败'), res.code === 0 ? 'success' : 'error');
    });
}

function toggleGlobalMuteAction(aid) {
    HdApi.toggleGlobalMute(aid).then(function(res) {
        if (res.code === 0) {
            showToast(res.msg || '操作成功');
            loadSecurityTab(aid, document.getElementById('actTabContent'));
        } else {
            showToast(res.msg||'操作失败', 'error');
        }
    });
}

function showAddKeywordForm(aid) {
    var html = '<div style="background:#eef2ff;border-radius:8px;padding:16px;margin-bottom:16px">';
    html += '<div class="config-grid">';
    html += '<div class="form-group"><label class="form-label">关键词</label><input type="text" class="form-input" id="kwWord" placeholder="输入关键词"></div>';
    html += '<div class="form-group"><label class="form-label">匹配方式</label><select class="form-input" id="kwMatchType"><option value="1">包含匹配</option><option value="2">完全匹配</option><option value="3">正则匹配</option></select></div>';
    html += '<div class="form-group"><label class="form-label">命中动作</label><select class="form-input" id="kwAction"><option value="1">替换为***</option><option value="2">拒绝发送</option><option value="3">转人工审核</option></select></div>';
    html += '</div>';
    html += '<button class="btn btn-primary" onclick="addKwAction(' + aid + ')">添加</button> ';
    html += '<button class="btn btn-default" onclick="document.getElementById(\'keywordFormArea\').innerHTML=\'\'">取消</button></div>';
    document.getElementById('keywordFormArea').innerHTML = html;
}

function showBatchKeywordForm(aid) {
    var html = '<div style="background:#eef2ff;border-radius:8px;padding:16px;margin-bottom:16px">';
    html += '<div class="form-group"><label class="form-label">批量关键词（每行一个，或用逗号分隔）</label>';
    html += '<textarea class="form-input" id="kwBatchText" rows="5" placeholder="敏感词1\n敏感词2\n敏感词3"></textarea></div>';
    html += '<p style="font-size:12px;color:#9ca3af;margin-bottom:12px">默认使用「包含匹配 + 替换为***」规则</p>';
    html += '<button class="btn btn-primary" onclick="batchAddKwAction(' + aid + ')">批量添加</button> ';
    html += '<button class="btn btn-default" onclick="document.getElementById(\'keywordFormArea\').innerHTML=\'\'">取消</button></div>';
    document.getElementById('keywordFormArea').innerHTML = html;
}

function addKwAction(aid) {
    var keyword = document.getElementById('kwWord').value.trim();
    if (!keyword) { showToast('请输入关键词', 'error'); return; }
    var data = {
        keyword: keyword,
        match_type: document.getElementById('kwMatchType').value,
        action: document.getElementById('kwAction').value
    };
    HdApi.addKeyword(aid, data).then(function(res) {
        if (res.code === 0) { showToast('已添加'); loadSecurityTab(aid, document.getElementById('actTabContent')); }
        else showToast(res.msg||'添加失败', 'error');
    });
}

function batchAddKwAction(aid) {
    var text = document.getElementById('kwBatchText').value.trim();
    if (!text) { showToast('请输入关键词', 'error'); return; }
    HdApi.batchAddKeywords(aid, { keywords_text: text }).then(function(res) {
        if (res.code === 0) { showToast(res.msg||'批量添加成功'); loadSecurityTab(aid, document.getElementById('actTabContent')); }
        else showToast(res.msg||'添加失败', 'error');
    });
}

function toggleKwAction(aid, id) {
    HdApi.toggleKeyword(aid, id).then(function(res) {
        if (res.code === 0) { showToast(res.msg||'已切换'); loadSecurityTab(aid, document.getElementById('actTabContent')); }
        else showToast(res.msg||'操作失败', 'error');
    });
}

function deleteKwAction(aid, id) {
    if (!confirm('确定删除该关键词？')) return;
    HdApi.deleteKeyword(aid, id).then(function(res) {
        if (res.code === 0) { showToast('已删除'); loadSecurityTab(aid, document.getElementById('actTabContent')); }
        else showToast(res.msg||'删除失败', 'error');
    });
}

function showBanUserForm(aid) {
    var html = '<div style="background:#fef2f2;border-radius:8px;padding:16px;margin-bottom:16px">';
    html += '<div class="config-grid">';
    html += '<div class="form-group"><label class="form-label">用户标识(openid)</label><input type="text" class="form-input" id="banOpenid" placeholder="粘贴用户openid"></div>';
    html += '<div class="form-group"><label class="form-label">昵称(可选)</label><input type="text" class="form-input" id="banNickname" placeholder="用户昵称"></div>';
    html += '<div class="form-group"><label class="form-label">原因</label><input type="text" class="form-input" id="banReason" value="违规发言" placeholder="禁言原因"></div>';
    html += '<div class="form-group"><label class="form-label">时长(分钟，空=永久)</label><input type="number" class="form-input" id="banDuration" placeholder="留空永久禁言"></div>';
    html += '</div>';
    html += '<button class="btn btn-danger" onclick="banUserAction(' + aid + ')">确认禁言</button> ';
    html += '<button class="btn btn-default" onclick="document.getElementById(\'banFormArea\').innerHTML=\'\'">取消</button></div>';
    document.getElementById('banFormArea').innerHTML = html;
}

function banUserAction(aid) {
    var openid = document.getElementById('banOpenid').value.trim();
    if (!openid) { showToast('请输入用户标识', 'error'); return; }
    var data = {
        openid: openid,
        nickname: document.getElementById('banNickname').value.trim(),
        reason: document.getElementById('banReason').value.trim(),
        duration: document.getElementById('banDuration').value || ''
    };
    HdApi.banUser(aid, data).then(function(res) {
        if (res.code === 0) { showToast('已禁言'); loadSecurityTab(aid, document.getElementById('actTabContent')); }
        else showToast(res.msg||'操作失败', 'error');
    });
}

function unbanAction(aid, id) {
    if (!confirm('确定解除该用户禁言？')) return;
    HdApi.unbanUser(aid, id).then(function(res) {
        if (res.code === 0) { showToast('已解除'); loadSecurityTab(aid, document.getElementById('actTabContent')); }
        else showToast(res.msg||'操作失败', 'error');
    });
}

// ============================================================
// 品牌定制管理
// ============================================================
function loadBrandTab(aid, container) {
    HdApi.getBrandConfig(aid).then(function(res) {
        var cfg = (res.code === 0 && res.data) ? res.data : {};
        var presets = cfg.animation_presets || {};

        var html = '';

        // 品牌信息
        html += '<div class="card"><div class="card-header"><h3>🏷️ 品牌信息</h3></div><div class="card-body">';
        html += '<div class="config-grid">';
        html += '<div class="form-group"><label class="form-label">品牌名称</label><input type="text" class="form-input" id="brandName" value="' + (cfg.brand_name||'') + '" placeholder="您的品牌名"></div>';
        html += '<div class="form-group"><label class="form-label">品牌LOGO(URL)</label><input type="text" class="form-input" id="brandLogo" value="' + (cfg.brand_logo||'') + '" placeholder="https://..."></div>';
        html += '</div>';
        if (cfg.brand_logo) html += '<div style="margin-top:8px"><img src="' + cfg.brand_logo + '" style="max-height:60px;border-radius:8px;border:1px solid #e5e7eb" alt="LOGO预览"></div>';
        html += '</div></div>';

        // 主题色
        html += '<div class="card" style="margin-top:20px"><div class="card-header"><h3>🎨 主题色</h3></div><div class="card-body">';
        html += '<div class="config-grid">';
        html += '<div class="form-group"><label class="form-label">主色调</label><div style="display:flex;gap:8px;align-items:center"><input type="color" id="brandPrimary" value="' + (cfg.primary_color||'#6366f1') + '" style="width:50px;height:36px;border:none;cursor:pointer"><input type="text" class="form-input" id="brandPrimaryText" value="' + (cfg.primary_color||'#6366f1') + '" style="width:100px" onchange="document.getElementById(\'brandPrimary\').value=this.value"></div></div>';
        html += '<div class="form-group"><label class="form-label">辅色调</label><div style="display:flex;gap:8px;align-items:center"><input type="color" id="brandSecondary" value="' + (cfg.secondary_color||'#8b5cf6') + '" style="width:50px;height:36px;border:none;cursor:pointer"><input type="text" class="form-input" id="brandSecondaryText" value="' + (cfg.secondary_color||'#8b5cf6') + '" style="width:100px" onchange="document.getElementById(\'brandSecondary\').value=this.value"></div></div>';
        html += '<div class="form-group"><label class="form-label">强调色</label><div style="display:flex;gap:8px;align-items:center"><input type="color" id="brandAccent" value="' + (cfg.accent_color||'#f59e0b') + '" style="width:50px;height:36px;border:none;cursor:pointer"><input type="text" class="form-input" id="brandAccentText" value="' + (cfg.accent_color||'#f59e0b') + '" style="width:100px" onchange="document.getElementById(\'brandAccent\').value=this.value"></div></div>';
        html += '</div>';
        html += '<div style="margin-top:12px"><label class="form-label">预览效果</label><div id="brandColorPreview" style="display:flex;gap:8px;margin-top:4px"></div></div>';
        html += '</div></div>';

        // 背景设置
        html += '<div class="card" style="margin-top:20px"><div class="card-header"><h3>🖼️ 背景设置</h3></div><div class="card-body">';
        html += '<div class="config-grid">';
        html += '<div class="form-group"><label class="form-label">背景类型</label><select class="form-input" id="brandBgType" onchange="toggleBgFields()"><option value="1"' + (cfg.bg_type==1?' selected':'') + '>纯色</option><option value="2"' + (cfg.bg_type==2?' selected':'') + '>渐变</option><option value="3"' + (cfg.bg_type==3?' selected':'') + '>图片</option><option value="4"' + (cfg.bg_type==4?' selected':'') + '>视频</option></select></div>';
        html += '<div class="form-group" id="bgColorField"><label class="form-label">背景色</label><input type="color" id="brandBgColor" value="' + (cfg.bg_color||'#0a0a1a') + '" style="width:50px;height:36px;border:none;cursor:pointer"></div>';
        html += '<div class="form-group" id="bgGradientField" style="display:none"><label class="form-label">渐变CSS</label><input type="text" class="form-input" id="brandBgGradient" value="' + (cfg.bg_gradient||'') + '" placeholder="linear-gradient(135deg, #667eea, #764ba2)"></div>';
        html += '<div class="form-group" id="bgImageField" style="display:none"><label class="form-label">背景图URL</label><input type="text" class="form-input" id="brandBgImage" value="' + (cfg.bg_image||'') + '" placeholder="https://..."></div>';
        html += '<div class="form-group" id="bgVideoField" style="display:none"><label class="form-label">背景视频URL</label><input type="text" class="form-input" id="brandBgVideo" value="' + (cfg.bg_video||'') + '" placeholder="https://..."></div>';
        html += '</div></div></div>';

        // 动画风格
        html += '<div class="card" style="margin-top:20px"><div class="card-header"><h3>✨ 动画风格</h3></div><div class="card-body">';
        html += '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px">';
        var presetKeys = Object.keys(presets);
        for (var i = 0; i < presetKeys.length; i++) {
            var pk = presetKeys[i];
            var p = presets[pk];
            var sel = (cfg.animation_style === pk) ? ' style="border-color:#6366f1;background:#eef2ff"' : '';
            html += '<div class="item-card" data-anim="' + pk + '" onclick="selectAnimPreset(this,\'' + pk + '\')"' + sel + '>';
            html += '<div class="item-title">' + p.name + '</div>';
            html += '<div class="item-meta">' + p.desc + '</div>';
            html += '</div>';
        }
        html += '</div>';
        html += '<input type="hidden" id="brandAnimStyle" value="' + (cfg.animation_style||'default') + '">';
        html += '</div></div>';

        // 自定义CSS
        html += '<div class="card" style="margin-top:20px"><div class="card-header"><h3>💻 自定义CSS（高级）</h3></div><div class="card-body">';
        html += '<textarea class="form-input" id="brandCustomCss" rows="4" placeholder="输入自定义CSS代码">' + (cfg.custom_css||'') + '</textarea>';
        html += '</div></div>';

        // 保存按钮
        html += '<div style="margin-top:20px;text-align:center">';
        html += '<button class="btn btn-primary" style="min-width:200px" onclick="saveBrandConfig(' + aid + ')">💾 保存品牌设置</button>';
        html += '</div>';

        container.innerHTML = html;
        toggleBgFields();
        updateColorPreview();

        // 颜色同步
        var cp = document.getElementById('brandPrimary');
        var cs = document.getElementById('brandSecondary');
        var ca = document.getElementById('brandAccent');
        if (cp) cp.addEventListener('input', function() { document.getElementById('brandPrimaryText').value = this.value; updateColorPreview(); });
        if (cs) cs.addEventListener('input', function() { document.getElementById('brandSecondaryText').value = this.value; updateColorPreview(); });
        if (ca) ca.addEventListener('input', function() { document.getElementById('brandAccentText').value = this.value; updateColorPreview(); });
    });
}

function toggleBgFields() {
    var t = document.getElementById('brandBgType').value;
    var show = function(id, v) { var el = document.getElementById(id); if (el) el.style.display = v ? '' : 'none'; };
    show('bgColorField', t === '1');
    show('bgGradientField', t === '2');
    show('bgImageField', t === '3');
    show('bgVideoField', t === '4');
}

function updateColorPreview() {
    var p = document.getElementById('brandPrimary').value;
    var s = document.getElementById('brandSecondary').value;
    var a = document.getElementById('brandAccent').value;
    var el = document.getElementById('brandColorPreview');
    if (!el) return;
    el.innerHTML = '<div style="width:60px;height:40px;border-radius:8px;background:' + p + '"></div>' +
        '<div style="width:60px;height:40px;border-radius:8px;background:' + s + '"></div>' +
        '<div style="width:60px;height:40px;border-radius:8px;background:' + a + '"></div>' +
        '<div style="width:120px;height:40px;border-radius:8px;background:linear-gradient(135deg,' + p + ',' + s + ')"></div>';
}

function selectAnimPreset(el, style) {
    document.querySelectorAll('[data-anim]').forEach(function(c) { c.style.borderColor = ''; c.style.background = ''; });
    el.style.borderColor = '#6366f1';
    el.style.background = '#eef2ff';
    document.getElementById('brandAnimStyle').value = style;
}

function saveBrandConfig(aid) {
    var data = {
        brand_name: document.getElementById('brandName').value.trim(),
        brand_logo: document.getElementById('brandLogo').value.trim(),
        primary_color: document.getElementById('brandPrimary').value,
        secondary_color: document.getElementById('brandSecondary').value,
        accent_color: document.getElementById('brandAccent').value,
        bg_type: document.getElementById('brandBgType').value,
        bg_color: document.getElementById('brandBgColor') ? document.getElementById('brandBgColor').value : '',
        bg_gradient: document.getElementById('brandBgGradient') ? document.getElementById('brandBgGradient').value : '',
        bg_image: document.getElementById('brandBgImage') ? document.getElementById('brandBgImage').value : '',
        bg_video: document.getElementById('brandBgVideo') ? document.getElementById('brandBgVideo').value : '',
        animation_style: document.getElementById('brandAnimStyle').value,
        custom_css: document.getElementById('brandCustomCss').value
    };
    HdApi.updateBrandConfig(aid, data).then(function(res) {
        showToast(res.code === 0 ? '品牌设置已保存' : (res.msg||'保存失败'), res.code === 0 ? 'success' : 'error');
    });
}

// ============================================================
// 通用工具函数
// ============================================================
function removeDynModal(id) {
    var el = document.getElementById(id);
    if (el) el.remove();
}