<template>
	<view class="container">
		<block v-if="isload">
			<block v-if="cartlist.length>0">
				<view class="cartmain">
					<block v-for="(item, index) in cartlist" :key="item.bid">
						<view class="item">
							<view class="btitle">
								<view @tap.stop="changeradio" :data-index="index" class="radio" :style="item.checked ? 'background:'+t('color1')+';border:0' : ''">
									<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
								</view>
								<view class="btitle-name" @tap="goto" :data-url="item.bid==0?indexurl:'/pagesExt/business/index?id=' + item.business.id">
									{{item.business.name}}</view>
								<view class="flex1"> </view>
								<view class="btitle-del" @tap="cartdeleteb" :data-bid="item.bid">
									<image class="img" :src="pre_url+'/static/img/del.png'" /><text style="margin-left:10rpx">删除</text>
								</view>
							</view>
							<view class="content" v-for="(item2,index2) in item.prolist" @tap="goto" :data-url="'/pages/shop/product?id=' + item2.product.id" :key="index2">
								<view @tap.stop="changeradio2" :data-index="index" :data-index2="index2" class="radio" :style="item2.checked ? 'background:'+t('color1')+';border:0' : ''">
									<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
								</view>
								<view class="proinfo" :style="(item.prolist).length == index2+1 ? 'border:0' : ''">
									<image :src="item2.guige.pic?item2.guige.pic:item2.product.pic" class="img" />
									<view class="detail">
										<view class="title"><text>{{item2.product.name}}</text></view>
										<view class="desc"><text>{{item2.guige.name}}</text></view>
										<view class="price" :style="{color:t('color1')}">
											<view>
												<text style="font-size:24rpx">￥</text>{{item2.guige.sell_price}}
											</view>
										</view>
										<view class="addnum">
											<view class="minus" @tap.stop="gwcminus" :data-index="index" :data-index2="index2"
												:data-cartid="item2.id" :data-num="item2.num" :data-limit_start="item2.product.limit_start"
												:data-limit_start_guige="item2.guige.limit_start">
												<image class="img" :src="pre_url+'/static/img/cart-minus.png'" />
											</view>
											<input class="input" @tap.stop="" type="number" :value="item2.num" @blur="gwcinput"
												:data-max="item2.guige.store_nums" :data-index="index" :data-index2="index2"
												:data-cartid="item2.id" :data-num="item2.num" :data-limit_start="item2.product.limit_start"
												:data-limit_start_guige="item2.guige.limit_start"></input>
											<view class="plus" @tap.stop="gwcplus" :data-index="index" :data-index2="index2"
												:data-max="item2.guige.store_nums" :data-num="item2.num" :data-cartid="item2.id"
												:data-limit_start="item2.product.limit_start" :data-limit_start_guige="item2.guige.limit_start">
												<image class="img" :src="pre_url+'/static/img/cart-plus.png'" />
											</view>
										</view>
									</view>
									<view class="prodel" @tap.stop="cartdelete" :data-cartid="item2.id">
										<image class="prodel-img" :src="pre_url+'/static/img/del.png'" />
									</view>
								</view>
							</view>
						</view>
					</block>
				</view>
			</block>

			<block v-if="cartlist.length<=0">
				<view class="data-empty">
					<image :src="pre_url+'/static/img/cartnull.png'" class="data-empty-img" style="width:120rpx;height:120rpx" />
					<view class="data-empty-text" style="margin-top:20rpx;font-size:24rpx">采购单空空如也~</view>
					<view style="width:400rpx;border:0;height:80rpx;line-height:80rpx;margin:40rpx auto;border-radius:6rpx;color:#fff"
						:style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"
						@tap="goto" :data-url="indexurl" data-opentype="reLaunch">去选购</view>
				</view>
			</block>
		</block>
		<loading v-if="loading"></loading>
		<block>
			<view style="height:auto;position:relative">
				<view style="width:100%;height:110rpx"></view>
				<view class="footer flex" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
					<view @tap.stop="changeradioAll" class="radio" :style="allchecked ? 'background:'+t('color1')+';border:0' : ''">
						<image class="radio-img" :src="pre_url+'/static/img/checkd.png'" />
					</view>
					<view @tap.stop="changeradioAll" class="text0">全选（{{selectedcount}}）</view>
					<view class="flex1"></view>
					<view class="op" style="width:150rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center" @tap.stop="goto" data-url="sharerecord">分享记录</view>
					<view class="op" style="width:150rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center" @tap="scanQRCode">扫码加购</view>
					<view class="op" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap="purchaseOrderSetOpen">生成采购单</view>
				</view>
			</view>
		</block>
		<uni-popup id="purchaseOrderSet" ref="purchaseOrderSet" type="dialog">
			<view class="uni-popup-dialog">
				<view class="uni-dialog-title">
					<text class="uni-dialog-title-text">设置</text>
				</view>
				<view class="uni-dialog-content">
					<view>
						<view class="flex-y-center flex-x-center form-item">
							<view class="form-label">有效期：</view>
							<input type="number" class="text-input" :value="validity" @input="inputChange" />
							<view class="form-label" style="margin-left: 10rpx;">天</view>
						</view>
					</view>
				</view>
				<view class="uni-dialog-button-group">
					<view class="uni-dialog-button" @click="purchaseOrderSetClose">
						<text class="uni-dialog-button-text">取消</text>
					</view>
					<view class="uni-dialog-button uni-border-left" @click="createOrder">
						<text class="uni-dialog-button-text uni-button-color">确定</text>
					</view>
				</view>
			</view>
		</uni-popup>
		<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
		<wxxieyi></wxxieyi>
	</view>
