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
                    省份
                </view>
                <view style="float: right;overflow: hidden;text-algin:right">
                    <!--省份s-->
                    <picker  @change="provinceChange"  :value="province_index" :range="province_list">
                        <text>{{province_name}}</text>
                        <image :src="pre_url+'/static/img/arrowright.png'" style="width: 24rpx;height: 24rpx;float: right;margin-top: 28rpx;"></image>
                    </picker>
                    <!--省份e-->
                </view>
            </view>
            <view style="width: 700rpx;margin: 0 auto;overflow: hidden;border-bottom:2rpx solid #eee">
                <view style="width: 100rpx;float: left;">
                    依据
                </view>
                <view @tap="goto" :data-url="'detail?type_id='+type_id+'&province_name='+province_name" style="float: right;overflow: hidden;text-algin:right">
                    <text>{{yj_name}}</text>
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

        <view v-if="type_id !=3 && price" style="width: 100%;line-height: 80rpx;background-color: #fff;margin-top: 40rpx;">
            <view style="width: 700rpx;margin: 0 auto;overflow: hidden;border-bottom:2rpx solid #eee">
                <view style="width: 100rpx;float: left;">
                    律师费
                </view>
                <view style="float: right;overflow: hidden;text-algin:right">
                    <text>{{price}}元</text>
                </view>
            </view>
        </view>
        
        <view v-if="type_id ==3 && xs_list" style="width: 100%;line-height: 80rpx;background-color: #fff;margin-top: 40rpx;">
            <view v-for="(item,index) in xs_list" style="width: 700rpx;margin: 0 auto;overflow: hidden;border-bottom:2rpx solid #eee">
                <view style="float: left;">
                    {{item.stage}}
                </view>
                <view style="float: right;overflow: hidden;text-algin:right">
                    <text>{{item.start_price}}~{{item.end_price}}元</text>
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
            
            province_list:'',//城市列表
            province_index:0,
            province_name:'',//城市名称
            
            
            yj_name:'',//依据
            
            min_price:'',//最小金额
            
            
            price_list:'',//价格列表
            bd_price:'',//标的金额
            price:'',//律师费
            
            xs_list:''//刑事费用列表
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
			app.get('ApiCounselFee/index',{},function (res){
                if(res.status == 1){
                    var data = res.data;
                    if(data){
                        that.data = data;
                        uni.setNavigationBarTitle({
                            title: data.title
                        });
                    
                        var type_list = data.type_list;
                        var len = type_list.length;
                        if(len>0){
                            that.type_list = type_list;
                            that.type_id   = type_list[0]['id'];
                            that.type_name = type_list[0]['name'];
                            
                            var guigedata  = type_list[0]['guigedata'];
                            if(guigedata){
                                that.province_name = guigedata[0]['province'];
                                that.yj_name       = guigedata[0]['yj_name'];

                                if(type_list[0]['id'] != 3){
                                    that.min_price       = guigedata[0]['min_price'];
                                }
                                that.price_list    = guigedata[0]['items'];
                            }
                        }
                        that.province_list  = data.province_list;
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
            
            var type_id    = that.type_list[type_index]['id'];
            that.type_id   = type_id;

            var type_list     = that.type_list;
            var province_name = that.province_name;
            var guigedata     = type_list[type_index]['guigedata'];
            
            if(guigedata){
                var len = guigedata.length;
                if(len>0){
                    var have = 0;
                    for(var i=0;i<len;i++){
                        var province = guigedata[i]['province'];
                        if(province_name == province){
                            have = 1;
                            if(type_id != 3){
                                that.price_list = guigedata[i]['items'];
                                that.yj_name    = guigedata[i]['yj_name'];
                                that.min_price  = guigedata[i]['min_price'];
                                that.calPrice();
                            }else{
                                that.bd_price = 0;
                                that.price    = 0;
                                that.xs_list  = guigedata[i]['items'];
                            }
                        }
                    }
                    if(!have){
                        
                        that.resetsome();
                    }
                }else{

                    that.resetsome();
                }
            }else{

                that.resetsome();
            }
        },
        provinceChange:function(e){
           
            var that = this;
            var province_index = e.detail.value;
            that.province_index = province_index;
            
            var province_name   = that.province_list[province_index];
            that.province_name  = province_name;
            
            var type_index     = that.type_index;
            var type_name      = that.type_name;
            var type_id        = that.type_id;
            var type_list      = that.type_list;
            var guigedata      = type_list[type_index]['guigedata'];
            if(guigedata){
                var len = guigedata.length;
                if(len>0){
                    var have = 0;
                    for(var i=0;i<len;i++){
                        var province = guigedata[i]['province'];
                        if(province_name == province){
                            have = 1;
                            console.log(guigedata[i]);
                            if(type_id != 3){
                                that.price_list = guigedata[i]['items'];
                                that.yj_name    = guigedata[i]['yj_name'];
                                that.min_price  = guigedata[i]['min_price'];
                                that.calPrice();
                            }else{
                                that.bd_price = 0;
                                that.price    = 0;
                                that.xs_list  = guigedata[i]['items'];
                            }
                        }
                    }
                    if(!have){

                        that.resetsome();
                    }
                }else{

                    that.resetsome();
                }
            }else{

                that.resetsome();
            }
        },
        //重置一些数据
        resetsome:function(){
            var that = this;
            that.price_list = [];
            that.yj_name    = '';
            that.price      = 0;
            that.min_price  = 0;
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
            var value = that.bd_price;
            if(value<=0){
                that.price = 0;
                return;
            }

            var price_list = that.price_list;
            if(!price_list){
                that.price = 0;
                return;
            }
            
            var len = price_list.length;
            if(len>0){
                var min_price = that.min_price;
                var price = 0;
                for(var i=0;i<len;i++){
                    var start_price = price_list[i]['start_price']*10000;
                    var end_price   = price_list[i]['end_price']*10000;
                    var ratio       = price_list[i]['ratio'];
                    
                    if(end_price>0){
                        if(value>end_price && value>start_price){
                            price += (end_price - start_price)*(ratio/100);
                            console.log(price)
                        }
                    }else{
                        if(value>start_price){
                            price += (value - start_price)*(ratio/100);
                            console.log(price)
                        }
                    }
                    
                    if(value<=end_price && value>start_price){
                        price += (value - start_price)*(ratio/100);
                        console.log(price)
                    }
                }

                if(price<=min_price){
                    price = min_price;
                }
                that.price = parseInt(price);
                return;
            }else{
                that.price = 0;
                return;
            }
            
        }
	}
};
</script>
<style>
</style>
