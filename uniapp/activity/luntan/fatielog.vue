<template>
    <view>
        <block v-if="isload">
            <view class="content" id="datalist">
            	<block v-for="(item, index) in datalist" :key="index"> 
                    <view class="item" style="overflow: hidden;" @tap="goto" :data-url="'detail?id=' + item.id">
                        <view style="width: 100rpx;">
                            <image :src="item.pic" style="width: 90rpx;height: 90rpx;"></image>
                        </view>
                        <view class="f1" style="width: 560rpx;float: right;">
                            <text class="t1">{{item.content}}</text>
                            <text class="t2">{{item.createtime}}</text>
                        </view>
                    </view>
                </block>
                <nodata v-if="nodata"></nodata>
            </view>
            <nomore v-if="nomore"></nomore>
        </block>
        <loading v-if="loading"></loading>
        <dp-tabbar :opt="opt"></dp-tabbar>
        <popmsg ref="popmsg"></popmsg>
    </view>
</template>

<script>
    var app = getApp();

    export default {
        data() {
            return {
                opt:{},
                loading:false,
                isload: false,
                menuindex:-1,
                pre_url:app.globalData.pre_url,
                st: 0,
                datalist: [],
                pagenum: 1,
                nodata: false,
                nomore: false,
            };
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
                var that = this;
                if(!loadmore){
                    that.pagenum = 1;
                    that.datalist = [];
                }
                var pagenum = that.pagenum;

                that.nodata = false;
                that.nomore = false;
                that.loading = true;
                app.post('ApiLuntan/fatielog', {pagenum: pagenum}, function (res) {
                    that.loading = false;
                    var data = res.data;
                    if (pagenum == 1) {
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
                });
            }
        }
    }
</script>

<style>

.content{ width:700rpx;margin:0 auto;}
.content .item{width:100%;margin:20rpx 0;background:#fff;border-radius:5px;padding:20rpx 20rpx;display:flex;align-items:center}
.content .item .f1{flex:1;display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:30rpx;overflow:hidden;text-overflow:ellipsis;white-space: nowrap;}
.content .item .f1 .t2{color:#666666}
.content .item .f1 .t3{color:#666666}
.content .item .f2{ flex:1;font-size:36rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}

</style>