<template>
	<view class="container">
		<block v-if="isload">
			<block v-if="cartlist.length>0">
				<view class="cartmain">
					<block v-for="(item, index) in cartlist" :key="item.bid">
						<view class="item">
							<view class="btitle">
								<view class="flex flex-xy-center click-radio" @tap.stop="changeradio" :data-index="index">
									<view class="radio" :style="item.checked ? 'background:'+t('color1')+';border:0' : ''">
										<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
									</view>
								</view>
								<view class="btitle-name" @tap="goto"
									:data-url="item.bid==0?indexurl:'/pagesExt/business/index?id=' + item.business.id">
									{{item.business.name}}</view>
								<view class="flex1"> </view>
							</view>
							<view class="content" v-for="(item2,index2) in item.prolist" @tap="goto"
								:data-url="'/pages/shop/product?id=' + item2.product.id" :key="index2">
								<view class="flex flex-xy-center click-radio" @tap.stop="changeradio2" :data-index="index"
									:data-index2="index2">
									<view class="radio" :style="item2.checked ? 'background:'+t('color1')+';border:0' : ''">
										<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
									</view>
								</view>
								<view class="proinfo" :style="(item.prolist).length == index2+1 ? 'border:0' : ''">
									<image :src="item2.guige.pic?item2.guige.pic:item2.product.pic" class="img" />
									<view class="detail">
										<view class="title"><text>{{item2.product.name}}</text></view>
										<view class="desc"><text>{{item2.guige.name}}</text></view>
										<view class="price" :style="{color:t('color1')}">
											<view><text style="font-size:24rpx">￥</text>{{item2.guige.sell_price}} × {{item2.num}}</view>
										</view>
									</view>
								</view>
							</view>
						</view>
					</block>
				</view>
			</block>
		</block>

		<loading v-if="loading"></loading>
		<block v-if="cartlist.length>0">
			<view style="height:auto;position:relative">
				<view style="width:100%;height:110rpx"></view>
				<view class="footer flex" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
					<view @tap.stop="changeradioAll" class="radio"
						:style="allchecked ? 'background:'+t('color1')+';border:0' : ''">
						<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
					</view>
					<view @tap.stop="changeradioAll" class="text0">全选（{{selectedcount}}）</view>
					<view class="flex1"></view>
					
					<view class="op" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="shareClick">分享</view>
				</view>
			</view>
		</block>
		<view v-if="sharevisible" class="popup__container">
			<view class="popup__overlay" @tap.stop="shareClick"></view>
			<view class="popup__modal" style="height:320rpx;min-height:320rpx">
				<view class="popup__content">
					<view class="sharetypecontent">
						<!-- #ifdef APP -->
						<view class="f1" @tap="shareClick">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</view>
						<!-- #endif -->
						<!-- #ifdef H5 -->
						<view class="f1" @tap="shareClick" v-if="getplatform() == 'mp'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</view>
						<!-- #endif -->
						<!-- #ifndef H5 || APP -->
						<button class="f1" open-type="share" v-if="getplatform() != 'h5'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</button>
						<!-- #endif -->
					</view>
				</view>
			</view>
		</view>
		
		<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
		<wxxieyi></wxxieyi>
	</view>
</template>

