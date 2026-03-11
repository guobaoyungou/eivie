/**
 * auth.js — 会员登录状态管理 & 头部/侧边栏UI更新
 * 跨页面共享模块，所有引用 header.html 的页面均需加载
 */
var Auth = (function(){
    var DEFAULT_AVATAR = '/static/index3/img/default_avatar.svg';

    var state = {
        isLoggedIn: false,
        loginChecked: false,
        loginUser: null,
        userDropdownVisible: false
    };

    // ========== 初始化 ==========
    function init(){
        checkLogin();
        initUserDropdownEvents();
        initTabbarProfile();
    }

    // ========== 登录检测 ==========
    function checkLogin(callback){
        Api.checkLogin(function(err, res){
            if(!err && res && res.status === 1){
                state.isLoggedIn = true;
                state.loginUser = res.data || null;
                updateHeaderForLoggedIn(res.data);
                updateSidebarForLoggedIn(res.data);
            } else {
                state.isLoggedIn = false;
                state.loginUser = null;
                updateHeaderForGuest();
                updateSidebarForGuest();
            }
            state.loginChecked = true;
            if(callback) callback(state.isLoggedIn);
        });
    }

    // ========== 顶部栏：已登录态 ==========
    function updateHeaderForLoggedIn(user){
        if(!user) return;

        // 1. 替换"会员中心"按钮为余额积分摘要
        var vipBtn = document.querySelector('.header-btn-vip');
        if(vipBtn){
            vipBtn.style.display = 'none';
        }
        var balanceInfo = document.getElementById('headerBalanceInfo');
        if(balanceInfo){
            balanceInfo.querySelector('.header-balance-money').textContent = '¥' + (user.money || '0.00');
            balanceInfo.querySelector('.header-balance-score').textContent = (user.score || 0) + '分';
            balanceInfo.style.display = 'inline-flex';
        }

        // 2. 显示消息图标
        var msgIcon = document.getElementById('headerMsgIcon');
        if(msgIcon) msgIcon.style.display = 'inline-flex';

        // 3. 隐藏"登录/注册"按钮，显示头像
        var loginBtn = document.getElementById('headerLoginBtn');
        if(loginBtn) loginBtn.style.display = 'none';

        var avatarWrap = document.getElementById('headerAvatarWrap');
        if(avatarWrap){
            var img = avatarWrap.querySelector('.header-user-avatar');
            if(img){
                img.src = user.headimg || DEFAULT_AVATAR;
                img.onerror = function(){ this.src = DEFAULT_AVATAR; };
            }
            avatarWrap.style.display = 'inline-flex';
        }

        // 4. 渲染下拉弹窗内容
        renderUserDropdown(user);
    }

    // ========== 顶部栏：未登录态 ==========
    function updateHeaderForGuest(){
        var vipBtn = document.querySelector('.header-btn-vip');
        if(vipBtn) vipBtn.style.display = '';

        var balanceInfo = document.getElementById('headerBalanceInfo');
        if(balanceInfo) balanceInfo.style.display = 'none';

        var msgIcon = document.getElementById('headerMsgIcon');
        if(msgIcon) msgIcon.style.display = 'none';

        var loginBtn = document.getElementById('headerLoginBtn');
        if(loginBtn) loginBtn.style.display = '';

        var avatarWrap = document.getElementById('headerAvatarWrap');
        if(avatarWrap) avatarWrap.style.display = 'none';

        // 隐藏下拉弹窗
        var dropdown = document.getElementById('userDropdown');
        if(dropdown) dropdown.classList.remove('show');
        state.userDropdownVisible = false;
    }

    // ========== 构建创作会员订阅卡片 ==========
    function buildSubscriptionCard(user){
        if(!user) return '';
        if(user.has_creative_member){
            // 已订阅状态
            return '<div class="ud-subscribe-card subscribed">' +
                '<div class="ud-sc-header">' +
                    '<span class="ud-sc-badge">&#x1F451; ' + escapeHtml(user.creative_version_name || '创作会员') + '</span>' +
                    '<a class="ud-sc-manage" href="/CreativeMember/index">管理</a>' +
                '</div>' +
                '<div class="ud-sc-info">' +
                    '<div class="ud-sc-info-item">' +
                        '<span class="ud-sc-info-label">到期时间</span>' +
                        '<span class="ud-sc-info-value">' + escapeHtml(user.creative_expire_text || '--') + '</span>' +
                    '</div>' +
                    '<div class="ud-sc-info-item">' +
                        '<span class="ud-sc-info-label">剩余积分</span>' +
                        '<span class="ud-sc-info-value">' + (user.creative_remaining_score || 0) + '</span>' +
                    '</div>' +
                '</div>' +
            '</div>';
        } else {
            // 未订阅状态 — 促销CTA
            return '<div class="ud-subscribe-card not-subscribed">' +
                '<div class="ud-sc-promo">' +
                    '<div class="ud-sc-promo-text">' +
                        '<div class="ud-sc-promo-title">&#x2728; 开通创作会员</div>' +
                        '<div class="ud-sc-promo-desc">解锁更多AI创作能力，专属积分加赠</div>' +
                    '</div>' +
                    '<button class="ud-sc-cta-btn" data-action="subscribe">立即开通</button>' +
                '</div>' +
            '</div>';
        }
    }

    // ========== 绑定订阅卡片事件 ==========
    function bindSubscriptionCardEvents(container){
        if(!container) return;
        // 「立即开通」按钮
        var ctaBtn = container.querySelector('.ud-sc-cta-btn[data-action="subscribe"]');
        if(ctaBtn){
            ctaBtn.addEventListener('click', function(e){
                e.stopPropagation();
                closeUserDropdown();
                showSubscriptionPopup();
            });
        }
        // 「管理」链接
        var manageLink = container.querySelector('.ud-sc-manage');
        if(manageLink){
            manageLink.addEventListener('click', function(e){
                e.preventDefault();
                e.stopPropagation();
                closeUserDropdown();
                showSubscriptionPopup();
            });
        }
    }

    // ========== 渲染用户信息下拉弹窗 ==========
    function renderUserDropdown(user){
        var dropdown = document.getElementById('userDropdown');
        if(!dropdown) return;

        var avatarUrl = user.headimg || DEFAULT_AVATAR;
        var nickname = escapeHtml(user.nickname || '用户');
        var mid = user.mid || 0;
        var levelName = escapeHtml(user.level_name || '普通会员');
        var levelIcon = user.level_icon || '';
        var money = user.money || '0.00';
        var score = user.score || 0;

        var levelHtml = levelIcon
            ? '<img class="ud-level-icon" src="' + escapeHtml(levelIcon) + '" alt="">'
            : '<span class="ud-level-emoji">👑</span>';

        dropdown.innerHTML =
            '<!-- 用户信息区 -->' +
            '<div class="ud-user-section">' +
                '<img class="ud-avatar" src="' + escapeHtml(avatarUrl) + '" alt="头像" onerror="this.src=\'' + DEFAULT_AVATAR + '\'">' +
                '<div class="ud-user-info">' +
                    '<div class="ud-nickname">' + nickname + '</div>' +
                    '<div class="ud-meta">' +
                        '<span class="ud-mid">ID: ' + mid + '</span>' +
                        '<a class="ud-level-tag" href="/?s=/index/member_level" style="text-decoration:none;color:inherit;cursor:pointer">' + levelHtml + levelName + '</a>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            '<!-- 资产概览区 -->' +
            '<div class="ud-asset-section">' +
                '<a class="ud-asset-item" href="/?s=/index/recharge" style="text-decoration:none;color:inherit;cursor:pointer">' +
                    '<div class="ud-asset-label">账户余额</div>' +
                    '<div class="ud-asset-value">¥' + escapeHtml(money) + '</div>' +
                '</a>' +
                '<div class="ud-asset-divider"></div>' +
                '<a class="ud-asset-item" href="/?s=/index/score_shop" style="text-decoration:none;color:inherit;cursor:pointer">' +
                    '<div class="ud-asset-label">积分余额</div>' +
                    '<div class="ud-asset-value">' + score + '</div>' +
                '</a>' +
                '<div class="ud-asset-divider"></div>' +
                '<a class="ud-asset-item" href="/?s=/index/user_storage" style="text-decoration:none;color:inherit;cursor:pointer">' +
                    '<div class="ud-asset-label">云端空间</div>' +
                    '<div class="ud-asset-value" id="udStorageValue">加载中</div>' +
                '</a>' +
            '</div>' +
            '<!-- 创作会员订阅卡片 -->' +
            buildSubscriptionCard(user) +
            '<!-- 菜单项区 -->' +
            '<div class="ud-menu-section">' +
                '<a class="ud-menu-item" href="/?s=/index/user_center" data-action="profile">' +
                    '<span class="ud-menu-icon">👤</span><span>个人中心</span>' +
                '</a>' +
                '<a class="ud-menu-item" href="/?s=/index/member_level" data-action="level">' +
                    '<span class="ud-menu-icon">👑</span><span>我的等级</span>' +
                '</a>' +
                '<a class="ud-menu-item" href="/?s=/index/recharge" data-action="wallet">' +
                    '<span class="ud-menu-icon">📦</span><span>余额充值</span>' +
                '</a>' +
            '</div>' +
            '<div class="ud-divider"></div>' +
            '<!-- 退出登录 -->' +
            '<div class="ud-logout-section">' +
                '<button class="ud-logout" id="udLogoutBtn">🚪 退出登录</button>' +
            '</div>';

        // 绑定退出登录
        var logoutBtn = document.getElementById('udLogoutBtn');
        if(logoutBtn){
            logoutBtn.addEventListener('click', function(e){
                e.stopPropagation();
                handleLogout();
            });
        }

        // 绑定订阅卡片事件
        bindSubscriptionCardEvents(dropdown);

        // 加载云端空间信息
        loadStorageInfo();
    }

    // ========== 加载云端空间信息 ==========
    function loadStorageInfo(){
        Api.getStorageInfo(function(err, res){
            var text = '--';
            if(!err && res && res.status === 1 && res.data){
                var d = res.data;
                text = d.used_gb + ' / ' + d.total_quota_gb + ' GB';
            }
            var el1 = document.getElementById('udStorageValue');
            if(el1) el1.textContent = text;
            var el2 = document.getElementById('mobileStorageValue');
            if(el2) el2.textContent = text;
        });
    }

    // ========== 切换下拉弹窗 ==========
    function toggleUserDropdown(){
        var dropdown = document.getElementById('userDropdown');
        if(!dropdown) return;
        state.userDropdownVisible = !state.userDropdownVisible;
        dropdown.classList.toggle('show', state.userDropdownVisible);
    }

    // ========== 关闭下拉弹窗 ==========
    function closeUserDropdown(){
        var dropdown = document.getElementById('userDropdown');
        if(dropdown) dropdown.classList.remove('show');
        state.userDropdownVisible = false;
    }

    // ========== 下拉弹窗事件绑定 ==========
    function initUserDropdownEvents(){
        // 点击头像切换弹窗
        var avatarWrap = document.getElementById('headerAvatarWrap');
        if(avatarWrap){
            avatarWrap.addEventListener('click', function(e){
                e.stopPropagation();
                toggleUserDropdown();
            });
        }

        // 点击弹窗外关闭
        document.addEventListener('click', function(e){
            if(state.userDropdownVisible){
                var dropdown = document.getElementById('userDropdown');
                if(dropdown && !dropdown.contains(e.target)){
                    closeUserDropdown();
                }
            }
        });

        // Esc关闭
        document.addEventListener('keydown', function(e){
            if(e.key === 'Escape' && state.userDropdownVisible){
                closeUserDropdown();
            }
        });
    }

    // ========== 退出登录 ==========
    function handleLogout(){
        Api.logout(function(err, res){
            state.isLoggedIn = false;
            state.loginUser = null;
            state.loginChecked = true;
            closeUserDropdown();
            updateHeaderForGuest();
            updateSidebarForGuest();
            showAuthToast('已退出登录', 'success');
        });
    }

    // ========== 侧边栏：已登录态 ==========
    function updateSidebarForLoggedIn(user){
        if(!user) return;
        var drawerUser = document.querySelector('.drawer-user');
        if(!drawerUser) return;

        var loginBtn = drawerUser.querySelector('.login-btn');
        if(loginBtn) loginBtn.style.display = 'none';

        var userInfo = drawerUser.querySelector('.drawer-user-info');
        if(!userInfo){
            userInfo = document.createElement('div');
            userInfo.className = 'drawer-user-info';
            drawerUser.appendChild(userInfo);
        }
        userInfo.innerHTML =
            '<img class="drawer-user-avatar" src="' + escapeHtml(user.headimg || DEFAULT_AVATAR) + '" alt="" onerror="this.src=\'' + DEFAULT_AVATAR + '\'">' +
            '<span class="drawer-user-name">' + escapeHtml(user.nickname || '用户') + '</span>' +
            '<span class="ud-level-tag"><span class="ud-level-emoji">👑</span>' + escapeHtml(user.level_name || '普通会员') + '</span>';
        userInfo.style.display = 'flex';
    }

    // ========== 侧边栏：未登录态 ==========
    function updateSidebarForGuest(){
        var drawerUser = document.querySelector('.drawer-user');
        if(!drawerUser) return;

        var loginBtn = drawerUser.querySelector('.login-btn');
        if(loginBtn) loginBtn.style.display = '';

        var userInfo = drawerUser.querySelector('.drawer-user-info');
        if(userInfo) userInfo.style.display = 'none';
    }

    // ========== TabBar"我的"按钮 ==========
    function initTabbarProfile(){
        var profileBtn = document.querySelector('.tabbar-item[data-action="profile"]');
        if(!profileBtn) return;

        profileBtn.addEventListener('click', function(e){
            e.preventDefault();
            e.stopPropagation();
            if(state.isLoggedIn && state.loginUser){
                showMobileUserDrawer();
            } else {
                window.location.href = '/Backstage/index';
            }
        });
    }

    // ========== 移动端底部抽屉 ==========
    function showMobileUserDrawer(){
        // 检查是否已存在
        var existing = document.getElementById('mobileUserDrawer');
        if(existing){
            existing.classList.add('show');
            var ov = document.getElementById('mobileUserDrawerOverlay');
            if(ov) ov.classList.add('show');
            return;
        }

        var user = state.loginUser;
        if(!user) return;

        // 创建遮罩
        var overlay = document.createElement('div');
        overlay.id = 'mobileUserDrawerOverlay';
        overlay.className = 'mobile-drawer-overlay show';
        document.body.appendChild(overlay);

        // 创建抽屉
        var drawer = document.createElement('div');
        drawer.id = 'mobileUserDrawer';
        drawer.className = 'mobile-user-drawer show';
        drawer.innerHTML = renderUserDropdown_html(user) +
            '<div class="ud-divider"></div>' +
            '<div class="ud-logout-section">' +
                '<button class="ud-logout" id="mobileLogoutBtn">🚪 退出登录</button>' +
            '</div>';
        document.body.appendChild(drawer);

        // 绑定关闭
        overlay.addEventListener('click', closeMobileUserDrawer);

        // 绑定退出
        var logoutBtn = document.getElementById('mobileLogoutBtn');
        if(logoutBtn){
            logoutBtn.addEventListener('click', function(){
                handleLogout();
                closeMobileUserDrawer();
            });
        }

        // 绑定订阅卡片事件
        bindSubscriptionCardEvents(drawer);
    }

    function closeMobileUserDrawer(){
        var drawer = document.getElementById('mobileUserDrawer');
        var overlay = document.getElementById('mobileUserDrawerOverlay');
        if(drawer) drawer.classList.remove('show');
        if(overlay) overlay.classList.remove('show');
        setTimeout(function(){
            if(drawer && drawer.parentNode) drawer.parentNode.removeChild(drawer);
            if(overlay && overlay.parentNode) overlay.parentNode.removeChild(overlay);
        }, 300);
    }

    // 生成抽屉内容 HTML（复用弹窗结构）
    function renderUserDropdown_html(user){
        var avatarUrl = user.headimg || DEFAULT_AVATAR;
        var nickname = escapeHtml(user.nickname || '用户');
        var mid = user.mid || 0;
        var levelName = escapeHtml(user.level_name || '普通会员');
        var money = user.money || '0.00';
        var score = user.score || 0;

        return '<div class="ud-user-section">' +
            '<img class="ud-avatar" src="' + escapeHtml(avatarUrl) + '" alt="" onerror="this.src=\'' + DEFAULT_AVATAR + '\'">' +
            '<div class="ud-user-info">' +
                '<div class="ud-nickname">' + nickname + '</div>' +
                '<div class="ud-meta">' +
                    '<span class="ud-mid">ID: ' + mid + '</span>' +
                    '<a class="ud-level-tag" href="/?s=/index/member_level" style="text-decoration:none;color:inherit"><span class="ud-level-emoji">👑</span>' + levelName + '</a>' +
                '</div>' +
            '</div>' +
        '</div>' +
        '<div class="ud-asset-section">' +
            '<a class="ud-asset-item" href="/?s=/index/recharge" style="text-decoration:none;color:inherit;cursor:pointer">' +
                '<div class="ud-asset-label">账户余额</div>' +
                '<div class="ud-asset-value">¥' + escapeHtml(money) + '</div>' +
            '</a>' +
            '<div class="ud-asset-divider"></div>' +
            '<a class="ud-asset-item" href="/?s=/index/score_shop" style="text-decoration:none;color:inherit;cursor:pointer">' +
                '<div class="ud-asset-label">积分余额</div>' +
                '<div class="ud-asset-value">' + score + '</div>' +
            '</a>' +
            '<div class="ud-asset-divider"></div>' +
            '<a class="ud-asset-item" href="/?s=/index/user_storage" style="text-decoration:none;color:inherit;cursor:pointer">' +
                '<div class="ud-asset-label">云端空间</div>' +
                '<div class="ud-asset-value" id="mobileStorageValue">加载中</div>' +
            '</a>' +
        '</div>' +
        buildSubscriptionCard(user) +
        '<div class="ud-menu-section">' +
            '<a class="ud-menu-item" href="/?s=/index/user_center" data-action="profile">' +
                '<span class="ud-menu-icon">👤</span><span>个人中心</span>' +
            '</a>' +
            '<a class="ud-menu-item" href="/?s=/index/member_level" data-action="level">' +
                '<span class="ud-menu-icon">👑</span><span>我的等级</span>' +
            '</a>' +
            '<a class="ud-menu-item" href="/?s=/index/recharge" data-action="wallet">' +
                '<span class="ud-menu-icon">📦</span><span>余额充值</span>' +
            '</a>' +
        '</div>';
    }

    // ========== 创作会员订阅 —— 跳转到独立页面 ==========
    function showSubscriptionPopup(){
        window.location.href = '/?s=/index/creative_member';
    }

    function hideSubscriptionPopup(){
        var overlay = document.getElementById('subModalOverlay');
        if(overlay){
            overlay.classList.remove('show');
            document.body.style.overflow = '';
            setTimeout(function(){
                if(overlay.parentNode) overlay.parentNode.removeChild(overlay);
            }, 300);
        }
    }

    function renderSubscriptionPlans(container, plans, currentSub){
        if(!plans || plans.length === 0){
            container.innerHTML = '<div class="sub-empty">\u6682\u65e0\u53ef\u7528\u5957\u9910</div>';
            return;
        }

        // 按版本分组
        var grouped = {};
        var versionOrder = [];
        for(var i = 0; i < plans.length; i++){
            var p = plans[i];
            var code = p.version_code;
            if(!grouped[code]){
                grouped[code] = { name: p.version_name, code: code, modes: {} };
                versionOrder.push(code);
            }
            grouped[code].modes[p.purchase_mode] = p;
        }

        // 当前订阅信息
        var currentHtml = '';
        if(currentSub){
            currentHtml =
                '<div class="sub-current-banner">' +
                    '<span class="sub-current-badge">\ud83d\udc51 \u5f53\u524d\u5957\u9910</span>' +
                    '<span class="sub-current-name">' + escapeHtml(currentSub.version_name || '') + '</span>' +
                    '<span class="sub-current-expire">\u5230\u671f: ' + escapeHtml(currentSub.expire_text || '--') + '</span>' +
                '</div>';
        }

        // 套餐卡片
        var cardsHtml = '';
        var modeLabels = { yearly: '\u6309\u5e74', monthly_auto: '\u8fde\u7eed\u5305\u6708', monthly: '\u5355\u6708' };
        // Determine recommended version (middle one or second)
        var recommendedIdx = versionOrder.length >= 2 ? 1 : 0;
        for(var vi = 0; vi < versionOrder.length; vi++){
            var ver = grouped[versionOrder[vi]];
            // 选取\u4f18\u5148\u5c55\u793a\u7684\u8d2d\u4e70\u6a21\u5f0f\uff08\u5e74\u4ed8 > \u5355\u6708 > \u8fde\u7eed\u5305\u6708\uff09
            var modePriority = ['yearly', 'monthly', 'monthly_auto'];
            var defaultPlan = null;
            var allModes = [];
            for(var mi = 0; mi < modePriority.length; mi++){
                if(ver.modes[modePriority[mi]]){
                    if(!defaultPlan) defaultPlan = ver.modes[modePriority[mi]];
                    allModes.push(modePriority[mi]);
                }
            }
            if(!defaultPlan) continue;

            var isCurrentVersion = currentSub && currentSub.version_code === ver.code;
            var isRecommended = (vi === recommendedIdx) && !isCurrentVersion;
            var cardClass = 'sub-plan-card' + (isCurrentVersion ? ' current' : '') + (isRecommended ? ' recommended' : '');

            // 购买模式切换按钮
            var modeTabsHtml = '';
            if(allModes.length > 1){
                modeTabsHtml = '<div class="sub-mode-tabs" data-version="' + ver.code + '">';
                for(var ti = 0; ti < allModes.length; ti++){
                    var m = allModes[ti];
                    var activeClass = (ver.modes[m] === defaultPlan) ? ' active' : '';
                    modeTabsHtml += '<button class="sub-mode-tab' + activeClass + '" data-mode="' + m + '">' + (modeLabels[m] || m) + '</button>';
                }
                modeTabsHtml += '</div>';
            }

            // 价格\u533a
            var priceHtml = '<div class="sub-plan-price-row">';
            priceHtml += '<span class="sub-plan-price">\u00a5' + defaultPlan.price.toFixed(2) + '</span>';
            if(defaultPlan.original_price && defaultPlan.original_price > defaultPlan.price){
                priceHtml += '<span class="sub-plan-original-price">\u00a5' + defaultPlan.original_price.toFixed(2) + '</span>';
            }
            if(defaultPlan.discount_text){
                priceHtml += '<span class="sub-plan-discount">' + escapeHtml(defaultPlan.discount_text) + '</span>';
            }
            priceHtml += '</div>';

            // 权\u76ca\u5217\u8868
            var featHtml = '<ul class="sub-plan-features">';
            featHtml += '<li>\u6bcf\u6708 <strong>' + defaultPlan.monthly_score + '</strong> \u521b\u4f5c\u79ef\u5206</li>';
            if(defaultPlan.daily_login_score > 0){
                featHtml += '<li>\u6bcf\u65e5\u767b\u5f55\u8d60 <strong>' + defaultPlan.daily_login_score + '</strong> \u79ef\u5206</li>';
            }
            if(defaultPlan.max_concurrency > 0 && defaultPlan.max_concurrency < 999){
                featHtml += '<li>\u540c\u65f6 <strong>' + defaultPlan.max_concurrency + '</strong> \u4e2a\u4efb\u52a1\u5e76\u53d1</li>';
            } else if(defaultPlan.max_concurrency >= 999){
                featHtml += '<li><strong>\u65e0\u9650</strong>\u4efb\u52a1\u5e76\u53d1</li>';
            }
            if(defaultPlan.cloud_storage_gb > 0){
                featHtml += '<li><strong>' + defaultPlan.cloud_storage_gb + 'GB</strong> \u4e91\u5b58\u50a8\u7a7a\u95f4</li>';
            }
            // 自\u5b9a\u4e49features
            if(defaultPlan.features && defaultPlan.features.length){
                for(var fi = 0; fi < defaultPlan.features.length; fi++){
                    featHtml += '<li>' + escapeHtml(defaultPlan.features[fi]) + '</li>';
                }
            }
            featHtml += '</ul>';

            // 按\u94ae
            var btnHtml = '';
            if(isCurrentVersion){
                btnHtml = '<button class="sub-plan-btn current" disabled>\u5f53\u524d\u5957\u9910</button>';
            } else {
                btnHtml = '<button class="sub-plan-btn" data-plan-id="' + defaultPlan.id + '" data-mode="' + defaultPlan.purchase_mode + '">\u7acb\u5373\u8ba2\u9605</button>';
            }

            cardsHtml +=
                '<div class="' + cardClass + '" data-version="' + ver.code + '">' +
                    '<div class="sub-plan-name">' + escapeHtml(ver.name) + '</div>' +
                    modeTabsHtml +
                    priceHtml +
                    featHtml +
                    btnHtml +
                '</div>';
        }

        container.innerHTML = currentHtml + '<div class="sub-plan-grid">' + cardsHtml + '</div>';

        // 绑\u5b9a\u8d2d\u4e70\u6a21\u5f0f\u5207\u6362
        var modeTabs = container.querySelectorAll('.sub-mode-tab');
        for(var k = 0; k < modeTabs.length; k++){
            modeTabs[k].addEventListener('click', function(){
                var tab = this;
                var mode = tab.getAttribute('data-mode');
                var versionCode = tab.parentNode.getAttribute('data-version');
                var card = container.querySelector('.sub-plan-card[data-version="' + versionCode + '"]');
                if(!card) return;

                // \u5207\u6362active
                var siblings = tab.parentNode.querySelectorAll('.sub-mode-tab');
                for(var s = 0; s < siblings.length; s++) siblings[s].classList.remove('active');
                tab.classList.add('active');

                // \u66f4\u65b0\u4ef7\u683c\u548c\u6309\u94ae
                var ver = grouped[versionCode];
                var plan = ver.modes[mode];
                if(!plan) return;
                var priceRow = card.querySelector('.sub-plan-price-row');
                if(priceRow){
                    var html = '<span class="sub-plan-price">\u00a5' + plan.price.toFixed(2) + '</span>';
                    if(plan.original_price && plan.original_price > plan.price){
                        html += '<span class="sub-plan-original-price">\u00a5' + plan.original_price.toFixed(2) + '</span>';
                    }
                    if(plan.discount_text){
                        html += '<span class="sub-plan-discount">' + escapeHtml(plan.discount_text) + '</span>';
                    }
                    priceRow.innerHTML = html;
                }
                var btn = card.querySelector('.sub-plan-btn');
                if(btn && !btn.disabled){
                    btn.setAttribute('data-plan-id', plan.id);
                    btn.setAttribute('data-mode', plan.purchase_mode);
                }
            });
        }

        // \u7ed1\u5b9a\u8d2d\u4e70\u6309\u94ae
        var buyBtns = container.querySelectorAll('.sub-plan-btn:not([disabled])');
        for(var b = 0; b < buyBtns.length; b++){
            buyBtns[b].addEventListener('click', function(){
                var btn = this;
                var planId = btn.getAttribute('data-plan-id');
                var mode = btn.getAttribute('data-mode');
                if(!planId) return;
                handleBuyPlan(btn, planId, mode);
            });
        }
    }

    function handleBuyPlan(btn, planId, purchaseMode){
        btn.disabled = true;
        btn.textContent = '\u5904\u7406\u4e2d...';
        Api.buyCreativeMember({plan_id: planId, purchase_mode: purchaseMode}, function(err, res){
            if(err || !res){
                showAuthToast('\u8bf7\u6c42\u5931\u8d25\uff0c\u8bf7\u91cd\u8bd5', 'error');
                btn.disabled = false;
                btn.textContent = '\u7acb\u5373\u8ba2\u9605';
                return;
            }
            if(res.status !== 1){
                showAuthToast(res.msg || '\u8d2d\u4e70\u5931\u8d25', 'error');
                btn.disabled = false;
                btn.textContent = '\u7acb\u5373\u8ba2\u9605';
                return;
            }
            var data = res.data || {};
            if(!data.need_pay){
                // \u514d\u8d39\u5957\u9910\uff0c\u76f4\u63a5\u6fc0\u6d3b
                showAuthToast('\u5f00\u901a\u6210\u529f\uff01', 'success');
                hideSubscriptionPopup();
                // \u5237\u65b0\u767b\u5f55\u72b6\u6001
                checkLogin();
            } else {
                // \u9700\u8981\u652f\u4ed8\uff0c\u5148\u9690\u85cf\u8ba2\u9605\u5f39\u7a97\uff0c\u7136\u540e\u8c03\u8d77\u652f\u4ed8\u6d41\u7a0b
                hideSubscriptionPopup();
                btn.disabled = false;
                btn.textContent = '\u7acb\u5373\u8ba2\u9605';
                if(data.ordernum && window.Pay){
                    Pay.startPay({
                        ordernum: data.ordernum,
                        order_type: 'creative_member',
                        amount: data.price || '0.00',
                        title: '\u521b\u4f5c\u4f1a\u5458-' + (data.plan_name || '\u8ba2\u9605'),
                        onSuccess: function(){
                            showAuthToast('\u652f\u4ed8\u6210\u529f\uff0c\u4f1a\u5458\u5df2\u5f00\u901a\uff01', 'success');
                            checkLogin();
                        }
                    });
                } else {
                    showAuthToast('\u8ba2\u5355\u521b\u5efa\u6210\u529f\uff0c\u8bf7\u524d\u5f80\u652f\u4ed8', 'info');
                }
            }
        });
    }

    // ========== 工具函数 ==========
    function escapeHtml(str){
        if(!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function showAuthToast(message, type){
        // 优先使用 Index3 的 showToast
        if(window.Index3 && window.Index3.showToast){
            window.Index3.showToast(message, type);
            return;
        }
        // 备用简易 toast
        type = type || 'info';
        var container = document.querySelector('.toast-container');
        if(!container){
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        var toast = document.createElement('div');
        toast.className = 'toast ' + type;
        var icon = {success:'✓', error:'✕', info:'ℹ', warning:'⚠'}[type] || 'ℹ';
        toast.innerHTML = '<span style="font-size:16px">' + icon + '</span><span>' + escapeHtml(message) + '</span>';
        container.appendChild(toast);
        setTimeout(function(){
            toast.classList.add('hiding');
            setTimeout(function(){
                if(toast.parentNode) toast.parentNode.removeChild(toast);
            }, 300);
        }, 3000);
    }

    // ========== DOM Ready ==========
    document.addEventListener('DOMContentLoaded', init);

    // ========== 公开接口 ==========
    return {
        getState: function(){ return state; },
        isLoggedIn: function(){ return state.isLoggedIn; },
        getUser: function(){ return state.loginUser; },
        isChecked: function(){ return state.loginChecked; },
        checkLogin: checkLogin,
        logout: handleLogout,
        closeUserDropdown: closeUserDropdown,
        showSubscriptionPopup: showSubscriptionPopup
    };
})();
