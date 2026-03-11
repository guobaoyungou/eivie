/**
 * api.js — 数据请求封装
 */
var Api = (function(){
    function request(method, url, data, callback){
        var xhr = new XMLHttpRequest();
        if(method === 'GET' && data){
            var params = [];
            for(var k in data){
                if(data.hasOwnProperty(k) && data[k] !== undefined && data[k] !== ''){
                    params.push(encodeURIComponent(k) + '=' + encodeURIComponent(data[k]));
                }
            }
            if(params.length) url += (url.indexOf('?') > -1 ? '&' : '?') + params.join('&');
        }
        xhr.open(method, url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        if(method === 'POST'){
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        }
        xhr.onreadystatechange = function(){
            if(xhr.readyState === 4){
                if(xhr.status === 200){
                    var json = null;
                    var parseError = null;
                    try {
                        json = JSON.parse(xhr.responseText);
                    } catch(e){
                        parseError = e;
                    }
                    if(parseError){
                        callback(parseError, null);
                    } else {
                        callback(null, json);
                    }
                } else {
                    callback(new Error('HTTP ' + xhr.status), null);
                }
            }
        };
        if(method === 'POST' && data){
            var body = [];
            for(var k in data){
                if(data.hasOwnProperty(k)){
                    if(Array.isArray(data[k])){
                        data[k].forEach(function(v){
                            body.push(encodeURIComponent(k + '[]') + '=' + encodeURIComponent(v));
                        });
                    } else {
                        body.push(encodeURIComponent(k) + '=' + encodeURIComponent(data[k]));
                    }
                }
            }
            xhr.send(body.join('&'));
        } else {
            xhr.send();
        }
    }

    function requestFormData(url, formData, callback){
        var xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function(){
            if(xhr.readyState === 4){
                if(xhr.status === 200){
                    var json = null;
                    var parseError = null;
                    try {
                        json = JSON.parse(xhr.responseText);
                    } catch(e){
                        parseError = e;
                    }
                    if(parseError){
                        callback(parseError, null);
                    } else {
                        callback(null, json);
                    }
                } else {
                    callback(new Error('HTTP ' + xhr.status), null);
                }
            }
        };
        xhr.send(formData);
    }

    return {
        getSceneList: function(params, cb){
            request('GET', '/?s=/index/scene_list', params, cb);
        },
        search: function(params, cb){
            request('GET', '/?s=/index/search', params, cb);
        },
        getModelList: function(params, cb){
            request('GET', '/?s=/index/model_list', params, cb);
        },
        getModelsByProvider: function(params, cb){
            request('GET', '/?s=/index/model_list_by_provider', params, cb);
        },
        getModelDetail: function(params, cb){
            request('GET', '/?s=/index/model_detail', params, cb);
        },
        getSceneDetail: function(params, cb){
            request('GET', '/?s=/index/scene_template_detail', params, cb);
        },
        createGenerationOrder: function(params, cb){
            request('POST', '/?s=/index/create_generation_order', params, cb);
        },
        checkLogin: function(cb){
            request('GET', '/?s=/index/check_login', null, cb);
        },
        sendSms: function(params, cb){
            request('POST', '/?s=/index/send_sms', params, cb);
        },
        phoneLogin: function(params, cb){
            request('POST', '/?s=/index/phone_login', params, cb);
        },
        logout: function(cb){
            request('POST', '/?s=/index/logout', null, cb);
        },
        uploadImage: function(file, cb){
            var fd = new FormData();
            fd.append('file', file);
            requestFormData('/?s=/index/upload_image', fd, cb);
        },
        getCreativeMemberPlans: function(cb){
            request('GET', '/?s=/index/creative_member_plans', null, cb);
        },
        buyCreativeMember: function(params, cb){
            request('POST', '/?s=/index/buy_creative_member', params, cb);
        },
        // ===== 充值相关 =====
        getRechargeConfig: function(cb){
            request('GET', '/?s=/index/recharge_config', null, cb);
        },
        createRechargeOrder: function(params, cb){
            request('POST', '/?s=/index/create_recharge_order', params, cb);
        },
        // ===== 积分购买相关 =====
        getScoreConfig: function(cb){
            request('GET', '/?s=/index/score_config', null, cb);
        },
        createScoreOrder: function(params, cb){
            request('POST', '/?s=/index/create_score_order', params, cb);
        },
        // ===== 会员等级相关 =====
        getLevelList: function(cb){
            request('GET', '/?s=/index/level_list', null, cb);
        },
        applyLevel: function(params, cb){
            request('POST', '/?s=/index/apply_level', params, cb);
        },
        // ===== 个人中心 =====
        getUserCenterData: function(cb){
            request('GET', '/?s=/index/user_center_data', null, cb);
        },
        // ===== 支付相关 =====
        h5Pay: function(params, cb){
            if(params && !params.platform) params.platform = 'pc';
            request('POST', '/?s=/index/h5_pay', params, cb);
        },
        checkPayStatus: function(params, cb){
            request('GET', '/?s=/index/check_pay_status', params, cb);
        },
        getPayConfig: function(cb){
            request('GET', '/?s=/index/pay_config', {platform: 'pc'}, cb);
        },
        // ===== 云端存储空间 =====
        getStorageInfo: function(cb){
            request('GET', '/?s=/index/user_storage_info', null, cb);
        },
        getStorageFiles: function(params, cb){
            request('GET', '/?s=/index/user_storage_files', params, cb);
        },
        deleteStorageFiles: function(params, cb){
            request('POST', '/?s=/index/delete_storage_file', params, cb);
        },
        checkStorageQuota: function(params, cb){
            request('POST', '/?s=/index/check_storage_quota', params, cb);
        },
        // ===== 登录设置相关 =====
        getPcLoginConfig: function(cb){
            request('GET', '/?s=/index/pc_login_config', null, cb);
        },
        createQrLoginTicket: function(cb){
            request('GET', '/?s=/index/create_qr_login_ticket', null, cb);
        },
        checkQrLoginStatus: function(params, cb){
            request('GET', '/?s=/index/check_qr_login_status', params, cb);
        }
    };
})();
