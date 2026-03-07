<template>
<view style="width:100%">
	<view class="dp-product-itemlist">
		<view class="dp-product-item" v-for="(item,index) in data" :key="item.id" :style="{backgroundColor:probgcolor}">
		<view class="item" @click="toDetail(index)">
			<view class="product-pic">
				<block v-if="covertype=='video'">
					<video class="cover-video" :src="item.pic" :autoplay="false" :loop="false" :muted="true" :controls="false" :show-center-play-btn="false" :show-play-btn="false" :show-fullscreen-btn="false" :enable-progress-gesture="false" objectFit="cover"></video>
					<view class="play-icon"><image class="play-img" :src="pre_url+'/static/img/play.png'" mode="aspectFit"></image></view>
				</block>
				<block v-else>
					<image class="image" :src="item.pic" mode="widthFix"/>
				</block>
				<image class="saleimg" :src="saleimg" v-if="saleimg!=''" mode="widthFix"/>
			</view>
			<view class="product-info">
				<view class="p1" v-if="showname == 1">{{item.name}}</view>
				<view class="p5" :style="{color:t('color2')}" v-if="item.sellpoint"><text>{{item.sellpoint}}</text></view>
        <view :style="{color:t('color1')}" v-if="item.showgivescore">
          <text style="font-size: 24rpx;">赠送{{item.showgivescore}}{{t('积分')}}</text>
        </view>
				<view :style="{color:item.cost_color?item.cost_color:'#999',fontSize:'36rpx'}" v-if="item.show_cost && item.price_type != 1"><text style="font-size: 24rpx;">{{item.cost_tag}}</text>{{item.cost_price}}</view>
				<view class="p2" v-if="(!item.show_sellprice || (item.show_sellprice && item.show_sellprice==true)) && ( item.price_type != 1 || item.sell_price > 0) && showprice != '0'">
					<view v-if="item.price_show_type=='0' || !item.price_show_type ">
						<text class="t1" :style="{color:item.price_color?item.price_color:t('color1')}">
							<block v-if="item.usd_sellprice">
								<text style="font-size:24rpx">$</text>{{item.usd_sellprice}}
								<text style="font-size: 28rpx;"><text style="font-size:24rpx;padding-right:1px">￥</text>{{item.sell_price}}</text><text style="font-size:24rpx" v-if="item.product_unit">/{{item.product_unit}}</text>
                <text v-if="item.price_show && item.price_show_text" style="margin: 0 15rpx;font-size: 24rpx;font-weight: 400;">{{item.price_show_text}}</text>
							</block>
							<block v-else>
								<text style="font-size:24rpx;padding-right:1px">{{item.price_tag?item.price_tag:'￥'}}</text>{{item.sell_price}}
                <text v-if="item.price_show && item.price_show_text" style="margin: 0 15rpx;font-size: 24rpx;font-weight: 400;">{{item.price_show_text}}</text>
                <text style="font-size:24rpx" v-if="item.product_unit">/{{item.product_unit}}</text><text v-if="!isNull(item.service_fee) && item.service_fee_switch && item.service_fee > 0" style="font-size: 28rpx;">+{{item.service_fee}}{{t('服务费')}}</text>
								<text v-if="!isNull(item.freezemoney_price) && item.freezemoney_price>0">+{{item.freezemoney_price}}{{t('冻结资金')}}</text>
								<text v-if="item.product_type==2 && item.unit_price && item.unit_price>0" class="t1-m" :style="{color:t('color1')}">
									(约{{item.unit_price}}元/斤)
								</text>
								<!-- 称重商品 单价 -->
								<view class="p6" v-if="item.fwlist && item.fwlist.length>0">
									<view class="p6-m" :style="'background:rgba('+t('color2rgb')+',0.15);color:'+t('color2')+';'" v-for="(fw,fwidx) in item.fwlist" :key="fwidx">
										{{fw}}
									</view>
								</view>
							</block>
						</text>
					</view>
					<view v-if="item.price_show_type=='1' || item.price_show_type=='2'">
						<view v-if="item.is_vip == '0'">

							<text class="t1" :style="{color:t('color1')}">
								<block v-if="item.usd_sellprice">
									<text style="font-size:24rpx">$</text>{{item.usd_sellprice}}
									<view style="font-size: 28rpx;">
                    <text style="font-size:20rpx;padding-right:1px">￥</text>
                    <text style="font-size: 32rpx;">{{item.sell_price}}</text>
                    <text v-if="item.price_show && item.price_show_text" style="margin: 0 15rpx;font-size: 24rpx;font-weight: 400;">{{item.price_show_text}}</text>
									</view>
								</block>
								<block v-else>
									<text style="font-size:20rpx;padding-right:1px">￥</text>
									<text style="font-style: 32rpx;">{{item.sell_price}}</text>
								</block>
							</text>
							<view class="member flex" v-if="item.price_show_type=='2' && item.lvprice==1 ">
								<view class="member_module flex" :style="'border-color:' + t('color1')">
									<view v-if="!isNull(item.level_name) && !isEmpty(item.level_name)" :style="{background:t('color1')}" class="member_lable flex-y-center">{{item.level_name}}</view>
									<view :style="'color:' + t('color1')" class="member_value">
										￥<text style="font-size: 26rpx ;">{{item.sell_price_origin}}</text>
                    <text v-if="item.price_show && item.price_show_text" style="margin: 0 15rpx;font-size: 24rpx;font-weight: 400;">{{item.price_show_text}}</text>
									</view>
								</view>
							</view>
						</view>
						<view v-if="item.is_vip == '1'">
							<view class="member flex">
								<view class="member_module flex" :style="'border-color:' + t('color1')">
									<view v-if="!isNull(item.level_name) && !isEmpty(item.level_name)" :style="{background:t('color1')}" class="member_lable flex-y-center">{{item.level_name}}</view>
									<view :style="'color:' + t('color1')" class="member_value" >
										￥<text style="font-size: 32rpx;">{{item.sell_price}}<text v-if="item.service_fee_switch && item.service_fee > 0" style="font-size: 28rpx;">+{{item.service_fee}}{{t('服务费')}}</text></text>
									</view>
								</view>
							</view>
						
							<text class="t1" :style="{color:t('color1')}">
								<block v-if="item.usd_sellprice">
									<text style="font-size:24rpx">$</text>{{item.usd_sellprice}}
									<text style="font-size: 30rpx;"><text style="font-size:20rpx;padding-right:1px">￥</text>
									<text :style="item.lvprice =='1'?'font-size:26rpx;':'font-size:32rpx;'">{{item.sell_price_origin}}</text>	
									</text>
								</block>
								<block v-else>
									<text >	
										<text style="font-size:20rpx;padding-right:1px" v-if="item.sell_price_origin">￥</text >
										
										<text :style="item.lvprice =='1'?'font-size:26rpx;':'font-size:32rpx;'"> {{item.sell_price_origin}}</text>
									</text>
								</block>
							</text>
						
						</view>
					</view>
					
					
					<text class="t2" v-if="item.show_sellprice && item.market_price*1 > item.sell_price*1 && showprice == '1'">￥{{item.market_price}}</text>
					
					<text class="t3" v-if="item.juli">{{item.juli}}</text>
				</view>
				<view class="p2" v-if="item.xunjia_text && item.price_type == 1 && item.sell_price <= 0" style="height: 50rpx;line-height: 44rpx;">
					<text class="t1" :style="{color:t('color1'),fontSize:'30rpx'}">询价</text>
						<block v-if="item.xunjia_text && item.price_type == 1">
							<view class="lianxi" :style="{background:t('color1')}" @tap.stop="showLinkChange" :data-lx_name="item.lx_name" :data-lx_bid="item.lx_bid" :data-lx_bname="item.lx_bname" :data-lx_tel="item.lx_tel" data-btntype="2">{{item.xunjia_text?item.xunjia_text:'联系TA'}}</view>
						</block>
				</view>
        <!-- 商品处显示会员价 -->
        <view v-if="item.price_show && item.price_show == 1" style="line-height: 46rpx;">
          <text style="font-size:26rpx">￥{{item.sell_putongprice}}</text>
        </view>
        <view v-if="item.priceshows && item.priceshows.length>0">
          <view v-for="(item2,index2) in item.priceshows" style="line-height: 46rpx;">
            <text style="font-size:26rpx">￥{{item2.sell_price}}</text>
            <text style="margin-left: 15rpx;font-size: 22rpx;font-weight: 400;">{{item2.price_show_text}}</text>
          </view>
        </view>
				<!-- 是否显示 佣金 S-->
				<view class="couponitem" v-if="showcommission == 1 && item.commission_price>0">
					<view class="f1">
						<view class="t" :style="{background:'rgba('+t('color2rgb')+',0.1)',color:t('color2')}">
							<text>{{t('佣金')}}{{item.commission_price}}{{item.commission_desc}}</text>
						</view>
					</view>
				</view>
				<!-- 是否显示 佣金 E-->
				<view class="p1" v-if="item.merchant_name" style="color: #666;font-size: 24rpx;white-space: nowrap;text-overflow: ellipsis;margin-top: 6rpx;height: 30rpx;line-height: 30rpx;font-weight: normal"><text>{{item.merchant_name}}</text></view>
				<view class="p1" v-if="item.main_business" style="color: #666;font-size: 24rpx;margin-top: 4rpx;font-weight: normal;"><text>{{item.main_business}}</text></view>
        <view class="p3" v-if="item.product_type==3">
        	<view class="p3-1"><text style="overflow:hidden">手工费: ￥{{item.hand_fee?item.hand_fee:0}}</text></view>
        </view>
        <view class="p3">
					<view class="p3-1" style="flex-grow:1;text-align: left;" v-if="showsales=='1' && item.sales>0"><text style="overflow:hidden">已售{{item.sales}}件</text></view>
					<view class="p3-1" style="flex-grow:1;text-align: left;" v-if="showstock=='1' && item.stock>0"><text style="overflow:hidden">库存{{item.stock}}</text></view>
				</view>
				<view v-if="(showsales !='1' ||  item.sales<=0) && item.main_business" style="height: 44rpx;"></view>
        <block v-if="!item.price_type && item.hide_cart!=true">
          <view v-if="showcart==1" class="p4" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}"  @click.stop="buydialogChange" :data-proid="item[idfield]"><text class="iconfont icon_gouwuche"></text></view>
          <view v-if="showcart==2" class="p4" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}"  @click.stop="buydialogChange" :data-proid="item[idfield]"><image :src="cartimg" class="img"/></text></view>
          <view v-if="showcart==3" class="p4 p4-text" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" @click.stop="toCartTextDetail(index)">{{carttext||'做同款'}}</view>
        </block>
    
        <view v-if="poshopid && poshopid>0" class="p4" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}"><text class="iconfont icon_gouwuche"></text></view>
			</view>
		</view>
		<!-- 是否显示商家 距离 S-->
		<view class="binfo flex-bt" v-if="(showbname=='1' || showbdistance=='1') && item.binfo">
				<view class="flex-y-center">
					<block  v-if="showbname=='1'">
						<image :src="item.binfo.logo" class="t1">
						<text class="t2">{{item.binfo.name}}</text>
					</block>
				</view>
				<text class="t2" v-if="showbdistance=='1' && item.binfo.distance">{{item.binfo.distance}}</text>
		</view>
		<!-- 是否显示商家 距离 E-->
		</view>
	</view>
	<block v-if="productType == 4">
		<block v-if="ggNum == 2">
			<buydialog-pifa v-if="buydialogShow" :proid="proid"  @buydialogChange="buydialogChange" @showLinkChange="showLinkChange" :menuindex="menuindex" />
		</block>
		<block v-else>
			<buydialog-pifa2 v-if="buydialogShow" :proid="proid" @buydialogChange="buydialogChange" @showLinkChange="showLinkChange" :menuindex="menuindex" />
		</block>
	</block>
	<block v-else>
		<buydialog v-if="buydialogShow" :proid="proid" @buydialogChange="buydialogChange" :menuindex="menuindex"></buydialog>
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
			carttext:{default:''},
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
      poshopid:{default:0},//排队系统里的店铺ID参数，用于页面进入来源区别
			detailurl:{default:''},
			covertype:{default:''},
			saleslabel:{default:''},
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
				if(item.tourl){
					app.goto(item.tourl);
					return;
				}
				if(that.detailurl){
					var id = item[that.idfield];
					app.goto(that.detailurl + (that.detailurl.indexOf('?') > -1 ? '&' : '?') + 'id=' + id);
					return;
				}
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
        if(that.poshopid) url += '&poshopid='+that.poshopid;
				app.goto(url);
			},
			toCartTextDetail:function(key){
				var that = this;
				var item = that.data[key];
				if(item.tourl){
					app.goto(item.tourl);
				} else if(that.detailurl){
					var id = item[that.idfield];
					app.goto(that.detailurl + (that.detailurl.indexOf('?') > -1 ? '&' : '?') + 'id=' + id);
				} else {
					var id = item[that.idfield];
					app.goto('/pagesZ/generation/create?id='+id+'&type=1');
				}
			}
		}
	}
