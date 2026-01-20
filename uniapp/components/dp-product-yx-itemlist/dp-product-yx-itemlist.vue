<template>
<view style="width:100%">
	<view class="dp-product-itemlist">
		<view class="dp-product-item" v-for="(item,index) in data" :key="item.id">
			<view class="item" :style="item.product_promotion_tag ? 'align-items: normal; background-color: ' + probgcolor + ';' : 'background-color: ' + probgcolor + ';'" @click="toDetail(index)">
				<view class="product-pic" :style="item.product_promotion_tag ? 'width:40%;height:auto;' : ''">
					<image class="image" :src="item.pic" mode="widthFix"/>
					<image class="saleimg" :src="saleimg" v-if="saleimg!=''" mode="widthFix"/>
				</view>
				<view class="product-info" :style="item.product_promotion_tag ? 'width:60%;padding: 6rpx 0 5rpx 20rpx' : ''">
					<view class="p1" v-if="showname == 1">{{item.name}}</view>
					<view class="p5" :style="{color:t('color2')}" v-if="item.sellpoint"><text>{{item.sellpoint}}</text></view>
          <view :style="{color:t('color1')}" v-if="item.showgivescore">
            <text style="font-size: 24rpx;">赠送{{item.showgivescore}}{{t('积分')}}</text>
          </view>
					<view :style="{color:item.cost_color?item.cost_color:'#999',fontSize:'36rpx'}" v-if="item.show_cost && item.price_type != 1"><text style="font-size: 24rpx;">{{item.cost_tag}}</text>{{item.cost_price}}</view>
					<view class="p2" v-if="(!item.show_sellprice || (item.show_sellprice && item.show_sellprice==true)) && ( item.price_type != 1 || item.sell_price > 0) && showprice != '0'">
						<!-- 默认价格样式 -->
						<view v-if="item.price_show_type=='0' || !item.price_show_type " class="flex-bt flex-y-center">
							<text class="t1" :style="{color:item.price_color?item.price_color:t('color1')}">
								<block >
									<text style="font-size:24rpx;padding-right:1px">{{item.price_tag?item.price_tag:'￥'}}</text>{{item.sell_price}}
									<text v-if="item.price_show && item.price_show_text" style="margin: 0 15rpx;font-size: 24rpx;font-weight: 400;">{{item.price_show_text}}</text>
									<text style="font-size:24rpx" v-if="item.product_unit">/{{item.product_unit}}</text>

								</block>
							</text>
							
							<view v-if="!item.product_promotion_tag">
								<view style="color: #999;font-size: 20rpx;" v-if="item.yingxiao_tag && item.yingxiao_tag.id">{{item.yingxiao_tag.sales_text}}</view>
								<view style="color: #999;font-size: 20rpx;" v-else>已售{{item.sales}}人</view>
							</view>
						</view>
						<!-- 默认价格样式 end -->
						<text class="t2" v-if="item.show_sellprice && item.market_price*1 > item.sell_price*1 && showprice == '1'">￥{{item.market_price}}</text>
						<text class="t3" v-if="item.juli">{{item.juli}}</text>
					</view>
				
		  
					<!-- 佣金横排 -->
					<block v-if="item.product_promotion_tag">
						<view :style="'color:'+t('color1')+';'" class="flex-y-center product_promotion_tag" v-if="item.tag_commission_rate || item.tag_commission">
							<view class="pro-label flex-col flex-y-center" style="width: 33%;" v-if="item.tag_commission_rate">
								<view style="color: #999">{{t('佣金')}}率</view>
								<view style="font-weight: bold;">{{item.tag_commission_rate}}</view>
							</view>
							<view v-if="item.tag_commission_rate" class="separator"></view>
							<view class="pro-label flex-col flex-y-center" style="width: 33%;" v-if="item.tag_commission">
								<view style="color: #999">预估{{t('佣金')}}</view>
								<view style="font-weight: bold;">￥{{item.tag_commission}}</view>
							</view>
							<view v-if="item.tag_commission_rate || item.tag_commission" class="separator"></view>
							<view class="pro-label flex-col flex-y-center" style="width: 33%;" v-if="item.tag_upgrade_money">
								<view style="color: #999">{{t('升级多赚')}}</view>
								<view style="font-weight: bold;">￥{{item.tag_upgrade_money}}</view>
							</view>
						</view>
						<view class="p3" style="justify-content: flex-end;">
							<view class="right tag-share">
								<image :src="`${pre_url}/static/img/share_white.png`" mode="" style="width: 30rpx;height: 30rpx;margin-right: 10rpx;"></image>
								<text v-if="item.tag_commission">赚￥{{item.tag_commission}}</text>
								<text v-else>立即分享</text>
							</view>
						</view>
					</block>
					<!-- END 佣金横排 -->
					
					<!-- 横排营销 -->
					<block v-else>
						<!-- 存在营销标签时，否则走默认 -->
						<view class="p3" v-if="item.yingxiao_tag && item.yingxiao_tag.id">
							<view class="left">{{item.yingxiao_tag.title}}</view>
							<view class="right">
								<view class="yuandian"></view>
								{{item.yingxiao_tag.btn_text}}
							</view>
						</view>
						<view class="p3" v-else>
							<view class="left">限量低价，先到先得</view>
							<view class="right">
								<view class="yuandian"></view>
								立即抢购
							</view>
						</view>
					</block>
					<!-- END 横排营销 -->
				</view> 
			</view>
		</view>
	</view>

	<block >
		<buydialog v-if="buydialogShow" :proid="proid" @buydialogChange="buydialogChange" :menuindex="menuindex"></buydialog>
	</block>

