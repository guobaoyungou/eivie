<template>
	<view>
		<view class="page"
			:style="'background:'+info.bgcolor+';background-size:100%'"
			v-if="isload">
			<view class="slides">
				<image :src="info.fmpic" class="slides_img" mode="widthFix" />
			</view>
			<view class="countdown">
				<view class="xsqg flex-y-center" :style="{background:info.color2}">
						<text style="font-size: 24rpx;">抢购价：</text> 
						<text style="font-size: 38rpx;font-weight: 500;margin-right: 5rpx;">￥{{info.price}}</text>
				</view>
				<view class="djs flex-y-center" :style="{background:info.color1}">
					
						<!-- <view></view> -->
						<view  v-if=" time.days >0 || time.hours > 0||time.min >0 || time.sec >0 " class="time flex-y-center flex" >
							<view style="font-size: 24rpx;">距结束：</view>
						
							<text class="timetext" :style="{color:info.color1?info.color1:t('color1')}" v-if="time.days > 0" >{{time.days}}</text><text v-if="time.days > 0">天</text>
							
							<text class="timetext" :style="{color:info.color1?info.color1:t('color1')}">{{time.hours}}</text>:
							<text class="timetext" :style="{color:info.color1?info.color1:t('color1')}">{{time.min}}</text>:
							<text class="timetext" :style="{color:info.color1?info.color1:t('color1')}">{{time.sec}}</text>
					
					</view>
					
					<view v-else class="activity_end">活动已结束</view>
				</view>
			</view>
			<view class="detail">
				<view class="detail_title">{{info.name}}</view>
				<view class="detail_data" :style="{color:info.color1?info.color1:t('color2')}">
					{{info.viewnum}}浏览 <text>·</text> {{info.buy_count}}购买 <text>·</text> {{info.join_count}}参与 <text>·</text> {{info.share_count}}分享
				</view>
			</view>
			<view class="step">
				<view class="step_title" >
					参入方式
				</view>
				<view class="flex-y-center">
					<view class="step_item flex1">
						<view class="step_icon">
							<image :style="'transform: translateZ(0);filter: drop-shadow(' + info.color1 + ' -90rpx 0rpx 0rpx);'" :src="pre_url+'/static/img/hbtk_i1.svg'"></image>
						</view>
						<view class="step_text" :style="{color:info.color1?info.color1:t('color1')}">1.点击立即抢购</view>
					</view>
					<view class="step_tag">
						<image :style="'transform: translateZ(0);filter: drop-shadow(' +info.color1 + ' -30rpx 0rpx 0rpx);'" :src="pre_url+'/static/img/hbtk_tag.svg'"></image>
					</view>
					<view class="step_item flex1">
						<view class="step_icon">
							<image :style="'transform: translateZ(0);filter: drop-shadow(' + info.color1 + ' -90rpx 0rpx 0rpx);'" :src="pre_url+'/static/img/hbtk_i2.svg'"></image>
						</view>
						<view class="step_text":style="{color:info.color1?info.color1:t('color1')}">2.好友扫码抢购</view>
					</view>
					<view class="step_tag">
						<image :style="'transform: translateZ(0);filter: drop-shadow(' + info.color1 + ' -30rpx 0rpx 0rpx);'" :src="pre_url+'/static/img/hbtk_tag.svg'"></image>
					</view>
					<view class="step_item flex1">
						<view class="step_icon">
							<image :style="'transform: translateZ(0);filter: drop-shadow(' + info.color1 + ' -90rpx 0rpx 0rpx);'" :src="pre_url+'/static/img/hbtk_i3.svg'"></image>
						</view>
						<view class="step_text" :style="{color:info.color1?info.color1:t('color1')}">3.到店进行核销</view>
					</view>
				</view>
			</view>
			<!-- <view class="liucheng">
				<text class="number">1</text>
				<view class="liucheng_module">
					<view>点击立即抢购</view>
					<view>享受优惠内容</view>
				</view>

				<text class="number">2</text>
				<view class="liucheng_module">
					<view>邀请推荐好友</view>
					<view>一起参与抢购</view>
				</view>

				<text class="number">3</text>
				<view class="liucheng_module">
					<view>到店进行核销</view>
					<view>等待商家发货</view>
				</view>
			</view> -->
			<view class="join moudle">
				<view class="join_number">
					<text class="f1">已参与<text class="red">{{info.join_count}}</text>人</text>
					<!-- <text class="f1"><text class="red">{{info.viewnum}}</text>次浏览</text> -->
				</view>
				<view class="join_list flex-xy-center">
					<view class="flex" style="padding-left: 20rpx">
						<!-- <view class="join_user" v-for="(item,index) in join_list" :key="index"> -->
							<image v-for="(item,index) in join_list" :key="index" :src="item.headimg" class="join_head" />
							<!-- <view class="nickname">{{item.nickname}}</view> -->
						<!-- </view> -->
					</view>
				</view>
			</view>
			<view class="buy moudle" v-if="info.show_buylog !=2">
				<view class="buy_number moudle_title">
					已有 <text class="red">{{info.buy_count}}</text>人购买
				</view>

				<swiper class="buy_swiper" circular vertical autoplay="true" :display-multiple-items='info.buy_count < 2?info.buy_count:2'
					interval="3000" duration="500">
					<block v-for="(item,index) in buy_list" :key="index">
						<swiper-item style="height: 95rpx;">
							<view class="buy_list">
								<view class="flex-y-center " style="width: 200rpx;overflow: hidden;">
									<image :src="item.headimg" class="img" />
									<text v-if="!info.show_buylog">{{item.nickname}}</text>
								</view>
								<text >支付{{item.price}}元</text>
								<text >{{item.createtime}}</text>
							</view>
						</swiper-item>
					</block>

				</swiper>

				<!-- <view class="buy_list"  v-for="(item,index) in buy_list" :key="index">
					<view class="flex-y-center">
						<image :src="item.headimg" class="img" />
						{{item.nickname}}
					</view>
					<text>支付{{item.price}}元</text>
					<text>{{item.createtime}}</text>
				</view>	 -->
			</view>
			<view class="desc moudle" v-if="info.content">
				<view class="moudle_title">活动介绍</view>
				<rich-text style="padding: 0 20rpx;" :nodes="info.content"></rich-text>
			</view>
			<view class="desc moudle" v-if="info.guize">
				<view class="moudle_title">活动规则</view>
				<rich-text style="padding: 0 20rpx;" :nodes="info.guize"></rich-text>
			</view>
			<view class="ranking moudle" v-if="info.show_ranking">
				<view class="moudle_title">邀请排行榜</view>
				<view class="ranking_list" v-for="(item,index) in yq_list" :key="index">
					<view class="flex-y-center">
						<text class="ranking_number">{{index +1}}</text>
						<image :src="item.headimg" class="img" />
						{{item.nickname}}
					</view>
					<text>邀请{{item.yqnum}}人</text>
				</view>
			</view>
			<view class="module_btn">
				<view class="btn">
					<view class="btn_left btn_bar" :style="{background:info.color2}" @click="showPoster">专属海报</view>
					<view class="btn_right btn_bar" :style="{background:info.color1}" @click="topay" v-if="time.days >0 || time.hours > 0||time.min >0 || time.sec >0  ">支付抢购</view>
					<view class="btn_right btn_bar" style="background: #eee;color: #333;"  v-else>活动已结束</view>
				</view>
			</view>
		</view>
		<view class="posterDialog" v-if="showposter">
			<view class="main">
				<view class="close" @tap="posterDialogClose">
					<image class="img" :src="pre_url+'/static/img/close.png'" />
				</view>
				<view class="content">
					<image class="img" :src="posterpic" mode="widthFix" @tap="previewImage" :data-url="posterpic">
					</image>
				</view>
			</view>
		</view>
		<view v-if="musicurl"
		    class="floating-button"
		    :class="{ 'playing': isPlaying }"
		    style="top: 260rpx;right: 10rpx;"
		    @click="togglePlayPause"
		  >
		    <!-- <i :class="isPlaying ? 'pause-icon' : 'play-icon'"></i> -->
			<image v-if="isPlaying" :src="pre_url+'/static/img/music.png'" style="width: 100%;height: 100%;" class="img" />
			<image v-if="!isPlaying" :src="pre_url+'/static/img/music_pause.png'" style="width: 100%;height: 100%;" class="img" />
		  </view>
		<button class="covermy" @tap="toHome" data-url="" style="top: 80rpx;" data-opentype="reLaunch">首页</button>
		<button class="covermy" @tap="goto" data-url="orderlist">我的记录</button>
		<loading v-if="loading"></loading>
		<wxxieyi></wxxieyi>
	</view>

