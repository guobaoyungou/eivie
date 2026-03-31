/**
 * 艺为微信大屏互动 - 管理后台应用逻辑
 */
(function() {
    'use strict';

    // 登录检查
    if (!HdApi.isLoggedIn()) {
        window.location.href = '/login';
        return;
    }

    // 初始化用户名显示
    document.getElementById('topbarUser').textContent = '👤 ' + HdApi.getName();

    // 初始加载仪表盘
    loadDashboard();
})();

var currentPage = 'dashboard';

function switchPage(page) {
    // 隐藏所有页面
    var pages = document.querySelectorAll('[id^="page-"]');
    for (var i = 0; i < pages.length; i++) {
        pages[i].style.display = 'none';
    }
    document.getElementById('page-' + page).style.display = 'block';

    // 更新导航
    var navItems = document.querySelectorAll('.nav-item');
    for (var i = 0; i < navItems.length; i++) {
        navItems[i].classList.remove('active');
        if (navItems[i].getAttribute('data-page') === page) {
            navItems[i].classList.add('active');
        }
    }

    // 更新标题
    var titles = {dashboard: '仪表盘', stores: '门店管理', activities: '活动管理', settings: '账户设置', 'activity-detail': '活动管理'};
    document.getElementById('pageTitle').textContent = titles[page] || page;

    currentPage = page;

    // 加载数据
    if (page === 'dashboard') loadDashboard();
    else if (page === 'stores') loadStores();
    else if (page === 'activities') loadActivities();
    else if (page === 'settings') loadSettings();
    else if (page === 'activity-detail') { /* loaded by manageActivity() */ }
}

// ============ Dashboard ============
function loadDashboard() {
    HdApi.getProfile().then(function(res) {
        if (res.code === 0 && res.data) {
            var d = res.data;
            var plan = d.plan || {};
            var html = '';
            html += '<div class="stat-card"><div class="stat-icon blue">🏪</div><div class="stat-info"><div class="label">门店上限</div><div class="value">' + (plan.max_stores || '-') + '</div></div></div>';
            html += '<div class="stat-card"><div class="stat-icon green">🎯</div><div class="stat-info"><div class="label">活动上限</div><div class="value">' + (plan.max_activities || '-') + '</div></div></div>';
            html += '<div class="stat-card"><div class="stat-icon orange">👥</div><div class="stat-info"><div class="label">单活动人数上限</div><div class="value">' + (plan.max_participants || '-') + '</div></div></div>';
            html += '<div class="stat-card"><div class="stat-icon purple">📦</div><div class="stat-info"><div class="label">套餐状态</div><div class="value">' + (plan.is_valid ? '<span style="color:#059669">有效</span>' : '<span style="color:#dc2626">已过期</span>') + '</div></div></div>';
            document.getElementById('dashStats').innerHTML = html;
        }
    });

    HdApi.getActivities(1).then(function(res) {
        if (res.code === 0 && res.data) {
            var list = res.data.list || [];
            if (list.length === 0) {
                document.getElementById('dashActivities').innerHTML = '<tr><td colspan="5"><div class="empty-state"><div class="icon">🎯</div><p>还没有活动，快去创建第一个吧！</p><br><button class="btn btn-primary" onclick="switchPage(\'activities\')">创建活动</button></div></td></tr>';
                return;
            }
            var html = '';
            for (var i = 0; i < Math.min(list.length, 5); i++) {
                var a = list[i];
                html += '<tr>';
                html += '<td>' + a.title + '</td>';
                html += '<td><code style="background:#eef2ff;padding:2px 8px;border-radius:4px;color:#6366f1">' + a.access_code + '</code></td>';
                html += '<td>' + (a.participant_count || 0) + '</td>';
                html += '<td>' + statusBadge(a.status) + '</td>';
                html += '<td>' + formatTime(a.createtime) + '</td>';
                html += '</tr>';
            }
            document.getElementById('dashActivities').innerHTML = html;
        }
    });
}

