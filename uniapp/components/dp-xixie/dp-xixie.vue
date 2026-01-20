<template>
<view class="dp-collage" :style="{
	backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:(params.padding_y*2.2)+'rpx '+params.padding_x*2.2+'rpx'
}">
	<view class="dp-collage-itemlist">
		<block v-if="datalist && datalist.length>0">
			<view class="item" v-for="(item,index) in datalist" :key="item.id">
				<view class="product-pic">
					<image class="image" :src="item.pic" mode="widthFix"/>
					<image class="saleimg" :src="params.saleimg" v-if="params.saleimg!=''" mode="widthFix"/>
				</view>
				<view class="product-info">
					<view class="p1" v-if="params.showname == 1">{{item.name}}</view>
					<view class="p2" v-if="params.showprice != '0'">
						<text class="t1" :style="{color:t('color1')}"><text style="font-size:24rpx;padding-right:1px">￥</text>{{item.sell_price}}</text>
					</view>
			        <view class="p2" v-if="params.showprice == '1' " style="overflow: hidden;line-height: 50rpx;height: 50rpx;margin-top: 4rpx;">
			            <block v-if="item.vip_price && item.vip_price>0">
			                <view style="background-color: #CC932E;color: #fff;display: inline-block;padding: 0 10rpx;border-radius: 6rpx 0 0 6rpx;font-size: 25rpx;float:left">会员价</view>
			                <view class="t1"  :style="{backgroundColor:t('color1'),color:'#fff',display:'inline-block',borderRadius:'0 6rpx 6rpx 0',padding:'0 20rpx',lineHeight: '50rpx',float:'left',fontSize:'27rpx'}">
			                    <text style="font-size:24rpx;padding-right:1px">￥</text>
			                    {{item.vip_price}}
			                </view>
			            </block>
			            <view v-if="!item.gwcnum || item.gwcnum<=0" @tap="gwcplus" :data-proid="item.proid" style="float: right;">
			                <text class="iconfont icon_gouwuche" :style="{color:t('color1')}"></text>
			            </view>
			            <view  v-if="item.gwcnum && item.gwcnum>0" class="addnum" style="margin-top: 4rpx;">
			            	<view class="minus" @tap="gwcminus" :data-proid="item.proid"><image class="img" src="/static/img/cart-minus.png"/></view>
			            	<input class="input" type="number" :value="item.gwcnum?item.gwcnum:1" :data-proid="item.proid" @input="gwcinput"></input>
			            	<view class="plus" @tap="gwcplus" :data-proid="item.proid"><image class="img" src="/static/img/cart-plus.png"/></view>
			            </view>
			        </view>
				</view>
			</view>
		</block>
		<block v-else>
			<view class="item" v-for="(item,index) in data" :key="item.id">
				<view class="product-pic">
					<image class="image" :src="item.pic" mode="widthFix"/>
					<image class="saleimg" :src="params.saleimg" v-if="params.saleimg!=''" mode="widthFix"/>
				</view>
				<view class="product-info">
					<view class="p1" v-if="params.showname == 1">{{item.name}}</view>
					<view class="p2" v-if="params.showprice != '0'">
						<text class="t1" :style="{color:t('color1')}"><text style="font-size:24rpx;padding-right:1px">￥</text>{{item.sell_price}}</text>
					</view>
					<view class="p2" v-if="params.showprice == '1' " style="overflow: hidden;line-height: 50rpx;height: 50rpx;margin-top: 4rpx;">
						<block v-if="item.vip_price && item.vip_price>0">
							<view style="background-color: #CC932E;color: #fff;display: inline-block;padding: 0 10rpx;border-radius: 6rpx 0 0 6rpx;font-size: 25rpx;float:left">会员价</view>
							<view class="t1"  :style="{backgroundColor:t('color1'),color:'#fff',display:'inline-block',borderRadius:'0 6rpx 6rpx 0',padding:'0 20rpx',lineHeight: '50rpx',float:'left',fontSize:'27rpx'}">
								<text style="font-size:24rpx;padding-right:1px">￥</text>
								{{item.vip_price}}
							</view>
						</block>
						<view v-if="!item.gwcnum || item.gwcnum<=0" @tap="gwcplus" :data-proid="item.proid" style="float: right;">
							<text class="iconfont icon_gouwuche" :style="{color:t('color1')}"></text>
						</view>
						<view  v-if="item.gwcnum && item.gwcnum>0" class="addnum" style="margin-top: 4rpx;">
							<view class="minus" @tap="gwcminus" :data-proid="item.proid"><image class="img" src="/static/img/cart-minus.png"/></view>
							<input class="input" type="number" :value="item.gwcnum?item.gwcnum:1" :data-proid="item.proid" @input="gwcinput"></input>
							<view class="plus" @tap="gwcplus" :data-proid="item.proid"><image class="img" src="/static/img/cart-plus.png"/></view>
						</view>
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
        	return {
                pre_url:'',
                datalist:'',
        		proid:0,
                pic:'',
                sell_price:'',
                vip_price:''
        	}
        },
		props: {
			params:{},
			data:{}
		},
        mounted:function(){
            var that = this;
            that.pre_url = app.globalData.pre_url;
        },
        updated:function(){
            var that = this;
            if(that.data){
                that.datalist = that.data;
            }else{
                that.datalist = '';
            }
        },
        methods: {
        	
            getdata:function(){
            	var that = this;
            	that.loading = true;
            	app.post('ApiXixie/getproductdetail',{id:that.proid},function(res){
            		that.loading = false;
            		that.product = res.product;
            		if(!that.product.limit_start){
            			that.product.limit_start = 1;
            		}
            		that.gwcnum = that.product.limit_start;
            		that.isload = true;
            	});
            },
            buydialogChange:function(){
            	this.$emit('buydialogChange');
            },
            tobuy: function (e) {
            	var that = this;
            	var ks = that.ks;
            	var proid = that.product.id;
            	var num = that.gwcnum;
            	if (num < 1) num = 1;
            	var prodata = proid + ',' + num;
            	this.$emit('buydialogChange');
            	app.goto('/xixie/buy?prodata=' + prodata);
            },
            //加
            gwcplus: function (e) {
                var that = this;
                var proid    =  e.currentTarget.dataset.proid;
				if(!that.datalist || that.datalist.length<=0){
					if(that.data && that.data.length>0){
						that.datalist = that.data;
					}
				}
                var datalist = that.datalist;
                var len      = datalist.length;
                
                var num  = 0;
                var add_i= -1;
                if(len>0){
                    for(var i=0;i<len;i++){
                        if(datalist[i]['proid'] == proid){
                            add_i = i;
                            //datalist[i]['gwcnum']+= 1;
                            num = datalist[i]['gwcnum']+1;
                            if(datalist[i]['buymax']>0 && datalist[i]['gwcnum']>datalist[i]['buymax']){
                                //datalist[i]['gwcnum'] = datalist[i]['buymax'];
                                num = datalist[i]['buymax'];
                                app.alert('每人限购'+datalist[i]['buymax']);
                                break;
                                return;
                            }
                            //num = datalist[i]['gwcnum'];
                        }
                    }
                }
                that.addcart(proid,num,add_i,datalist);
            },
            //减
            gwcminus: function (e) {
                var that = this;
                var proid =  e.currentTarget.dataset.proid;
				if(!that.datalist || that.datalist.length<=0){
					if(that.data && that.data.length>0){
						that.datalist = that.data;
					}
				}
                var datalist = that.datalist;
                var len  = datalist.length;
                var num  = 0;
                var add_i= -1;
                if(len>0){
                    for(var i=0;i<len;i++){
                        if(datalist[i]['proid'] == proid){
                            add_i = i;
                            //datalist[i]['gwcnum'] -= 1;
                            num = datalist[i]['gwcnum']-1;
                            if(datalist[i]['gwcnum']<=0){
                                //datalist[i]['gwcnum'] = 0;
                                num = 0;
                                //that.addshow = false;
                            }
                            //num = datalist[i]['gwcnum'];
                        }
                    }
                }
                that.addcart(proid,num,add_i,datalist);
            },
            //输入
            gwcinput: function (e) {
            	var ks = this.ks;
            	var input_num = parseInt(e.detail.value);
            	if (input_num !== input_num){
                    input_num = 1;
                } 
                var that = this;
                var proid    = e.currentTarget.dataset.proid;
				if(!that.datalist || that.datalist.length<=0){
					if(that.data && that.data.length>0){
						that.datalist = that.data;
					}
				}
                var datalist = that.datalist;
                var len      = datalist.length;
                var num = 0;
                var add_i= -1;
                if(len>0){
                    for(var i=0;i<len;i++){
                        if(datalist[i]['proid'] == proid){
                            add_i = i;
                            //datalist[i]['gwcnum'] = input_num;
                            num = input_num;
                            if(datalist[i]['buymax']>0 && datalist[i]['gwcnum']>datalist[i]['buymax']){
                                // datalist[i]['gwcnum'] = datalist[i]['buymax'];
                                // num = datalist[i]['gwcnum'];
                                num = datalist[i]['buymax'];
                                app.alert('每人限购'+datalist[i]['buymax']);
                                break;
                                return;
                            }
                            if(datalist[i]['gwcnum']<=0){
                                //datalist[i]['gwcnum'] = 0;
                                num = 0;
                                //that.addshow = false;
                            }
                            //num = datalist[i]['gwcnum'];
                        }
                    }
                }
                that.addcart(proid,num,add_i,datalist);
            },
            //加入购物车操作
            addcart: function (proid,num,i,datalist) {
            	var that = this;
                app.post('ApiXixie/addcart', {proid: proid,num: num}, function (res) {
                    if (res.status == 1) {
                        if(num<=0){
                            that.addshow = false;
                        }
                        if(i>=0){
                            datalist[i]['gwcnum'] = num;
                        }
                        that.datalist = datalist;
                        that.$emit('getdata');
                    } else {
                        app.error(res.msg);
                    }
                });
            	//this.$emit('addcart',{proid: proid,num: num});
            	this.$emit('buydialogChange');
            },
        }
	}
