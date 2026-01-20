<template>
	<view>
		<block v-if="isload">
			<view class="navigation" :style="'top:' + topBtn +'px'">
				<img :src="pre_url+'/static/img/diylight_n1.png'" class="navigation_item" alt="" @tap="goback">
				<img :src="pre_url+'/static/img/diylight_n2.png'" class="navigation_item" alt="" @tap="goto"
					:data-url="'/pages/shop/cart'">
				<img :src="pre_url+'/static/img/diylight_n3.png'" class="navigation_item" alt="" @tap="uploadImg()">
				<img :src="pre_url+'/static/img/diylight_n4.png'" class="navigation_item" alt="" @tap="draw">
				<button open-type="share" v-if="getplatform() == 'wx'" class="navigation_icon"><img
						:src="pre_url+'/static/img/diylight_n5.png'" class="navigation_item" alt=""></button>
			</view>
			<view v-for="(item,index) in dataList" :key="index"
				:style="{top: item.y - item.size / 2 +'px',left:item.x - item.size / 2 +'px',height:item.size+'px',width:item.size+'px',transform: 'rotate(' + item.rotate + 'deg)'}"
				class="move_module move_item" @click="moveClick(index)" :class="item.active?'move_active':''">
				<img :src="item.url[item.lightIndex]" class="move_image" alt="" @touchstart='siteStart($event,index)'
					@touchmove='siteMove($event,index)' @touchend='siteEnd($event,index)'>
				<view v-if="item.active" @touchstart='spinStart($event,index)' @touchmove='spinMove($event,index)'
					@touchend='spinEnd($event,index)' class="move_spin">
					<img :src="pre_url+'/static/img/diylight_spin.png'" alt="">
				</view>
				<view v-if="item.active" class="move_close" @click="moveDelete(index)">
					<img :src="pre_url+'/static/img/diylight_close.png'" alt="">
				</view>
				<view v-if="item.active" @touchstart='sizeStart($event,index)' @touchmove='sizeMove($event,index)'
					@touchend='sizeEnd($event,index)' class="move_size">
					<img :src="pre_url+'/static/img/diylight_size.png'" alt="">
				</view>
			</view>
			<view class="table" :class="menuindex>-1?'table_opt':''">
				<view @click="sceneClick" class="table_item">
					<img class="table_icon" :src="pre_url+'/static/img/diylight_image.png'" alt="">更换场景
				</view>
				<view @click="lightClick" class="table_item">
					<img class="table_icon" :src="pre_url+'/static/img/diylight_lamp.png'" alt="">选择产品
				</view>
				<view v-if="switchState" @click="switchClick" class="table_item">
					<img class="table_icon" :src="pre_url+'/static/img/diylight_closeL.png'" alt="">关灯
				</view>
				<view v-if="!switchState" @click="switchClick" class="table_item">
					<img class="table_icon" :src="pre_url+'/static/img/diylight_openL.png'" alt="">开灯
				</view>
			</view>
			<view v-if="!switchState" class="page_cut"></view>
			<view v-if="sceneState" class="alert">
				<view @click="sceneClick" class="alert_hide"></view>
				<view class="alert_module" :class="menuindex>-1?'alert_opt':''">
					<view class="alert_head">
						<view class="alert_btn" @tap="uploadImg">
							+上传场景
						</view>
						<img @click="sceneClick" class="alert_close" :src="pre_url+'/static/img/diylight_closeA.png'"
							alt="">
					</view>
					<scroll-view scroll-y="true" class="alert_content" @scrolltolower="moreScene">
						<view v-for="(item,index) in sceneList" :key="index" v-if="sceneList.length"
							@click="sceneChoose(item)" class="alert_item">
							<img :src="item" mode="widthFix" alt="">
						</view>
						<nodata text="没有查找到相关商品" type='small' v-if="!sceneList.length"></nodata>
					</scroll-view>
				</view>
			</view>
			<view v-if="lightState" class="alert">
				<view @click="lightClick" class="alert_hide"></view>
				<view class="alert_module" :class="menuindex>-1?'alert_opt':''">
					<view class="alert_title">
						<text>选择产品</text>
						<img @click="lightClick" class="alert_close" :src="pre_url+'/static/img/diylight_closeA.png'"
							alt="">
					</view>
					<scroll-view scroll-x="true" class="alert_table">
						<view v-for="(item,index) in tableList" :key="index" @click="tableClick(index)"
							:class="tableIndex==index?'alert_active':''">
							{{item.name}}
						</view>
					</scroll-view>
					<scroll-view scroll-y="true" class="alert_content" @scrolltolower="moreLight">
						<view v-for="(item,index) in lightList" :key="index" @click="lightChoose(item)"
							class="alert_item">
							<img :src="item.url[0]" mode="widthFix" alt="">
						</view>
						<nodata text="没有查找到相关商品" type='small' v-if="!lightList.length"></nodata>
					</scroll-view>
				</view>
			</view>
			<img class="scene" @click="areaClick" :src="scene" mode="widthFix" alt="">
			<scroll-view scroll-x="true" v-for="(item,index) in dataList" wx:key="index" v-if="item.active" class="class"
				:class="menuindex>-1?'class_opt':''">
				<img v-for="(itemS,indexS) in item.url" wx:key="indexS" :src="itemS" @click="lightType(index,indexS)"
					:class="item.lightIndex==indexS?'class_item class_active':'class_item'" alt="" />
			</scroll-view>
			<view class="cart" v-for="(item,index) in dataList" wx:key="index" :style="'top:' + topCart +'px'"
				v-if="item.real" @tap="goto" :data-url="'/pages/shop/product?id='+item.id">
				<img :src="item.realUrl" class="cart_item" alt="">
				<view class="cart_title">加购物车</view>
			</view>
		</block>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
		<canvas class="canvasDraw" v-if="canvasState" canvas-id="myCanvas" id="myCanvas"></canvas>
		<wxxieyi></wxxieyi>
	</view>
