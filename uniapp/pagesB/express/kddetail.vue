<template>
<view class="container">
	<block v-if="isload">
		<view class="expressinfo">
			<view class="head">
				<view class="f1"><view v-if="data.kuaidinum">运单号：{{data.kuaidinum}}</view>
				<view v-else>订单号：{{data.ordernum}}</view>
				<view></view></view>
				<view class="f2 flex">
					<view class="t2_box"><text>{{data.sendManPrintCity}}</text><text class="t2">{{data.sendManName}}</text></view>
					<view class="t2_box"><image class="jiantou"  :src="pre_url+'/static/img/jiantou2.png'"></image><view class="t2">{{data.sta}}</view></view>
					<view class="t2_box"><text>{{data.recManPrintCity}}</text><text class="t2">{{data.sendManName}}</text></view>
				</view>
			</view>
			<view class="content">
				<block v-if="datalist">
					<view v-for="(item, index) in datalist" :key="index" :class="'item ' + (index==0?'on':'')">
						<view class="f1"><image :src="'/static/img/dot' + (index==0?'2':'1') + '.png'"></image></view>
						<view class="f2">
							<text class="t2">{{item.time}}</text>
							<text class="t1">{{item.context}}</text>
						</view>
					</view>
					<nodata v-if="nodata" text="暂未查找到物流信息"></nodata>
				</block>
				<view v-else class="item">
					<view class="f1"><image :src="'/static/img/dot' + (index==0?'2':'1') + '.png'"></image></view>
					<view class="f2">
						<text class="t2">{{data.sta}}</text>
						<text class="t1"></text>
					</view>
				</view>
			</view>
		</view>
	</block>
	
	<uni-popup id="dialogRemark" ref="dialogExpress" type="dialog">
		<view style="background:#fff;padding:20rpx 30rpx;border-radius:10rpx;width:600rpx" >
			<form @submit="formSubmit">
			<view class="sendexpress-item" style="padding:20rpx 0;">
					<view>	
						<input class="input" type="text" placeholder="请输入原因" placeholder-style="font-size:28rpx;color:#BBBBBB" name="remark" ></input>
					</view>
					<button  class="submit" form-type="submit" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'"> 确定取消</button>
			</view>
			</form>
		</view>
	</uni-popup>
	
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
      datalist: [],
			data:[]
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
			that.id = that.opt.id;
			that.loading = true;
			app.post('ApiExpress/kddetail', {id: that.id}, function (res) {
				that.loading = false;
				that.data = res.data
				var datalist = res.datalist;
				if (datalist.length < 1) {
					that.nodata = true;
				}
				that.datalist = datalist;
				that.loaded();
			});
		},
		call:function(e){
			var tel = e.currentTarget.dataset.tel;
			uni.makePhoneCall({
				phoneNumber: tel
			});
		},
		cancle:function(e){
			this.$refs.dialogExpress.open();
		},
		formSubmit: function (e) {
		  var that = this;
		  var formdata = e.detail.value;
		  if (formdata['remark'] == '' ) {
		    app.error('请填写原因');
		    return;
		  }
			app.showLoading('提交中');
		  app.post('ApiExpress/cancle', {id:that.id,remark: formdata['remark']}, function (res) {
				app.showLoading(false);
		    if (res.status == 1) {
		      app.success('取消成功');
		      setTimeout(function () {
		        app.goback(true);
		      }, 1000);
		    }else{
					app.alert(res.msg);
					return;
				}
		    
		  });
		},
  }
}
</script>
<style>
.expressinfo{}
.expressinfo .head { width:95%;background: #fff; margin:20rpx ;padding: 20rpx 20rpx;align-items:center; border-radius: 10rpx;}
.expressinfo .head .f1{margin-right:20rpx;border-bottom:1px solid #E2E2E2; height: 60rpx;}
.expressinfo .head .f1 image{width:100%;height:100%}
.expressinfo .head .f2{display:flex;font-size:30rpx;color:#999999; margin-top: 20rpx;}
.expressinfo .head .f2 .t1{margin-bottom:8rpx}
.expressinfo .content{ width: 95%; margin: 20rpx; border-radius: 10rpx;  background: #fff;display:flex;flex-direction:column;color: #979797;padding:20rpx 40rpx; padding-bottom: 100rpx;;}
.expressinfo .content .on{color: #23aa5e;}
.expressinfo .content .item{display:flex;width: 96%;  margin: 0 2%;border-left: 1px #dadada solid;padding:10rpx 0}
.expressinfo .content .item .f1{ width:40rpx;flex-shrink:0;position:relative}
.expressinfo .content image{width: 30rpx; height: 30rpx; position: absolute; left: -16rpx; top: 22rpx;}
/*.content .on image{ top:-1rpx}*/
.expressinfo .content .item .f1 image{ width: 30rpx; height: 30rpx;}

.expressinfo .content .item .f2{display:flex;flex-direction:column;flex:auto;}
.expressinfo .content .item .f2 .t1{font-size: 30rpx;}
.expressinfo .content .item .f2 .t1{font-size: 26rpx;}

.head .jiantou{ width: 124rpx; height: 16rpx;}
.head .t2_box{ width: 33.33%; text-align: center; font-size: 36rpx; color:#222} 
.head .t2_box .t2{ display: flex; justify-content: center; font-size: 24rpx;color:#888; margin-top: 10rpx;} 

.submit{ width:200rpx;height:80rpx; line-height: 80rpx; text-align:center;border-radius:10rpx; color: #fff;font-weight:bold; margin-top:60rpx; border: none; }

</style>