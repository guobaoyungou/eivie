<template>
<view class="container">
	<block v-if="isload">
		<view class="myscore" :style="{background:t('color1')}">
			<view class="f1">
				我的返利
			</view>
			<view class="f2">{{totalmoney}}</view>
		</view>
	<!-- 	<view class="content" id="datalist">
			<block v-for="(item, index) in datalist" :key="index"> 
				<view class="item">
					<view class="f1">
							<text class="t1">{{item.remark}}</text>
							<text class="t2">{{item.createtime}}</text>
					</view>
					<view class="f2">
						<block v-if="item.money>0">
							<text class="t1">+{{item.money}}</text>
						</block>
						<block v-else>
							<text class="t2">{{item.money}}</text>
						</block>
					</view>
				</view>
			</block>
			<nodata v-if="nodata"></nodata>
		</view> -->
		
		<view class="order-box" v-for="(item, index) in datalist" :key="index">
			<view class="head">
				<view class="f1">订单号：{{item.ordernum}}</view>
				<view class="flex1"></view>
			</view>
			<view class="content" style="border-bottom:none">
				<view class="detail">
					<text class="t1">类型：{{item.typename}}</text>
				</view>
				
				<view class="detail">
					金额：<text class="t1" :style="'color:'+t('color1')">￥{{item.money}}</text>
				</view>
				<view class="detail">
					状态：<text class="t1" v-if="item.status ==1" style="color:green">已完成</text>
					<text class="t1" v-else style="color:orange">已退还</text>
				</view>
			
				<view class="detail">
					<text class="t1">创建时间：{{item.createtime}}</text>
				</view>
			</view>
			<nodata v-if="nodata"></nodata>
		</view>
		
		
		<nomore v-if="nomore"></nomore>
	</block>
	<uni-popup ref="popupMiandan" type='center'>
		<view class="popup-miandan-content">
			<view class="popup-miandan-price flex">
				<view class="price-num-text popup-color-text">{{totalmoney}}</view>
				<view class="right-text popup-color-text">元</view>
			</view>
			<view class="popup-leiji-miandan popup-color-text">累计返现金额</view>
			<!-- <view class="popup-guishu flex-col">
				<view class="top-text">{{set.popup_text1}}</view>
				<view class="bottom-text">{{set.popup_text2}}</view>
			</view> -->
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
      totalmoney: 0,
      nodata: false,
      nomore: false,
	  set:{},
	  fireworksShow:false,//烟花效果
	  isOnload:false,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
		this.isOnload = true;
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
  onHide(){
  	this.fireworksShow = false;
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
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiAdminQueueFree/getBusinessFanliLog', {st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.datalist;
        if (pagenum == 1) {
					uni.setNavigationBarTitle({
						title: that.t('排队免单') + '商户返利记录'
					});
					that.totalmoney = res.totalmoney;
					
					that.datalist = data;
					that.set = res.set;
					//显示弹窗
					if(that.isOnload){
						that.fireworksShow = false;
						that.$nextTick(() => {
							that.fireworksShow = true;
							that.$refs.popupMiandan.open();
							that.isOnload = false;
						})
					}
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
    changetab: function (e) {
      var st = e.currentTarget.dataset.st;
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
	popupMiandanclose(){
		this.$refs.popupMiandan.close();
	}
  }
};
</script>
<style>
.myscore{width:94%;margin:20rpx 3%;border-radius: 10rpx 56rpx 10rpx 10rpx;position:relative;display:flex;flex-direction:column;padding:70rpx 0}
.myscore .f1{margin:0 0 0 60rpx;color:rgba(255,255,255,0.8);font-size:24rpx;}
.myscore .f2{margin:20rpx 0 0 60rpx;color:#fff;font-size:64rpx;font-weight:bold; position: relative;}

.myscore .item {min-width: 50%;color: #FFFFFF;padding: 0 10%;margin-top: 30rpx;}
.myscore .item .label{font-size: 26rpx}
.myscore .item .value{font-size: 44rpx;margin-top: 10rpx;font-weight: bold;}

.myscore  .btn{height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;display: inline-flex;align-items: center;justify-content: center;margin-left: 10rpx;padding: 0 10rpx;}

.content{ width:94%;margin:0 3%;}
.content .item{width:100%;margin:20rpx 0;background:#fff;border-radius:5px;padding:20rpx 20rpx;display:flex;align-items:center}
.content .item .f1{flex:1;display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666}
.content .item .f1 .t3{color:#666666}
.content .item .f2{ flex:1;font-size:36rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}

.data-empty{background:#fff}
.btn-mini {right: 32rpx;top: 28rpx;width: 100rpx;height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;display: inline-flex;align-items: center;justify-content: center;position: absolute;}
.btn-xs{min-width: 100rpx;height: 50rpx;line-height: 50rpx;text-align: center;border: 1px solid #e6e6e6;border-radius: 10rpx;color: #fff;font-size: 24rpx;font-weight: bold;margin-bottom:10rpx;padding: 0 20rpx;}
.scoreL{flex: 1;padding-left: 60rpx;color: #FFFFFF;}
.score_txt{color:rgba(255,255,255,0.8);font-size:24rpx;padding-bottom: 14rpx;}
.score{color:#fff;font-size:64rpx;font-weight:bold;}
.scoreR{padding-right: 30rpx;align-self: flex-end;flex-wrap: wrap;}
.right-image{height: 32rpx;width: 32rpx;margin-left: 5rpx;}



.order-box{ width: 94%;margin:10rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width:100%; border-bottom: 1px #f4f4f4 solid; height: 70rpx; line-height: 70rpx; overflow: hidden; color: #999;}
.order-box .head .f1{display:flex;align-items:center;color:#333}
.order-box .head .f1 image{width:34rpx;height:34rpx;margin-right:4px}
.order-box .head .st0{ width: 140rpx; color: #ff8758; text-align: right; }
.order-box .content {line-height: 180%;}
/* 免单金额 */
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
</style>