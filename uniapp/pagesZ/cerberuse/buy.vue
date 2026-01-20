<template>
	<view class="container">
		<block v-if="isload">
			<form @submit="topay">
				<view class="address-add">
					<view class="linkitem">
						<text class="f1">联 系 人：</text>
						<input type="text" class="input" :value="linkman" placeholder="请输入您的姓名" @input="inputLinkman" placeholder-style="color:#757575;font-size:28rpx;"/>
					</view>
					<view class="linkitem">
						<text class="f1">联系电话：</text>
						<input type="text" class="input" :value="tel" placeholder="请输入您的手机号" @input="inputTel" placeholder-style="color:#757575;font-size:28rpx;"/>
					</view>
				</view>
				<view class="buydata">			
					<view class="bcontent">
						<view class="btitle">产品信息</view>
						<view class="product">
							<view class="item flex">
								<view class="img" @tap="goto" :data-url="'product?id=' + product.id">
									<image :src="product.pic"></image>
								</view>
								<view class="info flex1">
									<view class="f1">{{product.title}}</view>
									<view class="f3"><text style="font-weight:bold;">￥{{product.price}}</text></view>
								</view>
							</view>
						</view>
						
						<view class="price">
							<text class="f1">价格</text>
							<text class="f2">¥{{product.price}}/小时 </text>
						</view>
					</view>
					<view class="bcontent2">
						<view class="price" v-if="userinfo.leveldk_money>0">
							<text class="f1">{{t('会员')}}折扣({{userinfo.discount}}折)</text>
							<text class="f2">-¥{{userinfo.leveldk_money}}</text>
						</view>
						<view class="price" >
							<view class="f1">{{t('优惠券')}}</view>
							<view v-if="couponCount > 0" class="f2" @tap="showCouponList">
								<text style="color:#fff;padding:4rpx 16rpx;font-weight:normal;border-radius:8rpx;font-size:24rpx"
									:style="{background:t('color1')}">{{couponrid!=0?couponList[couponkey].couponname:couponCount+'张可用'}}</text><text
									class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</view>
							<text class="f2" v-else style="color:#999">无可用{{t('优惠券')}}</text>
						</view>
						
						<view class="price" v-if="opt.type==1">
							<view class="f1" style="flex: 1;">预约开始时间</view>
							<view class="choose f2">
								<view class="choosetime"  @tap="toStartYuyue"><text v-if="start_time !=''">{{start_time}}</text><text v-else>请选预约开始时间</text></view>
							</view>
						</view>
						<view class="price" v-if="opt.type==1">
							<view class="f1" style="flex: 1;">预约结束时间</view>
							<view class="choose f2">
								<view class="choosetime"  ><text v-if="end_time">{{end_time}}</text></view>
							</view>
						</view>
						
						<view class="buynum flex flex-y-center">
							<view class="flex1">购买时长(小时)：</view>
							<view class="addnum">
								<view class="minus" @tap="gwcminus"><image class="img" :src="pre_url+'/static/img/cart-minus.png'"/></view>
								<input class="input" type="number" :value="gwcnum" @input="gwcinput"></input>
								<view class="plus" @tap="gwcplus"><image class="img" :src="pre_url+'/static/img/cart-plus.png'"/></view>
							</view>
						</view>
						<view class="price" v-if="opt.type==2">
							<view class="f1" style="flex: 1;">结束时间</view>
							<view class="choose f2">
								<view class="choosetime"  ><text v-if="end_time">{{end_time}}</text></view>
							</view>
						</view>
					
					</view>
				</view>


				<view style="width: 100%; height:182rpx;"></view>
				<view class="footer flex">
					<view class="text1 flex1">总计：
						<text style="font-weight:bold;font-size:36rpx">￥{{totalprice}}</text>
					</view>
					<button v-if="issubmit" class="op" style="background: #999;">确认提交</button>
					<button v-else class="op" form-type="submit" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">确认提交</button>
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
						<couponlist :couponlist="couponList" :choosecoupon="true"
							:selectedrid="couponrid" @chooseCoupon="chooseCoupon">
						</couponlist>
					</view>
				</view>
			</view>
		<view v-if="yuyuevisible" class="picker-time">
			<view @tap="yuyuevisible=false" class="picker-hide"></view>
			<view class="picker-module">
				<picker-view :value="value" @change="bindChange" class="picker-view">
				    <picker-view-column>
				        <view class="flex-xy-center" v-for="(item,index) in years" :key="index">{{item}}</view>
				    </picker-view-column>
				    <picker-view-column>
				        <view class="flex-xy-center" v-for="(item,index) in hour" :key="index">{{item}}</view>
				    </picker-view-column>
				    <picker-view-column>
				        <view class="flex-xy-center" v-for="(item,index) in min" :key="index">{{item}}</view>
				   </picker-view-column>
				</picker-view>	
				<view class="picker-opt flex-xy-center">
					<view class="picker-btn" @tap="yuyuevisible=false" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">取消</view>
					<view class="picker-btn" @tap="getTime" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">确定</view>
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
				totalprice: '0.00',
				couponvisible: false,
				linkman: '',
				tel: '',
				userinfo: {},
				editorFormdata:[],
				issubmit:false,
				dateIndex:0,
				proid:'',
				ykset:{},
				couponrid:'',
				couponkey:-1,
				coupontype:1,
				coupon_money:0,
				couponList:[],
				couponCount:0,
				product:{},
				workerinfo:{},
				formdata:[],
				gwcnum:1,
				leveldk_money: 0,
				formdata_money:0,
				formdata_label:[],
				priceAry:[],
				years:[],
				hour:[],
				min:[],
				yuyuevisible:false,
				value: [0, 0, 0],//时间组件选中的值
				yytime:[],//接口返回的数据
				open_type:0,//打开类型 1：打开开始时间 2：打开结束时间
				start_time:'',//选中的开始时间
				end_time:'',//选中的结束时间
				difftime:0,//天数差
				difftext:'',//天数小时拼接
				start_value:[],//开始时间的选中
				end_value:[]//结束时间的选中
			};
		},

		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			if(this.opt && this.opt.dateIndex) this.dateIndex = this.opt.dateIndex;
			if(this.opt && this.opt.proid) this.proid = this.opt.proid;
			this.getdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		methods: {
			getTime(){
				var time = this.years[this.value[0]] + ' ' + this.hour[this.value[1]] + ':' + this.min[this.value[2]]
				//开始时间选择时
				if(this.open_type==1){
					this.start_time = time;	
					
					//保存 开始时间的选择的位置
					this.start_value = this.value;
					if(this.years[this.value[0]]){
						this.value = [this.value[0],this.value[1]+1,this.value[2]];
					}else{
						this.value = [this.value[0],this.value[1],this.value[2]];
					}	
					
					console.log(this.start_value,'starut');
				}
				// //结束时间选择时
				// if(this.open_type==2){
				// 	this.end_time = time;
				// 	var difftime = this.getDateDiff(this.start_time,this.end_time,'hour');
				// 	if(difftime < 0){
				// 		app.error('结束时间必须大于开始时间');
				// 		return;
				// 	}
				// 	this.end_value = this.value;
				// }
				// this.difftime =  difftime;
				// this.computetime();
				this.getAfterTime();
				this.yuyuevisible = false;
			},
			// computetime(){
			// 	//如果天数差大于0时，拼接天数 和计算 是天数 大于4天为一天
			// 	var difftime = this.difftime;
			// 	if(difftime){
			// 		var diffarr = difftime.toString().split(".");
			// 		console.log(diffarr,'diffarr');
			// 		var hour = diffarr[0]+'.'+diffarr[1];
			// 		this.gwcnum = parseInt(hour)
			// 		console.log(this.gwcnum,'this.gwcnum');
			// 		this.difftext =this.gwcnum+'小时';
			// 		this.calculatePrice();
			// 	}
			// },
			bindChange(e){
				 const val = e.detail.value;
					 console.log(val);
				 var dateindex = val[0];
				dateindex = dateindex >=1?1:dateindex;

				 if(dateindex==0 && val[1] ==0){
					this.min = this.yytime[0]['min1'];
					this.hour = this.yytime[0]['hour'];
				 }else{
					 this.min = this.yytime[dateindex]['min']; 
					 this.hour = this.yytime[dateindex]['hour'];
				 }
				 this.value = val;
			},
			toStartYuyue(){
				this.open_type =1;
				this.value = this.start_value.length>0?this.start_value:this.value;
				if(this.value[0] ==0){//是年的第一条数据时 使用0
					this.min = this.yytime[0]['min1'];
					this.hour = this.yytime[0]['hour'];
				}else{
					this.min = this.yytime[1]['min'];
					this.hour = this.yytime[1]['hour'];
				}
				this.yuyuevisible = true;
			},
			toEndYuyue(){
				if(this.start_time ==''){
					app.error('请先选择开始时间');
					return;
				}
				this.open_type =2;
				this.value = this.end_value.length>0?this.end_value:this.value;
				if(this.value[0] ==0){//是年的第一条数据时 使用0
					this.min = this.yytime[0]['min1'];
					this.hour = this.yytime[0]['hour'];
				}else{
					this.min = this.yytime[1]['min'];
					this.hour = this.yytime[1]['hour'];
				}
				this.yuyuevisible = true;
			},
			getdata: function() {
				var that = this;
				app.get('ApiCerberuse/buy', {proid: that.proid,dateIndex:that.dateIndex}, function(res) {
					if(!that.linkman ){
						that.linkman = res.linkman;
					}
					if(!that.tel ){
						that.tel = res.tel;
					}
					that.userinfo = res.userinfo;
					that.ykset = res.ykset;
					that.product = res.product;
					that.workerinfo = res.workerinfo;
					that.couponList = res.couponList;
					that.couponCount = res.couponCount;
					that.formdata = res.formdata;
					that.yytime = res.yytime;
					that.years = res.years;
					if(that.yytime.length > 0 && that.opt.type ==1){
						that.hour = that.yytime[0]['hour']
						that.min= that.yytime[0]['min1']
						//计算默认的租车天数
						that.open_type=1;
						that.getTime();
						// that.open_type=2;
						// that.getTime();
						that.getAfterTime();
					}else{
						var time = new Date();
						var y = time.getFullYear();
						var m = time.getMonth()+1;
						var d = time.getDate();
						var h = time.getHours();
						var mm = time.getMinutes();
						that.start_time =  y+'-'+that.add0(m)+'-'+that.add0(d)+' '+that.add0(h)+':'+that.add0(mm);	
						that.getAfterTime();
					}
					var leveldk_money = 0;
					var sell_price = res.product.sell_price;
					var userinfo = res.userinfo;
					if ( userinfo.discount > 0 && userinfo.discount < 10) {
						leveldk_money = sell_price * (1 - userinfo.discount * 0.1);
						leveldk_money = leveldk_money.toFixed(2);
					}
					that.leveldk_money = leveldk_money;
					that.calculatePrice();
					that.loaded();
				});
			},
			chooseCoupon: function(e) {
				var couponrid = e.rid;
				var couponkey = e.key;
				if (couponrid == this.couponrid) {
					this.couponkey = -1;
					this.couponrid = '';
					this.coupontype = 1;
					this.coupon_money = 0;
					this.couponvisible = false;
				} else {
					var couponList = this.couponList;
					var coupon_money = couponList[couponkey]['money'];
					var coupontype = couponList[couponkey]['type'];
					if(coupontype == 10){
						coupon_money = this.product.sell_price * (100 - couponList[couponkey]['discount']) * 0.01;
					}
					this.couponkey = couponkey;
					this.couponrid = couponrid;
					this.coupontype = coupontype;
					this.coupon_money = coupon_money;
					this.couponvisible = false;
				}
				this.calculatePrice();
			},
			showCouponList: function(e) {
				this.couponvisible = true;
			},
			handleClickMask: function() {
				this.couponvisible = false;
			},
			//计算价格
			calculatePrice: function() {
				var that = this;
				var product_price = parseFloat(that.product.price*this.gwcnum) ;
				var coupon_money = parseFloat(that.coupon_money); //-优惠券抵扣 
				var leveldk_money = parseFloat(that.leveldk_money); //-会员折扣
				var formdata_money =  parseFloat(that.formdata_money); //表单价格
				if(that.coupontype==3) coupon_money = product_price
				var totalprice = product_price - coupon_money - leveldk_money;
				if(formdata_money > 0){
					totalprice =parseFloat( totalprice + formdata_money);
				}
				if (totalprice < 0) totalprice = 0;
				totalprice = totalprice.toFixed(2);
				that.totalprice = totalprice;
				//计算日期
				
				
			},
			
			//减
			gwcminus: function (e) {
				this.gwcnum =this.gwcnum <= 1?this.gwcnum: this.gwcnum - 1;
				this.calculatePrice();
				this.getAfterTime();
			},
			//加
			gwcplus: function (e) {
				this.gwcnum = this.gwcnum + 1;
				this.calculatePrice();
				this.getAfterTime();
			},
			//输入
			gwcinput: function (e) {
				var gwcnum = parseInt(e.detail.value);
				if (gwcnum < 1) return 1;
				this.gwcnum = gwcnum;
				if(this.gwcnum >=1){
					this.calculatePrice();
				}	
				this.getAfterTime();
			},
			getAfterTime(){
				var that =this;
				var sTime =new Date(that.start_time); //开始时间
				var addtime = that.gwcnum * 3600*1000;
				var etime = parseInt (sTime.getTime() + parseInt(addtime));
				var time = new Date(etime);
				var y = time.getFullYear();
				var m = time.getMonth()+1;
				var d = time.getDate();
				var h = time.getHours();
				var mm = time.getMinutes();
				this.end_time =  y+'-'+this.add0(m)+'-'+this.add0(d)+' '+this.add0(h)+':'+this.add0(mm);				
			},
			add0(m){return m<10?'0'+m:m },
			//提交并支付
			topay: function(e) {
				var that = this;
				var linkman = this.linkman;
				var tel = this.tel;
				var proid = this.proid;
				var couponrid = this.couponrid;
				var dateIndex = this.dateIndex;
				if(!linkman || !tel) {
					app.error('请填写联系人及联系电话');
					return;
				}
				var formdata_fields = that.formdata;
				var formdata = e.detail.value;
				var formdata_money = that.formdata_money;
				var formdata_title = that.formdata_title;
				var start_time = that.start_time;
				var end_time = that.end_time;
				
				 var newformdata = {};
				// for (var j = 0; j < formdata_fields.length;j++){
				// 	var thisfield = 'form_' + j;
				// 	if (formdata_fields[j].val3 == 1 && (formdata[thisfield] === '' || formdata[thisfield] === undefined || formdata[thisfield].length==0)){
				// 			app.alert(formdata_fields[j].val1+' 必填');return;
				// 	}
				// 	if (formdata_fields[j].key == 'selector') {
				// 		console.log(formdata[thisfield],'---');
				// 			formdata[thisfield] = formdata_fields[j].valdata[formdata[thisfield]]
				// 	}
				// 	newformdata['form'+j] = formdata[thisfield];
				// }
				app.showLoading('提交中');
				app.post('ApiCerberuse/createOrder', {linkman: linkman,tel: tel,formdata:newformdata,proid:proid,couponrid:couponrid,num:that.gwcnum,start_time:start_time,end_time:end_time}, function(res) {
					app.showLoading(false);
					if(res.status==1 && res.payorderid){
							that.issubmit = true	
							app.goto('/pagesExt/pay/pay?id=' + res.payorderid);
					}else{
						app.error(res.msg);
						return;
					}
				});
			},
			editorChooseImage: function (e) {
				var that = this;
				var idx = e.currentTarget.dataset.idx;
				var editorFormdata = that.editorFormdata;
				if(!editorFormdata) editorFormdata = [];
				app.chooseImage(function(data){
					editorFormdata[idx] = data[0];
					that.editorFormdata = editorFormdata
					that.test = Math.random();
				})
			},
			editorBindPickerChange:function(e){
				var that = this;
				var idx = e.currentTarget.dataset.idx;
				var val = e.detail.value;
				var editorFormdata = that.editorFormdata;
				if(!editorFormdata) editorFormdata = [];
				editorFormdata[idx] = val;
				that.editorFormdata = editorFormdata;
				that.test = Math.random();
			},
			selectorPickerChange:function(e){
				var that = this;
				var idx = e.currentTarget.dataset.idx;
				var val = e.detail.value;
				var editorFormdata = that.editorFormdata;
				if(!editorFormdata) editorFormdata = [];
				editorFormdata[idx] = val;
				that.editorFormdata = editorFormdata;
				that.test = Math.random();
				that.formdata_money = 0;
				var valdata = that.formdata[idx].valdata;
				
				that.priceAry[idx] = valdata[val];
				let ary = [];
				for(let i=0;i<that.priceAry.length;i++){
					if(that.priceAry[i]){
						ary.push(parseFloat(that.priceAry[i].value))
					}
				}
				var totalPrice = that.getSum(ary);
				that.formdata_money = totalPrice? parseFloat(totalPrice):0;
				that.calculatePrice();
			},
			getSum(arr) {
			  var s = 0;
			  for (var i=arr.length-1; i>=0; i--) {
				if(!isNaN(parseFloat(arr[i]))){
					 s += arr[i];
				}   
			  }
			  return s;
			},
			inputLinkman: function (e) {
				this.linkman = e.detail.value
			},
			inputTel: function (e) {
				this.tel = e.detail.value
			},
			getDateDiff:function(startTime, endTime, diffType) {
				if(endTime ==''){
					endTime = startTime;
				}
			    //将xxxx-xx-xx的时间格式，转换为 xxxx/xx/xx的格式 
			    startTime = startTime.replace(/\-/g, "/");
			    endTime = endTime.replace(/\-/g, "/");
			    //将计算间隔类性字符转换为小写
			    diffType = diffType.toLowerCase();
			    var sTime =new Date(startTime); //开始时间
			    var eTime =new Date(endTime); //结束时间
				console.log(eTime.getTime());
			    //作为除数的数字
			    var timeType =1;
			    switch (diffType) {
			        case"second":
			            timeType =1000;
			        break;
			        case"minute":
			            timeType =1000*60;
			        break;
			        case"hour":
			            timeType =1000*3600;
			        break;
			        case"day":
			            timeType =1000*3600*24;
			        break;
			        default:
			        break;
			    }
			    return parseFloat((eTime.getTime() - sTime.getTime()) / parseInt(timeType)).toFixed(2);
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
/* 购买数量*/
.price .addnum {font-size: 30rpx;color: #666;width:auto;display:flex;align-items:center}
.price .addnum .plus {width:65rpx;height:48rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.price .addnum .minus {width:65rpx;height:48rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.price .addnum .img{width:24rpx;height:24rpx}
.price .addnum .input{flex:1;width:50rpx;border:0;text-align:center;color:#2B2B2B;font-size:28rpx;margin: 0 15rpx;}

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

.price .choose {color: #666;width:auto;display:flex;align-items:center}
.price .choose .choosetime {width: 100%; height:48rpx;display:flex;align-items:center;justify-content:flex-end; font-size: 24rpx;font-weight: normal;}
.price .choose .difftext{flex:1;border:0;text-align:center;color:#2B2B2B;font-size:24rpx;margin: 0 15rpx;width: 95rpx;padding: 0;margin: 0;}
.picker-time{
		position: fixed;
		height: 100%;
		width: 100%;
		top: 0;
		left: 0;
		background: rgba(0, 0, 0, 0.7);
		z-index: 1000;
	}
	.picker-hide{
		position: absolute;
		height: 100%;
		width: 100%;
		top: 0;
		left: 0;
	}
	.picker-module{
		position:absolute;
		bottom: 0;
		padding: 50rpx 0;
		background-color: #fff;
	}
	.picker-view {
		width: 750rpx;
		height: 600rpx;
	}
	.picker-opt{
		position: relative;
		padding: 30rpx 0 0 0;
	}
	.picker-btn {
		width: 200rpx;
		height: 80rpx;
		line-height: 80rpx;
		color: #fff;
		text-align: center;
		font-size: 30rpx;
		border-radius: 44rpx;
		margin: 0 50rpx;
	}
/*时间范围*/
.datetab{ display: flex; border:1px solid red; width: 200rpx; text-align: center;}
.order-tab{ }
.order-tab2{display:flex;width:auto;min-width:100%}
.order-tab2 .item{width:auto;font-size:28rpx;font-weight:bold;text-align: center; color:#999999;overflow: hidden;flex-shrink:0;flex-grow: 1; display: flex; flex-direction: column; justify-content: center; align-items: center;}
.order-tab2 .item .datetext{ line-height: 60rpx; height:60rpx;}
.order-tab2 .item .datetext2{ line-height: 60rpx; height:60rpx;font-size: 22rpx;}
.order-tab2 .on{color:#222222;}
.order-tab2 .after{display:none;margin-left:-10rpx;bottom:5rpx;height:6rpx;border-radius:1.5px;width:70rpx}
.order-tab2 .on .after{display:block}
.daydate{ padding:20rpx; flex-wrap: wrap; overflow-y: scroll; height:400rpx; }
.daydate .date{ width:20%;text-align: center;line-height: 60rpx;height: 60rpx; margin-top: 30rpx;}
.daydate .on{ background:red; color:#fff;}
.daydate .hui{ border:1px solid #f0f0f0; background:#f0f0f0;border-radius: 5rpx;}
.tobuy{flex:1;height: 72rpx; line-height: 72rpx; color: #fff; border-radius: 0px; border: none;font-size:28rpx;font-weight:bold;width:90%;margin:20rpx 5%;border-radius:36rpx;}

.buynum{ width: 100%; position: relative; padding:10px 0px 10px 0px; }
.addnum {font-size: 30rpx;color: #666;width:auto;display:flex;align-items:center}
.addnum .plus {width:65rpx;height:48rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.addnum .minus {width:65rpx;height:48rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.addnum .img{width:24rpx;height:24rpx}
.addnum .input{flex:1;width:50rpx;border:0;text-align:center;color:#2B2B2B;font-size:28rpx;margin: 0 15rpx;}
</style>
