<template>
	<view>
		<block v-if="isload">
			
			<view class="top-view flex"  :style="{backgroundImage:`linear-gradient(to right , rgba(${t('color1rgb')},0.7),${t('color1')})`}">
				<view class="left-view flex-col">
					<view class="title-view">金额总计（元）</view>
					<view class="price-view">{{totalprice}}</view>
				</view>
				<view class="right-view">
					<image :src="pre_url+'/static/img/deposit/buy.png'"></image>
				</view>
			</view>
			<view class="content-view">
				<view class="filter-view flex flex-bt">
					<view class="filter-title">{{t('押金')}}名称</view>
					<!-- <view class="picker-view">
						<picker @change="bindPickerChange" :value="index" :range="array">
							<view class="flex flex-y-center">
								<view class="picker-text">选择类型</view>
								<image class="jiantou-icon" :src="pre_url+'/static/img/left_jiantou.png'"></image>
							</view>
						</picker>
					</view> -->
				</view>
				<view class="content-list">
					<scroll-view style="max-height: 65vh;height: auto;;" scroll-y>
						<block v-for="(item,index) in datalist" :key="index">
							<view class="list-options flex" :style="{borderColor:t('color1')}">
								<view class="img-view">
									<image :src="item.pic"></image>
								</view>
								<view class="info-view flex-col">
									<view class="title-view">{{item.name}}</view>
									<view class="bname">适用商家：{{item.bname}}</view>
									<view class="price-fun-view flex flex-y-center flex-bt">
										<view class="price-text" :style="{color:t('color1')}">￥{{item.money}}</view>
										<view class="fun-view flex flex-y-center">
											<view class="fun-class minus-class flex-xy-center" :style="{Color:t('color1'),borderColor:t('color1')}" @tap="subnum" :data-key="index" v-if="item.buynum > 0">-</view>
											<view class="input-view" v-if="item.buynum > 0">{{item.buynum}}</view>
											<view class="fun-class add-class flex-xy-center" :style="{backgroundColor:t('color1'),borderColor:t('color1')}"  @tap="addnum" :data-key="index" v-if="numkey ==-1 || numkey == index  ">+</view>
										</view>
									</view>
								</view>
							</view>
						</block>
						<nodata v-if="nodata"></nodata>
					</scroll-view>
				</view>
			</view>
			<view style="height: calc(140rpx + env(safe-area-inset-bottom))"></view>
			<view class="buy-but-view" >
				<view class="buy-but" :style="{backgroundColor:t('color1')}" @tap="toBuy">
					确定购买
				</view>
			</view>
			
		</block>
		<loading v-if="loading"></loading>
		<nodata v-if="nodata"></nodata>
		<nomore v-if="nomore"></nomore>
	</view>
</template>

