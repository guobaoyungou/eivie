<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待审核','已通过','已驳回','已过期']" :itemst="['all','0','1','-1','-2']" :st="st" :showstatus="showstatus" :ismoney="1" :isfixed="true" @changetab="changetab"></dd-tab>
		<view class="content">
			<view v-for="(item, index) in datalist" :key="index" class="item">
				<view class="f1" style="width: 100%;">
            <view style="display: flex;justify-content: space-between;">
              <view @tap="goto" :data-url="'/pagesExt/business/index?id=' + item.bid" class="t1" style="margin-bottom: 10rpx;width:calc(100% - 110rpx;) ">
                商户：{{item.business.name}}
              </view>
              <view  style="width: 110rpx;">
              		<text v-if="item.status==0"  style="color: #ff8758;">待审核</text>
              		<text v-if="item.status==1"  style="color: green;">已通过</text>
              		<text v-if="item.status==-1"  style="color: #bbb;">已驳回</text>
                  <text v-if="item.status==-2"  style="color: #bbb;">已过期</text>
              </view>
            </view>
						
            <view class="t2">联系人姓名：{{item.linkman}}</view>
            <view class="t2">联系人电话：{{item.linktel}}</view>
            <view class="t2">申请原因：{{item.reason}}</view>
            <block v-if="item.pics">
              <view class="con previewImgContent" style="width:100%;padding:24rpx 0;display:flex;flex-wrap: wrap;">
                <view v-for="(item2,index2) in item.pics" :key="index2" class="dp-form-imgbox">
                  <view class="dp-form-imgbox-img" style="margin-bottom: 10rpx;">
                    <image class="image" :src="item2" @click="previewImage" :data-url="item2" mode="aspectFit" />
                  </view>
                </view>
              </view>
            </block>
            <view v-if="item.premonthinfo" class="t2">{{item.premonthinfo}}</view>
						<view class="t2">提交时间：{{item.createtime}}</view>
            <view v-if="item.checktime" class="t2">审核时间：{{item.checktime}}</view>
            <view v-if="item.expiredtime" class="t2">失效时间：{{item.expiredtime}}</view>
            <view v-if="item.check_reason" class="t2" style="color:red">审核原因：{{item.check_reason}}</view>
            <view v-if="item.expiredremark" class="t2" style="color:red">{{item.expiredremark}}</view>
				</view>
				
			</view>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
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
      pagenum: 1,
      nodata:false,
      nomore: false,
			
      st: 'all',
      datalist: [],
      showstatus:[1,1,1,1,1],
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
      app.post('ApiBusiness/expertlist', {st: st,pagenum: pagenum}, function (res) {
				that.loading = false;
        if(res.status == 1){
          var data = res.datalist;
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
        }else{
          app.alert(res.msg)
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
    }
  }
};
</script>
<style>
.container{ width:100%;margin-top:90rpx;display:flex;flex-direction:column}
.content{ width:94%;margin:0 3% 20rpx 3%;}
.content .item{width:100%;background:#fff;margin:20rpx 0;padding:20rpx 20rpx;border-radius:8px;display:flex;align-items:center}
.content .item:last-child{border:0}
.content .item .f1{display:flex;flex-direction:column}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
.content .item .f1 .t2{color:#666666;max-width: 530rpx;}
.content .item .f1 .t3{color:#666666}
.content .item .f2{ flex:1;font-size:36rpx;text-align:right}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}
.btn1{height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;padding: 0 15rpx;float: right;font-size: 14px;margin-left: 10rpx}
.data-empty{background:#fff}

.dp-form-item{width: 100%;padding:10rpx 0 10rpx;display:flex;align-items: center;}
.dp-form-imgbox{width: 200rpx;margin-right:16rpx;font-size:24rpx;position: relative;}
.dp-form-imgbox-img{display: block;;overflow:hidden;}
.dp-form-imgbox-img>.image{width: 200rpx;height: 200rpx;margin-right: 2%;margin-bottom: 10rpx;border-radius: 8rpx;}
</style>