<template>
<view class="container">
	<block v-if="isload">
    <dd-tab :itemdata="['全部','待签署','已完成','已失效']" :itemst="['all','1','2','-1']" :st="st" :showstatus="showstatus" :ismoney="1" :isfixed="true" @changetab="changetab"></dd-tab>
		<view class="content">
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view>
            <view >
              <view >合同名称：{{item.FlowName}}</view>
            </view>
						<view >
              <view>类型：{{item.typename}}</view>
            </view>
            <view v-if="item.type == 'member_uplevel'">
              <view>等级：{{item.levelname}}</view>
            </view>
            <view v-if="item.wxurl">
              <view style="word-wrap: break-word;line-height: 40rpx;" >微信小程序链接：{{item.wxurl}}</view>
              <view @tap="fuzhi" :data-content="item.wxurl" style="width: 130rpx;color: #fff;background-color: red;text-align:center;border-radius: 8rpx 8rpx;margin-left: 20rpx;">复制</view>
            </view>
            <view v-if="item.h5url">
              <view style="word-wrap: break-word;;line-height: 40rpx;">手机H5链接：{{item.h5url}}</view>
              <view @tap="fuzhi" :data-content="item.h5url" style="width: 130rpx;color: #fff;background-color: red;text-align:center;border-radius: 8rpx 8rpx;margin-left: 20rpx;">复制</view>
            </view>
            
            <view style="display: flex;">
              <view>状态：</view>
              <view v-if="item.status == 0" style="color: #f30;">待发起</view>
              <view v-if="item.status == 1" style="display: flex;align-items: center;">
                <view style="color: green;">待签署</view>
                <view v-if="item.cancelFlow" @tap="popupCancelflowOpen" :data-id="item.id" style="width: 100rpx;color: #fff;background-color: red;text-align:center;border-radius: 8rpx 8rpx;margin-left: 20rpx;">撤销</view>
              </view>
              <view v-if="item.status == 2" style="color: #999;">已完成</view>
              <view v-if="item.status == -1 || item.status == -2" style="color: #bbb;">已失效</view>
            </view>
            <view v-if="(item.status == -1 || item.status == -2) && item.reason">
              <view>{{item.reason}}</view>
            </view>
            <view >
              <view >时间：{{item.createtime}}</view>
            </view>
				</view>
			</view>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
    
    <uni-popup id="popupCancelflow" ref="popupCancelflow" type="dialog">
      <view class="uni-popup-dialog">
        <view class="uni-dialog-title">
          <text class="uni-dialog-title-text">撤销签署合同</text>
        </view>
        <view class="uni-dialog-content">
          <view style="padding: 0 20rpx;width: 100%;line-height: 60rpx;">
            <view style="margin-bottom: 20rpx;display: flex;">
              撤销理由：<input @input="popupInput" data-field='reason' class="input" type="text" :value="reason" placeholder="请输入撤销理由" placeholder-style="color:#999;font-size:32rpx;height:60rpx;line-height: 60rpx;" style="width: 460rpx;height:60rpx;line-height: 60rpx;" ></input>
            </view>
          </view>
        </view>
        <view class="uni-dialog-button-group">
          <view class="uni-dialog-button" @tap="popupCancelflowClose">
            <text class="uni-dialog-button-text">取消</text>
          </view>
          <view class="uni-dialog-button" :style="{color:t('color1')}" @tap="popupCancelflowConfirm">
            <text class="uni-dialog-button-text">确定</text>
          </view>
        </view>
      </view>
    </uni-popup>
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
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			
			canwithdraw:false,
			textset:{},
      st: 'all',
      datalist: [],
      pagenum: 1,
			nodata:false,
      nomore: false,
      showstatus:[1,1,1,1],
      
      reason:''
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 'all';
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata(true);
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata(true);
    }
  },
  methods: {
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
      var st = that.st;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiMy/tencentqianlog', {st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1) {

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
      });
    },
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
    popupCancelflowConfirm:function(e){
      var that = this;
      
      app.confirm('确定撤销此签署合同吗？',function(){
        that.loading = true;
        var data = {
          id: that.id,
          reason:that.reason
        }
        app.post('ApiMy/tencentqianCancelFlow', data, function (res) {
          that.loading = false;
          if(res.status == 1){
            app.success(res.msg);
            setTimeout(function(){
              that.popupCancelflowClose();
              that.getdata();
            },900)
            
          }else{
            app.alert(res.msg);
          }
        });
      })
    },
    popupCancelflowOpen:function(e){
      var that = this;
      that.id = e.currentTarget.dataset.id;
    	that.$refs.popupCancelflow.open();
    },
    popupCancelflowClose:function(){
      var that = this;
      that.id = 0;
      that.reason = '';
    	that.$refs.popupCancelflow.close();
    },
    popupInput(e){
      var that = this;
      var field  = e.currentTarget.dataset.field;
    	that[field]= e.detail.value;
    },
    fuzhi:function(e){
      var content = e.currentTarget.dataset.content
      if(!content){
        app.success('无内容可复制');
        return;
      }
      var that = this;
      uni.setClipboardData({
        data: content,
        success: function (res) {
          app.success('复制成功');
        },
        fail:function(res){
          app.error('复制失败')
        }
      });
    },
  }
};
</script>
<style>
.container{ width:100%;display:flex;flex-direction:column;margin-top: 90rpx;}
.content{ width:94%;margin:0 3% 20rpx 3%;}
.content .item{width:100%;background:#fff;margin:20rpx 0;padding:20rpx 20rpx;border-radius:8px;line-height: 60rpx;}
.content .item:last-child{border:0}

.uni-popup-dialog {width: 720rpx;border-radius: 10rpx;background-color: #fff;}
.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 30rpx;padding-bottom: 10rpx;}
.uni-dialog-title-text {font-size: 32rpx;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 15px 15px 15px;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}

.head-img{width: 90rpx;height: 90rpx;border-radius: 50%;overflow: hidden;}
.member-text-view{height: 90rpx;padding-left: 20rpx;display: flex;flex-direction: column;align-items: flex-start;justify-content: flex-start;}
.member-text-view .member-nickname{font-size: 28rpx;color: #333;font-weight: bold;}
.member-text-view .member-id{font-size: 24rpx;color: #999999;margin-top: 10rpx;}
</style>