// ============ Stores ============
function loadStores() {
    HdApi.getStores(1).then(function(res) {
        if (res.code === 0 && res.data) {
            var list = res.data.list || [];
            if (list.length === 0) {
                document.getElementById('storeList').innerHTML = '<tr><td colspan="5"><div class="empty-state"><div class="icon">🏪</div><p>还没有门店</p></div></td></tr>';
                return;
            }
            var html = '';
            for (var i = 0; i < list.length; i++) {
                var s = list[i];
                html += '<tr>';
                html += '<td>' + s.id + '</td>';
                html += '<td>' + (s.name || '') + '</td>';
                html += '<td>' + (s.address || '-') + '</td>';
                html += '<td>' + (s.tel || '-') + '</td>';
                html += '<td>';
                html += '<button class="btn btn-default btn-sm" onclick="editStore(' + s.id + ')">编辑</button> ';
                html += '<button class="btn btn-danger btn-sm" onclick="delStore(' + s.id + ')">删除</button>';
                html += '</td></tr>';
            }
            document.getElementById('storeList').innerHTML = html;
        }
    });
}

function showStoreModal(id) {
    document.getElementById('storeEditId').value = '';
    document.getElementById('storeFormName').value = '';
    document.getElementById('storeFormAddr').value = '';
    document.getElementById('storeFormTel').value = '';
    document.getElementById('storeModalTitle').textContent = '新建门店';
    document.getElementById('storeModal').classList.add('show');
}

function editStore(id) {
    HdApi.getStore(id).then(function(res) {
        if (res.code === 0 && res.data) {
            document.getElementById('storeEditId').value = res.data.id;
            document.getElementById('storeFormName').value = res.data.name || '';
            document.getElementById('storeFormAddr').value = res.data.address || '';
            document.getElementById('storeFormTel').value = res.data.tel || '';
            document.getElementById('storeModalTitle').textContent = '编辑门店';
            document.getElementById('storeModal').classList.add('show');
        }
    });
}

function saveStore() {
    var id = document.getElementById('storeEditId').value;
    var data = {
        name: document.getElementById('storeFormName').value,
        address: document.getElementById('storeFormAddr').value,
        tel: document.getElementById('storeFormTel').value
    };
    if (!data.name) { showToast('请输入门店名称', 'error'); return; }

    var p = id ? HdApi.updateStore(id, data) : HdApi.createStore(data);
    p.then(function(res) {
        if (res.code === 0) {
            showToast(id ? '修改成功' : '创建成功');
            closeModal('storeModal');
            loadStores();
        } else {
            showToast(res.msg || '操作失败', 'error');
        }
    });
}

function delStore(id) {
    if (!confirm('确定要删除该门店吗？')) return;
    HdApi.deleteStore(id).then(function(res) {
        if (res.code === 0) { showToast('删除成功'); loadStores(); }
        else showToast(res.msg || '删除失败', 'error');
    });
}

// ============ Activities ============
function loadActivities() {
    HdApi.getActivities(1).then(function(res) {
        if (res.code === 0 && res.data) {
            var list = res.data.list || [];
            if (list.length === 0) {
                document.getElementById('actList').innerHTML = '<tr><td colspan="8"><div class="empty-state"><div class="icon">🎯</div><p>还没有活动</p></div></td></tr>';
                return;
            }
            var html = '';
            for (var i = 0; i < list.length; i++) {
                var a = list[i];
                html += '<tr>';
                html += '<td>' + a.id + '</td>';
                html += '<td>' + a.title + '</td>';
                html += '<td><code style="background:#eef2ff;padding:2px 8px;border-radius:4px;color:#6366f1;cursor:pointer" onclick="copyCode(\'' + a.access_code + '\')" title="点击复制">' + a.access_code + '</code></td>';
                html += '<td>' + (a.participant_count || 0) + '</td>';
                html += '<td>' + (a.signed_count || 0) + '</td>';
                html += '<td>' + statusBadge(a.status) + '</td>';
                html += '<td>' + formatTime(a.createtime) + '</td>';
                html += '<td>';
                html += '<button class="btn btn-primary btn-sm" onclick="manageActivity(' + a.id + ')">管理</button> ';
                html += '<button class="btn btn-default btn-sm" onclick="viewActivity(' + a.id + ')">详情</button> ';
                html += '<button class="btn btn-default btn-sm" onclick="editActivity(' + a.id + ')">编辑</button> ';
                html += '<button class="btn btn-danger btn-sm" onclick="delActivity(' + a.id + ')">删除</button>';
                html += '</td></tr>';
            }
            document.getElementById('actList').innerHTML = html;
        }
    });
}

function showActivityModal() {
    // 显示引导向导（快速创建 vs 自定义创建）
    document.getElementById('actEditId').value = '';
    document.getElementById('actFormTitle').value = '';
    document.getElementById('actFormStart').value = '';
    document.getElementById('actFormEnd').value = '';
    document.getElementById('actModalTitle').textContent = '新建活动';
    document.getElementById('activityModal').classList.add('show');
    showWizardChoice();
}

