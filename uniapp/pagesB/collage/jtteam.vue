<template>
<view>
	<block v-if="isload">
		<view class="container">
			<view class="content-view"  :style="{backgroundImage:'url('+pre_url+'/static/imgsrc/collage/bg.png)'}">
				<view class="title-view paddimg-around">
					<view class="left-img">
						<image :src="pre_url+'/static/imgsrc/collage/titlelog.png'"></image>
					</view>
					<view class="time-view">
						活动倒计时：
						<view class="djstime-view" id="djstime">
							<text class="djsspan">{{djshour}}</text> : <text class="djsspan">{{djsmin}}</text> : <text class="djsspan">{{djssec}}</text>
						</view>
					</view>
				</view>
				<!--  -->
				<view class="content-options">
					<view class="title-view">
						{{product.name}}
					</view>
					<view class="bot-list-view">
						<block v-for="(item,index) in product.jieti_data" >
							<view class="list-class">
								<view class="left-view">
									<view class="icon-view">
										<image :src= "pre_url+'/static/imgsrc/collage/listicon.png'"  ></image>
									</view>
									<view class="text-view">
										<view style="color: #242424;margin-right: 30rpx;">满{{item.teamnum}}人</view>
										<view style="color: #88827F;">团长送{{item.goodsname}}</view>
									</view>
								</view>
								<view class="keshi-text">课时:{{item.goodsnum}}</view>
							</view>
						</block>
					</view>
				</view>
				<!--  -->
				<view class="content-options" style="padding: 0rpx 30rpx;">
					<view class="title-bg-view"  :style="{backgroundImage:'url('+pre_url+'/static/imgsrc/collage/titbg.png)'}" >拼团详情</view>
					<view class="shuju-view">
						<view class="shuju-options-view">
							<view class="num-class">{{product.view_num}}</view>
							<view class="text-num">已浏览</view>
						</view>
						<view class="shuju-options-view">
							<view class="num-class">{{product.ordernum}}</view>
							<view class="text-num">已报名</view>
						</view>
						<view class="shuju-options-view">
							<view class="num-class">{{product.share_num}}</view>
							<view class="text-num">已分享</view>
						</view>
					</view>
					<view class="browsing-history">
						<view style="white-space: nowrap;">浏览记录</view>
						<view class="view-class-view">
							<view class="touxiang-view">
								<block v-for="(item,index) in view_history" :key="index">
									<view class="avater-view">
										<image   :src= "item.headimg" ></image>
									</view>
								</block>
								
								<view style="width: 15rpx;"></view>
							</view>
							<view class="avater-view" >
								<image  :src= "pre_url+'/static/imgsrc/collage/genduo.png'"></image>
							</view>
						</view>

					</view>
				</view>
				<!--  -->
				<view class="content-options" style="padding: 0rpx 30rpx;">
					<view class="title-bg-view" :style="{backgroundImage:'url('+pre_url+'/static/imgsrc/collage/titbg.png)'}" >拼团人员</view>
					<view class="details-view">
						<block v-for="(item,index) in userlist" :key="index" v-if="userlist.length > 0">
							<view class="details-view-options">
								<view class="img-view"><image :src="item.headimg?item.headimg:pre_url+'/static/img/wh.png'" ></image></view>
								<!-- <text class="f2" v-if="item.id == team.mid">团长</text> -->
								<view class='name-text'>{{item.nickname}}</view>
							</view>
						</block>
					</view>
					<view class="tips-text">
						当前{{team.num}}人在拼单，可以直接参与
					</view>
				</view>
				<!--  -->
				<view class="content-options" style="padding: 0rpx 30rpx;">
					<view class="title-bg-view" :style="{backgroundImage:'url('+pre_url+'/static/imgsrc/collage/titbg.png)'}" >拼团介绍</view>
					<view class="jieshao-view">
						<dp :pagecontent="pagecontent"></dp>
					</view>
				</view>
				<!--  -->
				<view style="height: 180rpx;"></view>
				<!-- but -->
				<view class="but-view">
					<view class="kefu-view" @tap.stop="callphone" :data-phone="shopset.tel">
						<image :src= "pre_url+'/static/imgsrc/collage/lianxi.png'" ></image>
						<view>联系商家</view>
					</view>
					<view class="right-view">
						<view class='button-class kt' v-if="team.status==1 && haveme==0 && team.haveorder ==0"  @tap="buydialogShow" data-btntype="2">我要开团</view>
						<view class='button-class kt' v-if="team.haveorder ==1"  @tap="toOrderlist" >拼团订单</view>
						<view class="button-class ct" v-if="team.haveorder ==1"  @tap="shareClick"><text>邀请好友参团</text></view>
						<view class='button-class ct'  v-if="team.status==1 && haveme==0 &&  team.haveorder ==0"  @tap="handleShowJt" data-btntype="3">我要参团</view>
					</view>
				</view>
			</view>
		</view>
		<view v-if="sharetypevisible" class="popup__container">
			<view class="popup__overlay" @tap.stop="handleClickMask"></view>
			<view class="popup__modal" style="height:320rpx;min-height:320rpx">
				<view class="popup__content">
					<view class="sharetypecontent">
						<view class="f1" @tap="shareapp" v-if="getplatform() == 'app'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'" />
							<text class="t1">分享给好友</text>
						</view>
						<view class="f1" @tap="sharemp" v-else-if="getplatform() == 'mp'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'" />
							<text class="t1">分享给好友</text>
						</view>
						<!-- <view class="f1" @tap="sharemp" v-else-if="getplatform() == 'h5'">
						<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
						<text class="t1">分享给好友</text>
					</view> -->
						<button class="f1" open-type="share" v-else-if="getplatform() != 'h5'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'" />
							<text class="t1">分享给好友</text>
						</button>
						<view class="f2" @tap="showPoster">
							<image class="img" :src="pre_url+'/static/img/sharepic.png'" />
							<text class="t1">生成分享图片</text>
						</view>
					</view>
				</view>
			</view>
		</view>
		<view :hidden="buydialogHidden">
			<view class="buydialog-mask">
				<view class="buydialog">
					<view class="close" @tap="buydialogChange">
						<image :src="pre_url+'/static/img/close.png'" class="buydialog-canimg"></image>
					</view>
					<view class="title">
						<image :src="guigelist[ks].pic?guigelist[ks].pic:product.pic" class="img"
							@tap="previewImage" :data-url="guigelist[ks].pic?guigelist[ks].pic:product.pic">
						</image>
						<view class="price"><text class="t1">￥</text>{{guigelist[ks].sell_price}} <text
								v-if="guigelist[ks].market_price > guigelist[ks].sell_price"
								class="t2">￥{{guigelist[ks].market_price}}</text></view>
						<view class="choosename">已选规格: {{guigelist[ks].name}}</view>
						<view class="stock">剩余{{guigelist[ks].stock}}件</view>
					</view>
		
					<view v-for="(item, index) in guigedata" :key="index" class="guigelist flex-col">
						<view class="name">{{item.title}}</view>
						<view class="item flex flex-wp">
							<block v-for="(item2, index2) in item.items" :key="index2">
								<view :data-itemk="item.k" :data-idx="item2.k"
									:class="'item2 ' + (ggselected[item.k]==item2.k ? 'on':'')"
									@tap="ggchange">{{item2.title}}</view>
							</block>
						</view>
					</view>
					<view class="buynum flex flex-y-center" v-if="!product.collage_type">
						<view class="flex1">购买数量：</view>
						<view class="f2 flex flex-y-center">
							<text class="minus flex-x-center" @tap="gwcminus">-</text>
							<input class="flex-x-center" type="number" :value="gwcnum"
								@input="gwcinput"></input>
							<text class="plus flex-x-center" @tap="gwcplus">+</text>
						</view>
					</view>
					<block>
						<button class="tobuy" @tap="tobuy" :data-type="btntype">确 定</button>
					</block>
				</view>
			</view>
		</view>
		<!-- 阶梯拼团 -->
		<view class="posterDialog" v-if="showposter">
			<view class="main">
				<view class="close" @tap="posterDialogClose"><image class="img" :src="pre_url+'/static/img/close.png'"/></view>
				<view class="content">
					<image class="img" :src="posterpic" mode="widthFix" @tap="previewImage" :data-url="posterpic"></image>
				</view>
			</view>
		</view>
		<view v-if="showJt" class="popup__container">
			<view class="popup__overlay" @tap.stop="handleShowJt"></view>
			<view class="popup__modal" style="height:320rpx;min-height:320rpx">
				<view class="popup__content">
					<view class="jt_tab" >
						<view class="x1" @tap="changeKh"  data-type="1" :style="{backgroundColor:jt_st==1?t('color1'):'',color:jt_st==1?'#fff':t('color1'),borderColor:t('color1')}">老客户</view>
						<view class="x2" @tap="changeKh" data-type="2" :style="{backgroundColor:jt_st==2?t('color1'):'',color:jt_st==2?'#fff':t('color1'),borderColor:t('color1')}">新客户</view>
					</view>
					<view class="btnlist" v-if="jt_st ==1 || jt_st==2">
						<view class="tocart" v-if="jt_st ==1 ||jt_st ==2"  :style="{background:t('color1')}"  @tap="buydialogShow" data-btntype="2"><text>我要开团</text></view>
						<view class="tobuy" v-if="jt_st ==2" :style="{background:t('color2')}" @tap="buydialogShow" data-btntype="3" ><text>我要参团</text></view>
					</view>
				</view>
			</view>
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
				opt: {},
				loading: false,
				isload: false,
				menuindex: -1,
				pre_url: app.globalData.pre_url,
				tabnum: 1,
				ggselected: [],
				guigedata: [],
				guigelist: [],
				haveme: 0,
				ks: '',
				gwcnum: 1,
				showdetail: false,
				buydialogHidden: true,
				team: [],
				userlist: [],
				product: [],
				rtime: '',
				rtimeformat: '',
				isfavorite: "",
				sharetypevisible: false,
				showposter: false,
				posterpic: "",
				show_mingpian: false,
				djshour: '00',
				djsmin: '00',
				djssec: '00',
				nowtime: "",
				btntype: 3,
				current: 0,
				showJt:false,
				jt_st:0,
				history:[],
				pagecontent: "",
				view_history:[],
				shopset:''
			}
		},
		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		onShareAppMessage: function() {
			return this._sharewx({
				title: '就差你了，快来一起拼团~ ' + this.product.name,
				pic: this.product.pic
			});
		},
		onShareTimeline: function() {
			var sharewxdata = this._sharewx({
				title: '就差你了，快来一起拼团~ ' + this.product.name,
				pic: this.product.pic
			});
			var query = (sharewxdata.path).split('?')[1] + '&seetype=circle';
			return {
				title: sharewxdata.title,
				imageUrl: sharewxdata.imageUrl,
				query: query
			}
		},
		methods: {
			getdata: function() {
				var that = this;
				var teamid = that.opt.teamid;
				that.loading = true;
				app.get('ApiCollage/team', {
					teamid: teamid
				}, function(res) {
					that.loading = false;
					that.ggselected = res.ggselected;
					that.guigedata = res.guigedata;
					that.guigelist = res.guigelist;
					that.haveme = res.haveme;
					that.ks = res.ks;
					that.product = res.product;
					that.rtime = res.rtime;
					that.shopset = res.shopset;
					that.sysset = res.sysset;
					that.team = res.team;
					that.userlist = res.userlist;
					that.show_mingpian = res.show_mingpian;
					that.view_history = res.view_history;
					if (res.product.nowtime) {
						that.nowtime = res.product.nowtime;
					}
					var pagecontent = JSON.parse(res.product.detail);
					that.pagecontent = pagecontent;
					that.getTgdjs();
					setInterval(function() {
						that.nowtime = that.nowtime + 1;
						that.getTgdjs();
					}, 1000);
					that.loaded({
						title: '就差你了，快来一起拼团~ ',
						desc: res.product.name,
						pic: res.product.pic
					});
				});
			},
			swiperChange: function(e) {
				var that = this;
				that.current = e.detail.current;
			},
			buydialogChange: function(e) {
				this.buydialogHidden = !this.buydialogHidden
			},
			//选择规格
			ggchange: function(e) {
				var idx = e.currentTarget.dataset.idx;
				var itemk = e.currentTarget.dataset.itemk;
				var ggselected = this.ggselected;
				ggselected[itemk] = idx;
				var ks = ggselected.join(',');
				this.ggselected = ggselected;
				this.ks = ks;
			},
			//加入购物车
			buydialogShow: function(e) {
				var btntype = e.currentTarget.dataset.btntype;
				if (btntype == 3) {
					this.teamid = e.currentTarget.dataset.teamid
				}
				this.btntype = btntype;
				this.buydialogHidden = !this.buydialogHidden;
				this.showJt = false;
			},
			//加
			gwcplus: function(e) {
				var gwcnum = this.gwcnum + 1;
				var ggselected = this.ks;

				if (gwcnum > this.guigelist[ggselected].stock) {
					app.error('库存不足');
					return;
				}
				this.gwcnum = this.gwcnum + 1;
			},
			//减
			gwcminus: function(e) {
				var gwcnum = this.gwcnum - 1;
				var ggselected = this.ks;

				if (gwcnum <= 0) {
					return;
				}
				this.gwcnum = this.gwcnum - 1;
			},
			//输入
			gwcinput: function(e) {
				var ggselected = this.ks;
				var gwcnum = parseInt(e.detail.value);
				if (gwcnum < 1) return 1;
				if (gwcnum > this.guigelist[ggselected].stock) {
					return this.guigelist[ggselected].stock;
				}
				this.gwcnum = gwcnum;
			},
			tobuy: function(e) {
				var type = e.currentTarget.dataset.type;
				var that = this;
				var ggselected = that.ks;
				var proid = that.product.id;
				var ggid = that.guigelist[ggselected].id;
				var num = that.gwcnum; //var prodata = proid + ',' + ggid + ',' + num;
				app.goto('/activity/collage/buy?proid=' + proid + '&num=' + num + '&ggid=' + ggid + '&buytype=' +
					type + '&teamid=' + that.team.id);
			},

			getTgdjs:function(){
				var that = this
				var nowtime = that.nowtime*1;
				var starttime = that.product.starttime*1;
				var endtime = that.product.endtime*1;
			
				if(endtime < nowtime){ //已结束
					that.tuangou_status = 2
					that.djshour = '00';
					that.djsmin = '00';
					that.djssec = '00';
				}else{
					if(starttime > nowtime){ //未开始
						that.tuangou_status = 0
						var totalsec = starttime - nowtime;
					}else{ //进行中
						that.tuangou_status = 1
						var totalsec = endtime - nowtime;
					}
					var houer = Math.floor(totalsec/3600);
					var min = Math.floor((totalsec - houer *3600)/60);
					var sec = totalsec - houer*3600 - min*60
					var djs = (houer<10?'0':'')+houer+'时'+(min<10?'0':'')+min+'分'+(sec<10?'0':'')+sec+'秒';
					var djshour = (houer<10?'0':'')+houer
					var djsmin = (min<10?'0':'')+min
					var djssec = (sec<10?'0':'')+sec
					that.djshour = djshour;
					that.djsmin = djsmin;
					that.djssec = djssec;
				}
			},
			shareClick: function() {
				this.sharetypevisible = true;
			},
			handleClickMask: function() {
				this.sharetypevisible = false;
			},
			showPoster: function() {
				var that = this;
				that.showposter = true;
				that.sharetypevisible = false;
				app.showLoading('努力生成中');
				app.post('ApiCollage/getTeamPoster', {
					proid: that.product.id,
					teamid: that.team.id
				}, function(data) {
					app.showLoading(false);
					if (data.status == 0) {
						app.alert(data.msg);
					} else {
						that.posterpic = data.poster;
					}
				});
			},
			
			
			scroll:function(e){
				var scrollTop = e.detail.scrollTop;
				//console.log(e)
				var that = this;
				if (scrollTop > 200) {
					that.scrolltopshow = true;
				}
				if(scrollTop < 150) {
					that.scrolltopshow = false
				}
				if (scrollTop > 100) {
					that.toptabbar_show = true;
				}
				if(scrollTop < 50) {
					that.toptabbar_show = false
				}
				var height0 = that.scrolltab0Height;
				var height1 = that.scrolltab0Height + that.scrolltab1Height;
				var height2 = that.scrolltab0Height + that.scrolltab1Height + that.scrolltab2Height;
				//var height3 = that.scrolltab0Height + that.scrolltab1Height + that.scrolltab2Height + that.scrolltab3Height;
				//console.log(that.scrolltab0Height);
				if(scrollTop >=0 && scrollTop < height0){
					//this.scrollToViewId = 'scroll_view_tab0';
					this.toptabbar_index = 0;
				}else if(scrollTop >= height0 && scrollTop < height1){
					//this.scrollToViewId = 'scroll_view_tab1';
					this.toptabbar_index = 1;
				}else if(scrollTop >= height1 && scrollTop < height2){
					//this.scrollToViewId = 'scroll_view_tab2';
					this.toptabbar_index = 2;
				}else if(scrollTop >= height2){
					//this.scrollToViewId = 'scroll_view_tab3';
					this.toptabbar_index = 3;
				}
			},
			
			posterDialogClose: function() {
				this.showposter = false;
			},
			sharemp: function() {
				app.error('点击右上角发送给好友或分享到朋友圈');
				this.sharetypevisible = false
			},
			shareapp: function() {
				var that = this;
				uni.showActionSheet({
					itemList: ['发送给微信好友', '分享到微信朋友圈'],
					success: function(res) {
						if (res.tapIndex >= 0) {
							var scene = 'WXSceneSession';
							if (res.tapIndex == 1) {
								scene = 'WXSenceTimeline';
							}
							var sharedata = {};
							sharedata.provider = 'weixin';
							sharedata.type = 0;
							sharedata.scene = scene;
							sharedata.title = that.product.name;
							//sharedata.summary = app.globalData.initdata.desc;
							sharedata.href = app.globalData.pre_url + '/h5/' + app.globalData.aid +
								'.html#/activity/collage/product?scene=id_' + that.product.id + '-pid_' +
								app.globalData.mid;
							sharedata.imageUrl = that.product.pic;
							var sharelist = app.globalData.initdata.sharelist;
							if (sharelist) {
								for (var i = 0; i < sharelist.length; i++) {
									if (sharelist[i]['indexurl'] == '/activity/collage/product') {
										sharedata.title = sharelist[i].title;
										sharedata.summary = sharelist[i].desc;
										sharedata.imageUrl = sharelist[i].pic;
										if (sharelist[i].url) {
											var sharelink = sharelist[i].url;
											if (sharelink.indexOf('/') === 0) {
												sharelink = app.globalData.pre_url + '/h5/' + app
													.globalData.aid + '.html#' + sharelink;
											}
											if (app.globalData.mid > 0) {
												sharelink += (sharelink.indexOf('?') === -1 ? '?' : '&') +
													'pid=' + app.globalData.mid;
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
			},
			changeKh:function(e){
				var jt_status = e.currentTarget.dataset.type;
				this.jt_st = jt_status;
			},
			handleShowJt:function(e){
				this.showJt = !this.showJt;
				this.jt_st = 0
			},
			callphone:function(e) {
				var phone = e.currentTarget.dataset.phone;
				uni.makePhoneCall({
					phoneNumber: phone,
					fail: function () {
					}
				});
			},
			toOrderlist:function(){
				app.goto('/activity/collage/orderlist');
			}
		}
	}
</script>

<style>
	page{background: #FDCD64;}
	.paddimg-around{padding: 30rpx;}
	.content-view{width: 100%;height: auto;background-repeat: no-repeat;display: flex;flex-direction: column;}
	.content-view .title-view{width: 100%;display: flex;align-items: center;justify-content: space-between;}
	.content-view .title-view .left-img{width: 146rpx;height: 30rpx;}
	.content-view .title-view .left-img image{width: 100%;height: 100%;}
	.content-view .title-view .time-view{display: flex;align-items: center;justify-content: flex-end;font-size:24rpx;color: #FFFFFF;}
	.djstime-view .djsspan{background-color: #fff;color: #FF4622;font-size: 24rpx;border-radius:8rpx;margin: 0rpx 10rpx;display: inline-block;min-width: 40rpx;height: 40rpx;text-align: center;line-height: 40rpx;padding: 0 5rpx}
	.content-options{width: 94%;background: #FFFFFF;border-radius: 15px;margin: 0 auto;padding: 30rpx 30rpx;margin-top: 20rpx;}
	.content-options .title-view {font-size: 30rpx;line-height: 24px;color: #242526;border-bottom: 1px #E4E5E6 solid;padding-bottom: 20rpx;font-weight: 700;}
	.content-options .bot-list-view{padding: 20rpx 0rpx;}
	.content-options .list-class{width: 100%;display: flex;align-items: center;justify-content: space-between;padding: 10rpx 0rpx;}
	.content-options .list-class .left-view{display: flex;align-items: center;justify-content: flex-start;}
	.list-class .left-view .icon-view {width: 30rpx;height: 30rpx;margin-right: 30rpx;}
	.list-class .left-view .icon-view image{width: 100%;height: 100%;}
	.list-class .left-view .text-view{display: flex;align-items: center;justify-content: flex-start;font-size: 24rpx;}
	.content-options .list-class .keshi-text{font-size: 24rpx;color: #FF4622;}
	.title-bg-view{width: 100%;height: 78rpx;background-repeat: no-repeat;background-position: 50% -5%;color: #FF5D1D;font-size: 28rpx;
	text-align: center;line-height: 68rpx;font-weight: bold;}
	.content-options .shuju-view{width: 100%;border-bottom: 1px #E4E5E6 solid;display: flex;align-items: center;justify-content: center;padding: 40rpx 0rpx 50rpx;}
	.content-options .shuju-view .shuju-options-view{display: flex;align-items: center;flex-direction: column;width: 30%;}
	.shuju-options-view .num-class{color: #FF5D1D;font-size: 40rpx;font-weight: bold;}
	.shuju-options-view .text-num{color: #483B36;font-size: 24rpx;margin-top: 10rpx;}
	.browsing-history{width: 100%;display: flex;align-items: center;justify-content: space-between;color: #242424;font-size: 24rpx;padding: 30rpx 0rpx;}
	.browsing-history .touxiang-view{display: flex;align-items: center;width: 75%;overflow: hidden;justify-content: flex-end;margin-right: 10rpx;}
	.browsing-history .touxiang-view .avater-view{width: 55rpx;height: 55rpx;border-radius:50%;border: 1px #fff solid;display: table;margin-right: -15rpx;overflow: hidden;}
	.browsing-history .touxiang-view .avater-view image{width: 100%;height: 100%;}
	.avater-view{width: 55rpx;height: 55rpx;border-radius:50%;border: 1px #fff solid;display: table;}
	.avater-view image{width: 100%;height: 100%;}
	.view-class-view{display: flex;align-items: center;justify-content: flex-end;flex: 1;}
	.details-view{display: flex;align-items: center;justify-content: flex-start;flex-wrap: wrap;}
	.details-view .details-view-options{display: flex;align-items: center;flex-direction: column;padding: 0rpx 10rpx;margin: 20rpx 6rpx;width: 117rpx;}
	.details-view-options .img-view{width: 80rpx;height: 80rpx;border-radius: 50%;overflow: hidden;padding: 7rpx;border: 1rpx solid #EEEEEE; }
	.details-view-options .img-view image{width: 100%;height: 100%;border-radius: 50%;}
	.details-view-options .name-text{font-size: 24rpx;color: #242424;margin-top: 20rpx;text-align: center;overflow: hidden;width: 80rpx;text-overflow: ellipsis;}
	.content-options .tips-text{color: #FE6F09;font-size: 24rpx;width: 100%;text-align: center;padding: 20rpx 0rpx;}
	.content-options .jieshao-view{width: 100%;padding: 20rpx 0rpx;}
	.but-view{width: 100%;display: flex;align-items: center;justify-content: space-between;padding: 20rpx 20rpx;background: #fff;position: fixed;bottom: 0rpx;
	}
	.but-view .kefu-view{display: flex;align-items: center;flex-direction: column;font-size:24rpx;color: rgba(34, 34, 34, 0.6);justify-content: center;width: 150rpx;}
	.but-view .kefu-view image{width: 40rpx;height: 40rpx;margin-bottom: 10rpx;}
	.but-view .right-view{display: flex;align-items: center;justify-content: space-between;}
	.but-view .right-view .button-class{width: 130px;height: 44px;border-radius: 50px;font-size: 28rpx;font-weight: bold;display: flex;align-items: center;justify-content: center;}
	.kt{color: #FE6F09;background: rgba(254, 111, 9, 0.12);}
	.ct{color: #fff;background: #FE6F09;}
	
	.buydialog-mask {
		position: fixed;
		top: 0px;
		left: 0px;
		width: 100%;
		background: rgba(0, 0, 0, 0.5);
		bottom: 0px;
		z-index: 9
	}
	
	.buydialog {
		position: absolute;
		width: 100%;
		left: 0px;
		bottom: 0px;
		background: #fff;
		z-index: 9
	}
	
	.buydialog .close {
		position: absolute;
		top: 0;
		right: 0;
		padding: 20rpx;
		z-index: 9999
	}
	
	.buydialog .close image {
		width: 30rpx;
		height: 30rpx;
	}
	
	.buydialog .title {
		width: 94%;
		position: relative;
		margin: 0 3%;
		padding: 20rpx 0px;
		border-bottom: 1px #e5e5e5 solid;
		height: 140rpx;
	}
	
	.buydialog .title .img {
		width: 160rpx;
		height: 160rpx;
		position: absolute;
		top: -40rpx;
		border-radius: 10rpx;
		border: 1px #e5e5e5 solid;
		background-color: #fff
	}
	
	.buydialog .title .price {
		padding-left: 180rpx;
		width: 100%;
		font-size: 36rpx;
		height: 50rpx;
		color: #ff4a03;
		overflow: hidden;
	}
	
	.buydialog .title .price .t1 {
		font-size: 24rpx
	}
	
	.buydialog .title .price .t2 {
		font-size: 26rpx;
		text-decoration: line-through;
		color: #aaa
	}
	
	.buydialog .title .choosename {
		padding-left: 180rpx;
		width: 100%;
		font-size: 24rpx;
		height: 30rpx;
		line-height: 30rpx;
	}
	
	.buydialog .title .stock {
		padding-left: 180rpx;
		width: 100%;
		font-size: 22rpx;
		height: 30rpx;
		line-height: 30rpx;
		color: #aaa
	}
	
	.buydialog .guigelist {
		width: 94%;
		position: relative;
		margin: 0 3%;
		padding: 0px 0px 10px 0px;
		border-bottom: 1px #e5e5e5 solid;
	}
	
	.buydialog .guigelist .name {
		height: 70rpx;
		line-height: 70rpx;
	}
	
	.buydialog .guigelist .item {
		font-size: 30rpx;
		color: #333;
	}
	
	.buydialog .guigelist .item2 {
		border: 1px #a9a9a9 solid;
		border-radius: 8rpx;
		padding: 5rpx 10rpx;
		color: #353535;
		margin-right: 10rpx;
		font-size: 26rpx;
		margin-top: 10rpx;
		display: inline-block;
	}
	
	.buydialog .guigelist .on {
		border: 1px #ff4a03 solid;
		color: #ff4a03;
	}
	
	.buydialog .buynum {
		width: 94%;
		position: relative;
		margin: 0 3%;
		padding: 10px 0px 10px 0px;
	}
	
	.buydialog .buynum .f2 {
		border: 1px solid #aaa
	}
	
	.buydialog .buynum .f2 input {
		flex: 1;
		width: 70rpx;
		border-left: 1px solid #aaa;
		border-right: 1px solid #aaa;
		text-align: center
	}
	
	.buydialog .buynum .f2 .plus {
		width: 50rpx;
	}
	
	.buydialog .buynum .f2 .minus {
		width: 50rpx;
	}
	
	.buydialog .addcart {
		height: 45px;
		line-height: 45px;
		background: #e94745;
		color: #fff;
		border-radius: 0px;
		border: none;
		font-size: 16px;
	}
	
	.buydialog .tobuy {
		height: 45px;
		line-height: 45px;
		background: #ff6801;
		color: #fff;
		border-radius: 0px;
		border: none;
		font-size: 16px;
	}
	.jt_tab{overflow: hidden;display: flex;justify-content: center;margin-top: 20rpx;}
	.jt_tab .x1{width: 120rpx;height: 60rpx;line-height: 60rpx;border:1rpx solid #18be6c;text-align: center;}
	.jt_tab .x2{width: 120rpx;height: 60rpx;line-height: 60rpx;color:#fff;border:1rpx solid #18be6c;text-align: center;}
	
	.btnlist{display: flex;justify-content: space-evenly;margin-top: 70rpx;}
	.btnlist .tocart{ width:35%; height: 80rpx;border-radius:10rpx;color: #fff; background: #fa938a; font-size: 28rpx;display:flex;flex-direction:column;align-items:center;justify-content:center;border-radius:45rpx;}
	 .btnlist .tobuy{ width:35%; height: 80rpx;border-radius:10rpx;color: #fff; background: #df2e24; font-size:28rpx;display:flex;flex-direction:column;align-items:center;justify-content:center;border-radius:45rpx;}
</style>