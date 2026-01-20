<template>
<view>
	<view v-if="isload">
		<view class="buydialog-mask" @tap="buydialogChange"></view>
		<view class="buydialog" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
			<view class="close" @tap="buydialogChange">
				<image :src="pre_url+'/static/img/close.png'" class="image"/>
			</view>
			<view class="title">
				<image :src="product.pic" class="img" @tap="previewImage" :data-url="product.pic"/>
				<view class="price" :style="{color:t('color1')}">
						<block v-if="product.price_dollar">	<text style="margin-right: 10rpx;">${{product.usdsell_price}}</text></block>
						￥{{product.sell_price}} 
					<text v-if="product.market_price*1 > product.sell_price*1" class="t2">￥{{product.market_price}}</text>
				</view>
				<view class="stock">库存：{{product.stock}}</view>
			</view>
			<view class="buynum flex flex-y-center">
				<view class="flex1">购买数量：</view>
				<view class="addnum">
					<view class="minus" @tap="gwcminus"><image class="img" :src="pre_url+'/static/img/cart-minus.png'" /></view>
					<input class="input" type="number" :value="gwcnum" @input="gwcinput"></input>
					<view class="plus" @tap="gwcplus"><image class="img" :src="pre_url+'/static/img/cart-plus.png'"/></view>
				</view>
			</view>
			<view class="op">
				<block  v-if="product.stock <= 0">
					<button class="nostock">库存不足</button>
				</block>
				<block  v-else>
					<button class="tobuy" :style="{backgroundColor:t('color1')}" @tap="tobuy" v-if="btntype==0">立即购买</button>
					<button class="tobuy" :style="{backgroundColor:t('color1')}" @tap="tobuy" v-if="btntype==2">确 定</button>
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
				gwcnum:1,
				isload:false,
				loading:false,
				canaddcart:true,
				pre_url: app.globalData.pre_url,
			}
		},
		props: {
			btntype:{default:0},
			menuindex:{default:-1},
			controller:{default:'ApiTuangou'},
			needaddcart:{default:true},
			proid:{}
		},
		mounted:function(){
			this.getdata();
		},
		methods:{
			getdata:function(){
				var that = this;
				that.loading = true;
				app.post(this.controller+'/getproductdetail',{id:that.proid},function(res){
					that.loading = false;
					that.product = res.product;
					if(!that.product.limit_start){
						that.product.limit_start = 1;
					}
					that.gwcnum = that.product.limit_start;
					that.isload = true;
					if(that.product.freighttype==3 || that.product.freighttype==4){ //虚拟商品不能加入购物车
						that.canaddcart = false;
					}
					if(that.product.istg == 1){
						that.canaddcart = false;
					}
					if(that.controller == 'ApiTuangou'){
						that.canaddcart = false;
					}
				});
			},
			buydialogChange:function(){
				this.$emit('buydialogChange');
			},
			tobuy: function (e) {
				var that = this;
				var ks = that.ks;
				var proid = that.product.id;
				var num = that.gwcnum;
				if (num < 1) num = 1;
				if (this.product.stock < num) {
					app.error('库存不足');
					return;
				}
				var prodata = proid + ',' + num;
				this.$emit('buydialogChange');
				if(this.controller == 'ApiTuangou'){
					app.goto('/activity/tuangou/buy?prodata=' + prodata);
				}
			},
			//加
			gwcplus: function (e) {
				var gwcnum = this.gwcnum + 1;
				var ks = this.ks;
				if (gwcnum > this.product.stock) {
					app.error('库存不足');
					return 1;
				}
				this.gwcnum = this.gwcnum + 1;
			},
			//减
			gwcminus: function (e) {
				var gwcnum = this.gwcnum - 1;
				var ks = this.ks;
				if (gwcnum <= this.product.limit_start - 1) {
					if(this.product.limit_start > 1){
						app.error('该商品' + this.product.limit_start + '件起售');
					}
					return;
				}
				this.gwcnum = this.gwcnum - 1;
			},
			//输入
			gwcinput: function (e) {
				var ks = this.ks;
				var gwcnum = parseInt(e.detail.value);
				if (gwcnum < 1) return 1;
				if (gwcnum > this.product.stock) {
					return this.product.stock > 0 ? this.product.stock : 1;
				}
				this.gwcnum = gwcnum;
			},
		}
	}
</script>
<style>
.buydialog .title{padding:20rpx 0px;height: 190rpx;}
.buydialog .title .img{ width: 160rpx; height: 160rpx; position: absolute; top: 20rpx; border-radius: 10rpx; border: 0 #e5e5e5 solid;background-color: #fff}
.buydialog .title .price{ padding-left:180rpx;width:100%;font-size: 36rpx;height:70rpx; color: #FC4343;overflow: hidden;}
.buydialog .title .choosename{ padding-left:180rpx;width: 100%;font-size: 24rpx;height: 42rpx;line-height:42rpx;color:#888888}
.buydialog .title .stock{ padding-left:180rpx;width: 100%;font-size: 24rpx;height: 42rpx;line-height:42rpx;color:#888888}
.buydialog .guigelist{padding:0px 0px 10px 0px; }
.buydialog .op{margin-top:100rpx;}
</style>