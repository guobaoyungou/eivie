<template>
<view>
	<view v-if="isload">
		<view class="buydialog-mask" @tap="buydialogChange"></view>
		<view class="buydialog" :class="menuindex>-1?'tabbarbot':''">
			<view class="close" @tap="buydialogChange">
				<image :src="pre_url+'/static/img/close.png'" class="image"/>
			</view>
			<view class="title">
				<image :src="nowguige.pic || product.pic" class="img" @tap="previewImage" :data-url="nowguige.pic || product.pic"/>
					<view class="price" :style="{color:t('color1')}">
						<block  v-if="nowguige.score_price>0 && nowguige.sell_price>0">
							{{nowguige.score_price}}{{t('积分')}} + ￥{{nowguige.sell_price}} 
							<text v-if="nowguige.market_price > nowguige.sell_price" class="t2">￥{{nowguige.market_price}}</text>	
						</block>
						<block  v-else-if="nowguige.score_price>0">
							{{nowguige.score_price}}{{t('积分')}}
						</block>
						<block  v-else-if="nowguige.sell_price>0">
							￥{{nowguige.sell_price}} <text v-if="nowguige.market_price > nowguige.sell_price" class="t2">￥{{nowguige.market_price}}</text>
						</block>
            <block  v-else>
              ￥{{nowguige.sell_price}} <text v-if="nowguige.market_price > nowguige.sell_price" class="t2">￥{{nowguige.market_price}}</text>
            </block>
					</view>
				<view class="choosename">已选规格: {{nowguige.name}}</view>
			</view>
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
			<view class="buynum flex flex-y-center">
				<view class="flex1">数量：</view>
				<view class="addnum">
					<view class="minus"><image class="img" :src="pre_url+'/static/img/cart-minus.png'" @tap="gwcminus"/></view>
					<input class="input" type="number" :value="gwcnum" @input="gwcinput"></input>
					<view class="plus"><image class="img" :src="pre_url+'/static/img/cart-plus.png'" @tap="gwcplus"/></view>
				</view>
			</view>
			<view class="op">
					<button class="tobuy" :style="{backgroundColor:t('color1')}" @tap="addtobuy" >确 定</button>

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
			controller:{default:'ApiHuodongBaoming'},
			needaddcart:{default:true},
			proid:{},
			isfuwu:false
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
					that.gwcnum = 1
					that.guigelist = res.guigelist;
					that.guigedata = res.guigedata
					var guigedata = res.guigedata;
					var ggselected = [];
					for (var i = 0; i < guigedata.length; i++) {
						ggselected.push(0);
					}
					that.ks = ggselected.join(','); 
					that.nowguige = that.guigelist[that.ks];
					that.ggselected = ggselected
					that.isload = true;
					if(that.product.freighttype==3 || that.product.freighttype==4){ //虚拟商品不能加入购物车
						that.canaddcart = false;
					}
				});
			},
			buydialogChange:function(){
				this.$emit('buydialogChange');
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
			},
			addtobuy: function (e) {
				var that = this;
				var ks = that.ks;
				var proid = that.product.id;
				var ggid = that.guigelist[ks].id;
				var num = that.gwcnum;
				if (num < 1) num = 1;
				var prodata = proid + ',' + ggid + ',' + num;
				var prodata = proid + ',' + ggid + ',' + num;
				if(!ggid || ggid==undefined){ app.error('请选择服务'); return;}
				app.goto('/pagesB/huodongbaoming/buy?prodata=' + prodata);
			},
			//加
			gwcplus: function (e) {
				var gwcnum = this.gwcnum + 1;
				var ks = this.ks;				
				if(this.product.perlimit > 0) {
					if (gwcnum > this.product.perlimit) {
						app.error('该服务最多购买' + this.product.perlimit + '份');
						return;
					}
				}
				this.gwcnum = this.gwcnum + 1;
			},
			//减
			gwcminus: function (e) {
				var gwcnum = this.gwcnum - 1;
				var ks = this.ks;				
					
				if (gwcnum <= 0) {
					return;
				}
				this.gwcnum = this.gwcnum - 1;
			},
			//输入
			gwcinput: function (e) {
				console.log(e)
				var ks = this.ks;
				var gwcnum = parseInt(e.detail.value);
				if (gwcnum < 1) return 1;
				//if (gwcnum > this.guigelist[ks].stock) {
				//	return this.guigelist[ks].stock > 0 ? this.guigelist[ks].stock : 1;
				//}
				console.log(gwcnum);
				this.gwcnum = gwcnum;
			},
		}
	}
