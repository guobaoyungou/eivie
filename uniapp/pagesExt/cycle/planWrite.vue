<template>
	<view >
		<view class="page"  v-if="isload">
			<view class="head">
				<img class="head_img" @tap="previewImage" :src="nowguige.pic || product.pic" :data-url="nowguige.pic || product.pic" alt="" />
				<view>
					<view class="head_title">{{product.name}}</view>
					<view class="head_text">{{nowguige.name}} | {{product.ps_cycle_title}}</view>
					<view class="head_price">
						<text class="head_icon">￥</text>
						<text v-if="nowguige.sell_price > 0">{{nowguige.sell_price}}</text>
						<text v-else>{{product.sell_price}}</text>
					</view>
				</view>
			</view>
			<view class="body">
				<view class="body_item" v-for="(item, index) in guigedata">
					<view class="body_title flex flex-bt">{{item.title}}<text class="body_text">请选择周期购计划</text></view>
					<view class="body_content">
						<view v-for="(item2, index2) in item.items" :key="index" :data-itemk="item.k" :data-idx="item2.k" @tap="ggchange" class="body_tag" :class="ggselected[item.k]==item2.k?'body_active':''">{{item2.title}}</view>
					</view>
				</view>
				<view class="body_item" >
					<view class="body_title flex flex-bt">配送时间<text class="body_text">{{product.ps_cycle_title}}</text></view>
					<view class="body_content" v-if="product.ps_cycle == 1">
						<view v-for="(item,index) in rateList" :key="index" @click="rateClick(index)" class="body_tag" :class="rateIndex===index?'body_active':''">{{item.label}}</view>
					</view>
				</view>
				<view class="body_item">
					<view class="body_title flex flex-bt">
						<span>开始时间</span>
						<view class="body_data" @click="toCheckDate">{{week}} {{startDate?startDate:'请选择开始时间'}} <img class="body_detail" :src="pre_url+'/static/img/week/week_detail.png'" /> </view>
					</view>
				</view>
				<view class="body_item">
					<view class="body_title flex flex-bt">
						<text>配送期数</text>
						<view class="flex-y-center">
							<text v-if="min_qsnum!=1" class="body_notice">{{min_qsnum}}期起订</text>
							<view class="body_data">
								<img v-if="qsnumState" @click="qsminus" class="body_opt" :class="qsnum<=min_qsnum?'body_disabled':''" :src="pre_url+'/static/img/week/week_cut.png'" />
								<img v-if="!qsnumState" class="body_opt body_disabled" :src="pre_url+'/static/img/week/week_cut.png'" />
								
								<input @blur="getQsTotal" type="number" class="body_num" :value="qsnum"/>
								
								<img @click="qsplus" class="body_opt" :src="pre_url+'/static/img/week/week_add.png'" />
							</view>
						</view>
					</view>
				</view>
				<view class="body_item">
					<view class="body_title flex flex-bt">
						<text>每期数量</text>
						<view class="flex-y-center">
							<text v-if="min_num!=1" class="body_notice">{{min_num}}件起订</text>
							<view class="body_data">
								<img v-if="numState" @click="gwcminus" class="body_opt" :class="num<=min_num?'body_disabled':''" :src="pre_url+'/static/img/week/week_cut.png'" />
								<img v-if="!numState" class="body_opt body_disabled" :src="pre_url+'/static/img/week/week_cut.png'" />
								
								<input @blur="getTotal" type="number" class="body_num" :value="num"/>
								
								<img @click="gwcplus" class="body_opt" :src="pre_url+'/static/img/week/week_add.png'" />
							</view>
						</view>
					</view>
				</view>
			</view>
		</view>
		<view class="bottombar flex-row flex-xy-center" :class="menuindex>-1?'tabbarbot':'notabbarbot'" v-if="product.status==1">
			<view class="operate_data">
				<view class="operate_text">
					配送<text class="operate_color">{{!qsnum?'':qsnum}}</text>期，共<text class="operate_color">{{!num||!qsnum?'':num*qsnum}}</text>件商品
				</view>
				<view class="operate_price">
					<text class="operate_lable">总价：</text><text class="operate_tag">￥</text><text class="operate_num">{{!num||!qsnum?'':totalprice}}</text>
				</view>
			</view>
			<view class="tobuy flex1"  @tap="tobuy" :style="{background:t('color1')}"><text>去结算</text></view>
		</view>
		<loading v-if="loading"></loading>
	</view>
	
