<template> 
<view>
	<block v-if="isload">
 <view class="pyramid-container">
  <view class="pyramid"> 
    <!-- 第一层 -->  
    <view class="level level-1">  
      <view class="user-container" v-if="list[0]">  
        <image class="avatar" :src="list[0].headimg?list[0].headimg:`${pre_url}/static/img/touxiang.png`" />
        <text class="user-name">{{list[0].nickname}}</text>  
				<text class="user-name">贡献值：{{list[0].num}}</text>
				<text class="user-name">{{dateFormat(list[0].createtime,'m-d H:i')}}</text>
      </view> 
			<view class="user-container" v-else>
			  <image class="avatar" :src="`${pre_url}/static/img/touxiang.png`" />
			</view>  
    </view>  
    
    <!-- 第一层与第二层之间的两个箭头 -->  
    <view class="arrow-container">  
      <image class="arrow" :src="pre_url+'/static/img/left.png'" /> 
      <image class="arrow" :src="pre_url+'/static/img/right.png'" /> 
    </view>  
    
    <!-- 第二层 -->  
    <view class="level level-2">  
			<view v-for="(item, index) in 2" :key="index">
				<view class="user-container" v-if="list[index+1]">  
				 <image class="avatar" :src="list[index+1].headimg?list[index+1].headimg:`${pre_url}/static/img/touxiang.png`" />
					<text class="user-name">{{list[index+1].nickname}}</text>  
					<text class="user-name">贡献值：{{list[index+1].num}}</text>
					<text class="user-name">{{dateFormat(list[index+1].createtime,'m-d H:i')}}</text>
				</view>  
				<view class="user-container" v-else>
				 <image class="avatar" :src="`${pre_url}/static/img/touxiang.png`" />
				</view>  
			</view> 
    </view>  
    
    <!-- 第二层与第三层之间的四个箭头 -->  
    <view class="arrow-container">  
      <image class="arrow" :src="pre_url+'/static/img/left.png'" /> 
      <image class="arrow" :src="pre_url+'/static/img/right.png'" /> 
      <image class="arrow" :src="pre_url+'/static/img/left.png'" /> 
      <image class="arrow" :src="pre_url+'/static/img/right.png'" /> 
    </view>  

    <!-- 第三层 -->
    	<view class="level level-3">  
    		<view v-for="(item, index) in 4" :key="index">
    	  <view class="user-container" v-if="list[3+index]">  
    	   <image class="avatar" :src="list[3+index].headimg?list[3+index].headimg:`${pre_url}/static/img/touxiang.png`" />
    	    <text class="user-name">{{list[3+index].nickname}}</text>
    			<text class="user-name">贡献值：{{list[3+index].num}}</text>
    			<text class="user-name">{{dateFormat(list[3+index].createtime,'m-d H:i')}}</text>
    	  </view> 
    		<view class="user-container" v-else>
    		  <image class="avatar" :src="`${pre_url}/static/img/touxiang.png`" />
    		</view>  
    		</view>
      </view> 
		</view> 
	 <!-- 进行中信息 -->   
		<view class="progress-info">  
			<view class="progress-container">  
				<image :src="pre_url+'/static/img/redpacket-3.png'" class="progress-img" />  
				<text class="progress-text" v-if="info.id">{{info.status==0?'进行中':'已成团'}}</text>  
			</view>  
		</view>  
<!-- 新增的 pyramidinfo -->  
    <view class="pyramidinfo">  
      <view class="info-row">  
        <text class="info-item">队伍号</text>  
        <text class="info-item">{{info.tuannum}}</text>  
      </view>  
      <view class="info-row">  
        <text class="info-item">开始时间</text>  
        <text class="info-item" v-if="info.createtime">{{dateFormat(info.createtime,"Y-m-d H:i")}}</text>  
      </view>  
			<view class="info-row">
			  <text class="info-item">支付金额</text>  
			  <text class="info-item">{{info.totalprice}}</text>  
			</view>  
      <view class="info-row">  
        <text class="info-item">状态</text>  
        <text class="info-item" v-if="info.id" >{{info.status==0?'进行中':'已成团'}}</text>  
      </view> 
      <view class="info-row">  
        <text class="info-item">奖金</text>  
        <text class="info-item">{{t('佣金')}}{{info.commission}}+{{t('积分')}}{{info.score}}</text>  
      </view>  
			<view class="info-row">
			  <text class="info-item">成团奖励者</text>  
			  <text class="info-item">{{tmember.nickname}}</text>  
			</view> 
    </view>   
  </view>  
