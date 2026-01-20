<template>
<view>
	<view v-if="isload">
		<view class="buydialog-mask" @tap="buydialogChange"  @touchmove.stop.prevent=" "></view>
		<view class="buydialog" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
		<scroll-view scroll-y style="height: auto;">
			<view class="close" @tap="buydialogChange">
				<image :src="pre_url+'/static/img/close.png'" class="image"/>
			</view>
			<view class="content-view flex-row">
				<view class='left-view'>
					<view class="guige-title">{{!isEmpty(guigedata[0]) ? guigedata[0].title : ''}}</view>
					<view class="guigelist flex-row">
						<view class="item flex flex-y-center">
							<block v-for="(item2, index2) in guigedata[0].items" :key="index2">
								<view :data-itemk="guigedata[0].k" :data-idx="item2.k" :class="'item2 ' + (ggselected[guigedata[0].k]==item2.k ? 'on':'')" @tap="ggchange">
									<view class="image-view">
										<image :src="item2.ggpic_wholesale" mode="scaleToFill"></image>
									</view>
									<view class="guige-name">
										{{item2.title}}
									</view>
								</view>
							</block>
						</view>
					</view>
				</view>
				<view class='right-view'>
					<!-- 商品图片信息展示 -->
					<view class="title flex">
						<image :src="nowguige.pic || product.pic" class="img" @tap="previewImage" :data-url="nowguige.pic || product.pic" mode="aspectFill"/>
						<view class="flex-col">
							<view class='product-name'>{{product.name}}</view>
							<view class="pifa-tag" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}">{{ jietiDiscountType ? '整批':'混批'}}</view>
						</view>
						<!-- <view v-if="controller =='ApiRestaurantShop' || controller =='ApiRestaurantTakeaway'" >
							<view class="price" :style="{color:t('color1')}" >￥{{totalprice}}</view>
						</view> -->
					</view>
					<!-- 价格区间 -->
					<view class="pricerange-view flex-y-center">
						<scroll-view scroll-x="true" style="white-space: nowrap;"  v-if="nowguige.sell_price != '请先登录'">
							<view class="price-range flex-col" v-for="(item,index) in jietidiscountArr" :key="index">
								<view class="price-text flex-y-center"><text style="font-size: 24rpx;">￥</text>{{(item.ratio*nowguige.sell_price*0.01).toFixed(2)}}</view>
								<view class="range-text" v-if="item.end_num">{{item.start_num}} - {{item.end_num}}{{product.product_unit ? product.product_unit :''}}</view>
								<view class="range-text" v-else> >{{item.start_num}}{{product.product_unit ? product.product_unit :''}}</view>
							</view>
						</scroll-view>
					</view>
					<!--产品描述-->
