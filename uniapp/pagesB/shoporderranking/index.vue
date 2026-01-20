<template>
<view v-if="isload">
  <view>
    <view class="poolmoney" v-if="set && set.showpool" :style="pic?'background:url('+pic+') no-repeat;background-size:100% 100%;':'background-color:'+t('color1')" >
      <view style="text-align: center;">
        <view style="font-size: 36rpx;">分红池总金额</view>
        <view style="font-size: 40rpx;margin-top: 20rpx;">{{poolmoney}}元</view>
      </view>
    </view>
    <view v-if="set && ((set.showselfranking && selfranking>0) || (set.showlast && lastid>0))" style="width: 720rpx;margin: 0 auto;line-height: 70rpx;height: 70rpx;">
      <view v-if="set && set.showselfranking && selfranking>0" style="float: left;">
        当前自己排名：{{selfranking}}
      </view>
      <view v-if="set && set.showlast && lastid>0" @tap="goto" :data-url="'detail?id='+lastid" style="float: right;">
        上一期
      </view>
    </view>
    <view class="dd-tab2">
    	<scroll-view scroll-x="true">
        <view class="dd-tab2-content">
          <view v-for="(item,index) in itemdata"  :key="index" class="item" :class="st==itemst[index]?'on':''" @tap="changetab" :data-st="itemst[index]">
            <view>{{item.name}}</view>
            <view class="after" :style="{background:color1?color1:t('color1')}"></view>
            <view v-if="set && set.showchildpool" :style="'text-align: center;line-height: 50rpx;color:'+t('color1')">{{item.childmoney}}元</view>
          </view>
        </view>
    	</scroll-view>
    </view>
    <view v-if="showmoney" style="font-size: 34rpx;text-align: center;">
      参与该分红池差距消费金额:{{money}}元
    </view>
    <view class="contentbox">
      <view class="content">
        <view class="tab">
          <view class="t1" style="width: 80rpx;">排名</view>
        	<view class="t1" style="width: 280rpx;">姓名</view>
        	<view class="t2">消费金额</view>
        	<view v-if="display==1" class="t3" style="width: 112rpx;">预估奖金</view>
        </view>
        <view class="itembox">	
          <block v-for="(item, index) in datalist" :key="index" >
          <view class="item">
              <text class="t1" style="width: 80rpx;">{{index+1}} </text>
              <view class="t2" style="width: 280rpx;">
                <image :src="item.headimg">
                <view style="width:180rpx;overflow: hidden;text-overflow: ellipsis;white-space: nowrap">
                {{item.nickname}}
                </view>
              </view>
              <text class="t3"> {{item.summoney}}元</text>
              <text v-if="display==1" class="t3" style="width: 112rpx;"> {{item.avgmoney}}元</text>
          </view>
          </block>
        </view>
        <nodata v-if="nodata"></nodata>
        <nomore v-if="nomore"></nomore>
      </view>
    </view>
    <loading v-if="loading"></loading>
    <dp-tabbar :opt="opt"></dp-tabbar>
    <popmsg ref="popmsg"></popmsg>
  </view>
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
			menuindex:-1,
			pre_url:app.globalData.pre_url,
      nodata: false,
      nomore: false,
      
      showmoney:false,
      st:0,
      pic:'',
      itemdata:[],
      itemst:[],
      display:0,
      datalist: [],
			poolmoney:0,
      childmoney:0,
      selfmoney:0,
			money:0,
      set:'',
      selfranking:0,
      lastid:0
    };
  },

  onLoad: function (opt) {
    var that = this;
		that.opt = app.getopts(opt);
		that.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
    getdata: function () {
      var that = this;
			that.loading = true;
			that.nodata = false;
      that.nomore = false;
			
      app.post('ApiAgent/shoporderranking', {st:that.st}, function (res) {
				that.loading = false;
        if(res.status == 1){
          //消费排行榜
          uni.setNavigationBarTitle({
            title: res.title
          });
          that.pic       = res.pic;
          that.poolmoney = res.poolmoney;
          that.itemdata  = res.itemdata;
          that.itemst    = res.itemst;
          that.childmoney= res.childmoney;
          that.selfmoney = res.selfmoney;
          that.showmoney = res.showmoney;
          that.money     = res.money;
          that.display   = res.display;
          if(res.mlist && res.mlist.length>0){
            that.datalist = res.mlist;
          }else{
            that.datalist = [];
          }
          if(res.set ){
            that.set = res.set
          }
          if(res.selfranking){
            that.selfranking = res.selfranking
          }
          if(res.lastid){
            that.lastid = res.lastid
          }
          that.loaded();
        }else {
          if (res.msg) {
            app.alert(res.msg, function() {
              if (res.url) app.goto(res.url);
            });
          } else if (res.url) {
            app.goto(res.url);
          } else {
            app.alert('您无查看权限');
          }
        }
      });
    },
    changetab: function (e) {
        this.st = e.currentTarget.dataset.st;
        this.getdata();
    }
  }
};
</script>
<style>
page{background-color: #fff;}
.poolmoney{padding: 60rpx 0;}
.contentbox{width: 720rpx;margin: 0 auto;overflow: hidden;}
.contentbox image{ border-top-left-radius: 10rpx; border-top-right-radius: 10rpx; width: 100%; border:none; display: block;}

.content{display: flex; align-items: center; flex-direction: column; }
.content .top{ background: #F4F5F9; width: 90%;  margin-top: 20rpx; border-radius: 10rpx; display: flex; height: 70rpx; line-height: 70rpx; padding-left: 20rpx; display: flex; align-items: center; }
.content .top .border{ margin-right: 10rpx; height: 30rpx; border-right: 1rpx solid #999; margin: 0 30rpx; }
.content .tab{ display: flex; width:100%; text-align: left;  line-height: 70rpx; margin-top: 20rpx; color: #666;justify-content: space-between;}

.content .tab1{ display: flex; border-bottom: 1rpx solid #dedede; width: 90%; height: 100rpx; line-height: 100rpx;}
.content .tab1 .t1{ text-align: center;margin: 0 30rpx; }
.content .tab1 .t1.on{ color:red;}

.content .itembox{width:100%;}
.content .item{width:100%; display:flex;padding:40rpx 0rpx;border-radius:8px;margin-top: 6rpx;align-items:center;justify-content: space-between;}

.content .item image{ width: 80rpx; height: 80rpx; border-radius: 50%; margin-right: 20rpx;}
.content .item .t1{color:#000000;font-size:30rpx;}
.content .item .t2{color:#666666;font-size:24rpx;  display: flex; align-items: center;}
.content .item .t3{font-weight: bold;word-break: break-all;}

.data-empty{background:#fff}

.dd-tab{display:flex;width:100%;background: #fff;top:var(--window-top);z-index:11;}
.dd-tab .item{flex:1;font-size:28rpx; text-align:center; color:#666; height: 90rpx; line-height: 90rpx;overflow: hidden;position:relative}
.dd-tab .item .after{display:none;position:absolute;left:50%;margin-left:-16rpx;bottom:10rpx;height:3px;border-radius:1.5px;width:32rpx}
.dd-tab .on{color: #323233;}
.dd-tab .on .after{display:block}
.dd-tab2{width:100%;background: #fff;top:var(--window-top);z-index:11;}
.dd-tab2 scroll-view {overflow: visible !important}
.dd-tab2-content{flex-grow: 0;flex-shrink: 0;display:flex;align-items:center;flex-wrap:nowrap;color:#999999;position:relative;}
.dd-tab2-content .item{flex-grow:1;min-width:140rpx;flex-shrink: 0;line-height: 90rpx;text-align:center;position:relative;padding:0 14rpx}
.dd-tab2-content .item .after{display:none;bottom:10rpx;height:3px;border-radius:1.5px;width:40rpx;margin: 0 auto;}
.dd-tab2-content .on{color: #323233;}
.dd-tab2-content .on .after{display:block}
</style>