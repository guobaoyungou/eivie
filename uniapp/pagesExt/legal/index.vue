<template>
    <view v-if="type_list">
        <view style="width: 100%;line-height: 80rpx;border-top:2rpx solid #eee;background-color: #fff;">
            <view style="width: 700rpx;margin: 0 auto;overflow: hidden;border-bottom:2rpx solid #eee">
                <view style="width: 100rpx;float: left;">
                    类型
                </view>
                <view style="float: right;overflow: hidden;text-algin:right">
                    <!--类型s-->
                    <picker  @change="typeChange"  :value="type_index" :range="type_list" range-key='name'>
                        <text>{{type_name}}</text>
                        <image :src="pre_url+'/static/img/arrowright.png'" style="width: 24rpx;height: 24rpx;float: right;margin-top: 28rpx;"></image>
                    </picker>
                    <!--类型e-->
                </view>
            </view>
            <view style="width: 700rpx;margin: 0 auto;overflow: hidden;border-bottom:2rpx solid #eee">
                <view style="width: 100rpx;float: left;">
                    法条
                </view>
                <view @tap="goto" data-url="detail" style="float: right;overflow: hidden;text-algin:right">
                    <text>{{law_name}}</text>
                    <image :src="pre_url+'/static/img/arrowright.png'" style="width: 24rpx;height: 24rpx;float: right;margin-top: 28rpx;"></image>
                </view>
            </view>
            <view v-if="type_id !=3" style="width: 700rpx;margin: 0 auto;overflow: hidden;border-bottom:2rpx solid #eee">
                <view style="width: 100rpx;float: left;">
                    标的
                </view>
                <view style="float: right;width: 600rpx;overflow: hidden;height: 70rpx;margin-top: 5rpx;">
                    <input type="number" @input="inputPrice" placeholder="请输入标的额" style="display: inline-block;width: 540rpx;height: 70rpx;line-height: 70rpx;background-color: #eee;padding-left: 10rpx"/>
                    <view style="float: right;text-align: right;height: 70rpx;line-height: 80rpx;">元</view>
                </view>
            </view>
        </view>

        <view style="width: 100%;line-height: 80rpx;background-color: #fff;margin-top: 40rpx;">
            <view style="width: 700rpx;margin: 0 auto;overflow: hidden;border-bottom:2rpx solid #eee">
                <view style="width: 100rpx;float: left;">
                    受理费
                </view>
                <view style="float: right;overflow: hidden;text-algin:right">
                    <text>{{price}}元</text>
                </view>
            </view>
            <view style="width: 700rpx;margin: 0 auto;overflow: hidden;border-bottom:2rpx solid #eee">
                <view style="width: 100rpx;float: left;">
                    保全费
                </view>
                <view style="float: right;overflow: hidden;text-algin:right">
                    <text>{{bq_price}}元</text>
                </view>
            </view>
            <view style="width: 700rpx;margin: 0 auto;overflow: hidden;border-bottom:2rpx solid #eee">
                <view style="width: 100rpx;float: left;">
                    执行费
                </view>
                <view style="float: right;overflow: hidden;text-algin:right">
                    <text>{{zx_price}}元</text>
                </view>
            </view>
        </view>
    </view>
</template>

