/**
 * generation.js — 图片/视频生成页交互逻辑（重构版）
 * 两页共享同一套逻辑，通过 data-page-type 区分页面类型
 */
(function(){
    'use strict';

    // === 页面状态 ===
    var state = {
        pageType: 'photo',          // 'photo' | 'video'
        selectedModelId: null,
        selectedModelName: '',
        modelListCache: null,       // 模型列表缓存
        templateBarCollapsed: false,
        uploadedFile: null
    };

    document.addEventListener('DOMContentLoaded', function(){
        // 识别页面类型
        var container = document.querySelector('.generation-container');
        if(container && container.getAttribute('data-page-type')){
            state.pageType = container.getAttribute('data-page-type');
        }

        initPillSelectors();
        initPopoverSelectors();
        initModelCardClick();
        initModelModal();
        initImageUpload();
        initGenerateButton();
        initTemplateClick();
        initTemplateBarCollapse();
        initTemplateBarScroll();
        initClickOutside();
    });

    // ====================================================
    // Pill 选择器
    // ====================================================
    function initPillSelectors(){
        document.querySelectorAll('.gf-pill-group').forEach(function(group){
            group.querySelectorAll('.gf-pill').forEach(function(pill){
                pill.addEventListener('click', function(){
                    // 同组单选互斥
                    group.querySelectorAll('.gf-pill').forEach(function(p){
                        p.classList.remove('active');
                    });
                    this.classList.add('active');
                });
            });

            // 触摸拖拽横向滚动
            enableTouchScroll(group);
        });
    }

    function getPillValue(paramName){
        // 先查popover选择器
        var popCard = document.querySelector('.gf-popover-card[data-param="' + paramName + '"]');
        if(popCard) return popCard.getAttribute('data-value') || '';
        // 再查pill选择器
        var group = document.querySelector('.gf-pill-group[data-param="' + paramName + '"]');
        if(!group) return '';
        var active = group.querySelector('.gf-pill.active');
        return active ? active.getAttribute('data-value') : '';
    }

    // ====================================================
    // Popover 弹出选择器（比例/数量/时长）
    // ====================================================
    function initPopoverSelectors(){
        document.querySelectorAll('.gf-popover-card').forEach(function(card){
            card.addEventListener('click', function(e){
                e.stopPropagation();
                var wrap = this.closest('.gf-popover-wrap');
                var dropdown = wrap ? wrap.querySelector('.gf-popover-dropdown') : null;
                if(!dropdown) return;

                // 关闭其他已打开的popover
                closeAllPopovers(dropdown);

                var isOpen = dropdown.classList.contains('active');
                if(isOpen){
                    dropdown.classList.remove('active');
                    card.classList.remove('open');
                } else {
                    dropdown.classList.add('active');
                    card.classList.add('open');
                }
            });
        });

        // 绑定选项点击
        document.querySelectorAll('.gf-popover-dropdown .gf-popover-option').forEach(function(option){
            option.addEventListener('click', function(e){
                e.stopPropagation();
                var dropdown = this.closest('.gf-popover-dropdown');
                var wrap = this.closest('.gf-popover-wrap');
                var card = wrap ? wrap.querySelector('.gf-popover-card') : null;
                var value = this.getAttribute('data-value');
                var label = this.textContent.trim();
                var param = dropdown.getAttribute('data-param');

                // 同组单选互斥
                dropdown.querySelectorAll('.gf-popover-option').forEach(function(opt){
                    opt.classList.remove('active');
                });
                this.classList.add('active');

                // 更新卡片显示
                if(card){
                    var textEl = card.querySelector('.gf-popover-card-text');
                    if(param === 'count'){
                        textEl.textContent = value + ' 张';
                    } else {
                        textEl.textContent = label;
                    }
                    card.setAttribute('data-value', value);
                    card.classList.add('has-value');
                    card.classList.remove('open');
                }

                // 关闭下拉
                dropdown.classList.remove('active');
            });
        });
    }

    function closeAllPopovers(exceptDropdown){
        document.querySelectorAll('.gf-popover-dropdown.active').forEach(function(d){
            if(d !== exceptDropdown){
                d.classList.remove('active');
                var wrap = d.closest('.gf-popover-wrap');
                if(wrap){
                    var card = wrap.querySelector('.gf-popover-card');
                    if(card) card.classList.remove('open');
                }
            }
        });
    }

    // ====================================================
    // 模型选择卡片
    // ====================================================
    function initModelCardClick(){
        var card = document.getElementById('modelCard');
        if(!card) return;
        card.addEventListener('click', function(){
            openModelModal();
        });
    }

    function updateModelCard(id, name){
        state.selectedModelId = id;
        state.selectedModelName = name;
        var card = document.getElementById('modelCard');
        var hidden = document.getElementById('selectedModelId');
        if(card){
            var textEl = card.querySelector('.gf-model-card-text');
            if(textEl) textEl.textContent = name || '选择模型';
            card.classList.toggle('selected', !!id);
        }
        if(hidden) hidden.value = id || '';
    }

    // ====================================================
    // 模型选择弹窗
    // ====================================================
    function initModelModal(){
        var modal = document.getElementById('modelModal');
        if(!modal) return;

        // 点击遮罩层关闭
        var overlay = modal.querySelector('.model-modal-overlay');
        if(overlay){
            overlay.addEventListener('click', function(){
                closeModelModal();
            });
        }

        // 关闭按钮
        var closeBtn = document.getElementById('modelModalClose');
        if(closeBtn){
            closeBtn.addEventListener('click', function(){
                closeModelModal();
            });
        }
    }

    function openModelModal(){
        var modal = document.getElementById('modelModal');
        if(!modal) return;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';

        // 加载数据（有缓存则不重复请求）
        if(state.modelListCache){
            renderModelGrid(state.modelListCache);
        } else {
            loadModelList();
        }
    }

    function closeModelModal(){
        var modal = document.getElementById('modelModal');
        if(!modal) return;
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    function loadModelList(){
        var grid = document.getElementById('modelModalGrid');
        if(grid) grid.innerHTML = '<div class="model-modal-loading">加载中...</div>';

        Api.getModelList({is_recommend: 1, limit: 50}, function(err, res){
            if(err || !res || res.code !== 0){
                if(grid) grid.innerHTML = '<div class="model-modal-empty">加载失败，请重试</div>';
                return;
            }
            state.modelListCache = res.data || [];
            renderModelGrid(state.modelListCache);
        });
    }

    function renderModelGrid(list){
        var grid = document.getElementById('modelModalGrid');
        if(!grid) return;

        if(!list || list.length === 0){
            grid.innerHTML = '<div class="model-modal-empty">暂无可用模型</div>';
            return;
        }

        var html = '';
        list.forEach(function(model){
            var selectedClass = (state.selectedModelId == model.id) ? ' selected' : '';
            var logoSrc = model.provider_logo || '/static/index3/img/placeholder.png';
            html += '<div class="model-select-item' + selectedClass + '" data-model-id="' + model.id + '" data-model-name="' + escapeHtml(model.model_name) + '">';
            html += '  <img class="model-item-logo" src="' + escapeHtml(logoSrc) + '" alt="" onerror="this.src=\'/static/index3/img/placeholder.png\'">';
            html += '  <div class="model-item-name">' + escapeHtml(model.model_name) + '</div>';
            html += '  <div class="model-item-provider">' + escapeHtml(model.provider_name || '') + '</div>';
            html += '</div>';
        });
        grid.innerHTML = html;

        // 绑定点击事件
        grid.querySelectorAll('.model-select-item').forEach(function(item){
            item.addEventListener('click', function(){
                var modelId = this.getAttribute('data-model-id');
                var modelName = this.getAttribute('data-model-name');
                // 更新选中态
                grid.querySelectorAll('.model-select-item').forEach(function(el){
                    el.classList.remove('selected');
                });
                this.classList.add('selected');
                // 回传数据并关闭
                updateModelCard(modelId, modelName);
                setTimeout(function(){ closeModelModal(); }, 150);
            });
        });
    }

    // ====================================================
    // 图片/素材上传
    // ====================================================
    function initImageUpload(){
        document.querySelectorAll('.gf-image-upload').forEach(function(upload){
            upload.addEventListener('click', function(e){
                // 如果点击的是删除按钮，不触发上传
                if(e.target.classList.contains('gf-upload-remove')){
                    resetUpload(upload);
                    return;
                }
                // 如果已有预览，不重复触发
                if(upload.querySelector('.gf-upload-preview')) return;

                var input = document.createElement('input');
                input.type = 'file';
                var acceptAttr = upload.getAttribute('data-accept');
                input.accept = acceptAttr || 'image/*';
                input.onchange = function(ev){
                    var file = ev.target.files[0];
                    if(file){
                        state.uploadedFile = file;
                        handleFileUpload(file, upload);
                    }
                };
                input.click();
            });
        });
    }

    function handleFileUpload(file, container){
        var isVideo = file.type.startsWith('video/');

        if(isVideo){
            // 视频预览：显示首帧 + 播放图标
            var video = document.createElement('video');
            video.preload = 'metadata';
            video.muted = true;
            video.onloadeddata = function(){
                var canvas = document.createElement('canvas');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0);
                var thumbUrl = canvas.toDataURL('image/jpeg');
                showPreview(container, thumbUrl, true);
                URL.revokeObjectURL(video.src);
            };
            video.src = URL.createObjectURL(file);
        } else {
            // 图片预览
            var reader = new FileReader();
            reader.onload = function(ev){
                showPreview(container, ev.target.result, false);
            };
            reader.readAsDataURL(file);
        }
    }

    function showPreview(container, src, isVideo){
        var html = '<div class="gf-upload-preview">';
        html += '<img src="' + src + '">';
        if(isVideo){
            html += '<span class="gf-video-play-icon"><i data-lucide="play" style="width:16px;height:16px"></i></span>';
        }
        html += '<button class="gf-upload-remove" title="删除">&times;</button>';
        html += '</div>';
        container.innerHTML = html;

        // 绑定删除按钮
        container.querySelector('.gf-upload-remove').addEventListener('click', function(e){
            e.stopPropagation();
            resetUpload(container);
        });
    }

    function resetUpload(container){
        state.uploadedFile = null;
        var isMedia = container.getAttribute('data-accept');
        var icon = isMedia ? '<i data-lucide="camera" style="width:32px;height:32px"></i><i data-lucide="video" style="width:20px;height:20px;margin-left:-8px"></i>' : '<i data-lucide="camera" style="width:32px;height:32px"></i>';
        var text = isMedia ? '点击上传图片或视频' : '点击上传参考图片';
        container.innerHTML = '<div class="gf-upload-placeholder"><span class="gf-upload-icon">' + icon + '</span><span class="gf-upload-text">' + text + '</span></div>';
    }

    // ====================================================
    // 生成按钮
    // ====================================================
    function initGenerateButton(){
        var btn = document.getElementById('generateBtn');
        if(btn){
            btn.addEventListener('click', function(){
                handleGenerate();
            });
        }
    }

    function handleGenerate(){
        var prompt = document.getElementById('promptInput');
        if(!prompt || !prompt.value.trim()){
            showToast('请输入提示词', 'warning');
            return;
        }
        var promptText = prompt.value.trim();
        if(promptText.length < 2){
            showToast('提示词至少2个字符', 'warning');
            return;
        }
        if(promptText.length > 2000){
            showToast('提示词不能超过2000个字符', 'warning');
            return;
        }

        // 检查是否已选择模型或模板
        var selectedTemplateId = 0;
        var activeTemplate = document.querySelector('.gt-item.active');
        if(activeTemplate){
            selectedTemplateId = parseInt(activeTemplate.getAttribute('data-template-id')) || 0;
        }

        if(!state.selectedModelId && !selectedTemplateId){
            showToast('请选择模型或模板', 'warning');
            return;
        }

        // 检查登录状态
        if(window.Auth && Auth.isChecked && Auth.isChecked() && !Auth.isLoggedIn()){
            if(typeof Auth.showLogin === 'function'){
                Auth.showLogin(function(){ handleGenerate(); });
            } else {
                showToast('请先登录后再生成', 'warning');
            }
            return;
        }

        // 显示加载状态
        var btn = document.getElementById('generateBtn');
        if(btn){
            btn.disabled = true;
            btn.innerHTML = '<span class="gf-submit-text">生成中...</span>';
        }

        // 如果有参考图需要上传，先上传后再提交
        if(state.uploadedFile){
            Api.uploadImage(state.uploadedFile, function(err, res){
                if(err || !res || res.status !== 1){
                    restoreGenerateBtn(btn);
                    showToast(res && res.msg ? res.msg : '图片上传失败', 'error');
                    return;
                }
                doSubmitGeneration(btn, promptText, selectedTemplateId, [res.url]);
            });
        } else {
            doSubmitGeneration(btn, promptText, selectedTemplateId, []);
        }
    }

    function doSubmitGeneration(btn, promptText, selectedTemplateId, refImageUrls){
        var postData = {
            prompt: promptText,
            generation_type: state.pageType === 'photo' ? 1 : 2,
            ratio: getPillValue('ratio'),
            quality: getPillValue('quality')
        };

        // 确定是模板驱动还是模型直选
        if(selectedTemplateId > 0){
            postData.template_id = selectedTemplateId;
        } else {
            postData.template_id = 0;
            postData.model_id = state.selectedModelId;
        }

        if(state.pageType === 'photo'){
            var countVal = getPillValue('count');
            if(countVal) postData.quantity = countVal;
        }

        if(refImageUrls && refImageUrls.length > 0){
            postData.ref_images = refImageUrls;
        }

        Api.createGenerationOrder(postData, function(err, res){
            restoreGenerateBtn(btn);

            if(err){
                showToast('网络错误，请重试', 'error');
                return;
            }

            if(!res || res.status !== 1){
                var msg = (res && res.msg) ? res.msg : '提交失败';
                // 未登录检测
                if(msg.indexOf('登录') > -1 || msg.indexOf('登陆') > -1){
                    if(window.Auth && typeof Auth.showLogin === 'function'){
                        Auth.showLogin(function(){ handleGenerate(); });
                    } else {
                        showToast(msg, 'warning');
                    }
                    return;
                }
                showToast(msg, 'error');
                return;
            }

            // 成功
            showToast('生成任务已提交！', 'success');

            // 如果需要支付，引导支付流程
            if(res.data && res.data.need_pay){
                showToast('请完成支付后查看生成结果', 'info');
            }
        });
    }

    function restoreGenerateBtn(btn){
        if(btn){
            btn.disabled = false;
            btn.innerHTML = '<span class="gf-submit-text">✨ 立即生成</span>';
        }
    }

    // ====================================================
    // 模板卡片点击
    // ====================================================
    function initTemplateClick(){
        document.querySelectorAll('.gt-item').forEach(function(item){
            item.addEventListener('click', function(){
                var templateId = this.getAttribute('data-template-id');
                // 切换选中态
                document.querySelectorAll('.gt-item').forEach(function(el){
                    el.classList.remove('active');
                });
                this.classList.add('active');
                if(templateId){
                    loadTemplateData(templateId);
                }
            });
        });
    }

    function loadTemplateData(templateId){
        // 选中模板时清空模型直选
        state.selectedModelId = null;
        state.selectedModelName = '';
        var card = document.getElementById('modelCard');
        if(card){
            var textEl = card.querySelector('.gf-model-card-text');
            if(textEl) textEl.textContent = '选择模型';
            card.classList.remove('selected');
        }
        showToast('已选择模板', 'info');
    }

    // ====================================================
    // 底部模板横排栏折叠/展开
    // ====================================================
    function initTemplateBarCollapse(){
        var bar = document.getElementById('templateBar');
        var toggle = document.getElementById('templateBarToggle');
        var closeBtn = document.getElementById('templateBarClose');

        if(!bar || !toggle) return;

        // 记录展开态自然高度
        bar.style.maxHeight = bar.scrollHeight + 'px';

        if(closeBtn){
            closeBtn.addEventListener('click', function(){
                state.templateBarCollapsed = true;
                bar.style.maxHeight = bar.scrollHeight + 'px'; // 设置当前高度以便过渡
                // 强制 reflow
                bar.offsetHeight;
                bar.classList.add('collapsed');
                toggle.classList.add('visible');
            });
        }

        var toggleBtn = toggle.querySelector('.template-bar-toggle-btn');
        if(toggleBtn){
            toggleBtn.addEventListener('click', function(){
                state.templateBarCollapsed = false;
                bar.classList.remove('collapsed');
                bar.style.maxHeight = bar.scrollHeight + 'px';
                toggle.classList.remove('visible');
            });
        }
    }

    // ====================================================
    // 模板横排栏左右滚动箭头
    // ====================================================
    function initTemplateBarScroll(){
        var body = document.getElementById('templateBarBody');
        var arrowLeft = document.getElementById('tplArrowLeft');
        var arrowRight = document.getElementById('tplArrowRight');

        if(!body) return;

        function updateArrows(){
            if(!arrowLeft || !arrowRight) return;
            var canScrollLeft = body.scrollLeft > 10;
            var canScrollRight = body.scrollLeft < body.scrollWidth - body.clientWidth - 10;
            arrowLeft.classList.toggle('visible', canScrollLeft);
            arrowRight.classList.toggle('visible', canScrollRight);
        }

        body.addEventListener('scroll', updateArrows);
        // 初始化检查
        setTimeout(updateArrows, 200);
        window.addEventListener('resize', updateArrows);

        if(arrowLeft){
            arrowLeft.addEventListener('click', function(){
                body.scrollBy({ left: -260, behavior: 'smooth' });
            });
        }
        if(arrowRight){
            arrowRight.addEventListener('click', function(){
                body.scrollBy({ left: 260, behavior: 'smooth' });
            });
        }

        // 触摸拖拽横向滚动
        enableTouchScroll(body);
    }

    // ====================================================
    // 全局点击关闭popover
    // ====================================================
    function initClickOutside(){
        document.addEventListener('click', function(){
            closeAllPopovers(null);
        });
    }

    // ====================================================
    // 工具函数
    // ====================================================

    /** 触摸拖拽横向滚动 */
    function enableTouchScroll(el){
        var isDown = false, startX, scrollLeft;
        el.addEventListener('mousedown', function(e){
            isDown = true;
            el.style.cursor = 'grabbing';
            startX = e.pageX - el.offsetLeft;
            scrollLeft = el.scrollLeft;
        });
        el.addEventListener('mouseleave', function(){ isDown = false; el.style.cursor = ''; });
        el.addEventListener('mouseup', function(){ isDown = false; el.style.cursor = ''; });
        el.addEventListener('mousemove', function(e){
            if(!isDown) return;
            e.preventDefault();
            var x = e.pageX - el.offsetLeft;
            el.scrollLeft = scrollLeft - (x - startX);
        });
    }

    /** Toast提示 */
    function showToast(message, type){
        type = type || 'info';
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

    /** HTML转义 */
    function escapeHtml(str){
        if(!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // 暴露给外部
    window.Generation = {
        loadTemplateData: loadTemplateData,
        showToast: showToast
    };
})();
