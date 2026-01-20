<template>
<view style="width:100%">
	<view class="dp-product-normal-item">
		<view class="item" v-for="(item,index) in data" :style="'background:'+probgcolor+';'+(showstyle==2 ? 'width:49%;margin-right:'+(index%2==0?'2%':0) : (showstyle==3 ? 'width:32%;margin-right:'+(index%3!=2?'2%':0) :'width:100%'))" :key="item.id" @click="toDetail(index)" >
			<view class="product-pic" >
				<image class="image" :src="item.pic" mode="widthFix"/>
				<image class="saleimg" :src="saleimg" v-if="saleimg!=''" mode="widthFix"/>
			</view>
			<view class="product-info">
				<view class="p1" v-if="showname == 1">{{item.name}}</view>
				<view class="p5" v-if="showstyle=='1' && item.sellpoint" :style="{color:t('color2')}"><text>{{item.sellpoint}}</text></view>
        <view class="p5" v-if="item.showgivescore" :style="{color:t('color1')}">
          <text style="font-size: 24rpx;">赠送{{item.showgivescore}}{{t('积分')}}</text>
        </view>
				<!-- 是否显示商家 距离 佣金 S-->
				<view class="binfo flex-bt" v-if="(showbname=='1' || showbdistance=='1') && item.binfo">
						<view class="flex-y-center b1">
							<block v-if="showbname=='1'">
								<image :src="item.binfo.logo" class="t1">
								<text class="t2">{{item.binfo.name}}</text>
							</block>
						</view>
						<view class="b2 t2" v-if="showbdistance=='1' && item.binfo.distance">{{item.binfo.distance}}</view>
				</view>
				<view class="couponitem" v-if="showcommission == 1 && item.commission_price>0">
					<view class="f1">
						<view class="t" :style="{background:'rgba('+t('color2rgb')+',0.1)',color:t('color2')}">
							<text>{{t('佣金')}}{{item.commission_price}}{{item.commission_desc}}</text>
						</view>
					</view>
				</view>
				<!-- 是否显示商家 距离 佣金 E-->
                
        <view v-if="showstyle==2">
            <view class="field_buy" v-if="params.brand == 1 && item.brand">
                <span style="width: 80rpx">品牌：</span>
                <span>{{item.brand}}</span>
            </view>
            <view  class="field_buy" v-if="params.barcode == 1 && item.barcode">
                <span style="width: 80rpx">货号：</span>
                <span>{{item.barcode}}</span>
            </view>
            <view  class="field_buy" v-if="params.guige == 1 && item.ggname">
                <span style="width: 80rpx"> 规格：</span>
                <span>{{item.ggname}}</span>
            </view>
            <view  class="field_buy" v-if="params.unit == 1 && item.unit">
                <span style="width: 80rpx"> 单位：</span>
                <span>{{item.unit}}</span>
            </view>
            <view  class="field_buy" v-if="params.ggstock == 1">
                <span style="width: 80rpx"> 库存：</span>
                <span>{{item.ggstock}}</span>
            </view>
            <view  class="field_buy" v-if="params.valid_time == 1 && item.valid_time">
                <span style="width: 80rpx"> 有效期：</span>
                <span>{{item.valid_time}}</span>
            </view>
            <view  class="field_buy" v-if="params.remark == 1 && item.remark">
                <span style="width: 80rpx"> 备注：</span>
                <span>{{item.remark}}</span>
            </view>
        </view>
        <view v-if="(showstyle=='2' || showstyle=='3') && item.price_type != 1 && item.show_cost == '1'" :style="{color:item.cost_color?item.cost_color:'#999',fontSize:'36rpx'}"><text style="font-size: 24rpx;">{{item.cost_tag}}</text>{{item.cost_price}}</view>      
				<view class="p2">
					<view class="p2-1" :class="params.style=='1'?'flex-bt flex-y-center':''" v-if="showprice != '0' && ( item.price_type != 1 || item.sell_price > 0)">
						<view v-if="showstyle=='1' && item.price_type != 1 && item.show_cost=='1'" :style="{color:item.cost_color?item.cost_color:'#999',fontSize:'36rpx'}"><text style="font-size: 24rpx;">{{item.cost_tag}}</text>{{item.cost_price}}</view>
						<view class="flex-y-center" v-if="(!item.show_sellprice || (item.show_sellprice && item.show_sellprice==true)) || item.usd_sellprice">
							<view class="t1" :style="{color:item.price_color?item.price_color:t('color1')}">
								<block v-if="item.usd_sellprice">
									<text style="font-size:24rpx">$</text>{{item.usd_sellprice}}
									<text style="font-size: 28rpx;"><text style="font-size:24rpx">￥</text>{{item.sell_price}}</text><text style="font-size:24rpx" v-if="item.product_unit">/{{item.product_unit}}</text>
                  <text v-if="item.price_show && item.price_show_text" style="margin: 0 15rpx;font-size: 24rpx;font-weight: 400;">{{item.price_show_text}}</text>
								</block>
								<block v-else>
									<text style="font-size:24rpx">{{item.price_tag?item.price_tag:'￥'}}</text>{{item.sell_price}}
                  <text v-if="item.price_show && item.price_show_text" style="margin: 0 15rpx;font-size: 24rpx;font-weight: 400;">{{item.price_show_text}}</text>
				  <text v-if="!isNull(item.freezemoney_price) && item.freezemoney_price>0">+{{item.freezemoney_price}}{{t('冻结资金')}}</text>
                  <text v-if="!isNull(item.service_fee) && item.service_fee_switch && item.service_fee > 0" style="font-size: 28rpx;">+{{item.service_fee}}{{t('服务费')}}</text><text style="font-size:24rpx" v-if="item.product_unit">/{{item.product_unit}}</text>
									<text v-if="showstyle!=3 && item.product_type==2 && item.unit_price && item.unit_price>0" class="t1-m" :style="{color:t('color1')}">
										(约{{item.unit_price}}元/斤)
									</text>
								</block>
								<text :style="{background:'rgba('+t('color1rgb')+',0.12)',color:t('color1'),fontSize:'12rpx'}" v-if="(showprice == '3' && item.nextmemberlevel_name) || showprice == '4'" class="tag_price_name"> 零售价 </text>	
							</view>
							
							<text class="t2" v-if="(!item.show_sellprice || (item.show_sellprice && item.show_sellprice==true)) && item.market_price*1 > item.sell_price_origin*1 && showprice == '1'">￥{{item.market_price}}</text>
							<text class="t3" v-if="item.juli" style="color:#888;">{{item.juli}}</text> 
						</view>
					</view>					
					<view class="p2-1" v-if="item.xunjia_text && item.price_type == 1 && item.sell_price <= 0" style="height: 50rpx;line-height: 44rpx;">
						<text v-if="showstyle!=1" class="t1" :style="{color:t('color1'),fontSize:'30rpx'}">询价</text>
							<text v-if="showstyle==1" class="t1" :style="{color:t('color1')}">询价</text>
							<block v-if="item.xunjia_text && item.price_type == 1">
								<view class="lianxi" :style="{background:t('color1')}" @tap.stop="showLinkChange" :data-lx_name="item.lx_name" :data-lx_bid="item.lx_bid" :data-lx_bname="item.lx_bname" :data-lx_tel="item.lx_tel" data-btntype="2">{{item.xunjia_text?item.xunjia_text:'联系TA'}}</view>
							</block>
					</view>
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
        
				<view class="p2" v-if="showprice == '3'">
					<view class="p2-1"  style="height: 30rpx;line-height: 30rpx;">
						<view class="flex-y-center">
						<view class="t1" v-if="item.nextmemberlevel_price" style="height: 30rpx;line-height: 30rpx;text-align:center;">
						<text :style="{color:t('color2'),fontSize:'30rpx'}"><text style="font-size:22rpx">￥</text>{{item.nextmemberlevel_price}} </text>
						<text :style="{background:'rgba('+t('color1rgb')+',0.12)',color:t('color1'),fontSize:'12rpx'}" v-if="item.nextmemberlevel_name"  class="tag_price_name"> {{item.nextmemberlevel_name}} </text>
						</view>
						
						</view>
					</view>
				</view>
				<view class="p2" v-if="showprice == '4'">
					<view class="p2-1" style="height: 30rpx;line-height: 30rpx;">
						<view class="flex-y-center">
						<view class="t1" v-if="item.scoredk_price >=0" style="height: 30rpx;line-height: 30rpx;text-align:center;">
						<text :style="{color:t('color2'),fontSize:'30rpx'}"><text style="font-size:22rpx">￥</text>{{item.scoredk_price}} </text> <text :style="{background:'rgba('+t('color1rgb')+',0.12)',color:t('color1'),fontSize:'12rpx'}"  class="tag_price_name"> 出厂价 </text>					
						</view>
						</view>
					</view>
				</view>
				
				<view class="p6" v-if="showstyle=='1' && item.fwlist && item.fwlist.length>0">
					<view class="p6-m" :style="'background:rgba('+t('color2rgb')+',0.15);color:'+t('color2')+';'" v-for="(fw,fwidx) in item.fwlist" :key="fwidx">
						{{fw}}
					</view>
				</view>
				<view class="p1" v-if="item.merchant_name" style="color: #666;font-size: 24rpx;white-space: nowrap;text-overflow: ellipsis;margin-top: 6rpx;height: 30rpx;line-height: 30rpx;font-weight: normal;"><text>{{item.merchant_name}}</text></view>
				<view class="p1" v-if="item.main_business" style="color: #666;font-size: 24rpx;margin-top: 4rpx;font-weight: normal;"><text>{{item.main_business}}</text></view>
				<text class="p3" v-if="item.product_type == 3">手工费: ￥{{item.hand_fee?item.hand_fee:0}}</text>
				<view class="p3" v-if="item.product_show_costprice_fanwei">成本价￥{{item.cost_price_fanwei}}</view>
				<view class="p3" v-if="showstock=='1'">库存{{item.stock}}</view>
				<view class="p3" v-if="showsales=='1'">已售{{item.sales}}件</view>
				<view v-if="(showsales !='1' ||  item.sales<=0) && item.main_business" style="height: 44rpx;"></view>
        <view v-if="params.style=='2' && params.nowbuy == 1" @click.stop="buydialogChange" data-btntype="2" :data-proid="item[idfield]" class="nowbuy" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" >
            立即购买
        </view>
				<view class="p4" :style="params.style=='2' && params.nowbuy == 1?'bottom:24rpx;background:rgba('+t('color1rgb')+',0.1);color:'+t('color1'):'background:rgba('+t('color1rgb')+',0.1);color:'+t('color1')" v-if="showcart==1 && !item.price_type && item.hide_cart!=true" @click.stop="buydialogChange" data-btntype="1" :data-proid="item[idfield]"><text class="iconfont icon_gouwuche"></text></view>
				<view class="p4" :style="params.style=='2' && params.nowbuy == 1?'bottom:24rpx;background:rgba('+t('color1rgb')+',0.1);color:'+t('color1'):'background:rgba('+t('color1rgb')+',0.1);color:'+t('color1')" v-if="showcart==2 && !item.price_type && item.hide_cart!=true" @click.stop="buydialogChange" data-btntype="1" :data-proid="item[idfield]"><image :src="cartimg" class="img"/></text></view>
      </view>
			<view class="bg-desc" v-if="item.hongbaoEdu > 0" :style="{background:'linear-gradient(90deg,'+t('color2')+' 0%,rgba('+t('color2rgb')+',0.8) 100%)'}">可获额度 +{{item.hongbaoEdu}}</view>
		</view>
	</view>
	<block v-if="productType == 4">
		<block v-if="ggNum == 2">
			<buydialog-pifa v-if="buydialogShow" :proid="proid" :btntype="btntype" @buydialogChange="buydialogChange" @showLinkChange="showLinkChange" :menuindex="menuindex" />
		</block>
		<block v-else>
			<buydialog-pifa2 v-if="buydialogShow" :proid="proid" :btntype="btntype" @buydialogChange="buydialogChange" @showLinkChange="showLinkChange" :menuindex="menuindex" />
		</block>
	</block>
	<block v-else>
		<buydialog v-if="buydialogShow" :proid="proid" :btntype="btntype" @addcart="addcart" @buydialogChange="buydialogChange" :menuindex="menuindex"></buydialog>
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
				pre_url: app.globalData.pre_url,
				buydialogShow:false,
				proid:0,
                
        showLinkStatus:false,
        lx_bname:'',
        lx_name:'',
        lx_bid:'',
        lx_tel:'',
        btntype:1,
				productType:'',
				ggNum:''
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
			showstock:{default:'0'},
			showsales:{default:'1'},
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
      params:{
				type:Object,
				default() {
					return {};
				}
			},
		},
		methods: {
			buydialogChange: function (e) {
				if(!this.buydialogShow){
					this.proid = e.currentTarget.dataset.proid;
          this.btntype = e.currentTarget.dataset.btntype;
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
				console.log(this.buydialogShow);
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
.dp-product-normal-item{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-product-normal-item .item{display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:10rpx;overflow:hidden;}
.dp-product-normal-item .product-pic {width: 100%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 100%;position: relative;}
.dp-product-normal-item .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-product-normal-item .product-pic .saleimg{ position: absolute;width: 60px;height: auto; top: -3px; left:-3px;}
.dp-product-normal-item .product-info {padding:20rpx 20rpx;position: relative;}
.dp-product-normal-item .product-info .p2 .t1-m {font-size: 32rpx;padding-left: 8rpx;}
.dp-product-normal-item .product-info .p5 {font-size:24rpx;font-weight: bold;margin: 8rpx 0;}
.dp-product-normal-item .product-info .p6{font-size:24rpx;display: flex;flex-wrap: wrap;margin-top: 6rpx;}
.dp-product-normal-item .product-info .p6-m{text-align: center;padding:6rpx 10rpx;border-radius: 6rpx;margin: 6rpx;}
.dp-product-normal-item .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx;word-break: break-all;}
.dp-product-normal-item .product-info .p2{display:flex;align-items:center;overflow:hidden;padding:2px 0}
.dp-product-normal-item .product-info .p2-1{flex-grow:1;flex-shrink:1;height:40rpx;line-height:40rpx;overflow:hidden;white-space: nowrap}
.dp-product-normal-item .product-info .p2-1 .t1{font-size:36rpx;}
.dp-product-normal-item .product-info .p2-1 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-product-normal-item .product-info .p2-1 .t3 {margin-left:10rpx;font-size:22rpx;color: #999;}
.dp-product-normal-item .product-info .p2-2{font-size:20rpx;height:40rpx;line-height:40rpx;text-align:right;padding-left:20rpx;color:#999}
.dp-product-normal-item .product-info .p3{color:#999999;font-size:20rpx;margin-top:10rpx}
.dp-product-normal-item .product-info .p4{width:52rpx;height:52rpx;border-radius:50%;position:absolute;display:relative;bottom:10rpx;right:20rpx;text-align:center;}
.dp-product-normal-item .product-info .p4 .icon_gouwuche{font-size:30rpx;height:52rpx;line-height:52rpx}
.dp-product-normal-item .product-info .p4 .img{width:100%;height:100%}
.bg-desc {color: #fff; padding: 10rpx 20rpx;}

.dp-product-normal-item .product-info .binfo {
		padding-bottom:6rpx;
		display: flex;
		align-items: center;
		min-width: 0;
	}

	.dp-product-normal-item .product-info .binfo .t1 {
		width: 30rpx;
		height: 30rpx;
		border-radius: 50%;
		margin-right: 10rpx;
		flex-shrink: 0;
	}

	.dp-product-normal-item .product-info .binfo .t2 {
		color: #666;
		font-size: 24rpx;
		font-weight: normal;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
	.dp-product-normal-item .product-info .binfo .b2{flex-shrink: 0;}
	.dp-product-normal-item .product-info .binfo .b1{max-width: 75%;}
	.dp-product-normal-item .couponitem {
		width: 100%;
		/* padding: 0 20rpx 20rpx 20rpx; */
		font-size: 24rpx;
		color: #333;
		display: flex;
		align-items: center;
	}

	.dp-product-normal-item .couponitem .f1 {
		flex: 1;
		display: flex;
		flex-wrap: nowrap;
		overflow: hidden
	}

	.dp-product-normal-item .couponitem .f1 .t {
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
.field_buy{line-height: 40rpx;border-bottom: 0;padding: 4rpx 0;word-break: break-all;}
.nowbuy{width:160rpx;line-height:60rpx;text-align: center;border-radius: 4rpx;margin-top: 10rpx;}

.tag_price_name{height:25rpx;line-height:25rpx;border:0 #FF3143 solid;border-radius:10rpx;color:#FF3143;padding:0 8rpx;font-size:20rpx;text-overflow: ellipsis;}
</style>