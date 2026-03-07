<template>
<view class="container">
	<block v-if="isload">
		<view class="pageback" :style="{ background:backcolor==''?'#f58d40':backcolor}"></view>
		<view class="wrap" :style="'background-image:url(' + info.bgpic + ');background-size:100% 100%;'">
			<view class="header clearfix">
				<view class="rule" @tap="changemaskrule">活动规则</view>
				<view @tap="goto" :data-url="'myprize?hid=' + info.id" class="my">我的奖品</view>
			</view>
			<view class="title" :style="'background-image:url(' + info.banner + ');background-size:100% 100%;'"></view>
			<view class="canvas" :hidden="showmaskrule || jxshow || showqrpopup">
				<canvas canvas-id="roulette" style=" width: 650rpx; height: 650rpx;" v-if="showdzp">
					<!-- #ifndef MP-TOUTIAO -->
					<cover-image :src="pre_url + '/static/img/xydzp_start.png'" class="start" @tap="rollStart"></cover-image>
					<!-- #endif -->
				</canvas>
				<!-- #ifdef MP-TOUTIAO -->
				<image :src="pre_url + '/static/img/xydzp_start.png'" class="start" @tap="rollStart"></image>
				<!-- #endif -->
			</view>
			<view class="border" v-if="info.use_type != 2">您今日还有 <text id="change">{{remaindaytimes}}</text> 次抽奖机会</view>
			<view class="border2" v-if="info.use_type == 1 && info.usescore>0"><text v-if="!info.is_tr">每次</text><text v-else>本次</text>抽奖将消耗 <text>{{info.usescore}}</text> {{t('积分')}}，您共有 <text id="myscore">{{member.score}}</text> {{t('积分')}}</view>
			<view class="border2" v-if="info.use_type == 2 && info.usemoney>0">每次抽奖将消耗 <text>{{t('余额')}}</text>{{info.usemoney}}元 ，您共有 <text id="mymoney">{{member.money}}</text> 元</view>
            <!--滚动信息-->
			<view class="scroll">
				<view class="p" :style="'background-image: url('+pre_url+'/static/img/dzp/list.png);background-size:100% 100%;'"></view>
				<view class="sideBox">
					<swiper class="bd" autoplay="true" :indicator-dots="false" current="0" :vertical="true" circular="true">
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
					<view id="close-rule" :style="'background-image:url('+pre_url+'/static/img/dzp/close.png);background-size:100%'" @tap="changemaskrule"></view>
					<view class="con">
						<view class="text">
							<text decode="true" space="true">{{info.guize}}</text>
						</view>
					</view>
				</view>
			</view>
			<!--中奖提示-->
			<view id="mask" v-if="jxshow && jx>0">
				<view class="blin"></view>
				<view class="caidai" :style="'background-image: url('+pre_url+'/static/img/dzp/dianzhui.png);'"></view>
				<view class="winning reback" :style="'background:url(' + pre_url + '/static/img/dzp/bg2.png) no-repeat;background-size:100% 100%;'">
					<view class="p">
						<view>恭喜您抽中了</view>
						<view class="b" id="text1">{{jxmc}}</view>
					</view>
					<view @tap="changemask" class="btn">确定</view>
				</view>
			</view>
			<!--未中奖提示-->
			<view id="mask2" v-if="jxshow && jx==0">
				<view class="blin"></view>
				<view class="caidai" :style="'background-image: url('+pre_url+'/static/img/dzp/dianzhui.png);'"></view>
				<view class="winning reback" :style="'background:url(' + pre_url + '/static/img/dzp/bg3.png) no-repeat;background-size:100% 100%;'">
					<view class="p">
						<view class="b text2" id="text2">{{jxmc}}</view>
					</view>
					<view @tap="changemask" class="btn">确定</view>
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
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
</view>
</template>

<script>
var dot_inter, bool;
var app = getApp();
var windowWidth = uni.getWindowInfo().windowWidth;

