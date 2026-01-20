<template>
<view class="container">
	<block v-if="isload">
		<view class="ordertop" :style="'background:url(' + pre_url + '/static/img/ordertop.png);background-size:100%'">
			<view class="f1" v-if="detail.status==1">
				<view class="t1">活动待核销</view>
			</view>
			<view class="f1" v-if="detail.status==2">
				<view class="t1">活动已核销</view>
			</view>
		</view>
		<view class="product">
			<view class="content">
				<view @tap="goto" :data-url="'index?id=' + detail.hid">
					<image :src="detail.pic"></image>
				</view>
				<view class="detail">
					<text class="t1">{{detail.name}}</text>
		
					<view class="t3"><text class="x1 flex1">￥{{detail.price}}</text></view>
					<view class="t2"><text>已邀请{{detail.yqnum}}人</text></view>
				</view>
			</view>
		</view>
	
		<view class="orderinfo" v-if="detail.yqnum > 0">
			<view class="item flex-y-center" style="padding: 10rpx 0;">
				<text class="t1">邀请人员</text>
				<view class="t2" user-select="true" selectable="true" style="overflow: hidden;">
					<block v-for="(item,index) in detail.yqlist">
						<image class="yq_image" :src="item.headimg"/>
					</block>
					
				</view>
			</view>
			
		</view>
		<view class="orderinfo">
			<view class="item">
				<text class="t1">订单编号</text>
				<text class="t2" user-select="true" selectable="true">{{detail.ordernum}}</text>
			</view>
			<view class="item">
				<text class="t1">下单时间</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>
			<view class="item" v-if="detail.status>0 && detail.paytime">
				<text class="t1">支付时间</text>
				<text class="t2">{{detail.paytime}}</text>
			</view>
			<view class="item" v-if="detail.status ==2 && detail.hxtime">
				<text class="t1">核销时间</text>
				<text class="t2">{{detail.hxtime}}</text>
			</view>
		</view>
		<view class="orderinfo">
			<view class="item" v-if="detail.price > 0">
				<text class="t1">支付金额</text>
				<text class="t2 red">¥{{detail.price}}</text>
			</view>
			<!-- <view class="item" v-if="detail.jxmc">
				<text class="t1">邀请人奖品</text>
				<text class="t2 red">{{detail.jxmc}}</text>
			</view> -->
			<view class="item">
				<text class="t1">订单状态</text>
				<text class="t2" v-if="detail.status==1">待核销</text>
				<text class="t2" v-if="detail.status==2">已核销</text>
			</view>
			
		</view>
		
		<!-- <view class="orderinfo" v-if="(detail.formdata).length > 0">
			<view class="item" v-for="item in detail.formdata" :key="index">
				<text class="t1">{{item[0]}}</text>
				<view class="t2" v-if="item[2]=='upload'"><image :src="item[1]" style="width:400rpx;height:auto" mode="widthFix" @tap="previewImage" :data-url="item[1]"/></view>
				<text class="t2" v-else user-select="true" selectable="true">{{item[1]}}</text>
			</view>
		</view> -->

		<view style="width:100%;height:160rpx"></view>

		<view class="bottom notabbarbot" v-if="detail.status!=2">
			
				<view class="btn2" @tap="showhxqr">核销码</view>
	
		</view>
		<uni-popup id="dialogHxqr" ref="dialogHxqr" type="dialog">
			<view class="hxqrbox">
				<image :src="detail.hexiaoqr" @tap="previewImage" :data-url="detail.hexiaoqr" class="img"/>
				<view class="txt">请出示核销码给核销员进行核销</view>
				<view class="close" @tap="closeHxqr">
					<image :src="pre_url+'/static/img/close2.png'" style="width:100%;height:100%"/>
				</view>
			</view>
		</uni-popup>
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
			textset:{},
			detail:{},
			team:{},
			storeinfo:{},
			shopset:{},
			invoice:0
    }
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function (option) {
			var that = this;
			that.loading = true;
			app.get('ApiHbtkActivity/orderdetail', {id: that.opt.id}, function (res) {
				that.loading = false;
				that.detail = res.detail;
				that.loaded();
			});
		},
    
		showhxqr:function(){
			this.$refs.dialogHxqr.open();
		},
		closeHxqr:function(){
			this.$refs.dialogHxqr.close();
		},
  }
};
</script>
<style>
.ordertop{width:100%;height:220rpx;padding:50rpx 0 0 70rpx}
.ordertop .f1{color:#fff}
.ordertop .f1 .t1{font-size:32rpx;height:60rpx;line-height:60rpx}
.ordertop .f1 .t2{font-size:24rpx}

.address{ display:flex;width: 100%; padding: 20rpx 3%; background: #FFF;}
.address .img{width:40rpx}
.address image{width:40rpx; height:40rpx;}
.address .info{flex:1;display:flex;flex-direction:column;}
.address .info .t1{font-size:28rpx;font-weight:bold;color:#333}
.address .info .t2{font-size:24rpx;color:#999}

.product{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.product .content{display:flex;position:relative;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed;position:relative}
.product .content:last-child{ border-bottom: 0; }
.product .content image{ width: 140rpx; height: 140rpx;}
.product .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.product .content .detail .t1{font-size:26rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .content .detail .t2{color: #999;font-size: 26rpx;margin-top: 10rpx;}
.product .content .detail .t3{display:flex;color: #ff4246;margin-top: 10rpx;}
.product .content .detail .t4{margin-top: 10rpx;}
.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.product .content .comment{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc702 solid; border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.orderinfo{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}

.bottom{ width: 100%;height:92rpx;padding: 0 20rpx;background: #fff; position: fixed; bottom: 0px; left: 0px;display:flex;justify-content:flex-end;}
.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;background:#FB4343;border-radius:3px;text-align:center}
.btn2{margin-top: 12rpx; margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.btn3{font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

.hxqrbox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx}
.hxqrbox .img{width:400rpx;height:400rpx}
.hxqrbox .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}
.hxqrbox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}
.module_num{display: flex;align-items: center;margin-top: 20rpx;}
.module_lable{font-size: 24rpx;color: #666;line-height: 24rpx;border-right: 1px solid #e0e0e0;padding: 0 15rpx 0 0;margin-right: 15rpx;}
.module_view{display: flex;flex: 1;align-items: center;}
.yq_image{height: 60rpx;width: 60rpx;border-radius: 100rpx;margin-right: 10rpx;}

</style>