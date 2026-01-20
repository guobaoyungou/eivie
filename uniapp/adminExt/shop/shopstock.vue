<template>
	<view >
		<!-- #ifndef H5 -->
		<view class="navigation">
			<view class='navcontent' :style="{marginTop:navigationMenu.top+'px',width:(navigationMenu.right)+'px'}">
				<view class="header-location-top" :style="{height:navigationMenu.height+'px'}">
					<view class="header-back-but" @click="goBack">
						<image  :src="pre_url+'/static/img/admin/goback.png'"></image>
					</view>
					<view class="header-page-title">录入库存</view>
				</view>
			</view>
		</view>
		<!-- #endif -->
		<view class="content" v-if="isload">
			<view class="item">
				<view class="title-view flex-y-center">
					<view>商品列表</view>
					<view class="but-class" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @click="addshop">添加商品</view>
				</view>
				<view class="product">
					<view v-for="(item, index2) in prodata" :key="index2">
						<view class="item flex">
							<!-- @tap="goto" :data-url="'/pages/shop/product?id=' + item.product.id" -->
							<view class="img">
								<image v-if="item.guige.pic" :src="item.guige.pic"></image>
								<image v-else :src="item.product.pic"></image>
							</view>
							<view class="info flex1">
								<view class="f1">{{item.product.name}}</view>
								<view class="f2">规格：{{item.guige.name}}</view>
								
								<view class="f3">
									<!-- <block><text style="font-weight:bold;">￥{{item.guige.sell_price}}</text></block> -->
									<!-- <text style="padding-left:20rpx"> × {{item.num}}</text> -->
								</view>
							</view>
							<view class="del-view flex-y-center" @tap.stop="clearShopCartFn(item.id)" style="color:#999999;font-size:24rpx">
								<image :src="pre_url+'/static/img/del.png'" style="width:24rpx;height:24rpx;margin-right:6rpx"/>
							</view>
						</view> 
						<view class="flex flex-y-center sb">
							<view class="modify-price flex-y-center">
								<input type="digit" placeholder="录入数量" :value="item.num" class="inputPrice" @input="inputNum($event,index2)">
							</view>
							 * 
							<view class="modify-price flex-y-center">
								<input type="digit" placeholder="进货单价" :value="item.guige.sell_price"  class="inputPrice" @input="inputPrice($event,index2)">
							</view>
							=
							<view class="modify-price flex-y-center">
								<input type="digit" placeholder="总价" :value="item.buytotal"  class="inputPrice" @input="inputTotalPrice($event,index2)">
							</view>
					
						</view>
						
					</view>
					<nodata v-if="nodata" :text="'请添加需要操作的商品'"></nodata>
				</view>
			</view>
			<view style="width: 100%; height:182rpx;"></view>
			<view class="footer flex notabbarbot">

				<button class="op" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @click="tosubmit">
					确定</button>
			</view>
		</view>
		
		<loading v-if="loading"></loading>
	</view>
</template>

