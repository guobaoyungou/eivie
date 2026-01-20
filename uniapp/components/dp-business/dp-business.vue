<template>
<view class="dp-business" :style="{
	color:params.color,
	backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+(params.margin_x*2.2)+'rpx',
	padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx',
	fontSize:(params.fontsize*2)+'rpx'
}">
	<view class="busbox" v-for="(item,index) in data" :key="item.id" v-if="!params.showstyle || params.showstyle =='1'">
		<view class="businfo" @click="gotourl" :data-bid="item.bid" :data-tourl="item.tourl" :data-key="index">
			<view class="f1"><image class="image" lazy-load="true" lazy-load-margin="0" :src="item.logo"/></view>
			<view class="f2">
				<view class="title">{{item.name}}</view>
				<view class="score" v-if="params.showpingfen!=='0'"><image class="image" :src="pre_url+'/static/img/star'+item.commentscore+'.png'"/>{{item.comment_score}}分</view>
        <view class="address" v-if="params.showcategroy=='1'">{{item.catname}}</view>
				<view class="sales" v-if="params.showturnover!=='0' && item.turnover_show==1"><text>营业额：</text>{{item.turnover}}</view>
				<view class="sales" v-if="params.showsales!=='0'"><text>销量：</text>{{item.sales}}</view>
				<view class="address" v-if="params.showjianjie=='1'"><text :decode="true">{{item.content}}</text></view>
				<view class="address flex"><view class="flex1"><text v-if="params.showaddress!=='0'">{{item.address}}</text></view><view v-if="params.showdistance" :style="{color:t('color1')}">{{item.juli}}</view></view>
				<view class="address flex" v-if="params.showmaidan=='1'">
					<view class="btn" @tap.stop="showMap" :data-name="item.name" :data-address="item.address" :data-latitude="item.latitude" :data-longitude="item.longitude" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}">导航</view>
					<view class="btn" @tap.stop="goto" :data-url="'/pages/maidan/pay?bid='+item.bid" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}">买单</view>
					<view class="btn" v-if="item.tel" @tap.stop="goto" :data-url="'tel::'+item.tel" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}">咨询电话</view>
				</view>
				<view style="font-size: 24rpx;" :style="{color:t('color1')}" v-if="item.activity_time && item.activity_time_status==1" >
					活动时间：<text class="x1">{{item.activity_time}}</text>
				</view>
				<view class="ratio-list flex">
					<view class="ratio-label flex-y-center" v-if="item.rate_back && item.rate_back > 0" :style="{color:t('color1'),borderColor:t('color1')}">
						<view class="label" :style="{backgroundColor:t('color1')}">返</view>
						<view class="t1">{{item.rate_back}}%</view>
					</view>
					<view class="ratio-label flex-y-center" v-if="item.scoredkmaxpercent && item.scoredkmaxpercent > 0"  :style="{color:t('color1'),borderColor:t('color1')}">
						<view class="label" :style="{backgroundColor:t('color1')}">积</view>
						<view class="t1">{{item.scoredkmaxpercent}}%</view>
					</view>
					<view class="ratio-label flex-y-center" v-if="!isNull(item.activecoin_bili) && item.activecoin_bili > 0"  :style="{color:t('color1'),borderColor:t('color1'), width: '220rpx'}">
						<view class="label" :style="{backgroundColor:t('color1'), width: '120rpx'}">{{t('激活币')}}</view>
						<view class="t1">{{item.activecoin_bili}}%</view>
					</view>
					<view class="ratio-label flex-y-center" v-if="!isNull(item.newscore_ratio) && item.newscore_ratio > 0"  :style="{color:t('color1'),borderColor:t('color1'), width: '220rpx'}">
						<view class="label" :style="{backgroundColor:t('color1'), width: '120rpx'}">{{t('新积分')}}</view>
						<view class="t1">{{item.newscore_ratio}}%</view>
					</view>
					<view class="ratio-label flex-y-center" v-if="!isNull(item.newscore_pack_ratio) && item.newscore_pack_ratio > 0"  :style="{color:t('color1'),borderColor:t('color1'), width: '220rpx'}">
						<view class="label" :style="{backgroundColor:t('color1'), width: '120rpx'}">{{t('加速包')}}</view>
						<view class="t1">{{item.newscore_pack_ratio}}%</view>
					</view>
					<view class="ratio-label flex-y-center" v-if="!isNull(item.subsidy_ratio) && item.subsidy_ratio > 0"  :style="{color:t('color1'),borderColor:t('color1'), width: '220rpx'}">
						<view class="label" :style="{backgroundColor:t('color1'), width: '120rpx'}">{{t('让利')}}</view>
						<view class="t1">{{item.subsidy_ratio}}%</view>
					</view>
				</view>
				<view class="flex queue-free" v-if="params.showqueuefreeratio =='1' && item.queue_free_set ==1 && item.queue_free_rate_back >0">
					<view class="queue-free-ratio flex" :style="{color:t('color1'),borderColor:t('color1')}">
						<view class="icon-div" :style="{backgroundColor:t('color1')}">
							<image class="icon" :src="pre_url+'/static/img/qianbao.png'"></image>
						</view>
						{{params.zuigaopaiduibutie ||'最高排队补贴'}} {{item.queue_free_rate_back}}%
					</view>
				</view>
        <block v-if="params.showdedamount =='1'">
          <view class="flex queue-free" v-if="item.dedamount_maxdkpercent && item.dedamount_maxdkpercent >0" style="justify-content: flex-start;">
            <view class="queue-free-ratio flex" :style="{color:t('color1'),borderColor:t('color1')}">
              <view class="icon-div" :style="{backgroundColor:t('color1')}">
                <image class="icon" :src="pre_url+'/static/img/qianbao.png'"></image>
              </view>
              抵扣 {{item.dedamount_maxdkpercent}}%
            </view>
          </view>
        </block>
				<view class="instore" v-if="params.showinstore=='1'" @tap.stop="gotourl" :data-bid="item.bid" :data-tourl="item.tourl"  :style="{background:'rgba('+t('color1rgb')+')'}">
						<text >进店</text>
				</view>
			</view>
		</view>
		<!-- 店铺商品 -->
		<view class="buspro" v-if="params.showproduct == 1">
			<view class="item" v-for="(item2,index2) in item.prolist" :style="'width:23%;margin-right:'+(index2%4!=3?'2%':0)" :key="item2.id" @click="goto" :data-url="item2.module == 'yuyue' ? '/activity/yuyue/product?id='+item2.id : '/pages/shop/product?id='+item2.id">
				<view class="product-pic">
					<image class="image" :src="item2.pic" mode="widthFix"/>
				</view>
				<view class="product-info">
					<view class="p1">{{item2.name}}</view>
					<view class="p2">
						<view class="p2-1">
							<text class="t1" :style="{color:t('color1')}"><text style="font-size:24rpx">￥</text>{{item2.sell_price}}
              <text v-if="item2.price_show && item2.price_show_text" style="margin: 0 15rpx;font-size: 24rpx;font-weight: 400;">{{item2.price_show_text}}</text>
              <text v-if="item2.module == 'yuyue'" style="font-size:24rpx">/{{item2.danwei}}</text></text>
						</view>
					</view>
          <!-- 商品处显示会员价 -->
          <view v-if="item2.price_show && item2.price_show == 1" style="line-height: 44rpx;">
            <text style="font-size:24rpx">￥{{item2.sell_putongprice}}</text>
          </view>
          <view v-if="item2.priceshows && item2.priceshows.length>0">
            <view v-for="(item3,index3) in item2.priceshows" style="line-height: 44rpx;">
              <text style="font-size:24rpx">￥{{item3.sell_price}}</text>
              <text style="margin-left: 15rpx;font-size: 22rpx;font-weight: 400;">{{item3.price_show_text}}</text>
            </view>
          </view>
					<view v-if="item2.module == 'yuyue'" class="p4" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" @click.stop="goto" :data-url="'/activity/yuyue/product?id='+item2.id"><text class="iconfont icon_gouwuche"></text></view>
					<view class="p4" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" v-if="item2.module != 'yuyue' && !item2.price_type" @click.stop="buydialogChange" :data-proid="item2.id"><text class="iconfont icon_gouwuche"></text></view>
				</view>
			</view>
		</view>
		<!-- 店铺商品 -->
		<!-- 秒杀商品 -->
		<!-- <view class="buspro" v-if="params.showseckill == 1 && item.seckillprolist.length>0">
			<view class="buspro-title">限时秒杀</view>
			<scroll-view scroll-x="true" style="width: 100%;white-space: nowrap;">
				<view class="item" v-for="(item2,index2) in item.seckillprolist" :style="'width:23%;margin-right:'+(index2%4!=3?'2%':0)" :key="item2.id" @click="goto" :data-url="'/activity/seckill/product?id='+item2.id">
					<view class="product-pic">
						<image class="image" :src="item2.pic" mode="widthFix"/>
					</view>
					<view class="product-info">
						<view class="p1">{{item2.name}}</view>
						<view class="p2">
							<view class="p2-1">
								<text class="t1" :style="{color:t('color1')}"><text style="font-size:24rpx">￥</text>{{item2.sell_price}}</text>
								<text class="t2" v-if="item2.market_price && item2.market_price>item2.sell_price"><text style="font-size:24rpx">￥</text>{{item2.market_price}}</text>
							</view>
						</view>
					</view>
				</view>
			</scroll-view>
		</view> -->
		<!-- 秒杀商品 -->
		<view class="free_product" v-if="params.showbusinessmiandan == 1 && item.miandanset_status == 1 && item.miandanprolist.length>0 " >
			<view class="product" v-for="(item3,index3) in item.miandanprolist" @click="gotourl" :data-bid="item.bid" :data-tourl="item.tourl">
				<view class="item">
					<view class="hui">免</view>
					<view class="price">￥{{item3.sell_price}}</view>
					<view class="proname">{{item3.name}}</view>
				</view>
			</view>
		</view>
		
		<view class="buspro" v-if="params.showscoreshop == 1 && item.scoreshopprolist.length>0">
			<scroll-view scroll-x="true" style="width: 100%;white-space: nowrap;">
				<view class="item" v-for="(item2,index2) in item.scoreshopprolist" :style="'width:23%;margin-right:'+(index2%4!=3?'2%':0)" :key="item2.id" @click="goto" :data-url="'/activity/scoreshop/product?id='+item2.id">
					<view class="product-pic">
						<image class="image" :src="item2.pic" mode="widthFix"/>
					</view>
					<view class="product-info">
						<view class="p1">{{item2.name}}</view>
						<view class="p2">
							<view class="p2-1">
								<text class="t1" :style="{color:t('color1')}">{{item2.score_price}}{{t('积分')}}<text v-if="item2.money_price>0">+{{item2.money_price}}元</text></text>
							</view>
						</view>
					</view>
				</view>
			</scroll-view>
		</view>

		<view class="cuxiaopro" :style="{background:'linear-gradient(180deg,rgba('+t('color1rgb')+',0.2),rgba('+t('color1rgb')+',0) 60%)'}" v-if="(params.showseckill == 1 || params.showtuangou == 1 || params.showkanjia == 1 || params.showcollage == 1 || params.showluckycollage == 1) && item.cuxiaoprolist.length>0">
			<view class="item" v-for="(item2,index2) in item.cuxiaoprolist"  :key="item2.id" @click="goto" :data-url="'/activity/'+item2.product_type+'/product?id='+item2.id">
				<view class="product-pic">
					<image class="image" :src="item2.pic" mode="widthFix"/>
				</view>
				<view class="product-info">
					<view class="p1">{{item2.name}}</view>
					<view class="p2">
						<view class="p2-1">
							<text class="t1" :style="{color:t('color1')}">
								<text v-if="item2.product_type=='tuangou'" class="t1-1">低至</text>
								<text>￥</text>{{item2.sell_price}}
							</text>
							<text class="t2" v-if="(item2.product_type=='seckill') && item2.market_price && item2.market_price>item2.sell_price"><text style="font-size:24rpx">￥</text>{{item2.market_price}}</text>
						</view>
					</view>
					<view class="p3">
						<view class="p3-1" :style="{background:'rgba('+t('color1rgb')+',0.12)',color:t('color1')}">
							<text v-if="item2.product_type=='seckill'">秒杀</text>
							<text v-if="item2.product_type=='kanjia'">砍价</text>
							<text v-if="item2.product_type=='tuangou'">团购</text>
							<text v-if="item2.product_type=='collage'">拼团</text>
							<text v-if="item2.product_type=='luckycollage'">{{t('幸运拼团')}}</text>
						</view>
						<text class="p3-2" v-if="item2.product_type=='seckill'">已抢购{{item2.sales}}件</text>
						<text class="p3-2" v-if="item2.product_type=='kanjia'">已砍走{{item2.sales}}件</text>
						<text class="p3-2" v-if="item2.product_type=='collage' || item2.product_type=='luckycollage'">{{item2.teamnum}}人拼</text>
					</view>
				</view>
			</view>
		</view>
	</view>
	<view class="busbox2" v-if="params.showstyle && params.showstyle =='2'" v-for="(item,index) in data" :key="item.id" @tap="goto"  :data-url="'/pagesA/business/businessindex?id='+item.bid" >
		<view class="new_blist" >
			<view class="f1"><image class="image" :src="item.logo" mode="aspectFill"  /></view>
			<view class="f2">
				<view class="t1">{{item.name}}</view>
				<view class="t2"><image class="image" :src="pre_url+'/static/img/telphone.png'" mode="widthFix"/>{{item.tel}}</view>
				<view class="t2" v-if="params.showaddress ==1"><image class="image" :src="pre_url+'/static/img/position.png'" mode="widthFix"/>
					<text class="text">{{item.address}}</text>
				</view>
			</view>
			<view class="f3">
				<image class="image" :src="pre_url+'/static/img/calltel.png'" @tap.stop="callphone" :data-tel="item.tel" mode="widthFix" />
				<view  v-if="params.showdistance ==1" style="color: #EC4149;font-size: 26rpx;margin-top: 10rpx;">{{item.juli}}</view>
			</view>
		</view>
		
	</view>
	<buydialog v-if="buydialogShow" :proid="proid" @addcart="addcart" @buydialogChange="buydialogChange" :menuindex="menuindex"></buydialog>
