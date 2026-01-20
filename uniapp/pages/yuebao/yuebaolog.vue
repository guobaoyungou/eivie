<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="[t('余额宝')+'明细','提现记录']" :itemst="['0','1']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view class="content">
			<block v-if="st==0">
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="f1">
						<text class="t1">{{item.remark}}</text>
						<text class="t2">{{item.createtime}}</text>
						<text class="t3">变更后余额: {{item.after}}</text>
				</view>
				<view class="f2">
						<text class="t1" v-if="item.money>0">+{{item.money}}</text>
						<text class="t2" v-else>{{item.money}}</text>
				</view>
			</view>
			</block>
			<block v-if="st==1">
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="f1">
						<text class="t1">提现金额：{{item.money}}元</text>
						<text class="t2">{{item.createtime}}</text>
				</view>
				<view class="f3">
						<text class="t1" v-if="item.status==0">审核中</text>
						<text class="t1" v-if="item.status==1">已审核</text>
						<text class="t2" v-if="item.status==2">已驳回</text>
						<text class="t1" v-if="item.status==3">已打款</text>
				</view>
			</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
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

            canwithdraw:false,
            textset:{},
            st: 0,
            datalist: [],
            pagenum: 1,
            nodata:false,
            nomore: false
        };
    },

    onLoad: function (opt) {
        this.opt = app.getopts(opt);
        this.st = this.opt.st || 0;
        this.getdata();
    },
    onPullDownRefresh: function () {
        this.getdata(true);
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
            var st = that.st;
            that.nodata = false;
            that.nomore = false;
            that.loading = true;
            app.post('ApiMy/yuebaolog', {st: st,pagenum: pagenum}, function (res) {
                that.loading = false;
                var data = res.data;
                if (pagenum == 1) {
                    that.textset = app.globalData.textset;
                    uni.setNavigationBarTitle({
                        title: that.t('余额宝') + '明细'
                    });
                    that.canwithdraw = res.canwithdraw;
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
        },
        changetab: function (st) {
            this.st = st;
            uni.pageScrollTo({
                scrollTop: 0,
                duration: 0
            });
            this.getdata();
        },
    }
};
</script>
<style>
.container{ width:100%;margin-top:90rpx;display:flex;flex-direction:column}
.content{ width:94%;margin:0 3% 20rpx 3%;}
.content .item{width:100%;background:#fff;margin:20rpx 0;padding:20rpx 20rpx;border-radius:8px;display:flex;align-items:center}
.content .item:last-child{border:0}
.content .item .f1{width:500rpx;display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666}
.content .item .f1 .t3{color:#666666}
.content .item .f2{ flex:1;width:200rpx;font-size:36rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;width:200rpx;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}

.data-empty{background:#fff}
</style>