/**
 * XPD 选片端 - QRCode 二维码模块
 * 封装二维码生成、自适应缩放、公众号优先逻辑
 */
const QRCodeModule = {
    name: 'qrcode',
    containerEl: null,
    qrcodeInstance: null,
    currentQrcode: '',
    currentMpUrl: '',
    isFaceMatchMode: false,

    /**
     * 初始化二维码模块
     * @param {HTMLElement} el - 模块容器DOM
     */
    init(el) {
        this.containerEl = el;
        el.classList.add('module-qrcode');
        el.innerHTML = `
            <div class="qrcode-inner">
                <div class="qrcode-canvas-wrap" style="display:none;"></div>
                <img class="qrcode-image" style="display:none;" alt="扫码关注公众号">
                <div class="qrcode-tip">扫码查看和购买</div>
            </div>
        `;

        // 监听容器尺寸变化做重绘
        if (window.ResizeObserver) {
            const ro = new ResizeObserver(() => {
                this._refresh();
            });
            ro.observe(el);
        }
    },

    /**
     * 更新二维码显示
     * @param {Object} item - 当前人像数据项 { mp_qrcode_url, qrcode }
     * @param {boolean} isFaceMatch - 是否人脸匹配模式
     */
    update(item, isFaceMatch) {
        if (!item) {
            this.hide();
            return;
        }

        this.isFaceMatchMode = !!isFaceMatch;

        const inner = this.containerEl.querySelector('.qrcode-inner');
        const canvasWrap = this.containerEl.querySelector('.qrcode-canvas-wrap');
        const imgEl = this.containerEl.querySelector('.qrcode-image');
        const tipEl = this.containerEl.querySelector('.qrcode-tip');

        // 优先展示公众号二维码
        if (item.mp_qrcode_url) {
            imgEl.src = item.mp_qrcode_url;
            imgEl.style.display = 'block';
            canvasWrap.style.display = 'none';
            tipEl.textContent = '微信扫码关注\n查看您的专属照片';
            inner.style.display = 'flex';
        } else if (item.qrcode) {
            // 降级：生成选片二维码
            imgEl.style.display = 'none';
            canvasWrap.style.display = 'flex';
            tipEl.textContent = '扫码查看和购买';
            inner.style.display = 'flex';

            this.currentQrcode = item.qrcode;
            this.currentMpUrl = '';
            this._generateQrcode();
        } else {
            this.hide();
            return;
        }

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
     * 生成选片二维码 Canvas
     */
    _generateQrcode() {
        const canvasWrap = this.containerEl.querySelector('.qrcode-canvas-wrap');
        if (!canvasWrap || !this.currentQrcode) return;

        // 清空旧Canvas
        canvasWrap.innerHTML = '';

        const canvas = document.createElement('canvas');
        canvasWrap.appendChild(canvas);

        // 根据容器尺寸动态计算二维码大小
        const size = Math.min(canvasWrap.clientWidth, canvasWrap.clientHeight) * 0.85;
        const qrSize = Math.max(100, Math.min(300, size));

        const url = XpdApi.buildPickPageUrl(this.currentQrcode);

        try {
            if (this.qrcodeInstance) {
                this.qrcodeInstance.clear();
            }
            this.qrcodeInstance = new QRCode(canvas, {
                text: url,
                width: qrSize,
                height: qrSize,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.L
            });
        } catch (error) {
            console.error('生成二维码失败:', error);
        }
    },

    /**
     * 容器尺寸变化时刷新二维码
     */
    _refresh() {
        if (this.currentQrcode && !this.currentMpUrl) {
            this._generateQrcode();
        }
    },

    /**
     * 销毁
     */
    destroy() {
        this.qrcodeInstance = null;
        this.containerEl = null;
    }
};
