<template>
	<view class="view-width">
		<block v-if="isload">
      <view class="head-class" :style="{background:'url('+taskset.bgpic+')',backgroundSize:'cover',backgroundRepeat:'no-repeat'}">
        <view class="navigation" :style="'top:' + menuButton +'px'">
          <image @tap="goback()" class="imgback" :src="pre_url+'/static/img/goback2.png'" ></image>
          <view style="width: 100%;text-align: center;color: #fff;font-weight: bold;"> 任务</view>
        </view>
        <view @tap="goto" data-url="log" class="log" :style="'background-color: rgba('+t('color1rgb')+',0.4);top:' + logtop +'px'">
          历史任务
        </view>
        <view class="taskprogress-wc" :style="'top:' + taskprogresstop +'px'">
          <view class="taskprogress">
            任务进度 <progress :percent="taskprogress" show-info stroke-width="5" border-radius="5" style="width: 560rpx;"/>
          </view>
        </view>
        <view v-if="taskset.givescore>0 || (taskset.givecouponid>0 && taskset.givecouponname)" class="taskprogress-wc" style="bottom:20rpx;" :style="'top:' + endgivetop +'px'">
          <view style="width: 700rpx;margin: 0 auto;text-align: center;font-size: 26rpx;">
            当日任务全部完成奖励：
            <text v-if="taskset.givescore>0" > +{{taskset.givescore}}{{t('积分')}}</text>
            <text v-if="taskset.givecouponid>0 && taskset.givecouponname"  :style="taskset.givescore>0 ?'margin-left: 10rpx;':''"> +{{taskset.givecouponname}}{{t('优惠券')}}</text>
          </view>
        </view>
      </view>
      <view class="task-wc">
        <view style="display: flex;justify-content: space-between">
          <view class="task-title" style="font-size: 30rpx;padding-left: 10rpx;" :style="'border-left: 6rpx solid '+t('color1')">
            任务列表
          </view>
          <view @tap="changemaskrule" style="display: flex;align-items: center;">
            <image class="imgback" :src="pre_url+'/static/img/wh.png'" ></image>
            <view :style="'color:'+t('color1')">规则说明</view>
          </view>
        </view>
      </view>

      <view class="tasklist">
        <view>
        	<block v-for="(item, index) in datalist" :key="index"> 
            <view class="task-item">
              <view style="width: 70rpx;height: 70rpx;border-radius: 50% 50%;overflow:hidden">
                <image :src="item.pic" style="width: 100%;height: 100%;"></image>
              </view>
              <view style="width: 400rpx;">
                <view>{{item.name}}</view>
                <view style="font-size: 26rpx;color: #8E8E8E;">
                  {{item.shortcontent}}
                </view>
                <view style="font-size: 26rpx;">
                  <text v-if="item.givescore>0"  :style="'color:'+t('color1')"> +{{item.givescore}}{{t('积分')}}</text>
                  <text v-if="item.givecouponid>0 && item.givecouponname"  :style="item.givescore>0 ?'margin-left: 10rpx;color:'+t('color1'):'color:'+t('color1')"> +{{item.givecouponname}}{{t('优惠券')}}</text>
                </view>
              </view>
              <view v-if="item.takestatus == 0" @tap="takeTask" :data-id="item.id" :data-index="index" class="opt" :style="'background-color:'+t('color1')">
                领任务
              </view>
              <view v-if="item.takestatus == 1" @tap="goto" :data-url="'detail?type=1&id='+item.id" class="opt" :style="'background-color: rgba('+t('color1rgb')+',0.75)'">
                去完成
              </view>
              <view v-if="item.takestatus == 2" @tap="goto" :data-url="'detail?type=1&id='+item.id" class="opt" :style="'background-color: rgba('+t('color1rgb')+',0.45)'">
                已完成
              </view>
            </view>
          </block>
          <nodata v-if="nodata"></nodata>
        </view>
        <nomore v-if="nomore"></nomore>
      </view>
      <view style="width: 100%;height: 20rpx;"></view>
		</block>

    <view id="mask-rule" v-if="showmaskrule">
    	<view class="box-rule">
    		<view class="h2">规则说明</view>
    		<view id="close-rule" :style="'background-image:url('+pre_url+'/static/img/close.png);background-size:100%'" @tap="changemaskrule"></view>
    		<scroll-view scroll-y="true" class="content">
    			<view style="padding: 0 20rpx;">
            <parse :content="taskset.content" @navigate="navigate"></parse>
    			</view>
    		</scroll-view>
    	</view>
    </view>
		<popmsg ref="popmsg"></popmsg>
		<loading v-if="loading"></loading>
	</view>
</template>

