<template>
<view class="container">
	<block v-if="isload">
		<view class="top">
			<view class="top-search">
				<image :src="pre_url + '/static/img/search_ico.png'" class="search-icon">
				<input class="input" type="text" @confirm="searchMember" v-model="keyword" placeholder="输入昵称|姓名|手机号添加好友" placeholder-style="font-size:26rpx;color:#999">
			</view>
		</view>
		<view class="box" v-if="datalist.length>0">
			<view class="itembox">
				<block v-for="(item,index) in datalist">
					<view class="item flex-sb">
						<view class="left">
							<image class="headimg" :src="item.headimg">
							<view class="info">
								<view class="nickname txthide">{{item.nickname}}</view>
								<view class="desc">等级：{{item.levelname}}</view>
								<view class="desc txthide"><!-- 性别：{{item.sex==1?'男':'女'}} | -->地区：{{item.area?item.area:'未知'}}</view>
							</view>
						</view>
						<view class="right">
							<button class="btn" v-if="item.add_status==1" :style="{background:t('color1'),color:'#fff'}" @tap="addFriend" :data-id="item.id">加好友</button>
							<button class="btn btn1" v-if="item.add_status==2">已添加</button>
						</view>
					</view>
				</block>
			</view>
		</view>
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
			pre_url:app.globalData.pre_url,
      isload: false,
      groupList: [],
      datalist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,
			keyword:'',
			canAdd:false,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this;0
			that.loading = true
			app.get('ApiFriend/addFriend', {}, function (res) {
				that.loading = false;
				that.canAdd = res.canAdd
				that.loaded()
			})
		},
    searchMember:function(){
			var that = this;
			this.datalist = [];
			if(this.keyword=='' || !this.canAdd){
				return true
			}
			that.loading = true;
			app.get('ApiFriend/searchMember', {keyword:that.keyword}, function (res) {
				that.loading = false;
				that.datalist = res.datalist
			})
		},
		addFriend:function(e){
			var that = this;
			var id = e.currentTarget.dataset.id;
			app.showLoading('添加中');
			app.post('ApiFriend/addFriend', {id:id,from:'查找'}, function (res) {
				app.showLoading(false);
				if(res.status==1){
					app.success(res.msg);
					setTimeout(function () {
					  app.goto('index')
					}, 1000);
				}else{
					app.error(res.msg);
				}
			})
		}
  }
};
</script>
<style>
.container{ width:100%;}
.flex-sb{display: flex;justify-content: space-between;align-items: center;}
.flex-s{display: flex;align-items: center;}
.top{width: 100%;padding:20rpx 26rpx;z-index: 9999;background: #FFFFFF;}
.top-search{display: flex;align-items: center; background: #F6F6F6;padding: 12rpx 20rpx;border-radius: 50rpx;}
.top-search input{font-size: 28rpx;width: 100%;}
.search-icon{width: 30rpx;height: 30rpx;margin-right: 10rpx;}
.box{background: #FFFFFF;margin-top: 20rpx;padding: 30rpx;}
.box .title{border-bottom: 1rpx solid #f0f0f0;padding-bottom: 20rpx;}


.item{border-bottom: 1rpx solid #f0f0f0;padding: 20rpx 0;}
.item:first-child{padding-top: 0;}
.item:last-child{border: 0;padding-bottom: 0;}
.item .left{display: flex;flex:1;overflow: hidden;}
.left .info{padding: 0 20rpx;flex:1;}
.left .headimg{width: 110rpx; height: 110rpx;border-radius: 16rpx;flex-shrink: 0;}
.left .nickname{font-size: 30rpx;font-weight: 600;max-width: 400rpx;white-space: nowrap;text-overflow: ellipsis;}
.left .desc{color: #999;font-size: 24rpx;line-height: 36rpx;}
.txthide{max-width:100%;text-overflow: ellipsis;overflow: hidden;white-space: nowrap;}

.item .right{display: flex;justify-content: flex-end;flex-wrap: wrap;align-items: center;font-size: 24rpx;flex-shrink: 0;width: 150rpx;}
.right .btn{border: 1rpx solid #e5e5e5; text-align: center;color: #555;width: 120rpx;margin: 4rpx 0 4rpx 6rpx;border-radius: 10rpx;height: 60rpx;line-height: 60rpx;font-size: 24rpx;}
.right .btn1{ background: #eee; color: #666;}
</style>