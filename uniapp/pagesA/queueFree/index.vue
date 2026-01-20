<template>
<view class="container">
	<block v-if="isload">
		<view class="tongji flex-y-center "   :style="{background:color1?color1:t('color1')}">
			<view>
				<view class="price">{{set.give_money}}</view>
				<view>{{jiangli_text}}</view>
			</view>
			<view v-if="paidui_money_show">
				<view class="price">{{set.sy_money}}</view>
				<view>{{paidui_text}}</view>
			</view>
      <view v-if="set.activity_rules" @tap="changemaskrule" style="position: absolute;right: 14rpx;top: 10rpx;font-size: 26rpx;">
        活动规则
      </view>
		</view>
		<view class="tab tab_top" >
			<dd-tab :itemdata="itemlabel" :itemst="itemvalue" :st="st"  @changetab="changetab"></dd-tab>
		</view>
		<view class="height130" ></view>
		<view class="order-content ">
			<block v-if="st ==2">
				<view class="order-box" v-for="(item1, index) in datalist" :key="index">
					<view class="head">
						<view class="f1">订单号：{{item1.ordernum}}</view>
						<view class="flex1"></view>
					</view>
					<view class="content" style="border-bottom:none">
						<view class="detail">
							<text class="t1">商户名称：{{item1.bname}}</text>
						</view>
						<view class="detail">
							拆单金额：<text class="t1" :style="'color:'+t('color1')">{{item1.money}}</text>
						</view>
						<view class="detail">
							排队金额：<text class="t1" :style="'color:'+t('color1')">{{item1.free_money}}</text>
						</view>
						<view class="detail">
							释放所需单数：<text class="t1" :style="'color:'+t('color1')">{{item1.order_count}}单</text>
						</view>
						
						<view class="detail">
							<text class="t1">创建时间：{{dateFormat(item1.createtime)}}</text>
						</view>
					</view>
					
				</view>
			</block>
			<block v-else v-for="(item, index) in datalist" :key="index" >
				<view class="order-box">	
					<view class="head">
						<view class="f1">订单号：{{item.ordernum}}</view>
						<view class="flex1"></view>
						<text  class="st0">{{item.statusLabel}}</text>
					</view>
					<view class="content" style="border-bottom:none">
						<view class="detail">
							<text class="t1">商户名称：{{item.bname}}</text>
						</view>
						<view class="detail" v-if="item.title">
							排队名称：<text class="t1" :style="'color:'+t('color1')">{{item.title}}</text>
						</view>
						<view class="detail">
							排队金额：<text class="t1" :style="'color:'+t('color1')">{{item.money}}</text>
						</view>
						<view class="detail" v-if="set.back_money_type ==1">
							路上金额：<text class="t1" :style="'color:'+t('color1')">{{item.addup_money}}</text> 
						</view>
						<view class="detail" v-if="!set.back_money_type || (set.back_money_type && item.status ==1 )">
							已获金额：<text class="t1" :style="'color:'+t('color1')">{{item.money_give}}</text> 
							
							<!-- <text v-if="item.status==0 && set.quit_wxhb == 1 && item.money_quit_hb > 0" @tap.stop="quitWithHb" :data-money="item.money_quit_hb" :data-id="item.id" :data-index="index" :style="'color:'+t('color1')+';margin-left: 30rpx;'">退出排队抽红包</text> -->
						</view>
						<view class="detail" v-if="item.queue_no">
							当前排名：<text class="t1" :style="'color:'+t('color1')">{{item.queue_noLabel}}</text>
						</view>
						<view class="detail">
							<text class="t1">排队时间：{{item.createtimeFormat}}</text>
						</view>
					</view>
					<view class="operate flex-y-center flex-x-bottom ">
						<view class="button" :style="{background:t('color1'),color:'#fff',border:'none'}" v-if="item.status==0 && set.quit_wxhb == 1 && item.money_quit_hb > 0" @tap.stop="quitWithHb" :data-money="item.money_quit_hb" :data-id="item.id" :data-index="index" >{{quit_wxhb_text}}</view>
						<view class="button" :style="{background:t('color1'),color:'#fff',border:'none'}"  v-if="item.status==0 && set.quit_score == 1 " @tap.stop="quitScore"  :data-id="item.id" :data-givescore="item.give_score" :data-index="index" >退出返{{t('积分')}}</view>
						<view class="button" :style="{background:t('color1'),color:'#fff',border:'none'}" v-if="item.status==0 && set.quit_random_score == 1 " @tap.stop="quitRandomScore" :data-money="item.money_quit_hb" :data-id="item.id" :data-index="index" >{{quit_random_score_text}}</view>
						<view class="button" :style="{background:t('color1'),color:'#fff',border:'none'}" v-if="item.status==0 && set.quit_back_price_st == 1 && item.quit_back_price_st==1" @tap.stop="quitBackPrice"   :data-backprice="item.give_back_price" :data-id="item.id" :data-index="index" >退出转每日补贴</view>
							<view class="button" :style="{background:t('color1'),color:'#fff',border:'none'}"  v-if="item.status==0 && set.quit_freeze_money == 1 " @tap.stop="quitFreezeMoney"  :data-id="item.id" :data-givemoney="item.give_freeze_money" :data-index="index" >退出返{{t('冻结资金')}}</view>
					</view>
				</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
	</block>
	<!-- 红包区域 -->
	<view class="page-view-hongbao" v-if="prizeVisible" @touchmove.stop.prevent="() => {}" @mousewheel.prevent>
		<!-- 三个红包 -->
		<view class="hongbao-view">
			<view class="wrap">
				<view class="envelope" @tap="receive">
					<image :src="`${pre_url}/static/img/envelope.png`" mode="aspectFill" class="cover" />
					<image :src="`${pre_url}/static/img/btn.png`" mode="aspectFill" class="btn" />
				</view>
			</view>
			<view class="wrap">
				<view class="envelope" @tap="receive">
					<image :src="`${pre_url}/static/img/envelope.png`" mode="aspectFill" class="cover" />
					<image :src="`${pre_url}/static/img/btn.png`" mode="aspectFill" class="btn" />
				</view>
			</view>
			<view class="wrap">
				<view class="envelope" @tap="receive">
					<image :src="`${pre_url}/static/img/envelope.png`" mode="aspectFill" class="cover" />
					<image :src="`${pre_url}/static/img/btn.png`" mode="aspectFill" class="btn" />
				</view>
			</view>
			<!-- 关闭弹窗按钮  -->
			<view class="hongbao-view-close" @click="hbclose">
				<image :src="pre_url+'/static/img/close2.png'"></image>
			</view>
		</view>
		<uni-popup ref="popup" @change="">
			<view class="cl-popup">
				<view class="main">
					<image :src="`${pre_url}/static/img/popup-top.png`" mode="aspectFill" class="top" />
					<image :src="`${pre_url}/static/img/popup-icon.png`" mode="aspectFill" class="icon" />
					<image :src="`${pre_url}/static/img/popup-bottom.png`" mode="aspectFill" class="bottom" />
					<view class="content">
						<view class="price">
							<text class="num">{{hbmoney}}</text>
							<text class="unit">元</text>
						</view>
						<!-- 标题 -->
						<view class="title"> {{hbtext}} </view>
						<!-- 领取按钮 -->
						<view class="cl-button"  @tap="hbsuccess">
							<text>确定</text>
						</view>
					</view>
				</view>
			</view>
		</uni-popup>
	</view>
	
	
	<view class="page-view-hongbao" v-if="randomScoreVisible" @touchmove.stop.prevent="() => {}" @mousewheel.prevent>
		<!-- 三个宝箱 -->
		<view class="hongbao-view" style="top: 15%;">
			<view class="wrap" style="width: 33%;height: 310rpx;" v-for="(item,index) in 3">
				<view class="envelope" @tap="receiveRandomScore">
					<image :src="`${pre_url}/static/img/queuefree/baoxiang.gif`" mode="aspectFill" class="cover" />
				</view>
			</view>
			<!-- 关闭弹窗按钮  -->
			<view class="hongbao-view-close" style="bottom: -150rpx;" @click="randomScoreclose">
				<image :src="pre_url+'/static/img/close2.png'"></image>
			</view>
		</view>
		<uni-popup ref="popup" @change="">
			<view class="cl-popup">
				<view class="main">
					<image :src="`${pre_url}/static/img/popup-top.png`" mode="aspectFill" class="top" />
					<image :src="`${pre_url}/static/img/popup-icon.png`" mode="aspectFill" class="icon" />
					<image :src="`${pre_url}/static/img/popup-bottom.png`" mode="aspectFill" class="bottom" />
					<view class="content">
						<view class="price">
							<text class="num">{{randomScore}}</text>
							<text class="unit">{{t('积分')}}</text>
						</view>
						<!-- 标题 -->
						<view class="title"> 奖励将发放到个人{{t('积分')}}账户 </view>
						<!-- 领取按钮 -->
						<view class="cl-button" @tap="randomScoreclose">
							<text >确定</text>
						</view>
					</view>
				</view>
			</view>
		</uni-popup>
	</view>
	<!-- 退出到待返金额 -->
	<view class="page-view-hongbao" v-if="backPriceVisible" @touchmove.stop.prevent="() => {}" @mousewheel.prevent>
		<!-- 三个红包 -->
		<view class="hongbao-view" >
			<view class="wrap" v-for="(item,index) in 3">
				<view class="envelope" @tap="receiveBackPrice" >
					<image :src="`${pre_url}/static/img/envelope.png`" mode="aspectFill" class="cover" />
					<image :src="`${pre_url}/static/img/btn.png`" mode="aspectFill" class="btn" />
				</view>
			</view>
		
			<!-- 关闭弹窗按钮  -->
			<view class="hongbao-view-close" @click="quitBackpriceclose">
				<image :src="pre_url+'/static/img/close2.png'"></image>
			</view>
		</view>
		<uni-popup ref="popup" @change="">
			<view class="cl-popup">
				<view class="main">
					<image :src="`${pre_url}/static/img/popup-top.png`" mode="aspectFill" class="top" />
					<image :src="`${pre_url}/static/img/popup-icon.png`" mode="aspectFill" class="icon" />
					<image :src="`${pre_url}/static/img/popup-bottom.png`" mode="aspectFill" class="bottom" />
					<view class="content">
						<view class="price">
							<text class="num">{{give_back_price}}</text>
							<text class="unit"</text>
						</view>
						<!-- 标题 -->
						<view class="title"> 奖励将发放到个人待返金额中 </view>
						<!-- 领取按钮 -->
						<view class="cl-button" @tap="quitBackpriceclose">
							<text >确定</text>
						</view>
					</view>
				</view>
			</view>
		</uni-popup>
	</view>
	<uni-popup ref="popupMiandan" type='center'>
		<view class="popup-miandan-content">
			<view class="popup-miandan-price flex">
				<view class="price-num-text popup-color-text">{{set.give_money}}</view>
				<view class="right-text popup-color-text">元</view>
			</view>
			<view class="popup-leiji-miandan popup-color-text">累计免单金额</view>
			<view class="popup-guishu flex-col">
				<view class="top-text">{{set.popup_text1}}</view>
				<view class="bottom-text">{{set.popup_text2}}</view>
			</view>
			<image :src="`${pre_url}/static/img/miandanpopupbg.png`" mode="widthFix" />
		</view>
		<!-- 关闭弹窗按钮  -->
		<view class="popupMiandan-close" @click="popupMiandanclose">
			<image :src="pre_url+'/static/img/close2.png'"></image>
		</view>
	</uni-popup>
  <!-- 活动规则弹框  -->
  <view id="mask-rule" v-if="showmaskrule">
    <view class="box-rule">
      <view class="h2">活动规则</view>
      <view id="close-rule" @tap="changemaskrule"
            :style="'background-image:url('+pre_url+'/static/img/dzp/close.png);background-size:100%'">
      </view>
      <view class="con">
        <view class="text">
          <text decode="true" space="true">{{set.activity_rules}}</text>
        </view>
      </view>
    </view>
  </view>
	<fireworks :fireworksShow='fireworksShow'></fireworks>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();