<script>
	var app = getApp();
	export default {
		data(){
			return{
				pre_url:app.globalData.pre_url,
				isload: false,
				nodata:false,
				nomore:false,
				loading:false,
				array:['1','2','3'],
				switchIndex:0,
				datalist:[],
				totalprice:'0.00',
				numdata:[],
				numkey:-1,//操作的押金key
			
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			
			this.getdata();
		},
		onPullDownRefresh: function () {
			this.getdata();
		},
		onReachBottom: function () {
			if (!this.nodata && !this.nomore) {
				this.pagenum = this.pagenum + 1;
				this.getdata(true);
			}
		},
		methods:{
			getdata: function (loadmore) {
				if(!loadmore){
					this.pagenum = 1;
					this.datalist = [];
				}
				var that = this;
				var pagenum = that.pagenum;
				that.nodata = false;
				that.nomore = false;
				that.loading = true;
				app.post('ApiDeposit/getDepositList', {pagenum: pagenum}, function (res) {
					that.loading = false;
					var data = res.data;
					if (pagenum == 1) {
						uni.setNavigationBarTitle({
							title: that.t('押金') + '购买'
						});
						that.datalist = data;
						if (data.length == 0) {
							that.nodata = true;
						}
	
						that.loaded();
					}else{
						if (data.length == 0) {
							that.nomore = true;
						} else {
							var datalist = that.datalist;
							var newdata = datalist.concat(data);
							that.datalist = newdata;
						}
					}
				});
			},
			addnum(e){
				var that = this;
				var key = e.currentTarget.dataset.key;
				console.log(key,'keykeykeykey');
				if(that.numkey != -1 && that.numkey != key){
					 app.error('已选择其他押金');
					 return;
				}
				var depositlist = that.datalist;
				depositlist[key].buynum++;
				that.depositlist = depositlist
				that.numkey = key;
				that.computePrice();
			},
			subnum(e){
				var that = this;
				var key = e.currentTarget.dataset.key;
				var depositlist = that.datalist;
				var deposit = depositlist[key];
				
				var buynum = deposit.buynum;
				buynum = buynum - 1;
				
				depositlist[key].buynum = buynum;
				that.computePrice();
				if(buynum ==0){
					that.numkey = -1;
					console.log(that.numkey,'=====numkey');
					return;
				}
			
			},
			computePrice(){
				var key = this.numkey;
				var deposit = this.datalist[key]
				var totalprice = deposit.buynum * deposit.money;
				this.totalprice = parseFloat(totalprice).toFixed(2);
			
			},
			toBuy(){
				var that = this;
				var key = that.numkey;
				if(key ==-1){
					app.error('选择需要购买的'+that.t('押金'));
					return;
				}
				var deposit = that.datalist[key];
				var id = deposit.id;
				var num = deposit.buynum;
				that.loading = true;
				app.post('ApiDeposit/createorder',{id:id,num:num}, function(res) {
					that.loading = false;
					if(res.status ==0){
						app.error(res.msg);
						return;
					}
					app.goto('/pagesExt/pay/pay?id=' + res.payorderid);
				})
			}
			
		}
	}
</script>

<style>
	.top-view{width: 100%;height: 300rpx;padding: 30rpx;align-items: flex-start;justify-content: space-between;}
	.top-view .left-view{padding: 20rpx;}
	.top-view .left-view .title-view{font-size: 24rpx;color: #fff;opacity: .7;}
	.top-view .left-view .price-view{font-size: 56rpx;font-weight: bold;color: #fff;margin-top: 10rpx;}
	.top-view .right-view{width: 200rpx;height: 200rpx;}
	.top-view .right-view image{width: 100%;height: 100%;}
	.content-view{width: 100%;background: #fff;border-radius: 40rpx 40rpx 0rpx 0rpx;height: calc(100vh - 240rpx);position: absolute;top: 240rpx;
	left: 0;padding: 30rpx 20rpx;}
	.content-view .filter-view{width: 100%;align-items: center;}
	.content-view .filter-view .filter-title{font-size: 30rpx;font-weight: bold;color: #333;}
	.content-view .picker-view{align-items: center;}
	.content-view .picker-view .jiantou-icon{width: 30rpx;height: 30rpx;margin-left: 10rpx;}
	.content-view .picker-view .picker-text{font-size: 26rpx;color: #999;}
	.content-view .content-list{width: 100%;margin: 20rpx 0rpx;}
	.content-view .content-list .list-options{width: 100%;border-radius: 16rpx;padding: 15rpx;margin-bottom: 20rpx;border: 0.5px solid;}
	.content-view .content-list .list-options .img-view{width: 170rpx;height: 170rpx;border-radius: 16rpx;overflow: hidden;}
	.content-list .list-options .img-view image{width: 100%;height: 100%;}
	.content-list .list-options .info-view{width: calc(100% - 170rpx);height: 170rpx;padding-left: 15rpx;padding-top: 10rpx;padding-bottom: 10rpx;
	justify-content: space-between;}
	.list-options .info-view .title-view{font-size: 30rpx;font-weight: bold;color: #333;}
	.list-options .info-view .price-fun-view{}
	.list-options .info-view .price-fun-view .price-text{font-size: 34rpx;font-weight: bold;}
	.list-options .info-view .price-fun-view .fun-view{}
	.price-fun-view .fun-view .input-view{width: 70rpx;border: 1px res solid;text-align: center;font-size: 30rpx;color: #000;text-align: center;}
	.price-fun-view .fun-view .fun-class{width: 46rpx;height: 46rpx;border-radius: 50rpx;overflow: hidden;font-size: 36rpx;font-weight: bold;border: 2px solid;line-height: 46rpx;}
	.price-fun-view .fun-view .add-class{color: #fff;}

	.buy-but-view{position: fixed;bottom: 0;left:0;background: #fff;width: 100%;height: calc(110rpx + env(safe-area-inset-bottom));}
	.buy-but-view .buy-but{width: 96%;font-size: 32rpx;letter-spacing: 2rpx;text-align: center;border-radius: 40rpx;overflow: hidden;margin: 20rpx auto 0;color: #fff;
	padding: 20rpx 0rpx;}
	.bname{    font-size: 24rpx;color: #616161;}
</style>