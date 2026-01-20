<template>
  <view class="container">
    <view class="header">
      <text class="title">送货单</text>
    </view>
    <!-- 横向滚动表格区域 -->
    <scroll-view class="table-container" scroll-x="true">
      <view class="table" id="table-content">
        <!-- 表头 -->
        <view class="table-row header-row">
          <view class="table-cell">序号</view>
          <view class="table-cell">品名及规格</view>
          <view class="table-cell">数量</view>
          <view class="table-cell">单价</view>
          <view class="table-cell">金额</view>
          <view class="table-cell">备注</view>
        </view>

        <!-- 表格内容 -->
        <view class="table-row" v-for="(item, index) in tableData" :key="index">
          <view class="table-cell">{{ item.id }}</view>
          <view class="table-cell product-cell">{{ item.product }}</view>
          <view class="table-cell">{{ item.quantity }}</view>
          <view class="table-cell">{{ item.price }}</view>
          <view class="table-cell">{{ item.amount }}</view>
          <view class="table-cell">{{ item.remark }}</view>
        </view>
      </view>
    </scroll-view>

    <!-- 合计金额区域 -->
    <view class="total-section">
      <view class="total-row">
        <text class="total-label">商品金额：</text>
        <text class="total-value">{{ productAmount }}({{ productAmountChinese }})</text>
      </view>
      <view class="total-row">
        <text class="total-label">实付金额：</text>
        <text class="total-value">{{ totalAmount }}({{ totalAmountChinese }})</text>
      </view>
    </view>

    <!-- 操作按钮 -->
    <view class="action-buttons">
      <button class="save-btn" @tap="saveAsImage">保存为图片</button>
    </view>

    <!-- 保存提示 -->
    <view class="save-tips" v-if="showTips">
      <text>图片已保存到相册</text>
    </view>

    <!-- 隐藏的Canvas用于生成图片 -->
    <canvas
        canvas-id="myCanvas"
        id="myCanvas"
        class="hidden-canvas"
        :style="{ width: canvasWidth + 'px', height: canvasHeight + 'px' }"
    ></canvas>
    <!-- H5环境下的图片预览和下载 -->
    <view v-if="showImagePreview" class="image-preview-mask" @tap="closeImagePreview">
      <view class="image-preview-content" @tap.stop>
        <image :src="previewImageUrl" mode="widthFix" class="preview-image"></image>
        <view class="preview-actions">
          <button class="preview-btn" @tap="downloadImageInH5">长按图片保存到手机</button>
          <button class="preview-btn close-btn" @tap="closeImagePreview">关闭</button>
        </view>
      </view>
    </view>

    <loading v-if="loading"></loading>
    <nomore v-if="nomore"></nomore>
    <nodata v-if="nodata"></nodata>
    <dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
    <popmsg ref="popmsg"></popmsg>
  </view>
</template>

