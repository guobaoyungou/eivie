<template>
	<view>
		<view class="screen-view">
			<view class="screen-view-left">
				<view class='screen-options' :style="'background:rgba('+t('color1rgb')+',0.05);color:'+tColor('color1')">智能排序<image :src="pre_url+'/static/img/arrowdown.png'"></image></view>
				<view class='screen-options'>1-3公里<image :src="pre_url+'/static/img/arrowdown.png'"></image></view>
				<view class='screen-options'>价格·星级<image :src="pre_url+'/static/img/arrowdown.png'"></image></view>
			</view>
			<view class="right-screen">
				筛选<image :src="`${pre_url}/static/img/hotel/screenicon.png`"></image>
			</view>
		</view>
		<!--  -->
		<view class="hotels-list">
			<block v-for="(item,index) in hotelList">
				<view class="hotels-options">
					<view class="hotel-img">
						<image :src="item.img"></image>
					</view>
					<view class="hotel-info">
						<view class="hotel-title">{{item.name}}</view>
						<view class="hotel-address">{{item.address}}</view>
						<view class="hotel-characteristic">
							<block v-for="(items,indexs) in item.characteristic">
								<view class="characteristic-options" :style="'background:rgba('+t('color1rgb')+',0.05);color:'+tColor('color1')">{{items}}</view>
							</block>
						</view>
						<view class="hotel-but-view">
							<view class="make-info">
								<view class="hotel-price" :style="{color:t('color1')}">
									<view>￥</view>
									<view class="hotel-price-num">{{item.price}}</view>
									<view>起</view>
								</view>
								<view class="hotel-text">{{item.num}}人预定 | 剩{{item.fangjian}}间</view>
							</view>
							<view class="hotel-make"  :style="'background:rgba('+t('color1rgb')+',0.8);color:#FFF'">预约</view>
						</view>
					</view>
				</view>
			</block>
		</view>
	</view>
</template>

<script>
	var app = getApp();
	export default {
		data(){
			return{
				pre_url: app.globalData.pre_url,
				hotelList:[
					{
						img:'https://img1.baidu.com/it/u=1110455567,1375139781&fm=253&fmt=auto&app=120&f=JPEG?w=500&h=1083',
						name:'驿马假日酒店(胖东来店',
						address:'宜宾区林机小区22幢',
						characteristic:['50平米','有床','双长','早饭'],
						price:'999',
						num:'909',
						fangjian:'9'
					},
					{
						img:'https://img1.baidu.com/it/u=1110455567,1375139781&fm=253&fmt=auto&app=120&f=JPEG?w=500&h=1083',
						name:'驿马假日酒店(胖东来店',
						address:'宜宾区林机小区22幢',
						characteristic:['50平米','有床','双长','早饭'],
						price:'999',
						num:'909',
						fangjian:'9'
					},
					{
						img:'https://img1.baidu.com/it/u=1110455567,1375139781&fm=253&fmt=auto&app=120&f=JPEG?w=500&h=1083',
						name:'驿马假日酒店(胖东来店',
						address:'宜宾区林机小区22幢',
						characteristic:['50平米','有床','双长','早饭'],
						price:'999',
						num:'909',
						fangjian:'9'
					},
					{
						img:'https://img1.baidu.com/it/u=1110455567,1375139781&fm=253&fmt=auto&app=120&f=JPEG?w=500&h=1083',
						name:'驿马假日酒店(胖东来店',
						address:'宜宾区林机小区22幢',
						characteristic:['50平米','有床','双长','早饭'],
						price:'999',
						num:'909',
						fangjian:'9'
					},
					{
						img:'https://img1.baidu.com/it/u=1110455567,1375139781&fm=253&fmt=auto&app=120&f=JPEG?w=500&h=1083',
						name:'驿马假日酒店(胖东来店',
						address:'宜宾区林机小区22幢',
						characteristic:['50平米','有床','双长','早饭'],
						price:'999',
						num:'909',
						fangjian:'9'
					},
					{
						img:'https://img1.baidu.com/it/u=1110455567,1375139781&fm=253&fmt=auto&app=120&f=JPEG?w=500&h=1083',
						name:'驿马假日酒店(胖东来店',
						address:'宜宾区林机小区22幢',
						characteristic:['50平米','有床','双长','早饭'],
						price:'999',
						num:'909',
						fangjian:'9'
					}
				]
			}
		},
		methods:{
			tabChange(index){
				this.tabindex = index;
			},
			tColor(text){
				let that = this;
				if(text=='color1'){
					if(app.globalData.initdata.color1 == undefined){
						let timer = setInterval(() => {
							that.tColor('color1')
						},1000)
						clearInterval(timer)
					}else{
						return app.globalData.initdata.color1;
					}
				}else if(text=='color2'){
					return app.globalData.initdata.color2;
				}else if(text=='color1rgb'){
					if(app.globalData.initdata.color1rgb == undefined){
						let timer = setInterval(() => {
							that.tColor('color1rgb')
						},1000)
						clearInterval(timer)
					}else{
						var color1rgb = app.globalData.initdata.color1rgb;
						return color1rgb['red']+','+color1rgb['green']+','+color1rgb['blue'];
					}
				}else if(text=='color2rgb'){
					var color2rgb = app.globalData.initdata.color2rgb;
					return color2rgb['red']+','+color2rgb['green']+','+color2rgb['blue'];
				}else{
					return app.globalData.initdata.textset[text] || text;
				}
			},
		}
	}