<script>
	var app = getApp();
	export default {
		data() {
			return {
				opt: {},
				loading: false,
				isload: false,
				menuindex: -1,
				pre_url: app.globalData.pre_url,
				indexurl: app.globalData.indexurl,
				cartlist: [],
				selectedcount: 0,
				allchecked: true,
				member:[],
				sharetitle:'',
				sharedesc:'',
				sharepic:'',
				sharecart:0,
				sharevisible:false
			};
		},

		onLoad: function(opt) {
			this.opt = app.getopts(opt);
		},
		onShow: function() {
			this.getdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		onShareTimeline:function(){
			console.log('分享朋友圈')
			var shareLink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesD/shop/sharecartrecord?scene=cart_'+this.sharecart;
			var sharewxdata = this._sharewx({title:this.sharetitle,pic:this.sharepic,desc:this.sharedesc,link:shareLink});
			console.log({title:this.sharetitle,pic:this.sharepic,desc:this.sharedesc,link:shareLink});
			var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
			return {
				title: sharewxdata.title,
				imageUrl: sharewxdata.imageUrl,
				query: query
			}
		},
		onShareAppMessage:function(){
			console.log('分享')
			var shareLink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesD/shop/sharecartrecord?scene=cart_'+this.sharecart;
			console.log({title:this.sharetitle,pic:this.sharepic,desc:this.sharedesc,link:shareLink});
			return this._sharewx({title:this.sharetitle,pic:this.sharepic,desc:this.sharedesc,link:shareLink});
		},
		methods: {
			getdata: function() {
				var that = this;
				var bid = that.opt.bid ? that.opt.bid : '';
				that.loading = true;
				app.get('ApiShop/shareCart', {bid: bid}, function(res) {
					that.loading = false;
					that.cartlist = res.cartlist;
					that.sharedesc = res.sharedesc;
					that.sharetitle = res.sharetitle;
					that.calculate();
					that.loaded();
				});
			},
			changeradio: function(e) {
				var that = this;
				var cartlist = that.cartlist;
				var checked = cartlist[index].checked;
				if (checked) {
					cartlist[index].checked = false;
				} else {
					cartlist[index].checked = true;
				}
				for (var i in cartlist[index].prolist) {
					cartlist[index].prolist[i].checked = cartlist[index].checked;
				}
				that.cartlist = cartlist;
				that.calculate();
			},
			changeradio2: function(e) {
				var that = this;
				var index = e.currentTarget.dataset.index;
				var index2 = e.currentTarget.dataset.index2;
				var cartlist = that.cartlist;
				var checked = cartlist[index].prolist[index2].checked;
				if (checked) {
					cartlist[index].prolist[index2].checked = false;
				} else {
					cartlist[index].prolist[index2].checked = true;
				}
				var isallchecked = true;
				for (var i in cartlist[index].prolist) {
					if (cartlist[index].prolist[i].checked == false) {
						isallchecked = false;
					}
				}
				if (isallchecked) {
					cartlist[index].checked = true;
				} else {
					cartlist[index].checked = false;
				}
				that.cartlist = cartlist;
				that.calculate();
			},
			changeradioAll: function() {
				var that = this;
				var cartlist = that.cartlist;
				var allchecked = that.allchecked
				for (var i in cartlist) {
					cartlist[i].checked = allchecked ? false : true;
					for (var j in cartlist[i].prolist) {
						cartlist[i].prolist[j].checked = allchecked ? false : true;
					}
				}
				that.cartlist = cartlist;
				that.allchecked = allchecked ? false : true;
				that.calculate();
			},
			calculate: function () {
				var that = this;
				var cartlist = that.cartlist;
				var ids = [];
				var selectedcount = 0;
				var sharepic = that.sharepic;
				for(var i in cartlist){
						for(var j in cartlist[i].prolist){
								if(cartlist[i].prolist[j].checked){
										selectedcount += 1;
										if (!sharepic) {
											var thispro = cartlist[i].prolist[j];
											that.sharepic = thispro.guige.pic ? thispro.guige.pic : thispro.product.pic;
										}
								}
						}
				}
				that.selectedcount = selectedcount;
			},
      shareClick: function() {
				var that = this;
				var cartlist = that.cartlist;
				var prodata = [];

        for (var i in cartlist) {
          for (var j in cartlist[i].prolist) {
            if (cartlist[i].prolist[j].checked) {
              var thispro = cartlist[i].prolist[j];
              var tmpprostr = thispro.product.id + '_' + thispro.guige.id + '_' + thispro.num;
              prodata.push(tmpprostr);
            }
          }
        }

				if (prodata == undefined || prodata.length == 0) {
					app.error('请先选择产品');
					return;
				}

				app.post('ApiShop/shareCart', {bid:that.opt.bid,prodata:prodata.join(',')}, function(res) {
					if(res.data && res.data.id){
						that.sharecart = res.data.id;
						that.sharevisible = true; 
					}
					// #ifdef H5
					if(app.globalData.platform == 'mp'){
						 app.error('点击右上角发送给好友或分享到朋友圈');
						 var shareLink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesD/shop/sharecartrecord?scene=cart_'+that.sharecart;
						 that._sharemp({title:that.sharetitle,pic:that.sharepic,desc:that.sharedesc,link:shareLink})
						 that.sharevisible = false; 
						 return;
					}
					// #endif
		
					// #ifdef APP
					that.sharevisible = false; 
					var shareLink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesD/shop/sharecartrecord?scene=cart_'+that.sharecart;
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
					      sharedata.title = that.sharetitle;
					      sharedata.summary = that.sharedesc;
					      sharedata.href = shareLink;
					      sharedata.imageUrl = that.sharepic;
					      uni.share(sharedata);
					    }
					  }
					});
					// #endif
				})
			}
		}
	};
