<template>
<view class="dp-kanjia" :style="{
	backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:(params.padding_y*2.2)+'rpx '+params.padding_x*2.2+'rpx'
}">
	<view class="dp-kanjia-item" v-if="params.style=='1' || params.style=='2' || params.style=='3'">
		<view class="item" v-for="(item,index) in data" :style="params.style==2 ? 'width:49%;margin-right:'+(index%2==0?'2%':0) : (params.style==3 ? 'width:32%;margin-right:'+(index%3!=2?'2%':0) :'width:100%')" :key="item.id" @click="goto" :data-url="'/activity/yuyue/product?id='+item.proid">
			<view class="product-pic">
				<image class="image" :src="item.pic" mode="widthFix"/>
				<image class="saleimg" :src="params.saleimg" v-if="params.saleimg!=''" mode="widthFix"/>
				<view v-if="params.showisopen == 1" class="showisopen" :style="item.is_open?'background:'+t('color1'):'background:#CCC'">
					{{item.is_open?item.opentip:item.noopentip}}
				</view>
				<view v-if="params.showfwtype == 1" >
					<view v-if="item.fwtype.indexOf('1')>=0" class="showfwtype" style="top: 0;">
						到店服务
					</view>
					<view v-if="item.fwtype.indexOf('2')>=0" class="showfwtype" :style="{top:item.fwtypetop2wx+'rpx '}">
						上门服务
					</view>
					<view v-if="item.fwtype.indexOf('3')>=0" class="showfwtype" :style="{top:item.fwtypetop3wx+'rpx '}">
						到商家服务
					</view>
				</view>
			</view>
			<view class="product-info">
				<view class="p1" v-if="params.showname == 1 || params.showcat == 1"><text v-if="params.showcat == 1" style="color:red">{{item.catnames}}</text><text v-if="params.showname == 1">{{item.name}}</text></view>
				<view class="p2" v-if="params.showbusiness == 1">
					<text>{{item.bname}}</text>
				</view>
				<view v-if="params.showsellpoint == 1" style="line-height: 36rpx;word-break: break-all;white-space: pre-wrap;">{{item.sellpoint}}</view>
				<view v-if="params.showfuwu== 1" style="line-height: 36rpx;display: flex;overflow: hidden;flex-wrap:wrap;font-size: 26rpx;">
					<view v-for="item2 in item.fuwulist" style="margin-right: 10rpx;">
						<view class="fuwu">{{item2}}</view>
					</view>
				</view>
				<view class="p2">
					<view class="p2-1" v-if="params.showprice != '0'">
						<view class="t1" :style="{color:t('color1')}">{{item.sell_price}}<text style="font-size:24rpx;padding-left:4rpx">元/{{item.danwei}}</text></view>
					</view>
				</view>
				<view class="p3">
					<view class="p3-2" v-if="params.showsales=='1' && item.sales>0"><text style="overflow:hidden">已售{{item.sales}}</text></view>
				</view>
			</view>
		</view>
	</view>
	<view class="dp-kanjia-itemlist" v-if="params.style=='list'">
		<view class="item" v-for="(item,index) in data" :key="item.id" @click="toDetail"  :data-id="item.proid" :data-type="item.type?item.type:0">
			<view class="product-pic">
				<image class="image" :src="item.pic" mode="widthFix"/>
				<image class="saleimg" :src="params.saleimg" v-if="params.saleimg!=''" mode="widthFix"/>
				<view v-if="params.showisopen == 1" class="showisopen" :style="item.is_open?'background:'+t('color1'):'background:#CCC'">
					{{item.is_open?item.opentip:item.noopentip}}
				</view>
			</view>
			<view class="product-info">
				<view class="p1" v-if="params.showname == 1 || params.showcat == 1"><text v-if="params.showcat == 1" style="color:red">{{item.catnames}}</text><text v-if="params.showname == 1">{{item.name}}</text></view>
				<view class="p2" v-if="params.showbusiness == 1">
					<text>{{item.bname}}</text>
				</view>
				<view v-if="params.showsellpoint == 1" style="line-height: 36rpx;word-break: break-all;white-space: pre-wrap;">{{item.sellpoint}}</view>
				<view v-if="params.showfuwu== 1" style="line-height: 36rpx;display: flex;overflow: hidden;flex-wrap:wrap;font-size: 26rpx;">
					<view v-for="item2 in item.fuwulist" style="margin-right: 10rpx;">
						<view class="fuwu">{{item2}}</view>
					</view>
				</view>
				<view class="p2" v-if="params.showprice != '0'">
					<view class="t1" :style="{color:t('color1')}">{{item.sell_price}}<text style="font-size:24rpx;padding-left:4rpx">元/{{item.danwei}}</text></view>
				</view>
				<view class="p3">
					<view class="p3-2" v-if="params.showsales=='1' &&  item.sales>0"><text style="overflow:hidden">已售{{item.sales}}</text></view>
				</view>
				<view v-if="params.showfwtype == 1" >
					<view v-if="item.fwtype.indexOf('1')>=0" class="showfwtype" style="top: 0;">
						到店服务
					</view>
					<view v-if="item.fwtype.indexOf('2')>=0" class="showfwtype" :style="{top:item.fwtypetop2wx+'rpx '}">
						上门服务
					</view>
					<view v-if="item.fwtype.indexOf('3')>=0" class="showfwtype" :style="{top:item.fwtypetop3wx+'rpx '}">
						到商家服务
					</view>
				</view>
			</view>
		</view>
	</view>
	<view class="dp-kanjia-itemline" v-if="params.style=='line'">
		<view class="item" v-for="(item,index) in data" :key="item.id" @click="toDetail"  :data-id="item.proid" :data-type="item.type?item.type:0" >
			<view class="product-pic">
				<image class="image" :src="item.pic" mode="widthFix"/>
				<image class="saleimg" :src="params.saleimg" v-if="params.saleimg!=''" mode="widthFix"/>
				<view v-if="params.showisopen == 1" class="showisopen" :style="item.is_open?'background:'+t('color1'):'background:#CCC'">
					{{item.is_open?item.opentip:item.noopentip}}
				</view>
				<view v-if="params.showfwtype == 1" >
					<view v-if="item.fwtype.indexOf('1')>=0" class="showfwtype" style="top: 0;">
						到店服务
					</view>
					<view v-if="item.fwtype.indexOf('2')>=0" class="showfwtype" :style="{top:item.fwtypetop2wx+'rpx '}">
						上门服务
					</view>
					<view v-if="item.fwtype.indexOf('3')>=0" class="showfwtype" :style="{top:item.fwtypetop3wx+'rpx '}">
						到商家服务
					</view>
				</view>
			</view>
			<view class="product-info">
				<view class="p1" v-if="params.showname == 1 || params.showcat == 1"><text v-if="params.showcat == 1" style="color:red">{{item.catnames}}</text><text v-if="params.showname == 1">{{item.name}}</text></view>
				<view class="p2" v-if="params.showbusiness == 1">
					<text>{{item.bname}}</text>
				</view>
				<view v-if="params.showsellpoint == 1" style="line-height: 36rpx;word-break: break-all;white-space: pre-wrap;">{{item.sellpoint}}</view>
				<view v-if="params.showfuwu== 1" style="line-height: 36rpx;display: flex;overflow: hidden;flex-wrap:wrap;font-size: 26rpx;">
					<view v-for="item2 in item.fuwulist" style="margin-right: 10rpx;">
						<view class="fuwu">{{item2}}</view>
					</view>
				</view>
				<view class="p2">
					<view class="p2-1" v-if="params.showprice != '0'">
						<view class="t1" :style="{color:t('color1')}">{{item.sell_price}}<text style="font-size:24rpx;padding-left:4rpx">元/{{item.danwei}}</text></view>
					</view>
				</view>
				<view class="p3">
					<view class="p3-2" v-if="params.showsales=='1' &&  item.sales>0"><text style="overflow:hidden">已售{{item.sales}}</text></view>
				</view>
			</view>
		</view>
	</view>
