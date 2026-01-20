<template>
<view class="container" v-if="isload">
	<image :src="info.banner" style="width:100%;height:auto;display:table" mode="widthFix" v-if="info.banner"/>
	<view class="content" :style="{background:'linear-gradient(180deg,'+info.bgcolor+' 0%,rgba('+info.bgcolorrgb.red+','+info.bgcolorrgb.green+','+info.bgcolorrgb.blue+',0) 100%)'}">
		<view class="result">
			<view class="result_table">
				<view @click="tableClick(index)" v-for="(item,index) in table" :key="index" class="item"
					:class="tableIndex==index?'active':''">
					{{item.lable}}
					<text v-if="tableIndex!=index">{{item.value}}</text>
				</view>
			</view>
			<view class="result_module">
				<view class="result_data">
					<view class="result_chart">
						<canvas style="width: 220rpx; height: 220rpx;" canvas-id="canvas1" id="canvas1"></canvas>
						<view class="result_num">
							{{table[tableIndex].value}}
						</view>
					</view>
					<view class="result_content">
						<view class="result_item" v-for="(item,index) in cdata">
							<view class="result_tag" :style="{background:item.color}"></view>
							<view class="result_title">{{item.title}}</view>
							<view class="result_price">{{item.pricew[tableIndex]}}</view>
						</view>
					</view>
				</view>
				<block v-for="(item,index) in cdata">
				<view class="result_list" @tap="showitems" :data-index="index">
					<view>{{item.title}}</view>
					<view class="result_more">
						<text>￥{{item.price[tableIndex]}}</text>
						<image :src="pre_url+'/static/img/renovation_calculator/result_'+(item.showitems?'s':'h')+'.png'" alt=""/>
					</view>
				</view>
				<view class="result_option" v-if="item.showitems">
					<view class="item" v-for="(item2,index2) in item.items">
						<text>{{item2.title}}</text>
						<text>{{item2.price[tableIndex]}}</text>
					</view>
				</view>
				</block>
				<view class="result_notice">
					*该报价来自系统估算，实际以量房为准
				</view>
			</view>
		</view>
	</view>
	<view class="qd_guize">
		<!-- <view class="gztitle"> — 兑换规则 — </view> -->
		<view class="guize_txt">
			<parse :content="info.description" />
		</view>
	</view>
	<view style="display:none">{{test}}</view>
</view>
</template>

