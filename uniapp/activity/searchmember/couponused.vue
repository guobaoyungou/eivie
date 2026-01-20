<template>
<view class="container">
	<block v-if="isload">
		<!--<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" src="/static/img/search_ico.png"></image>
				<input :value="keyword" placeholder="输入姓名/手机号搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
			</view>
		</view>-->

	
		<view v-for="(item, index) in datalist" :key="index" class="content flex" :data-id="item.id">
				
			<view class="item" @click="goto"  >
				<view class="f1">
					<view class="name">次卡名称：{{item.couponname}}</view>
					<view class="f2">
						<text class="t1">{{item.remark}}</text>
						<text class="t2">使用时间：{{dateFormat(item.createtime)}}</text>
					</view>
				</view>

			</view>
	
		</view>
		<nodata v-if="nodata"></nodata>
		<nomore v-if="nomore"></nomore>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
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
      keyword: '',
      datalist: [],
      type: "",
		keyword:'',
		nodata:false,
		 curTopIndex: -1,
		 index:0,
		 curCid:0,
		 
    }
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.id = this.opt.id || '';
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
			var bid = that.opt.bid ? that.opt.bid : '';
			var rid = that.opt.id ? that.opt.id : '';
			var order = that.order;
		    var keyword = that.keyword;
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
			app.post('ApiSearchMember/couponused', {rid:rid,pagenum: pagenum}, function (res) { 
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

.content{width:94%;margin:20rpx 3%;background:#fff;border-radius:5px;padding:20rpx 40rpx; justify-content: space-between;}
.content .item { display: flex; justify-content: space-between; align-items:center; width: 100%;}
.content .item .name{ font-size: 30rpx; font-weight: bold;}
.content .f2 { display: flex; margin-top: 20rpx; flex-direction: column; }
.content .f2 .t1{color:#7A83EC; font-size:26rpx;margin-left:10rpx; margin: 10rpx 0;}
.content .f2 .t2{color:#999999;font-size:28rpx; font-size: 20rpx;}

</style>