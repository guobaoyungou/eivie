<template>
<view class="container">
	<block v-if="isload">
		<view class="address-add flex-y-center" >
			<view class="f1">桌台信息</view>
			<view class="f2 flex1" v-if="tableinfo.id">
				<view style="font-weight:bold;color:#111111;font-size:30rpx">{{tableinfo.name}}<text style="font-size:24rpx;font-weight:normal;color:#666;margin-left:10rpx">{{tableinfo.seat}}人桌</text></view>
			</view>
			<view v-else class="f2 flex1">请扫描桌台二维码</view>
			<image :src="pre_url+'/static/img/arrowright.png'" class="f3"></image>
		</view>

		<view v-for="(buydata, index) in allbuydata" :key="index" class="buydata">
			<view class="buystatus flex flex-y-center"> <view class="leftline" :style="{backgroundColor:t('color1')}"></view>待下单</view>	
			<!-- <view class="btitle"><image class="img" :src="pre_url+'/static/img/ico-shop.png'"/>{{buydata.business.name}}</view> -->
			<view class="bcontent">
				<view class="product">
					<view v-for="(item, index2) in buydata.prodata" :key="index2" class="item " v-if="index2 < 4 || waitordershowall==true">
						<view class="flex">
							<view class="img" @tap="goto" :data-url="'product?id=' + item.product.id"><image :src="item.product.pic"></image></view>
							<view class="info flex1" style="position: relative;">
								<view class="f1">{{item.product.name}}</view>
								<view class="f2" v-if="item.guige.name">规格：{{item.guige.name}}{{item.jldata.jltitle}} <text v-if="item.jldata.njltitle">（{{item.jldata.njltitle}})</text></view>
								<!--套餐中产品展示-->
								<view class="f2" v-if=" item.guige.ggtext && item.guige.ggtext.length > 0">
									<view v-for="(gitem,index) in item.guige.ggtext">{{gitem}}</view>
								</view>
								<view class="f3">
									<text style="font-weight:bold;">
										￥{{parseFloat(parseFloat(item.guige.sell_price)+parseFloat(item.jldata.jlprice)).toFixed(2)}} 
									</text>					
									<text style="padding-left:20rpx"> × {{item.num}}</text>
									<text v-if="item.product.product_type && item.product.product_type ==1" style="font-size: 20rpx;">斤</text>
									
								</view>
								<view class="tip" v-if="item.product.select_type =='kx' || item.product.select_type =='bx'">
									<text v-if="item.product.select_type =='kx'">可选</text>
									<text v-if="item.product.select_type =='bx'">必选</text>
								</view>
								<!-- 删除按钮 -->
								<view  class="prodel" @tap="prodel" :data-key="index2" :data-bid="index" v-if="item.product.select_type =='kx' ">
									<image :src="pre_url+'/static/img/del.png'" class="img"/>删除
								</view>
								<!-- 删除按钮 -->
							</view>
							
						</view>
						<view class=" flex-y-center" style="color:#999999">
							<view>备注：</view> 
							<input type="text" class="flex1" placeholder="请输入您的口味或要求" @input="inputgoodsremark" :data-index="index" :data-index2="index2" placeholder-style="color:#cdcdcd;font-size:24rpx"></input>
						</view>	
					</view>	
					<view v-if="waitordershowall==false && buydata.prodata.length > 4" class="storeviewmore" @tap="doWaitorderShowAll">- 查看更多 - </view>
				</view>
		
			</view>
		</view>
		<view  class="buydata" v-for="(buydata, index) in oglist" >
			<view class="buystatus flex flex-y-center"> <view class="leftline" :style="{backgroundColor:t('color1')}"></view>已下单</view>	
			<!-- <view class="btitle"><image class="img" :src="pre_url+'/static/img/ico-shop.png'"/>{{buydata.business.name}}</view> -->
			<view class="bcontent">
				<view class="product">
					<view v-for="(item, index2) in buydata.prodata" :key="index2" class="item"  v-if="index2<4 || ordershowall==true">
						<view class="flex">
							<view class="img" @tap="goto" :data-url="'product?id=' + item.proid"><image :src="item.pic"></image></view>
							<view class="info flex1">
								<view class="f1">{{item.name}}</view>
								<view class="f2" v-if="item.ggname">规格：{{item.ggname}}<text v-if="item.jltitle">{{item.jltitle}}</text></view>
					
								<!--套餐中产品展示-->
								<view class="f2" v-if=" item.ggtext && item.ggtext.length > 0">
									<view v-for="(gitem,index) in item.ggtext">{{gitem}}</view>
								</view>
								<view class="f3">
									<text style="font-weight:bold;">
										￥{{!isNull(item.jlprice)?parseFloat(parseFloat(item.sell_price)+parseFloat(item.jlprice)).toFixed(2):item.sell_price}} 	 
									</text>								
									<text style="padding-left:20rpx"> × {{item.num}}</text>
									<text v-if="item.product_type && item.product_type ==1" style="font-size: 20rpx;">斤</text>
									</view>
							</view>
						</view>
						<view v-if="item.remark" style="color: #999999">备注：{{item.remark}}</view>
					</view>
					<view v-if="ordershowall==false && buydata.prodata.length > 4" class="storeviewmore" @tap="doOrderShowAll">- 查看更多 - </view>
				</view>
			</view>
		</view>
		<view style="width: 100%; height:182rpx;"></view>
		<view class="bottombar flex-row" :class="menuindex>-1?'tabbarbot':'notabbarbot'" >
			<view class="btn" @tap="goback">继续点餐</view>	
			<view class="btn " :style="{backgroundColor:t('color1'),borderColor:t('color1'),color:'#fff'}" @tap="toBuy">现在下单</view>	
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
		address: [],
		usescore: 0,
		scoredk_money: 0,
		totalprice: '0.00',
		couponvisible: false,
		cuxiaovisible: false,
		renshuvisible:false,
		bid: 0,
		nowbid: 0,
		needaddress: 1,
		userinfo:{},
		latitude: "",
		longitude: "",
		allbuydata: "",
		tableId:'',
		tableinfo:{},
		oglist:[],
		waitordershowall:false,//代下单显示全部
		ordershowall:false,//代下单显示全部
		prodata:'',
		pre_url: app.globalData.pre_url,
		remark:[]
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.tableId = this.opt.tableId || '';
		this.bid = this.opt.bid  || 0;
		var cachelongitude = app.getCache('user_current_longitude');
		var cachelatitude = app.getCache('user_current_latitude');
		if(cachelongitude && cachelatitude){
			this.latitude = cachelatitude
			this.longitude = cachelongitude
		}else{
			var that = this;
			app.getLocation(function(res) {
				that.latitude = res.latitude;
				that.longitude = res.longitude;
			});
		}
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
	toBuy(){
		 var that = this;
		 that.loading = true;
		 var frompage = that.opt.frompage ? that.opt.frompage : '';
		 app.post('ApiRestaurantShop/addToOrder', {tableid:that.tableId,prodata:that.prodata,renshu:that.opt.renshu,remark:that.remark}, function (res) {
			 that.loading = false;
			if (res.status == 0) {
				if (res.msg) {
					app.alert(res.msg, function () {
						if (res.url) {
							app.goto(res.url);
						} else {
							app.goback();
						}
					});
				} else if (res.url) {
					app.goto(res.url);
				} else {
					app.alert('您没有权限购买该商品');
				}
				return;
			}
			app.success('下单成功');
			setTimeout(()=>{
				if(frompage == 'admin'){
					app.goto('/admin/restaurant/tableWaiterDetail?id=' + that.tableId, 'redirect');
				}else{
					app.goback();
				}	
			},1000)
		 })
	},
	goback(){
	  app.goback();
	},
	getdata: function () {
		var that = this;
		that.loading = true;
		app.get('ApiRestaurantShop/getTableOrder', {tableId:that.tableId,bid:that.bid,prodata:that.opt.prodata,renshu:that.opt.renshu}, function (res) {
			that.loading = false;
			if (res.status == 0) {
				if (res.msg) {
					app.alert(res.msg, function () {
						if (res.url) {
							app.goto(res.url);
						} else {
							app.goback();
						}
					});
				} else if (res.url) {
					app.goto(res.url);
				} else {
					app.alert('您没有权限购买该商品');
				}
				return;
			}
			that.tableinfo = res.tableinfo;
			that.allbuydata = res.allbuydata;
			if(that.allbuydata.length <=0){
				app.alert('小伙伴已提交订单', function(){
					app.goback(true)
				});
			}
			that.oglist = res.oglist;
			var prodatastr=that.allbuydata[that.bid].prodatastr
			that.prodata = prodatastr
			that.loaded();
		});
	},
	doWaitorderShowAll:function(){
		this.waitordershowall = true;
	},
	doOrderShowAll:function(){
		this.ordershowall = true;
	},
	receiveMessage: function (data) {
		var that = this;
		console.log(data,'receiveMessage');
		if(data.type == 'restaurant_shop_createorder' && that.tableId == data.data.tableid && app.globalData.mid!=data.data.mid) {
			that.getdata();
		}
	},
	prodel(e){
		var key = e.currentTarget.dataset.key;
		var bid = e.currentTarget.dataset.bid;
		var allbuydata = this.allbuydata;
		var thisallbuydata = allbuydata[bid];
		var prodata = thisallbuydata.prodata;
		var delprodata = prodata[key];
		//删除对应商品
		var newprodata =prodata.filter(function(item,index){
			return index !=key;
		});
		var new_prodatastr_arr = [];
		for(var p in newprodata){
			var thiprodata = newprodata[p];
			 new_prodatastr_arr.push( thiprodata.product.id+','+thiprodata.guige.id+','+thiprodata.num+',' + thiprodata.carid);
		}
		allbuydata[bid].prodata = newprodata;
		var prodatastr= new_prodatastr_arr.join('-');
		this.prodata = prodatastr
		allbuydata[bid].prodatastr  
		this.allbuydata = allbuydata;
	},
	inputgoodsremark: function (e) {
	  var that = this;
	  var allbuydata = that.allbuydata;
	  var index = e.currentTarget.dataset.index;
	  var index2 = e.currentTarget.dataset.index2;
	  var reamrk = that.remark;
	  reamrk[index2]= e.detail.value;
	  that.remark = reamrk;
	},
  }
}
</script>
<style>
.address-add{ width:94%;margin:20rpx 3%;background:#fff;border-radius:20rpx;padding: 20rpx 3%;min-height:140rpx;}
.address-add .f1{margin-right:20rpx}
.address-add .f1 .img{ width: 66rpx; height: 66rpx; }
.address-add .f2{ color: #666; }
.address-add .f3{ width: 26rpx; height: 26rpx;}

.linkitem{width: 100%;padding:1px 0;background: #fff;display:flex;align-items:center}
.linkitem .f1{width:160rpx;color:#111111}
.linkitem .input{height:50rpx;padding-left:10rpx;color:#222222;font-weight:bold;font-size:28rpx;flex:1}

.buydata{width:94%;margin:0 3%;background:#fff;margin-bottom:20rpx;border-radius:20rpx;}

.btitle{width:100%;padding:20rpx 20rpx;display:flex;align-items:center;color:#111111;font-weight:bold;font-size:30rpx}
.btitle .img{width:34rpx;height:34rpx;margin-right:10rpx}

.bcontent{width:100%;padding:0 20rpx}

.product{width:100%;border-bottom:1px solid #f4f4f4} 
.product .item{width:100%; padding:20rpx 0;background:#fff;border-bottom:1px #ededed dashed;}
.product .item:last-child{border:none}
.product .info{padding-left:20rpx;}
.product .info .f1{color: #222222;font-weight:bold;font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .info .f2{color: #999999; font-size:24rpx}
.product .info .f3{color: #FF4C4C; font-size:28rpx;display:flex;align-items:center;margin-top:10rpx}
.product image{ width:140rpx;height:140rpx}

.freight{width:100%;padding:20rpx 0;background:#fff;display:flex;flex-direction:column;}
.freight .f1{color:#333;margin-bottom:10rpx}
.freight .f2{color: #111111;text-align:right;flex:1}
.freight .f3{width: 24rpx;height:28rpx;}
.freighttips{color:red;font-size:24rpx;}

.freight-ul{width:100%;display:flex;}
.freight-li{flex-shrink:0;display:flex;background:#F5F6F8;border-radius:24rpx;color:#6C737F;font-size:24rpx;text-align: center;height:48rpx; line-height:48rpx;padding:0 28rpx;margin:12rpx 10rpx 12rpx 0}


.price{width:100%;padding:20rpx 0;background:#fff;display:flex;align-items:center}
.price .f1{color:#333}
.price .f2{ color:#111;font-weight:bold;text-align:right;flex:1}
.price .f3{width: 24rpx;height:24rpx;}

.scoredk{width:94%;margin:0 3%;margin-bottom:20rpx;border-radius:20rpx;padding:24rpx 20rpx; background: #fff;display:flex;align-items:center}
.scoredk .f1{color:#333333}
.scoredk .f2{ color: #999999;text-align:right;flex:1}

.remark{width: 100%;padding:16rpx 0;background: #fff;display:flex;align-items:center}
.remark .f1{color:#333;width:200rpx}
.remark input{ border:0px solid #eee;height:70rpx;padding-left:10rpx;text-align:right}

.footer {width: 100%;background: #fff;margin-top: 5px;position: fixed;left: 0px;bottom: 0px;padding:0 20rpx;display:flex;align-items:center;z-index:8}
.footer .text1 {height:110rpx;line-height:110rpx;color: #2a2a2a;font-size: 30rpx;}
.footer .text1  text{color: #e94745;font-size: 32rpx;}
.footer .op{width: 200rpx;height:80rpx;line-height:80rpx;color: #fff;text-align: center;font-size: 30rpx;border-radius:44rpx}

.storeitem{width: 100%;padding:20rpx 0;display:flex;flex-direction:column;color:#333}
.storeitem .panel{width: 100%;height:60rpx;line-height:60rpx;font-size:28rpx;color:#333;margin-bottom:10rpx;display:flex}
.storeitem .panel .f1{color:#333}
.storeitem .panel .f2{ color:#111;font-weight:bold;text-align:right;flex:1}
.storeitem .radio-item{display:flex;width:100%;color:#000;align-items: center;background:#fff;border-bottom:0 solid #eee;padding:8rpx 20rpx;}
.storeitem .radio-item:last-child{border:0}
.storeitem .radio-item .f1{color:#666;flex:1}
.storeitem .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-left:30rpx}
.storeitem .radio .radio-img{width:100%;height:100%}

.pstime-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.pstime-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.pstime-item .radio .radio-img{width:100%;height:100%}

.cuxiao-desc{width:100%}
.cuxiao-item{display: flex;padding:0 40rpx 20rpx 40rpx;}
.cuxiao-item .type-name{font-size:28rpx; color: #49aa34;margin-bottom: 10rpx;flex:1}
.cuxiao-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.cuxiao-item .radio .radio-img{width:100%;height:100%}

.header {
	position: relative;
	padding: 30rpx;
	
	width: 94%;
	margin: 0 3%;
	background: #fff;
	border-radius: 20rpx;
	margin-bottom: 21rpx;
}

.header_title {
	font-size: 28rpx;
	color: #333;
}
.header_address {
		font-size: 24rpx;
		color: #999;
		margin-top: 20rpx;
	}
.buystatus{
	padding: 20rpx 20rpx; 
	font-weight: 700;
	font-size: 30rpx;
}
.leftline{width: 6rpx;height: 30rpx;border-radius: 20rpx;margin-right: 10rpx;}
.storeviewmore{width:100%;text-align:center;color:#889;height:55rpx;margin-top:10rpx}
.bottombar{ width: 94%; position: fixed;bottom: 0px; left: 0px; background: #fff;display:flex;height:75rpx;padding:20rpx 4% 0 2%;align-items:center;box-sizing:content-box;justify-content: flex-end;z-index: 10;}
.btn{
	width: 200rpx;
	line-height: 70rpx;
	border-radius: 35rpx;
	border: 2rpx solid #9e9e9e;
	text-align: center;
	font-size: 32rpx;
	margin:  0 10rpx;
	color: #424242;
	font-weight: 700;
}
.prodel{
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 0 20rpx;
	height: 52rpx;
	position: absolute;
	top: 50%;
	right: -20rpx;
	border-radius: 26rpx 0 0 26rpx;
	background: #FFE8E1;
	color: #222222;
	font-size: 24rpx;
	font-weight: 700;
}
.prodel .img{
	width: 25rpx;
	height: 25rpx;
	margin: 0 6rpx;
}
.tip{position: absolute;right: -20rpx;top: -10rpx;height: 40rpx;line-height: 40rpx;;width: 80rpx;background: #ffab91;color: #fff;text-align: center;border-radius: 26rpx 0 0 26rpx;font-size: 22rpx}
</style>