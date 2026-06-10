/**
 * XPD 选片端 - Text 文本模块
 * 支持自由拖拽位置和缩放，用于展示自定义文字（如"微信扫码选片"）
 */
const TextModule = {
    name: 'text',
    containerEl: null,
    defaultText: '微信扫码选片',

    /**
     * 初始化文本模块
     * @param {HTMLElement} el - 模块容器DOM
     * @param {string} text - 显示的文本（可选，默认"微信扫码选片"）
     */
    init(el, text) {
        this.containerEl = el;
        this.text = text || this.defaultText;
        el.classList.add('module-text');
        // 将文本内容存入 dataset，供 serializeLayout 读取
        el.dataset.text = this.text;
        el.innerHTML = `
            <div class="text-inner">${this.text}</div>
        `;
    },

    /**
     * 更新文本内容
     * @param {string} text
     */
    setText(text) {
        this.text = text || this.defaultText;
        this.containerEl.dataset.text = this.text;
        const inner = this.containerEl.querySelector('.text-inner');
        if (inner) inner.textContent = this.text;
    },

    /**
     * 销毁
     */
    destroy() {
        this.containerEl = null;
        this.text = null;
    }
};
