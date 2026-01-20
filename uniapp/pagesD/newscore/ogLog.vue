<template>
	<view class="container">
	<view v-if="isload">
			<view class="navigation">
				<view class='navcontent' :style="{marginTop:navigationMenu.top+'px',width:(navigationMenu.right)+'px'}">
					<view class="header-location-top" :style="{height:navigationMenu.height+'px'}">
						<view class="header-back-but" @tap="goback">
							<image :src="`${pre_url}/static/img/hotel/fanhui.png`"></image>
						</view>
						<view class="header-page-title">
							{{t('新积分')}}
						</view>
					</view>
				</view>
			</view>
		<view v-if="showyejidata" class="top-view flex-col">
			<view class="top-data-list flex flex-y-center">
				<view class="top-column">
					<view class="data-options flex-col">
						<view class="title-text">{{t('会员')}}积分</view>
						<view class="num-text">{{total_data.score_total_m}}</view>
					</view>
					<view class="data-options flex-col" style="margin: 30rpx 0;">
						<view class="line-class-width"></view>
					</view>
					<view class="data-options flex-col">
						<view class="title-text">{{textset['累计补贴']}}</view>
						<view class="num-text">{{total_data.score_total_release_m}}</view>
					</view>
				</view>
				<view class="line-class"></view>
				<view class="top-column">
					<view class="data-options flex-col">
						<view class="title-text">{{t('商户')}}积分</view>
						<view class="num-text">{{total_data.score_total_b}}</view>
					</view>
					<view class="data-options flex-col" style="margin: 30rpx 0;">
						<view class="line-class-width"></view>
					</view>
					<view class="data-options flex-col">
						<view class="title-text">{{textset['累计补贴']}}</view>
						<view class="num-text">{{total_data.score_total_release_b}}</view>
					</view>
				</view>
			</view>
		</view>
		<!-- 选项卡 -->
		<dd-tab :itemdata="[t('会员')+t('新积分'),t('商户')+t('新积分')]" :itemst="['0','1']" :st="st" :showstatus="showstatus" :isfixed="false" @changetab="changetab"></dd-tab>
		<view class="content" id="datalist">
				 <block v-for="(item, index) in datalist" :key="index"> 
					<view class="list-item" >
						<view class="item-title" :class="item.order_type=='shop'?'shop-title':'maidan-title'">
							<view class="item-ordernum">
								<image v-if="item.order_type=='shop'" :src="pre_url+'/static/img/newscore_shop.png'" mode="aspectFit" />
								<image v-if="item.order_type=='maidan'" :src="pre_url+'/static/img/newscore_maidan.png'" mode="aspectFit" />
								<view class="ordernum-content">
									<view class="shop-label" v-if="item.order_type=='shop'">商城</view>
									<text class="shop-label" v-if="item.order_type=='maidan'">买单</text>
									<text class="ordernum-text">{{item.ordernum}}</text>
								</view>
							</view>
							<view class="item-url" :style="{color: item.order_type=='shop' ? '#742828' : '#094652'}" @tap="goto" :data-url="'/pagesD/newscore/releaseLog?id='+item.id">
								<text class="url-text">查看明细 </text>
								<text class="iconfont iconjiantou" style="font-size:28rpx"></text>
							</view>
						</view>
					  <view class="item-row data-row">
						<view class="column left-column">
						  <text class="data-item"><text class="data-label">发放数量:</text> <text class="data-value">{{item.score}}</text></text>
						  <text class="data-item"><text class="data-label">剩余数量:</text> <text class="data-value">{{item.remain}}</text></text>
						</view>
						<view class="column right-column">
						  <text class="data-item"><text class="data-label">释放数量:</text> <text class="data-value">{{item.release_score || 0}}</text></text>
						  <text class="data-item"><text class="data-label">累计补贴抵用券:</text> <text class="data-value">{{item.send_all|| 0}}</text></text>
						</view>
					  </view>
					  <view class="item-row bottom-row">
						<view class="left-info">
							<view class="ratio-label" v-if="item.status==0">
								<text class="label">上期释放</text>
								<text class="ratio-value">{{item.last_circle_send}}</text>
							</view>
							<button class="btn-mini3" v-if="item.status==1">已完成</button>
							<button class="btn-mini3" v-if="item.status==2">已撤销</button>
						</view>
						<text class="time">{{item.createtime}}</text>
					  </view>
					  <view class="item-row bottom-row">
							<view class="left-info" style="min-width: 50%;">
								<view class="ratio-label" v-if="!isNull(item.newscore_pack_ratio) && item.newscore_pack_ratio > 0">
									<text class="label">{{t('加速包')}}</text>
									<text class="ratio-value">{{item.newscore_pack_ratio}}%</text>
								</view>
							</view>
							<button @tap="goto" :data-url="'/pagesD/newscore/toScore?id='+item.id" v-if="set.to_score_status==1" class="btn-mini2">转为{{t('积分')}}</button>
							
					  </view>
					</view>
				 </block>
				 
		 </view>
		<nodata v-if="nodata"></nodata>
		<nomore v-if="nomore"></nomore>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
	</view>
	<uni-popup ref="popupMiandan" type='center'>
		<view class="popup-miandan-content">
			<view class="popup-miandan-price flex">
				<view class="price-num-text popup-color-text">{{recieve_money || 0}}</view>
				<view class="right-text popup-color-text">元</view>
			</view>
			<view class="popup-leiji-miandan popup-color-text">获得补贴金额</view>
			<view class="popup-guishu flex-col">
				<button class="popup-receive-btn" @click="handleReceive">领取</button>
			</view>
			<image :src="`${pre_url}/static/img/miandanpopupbg.png`" mode="widthFix" />
		</view>
		<!-- 关闭弹窗按钮  -->
		<view class="popupMiandan-close" @click="popupMiandanclose">
			<image :src="pre_url+'/static/img/close2.png'"></image>
		</view>
	</uni-popup>
	</view>