// ========== 极简操作 - 快速引导向导 ==========
var WIZARD_TEMPLATES = {
    'conference': { name: '🎙️ 会议活动', desc: '签到+上墙+弹幕+投票', features: ['qdq','wall','danmu','vote','kaimu','bimu'] },
    'annual':    { name: '🎉 年会盛典', desc: '签到+抽奖+摇一摇+红包雨+弹幕', features: ['qdq','lottery','shake','redpacket','danmu','kaimu','bimu'] },
    'wedding':   { name: '💍 婚礼庆典', desc: '签到+相册+上墙祝福+抽奖', features: ['qdq','xiangce','wall','lottery','kaimu','bimu'] },
    'marketing': { name: '📱 营销活动', desc: '签到+抢红包+投票+抽奖', features: ['qdq','redpacket','vote','lottery'] },
    'education': { name: '🎓 培训/讲座', desc: '签到+弹幕互动+投票', features: ['qdq','danmu','vote','wall'] },
    'custom':    { name: '⚙️ 自定义创建', desc: '自由选择功能模块', features: [] }
};

function showWizardChoice() {
    var body = document.querySelector('#activityModal .modal-body');
    if (!body) return;
    var html = '<div id="wizardStep" style="margin-bottom:16px">';
    html += '<div style="text-align:center;margin-bottom:20px"><h3 style="margin:0 0 4px 0;font-size:18px">选择活动模板</h3>';
    html += '<p style="color:#9ca3af;font-size:13px;margin:0">一键快速创建，10分钟上手</p></div>';
    html += '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px">';
    var keys = Object.keys(WIZARD_TEMPLATES);
    for (var i = 0; i < keys.length; i++) {
        var k = keys[i];
        var t = WIZARD_TEMPLATES[k];
        html += '<div class="wizard-tpl-card" onclick="selectWizardTemplate(\'' + k + '\')" style="border:2px solid #e5e7eb;border-radius:10px;padding:14px;cursor:pointer;transition:all .2s" onmouseover="this.style.borderColor=\'#6366f1\';this.style.background=\'#eef2ff\'" onmouseout="this.style.borderColor=\'#e5e7eb\';this.style.background=\'\'">';
        html += '<div style="font-size:15px;font-weight:600;margin-bottom:4px">' + t.name + '</div>';
        html += '<div style="font-size:12px;color:#6b7280">' + t.desc + '</div>';
        html += '</div>';
    }
    html += '</div></div>';
    // 将向导插入到表单前面
    var existingWizard = document.getElementById('wizardStep');
    if (existingWizard) existingWizard.remove();
    body.insertBefore(createElementFromHTML(html), body.firstChild);
}

function createElementFromHTML(htmlString) {
    var div = document.createElement('div');
    div.innerHTML = htmlString.trim();
    return div.firstChild;
}

var selectedTemplate = null;

function selectWizardTemplate(key) {
    selectedTemplate = key;
    var tpl = WIZARD_TEMPLATES[key];
    if (!tpl) return;

    // 高亮选中的模板
    var cards = document.querySelectorAll('.wizard-tpl-card');
    for (var i = 0; i < cards.length; i++) {
        cards[i].style.borderColor = '#e5e7eb';
        cards[i].style.background = '';
    }
    event.currentTarget.style.borderColor = '#6366f1';
    event.currentTarget.style.background = '#eef2ff';

    // 自动填充活动名称
    var titleInput = document.getElementById('actFormTitle');
    if (!titleInput.value) {
        var defaultNames = {
            'conference': '会议活动',
            'annual': '年会盛典',
            'wedding': '婚礼庆典',
            'marketing': '营销活动',
            'education': '培训讲座',
            'custom': ''
        };
        titleInput.value = defaultNames[key] || '';
    }
}

function editActivity(id) {
    HdApi.getActivity(id).then(function(res) {
        if (res.code === 0 && res.data) {
            var d = res.data;
            document.getElementById('actEditId').value = d.id;
            document.getElementById('actFormTitle').value = d.title || '';
            document.getElementById('actFormStart').value = d.started_at ? tsToDatetime(d.started_at) : '';
            document.getElementById('actFormEnd').value = d.ended_at ? tsToDatetime(d.ended_at) : '';
            document.getElementById('actModalTitle').textContent = '编辑活动';
            document.getElementById('activityModal').classList.add('show');
        }
    });
}

