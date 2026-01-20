<template>
<view>
	<view v-if="isload" class="container" id="datalist">
			<view>
				<view class="info-item" style="line-height: 40rpx;">
					<view class="t1" style="width: 200rpx;">云音响管理</view>
          <view style="display: flex;flex-wrap: wrap;justify-content: space-between;">
            <view class="btn2" @tap="goto" :data-url="'edit'">添加唱单宝音响</view>
            <view class="btn2" v-if="customs.sound_mqlinks" @tap="goto" :data-url="'editmqlinks'">添加劢领云喇叭</view>
            <view class="btn2" v-if="customs.sound_yuzhi"@tap="goto" :data-url="'edityuzhi'">添加语智云喇叭</view>
          </view>
				</view>

        <view class="info-item2" v-for="(item,index) in datalist" :key="index" @tap="gotoedit" :data-id="item.id" :data-device_brand="item.device_brand">
          <view>
            <view class="t1">名称：{{item.name}}</view>
            <view class="t2">终端号：{{item.device_sn}}</view>
            <view class="t2">厂商：{{item.device_brand_name}}</view>
            <view class="t2">添加时间：{{item.createtime}}</view>
            <view class="t2">状态：<text v-if="item.status == 1" style="color: green;">显示</text><text v-else style="color: red;">隐藏</text></view>
          </view>
          <image class="t3"  :src="pre_url+'/static/img/arrowright.png'" />
        </view>
			</view>
	</view>

	<nomore v-if="nomore"></nomore>
	<nodata v-if="nodata"></nodata>
</view>
</template>

<script>
var app = getApp();

export default {
  data() {
    return {
      st: 'all',
      datalist: [],
      pagenum: 1,
      isload: false,
      nomore: false,
      nodata: false,
      pre_url:app.globalData.pre_url,
      
      customs:{
        sound_mqlinks:false,
        sound_yuzhi:false
      }
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
  },
  onShow:function(){
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
  methods: {
    changetab: function (st) {
      var that = this;
      that.st = st;
      that.getdata();
    },
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
      app.post('ApiAdminSound/index', {pagenum: pagenum}, function (res) {
        that.loading = false;
        that.customs = res.customs;
        var data = res.datalist;
        if (pagenum == 1){
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
    gotoedit(e){
      var that = this;
      var id = e.currentTarget.dataset.id;
      var device_brand = e.currentTarget.dataset.device_brand;
      if(device_brand=='changdanbao'){
        app.goto('edit?id='+id);
      }else if(device_brand=='mqlinks'){
        app.goto('editmqlinks?id='+id);
      }else if(device_brand=='yuzhi'){
        app.goto('edityuzhi?id='+id);
      }
    }
  }
};
</script>
<style>
.container{ width:100%;}

.info-item{ display:flex;align-items:center;width: 100%; background: #fff;padding:0 3%;  border-bottom: 1px #f3f3f3 solid;line-height:96rpx}
.info-item:last-child{border:none}
.info-item .t1{ width: 300rpx;font-weight:bold;height:auto;line-height:48rpx;}
.info-item .t2{ color:#444444;text-align:right;flex:1;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;line-height:48rpx;}
.info-item .t3{ width: 26rpx;height:26rpx;margin-left:20rpx}

.btn1{margin-left:20rpx;width:120rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin: 10rpx 0;margin-left:20rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;display: inline-block;padding: 0 10rpx;}

.info-item .t2 image{ width: 100rpx; height: 100rpx;}
.info-item .t1 .d1{margin-left:5rpx}
.info-item .t1 .d2{margin-left:20rpx}

.info-item2{width: 96%;margin:0 auto; display:flex;align-items:center;background: #fff;padding:20rpx;line-height:48rpx;border-radius: 4rpx;margin-top: 20rpx;justify-content: space-between;}
.info-item2 .t1{font-weight:bold;height:auto;}
.info-item2 .t2{ color:#444444;}
.info-item2 .t2 image{ width: 100rpx; height: 100rpx;}
.info-item2 .t3{ width: 26rpx;height:26rpx;margin-left:20rpx}
</style>