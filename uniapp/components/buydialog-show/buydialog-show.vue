<template>
<view>
	<view v-if="isload">
		<view class="buydialog-current" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
			<view style="padding-top: 20rpx;padding-left: 20rpx;font-weight: bold;">{{shopset.guige_name?shopset.guige_name:'请选择规格和数量'}}</view>
			<view v-if="nowguige.balance_price" style="width:94%;margin:10rpx 3%;font-size:24rpx;" :style="{color:t('color1')}">首付款金额：{{nowguige.advance_price}}元，尾款金额：{{nowguige.balance_price}}元</view>
			<view style="max-height:50vh;overflow:scroll">
				<view v-for="(item, index) in guigedata" :key="index" class="guigelist flex-col">
					<view class="name">{{item.title}}</view>
					<view class="item flex flex-y-center">
						<block v-for="(item2, index2) in item.items" :key="index2">
							<view :data-itemk="item.k" :data-idx="item2.k" :class="'item2 ' + (ggselected[item.k]==item2.k ? 'on':'')" @tap="ggchange">{{item2.title}}</view>
						</block>
					</view>
				</view>
			</view>
			<block v-if="product.price_type == 1">
				<button class="addcart" :style="{backgroundColor:t('color2')}" @tap="showLinkChange">{{product.xunjia_text?product.xunjia_text:'联系TA'}}</button>
			</block>
			<block v-else>
				<view class="buynum flex flex-y-center">
					<view class="flex1">购买数量：</view>
					<view class="addnum">
						<view class="minus" @tap="gwcminus"><image class="img" :src="pre_url+'/static/img/cart-minus.png'" /></view>
						<input class="input" type="number" :value="gwcnum" @input="gwcinput"></input>
						<view class="plus" @tap="gwcplus"><image class="img" :src="pre_url+'/static/img/cart-plus.png'"/></view>
					</view>
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
				jlprice:0,
				gwcnum:1,
				isload:false,
				loading:false,
				canaddcart:true,
				shopset:{},
				totalprice:0,
				pre_url: app.globalData.pre_url,
			}
		},
		props: {
			btntype:{default:0},
			menuindex:{default:-1},
			controller:{default:'ApiShop'},
			proid:{}
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
				// #ifdef MP-TOUTIAO
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
				// #endif
				that.loading = true;
				app.post(this.controller+'/getproductdetail',{id:that.proid},function(res){
					that.loading = false;
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
					that.$emit('changeGuige',{proid: that.proid,num: that.gwcnum,ggid:that.nowguige.id,stock:that.nowguige.stock,sell_price:that.nowguige.sell_price});
				});
			},
			showLinkChange:function () {
				this.$emit('showLinkChange');
			},
			//选择规格
			ggchange: function (e){
				var that=this
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
				var proid = that.product.id;
				var num = that.gwcnum
				var ggid =this.nowguige.id
				var stock = this.nowguige.stock
				var sell_price = this.nowguige.sell_price
				that.$emit('changeGuige',{proid: proid,num: num,ggid:ggid,stock:stock,sell_price:sell_price});
			},
			//加
			gwcplus: function (e) {
				var that=this
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
				var proid = that.product.id;
				var ggid =this.guigelist[ks].id
				var stock = this.guigelist[ks].stock
				var sell_price = this.guigelist[ks].sell_price
				that.$emit('changeGuige',{proid: proid,num: gwcnum,ggid:ggid,stock:stock,sell_price:sell_price});
			},
			//减
			gwcminus: function (e) {
				var that=this
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
				var proid = that.product.id;
				var ggid =this.guigelist[ks].id
				var stock = this.guigelist[ks].stock
		  	var sell_price = this.guigelist[ks].sell_price
				that.$emit('changeGuige',{proid: proid,num: gwcnum,ggid:ggid,stock:stock,sell_price:sell_price});
			},
			//输入
			gwcinput: function (e) {
				var that=this
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
				if (this.product.perlimitdan > 0 && gwcnum > this.product.perlimitdan) {
					app.error('每单限购'+this.product.perlimitdan+'件');
					gwcnum = this.product.perlimitdan;
				}
				this.gwcnum = gwcnum;
				var proid = that.product.id;
				var ggid =this.guigelist[ks].id
				var stock = this.guigelist[ks].stock
				var sell_price = this.guigelist[ks].sell_price
				that.$emit('changeGuige',{proid: proid,num: gwcnum,ggid:ggid,stock:stock,sell_price:sell_price});
			},
		}
	}
</script>
<style scoped>
.buydialog-current{ background: #fff;z-index:11;border-radius:20rpx 20rpx 0px 0px;margin-top: 10rpx;}
.buydialog-current .guigelist{ width: 94%; position: relative; margin: 0 3%;  border-bottom: 0; }
.buydialog-current .guigelist .name{ height:70rpx; line-height: 70rpx;}
.buydialog-current .guigelist .item{ font-size: 30rpx;color: #333;flex-wrap:wrap}
.buydialog-current .guigelist .item2{ height:60rpx;line-height:60rpx;margin-bottom:4px;border:0; border-radius:4rpx; padding:0 40rpx;color:#666666; margin-right: 10rpx; font-size:26rpx;background:#F4F4F4}
.buydialog-current .guigelist .on{color:#FC4343;background:rgba(252,67,67,0.1);font-weight:bold}
.buydialog-current .buynum{ width: 94%; position: relative; margin: 0 3%; padding:10px 0px 10px 0px; }
.buydialog-current .addnum {font-size: 30rpx;color: #666;width:auto;display:flex;align-items:center}
.buydialog-current .addnum .plus {width:65rpx;height:48rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.buydialog-current .addnum .minus {width:65rpx;height:48rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.buydialog-current .addnum .img{width:24rpx;height:24rpx}
.buydialog-current .addnum .input{flex:1;width:50rpx;border:0;text-align:center;color:#2B2B2B;font-size:28rpx;margin: 0 15rpx;}
.buydialog-current .addcart{flex:1;height:72rpx; line-height: 72rpx; color: #fff; border-radius: 0px; border: none;font-size:28rpx;font-weight:bold}
</style>