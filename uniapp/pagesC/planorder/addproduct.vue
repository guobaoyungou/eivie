<template>
<view >
  <view style="width:100%;padding: 0 24rpx;">
    <view class="dp-product-itemlist">
    	<view class="dp-product-item" v-for="(item,index) in datalist" :key="index">
        <view class="item" @click="toDetail(index)">
          <view class="product-pic">
            <image class="image" :src="item.pic" mode="widthFix"/>
          </view>
          <view class="product-info">
            <view class="p1" v-if="showname == 1">{{item.name}}</view>
            <view class="p5" :style="{color:t('color2')}" v-if="item.sellpoint"><text>{{item.sellpoint}}</text></view>
            <view :style="{color:t('color1')}" v-if="item.showgivescore">
              <text style="font-size: 24rpx;">赠送{{item.showgivescore}}{{t('积分')}}</text>
            </view>
            <view :style="{color:item.cost_color?item.cost_color:'#999',fontSize:'36rpx'}" v-if="item.show_cost && item.price_type != 1"><text style="font-size: 24rpx;">{{item.cost_tag}}</text>{{item.cost_price}}</view>
            
            <view class="p2" v-if="(!item.show_sellprice || (item.show_sellprice && item.show_sellprice==true)) && ( item.price_type != 1 || item.sell_price > 0)">
              <view class="t1" :style="{color:item.price_color?item.price_color:t('color1')}">
                  <text style="font-size:24rpx;padding-right:1px">{{item.price_tag?item.price_tag:'￥'}}</text>{{item.sell_price}}
                  <text v-if="item.price_show && item.price_show_text" style="margin: 0 15rpx;font-size: 24rpx;font-weight: 400;">{{item.price_show_text}}</text>
                  <text style="font-size:24rpx" v-if="item.product_unit">/{{item.product_unit}}</text><text v-if="!isNull(item.service_fee) && item.service_fee_switch && item.service_fee > 0" style="font-size: 28rpx;">+{{item.service_fee}}{{t('服务费')}}</text>
                  <text v-if="item.product_type==2 && item.unit_price && item.unit_price>0" class="t1-m" :style="{color:t('color1')}">
                    (约{{item.unit_price}}元/斤)
                  </text>
                  <!-- 称重商品 单价 -->
                  <view class="p6" v-if="item.fwlist && item.fwlist.length>0">
                    <view class="p6-m" :style="'background:rgba('+t('color2rgb')+',0.15);color:'+t('color2')+';'" v-for="(fw,fwidx) in item.fwlist" :key="fwidx">
                      {{fw}}
                    </view>
                  </view>
              </view>
              <view class="t2" v-if="item.show_sellprice && item.market_price*1 > item.sell_price*1 && showprice == '1'">￥{{item.market_price}}</view>
              <view v-if="item.addstatus<=0" @tap.stop="addit" :data-id="item.id" :data-index="index" data-type='one'  class="right-btn" :style="{backgroundColor:t('color1')}">上橱窗</view>
              <view v-else  class="right-btn" style="background-color:#ddd">已添加</view>
            </view>
            <!-- 商品处显示会员价 -->
    
            <!-- 是否显示 佣金 S-->
            <view class="couponitem" v-if="item.commission>0">
              <view class="f1">
                <view class="t" :style="{background:'rgba('+t('color2rgb')+',0.1)',color:t('color2')}">
                  <text>{{t('佣金')}}{{item.commission}}{{item.commission_desc}}</text>
                </view>
              </view>
            </view>
            <!-- 是否显示 佣金 E-->
    
          </view>
        </view>
    	</view>
    </view>
    <nomore v-if="nomore" style="width: 100%;"></nomore>
    <nodata v-if="nodata" style="width: 100%;"></nodata>
    <loading v-if="loading"></loading>
    <view style="width: 100%;height:140rpx ;clear: both;"></view>
    <view class="bottom-wc" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
      <view>共计{{count}}件商品</view>
      <view @tap.stop="addit" data-id="" data-index="" data-type='all' class="bottom-btn" :style="{backgroundColor:t('color1')}">一键添加所有商品</view>
    </view>
  </view>
  <dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
  <popmsg ref="popmsg"></popmsg>
  <wxxieyi></wxxieyi>
