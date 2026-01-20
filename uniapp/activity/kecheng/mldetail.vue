<template>
<view class="wrap">
	<block v-if="isload">
		<block v-if="detail.kctype==1">
			<view class="title">{{detail.name}}</view>
			<dp :pagecontent="pagecontent"></dp>
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
		</view>
		<view class="videobox" v-if="detail.kctype==3"> 
			<video  class="video" id="video" :autoplay="true" :src="detail.video_url"  :initial-time="detail.startTime" @pause="pause" @timeupdate="timeupdate" @ended="ended"></video>
		
				<view class="speed" v-if="showspeed">
						<view class="t1" v-if="showspeed==1" @tap="setspeed">{{speedtext}}</view>
						<view v-if="showspeed==2" class="f2">
							<block v-for="(item,index) in speedlist">
								<view  :class="'f22 '+(rate==item?'on':'')" @tap="bindButtonRate" :data-rate='item'><text class="t2">{{item}}</text><text>X</text></view>
							</block>
						</view>
				</view>
		</view>
		<view style=" height: 30rpx; width: 100%; background-color: #f5f5f5;"></view>
		<view class="content_box">
			<view class="title flex">
				<view class="t1">课程目录</view>
          <view class="t2" v-if="!detail.learnhg && detail.isdt==1 && detail.count>=detail.kccount && iskaoshi!=1" @tap.stop="goto" :data-url="'tiku?id=' + detail.kcid" data-opentype="redirect">去答题</view>
          <view class="t2" v-if="iskaoshi==1" @tap.stop="goto" :data-url="'recordlog?kcid=' + detail.kcid">答题记录</view>
			</view>
			<view class="mulubox-container" v-for="(item, index) in datalist" :key="index" >
				<view class="mulubox flex" >
					<view class="left_box" v-if="item.children && item.children.length > 0" @tap="todetail" :data-index='index' >
						<image :src="pre_url+'/static/img/arrowdown.png'" style="width:24rpx;height:24rpx;" :style="item.expanded && item.children && item.children.length > 0 ? '' :'transform: rotate(-90deg);'" />
					</view>
					<view class="left_box" v-else>
						<image v-if="item.kctype==1" :src="pre_url+'/static/img/tw_icon.png'" /> 
						<image v-if="item.kctype==2" :src="pre_url+'/static/img/mp3_icon.png'" />
						<image v-if="item.kctype==3" :src="pre_url+'/static/img/video_icon.png'" /> 
					</view>
					<view class="right_box flex">
						<view :class="'title_box '+ (item.id==detail.id?'on':'')"   @tap="todetail" :data-index='index' :data-mianfei='item.ismianfei' :data-url="'mldetail?id='+item.id+'&kcid='+detail.kcid" :data-opentype="item.kctype==1 ? 'redirect' : 'redirect'" :data-study_status='item.study_status' :data-record_status='item.record_status'>
							<view class="t1"> {{item.name}}</view>
							<view> 
								<text  v-if="item.kctype==1"  class="t2">图文课程 </text>
								<text v-if="item.kctype==2"  class="t2">音频课程 </text>
								<text v-if="item.kctype==3"  class="t2">视频课程 </text>
								<text  class="t2" v-if="item.video_duration>0"> 时长: {{item.duration}}</text>
							</view>
						</view>
						<view>
							<view class="jindu" v-if="item.jindu">{{item.jindu}}{{item.kctype==1 && item.jindu!='100'?'':'%'}}</view>
							<view class="skbtn" v-if="item.ismianfei && !item.jindu">试看</view>
							<block v-if="detail.learnhg==1 && item.study_status == 1">
								<!-- 需要上一章答题合格，且有题库可答 -->
								<view class="skbtn" style="width: 120rpx;height:50rpx ;line-height: 50rpx;padding: 0rpx 20rpx;" v-if="item.record_status==0 && item.tiku_status==1" @tap.stop="totiku" :data-index='index' :data-study_status='item.study_status' :data-record_status='item.record_status' :data-url="'tiku?id=' + detail.kcid + '&mlid='+item.id" data-opentype="redirect">去答题</view>
								<view class="skbtn" style="width: 140rpx;background-color: #fff;border: 2rpx solid #FC6D65;color: #FC6D65;height:50rpx ;line-height: 50rpx;padding: 0rpx 20rpx;" v-if="item.record_status==1" @tap.stop="goto" :data-url="'recordlog?kcid=' + detail.kcid + '&mlid='+item.id">答题记录</view>
							</block>
						</view>
					</view>		
				</view>
				<!-- 子章节 -->
				<view v-if="item.expanded && item.children && item.children.length > 0"  :class="['children-container', item.expanded && item.children && item.children.length > 0 ? 'expanded' : '']">
					<view class="child-item" v-for="(child, childIndex) in item.children" :key="child.id">
						<view class="mulubox flex">
							<view class="left_box">
								<view style="width: 40rpx; height: 1px;"></view> <!-- 缩进占位符 -->
								<image v-if="child.kctype==1" :src="pre_url+'/static/img/tw_icon.png'" style="margin-left: 40rpx;" /> 
								<image v-if="child.kctype==2" :src="pre_url+'/static/img/mp3_icon.png'" style="margin-left: 40rpx;" />
								<image v-if="child.kctype==3" :src="pre_url+'/static/img/video_icon.png'" style="margin-left: 40rpx;" /> 
							</view>
							<view class="right_box flex">
								<view :class="'title_box '+ (child.id==detail.id?'on':'')"   @tap="todetail" :data-index='childIndex' :data-parentindex="index" :data-mianfei='child.ismianfei' :data-url="'mldetail?id='+child.id+'&kcid='+detail.kcid" :data-opentype="child.kctype==1 ? 'redirect' : 'redirect'" :data-study_status='child.study_status' :data-record_status='child.record_status'>
									<view class="t1"> {{child.name}}</view>
									<view> 
										<text v-if="child.kctype==1" class="t2">图文课程 </text>
										<text v-if="child.kctype==2" class="t2">音频课程 </text>
										<text v-if="child.kctype==3" class="t2">视频课程 </text>
										<text class="t2" v-if="child.video_duration>0"> 时长: {{child.duration}}</text>
									</view>
								</view>
								<view>
									<view class="jindu" v-if="child.jindu">{{child.jindu}}{{child.kctype==1 && child.jindu!='100'?'':'%'}}</view>
									<view class="skbtn" v-if="child.ismianfei && !child.jindu">试看</view>
									<block v-if="detail.learnhg==1 && child.study_status == 1">
										<!-- 需要上一章答题合格，且有题库可答 -->
										<view class="skbtn" style="width: 120rpx;height:50rpx ;line-height: 50rpx;padding: 0rpx 20rpx;" v-if="child.record_status==0 && child.tiku_status==1" @tap.stop="totiku" :data-index='index' :data-study_status='child.study_status' :data-record_status='child.record_status' :data-url="'tiku?id=' + detail.kcid + '&mlid='+child.id" data-opentype="redirect">去答题</view>
										<view class="skbtn" style="width: 140rpx;background-color: #fff;border: 2rpx solid #FC6D65;color: #FC6D65;height:50rpx ;line-height: 50rpx;padding: 0rpx 20rpx;" v-if="child.record_status==1" @tap.stop="goto" :data-url="'recordlog?kcid=' + detail.kcid + '&mlid='+child.id">答题记录</view>
									</block>
								</view>
							</view>
						</view>
					</view>
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
			loading:false,
			isload: false,
			isplay: 0,
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
			iskaoshi:'',
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
			pre_url:app.globalData.pre_url,
		};
	},
	onLoad: function (opt) {
		this.opt = app.getopts(opt);
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
	methods: {
		getdata:function(){
			var that = this;
			var id = this.opt.id || 0;
			that.id = id;
			var kcid = this.opt.kcid || 0;
			that.loading = true;
			app.get('ApiKecheng/mldetail', {id: id,kcid:kcid}, function (res) {
				that.loading = false;
        if(res.status == 1){
          var detail = res.detail;
          that.detail = detail;
          that.iskaoshi = res.iskaoshi;
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
          var pagecontent = JSON.parse(detail.detail);
          that.pagecontent = pagecontent;
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
		todetail:function(e){
			var that = this;
		  var detail = that.detail;
		  var index = e.currentTarget.dataset.index;
			var item = that.datalist[index] || '';
			var parentindex = e.currentTarget.dataset.parentindex;
			if(parentindex === undefined || parentindex === ''){
				if(item && item.children && item.children.length > 0){
					that.datalist[index].expanded = !item.expanded;
					return;
				}
			}			
      //按顺序学习
		  if(detail.orderlearn){
		    var datalist = that.datalist;
		    if(index>=1){
		      var study_status = datalist[index-1].study_status;
		      if(study_status!=1){
		        app.alert('请按顺序从上往下学习');
		        return;
		      }
          if(detail.learnhg){
            var tiku_status      = datalist[index-1].tiku_status;
            var record_status = datalist[index-1].record_status;
            if(tiku_status == 1 && record_status!=1){
              app.alert('上一章答题合格才能学习下一章');
              return;
            }
          }
		    }
		  }else{
        //答题合格后学习下一章
        if(detail.learnhg && that.detail.learnkey != index){
          var datalist = that.datalist;
          var tiku_status   = datalist[that.detail.learnkey].tiku_status;
          var record_status = datalist[that.detail.learnkey].record_status;
          if(tiku_status == 1 && record_status!=1){
            app.alert('上一章答题合格才能学习下一章');
            return;
          }
        }
      }
      
			var url = e.currentTarget.dataset.url;
			var ismf = e.currentTarget.dataset.mianfei;
			var opentype = e.currentTarget.dataset.opentype;
			
			if(ismf==1 || that.detail.ispay==1 || that.detail.price==0){
				app.goto(url,opentype);
			}else{
				app.alert('请先购买课程',function(){
					app.goto('product?id='+that.opt.kcid);
				});
			}
		},
    totiku:function(e){
    	var that = this;
      var detail = that.detail;
      var index  = e.currentTarget.dataset.index;
      //按顺序学习
      if(detail.orderlearn){
        var datalist = that.datalist;
        if(index>=1){
          var prestudy_status = datalist[index-1].study_status;
          if(prestudy_status!=1){
            app.alert('请按顺序从上往下学习');
            return;
          }
          if(detail.learnhg){
            var tiku_status      = datalist[index-1].tiku_status;
            var prerecord_status = datalist[index-1].record_status;
            if(tiku_status == 1 && prerecord_status!=1){
              app.alert('上一章答题合格才能学习下一章');
              return;
            }
          }
        }
      }else{
        //答题合格后学习下一章
        if(detail.learnhg && that.detail.learnkey != index){
          var datalist = that.datalist;
          var tiku_status   = datalist[that.detail.learnkey].tiku_status;
          var record_status = datalist[that.detail.learnkey].record_status;
          if(tiku_status == 1 && record_status!=1){
            app.alert('上一章答题合格才能学习下一章');
            return;
          }
        }
      }
    	var url = e.currentTarget.dataset.url;
    	app.goto(url);
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
          if(that.detail.learnkey>=0){
            let targetItem;

            if((that.detail.childrenkey ?? -1) >= 0) {
              //子章节
              targetItem = datalist[that.detail.learnkey].children[that.detail.childrenkey];
            } else {
              targetItem = datalist[that.detail.learnkey];
            }
						
            targetItem.jindu = res.jindu
            if(res.jindu == '已学完' || res.jindu >= 100){
              if(targetItem.study_status == 0){
                targetItem.study_status = 1;
              }
            }
            that.datalist = datalist;
          }
        }
				//if(res.playJd>='100' &&  that.isauto && that.detail.isdt==1 && that.detail.count>=that.detail.kccount && that.iskaoshi!=1){
					//app.goto('tiku?id=' + that.detail.kcid);
				//}
				/*if(that.playJd>=100){
					  app.confirm('本节已学完，是否学习下一节', function (res) {
								app.post('ApiKecheng/nextsection', {id: id,kcid:that.detail.kcid}, function (res) {
											app.goto('/activity/kecheng/mldetail?id='+res.id+'&kcid='+that.detail.kcid);
								});
						},
						function (res) {
									that.stipshow=false;
									that.playshow=true;
									that.currentTime = 0;
									that.nowtime = '00:00';
									that.playJd = 0;
						})
				}*/
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
				app.confirm('试看时长已结束，前去开通继续观看', ()=>{
					app.goto('/activity/kecheng/product?id='+that.detail.kcid);
				}, ()=>{
					that.alert_status = 0;
				});
				return ;
			}
			
			//当前视频进度
			// console.log("视频播放到第" + currentTime + "秒")//查看正在播放时间，以秒为单位
			var jump_time = that.currentTime   //上次结束时间
			if (that.detail.isjinzhi == 1) {
				if ((that.detail.childrenkey ?? -1) >= 0) {
					//子章节
					var detail = that.datalist[that.detail.learnkey].children[that.detail.childrenkey]
				}else{
					var detail = that.datalist[that.detail.learnkey]
				}
        if (currentTime > jump_time && currentTime - jump_time > 2 && detail.jindu!='100' ) {
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
			if ((that.detail.childrenkey ?? -1) >= 0) {
				//子章节
				that.datalist[that.detail.learnkey].children[that.detail.childrenkey].jindu = that.playJd.toFixed(1)
			}else{
				that.datalist[that.detail.learnkey].jindu = that.playJd.toFixed(1)
			}
		},
		ended(){
			var that=this;
			if(that.detail.is_give_score){
				app.get('ApiKecheng/givescore', {kccid:that.detail.id}, function (res) {
					if(res.status){
						app.success(res.msg);
						setTimeout(function () {
							 if(that.iskaoshi!=1 && that.playJd==100 &&  that.isauto && that.detail.isdt==1  ){
								app.goto('tiku?id=' + that.detail.kcid);
								return;uni.createVideoContext('video')
							 
							 }
						}, 1000);
					}
				})
			}
			if(that.iskaoshi!=1 && that.playJd==100 &&  that.isauto && that.detail.isdt==1  ){
				app.goto('tiku?id=' + that.detail.kcid);
				return;uni.createVideoContext('video')
			
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
		}
		
	}
};
</script>
<style>
.wrap{ background: #fff;}
.wrap .title{ padding: 30rpx; font-size: 42rpx; color: #111111; font-weight: bold; justify-content: space-between;}
	
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
</style>