<template>
<view class="myBtaPadding">
	<view>
		<view style="position:fixed;top:100rpx;left:-10rpx;width:160rpx;border:1px solid #f5f5f5;border-radius:40rpx;height:80rpx;line-height:80rpx;background:#fff;color:#333;text-align:center;transform: rotate(90deg);" @tap="goback">返回</view>
		<view style="position:fixed;bottom:260rpx;left:-10rpx;width:160rpx;border:1px solid #f5f5f5;border-radius:40rpx;height:80rpx;line-height:80rpx;background:#fff;color:#333;text-align:center;transform: rotate(90deg);"  @click="clear">重新签名</view>
		<view style="position:fixed;bottom:70rpx;left:-10rpx;width:160rpx;border:1px solid #f5f5f5;border-radius:40rpx;height:80rpx;line-height:80rpx;background:#fff;color:#fff;text-align:center;transform: rotate(90deg);" :style="{background:t('color1')}"  @click="sumbit">确认</view>
	</view>
	<view class="htz-signature-body">
		<!-- <view class="title">请在下方区域签写姓名</view> -->
		<canvas canvas-id="canvas" id="canvas" @touchstart="touchstart" @touchmove="touchmove"	@touchend="touchend"></canvas>
		<!-- <view class="clear"  @click="clear"><image src="/static/img/refsh.png"><text>清除内容</text></view>
		<view class="htz-signature-fixed-bottom">
			<view class="htz-signature-fixed-bottom-item sumbit" @click="sumbit" :style="{background:t('color1')}">确定签字</view>
		</view> -->
	</view>
</view>
</template>

