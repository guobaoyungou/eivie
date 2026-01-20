<template>
<view class="wrap">
	<block v-if="isload">
    <view class="title">{{detail.name}}</view>
    <view style="display: flex;justify-content: space-between;padding: 30rpx; 20rpx;align-items: center;">
      <view style="display: flex;align-items: center;">
        <view @tap="goto" :data-url="'/pagesB/kecheng/lecturercenter?id='+detail.lecturer.id" class="headimg">
          <image v-if="detail.lecturer && detail.lecturer.headimg" :src="detail.lecturer.headimg" style="width: 100%;height: 100%;"></image>
        </view>
        <view style="line-height: 50rpx;">
          <view style="max-width: 400rpx;overflow:hidden;text-overflow: ellipsis;white-space: nowrap;">
            <text v-if="detail.lecturer && detail.lecturer.nickname">{{detail.lecturer.nickname}}</text>
          </view>
          <view style="color: #757575;display: flex;align-items: center;">
            {{detail.createtime}} 
            <div style="width: 4rpx;height: 4rpx;border-radius: 4rpx;overflow: hidden;background-color: #000;margin: 0 10rpx;"></div> 
            {{detail.readnum}}次浏览
          </view>
        </view>
      </view>
      <view>
        <image @tap="shareClick" class="img" :src="pre_url+'/static/img/share.png'" style="width: 40rpx;height: 40rpx;"/>
      </view>
    </view>
    <view v-if="freecontent">
      <view style="padding: 0 20rpx;font-size: 28rpx;">
        <parse :content="freecontent" ></parse>
      </view>
    </view>
    <view v-if="!detail.needbuy">
      <block v-if="detail.kctype==1">
        <block v-if="pagecontent">
          <dp :pagecontent="pagecontent"></dp>
        </block>
      	<view style="margin-bottom: 40rpx;"></view>
      </block>
      <view class="audo-video" v-if="detail.kctype==2">
      	<view class="audoimg"><image :src="detail.pic"/></view>
      	<view class="play">
      		<view class="play-left">
      			<image :src="pre_url+'/static/img/video_icon.png'" v-show="playshow" @tap="play"></image>   
      	    <image :src="pre_url+'/static/img/play.png'" v-show="!playshow" @tap="pauseaudio"></image> 
      	    <text>{{nowtime}}</text>
      	  </view>
      	  <view class="play-right">
      			<slider @change="sliderChange"  @changing="sliderChanging" class="slider" block-size="16"  :min="0" :max="time"  :value="currentTime" activeColor="#595959"  />
      	  </view>
      		<view class="play-end"><text>{{duration}}</text></view>
      	</view>
        <view v-if="pagecontent">
          <dp :pagecontent="pagecontent"></dp>
          <view style="margin-bottom: 40rpx;"></view>
        </view>
      </view>
      <view class="videobox" v-if="detail.kctype==3"> 
      	<video class="video" id="video" :autoplay="true" :src="detail.video_url"  :initial-time="detail.startTime" @pause="pause" @timeupdate="timeupdate" @ended="ended"></video>
      		<view class="speed" v-if="showspeed">
      				<view class="t1" v-if="showspeed==1" @tap="setspeed">{{speedtext}}</view>
      				<view v-if="showspeed==2" class="f2">
      					<block v-for="(item,index) in speedlist">
      						<view  :class="'f22 '+(rate==item?'on':'')" @tap="bindButtonRate" :data-rate='item'><text class="t2">{{item}}</text><text>X</text></view>
      					</block>
      				</view>
      		</view>
          <view v-if="pagecontent">
            <dp :pagecontent="pagecontent"></dp>
            <view style="margin-bottom: 40rpx;"></view>
          </view>
      </view>
    </view>

    <block v-if="detail.needbuy">
      <view style="position: relative;width: 100%;height: 450rpx;overflow: hidden;">
        <block v-if="detail.kctype==1 && detail.detail">
          <view style="padding:0 20rpx;">付费后可以查看付费后可以查看付费后可以查看付费后可以查看付费后可以查看付费后可以查看付费后可以查看</view>
          <view style="padding:0 20rpx;">付费后可以查看付费后可以查看付费后可以查看付费后可以查看付费后可以查看付费后可以查看付费后可以查看</view>
          <view style="padding:0 20rpx;">付费后可以查看付费后可以查看付费后可以查看付费后可以查看付费后可以查看付费后可以查看付费后可以查看</view>
          <view style="padding:0 20rpx;">付费后可以查看付费后可以查看付费后可以查看付费后可以查看付费后可以查看付费后可以查看付费后可以查看</view>
          <view style="padding:0 20rpx;">付费后可以查看付费后可以查看付费后可以查看付费后可以查看付费后可以查看付费后可以查看付费后可以查看</view>
          <view style="padding:0 20rpx;">付费后可以查看付费后可以查看付费后可以查看付费后可以查看付费后可以查看付费后可以查看付费后可以查看</view>
        </block>
        <block v-else>
          <video v-if="detail.video_url" class="video"  style="width: 100%;" :autoplay="false" src=""></video>
        </block>
        <view class="needbuytip">
          付费后查看详情
        </view>
      </view>
    </block>
    <view style="width: 100%;height:100rpx;clear:both;"></view>
    <view v-if="detail.needbuy || showbuy" style="position:fixed;width: 100%;left: 0;bottom: 60rpx;z-index: 999;">
      <view @tap="tobuy"  :style="{background:t('color1')}" class="tobuy">
        查看完整版：{{detail.price}}元
      </view>
    </view>
    <view v-if="sharetypevisible" class="popup__container" style="z-index: 99;">
    	<view class="popup__overlay" @tap.stop="handleClickMask"></view>
    	<view class="popup__modal" style="height:320rpx;min-height:320rpx">
    		<!-- <view class="popup__title">
    			<text class="popup__title-text">请选择分享方式</text>
    			<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hidePstimeDialog"/>
    		</view> -->
    		<view class="popup__content">
    			<view class="sharetypecontent">
    				<view class="f1" @tap="shareapp" v-if="getplatform() == 'app'">
    					<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
    					<text class="t1">分享给好友</text>
    				</view>
    				<view class="f1" @tap="sharemp" v-else-if="getplatform() == 'mp'">
    					<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
    					<text class="t1">分享给好友</text>
    				</view>
    			<!-- 	<view class="f1" @tap="sharemp" v-else-if="getplatform() == 'h5'">
    					<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
    					<text class="t1">分享给好友</text>
    				</view> -->
    				<button class="f1" open-type="share" v-else-if="getplatform() != 'h5'">
    					<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
    					<text class="t1">分享给好友</text>
    				</button>
    				<view class="f2" @tap="showPoster">
    					<image class="img" :src="pre_url+'/static/img/sharepic.png'"/>
    					<text class="t1">生成分享图片</text>
    				</view>
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
	</block>
	<nomore text="没有更多课程了" v-if="nomore"></nomore>
	<nodata text="没有查找到相关课程" v-if="nodata"></nodata>
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
			isplay: 0,
      pre_url:app.globalData.pre_url,
			detail: [],
			datalist: [],
			pagecontent: "",
			playshow:true, //播放的图片
			stipshow:false, //暂停的图片
			lock: false, // 锁
			status: 1, // 1暂停 2播放
			currentTime: 0,  //当前进度
			duration: '', // 总进度
			videoContext: '',
			pagenum:1,
			studlog:[],
			innerAudioContext: '',
			startTime:'',
			seek: false ,//是否处于拖动状态
			time:'',
			playJd:0,
			nowtime:'',
			isauto:false,
			showspeed:0,
			speedlist:[],
			rate:1,
			speedtext:'倍速',
			nodata: false,
      mlid:0,
			alert_time:0,
			alert_status:0,
      freecontent:'',
      kechengset:'',
      showbuy:false,
      
      sharepic: "",
      sharetypevisible: false,
      showposter: false,
      posterpic: "",
		};
	},
	onLoad: function (opt) {
		this.opt = app.getopts(opt);
    this.id = this.opt.id || 0;
    this.kcid = this.opt.kcid || 0;
		this.getdata();
		this.getdatalist(); 
		this.innerAudioContext = uni.createInnerAudioContext();
	},
	onShow:function(){
		var that=this
		clearInterval(interval);
		this.innerAudioContext.stop();
	},
	onUnload: function () {
		clearInterval(interval);
		var that=this
		this.innerAudioContext.stop();
	},
	onHide(){
		this.playshow = false
	},
	onReachBottom: function () {
		if (!this.nodata && !this.nomore) {
			this.pagenum = this.pagenum + 1;
			this.getdatalist(true);
		}
	},
  onShareAppMessage:function(){
  	return this._sharewx({title:this.detail.name,pic:this.detail.pic});
  },
  onShareTimeline:function(){
  	var sharewxdata = this._sharewx({title:this.detail.name,pic:this.detail.pic});
  	var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
  	console.log(sharewxdata)
  	console.log(query)
  	return {
  		title: sharewxdata.title,
  		imageUrl: sharewxdata.imageUrl,
  		query: query
  	}
  },
	methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiKecheng/lecturermldetail', {id: that.id,kcid:that.kcid}, function (res) {
				that.loading = false;
        if(res.status == 1){
          if(res.kechengset){
            that.kechengset = res.kechengset;
          }
          var detail = res.detail;
          that.detail = detail;
          that.isauto = res.isauto;
          that.currentTime = detail.startTime
          that.showspeed = res.showspeed
          that.speedlist = res.speedlist
					that.alert_time = (detail.mianfei_time>0 && detail.ismianfei==1)?detail.mianfei_time:0
          uni.setNavigationBarTitle({
          	title: detail.name
          });
          if(detail.jumpurl){
          	app.goto(detail.jumpurl);return;
          }
          that.studylog = res.studylog;
          if(detail.detail && detail.detail !='付费后查看详情'){
              var pagecontent = JSON.parse(detail.detail);
              that.pagecontent = pagecontent;
          }
          
          if(detail.freecontent){
            that.freecontent = detail.freecontent;
          }
          that.loaded({title:detail.name,pic:detail.pic});
          that.addstudy();
          if(detail.kctype>1){
          	interval = setInterval(function () {
          		that.addstudy();
          	}, 10000);
          }
          that.play();
        }else{
          app.alert(res.msg)
        }

			});
		},
		setspeed:function(e){
			var that=this
			if(that.showspeed==2){
					that.showspeed = 1
			}else	if(that.showspeed==1){
					that.showspeed = 2
			}
		},
		getdatalist: function(loadmore){
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
			var that = this;
			var pagenum = that.pagenum;
			var kcid = that.opt.kcid ? that.opt.kcid : '';
			var order = that.order;
			var field = that.field; 
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
			app.post('ApiKecheng/getmululist', {pagenum: pagenum,field: field,order: order,id:kcid}, function (res) { 
				that.loading = false;
				uni.stopPullDownRefresh();
				var data = res.data;
				if (pagenum == 1) {
				  that.datalist = data;
				  if (data.length == 0) {
				    that.nodata = true;
				  }
					
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
		scrolltolower: function () {	     
			if (!this.nomore) {
				this.pagenum = this.pagenum + 1;    
				this.getdatalist(true);
			}
		},
		payvideo: function () {
			this.isplay = 1;
			uni.createVideoContext('video').play();
		},
		parsevideo: function () {
			this.isplay = 0;
			uni.createVideoContext('video').stop();
		},
		pause:function(){
			//将暂停播放时间请求
			var that = this
			var id = that.opt.id ? that.opt.id : '';
			that.addstudy();
		},
		addstudy:function(){
			var that = this
      var studylog = that.studylog; 
			var id       = that.detail.id;
      var datalist = that.datalist;
			app.post('ApiKecheng/addstudy', {logid:studylog.id,currentTime:that.currentTime,playJd:that.playJd}, function (res) {
				that.detail.startTime = that.currentTime
        if(res.status == 1 ){
          //查询当前学习章节
          if(that.detail.learnkey>=0 && datalist[that.detail.learnkey] && datalist[that.detail.learnkey].jindu){
            datalist[that.detail.learnkey].jindu = res.jindu
            if(res.jindu == '已学完' || res.jindu >= 100){
              if(datalist[that.detail.learnkey].study_status == 0){
                datalist[that.detail.learnkey].study_status = 1;
              }
            }
            that.datalist = datalist;
          }
        }
			})
		},
		timeupdate:function(e){
			//跳转到指定播放位置 initial-time 时间为秒
			let that = this;
      if(that.detail.learnkey<0){
        return;
      }
      var studylog = that.studylog; 
			//播放的总时长
			var duration = e.detail.duration;
			//实时播放进度 秒数
			var currentTime = e.detail.currentTime;
			
			if(that.alert_time>0 && currentTime>that.alert_time && that.alert_status == 0){
				that.alert_status = 1
				var video = uni.createVideoContext('video');
				video.pause();
				video.seek(that.alert_time)
        that.alert_status = 0;
        that.showbuy = true;
        app.alert('试看时长已结束，前去开通继续观看');
        
				// app.confirm('试看时长已结束，前去开通继续观看', ()=>{
				// 	app.goto('/activity/kecheng/product?id='+that.detail.kcid);
				// }, ()=>{
				// 	that.alert_status = 0;
				// });
				return ;
			}
			//当前视频进度
			// console.log("视频播放到第" + currentTime + "秒")//查看正在播放时间，以秒为单位
			var jump_time = that.currentTime   //上次结束时间
			if (that.detail.isjinzhi == 1) {
        if (currentTime > jump_time && currentTime - jump_time > 2 && that.datalist[that.detail.learnkey].jindu!='100' ) {
            let videoContext = wx.createVideoContext('video');
            videoContext.seek(that.currentTime);
            wx.showToast({
              title: '未完整看完该视频，不能快进',
              icon: 'none',
              duration: 2000
            });
        }
			}
			
			that.currentTime  = currentTime; //实时播放进度
			var min = Math.floor(currentTime/60)
			var second = currentTime%60
			that.nowtime = (min>=10?min:'0'+min)+':'+(second>=10?second:'0'+second)
			  //计算进度
			if(that.playJd < 100){
				that.playJd = (that.currentTime/(duration-1)).toFixed(2)*100;
				if(that.playJd>100) that.playJd=100
			}
			that.datalist[that.detail.learnkey].jindu = that.playJd.toFixed(1)
		
		},
		ended(){
			var that=this;
			if(that.detail.is_give_score){
				app.get('ApiKecheng/givescore', {kccid:that.detail.id}, function (res) {
					if(res.status){
						app.success(res.msg);
					}
				})
			}
		},
		bindButtonRate:function(e){  //设置倍速
				var that=this
				var rate = e.currentTarget.dataset.rate
				that.rate = rate;
				that.showspeed=1;

				that.speedtext = (rate=='1.0'?'正常':rate+'X');
				uni.createVideoContext('video').playbackRate(Number(rate));
		},
		// 播放
		play() {
			var that=this
			this.playshow=true;
			this.innerAudioContext.autoplay = true;
			this.innerAudioContext.src = that.detail.voice_url;
			this.innerAudioContext.play();
			this.innerAudioContext.onCanplay(()=> {
				this.innerAudioContext.duration;
				setTimeout(() => {
					that.time = this.innerAudioContext.duration.toFixed(0);
					var min = Math.floor(that.time/60);
					var second = that.time%60
					this.duration = (min>10?min:'0'+min)+':'+(second>10?second:'0'+second);	
				}, 1000)
			})  
			that.startTime =  that.detail.startTime

			if(that.detail.startTime >=that.detail.video_duration){
				that.startTime =  0
			}

			this.innerAudioContext.seek(that.startTime)
			this.innerAudioContext.onPlay(() => {
				that.playshow=false;   
			});
			this.innerAudioContext.onPause(() => {
				//that.addstudy();
				that.playshow=true;
			});
			this.innerAudioContext.onEnded(() => {
				that.playJd = 100;
				clearInterval(interval);
				that.addstudy();
				that.playshow=true;
			});
			this.innerAudioContext.onTimeUpdate(() => {
				var nowtime = this.innerAudioContext.currentTime.toFixed(0)
				var min = Math.floor(nowtime/60)
				var second = nowtime%60
				that.nowtime = (min>=10?min:'0'+min)+':'+(second>=10?second:'0'+second)
				  //计算进度
				if(that.playJd < 100 && that.innerAudioContext.duration > 0){
					that.playJd = ((nowtime/that.innerAudioContext.duration).toFixed(2))*100;
					if(that.playJd>100) that.playJd=100
				}
				that.currentTime = this.innerAudioContext.currentTime;
			//	console.log(that.currentTime);
				//console.log('播放进度',that.innerAudioContext.currentTime,)			
			});
		 
	
		}, 
		// 暂停
		pauseaudio() {
			var that=this
			this.innerAudioContext.pause();
			that.addstudy();
		},
		// 拖动进度条
		sliderChange(data) {
			var that=this;
			if(that.detail.isjinzhi == 1 && data.detail.value>that.detail.startTime && that.datalist[that.detail.learnkey].jindu!='100'){
				app.error('未完整听完该音频，不能快进');return;
			}else{
				that.currentTime = data.detail.value;
				this.innerAudioContext.seek(data.detail.value)
			}	
		},
		//拖动中
		sliderChanging(data) {	
			this.currentTime = data.detail.value	
		},
    tobuy: function (e) {
    	var that=this;
      var kechengset = that.kechengset;
      if(that.osname == 'ios' && !kechengset.ios_canbuy){
        app.alert(kechengset.ios_tip);
        return;
      }
    	//购买
      app.showLoading();
    	app.post('ApiKecheng/createOrder', {
    		kcid:that.detail.kcid,
    	}, function(res) {
    		app.showLoading(false);
    		if(res.status == 1){
          app.goto('/pagesExt/pay/pay?id=' + res.payorderid);
        }else{
          app.error(res.msg);
          return;
        }
    	});
    },
    shareClick: function () {
    	this.sharetypevisible = true;
    },
    handleClickMask: function () {
    	this.sharetypevisible = false
    },
    showPoster: function () {
    	var that = this;
    	that.showposter = true;
    	that.sharetypevisible = false;
    	app.showLoading('生成海报中');
    	app.post('ApiKecheng/getposter', {proid: that.detail.kcid,type:'lecturer',id: that.detail.id}, function (data) {
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
		sharemp:function(){
			app.error('点击右上角发送给好友或分享到朋友圈');
			this.sharetypevisible = false
		},
		shareapp:function(){
      // #ifdef APP-PLUS
			var that = this;
			that.sharetypevisible = false;
			uni.showActionSheet({
		    itemList: ['发送给微信好友', '分享到微信朋友圈'],
		    success: function (res){
					if(res.tapIndex >= 0){
						var scene = 'WXSceneSession';
						if (res.tapIndex == 1) {
							scene = 'WXSenceTimeline';
						}
						var sharedata = {};
						sharedata.provider = 'weixin';
						sharedata.type     = 0;
						sharedata.scene    = scene;
						sharedata.title    = that.detail.name || '';
						//sharedata.summary = app.globalData.initdata.desc;
						sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesB/kecheng/lecturermldetail?scene=id_'+that.detail.id+'-kcid_' + that.detail.kcid+'-pid_' + app.globalData.mid;
						sharedata.imageUrl = that.detail.pic || '';
						var sharelist = app.globalData.initdata.sharelist;
						if(sharelist){
							for(var i=0;i<sharelist.length;i++){
								if(sharelist[i]['indexurl'] == '/pagesB/kecheng/lecturermldetail'){
									sharedata.title    = sharelist[i].title;
									sharedata.summary  = sharelist[i].desc;
									sharedata.imageUrl = sharelist[i].pic;
									if(sharelist[i].url){
										var sharelink = sharelist[i].url;
										if(sharelink.indexOf('/') === 0){
											sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#'+ sharelink;
										}
										if(app.globalData.mid>0){
											 sharelink += (sharelink.indexOf('?') === -1 ? '?' : '&') + 'pid='+app.globalData.mid;
										}
										sharedata.href = sharelink;
									}
								}
							}
						}
						uni.share(sharedata);
					}
		    }
		  });
      // #endif
		},
	}
};
</script>
<style>
  page{background-color: #fff;}
.wrap{ background: #fff;}
.wrap .title{ padding: 30rpx 20rpx; font-size: 42rpx; color: #111111; font-weight: bold; justify-content: space-between;}
	
.hide{ display: none;}
.provideo{border-radius:27rpx;width:750rpx;position:absolute;z-index:1000;align-items:center;justify-content:space-between}
.provideo image{ width: 100%;}
.provideo .txt{flex:1;text-align:center;padding-left:10rpx;font-size:24rpx;color:#333}
.videobox{width:100%;text-align:center;background:#000;position: relative; }
.videobox .video{width:100%; }
.videobox .parsevideo{margin:0 auto;margin-top:20rpx;height:40rpx;line-height:40rpx;color:#333;background:#ccc;width:140rpx;border-radius:25rpx;font-size:24rpx}
.videobox .speed{ position: absolute; right: 0; bottom:96rpx; display: flex;}
.videobox .speed .f2{ display: flex; flex-direction: column; color:#7B7F80;background: rgba(0,0,0,0.6);padding-bottom: 20rpx;}
.videobox .speed .f22{padding: 20rpx 20rpx 0rpx 20rpx; font-size: 20rpx;} 
.videobox .speed .f22 .t2{ color:#fff; font-size: 30rpx; margin-right: 4rpx;}
.videobox .speed .f22.on{ color: #00BFFF;}
.videobox .speed .f22.on .t2{color: #00BFFF;font-size: 34rpx; }
.videobox .speed .t1{ background: rgba(0,0,0,0.5); padding:10rpx; color: #C4C3C3; font-size: 20rpx;}


.content_box{ background: #fff;}
.content_box .title{ line-height: 60rpx; margin-left: 30rpx; padding:20rpx 0rpx;border-bottom: 1px solid #F7F7F7;}
.content_box .title .t1{ font-size: 32rpx; font-weight: bold;  }
.content_box .title .t2{ font-size: 24rpx; background:#fff;border:1px solid #cdcdcd;border-radius:3px; margin-right: 20rpx; padding: 0rpx 20rpx; border-radius: 10rpx;}
.mulubox{ padding-top: 35rpx; padding-left: 30rpx;}
.left_box{ display: flex;}
.left_box image{ width: 44rpx; height:44rpx; margin-right: 40rpx; margin-top: 26rpx; }
.right_box{ border-bottom: 1px solid #F6F6F6; padding-bottom: 30rpx; width: 100%; justify-content: space-between;}
.title_box{ width: 80%;}
.title_box .t1{ color: #1E252F; font-size: 28rpx; font-weight: bold;}
.title_box .t2{ color: #B8B8B8;font-size: 24rpx;line-height: 60rpx; margin-right: 15rpx;}
.right_box .on text{ color:#FF5347}
.right_box .on .t1{  color:#FF5347}
.skbtn{  background-color: #FFEEEC; padding: 6rpx 20rpx; margin-right: 10px; height: 44rpx; width: 90rpx; color: #FC6D65; font-size: 24rpx; border-radius: 22rpx; margin-top: 20rpx;}
.right_box .jindu{ color:#FF5347; margin-right: 20rpx; font-size: 24rpx;}
.baner{ width:100%; overflow: hidden; box-sizing: border-box; position: relative;}
.audioBg{display: block; width:100%; height:370rpx;}
.transmit{ position: absolute; left: 0;  right: 0; top: 0; bottom:0; margin: auto; display: block; width:80rpx; height:80rpx;}

.content {	padding: 20upx;}
.list {font-size: 28upx;line-height: 88upx;padding-left: 30upx;background: #fff;border-radius: 10upx;margin-top: 20upx;color: #333;}
.active {	background: #169af3;color: #fff;}

/*音频播放器样式*/
.audoimg{ width: 100%; }
.audoimg image{ width: 100%; height: 600rpx; }
/deep/.uni-slider-handle-wrapper{
    background: black !important;
}
/deep/.uni-slider-thumb{
    background: black !important;
}
.play{ background-color:rgba(255,255,255,0.5);width: 100%; height: 124rpx;position: absolute; bottom:0%;  }
.play-left text{ margin-top: 1px; color: black;  font-size: 13px; line-height: 120rpx;  position: absolute; left: 13%;    }
.play-end text{ margin-top: 1px; color: black;  font-size: 13px; line-height: 120rpx; right: 8%;  position: absolute;      }
.slider{  width: 366rpx; position: relative; margin-top: 42rpx;  color: black; float: left;}
.musions{  width: 26px; height: 26px; margin: 17px 4px 0 5px; float: left; }
.play image{   width: 26px; height: 26px; margin: 34rpx 4px 0 5px;float: left;  }
.play-left{width: 170rpx;height: 116upx;    float: left;  border-radius: 38px;  }
.play-right{ width: 66%;  float: left; height: 58px; position: relative; }
.audo-video {  width: 100%;   position: relative; top: -18px; }
.slider-box {  display: flex; align-items: center;justify-content: center;font-size: 26upx; color: #999; }
button {  display: inline-block; width: 100upx; background-color: #fff;  font-size: 24upx;    color: #000;   padding: 0; }
.hidden {position: fixed;  z-index: -1;   width: 1upx;height: 1upx;}

.needbuytip{position: absolute;top:0;left: 0;z-index: 99;background-color: #fff;opacity: 0.96;width: 100%;height: 450rpx;color: red;line-height:450rpx;font-size: 34rpx;text-align: center;}
.tobuy{width: 710rpx;margin: 0 auto;color: #fff;height: 80rpx;line-height: 80rpx;border-radius: 80rpx;overflow: hidden;text-align: center;font-size: 32rpx;}
.headimg{width: 100rpx;height: 100rpx;background-color: #f1f1f1;border-radius: 50% 50%;margin-right: 10rpx;overflow: hidden;}
</style>