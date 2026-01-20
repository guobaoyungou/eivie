<template>
	<view>
		<view class="banner-view">
			<view class="banner-text1 text-class">自选礼包</view>
			<view class="banner-text2 text-class">请选择心仪的商品下单~</view>
		</view>
		<view class="content-view">
			<view class="top-view">
				<view class="flex-y-center">
					<text class="text1">礼包单价：</text><text class="text2">￥{{product.sell_price}}</text>
				</view>
				<view class="tisp-text">
					本次购买礼包数量：{{product.perlimit}}
				</view>
				<view class="top-view-bottom">可从下方任选{{product.perlimit}}件商品</view>
				<view class="pos-icon">
					<image :src="pre_url+'/static/img/libao_liwu.png'"></image>
				</view>
			</view>
			<view class="list-view flex-col">
				<view class="list-view-title flex-y-center">
					<view class="text1">商品礼包</view>
					<view class="text2">升级会员获得更多商品权益</view>
				</view>
				<block v-for="(item,index) in guigelist">
					<view class="shop-options flex-bt">
						<image class="shop-image" :src="item.pic"></image>
						<view class="shop-info-view">
							<view class="shop-title">{{item.name}}</view>
							<view class="shop-tisp">{{item.ggname}}</view>
							<view class="shop-tisp">最大购买数量：{{item.max_num}}</view>
						</view>
						<view class="addnum">
							<view class="minus" @click="gwcminus(item.gid)">-</view>	
							<input class="i input" type="number" :value="guige_num[item.gid]" :data-gid="item.gid" @input="gwcinput"></input>
							<view class="plus" @click="gwcplus(item.gid)">+</view>
						</view>
					</view>
				</block>
			</view>
			<view style="width: 100%;height: 180rpx;"></view>
		</view>
		<view class="but-class">
			<view class="but-content">
				<view class="but-left-info flex-col">
					<view class="info1">
						<text class="text1">总价：￥</text>
						<text class="text2">{{price_total}}</text>
					</view>
					<view class="info2">还需要选择 {{need_num}} 种商品</view>
				</view>
				<view v-if="can_buy==1" class="quedingbut" @click="tobuy">确定</view>
				<view v-if="can_buy==0" class="quedingbut2">确定</view>
			</view>
		</view>
	</view>
</template>

