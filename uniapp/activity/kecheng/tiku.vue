<template>
<view>
	<block v-if="isload">

		<view class="wrap">
			<view class="top flex">
				<view class="f1" v-if="tkdata.type==1 && tkdata.rightcount==1">单选题</view>
				<view class="f1" v-if="tkdata.type==1 && tkdata.rightcount==2">多选题</view>
				<view class="f1" v-if="tkdata.type==2">填空题</view>
				<view class="f2">倒计时：{{djs}}</view>
				<view class="f3">{{hasnum}}/{{nums}}</view>
			</view>
				<view class="question" >
					<view class="title" >
						{{hasnum}}.{{tkdata.title}}
					</view>
					<block v-if="tkdata.type==1 && tkdata.rightcount==1">
						<view class="option_group" >
							<view :class="'option flex ' +(index==currentindex?'on':'') "  v-for="(item, index) in tkdata.option" :key="index" @tap="selectOption" :data-index='index'>
							{{tkdata.sorts[index]}}
							<view class="after" ></view> 
							<view class="t1">{{item}}</view></view>
						</view>
					</block>
					<block v-if="tkdata.type==1 && tkdata.rightcount>1">
						<view class="option_group" >
							<view :class="'option flex '+(isActive.indexOf(index)!=-1?'on':'')"  v-for="(item, index) in tkdata.option" :key="index" @tap="selectOption" :data-index='index'>
							{{tkdata.sorts[index]}}
							<view class="after" ></view> 
							<view class="t1">{{item}}</view></view>
						</view>
					</block>
					<block v-if="tkdata.type==2">
						<view class="option_group">
							<view class="uni-textarea">
								<textarea placeholder-style="color:#222" placeholder="答:" @blur="bindTextAreaBlur" :value="right_option" />
							</view>
		
						</view>
					</block>
				</view>
	
		</view>
		<view class="bottom flex">
			<block v-if="hasnum==1"><button class="upbut flex-x-center flex-y-center hui" >上一题</button></block>
			<block v-if="hasnum>1"><button  @tap="prevquestion" data-dttype="up" class="upbut flex-x-center flex-y-center"  :style="{background:t('color1')}" >上一题</button></block>
			<button v-if="nums==hasnum" @tap="finish" data-dttype="down" class="downbtn flex-x-center flex-y-center"  :style="{background:t('color1')}" >交卷</button>
			<button v-else @tap="nextquestion" data-dttype="down" class="downbtn flex-x-center flex-y-center"  :style="{background:t('color1')}" >下一题</button>
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
			currentindex:'-1',
			up:'',
			djs: '',
			tkdata:[],
			set:{},
			hasnum:0,
			rid:0,
			nums:0,
			dtid:0,
			lefttime:0,
			right_option:'',
			isActive: [],
      mlid:0,
		};
	},
	  onLoad: function (opt) {
			this.opt = app.getopts(opt);
      this.mlid = this.opt.mlid || 0;
			this.getdata();
	  },
	onUnload: function () {
		clearInterval(interval);
	},
	methods: {
		getdata:function(){
			var that = this;
			var id = this.opt.id || 0;
			that.loading = true;
			app.post('ApiKecheng/getTiku', {kcid: id,tmid:that.tmid,op:that.op,rid:that.rid,mlid:that.mlid}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.alert(res.msg);
					return;
				}
				if (res.status == 2) {
					app.alert(res.msg);
						app.goto('complete?rid=' + that.rid);return;
				}
				that.set = res.data.set;
				that.tkdata = res.data.tkdata;
				that.hasnum = res.data.hasnum;
				that.rid = res.data.rid;
				that.nums = res.data.nums;
				that.dtid = res.data.dtid;
				that.lefttime = res.data.lefttime;
				if (res.data.lefttime > 0) {
					interval = setInterval(function () {
						that.lefttime = that.lefttime - 1;
						that.getdjs();
					}, 1000);
				}else{
					app.goto('complete?rid=' + res.rid);return;
				}
				that.loaded();		
			});
		},
		//上一题
		prevquestion:function(){
				var that=this
				that.loading = true;
				app.post('ApiKecheng/prevquestion', {	dtid:that.dtid,mlid:that.mlid}, function(res) {
					that.loading = false;
					if (res.status == 0) {
						app.error(res.msg);
						return;
					}
					that.tkdata = res.data.tkdata;
					that.hasnum = res.data.hasnum;
					that.dtid = res.data.dtid;
					that.isActive = [];
					if(that.tkdata.type==1 && res.data.answer!=null){
						if(that.tkdata.rightcount>1){
							var answer = res.data.answer;
							answer.map((item) => {
							  that.isActive.push(item);
							})
						}
						that.currentindex = res.data.answer;
					}else{
						that.right_option = res.data.answer;
					}
			});
		},
		//下一题
		nextquestion:function(){
				var that=this
				var right_option='';
				if(that.tkdata.type==1){
					right_option = that.currentindex;
					if(right_option==-1){
						app.error('请选择答案');return;
					}
				}
				if(that.tkdata.type==2){
					right_option = that.right_option;
					if(right_option=='' || right_option==undefined){
						app.error('请填写答案');return;
					}
				}
				that.loading = true;
				app.post('ApiKecheng/nextquestion', {dtid:that.dtid,	right_option:right_option,mlid:that.mlid}, function(res) {
					that.loading = false;
					if (res.status == 0) {
						app.error(res.msg);
						return;
					}
					that.tkdata = res.data.tkdata;
					that.hasnum = res.data.hasnum;
					that.dtid = res.data.dtid;
					that.isActive = [];
					if(that.tkdata.type==1 && res.data.answer!=null){
						if(that.tkdata.rightcount>1){
							var answer = res.data.answer;
							answer.map((item) => {
							  that.isActive.push(item);
							})
							console.log(that.isActive)
						}
						that.currentindex = res.data.answer;
					}else{
						that.right_option = res.data.answer;
					}
					if(!res.data.answer){
						that.currentindex='-1';
						that.isActive=[];
					}		
					
			});
		},
		getdjs: function () {
		  var that = this;
		  var totalsec = that.lefttime;
		  if (totalsec <= 0) {
		    that.djs = '00时00分00秒';
				that.finish();
			//that.toanswer();return;
			//app.goto('complete?rid=' + that.rid);return;
		  } else {
		    var houer = Math.floor(totalsec / 3600);
		    var min = Math.floor((totalsec - houer * 3600) / 60);
		    var sec = totalsec - houer * 3600 - min * 60;
		    var djs = (houer < 10 ? '0' : '') + houer + '时' + (min < 10 ? '0' : '') + min + '分' + (sec < 10 ? '0' : '') + sec + '秒';
		    that.djs = djs;
		  }
		},
		bindTextAreaBlur: function (e) {
			var that=this
			that.right_option = e.detail.value
		},
		selectOption: function (e) {
			var index = e.currentTarget.dataset.index
		  var that = this;
		  if(that.tkdata.rightcount==1){
			that.currentindex = index;
		  }else{
				if (that.isActive.indexOf(index) == -1) {
					that.isActive.push(index); //选中添加到数组里
				} else {
					that.isActive.splice(that.isActive.indexOf(index), 1); //取消
				}
				that.currentindex = that.isActive;
		  }
		}, 
		bindanswer:function(e){
			var that=this;
			var op = e.currentTarget.dataset.dttype
			that.op = op;
			that.toanswer();
		},
		finish:function(){
			var that=this
			var right_option='';
			if(that.tkdata.type==1 && that.lefttime>0){
				right_option = that.currentindex;
				if(right_option==-1){
					app.error('请选择答案');return;
				}
			}
			if(that.tkdata.type==2  && that.lefttime>0){
				right_option = that.right_option;
				if(right_option=='' || right_option==undefined){
					app.error('请填写答案');return;
				}
			}
			app.post('ApiKecheng/tofinish', {
				dtid:that.dtid,
				right_option:right_option,
        mlid:that.mlid
			}, function(res) {
				app.showLoading(false);
				if(res.status == 1){
					clearInterval(interval);
					app.goto('complete?rid=' + that.rid);return;
				}
			});
		},
		onPullDownRefresh: function () {
			this.getdata();
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

.option_group .option{ position: relative; padding:30rpx; background:#F8F4F4 ; margin-top: 30rpx; border-radius: 48rpx;border-color: #bbb; }
.option_group .option .t1{ margin-left: 20rpx;border-left: 1px solid;padding-left: 20rpx;}
.option_group .option.on{ background: #FDF1F1; color:#FF5347 ; border: 1px solid #FFAEA8 ; border-color: #FF8D8D;}
/* .option_group .option.on .after{ border:1px solid #FF8D8D; } */
/* .option_group .option .after{ border:1px solid #BBB; height:29rpx;  position: absolute;left: 12%; margin-top: 35rpx;} */
.bottom .upbut{width:240rpx;height: 88rpx; line-height: 88rpx;color: #fff;  border-radius: 44rpx;border: none;font-size:28rpx;font-weight:bold; background: #FD4A46; }	
.bottom .upbut.hui{ background:#E3E3E3;}
.bottom .downbtn{margin-left:50rpx;width:360rpx;height: 88rpx; border-radius: 44rpx; line-height: 72rpx;color: #fff;  border: none;font-size:28rpx;font-weight:bold; background: #FD4A46; }	
.bottom{ margin-top: 30rpx; padding: 30rpx;}
.uni-textarea{ margin-top: 30rpx; background: #FAFAFA; border: 1px solid #EBE5E5;border-radius: 8rpx; padding: 30rpx;}
</style>