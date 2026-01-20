<template>
	<view class="container">
		<block v-if="isload">
			<form @submit="topay">
				<view v-if="sindex==1" class="address-add">
					<view class="linkitem">
						<text class="f1">联 系 人：</text>
						<input type="text" class="input" :value="linkman" placeholder="请输入您的姓名" @input="inputLinkman" placeholder-style="color:#626262;font-size:28rpx;"/>
					</view>
					<view class="linkitem">
						<text class="f1">联系电话：</text>
						<input type="text" class="input" :value="tel" placeholder="请输入您的手机号" @input="inputTel" placeholder-style="color:#626262;font-size:28rpx;"/>
					</view>
				</view>
				<view v-else class="address-add flex-y-center" @tap="goto"
					:data-url="'/pagesB/address/'+(address.id ? 'address' : 'addressadd')+'?fromPage=buy&type=1'">
					<view class="f1">
						<image class="img" src="/static/img/address.png" />
					</view>
					<view class="f2 flex1" v-if="address.id">
						<view style="font-weight:bold;color:#111111;font-size:30rpx">{{address.name}} {{address.tel}} <text v-if="address.company">{{address.company}}</text></view>
						<view style="font-size:24rpx">{{address.area}} {{address.address}}</view>
					</view>
					<view v-else class="f2 flex1">请选择您的地点</view>
					<image src="/static/img/arrowright.png" class="f3"></image>
				</view>
				<view class="buydata">			
					<view class="bcontent">
						<view class="btitle">
							服务信息
						</view>
						<view class="product">
							<view class="item flex">
								<view class="img" @tap="goto" :data-url="'product2?id=' + allbuydata.skillId">
									<image v-if="allbuydata.pic" :src="allbuydata.pic"></image>
									<image v-else :src="allbuydata.pic"></image>
								</view>
								<view class="info flex1">
									<view class="f1">{{allbuydata.name}}</view>
								
									<view class="f3"><text style="font-weight:bold;">￥{{allbuydata.price}}{{allbuydata.unit}}</text>
									<text style="padding-left:20rpx"> × {{allbuydata.num}}</text></view>
								</view>
							</view>
						</view>	
					</view>
					<view class="bcontent2">
						<view class="btitle2">
							服务方式
						</view>	
		
							<view class="price" >
								<view class="f1">预约时间</view>
								<view class="f2" >{{yydate}}</view>
							</view>
							<view class="price" >
								<view class="f1">服务人员</view>
								<view class="f2" >{{master.name}}</view>
							</view>
							<view class="price" >
								<view class="f1">服务类型</view>
								<view class="f2" >{{allbuydata.serviceType}}</view>
							</view>
					</view>
					<view class="bcontent2">
						<view class="price">
							<text class="f1">服务价格</text>
							<text class="f2">¥{{allbuydata.price}}</text>
						</view>

						<view class="price" >
							<text class="f1">路程费用</text>
							<view class="f2">
								<text style="color:red; ">{{master.juli}}km </text> 
								<text> ¥{{allbuydata.freight_price}}</text>
							</view>
						</view>
						
						<view class="form-item" >
							<view class="label">顾客备注<text> </text></view>
							
							<input name="remark" class='input' placeholder="请输入备注" placeholder-style="font-size:28rpx"/>
						</view>
						
					</view>
					
				</view>


				<view style="width: 100%; height:182rpx;"></view>
				<view class="footer flex">
					<view class="text1 flex1">总计：
						<text style="font-weight:bold;font-size:36rpx">￥{{totalprice}}</text>
					</view>
					<button class="op" form-type="submit" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">
						确认提交</button>
				</view>
			</form>

		</block>
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
				opt: {},
				loading: false,
				isload: false,
				menuindex: -1,
				pre_url:app.globalData.pre_url,
				test:'test',
				address: [],
				totalprice: '0.00',
				couponvisible: false,
				bid: 0,
				nowbid: 0,
				needaddress: 1,
				linkman: '',
				tel: '',
				userinfo: {},
				latitude: "",
				longitude: "",
				allbuydata: {},
				alltotalprice: "",
				type11visible: false,
				type11key: -1,
				regiondata: '',
				items: [],
				editorFormdata:[],
				sindex:0,
				prodata:'',
				yydate:'',
				master:[]
			};
		},

		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.yydate = opt.yydate
			this.prodata = opt.prodata;
			this.getdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		methods: {
			getdata: function() {
				var that = this;			
				app.get('ApiYuyue2/buy', {
					prodata: that.opt.prodata,
				}, function(res) {
					if(res.status==0){		app.error('技能获取失败'); }
					that.address = res.address;
					that.linkman = res.linkman;
					that.tel = res.tel;
					that.allbuydata = res.allbuydata;
					that.totalprice = res.totalprice;
					that.rdata = res.rdata;
					that.master = res.master;
				
					//	that.calculatePrice();
					that.loaded();
				});
			},
			showCouponList: function(e) {
				this.couponvisible = true;
				this.bid = e.currentTarget.dataset.bid;
			},
			handleClickMask: function() {
				this.couponvisible = false;
			},
	
			//提交并支付
			topay: function(e) {
				var formdata = e.detail.value;
			
				var that = this;
				var addressid = this.address.id;
				var linkman = this.linkman;
				var tel = this.tel;
				var frompage = that.opt.frompage ? that.opt.frompage : '';
				var allbuydata = that.allbuydata;
				if(addressid == undefined) {
					app.error('请选择地址');
					return;
				}
				if(!linkman || !tel) {
					app.error('请填写联系人及联系电话');
					return;
				}
				var remark =  formdata.remark;
				var yydate = that.yydate;
				//console.log(buydata);return;
				app.showLoading('提交中');
				app.post('ApiYuyue2/createOrder', {
					frompage: frompage,
					addressid: addressid,
					linkman: linkman,
					tel: tel,
					remark:remark,
					yydate:that.yydate,
					prodata:that.prodata,
				}, function(res) {
					app.showLoading(false);
					if (res.status == 1) {
							app.goto('/pagesExt/pay/pay?id=' + res.payorderid);
						//that.showsuccess(res.data.msg);
					}else{
						app.error(res.msg);
						return;
						
					}
					//app.error('订单编号：' +res.payorderid);
				
				});
			},
			editorChooseImage: function (e) {
				var that = this;
				var bid = e.currentTarget.dataset.bid;
				var idx = e.currentTarget.dataset.idx;
				var editorFormdata = that.allbuydata[bid].editorFormdata;
				if(!editorFormdata) editorFormdata = [];
				app.chooseImage(function(data){
					editorFormdata[idx] = data[0];
					// console.log(editorFormdata)
					that.allbuydata[bid].editorFormdata = editorFormdata
					that.test = Math.random();
				})
			},editorBindPickerChange:function(e){
				var that = this;
				var bid = e.currentTarget.dataset.bid;
				var idx = e.currentTarget.dataset.idx;
				var val = e.detail.value;
				var editorFormdata = that.allbuydata[bid].editorFormdata;
				if(!editorFormdata) editorFormdata = [];
				editorFormdata[idx] = val;
				// console.log(editorFormdata)
				that.allbuydata[bid].editorFormdata = editorFormdata;
				that.test = Math.random();
			},
			//选择服务方式
			selectFwtype: function(e) {
				var that = this;
				var index = e.currentTarget.dataset.index;
				that.sindex = index
			},
			inputLinkman: function (e) {
				this.linkman = e.detail.value
			},
			inputTel: function (e) {
				this.tel = e.detail.value
			},
		}
	}