<script>
	var app = getApp();
	export default{
		data(){
			return{
				pre_url:app.globalData.pre_url,
				opt:{},
				guigedata:[],
				guigelist:[],
				product:{},
				guige_num:{},
				num_total:0,
				can_buy:0,
				price_total:0,
				need_num:0
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		methods:{
			getdata: function () {
				var that = this;
				var id = that.opt.proid;
				that.loading = true;
				app.get('ApiTaocan/getproductdetail', {id: id}, function (res) {
					that.loading = false;
					if (res.status == 0) {
						app.alert(res.msg, function() {
						 app.goback();
						});
						return;
					}
					that.guigedata = res.guigedata;
					that.guigelist = res.guigelist;
					that.product = res.product;
					that.guige_num = res.guige_num;
					that.price_total = res.product.sell_price;
					that.need_num = res.product.perlimit;
					that.loaded({title:res.product.name,pic:res.product.pic});
				});
			},
			//加
			gwcplus: function (gid) {
				var guige_num = this.guige_num;
				var num_total = this.num_total;
				var perlimit = this.product.perlimit;
				if(num_total>=perlimit){
					return;
				}
			  guige_num[gid] = guige_num[gid] + 1;
			  if (guige_num[gid] > this.guigelist[gid].stock) {
				  guige_num[gid] = this.guigelist[gid].stock;
				  app.error('库存不足');
			    return;
			  }
			  if (guige_num[gid] > this.guigelist[gid].max_num) {
				  guige_num[gid] = this.guigelist[gid].max_num;
				  app.error('超出单品购买数量');
					return;
			  }
			  num_total = num_total+1;
			  this.num_total = num_total;
			  this.guige_num = guige_num
			  this.changetotal();
			},
			//减
			gwcminus: function (gid) {
			  var guige_num = this.guige_num;
			  var num_total = this.num_total;
			  if (guige_num[gid] <= 0) {
			    return;
			  }
			   guige_num[gid] = guige_num[gid] - 1;
			  num_total = num_total-1;
			  this.num_total = num_total;
			  this.guige_num = guige_num
			  this.changetotal();
			},
			//输入
			gwcinput: function (e) {
			   var guige_num = this.guige_num;
			   var gid = e.target.dataset.gid;
			  var gwcnum = parseInt(e.detail.value);
			  if (gwcnum < 0) {
				  gwcnum = 0;
			  };
			  if (gwcnum > this.guigelist[gid].stock) {
				  app.error('库存不足');
			    gwcnum = parseInt(this.guigelist[gid].stock);
			  }
			  if (gwcnum > this.guigelist[gid].max_num) {
				app.error('超出单品购买数量');
			    gwcnum = parseInt(this.guigelist[gid].max_num);
			  }
			  var perlimit = parseInt(this.product.perlimit);
			  
			  guige_num[gid] = gwcnum;
			  var num_total = 0;
			  console.log(guige_num);
			  var num_arr = Object.values(guige_num);
			  console.log(num_arr);
			  num_arr.forEach(function(value){
				 num_total = num_total + value; 
			  })
			  console.log(num_total+'>'+perlimit);
			  if(num_total>perlimit){
				  var guige_num = this.guige_num;
			  	guige_num[gid] = 0;
				var num_arr = Object.values(guige_num);
				console.log(num_arr);
				console.log(guige_num);
				var num_total = 0;
				num_arr.forEach(function(value){
					num_total = num_total + value; 
				})
				this.num_total = num_total;
				this.guige_num = guige_num;
				console.log('最终数量',this.guige_num);
				this.changetotal();
				app.error('选择数量超出礼包数量，请重新选择');
				return;
			  }
			  this.num_total = num_total;
			  this.guige_num = guige_num;
			  console.log('最终数量2',this.guige_num);
			  this.changetotal();
			},
			//修改数量计算价格
			changetotal:function(){
				var num_total = this.num_total;
				var perlimit = this.product.perlimit;
				var yushu = num_total % perlimit;
				var beishu = Math.floor(num_total / perlimit);
				// if(num_total>=perlimit && yushu==0){
				//   this.can_buy = 1;
				// }else{
				//   this.can_buy = 0;
				// }
				if(num_total==perlimit){
				  this.can_buy = 1;
				}else{
				  this.can_buy = 0;
				}
				if(beishu>0){
					var sell_price = this.product.sell_price;
					var price_total = sell_price*beishu;
					this.price_total = price_total;
				}
				var need_num = perlimit-(num_total-perlimit*beishu);
				if(beishu>=1 && need_num==perlimit){
					need_num = 0;
				}
				this.need_num = need_num;
			},
			tobuy: function (e) {
			  var that = this;
			  var num_total = that.num_total;
			  var proid = that.product.id;
			  var guige_num = JSON.stringify(that.guige_num);
			  app.goto('buy?proid=' + proid + '&guige_num=' + guige_num +'&num_total='+num_total);
			},
		}
	}
</script>

<style>
	.banner-view{width: 100%;background: linear-gradient(90deg, #FF774A -7%, #FE472E 100%), linear-gradient(270deg, #FED77A 0%, #FE9059 52%, #FF5941 100%);height: 380rpx;
	border-radius: 0rpx 0rpx 80rpx 80rpx;}
	.banner-view .text-class{padding: 0rpx 80rpx;}
	.banner-view .banner-text1{font-size: 48rpx;font-weight: bold;color: #FFFFFF;padding-top: 110rpx;}
	.banner-view .banner-text2{font-size: 28rpx;color: #FFFFFF;opacity: 0.7;padding-top: 10rpx;}
	.content-view{width: 100%;height: auto;position: absolute;top: 260rpx;}
	.content-view .top-view{width: 94%;background: #FFFFFF;border-radius: 16rpx;margin: 0 auto;height: 240rpx;padding:30rpx;position: relative;}
	.content-view .top-view .text1{color: #222229;font-size: 36rpx;font-weight: bold;}
	.content-view .top-view .text2{color: #F94B30;font-size: 28rpx;font-weight: bold;}
	.content-view .top-view .tisp-text{font-size: 24rpx;color: #888889;padding-top: 20rpx;}
	.content-view .top-view .top-view-bottom{width: 400rpx;height: 60rpx;background: linear-gradient(90deg, #FFF3F1 0%, rgba(255, 243, 241, 0.4) 52%, #FFF3F1 100%);
	line-height: 60rpx;color: #C96353;text-align: center;font-size: 24rpx;position: absolute;bottom: 0;left:50%;;border-radius: 70rpx 70rpx 0rpx 0rpx;transform: translate(-50%);}
	.content-view .top-view  .pos-icon{width: 270rpx;height: 340rpx;position: absolute;right: 30rpx;top:-240rpx;}
	.content-view .top-view  .pos-icon image{width: 100%;height: 100%;}
	.list-view{width: 94%;background: #FFFFFF;border-radius: 16rpx;margin: 30rpx auto 0rpx;padding:20rpx 30rpx;}
	.list-view .list-view-title{}
	.list-view .list-view-title .text1{color: #1A1A1A;font-size: 32rpx;font-weight: bold;}
	.list-view .list-view-title .text2{color: #AAAAAA;font-size: 24rpx;padding: 0rpx 20rpx;}
	.shop-options{width: 100%;height:auto;align-items: center;justify-content: flex-start;position: relative;margin-top: 30rpx;border-bottom:1px #f3f3f3 solid;padding-bottom: 20rpx;}
	.shop-options .shop-image{width: 145rpx;height: 145rpx;border-radius: 16rpx;border: 1px red solid;}
	.shop-options .shop-info-view{height: 100%;flex:1;padding: 20rpx;display: flex;flex-direction: column;align-items: flex-start;justify-content: flex-start;
	}
	.shop-info-view .shop-title{color: #1A1A1A;font-size: 24rpx;font-weight: bold;}
	.shop-info-view .shop-tisp{color: #AAAAAA;font-size: 24rpx;margin-top: 20rpx;}
	.addnum {width: auto;position: absolute;right:30rpx;bottom: 30rpx;font-size: 26rpx;color: #666;display: flex;align-items: center;justify-content:flex-end;padding:0rpx 8rpx;}
	.addnum .plus {width: 43rpx;height: 43rpx;background: #FD4A46;color: #FFFFFF;	border-radius: 8rpx;display: flex;align-items: center;justify-content: center;font-size: 26rpx}
	.addnum .minus {width: 43rpx;height: 43rpx;background: #FFFFFF;	color: #FD4A46;	border: 1px solid #FD4A46;border-radius: 8rpx;display: flex;align-items: center;
		justify-content: center;font-size: 26rpx}
	.addnum .i {padding: 0 20rpx;color: #999999;font-size: 26rpx}
	.but-class{width: 100%;height: 165rpx;background: #fff;position: fixed;bottom: 0rpx;padding: 20rpx;border-top: 1px #ebebeb solid;}
	.but-class .but-content{width: 94%;margin: 0 auto;height: calc(165rpx - env(safe-area-inset-bottom));display: flex;align-items: center;justify-content: space-between;}
	.but-class .but-content .quedingbut{width: 380rpx;height:50%;text-align: center;display: flex;align-items: center;justify-content: center;border-radius: 100rpx;font-size: 30rpx;
	font-weight: bold;color: #FFFFFF;background: #F94B30;}
	.but-class .but-content .quedingbut2{width: 380rpx;height:50%;text-align: center;display: flex;align-items: center;justify-content: center;border-radius: 100rpx;font-size: 30rpx;
	font-weight: bold;color: #FFFFFF;background: gray;}
	.but-left-info{}
	.but-left-info .info1{display: flex;align-items: flex-end;justify-content: flex-start;}
	.but-left-info .info1 .text1{color: #F94B30;font-size: 24rpx;}
	.but-left-info .info1 .text2{color: #F94B30;font-size: 36rpx;font-weight: bold;}
	.but-left-info .info2{color: rgba(34, 34, 34, 0.6);font-size: 24rpx;}
	.addnum .input{flex:1;width:120rpx;border:0;text-align:center;color:#2B2B2B;font-size:24rpx}
</style>