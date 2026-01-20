<template>
<view>
	<view v-if="isload">
		<view class="buydialog-mask" @tap="buydialogChange"></view>
		<view class="buydialog" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
			<view class="close" @tap="buydialogChange">
				<image :src="pre_url+'/static/img/close.png'" class="image"/>
			</view>
			
			<view class="title flex">
				<image :src="nowguige.pic || product.pic" class="img" @tap="previewImage" :data-url="nowguige.pic || product.pic" mode="aspectFill"/>
				<view class="flex1">
					<view>
						<!-- 价格显示方式 当前等级价格（默认） -->
						<view v-if="(shopset && (shopset.price_show_type =='0' || !shopset.price_show_type)) || !shopset" >
							 <view  class="price" :style="{color:t('color1')}" v-if="product.price_type != 1 || nowguige.sell_price > 0"  >
								<block v-if="product.price_dollar && nowguige.usdsell_price>0">	
								<text style="margin-right: 10rpx;">${{nowguige.usdsell_price}}</text></block>
								￥{{nowguige.sell_price}}
								<text v-if="Number(nowguige.market_price) > Number(nowguige.sell_price)" class="t2">￥{{nowguige.market_price}}</text>
							 </view>
						</view>
						
						<!-- 价格显示方式 -->
						<!-- 1 会员价+默认售价（会员价会员）/默认售价（普通会员） -->
						<!-- 2 会员价+默认售价（会员价会员）/默认售价+会员价（普通会员） -->
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
							
							<!-- 会员价格 -->
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
					<view class="stock" v-if="!shopset || shopset.hide_stock!=1">库存：{{nowguige.stock}}</view>
					<view class="choosename" v-if="product.limit_start<=1">已选规格: {{nowguige.name}}{{jltitle}}</view>
				</view>
			</view>
			<!--产品描述-->
			<view style="max-height:50vh;overflow:scroll" v-if="product.sellpoint">
				<view class="guigelist flex-col">
					<view class="name">产品描述</view>
					<view class="item flex flex-y-center">
						<view class="description">{{product.sellpoint}}</view>
					</view>
				</view>
			</view>
			<view style="max-height:50vh;overflow:scroll">
				<view v-for="(item, index) in guigedata" :key="index" class="guigelist flex-col">
					<view class="name">{{item.title}}</view>
					<view class="item flex flex-y-center">
						<block v-for="(item2, index2) in item.items" :key="index2">
							<view :data-itemk="item.k" :data-idx="item2.k" :class="'item2'"  :style="ggselected[item.k] == item2.k ? 'color:' + t('color1') +';background:rgba('+t('color1rgb')+',0.1);font-weight:bold':''" @tap="ggchange">{{item2.title}}</view>
						</block>
					</view>
				</view>
			</view>
			
			<block>
				<view v-if="showbuynum" class="buynum flex flex-y-center">
					<view class="flex1">购买数量：</view>
					<view class="addnum">
						<view class="minus" @tap="gwcminus"><image class="img" :src="pre_url+'/static/img/cart-minus.png'"/></view>
						<input class="input" type="number" :value="gwcnum" @input="gwcinput" @blur='gwcinputblur'></input>
						<view class="plus" @tap="gwcplus"><image class="img" :src="pre_url+'/static/img/cart-plus.png'"/></view>
					</view>
				</view>
				<view class="op">
					<block>
						<button class="addcart" :style="{backgroundColor:t('color2')}" @tap="addcart">加入采购单</button>
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
				jltitle:'',
				gwcnum:1,
				isload:false,
				loading:false,
				shopset:{},
				totalprice:0,
				pre_url: app.globalData.pre_url,
			}
		},
		props: {
			btntype:{default:0},
			menuindex:{default:-1},
			controller:{default:'ApiShop'},
			needaddcart:{default:true},
      showbuynum:{default:true},
			proid:{},
			param:{}
		},
		mounted:function(){
			var that = this;
			that.getdata();
		},
		methods:{
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
				});
			},
			buydialogChange:function(){
				this.$emit('buydialogChange');
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
				this.totalprice = parseFloat( parseFloat(this.nowguige.sell_price)).toFixed(2);
			},
			//加入购物车操作
			addcart: function () {
				var that = this;
				var ks = that.ks;
				var num = that.gwcnum;
				var proid = that.product.id;
				var ggid = that.guigelist[ks].id;
				var stock = that.guigelist[ks].stock;
				
				
				if (num < 1) num = 1;
        var addcartdata={
          proid: proid,
					ggid: ggid,
					num: num,
					ggname:that.nowguige.name,
					ggprice:that.nowguige.sell_price
        };
				app.post('ApiPurchaseOrder/addcart', {proid: proid,ggid: ggid,num: num}, function (res) {
					if (res.status == 1) {
						app.success('添加成功');
						
						that.$emit('addcart',addcartdata);
						that.$emit('buydialogChange');
					} else {
						app.error(res.msg);
					}
				});
			},
			//加
			gwcplus: function (e) {
				var gwcnum = this.gwcnum + 1;
				var ks = this.ks;
				this.gwcnum = this.gwcnum + 1;
			},
			//减
			gwcminus: function (e) {
				var gwcnum = this.gwcnum - 1;
				var ks = this.ks;
				this.gwcnum = this.gwcnum - 1;
			},
			//输入
			gwcinput: function (e) {
				var ks = this.ks;
				var gwcnum = parseInt(e.detail.value);
				if (gwcnum < 1) return 1;
				this.gwcnum = gwcnum;
			},
			// 输入失去焦点 起售数量判断
			gwcinputblur(e){
				var ks = this.ks;
				var gwcnum = parseInt(e.detail.value);
				if (gwcnum < 1) return 1;
				this.gwcnum = gwcnum;
			}
		}
	}
</script>