<template>
<view class="container">
	<block v-if="isload">
		<view v-if="show_rebate" class="top-view flex-col" :style="{background:t('color1')}">
			<view class="top-data-list flex flex-y-center">
				<view class="top-column">
					<view class="data-options flex-col">
						<view class="title-text">待返{{t('余额')}}</view>
						<view class="num-text">{{info.un_send_money}}</view>
					</view>
					<view class="data-options flex-col" style="margin: 30rpx 0;">
						<view class="line-class-width"></view>
					</view>
					<view class="data-options flex-col">
						<view class="title-text">已返{{t('余额')}}</view>
						<view class="num-text">{{info.send_money}}</view>
					</view>
				</view>
				<view class="line-class"></view>
				<view class="top-column">
					<view class="data-options flex-col">
						<view class="title-text">待返{{t('佣金')}}</view>
						<view class="num-text">{{info.un_send_commission}}</view>
					</view>
					<view class="data-options flex-col" style="margin: 30rpx 0;">
						<view class="line-class-width"></view>
					</view>
					<view class="data-options flex-col">
						<view class="title-text">已返{{t('佣金')}}</view>
						<view class="num-text">{{info.send_commission}}</view>
					</view>
				</view>
				<view class="line-class"></view>
				<view class="top-column">
					<view class="data-options flex-col">
						<view class="title-text">待返{{t('积分')}}</view>
						<view class="num-text">{{info.un_send_score}}</view>
					</view>
					<view class="data-options flex-col" style="margin: 30rpx 0;">
						<view class="line-class-width"></view>
					</view>
					<view class="data-options flex-col">
						<view class="title-text">已返{{t('积分')}}</view>
						<view class="num-text">{{info.send_score}}</view>
					</view>
				</view>
			</view>
		</view>

		<dd-tab :class="show_rebate?'container-tab':''" :itemdata="['全部','进行中','已结束']" :itemst="['all','1','2']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view :style="show_rebate?'height:420rpx;width:100%;':'height:100rpx;width:100%;'"></view>
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
			<view class="order-box"  @tap.stop="goto" :data-url="'recordlog?cashback_id=' + item.cashback_id+'&pro_id=' + item.pro_id+'&id='+item.id" >	
			<view class="head flex flex-y-center">
				<view class="head-top-view flex flex-y-center">
					<image :src="pre_url+'/static/img/ico-shop.png'"></image>
					<view class="head-title">活动名称：{{item.name}}</view>
				</view>
				<text class="st0">{{item.status}}</text>
			</view>
			<view class="content flex-col">
				<view class="flex-y-center option-content" v-if="item.business_name">商户名称：{{item.business_name}}</view>
				<view class="flex-y-center option-content" v-if="item.back_name">返现名称：{{item.back_name}}</view>
				<view class="flex-y-center option-content" v-if="item.cashback_money_max >0" >返现额度：{{item.cashback_money_max}}</view>
				<view class="flex-y-center option-content" v-else >返现额度：不限</view>
				<view class="flex-y-center option-content">已返{{item.back_type_name}}：{{item.cashback_num}}</view>
				<view class="shuoming" :style="{background:t('color1')}" @click.stop="getshuoming(index)" v-if="item.shuoming">{{t('购物返现')}}说明</view>
				<view class="flex-y-center option-content"><view class="progress-box"><progress :percent="item.progress" show-info stroke-width="5" activeColor='#ff8758' border-radius='50' /></view></view>
			</view>
			</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
	</block>
	<view id="mask-rule" v-if="shuomingShow">
		<view class="box-rule" :style="{backgroundColor:t('color1')}">
			<view class="h2">{{t('购物返现')}}说明</view>
			<view id="close-rule" @tap="closeshuoming" :style="'background-image:url('+pre_url+'/static/img/dzp/close.png);background-size:100%'"></view>
			<view class="con">
				<view class="text">
					<text decode="true" space="true">{{shuoming}}</text>
				</view>
			</view>
		</view>
	</view>
	<uni-popup ref="popupMiandan" type='center' v-if="info && info.cashback_popup">
		<view class="popup-miandan-content">
			<view class="popup-miandan-price flex">
				<view class="price-num-text popup-color-text">{{info.total_send}}</view>
				<view class="right-text popup-color-text">元</view>
			</view>
			<view class="popup-leiji-miandan popup-color-text">累计返现金额</view>
			<image :src="`${pre_url}/static/img/miandanpopupbg.png`" mode="widthFix" />
		</view>
		<!-- 关闭弹窗按钮  -->
		<view class="popupMiandan-close" @click="popupMiandanclose">
			<image :src="pre_url+'/static/img/close2.png'"></image>
		</view>
	</uni-popup>
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
      st: 'all',
      datalist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,
			info:[],
			show_rebate:0,
			pre_url:app.globalData.pre_url,
			isOnload:false,
			fireworksShow:false,
			shuomingShow:false,
			shuoming:''
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
      app.post('ApiCashback/index', {status: st,pagenum: pagenum}, function (res) {
				that.loading = false;
				that.show_rebate = res.show_rebate;
				that.info = res;
				uni.setNavigationBarTitle({
					title: that.t('购物返现')
				});
        var data = res.datalist;
        if (pagenum == 1) {
					that.datalist = data;
          if (data.length == 0) {
            that.nodata = true;
          }
					if(that.isOnload && res.total_send > 0){
						that.fireworksShow = false;
						that.$nextTick(() => {
							that.fireworksShow = true;
							that.$refs.popupMiandan.open();
							that.isOnload = false;
						})
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
		getshuoming:function(index){
			var info = this.datalist[index] || ''
			if(info){
				this.shuoming = info.shuoming;
				this.shuomingShow = true;
			}
		},
		closeshuoming: function() {
			this.shuomingShow = false;
		}
  }
};
</script>
<style>
.container{ width:100%;}
.top-view{width: 100%;background: #FC2D41;align-items: center;position: fixed;z-index: 10;}
.top-data-list{width: 100%;padding: 35rpx 0rpx;justify-content: center;}
.top-data-list .data-options{text-align: center;}
.top-data-list .line-class{height: 240rpx;border-left: 1rpx rgba(255, 255, 255, .3) solid;}
.top-data-list .line-class-width{width: 20%;border-top: 8rpx #f6f6f6 solid;margin: auto;border-radius: 20rpx;}
.top-data-list .data-options .title-text{font-size: 26rpx;color: #f6f6f6;white-space: nowrap;}
.top-data-list .data-options .num-text{font-size: 36rpx;color:  rgba(255, 255, 255);margin-top: 15rpx;font-weight: bold;}
.top-column {display: flex;flex-direction: column;width: 50%;}
.container .container-tab .dd-tab2,.container .container-tab{top: 320rpx;}

.order-content{display:flex;flex-direction:column}
.order-box{ width: 94%;margin:10rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px;position: relative;}
.order-box .head{width:100%; border-bottom: 1px #f4f4f4 solid; height: 70rpx; line-height: 70rpx;justify-content: space-between;}
.order-box .head .head-top-view{color:#333;width:calc(100% - 130rpx);justify-content: flex-start;}
.order-box .head .head-top-view .head-title{width: calc(100% - 40rpx);white-space: nowrap;overflow: hidden;text-overflow: ellipsis;color:#333}
.order-box .head .head-top-view image{width:34rpx;height:34rpx;margin-right:4px}
.order-box .head .st0{ width: 130rpx; color: #ff8758; text-align: right;}
.order-box .content{}
.order-box .content .option-content{padding: 3px 0px;}
.progress-box {width: 100%;}

/* 累计返现金额 */
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

.shuoming{color: #fff;padding: 2rpx 12rpx;border-radius: 10rpx;width: 150rpx;text-align: center;position: absolute;right: 10rpx;bottom: 60rpx;}
#mask-rule,#mask{position:fixed;left:0;top:0;z-index:99999;width:100%;height:100%;background-color:rgba(0,0,0,0.85)}
#mask-rule .box-rule{position:relative;margin:30% auto;padding-top:40rpx;width:90%;height:675rpx;border-radius:20rpx;background-color:#D85356}
#mask-rule .box-rule .star{position:absolute;left:50%;top:-100rpx;margin-left:-130rpx;width:259rpx;height:87rpx}
#mask-rule .box-rule .h2{width:100%;text-align:center;line-height:34rpx;font-size:34rpx;font-weight:normal;color:#fff}
#mask-rule #close-rule{position:absolute;right:34rpx;top:38rpx;width:40rpx;height:40rpx}
#mask-rule .con{overflow:auto;position:relative;margin:40rpx auto;padding-right:15rpx;width:580rpx;height:82%;line-height:48rpx;font-size:26rpx;color:#fff}
#mask-rule .con .text{position:absolute;top:0;left:0;width:inherit;height:auto}
</style>