<script>
	const app = getApp();
	export default {
		data(){
			return{
				isload:false,
				loading:false,
				nodata:false,
				mid:'',
				pre_url:app.globalData.pre_url,
				prodata:[],
				payTypeArr: [],
				payTypeIndex:0,
				paytype:'',
				dialogShow:false,
				onSharelink:'',
				navigationMenu:{},
				platform: app.globalData.platform,
				items:[],
				orderNotes:'',
				buydata:{},
				
			}
		},
		onLoad(opt) {
			let that = this;
			this.getdatacart();
			var sysinfo = uni.getSystemInfoSync();
			this.statusBarHeight = sysinfo.statusBarHeight;
			this.wxNavigationBarMenu();
			this.isload = true;
		},
		onShow(){
			let that = this;
			this.getdatacart();
		},
		
		methods:{
			goBack(){
				app.goto('/admin/index/index','reLaunch')
			},
			wxNavigationBarMenu:function(){
				if(this.platform=='wx'){
					//胶囊菜单信息
					this.navigationMenu = wx.getMenuButtonBoundingClientRect()
				}
			},
			clearShopCartFn: function (id) {
			  var that = this;
			  that.loading = true;
				uni.showModal({
					title: '提示',
					content: '确认删除该商品吗？',
					success: function (res) {
						if (res.confirm) {
							
							app.post("ApiAdminOrderlr/cartdelete", {cartid:id}, function (res) {
								that.loading = false;
							  that.getdatacart(that.mid)
							});
						} else if (res.cancel) {
						}
					}
				});
			},
			shareBut(){
				this.dialogShow = false;
			},
			showdialog(){
				this.dialogShow = !this.dialogShow;
			},
			inputNum(event,index){
				this.prodata[index].num = event.detail.value;
				this.computetotalprice(index);
			},
			inputPrice(event,index){
				this.prodata[index].guige.sell_price = event.detail.value;
				this.computetotalprice(index);
			},
			inputTotalPrice(event,index){
				this.prodata[index].buytotal = event.detail.value;	
				let prodata = this.prodata
				this.prodata = [];
				var totalprice = prodata[index].buytotal;
				var buynum = prodata[index].num;
				var sell_price = parseFloat( totalprice / buynum).toFixed(2);
				prodata[index].guige.sell_price =sell_price;
				this.prodata = prodata;
			},
			computetotalprice(index){
				var buynum = this.prodata[index].num;
				var sell_price = this.prodata[index].guige.sell_price;
				var buyprice = parseFloat( buynum * sell_price).toFixed(2);
				let prodata = this.prodata;
				 this.prodata = [];
				prodata[index].buytotal =buyprice;
				this.prodata = prodata;
			},
			getdatacart(){
				let that = this;
				that.loading = true;
				app.post('ApiAdminOrderlr/cart', {}, function (res) {
					that.loading = false;
					that.prodata = res.cartlist;

					for (var i = 0; i < that.prodata.length; i++) {
						var thisprodata = that.prodata[i];
						thisprodata.buytotal = parseFloat( thisprodata.num * thisprodata.guige.sell_price).toFixed(2);
					}
					if(that.prodata.length <=0){
						that.nodata = true;
					}
				});
			},
			addshop(){
				app.goto('shopstockgoods'  )
			},
			//提交代付款订单 
			tosubmit: function(e) {
				var that = this;
				var prodata = that.prodata;	
				if(!that.prodata.length) return app.error('请添加商品');
				var prodataIdArr = [];
				for (var i = 0; i < prodata.length; i++) {
					var thisprodata = prodata[i];
					if(thisprodata.num <=0 || thisprodata.num ==undefined){
						app.error('检查录入数量');
						return;
					}
					if(thisprodata.guige.sell_price <=0 || thisprodata.guige.sell_price ==undefined){
						app.error('检查货品单价');
						return;
					}
					var buydata = {
						proid:thisprodata.product.id,
						ggid:thisprodata.guige.id,
						buynum:thisprodata.num,
						buytotal:thisprodata.buytotal,
					}
				  prodataIdArr.push(buydata);
				}
				app.showLoading('提交中');
				app.post('ApiAdminOrderlr/shopsotckSave', {
					prodata:prodataIdArr,
				}, function(res) {
					app.showLoading(false);
					if (res.status == 0) {
						//that.showsuccess(res.data.msg);
						app.error(res.msg);
						return;
					}else{
						app.success(res.msg);
						that.getdatacart();
					}
				});
			},
		}
	}
</script>

