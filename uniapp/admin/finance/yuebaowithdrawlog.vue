<template>
<view>
	<block v-if="isload">
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" :placeholder="'输入'+t('会员')+'昵称搜索'" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
			</view>
		</view>
		<view class="tabcontent">
			<dd-tab :itemdata="['全部','审核中','已审核','已驳回','已打款']" :itemst="['all','0','1','2','3']" :st="st" @changetab="changetab"></dd-tab>
		</view>
		<view class="content">
			<view v-for="(item, index) in datalist" :key="index" class="item" @tap="goto" :data-url="'yuebaowithdrawdetail?id=' + item.id">
				<view class="f1">
						<image class="t1" :src="item.headimg"></image>
						<text class="t2">{{item.nickname}}</text>
				</view>
				<view class="f2">
						<text class="t1">提现金额：{{item.money}}元</text>
						<text class="t2">{{dateFormat(item.createtime)}}</text>
						<text class="t2">提现方式：{{item.paytype}}</text>
				</view>
				<view class="f3">
						<text class="t1" v-if="item.status==0">审核中</text>
						<text class="t1" v-if="item.status==1">已审核</text>
						<text class="t2" v-if="item.status==2">已驳回</text>
						<text class="t1" v-if="item.status==3">已打款</text>
				</view>
			</view>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
	</block>
	<loading v-if="loading"></loading>
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

            keyword:'',
            st: 'all',
            count:0,
            datalist: [],
            pagenum: 1,
            nodata: false,
            nomore: false,
            pre_url: app.globalData.pre_url
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
        if(!loadmore){
            this.pagenum = 1;
            this.datalist = [];
        }
        var that = this;
        var pagenum = that.pagenum;
        var st = that.st;
        var keyword = that.keyword;
        that.nodata = false;
        that.nomore = false;
        that.loading = true;
        app.post('ApiAdminFinance/yuebaowithdrawlog', {keyword:keyword,pagenum: pagenum,st:st}, function (res) {
            that.loading = false;
            var data = res.data;
            if (pagenum == 1){
                uni.setNavigationBarTitle({
                    title: that.t('余额宝') + '提现记录'
                });
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
    searchChange: function (e) {
        this.keyword = e.detail.value;
    },
    searchConfirm: function (e) {
        var that = this;
        var keyword = e.detail.value;
        that.keyword = keyword;
        that.getdata();
    }
  }
};
</script>
<style>
.topsearch{width:94%;margin:16rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

.tabcontent{width:94%;margin:16rpx 3%;overflow:hidden}
.content{ width:94%;margin:0 3%;border-radius:16rpx;background:#fff;}
.content .item{ width:100%;padding:20rpx 20rpx;border-bottom:1px solid #f6f6f6;display:flex;align-items:center}
.content .item:last-child{border:0}
.content .item .f1{display:flex;flex-direction:column;margin-right:20rpx}
.content .item .f1 .t1{width:100rpx;height:100rpx;margin-bottom:10rpx;border-radius:50%}
.content .item .f1 .t2{color:#666666;text-align:center}
.content .item .f1 .t3{color:#666666}
.content .item .f2{ flex:1;font-size:30rpx;display:flex;flex-direction:column}
.content .item .f2 .t1{color:#03bc01;font-size:32rpx;height:50rpx;line-height:50rpx}
.content .item .f2 .t2{color:#999;font-size:28rpx;height:40rpx;line-height:40rpx}
.content .item .f2 .t3{color:#aaa;font-size:28rpx;height:40rpx;line-height:40rpx}
.content .item .f3{ font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}

.team-box2{width: 100%;float: left;padding: 8px;box-sizing: border-box;margin-bottom: 5px;position: relative;min-height: 56px;background: #fff;}
.team-box3{width: 75%;box-sizing: border-box;float: left;border-right: 1px #eaeaea solid;}
.team-box4{width: 25%;text-align: right;float: left;box-sizing: border-box;}
.team-sp1{ display: block;width: 100%;height: 25px;line-height: 25px;overflow: hidden;color: #666;font-size: 16px;}
.team-sp2{ display: block;width: 100%;height: 20px;line-height: 20px;overflow: hidden;color: #999;}
.team-sp3{ display: block;width: 100%;height: 45px;line-height: 45px;text-align: center;overflow: hidden;color: red;}
.team-sp4{ display: block;width: 100%;height: 45px;line-height: 45px;text-align: center;overflow: hidden;color: #ff6801;}
</style>