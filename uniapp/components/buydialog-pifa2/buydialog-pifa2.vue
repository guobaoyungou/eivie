<template>
<view>
	<view v-if="isload">
		<view class="buydialog-mask" @tap="buydialogChange" @touchmove.stop.prevent=" "></view>
		<view class="buydialog" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
		<scroll-view scroll-y style="height: auto;max-height: 70vh;">
			<view class="close" @tap="buydialogChange">
				<image :src="pre_url+'/static/img/close.png'" class="image"/>
			</view>
			<view class="title flex">
				<image :src="nowguige.pic || product.pic" class="img" @tap="previewImage" :data-url="nowguige.pic || product.pic" mode="aspectFill"/>
				<view class="flex1">
					<view v-if="controller =='ApiRestaurantShop' || controller =='ApiRestaurantTakeaway'" >
						<view class="price" :style="{color:t('color1')}" >￥{{totalprice}}</view>
					</view>
					<view v-else>
						<view v-if="(shopset && (shopset.price_show_type =='0' || !shopset.price_show_type)) || !shopset" >
							 <view  class="price" :style="{color:t('color1')}" v-if="product.price_type != 1 || nowguige.sell_price > 0"  >
								<block v-if="product.price_dollar && nowguige.usdsell_price>0">	
								<text style="margin-right: 10rpx;">${{nowguige.usdsell_price}}</text></block>
								￥{{nowguige.sell_price}}
								<text v-if="Number(nowguige.market_price) > Number(nowguige.sell_price)" class="t2">￥{{nowguige.market_price}}</text>
							 </view>
						</view>
						<view v-if="shopset && (shopset.price_show_type =='1' ||shopset.price_show_type =='2') ">
							<view v-if="product.is_vip=='0' ">
								<view class="price" :style="{color:t('color1')}" v-if="product.price_type != 1 || nowguige.sell_price > 0" >
									<block v-if="product.price_dollar && nowguige.usdsell_price>0">	
									<text style="margin-right: 10rpx;">${{nowguige.usdsell_price}}</text></block>
									￥{{nowguige.sell_price}}
									<text v-if="Number(nowguige.market_price) > Number(nowguige.sell_price)" class="t2">￥{{nowguige.market_price}}</text>
								</view>
								<view class="member flex" v-if="shopset.price_show_type=='2' &&  product.lvprice ==1 ">
									<view class="member_module flex" :style="'border-color:' + t('color1')">
										<view :style="{background:t('color1')}" class="member_lable flex-y-center">{{nowguige.level_name}}</view>
										<view :style="'color:' + t('color1')" class="member_value">
											￥<text>{{nowguige.sell_price_origin}}</text>
										</view>
									</view>
								</view>
							</view>
							<view v-if="product.is_vip=='1'">
								<view class="member flex">
									<view class="member_module flex" :style="'border-color:' + t('color1')">
										<view :style="{background:t('color1')}" class="member_lable flex-y-center">{{nowguige.level_name}}</view>
										<view :style="'color:' + t('color1')" class="member_value" style="font-size: 36rpx;">
											￥<text>{{nowguige.sell_price}}</text>
										</view>
									</view>
								</view>
								<view class="price" :style="{color:t('color1')}" v-if="product.price_type != 1 || nowguige.sell_price > 0" >
									<block v-if="product.price_dollar && nowguige.usdsell_price>0">	
									<text style="margin-right: 10rpx;">${{nowguige.usdsell_price}}</text></block>
									<text :style="product.lvprice =='1'?'font-size:30rpx;':'font-size:36rpx;'">
										￥{{nowguige.sell_price_origin}}
									</text>
								</view>		
							</view>
						</view>
					</view>
					<text class="choosename" v-if="product.limit_start > 1"> {{product.limit_start}}件起售</text>
					<view class="stock" v-if="!shopset || shopset.hide_stock!=1">库存：{{nowguige.stock}}</view>
					<view class="choosename" v-if="product.limit_start<=1">已选规格: {{nowguige.name}}{{jltitle}}</view>
					<view class="pifa-tag" v-if="product.product_type==4" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}">{{ jietiDiscountType ? '整批':'混批'}}</view>
          <view class="choosename" v-if="product.product_type==3">手工费: ￥{{nowguige.hand_fee?nowguige.hand_fee:0}}</view>
				</view>
			</view>
			<!--产品描述-->
			<view style="max-height:50vh;overflow:scroll" v-if="product.sellpoint && product.product_type != 6">
				<view   class="guigelist flex-col">
					<view class="name">产品描述</view>
					<view  class="item flex flex-y-center">
						<view class="description">{{product.sellpoint}}</view>
					</view>
				</view>
			</view>
			<!-- 价格区间 -->
			<view style="max-height:50vh;overflow:scroll;" v-if="jietidiscountArr.length">
				<view class="guigelist flex-col" style="padding-bottom: 0rpx;">
					<view class="name">阶梯价格</view>
					<view class="name" v-if="nowguige.sell_price == '请先登录'">{{nowguige.sell_price}}</view>
					<view class="pricerange-view flex-y-center" v-if="nowguige.sell_price != '请先登录'">
						<scroll-view scroll-x="true" style="white-space: nowrap;">
							<view class="price-range flex-col" v-for="(item,index) in jietidiscountArr" :key="index">
								<view class="price-text flex-y-center"><text style="font-size: 24rpx;">￥</text>{{(item.ratio*nowguige.sell_price*0.01).toFixed(2)}}</view>
								<view class="range-text" v-if="item.end_num">{{item.start_num}} - {{item.end_num}}{{product.product_unit ? product.product_unit :''}}</view>
								<view class="range-text" v-else> >{{item.start_num}}{{product.product_unit ? product.product_unit :''}}</view>
							</view>
						</scroll-view>
					</view>
				</view>
			</view>	
			<block v-if="showglass">
				<view class="glassinfo" @tap="goto" :data-url="'/pagesExt/glass/index?c=1'" :style="'background:rgba('+t('color1rgb')+',0.8);color:#FFF'">
					<view class="g-title">
						视力档案
					</view>
					<view class="flex flex-e">
						<text>{{glassrecord.id>0?glassrecord.name:'请选择'}}</text>
						<image :src="pre_url+'/static/img/arrowright.png'">
					</view>
				</view>
			</block>
			<view v-if="nowguige.balance_price" style="width:94%;margin:10rpx 3%;font-size:24rpx;" :style="{color:t('color1')}">首付款金额：{{nowguige.advance_price}}元，尾款金额：{{nowguige.balance_price}}元</view>
			
			<block v-if="guigedata.length < 2">
				<view>
					<view v-for="(item, index) in guigedata" :key="index" class="guigelist flex-col">
						<view class="name flex-bt" v-if="product.product_type == 6">
							<view>{{item.title}}</view>
							<view>单价</view>
							<view>数量</view>
						</view>
						<view class="name" v-else>{{item.title}}</view>
						<view v-for="(item2, index2) in item.items" :key="index2" class="buynum flex flex-y-center" :style="product.product_type == 6 ? 'border-top:1px rgba(0,0,0,0.1) solid;padding-top:15rpx':''">
							<view class="buynumleft-view flex flex-y-center">
								<view class="image-view" v-if='item2.pic || product.pic'>
									<image :src="item2.pic ? item2.pic:product.pic" mode="scaleToFill"></image>
								</view>
								<view class='ggname-text'>{{item2.title}}</view>							
								
							</view>
							<view class="ggprice-prounits flex-col" v-if="product.product_type == 6" :style="{color:t('color1')}">
								<view class="gg-price">￥{{item2.sell_price}}</view>
								<view class="gg-prounits">{{item2.prounits}}</view>
							</view>
							<view class="stock-addnum flex-col">
							<view class="addnum">
								<view class="minus" @tap="gwcminus(item2,index2)"><image class="img" :src="pre_url+'/static/img/cart-minus.png'"/></view>
								<input class="input" type="number" :value="item2.num" @input="gwcinput($event,item2,index2)"></input>
								<view class="plus" @tap="gwcplus(item2,index2)"><image class="img" :src="pre_url+'/static/img/cart-plus.png'"/></view>
							</view>
							<view class="stock-text-class" v-if="product.product_type == 6">库存：{{item2.stock}} {{item2.prounit ? item2.prounit :''}}</view>
						</view>
						</view>
					</view>
				</view>
			</block>
			<block v-else>
				<view style="max-height:50vh;overflow:scroll">
					<view v-for="(item, index) in guigedata" :key="index" class="guigelist flex-col" v-if="index != guigedata.length-1">
						<view class="name">{{item.title}}</view>
						<view class="item flex flex-y-center">
							<block v-for="(item2, index2) in item.items" :key="index2">
								<view :data-itemk="item.k" :data-idx="item2.k" :class="'item2 ' + (ggselected[item.k]==item2.k ? 'on':'')" @tap="ggchange">{{item2.title}}</view>
							</block>
						</view>
					</view>
				</view>
				<view style="max-height:50vh;overflow:scroll;">
					<view v-for="(item, index) in guigedatalast" :key="index" class="guigelist flex-col">
						<view class="name flex-bt" v-if="product.product_type == 6">
							<view>{{item.title}}</view>
							<view>单价</view>
							<view>数量</view>
						</view>
						<view class="name" v-else>{{item.title}}</view>
						<view v-for="(item2, index2) in item.items" :key="index2" class="buynum flex flex-y-center"  :style="product.product_type == 6 ? 'border-top:1px rgba(0,0,0,0.1) solid;padding-top:15rpx':''">
							<view class="buynumleft-view flex flex-y-center">
					<!-- 			<view class="image-view">
									<image :src="item2.pic" mode="scaleToFill"></image>
								</view> -->
								<view class='ggname-text'>{{item2.title}}</view>
							</view>
							<view class="ggprice-prounits flex-col" v-if="product.product_type == 6" :style="{color:t('color1')}">
								<view class="gg-price">￥{{item2.sell_price}}</view>
								<view class="gg-prounits">{{item2.prounits}}</view>
							</view>
							<view class="stock-addnum flex-col">
								<view class="addnum" :class="product.product_type == '6' ? 'addnumtype6':''">
									<view class="minus" @tap="gwcminus(item2,index2,item.k)"><image class="img" :src="pre_url+'/static/img/cart-minus.png'"/></view>
									<input class="input" type="number" :value="item2.num" @input="gwcinput($event,item2,index2,item.k)"></input>
									<view class="plus" @tap="gwcplus(item2,index2,item.k)"><image class="img" :src="pre_url+'/static/img/cart-plus.png'"/></view>
								</view>
								<view class="stock-text-class" v-if="product.product_type == 6">库存：{{item2.stock}} {{item2.prounit ? item2.prounit :''}}</view>
							</view>

						</view>
					</view>
				</view>
			</block>
			<!--加料-->
			<view style="max-height:50vh;overflow:scroll" v-if="jialiaodata.length > 0 && (controller =='ApiRestaurantShop' || controller =='ApiRestaurantTakeaway')">
				<view   class="guigelist flex-col">
					<view class="name">加料</view>
					<view  class="item flex flex-y-center">
						
						<view v-for="(jlitem, jlindex) in jialiaodata" :key="jlindex"  class="item2" :class="jlitem.active?'on':''" @click="jlchange(jlindex)">{{jlitem.jltitle}}</view>
					</view>
				</view>
			</view>
		</scroll-view>
			<view class="bottom-but">
				<view class="total-view flex-y-center" v-if="product.product_type == 6">
					<view>已选 {{total_quantity}} 种</view>
					<view class="flex-y-center">商品金额：<view class="price" :style="{color:t('color1')}" v-if="nowguige.sell_price != '请先登录'">￥{{total_price}}</view>
					<view class="price" :style="{color:t('color1')}" v-else>请先登录</view>
					</view>
				</view>
				<block v-if="product.price_type == 1">
					<button class="addcart" :style="{backgroundColor:t('color2')}" @tap="showLinkChange">{{product.xunjia_text?product.xunjia_text:'联系TA'}}</button>
				</block>
				<block v-else>
					<view class="tips-text" :style="{color:t('color1')}" v-if="shopset && shopset.showcommission==1 && nowguige.commission > 0">分享好友购买预计可得{{t('佣金')}}：
						<block v-if="nowguige.commission > 0"><text style="font-weight:bold;padding:0 2px">{{nowguige.commission}}</text>{{nowguige.commission_desc}}</block>
						<block v-if="nowguige.commission > 0 && nowguige.commissionScore > 0">+</block>
						<block v-if="nowguige.commissionScore > 0"><text style="font-weight:bold;padding:0 2px">{{nowguige.commissionScore}}</text>{{nowguige.commission_desc_score}}</block>
					</view>
					<view class="op">
						<block v-if="(nowguige.stock <= 0  && !product.yuding_stock) ||( product.yuding_stock && nowguige.stock <= 0 && product.yuding_stock <= 0 )  ">
							<button class="nostock">库存不足</button>
						</block>
						<block v-else>
							<button class="addcart" :style="{backgroundColor:t('color2')}" @tap="addcart" v-if="btntype==0 && canaddcart">加入购物车</button>
							<button class="tobuy" :style="{backgroundColor:t('color1')}" @tap="tobuy" v-if="btntype==0">立即购买</button>
							<button class="addcart" :style="{backgroundColor:t('color2')}" @tap="addcart" v-if="btntype==1">确 定</button>
							<button class="tobuy" :style="{backgroundColor:t('color1')}" @tap="tobuy" v-if="btntype==2">确 定</button>
						</block>
					</view>
				</block>
			</view>

		</view>
	</view>
	<loading v-if="loading"></loading>