</template>

<script>
	var app = getApp();
	export default {
		data() {
			return {
				opt: {},
				loading: false,
				isload: false,
				menuindex: -1,
				pre_url: app.globalData.pre_url,
				indexurl: app.globalData.indexurl,
				cartlist: [],
				totalprice: '0.00',
				selectedcount: 0,
				allchecked: true,
				xixie: false,
				xixie_cartlist: '',
				usdrate: 0,
				usdtotalprice: 0,
				validity:1 //采购单有效期默认1天
			};
		},

		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.isload = true
		},
		onShow: function() {
			if (app.globalData.platform == 'wx' && app.globalData.hide_home_button == 1) {
				uni.hideHomeButton();
			}
			this.getdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		methods: {
			getdata: function() {
				var that = this;
				var bid = that.opt.bid ? that.opt.bid : '';
				if (bid) {
					that.indexurl = '/pagesExt/business/index?id=' + bid;
				}
				//如果设置过地域限制【定位模式下】
				var locationCache = that.checkLocationCache();
				that.loading = true;
				app.get('ApiPurchaseOrder/cart', {
					bid: bid,
					latitude: locationCache.latitude,
					longitude: locationCache.longitude,
					area: locationCache.area,
					mendian_id: locationCache.mendian_id
				}, function(res) {
					that.loading = false;
					that.cartlist = res.cartlist;
					that.usdrate = res.usdrate
					that.calculate();
					that.loaded();
				});
			},
			checkLocationCache: function() {
				var that = this
				var locationCache = app.getLocationCache();
				if (!locationCache.latitude) {
					locationCache.latitude = ''
				}
				if (!locationCache.longitude) {
					locationCache.longitude = ''
				}
				if (!locationCache.mendian_id) {
					locationCache.mendian_id = 0
				}
				if (!locationCache.area) {
					locationCache.area = ''
				}
				if (locationCache && locationCache.area) {
					var area = '';
					var areaArr = locationCache.area.split(',');
					var showlevel = locationCache.showlevel ? locationCache.showlevel : 2
					if (showlevel == 1 && areaArr.length > 0) {
						area = areaArr[0]
					} else if (showlevel == 2 && areaArr.length > 1) {
						area = areaArr[0] + ',' + areaArr[1]
					} else if (showlevel == 3 && areaArr.length > 2) {
						area = areaArr[0] + ',' + areaArr[1] + ',' + areaArr[2]
					}
					locationCache.area = area;
				}
				return locationCache;
			},
			calculate: function() {
				var that = this;
				var cartlist = that.cartlist;
				var ids = [];
				var totalprice = 0.00;
				var totalservicefee = 0.00;
				var selectedcount = 0;
				for (var i in cartlist) {
					for (var j in cartlist[i].prolist) {
						if (cartlist[i].prolist[j].checked) {
							ids.push(cartlist[i].prolist[j].id);
							var thispro = cartlist[i].prolist[j];
							totalprice += thispro.guige.sell_price * thispro.num;
							selectedcount += thispro.num;
							if (!app.isNull(thispro.product.service_fee_switch) && thispro.product.service_fee_switch == 1) {
								totalservicefee += thispro.guige.service_fee * thispro.num;
							}
						}
					}
				}
				that.totalprice = totalprice.toFixed(2);
				if (that.showprice_dollar && that.usdrate) {
					that.usdtotalprice = (totalprice / that.usdrate).toFixed(2);
				}

				that.selectedcount = selectedcount;
			},
			changeradio: function(e) {
				var that = this;
				var xixie = that.xixie;
				var index = e.currentTarget.dataset.index;
				var type = e.currentTarget.dataset.type ? e.currentTarget.dataset.type : '';
				if (type == 2) {

				} else {
					var cartlist = that.cartlist;
					var checked = cartlist[index].checked;
					if (checked) {
						cartlist[index].checked = false;
					} else {
						cartlist[index].checked = true;
					}
					for (var i in cartlist[index].prolist) {
						cartlist[index].prolist[i].checked = cartlist[index].checked;
					}
					that.cartlist = cartlist;
				}
				that.calculate();
			},

			changeradio2: function(e) {
				var that = this;
				var type = e.currentTarget.dataset.type ? e.currentTarget.dataset.type : '';
				var index = e.currentTarget.dataset.index;
				var index2 = e.currentTarget.dataset.index2;
				if (!type) {
					var cartlist = that.cartlist;
					var checked = cartlist[index].prolist[index2].checked;
					if (checked) {
						cartlist[index].prolist[index2].checked = false;
					} else {
						cartlist[index].prolist[index2].checked = true;
					}
					var isallchecked = true;
					for (var i in cartlist[index].prolist) {
						if (cartlist[index].prolist[i].checked == false) {
							isallchecked = false;
						}
					}
					if (isallchecked) {
						cartlist[index].checked = true;
					} else {
						cartlist[index].checked = false;
					}
					that.cartlist = cartlist;
				} else {

				}

				that.calculate();
			},
			changeradioAll: function() {
				var that = this;
				var cartlist = that.cartlist;
				var allchecked = that.allchecked
				for (var i in cartlist) {
					cartlist[i].checked = allchecked ? false : true;
					for (var j in cartlist[i].prolist) {
						cartlist[i].prolist[j].checked = allchecked ? false : true;
					}
				}
				that.cartlist = cartlist;
				that.allchecked = allchecked ? false : true;
				that.calculate();
			},
			cartdelete: function(e) {
				var that = this;
				var id = e.currentTarget.dataset.cartid;
				var type = e.currentTarget.dataset.type ? e.currentTarget.dataset.type : '';
				app.confirm('确定要从采购单移除吗?', function() {
					app.post('ApiPurchaseOrder/cartdelete', {
						id: id,
						type: type
					}, function(data) {
						app.success(data.msg);
						if (data.status == 1) {
							setTimeout(function() {
								that.getdata();
							}, 1000);
						}
					});
				});
			},
			cartdeleteb: function(e) {
				var that = this;
				var bid = e.currentTarget.dataset.bid;
				var type = e.currentTarget.dataset.type ? e.currentTarget.dataset.type : '';

				app.confirm('确定要从采购单移除吗?', function() {
					app.post('ApiPurchaseOrder/cartdelete', {
						bid: bid,
						type: type
					}, function(data) {
						app.success('操作成功');
						setTimeout(function() {
							that.getdata();
						}, 1000);
					});
				});
			},
			//加
			gwcplus: function(e) {
				var type = e.currentTarget.dataset.type ? e.currentTarget.dataset.type : '';
				var index = parseInt(e.currentTarget.dataset.index);
				var index2 = parseInt(e.currentTarget.dataset.index2);
				var cartid = e.currentTarget.dataset.cartid;

				var num = parseInt(e.currentTarget.dataset.num);
				var cartlist = this.cartlist;
				cartlist[index].prolist[index2].num++;
				this.cartlist = cartlist
				
				this.calculate();
				var that = this;
				app.post('ApiPurchaseOrder/cartChangenum', {
					id: cartid,
					num: num + 1,
					type: type
				}, function(data) {
					if (data.status == 1) {
						//that.getdata();
					} else if (data.status == 2) {
						app.error(data.msg);
						cartlist[index].prolist[index2].num = data.num;
					} else {
						app.error(data.msg);
						cartlist[index].prolist[index2].num--;
					}
				});
			},
			//减
			gwcminus: function(e) {
				var type = e.currentTarget.dataset.type ? e.currentTarget.dataset.type : '';
				var index = parseInt(e.currentTarget.dataset.index);
				var index2 = parseInt(e.currentTarget.dataset.index2);
				var cartid = e.currentTarget.dataset.cartid;

				var num = parseInt(e.currentTarget.dataset.num);
				if (num == 1) return;

				var cartlist = this.cartlist;
				cartlist[index].prolist[index2].num--;
				this.cartlist = cartlist
				this.calculate();


				var that = this;
				app.post('ApiPurchaseOrder/cartChangenum', {
					id: cartid,
					num: num - 1,
					type: type
				}, function(data) {
					if (data.status == 1) {
						//that.getdata();
					} else {
						app.error(data.msg);
						cartlist[index].prolist[index2].num++;
					}
				});
			},
			//输入
			gwcinput: function(e) {
				var type = e.currentTarget.dataset.type ? e.currentTarget.dataset.type : '';
				var index = parseInt(e.currentTarget.dataset.index);
				var index2 = parseInt(e.currentTarget.dataset.index2);
				var maxnum = parseInt(e.currentTarget.dataset.max);
				var cartid = e.currentTarget.dataset.cartid;
				var num = e.currentTarget.dataset.num;
				var newnum = parseInt(e.detail.value);
				if (num == newnum) return;
				if (newnum < 1) {
					app.error('最小数量为1');
					return;
				}
				var cartlist = this.cartlist;
				cartlist[index].prolist[index2].num = newnum;
				this.cartlist = cartlist
				this.calculate();
				
				var that = this;
				app.post('ApiPurchaseOrder/cartChangenum', {
					id: cartid,
					num: newnum,
					type: type
				}, function(data) {
					if (data.status == 1) {
						//that.getdata();
					} else if (data.status == 2) {
						app.error(data.msg);
						cartlist[index].prolist[index2].num = data.num;
					} else {
						app.error(data.msg);
					}
				});
			},
			purchaseOrderSetOpen:function(){
				this.$refs.purchaseOrderSet.open();
			},
			purchaseOrderSetClose:function(){
				this.$refs.purchaseOrderSet.close();
			},
			inputChange:function(e){
				this.validity = e.detail.value;
			},
			createOrder: function() {
				var that = this;
				var cartlist = that.cartlist;
				var ids = [];
				var prodata = [];
				that.$refs.purchaseOrderSet.close();
				for (var i in cartlist) {
					for (var j in cartlist[i].prolist) {
						if (cartlist[i].prolist[j].checked) {
							var thispro = cartlist[i].prolist[j];
							var tmpprostr = thispro.product.id + ',' + thispro.guige.id + ',' + thispro.num;
							if (thispro.glassrecord) {
								tmpprostr += ',' + thispro.glassrecord.id
							}
							prodata.push(tmpprostr);
						}
					}
				}
				
				var validity = that.validity;
				
				if(validity <= 0){
					return app.error('设置的有效期无效');
				}
			
				if (prodata == undefined || prodata.length == 0) {
					app.error('请先选择产品');
					return;
				}
				app.showLoading('生成中');
				app.post('ApiPurchaseOrder/createPurchaseOrder', {
					prodata: prodata,
					validity_day:validity
				}, function(res) {
					app.showLoading(false);
					if(res.status == 1){
						app.success(res.msg);
						//进入分享页
						setTimeout(function () {
							app.goto('posterpurchaseorder?orderid=' + res.orderid);
						}, 1000)
					}else{
						return app.error(res.msg);
					}
				});
			},
			scanQRCode:function(e){
				var that = this;
				//调用二维码扫描接口
				if(app.globalData.platform == 'h5'){
					// #ifdef H5
					app.alert('请使用微信扫一扫功能扫码');return;
					// #endif
				}else if(app.globalData.platform == 'mp'){
					var jweixin = require('jweixin-module');
					jweixin.ready(function () {   //需在用户可能点击分享按钮前就先调用
						jweixin.scanQRCode({
							needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
							scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
							success: function (res) {
								var content = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
								if(content.indexOf(',') > 0){
									content = content.split(',')[1];
								}
								that.addPurchase(content,that);
							},
							fail:function(err){
								app.error(err.errMsg);
							}
						});
					});
				}else{
					// #ifdef MP-WEIXIN
					uni.scanCode({
						success: function(res){
							var content = res.result;
							if(!content){
							  app.alert('请扫描正确的商品码');
							  return;
							}
							that.addPurchase(content,that);
						},
						fail:function(err){
							app.error('扫码失败');
						  console.error('扫码失败：', err);
						}
					});
					// #endif
				}
				
			},
			addPurchase:function(code,that){
				if(code == '') return false;
				that.loading = true;
				//查询商品信息
				app.post('ApiShop/scanCodeSearchGoods', {specs_number:code}, function (p) {
					that.loading = false;
					if (p.status == 1) {
						//加入购物车
						app.post('ApiPurchaseOrder/addcart', {proid: p.product_id,ggid: p.guige_id,num: 1}, function (res) {
							if (res.status == 1) {
								that.getdata();
							} else {
								app.error(res.msg);
							}
						});
					}else{
						app.alert(p.msg);
						return;
					}
				});
			}
		}
	};
