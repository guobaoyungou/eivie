/**
 * index.js — 主逻辑脚本
 */
(function(){
    // === State ===
    var state = {
        currentTab: 'photo',  // 'photo' or 'video'
        photoCategoryId: 0,
        videoCategoryId: 0,
        photoPage: 1,
        videoPage: 1,
        photoLoading: false,
        videoLoading: false,
        photoNoMore: false,
        videoNoMore: false,
        pageLimit: 12,
        // 模型广场Tab状态
        activeModelTab: 'recommend',
        providerDataCache: {},
        providerLoading: {},
        cacheTimestamp: {},
        // 登录状态
        isLoggedIn: false,
        loginChecked: false,
        loginUser: null,
        // 场景弹窗状态
        popupTemplateId: 0,
        popupGenerationType: 1,
        popupExpanded: false,
        popupDetail: null,
        popupRefImages: [],
        popupSelectedRatio: '1:1',
        popupSelectedQuantity: 1,
        popupSubmitting: false,
        popupRatioOptions: [],
        popupQuantityOptions: [1,2,3,4,5,6,7,8,9],
        loginPendingCallback: null
    };

    document.addEventListener('DOMContentLoaded', function(){
        syncAuthState();
        initLoginModal();
        initFollowGuide();
        initModelTabs();
        initModelCardClick();
        initModelScrollArrows();
        initTabs();
        initCategoryBars();
        initSearch();
        initMobileTabbar();
        initQrModal();
        initActionSheet();
        initLoadMore();
        initFullscreenSearch();
        initTaskModalOverlay();
        initKeyboardNavigation();
        initSceneCards();
        initScenePopup();
    });
    
    // === 键盘导航支持 ===
    function initKeyboardNavigation(){
        // Tab按钮键盘导航
        document.querySelectorAll('.model-tab-card, .tab-btn').forEach(function(btn, index, btns){
            btn.setAttribute('tabindex', '0');
            btn.addEventListener('keydown', function(e){
                if(e.key === 'Enter' || e.key === ' '){
                    e.preventDefault();
                    this.click();
                } else if(e.key === 'ArrowLeft' || e.key === 'ArrowRight'){
                    e.preventDefault();
                    var nextIndex = e.key === 'ArrowRight' ? index + 1 : index - 1;
                    if(nextIndex >= 0 && nextIndex < btns.length){
                        btns[nextIndex].focus();
                    }
                }
            });
        });
        
        // Esc键关闭弹窗
        document.addEventListener('keydown', function(e){
            if(e.key === 'Escape'){
                var modal = document.getElementById('taskModal');
                if(modal && modal.style.display === 'flex'){
                    closeTaskModal();
                }
            }
        });
    }

    // === 登录状态同步（从Auth模块） ===
    function syncAuthState(){
        // Auth模块已在DOMContentLoaded时自动调用checkLogin
        // 这里延迟同步状态
        var syncInterval = setInterval(function(){
            if(window.Auth && Auth.isChecked()){
                clearInterval(syncInterval);
                state.isLoggedIn = Auth.isLoggedIn();
                state.loginUser = Auth.getUser();
                state.loginChecked = true;
            }
        }, 100);
        // 5秒超时停止
        setTimeout(function(){ clearInterval(syncInterval); }, 5000);
    }

    // === 登录状态检测 ===
    function checkLoginStatus(callback){
        if(window.Auth){
            if(Auth.isChecked()){
                state.isLoggedIn = Auth.isLoggedIn();
                state.loginUser = Auth.getUser();
                state.loginChecked = true;
                if(callback) callback(state.isLoggedIn);
                return;
            }
            Auth.checkLogin(function(loggedIn){
                state.isLoggedIn = loggedIn;
                state.loginUser = Auth.getUser();
                state.loginChecked = true;
                if(callback) callback(loggedIn);
            });
        } else {
            Api.checkLogin(function(err, res){
                if(!err && res && res.status === 1){
                    state.isLoggedIn = true;
                    state.loginUser = res.data || null;
                } else {
                    state.isLoggedIn = false;
                    state.loginUser = null;
                }
                state.loginChecked = true;
                if(callback) callback(state.isLoggedIn);
            });
        }
    }

    /**
     * 要求登录后才执行回调。
     * 如果已登录，直接执行 callback；
     * 如果未登录，弹出毛玻璃登录界面。
     */
    function requireLogin(callback){
        // 如果已经检测过且已登录，直接执行
        if(state.loginChecked && state.isLoggedIn){
            callback();
            return;
        }
        // 重新检测一次（防止缓存过期）
        checkLoginStatus(function(loggedIn){
            if(loggedIn){
                callback();
            } else {
                // 保存待执行回调，弹出登录模态框
                state.loginPendingCallback = callback;
                showLoginModal();
            }
        });
    }

    // === 登录弹窗状态 ===
    var loginState = {
        smsCooldown: 0,
        smsTimer: null,
        submitting: false,
        // 扫码登录相关状态
        requireFollow: false,
        followGuideText: '',
        followAppname: '',
        newUserFollowGuide: false,
        mpConfigured: false,
        configLoaded: false,
        // 动态二维码状态
        qrSceneStr: '',
        qrExpireSeconds: 300,
        qrCreateTime: 0,
        qrPollingTimer: null,
        qrExpireTimer: null,
        qrStatus: 'idle' // idle, loading, showing, expired, success
    };

    function showLoginModal(){
        var overlay = document.getElementById('loginModalOverlay');
        if(!overlay) return;
        overlay.classList.add('show');
        // 清空上次的状态
        document.getElementById('loginPhone').value = '';
        document.getElementById('loginCode').value = '';
        document.getElementById('loginError').textContent = '';
        var submitBtn = document.getElementById('loginSubmitBtn');
        submitBtn.disabled = false;
        submitBtn.textContent = '商家登录/注册';
        submitBtn.classList.remove('loading');
        // 重置扫码登录状态
        stopQrPolling();
        loginState.qrStatus = 'idle';
        loginState.qrSceneStr = '';
        var statusEl = document.getElementById('qrLoginStatus');
        if(statusEl) statusEl.innerHTML = '';
        // 恢复验证码按钮的倒计时状态
        var sendBtn = document.getElementById('loginSendBtn');
        if(sendBtn && loginState.smsCooldown > 0){
            sendBtn.disabled = true;
            sendBtn.classList.add('counting');
            sendBtn.textContent = loginState.smsCooldown + 's 后重新获取';
        } else if(sendBtn){
            sendBtn.disabled = false;
            sendBtn.classList.remove('counting');
            sendBtn.textContent = '获取验证码';
        }
        // 加载登录配置并渲染双栏
        loadLoginConfig(function(){
            applyQrLoginPanel();
        });
        // 聚焦手机号输入框
        setTimeout(function(){ document.getElementById('loginPhone').focus(); }, 300);
        // 禁止背景滚动
        document.body.style.overflow = 'hidden';
    }

    function hideLoginModal(){
        var overlay = document.getElementById('loginModalOverlay');
        if(!overlay) return;
        overlay.classList.remove('show');
        document.body.style.overflow = '';
        stopQrPolling();
    }

    // === 加载登录配置 ===
    function loadLoginConfig(callback){
        if(loginState.configLoaded){
            if(callback) callback();
            return;
        }
        Api.getPcLoginConfig(function(err, res){
            if(!err && res && res.status === 1 && res.data){
                var d = res.data;
                loginState.requireFollow = d.require_follow == 1;
                loginState.followGuideText = d.follow_guide_text || '微信扫码关注公众号即可登录';
                loginState.followAppname = d.follow_appname || '';
                loginState.newUserFollowGuide = d.new_user_follow_guide == 1;
                loginState.mpConfigured = d.mp_configured == 1;
                loginState.configLoaded = true;
            }
            if(callback) callback();
        });
    }

    // === 应用扫码登录面板 ===
    function applyQrLoginPanel(){
        var modal = document.getElementById('loginModal');
        var rightPanel = document.getElementById('loginModalRight');
        if(!modal || !rightPanel) return;

        if(loginState.requireFollow && loginState.mpConfigured){
            modal.classList.add('has-follow-panel');
            rightPanel.style.display = '';
            // 填充内容
            var appname = document.getElementById('qrLoginAppname');
            if(appname) appname.textContent = loginState.followAppname;
            var guideText = document.getElementById('qrLoginGuide');
            if(guideText) guideText.textContent = loginState.followGuideText;
            // 自动创建二维码
            createQrLogin();
        } else {
            modal.classList.remove('has-follow-panel');
            rightPanel.style.display = 'none';
        }
    }

    // === 创建扫码登录二维码 ===
    function createQrLogin(){
        stopQrPolling();
        loginState.qrStatus = 'loading';
        // 显示loading
        var loadingEl = document.getElementById('qrLoading');
        var imgEl = document.getElementById('qrLoginImg');
        var expiredEl = document.getElementById('qrExpiredOverlay');
        if(loadingEl) loadingEl.style.display = 'flex';
        if(imgEl) imgEl.style.display = 'none';
        if(expiredEl) expiredEl.style.display = 'none';
        var statusEl = document.getElementById('qrLoginStatus');
        if(statusEl) statusEl.innerHTML = '';

        Api.createQrLoginTicket(function(err, res){
            if(err || !res || res.status !== 1){
                loginState.qrStatus = 'idle';
                if(loadingEl) loadingEl.style.display = 'none';
                var msg = (res && res.msg) ? res.msg : '生成二维码失败';
                if(statusEl) statusEl.innerHTML = '<span class="qr-status-error">' + escapeHtml(msg) + '</span>';
                return;
            }
            var d = res.data;
            loginState.qrSceneStr = d.scene_str;
            loginState.qrExpireSeconds = d.expire_seconds || 300;
            loginState.qrCreateTime = Date.now();
            loginState.qrStatus = 'showing';

            // 显示二维码图片
            if(loadingEl) loadingEl.style.display = 'none';
            if(imgEl){
                imgEl.src = d.qr_url;
                imgEl.style.display = 'block';
            }

            // 启动轮询
            startQrPolling();
            // 启动过期计时器
            startQrExpireTimer();
        });
    }

    // === 扫码登录状态轮询 ===
    function startQrPolling(){
        stopQrPolling();
        loginState.qrPollingTimer = setInterval(function(){
            if(loginState.qrStatus !== 'showing') return;
            Api.checkQrLoginStatus({scene_str: loginState.qrSceneStr}, function(err, res){
                if(err) return;
                if(!res) return;
                // 二维码过期
                if(res.status === 0 && res.data && res.data.expired){
                    showQrExpired();
                    return;
                }
                if(res.status === 1 && res.data){
                    if(res.data.login_status === 'success'){
                        onQrLoginSuccess(res.data);
                    }
                    // pending 继续轮询
                }
            });
        }, 3000);
    }

    function stopQrPolling(){
        if(loginState.qrPollingTimer){
            clearInterval(loginState.qrPollingTimer);
            loginState.qrPollingTimer = null;
        }
        if(loginState.qrExpireTimer){
            clearTimeout(loginState.qrExpireTimer);
            loginState.qrExpireTimer = null;
        }
    }

    // === 二维码过期计时 ===
    function startQrExpireTimer(){
        if(loginState.qrExpireTimer){
            clearTimeout(loginState.qrExpireTimer);
        }
        loginState.qrExpireTimer = setTimeout(function(){
            if(loginState.qrStatus === 'showing'){
                showQrExpired();
            }
        }, loginState.qrExpireSeconds * 1000);
    }

    function showQrExpired(){
        loginState.qrStatus = 'expired';
        stopQrPolling();
        var expiredEl = document.getElementById('qrExpiredOverlay');
        if(expiredEl) expiredEl.style.display = 'flex';
        var statusEl = document.getElementById('qrLoginStatus');
        if(statusEl) statusEl.innerHTML = '';
    }

    // === 扫码登录成功 ===
    function onQrLoginSuccess(data){
        loginState.qrStatus = 'success';
        stopQrPolling();
        // 更新登录状态
        state.isLoggedIn = true;
        state.loginChecked = true;
        state.loginUser = data;
        var statusEl = document.getElementById('qrLoginStatus');
        if(statusEl) statusEl.innerHTML = '<span class="qr-status-ok">✓ 登录成功</span>';
        showToast('扫码登录成功', 'success');
        // 延迟关闭弹窗
        setTimeout(function(){
            hideLoginModal();
            if(state.loginPendingCallback){
                var cb = state.loginPendingCallback;
                state.loginPendingCallback = null;
                setTimeout(function(){ cb(); }, 300);
            }
            if(window.Auth) Auth.checkLogin();
        }, 800);
    }

    // === 新用户关注引导浮层 ===
    function showNewUserFollowGuide(){
        if(!loginState.newUserFollowGuide || !loginState.mpConfigured) return;
        var overlay = document.getElementById('followGuideOverlay');
        if(!overlay) return;
        var appname = document.getElementById('followGuideAppname');
        if(appname) appname.textContent = loginState.followAppname;
        var desc = document.getElementById('followGuideDesc');
        if(desc) desc.textContent = loginState.followGuideText;
        // 动态生成二维码
        var loadingEl = document.getElementById('followGuideQrLoading');
        var imgEl = document.getElementById('followGuideQrImage');
        if(loadingEl) loadingEl.style.display = 'flex';
        if(imgEl) imgEl.style.display = 'none';
        overlay.classList.add('show');
        Api.createQrLoginTicket(function(err, res){
            if(loadingEl) loadingEl.style.display = 'none';
            if(!err && res && res.status === 1 && res.data){
                if(imgEl){
                    imgEl.src = res.data.qr_url;
                    imgEl.style.display = 'block';
                }
            }
        });
    }

    function hideNewUserFollowGuide(){
        var overlay = document.getElementById('followGuideOverlay');
        if(overlay) overlay.classList.remove('show');
    }

    function initFollowGuide(){
        var overlay = document.getElementById('followGuideOverlay');
        var closeBtn = document.getElementById('followGuideClose');
        if(!overlay) return;
        if(closeBtn){
            closeBtn.addEventListener('click', function(){ hideNewUserFollowGuide(); });
        }
        overlay.addEventListener('click', function(e){
            if(e.target === overlay) hideNewUserFollowGuide();
        });
    }

    function initLoginModal(){
        var overlay = document.getElementById('loginModalOverlay');
        var closeBtn = document.getElementById('loginModalClose');
        var phoneInput = document.getElementById('loginPhone');
        var codeInput = document.getElementById('loginCode');
        var sendBtn = document.getElementById('loginSendBtn');
        var submitBtn = document.getElementById('loginSubmitBtn');
        var errorEl = document.getElementById('loginError');
        var qrRefreshBtn = document.getElementById('qrRefreshBtn');
        if(!overlay) return;

        // 关闭按钮
        closeBtn.addEventListener('click', function(){
            hideLoginModal();
            state.loginPendingCallback = null;
        });
        // 点击遮罩关闭
        overlay.addEventListener('click', function(e){
            if(e.target === overlay){
                hideLoginModal();
                state.loginPendingCallback = null;
            }
        });
        // Esc关闭
        document.addEventListener('keydown', function(e){
            if(e.key === 'Escape' && overlay.classList.contains('show')){
                hideLoginModal();
                state.loginPendingCallback = null;
            }
        });

        // 二维码刷新按钮
        if(qrRefreshBtn){
            qrRefreshBtn.addEventListener('click', function(){
                createQrLogin();
            });
        }

        // 手机号输入只允许数字
        phoneInput.addEventListener('input', function(){
            this.value = this.value.replace(/\D/g, '').substring(0, 11);
            errorEl.textContent = '';
        });
        codeInput.addEventListener('input', function(){
            this.value = this.value.replace(/\D/g, '').substring(0, 6);
            errorEl.textContent = '';
        });

        // 发送验证码
        sendBtn.addEventListener('click', function(){
            if(sendBtn.disabled || loginState.smsCooldown > 0) return;
            var tel = phoneInput.value.trim();
            if(!tel || tel.length !== 11){
                errorEl.textContent = '请输入正确的手机号';
                phoneInput.focus();
                return;
            }
            errorEl.textContent = '';
            sendBtn.disabled = true;
            sendBtn.textContent = '发送中...';
            sendBtn.classList.add('sending');

            Api.sendSms({tel: tel}, function(err, res){
                sendBtn.classList.remove('sending');
                if(err || !res || res.status !== 1){
                    var msg = (res && res.msg) ? res.msg : '发送失败';
                    errorEl.textContent = msg;
                    sendBtn.disabled = false;
                    sendBtn.textContent = '获取验证码';
                    return;
                }
                showToast('验证码已发送', 'success');
                // 启动倒计时
                startSmsCooldown(sendBtn);
                // 聚焦验证码输入框
                codeInput.focus();
            });
        });

        /**
         * 启动短信倒计时（60秒），按钮显示 "Ns 后重新获取" + 进度条动画
         */
        function startSmsCooldown(btn){
            // 清理旧计时器
            if(loginState.smsTimer){ clearInterval(loginState.smsTimer); }
            loginState.smsCooldown = 60;
            btn.disabled = true;
            btn.classList.add('counting');
            btn.textContent = '60s 后重新获取';
            // CSS 动画：通过自定义属性驱动进度条
            btn.style.setProperty('--cd-duration', '60s');
            // 强制重绘以重新触发动画
            btn.classList.remove('cd-animate');
            void btn.offsetWidth;
            btn.classList.add('cd-animate');

            loginState.smsTimer = setInterval(function(){
                loginState.smsCooldown--;
                if(loginState.smsCooldown <= 0){
                    clearInterval(loginState.smsTimer);
                    loginState.smsTimer = null;
                    loginState.smsCooldown = 0;
                    btn.disabled = false;
                    btn.classList.remove('counting', 'cd-animate');
                    btn.textContent = '重新获取';
                } else {
                    btn.textContent = loginState.smsCooldown + 's 后重新获取';
                }
            }, 1000);
        }

        // 提交登录
        submitBtn.addEventListener('click', function(){
            doPhoneLogin();
        });
        // 回车提交
        codeInput.addEventListener('keydown', function(e){
            if(e.key === 'Enter') doPhoneLogin();
        });

        function doPhoneLogin(){
            if(loginState.submitting) return;
            var tel = phoneInput.value.trim();
            var smscode = codeInput.value.trim();
            if(!tel || tel.length !== 11){
                errorEl.textContent = '请输入正确的手机号';
                phoneInput.focus();
                return;
            }
            if(!smscode || smscode.length < 4){
                errorEl.textContent = '请输入验证码';
                codeInput.focus();
                return;
            }
            errorEl.textContent = '';
            loginState.submitting = true;
            submitBtn.disabled = true;
            submitBtn.textContent = '登录中...';
            submitBtn.classList.add('loading');

            Api.phoneLogin({tel: tel, smscode: smscode}, function(err, res){
                loginState.submitting = false;
                if(err || !res || res.status !== 1){
                    var msg = (res && res.msg) ? res.msg : '登录失败';
                    errorEl.textContent = msg;
                    submitBtn.disabled = false;
                    submitBtn.textContent = '商家登录/注册';
                    submitBtn.classList.remove('loading');
                    return;
                }
                // 登录成功
                state.isLoggedIn = true;
                state.loginChecked = true;
                state.loginUser = res.data || null;
                var isNewUser = res.data && res.data.is_new_user;
                showToast('登录成功', 'success');

                // 关闭弹窗
                hideLoginModal();
                // 新用户引导（非强制）
                if(isNewUser && loginState.newUserFollowGuide && loginState.mpConfigured){
                    // 确保配置已加载
                    loadLoginConfig(function(){
                        if(loginState.newUserFollowGuide && loginState.mpConfigured){
                            setTimeout(function(){ showNewUserFollowGuide(); }, 500);
                        }
                    });
                }
                // 执行待定回调
                if(state.loginPendingCallback){
                    var cb = state.loginPendingCallback;
                    state.loginPendingCallback = null;
                    setTimeout(function(){ cb(); }, 300);
                }
                // 刷新头部和侧边栏用户信息
                if(window.Auth) Auth.checkLogin();
            });
        }
    }

    // === Toast 提示函数 ===
    function showToast(message, type){
        type = type || 'info'; // success, error, info, warning
        var container = document.querySelector('.toast-container');
        if(!container){
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        var toast = document.createElement('div');
        toast.className = 'toast ' + type;
        var icon = {success: '✓', error: '✕', info: 'ℹ', warning: '⚠'}[type] || 'ℹ';
        toast.innerHTML = '<span style="font-size:16px">' + icon + '</span><span>' + escapeHtml(message) + '</span>';
        container.appendChild(toast);

        setTimeout(function(){
            toast.classList.add('hiding');
            setTimeout(function(){
                if(toast.parentNode) toast.parentNode.removeChild(toast);
            }, 300);
        }, 3000);
    }

    // === 模型广场Tab切换 ===
    function initModelTabs(){
        document.querySelectorAll('.model-tab-card').forEach(function(btn){
            btn.addEventListener('click', function(){
                var providerId = this.getAttribute('data-provider');
                if(providerId === state.activeModelTab) return;

                // 切换Tab激活态
                document.querySelectorAll('.model-tab-card').forEach(function(b){ 
                    b.classList.remove('active'); 
                    b.setAttribute('aria-selected', 'false');
                });
                this.classList.add('active');
                this.setAttribute('aria-selected', 'true');

                // 切换面板
                document.querySelectorAll('.model-tab-panel').forEach(function(p){ p.classList.remove('active'); });
                var panel = document.querySelector('.model-tab-panel[data-provider="' + providerId + '"]');
                if(panel) panel.classList.add('active');

                state.activeModelTab = providerId;

                // 供应商Tab懒加载
                if(providerId !== 'recommend' && !state.providerDataCache[providerId]){
                    loadProviderModels(providerId);
                }
            });
        });
    }

    // === 加载供应商模型 ===
    function loadProviderModels(providerId){
        if(state.providerLoading[providerId]) return;
        
        // 检查缓存是否过期（5分钟）
        var now = Date.now();
        if(state.providerDataCache[providerId] && state.cacheTimestamp[providerId]){
            if(now - state.cacheTimestamp[providerId] < 300000){
                // 缓存未过期，直接使用
                renderProviderPanel(providerId, state.providerDataCache[providerId]);
                return;
            }
        }
        
        state.providerLoading[providerId] = true;

        var panel = document.querySelector('.model-tab-panel[data-provider="' + providerId + '"]');
        if(!panel) return;
        var scroll = panel.querySelector('.model-scroll');
        if(!scroll) return;

        // 显示骨架屏
        scroll.innerHTML = renderSkeletonCards(3);

        Api.getModelsByProvider({provider_id: providerId}, function(err, res){
            state.providerLoading[providerId] = false;

            if(err || !res || res.code !== 0){
                scroll.innerHTML = '<div class="empty-state"><div class="empty-icon">😔</div><p>加载失败，请重试</p></div>';
                showToast('加载模型失败', 'error');
                return;
            }

            var list = res.data || [];
            state.providerDataCache[providerId] = list;
            state.cacheTimestamp[providerId] = Date.now();
            
            renderProviderPanel(providerId, list);
        });
    }
    
    // === 渲染供应商面板 ===
    function renderProviderPanel(providerId, list){
        var panel = document.querySelector('.model-tab-panel[data-provider="' + providerId + '"]');
        if(!panel) return;
        var scroll = panel.querySelector('.model-scroll');
        if(!scroll) return;
        
        if(list.length === 0){
            scroll.innerHTML = '<div class="empty-state"><div class="empty-icon">🤖</div><p>该供应商暂无可用模型</p></div>';
            return;
        }

        renderModelCards(scroll, list);
    }
    
    // === 渲染骨架屏 ===
    function renderSkeletonCards(count){
        var html = '<div class="model-skeleton-grid">';
        for(var i = 0; i < count; i++){
            html += '<div class="model-skeleton-card">' +
                '<div class="skeleton-header">' +
                    '<div class="skeleton-avatar"></div>' +
                    '<div class="skeleton-text">' +
                        '<div class="skeleton-line"></div>' +
                        '<div class="skeleton-line short"></div>' +
                    '</div>' +
                '</div>' +
                '<div class="skeleton-line"></div>' +
                '<div class="skeleton-line"></div>' +
            '</div>';
        }
        html += '</div>';
        return html;
    }

    // === 渲染模型卡片 ===
    function renderModelCards(container, list){
        container.innerHTML = '';
        list.forEach(function(item){
            var tags = item.capability_tags || [];
            if(typeof tags === 'string'){ try{ tags = JSON.parse(tags); }catch(e){ tags = []; } }

            var card = document.createElement('div');
            card.className = 'model-card';
            card.setAttribute('data-id', item.id);
            card.setAttribute('data-model-code', item.model_code || '');
            card.setAttribute('role', 'listitem');
            card.setAttribute('tabindex', '0');
            card.setAttribute('aria-label', item.model_name + ' - ' + (item.provider_name || ''));
            
            // 能力标签渲染
            var capabilitiesHtml = '';
            if(tags.length > 0){
                capabilitiesHtml = '<div class="mc-capabilities">';
                tags.slice(0, 3).forEach(function(tag){
                    capabilitiesHtml += '<span class="mc-capability-tag">' + escapeHtml(tag) + '</span>';
                });
                capabilitiesHtml += '</div>';
            } else {
                capabilitiesHtml = '<div class="mc-capabilities"></div>';
            }
            
            card.innerHTML =
                '<div class="mc-header">' +
                    (item.provider_logo ? '<img class="mc-logo" src="' + escapeHtml(item.provider_logo) + '" alt="" loading="lazy">' : '') +
                    '<div>' +
                        '<div class="mc-name">' + escapeHtml(item.model_name) + '</div>' +
                        '<div class="mc-provider">' + escapeHtml(item.provider_name || '') + '</div>' +
                    '</div>' +
                    (item.is_recommend == 1 ? '<span class="mc-recommend-badge">🔥</span>' : '') +
                '</div>' +
                '<div class="mc-desc">' + escapeHtml(item.description || '') + '</div>' +
                capabilitiesHtml +
                '<div class="mc-footer">' +
                    (item.type_name ? '<span class="mc-tag">' + escapeHtml(item.type_name) + '</span>' : '<span></span>') +
                    '<span class="mc-action">开始创作 →</span>' +
                '</div>';
            container.appendChild(card);

            // 绑定点击事件
            card.addEventListener('click', function(){
                var modelId = this.getAttribute('data-id');
                openTaskModal(modelId);
            });
            // 键盘支持
            card.addEventListener('keydown', function(e){
                if(e.key === 'Enter' || e.key === ' '){
                    e.preventDefault();
                    var modelId = this.getAttribute('data-id');
                    openTaskModal(modelId);
                }
            });
        });
    }

    // === 模型卡片点击事件（服务端渲染的卡片） ===
    function initModelCardClick(){
        document.querySelectorAll('.model-card[data-id]').forEach(function(card){
            card.addEventListener('click', function(){
                var modelId = this.getAttribute('data-id');
                if(modelId) openTaskModal(modelId);
            });
            // 键盘支持
            card.addEventListener('keydown', function(e){
                if(e.key === 'Enter' || e.key === ' '){
                    e.preventDefault();
                    var modelId = this.getAttribute('data-id');
                    if(modelId) openTaskModal(modelId);
                }
            });
        });
    }

    // === 打开生成任务弹窗（16:9 四排布局） ===
    function openTaskModal(modelId){
        var modal = document.getElementById('taskModal');
        var row1 = document.getElementById('tmModelType');
        var row2 = document.getElementById('tmCapabilities');
        var row3 = document.getElementById('tmParams');
        var row4 = document.getElementById('tmScenes');
        var loginTip = document.getElementById('taskLoginTip');
        var submitBtn = document.getElementById('taskSubmitBtn');

        if(!modal) return;

        // 显示加载状态
        row1.innerHTML = '<div style="text-align:center;padding:16px;color:var(--text-tertiary)"><div style="display:inline-block;width:24px;height:24px;border:3px solid var(--border-color);border-top-color:var(--accent-color);border-radius:50%;animation:spin 1s linear infinite"></div></div>';
        row2.innerHTML = '';
        row3.innerHTML = '';
        row4.innerHTML = '';
        loginTip.style.display = 'none';
        submitBtn.style.display = 'inline-block';
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        Api.getModelDetail({id: modelId}, function(err, res){
            if(err || !res || res.code !== 0){
                row1.innerHTML = '<div class="empty-state" style="padding:24px"><div class="empty-icon">😔</div><p>加载失败</p></div>';
                showToast('加载模型详情失败', 'error');
                return;
            }

            var data = res.data;
            var tags = data.capability_tags || [];
            var scenes = data.scene_templates || [];

            // === Row 1: 模型类型信息 ===
            var typeIconMap = {
                'image_generation': '🖼️',
                'video_generation': '🎬',
                'text_generation': '✍️',
                'deep_thinking': '🧠',
                'speech_model': '🎙️',
                'embedding': '🧩'
            };
            var typeEmoji = typeIconMap[data.type_code] || '🤖';

            var ioHtml = '';
            var inputTypes = data.type_input_types || [];
            var outputTypes = data.type_output_types || [];
            if(inputTypes.length || outputTypes.length){
                ioHtml = '<div class="tmt-io">';
                inputTypes.forEach(function(t){ ioHtml += '<span class="tmt-io-tag">⬆ ' + escapeHtml(t) + '</span>'; });
                outputTypes.forEach(function(t){ ioHtml += '<span class="tmt-io-tag">⬇ ' + escapeHtml(t) + '</span>'; });
                ioHtml += '</div>';
            }

            row1.innerHTML =
                (data.provider_logo ? '<img class="tmt-logo" src="' + escapeHtml(data.provider_logo) + '" alt="" loading="lazy">' : '') +
                '<div class="tmt-info">' +
                    '<div class="tmt-name">' + escapeHtml(data.model_name) + '</div>' +
                    '<div class="tmt-meta">' +
                        '<span>' + escapeHtml(data.provider_name || '') + '</span>' +
                        (data.description ? '<span>·</span><span>' + escapeHtml(data.description.length > 40 ? data.description.substring(0, 40) + '...' : data.description) + '</span>' : '') +
                    '</div>' +
                '</div>' +
                '<span class="tmt-type-badge"><span class="tmt-type-icon">' + typeEmoji + '</span>' + escapeHtml(data.type_name || '') + '</span>' +
                ioHtml;

            // === Row 2: 能力Tab ===
            if(tags.length > 0){
                var capsHtml = '';
                tags.forEach(function(tag, i){
                    capsHtml += '<span class="tmc-tag' + (i === 0 ? ' active' : '') + '">' + escapeHtml(tag) + '</span>';
                });
                row2.innerHTML = capsHtml;
                // 能力Tab点击交互
                row2.querySelectorAll('.tmc-tag').forEach(function(tagEl){
                    tagEl.addEventListener('click', function(){
                        row2.querySelectorAll('.tmc-tag').forEach(function(t){ t.classList.remove('active'); });
                        this.classList.add('active');
                    });
                });
            } else {
                row2.innerHTML = '<span class="tmc-tag active">通用能力</span>';
            }

            // === Row 3: 参数配置 ===
            renderTaskForm(row3, data.input_schema);

            // === Row 4: 推荐场景模板 ===
            renderSceneTemplates(row4, scenes);
        });
    }

    // === 关闭生成任务弹窗 ===
    function closeTaskModal(){
        var modal = document.getElementById('taskModal');
        if(modal){
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    // === 根据input_schema渲染参数表单（Row 3） ===
    function renderTaskForm(container, inputSchema){
        container.innerHTML = '';
        if(!inputSchema || !inputSchema.parameters || !inputSchema.parameters.length){
            container.innerHTML = '<div style="padding:8px 0;color:var(--text-tertiary);font-size:12px">该模型无需配置额外参数</div>';
            return;
        }

        inputSchema.parameters.forEach(function(param){
            var group = document.createElement('div');
            group.className = 'tf-group';

            var label = '<label class="tf-label">';
            if(param.required) label += '<span class="tf-required">*</span>';
            label += escapeHtml(param.label || param.name) + '</label>';

            var input = '';
            var name = 'param_' + (param.name || '');

            if(param.type === 'textarea' || param.name === 'prompt'){
                input = '<textarea class="tf-textarea" name="' + name + '" placeholder="' + escapeHtml(param.placeholder || param.description || '') + '"></textarea>';
            } else if((param.type === 'select' || param.type === 'enum') && param.options && param.options.length){
                input = '<select class="tf-select" name="' + name + '">';
                param.options.forEach(function(opt){
                    var val = typeof opt === 'object' ? opt.value : opt;
                    var text = typeof opt === 'object' ? (opt.label || opt.value) : opt;
                    var selected = (param.default !== undefined && String(val) === String(param.default)) ? ' selected' : '';
                    input += '<option value="' + escapeHtml(String(val)) + '"' + selected + '>' + escapeHtml(String(text)) + '</option>';
                });
                input += '</select>';
            } else if(param.type === 'image' || param.type === 'mixed'){
                input = '<div class="tf-image-upload">📷 点击上传图片</div>';
            } else if(param.type === 'boolean'){
                var checked = param.default ? ' checked' : '';
                input = '<label style="display:flex;align-items:center;gap:8px;cursor:pointer"><input type="checkbox" name="' + name + '"' + checked + ' style="width:16px;height:16px;accent-color:var(--accent-color)"><span style="font-size:13px;color:var(--text-secondary)">' + escapeHtml(param.description || '') + '</span></label>';
            } else {
                input = '<input class="tf-input" type="' + (param.type || 'text') + '" name="' + name + '" placeholder="' + escapeHtml(param.placeholder || param.description || '') + '"' + (param.default !== undefined ? ' value="' + escapeHtml(String(param.default)) + '"' : '') + '>';
            }

            group.innerHTML = label + input;
            container.appendChild(group);
        });
    }

    // === 渲染推荐场景模板（Row 4） ===
    function renderSceneTemplates(container, scenes){
        container.innerHTML = '';
        if(!scenes || scenes.length === 0){
            container.innerHTML = '<div class="tm-scenes-empty">💭 暂无推荐场景模板</div>';
            return;
        }

        var title = '<div class="tms-title"><span class="tms-icon">✨</span>推荐场景模板</div>';
        var scroll = document.createElement('div');
        scroll.className = 'tms-scroll';

        scenes.forEach(function(scene){
            var card = document.createElement('div');
            card.className = 'tms-card';
            card.setAttribute('data-scene-id', scene.id);
            card.innerHTML =
                '<img class="tms-cover" src="' + escapeHtml(scene.cover_image || '/static/index3/img/placeholder.png') + '" alt="' + escapeHtml(scene.template_name) + '" loading="lazy">' +
                '<div class="tms-info">' +
                    '<div class="tms-name">' + escapeHtml(scene.template_name) + '</div>' +
                    '<div class="tms-meta">' +
                        '<span class="tms-price">' + (parseFloat(scene.base_price) > 0 ? scene.base_price + '积分' : '免费') + '</span>' +
                        '<span class="tms-uses">' + (scene.use_count || 0) + '次</span>' +
                    '</div>' +
                '</div>';
            scroll.appendChild(card);
        });

        container.innerHTML = title;
        container.appendChild(scroll);
    }

    // === 弹窗遮罩层点击关闭 ===
    function initTaskModalOverlay(){
        var modal = document.getElementById('taskModal');
        if(modal){
            modal.addEventListener('click', function(e){
                if(e.target === modal) closeTaskModal();
            });
        }
    }

    // === TAB切换 ===
    function initTabs(){
        document.querySelectorAll('.tab-btn').forEach(function(btn){
            btn.addEventListener('click', function(){
                var tab = this.getAttribute('data-tab');
                state.currentTab = tab;
                document.querySelectorAll('.tab-btn').forEach(function(b){ b.classList.remove('active'); });
                this.classList.add('active');
                document.querySelectorAll('.tab-panel').forEach(function(p){ p.classList.remove('active'); });
                var panel = document.getElementById('panel-' + tab);
                if(panel) panel.classList.add('active');
            });
        });
    }

    // === 分类标签点击 ===
    function initCategoryBars(){
        document.querySelectorAll('.category-bar').forEach(function(bar){
            bar.addEventListener('click', function(e){
                var tag = e.target.closest('.category-tag');
                if(!tag) return;
                var type = bar.getAttribute('data-type');
                var catId = parseInt(tag.getAttribute('data-id')) || 0;

                bar.querySelectorAll('.category-tag').forEach(function(t){ t.classList.remove('active'); });
                tag.classList.add('active');

                if(type === 'photo'){
                    state.photoCategoryId = catId;
                    state.photoPage = 1;
                    state.photoNoMore = false;
                    loadScenes('photo', true);
                } else {
                    state.videoCategoryId = catId;
                    state.videoPage = 1;
                    state.videoNoMore = false;
                    loadScenes('video', true);
                }
            });
        });
    }

    // === 加载场景模板 ===
    function loadScenes(type, replace){
        var isPhoto = (type === 'photo');
        if(isPhoto && state.photoLoading) return;
        if(!isPhoto && state.videoLoading) return;

        if(isPhoto) state.photoLoading = true;
        else state.videoLoading = true;

        var params = {
            generation_type: isPhoto ? 1 : 2,
            category_id: isPhoto ? state.photoCategoryId : state.videoCategoryId,
            page: isPhoto ? state.photoPage : state.videoPage,
            limit: state.pageLimit
        };

        var loadBtn = document.querySelector('#panel-' + type + ' .load-more-btn');
        if(loadBtn) loadBtn.classList.add('loading');
        if(loadBtn) loadBtn.textContent = '加载中...';

        Api.getSceneList(params, function(err, res){
            if(isPhoto) state.photoLoading = false;
            else state.videoLoading = false;

            if(loadBtn) loadBtn.classList.remove('loading');

            if(err || !res || res.code !== 0){
                if(loadBtn) loadBtn.textContent = '加载更多';
                return;
            }

            var grid = document.querySelector('#panel-' + type + ' .scene-grid');
            if(!grid) return;

            if(replace) grid.innerHTML = '';

            var list = res.data || [];
            if(list.length < state.pageLimit){
                if(isPhoto) state.photoNoMore = true;
                else state.videoNoMore = true;
                if(loadBtn) loadBtn.textContent = '没有更多了';
            } else {
                if(loadBtn) loadBtn.textContent = '加载更多';
            }

            if(list.length === 0 && replace){
                grid.innerHTML = '<div class="empty-state"><div class="empty-icon">📭</div><p>暂无场景模板</p></div>';
                return;
            }

            list.forEach(function(item){
                var card = document.createElement('div');
                card.className = 'scene-card';
                card.setAttribute('data-id', item.id);
                card.setAttribute('data-type', isPhoto ? 'photo' : 'video');
                card.innerHTML =
                    buildSceneCoverHtml(item) +
                    '<div class="sc-info">' +
                        '<div class="sc-name">' + escapeHtml(item.template_name) + '</div>' +
                        '<div class="sc-desc">' + escapeHtml(item.description || '') + '</div>' +
                        '<div class="sc-meta">' +
                            '<span class="sc-price">' + (parseFloat(item.base_price) > 0 ? item.base_price + '积分' : '免费') + '</span>' +
                            '<span class="sc-uses">' + (item.use_count || 0) + '次使用</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="sc-hover-btn">做同款</div>';
                grid.appendChild(card);
                bindSceneCard(card);
            });

            if(isPhoto) state.photoPage++;
            else state.videoPage++;
        });
    }

    // === 模型广场滚动箭头 ===
    function initModelScrollArrows(){
        document.querySelectorAll('.model-scroll-wrap').forEach(function(wrap){
            var scroll = wrap.querySelector('.model-scroll');
            var leftArrow = wrap.querySelector('.scroll-arrow.left');
            var rightArrow = wrap.querySelector('.scroll-arrow.right');
            
            if(!scroll || !leftArrow || !rightArrow) return;
            
            // 更新箭头状态
            function updateArrows(){
                var isAtStart = scroll.scrollLeft <= 0;
                var isAtEnd = scroll.scrollLeft + scroll.clientWidth >= scroll.scrollWidth - 1;
                leftArrow.disabled = isAtStart;
                rightArrow.disabled = isAtEnd;
            }
            
            scroll.addEventListener('scroll', updateArrows);
            updateArrows();
            
            leftArrow.addEventListener('click', function(){
                scroll.scrollBy({ left: -300, behavior: 'smooth' });
                setTimeout(updateArrows, 350);
            });
            
            rightArrow.addEventListener('click', function(){
                scroll.scrollBy({ left: 300, behavior: 'smooth' });
                setTimeout(updateArrows, 350);
            });
        });
    }

    // === 搜索 ===
    function initSearch(){
        var searchInput = document.querySelector('.header-search input');
        var timer = null;
        if(searchInput){
            searchInput.addEventListener('keydown', function(e){
                if(e.key === 'Enter'){
                    clearTimeout(timer);
                    doSearch(this.value.trim());
                }
            });
            searchInput.addEventListener('input', function(){
                var val = this.value.trim();
                clearTimeout(timer);
                if(val.length >= 2){
                    timer = setTimeout(function(){ doSearch(val); }, 500);
                }
            });
        }
    }

    function doSearch(keyword){
        if(!keyword) return;
        Api.search({keyword: keyword, type: state.currentTab === 'photo' ? 1 : 2}, function(err, res){
            if(err || !res || res.code !== 0) return;
            var grid = document.querySelector('#panel-' + state.currentTab + ' .scene-grid');
            if(!grid) return;
            grid.innerHTML = '';
            var list = res.data || [];
            if(list.length === 0){
                grid.innerHTML = '<div class="empty-state"><div class="empty-icon">🔍</div><p>未找到相关内容</p></div>';
                return;
            }
            list.forEach(function(item){
                var card = document.createElement('div');
                card.className = 'scene-card';
                card.setAttribute('data-id', item.id || '');
                card.setAttribute('data-type', state.currentTab);
                card.innerHTML =
                    buildSceneCoverHtml(item) +
                    '<div class="sc-info">' +
                        '<div class="sc-name">' + escapeHtml(item.template_name || item.model_name || '') + '</div>' +
                        '<div class="sc-desc">' + escapeHtml(item.description || '') + '</div>' +
                    '</div>' +
                    '<div class="sc-hover-btn">做同款</div>';
                grid.appendChild(card);
                bindSceneCard(card);
            });
        });
    }

    // === 加载更多 ===
    function initLoadMore(){
        document.querySelectorAll('.load-more-btn').forEach(function(btn){
            btn.addEventListener('click', function(){
                var type = this.getAttribute('data-type');
                var noMore = (type === 'photo') ? state.photoNoMore : state.videoNoMore;
                if(noMore) return;
                loadScenes(type, false);
            });
        });

        // 移动端上拉加载
        if(window.innerWidth < 1024){
            var throttle = false;
            window.addEventListener('scroll', function(){
                if(throttle) return;
                throttle = true;
                setTimeout(function(){ throttle = false; }, 200);

                var scrollBottom = document.documentElement.scrollHeight - window.innerHeight - window.scrollY;
                if(scrollBottom < 200){
                    var type = state.currentTab;
                    var noMore = (type === 'photo') ? state.photoNoMore : state.videoNoMore;
                    var loading = (type === 'photo') ? state.photoLoading : state.videoLoading;
                    if(!noMore && !loading){
                        loadScenes(type, false);
                    }
                }
            });
        }
    }

    // === 移动端 TabBar ===
    function initMobileTabbar(){
        document.querySelectorAll('.tabbar-item').forEach(function(item){
            item.addEventListener('click', function(e){
                var action = this.getAttribute('data-action');
                if(action === 'home'){
                    window.scrollTo({top: 0, behavior: 'smooth'});
                } else if(action === 'create'){
                    e.preventDefault();
                    var panel = document.querySelector('.create-panel');
                    if(panel) panel.classList.toggle('show');
                }
                // 更新激活状态
                document.querySelectorAll('.tabbar-item').forEach(function(t){ t.classList.remove('active'); });
                this.classList.add('active');
            });
        });

        // 点击其他区域关闭创作面板
        document.addEventListener('click', function(e){
            var panel = document.querySelector('.create-panel');
            if(panel && panel.classList.contains('show')){
                if(!e.target.closest('.create-panel') && !e.target.closest('[data-action="create"]')){
                    panel.classList.remove('show');
                }
            }
        });
    }

    // === APP二维码弹窗（移动端） ===
    function initQrModal(){
        document.querySelectorAll('.qr-trigger-mobile').forEach(function(btn){
            btn.addEventListener('click', function(e){
                e.preventDefault();
                var modal = document.querySelector('.qr-modal');
                if(modal) modal.classList.add('show');
            });
        });
        var modal = document.querySelector('.qr-modal');
        if(modal){
            modal.querySelector('.qr-overlay').addEventListener('click', function(){
                modal.classList.remove('show');
            });
        }
    }

    // === 更多菜单 Action Sheet（移动端） ===
    function initActionSheet(){
        document.querySelectorAll('.more-trigger-mobile').forEach(function(btn){
            btn.addEventListener('click', function(e){
                e.preventDefault();
                var sheet = document.querySelector('.action-sheet');
                var ov = document.querySelector('.sidebar-overlay');
                if(sheet){
                    sheet.classList.add('show');
                    if(ov) ov.classList.add('show');
                }
            });
        });
        var cancelBtn = document.querySelector('.action-sheet .as-cancel');
        if(cancelBtn){
            cancelBtn.addEventListener('click', function(){
                document.querySelector('.action-sheet').classList.remove('show');
                document.querySelector('.sidebar-overlay').classList.remove('show');
            });
        }
    }

    // === 全屏搜索（移动端） ===
    function initFullscreenSearch(){
        var fsSearch = document.querySelector('.fullscreen-search');
        document.querySelectorAll('.header-search-icon-mobile').forEach(function(btn){
            btn.addEventListener('click', function(){
                if(fsSearch){
                    fsSearch.classList.add('show');
                    fsSearch.querySelector('.fs-input').focus();
                }
            });
        });
        if(fsSearch){
            fsSearch.querySelector('.fs-cancel').addEventListener('click', function(){
                fsSearch.classList.remove('show');
            });
            fsSearch.querySelector('.fs-input').addEventListener('keydown', function(e){
                if(e.key === 'Enter'){
                    doSearch(this.value.trim());
                    fsSearch.classList.remove('show');
                }
            });
        }
    }

    // === 构建场景卡片封面HTML（视频类型：GIF + hover视频，图片类型：普通img） ===
    function buildSceneCoverHtml(item){
        var altText = escapeHtml(item.template_name || item.model_name || '');
        var coverUrl = item.cover_image || '/static/index3/img/placeholder.png';
        var gifCover = item.gif_cover || '';
        var isVideo = (parseInt(item.generation_type) === 2);

        if(isVideo && gifCover){
            return '<div class="sc-cover-wrap">' +
                '<img class="sc-cover" src="' + gifCover + '" alt="' + altText + '" loading="lazy">' +
                '<video class="sc-video" src="' + coverUrl + '" muted loop playsinline preload="none"></video>' +
                '<span class="sc-video-badge">▶ 视频</span>' +
            '</div>';
        } else if(isVideo && !gifCover){
            // 视频模板但无GIF，用cover_image作为video源，首帧会显示为poster
            return '<div class="sc-cover-wrap">' +
                '<video class="sc-cover" src="' + coverUrl + '" muted loop playsinline preload="metadata" style="object-fit:cover"></video>' +
                '<span class="sc-video-badge">▶ 视频</span>' +
            '</div>';
        }
        return '<img class="sc-cover" src="' + coverUrl + '" alt="' + altText + '" loading="lazy">';
    }

    // === 场景卡片点击交互（底部“做同款”按钮） ===
    function initSceneCards(){
        // 绑定服务端渲染的卡片
        document.querySelectorAll('.scene-card[data-id]').forEach(function(card){
            bindSceneCard(card);
        });
    }

    function bindSceneCard(card){
        var btn = card.querySelector('.sc-hover-btn');
        if(btn){
            btn.addEventListener('click', function(e){
                e.stopPropagation();
                var id = card.getAttribute('data-id');
                var type = card.getAttribute('data-type');
                var genType = (type === 'video') ? 2 : 1;
                openScenePopup(id, genType);
            });
        }
        // 卡片自身点击也打开弹窗
        card.addEventListener('click', function(){
            var id = this.getAttribute('data-id');
            var type = this.getAttribute('data-type');
            var genType = (type === 'video') ? 2 : 1;
            openScenePopup(id, genType);
        });
        // 视频卡片hover交互：鼠标移入播放视频，移出暂停
        bindVideoCardHover(card);
    }

    // === 视频卡片hover播放/暂停 ===
    function bindVideoCardHover(card){
        var wrap = card.querySelector('.sc-cover-wrap');
        if(!wrap) return;
        var video = wrap.querySelector('video');
        if(!video) return;

        card.addEventListener('mouseenter', function(){
            try { video.play(); } catch(e){}
        });
        card.addEventListener('mouseleave', function(){
            try {
                video.pause();
                video.currentTime = 0;
            } catch(e){}
        });
    }

    // === 场景弹窗初始化 ===
    function initScenePopup(){
        var overlay = document.getElementById('scenePopupOverlay');
        var closeBtn = document.getElementById('spCloseBtn');
        var expandBtn = document.getElementById('spExpandBtn');
        var imageAdd = document.getElementById('spImageAdd');
        var fileInput = document.getElementById('spFileInput');
        var submitBtn = document.getElementById('spSubmitBtn');
        var ratioTrigger = document.getElementById('spRatioTrigger');
        var quantityTrigger = document.getElementById('spQuantityTrigger');

        if(overlay) overlay.addEventListener('click', closeScenePopup);
        if(closeBtn) closeBtn.addEventListener('click', closeScenePopup);
        if(expandBtn) expandBtn.addEventListener('click', togglePopupExpand);
        if(imageAdd) imageAdd.addEventListener('click', function(){ fileInput.click(); });
        if(fileInput) fileInput.addEventListener('change', handleImageUpload);
        if(submitBtn) submitBtn.addEventListener('click', submitSceneGeneration);
        if(ratioTrigger) ratioTrigger.addEventListener('click', function(e){
            e.stopPropagation();
            toggleDropdown('spRatioPanel', 'spRatioTrigger');
        });
        if(quantityTrigger) quantityTrigger.addEventListener('click', function(e){
            e.stopPropagation();
            toggleDropdown('spQuantityPanel', 'spQuantityTrigger');
        });

        // 点击其他地方关闭下拉面板
        document.addEventListener('click', function(){
            closeAllDropdowns();
        });

        // Esc关闭弹窗
        document.addEventListener('keydown', function(e){
            if(e.key === 'Escape'){
                var popup = document.getElementById('scenePopup');
                if(popup && popup.classList.contains('show')){
                    closeScenePopup();
                }
            }
        });
    }

    // === 打开场景弹窗 ===
    function openScenePopup(templateId, generationType){
        var overlay = document.getElementById('scenePopupOverlay');
        var popup = document.getElementById('scenePopup');
        var loading = document.getElementById('spLoading');
        var content = document.getElementById('spContent');

        if(!popup) return;

        state.popupTemplateId = templateId;
        state.popupGenerationType = generationType;
        state.popupExpanded = false;
        state.popupRefImages = [];
        state.popupSelectedRatio = '1:1';
        state.popupSelectedQuantity = 1;
        state.popupDetail = null;

        // 显示弹窗
        overlay.classList.add('show');
        popup.classList.add('show');
        popup.classList.remove('expanded');
        loading.style.display = 'flex';
        content.style.display = 'none';
        document.body.style.overflow = 'hidden';

        // 加载模板详情
        Api.getSceneDetail({template_id: templateId}, function(err, res){
            loading.style.display = 'none';

            if(err || !res || res.status !== 1){
                content.style.display = 'none';
                showToast(res && res.msg ? res.msg : '加载模板详情失败', 'error');
                return;
            }

            var data = res.data;
            state.popupDetail = data;

            // 解析比例选项
            var cap = data.model_capability || {};
            state.popupRatioOptions = cap.supported_ratios || ['1:1','2:3','3:2','3:4','4:3','9:16','16:9'];

            // 默认比例：优先使用模板的 default_params.ratio，否则取支持列表第一项
            var defaultRatio = '';
            if(data.default_params && data.default_params.ratio){
                defaultRatio = data.default_params.ratio;
            }
            if(!defaultRatio || state.popupRatioOptions.indexOf(defaultRatio) === -1){
                defaultRatio = state.popupRatioOptions[0] || '1:1';
            }
            state.popupSelectedRatio = defaultRatio;

            // 默认数量：使用模板的 output_quantity
            state.popupSelectedQuantity = data.output_quantity || 1;

            // 数量选项：根据模型能力 max_images 限制范围
            var maxImages = cap.max_images || 9;
            if(maxImages < 1) maxImages = 1;
            if(maxImages > 9) maxImages = 9;
            var qtyOptions = [];
            for(var qi = 1; qi <= maxImages; qi++){
                qtyOptions.push(qi);
            }
            state.popupQuantityOptions = qtyOptions;
            // 确保默认数量在有效范围内
            if(state.popupSelectedQuantity > maxImages){
                state.popupSelectedQuantity = maxImages;
            }
            if(state.popupSelectedQuantity < 1){
                state.popupSelectedQuantity = 1;
            }

            // Row 1: 类型标题
            var typeText = (generationType === 2) ? '🎬 视频生成' : '🖼️ 图片生成';
            var rowType = document.getElementById('spRowType');
            rowType.innerHTML = '<span class="sp-type-badge">' + typeText + '</span>' +
                '<span class="sp-template-name">' + escapeHtml(data.template_name) + '</span>';

            // 填充提示词
            var promptEl = document.getElementById('spPrompt');
            promptEl.value = data.prompt || '';

            // 填充参考图（ref_image 可能是字符串或数组）
            var imagesRow = document.getElementById('spRowImages');
            imagesRow.innerHTML = '';
            state.popupRefImages = [];
            if(data.ref_image){
                if(Array.isArray(data.ref_image)){
                    for(var ri = 0; ri < data.ref_image.length; ri++){
                        if(data.ref_image[ri]){
                            state.popupRefImages.push(data.ref_image[ri]);
                        }
                    }
                } else {
                    state.popupRefImages.push(data.ref_image);
                }
            }
            renderPopupRefImages();

            // 填充比例下拉
            renderRatioPanel();
            document.getElementById('spRatioValue').textContent = state.popupSelectedRatio;

            // 填充数量下拉（视频隐藏数量选择）
            var qtyGroup = document.getElementById('spQuantityGroup');
            if(generationType === 2){
                qtyGroup.style.display = 'none';
            } else {
                qtyGroup.style.display = '';
                renderQuantityPanel();
                document.getElementById('spQuantityValue').textContent = state.popupSelectedQuantity;
            }

            content.style.display = 'flex';
        });
    }

    // === 关闭场景弹窗 ===
    function closeScenePopup(){
        var overlay = document.getElementById('scenePopupOverlay');
        var popup = document.getElementById('scenePopup');
        if(overlay) overlay.classList.remove('show');
        if(popup){
            popup.classList.remove('show');
            popup.classList.remove('expanded');
        }
        document.body.style.overflow = '';
        closeAllDropdowns();
    }

    // === 切换弹窗展开/收起 ===
    function togglePopupExpand(){
        var popup = document.getElementById('scenePopup');
        if(!popup) return;
        state.popupExpanded = !state.popupExpanded;
        popup.classList.toggle('expanded', state.popupExpanded);
    }

    // === 渲染参考图 ===
    function renderPopupRefImages(){
        var container = document.getElementById('spRowImages');
        if(!container) return;
        container.innerHTML = '';
        state.popupRefImages.forEach(function(url, idx){
            var item = document.createElement('div');
            item.className = 'sp-image-item';
            item.innerHTML = '<img src="' + escapeHtml(url) + '" alt="参考图">' +
                '<button class="sp-image-remove" data-idx="' + idx + '">&times;</button>';
            container.appendChild(item);
            item.querySelector('.sp-image-remove').addEventListener('click', function(){
                var i = parseInt(this.getAttribute('data-idx'));
                state.popupRefImages.splice(i, 1);
                renderPopupRefImages();
            });
        });
        // 最多3张参考图
        if(state.popupRefImages.length < 3){
            var addBtn = document.createElement('div');
            addBtn.className = 'sp-image-add';
            addBtn.textContent = '+';
            addBtn.title = '添加参考图';
            addBtn.addEventListener('click', function(){
                requireLogin(function(){
                    document.getElementById('spFileInput').click();
                });
            });
            container.appendChild(addBtn);
        }
    }

    // === 图片上传 ===
    function handleImageUpload(){
        var fileInput = document.getElementById('spFileInput');
        if(!fileInput.files || !fileInput.files[0]) return;
        var file = fileInput.files[0];
        if(file.size > 10 * 1024 * 1024){
            showToast('图片大小不能超过10MB', 'warning');
            fileInput.value = '';
            return;
        }

        showToast('上传中...', 'info');
        Api.uploadImage(file, function(err, res){
            fileInput.value = '';
            if(err || !res || res.status !== 1){
                showToast(res && res.msg ? res.msg : '上传失败', 'error');
                return;
            }
            state.popupRefImages.push(res.url);
            renderPopupRefImages();
            showToast('上传成功', 'success');
        });
    }

    // === 渲染比例下拉面板 ===
    function renderRatioPanel(){
        var panel = document.getElementById('spRatioPanel');
        if(!panel) return;
        panel.innerHTML = '';
        state.popupRatioOptions.forEach(function(r){
            var item = document.createElement('div');
            item.className = 'sp-dropdown-item' + (r === state.popupSelectedRatio ? ' active' : '');
            item.textContent = r;
            item.addEventListener('click', function(e){
                e.stopPropagation();
                state.popupSelectedRatio = r;
                document.getElementById('spRatioValue').textContent = r;
                renderRatioPanel();
                closeAllDropdowns();
            });
            panel.appendChild(item);
        });
    }

    // === 渲染数量下拉面板 ===
    function renderQuantityPanel(){
        var panel = document.getElementById('spQuantityPanel');
        if(!panel) return;
        panel.innerHTML = '';
        state.popupQuantityOptions.forEach(function(q){
            var item = document.createElement('div');
            item.className = 'sp-dropdown-item' + (q === state.popupSelectedQuantity ? ' active' : '');
            item.textContent = q + '张';
            item.addEventListener('click', function(e){
                e.stopPropagation();
                state.popupSelectedQuantity = q;
                document.getElementById('spQuantityValue').textContent = q;
                renderQuantityPanel();
                closeAllDropdowns();
            });
            panel.appendChild(item);
        });
    }

    // === 下拉面板切换 ===
    function toggleDropdown(panelId, triggerId){
        var panel = document.getElementById(panelId);
        var trigger = document.getElementById(triggerId);
        var isOpen = panel.classList.contains('show');
        closeAllDropdowns();
        if(!isOpen){
            panel.classList.add('show');
            if(trigger) trigger.classList.add('open');
        }
    }

    function closeAllDropdowns(){
        document.querySelectorAll('.sp-dropdown-panel.show').forEach(function(p){ p.classList.remove('show'); });
        document.querySelectorAll('.sp-param-trigger.open').forEach(function(t){ t.classList.remove('open'); });
    }

    // === 提交生成任务 ===
    function submitSceneGeneration(){
        if(state.popupSubmitting) return;

        // 登录检测
        if(!state.isLoggedIn){
            requireLogin(function(){
                submitSceneGeneration();
            });
            return;
        }

        var prompt = (document.getElementById('spPrompt').value || '').trim();
        if(prompt.length < 2){
            showToast('请填写提示词（至少2个字符）', 'warning');
            return;
        }

        state.popupSubmitting = true;
        var submitBtn = document.getElementById('spSubmitBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = '提交中...';

        var postData = {
            template_id: state.popupTemplateId,
            generation_type: state.popupGenerationType,
            prompt: prompt,
            ratio: state.popupSelectedRatio
        };

        // 参考图（使用数组格式提交，便于后端 /a 修饰符正确解析）
        if(state.popupRefImages.length > 0){
            postData['ref_images'] = state.popupRefImages;
        }

        // 数量（图片生成）
        if(state.popupGenerationType === 1){
            postData.quantity = state.popupSelectedQuantity;
        }

        Api.createGenerationOrder(postData, function(err, res){
            state.popupSubmitting = false;
            submitBtn.disabled = false;
            submitBtn.textContent = '立即生成';

            if(err){
                showToast('网络错误，请重试', 'error');
                return;
            }

            if(!res || res.status !== 1){
                var msg = (res && res.msg) ? res.msg : '提交失败';
                // 未登录检测
                if(msg.indexOf('登录') > -1 || msg.indexOf('登陆') > -1){
                    state.isLoggedIn = false;
                    state.loginChecked = true;
                    requireLogin(function(){
                        submitSceneGeneration();
                    });
                    return;
                }
                // 余额不足
                if(msg.indexOf('余额') > -1 || msg.indexOf('充值') > -1 || msg.indexOf('费用') > -1){
                    showToast(msg, 'warning');
                    return;
                }
                showToast(msg, 'error');
                return;
            }

            showToast('生成任务已提交！', 'success');
            closeScenePopup();
        });
    }

    // === Helpers ===
    function escapeHtml(str){
        if(!str) return '';
        if(typeof str !== 'string') str = String(str);
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // 暴露给外部
    window.Index3 = { 
        loadScenes: loadScenes, 
        doSearch: doSearch, 
        closeTaskModal: closeTaskModal, 
        openTaskModal: openTaskModal,
        showToast: showToast,
        openScenePopup: openScenePopup,
        closeScenePopup: closeScenePopup,
        showLoginModal: showLoginModal
    };
})();
