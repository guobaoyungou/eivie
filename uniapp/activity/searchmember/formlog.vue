<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待确认','已确认','已驳回','待支付']" :itemst="['all','0','1','2','10']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view style="width:100%;height:90rpx"></view>
		<view class="content" id="datalist">
			<view class="item" @tap="goto" :data-url="'formdetail?id=' + item.id" v-for="(item, index) in datalist" :key="index">
				<view class="f1">
						<text class="t1">{{item.title}}</text>
						<text class="t2">提交时间：{{item.createtime}}</text>
				</view>
				<view class="f2">
					<text class="t1" v-if="item.status==0 && (!item.payorderid||item.paystatus==1)" style="color:#88e">待确认</text>
					<text class="t1" v-if="item.status==0 && item.payorderid && item.paystatus==0" style="color:red">待支付</text>
					<text class="t1" v-if="item.status==1" style="color:green">已确认</text>
					<text class="t1" v-if="item.status==2" style="color:red">已驳回</text>
				</view>
			</view>
		</view>
		<!-- <view v-if="showtabbar" class="btn-add" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" @tap.stop="goto" :data-url="formurl"><image src="/static/img/add.png" style="width:28rpx;height:28rpx;margin-right:6rpx"/>新增档案</view>

		<view class="tabbar" v-if="showtabbar">
			<view class="tabbar-bot"></view>
			<view class="tabbar-bar" style="background-color:#ffffff">
				<view @tap="goto" data-url="dating" data-opentype="reLaunch" class="tabbar-item">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/home.png'"></image>
					</view>
					<view class="tabbar-text">大厅</view>
				</view>
				<view @tap="goto" data-url="jdorderlist" data-opentype="reLaunch" class="tabbar-item">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/order.png'"></image>
					</view>
					<view class="tabbar-text">订单</view>
				</view>
				<view @tap="goto" data-url="jdorderlist?st=3" data-opentype="reLaunch" class="tabbar-item">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/orderwc.png'"></image>
					</view>
					<view class="tabbar-text">已完成</view>
				</view>
				<view @tap="goto" data-url="formlog" data-opentype="reLaunch" class="tabbar-item">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/dangan2.png'"></image>
					</view>
					<view class="tabbar-text active">档案</view>
				</view>
				<view @tap="goto" data-url="my" data-opentype="reLaunch" class="tabbar-item">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/my.png'"></image>
					</view>
					<view class="tabbar-text">我的</view>
				</view>
			</view>
		</view> -->
	</block>
	<nodata v-if="nodata"></nodata>
	<nomore v-if="nomore"></nomore>
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

      st: 'all',
      datalist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,
			formurl:'',
			showtabbar:false,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 'all';
		if(this.opt.tel){
			this.showtabbar = false;
		}else{
			this.showtabbar = true;
		}
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
			this.nodata = false;
			this.nomore = false;
			this.loading = true;
      app.post('ApiSearchMember/formlog', {st: st,pagenum: pagenum,tel:this.opt.tel}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1) {
					that.datalist = data;
					that.formurl = res.formurl;
					console.log(that.formurl)
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
}
</script>
<style>

@import "../yuyue/common.css";
	.content{ width:100%;margin:0;}
	.content .item{ width:94%;margin:20rpx 3% 40rpx 3%;background:#fff;border-radius:16rpx;padding:30rpx 30rpx;display:flex;align-items:center;}
	.content .item:last-child{border:0}
	.content .item .f1{width:80%;display:flex;flex-direction:column}
	.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
	.content .item .f1 .t2{color:#666666;margin-top:10rpx}
	.content .item .f1 .t3{color:#666666}
	.content .item .f2{width:20%;font-size:32rpx;text-align:right}
	.content .item .f2 .t1{color:#03bc01}
	.content .item .f2 .t2{color:#000000}
	.content .item .f3{ flex:1;font-size:30rpx;text-align:right}
	.content .item .f3 .t1{color:#03bc01}
	.content .item .f3 .t2{color:#000000}
	.container .btn-add{width:90%;max-width:700px;margin:0 auto;height:96rpx;line-height:96rpx;text-align:center;color:#fff;font-size:30rpx;font-weight:bold;border-radius:40rpx;position: fixed;left:0px;right:0;bottom:110rpx;margin-bottom:20rpx;z-index:9}
</style>