<template>
<view class="container" style="min-height:100vh">
	<block v-if="isload">
		<view class="activity">
			<view class="activity-amin">
				<view class="h2">中奖名单</view>
				<view class="listbox">
					<view v-for="(item, index) in datalist" :key="index" class="tr flex listitem">
						<view class="member">
							<view><image :src="item.headimg"></view>
							<view>{{item.nickname}}</view>
						</view>
						<view class="info">
							<view class="td td2">中奖备注：{{item.remark}}</view>
							<view class="td td2">中奖时间：{{item.createtime}}</view>
							<view class="td td2">中奖奖品：{{item.jxmc}}</view>
						</view>
					</view>
				</view>
				<nomore v-if="nomore"></nomore>
				<nodata v-if="nodata"></nodata>
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

export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			
			pre_url:app.globalData.pre_url,
      st: 0,
      datalist: [],
      maskshow: false,
      record: "",
			info:{},
      formdata: "",
			pagenum: 1,
			nomore: false,
			nodata: false,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		// this.getdata();
	},
	onReachBottom: function () {
	  if (!this.nodata && !this.nomore) {
	    this.pagenum = this.pagenum + 1;
	    this.getdata(true);
	  }
	},
  methods: {
		getdata: function (loadmore) {
			var that = this;
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
			var that = this;
			var pagenum = that.pagenum;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
			app.post('ApiDscj/prize', {hid: that.opt.hid,pagenum: that.pagenum}, function (res) {
				that.loading = false;
			  that.loaddingmore = false;
			  var data = res.datalist;
				// that.joinnum = res.count
			  if (pagenum == 1) {
					that.datalist = res.datalist;
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
		}
  }
};
</script>
<style>
.banner{ width:100%;padding:0 5%}
.banner image{ display:block;width:100%;}
.flex{display: flex;justify-content: space-between;align-items: center;}

.activity{padding-top:50rpx}
.activity-amin{width:94%; margin:0 auto;}
.activity-amin .h2{ margin:0 auto 10rpx auto;width:330rpx;height: 70rpx;background-color: #f4733b;text-align: center;line-height:70rpx;font-size: 30rpx;color: #ffffff;border-radius: 50rpx;letter-spacing:14rpx}
.wt1{display:block; border:none; background-color:#FFF; padding:22rpx 22rpx; border-radius:8rpx; font-size: 30rpx; margin-bottom:60rpx;width:100%}
.wt4{width:100%;background-color:#f4733b; color:#FFF;font-size:30rpx;margin-top: 30rpx;}
.listbox{width:100%;font-size:30rpx;margin-top: 60rpx;margin-bottom: 30rpx;}
.listbox .listitem{background-color:#FFFFFF; color:#222222;border-radius: 16rpx;margin-top: 20rpx;padding: 30rpx 10rpx;}
.listitem .member{flex-shrink: 0;width: 150rpx;text-align: center;}
.listitem .member image{height: 70rpx;width: 70rpx;border-radius: 50%;}
.listitem .info{flex: 1;padding-left: 40rpx;line-height: 50rpx;color: #989898;}
.listitem .opt{display: flex;justify-content: flex-end;align-items: center;}
.listitem .opt text{padding: 0 20rpx;border: 1rpx solid #f58d19;border-radius: 20rpx;color: #f58d19;}
.goback{display:block;color:#fff;background-color:#f4733b;margin:20rpx auto 40rpx auto;width:90%;padding:20rpx 0;text-align:center;font-size:36rpx;border-radius:15rpx;}

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
.subbtn{width:100%;background:#fb3a13;font-size: 30rpx;padding:0 22rpx;border-radius: 8rpx;color:#FFF;}
</style>