export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			
			pre_url:app.globalData.pre_url,
      speed: 10,

      /**转盘速度 */
      speedDot: 1000,

      /**点切换速度 */
      dotColor: ['#ffffff', '#FCDF00'],
      dotColor_1: ['#ffffff', '#FCDF00'],
      dotColor_2: ['#FCDF00', '#ffffff'],
      jxshow: false,
      showmaskrule: false,
			info:{},
			member:{},
			remaindaytimes:0,
			remaintimes:0,
			zjlist:[],
      jxarr: "",
      latitude: "",
      longitude: "",
      angelTo: "",
      jxmc: "",
      jx: "",
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
			showdzp:true,
      after_tourl:'',//抽奖后跳转链接
    };
  },

  onLoad: function (opt) {
		var that=this
		this.opt = app.getopts(opt);
		if(this.opt.code || app.globalData.choujiang_code){
			this.getcode();
		}else{
			this.getdata();
		}		
		
		app.get('ApiIndex/getCustom',{}, function (customs) {
			var url = app.globalData.pre_url+'/static/area.json';
			uni.request({
				url: app.globalData.pre_url+'/static/area.json',
				data: {},
				method: 'GET',
				header: { 'content-type': 'application/json' },
				success: function(res2) {
					that.items = res2.data
				}
			});
		});
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onShareAppMessage: function () {
		var that = this;
		var title = that.info.name;
		if (that.info.sharetitle) title = that.info.sharetitle;
		var sharepic = that.info.sharepic ? that.info.sharepic : '';
		var sharelink = that.info.sharelink ? that.info.sharelink : '';
		var sharedesc = that.info.sharedesc ? that.info.sharedesc : '';
		return this._sharewx({title:title,desc:sharedesc,link:sharelink,pic:sharepic,callback:function(){that.sharecallback();}});
  },
	onShareTimeline:function(){
		var that = this;
		var title = that.info.name;
		if (that.info.sharetitle) title = that.info.sharetitle;
		var sharepic = that.info.sharepic ? that.info.sharepic : '';
		var sharelink = that.info.sharelink ? that.info.sharelink : '';
		var sharedesc = that.info.sharedesc ? that.info.sharedesc : '';
		var sharewxdata = this._sharewx({title:title,desc:sharedesc,link:sharelink,pic:sharepic,callback:function(){that.sharecallback();}});
		var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
		return {
			title: sharewxdata.title,
			imageUrl: sharewxdata.imageUrl,
			query: query
		}
	},
  methods: {
		getdata: function () {
			var that = this;
			var id = that.opt.id || app.globalData.h5ToWxTempParam;
			var bid = that.opt.bid || app.globalData.maidan_bid;
			that.loading = true;
			app.get('ApiChoujiang/index', {id: id,bid:bid}, function (res) {
				that.loading = false;
				if(res.status == 0){
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
				that.downloadFile(res.jxarr, 0);
				if (that.info.fanwei == 1) {
					app.getLocation(function (res) {
						var latitude = res.latitude;
						var longitude = res.longitude;
						that.latitude = latitude;
						that.longitude = longitude;
						console.log(longitude);
					});
				}
				var title = that.info.name;
				if (that.info.sharetitle) title = that.info.sharetitle;
				var sharepic = that.info.sharepic ? that.info.sharepic : '';
				var sharelink = that.info.sharelink ? that.info.sharelink : '';
				var sharedesc = that.info.sharedesc ? that.info.sharedesc : '';
				that.loaded({title:title,desc:sharedesc,link:sharelink,pic:sharepic,callback:function(){that.sharecallback();}});
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
		sharecallback:function(){
			var that = this;
			app.post("ApiChoujiang/share", {hid: that.info.id}, function (res) {
				if (res.status == 1) {
					setTimeout(function () {
						that.getdata();
					}, 1000);
				} else if (res.status == 0) {//dialog(res.msg);
				}
			});
		},
    downloadFile: function (jxarr, i) {
      var that = this;
      if (jxarr[i].pic) {
        uni.downloadFile({
          url: jxarr[i].pic,
          success: function (res) {
            if (res.tempFilePath) {
              jxarr[i].pic = res.tempFilePath;
              that.jxarr = jxarr;
            }
          },
          complete: function () {
            if (jxarr.length > i + 1) {
              that.downloadFile(jxarr, i + 1);
            } else {
              that.dotStart();
            }
          }
        });
      } else {
        if (jxarr.length > i + 1) {
          that.downloadFile(jxarr, i + 1);
        } else {
          that.dotStart();
        }
      }
    },
    changemaskrule: function () {
      this.showmaskrule = !this.showmaskrule
    },
    changemask: function () {
      this.jxshow = !this.jxshow;
      if(this.after_tourl && this.after_tourl != ''){
        app.goto(this.after_tourl);
        return;
      }
      this.getdata();
    },
		changemaskshow: function () {
		  var that = this;
		  that.maskshow = !that.maskshow;
			that.showdzp=true
		},
		time_editorBindPickerChange:function(e){
			this.picker_tmer = e.detail.value
		},
		selector_editorBindPickerChange:function(e){
			this.selectIndex = e.detail.value;
		},
		date_editorBindPickerChange:function(e){
			this.picker_date = e.detail.value;
		},
		region_editorBindPickerChange:function(e){
			const value = e.detail.value
			this.picker_region = value[0].text + ',' + value[1].text + ',' + value[2].text;
		},
    rollStart: function () {
      var that = this;
			if(that.isinfo){
				if(that.info.isbegin){
						app.error('活动未开始');return;
				}
				if(that.info.isend){
						app.error('活动已结束');return;
				}
				that.showdzp=false
				that.maskshow = true;return;
			}
      if (bool) return; // 如果在执行就退出
      bool = true; // 标志为 在执行
      app.post('ApiChoujiang/index', {id: that.info.id,bid:that.info.bid,op: 'getjx',longitude: that.longitude,latitude: that.latitude}, function (res) {
        if (res.status != 1) {
          // app.alert(res.msg);
					uni.showModal({
						title: '提示信息',
						showCancel: false,
						content: res.msg,
						success: function (res) {}
					});
          bool = false;
          return;
        } else {
          //奖品数量等于10,指针落在对应奖品区域的中心角度[252, 216, 180, 144, 108, 72, 36, 360, 324, 288]
          var angel = 360 - 360 / res.jxcount * res.jxindex;
        }
        angel += 360 * 6;
				// #ifdef H5
				var baseStep = 30; // 起始滚动速度
				// #endif
        // #ifndef H5
        var baseStep = 140; // 起始滚动速度
        // #endif
        var baseSpeed = 0.3;
        var count = 1;
        var timer = setInterval(function () {
          that.angelTo = count
          clearInterval(dot_inter);
          that.drawCanvas();

          if (count == angel) {
            console.log('完毕');
						bool = false;
            clearInterval(timer);
						// #ifdef MP-TOUTIAO
						setTimeout(() => {
							that.jxshow = true;
							that.jxmc = res.jxmc;
							that.jx = res.jx;
							that.dotStart();
						},3000)
						// #endif
						// #ifndef MP-TOUTIAO
							that.jxshow = true;
							that.jxmc = res.jxmc;
							that.jx = res.jx;
							that.dotStart();
						// #endif
            if (res.jxtp == 2 && res.spdata) {
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
                success: function (res) {
                  console.log(res);
                },
                fail: function (res) {
                  console.log(res);
                },
                complete: function (res) {
                  console.log(res);
                }
              });
            }
          }

          count = count + baseStep * ((angel - count) / angel > baseSpeed ? baseSpeed : (angel - count) / angel);
          // #ifdef H5
          if (angel - count < 0.5) {
            count = angel;
          }
          // #endif
					// #ifndef H5
					if (angel - count < 1) {
					  count = angel;
					}
					// #endif
        }, that.speed);
      });
    },
    drawCanvas: function () {
      var that = this;
      var ctx = uni.createCanvasContext('roulette', this);
      var angelTo = this.angelTo || 0;
      var width = windowWidth / 750 * 650;
      var height = width;
      var x = width / 2;
      var y = width / 2;
      var num = that.jxarr.length;
      ctx.translate(x, y);
      ctx.clearRect(-width, -height, width, height);
      ctx.rotate(angelTo * Math.PI / 180); // 画外圆

      ctx.beginPath();
			ctx.setLineWidth(width / 2)
			ctx.setStrokeStyle('#FFF7C5')
      ctx.arc(0, 0, width / 4, 0, 2 * Math.PI);
      ctx.stroke();
      ctx.beginPath();
			ctx.setLineWidth(1)
			ctx.setStrokeStyle('#D9644F')
      ctx.arc(0, 0, width / 2 - 1, 0, 2 * Math.PI);
      ctx.stroke();
      ctx.beginPath();
			ctx.setLineWidth(2)
			ctx.setStrokeStyle('#FDF28C')
      ctx.arc(0, 0, width / 2 - 3, 0, 2 * Math.PI);
      ctx.stroke();
      ctx.beginPath();
			ctx.setLineWidth(15)
			ctx.setStrokeStyle('#F8645E')
      ctx.arc(0, 0, width / 2 - 14, 0, 2 * Math.PI);
      ctx.stroke(); // 装饰点

      var dotColor = that.dotColor;
      var startAngel = 0;
      for (var i = 0; i < 26; i++) {
        ctx.beginPath();
        var radius = width / 2 - 14;
        var xr = radius * Math.cos(startAngel);
        var yr = radius * Math.sin(startAngel);
				ctx.setFillStyle(dotColor[i % dotColor.length])
        ctx.arc(xr, yr, 4, 0, 2 * Math.PI);
        ctx.fill();
        startAngel += 360 / 26 * (Math.PI / 180);
      }

      var jxarr = that.jxarr;
			
      ctx.rotate(-(360 / num) * Math.PI / 180);

      for (var i = 0; i < num; i++) {
        ctx.rotate(360 / num * Math.PI / 180); //ctx.setFontSize(14)

        ctx.font = 'normal bold 14px Arial';
				ctx.setFillStyle('#e75228');
				ctx.setTextAlign("center");
        ctx.fillText(jxarr[i].mc?jxarr[i].mc:'', 0, -(width / 2 - 50));
        ctx.drawImage(jxarr[i].pic, -20, -(width / 2 - 70), 40, 40); //ctx.restore();
      }

      if (num % 2 == 0) {
        ctx.rotate(180 / num * Math.PI / 180);
      }

      for (var i = 0; i < num; i++) {
        //ctx.save();
        ctx.rotate(360 / num * Math.PI / 180);
        ctx.beginPath();
				ctx.setLineWidth(2)
        ctx.moveTo(0, 0);
        ctx.lineTo(0, width / 2 - 20);
        ctx.setStrokeStyle('#f6625c');
        ctx.stroke();
      }

      ctx.draw();
    },
    dotStart: function () {
      var that = this;
      var times = 0;
			clearInterval(dot_inter);
      that.drawCanvas();
      dot_inter = setInterval(function () {
        if (times % 2) {
          var dotColor = that.dotColor_1;
        } else {
          var dotColor = that.dotColor_2;
        }

        times++;
        that.dotColor = dotColor;
        that.drawCanvas();
      }, that.speedDot);
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
<style>
.canvas{position: relative;}
.canvas{width: 650rpx;height: 650rpx;margin:0 auto;margin-bottom:20rpx}
.canvas>canvas{z-index: 0;}
.start{height: 200rpx;width: 160rpx;position: absolute;top:50%;left: 50%;margin-left: -80rpx;margin-top: -110rpx;z-index: 2;}

.wrap {width:100%;height:100%;}
.header{width:100%;padding:22rpx 37rpx 0 37rpx;display:flex;justify-content:space-between}
.rule,.my{width:140rpx;height:60rpx;border: 1px solid #f58d40;font-size:30rpx;line-height:60rpx;text-align: center;color: #f58d40;border-radius:5rpx;}
.title {width:640rpx;height:316rpx;margin: auto;margin-top:-60rpx;}

/*次数*/
.border {width: 380rpx;height:64rpx;margin: 0 auto 25rpx;background:#fb3a13;font-size:24rpx;line-height:64rpx;text-align: center;color: #fff;border-radius:45rpx}
.border2 {width:600rpx;height:50rpx;margin: 0 auto;background:#dbaa83;font-size:24rpx;line-height:50rpx;text-align: center;color: #fff;border-radius:10rpx}
.scroll {width:550rpx;height:185rpx;margin:75rpx auto 0 auto;}
.scroll .p {width: 372rpx;height:24rpx;margin: auto;}
.sideBox{  width: 100%;height:100rpx;margin-top:20rpx;padding: 10rpx 0 10rpx 0;background-color: rgba(255, 255, 255, 0.2);border-radius:10rpx;overflow:hidden;}
.sideBox .bd {width: 100%;height:80rpx;overflow:hidden;}
.sideBox .sitem{overflow:hidden;text-align: center;font-size:20rpx;line-height:40rpx;color: #fff;}

/*规则弹窗*/
#mask-rule,#mask {position: fixed;left: 0;top: 0;z-index: 999;width: 100%;height: 100%;background-color: rgba(0, 0, 0, 0.85);}
#mask-rule .box-rule {position: relative;margin: 30% auto;padding-top: 40rpx;width: 90%;height: 675rpx;border-radius: 20rpx;background-color: #f58d40;}
#mask-rule .box-rule .star {position: absolute;left: 50%;top: -100rpx;margin-left: -130rpx;width: 259rpx;height:87rpx;}
#mask-rule .box-rule .h2 {width: 100%;text-align: center;line-height: 34rpx;font-size: 34rpx;font-weight: normal;color: #fff;}
#mask-rule #close-rule {position: absolute;right: 34rpx;top: 38rpx;width: 40rpx;height: 40rpx;}
/*内容盒子*/
#mask-rule .con {overflow: auto;position: relative;margin: 40rpx auto;padding-right: 15rpx;width: 580rpx;height: 82%;line-height: 48rpx;font-size: 26rpx;color: #fff;}
#mask-rule .con .text {position: absolute;top: 0;left: 0;width: inherit;height: auto;}
/*中奖提示*/
#mask,#mask2{position: fixed;left: 0;top: 0;z-index: 999;width: 100%;height: 100%;background-color: rgba(0, 0, 0, 0.85);}
#mask .blin,#mask2 .blin {width: 100%;height: 100%;-o-animation: circle 10s linear infinite;-ms-animation: circle 10s linear infinite;-moz-animation: circle 10s linear infinite;-webkit-animation: circle 10s linear infinite;animation: circle 10s linear infinite;}
@keyframes circle {0% {-o-transform: rotate(0deg);-ms-transform: rotate(0deg);-moz-transform: rotate(0deg);-webkit-transform: rotate(0deg);transform: rotate(0deg);}100% {-o-transform: rotate(360deg);-ms-transform: rotate(360deg);-moz-transform: rotate(360deg);-webkit-transform: rotate(360deg);transform: rotate(360deg);}}
#mask .caidai,#mask2 .caidai{position: absolute;left: 0;top: 0;z-index: 1;width: 100%;height: 100%;-o-transform: scale(1.2);-ms-transform: scale(1.2);-moz-transform: scale(1.2);-webkit-transform: scale(1.2);transform: scale(1.2);}
#mask .winning,#mask2 .winning {position: absolute;left: 50%;top: 50%;z-index: 1;width: 675rpx;height: 600rpx;margin: -300rpx 0 0 -338rpx;-o-transform: scale(0.1);-ms-transform: scale(0.1);-moz-transform: scale(0.1);-webkit-transform: scale(0.1);transform: scale(0.1);}
#mask .reback,#mask2 .reback{-o-animation: reback .5s linear forwards;-ms-animation: reback .5s linear forwards;-moz-animation: reback .5s linear forwards;-webkit-animation: reback .5s linear forwards;animation: reback .5s linear forwards;}
@keyframes reback {100% {-o-transform: scale(1);-ms-transform: scale(1);-moz-transform: scale(1);-webkit-transform: scale(1);transform: scale(1);}}
.winning .p{ position: absolute;left: 50%;top: 30%;width:80%;margin-left:-40%;color:#FFF;font-size:52rpx;text-align:center;}
.winning .b{ font-size:44rpx;}
.winning .btn {position: absolute;left: 50%;bottom: 15%;z-index: 2;width: 364rpx;height: 71rpx;line-height: 71rpx;margin-left: -182rpx;background-color:#ffee8d;border-radius:45rpx;-webkit-border-radius:45rpx;color:#f62a39;text-align:center;font-size:45rpx;
}
.winning .text2{padding-top: 70rpx;}
@keyframes shake {50% {-o-transform: rotate(-5deg);-ms-transform: rotate(-5deg);-moz-transform: rotate(-5deg);-webkit-transform: rotate(-5deg);transform: rotate(-5deg);}100% {-o-transform: rotate(5deg);-ms-transform: rotate(5deg);-moz-transform: rotate(5deg);-webkit-transform: rotate(5deg);transform: rotate(5deg);}}
@keyframes fadein {100% {opacity: 1;-o-transform: rotate(360deg);-ms-transform: rotate(360deg);-moz-transform: rotate(360deg);-webkit-transform: rotate(360deg);transform: rotate(360deg);}}
.pageback{position: fixed;width: 100%;height: 100%;top: 0;left: 0;z-index: -1;}

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