</script>

<style>
	.screen-view{width: 100%;display: flex;align-items: center;justify-content: space-between;background: #fff;padding: 30rpx 30rpx;position: sticky;top: 0;}
	.screen-view .screen-view-left{flex:1;display: flex;align-items: center;justify-content: flex-start;margin-right: 30rpx;}
	.screen-view .screen-view-left .screen-options{display: flex;align-items: center;justify-content: space-between;background: #F4F4F4;border-radius: 6px;color: #212121;
	font-size: 24rpx;padding: 12rpx 18rpx;margin-right: 20rpx;}
	.screen-view .screen-view-left .screen-options image{width: 16rpx;height: 16rpx;margin-left: 16rpx;}
	.screen-view .right-screen{display: flex;align-items: center;color: #212121;font-size: 24rpx;}
	.screen-view .right-screen image{width: 24rpx;height: 24rpx;margin-left: 20rpx;}
	/*  */
	.hotels-list{width: 96%;margin: 20rpx auto 0rpx;display: flex;align-items: center;justify-content: space-between;flex-direction:column;}
	.hotels-list .hotels-options{width: 100%;padding: 20rpx;display: flex;align-items: center;justify-content: space-between;border-radius: 8px;background: #FFFFFF;margin-bottom: 20rpx;}
	.hotels-list .hotels-options .hotel-img{width: 98px;height: 130px;border-radius: 15rpx;overflow: hidden;}
	.hotels-list .hotels-options .hotel-img image{width: 100%;height: 100%;}
	.hotels-list .hotels-options .hotel-info{flex: 1;padding-left: 20rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-title{width: 100%;color: #343536;font-size: 30rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-address{width: 100%;color: #7B8085;font-size: 24rpx;margin-top: 7rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-characteristic{width: 100%;display: flex;align-items: center;justify-content: flex-start;margin-top: 7rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-characteristic .characteristic-options{font-size: 20rpx;padding: 7rpx 13rpx;flex-wrap: wrap;margin-right: 20rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-but-view{width: 100%;display: flex;align-items: center;justify-content: space-between;margin-top: 25rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-but-view .make-info{display: flex;flex-direction: column;justify-content: flex-start;}
	.hotels-options .hotel-info .hotel-but-view .make-info .hotel-price{display: flex;align-items: center;justify-content: flex-start;font-size: 24rpx;}
	.hotel-info .hotel-but-view .make-info .hotel-price .hotel-price-num{font-size: 40rpx;font-weight: bold;padding: 0rpx 3rpx;}
	.hotels-options .hotel-info .hotel-but-view .make-info .hotel-text{color: #7B8085;font-size: 24rpx;margin-top: 15rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-but-view .hotel-make{background: linear-gradient(90deg, #06D470 0%, #06D4B9 100%);width: 72px;height: 32px;line-height: 32px;
	text-align: center;border-radius: 36px;color: #FFFFFF;font-size: 28rpx;font-weight: bold;}
</style>