<style>
	/* #ifdef H5 */
	/deep/ .input-value{padding: 0px 0px !important;color:#626262 !important;font-size:28rpx;}
	/deep/ .placeholder{color:#626262 !important;font-size:28rpx;}
	/* #endif */
	.headimg-mendian image{ width: 100rpx; height:100rpx; border-radius:10rpx;margin-right: 20rpx;}
	.data-v-31ccf324{padding: 0px 0px !important;color:#626262 !important;font-size:28rpx;}
	.content{width: 95%;margin: 0 auto;}
	.item{width: 100%;border-radius: 12rpx;background: #fff;margin-top: 20rpx;padding: 15rpx;justify-content: space-between;}
	.itemfirst{width: 100%;height:120rpx;margin-top: 20rpx;justify-content: space-between;}
	.itemfirst-options{width: 47%;height: 100%;border-radius: 12rpx;background: #fff;justify-content: space-between;padding: 0rpx 15rpx;}
	.avat-img-view {width: 80rpx;height:80rpx;border-radius: 50%;overflow: hidden;}
	.avat-img-view image{width: 100%;height: 100%;}
	.title-view{justify-content: space-between;padding: 10rpx 0rpx 20rpx 0;border-bottom: 2rpx solid #eeeeee}
	.title-view .but-class{width: 150rpx;height: 50rpx;line-height: 50rpx;color: #fff;text-align: center;font-size: 24rpx;border-radius:35rpx}
	
	.user-info{margin-left: 20rpx;}
	.item .user-info .un-text{font-size: 28rpx;color: rgba(34, 34, 34, 0.7);}
	.item .user-info .tel-text{font-size: 26rpx;color: rgba(34, 34, 34, 0.7);margin-top: 5rpx;}
	.jiantou-img{width: 24rpx;height: 24rpx;}
	.jiantou-img image{width: 100%;height: 100%;}
	.input-view{padding: 10rpx 0rpx;margin-bottom: 10rpx;}
	.input-view .picker-paytype{display: flex;align-items: center;justify-content: space-between;}
	.picker-class{width: 500rpx;}
	.input-view .input-title{width: 150rpx;white-space: nowrap;}
	.input-view .but-class{width: 100rpx;height: 50rpx;line-height: 50rpx;color: #fff;text-align: center;font-size: 24rpx;border-radius:35rpx}
	.address-view{display: flex;align-items: flex-start;}
	.address-chose{justify-content: space-between;width: 540rpx;display: flex;align-items: flex-start;}
	.address-plac{color:#626262;font-size:28rpx;}
	.product {width: 100%;}
	.product .item {position: relative;width: 100%;padding: 20rpx 0 0rpx 0;background: #fff;}
	.product .del-view{position: absolute;right: 10rpx;top: 50%;margin-top: -7px;padding: 10rpx;}
	.product .info {padding-left: 20rpx;}
	.product .info .f1 {color: #222222;font-weight: bold;font-size: 26rpx;line-height: 36rpx;margin-bottom: 10rpx;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp: 2;overflow: hidden;width: 90%;}
	.product .info .f2 {color: #999999;font-size: 24rpx}
	.product .info .f3 {color: #FF4C4C;font-size: 28rpx;display: flex;align-items: center;margin-top: 10rpx}
	
	.product  .modify-price{padding: 10rpx 0rpx;margin-right:10rpx}
	
	.product image {width: 140rpx;height: 140rpx}
	
	.inputPrice {border: 1px solid #ddd; width: 180rpx; height: 60rpx; border-radius: 10rpx; padding: 0 10rpx;}
	.footer {width: 96%;background: #fff;margin-top: 5px;position: fixed;left: 0px;bottom: 0px;padding:20rpx 2% 0 2%;display: flex;align-items: center;z-index: 8;box-sizing:content-box}
	.footer .text1 {height: 110rpx;line-height: 110rpx;color: #2a2a2a;font-size: 30rpx;}
	.footer .text1 text {color: #e94745;font-size: 32rpx;}
	.footer .op {width: 200rpx;height: 80rpx;line-height: 80rpx;color: #fff;text-align: center;font-size: 30rpx;border-radius: 44rpx}
	.footer .op[disabled] { background: #aaa !important; color: #666;}
	
	.navigation {width: 100%;padding-bottom:10px;overflow: hidden;}
	.navcontent {display: flex;align-items: center;padding-left: 10px;}
	.header-location-top{position: relative;display: flex;justify-content: center;align-items: center;flex:1;}
	.header-back-but{position: absolute;left:12rpx;display: flex;align-items: center;width: 35rpx;height: 35rpx;overflow: hidden;}
	.header-back-but image{width: 17rpx;height: 31rpx;} 
	.header-page-title{display: flex;flex: 1;align-items: center;justify-content: center;font-size: 34rpx;letter-spacing: 2rpx;}
	.del-view{color:#999999;font-size:24rpx}
	.del-view image{width:24rpx;height:24rpx;margin-right:6rpx}
	.buyprice{height: 65rpx;line-height: 65rpx;color: #FF4C4C;font-weight: bold;    width: 45%;
    text-align: left;}
	.sb{justify-content: space-between;}
</style>