<template>
	<view>
		<view class="content-view">
			<view class="top-view">
				<view class="flex-y-center">
					<text class="text1">{{product.title}}</text>
				</view>
				<view class="tisp-text">
					有效时间：{{product.yxq}}
				</view>
        <view class="tisp-text">
          配送方式：{{product.deliver_type_name}}
        </view>
        <view class="tisp-text" style="line-height: 34rpx;">
          套餐说明：{{product.desc}}
        </view>
			</view>
			<view class="list-view flex-col">
				<view class="list-view-title flex-y-center">
					<view class="text1">套餐商品</view>
				</view>
				<block v-for="(item,index) in cartlist">
					<view class="shop-options flex-bt">
            <view @tap.stop="changeradio2" :data-index="index" :data-index2="index" class="radio" :style="item.checked ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
            <image class="shop-image" :src="item.pic" :data-url="'pages/shop/product?id=' + item.id" @tap="goto"></image>
            <view class="shop-info-view" :data-url="'pages/shop/product?id=' + item.id" @tap="goto">
              <view class="shop-title">{{item.name}}</view>
              <view class="shop-tisp"></view>
              <view class="shop-tisp" style="color: red">￥{{item.sell_price}}</view>
            </view>
            <view class="addnum">
              <view class="minus" @click="gwcminus(item.id)">-</view>
              <input class="i input" type="number" :value="guige_num[item.id]" :data-gid="item.id" @input="gwcinput"></input>
              <view class="plus" @click="gwcplus(item.id)">+</view>
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
            <view @tap.stop="changeradioAll" class="radio" :style="allchecked ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
            <view @tap.stop="changeradioAll" class="text0">全选</view>
						<text class="text1" style="margin-left: 40rpx">总价：￥</text>
						<text class="text2">{{price_total}}</text>
					</view>
				</view>
				<view class="quedingbut" @click="tobuy">确定</view>
			</view>
		</view>
    <button class="covermy" @tap="goto" data-url="orderlist" :style="'background:'+t('color1')">我的订单</button>
	</view>
</template>

