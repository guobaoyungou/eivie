<template>
<view>
	<block v-if="isload">
		<view class="top-view" :style="{backgroundImage:`linear-gradient(rgba(${t('color1rgb')},0.2),#f6f6f6)`}">
			<view class="card-view flex-col flex-y-center" :style="{backgroundImage:`url(${pre_url}/static/img/deposit/cardbg.png)`,backgroundColor:`rgba(${t('color1rgb')},0.8)`}">
				<view class="title-view flex-y-center">当前{{t('押金')}}余额（元）<image class="title-icon" :src="`${pre_url}/static/img/admin/jieshiicon.png`"  @click="showExplain"></image></view>
				<view class="price-view">{{total_data.totalmoney}}</view>
				<view class="but-view flex flex-y-center">
					<view class='but-class but1' :style="{color:t('color1')}" @click="goto" data-url="refund">退还</view>
					<view class='but-class but2' :style="{background:t('color1')}" @click="goto" data-url="buy">购买</view>
				</view>
			</view>
		</view>
		<view class="tab-view flex flex-y-center">
			<view @click='tabChange(0)' :class="[type == 0 ? 'tab-options-active':'','tab-options']">{{t('押金')}}<view v-if='type == 0' class='tab-tag' :style="{background:t('color1')}"></view></view>
			<view @click='tabChange(1)' :class="[type == 1 ? 'tab-options-active':'','tab-options']">{{t('押金')}}记录<view v-if='type == 1' class='tab-tag' :style="{background:t('color1')}"></view></view>
		</view>
		<!-- <view class="list-view flex-col" v-if="type==0">
			<block v-for="(item,index) in datalist">
				<view class="list-options flex-col">
					<view class="title-name">{{item.name}}</view>
					<view class="num-view">数量 x{{item.num}}</view>
					<view class="time-view">{{item.createtime}}</view>
					<view class="price-view" :style="{color:t('color1')}">￥{{item.totalmoney}}</view>
				
				</view>
			</block>
		</view> -->
		<view class="content-view" v-if="type ==0">
			<view class="content-list">
				<scroll-view style="max-height: 65vh;height: auto;" scroll-y>
					<block v-for="(item,index) in datalist" :key="index">
						<view class="list-options flex">
							<view class="img-view">
								<image :src="item.pic"></image>
							</view>
							<view class="info-view flex-col">
								<view class="title-view">{{item.name}}</view>
								<view class="price-fun-view flex flex-y-center flex-bt">
									<view class="flex flex-y-center flex-bt" style="line-height: 46rpx;">
										<view class="price-text" :style="{color:t('color1')}">￥{{item.money}}</view>
										<view style="margin-left: 20rpx;">数量：{{item.num}}</view>
									</view>
									<view class="fun-view flex flex-y-center">
										<view class="fun-class minus-class flex-xy-center" :style="{Color:t('color1'),borderColor:t('color1')}" @tap="subnum" :data-key="index" v-if="item.buynum > 0">-</view>
										<view class="input-view" v-if="item.buynum > 0">{{item.buynum}}</view>
										<view class="fun-class add-class flex-xy-center"  :style="{backgroundColor:t('color1'),borderColor:t('color1')}" @tap="addnum" :data-key="index" v-if="numkey ==-1 || numkey == index  ">+</view>
									</view>
								</view>
							</view>
						</view>
					</block>
				</scroll-view>
				<nomore v-if="nomore"></nomore>
				<nodata v-if="nodata"></nodata>
			</view>
		</view>
		<view class="list-view flex-col" v-else>
			<scroll-view style="max-height: 65vh;height: auto;" scroll-y>
			<block v-for="(item,index) in datalist">
				<view class="list-options flex-col">
					<view class="title-name">{{item.name}}</view>
					<view class="num-view">数量 x{{item.num}}</view>
					<view class="flex-y-center flex-bt">
						<view class="time-view">{{item.createtime}}</view>
						<view class="price-view" :style="{color:t('color1')}"><text v-if="item.type==1">-</text>￥{{item.totalmoney}}</view>
					</view>
				
				</view>
			</block>
			
			</scroll-view>
			<nomore v-if="nomore"></nomore>
			<nodata v-if="nodata"></nodata>
		</view>
		<uni-popup ref="refPopup">
			<view class="popup-content flex flex-col" :style="{backgroundImage:`linear-gradient(rgba(${t('color1rgb')},0.2),#fff)`}">
				<view class="popup-img">
					<image :src="pre_url+'/static/img/deposit/buy.png'"></image>
				</view>
				<view class="title-view">抱歉 押金不足！</view>
				<view class="tips-view">请勾选需要XXXXXXXXXX</view>
				<view class="popup-but" :style="{background:t('color1')}">确认</view>
			</view>
		</uni-popup>
	</block>
			<loading v-if="loading"></loading>
</view>
</template>