</script>
<style>
	.container{height:100%}
	.cartmain .item{width:94%;margin:20rpx 3%;background:#fff;border-radius:20rpx;padding:30rpx 3% 30rpx 1%}
	.cartmain .item .click-radio{width:64rpx;height:64rpx;margin-right:15rpx;border-radius:50%}
	.cartmain .item .radio{flex-shrink:0;width:32rpx;height:32rpx;background:#FFFFFF;border:2rpx solid #BFBFBF;border-radius:50%}
	.cartmain .item .radio .radio-img{width:100%;height:100%}
	.cartmain .item .btitle{width:100%;display:flex;align-items:center;margin-bottom:30rpx}
	.cartmain .item .btitle-name{color:#222222;font-weight:bold;font-size:28rpx}
	.cartmain .item .btitle-del{display:flex;align-items:center;color:#999999;font-size:24rpx}
	.cartmain .item .btitle-del .img{width:24rpx;height:24rpx}
	.cartmain .item .content{width:100%;position:relative;display:flex;align-items:center}
	.cartmain .item .content .proinfo{flex:1;display:flex;padding:20rpx 0;border-bottom:1px solid #f2f2f2}
	.cartmain .item .content .proinfo .img{width:176rpx;height:176rpx}
	.cartmain .item .content .detail{flex:1;margin-left:20rpx;height:176rpx;position:relative}
	.cartmain .item .content .detail .title{color:#222222;font-weight:bold;font-size:28rpx;line-height:34rpx;margin-bottom:0;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:68rpx;word-break:break-all}
	.cartmain .item .content .detail .desc{margin-top:0rpx;height:auto;color:#999;overflow:hidden;font-size:20rpx}
	.cartmain .item .content .detail .desc text{width:350rpx;display:block;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden}
	.cartmain .item .content .prodel{width:24rpx;height:24rpx;position:absolute;top:90rpx;right:0}
	.cartmain .item .content .prodel-img{width:100%;height:100%}
	.cartmain .item .content .price{margin-top:10rpx;height:60rpx;line-height:60rpx;font-size:32rpx;font-weight:bold;display:flex;align-items:center}
	.cartmain .item .content .addnum{position:absolute;right:0;bottom:0rpx;font-size:30rpx;color:#666;width:auto;display:flex;align-items:center}
	.cartmain .item .content .addnum .plus{width:65rpx;height:48rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
	.cartmain .item .content .addnum .minus{width:65rpx;height:48rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
	.cartmain .item .content .addnum .img{width:24rpx;height:24rpx}
	.cartmain .item .content .addnum .i{padding:0 20rpx;color:#2B2B2B;font-weight:bold;font-size:24rpx}
	.cartmain .item .content .addnum .input{flex:1;width:50rpx;border:0;text-align:center;color:#2B2B2B;font-size:24rpx;margin:0 15rpx}
	.cartmain .item .bottom{width:94%;margin:0 3%;border-top:1px #e5e5e5 solid;padding:10rpx 0px;overflow:hidden;color:#ccc;display:flex;align-items:center;justify-content:flex-end}
	.cartmain .item .bottom .f1{display:flex;align-items:center;color:#333}
	.cartmain .item .bottom .f1 image{width:40rpx;height:40rpx;border-radius:4px;margin-right:4px}
	.cartmain .item .bottom .op{border:1px #ff4246 solid;border-radius:10rpx;color:#ff4246;padding:0 10rpx;height:46rpx;line-height:46rpx;margin-left:10rpx}
	.footer{width:100%;background:#fff;margin-top:5px;position:fixed;left:0px;bottom:0px;z-index:8;display:flex;align-items:center;padding:10rpx 20rpx;border-top:1px solid #EFEFEF}
	.footer .radio{flex-shrink:0;width:32rpx;height:32rpx;background:#FFFFFF;border:2rpx solid #BFBFBF;border-radius:50%;margin-right:10rpx}
	.footer .radio .radio-img{width:100%;height:100%}
	.footer .text0{color:#666666;font-size:24rpx}
	.footer .text1{height:110rpx;line-height:110rpx;color:#444;font-weight:bold;font-size:24rpx}
	.footer .text2{color:#F64D00;font-size:36rpx;font-weight:bold}
	.footer .text3{color:#F64D00;font-size:28rpx;font-weight:bold}
	.footer .op{width:216rpx;height:80rpx;line-height:80rpx;border-radius:6rpx;font-weight:bold;color:#fff;font-size:28rpx;text-align:center;margin-left:30rpx}
	.xihuan{height:auto;overflow:hidden;display:flex;align-items:center;width:100%;padding:12rpx 160rpx}
	.xihuan-line{height:auto;padding:0;overflow:hidden;flex:1;height:0;border-top:1px solid #eee}
	.xihuan-text{padding:0 32rpx;text-align:center;display:flex;align-items:center;justify-content:center}
	.xihuan-text .txt{color:#111;font-size:30rpx}
	.xihuan-text .img{text-align:center;width:36rpx;height:36rpx;margin-right:12rpx}
	.prolist{width:100%;height:auto;padding:8rpx 20rpx}
	.data-empty{width:100%;text-align:center;padding-top:100rpx;padding-bottom:100rpx}
	.data-empty-img{width:300rpx;height:300rpx;display:inline-block}
	.data-empty-text{display:block;text-align:center;color:#999999;font-size:32rpx;width:100%;margin-top:30rpx}
	
	.popup__container{position: fixed;bottom: 0;left: 0;right: 0;width:100%;height:auto;z-index:10;background:#fff}
	.popup__overlay{position: fixed;bottom: 0;left: 0;right: 0;width:100%;height: 100%;z-index: 11;opacity:0.3;background:#000}
	.popup__modal{width: 100%;position: absolute;bottom: 0;color: #3d4145;overflow-x: hidden;overflow-y: hidden;opacity:1;padding-bottom:20rpx;background: #fff;border-radius:20rpx 20rpx 0 0;z-index:12;min-height:600rpx;max-height:1000rpx;}
	.popup__title{text-align: center;padding:30rpx;position: relative;position:relative}
	.popup__title-text{font-size:32rpx}
	.popup__close{position:absolute;top:34rpx;right:34rpx}
	.popup__content{width:100%;max-height:880rpx;overflow-y:scroll;padding:20rpx 0;}
</style>