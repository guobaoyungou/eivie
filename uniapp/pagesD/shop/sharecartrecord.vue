<template>
	<view class="container">
		<block v-if="isload">
			<block v-if="sharelist.length>0">
				<view class="cartmain">
					<view class="item">
						<view class="content" v-for="(item,index) in sharelist" @tap="goto" :data-url="'/pages/shop/product?id=' + item.proid" :key="index">
							<view class="flex flex-xy-center click-radio" @tap.stop="changeradio" :data-index="index">
								<view class="radio" :style="item.checked ? 'background:'+t('color1')+';border:0' : ''">
									<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
								</view>
							</view>
							<view class="proinfo">
								<image :src="item.guige.pic?item.guige.pic:item.product.pic" class="img" />
								<view class="detail">
									<view class="title"><text>{{item.product.name}}</text></view>
									<view class="desc"><text>{{item.guige.name}}</text></view>
									<view class="price" :style="{color:t('color1')}">
										<view><text style="font-size:24rpx">￥</text>{{item.guige.sell_price}} × {{item.num || 1}}</view>
									</view>
								</view>
							</view>
						</view>
					</view>
				</view>
			</block>
		</block>

		<loading v-if="loading"></loading>
		<block v-if="sharelist.length>0">
			<view style="height:auto;position:relative">
				<view style="width:100%;height:110rpx"></view>
				<view class="footer flex" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
					<view @tap.stop="changeradioAll" class="radio" :style="allchecked ? 'background:'+t('color1')+';border:0' : ''">
						<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
					</view>
					<view @tap.stop="changeradioAll" class="text0">全选（{{selectedcount}}）</view>
					<view class="flex1"></view>
					
					<view class="op" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="addcart">加入购物车</view>
				</view>
			</view>
		</block>
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
				sharelist: [],
				selectedcount: 0,
				selectedproduct:[],
				allchecked: true,
				cart:0,
			};
		},

		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.cart = this.opt.cart || 0;
		},
		onShow: function() {
			this.getdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		methods: {
			getdata: function() {
				var that = this;
				that.loading = true;
				app.get('ApiShop/shareCartRecord', {id:that.cart}, function(res) {
					that.loading = false;
					that.sharelist = res.datalist;
					uni.setNavigationBarTitle({
						title: res.title
					});
					that.calculate();
					that.loaded();
				});
			},
			calculate: function() {
			  var that = this;
			  var sharelist = that.sharelist;
			  var selectedcount = 0;
			  var selectedproduct = [];
			  
			  for (var i in sharelist) {
			    if (sharelist[i].checked) {
			      selectedcount++;

			      selectedproduct.push({
			        proid: sharelist[i].proid,
			        ggid: sharelist[i].ggid,
			        num: sharelist[i].num,
			      });
			    }
			  }
			  
			  that.selectedcount = selectedcount;
			  that.selectedproduct = selectedproduct;
			},
			changeradioAll:function(){
			  var that = this;
				var sharelist = that.sharelist;
				var allchecked = that.allchecked
				for(var i in sharelist){
					sharelist[i].checked = allchecked ? false : true;
				}
				that.sharelist = sharelist;
				that.allchecked = allchecked ? false : true;
			  that.calculate();
			},
			changeradio:function(e){
				var that = this;
				var index = e.currentTarget.dataset.index;
				var index2 = e.currentTarget.dataset.index2;
				var sharelist = that.sharelist;
				var checked = sharelist[index].checked;
				if(checked){
					sharelist[index].checked = false;
				}else{
					sharelist[index].checked = true;
				}
				that.sharelist = sharelist;			
				that.calculate();
				console.log(that.selectedproduct,'选中');
			},
			addcart: function() {
			  var that = this;
			  var selectedproduct = that.selectedproduct;
			    
				if (selectedproduct.length === 0) {
					app.error('请先选择商品');
					return;
				}
			  
				that.loading = true;
				var successCount = 0;
				var failCount = 0;
				var total = selectedproduct.length;
			  
			  //循环
				selectedproduct.forEach(item => {
					app.post('ApiShop/addcart', {proid: item.proid,ggid: item.ggid,num: item.num || 1}, function(res) {
						if (res.status == 1) {
							successCount++;
						} else {
							failCount++;
						}
						
						//所有请求完成后
						if (successCount + failCount === total) {
							that.loading = false;
							if (failCount === 0) {
								app.success('加入购物车成功');
							} else if (successCount > 0) {
								app.success(`成功添加 ${successCount} 件商品${failCount > 0 ? `，${failCount}件失败` : ''}`);
							} else {
								app.error('全部商品添加失败');
							}
						}
						setTimeout(function () {
							app.goto('/pages/shop/cart','redirectTo');
						}, 1000)
					});
				});
			},
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
</style>