</view>
</template>
<script>
	var app = getApp();
	export default {
		props: {
			params:{},
			data:{}
		},
		methods: {
			toDetail(e){
				var id = e.currentTarget.dataset.id;
				var type = e.currentTarget.dataset.type;
				if(type == 0){
					app.goto('/activity/yuyue/product?id='+id);
				}else{
					var prodata = id;
					app.goto('/activity/yuyue/buy?prodata=' + prodata);
				}
			}
		}
	}
</script>
<style>
.dp-kanjia{height: auto; position: relative;overflow: hidden; padding: 0px; background: #fff;}
.dp-kanjia-item{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-kanjia-item .item{display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:10rpx;overflow:hidden}
.dp-kanjia-item .product-pic {width: 100%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 100%;position: relative;}
.dp-kanjia-item .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-kanjia-item .product-pic .saleimg{ position: absolute;width: 60px;height: auto; top: -3px; left:-3px;}
.dp-kanjia-item .product-info {padding:20rpx 20rpx;position: relative;}
.dp-kanjia-item .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-kanjia-item .product-info .p2{display:flex;align-items:center;overflow:hidden;padding:2px 0}
.dp-kanjia-item .product-info .p2-1{flex-grow:1;flex-shrink:1;height:40rpx;line-height:40rpx;overflow:hidden;white-space: nowrap}
.dp-kanjia-item .product-info .p2-1 .t1{font-size:36rpx;}
.dp-kanjia-item .product-info .p2-1 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-kanjia-item .product-info .p2-2{font-size:20rpx;height:40rpx;line-height:40rpx;text-align:right;padding-left:20rpx;color:#999}
.dp-kanjia-item .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx;justify-content:space-between}
.dp-kanjia-item .product-info .p3-1{height:40rpx;line-height:40rpx;border:1px #FF3143 solid;border-radius:10rpx;color:#FF3143;padding:0 24rpx;font-size:24rpx}
.dp-kanjia-item .product-info .p3-2{color:#999999;font-size:20rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;}

.dp-kanjia-itemlist{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-kanjia-itemlist .item{width:100%;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;display:flex;padding:20rpx;border-radius:10rpx}
.dp-kanjia-itemlist .product-pic {width: 30%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 30%;position: relative;border-radius:4px;}
.dp-kanjia-itemlist .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-kanjia-itemlist .product-pic .saleimg{ position: absolute;width: 120rpx;height: auto; top: -6rpx; left:-6rpx;}
.dp-kanjia-itemlist .product-info {width: 70%;padding:6rpx 10rpx 5rpx 20rpx;position: relative;}
.dp-kanjia-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-kanjia-itemlist .product-info .p2{margin-top:20rpx;height:56rpx;line-height:56rpx;overflow:hidden;}
.dp-kanjia-itemlist .product-info .p2 .t1{font-size:36rpx;}
.dp-kanjia-itemlist .product-info .p2 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-kanjia-itemlist .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx;justify-content:space-between}
.dp-kanjia-itemlist .product-info .p3-1{height:40rpx;line-height:40rpx;border:1px #FF3143 solid;border-radius:10rpx;color:#FF3143;padding:0 24rpx;font-size:24rpx}
.dp-kanjia-itemlist .product-info .p3-2{color:#999999;font-size:20rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;}

.dp-kanjia-itemline{width:100%;display:flex;overflow-x:scroll;overflow-y:hidden}
.dp-kanjia-itemline .item{width: 220rpx;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:10rpx;margin-right:4px}
.dp-kanjia-itemline .product-pic {width:220rpx;height:0;overflow:hidden;background: #ffffff;padding-bottom: 100%;position: relative;}
.dp-kanjia-itemline .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-kanjia-itemline .product-pic .saleimg{ position: absolute;width: 60px;height: auto; top: -3px; left:-3px;}
.dp-kanjia-itemline .product-info {padding:20rpx 20rpx;position: relative;}
.dp-kanjia-itemline .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-kanjia-itemline .product-info .p2{display:flex;align-items:center;overflow:hidden;padding:2px 0}
.dp-kanjia-itemline .product-info .p2-1{flex-grow:1;flex-shrink:1;height:40rpx;line-height:40rpx;overflow:hidden;white-space: nowrap}
.dp-kanjia-itemline .product-info .p2-1 .t1{font-size:36rpx;}
.dp-kanjia-itemline .product-info .p2-1 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-kanjia-itemline .product-info .p2-2{font-size:20rpx;height:40rpx;line-height:40rpx;text-align:right;padding-left:20rpx;color:#999}
.dp-kanjia-itemline .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx;justify-content:space-between}
.dp-kanjia-itemline .product-info .p3-1{height:40rpx;line-height:40rpx;border:1px #FF3143 solid;border-radius:10rpx;color:#FF3143;padding:0 24rpx;font-size:24rpx}
.dp-kanjia-itemline .product-info .p3-2{color:#999999;font-size:20rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;}

.showisopen{position: absolute;width: 100%;bottom: 0; left:0;color: #fff;text-align: center;line-height: 40rpx;font-size: 24rpx;}
.showfwtype{position: absolute;width: auto;right:0px;background: #EC9100;color: #fff;text-align: center;line-height: 36rpx;font-size: 26rpx;padding:0 20rpx;border-radius: 4rpx;margin-bottom: 10rpx;opacity: 0.7;}
.fuwu:before {
	content: "";
    display: inline-block;
    vertical-align: middle;
    margin-top: -6rpx;
    margin-right: 6rpx;
    width:24rpx;
    height: 24rpx;
    background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYBAMAAAASWSDLAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAAwUExURUdwTOU5O+Q5POU5POQ4O+U4PN80P+M4O+Q4O+Q4POQ5POQ4OuQ4O+Q4O+I4PuQ5PJxkAycAAAAPdFJOUwAf+VSoeAvzws7ka7miLboUzckAAADJSURBVBjTY2BgYGCMWVR5VIABDBid/gPBFwjP/JOzQKKtfjGIzf3fEUSJ/N8AJO21Iao3fQbqqA+AcLi/CzCwfGGAAn8HBnlFMIttBoP4R4b4C2BOzk8G3q8M5w3AnPsLGZj/MKwHW8b6/QED4y8G/QQQx14ZSHwCcWYkMOtvAHOAyvqnPf8KcuMvkAGZP9eDjAQaEO/AwDb/D0gj0GiQpRnTQIYIfUR1DopDGexVIZygz8ieC4B6WyzRBOJtBkZ/pAABBZUWOKgAispF5e7ibycAAAAASUVORK5CYII=) no-repeat;
    background-size: 24rpx auto;
    background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYBAMAAAASWSDLAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAAwUExURUdwTOU5O+Q5POU5POQ4O+U4PN80P+M4O+Q4O+Q4POQ5POQ4OuQ4O+Q4O+I4PuQ5PJxkAycAAAAPdFJOUwAf+VSoeAvzws7ka7miLboUzckAAADJSURBVBjTY2BgYGCMWVR5VIABDBid/gPBFwjP/JOzQKKtfjGIzf3fEUSJ/N8AJO21Iao3fQbqqA+AcLi/CzCwfGGAAn8HBnlFMIttBoP4R4b4C2BOzk8G3q8M5w3AnPsLGZj/MKwHW8b6/QED4y8G/QQQx14ZSHwCcWYkMOtvAHOAyvqnPf8KcuMvkAGZP9eDjAQaEO/AwDb/D0gj0GiQpRnTQIYIfUR1DopDGexVIZygz8ieC4B6WyzRBOJtBkZ/pAABBZUWOKgAispF5e7ibycAAAAASUVORK5CYII=);
    background-repeat-x: no-repeat;
    background-repeat-y: no-repeat;
}
</style>