<script>
	const app = getApp();
	export default {
		data(){
			return{
				opt:{},
				loading:false,
				isload:false,
        nodata:false,
        nomore:false,
				pre_url:app.globalData.pre_url,
        pagenum:1,
        menuButton:24,
        logtop:64,
        taskprogresstop:124,
        endgivetop:154,
        
        showmaskrule:false,
        taskset:'',
        taskprogress:0,
        datalist:[]
			}
		},
    onReady() {
      // #ifdef MP-WEIXIN || MP-ALIPAY || MP-BAIDU || MP-TOUTIAO || MP-QQ
        this.menuButton = uni.getMenuButtonBoundingClientRect().top;
        this.logtop = this.menuButton+40;
        this.taskprogresstop = this.logtop +60;
        this.endgivetop = this.taskprogresstop+30;
      // #endif
    },
		onLoad(opt){
			this.opt = app.getopts(opt);
			
			let sysinfo = uni.getSystemInfoSync();
      if (sysinfo && sysinfo.statusBarHeight) {
      	this.statusBarHeight = sysinfo.statusBarHeight;
      }
      // #ifdef H5
      this.statusBarHeight = 20;
      // #endif
      
      this.getdata();
		},
    onPullDownRefresh: function () {
    	this.getdata();
    },
		onReachBottom: function () {
		  if (!this.nodata && !this.nomore) {
		    this.pagenum = this.pagenum + 1;
		    this.getdata(true);
		  }
		},
		methods:{
			// 页面信息
			getdata: function (loadmore) {
				if(!loadmore){
					this.pagenum = 1;
					this.datalist = [];
				}
			  var that = this;
			  var pagenum = that.pagenum;
				that.nodata = false;
				that.nomore = false;
				that.loading = true;
				app.get('ApiTask/index', {pagenum:that.pagenum}, function (res) {
					that.loading = false;
          if(res.status == 1){
            var data = res.data;
            that.taskprogress = res.taskprogress;
            if (pagenum == 1) {
              that.taskset = res.taskset;
            	that.datalist = data;
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
            that.loaded();
          }else{
            app.alert(res.msg)
          }
				});
			},
      changemaskrule: function () {
        this.showmaskrule = !this.showmaskrule
      },
      takeTask:function(e){
        var that = this;
        var id = e.currentTarget.dataset.id;
        var index = e.currentTarget.dataset.index;
        
        app.confirm('确定领取此任务？',function(){
          app.post('ApiTask/taketask', {id:id}, function (res) {
            that.loading = false;
            if(res.status == 1){
              app.success(res.msg)
              that.datalist[index]['takestatus'] = 1;
            }else{
              app.alert(res.msg)
            }
          });
        })
      }
		}
	}
</script>

<style>
  .view-width{width: 100%;height: auto;position: relative;}
  .head-class{height: 400rpx;color: #fff;;position: relative;}
  .navigation {
  	position: absolute;
  	padding: 10rpx 30rpx;
  	width: 100%;
  	box-sizing: border-box;
  	display: flex;
  	z-index: 10;
  }
  .imgback{width: 40rpx;height: 40rpx;}
  .log{position: absolute;right: 0;padding:0 10rpx 0 30rpx;line-height:70rpx;border-radius: 70rpx 0 0 70rpx;}
  .taskprogress-wc{width: 100%;position: absolute;}
  .task-wc{width: 700rpx;margin: 30rpx auto;font-weight: bold;}
  .taskprogress{width: 700rpx;margin: 0 auto;display: flex;justify-content: space-between;}
  .tasklist{width: 700rpx;margin: 0 auto;background-color: #fff;border-radius: 4rpx;padding: 20rpx;}
  .task-item{padding: 20rpx 0;display: flex;justify-content: space-between;border-bottom: 2rpx solid #f1f1f1;align-items: center;}
  .task-item:last-child{border-bottom: 0;}
  .opt{width: 140rpx;height: 60rpx;line-height: 60rpx;border-radius: 60rpx 60rpx;text-align: center;color:#fff}
  
  #mask-rule,#mask {position: fixed;left: 0;top: 0;z-index: 999;width: 100%;height: 100%;background-color: rgba(0, 0, 0, 0.85);}
  #mask-rule .box-rule {position: relative;margin: 30% auto;width: 95%;height: 800rpx;border-radius: 8rpx;background-color: #fff;}
  #mask-rule .content{height:670rpx;}
  #mask-rule .box-rule .star {position: absolute;left: 50%;top: -100rpx;margin-left: -130rpx;width: 259rpx;height:87rpx;}
  #mask-rule .box-rule .h2 {width: 100%;text-align: center;line-height: 34rpx;font-size: 34rpx;font-weight: normal;padding: 20rpx 0;}
  #mask-rule #close-rule {position: absolute;right: 34rpx;top: 24rpx;width: 36rpx;height: 36rpx;}
</style>
