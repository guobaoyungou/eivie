<template>
<view class="page-container">
    <view class="container">
        <!-- 门店选择 -->
        <view class="section">
            <view class="section-title">选择门店</view>
            <picker mode="selector" :range="storeNames" :value="storeIndex" @change="onStoreChange">
                <view class="picker-box">
                    <text class="picker-text">{{ storeNames[storeIndex] || '全部门店' }}</text>
                    <image class="picker-arrow" :src="pre_url+'/static/img/arrowright.png'"/>
                </view>
            </picker>
        </view>

        <!-- 图片选择 -->
        <view class="section">
            <view class="section-title">选择人像图片（最多20张）</view>
            <view class="image-grid">
                <block v-for="(item, index) in imageList" :key="index">
                    <view class="image-item">
                        <image :src="item" mode="aspectFill" class="image-preview"/>
                        <view class="image-delete" @tap="removeImage(index)">
                            <text class="delete-icon">×</text>
                        </view>
                    </view>
                </block>
                <view v-if="imageList.length < 20" class="image-add" @tap="chooseImages">
                    <text class="add-icon">+</text>
                    <text class="add-text">添加图片</text>
                </view>
            </view>
        </view>

        <!-- 上传进度 -->
        <view v-if="uploading" class="section">
            <view class="section-title">上传进度</view>
            <view class="progress-bar-wrap">
                <view class="progress-bar" :style="{width: uploadProgress + '%'}"></view>
            </view>
            <view class="progress-text">{{ uploadCurrent }} / {{ uploadTotal }} ({{ uploadProgress }}%)</view>
        </view>

        <!-- 合成进度 -->
        <view v-if="synthesizing" class="section">
            <view class="section-title">批量合成中</view>
            <view class="synth-status">
                <view class="loading-spinner"></view>
                <text class="synth-text">正在提交合成任务，请稍候...</text>
            </view>
        </view>

        <!-- 操作按钮 -->
        <view v-if="!uploading && !synthesizing" class="action-bar">
            <button class="btn-upload" :disabled="imageList.length === 0" @tap="startUpload">
                {{ imageList.length > 0 ? '开始上传（' + imageList.length + '张）' : '请先选择图片' }}
            </button>
        </view>

        <!-- 结果提示 -->
        <view v-if="showResult" class="result-mask" @tap="showResult=false">
            <view class="result-dialog" @tap.stop="">
                <view class="result-icon">✓</view>
                <view class="result-title">处理完成</view>
                <view class="result-content">
                    <text v-if="uploadResult.success_count > 0">上传成功 {{ uploadResult.success_count }} 张</text>
                    <text v-if="uploadResult.fail_count > 0" style="color:#ff4444">，失败 {{ uploadResult.fail_count }} 张</text>
                </view>
                <view class="result-content" v-if="synthResultMsg">
                    <text>{{ synthResultMsg }}</text>
                </view>
                <button class="btn-confirm" @tap="goBack">确定</button>
            </view>
        </view>
    </view>
</view>
</template>