function saveActivity() {
    var id = document.getElementById('actEditId').value;
    var data = {
        title: document.getElementById('actFormTitle').value,
        started_at: document.getElementById('actFormStart').value,
        ended_at: document.getElementById('actFormEnd').value
    };
    if (!data.title) { showToast('请输入活动名称', 'error'); return; }

    var p = id ? HdApi.updateActivity(id, data) : HdApi.createActivity(data);
    p.then(function(res) {
        if (res.code === 0) {
            showToast(id ? '修改成功' : '创建成功');
            closeModal('activityModal');
            loadActivities();
            if (!id && res.data && res.data.access_code) {
                showToast('活动链接: wxhd.eivie.cn/s/' + res.data.access_code, 'success');
            }
            // 如果通过向导创建，自动启用模板功能并跳转管理页
            if (!id && selectedTemplate && selectedTemplate !== 'custom' && res.data && res.data.id) {
                var tpl = WIZARD_TEMPLATES[selectedTemplate];
                if (tpl && tpl.features && tpl.features.length > 0) {
                    autoEnableFeatures(res.data.id, tpl.features);
                }
                selectedTemplate = null;
                // 跳转到活动管理
                setTimeout(function() { manageActivity(res.data.id); }, 500);
            }
        } else {
            showToast(res.msg || '操作失败', 'error');
        }
    });
}

function viewActivity(id) {
    document.getElementById('actDetailContent').innerHTML = '加载中...';
    document.getElementById('actDetailModal').classList.add('show');

    HdApi.getActivity(id).then(function(res) {
        if (res.code !== 0) { document.getElementById('actDetailContent').innerHTML = '加载失败'; return; }
        var d = res.data;
        var html = '';
        html += '<div style="margin-bottom:16px"><strong>活动名称：</strong>' + d.title + '</div>';
        html += '<div style="margin-bottom:16px"><strong>访问链接：</strong><a href="' + d.url + '" target="_blank" style="color:#6366f1">' + d.url + '</a></div>';
        html += '<div style="margin-bottom:16px"><strong>状态：</strong>' + statusBadge(d.status) + '</div>';
        html += '<div style="margin-bottom:16px"><strong>参与人数：</strong>' + (d.participant_count || 0) + '人 / 已签到 ' + (d.signed_count || 0) + '人</div>';
        html += '<div style="margin-bottom:16px"><strong>验证码：</strong>' + (d.verifycode || '-') + '</div>';
        html += '<div style="margin-bottom:16px"><strong>开始时间：</strong>' + formatTime(d.started_at) + '</div>';
        html += '<div style="margin-bottom:16px"><strong>结束时间：</strong>' + formatTime(d.ended_at) + '</div>';

        // 功能列表
        if (d.features && d.features.length > 0) {
            html += '<div style="margin-bottom:8px"><strong>已配置功能：</strong></div>';
            html += '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">';
            for (var i = 0; i < d.features.length; i++) {
                var f = d.features[i];
                var enabled = f.enabled == 1;
                html += '<span class="badge ' + (enabled ? 'badge-success' : 'badge-danger') + '">' + (f.feature_name || f.feature_code) + '</span>';
            }
            html += '</div>';
        }

        // 操作按钮
        html += '<div style="display:flex;gap:10px;margin-top:24px;padding-top:16px;border-top:1px solid #e5e7eb">';
        if (d.status === 1) html += '<button class="btn btn-success" onclick="changeStatus(' + d.id + ',2)">开始活动</button>';
        if (d.status === 2) html += '<button class="btn btn-danger" onclick="changeStatus(' + d.id + ',3)">结束活动</button>';
        html += '<button class="btn btn-default" onclick="window.open(\'' + d.url + '\',\'_blank\')">打开大屏</button>';
        html += '</div>';

        document.getElementById('actDetailContent').innerHTML = html;
    });
}

function changeStatus(id, status) {
    HdApi.updateActivityStatus(id, status).then(function(res) {
        if (res.code === 0) { showToast('状态已更新'); viewActivity(id); loadActivities(); }
        else showToast(res.msg || '操作失败', 'error');
    });
}

