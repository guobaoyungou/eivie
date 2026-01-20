<template>
<view class="container" :style="{background:info.color1}">
	<block v-if="isload">
		<view class="banner"><image :src="info.banner" mode="widthFix"></image></view>
		<view class="box1">
			<view class="item"><view class="t1" :style="{color:info.color2}">{{info.joinnum}}</view><view class="t2">参与人数</view></view>
			<view class="item"><view class="t1" :style="{color:info.color2}">{{info.helpnum}}</view><view class="t2">累计投票</view></view>
			<view class="item"><view class="t1" :style="{color:info.color2}">{{info.readcount}}</view><view class="t2">访问次数</view></view>
		</view>
		<view class="box2">
			<block v-for="(item,index) in datalist" :key="index">
			<view class="item" @tap="goto" :data-url="'detail?id='+item.id">
				<view class="f1" :style="{color:info.color2}">{{index+1}}</view>
				<view class="right">
					<view class="f2">
						<image :src="item.pic" class="img"/>
						<view class="t1">
							<text class="x1">{{item.name}}</text>
							<text class="x2">编号：{{item.number}}</text>
						</view>
					</view>
					<view class="f3" :style="{color:info.color2}">{{item.helpnum}}</view>
				</view>
			</view>
			</block>
			<view v-if="!nomore" style="width:100%;text-align:center;height:40rpx;line-height:40rpx;color:#778899;font-size:26rpx;margin-top:30rpx" @tap="getmore">- 查看更多 -</view>
			<nomore v-if="nomore"></nomore>
			<nodata v-if="nodata"></nodata>
		</view>
		<view style="width:100%;height:20rpx"></view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();
var interval = null;
export default {
  data() {
    return {  
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			
      datalist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,

      info: {},
			nowtime:0,
      djsday: '00',
      djshour: '00',
      djsmin: '00',
      djssec: '00',
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	}, 
  onUnload: function () {
    clearInterval(interval);
  },
  methods: {
    getdata: function () {
      var that = this;
      var pagenum = that.pagenum;
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
      app.post('ApiToupiao/phb', {id:that.opt.id,pagenum:pagenum}, function (res) {
				that.loading = false;
				if (pagenum == 1) {
					that.datalist = res.datalist;
          if ((that.datalist).length == 0) {
            that.nodata = true;
          }

					that.info = res.info;
					that.nowtime = res.nowtime;
					//uni.setNavigationBarTitle({
					//	title: that.info.name
					//});
					var title = that.info.sharetitle ? that.info.sharetitle : that.info.name;
					var sharepic = that.info.sharepic ? that.info.sharepic : that.info.pic;
					var sharelink = that.info.sharelink ? that.info.sharelink : '';
					var sharedesc = that.info.sharedesc ? that.info.sharedesc : '';
					that.loaded({title:title,desc:sharedesc,link:sharelink,pic:sharepic});

					clearInterval(interval);
					interval = setInterval(function () {
						that.nowtime = that.nowtime + 1;
						that.getdjs();
					}, 1000);
				}else{
					if ((res.datalist).length == 0) {
            that.nomore = true;
          } else {
            var datalist = that.datalist;
            var newdata = datalist.concat(res.datalist);
            that.datalist = newdata;
          }
				}
      });
    },
    getdjs: function () {
      var that = this;
      if (that.info.starttime * 1 > that.nowtime * 1) {
        var totalsec = that.info.starttime * 1 - that.nowtime * 1;
      } else {
        var totalsec = that.info.endtime * 1 - that.nowtime * 1;
      }
      if (totalsec <= 0) {
        that.djsday = '00';
        that.djshour = '00';
        that.djsmin = '00';
        that.djssec = '00';
      } else {
        var date = Math.floor(totalsec / 86400);
        var houer = Math.floor((totalsec - date * 86400) / 3600);
        var min = Math.floor((totalsec - date * 86400 - houer * 3600) / 60);
        var sec = totalsec - date * 86400 - houer * 3600 - min * 60;
        var djsday = (date < 10 ? '0' : '') + date;
        var djshour = (houer < 10 ? '0' : '') + houer;
        var djsmin = (min < 10 ? '0' : '') + min;
        var djssec = (sec < 10 ? '0' : '') + sec;
        that.djsday = djsday;
        that.djshour = djshour;
        that.djsmin = djsmin;
        that.djssec = djssec;
      }
    },
		getmore:function(){
			this.pagenum = this.pagenum + 1;
      this.getdata(true);
		}
  }
}
</script>
<style>
.container{min-height:100vh;display:flex;flex-direction:column;}
.banner{width:100%;}
.banner image{width:100%;height:auto}

.box1{width:94%;margin-left:3%;border-radius:12rpx;background:#fff;padding:60rpx 10rpx;display:flex;align-items:center;position:relative;z-index:12;margin-top:-160rpx}
.box1 .item{flex:1;display:flex;flex-direction:column;align-items:center;}
.box1 .item .t1{font-size:48rpx;font-weight:bold}
.box1 .item .t2{font-size:24rpx;color:#778899;margin-top:10rpx}

.box2{width:94%;margin-left:3%;border-radius:12rpx;background:#fff;padding:30rpx 10rpx;display:flex;flex-direction:column;align-items:center;position:relative;z-index:12;margin-top:20rpx}
.box2 .item{width:100%;display:flex;align-items:center;padding:0 40rpx 0 0;}
.box2 .item .f1{font-size:36rpx;font-weight:bold;width:100rpx;text-align:center}
.box2 .item .right{flex:1;border-bottom:1px solid #EEEEEE;display:flex;padding:20rpx 0;}
.box2 .item .f2{display:flex;align-items:center;}
.box2 .item .f2 .img{width:80rpx;height:80rpx;border-radius:50%;margin-right:20rpx;flex-shrink:0}
.box2 .item .f2 .t1{display:flex;flex-direction:column;}
.box2 .item .f2 .t1 .x1{color:#222222;font-size:28rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.box2 .item .f2 .t1 .x2{color:#778899;font-size:24rpx}
.box2 .item .f3{flex:1;font-size:36rpx;font-weight:bold;display:flex;align-items:center;justify-content:flex-end;}
</style>