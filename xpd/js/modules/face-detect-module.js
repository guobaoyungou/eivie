/**
 * XPD 选片端 - 人脸检测模块
 * 按需加载 face-api.js、特征库构建、实时匹配
 */
const FaceDetectModule = {
    name: 'faceDetect',
    enabled: false,
    cameraReady: false,
    modelsLoaded: false,
    detectionTimer: null,
    dataList: [],
    onMatchCallback: null,
    onNoFaceCallback: null,

    // 配置
    config: {
        noFaceTimeout: 5000,
        matchThreshold: 0.6,
        detectionInterval: 1000,
    },

    // 状态
    state: {
        mode: 'default', // 'default' | 'faceMatch'
        matchedPortraitId: null,
        lastFaceDetectedTime: 0,
        faceMatchPlayComplete: false,
    },

    // 特征库 Map<portraitId, Float32Array>
    faceDescriptorMap: new Map(),

    /**
     * 初始化人脸检测
     * @param {boolean} enabled - 管理员开关
     * @param {Function} onMatch - 匹配成功回调(portraitId)
     * @param {Function} onNoFace - 无人脸超时回调
     * @param {Function} onStatus - 状态变化回调(state, text)
     */
    async init(enabled, onMatch, onNoFace, onStatus) {
        this.enabled = enabled;
        this.onMatchCallback = onMatch;
        this.onNoFaceCallback = onNoFace;

        if (!enabled) {
            onStatus && onStatus('disabled', '人脸识别已关闭');
            return;
        }

        try {
            onStatus && onStatus('loading', '加载人脸检测模型...');
            await this._loadModels();
            this.modelsLoaded = true;

            onStatus && onStatus('loading', '初始化摄像头...');
            await this._initCamera();

            onStatus && onStatus('active', '人脸检测已开启');
            this._startDetection();

        } catch (error) {
            console.warn('人脸检测初始化失败:', error);
            onStatus && onStatus('error', '人脸检测不可用');
            this.enabled = false;
        }
    },

    /**
     * 加载 face-api.js 模型
     */
    async _loadModels() {
        const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1/model';
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
        ]);
        console.log('[人脸检测] 模型加载完成');
    },

    /**
     * 初始化摄像头（隐藏video元素）
     */
    async _initCamera() {
        const video = document.getElementById('cameraVideo');
        if (!video) {
            const v = document.createElement('video');
            v.id = 'cameraVideo';
            document.body.appendChild(v);
        }

        const stream = await navigator.mediaDevices.getUserMedia({
            video: { width: 640, height: 480, facingMode: 'user' },
            audio: false
        });

        const vid = document.getElementById('cameraVideo');
        vid.srcObject = stream;
        await new Promise(resolve => {
            vid.onloadedmetadata = () => {
                vid.play();
                resolve();
            };
        });
        this.cameraReady = true;
        console.log('[人脸检测] 摄像头就绪');
    },

    /**
     * 构建人脸特征库（数据刷新后调用）
     * @param {Array} dataList - 选片数据列表
     */
    async buildDescriptors(dataList) {
        if (!this.enabled || !this.modelsLoaded) return;
        this.dataList = dataList;
        this.faceDescriptorMap.clear();
        let successCount = 0;

        for (const item of dataList) {
            try {
                const imgUrl = item.original_url || item.thumbnail_url;
                if (!imgUrl) continue;

                const img = await faceapi.fetchImage(imgUrl);
                const detection = await faceapi
                    .detectSingleFace(img, new faceapi.TinyFaceDetectorOptions({ scoreThreshold: 0.5 }))
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                if (detection) {
                    this.faceDescriptorMap.set(item.id, detection.descriptor);
                    successCount++;
                }
            } catch (error) {
                // 静默跳过
            }
        }
        console.log(`[人脸检测] 特征库: ${successCount}/${dataList.length}`);
    },

    /**
     * 启动检测循环
     */
    _startDetection() {
        if (this.detectionTimer) clearInterval(this.detectionTimer);
        this.detectionTimer = setInterval(() => {
            this._detect();
        }, this.config.detectionInterval);
    },

    /**
     * 停止检测
     */
    stop() {
        if (this.detectionTimer) {
            clearInterval(this.detectionTimer);
            this.detectionTimer = null;
        }
        // 释放摄像头
        const video = document.getElementById('cameraVideo');
        if (video && video.srcObject) {
            video.srcObject.getTracks().forEach(t => t.stop());
        }
    },

    /**
     * 单次人脸检测
     */
    async _detect() {
        if (!this.enabled || !this.cameraReady) return;
        if (this.faceDescriptorMap.size === 0) return;

        try {
            const video = document.getElementById('cameraVideo');

            const detections = await faceapi
                .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({ scoreThreshold: 0.5 }))
                .withFaceLandmarks()
                .withFaceDescriptors();

            if (detections.length === 0) {
                const now = Date.now();
                if (this.state.mode === 'faceMatch' &&
                    (now - this.state.lastFaceDetectedTime > this.config.noFaceTimeout)) {
                    this._switchToDefault();
                }
                return;
            }

            this.state.lastFaceDetectedTime = Date.now();

            // 取置信度最高的人脸
            const best = detections.reduce((a, b) =>
                a.detection.score > b.detection.score ? a : b
            );

            // 欧氏距离比对
            const matchId = this._matchFace(best.descriptor);
            if (matchId) {
                if (this.state.mode !== 'faceMatch' || this.state.matchedPortraitId !== matchId) {
                    this._switchToFaceMatch(matchId);
                }
            } else if (this.state.mode === 'faceMatch' && this.state.faceMatchPlayComplete) {
                this._switchToDefault();
            }
        } catch (error) {
            // 静默
        }
    },

    /**
     * 人脸匹配
     */
    _matchFace(descriptor) {
        let bestId = null;
        let bestDist = Infinity;
        for (const [pid, storedDesc] of this.faceDescriptorMap) {
            const dist = faceapi.euclideanDistance(descriptor, storedDesc);
            if (dist < bestDist) {
                bestDist = dist;
                bestId = pid;
            }
        }
        return bestDist < this.config.matchThreshold ? bestId : null;
    },

    /**
     * 切换到人脸匹配模式
     */
    _switchToFaceMatch(portraitId) {
        this.state.mode = 'faceMatch';
        this.state.matchedPortraitId = portraitId;
        this.state.faceMatchPlayComplete = false;
        if (this.onMatchCallback) this.onMatchCallback(portraitId);
    },

    /**
     * 切回默认轮播模式
     */
    _switchToDefault() {
        console.log('[人脸检测] 切回默认轮播');
        this.state.mode = 'default';
        this.state.matchedPortraitId = null;
        this.state.faceMatchPlayComplete = false;
        if (this.onNoFaceCallback) this.onNoFaceCallback();
    },

    /**
     * 标记人脸匹配播放完成
     */
    markPlayComplete() {
        this.state.faceMatchPlayComplete = true;
    },

    /**
     * 获取当前模式
     */
    getMode() {
        return this.state.mode;
    },

    /**
     * 销毁
     */
    destroy() {
        this.stop();
        this.faceDescriptorMap.clear();
    }
};
