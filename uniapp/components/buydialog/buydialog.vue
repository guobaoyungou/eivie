<template>
<view>
	<view v-if="isload">
		<view class="buydialog-mask" @tap="buydialogChange" @touchmove.stop.prevent=""></view>
		<view class="buydialog" :class="menuindex>-1?'tabbarbot':'notabbarbot'" @touchmove.stop.prevent="">
			<view class="close" @tap="buydialogChange">
				<image :src="pre_url+'/static/img/close.png'" class="image"/>
			</view>
			<scroll-view scroll-y>
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
								￥{{nowguige.sell_price}}<text v-if="!isNull(nowguige.service_fee) && show_service_fee && nowguige.service_fee > 0">+{{nowguige.service_fee}}{{t('服务费')}}</text>
								<text v-if="!isNull(nowguige.freezemoney_price) && nowguige.freezemoney_price > 0">+{{nowguige.freezemoney_price}}{{t('冻结资金')}}</text>
								<text v-if="product.product_type==2 && Number(nowguige.unit_price)>0" class="t1-m">(单价：￥{{nowguige.unit_price}}/斤)</text>
								<text v-if="Number(nowguige.market_price) > Number(nowguige.sell_price)" class="t2">￥{{nowguige.market_price}}</text>
								<text v-if="product.product_show_costprice_fanwei ==1" class="t2" style="text-decoration:none !important;">￥{{nowguige.cost_price}}</text>
							 </view>
						</view>
						
						<view v-if="shopset && (shopset.price_show_type =='1' ||shopset.price_show_type =='2') ">
							<view v-if="product.is_vip=='0' ">
								<view class="price" :style="{color:t('color1')}" v-if="product.price_type != 1 || nowguige.sell_price > 0" >
									<block v-if="product.price_dollar && nowguige.usdsell_price>0">	
									<text style="margin-right: 10rpx;">${{nowguige.usdsell_price}}</text></block>
									￥{{nowguige.sell_price}}
									<text v-if="Number(nowguige.market_price) > Number(nowguige.sell_price)" class="t2">￥{{nowguige.market_price}}</text>
                  <text v-if="product.product_show_costprice_fanwei ==1" style="text-decoration:none !important;" class="t2">￥{{nowguige.cost_price}}</text>
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
          <view class="choosename" v-if="product.product_type==3">手工费: ￥{{nowguige.hand_fee?nowguige.hand_fee:0}}</view>
				</view>
			</view>
			<!--产品描述-->
			<block v-if="product.sellpoint">
				<scroll-view scroll-y style="max-height:50vh;">
					<view class="guigelist flex-col">
						<view class="name">产品描述</view>
						<view class="item flex flex-y-center">
							<view class="description">{{product.sellpoint}}</view>
						</view>
					</view>
				</scroll-view>
			</block>
			<view v-if="nowguige.balance_price" style="width:94%;margin:10rpx 3%;font-size:24rpx;" :style="{color:t('color1')}">首付款金额：{{nowguige.advance_price}}元，尾款金额：{{nowguige.balance_price}}元</view>
			<scroll-view scroll-y style="max-height:50vh;">
				<!--商品加料-->
				<view style="height:auto;" v-if="jlselectdata.length > 0">
					<view   class="jialiao flex-col">
						<view class="name">{{product.jl_title||'加料'}}</view>
						<view  class="item flex flex-y-center">
							<view v-for="(jitem, jindex) in jlselectdata" :key="jindex"  class="jlitem" :style="jitem.num >0? 'color:' + t('color1') + ';border-color:' + t('color1'):''"  >
								<view class="addsub" :style="'color:'+t('color1')+';border:4rpx solid '+ t('color1')" @click="jlsubnum(jindex)">－</view>
								<view class="text">{{jitem.title}}</view>
								<view class="addsub" :style="'color:#fff;background-color:'+ t('color1')" @click="jladdnum(jindex)">＋</view>
								<view class="jlnum" :style="'background-color:'+t('color1')" v-if="jitem.num >0">{{jitem.num}}</view>
							</view>
						</view>
					</view>
				</view>
				<!--商品加料 end-->
				
				<view v-for="(item, index) in guigedata" :key="index" class="guigelist flex-col">
					<view class="name">{{item.title}}</view>
					<view class="item flex flex-y-center">
						<block v-for="(item2, index2) in item.items" :key="index2">
							<view :data-itemk="item.k" :data-idx="item2.k" :class="'item2'"  :style="ggselected[item.k] == item2.k ? 'color:' + t('color1') +';background:rgba('+t('color1rgb')+',0.1);font-weight:bold':''" @tap="ggchange">{{item2.title}}</view>
						</block>
					</view>
				</view>
			</scroll-view>
			<!--加料 原餐饮-->
			<block v-if="jialiaodata.length > 0 && (controller =='ApiRestaurantShop' || controller =='ApiRestaurantTakeaway')">
				<scroll-view scroll-y style="max-height:50vh;">
					<view   class="guigelist flex-col">
						<view class="name">加料</view>
						<view  class="item flex flex-y-center">
							<view v-for="(jlitem, jlindex) in jialiaodata" :key="jlindex"  class="item2" :style="jlitem.active ? 'color:' + t('color1') +';background:rgba('+t('color1rgb')+',0.1);font-weight:bold':''" @click="jlchange(jlindex)">{{jlitem.jltitle}}</view>
						</view>
					</view>
				</scroll-view>
			</block>
			
			<block v-if="product.price_type == 1">
				<button class="addcart" :style="{backgroundColor:t('color2')}" @tap="showLinkChange">{{product.xunjia_text?product.xunjia_text:'联系TA'}}</button>
			</block>
			
			<block v-else>
				<view v-if="showbuynum && product.glass_type != 2" class="buynum flex flex-y-center">
					<view class="flex1">购买数量：</view>
					<view class="addnum">
						<view class="minus" @tap="gwcminus"><image class="img" :src="pre_url+'/static/img/cart-minus.png'" /></view>
						<input class="input" type="number" :value="gwcnum" @input="gwcinput" @blur='gwcinputblur'></input>
						<view class="plus" @tap="gwcplus"><image class="img" :src="pre_url+'/static/img/cart-plus.png'"/></view>
					</view>
				</view>
				<view class="tips-text" :style="{color:t('color1')}" v-if="shopset && shopset.showcommission==1 && nowguige.commission > 0">分享好友购买预计可得{{t('佣金')}}：
					<block v-if="nowguige.commission > 0"><text style="font-weight:bold;padding:0 2px">{{nowguige.commission}}</text>{{nowguige.commission_desc}}</block>
					<block v-if="nowguige.commission > 0 && nowguige.commissionScore > 0">+</block>
					<block v-if="nowguige.commissionScore > 0"><text style="font-weight:bold;padding:0 2px">{{nowguige.commissionScore}}</text>{{nowguige.commission_desc_score}}</block>
				</view>
        <block v-if="showglass">
        	<view class="glassinfo" @tap="goto" :data-url="'/pagesExt/glass/index?c=1&dkmid='+dkmid" :style="'background:rgba('+t('color1rgb')+',0.8);color:#FFF'" v-if="product.glass_type && product.glass_type == 1">
        		<view class="g-title">
        			视力档案
        		</view>
        		<view class="flex flex-e">
        			<text>{{glassrecord.id>0?glassrecord.name:'请选择'}}</text>
        			<image :src="pre_url+'/static/img/arrowright.png'">
        		</view>
        	</view>
					<view class="glassinfo" @tap="goto" :data-url="'/pagesC/glass/index?c=1&dkmid='+dkmid" :style="'background:rgba('+t('color1rgb')+',0.8);color:#FFF'" v-if="product.glass_type && product.glass_type == 2">
						<view class="g-title">
							定制参数
						</view>
						<view class="flex flex-e">
							<text>{{glasscustom.id>0?glasscustom.name:'请选择'}}</text>
							<image :src="pre_url+'/static/img/arrowright.png'">
						</view>
					</view>
        </block>
				<view class="op">
					<block v-if="(nowguige.stock <= 0  && !product.yuding_stock) ||( product.yuding_stock && nowguige.stock <= 0 && product.yuding_stock <= 0 )  ">
						<button class="nostock">库存不足</button>
					</block>
					<block v-else>
						<button class="addcart" :style="{backgroundColor:t('color2')}" @tap="addcart" v-if="btntype==0 && canaddcart">加入购物车</button>
						<button class="tobuy" :style="{backgroundColor:t('color1')}" @tap="tobuy" v-if="btntype==0">立即购买</button>
						<button class="addcart" :style="{backgroundColor:t('color2')}" @tap="addcart" v-if="btntype==1">确 定</button>
						<button class="tobuy" :style="{backgroundColor:t('color1')}" @tap="tobuy" v-if="btntype==2">确 定</button>
						<button class="tobuy" :style="{backgroundColor:t('color1')}" @tap="topay" v-if="btntype==3">立即购买</button>
					</block>
				</view>
			</block>
		</scroll-view>
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
				show_service_fee:false,
				this_service_fee:0,
				jlselectdata:[],//加料
				pre_url:app.globalData.pre_url,
				glasscustom:{},
				hasglasscustom:0,
			}
		},
		props: {
			btntype:{default:0},
			menuindex:{default:-1},
			controller:{default:'ApiShop'},
			needaddcart:{default:true},
      showbuynum:{default:true},
			proid:{},
			param:{},
			dkmid:0, //代客下单用户id
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
			getdata:function(){
				var that = this;
				if(this.controller == 'ApiShop' && app.globalData.isdouyin == 1){
					app.showLoading('加载中');
					app.post('ApiShop/getDouyinProductId',{proid:that.proid},function(res){
						app.showLoading(false);
						if(res.status == 1){
							tt.openEcGood({promotionId:res.douyin_product_id});
						}else{
							app.alert(res.msg)
						}
					});
					return;
				}

				that.loading = true;
				app.post(this.controller+'/getproductdetail',{id:that.proid},function(res){
					that.loading = false;
					if(res.status != 1){
						app.alert(res.msg)
						return;
					}
					
					that.product = res.product;
					that.shopset = res.shopset;
					if(!that.product.limit_start){
						that.product.limit_start = 1;
					}
					
					that.guigelist = res.guigelist;
					that.guigedata = res.guigedata;
					var guigedata = res.guigedata;
					var ggselected = [];
					for (var i = 0; i < guigedata.length; i++) {
						ggselected.push(0);
					}
					that.ks = ggselected.join(','); 
					that.nowguige = that.guigelist[that.ks];
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
						if(that.product.glass_type && that.product.glass_type == 2){
							that.getglasscustom();
						}else{
							that.getglassrecord()
						}
					}
					if(that.controller =='ApiRestaurantShop' ||that.controller =='ApiRestaurantTakeaway'){
						
						that.jialiaodata = res.jialiaodata;
						that.totalprice = that.nowguige.sell_price;
					}
					if(that.product.service_fee_switch == 1){
						that.show_service_fee = true
					}
					//新加料
					var jldata = res.jldata;
					var jlselectdata = [];
					if(jldata.length > 0){
						for(var i =0;i<jldata.length; i++){
							var sdata = {id:jldata[i].id,title:jldata[i].title,limit_num:jldata[i].limit_num,num:0,price:jldata[i].price};
							jlselectdata.push(sdata);
						}
					}
					that.jlselectdata = jlselectdata;
					//新加料结束
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
			getglasscustom:function(e){
				var that = this;
				var gcid = app.getCache('_glass_custom_id');
				if(that.showglass===true && (!that.glasscustom || (that.glasscustom && that.glasscustom.id!=gcid))){
					app.post('ApiGlassCustom/myCustom', {pagenum:1,listrow:1,id:gcid}, function (resG) {
						var datalist = resG.data;
						if(datalist.length>0){
							that.hasglasscustom = 1;
							let num = 0;
							if(gcid>0){
								that.gcid = gcid
								for(let i in datalist){
									if(datalist[i].id==gcid){
										that.glasscustom = datalist[i]
										num = datalist[i].qty_left + datalist[i].qty_right;
									}
								}
								that.gwcnum = num; //改变数量
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
				this.nowguige = this.guigelist[this.ks];
				if(this.nowguige.limit_start > 0) {
					if (this.gwcnum < this.nowguige.limit_start) {
						this.gwcnum = this.nowguige.limit_start;
					}
				}
				this.totalprice = parseFloat( parseFloat(this.nowguige.sell_price) +this.jlprice).toFixed(2); ;
				if(this.show_service_fee){
					this.this_service_fee = this.nowguige.service_fee
				}
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
			//提交并支付
				topay: function(e) {
					var that = this;
					var ks = that.ks;
					var proid = that.product.id;
					var ggid = that.guigelist[ks].id;
					var stock = that.guigelist[ks].stock;
					var num = that.gwcnum;
					if (num < 1) num = 1;
					var needaddress = '';
					var addressid = '';
					var checkmemid = '';
					var linkman = '';
					var tel = '';
					var usescore = '';
					var frompage = '';
			
					var buydata = [];			
					var buydatatemp = {
								bid: that.product.bid,
								prodata: proid + ',' + ggid + ',' + num,
								cuxiaoid: '',
								couponrid: '',
								freight_id: '',
								freight_time: '',
								storeid: 0,
								formdata:[],
								type11key: 0,
								moneydec_rate:0,
								weightids:[]
          };
          buydata.push(buydatatemp);
					app.showLoading('提交中');
					app.post('ApiShop/createOrder', {
						frompage: frompage,
						buydata: buydata,
						addressid: addressid,
						linkman: linkman,
						tel: tel,
						checkmemid:checkmemid,
						usescore: usescore,
						latitude:'',
						longitude:'',
            worknum:'',
            discount_code_zc:'',
            poshopid:that.poshopid
					}, function(res) {
						app.showLoading(false);
						if (res.status == 0) {
							app.error(res.msg);
							return;
						}
						if(res.payorderid)
						app.goto('/pagesExt/pay/pay?id=' + res.payorderid,'redirectTo');
					});
				},
			tobuy: function (e) {
				var that = this;
				var ks = that.ks;
				var proid = that.product.id;
				var ggid = that.guigelist[ks].id;
				var stock = that.guigelist[ks].stock;
				var num = that.gwcnum;
				if (num < 1) num = 1;
				if ((stock < num && !that.product.shop_yuding) || (that.product.shop_yuding && stock < num && that.product.yuding_stock < num) ) {
					app.error('库存不足');
					return;
				}
				if(that.product.limitdata && that.product.limitdata.ismemberlevel_limit){
					var limitmsg = '该商品'+that.product.limitdata.days+'天内限购'+that.product.limitdata.limit_num+'件';
					that.product.limitdata.hasOwnProperty('days_type')
					if(that.product.limitdata.hasOwnProperty('days_type') && that.product.limitdata.days_type ==1){
						limitmsg = '本月购买额度已用完，请次月购买。';
					}
					app.error(limitmsg);
					return;
				}
				//加料 如果设置了
				var total_num = 0;
				var jlselectdata = that.jlselectdata;
				for(var i=0; i<jlselectdata.length;i++ ){
					total_num = total_num + jlselectdata[i].num;
				}	
				
				if(that.product.jl_total_min >0 && total_num < that.product.jl_total_min && jlselectdata.length > 0) return app.error('最少'+that.product.jl_total_min+'份');
				
				var prodata = proid + ',' + ggid + ',' + num;
				if(that.showglass){
					if(that.grid>0){
						prodata += ',' + that.grid;
					}
					if(that.gcid > 0){
						prodata += ',' + that.gcid;
					}
				}
				this.$emit('buydialogChange');
				
				var jldata = [];
				var newjlselectdata = [];
				if(that.jlselectdata.length>0){
					for(var i=0;i < that.jlselectdata.length;i++){
						if(that.jlselectdata[i].num > 0){
							newjlselectdata.push(that.jlselectdata[i]);
						}
					}
					jldata.push(newjlselectdata);
				}
				if(this.controller == 'ApiShop'){
          var tourl = '/pages/shop/buy?prodata=' + prodata;
					if(that.param && that.param.roomid>0){
						tourl += '&roomid='+that.param.roomid;
					}
          if(jldata.length > 0){
            tourl += '&jldata='+JSON.stringify(jldata);
          }
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
				//新加料 如果设置了必选
				var total_num = 0;
				var jlselectdata = that.jlselectdata;
				for(var i=0; i<jlselectdata.length;i++ ){
					total_num = total_num + jlselectdata[i].num;
				}	
				if(that.product.jl_total_min >0 && total_num < that.product.jl_total_min && jlselectdata.length > 0) return app.error('最少'+that.product.jl_total_min+'份');
				
				
				if (num < 1) num = 1;
				if (stock < num) {
					app.error('库存不足');
					return;
				}				
				var glass_record_id = 0;
				if(that.showglass){
					glass_record_id = that.grid;
				}
        var addcartdata={
          proid: proid,ggid: ggid,num: num,jlprice:that.jlprice,jltitle:that.jltitle,ggname:that.nowguige.name,ggprice:that.nowguige.sell_price,glass_record_id:glass_record_id
        };
				if(this.needaddcart){
					app.post(this.controller+'/addcart', {proid: proid,ggid: ggid,num: num,glass_record_id:glass_record_id,jldata:that.jlselectdata}, function (res) {
						if (res.status == 1) {
							app.success('添加成功');
              
							that.$emit('addcart',addcartdata);
							that.$emit('buydialogChange');
						} else {
							app.error(res.msg);
						}
					});
				}else{
					that.$emit('addcart',addcartdata);
					that.$emit('buydialogChange');
				}
			},
			//加
			gwcplus: function (e) {
				var gwcnum = this.gwcnum + 1;
				var ks = this.ks;
				if (gwcnum > this.guigelist[ks].stock) {
					app.error('库存不足');
					return 1;
				}
				if (this.product.perlimitdan > 0 && gwcnum > this.product.perlimitdan) {
					app.error('每单限购'+this.product.perlimitdan+'件');
					return 1;
				}
				this.gwcnum = this.gwcnum + 1;
			},
			//减
			gwcminus: function (e) {
				var gwcnum = this.gwcnum - 1;
				var ks = this.ks;
				if(this.nowguige.limit_start > 0) {
					if (gwcnum <= this.nowguige.limit_start - 1) {
						if(this.nowguige.limit_start > 1){
							app.error('该规格' + this.nowguige.limit_start + '件起售');
						}
						return;
					}
				}else{
					if (gwcnum <= this.product.limit_start - 1) {
						if(this.product.limit_start > 1){
							app.error('该商品' + this.product.limit_start + '件起售');
						}
						return;
					}
				}
				
				this.gwcnum = this.gwcnum - 1;
			},
			//输入
			gwcinput: function (e) {
				var ks = this.ks;
				var gwcnum = parseInt(e.detail.value);
				if (gwcnum < 1) return 1;
				if (gwcnum > this.guigelist[ks].stock) {
					return this.guigelist[ks].stock > 0 ? this.guigelist[ks].stock : 1;
				}
				if (this.product.perlimitdan > 0 && gwcnum > this.product.perlimitdan) {
					app.error('每单限购'+this.product.perlimitdan+'件');
					gwcnum = this.product.perlimitdan;
				}
				
				this.gwcnum = gwcnum;
			},
			// 输入失去焦点 起售数量判断
			gwcinputblur(e){
				var ks = this.ks;
				var gwcnum = parseInt(e.detail.value);
				if (gwcnum < 1) return 1;
				if (gwcnum > this.guigelist[ks].stock) {
					return this.guigelist[ks].stock > 0 ? this.guigelist[ks].stock : 1;
				}
				if(this.nowguige.limit_start > 0) {
					if (gwcnum <= this.nowguige.limit_start - 1) {
						if(this.nowguige.limit_start > 1){
							app.error('该规格' + this.nowguige.limit_start + '件起售');
						}
						gwcnum = this.nowguige.limit_start;
					}
				}else{
					if (gwcnum <= this.product.limit_start - 1) {
						if(this.product.limit_start > 1){
							app.error('该商品' + this.product.limit_start + '件起售');
						}
						gwcnum = this.product.limit_start;
					}
				}
				this.gwcnum = gwcnum;
			},
			jladdnum:function(index){
				var that = this;
				var jlselectdata = that.jlselectdata;
				var selectdata = jlselectdata[index];
				var selectnum = selectdata.num + 1;
				
				//判断总份数
				var total_num = 1;
				for(var i=0; i<jlselectdata.length;i++ ){
					total_num = total_num + jlselectdata[i].num;
				}
				if(total_num > that.product.jl_total_max && that.product.jl_total_max >0 && jlselectdata.length > 0){
					app.error('最多'+that.product.jl_total_max+'份');
					return;
				}
				if(selectnum > selectdata.limit_num){
					selectnum = selectdata.num - 1;
					app.error('限购'+selectdata.limit_num+'份');
					return;
				}
				
				selectdata.num = selectnum;
				that.jlselectdata[index] = selectdata;
			},
			jlsubnum:function(index){
				var that = this;
				var jlselectdata = that.jlselectdata;
				var selectdata = jlselectdata[index];
				var selectnum = selectdata.num - 1;
				if(selectnum < 0){
					var selectnum = 0;
				}
				selectdata.num = selectnum;
				that.jlselectdata[index] = selectdata;
			},
		}
	}
</script>