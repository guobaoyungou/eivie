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
                    try {
                        var json = JSON.parse(xhr.responseText);
                        callback(null, json);
                    } catch(e){
                        callback(e, null);
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
                    body.push(encodeURIComponent(k) + '=' + encodeURIComponent(data[k]));
                }
            }
            xhr.send(body.join('&'));
        } else {
            xhr.send();
        }
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
        }
    };
})();
