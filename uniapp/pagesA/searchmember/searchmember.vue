<template>
<view>
	<block v-if="isload">
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入昵称/姓名/手机号搜索" placeholder-style="font-size:28rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
			</view>
		</view>
		<view class="content" v-if="datalist && datalist.length>0">
			<view class="label">
				<text class="t1">共搜索到 {{count}} 条数据</text>
			</view>
			<block v-for="(item, index) in datalist" :key="index">
				<view class="item">
					<view class="f1" @tap="goto" :data-url="'memberdetail?mid=' + item.id">
						<image :src="item.headimg"></image>
						<view class="t2">
							<view class="x1 flex-y-center">
								{{item.nickname}}
								<image style="margin-left:10rpx;width:40rpx;height:40rpx" :src="pre_url+'/static/img/nan2.png'" v-if="item.sex==1"></image>
								<image style="margin-left:10rpx;width:40rpx;height:40rpx" :src="pre_url+'/static/img/nv2.png'" v-if="item.sex==2"></image>
							</view>
							<text class="x2" style="color:#666">{{item.realname ? item.realname : ''}} {{item.tel ? item.tel : ''}}</text>
							<text class="x2">{{item.createtime}}</text>
							<text class="x2" v-if="item.remark" style="color:#a66;font-size:22rpx">{{item.remark}}</text>
						</view>
					</view>
					<view class="op">
						<view class="btn2" @tap.stop="goto" :data-url="'jdorderlist?mid='+item.id">预约订单</view>
						<view class="btn2" @tap.stop="goto" :data-url="'couponrecord?mid='+item.id">计次卡</view>
						<view class="btn2" @tap.stop="goto" :data-url="'formlog?tel='+item.tel" v-if="item.tel">病例档案</view>
					</view>
				</view>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata" text="没有搜索到相关信息"></nodata>
	</block>
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
			pre_url:app.globalData.pre_url,

      datalist: [],
      pagenum: 1,
      nomore: false,
			nodata:false,
      count: 0,
      keyword: '',
      auth_data: {},
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		//this.getdata();
		this.loaded();
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
      var keyword = that.keyword;
			if(!keyword) return;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiSearchMember/searchmember', {keyword: keyword,pagenum: pagenum}, function (res) {
        that.loading = false;
        var data = res.datalist;
        if (pagenum == 1) {
					that.datalist = data;
					that.count = res.count;
					that.auth_data = res.auth_data;
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
.topsearch{width:94%;margin:30rpx 3% 16rpx 3%;}
.topsearch .f1{height:90rpx;border-radius:45rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:30rpx;height:30rpx;margin-left:20px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:32rpx;color:#333;}

.content{width: 94%;margin:0 3%;border-radius:16rpx}
.content .label{display:flex;width: 100%;padding:24rpx 16rpx;color: #333;}
.content .label .t1{flex:1}
.content .label .t2{ width:300rpx;text-align:right}

.content .item{background: #fff;width: 100%;padding: 32rpx 32rpx 10rpx 32rpx;border-radius:16rpx;min-height: 112rpx;display:flex;flex-direction:column;margin-bottom:14rpx}
.content .item image{width:110rpx;height:110rpx;}
.content .item .f1{display:flex;flex:1;padding-bottom:20rpx;}
.content .item .f1 .t2{display:flex;flex-direction:column;padding-left:20rpx}
.content .item .f1 .t2 .x1{color: #222;font-size:30rpx;}
.content .item .f1 .t2 .x2{color: #999;font-size:24rpx}

.content .item .f2{display:flex;flex-direction:column;width:200rpx;text-align:right;border-left:1px solid #eee}
.content .item .f2 .t1{ font-size: 40rpx;color: #666;height: 40rpx;line-height: 40rpx;}
.content .item .f2 .t2{ font-size: 28rpx;color: #999;height: 50rpx;line-height: 50rpx;}

.content .op{ display:flex;flex-wrap: wrap;justify-content:flex-end;align-items:center;width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}

.content .btn1{margin-left:20rpx; margin-top: 10rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;}
.content .btn2{margin-left:20rpx; margin-top: 10rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;}

</style>