import fireworks from '../fireworks/fireworks.vue'
export default {
	components: {
		fireworks
	},
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,

      st: '0',
      datalist: [],
			set:{},
			hbmoney:0,
			hbtext:'奖励将发送到微信零钱或余额中',
			tempid:0,
			tempindex:0,
      pagenum: 1,
      nomore: false,
      nodata: false,
			keyword:'',
			prizeVisible: false,//是否展示红包
			pre_url:app.globalData.pre_url,
			randomScoreVisible:false,//随机积分弹窗
			randomScore:0,//获得的积分
			itemlabel:['排队中','已完成'],
			itemvalue:['0','1'],
			backPriceVisible:false,//抽待返金额
			give_back_price:0,//待返金额
			paidui_text:'排队中',
			paidui_money_show:1,
			jiangli_text:'累计奖励',
			fireworksShow:false,
			quit_random_score_text:'退出抽积分',
			quit_wxhb_text:'退出返红包',
			isOnload:false,
      showmaskrule:false,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.isOnload = true;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.isOnload = true;
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata(true);
    }
  },
	onNavigationBarSearchInputConfirmed:function(e){
		this.searchConfirm({detail:{value:e.text}});
	},
	onHide(){
		this.fireworksShow = false;
	},
  methods: {
		// 红包关闭
		hbclose(){
			this.prizeVisible = false;
		},
		hbshow(){
			this.prizeVisible = true;
		},
		hbsuccess(){
			this.prizeVisible = false;
			let datalist = this.datalist;
			let tempindex = this.tempindex;
			datalist.splice(tempindex, 1);
			this.tempindex = datalist;
		},
		// 领取红包
		receive() {
			let that = this;
			that.loading = true;
			app.post('ApiQueueFree/quitHb', {id: that.tempid}, function (res) {
				that.loading = false;
			  if(res.status == 0){
					app.alert(res.msg);
					that.hbclose()
					return;
				}
				that.hbmoney = res.data.random_money;
				that.$refs.popup.open('center');
			});
		},
		quitWithHb(e) {
			let that = this;
			let tempid = e.currentTarget.dataset.id;
			let money = e.currentTarget.dataset.money;
			that.tempid = tempid;
			that.tempindex = e.currentTarget.dataset.index;
			that.hbshow();
		},
		//待金额
		// quitBackPrice(e) {
		// 	let that = this;
		// 	let tempid = e.currentTarget.dataset.id;
		// 	that.tempid = tempid;
		// 	let money = e.currentTarget.dataset.money;
		// 	that.backprice= money;
		// 	that.backPriceVisible = true;
		// },
		//待返金额
		quitBackPrice(e){
			let that = this;
			var givebackmoney = e.currentTarget.dataset.backprice;
			var msg = '退出排队后您将获得'+givebackmoney+'元待补金额，确定退出吗？';
			var id = e.currentTarget.dataset.id;
			app.confirm(msg, function () {
				app.showLoading('提交中');
				app.post('ApiQueueFree/quitBackPrice', {id: id}, function (data) {
					app.showLoading(false);
					if(data.status ==0){
						app.error(res.msg);
						return;
					}
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000);
				});
			});
		},
		receiveBackPrice(){
			var that = this;
			var tempid = that.tempid;
			that.loading = true;
			app.post('ApiQueueFree/quitBackPrice', {id:tempid}, function (res) {
				that.loading = false;
				that.give_back_price = res.data.give_back_price;
				that.$refs.popup.open('center');
			});
		},
		quitBackpriceclose(){
			this.backPriceVisible = false;
			this.getdata();
		},
		//-----
		quitRandomScore(e) {
			let that = this;
			let tempid = e.currentTarget.dataset.id;
			that.tempid = tempid;
			that.randomScoreVisible = true;
		},
		receiveRandomScore(){
			var that = this;
			var tempid = that.tempid;
			that.loading = true;
			app.post('ApiQueueFree/randomScore', {id:tempid}, function (res) {
				that.loading = false;
				that.randomScore = res.data.random_score;
				console.log(that.randomScore,'that.randomScore');
				that.$refs.popup.open('center');
			});
		},
		randomScoreclose(){
			this.randomScoreVisible = false;
			this.getdata();
		},
		
		quitScore(e){
			let that = this;
			var givescore = e.currentTarget.dataset.givescore;
			console.log(givescore);
			var msg = '退出排队后您将获得'+givescore+that.t('积分')+'，确定退出吗？';
			var id = e.currentTarget.dataset.id;
			app.confirm(msg, function () {
				app.showLoading('提交中');
				app.post('ApiQueueFree/quitscore', {id: id}, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000);
				});
			});
		},
		quitFreezeMoney(e){
			let that = this;
			var givemoney = e.currentTarget.dataset.givemoney;
			var msg = '退出排队后您将获得'+givemoney+that.t('冻结资金')+'，确定退出吗？';
			var id = e.currentTarget.dataset.id;
			app.confirm(msg, function () {
				app.showLoading('提交中');
				app.post('ApiQueueFree/quitFreezeMoney', {id: id}, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000);
				});
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
      app.post('ApiQueueFree/index', {status: st,pagenum: pagenum,keyword:that.keyword}, function (res) {
				that.loading = false;
				uni.setNavigationBarTitle({
					title: that.t('排队返现')
				});
        var data = res.datalist;
        if (pagenum == 1) {
					that.datalist = data;
					that.set = res.set;
					if(that.set.total_money_popup_st == 1 && that.isOnload){
						that.fireworksShow = false;
						that.$nextTick(() => {
							that.fireworksShow = true;
							that.$refs.popupMiandan.open();
							that.isOnload = false;
						})
					}
					if(that.set.hasOwnProperty('paidui_text')){
						that.paidui_text =that.set.paidui_text
					}
					that.itemlabel= [that.paidui_text,'已完成'];
					if(res.set.large_money_split_status ==1){
						that.itemlabel =[that.paidui_text,'待排队','已完成'];
						that.itemvalue =['0','2','1'];
						
					}
					
					if(that.set.hasOwnProperty('paidui_money_show')){
						that.paidui_money_show =that.set.paidui_money_show
					}
					if(that.set.hasOwnProperty('jiangli_text')){
						if(that.set.jiangli_text !=''){
							that.jiangli_text =that.set.jiangli_text
						}
					}
					//退出抽积分 自定义文字
					that.quit_random_score_text = '退出抽'+app.t('积分');
					if(that.set.hasOwnProperty('quit_random_score_text')){
						if(that.set.quit_random_score_text !=''){
							that.quit_random_score_text = that.set.quit_random_score_text;
						}
					}
					//退出返红包 自定义文字
					if(that.set.hasOwnProperty('quit_wxhb_text')){
						if(that.set.quit_wxhb_text !=''){
							that.quit_wxhb_text =that.set.quit_wxhb_text;
						}
					}
					
					console.log(that.paidui_money_show,'that.paidui_money_show')
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
		popupMiandanclose(){
			this.$refs.popupMiandan.close();
		},
    changemaskrule: function() {
      this.showmaskrule = !this.showmaskrule;
    },
  }
};
</script>
<style>
.container{ width:100%;}
.order-content{display:flex;flex-direction:column}
.order-box{ width: 94%;margin:10rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width:100%; border-bottom: 1px #f4f4f4 solid; height: 70rpx; line-height: 70rpx; overflow: hidden; color: #999;}
.order-box .head .f1{display:flex;align-items:center;color:#333}
.order-box .head .f1 image{width:34rpx;height:34rpx;margin-right:4px}
.order-box .head .st0{ width: 140rpx; color: #ff8758; text-align: right; }
.order-box .content {line-height: 180%;}
.page-view-hongbao {width: 100%;height: 100%;position: fixed;top:0;left: 0;z-index: 999;background-color: rgba(0, 0, 0, 0.4);overscroll-behavior-y: contain !important;}
.page-view-hongbao .hongbao-view{width: 100%;margin: 266rpx auto;	display: flex;align-items: center;justify-content: space-around;position: relative;}
.hongbao-view .wrap {display: flex;justify-content: center;position: relative;width: 25%;height: 400rpx;overflow: hidden;}
.hongbao-view .wrap .cover{width: 100%;height:230rpx;}
.hongbao-view .wrap .envelope {position: relative;top: 70rpx;animation: envelope-animation 1.8s ;width: 100%;}
@keyframes envelope-animation {
	0% {
		top: 120rpx;
		transform: scaleY(1);
	}
	20% {
		top: 20rpx;
		transform: scaleY(1);
	}
	70% {
		top: 20rpx;
		transform: scaleY(1);
	}
	80% {
		top: 20rpx;
		transform: scaleY(1);
	}
	90% {
		top: 70rpx;
		transform: scaleY(0.9);
	}
	100% {
		top: 70rpx;
		transform: scaleY(1);
	}
}
.hongbao-view .hongbao-view-close{position: absolute;bottom:-188rpx;border: 2px #fff solid;width: 60rpx;height: 60rpx;border-radius: 50%;display: flex;align-items: center;justify-content: center;}
.hongbao-view .hongbao-view-close image{width: 80%;height: 80%;}
.hongbao-view .wrap .btn {position: absolute;top: 30rpx;left: calc(50% - 40rpx);width: 80rpx;height: 80rpx;animation: btn-animation 0.3s 4;animation-direction: alternate;}
@keyframes btn-animation {from {transform: scale(1);}to {	transform: scale(0.6);}}
.cl-popup {}
.cl-popup .main {position: relative;width: 580rpx;height: 770rpx;}
.cl-popup .top {position: absolute;top: 0;width: 100%;height: 560rpx;}
.cl-popup .icon {position: absolute;top: 324rpx;left: calc(50% - 87rpx);width: 174rpx;height: 178rpx;z-index: 2;}
.cl-popup .bottom {position: absolute;bottom: 0;width: 100%;height: 434rpx;}
.cl-popup .content {display: flex;flex-direction: column;align-items: center;position: absolute;top: 0;left: 0;width: 100%;height: 100%;z-index: 5;}
.cl-popup .price {margin-top: 70rpx;margin-bottom: 300rpx;}
.cl-popup .num {font-size: 122rpx;font-weight: bold;color: #fc5c43;}
.cl-popup .unit {position: relative;left: 10rpx;bottom: 10rpx;font-size: 50rpx;font-weight: 500;color: #fc5c43;}
.cl-popup .title {margin-bottom: 40rpx;font-size: 28rpx;font-weight: 400;color: #ffe0be;}
.cl-popup .cl-button {width: 316rpx;height: 78rpx;background: linear-gradient(180deg, #fff7da 0%, #f3a160 100%);box-shadow: 0 3rpx 6rpx #d12200;border-radius: 50rpx;text-align: center;line-height: 78rpx;}
.cl-popup .cl-button text {font-size: 32rpx;font-weight: bold;color: #f74d2e;}
.tab{position: fixed;width: 100%;}
.height50{width: 100%;height: 50px}
/* 累计奖励 */
.tongji{width: 94%;margin: 30rpx 3%;padding: 30rpx;border-radius: 20rpx; background-color: #F2350D;height: 160rpx;color: #fff;position: fixed;justify-content: space-evenly;text-align: center;}
.tongji .price{font-size: 40rpx;font-weight: 700;}
.height130{width: 100%;height: 310rpx}
.tab_top{top:210rpx}

.operate{margin-bottom: 10rpx;}
.operate .button{margin-left:20rpx; margin-top: 10rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:8rpx;text-align:center;padding: 0 10rpx;font-size: 28rpx;}
/* 免单金额 */
.popup-miandan-content{position: relative;}
.popupMiandan-close{border: 2px #fff solid;width: 60rpx;height: 60rpx;border-radius: 50%;display: flex;align-items: center;justify-content: center;margin: 0 auto;}
.popupMiandan-close image{width: 80%;height: 80%;}
.popup-miandan-content .popup-miandan-price{width: 100%;position: absolute;top:25%;align-items: flex-end;justify-content: center;z-index: 1;}
.popup-color-text{background: linear-gradient(180deg, #FF8D3F 0%, #F84A2C 100%);-webkit-background-clip: text;-webkit-text-fill-color: transparent;background-clip: text;
text-fill-color: transparent;}
.popup-miandan-content .popup-miandan-price .price-num-text{font-size: 90rpx;font-weight: 900;padding-left: 30rpx;}
.popup-miandan-content .popup-miandan-price .right-text{font-size: 26rpx;font-weight: bold;margin-bottom: 25rpx;}
.popup-leiji-miandan{width: 100%;position: absolute;top:43%;text-align: center;font-size: 30rpx;font-weight: bold;z-index: 1;}
.popup-guishu{width: 100%;position: absolute;bottom: 10%;text-align: center;bottom: 17%;color: #FFDAA1;z-index: 1;}
.popup-guishu .top-text{font-size: 40rpx;font-weight: bold;letter-spacing: 4rpx;color: #FFDAA1;}
.popup-guishu .bottom-text{font-size: 20rpx;letter-spacing: 15rpx;color: #eac694;margin-top: 5rpx;}
/*规则弹窗*/
#mask-rule {
  position: fixed;
  left: 0;
  top: 0;
  z-index: 99999;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.85);
}

#mask-rule .box-rule {
  position: relative;
  margin: 30% auto;
  padding-top: 40rpx;
  width: 90%;
  height: 700rpx;
  border-radius: 20rpx;
  background-color: #f58d40;
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
</style>