<template>
<view class="container">
	<block v-if="isload">
		<block>
			<!-- <dd-tab :itemdata="tabdata" :itemst="tabitems" :st="st" :isfixed="false" @changetab="changetab" ></dd-tab> -->
		</block>
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入昵称/姓名/手机号搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
			</view>
		</view>
		<view class="content" v-if="datalist && datalist.length>0">
			<!-- 给下级升级 人数 -->
			<view class="label">
				<text class="t1">成员信息</text>
			</view>
			<block v-for="(item, index) in datalist" :key="index">
				<view class="item">
					<view class="f1">
						<image :src="item.headimg" mode="widthFix"></image>
						<view class="t2">
							<text class="x1">{{item.nickname}}(ID:{{item.mid}})</text>
							<text class="x2">等级：{{item.level_name}}</text>
							<text class="x2" v-if="item.tel">手机号：{{item.tel}}</text>
							<text class="x2" v-if="item.pid">推荐人ID：{{item.pid}}</text>
						</view>
					</view>
				</view>
			</block>
		</view>
	</block>
	<nodata v-if="nodata"></nodata>
	<nomore v-if="nomore"></nomore>
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
		st: 1,
		datalist: [],
		pagenum: 1,
		userlevel:{},
		userinfo:{},
		keyword:'',
		nodata: false,
		nomore: false,
		mid:0,
		tabdata:[],
		tabitems:[],
		pre_url: app.globalData.pre_url,
		active_id:0,
		net_path_level:1
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.active_id = this.opt.active_id;
		this.net_path_level = this.opt.net_path_level || 1;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1
      this.getdata(true);
    }
  },
  methods: {
    getdata: function (loadmore) {
		if(!loadmore){
			this.pagenum = 1;
			this.datalist = [];
			this.is_end = 0;
		}
		var that = this;
		var st = that.st;
		var pagenum = that.pagenum;
		var keyword = that.keyword;
		var active_id = that.active_id;
		var net_path_level = that.net_path_level;
		that.loading = true;
		that.nodata = false;
		that.nomore = false;
		var mid = that.mid;
		
      app.post('ApiNetworkHelp/netteam', {pagenum: pagenum,keyword:keyword,active_id:active_id,net_path_level:net_path_level}, function (res) {
        var data = res.datalist;
        if (pagenum == 1) {
			that.userinfo = res.userinfo;
			that.datalist = data;
			if (data.length == 0) {
				that.nodata = true;
			}
			uni.setNavigationBarTitle({
				title: '我的人脉'
			});
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
		that.loading = false;
      });
    },
    changetab: function (st) {
		if(this.loading) return;
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
    },
	}
};
</script>
<style>

.topsearch{width:94%;margin:16rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

.content{width:94%;margin:0 3%;border-radius:16rpx;background: #fff;margin-top: 20rpx;}
.content .label{display:flex;width: 100%;padding: 16rpx;color: #333;}
.content .label .t1{flex:1}
.content .label .t2{ width:300rpx;text-align:right}

.content .item{width: 100%;padding:32rpx 20rpx;border-top: 1px #eaeaea solid;min-height: 112rpx;display:flex;align-items:center;}
.content .item image{width: 90rpx;height: 90rpx;border-radius:4px}
.content .item .f1{display:flex;flex:1;align-items:center;}
.content .item .f1 .t2{display:flex;flex-direction:column;padding-left:20rpx}
.content .item .f1 .t2 .x1{color: #333;font-size:26rpx;}
.content .item .f1 .t2 .x2{color: #999;font-size:24rpx;}
.content .item .f1 .t2 .x3{font-size:24rpx;}

</style>