<!-- 					<view style="max-height:50vh;overflow:scroll" v-if="product.sellpoint">
						<view   class="guigelist flex-col">
							<view class="name">产品描述</view>
							<view  class="item flex flex-y-center">
								<view class="description">{{product.sellpoint}}</view>
							</view>
						</view>
					</view> -->
					<view class="guigetitle">{{!isEmpty(guigedata[1]) ? guigedata[1].title : ''}}</view>
					<scroll-view scroll-y style="height:45vh;">
						<view v-for="(item,index) in guigedata[1].items" class="buynum flex flex-y-center">
							<view class="flex1">
								<view class='description'>{{item.title}}</view>
								<!-- <view class='description'>库存：00</view> -->
							</view>
							<view class="addnum">
								<view class="minus" @tap="gwcminus(item,index)"><image class="img" :src="pre_url+'/static/img/cart-minus.png'" /></view>
								<input class="input" type="number" :value="item.num" @input="gwcinput($event,item,index)"></input>
								<view class="plus" @tap="gwcplus(item,index)"><image class="img" :src="pre_url+'/static/img/cart-plus.png'"/></view>
							</view>
						</view>
					</scroll-view>
				</view>
			</view>
		</scroll-view>
			<block>
				<view class="total-view flex-y-center">
					<view>已选{{total_quantity}}件</view>
					<view class="flex-y-center">商品金额：<view class="price" :style="{color:t('color1')}" v-if="nowguige.sell_price != '请先登录'">￥{{total_price}}</view>
					<view class="price" :style="{color:t('color1')}" v-else>请先登录</view>
					</view>
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
				gwcnum:0,
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
				total_quantity:1,
				total_price:"",
				jietiDiscountType:'',
				pre_url: app.globalData.pre_url,
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
						if(that.jietidiscountArr.length){
							that.jietidiscountArr.forEach((item2,index2) => {
								// 判断总数量或者单数量
								if((that.jietiDiscountType ? item.num : num) >= item2.start_num){
									priceArr[index] = item.num*item.sell_price*item2.ratio*0.01; //不判断区间 - 通过索引直接替换
								}else if((that.jietiDiscountType ? item.num : num) < that.jietidiscountArr[0].start_num){
									priceArr[index] = item.num*item.sell_price; //不在区间内就按照原价走
								}
							})
						}else{
							priceArr[index] = item.num*item.sell_price;
						}						
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
						that.jietidiscountArr = [];
					}
					that.jietiDiscountType = that.product.jieti_discount_type; //阶梯优惠价格类型
					that.shopset = res.shopset;
					if(!that.product.limit_start){
						that.product.limit_start = 1;
					}
					
					that.guigelist = res.guigelist;
					Object.values(that.guigelist).map(item => item.num = 0);//向规格list 加入数量初始值
					that.guigedata = res.guigedata;
					that.guigedata[1].items.map(item => item.num = 0); //向2规格加入初始值
					// that.guigedata[1].items[0].num = 1; //默认选中第一个规格
					var guigedata = res.guigedata;
					var ggselected = [];
					for (var i = 0; i < guigedata.length; i++) {
						ggselected.push(0);
					}
					that.ks = ggselected.join(','); 
					// that.guigelist[that.ks].num = that.guigedata[1].items[0].num;
					that.nowguige = that.guigelist[that.ks];
					that.ProductQuantity(); //默认第一次计算价格
					// 默认选择第一个规格
					that.ggselected = ggselected;
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
					if(that.controller =='ApiRestaurantShop' || that.controller =='ApiRestaurantTakeaway'){
						
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
				let thisKeysArr = Object.keys(this.guigelist).filter(item => item.split(',')[0] == idx);
				thisKeysArr.forEach((itemm,index) => {
					if(this.guigelist[itemm].ks.split(',')[1] == this.guigedata[1].items[index].k){
						this.guigedata[1].items[index].num = this.guigelist[itemm].num 
					}
				})
				this.nowguige = this.guigelist[this.ks];
				this.nowguige.pic = this.guigedata[0].items[idx].ggpic_wholesale;
				if(this.nowguige.limit_start > 0) {
					if (this.gwcnum < this.nowguige.limit_start) {
						this.gwcnum = this.nowguige.limit_start;
					}
				}
				// this.totalprice = parseFloat( parseFloat(this.nowguige.sell_price)).toFixed(2);
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
					app.post(this.controller+'/addcartmore', {prodata: prodataIdArr.join('-'),glass_record_id:glass_record_id}, function (res) {
						if (res.status == 1) {
							app.success('添加成功');
							// 所有商品数量
							that.$emit('addcart',{proid: proid,ggid: ggid,num: num,jlprice:that.jlprice,jltitle:that.jltitle});
							that.$emit('buydialogChange');
						} else {
							app.error(res.msg);
						}
					});
				}else{
					that.$emit('addcart',{proid: proid,ggid: ggid,num: num,jlprice:that.jlprice,jltitle:that.jltitle});
					that.$emit('buydialogChange');
				}
			},
			//加
			gwcplus: function (e,index) {
				let  gwcnum = e.num + 1;
				var ggselected = this.ggselected;
				ggselected[1] = e.k;
				let ks = ggselected.join(',');
				this.ggselected = ggselected;
				this.ks = ks;
				if (gwcnum > this.guigelist[ks].stock) {
					app.error('库存不足');
					return 1;
				}
				if (this.product.perlimitdan > 0 && gwcnum > this.product.perlimitdan) {
					app.error('每单限购'+this.product.perlimitdan+'件');
					return 1;
				}
				this.guigelist[ks].num = gwcnum;
				let guigeArr = this.guigedata[1].items;
				this.guigedata[1].items = [];
				guigeArr[index].num = gwcnum;
				this.guigedata[1].items = guigeArr;
				this.ProductQuantity();
			},
			//减
			gwcminus: function (e,index) {
				if(!e.num) return;
				let  gwcnum = e.num - 1;
				var ggselected = this.ggselected;
				ggselected[1] = e.k;
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
				let guigeArr = this.guigedata[1].items;
				this.guigedata[1].items = [];
				guigeArr[index].num = gwcnum;
				this.guigedata[1].items = guigeArr;
				this.ProductQuantity();
			},
			//输入
			gwcinput: function (e,item,index) {
				var ggselected = this.ggselected;
				ggselected[1] = item.k;
				let ks = ggselected.join(',');
				this.ggselected = ggselected;
				this.ks = ks;
				this.nowguige = this.guigelist[this.ks];
				var gwcnum = parseInt(e.detail.value);
				if (gwcnum > this.guigelist[ks].stock) {
					if(this.guigelist[ks].stock > 0){this.guigelist[ks].num = this.guigelist[ks].stock;}else{this.guigelist[ks].num = 0;}
					let guigeArr = this.guigedata[1].items;
					this.guigedata[1].items = [];
					guigeArr[index].num = 0;
					this.guigedata[1].items = guigeArr;
					return this.guigelist[ks].stock > 0 ? this.guigelist[ks].stock : 0;
				}
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
				let guigeArr = this.guigedata[1].items;
				this.guigedata[1].items = [];
				guigeArr[index].num = gwcnum;
				this.guigedata[1].items = guigeArr;
				this.ProductQuantity();
			},
		}
	}
