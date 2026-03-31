/**
 * 艺为微信大屏互动 - 大屏端交互逻辑
 * 功能：签到轮播、抽奖动画、投票柱状图、弹幕飘屏、摇一摇排行、红包雨、开幕/闭幕墙、相册PPT、上墙消息
 */
(function () {
    'use strict';

    var API = window.HD_API_BASE || '';
    var panel = document.getElementById('screenContent');
    var currentFeature = null;
    var timers = {};
    var lotteryState = {};
    var albumState = {};
    var danmuLastId = 0;

    // ========== 工具函数 ==========
    function api(path, opts) {
        opts = opts || {};
        var url = API + path;
        var init = { method: opts.method || 'GET', headers: {} };
        if (opts.body) {
            init.headers['Content-Type'] = 'application/json';
            init.body = JSON.stringify(opts.body);
            init.method = 'POST';
        }
        return fetch(url, init).then(function (r) { return r.json(); });
    }

    function showToast(msg) {
        var t = document.createElement('div');
        t.className = 'hd-toast';
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(function () { t.remove(); }, 2200);
    }

    function clearTimers() {
        for (var k in timers) { clearInterval(timers[k]); clearTimeout(timers[k]); }
        timers = {};
    }

    function removeDanmuStage() {
        var old = document.querySelector('.danmu-stage');
        if (old) old.remove();
    }

    function removeRainStage() {
        var old = document.querySelector('.redpacket-rain');
        if (old) old.remove();
    }

    function removeCeremony() {
        var old = document.querySelector('.ceremony-panel');
        if (old) old.remove();
    }

    // ========== 功能切换 ==========
    window.loadScreenFeature = function (code) {
        clearTimers();
        removeDanmuStage();
        removeRainStage();
        removeCeremony();
        currentFeature = code;
        if (!panel) return;

        var handlers = {
            'qdq': loadSign, 'threedimensionalsign': loadSign,
            'wall': loadWall,
            'lottery': loadLottery, 'choujiang': loadLottery, 'importlottery': loadLottery,
            'vote': loadVote,
            'danmu': loadDanmu,
            'shake': loadShake,
            'game': loadShake,
            'redpacket': loadRedpacket,
            'kaimu': loadKaimu,
            'bimu': loadBimu,
            'xiangce': loadAlbum,
            'ydj': loadLottery,
        };

        var fn = handlers[code];
        if (fn) {
            fn();
        } else {
            panel.innerHTML = '<div style="text-align:center;color:rgba(255,255,255,0.3);font-size:18px;padding:80px 0">功能 ' + code + ' 开发中...</div>';
        }
    };

    // ========== 1. 签到 ==========
    function loadSign() {
        panel.innerHTML = '<div class="sign-grid" id="signGrid"><p style="color:rgba(255,255,255,0.4);font-size:16px">等待嘉宾扫码签到...</p></div>';
        fetchSignList();
        timers.sign = setInterval(fetchSignList, 3000);
    }

    function fetchSignList() {
        api('/sign-list').then(function (res) {
            if (res.code !== 0 || !res.data) return;
            var grid = document.getElementById('signGrid');
            if (!grid) return;
            var list = res.data.list || [];
            if (list.length === 0) {
                grid.innerHTML = '<p style="color:rgba(255,255,255,0.4);font-size:16px">等待嘉宾扫码签到...</p>';
                return;
            }
            var html = '';
            list.forEach(function (p) {
                html += '<img class="sign-avatar" src="' + (p.avatar || '/static/img/default-avatar.png') + '" title="' + (p.nickname || '') + '">';
            });
            html += '<p class="sign-total">已签到 ' + res.data.total + ' 人</p>';
            grid.innerHTML = html;
        }).catch(function () { });
    }

    // ========== 2. 上墙消息 ==========
    function loadWall() {
        panel.innerHTML = '<div class="wall-messages" id="wallList"><p style="text-align:center;color:rgba(255,255,255,0.4)">加载中...</p></div>';
        fetchWallMessages();
        timers.wall = setInterval(fetchWallMessages, 4000);
    }

    function fetchWallMessages() {
        api('/wall').then(function (res) {
            if (res.code !== 0 || !res.data) return;
            var container = document.getElementById('wallList');
            if (!container) return;
            var list = res.data.list || [];
            if (list.length === 0) {
                container.innerHTML = '<p style="text-align:center;color:rgba(255,255,255,0.4);padding:40px">暂无消息</p>';
                return;
            }
            var html = '';
            list.forEach(function (m) {
                var cls = m.is_topped ? 'wall-msg topped' : 'wall-msg';
                html += '<div class="' + cls + '">';
                html += '<img src="' + (m.avatar || '/static/img/default-avatar.png') + '">';
                html += '<div class="msg-body"><div class="nickname">' + (m.nickname || '匿名') + '</div>';
                html += '<div class="text">' + (m.content || '') + '</div>';
                if (m.imgurl) html += '<img class="msg-img" src="' + m.imgurl + '">';
                html += '</div></div>';
            });
            container.innerHTML = html;
        }).catch(function () { });
    }

    // ========== 3. 抽奖 ==========
    function loadLottery() {
        panel.innerHTML = '<div class="lottery-panel"><div class="lottery-round-selector" id="lotteryRounds"></div><div class="lottery-stage" id="lotteryStage"><p style="color:rgba(255,255,255,0.4)">加载轮次...</p></div></div>';
        api('/lottery/rounds').then(function (res) {
            if (res.code !== 0 || !res.data) return;
            var rounds = res.data.rounds || [];
            lotteryState.rounds = rounds;
            if (rounds.length === 0) {
                document.getElementById('lotteryStage').innerHTML = '<p style="color:rgba(255,255,255,0.4);font-size:18px">暂未设置抽奖轮次</p>';
                return;
            }
            renderLotteryRounds(rounds);
            selectLotteryRound(rounds[0]);
        }).catch(function () { });
    }

    function renderLotteryRounds(rounds) {
        var c = document.getElementById('lotteryRounds');
        if (!c) return;
        var html = '';
        rounds.forEach(function (r) {
            var cls = 'round-btn' + (r.status === 2 ? ' done' : '');
            html += '<button class="' + cls + '" data-rid="' + r.id + '" onclick="window._selectRound(' + r.id + ')">';
            html += r.round_name + (r.status === 2 ? ' ✓' : '');
            html += '</button>';
        });
        c.innerHTML = html;
    }

    window._selectRound = function (rid) {
        var round = null;
        (lotteryState.rounds || []).forEach(function (r) { if (r.id === rid) round = r; });
        if (round) selectLotteryRound(round);
    };

    function selectLotteryRound(round) {
        lotteryState.current = round;
        // 高亮当前轮次
        document.querySelectorAll('.round-btn').forEach(function (b) {
            b.classList.toggle('active', parseInt(b.getAttribute('data-rid')) === round.id);
        });
        var stage = document.getElementById('lotteryStage');
        if (!stage) return;

        if (round.status === 2 && round.winners && round.winners.length > 0) {
            // 已抽过，显示中奖者
            var html = '<div class="lottery-prize-name">🏆 ' + round.round_name + (round.prize_name ? ' - ' + round.prize_name : '') + '</div>';
            html += '<div class="lottery-winners">';
            round.winners.forEach(function (w) {
                html += '<div class="winner-card"><img src="' + (w.avatar || '/static/img/default-avatar.png') + '"><div class="name">' + (w.nickname || '') + '</div></div>';
            });
            html += '</div>';
            stage.innerHTML = html;
        } else {
            // 未抽过
            var html = '<div class="lottery-prize-name">🎰 ' + round.round_name + (round.prize_name ? ' - ' + round.prize_name : '') + '</div>';
            html += '<p style="color:rgba(255,255,255,0.5);margin-bottom:20px">抽取 ' + round.win_num + ' 位幸运观众</p>';
            html += '<div class="lottery-rolling" id="lotteryRolling" style="display:none"></div>';
            html += '<div id="lotteryWinnersArea"></div>';
            html += '<button class="lottery-draw-btn" id="btnDraw" onclick="window._doDraw()">🎰 开始抽奖</button>';
            stage.innerHTML = html;
        }
    }

    window._doDraw = function () {
        var round = lotteryState.current;
        if (!round || round.status === 2) return;
        var btn = document.getElementById('btnDraw');
        if (btn) { btn.disabled = true; btn.textContent = '🎰 抽奖中...'; }

        // 显示滚动动画
        var rolling = document.getElementById('lotteryRolling');
        if (rolling) {
            rolling.style.display = 'flex';
            var slots = '';
            for (var i = 0; i < Math.min(round.win_num, 10); i++) {
                slots += '<div class="roll-slot"><img src="/static/img/default-avatar.png"></div>';
            }
            rolling.innerHTML = slots;
        }

        // 发起抽奖
        api('/lottery/draw', { body: { round_id: round.id } }).then(function (res) {
            if (rolling) rolling.style.display = 'none';
            if (res.code !== 0) {
                showToast(res.msg || '抽奖失败');
                if (btn) { btn.disabled = false; btn.textContent = '🎰 开始抽奖'; }
                return;
            }
            var winners = res.data.winners || [];
            if (btn) btn.style.display = 'none';
            var area = document.getElementById('lotteryWinnersArea');
            if (area) {
                var html = '<div class="lottery-winners">';
                winners.forEach(function (w) {
                    html += '<div class="winner-card"><img src="' + (w.avatar || '/static/img/default-avatar.png') + '"><div class="name">' + (w.nickname || '') + '</div></div>';
                });
                html += '</div>';
                area.innerHTML = html;
            }
            // 更新轮次按钮状态
            round.status = 2;
            round.winners = winners;
            document.querySelectorAll('.round-btn').forEach(function (b) {
                if (parseInt(b.getAttribute('data-rid')) === round.id) {
                    b.classList.add('done');
                    b.textContent = round.round_name + ' ✓';
                }
            });
        }).catch(function () {
            if (rolling) rolling.style.display = 'none';
            if (btn) { btn.disabled = false; btn.textContent = '🎰 开始抽奖'; }
            showToast('网络错误');
        });
    };

    // ========== 4. 投票 ==========
    function loadVote() {
        panel.innerHTML = '<div class="vote-panel"><div class="vote-chart" id="voteChart"></div><div class="vote-total-info" id="voteTotalInfo"></div></div>';
        fetchVoteData();
        timers.vote = setInterval(fetchVoteData, 5000);
    }

    function fetchVoteData() {
        api('/vote/items').then(function (res) {
            if (res.code !== 0 || !res.data) return;
            var items = res.data.items || [];
            var total = res.data.total_votes || 0;
            renderVoteChart(items, total);
        }).catch(function () { });
    }

    function renderVoteChart(items, total) {
        var chart = document.getElementById('voteChart');
        var info = document.getElementById('voteTotalInfo');
        if (!chart) return;

        if (items.length === 0) {
            chart.innerHTML = '<p style="color:rgba(255,255,255,0.4);font-size:18px">暂无投票选项</p>';
            return;
        }

        var maxCount = Math.max.apply(null, items.map(function (i) { return i.vote_count; })) || 1;
        var html = '';
        items.forEach(function (item) {
            var pct = (item.vote_count / maxCount * 100) || 0;
            var h = Math.max(4, pct * 2.8); // max bar height ~280px
            html += '<div class="vote-bar-wrap">';
            html += '<div class="vote-bar" style="height:' + h + 'px"><span class="bar-count">' + item.vote_count + '</span></div>';
            if (item.image) html += '<img class="vote-bar-img" src="' + item.image + '">';
            html += '<div class="vote-bar-label">' + item.title + '</div>';
            html += '</div>';
        });
        chart.innerHTML = html;
        if (info) info.textContent = '总投票数：' + total;
    }

    // ========== 5. 弹幕 ==========
    function loadDanmu() {
        panel.innerHTML = '<div style="text-align:center;color:rgba(255,255,255,0.3);font-size:18px;padding:80px 0">💭 弹幕飘屏模式<br><small style="font-size:14px">手机端发送弹幕，大屏实时展示</small></div>';
        danmuLastId = 0;
        // 创建弹幕舞台
        var stage = document.createElement('div');
        stage.className = 'danmu-stage';
        stage.id = 'danmuStage';
        document.getElementById('app').appendChild(stage);
        fetchDanmu();
        timers.danmu = setInterval(fetchDanmu, 2000);
    }

    function fetchDanmu() {
        var url = '/danmu' + (danmuLastId ? '?last_id=' + danmuLastId : '');
        api(url).then(function (res) {
            if (res.code !== 0 || !res.data) return;
            var list = res.data.list || [];
            list.forEach(function (m) {
                spawnDanmu(m.content, m.color || '#ffffff', m.nickname || '');
                if (m.id > danmuLastId) danmuLastId = m.id;
            });
        }).catch(function () { });
    }

    function spawnDanmu(text, color, nick) {
        var stage = document.getElementById('danmuStage');
        if (!stage) return;
        var el = document.createElement('div');
        el.className = 'danmu-item';
        el.style.color = color;
        el.style.top = (Math.random() * 80 + 5) + '%';
        el.style.animationDuration = (6 + Math.random() * 4) + 's';
        el.textContent = (nick ? nick + ': ' : '') + text;
        stage.appendChild(el);
        setTimeout(function () { el.remove(); }, 12000);
    }

    // ========== 6. 摇一摇排行 ==========
    function loadShake() {
        panel.innerHTML = '<div class="shake-panel"><div class="shake-status-text" id="shakeStatusText">加载中...</div><div class="shake-ranking" id="shakeRanking"></div></div>';
        fetchShakeStatus();
        timers.shake = setInterval(fetchShakeStatus, 3000);
    }

    function fetchShakeStatus() {
        api('/shake/status').then(function (res) {
            if (res.code !== 0 || !res.data) return;
            var st = document.getElementById('shakeStatusText');
            var rk = document.getElementById('shakeRanking');
            if (!st || !rk) return;

            var statusMap = { 0: '⏸ 未开始', 1: '🔜 准备中', 2: '🏃 进行中', 3: '🏁 已结束' };
            st.textContent = (statusMap[res.data.status] || '摇一摇竞技') + (res.data.duration ? ' (' + res.data.duration + '秒)' : '');

            var records = res.data.records || [];
            if (records.length === 0) {
                rk.innerHTML = '<p style="color:rgba(255,255,255,0.4);text-align:center;padding:40px">暂无排行数据</p>';
                return;
            }
            var html = '';
            records.forEach(function (r, i) {
                html += '<div class="rank-row">';
                html += '<span class="rank-num">' + (i + 1) + '</span>';
                html += '<img class="rank-avatar" src="' + (r.avatar || '/static/img/default-avatar.png') + '">';
                html += '<span class="rank-name">' + (r.nickname || '') + '</span>';
                html += '<span class="rank-score">' + r.score + '</span>';
                html += '</div>';
            });
            rk.innerHTML = html;
        }).catch(function () { });
    }

    // ========== 7. 红包雨 ==========
    function loadRedpacket() {
        panel.innerHTML = '<div class="redpacket-panel"><div class="redpacket-info" id="rpInfo">🧧 红包互动</div><div class="redpacket-amount" id="rpAmount" style="display:none"></div></div>';
        // 创建红包雨动画
        var rain = document.createElement('div');
        rain.className = 'redpacket-rain';
        rain.id = 'rpRain';
        document.getElementById('app').appendChild(rain);
        timers.rp = setInterval(function () {
            spawnRedpacketItem();
        }, 600);
    }

    function spawnRedpacketItem() {
        var rain = document.getElementById('rpRain');
        if (!rain) return;
        var el = document.createElement('div');
        el.className = 'rp-item';
        el.textContent = '🧧';
        el.style.left = (Math.random() * 90 + 5) + '%';
        el.style.animationDuration = (3 + Math.random() * 4) + 's';
        el.style.fontSize = (30 + Math.random() * 20) + 'px';
        rain.appendChild(el);
        setTimeout(function () { el.remove(); }, 8000);
    }

    // ========== 8. 开幕墙 ==========
    function loadKaimu() {
        api('/theme/kaimu').then(function (res) {
            if (res.code !== 0 || !res.data) {
                panel.innerHTML = '<div style="text-align:center;color:rgba(255,255,255,0.3);padding:80px">开幕墙未配置</div>';
                return;
            }
            showCeremony(res.data, 'kaimu');
        }).catch(function () { });
    }

    // ========== 9. 闭幕墙 ==========
    function loadBimu() {
        api('/theme/bimu').then(function (res) {
            if (res.code !== 0 || !res.data) {
                panel.innerHTML = '<div style="text-align:center;color:rgba(255,255,255,0.3);padding:80px">闭幕墙未配置</div>';
                return;
            }
            showCeremony(res.data, 'bimu');
        }).catch(function () { });
    }

    function showCeremony(data, type) {
        var overlay = document.createElement('div');
        overlay.className = 'ceremony-panel';
        if (data.bg_image) {
            overlay.style.backgroundImage = 'url(' + data.bg_image + ')';
        } else {
            overlay.style.background = type === 'kaimu'
                ? 'linear-gradient(135deg, #1a1a2e, #16213e, #0f3460)'
                : 'linear-gradient(135deg, #0f0c29, #302b63, #24243e)';
        }
        overlay.innerHTML = '<button class="ceremony-close-btn" onclick="this.parentElement.remove()">✕ 关闭</button>'
            + '<div class="ceremony-title">' + (data.title || (type === 'kaimu' ? '开幕墙' : '闭幕墙')) + '</div>'
            + '<div class="ceremony-subtitle">' + (data.subtitle || '') + '</div>';
        document.getElementById('app').appendChild(overlay);
        panel.innerHTML = '';
    }

    // ========== 10. 相册PPT ==========
    function loadAlbum() {
        panel.innerHTML = '<div class="album-panel" id="albumPanel"><p style="text-align:center;color:rgba(255,255,255,0.4);padding:80px">加载相册...</p></div>';
        api('/album/photos').then(function (res) {
            if (res.code !== 0 || !res.data) return;
            var photos = res.data.photos || [];
            var config = res.data.config || {};
            if (photos.length === 0) {
                document.getElementById('albumPanel').innerHTML = '<p style="text-align:center;color:rgba(255,255,255,0.4);padding:80px">相册暂无照片</p>';
                return;
            }
            albumState.photos = photos;
            albumState.index = 0;
            albumState.autoPlay = config.auto_play !== 0;
            albumState.interval = (config.play_interval || 5) * 1000;
            renderAlbum(photos);
            if (albumState.autoPlay) {
                timers.album = setInterval(function () { albumNext(); }, albumState.interval);
            }
        }).catch(function () { });
    }

    function renderAlbum(photos) {
        var ap = document.getElementById('albumPanel');
        if (!ap) return;
        var html = '';
        photos.forEach(function (p, i) {
            html += '<div class="album-slide' + (i === 0 ? ' active' : '') + '" data-idx="' + i + '"><img src="' + p.file_path + '"></div>';
        });
        // 指示器
        html += '<div class="album-indicator">';
        photos.forEach(function (p, i) {
            html += '<div class="album-dot' + (i === 0 ? ' active' : '') + '" data-idx="' + i + '"></div>';
        });
        html += '</div>';
        // 控制按钮
        html += '<div class="album-controls"><button onclick="window._albumPrev()">◀ 上一张</button><button onclick="window._albumNext()">下一张 ▶</button></div>';
        ap.innerHTML = html;
    }

    function albumGo(idx) {
        var photos = albumState.photos || [];
        if (photos.length === 0) return;
        idx = ((idx % photos.length) + photos.length) % photos.length;
        albumState.index = idx;
        document.querySelectorAll('.album-slide').forEach(function (s) {
            s.classList.toggle('active', parseInt(s.getAttribute('data-idx')) === idx);
        });
        document.querySelectorAll('.album-dot').forEach(function (d) {
            d.classList.toggle('active', parseInt(d.getAttribute('data-idx')) === idx);
        });
    }

    function albumNext() { albumGo(albumState.index + 1); }
    function albumPrev() { albumGo(albumState.index - 1); }
    window._albumNext = albumNext;
    window._albumPrev = albumPrev;

    // ========== 自动启动签到 + 功能按钮绑定 + 动画初始化 ==========
    if (!window.HD_IS_MOBILE) {
        // 初始化背景动画
        initAnimation(window.HD_ANIMATION || 'default');

        // 绑定功能按钮点击事件
        document.querySelectorAll('.feature-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.feature-btn').forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                var code = btn.getAttribute('data-code');
                window.loadScreenFeature(code);
            });
        });
        // 默认加载签到
        loadSign();

        // 启动 SSE 实时连接
        setTimeout(connectSSE, 1000);
    }

    // ========== SSE 实时连接 ==========
    var sseConnection = null;
    var sseReconnectDelay = 1000;
    var sseMaxReconnect = 5;
    var sseReconnectCount = 0;

    function connectSSE() {
        if (!API || typeof EventSource === 'undefined') return;

        var channels = 'sign,wall,danmu,vote,lottery,shake,notice';
        var url = API + '/sse?channels=' + channels;

        try {
            sseConnection = new EventSource(url);

            sseConnection.addEventListener('connected', function(e) {
                sseReconnectCount = 0;
                sseReconnectDelay = 1000;
                console.log('[SSE] Connected', JSON.parse(e.data));
            });

            sseConnection.addEventListener('sign', function(e) {
                if (currentFeature === 'qdq' || currentFeature === 'threedimensionalsign') {
                    var data = JSON.parse(e.data);
                    var grid = document.getElementById('signGrid');
                    if (!grid || !data.list) return;
                    var html = '';
                    data.list.forEach(function(p) {
                        html += '<img class="sign-avatar" src="' + (p.avatar || '/static/img/default-avatar.png') + '" title="' + (p.nickname || '') + '">';
                    });
                    html += '<p class="sign-total">已签到 ' + data.total + ' 人</p>';
                    grid.innerHTML = html;
                }
            });

            sseConnection.addEventListener('wall', function(e) {
                if (currentFeature === 'wall') {
                    var data = JSON.parse(e.data);
                    var container = document.getElementById('wallList');
                    if (!container || !data.list) return;
                    var html = '';
                    data.list.forEach(function(m) {
                        var cls = m.is_topped ? 'wall-msg topped' : 'wall-msg';
                        html += '<div class="' + cls + '">';
                        html += '<img src="' + (m.avatar || '/static/img/default-avatar.png') + '">';
                        html += '<div class="msg-body"><div class="nickname">' + (m.nickname || '匿名') + '</div>';
                        html += '<div class="text">' + (m.content || '') + '</div>';
                        if (m.imgurl) html += '<img class="msg-img" src="' + m.imgurl + '">';
                        html += '</div></div>';
                    });
                    container.innerHTML = html || '<p style="text-align:center;color:rgba(255,255,255,0.4);padding:40px">暂无消息</p>';
                }
            });

            sseConnection.addEventListener('danmu', function(e) {
                if (currentFeature === 'danmu') {
                    var data = JSON.parse(e.data);
                    if (data.list && data.list.length > 0) {
                        data.list.forEach(function(d) {
                            spawnDanmu(d.content, d.color || '#fff');
                        });
                    }
                }
            });

            sseConnection.addEventListener('vote', function(e) {
                if (currentFeature === 'vote') {
                    var data = JSON.parse(e.data);
                    if (data.items) {
                        renderVoteBarsDynamic(data.items);
                    }
                }
            });

            sseConnection.addEventListener('lottery', function(e) {
                if (currentFeature === 'lottery' || currentFeature === 'choujiang') {
                    var data = JSON.parse(e.data);
                    if (data.status === 'drawn' && data.winners) {
                        showToast('🎉 ' + data.round_name + ' 抽奖完成！');
                    }
                }
            });

            sseConnection.addEventListener('notice', function(e) {
                var data = JSON.parse(e.data);
                if (data.content) {
                    showToast('📢 ' + data.content);
                }
            });

            sseConnection.addEventListener('reconnect', function() {
                sseConnection.close();
                setTimeout(connectSSE, 500);
            });

            sseConnection.onerror = function() {
                sseConnection.close();
                sseReconnectCount++;
                if (sseReconnectCount < sseMaxReconnect) {
                    sseReconnectDelay = Math.min(sseReconnectDelay * 2, 15000);
                    setTimeout(connectSSE, sseReconnectDelay);
                } else {
                    console.log('[SSE] Max reconnects reached, falling back to polling');
                }
            };
        } catch (err) {
            console.error('[SSE] Connection error', err);
        }
    }

    function renderVoteBarsDynamic(items) {
        var bars = document.getElementById('voteBars');
        if (!bars) return;
        var max = 1;
        items.forEach(function(v) { if (v.vote_count > max) max = v.vote_count; });
        var html = '';
        items.forEach(function(v) {
            var pct = Math.round((v.vote_count / max) * 100);
            html += '<div class="vote-bar-item">';
            html += '<div class="bar-label">' + v.title + '</div>';
            html += '<div class="bar-track"><div class="bar-fill" style="width:' + pct + '%"></div></div>';
            html += '<div class="bar-count">' + v.vote_count + ' 票</div>';
            html += '</div>';
        });
        bars.innerHTML = html;
    }

    function spawnDanmu(text, color) {
        var stage = document.querySelector('.danmu-stage');
        if (!stage) return;
        var d = document.createElement('div');
        d.className = 'danmu-item';
        d.textContent = text;
        d.style.color = color || '#fff';
        d.style.top = (5 + Math.random() * 80) + '%';
        d.style.animationDuration = (6 + Math.random() * 4) + 's';
        stage.appendChild(d);
        d.addEventListener('animationend', function() { d.remove(); });
    }

    // ========== 背景动画系统 ==========
    function initAnimation(style) {
        var app = document.getElementById('app');
        if (!app || style === 'default') return;

        app.classList.add('anim-' + style);

        if (style === 'particle') {
            for (var i = 0; i < 30; i++) {
                var p = document.createElement('div');
                p.className = 'particle';
                p.style.width = p.style.height = (4 + Math.random() * 8) + 'px';
                p.style.left = (Math.random() * 100) + '%';
                p.style.animationDuration = (6 + Math.random() * 10) + 's';
                p.style.animationDelay = (Math.random() * 8) + 's';
                app.appendChild(p);
            }
        } else if (style === 'starfield') {
            for (var i = 0; i < 60; i++) {
                var s = document.createElement('div');
                s.className = 'star';
                s.style.left = (Math.random() * 100) + '%';
                s.style.top = (Math.random() * 100) + '%';
                s.style.animationDuration = (2 + Math.random() * 3) + 's';
                s.style.animationDelay = (Math.random() * 3) + 's';
                app.appendChild(s);
            }
        } else if (style === 'snow') {
            for (var i = 0; i < 35; i++) {
                var sf = document.createElement('div');
                sf.className = 'snowflake';
                sf.textContent = '❄';
                sf.style.left = (Math.random() * 100) + '%';
                sf.style.top = -(Math.random() * 20) + '%';
                sf.style.fontSize = (10 + Math.random() * 14) + 'px';
                sf.style.animationDuration = (8 + Math.random() * 12) + 's';
                sf.style.animationDelay = (Math.random() * 8) + 's';
                app.appendChild(sf);
            }
        } else if (style === 'bubble') {
            for (var i = 0; i < 20; i++) {
                var b = document.createElement('div');
                b.className = 'bub';
                var sz = (15 + Math.random() * 40);
                b.style.width = b.style.height = sz + 'px';
                b.style.left = (Math.random() * 100) + '%';
                b.style.animationDuration = (8 + Math.random() * 12) + 's';
                b.style.animationDelay = (Math.random() * 6) + 's';
                app.appendChild(b);
            }
        }
    }

})();
