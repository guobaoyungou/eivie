<template>
	<view v-if="isload">
		<image :src="pre_url+'/static/img/fifa2022/guessing_back.png'" class="banner" mode="widthFix"></image>
		<view class="page">
			<view class="module">
				<view class="module_data">
					<image :src="pre_url+'/static/img/fifa2022/guessing_tagA.png'" class="module_tag" mode=""></image>
					<view class="module_title">{{detail.matchStage}}</view>
					<view class="module_time">{{detail.startTime}}</view>
				</view>
				<view class="module_content">
					<view class="module_team">
						<image :src="detail.leftTeam_logo" mode="widthFix" class="module_head"></image>
						<view class="module_text">{{detail.leftTeam_name}}</view>
					</view>
					<view class="module_grade">{{detail.leftTeam_score || 0}}</view>
					<view class="module_state">
						<view class="module_vs">VS</view>
						<view class="module_status module_n" v-if="detail.matchStatus==0">未开始</view>
						<view class="module_status module_n" v-if="detail.matchStatus==1" style="color:#415CC0">进行中</view>
						<view class="module_status" v-if="detail.matchStatus==2">已结束</view>
					</view>
					<view class="module_grade module_r">{{detail.rightTeam_score || 0}}</view>
					<view class="module_team">
						<image :src="detail.rightTeam_logo" mode="widthFix" class="module_head"></image>
						<view class="module_text">{{detail.rightTeam_name}}</view>
					</view>
				</view>
			</view>
			<view class="guess">
				<view class="table">
					<view v-for="(item,index) in tableList" :key="index" @click="tableClick(index)" class="table_item"
						:class="tableIndex==index?'table_active':''">
						{{item.lable}}
						<view class="table_tag" v-if="tableIndex==index"></view>
					</view>
				</view>
				<view v-if="tableIndex==0" class="state">
					<view @click="stateClick('1')" class="state_item" :class="stateIndex=='1'?'state_active':''">
						{{detail.leftTeam_name}} 胜<image v-if="stateIndex=='1'" :src="pre_url+'/static/img/fifa2022/guessing_c.png'" mode="" class="state_icon"></image>
					</view>
					<view @click="stateClick('2')" class="state_item" :class="stateIndex=='2'?'state_active':''">
						平局<image v-if="stateIndex=='2'" :src="pre_url+'/static/img/fifa2022/guessing_c.png'" mode="" class="state_icon"></image>
					</view>
					<view @click="stateClick('3')" class="state_item" :class="stateIndex=='3'?'state_active':''">
						{{detail.rightTeam_name}} 胜<image v-if="stateIndex=='3'" :src="pre_url+'/static/img/fifa2022/guessing_c.png'" mode="" class="state_icon"></image>
					</view>

					<view style="margin-top:60rpx;color:green;text-align:center;font-size:32rpx" v-if="myrecord.guess1st==1">恭喜您已猜中 <text v-if="myrecord.givescore1 > 0">奖励{{t('积分')}}{{myrecord.givescore1}}</text></view>
					<view style="margin-top:60rpx;color:red;text-align:center;font-size:32rpx" v-if="myrecord.guess1st==2">很遗憾未猜中</view>
				</view>
				<view v-if="tableIndex==1" class="grade">
					
					<view>
						<view class="grade_title">
							<text>#</text>{{detail.leftTeam_name}} 胜<text>#</text>
						</view>
						<view class="grade_module">
							<view class="grade_item" @click="gradeClick('1:0')" :class="gradeIndex=='1:0'?'grade_active':''">1:0</view>
							<view class="grade_item" @click="gradeClick('2:0')" :class="gradeIndex=='2:0'?'grade_active':''">2:0</view>
							<view class="grade_item" @click="gradeClick('2:1')" :class="gradeIndex=='2:1'?'grade_active':''">2:1</view>
							<view class="grade_item" @click="gradeClick('3:0')" :class="gradeIndex=='3:0'?'grade_active':''">3:0</view>
							<view class="grade_item" @click="gradeClick('3:1')" :class="gradeIndex=='3:1'?'grade_active':''">3:1</view>
							<view class="grade_item" @click="gradeClick('3:2')" :class="gradeIndex=='3:2'?'grade_active':''">3:2</view>
							<view class="grade_item" @click="gradeClick('4:0')" :class="gradeIndex=='4:0'?'grade_active':''">4:0</view>
							<view class="grade_item" @click="gradeClick('4:1')" :class="gradeIndex=='4:1'?'grade_active':''">4:1</view>
							<view class="grade_item" @click="gradeClick('4:2')" :class="gradeIndex=='4:2'?'grade_active':''">4:2</view>
							<view class="grade_item" @click="gradeClick('4:3')" :class="gradeIndex=='4:3'?'grade_active':''">4:3</view>
							<view class="grade_item" @click="gradeClick('5:0')" :class="gradeIndex=='5:0'?'grade_active':''">5:0</view>
							<view class="grade_item" @click="gradeClick('5:1')" :class="gradeIndex=='5:1'?'grade_active':''">5:1</view>
							<view class="grade_item" @click="gradeClick('5:2')" :class="gradeIndex=='5:2'?'grade_active':''">5:2</view>
							<view class="grade_item" @click="gradeClick('5:3')" :class="gradeIndex=='5:3'?'grade_active':''">5:3</view>
							<view class="grade_item" @click="gradeClick('5:4')" :class="gradeIndex=='5:4'?'grade_active':''">5:4</view>
							<view class="grade_item" @click="gradeClick('胜其他')" :class="gradeIndex=='胜其他'?'grade_active':''">胜其他</view>
						</view>
					</view>

					
					<view>
						<view class="grade_title">
							<text>#</text>平局<text>#</text>
						</view>
						<view class="grade_module">
							<view class="grade_item" @click="gradeClick('0:0')" :class="gradeIndex=='0:0'?'grade_active':''">0:0</view>
							<view class="grade_item" @click="gradeClick('1:1')" :class="gradeIndex=='1:1'?'grade_active':''">1:1</view>
							<view class="grade_item" @click="gradeClick('2:2')" :class="gradeIndex=='2:2'?'grade_active':''">2:2</view>
							<view class="grade_item" @click="gradeClick('3:3')" :class="gradeIndex=='3:3'?'grade_active':''">3:3</view>
							<view class="grade_item" @click="gradeClick('平其他')" :class="gradeIndex=='平其他'?'grade_active':''">平其他</view>
						</view>
					</view>

					
					<view>
						<view class="grade_title">
							<text>#</text>{{detail.rightTeam_name}} 胜<text>#</text>
						</view>
						<view class="grade_module">
							<view class="grade_item" @click="gradeClick('0:1')" :class="gradeIndex=='0:1'?'grade_active':''">0:1</view>
							<view class="grade_item" @click="gradeClick('0:2')" :class="gradeIndex=='0:2'?'grade_active':''">0:2</view>
							<view class="grade_item" @click="gradeClick('1:2')" :class="gradeIndex=='1:2'?'grade_active':''">1:2</view>
							<view class="grade_item" @click="gradeClick('0:3')" :class="gradeIndex=='0:3'?'grade_active':''">0:3</view>
							<view class="grade_item" @click="gradeClick('1:3')" :class="gradeIndex=='1:3'?'grade_active':''">1:3</view>
							<view class="grade_item" @click="gradeClick('2:3')" :class="gradeIndex=='2:3'?'grade_active':''">2:3</view>
							<view class="grade_item" @click="gradeClick('0:4')" :class="gradeIndex=='0:4'?'grade_active':''">0:4</view>
							<view class="grade_item" @click="gradeClick('1:4')" :class="gradeIndex=='1:4'?'grade_active':''">1:4</view>
							<view class="grade_item" @click="gradeClick('2:4')" :class="gradeIndex=='2:4'?'grade_active':''">2:4</view>
							<view class="grade_item" @click="gradeClick('3:4')" :class="gradeIndex=='3:4'?'grade_active':''">3:4</view>
							<view class="grade_item" @click="gradeClick('0:5')" :class="gradeIndex=='0:5'?'grade_active':''">0:5</view>
							<view class="grade_item" @click="gradeClick('1:5')" :class="gradeIndex=='1:5'?'grade_active':''">1:5</view>
							<view class="grade_item" @click="gradeClick('2:5')" :class="gradeIndex=='2:5'?'grade_active':''">2:5</view>
							<view class="grade_item" @click="gradeClick('3:5')" :class="gradeIndex=='3:5'?'grade_active':''">3:5</view>
							<view class="grade_item" @click="gradeClick('4:5')" :class="gradeIndex=='4:5'?'grade_active':''">4:5</view>
							<view class="grade_item" @click="gradeClick('其他')" :class="gradeIndex=='其他'?'grade_active':''">其他</view>
						</view>
					</view>

					
					<view style="margin-top:60rpx;color:green;text-align:center;font-size:32rpx" v-if="myrecord.guess2st==1">恭喜您已猜中 <text v-if="myrecord.givescore2 > 0">奖励{{t('积分')}}{{myrecord.givescore2}}</text></view>
					<view style="margin-top:60rpx;color:red;text-align:center;font-size:32rpx" v-if="myrecord.guess2st==2">很遗憾未猜中</view>

				</view>
			</view>
			<view class="btn" @tap="subguess" v-if="guessStatus==1" style="background:#aaa;color:#fff">赛前{{sset.starthour}}小时开启竞猜</view>
			<view class="btn" @tap="subguess" v-else-if="guessStatus==2" style="background:#aaa;color:#fff">竞猜已结束</view>
			<view class="btn" @tap="subguess" v-else-if="(tableIndex==0 && myrecord.guess1) || (tableIndex==1 && myrecord.guess2)" style="background:#aaa;color:#fff">已参与竞猜</view>
			<view class="btn" @tap="subguess" v-else-if="(tableIndex==0 && !myrecord.guess1) || (tableIndex==1 && !myrecord.guess2)">提交竞猜</view>
			
			<view style="color:#ccc;text-align:center;margin-top:20rpx;" @tap="goto" data-url="index" data-opentype="redirect">返回竞猜列表</view>

			<view class="qd_guize" v-if="sset.guize">
				<view class="gztitle"> — 竞猜规则 — </view>
				<view class="guize_txt">
					<parse :content="sset.guize" />
				</view>
			</view>

		</view>

		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
	</view>
