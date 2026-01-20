<template>
<view>
	<block v-if="isload">
		<view class="page-content">
			<view class="bg-view flex-col">
				<view class="icon-image">
					<image :src="pre_url+'/static/img/redenvelope/duihao.png'"></image>
				</view>
				<view class="title-text">红包已包好</view>
				<view class="tips-text">快把红包分享给好友吧</view>
			</view>
			<view class="poster-view flex-col">
					<image class="img-bg-view" :src="posterpic" mode="widthFix" style="width: 87%;margin: 0 auto;"/>
			<!-- 	<view class="img-bg-view">
					<view class="poster-img">
					
					</view>
				</view> -->
				<view class="but-view flex-bt">
					<view class='but-class class-type1' @tap="savpic2">保存图片</view>
					
					<view class='but-class class-type2' @tap="shareapp" v-if="getplatform() == 'app'">
						分享红包
					</view>
					<view class="but-class class-type2" @tap="sharemp" v-else-if="getplatform() == 'mp'">
						分享红包
					</view>
					<button class="but-share class-type2" open-type="share" v-else-if="getplatform() != 'h5'">
						分享红包
					</button>
				</view>
			</view>
		</view>
		
	 </block>
	 <loading v-if="loading"></loading>
	 <uni-popup ref="envelopePopup">
		<view class="envelopePopup-content">
			<view :class="[openShow ? 'top-view-active':'','top-view']">
				<!-- 背景图 ，没有就不展示
				 -->
				<image :src="data.pic" />
			</view>
			<view :class="[openShow ? 'bottom-view-active':'','bottom-view']">
				<image :src="pre_url+'/static/img/redenvelope/hb_bottom.png'"></image>
			</view>
			<view class="jinbi-view" @click="openEnvelope" v-if="!openShow">
				<image :src="pre_url+'/static/img/redenvelope/jinbi.png'"></image>
			</view>
		</view>
	</uni-popup>
</view>
</template>