</script>
<style scoped>
.buydialog{ overflow: hidden;}

.buydialog .content-view{width: 100%;max-height:70vh;justify-content: space-between;}
.buydialog .content-view .left-view{width: 30%;background: #f4f4f4;}
.buydialog .content-view .right-view{width: 70%;padding: 20rpx;padding-right: 30rpx;}

.content-view .left-view .guige-title{ width: 100%;height: 100rpx;line-height: 100rpx;font-weight: bold;text-align: center;}
.content-view .left-view .guigelist{ width: 100%;position: relative;max-height: 100%;overflow: scroll;margin: 0;}
.content-view .left-view .guigelist .item{ font-size: 28rpx;color: #999;display: flex;width: 100%;}
.content-view .left-view .guigelist .item2{height: 140rpx;padding:0rpx 20rpx;color:#666666;font-size:26rpx;display: flex;align-items: center;justify-content: flex-start;width: 100%;}
.content-view .left-view .guigelist .item2 .image-view {width: 80rpx;height: 80rpx;border-radius:10rpx;overflow: hidden;margin: 15rpx 0rpx;}
.content-view .left-view .guigelist .item2 .guige-name{margin-left: 10rpx;width: 100rpx;overflow: hidden;text-overflow: ellipsis;font-size: 24rpx;padding: 5rpx 0rpx;color: #888;}
.left-view .guigelist .item2 .image-view image{width: 80rpx;height: 80rpx;}
.content-view .left-view .guigelist .on{background:#fff;}

.content-view .right-view .title{ width: 100%;position: relative;}
.content-view .right-view .title .product-name{font-size: 26rpx;color: #333;padding-right: 10rpx;line-height: 1.5em;
  overflow: hidden;text-overflow: ellipsis; display: -webkit-box;-webkit-line-clamp: 3; -webkit-box-orient: vertical;width: 260rpx;height: 4.5em;}
.content-view .right-view .title .pifa-tag{height: 44rpx;line-height: 44rpx;border-radius: 14rpx;font-size: 20rpx;width:60rpx;text-align: center;}
.content-view .right-view .title .img{width: 160rpx; height: 160rpx; border-radius: 10rpx; border: 0 #e5e5e5 solid;background-color: #fff;flex-shrink: 0;margin-right: 15rpx;}
.content-view .right-view .title .price{ width:100%;font-size: 36rpx;color: #FC4343;overflow: hidden;}
.content-view .right-view .title .price .t1{ font-size:26rpx}
.content-view .right-view .title .price .t2{ font-size:26rpx;text-decoration:line-through;color:#aaa; margin-left: 6rpx;}
.content-view .right-view .title .choosename{ width: 100%;font-size: 24rpx;height: 42rpx;line-height:42rpx;color:#888888}
.content-view .right-view .title .stock{ width: 100%;font-size: 24rpx;height: 42rpx;line-height:42rpx;color:#888888}

.content-view .right-view .guigetitle{ width: 100%; font-weight: bold;padding-top: 20rpx;}
.content-view .right-view .buynum{ width: 100%; position: relative;margin: 7% 0rpx;}
.content-view .right-view .addnum {font-size: 30rpx;color: #666;width:auto;display:flex;align-items:center}
.content-view .right-view .addnum .plus {width:65rpx;height:48rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.content-view .right-view .addnum .minus {width:65rpx;height:48rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.content-view .right-view .addnum .img{width:24rpx;height:24rpx}
.content-view .right-view .addnum .input{flex:1;width:50rpx;border:0;text-align:center;color:#2B2B2B;font-size:28rpx;margin: 0 15rpx;}
.content-view .right-view .tips-text{display:inline-block;margin-top:20rpx;margin-bottom:10rpx;border-radius:10rpx;font-size:20rpx;height:44rpx;line-height:44rpx;padding:0 20rpx}

.content-view .right-view .pricerange-view{width: 100%;justify-content: space-between;border-bottom: 1px #ececec solid;overflow: scroll;}
.pricerange-view .price-range{justify-content: center;text-align: center;padding: 20rpx 0rpx;;display: inline-block;width: 130rpx;margin-right: 10rpx;}
.pricerange-view .price-range .price-text{font-size: 28rpx;color: #333;justify-content: center;}
.pricerange-view .price-range .range-text{font-size: 24rpx;color: #888;}
.buydialog .total-view{width: 90%;justify-content: space-between;font-size: 26rpx;color:#333;margin: 20rpx auto 0rpx;}
.buydialog .total-view .price{ font-size: 30rpx;color: #FC4343;}
.buydialog .op{margin-top:20rpx;}
.member_module{border: 1rpx solid #fd4a46;}
.member_lable{background: #fd4a46;}
.member_value{color: #fd4a46;}
</style>