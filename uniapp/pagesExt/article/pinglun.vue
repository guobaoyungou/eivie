<template>
<view class="container">
	<view class="box2">
		<view class="header">
			<!-- <view class="f1" @tap="goback"><image src="/static/img/arrowright.png" style="width:40rpx;height:40rpx;transform:rotateY(180deg)"/></view> -->
			<view class="f2" id="box2_title">{{title}}</view>
			<view class="f3" @tap="subpinglun">发表</view>
		</view>
		<textarea style="width:100%;height:50vh" placeholder="写评论..." id="editcontent" :value="content" @input="setcontent"></textarea>
		<view style="height:100rpx"></view>
		<view class="bottom notabbarbot">
			<view @tap="showface"><image :src="pre_url+'/static/img/emote.png'"></image></view>
		</view>
		<wxface v-if="faceshow" @selectface="selectface"></wxface>
	</view>
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

			pre_url: app.globalData.pre_url,
      title: '发表评论',
      type: 0,
      id: '',
      hfid: '',
      faceshow: false,
      content: ''
    }
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
    var that = this;
    that.type = that.opt.type;
		that.id = that.opt.id;
		that.hfid = that.opt.hfid;
    if(that.hfid){
      that.title = '回复评论'
    }
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiArticle/detail',{id:this.id},function(res){
				that.loading = false;
				uni.setNavigationBarTitle({
					title: res.detail.name
				});
				that.loaded();
			})
		},
    showface: function () {
      this.faceshow = !this.faceshow
    },
    selectface: function (face) {
      var content = this.content + face;
      this.faceshow = false;
      this.content = content;
    },
    setcontent: function (e) {
      this.content = e.detail.value;
    },
    subpinglun: function () {
      var that = this;
      var id = that.id;
      var type = that.type;
      var hfid = that.hfid;
      var content = that.content;
      if (content == '') {
        app.error('请输入评论内容');
        return;
      }
      app.showLoading('提交中');
      app.post("ApiArticle/subpinglun", {id: id,type: type,hfid: hfid,content: content}, function (res) {
        app.showLoading(false);
        if (res.status == 1) {
          app.success(res.msg);
          setTimeout(function () {
            app.goback(true);
          }, 1000);
        } else {
          app.error(res.msg);
        }
      });
    }
  }
};
</script>
<style>
/* m/dietitian/detail.wxss */
page{ background: #fff}

.box2{background:#fff;height:100vh;}
.box2 .header{width:100%;height:92rpx;line-height:92rpx;background:#fafafa;border-bottom:1px solid #cfcfcf;display:flex;text-align:center}
.box2 .header .f1{width:80rpx;font-size:44rpx}
.box2 .header .f2{flex:1;font-size:32rpx;color:#111}
.box2 .header .f3{width:100rpx;font-size:32rpx;color:#1b9af4}
.box2 textarea{width:100%;height:80vh;border:0;color:#333;padding:20rpx;font-size:32rpx}
.box2 .bottom{width:96%;max-width:750px;margin:0 auto;position:fixed;display:flex;align-items:center;bottom:0;left:0;right:0;height:100rpx;background:#fff;z-index:996;border-top:1px solid #f7f7f7;padding:0 2%;box-sizing:content-box}
.box2 .bottom image{width:60rpx;height:60rpx}

.plbox{width:100%;padding:40rpx 20rpx}
.plbox_title{font-size:28rpx;height:6rpx;line-height:6rpx;margin-bottom:20rpx}
.plbox_title .t1{color:#000;font-weight:bold}
.plbox_content .plcontent{vertical-align: middle;color:#111}
.plbox_content .plcontent image{ width:44rpx;height:44rpx;vertical-align: inherit;}
.plbox_content .item1{width:100%;margin-bottom:20rpx}
.plbox_content .item1 .f1{width:80rpx;}
.plbox_content .item1 .f1 image{width:60rpx;height:60rpx;border-radius:50%}
.plbox_content .item1 .f2{flex:1}
.plbox_content .item1 .f2 .t1{}
.plbox_content .item1 .f2 .t2{color:#000;margin:10rpx 0;line-height:60rpx;}
.plbox_content .item1 .f2 .t3{color:#999;font-size:20rpx}
.plbox_content .item1 .f2 .pzan image{width:32rpx;height:32rpx;margin-right:2px}
.plbox_content .item1 .f2 .phuifu{margin-left:6px;color:#507DAF}
.plbox_content .relist{width:100%;background:#f5f5f5;padding:4rpx 20rpx;margin-bottom:20rpx}
.plbox_content .relist .item2{font-size:24rpx;margin-bottom:10rpx}

.copyright{display:none}
</style>