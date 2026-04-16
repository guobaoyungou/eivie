/**
 * short_drama.js — 短剧创作前端交互 v1.0
 * 包含：项目列表页 + 创作画布页
 */
;(function(){
'use strict';

var SD = window.ShortDrama = {};

// ==================== 工具函数 ====================
var API_BASE = '';

function ajax(method, url, data, onSuccess, onError) {
    var xhr = new XMLHttpRequest();
    xhr.open(method, API_BASE + url, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    if (method === 'POST') {
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    }
    xhr.onreadystatechange = function(){
        if(xhr.readyState === 4){
            if(xhr.status === 200){
                try {
                    var res = JSON.parse(xhr.responseText);
                    onSuccess && onSuccess(res);
                } catch(e){
                    onError && onError('解析响应失败');
                }
            } else {
                onError && onError('请求失败: ' + xhr.status);
            }
        }
    };
    if(method === 'POST' && data){
        var params = [];
        for(var k in data){ if(data.hasOwnProperty(k)) params.push(encodeURIComponent(k)+'='+encodeURIComponent(data[k])); }
        xhr.send(params.join('&'));
    } else {
        xhr.send();
    }
}

function showToast(msg, type) {
    if(window.Index3 && Index3.showToast){
        Index3.showToast(msg, type || 'info');
    } else {
        alert(msg);
    }
}

function formatTime(ts) {
    if(!ts) return '-';
    var d = new Date(ts * 1000);
    return d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate())+' '+pad(d.getHours())+':'+pad(d.getMinutes());
}

function pad(n){ return n<10?'0'+n:n; }

var statusMap = {
    draft: '草稿', running: '生成中', completed: '已完成', failed: '失败'
};

// ==================== 项目列表页 ====================
SD.projectList = {
    page: 1,
    limit: 12,
    total: 0,
    filters: { status: '', creation_mode: '', keyword: '' },
    
    init: function(){
        this.bindEvents();
        this.loadProjects();
        this.loadPresetTemplates();
    },

    bindEvents: function(){
        var self = this;
        // 创建项目按钮
        var createBtn = document.getElementById('sdCreateBtn');
        if(createBtn) createBtn.addEventListener('click', function(){ self.showCreateModal(); });
        
        var oneclickBtn = document.getElementById('sdOneclickBtn');
        if(oneclickBtn) oneclickBtn.addEventListener('click', function(){ self.showOneclickModal(); });

        // 筛选
        var statusFilter = document.getElementById('sdStatusFilter');
        if(statusFilter) statusFilter.addEventListener('change', function(){ self.filters.status = this.value; self.page = 1; self.loadProjects(); });

        var genreFilter = document.getElementById('sdGenreFilter');
        if(genreFilter) genreFilter.addEventListener('change', function(){ self.filters.genre = this.value; self.page = 1; self.loadProjects(); });

        // 搜索
        var searchInput = document.getElementById('sdSearchInput');
        var searchTimer;
        if(searchInput) searchInput.addEventListener('input', function(){
            clearTimeout(searchTimer);
            var val = this.value;
            searchTimer = setTimeout(function(){ self.filters.keyword = val; self.page = 1; self.loadProjects(); }, 400);
        });

        // 模式选择
        document.querySelectorAll('.sd-mode-card').forEach(function(card){
            card.addEventListener('click', function(){
                document.querySelectorAll('.sd-mode-card').forEach(function(c){ c.classList.remove('active'); });
                this.classList.add('active');
            });
        });

        // 弹窗关闭
        document.querySelectorAll('.sd-modal-close, .sd-modal-cancel').forEach(function(btn){
            btn.addEventListener('click', function(){
                var overlay = this.closest('.sd-modal-overlay');
                if(overlay) overlay.classList.remove('show');
            });
        });
    },

    loadProjects: function(){
        var self = this;
        var grid = document.getElementById('sdProjectGrid');
        if(!grid) return;
        grid.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-tertiary)">加载中...</div>';

        var params = '?page='+this.page+'&limit='+this.limit;
        if(this.filters.status) params += '&status='+this.filters.status;
        if(this.filters.keyword) params += '&keyword='+encodeURIComponent(this.filters.keyword);

        ajax('GET', '/?s=/Workflow/project_list'+params, null, function(res){
            if(res.code === 0 && res.data){
                self.total = res.count || 0;
                self.renderProjects(res.data);
                self.renderPagination();
            } else {
                grid.innerHTML = '<div class="sd-empty"><i data-lucide="folder-open"></i><p>暂无短剧项目</p><button class="sd-btn primary" onclick="ShortDrama.projectList.showCreateModal()"><i data-lucide="plus"></i> 创建第一个项目</button></div>';
                if(typeof lucide!=='undefined') lucide.createIcons();
            }
        }, function(err){
            grid.innerHTML = '<div class="sd-empty"><p>加载失败，请刷新重试</p></div>';
        });
    },

    renderProjects: function(list){
        var grid = document.getElementById('sdProjectGrid');
        if(!list || !list.length){
            grid.innerHTML = '<div class="sd-empty"><i data-lucide="folder-open"></i><p>暂无短剧项目</p><button class="sd-btn primary" onclick="ShortDrama.projectList.showCreateModal()"><i data-lucide="plus"></i> 创建第一个项目</button></div>';
            if(typeof lucide!=='undefined') lucide.createIcons();
            return;
        }
        var html = '';
        list.forEach(function(item){
            var statusText = statusMap[item.status] || item.status;
            var genre = item.genre || '';
            var coverHtml = item.output_video_url
                ? '<img src="'+item.output_video_url+'" alt="'+item.title+'">'
                : '<div class="sd-cover-placeholder"><i data-lucide="film"></i></div>';
            
            html += '<div class="sd-project-card" data-id="'+item.id+'" onclick="ShortDrama.projectList.openProject('+item.id+')">'
                + '<div class="sd-card-cover">'+coverHtml+'<span class="sd-card-status '+item.status+'">'+statusText+'</span></div>'
                + '<div class="sd-card-body">'
                + '<div class="sd-card-title">'+item.title+'</div>'
                + '<div class="sd-card-meta">'
                + (genre ? '<span class="sd-card-genre">'+genre+'</span>' : '')
                + '<span>'+formatTime(item.create_time)+'</span>'
                + '</div>'
                + '<div class="sd-card-actions" onclick="event.stopPropagation()">'
                + '<button class="sd-card-action" onclick="ShortDrama.projectList.openProject('+item.id+')" title="编辑"><i data-lucide="pencil"></i> 编辑</button>'
                + '<button class="sd-card-action" onclick="ShortDrama.projectList.duplicateProject('+item.id+')" title="复制"><i data-lucide="copy"></i> 复制</button>'
                + '<button class="sd-card-action danger" onclick="ShortDrama.projectList.deleteProject('+item.id+')" title="删除"><i data-lucide="trash-2"></i> 删除</button>'
                + '</div></div></div>';
        });
        grid.innerHTML = html;
        if(typeof lucide!=='undefined') lucide.createIcons();
    },

    renderPagination: function(){
        var pag = document.getElementById('sdPagination');
        if(!pag) return;
        var totalPages = Math.ceil(this.total / this.limit);
        if(totalPages <= 1){ pag.innerHTML = ''; return; }
        var html = '<button '+(this.page<=1?'disabled':'')+' onclick="ShortDrama.projectList.goPage('+(this.page-1)+')">上一页</button>';
        for(var i=1;i<=totalPages;i++){
            html += '<button class="'+(i===this.page?'active':'')+'" onclick="ShortDrama.projectList.goPage('+i+')">'+i+'</button>';
        }
        html += '<button '+(this.page>=totalPages?'disabled':'')+' onclick="ShortDrama.projectList.goPage('+(this.page+1)+')">下一页</button>';
        pag.innerHTML = html;
    },

    goPage: function(p){
        this.page = p;
        this.loadProjects();
    },

    openProject: function(id){
        window.location.href = '/?s=/Index/short_drama_canvas&project_id='+id;
    },

    showCreateModal: function(){
        var modal = document.getElementById('sdCreateModal');
        if(modal) modal.classList.add('show');
    },

    showOneclickModal: function(){
        var modal = document.getElementById('sdOneclickModal');
        if(modal) modal.classList.add('show');
    },

    submitCreate: function(){
        var title = document.getElementById('sdProjectTitle');
        if(!title || !title.value.trim()){ showToast('请输入项目标题', 'warning'); return; }

        var modeCard = document.querySelector('#sdCreateModal .sd-mode-card.active');
        var mode = modeCard ? modeCard.dataset.mode : 'canvas';

        var data = {
            title: title.value.trim(),
            description: (document.getElementById('sdProjectDesc') || {}).value || '',
            genre: (document.getElementById('sdProjectGenre') || {}).value || '',
            creation_mode: mode
        };

        ajax('POST', '/?s=/Workflow/project_save', data, function(res){
            if(res.status === 1){
                showToast('项目创建成功', 'success');
                document.getElementById('sdCreateModal').classList.remove('show');
                if(mode === 'canvas'){
                    window.location.href = '/?s=/Index/short_drama_canvas&project_id='+res.data.id;
                } else {
                    SD.projectList.loadProjects();
                }
            } else {
                showToast(res.msg || '创建失败', 'error');
            }
        });
    },

    submitOneclick: function(){
        var creativity = (document.getElementById('sdOneclickCreativity') || {}).value || '';
        var genre = (document.getElementById('sdOneclickGenre') || {}).value || '';
        var episodes = (document.getElementById('sdOneclickEpisodes') || {}).value || '1';
        var duration = (document.getElementById('sdOneclickDuration') || {}).value || '60';
        var templateId = 0;
        var activeTemplate = document.querySelector('.sd-template-card.active');
        if(activeTemplate) templateId = activeTemplate.dataset.id || 0;

        if(!creativity.trim() && !templateId){
            showToast('请输入创意描述或选择一个预设模板', 'warning');
            return;
        }

        var data = {
            creativity: creativity,
            genre: genre,
            episodes: episodes,
            duration: duration,
            template_id: templateId
        };

        var btn = document.getElementById('sdOneclickSubmit');
        if(btn){ btn.disabled = true; btn.textContent = '正在生成...'; }

        ajax('POST', '/?s=/Workflow/oneclick_generate', data, function(res){
            if(btn){ btn.disabled = false; btn.textContent = '开始生成'; }
            if(res.status === 1){
                showToast('短剧生成已启动！', 'success');
                document.getElementById('sdOneclickModal').classList.remove('show');
                window.location.href = '/?s=/Index/short_drama_canvas&project_id='+res.data.id;
            } else {
                showToast(res.msg || '生成失败', 'error');
            }
        }, function(err){
            if(btn){ btn.disabled = false; btn.textContent = '开始生成'; }
            showToast('网络错误，请重试', 'error');
        });
    },

    loadPresetTemplates: function(){
        var grid = document.getElementById('sdPresetTemplateGrid');
        if(!grid) return;
        
        ajax('GET', '/?s=/Workflow/preset_template_list', null, function(res){
            if(res.status === 1 && res.data && res.data.length){
                var html = '';
                res.data.forEach(function(tpl){
                    html += '<div class="sd-template-card" data-id="'+tpl.id+'" onclick="ShortDrama.projectList.selectTemplate(this)">'
                        + '<div class="tpl-title">'+tpl.title+'</div>'
                        + '<div class="tpl-genre">'+(tpl.genre||'通用')+'</div>'
                        + '</div>';
                });
                grid.innerHTML = html;
            }
        });
    },

    selectTemplate: function(el){
        document.querySelectorAll('.sd-template-card').forEach(function(c){ c.classList.remove('active'); });
        el.classList.add('active');
    },

    duplicateProject: function(id){
        if(!confirm('确定要复制此项目？')) return;
        ajax('POST', '/?s=/Workflow/project_duplicate', {id:id}, function(res){
            if(res.status === 1){
                showToast('复制成功', 'success');
                SD.projectList.loadProjects();
            } else {
                showToast(res.msg || '复制失败', 'error');
            }
        });
    },

    deleteProject: function(id){
        if(!confirm('确定要删除此项目？此操作不可撤销。')) return;
        ajax('POST', '/?s=/Workflow/project_delete', {id:id}, function(res){
            if(res.status === 1){
                showToast('删除成功', 'success');
                SD.projectList.loadProjects();
            } else {
                showToast(res.msg || '删除失败', 'error');
            }
        });
    }
};

// ==================== 画布页 ====================
SD.canvas = {
    projectId: 0,
    projectData: null,
    nodes: [],
    edges: [],
    selectedNodeId: null,
    pollTimer: null,

    init: function(projectId){
        this.projectId = projectId;
        this.bindEvents();
        if(projectId){
            this.loadProject();
        }
    },

    bindEvents: function(){
        var self = this;

        // 保存按钮
        var saveBtn = document.getElementById('sdSaveBtn');
        if(saveBtn) saveBtn.addEventListener('click', function(){ self.saveProject(); });

        // 运行按钮
        var runBtn = document.getElementById('sdRunBtn');
        if(runBtn) runBtn.addEventListener('click', function(){ self.runWorkflow(); });

        // 停止按钮
        var stopBtn = document.getElementById('sdStopBtn');
        if(stopBtn) stopBtn.addEventListener('click', function(){ self.stopWorkflow(); });

        // 导出按钮
        var exportBtn = document.getElementById('sdExportBtn');
        if(exportBtn) exportBtn.addEventListener('click', function(){ self.exportVideo(); });

        // 返回按钮
        var backBtn = document.getElementById('sdBackBtn');
        if(backBtn) backBtn.addEventListener('click', function(){ window.location.href = '/?s=/Index/short_drama'; });

        // 节点面板拖拽
        document.querySelectorAll('.sd-node-type').forEach(function(nt){
            nt.addEventListener('dblclick', function(){
                self.addNode(this.dataset.type, this.dataset.label);
            });
        });
    },

    loadProject: function(){
        var self = this;
        ajax('GET', '/?s=/Workflow/project_detail&id='+this.projectId, null, function(res){
            if(res.status === 1 && res.data){
                self.projectData = res.data;
                self.nodes = res.data.nodes || [];
                self.edges = res.data.edges || [];
                self.renderProjectInfo();
                self.renderCanvas();

                if(res.data.status === 'running'){
                    self.startPolling();
                }
            } else {
                showToast(res.msg || '加载项目失败', 'error');
            }
        });
    },

    renderProjectInfo: function(){
        var nameInput = document.getElementById('sdProjectNameInput');
        if(nameInput && this.projectData){
            nameInput.value = this.projectData.title || '未命名项目';
        }
    },

    renderCanvas: function(){
        var container = document.getElementById('sdCanvasInner');
        if(!container) return;

        // Clear
        container.querySelectorAll('.sd-canvas-node').forEach(function(n){ n.remove(); });

        var self = this;
        var nodeTypeIcons = {
            script: 'file-text', character: 'user-round', storyboard: 'layout-grid',
            video: 'video', voice: 'mic', compose: 'layers'
        };
        var nodeTypeLabels = {
            script: '剧本', character: '角色', storyboard: '分镜',
            video: '视频', voice: '配音', compose: '合成'
        };

        // Default layout positions
        var defaultPositions = {
            script: {x: 80, y: 120},
            character: {x: 340, y: 50},
            storyboard: {x: 340, y: 220},
            voice: {x: 340, y: 380},
            video: {x: 600, y: 150},
            compose: {x: 860, y: 200}
        };

        this.nodes.forEach(function(node, idx){
            var el = document.createElement('div');
            el.className = 'sd-canvas-node' + (node.status ? ' ' + node.status : '');
            el.dataset.id = node.id;
            
            var pos = node.position || defaultPositions[node.node_type] || {x: 100 + idx*220, y: 120};
            el.style.left = pos.x + 'px';
            el.style.top = pos.y + 'px';

            var icon = nodeTypeIcons[node.node_type] || 'box';
            var label = node.node_label || nodeTypeLabels[node.node_type] || node.node_type;
            var statusDotClass = node.status || 'pending';

            el.innerHTML = '<div class="sd-node-header">'
                + '<span class="node-icon nt-icon ' + node.node_type + '"><i data-lucide="'+icon+'"></i></span>'
                + '<span class="node-label">'+label+'</span>'
                + '<span class="node-status-dot '+statusDotClass+'"></span>'
                + '</div>'
                + '<div class="sd-node-body">'+(node.node_type === 'script' ? '创意输入 → 剧本输出' : label+'节点')+'</div>'
                + '<div class="sd-node-ports">'
                + '<span class="sd-port input" data-port="in"></span>'
                + '<span class="sd-port output" data-port="out"></span>'
                + '</div>';

            el.addEventListener('click', function(e){
                e.stopPropagation();
                self.selectNode(node.id);
            });

            container.appendChild(el);
        });

        this.renderEdges();
        if(typeof lucide!=='undefined') lucide.createIcons();
    },

    renderEdges: function(){
        var svg = document.getElementById('sdCanvasSvg');
        if(!svg) return;
        svg.innerHTML = '';

        var container = document.getElementById('sdCanvasInner');
        if(!container) return;

        this.edges.forEach(function(edge){
            var sourceEl = container.querySelector('.sd-canvas-node[data-id="'+edge.source_node_id+'"]');
            var targetEl = container.querySelector('.sd-canvas-node[data-id="'+edge.target_node_id+'"]');
            if(!sourceEl || !targetEl) return;

            var sx = sourceEl.offsetLeft + sourceEl.offsetWidth;
            var sy = sourceEl.offsetTop + sourceEl.offsetHeight / 2;
            var tx = targetEl.offsetLeft;
            var ty = targetEl.offsetTop + targetEl.offsetHeight / 2;
            var mx = (sx + tx) / 2;

            var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', 'M'+sx+','+sy+' C'+mx+','+sy+' '+mx+','+ty+' '+tx+','+ty);
            svg.appendChild(path);
        });
    },

    selectNode: function(nodeId){
        this.selectedNodeId = nodeId;
        document.querySelectorAll('.sd-canvas-node').forEach(function(n){ n.classList.remove('selected'); });
        var el = document.querySelector('.sd-canvas-node[data-id="'+nodeId+'"]');
        if(el) el.classList.add('selected');

        this.renderConfigPanel(nodeId);
    },

    renderConfigPanel: function(nodeId){
        var panel = document.getElementById('sdConfigContent');
        if(!panel) return;
        
        var node = this.nodes.find(function(n){ return n.id == nodeId; });
        if(!node){
            panel.innerHTML = '<div class="sd-config-panel-empty"><i data-lucide="mouse-pointer-2"></i><p>点击节点查看配置</p></div>';
            if(typeof lucide!=='undefined') lucide.createIcons();
            return;
        }

        var config = node.config_params || {};
        var html = '<div class="sd-config-section">'
            + '<div class="sd-config-section-title">节点信息</div>'
            + '<div class="sd-form-group"><label class="sd-form-label">节点名称</label>'
            + '<input class="sd-form-input" value="'+(node.node_label||'')+'" onchange="ShortDrama.canvas.updateNodeLabel('+nodeId+',this.value)"></div>'
            + '</div>';

        // 模型选择（所有节点都支持）
        if(node.node_type !== 'compose'){
            html += '<div class="sd-config-section">'
                + '<div class="sd-config-section-title">AI模型选择</div>'
                + '<div class="sd-form-group"><label class="sd-form-label">选择模型</label>'
                + '<select class="sd-form-select" id="sdNodeModel" onchange="ShortDrama.canvas.updateNodeModel('+nodeId+',this.value)">'
                + '<option value="">自动选择</option>'
                + '</select></div></div>';
        }

        // 节点特有参数
        html += '<div class="sd-config-section">'
            + '<div class="sd-config-section-title">参数配置</div>';

        switch(node.node_type){
            case 'script':
                html += '<div class="sd-form-group"><label class="sd-form-label">创意描述</label>'
                    + '<textarea class="sd-form-textarea" placeholder="描述您的短剧创意...">'+(config.creativity||'')+'</textarea></div>'
                    + '<div class="sd-form-group"><label class="sd-form-label">题材</label>'
                    + '<select class="sd-form-select"><option value="">请选择</option><option value="甜宠">甜宠</option><option value="悬疑">悬疑</option><option value="都市">都市</option><option value="古风">古风</option><option value="科幻">科幻</option></select></div>'
                    + '<div class="sd-form-group"><label class="sd-form-label">集数</label>'
                    + '<input class="sd-form-input" type="number" value="'+(config.episodes||1)+'" min="1" max="20"></div>'
                    + '<div class="sd-form-group"><label class="sd-form-label">时长(秒/集)</label>'
                    + '<input class="sd-form-input" type="number" value="'+(config.duration||60)+'" min="30" max="300"></div>';
                break;
            case 'character':
                html += '<div class="sd-form-group"><label class="sd-form-label">风格</label>'
                    + '<select class="sd-form-select"><option value="realistic">写实</option><option value="anime">动漫</option><option value="3d">3D</option></select></div>';
                break;
            case 'storyboard':
                html += '<div class="sd-form-group"><label class="sd-form-label">分辨率</label>'
                    + '<select class="sd-form-select"><option value="720p">720P</option><option value="1080p">1080P</option></select></div>';
                break;
            case 'video':
                html += '<div class="sd-form-group"><label class="sd-form-label">时长(秒)</label>'
                    + '<input class="sd-form-input" type="number" value="'+(config.clip_duration||5)+'" min="3" max="10"></div>'
                    + '<div class="sd-form-group"><label class="sd-form-label">生成模式</label>'
                    + '<select class="sd-form-select"><option value="first_frame">首帧生成</option><option value="first_last_frame">首尾帧生成</option><option value="motion">运动模式</option></select></div>';
                break;
            case 'voice':
                html += '<div class="sd-form-group"><label class="sd-form-label">语速</label>'
                    + '<input class="sd-form-input" type="range" min="0.5" max="2" step="0.1" value="'+(config.speed||1)+'"></div>'
                    + '<div class="sd-form-group"><label class="sd-form-label">合成模式</label>'
                    + '<select class="sd-form-select"><option value="design">音色设计</option><option value="clone">音色克隆</option></select></div>';
                break;
            case 'compose':
                html += '<div class="sd-form-group"><label class="sd-form-label">转场效果</label>'
                    + '<select class="sd-form-select"><option value="none">无</option><option value="fade">淡入淡出</option><option value="slide">滑动</option><option value="zoom">缩放</option></select></div>'
                    + '<div class="sd-form-group"><label class="sd-form-label">字幕样式</label>'
                    + '<select class="sd-form-select"><option value="bottom">底部字幕</option><option value="top">顶部字幕</option><option value="none">无字幕</option></select></div>';
                break;
        }

        html += '</div>';

        // 输出预览
        if(node.output_data){
            html += '<div class="sd-config-section">'
                + '<div class="sd-config-section-title">输出预览</div>'
                + '<div class="sd-node-output-preview" style="font-size:12px;color:var(--text-secondary);word-break:break-all;max-height:200px;overflow-y:auto">'
                + '<pre style="white-space:pre-wrap">'+JSON.stringify(node.output_data, null, 2).substring(0, 500)+'</pre>'
                + '</div></div>';
        }

        // 执行按钮
        html += '<div style="padding-top:12px">'
            + '<button class="sd-btn primary" style="width:100%" onclick="ShortDrama.canvas.executeNode('+nodeId+')">'
            + '<i data-lucide="play"></i> 执行此节点</button></div>';

        panel.innerHTML = html;
        if(typeof lucide!=='undefined') lucide.createIcons();

        // Load models for this node type
        if(node.node_type !== 'compose'){
            this.loadNodeModels(node);
        }
    },

    loadNodeModels: function(node){
        var select = document.getElementById('sdNodeModel');
        if(!select) return;

        var typeMap = { script: 'llm', character: 'image', storyboard: 'image', video: 'video', voice: 'tts' };
        var modelType = typeMap[node.node_type] || '';

        ajax('GET', '/?s=/Index/model_list&limit=50', null, function(res){
            if(res.code === 0 && res.data){
                res.data.forEach(function(m){
                    var opt = document.createElement('option');
                    opt.value = m.id;
                    opt.textContent = m.model_name + (m.provider_name ? ' ('+m.provider_name+')' : '');
                    if(node.model_id && node.model_id == m.id) opt.selected = true;
                    select.appendChild(opt);
                });
            }
        });
    },

    addNode: function(type, label){
        var self = this;
        ajax('POST', '/?s=/Workflow/node_add', {
            project_id: this.projectId,
            node_type: type,
            node_label: label || type
        }, function(res){
            if(res.status === 1){
                showToast('节点已添加', 'success');
                self.loadProject();
            } else {
                showToast(res.msg || '添加失败', 'error');
            }
        });
    },

    updateNodeLabel: function(nodeId, label){
        ajax('POST', '/?s=/Workflow/node_update', {id: nodeId, node_label: label}, function(res){
            if(res.status !== 1) showToast(res.msg || '更新失败', 'error');
        });
    },

    updateNodeModel: function(nodeId, modelId){
        ajax('POST', '/?s=/Workflow/node_update', {id: nodeId, model_id: modelId}, function(res){
            if(res.status === 1){
                showToast('模型已更新', 'success');
            } else {
                showToast(res.msg || '更新失败', 'error');
            }
        });
    },

    executeNode: function(nodeId){
        var self = this;
        ajax('POST', '/?s=/Workflow/node_execute', {id: nodeId}, function(res){
            if(res.status === 1){
                showToast('节点执行完成', 'success');
                self.loadProject();
            } else if(res.status === 2){
                showToast('异步任务已提交，正在处理...', 'info');
                self.startPolling();
            } else {
                showToast(res.msg || '执行失败', 'error');
            }
        });
    },

    saveProject: function(){
        var nameInput = document.getElementById('sdProjectNameInput');
        var title = nameInput ? nameInput.value.trim() : '';
        if(!title){ showToast('请输入项目名称', 'warning'); return; }

        ajax('POST', '/?s=/Workflow/project_save', {
            id: this.projectId,
            title: title
        }, function(res){
            if(res.status === 1){
                showToast('保存成功', 'success');
            } else {
                showToast(res.msg || '保存失败', 'error');
            }
        });
    },

    runWorkflow: function(){
        var self = this;
        ajax('POST', '/?s=/Workflow/run', {project_id: this.projectId}, function(res){
            if(res.status === 1){
                showToast('工作流已启动', 'success');
                self.startPolling();
            } else {
                showToast(res.msg || '启动失败', 'error');
            }
        });
    },

    stopWorkflow: function(){
        var self = this;
        ajax('POST', '/?s=/Workflow/stop', {project_id: this.projectId}, function(res){
            if(res.status === 1){
                showToast('工作流已停止', 'info');
                self.stopPolling();
                self.loadProject();
            } else {
                showToast(res.msg || '停止失败', 'error');
            }
        });
    },

    exportVideo: function(){
        if(this.projectData && this.projectData.output_video_url){
            window.open(this.projectData.output_video_url, '_blank');
        } else {
            showToast('成片尚未生成', 'warning');
        }
    },

    startPolling: function(){
        var self = this;
        this.stopPolling();
        this.pollTimer = setInterval(function(){
            ajax('GET', '/?s=/Workflow/progress&project_id='+self.projectId, null, function(res){
                if(res.status === 1 && res.data){
                    self.updateProgress(res.data);
                    if(res.data.project_status === 'completed' || res.data.project_status === 'failed'){
                        self.stopPolling();
                        self.loadProject();
                        if(res.data.project_status === 'completed'){
                            showToast('短剧生成完成！', 'success');
                        }
                    }
                }
            });
        }, 3000);
    },

    stopPolling: function(){
        if(this.pollTimer){ clearInterval(this.pollTimer); this.pollTimer = null; }
    },

    updateProgress: function(data){
        var fill = document.getElementById('sdProgressFill');
        var text = document.getElementById('sdProgressText');
        var pct = data.progress || 0;
        if(fill) fill.style.width = pct + '%';
        if(text) text.textContent = Math.round(pct) + '%';

        // Update node statuses
        if(data.nodes){
            data.nodes.forEach(function(n){
                var el = document.querySelector('.sd-canvas-node[data-id="'+n.id+'"]');
                if(el){
                    el.className = 'sd-canvas-node ' + (n.status || 'pending');
                    var dot = el.querySelector('.node-status-dot');
                    if(dot) dot.className = 'node-status-dot ' + (n.status || 'pending');
                }
            });
        }
    }
};

})();
