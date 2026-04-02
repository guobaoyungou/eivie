/**
 * API 请求层
 * 基于 Axios 封装，自动注入 Token、处理 401、统一错误提示
 */
;(function(global) {
    'use strict';

    var TOKEN_KEY = 'hd_admin_token';
    var BASE_URL = '/api/hd';

    // 创建 Axios 实例
    var http = axios.create({
        baseURL: BASE_URL,
        timeout: 30000,
        headers: { 'Content-Type': 'application/json' }
    });

    // 请求拦截器 - 注入 Token
    http.interceptors.request.use(function(config) {
        var token = localStorage.getItem(TOKEN_KEY);
        if (token) {
            config.headers['Hd-Token'] = token;
        }
        return config;
    }, function(error) {
        return Promise.reject(error);
    });

    // 响应拦截器 - 处理 401 和错误
    http.interceptors.response.use(function(response) {
        var data = response.data;
        // 后端统一返回 {code, msg, data} 格式，code=0 表示成功
        if (data && data.code !== undefined && data.code !== 0) {
            // 业务错误（code 非 0）
            layui.layer.msg(data.msg || '操作失败', { icon: 2 });
            return Promise.reject(data);
        }
        return data;
    }, function(error) {
        if (error.response) {
            var status = error.response.status;
            if (status === 401) {
                // Token 过期或未登录
                Api.clearToken();
                layui.layer.msg('登录已过期，请重新登录', { icon: 2 }, function() {
                    if (global.App && global.App.showLogin) {
                        global.App.showLogin();
                    }
                });
                return Promise.reject(error);
            }
            if (status === 403) {
                layui.layer.msg('没有操作权限', { icon: 2 });
                return Promise.reject(error);
            }
            if (status === 404) {
                layui.layer.msg('接口不存在', { icon: 2 });
                return Promise.reject(error);
            }
            if (status >= 500) {
                layui.layer.msg('服务器错误，请稍后重试', { icon: 2 });
                return Promise.reject(error);
            }
        }
        layui.layer.msg('网络连接异常', { icon: 2 });
        return Promise.reject(error);
    });

    var Api = {
        http: http,

        // ---- Token 管理 ----
        setToken: function(token) {
            localStorage.setItem(TOKEN_KEY, token);
        },
        getToken: function() {
            return localStorage.getItem(TOKEN_KEY);
        },
        clearToken: function() {
            localStorage.removeItem(TOKEN_KEY);
        },

        // ---- 认证 API ----
        login: function(username, password) {
            return http.post('/auth/login', { username: username, password: password });
        },
        logout: function() {
            return http.post('/auth/logout');
        },
        getProfile: function() {
            return http.get('/auth/profile');
        },
        updateProfile: function(data) {
            return http.post('/auth/profile', data);
        },

        // ---- 扫码登录 API ----
        getQrCode: function() {
            return http.get('/auth/qr-code');
        },
        checkQrCode: function(sceneId) {
            return http.get('/auth/qr-check', { params: { scene_id: sceneId } });
        },

        // ---- 手机绑定 API ----
        sendBindCode: function(phone) {
            return http.post('/auth/send-bind-code', { phone: phone });
        },
        wxBind: function(data) {
            return http.post('/auth/wx-bind', data);
        },

        // ---- 门店/公司 API ----
        getStores: function(params) {
            return http.get('/stores', { params: params });
        },

        // ---- 活动管理 API ----
        getActivities: function(params) {
            return http.get('/activities', { params: params });
        },
        getActivity: function(id) {
            return http.get('/activities/' + id);
        },
        createActivity: function(data) {
            return http.post('/activities', data);
        },
        updateActivity: function(id, data) {
            return http.post('/activities/' + id + '/update', data);
        },
        deleteActivity: function(id) {
            return http.post('/activities/' + id + '/delete');
        },
        getActivityStats: function(id) {
            return http.get('/activities/' + id + '/stats');
        },

        // ---- 签到管理 API ----
        getSignConfig: function(actId) {
            return http.get('/sign/' + actId + '/config');
        },
        updateSignConfig: function(actId, data) {
            return http.post('/sign/' + actId + '/config', data);
        },
        getSignList: function(actId, params) {
            return http.get('/sign/' + actId + '/list', { params: params });
        },
        deleteParticipant: function(actId, pid) {
            return http.post('/sign/' + actId + '/participant/' + pid + '/delete');
        },
        clearSignList: function(actId) {
            return http.post('/sign/' + actId + '/clear');
        },

        // ---- 3D签到 API ----
        get3dConfig: function(actId) {
            return http.get('/sign/' + actId + '/3d-config');
        },
        save3dConfig: function(actId, data) {
            return http.post('/sign/' + actId + '/3d-config', data);
        },
        add3dEffect: function(actId, data) {
            return http.post('/sign/' + actId + '/3d-effects/add', data);
        },
        delete3dEffect: function(actId, effectId) {
            return http.post('/sign/' + actId + '/3d-effects/' + effectId + '/delete');
        },
        reorder3dEffects: function(actId, effectIds) {
            return http.post('/sign/' + actId + '/3d-effects/reorder', { effect_ids: effectIds });
        },
        upload3dLogo: function(actId, formData) {
            return http.post('/sign/' + actId + '/3d-effects/upload-logo', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        },

        // ---- 抽奖管理 API ----
        getLotteryRounds: function(actId) {
            return http.get('/lottery/' + actId + '/rounds');
        },
        createLotteryRound: function(actId, data) {
            return http.post('/lottery/' + actId + '/rounds', data);
        },
        updateLotteryRound: function(actId, id, data) {
            return http.post('/lottery/' + actId + '/rounds/' + id + '/update', data);
        },
        deleteLotteryRound: function(actId, id) {
            return http.post('/lottery/' + actId + '/rounds/' + id + '/delete');
        },
        resetLotteryRound: function(actId, id) {
            return http.post('/lottery/' + actId + '/rounds/' + id + '/reset');
        },
        getLotteryPrizes: function(actId) {
            return http.get('/lottery/' + actId + '/prizes');
        },
        createLotteryPrize: function(actId, data) {
            return http.post('/lottery/' + actId + '/prizes', data);
        },
        updateLotteryPrize: function(actId, id, data) {
            return http.post('/lottery/' + actId + '/prizes/' + id + '/update', data);
        },
        deleteLotteryPrize: function(actId, id) {
            return http.post('/lottery/' + actId + '/prizes/' + id + '/delete');
        },
        getLotteryThemes: function(actId) {
            return http.get('/lottery/' + actId + '/themes');
        },
        getChoujiangConfig: function(actId) {
            return http.get('/lottery/' + actId + '/choujiang');
        },
        updateChoujiangConfig: function(actId, data) {
            return http.post('/lottery/' + actId + '/choujiang', data);
        },
        getImportList: function(actId) {
            return http.get('/lottery/' + actId + '/import');
        },
        batchImport: function(actId, data) {
            return http.post('/lottery/' + actId + '/import', data);
        },
        clearImportList: function(actId) {
            return http.post('/lottery/' + actId + '/import/clear');
        },

        // ---- 中奖名单 API ----
        getLotteryWinners: function(actId, params) {
            return http.get('/lottery/' + actId + '/winners', { params: params });
        },
        giveLotteryPrize: function(actId, id) {
            return http.post('/lottery/' + actId + '/winners/' + id + '/give');
        },
        cancelLotteryPrize: function(actId, id) {
            return http.post('/lottery/' + actId + '/winners/' + id + '/cancel');
        },
        deleteLotteryWinner: function(actId, id) {
            return http.post('/lottery/' + actId + '/winners/' + id + '/delete');
        },
        clearLotteryWinners: function(actId, data) {
            return http.post('/lottery/' + actId + '/winners/clear', data || {});
        },

        // ---- 内定名单 API ----
        getLotteryDesignated: function(actId, params) {
            return http.get('/lottery/' + actId + '/designated', { params: params });
        },
        addLotteryDesignated: function(actId, data) {
            return http.post('/lottery/' + actId + '/designated', data);
        },
        cancelLotteryDesignated: function(actId, id) {
            return http.post('/lottery/' + actId + '/designated/' + id + '/cancel');
        },
        searchLotteryUsers: function(actId, keyword) {
            return http.get('/lottery/' + actId + '/designated/search-users', { params: { keyword: keyword } });
        },

        // ---- 幸运手机号 API ----
        getLuckyPhoneRecords: function(actId, params) {
            return http.get('/lottery/' + actId + '/lucky-phone', { params: params });
        },

        // ---- 幸运号码 API ----
        getLuckyNumberConfig: function(actId) {
            return http.get('/lottery/' + actId + '/lucky-number/config');
        },
        updateLuckyNumberConfig: function(actId, data) {
            return http.post('/lottery/' + actId + '/lucky-number/config', data);
        },
        getLuckyNumberRecords: function(actId, params) {
            return http.get('/lottery/' + actId + '/lucky-number/records', { params: params });
        },

        // ---- 拼手速 / 游戏 API ----
        getShakeConfig: function(actId) {
            return http.get('/speed/' + actId + '/shake/config');
        },
        updateShakeConfig: function(actId, data) {
            return http.post('/speed/' + actId + '/shake/config', data);
        },
        getShakeThemes: function(actId) {
            return http.get('/speed/' + actId + '/shake/themes');
        },
        updateShakeTheme: function(actId, id, data) {
            return http.post('/speed/' + actId + '/shake/themes/' + id + '/update', data);
        },
        getShakeRanking: function(actId) {
            return http.get('/speed/' + actId + '/shake/ranking');
        },
        resetShake: function(actId) {
            return http.post('/speed/' + actId + '/shake/reset');
        },
        getGameConfig: function(actId) {
            return http.get('/speed/' + actId + '/game/config');
        },
        updateGameConfig: function(actId, data) {
            return http.post('/speed/' + actId + '/game/config', data);
        },
        getGameRanking: function(actId) {
            return http.get('/speed/' + actId + '/game/ranking');
        },
        resetGame: function(actId) {
            return http.post('/speed/' + actId + '/game/reset');
        },

        // ---- 弹幕/上墙 API ----
        getWallConfig: function(actId) {
            return http.get('/wall/' + actId + '/config');
        },
        updateWallConfig: function(actId, data) {
            return http.post('/wall/' + actId + '/config', data);
        },
        getDanmuConfig: function(actId) {
            return http.get('/wall/' + actId + '/danmu-config');
        },
        updateDanmuConfig: function(actId, data) {
            return http.post('/wall/' + actId + '/danmu-config', data);
        },
        getMessages: function(actId, params) {
            return http.get('/wall/' + actId + '/messages', { params: params });
        },
        approveMessage: function(actId, mid) {
            return http.post('/wall/' + actId + '/messages/' + mid + '/approve');
        },
        batchApproveMessages: function(actId) {
            return http.post('/wall/' + actId + '/messages/batch-approve');
        },
        deleteMessage: function(actId, mid) {
            return http.post('/wall/' + actId + '/messages/' + mid + '/delete');
        },
        toggleMessageTop: function(actId, mid) {
            return http.post('/wall/' + actId + '/messages/' + mid + '/toggle-top');
        },
        publishNotice: function(actId, data) {
            return http.post('/wall/' + actId + '/notice', data);
        },

        // ---- 投票 API ----
        getVoteItems: function(actId) {
            return http.get('/vote/' + actId + '/items');
        },
        createVoteItem: function(actId, data) {
            return http.post('/vote/' + actId + '/items', data);
        },
        updateVoteItem: function(actId, id, data) {
            return http.post('/vote/' + actId + '/items/' + id + '/update', data);
        },
        deleteVoteItem: function(actId, id) {
            return http.post('/vote/' + actId + '/items/' + id + '/delete');
        },
        resetVotes: function(actId) {
            return http.post('/vote/' + actId + '/reset');
        },

        // ---- 相册 API ----
        getAlbumPhotos: function(actId, params) {
            return http.get('/album/' + actId + '/photos', { params: params });
        },
        addAlbumPhoto: function(actId, data) {
            return http.post('/album/' + actId + '/photos', data);
        },
        deleteAlbumPhoto: function(actId, id) {
            return http.post('/album/' + actId + '/photos/' + id + '/delete');
        },
        clearAlbum: function(actId) {
            return http.post('/album/' + actId + '/clear');
        },

        // ---- 红包 API ----
        getRedpacketConfig: function(actId) {
            return http.get('/redpacket/' + actId + '/config');
        },
        updateRedpacketConfig: function(actId, data) {
            return http.post('/redpacket/' + actId + '/config', data);
        },
        getRedpacketRounds: function(actId) {
            return http.get('/redpacket/' + actId + '/rounds');
        },
        createRedpacketRound: function(actId, data) {
            return http.post('/redpacket/' + actId + '/rounds', data);
        },
        updateRedpacketRound: function(actId, id, data) {
            return http.post('/redpacket/' + actId + '/rounds/' + id + '/update', data);
        },
        deleteRedpacketRound: function(actId, id) {
            return http.post('/redpacket/' + actId + '/rounds/' + id + '/delete');
        },
        getRedpacketRecords: function(actId) {
            return http.get('/redpacket/' + actId + '/records');
        },

        // ---- 主题/背景/音乐 API ----
        getKaimuConfig: function(actId) {
            return http.get('/theme/' + actId + '/kaimu');
        },
        updateKaimuConfig: function(actId, data) {
            return http.post('/theme/' + actId + '/kaimu', data);
        },
        getBimuConfig: function(actId) {
            return http.get('/theme/' + actId + '/bimu');
        },
        updateBimuConfig: function(actId, data) {
            return http.post('/theme/' + actId + '/bimu', data);
        },
        getBackgrounds: function(actId) {
            return http.get('/theme/' + actId + '/backgrounds');
        },
        addBackground: function(actId, data) {
            return http.post('/theme/' + actId + '/backgrounds', data);
        },
        resetBackground: function(actId, plugname) {
            return http.post('/theme/' + actId + '/backgrounds/reset', { plugname: plugname });
        },
        deleteBackground: function(actId, id) {
            return http.post('/theme/' + actId + '/backgrounds/' + id + '/delete');
        },
        getMusics: function(actId) {
            return http.get('/theme/' + actId + '/musics');
        },
        addMusic: function(actId, data) {
            return http.post('/theme/' + actId + '/musics', data);
        },
        deleteMusic: function(actId, id) {
            return http.post('/theme/' + actId + '/musics/' + id + '/delete');
        },

        // ---- 背景音乐管理 API（weixin_music 表） ----
        getBgMusics: function(actId) {
            return http.get('/theme/' + actId + '/bgmusics');
        },
        toggleBgMusic: function(actId, plugname, bgmusicstatus) {
            return http.post('/theme/' + actId + '/bgmusics/toggle', { plugname: plugname, bgmusicstatus: bgmusicstatus });
        },
        uploadBgMusic: function(actId, formData) {
            return http.post('/theme/' + actId + '/bgmusics/upload', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        },

        // ---- 功能开关 API ----
        getSwitchList: function(actId) {
            return http.get('/switch/' + actId);
        },
        toggleSwitch: function(actId, code) {
            return http.post('/switch/' + actId + '/toggle/' + code);
        },

        // ---- 签到主题配置 API ----
        getSignThemeConfig: function(actId) {
            return http.get('/theme/' + actId + '/sign-theme');
        },
        updateSignThemeConfig: function(actId, data) {
            return http.post('/theme/' + actId + '/sign-theme', data);
        },

        // ---- 系统设置 API ----
        getSettings: function() {
            return http.get('/setting');
        },
        updateBusiness: function(data) {
            return http.post('/setting/business', data);
        },
        changePassword: function(data) {
            return http.post('/setting/password', data);
        },
        getMapKey: function() {
            return http.get('/setting/map-key');
        },
        searchPlace: function(keyword) {
            return http.get('/setting/place-search', { params: { keyword: keyword } });
        },
        reverseGeo: function(lat, lng) {
            return http.get('/setting/reverse-geo', { params: { lat: lat, lng: lng } });
        },
        getMobileUrls: function(activityId) {
            return http.get('/setting/mobile-urls', { params: { activity_id: activityId } });
        },
        updateWxConfig: function(data) {
            return http.post('/setting/wx-config', data);
        },

        // ---- 大屏密码管理 API ----
        getScreenPassword: function(actId) {
            return http.get('/sign/' + actId + '/screen-password');
        },
        updateScreenPassword: function(actId, data) {
            return http.post('/sign/' + actId + '/screen-password', data);
        },

        // ---- 内容安全 API ----
        getSecurityConfig: function(actId) {
            return http.get('/security/' + actId + '/config');
        },
        updateSecurityConfig: function(actId, data) {
            return http.post('/security/' + actId + '/config', data);
        },

        // ---- 文件上传 ----
        uploadImage: function(formData) {
            return http.post('/upload/image', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        },
        uploadBackground: function(formData) {
            return http.post('/upload/background', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        },
        uploadMusic: function(formData) {
            return http.post('/upload/music', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        },

        // ---- 套餐查询 API ----
        getPlans: function() {
            return http.get('/plans');
        },

        // ---- 导出 API ----
        exportParticipants: function(actId) {
            window.open(BASE_URL + '/export/participants/' + actId + '?token=' + Api.getToken());
        },
        exportMessages: function(actId) {
            window.open(BASE_URL + '/export/messages/' + actId + '?token=' + Api.getToken());
        },
        exportLottery: function(actId) {
            window.open(BASE_URL + '/export/lottery/' + actId + '?token=' + Api.getToken());
        }
    };

    global.Api = Api;
})(window);
