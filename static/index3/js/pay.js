/**
 * pay.js — 统一支付模块
 * 支付方式选择弹窗、二维码展示、支付结果轮询
 */
var Pay = (function(){
    var pollTimer = null;
    var pollCount = 0;
    var MAX_POLL = 120; // 最大轮询次数（每2s一次，共4分钟）
    var currentOrdernum = '';
    var currentOrderType = '';
    var onSuccessCallback = null;

    // ========== 检测浏览器环境 ==========
    function isWechatBrowser(){
        var ua = navigator.userAgent.toLowerCase();
        return ua.indexOf('micromessenger') > -1;
    }
    function isAlipayBrowser(){
        var ua = navigator.userAgent.toLowerCase();
        return ua.indexOf('alipayclient') > -1;
    }

    // ========== 发起支付流程 ==========
    /**
     * @param {object} options
     * @param {string} options.ordernum     订单号
     * @param {string} options.order_type   订单类型 recharge/score/level/creative_member
     * @param {number} options.amount       金额
     * @param {string} options.title        订单描述
     * @param {function} options.onSuccess  支付成功回调
     */
    function startPay(options){
        currentOrdernum = options.ordernum;
        currentOrderType = options.order_type;
        onSuccessCallback = options.onSuccess || null;

        // 微信浏览器内直接走微信支付
        if(isWechatBrowser()){
            callH5Pay(options.ordernum, 'wxpay', options.order_type);
            return;
        }
        // 支付宝浏览器内直接走支付宝
        if(isAlipayBrowser()){
            callH5Pay(options.ordernum, 'alipay', options.order_type);
            return;
        }

        // PC普通浏览器：先检查可用支付方式，再决定展示模式
        Api.getPayConfig(function(err, res){
            if(err || !res || res.status !== 1){
                showToast('获取支付配置失败', 'error');
                return;
            }
            var payTypes = (res.data && res.data.pay_types) ? res.data.pay_types : [];
            if(payTypes.length === 0){
                showToast('没有可用的支付方式，请联系管理员配置', 'error');
                return;
            }

            var hasWxpay = false, hasAlipay = false;
            var wxMode = '', aliMode = '';
            for(var i = 0; i < payTypes.length; i++){
                if(payTypes[i].id === 'wxpay') { hasWxpay = true; wxMode = payTypes[i].mode || 'qrcode'; }
                if(payTypes[i].id === 'alipay') { hasAlipay = true; aliMode = payTypes[i].mode || 'form'; }
            }

            if(hasWxpay && hasAlipay){
                // 两种支付方式都可用：检查是否都支持二维码
                if(wxMode === 'qrcode' && aliMode === 'qrcode'){
                    // 双码模式：同时展示微信和支付宝二维码
                    showDualQrcodeModal(options);
                } else {
                    // 混合模式：展示支付方式选择弹窗，用户自己选择
                    showPayModal(options, payTypes);
                }
            } else if(payTypes.length === 1){
                // 只有一种支付方式：直接调用，显示二维码或表单
                showSinglePayModal(options, payTypes[0]);
            } else {
                // 多种支付方式但非标准组合：展示选择弹窗
                showPayModal(options, payTypes);
            }
        });
    }

    // ========== 支付方式选择弹窗 ==========
    function showPayModal(options, payTypes){
        // 移除已有弹窗
        var existing = document.getElementById('payModalOverlay');
        if(existing) existing.parentNode.removeChild(existing);

        var overlay = document.createElement('div');
        overlay.id = 'payModalOverlay';
        overlay.className = 'pay-modal-overlay show';

        var typesHtml = '';
        var icons = { wxpay: '💚', alipay: '🔵' };
        var iconClass = { wxpay: 'wxpay', alipay: 'alipay' };
        for(var i = 0; i < payTypes.length; i++){
            var pt = payTypes[i];
            var activeClass = i === 0 ? ' active' : '';
            typesHtml +=
                '<div class="pay-type-item' + activeClass + '" data-paytype="' + pt.id + '">' +
                    '<div class="pay-type-icon ' + (iconClass[pt.id] || '') + '">' + (icons[pt.id] || '💳') + '</div>' +
                    '<div class="pay-type-name">' + escapeHtml(pt.name) + '</div>' +
                    '<div class="pay-type-check"></div>' +
                '</div>';
        }

        overlay.innerHTML =
            '<div class="pay-modal">' +
                '<div class="pay-modal-header">' +
                    '<div class="pay-modal-title">选择支付方式</div>' +
                    '<button class="pay-modal-close" id="payModalClose">✕</button>' +
                '</div>' +
                '<div class="pay-order-info">' +
                    '<div class="pay-order-type">' + escapeHtml(options.title || '订单支付') + '</div>' +
                    '<div class="pay-order-amount"><small>¥</small>' + (options.amount || '0.00') + '</div>' +
                '</div>' +
                '<div class="pay-type-list" id="payTypeList">' + typesHtml + '</div>' +
                '<div class="pay-confirm-wrap">' +
                    '<button class="pay-confirm-btn" id="payConfirmBtn">确认支付 ¥' + (options.amount || '0.00') + '</button>' +
                '</div>' +
                '<div class="pay-status-area" id="payStatusArea"></div>' +
            '</div>';

        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        // 绑定事件
        document.getElementById('payModalClose').addEventListener('click', closePayModal);
        overlay.addEventListener('click', function(e){ if(e.target === overlay) closePayModal(); });

        // 支付方式选择
        var items = document.querySelectorAll('#payTypeList .pay-type-item');
        for(var k = 0; k < items.length; k++){
            items[k].addEventListener('click', function(){
                for(var j = 0; j < items.length; j++) items[j].classList.remove('active');
                this.classList.add('active');
            });
        }

        // 确认支付
        document.getElementById('payConfirmBtn').addEventListener('click', function(){
            var selected = document.querySelector('#payTypeList .pay-type-item.active');
            if(!selected) { showToast('请选择支付方式', 'warning'); return; }
            var payType = selected.getAttribute('data-paytype');
            this.disabled = true;
            this.textContent = '处理中...';
            callH5Pay(options.ordernum, payType, options.order_type);
        });
    }

    function closePayModal(){
        stopPolling();
        var overlay = document.getElementById('payModalOverlay');
        if(overlay){
            overlay.classList.remove('show');
            document.body.style.overflow = '';
            setTimeout(function(){ if(overlay.parentNode) overlay.parentNode.removeChild(overlay); }, 300);
        }
    }

    // ========== 单一支付方式弹窗（仅一种可用时直接调用） ==========
    function showSinglePayModal(options, payType){
        // 移除已有弹窗
        var existing = document.getElementById('payModalOverlay');
        if(existing) existing.parentNode.removeChild(existing);

        var overlay = document.createElement('div');
        overlay.id = 'payModalOverlay';
        overlay.className = 'pay-modal-overlay show';

        var payLabel = payType.name || (payType.id === 'wxpay' ? '微信支付' : '支付宝支付');

        overlay.innerHTML =
            '<div class="pay-modal">' +
                '<div class="pay-modal-header">' +
                    '<div class="pay-modal-title">' + escapeHtml(payLabel) + '</div>' +
                    '<button class="pay-modal-close" id="payModalClose">✕</button>' +
                '</div>' +
                '<div class="pay-order-info">' +
                    '<div class="pay-order-type">' + escapeHtml(options.title || '订单支付') + '</div>' +
                    '<div class="pay-order-amount"><small>¥</small>' + (options.amount || '0.00') + '</div>' +
                '</div>' +
                '<div class="pay-status-area show" id="payStatusArea">' +
                    '<div class="pay-loading"><div class="spinner"></div><span>正在创建支付订单...</span></div>' +
                '</div>' +
            '</div>';

        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        document.getElementById('payModalClose').addEventListener('click', closePayModal);
        overlay.addEventListener('click', function(e){ if(e.target === overlay) closePayModal(); });

        // 直接调用支付
        callH5Pay(options.ordernum, payType.id, options.order_type);
    }

    // ========== 调用H5支付接口 ==========
    function callH5Pay(ordernum, payType, orderType){
        Api.h5Pay({ordernum: ordernum, pay_type: payType, order_type: orderType}, function(err, res){
            if(err || !res || res.status !== 1){
                showToast(res ? res.msg : '支付请求失败', 'error');
                resetConfirmBtn();
                return;
            }
            var data = res.data;
            if(data.pay_method === 'qrcode'){
                showQrcode(data.qrcode_url);
                startPolling(ordernum);
            } else if(data.pay_method === 'redirect'){
                window.open(data.redirect_url, '_blank');
                startPolling(ordernum);
                showPollingStatus();
            } else if(data.pay_method === 'form'){
                // 支付宝电脑网站支付：新窗口渲染表单HTML并自动提交
                var formHtml = data.form_html || '';
                if(formHtml){
                    var payWin = window.open('', '_blank');
                    if(payWin){
                        payWin.document.write(formHtml);
                        payWin.document.close();
                    } else {
                        showToast('请允许弹出窗口后重试', 'warning');
                        resetConfirmBtn();
                        return;
                    }
                }
                startPolling(ordernum);
                showPollingStatus();
            } else if(data.pay_method === 'dual_qrcode'){
                // 双码模式：同时展示微信和支付宝二维码
                renderDualQrcodes(data.wxpay_qrcode, data.alipay_qrcode);
                startPolling(ordernum);
            } else if(data.pay_method === 'jsapi'){
                callJsapiPay(data.jsapi_params, ordernum);
            } else {
                showToast('未知支付方式', 'error');
                resetConfirmBtn();
            }
        });
    }

    // ========== 微信JSAPI支付 ==========
    function callJsapiPay(params, ordernum){
        if(typeof WeixinJSBridge === 'undefined'){
            showToast('请在微信浏览器中使用', 'error');
            return;
        }
        WeixinJSBridge.invoke('getBrandWCPayRequest', params, function(res){
            if(res.err_msg === 'get_brand_wcpay_request:ok'){
                showPayResult(true);
                if(onSuccessCallback) onSuccessCallback();
            } else {
                showToast('支付取消或失败', 'warning');
            }
        });
    }

    // ========== 二维码展示 ==========
    function showQrcode(qrcodeUrl){
        var statusArea = document.getElementById('payStatusArea');
        if(!statusArea) return;
        var confirmWrap = statusArea.parentNode.querySelector('.pay-confirm-wrap');
        var typeList = statusArea.parentNode.querySelector('.pay-type-list');
        if(confirmWrap) confirmWrap.style.display = 'none';
        if(typeList) typeList.style.display = 'none';

        statusArea.innerHTML =
            '<div class="pay-qrcode-wrap">' +
                '<img src="' + escapeHtml(qrcodeUrl) + '" alt="支付二维码">' +
            '</div>' +
            '<div class="pay-qrcode-tip">请使用微信扫码支付</div>' +
            '<div class="pay-loading"><div class="spinner"></div><span>等待支付中...</span></div>';
        statusArea.classList.add('show');
    }

    // ========== 轮询等待状态 ==========
    function showPollingStatus(){
        var statusArea = document.getElementById('payStatusArea');
        if(!statusArea) return;
        var confirmWrap = statusArea.parentNode.querySelector('.pay-confirm-wrap');
        if(confirmWrap) confirmWrap.style.display = 'none';
        statusArea.innerHTML =
            '<div class="pay-loading"><div class="spinner"></div><span>等待支付结果...</span></div>' +
            '<div class="pay-qrcode-tip">请在打开的页面完成支付</div>';
        statusArea.classList.add('show');
    }

    // ========== 支付结果展示 ==========
    function showPayResult(success){
        stopPolling();
        // 隐藏双码区域（如有）
        var dualQrcode = document.getElementById('payDualQrcode');
        if(dualQrcode) dualQrcode.style.display = 'none';
        var statusArea = document.getElementById('payStatusArea');
        if(!statusArea) return;
        if(success){
            statusArea.innerHTML =
                '<div class="pay-result">' +
                    '<div class="pay-result-icon">✅</div>' +
                    '<div class="pay-result-text">支付成功</div>' +
                    '<div class="pay-result-desc">页面即将自动刷新</div>' +
                '</div>';
        } else {
            statusArea.innerHTML =
                '<div class="pay-result">' +
                    '<div class="pay-result-icon">❌</div>' +
                    '<div class="pay-result-text">支付超时</div>' +
                    '<div class="pay-result-desc">如已支付请刷新页面查看</div>' +
                '</div>';
        }
        statusArea.classList.add('show');

        if(success){
            setTimeout(function(){
                closePayModal();
                if(onSuccessCallback) onSuccessCallback();
            }, 1500);
        }
    }

    // ========== 轮询支付状态 ==========
    function startPolling(ordernum){
        pollCount = 0;
        stopPolling();
        pollTimer = setInterval(function(){
            pollCount++;
            if(pollCount > MAX_POLL){
                showPayResult(false);
                return;
            }
            Api.checkPayStatus({ordernum: ordernum}, function(err, res){
                if(!err && res && res.status === 1 && res.data && res.data.paid){
                    showPayResult(true);
                }
            });
        }, 2000);
    }

    function stopPolling(){
        if(pollTimer){ clearInterval(pollTimer); pollTimer = null; }
    }

    // ========== 重置确认按钮 ==========
    function resetConfirmBtn(){
        var btn = document.getElementById('payConfirmBtn');
        if(btn){ btn.disabled = false; btn.textContent = '确认支付'; }
    }

    // ========== 双码弹窗：同时展示微信和支付宝二维码 ==========
    function showDualQrcodeModal(options){
        // 移除已有弹窗
        var existing = document.getElementById('payModalOverlay');
        if(existing) existing.parentNode.removeChild(existing);

        var overlay = document.createElement('div');
        overlay.id = 'payModalOverlay';
        overlay.className = 'pay-modal-overlay show';

        overlay.innerHTML =
            '<div class="pay-modal pay-modal--dual">' +
                '<div class="pay-modal-header">' +
                    '<div class="pay-modal-title">扫码支付</div>' +
                    '<button class="pay-modal-close" id="payModalClose">✕</button>' +
                '</div>' +
                '<div class="pay-order-info">' +
                    '<div class="pay-order-type">' + escapeHtml(options.title || '订单支付') + '</div>' +
                    '<div class="pay-order-amount"><small>¥</small>' + (options.amount || '0.00') + '</div>' +
                '</div>' +
                '<div class="pay-dual-qrcode" id="payDualQrcode">' +
                    '<div class="pay-loading"><div class="spinner"></div><span>正在生成支付二维码...</span></div>' +
                '</div>' +
                '<div class="pay-status-area" id="payStatusArea"></div>' +
            '</div>';

        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        // 绑定关闭事件
        document.getElementById('payModalClose').addEventListener('click', closePayModal);
        overlay.addEventListener('click', function(e){ if(e.target === overlay) closePayModal(); });

        // 请求双码
        Api.h5Pay({ordernum: options.ordernum, pay_type: 'all', order_type: options.order_type}, function(err, res){
            var qrcodeArea = document.getElementById('payDualQrcode');
            if(err || !res || res.status !== 1){
                if(qrcodeArea){
                    qrcodeArea.innerHTML = '<div class="pay-dual-error">' + escapeHtml(res ? res.msg : '支付请求失败') + '</div>';
                }
                return;
            }

            var data = res.data;
            if(data.pay_method === 'dual_qrcode'){
                renderDualQrcodes(data.wxpay_qrcode, data.alipay_qrcode);
                startPolling(options.ordernum);
            } else if(data.pay_method === 'qrcode'){
                // 后备：只有单一支付方式二维码
                renderDualQrcodes(data.qrcode_url, '');
                startPolling(options.ordernum);
            } else if(data.pay_method === 'form'){
                // 后备：二维码不可用，降级为表单跳转（如支付宝手机网站支付/电脑网站支付）
                var formHtml = data.form_html || '';
                if(formHtml){
                    var payWin = window.open('', '_blank');
                    if(payWin){
                        payWin.document.write(formHtml);
                        payWin.document.close();
                        startPolling(options.ordernum);
                        if(qrcodeArea) qrcodeArea.innerHTML = '<div class="pay-dual-info">已在新窗口打开支付页面，请在新窗口完成支付</div>';
                        showPollingStatus();
                    } else {
                        if(qrcodeArea) qrcodeArea.innerHTML = '<div class="pay-dual-error">请允许弹出窗口后重试</div>';
                    }
                } else {
                    if(qrcodeArea) qrcodeArea.innerHTML = '<div class="pay-dual-error">支付表单生成失败，请重试</div>';
                }
            } else if(data.pay_method === 'redirect'){
                // 后备：跳转支付
                if(data.redirect_url){
                    window.open(data.redirect_url, '_blank');
                    startPolling(options.ordernum);
                    if(qrcodeArea) qrcodeArea.innerHTML = '<div class="pay-dual-info">已在新窗口打开支付页面，请在新窗口完成支付</div>';
                    showPollingStatus();
                } else {
                    if(qrcodeArea) qrcodeArea.innerHTML = '<div class="pay-dual-error">支付链接获取失败</div>';
                }
            } else {
                if(qrcodeArea){
                    qrcodeArea.innerHTML = '<div class="pay-dual-error">暂无可用的扫码支付方式</div>';
                }
            }
        });
    }

    function renderDualQrcodes(wxpayQr, alipayQr){
        var container = document.getElementById('payDualQrcode');
        if(!container) return;

        var hasWx = !!wxpayQr;
        var hasAli = !!alipayQr;
        var singleMode = (hasWx && !hasAli) || (!hasWx && hasAli);

        var html = '<div class="dual-qrcode-grid' + (singleMode ? ' dual-qrcode-grid--single' : '') + '">';

        if(hasWx){
            html +=
                '<div class="dual-qrcode-item dual-qrcode-item--wx">' +
                    '<div class="dual-qrcode-header">' +
                        '<span class="dual-qrcode-badge dual-qrcode-badge--wx"></span>' +
                        '<span class="dual-qrcode-label dual-qrcode-label--wx">微信支付</span>' +
                    '</div>' +
                    '<div class="dual-qrcode-img">' +
                        '<img src="' + escapeHtml(wxpayQr) + '" alt="微信支付二维码">' +
                    '</div>' +
                    '<div class="dual-qrcode-tip dual-qrcode-tip--wx">请使用微信扫一扫</div>' +
                '</div>';
        }

        if(hasAli){
            html +=
                '<div class="dual-qrcode-item dual-qrcode-item--ali">' +
                    '<div class="dual-qrcode-header">' +
                        '<span class="dual-qrcode-badge dual-qrcode-badge--ali"></span>' +
                        '<span class="dual-qrcode-label dual-qrcode-label--ali">支付宝</span>' +
                    '</div>' +
                    '<div class="dual-qrcode-img">' +
                        '<img src="' + escapeHtml(alipayQr) + '" alt="支付宝二维码">' +
                    '</div>' +
                    '<div class="dual-qrcode-tip dual-qrcode-tip--ali">请使用支付宝扫一扫</div>' +
                '</div>';
        }

        html += '</div>';
        html += '<div class="pay-loading dual-qrcode-waiting"><div class="spinner"></div><span>等待扫码支付...</span></div>';

        container.innerHTML = html;
    }

    // ========== 工具函数 ==========
    function escapeHtml(str){
        if(!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function showToast(msg, type){
        if(window.Auth && window.Index3 && window.Index3.showToast){
            window.Index3.showToast(msg, type);
        } else if(window.Auth){
            // 使用简易toast
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
            toast.innerHTML = '<span style="font-size:16px">' + icon + '</span><span>' + escapeHtml(msg) + '</span>';
            container.appendChild(toast);
            setTimeout(function(){
                toast.classList.add('hiding');
                setTimeout(function(){ if(toast.parentNode) toast.parentNode.removeChild(toast); }, 300);
            }, 3000);
        } else {
            alert(msg);
        }
    }

    // ========== 公开接口 ==========
    return {
        startPay: startPay,
        closePayModal: closePayModal,
        isWechatBrowser: isWechatBrowser,
        isAlipayBrowser: isAlipayBrowser
    };
})();