</script>
<style>
	.container{height:100%}
	.cartmain .item{width:94%;margin:20rpx 3%;background:#fff;border-radius:20rpx;padding:30rpx 3%}
	.cartmain .item .radio{flex-shrink:0;width:32rpx;height:32rpx;background:#FFFFFF;border:2rpx solid #BFBFBF;border-radius:50%;margin-right:30rpx}
	.cartmain .item .radio .radio-img{width:100%;height:100%}
	.cartmain .item .btitle{width:100%;display:flex;align-items:center;margin-bottom:30rpx}
	.cartmain .item .btitle-name{color:#222222;font-weight:bold;font-size:28rpx}
	.cartmain .item .btitle-del{display:flex;align-items:center;color:#999999;font-size:24rpx}
	.cartmain .item .btitle-del .img{width:24rpx;height:24rpx}
	.cartmain .item .content{width:100%;position:relative;display:flex;align-items:center}
	.cartmain .item .content .proinfo{flex:1;display:flex;padding:20rpx 0;border-bottom:1px solid #f2f2f2}
	.cartmain .item .content .proinfo .img{width:176rpx;height:176rpx}
	.cartmain .item .content .detail{flex:1;margin-left:20rpx;height:176rpx;position:relative}
	.cartmain .item .content .detail .title{color:#222222;font-weight:bold;font-size:28rpx;line-height:34rpx;margin-bottom:0;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:68rpx}
	.cartmain .item .content .detail .desc{margin-top:0rpx;height:auto;color:#999;overflow:hidden;font-size:20rpx}
	.cartmain .item .content .detail .desc text{width:350rpx;display:block;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden}
	.cartmain .item .content .prodel{width:24rpx;height:24rpx;position:absolute;top:90rpx;right:0}
	.cartmain .item .content .prodel-img{width:100%;height:100%}
	.cartmain .item .content .price{margin-top:10rpx;height:60rpx;line-height:60rpx;font-size:32rpx;font-weight:bold;display:flex;align-items:center}
	.cartmain .item .content .addnum{position:absolute;right:0;bottom:0rpx;font-size:30rpx;color:#666;width:auto;display:flex;align-items:center}
	.cartmain .item .content .addnum .plus{width:65rpx;height:48rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
	.cartmain .item .content .addnum .minus{width:65rpx;height:48rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
	.cartmain .item .content .addnum .img{width:24rpx;height:24rpx}
	.cartmain .item .content .addnum .i{padding:0 20rpx;color:#2B2B2B;font-weight:bold;font-size:24rpx}
	.cartmain .item .content .addnum .input{flex:1;width:50rpx;border:0;text-align:center;color:#2B2B2B;font-size:24rpx;margin:0 15rpx}
	.cartmain .item .bottom{width:94%;margin:0 3%;border-top:1px #e5e5e5 solid;padding:10rpx 0px;overflow:hidden;color:#ccc;display:flex;align-items:center;justify-content:flex-end}
	.cartmain .item .bottom .f1{display:flex;align-items:center;color:#333}
	.cartmain .item .bottom .f1 image{width:40rpx;height:40rpx;border-radius:4px;margin-right:4px}
	.cartmain .item .bottom .op{border:1px #ff4246 solid;border-radius:10rpx;color:#ff4246;padding:0 10rpx;height:46rpx;line-height:46rpx;margin-left:10rpx}
	.footer{width:100%;background:#fff;margin-top:5px;position:fixed;left:0px;bottom:0px;z-index:8;display:flex;align-items:center;padding:0 20rpx;border-top:1px solid #EFEFEF}
	.footer .radio{flex-shrink:0;width:32rpx;height:32rpx;background:#FFFFFF;border:2rpx solid #BFBFBF;border-radius:50%;margin-right:10rpx}
	.footer .radio .radio-img{width:100%;height:100%}
	.footer .text0{color:#666666;font-size:24rpx}
	.footer .text1{height:110rpx;line-height:110rpx;color:#444;font-weight:bold;font-size:24rpx}
	.footer .text2{color:#F64D00;font-size:36rpx;font-weight:bold}
	.footer .text3{color:#F64D00;font-size:28rpx;font-weight:bold}
	.footer .op{width:190rpx;height:80rpx;line-height:80rpx;border-radius:6rpx;font-weight:bold;color:#fff;font-size:28rpx;text-align:center;margin-left:15rpx;margin-top:10rpx}
	.xihuan{height:auto;overflow:hidden;display:flex;align-items:center;width:100%;padding:12rpx 160rpx}
	.xihuan-line{height:auto;padding:0;overflow:hidden;flex:1;height:0;border-top:1px solid #eee}
	.xihuan-text{padding:0 32rpx;text-align:center;display:flex;align-items:center;justify-content:center}
	.xihuan-text .txt{color:#111;font-size:30rpx}
	.xihuan-text .img{text-align:center;width:36rpx;height:36rpx;margin-right:12rpx}
	.prolist{width:100%;height:auto;padding:8rpx 20rpx}
	.data-empty{width:100%;text-align:center;padding-top:100rpx;padding-bottom:100rpx}
	.data-empty-img{width:300rpx;height:300rpx;display:inline-block}
	.data-empty-text{display:block;text-align:center;color:#999999;font-size:32rpx;width:100%;margin-top:30rpx}
	
	.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
	.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
	.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
	.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 5px 15px 5px;}
	.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
	.uni-dialog-content .form-item{margin:20rpx 20rpx;height: 80rpx;}
	.uni-dialog-content .form-label{font-size:28rpx;color:#555}
	.uni-dialog-content .text-input{border: 1px #eee solid;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;width:120rpx;text-align: center;}
	.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
	.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
	.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
	.uni-dialog-button-text {font-size: 14px;}
	.uni-button-color {color: #007aff;}
	.danhao-input-view{border: 1px #eee solid;display: flex;align-items: center;flex: 1;}
	.danhao-input-view image{width: 60rpx;height: 60rpx;}
	
</style>