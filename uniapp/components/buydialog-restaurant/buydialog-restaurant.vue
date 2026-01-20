<template>
<view>
	<view v-if="isload">
		<view class="buydialog-mask flex-xy-center">
			<view class="buydialog-back" @tap="buydialogChange"></view>
			<view class="buydialog" :class="menuindex>-1?'tabbarbot':'notabbarbot2'" @touchmove.stop.prevent="">
				<view class="close" @tap="buydialogChange">
					<image :src="pre_url+'/static/img/close.png'" class="image"/>
				</view>
				<scroll-view scroll-y="true">
					<scroll-view scroll-y="true" class="page-scroll">
						<view class="box">
							<image :src="nowguige.pic || product.pic" class="banner" mode="aspectFit" @tap="previewImage" :data-url="nowguige.pic || product.pic"/>
						</view>
						<!--产品描述-->
						<view style="max-height:50vh;overflow:scroll" v-if="product.sellpoint">
							<view class="guigelist flex-col">
								<view class="name">产品描述</view>
								<view  class="item flex flex-y-center">
									<view class="description">{{product.sellpoint}}</view>
								</view>
							</view>
						</view>
						<!--jialiao-->
						<view style="height:auto;" v-if="jlselectdata.length > 0 && (controller =='ApiRestaurantShop' || controller =='ApiRestaurantTakeaway')">
							<view   class="guigelist flex-col">
								<view class="name">加料</view>
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
						<view style="height:auto;">
							<view v-for="(item, index) in guigedata" :key="index" class="guigelist flex-col">
								<view class="name">{{item.title}}</view>
								<view class="item flex flex-y-center">
									<block v-for="(item2, index2) in item.items" :key="index2">
										<view :data-itemk="item.k" :data-idx="item2.k" class="item2" :style="ggselected[item.k]==item2.k? 'color:' + t('color1') + ';' + 'border-color:' + t('color1'):''" @tap="ggchange">{{item2.title}}</view>
									</block>
								</view>
							</view>
						</view>
						<!--加料-->
						<view style="height:auto;" v-if="jialiaodata.length > 0 && (controller =='ApiRestaurantShop' || controller =='ApiRestaurantTakeaway')">
							<view   class="guigelist flex-col">
								<view class="name">加料</view>
								<view  class="item flex flex-y-center">
									<view v-for="(jlitem, jlindex) in jialiaodata" :key="jlindex"  class="item2" :style="jlitem.active? 'color:' + t('color1') + ';' + 'border-color:' + t('color1'):''" @click="jlchange(jlindex)">{{jlitem.jltitle}}</view>
								</view>
							</view>
						</view>
					</scroll-view>
					<view class="page_price" :style="{color:t('color1')}">￥{{totalprice}}</view>
					<view class="page_content">已选规格: {{nowguige.name}}{{jltitle}}</view>
					<view class="buynum flex flex-y-center">
						<view class="flex1">购买数量：</view>
						<view class="addnum">
							<view class="minus" @click.stop="gwcminus"><image class="img" :src="pre_url+'/static/img/cart-minus.png'" /></view>
							<input class="input" type="number" :value="gwcnum" @input="gwcinput"></input>
							<view class="plus" @click.stop="gwcplus"><image class="img" :src="pre_url+'/static/img/cart-plus.png'"/></view>
						</view>
					</view>
					<view class="tips-text" :style="{color:t('color1')}" v-if="shopset && shopset.showcommission==1 && nowguige.commission > 0">分享好友购买预计可得{{t('佣金')}}：<text style="font-weight:bold;padding:0 2px">{{nowguige.commission}}</text>{{nowguige.commission_desc}}</view>
					<view class="op">
						<block v-if="nowguige.stock <= 0 || nowguige.stock_daily <=0">
							<button class="nostock">库存不足</button>
						</block>
						<block v-else>
							<button class="addcart" :style="{backgroundColor:t('color1')}" @tap="addcart" v-if="btntype==0 && canaddcart">加入购物车</button>
							<button class="tobuy" :style="{backgroundColor:t('color1')}" @tap="tobuy" v-if="btntype==0">立即购买</button>
							<button class="addcart" :style="{backgroundColor:t('color1')}" @tap="addcart" v-if="btntype==1">确 定</button>
							<button class="tobuy" :style="{backgroundColor:t('color1')}" @tap="tobuy" v-if="btntype==2">确 定</button>
						</block>
					</view>
				</scroll-view>
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
				totalprice:0,
				jlselected:[],
				jlselectdata:[],//新加料
				jlselectindex:[],
				not_selected:[],
				pre_url: app.globalData.pre_url,
			}
		},
		props: {
			btntype:{default:0},
			menuindex:{default:-1},
			controller:{default:'ApiShop'},
			needaddcart:{default:true},
			proid:{}
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
					if(res.status == 0){
						app.error(res.msg);
						return;
					}
					that.product = res.product;
					that.shopset = res.shopset;
					if(!that.product.limit_start){
						that.product.limit_start = 1;
					}
					
					that.guigelist = res.guigelist;
					that.guigedata = res.guigedata;
					that.not_selected =res.not_selected?res.not_selected:[];
					//新加料
					var jldata = res.jldata;
					var jlselectdata = [];
					for(var i =0;i<jldata.length; i++){
						var sdata = {id:jldata[i].id,title:jldata[i].title,limit_num:jldata[i].limit_num,num:0,price:jldata[i].price};
						jlselectdata.push(sdata);
					}
					that.jlselectdata = jlselectdata;
					//新加料结束
					
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
					if(that.controller =='ApiRestaurantShop' ||that.controller =='ApiRestaurantTakeaway'){
						
						that.jialiaodata = res.jialiaodata;
						that.totalprice = that.nowguige.sell_price;
					}
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
				var ggselected = JSON.parse(JSON.stringify(this.ggselected));
				ggselected[itemk] = idx;
				var ks = ggselected.join(',');
				if(this.not_selected.includes(ks)){
					app.error('暂不支持该规格组合，可变更其他规格下单');
					return false;
				}
				this.ggselected = ggselected;
				this.ks = ks;
				this.nowguige = this.guigelist[this.ks];
				if(this.nowguige.limit_start > 0) {
					if (this.gwcnum < this.nowguige.limit_start) {
						this.gwcnum = this.nowguige.limit_start;
					}
				}
				this.totalprice = parseFloat( parseFloat(this.nowguige.sell_price) +this.jlprice).toFixed(2); ;
			
				this.computejlprice();
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
				if(total_num > that.product.jl_total_limit && that.product.jl_total_limit >0){
					app.error('最多加'+that.product.jl_total_limit+'份');
					return;
				}
				if(selectnum > selectdata.limit_num){
					selectnum = selectdata.num - 1;
					app.error('限购'+selectdata.limit_num+'份');
					return;
				}
				
				selectdata.num = selectnum;
				that.jlselectdata[index] = selectdata;
				that.computejlprice();
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
				that.computejlprice();
			},
			computejlprice(){
				var that = this;
				var jlselectdata = that.jlselectdata;
				var totaljlprice = 0;
				for(var i=0; i<jlselectdata.length;i++ ){
					var price = parseFloat(jlselectdata[i].num * jlselectdata[i].price).toFixed(2);
					totaljlprice =parseFloat(parseFloat(totaljlprice) + parseFloat(price)).toFixed(2); 
				}
				that.totalprice =parseFloat( parseFloat(that.nowguige.sell_price) +parseFloat(totaljlprice)).toFixed(2);	
			},
			tobuy: function (e) {
				var that = this;
				var ks = that.ks;
				var proid = that.product.id;
				var ggid = that.guigelist[ks].id;
				var stock = that.guigelist[ks].stock;
				var num = that.gwcnum;
				if (num < 1) num = 1;
				if (stock < num) {
					app.error('库存不足');
					return;
				}
				var prodata = proid + ',' + ggid + ',' + num;
				var jldata=that.jlprice+'-'+that.jltitle;
				this.$emit('buydialogChange');
				if(this.controller == 'ApiRestaurantTakeaway'){
					app.goto('/restaurant/takeaway/buy?prodata=' + prodata+'&jldata='+jldata+'&btype=1');
				}else if(this.controller == 'ApiRestaurantShop'){
					app.goto('/restaurant/shop/buy?prodata=' + prodata+'&jldata='+jldata+'&btype=1');
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
				if(that.product.jl_is_selected ==1){
					var jlselectdata = that.jlselectdata;
					for(var i=0; i<jlselectdata.length;i++ ){
						total_num = total_num + jlselectdata[i].num;
					}	
				}
				if(that.product.jl_is_selected ==1 && total_num ==0) return app.error('请选择加料');
				
				
				if (num < 1) num = 1;
				if (stock < num) {
					app.error('库存不足');
					return;
				}	
				if(this.needaddcart){
					
					app.post(this.controller+'/addcart', {proid: proid,ggid: ggid,num: num,jlprice: that.jlprice,
					jltitle: that.jltitle,jldata:that.jlselectdata}, function (res) {
						if (res.status == 1) {
							app.success('添加成功');
						} else {
							app.error(res.msg);
						}
					});
				}
				
				this.$emit('addcart',{proid: proid,ggid: ggid,num: num,jlprice:this.jlprice,jltitle:this.jltitle,jldata:JSON.stringify(that.jlselectdata)});
				this.$emit('buydialogChange');
				that.jlselectdata = [];
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
			},
		}
	}