<script>
	var app = getApp();
	export default{
		data(){
			return{
				pre_url:app.globalData.pre_url,
				opt:{},
        cartlist:[],
				product:{'title':'加载中..','yxq':'加载中..','deliver_type_name':'加载中..','desc':'加载中..'},
				guige_num:{},
				num_total:0,
				can_buy:0,
				price_total:0.00,
				need_num:0,
        allchecked:true,
        xzproids:[],
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		methods:{
			getdata: function () {
				var that = this;
				var id = that.opt.id;
				that.loading = true;
				app.get('ApiProductThali/detail', {id: id}, function (res) {
					that.loading = false;
					if (res.status == 0) {
						app.alert(res.msg, function() {
						 app.goback();
						});
						return;
					}
					that.cartlist = res.productlist;
					that.product = res.thali;
          that.guige_num = res.guige_num;
          that.changetotal();
					//that.loaded({title:res.product.title});
				});
			},
      //单选
      changeradio2: function (e) {
        var that = this;
        var index = e.currentTarget.dataset.index;
        var cartlist = that.cartlist;
        var checked = cartlist[index].checked;
        var isbx = cartlist[index].isbx;
        if(checked){
          if(isbx){
            app.error('此商品必须选择不能取消');
            return;
          }
          cartlist[index].checked = false;
        }else{
          cartlist[index].checked = true;
        }
        that.cartlist = cartlist;
        that.changetotal();
      },
      //全选
      changeradioAll:function(){
        var that = this;
        var cartlist = that.cartlist;
        var allchecked = that.allchecked
        for(var i in cartlist){
          //判断是否是不用必选
          if(cartlist[i].isbx == false){
            cartlist[i].checked = allchecked ? false : true;
          }
        }
        that.cartlist = cartlist;
        that.allchecked = allchecked ? false : true;
        that.changetotal();
      },
			//计算价格
			changetotal:function(){
        var cartlist = this.cartlist;
        this.price_total = 0.00;
        this.xzproids = [];
        for(var i in cartlist){
          if(cartlist[i].checked == true){
            this.xzproids.push(cartlist[i].id);
            this.price_total += cartlist[i].sell_price * cartlist[i].num;
          }
        }
        this.price_total = this.price_total.toFixed(2);
			},
			tobuy: function (e) {
			  var that = this;
			  var xzproids = that.xzproids;
			  var id = that.product.id;
        var cartlist = that.cartlist;
        var guige_num = JSON.stringify(that.guige_num);
        if(!xzproids || xzproids == '' || xzproids ==undefined){
          app.error('最少选择一个商品');
          return;
        }
        for(var i in cartlist){
          if((cartlist[i].isbx == true) && (!cartlist[i].num || cartlist[i].num <= 0 || cartlist[i].num =='' || cartlist[i].num == undefined)){
            app.error('必选商品数量至少是1个');
            return;
          }
        }
			  app.goto('buy?id=' + id + '&xzproids=' + xzproids+ '&guige_num=' + guige_num);
			},
      //加
      gwcplus: function (gid) {
        var guige_num = this.guige_num;
        var num_total = this.num_total;
        var perlimit = this.product.perlimit;
        guige_num[gid] = parseInt(guige_num[gid]) || 0;
        if(num_total>=perlimit){
          return;
        }
        guige_num[gid] = guige_num[gid] + 1;
        num_total = num_total+1;
        this.num_total = num_total;
        this.guige_num = guige_num
        var cartlist = this.cartlist;
        for(var i in cartlist){
          if(cartlist[i].id == gid){
            cartlist[i].num = guige_num[gid];
            if(guige_num[gid] > 0){
              cartlist[i].checked = true;
            }
          }
        }
        this.changetotal();
      },
      //减
      gwcminus: function (gid) {
        var guige_num = this.guige_num;
        var num_total = this.num_total;
        guige_num[gid] = parseInt(guige_num[gid]) || 0;
        if (guige_num[gid] <= 1) {
          return;
        }
        guige_num[gid] = guige_num[gid] - 1;
        num_total = num_total-1;
        this.num_total = num_total;
        this.guige_num = guige_num
        var cartlist = this.cartlist;
        for(var i in cartlist){
          if(cartlist[i].id == gid){
            if(cartlist[i].isbx == true && (!guige_num[gid] || guige_num[gid] <= 0)){
              app.error('必选商品数量至少是1个');
              break;
            }else if(cartlist[i].isbx == false && (!guige_num[gid] || guige_num[gid] <= 0)){
              cartlist[i].checked = false;
            }
            if(guige_num[gid] > 0){
              cartlist[i].checked = true;
            }

            cartlist[i].num = guige_num[gid];
          }
        }
        this.changetotal();
      },
      //输入
      gwcinput: function (e) {
        var guige_num = this.guige_num;
        var gid = e.target.dataset.gid;
        var gwcnum = parseInt(e.detail.value);
        if (gwcnum <= 0 ) {
          gwcnum = 1;
        };
        //异步赋值
        setTimeout(() => {
          guige_num[gid] = gwcnum;
          this.guige_num = guige_num;
        }, 0)
        var cartlist = this.cartlist;
        for(var i in cartlist){
          if(cartlist[i].id == gid){

            if(cartlist[i].isbx == true && (!gwcnum || gwcnum <= 0)){
              app.error('必选商品数量至少是1个');
            }else if(cartlist[i].isbx == false && (!gwcnum || gwcnum <= 0)){
              cartlist[i].checked = false;
            }
            if(gwcnum > 0){
              cartlist[i].checked = true;
            }
            cartlist[i].num = (!gwcnum) ? 0 : gwcnum;
          }
        }
        this.changetotal();
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
	.content-view{width: 100%;height: auto;position: absolute;top: 20rpx;}
	.content-view .top-view{width: 94%;background: #FFFFFF;border-radius: 16rpx;margin: 0 auto;height: auto;padding:30rpx;position: relative;}
	.content-view .top-view .text1{color: #222229;font-size: 36rpx;font-weight: bold;}
	.content-view .top-view .text2{color: #F94B30;font-size: 28rpx;font-weight: bold;}
	.content-view .top-view .tisp-text{font-size: 24rpx;padding-top: 20rpx;}
	.content-view .top-view .top-view-bottom{width: 400rpx;height: 60rpx;background: linear-gradient(90deg, #FFF3F1 0%, rgba(255, 243, 241, 0.4) 52%, #FFF3F1 100%);
	line-height: 60rpx;color: #C96353;text-align: center;font-size: 24rpx;position: absolute;bottom: 0;left:50%;;border-radius: 70rpx 70rpx 0rpx 0rpx;transform: translate(-50%);}
	.content-view .top-view  .pos-icon{width: 270rpx;height: 340rpx;position: absolute;right: 30rpx;top:-240rpx;}
	.content-view .top-view  .pos-icon image{width: 100%;height: 100%;}
	.list-view{width: 94%;background: #FFFFFF;border-radius: 16rpx;margin: 30rpx auto 0rpx;padding:20rpx 30rpx;}
	.list-view .list-view-title{}
	.list-view .list-view-title .text1{color: #1A1A1A;font-size: 32rpx;font-weight: bold;}
	.list-view .list-view-title .text2{color: #AAAAAA;font-size: 24rpx;padding: 0rpx 20rpx;}
	.shop-options{width: 100%;height:auto;align-items: center;justify-content: flex-start;position: relative;margin-top: 30rpx;border-bottom:1px #f3f3f3 solid;padding-bottom: 20rpx;}
	.shop-options .shop-image{width: 145rpx;height: 145rpx;border-radius: 16rpx;}
	.shop-options .shop-info-view{height: 100%;flex:1;padding: 20rpx;display: flex;flex-direction: column;align-items: flex-start;justify-content: flex-start;
	}
	.shop-info-view .shop-title{color: #1A1A1A;font-size: 24rpx;font-weight: bold;height: 35px;overflow: hidden;text-overflow: ellipsis;}
	.shop-info-view .shop-tisp{color: #AAAAAA;font-size: 24rpx;margin-top: 20rpx;}
	.addnum {width: auto;position: absolute;right:30rpx;bottom: 30rpx;font-size: 26rpx;color: #666;display: flex;align-items: center;justify-content:flex-end;padding:0rpx 8rpx;}
	.addnum .plus {width: 43rpx;height: 43rpx;background: #FD4A46;color: #FFFFFF;	border-radius: 8rpx;display: flex;align-items: center;justify-content: center;font-size: 26rpx}
	.addnum .minus {width: 43rpx;height: 43rpx;background: #FFFFFF;	color: #FD4A46;	border: 1px solid #FD4A46;border-radius: 8rpx;display: flex;align-items: center;
		justify-content: center;font-size: 24rpx}
	.addnum .i {padding: 0 20rpx;color: #999999;font-size: 26rpx}
	.but-class{width: 100%;height: 165rpx;background: #fff;position: fixed;bottom: 0rpx;padding: 20rpx;border-top: 1px #ebebeb solid;}
	.but-class .but-content{width: 94%;margin: 0 auto;height: calc(165rpx - env(safe-area-inset-bottom));display: flex;align-items: center;justify-content: space-between;}
	.but-class .but-content .quedingbut{width: 230rpx;height:76rpx;text-align: center;display: flex;align-items: center;justify-content: center;border-radius: 100rpx;font-size: 30rpx;
	font-weight: bold;color: #FFFFFF;background: #F94B30;}
	.but-class .but-content .quedingbut2{width: 360rpx;height:76rpx;text-align: center;display: flex;align-items: center;justify-content: center;border-radius: 100rpx;font-size: 30rpx;
	font-weight: bold;color: #FFFFFF;background: gray;}
	.but-left-info{}
	.but-left-info .info1{display: flex;align-items: flex-end;justify-content: flex-start;}
	.but-left-info .info1 .text1{color: #F94B30;font-size: 24rpx;}
	.but-left-info .info1 .text2{color: #F94B30;font-size: 36rpx;font-weight: bold;}
	.but-left-info .info2{color: rgba(34, 34, 34, 0.6);font-size: 24rpx;}
	.addnum .input{flex:1;width:120rpx;border:0;text-align:center;color:#2B2B2B;font-size:24rpx}
  .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
  .radio .radio-img{width:100%;height:100%}
  .but-left-info .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:10rpx}
  .but-left-info .radio .radio-img{width:100%;height:100%}
  .but-left-info .text0{color:#666666;font-size:24rpx;}
  .covermy{position:absolute;z-index:99999;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;z-index:9999;top:260rpx;right:15rpx;color:#fff;background-color:rgba(17,17,17,0.3);width:100rpx;height:100rpx;font-size:26rpx;border-radius:100rpx;padding: 15rpx;line-height:34rpx}
</style>