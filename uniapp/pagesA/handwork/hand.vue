<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit" @reset="formReset" report-submit="true">
			<view class="form-content">
				<view class="form-item">
					<text class="label">回寄商品</text>
				</view>
				<view class="product">
					<view v-for="(item, index) in prolist" :key="index" class="content">
						<view @tap="goto" :data-url="'/pages/shop/product?id=' + item.proid">
							<image :src="item.pic"></image>
						</view>
						<view class="detail">
							<text class="t1">{{item.name}}</text>
							<text class="t2">{{item.ggname}}</text>
							<view class="t3"><text class="x1 flex1">￥{{item.sell_price}}</text>
							<!-- <text class="x2">×{{item.num}}</text> -->
							<view class="num-wrap">
								<view class="addnum">
									<view class="minus" @tap="gwcminus" :data-index="index" :data-ogid="item.id" :data-num="handNum[index].num"><image class="img" :src="pre_url+'/static/img/cart-minus.png'"/></view>
									<input class="input" type="number" :value="handNum[index].num" @blur="gwcinput" :data-index="index" :data-ogid="item.id" :data-max="item.num-item.hand_num" :data-num="handNum[index].num"></input>
									<view class="plus" @tap="gwcplus" :data-index="index" :data-ogid="item.id" :data-max="item.num-item.hand_num" :data-num="handNum[index].num"><image class="img" :src="pre_url+'/static/img/cart-plus.png'"/></view>
								</view>
								<view class="text-desc">回寄数量：最多可回寄{{item.canHandNum}}件</view>
							</view>
							</view>
						</view>
					</view>
				</view>
			</view>
			<view class="form-content">
        <view class="form-item">
          <text class="label">商家地址</text>
          <view class="input-item">
            {{binfo.name}} {{binfo.tel}}
          </view>
          <view class="input-item">
            {{binfo.province}}{{binfo.city}}{{binfo.district}}{{binfo.address}}
          </view>
        </view>
        <block v-for="(item,index) in express_content">
          <view class="form-item" style="padding: 10rpx 20rpx;">
            <view class="label" style="display: flex;justify-content: space-between;">
              <text>快递公司：</text>
              <view @tap="delexpress" :data-index="index" style="width: 100rpx;text-align: center;color: red;">删除</view>
            </view>
            <view class="flex">
              <picker @change="expresschange" :value="item.express_index" :range="expressdata" :data-index="index" style="font-size:28rpx;border: 1px #eee solid;padding:10rpx;height:70rpx;border-radius:4px;flex:1">
                <view class="picker">{{item.express_com}}</view>
              </picker>
            </view>
          </view>
          <view class="form-item"  style="padding: 10rpx 20rpx;">
            <text class="label">快递单号：</text>
            <view class="flex">
              <input type="text" placeholder="请输入快递单号" @input="setexpressno" :value="item.express_no" :data-index="index" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
              <view class="setup-view" @tap="saoyisao" :data-index="index">
              	<image :src="`${pre_url}/static/img/admin/saoyisao.png`"></image>
              </view>
            </view>
          </view>
        </block>
        <view @tap="addexpress" style="width: 500rpx;margin: 10rpx auto;border-radius: 8rpx;line-height: 70rpx;border: 2rpx solid #EEE;text-align: center;">+添加快递</view>
        <!-- <view v-if="showexpresspic" class="form-item flex-col">
        	<view class="label">快递图片</view>
        	<view id="express_picpreview" class="flex" style="flex-wrap:wrap;padding-top:20rpx">
        		<view v-for="(item, index) in express_pic" :key="index" class="layui-imgbox">
        			<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="express_pic"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
        			<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
        		</view>
        		<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="express_pic" v-if="express_pic.length<1"></view>
        	</view>
        </view>
				<view v-if="showcontentpic" class="form-item flex-col">
					<view class="label">图片(最多三张)</view>
					<view id="content_picpreview" class="flex" style="flex-wrap:wrap;padding-top:20rpx">
						<view v-for="(item, index) in content_pic" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="content_pic"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="content_pic" v-if="content_pic.length<3"></view>
					</view>
				</view> -->
			</view>
			<button class="btn" @tap="formSubmit" :style="{background:t('color1')}">确定</button>
			<view style="padding-top:30rpx"></view>
		</form>
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
			pre_url:app.globalData.pre_url,

      orderid: '',
      totalprice: 0,
			order:{},
			detail: {},
			handNum:[],
			prolist: [],
			binfo:[],

			type:'',
			tmplids:[],
			isloading:0,
			totalcanhandnum:0,
      
      expressdata:[],//快递列表
      express_content:[],//添加的多个快递数据
      express_index:0,
      express_no:'',
      express_pic:[],
      content_pic:[],
      
      showexpresspic:0,//0不展示 1展示
      showcontentpic:0,//0不展示 1展示
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.orderid = this.opt.orderid;
		this.pre_url = app.globalData.pre_url;
		this.type = this.opt.type;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.post('ApiOrder/handinit', {id: that.orderid}, function (res) {
				that.loading = false;
        if(res.status == 1) {
          that.detail = res.detail;
          var temp = [];
          that.prolist = res.prolist;
          that.order   = res.order;
          that.binfo   = res.binfo;
          var totalcanhandnum = 0;
          for(var i in that.prolist) {
          	temp.push({ogid:that.prolist[i].id,num:that.prolist[i].canHandNum})
          	totalcanhandnum += that.prolist[i].canHandNum;
          }
          that.totalcanhandnum = totalcanhandnum;
          that.handNum = temp;
          that.showexpresspic = res.showexpresspic;
          that.showcontentpic = res.showcontentpic;

          that.expressdata    = res.expressdata;
          if(that.expressdata && that.expressdata[0]){
            that.express_content = [{'express_index':0,'express_com':that.expressdata[0],'express_no':''}];
          }
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
    formSubmit: function () {
      var that = this;
			if(that.isloading) return;
      var handtotal = 0;
      var handNum = that.handNum;
      for(var i in handNum) {
      	handtotal += handNum[i].num;
      }
      if(handtotal <= 0) {
      	app.alert('请选择要回寄的商品');
      	return;
      }
      var express_content = that.express_content;
      var data = {
        orderid: that.orderid,
        handNum: that.handNum,
        express_content: express_content,
        express_pic : that.express_pic,
        content_pic : that.content_pic,
      }
			that.isloading = 1;
      app.confirm('确定回寄吗?', function () {
        app.showLoading('提交中');
        app.post('ApiOrder/hand', data, function (res) {
          app.showLoading(false);
          app.alert(res.msg);
          if (res.status == 1) {
            setTimeout(function(){
              app.goback()
            },800)
          }else{
            that.isloading = 0;
          }
        });
      },function(){
        that.isloading = 0;
      });
    },
		//加
		gwcplus: function (e) {
		  var index = parseInt(e.currentTarget.dataset.index);
		  var maxnum = parseInt(e.currentTarget.dataset.max);
		  var ogid = e.currentTarget.dataset.ogid;
		  var num = parseInt(e.currentTarget.dataset.num);
		  if (num >= maxnum) {
		    return;
		  }
			var handNum = this.handNum;
			handNum[index].num++;
			this.handNum = handNum
		},
		//减
		gwcminus: function (e) {
		  var index = parseInt(e.currentTarget.dataset.index);
		  var maxnum = parseInt(e.currentTarget.dataset.max);
		  var ogid = e.currentTarget.dataset.ogid;
		  var num = parseInt(e.currentTarget.dataset.num);
		  if (num == 0) return;
			var handNum = this.handNum;
			handNum[index].num--;
			this.handNum = handNum
		},
		//输入
		gwcinput: function (e) {
		  var index = parseInt(e.currentTarget.dataset.index);
		  var maxnum = parseInt(e.currentTarget.dataset.max);
		  var ogid = e.currentTarget.dataset.ogid;
		  var num = parseInt(e.currentTarget.dataset.num);
		  var newnum = parseInt(e.detail.value);
		  console.log(num + '--' + newnum);
		  if (num == newnum) return;

		  if (newnum > maxnum) {
		    app.error('请输入正确数量');
		    return;
		  }
			var handNum = this.handNum;
			handNum[index].num = newnum;
			this.handNum = handNum
		},
		inputExcompany: function (e) {
			this.express_com = e.detail.value;
		},
    inputExnum: function (e) {
    	this.express_no = e.detail.value;
    },
		uploadimg:function(e){
			var that = this;
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			if(!pics) pics = [];
			app.chooseImage(function(urls){
				for(var i=0;i<urls.length;i++){
					pics.push(urls[i]);
				}
        if(field == 'content_pic')  that.content_pic = pics;
        if(field == 'express_pic') that.express_pic = pics;
			},1)
		},
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			pics.splice(index,1)
      if(field == 'content_pic'){
      	var pics = that.content_pic
      	pics.splice(index,1);
      	that.content_pic = pics;
      }else if(field == 'express_pic'){
      	that.express_pic = [];
      }
		},
    expresschange:function(e){
      var that = this;
      var index = e.currentTarget.dataset.index;
      var express_content = that.express_content;
      var expressdata     = that.expressdata;
    	express_content[index]['express_com'] = expressdata[e.detail.value];
      that.express_content = express_content;
    },
    setexpressno:function(e){
      var that = this;
      var index = e.currentTarget.dataset.index;
      var express_content = that.express_content;
      express_content[index]['express_no'] = e.detail.value;
      that.express_content = express_content;
    },
    addexpress:function(){
      var that = this;
      var express_content = that.express_content;
      var expressdata = that.expressdata;
      if(!expressdata || !expressdata[0]){
        app.alert('系统暂未设置快递信息');
        return;
      }
      express_content.push({'express_index':0,'express_com':expressdata[0],'express_no':''});
      that.express_content = express_content;
    },
    delexpress:function(e){
      var that = this;
      var index = e.currentTarget.dataset.index;
      app.confirm('确定删除此条快递？',function(e){
        var express_content = that.express_content;
        express_content.splice(index,1);
        that.express_content = express_content;
      })
    },
    saoyisao: function (e) {
      var that = this;
      var index = e.currentTarget.dataset.index;
      var express_content = that.express_content;
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
              that.express_content = express_content;
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
    				that.express_content = express_content;
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
	.num-wrap {position: absolute;right: 0;bottom:24rpx;}
	.num-wrap .text-desc { margin-bottom: -60rpx; color: #999; font-size: 24rpx; text-align: right;}
	.addnum {position: absolute;right: 0;bottom:0rpx;font-size: 30rpx;color: #666;width:auto;display:flex;align-items:center}
	.addnum .plus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
	.addnum .minus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
	.product .addnum .img{width:24rpx;height:24rpx}
	.addnum .i {padding: 0 20rpx;color:#2B2B2B;font-weight:bold;font-size:24rpx}
	.addnum .input{flex:1;width:70rpx;border:0;text-align:center;color:#2B2B2B;font-size:24rpx}

	.form-item4{width:100%;background: #fff; padding: 20rpx 20rpx;margin-top:1px}
	.form-item4 .label{ width:150rpx;}
	.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
	.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
	.layui-imgbox-img>image{max-width:100%;}
	.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
	.uploadbtn{position:relative;height:200rpx;width:200rpx}

.product{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.product .content{display:flex;position:relative;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed; height: 196rpx;}
.product .content:last-child{ border-bottom: 0; }
.product .content image{ width: 140rpx; height: 140rpx;}
.product .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.product .content .detail .t1{font-size:26rpx;line-height:36rpx;height:72rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.product .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246; position: relative;}
.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.product .content .comment{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc702 solid; border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.product .content .comment2{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc7c2 solid; border-radius:10rpx;background:#fff; color: #ffc7c2;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

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
