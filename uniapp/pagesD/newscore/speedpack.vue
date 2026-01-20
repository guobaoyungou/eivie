<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['未使用','已使用','已过期']" :itemst="['0','1','2']" :st="st" :isfixed="false" @changetab="changetab"></dd-tab>

		<view class="coupon-list">
			<view v-for="(item, index) in datalist" :key="index" class="coupon" >
				<view class="pt_left">
					<view class="pt_left-content">
						<view class="f1" :style="{color:t('color1')}"><text class="t1">{{item.pack_ratio}}%</text></view>
						<view class="f2" :style="{color:t('color1')}">
							{{t('新积分')}}加速
						</view>
					</view>
				</view>
				<view class="pt_right">
					<view class="f1">
						<view class="t1">{{item.pack_name}}</view>
						<text class="t2" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}">{{item.type_txt}}</text>
						<view class="t3" v-if="item.type!=20" :style="item.bid>0?'margin-top:0':'margin-top:10rpx'">有效期至 {{item.endtime}}</view>
						<view class="t4" v-if="item.bid>0">适用商家：{{item.bname}}</view>
					</view>
          <view style="display: flex;flex-wrap: wrap;align-items: center;justify-content: center;width: 140rpx;">
              
              <block v-if="item.status==1" >
                <button class="btn" :style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" >加速中</button>
              </block>
            
            <image class="sygq" v-if="st==2" :src="pre_url+'/static/img/ygq.png'"></image>
          </view>
				</view>
			</view>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
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
		pagenum: 1,
		nomore: false,
		nodata:false,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 0;
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
  methods: {
    getdata: function (loadmore) {
		if(!loadmore){
			this.pagenum = 1;
			this.datalist = [];
		}
		var that = this;
		var pagenum = that.pagenum;
		var st = that.st;
		var bid = that.opt && (that.opt.bid || that.opt.bid === '0') ? that.opt.bid : '';
		that.loading = true;
		that.nomore = false;
		that.nodata = false;
		app.post('ApiNewScore/myspeedpack', {st: st,pagenum: pagenum}, function (res) {
			that.loading = false;
			uni.setNavigationBarTitle({
				title: '我的' + that.t('加速包')
			});
			that.rdata =  res.rdata;
			var data = res.data;
			if (pagenum == 1) {
				that.checkednum = 0;
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
		
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
  }
};
</script>
<style>

.coupon-list{width:100%;padding:20rpx}
.coupon{width:100%;display:flex;margin-bottom:20rpx;border-radius:10rpx;overflow:hidden;align-items:center;position:relative;background: #fff;}
.coupon .pt_left{background: #fff;min-height:200rpx;color: #FFF;width:40%;display:flex;flex-direction:column;align-items:center;justify-content:center}
.coupon .pt_left-content{width:100%;height:100%;margin:30rpx 0;border-right:1px solid #EEEEEE;display:flex;flex-direction:column;align-items:center;justify-content:center}
.coupon .pt_left .f1{font-size:40rpx;font-weight:bold;text-align:center;}
.coupon .pt_left .t0{padding-right:0;}
.coupon .pt_left .t1{font-size:60rpx;}
.coupon .pt_left .t2{padding-left:10rpx;}
.coupon .pt_left .f2{font-size:20rpx;color:#4E535B;text-align:center;}
.coupon .pt_right{background: #fff;width:60%;display:flex;min-height:200rpx;text-align: left;padding:20rpx 20rpx;position:relative;justify-content: space-between;align-items: center;}
.coupon .pt_right .f1{flex-grow: 1;flex-shrink: 1;}
.coupon .pt_right .f1 .t1{font-size:28rpx;color:#2B2B2B;font-weight:bold;height:60rpx;line-height:60rpx;overflow:hidden}
.coupon .pt_right .f1 .t2{height:36rpx;line-height:36rpx;font-size:20rpx;font-weight:bold;padding:0 16rpx;border-radius:4rpx; margin-right: 16rpx;}
.coupon .pt_right .f1 .t2:last-child {margin-right: 0;}
.coupon .pt_right .f1 .t3{font-size:20rpx;color:#999999;height:46rpx;line-height:46rpx;}
.coupon .pt_right .f1 .t4{font-size:20rpx;color:#999999;height:46rpx;line-height:46rpx;max-width: 76%;text-overflow: ellipsis;overflow: hidden;white-space: nowrap;}
.coupon .pt_right .btn{border-radius:28rpx;width:140rpx;height:56rpx;line-height:56rpx;color:#fff}
.coupon .pt_right .sygq{margin-top:20rpx;width:100rpx;height:100rpx;}



</style>