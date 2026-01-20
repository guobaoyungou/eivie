<template>
<view class="container">
	<block v-if="isload">

		<map v-if="psorder.status!=4" class="map" :longitude="binfo.longitude" :latitude="binfo.latitude" scale="14" :markers="[{
			id:0,
			latitude:binfo.latitude,
			longitude:binfo.longitude,
			iconPath: `${pre_url}/static/img/peisong/marker_business.png`,
			width:'44',
			height:'54'
		},{
			id:1,
			latitude:orderinfo.latitude,
			longitude:orderinfo.longitude,
			iconPath: `${pre_url}/static/img/peisong/marker_kehu.png`,
			width:'44',
			height:'54'
		},{
			id:2,
			latitude:psuser.latitude,
			longitude:psuser.longitude,
			iconPath: `${pre_url}/static/img/peisong/marker_qishou.png`,
			width:'44',
			height:'54'
		}]"></map>
		<map v-else class="map" :longitude="binfo.longitude" :latitude="binfo.latitude" scale="14" :markers="[{
			id:0,
			latitude:binfo.latitude,
			longitude:binfo.longitude,
			iconPath: `${pre_url}/static/img/peisong/marker_business.png`,
			width:'44',
			height:'54'
		},{
			id:1,
			latitude:orderinfo.latitude,
			longitude:orderinfo.longitude,
			iconPath: `${pre_url}/static/img/peisong/marker_kehu.png`,
			width:'44',
			height:'54'
		}]"></map>
		
		<view class="order-box">
			<view class="head">
				<view class="f1" v-if="psorder.status==4"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>已送达</view>
				<view class="f1" v-else-if="psorder.leftminute>0"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/><text class="t1">{{psorder.leftminute}}分钟内</text> 送达</view>
				<view class="f1" v-else><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>已超时<text class="t1" style="margin-left:10rpx">{{-psorder.leftminute}}分钟</text></view>
				<view class="flex1"></view>
				<view class="f2">
						<text class="t1">{{psorder.ticheng}}</text>元
						<text v-if="psorder.tip_fee && psorder.tip_fee>0">
								+{{psorder.tip_fee}}元小费
						</text>
						<text v-if="psorder.up_floor_fee && psorder.up_floor_fee>0">
								+{{psorder.up_floor_fee}}元上楼费
						</text>
				</view>
			</view>
			<view class="content" style="border-bottom:0">
				<block v-if="psorder.type =='deposit_refund_order'">
					<view class="f1">
						<view class="t1"><text class="x1">{{psorder.juli2}}</text><text class="x2">{{psorder.juli2_unit}}</text></view>
						<view class="t2"><image :src="pre_url+'/static/img/peisong/ps_juli.png'" class="img"/></view>
						<view class="t3"><text class="x1">{{psorder.juli}}</text><text class="x2">{{psorder.juli_unit}}</text></view>
					</view>
					<view class="f2">
						<view class="t1">{{orderinfo.address}}</view>
						<view class="t2">{{orderinfo.area}}</view>
						<view class="t3">{{binfo.name}}</view>
						<view class="t2">{{binfo.address}}</view>
					</view>
				</block>
				<block v-else>
					<view class="f1">
						<view class="t1"><text class="x1">{{psorder.juli}}</text><text class="x2">{{psorder.juli_unit}}</text></view><!-- 配送员距商家距离 -->
						<view class="t2"><image :src="pre_url+'/static/img/peisong/ps_juli.png'" class="img"/></view>
						<view class="t3"><text class="x1">{{psorder.juli2}}</text><text class="x2">{{psorder.juli2_unit}}</text></view><!-- 配送员距会员距离 -->
					</view>
					<view class="f2">
						<view class="t1">{{binfo.name}}</view>
						<view class="t2">{{binfo.address}}</view>
						<view class="t3">{{orderinfo.address}}</view>
							<view class="t2">{{orderinfo.area}}</view>
					</view>
				</block>
				
				<view class="f3" @tap.stop="daohang"><image :src="pre_url+'/static/img/peisong/ps_daohang.png'" class="img"/></view>
			</view>
		</view>

		<view class="orderinfo">
			<view class="box-title">商品清单({{orderinfo.procount}})</view>
			<view v-for="(item, idx) in prolist" :key="idx" class="item">
			<block v-if="psorder.type!='paotui_order'">
					<text class="t1 flex1">{{item.name}} {{item.ggname}}</text>
					<text class="t2 flex0">￥{{item.sell_price}} ×{{item.num}} </text>
			</block>
			<block v-else>
					<text class="t1 flex1">{{item.name}} </text>
					<text class="t2 flex0">x{{item.num}} </text>
				</block>
			</view>
		</view>
		<!-- 押金回收 -->
		<view class="orderinfo" v-if="orderinfo.deposit_return_list && orderinfo.deposit_return_list.length > 0">
			<view class="box-title">{{t('押金')}}回收清单({{orderinfo.deposit_return_list.length}})</view>
			<view v-for="(item, idx) in orderinfo.deposit_return_list" :key="idx" class="item">
				<text class="t1 flex1">{{item.name}} </text>
				<text class="t2 flex0">x{{item.num}} </text>	
			</view>
		</view>
		<view class="orderinfo" v-if="psorder.status!=0">
			<view class="box-title">配送信息</view>
			<view class="item">
				<text class="t1">接单时间</text>
				<text class="t2">{{dateFormat(psorder.starttime)}}</text>
			</view>
		
			<view class="item" v-if="psorder.daodiantime ">
				<text class="t1">到店时间</text>
				<text class="t2">{{dateFormat(psorder.daodiantime)}}</text>
			</view>
			<view class="item" v-if="psorder.quhuotime">
				<text class="t1">取货时间</text>
				<text class="t2">{{dateFormat(psorder.quhuotime)}}</text>
			</view>
			<view class="item" v-if="psorder.endtime">
				<text class="t1">送达时间</text>
				<text class="t2">{{dateFormat(psorder.endtime)}}</text>
			</view>
		</view>

		<view class="orderinfo">
			<view class="box-title">订单信息</view>
			<view class="item">
				<text class="t1">订单编号</text>
				<text class="t2" user-select="true" selectable="true">{{orderinfo.ordernum}}</text>
			</view>
			<view class="item">
				<text class="t1">下单时间</text>
				<text class="t2">{{dateFormat(orderinfo.createtime)}}</text>
			</view>
			<block v-if="psorder.type !='deposit_refund_order'">
				
				<view class="item">
					<text class="t1">支付时间</text>
					<text class="t2">{{dateFormat(orderinfo.paytime)}}</text>
				</view>
				<view class="item">
					<text class="t1">支付方式</text>
					<text class="t2">{{orderinfo.paytype}}</text>
				</view>
			</block>
			<view class="item" v-if="orderinfo.expect_take_time">
				<text class="t1">取件时间</text>
				<text class="t2">{{orderinfo.expect_take_time}}</text>
			</view>
			<view class="item" v-if="orderinfo.pic">
				<text class="t1">物品图片</text>
				<view class="t2" ><image :src="orderinfo.pic" style="width:400rpx;height:auto" mode="widthFix" @tap="previewImage" :data-url="orderinfo.pic"/></view>
			</view>
			<block v-if="psorder.type !='deposit_refund_order'">
				<view class="item" v-if="psorder.type!='paotui_order'">
					<text class="t1">商品金额</text>
					<text class="t2 red">¥{{orderinfo.product_price}}</text>
				</view>
				<view class="item">
					<text class="t1">实付款</text>
					<text class="t2 red">¥{{orderinfo.totalprice}}</text>
				</view>
			</block>
			<block v-else>
				<view class="item">
					<text class="t1">退押金额</text>
					<text class="t2 red">¥{{orderinfo.totalprice}}</text>
				</view>
			</block>
			<view class="item" v-if="psorder.type !='deposit_refund_order'">
				<text class="t1">备注</text>
				<text class="t2 red">{{orderinfo.message ? orderinfo.message : '无'}}</text>
			</view>
			<view class="item" v-for="item in orderinfo.formdata" :key="index" v-if="(orderinfo.formdata).length > 0">
				<text class="t1">{{item[0]}}</text>
				<view class="t2" v-if="item[2]=='upload'"><image :src="item[1]" style="width:400rpx;height:auto" mode="widthFix" @tap="previewImage" :data-url="item[1]"/></view>
				<text class="t2" v-else user-select="true" selectable="true">{{item[1]}}</text>
			</view>
			<view class="item" v-if="orderinfo.field1data">
				<text class="t1">{{orderinfo.field1data[0]}}</text>
				<text class="t2 red">{{orderinfo.field1data[1]}}</text>
			</view>
			<view class="item" v-if="orderinfo.field2data">
				<text class="t1">{{orderinfo.field2data[0]}}</text>
				<text class="t2 red">{{orderinfo.field2data[1]}}</text>
			</view>
			<view class="item" v-if="orderinfo.field3data">
				<text class="t1">{{orderinfo.field3data[0]}}</text>
				<text class="t2 red">{{orderinfo.field3data[1]}}</text>
			</view>
			<view class="item" v-if="orderinfo.field4data">
				<text class="t1">{{orderinfo.field4data[0]}}</text>
				<text class="t2 red">{{orderinfo.field4data[1]}}</text>
			</view>
			<view class="item" v-if="orderinfo.field5data">
				<text class="t1">{{orderinfo.field5data[0]}}</text>
				<text class="t2 red">{{orderinfo.field5data[1]}}</text>
			</view>
		</view>
		<view class="orderinfo" v-if="!isNull(deposit_return_pic) && deposit_return_pic.length > 0 ">
			<view class="box-title">回收照片</view>
			<view class="item" v-if="">
				<!-- <text class="t1">物品照片</text> -->
				<view class="t2 " >
					<view id="content_picpreview" class="flex" style="flex-wrap:wrap;padding-top:20rpx">
						<view v-for="(item, index) in deposit_return_pic" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" v-if="psorder.status !=4" :data-index="index" data-field="deposit_return_pic"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						</view>
					</view>
				</view>
			</view>
		</view>
		<view style="width:100%;height:180rpx"></view>
		<view class="bottom notabbarbot" v-if="psorder.status!=4">
			
			<block v-if="psorder.type!='paotui_order'">
					<view class="f1" v-if="psorder.status!=0" @tap="call" :data-tel="orderinfo.tel"><image :src="pre_url+'/static/img/peisong/tel1.png'" class="img"/>联系顾客</view>
					<view class="f2" v-if="psorder.status!=0" @tap="call" :data-tel="binfo.tel"><image :src="pre_url+'/static/img/peisong/tel2.png'" class="img"/>联系商家</view>
			</block>
			<block v-else>
					<view class="f1" v-if="psorder.status!=0" @tap="call" :data-tel="orderinfo.take_tel"><image :src="pre_url+'/static/img/peisong/tel1.png'" class="img"/>取货顾客</view>
					<view class="f2" v-if="psorder.status!=0" @tap="call" :data-tel="orderinfo.send_tel"><image :src="pre_url+'/static/img/peisong/tel2.png'" class="img"/>收货顾客</view>
					<view class="f2" v-if="psorder.status!=0" @tap="call" :data-tel="shop_tel"><image :src="pre_url+'/static/img/peisong/tel2.png'" class="img"/>联系商家</view>
			</block>
			
			<view class="btn1" @tap="uploadDepositReturn" data-field="deposit_return_pic" v-if="psorder.status==3 && !isNull(orderinfo.deposit_return_list) &&orderinfo.deposit_return_list.length > 0">回收拍照</view>
			
			<view class="btn1" @tap="qiangdan" :data-id="psorder.id" v-if="psorder.status==0">立即抢单</view>
			<block v-if="psorder.type !='deposit_refund_order'">
				<view class="btn1" @tap="setst" :data-id="psorder.id" data-st="2" v-if="psorder.status==1">我已到店</view>
			</block>
		
			<view class="btn1" @tap="setst" :data-id="psorder.id" data-st="3" v-if="psorder.status==2">我已取货</view>
			<view class="btn1" @tap="setst" :data-id="psorder.id" data-st="4" v-if="psorder.status==3">我已送达</view>
		
		</view>
		
		<!-- 押金照片弹窗 -->
		<uni-popup ref="refPopup">
			<view class="popup-content flex flex-col" :style="{backgroundImage:`linear-gradient(rgba(#06A051,0.2),#fff)`}">
				<view class="deposit-title-view">拍摄照片提示</view>
				<view class="deposit-tips-view">上门回收{{t('押金')}}物品需要拍摄完整照片</view>
				<view class="deposit-popup-but" style="background: #06A051;" @click="depositClose">确认</view>
			</view>
		</uni-popup>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
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
			
      orderinfo: {},
      prolist: [],
			psuser:{},
      binfo: {},
      psorder: {},
      shop_tel:'',
			interval1:null,
			deposit_return_pic:[]
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
		this.updatemylocation(false);
  },
	onUnload:function(){
		clearInterval(this.interval1);
	},
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiPeisong/orderdetail', {id: that.opt.id}, function (res) {
				that.loading = false;
				if(res.status == 0) {
					app.alert(res.msg);return;
				}
				that.orderinfo = res.orderinfo;
				that.prolist = res.prolist;
				that.binfo = res.binfo;
				that.psorder = res.psorder;
				that.psuser = res.psuser;
				if(res.shop_tel){
						that.shop_tel = res.shop_tel;
				}
				if(that.psorder.deposit_return_pic && that.psorder.deposit_return_pic.length > 0){
					that.deposit_return_pic =  that.psorder.deposit_return_pic;
				}
				that.loaded();
				clearInterval(that.interval1);
				that.interval1 = setTimeout(function(){
					that.updatemylocation(true);
				},30000)
			});
		},
		updatemylocation:function(needload){
			var that = this;
			app.getLocation(function(res){
				var longitude = res.longitude;
				var latitude = res.latitude;
				app.post('ApiPeisong/updatemylocation',{longitude:longitude,latitude:latitude},function(){
					if(needload) that.getdata();
				});
			});
		},
    qiangdan: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      app.confirm('确定要接单吗?', function () {
				app.showLoading('提交中');
        app.post('ApiPeisong/qiangdan', {id: id}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
    setst: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      var st = e.currentTarget.dataset.st;
			if(st == 2){
				var tips = '确定改为已到店吗?';
			}if(st == 3){
				var tips = '确定改为已取货吗?';
			}if(st == 4){
				var tips = '确定改为已送达吗?';
			}
			if(st == 4){
				if(!app.isNull(that.orderinfo.deposit_return_list) && that.orderinfo.deposit_return_list.length > 0 && that.deposit_return_pic.length <=0){
					that.$refs.refPopup.open();
					return;
				}
			}
		
      app.confirm(tips, function () {
				app.showLoading('提交中');
        app.post('ApiPeisong/setst', {id: id,st:st,deposit_return_pic:that.deposit_return_pic}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
		openLocation:function(e){
			var latitude = parseFloat(e.currentTarget.dataset.latitude)
			var longitude = parseFloat(e.currentTarget.dataset.longitude)
			var address = e.currentTarget.dataset.address
			uni.openLocation({
			 latitude:latitude,
			 longitude:longitude,
			 name:address,
			 scale: 13
		 })
		},
		daohang:function(e){
			var that = this;
			var datainfo = that.psorder;
			var binfo = that.binfo
			var orderinfo = that.orderinfo;
            if(datainfo.type == 'paotui_order'){
                var address1 = '导航到取货地址';
                var address2 = '导航到收货地址';
            }else{
                var address1 = '导航到商家';
                var address2 = '导航到用户';
            }
            
			uni.showActionSheet({
            itemList: [address1, address2],
            success: function (res) {
					if(res.tapIndex >= 0){
						if (res.tapIndex == 0) {
							var longitude = datainfo.longitude
							var latitude = datainfo.latitude
							var name = binfo.name
							var address = binfo.address
						}else{
							var longitude = datainfo.longitude2
							var latitude = datainfo.latitude2
							var name = orderinfo.address
							var address = orderinfo.address
						}
						console.log(longitude);
						console.log(latitude);
						console.log(address);
						uni.openLocation({
						 latitude:parseFloat(latitude),
						 longitude:parseFloat(longitude),
						 name:name,
						 address:address,
						 scale: 13
						})
					}
				}
			});
		},
		call:function(e){
			var tel = e.currentTarget.dataset.tel;
			uni.makePhoneCall({
				phoneNumber: tel
			});
		},
		//押金图片拍照
		uploadDepositReturn(e){
			var that = this;
			var field= e.currentTarget.dataset.field
			app.confirm('确定回收拍照吗?', function () {
				var pics = [];
				uni.chooseImage({
					count:  1,
					sizeType: ['original', 'compressed'],
					sourceType: ['camera'],
					success: function(res) {
						var tempFilePaths = res.tempFilePaths,
							imageUrls = [];
						var uploadednum = 0;
						for (var i = 0; i < tempFilePaths.length; i++) {
							imageUrls.push('');
							app.showLoading('上传中');
							uni.uploadFile({
								url: app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app
									.globalData.aid + '/platform/' + app.globalData.platform +
									'/session_id/' +app.globalData.session_id+'/sortnum/'+i+'/other_param/0',
								filePath: tempFilePaths[i],
								name: 'file',
								success: function(res) {
									app.showLoading(false);
									if(typeof res.data == 'string'){
										//兼容微信小程序
										var data = JSON.parse(res.data);
									}else{
										//兼容百度小程序
										var data = res.data;
									}
									if (data.status == 1) {
										uploadednum++;
										imageUrls[parseInt(data.sortnum)] = data.url;
				
										if (uploadednum == tempFilePaths.length) {
											console.log(imageUrls);
											
											for(var i=0;i<imageUrls.length;i++){
												pics.push(imageUrls[i]);
											}
											that.deposit_return_pic = pics;
										}
									} else {
										app.alert(data.msg);
									}
								},
								fail: function(res) {
									app.showLoading(false);
									app.alert(res.errMsg);
								}
							});
						}
					},
					fail: function(res) { //alert(res.errMsg);
					}
				});
			});
		},
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			pics.splice(index,1)
		},
		depositClose(){
			this.$refs.refPopup.close();
		}
  }
}
</script>
<style>

.map{width:100%;height:500rpx;overflow:hidden}
.ordertop{width:100%;height:220rpx;padding:50rpx 0 0 70rpx}
.ordertop .f1{color:#fff}
.ordertop .f1 .t1{font-size:32rpx;height:60rpx;line-height:60rpx}
.ordertop .f1 .t2{font-size:24rpx}

.order-box{ width: 94%;margin:20rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width:100%; border-bottom: 1px #f5f5f5 solid; height:88rpx; line-height:88rpx; overflow: hidden; color: #999;}
.order-box .head .f1{display:flex;align-items:center;color:#222222}
.order-box .head .f1 .img{width:24rpx;height:24rpx;margin-right:4px}
.order-box .head .f1 .t1{color:#06A051;margin-right:10rpx}
.order-box .head .f2{color:#FF6F30}
.order-box .head .f2 .t1{font-size:36rpx;margin-right:4rpx}

.order-box .content{display:flex;justify-content:space-between;width: 100%; padding:16rpx 0px;border-bottom: 1px solid #f5f5f5;position:relative}
.order-box .content .f1{width:100rpx;display:flex;flex-direction:column;align-items:center}
.order-box .content .f1 .t1{display:flex;flex-direction:column;align-items:center}
.order-box .content .f1 .t1 .x1{color:#FF6F30;font-size:28rpx;font-weight:bold}
.order-box .content .f1 .t1 .x2{color:#999999;font-size:24rpx;margin-bottom:8rpx}
.order-box .content .f1 .t2 .img{width:12rpx;height:36rpx; margin: 10rpx 0;}

.order-box .content .f1 .t3{display:flex;flex-direction:column;align-items:center}
.order-box .content .f1 .t3 .x1{color:#FF6F30;font-size:28rpx;font-weight:bold}
.order-box .content .f1 .t2 .img{width:12rpx;height:36rpx; margin: 10rpx 0;}
.order-box .content .f2{padding:0 10rpx;}
.order-box .content .f2 .t1{font-size:36rpx;color:#222222;font-weight:bold;line-height:50rpx;margin-bottom:6rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.order-box .content .f2 .t2{font-size:28rpx;color:#222222;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:3;overflow:hidden;}
.order-box .content .f2 .t3{font-size:36rpx;color:#222222;font-weight:bold;line-height:50rpx;margin-top:30rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:3;overflow:hidden;}
.order-box .content .f3 .img{width:72rpx;height:168rpx}

.orderinfo{width: 94%;margin:20rpx 3%;margin-top:10rpx;padding: 14rpx 3%;background: #FFF;border-radius:8px}
.orderinfo .box-title{color:#161616;font-size:30rpx;height:80rpx;line-height:80rpx;font-weight:bold}
.orderinfo .item{display:flex;width:100%;padding:10rpx 0;}
.orderinfo .item .t1{width:200rpx;color:#161616}
.orderinfo .item .t2{flex:1;text-align:right;color:#222222}
.orderinfo .item .red{color:red}

.bottom{ width: 100%;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;z-index: 11;}
.bottom .f1{width:188rpx;display:flex;align-items:center;flex-direction:column;font-size:20rpx;color:#373C55;border-right:1px solid #EAEEED}
.bottom .f1 .img{width:44rpx;height:44rpx}
.bottom .f2{width:188rpx;display:flex;align-items:center;flex-direction:column;font-size:20rpx;color:#373C55}
.bottom .f2 .img{width:44rpx;height:44rpx}
.bottom .btn1{flex:1;background:linear-gradient(-90deg, #06A051 0%, #03B269 100%);height:100rpx;line-height:100rpx;color:#fff;text-align:center;font-size:32rpx}

/* 押金照片 */
	.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
	.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
	.layui-imgbox-img>image{max-width:100%;}
	
.popup-content{width: 480rpx;background: #fff;border-radius: 40rpx;overflow: hidden;padding: 30rpx}
.popup-content .deposit-title-view{font-size: 30rpx;font-weight: bold;color: #000;letter-spacing: 3rpx;text-align: center;margin-top: 30rpx;}
.popup-content .deposit-tips-view{font-size: 24rpx;color: #999;text-align: center;margin-top: 10rpx;}
.popup-content .deposit-popup-but{width: 100%;font-size: 28rpx;padding: 18rpx;text-align: center;border-radius: 40rpx;letter-spacing: 2rpx;font-weight: bold;color: #fff;margin-top: 30rpx;}
</style>