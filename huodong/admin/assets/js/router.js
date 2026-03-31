/**
 * 轻量级 Hash Router
 * 支持路径参数、路由守卫、404 回退
 */
;(function(global) {
    'use strict';

    function HashRouter() {
        this.routes = [];
        this.beforeEach = null;
        this.afterEach = null;
        this.notFoundHandler = null;
        this._current = null;
        this._listening = false;
    }

    HashRouter.prototype = {
        /**
         * 注册路由
         * @param {string} path - 路由路径，如 /dashboard、/lottery/:id/prizes
         * @param {Function} handler - 路由处理函数 handler(params, query)
         * @param {Object} meta - 路由元信息（面包屑、标题等）
         */
        on: function(path, handler, meta) {
            var regex = this._pathToRegex(path);
            this.routes.push({
                path: path,
                regex: regex.regex,
                keys: regex.keys,
                handler: handler,
                meta: meta || {}
            });
            return this;
        },

        /**
         * 设置全局前置守卫
         * @param {Function} fn - fn(to, from, next)
         */
        guard: function(fn) {
            this.beforeEach = fn;
            return this;
        },

        /**
         * 设置 404 处理
         * @param {Function} fn
         */
        notFound: function(fn) {
            this.notFoundHandler = fn;
            return this;
        },

        /**
         * 开始监听 hash 变化
         */
        start: function() {
            if (this._listening) return;
            this._listening = true;
            var self = this;
            window.addEventListener('hashchange', function() {
                self._resolve();
            });
            // 首次加载
            this._resolve();
            return this;
        },

        /**
         * 导航到指定路由
         * @param {string} path
         */
        push: function(path) {
            window.location.hash = '#' + path;
        },

        /**
         * 替换当前路由（不产生历史记录）
         * @param {string} path
         */
        replace: function(path) {
            var url = window.location.href.split('#')[0] + '#' + path;
            window.location.replace(url);
        },

        /**
         * 获取当前路由信息
         */
        current: function() {
            return this._current;
        },

        /**
         * 解析当前 hash 并匹配路由
         */
        _resolve: function() {
            var hash = window.location.hash.slice(1) || '/dashboard';
            var parts = hash.split('?');
            var path = parts[0];
            var queryStr = parts[1] || '';
            var query = this._parseQuery(queryStr);
            var from = this._current;

            for (var i = 0; i < this.routes.length; i++) {
                var route = this.routes[i];
                var match = path.match(route.regex);
                if (match) {
                    var params = {};
                    for (var j = 0; j < route.keys.length; j++) {
                        params[route.keys[j]] = decodeURIComponent(match[j + 1] || '');
                    }
                    var to = {
                        path: path,
                        params: params,
                        query: query,
                        meta: route.meta,
                        route: route
                    };

                    if (this.beforeEach) {
                        var self = this;
                        this.beforeEach(to, from, function(redirect) {
                            if (redirect === false) return;
                            if (typeof redirect === 'string') {
                                self.push(redirect);
                                return;
                            }
                            self._current = to;
                            route.handler(params, query, to.meta);
                            if (self.afterEach) self.afterEach(to, from);
                        });
                    } else {
                        this._current = to;
                        route.handler(params, query, to.meta);
                        if (this.afterEach) this.afterEach(to, from);
                    }
                    return;
                }
            }

            // 未匹配到路由
            if (this.notFoundHandler) {
                this.notFoundHandler(path);
            }
        },

        /**
         * 将路径模式转为正则
         */
        _pathToRegex: function(path) {
            var keys = [];
            var pattern = path.replace(/:([^/]+)/g, function(_, key) {
                keys.push(key);
                return '([^/]+)';
            });
            return {
                regex: new RegExp('^' + pattern + '$'),
                keys: keys
            };
        },

        /**
         * 解析查询字符串
         */
        _parseQuery: function(str) {
            var query = {};
            if (!str) return query;
            str.split('&').forEach(function(pair) {
                var kv = pair.split('=');
                if (kv[0]) {
                    query[decodeURIComponent(kv[0])] = decodeURIComponent(kv[1] || '');
                }
            });
            return query;
        }
    };

    global.HashRouter = HashRouter;
})(window);
