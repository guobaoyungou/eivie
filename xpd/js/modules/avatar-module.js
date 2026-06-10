/**
 * XPD 选片端 - Avatar 头像模块
 * 封装头像列表渲染、弹性排列、高亮跟踪、点击切换
 */
const AvatarModule = {
    name: 'avatar',
    containerEl: null,
    dataList: [],
    currentIndex: 0,
    onClickCallback: null,

    /**
     * 初始化头像模块
     * @param {HTMLElement} el - 模块容器DOM
     * @param {Function} onClick - 点击头像回调(groupIndex)
     */
    init(el, onClick) {
        this.containerEl = el;
        this.onClickCallback = onClick;
        el.classList.add('module-avatar');
        el.innerHTML = `
            <div class="avatar-inner">
                <div class="avatar-list"></div>
            </div>
        `;
    },

    /**
     * 渲染头像列表
     * @param {Array} dataList - 数据列表
     * @param {number} currentGroupIndex - 当前高亮组索引
     */
    render(dataList, currentGroupIndex) {
        this.dataList = dataList;
        this.currentIndex = currentGroupIndex;

        const listEl = this.containerEl.querySelector('.avatar-list');
        if (!listEl) return;
        listEl.innerHTML = '';

        const count = dataList.length;
        if (count === 0) return;

        // 根据容器尺寸动态计算网格布局，自适应行列填满空间
        const containerWidth = this.containerEl.clientWidth;
        const containerHeight = this.containerEl.clientHeight;
        // 根据容器宽高比自适应列数
        const aspect = containerWidth / Math.max(containerHeight, 1);
        const cols = Math.max(1, Math.ceil(Math.sqrt(count * aspect)));
        const rows = Math.ceil(count / cols);
        // 按宽度均分可用空间，高度不限制（超出则滚动）
        const avatarSize = Math.max(32, Math.floor(containerWidth / cols * 0.88));

        dataList.forEach((item, index) => {
            const avatarItem = document.createElement('div');
            avatarItem.className = 'avatar-item';
            avatarItem.style.width = avatarSize + 'px';
            avatarItem.style.height = avatarSize + 'px';
            if (index === currentGroupIndex) {
                avatarItem.classList.add('highlight');
            }

            const img = document.createElement('img');
            img.src = item.thumbnail_url || item.original_url;
            img.alt = '人像';
            img.onerror = function () {
                this.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="80" height="80"%3E%3Crect fill="%23333" width="80" height="80"/%3E%3C/svg%3E';
            };

            avatarItem.addEventListener('click', () => {
                if (this.onClickCallback) {
                    this.onClickCallback(index);
                }
            });

            avatarItem.appendChild(img);
            listEl.appendChild(avatarItem);
        });
    },

    /**
     * 更新高亮
     * @param {number} groupIndex - 当前播放组索引
     */
    highlight(groupIndex) {
        this.currentIndex = groupIndex;
        const items = this.containerEl.querySelectorAll('.avatar-item');
        items.forEach((item, i) => {
            if (i === groupIndex) {
                item.classList.add('highlight');
            } else {
                item.classList.remove('highlight');
            }
        });
    },

    /**
     * 容器尺寸变化后重新渲染
     */
    resize() {
        if (this.dataList.length > 0) {
            this.render(this.dataList, this.currentIndex);
        }
    }
};