</script>
<style>

.buydialog-mask{ position: fixed; top: 0px; left: 0px; width: 100%; background: rgba(0,0,0,0.5); bottom: 0px;z-index:10}
.buydialog{ position: fixed; width: 100%; left: 0px; bottom: 0px; background: #fff;z-index:11;border-radius:20rpx 20rpx 0px 0px}
.buydialog .close{ position: absolute; top: 0; right: 0;padding:20rpx;z-index:12}
.buydialog .close .image{ width: 30rpx; height:30rpx; }
.buydialog .title{ width: 94%;position: relative; margin: 0 3%; padding:20rpx 0px; border-bottom:0; height: 190rpx;}
.buydialog .title .img{ width: 160rpx; height: 160rpx; position: absolute; top: 20rpx; border-radius: 10rpx; border: 0 #e5e5e5 solid;background-color: #fff}
.buydialog .title .price{ padding-left:180rpx;width:100%;font-size: 36rpx;height:70rpx; color: #FC4343;overflow: hidden;}
.buydialog .title .price .t1{ font-size:26rpx}
.buydialog .title .price .t2{ font-size:26rpx;text-decoration:line-through;color:#aaa}
.buydialog .title .choosename{ padding-left:180rpx;width: 100%;font-size: 24rpx;height: 42rpx;line-height:42rpx;color:#888888}
.buydialog .title .stock{ padding-left:180rpx;width: 100%;font-size: 24rpx;height: 42rpx;line-height:42rpx;color:#888888}

.buydialog .guigelist{ width: 94%; position: relative; margin: 0 3%; padding:0px 0px 10px 0px; border-bottom: 0; }
.buydialog .guigelist .name{ height:70rpx; line-height: 70rpx;}
.buydialog .guigelist .item{ font-size: 30rpx;color: #333;flex-wrap:wrap}
.buydialog .guigelist .item2{display: flex;align-items: center;min-height:40rpx;height:auto;margin-bottom:4px;border:0; border-radius:4rpx; padding:10rpx 40rpx;color:#666666; margin-right: 10rpx; font-size:26rpx;background:#F4F4F4;}
.buydialog .guigelist .on{color:#FC4343;background:rgba(252,67,67,0.1);font-weight:bold}
.buydialog .buynum{ width: 94%; position: relative; margin: 0 3%; padding:10px 0px 10px 0px; }
.buydialog .addnum {font-size: 30rpx;color: #666;width:auto;display:flex;align-items:center}
.buydialog .addnum .plus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.buydialog .addnum .minus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.buydialog .addnum .img{width:24rpx;height:24rpx}
.buydialog .addnum .input{flex:1;width:70rpx;border:0;text-align:center;color:#2B2B2B;font-size:24rpx}

.buydialog .op{width:90%;margin:20rpx 5%;border-radius:36rpx;overflow:hidden;display:flex;margin-top:100rpx;}
.buydialog .addcart{flex:1;height:72rpx; line-height: 72rpx; color: #fff; border-radius: 0px; border: none;font-size:28rpx;font-weight:bold}
.buydialog .tobuy{flex:1;height: 72rpx; line-height: 72rpx; color: #fff; border-radius: 0px; border: none;font-size:28rpx;font-weight:bold}
.buydialog .nostock{flex:1;height: 72rpx; line-height: 72rpx; background:#aaa; color: #fff; border-radius: 0px; border: none;}
</style>