<template>
	<view>
		<block v-if="isload">
			<view class="container">
				<view class="swiper-container">
					<swiper class="swiper" :indicator-dots="false" :autoplay="true" :interval="5000" @change="swiperChange">
						<block v-for="(item, index) in product.pics" :key="index">
							<swiper-item class="swiper-item">
								<view class="swiper-item-view">
									<image class="img" :src="item" mode="widthFix" />
								</view>
							</swiper-item>
						</block>
					</swiper>
					<view class="imageCount">{{current+1}}/{{product.pics.length}}</view>
				</view>
				<view class="header">
					<view class="title">
						<view class="lef">
							<text>{{product.name}}</text>
						</view>
						<view class="share" @tap="shareClick">	
							<image :src="pre_url+'/static/img/share.png'"></image>
							<text>分享</text>
						</view>
					</view>
					<view class="sales_stock">
						<view class="f1">销量：{{product.sales}} </view>
						<view class="f2">库存：{{product.stock}}</view>
					</view>
				</view>

				<!-- 选择钓点 -->
				<view class="basan">
					<view class="seat_params">
						<view class="seat" >
							<image :src="pre_url+'/static/img/fishpond/seat_kexuan.png'" class="seat-img">空闲</image>
						</view>
						<view class="seat">
							<image :src="pre_url+'/static/img/fishpond/seat_yishou.png'" class="seat-img">使用中</image>
						</view>
						<view class="seat">
							<image :src="pre_url+'/static/img/fishpond/seat_yixuan.png'" class="seat-img">已选择</image>
						</view>
						<view class="seat">
							<image :src="pre_url+'/static/img/fishpond/seat_suozuo.png'" class="seat-img">锁座</image>
						</view>
					</view>
					<view class="tips-text flex-x-center">
						<text>请在下方选择位置</text>
					</view>
					<view class="basan-seat flex-wp">
						<view class="seat-box" v-for="(item, index) in basan" :key="index" @tap="selectBasan(index,item.id)">
							<image :src="pre_url+'/static/img/fishpond/seat_yishou.png'" mode="" v-if="item.status == 2"></image>
							<image :src="pre_url+'/static/img/fishpond/seat_yixuan.png'" mode="" v-else-if="item.status == 1"></image>
							<image :src="pre_url+'/static/img/fishpond/seat_suozuo.png'" mode="" v-else-if="item.status == 3"></image>
							<image :src="pre_url+'/static/img/fishpond/seat_kexuan.png'" mode="" v-else></image>
							<text class="basan-name">{{item.name}}</text>
						</view>
					</view>
				</view>
				<!-- 选择钓点 END -->

				<view class="detail_title">
					<view class="t1"></view>
					<view class="t2"></view>
					<view class="t0">商品描述</view>
					<view class="t2"></view>
					<view class="t1"></view>
				</view>
				<view class="detail">
					<dp :pagecontent="pagecontent"></dp>
				</view>
				<view style="width:100%;height:70px;"></view>

				<view class="bottombar flex-row" :class="menuindex>-1?'tabbarbot':'notabbarbot'" v-if="product.status==1">
					<view class="f1">
						<view class="item" @tap="goto" :data-url="kfurl" v-if="kfurl!='contact::'">
							<image class="img" :src="pre_url+'/static/img/kefu.png'" />
							<view class="t1">客服</view>
						</view>
						<button class="item" v-else open-type="contact" show-message-card="true">
							<image class="img" :src="pre_url+'/static/img/kefu.png'" />
							<view class="t1">客服</view>
						</button>
						<view class="item flex1" @tap="shareClick">
							<image class="img" :src="pre_url+'/static/img/share2.png'" />
							<view class="t1">分享</view>
						</view>
					</view>
					<view class="op">
						<block>
							<view class="tobuy flex-x-center flex-y-center" :style="{background:t('color1')}" @tap="buydialogChange" data-btntype="3">立即购买</view>
						</block>
					</view>
				</view>
				<!-- 规格 -->
				<view :hidden="buydialogHidden">
					<view class="buydialog-mask">
						<view class="buydialog" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
							<view class="close" @tap="buydialogChange">
								<image :src="pre_url+'/static/img/close.png'" class="image"></image>
							</view>
							<view class="title">
								<image :src="guigelist[ks].pic?guigelist[ks].pic:product.pic" class="img" @tap="previewImage" :data-url="guigelist[ks].pic?guigelist[ks].pic:product.pic"></image>
								<!-- <text class="name">{{product.name}}</text> -->
								<view class="price"><text class="t1">￥</text>{{guigelist[ks].sell_price}}</view>
								<view class="choosename">已选规格: {{guigelist[ks].name}}</view>
								<view class="stock">剩余{{guigelist[ks].stock}}件</view>
							</view>
				
							<view v-for="(item, index) in guigedata" :key="index" class="guigelist flex-col">
								<view class="name">{{item.title}}</view>
								<view class="item flex flex-y-center">
									<block v-for="(item2, index2) in item.items" :key="index2">
										<view :data-itemk="item.k" :data-idx="item2.k" :class="'item2 ' + (ggselected[item.k]==item2.k ? 'on':'')" @tap="ggchange">{{item2.title}}</view>
									</block>
								</view>
							</view>
							<!-- <view class="buynum flex flex-y-center">
								<view class="flex1">购买数量：</view>
								<view class="addnum">
									<view class="minus"><image class="img" :src="pre_url+'/static/img/cart-minus.png'" @tap="gwcminus"/></view>
									<input class="input" type="number" :value="gwcnum" @input="gwcinput" max="1"></input>
									<view class="plus"><image class="img" :src="pre_url+'/static/img/cart-plus.png'" @tap="gwcplus"/></view>
								</view>
							</view> -->
							<view class="op">
								<block >
									<button class="tobuy" :style="{background:t('color2')}" @tap="tobuy" data-type="1">确定</button>
								</block>
							</view>
						</view>
					</view>
				</view>
			</view>

			<view v-if="sharetypevisible" class="popup__container">
				<view class="popup__overlay" @tap.stop="handleClickMask"></view>
				<view class="popup__modal" style="height:320rpx;min-height:320rpx">
					<view class="popup__content">
						<view class="sharetypecontent">
							<view class="f1" @tap="shareapp" v-if="getplatform() == 'app'">
								<image class="img" :src="pre_url+'/static/img/sharefriends.png'" />
								<text class="t1">分享给好友</text>
							</view>
							<view class="f1" @tap="sharemp" v-else-if="getplatform() == 'mp'">
								<image class="img" :src="pre_url+'/static/img/sharefriends.png'" />
								<text class="t1">分享给好友</text>
							</view>
							<button class="f1" open-type="share" v-else-if="getplatform() != 'h5'">
								<image class="img" :src="pre_url+'/static/img/sharefriends.png'" />
								<text class="t1">分享给好友</text>
							</button>
							<view class="f2" @tap="showPoster">
								<image class="img" :src="pre_url+'/static/img/sharepic.png'" />
								<text class="t1">生成分享图片</text>
							</view>
						</view>
					</view>
				</view>
			</view>
			<view class="posterDialog" v-if="showposter">
				<view class="main">
					<view class="close" @tap="posterDialogClose">
						<image class="img" :src="pre_url+'/static/img/close.png'" />
					</view>
					<view class="content">
						<image class="img" :src="posterpic" mode="widthFix" @tap="previewImage" :data-url="posterpic"></image>
					</view>
				</view>
			</view>
			
		</block>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
	</view>