function delActivity(id) {
    if (!confirm('确定要删除该活动吗？此操作不可撤销。')) return;
    HdApi.deleteActivity(id).then(function(res) {
        if (res.code === 0) { showToast('删除成功'); loadActivities(); }
        else showToast(res.msg || '删除失败', 'error');
    });
}

// ============ Settings ============
function loadSettings() {
    HdApi.getProfile().then(function(res) {
        if (res.code === 0 && res.data) {
            var d = res.data;
            document.getElementById('setBizName').value = d.business_name || '';
            document.getElementById('setContact').value = d.contact || '';
            document.getElementById('setTel').value = d.tel || '';
            document.getElementById('setPassword').value = '';

            // 套餐信息
            var plan = d.plan;
            if (plan) {
                var planHtml = '<div style="display:flex;gap:24px;flex-wrap:wrap">';
                planHtml += '<div><strong>套餐：</strong>' + plan.name + '</div>';
                planHtml += '<div><strong>到期时间：</strong>' + formatTime(plan.expire_time) + '</div>';
                planHtml += '<div><strong>状态：</strong>' + (plan.is_valid ? '<span style="color:#059669">有效</span>' : '<span style="color:#dc2626">已过期</span>') + '</div>';
                planHtml += '<div><strong>门店上限：</strong>' + plan.max_stores + '</div>';
                planHtml += '<div><strong>活动上限：</strong>' + plan.max_activities + '</div>';
                planHtml += '<div><strong>人数上限：</strong>' + plan.max_participants + '/活动</div>';
                planHtml += '</div>';
                document.getElementById('planInfo').innerHTML = planHtml;
            } else {
                document.getElementById('planInfo').innerHTML = '暂无套餐信息';
            }
        }
    });
}

function saveSettings() {
    var data = {
        name: document.getElementById('setBizName').value,
        contact_name: document.getElementById('setContact').value,
        tel: document.getElementById('setTel').value,
        password: document.getElementById('setPassword').value
    };
    HdApi.updateProfile(data).then(function(res) {
        if (res.code === 0) {
            showToast('保存成功');
            if (data.name) localStorage.setItem('hd_name', data.name);
        }
        else showToast(res.msg || '保存失败', 'error');
    });
}

// ============ Activity Detail Management ============
var currentActivityId = null;
var currentActivityData = null;

function manageActivity(id) {
    currentActivityId = id;
    HdApi.getActivity(id).then(function(res) {
        if (res.code === 0 && res.data) {
            currentActivityData = res.data;
            document.getElementById('actDetailTitle').textContent = res.data.title;
            document.getElementById('actDetailCode').textContent = res.data.access_code;
            switchPage('activity-detail');
            switchActTab('sign');
        } else {
            showToast(res.msg || '加载失败', 'error');
        }
    });
}

function openScreen() {
    if (currentActivityData && currentActivityData.url) {
        window.open(currentActivityData.url, '_blank');
    } else if (currentActivityData && currentActivityData.access_code) {
        window.open('https://wxhd.eivie.cn/s/' + currentActivityData.access_code, '_blank');
    }
}

// ============ Utils ============
function autoEnableFeatures(activityId, featureCodes) {
    // 向导创建后自动启用模板对应的功能模块
    featureCodes.forEach(function(code) {
        HdApi.toggleFeature(activityId, code).catch(function(){});
    });
}

function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}

function doLogout() {
    HdApi.logout().finally(function() {
        localStorage.clear();
        window.location.href = '/login';
    });
}

function copyCode(code) {
    var url = 'https://wxhd.eivie.cn/s/' + code;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function() { showToast('链接已复制'); });
    } else {
        var ta = document.createElement('textarea');
        ta.value = url;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        showToast('链接已复制');
    }
}

function tsToDatetime(ts) {
    if (!ts) return '';
    var d = new Date(ts * 1000);
    return d.getFullYear() + '-' +
        String(d.getMonth() + 1).padStart(2, '0') + '-' +
        String(d.getDate()).padStart(2, '0') + 'T' +
        String(d.getHours()).padStart(2, '0') + ':' +
        String(d.getMinutes()).padStart(2, '0');
}

// 响应式菜单切换按钮
(function() {
    function checkMobile() {
        var btn = document.getElementById('menuToggle');
        if (window.innerWidth <= 768) {
            btn.style.display = 'inline-flex';
        } else {
            btn.style.display = 'none';
            document.getElementById('sidebar').classList.remove('open');
        }
    }
    checkMobile();
    window.addEventListener('resize', checkMobile);
})();
