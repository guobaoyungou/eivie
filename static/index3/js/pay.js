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

        // 普通浏览器：先获取可用支付方式，然后弹窗选择
        Api.getPayConfig(function(err, res){
            if(err || !res || res.status !== 1){
                showToast('获取支付配置失败', 'error');
                return;
            }
            var payTypes = res.data.pay_types || [];
            if(payTypes.length === 0){
                showToast('暂无可用支付方式', 'error');
                return;
            }
            if(payTypes.length === 1){
                // 只有一种支付方式，直接调用
                callH5Pay(options.ordernum, payTypes[0].id, options.order_type);
                return;
            }
            showPayModal(options, payTypes);
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