</script>
<style>
.dp-product-item{margin-bottom: 12rpx;padding:20rpx;width: 100%;border-bottom: 1rpx solid #f6f6f6;}
.dp-product-itemlist{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-product-itemlist .item{width:100%;display: inline-block;position: relative;background: #fff;display:flex;border-radius:10rpx;align-items: center;}
.dp-product-itemlist .product-pic {width: 30%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 30%;position: relative;border-radius:4px;}
.dp-product-itemlist .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-product-itemlist .product-pic .cover-video{position:absolute;top:0;left:0;width:100%;height:100%;}
.dp-product-itemlist .product-pic .play-icon{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:50rpx;height:50rpx;background:rgba(0,0,0,0.5);border-radius:50%;display:flex;align-items:center;justify-content:center;z-index:10;}
.dp-product-itemlist .product-pic .play-icon .play-img{width:24rpx;height:24rpx;}
.dp-product-itemlist .product-pic .saleimg{ position: absolute;width: 120rpx;height: auto; top: -6rpx; left:-6rpx;}
.dp-product-itemlist .product-info {width: 70%;padding:6rpx 10rpx 5rpx 20rpx;position: relative;}
.dp-product-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;word-break: break-all;}
.dp-product-itemlist .product-info .p2{margin-top:10rpx;overflow:hidden;}
.dp-product-itemlist .product-info .p2 .t1{font-size:36rpx;}
.dp-product-itemlist .product-info .p2 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-product-itemlist .product-info .p2 .t3 {margin-left:10rpx;font-size:24rpx;color: #888;}
.dp-product-itemlist .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx}
.dp-product-itemlist .product-info .p3-1{font-size:20rpx;height:30rpx;line-height:30rpx;text-align:right;color:#999}
.dp-product-itemlist .product-info .p4{width:48rpx;height:48rpx;border-radius:50%;position:absolute;display:relative;bottom:6rpx;right:4rpx;text-align:center;}
.dp-product-itemlist .product-info .p4 .icon_gouwuche{font-size:28rpx;height:48rpx;line-height:48rpx}
.dp-product-itemlist .product-info .p4 .img{width:100%;height:100%}
.dp-product-itemlist .product-info .p4.p4-text{width:auto;height:auto;padding:0 16rpx;border-radius:26rpx;font-size:22rpx;line-height:48rpx}
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
</style>