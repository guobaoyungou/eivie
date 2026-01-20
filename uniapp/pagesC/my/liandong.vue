<template>
	<view class="container">
	  <block v-if="isload">
			<view class="user-info-view flex-col">
				<view class="user-top-view flex flex-y-center">
					<view class="info-view flex flex-y-center">
						<view class="avatar-view">
							<image :src="userinfo.headimg"></image>
						</view>
						<view style="padding-left: 20rpx;flex: 1;">
							<view class="user-name flex flex-y-center">
								<view>{{userinfo.nickname}}</view>
								<view class="tuanzhang-tag flex flex-y-center">
									<image :src="userinfo.levelicon" class="tuanzhang-icon" />
									{{userinfo.levelname}}
								</view>
							</view>
							<view class="reference-name flex flex-y-center"> 
								<view class='reference-options'>{{t('会员')}}ID：{{userinfo.id}}</view>
								<view class='reference-options' v-if="!isNull(userinfo.yqcode)">邀请码：{{userinfo.yqcode}}</view>
								<view class='reference-options'>推荐人：{{userinfo.pid_nickname}}</view>
							</view>
						</view>
					</view>
				</view>
				<view class="rewards-view flex flex-y-center rewards-view-class1">
					<view class="flex-col flex-y-center" @tap="goto" data-url="/activity/commission/withdraw">
						<view class="rewards-top-title">去提现</view>
					</view>
					<view class="flex-col flex-y-center" @tap="goto" data-url="/activity/commission/commissionlog">
						<view class="rewards-top-title">{{t('佣金')}}明细</view>
					</view>
					<view class="flex-col flex-y-center" v-if="userinfo.can_agent>0" @tap="goto" data-url="/activity/commission/poster">
						<view class="rewards-top-title">邀请好友</view>
					</view>
					<view class="flex-col flex-y-center" v-if="userinfo.can_agent<=0">
						<view class="rewards-top-title">无推广权限</view>
					</view>
				</view>
				<view class="rewards-view flex flex-y-center rewards-view-class2">
					<view class="flex flex-y-center" style="justify-content: space-between;flex: 1;">
						<view class="flex-col flex-y-center rewards-options">
							<view class="rewards-text">累计{{t('佣金')}}(元)</view>
							<view class="rewards-price">{{userinfo.totalcommission}}</view>
						</view>
						<view class="flex-col flex-y-center rewards-options">
							<view class="rewards-text">{{t('一级')}}{{t('佣金')}}(元)</view>
							<view class="rewards-price">{{userinfo.parent1}}</view>
						</view>
						<view class="flex-col flex-y-center rewards-options">
							<view class="rewards-text">{{t('等级价格极差分销')}}{{t('佣金')}}(元)</view>
							<view class="rewards-price">{{userinfo.parent_jicha}}</view>
						</view>
					</view>
					<view class="flex flex-y-center" style="justify-content: space-between;flex: 1;margin-top: 30rpx;">
						<view class="flex-col flex-y-center rewards-options">
							<view class="rewards-text">剩余{{t('佣金')}}(元)</view>
							<view class="rewards-price">{{userinfo.commission}}</view>
						</view>
						<view class="flex-col flex-y-center rewards-options">
							<view class="rewards-text">{{t('一级')}}{{t('团队')}}(个)</view>
							<view class="rewards-price">{{userinfo.tj_num}}</view>
						</view>
						<view class="flex-col flex-y-center rewards-options">
							<view class="rewards-text">裂变{{t('团队')}}(个)</view>
							<view class="rewards-price">{{userinfo.team_num}}</view>
						</view>
					</view>
				</view>
			</view>
			<!--  -->
			<view class="user-info-view flex-col">
				<view class="user-top-view flex flex-y-center">
					<view class="info-view flex flex-y-center" style="justify-content: space-between;">
						<view @click='changeTab(0)' :class="[st == 0 ? 'tab-options-active' : '' , 'tab-options']">{{t('一级')}}{{t('团队')}}</view>
						<view @click='changeTab(1)' :class="[st == 1 ? 'tab-options-active' : '' , 'tab-options']">裂变{{t('团队')}}</view>
					</view>
				</view>
				<block>
				  <view class="activity-list-view flex-col" v-if="datalist.length > 0">
						<block v-for="(item,index) in datalist">
						  <view class="flex active-options">
							<view class="active-image" >
							  <image :src="item.headimg"></image>
							</view>
							<view class="active-info-list flex-col" >
							  <view class="active-name flex flex-y-center">
								<view class="name-text">{{item.nickname}}</view>
								<view class="tag-view">{{item.path_desc}}</view>
							  </view>
									<view class="huiyuan-info flex">
										<view class='info-options'>会员ID：{{item.id}}</view>
										<view class='info-options' v-if="!isNull(item.yqcode)">会员邀请码：{{item.yqcode}}</view>
									</view>
									<view class="list-view flex flex-x-center">
										<view class="list-view-options flex-col flex-x-center flex-y-center">
											<view class="options-title">会员等级</view>
											<view class="options-num-text">{{item.levelname}}</view>
										</view>
										<view class="list-view-options flex-col flex-x-center flex-y-center">
											<view class="options-title">{{t('一级')}}{{t('团队')}}(人)</view>
											<view class="options-num-text">{{item.downcount}}</view>
										</view>
										<view class="list-view-options flex-col flex-x-center flex-y-center" style="margin-right: 13rpx;">
											<view class="options-title">累计{{t('佣金')}}(元)</view>
											<view class="options-num-text">{{item.totalcommission}}</view>
										</view>
									</view>
							</view>
						  </view>
						</block>
				  </view>
				  <nodata v-if="nodata"></nodata>
				  <nomore v-if="nomore"></nomore>
				</block>
			</view>
		</block>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
	</view>
