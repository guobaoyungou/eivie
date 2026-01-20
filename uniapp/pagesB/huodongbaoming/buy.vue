<template>
	<view class="container">
		<block v-if="isload">
			<form @submit="topay">
        <block>
          <view class="address-add">
          	<view class="linkitem">
          		<text class="f1">联 系 人：</text>
          		<input type="text" class="input" :value="linkman" placeholder="请输入您的姓名" @input="inputLinkman" placeholder-style="color:#626262;font-size:28rpx;"/>
          	</view>
          	<view class="linkitem">
          		<text class="f1">联系电话：</text>
          		<input type="text" class="input" :value="tel" placeholder="请输入您的手机号" @input="inputTel" placeholder-style="color:#626262;font-size:28rpx;"/>
          	</view>
          </view>
        </block>
				<view v-for="(buydata, index) in allbuydata" :key="index" class="buydata">					
					<view class="bcontent">
						<view class="btitle" v-if="protype ==0">
							服务信息
						</view>
						<view class="product" v-if="protype ==0">
							<view v-for="(item, index2) in buydata.prodata" :key="index2" class="item flex">
								<view class="img" @tap="goto" :data-url="'product?id=' + item.product.id">
									<image :src="item.product.pic"></image>
								</view>
								<view class="info flex1">
									<view class="f1">{{item.product.name}}</view>
									<view class="f2">{{item.guige.name}}</view>
									<view class="f3"><text style="font-weight:bold;">￥{{item.guige.sell_price}}</text><text
											style="padding-left:20rpx"> × {{item.num}}</text></view>
								</view>
							</view>
						</view>
						<view class="price" v-if="protype ==1">
							<view class="f1">购买数量</view>
							<view class="f2">{{num}}{{danwei}}</view>
						</view>	
					</view>
					<view class="bcontent2">
						<view class="price" v-if="buydata.leveldk_money>0">
							<text class="f1">{{t('会员')}}折扣({{userinfo.discount}}折)</text>
							<text class="f2">-¥{{buydata.leveldk_money}}</text>
						</view>
						<view class="price" v-if="yyset.iscoupon==1">
							<view class="f1">{{t('优惠券')}}</view>
							<view v-if="buydata.couponCount > 0" class="f2" @tap="showCouponList" :data-bid="buydata.bid">
								<text
									style="color:#fff;padding:4rpx 16rpx;font-weight:normal;border-radius:8rpx;font-size:24rpx"
									:style="{background:t('color1')}">{{buydata.couponrid!=0?buydata.couponList[buydata.couponkey].couponname:buydata.couponCount+'张可用'}}</text><text
									class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</view>
							<text class="f2" v-else style="color:#999">无可用{{t('优惠券')}}</text>
						</view>
						<view class="price">
							<text class="f1">报名价格</text>
							<text class="f2">¥{{buydata.product_price}}</text>
						</view>			
						<view class="price">
							<text class="f1">所需{{t('积分')}}</text>
							<text class="f2">{{buydata.score_price}}</text>
						</view>			
						<view style="display:none">{{test}}</view>
						<view class="form-item" v-for="(item,idx) in buydata.formdata" :key="item.id">
							<view class="label">{{item.val1}}<text v-if="item.val3==1" style="color:red"> *</text></view>
							<block v-if="item.key=='input'">
								<input type="text" :name="'form'+buydata.bid+'_'+idx" class="input" :placeholder="item.val2" placeholder-style="font-size:28rpx"/>
							</block>
							<block v-if="item.key=='textarea'">
								<textarea :name="'form'+buydata.bid+'_'+idx" class='textarea' :placeholder="item.val2" placeholder-style="font-size:28rpx"/>
							</block>
							<block v-if="item.key=='radio'">
								<radio-group class="radio-group" :name="'form'+buydata.bid+'_'+idx">
									<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
										<radio class="radio" :value="item1"/>{{item1}}
									</label>
								</radio-group>
							</block>
							<block v-if="item.key=='checkbox'">
								<checkbox-group :name="'form'+buydata.bid+'_'+idx" class="checkbox-group">
									<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
										<checkbox class="checkbox" :value="item1"/>{{item1}}
									</label>
								</checkbox-group>
							</block>
							<block v-if="item.key=='selector'">
								<picker class="picker" mode="selector" :name="'form'+buydata.bid+'_'+idx" value="" :range="item.val2" @change="editorBindPickerChange" :data-bid="buydata.bid" :data-idx="idx">
									<view v-if="buydata.editorFormdata[idx] || buydata.editorFormdata[idx]===0"> {{item.val2[buydata.editorFormdata[idx]]}}</view>
									<view v-else>请选择</view>
								</picker>
								<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</block>
							<block v-if="item.key=='time'">
								<picker class="picker" mode="time" :name="'form'+buydata.bid+'_'+idx" value="" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-bid="buydata.bid" :data-idx="idx">
									<view v-if="buydata.editorFormdata[idx]">{{buydata.editorFormdata[idx]}}</view>
									<view v-else>请选择</view>
								</picker>
								<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</block>
							<block v-if="item.key=='date'">
								<picker class="picker" mode="date" :name="'form'+buydata.bid+'_'+idx" value="" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-bid="buydata.bid" :data-idx="idx">
									<view v-if="buydata.editorFormdata[idx]">{{buydata.editorFormdata[idx]}}</view>
									<view v-else>请选择</view>
								</picker>
								<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</block>
							<block v-if="item.key=='upload'">
								<input type="text" style="display:none" :name="'form'+buydata.bid+'_'+idx" :value="buydata.editorFormdata[idx]"/>
								<view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
									<view class="form-imgbox" v-if="buydata.editorFormdata[idx]">
										<view class="layui-imgbox-close" style="z-index: 2;" @tap="removeimg" :data-bid="buydata.bid" :data-idx="idx"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
										<view class="form-imgbox-img"><image class="image" :src="buydata.editorFormdata[idx]" @click="previewImage" :data-url="buydata.editorFormdata[idx]" mode="aspectFit"/></view>
									</view>
									<view v-else class="form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 50rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="editorChooseImage" :data-bid="buydata.bid" :data-idx="idx"></view>
								</view>
							</block>
						</view>
					</view>				
				</view>
				<view style="width: 100%; height:182rpx;"></view>
				<view class="footer flex">
					<view class="text1 flex1">总计：
						<text class="txt" style="font-weight:bold" :style="{color:t('color1')}" v-if="alltotalprice*1 > 0">￥{{alltotalprice}} + {{totalscore}}{{t('积分')}}</text>
						<text class="txt" :style="{color:t('color1')}" v-else>{{totalscore}}{{t('积分')}}</text>
					<!--	<text style="font-weight:bold;font-size:36rpx">￥{{alltotalprice}}</text>-->
					</view>
					<button v-if="issubmit" class="op" style="background: #999;" >
						确认提交</button>
					<button v-else class="op" form-type="submit" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">
							确认提交</button>
				</view>
			</form>

			<view v-if="couponvisible" class="popup__container">
				<view class="popup__overlay" @tap.stop="handleClickMask"></view>
				<view class="popup__modal coupon-modal">
					<view class="popup__title">
						<text class="popup__title-text">请选择{{t('优惠券')}}</text>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
							@tap.stop="handleClickMask" />
					</view>
					<view class="popup__content">
						<couponlist :couponlist="allbuydata[bid].couponList" :choosecoupon="true"
							:selectedrid="allbuydata[bid].couponrid" :bid="bid" @chooseCoupon="chooseCoupon">
						</couponlist>
					</view>
				</view>
			</view>
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
				sindex:'',
				prodata:'',
				issubmit:false,
				isdate:false,
				datelist:[],
				index:0,
				day: -1,
				num:0,
				proid:'',
				yyset:'',
				protype:0,
				carlocat_name:'',
				carlocat_address:'',
				carlocat_latitude:'',
				carlocat_longitude:'',
				carlocat_stop:'',
				carinfor:'',
				
				latitude:'',
				longitude:'',
				
				btntype:1,
				menuindex:-1,
				buydata:{},
				ggname:'',
				totalscore: 0,
			};
		},

		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.prodata = opt.prodata;
			this.sindex = opt.sindex;
			this.linkman = opt.linkman;
			this.tel = opt.tel;
			this.getdata();
			console.log(this.yydate);
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		methods: {
			getdata: function() {
				var that = this;
				that.loading = true;
				app.get('ApiHuodongBaoming/buy', {
					prodata: that.opt.prodata,
				}, function(res) {
          that.loading = false;
				  if(res.status == 1){
					that.address = res.address;
					if(!that.linkman ){
						that.linkman = res.linkman;
					}
					if(!that.tel ){
						that.tel = res.tel;
					}
					that.userinfo = res.userinfo;
					that.yyset = res.yyset;
					that.allbuydata = res.allbuydata;
					if(res.protype){
					  that.protype = res.protype;
					}
					if(res.carinfor){
					  that.carinfor = res.carinfor
					}

					that.calculatePrice();
					that.loaded();
				  }else{
            app.alert(res.msg, function () {
              uni.navigateBack();
            });
            return;
				  }
				  app.getLocation(function(res) {
				  	var latitude = res.latitude;
				  	var longitude = res.longitude;
				  	that.latitude = latitude;
				  	that.longitude = longitude;
				  });
				});
			},
			chooseCoupon: function(e) {
				var allbuydata = this.allbuydata;
				var bid = e.bid;
				var couponrid = e.rid;
				var couponkey = e.key;
				if (couponrid == allbuydata[bid].couponrid) {
					allbuydata[bid].couponkey = 0;
					allbuydata[bid].couponrid = 0;
					allbuydata[bid].coupontype = 1;
					allbuydata[bid].coupon_money = 0;
					this.allbuydata = allbuydata;
					this.couponvisible = false;
				} else {
					var couponList = allbuydata[bid].couponList;
					var coupon_money = couponList[couponkey]['money'];
					var coupontype = couponList[couponkey]['type'];
					if(coupontype == 10){
						coupon_money = allbuydata[bid].sell_price * (100 - couponList[couponkey]['discount']) * 0.01;
					}
					allbuydata[bid].couponkey = couponkey;
					allbuydata[bid].couponrid = couponrid;
					allbuydata[bid].coupontype = coupontype;
					allbuydata[bid].coupon_money = coupon_money;
					this.allbuydata = allbuydata;
					this.couponvisible = false;
				}
				this.calculatePrice();
			},
			showCouponList: function(e) {
				this.couponvisible = true;
				this.bid = e.currentTarget.dataset.bid;
			},
			handleClickMask: function() {
				this.couponvisible = false;
			},
			//计算价格
			calculatePrice: function() {
				var that = this;
				var address = that.address;
				var allbuydata = that.allbuydata;
				var alltotalprice = 0;
				var needaddress = 0;
				var totalscore = 0;
				for (var k in allbuydata) {
					var product_price = parseFloat(allbuydata[k].sell_price);
					var coupon_money = parseFloat(allbuydata[k].coupon_money); //-优惠券抵扣 
				  if(allbuydata[k].coupontype==3) coupon_money =  product_price
					var totalprice = product_price - coupon_money ;
					if (totalprice < 0) totalprice = 0; //优惠券不抵扣运费					
					alltotalprice += totalprice;
					totalscore = allbuydata[k].score_price;
				}
				
				that.needaddress = needaddress;
				var oldalltotalprice = alltotalprice;
				if (alltotalprice < 0) alltotalprice = 0;
				alltotalprice = alltotalprice.toFixed(2);
				that.alltotalprice = alltotalprice;
				that.totalscore = totalscore
				that.allbuydata = allbuydata;
			},
			//提交并支付
			topay: function(e) {
				var that = this;
				var addressid = this.address?this.address.id:0;
				var linkman = this.linkman?this.linkman:'';
				var tel = this.tel?this.tel:'';
				var frompage = that.opt.frompage ? that.opt.frompage : '';
				var allbuydata = that.allbuydata;
				if(!that.protype){
				  if(that.sindex==2 && addressid == undefined) {
					app.error('请选择地址');
					return;
				  }
				  if(that.sindex==1 && (!linkman || !tel)) {
					app.error('请填写联系人及联系电话');
					return;
				  }
				}
				var buydata = [];
				for (var i in allbuydata) {
					var formdata_fields = allbuydata[i].formdata;
					var formdata = e.detail.value;
					var newformdata = {};
					for (var j = 0; j < formdata_fields.length;j++){
						var thisfield = 'form'+allbuydata[i].bid + '_' + j;
						console.log(allbuydata[i]);
						if (formdata_fields[j].val3 == 1 && (formdata[thisfield] === '' || formdata[thisfield] === undefined || formdata[thisfield].length==0)){
								app.alert(formdata_fields[j].val1+' 必填');return;
						}
						if (formdata_fields[j].key == 'selector') {
								formdata[thisfield] = formdata_fields[j].val2[formdata[thisfield]]
						}
						newformdata['form'+j] = formdata[thisfield];
					}

					var prodata = allbuydata[i].prodatastr;
					if(that.protype){
						var prodata = that.proid+','+that.ggid+','+that.num;
					}
					buydata.push({
						bid: allbuydata[i].bid,
						prodata: allbuydata[i].prodatastr,
						couponrid: allbuydata[i].couponrid,
						formdata:newformdata
					});
				}


				app.showLoading('提交中');
				app.post('ApiHuodongBaoming/createOrder', {
					frompage: frompage,
					buydata: buydata,
					addressid: addressid,
					linkman: linkman,
					tel: tel,
					longitude: that.longitude,
					latitude: that.latitude

				}, function(res) {
					app.showLoading(false);
	
					//app.error('订单编号：' +res.payorderid);
					if(res.status==1 && res.payorderid){
							that.	issubmit = true	
							app.goto('/pagesExt/pay/pay?id=' + res.payorderid,'redirectTo');
					}else if(res.status==1 && !res.payorderid){
							//成功提示
							app.goto('/pagesB/huodongbaoming/orderlist');
					}else	if (res.status == 0) {
						//that.showsuccess(res.data.msg);
						app.error(res.msg);
						return;
					}
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
					that.editorFormdata = editorFormdata
					that.allbuydata[bid].editorFormdata = editorFormdata
					that.test = Math.random();
				})
			},
			removeimg:function(e){
				var that = this;
				var bid = e.currentTarget.dataset.bid;
				var idx = e.currentTarget.dataset.idx;
				var pics = that.editorFormdata
				pics.splice(idx,1);
				that.editorFormdata = pics;
				that.allbuydata[bid].editorFormdata = that.editorFormdata;
			},
			editorBindPickerChange:function(e){
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
			inputLinkman: function (e) {
				this.linkman = e.detail.value
			},
			inputTel: function (e) {
				this.tel = e.detail.value
			}
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
.bcontent {width: 100%;padding: 0 20rpx;background: #fff;border-radius: 20rpx;}
.bcontent2 {width: 100%;padding: 0 20rpx; margin-top: 30rpx;background: #fff;border-radius: 20rpx;}
.product {width: 100%;border-bottom: 1px solid #f4f4f4}
.product .item {width: 100%;padding: 20rpx 0;background: #fff;border-bottom: 1px #ededed dashed;}
.product .item:last-child {border: none}
.product .info {padding-left: 20rpx;}
.product .info .f1 {color: #222222;font-weight: bold;font-size: 26rpx;line-height: 36rpx;margin-bottom: 10rpx;display: -webkit-box;-webkit-box-orient: vertical;-webkit-line-clamp: 2;overflow: hidden;}
.product .info .f2 {color: #999999;font-size: 24rpx}
.product .info .f3 {color: #FF4C4C;font-size: 28rpx;display: flex;align-items: center;margin-top: 10rpx}
.product image {width: 140rpx;height: 140rpx}
.price {width: 100%;padding: 20rpx 0;background: #fff;display: flex;align-items: center}
.price .f1 {color: #333}
.price .f2 {color: #111;font-weight: bold;text-align: right;flex: 1}
.price .f3 {width: 24rpx;height: 24rpx;}
.scoredk {width: 94%;margin: 0 3%;margin-bottom: 20rpx;border-radius: 20rpx;padding: 24rpx 20rpx;background: #fff;display: flex;align-items: center}
.scoredk .f1 {color: #333333}
.scoredk .f2 {color: #999999;text-align: right;flex: 1}
.footer {width: 100%;background: #fff;margin-top: 5px;position: fixed;left: 0px;bottom: 0px;padding: 0 20rpx;display: flex;align-items: center;z-index: 8}
.footer .text1 {height: 110rpx;line-height: 110rpx;color: #2a2a2a;font-size: 30rpx;}
.footer .text1 text {color: #e94745;font-size: 32rpx;}
.footer .op {width: 200rpx;height: 80rpx;line-height: 80rpx;color: #fff;text-align: center;font-size: 30rpx;border-radius: 44rpx}
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
.form-imgbox-img>.image{width:100%;height:100%}
.form-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.form-uploadbtn{position:relative;height:180rpx;width:180rpx;margin-right: 16rpx;margin-bottom:10rpx;}
.placeholder{  font-size: 26rpx;line-height: 80rpx;}
.tobuy{flex:1;height: 72rpx; line-height: 72rpx; color: #fff; border-radius: 0px; border: none;font-size:28rpx;font-weight:bold;width:90%;margin:20rpx 5%;border-radius:36rpx;}
</style>
