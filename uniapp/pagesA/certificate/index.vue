<template>
<view>
	<block v-if="isload">
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="搜索感兴趣的证书" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" ></input>
				<button class="search-btn" @click="searchConfirm">搜索</button>
			</view>
		</view>
		<view class="content" v-if="datalist && datalist.length>0">
			<view class="list">
				<view class="item" v-for="(item,index) in datalist" @tap="viewDetail" :data-url="'detail?id='+item.id" :data-index="index">
					<view class="info">
						<view class="p1">证书名称：{{item.cname}}</view>
						<view class="p1 flex"><view class="row-l">联&nbsp; 系&nbsp;人：{{item.name}}</view><view class="row-r">联系方式：{{item.tel}}</view></view>
					</view>
				</view>
			</view>
		</view>
		<nodata v-if="nodata"></nodata>
		
	</block>
</view>
</template>

<script>
	var app = getApp();
	export default {
		data() {
			return {
				opt:{},
				loading:false,
				isload: true,
				nomore: false,
				nodata:false,
				pagenum: 1,
				datalist: [],
				keyword:'',
				set:{},
				pre_url:app.globalData.pre_url
			}
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
				var keyword = that.keyword;
				that.nodata = false;
				that.nomore = false;
				that.loading = true;
				app.post('ApiCertificate/getlist', {pagenum: pagenum,keyword:keyword}, function (res) {
					var data = res.data;
					if (pagenum == 1) {
						that.datalist = data;
						that.set = res.set;
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
					that.loading = false;
				})
			},
			searchConfirm: function (e) {
			  var that = this;
			  var keyword = e.detail.value;
			  that.keyword = keyword
			  that.getdata();
			},
			viewDetail:function(e){
			  var that = this;
				var url = e.currentTarget.dataset.url;
				var index = e.currentTarget.dataset.index;
				var alertText = that.set && that.set.uplevel_text ? that.set.uplevel_text : '无权限查看，请先升级'
				if(!that.datalist[index].auth){
					app.alert(alertText, function () {
					  app.goto(that.set.uplevel_url);
					});
					return;
				}
				app.goto(url);
			}
		}
	}
</script>

<style>
.topsearch{width:100%;padding:16rpx 20rpx;background-color: #fff;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.topsearch .f1 .camera {height:72rpx;width:72rpx;color: #666;border: 0px;padding: 0px;margin: 0px;background-position: center;background-repeat: no-repeat; background-size:40rpx;}
/* .topsearch .search-btn{display:flex;align-items:center;color:#5a5a5a;font-size:30rpx;width:60rpx;text-align:center;margin-left:20rpx} */
	.search-btn{width: 100rpx;border-radius: 50rpx;color: #fff;background: #342C2A;line-height: 48rpx;}

.content {
	background: #ffffff;
	padding: 0 20rpx;
	height: 100%;
}
.content .list{}
.content .list .item{ 
	width: 100%;
    display: inline-block;
    position: relative;
    display: flex;
    padding: 14rpx 0;
    border-radius: 10rpx;
		background-color: #f5f5f5;
		margin-bottom: 10rpx;
		}
.content .list .item .pic{
	width: 50%;
	overflow: hidden;
	background: #eee;
	height: 200rpx;
}	
.content .list .item .pic .image {
    width: 100%;
}
.list .item .info{
	width: 100%;
	padding: 0 10rpx 5rpx 20rpx;
}
.list .item .info .p1{
	color: #333;
	font-size: 28rpx;
	line-height: 70rpx;
	margin-bottom: 0;
	-webkit-box-orient: vertical;
	-webkit-line-clamp: 2;
	overflow: hidden;
}
.info .p1 .row-l {width: 40%;}
</style>