<script>
var app = getApp();
export default {
    data() {
        return {
            pre_url: app.globalData.pre_url,
            bid: 0,
            // 门店数据
            mendianlist: [],
            storeNames: ['全部门店'],
            storeIndex: 0,
            selectedMendianId: 0,
            // 图片数据
            imageList: [],
            // 上传状态
            uploading: false,
            uploadCurrent: 0,
            uploadTotal: 0,
            uploadProgress: 0,
            uploadResult: { success_count: 0, fail_count: 0 },
            // 合成状态
            synthesizing: false,
            synthResultMsg: '',
            // 结果展示
            showResult: false,
        }
    },
    onLoad: function(opt) {
        var that = this;
        this.bid = opt.bid || 0;
        
        if (!this.bid) {
            app.alert('缺少商家信息');
            setTimeout(function() { app.goback(); }, 1500);
            return;
        }

        // 获取门店列表（优先从上一页数据获取，避免重复API请求）
        this.loadMendianList();
    },
    methods: {
        // 构建门店名称列表
        buildStoreNames: function(list) {
            var names = ['全部门店'];
            for (var i = 0; i < list.length; i++) {
                names.push(list[i].name || ('门店' + (i+1)));
            }
            return names;
        },
        // 获取门店列表
        loadMendianList: function() {
            var that = this;
            // 1. 优先从 app.globalData 读取（business/index 跳转时写入）
            if (app.globalData.batch_portrait_mendianlist && app.globalData.batch_portrait_mendianlist.length > 0) {
                that.mendianlist = app.globalData.batch_portrait_mendianlist;
                that.storeNames = that.buildStoreNames(that.mendianlist);
                return;
            }
            // 2. 其次从上一页获取
            var pages = getCurrentPages();
            if (pages.length >= 2) {
                var prevPage = pages[pages.length - 2];
                if (prevPage && prevPage.mendianlist && prevPage.mendianlist.length > 0) {
                    that.mendianlist = prevPage.mendianlist;
                    that.storeNames = that.buildStoreNames(that.mendianlist);
                    return;
                }
            }
            // 3. 调用专用门店接口 ApiMendian/mendianlist
            app.post('ApiMendian/mendianlist', {
                bid: that.bid,
                latitude: that.latitude || '',
                longitude: that.longitude || ''
            }, function(resp) {
                if (resp.status == 1 && resp.data && resp.data.length > 0) {
                    that.mendianlist = resp.data;
                    that.storeNames = that.buildStoreNames(resp.data);
                }
            });
        },

        // 门店选择
        onStoreChange: function(e) {
            var index = e.detail.value;
            this.storeIndex = index;
            if (index === 0) {
                this.selectedMendianId = 0;
            } else {
                var mendian = this.mendianlist[index - 1];
                this.selectedMendianId = mendian ? mendian.id : 0;
            }
        },

        // 选择图片
        chooseImages: function() {
            var that = this;
            var remain = 20 - that.imageList.length;
            uni.chooseImage({
                count: remain,
                sizeType: ['compressed'],
                sourceType: ['album', 'camera'],
                success: function(res) {
                    that.imageList = that.imageList.concat(res.tempFilePaths);
                }
            });
        },

        // 删除图片
        removeImage: function(index) {
            this.imageList.splice(index, 1);
        },

        // 开始上传
        startUpload: function() {
            var that = this;
            this.uploading = true;
            this.uploadCurrent = 0;
            this.uploadTotal = this.imageList.length;
            this.uploadProgress = 0;
            this.uploadResult = { success_count: 0, fail_count: 0 };

            // 逐个上传
            this.uploadNext(0);
        },

        // 逐张上传
        uploadNext: function(index) {
            var that = this;
            if (index >= that.imageList.length) {
                // 全部上传完成
                that.uploading = false;
                that.uploadProgress = 100;
                
                if (that.uploadResult.success_count > 0) {
                    // 自动触发批量合成
                    that.startSynthesis();
                } else {
                    that.showResult = true;
                    that.synthResultMsg = '没有上传成功的图片，无法进行批量合成';
                }
                return;
            }

            that.uploadCurrent = index + 1;
            that.uploadProgress = Math.floor((index / that.imageList.length) * 100);

            uni.uploadFile({
                url: app.globalData.baseurl + 'ApiBusiness/batch_portrait_upload/aid/' + app.globalData.aid + '/platform/' + app.globalData.platform + '/session_id/' + (app.globalData.session_id || ''),
                filePath: that.imageList[index],
                name: 'images',
                formData: {
                    bid: that.bid,
                    mdid: that.selectedMendianId
                },
                success: function(uploadRes) {
                    try {
                        var data = JSON.parse(uploadRes.data);
                        if (data.code === 200) {
                            that.uploadResult.success_count++;
                        } else {
                            that.uploadResult.fail_count++;
                        }
                    } catch(e) {
                        that.uploadResult.fail_count++;
                    }
                },
                fail: function() {
                    that.uploadResult.fail_count++;
                },
                complete: function() {
                    // 继续上传下一张
                    setTimeout(function() {
                        that.uploadNext(index + 1);
                    }, 300);
                }
            });
        },

        // 批量合成
        startSynthesis: function() {
            var that = this;
            this.synthesizing = true;
            this.synthResultMsg = '';

            app.post('ApiBusiness/batch_portrait_synthesis', {
                bid: that.bid
            }, function(res) {
                that.synthesizing = false;
                that.showResult = true;
                if (res.code === 200) {
                    var data = res.data || {};
                    if (data.processing_count > 0) {
                        that.synthResultMsg = res.msg || '人像已自动进入合成队列，正在处理中';
                    } else {
                        that.synthResultMsg = '批量合成已完成，成功 ' + (data.success_count || 0) + ' 组';
                    }
                } else {
                    that.synthResultMsg = res.msg || '合成任务提交异常';
                }
            }, function() {
                that.synthesizing = false;
                that.showResult = true;
                that.synthResultMsg = '批量合成请求失败，请稍后重试';
            });
        },

        // 返回上一页
        goBack: function() {
            uni.navigateBack();
        }
    }
}
</script>