</script>
<style>
.dp-collage{height: auto; position: relative;overflow: hidden; padding: 0px; background: #fff;}
.dp-collage-item{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-collage-item .item{display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:10rpx;overflow:hidden}
.dp-collage-item .product-pic {width: 100%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 100%;position: relative;}
.dp-collage-item .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-collage-item .product-pic .saleimg{ position: absolute;width: 60px;height: auto; top: -3px; left:-3px;}
.dp-collage-item .product-info {padding:20rpx 20rpx;position: relative;}
.dp-collage-item .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-collage-item .product-info .p2{display:flex;align-items:center;overflow:hidden;padding:2px 0}
.dp-collage-item .product-info .p2-1{flex-grow:1;flex-shrink:1;height:40rpx;line-height:40rpx;overflow:hidden;white-space: nowrap}
.dp-collage-item .product-info .p2-1 .t1{font-size:30rpx;}
.dp-collage-item .product-info .p2-1 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-collage-item .product-info .p2-2{font-size:20rpx;height:40rpx;line-height:40rpx;text-align:right;padding-left:20rpx;color:#999}
.dp-collage-item .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx;justify-content:space-between}
.dp-collage-item .product-info .p3-1{height:40rpx;line-height:40rpx;border:0 #FF3143 solid;border-radius:10rpx;color:#FF3143;padding:0 24rpx;font-size:24rpx}
.dp-collage-item .product-info .p3-2{color:#999999;font-size:20rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;}

.dp-collage-itemlist{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-collage-itemlist .item{width:100%;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;display:flex;padding:20rpx;border-radius:10rpx}
.dp-collage-itemlist .product-pic {width: 30%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 30%;position: relative;border-radius:4px;}
.dp-collage-itemlist .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-collage-itemlist .product-pic .saleimg{ position: absolute;width: 120rpx;height: auto; top: -6rpx; left:-6rpx;}
.dp-collage-itemlist .product-info {width: 70%;padding:6rpx 10rpx 5rpx 20rpx;position: relative;}
.dp-collage-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-collage-itemlist .product-info .p2{margin-top:20rpx;height:60rpx;line-height:60rpx;overflow:hidden;}
.dp-collage-itemlist .product-info .p2 .t1{font-size:30rpx;}
.dp-collage-itemlist .product-info .p2 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-collage-itemlist .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx;justify-content:space-between}
.dp-collage-itemlist .product-info .p3-1{height:40rpx;line-height:40rpx;border:0 #FF3143 solid;border-radius:10rpx;color:#FF3143;padding:0 24rpx;font-size:24rpx}
.dp-collage-itemlist .product-info .p3-2{color:#999999;font-size:20rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;}

.dp-collage-itemline{width:100%;display:flex;overflow-x:scroll;overflow-y:hidden}
.dp-collage-itemline .item{width: 220rpx;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:10rpx;margin-right:4px}
.dp-collage-itemline .product-pic {width:220rpx;height:0;overflow:hidden;background: #ffffff;padding-bottom: 100%;position: relative;}
.dp-collage-itemline .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-collage-itemline .product-pic .saleimg{ position: absolute;width: 60px;height: auto; top: -3px; left:-3px;}
.dp-collage-itemline .product-info {padding:20rpx 20rpx;position: relative;}
.dp-collage-itemline .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-collage-itemline .product-info .p2{display:flex;align-items:center;overflow:hidden;padding:2px 0}
.dp-collage-itemline .product-info .p2-1{flex-grow:1;flex-shrink:1;height:40rpx;line-height:40rpx;overflow:hidden;white-space: nowrap}
.dp-collage-itemline .product-info .p2-1 .t1{font-size:36rpx;}
.dp-collage-itemline .product-info .p2-1 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-collage-itemline .product-info .p2-2{font-size:20rpx;height:40rpx;line-height:40rpx;text-align:right;padding-left:20rpx;color:#999}
.dp-collage-itemline .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx;justify-content:space-between}
.dp-collage-itemline .product-info .p3-1{height:40rpx;line-height:40rpx;border:0 #FF3143 solid;border-radius:10rpx;color:#FF3143;padding:0 24rpx;font-size:24rpx}
.dp-collage-itemline .product-info .p3-2{color:#999999;font-size:20rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;}

.buynum{ width: 94%; position: relative; margin: 0 3%; padding:10px 0px 10px 0px; }
.addnum {font-size: 30rpx;color: #666;width:auto;display:flex;align-items:center;float: right;}
.addnum .plus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.addnum .minus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.addnum .img{width:24rpx;height:24rpx}
.addnum .input{flex:1;width:70rpx;border:0;text-align:center;color:#2B2B2B;font-size:24rpx}

</style>