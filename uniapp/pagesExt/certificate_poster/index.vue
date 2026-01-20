<template>
<view class="container">
	<block v-if="isload">
	<!-- 头部富文本 -->
	<view v-if="set.header_text">
		<parse :content="set.header_text" />
	</view>
	<form report-submit="true" @submit="subconfirm" style="width:100%;margin: 10rpx;">
		<view class="title" v-if="set && set.query_type == 'id_card'">请输入身份证号查询{{certificate_text}}</view>
		<view class="title" v-if="set && set.query_type == 'tel'">请输入手机号查询{{certificate_text}}</view>
		
		<view class="inputdiv">
			<input type="text" name="tel" v-if="set && set.query_type == 'id_card'" :value="tel" placeholder-style="color:#666;" placeholder="请输入您的身份证号"/>
			<input type="text" name="tel2" v-if="set && set.query_type == 'tel'" :value="tel2" placeholder-style="color:#666;" placeholder="请输入您的手机号"/>
		</view>
		<button class="btn" form-type="submit">查询</button>
	</form>
	
	<!-- 尾部富文本 -->
	<view v-if="set.footer_text">
		<parse :content="set.footer_text" />
	</view>
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
			platform:app.globalData.platform,
			pre_url:app.globalData.pre_url,
			tel:'',//身份证号
			tel2:'',//手机号
			set:{},
			certificate_text:'成绩'
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt && this.opt.tel) this.tel = this.opt.tel;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiCertificatePoster/index', {}, function (res) {
				if(res.set && res.set.certificate_text){
					that.set = res.set;
					uni.setNavigationBarTitle({
						title: '查询'+res.set.certificate_text
					});
					that.certificate_text = res.set.certificate_text;
				}else{
					uni.setNavigationBarTitle({
						title: '查询成绩'
					});
				}
				that.loading = false;
				that.loaded();
			});
		},
    subconfirm: function (e) {
      var that = this;
      var tel = e.detail.value.tel ? e.detail.value.tel : '';
      var tel2 = e.detail.value.tel2 ? e.detail.value.tel2 : '';
			that.loading = true;
      app.post('ApiCertificatePoster/index', {tel: tel,tel2: tel2}, function (res) {
				that.loading = false;
        if (res.status == 0) {
          app.error(res.msg);
          return;
        }
        if (res.status == 1) {
          app.success(res.msg);
          setTimeout(function () {
            app.goto('record?tel='+tel+'&tel2='+tel2);
          }, 1000);
        }
				that.loaded();
      });
    },
  }
}
</script>
<style>
.container{display:flex;flex-direction:column;}
.container .title{display:flex;justify-content:center;width:100%;color:#555;font-size:40rpx;text-align:center;height:100rpx;line-height:100rpx;margin-top:60rpx}
.container .inputdiv{display:flex;width:90%;margin:0 auto;margin-top:40rpx;margin-bottom:40rpx;position:relative}
.container .inputdiv input{background:#fff;width:100%;height:120rpx;line-height:120rpx;padding:0 40rpx;font-size:40rpx;border:1px solid #f5f5f5;border-radius:20rpx}
.container .btn{ height: 88rpx;line-height: 88rpx;background: #FC4343;width:90%;margin:0 auto;border-radius:8rpx;margin-top:60rpx;color: #fff;font-size: 36rpx;}
.container .f0{width:100%;margin-top:40rpx;height:60rpx;line-height:60rpx;color:#FC4343;font-size:30rpx;display:flex;align-items:center;justify-content:center}
.container .scanicon{width:80rpx;height:80rpx;position:absolute;top:20rpx;right:20rpx;z-index:9}
.container .scanicon image{width:100%;height:100%}
.qd_guize{width:100%;margin:30rpx 0 20rpx 0;}
.qd_guize .gztitle{width:100%;text-align:center;font-size:32rpx;color:#656565;font-weight:bold;height:100rpx;line-height:100rpx}
.guize_txt{box-sizing: border-box;padding:0 30rpx;line-height:42rpx;}
</style>