</view>
</template>
<script>
	var app = getApp();
	export default {
		data(){
			return {
				buydialogShow:false,
				proid:0,
        showLinkStatus:false,
        lx_bname:'',
        lx_name:'',
        lx_bid:'',
        lx_tel:'',
				productType:'',
				ggNum:'',
				pre_url: app.globalData.pre_url,
			}
		},
		props: {
			menuindex:{default:-1},
			saleimg:{default:''},
			showname:{default:1},
			namecolor:{default:'#333'},
			showprice:{default:'1'},
			showcost:{default:'0'},
			showsales:{default:'1'},
			showstock:{default:'0'},
			showcart:{default:'1'},
			cartimg:{default:'/static/imgsrc/cart.svg'},
			data:{},
			idfield:{default:'id'},
			probgcolor:{default:'#fff'},
			showcommission: {
				default: '0'
			},
			showbname: {
				default: '0'
			},
			showbdistance: {
				default: '0'
			},
		},
		methods: {
			buydialogChange: function (e) {
				if(!this.buydialogShow){
					this.proid = e.currentTarget.dataset.proid
					this.data.forEach(item => {
						if(item[this.idfield] == this.proid){
							this.productType = item.product_type;
							if(item.product_type == 4){
								if(item.gg_num){
									this.ggNum = item.gg_num;
								}else if(item.guigedata){
									this.ggNum = Object.keys(JSON.parse(item.guigedata)).length;
								}
							}
						}
					})
				}
				this.buydialogShow = !this.buydialogShow;
			},
            showLinkChange: function (e) {
                var that = this;
            	that.showLinkStatus = !that.showLinkStatus;
                that.lx_name = e.currentTarget.dataset.lx_name;
                that.lx_bid = e.currentTarget.dataset.lx_bid;
                that.lx_bname = e.currentTarget.dataset.lx_bname;
                that.lx_tel = e.currentTarget.dataset.lx_tel;
            },
			toDetail:function(key){
				var that = this;
				var item = that.data[key];
				var id = item[that.idfield];
				var url = '/pages/shop/product?id='+id;//默认链接
				//来自商品柜
				if(item.device_id){
					var dgid = item.id;
					var deviceno = item.device_no;
					var lane = item.goods_lane;
					var prodata  = id+','+item.ggid+','+item.stock;
					var devicedata = deviceno+','+lane;
					url = url+'&dgprodata='+prodata+'&devicedata='+devicedata;
				}
				app.goto(url);
			}
		}
	}
