<template>
<view v-if="isload">
	<view  class="content">
		<view class="djs" v-if="djs>0">{{djs}}</view>
		<view class="djs" v-else><text class="t1">已阅</text></view>
		<view class="bottom">
			<view class="tips" v-if="sharegive.isanswer"> 请浏览<text class="t1">{{djs?djs:0}}</text>秒并回答问题在分享</view>
			<view class="tips" v-else> 请浏览<text class="t1">{{djs?djs:0}}</text>秒后在分享</view>
			<view class="btnbox">
				<view class="btn1" v-if="sharegive.isanswer" :style="{backgroundColor:t('color1')}" @tap="toanswer" >回答问题</view>
				
				<button :class="'btn2 '+(sharegive.isanswer?'btn3':'') " :style="{backgroundColor:t('color2')}"  @tap.stop="toshare" v-if="getplatform() == 'h5' || getplatform() == 'mp' || getplatform() == 'app'">
					<image :src="pre_url+'/static/img/share_white.png'">我要分享
				</button>
				<button :class="'btn2 '+(sharegive.isanswer?'btn3':'') " v-else :style="{backgroundColor:t('color2')}"   open-type="share" data-callback='sharegive' :data-id='sharegive.id' :data-isanswer='isanswer' >
					<image :src="pre_url+'/static/img/share_white.png'">我要分享</button>
			</view>
		</view>
	</view>
	<view class="modal" v-if="showanswer">
		<view class="question" >
			<view class="close" @tap="toclosed">
				<image :src="pre_url+'/static/img/close.png'" class="buydialog-canimg"></image>
			</view>
			<block v-if="!is_right">
				<view class="title" >
					题目：{{tkdata.title}}
				</view>
				<block v-if="tkdata.rightcount==1">
					<view class="option_group" >
						<view :class="'option flex ' +(index==currentindex?'on':'') "  v-for="(item, index) in tkdata.option" :key="index" @tap="selectOption" :data-index='index'>
						{{tkdata.sorts[index]}}
						<view class="after" ></view> 
						<view class="t1">{{item}}</view></view>
					</view>
				</block>
				<block v-if="tkdata.rightcount>1">
					<view class="option_group" >
						<view :class="'option flex '+(isActive.indexOf(index)!=-1?'on':'')"  v-for="(item, index) in tkdata.option" :key="index" @tap.stop="selectOption" :data-index='index'>
						{{tkdata.sorts[index]}}
						<view class="after" ></view> 
						<view class="t1">{{item}}</view></view>
					</view>
				</block>
				<view class="bottoms flex">
					<button @tap.stop="finish" data-dttype="down" class="downbtn flex-x-center flex-y-center"  :style="{background:t('color1')}" >提交</button>
				</view>
			</block>
			
				
					<view class="right_content"  v-if="is_right==1">
							<view class="right-image">
								<image :src="pre_url+'/static/img/share_right.png'" />
								<text class="t1">回答正确</text>
							</view>
	
							<view class="bottoms flex" v-if="hasnum<nums">
								<button @tap="toanswer" data-dttype="down" class="downbtn flex-x-center flex-y-center"  :style="{background:t('color1')}" >下一题</button>
							</view>
							<view class="end" v-else-if="rightnums>=nums">
									答题完成，立即分享赚取奖励吧
							</view>
							<view class="end" v-else-if="rightnums<nums">
									未正确完成所有题目，请再接再励！
							</view>
					</view>
					<view class="right_content" v-if="is_right==2">
						<view class="right-image">
							<image :src="pre_url+'/static/img/share_error.png'" />
							<text class="t1">回答错误</text>
							<text class="t2">正确答案：{{right_options}}</text>
						</view>
						
						<view class="bottoms flex" v-if="hasnum<nums">
							<button @tap="toanswer" data-dttype="down" class="downbtn flex-x-center flex-y-center"  :style="{background:t('color1')}" >下一题</button>
						</view>
						<view class="end" v-else>
								未正确完成所有题目，请再接再励！
						</view>
					</view>	
				
		</view>
	</view>
	<loading v-if="loading"></loading>
