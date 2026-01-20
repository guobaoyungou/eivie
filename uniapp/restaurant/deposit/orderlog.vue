<template>
<view class="container">
	<block v-if="isload">
		<view class="order-content">
				<view class="order-box">
					<view class="head">
						<view class="f1" @tap.stop="goto" :data-url="data.bid!=0?'/pagesExt/business/index?bid=' + data.bid:'/pages/index/index'">
							<image :src="data.binfo.logo"></image>
							<text>{{data.binfo.name}}</text>
							<text class="flex1"></text>
							<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
						</view>
					</view>
					<view class="content" :style="idx+1==data.procount?'border-bottom:none':''">
						<view class="pic">
							<image :src="data.pic" class="img"></image>
						</view>
						<view class="detail">
							<text class="t1">{{data.name}}</text>
							<text class="t2">数量：{{data.num}}</text>
							<text class="t2">存入时间：{{dateFormat(data.createtime)}}</text>
						</view>
						<view v-if="data.status==0" class="takeout st0" :data-orderid="data.id">审核中</view>
						<view v-if="data.status==1" class="takeout" >寄存中</view>
						<view v-if="data.status==2" class="takeout st2" :data-orderid="data.id">已取走</view>
						<view v-if="data.status==3" class="takeout st3" :data-orderid="data.id">未通过</view>
						<view v-if="data.status==4" class="takeout st4" :data-orderid="data.id">已过期</view>
					</view>
					<view>备注：{{data.message}}</view>
					<view class="op">
						<view v-if="data.status==1" @tap.stop="takeout" :data-bid="data.bid" data-orderid="0" class="btn1" :data-num="data.num" :style="{background:t('color1')}">取出</view>
					</view>
				</view>
				
				<view class="expressinfo" v-if="data.log.length >0">
					<view class="content">
						<view v-for="(item, index) in data.log" :key="index" :class="'item ' + (index==0?'on':'')">
							<view class="f1"><image :src="'/static/img/dot' + (index==0?'2':'1') + '.png'"></image></view>
							<view class="f2">
								<text class="t2">{{dateFormat(item.createtime)}}</text>
								<view class="t1">{{item.remark}}{{item.num}}件 <text v-if="item.tablename">，桌台：{{item.tablename}}</text></view>
							</view>
						</view>
					</view>
				</view>
		</view>
		<nodata v-if="nodata"></nodata>
		
		<!-- 弹框 -->
		<view v-if="boxShow" class="">
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

      data: {},
      pagenum: 1,
      nomore: false,
			nodata:false,
			boxShow:false,
			num:1,
			pre_url:app.globalData.pre_url,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt && this.opt.st){
			this.st = this.opt.st;
		}
		if(!this.opt.id){
			app.alert('缺少参数');return;
		}
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    
  },
  methods: {
    getdata: function () {
      var that = this;
			that.nodata = false;
			that.loading = true;
      app.post('ApiRestaurantDeposit/orderlog', {id: that.opt.id}, function (res) {
				that.loading = false;
        that.data = res.data;
        that.loaded();
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
		formSubmit: function (e) {
			 var that = this;
			 var formdata = e.detail.value;
			 //alert(formdata.numbers);
			
			app.post('ApiRestaurantDeposit/takeout', {bid:that.data.bid,orderid:that.data.id,numbers:formdata.numbers}, function (data) {
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
  }
};
</script>
<style>
.container{ width:100%;}
.order-content{display:flex;flex-direction:column}
.order-box{ width: 96%;margin:0 2%;margin-top:20rpx;padding:6rpx 20rpx; background: #fff;border-radius:8px}
.order-box .head{ display:flex;border-bottom: 1px #f4f4f4 solid; height:90rpx; line-height: 90rpx; overflow: hidden; color: #999;}
.order-box .head .f1{flex:1;display:flex;align-items:center;color:#222;font-weight:bold}
.order-box .head .f1 image{width:56rpx;height:56rpx;margin-right:20rpx;border-radius:50%}
.order-box .head .st0{ width: 140rpx; color: #ff8758; text-align: right; }
.order-box .head .st1{ width: 140rpx; color: #ffc702; text-align: right; }
.order-box .head .st2{ width: 140rpx; color: #ff4246; text-align: right; }
.order-box .head .st3{ width: 140rpx; color: #999; text-align: right; }
.order-box .head .st4{ width: 140rpx; color: #bbb; text-align: right; }

.order-box .content{display:flex;width: 100%; border-bottom: 0 #f4f4f4 dashed;position:relative; padding: 20rpx 0;}
.order-box .content:last-child{ border-bottom: 0; }
.order-box .content .pic{ width: 120rpx; height: 120rpx;}
.order-box .content .pic .img{ width: 120rpx; height: 120rpx;}
.order-box .content .detail{display:flex;flex-direction:column;margin-left:20rpx;flex:1;margin-top:6rpx}
.order-box .content .detail .t1{font-size:28rpx;font-weight:bold;height:40rpx;line-height:40rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.order-box .content .detail .t2{height: 36rpx;line-height: 36rpx;color: #999;overflow: hidden;font-size: 22rpx;}
.order-box .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.order-box .content .takeout{display:flex;align-items:center;justify-content:center;padding:0 24rpx;height:52rpx;position:absolute;top:50%;margin-top:-26rpx;right:0;border-radius:26rpx 0 0 26rpx;background:#FFE8E1;color:#222222;font-size:24rpx;font-weight:bold;
    margin-right: -10px}
.order-box .content .takeout .img{width:28rpx;height:28rpx;margin-right:6rpx}
.order-box .content .takeout.st0{color:#f55}
.order-box .content .takeout.st2{background:#F7F7F7;color:#BBBBBB}
.order-box .content .takeout.st3{background:#F7F7F7;color:#888}
.order-box .content .takeout.st4{background:#F7F7F7;color:#808080}

.order-box .bottom{ width:100%; padding:20rpx; border-top: 0 #f4f4f4 solid; color: #555;}
.order-box .op{ display:flex;justify-content:flex-end;align-items:center;width:100%; padding:20rpx 0; border-top: 0 #f4f4f4 solid; color: #555;}

.btn1{margin-left:20rpx;width:200rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:44rpx;text-align:center;font-weight:bold}
.btn2{margin-left:20rpx;width:200rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;font-weight:bold;border-radius:44rpx;text-align:center}

.expressinfo { width: 96%;margin:0 2%;margin-top:20rpx;padding:6rpx 0;padding:6rpx 0; background: #fff;border-radius:8px}
.expressinfo .content{ width: 100%;  background: #fff;display:flex;flex-direction:column;color: #979797;padding:20rpx 40rpx}
.expressinfo .content .on{color: #23aa5e;}
.expressinfo .content .item{display:flex;width: 96%;  margin: 0 2%;border-left: 1px #dadada solid;padding:10rpx 0}
.expressinfo .content .item .f1{ width:40rpx;flex-shrink:0;position:relative}
.expressinfo .content image{width: 30rpx; height: 30rpx; position: absolute; left: -16rpx; top: 22rpx;}
.expressinfo .content .item .f1 image{ width: 30rpx; height: 30rpx;}
.expressinfo .content .item .f2{display:flex;flex-direction:column;flex:auto;}
.expressinfo .content .item .f2 .t1{font-size: 30rpx;}
.expressinfo .content .item .f2 .t1{font-size: 26rpx;}

.takeoutBox .btn {border-radius:44rpx; margin: 0 auto; width: 96%; color: #FFF;}
.takeoutBox { padding-bottom: 30rpx;}
.orderinfo{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:0px dashed #ededed;overflow:hidden}
.orderinfo .item{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;flex-shrink:0}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}
.popup__modal{ min-height: 0; position: fixed;}
</style>