</script>
<style>
.dp-product-item{width: 100%;padding:10rpx 10rpx 10rpx 10rpx;}
.dp-product-itemlist{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-product-itemlist .item{width:100%;display: inline-block;position: relative;background: #fff;display:flex;border-radius:10rpx;align-items: center;overflow: hidden;}
.dp-product-itemlist .product-pic {width: 30%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 30%;position: relative;border-radius:4px;}
.dp-product-itemlist .product-pic .image{position:absolute;top:0;left:0;width: 90%;height:auto;margin: 5%;border-radius: 10rpx;}
.dp-product-itemlist .product-pic .saleimg{ position: absolute;width: 120rpx;height: auto; top: -6rpx; left:-6rpx;}
.dp-product-itemlist .product-info {width: 70%;padding:6rpx 26rpx 5rpx 20rpx;position: relative;}
.dp-product-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:26rpx;line-height:36rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-product-itemlist .product-info .p2{margin-top:5rpx;overflow:hidden;}
.dp-product-itemlist .product-info .p2 .t1{font-size:36rpx;}
.dp-product-itemlist .product-info .p2 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-product-itemlist .product-info .p2 .t3 {margin-left:10rpx;font-size:24rpx;color: #888;}
.dp-product-itemlist .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:5rpx}
.dp-product-itemlist .product-info .p3-1{font-size:20rpx;height:30rpx;line-height:30rpx;text-align:right;color:#999}
.dp-product-itemlist .product-info .p4{width:48rpx;height:48rpx;border-radius:50%;position:absolute;display:relative;bottom:6rpx;right:4rpx;text-align:center;}
.dp-product-itemlist .product-info .p4 .icon_gouwuche{font-size:28rpx;height:48rpx;line-height:48rpx}
.dp-product-itemlist .product-info .p4 .img{width:100%;height:100%}
.dp-product-itemlist .product-info .p2 .t1-m {font-size: 32rpx;padding-left: 8rpx;}
.dp-product-itemlist .product-info .p5{font-size:24rpx;font-weight: bold;margin: 6rpx 0;}
.dp-product-itemlist .product-info .p6{font-size:24rpx;display: flex;flex-wrap: wrap;margin-top: 6rpx;}
.dp-product-itemlist .product-info .p6-m{text-align: center;padding:6rpx 10rpx;border-radius: 6rpx;margin: 6rpx;}
.dp-product-itemlist .binfo {
		padding-top:6rpx;
		display: flex;
		align-items: center;
		min-width: 0;
	}

	.dp-product-itemlist .binfo .t1 {
		width: 40rpx;
		height: 40rpx;
		border-radius: 50%;
		margin-right: 10rpx;
		flex-shrink: 0;
	}

	.dp-product-itemlist .binfo .t2 {
		color: #666;
		font-size: 26rpx;
		font-weight: normal;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	.dp-product-itemlist .couponitem {
		width: 100%;
		/* padding: 0 20rpx 20rpx 20rpx; */
		font-size: 24rpx;
		color: #333;
		display: flex;
		align-items: center;
	}

	.dp-product-itemlist .couponitem .f1 {
		flex: 1;
		display: flex;
		flex-wrap: nowrap;
		overflow: hidden
	}

	.dp-product-itemlist .couponitem .f1 .t {
		margin-right: 10rpx;
		border-radius: 3px;
		font-size: 22rpx;
		height: 40rpx;
		line-height: 40rpx;
		padding-right: 10rpx;
		flex-shrink: 0;
		overflow: hidden
	}


.lianxi{color: #fff;border-radius: 50rpx 50rpx;line-height: 50rpx;text-align: center;font-size: 22rpx;padding: 0 14rpx;display: inline-block;float: right;}

.member{padding: 5rpx 0;}
.member_module{position: relative;border-radius: 8rpx;border: 1rpx solid #fd4a46;overflow: hidden;box-sizing: content-box;}
.member_lable{height: 100%;font-size: 22rpx;color: #fff;background: #fd4a46;padding: 0 15rpx;}
.member_value{padding: 0 15rpx;font-size: 20rpx;color: #fd4a46;}
.p3 .left{ line-height: 55rpx;border-radius: 10rpx 0 0 10rpx;color: #FD3D2D; font-size: 24rpx; 
    padding: 0 20rpx;flex:1;justify-content: space-between;  background: linear-gradient(90deg, #FCE48A 0%, #FCE48A 0%, rgba(253,74,70,0) 130%)     }
.p3 .right{line-height: 55rpx;text-align: center;border-radius:0 10rpx 10rpx 0;position: relative;font-size: 24rpx;color: #fff;padding: 0 20rpx;background: linear-gradient(271deg, #FB392A 0%, rgba(251,100,55,0.8) 100%);}
.p3 .right .yuandian{
    width: 8rpx;height: 14rpx;position: absolute;left: 0;border-radius: 0 100rpx 100rpx 0;top: 41%; background: #FCEFBA
}
.product_promotion_tag{font-size:24rpx;white-space:nowrap;margin-bottom:10rpx}
.product_promotion_tag .separator{width: 1px;height: 48rpx;background: #c9c9c9; margin: 0 10rpx;}
.tag-share{border-radius: 40rpx 0 0 40rpx !important;display: flex;align-items: center;width: 200rpx;}
</style>