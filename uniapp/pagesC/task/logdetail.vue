<template>
	<view class="view-width">
		<block v-if="isload">
      <view class="tasklist">
        <view>
        	<block v-for="(item, index) in datalist" :key="index"> 
            <view class="task-item">
              <view style="width: 60rpx;height: 60rpx;border-radius: 50% 50%;overflow:hidden">
                <image :src="item.pic" style="width: 100%;height: 100%;"></image>
              </view>
              <view style="width: 400rpx;">
                <view>{{item.name}}</view>
                <view style="font-size: 26rpx;color: #8E8E8E;">
                  {{item.shortcontent}}
                </view>
                <view style="font-size: 26rpx;">
                  <text v-if="item.givescore>0" :style="'color:'+t('color1')"> +{{item.givescore}}{{t('积分')}}</text>
                  <text v-if="item.givecouponid>0 && item.givecouponname" :style="item.givescore>0 ?'margin-left: 10rpx;color:'+t('color1'):'color:'+t('color1')"> +{{item.givecouponname}}{{t('优惠券')}}</text>
                </view>
              </view>
              <view v-if="item.status == 0" @tap="goto" :data-url="'detail?type=2&detailid='+item.id" class="opt" :style="'background-color: rgba('+t('color1rgb')+',0.75)'">
                去完成
              </view>
              <view v-if="item.status == 1" @tap="goto" :data-url="'detail?type=2&detailid='+item.id" class="opt" :style="'background-color: rgba('+t('color1rgb')+',0.45)'">
                已完成
              </view>
              <view v-if="item.status == -2" @tap="goto" :data-url="'detail?type=2&detailid='+item.id" class="opt" :style="'background-color: rgba('+t('color1rgb')+',0.45)'">
                已失效
              </view>
              <view v-if="item.status == -1" @tap="goto" :data-url="'detail?type=2&detailid='+item.id" class="opt" :style="'background-color: rgba('+t('color1rgb')+',0.45)'">
                已关闭
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
            <parse :content="set.content" @navigate="navigate"></parse>
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
        menuButton:0,
        statusBarHeight: 20,
        
        showmaskrule:false,
        set:'',
        taskprogress:0,
        datalist:[],
        logid:0,
			}
		},
		onLoad(opt){
			this.opt = app.getopts(opt);
      this.logid = this.opt.logid || 0;
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
				app.get('ApiTask/logdetail', {logid:that.logid,pagenum:that.pagenum}, function (res) {
					that.loading = false;
          if(res.status == 1){
            var data = res.data;
            if (pagenum == 1) {
              that.set = res.set;
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
  .log{position: absolute;top:150rpx;right: 0;text-decoration:underline;padding: 0 10rpx;}
  .taskprogress{width: 680rpx;margin: 0 auto;display: flex;justify-content: space-between;}
  .tasklist{width: 680rpx;margin: 0 auto;background-color: #fff;border-radius: 12rpx;padding: 20rpx;}
  .task-item{padding: 20rpx 0;display: flex;justify-content: space-between;border-bottom: 2rpx solid #f1f1f1;align-items: center;}
  .task-item:last-child{border-bottom: 0;}
  .opt{width: 140rpx;height: 60rpx;line-height: 60rpx;border-radius: 60rpx 60rpx;text-align: center;color:#fff}
  
  #mask-rule,#mask {position: fixed;left: 0;top: 0;z-index: 999;width: 100%;height: 100%;background-color: rgba(0, 0, 0, 0.85);}
  #mask-rule .box-rule {position: relative;margin: 30% auto;width: 95%;height: 750rpx;border-radius: 8rpx;background-color: #fff;}
  #mask-rule .content{height:670rpx;}
  #mask-rule .box-rule .star {position: absolute;left: 50%;top: -100rpx;margin-left: -130rpx;width: 259rpx;height:87rpx;}
  #mask-rule .box-rule .h2 {width: 100%;text-align: center;line-height: 34rpx;font-size: 34rpx;font-weight: normal;padding: 20rpx 0;}
  #mask-rule #close-rule {position: absolute;right: 34rpx;top: 20rpx;width: 40rpx;height: 40rpx;}
</style>