<script>
var app = getApp();
export default {
  data() {
    return {
      pre_url:app.globalData.pre_url,
      opt:{},
      loading:false,
      isload: false,
      nomore: false,
      nodata: false,
      tableData: [],
      totalAmount: "",
      totalAmountChinese: "",
      productAmount: "",
      productAmountChinese:"",
      showTips: false,
      canvasWidth: 750,
      canvasHeight: 1000,
      showImagePreview: false,
      previewImageUrl: "",
      tempImagePath: "",
      isWechatBrowser: false
    }
  },
  onLoad(opt) {
    let that = this;
    that.opt = app.getopts(opt);
    that.mid = that.opt.mid ? that.opt.mid  : '';
    that.id = that.opt.id ? that.opt.id  : '';

    // 检测是否在微信浏览器中
    that.isWechatBrowser = that.checkWechatBrowser();

    that.getData();
  },
  methods: {
    // 检测是否为微信浏览器
    // 检测是否为微信浏览器
    checkWechatBrowser() {
      try {
        // #ifdef H5
        if (typeof navigator !== 'undefined' && navigator.userAgent) {
          const ua = navigator.userAgent.toLowerCase();
          return ua.indexOf('micromessenger') !== -1;
        }
        // #endif
        return false;
      } catch (error) {
        console.warn('检测浏览器类型失败:', error);
        return false;
      }
    },

    getData: function (mid) {
      var that = this;
      that.loading = true;
      app.post('ApiAdminOrder/shdImgNew', {id: that.id}, function (res) {
        that.loading = false;
        if(res.status == 0){
          app.error(res.msg);
          setTimeout(function () {
            //app.goback();
          }, 1500)
          return;
        }else {
          var tableData = res.data;
          that.tableData = tableData;
          that.totalAmount =  res.totalAmount || '';
          that.totalAmountChinese =  res.totalAmountChinese || '';
          that.productAmount =  res.productAmount || '';
          that.productAmountChinese =  res.productAmountChinese || '';
        }

      });
    },
    async saveAsImage() {
      // 显示加载中
      uni.showLoading({
        title: '生成图片中...'
      });

      try {
        // 计算表格尺寸
        const tableSize = await this.calculateTableSize();

        // 设置Canvas尺寸
        this.canvasWidth = tableSize.width;
        this.canvasHeight = tableSize.height;

        // 等待Canvas初始化
        await this.$nextTick();

        // 创建Canvas上下文
        const ctx = uni.createCanvasContext('myCanvas', this);

        // 绘制背景
        ctx.setFillStyle('#ffffff');
        ctx.fillRect(0, 0, this.canvasWidth, this.canvasHeight);

        // 绘制标题
        ctx.setFillStyle('#333333');
        ctx.setFontSize(20);
        ctx.setTextAlign('center');
        ctx.fillText('送货单', this.canvasWidth / 2, 40);

        // 绘制表格
        await this.drawTable(ctx, tableSize);

        // 绘制完成
        ctx.draw(false, () => {
          // 延迟确保绘制完成
          setTimeout(() => {
            this.canvasToImage();
          }, 500);
        });

      } catch (error) {
        console.error('生成图片失败:', error);
        uni.hideLoading();
        uni.showToast({
          title: '生成图片失败',
          icon: 'none'
        });
      }
    },

    // 计算表格尺寸
    calculateTableSize() {
      return new Promise((resolve) => {
        const ctx = uni.createCanvasContext('myCanvas', this);

        // 表头
        const headers = ['序号', '品名及规格', '数量', '单价', '金额', '备注'];

        // 计算每列的最大宽度
        const columnWidths = [60, 0, 60, 80, 80, 80]; // 初始宽度

        // 测量表头宽度
        headers.forEach((header, index) => {
          const metrics = ctx.measureText(header);
          const width = metrics.width + 20; // 加上内边距
          if (width > columnWidths[index]) {
            columnWidths[index] = width;
          }
        });

        // 测量内容宽度
        this.tableData.forEach(row => {
          // 序号列
          const idWidth = ctx.measureText(row.id.toString()).width + 20;
          if (idWidth > columnWidths[0]) columnWidths[0] = idWidth;

          // 品名列 - 需要处理换行
          const productLines = this.getTextLines(ctx, row.product, 300);
          const productWidth = 300; // 固定宽度，因为需要换行
          if (productWidth > columnWidths[1]) columnWidths[1] = productWidth;

          // 数量列
          const quantityWidth = ctx.measureText(row.quantity.toString()).width + 20;
          if (quantityWidth > columnWidths[2]) columnWidths[2] = quantityWidth;

          // 单价列
          const priceWidth = ctx.measureText(row.price).width + 20;
          if (priceWidth > columnWidths[3]) columnWidths[3] = priceWidth;

          // 金额列
          const amountWidth = ctx.measureText(row.amount ? row.amount.toString() : '').width + 20;
          if (amountWidth > columnWidths[4]) columnWidths[4] = amountWidth;

          // 备注列 - 处理长文本，设置最大宽度为150，超过则换行
          const remarkLines = this.getTextLines(ctx, row.remark, 150);
          const remarkWidth = Math.min(150, ctx.measureText(row.remark).width + 20);
          if (remarkWidth > columnWidths[5]) columnWidths[5] = remarkWidth;
        });

        // 计算总宽度
        const totalWidth = columnWidths.reduce((sum, width) => sum + width, 0) + 40;

        // 计算行高
        let totalHeight = 120; // 标题和表头高度

        this.tableData.forEach(row => {
          const productLines = this.getTextLines(ctx, row.product, columnWidths[1] - 10);
          const remarkLines = this.getTextLines(ctx, row.remark, columnWidths[5] - 10);
          const lineHeight = 16;
          const maxLines = Math.max(productLines.length, remarkLines.length);
          const rowHeight = Math.max(30, maxLines * lineHeight + 10);
          totalHeight += rowHeight;
        });

        // 添加合计区域高度
        totalHeight += 80;

        resolve({
          width: totalWidth,
          height: totalHeight,
          columnWidths: columnWidths
        });
      });
    },

    // 获取文本行数
    getTextLines(ctx, text, maxWidth) {
      if (!text) return [''];

      const words = text.split('');
      const lines = [];
      let currentLine = '';

      for (let i = 0; i < words.length; i++) {
        const testLine = currentLine + words[i];
        const metrics = ctx.measureText(testLine);

        if (metrics.width > maxWidth && currentLine !== '') {
          lines.push(currentLine);
          currentLine = words[i];
        } else {
          currentLine = testLine;
        }
      }

      if (currentLine) {
        lines.push(currentLine);
      }

      return lines;
    },

    async drawTable(ctx, tableSize) {
      return new Promise((resolve) => {
        // 表格起始位置
        const startX = 20;
        const startY = 70;

        // 列宽
        const columnWidths = tableSize.columnWidths;
        const headers = ['序号', '品名及规格', '数量', '单价', '金额', '备注'];

        // 表头背景
        ctx.setFillStyle('#f0f0f0');
        ctx.fillRect(startX, startY, tableSize.width - 40, 30);

        // 表头文字
        ctx.setFillStyle('#333333');
        ctx.setFontSize(14);
        ctx.setTextAlign('center');

        let xPos = startX;

        // 绘制表头
        for (let i = 0; i < headers.length; i++) {
          ctx.setStrokeStyle('#333333');
          ctx.setLineWidth(1);
          ctx.strokeRect(xPos, startY, columnWidths[i], 30);
          ctx.fillText(headers[i], xPos + columnWidths[i] / 2, startY + 15);
          xPos += columnWidths[i];
        }

        // 绘制表格内容
        let yPos = startY + 30;

        this.tableData.forEach((row, index) => {
          xPos = startX;

          // 计算品名列和备注列的行数
          const productLines = this.getTextLines(ctx, row.product, columnWidths[1] - 10);
          const remarkLines = this.getTextLines(ctx, row.remark, columnWidths[5] - 10);
          const lineHeight = 16;
          const maxLines = Math.max(productLines.length, remarkLines.length);
          const rowHeight = Math.max(30, maxLines * lineHeight + 10);

          // 交替行背景色
          if (index % 2 === 0) {
            ctx.setFillStyle('#f9f9f9');
          } else {
            ctx.setFillStyle('#ffffff');
          }
          ctx.fillRect(startX, yPos, tableSize.width - 40, rowHeight);

          ctx.setFillStyle('#333333');
          ctx.setFontSize(12);

          // 序号
          ctx.setStrokeStyle('#333333');
          ctx.strokeRect(xPos, yPos, columnWidths[0], rowHeight);
          ctx.setTextAlign('center');
          ctx.fillText(row.id.toString(), xPos + columnWidths[0] / 2, yPos + rowHeight / 2);
          xPos += columnWidths[0];

          // 品名及规格 - 左对齐，支持多行
          ctx.strokeRect(xPos, yPos, columnWidths[1], rowHeight);
          ctx.setTextAlign('left');
          let textY = yPos + 15;
          productLines.forEach(line => {
            ctx.fillText(line, xPos + 5, textY);
            textY += lineHeight;
          });
          ctx.setTextAlign('center');
          xPos += columnWidths[1];

          // 数量
          ctx.strokeRect(xPos, yPos, columnWidths[2], rowHeight);
          ctx.fillText(row.quantity.toString(), xPos + columnWidths[2] / 2, yPos + rowHeight / 2);
          xPos += columnWidths[2];

          // 单价
          ctx.strokeRect(xPos, yPos, columnWidths[3], rowHeight);
          ctx.fillText(row.price, xPos + columnWidths[3] / 2, yPos + rowHeight / 2);
          xPos += columnWidths[3];

          // 金额
          ctx.strokeRect(xPos, yPos, columnWidths[4], rowHeight);
          ctx.fillText(row.amount ? row.amount.toString() : '', xPos + columnWidths[4] / 2, yPos + rowHeight / 2);
          xPos += columnWidths[4];

          // 备注 - 支持多行文本，左对齐
          ctx.strokeRect(xPos, yPos, columnWidths[5], rowHeight);
          ctx.setTextAlign('left');
          textY = yPos + 15;
          remarkLines.forEach(line => {
            ctx.fillText(line, xPos + 5, textY);
            textY += lineHeight;
          });
          ctx.setTextAlign('center');

          yPos += rowHeight;
        });

        // 绘制合计金额
        yPos += 20;
        ctx.setFillStyle('#333333');
        ctx.setFontSize(14);
        ctx.setTextAlign('left');

        // 小写金额
        ctx.fillText(`商品金额：   ${this.productAmount}(${this.productAmountChinese})`, startX, yPos);
        yPos += 25;

        // 大写金额
        ctx.fillText(`实付金额：   ${this.totalAmount}(${this.totalAmountChinese})`, startX, yPos);

        resolve();
      });
    },

    canvasToImage() {
      // 将Canvas转换为临时图片
      uni.canvasToTempFilePath({
        canvasId: 'myCanvas',
        quality: 1, // 最高质量
        success: (res) => {
          this.tempImagePath = res.tempFilePath;

          // 判断平台
          // #ifdef H5
          this.handleH5Save(res.tempFilePath);
          // #endif

          // #ifdef MP-WEIXIN
          this.saveImageToAlbum(res.tempFilePath);
          // #endif
        },
        fail: (err) => {
          uni.hideLoading();
          console.error('Canvas转换失败:', err);
          uni.showToast({
            title: '生成图片失败',
            icon: 'none',
            duration: 2000
          });
        }
      }, this);
    },

    // H5环境保存处理
    handleH5Save(tempFilePath) {
      this.previewImageUrl = tempFilePath;

      // 微信浏览器中显示预览，提示用户长按保存
      this.showImagePreview = true;
      this.saveTipsText = '图片已生成，请长按图片保存';

      // if (this.isWechatBrowser) {
      //   // 微信浏览器中显示预览，提示用户长按保存
      //   this.showImagePreview = true;
      //   this.saveTipsText = '图片已生成，请长按图片保存';
      // } else {
      //   // 其他浏览器直接下载
      //   this.downloadImageInH5();
      // }

      uni.hideLoading();
    },

    // 保存图片到相册
    saveImageToAlbum(tempFilePath) {
      uni.saveImageToPhotosAlbum({
        filePath: tempFilePath,
        success: () => {
          uni.hideLoading();
          this.saveTipsText = '图片已保存到相册';
          this.showTips = true;
          setTimeout(() => {
            this.showTips = false;
          }, 2000);
        },
        fail: (err) => {
          uni.hideLoading();
          console.error('保存失败:', err);

          // 处理权限问题
          if (err.errMsg && err.errMsg.indexOf('auth deny') !== -1) {
            uni.showModal({
              title: '提示',
              content: '需要相册权限才能保存图片，请在设置中开启权限',
              showCancel: false
            });
          } else {
            uni.showToast({
              title: '保存失败，请检查权限设置',
              icon: 'none'
            });
          }
        }
      });
    },

    // H5环境下载图片
    downloadImageInH5() {
      try {
        // 创建一个虚拟的a标签用于下载
        const a = document.createElement('a');
        a.href = this.previewImageUrl;
        a.download = `送货单_${new Date().getTime()}.png`;
        a.style.display = 'none';

        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);

        this.saveTipsText = '图片已开始下载';
        this.showTips = true;
        setTimeout(() => {
          this.showTips = false;
        }, 3000);

        // 非微信浏览器下载后关闭预览
        if (!this.isWechatBrowser) {
          this.closeImagePreview();
        }
      } catch (error) {
        console.error('下载失败:', error);
        // 下载失败时显示预览
        this.showImagePreview = true;
        this.saveTipsText = '下载失败，请长按图片保存';
      }
    },

    // 关闭图片预览
    closeImagePreview() {
      this.showImagePreview = false;
      this.previewImageUrl = "";
    }
  }
}
</script>