</template>

<script>
	var app = getApp();
	export default{
		components: {},
		data(){
			return{
				isload:false,
				pre_url: app.globalData.pre_url,
				nodata:false,
				nomore:false,
				loading:false,
				menuindex:-1,
				datalist: [],
				pagenum: 1,
				total_data:{},
				is_withdraw:0,
				showstatus:[1,1],
				st:0,
				set:{},
				opt:{},
				showyejidata:false,//是否显示头部业绩数据
				navigationMenu:{},
				recieve_money:0,//待领取金额
				textset:{}
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
		methods: {
		  getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
			var that = this;
			var pagenum = that.pagenum;
			var st = that.st;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
		    app.post('ApiNewScore/og_log', {st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
				var data = res.datalist;
				that.showyejidata = res.showyejidata;
				that.showLastCircleYeji = res.showLastCircleYeji;
				if (pagenum == 1) {
					uni.setNavigationBarTitle({
						title: that.t('新积分') + '记录'
					});
				
					that.datalist = data;
					if (data.length == 0) {
						that.nodata = true;
					}
					that.total_data = res.total_data;
					that.set = res.set;
					that.recieve_money = res.recieve_money;
					// 使用 $nextTick 确保组件已经渲染完成
					if(res.recieve_money>0){
						that.$nextTick(() => {
							if (that.$refs.popupMiandan) {
								that.$refs.popupMiandan.open();
							}
						})
					}
					that.textset = res.textset;
					
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
			changetab: function (st) {
			  this.st = st;
			  uni.pageScrollTo({
			    scrollTop: 0,
			    duration: 0
			  });
			  this.getdata();
			},
			// 关闭弹窗的方法
			popupMiandanclose: function() {
				if (this.$refs.popupMiandan) {
					this.$refs.popupMiandan.close();
				}
			},
			// 安全打开弹窗的方法
			openPopupSafely: function() {
				const that = this;
				const tryOpen = () => {
					if (that.$refs.popupMiandan && that.$refs.popupMiandan.open) {
						that.$refs.popupMiandan.open();
					} else {
						// 如果组件还没有准备好，再等一下
						setTimeout(tryOpen, 100);
					}
				};
				that.$nextTick(tryOpen);
			},
			// 处理领取按钮点击
			handleReceive: function() {
				// 这里添加领取逻辑
				console.log('点击了领取按钮');
				var that = this;
				app.showLoading('领取中');
				app.post('ApiNewScore/recieve_money', {}, function (res) {
					app.showLoading(false);
					if (res.status == 1) {
						app.success();
						setTimeout(function () {
							that.getdata();
						}, 1000);
					}else{
						app.error(res.msg);
						return;
					}
				});
				// 领取完成后关闭弹窗
				this.popupMiandanclose();
			},
			
		}
	}
</script>

<style>
	.top-view{width: 100%;background: #FC2D41;align-items: center;}
	.points-view{padding: 40rpx 0rpx;width: 100%;text-align: center;}
	.points-view .points-title-view{font-size: 28rpx;color: #ecdd36;align-items: center;width: 100%;justify-content: center;}
	.points-view .points-title-view .icon-class{width: 24rpx;height: 24rpx;margin-right: 5rpx;}
	.points-view .points-num{font-size: 62rpx;color: #fff;margin-top: 10rpx;}
	.top-data-list{width: 100%;padding: 35rpx 0rpx;justify-content: center;}
	.top-data-list .data-options{text-align: center;}
	.top-data-list .line-class{height: 240rpx;border-left: 1rpx rgba(255, 255, 255, .3) solid;}
	.top-data-list .line-class-width{width: 20%;border-top: 8rpx #f6f6f6 solid;margin: auto;border-radius: 20rpx;}
	.top-data-list .data-options .title-text{font-size: 26rpx;color: #ecdd36;white-space: nowrap;}
	.top-data-list .data-options .num-text{font-size: 36rpx;color:  rgba(255, 255, 255);margin-top: 15rpx;}
	.list-title-view{font-size: 28rpx;color: #828282;padding: 30rpx 20rpx;}
	.options-view{background: #fff;border-bottom: 1px #f6f6f6 solid;padding: 30rpx 20rpx;align-items: center;}
	.options-view .left-view{}
	.options-view .left-view .left-title{font-size: 26rpx;color: #333;font-weight: bold;white-space: nowrap;}
	.options-view .left-view .time-text{font-size: 24rpx;color: #828282;margin-top: 20rpx;}
	.price-view{font-size: 30rpx;color: #fb443e;}
	
	.tabs {
	  display: flex;
	  background-color: white;
	  margin-top: 20rpx;
	}
	.tab {
	  flex: 1;
	  text-align: center;
	  padding: 20rpx 0;
	  font-size: 28rpx;
	}
	.tab.active {
	  color: #ff4d4f;
	  border-bottom: 4rpx solid #FC2D41;
	}
	
	.content {
	  margin-top: 20rpx;
	  background: #F7F8FA;
	  min-height: 100vh;
	  padding-bottom: 20rpx;
	}
	.list-item {
	  background: #FFFFFF;
	  margin: 0 24rpx 28rpx 24rpx;
	  padding: 0;
	  border-radius: 20rpx;
	  overflow: hidden;
	  box-shadow: 0 6rpx 20rpx rgba(0, 0, 0, 0.05), 0 2rpx 6rpx rgba(0, 0, 0, 0.03);
	  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
	  position: relative;
	}
	
	.list-item::before {
	  content: '';
	  position: absolute;
	  top: 0;
	  left: 0;
	  right: 0;
	  height: 2rpx;
	  background: linear-gradient(90deg, transparent 0%, #FFE5E9 20%, #FFE5E9 80%, transparent 100%);
	  z-index: 1;
	}
	
	.list-item:active {
	  transform: scale(0.98);
	  box-shadow: 0 3rpx 12rpx rgba(0, 0, 0, 0.04), 0 1rpx 4rpx rgba(0, 0, 0, 0.02);
	}
	
	.item-row {
	  display: flex;
	  justify-content: space-between;
	  margin-bottom: 0;
	  font-size: 28rpx;
	  padding: 0 28rpx;
	}
	
	.data-row {
	  padding: 16rpx 10rpx 10rpx 5rpx;
	  margin-bottom: 0;
	  background: #FFFFFF;
	  position: relative;
	}
	
	.data-row::after {
	  content: '';
	  position: absolute;
	  bottom: 0;
	  left: 28rpx;
	  right: 28rpx;
	  height: 1rpx;
	  background: linear-gradient(90deg, transparent 0%, #F0F0F0 10%, #F0F0F0 90%, transparent 100%);
	}
	
	.bottom-row {
	  padding: 18rpx 28rpx;
	  margin-bottom: 0;
	  align-items: center;
	  display: flex;
	  justify-content: space-between;
	}
	
	.item-row:last-child {
	  align-items: center; /* 垂直居中对齐最后一行的内容 */
	}
	
	.column {
	  display: flex;
	  flex-direction: column;
	  flex: 1;
	}
	
	.left-column {
	  padding-right: 20rpx;
	  position: relative;
	  flex: 0 0 45%;
	  width: 45%;
	}
	
	.left-column::after {
	  content: '';
	  position: absolute;
	  right: 0;
	  top: 50%;
	  transform: translateY(-50%);
	  width: 2rpx;
	  height: 80%;
	  background: linear-gradient(180deg, transparent 0%, #E8E8E8 20%, #E8E8E8 80%, transparent 100%);
	}
	
	.right-column {
	  padding-left: 20rpx;
	  align-items: flex-end;
	  flex: 0 0 55%;
	  width: 55%;
	}
	
	.column .data-item {
	  margin-bottom: 24rpx;
	  display: block;
	  line-height: 1.8;
	  position: relative;
	  padding-left: 4rpx;
	}
	
	.column .data-item:last-child {
	  margin-bottom: 0;
	}
	
	.data-label {
	  color: #999999;
	  font-size: 26rpx;
	  font-weight: 400;
	  letter-spacing: 0.5rpx;
	}
	
	.data-value {
	  color: #2C2C2C;
	  font-weight: 700;
	  font-size: 24rpx;
	  letter-spacing: 0.5rpx;
	  margin-left: 4rpx;
	}
	
	.highlight {
	  color: #52c41a;
	}
	
	.time {
	  color: #B8B8B8;
	  font-size: 26rpx;
	  margin-top: 0;
	  font-weight: 400;
	  letter-spacing: 0.5rpx;
	  opacity: 0.85;
	  flex: 1;
	  text-align: right;
	  padding: 0 20rpx;
	}
	
	.btn-mini2, .btn-mini3 {
	  width: 140rpx;
	  height: 52rpx;
	  text-align: center;
	  border: 1px solid #e6e6e6;
	  border-radius: 10rpx;
	  display: inline-flex;
	  align-items: center;
	  justify-content: center;
	  font-size: 24rpx;
	  margin-left: auto; /* 将按钮推到右侧 */
	}
	
	.btn-mini2 {
	  background-color: #FC2D41;
	  color: white;
	  width: auto;
	  padding: 0 18rpx;
	  font-size: 28rpx;
	}
	
	.btn-mini3 {
	  background-color: #A5A5A5;
	  color: white;
	}
	
	/* 新增样式：使item-ordernum和item-url在同一行，item-ordernum靠左，item-url靠右 */
	.item-title {
	  display: flex;
	  justify-content: space-between;
	  align-items: center;
	  padding: 28rpx 28rpx 28rpx 0;
	  position: relative;
	}
	.shop-title{
		background: linear-gradient(135deg, #FFF5F7 0%, #FFFCFC 40%, #FFFFFF 100%);
		border-bottom: 2rpx solid rgba(248, 248, 248, 0.8);
	}
	.maidan-title{
		background: linear-gradient(135deg, #E9FEFF 0%, #F3FFFF 40%, #FFFFFF 100%);
		border-bottom: 2rpx solid rgba(248, 248, 248, 0.8);
	}
	

	
	.item-ordernum {
	  display: flex;
	  align-items: center;
	  font-size: 30rpx;
	  z-index: 1;
	  padding-left: 16rpx;
	}
	
	.item-ordernum image {
	  width: 64rpx;
	  height: 64rpx;
	  margin-right: 16rpx;
	  flex-shrink: 0;
	  border-radius: 12rpx;
	  box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.08);
	}
	.arrow image {
	  width: 32rpx;
	  height: 32rpx;
	}
	
	.ordernum-content {
	  display: flex;
	  align-items: center;
	  gap: 8rpx;
	}
	
	.shop-label {
	  color: #2C2C2C;
	  font-size: 32rpx;
	  font-weight: 600;
	  letter-spacing: 1rpx;
	}
	
	.ordernum-text {
	  color: #9E9E9E;
	  font-size: 28rpx;
	  font-weight: 400;
	  letter-spacing: 0.5rpx;
	}
	
	.item-url {
	  display: flex;
	  align-items: center;
	  z-index: 1;
	  padding: 8rpx 16rpx 8rpx 12rpx;
	  background: rgba(255, 255, 255, 0.6);
	  border-radius: 20rpx;
	  backdrop-filter: blur(4rpx);
	  transition: all 0.3s ease;
	}
	
	.item-url:active {
	  background: rgba(252, 45, 65, 0.08);
	  transform: scale(0.95);
	}
	
	.item-url .url-text {
	  font-size: 28rpx;
	  font-weight: 500;
	}
	
	.left-info {
	  display: flex;
	  align-items: center;
	  gap: 0;
	}
	
	.right-info {
	  display: flex;
	  align-items: center;
	  margin-left: auto;
	  gap: 16rpx;
	}
	
	.release-tag {
	  background: linear-gradient(135deg, #FFE5E9 0%, #FFF0F2 100%);
	  color: #FF5370;
	  font-size: 26rpx;
	  padding: 0;
	  border-radius: 12rpx;
	  margin-bottom: 0;
	  display: inline-flex;
	  align-items: center;
	  width: fit-content;
	  font-weight: 500;
	  box-shadow: 0 2rpx 8rpx rgba(255, 107, 138, 0.15);
	  border: 1rpx solid rgba(255, 107, 138, 0.2);
	  overflow: hidden;
	}
	
	.release-tag .tag-text {
	  background: linear-gradient(135deg, #FF6B8A 0%, #FF5370 100%);
	  color: #FFFFFF;
	  padding: 12rpx 18rpx;
	  font-weight: 600;
	  border-radius: 10rpx 0 0 10rpx;
	  box-shadow: inset 0 -2rpx 4rpx rgba(0, 0, 0, 0.1);
	  position: relative;
	  font-size: 26rpx;
	}
	
	.release-tag .tag-text::after {
	  content: '';
	  position: absolute;
	  right: 0;
	  top: 50%;
	  transform: translateY(-50%);
	  width: 12rpx;
	  height: 70%;
	  background: inherit;
	  border-radius: 0 6rpx 6rpx 0;
	}
	
	.release-tag .tag-value {
	  font-weight: 700;
	  font-size: 28rpx;
	  color: #FF5370;
	  padding: 12rpx 18rpx;
	  background: transparent;
	}
	
	.top-column {
	  display: flex;
	  flex-direction: column;
	  width: 50%;
	}
	
	.navigation {width: 100%;overflow: hidden;background: #FC2D41; border-bottom: 8rpx solid white;}
	.navcontent {display: flex;align-items: center;padding-left: 10px;height: 100rpx;}
	.header-location-top{position: relative;display: flex;justify-content: center;align-items: center;flex:1;}
	.header-back-but{position: absolute;left:0;display: flex;align-items: center;width: 40rpx;height: 45rpx;overflow: hidden;}
	.header-back-but image{width: 40rpx;height: 45rpx;} 
	.header-page-title{font-size: 36rpx;color: #fff;}
	
	/*返利 和 积分显示*/
	.ratio-label {
	  display: inline-flex;
	  align-items: center;
	  height: 52rpx;
	  border-radius: 10rpx;
	  border: 2rpx solid #FC2D41;
	  overflow: hidden;
	  box-shadow: 0 4rpx 12rpx rgba(252, 45, 65, 0.18);
	  transition: all 0.3s ease;
	}
	
	.ratio-label:active {
	  transform: scale(0.96);
	  box-shadow: 0 2rpx 8rpx rgba(252, 45, 65, 0.15);
	}
	
	.ratio-label .label {
	  background: linear-gradient(135deg, #FC2D41 0%, #FF4458 50%, #FF5370 100%);
	  color: #FFFFFF;
	  padding: 0 18rpx;
	  height: 52rpx;
	  line-height: 52rpx;
	  font-size: 24rpx;
	  white-space: nowrap;
	  font-weight: 600;
	  letter-spacing: 1rpx;
	  box-shadow: inset 0 -2rpx 4rpx rgba(0, 0, 0, 0.1);
	  border-top-right-radius: 18rpx;
	  border-bottom-right-radius: 18rpx;
	}
	
	.ratio-label .ratio-value {
	  color: #FC2D41;
	  padding: 0 18rpx;
	  font-size: 28rpx;
	  font-weight: 700;
	  letter-spacing: 0.5rpx;
	  background: linear-gradient(135deg, #FFE5E9 0%, #FFF5F7 100%);
	  height: 52rpx;
	  line-height: 52rpx;
	}
	
	/* 免单金额 */
	.popup-miandan-content{position: relative;}
	.popupMiandan-close{border: 2px #fff solid;width: 60rpx;height: 60rpx;border-radius: 50%;display: flex;align-items: center;justify-content: center;margin: 0 auto;}
	.popupMiandan-close image{width: 80%;height: 80%;}
	.popup-miandan-content .popup-miandan-price{width: 100%;position: absolute;top:25%;align-items: flex-end;justify-content: center;z-index: 1;}
	.popup-color-text{background: linear-gradient(180deg, #FF8D3F 0%, #F84A2C 100%);-webkit-background-clip: text;-webkit-text-fill-color: transparent;background-clip: text;
	text-fill-color: transparent;}
	.popup-miandan-content .popup-miandan-price .price-num-text{font-size: 50rpx;font-weight: 900;padding-left: 30rpx;}
	.popup-miandan-content .popup-miandan-price .right-text{font-size: 26rpx;font-weight: bold;margin-bottom: 25rpx;}
	.popup-leiji-miandan{width: 100%;position: absolute;top:43%;text-align: center;font-size: 30rpx;font-weight: bold;z-index: 1;}
	.popup-guishu{width: 100%;position: absolute;bottom: 10%;text-align: center;bottom: 17%;color: #FFDAA1;z-index: 1;}
	.popup-guishu .top-text{font-size: 40rpx;font-weight: bold;letter-spacing: 4rpx;color: #FFDAA1;}
	.popup-guishu .bottom-text{font-size: 20rpx;letter-spacing: 15rpx;color: #eac694;margin-top: 5rpx;}
	
	/* 领取按钮样式 */
	.popup-receive-btn {
		width: 200rpx;
		height: 70rpx;
		background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
		border: none;
		border-radius: 40rpx;
		color: #fff;
		font-size: 32rpx;
		font-weight: bold;
		box-shadow: 0 8rpx 20rpx rgba(255, 165, 0, 0.4);
		transition: all 0.3s ease;
		display: flex;
		align-items: center;
		justify-content: center;
		margin: 0 auto;
		position: relative;
		top: -10rpx;
	}
	
	.popup-receive-btn:active {
		transform: scale(0.95);
		box-shadow: 0 4rpx 10rpx rgba(255, 165, 0, 0.3);
	}
</style>