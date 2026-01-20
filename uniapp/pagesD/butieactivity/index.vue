<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','发放中','已完成']" :itemst="['all','1','2']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view :style="show_rebate?'height:260rpx;width:100%;':'height:100rpx;width:100%;'"></view>
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
			<view class="order-box"  @tap.stop="goto" :data-url="'recordlog?butie_member_product_id=' + item.id+'&butie_activity_id=' + item.butie_activity_id+'&pro_id=' + item.pro_id" >	
			<view class="head flex flex-y-center">
				<view class="head-top-view flex flex-y-center">					
					<image v-if="item.pic" :src="item.pic"></image>
					<image v-else :src="pre_url+'/static/img/ico-shop.png'"></image>
					<view class="head-title">{{item.name}}</view>
				</view>
				<text class="st" :class="'st'+item.status">{{item.status_str}}</text>
			</view>
			<view class="report-box">
				<view class="report-item">
					<view class="item-txt">已补贴天数</view>
					<view class="item-num">{{item.back_days_total}}天</view>
				</view>
				<view class="report-item">
					<view class="item-txt">累计可补贴天数</view>
					<view class="item-num">{{item.back_days}}天</view>
				</view>
				<view class="report-item">
					<view class="item-txt">推广增加天数</view>
					<view class="item-num">{{item.back_days_add}}天</view>
				</view>
			</view>
			<view class="report-box">
				<view class="report-item">
					<view class="item-txt">每日发放金额</view>
					<view class="item-num">￥{{item.back_everyday_money}}</view>
				</view>
				<view class="report-item">
					<view class="item-txt">推广增加日金额</view>
					<view class="item-num">￥{{item.back_everyday_money_add}}</view>
				</view>
				<view class="report-item">
					<view class="item-txt">已发放金额</view>
					<view class="item-num">￥{{item.back_money_total}}</view>
				</view>
			</view>
			<view class="report-box">
				<view class="report-item">
					<view class="item-txt">已推人数</view>
					<view class="item-num">{{item.tuiguang_num}}人</view>
				</view>
				<view class="report-item" @tap.stop="showmemberstage(item)">
					<view class="item-txt">推广人数</view>
					<view class="item-num">{{item.tuiguang_num_max}}人</view>
				</view>
			</view>
			<view class="weekdays">
				<view class="weekday" v-for="(day,i) in item.week_day" :key="i">{{day}}</view>
			</view>
			</view>
			</block>
		</view>
		<uni-popup id="dialogTuiguang" ref="dialogTuiguang" type="dialog">
			<view style="background:#fff;padding:20rpx 30rpx;border-radius:10rpx;width:600rpx" >
				<view class="sendexpress-item" v-for="(item, index) in member_stage.tuiguangdata" :key="index" style="display: flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 0;">
					<view ><text v-if="item.tuiguang">推广{{item.tuiguang}}人</text><text v-if="item.addday">增加{{item.addday}}</text><text v-if="item.addmoney">天,每日多补贴{{item.addmoney}}元</text></view>					
				</view>
			</view>
		</uni-popup>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
		
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();

export default {
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
			member_stage: [],
			show_rebate:0,
			pre_url:app.globalData.pre_url,
			weekdays:[]
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.butie_activity_id = this.opt.butie_activity_id || 0;
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
	onNavigationBarSearchInputConfirmed:function(e){
		this.searchConfirm({detail:{value:e.text}});
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
	  var butie_activity_id = that.butie_activity_id;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiButieActivity/index', {status: st,butie_activity_id: butie_activity_id,pagenum: pagenum}, function (res) {
				that.loading = false;
				that.show_rebate = res.show_rebate;
				that.info = res;
        var data = res.datalist;
        if (pagenum == 1) {
					that.datalist = data;
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
	
  }
};
</script>
<style>
.container{ width:100%;}
.container .container-top{position: fixed;background-color:#fff;width: 100%;padding: 16rpx 3%;color: #333;justify-content: space-between;flex-wrap: wrap;line-height:50rpx;z-index: 11;}
.container .container-top .top-content{ display:flex; justify-content:left;align-items: center;white-space:nowrap;overflow: hidden;margin: 0 30rpx;}
.container .container-top .top-content .t1{text-align: left;width: 50%;overflow: hidden;}
.container .container-tab {top: 156rpx;}
.container .container-tab .dd-tab2{top: 156rpx;}

.order-content{display:flex;flex-direction:column}
.order-box{ width: 94%;margin:10rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px}
.order-box .head{width:100%; border-bottom: 1px #f4f4f4 solid; height: 70rpx; line-height: 70rpx;justify-content: space-between;}
.order-box .head .head-top-view{color:#333;width:calc(100% - 130rpx);justify-content: flex-start;}
.order-box .head .head-top-view .head-title{width: calc(100% - 40rpx);white-space: nowrap;overflow: hidden;text-overflow: ellipsis;color:#333}
.order-box .head .head-top-view image{width:34rpx;height:34rpx;margin-right:4px}
.order-box .head .st{ width: 130rpx; text-align: right;}
.order-box .head .st0{  color: #fda160;}
.order-box .head .st1{  color: #00ab64;}
.order-box .head .st2{ color: #999; }
.order-box .head .st3{  color: #999; }
.order-box .content{}
.order-box .content .option-content{padding: 3px 0px;}
.progress-box {width: 100%;}

.report-box{display: flex;flex-wrap: wrap;align-items: center;margin-top: 20rpx;}
.report-box .report-item{display: flex;flex-direction: column;align-items: center;justify-content: center; width: 33%; }
.report-item .item-txt{color: #999;margin-bottom: 4rpx;font-size: 24rpx;}
.weekdays{display: flex;flex-wrap: wrap;background: #f6f6f6;border-radius:8rpx;padding: 10rpx 20rpx;margin-top: 20rpx;font-size: 24rpx;margin-bottom: 20rpx;justify-content: space-around;}
.weekdays .weekday{margin: 6rpx;}
</style>