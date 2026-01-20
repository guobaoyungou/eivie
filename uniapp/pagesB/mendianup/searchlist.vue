<template>
<view>
	<block v-if="isload">
		
		<view class="initials-list-wrap" v-if="datalist.length>0">
		    <scroll-view class="scroll-list" scroll-y :scroll-into-view="scrollViewId">
		        <view v-for="(item,index) in datalist" :key="index" >
		            <view class="letter" :id="item.zimu">{{item.zimu}}</view>
		            <view class="con" @tap="search" :data-city="item.city" >
									 <view class="list-item">	{{item.city}}</view>
		            </view>
		        </view>
		    </scroll-view>
		    <view class="letter-list-bar" :class="{active:touchmove}">
		        <text v-for="(item, index) in letterList" :key="index" class="text"
		            :class="{active:touchmoveIndex == index }" @tap.stop.prevent="letterSelect(index)">
		               {{ item }}
		        </text>
		    </view>
		    <view class="letter-alert" v-if="touchmove">{{ letterList[touchmoveIndex] }} </view>
			
		</view>
	
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata" text=""></nodata>
		
		<dp-chat-tabbar currentIndex="2"></dp-chat-tabbar> 
		
		
		
	</block>
	<popmsg ref="popmsg"></popmsg>
	<loading v-if="loading"></loading>
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
			pre_url:app.globalData.pre_url,

      datalist: [],
      pagenum: 1,
      nomore: false,
			nodata:false,
      count: 0,
      keyword: '',
      auth_data: {},
			touchmove: false,
			touchmoveIndex: -1,
			scrollViewId: '',
			letterList:[],
			newcount:0
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		//this.getdatalist();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
     // this.getdatalist(true);
    }
  },
  methods: {
    getdata: function () {
      var that = this;
			that.loading = true;
      app.get('ApiMendianup/getmendiancity', { }, function (res) {
        that.loading = false;
				that.letterList = res.data.letterlist
				that.datalist = res.data.namelist;
				//console.log(that.datalist);
				that.newcount = res.newcount
        that.loaded();
      });
    },
		search:function(e){
			var city = e.currentTarget.dataset.city;
			app.goto('list?city='+city);
		},
   
		letterSelect(index) {
		    if (this.touchmove) {
		        return false;
		    }
		    this.touchmove = true;
		    let item = this.letterList[index];
		    if (item) {
		        this.scrollViewId = item;
		        this.touchmoveIndex = index;
		    }
				console.log( this.scrollViewId);
		    setTimeout(() => {
		        this.touchmove = false;
		        this.touchmoveIndex = -1;
		    }, 500);
		},
  }
};
</script>
<style>

page{ background: #fff;}
.topsearch{width:94%;margin:90rpx 3% 0rpx;}
.topsearch .f1{height:80rpx;border-radius:40rpx;border:0;background-color:#F5F6F7;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

.content{width: 100%;background: #fff; padding:20rpx}
.content .label{display:flex;width: 100%;padding:24rpx 16rpx;color: #333;}
.content .label .t1{flex:1}
.content .label .t2{ width:300rpx;text-align:right}

.content .item:first-child{ border-top:none}
.content .item{width: 100%;border-top: 1px #f5f5f5 solid;min-height: 80rpx;display:flex;align-items:center; padding:20rpx}
.content .item image{width:80rpx;height:80rpx; border-radius: 5rpx; margin-right: 10rpx;}
.content .item .f1{display:flex;flex:1}
.content .item .f1 .t2{display:flex;flex-direction:column;padding-left:20rpx}
.content .item .f1 .t2 .x1{color: #222;font-size:30rpx;}
.content .item .f1 .t2 .x2{color: #999;font-size:24rpx}

.content .item .shuzi{ background: #F55726; color: #fff;width: 36rpx; height: 36rpx; border-radius: 50%; margin-right: 30rpx; text-align: center; }



.content .item .f2{display:flex;flex-direction:column;width:auto;text-align:right;border-left:1px solid #eee}
.content .item .f2 .t1{ font-size: 40rpx;color: #666;height: 40rpx;line-height: 40rpx;}
.content .item .f2 .t2{ font-size: 28rpx;color: #999;height: 50rpx;line-height: 50rpx;}
.content .item .btn{ border-radius:8rpx; padding:3rpx 12rpx;margin-left: 10px;border: 1px #999 solid; text-align:center; font-size:28rpx;color:#333;}
.content .item .btn:nth-child(n+2) {margin-top: 10rpx;}


.list-item { display: flex;  align-items: center;}
.list-item image{ width:80rpx; height: 80rpx; margin-right: 20rpx; border-radius: 50%;}
.list-item text{  width: 90%; height: 80rpx; line-height: 80rpx;}

.title { display: flex;position: fixed; top:0; z-index: 1000; background: #fff; width: 100%; align-items: center; justify-content: center; height: 80rpx;}
.title .add{ position: absolute; top: 20rpx; right: 20rpx;}
.title .add image{ width: 40rpx; height: 40rpx;}


.initials-list-wrap { height: 100%;}
.scroll-list {	height: 800px;	background: #F8F9FD;		padding: 0 0rpx 24px;	box-sizing: border-box;}
.letter {	display: flex;		align-items: center;	font-size: 28rpx;		color: #333;	padding: 32rpx 0rpx 24rpx 44rpx;background-color: #F6F6F6;}
.con .list-item {	padding: 24rpx;	background-color: #fff;	display: flex;	align-items: center;height: 124rpx; }
 .con .list-item  text {	color: #000;	font-size: 26rpx; }
.letter-list-bar {   display: flex;   flex-direction: column;  position: fixed;   padding: 32rpx 12rpx;  right: 0;  z-index: 10;
  top: 50%;   transform: translateY(-50%);}
		
		
		.text {
		    color: rgba(0, 0, 0, 0.6);
		    font-size: 24rpx;
		    text-align: center;
		    height: 38rpx;
		    line-height: 38rpx;
		
	
		}
		.text .active {
		    color: #222222;
		    font-size: 36rpx;
		}
		.active .active {
		    color: #222222;
		    font-size: 36rpx;
		}

    .letter-alert {
        position: absolute;
        z-index: 20;
        left: 50%;
        top: 50%;
        width: 160rpx;
        height: 160rpx;
        line-height: 160rpx;
        margin-left: -80rpx;
        margin-top: -80rpx;
        border-radius: 80rpx;
        text-align: center;
        font-size: 70rpx;
        color: #fff;
        background-color: rgba(0, 0, 0, 0.5);
    }


</style>