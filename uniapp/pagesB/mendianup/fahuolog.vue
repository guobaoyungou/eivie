<template>
<view>
	<block v-if="isload">
		<view class="content" v-if="datalist && datalist.length>0">
			<view class="label">
				<text class="t1">拣货单记录（共{{count}}条）</text>
			</view>
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="f1">
					<text class="t2">总件数：{{item.number}}</text>
				</view>
				<view class="f2">
					<text class="t2">{{dateFormat(item.createtime)}}</text>
					<text class="t3" v-if="item.status==0">未收货</text>
					<text class="t3" v-if="item.status==1">已收货</text>
				</view>
				<view class="f3">
					<text class="t3 btn2" @tap="goto" :data-url="'/pagesB/mendianup/fahuoinfo?oid='+item.id">查看</text>
					<text class="t3 btn2" v-if="item.status==0" @tap="shouhuo(item.id)">收货</text>
				</view>
			</view>
		</view>
		<nodata v-if="nodata"></nodata>
		<nomore v-if="nomore"></nomore>
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
			count:0,
      datalist: [],
      pagenum: 1,
      nodata: false,
      nomore: false
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
	onNavigationBarSearchInputConfirmed:function(e){
		this.searchConfirm({detail:{value:e.text}});
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
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiMendianCenter/jianhuodan', {pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1){
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
    searchChange: function (e) {
      this.keyword = e.detail.value;
    },
    searchConfirm: function (e) {
      var that = this;
      var keyword = e.detail.value;
      that.keyword = keyword;
      that.getdata();
    },
		shouhuo: function (oid) {
			
      var that = this;
			var oid = oid;
			app.confirm('确定要收货吗?', function () {
				app.showLoading('收货中');
				app.get('ApiMendianCenter/shouhuoall', {oid:oid}, function (res) {
					if(res.status==1){
						app.success(res.msg);
						setTimeout(function () {
							that.getdata();
						}, 1000);
					}else{
						app.error(res.msg)
					}
				});
			})
    }
  }
};
</script>
<style>
.topsearch{width:94%;margin:16rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

.content{width: 94%;margin:0 3%;background: #fff;border-radius:16rpx}
.content .label{display:flex;width: 100%;padding:24rpx 16rpx;color: #333;}
.content .label .t1{flex:1}
.content .label .t2{ width:300rpx;text-align:right}

.content .item{ width:100%;padding:20rpx 20rpx;border-top: 1px #f5f5f5 solid;display:flex;align-items:center}
.content .item .f1{display:flex;flex-direction:column;margin-right:20rpx}
.content .item .f1 .t1{width:100rpx;height:100rpx;margin-bottom:10rpx;border-radius:50%;margin-left:20rpx}
.content .item .f1 .t2{color:#666666;text-align:center;width:200rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.content .item .f2{ flex:1;width:200rpx;font-size:30rpx;display:flex;flex-direction:column}
.content .item .f2 .t1{color:#03bc01;line-height:36rpx;font-size:28rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.content .item .f2 .t2{color:#999;height:40rpx;line-height:40rpx;font-size:24rpx}
.content .item .f2 .t3{color:#aaa;height:40rpx;line-height:40rpx;font-size:24rpx}

.btn2{margin-left:20rpx;margin-top: 10rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 6rpx;font-size: 24rpx;}
</style>