<!-- 按钮容器 -->  
	<view class="button-container">  
    <button class="btn" :data-url="'orderproduct?tid=' + tid" @tap="goto">拼团商品</button>  
    <button class="btn" @tap="gotofutou" >复投商品</button>  
  </view>
	</block>
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
			isload:false,
			loading:false,
			opt:{},
			tid:0,
			list:[],
			info:{},
			pre_url:app.globalData.pre_url,
			tmember:{}
    };
  },
	onLoad: function(opt) {
	  this.opt = app.getopts(opt);
	  this.tid = this.opt.tid || 0;
	  this.getdata();
	},
	methods: {
		gotofutou:function(){
			if(this.info.isfutou){
				app.goto('futouproduct?id=' + this.info.jipinid)
			}else{
				app.error('该活动暂不支持自动复投')
			}
		},
	  getdata:function(){
	  	var that = this;
	  	that.loading = true;
	  	var tid = that.tid || 0;
	  	app.get('ApiJipinLog/detail',{tid:tid}, function (res) {
	  		that.loading = false;
				
	      if (res.status == 1) {
	        that.list = res.list || [];
					that.info = res.data || {};
					that.tmember = res.tmember || {};
					console.log(that.list)
					console.log(that.info)
	        that.loaded();
	      }else{
	      		if(res.msg) {
	      			app.alert(res.msg, function() {
	      				// if (res.url) app.goto(res.url);
	      			});
	      		}
	      	}
	  	});
	  },
	}
};
</script> 

<style>  
.pyramid-container {  
  border: 20rpx solid transparent; /* 初始边框透明 */
  border-image: linear-gradient(to bottom,  rgb(253, 74, 70), rgba(255, 182, 193, 1)) 10; 
	/* 背景色渐变设置 */  
	background: linear-gradient(to bottom,  rgb(253, 74, 70), rgba(255, 182, 193, 1)); /* 红色渐变淡 */ 
}  
.pyramid {  
  display: flex;  
  flex-direction: column;  
  align-items: center;  
  margin-top: 10rpx; /* 页面的顶部边距 */  
  border-radius: 20rpx; /* 设置内层圆角 */  
  background: white; /* 内部背景可以设为白色，或根据需要自定义 */  
	padding: 20rpx 10rpx 10rpx 10rpx;
}  

/* 定义层级的样式 */  
.level {  
  display: flex;  
  justify-content: space-around; /* 等距离布局 */  
  width: 100%; /* 让用户层占满整个父容器 */  
}  

.user-container {  
  display: flex;  
  flex-direction: column; /* 垂直排列每个用户 */  
  align-items: center; /* 用户居中对齐 */  
}  

/* 头像样式 */  
.avatar {  
  width: 100rpx; /* 头像宽度设置为 100rpx */  
  height: 100rpx; /* 头像高度设置为 100rpx */  
  border-radius: 50%; /* 圆形头像 */  
}  

/* 用户名文本样式 */  
.user-name {  
	font-size: 20rpx;
  text-align: center; /* 文本居中 */  
}  

/* 箭头的容器样式 */  
.arrow-container {  
  display: flex;  
  justify-content: space-around; /* 等比例间隔 */  
  width: 100%; /* 让箭头层占满整个父容器 */  
  /* margin-top: 10rpx; */
}  

/* 箭头的样式 */  
.arrow {  
  width: 50rpx; /* 箭头宽度 */  
  height: 50rpx; /* 箭头高度 */  
}  
/* pyramidinfo 样式 */  
.pyramidinfo {  
  margin-top: 40rpx; /* 与 pyramid 之间的间距 */  
  background: white;  
  border-radius: 20rpx; /* 圆角 */  
  padding: 10rpx 30rpx; /* 内部间距 */
	margin-top: -50rpx;
}  

.info-row {  
  display: flex;  
  justify-content: space-between; /* 两端对齐 */  
  width: 100%; /* 占满宽度 */  
  margin: 20rpx 0; /* 每行之间的间距 */  
}  

/* 信息项样式 */  
.info-item {  
  text-align: justify; /* 中间对齐 */  
}  
/* 按钮容器样式 */  
.button-container {  
  display: flex;  
  justify-content: space-around; /* 分散对齐按钮 */  
  margin-top: 10rpx; /* 与 pyramidinfo 的间距 */  
  padding: 20rpx; /* 按钮上下间距，增加可点击 area */  
}  

/* 按钮样式 */  
.btn {  
  border-radius: 50px; /* 椭圆形按钮 */  
  background-color:  rgb(253, 74, 70);/* 按钮背景色 */  
  color: white; /* 按钮文本颜色 */  
  padding: 8rpx 60rpx; /* 加大内部填充以增大按钮宽度 */  
  border: none; /* 去除边框 */  
  text-align: center; /* 中间对齐文本 */  
  cursor: pointer; /* 鼠标样式 */
	width: 45%;
}  
/* 进行中信息样式 */  
.progress-info {  
  text-align: center;  
  margin-top: 20rpx;  
}  

.progress-container {  
  position: relative;  
  display: inline-block;  
}  

.progress-img {  
  width: 300rpx;  
  height: 80rpx;  
}  

.progress-text {  
  position: absolute;  
  top: 40%;  
  left: 50%;  
  transform: translate(-50%, -50%);  
  font-size: 28rpx;  
  color: red;  
}  
</style>