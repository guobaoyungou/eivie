<template>
	<view class="container" :style="{ background:backcolor==''?'#c40004':backcolor}">
		<block v-if="isload">
			<img :src="backimg" v-if="backimg!=''" class="back_img" mode="widthFix" alt="" />
			<view class="body">
				<view class="wrap">
					<view class="header clearfix">
						<view class="rule" @tap="changemaskrule">活动规则</view>
						<view @tap="goto" :data-url="'myprize?hid=' + info.id" class="my">我的奖品</view>
					</view>
					<view class="title" :style="'background-image:url(' + info.banner + ');background-size:100% 100%;'">
					</view>

					<view class="scratch-bg">
						<view style="position:relative">
							<image class="scratch-bg-1" :src="pre_url+'/static/img/ggk/scratch_bg.png'"></image>
							<image class="scratch-bg-2" id="frame" :src="pre_url+'/static/img/ggk/scratch_kuang.png'">
							</image>
							<view class="scratch-award">
								<view class="scratch-award-a">
									<block v-if="isStart && !showmaskrule && !jxshow"><canvas @touchend="touchEnd"
											@touchmove="touchMove" @touchstart="touchStart" canvasId="scratch"
											class="scratch-canvas" :disableScroll="isScroll" id="scratch"
											style="position:absolute;left:0;z-index:888"></canvas></block>
									<view class="scratch-bg-text">
										<block v-if="award_name"><text class="scratch-text-1">{{jxmc||'刮开图层'}}</text>
										</block>
										<block v-if="(remaindaytimes > 0 && award_name)">
											<view @tap="onStart" class="scratch-bg-text-2">再刮一次</view>
										</block>
										<block v-if="(remaindaytimes <= 0 && award_name)">
											<view class="scratch-bg-text-3">再刮一次</view>
										</block>
									</view>
							</view>
								</view>
						</view>
					</view>

					<view class="border" v-if="info.use_type != 2">您今日还有 <text id="change">{{remaindaytimes}}</text>
						次抽奖机会</view>
					<view class="border2" v-if="info.use_type == 1 && info.usescore>0"><text
							v-if="!info.is_tr">每次</text><text v-else>本次</text>抽奖将消耗 <text>{{info.usescore}}</text>
						{{t('积分')}}，您共有 <text id="myscore">{{member.score}}</text> {{t('积分')}}</view>
					<view class="border2" v-if="info.use_type == 2 && info.usemoney>0">每次抽奖将消耗
						<text>{{t('余额')}}</text>{{info.usemoney}}元 ，您共有 <text id="mymoney">{{member.money}}</text> 元
					</view>
					<view @tap="goto" data-url='/pages/index/index' style="text-align:center;margin-top: 40rpx; line-height: 60rpx;color: #fff;"><text>返回首页</text></view>
					<!--滚动信息-->
					<view class="scroll">
						<view class="p"
							:style="'background-image:url('+pre_url+'/static/img/dzp/list.png);background-size:100% 100%;'">
						</view>
						<view class="sideBox">
							<swiper class="bd" autoplay="true" current="0" vertical="true" circular="true">
								<swiper-item v-for="(item, index) in zjlist" :key="index" class="sitem" v-if="index%2==0">
									<view>恭喜{{item.nickname}} 获得<text class="info">{{item.jxmc}}</text></view>
									<view v-if="zjlist[index+1]">恭喜{{zjlist[index+1].nickname}} 获得<text class="info">{{zjlist[index+1].jxmc}}</text></view>
								</swiper-item>
							</swiper>
						</view>
					</view>

					<view id="mask-rule" v-if="showmaskrule">
						<view class="box-rule">
							<view class="h2">活动规则说明</view>
							<view id="close-rule" @tap="changemaskrule"
								:style="'background-image:url('+pre_url+'/static/img/dzp/close.png);background-size:100%'">
							</view>
							<view class="con">
								<view class="text">
									<text decode="true" space="true">{{info.guize}}</text>
							</view>
								</view>
						</view>
					</view>
					
					<view v-if="showqrpopup" class="popup__container">
						<view class="popup__overlay" @tap.stop="hideqrmodal"></view>
						<view class="popup__modal" style="">
							<view class="popup__content">
								<view><image :data-url="info.qrcode" :src="info.qrcode" @tap="previewImage" ></image></view>
								<view class="txt" v-if="info.qrcode_tip">{{info.qrcode_tip}}</view>
							</view>
						</view>
					</view>
					
					
					<view id="mask-rule1" v-if="maskshow && !formdata">
						<view class="box-rule" style="height:640rpx">
							<view class="h2">请填写兑奖信息</view>
							<view id="close-rule1" :style="'background: no-repeat center / contain;background-image: url('+pre_url+'/static/img/dzp/close.png);'" @tap="changemaskshow"></view>
							<view class="con">
								<form class @submit="formsub">
								<view class="pay-form" style="margin-top:0.18rem">
									<view v-for="(item, idx) in info.formcontent" :key="idx" class="item flex-y-center">
										<view class="f1">{{item.val1}}：</view>
										<view class="f2 flex flex1">
											<block v-if="item.key=='input'">
												<input type="text" :name="'form' + idx" class="input" :placeholder="item.val2"></input>
											</block>
											<block v-if="item.key=='textarea'">
												<textarea :name="'form' + idx" class="textarea" :placeholder="item.val2"></textarea>
											</block>
											<block v-if="item.key=='radio'">
												<radio-group class="radio-group" :name="'form' + idx">
													<label v-for="(item1, index) in item.val2" :key="index">
															<radio :value="item1"></radio>{{item1}}
													</label>
												</radio-group>
											</block>
											<block v-if="item.key=='checkbox'">
												<checkbox-group :name="'form' + idx">
													<label v-for="(item1, index) in item.val2" :key="index">
														<checkbox :value="item1" class="xyy-zu"></checkbox>{{item1}}
													</label>
												</checkbox-group>
											</block>
											<block v-if="item.key=='switch'">
												<switch class="xyy-zu" value="1" :name="'form' + idx"></switch>
											</block>
											<block v-if="item.key=='selector'">
												<picker mode="selector" :name="'form' + idx" class="xyy-pic" :range="item.val2" @change="selector_editorBindPickerChange" :data-idx="idx" data-tplindex="0">
													<view class="picker" v-if="item.val2[selectIndex]"> {{item.val2[selectIndex]}}</view>
													<view v-else>请选择</view>
												</picker>
											</block>
											<block v-if="item.key=='time'">
												<picker mode="time" :name="'form' + idx" class="xyy-pic" @change="time_editorBindPickerChange" :data-idx="idx" data-tplindex="0">
													<view class="picker" v-if="picker_tmer">{{picker_tmer}}</view>
													<view v-else>选择时间</view>
												</picker>
											</block>
											<block v-if="item.key=='date'">
												<picker mode="date" :name="'form' + idx" class="xyy-pic" @change="date_editorBindPickerChange" :data-idx="idx" data-tplindex="0">
													<view class="picker" v-if="picker_date"> {{picker_date}}</view>
													<view v-else>选择日期</view>
												</picker>
											</block>
											<block v-if="item.key=='region'">
												<uni-data-picker style="color: #333;" class="flex1" :localdata="items" popup-title="请选择省市区"  @change="region_editorBindPickerChange"	:data-idx="idx" data-tplindex="0">
													<input type="text" :name="'form'+idx" :value="picker_region" placeholder="请选择省市区" placeholder-style="color:#fff;font-size:28rpx" style="border: none;font-size:28rpx;padding: 0rpx;color:#fff;"/>
												</uni-data-picker>
											</block>
										</view>
									</view>
									<view >
										<button class="subbtn" form-type="submit">确 定</button>
									</view>
								</view>
								</form>
							</view>
						</view>
					</view>
					
					
					
				</view>
			</view>
		</block>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
		<wxxieyi></wxxieyi>
	</view>
