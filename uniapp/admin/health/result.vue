<template>
<view >
	<block v-if="isload">
		<view class="container">
			<view class="enter">
				<view class="gobtn" @tap="goto" :data-url="'recordlog?rid='+detail.id" :style="{background:themeColor,color:'#FFF'}">评测详情</view>
			</view>
			<view class="box">
				<view class="row">
					<view class="lable">评测量表：</view>
					<view class="value">{{detail.ha_name}}</view>
				</view>
				<view class="row">
					<view class="lable">评测时间：</view>
					<view class="value">{{detail.createtime}}</view>
				</view>
				<view class="row">
					<view class="lable">联系方式：</view>
					<view class="value">{{detail.tel}}</view>
				</view>
				<view class="row">
					<view class="lable">姓名：</view>
					<view class="value">{{detail.name}}</view>
				</view>
				<view class="row">
					<view class="lable">年龄：</view>
					<view class="value">{{detail.age}}岁</view>
				</view>
				<view class="row">
					<view class="lable">性别：</view>
					<view class="value">{{detail.sex==2?'女':'男'}}</view>
				</view>
				<view class="row" v-if="detail.bname">
					<view class="lable">门店：</view>
					<view class="value">{{detail.bname}}</view>
				</view>
				<view class="row">
					<view class="lable">家庭地址：</view>
					<view class="value">{{detail.address}}</view>
				</view>
			</view>
			<view class="box">
				<block v-if="health.type!=3">
					<view class="tag"  :style="{color:themeColor}">{{detail.score_tag}}</view>
					<view class="score" :style="{color:themeColor}">{{detail.score}}分</view>
				</block>
				<view class="child-result" v-if="detail.child_result.length>0">
					<view class="child-item" :style="'border:1rpx solid '+themeColor+''" v-for="(itemC,indexC) in detail.child_result">
						<view class="child-title">{{itemC.name}}</view>
						<view class="child-score txt1">
							<view class="score" :style="{color:themeColor}">{{itemC.score}}分</view>
							<view>{{itemC.score_tag}}</view>
						</view>
						<view class="txt1"><rich-text :nodes="itemC.score_desc"></rich-text></view>
					</view>
				</view>
				<view class="desc" v-if="health.type!=3">
					<view class="title">评测概述</view>
					<view class="content"><rich-text :nodes="detail.score_desc"></rich-text></view>
				</view>
				<view class="desc">
					<view class="title">评测说明</view>
					<view class="content"><rich-text :nodes="detail.desc"></rich-text></view>
				</view>
			</view>
			<view class="box">
				<dp :pagecontent="pagecontent" :menuindex="menuindex" @getdata="getdata"></dp>
			</view>
		</view>
		<view style="height: 90rpx;"></view>
		<view class="bottom">
				<button  class="btn" @tap="goback" :style="{background:themeColor,color:'#FFF'}">
					返 回
				</button>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();
var interval = null;
export default {
	data() {
		return {
			opt:{},
			loading:false,
			isload: false,
			detail:[],
			pagecontent:[],
			id:0,
			menuindex:-1,
			health:{},
			custom:{},
			themeColor:''
		};
	},
	  onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.id = this.opt.id || 0;
			this.getdata();
	  },
		
	onPullDownRefresh: function () {
		this.getdata();
	},
	methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.post('ApiAdminHealth/questionResult', {id:that.id}, function (res) {
				that.loading = false;
				if (res.status == 1) {
				  that.detail = res.detail
					that.pagecontent = res.pagecontent
					that.health = res.health
					that.health = res.health
					that.custom = res.custom
					if(that.custom.PSQI){
						that.themeColor = '#229989'
					}else{
						that.themeColor = that.themeColor;
					}
					that.loaded();
				}else{
					app.alert(res.msg);
					return;
				}
			});
		}
	}
};
</script>
<style>
.container{padding: 30rpx;}
.box{margin-bottom: 30rpx;border: 1rpx solid #F6F6F6;border-radius: 20rpx;background: #FFFFFF;padding: 30rpx; /* font-size: 12px; */ color: #666;}
.row{display: flex;align-items: center;padding-top: 20rpx;}
.row .lable{width: 180rpx;flex-shrink: 0;text-align: right;padding-right: 20rpx;color: #666;}
.tag{font-size: 40rpx;font-weight: bold;text-align: center;}
.score{font-size: 32rpx;text-align: center;padding: 10rpx 0;}
.desc{font-size: 28rpx;margin-top: 20rpx;color: #666;}
.desc .title{line-height: 50rpx;/* font-size: 30rpx;*/font-weight: bold; color: #222222; }
.desc .content{line-height: 40rpx;/* color: #909090; font-size: 26rpx; */}
.bottom{display: flex;justify-content: center;align-items: center;background: #f6f6f6;padding: 20rpx;position: fixed;bottom: 0;width: 100%;}

.bottom .btn{height: 80rpx;line-height: 80rpx;padding: 0 20rpx;border-radius: 16rpx;width: 90%;background: #bbb;color: #FFFFFF;}
.enter{display: flex;justify-content: flex-end;font-size: 24rpx;}
.gobtn{width: 150rpx;height: 50rpx;line-height: 50rpx;text-align: center;right: 0;z-index: 999;border-radius: 40px 0 0 40px;margin-right: -30rpx;margin-bottom: 20rpx;}

.child-score{display: flex;justify-content: flex-start;align-items: center;}
.child-result{margin: 20rpx 0;}
.child-title{/* font-size: 30rpx;font-weight: bold; */}
.child-item{border: 1rpx solid #EEEEEE;padding: 20rpx;border-radius: 10rpx;margin-bottom: 20rpx;}
.child-score .score{margin-right: 10rpx;font-size: 28rpx;}
.child-result .txt1{font-size: 24rpx;}
.content img{object-fit: contain;}
</style>