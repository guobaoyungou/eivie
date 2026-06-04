/**
 * XPD 选片端 - 布局编辑器
 * Pointer Events 拖拽/缩放/吸附/选区管理
 */
const LayoutEditor = {
    engine: null,        // LayoutEngine 引用
    canvasEl: null,
    selectedEl: null,

    // 拖拽状态
    dragInfo: null,

    // 缩放状态
    resizeInfo: null,

    // 坐标提示
    tooltipEl: null,

    // 对齐参考线
    alignLines: { h: null, v: null },

    // 吸附容差(px)
    snapThreshold: 8,

    // 最小模块尺寸限制(百分比)
    minSizePercent: 5,

    /**
     * 初始化编辑器
     * @param {Object} engine - LayoutEngine 实例
     */
    init(engine) {
        this.engine = engine;
        this.canvasEl = engine.canvasEl;

        // 创建坐标提示气泡
        this._createTooltip();

        // 创建对齐参考线
        this._createAlignLines();

        // 绑定画布事件
        this._bindEvents();
    },

    /**
     * 创建坐标提示气泡
     */
    _createTooltip() {
        this.tooltipEl = document.createElement('div');
        this.tooltipEl.className = 'position-tooltip';
        document.body.appendChild(this.tooltipEl);
    },

    /**
     * 创建对齐参考线
     */
    _createAlignLines() {
        const hl = document.createElement('div');
        hl.className = 'align-line h';
        hl.style.display = 'none';
        this.alignLines.h = hl;

        const vl = document.createElement('div');
        vl.className = 'align-line v';
        vl.style.display = 'none';
        this.alignLines.v = vl;

        document.body.appendChild(hl);
        document.body.appendChild(vl);
    },

    /**
     * 绑定全局事件
     */
    _bindEvents() {
        // 使用 Pointer Events（同时支持鼠标和触摸）
        this.canvasEl.addEventListener('pointerdown', e => this._onPointerDown(e));
        document.addEventListener('pointermove', e => this._onPointerMove(e));
        document.addEventListener('pointerup', e => this._onPointerUp(e));

        // 工具栏按钮（委托）
        this.canvasEl.addEventListener('click', e => {
            const btn = e.target.closest('button');
            if (!btn) return;
            if (btn.id === 'btnSaveLayout') this._onSave();
            if (btn.id === 'btnResetLayout') this._onReset();
            if (btn.id === 'btnExitEdit') this._onExit();
        });

        // 窗口resize时更新
        window.addEventListener('resize', () => {
            // 不做任何操作，百分比定位自动适配
        });
    },

    /**
     * Pointer Down 事件处理
     */
    _onPointerDown(e) {
        const modEl = e.target.closest('.layout-module.edit-mode');
        if (!modEl) {
            this._deselect();
            return;
        }

        // 选中模块
        this._select(modEl);

        const resizeHandle = e.target.closest('.resize-handle');

        if (resizeHandle) {
            // 开始缩放
            this._startResize(e, modEl, resizeHandle.dataset.resizeDir);
        } else {
            // 任何在模块内部（非缩放手柄）的点击都触发拖拽
            this._startDrag(e, modEl);
        }
    },

    /**
     * 开始拖拽
     */
    _startDrag(e, modEl) {
        e.preventDefault();
        modEl.setPointerCapture(e.pointerId);

        this.dragInfo = {
            el: modEl,
            startX: e.clientX,
            startY: e.clientY,
            startLeft: parseFloat(modEl.style.left) || 0,
            startTop: parseFloat(modEl.style.top) || 0,
            pointerId: e.pointerId
        };

        modEl.classList.add('dragging');
    },

    /**
     * 开始缩放
     */
    _startResize(e, modEl, dir) {
        e.preventDefault();
        e.stopPropagation();
        modEl.setPointerCapture(e.pointerId);

        const rect = modEl.getBoundingClientRect();
        this.resizeInfo = {
            el: modEl,
            dir: dir,
            startX: e.clientX,
            startY: e.clientY,
            startLeft: rect.left,
            startTop: rect.top,
            startWidth: rect.width,
            startHeight: rect.height,
            pointerId: e.pointerId
        };

        modEl.classList.add('resizing');
    },

    /**
     * Pointer Move 事件处理
     */
    _onPointerMove(e) {
        // 拖拽中
        if (this.dragInfo && e.pointerId === this.dragInfo.pointerId) {
            this._handleDragMove(e);
            return;
        }

        // 缩放中
        if (this.resizeInfo && e.pointerId === this.resizeInfo.pointerId) {
            this._handleResizeMove(e);
            return;
        }
    },

    /**
     * 处理拖拽移动
     */
    _handleDragMove(e) {
        const info = this.dragInfo;
        const canvasRect = this.canvasEl.getBoundingClientRect();
        const dx = e.clientX - info.startX;
        const dy = e.clientY - info.startY;

        const dxPercent = (dx / canvasRect.width) * 100;
        const dyPercent = (dy / canvasRect.height) * 100;

        let newLeft = info.startLeft + dxPercent;
        let newTop = info.startTop + dyPercent;

        // 限制在画布内
        newLeft = Math.max(0, Math.min(100 - this.minSizePercent, newLeft));
        newTop = Math.max(0, Math.min(100 - this.minSizePercent, newTop));

        // 吸附检测
        const snapped = this._snapToEdges(info.el, newLeft, newTop, info.el.style.width, info.el.style.height);
        newLeft = snapped.left;
        newTop = snapped.top;

        info.el.style.left = newLeft.toFixed(2) + '%';
        info.el.style.top = newTop.toFixed(2) + '%';

        // 更新坐标提示
        this._showTooltip(e.clientX, e.clientY, newLeft, newTop);
    },

    /**
     * 处理缩放移动
     */
    _handleResizeMove(e) {
        const info = this.resizeInfo;
        const canvasRect = this.canvasEl.getBoundingClientRect();
        const dx = e.clientX - info.startX;
        const dy = e.clientY - info.startY;

        const dxPercent = (dx / canvasRect.width) * 100;
        const dyPercent = (dy / canvasRect.height) * 100;

        const minW = this.minSizePercent;
        const minH = this.minSizePercent;

        let newLeft = (info.startLeft / canvasRect.width) * 100;
        let newTop = (info.startTop / canvasRect.height) * 100;
        let newWidth = (info.startWidth / canvasRect.width) * 100;
        let newHeight = (info.startHeight / canvasRect.height) * 100;

        const dir = info.dir;

        // 按方向调整尺寸和位置
        if (dir.includes('e')) {
            newWidth = Math.max(minW, newWidth + dxPercent);
        }
        if (dir.includes('w')) {
            const proposedW = Math.max(minW, newWidth - dxPercent);
            const deltaW = newWidth - proposedW;
            newLeft = newLeft + deltaW;
            newWidth = proposedW;
        }
        if (dir.includes('s')) {
            newHeight = Math.max(minH, newHeight + dyPercent);
        }
        if (dir.includes('n')) {
            const proposedH = Math.max(minH, newHeight - dyPercent);
            const deltaH = newHeight - proposedH;
            newTop = newTop + deltaH;
            newHeight = proposedH;
        }

        // 限制不溢出
        newLeft = Math.max(0, newLeft);
        newTop = Math.max(0, newTop);
        if (newLeft + newWidth > 100) newWidth = 100 - newLeft;
        if (newTop + newHeight > 100) newHeight = 100 - newTop;

        info.el.style.left = newLeft.toFixed(2) + '%';
        info.el.style.top = newTop.toFixed(2) + '%';
        info.el.style.width = newWidth.toFixed(2) + '%';
        info.el.style.height = newHeight.toFixed(2) + '%';

        this._showTooltip(e.clientX, e.clientY, newLeft, newTop, newWidth, newHeight);
    },

    /**
     * Pointer Up 事件处理
     */
    _onPointerUp(e) {
        if (this.dragInfo) {
            this.dragInfo.el.classList.remove('dragging');
            this.dragInfo.el.releasePointerCapture(this.dragInfo.pointerId);
            this.dragInfo = null;
            this._hideTooltip();
            this._hideAlignLines();
        }

        if (this.resizeInfo) {
            this.resizeInfo.el.classList.remove('resizing');
            this.resizeInfo.el.releasePointerCapture(this.resizeInfo.pointerId);
            this.resizeInfo = null;
            this._hideTooltip();
            this._hideAlignLines();

            // 缩放后触发布局更新（头像等模块重新计算）
            const event = new CustomEvent('layout-resized', {
                detail: { el: this.selectedEl }
            });
            window.dispatchEvent(event);
        }
    },

    /**
     * 吸附对齐检测
     */
    _snapToEdges(el, left, top, widthStr, heightStr) {
        const threshold = this.snapThreshold;
        const canvasW = this.canvasEl.clientWidth;
        const canvasH = this.canvasEl.clientHeight;

        const wPercent = parseFloat(widthStr) || 0;
        const hPercent = parseFloat(heightStr) || 0;

        const leftPx = (left / 100) * canvasW;
        const topPx = (top / 100) * canvasH;
        const rightPx = leftPx + (wPercent / 100) * canvasW;
        const bottomPx = topPx + (hPercent / 100) * canvasH;
        const centerXPx = leftPx + (wPercent / 100) * canvasW / 2;
        const centerYPx = topPx + (hPercent / 100) * canvasH / 2;

        let snapH = null, snapV = null;

        // 水平吸附：左边缘、右边缘、中心对齐画布中心
        if (Math.abs(leftPx) < threshold) {
            left = 0;
            snapV = 0;
        } else if (Math.abs(rightPx - canvasW) < threshold) {
            left = 100 - wPercent;
            snapV = canvasW;
        } else if (Math.abs(centerXPx - canvasW / 2) < threshold) {
            left = 50 - wPercent / 2;
            snapV = canvasW / 2;
        }

        // 垂直吸附：上边缘、下边缘、中心对齐画布中心
        if (Math.abs(topPx) < threshold) {
            top = 0;
            snapH = 0;
        } else if (Math.abs(bottomPx - canvasH) < threshold) {
            top = 100 - hPercent;
            snapH = canvasH;
        } else if (Math.abs(centerYPx - canvasH / 2) < threshold) {
            top = 50 - hPercent / 2;
            snapH = canvasH / 2;
        }

        // 与其他模块边缘吸附
        const allMods = this.canvasEl.querySelectorAll('.layout-module.edit-mode');
        allMods.forEach(other => {
            if (other === el) return;
            const otherRect = other.getBoundingClientRect();
            const otherLeft = otherRect.left;
            const otherRight = otherRect.right;
            const otherTop = otherRect.top;
            const otherBottom = otherRect.bottom;

            if (Math.abs(leftPx - otherLeft) < threshold) { left = (otherLeft / canvasW) * 100; snapV = otherLeft; }
            if (Math.abs(rightPx - otherLeft) < threshold) { left = (otherLeft / canvasW) * 100 - wPercent; snapV = otherLeft; }
            if (Math.abs(leftPx - otherRight) < threshold) { left = (otherRight / canvasW) * 100; snapV = otherRight; }
            if (Math.abs(rightPx - otherRight) < threshold) { left = (otherRight / canvasW) * 100 - wPercent; snapV = otherRight; }

            if (Math.abs(topPx - otherTop) < threshold) { top = (otherTop / canvasH) * 100; snapH = otherTop; }
            if (Math.abs(bottomPx - otherTop) < threshold) { top = (otherTop / canvasH) * 100 - hPercent; snapH = otherTop; }
            if (Math.abs(topPx - otherBottom) < threshold) { top = (otherBottom / canvasH) * 100; snapH = otherBottom; }
            if (Math.abs(bottomPx - otherBottom) < threshold) { top = (otherBottom / canvasH) * 100 - hPercent; snapH = otherBottom; }
        });

        // 显示对齐参考线
        if (snapV !== null) {
            this.alignLines.v.style.display = 'block';
            this.alignLines.v.style.left = snapV + 'px';
        } else {
            this.alignLines.v.style.display = 'none';
        }
        if (snapH !== null) {
            this.alignLines.h.style.display = 'block';
            this.alignLines.h.style.top = snapH + 'px';
        } else {
            this.alignLines.h.style.display = 'none';
        }

        return { left, top };
    },

    /**
     * 选中模块
     */
    _select(el) {
        if (this.selectedEl === el) return;
        this._deselect();
        this.selectedEl = el;
        el.classList.add('selected');
        el.style.zIndex = 9999;
    },

    /**
     * 取消选中
     */
    _deselect() {
        if (this.selectedEl) {
            this.selectedEl.classList.remove('selected');
            // 恢复原始zIndex（从默认配置查找）
            const mod = this.engine.modules.find(m => m.id === this.selectedEl.dataset.moduleId);
            this.selectedEl.style.zIndex = mod ? mod.zIndex : 1;
            this.selectedEl = null;
        }
    },

    /**
     * 显示坐标提示
     */
    _showTooltip(x, y, left, top, width, height) {
        const tip = this.tooltipEl;
        let text = `左:${left.toFixed(1)}% 上:${top.toFixed(1)}%`;
        if (width !== undefined && height !== undefined) {
            text += ` | ${width.toFixed(1)}% × ${height.toFixed(1)}%`;
        }
        tip.textContent = text;
        tip.style.left = (x + 15) + 'px';
        tip.style.top = (y - 30) + 'px';
        tip.classList.add('show');
    },

    /**
     * 隐藏坐标提示
     */
    _hideTooltip() {
        if (this.tooltipEl) {
            this.tooltipEl.classList.remove('show');
        }
    },

    /**
     * 隐藏对齐参考线
     */
    _hideAlignLines() {
        this.alignLines.h.style.display = 'none';
        this.alignLines.v.style.display = 'none';
    },

    /**
     * 保存按钮
     */
    _onSave() {
        const layout = this.engine.serializeLayout();
        // 触发自定义事件，由主应用处理保存
        const event = new CustomEvent('layout-save', {
            detail: { layout: JSON.stringify(layout) }
        });
        window.dispatchEvent(event);
    },

    /**
     * 重置默认按钮
     */
    _onReset() {
        if (confirm('确认重置为默认布局？当前修改将丢失。')) {
            this.engine.init(this.canvasEl, null, true);
            this._deselect();
        }
    },

    /**
     * 退出编辑按钮
     */
    _onExit() {
        // 移除 mode=edit 参数后刷新
        const url = new URL(window.location.href);
        url.searchParams.delete('mode');
        window.location.href = url.toString();
    },

    /**
     * 销毁
     */
    destroy() {
        this._hideTooltip();
        this._hideAlignLines();
        if (this.tooltipEl) this.tooltipEl.remove();
        if (this.alignLines.h) this.alignLines.h.remove();
        if (this.alignLines.v) this.alignLines.v.remove();
        this.dragInfo = null;
        this.resizeInfo = null;
    }
};