</view>
</template>
<script>
	var app = getApp();
	export default {
		data(){
			return {
				pre_url:getApp().globalData.pre_url,
				buydialogShow:false,
				proid:''
			}
		},
		props: {
			menuindex:{default:-1},
			params:{},
			data:{}
		},
		methods: {
			buydialogChange: function (e) {
				if(!this.buydialogShow){
					this.proid = e.currentTarget.dataset.proid
				}
				this.buydialogShow = !this.buydialogShow;
				console.log(this.buydialogShow);
			},
			addcart:function(){
				this.$emit('addcart');
			},
			gotourl:function(e){
				var bid = e.currentTarget.dataset.bid;
				var tourl = e.currentTarget.dataset.tourl;
				if(tourl){
					tourl = tourl;
				}else if(this.params.intotype==1){
					tourl = '/restaurant/takeaway/index?bid='+bid
				}else if(this.params.intotype==2){
					tourl = '/restaurant/shop/index?bid='+bid;
					if(this.params.fzcode){
						tourl +='&fzcode='+this.params.fzcode;
					}
					console.log(this.params,'this.params');
					if(this.params.tablebid){
						tourl +='&tableId='+this.params.tablebid;
					}
					var key = e.currentTarget.dataset.key;
					var mdid = this.data[key].mdid;
					if(!this.params.tablebid && mdid){
						tourl +='&tableId=0&mdid='+mdid;
					}
				}else{
					tourl ='/pagesExt/business/index?id='+bid;
				}
				app.goto(tourl);
			},
			callphone:function(e) {
				var phone = e.currentTarget.dataset.tel;
				uni.makePhoneCall({
					phoneNumber: phone,
					fail: function () {
					}
				});
			},
		}
	}