<script>
var app = getApp();
export default {
	data() {
		return {
			id: '',
			Strokes: [],
			dom: null,
			width: 0,
			height: 0,
			signatureurl:'',
			opt:{},
      mid:app.globalData.mid,
		}
	},
	onLoad: function(opt) {
    this.opt = app.getopts(opt);
		// #ifdef H5
		document.body.addEventListener('touchmove', this.touchmoveEnd, {
			passive: false
		});
		// #endif
		uni.getSystemInfo({
			success: (res) => {
				this.width = res.windowWidth;
				this.height = res.windowHeight;
			}
		});
		this.dom = uni.createCanvasContext('canvas', this);
	},
  onPullDownRefresh: function() {
    // 停止下拉刷新
    uni.stopPullDownRefresh();
  },
	onUnload: function() {
		// #ifdef H5
		document.body.removeEventListener('touchmove', this.touchmoveEnd, {
			passive: false
		})
		// #endif
	},
	methods: {
		confirm:function(){
			var that = this;
			var orderid = this.opt.id;
			var signatureurl = this.signatureurl;
			app.setCache(that.mid+'htsignatureurl',signatureurl);
			/*app.showLoading('提交中');
			app.post('ApiHotel/signature',{signatureurl:signatureurl}, function(res) {
				app.showLoading(false);
				if (res.status == 0) {
					app.error(res.msg);
					return;
				}
			});*/
			that.goback2(true);
		},
		goback2: function(isreload) {
			var app = this;
			var pages = getCurrentPages();
			if (isreload && pages.length > 1) {
				var prePage = pages[pages.length - 2];
				prePage.$vm.htsignatureurl=this.signatureurl
				prePage.$vm.isagree=1;
        prePage.$vm.hidehtqm(this.signatureurl);
			}

			if (pages.length == 1) {
				app.goto(app.globalData.indexurl, 'reLaunch');
			} else {
				uni.navigateBack({
					fail: function() {
						app.goto(app.globalData.indexurl, 'reLaunch');
					}
				});
			}
		},
		touchmoveEnd(e) {
			e.preventDefault();
			e.stopPropagation();
		},
		sumbit(){
			var that = this;
			console.log(that.Strokes);
			if((that.Strokes).length == 0){
				app.error('请先签字');return;
			}
			uni.canvasToTempFilePath({
				canvasId: 'canvas',
				success: (res) => {
					console.log('success', res);
					uni.uploadFile({
						url: app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app.globalData.aid + '/platform/' + app.globalData.platform + '/session_id/' + app.globalData.session_id+'/xuanzhuan/1/hotelqz/1',
						filePath: res.tempFilePath,
						name: 'file',
						formData: {
							// 'user': 'test'
						},
						success: (uploadFileRes) => {
							//console.log(uploadFileRes.data);
							// 判断是否json字符串，将其转为json格式
							//let data = _this.$u.test.jsonString(uploadFileRes.data) ? JSON.parse(uploadFileRes.data) : uploadFileRes.data;
							var data =  JSON.parse(uploadFileRes.data)
							that.signatureurl = data.url;
							that.confirm();
						}
					})
				},
				fail: (err) => {
					console.log('fail', err)
				}
			}, this);
		},
		clear() { //清空
			this.Strokes = [];
			this.dom.clearRect(0, 0, this.width, this.height)
			this.dom.draw();
		},
		touchstart(e) {
			this.Strokes.push({
				imageData: null,
				style: {
					color: '#000000',
					lineWidth: 6,
				},
				points: [{
					x: e.touches[0].x,
					y: e.touches[0].y,
					type: e.type,
				}]
			})
			this.drawLine(this.Strokes[this.Strokes.length - 1], e.type);
		},
		touchmove(e) {
			this.Strokes[this.Strokes.length - 1].points.push({
				x: e.touches[0].x,
				y: e.touches[0].y,
				type: e.type,
			})
			this.drawLine(this.Strokes[this.Strokes.length - 1], e.type);
		},
		touchend(e) {
			if (this.Strokes[this.Strokes.length - 1].points.length < 2) { //当此路径只有一个点的时候
				this.Strokes.pop();
			}
		},
		drawLine(StrokesItem, type) {
			if (StrokesItem.points.length > 1) {
				this.dom.beginPath();
				this.dom.setLineCap('round')
				this.dom.setStrokeStyle(StrokesItem.style.color);
				this.dom.setLineWidth(StrokesItem.style.lineWidth);
				this.dom.moveTo(StrokesItem.points[StrokesItem.points.length - 2].x, StrokesItem.points[StrokesItem
					.points.length -
					2].y);
				this.dom.lineTo(StrokesItem.points[StrokesItem.points.length - 1].x, StrokesItem.points[StrokesItem
					.points.length -
					1].y);
				this.dom.stroke();
				this.dom.draw(true);
			}
		}
	}
}
</script>

<style>
	.title{ height: 100rpx;line-height: 100rpx; text-align: center;}
	.clear{ text-align: center;  height: 100rpx; line-height: 100rpx;color: #999; display: flex;align-items: center; justify-content: center;}
	.clear image{ width: 40rpx; height: 40rpx;  margin-right: 10rpx;}
	
	.htz-signature-body {
		position: fixed;
		top: 0;
		bottom: 120rpx;
		left: 15%;
		width: 100%;
	}

	.htz-signature-body canvas {
		width: 85%;
		height: 100vh;
		background: #fff;
		margin:0 20rpx;
		border-radius: 10rpx;
	}

	.htz-signature-fixed-bottom {
		position: fixed;
		bottom: 0;
		left: 0;
		width: 100%;
	
		text-align: center;
		color: #000;
		z-index: 11;
		display: -webkit-box;
		display: -webkit-flex;
		display: flex;
		background-color: #fff;
		justify-content: center;
		padding: 30rpx 0;
	}
	.htz-signature-fixed-bottom .htz-signature-fixed-bottom-item {
		background: #1890ff;
		color: #fff;
		height: 80rpx; width: 80%;
		line-height: 80rpx;
		border-radius: 50rpx; ;
	}
	.htz-signature-fixed-bottom-item view image {
		width: 50rpx;
		height: 50rpx;
		padding-top: 10rpx;
	}
  .myBtaPadding {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100vh;
    overflow: hidden;
  }

</style>