</view>
</template>
<script>
	var app = getApp();
	var interval = null;
	export default {
		data() {
			return {
				pre_url:app.globalData.pre_url,
				isload:false,
				loading:false,
				data:[],
				isanswer:false,
				tkdata:[],
				isActive: [],
				currentindex:'-1',
				showanswer:false,
				nums:0,
				hasnum:0,
				right:false,
				error:false,
				dtid:0,
				is_right:0,
				djs:'',
				right_options:'',
				rightnums:0
			}
		},
		props: {
			controller:{default:'ApiShareGive'},
			sharegive:{},
		},
		mounted:function(){
			this.getdata();
		},
		methods:{
			getdata:function(){
				var that = this;
				that.loading = true;
				app.post(this.controller+'/getdetail',{id:that.sharegive.id},function(res){
					that.loading = false;
					that.data = res.data;
					that.isanswer = res.isanswer
					that.lefttime = res.data.readtimes
					if (that.lefttime > 0) {
          	interval = setInterval(function () {
          		that.lefttime = that.lefttime - 1;
          		that.getdjs();
          	}, 1000);
          }
					that.isload = true;
				});
			},
			toanswer:function(e){
				var that=this
				that.is_right=false
				app.post('ApiShareGive/getTiku', {giveid: that.data.id}, function(res){
					if(res.status==1){
						that.tkdata = res.data.tkdata
						//that.nums   = res.data.nums
						that.dtid = res.data.dtid
						//that.hasnum = res.data.hasnum
						that.showanswer = true;
					}else{
						app.error('题目获取失败')
					}
				})
			},
			toclosed:function(e){
				var that=this
				that.showanswer = false;
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
			finish:function(){
				var that=this
				var right_option='';
				right_option = that.currentindex;
				if(right_option==-1){
					app.error('请选择答案');return;
				}

				app.post('ApiShareGive/tofinish', {
					dtid:that.dtid,
					right_option:right_option,
				}, function(res) {
					app.showLoading(false);
					if(res.status == 1){
						that.is_right = res.is_right
						that.nums = res.nums
						that.hasnum = res.hasnum
						that.rightnums = res.rightnums
						that.right_options = res.right_options
						that.getdata()
						clearInterval(interval);	
					}else{
						app.error('服务器错误');return;
					}
				});
			},
			toshare:function(e){
				var that = this;
				if(!that.isanswer){
						app.error('请先回答问题');return;
				}	
				var giveid = that.sharegive.id
				this.$emit('toshare',{giveid: giveid});
			},
			getdjs: function () {
			  var that = this;
			  var totalsec = that.lefttime;
			  if (totalsec <= 0) {
			    that.djs = '00';
					app.post('ApiShareGive/addread', {giveid: that.sharegive.id}, function (res) {
						if(res.status==1){
								app.success(res.msg)
						}
						clearInterval(interval);	
					})
			  } else {
			    var sec = totalsec;
			    var djs =  (sec < 10 ? '0' : '') + sec;
			    that.djs = djs;
			  }
			},
		}
	}
</script>
<style>
.djs{ border-radius: 50%; position: fixed; right:5%; top:30rpx; width: 80rpx; height: 80rpx; right: 10rpx; text-align: center; line-height: 80rpx; font-size: 28rpx;background: rgba(0,0,0, 0.4); color: #fff; }

.bottom{ width: 100%;position: fixed; bottom:0;background: #fff;align-items: center; }
.bottom .btnbox{ display: flex;}
.bottom .tips{ height: 60rpx;line-height: 60rpx;text-align: center; }
.bottom .tips .t1{ color: red;font-weight: bold;}
.bottom .btn1{ width: 50%;text-align: center;color: #fff;height: 80rpx;line-height: 80rpx;}
.bottom .btn2{width: 100%; height: 80rpx;line-height: 80rpx;text-align: center;color: #fff; display: flex;justify-content: center;align-items: center;}
.bottom .btn2 image{ width: 40rpx; height:40rpx;margin-right: 10rpx;}
.bottom .btn2.btn3{ width: 50%;} 

.bottoms .upbut{width:240rpx;height: 88rpx; line-height: 88rpx;color: #fff;  border-radius: 44rpx;border: none;font-size:28rpx;font-weight:bold; background: #FD4A46; }	
.bottoms .upbut.hui{ background:#E3E3E3;}
.bottoms .downbtn{margin-left:50rpx;width:360rpx;height: 88rpx; border-radius: 44rpx; line-height: 72rpx;color: #fff;  border: none;font-size:28rpx;font-weight:bold; background: #FD4A46; }	
.bottoms{ margin-top: 30rpx; padding: 30rpx;}


.right_content{ background: #fff; margin: 30rpx; border-radius: 10rpx; padding: 30rpx;text-align: center;}
.right_content .t1{ color: #333; font-weight: 30rpx; font-weight: bold; display: block; margin-bottom: 20rpx;}
.right_content .t2{ color:#93949E;font-size: 26rpx;}

.right_content .right-image image{ width: 80rpx;height: 80rpx;}

.modal{ position: fixed;background: rgba(0,0,0, 0.4); width: 100%; height:100%; top:0;display: flex;justify-content: center;}
.question{ position: absolute;background: #fff; width: 90%; padding:30rpx;margin-top: 130rpx;border-radius: 10rpx;}

.question .close{ position: absolute; top: 0; right: 0;padding:20rpx;z-index:9999}
.question .close image{ width: 30rpx; height:30rpx; }

.question .title{ font-size: 30rpx; color: #333; font-weight: bold;}
.option_group .option{ position: relative; padding:20rpx 30rpx; background:#F8F4F4 ; margin-top: 30rpx; border-radius: 48rpx;border-color: #bbb; }
.option_group .option .t1{ margin-left: 20rpx;border-left: 1px solid;padding-left: 20rpx;}
.option_group .option.on{ background: #FDF1F1; color:#FF5347 ; border: 1px solid #FFAEA8 ; border-color: #FF8D8D;}



.end{ margin-top: 30rpx;}
</style>