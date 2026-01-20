<template>
	<view class="container">
		<block v-if="isload">
			<form @submit="topay">
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
				<view class="buydata">			
					<view class="bcontent">
						<view class="btitle">约车信息</view>
						<view class="product">
							<view class="item flex">
								<view class="img" @tap="goto" :data-url="'product?id=' + product.id">
									<image :src="product.pic"></image>
								</view>
								<view class="info flex1">
									<view class="f1">{{product.name}}</view>
									<view class="f3"><text style="font-weight:bold;">￥{{product.sell_price}}</text></view>
								</view>
							</view>
						</view>
						
						<view class="price" v-if="product.cid  ==2">
							<view class="f1">乘车日期</view>
							<view class="f2">{{product.yy_date}} </view>
						</view>
						<view class="price" v-if="product.cid  ==3">
							<view class="f1">预约时间</view>
							<view class="f2">{{product.yy_date}}</view>
						</view>
						<view class="price">
							<text class="f1">价格</text>
							<text class="f2">¥{{product.sell_price}} <text v-if="product.cid !='2'">/天</text> </text>
						</view>
					</view>
					<view class="bcontent2">
						<view class="price" v-if="userinfo.leveldk_money>0 && ykset.discount">
							<text class="f1">{{t('会员')}}折扣({{userinfo.discount}}折)</text>
							<text class="f2">-¥{{userinfo.leveldk_money}}</text>
						</view>
						
						<view class="price" v-if="product.is_coupon==1">
							<view class="f1">{{t('优惠券')}}</view>
							<view v-if="couponCount > 0" class="f2" @tap="showCouponList">
								<text style="color:#fff;padding:4rpx 16rpx;font-weight:normal;border-radius:8rpx;font-size:24rpx"
									:style="{background:t('color1')}">{{couponrid!=0?couponList[couponkey].couponname:couponCount+'张可用'}}</text><text
									class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</view>
							<text class="f2" v-else style="color:#999">无可用{{t('优惠券')}}</text>
						</view>

					<!-- 	<view class="price" v-if="product.cid ==3">
							<view class="f1" style="flex: 2;">包车天数</view>
							<view class="addnum f2">
								<view class="minus" @tap="gwcminus"><image class="img" :src="pre_url+'/static/img/cart-minus.png'"/></view>
								<input class="input" type="number" :value="gwcnum" @input="gwcinput"></input>
								<view class="plus" @tap="gwcplus"><image class="img" :src="pre_url+'/static/img/cart-plus.png'"/></view>
							</view>
						</view> -->
						<view class="price" v-if="product.cid ==3">
							<view class="f1" style="flex: 2;">包车数量</view>
							<view class="addnum f2">
								<view class="minus" @tap="buynumminus"><image class="img" :src="pre_url+'/static/img/cart-minus.png'"/></view>
								<input class="input" type="number" :value="buynum" @input="buynuminput"></input>
								<view class="plus" @tap="buynumplus"><image class="img" :src="pre_url+'/static/img/cart-plus.png'"/></view>
							</view>
						</view>
						<view class="price" v-if="product.cid ==1">
							<view class="f1" style="flex: 1;">租车天数</view>
							<view class="choose f2">
								<view class="choosetime"  @tap="toStartYuyue"><text v-if="start_time !=''">{{start_time}}</text><text v-else>开始时间</text></view>
								<text class="difftext" v-if="difftext">{{difftext}} </text><text class="difftext" v-else>请选时间</text>
								<view class="choosetime"  @tap="toEndYuyue"><text v-if="end_time !=''">{{end_time}}</text><text v-else>结束时间</text></view>
							</view>
						</view>
						<view style="display:none">{{test}}</view>
						<view class="form-item" v-for="(item,idx) in formdata" :key="item.id">
							<view class="label">{{item.val1}}<text v-if="item.val3==1" style="color:red"> *</text></view>
							<block v-if="item.key=='price'">
								<text class="f2"  style="color:#999">￥{{item.val2}}</text>
							</block>
							<block v-if="item.key=='input'">
								<input type="text" :name="'form_'+idx" class="input" :placeholder="item.val2" placeholder-style="font-size:28rpx"/>
							</block>
							<block v-if="item.key=='textarea'">
								<textarea :name="'form_'+idx" class='textarea' :placeholder="item.val2" placeholder-style="font-size:28rpx"/>
							</block>
							<block v-if="item.key=='radio'">
								<radio-group class="radio-group" :name="'form_'+idx">
									<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
										<radio class="radio" :value="item1"/>{{item1}}
									</label>
								</radio-group>
							</block>
							<block v-if="item.key=='checkbox'">
								<checkbox-group :name="'form_'+idx" class="checkbox-group">
									<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
										<checkbox class="checkbox" :value="item1"/>{{item1}}
									</label>
								</checkbox-group>
							</block>
							<block v-if="item.key=='selector'">
								<picker class="picker" mode="selector" :name="'form_'+idx" :value="item.valdata" :range="item.val2" @change="selectorPickerChange" :data-idx="idx">
									<view v-if="editorFormdata[idx] || editorFormdata[idx]===0"> {{item.val2[editorFormdata[idx]]}}</view>
									<view v-else>请选择</view>
								</picker>
								<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</block>
							<block v-if="item.key=='time'">
								<picker class="picker" mode="time" :name="'form_'+idx" value="" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx">
									<view v-if="editorFormdata[idx]">{{editorFormdata[idx]}}</view>
									<view v-else>请选择</view>
								</picker>
								<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</block>
							<block v-if="item.key=='date'">
								<picker class="picker" mode="date" :name="'form_'+idx" value="" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx">
									<view v-if="editorFormdata[idx]">{{editorFormdata[idx]}}</view>
									<view v-else>请选择</view>
								</picker>
								<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</block>
							<block v-if="item.key=='upload'">
								<input type="text" style="display:none" :name="'form_'+idx" :value="editorFormdata[idx]"/>
								<view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
									<view class="form-imgbox" v-if="editorFormdata[idx]">
										<view class="layui-imgbox-close" style="z-index: 2;" @tap="removeimg" :data-idx="idx"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
										<view class="form-imgbox-img"><image class="image" :src="editorFormdata[idx]" @click="previewImage" :data-url="editorFormdata[idx]" mode="aspectFit"/></view>
									</view>
									<view v-else class="form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 50rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="editorChooseImage" :data-idx="idx"></view>
								</view>
							</block>
						</view>
						
					</view>
					<view class="scoredk flex" v-if="userinfo.score2money > 0">
						<checkbox-group @change="scoredk" class="flex" style="width:100%">
							<view class="f1">
								<view>{{userinfo.score*1}} {{t('积分')}}可抵扣 <text style="color:#e94745">{{userinfo.scoredk_money*1}}</text> 元</view>
								<view style="font-size:22rpx;color:#999" v-if="userinfo.scoredkmaxpercent > 0 && userinfo.scoredkmaxpercent<=100">最多可抵扣订单金额的{{userinfo.scoredkmaxpercent}}%</view>
							</view>
							<view class="f2">使用{{t('积分')}}抵扣
								<checkbox value="1" style="margin-left:6px;transform:scale(.8)"></checkbox>
							</view>
						</checkbox-group>
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
				endyears:[],
				startyears:[],
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
				end_value:[],//结束时间的选中
				usescore: 0,
				scoredk_money:0,
				buynum:1
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

					var difftime = this.getDateDiff(this.start_time,this.end_time,'day');
					if(difftime < 0){
						app.error('开始时间必须小于结束时间');
						return;
					}
					//保存 开始时间的选择的位置
					this.start_value = this.value;
					if(this.years[this.value[0]+2]){
						this.value = [this.value[0]+2,this.value[1],this.value[2]];
					}else{
						this.value = [this.value[0],this.value[1],this.value[2]];
					}	
					
					console.log(this.start_value,'starut');
				}
				//结束时间选择时
				if(this.open_type==2){
					this.end_time = time;
					console.log(this.end_time);
					var difftime = this.getDateDiff(this.start_time,this.end_time,'day');
					if(difftime < 0){
						app.error('结束时间必须大于开始时间');
						return;
					}
					this.end_value = this.value;
					console.log(this.end_value,'this.end_value');
				}
				this.difftime =  difftime;
				console.log(difftime)
				this.computetime();
				this.yuyuevisible = false;
			},
			computetime(){
				//如果天数差大于0时，拼接天数 和计算 是天数 大于4天为一天
				var difftime = this.difftime;
				if(difftime){
					var diffarr = difftime.toString().split(".");
					var tian = diffarr[0] > 0?diffarr[0]+'天':'';
					var xiaoshi =diffarr[1] >0?(diffarr[1]/100*24).toFixed(0):''; 
					var xiaoshi_txt = xiaoshi>0?xiaoshi+'时':'';
					this.difftext =tian+xiaoshi_txt;
					var zc_hour_day = this.ykset.zc_hour_day?this.ykset.zc_hour_day:4;
					if( xiaoshi >=zc_hour_day && xiaoshi > 0){
						this.gwcnum = parseInt(diffarr[0])+1;
					}else if(xiaoshi < zc_hour_day && xiaoshi > 0){
						this.gwcnum = parseInt(diffarr[0])+0.5;
					}else{
						this.gwcnum = diffarr[0];
					}	
					console.log(this.gwcnum,'this.gwcnum');
					this.calculatePrice();
				}
			},
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
				this.years =this.startyears;
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
				this.years = this.endyears;
				console.log(this.years,'years');
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
				app.get('ApiCarHailing/buy', {proid: that.proid,dateIndex:that.dateIndex}, function(res) {
					if(res.status ==0){
						app.error(res.msg);
						if(res.msg =='可供车辆不足'){
							if(res.tourl){
								console.log(res.tourl);
								setTimeout(function(){
									app.goto(res.tourl);
								},2000)
								return;
							}
						}
						setTimeout(function(){
							app.goback();
						},2000)
						return;
					}
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
					that.years = that.startyears = res.years;
					that.endyears = res.endyears;
					that.scoredk_money = that.userinfo.scoredk_money;
					if(that.yytime.length > 0 && that.product.cid==1){
						that.hour = that.yytime[0]['hour']
						that.min= that.yytime[0]['min1']
						//计算默认的租车天数
						that.open_type=1;
						that.getTime();
						that.open_type=2;
						that.getTime();
					}
					var leveldk_money = 0;
					var sell_price = res.product.sell_price;
					var userinfo = res.userinfo;
					if (that.ykset.discount && userinfo.discount > 0 && userinfo.discount < 10) {
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
				var product_price = parseFloat(that.product.sell_price*this.gwcnum) ;
				product_price = parseFloat(product_price * this.buynum)
				var coupon_money = parseFloat(that.coupon_money); //-优惠券抵扣 
				var leveldk_money = parseFloat(that.leveldk_money); //-会员折扣
				var formdata_money =  parseFloat(that.formdata_money); //表单价格
				if(that.coupontype==3) coupon_money = product_price
				if (that.usescore) {
				  var scoredk_money = parseFloat(that.scoredk_money); //-积分抵扣
				} else {
				  var scoredk_money = 0;
				}
				var totalprice = product_price - coupon_money - leveldk_money
				if(formdata_money > 0){
					totalprice =parseFloat( totalprice + formdata_money);
				}
				if (totalprice < 0) totalprice = 0;
				var scoredkmaxpercent = parseFloat(that.userinfo.scoredkmaxpercent); //最大抵扣比例
				
				if (scoredk_money > 0 && scoredkmaxpercent > 0 && scoredkmaxpercent <= 100 && scoredk_money > totalprice * scoredkmaxpercent * 0.01) {
				  scoredk_money = totalprice * scoredkmaxpercent * 0.01 ;
				  totalprice = totalprice - scoredk_money;
				}
				totalprice = totalprice.toFixed(2);
				that.totalprice = totalprice;
			},
			//减
			gwcminus: function (e) {
				this.gwcnum =this.gwcnum <= 1?this.gwcnum: this.gwcnum - 1;
				this.calculatePrice();
			},
			//加
			gwcplus: function (e) {
				this.gwcnum = this.gwcnum + 1;
				this.calculatePrice();
			},
			//输入
			gwcinput: function (e) {
				var gwcnum = parseInt(e.detail.value);
				if (gwcnum < 1) return 1;
				this.gwcnum = gwcnum;
				if(this.gwcnum >=1){
					this.calculatePrice();
				}	
			},
			//减
			buynumminus: function (e) {
				console.log(e);
				this.buynum =this.buynum <= 1?this.buynum: this.buynum - 1;
				this.calculatePrice();
			},
			//加
			buynumplus: function (e) {
				var buynum = this.buynum + 1;
				if(buynum > this.product.car_num){
					buynum = this.product.car_num;
				}
				this.buynum = buynum;
				this.calculatePrice();
			},
			//输入
			buynuminput: function (e) {
				var buynum = parseInt(e.detail.value);
				if (buynum < 1) return 1;
				this.buynum = buynum;
				if(this.buynum >=1){
					this.calculatePrice();
				}	
			},
			//积分抵扣
			scoredk: function (e) {
			  var usescore = e.detail.value[0];
			  if (!usescore) usescore = 0;
			  this.usescore = usescore;
			  this.calculatePrice();
			},
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
				if(that.product.cid ==1 && (start_time =='' || end_time =='' || that.gwcnum<1) ){
					app.error('租车时间不得少于4个小时');
					return;
				}
				var newformdata = {};
				for (var j = 0; j < formdata_fields.length;j++){
					var thisfield = 'form_' + j;
					if (formdata_fields[j].val3 == 1 && (formdata[thisfield] === '' || formdata[thisfield] === undefined || formdata[thisfield].length==0)){
							app.alert(formdata_fields[j].val1+' 必填');return;
					}
					if (formdata_fields[j].key == 'selector') {
						console.log(formdata[thisfield],'---');
							formdata[thisfield] = formdata_fields[j].valdata[formdata[thisfield]]
					}
					newformdata['form'+j] = formdata[thisfield];
				}
				 var usescore = this.usescore;
				app.showLoading('提交中');
				app.post('ApiCarHailing/createOrder', {linkman: linkman,tel: tel,formdata:newformdata,proid:proid,dateIndex:dateIndex,couponrid:couponrid,num:that.gwcnum,start_time:start_time,end_time:end_time,usescore: usescore,buynum:that.buynum}, function(res) {
					app.showLoading(false);
					if(res.status==1 && res.payorderid){
							that.issubmit = true	
							app.goto('/pagesExt/pay/pay?id=' + res.payorderid);
					}else if(res.status==1 && !res.payorderid){
							app.alert('预约成功',function(){
								app.goto('orderlist');
							});
					}else	if(res.status == 0){
						app.error(res.msg);
						console.log(res.msg);
						if(res.msg =='可供车辆不足'){
							setTimeout(function(){
								if(that.ykset.tourl){
									app.goto(that.ykset.tourl)
								}
							})
						}
					
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
			removeimg:function(e){
				var that = this;
				var idx = e.currentTarget.dataset.idx;
				var pics = that.editorFormdata
				pics.splice(idx,1);
				that.editorFormdata = pics;
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
/* 购买数量*/
.price .addnum {font-size: 30rpx;color: #666;width:auto;display:flex;align-items:center}
.price .addnum .plus {width:65rpx;height:48rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.price .addnum .minus {width:65rpx;height:48rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.price .addnum .img{width:24rpx;height:24rpx}
.price .addnum .input{flex:1;width:50rpx;border:0;text-align:center;color:#2B2B2B;font-size:28rpx;margin: 0 15rpx;}

.scoredk {width: 100%;margin-bottom: 20rpx;border-radius: 20rpx;padding: 24rpx 20rpx;background: #fff;display: flex;align-items: center;margin-top: 20rpx;}
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
.form-imgbox-img>.image{width:100%;height:100%}
.form-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.form-uploadbtn{position:relative;height:180rpx;width:180rpx;margin-right: 16rpx;margin-bottom:10rpx;}

.placeholder{  font-size: 26rpx;line-height: 80rpx;}
.selected-item span{ font-size: 26rpx !important;}

.price .choose {color: #666;width:auto;display:flex;align-items:center}
.price .choose .choosetime {width:215rpx;height:48rpx;display:flex;align-items:center;justify-content:center; font-size: 24rpx;font-weight: normal;}
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

</style>
