<template>
	<view class="page" :style="{ background:bgcolor==''?'#FA5C01':bgcolor}">
		<view class="page-box" :style="{backgroundImage:'url('+ bgpic +')'}">
			<view class="head-box">
				<view class="logo-box">
					<image :src="channels.headimg" class="logo" :alt="channels.nickname">
				</view>
				<view class="head-title">
					<view class="title" :style="{color:fontcolor}">{{channels.nickname}}</view>
				</view>
				<view class="head-desc" :style="{color:fontcolor}">{{setData.desc}}</view>
				<view class="bgcolor"></view>
				<view class="button-channels" @tap="openChannels()" :style="{backgroundImage:'url('+pre_url+'/static/img/channels_live/button_sph.png)'}"></view>
			</view>
			<view class="content-box">
				<view class="live-box empty" v-if="isNull(liveList)">暂无数据</view>
				<view class="live-box" v-else v-for="(item,index) in liveList" :key="index">
					<view class="live-title">{{item.content}}</view>
					<view class="live-content flex-bt">
						<view class="live-time" :style="'background: url('+pre_url+'/static/img/channels_live/time_icon.png) no-repeat;background-size:32rpx;background-position: left;'">{{dateFormat(item.live_start_time,'m月d日 H:i') }}开播</view>
						<view class="live-reservation opacity" :style="'background: url('+pre_url+'/static/img/channels_live/bg_bottom.png) no-repeat 100%;background-size: 100%;'" @tap="reserveChannelsLive(item.noticeId,item.id)" v-if="item.reserve_status == 1">已预约</view>
						<view class="live-reservation":style="'background: url('+pre_url+'/static/img/channels_live/tmbg_bottom.png) no-repeat 100%;color: #E83612;background-size: 100%;'" v-else-if="item.live_status == 3"  @click="gotoLive" data-id="">直播中</view>
						<view class="live-reservation" :style="'background: url('+pre_url+'/static/img/channels_live/bg_bottom.png) no-repeat 100%;background-size: 100%;'" v-else @tap="reserveChannelsLive(item.noticeId,item.id)">预约直播</view>
					</view>
				</view>
			</view>
			<view class="rule-title flex-y-center"  @tap="changerule">规则</view>
			<view id="mask-rule" v-if="showrule">
				<view class="box-rule">
					<view class="h2">活动规则说明</view>
					<view id="close-rule" :style="'background-image:url('+pre_url+'/static/img/dzp/close.png);background-size:100%'" @tap="changerule"></view>
					<view class="con">
						<view class="text">
							<text decode="true" space="true">{{setData.guize}}</text>
						</view>
					</view>
				</view>
			</view>
		</view>
		<loading v-if="loading"></loading>
	</view>
