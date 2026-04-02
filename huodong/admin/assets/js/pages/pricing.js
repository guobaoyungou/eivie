/**
 * 套餐选购弹窗模块
 * 在用户下拉菜单中点击"套餐选购"后打开 layui.layer 弹窗
 */
;(function(global) {
    'use strict';

    // 功能代码 -> 中文名映射
    var FEATURE_NAMES = {
        'qdq':                 '签到墙',
        'threedimensionalsign': '3D签到',
        'wall':                '上墙互动',
        'danmu':               '弹幕互动',
        'vote':                '现场投票',
        'lottery':             '大屏抽奖',
        'choujiang':           '手机抽奖',
        'ydj':                 '摇大奖',
        'shake':               '摇一摇竞技',
        'game':                '互动游戏',
        'redpacket':           '红包雨',
        'importlottery':       '导入抽奖',
        'kaimu':               '开幕式',
        'bimu':                '闭幕式',
        'xiangce':             '相册PPT',
        'xyh':                 '幸运盒子',
        'xysjh':               '幸运时间盒'
    };

    // 各套餐的功能展示列表（用于卡片中的 ✅/❌ 显示）
    var DISPLAY_FEATURES = [
        'qdq', 'threedimensionalsign', 'wall', 'danmu', 'vote',
        'lottery', 'choujiang', 'shake', 'game', 'redpacket'
    ];

    var PricingPage = {
        /**
         * 打开套餐选购弹窗
         */
        showPricingModal: function() {
            var self = this;
            var loadIdx = layui.layer.load(2);

            // 并行请求套餐列表和用户信息
            Promise.all([
                Api.getPlans(),
                Api.getProfile()
            ]).then(function(results) {
                layui.layer.close(loadIdx);
                var plans = results[0].data || [];
                var profile = results[1].data || {};
                self._openModal(plans, profile);
            }).catch(function(err) {
                layui.layer.close(loadIdx);
                layui.layer.msg('加载套餐信息失败，请重试', { icon: 2 });
                console.error('加载套餐信息失败:', err);
            });
        },

        /**
         * 打开弹窗并渲染内容
         */
        _openModal: function(plans, profile) {
            var self = this;
            var planInfo = profile.plan || {};
            var content = self._buildModalContent(plans, planInfo);

            var isMobile = window.innerWidth <= 767;
            layui.layer.open({
                type: 1,
                title: '<i class="fas fa-crown" style="color:#FB8C00;margin-right:8px;"></i>套餐选购',
                area: isMobile ? ['100%', '100%'] : ['90%', '85%'],
                maxmin: true,
                content: content,
                shadeClose: true,
                success: function(layero) {
                    self._bindCardEvents(layero, plans, planInfo);
                }
            });
        },

        /**
         * 构建弹窗 HTML 内容
         */
        _buildModalContent: function(plans, planInfo) {
            var html = '<div class="pricing-modal-wrapper">';

            // ---- 顶部：当前套餐状态卡片 ----
            html += this._buildCurrentPlanCard(planInfo);

            // ---- 主体：套餐卡片网格 ----
            html += '<div class="pricing-grid">';
            for (var i = 0; i < plans.length; i++) {
                html += this._buildPlanCard(plans[i], planInfo);
            }
            html += '</div>';

            html += '</div>';
            return html;
        },

        /**
         * 构建当前套餐状态卡片
         */
        _buildCurrentPlanCard: function(planInfo) {
            var name = planInfo.name || '无套餐';
            var isValid = !!planInfo.is_valid;
            var expireDate = planInfo.expire_date || '--';

            var statusClass = isValid ? 'pricing-status-active' : 'pricing-status-expired';
            var statusText = isValid ? '有效' : '已过期';

            var html = '<div class="pricing-current-plan ' + statusClass + '">';
            html += '<div class="pricing-current-info">';
            html += '<div class="pricing-current-left">';
            html += '<h3><i class="fas fa-gem"></i> 当前套餐：<strong>' + this._esc(name) + '</strong>';
            html += ' <span class="pricing-badge ' + statusClass + '">' + statusText + '</span></h3>';
            html += '<p class="pricing-expire">到期时间：' + this._esc(expireDate) + '</p>';
            html += '</div>';
            html += '<div class="pricing-current-right">';

            // 配额使用情况
            if (planInfo.max_stores !== undefined) {
                var storeUsed = planInfo.store_count || 0;
                var storeMax = planInfo.max_stores || 0;
                html += '<span class="pricing-quota"><i class="fas fa-store"></i> 门店 ' + storeUsed + '/' + storeMax + '</span>';
            }
            if (planInfo.max_activities !== undefined) {
                var actUsed = planInfo.activity_count || 0;
                var actMax = planInfo.max_activities || 0;
                html += '<span class="pricing-quota"><i class="fas fa-calendar-alt"></i> 活动 ' + actUsed + '/' + actMax + '</span>';
            }
            if (planInfo.max_participants !== undefined) {
                html += '<span class="pricing-quota"><i class="fas fa-users"></i> 人数上限 ' + planInfo.max_participants + '/活动</span>';
            }

            html += '</div>';
            html += '</div>';

            if (!isValid) {
                html += '<div class="pricing-renew-tip"><i class="fas fa-exclamation-triangle"></i> 套餐已过期，请续费或升级以继续使用全部功能</div>';
            }

            html += '</div>';
            return html;
        },

        /**
         * 构建单个套餐卡片
         */
        _buildPlanCard: function(plan, currentPlan) {
            var isCurrentPlan = currentPlan.name === plan.name;
            var isRecommended = plan.is_recommended;
            var isCustom = plan.code === 'custom';

            var cardClass = 'pricing-card';
            if (isRecommended) cardClass += ' pricing-card-recommended';
            if (isCurrentPlan) cardClass += ' pricing-card-current';

            var html = '<div class="' + cardClass + '" data-plan-code="' + plan.code + '">';

            // 推荐角标
            if (isRecommended) {
                html += '<div class="pricing-card-badge">推荐</div>';
            }

            // 套餐名称
            html += '<div class="pricing-card-header">';
            html += '<h3>' + this._esc(plan.name) + '</h3>';
            html += '</div>';

            // 价格区域
            html += '<div class="pricing-card-price">';
            if (isCustom) {
                html += '<span class="pricing-price-amount">联系客服</span>';
                html += '<span class="pricing-price-period">定制方案</span>';
            } else if (plan.price === 0) {
                html += '<span class="pricing-price-amount">免费</span>';
                html += '<span class="pricing-price-period">/ ' + this._esc(plan.period) + '</span>';
            } else {
                html += '<span class="pricing-price-symbol">¥</span>';
                html += '<span class="pricing-price-amount">' + (plan.price / 100) + '</span>';
                html += '<span class="pricing-price-period">/ ' + this._esc(plan.period) + '</span>';
            }
            html += '</div>';

            // 配额信息
            html += '<div class="pricing-card-quotas">';
            if (isCustom) {
                html += '<div class="pricing-quota-item"><i class="fas fa-store"></i> 门店数量 <strong>定制</strong></div>';
                html += '<div class="pricing-quota-item"><i class="fas fa-calendar-alt"></i> 活动数量 <strong>定制</strong></div>';
                html += '<div class="pricing-quota-item"><i class="fas fa-users"></i> 单活动人数 <strong>定制</strong></div>';
            } else {
                html += '<div class="pricing-quota-item"><i class="fas fa-store"></i> 门店上限 <strong>' + plan.max_stores + ' 个</strong></div>';
                html += '<div class="pricing-quota-item"><i class="fas fa-calendar-alt"></i> 活动上限 <strong>' + plan.max_activities + ' 个</strong></div>';
                html += '<div class="pricing-quota-item"><i class="fas fa-users"></i> 单活动人数 <strong>' + plan.max_participants + ' 人</strong></div>';
            }
            html += '</div>';

            // 功能清单
            html += '<div class="pricing-card-features">';
            var planFeatures = plan.features || [];
            for (var j = 0; j < DISPLAY_FEATURES.length; j++) {
                var fCode = DISPLAY_FEATURES[j];
                var fName = FEATURE_NAMES[fCode] || fCode;
                var hasFeature = planFeatures.indexOf(fCode) > -1;
                if (isCustom) hasFeature = true;
                html += '<div class="pricing-feature-item ' + (hasFeature ? 'has' : 'no') + '">';
                html += '<i class="fas ' + (hasFeature ? 'fa-check-circle' : 'fa-times-circle') + '"></i> ';
                html += this._esc(fName);
                html += '</div>';
            }
            // 企业版额外功能
            if (plan.code === 'enterprise' || isCustom) {
                html += '<div class="pricing-feature-item has"><i class="fas fa-check-circle"></i> 专属客服</div>';
                html += '<div class="pricing-feature-item has"><i class="fas fa-check-circle"></i> 优先支持</div>';
            }
            if (isCustom) {
                html += '<div class="pricing-feature-item has"><i class="fas fa-check-circle"></i> 定制开发</div>';
            }
            html += '</div>';

            // 操作按钮
            html += '<div class="pricing-card-action">';
            if (isCurrentPlan) {
                html += '<button class="pricing-btn pricing-btn-current" disabled><i class="fas fa-check"></i> 当前套餐</button>';
            } else if (isCustom) {
                html += '<button class="pricing-btn pricing-btn-contact" data-action="contact"><i class="fas fa-headset"></i> 联系客服</button>';
            } else if (plan.price === 0) {
                html += '<button class="pricing-btn pricing-btn-trial" data-action="trial"><i class="fas fa-rocket"></i> 免费试用</button>';
            } else {
                html += '<button class="pricing-btn pricing-btn-buy" data-action="buy"><i class="fas fa-shopping-cart"></i> 立即购买</button>';
            }
            html += '</div>';

            html += '</div>';
            return html;
        },

        /**
         * 绑定卡片事件
         */
        _bindCardEvents: function(layero, plans, planInfo) {
            var self = this;
            var cards = layero[0].querySelectorAll('.pricing-card');

            cards.forEach(function(card) {
                var btn = card.querySelector('.pricing-btn');
                if (!btn || btn.disabled) return;

                var action = btn.getAttribute('data-action');
                var planCode = card.getAttribute('data-plan-code');

                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (action === 'contact') {
                        self._showContactModal();
                    } else if (action === 'trial') {
                        layui.layer.msg('免费试用功能即将上线，敬请期待！', { icon: 6 });
                    } else if (action === 'buy') {
                        layui.layer.msg('在线购买功能即将上线，敬请期待！', { icon: 6 });
                    }
                });
            });
        },

        /**
         * 显示联系客服弹窗
         */
        _showContactModal: function() {
            var html = '<div style="padding:30px;text-align:center;">';
            html += '<div style="margin-bottom:20px;"><i class="fas fa-headset" style="font-size:48px;color:#1E88E5;"></i></div>';
            html += '<h3 style="margin-bottom:16px;font-size:18px;color:#333;">联系客服获取定制方案</h3>';
            html += '<div style="background:#f8f9fa;border-radius:8px;padding:20px;margin-bottom:16px;text-align:left;">';
            html += '<p style="margin-bottom:12px;font-size:14px;"><i class="fab fa-weixin" style="color:#07C160;margin-right:8px;"></i> 客服微信：<strong style="color:#333;">eivie_hd</strong></p>';
            html += '<p style="font-size:14px;"><i class="fas fa-envelope" style="color:#1E88E5;margin-right:8px;"></i> 客服邮箱：<strong style="color:#333;">support@eivie.cn</strong></p>';
            html += '</div>';
            html += '<p style="color:#999;font-size:13px;">请联系客服获取定制方案报价</p>';
            html += '</div>';

            layui.layer.open({
                type: 1,
                title: '联系客服',
                area: ['400px', 'auto'],
                content: html,
                shadeClose: true
            });
        },

        /**
         * HTML 转义
         */
        _esc: function(str) {
            if (!str) return '';
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    };

    global.PricingPage = PricingPage;
})(window);
