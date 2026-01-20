<template>
<view class="container">
	<block v-if="isload">
		<view class="content" id="datalist">
			<view class="item" @tap="goto" :data-url="'detail?tel='+item.tel+'&posterid='+item.id" v-for="(item, index) in datalist" :key="index">
				<view class="f1">
						<text class="t1">{{item.name}}</text>
						<text class="t2">姓名：{{item.realname}}</text>
						<text class="t2">身份证号：{{item.tel}}</text>
				</view>
			</view>
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

      datalist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,
			tel:'',
			tel2:'',
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.tel = this.opt.tel || '';
		this.tel2 = this.opt.tel2 || '';
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
	onNavigationBarSearchInputConfirmed:function(e){
		this.searchConfirm({detail:{value:e.text}});
	},
  methods: {
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
			this.nodata = false;
			this.nomore = false;
			this.loading = true;
      app.post('ApiCertificatePoster/record', {tel:that.tel,tel2:that.tel2,pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1) {
					that.datalist = data;
          if (data.length == 0) {
            that.nodata = true;
          }
					if(res.set && res.set.certificate_text){
						uni.setNavigationBarTitle({
							title: '我的'+res.set.certificate_text
						});
						that.certificate_text = res.set.certificate_text;
					}else{
						uni.setNavigationBarTitle({
							title: '我的成绩'
						});
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
		searchConfirm:function(e){
			this.keyword = e.detail.value;
      this.getdata(false);
		}
  }
}
</script>
<style>
.content{ width:100%;margin:0;}
.content .item{ width:94%;margin:20rpx 3%;;background:#fff;border-radius:16rpx;padding:30rpx 30rpx;display:flex;align-items:center;}
.content .item:last-child{border:0}
.content .item .f1{width:80%;display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666;margin-top:10rpx}
.content .item .f1 .t3{color:#666666}
.content .item .f2{width:20%;font-size:32rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;font-size:30rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}
</style>