<template>
<view style="width:100%">
	<view class="dp-product-itemline">
		<view class="item" v-for="(item,index) in data" :key="item.id"  @click="toDetail(index)" :style="{
	backgroundColor:probgcolor}">
			<view class="product-pic">
				<image class="image" :src="item.pic" mode="widthFix"/>
				<image class="saleimg" :src="saleimg" v-if="saleimg!=''" mode="widthFix"/>
			</view>
			<view class="product-info">
				<view class="p1" v-if="showname == 1">{{item.name}}</view>
        <view :style="{color:t('color1')}" v-if="item.showgivescore">
          <text style="font-size: 24rpx;">赠送{{item.showgivescore}}{{t('积分')}}</text>
        </view>
				<view :style="{color:item.cost_color?item.cost_color:'#999',fontSize:'36rpx'}" v-if="item.show_cost && item.price_type != 1"><text style="font-size: 24rpx;">{{item.cost_tag}}</text>{{item.cost_price}}</view>
				<view class="p2">
					<view class="p2-1" v-if="(!item.show_sellprice || (item.show_sellprice && item.show_sellprice==true)) && ( item.price_type != 1 || item.sell_price > 0) && showprice == '1'">
						<text class="t1" :style="{color:item.price_color?item.price_color:t('color1')}">
							<block v-if="item.usd_sellprice">
								<text style="font-size:24rpx">$</text>{{item.usd_sellprice}}
								<text style="font-size: 28rpx;"><text style="font-size:24rpx">￥</text>{{item.sell_price}}</text>
                <text v-if="item.price_show && item.price_show_text" style="margin: 0 15rpx;font-size: 22rpx;font-weight: 400;">{{item.price_show_text}}</text>
							</block>
							<block v-else>
								<text style="font-size:24rpx">{{item.price_tag?item.price_tag:'￥'}}</text>{{item.sell_price}}
                <text v-if="item.price_show && item.price_show_text" style="margin: 0 15rpx;font-size: 22rpx;font-weight: 400;">{{item.price_show_text}}</text>
				<text v-if="!isNull(item.freezemoney_price) && item.freezemoney_price>0">+{{item.freezemoney_price}}{{t('冻结资金')}}</text>
							</block>
						</text>
						<text class="t2" v-if="item.show_sellprice && item.market_price*1 > item.sell_price*1  && showprice == '1'">￥{{item.market_price}}</text>
            <text class="t3" v-if="item.product_type==3">手工费: ￥{{item.hand_fee?item.hand_fee:0}}</text>
					</view>
          <view class="p2-1" v-if="item.xunjia_text && item.price_type == 1 && item.sell_price <= 0" style="height: 50rpx;line-height: 44rpx;">
            <text class="t1" :style="{color:t('color1'),fontSize:'30rpx'}">询价</text>
              <block v-if="item.xunjia_text && item.price_type == 1">
                <view class="lianxi" :style="{background:t('color1')}" @tap.stop="showLinkChange" :data-lx_name="item.lx_name" :data-lx_bid="item.lx_bid" :data-lx_bname="item.lx_bname" :data-lx_tel="item.lx_tel" data-btntype="2">{{item.xunjia_text?item.xunjia_text:'联系TA'}}</view>
              </block>
          </view>
				</view>
        <!-- 商品处显示会员价 -->
        <view v-if="item.price_show && item.price_show == 1" style="line-height: 46rpx;">
          <text style="font-size:24rpx">￥{{item.sell_putongprice}}</text>
        </view>
        <view v-if="item.priceshows && item.priceshows.length>0">
          <view v-for="(item2,index2) in item.priceshows" style="line-height: 46rpx;">
            <text style="font-size:24rpx">￥{{item2.sell_price}}</text>
            <text style="margin-left: 15rpx;font-size: 22rpx;font-weight: 400;">{{item2.price_show_text}}</text>
          </view>
        </view>
                <view class="p1" v-if="item.merchant_name" style="color: #666;font-size: 24rpx;white-space: nowrap;text-overflow: ellipsis;margin-top: 6rpx;height: 30rpx;line-height: 30rpx;font-weight: normal"><text>{{item.merchant_name}}</text></view>
                <view class="p1" v-if="item.main_business" style="color: #666;font-size: 24rpx;margin-top: 4rpx;font-weight: normal;"><text>{{item.main_business}}</text></view>
				<view class="p3" v-if="showsales=='1'">已售{{item.sales}}件</view>
                <view v-if="(showsales !='1' ||  item.sales<=0) && item.main_business" style="height: 44rpx;"></view>
				<view class="p4" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" v-if="showcart==1 && !item.price_type && item.hide_cart!=true" @click.stop="buydialogChange" :data-proid="item[idfield]"><text class="iconfont icon_gouwuche"></text></view>
				<view class="p4" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" v-if="showcart==2 && !item.price_type && item.hide_cart!=true" @click.stop="buydialogChange" :data-proid="item[idfield]"><image :src="cartimg" class="img"/></text></view>
            </view>
		</view>
	</view>
	<block v-if="productType == 4">
		<block v-if="ggNum == 2">
			<buydialog-pifa v-if="buydialogShow" :proid="proid" @buydialogChange="buydialogChange" @showLinkChange="showLinkChange" :menuindex="menuindex" />
		</block>
		<block v-else>
			<buydialog-pifa2 v-if="buydialogShow" :proid="proid" @buydialogChange="buydialogChange" @showLinkChange="showLinkChange" :menuindex="menuindex" />
		</block>
	</block>
	<block v-else>
		<buydialog v-if="buydialogShow" :proid="proid" @addcart="addcart" @buydialogChange="buydialogChange" :menuindex="menuindex"></buydialog>
	</block>
    <view class="posterDialog linkDialog" v-if="showLinkStatus">
    	<view class="main">
    		<view class="close" @tap="showLinkChange"><image class="img" :src="pre_url+'/static/img/close.png'"/></view>
    		<view class="content">
    			<view class="title">{{lx_name}}</view>
    			<view class="row" v-if="lx_bid > 0">
    				<view class="f1" style="width: 150rpx;">店铺名称</view>
    				<view class="f2" style="width: 100%;max-width: 470rpx;display: flex;" @tap="goto" :data-url="'/pagesExt/business/index?id='+lx_bid">
    				  <view style="width: 100%;white-space: nowrap;text-overflow: ellipsis;overflow: hidden;">{{lx_bname}}</view>
    				  <view style="flex: 1;"></view>
    				  <image :src="pre_url+'/static/img/arrowright.png'" class="image"/>
    				</view>
    			</view>
    			<view class="row" v-if="lx_tel">
    				<view class="f1" style="width: 150rpx;">联系电话</view>
    				<view class="f2" style="width: 100%;max-width: 470rpx;" @tap="goto" :data-url="'tel::'+lx_tel" :style="{color:t('color1')}">{{lx_tel}}<image :src="pre_url+'/static/img/copy.png'" class="copyicon" @tap.stop="copy" :data-text="lx_tel"></image></view>
    			</view>
    		</view>
    	</view>
    </view>
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
			showstyle:{default:2},
			menuindex:{default:-1},
			saleimg:{default:''},
			showname:{default:1},
			namecolor:{default:'#333'},
			showprice:{default:'1'},
			showcost:{default:'0'},
			showsales:{default:'1'},
			showcart:{default:'1'},
			cartimg:{default:'/static/imgsrc/cart.svg'},
			data:{},
			idfield:{default:'id'},
			probgcolor:{default:'#fff'}
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
			addcart:function(){
				this.$emit('addcart');
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
.dp-product-itemline{width:100%;display:flex;overflow-x:scroll;overflow-y:hidden}
.dp-product-itemline .item{width: 220rpx;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:10rpx;margin-right:4px}
.dp-product-itemline .product-pic {width:220rpx;height:0;overflow:hidden;background: #ffffff;padding-bottom: 100%;position: relative;}
.dp-product-itemline .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-product-itemline .product-pic .saleimg{ position: absolute;width: 60px;height: auto; top: -3px; left:-3px;}
.dp-product-itemline .product-info {padding:10rpx 10rpx;position: relative;}
.dp-product-itemline .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx;word-break: break-all;}
.dp-product-itemline .product-info .p2{display:flex;align-items:center;overflow:hidden;padding:2px 0}
.dp-product-itemline .product-info .p2-1{flex-grow:1;flex-shrink:1;height:40rpx;line-height:40rpx;overflow:hidden;white-space: nowrap}
.dp-product-itemline .product-info .p2-1 .t1{font-size:36rpx;}
.dp-product-itemline .product-info .p2-1 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-product-itemline .product-info .p2-2{font-size:20rpx;height:40rpx;line-height:40rpx;text-align:right;padding-left:20rpx;color:#999}
.dp-product-itemline .product-info .p3{color:#999999;font-size:20rpx;margin-top:10rpx}
.dp-product-itemline .product-info .p4{width:48rpx;height:48rpx;border-radius:50%;position:absolute;display:relative;bottom:8rpx;right:20rpx;text-align:center;}
.dp-product-itemline .product-info .p4 .icon_gouwuche{font-size:28rpx;height:48rpx;line-height:48rpx}
.dp-product-itemline .product-info .p4 .img{width:100%;height:100%}

.lianxi{color: #fff;border-radius: 50rpx 50rpx;line-height: 50rpx;text-align: center;font-size: 22rpx;padding: 0 14rpx;display: inline-block;float: right;}
</style>