</script>
<style>
.dp-business{height: auto; position: relative;}
.dp-business .busbox{background: #fff;padding:16rpx;overflow: hidden;margin-bottom:16rpx;width:100%}
.dp-business .businfo{display:flex;width:100%}
.dp-business .businfo .f1{width:200rpx;height:200rpx; margin-right:20rpx;flex-shrink:0}
.dp-business .businfo .f1 .image{ width: 100%;height:100%;border-radius:20rpx;object-fit: cover;}
.dp-business .businfo .f2{flex:1; position: relative;}
.dp-business .businfo .f2 .title{font-size:28rpx;font-weight:bold; color: #222;line-height:46rpx;margin-bottom:3px;}
.dp-business .businfo .f2 .score{font-size:24rpx;color:#f99716;}
.dp-business .businfo .f2 .score .image{width:140rpx; height:50rpx; vertical-align: middle;margin-bottom:3px; margin-right:3px;}
.dp-business .businfo .f2 .sales{font-size:24rpx; color:#31C88E;margin-bottom:3px;}
.dp-business .businfo .f2 .address{color:#999;font-size:24rpx;line-height:40rpx;margin-bottom:3px;}
.dp-business .businfo .btn {font-size:28rpx;line-height:56rpx;border-radius: 28rpx;padding: 0 30rpx;margin-right: 30rpx;}

.dp-business .buspro{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap;margin-top:32rpx}
.dp-business .buspro .item{display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:10rpx;overflow:hidden;}
.dp-business .buspro .product-pic {width: 100%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 100%;position: relative;}
.dp-business .buspro .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-business .buspro .product-info {padding:20rpx 0;position: relative;}
.dp-business .buspro .product-info .p1 {color:#323232;font-weight:bold;font-size:24rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;height:36rpx}
.dp-business .buspro .product-info .p2{display:flex;align-items:center;overflow:hidden;padding:2px 0}
.dp-business .buspro .product-info .p2-1{flex-grow:1;flex-shrink:1;height:40rpx;line-height:40rpx;overflow:hidden;white-space: nowrap}
.dp-business .buspro .product-info .p2-1 .t1{font-size:28rpx;}
.dp-business .buspro .product-info .p2-1 .t2 {margin-left:10rpx;font-size:22rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-business .buspro .product-info .p2-1 .t3 {margin-left:10rpx;font-size:22rpx;color: #999;}
.dp-business .buspro .product-info .p2-2{font-size:20rpx;height:40rpx;line-height:40rpx;text-align:right;padding-left:20rpx;color:#999}
.dp-business .buspro .product-info .p3{color:#999999;font-size:20rpx;margin-top:10rpx}
.dp-business .buspro .product-info .p4{width:48rpx;height:48rpx;border-radius:50%;position:absolute;display:relative;bottom:20rpx;right:0;text-align:center;}
.dp-business .buspro .product-info .p4 .icon_gouwuche{font-size:28rpx;height:48rpx;line-height:48rpx}
.dp-business .buspro .product-info .p4 .img{width:100%;height:100%;}

.dp-business .cuxiaopro{width: 100%;padding: 10rpx  20rpx;border-radius: 10rpx;margin-top: 20rpx;}
.dp-business .buspro-title{line-height: 60rpx;font-size:16px;font-weight: bold}
.dp-business .cuxiaopro .item{display: flex;align-items: center;margin-bottom: 20rpx;overflow: hidden;border-bottom: 1rpx solid #f2f2f2;}
.dp-business .cuxiaopro .product-pic{width: 140rpx;height: 140rpx;flex-shrink: 0;overflow: hidden;border-radius: 6rpx;}
.dp-business .cuxiaopro .product-pic .image{width: 100%;height:auto;object-fit: cover;border-radius: 6rpx;}
.dp-business .cuxiaopro .product-info {padding:10rpx 0;flex: 1;margin-left: 10px;align-self: flex-start;}
.dp-business .cuxiaopro .product-info .p1 {color:#323232;font-weight:bold;font-size:12rpx;line-height:18px;margin-bottom:5px;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;height:18px}
.dp-business .cuxiaopro .product-info .p2{display:flex;align-items:center;overflow:hidden;padding:2px 0}
.dp-business .cuxiaopro .product-info .p2-1{flex-grow:1;flex-shrink:1;height:20px;line-height:20px;overflow:hidden;white-space: nowrap}
.dp-business .cuxiaopro .product-info .p2-1 .t1{font-size:30rpx;font-weight: bold;}
.dp-business .cuxiaopro .product-info .p2-1 .t1-1{font-size:24rpx;font-weight:400;}
.dp-business .cuxiaopro .product-info .p2-1 .t2 {margin-left:5px;font-size:11px;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-business .cuxiaopro .product-info .p2-2{font-size:10px;height:20px;line-height:20px;text-align:right;padding-left:10px;color:#999}
.dp-business .cuxiaopro .product-info .p3{font-size:10px;margin-top:5px;display: flex;justify-content: space-between;}
.dp-business .cuxiaopro .product-info .p3-1{border:0 #FF3143 solid;border-radius:10rpx;color:#FF3143;padding:4rpx 10rpx;font-size:24rpx;min-width: 80rpx;flex-shrink: 0;text-align: center;}
.dp-business .cuxiaopro .product-info .p3-2{color:#999999;font-size:20rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;}

.dp-business .busbox2{background: #fff;padding: 8px;overflow: hidden;width: 100%;border-bottom: 2rpx solid #f5f5f5;}
.dp-business .busbox2:last-child{border: none;}
.dp-business .new_blist{display:flex;width:100%;align-items: center;}
.dp-business .new_blist .f1{width:130rpx;height:130rpx; margin-right: 30rpx;flex-shrink:0}
.dp-business .new_blist .f1 .image{ width: 100%;height:100%;border-radius:50%;object-fit: cover;}
.dp-business .new_blist .f2{flex:1}
.dp-business .new_blist .f2 .t1{font-size:28rpx; color: #222;font-weight:bold;line-height:60rpx}
.dp-business .new_blist .f2 .t2{font-size:28rpx; color: #222;line-height:40rpx;align-items: center;margin: 10rpx 0;display: flex}
.dp-business .new_blist .f2 .t2 .image{width: 32rpx;  height:32rpx ; line-height: 60rpx;margin-right: 20rpx;}
.dp-business .new_blist .f2 .t2 .text{width: 350rpx;overflow: hidden;text-overflow: ellipsis; white-space: nowrap}
.dp-business .new_blist .f3{display: flex;align-items: center;flex-direction: column; justify-content: center}
.dp-business .new_blist .f3 .image{width: 80rpx;height: 80rpx}

.dp-business .instore{ position: absolute; top:10rpx;right:15rpx; color: #fff; padding:6rpx 10rpx; border-radius: 5rpx;}

.free_product .product{width:100%;overflow:hidden;margin-right:24rpx;margin: 10rpx 0;}
.free_product .product .item{ display: flex;align-items: center; padding:4rpx 0}
.free_product .product .item .hui{ background: #FAF0ED; color: #E16954;font-weight: bold;padding:2rpx 10rpx; border-radius:10rpx;height: 36rpx;font-size: 24rpx;}
.free_product .product .item .price{font-size:28rpx;color:#FC5648;margin-right: 20rpx;}

.free_product .f1{width:108rpx;height:108rpx;border-radius:8rpx;background:#f6f6f6}
.free_product .product .desc{ display: inline-flex; border:1rpx solid #E8A7AE; color:#DF474E;padding: 10rpx 0 ;padding:5rpx 20rpx; border-radius:10rpx;margin-top: 10rpx;font-size: 24rpx;}
/* 最高排队免单比例 */
.queue-free{justify-content: flex-end}
.queue-free-ratio{line-height: 50rpx;text-align: center;border-radius: 10rpx;border:4rpx solid #FC5D2B;color: #FC5D2B;font-size: 28rpx;padding-right: 10rpx;}
.queue-free-ratio .icon-div{height: 50rpx;width: 50rpx;display: flex;align-items: center;justify-content: center;margin-right: 10rpx}
.queue-free-ratio .icon-div .icon{width: 40rpx;height: 40rpx}
/*返利 和 积分显示*/
.ratio-list{padding-top: 10rpx;}
.ratio-label{height: 40rpx;border-radius: 10rpx;width:160rpx;border: 2rpx solid;margin-right:20rpx;}
.ratio-label .label{width: 55rpx ;height: 40rpx;line-height: 40rpx;border-radius: 10rpx 20rpx 5rpx 10rpx;color: #fff;text-align: center;}
.ratio-label .t1{text-align: center;width: 65%;font-size: 28rpx;}
</style>