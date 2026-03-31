/**
 * 艺为微信大屏互动 - 管理后台 API 工具
 */
var HdApi = (function() {
    var BASE = '/api/hd';

    function getToken() {
        return localStorage.getItem('hd_token') || '';
    }

    function request(method, url, data) {
        var opts = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Hd-Token': getToken()
            }
        };
        if (data && (method === 'POST' || method === 'PUT')) {
            opts.body = JSON.stringify(data);
        }
        return fetch(BASE + url, opts).then(function(r) {
            if (r.status === 401) {
                localStorage.clear();
                window.location.href = '/login';
                return Promise.reject('未登录');
            }
            return r.json();
        });
    }

    return {
        get: function(url) { return request('GET', url); },
        post: function(url, data) { return request('POST', url, data); },
        put: function(url, data) { return request('PUT', url, data); },
        del: function(url) { return request('DELETE', url); },

        // Auth
        getProfile: function() { return this.get('/auth/profile'); },
        updateProfile: function(data) { return this.post('/auth/profile', data); },
        logout: function() { return this.post('/auth/logout'); },

        // Stores
        getStores: function(p) { return this.get('/stores?page=' + (p||1)); },
        createStore: function(d) { return this.post('/stores', d); },
        getStore: function(id) { return this.get('/stores/' + id); },
        updateStore: function(id, d) { return this.post('/stores/' + id + '/update', d); },
        deleteStore: function(id) { return this.post('/stores/' + id + '/delete'); },

        // Activities
        getActivities: function(p) { return this.get('/activities?page=' + (p||1)); },
        createActivity: function(d) { return this.post('/activities', d); },
        getActivity: function(id) { return this.get('/activities/' + id); },
        updateActivity: function(id, d) { return this.post('/activities/' + id + '/update', d); },
        deleteActivity: function(id) { return this.post('/activities/' + id + '/delete'); },
        updateActivityStatus: function(id, st) { return this.post('/activities/' + id + '/status', {status:st}); },
        getFeatures: function(id) { return this.get('/activities/' + id + '/features'); },
        updateFeature: function(id, code, d) { return this.post('/activities/' + id + '/features/' + code, d); },
        getParticipants: function(id, p) { return this.get('/activities/' + id + '/participants?page=' + (p||1)); },
        getActivityStats: function(id) { return this.get('/activities/' + id + '/stats'); },
        getAllFeatures: function() { return this.get('/features'); },

        // ============ Sign (签到) ============
        getSignConfig: function(aid) { return this.get('/sign/' + aid + '/config'); },
        updateSignConfig: function(aid, d) { return this.post('/sign/' + aid + '/config', d); },
        getSignList: function(aid, p) { return this.get('/sign/' + aid + '/list?page=' + (p||1)); },
        deleteSignParticipant: function(aid, id) { return this.post('/sign/' + aid + '/participant/' + id + '/delete'); },
        clearSignList: function(aid) { return this.post('/sign/' + aid + '/clear'); },
        getSignMobileConfig: function(aid) { return this.get('/sign/' + aid + '/mobile-config'); },
        updateSignMobileConfig: function(aid, d) { return this.post('/sign/' + aid + '/mobile-config', d); },

        // ============ Lottery (抽奖) ============
        getPrizes: function(aid) { return this.get('/lottery/' + aid + '/prizes'); },
        createPrize: function(aid, d) { return this.post('/lottery/' + aid + '/prizes', d); },
        updatePrize: function(aid, id, d) { return this.post('/lottery/' + aid + '/prizes/' + id + '/update', d); },
        deletePrize: function(aid, id) { return this.post('/lottery/' + aid + '/prizes/' + id + '/delete'); },
        getRounds: function(aid) { return this.get('/lottery/' + aid + '/rounds'); },
        createRound: function(aid, d) { return this.post('/lottery/' + aid + '/rounds', d); },
        updateRound: function(aid, id, d) { return this.post('/lottery/' + aid + '/rounds/' + id + '/update', d); },
        deleteRound: function(aid, id) { return this.post('/lottery/' + aid + '/rounds/' + id + '/delete'); },
        resetRound: function(aid, id) { return this.post('/lottery/' + aid + '/rounds/' + id + '/reset'); },
        getLotteryThemes: function(aid) { return this.get('/lottery/' + aid + '/themes'); },
        createLotteryTheme: function(aid, d) { return this.post('/lottery/' + aid + '/themes', d); },
        updateLotteryTheme: function(aid, id, d) { return this.post('/lottery/' + aid + '/themes/' + id + '/update', d); },
        deleteLotteryTheme: function(aid, id) { return this.post('/lottery/' + aid + '/themes/' + id + '/delete'); },
        getChoujiangConfig: function(aid) { return this.get('/lottery/' + aid + '/choujiang'); },
        updateChoujiangConfig: function(aid, d) { return this.post('/lottery/' + aid + '/choujiang', d); },

        // ============ Speed (拼手速) ============
        getShakeConfig: function(aid) { return this.get('/speed/' + aid + '/shake/config'); },
        updateShakeConfig: function(aid, d) { return this.post('/speed/' + aid + '/shake/config', d); },
        getShakeThemes: function(aid) { return this.get('/speed/' + aid + '/shake/themes'); },
        updateShakeTheme: function(aid, id, d) { return this.post('/speed/' + aid + '/shake/themes/' + id + '/update', d); },
        getShakeRanking: function(aid) { return this.get('/speed/' + aid + '/shake/ranking'); },
        resetShake: function(aid) { return this.post('/speed/' + aid + '/shake/reset'); },
        getGameConfig: function(aid) { return this.get('/speed/' + aid + '/game/config'); },
        updateGameConfig: function(aid, d) { return this.post('/speed/' + aid + '/game/config', d); },
        getGameThemes: function(aid) { return this.get('/speed/' + aid + '/game/themes'); },
        updateGameTheme: function(aid, id, d) { return this.post('/speed/' + aid + '/game/themes/' + id + '/update', d); },
        getGameRanking: function(aid) { return this.get('/speed/' + aid + '/game/ranking'); },
        resetGame: function(aid) { return this.post('/speed/' + aid + '/game/reset'); },

        // ============ Redpacket (红包) ============
        getRedpacketConfig: function(aid) { return this.get('/redpacket/' + aid + '/config'); },
        updateRedpacketConfig: function(aid, d) { return this.post('/redpacket/' + aid + '/config', d); },
        getRedpacketRounds: function(aid) { return this.get('/redpacket/' + aid + '/rounds'); },
        createRedpacketRound: function(aid, d) { return this.post('/redpacket/' + aid + '/rounds', d); },
        updateRedpacketRound: function(aid, id, d) { return this.post('/redpacket/' + aid + '/rounds/' + id + '/update', d); },
        deleteRedpacketRound: function(aid, id) { return this.post('/redpacket/' + aid + '/rounds/' + id + '/delete'); },
        getRedpacketRecords: function(aid) { return this.get('/redpacket/' + aid + '/records'); },

        // ============ Wall / Danmu (弹幕) ============
        getWallConfig: function(aid) { return this.get('/wall/' + aid + '/config'); },
        updateWallConfig: function(aid, d) { return this.post('/wall/' + aid + '/config', d); },
        getDanmuConfig: function(aid) { return this.get('/wall/' + aid + '/danmu-config'); },
        updateDanmuConfig: function(aid, d) { return this.post('/wall/' + aid + '/danmu-config', d); },
        getWallMessages: function(aid, p) { return this.get('/wall/' + aid + '/messages?page=' + (p||1)); },
        approveWallMessage: function(aid, id) { return this.post('/wall/' + aid + '/messages/' + id + '/approve', {status:1}); },
        batchApproveWall: function(aid, ids) { return this.post('/wall/' + aid + '/messages/batch-approve', {ids:ids, status:1}); },
        deleteWallMessage: function(aid, id) { return this.post('/wall/' + aid + '/messages/' + id + '/delete'); },
        toggleWallTop: function(aid, id) { return this.post('/wall/' + aid + '/messages/' + id + '/toggle-top'); },
        publishNotice: function(aid, d) { return this.post('/wall/' + aid + '/notice', d); },

        // ============ Theme (主题) ============
        getKaimuConfig: function(aid) { return this.get('/theme/' + aid + '/kaimu'); },
        updateKaimuConfig: function(aid, d) { return this.post('/theme/' + aid + '/kaimu', d); },
        getBimuConfig: function(aid) { return this.get('/theme/' + aid + '/bimu'); },
        updateBimuConfig: function(aid, d) { return this.post('/theme/' + aid + '/bimu', d); },
        getBackgrounds: function(aid) { return this.get('/theme/' + aid + '/backgrounds'); },
        addBackground: function(aid, d) { return this.post('/theme/' + aid + '/backgrounds', d); },
        updateBgItem: function(aid, id, d) { return this.post('/theme/' + aid + '/backgrounds/' + id + '/update', d); },
        deleteBgItem: function(aid, id) { return this.post('/theme/' + aid + '/backgrounds/' + id + '/delete'); },
        getMusics: function(aid) { return this.get('/theme/' + aid + '/musics'); },
        addMusicItem: function(aid, d) { return this.post('/theme/' + aid + '/musics', d); },
        updateMusicItem: function(aid, id, d) { return this.post('/theme/' + aid + '/musics/' + id + '/update', d); },
        deleteMusicItem: function(aid, id) { return this.post('/theme/' + aid + '/musics/' + id + '/delete'); },
        getQrcodeConfig: function(aid) { return this.get('/theme/' + aid + '/qrcode'); },
        updateQrcodeConfig: function(aid, d) { return this.post('/theme/' + aid + '/qrcode', d); },

        // ============ Album (相册) ============
        getAlbumConfig: function(aid) { return this.get('/album/' + aid + '/config'); },
        updateAlbumConfig: function(aid, d) { return this.post('/album/' + aid + '/config', d); },
        getAlbumPhotos: function(aid) { return this.get('/album/' + aid + '/photos'); },
        addAlbumPhoto: function(aid, d) { return this.post('/album/' + aid + '/photos', d); },
        batchAddPhotos: function(aid, d) { return this.post('/album/' + aid + '/photos/batch', d); },
        deleteAlbumPhoto: function(aid, id) { return this.post('/album/' + aid + '/photos/' + id + '/delete'); },
        clearAlbum: function(aid) { return this.post('/album/' + aid + '/clear'); },

        // ============ Vote (投票) ============
        getVoteItems: function(aid) { return this.get('/vote/' + aid + '/items'); },
        createVoteItem: function(aid, d) { return this.post('/vote/' + aid + '/items', d); },
        updateVoteItem: function(aid, id, d) { return this.post('/vote/' + aid + '/items/' + id + '/update', d); },
        deleteVoteItem: function(aid, id) { return this.post('/vote/' + aid + '/items/' + id + '/delete'); },
        getVoteStats: function(aid) { return this.get('/vote/' + aid + '/stats'); },
        resetVotes: function(aid) { return this.post('/vote/' + aid + '/reset'); },

        // ============ Switch (功能开关) ============
        getFeatureSwitches: function(aid) { return this.get('/switch/' + aid); },
        batchUpdateSwitches: function(aid, d) { return this.post('/switch/' + aid + '/batch', d); },
        toggleFeature: function(aid, code) { return this.post('/switch/' + aid + '/toggle/' + code); },

        // ============ Setting (系统设置) ============
        getSystemSettings: function() { return this.get('/setting'); },
        updateBusinessInfo: function(d) { return this.post('/setting/business', d); },
        updateWxConfig: function(d) { return this.post('/setting/wx-config', d); },
        changePassword: function(d) { return this.post('/setting/password', d); },

        // ============ Security (内容安全) ============
        getSecurityConfig: function(aid) { return this.get('/security/' + aid + '/config'); },
        updateSecurityConfig: function(aid, d) { return this.post('/security/' + aid + '/config', d); },
        getKeywords: function(aid) { return this.get('/security/' + aid + '/keywords'); },
        addKeyword: function(aid, d) { return this.post('/security/' + aid + '/keywords', d); },
        batchAddKeywords: function(aid, d) { return this.post('/security/' + aid + '/keywords/batch', d); },
        deleteKeyword: function(aid, id) { return this.post('/security/' + aid + '/keywords/' + id + '/delete'); },
        toggleKeyword: function(aid, id) { return this.post('/security/' + aid + '/keywords/' + id + '/toggle'); },
        getBanList: function(aid) { return this.get('/security/' + aid + '/bans'); },
        banUser: function(aid, d) { return this.post('/security/' + aid + '/bans', d); },
        unbanUser: function(aid, id) { return this.post('/security/' + aid + '/bans/' + id + '/unban'); },
        toggleGlobalMute: function(aid) { return this.post('/security/' + aid + '/global-mute'); },

        // ============ Brand (品牌定制) ============
        getBrandConfig: function(aid) { return this.get('/brand/' + aid + '/config'); },
        updateBrandConfig: function(aid, d) { return this.post('/brand/' + aid + '/config', d); },
        getAnimationPresets: function() { return this.get('/brand/animation-presets'); },

        // Util
        isLoggedIn: function() { return !!getToken(); },
        getName: function() { return localStorage.getItem('hd_name') || '商家'; }
    };
})();

