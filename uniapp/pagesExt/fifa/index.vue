<template>
	<view v-if="isload">
		<image :src="pre_url+'/static/img/fifa2022/guessing_banner.png'" class="banner" mode="widthFix"></image>
		<view class="page">
			<view class="my">
				<view class="my_title">
					我的竞猜
				</view>
				<view class="my_text">
					当前积分：<text>{{myscore}}</text>
				</view>
				<view class="my_data">
					<view class="my_grade">
						<view>
							<view class="my_value">{{successnum}}</view>
							<view class="my_lable">已中胜场</view>
						</view>
						<view>
							<view class="my_value">{{winscore}}</view>
							<view class="my_lable">已中得分</view>
						</view>
					</view>
					<view class="my_btn" @tap="showPoster">
						<image :src="pre_url+'/static/img/fifa2022/guessing_share.png'" mode="widthFix"></image>生成海报
					</view>
				</view>
			</view>
			<scroll-view class="table" scroll-x="true" >
				<view v-for="(item,index) in datalist" :key="index" @click="tableClick" :data-thisdate="item.date" class="table_item" :class="selectdate==item.date?'table_active':''">
					<view class="table_time">{{item.date}}</view>
					<view class="table_week">{{item.week}}</view>
				</view>
			</scroll-view>
			<view v-for="(item,index) in datalist[selectdate].data" :key="index" @click="goto" :data-url="'detail?id='+item.id" class="module">
				<view class="module_data" :class="(item.matchStatus==0 || item.matchStatus==1)?'module_active':''">
					<image :src="pre_url+'/static/img/fifa2022/guessing_tagA.png'" v-if="item.matchStatus==0 || item.matchStatus==1" class="module_tag" mode=""></image>
					<image :src="pre_url+'/static/img/fifa2022/guessing_tag.png'" v-else class="module_tag" mode=""></image>
					<view class="module_title">{{item.matchStage}}</view>
					<view class="module_time">{{item.startTime}}</view>
				</view>
				<view class="module_content">
					<view class="module_team">
						<image :src="item.leftTeam_logo" mode="widthFix" class="module_head"></image>
						<view class="module_text">{{item.leftTeam_name}}</view>
					</view>
					<view class="module_grade">{{item.leftTeam_score || 0}}</view>
					<view class="module_state">
						<view class="module_vs">VS</view>
						<view class="module_status module_n" v-if="item.matchStatus==0">未开始</view>
						<view class="module_status module_n" v-if="item.matchStatus==1" style="color:#415CC0">进行中</view>
						<view class="module_status" v-if="item.matchStatus==2">已结束</view>
					</view>
					<view class="module_grade module_r">{{item.rightTeam_score || 0}}</view>
					<view class="module_team">
						<image :src="item.rightTeam_logo" mode="widthFix" class="module_head"></image>
						<view class="module_text">{{item.rightTeam_name}}</view>
					</view>
				</view>
			</view>
		</view>
		
		<view class="posterDialog" v-if="showposter">
			<view class="main">
				<view class="close" @tap="posterDialogClose"><image class="img" :src="pre_url+'/static/img/close.png'"/></view>
				<view class="content">
					<image class="img" :src="posterpic" mode="widthFix" @tap="previewImage" :data-url="posterpic"></image>
				</view>
			</view>
		</view>

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
			pre_url:app.globalData.pre_url,
			
			selectdate:'11-21',
			datalist:[],
			myscore:'-',
			successnum:'-',
			winscore:'-',
			showposter: false,
			posterpic: "",
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
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiFifa/index', {}, function (res) {
				that.loading = false;
				that.datalist = res.datalist;
				that.myscore = res.myscore;
				that.successnum = res.successnum;
				that.winscore = res.winscore;
				that.selectdate = res.selectdate;
				that.loaded();
			});
		},
		tableClick(e){
			this.selectdate = e.currentTarget.dataset.thisdate;
		},
		showPoster: function () {
			var that = this;
			that.showposter = true;
			that.sharetypevisible = false;
			app.showLoading('生成海报中');

			app.post('ApiFifa/getposter', {  }, function (data) {
				app.showLoading(false);
				if (data.status == 0) {
					app.alert(data.msg);
				} else {
					that.posterpic = data.poster;
				}
			});
		},
		posterDialogClose: function () {
			this.showposter = false;
		},
	}
}
</script>
<style>
	page{
		background: linear-gradient(90deg, #405ADD 0%, #695DDE 100%);
	}
</style>
<style scoped>
	.banner{
		position: absolute;
		width: 100%;
		display: block;
	}
	.page{
		position: relative;
		padding: 35rpx 30rpx 30rpx 30rpx;
	}
	.title{
		position: relative;
		font-size: 36rpx;
		font-family: Source Han Sans CN;
		font-weight: bold;
		color: #FFFFFF;
		text-align: center;
	}
	.title image{
		position: absolute;
		height: 45rpx;
		width: 45rpx;
		left: 30rpx;
		top: 0;
		bottom: 0;
		margin: auto 0;
	}
	
	.my{
		position: relative;
		padding: 70rpx 0;
	}
	.my_title{
		font-size: 48rpx;
		font-family: Source Han Sans CN;
		font-weight: bold;
		color: #FFFFFF;
	}
	.my_text{
		font-size: 24rpx;
		font-family: Source Han Sans CN;
		font-weight: 400;
		color: #C9D4FF;
		margin-top: 20rpx;
	}
	.my_text text{
		color: #FFCA4C;
	}
	.my_data{
		margin-top: 50rpx;
		display: flex;
		align-items: center;
		justify-content: space-between;
	}
	.my_grade{
		display: flex;
		width: 270rpx;
		justify-content: space-between;
	}
	.my_value{
		font-size: 36rpx;
		font-family: Source Han Sans CN;
		font-weight: bold;
		color: #FFFFFF;
		text-align: center;
	}
	.my_lable{
		font-size: 24rpx;
		font-family: Source Han Sans CN;
		font-weight: 400;
		color: #C9D4FF;
		margin-top: 20rpx;
		text-align: center;
	}
	.my_btn{
		width: 213rpx;
		height: 72rpx;
		background: #FFCA4C;
		border-radius: 36rpx;
		font-size: 24rpx;
		font-family: Source Han Sans CN;
		font-weight: 400;
		color: #533F10;
		display: flex;
		align-items: center;
		justify-content: center;
	}
	.my_btn image{
		height: 28rpx;
		width: 28rpx;
		margin-right: 5rpx;
	}
	
	.table{
		padding: 0 0 5rpx 0;
		white-space: nowrap;
	}
	.table_item{
		display: inline-block;
		padding: 10rpx 30rpx;
		border-radius: 20rpx;
		color: rgba(255, 255, 255, 0.3);
	}
	.table_time{
		font-size: 24rpx;
		font-family: Source Han Sans CN;
		font-weight: 400;
		text-align: center;
	}
	.table_week{
		font-size: 20rpx;
		font-family: Source Han Sans CN;
		font-weight: 400;
		text-align: center;
		margin-top: 5rpx;
	}
	.table_active{
		color: #fff;
		background: rgba(255, 255, 255, 0.12);
	}
	
	.module{
		position: relative;
		padding: 70rpx 30rpx 45rpx 30rpx;
		background: #f5f5ff;
		border-radius: 20rpx;
		margin-top: 30rpx;
	}
	.module_data{
		position: absolute;
		left: 0;
		right: 0;
		top: 0;
		width: 240rpx;
		height: 95rpx;
		color: #C1C0C0;
		margin: 0 auto;
	}
	.module_active{
		color: #415CC0;
	}
	.module_tag{
		width: 240rpx;
		height: 95rpx;
	}
	.module_title{
		position: absolute;
		width: 100%;
		text-align: center;
		top: 20rpx;
		font-size: 20rpx;
		line-height: 20rpx;
		font-family: Source Han Sans CN;
		font-weight: 400;
	}
	.module_time{
		position: absolute;
		width: 100%;
		text-align: center;
		top: 55rpx;
		font-size: 28rpx;
		line-height: 28rpx;
		font-family: DIN Pro;
		font-weight: bold;
	}
	.module_content{
		display: flex;
		align-items: flex-end;
	}
	.module_team{
		flex: 1;
	}
	.module_head{
		width: 80rpx;
		height:53rpx;
		display: block;
		margin: 0 auto;
	}
	.module_text{
		font-size: 24rpx;
		font-family: Source Han Sans CN;
		font-weight: 400;
		color: #121212;
		margin-top: 15rpx;
		text-align: center;
	}
	.module_grade{
		width: 60rpx;
		height: 72rpx;
		background: #3D5C9B;
		border-radius: 8rpx;
		font-size: 36rpx;
		font-weight: bold;
		color: #FFFFFF;
		display: flex;
		align-items: center;
		justify-content: center;
	}
	.module_r{
		background: #AB3F3F;
	}
	.module_state{
		padding: 0 35rpx;
	}
	.module_vs{
		font-size: 24rpx;
		line-height: 24rpx;
		font-family: DIN Pro;
		font-weight: bold;
		color: #415CC0;
		text-align: center;
	}
	.module_status{
		font-size: 20rpx;
		line-height: 20rpx;
		font-family: Source Han Sans CN;
		font-weight: 400;
		color: #C1C0C0;
		margin-top: 20rpx;
		text-align: center;
	}
	.module_n{
		color: #C7AF8C;
	}


</style>
