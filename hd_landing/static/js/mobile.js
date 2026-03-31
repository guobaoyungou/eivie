/**
 * 艺为微信大屏互动 - 手机端交互逻辑
 * 功能：签到、上墙发消息、投票、摇一摇、抢红包、发弹幕
 */
(function () {
    'use strict';

    var API = window.HD_API_BASE || '';
    var content = document.getElementById('mobileContent');
    var currentMobileFeature = null;
    var mTimers = {};
    var shakeState = { score: 0, active: false };

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

    function clearMTimers() {
        for (var k in mTimers) { clearInterval(mTimers[k]); clearTimeout(mTimers[k]); }
        mTimers = {};
    }

    function getOpenid() { return localStorage.getItem('hd_openid') || 'visitor_' + Math.random().toString(36).substr(2, 8); }
    function getNickname() { return localStorage.getItem('hd_nickname') || '访客'; }
    function getAvatar() { return localStorage.getItem('hd_avatar') || ''; }

    // ========== 签到 ==========
    window.doSign = function () {
        var btn = document.getElementById('btnSign');
        if (btn) { btn.disabled = true; btn.textContent = '签到中...'; }

        var signData = {
            openid: getOpenid(),
            nickname: getNickname(),
            avatar: getAvatar(),
            signname: ''
        };

        // 尝试获取位置信息后再提交签到
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    signData.latitude = pos.coords.latitude;
                    signData.longitude = pos.coords.longitude;
                    _submitSign(signData, btn);
                },
                function() {
                    // 定位失败仍然提交（后端会判断是否开启了地点限定）
                    _submitSign(signData, btn);
                },
                { enableHighAccuracy: true, timeout: 5000, maximumAge: 60000 }
            );
        } else {
            _submitSign(signData, btn);
        }
    };

    function _submitSign(signData, btn) {
        api('/sign', { body: signData }).then(function (res) {
            if (btn) {
                btn.textContent = res.code === 0 ? '✅ 已签到' : res.msg;
                if (res.code !== 0) btn.disabled = false;
            }
            if (res.code === 0) showToast('签到成功！排名第 ' + (res.data.signorder || '') + ' 位');
        }).catch(function () {
            if (btn) { btn.textContent = '签到失败，重试'; btn.disabled = false; }
        });
    }

    // ========== 功能切换 ==========
    window.loadMobileFeature = function (code) {
        clearMTimers();
        currentMobileFeature = code;
        if (!content) return;

        var handlers = {
            'wall': mLoadWall,
            'danmu': mLoadDanmu,
            'vote': mLoadVote,
            'shake': mLoadShake,
            'game': mLoadShake,
            'redpacket': mLoadRedpacket,
            'lottery': mLoadLottery,
            'choujiang': mLoadLottery,
            'xiangce': mLoadAlbum,
            'qdq': mLoadSignInfo,
            'threedimensionalsign': mLoadSignInfo,
        };

        var fn = handlers[code];
        if (fn) {
            fn();
        } else {
            content.innerHTML = '<div style="text-align:center;color:rgba(255,255,255,0.3);padding:40px">功能 ' + code + ' 开发中...</div>';
        }
    };

    // ========== 签到信息 ==========
    function mLoadSignInfo() {
        content.innerHTML = '<div style="text-align:center;padding:30px;color:rgba(255,255,255,0.5)">请点击上方"立即签到"按钮进行签到</div>';
    }

    // ========== 上墙发消息 ==========
    function mLoadWall() {
        content.innerHTML =
            '<div class="m-send-panel">' +
            '<h3 style="font-size:16px;margin-bottom:10px;color:#a5b4fc">💬 发送上墙消息</h3>' +
            '<textarea id="wallMsgInput" placeholder="输入要发送到大屏的消息..." maxlength="200"></textarea>' +
            '<button class="m-send-btn" id="btnSendWall" onclick="window._sendWall()">发送消息</button>' +
            '</div>' +
            '<div id="mWallList" style="margin-top:16px"></div>';
        mFetchWall();
        mTimers.wall = setInterval(mFetchWall, 5000);
    }

    window._sendWall = function () {
        var input = document.getElementById('wallMsgInput');
        var btn = document.getElementById('btnSendWall');
        var msg = input ? input.value.trim() : '';
        if (!msg) { showToast('请输入消息内容'); return; }
        if (btn) { btn.disabled = true; btn.textContent = '发送中...'; }
        api('/wall', {
            body: {
                openid: getOpenid(),
                nickname: getNickname(),
                avatar: getAvatar(),
                content: msg
            }
        }).then(function (res) {
            showToast(res.msg || '已发送');
            if (input) input.value = '';
            if (btn) { btn.disabled = false; btn.textContent = '发送消息'; }
        }).catch(function () {
            showToast('发送失败');
            if (btn) { btn.disabled = false; btn.textContent = '发送消息'; }
        });
    };

    function mFetchWall() {
        api('/wall').then(function (res) {
            if (res.code !== 0 || !res.data) return;
            var c = document.getElementById('mWallList');
            if (!c) return;
            var list = res.data.list || [];
            if (list.length === 0) { c.innerHTML = '<p style="text-align:center;color:rgba(255,255,255,0.3);padding:20px">暂无消息</p>'; return; }
            var html = '<div class="wall-messages">';
            list.slice(0, 20).forEach(function (m) {
                html += '<div class="wall-msg' + (m.is_topped ? ' topped' : '') + '">';
                html += '<img src="' + (m.avatar || '/static/img/default-avatar.png') + '">';
                html += '<div class="msg-body"><div class="nickname">' + (m.nickname || '匿名') + '</div><div class="text">' + (m.content || '') + '</div></div></div>';
            });
            html += '</div>';
            c.innerHTML = html;
        }).catch(function () { });
    }

    // ========== 发弹幕 ==========
    function mLoadDanmu() {
        content.innerHTML =
            '<div class="m-send-panel">' +
            '<h3 style="font-size:16px;margin-bottom:10px;color:#a5b4fc">💭 发送弹幕</h3>' +
            '<div class="m-danmu-input">' +
            '<input id="danmuInput" type="text" placeholder="输入弹幕内容..." maxlength="50">' +
            '<button class="m-danmu-send" onclick="window._sendDanmu()">发送</button>' +
            '</div>' +
            '<div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap" id="danmuColors">' +
            '<span style="cursor:pointer;font-size:20px" onclick="window._setDanmuColor(\'#ffffff\')"  title="白色">⚪</span>' +
            '<span style="cursor:pointer;font-size:20px" onclick="window._setDanmuColor(\'#ef4444\')" title="红色">🔴</span>' +
            '<span style="cursor:pointer;font-size:20px" onclick="window._setDanmuColor(\'#f59e0b\')" title="黄色">🟡</span>' +
            '<span style="cursor:pointer;font-size:20px" onclick="window._setDanmuColor(\'#22c55e\')" title="绿色">🟢</span>' +
            '<span style="cursor:pointer;font-size:20px" onclick="window._setDanmuColor(\'#6366f1\')" title="蓝色">🔵</span>' +
            '<span style="cursor:pointer;font-size:20px" onclick="window._setDanmuColor(\'#e879f9\')" title="粉色">🟣</span>' +
            '</div>' +
            '</div>';
        window._danmuColor = '#ffffff';
    }

    window._setDanmuColor = function (c) { window._danmuColor = c; showToast('已选颜色'); };

    window._sendDanmu = function () {
        var input = document.getElementById('danmuInput');
        var msg = input ? input.value.trim() : '';
        if (!msg) { showToast('请输入弹幕内容'); return; }
        api('/danmu', {
            body: {
                openid: getOpenid(),
                nickname: getNickname(),
                content: msg,
                color: window._danmuColor || '#ffffff'
            }
        }).then(function (res) {
            showToast(res.msg || '已发送');
            if (input) input.value = '';
        }).catch(function () { showToast('发送失败'); });
    };

    // ========== 投票 ==========
    function mLoadVote() {
        content.innerHTML = '<div id="mVoteArea"><p style="text-align:center;color:rgba(255,255,255,0.3);padding:30px">加载投票...</p></div>';
        api('/vote/items').then(function (res) {
            if (res.code !== 0 || !res.data) return;
            var items = res.data.items || [];
            var area = document.getElementById('mVoteArea');
            if (!area) return;
            if (items.length === 0) {
                area.innerHTML = '<p style="text-align:center;color:rgba(255,255,255,0.3);padding:30px">暂无投票选项</p>';
                return;
            }
            var html = '<h3 style="font-size:16px;margin-bottom:12px;color:#a5b4fc">🗳️ 请选择投票</h3>';
            html += '<ul class="m-vote-list">';
            items.forEach(function (item) {
                html += '<li class="m-vote-item" data-vid="' + item.id + '" onclick="window._selectVote(this,' + item.id + ')">';
                if (item.image) html += '<img src="' + item.image + '">';
                html += '<span class="vi-title">' + item.title + '</span>';
                html += '<span class="vi-count">' + item.vote_count + '票</span>';
                html += '</li>';
            });
            html += '</ul>';
            html += '<button class="m-vote-submit" id="btnVote" onclick="window._doVote()">投票</button>';
            area.innerHTML = html;
            window._selectedVoteId = null;
        }).catch(function () { });
    }

    window._selectVote = function (el, vid) {
        document.querySelectorAll('.m-vote-item').forEach(function (li) { li.classList.remove('selected'); });
        el.classList.add('selected');
        window._selectedVoteId = vid;
    };

    window._doVote = function () {
        if (!window._selectedVoteId) { showToast('请选择一个选项'); return; }
        var btn = document.getElementById('btnVote');
        if (btn) { btn.disabled = true; btn.textContent = '投票中...'; }
        api('/vote', {
            body: {
                vote_item_id: window._selectedVoteId,
                openid: getOpenid()
            }
        }).then(function (res) {
            showToast(res.msg || '投票成功');
            if (res.code === 0) {
                if (btn) { btn.textContent = '✅ 已投票'; }
                setTimeout(mLoadVote, 1500);
            } else {
                if (btn) { btn.disabled = false; btn.textContent = '投票'; }
            }
        }).catch(function () {
            showToast('投票失败');
            if (btn) { btn.disabled = false; btn.textContent = '投票'; }
        });
    };

    // ========== 摇一摇 ==========
    function mLoadShake() {
        shakeState = { score: 0, active: false };
        content.innerHTML =
            '<div class="m-shake-area">' +
            '<div class="m-shake-icon" id="shakeIcon">📱</div>' +
            '<div class="m-shake-score" id="shakeScore">0</div>' +
            '<div class="m-shake-hint" id="shakeHint">摇动手机进行互动！</div>' +
            '</div>';
        // 检查状态
        checkShakeStatus();
        mTimers.shake = setInterval(checkShakeStatus, 3000);
        // 监听设备摇动
        if (window.DeviceMotionEvent) {
            window._shakeHandler = function (e) {
                if (!shakeState.active) return;
                var acc = e.accelerationIncludingGravity;
                if (!acc) return;
                var force = Math.abs(acc.x) + Math.abs(acc.y) + Math.abs(acc.z);
                if (force > 25) {
                    shakeState.score++;
                    var sc = document.getElementById('shakeScore');
                    if (sc) sc.textContent = shakeState.score;
                }
            };
            window.addEventListener('devicemotion', window._shakeHandler);
        }
        // 也支持点击（桌面调试用）
        var icon = document.getElementById('shakeIcon');
        if (icon) {
            icon.addEventListener('click', function () {
                if (!shakeState.active) return;
                shakeState.score += 5;
                var sc = document.getElementById('shakeScore');
                if (sc) sc.textContent = shakeState.score;
            });
        }
    }

    function checkShakeStatus() {
        api('/shake/status').then(function (res) {
            if (res.code !== 0 || !res.data) return;
            var hint = document.getElementById('shakeHint');
            if (res.data.status === 2) {
                shakeState.active = true;
                if (hint) hint.textContent = '🏃 竞技进行中，用力摇！';
            } else if (res.data.status === 3) {
                shakeState.active = false;
                if (hint) hint.textContent = '🏁 竞技已结束';
                submitShakeScore();
            } else {
                shakeState.active = false;
                if (hint) hint.textContent = '⏸ 等待主持人开始...';
            }
        }).catch(function () { });
    }

    function submitShakeScore() {
        if (shakeState.score <= 0) return;
        api('/shake/score', {
            body: {
                openid: getOpenid(),
                nickname: getNickname(),
                avatar: getAvatar(),
                score: shakeState.score
            }
        }).then(function (res) {
            showToast('分数已提交: ' + shakeState.score);
        }).catch(function () { });
    }

    // ========== 抢红包 ==========
    function mLoadRedpacket() {
        content.innerHTML =
            '<div class="m-redpacket-area">' +
            '<div class="m-rp-icon">🧧</div>' +
            '<button class="m-rp-btn" id="btnGrabRp" onclick="window._grabRedpacket()">抢红包</button>' +
            '<div class="m-rp-result" id="rpResult" style="display:none"></div>' +
            '</div>';
    }

    window._grabRedpacket = function () {
        var btn = document.getElementById('btnGrabRp');
        if (btn) { btn.disabled = true; btn.textContent = '抢红包中...'; }
        api('/redpacket/grab', {
            body: {
                openid: getOpenid(),
                nickname: getNickname()
            }
        }).then(function (res) {
            var result = document.getElementById('rpResult');
            if (res.code === 0) {
                if (btn) btn.style.display = 'none';
                if (result) {
                    result.style.display = 'block';
                    result.innerHTML = '🎉 恭喜获得 <strong>¥' + res.data.amount + '</strong>';
                }
            } else {
                showToast(res.msg || '抢红包失败');
                if (res.msg && res.msg.indexOf('已抢过') >= 0) {
                    if (btn) btn.style.display = 'none';
                    if (result) {
                        result.style.display = 'block';
                        result.innerHTML = '您已抢过红包 ¥' + (res.data ? res.data.amount : '');
                    }
                } else {
                    if (btn) { btn.disabled = false; btn.textContent = '抢红包'; }
                }
            }
        }).catch(function () {
            showToast('网络错误');
            if (btn) { btn.disabled = false; btn.textContent = '抢红包'; }
        });
    };

    // ========== 查看抽奖 ==========
    function mLoadLottery() {
        content.innerHTML = '<div style="text-align:center;padding:30px;color:rgba(255,255,255,0.5)"><div style="font-size:48px;margin-bottom:16px">🎰</div><p>请关注大屏幕查看抽奖结果</p></div>';
    }

    // ========== 查看相册 ==========
    function mLoadAlbum() {
        content.innerHTML = '<div style="text-align:center;padding:30px;color:rgba(255,255,255,0.5)"><div style="font-size:48px;margin-bottom:16px">📸</div><p>请关注大屏幕查看精彩照片</p></div>';
    }

    // ========== 绑定功能按钮 ==========
    document.querySelectorAll('.feature-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.feature-btn').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var code = btn.getAttribute('data-code');
            window.loadMobileFeature(code);
        });
    });

})();