</template>

<script>
	var app = getApp();
	var interval = null;

	export default {
		data() {
			return {
				opt: {},
				loading: false,
				isload: false,
				menuindex: -1,
				pre_url: app.globalData.pre_url,
				current: 0,
				indexurl: app.globalData.indexurl,
				platform: app.globalData.platform,
				tabnum: 1,
				num: 1,
				isfavorite: false,
				btntype: 1,
				ggselected: [],
				ks: '',
				gwcnum: 1,
				nodata: 0,
				userinfo: [],
				djsday: '00',
				djshour: '00',
				djsmin: '00',
				djssec: '00',
				product: "",
				basan:"",
				buydialogHidden: true,
				shopset: "",
				pagecontent: "",
				selectedbasan:[],
				guigelist:"",
				guigedata:"",
				ks:"",
				joinlist: "",
				nowtime: "",
				imJoin: "",
				title: "",
				sharepic: "",
				sharetypevisible: false,
				showposter: false,
				posterpic: "",
				kfurl: '',
			};
		},


		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		onShareAppMessage: function() {
			return this._sharewx({
				title: this.product.name,
				pic: this.product.pic,
				callback: function() {
					that.sharecallback();
				}
			});
		},
		onShareTimeline: function() {
			var sharewxdata = this._sharewx({
				title: this.product.name,
				pic: this.product.pic,
				callback: function() {
					that.sharecallback();
				}
			});
			var query = (sharewxdata.path).split('?')[1] + '&seetype=circle';
			return {
				title: sharewxdata.title,
				imageUrl: sharewxdata.imageUrl,
				query: query
			}
		},
		onUnload: function() {
			clearInterval(interval);
		},
		methods: {
			getdata: function() {
				var that = this;
				var id = that.opt.id;
				that.loading = true;
				app.get('ApiFishPond/getDetail', {
					id: id
				}, function(res) {
					that.loading = false;
					if (res.status == 0) {
						app.alert(res.msg);
						return;
					}
					var product = res.product;
					that.basan = res.basan;
					that.ggselected = res.ggselected;
					that.guigedata = res.guigedata;
					that.guigelist = res.guigelist;
					that.ks = res.ks;
					var pagecontent = JSON.parse(product.detail);
					that.product = product;
					that.shopset = res.shopset;
					that.pagecontent = pagecontent;
					that.title = product.name;
					that.sharepic = product.pics[0];
					uni.setNavigationBarTitle({
						title: product.name
					});
					that.kfurl = '/pages/kefu/index?bid=' + res.product.bid;
					if (app.globalData.initdata.kfurl != '') {
						that.kfurl = app.globalData.initdata.kfurl;
					}
					that.loaded({
						title: res.product.name,
						pic: res.product.pic,
						callback: function() {
							that.sharecallback();
						}
					});
				});
			},
			selectBasan:function(index,id){
				var that = this;
				let basan = that.basan[index];
				
				if(basan.status == 2 || basan.status == 3){
					app.error('此钓位不可选');
					return;
				}
	
				if(basan.status == 1){
					basan.status = 0; // 取消选中
					// 从selectedbasan数组中移除对应的id
					var indexToRemove = that.selectedbasan.indexOf(id);
					if (indexToRemove !== -1) {
						that.selectedbasan.splice(indexToRemove, 1);
					}
				} else {
					basan.status = 1; // 标记为选中
					that.selectedbasan.push(id); // 将id添加到selectedbasan数组
				}
				console.log(that.selectedbasan);
			},
			buydialogChange: function (e) {
				this.buydialogHidden = !this.buydialogHidden;
			},
			//选择规格
			ggchange: function (e) {
			  var idx = e.currentTarget.dataset.idx;
			  var itemk = e.currentTarget.dataset.itemk;
			  var ggselected = this.ggselected;
			  ggselected[itemk] = idx;
			  var ks = ggselected.join(',');
			  this.ggselected = ggselected;
			  this.ks = ks;
			},
			swiperChange: function(e) {
				var that = this;
				that.current = e.detail.current;
			},
			tobuy: function (e) {
			  var type = e.currentTarget.dataset.type;
			  var that = this;
			  var ks = that.ks;
			  var proid = that.product.id;
			  var ggid = that.guigelist[ks].id;
				var selectedbasan = this.selectedbasan;
				if(app.isNull(selectedbasan)){
					return app.error('请选择位置');
				}
				var basanIds = selectedbasan.join(',');
			  app.goto('buy?proid=' + proid + '&ggid=' + ggid + '&basan=' + basanIds);
			},
			shareClick: function() {
				this.sharetypevisible = true;
			},
			handleClickMask: function() {
				this.sharetypevisible = false;
			},
			showPoster: function() {
				var that = this;
				that.showposter = true;
				that.sharetypevisible = false;
				app.showLoading('努力生成中');
				app.post('ApiFishPond/getposter', {
					proid: that.product.id
				}, function(data) {
					app.showLoading(false);
					if (data.status == 0) {
						app.alert(data.msg);
					} else {
						that.posterpic = data.poster;
					}
				});
			},
			posterDialogClose: function() {
				this.showposter = false;
			},
			sharemp: function() {
				app.error('点击右上角发送给好友或分享到朋友圈');
				this.sharetypevisible = false
			},
			shareapp: function() {
				var that = this;
				uni.showActionSheet({
					itemList: ['发送给微信好友', '分享到微信朋友圈'],
					success: function(res) {
						if (res.tapIndex >= 0) {
							var scene = 'WXSceneSession';
							if (res.tapIndex == 1) {
								scene = 'WXSenceTimeline';
							}
							var sharedata = {};
							sharedata.provider = 'weixin';
							sharedata.type = 0;
							sharedata.scene = scene;
							sharedata.title = that.product.name;
							//sharedata.summary = app.globalData.initdata.desc;
							sharedata.href = app.globalData.pre_url + '/h5/' + app.globalData.aid +
								'.html#/pagesB/fishpond/detail?scene=id_' + that.product.id + '-pid_' + app.globalData.mid;
							sharedata.imageUrl = that.product.pic;
							var sharelist = app.globalData.initdata.sharelist;
							if (sharelist) {
								for (var i = 0; i < sharelist.length; i++) {
									if (sharelist[i]['indexurl'] == '/pagesB/fishpond/detail') {
										sharedata.title = sharelist[i].title;
										sharedata.summary = sharelist[i].desc;
										sharedata.imageUrl = sharelist[i].pic;
										if (sharelist[i].url) {
											var sharelink = sharelist[i].url;
											if (sharelink.indexOf('/') === 0) {
												sharelink = app.globalData.pre_url + '/h5/' + app.globalData.aid + '.html#' + sharelink;
											}
											if (app.globalData.mid > 0) {
												sharelink += (sharelink.indexOf('?') === -1 ? '?' : '&') + 'pid=' + app.globalData.mid;
											}
											sharedata.href = sharelink;
										}
									}
								}
							}
							uni.share(sharedata);
						}
					}
				});
			}
		}
	};