</template>

<script>
	var app = getApp();
	export default{
		data(){
			return{
				opt:{},
				loading:false,
				isload: false,
				menuindex:-1,
				nodata: false,
				nomore: false,
				
				pre_url: app.globalData.pre_url,
				st: 0,
				userinfo:{},
				datalist: [],
				pagenum: 1,
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
				this.pagenum = this.pagenum + 1
				this.getdata(true);
			}
		},
		onShow(){
		},
		methods:{
			getdata: function (loadmore) {
				if(!loadmore){
					this.pagenum = 1;
					this.datalist = [];
					this.is_end = 0;
				}
				var that = this;
				var st = that.st;
				var pagenum = that.pagenum;
				var keyword = that.keyword;
				that.loading = true;
				that.nodata = false;
				that.nomore = false;
				var mid = that.mid;
				var date_start = that.startDate;
				var date_end = that.endDate;
				var checkLevelid = that.checkLevelid;
				var month_search = that.month_value;	
				var first_mid = that.first_mid;	
				app.post('ApiMy/userdata', {st: st,pagenum: pagenum}, function (res) {
					that.loading = false;
					var data = res.datalist;
					if (pagenum == 1) {
						that.userinfo = res.userinfo;
						that.datalist = data;
						if (data.length == 0) {
							that.nodata = true;
						}
						uni.setNavigationBarTitle({
							title: '数据统计'
						});
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
			changeTab(e){
				this.st = e;
				uni.pageScrollTo({
				  scrollTop: 0,
				  duration: 0
				});
				this.getdata();
			},
		}
	}
</script>

<style>
	/* #ifdef H5 */
	/deep/ .uni-progress-bar{border-radius: 12px;overflow: hidden;}
	/* #endif */
	.container {width: 94%;margin: 0 auto;}
	.user-info-view{background: #fff;margin-top: 30rpx;border-radius: 36rpx;padding: 30rpx;position: relative;overflow: hidden;}
	.user-info-view .user-top-view{width: 100%;border-bottom: 1px #ebeaea solid;justify-content: space-between;padding-bottom: 15rpx;}
	.user-info-view .user-top-view .info-view{flex: 1;}
	.user-info-view .user-top-view .info-view .avatar-view{width: 90rpx;height: 90rpx;border-radius: 50%;overflow: hidden;}
	.user-info-view .user-top-view .info-view .avatar-view image{width: 100%;height: 100%;}
	.user-info-view .user-top-view .info-view .user-name{font-size: 32rpx;color: #000;align-items: center;}
	.user-info-view .user-top-view .info-view .user-name .tuanzhang-tag{font-size: 24rpx;color: #fff001;background: #fb484c;padding: 3rpx 15rpx;margin-left: 20rpx;
	border-radius: 20rpx;}
	.user-top-view .info-view .user-name .tuanzhang-tag .tuanzhang-icon{width: 18rpx;height: 21rpx;margin-right: 5rpx;}
	.user-info-view .user-top-view .info-view .reference-name{font-size: 24rpx;color: #959494;margin-top: 15rpx;justify-content: space-between;flex: 1;}
	.user-top-view .info-view .reference-name .reference-options{flex: 1;white-space: nowrap;}

	.user-info-view .rewards-view{width: 100%;justify-content: space-between;}
	.rewards-view-class1{padding: 15rpx 0rpx;border-bottom: 1px #ebeaea solid;}
	.rewards-view-class2{padding: 30rpx 0rpx 0rpx;padding-bottom: 20rpx;flex-wrap: wrap;}
	.user-info-view .rewards-view .rewards-options{flex: 1;}
	.rewards-view .rewards-options .rewards-text{font-size: 26rpx;white-space: nowrap;color: #959494;font-weight: bold;}
	.rewards-view .rewards-options .rewards-price{margin-top: 15rpx;font-size: 26rpx;font-weight: bold;}
	.rewards-view .rewards-top-title{background: #333333;font-size: 26rpx;color: #fff;text-align: center;border-radius: 34rpx;padding: 5rpx 20rpx;
	width: 100%;}
	/* 进行中&已成团 */
	.tab-options{width: 43%;text-align: center;padding: 12rpx;border-radius: 30rpx;background-color: #e3e3e3;color: #484848;font-size: 26rpx;}
	.tab-options-active{background: #fb484c;color: #fff;}
	.activity-list-view{width: 100%;margin-top: 10rpx;}
	.activity-list-view .active-options{align-items: center;margin-top: 20rpx;border-radius: 14rpx;background: #e3e3e3;}
	.activity-list-view .active-options .active-image{width: 200rpx;height: 200rpx;border-radius: 14rpx;overflow: hidden;padding: 10rpx;}
	.activity-list-view .active-options .active-image image{width: 100%;height: 100%;}
	.activity-list-view .active-options .active-info-list{margin-left: 20rpx;flex: 1;padding: 15rpx 0rpx;}
	.active-info-list .active-name{}
	.active-info-list .active-name .name-text{font-size: 28rpx;color: #000;}
	.active-info-list .active-name .tag-view{font-size: 24rpx;color: #fff;background-color: #333333;border-radius: 18rpx;padding: 3rpx 6rpx;width: 126rpx;
	text-align: center;white-space: nowrap;margin-left: 10rpx;}
	.active-info-list .huiyuan-info{background: #fa484b;color: #eeeeee;font-size: 20rpx;height: 32rpx;margin: 15rpx 0rpx;justify-content: flex-start;
	align-items: center;}
	.active-info-list .huiyuan-info .info-options{width: 45%;text-align: center;}
	.active-info-list .list-view{justify-content: space-between;}
	.active-info-list .list-view .list-view-options{width: 30%;background: #fff;border-radius: 15rpx;padding: 10rpx 0rpx;}
	.active-info-list .list-view .list-view-options .options-title{font-size: 20rpx;color: #717171;}
	.active-info-list .list-view .list-view-options .options-num-text{font-size: 22rpx;color: #333;font-weight: bold;margin-top: 5rpx;}
</style>