<script>
var app = getApp();
var windowWidth = uni.getSystemInfoSync().windowWidth;
export default {
	data() {
		return {
			opt:{},
			loading:false,
			isload: false,
			menuindex:-1,
			pre_url:app.globalData.pre_url,
			tableIndex: 0,
			info:{},
			cdata:{},
			table:[],
			test:'',
		}
	},
	onLoad: function(opt) {
		this.opt = app.getopts(opt);
	},
	onPullDownRefresh: function() {
		this.getdata();
	},
	onReady() {
		this.getdata();
	},
	methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiRenovationCalculator/result',{region:that.opt.region,mianji:that.opt.mianji},function (res){
				that.info = res.info;
				that.cdata = res.cdata;
				that.table = res.table;
				uni.setNavigationBarTitle({
					title: that.info.name
				});
				that.loaded();
				setTimeout(() => {
					that.drawCircle()
				},100)
				
			});
		},
		showitems:function(e){
			var index = e.currentTarget.dataset.index
			this.cdata[index].showitems = !this.cdata[index].showitems;
			this.test = Math.random();
		},
		countPercentage(countArray) {
			var j = this.total(countArray);
			var resultArray = [];
			for (var i = 0; i < countArray.length; i++) {
				var k = (countArray[i] / j) * 100;
				resultArray.push(k);
			}
			return resultArray;
		},
		total(arr) {
			var len = arr.length;
			if (len == 0) {
				return 0;
			} else if (len == 1) {
				return arr[0];
			} else {
				return arr[0] + this.total(arr.slice(1));
			}
		},
		drawCircle() {
			var that = this;
			var priceArr = [];
			var colorArr = [];
			for(var i in that.cdata){
				colorArr.push(that.cdata[i].color);
				priceArr.push(that.cdata[i].price[this.tableIndex]);
			}

			var width = windowWidth / 750 * 220 - 10;
      var height = width;
			var x = width / 2+5;
      var y = width / 2+5;
			var ctx = uni.createCanvasContext('canvas1');
			console.log(ctx);
			ctx.clearRect(0,0,width+10,height+10);
			ctx.lineWidth = 10;
			ctx.beginPath();
			let percentageAry = this.countPercentage(priceArr);
				console.log(percentageAry)
			var Deg = 0;
			ctx.translate(x,y);
			for (let i = 0; i < percentageAry.length; i++) {
				var step = (percentageAry[i]) / 50;
				ctx.beginPath()
				ctx.setStrokeStyle(colorArr[i]);
				ctx.rotate((Deg % 360) * Math.PI / 180);
				ctx.arc(0, 0, width/2, 0.75 * Math.PI, 0.75 * Math.PI + step * 1 * Math.PI);
				ctx.stroke();

				Deg = ((percentageAry[i]) / 100) * 360;
			}
			ctx.draw();
		},
		tableClick(e) {
			this.tableIndex = e;
			this.drawCircle();
		}
	}
};
</script>
<style>
	page {
		background: #f0f0f0;
	}

	.content {
		width: 750rpx;
		padding-top: 30rpx;
		padding-bottom:30rpx;
	}

	.result {
		width: 710rpx;
		position: relative;
		border-radius: 35rpx;
		background: #fff;
		box-sizing: border-box;
		overflow: hidden;
		margin: 0 auto;
	}

	.result_table {
		height: 95rpx;
		display: flex;
		background: #e4eff3;
		line-height: 95rpx;
	}

	.result_table .item {
		color: #434e53;
		flex: 1;
		text-align: center;
		font-size: 26rpx;
	}

	.result_table .active {
		font-weight: bold;
		font-size: 40rpx;
		background: #fff;
		border-radius: 20rpx 20rpx 0 0;
	}

	.result_module {
		padding: 30rpx;
	}

	.result_data {
		position: relative;
		display: flex;
		align-items: center;
		padding-bottom: 10rpx;
	}

	.result_chart {
		position: relative;
		height: 195rpx;
		width: 280rpx;
		margin-right: 50rpx;
	}

	.result_chart canvas {
		position: absolute;
		left: 0;
		right: 0;
		top: 0;
		display: block;
		bottom: 0;
		margin: auto auto;
		height: 110px;
		width: 110px;
	}
	
	.result_num{
		position: absolute;
		left: 0;
		right: 0;
		top: 0;
		display: block;
		bottom: 0;
		margin: auto auto;
		height: 110px;
		font-size: 35rpx;
		width: 110px;
		font-weight: bold;
		text-align: center;
		line-height: 110px;
	}
	.result_unit{
		font-size: 24rpx;
	}

	.result_content {
		flex: 1;
	}

	.result_item {
		padding: 15rpx 0;
		display: flex;
		align-items: center;
		font-size: 27rpx;
	}

	.result_tag {
		height: 15rpx;
		width: 15rpx;
		border-radius: 100rpx;
		margin-right: 25rpx;
	}

	.result_title {
		color: #999;
		flex: 1;
	}

	.result_price {
		font-weight: bold;
		color: #333;
	}

	.result_list {
		padding: 30rpx;
		background: #f5f5f5;
		color: #666;
		font-weight: bold;
		font-size: 27rpx;
		display: flex;
		align-items: center;
		justify-content: space-between;
		border-radius: 15rpx;
		margin-top: 20rpx;
	}

	.result_more {
		position: relative;
		display: flex;
		align-items: center;
	}

	.result_more image {
		height: 25rpx;
		width: 25rpx;
		display: block;
		margin-left: 10rpx;
	}

	.result_option {
		padding: 30rpx 30rpx 10rpx 30rpx;
	}

	.result_option .item {
		font-size: 27rpx;
		display: flex;
		justify-content: space-between;
		color: #333;
		margin: 30rpx 0 0 0;
	}

	.result_option .item:first-child {
		margin: 0 0 0 0;
	}

	.result_notice {
		font-size: 23rpx;
		color: #999;
		margin-top: 20rpx;
	}
	.qd_guize{width:100%;margin:30rpx 0 20rpx 0;}
.qd_guize .gztitle{width:100%;text-align:center;font-size:32rpx;color:#656565;font-weight:bold;height:100rpx;line-height:100rpx}
.guize_txt{box-sizing: border-box;padding:0 30rpx;line-height:42rpx;}

</style>
