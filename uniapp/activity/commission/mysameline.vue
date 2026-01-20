<template>
<view class="container">
	<block v-if="isload">
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入昵称/姓名/手机号搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
			</view>
		</view>
		<view class="content" v-if="datalist && datalist.length>0">
			<view class="label">
				<text class="t1">成员信息</text>
				<text class="t2"></text>
			</view>
			<block v-for="(item, index) in datalist" :key="index">
				<view class="item">
					<view class="f1">
						<image :src="item.headimg"></image>
						<view class="t2">
							<text class="x1">{{item.nickname}}</text>
							<text class="x2">{{dateFormat(item.createtime,'Y-m-d H:i')}}</text>
							<text class="x2">等级：{{item.levelname}}</text>
							<text class="x2" v-if="item.tel">手机号：{{item.tel}}</text>
						</view>
					</view>
					<view class="f2">
						<!-- <text class="t1">+{{item.commission}}</text> -->
						<!-- <text class='t2'>{{item.downcount}}个成员</text> -->
						<view class="t3">
							<view v-if="userlevel && userlevel.team_givemoney==1" class="x1" @tap="givemoneyshow" :data-id="item.id">转{{t('余额')}}</view>
							<view v-if="userlevel && userlevel.team_givescore==1" class="x1" @tap="givescoreshow" :data-id="item.id" >转{{t('积分')}}</view>
						</view>
					</view>
				</view>
			</block>
		</view>
		<uni-popup id="dialogmoneyInput" ref="dialogmoneyInput" type="dialog">
			<uni-popup-dialog mode="input" title="转账金额" value="" placeholder="请输入转账金额" @confirm="givemoney"></uni-popup-dialog>
		</uni-popup>
		<uni-popup id="dialogscoreInput" ref="dialogscoreInput" type="dialog">
			<uni-popup-dialog mode="input" title="转账数量" value="" placeholder="请输入转账数量" @confirm="givescore"></uni-popup-dialog>
		</uni-popup>
		
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
			textset:{},
			levelList:{},
			keyword:'',
			tomid:'',
			tomoney:0,
			toscore:0,
      nodata: false,
      nomore: false,
			dialogShow: false,
			tempMid: '',
			tempLevelid: '',
			tempLevelsort: '',
			pre_url: app.globalData.pre_url,
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
      this.pagenum = this.pagenum + 1
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
      var st = that.st;
      var pagenum = that.pagenum;
      var keyword = that.keyword;
			that.loading = true;
			that.nodata = false;
      that.nomore = false;
      app.post('ApiAgent/sameline', {st: st,pagenum: pagenum,keyword:keyword}, function (res) {
				that.loading = false;
        var data = res.datalist;
        if (pagenum == 1) {
					that.userinfo = res.userinfo;
					that.userlevel = res.userlevel;
					that.textset = app.globalData.textset;
          that.datalist = data;
					that.levelList = res.levelList;
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
    changetab: function (st) {
			this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
		givemoneyshow:function(e){
			var that = this;
			var id = e.currentTarget.dataset.id;
			that.tomid = id;
			that.$refs.dialogmoneyInput.open();
		},
		givescoreshow:function(e){
			var that = this;
			var id = e.currentTarget.dataset.id;
			that.tomid = id;
			that.$refs.dialogscoreInput.open();
		},
		givemoney:function(done, money){
			var that = this;
			var id = that.tomid;
			app.showLoading('提交中');
			app.post('ApiAgent/givemoney', {id:id,money:money}, function (res) {
				app.showLoading(false);
				if (res.status == 0) {
          app.error(res.msg);
        } else {
          app.success(res.msg);
					that.getdata();
					that.$refs.dialogmoneyInput.close();
				}
			})
		},
		givescore:function(done, score){
			var that = this;
			var id = that.tomid;
			app.showLoading('提交中');
			app.post('ApiAgent/givescore', {id:id,score:score}, function (res) {
				app.showLoading(false);
				if (res.status == 0) {
          app.error(res.msg);
        } else {
          app.success(res.msg);
					that.getdata();
					that.$refs.dialogscoreInput.close();
				}
			})
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
		showDialog:function(e){
			let that = this;
			that.tempMid = e.currentTarget.dataset.id;
			that.tempLevelid = e.currentTarget.dataset.levelid;
			that.tempLevelsort = e.currentTarget.dataset.levelsort;
			this.dialogShow = !this.dialogShow
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

.content .item{width: 100%;padding: 32rpx;border-top: 1px #eaeaea solid;min-height: 112rpx;display:flex;align-items:center;}
.content .item image{width: 90rpx;height: 90rpx;border-radius:4px}
.content .item .f1{display:flex;flex:1;align-items:center;}
.content .item .f1 .t2{display:flex;flex-direction:column;padding-left:20rpx}
.content .item .f1 .t2 .x1{color: #333;font-size:26rpx;}
.content .item .f1 .t2 .x2{color: #999;font-size:24rpx;}

.content .item .f2{display:flex;flex-direction:column;width:200rpx;text-align:right;border-left:1px solid #eee}
.content .item .f2 .t1{ font-size: 40rpx;color: #666;height: 40rpx;line-height: 40rpx;}
.content .item .f2 .t2{ font-size: 28rpx;color: #999;height: 50rpx;line-height: 50rpx;}
.content .item .f2 .t3{ display:flex;justify-content:space-around;margin-top:10rpx; flex-wrap: wrap;}
.content .item .f2 .t3 .x1{height:40rpx;line-height:40rpx;padding:0 8rpx;border:1px solid #ccc;border-radius:6rpx;font-size:22rpx;color:#666;margin-top: 10rpx;}

	.sheet-item {display: flex;align-items: center;padding:20rpx 30rpx;}
	.sheet-item .item-img {width: 44rpx;height: 44rpx;}
	.sheet-item .item-text {display: block;color: #333;height: 100%;padding: 20rpx;font-size: 32rpx;position: relative; width: 90%;}
	.sheet-item .item-text:after {position: absolute;content: '';height: 1rpx;width: 100%;bottom: 0;left: 0;border-bottom: 1rpx solid #eee;}
	.man-btn {
		line-height: 100rpx;
		text-align: center;
		background: #FFFFFF;
		font-size: 30rpx;
		color: #FF4015;
	}
</style>