</template>

<script>
	var app = getApp();
	export default {
		data() {
			return {
				opt:{},
				pre_url: app.globalData.pre_url,
				product:{},
				isload: false,
				loading:false,
				num: 1,
				qsnum: 1,
				min_num:1,
				min_qsnum:1,
				guigedata:{},
				ggselected:{},
				nowguige:{},
				specsIndex: 0,
				rateList:{},
				rateIndex: 0,
				totalprice:0,
				ks:'',
				startDate:'',
				week :'',
				pspl:'',//配送频率,
				qsnumState: true,
				numState: true,
				
				ps_cycle:''
			}
		},
		onLoad(opt) {
			 this.opt = app.getopts(opt);
			this.getdata();
		},
		onShow(){
			var that  = this;
			uni.$on('selectedDate',function(data){
						that.startDate = data.startStr.dateStr;
						that.week = data.startStr.week
					})
					
		},
		methods: {
			tobuy: function (e) {
				var that = this;
				var ks = that.ks;
				var proid = that.product.id;
				var ggid = that.guigelist[ks].id;
				var stock = that.guigelist[ks].stock;
				var num = that.num;
				if (num < 1) num = 1;
				if (stock < num) {
					app.error('库存不足');
					return;
				}
				
				if (that.startDate =='') {
					app.error('请选择开始时间');
					return;
				}
				
				if(!that.qsnumState){
					app.error('配送期数最小数量为' + that.min_qsnum);
					return;
				}
				
				if(!that.numState){
					app.error('每期数量最小数量为' + that.min_num);
					return;
				}
				
				var prodata = proid + ',' + ggid + ',' + num;
				var pspl_value = that.pspl?that.pspl.value:'';
				var qsdata = that.startDate+','+that.qsnum+','+pspl_value;
				app.goto('/pagesExt/cycle/buy?prodata=' + prodata+'&qsdata='+qsdata);
			},
			getdata:function(){
				var that = this;
				 that.loading = true;
				app.get('ApiCycle/product', {id:that.opt.id}, function (res) {
					 that.loading = false;
					if(res.status==1){
						that.loading = false;
						that.ps_cycle = res.product.ps_cycle;
						that.product = res.product;
						that.shopset = res.shopset;
						that.guigelist = res.guigelist;
						that.guigedata = res.guigedata;
						that.rateList = res.product.everyday_item;
						that.num  = res.product.min_num;
						that.qsnum = res.product.min_qsnum;
						that.min_num  = res.product.min_num;
						that.min_qsnum  = res.product.min_qsnum;
						var guigedata = res.guigedata;
						var ggselected = [];
						for (var i = 0; i < guigedata.length; i++) {
							ggselected.push(0);
						}
						that.ks = ggselected.join(','); 
						console.log(that.ks,'ks');
						that.nowguige = that.guigelist[that.ks];
						that.ggselected = ggselected;
						that.pspl = that.rateList?that.rateList[0]:'';
						that.isload = true;
						if(that.product.freighttype==3 || that.product.freighttype==4){ //虚拟商品不能加入购物车
							that.canaddcart = false;
						}
						that.totalprice = (that.nowguige.sell_price * that.num * that.qsnum).toFixed(2);
						that.isload = true;
					}else{
						app.alert(res.msg)
					}
				});
			},
			ggchange(e){
				var idx = e.currentTarget.dataset.idx;
				var itemk = e.currentTarget.dataset.itemk;
				var ggselected = this.ggselected;
				ggselected[itemk] = idx;
				var ks = ggselected.join(',');
				console.log(ks,'ks');
				this.ggselected = ggselected;
				this.ks = ks;
				this.nowguige = this.guigelist[this.ks];
				console.log(this.nowguige,'nowguige');
				if(this.nowguige.limit_start > 0) {
					if (this.gwcnum < this.nowguige.limit_start) {
						this.gwcnum = this.nowguige.limit_start;
					}
				}
				this.totalprice = (this.nowguige.sell_price * this.num * this.qsnum).toFixed(2);
			},
			rateClick(e){
				this.rateIndex = e;
				this.pspl = this.rateList[e];
				
				this.startDate = '';
				this.week = '';
			},
			qsplus(){//期数加
				this.qsnum +=1;
				if(this.qsnum==''){
					this.qsnumState = false;
					app.error('配送期数不能为空');
				}else if(this.qsnum<this.min_qsnum){
					this.qsnumState = false;
					app.error('配送期数最小数量为' + this.min_qsnum);
				}else{
					this.qsnumState = true;
				}
				this.totalprice = (this.nowguige.sell_price * this.qsnum * this.num).toFixed(2);
			},
			qsminus(){//期数减
				this.qsnum==1?null:this.qsnum -=1;
				this.qsnum = this.qsnum <= this.min_qsnum?this.min_qsnum:this.qsnum;
				this.totalprice = (this.nowguige.sell_price * this.qsnum * this.num).toFixed(2);
			},
			gwcplus(){//数量加
				this.num +=1;
				if(this.num==''){
					this.numState = false;
					app.error('每期数量不能为空');
				}else if(this.num<this.min_num){
					this.numState = false;
					app.error('每期数量最小数量为' + this.min_num);
				}else{
					this.numState = true;
				}
				this.totalprice = (this.nowguige.sell_price * this.qsnum* this.num).toFixed(2);
			},
			gwcminus(){//数量减
				this.num==1?null:this.num -=1;
				this.num = 	this.num <= this.min_num?this.min_num:this.num;
				this.totalprice = (this.nowguige.sell_price * this.qsnum * this.num).toFixed(2);
			},
			getQsTotal(e){
				if(e.detail.value==''){
					this.qsnumState = false;
					app.error('配送期数不能为空');
				}else if(parseInt(e.detail.value)<this.min_qsnum){
					this.qsnumState = false;
					app.error('配送期数最小数量为' + this.min_qsnum);
				}else{
					this.qsnumState = true;
				}
				this.qsnum = parseInt(e.detail.value);
				this.totalprice = (this.nowguige.sell_price * this.qsnum * this.num).toFixed(2);
			},
			getTotal(e){
				if(e.detail.value==''){
					this.numState = false;
					app.error('每期数量不能为空');
				}else if(parseInt(e.detail.value)<this.min_num){
					this.numState = false;
					app.error('每期数量最小数量为' + this.min_num);
				}else{
					this.numState = true;
				}
				this.num = parseInt(e.detail.value);
				this.totalprice = (this.nowguige.sell_price * this.qsnum * this.num).toFixed(2);
			},
			toCheckDate(){
				let type = ''
				if(this.ps_cycle=='2'){
					type = 5
				}else if(this.ps_cycle=='3'){
					type = 6
				}else{
					type =this.pspl?this.pspl.value:''
				}
				app.goto('/pagesExt/cycle/checkDate?date='+this.startDate+'&ys='+this.product.advance_pay_days+'&type='+type);
			}
		}
	}