<style scoped>
.container {
  padding: 20rpx;
  background-color: #ffffff;
  min-height: 100vh;
}

.header {
  text-align: center;
  margin-bottom: 30rpx;
}

.title {
  font-size: 36rpx;
  font-weight: bold;
  color: #333333;
}

.table-container {
  width: 100%;
  margin-bottom: 40rpx;
}

.table {
  display: table;
  width: auto;
  border-collapse: collapse;
  min-width: 100%;
}

.table-row {
  display: table-row;
}

.table-cell {
  display: table-cell;
  padding: 20rpx 15rpx;
  border: 1rpx solid #e0e0e0;
  text-align: center;
  vertical-align: middle;
  min-width: 120rpx;
  max-width: 450rpx;
  word-break: break-word;
}

.header-row .table-cell {
  background-color: #f5f5f5;
  font-weight: bold;
  color: #333333;
}

.product-cell {
  text-align: left;
  max-width: 450rpx;
  min-width: 450rpx;
}

.total-section {
  margin-top: 40rpx;
  padding: 0 20rpx;
}

.total-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 20rpx;
}

.total-label {
  font-size: 28rpx;
  color: #333333;
}

.total-value {
  font-size: 28rpx;
  color: #333333;
  font-weight: bold;
}

.action-buttons {
  margin-top: 60rpx;
  display: flex;
  justify-content: center;
}

.save-btn {
  background-color: #007AFF;
  color: #ffffff;
  width: 80%;
  border-radius: 10rpx;
}

.save-tips {
  position: fixed;
  bottom: 100rpx;
  left: 50%;
  transform: translateX(-50%);
  background-color: rgba(0, 0, 0, 0.7);
  color: #ffffff;
  padding: 20rpx 40rpx;
  border-radius: 10rpx;
  font-size: 28rpx;
}

.hidden-canvas {
  position: fixed;
  top: -10000rpx;
  left: -10000rpx;
}
/* H5图片预览样式 */
.image-preview-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.7);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9998;
}

.image-preview-content {
  background-color: #ffffff;
  border-radius: 10rpx;
  padding: 30rpx;
  max-width: 90%;
  max-height: 90%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.preview-image {
  max-width: 100%;
  margin-bottom: 30rpx;
}

.preview-actions {
  display: flex;
  justify-content: center;
  gap: 20rpx;
}

.preview-btn {
  background-color: #007AFF;
  color: #ffffff;
  padding: 15rpx 30rpx;
  border-radius: 8rpx;
  font-size: 28rpx;
}

.close-btn {
  background-color: #999999;
}
</style>