<template>
<view class="container">
  <block v-if="isload">
    <form @submit="formSubmit" @reset="formReset" report-submit="true">
      
      <view v-if="ordergoods" class="form-content">
      	<view class="product">
      		<view v-for="(item, index) in ordergoods" :key="index" class="content">
            <view  @tap="chooseit" :data-index="index" class="radio" :style="item.issel ? 'border:0;background:'+t('color1') : ''">
              <image class="radio-img" mode="widthFix" :src="pre_url+'/static/img/checkd.png'" style="width:100%;height:100%"/>
            </view>
      			<view @tap="chooseit" :data-index="index" class="detail">
      				<text class="t1">{{item.name}}</text>
      				<text class="t2">{{item.cardNo}}</text>
      			</view>
      		</view>
        </view>
      </view>
      <button class="ref-btn" @tap="changeapply">确定</button>
    </form>
    <loading v-if="loading"></loading>
    <dp-tabbar :opt="opt"></dp-tabbar>
    <popmsg ref="popmsg"></popmsg>
  </block>
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
      
      orderid: '',
      ordergoods:'',
      showpic:false,
      pics:[],
      
      selogids:[]
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.orderid = this.opt.orderid;
		this.totalprice = this.opt.price;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.post('ApiHanglvfeike/changeinit', {orderid:that.orderid}, function (res) {
				that.loading = false;
        if(res.status == 1){
          that.ordergoods   = res.ordergoods;
          that.showpic = res.showpic;
          that.tmplids = res.tmplids;
          that.loaded();
        }else{
          app.alert(res.msg)
        }
			});
		},
    chooseit:function(e){
      var that = this;
      var index= e.currentTarget.dataset.index;
      var ordergoods = that.ordergoods;
      ordergoods[index]['issel'] = !ordergoods[index]['issel'];
      that.selogids = [];
      var len = ordergoods.length;
      for(var i=0;i<len;i++){
        if(ordergoods[i]['issel']){
          that.selogids.push(ordergoods[i]['id']);
        }
      }
    },
    changeapply:function(e){
      var that = this;
      var selogids = that.selogids;
      if(!selogids || selogids.length==0){
        app.alert('请选择要改签的旅客');
        return;
      }
      selogids = selogids.join(',');
      var url = 'changedetail?orderid='+that.orderid+'&ogids='+selogids;
			app.goto(url)
    }
  }
};
</script>
<style>
.form-item{ width:100%;background: #fff; padding: 32rpx 20rpx;}
.form-item .label{ width:100%;height:60rpx;line-height:60rpx}
.form-item .input-item{ width:100%;}
.form-item input{ width:100%;border: 1px #eee solid;padding: 10rpx;height:80rpx; background-color: #EEEEEE;}
.form-item input{ width:100%;border: 1px #eee solid;padding: 10rpx;height:80rpx}
.form-item .mid{ height:80rpx;line-height:80rpx;padding:0 20rpx;}
.ref-btn{ width: 90%; margin: 0 5%; height: 40px; line-height: 40px; text-align: center; color: #fff; font-size: 16px; border-radius: 8px;border: none; background: #ff8758; }
.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}

.form-content{width:96%;margin:16rpx 2%;border-radius:10rpx;display:flex;flex-direction:column;background:#fff;overflow:hidden}
.product{width:96%;margin:0 2%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.product .content{display:flex;position:relative;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed;align-items: center;}
.product .content:last-child{ border-bottom: 0; }
.product .content image{ width: 140rpx; height: 140rpx;}
.product .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.product .content .detail .t1{font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.product .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246; position: relative;}
.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.product .content .comment{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc702 solid; border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.product .content .comment2{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc7c2 solid; border-radius:10rpx;background:#fff; color: #ffc7c2;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx;}
.radio .radio-img{width:100%;height:100%}
</style>