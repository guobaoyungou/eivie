<template>
<view>
	<block v-if="isload">

		<view class="content">
			<view class="list">
				
				<view class="item" v-for="(item,inex) in datalist" @tap="goto" :data-url="'detail?ismy=1&id='+item.id">
					<view class="pic">
						<image mode="widthFix" class="image" :src="item.zhengjian"></image>
					</view>
					<view class="info">
						<view class="p1">姓名：{{item.realname}}</view>
						<view class="p1">身份证号：{{item.name}}</view>
						<view class="p1">联系方式：{{item.tel}}</view>
						<view class="p1">审核状态：
							<text v-if="item.status ==1" style="color: green;">审核通过</text>
							<text v-if="item.status ==2" style="color: red;">审核驳回,{{item.check_reason}}</text>
							<text v-if="item.status ==0" >待审核</text>
						</view>
					</view>
				</view>
	
			</view>
		</view>
		
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
				keyword:''
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		onPullDownRefresh: function () {
			this.getdata();
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
				app.post('ApiBaomingxcx/getmylist', {pagenum: pagenum,keyword:keyword}, function (res) {
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
					that.loading = false;
				})
			},
			searchConfirm: function (e) {
			  var that = this;
			  var keyword = e.detail.value;
			  that.keyword = keyword
			  that.getdata();
			},
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
.content .list{margin-top: 10rpx;}
.content .list .item{ width: 100%;
    display: inline-block;
    position: relative;
    margin-bottom: 12rpx;
    background: #fff;
    display: flex;
    padding: 14rpx 0;
    border-radius: 10rpx;
    border-bottom: 1px solid #F8F8F8}
.content .list .item .pic{
	width: 50%;
	overflow: hidden;
	background: #ffffff;
	height: 200rpx;
}	
.content .list .item .pic .image {
    width: 100%;
}
.list .item .info{
	width: 70%;
	padding: 0 10rpx 5rpx 20rpx;
}
.list .item .info .p1{
	color: #323232;
	font-size: 28rpx;
	line-height: 50rpx;
	margin-bottom: 0;
	display: -webkit-box;
	-webkit-box-orient: vertical;
	-webkit-line-clamp: 2;
	overflow: hidden;
}
</style>