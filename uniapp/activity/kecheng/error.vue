<template>
<view>
	<block v-if="isload">

		<view class="wrap">
				<view class="top flex">
				<view class="f1" v-if="tkdata.type==1 && tkdata.rightcount==1">单选题</view>
				<view class="f1" v-if="tkdata.type==1 && tkdata.rightcount==2">多选题</view>
				<view class="f1" v-if="tkdata.type==2">填空题</view>
				<view class="f3">{{tkdata.sort}}/{{tkdata.nums}}</view>
			</view>
				<view class="question" >
					<view class="title" >
						{{tkdata.sort}}.{{tkdata.title}}
					</view>
					<block v-if="tkdata.type==1 && tkdata.rightcount==1">
						<view class="option_group" >
							<view :class="'option flex ' +(index==currentindex?'on':'') "  v-for="(item, index) in tkdata.option" :key="index">
							{{tkdata.sorts[index]}}
							<view class="after" ></view> 
							<view class="t1">{{item}}</view></view>
						</view>
					</block>
					<block v-if="tkdata.type==1 && tkdata.rightcount>1">
						<view class="option_group" >
							<view :class="'option flex '+(isActive.indexOf(index)!=-1?'on':'')"  v-for="(item, index) in tkdata.option" :key="index" @tap="selectOption(index)" :data-index='index'>
							{{tkdata.sorts[index]}}
							<view class="after" ></view> 
							<view class="t1">{{item}}</view></view>
						</view>
					</block>
					<block v-if="tkdata.type==1 && !tkdata.rightcount">
						<view class="option_group" >
							<view :class="'option flex '"  v-for="(item, index) in tkdata.option" :key="index" @tap="selectOption(index)" :data-index='index'>
							{{tkdata.sorts[index]}}
							<view class="after" ></view> 
							<view class="t1">{{item}}</view></view>
						</view>
					</block>
					<block v-if="tkdata.type==2">
						<view class="option_group">
							<view class="uni-textarea">
								<textarea placeholder-style="color:#222" placeholder="答:" @blur="bindTextAreaBlur" :value="tkdata.answer"/>
							</view>
						</view>
					</block>
	
				</view>
		</view>
		<view class="right_content">
			<text class="t1">正确答案</text>
			<text class="t2">{{tkdata.type==2?tkdata.right_option:''}}{{tkdata.type==1?tkdata.right_options:''}}</text>
			<view  style="color: #93949E;font-size: 26rpx">题目解析：{{tkdata.jiexi}}</view>
		</view>
		<view class="bottom flex">
			<block v-if="tkdata.isup!=1"><button class="upbut flex-x-center flex-y-center hui" >上一题</button></block>
			<block v-if="tkdata.isup==1"><button  @tap="toanswer" data-dttype="up" class="upbut flex-x-center flex-y-center"  :style="{background:t('color1')}" >上一题</button></block>
			<button  v-if="tkdata.isdown==1" @tap="toanswer" data-dttype="down" class="downbtn flex-x-center flex-y-center"  :style="{background:t('color1')}" >下一题</button>
			<button  v-if="tkdata.isdown!=1"  class="downbtn flex-x-center flex-y-center hui"  >下一题</button>
		</view>

	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
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
		  datalist: [],
			logid:'',
			isActive:[],
			currentindex:'',
			op:'',
			isActive:[],
			right_option:'',
			tkdata:[]
		};
	},
	onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
	},
	onUnload: function () {
		clearInterval(interval);
	},
	methods: {
		getdata:function(){
			var that =this;
			var id = this.opt.rid || 0;
			that.loading = true;
			app.post('ApiKecheng/error', { rid:id,op:that.op,logid:that.logid}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.alert(res.msg);
					return;
				}
				that.tkdata = res.data;	
				var answer = [];
				if(that.tkdata.type==1 && res.data.answer.length>0){
					var answer = res.data.answer;
					answer.map((item) => {
					  that.isActive.push(item);
					})
					that.currentindex = answer;
				}else{
					that.currentindex = res.data.answer;
				}
				that.logid = res.data.logid
				that.loaded();		
			});
		},
		toanswer:function(e){
			var that =this;
			that.op = e.currentTarget.dataset.dttype
			that.isActive=[];
			that.getdata();
		},
		bindTextAreaBlur: function (e) {
			var that=this
			that.right_option = e.detail.value
		},
	}
};
</script>
<style>
.wrap{ background: #fff; margin: 30rpx; border-radius: 10rpx; padding: 30rpx;}

.top{height: 120rpx; line-height: 100rpx; justify-content: space-between;}
.top .f1{ color:#93949E;font-size: 28rpx;}
.top .f2{ color:#FF5347 ; font-size: 28rpx;}
.top .f3{ color:#93949E ; font-size: 28rpx;}
.question .title{ font-size: 30rpx; color: #333; font-weight: bold;}

.right_content{ background: #fff; margin: 30rpx; border-radius: 10rpx; padding: 30rpx;}
.right_content .t1{ color: #333; font-weight: 30rpx; font-weight: bold; display: block; margin-bottom: 20rpx;}
.right_content .t2{ color:#93949E;font-size: 26rpx;}

.option_group .option{ position: relative; padding-left: 37rpx; height: 96rpx; line-height: 96rpx; background:#F8F4F4 ; margin-top: 30rpx; border-radius: 48rpx; }
.option_group .option .t1{ margin-left: 40rpx;}
.option_group .option.on{ background: #FDF1F1; color:#FF5347 ; border: 1px solid #FFAEA8;}
.option_group .option.on .after{ border:1px solid #FF8D8D; }
.option_group .option.green{ background:#36CF7B ; color:#fff}
.option_group .option.green .after{  border:1px solid #fff;} 
.option_group .option .after{ border:1px solid #BBB; height:29rpx;  position: absolute;left: 12%; margin-top: 35rpx;}
.bottom .upbut{width:240rpx;height: 88rpx; line-height: 88rpx;color: #fff;  border-radius: 44rpx;border: none;font-size:28rpx;font-weight:bold; background: #FD4A46; }	
.bottom .upbut.hui{ background: #E3E3E3;}
.bottom .downbtn{margin-left:50rpx;width:360rpx;height: 88rpx; border-radius: 44rpx; line-height: 72rpx;color: #fff;  border: none;font-size:28rpx;font-weight:bold; background: #FD4A46; }	
.bottom .downbtn.hui{ background: #E3E3E3;}
.bottom{ margin-top: 30rpx; padding: 30rpx;}
.uni-textarea{ margin-top: 30rpx; background: #FAFAFA; border: 1px solid #EBE5E5;border-radius: 8rpx; padding: 30rpx;}
</style>