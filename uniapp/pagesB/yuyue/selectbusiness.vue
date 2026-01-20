<template>
<view class="container">
	<block v-if="isload">
		<view v-for="(item, index) in datalist" :key="index" class="content " @tap.stop="setdefault" :data-id="item.id" :data-name="item.name">
      <view class="flex" style="justify-content: space-between;">
        <view class="f1" >
          <view class="headimg">
            <image v-if="item.logo" :src="item.logo"  />
            <image v-else :src="pre_url+'/static/img/touxiang.png'"/>
          </view>
          <view class="text1">	
            <text class="t1">{{item.name}} </text>
          </view>
        </view>
        <view class="yuyue" >选择</view>
			</view>
      <view >
      	{{item.address}}
      </view>
		</view>
		<nodata v-if="nodata"></nodata>
		<view style="height:140rpx"></view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();
export default {
  data() {
    return {
			opt:{},
			pre_url:app.globalData.pre_url,
			loading:false,
      isload: false,
			menuindex:-1,

      datalist: [],
      prodata:'',
      setstatus:false
    }
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.prodata = opt.prodata;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			that.nodata = false;
			app.get('ApiYuyue/selectbusiness', {prodata:that.prodata}, function (res) {
				that.loading = false;
				var datalist = res.data;
				that.datalist = datalist;
				if (datalist.length == 0) {
					that.nodata = true;
				}
				that.loaded();
			});
		},
    setdefault: function (e) {
      var that = this;
      var fromPage = that.opt.fromPage;
      var id   = e.currentTarget.dataset.id;
      var name = e.currentTarget.dataset.name;
      if(that.setstatus){
        return
      }
      that.setstatus = true;
      setTimeout(function() {
          let pages = getCurrentPages();
          if (pages.length >= 2) {
              //let curPage = pages[pages.length - 1]; // 当前页面
              let prePage = pages[pages.length - 2]; // 上一页面
              prePage.$vm.fwbid  = id;
              prePage.$vm.fwbname= name;
              uni.navigateBack();
          }
      }, 600);

    },
  }
};
</script>
<style>
.topsearch{width:94%;margin:16rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

.content{width:94%;margin:20rpx 3%;background:#fff;border-radius:5px;padding:20rpx 40rpx;}
.content .f1{display:flex;align-items:center}
.content .f1 image{ width: 80rpx; height: 80rpx;}
.content .f1 .t1{color:#2B2B2B;font-weight:bold;font-size:30rpx;margin-left:10rpx;}
.content .f1 .t2{color:#999999;font-size:28rpx; background: #E8E8F7;color:#7A83EC; margin-left: 10rpx; padding:3rpx 20rpx; font-size: 20rpx; border-radius: 18rpx;}
.content .f1 .t3{ margin-left:10rpx;display: block; height: 40rpx;line-height: 40rpx}
.content .f2{color:#2b2b2b;font-size:26rpx;line-height:42rpx;padding-bottom:20rpx;}
.content .f3{height:96rpx;display:flex;align-items:center}
.content .radio{flex-shrink:0;width: 36rpx;height: 36rpx;background: #FFFFFF;border: 3rpx solid #BFBFBF;border-radius: 50%;}
.content .radio .radio-img{width:100%;height:100%}
.content .mrtxt{color:#2B2B2B;font-size:26rpx;margin-left:10rpx}


.text2{ margin-left: 10rpx; color:#999999; font-size: 20rpx;}
.yuyue{ background: #7A83EC; height: 40rpx; line-height: 40rpx; padding: 0 10rpx; color:#fff; border-radius:28rpx; width: 80rpx; font-size: 20rpx; text-align: center; margin-top: 20rpx;}
.yuyue.hui{  background: #bbb; }
.container .btn-add{width:90%;max-width:700px;margin:0 auto;height:96rpx;line-height:96rpx;text-align:center;color:#fff;font-size:30rpx;font-weight:bold;border-radius:40rpx;position: fixed;left:0px;right:0;bottom:20rpx;}
</style>