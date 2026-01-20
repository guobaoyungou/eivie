<template>
<view class="container">
	<block v-if="isload">
		<view class="ordertop" :style="'background:url(' + pre_url + '/static/img/ordertop_refund.jpg);background-size:100%'">
			<view class="f1" v-if="detail.status==0">
				<view class="t1">待验货</view>
			</view>
			<view class="f1" v-if="detail.status==1">
				<view class="t1">已验货</view>
			</view>
		</view>
		<view class="btitle flex-y-center" v-if="detail.bid>0 && detail.binfo" @tap="goto" :data-url="'/pagesExt/business/index?id=' + detail.bid">
			<image :src="detail.binfo.logo" style="width:36rpx;height:36rpx;"></image>
			<view class="flex1" decode="true" space="true" style="padding-left:16rpx">{{detail.binfo.name}}</view>
		</view>

		<view class="product">
			<view v-for="(item, idx) in prolist" :key="idx" class="content">
				<view @tap="goto" :data-url="'/pages/shop/product?id=' + item.proid">
					<image :src="item.pic"></image>
				</view>
				<view class="detail">
					<text class="t1">{{item.name}}</text>
					<text class="t2">{{item.ggname}}</text>
          <view style="display: flex;justify-content: space-between;">
            <text class="t2">手工费：{{item.hand_fee}}</text>
            <text class="t2">回寄数量：{{item.hand_num}}</text>
          </view>
          <block v-if="detail.status==1">
            <view style="display: flex;justify-content: space-between;">
              <text class="t2">合格数量：{{item.ispassnum}}</text>
              <text class="t2">不合格数量：{{item.nopassnum}}</text>
            </view>
            <view class="item" v-if="item.fbpics">
            	<text class="t2">图片:</text>
            	<view id="content_picpreview" class="flex" style="flex-wrap:wrap;padding-top:20rpx">
            		<view v-for="(item, index) in item.fbpics" :key="index" class="layui-imgbox">
            			<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
            		</view>
            	</view>
            </view>
            <text v-if="item.fbremark" class="t2">备注：{{item.fbremark}}</text>
          </block>
				</view>
			</view>
		</view>

		<view class="orderinfo">
			<view class="item">
				<text class="t1">回寄单号</text>
				<text class="t2" user-select="true" selectable="true">{{detail.hand_ordernum}}</text>
			</view>
			<view class="item">
				<text class="t1">订单号</text>
				<text class="t2" user-select="true" selectable="true" @tap="goto" :data-url="'detail?id='+detail.orderid">{{detail.ordernum}}</text>
			</view>
			<view class="item">
				<text class="t1">回寄时间</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>
      <block v-if="detail.express_com || detail.express_no">
         <view class="item">
          <text class="t1">快递公司</text>
          <text class="t2">{{detail.express_com}}</text>
        </view>
        <view class="item">
          <text class="t1">快递单号</text>
          <text class="t2">{{detail.express_no}}</text>
        </view>
        <view class="item" v-if="detail.express_com || detail.express_no">
          <view class="t1"></view>
          <view class="t2" style="overflow: hidden;">
            <view class="btn2" style="float: right;" @tap="logistics" :data-express_com="detail.express_com" :data-express_no="detail.express_no" :data-express_content="detail.express_content">查看物流</view></view>
        </view>
      </block>
      <block v-else>
        <block v-if="detail.issign==1">
          <block v-for="(item,index) in detail.express_content">
            <view class="item">
              <text class="t1">快递公司</text>
              <text class="t2">{{item.express_com}}</text>
            </view>
            <view class="item">
              <text class="t1">快递单号</text>
              <text class="t2">{{item.express_no}}</text>
            </view>
            <view class="item" v-if="item.express_com || item.express_no">
              <view class="t1"></view>
              <view class="t2" style="overflow: hidden;">
                <view class="btn2" style="float: right;" @tap="logistics" :data-express_com="item.express_com" :data-express_no="item.express_no" :data-express_content="item.express_content">查看物流</view></view>
            </view>
          </block>
        </block>
        <block v-else>
          <block v-for="(item,index) in detail.express_content">
            <view class="form-item" style="padding: 10rpx 20rpx;">
              <view class="label" style="display: flex;justify-content: space-between;">
                <text>快递公司：</text>
              </view>
              <view class="flex">
                <picker @change="expresschange" :value="item.express_index" :range="detail.expressdata" :data-index="index" style="font-size:28rpx;border: 1px #eee solid;padding:10rpx;height:70rpx;border-radius:4px;flex:1">
                  <view class="picker">{{item.express_com}}</view>
                </picker>
              </view>
            </view>
            <view class="form-item"  style="padding: 10rpx 20rpx;">
              <text class="label">快递单号：</text>
              <view class="flex">
                <input type="text" @input="setexpressno" :data-index="index" :value="item.express_no" placeholder="请输入快递单号"  style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
                <view class="setup-view" @tap="saoyisao" :data-index="index">
                	<image :src="`${pre_url}/static/img/admin/saoyisao.png`"></image>
                </view>
              </view>
            </view>
            <view class="item" v-if="item.express_com || item.express_no">
              <view class="t1"></view>
              <view class="t2" style="overflow: hidden;">
                <view class="btn2" style="float: right;" @tap="logistics" :data-express_com="item.express_com" :data-express_no="item.express_no" :data-express_content="item.express_content">查看物流</view>
              </view>
            </view>
          </block>
          <view v-if="detail.express_content" @tap="changeexpress" style="width: 500rpx;margin: 10rpx auto;border-radius: 8rpx;line-height: 70rpx;border: 2rpx solid #EEE;text-align: center;">修改快递</view>
        </block>
      </block>
      <!-- <view class="item" v-if="detail.express_pic">
      	<text class="t1">快递图片</text>
      	<view id="content_picpreview" class="flex" style="flex-wrap:wrap;padding-top:20rpx">
      		<view v-for="(item, index) in detail.express_pic" :key="index" class="layui-imgbox">
      			<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
      		</view>
      	</view>
      </view>
			<view class="item" v-if="detail.hand_pics">
				<text class="t1">图片</text>
				<view id="content_picpreview" class="flex" style="flex-wrap:wrap;padding-top:20rpx">
					<view v-for="(item, index) in detail.hand_pics" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
					</view>
				</view>
			</view> -->
		</view>
    <view class="orderinfo">
      <view class="item">
      	<text class="t1">签收状态</text>
      	<text class="t2 red" v-if="detail.issign==0">未签收</text>
      	<text class="t2" v-if="detail.issign==1">已签收</text>
      </view>
      <view v-if="detail.signtime" class="item">
      	<text class="t1">签收时间</text>
      	<text class="t2">{{detail.signtime}}</text>
      </view>
      <view class="item">
      	<text class="t1">状态</text>
      	<text class="t2 red" v-if="detail.status==0">待验货</text>
      	<text class="t2" v-if="detail.status==1 && detail.issend==0">已验货,待返款</text>
        <text class="t2" v-if="detail.status==1 && detail.issend==1">已验货,已返款</text>
      </view>
      <view v-if="detail.checktime" class="item">
      	<text class="t1">验货时间</text>
      	<text class="t2">{{detail.checktime}}</text>
      </view>
      <block v-if="detail.status==1">
        <view class="item" >
        	<text class="t1">总计返款</text>
        	<text class="t2 red">￥{{detail.totalmoney}}</text>
        </view>
        <view class="item" >
        	<text class="t1">返款状态</text>
        	<text class="t2 red" v-if="detail.issend==0">待返款</text>
          <text class="t2" v-else>已返款</text>
        </view>
        <view v-if="detail.sendtime" class="item" >
        	<text class="t1">返款时间</text>
        	<text class="t2">{{detail.sendtime}}</text>
        </view>
      </block>
    	<view class="item" v-if="detail.hand_checkremark">
    		<text class="t1">审核备注</text>
    		<text class="t2">{{detail.hand_checkremark}}</text>
    	</view>
    </view>
    
		<view style="width:100%;height:120rpx"></view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
  <wxxieyi></wxxieyi>
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
      ispost:0,
			pre_url:app.globalData.pre_url,

      prodata: '',
      detail: "",
      prolist: "",
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
    this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiOrder/handDetail', {id: that.opt.id}, function (res) {
				that.loading = false;
        if(res.status == 1) {
          var detail   = res.detail;
          that.detail  = detail;
          that.prolist = res.prolist;
          that.loaded();
        } else {
        		if (res.msg) {
        			app.alert(res.msg, function() {
        				if (res.url) app.goto(res.url);
        			});
        		} else if (res.url) {
        			app.goto(res.url);
        		} else {
        			app.alert('您无查看权限');
        		}
        }
			});
		},
		openLocation:function(e){
			var latitude = parseFloat(e.currentTarget.dataset.latitude);
			var longitude = parseFloat(e.currentTarget.dataset.longitude);
			var address = e.currentTarget.dataset.address;
			uni.openLocation({
			 latitude:latitude,
			 longitude:longitude,
			 name:address,
			 scale: 13
			})
		},
    logistics:function(e){
    	var express_com = e.currentTarget.dataset.express_com
    	var express_no = e.currentTarget.dataset.express_no
    	var express_content = e.currentTarget.dataset.express_content
    	app.goto('/pagesExt/order/logistics?express_com=' + express_com + '&express_no=' + express_no);
    },
    expresschange:function(e){
      var that = this;
      var index = e.currentTarget.dataset.index;
      var express_content = that.detail.express_content;
      var expressdata     = that.detail.expressdata;
    	express_content[index]['express_com'] = expressdata[e.detail.value];
      that.detail.express_content = express_content;
    },
    setexpressno:function(e){
      var that = this;
      var index = e.currentTarget.dataset.index;
      var express_content = that.detail.express_content;
      express_content[index]['express_no'] = e.detail.value;
      that.detail.express_content = express_content;
    },
    changeexpress:function(e){
      var that = this;
      if(that.ispost) return;
      that.ispost = 1;
      var index = e.currentTarget.dataset.index;
      app.confirm('确定修改快递信息吗？',function(e){
        app.showLoading('提交中');
        app.post('ApiOrder/handChangeexpress', {id:that.detail.id,express_content:that.detail.express_content}, function (res) {
          app.showLoading(false);
          if (res.status == 1) {
            app.success('修改成功');
            setTimeout(function(){
              that.getdata()
            },800)
          }else{
            app.alert(res.msg);
            that.ispost = 0;
          }
        });
      })
    },
    saoyisao: function (e) {
      var that = this;
      var index = e.currentTarget.dataset.index;
      var express_content = that.detail.express_content;
    	if(app.globalData.platform == 'h5'){
    		app.alert('请使用微信扫一扫功能扫码核销');return;
    	}else if(app.globalData.platform == 'mp'){
    		var jweixin = require('jweixin-module');
    		jweixin.ready(function () {   //需在用户可能点击分享按钮前就先调用
    			jweixin.scanQRCode({
    				needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
    				scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
    				success: function (res) {
    					var content = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
              content = content.split(',')[1];
              express_content[index]['express_no'] = content;
              that.detail.express_content = express_content;
    				},
    				fail:function(err){
    					app.error(err.errMsg);
    				}
    			});
    		});
    	}else{
    		uni.scanCode({
    			success: function (res) {
    				var content = res.result;
    				express_content[index]['express_no'] = content;
    				that.detail.express_content = express_content;
    			},
    			fail:function(err){
    				app.error(err.errMsg);
    			}
    		});
    	}
    },
  }
};
</script>
<style>
.ordertop{width:100%;height:220rpx;padding:50rpx 0 0 70rpx}
.ordertop .f1{color:#fff}
.ordertop .f1 .t1{font-size:32rpx;height:60rpx;line-height:60rpx}
.ordertop .f1 .t2{font-size:24rpx}

.address{ display:flex;width: 100%; padding: 20rpx 3%; background: #FFF;}
.address .img{width:40rpx}
.address image{width:40rpx; height:40rpx;}
.address .info{flex:1;display:flex;flex-direction:column;}
.address .info .t1{font-size:28rpx;font-weight:bold;color:#333}
.address .info .t2{font-size:24rpx;color:#999}

.product{width:96%;margin:0 2%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.product .content{display:flex;position:relative;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed;}
.product .content:last-child{ border-bottom: 0; }
.product .content image{ width: 140rpx; height: 140rpx;}
.product .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.product .content .detail .t1{font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.product .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.product .content .comment{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc702 solid; border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.product .content .comment2{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc7c2 solid; border-radius:10rpx;background:#fff; color: #ffc7c2;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.orderinfo{width:96%;margin:0 2%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;overflow:hidden}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;flex-shrink:0}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}
.orderinfo .item .grey{color:grey}

.bottom{ width: 100%; padding: 16rpx 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.btn3{position:absolute;top:60rpx;right:10rpx;font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.btn4{border: none;}
.btitle{ width:100%;height:100rpx;background:#fff;padding:0 20rpx;border-bottom:1px solid #f5f5f5}
.btitle .comment{border: 1px #ffc702 solid;border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.btitle .comment2{border: 1px #ffc7c0 solid;border-radius:10rpx;background:#fff; color: #ffc7c0;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}

.form-content{width:94%;margin:16rpx 3%;border-radius:10rpx;display:flex;flex-direction:column;background:#fff;overflow:hidden}
.form-item{ width:100%;padding: 32rpx 20rpx;}
.form-item .label{ width:100%;height:60rpx;line-height:60rpx}
.form-item .input-item{ width:100%;}
.form-item textarea{ width:100%;height:200rpx;border: 1px #eee solid;padding: 20rpx;}
.form-item input{ width:100%;border: 1px #f5f5f5 solid;padding: 10rpx;height:80rpx}
.form-item .mid{ height:80rpx;line-height:80rpx;padding:0 20rpx;}
.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin-top:50rpx;color: #fff;font-size: 30rpx;font-weight:bold}

.setup-view{position:relative;width: 64rpx;height:64rpx;}
.setup-view image{width: 64rpx;height: 64rpx;}
</style>
