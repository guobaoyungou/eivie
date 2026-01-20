<template>
<view class="container">
	<block v-if="isload">
		<view class="expressinfo">
			<view class="head">
				<view class="f1"><image :src="pre_url + '/static/img/feiji.png'"></image></view>
				<view class="f2">
					<view class="t1">快递公司：<text style="color:#333" user-select="true" selectable="true">{{express_com}}</text></view>
					<view class="t2">快递单号：<text style="color:#333" user-select="true" selectable="true">{{express_no}}</text></view>
				</view>
			</view>
			<view class="content">
				<view v-for="(item, index) in datalist" :key="index" :class="'item ' + (index==0?'on':'')">
					<view class="f1"><image :src="'/static/img/dot' + (index==0?'2':'1') + '.png'"></image></view>
					<view class="f2">
						<text class="t2">{{item.time}}</text>
						<text class="t1">{{item.context}}</text>
					</view>
				</view>
				<nodata v-if="nodata" text="暂未查找到物流信息"></nodata>
			</view>
		</view>
	</block>
	<view class="tobuy flex-x-center flex-y-center" @tap="goto" data-url="mail"  :style="{background:t('color1')}" >我要寄件</view>
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
			nodata:false,
      express_com: '',
      express_no: '',
      datalist: []
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onPullDownRefresh: function () {
    this.getdata();
  },
  methods: {
		getdata: function () {
			var that = this;
			that.express_com = that.opt.com;
			that.express_no = that.opt.num;
			that.loading = true;
			app.post('ApiExpress/logistics', {com: that.express_com,num: that.express_no}, function (res) {
				that.loading = false;
				var datalist = res.datalist.data;
				if (datalist.length < 1) {
					that.nodata = true;
				}
				that.express_com = res.com
				that.datalist = datalist;
				that.loaded();
			});
		},
		call:function(e){
			var tel = e.currentTarget.dataset.tel;
			uni.makePhoneCall({
				phoneNumber: tel
			});
		}
  }
}
</script>
<style>
.expressinfo{}
.expressinfo .head { width:100%;background: #fff; margin:20rpx 0;padding: 20rpx 20rpx;display:flex;align-items:center}
.expressinfo .head .f1{ width:120rpx;height:120rpx;margin-right:20rpx}
.expressinfo .head .f1 image{width:100%;height:100%}
.expressinfo .head .f2{display:flex;flex-direction:column;flex:auto;font-size:30rpx;color:#999999}
.expressinfo .head .f2 .t1{margin-bottom:8rpx}
.expressinfo .content{ width: 100%;  background: #fff;display:flex;flex-direction:column;color: #979797;padding:20rpx 40rpx; padding-bottom: 100rpx;;}
.expressinfo .content .on{color: #23aa5e;}
.expressinfo .content .item{display:flex;width: 96%;  margin: 0 2%;border-left: 1px #dadada solid;padding:10rpx 0}
.expressinfo .content .item .f1{ width:40rpx;flex-shrink:0;position:relative}
.expressinfo .content image{width: 30rpx; height: 30rpx; position: absolute; left: -16rpx; top: 22rpx;}
/*.content .on image{ top:-1rpx}*/
.expressinfo .content .item .f1 image{ width: 30rpx; height: 30rpx;}
.expressinfo .content .item .f2{display:flex;flex-direction:column;flex:auto;}
.expressinfo .content .item .f2 .t1{font-size: 30rpx;}
.expressinfo .content .item .f2 .t1{font-size: 26rpx;}

.tobuy{width: 80%; margin: auto; line-height: 72rpx;color: #fff; border-radius:40rpx; 
background-color: #007AFF; border: none;font-size:28rpx;font-weight:bold; height: 80rpx; position: fixed; left:10%; bottom: 0;}
</style>