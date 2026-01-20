<template>
<view>
	<block v-if="isload">
		<view class="top-view flex"  :style="{backgroundImage:`linear-gradient(to right , rgba(${t('color1rgb')},0.7),${t('color1')})`}">
			<view class="left-view flex-col">
				<view class="title-view">金额总计（元）</view>
				<view class="price-view">{{totalprice}}</view>
			</view>
			<view class="right-view">
				<image :src="pre_url+'/static/img/deposit/buy.png'"></image>
			</view>
		</view>
		<view class="content-view">
			<view class="filter-view flex flex-bt">
				<view class="filter-title">{{t('押金')}}名称</view>
				<!-- <view class="picker-view">
					<picker @change="bindPickerChange" :value="index" :range="array">
						<view class="flex flex-y-center">
							<view class="picker-text">选择类型</view>
							<image class="jiantou-icon" :src="pre_url+'/static/img/left_jiantou.png'"></image>
						</view>
					</picker>
				</view> -->
			</view>
			<view class="content-list">
				<scroll-view style="max-height: 65vh;height: auto;" scroll-y>
					<block v-for="(item,index) in datalist" :key="index">
						<view class="list-options flex" :style="{borderColor:t('color1')}">
							<view class="img-view">
								<image :src="item.pic"></image>
							</view>
							<view class="info-view flex-col">
								<view class="title-view">{{item.name}}</view>
								<view class="bname">适用商家：{{item.bname}}</view>
								<view class="price-fun-view flex flex-y-center flex-bt">
									<view class="flex flex-y-center flex-bt" style="line-height: 46rpx;">
										<view class="price-text" :style="{color:t('color1')}">￥{{item.money}}</view>
										<view style="margin-left: 20rpx;">数量：{{item.num}}</view>
									</view>
									<view class="fun-view flex flex-y-center">
										<view class="fun-class minus-class flex-xy-center" :style="{Color:t('color1'),borderColor:t('color1')}" @tap="subnum" :data-key="index" v-if="item.buynum > 0">-</view>
										<view class="input-view" v-if="item.buynum > 0">{{item.buynum}}</view>
										<view class="fun-class add-class flex-xy-center"  :style="{backgroundColor:t('color1'),borderColor:t('color1')}" @tap="addnum" :data-key="index" v-if="numkey ==-1 || numkey == index  ">+</view>
									</view>
								</view>
							</view>
						</view>
					</block>
				</scroll-view>
			</view>
			<!-- 信息填写 -->
			<view class="input-info-view flex flex-bt flex-y-center">
				<view class="title-view">回收方式</view>
				<!-- @tap="showRecycle" -->
				<view class="switch-view flex-y-center">
					<text class="title-desc" style="color: #666;" v-if="recycle_type_name">{{recycle_type_name}}</text>
					<text class="title-desc" v-else>请选择回收方式</text> 
					<!-- <image :src="pre_url+'/static/img/arrowright.png'" class="arrowright"/> -->
				</view>
			</view>
			<view class="input-info-view flex flex-bt flex-y-center" v-if="recycle_type==2">
				<view>
					<view class="title-view">取件地址</view>
				</view>
				<view  class=" address-add flex-y-center" @tap="goto" :data-url="'/pagesB/address/address?fromPage=depositRefund&type=1'">
					<view class="f2 flex1 f1" v-if="address && address.name">
						<view style="font-weight:bold;color:#111111;font-size:30rpx">{{address.name}} {{address.tel}} <text v-if="address.company">{{address.company}}</text></view>
						<view style="font-size:24rpx">{{address.area}} {{address.address}} <text v-if="address.floor">{{address.floor}}楼</text><text v-if="address.room">{{address.room}}</text></view>
					</view>
					<view v-else class="f2 flex1 ">请选择取件地址</view>
					<image :src="pre_url+'/static/img/arrowright.png'" class="arrowright"/>
				</view>
			</view>
			
			<!-- <view class="input-info-view flex flex-bt flex-y-center">
				
				<view>
					<view class="title-view">支付凭证</view>
					<view class="title-desc">截图上传支付结果照片</view>
				</view>
				<view class="upload-img flex-xy-center">
					<image class="upload-img-class" :src="pay_pic" @click="previewImage(pay_pic)" v-if="pay_pic"></image>
					<view class="upload-icon-view flex-xy-center" :data-key="'pay_pic'" @tap="uploadimg" v-else>
						<image class="icon-class" :src="pre_url+'/static/img/deposit/xiangjixiao.png'"></image>
					</view>
					<view class="close-view" @click="pay_pic = ''" v-if="pay_pic">
						<image :src="pre_url+'/static/img/ico-del.png'"></image>
					</view>
				</view>
			</view> -->
			<view class="input-info-view flex flex-bt flex-y-center">
				<view>
					<view class="title-view">照片凭证</view>
					<view class="title-desc">拍摄商品照片</view>
				</view>
				<view class="upload-img flex-xy-center">
					<image class="upload-img-class" :src="goods_pic" @click="previewImage(goods_pic)" v-if="goods_pic"></image>
					<view class="upload-icon-view flex-xy-center" :data-key="'goods_pic'" @tap="uploadimg" v-else>
						<image class="icon-class" :src="pre_url+'/static/img/deposit/xiangjixiao.png'"></image>
					</view>
					<view class="close-view" @click="goods_pic = ''" v-if="goods_pic">
						<image :src="pre_url+'/static/img/ico-del.png'"></image>
					</view>
				</view>
			</view>
			<view class="input-info-view flex flex-bt flex-y-center">
				<view>
					<view class="title-view">收款码</view>
					<view class="title-desc">上传收款二维码用来退还{{t('押金')}}</view>
				</view>
				<view class="upload-img flex-xy-center">
					<image class="upload-img-class" :src="payment_code" @click="previewImage(payment_code)" v-if="payment_code"></image>
					<view class="upload-icon-view flex-xy-center" :data-key="'payment_code'" @tap="uploadimg" v-else>
						<image class="icon-class" :src="pre_url+'/static/img/deposit/xiangjixiao.png'"></image>
					</view>
					<view class="close-view" @click="payment_code = ''" v-if="payment_code">
						<image :src="pre_url+'/static/img/ico-del.png'"></image>
					</view>
				</view>
			</view>
			<view class="input-info-view flex flex-bt flex-y-center">
				<view class="title-view">真实姓名</view>
				<view class="info-input-view">
					<input class="input-class" v-model="realname" placeholder="请输入真实姓名" />
				</view>
			</view>
			<view style="height: calc(140rpx + env(safe-area-inset-bottom))"></view>
		</view>
		
		<view class="buy-but-view" >
			<view class="buy-but" :style="{backgroundColor:t('color1')}" @tap="toSubmit">
				提交审核
			</view>
		</view>
		<nodata v-if="nodata"></nodata>
		<nomore v-if="nomore"></nomore>
	</block>
	<uni-popup ref="refPopup">
		<view class="popup__container" >
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请选择回收方式</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="changeGlistDialog"/>
				</view>
				<view class="popup__content">
					<block v-for="(item, index) in sysset.recycle_type_list" >
						<view class="clist-item" @tap="recycleChange" :data-id="item.id" :data-key="index">
							<view class="flex1">{{item.name}}</view>
							<view class="radio" :style="item.id == recycle_type? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
					</block>
				</view>
			</view>
		</view>
	</uni-popup>
	
	<loading v-if="loading"></loading>
