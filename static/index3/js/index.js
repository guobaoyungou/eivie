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
        cacheTimestamp: {} // 缓存时间戳，5分钟过期
    };

    document.addEventListener('DOMContentLoaded', function(){
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
                        '<span class="tms-price">' + (parseFloat(scene.base_price) > 0 ? '¥' + scene.base_price : '免费') + '</span>' +
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
                card.innerHTML =
                    '<img class="sc-cover" src="' + (item.cover_image || '/static/index3/img/placeholder.png') + '" alt="' + escapeHtml(item.template_name) + '" loading="lazy">' +
                    '<div class="sc-info">' +
                        '<div class="sc-name">' + escapeHtml(item.template_name) + '</div>' +
                        '<div class="sc-desc">' + escapeHtml(item.description || '') + '</div>' +
                        '<div class="sc-meta">' +
                            '<span class="sc-price">' + (parseFloat(item.base_price) > 0 ? '¥' + item.base_price : '免费') + '</span>' +
                            '<span class="sc-uses">' + (item.use_count || 0) + '次使用</span>' +
                        '</div>' +
                    '</div>';
                grid.appendChild(card);
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
                card.innerHTML =
                    '<img class="sc-cover" src="' + (item.cover_image || '/static/index3/img/placeholder.png') + '" alt="' + escapeHtml(item.template_name || item.model_name || '') + '" loading="lazy">' +
                    '<div class="sc-info">' +
                        '<div class="sc-name">' + escapeHtml(item.template_name || item.model_name || '') + '</div>' +
                        '<div class="sc-desc">' + escapeHtml(item.description || '') + '</div>' +
                    '</div>';
                grid.appendChild(card);
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

    // === Helpers ===
    function escapeHtml(str){
        if(!str) return '';
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // 暴露给外部
    window.Index3 = { 
        loadScenes: loadScenes, 
        doSearch: doSearch, 
        closeTaskModal: closeTaskModal, 
        openTaskModal: openTaskModal,
        showToast: showToast 
    };
})();
