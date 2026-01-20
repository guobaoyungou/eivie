<template>
<view class="container">
	<view v-for="(item, index) in datalist" :key="index" class="item">
		<view class="product-item2" @tap="goto" :data-url="'index?id='+item.mpid">
			<view class="data" :style="{'background':item.info.bgpic ? 'url('+item.info.bgpic+')' : '#fff','background-size':'100%'}">
				<view class="data_info" :style="field_list2?'':'border:0;margin:0'">
					<img class="data_head" :src="item.info.headimg" alt=""/>
					<view>
						<view class="data_name">{{item.info.realname}}</view>
						<view class="data_text" v-if="item.info.touxian1">{{item.info.touxian1}}</view>
						<view class="data_text" v-if="item.info.touxian2">{{item.info.touxian2}}</view>
						<view class="data_text" v-if="item.info.touxian3">{{item.info.touxian3}}</view>
					</view>
				</view>

				<view class="data_list" v-for="(item2,index2) in field_list2">
					<img v-if="index2 == 'tel'" src="../static/images/tel.png" alt=""/>
					<img v-else-if="index2 == 'weixin'" :src="pre_url+'/static/img/weixin.png'" alt=""/>
					<img v-else-if="index2 == 'address'" src="../static/images/address.png" alt=""/>
					<img v-else :src="item2.icon" alt=""/>
					{{item.info[index2]}}
				</view>
			</view>
		</view>
		<view class="foot">
			<text class="flex1">收藏时间：{{dateFormat(item.createtime,'Y-m-d H:i')}}</text>
			<text class="btn" @tap="favoritedel" :data-id="item.id">取消收藏</text>
		</view>
	</view>
	
	<nomore v-if="nomore"></nomore>
	<nodata v-if="nodata"></nodata>
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
			pre_url:app.globalData.pre_url,
			
			field_list:[],
			field_list2:[],
      datalist: [],
      pagenum: 1,
			nodata:false,
      nomore: false,
      mingpiantext:'名片',
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.pagenum = 1;
		this.datalist = [];
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata();
    }
  },
  methods: {
    getdata: function () {
      var that = this;
      var pagenum = that.pagenum;
			that.loading = true;
			that.nomore = false;
			that.nodata = false;
      app.post('ApiMingpian/favorite', {pagenum: pagenum}, function (res) {
				that.loading = false;
        that.mingpiantext = that.t('名片');
        uni.setNavigationBarTitle({
        	title: '我的'+that.mingpiantext+'夹'
        });
        var data = res.data;
        if (pagenum == 1) {
					that.datalist = data;
          that.field_list  = res.field_list;
          that.field_list2 = res.field_list2;
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
    favoritedel: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      app.post('ApiMingpian/delfavorite', {id: id}, function (data) {
        app.success(data.msg);
        setTimeout(function () {
			that.getdata();
			that.onLoad();
        }, 1000);
      });
    }
  }
};
</script>
<style>
.item{ width:94%;margin:0 3%;padding:0 20rpx;background:#fff;margin-top:20rpx;border-radius:20rpx}
.product-item2 {display:flex;padding: 20rpx 0;border-bottom:1px solid #E6E6E6}
.product-item2 .product-pic {width: 180rpx;height: 180rpx; background: #ffffff;overflow:hidden}
.product-item2 .product-pic image{width: 100%;height:100%;}
.product-item2 .product-info {flex:1;padding: 5rpx 10rpx;}
.product-item2 .product-info .p1 {word-break: break-all;text-overflow: ellipsis;overflow: hidden;display: block;height: 80rpx;line-height: 40rpx;font-size: 30rpx;color:#111111}
.product-item2 .product-info .p2{font-size: 32rpx;height:40rpx;line-height: 40rpx}
.product-item2 .product-info .p2 .t2 {margin-left: 10rpx;font-size: 26rpx;color: #888;text-decoration: line-through;}
.product-item2 .product-info .p3{font-size: 24rpx;height:50rpx;line-height:50rpx;overflow:hidden}
.product-item2 .product-info .p3 .t1{color:#aaa;font-size:24rpx}
.product-item2 .product-info .p3 .t2{color:#888;font-size:24rpx;}
.foot{ display:flex;align-items:center;width:100%;height:100rpx;line-height:100rpx;color:#999999;font-size:24rpx;}
.foot .btn{ padding:2rpx 10rpx;height:50rpx;line-height:50rpx;color:#FF4C4C}


.data{
	position: relative;
	background: #FFFFFF;
	padding: 30rpx;
	border-radius: 12rpx;
	width:100%;
	box-shadow:2px 0px 10px rgba(0,0,0,0.5);
}
.data_info{
	display: flex;
	align-items: center;
	padding: 0 0 45rpx 0;
	border-bottom: 1rpx solid #eee;
	margin-bottom: 20rpx;
}
.data_head{
	width: 172rpx;
	height: 172rpx;
	border-radius: 50%;
	margin-right: 40rpx;
}
.data_name{
	font-size: 36rpx;
	font-family: Source Han Sans CN;
	font-weight: bold;
	color: #121212;
	padding-bottom: 10rpx;
}
.data_text{
	font-size: 24rpx;
	font-family: Alibaba PuHuiTi;
	font-weight: 400;
	color: #545556;
	padding-top: 15rpx;
}
.data_list{
	padding: 9rpx 0;
	font-size: 28rpx;
	font-family: Alibaba PuHuiTi;
	font-weight: 400;
	color: #8B9198;
	display: flex;
}
.data_list img{
	height: 30rpx;
	width: 30rpx;
	margin: 5rpx 30rpx 0 0;
	flex-shrink:0;
}
.data_tag{
	position: absolute;
	top: 50rpx;
	right: 50rpx;
	height: 60rpx;
	width: 60rpx;
}
</style>