<template>
	<view class="container">
		<block v-if="isload">
			<view class="search-container">
				<view class="search-box">
					<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
					<input class="search-text" placeholder="输入关键词或餐桌号码" placeholder-style="color:#aaa;font-size:24rpx" @confirm="searchConfirm" @input="searchChange" :value="keyword"/>
				</view>
			</view>
		<view class="tab-box shopping">
			<view class="page-tab">
				<view class="page-tab2">
					<view :class="'item ' + (curTopIndex == -1 ? 'on' : '')" @tap="switchTopTab" :data-index="-1" :data-id="0">全部</view>
					<block v-for="(item, index) in clist" :key="index">
						<view :class="'item ' + (curTopIndex == index ? 'on' : '')" @tap="switchTopTab" :data-index="index" :data-id="item.id">{{item.name}}</view>
					</block>
				</view>
			</view>
			<view class="table-box">
				<view class="table-item bg-green" v-for="(item,index2) in contentList" :key="index2" @tap="selectTable" :class="{'bg-orange': item.status == 2,'bg-blue':item.status == 3,'bg-gray':item.status==-1}" :data-id="item.id" :data-tablename="item.name">
					<view class="shop-name multi-ellipsis-2">
						{{item.name}}
					</view>
					<view class="color-fff line40">座位：{{item.seat}}</view>
					<view class="color-fff" v-if="item.status == 0">空闲</view>
					<view class="color-fff" v-if="item.status == 2">用餐</view>
					<view class="color-fff" v-if="item.status == 3">清台</view>
					<view class="color-fff" v-if="item.status == -1">禁用</view>
				</view>
			</view>
		</view>
		</block>
		
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt"></dp-tabbar>
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
				
				pagenum:1,
				contentList:[],
				clist:[],
				curCid:0,
				curTopIndex: -1,
				curIndex: -1,
				keyword:'',
				logo:'',
				pre_url:app.globalData.pre_url
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.opt.bid = this.opt.bid ? this.opt.bid : 0;
			this.logo = app.globalData.initdata.logo;
			this.getdata();
		},
		onShow: function (opt) {
			this.getdata();
		},
		onPullDownRefresh: function () {
			this.getdata();
		},
		methods: {
			getdata: function () {
				var that = this;
				that.loading = true;
				var nowcid = that.opt.cid;
				if (!nowcid) nowcid = 0;
				app.get('ApiAdminRestaurantTableCategory/index', {}, function (res) {
					that.loading = false;
					var data = res.datalist;
					that.clist = data;
					// that.curCid = data[0]['id'];
					if (nowcid) {
						for (var i = 0; i < data.length; i++) {
							if (data[i]['id'] == nowcid) {
								that.curTopIndex = i;
								that.curCid = nowcid;
								break;
							}
						}
					}
					that.getTabContentList();
					that.loaded();
				});
			},
			
			switchTopTab: function (e) {
			  var that = this;
			  var id = e.currentTarget.dataset.id;
			  var index = parseInt(e.currentTarget.dataset.index);
			  this.curTopIndex = index;
			  this.curIndex = -1;
			  this.contentList = [];
			  this.curCid = id;
				this.pagenum = 1;
			  this.getTabContentList();
			},
			getTabContentList:function(){
				var that = this;
				var pagenum = that.pagenum;
				var postdata = {cid: that.curCid,pagenum: pagenum,keyword:that.keyword,tableStatus:''};
				if(that.opt.operate == 'change') {
					postdata.tableStatus = 0;
				}
				that.nodata = false;
				that.nomore = false;
				that.loading = true;
				app.post('ApiAdminRestaurantTable/index', postdata , function (res) {
					that.loading = false;
					var data = res.datalist;
					if (pagenum == 1) {
						that.contentList = data;
						if (data.length == 0) {
							that.nodata = true;
						}
						that.loaded();
					}else{
						if (data.length == 0) {
							that.nomore = true;
						} else {
							var contentList = that.contentList;
							var newdata = contentList.concat(data);
							that.contentList = newdata;
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
				that.getTabContentList();
			},
			selectTable: function(e) {
				var that = this;
				var id = e.currentTarget.dataset.id;
				var newname = e.currentTarget.dataset.tablename;
				if(that.opt.operate == 'change') {
					app.confirm('您确定更换为' + newname + '吗？', function(){
						that.loading = true;
						app.post('ApiAdminRestaurantTable/change', {origin: that.opt.origin,new: id}, function (res) {
							that.loading = false;
							if(res.status == 0){
								app.alert(res.msg);return;
							}
							if(res.status == 1){
								app.goto('tableWaiterDetail?id='+id)
							}
						});
					})
				} else {
					app.goto('tableWaiterDetail?id='+id)
				}
			}
		}
	}
</script>

<style>
	.search-container {width: 100%;height: 94rpx;padding: 16rpx 23rpx 14rpx 23rpx;background-color: #fff;position: relative;overflow: hidden;}
	.search-box {display:flex;align-items:center;height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
	.search-box .img{width:24rpx;height:24rpx;margin-right:10rpx;margin-left:30rpx}
	.search-box .search-text {font-size:24rpx;color:#C2C2C2;width: 100%;}
.page-tab{display:flex;width:100%;overflow-x:scroll;border-bottom: 1px #f5f5f5 solid;padding:0 10rpx; background-color: #FFFFFF;}
.page-tab2{display:flex;width:auto;min-width:100%}
.page-tab2 .item{width:auto;padding:0 20rpx;font-size:28rpx;text-align: center; color:#333; height:90rpx; line-height:90rpx; overflow: hidden;position:relative;flex-shrink:0;flex-grow: 1;}
.page-tab2 .on{color:#007AFF; font-size: 30rpx;}

.table-box {padding: 0 ; display: flex; flex-wrap: wrap;align-items: center;}
.table-item {background-color: #FFF; width: 220rpx;height: 220rpx; border-radius: 8rpx; align-items: center; margin: 10rpx; padding: 10rpx; text-align: center;display:flex; flex-direction: column;justify-content: center;}
.table-item .shop-name {font-size: 34rpx;}
.color-fff {color: #fff;}
.bg-green { background-color: #15BC84;}
.bg-orange { background-color: #FD943E;}
.bg-blue {background-color: #007AFF;}
.bg-gray{background: #bdbdbd;}
.line40 {line-height: 40rpx;}

.button{width: 100rpx;height:70rpx;line-height:70rpx;font-size:28rpx;color:#FFFFFF;  background: #007AFF;border-radius: 10rpx; text-align: center;}
.bottom-view {position: fixed; bottom: 0; width: 100%; height: 100rpx; background-color: #fff; padding: 20rpx;display: flex; flex-direction: row-reverse; align-items: center; box-shadow: 0px -10rpx 20rpx 0rpx rgb(0 0 0 / 20%);}

.button{margin:0 20rpx;width:220rpx;line-height:70rpx;color:#fff;border-radius:3px;text-align:center; background-color: #007AFF;}

</style>