/* Toast 通知 */
function showToast(msg, type) {
    type = type || 'success';
    var toast = document.getElementById('globalToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'globalToast';
        toast.className = 'toast';
        document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.className = 'toast ' + type + ' show';
    setTimeout(function() { toast.classList.remove('show'); }, 3000);
}

/* Loading */
function showLoading() {
    var mask = document.getElementById('loadingMask');
    if (mask) mask.classList.add('show');
}
function hideLoading() {
    var mask = document.getElementById('loadingMask');
    if (mask) mask.classList.remove('show');
}

/* 格式化时间 */
function formatTime(ts) {
    if (!ts) return '-';
    var d = new Date(ts * 1000);
    return d.getFullYear() + '-' +
        String(d.getMonth() + 1).padStart(2, '0') + '-' +
        String(d.getDate()).padStart(2, '0') + ' ' +
        String(d.getHours()).padStart(2, '0') + ':' +
        String(d.getMinutes()).padStart(2, '0');
}

/* 活动状态文本 */
function statusText(s) {
    var map = {1: '未开始', 2: '进行中', 3: '已结束'};
    return map[s] || '未知';
}
function statusBadge(s) {
    var cls = {1: 'badge-warning', 2: 'badge-success', 3: 'badge-danger'};
    return '<span class="badge ' + (cls[s]||'badge-info') + '">' + statusText(s) + '</span>';
}
