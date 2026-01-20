<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit">
		<view class="form-content">
			<view class="form-item1">
				<view class="label">商品信息</view>
				<view class="product flex">
					<view class="img"><image :src="og.propic"></image></view>
					<view class="info flex1">
						<view class="f1">{{og.proname}}</view>
						<view class="f2">{{og.ggname}}</view>
						<view class="f3">￥{{og.sell_price}}</view>
					</view>
				</view>
			</view>
			<view class="form-item2 flex flex-y-center">
				<view class="label">您的打分</view>
				<view class="i-rate" @touchmove="handleTouchMove">
					<input type="text" name="score" :value="score" class="i-rate-hide-input"></input>
					<view v-for="(item, index) in 5" :key="index" class="i-rate-star" :class="( index < score ? 'i-rate-current':'' )" :data-index="index" @tap="handleClick">
							<image v-if="index < score" :src="pre_url+'/static/img/star2native.png'"></image>
							<image v-else :src="pre_url+'/static/img/star.png'"></image>
					</view>
					<view class="i-rate-text"></view>
				</view>
			</view>
			<view class="form-item3 flex-col">
				<view class="label">您的评价</view>
				<textarea placeholder="输入您的评价内容" placeholder-style="color:#ccc;" name="content" :value="comment.content" style="height:200rpx" :disabled="comment.id?true:false"></textarea>
			</view>
			
			<view class="form-item4 flex-col">
				<view class="label">上传图片</view>
				<view id="content_picpreview" class="flex" style="flex-wrap:wrap;padding-top:20rpx">
					<view v-for="(item, index) in content_pic" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="content_pic" v-if="!comment.id"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						<!-- <view class="layui-imgbox-repeat" bindtap="xuanzhuan" data-index="{{index}}" data-field="content_pic" wx:if="{{!comment.id}}"><text class="fa fa-repeat"></text></view> -->
					</view>
					<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="content_pic" v-if="!comment.id && content_pic.length<5"></view>
				</view>
			</view>
		</view>
		<button class="btn" form-type="submit" :style="{background:t('color1')}" v-if="!comment.id">确定</button>
		</form>
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
			
			pre_url:app.globalData.pre_url,
			og:{},
			comment:{},
      score: 0,
      content_pic: [],
      tempFilePaths: ""
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
			var orderid = that.opt.orderid;
			that.loading = true;
			app.get('ApiCollage/comment', {orderid: orderid}, function (res) {
				that.loading = false;
				that.og = res.og;
				if (res.comment){
					that.comment = res.comment;
					that.score = res.comment.score;
					var content_pic = res.comment.content_pic;
					that.content_pic = content_pic.split(',');
				}
				that.loaded();
			});
		},
    formSubmit: function (e) {
      var that = this;
      var orderid = that.opt.orderid;
      var score = e.detail.value.score;
      var content = e.detail.value.content;
      var content_pic = that.content_pic;
      if (score == 0) {
        app.error('请打分');
        return;
      }
      if (content == '') {
        app.error('请填写评价内容');
        return;
      }
			app.showLoading('提交中');
      app.post('ApiCollage/comment', {orderid: orderid,content: content,content_pic: content_pic.join(','),score: score}, function (data) {
				app.showLoading(false);
        app.success(data.msg);
        setTimeout(function () {
          app.goback(true);
        }, 2000);
      });
    },
    handleClick: function (e) {
      if (this.comment && this.comment.id) return;
      var index = e.currentTarget.dataset.index;
      this.score = index + 1;
    },
    handleTouchMove: function (e) {
      if (this.comment && this.comment.id) return;
      var clientWidth = uni.getSystemInfoSync().windowWidth;
      if (!e.changedTouches[0]) return;
      var movePageX = e.changedTouches[0].pageX;
      var space = movePageX - 150 / 750 * clientWidth;
      if (space <= 0) return;
      var starwidth = 60 / 750 * clientWidth;
      var setIndex = Math.ceil(space / starwidth);
      setIndex = setIndex > 5 ? 5 : setIndex;
      this.score = setIndex;
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
			},1)
		},
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			pics.splice(index,1)
		},
  }
};
</script>
<style>
.form-content{width:94%;margin:10rpx 3%;border-radius:10rpx;display:flex;flex-direction:column;background:#fff;overflow:hidden}
.form-item1{ width:100%;background: #fff; padding: 8rpx 20rpx;}
.form-item1 .label{ width:100%;height:60rpx;line-height:60rpx}
.product{ width: 100%; background: #fff; }
.product .info{padding-left:20rpx;}
.product .info .f2{color: #a4a4a4; font-size:24rpx}
.product .info .f3{color: #ff0d51; font-size:28rpx}
.product image{ width:140rpx;height:140rpx}

.form-item2{width:100%;background: #fff; padding: 8rpx 20rpx;margin-top:1px}
.form-item2 .label{ width:150rpx;height:60rpx;line-height:60rpx}

.form-item3{width:100%;background: #fff; padding: 8rpx 20rpx;margin-top:1px}
.form-item3 .label{ width:100%;height:60rpx;line-height:60rpx}
.form-item3 textarea{width: 100%;border: 1px #dedede solid; border-radius: 10rpx; padding: 10rpx;height: 120rpx;}


.form-item4{width:100%;background: #fff; padding: 20rpx 20rpx;margin-top:1px}
.form-item4 .label{ width:150rpx;}
/*.form-item4 image{ width: 100rpx; height: 100rpx;background:#eee;margin-right:6rpx}
.form-item4 .imgbox{height:100rpx}*/


.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin-top:50rpx;color: #fff;font-size: 30rpx;font-weight:bold}

.i-rate{margin:0;padding:0;display:inline-block;vertical-align:middle;}
.i-rate-hide-input{display:none}
.i-rate-star{display:inline-block;color:#e9e9e9;padding:0 10rpx}
.i-rate-star image{width:50rpx;height:50rpx}
.i-rate-current{color:#f5a623}
.i-rate-text{display:inline-block;vertical-align:middle;margin-left:6px;font-size:14px}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
</style>