</script>
<style>
	page {
		background: #f6f6f6;
	}
</style>
<style scoped>
	.page {
		padding: 30rpx 30rpx 200rpx 30rpx;
	}

	.head {
		position: relative;
		width: 690rpx;
		padding: 30rpx;
		background: #FFFFFF;
		border-radius: 10rpx;
		box-sizing: border-box;
		margin: 0 auto;
		display: flex;
	}

	.head_img {
		width: 172rpx;
		height: 172rpx;
		border-radius: 10rpx;
		margin-right: 30rpx;
	}

	.head_title {
		font-size: 28rpx;
		font-family: PingFang SC;
		font-weight: bold;
		color: #323232;
	}

	.head_text {
		font-size: 24rpx;
		font-family: PingFang SC;
		font-weight: 500;
		color: #999999;
		margin-top: 15rpx;
	}

	.head_price {
		font-size: 32rpx;
		font-weight: bold;
		color: #FD4A46;
		margin-top: 30rpx;
	}

	.head_icon {
		font-size: 24rpx;
		font-family: PingFang SC;
		font-weight: 500;
		font-weight: normal;
		color: #FD4A46;
	}

	.body {
		position: relative;
		width: 690rpx;
		padding: 0 30rpx;
		background: #FFFFFF;
		border-radius: 10rpx;
		box-sizing: border-box;
		margin: 20rpx auto 0 auto;
	}

	.body_item {
		padding: 20px 0;
		border-bottom: 1px solid #f6f6f6;
	}

	.body_item:last-child {
		border-bottom: none;
	}

	.body_title {
		font-size: 28rpx;
		font-family: PingFang SC;
		font-weight: bold;
		color: #323232;
	}

	.body_text {
		font-size: 24rpx;
		font-family: PingFang SC;
		font-weight: 500;
		color: #999999;
		margin-left: 20rpx;
	}

	.body_content {
		position: relative;
		display: flex;
		flex-wrap: wrap;
		margin-top: 10rpx;
	}

	.body_tag {
		padding: 0 20rpx;
		height: 54rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		background: #F4F4F4;
		border-radius: 27rpx;
		margin: 20rpx 10rpx 0 0;
		font-size: 22rpx;
		border: 1px solid rgba(0, 0, 0, 0);
		font-family: PingFang SC;
	}

	.body_active {
		background: rgba(252, 67, 67, 0.1200);
		border: 1px solid #FC4343;
		color: #FC4343;
		box-sizing: border-box;
	}

	.body_data {
		font-size: 28rpx;
		font-weight: normal;
		font-family: PingFang SC;
		font-weight: 500;
		color: #686868;
		display: flex;
		align-items: center;
	}

	.body_detail {
		height: 35rpx;
		width: 35rpx;
		margin-left: 10rpx;
	}

	.body_opt {
		height: 40rpx;
		width: 40rpx;
	}

	.body_num {
		font-size: 28rpx;
		font-weight: bold;
		color: #686868;
		width: 100rpx;
		text-align: center;
		padding: 0 20rpx;
	}
	
	.body_notice{
		font-size: 26rpx;
		color: #FC4343;
		margin-left: 30rpx;
		font-weight: normal;
		margin-right: 30rpx;
	}

	.body_disabled {
		opacity: 0.5;
	}

	.operate {
		position: fixed;
		bottom: 0;
		width: 100%;
		background: #FFFFFF;
		box-sizing: border-box;
		padding: 15rpx 30rpx;
		display: flex;
		align-items: center;
		box-shadow: 0rpx 0rpx 18rpx 0rpx rgba(132, 132, 132, 0.3200);
	}

	.operate_data {
		flex: 1;
		margin-left: 30rpx;
	}

	.operate_text {
		font-size: 22rpx;
		color: #999999;
	}

	.operate_color {
		color: #FC4343;
	}

	.operate_price {
		font-size: 24rpx;
		font-family: Alibaba PuHuiTi;
		color: #FC4343;
		margin-top: 5rpx;
	}

	.operate_lable {
		font-size: 22rpx;
		color: #222222;
	}

	.operate_tag {
		font-size: 24rpx;
		font-family: Alibaba PuHuiTi;
		font-weight: bold;
		color: #FC4343;
	}

	.operate_num {
		font-weight: bold;
		font-size: 30rpx;
		color: #FC4343;
	}

	.operate_btn {
		width: 320rpx;
		background: #FD4A46;
		border-radius: 8rpx;
		text-align: center;
		line-height: 80rpx;
		font-size: 28rpx;
		font-family: PingFang SC;
		font-weight: bold;
		color: #FFFFFF;
	}
	
	.bottombar{ width: 100%; position: fixed;bottom: 0px; left: 0px; background: #fff;}
	.bottombar .favorite{width: 15%;color:#707070;font-size:26rpx}
	.bottombar .favorite .fa{ font-size:40rpx;height:50rpx;line-height:50rpx}
	.bottombar .favorite .img{ width:50rpx;height:50rpx}
	.bottombar .favorite .t1{font-size:24rpx;color:#222222;height:30rpx;line-height:30rpx;margin-top:6rpx}
	.bottombar .cart{width: 15%;font-size:26rpx;color:#707070}
	.bottombar .cart .img{ width:50rpx;height:50rpx}
	.bottombar .cart .t1{font-size:24rpx;color:#222222;height:30rpx;line-height:30rpx;margin-top:6rpx}
	.bottombar .tocart{ width: 30%; height: 100rpx;color: #fff; background: #fa938a; font-size: 28rpx;display:flex;flex-direction:column;align-items:center;justify-content:center}
	.bottombar .tobuy{font-weight: bold;height: 80rpx;color: #fff; background: #FC635F; font-size:28rpx;display:flex;flex-direction:column;align-items:center;justify-content:center;margin: 15rpx 30rpx;border-radius: 100rpx;}
</style>