</template>

<script>
let videoAd = null;
var app = getApp();
export default {
	data() {
		return {
			opt:{},
			loading:false,
			isload: false,
			menuindex:-1,
			pre_url:app.globalData.pre_url,
			detail:{},
			myrecord:{},
			tableList: [{
					lable: "猜胜负",
					value: '1'
				},
				{
					lable: "猜比分",
					value: '2'
				}
			],
			tableIndex: 0,
			stateIndex: '',
			gradeIndex: '',
			sset:{},
			guessStatus:0,
			isdoing:false,
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
			app.get('ApiFifa/detail', {id:that.opt.id}, function (res) {
				that.loading = false;
				that.detail = res.detail;
				that.myrecord = res.myrecord;
				that.guessStatus = res.guessStatus;
				that.sset = res.sset;
				if(that.myrecord && that.myrecord.guess1){
					that.stateIndex = that.myrecord.guess1
				}
				if(that.myrecord && that.myrecord.guess2){
					that.gradeIndex = that.myrecord.guess2
				}
				that.loaded();
				
				if (app.globalData.platform == 'wx' && res.sset.rewardedvideoad && !videoAd && wx.createRewardedVideoAd) {
					videoAd = wx.createRewardedVideoAd({
						adUnitId: res.sset.rewardedvideoad
					});
					videoAd.onLoad(() => {})
					videoAd.onError((err) => {
						that.isdoing = false;
					})
					videoAd.onClose(res2 => {
						that.isdoing = false;
						if (res2 && res2.isEnded) {
							that.confirmguess();
						} else {
							
						}
					});
				}
			});
		},
		tableClick(index) {
			this.tableIndex = index;
		},
		stateClick(index) {
			this.stateIndex = index;
		},
		gradeClick(index) {
			this.gradeIndex = index;
		},
    subguess: function () {
			var that = this;
			if(that.isdoing) return;
			that.isdoing = true;
			if (app.globalData.platform == 'wx' && that.sset.rewardedvideoad && videoAd) {
				videoAd.show().catch(() => {
					videoAd.load()
					.then(() => videoAd.show())
					.catch(err => {
						that.confirmguess();
					});
				});
			}else{
				that.confirmguess();
			}
    },

		confirmguess:function(){
			var that = this;
			var guesstype = '';
			var guess = '';
			if(this.tableIndex == 0){
				guesstype = 1;
				guess = this.stateIndex
			}else{
				guesstype = 2;
				guess = this.gradeIndex;
			}
			if(!guess){
				app.alert('请选择你的竞猜');
				that.isdoing = false;
				return;
			}
			app.post('ApiFifa/subguess', {hid:that.opt.id,guesstype:guesstype,guess:guess}, function (data) {
        if (data.status == 1) {
          app.success(data.msg);
          that.getdata();
        } else {
          app.alert(data.msg);
        }
				that.isdoing = false;
      });
		},
	}
}
</script>
<style>
	page {
		background: linear-gradient(90deg, #405ADD 0%, #695DDE 100%);
	}
</style>
<style scoped>
	.banner {
		position: absolute;
		width: 100%;
		display: block;
	}

	.page {
		position: relative;
		padding: 35rpx 30rpx 30rpx 30rpx;
	}

	.title {
		position: relative;
		font-size: 36rpx;
		font-family: Source Han Sans CN;
		font-weight: bold;
		color: #FFFFFF;
		text-align: center;
	}

	.title image {
		position: absolute;
		height: 45rpx;
		width: 45rpx;
		left: 30rpx;
		top: 0;
		bottom: 0;
		margin: auto 0;
	}

	.module {
		position: relative;
		padding: 70rpx 30rpx 45rpx 30rpx;
		background: #f5f5ff;
		border-radius: 20rpx;
		margin-top: 65rpx;
	}

	.module_data {
		position: absolute;
		left: 0;
		right: 0;
		top: 0;
		width: 240rpx;
		height: 95rpx;
		color: #415CC0;
		margin: 0 auto;
	}

	.module_tag {
		width: 240rpx;
		height: 95rpx;
	}

	.module_title {
		position: absolute;
		width: 100%;
		text-align: center;
		top: 20rpx;
		font-size: 20rpx;
		line-height: 20rpx;
		font-family: Source Han Sans CN;
		font-weight: 400;
	}

	.module_time {
		position: absolute;
		width: 100%;
		text-align: center;
		top: 55rpx;
		font-size: 28rpx;
		line-height: 28rpx;
		font-family: DIN Pro;
		font-weight: bold;
	}

	.module_content {
		display: flex;
		align-items: flex-end;
	}

	.module_team {
		flex: 1;
	}

	.module_head{
		width: 80rpx;
		height:53rpx;
		display: block;
		margin: 0 auto;
	}

	.module_text {
		font-size: 24rpx;
		font-family: Source Han Sans CN;
		font-weight: 400;
		color: #121212;
		margin-top: 15rpx;
		text-align: center;
	}

	.module_grade {
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

	.module_r {
		background: #AB3F3F;
	}

	.module_state {
		padding: 0 35rpx;
	}

	.module_vs {
		font-size: 24rpx;
		line-height: 24rpx;
		font-family: DIN Pro;
		font-weight: bold;
		color: #415CC0;
		text-align: center;
	}

	.module_status {
		font-size: 20rpx;
		line-height: 20rpx;
		font-family: Source Han Sans CN;
		font-weight: 400;
		color: #C7AF8C;
		margin-top: 20rpx;
		text-align: center;
	}


	.table {
		display: flex;
	}

	.table_item {
		position: relative;
		padding: 0 0 35rpx 0;
		font-size: 28rpx;
		font-family: Source Han Sans CN;
		font-weight: bold;
		text-align: center;
		flex: 1;
		color: #C1C0C0;
	}

	.table_active {
		color: #121212;
	}

	.table_tag {
		position: absolute;
		left: 0;
		right: 0;
		bottom: 20rpx;
		margin: 0 auto;
		width: 40rpx;
		height: 6rpx;
		background: #3C62ED;
	}

	.guess {
		position: relative;
		padding: 40rpx 40rpx 110rpx 40rpx;
		margin-top: 20rpx;
		background: #FFFFFF;
		border-radius: 20rpx;
	}

	.state {
		padding: 30rpx 0 0 0;
	}

	.state_item {
		position: relative;
		height: 100rpx;
		background: #FFFFFF;
		border: 1rpx solid #F0F0F1;
		border-radius: 20rpx;
		padding: 0 50rpx;
		font-size: 28rpx;
		font-family: Source Han Sans CN;
		font-weight: 500;
		display: flex;
		align-items: center;
		color: #121212;
		margin-top: 20rpx;
	}

	.state_active {
		border: 1rpx solid #2D6FE8;
		color: #3C62ED;
		background: rgba(45, 111, 232, 0.2);
	}

	.state_icon {
		height: 36rpx;
		width: 36rpx;
		position: absolute;
		top: 0;
		bottom: 0;
		right: 40rpx;
		margin: auto 0;
	}

	.grade {
		position: relative;
	}

	.grade_title {
		font-size: 28rpx;
		text-align: center;
		font-family: Source Han Sans CN;
		font-weight: 500;
		color: #121212;
		margin-top: 50rpx;
	}

	.grade_title text:first-child {
		color: #3C62ED;
		margin: 0 10rpx;
	}

	.grade_title text:last-child {
		color: #ED3C83;
		margin: 0 10rpx;
	}

	.grade_module {
		display: grid;
		grid-template-columns: repeat(5, 1fr);
		grid-column-gap: 20rpx;
		grid-row-gap: 20rpx;
		margin-top: 30rpx;
	}

	.grade_item {
		height: 56rpx;
		background: #FFFFFF;
		border: 1rpx solid #F0F0F1;
		border-radius: 12rpx;
		font-size: 26rpx;
		text-align: center;
		font-family: Source Han Sans CN;
		font-weight: 500;
		color: #121212;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.grade_active {
		background: #3C62ED;
		color: #fff;
	}

	.btn {
		width: 650rpx;
		height: 108rpx;
		background: #FFCA4C;
		border-radius: 54rpx;
		font-size: 32rpx;
		font-family: Source Han Sans CN;
		font-weight: 500;
		color: #533F10;
		display: flex;
		align-items: center;
		justify-content: center;
		margin: 45rpx auto 0 auto;
	}
	.qd_guize{
		padding: 20rpx 40rpx 110rpx 40rpx;
		margin-top: 40rpx;
		background: #FFFFFF;
		border-radius: 20rpx;}
	.qd_guize .gztitle{width:100%;text-align:center;font-size:32rpx;color:#656565;font-weight:bold;height:100rpx;line-height:100rpx}
	.guize_txt{box-sizing: border-box;line-height:42rpx;}
</style>