</template>

<script>
	var dot_inter, bool;
	var interval;
	var app = getApp();
	var windowWidth = uni.getSystemInfoSync().windowWidth;

	export default {
		data() {
			return {
				opt: {},
				loading: false,
				isload: false,
				menuindex: -1,

				pre_url: app.globalData.pre_url,
				isStart: 1,
				name: "",
				jxmc: "",
				detect: 1,
				error: "",
				info: {},
				member: {},
				jxarr: [],
				remaindaytimes: 0,
				remaintimes: 0,
				zjlist: [],
				register: 1,
				award_name: 0,
				jxshow: false,
				showmaskrule: false,
				latitude: "",
				longitude: "",
				r: 0,
				lastX: "",
				lastY: "",
				minX: "",
				minY: "",
				maxX: "",
				maxY: "",
				canvasWidth: "",
				canvasHeight: "",
				isScroll: false,
				award: 0,
				jx: "",
				windowWidth: 0,
				windowHeight: 0,
				backimg: "",
				backcolor: "",
				showqrpopup:false,
				showdoneqrcode:false,
				isinfo:false,
				maskshow: false,
				formdata: "",
				picker_tmer:'',
				picker_date:'',
				picker_region:'',
				items: [],
				showggk:true,
        after_tourl:'',//抽奖后跳转链接
			};
		},
		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			if(this.opt.code || app.globalData.choujiang_code){
				this.getcode();
			}
		},
		onReady: function() {
			var that = this;
			var res = uni.getSystemInfoSync();
			that.windowWidth = res.windowWidth;
			that.windowHeight = res.windowHeight;
			this.getdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
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
		onShareTimeline: function() {
			var that = this;
			var title = that.info.name;
			if (that.info.sharetitle) title = that.info.sharetitle;
			var sharepic = that.info.sharepic ? that.info.sharepic : '';
			var sharelink = that.info.sharelink ? that.info.sharelink : '';
			var sharedesc = that.info.sharedesc ? that.info.sharedesc : '';
			var sharewxdata = this._sharewx({
				title: title,
				desc: sharedesc,
				link: sharelink,
				pic: sharepic,
				callback: function() {
					that.sharecallback();
				}
			});
			var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
			return {
				title: sharewxdata.title,
				imageUrl: sharewxdata.imageUrl,
				query: query
			}
		},
		methods: {
			getdata: function() {
				var that = this;
				var id = that.opt.id || app.globalData.h5ToWxTempParam;
				var bid = that.opt.bid || app.globalData.maidan_bid;
				that.loading = true;
				app.get('ApiChoujiang/index', {
					id: id,
					bid:bid
				}, function(res) {
					that.loading = false;
					if (res.status == 0) {
						app.alert(res.msg);
						return;
					}
					res.info.formcontent = JSON.parse(res.info.formcontent);
					that.info = res.info;
					that.jxarr = res.jxarr;
					that.member = res.member;
					that.remaindaytimes = res.remaindaytimes;
					that.remaintimes = res.remaintimes;
					that.zjlist = res.zjlist;
					that.backimg = res.info.bgpic;
					that.backcolor = res.info.bgcolor;
					that.isinfo = res.isinfo;
          that.after_tourl = that.info.choujiang_after_tourl || '';
					if(	that.isinfo && res.record && res.record.formdata){
							that.formdata = res.record.formdata
					}
					uni.setNavigationBarTitle({
						title: res.info.name
					});
					if(that.info.qrcode){
						that.showqrpopup = true
					}
					that.onStart();
					if (that.info.fanwei == 1) {
						app.getLocation(function(res) {
							var latitude = res.latitude;
							var longitude = res.longitude;
							that.latitude = latitude;
							that.longitude = longitude;
						});
					}
					var title = that.info.name;
					if (that.info.sharetitle) title = that.info.sharetitle;
					var sharepic = that.info.sharepic ? that.info.sharepic : '';
					var sharelink = that.info.sharelink ? that.info.sharelink : '';
					var sharedesc = that.info.sharedesc ? that.info.sharedesc : '';
					that.loaded({
						title: title,
						desc: sharedesc,
						link: sharelink,
						pic: sharepic,
						callback: function() {
							that.sharecallback();
						}
					});
				});
			},
			getcode: function() {
				var that = this;
				app.get('ApiChoujiang/qrcode_addtimes', {
					choujiang_id: that.opt.id || app.globalData.h5ToWxTempParam,
					code: that.opt.code || app.globalData.choujiang_code,
				}, function(res) {
						if (res.status != 1 ) {
							app.error(res.msg);
						}
					that.getdata();
				});
			},
			
			showqrmodal:function(){
				if(this.info.qrcode){
					this.showqrpopup = true;
				}
				this.showdoneqrcode = true;
			},
			hideqrmodal:function(){
				this.showqrpopup = false;
			},
			sharecallback: function() {
				var that = this;
				app.post("ApiChoujiang/share", {
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
			changemaskrule: function() {
				this.showmaskrule = !this.showmaskrule;
			},
			init: function() {
				var windowWidth = this.windowWidth;
				var windowHeight = this.windowHeight;
				//var query = uni.createSelectorQuery();
				var that = this;
				that.award_name = 0;
				//query.select("#frame").boundingClientRect();
				//query.exec(function (res) {
				//	console.log(res)
				//var width = res[0].width;
				//var height = res[0].height;
				var width = windowWidth / 750 * 600;
				var height = windowWidth / 750 * 320;
				console.log(height)
				that.r = 16;
				that.lastX = "";
				that.lastY = "";
				that.minX = "";
				that.minY = "";
				that.maxX = "";
				that.maxY = "";
				that.canvasWidth = width;
				that.canvasHeight = height;
				var scratch = uni.createCanvasContext("scratch");
				scratch.setFillStyle('#D3D3D3');
				scratch.fillRect(0, 0, width, height);
				scratch.draw();
				console.log(scratch);

				//scratch.drawImage("@/static/img/scratch_hide_2.png", 0, 0, width, height);
				//scratch.draw();
				that.ctx = scratch;
				that.isStart = 1;
				that.isScroll = true;
				//});
			},
			onStart: function() {
				this.jxmc = '';
				this.isStart = 1;
				this.award = 0;
				this.award_name = 0;
				var that = this
				setTimeout(function() {
					that.init();
				}, 100)
			},
			drawRect: function(t, e) {
				var a = this.r / 2;
				var i = 0 < t - a ? t - a : 0;
				var s = 0 < e - a ? e - a : 0;
				if ("" !== this.minX) {
					this.minX = this.minX > i ? i : this.minX;
					this.minY = this.minY > s ? s : this.minY;
					this.maxX = this.maxX > i ? this.maxX : i;
					this.maxY = this.maxY > s ? this.maxY : s;
				} else {
					this.minX = i;
					this.minY = s;
					this.maxX = i;
					this.maxY = s;
				}
				this.lastX = i;
				this.lastY = s;
				[i, s, 2 * a];
			},
			clearArc: function(x, y, a) {
				var r = this.r;
				var ctx = this.ctx;
				var x2 = r - a;
				var y2 = Math.sqrt(r * r - x2 * x2);
				var c = x - x2;
				var n = y - y2;
				var d = 2 * x2;
				var p = 2 * y2;

				if (a <= r) {
					ctx.clearRect(c, n, d, p);
					a += 1;
					this.clearArc(x, y, a);
				}
			},
			touchStart: function(t) {
				var that=this
				if(that.isinfo){
					if(that.info.isbegin){
							app.error('活动未开始');return;
					}
					if(that.info.isend){
							app.error('活动已结束');return;
					}
					that.showggk=false
					that.maskshow = true;
					return;
				}
				this.award_name = 1
				if (this.isStart && this.error) {
					app.alert(this.error);
				}
			},
			touchMove: function(t) {
				if (this.isStart && !this.error) {
					this.drawRect(t.touches[0].x, t.touches[0].y);
					this.clearArc(t.touches[0].x, t.touches[0].y, 1);
					this.ctx.draw(true);
				}
			},
			changemaskshow: function () {
			  var that = this;
			  that.maskshow = !that.maskshow;
				that.showggk=true
			},
			touchEnd: function(t) {	

				
				if (this.isStart && !this.error) {
					var that = this;
					var canvasWidth = this.canvasWidth;
					var canvasHeight = this.canvasHeight;
					var minX = this.minX;
					var minY = this.minY;
					var maxX = this.maxX;
					var maxY = this.maxY;

					if (0.4 * canvasWidth < maxX - minX && 0.4 * canvasHeight < maxY - minY && this.detect) {
						that.detect = 0;
						app.post('ApiChoujiang/index', {
							id: that.info.id,
							bid:that.info.bid,
							op: 'getjx',
							longitude: that.longitude,
							latitude: that.latitude
						}, function(res) {
							that.info = res.info;
							if (res.status != 1) {
								app.alert(res.msg);
								that.onStart();
							} else {
								that.jxmc = res.jxmc;
								that.jx = res.jx;
								that.remaindaytimes = that.remaindaytimes - 1;
								setTimeout(function() {
                  that.detect = 1;
									that.isStart = 0;
									that.isScroll = true;
								}, 1000);

								if (res.jxtp == 2 && res.spdata) {
									//#ifdef MP-WEIXIN
									uni.sendBizRedPacket({
										timeStamp: res.spdata.timeStamp,
										// 支付签名时间戳，
										nonceStr: res.spdata.nonceStr,
										// 支付签名随机串，不长于 32 位
										package: res.spdata.package,
										//扩展字段，由商户传入
										signType: res.spdata.signType,
										// 签名方式，
										paySign: res.spdata.paySign,
										// 支付签名
										success: function(res) {
											console.log(res);
										},
										fail: function(res) {
											console.log(res);
										},
										complete: function(res) {
											console.log(res);
										}
									});
									//#endif
								}
							}
              //跳转链接
              if(that.after_tourl && that.after_tourl != ''){
                setTimeout(function () {
                  app.goto(that.after_tourl);
                }, 2000);
              }
						});
					}
				}
			},
			formsub: function (e) {
			  var that = this;
			  var subdata = e.detail.value;
			  var formcontent = that.info.formcontent;
			  var record = that.record;
			  var formdata = {};
			  for (var i = 0; i < formcontent.length; i++) {
			    //console.log(subdata['form' + i]);
			    if (formcontent[i].val3 == 1 && (subdata['form' + i] === '' || subdata['form' + i] === undefined || subdata['form' + i].length == 0)) {
			      app.alert(formcontent[i].val1 + ' 必填');
			      return;
			    }
			    if (formcontent[i].key == 'switch') {
			      if (subdata['form' + i] == false) {
			        subdata['form' + i] = '否';
			      } else {
			        subdata['form' + i] = '是';
			      }
			    }
			    if (formcontent[i].key == 'selector') {
			      subdata['form' + i] = formcontent[i].val2[subdata['form' + i]];
			    }
			    var nowformdata = {};
			    formdata[formcontent[i].val1] = subdata['form' + i];
			  }
			  app.post("ApiChoujiang/savememberinfo", {
					hid:that.info.id,
			    formcontent: formdata
			  }, function (res) {
			    if (res.status == 0) {
			      app.alert(res.msg);
			    } else {
			      that.changemaskshow();
			      app.success(res.msg);
			      that.getdata();
			    }
			  });
			}
		}
	};
</script>
<style scoped>
	.container{
		position: absolute;
		height: 100%;
		width: 100%;
	}
</style>
<style>
	.scratch-center {
		position: relative;
		padding-top: 380rpx;
	}

	.scratch-bg {
		padding-top: 30rpx;
		text-align: center;
		margin-bottom: 80rpx
	}

	.scratch-bg-1 {
		width: 640rpx;
		height: 360rpx;
	}

	.scratch-bg-2 {
		position: absolute;
		top: 20rpx;
		left: 50%;
		-webkit-transform: translate(-50%, -50%);
		transform: translate(-50%, 0);
		width: 600rpx;
		height: 320rpx;
	}

	.scratch-bg-3 {
		position: absolute;
		top: 150rpx;
		left: 50%;
		-webkit-transform: translate(-50%, -50%);
		transform: translate(-50%, 0);
		line-height: 80rpx;
		background: #f05525;
		border-radius: 40rpx;
		padding: 0 48rpx;
		color: #ffffff;
	}

	.scratch-award {
		position: absolute;
		top: 20rpx;
		left: 50%;
		-webkit-transform: translate(-50%, -50%);
		transform: translate(-50%, 0);
		width: 600rpx;
		height: 320rpx;
	}

	.scratch-canvas {
		z-index: 999;
		width: 600rpx;
		height: 320rpx;
	}

	.scratch-award-a {
		position: relative;
		top: 0;
		left: 0;
		width: 600rpx;
		height: 320rpx;
	}

	.scratch-bg-text {
		position: absolute;
		top: 60rpx;
		left: 50%;
		-webkit-transform: translate(-50%, -50%);
		transform: translate(-50%, 0);
	}

	.scratch-text-1 {
		font-size: 18pt;
		color: #f05525;
		overflow: hidden;
		text-overflow: ellipsis;
		display: -webkit-box;
		-webkit-line-clamp: 1;
		-webkit-box-orient: vertical;
		width: 410rpx;
	}

	.scratch-bg-text-2 {
		width: 400rpx;
		line-height: 80rpx;
		color: #ffffff;
		margin-top: 40rpx;
		border-radius: 40rpx;
		background: #f05525;
	}

	.scratch-bg-text-3 {
		width: 400rpx;
		line-height: 80rpx;
		color: #ffffff;
		margin-top: 40rpx;
		border-radius: 40rpx;
		background: #cdcdcd;
	}

	.wrap {
		width: 100%;
		height: 100%;
	}

	.header {
		width: 100%;
		padding: 22rpx 37rpx 0 37rpx;
		display: flex;
		justify-content: space-between
	}

	.rule,
	.my {
		width: 140rpx;
		height: 60rpx;
		border: 1px solid #f58d40;
		font-size: 30rpx;
		line-height: 60rpx;
		text-align: center;
		color: #f58d40;
		border-radius: 5rpx;
	}

	.title {
		width: 640rpx;
		height: 316rpx;
		margin: auto;
		margin-top: -60rpx;
	}

	/*次数*/
	.border {
		width: 380rpx;
		height: 64rpx;
		margin: 0 auto 25rpx;
		background: #fb3a13;
		font-size: 24rpx;
		line-height: 64rpx;
		text-align: center;
		color: #fff;
		border-radius: 45rpx
	}

	.border2 {
		width: 600rpx;
		height: 50rpx;
		margin: 0 auto;
		background: #dbaa83;
		font-size: 24rpx;
		line-height: 50rpx;
		text-align: center;
		color: #fff;
		border-radius: 10rpx
	}

	.scroll {
		width: 550rpx;
		height: 185rpx;
		margin: 75rpx auto 0 auto;
	}

	.scroll .p {
		width: 372rpx;
		height: 24rpx;
		margin: auto;
	}

	.sideBox {
		width: 100%;
		height: 100rpx;
		margin-top: 20rpx;
		padding: 10rpx 0 10rpx 0;
		background-color: rgba(255, 255, 255, 0.2);
		border-radius: 10rpx;
		overflow: hidden;
	}

	.sideBox .bd {
		width: 100%;
		height: 80rpx;
		overflow: hidden;
	}

	.sideBox .sitem {
		overflow: hidden;
		text-align: center;
		font-size: 20rpx;
		line-height: 40rpx;
		color: #fff;
	}

	/*规则弹窗*/
	#mask-rule,
	#mask {
		position: fixed;
		left: 0;
		top: 0;
		z-index: 999;
		width: 100%;
		height: 100%;
		background-color: rgba(0, 0, 0, 0.85);
	}

	#mask-rule .box-rule {
		position: relative;
		margin: 30% auto;
		padding-top: 40rpx;
		width: 90%;
		height: 675rpx;
		border-radius: 20rpx;
		background-color: #f58d40;
	}

	#mask-rule .box-rule .star {
		position: absolute;
		left: 50%;
		top: -100rpx;
		margin-left: -130rpx;
		width: 259rpx;
		height: 87rpx;
	}

	#mask-rule .box-rule .h2 {
		width: 100%;
		text-align: center;
		line-height: 34rpx;
		font-size: 34rpx;
		font-weight: normal;
		color: #fff;
	}

	#mask-rule #close-rule {
		position: absolute;
		right: 34rpx;
		top: 38rpx;
		width: 40rpx;
		height: 40rpx;
	}

	/*内容盒子*/
	#mask-rule .con {
		overflow: auto;
		position: relative;
		margin: 40rpx auto;
		padding-right: 15rpx;
		width: 580rpx;
		height: 82%;
		line-height: 48rpx;
		font-size: 26rpx;
		color: #fff;
	}

	#mask-rule .con .text {
		position: absolute;
		top: 0;
		left: 0;
		width: inherit;
		height: auto;
	}

	.back_img {
		position: fixed;
		width: 100%;
		top: 0;
	}
	.body{
		position: relative;
	}
	.popup__container{width: 80%; margin: 10% auto;}
	.popup__modal{position: fixed;top:400rpx;width: 660rpx;height: 720rpx;margin: 0 auto;border-radius: 20rpx;left: 46rpx;}
	.popup__content{text-align: center;padding: 20rpx 0 ;}
	.popup__content image{height: 600rpx;width: 600rpx;}
	
	
	
	
	#mask-rule1{position: fixed;top: 0;z-index: 10;width: 100%;max-width:640px;height: 100%;background-color: rgba(0, 0, 0, 0.85);}
	#mask-rule1 .box-rule {background-color: #f58d40;position: relative;margin: 30% auto;padding-top:40rpx;width: 90%;height:700rpx;border-radius:20rpx;}
	#mask-rule1 .box-rule .h2{width: 100%;text-align: center;line-height:34rpx;font-size: 34rpx;font-weight: normal;color: #fff;}
	#mask-rule1 #close-rule1{position: absolute;right:34rpx;top: 38rpx;width: 40rpx;height: 40rpx;}
	#mask-rule1 .con {overflow: auto;position: relative;margin: 40rpx auto;padding-right: 15rpx;width:580rpx;height: 82%;line-height: 48rpx;font-size: 26rpx;color: #fff;}
	#mask-rule1 .con .text {position: absolute;top: 0;left: 0;width: inherit;height: auto;}
	
	#mask-rule2{position: fixed;top: 0;z-index: 10;width: 100%;max-width:640px;height: 100%;background-color: rgba(0, 0, 0, 0.85);}
	#mask-rule2 .box-rule {background-color: #f58d40;position: relative;margin: 30% auto;padding-top:40rpx;width: 90%;height:700rpx;border-radius:20rpx;}
	#mask-rule2 .box-rule .h2{width: 100%;text-align: center;line-height:34rpx;font-size: 34rpx;font-weight: normal;color: #fff;}
	#mask-rule2 #close-rule2{position: absolute;right:34rpx;top: 38rpx;width: 40rpx;height: 40rpx;}
	#mask-rule2 .con {overflow: auto;position: relative;margin: 20rpx auto;padding-right: 15rpx;width:580rpx;height:90%;line-height: 48rpx;font-size: 26rpx;color: #fff;}
	#mask-rule2 .con .text {position: absolute;top: 0;left: 0;width: inherit;height: auto;}
	.pay-form .item{width:100%;padding:0 0 10px 0;color:#fff;}
	.pay-form .item:last-child{border-bottom:0}
	.pay-form .item .f1{width:80px;text-align:right;padding-right:10px}
	.pay-form .item .f2 input[type=text]{width:100%;height:35px;padding:2px 5px;border:1px solid #ddd;border-radius:2px}
	.pay-form .item .f2 textarea{width:100%;height:60px;padding:2px 5px;border:1px solid #ddd;border-radius:2px}
	.pay-form .item .f2 select{width:100%;height:35px;padding:2px 5px;border:1px solid #ddd;border-radius:2px}
	.pay-form .item .f2 label{height:35px;line-height:35px;} 
	.subbtn{width:100%;background:#fb3a13;font-size: 30rpx;padding:0 22rpx;border-radius: 8rpx;color:#FFF;margin-top: 30rpx;}
</style>