</view>
</template>

<script>
	var app = getApp();
	export default {
		data(){
			return{
				pre_url:app.globalData.pre_url,
				isload: false,
				nodata:false,
				nomore:false,
				loading:false,
				array:['1','2','3'],
				to_door_status:-1,
				datalist:[],
				totalprice:'0.00',
				numdata:[],
				numkey:-1,//操作的押金key
				pay_pic:'',
				goods_pic:'',
				payment_code:'',
				realname:'',
				address:[],
				recycle_type_name:'上门回收',//回收方式名称,
				recycle_type:'2',//回收方式
				sysset:[],
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			
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
		methods:{
			getdata: function (loadmore) {
				if(!loadmore){
					this.pagenum = 1;
					this.datalist = [];
				}
				var that = this;
				var pagenum = that.pagenum;
				that.nodata = false;
				that.nomore = false;
				that.loading = true;
				app.post('ApiDeposit/getDepositMemberList', {pagenum: pagenum}, function (res) {
					that.loading = false;
					var data = res.data;
					that.address = res.address;
					that.sysset = res.sysset;
					if (pagenum == 1) {
						uni.setNavigationBarTitle({
							title: that.t('押金') + '退还'
						});
						that.datalist = data;
						if (data.length == 0) {
							app.alert('暂无可退'+that.t('押金'), function(){
								app.goback()
							});
							return;
						}
				
						that.loaded();
					}else{
						if (data.length == 0) {
							
						} else {
							var datalist = that.datalist;
							var newdata = datalist.concat(data);
							that.datalist = newdata;
						}
					}
				});
			},
			addnum(e){
				var that = this;
				var key = e.currentTarget.dataset.key;
				console.log(key,'keykeykeykey');
				if(that.numkey != -1 && that.numkey != key){
					 app.error('已选择其他'+that.t('押金'));
					 return;
				}
				
				var depositlist = that.datalist;
				var deposit = depositlist[key];
				if(deposit.num < deposit.buynum + 1){
					app.error('数量不足');
					return;
				}
				depositlist[key].buynum++;
				that.depositlist = depositlist
				that.numkey = key;
				that.computePrice();
			},
			subnum(e){
				var that = this;
				var key = e.currentTarget.dataset.key;
				var depositlist = that.datalist;
				var deposit = depositlist[key];
				
				var buynum = deposit.buynum;
				buynum = buynum - 1;
				
				depositlist[key].buynum = buynum;
				that.computePrice();
				if(buynum ==0){
					that.numkey = -1;
					console.log(that.numkey,'=====numkey');
					return;
				}
			
			},
			computePrice(){
				var key = this.numkey;
				var deposit = this.datalist[key]
				var totalprice = deposit.buynum * deposit.money;
				this.totalprice = parseFloat(totalprice).toFixed(2);
			
			},
			toSubmit(){
				var that = this;
				var key = that.numkey;
				if(key ==-1){
					app.error('选择需要退还的'+that.t('押金'));
					return;
				}
				if(that.recycle_type ==''){
					app.error('请选择回收方式');
					return;
				}
				if(that.to_door_status ==1 ){
					console.log(that.address,'that.address');
					console.log(that.address.id,'that.address.id');
					if(!that.address || !that.address.id){
						app.error('请选择取件地址');
						return;
					}
				}

				// if(that.pay_pic ==''){
				// 	app.error('请上传支付凭证');
				// 	return;
				// }
				if(that.goods_pic ==''){
					app.error('请上传照片凭证');
					return;
				}
				if(that.payment_code ==''){
					app.error('请上传收款码');
					return;
				}
				if(that.realname ==''){
					app.error('请输入真实姓名');
					return;
				}
				
				var deposit = that.datalist[key];
				var id = deposit.id;
				var num = deposit.buynum;
				that.loading = true;
				var address = '';
				if(that.address){
					var address = that.address.address;
					if(that.address.floor){
						address +=that.address.floor+'楼';
					}
					if(that.address.room){
						address +=that.address.room;
					}
				}
				
				var info={
					'to_door_status' : that.to_door_status,
					'pay_pic':that.pay_pic,
					'goods_pic' :that.goods_pic,
					'payment_code' : that.payment_code,
					'realname' :that.realname,
					'linkman' :that.address.name,
					'tel' :that.address.tel,
					'area' :that.address.area,
					'address' :address,
					'latitude':that.address.latitude,
					'longitude':that.address.longitude,
					'recycle_type' :that.recycle_type
				}
				app.post('ApiDeposit/createRefundOrder',{id:id,num:num,info:info}, function(res) {
					that.loading = false;
					if(res.status ==0){
						app.error(res.msg);
						return;
					}
					app.success(res.msg);
					setTimeout(function(){
						app.goback()
					},1000)
				})
			},
			switchChange(index){
				this.to_door_status = index;
			},
			uploadimg(e){
				var that = this;
				var type = e.currentTarget.dataset.key;
				app.chooseImage(function(urls){
					if(type =='goods_pic'){
						that.goods_pic = urls[0];
					}else if(type =='pay_pic'){
						that.pay_pic = urls[0];
					}else if(type =='payment_code'){
						that.payment_code = urls[0];
					}
				
				},1)
			},
			showRecycle(){
				this.$refs.refPopup.open();
			},
			recycleChange(e){
				var id = e.currentTarget.dataset.id;
				var key = e.currentTarget.dataset.key;
				this.recycle_type = id;
				var recycle = this.sysset.recycle_type_list[key];
				var name = recycle.name
				this.recycle_type_name = name;
				this.$refs.refPopup.close();
			}
		}
	}
</script>

<style>
	.top-view{width: 100%;height: 300rpx;padding: 30rpx;align-items: flex-start;justify-content: space-between;}
	.top-view .left-view{padding: 20rpx;}
	.top-view .left-view .title-view{font-size: 24rpx;color: #fff;opacity: .7;}
	.top-view .left-view .price-view{font-size: 56rpx;font-weight: bold;color: #fff;margin-top: 10rpx;}
	.top-view .right-view{width: 200rpx;height: 200rpx;}
	.top-view .right-view image{width: 100%;height: 100%;}
	.content-view{width: 100%;height:auto;background: #fff;border-radius: 40rpx 40rpx 0rpx 0rpx;position: absolute;top: 240rpx;left: 0;padding: 30rpx 20rpx;}
	.content-view .filter-view{width: 100%;align-items: center;}
	.content-view .filter-view .filter-title{font-size: 30rpx;font-weight: bold;color: #333;}
	.content-view .picker-view{align-items: center;}
	.content-view .picker-view .jiantou-icon{width: 30rpx;height: 30rpx;margin-left: 10rpx;}
	.content-view .picker-view .picker-text{font-size: 26rpx;color: #999;}
	.content-view .content-list{width: 100%;margin: 20rpx 0rpx;}
	.content-view .content-list .list-options{width: 100%;border-radius: 16rpx;padding: 15rpx;margin-bottom: 20rpx;border: 0.5px solid;}
	.content-view .content-list .list-options .img-view{width: 170rpx;height: 170rpx;border-radius: 16rpx;overflow: hidden;}
	.content-list .list-options .img-view image{width: 100%;height: 100%;}
	.content-list .list-options .info-view{width: calc(100% - 170rpx);height: 170rpx;padding-left: 15rpx;padding-top: 10rpx;padding-bottom: 10rpx;
	justify-content: space-between;}
	.list-options .info-view .title-view{font-size: 30rpx;font-weight: bold;color: #333;}
	.list-options .info-view .price-fun-view{}
	.list-options .info-view .price-fun-view .price-text{font-size: 34rpx;font-weight: bold;}
	.list-options .info-view .price-fun-view .fun-view{}
	.price-fun-view .fun-view .input-view{width: 70rpx;border: 1px res solid;text-align: center;font-size: 30rpx;color: #000;text-align: center;}
	.price-fun-view .fun-view .fun-class{width: 46rpx;height: 46rpx;border-radius: 50rpx;overflow: hidden;font-size: 36rpx;font-weight: bold;border: 2px solid;line-height: 46rpx;}
	.price-fun-view .fun-view .add-class{color: #fff;}

	.buy-but-view{position: fixed;bottom: 0;left:0;background: #fff;width: 100%;height: calc(140rpx + env(safe-area-inset-bottom));z-index: 2;}
	.buy-but-view .buy-but{width: 96%;font-size: 32rpx;letter-spacing: 2rpx;text-align: center;border-radius: 40rpx;overflow: hidden;margin: 20rpx auto 0;color: #fff;
	padding: 20rpx 0rpx;}
	/*  */
	.input-info-view{width: 100%;height: 200rpx;padding: 0rpx 10rpx;border-bottom: 1px #ededee solid;}
	.input-info-view .title-view{font-size: 30rpx;color: #000;font-weight: bold;}
	.input-info-view .title-desc{color: #9e9e9e;padding: 10rpx 0;}
	.input-info-view .switch-view{}
	.input-info-view .switch-view .switch-options{width: 120rpx;text-align: center;font-size: 28rpx;color: #999;padding: 6rpx 0rpx;border-radius: 40rpx;overflow: hidden;
	margin-left: 20rpx;border: 1px solid;}
	.input-info-view .upload-img{width: 150rpx;height: 150rpx;border-radius: 16rpx;overflow: hidden;position: relative;}
	.input-info-view .upload-img .upload-img-class{width: 100%;height:100%}
	.input-info-view .upload-img .upload-icon-view {width: 100%;height: 100%;background: #f9fafb;}
	.input-info-view .upload-img .upload-icon-view .icon-class{width: 70rpx;height:70rpx;}
	.input-info-view .upload-img .close-view{width: 40rpx;height: 40rpx;border-radius: 50%;position: absolute;top: 5rpx;right: 5rpx;}
	.input-info-view .upload-img .close-view image{width: 100%;height: 100%;}
	.input-info-view .info-input-view{width: auto;}
	.input-info-view .info-input-view .input-class{text-align: right;font-size: 28rpx;color: #333;}
	.address-add {width: 70%;background: #fff;border-radius: 20rpx;min-height: 140rpx;text-align: right;}
	.address-add .f1 {margin-right: 20rpx; flex:1;}
	.address-add .f1 .img {width: 66rpx;height: 66rpx;}
	.address-add .f2 {color: #666;margin-right: 10rpx;}
	.arrowright {width: 26rpx;height: 26rpx;}
	.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
	.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
	.radio .radio-img{width:100%;height:100%;display:block}
	.bname{    font-size: 24rpx;color: #616161;}
</style>