</template>

<script>
	var app = getApp();
	export default {
		data() {
			return {
				isload: false,
				loading: false,
				showposter: false,
				info: {
					fmpic: '',
					content: 'dddddddd',
					bgcolor: '#FFF7DE',
					bgpic: 'https://image.wxx1.com/upload/202204/13_1645185451.jpg',
					guize:'',
					content:''
				},
				opt: {
					id: 0,
					pid: 0
				},
				join_list: [],
				buy_list: [],
				yq_list: [],
				posterpic: '',
				time: {
					days: 0,
					hours: 0,
					min: 0,
					sec: 1
				},
				latitude: '',
				longitude: '',
				pre_url:app.globalData.pre_url,
				nowtime:0,
				innerAudioContext: '',
				 isPlaying: false,
				  musicurl:''
			}
		},
		onShareAppMessage: function() {
			var that = this;
			var title = that.info.name;
			if (that.info.sharetitle) title = that.info.sharetitle;
			var sharepic = that.info.sharepic ? that.info.sharepic : '';
			var sharelink = that.info.sharelink ? that.info.sharelink : '';
			var sharedesc = that.info.sharedesc ? that.info.sharedesc : '';
			return this._sharewx({
				title: title,
				desc: sharedesc,
				link: sharelink,
				pic: sharepic,
				callback: function() {
					that.sharecallback();
				}
			});
		},
		// onShareTimeline: function() {
		// 	var that = this;
		// 	var title = that.info.name;
		// 	if (that.info.sharetitle) title = that.info.sharetitle;
		// 	var sharepic = that.info.sharepic ? that.info.sharepic : '';
		// 	var sharelink = that.info.sharelink ? that.info.sharelink : '';
		// 	var sharedesc = that.info.sharedesc ? that.info.sharedesc : '';
		// 	var sharewxdata = this._sharewx({
		// 		title: title,
		// 		desc: sharedesc,
		// 		link: sharelink,
		// 		pic: sharepic,
		// 		callback: function() {
		// 			that.sharecallback();
		// 		}
		// 	});
		// 	var query = (sharewxdata.path).split('?')[1] + '&seetype=circle';
		// 	console.log(sharewxdata)
		// 	console.log(query)
		// 	return {
		// 		title: sharewxdata.title,
		// 		imageUrl: sharewxdata.imageUrl,
		// 		query: query
		// 	}
		// },
		onLoad(opt) {
			if (opt) {
				this.opt = app.getopts(opt);
			}
			this.innerAudioContext = uni.createInnerAudioContext();
			this.getInfo();
		},
		onUnload() {
		    // 用户卸载当前页面时的处理逻辑
		    console.log('用户卸载了页面');
			this.innerAudioContext.stop();
		  },
		onShow() {
			this.getdata();
			if(app.globalData.platform=='wx'&& app.globalData.hide_home_button==1){
					uni.hideHomeButton();
			}
		},
		methods: {
			getInfo() {
				var that = this;
				that.loading = true;
				app.post('ApiHbtkActivity/getdetail', {
					id: that.opt.id,
					pid: that.opt.pid
				}, function(data) {
					that.loading = false;
					that.info = data.info;
					that.join_list = data.join_list;
					that.buy_list = data.buy_list;
					that.yq_list = data.yq_list
					uni.setNavigationBarTitle({title:that.info.name});
					setInterval(function() {
						that.intervalTime(data.info.endtime);
					}, 1000);
					app.getLocation(function(res) {
						var latitude = res.latitude;
						var longitude = res.longitude;
						that.latitude = latitude;
						that.longitude = longitude;
					});
					
					var musicurl = data.info.musicurl || '';
					that.musicurl = musicurl;
					
					var sharetitle = that.info.name;
					if (that.info.sharetitle) sharetitle = that.info.sharetitle;
					var sharepic = that.info.sharepic ? that.info.sharepic : that.info.fmpic;
					var sharelink = that.info.sharelink ? that.info.sharelink : '';
					that.loaded({title:sharetitle,link:sharelink,pic:sharepic});
				});
			},
			showPoster: function() {
				var that = this;

				that.sharetypevisible = false;
				app.showLoading('生成海报中');
				app.post('ApiHbtkActivity/getposter', {
					proid: that.info.id
				}, function(data) {
					app.showLoading(false);
					if (data.status == 0) {
						app.alert(data.msg);
					} else {
						that.showposter = true;
						that.posterpic = data.poster;
					}
				});
			},
			sharecallback: function() {
				var that = this;
				app.post("ApiHbtkActivity/share", {
					hid: that.info.id
				}, function(res) {
					if (res.status == 1) {
						setTimeout(function() {
							that.getdata();
						}, 1000);
					} else if (res.status == 0) { //dialog(res.msg);
					}
				});
			},
			topay() {
				var that = this
				//购买
				that.loading = true;
				app.post('ApiHbtkActivity/createOrder', {
					id: that.info.id,
					longitude: that.longitude,
					latitude: that.latitude,
					pid: that.opt.pid
				}, function(res) {
					that.loading = false;
					if (res.status == 0) {
						app.error(res.msg);
						return;
					}
					if (res.payorderid == 0) {
						app.success('参加成功');
					} else {
						app.goto('/pagesExt/pay/pay?id=' + res.payorderid);
					}
				});
			},
			posterDialogClose: function() {
				this.showposter = false;
			},
			// 将时间戳转化为时分秒的格式，一般用作倒计时
			intervalTime: function(endTime) {

				// console.log(222)
				// var timestamp=new Date().getTime(); //计算当前时间戳
				var date1 = (Date.parse(new Date())) / 1000; //计算当前时间戳 (毫秒级)
				var date2 = endTime; //结束时间
				// var date3 = date2.getTime() - date1.getTime(); //时间差的毫秒数
				var date3 = (date2 - date1) * 1000; //时间差的毫秒数
				//计算出相差天数
				var days = Math.floor(date3 / (24 * 3600 * 1000));
				//计算出小时数

				var leave1 = date3 % (24 * 3600 * 1000); //计算天数后剩余的毫秒数
				var hours = Math.floor(leave1 / (3600 * 1000));
				//计算相差分钟数
				var leave2 = leave1 % (3600 * 1000); //计算小时数后剩余的毫秒数
				var minutes = Math.floor(leave2 / (60 * 1000));

				//计算相差秒数

				var leave3 = leave2 % (60 * 1000); //计算分钟数后剩余的毫秒数
				var seconds = Math.round(leave3 / 1000);
				// console.log(days + "天 " + hours + "小时 ")
				var sun = hours;
				var min = minutes;
				var sec = seconds;
				this.time.days = days <= 0 ? 0 : days;
				this.time.hours = hours < 10 ? '0' + hours : hours;
				this.time.min = min < 10 ? '0' + min : min;
				this.time.sec = sec < 10 ? '0' + sec : sec;
			},
			 togglePlayPause() {
				 var isPlaying = this.isPlaying;
			      this.isPlaying = !this.isPlaying;
			      this.$emit('toggle-play', this.isPlaying);
				  var that = this;
				  var musicurl = that.musicurl;
				  if(isPlaying){
					console.log('暂停');
					that.innerAudioContext.pause()
				  }else{
					  console.log('开始播放');
					  // that.innerAudioContext.autoplay = true;
					  that.innerAudioContext.loop = true;
					  that.innerAudioContext.src = musicurl;
					  that.innerAudioContext.play();
				  }
			    },
				toHome(){
					var that =this;
					if(that.info.bid > 0){
						var burl = 'pagesExt/business/index?id='+that.info.bid;
						app.goto(burl,'reLaunch');
					}else{
						var indexurl = '/pages/index/index';
						app.goto(indexurl,'reLaunch');
					}
				}
		}
	}