</view>
</template>
<script>
	var app = getApp();
	export default {
		data() {
			return {
				ks:'',
				product:{},
				guigelist:{},
				guigedata:{},
				ggselected:{},
				nowguige:{},
				jialiaodata:[],
				jlprice:0,
				jltitle:'',
				gwcnum:1,
				isload:false,
				loading:false,
				canaddcart:true,
				shopset:{},
				glassrecord:{},
				showglass:false,
				totalprice:0,
				jlselected:[],
				hasglassrecord:0,
				grid:0,
				jietidiscountArr:[],
				guigedatalast:[],
				jietiDiscountType:'',
				total_quantity:1,
				total_price:"",
				guigeProunit:'',
				pre_url:app.globalData.pre_url,
			}
		},
		props: {
			btntype:{default:0},
			menuindex:{default:-1},
			controller:{default:'ApiShop'},
			needaddcart:{default:true},
			proid:{},
      poshopid:{default:0},//排队系统里的店铺ID参数，用于页面进入来源区别
		},
		mounted:function(){
			var that = this;
			uni.$on('getglassrecord', function(data) {
				 that.getglassrecord()
			});
			that.getdata();
		},
		beforeDestroy(){
			uni.$off('getglassrecord')
		},
		methods:{
			ProductQuantity(){
				let that = this;
				let prodataArr = Object.values(this.guigelist).filter(item => item.num > 0);
				if(prodataArr.length){
					let num = prodataArr.reduce((acc,curr) => acc + curr.num,0)
					that.total_quantity = num;
					let priceArr = [];
					prodataArr.forEach((item,index) => {
							priceArr[index] = item.num*item.sell_price*1;
					})
					that.total_price = priceArr.reduce((total,current) => total+current).toFixed(2);
				}else{
					that.total_quantity = 0;
					that.total_price = 0
				}
			},
			getdata:function(){
				var that = this;
				that.loading = true;
				app.post(this.controller+'/getproductdetail',{id:that.proid},function(res){
					that.loading = false;
					if(res.status != 1){
						app.alert(res.msg)
						return;
					}
					that.product = res.product;
					if(that.product.jieti_discount_data){
						that.jietidiscountArr = JSON.parse(that.product.jieti_discount_data); // 价格区间优惠
					}else{
						that.jietidiscountArr = []; // 价格区间优惠
					}
					that.jietiDiscountType = that.product.jieti_discount_type; //阶梯优惠价格类型 0-混 1-整
					that.shopset = res.shopset;
					if(!that.product.limit_start){
						that.product.limit_start = 1;
					}
					that.guigelist = res.guigelist;
					Object.values(that.guigelist).map(item => item.num = 0);//向规格list 加入数量初始值
					that.guigedata = res.guigedata;
					if(that.guigedata.length < 2){
						// 判断商品几个规格-向规格内添加商品图片
						if(that.guigedata.length == 1){
							that.guigedata[0].items.map(item => item.num = 0); //向2规格加入初始值
							that.guigedata[0].items.forEach((item,index) => {
								if(item.k == Object.values(that.guigelist)[index].ks){
									item.pic = Object.values(that.guigelist)[index].pic;
								}
							})
						}
						// that.guigedata[0].items[0].num = 1; //默认选中第一个规格
					}else{
						that.guigedatalast.push(that.guigedata[that.guigedata.length - 1]);
						that.guigedatalast[0].items.map(item => item.num = 0);
						// that.guigedata[that.guigedata.length-1].items[0].num = 1;
					}
					var guigedata = res.guigedata;
					var ggselected = [];
					for (var i = 0; i < guigedata.length; i++) {
						ggselected.push(0);
					}
					that.ks = ggselected.join(','); 
					// that.guigelist[that.ks].num = that.guigedata[that.guigedata.length-1].items[0].num;
					that.nowguige = that.guigelist[that.ks];
					that.ProductQuantity(); //默认第一次计算价格
					that.ggselected = ggselected;
					// 判断此商品类型
					if(that.product.product_type == 6){
						if(that.guigedata.length == 1){

								that.guigedata[0].items.forEach((item,index) => {
									item.prounits = Object.values(that.guigelist)[index].prounits ? Object.values(that.guigelist)[index].prounits : '';
									item.sell_price = Object.values(that.guigelist)[index].sell_price ? Object.values(that.guigelist)[index].sell_price : '';
									item.stock = Object.values(that.guigelist)[index].stock ? Object.values(that.guigelist)[index].stock : '';
								})

							// that.guigedata[0].items[0].num = 1; //默认选中第一个规格
						}else{
							let thisKeysArr = Object.keys(that.guigelist);
							let guigelastArr = [];
							thisKeysArr.forEach((item,index) => {
								if(item.substr(0, item.length - 1) == that.ks.substr(0, that.ks.length - 1)){
									guigelastArr.push(Object.values(that.guigelist)[index])
								}
							})
							guigelastArr.forEach((item,index) => {
								that.guigedatalast[0].items[index].prounits = item.prounits ? item.prounits : '';
								that.guigedatalast[0].items[index].sell_price = item.sell_price ? item.sell_price : '';
								that.guigedatalast[0].items[index].stock = item.stock ? item.stock : '';
								that.guigedatalast[0].items[index].prounit = item.prounit ? item.prounit : '';
								that.guigeProunit = item.prounit ? item.prounit : '';
							})
						}
					}
					if(that.nowguige.limit_start > 0)
						that.gwcnum = that.nowguige.limit_start;
					else
						that.gwcnum = that.product.limit_start;
					that.isload = true;
					if(that.product.freighttype==3 || that.product.freighttype==4){ //虚拟商品不能加入购物车
						that.canaddcart = false;
					}
					//是否是眼睛产品
					if(that.product.product_type==1){
						that.showglass = true
						that.getglassrecord()
					}
					if(that.controller =='ApiRestaurantShop' ||that.controller =='ApiRestaurantTakeaway'){
						that.jialiaodata = res.jialiaodata;
						that.totalprice = that.nowguige.sell_price;
					}
				});
			},
			buydialogChange:function(){
				this.$emit('buydialogChange');
			},
			getglassrecord:function(e){
				var that = this;
				var grid = app.getCache('_glass_record_id');
				if(that.showglass===true && (!that.glassrecord || (that.glassrecord && that.glassrecord.id!=grid))){
					app.post('ApiGlass/myrecord', {pagenum:1,listrow:1,id:grid}, function (resG) {
						var datalist = resG.data;
						if(datalist.length>0){
							that.hasglassrecord = 1;
							if(grid>0){
								that.grid = grid
								for(let i in datalist){
									if(datalist[i].id==grid){
										that.glassrecord = datalist[i]
									}
								}
							}
						}
					});
				}
			},
			showLinkChange:function () {
				this.$emit('showLinkChange');
			},
			//选择规格
			ggchange: function (e){
				var idx = e.currentTarget.dataset.idx;
				var itemk = e.currentTarget.dataset.itemk;
				var ggselected = this.ggselected;
				ggselected[itemk] = idx;
				var ks = ggselected.join(',');
				this.ggselected = ggselected;
				this.ks = ks;
				// 切换规格 清空初始值
				// Object.values(this.guigelist).map(item => item.num = 0);
				// this.guigedatalast[0].items.map(item => item.num = 0);
				// 切换规格 保留初始值
				let deleggselected = JSON.parse(JSON.stringify(this.ggselected));
				deleggselected.pop();
				let thisKeysArr = Object.keys(this.guigelist);
				let guigelastArr = [];
				thisKeysArr.forEach((item,index) => {
					if(item.slice(0, item.length - 2) == deleggselected.join(',')){
						guigelastArr.push(item)
					}
				})
				guigelastArr.forEach((itemm,index) => {
					this.guigedatalast[0].items.forEach((item2,index2) => {
						if(this.guigelist[itemm].ks.split(',')[this.guigedata.length-1] == item2.k){
							item2.num = this.guigelist[itemm].num 
						}
					})
				})
				this.nowguige = this.guigelist[this.ks];
				if(itemk == 0){
					this.nowguige.pic = this.guigedata[0].items[idx].ggpic_wholesale;
				}
				if(this.nowguige.limit_start > 0) {
					if (this.gwcnum < this.nowguige.limit_start) {
						this.gwcnum = this.nowguige.limit_start;
					}
				}
				// 判断此商品类型
				if(this.product.product_type == 6){
					let thisKeysArr = Object.keys(this.guigelist);
					let guigelastArr = [];
					thisKeysArr.forEach((item,index) => {
						if(item.substr(0, item.length - 1) == this.ks.substr(0, this.ks.length - 1)){
							guigelastArr.push(Object.values(this.guigelist)[index])
						}
					})
					guigelastArr.forEach((item,index) => {
						this.guigedatalast[0].items[index].prounits = item.prounits ? item.prounits : '';
						this.guigedatalast[0].items[index].sell_price = item.sell_price ? item.sell_price : '';
						this.guigedatalast[0].items[index].stock = item.stock ? item.stock : '';
						this.guigedatalast[0].items[index].prounit = item.prounit ? item.prounit : '';
						this.guigeProunit = item.prounit ? item.prounit : '';
					})
				}
				this.totalprice = parseFloat( parseFloat(this.nowguige.sell_price) +this.jlprice).toFixed(2);
			},
			jlchange:function(index){
				this.jialiaodata[index].active =this.jialiaodata[index].active==true?false: true;
				var jlprice = 0;
				var title = '';
				let jlselect = [];
				for(let i=0;i<this.jialiaodata.length;i++){
					if(this.jialiaodata[i].active){
						jlprice = jlprice+parseFloat(this.jialiaodata[i].price);	
						title +=','+this.jialiaodata[i].jltitle;
						jlselect.push(this.jialiaodata[i]);
					}
				}
				this.jltitle =title;
				this.jlprice = jlprice;
			 	this.totalprice =parseFloat( parseFloat(this.nowguige.sell_price) +jlprice).toFixed(2);
				this.jlselected = jlselect;
				
				this.jialiaodata = this.jialiaodata;
			},
			tobuy: function (e) {
				var that = this;
				var ks = that.ks;
				var proid = that.product.id;
				var ggid = that.guigelist[ks].id;
				var stock = that.guigelist[ks].stock;
				var num = that.gwcnum;
				let prodataArr = Object.values(that.guigelist).filter(item => item.num > 0);
				if(!prodataArr.length) return app.error("数量不能为0");
				// 混批起售数量判断
				let gwcnum = prodataArr.reduce((acc,curr) => acc + curr.num,0)
				if(this.jietiDiscountType == 0){
					if(this.nowguige.limit_start > 0) {
						if (gwcnum <= this.nowguige.limit_start - 1 && (gwcnum != 0)) {
							if(this.nowguige.limit_start > 1){
								app.error('该规格' + this.nowguige.limit_start + '件起售');
							}
							return;
						}
					}else{
						if (gwcnum <= this.product.limit_start - 1 && (gwcnum != 0)) {
							if(this.product.limit_start > 1) return app.error('该商品' + this.product.limit_start + '件起售');
						}
					}
				}else if(this.product.limit_start > 0){
					let prodnum = prodataArr.filter(item => item.num < this.product.limit_start)
					if(prodnum.length) return app.error('该商品每种规格' + this.product.limit_start + '件起售');
				}
				let prodataIdArr = [];
				for (var i = 0; i < prodataArr.length; i++) {
				  prodataIdArr.push(proid + ',' + prodataArr[i].id + ',' + prodataArr[i].num);
				}
				this.$emit('buydialogChange');
				if(this.controller == 'ApiShop'){
          var tourl = '/pages/shop/buy?prodata=' + prodataIdArr.join('-');
          if(that.poshopid && that.poshopid>0){
          	tourl += '&poshopid='+that.poshopid;
          }
					app.goto(tourl);
				}else if(this.controller == 'ApiSeckill'){
					app.goto('/activity/seckill/buy?prodata=' + prodata);
				}else if(this.controller == 'ApiSeckill2'){
					app.goto('/activity/seckill2/buy?prodata=' + prodata);
				}else if(this.controller == 'ApiRestaurantTakeaway'){
					app.goto('/restaurant/takeaway/buy?prodata=' + prodata);
				}else if(this.controller == 'ApiRestaurantShop'){
					app.goto('/restaurant/shop/buy?prodata=' + prodata);
				}
			},
			//加入购物车操作
			addcart: function () {
				var that = this;
				var ks = that.ks;
				var num = that.gwcnum;
				var proid = that.product.id;
				var ggid = that.guigelist[ks].id;
				var stock = that.guigelist[ks].stock;		
				var glass_record_id = 0;
				if(that.showglass){
					glass_record_id = that.grid;
				}
				let prodataArr = Object.values(that.guigelist).filter(item => item.num > 0);
				if(!prodataArr.length) return app.error("数量不能为0");
				// 混批起售数量判断
				let gwcnum = prodataArr.reduce((acc,curr) => acc + curr.num,0)
				if(this.jietiDiscountType == 0){
					if(this.nowguige.limit_start > 0) {
						if (gwcnum <= this.nowguige.limit_start - 1 && (gwcnum != 0)) {
							if(this.nowguige.limit_start > 1){
								app.error('该规格' + this.nowguige.limit_start + '件起售');
							}
							return;
						}
					}else{
						if (gwcnum <= this.product.limit_start - 1 && (gwcnum != 0)) {
							if(this.product.limit_start > 1) return app.error('该商品' + this.product.limit_start + '件起售');
						}
					}
				}else if(this.product.limit_start > 0){
					let prodnum = prodataArr.filter(item => item.num < this.product.limit_start)
					if(prodnum.length) return app.error('该商品每种规格' + this.product.limit_start + '件起售');
				}
				let prodataIdArr = [];
				for (var i = 0; i < prodataArr.length; i++) {
				  prodataIdArr.push(proid + ',' + prodataArr[i].id + ',' + prodataArr[i].num);
				}
				if(this.needaddcart){
					app.post(this.controller+'/addcartmore', {prodata: prodataIdArr.join('-'),num: num,glass_record_id:glass_record_id}, function (res) {
						if (res.status == 1) {
							app.success('添加成功');
							that.$emit('addcart',{proid: proid,ggid: ggid,num: num,jlprice:that.jlprice,jltitle:that.jltitle});
							that.$emit('buydialogChange');
						} else {
							app.error(res.msg);
						}
					});
				}else{
					app.post(this.controller+'/addcartmore', {prodata: prodataIdArr.join('-'),num: num,glass_record_id:glass_record_id}, function (res) {
						if (res.status == 1) {
							app.success('添加成功');
							that.$emit('addcart');
							that.$emit('buydialogChange');
						} else {
							app.error(res.msg);
						}
					});
				}
			},
		//加
		gwcplus: function (e,index,itemk) {
			let  gwcnum = e.num + 1;
			var ggselected = this.ggselected;
			if(this.guigedata.length < 2){
				ggselected[0] = e.k;
			}else{
				ggselected[itemk] = e.k;
			}
			let ks = ggselected.join(',');
			this.ggselected = ggselected;
			this.ks = ks;
			this.nowguige = this.guigelist[this.ks];
			if (gwcnum > this.guigelist[ks].stock) {
				app.error('库存不足');
				return 1;
			}
			if (this.product.perlimitdan > 0 && gwcnum > this.product.perlimitdan) {
				app.error('每单限购'+this.product.perlimitdan+'件');
				return 1;
			}
			this.guigelist[ks].num = gwcnum;
			if(this.guigedata.length < 2){
				let guigeArr = this.guigedata[0].items;
				this.guigedata[0].items = [];
				guigeArr[index].num = gwcnum;
				this.guigedata[0].items = guigeArr;
			}else{
				let guigeArr = this.guigedatalast[0].items;
				this.guigedatalast[0].items = [];
				guigeArr[index].num = gwcnum;
				this.guigedatalast[0].items = guigeArr;
			}
			this.ProductQuantity();
		},
		//减
		gwcminus: function (e,index,itemk) {
			if(!e.num) return;
			let  gwcnum = e.num - 1;
			var ggselected = this.ggselected;
			if(this.guigedata.length < 2){
				ggselected[0] = e.k;
			}else{
				ggselected[itemk] = e.k;
			}
			let ks = ggselected.join(',');
			this.ggselected = ggselected;
			this.ks = ks;
			this.nowguige = this.guigelist[this.ks];
			// 起售件数判断
			if(this.jietiDiscountType == 1){
				if(this.nowguige.limit_start > 0) {
					if (gwcnum <= this.nowguige.limit_start - 1 && (gwcnum != 0)) {
						if(this.nowguige.limit_start > 1){
							app.error('该规格' + this.nowguige.limit_start + '件起售');
						}
						return;
					}
				}else{
					if (gwcnum <= this.product.limit_start - 1 && (gwcnum != 0)) {
						if(this.product.limit_start > 1){
							app.error('该商品' + this.product.limit_start + '件起售');
						}
						return;
					}
				}
			}
			this.guigelist[ks].num = gwcnum;
			if(this.guigedata.length < 2){
				let guigeArr = this.guigedata[0].items;
				this.guigedata[0].items = [];
				guigeArr[index].num = gwcnum;
				this.guigedata[0].items = guigeArr;
			}else{
				let guigeArr = this.guigedatalast[0].items;
				this.guigedatalast[0].items = [];
				guigeArr[index].num = gwcnum;
				this.guigedatalast[0].items = guigeArr;
			}
			this.ProductQuantity();
		},
		//输入
		gwcinput: function (e,item,index,itemk) {
			var ggselected = this.ggselected;
			if(this.guigedata.length < 2){
				ggselected[0] = item.k;
			}else{
				ggselected[itemk] = item.k;
			}
			let ks = ggselected.join(',');
			this.ggselected = ggselected;
			this.ks = ks;
			this.nowguige = this.guigelist[this.ks];
			var gwcnum = parseInt(e.detail.value);
			if (gwcnum > this.guigelist[ks].stock) {
				if(this.guigelist[ks].stock > 0){this.guigelist[ks].num = this.guigelist[ks].stock;}else{this.guigelist[ks].num = 0;}
				let guigeArr = this.guigedata[1].items;
				this.guigedata[0].items = [];
				guigeArr[index].num = 0;
				this.guigedata[0].items = guigeArr;
				return this.guigelist[ks].stock > 0 ? this.guigelist[ks].stock : 0;
			}
			this.nowguige = this.guigelist[this.ks];
			if(this.jietiDiscountType == 1){
				if(this.nowguige.limit_start > 0) {
					if (gwcnum <= this.nowguige.limit_start - 1) {
						if(this.nowguige.limit_start > 1){
							app.error('该规格' + this.nowguige.limit_start + '件起售');
						}
						gwcnum = 0
					}
				}else{
					if (gwcnum <= this.product.limit_start - 1) {
						if(this.product.limit_start > 1){
							app.error('该商品' + this.product.limit_start + '件起售');
						}
						gwcnum = 0;
					}
				}
			}
			if (this.product.perlimitdan > 0 && gwcnum > this.product.perlimitdan) {
				app.error('每单限购'+this.product.perlimitdan+'件');
				gwcnum = this.product.perlimitdan;
			}
			this.guigelist[ks].num = gwcnum;
			if(this.guigedata.length < 2){
				let guigeArr = this.guigedata[0].items;
				this.guigedata[0].items = [];
				guigeArr[index].num = gwcnum;
				this.guigedata[0].items = guigeArr;
			}else{
				let guigeArr = this.guigedatalast[0].items;
				this.guigedatalast[0].items = [];
				guigeArr[index].num = gwcnum;
				this.guigedatalast[0].items = guigeArr;
			}
			this.ProductQuantity();
		},
		}
	}
