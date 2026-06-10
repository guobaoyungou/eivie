/**
 * XPD 选片端 - API 请求封装
 * 封装数据获取和布局保存请求
 */
const XpdApi = {
    /**
     * 获取选片列表数据
     * @param {Object} params - { aid, bid, mdid, limit, load_mode }
     * @returns {Promise<Object>}
     */
    async fetchSelectionList(params) {
        const requestParams = {
            aid: params.aid,
            bid: params.bid,
            mdid: params.mdid || 0,
        };
        if (params.load_mode) {
            requestParams.load_mode = params.load_mode;
        }
        if (params.limit) {
            requestParams.limit = params.limit;
        }
        const response = await axios.get('/api/ai-travel-photo/selection-list', {
            params: requestParams,
            timeout: 10000
        });
        return response.data;
    },

    /**
     * 保存布局配置
     * @param {Object} params - { aid, mdid, layout, bg_color, face_detect }
     * @returns {Promise<Object>}
     */
    async saveLayout(params) {
        const formData = new URLSearchParams();
        formData.append('aid', params.aid);
        formData.append('mdid', params.mdid);
        if (params.layout !== undefined) {
            formData.append('layout', typeof params.layout === 'string' ? params.layout : JSON.stringify(params.layout));
        }
        if (params.bg_color !== undefined) {
            formData.append('bg_color', params.bg_color);
        }
        if (params.face_detect !== undefined) {
            formData.append('face_detect', params.face_detect ? '1' : '0');
        }

        const response = await axios.post('/api/ai-travel-photo/layout-save', formData, {
            timeout: 10000,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        });
        return response.data;
    },

    /**
     * 构建选片页URL（用于二维码）
     * @param {string} qrcode - 二维码字符串
     * @returns {string}
     */
    buildPickPageUrl(qrcode) {
        return window.location.origin + '/public/pick/index.html?qr=' + encodeURIComponent(qrcode);
    }
};
