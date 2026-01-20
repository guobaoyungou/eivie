<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待审核','寄存中','已取出','驳回']" :itemst="['all','0','1','2','3']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view style="width:100%;height:100rpx"></view>
		<!-- #ifndef H5 || APP-PLUS -->
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入寄存名称、寄存人姓名或手机号搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
			</view>
		</view>
		<!--  #endif -->
		<view class="order-content">
			<block>
				<view class="order-box" v-for="(item2, idx) in datalist" :key="idx">
					<block>
						<view class="content">
							<view class="pic" @tap="goto" :data-url="'depositorderdetail?id=' + item2.id">
								<image :src="item2.pic" class="img"></image>
							</view>
							<view class="detail" @tap="goto" :data-url="'depositorderdetail?id=' + item2.id">
								<text class="t1">{{item2.name}}</text>
								<text class="t2">寄存人：{{item2.linkman}} {{item2.tel}}</text>
								<text class="t2">数量：{{item2.num}}</text>
								<text class="t2">存入时间：{{dateFormat(item2.createtime)}}</text>
							</view>
							<view v-if="item2.status==0" class="takeout st0" :data-orderid="item2.id">待审核</view>
							<view v-if="item2.status==1" class="takeout" @tap="takeout" :data-orderid="item2.id" :data-num="item2.num"><image :src="pre_url+'/static/img/deposit_takeout.png'" class="img"/>取出</view>
							<view v-if="item2.status==2" class="takeout st2" :data-orderid="item2.id">已取走</view>
							<view v-if="item2.status==3" class="takeout st3" :data-orderid="item2.id">未通过</view>
							<view v-if="item2.status==4" class="takeout st2" :data-orderid="item2.id">已过期</view>
						</view>
					</block>
				</view>
			</block>
			<!-- <view class="">
				<view class="op">
					<view @tap.stop="goto" :data-url="'orderdetail?bid='" class="btn2">寄存记录</view>
					<view @tap.stop="goto" :data-url="'add?bid='" class="btn2">我要寄存</view>
					<view @tap.stop="takeout" data-orderid="0" class="btn1" :style="{background:t('color1')}">一键取出</view>
				</view>
			</view> -->
		</view>
		<!-- 弹框 -->
		<view v-if="boxShow" class="" @touchmove.stop.prevent="disabledScroll">
			<view class="popup__overlay" @tap.stop="handleClickMask"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请输入取出数量</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
						@tap.stop="handleClickMask" />
				</view>
				<view class="popup__content takeoutBox">
					<form @submit="formSubmit" @reset="formReset" report-submit="true">
						<view class="orderinfo">
							
							<view class="item">
								<text class="t1">取出数量</text>
								<input class="t2" type="text" placeholder="请输入要取出的数量" placeholder-style="font-size:28rpx;color:#BBBBBB" name="numbers" :value="num"></input>
							</view>
						</view>
						<button class="btn" form-type="submit" :style="{background:t('color1')}">确定</button>
						
					</form>
				</view>
			</view>
		</view>
		
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
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

			st: 'all',
      datalist: [],
      pagenum: 1,
      nomore: false,
			nodata:false,
			boxShow:false,
			num:1,
			orderid:0,
			keyword:'',
			pre_url:app.globalData.pre_url,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt && this.opt.st){
			this.st = this.opt.st;
		}
		this.getdata();
  },
	onShow:function (opt) {
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
	onNavigationBarSearchInputConfirmed:function(e){
		this.searchConfirm({detail:{value:e.text}});
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
      app.post('ApiAdminRestaurantDepositOrder/index', {keyword:that.keyword,st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.datalist;
        if (pagenum == 1) {
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
   handleClickMask: function() {
   	this.boxShow = !this.boxShow;
   },
   takeout: function (e) {
      var that = this;
      this.orderid = e.currentTarget.dataset.orderid;
			 this.boxShow = true; //显示弹框
			 this.num = e.currentTarget.dataset.num;
   },
		disabledScroll: function (e) {
			return false;
		},
	 formSubmit: function (e) {
	 	 var that = this;
	 	 var formdata = e.detail.value;
	 	 //alert(formdata.numbers);
	 	app.post('ApiAdminRestaurantDepositOrder/takeout', {orderid:that.orderid,numbers:formdata.numbers}, function (data) {
	 		if(data.status== 0){
	 			app.alert(data.msg);return;
	 		}
	 	  app.success(data.msg);
	 	  setTimeout(function () {
	 		  that.boxShow = false; //隐藏弹框
	 			that.getdata();
	 	  }, 1000);
	 	});
	 },
		changetab: function (st) {
		  this.st = st;
		  uni.pageScrollTo({
		    scrollTop: 0,
		    duration: 0
		  });
		  this.getdata(false);
		},
		searchConfirm:function(e){
			this.keyword = e.detail.value;
      this.getdata(false);
		}
  }
};
</script>
<style>
.container{ width:100%;}
.topsearch{width:94%;margin:10rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.order-content{display:flex;flex-direction:column}
.order-box{ width: 94%;margin:0 3%;margin-top:20rpx;padding:6rpx 0; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width: 94%;margin:0 3%;border-bottom: 1px #f4f4f4 solid; height:90rpx; line-height: 90rpx; overflow: hidden; color: #999;}
.order-box .head .f1{flex:1;display:flex;align-items:center;color:#222;font-weight:bold}
.order-box .head .f1 image{width:56rpx;height:56rpx;margin-right:20rpx;border-radius:50%}
.order-box .head .st0{ width: 140rpx; color: #ff8758; text-align: right; }
.order-box .head .st1{ width: 140rpx; color: #ffc702; text-align: right; }
.order-box .head .st2{ width: 140rpx; color: #ff4246; text-align: right; }
.order-box .head .st3{ width: 140rpx; color: #999; text-align: right; }
.order-box .head .st4{ width: 140rpx; color: #bbb; text-align: right; }

.order-box .content{display:flex;width: 100%; padding:16rpx 0px 16rpx 20rpx;border-bottom: 0 #f4f4f4 dashed;position:relative}
.order-box .content:last-child{ border-bottom: 0; }
.order-box .content .pic{ width: 140rpx; height: 140rpx;}
.order-box .content .pic .img{ width: 140rpx; height: 140rpx;}
.order-box .content .detail{display:flex;flex-direction:column;margin-left:20rpx;flex:1;margin-top:6rpx;}
.order-box .content .detail .t1{font-size:28rpx;font-weight:bold;height:40rpx;line-height:40rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.order-box .content .detail .t2{height: 36rpx;line-height: 36rpx;color: #999;overflow: hidden;font-size: 22rpx;}
.order-box .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.order-box .content .takeout{display:flex;align-items:center;justify-content:center;padding:0 24rpx;height:52rpx;position:absolute;top:50%;margin-top:-26rpx;right:0;border-radius:26rpx 0 0 26rpx;background:#FFE8E1;color:#222222;font-size:24rpx;font-weight:bold}
.order-box .content .takeout .img{width:28rpx;height:28rpx;margin-right:6rpx}
.order-box .content .takeout.st0{color:#f55}
.order-box .content .takeout.st2{background:#F7F7F7;color:#BBBBBB}
.order-box .content .takeout.st3{background:#F7F7F7;color:#888}

.order-box .bottom{ width:100%; padding:20rpx; border-top: 0 #f4f4f4 solid; color: #555;}
.op{ display:flex;justify-content:flex-end;align-items:center;width:100%; padding:20rpx; border-top: 0 #f4f4f4 solid; color: #555; position: fixed; bottom: 0; left: 0; background-color: #fff;}

.btn1{margin-left:20rpx;width:200rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:44rpx;text-align:center;font-weight:bold}
.btn2{margin-left:20rpx;width:200rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;font-weight:bold;border-radius:44rpx;text-align:center}

.takeoutBox .btn {border-radius:44rpx; margin: 0 auto; width: 96%; color: #FFF;}
.takeoutBox { padding-bottom: 30rpx;}
.orderinfo{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:0px dashed #ededed;overflow:hidden}
.orderinfo .item{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;flex-shrink:0}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}.popup__modal{ min-height: 0;position: fixed;} 
</style>