<script>
	var app = getApp();
	export default {
		data(){
			return{
				pre_url: app.globalData.pre_url,
				loading:false,
				isload: false,
				openShow:false,
				sysset:[],
				data:[],
				id:0,
				posterpic:'',
				data:[],
				isopen:0
			}
		},
		onLoad(opt) {
			this.opt = app.getopts(opt);
			if(this.opt.id){
				this.id = this.opt.id;
			}
			this.getdata();
		},
		onShareAppMessage:function(){
			var link = '/pagesD/moneySendHongbao/hongbaoshare?scene=id_'+this.id+'-pid_' + app.globalData.mid+'-isshare_1';

			return this._sharewx({title:this.sysset.sharetitle,pic:this.sysset.sharepic,link:link});
		},
		onShareTimeline:function(){
			var sharewxdata = this._sharewx({title:this.sysset.sharetitle,pic:this.sysset.sharepic});
			var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
			return {
				title: sharewxdata.title,
				imageUrl: sharewxdata.imageUrl,
				query: query
			}
		},
		onShow() {
			this.getdata();
		},
		methods:{
			getdata: function () {
				var that = this;
				that.loading = true;
				app.post('ApiMoneySendHongbao/getposter', {proid:that.id}, function (res) {
					
					uni.setNavigationBarTitle({
						title: '发红包分享'
					});
					that.data = res.data;
					that.sysset = res.sysset;
					that.loading = false;
					if (res.status == 0) {
						app.alert(res.msg);
						setTimeout(function(){
							app.goback();
						},1500)
					} 
					if(res.data.is_sender ==0){
						if(res.data.is_receive ==1){
							app.goto('hongbaolog','reLaunch')
						}else{
							that.$refs.envelopePopup.open();
						}
					}else{
						
						that.posterpic = res.poster;
						var link = '/pagesD/moneySendHongbao/hongbaoshare?scene=id_'+that.id+'-pid_' + app.globalData.mid+'-isshare_1';
						that.loaded({title:res.sysset.sharetitle,pic:res.sysset.sharepic,link:link});
					}
				});
			},
		
			// 开红包
			openEnvelope(){
				let that = this;
				that.openShow = true;
				that.loading = true;
				that.$refs.envelopePopup.close();
				app.post('ApiMoneySendHongbao/receiveHongbao', {id:that.id}, function (res) {
					that.loading = false;
					that.$refs.envelopePopup.close();
					if(res.status ==0){
						that.$refs.envelopePopup.close();
						app.error(res.msg);
						setTimeout(function(){
							that.openShow = false;
							that.getdata();
						},1000)
						return;
					}
					
					app.goto('receivehongbao?id='+res.id,'reLaunch')
					
				});
				
			},
			savpic2:function(pics){
				var that = this;
				
				var pic = that.posterpic;
				// #ifdef H5
				    window.location.href= pic;
				// #endif
				// #ifdef MP-WEIXIN
				app.showLoading('图片保存中');
				uni.downloadFile({
					url: pic,
					success (res) {
						if (res.statusCode === 200) {
							// #ifdef H5
							    window.location.href= file;
							// #endif
							
							uni.saveImageToPhotosAlbum({
								filePath: res.tempFilePath,
								success:function () {
									app.error('保存成功');
								},
								fail:function(){
									app.showLoading(false);
									app.error('保存失败');
								}
							})
						}
					},
					fail:function(){
						app.showLoading(false);
						app.error('下载失败');
					}
				});
				// #endif
			},
			sharemp:function(){
				app.error('点击右上角发送给好友或分享到朋友圈');
			},
			shareapp:function(){
				var that = this;
				uni.showActionSheet({
					  itemList: ['发送给微信好友', '分享到微信朋友圈'],
					  success: function (res){
						if(res.tapIndex >= 0){
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
							sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesD/moneySendHongbao/hongbaoshare?scene=id_'+that.product.id+'-pid_' + app.globalData.mid;
							sharedata.imageUrl = that.product.pic;
							var sharelist = app.globalData.initdata.sharelist;
							if(sharelist){
								for(var i=0;i<sharelist.length;i++){
									if(sharelist[i]['indexurl'] == '/pagesD/moneySendHongbao/hongbaoshare'){
										sharedata.title = sharelist[i].title;
										sharedata.summary = sharelist[i].desc;
										sharedata.imageUrl = sharelist[i].pic;
										if(sharelist[i].url){
											var sharelink = sharelist[i].url;
											if(sharelink.indexOf('/') === 0){
												sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#'+ sharelink;
											}
											if(app.globalData.mid>0){
												 sharelink += (sharelink.indexOf('?') === -1 ? '?' : '&') + 'pid='+app.globalData.mid;
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
	}
</script>

<style>
	.page-content{position: relative;width: 100%;}
	.bg-view{width: 100%;height: 22vh;background: #ff003c;align-items: center;justify-content: flex-start;}
	.icon-image{width: 50rpx;height: 50rpx;margin-top: 40rpx;}
	.icon-image image{width: 100%;height: 100%;}
	.title-text{color: #fff;font-size: 38rpx;padding: 30rpx 0rpx;}
	.tips-text{font-size: 26rpx;letter-spacing: 3rpx;color: #eeeeee;}
	/*  */
	.poster-view{width: 92%;position: relative;left: 50%;transform: translateX(-50%);top: -60rpx;}
	.poster-view .img-bg-view{background: #fff;border-radius: 14rpx;padding: 20rpx;width: 100%;height: auto;}
	.poster-view .img-bg-view .poster-img{width: 380rpx;margin: 60rpx auto;}
	.poster-view .img-bg-view .poster-img image{width: 100%;height: 100%;border-radius: 12rpx;overflow: hidden;box-shadow: 0rpx 0rpx 40rpx 1rpx rgba(0,0,0,0.3);}
	.but-view{margin-top: 40rpx;}
	.but-class{width: 48%;border-radius: 50rpx;font-size: 32rpx;text-align: center;padding: 20rpx 0rpx;box-sizing: border-box;height: 90rpx;}
	.but-share{width: 48%;border-radius: 50rpx;font-size: 32rpx;text-align: center;box-sizing: border-box;height: 90rpx;line-height: 90rpx;}
	.class-type1{color: #ff003c;border: 1px #ff003c solid;}
	.class-type2{background: #ff003c;color: #fff;}
	/*  */
	.envelopePopup-content{width: 500rpx;position: relative;border-radius: 16rpx;}
	.envelopePopup-content .top-view{width: 100%;height: 750rpx;background: #e70013;border-radius:16rpx 16rpx 240rpx 240rpx;overflow: hidden;}
	.envelopePopup-content .top-view image{width: 100%;height: 100%;}
	.envelopePopup-content .top-view-active{animation: animationleavetop .6s;}
	.envelopePopup-content .bottom-view{width: 100%;height: 220rpx;position: absolute;bottom: 0;border-radius:0rpx 0rpx 16rpx 16rpx;overflow: hidden;}
	.envelopePopup-content .bottom-view image{width: 100%;height: 100%;}
	.envelopePopup-content .bottom-view-active{animation: animationleavebot .6s;}
	.envelopePopup-content .jinbi-view{border-radius: 50%;overflow: hidden;width: 130rpx;height: 130rpx;position: absolute;
    bottom: 110rpx;left: 50%;transform: translateX(-50%);z-index: 2;}
	.envelopePopup-content .jinbi-view image{width: 100%;height: 100%;}
	
	@keyframes animationleavebot {
	  from {transform: translateY(0) scale(1);}
	  to {transform: translateY(300%) scale(1.3);}
	}
	@keyframes animationleavetop {
	  from {transform: translateY(0) scale(1);}
	  to {transform: translateY(-200%) scale(1.3);}
	}
</style>