/**
 * 签到墙 - 样式二：矩阵墙交互逻辑
 * 功能：
 * 1. 5行×12列头像矩阵显示
 * 2. AJAX 轮询获取新签到用户
 * 3. 新用户入场：弹射飞入中央 → 停留展示 → 镜面翻转缩小飞出至矩阵格子
 * 4. 奇偶签到者交替使用左下角/右下角入场方向
 * 5. 超过60个头像时自动平滑循环滚动
 * 6. 无新签到时随机从已签到名单抽取用户播放入场/出场动画（防冷场）
 */
;(function(window, $) {
    'use strict';

    var SignMatrix = {
        // 配置
        config: {
            cols: 12,              // 列数
            rowsVisible: 5,        // 可见行数
            pollInterval: 5000,    // 轮询间隔 ms
            toastDuration: 3000,   // Toast 显示总时长 ms (400ms弹入 + 2600ms保持)
            toastFadeOut: 300,     // Toast 淡出时长 ms
            scrollSpeed: 0.5,      // 滚动速度 px/frame
            avatarShowDelay: 1500, // 头像入场延迟 ms (初始加载时逐个显示)
            flyInDuration: 700,    // 弹射飞入总时长 ms（含回弹归位）
            centerStayDuration: 2000, // 中央停留展示时长 ms
            flyOutDuration: 1600,  // 镜面翻转飞出时长 ms（2次旋转）
            flyOutFadeStart: 1200, // 飞出动画渐变时刻（相对飞出开始，ms）
            flyInRotateDeg: 25,    // 入场初始旋转角度
            queueInterval: 800,    // 队列处理间隔（动画结束后到下一位开始的等待时长，ms）
            idleDelay: 5000,        // 队列空闲后多久触发空闲动画 ms
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
            $flyingAvatar: null,     // 当前飞行头像DOM引用
            flyAnimPhase: 'idle',    // 当前动画阶段：idle / flyIn / stay / flyOut
            currentTargetCell: null, // 当前飞出的目标矩阵格子DOM引用
            flyInAnimation: null,    // Web Animations API 动画引用
            centerX: 0,              // 居中位置 X
            centerY: 0,              // 居中位置 Y
            idleTimer: null,         // 空闲动画定时器
            isIdleAnimation: false,  // 当前是否为空闲动画（非真实签到）
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
         * 处理待显示用户队列 — 完整动画序列编排
         * 飞入(700ms) → 停留展示(2000ms) → 镜面翻转飞出(1600ms) → 清理
         * 队列为空时自动切换到空闲动画模式（防冷场）
         */
        _processQueue: function() {
            var self = this;

            if (self.state.pendingUsers.length === 0) {
                // 队列为空 — 如果有已签到用户，启动空闲动画
                if (self.state.allUsers.length > 0 && !self.state.isProcessing) {
                    self._scheduleIdleAnimation();
                } else {
                    setTimeout(function() { self._processQueue(); }, 1000);
                }
                return;
            }

            // 有真实新用户，取消空闲动画计时
            self._cancelIdleTimer();

            if (self.state.isProcessing) {
                setTimeout(function() { self._processQueue(); }, 500);
                return;
            }

            self.state.isProcessing = true;
            self.state.isIdleAnimation = false;
            var user = self.state.pendingUsers.shift();
            self.state.showCount++;
            self.state.totalSigned = Math.max(self.state.totalSigned, self.state.showCount);

            // 隐藏等待提示
            if (self.$waiting.is(':visible')) {
                self.$waiting.fadeOut(300);
            }

            // 显示中央文字区域（图片由飞行头像替代，center-ring已CSS隐藏）
            if (!self.$centerAvatar.is(':visible')) {
                self.$centerAvatar.show();
            }

            // 确定入场方向（奇数左下角，偶数右下角）
            var side = self.state.toastSide;

            // === 阶段1：弹射飞入 ===
            self.state.flyAnimPhase = 'flyIn';
            self._createFlyingAvatar(user);
            self._animateFlyIn(side, function() {

                // === 阶段2：中央停留展示 ===
                self.state.flyAnimPhase = 'stay';

                // 更新中央区域信息
                self._updateCenterInfo(user);

                // 同步触发 Toast 通知
                self._showToast(user);

                // 停留展示期间等待
                setTimeout(function() {

                    // === 阶段2.5：插入矩阵格子（不可见）并获取坐标 ===
                    var $cell = self._addAvatarToGrid(user);
                    self.state.currentTargetCell = $cell;

                    // 淡出中央信息
                    self._fadeOutCenterInfo();

                    // === 阶段3：镜面翻转缩小飞出 ===
                    self.state.flyAnimPhase = 'flyOut';
                    self._animateFlyOut($cell, function() {

                        // === 清理阶段 ===
                        self._cleanupFlyingAvatar();

                        // 飞行头像到达目标，直接显示格子头像（如落入该位置）
                        $cell.removeClass('cell-hidden').addClass('shown');

                        // 更新计数 + 检查滚动
                        self._updateCounter(self.state.totalSigned);
                        self._checkScroll();

                        // 处理完成，间隔后继续下一位
                        self.state.flyAnimPhase = 'idle';
                        setTimeout(function() {
                            self.state.isProcessing = false;
                            self._processQueue();
                        }, self.config.queueInterval);
                    });

                }, self.config.centerStayDuration);
            });
        },

        /**
         * 显示 Toast 通知（在飞行头像到达中央后触发）
         */
        _showToast: function(user) {
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
         * 矩阵末尾新增头像（先插入不可见格子，返回DOM引用）
         */
        _addAvatarToGrid: function(user) {
            var $cell = $(
                '<div class="avatar-cell cell-hidden">' +
                    '<div class="avatar-img-wrap"><img src="' + user.avatar + '" alt=""></div>' +
                    '<div class="avatar-nick">' + this._escapeHtml(user.nickname) + '</div>' +
                '</div>'
            );

            this.$scrollWrap.append($cell);
            this.state.allUsers.push(user);

            return $cell;
        },

        /**
         * 更新中央区域文字信息（昵称+签到序号，不更新图片）
         * @param {Object} user - 用户数据
         * @param {Number} [orderNum] - 可选，自定义显示序号（空闲动画用）
         */
        _updateCenterInfo: function(user, orderNum) {
            var self = this;
            var displayOrder = orderNum || self.state.showCount;

            // 更新文字
            self.$centerName.text(user.nickname);
            self.$centerOrder.text('第 ' + displayOrder + ' 位签到');

            // 淡入动画
            self.$centerName.removeClass('info-fade-out').addClass('info-fade-in');
            self.$centerOrder.removeClass('info-fade-out').addClass('info-fade-in');
        },

        /**
         * 中央信息淡出隐藏
         */
        _fadeOutCenterInfo: function() {
            var self = this;
            self.$centerName.removeClass('info-fade-in').addClass('info-fade-out');
            self.$centerOrder.removeClass('info-fade-in').addClass('info-fade-out');
        },

        /**
         * 创建飞行头像克隆DOM元素
         */
        _createFlyingAvatar: function(user) {
            var self = this;

            // 清理之前可能残留的飞行头像
            if (self.state.$flyingAvatar) {
                self.state.$flyingAvatar.remove();
            }

            var $flying = $(
                '<div class="sign-flying-avatar">' +
                    '<div class="flying-ring">' +
                        '<div class="flying-img">' +
                            '<img src="' + user.avatar + '" alt="">' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );

            // 初始状态：不可见
            $flying.css({
                opacity: 0,
                transform: 'scale(0.2)'
            });

            // 插入到签到页容器
            $('.sign-matrix-page').append($flying);
            self.state.$flyingAvatar = $flying;
        },

        /**
         * 执行阶段1：弹射飞入动画（Web Animations API 动态计算居中位置）
         */
        _animateFlyIn: function(side, callback) {
            var self = this;
            var $flying = self.state.$flyingAvatar;
            if (!$flying) return;

            var el = $flying[0];
            var container = $('.sign-matrix-page')[0];
            var containerRect = container.getBoundingClientRect();

            // 获取中央头像区域的ring位置，作为飞行目标点（精确居中）
            var $ring = self.$centerAvatar.find('.center-ring');
            var ringRect = $ring[0].getBoundingClientRect();
            var centerX = ringRect.left - containerRect.left;
            var centerY = ringRect.top - containerRect.top;

            // 存储居中位置供飞出动画复用
            self.state.centerX = centerX;
            self.state.centerY = centerY;

            // 起始位置（容器坐标系）
            var startX, startY, startRotate;
            if (side === 'left') {
                startX = -150;
                startY = containerRect.height + 150;
                startRotate = 25;
            } else {
                startX = containerRect.width + 150;
                startY = containerRect.height + 150;
                startRotate = -25;
            }

            var bounceSign = side === 'left' ? 1 : -1;

            // 设置基础位置
            $flying.css({ left: '0px', top: '0px', opacity: '', transform: '' });

            // 中间点计算（飞行弧线）
            var midX = startX + (centerX - startX) * 0.35;
            var midY = startY + (centerY - startY) * 0.5;

            // Web Animations API 关键帧
            var keyframes = [
                { // 0% - 弹射起始
                    transform: 'translate(' + startX + 'px, ' + startY + 'px) scale(0.2) rotate(' + startRotate + 'deg)',
                    opacity: 0,
                    offset: 0
                },
                { // 15% - 快速放大，透明度已满
                    transform: 'translate(' + midX + 'px, ' + midY + 'px) scale(0.5) rotate(' + (startRotate * 0.72) + 'deg)',
                    opacity: 1,
                    offset: 0.15
                },
                { // 55% - 过冲到位
                    transform: 'translate(' + (centerX + bounceSign * 8) + 'px, ' + (centerY - 8) + 'px) scale(1.08) rotate(0deg)',
                    opacity: 1,
                    offset: 0.55
                },
                { // 80% - 回弹修正
                    transform: 'translate(' + (centerX - bounceSign * 8) + 'px, ' + (centerY + 5) + 'px) scale(0.97) rotate(' + (-bounceSign * 3) + 'deg)',
                    opacity: 1,
                    offset: 0.80
                },
                { // 100% - 阻尼归位
                    transform: 'translate(' + centerX + 'px, ' + centerY + 'px) scale(1) rotate(0deg)',
                    opacity: 1,
                    offset: 1
                }
            ];

            var animation = el.animate(keyframes, {
                duration: self.config.flyInDuration,
                easing: 'cubic-bezier(0.17, 0.67, 0.29, 1.30)',
                fill: 'forwards'
            });

            self.state.flyInAnimation = animation;

            // 飞入尾声触发光晕脉冲
            setTimeout(function() {
                $flying.addClass('glow-pulse');
            }, self.config.flyInDuration - 100);

            // 飞入完成后回调
            animation.onfinish = function() {
                if (callback) callback();
            };
        },

        /**
         * 执行阶段3：镜面翻转缩小飞出至目标矩阵格子（不渐隐，到达后直接显示格子）
         */
        _animateFlyOut: function($targetCell, callback) {
            var self = this;
            var $flying = self.state.$flyingAvatar;
            if (!$flying || !$targetCell || !$targetCell.length) {
                if (callback) callback();
                return;
            }

            var el = $flying[0];
            var container = $('.sign-matrix-page')[0];
            var containerRect = container.getBoundingClientRect();

            // 获取目标格子屏幕坐标（相对容器）
            var cellRect = $targetCell[0].getBoundingClientRect();
            var targetX = cellRect.left - containerRect.left;
            var targetY = cellRect.top - containerRect.top;

            // 当前居中位置
            var centerX = self.state.centerX;
            var centerY = self.state.centerY;

            // 获取尺寸计算缩放比
            var flyingSize = $flying.find('.flying-ring').outerWidth() || 280;
            var targetSize = $targetCell.find('.avatar-img-wrap').outerWidth() || 90;
            var scaleRatio = targetSize / flyingSize;

            // 取消飞入动画
            if (self.state.flyInAnimation) {
                self.state.flyInAnimation.cancel();
                self.state.flyInAnimation = null;
            }

            // 移除glow-pulse类
            $flying.removeClass('glow-pulse');

            // 设置当前位置为起点
            el.style.left = '0px';
            el.style.top = '0px';
            el.style.opacity = '1';
            el.style.transform = 'translate(' + centerX + 'px, ' + centerY + 'px) scale(1)';
            el.offsetHeight; // 强制回流

            // 飞出动画关键帧（不渐隐，保持完全可见）
            var flyOutAnim = el.animate([
                {
                    transform: 'translate(' + centerX + 'px, ' + centerY + 'px) scale(1) rotateY(0deg)',
                    opacity: 1,
                    offset: 0
                },
                {
                    transform: 'translate(' + targetX + 'px, ' + targetY + 'px) scale(' + scaleRatio + ') rotateY(720deg)',
                    opacity: 1,
                    offset: 1
                }
            ], {
                duration: self.config.flyOutDuration,
                easing: 'cubic-bezier(0.55, 0.06, 0.68, 0.19)',
                fill: 'forwards'
            });

            // 飞出完成后回调
            flyOutAnim.onfinish = function() {
                if (callback) callback();
            };
        },

        /**
         * 清理飞行头像DOM，重置动画状态
         */
        _cleanupFlyingAvatar: function() {
            var self = this;
            if (self.state.flyInAnimation) {
                self.state.flyInAnimation.cancel();
                self.state.flyInAnimation = null;
            }
            if (self.state.$flyingAvatar) {
                self.state.$flyingAvatar.remove();
                self.state.$flyingAvatar = null;
            }
            self.state.currentTargetCell = null;
        },

        /**
         * 获取矩阵格子的屏幕坐标
         */
        _getTargetPosition: function(cellElement) {
            var rect = cellElement.getBoundingClientRect();
            return {
                x: rect.left,
                y: rect.top
            };
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
            var maxVisible = this.config.cols * this.config.rowsVisible; // 60
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

        // ========== 空闲动画（防冷场）==========

        /**
         * 调度空闲动画 — 队列为空时延迟触发
         */
        _scheduleIdleAnimation: function() {
            var self = this;
            if (self.state.idleTimer || self.state.isProcessing) return;

            self.state.idleTimer = setTimeout(function() {
                self.state.idleTimer = null;

                // 再次检查：如果有真实新用户进来，优先处理
                if (self.state.pendingUsers.length > 0) {
                    self._processQueue();
                    return;
                }

                self._playIdleAnimation();
            }, self.config.idleDelay);
        },

        /**
         * 取消空闲动画计时
         */
        _cancelIdleTimer: function() {
            if (this.state.idleTimer) {
                clearTimeout(this.state.idleTimer);
                this.state.idleTimer = null;
            }
        },

        /**
         * 播放空闲动画 — 随机抽取已签到用户，完整飞入→停留→飞出动画
         * 不新增格子，飞出至已有的随机格子位置
         */
        _playIdleAnimation: function() {
            var self = this;

            // 安全检查
            if (self.state.isProcessing || self.state.allUsers.length === 0) {
                self._scheduleIdleAnimation();
                return;
            }

            // 如果真实用户进来了，优先处理
            if (self.state.pendingUsers.length > 0) {
                self._processQueue();
                return;
            }

            // 随机抽取一位已签到用户
            var randomIndex = Math.floor(Math.random() * self.state.allUsers.length);
            var user = self.state.allUsers[randomIndex];
            var displayOrder = randomIndex + 1; // 显示为原始签到顺序

            // 随机选取一个已显示的格子作为飞出目标
            var $cells = self.$scrollWrap.find('.avatar-cell.shown');
            if ($cells.length === 0) {
                self._scheduleIdleAnimation();
                return;
            }
            var cellIndex = Math.floor(Math.random() * $cells.length);
            var $targetCell = $cells.eq(cellIndex);

            self.state.isProcessing = true;
            self.state.isIdleAnimation = true;

            // 确定入场方向
            var side = self.state.toastSide;

            // 显示中央区域
            if (!self.$centerAvatar.is(':visible')) {
                self.$centerAvatar.show();
            }

            // === 阶段1：弹射飞入 ===
            self.state.flyAnimPhase = 'flyIn';
            self._createFlyingAvatar(user);
            self._animateFlyIn(side, function() {

                // 如果动画过程中来了真实新用户，记住但不中断当前动画
                // （让本轮动画自然完成后再处理真实用户）

                // === 阶段2：中央停留展示 ===
                self.state.flyAnimPhase = 'stay';
                self._updateCenterInfo(user, displayOrder);
                self._showToast(user);

                setTimeout(function() {

                    // 淡出中央信息
                    self._fadeOutCenterInfo();

                    // === 阶段3：镜面翻转飞出至已有格子 ===
                    self.state.flyAnimPhase = 'flyOut';
                    self._animateFlyOut($targetCell, function() {

                        // === 清理阶段 ===
                        self._cleanupFlyingAvatar();
                        // 不修改格子状态（格子已经是shown状态）

                        self.state.flyAnimPhase = 'idle';
                        self.state.isIdleAnimation = false;

                        setTimeout(function() {
                            self.state.isProcessing = false;

                            // 优先处理真实新用户，否则继续空闲动画
                            if (self.state.pendingUsers.length > 0) {
                                self._processQueue();
                            } else {
                                self._scheduleIdleAnimation();
                            }
                        }, self.config.queueInterval);
                    });

                }, self.config.centerStayDuration);
            });
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