</script>

<style>
	.redBg{color:#fff;padding:4rpx 16rpx;font-weight:normal;border-radius:8rpx;font-size:24rpx; width: auto; display: inline-block; margin-top: 4rpx;}
.address-add {width: 94%;margin: 20rpx 3%;background: #fff;border-radius: 20rpx;padding: 20rpx 3%;min-height: 140rpx;}
.address-add .f1 {margin-right: 20rpx}
.address-add .f1 .img {width: 66rpx;height: 66rpx;}
.address-add .f2 {color: #666;}
.address-add .f3 {width: 26rpx;height: 26rpx;}
.linkitem {width: 100%;padding: 1px 0;background: #fff;display: flex;align-items: center}.cf3 {width: 200rpx;height: 26rpx;display: block;
    text-align: right;}
.linkitem .f1 {width: 160rpx;color: #111111}
.linkitem .input {height: 50rpx;padding-left: 10rpx;color: #222222;font-weight: bold;font-size: 28rpx;flex: 1}
.buydata {width: 94%;margin: 0 3%;margin-bottom: 20rpx;}
.btitle {width: 100%;padding: 20rpx 20rpx;display: flex;align-items: center;color: #111111;font-weight: bold;font-size: 30rpx}
.btitle .img {width: 34rpx;height: 34rpx;margin-right: 10rpx}
.btitle2 {width: 100%; padding-top:20rpx;display: flex;align-items: center;color: #111111;font-weight: bold;font-size: 30rpx}
.btitle2 .img {width: 34rpx;height: 34rpx;margin-right: 10rpx}

.bcontent {width: 100%;padding: 0 20rpx;background: #fff;border-radius: 20rpx;}
.bcontent2 {width: 100%;padding: 0 30rpx; margin-top: 30rpx;background: #fff;border-radius: 20rpx;}
.product {width: 100%;border-bottom: 1px solid #f4f4f4}
.product .item {width: 100%;padding: 20rpx 0;background: #fff;border-bottom: 1px #ededed dashed;}
.product .item:last-child {border: none}
.product .info {padding-left: 20rpx;}
.product .info .f1 {color: #222222;font-weight: bold;font-size: 26rpx;line-height: 36rpx;margin-bottom: 10rpx;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp: 2;overflow: hidden;}
.product .info .f2 {color: #999999;font-size: 24rpx}
.product .info .f3 {color: #FF4C4C;font-size: 28rpx;display: flex;align-items: center;margin-top: 10rpx}
.product image {width: 140rpx;height: 140rpx}
.freight {width: 100%;padding: 20rpx 0;background: #fff;display: flex;flex-direction: column;}
.freight .f1 {color: #333;margin-bottom: 10rpx}
.freight .f2 {color: #111111;text-align: right;flex: 1}
.freight .f3 {width: 24rpx;height: 28rpx;}
.freighttips {color: red;font-size: 24rpx;}
.freight-ul {width: 100%;display: flex;}
.freight-li {flex-shrink: 0;display: flex;background: #F5F6F8;border-radius: 24rpx;color: #6C737F;font-size: 24rpx;text-align: center;height: 48rpx;line-height: 48rpx;padding: 0 28rpx;margin: 12rpx 10rpx 12rpx 0}

.price {width: 100%;padding: 20rpx 0;background: #fff;display: flex;align-items: center}
.price .f1 {color: #333}
.price .f2 {color: #111;font-weight: bold;text-align: right;flex: 1}
.price .f3 {width: 24rpx;height: 24rpx;}
.scoredk {width: 94%;margin: 0 3%;margin-bottom: 20rpx;border-radius: 20rpx;padding: 24rpx 20rpx;background: #fff;display: flex;align-items: center}
.scoredk .f1 {color: #333333}
.scoredk .f2 {color: #999999;text-align: right;flex: 1}
.remark {width: 100%;padding: 16rpx 0;background: #fff;display: flex;align-items: center}
.remark .f1 {color: #333;width: 200rpx}
.remark input {border: 0px solid #eee;height: 70rpx;padding-left: 10rpx;text-align: right}
.footer {width: 100%;background: #fff;margin-top: 5px;position: fixed;left: 0px;bottom: 0px;padding: 0 20rpx;display: flex;align-items: center;z-index: 8}
.footer .text1 {height: 110rpx;line-height: 110rpx;color: #2a2a2a;font-size: 30rpx;}
.footer .text1 text {color: #e94745;font-size: 32rpx;}
.footer .op {width: 200rpx;height: 80rpx;line-height: 80rpx;color: #fff;text-align: center;font-size: 30rpx;border-radius: 44rpx}
.storeitem {width: 100%;padding: 20rpx 0;display: flex;flex-direction: column;color: #333}
.storeitem .panel {width: 100%;height: 60rpx;line-height: 60rpx;font-size: 28rpx;color: #333;margin-bottom: 10rpx;display: flex}
.storeitem .panel .f1 {color: #333}
.storeitem .panel .f2 {color: #111;font-weight: bold;text-align: right;flex: 1}
.storeitem .radio-item {display: flex;width: 100%;color: #000;align-items: center;background: #fff;border-bottom: 0 solid #eee;padding: 8rpx 20rpx;}
.storeitem .radio-item:last-child {border: 0}
.storeitem .radio-item .f1 {color: #666;flex: 1}
.storeitem .radio {flex-shrink: 0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-left: 30rpx}
.storeitem .radio .radio-img {width: 100%;height: 100%}
.pstime-item {display: flex;border-bottom: 1px solid #f5f5f5;padding: 20rpx 30rpx;}
.pstime-item .radio {flex-shrink: 0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right: 30rpx}
.pstime-item .radio .radio-img {width: 100%;height: 100%}
.cuxiao-desc {width: 100%}
.cuxiao-item {display: flex;padding: 0 40rpx 20rpx 40rpx;}
.cuxiao-item .type-name {font-size: 28rpx;color: #49aa34;margin-bottom: 10rpx;flex: 1}
.cuxiao-item .radio {flex-shrink: 0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right: 30rpx}
.cuxiao-item .radio .radio-img {width: 100%;height: 100%}

.form-item {width: 100%;padding: 16rpx 0;background: #fff;display: flex;align-items: center;justify-content:space-between}
.form-item .label {color: #333;width: 200rpx;flex-shrink:0}
.form-item .radio{transform:scale(.7);}
.form-item .checkbox{transform:scale(.7);}
.form-item .input {border:0px solid #eee;height: 70rpx;padding-left: 10rpx;text-align: right;flex:1}
.form-item .textarea{height:140rpx;line-height:40rpx;overflow: hidden;flex:1;border:1px solid #eee;border-radius:2px;padding:8rpx}
.form-item .radio-group{display:flex;flex-wrap:wrap;justify-content:flex-end}
.form-item .radio{height: 70rpx;line-height: 70rpx;display:flex;align-items:center}
.form-item .radio2{display:flex;align-items:center;}
.form-item .radio .myradio{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:50%}
.form-item .checkbox-group{display:flex;flex-wrap:wrap;justify-content:flex-end}
.form-item .checkbox{height: 70rpx;line-height: 70rpx;display:flex;align-items:center}
.form-item .checkbox2{display:flex;align-items:center;height: 40rpx;line-height: 40rpx;}
.form-item .checkbox .mycheckbox{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:2px}
.form-item .picker{height: 70rpx;line-height:70rpx;flex:1;text-align:right}

.form-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.form-imgbox-close{position: absolute;display: block;width:32rpx;height:32rpx;right:-16rpx;top:-16rpx;color:#999;font-size:32rpx;background:#fff}
.form-imgbox-close .image{width:100%;height:100%}
.form-imgbox-img{display: block;width:180rpx;height:180rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.form-imgbox-img>.image{max-width:100%;}
.form-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.form-uploadbtn{position:relative;height:180rpx;width:180rpx}

.member_search{width:100%;padding:0 40rpx;display:flex;align-items:center}
.searchMemberButton{height:60rpx;background-color: #007AFF;border-radius: 10rpx;width: 160rpx;line-height: 60rpx;color: #fff;text-align: center;font-size: 28rpx;display: block;}
.memberlist{width:100%;padding:0 40rpx;height: auto;margin:20rpx auto;}
.memberitem{display:flex;align-items:center;border-bottom:1px solid #f5f5f5;padding:20rpx 0}
.memberitem image{display: block;height:100rpx;width:100rpx;margin-right:20rpx;}
.memberitem .t1{color:#333;font-weight:bold}
.memberitem .radio {flex-shrink: 0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right: 30rpx}
.memberitem .radio .radio-img {width: 100%;height: 100%}

.checkMem{ display: inline-block; }
.checkMem p{ height: 30px; width: 100%; display: inline-block; }
.placeholder{  font-size: 26rpx;line-height: 80rpx;}
.selected-item span{ font-size: 26rpx !important;}
.orderinfo{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;overflow:hidden}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;flex-shrink:0}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}
</style>