<script>
	var app = getApp();
	export default {
		data(){
			return {
				opt:{},
				loading:false,
				isload: false,
				pre_url:app.globalData.pre_url,
				type:0,
				nomore:false,
				nodata:false,
				pagenum: 1,
				datalist:[],
				total_data:{},
			}
		},
		  onLoad: function (opt) {
				this.opt = app.getopts(opt);
				if(this.opt.type){
					this.type = this.opt.type;
				}
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
		onShow() {
			this.getdata();
		},
		methods:{
			getdata: function (loadmore) {
				if(!loadmore){
					this.pagenum = 1;
					this.datalist = [];
				}
				var that = this;
				var pagenum = that.pagenum;
				var type = that.type;
				that.nodata = false;
				that.nomore = false;
				that.loading = true;
				app.post('ApiDeposit/getDepositLogList', {type: type,pagenum: pagenum}, function (res) {
					that.loading = false;
					var data = res.data;
					that.loaded();
					if (pagenum == 1) {
						uni.setNavigationBarTitle({
							title: that.t('押金') + '记录'
						});
						that.datalist = data;
						if (data.length == 0) {
							that.nodata = true;
						}
						that.total_data = res.total_data;
						that.set = res.set;
						
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
			tabChange(index){
				this.type = index;
				this.pagenum = 1;
				this.datalist = [];
				this.getdata();
			},
			showExplain(e){
				var that = this;
				uni.showModal({
					title: '',
					content: '当前'+that.t('押金')+'余额= 已购买'+that.t('押金')+'- 已回收物品'+that.t('押金')+'（未回收物品'+that.t('押金')+'冻结暂未扣除）',
					showCancel:false
				});
			},
		}
	}
</script>

<style>
	.top-view{width: 100%;height: 370rpx;padding: 30rpx;}
	.top-view .card-view{width: 100%;height: 330rpx;border-radius: 20rpx;overflow: hidden;background-repeat: no-repeat;background-size: 168% 160%;background-position: -176rpx -48rpx;justify-content: flex-end;padding: 20rpx;}
	.top-view .card-view .title-view{font-size: 24rpx;color: #ececec;opacity: .7;}
	.top-view .card-view .price-view{font-size:60rpx;font-weight: bold;color: #fff;letter-spacing: 3rpx;margin-bottom: 60rpx;}
	.top-view .card-view .price-view .but-view{margin-top: 60rpx;}
	.top-view .card-view .but-view .but-class{font-size: 26rpx;width: 240rpx;text-align: center;border-radius: 50rpx;padding: 18rpx 0rpx;}
	.top-view .card-view .but-view .but1{background: #fff;margin-right: 35rpx;}
	.top-view .card-view .but-view .but2{color: #fff;margin-left: 35rpx;}
	/*  */
	.tab-view{width: 100%;justify-content: space-around;padding: 30rpx 60rpx;}
	.tab-view .tab-options{position: relative;font-size: 28rpx;font-weight: bold;color: #999;letter-spacing: 2rpx;}
	.tab-view .tab-options-active{color: #333 !important;}
	.tab-view .tab-options-active .tab-tag{position: absolute;bottom: -10rpx;left: 50%;transform: translateX(-50%);width: 40rpx;height: 8rpx;border-radius:10rpx;opacity: .8;}
	/*  */
	.list-view{width: 100%;padding:20rpx 30rpx;}
	.list-options{width: 100%;background: #fff;border-radius: 16rpx;overflow: hidden;padding: 25rpx;margin-bottom: 20rpx;position: relative;}
	.list-options .title-name{font-size: 30rpx;color: #333;width: 100%;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;font-weight: bold;}
	.list-options .num-view{font-size: 26rpx;color: #999;margin-top: 10rpx;}
	.list-options .time-view{font-size: 24rpx;color: #afafaf;margin-top: 30rpx;}
	.list-options .price-view{font-size: 35rpx;font-weight: bold;}
	.list-options .status-view{position: absolute;right: 30rpx;top: 25rpx;font-size: 26rpx;}
	.reason{color: #FD5878;margin-top: 10rpx;font-size: 26rpx;}
	/*  */
	.popup-content{width: 480rpx;height:480rpx;background: #fff;border-radius: 40rpx;overflow: hidden;padding: 30rpx}
	.popup-content .popup-img{width: 180rpx;height: 180rpx;margin: 0 auto;}
	.popup-content .popup-img image{width: 100%;height: 100%;}
	.popup-content .title-view{font-size: 30rpx;font-weight: bold;color: #000;letter-spacing: 3rpx;text-align: center;margin-top: 30rpx;}
	.popup-content .tips-view{font-size: 26rpx;color: #999;text-align: center;margin-top: 10rpx;}
	.popup-content .popup-but{width: 100%;font-size: 28rpx;padding: 18rpx;text-align: center;border-radius: 40rpx;letter-spacing: 2rpx;font-weight: bold;color: #fff;margin-top: 30rpx;}
	.title-icon{width: 28rpx;height: 28rpx;margin-left: 10rpx;}
	
	/* 押金列表 */
	.content-view{width: 100%;padding: 20rpx 30rpx 20rpx 30rpx}
	
	.content-list{width: 100%;margin: 0rpx 0rpx;}
	.content-list .list-options{width: 100%;border-radius: 16rpx;padding: 15rpx;margin-bottom: 20rpx;}
	.content-list .list-options .img-view{width: 170rpx;height: 170rpx;border-radius: 16rpx;overflow: hidden;}
	.list-options .img-view image{width: 100%;height: 100%;}
	.list-options .info-view{width: calc(100% - 170rpx);height: 170rpx;padding-left: 15rpx;padding-top: 10rpx;padding-bottom: 10rpx;
	justify-content: space-between;}
	
</style>