</script>
<style>
.buydialog-mask{height:100%;}
.buydialog-back{position: absolute;height: 100%;width: 100%;top: 0;left: 0;}
.buydialog{ position: relative; width: 690rpx;background: #fff;border-radius:20rpx;overflow: hidden;}
.buydialog .box{ padding: 20rpx 20rpx 0 20rpx; }
.buydialog .banner{ height:300rpx;display: block;margin: 0 auto;}
.buydialog .close{z-index: 5;}

.buydialog .title{ width: 92%;position: relative; margin: 0 4%; padding:30rpx 0px 20rpx 0; border-bottom:0; height: 190rpx;}
.buydialog .title .img{ width: 160rpx; height: 160rpx; position: absolute; top: 30rpx; border-radius: 10rpx; border: 0 #e5e5e5 solid;background-color: #fff}
.buydialog .title .price{ padding-left:180rpx;width:100%;font-size: 36rpx;height:70rpx; color: #FC4343;overflow: hidden;}

.buydialog .title .choosename{ padding-left:180rpx;width: 100%;font-size: 24rpx;height: 42rpx;line-height:42rpx;color:#888888}
.buydialog .title .stock{ padding-left:180rpx;width: 100%;font-size: 24rpx;height: 42rpx;line-height:42rpx;color:#888888}
.page-scroll{height: 55vh;}
.page_price{
	font-size: 32rpx;
	font-weight: bold;
	color: #FC4343;
	padding: 30rpx 30rpx 0 30rpx;
	border-top: 1rpx solid #f0f0f0;
}
.page_text{
	font-size: 24rpx;
	color: #888888;
	font-weight: normal;
	margin-left: 10rpx;
}
.page_content{
	font-size: 24rpx;
	color: #888888;
	font-weight: normal;
	margin-top: 30rpx;
	padding: 0 30rpx;
}
.buydialog .guigelist{ width: 92%; position: relative; margin: 0 4%; padding:0px 0px 40rpx 0px; border-bottom: 0; }
.buydialog .guigelist .item2{ height:60rpx;line-height:60rpx;margin-bottom:4px;border:0; border-radius:100rpx; padding:0 40rpx;color:#666666; margin-right: 10rpx; font-size:26rpx;border: 1px solid #eee;}

.buydialog .guigelist .jlitem{width: 48%;line-height:60rpx;margin-bottom:20rpx; border-radius:20rpx; color:#666666;  font-size:26rpx;border: 1px solid #eee;display: flex;padding: 10rpx 10rpx;align-items: center;position: relative;}
.buydialog .guigelist .jlitem:nth-child(2n){margin-left: 3%;}
.buydialog .guigelist .jlitem .addsub{width: 45rpx;height: 45rpx;border-radius: 50%;display:flex;align-items:center;justify-content:center;line-height: 45rpx;}
.buydialog .guigelist .jlitem .img{width:24rpx;height:24rpx}
.buydialog .guigelist .jlitem  .text{width: 65%;text-align: center;word-break: keep-all}
.buydialog .guigelist .jlitem .jlnum{width: 35rpx;height: 35rpx;position: absolute;top: -12rpx;right: -10rpx;border-radius: 10rpx 10rpx 10rpx 0;color: #fff;text-align: center;line-height: 35rpx;}

.buydialog .guigelist .on{color:#FC4343;border: 1rpx solid #FC4343;}
.buydialog .buynum{ width: 92%; position: relative; margin: 0 4%; padding:10px 0px 0px 0px; }
.buydialog .op{;margin:30rpx 5%;}

</style>