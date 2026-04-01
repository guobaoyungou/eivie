/**
 * 签到墙 - 样式二：矩阵墙交互逻辑
 * 功能：
 * 1. 4行×6列头像矩阵显示
 * 2. AJAX 轮询获取新签到用户
 * 3. 新用户入场：左下角/右下角交替 Toast → 矩阵末尾新增头像 → 中央大头像切换
 * 4. 超过24个头像时自动平滑循环滚动
 */
;(function(window, $) {
    'use strict';

    var SignMatrix = {
        // 配置
        config: {
            cols: 6,               // 列数
            rowsVisible: 4,        // 可见行数
            pollInterval: 5000,    // 轮询间隔 ms
            toastDuration: 3000,   // Toast 显示总时长 ms (400ms弹入 + 2600ms保持)
            toastFadeOut: 300,     // Toast 淡出时长 ms
            scrollSpeed: 0.5,      // 滚动速度 px/frame
            avatarShowDelay: 1500, // 头像入场延迟 ms (初始加载时逐个显示)
        },

        // 状态
        state: {
            mid: 0,                  // 最后签到序号
            allUsers: [],            // 所有已签到用户
            pendingUsers: [],        // 待显示的新签到用户队列
            showCount: 0,            // 已显示的头像计数
            toastSide: 'left',       // 当前 Toast 方向
            isProcessing: false,     // 是否正在处理新用户动画
            scrollOffset: 0,         // 当前滚动偏移
            isScrolling: false,      // 是否正在滚动
            scrollAnimId: null,      // requestAnimationFrame ID
            isPaused: false,         // 悬停暂停
            totalSigned: 0,          // 签到总人数
        },

        // DOM 缓存
        $grid: null,
        $scrollWrap: null,
        $centerAvatar: null,
        $centerImg: null,
        $centerName: null,
        $centerOrder: null,
        $toastLeft: null,
        $toastRight: null,
        $counter: null,
        $waiting: null,

        /**
         * 初始化
         */
        init: function(initialCount) {
            this.state.totalSigned = initialCount || 0;
            this._cacheDom();
            this._bindEvents();
            this._updateCounter(this.state.totalSigned);

            // 开始轮询
            var self = this;
            setTimeout(function() {
                self._pollSign(0);
            }, 1000);

            // 3秒后开始处理队列
            setTimeout(function() {
                self._processQueue();
            }, 3000);
        },

        /**
         * 缓存 DOM 元素
         */
        _cacheDom: function() {
            this.$grid = $('#signMatrixGrid');
            this.$scrollWrap = $('#gridScrollWrap');
            this.$centerAvatar = $('#signCenterAvatar');
            this.$centerImg = $('#centerAvatarImg');
            this.$centerName = $('#centerAvatarName');
            this.$centerOrder = $('#centerAvatarOrder');
            this.$toastLeft = $('#toastLeft');
            this.$toastRight = $('#toastRight');
            this.$counter = $('#signCounterNum');
            this.$waiting = $('#signWaiting');
        },

        /**
         * 绑定事件
         */
        _bindEvents: function() {
            var self = this;
            // 悬停暂停滚动
            this.$grid.on('mouseenter', function() {
                self.state.isPaused = true;
            }).on('mouseleave', function() {
                self.state.isPaused = false;
            });
        },

        /**
         * AJAX 轮询获取新签到
         */
        _pollSign: function(lastMid) {
            var self = this;
            $.getJSON(Path_url('ajax_act_get_sign.php'), {
                mid: lastMid,
                num: 50
            }, function(json) {
                if (json.code < 0) {
                    setTimeout(function() { self._pollSign(lastMid); }, self.config.pollInterval);
                    return;
                }

                var users = json.data.users || [];
                var newMid = json.data.mid || lastMid;

                if (users.length > 0) {
                    // 添加到待处理队列
                    self.state.pendingUsers = self.state.pendingUsers.concat(users);
                }

                self.state.mid = newMid;
                setTimeout(function() { self._pollSign(newMid); }, self.config.pollInterval);
            }).fail(function() {
                setTimeout(function() { self._pollSign(lastMid); }, self.config.pollInterval);
            });
        },

        /**
         * 处理待显示用户队列
         */
        _processQueue: function() {
            var self = this;

            if (self.state.pendingUsers.length === 0) {
                setTimeout(function() { self._processQueue(); }, 1000);
                return;
            }

            if (self.state.isProcessing) {
                setTimeout(function() { self._processQueue(); }, 500);
                return;
            }

            self.state.isProcessing = true;
            var user = self.state.pendingUsers.shift();
            self.state.showCount++;
            self.state.totalSigned = Math.max(self.state.totalSigned, self.state.showCount);

            // 隐藏等待提示
            if (self.$waiting.is(':visible')) {
                self.$waiting.fadeOut(300);
            }

            // 显示中央大头像（如果还没显示）
            if (!self.$centerAvatar.is(':visible')) {
                self.$centerAvatar.fadeIn(500);
            }

            // 步骤1：显示 Toast（左下/右下交替）
            self._showToast(user, function() {
                // 步骤2：矩阵末尾添加头像
                self._addAvatarToGrid(user);

                // 步骤3：切换中央大头像
                self._switchCenterAvatar(user);

                // 步骤4：更新计数
                self._updateCounter(self.state.totalSigned);

                // 步骤5：检查是否需要启动滚动
                self._checkScroll();

                // 处理完成，延迟后继续
                setTimeout(function() {
                    self.state.isProcessing = false;
                    self._processQueue();
                }, 800);
            });
        },

        /**
         * 显示 Toast 通知
         */
        _showToast: function(user, callback) {
            var self = this;
            var side = self.state.toastSide;
            var $toast = side === 'left' ? self.$toastLeft : self.$toastRight;
            var $other = side === 'left' ? self.$toastRight : self.$toastLeft;

            // 切换方向
            self.state.toastSide = side === 'left' ? 'right' : 'left';

            // 隐藏另一个
            $other.removeClass('toast-show toast-hide');

            // 设置内容
            $toast.find('.toast-avatar img').attr('src', user.avatar);
            $toast.find('.toast-nick').text(user.nickname);
            $toast.find('.toast-label').text('签到成功');

            // 弹入
            $toast.removeClass('toast-hide').addClass('toast-show');

            // 400ms弹入 + 回调（同时开始添加头像）
            setTimeout(function() {
                if (callback) callback();
            }, 400);

            // 总显示时长后淡出
            setTimeout(function() {
                $toast.removeClass('toast-show').addClass('toast-hide');
            }, self.config.toastDuration);

            // 淡出完成后重置
            setTimeout(function() {
                $toast.removeClass('toast-hide');
                $toast.css('opacity', '');
            }, self.config.toastDuration + self.config.toastFadeOut);
        },

        /**
         * 矩阵末尾新增头像
         */
        _addAvatarToGrid: function(user) {
            var $cell = $(
                '<div class="avatar-cell entering">' +
                    '<div class="avatar-img-wrap"><img src="' + user.avatar + '" alt=""></div>' +
                    '<div class="avatar-nick">' + this._escapeHtml(user.nickname) + '</div>' +
                '</div>'
            );

            this.$scrollWrap.append($cell);

            // 入场动画结束后移除 entering 类
            setTimeout(function() {
                $cell.removeClass('entering').addClass('shown');
            }, 850);

            this.state.allUsers.push(user);
        },

        /**
         * 平滑切换中央大头像
         */
        _switchCenterAvatar: function(user) {
            var self = this;
            var $img = self.$centerImg;

            // 添加切换过渡类
            $img.addClass('switching');

            setTimeout(function() {
                $img.attr('src', user.avatar);
                self.$centerName.text(user.nickname);
                self.$centerOrder.text('第 ' + self.state.showCount + ' 位签到');

                // 移除切换过渡类，图片淡入
                setTimeout(function() {
                    $img.removeClass('switching');
                }, 50);
            }, 300);
        },

        /**
         * 更新签到计数
         */
        _updateCounter: function(count) {
            this.$counter.text(count);
        },

        /**
         * 检查是否需要自动滚动
         */
        _checkScroll: function() {
            var maxVisible = this.config.cols * this.config.rowsVisible; // 24
            if (this.state.allUsers.length > maxVisible && !this.state.isScrolling) {
                this._startScroll();
            }
        },

        /**
         * 启动自动滚动
         * 使用 requestAnimationFrame 实现 60fps 平滑滚动
         */
        _startScroll: function() {
            var self = this;
            self.state.isScrolling = true;

            var $wrap = self.$scrollWrap;
            var gridHeight = self.$grid.height();

            function scrollStep() {
                if (!self.state.isPaused) {
                    self.state.scrollOffset += self.config.scrollSpeed;

                    var wrapHeight = $wrap.outerHeight();

                    // 如果滚动到一半以上，创建克隆实现无缝循环
                    if (self.state.scrollOffset >= wrapHeight - gridHeight) {
                        self.state.scrollOffset = 0;
                    }

                    $wrap.css('transform', 'translateY(-' + self.state.scrollOffset + 'px)');
                }

                self.state.scrollAnimId = requestAnimationFrame(scrollStep);
            }

            self.state.scrollAnimId = requestAnimationFrame(scrollStep);
        },

        /**
         * HTML 转义
         */
        _escapeHtml: function(str) {
            if (!str) return '';
            return str.replace(/&/g, '&amp;')
                      .replace(/</g, '&lt;')
                      .replace(/>/g, '&gt;')
                      .replace(/"/g, '&quot;')
                      .replace(/'/g, '&#039;');
        }
    };

    // 暴露到全局
    window.SignMatrix = SignMatrix;

})(window, jQuery);