</script>
<style scoped>
.pricerange-view{width: 100%;justify-content: space-between;overflow: scroll;}
.pricerange-view .price-range{justify-content: center;padding: 20rpx 0rpx;;display: inline-block;width: 145rpx;margin-right:15rpx;}
.pricerange-view .price-range .price-text{font-size: 28rpx;color: #333;width: 100%;justify-content: center;}
.pricerange-view .price-range .range-text{font-size: 24rpx;color: #888;width: 100%;text-align: center;}

.buydialog .title .choosename{height: 40rpx;line-height:40rpx;}
.buydialog .title .pifa-tag{height: 44rpx;line-height: 44rpx;border-radius: 14rpx;font-size: 20rpx;width:60rpx;text-align: center;}
.buydialog .title .stock{height: 34rpx;line-height:34rpx;}



.buydialog .buynum{width: 100%; position: relative;margin: 2% 0rpx;justify-content: space-between}
.buydialog .buynum .buynumleft-view{justify-content: flex-start;flex:1;}
.buydialog .buynum .buynumleft-view .image-view{width: 100rpx;height: 100rpx;border-radius: 8rpx;overflow: hidden;}
.buydialog .buynum .buynumleft-view .image-view image{width: 100rpx;height: 100rpx;}
.buydialog .buynum .buynumleft-view .ggname-text{font-size: 24rpx;color: #888888;margin-left: 10rpx;}
.buydialog .buynum .ggprice-prounits{align-items: center;font-size: 28rpx;padding: 3rpx 0rpx;}
.buydialog .buynum .stock-addnum{align-items: center;}
.buydialog .buynum .stock-addnum .stock-text-class{font-size: 26rpx;color: #888888;margin-top: 5rpx;}

.buydialog .addnumtype6{border: 1px rgba(0,0,0,0.1) solid;}
.buydialog .addnumtype6 .img{width:34rpx;height:34rpx;}

.buydialog .op{margin-top:20rpx;}

.member_module{border: 1rpx solid #fd4a46;}
.member_lable{background: #fd4a46;}
.member_value{color: #fd4a46;}
.bottom-but{width: 100%;box-shadow: 0rpx -1rpx 1rpx 0rpx rgba(0,0,0,0.1);display: flex;flex-direction: column;align-items: center;}
.bottom-but .total-view{width: 90%;justify-content: space-between;font-size: 26rpx;color:#333;margin: 20rpx auto 0rpx;}
.bottom-but .total-view .price{ font-size: 30rpx;color: #FC4343;}
</style>