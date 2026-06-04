/**
 * XPD 选片端 - 布局引擎核心
 * 模块DOM渲染、坐标换算、内容自适应、默认布局
 */
const LayoutEngine = {
    canvasEl: null,
    modules: [],
    isEditMode: false,

    /** 默认布局配置 */
    defaultLayout: {
        bgColor: '#000000',
        faceDetectEnabled: true,
        modules: [
            { id: 'swiper-main', type: 'swiper', top: '0%', left: '0%', width: '100%', height: '80%', zIndex: 1, visible: true },
            { id: 'avatar-bar',  type: 'avatar', top: '80%', left: '0%', width: '100%', height: '20%', zIndex: 2, visible: true },
            { id: 'qrcode-box',  type: 'qrcode', top: '62%', left: '84%', width: '15%', height: '16%', zIndex: 10, visible: true },
            { id: 'face-status', type: 'face-status', top: '1%', left: '1%', width: 'auto', height: 'auto', zIndex: 20, visible: false }
        ]
    },

    /**
     * 初始化布局引擎
     * @param {HTMLElement} canvas - 画布容器
     * @param {Object} layoutConfig - 从API获取的布局配置（可为null则用默认）
     * @param {boolean} isEdit - 是否编辑模式
     */
    init(canvas, layoutConfig, isEdit = false) {
        this.canvasEl = canvas;
        this.isEditMode = isEdit;

        // 合并配置：后台配置优先，缺失用默认值
        const config = this._mergeConfig(layoutConfig);
        this.modules = config.modules;

        // 应用模式类
        canvas.classList.add('xpd-canvas');
        canvas.classList.add(isEdit ? 'edit-mode' : 'display-mode');

        // 渲染所有模块DOM
        this._renderModules();
    },

    /**
     * 合并布局配置（后台配置 + 默认值兜底）
     */
    _mergeConfig(apiConfig) {
        if (!apiConfig || !apiConfig.modules || apiConfig.modules.length === 0) {
            return JSON.parse(JSON.stringify(this.defaultLayout));
        }

        const merged = {
            bgColor: apiConfig.bgColor || this.defaultLayout.bgColor,
            faceDetectEnabled: apiConfig.faceDetectEnabled !== undefined
                ? apiConfig.faceDetectEnabled
                : this.defaultLayout.faceDetectEnabled,
            modules: []
        };

        // 按默认模块ID顺序，用API配置覆盖
        const apiModuleMap = {};
        if (apiConfig.modules) {
            apiConfig.modules.forEach(m => { apiModuleMap[m.id] = m; });
        }

        this.defaultLayout.modules.forEach(defMod => {
            const apiMod = apiModuleMap[defMod.id];
            if (apiMod) {
                merged.modules.push({
                    id: defMod.id,
                    type: apiMod.type || defMod.type,
                    top: apiMod.top !== undefined ? apiMod.top : defMod.top,
                    left: apiMod.left !== undefined ? apiMod.left : defMod.left,
                    width: apiMod.width !== undefined ? apiMod.width : defMod.width,
                    height: apiMod.height !== undefined ? apiMod.height : defMod.height,
                    zIndex: apiMod.zIndex !== undefined ? apiMod.zIndex : defMod.zIndex,
                    visible: apiMod.visible !== undefined ? apiMod.visible : defMod.visible
                });
            } else {
                merged.modules.push({ ...defMod });
            }
        });

        return merged;
    },

    /**
     * 渲染所有模块DOM到画布
     */
    _renderModules() {
        this.canvasEl.innerHTML = '';

        this.modules.forEach(mod => {
            if (!mod.visible && !this.isEditMode) return;

            const modEl = document.createElement('div');
            modEl.className = 'layout-module';
            modEl.dataset.moduleId = mod.id;
            modEl.dataset.moduleType = mod.type;
            modEl.style.top = mod.top;
            modEl.style.left = mod.left;
            modEl.style.width = mod.width;
            modEl.style.height = mod.height;
            modEl.style.zIndex = mod.zIndex;

            if (this.isEditMode) {
                modEl.classList.add('edit-mode');
                this._addEditHandles(modEl, mod);
            } else {
                modEl.classList.add('display-mode');
            }

            this.canvasEl.appendChild(modEl);
        });

        // 编辑模式下添加工具栏
        if (this.isEditMode) {
            this._addEditToolbar();
        }
    },

    /**
     * 为编辑模式的模块添加手柄
     */
    _addEditHandles(modEl, mod) {
        // 模块类型标签
        const label = document.createElement('span');
        label.className = 'module-type-label';
        label.textContent = mod.type;
        modEl.appendChild(label);

        // 拖拽手柄
        const handle = document.createElement('div');
        handle.className = 'module-drag-handle';
        handle.innerHTML = '<span class="handle-dot"></span><span class="handle-dot"></span><span class="handle-dot"></span>';
        modEl.appendChild(handle);

        // 8个缩放手柄
        const directions = ['nw', 'n', 'ne', 'w', 'e', 'sw', 's', 'se'];
        directions.forEach(dir => {
            const rh = document.createElement('div');
            rh.className = `resize-handle ${dir}`;
            rh.dataset.resizeDir = dir;
            modEl.appendChild(rh);
        });
    },

    /**
     * 添加底部编辑工具栏
     */
    _addEditToolbar() {
        const toolbar = document.createElement('div');
        toolbar.className = 'edit-toolbar';
        toolbar.innerHTML = `
            <span class="toolbar-info">编辑模式</span>
            <span class="toolbar-sep"></span>
            <button class="btn-save" id="btnSaveLayout">保存布局</button>
            <button class="btn-reset" id="btnResetLayout">重置默认</button>
            <button class="btn-exit" id="btnExitEdit">退出编辑</button>
        `;
        this.canvasEl.appendChild(toolbar);
    },

    /**
     * 获取指定模块的DOM元素
     * @param {string} moduleId
     * @returns {HTMLElement|null}
     */
    getModuleEl(moduleId) {
        return this.canvasEl.querySelector(`[data-module-id="${moduleId}"]`);
    },

    /**
     * 获取指定类型的模块DOM元素
     * @param {string} type
     * @returns {HTMLElement|null}
     */
    getModuleByType(type) {
        return this.canvasEl.querySelector(`[data-module-type="${type}"]`);
    },

    /**
     * 序列化当前布局为JSON对象
     * @returns {Object}
     */
    serializeLayout() {
        const modules = [];
        const modEls = this.canvasEl.querySelectorAll('.layout-module');
        modEls.forEach(el => {
            modules.push({
                id: el.dataset.moduleId,
                type: el.dataset.moduleType,
                top: el.style.top,
                left: el.style.left,
                width: el.style.width,
                height: el.style.height,
                zIndex: parseInt(el.style.zIndex) || 1,
                visible: el.style.display !== 'none'
            });
        });
        return { modules };
    },

    /**
     * 销毁
     */
    destroy() {
        this.canvasEl.innerHTML = '';
        this.modules = [];
    }
};
