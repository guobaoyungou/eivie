/**
 * XPD 选片端 - Swiper 幻灯片模块
 * 封装 Swiper.js 初始化、播放控制、内容自适应
 */
const SwiperModule = {
    name: 'swiper',
    swiperInstance: null,
    containerEl: null,

    /**
     * 初始化 Swiper 模块
     * @param {HTMLElement} el - 模块容器DOM
     * @returns {Object} swiper 实例
     */
    init(el) {
        this.containerEl = el;
        el.classList.add('module-swiper');
        el.innerHTML = `
            <div class="swiper-container">
                <div class="swiper-wrapper"></div>
            </div>
        `;

        this.swiperInstance = new Swiper(el.querySelector('.swiper-container'), {
            effect: 'fade',
            fadeEffect: { crossFade: true },
            speed: 800,
            allowTouchMove: false,
            autoplay: false,
            observer: true,
            observeParents: true,
            observeSlideChildren: true,
        });

        return this.swiperInstance;
    },

    /**
     * 渲染幻灯片内容
     * @param {Array} dataList - 数据列表
     */
    renderSlides(dataList) {
        if (!this.swiperInstance) return;
        const wrapper = this.containerEl.querySelector('.swiper-wrapper');
        wrapper.innerHTML = '';

        dataList.forEach((item, groupIndex) => {
            if (!item.results || item.results.length === 0) return;
            item.results.forEach(result => {
                const slide = document.createElement('div');
                slide.className = 'swiper-slide';
                slide.dataset.groupIndex = groupIndex;

                if (result.type === 19) {
                    slide.innerHTML = `
                        <video class="media-item video"
                               src="${result.watermark_url}"
                               autoplay muted loop playsinline>
                        </video>`;
                } else {
                    slide.innerHTML = `
                        <img class="media-item"
                             src="${result.watermark_url}"
                             alt="AI旅拍照片">`;
                }
                wrapper.appendChild(slide);
            });
        });

        this.swiperInstance.update();
    },

    /**
     * 跳转到指定 slide 索引
     * @param {number} index - 全局索引
     * @param {number} speed - 动画速度ms
     */
    slideTo(index, speed = 500) {
        if (this.swiperInstance) {
            this.swiperInstance.slideTo(index, speed);
        }
    },

    /**
     * 更新滑动组件（内容变化后调用）
     */
    update() {
        if (this.swiperInstance) {
            this.swiperInstance.update();
        }
    },

    /**
     * 销毁实例
     */
    destroy() {
        if (this.swiperInstance) {
            this.swiperInstance.destroy(true, true);
            this.swiperInstance = null;
        }
    }
};