<script>
var app = getApp();
export default {
	data() {
		return {
			opt:{},
			menuindex:-1,
			pre_url:app.globalData.pre_url,
            
            data:'',
            
            type_list:'',//类型列表
            type_index:0,
            type_id:'',//类型
            type_name:'',//类型名称

            law_name:'',//法条
            
            under_price:0,//类型低于金额
            each_min_price:0,//类型每件最小金额
            each_max_price:0,//类型每件最大金额
            
            price_list:'',//价格列表
            bd_price:'',//标的金额
            price:0,//受理费
            bq_price:0,//保全费
            zx_price:0,//执行费
            
            bq_list:'',//保全列表
            zx_list:''//执行用列表
		}
	},
	onLoad: function (opt) {
        var that = this;
		that.opt = app.getopts(opt);
		that.getdata();
	},
	onPullDownRefresh: function () {
		this.getdata();
	},
	methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiLegalFee/index',{},function (res){
                if(res.status == 1){
                    var data = res.data;
                    if(data){
                        that.data = data;
                        uni.setNavigationBarTitle({
                            title: data.title
                        });
                        
                        that.law_name = data.law_name;
                        
                        var type_list  = data.type_list;
                        that.type_list = type_list;
                        var len = type_list.length;
                        if(len>0){
                            that.type_list = type_list;
                            that.type_name = type_list[0]['name'];

                            var items  = type_list[0]['items'];
                            if(items){
                                that.price_list    = type_list[0]['items'];
                            }
                            
                            that.under_price   = parseFloat(type_list[0]['under_price']);
                            //受理费
                            var each_min_price = parseFloat(type_list[0]['each_min_price']);
                            var each_max_price = parseFloat(type_list[0]['each_max_price']);
                            if(each_min_price != each_max_price){
                                that.price = each_min_price+'-'+each_max_price;
                            }
                            that.each_min_price = each_min_price;
                            that.each_max_price = each_max_price;
                        }
                        
                        var bq_list = data.bq_list;
                        that.bq_list = bq_list;
                        
                        var zx_list = data.zx_list;
                        that.zx_list = zx_list;
                        var zx_len = type_list.length;
                        if(zx_len>0){
                            //执行费
                            var zx_each_min_price = parseFloat(zx_list[0]['each_min_price']);
                            var zx_each_max_price = parseFloat(zx_list[0]['each_max_price']);
                            if(zx_each_min_price != zx_each_max_price){
                                that.zx_price = zx_each_min_price+'-'+zx_each_max_price;
                            }
                        }
                    }
                }else{
                    if(res.msg){
                        app.alert(res.msg);
                    }else{
                        app.alert('系统暂时无法访问');
                    }
                }
				
			});
		},
        typeChange:function(e){
            var that = this;
            
            var type_index = e.detail.value;
            that.type_index = type_index;
            that.type_name  = that.type_list[type_index]['name'];
            
            that.under_price   = parseFloat(that.type_list[type_index]['under_price']);
            //受理费
            var each_min_price = parseFloat(that.type_list[type_index]['each_min_price']);
            var each_max_price = parseFloat(that.type_list[type_index]['each_max_price']);
            that.each_min_price = each_min_price;
            that.each_max_price = each_max_price;

            var type_list    = that.type_list;
            var items     = type_list[type_index]['items'];
            that.price_list = items;
            var len = items.length;
            if(items &&len>0){
                that.calPrice();
            }else{
                if(each_min_price != each_max_price){
                    that.price = each_min_price+'-'+each_max_price;
                }else{
                    that.price = each_min_price;
                }
                
            }
        },
        inputPrice:function(e){
            var that = this;
            var value = e.detail.value;
                value = parseInt(value);
            that.bd_price = value;
            that.calPrice();
        },
        calPrice:function(){
            var that = this;
            var value = parseFloat(that.bd_price);
            if(value<0){
                app.alert('标的额必须大于0');
                return;
            }
            if(isNaN(value)){
                value =0;
            }
            var each_min_price = parseFloat(that.each_min_price);
            var each_max_price = parseFloat(that.each_max_price);
            
            //受理费
            var price_list = that.price_list;
            var len = price_list.length;
            if(!price_list || len<=0){
                
                if(each_min_price != each_max_price){
                    that.price = each_min_price+'-'+each_max_price;
                }else{
                    that.price = each_min_price;
                }
            }else{
                //低于金额
                var under_price = that.under_price*10000;
                if(value == 0){
                    that.price = 0;
                }else{
                    if(under_price >0 && value<=under_price){
                        if(each_min_price != each_max_price){
                            that.price = each_min_price+'-'+each_max_price;
                        }else{
                            that.price = each_min_price;
                        }
                    }else{
                        var price = 0;
                        for(var i=0;i<len;i++){
                            var start_price = price_list[i]['start_price']*10000;
                            var end_price   = price_list[i]['end_price']*10000;
                            var ratio       = price_list[i]['ratio'];
                            if(end_price>0){
                                if(value>end_price && value>start_price){
                                    price += (end_price - start_price)*(ratio/100);
                                }
                            }else{
                                if(value>start_price){
                                    price += (value - start_price)*(ratio/100);
                                }
                            }
                            
                            if(value<=end_price && value>start_price){
                                price += (value - start_price)*(ratio/100);
                            }
                        }
                        price = Math.round(price*100)/100;

                        if(each_min_price != each_max_price){
                            var min_price = price+each_min_price;
                            var max_price = price+each_max_price;
                            price = min_price+'-'+max_price;
                        }else{
                            price = price+each_min_price;
                        }
                        that.price = price;
                    }
                }
            }
            
            //保全费
            var bq_list = that.bq_list;

            var bq_len = bq_list.length;
            if(bq_list && bq_len>0){
                //低于金额
                var bq_under_price    = bq_list[0]['under_price']*10000;
                //每件金额
                var bq_each_min_price = bq_list[0]['each_min_price'];
                
                //最高金额
                var bq_max_price      = bq_list[0]['max_price'];
                
                if(bq_under_price>0 && value<=bq_under_price){
                    if(bq_each_min_price>=bq_max_price){
                        that.bq_price = bq_max_price;
                    }else{
                        that.bq_price = bq_each_min_price;
                    }
                }else{
                    var bq_items = bq_list[0]['items'];
                    var bi_len   = bq_items.length;
                    
                    if(bq_items && bi_len>0){
                        var bq_price = 0;
                        for(var i=0;i<bi_len;i++){
                            var start_price = bq_items[i]['start_price']*10000;
                            var end_price   = bq_items[i]['end_price']*10000;
                            var ratio       = bq_items[i]['ratio'];
                            if(end_price>0){
                                if(value>end_price && value>start_price){
                                    bq_price += (end_price - start_price)*(ratio/100);
                                }
                            }else{
                                if(value>start_price){
                                    bq_price += (value - start_price)*(ratio/100);
                                }
                            }
                            
                            if(value<=end_price && value>start_price){
                                bq_price += (value - start_price)*(ratio/100);
                            }
                        }
                        bq_price = Math.round(bq_price*100)/100;
                        
                        if(bq_under_price>0 && bq_each_min_price>0){
                            bq_price += bq_each_min_price;
                        }
                        if(bq_price>=bq_max_price){
                            bq_price = bq_max_price;
                        }
                        that.bq_price = bq_price;
                    }else{
                        if(bq_under_price>0){
                            that.bq_price = bq_each_min_price;
                        }else{
                            that.bq_price = 0;
                        }
                    }
                }
            }else{
                that.bq_price = 0;
            }
            
            //执行费
            var zx_list = that.zx_list;
            
            var zx_len = zx_list.length;
            if(zx_list && zx_len>0){
                //低于金额
                var zx_under_price       = zx_list[0]['under_price']*10000;
                //每件最低金额
                var zx_each_min_price    = zx_list[0]['each_min_price'];
                //每件最高金额
                var zx_each_max_price = zx_list[0]['each_max_price'];
                
                if(zx_under_price>0 && value<=zx_under_price){
                    if(zx_each_min_price != zx_each_max_price){
                        that.zx_price = zx_each_min_price+"-"+zx_each_max_price;
                    }else{
                        that.zx_price = zx_each_min_price;
                    }
                }else{
                    var zx_items = zx_list[0]['items'];
                    var bi_len   = zx_items.length;
                    
                    if(zx_items && bi_len>0){
                        var zx_price = 0;
                        for(var i=0;i<bi_len;i++){
                            var start_price = zx_items[i]['start_price']*10000;
                            var end_price   = zx_items[i]['end_price']*10000;
                            var ratio       = zx_items[i]['ratio'];
                            if(end_price>0){
                                if(value>end_price && value>start_price){
                                    zx_price += (end_price - start_price)*(ratio/100);
                                }
                            }else{
                                if(value>start_price){
                                    zx_price += (value - start_price)*(ratio/100);
                                }
                            }
                            
                            if(value<=end_price && value>start_price){
                                zx_price += (value - start_price)*(ratio/100);
                            }
                        }
                        zx_price = Math.round(zx_price*100)/100;
                        
                        if(zx_each_min_price != zx_each_max_price){
                            var min_price = price+zx_each_min_price;
                            var max_price = price+zx_each_max_price;
                            zx_price = min_price+'-'+max_price;
                        }else{
                            zx_price = zx_price+zx_each_min_price;
                        }
                        that.zx_price = zx_price;
                    }else{
                        that.zx_price = 0;
                    }
                }
            }else{
                that.zx_price = 0;
            }
        }
	}
};
</script>
<style>
</style>
