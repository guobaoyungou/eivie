<template>
<view :style="{fontSize:fontSize}">
	<block v-if="isload">
		<view class="progress">当前：<text>{{question_index+1}}/{{questionlist.length}}</text></view>
		<view class="container">
			<view class="question">
				<view class="title" >
					<rich-text :nodes="question.name"></rich-text>
				</view>
				<view class="option_group">
					<block v-for="(item,index) in question.optionlist">
						<view class="option flex" :style="item.checked==1?('background:'+themeColor+';color:#ffffff'):''" @tap="selectOption" :data-index="index">
							{{item.option}}
						</view>
					</block>
				</view>
			</view>
		</view>
		<view class="bottom" v-if="custom.PSQI">
			<block>
			<view class="btn btn1" @tap="goprev" v-if="question_index>0">上一题</view>
			<view class="btn btn0" v-else></view>
			</block>
			<view v-if="question_index<questionlist.length-1" class="btn btn2" @tap="gonext">下一题</view>
			<view v-if="question_index==questionlist.length-1" class="btn btn2" @tap="goback">返 回</view>
		</view>
		<view class="bottom" v-else>
			<block>
			<view class="btn btn1" @tap="goprev" v-if="question_index>0" :style="'background:rgba('+t('color1rgb')+',0.16);color:'+t('color1')">上一题</view>
			<view class="btn btn0" v-else></view>
			</block>
			<view v-if="question_index<questionlist.length-1" class="btn" :style="{background:themeColor}" @tap="gonext">下一题</view>
			<view v-if="question_index==questionlist.length-1" class="btn" :style="{background:themeColor}" @tap="goback">返 回</view>
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
			title: "",
			questionlist: [],
			question_index:-1,
			question:{},
			rid:0,
			custom:{},
			themeColor:'',
			fontSize:'28rpx'
		};
	},
	  onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.rid = this.opt.rid || 0;
			this.getdata();
	  },
	onUnload: function () {
	},
	methods: {
		getdata:function(){
			var that = this;
			var id = this.opt.id || 0;
			that.loading = true;
			app.post('ApiAdminHealth/questionRecordLog', {rid:that.opt.rid}, function (res) {
				that.loading = false;
				if (res.status == 1) {
				  that.questionlist = res.datalist
					if(that.questionlist.length>0){
						that.question_index = 0;
						that.question = that.questionlist[0]
					}
					that.custom = res.custom
					if(that.custom.PSQI){
						that.themeColor = '#229989'
						that.fontSize = '36rpx'
					}else{
						that.themeColor = that.t('color1');
					}
					that.loaded();
				}else{
					app.alert(res.msg);
					return;
				}
			});
		},
		goprev:function(){
			var that = this;
			if(that.question_index>0){
				that.question_index--;
			}
			that.question = that.questionlist[that.question_index]
		},
		gonext:function(){
			var that = this;
			//当前是不是选择了
			var optionlist = that.question.optionlist
			if(that.question_index<that.questionlist.length-1){
				that.question_index++;
			}
			that.question = that.questionlist[that.question_index]
		},
		onPullDownRefresh: function () {
			this.getdata();
		},
	}

};
</script>
<style>
.container{position: absolute;top: 80rpx;width: 100%;padding: 0 30rpx;}
.progress{position: fixed;top: 0;width: 100%;left: 0;background: #f6f6f6;display: flex;align-items: center;height: 80rpx;padding: 0 5%;}
.question{background: #fff;padding: 30rpx;border-radius: 16rpx;margin-bottom: 140rpx;}
.question .title{ width: 100%; /*color: #222222; font-size: 34rpx;*/ font-weight: bold; } 
.option_group{margin-top: 30rpx;max-height: 1260rpx;overflow-y: scroll;}
.option_group .option{background:#f6f6f6; border-radius: 10rpx;margin-bottom: 20rpx;line-height: 120%;padding:14rpx 20rpx;}
.option_group .option .t1{ margin-left: 40rpx;}
.option_group .option.on{ background: #61CD78; color:#fff;}
.bottom{width: 100%;background:#f6f6f6;padding:20rpx;display: flex;justify-content: center;align-items: center;position: fixed;bottom: 0;}
.bottom .btn{border-radius: 10rpx;background: #37A0D3;color: #FFFFFF;padding: 16rpx 40rpx;text-align: center;width: 43%;margin: 10rpx;}
.btn.btn1{background: #09d1c8;}
.btn.btn2{background: #ffc03b}
.btn.btn0{opacity: 0;}

</style>