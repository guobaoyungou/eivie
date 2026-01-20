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
									{{userinfo.levelname}}
								</view>
							</view>
							<view class="reference-name flex flex-y-center"> 
								<view class='reference-options'>{{t('会员')}}ID：{{userinfo.mid}}</view>
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
					<view class="flex-col flex-y-center" v-if="userinfo.can_agent>0" @tap.stop="shareClick">
						<view class="rewards-top-title">邀请好友</view>
					</view>
					<view class="flex-col flex-y-center" v-if="userinfo.can_agent<=0">
						<view class="rewards-top-title">无推广权限</view>
					</view>
				</view>
				<view class="rewards-view flex flex-y-center rewards-view-class2">
					<view class="flex flex-y-center" style="justify-content: space-between;flex: 1;margin-top: 30rpx;">
						<view class="flex-col flex-y-center rewards-options">
							<view class="rewards-text">累计{{t('佣金')}}(元)</view>
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
			<view class="user-info-view flex-col" style="padding-bottom: 15rpx;">
				<view class="user-top-view flex flex-y-center">
					<view class="info-view flex flex-y-center" style="justify-content: space-between;">
						参团活动产品
					</view>
				</view>
				<view class="currentScroll" style="overflow-x: scroll;">
					<view class="prolist" v-if="prolist.length > 0 ">
						<view v-for="(item2, index2) in prolist" class="product" @tap.stop="goto" :data-url="'/pages/shop/product?id=' + item2.id">
							<image class="f1" :src="item2.pic"></image>
							<view class="f2">￥{{item2.sell_price}}</view>
						</view>
					</view>
				</view>
			</view>
			<button class="covermy" @tap="showdescFun" data-url="orderlist">活动说明</button>
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
		<view v-if="sharetypevisible" class="popup__container">
			<view class="popup__overlay" @tap.stop="handleClickMask"></view>
			<view class="popup__modal" style="height:320rpx;min-height:320rpx">
				<!-- <view class="popup__title">
					<text class="popup__title-text">请选择分享方式</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hidePstimeDialog"/>
				</view> -->
				<view class="popup__content">
					<view class="sharetypecontent">
						<!-- #ifdef APP -->
						<view class="f1" @tap="shareapp">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</view>
						<!-- #endif -->
						<!-- #ifdef H5 -->
						<view class="f1" @tap="sharemp" v-if="getplatform() == 'mp'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</view>
						<!-- #endif -->
						<!-- #ifndef H5 -->
						<button class="f1" open-type="share" :data-id="share_active_id" v-if="getplatform() != 'h5'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</button>
						<!-- #endif -->
						<view class="f2" @tap="showPoster">
							<image class="img" :src="pre_url+'/static/img/sharepic.png'"/>
							<text class="t1">生成分享图片</text>
						</view>
						<!-- #ifdef MP-WEIXIN -->
						<view class="f1" @tap="shareScheme" v-if="xcx_scheme">
							<image class="img" :src="pre_url+'/static/img/weixin.png'"/>
							<text class="t1">小程序链接</text>
						</view>
						<!-- #endif -->
					</view>
				</view>
			</view>
		</view>
		<view class="posterDialog" v-if="showposter">
			<view class="main">
				<view class="close" @tap="posterDialogClose"><image class="img" :src="pre_url+'/static/img/close.png'"/></view>
				<view class="content">
					<image class="img" :src="posterpic" mode="widthFix" @tap="previewImage" :data-url="posterpic"></image>
				</view>
			</view>
		</view>
		
		<view v-if="showdesc" class="xieyibox">
			<view class="xieyibox-content">
				
					<parse :content="active.desc"></parse>
				
				<view  class="xieyibut-view flex-y-center">
					<view class="but-class" :style="{background:t('color1')}"  @tap="closeDesc">确定</view>
				</view>
			</view>
		</view>
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
				id:0,//活动id
				active:{},//活动信息
				prolist:[],//活动产品
				showdesc:false,//弹窗展示详情
				share_active_id:0,//推广活动id
				sharetypevisible: false,
				showposter:false,
				posterpic:'',
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.id = this.opt.id || 0;
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
				app.post('ApiLiandong/userdata', {active_id:that.id,st: st,pagenum: pagenum}, function (res) {
					that.loading = false;
					if(!res.status){
						app.error(res.msg);
						return;
					}
					var data = res.datalist;
					if (pagenum == 1) {
						that.userinfo = res.userinfo;
						that.active = res.active;
						that.prolist = res.prolist;
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
			closeDesc(){
				this.showdesc = false;
			},
			showdescFun: function () {
			  this.showdesc = true;
			},
			
			
			shareClick: function (e) {
				var that = this;
				this.share_active_id = that.active.id;
				app.post('ApiLiandong/detail', {id:that.share_active_id}, function (data) {
					that.active = data.info;
				});
				this.sharetypevisible = true;
			},
			handleClickMask: function () {
				this.sharetypevisible = false
			},
			showPoster: function () {
				var that = this;
				that.showposter = true;
				that.sharetypevisible = false;
				app.showLoading('生成海报中');
				app.post('ApiLiandong/poster', {active_id: that.share_active_id}, function (data) {
					app.showLoading(false);
					if (data.status == 0) {
						app.alert(data.msg);
					} else {
						that.posterpic = data.poster;
					}
				});
			},
			posterDialogClose: function () {
				this.showposter = false;
			},
			shareScheme: function () {
				var that = this;
				app.showLoading();
				app.post('ApiLiandong/getwxScheme', {active_id: that.share_active_id}, function (data) {
					app.showLoading(false);
					if (data.status == 0) {
						app.alert(data.msg);
					} else {
						that.showScheme = true;
						that.schemeurl=data.openlink
					}
				});
			},
			sharemp:function(){
				app.error('点击右上角发送给好友或分享到朋友圈');
				this.sharetypevisible = false
			},
			shareapp:function(){
				// #ifdef APP
				var that = this;
				that.sharetypevisible = false;
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
							sharedata.title = that.active.sharetitle || that.active.name;
							sharedata.summary = that.active.sharedesc || that.active.desc;
							sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesC/liandong/active?scene=id_'+that.share_active_id+'-pid_' + app.globalData.mid;
							sharedata.imageUrl = that.active.pic;
							var sharelist = app.globalData.initdata.sharelist;
							if(sharelist){
								for(var i=0;i<sharelist.length;i++){
									if(sharelist[i]['indexurl'] == '/pagesC/liandong/active'){
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
				// #endif
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
	
	.covermy{position:absolute;z-index:99999;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;z-index:9999;top:470rpx;right:0;color:#fff;background-color:rgba(17,17,17,0.3);width:140rpx;height:60rpx;font-size:26rpx;border-radius:30rpx 0px 0px 30rpx;}
	.xieyibox{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)}
	.xieyibox-content{width:90%;margin:0 auto;/*  #ifdef  MP-TOUTIAO */height:60%;/*  #endif  *//*  #ifndef  MP-TOUTIAO */height:80%;/*  #endif  */margin-top:20%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px;}
	.xieyibox-content .xieyibut-view{height: 60rpx;position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;justify-content: space-around;}
	.xieyibox-content .xieyibut-view .but-class{text-align:center; width: auto;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;padding:0rpx 25rpx;}
	
	.currentScroll::-webkit-scrollbar {display: none;width: 0 !important;height: 0 !important;-webkit-appearance: none;background: transparent;color: transparent;}
	.currentScroll {-ms-overflow-style: none;}
	.currentScroll {overflow: -moz-scrollbars-none;}
	.user-info-view .prolist{white-space: nowrap;margin-top:16rpx; }
	.user-info-view .prolist .product{width:150rpx;height:200rpx;overflow:hidden;display:inline-flex;flex-direction:column;align-items:center;margin-right:24rpx}
	.user-info-view .prolist .product .f1{width:160rpx;height:160rpx;border-radius:8rpx;background:#f6f6f6}
	.user-info-view .prolist .product .f2{font-size:22rpx;color:#FC5648;font-weight:bold;margin-top:4rpx}
</style>