</script>

<style>
	.page {
		height: 100%;
		padding-bottom: 200rpx;
	}

	.header {
		z-index: 10;
		position: fixed;
		height: 80rpx;
		width: 100%;
		padding: 0 30rpx;
		box-sizing: border-box;
		font-size: 26rpx;
		color: #fff;
		background: rgba(0, 0, 0, 0.6);
	}

	.header_item {
		padding: 0 15rpx;
	}

	.header_item image {
		width: 35rpx;
		height: 35rpx;
		margin-right: 10rpx;
	}

	.header_btn {
		width: 150rpx;
		text-align: center;
		height: 60rpx;
		font-size: 26rpx;
		color: #333;
		background-color: #FDDF1B;
		border-radius: 10rpx;
	}

	.slides {
		height: auto;
	
	}

	.slides_img {
		width: 100%;
		margin-bottom: -10rpx;
	}

	.countdown {
		display: flex;
		justify-content: space-between;
		height: 90rpx;
		padding: 0rpx 20rpx;
		
	}

	.countdown .xsqg {
		padding: 10rpx 20rpx;
		color: #fff;
		background-color: #fe4440;
		flex: 1.5;
		border-radius: 36rpx 0 0 0rpx;
	}

	.countdown .xsqg view {
		padding: 0 20rpx;
	}

	.countdown .djs {
		padding: 10rpx 20rpx;
		color: #fff;
		background: #ffed7d;
		flex: 2;
		border-radius: 0 36rpx 0rpx 0;
	}
	.activity_end{
		line-height: 80rpx;
		font-weight: 700;
		text-align: center;
	}
	.countdown .djs .time .timetext {
		background: #fff;
		border-radius: 8rpx;
		padding: 4rpx 10rpx;
		font-size: 24rpx;
		font-weight: bolder;
		margin: 0 8rpx;
	}

	.detail {
		margin: 0 20rpx;
		background-color: #fff;
		border-radius: 0 0 32rpx 32rpx;
		padding: 30rpx;
		
	}
	.detail_title{
		color: #000;
		font-weight: bold;
		text-align: center;
		font-size: 38rpx;
	}
	.detail_data{
		color: #999;
		font-size: 24rpx;
		text-align: center;
		margin-top: 30rpx;
	}
	.detail_data text{
		margin: 0 20rpx;
	}

	.liucheng {
		margin: 0 20rpx;
		padding: 20rpx;
		display: flex;
		align-items: center;
		background: #fff;
		margin-top: 20rpx;
		border-radius: 10rpx;
	}

	.liucheng .number {
		background: #f8a024;
		color: #fff;
		font-size: 24rpx;
		width: 30rpx;
		height: 30rpx;
		line-height: 30rpx;
		text-align: center;
		display: block;
		border-radius: 50%;
		margin-right: 10rpx;
	}

	.liucheng_module {
		flex: 1;
	}
	

	.step{
		padding: 30rpx;
		margin: 20rpx;
		background: #fff;
		border-radius: 32rpx;
	}
	.step_title{
		text-align: center;
		height: 60rpx;
		line-height: 60rpx;
		font-size: 34rpx;
	}
	.step_item{
		padding: 20rpx 0;
	}
	.step_icon{
		width: 90rpx;
		height: 90rpx;
		overflow: hidden;
		margin: 0 auto;
	}
	.step_text{
		font-size: 22rpx;
		text-align: center;
		margin-top: 15rpx;
	}
	.step_icon image{
		width: 90rpx;
		height: 90rpx;
		transform: translateX(90rpx);
	}
	.step_tag{
		width: 30rpx;
		height: 30rpx;
		margin-top: -40rpx;
		overflow: hidden;
	}
	.step_tag image{
		width: 30rpx;
		height: 30rpx;
		transform: translateX(30rpx);
	}
	
	

	.join {}

	.join .join_number {
		text-align: center;
		height: 80rpx;
		line-height: 80rpx;
		font-size: 34rpx;
	}

	.join_list {
		padding: 10rpx;
		display: flex;
		flex-wrap: wrap;
		overflow: hidden;

	}

	.join_list .join_user {
		padding: 14rpx;
	}

	.join_list .img {
		width: 80rpx;
		height: 80rpx;
		border-radius: 50%;
	}

	.join_list .join_user .nickname {
		width: 80rpx;
		overflow: hidden;
		text-align: center;
	}
	
	.join_head{
		height: 80rpx;
		width: 80rpx;
		border-radius: 100rpx;
		margin-left: -20rpx;
		border: 4rpx solid #fff;
		display: block;
	}

	.buy_swiper {
		position: relative;
		height: 180rpx;
	}

	.buy .buy_list {
		display: flex;
		justify-content: space-between
	}

	.buy .buy_list .img {
		width: 70rpx;
		height: 70rpx;
		border-radius: 50%;
		align-items: center;
		margin-right: 10rpx;
	}

	.buy .buy_list text {
		line-height: 80rpx;
		text-align: center;
	}

	.moudle {
		margin: 0 20rpx;
		padding: 20rpx;
		background: #fff;
		margin-top: 20rpx;
		border-radius: 32rpx;
	}

	.moudle .moudle_title {
		text-align: center;
		height: 80rpx;
		line-height: 80rpx;
		font-size: 34rpx;
	}

	.ranking .ranking_list {
		display: flex;
		justify-content: space-between;
		padding: 15rpx 0;
	}

	.ranking .ranking_list text {
		line-height: 80rpx;
		text-align: center;
	}

	.ranking .ranking_list .img {
		width: 80rpx;
		height: 80rpx;
		border-radius: 50%;
		align-items: center;
		margin-right: 10rpx;
	}

	.ranking_number {
		font-size: 34rpx;
		padding: 0 20rpx;
	}

	.module_btn {
		position: fixed;
		bottom: 10rpx;
		left: 0;
		width: 100%;
		padding: 0 20rpx;
		height: 90rpx;
	}

	.btn {
		justify-content: center;
		width: 100%;
		border-radius: 50rpx;
		overflow: hidden;
		display: flex;
	}

	.btn_bar {
		flex: 1;
		color: #fff;
		font-size: 28rpx;
		align-items: center;
		text-align: center;
		line-height: 90rpx;
		font-size: 36rpx;
	}

	.btn_left {
		background: #fe4440;
	}

	.btn_right {
		background: #ffed7d;
		color: #fff;
	}

	.red {
		color: red;
		font-weight: 700;
		padding: 0 5rpx;
	}

	.f1 {
		flex: 1;
	}
	.posterDialog .main {
	    width: 80%;
	    margin: 31px 10% 15px 10%;
	    background: #fff;
	    position: relative;
	    border-radius: 10px;
	    top: 11%;
	}
	.covermy{position:absolute;z-index:99999;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;z-index:9999;top:160rpx;right:0;color:#fff;background-color:rgba(17,17,17,0.3);width:140rpx;height:60rpx;font-size:26rpx;border-radius:30rpx 0px 0px 30rpx;}
	
	
	.floating-button {
	  position: fixed;
	  width: 80rpx;
	  height: 80rpx;
	  border-radius: 50%;
	  background-color: rgba(17,17,17,0.3);
	  display: flex;
	  justify-content: center;
	  align-items: center;
	  box-shadow: 0 2px 10px rgba(0,0,0,0.2);
	  z-index: 1000;
	  touch-action: none;
	}
	
	.floating-button.playing {
	  background-color: rgba(17,17,17,0.3);
	}
	
	.play-icon::before {
	  content: "\25B6";
	  color: white;
	  font-size: 24px;
	}
	
	.pause-icon::before {
	  content: "\275A\275A";
	  color: white;
	  font-size: 24px;
	}
</style>