</view>
</template>
<script>
	var app = getApp();
	export default {
		data(){
			return {
        opt:{},
				proid:0,
				pre_url: app.globalData.pre_url,
        menuindex:-1,
        showname:1,
        namecolor:'#333',
        showprice:1,
        showcost:0,
        showsales:1,
        cartimg:'/static/imgsrc/cart.svg',
        shopid:0,
        datalist: [],
        count:0,
			}
		},
    onLoad: function (opt) {
    	this.opt = app.getopts(opt);
    	this.getdata();
    },
    onPullDownRefresh: function () {
    	this.getdata();
    },
    onReachBottom: function () {
      if (!this.nodata && !this.nomore) {
        this.pagenum = this.pagenum + 1;
        this.getdata(true);
      }
    },
		methods: {
      getdata: function (loadmore) {
      	if(!loadmore){
      		this.pagenum = 1;
      		this.datalist = [];
      	}
        var that = this;
        var pagenum = that.pagenum;
      	that.nodata = false;
      	that.nomore = false;
      	that.loading = true;
        app.get('ApiPlanorder/addproduct', {pagenum: pagenum}, function (res) {
      		that.loading = false;
          if(res.status){
              var data = res.data;
              if (pagenum == 1) {
                that.shopid = res.shopid;
                that.count = res.count;
                that.datalist = data;
                if (data.length == 0) {
                  that.nodata = true;
                }
                that.loaded();
              }else{
                if (data.length == 0) {
                  that.nomore = true;
                } else {
                  var datalist = that.datalist;
                  var newdata = datalist.concat(data);
                  that.datalist = newdata;
                }
              }
          }else{
            app.alert(res.msg);
          }
        });
      },
			toDetail:function(key){
				var that = this;
				var item = that.datalist[key];
				var id = item['id'];
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
        if(that.shopid)  url += '&shopid='+that.shopid
				app.goto(url);
			},
      addit:function(e){
        var that = this;
        var id = e.currentTarget.dataset.id;
        var index = e.currentTarget.dataset.index;
        var type = e.currentTarget.dataset.type;
        if(type == 'one'){
          var msg = '确定添加此商品？';
        }else{
          var msg = '确定一键添加所有商品？';
        }
        app.confirm(msg,function(){
          app.post('ApiPlanorder/addproduct', {id:id,type:type}, function (res) {
          	that.loading = false;
            if(res.status){
              app.success(res.msg);
              setTimeout(function(){
                if(type == 'all'){
                  that.getdata();
                }else{
                  that.datalist[index]['addstatus'] = 1;
                }
              },900)
            }else{
              app.alert(res.msg);
            }
          });
        })
      }
		}
	}
</script>
<style>
.dp-product-item{margin-bottom: 12rpx;padding:20rpx;width: 100%;border-bottom: 1rpx solid #f6f6f6;background-color: #fff;}
.dp-product-itemlist{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-product-itemlist .item{width:100%;display: inline-block;position: relative;background: #fff;display:flex;border-radius:10rpx;align-items: center;}
.dp-product-itemlist .product-pic {width: 30%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 30%;position: relative;border-radius:4px;}
.dp-product-itemlist .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-product-itemlist .product-pic .saleimg{ position: absolute;width: 120rpx;height: auto; top: -6rpx; left:-6rpx;}
.dp-product-itemlist .product-info {width: 70%;padding:6rpx 10rpx 5rpx 20rpx;position: relative;}
.dp-product-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx;word-break: break-all;}
.dp-product-itemlist .product-info .p2{margin-top:10rpx;overflow:hidden;}
.dp-product-itemlist .product-info .p2 .t1{font-size:36rpx;}
.dp-product-itemlist .product-info .p2 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-product-itemlist .product-info .p2 .t3 {margin-left:10rpx;font-size:24rpx;color: #888;}
.dp-product-itemlist .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx}
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

.right-btn{float: right;height: 60rpx;line-height: 60rpx;width: 120rpx;text-align: center;color: #fff;border-radius: 4rpx 4rpx;font-size: 26rpx;}
.bottom-wc{padding:0 20rpx;display: flex;justify-content: space-between;background-color: #fff;align-items: center;position: fixed;bottom: 0;left: 0;width: 100%;height: 110rpx;}
.bottom-btn{width: 45%;height: 60rpx;line-height: 60rpx;text-align: center;color: #fff;border-radius: 4rpx 4rpx;}
</style>