<style scoped>
.page-container {
    min-height: 100vh;
    background-color: #f5f5f5;
}
.container {
    padding: 20rpx;
}
.section {
    background: #fff;
    border-radius: 16rpx;
    padding: 30rpx;
    margin-bottom: 20rpx;
}
.section-title {
    font-size: 30rpx;
    font-weight: bold;
    color: #333;
    margin-bottom: 20rpx;
}
.picker-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 80rpx;
    padding: 0 24rpx;
    background: #f8f8f8;
    border-radius: 12rpx;
    border: 1px solid #e8e8e8;
}
.picker-text {
    font-size: 28rpx;
    color: #333;
}
.picker-arrow {
    width: 24rpx;
    height: 24rpx;
}
.image-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
}
.image-item {
    position: relative;
    width: 200rpx;
    height: 200rpx;
    border-radius: 12rpx;
    overflow: hidden;
    border: 1px solid #e8e8e8;
}
.image-preview {
    width: 100%;
    height: 100%;
}
.image-delete {
    position: absolute;
    top: 4rpx;
    right: 4rpx;
    width: 40rpx;
    height: 40rpx;
    background: rgba(0,0,0,0.6);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.delete-icon {
    color: #fff;
    font-size: 32rpx;
    line-height: 1;
}
.image-add {
    width: 200rpx;
    height: 200rpx;
    border: 2px dashed #ccc;
    border-radius: 12rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #fafafa;
}
.add-icon {
    font-size: 60rpx;
    color: #999;
    line-height: 1;
}
.add-text {
    font-size: 22rpx;
    color: #999;
    margin-top: 8rpx;
}
.progress-bar-wrap {
    width: 100%;
    height: 20rpx;
    background: #e8e8e8;
    border-radius: 10rpx;
    overflow: hidden;
    margin-bottom: 12rpx;
}
.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #4CAF50, #8BC34A);
    border-radius: 10rpx;
    transition: width 0.3s ease;
}
.progress-text {
    font-size: 26rpx;
    color: #666;
    text-align: center;
}
.synth-status {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20rpx 0;
}
.loading-spinner {
    width: 40rpx;
    height: 40rpx;
    border: 4rpx solid #e8e8e8;
    border-top-color: #4CAF50;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-right: 16rpx;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
.synth-text {
    font-size: 28rpx;
    color: #666;
}
.action-bar {
    padding: 30rpx 0;
}
.btn-upload {
    width: 100%;
    height: 88rpx;
    line-height: 88rpx;
    background: linear-gradient(90deg, #4CAF50 0%, #8BC34A 100%);
    color: #fff;
    font-size: 32rpx;
    border-radius: 44rpx;
    border: none;
    text-align: center;
}
.btn-upload[disabled] {
    background: #ccc;
    color: #999;
}
.result-mask {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 999;
}
.result-dialog {
    width: 580rpx;
    background: #fff;
    border-radius: 24rpx;
    padding: 50rpx 40rpx;
    text-align: center;
}
.result-icon {
    width: 80rpx;
    height: 80rpx;
    line-height: 80rpx;
    background: #4CAF50;
    color: #fff;
    font-size: 48rpx;
    border-radius: 50%;
    margin: 0 auto 24rpx;
}
.result-title {
    font-size: 34rpx;
    font-weight: bold;
    color: #333;
    margin-bottom: 16rpx;
}
.result-content {
    font-size: 28rpx;
    color: #666;
    margin-bottom: 12rpx;
}
.btn-confirm {
    width: 400rpx;
    height: 80rpx;
    line-height: 80rpx;
    background: linear-gradient(90deg, #4CAF50 0%, #8BC34A 100%);
    color: #fff;
    font-size: 30rpx;
    border-radius: 40rpx;
    border: none;
    margin-top: 30rpx;
}
</style>