</template>
<script>
	var app = getApp();
	export default {
		data() {
			return {
				opt:{},
				bgcolor:'',
				fontcolor:'',
				pre_url:app.globalData.pre_url,
				bgpic:app.globalData.pre_url+"/static/img/channels_live/bg_pic.png",
				showrule:false,
				loading:false,
				channels:[],
				setData:[],
				liveList:[],
				finderUserName:'',
				thisLiveStatus:-1,
				errCode:0
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		methods: {
			getdata: function () {
				var that = this;
				var id  = that.opt.id || '';
        var bid = that.opt.bid || 0;
				
				// #ifdef H5
				app.alert('请使用微信小程序进行访问')
				// #endif
				
				that.loading = true;
				app.post('ApiWxChannelsLive/index', {id:id,bid:bid}, function (res) {
					that.loading = false;
					if (res.status == 0) {
						app.alert(res.msg);
						return;
					}
					var setData = res.setData;
					that.setData = res.setData;
					that.channels = res.channels;
					that.bgcolor = setData.bgcolor;
					that.fontcolor = setData.fontcolor;
					that.finderUserName = res.channels.sph_id || '';
					//判断是否存在背景图 不存在使用默认背景图
					if(setData.bgpic && setData.bgpic!= ''){
						that.bgpic = setData.bgpic
					}
					that.liveList = res.liveList;
					that.getLiveList()
				});
				
			},
			//直播列表
			getLiveList:function(){
				var that = this;
				var id  = that.opt.id || '';
        var bid = that.opt.bid || 0;
				//获取直播最新状态
				wx.getChannelsLiveInfo({
					finderUserName: that.finderUserName,
					success(r) {
						// status 2:直播中 3：直播结束
						that.thisLiveStatus = r.status;
					},
					fail(error){
						console.log(error,'getChannelsLiveInfo调用失败');
					},
					complete(c){
						console.log(that.thisLiveStatus ,'当前直播状态');
						//获取直播预告
						wx.getChannelsLiveNoticeInfo({
							finderUserName: that.finderUserName,
							success(res) {
								var list = that.liveList;
								if(res.status == 0){
									var otherInfos = res.otherInfos;
									var wxList = [];
									var wxDataList = [];
									wxList.push({
										headUrl: res.headUrl,
										nickname: res.nickname,
										noticeId: res.noticeId,
										reservable: res.reservable,
										startTime: res.startTime,
										status: res.status
									});
									wxDataList.push(res.startTime);
									//循环赋值
									otherInfos.forEach(info => {
									    wxList.push(info);
											wxDataList.push(info.startTime)
									});
								
									//获取当前时间
									const timestamp = Math.floor(new Date().getTime() / 1000);
									let mindiff = '',  //时间差
											dataindex = '',//直播中的key
											cancelId=[]; //取消直播预告ID
									//根据时间匹配
									for(var i in list){
										for(var x in wxList){
											//根据开播时间匹配
											if(list[i]['live_start_time'] === wxList[x]['startTime']){
												list[i]['headUrl'] = wxList[x]['headUrl'];
												list[i]['nickname'] = wxList[x]['nickname'];
												list[i]['noticeId'] = wxList[x]['noticeId'];
												list[i]['reservable'] = wxList[x]['reservable'];
												list[i]['startTime'] = wxList[x]['startTime'];
												list[i]['status'] = wxList[x]['status'];
												break;
											}	
										}
										//取消直播预告
										if(list[i]['live_status'] == 0){
											if(wxDataList.includes(list[i]['startTime']) == false){
												cancelId.push(list[i]['id']);
												list.splice(i, 1);
											}
										}
														
										//筛选贴近开播时间的key
										if(that.thisLiveStatus == 2){
											let diff = Math.abs(timestamp - list[i]['startTime']);
											if(mindiff == '' || diff < mindiff){
												mindiff = diff;
												dataindex = i;
											}
										}
									}
								
									//修改直播状态
									if(dataindex != '' && !app.isNull(list[dataindex])){
										list[dataindex]['live_status'] = 3; //直播中
									}
									that.liveList = list;
									//更新数据
									app.post('ApiWxChannelsLive/matchingData', {id:id,data:that.liveList,live_status:that.thisLiveStatus,cancel_id:cancelId,bid:bid});
									
									uni.setNavigationBarTitle({
										title: '预约直播'
									});
								}
							},
							fail(err){
								if(err.err_code){
									that.errCode = err.err_code;
								}
								console.log(err,'getChannelsLiveNoticeInfo调用失败');
							},
							complete(cr){
								
								//适用于 当前只有一条直播预告并且提前开播 getChannelsLiveNoticeInfo 返回空的情况下
								if(that.thisLiveStatus == 2 && that.errCode == 1){
									const timestamp = Math.floor(new Date().getTime() / 1000);
									let mindiff = '',  //时间差
											dataindex = '';//直播中的key
									let liveList = that.liveList;
									for(var i in liveList){
										let diff = Math.abs(timestamp - liveList[i]['startTime']);
										if(mindiff == '' || diff < mindiff){
											mindiff = diff;
											dataindex = i;
										}
										
										//修改直播状态
										if(dataindex != '' && !app.isNull(liveList[dataindex])){
											liveList[dataindex]['live_status'] = 3; //直播中
										}
									}
									//更新数据
									app.post('ApiWxChannelsLive/matchingData', {id:id,data:that.liveList,live_status:that.thisLiveStatus,bid:bid});
								}
								
								//thisLiveStatus -1 &&  errCode 1 只有一条直播预告并且没开播超过开播时间
								//thisLiveStatus 3 &&  errCode 1 只有一条直播预告并且结束
								if((that.thisLiveStatus == -1 || that.thisLiveStatus == 3) && that.errCode == 1){
									that.liveList = [];
									app.post('ApiWxChannelsLive/matchingData', {id:id,live_status:that.thisLiveStatus,bid:bid});
								}
								
								if(that.errCode == 1){
									
								}
							}
						})	
					}
				})
				
			},
			//规则开关
			changerule: function () {
			  this.showrule = !this.showrule
			},
			// 预约直播
			reserveChannelsLive:function(noticeId,id){
				var that = this;
        var bid  = that.opt.bid || 0;
				if(app.isNull(noticeId) && app.isNull(id)){
					app.error('预约异常');
					return;
				}
				// state = 1，正在直播中，用户点击“取消”拒绝前往直播
				// state = 2，正在直播中，用户点击“允许”前往直播
				// state = 3，预告已取消
				// state = 4，直播已结束
				// state = 5，用户此前未预约，在弹窗中未预约直播直接收起弹窗
				// state = 6，用户此前未预约，在弹窗中预约了直播
				// state = 7，用户此前已预约，在弹窗中取消了预约
				// state = 8，用户此前已预约，直接收起弹窗
				// state = 9，弹窗唤起前用户直接取消
				// state = 10，直播预约已过期
				wx.reserveChannelsLive({
					noticeId:noticeId,
					success(res) {
						console.log(res,'预约返回值');
						if(res.state == 6 || res.state == 7){
							app.post('ApiWxChannelsLive/reserveChannelsLive',{id:id,state:res.state,bid:bid}, function (r) {
								if(r.status == 0){
									console.log(r.msg);
								}
								// app.success(r.msg);
								setTimeout(function () {
								  that.getdata();
								}, 1000);
							})
						}
					},
					fail(err){
						console.log(err,'reserveChannelsLive调用失败');
					}
				})
			},
			//跳转直播间
			gotoLive:function(){
				var that = this;
				wx.getChannelsLiveInfo({
					finderUserName: that.finderUserName,
					success(res) {
						if(res.status == 3){
							app.alert('直播已结束');
						}else if(res.status == 2){
							wx.openChannelsLive({
								finderUserName: that.finderUserName,
							})
						}
					},
					fail(err) {
						console.log(err,'error');
					},
				})
			},
			//跳转视频号主页
			openChannels:function(){
				wx.openChannelsUserProfile({
					finderUserName:this.finderUserName,
					fail(err){
						if(err.errMsg == 'openChannelsUserProfile:fail'){
							console.log(err,'reserveChannelsLive调用失败');
							app.error('打开失败，请稍后再试');
						}
					}
				})
			},
			//分享
			onShareAppMessage: function() {
				return this._sharewx({
					title: this.setData.sharetitle ? this.setData.sharetitle : this.channels.nickname,
					pic: this.setData.sharepic ? this.setData.sharepic : this.channels.headimg,
					desc:this.setData.sharedesc ? this.setData.sharedesc : '',
					link:this.setData.sharelink ? this.setData.sharedesc : ''
				});
			},
			//分享到朋友圈
			onShareTimeline: function() {
				var sharewxdata = this._sharewx({
					title: this.setData.sharetitle ? this.setData.sharetitle : this.channels.nickname,
					desc: this.setData.sharedesc ? this.setData.sharedesc : '',
					link: this.setData.sharelink ? this.setData.sharelink : '',
					pic: this.setData.sharepic ? this.setData.sharepic : this.channels.headimg
				});
				var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
				return {
					title: sharewxdata.title,
					imageUrl: sharewxdata.imageUrl,
					query: query
				}
			},
			
		}
	}
</script>

<style>
	.page{height: 100%;color: #372D2D;background-size:100%}
	.page-box{background-size: 100%; background-repeat: no-repeat;min-height: 100vh;}
	.head-box{width:100%;margin: 0 auto;padding-top: 90rpx;}
	.logo-box{width: 120rpx;height: 120rpx;border-radius: 50%;background-color:#FFFFFF;margin: 0 auto;}
	.logo-box .logo{width: 100%;height: 100%;border-radius: 50%;}
	.head-title .title{color:#FF4001; font-size: 60rpx;font-weight: 900;line-height: 54rpx;text-align: center;margin-top: 32rpx;}
	.head-box .head-desc{width:534rpx;height:218rpx;margin:26rpx auto 0;font-size: 28rpx;line-height: 45rpx;color: #FF4001;}
	/* .button-channels{width:360rpx;height: 88rpx;background-color: #FF4001;border-radius: 50rpx;font-size: 32rpx;color: #FFFFFF;text-align: center;margin-top: -44rpx;background: linear-gradient(#FFE852,#FF4001)} */
	.button-channels{width:360rpx;height: 88rpx;background-repeat: no-repeat;background-size: 100%;margin: 0 auto;margin-top: -44rpx;}
	
	.content-box{padding-bottom: 40rpx;}
	.live-box{margin: 0 40rpx;padding: 40rpx;background: #FFFFFF;margin-top: 36rpx;border-radius: 48rpx;}
	.live-box .live-title{font-size: 32rpx;line-height: 54rpx;}
	.live-box .live-content{margin-top: 39rpx;}
	.live-content{height: 75rpx;}
	.live-content .live-time{padding-left: 35rpx;font-size: 28rpx;color: rgba(55, 45, 45, 0.64);line-height: 78rpx;}
	.live-content .live-reservation{width:180rpx;background-size: 100%;color: #FFFFFF;font-weight: 700;text-align: center;line-height: 72rpx;}
	.live-content .opacity{opacity:0.3}
	.rule-title{ position: absolute;top: 110rpx;right: 0;width: 72rpx;height: 144rpx;margin: 0 auto;text-align: center;padding: 14rpx;line-height: 54rpx;color: #FFF3CC;border-radius: 30rpx 0 0 30rpx;background: linear-gradient(155deg, #FF4001 0%, #B32C00 99%);box-shadow: -2px 5px 9px 2px rgba(0, 0, 0, 0.3);}
	.empty{display: flex;justify-content: center;align-items: center;    min-height: 300rpx;}
	#mask-rule,#mask {position: fixed;left: 0;top: 0;z-index: 999;width: 100%;height: 100%;background-color: rgba(0, 0, 0, 0.85);}
	#mask-rule .box-rule {position: relative;margin: 30% auto;padding-top: 40rpx;width: 90%;height: 675rpx;border-radius: 20rpx;background-color: #FA5C01;}
	#mask-rule .box-rule .star {position: absolute;left: 50%;top: -100rpx;margin-left: -130rpx;width: 259rpx;height:87rpx;}
	#mask-rule .box-rule .h2 {width: 100%;text-align: center;line-height: 34rpx;font-size: 34rpx;font-weight: normal;color: #fff;}
	#mask-rule #close-rule {position: absolute;right: 34rpx;top: 38rpx;width: 40rpx;height: 40rpx;}

	#mask-rule .con {overflow: auto;position: relative;margin: 40rpx auto;padding-right: 15rpx;width: 580rpx;height: 82%;line-height: 48rpx;font-size: 26rpx;color: #fff;}
	#mask-rule .con .text {position: absolute;top: 0;left: 0;width: inherit;height: auto;}
</style>
 