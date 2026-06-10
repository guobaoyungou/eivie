/**
 * XPD 选片端 - QRCode 二维码模块
 * 按门店配置展示公众号二维码(mp)或H5选片码(h5)，随头像切换自动更新
 */
const QRCodeModule = {
    name: 'qrcode',
    containerEl: null,
    qrcodeMode: 'mp',          // 二维码展示方式: 'mp'=公众号二维码, 'h5'=H5选片码
    currentQrcodeUrl: '',
    currentItemId: null,
    isFaceMatchMode: false,
    dataList: null,            // 完整数据列表，用于在 URL 为空时查找备用项

    /**
     * 设置二维码展示模式
     * @param {string} mode - 'mp' 或 'h5'
     */
    setMode(mode) {
        this.qrcodeMode = mode === 'h5' ? 'h5' : 'mp';
    },

    /**
     * 设置数据列表（用于 URL 为空时查找备用项）
     * @param {Array} list - 完整数据列表
     */
    setDataList(list) {
        this.dataList = list || [];
    },

    /**
     * 初始化二维码模块
     * @param {HTMLElement} el - 模块容器DOM
     */
    init(el) {
        this.containerEl = el;
        el.classList.add('module-qrcode');
        el.innerHTML = `
            <div class="qrcode-inner" style="display:flex;">
                <img class="qrcode-image" src="" alt="选片二维码" style="display:block;">
            </div>
        `;
    },

    /**
     * 获取当前 item 的二维码图片 URL
     * 当当前 item 的 URL 为空时，自动从 dataList 中查找备用项
     * @param {Object} item - 当前人像数据项
     * @returns {string} 二维码图片 URL
     */
    _getQrUrl(item) {
        const urlKey = this.qrcodeMode === 'h5' ? 'qrcode_url' : 'mp_qrcode_url';

        // 优先使用当前 item 的 URL
        if (item && item[urlKey]) return item[urlKey];

        // 当前 item URL 为空时，从 dataList 中查找第一个有效项
        if (this.dataList && this.dataList.length > 0) {
            const fallback = this.dataList.find(d => d[urlKey]);
            if (fallback && fallback !== item) return fallback[urlKey];
        }

        return '';
    },

    /**
     * 更新二维码显示 - 根据当前头像切换对应图片
     * @param {Object} item - 当前人像数据项 { mp_qrcode_url, qrcode, qrcode_url }
     * @param {boolean} isFaceMatch - 是否人脸匹配模式
     */
    update(item, isFaceMatch) {
        const inner = this.containerEl.querySelector('.qrcode-inner');
        if (!inner) return;

        // 显示二维码容器
        inner.style.display = 'flex';
        this.isFaceMatchMode = !!isFaceMatch;

        const qrUrl = this._getQrUrl(item);

        if (qrUrl) {
            const imgEl = this.containerEl.querySelector('.qrcode-image');
            if (imgEl) {
                imgEl.src = qrUrl;
                imgEl.style.display = 'block';
            }
            this.currentQrcodeUrl = qrUrl;
            this.currentItemId = item ? item.id : null;
        }
        // 无图片URL时保持显示上一个二维码

        // 高亮效果
        if (isFaceMatch) {
            inner.classList.add('highlight');
        } else {
            inner.classList.remove('highlight');
        }
    },

    /**
     * 隐藏二维码
     */
    hide() {
        const inner = this.containerEl.querySelector('.qrcode-inner');
        if (inner) inner.style.display = 'none';
    },

    /**
     * 销毁
     */
    destroy() {
        this.containerEl = null;
        this.currentQrcodeUrl = '';
        this.currentItemId = null;
        this.dataList = null;
    }
};