</script>
<style>
	.swiper-container{position:relative}
	.swiper{width:100%;height:750rpx;overflow:hidden}
	.swiper-item-view{width:100%;height:750rpx}
	.swiper .img{width:100%;height:750rpx;overflow:hidden}
	.imageCount{width:100rpx;height:50rpx;background-color:rgba(0,0,0,0.3);border-radius:40rpx;line-height:50rpx;color:#fff;text-align:center;font-size:26rpx;position:absolute;right:13px;bottom:20rpx}
	.kanjia_title{width:100%;height:110rpx;display:flex;align-items:center}
	.kanjia_title .f1{height:110rpx;background:linear-gradient(90deg,#FF3143,#FE6748);display:flex;flex-direction:column;flex:1;justify-content:center;position:relative;padding-left:20rpx}
	.kanjia_title .f1 .t1{font-size:24rpx;color:#fff}
	.kanjia_title .f1 .t2{font-size:24rpx;color:#fff}
	.kanjia_title .f1 .t3{background:rgba(255,255,255,0.9);height:46prx;line-height:46rpx;border-radius:23rpx;padding:0 20rpx;color:#FF3143;font-size:24rpx;position:absolute;right:10rpx;top:30rpx}
	.kanjia_title .f3{width:280rpx;height:110rpx;background:#FFDBDF;color:#333;display:flex;flex-direction:column;align-items:center;justify-content:center}
	.kanjia_title .f3 .t2{color:#FF3143}
	.kanjia_title .djsspan{font-size:22rpx;border-radius:8rpx;background:#FF3143;color:#fff;text-align:center;padding:4rpx 8rpx;margin:0 4rpx}
	.header{width:100%;padding:0 3%;background:#fff;display:flex;flex-direction:column}
	.header .title{padding:10px 0px;line-height:44rpx;font-size:32rpx;display:flex}
	.header .title .lef{display:flex;flex-direction:column;justify-content:center;flex:1;color:#222222;font-weight:bold}
	.header .title .lef .t2{font-size:26rpx;color:#999;padding-top:10rpx;font-weight:normal}
	.header .title .share{width:88rpx;height:88rpx;padding-left:20rpx;border-left:0 solid #f5f5f5;text-align:center;font-size:24rpx;color:#222;display:flex;flex-direction:column;align-items:center}
	.header .title .share image{width:32rpx;height:32rpx;margin-bottom:4rpx}
	.header .sales_stock{display:flex;justify-content:space-between;height:40rpx;line-height:40rpx;margin-bottom:20rpx;font-size:24rpx;color:#777777}
	.choose{display:flex;align-items:center;width:100%;background:#fff;margin-top:20rpx;height:80rpx;line-height:80rpx;padding:0 3%;color:#505050}
	.choose .f2{width:40rpx;height:40rpx}
	.joinlist{width:100%;display:flex;align-items:center;margin-top:20rpx;background:#fff;padding:30rpx 3%}
	.joinlist .t1{margin-left:-10rpx;height:40rpx}
	.joinlist .t1 image{width:50rpx;height:50rpx;border-radius:50%;border:2px solid #fff}
	.joinlist .t2{font-size:24rpx;color:#787878}
	.shop{display:flex;align-items:center;width:100%;background:#fff;margin-top:20rpx;padding:20rpx 3%;position:relative;min-height:100rpx}
	.shop .p1{width:90rpx;height:90rpx;border-radius:6rpx;flex-shrink:0}
	.shop .p2{padding-left:10rpx}
	.shop .p2 .t1{width:100%;height:40rpx;line-height:40rpx;overflow:hidden;color:#111;font-weight:bold;font-size:30rpx}
	.shop .p2 .t2{width:100%;height:30rpx;line-height:30rpx;overflow:hidden;color:#999;font-size:24rpx;margin-top:8rpx}
	.shop .p4{height:64rpx;line-height:64rpx;color:#FFFFFF;border-radius:32rpx;margin-left:20rpx;flex-shrink:0;padding:0 30rpx;font-size:24rpx;font-weight:bold}
	.detail{min-height:200rpx}
	.header .sales_stock{display:flex;justify-content:space-between;height:60rpx;line-height:60rpx;margin-top:30rpx;font-size:24rpx;color:#777777}
	.detail_title{width:100%;display:flex;align-items:center;justify-content:center;margin-top:60rpx;margin-bottom:30rpx}
	.detail_title .t0{font-size:28rpx;font-weight:bold;color:#222222;margin:0 20rpx}
	.detail_title .t1{width:12rpx;height:12rpx;background:rgba(253,74,70,0.2);transform:rotate(45deg);margin:0 4rpx;margin-top:6rpx}
	.detail_title .t2{width:18rpx;height:18rpx;background:rgba(253,74,70,0.4);transform:rotate(45deg);margin:0 4rpx}
	.bottombar{width:94%;position:fixed;bottom:0px;left:0px;background:#fff;display:flex;height:100rpx;padding:0 4% 0 2%;align-items:center;box-sizing:content-box}
	.bottombar .f1{flex:1;display:flex;align-items:center;margin-right:30rpx}
	.bottombar .f1 .item{display:flex;flex-direction:column;align-items:center;width:80rpx;position:relative}
	.bottombar .f1 .item .img{width:44rpx;height:44rpx}
	.bottombar .f1 .item .t1{font-size:18rpx;color:#222222;height:30rpx;line-height:30rpx;margin-top:6rpx}
	.bottombar .op{width:72%;border-radius:36rpx;overflow:hidden;display:flex}
	.bottombar .tobuy{flex:1;height:72rpx;line-height:72rpx;color:#fff;border-radius:0px;border:none;display:flex;align-items:center;justify-content:center}
	.basan{background:#fff;margin-top:20rpx}
	.seat_params{display:flex;align-items:center;justify-content:center;padding:20rpx 0}
	.seat_params > .seat{display:flex;align-items:center;justify-content:center;font-weight:400;font-size:27rpx;color:#222222}
	.seat_params > .seat:not(:last-child){margin-right:47rpx}
	.tips-text{background:#e4e2e2;margin:20rpx 0;padding:10rpx 0;color:#a5a0a0}
	.seat_params > .seat > .seat-img{width:36rpx;height:36rpx;display:block;margin-right:16rpx}
	.basan-seat{margin-top:12rpx;height:auto;overflow:hidden;padding:20rpx}
	.basan-seat .seat-box{width:16.66%;padding:20rpx 5rpx;box-sizing:border-box;display: flex;flex-direction: column;align-items: center;}
	.basan-seat .seat-box image{width:80rpx;height:80rpx}
	.basan-seat .seat-box .basan-name{font-size: 24rpx;color: #696868;margin-top: 10rpx;}
	
	
	.buydialog-mask{ position: fixed; top: 0px; left: 0px; width: 100%; background: rgba(0,0,0,0.5); bottom: 0px;z-index:10}
	.buydialog{ position: fixed; width: 100%; left: 0px; bottom: 0px; background: #fff;z-index:11;border-radius:20rpx 20rpx 0px 0px}
	.buydialog .close{ position: absolute; top: 0; right: 0;padding:20rpx;z-index:12}
	.buydialog .close .image{ width: 30rpx; height:30rpx; }
	.buydialog .title{ width: 94%;position: relative; margin: 0 3%; padding:20rpx 0px; border-bottom:0; height: 190rpx;}
	.buydialog .title .img{ width: 160rpx; height: 160rpx; position: absolute; top: 20rpx; border-radius: 10rpx; border: 0 #e5e5e5 solid;background-color: #fff}
	.buydialog .title .price{ padding-left:180rpx;width:100%;font-size: 36rpx;height:70rpx; color: #FC4343;overflow: hidden;}
	.buydialog .title .price .t1{ font-size:26rpx}
	.buydialog .title .price .t2{ font-size:26rpx;text-decoration:line-through;color:#aaa}
	.buydialog .title .choosename{ padding-left:180rpx;width: 100%;font-size: 24rpx;height: 42rpx;line-height:42rpx;color:#888888}
	.buydialog .title .stock{ padding-left:180rpx;width: 100%;font-size: 24rpx;height: 42rpx;line-height:42rpx;color:#888888}
	
	.buydialog .guigelist{ width: 94%; position: relative; margin: 0 3%; padding:0px 0px 10px 0px; border-bottom: 0; }
	.buydialog .guigelist .name{ height:70rpx; line-height: 70rpx;}
	.buydialog .guigelist .item{ font-size: 30rpx;color: #333;flex-wrap:wrap}
	.buydialog .guigelist .item2{ height:60rpx;line-height:60rpx;margin-bottom:4px;border:0; border-radius:4rpx; padding:0 40rpx;color:#666666; margin-right: 10rpx; font-size:26rpx;background:#F4F4F4}
	.buydialog .guigelist .on{color:#FC4343;background:rgba(252,67,67,0.1);font-weight:bold}
	.buydialog .buynum{ width: 94%; position: relative; margin: 0 3%; padding:10px 0px 10px 0px; }
	.buydialog .addnum {font-size: 30rpx;color: #666;width:auto;display:flex;align-items:center}
	.buydialog .addnum .plus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
	.buydialog .addnum .minus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
	.buydialog .addnum .img{width:24rpx;height:24rpx}
	.buydialog .addnum .input{flex:1;width:70rpx;border:0;text-align:center;color:#2B2B2B;font-size:24rpx}
	.buydialog .op{width:90%;margin:20rpx 5%;border-radius:36rpx;overflow:hidden;display:flex;margin-top:100rpx;}
	.buydialog .addcart{flex:1;height:72rpx; line-height: 72rpx;color: #fff; border-radius: 0px; border: none; font-size:28rpx;font-weight:bold}
	.buydialog .tobuy{flex:1;height: 72rpx; line-height: 72rpx;color: #fff; border-radius: 0px; border: none;}
	.buydialog .nostock{flex:1;height: 72rpx; line-height: 72rpx; background:#aaa; color: #fff; border-radius: 0px; border: none;}
	
</style>