</template>
<script>
	var app = getApp();
	export default {
		data() {
			return {
				pre_url: app.globalData.pre_url,
				opt: {},
				loading: false,
				isload: true,
				menuindex: -1,

				scene: "",
				sceneHeight: "",
				sceneWidth: "",
				tableList: [],
				dataList: [],
				tableIndex: 0,
				switchState: true,
				sceneState: false,
				lightState: false,
				sceneList: [],
				lightList: [],
				changeStart: '',
				pagenum: 1,
				canvasState: false,
				topBtn: "",
				topCart: "",
				
				chaX: 0,
				chaY: 0,
				chaR: 0
			}
		},
		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		onReady() {
			this.topBtn = uni.getMenuButtonBoundingClientRect().top;
			this.topCart = uni.getMenuButtonBoundingClientRect().top + 40;
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		methods: {
			moreScene() {
				console.log("获取更多场景");
			},
			moreLight() {
				console.log("获取更多产品");
				this.pagenum = this.pagenum + 1;
				this.getprolist();
			},
			draw() {
				if (!this.dataList.length) {
					uni.showModal({
						title: '提示',
						content: '请选择产品图片',
						showCancel: false
					});
					return;
				}
				this.canvasState = true;
				uni.showLoading({
					title: '加载中',
					mask: true
				});
				uni.getSystemInfo({
					success: (res) => {
						let heightPage = res.screenHeight || res.windowHeight;
						let widthPage = res.screenWidth || res.windowWidth;
						let scale = widthPage / this.sceneWidth;
						let back = res.path;
						let backTop = 0;
						let backHeight = this.sceneHeight * scale;
						if (heightPage > backHeight) {
							backTop = (heightPage - backHeight) / 2;
						} else {
							backTop = -((backHeight - heightPage) / 2);
						}
						var ctx = uni.createCanvasContext('myCanvas');
						ctx.drawImage(this.scene, 0, backTop, widthPage, backHeight);
						for (let i = 0; i < this.dataList.length; i++) {
							let itemSize = this.dataList[i].size;
							let rotateData = this.dataList[i].rotate % 360;
							ctx.translate(this.dataList[i].x, this.dataList[i].y);
							ctx.rotate(rotateData * Math.PI / 180);
							ctx.drawImage(this.dataList[i].currentUrl, -itemSize / 2, -itemSize / 2, itemSize,
								itemSize);
							ctx.rotate(-(rotateData * Math.PI / 180));
							ctx.translate(-(this.dataList[i].x), -(this.dataList[i].y));
						}
						ctx.draw(false, () => {
							uni.canvasToTempFilePath({
								canvasId: 'myCanvas',
								x: 0,
								y: 0,
								width: widthPage,
								height: heightPage,
								destWidth: widthPage * 3,
								destHeight: heightPage * 3,
								success: (
									res
								) => {
									this.canvasState = false;
									uni.hideLoading();
									uni.getImageInfo({
										src: res.tempFilePath,
										success: (image) => {
											uni.saveImageToPhotosAlbum({
												filePath: image.path,
												success: () => {
													uni.showModal({
														title: '保存成功',
														content: '图片已成功保存到相册',
														showCancel: false
													});
												}
											});
										}
									});
								}
							}, this)
						})
					}
				});
			},
			getdata() {
				var that = this;
				that.loading = true;
				var id = this.opt.id ? this.opt.id : 0;
				app.get('ApiShop/diylight', {
					id: id
				}, function(res) {
					that.loading = false;
					if (res.status == 1) {
						that.sceneList = res.data.bgimgs;
						that.scene = res.data.bgimgs[0];
						uni.getImageInfo({
							src: that.scene,
							success: function(image) {
								that.scene = image.path;
								that.sceneHeight = image.height;
								that.sceneWidth = image.width;
							}
						});

						if (id && res.data.pro) {
							that.lightChoose(res.data.pro)
						}
						that.loaded();
					} else {
						app.alert(res.msg);
					}

				});
				app.get('ApiShop/category1', {}, function(res2) {
					if (res2.status == 1) {
						// that.info = res.info;
						that.tableList = res2.data;
						that.loaded();
						that.getprolist();
					} else {
						app.alert(res2.msg);
					}
				});
			},
			getprolist: function() {
				var that = this;
				var pagenum = that.pagenum;
				var cid = that.tableList[that.tableIndex]['id'];
				that.loading = true;
				that.nodata = false;
				that.nomore = false;
				app.post('ApiShop/getprolistForLight', {
					pagenum: pagenum,
					cid: cid
				}, function(res) {
					that.loading = false;
					var data = res.data;
					if (pagenum == 1) {
						that.lightList = [];
						setTimeout(() => {
							that.lightList = data;
							if (data.length == 0) {
								that.nodata = true;
							}
						}, 10)
					} else {
						if (data.length == 0) {
							that.nomore = true;
						} else {
							var datalist = that.lightList;
							var newdata = datalist.concat(data);
							that.lightList = newdata;
						}
					}
				});
			},
			uploadImg: function() {
				var that = this;
				app.chooseImage(function(urls) {
					var imgurl = urls[0];
					app.post('ApiShop/diylightUpload', {
						imgurl: imgurl
					}, function(res) {
						that.loading = false;
						if (res.status == 1) {
							var datalist = that.sceneList;
							var newdata = urls.concat(datalist);
							that.sceneList = newdata;
							that.scene = imgurl;
							uni.getImageInfo({
								src: imgurl,
								success: function(image) {
									that.scene = image.path;
									that.sceneHeight = image.height;
									that.sceneWidth = image.width;
								}
							});
						} else {
							app.alert(res.msg);
						}
					});
				}, 1)
			},
			lightType(index, indexS) {
				this.dataList[index].currentUrl = this.dataList[index].url[indexS];
				this.dataList[index].lightIndex = indexS
				uni.getImageInfo({
					src: this.dataList[index].url[indexS],
					success: (res) => {
						this.dataList[index].currentUrl = res.path
					}
				});
			},
			areaClick() {
				for (let i = 0; i < this.dataList.length; i++) {
					this.dataList[i].active = false
				}
			},
			moveClick(index) {
				this.changeFocus(index)
				this.changeReal(index)
			},
			changeFocus(index) {
				for (let i = 0; i < this.dataList.length; i++) {
					if (this.dataList[i]) {
						this.dataList[i].active = false
					}
				}
				if (this.dataList[index]) {
					this.dataList[index].active = true
				}
			},
			changeReal(index) {
				for (let i = 0; i < this.dataList.length; i++) {
					if (this.dataList[i]) {
						this.dataList[i].real = false
					}
				}
				if (this.dataList[index]) {
					this.dataList[index].real = true
				}
			},
			lightChoose(e) {
				let item = {
					id: e.id,
					x: 200,
					y: 200,
					size: 100,
					url: e.url,
					realUrl: e.realUrl,
					rotate: 0,
					active: true,
					real: true,
					lightIndex: 0,
					currentUrl: ''
				}
				this.dataList.push(item)
				this.lightClose()
				this.changeReal(this.dataList.length - 1)
				this.changeFocus(this.dataList.length - 1)
				uni.getImageInfo({
					src: e.url[0],
					success: (res) => {
						this.dataList[this.dataList.length - 1].currentUrl = res.path;
					}
				});
			},
			tableClick(e) {
				var oldindex = this.tableIndex;
				this.pagenum = 1;
				this.tableIndex = e;
				if (e != oldindex)
					this.getprolist();
			},
			lightClick() {
				if (this.lightState) {
					this.lightState = false;
				} else {
					this.lightState = true;
				}
			},
			lightClose() {
				this.lightState = false;
			},
			sceneChoose(e) {
				this.scene = e;
				uni.getImageInfo({
					src: e,
					success: (image) => {
						this.scene = image.path;
						this.sceneHeight = image.height;
						this.sceneWidth = image.width;
					}
				});
				this.sceneClick()
			},
			sceneClick() {
				if (!app.globalData.mid) {
					var frompage = encodeURIComponent(app._fullurl());
					app.goto('/pages/index/login?frompage=' + frompage, 'reLaunch');
				}
				if (this.sceneState) {
					this.sceneState = false;
				} else {
					this.sceneState = true;
				}
			},
			switchClick() {
				if (this.switchState) {
					this.switchState = false;
				} else {
					this.switchState = true;
				}
			},
			siteStart(event, index) {
				this.changeFocus(index)
				var tranX = event.touches[0].pageX - this.dataList[index].x;
				var tranY = event.touches[0].pageY - this.dataList[index].y;
				
				this.chaX = tranX;
				this.chaY = tranY;
			},
			siteMove(event, index) {
				this.dataList[index].x = event.touches[0].clientX - this.chaX;
				this.dataList[index].y = event.touches[0].clientY - this.chaY;
			},
			siteEnd(event, index) {},
			sizeStart(event, index) {},
			sizeMove(event, index) {
				let sizeX = this.dataList[index].x;
				let sizeY = this.dataList[index].y;
				let pageX = event.touches[0].clientX;
				let pageY = event.touches[0].clientY;
				let cutX = pageX - sizeX;
				let cutY = pageY - sizeY;
				if (cutX > 0 && cutY > 0) {
					this.dataList[index].size = (event.touches[0].clientX - this.dataList[index].x) + (event.touches[0].clientY - this.dataList[index].y)
				}
				if (cutX < 0 && cutY < 0) {
					this.dataList[index].size = (this.dataList[index].x - event.touches[0].clientX) + (this.dataList[index].y - event.touches[0].clientY)
				}
				if (cutX < 0 && cutY > 0) {
					this.dataList[index].size = (this.dataList[index].x - event.touches[0].clientX) + (event.touches[0].clientY - this.dataList[index].y)
				}
				if (cutX > 0 && cutY < 0) {
					this.dataList[index].size = (event.touches[0].clientX - this.dataList[index].x) + (this.dataList[index].y - event.touches[0].clientY)
				}
			},
			sizeEnd(event, index) {},
			spinStart(event, index) {
				let centerx = this.dataList[index].size / 2 + this.dataList[index].x;
				let centery = this.dataList[index].size / 2 + this.dataList[index].y;
				let endx = event.touches[0].pageX;
				let endy = event.touches[0].pageY;
				this.chaR = this.getAngle(centerx, centery, endx, endy) - this.dataList[index].rotate;
			},
			spinMove(event, index) {
				let centerx = this.dataList[index].size / 2 + this.dataList[index].x;
				let centery = this.dataList[index].size / 2 + this.dataList[index].y;
				let endx = event.touches[0].pageX;
				let endy = event.touches[0].pageY;
				let rotate = this.getAngle(centerx, centery, endx, endy) - this.chaR;
				this.dataList[index].rotate = rotate;
			},
			getAngle(centerx, centery, endx, endy) {
				var diff_x = endx - centerx,
					diff_y = endy - centery
				var c = 360 * Math.atan2(diff_y, diff_x) / (2 * Math.PI)
				c = c <= -90 ? (360 + c) : c
				return c + 90
			},
			spinEnd(event, index) {},
			moveDelete(e) {
				this.dataList.splice(e, 1);
				setTimeout(() => {
					if (this.dataList.length > 0) {
						let have = ''
						for (let i = 0; i < this.dataList.length; i++) {
							if (this.dataList[i].real) {
								have = i
							}
						}
						if (have == '') {
							let index = 0;
							this.dataList[index].real = true
							this.dataList[index].active = true
						}
					}
				}, 10)
			},
			onChange: function(event, index) {
				this.changeFocus(index)
				this.dataList[index].x = event.detail.x
				this.dataList[index].y = event.detail.y
			}
		}
	}
</script>

<style>
	page {
		position: absolute;
		width: 100%;
		height: 100%;
		overflow: hidden;
	}

	.scene {
		position: absolute;
		left: -100%;
		right: -100%;
		bottom: -100%;
		top: -100%;
		display: block;
		width: 100%;
		margin: auto auto;
	}

	movable-area {
		position: absolute;
		height: 100%;
		width: 100%;
		overflow: hidden;
	}

	.move_module {
		position: absolute;
		z-index: 5;
	}

	.move_item {
		position: absolute;
		height: 100%;
		width: 100%;
		border: 2px dashed rgba(0, 0, 0, 0);
	}

	.move_active {
		border: 2px dashed #fff;
	}

	.move_image {
		position: absolute;
		height: 100%;
		width: 100%;
	}

	.move_spin {
		position: absolute;
		height: 40rpx;
		width: 40rpx;
		top: -20rpx;
		right: -20rpx;
		border-radius: 100rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		background: #666;
	}

	.move_spin img {
		height: 30rpx;
		width: 30rpx;
		display: block;
	}

	.move_close {
		position: absolute;
		height: 40rpx;
		width: 40rpx;
		left: -20rpx;
		bottom: -20rpx;
		border-radius: 100rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		background: #666;
	}

	.move_close img {
		height: 30rpx;
		width: 30rpx;
		display: block;
	}

	.move_size {
		position: absolute;
		height: 40rpx;
		width: 40rpx;
		right: -20rpx;
		bottom: -20rpx;
		border-radius: 100rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		background: #666;
	}

	.move_size img {
		height: 30rpx;
		width: 30rpx;
		transform: rotate(90deg);
		display: block;
	}

	.class {
		position: absolute;
		width: 100%;
		padding: 0 50rpx;
		bottom: 100rpx;
		white-space: nowrap;
		align-items: center;
	}

	.class_item {
		height: 110rpx;
		width: 110rpx;
		display: inline-block;
		border-radius: 10rpx;
		margin-right: 20rpx;
		border: 2px solid rgba(0, 0, 0, 0);
	}

	.class_active {
		border: 2px solid #e8506f;
	}

	.class_opt {
		bottom: calc(env(safe-area-inset-bottom) + 230rpx);
	}

	.cart {
		position: absolute;
		left: 50rpx;
	}

	.cart_item {
		height: 110rpx;
		width: 110rpx;
		border-radius: 10rpx;
		border: 2px solid #e8506f;
	}

	.cart_title {
		font-size: 28rpx;
		color: #333;
		text-align: center;
		margin: 5rpx 0 0 0;
	}

	.navigation {
		position: absolute;
		padding: 10rpx 30rpx;
		width: 100%;
		box-sizing: border-box;
		display: flex;
		z-index: 10;
	}

	.navigation_item {
		margin-right: 30rpx;
		width: 50rpx;
		height: 50rpx;
	}

	.navigation_icon {
		padding: 0 !important;
		margin: 0 !important;
		font-size: 0 !important;
		background: rgba(0, 0, 0, 0) !important;
	}

	.table {
		position: fixed;
		bottom: 0;
		height: 80rpx;
		width: 100%;
		font-size: 26rpx;
		color: #a9a19e;
		display: flex;
		align-items: center;
		z-index: 10;
		background: rgba(0, 0, 0, 0.7);
	}

	.table_opt {
		bottom: calc(env(safe-area-inset-bottom) + 110rpx);
	}

	.table_item {
		flex: 1;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.table_icon {
		height: 50rpx;
		width: 50rpx;
		margin-right: 15rpx;
	}

	.page_cut {
		position: fixed;
		height: 100%;
		width: 100%;
		z-index: 5;
		background: rgba(0, 0, 0, 0.3);
	}

	.alert {
		position: fixed;
		height: 100%;
		width: 100%;
		background: rgba(0, 0, 0, 0.7);
		z-index: 15;
	}

	.alert_hide {
		position: absolute;
		height: 100%;
		width: 100%;
	}

	.alert_module {
		position: absolute;
		padding: 30rpx;
		width: 100%;
		box-sizing: border-box;
		background: #fff;
		bottom: 0;
		border-radius: 18rpx 18rpx 0 0;
	}

	.alert_opt {
		bottom: 110rpx;
	}

	.alert_head {
		display: flex;
		align-items: center;
		justify-content: space-between;
	}

	.alert_btn {
		position: relative;
		height: 60rpx;
		width: 180rpx;
		border: 1px solid #65c498;
		color: #65c498;
		font-size: 26rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		border-radius: 100rpx;
		background: rgba(104, 196, 153, 0.15);
	}

	.alert_close {
		height: 40rpx;
		width: 40rpx;
	}

	.alert_content {
		position: relative;
		display: flex;
		flex-wrap: wrap;
		white-space: normal;
		max-height: 450rpx;
		padding-top: 30rpx;
	}

	.alert_item {
		position: relative;
		width: 30%;
		height: 200rpx;
		margin-right: 5%;
		margin-bottom: 30rpx;
		overflow: hidden;
		display: inline-block;
	}

	.alert_item:nth-child(3n) {
		margin-right: 0;
	}

	.alert_item img {
		position: absolute;
		left: -100%;
		right: -100%;
		bottom: -100%;
		top: -100%;
		display: block;
		width: 100%;
		margin: auto auto;
	}

	.alert_title {
		position: relative;
		font-size: 32rpx;
		display: flex;
		align-items: center;
		justify-content: space-between;
		color: #333;
	}

	.alert_table {
		position: relative;
		padding: 30rpx 0 0 0;
		white-space: nowrap;
	}

	.alert_table view {
		position: relative;
		padding-right: 30rpx;
		font-size: 28rpx;
		display: inline-block;
	}

	.alert_active {
		color: #e0110a;
		font-weight: bold;
	}

	.canvasDraw {
		position: fixed;
		width: 100%;
		height: 100%;
		top: 0;
	}
</style>
