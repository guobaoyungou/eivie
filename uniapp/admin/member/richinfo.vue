<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<!-- <view class="form-box">
				<view class="form-item">
					<view class="f1">昵称<text style="color:red"> *</text></view>
					<view class="f2"><input type="text" name="nickname" :value="info.nickname" placeholder="请填写昵称" placeholder-style="color:#888"></input></view>
				</view>
			</view> -->
			<view class="form-box">
				<view class="form-item">
					<view class="f1">ID</view>
					<view class="f2">{{info.id}}</view>
				</view>
				<view class="form-item">
					<view class="f1">昵称</view>
					<view class="f2">{{info.nickname}}</view>
				</view>
			</view>
			<view class="form-box">
				<view class="form-item flex-col">
					<text>详细介绍</text>
					<view class="detailop"><view class="btn" @tap="detailAddtxt">+文本</view><view class="btn" @tap="detailAddpic">+图片</view><view class="btn" @tap="detailAddvideo">+视频</view></view>
					<view>
						<block v-for="(setData, index) in pagecontent" :key="index">
							<view class="detaildp">
							<view class="op"><view class="flex1"></view><view class="btn" @tap="detailMoveup" :data-index="index">上移</view><view class="btn" @tap="detailMovedown" :data-index="index">下移</view><view class="btn" @tap="detailMoveEdit" :data-index="index">编辑</view><view class="btn" @tap="detailMovedel" :data-index="index">删除</view></view>
							<view class="detailbox">
								<block v-if="setData.temp=='notice'">
									<dp-notice :params="setData.params" :data="setData.data"></dp-notice>
								</block>
								<block v-if="setData.temp=='banner'">
									<dp-banner :params="setData.params" :data="setData.data"></dp-banner> 
								</block>
								<block v-if="setData.temp=='search'">
									<dp-search :params="setData.params" :data="setData.data"></dp-search>
								</block>
								<block v-if="setData.temp=='text'">
									<dp-text :params="setData.params" :data="setData.data" :selectable="true"></dp-text>
								</block>
								<block v-if="setData.temp=='title'">
									<dp-title :params="setData.params" :data="setData.data"></dp-title>
								</block>
								<block v-if="setData.temp=='dhlist'">
									<dp-dhlist :params="setData.params" :data="setData.data"></dp-dhlist>
								</block>
								<block v-if="setData.temp=='line'">
									<dp-line :params="setData.params" :data="setData.data"></dp-line>
								</block>
								<block v-if="setData.temp=='blank'">
									<dp-blank :params="setData.params" :data="setData.data"></dp-blank>
								</block>
								<block v-if="setData.temp=='menu'">
									<dp-menu :params="setData.params" :data="setData.data"></dp-menu> 
								</block>
								<block v-if="setData.temp=='map'">
									<dp-map :params="setData.params" :data="setData.data"></dp-map> 
								</block>
								<block v-if="setData.temp=='cube'">
									<dp-cube :params="setData.params" :data="setData.data"></dp-cube> 
								</block>
								<block v-if="setData.temp=='picture'">
									<dp-picture :params="setData.params" :data="setData.data" :preview="true"></dp-picture> 
								</block>
								<block v-if="setData.temp=='pictures'"> 
									<dp-pictures :params="setData.params" :data="setData.data"></dp-pictures> 
								</block>
								<block v-if="setData.temp=='video'">
									<dp-video :params="setData.params" :data="setData.data"></dp-video> 
								</block>
								<block v-if="setData.temp=='shop'">
									<dp-shop :params="setData.params" :data="setData.data" :shopinfo="setData.shopinfo"></dp-shop> 
								</block>
								<block v-if="setData.temp=='product'">
									<dp-product :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-product> 
								</block>
								<block v-if="setData.temp=='collage'">
									<dp-collage :params="setData.params" :data="setData.data"></dp-collage> 
								</block>
								<block v-if="setData.temp=='kanjia'">
									<dp-kanjia :params="setData.params" :data="setData.data"></dp-kanjia> 
								</block>
								<block v-if="setData.temp=='seckill'">
									<dp-seckill :params="setData.params" :data="setData.data"></dp-seckill> 
								</block>
								<block v-if="setData.temp=='scoreshop'">
									<dp-scoreshop :params="setData.params" :data="setData.data"></dp-scoreshop> 
								</block>
								<block v-if="setData.temp=='coupon'">
									<dp-coupon :params="setData.params" :data="setData.data"></dp-coupon> 
								</block>
								<block v-if="setData.temp=='article'">
									<dp-article :params="setData.params" :data="setData.data"></dp-article> 
								</block>
								<block v-if="setData.temp=='business'">
									<dp-business :params="setData.params" :data="setData.data"></dp-business> 
								</block>
								<block v-if="setData.temp=='liveroom'">
									<dp-liveroom :params="setData.params" :data="setData.data"></dp-liveroom> 
								</block>
								<block v-if="setData.temp=='button'">
									<dp-button :params="setData.params" :data="setData.data"></dp-button> 
								</block>
								<block v-if="setData.temp=='hotspot'">
									<dp-hotspot :params="setData.params" :data="setData.data"></dp-hotspot> 
								</block>
								<block v-if="setData.temp=='cover'">
									<dp-cover :params="setData.params" :data="setData.data"></dp-cover> 
								</block>
								<block v-if="setData.temp=='richtext'">
									<dp-richtext :params="setData.params" :data="setData.data" :content="setData.content"></dp-richtext> 
								</block>
								<block v-if="setData.temp=='form'">
									<dp-form :params="setData.params" :data="setData.data" :content="setData.content"></dp-form> 
								</block>
								<block v-if="setData.temp=='userinfo'">
									<dp-userinfo :params="setData.params" :data="setData.data" :content="setData.content"></dp-userinfo> 
								</block>
							</view>
							</view>
						</block>
					</view>
				</view>
			</view>


			<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">提交</button>
			<view style="height:50rpx"></view>
		</form>

		<uni-popup id="dialogDetailtxt" ref="dialogDetailtxt" type="dialog">
			<view class="uni-popup-dialog">
				<view class="uni-dialog-title">
					<text class="uni-dialog-title-text">请输入文本内容</text>
				</view>
				<view class="uni-dialog-content">
					<textarea :value="edit_index != -1 ? pagecontent[edit_index].params.showcontent : ''" placeholder="请输入文本内容" @input="catcheDetailtxt"></textarea>
				</view>
				<view class="uni-dialog-button-group">
					<view class="uni-dialog-button" @click="dialogDetailtxtClose">
						<text class="uni-dialog-button-text">取消</text>
					</view>
					<view class="uni-dialog-button uni-border-left" @click="dialogDetailtxtConfirm">
						<text class="uni-dialog-button-text uni-button-color">确定</text>
					</view>
				</view>
				<view class="uni-popup-dialog__close" @click="dialogDetailtxtClose">
					<span class="uni-popup-dialog__close-icon "></span>
				</view>
			</view>
		</uni-popup>
	</block>
	<view style="display:none">{{test}}</view>
	<loading v-if="loading"></loading>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
</view>
</template>

<script>
var app = getApp();

export default {
  data() {
    return {
			isload:false,
			loading:false,
			pre_url:app.globalData.pre_url,
      info:{},
			pagecontent:[],
			edit_index:-1,
			test:'',
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
  methods: {
		getdata:function(){
			var that = this;
			var id = that.opt.id ? that.opt.id : '';
			that.loading = true;
			app.get('ApiAdminMember/richinfo',{mid:id}, function (res) {
				that.loading = false;
				that.info = res.info;
				that.pagecontent = res.pagecontent;
				that.loaded();
			});
		},
    subform: function (e) {
      var that = this;
      var formdata = e.detail.value;
      var id = that.opt.id ? that.opt.id : '';
      app.post('ApiAdminMember/richinfo', {mid:id,info:formdata,pagecontent:that.pagecontent}, function (res) {
        if (res.status == 0) {
          app.error(res.msg);
        } else {
          app.success(res.msg);
          setTimeout(function () {
            app.goback(true);
          }, 1000);
        }
      });
    },
		detailAddtxt:function(){
			this.edit_index = -1;
			this.$refs.dialogDetailtxt.open();
		},
		dialogDetailtxtClose:function(){
			this.$refs.dialogDetailtxt.close();
		},
		catcheDetailtxt:function(e){
			console.log(e)
			this.catche_detailtxt = e.detail.value;
		},
		dialogDetailtxtConfirm:function(e){
			var detailtxt = this.catche_detailtxt;
			console.log(detailtxt)
			var Mid = 'M' + new Date().getTime() + parseInt(Math.random() * 1000000);
			var pagecontent = this.pagecontent;
			if(this.edit_index != -1){
				pagecontent[this.edit_index].params.showcontent = detailtxt;
				pagecontent[this.edit_index].params.content = detailtxt;
			}else{
				pagecontent.push({"id":Mid,"temp":"text","params":{"content":detailtxt,"showcontent":detailtxt,"bgcolor":"#ffffff","fontsize":"14","lineheight":"20","letter_spacing":"0","bgpic":"","align":"left","color":"#000","margin_x":"0","margin_y":"0","padding_x":"5","padding_y":"5","quanxian":{"all":true},"platform":{"all":true}},"data":"","other":"","content":""});
			}
			this.pagecontent = pagecontent;
			this.test = Math.random();
			this.$refs.dialogDetailtxt.close();
		},
		detailAddpic:function(){
			this.edit_index = -1;
			this.detailAddpicConfirm();
		},
		detailAddpicConfirm:function(){
			var that = this;
			app.chooseImage(function(urls){
				var Mid = 'M' + new Date().getTime() + parseInt(Math.random() * 1000000);
				var pics = [];
				for(var i in urls){
					var picid = 'p' + new Date().getTime() + parseInt(Math.random() * 1000000);
					pics.push({"id":picid,"imgurl":urls[i],"hrefurl":"","option":"0"})
				}
				var pagecontent = that.pagecontent;
				if(that.edit_index != -1){
					pagecontent[that.edit_index].data = pics;
				}else{
					pagecontent.push({"id":Mid,"temp":"picture","params":{"bgcolor":"#FFFFFF","margin_x":"0","margin_y":"0","padding_x":"0","padding_y":"0","quanxian":{"all":true},"platform":{"all":true}},"data":pics,"other":"","content":""});
				}
				that.pagecontent = pagecontent;
				that.test = Math.random();
			},9);
		},
		detailAddvideo:function(){
			this.edit_index = -1;
			this.detailAddvideoConfirm();
		},
		detailAddvideoConfirm(){
			var that = this;
			uni.chooseVideo({
        sourceType: ['album', 'camera'],
        success: function (res) {
          var tempFilePath = res.tempFilePath;
          app.showLoading('上传中');
          uni.uploadFile({
            url: app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app.globalData.aid + '/platform/' + app.globalData.platform + '/session_id/' + app.globalData.session_id,
            filePath: tempFilePath,
            name: 'file',
            success: function (res) {
              app.showLoading(false);
              var data = JSON.parse(res.data);
              if (data.status == 1) {
                that.video = data.url;
								var pagecontent = that.pagecontent;
								var Mid = 'M' + new Date().getTime() + parseInt(Math.random() * 1000000);
								if(that.edit_index != -1){
									pagecontent[that.edit_index].params.src = data.url;
								}else{
									pagecontent.push({"id":Mid,"temp":"video","params":{"bgcolor":"#FFFFFF","margin_x":"0","margin_y":"0","padding_x":"0","padding_y":"0","src":data.url,"quanxian":{"all":true},"platform":{"all":true}},"data":"","other":"","content":""});
								}
								that.pagecontent = pagecontent;
								that.test = Math.random();
              } else {
                app.alert(data.msg);
              }
            },
            fail: function (res) {
              app.showLoading(false);
              app.alert(res.errMsg);
            }
          });
        },
        fail: function (res) {
          console.log(res); //alert(res.errMsg);
        }
      });
		},
		detailMoveup:function(e){
			var index = e.currentTarget.dataset.index;
			var pagecontent = this.pagecontent;
			if(index > 0)
				pagecontent[index] = pagecontent.splice(index-1, 1, pagecontent[index])[0];
		},
		detailMovedown:function(e){
			var index = e.currentTarget.dataset.index;
			var pagecontent = this.pagecontent;
			if(index < pagecontent.length-1)
				pagecontent[index] = pagecontent.splice(index+1, 1, pagecontent[index])[0];
		},
		detailMovedel:function(e){
			var index = e.currentTarget.dataset.index;
			var pagecontent = this.pagecontent;
			pagecontent.splice(index,1);
		},
		detailMoveEdit:function(e){
			var index = e.currentTarget.dataset.index;
			var pagecontent = this.pagecontent;
			console.log(pagecontent[index]);
			this.edit_index = index;
			console.log(this.edit_index)
			if(pagecontent[index].temp == "picture"){
				this.detailAddpicConfirm();
			}else if(pagecontent[index].temp == "video"){
				this.detailAddvideoConfirm();
			}else if(pagecontent[index].temp == "text"){
				this.$refs.dialogDetailtxt.open();
			}else{
				
			}
		},
		uploadimg:function(e){
			var that = this;
			var pernum = parseInt(e.currentTarget.dataset.pernum);
			if(!pernum) pernum = 1;
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			if(!pics) pics = [];
			app.chooseImage(function(urls){
				for(var i=0;i<urls.length;i++){
					pics.push(urls[i]);
				}
				if(field == 'pic') that.pic = pics;
				if(field == 'pics') that.pics = pics;
			},pernum);
		},
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			if(field == 'pic'){
				var pics = that.pic
				pics.splice(index,1);
				that.pic = pics;
			}else if(field == 'pics'){
				var pics = that.pics
				pics.splice(index,1);
				that.pics = pics;
			}
		},
  }
};
</script>
<style>
radio{transform: scale(0.6);}
checkbox{transform: scale(0.6);}
.form-box{ padding:2rpx 24rpx 0 24rpx; background: #fff;margin: 24rpx;border-radius: 10rpx}
.form-item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee }
.form-item .f1{color:#222;width:200rpx;flex-shrink:0}
.form-item .f2{display:flex;align-items:center}
.form-box .form-item:last-child{ border:none}
.form-box .flex-col{padding-bottom:20rpx}
.form-item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right}
.form-item textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;}
.form-item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.form-item .upload_pic image{ width: 32rpx;height: 32rpx; }
.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }

.ggtitle{height:60rpx;line-height:60rpx;color:#111;font-weight:bold;font-size:26rpx;display:flex;border-bottom:1px solid #f4f4f4}
.ggtitle .t1{width:200rpx;}
.ggcontent{line-height:60rpx;margin-top:10rpx;color:#111;font-size:26rpx;display:flex}
.ggcontent .t1{width:200rpx;display:flex;align-items:center;flex-shrink:0}
.ggcontent .t1 .edit{width:40rpx;height:40rpx}
.ggcontent .t2{display:flex;flex-wrap:wrap;align-items:center}
.ggcontent .ggname{background:#f55;color:#fff;height:40rpx;line-height:40rpx;padding:0 20rpx;border-radius:8rpx;margin-right:20rpx;margin-bottom:10rpx;font-size:24rpx;position:relative}
.ggcontent .ggname .close{position:absolute;top:-14rpx;right:-14rpx;background:#fff;height:28rpx;width:28rpx;border-radius:14rpx}
.ggcontent .ggnameadd{background:#ccc;font-size:36rpx;color:#fff;height:40rpx;line-height:40rpx;padding:0 20rpx;border-radius:8rpx;margin-right:20rpx;margin-left:10rpx;position:relative}
.ggcontent .ggadd{font-size:26rpx;color:#558}

.ggbox{line-height:50rpx;}


.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}


.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.radio .radio-img{width:100%;height:100%;display:block}

.freightitem{width:100%;height:60rpx;display:flex;align-items:center;margin-left:40rpx}
.freightitem .f1{color:#666;flex:1}

.detailop{display:flex;line-height:60rpx}
.detailop .btn{border:1px solid #ccc;margin-right:10rpx;padding:0 16rpx;color:#222;border-radius:10rpx}
.detaildp{position:relative;line-height:50rpx}
.detaildp .op{width:100%;display:flex;justify-content:flex-end;font-size:24rpx;height:60rpx;line-height:60rpx;margin-top:10rpx}
.detaildp .op .btn{background:rgba(0,0,0,0.4);margin-right:10rpx;padding:0 10rpx;color:#fff}
.detaildp .detailbox{border:2px dashed #00a0e9}

.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 15px 15px 15px;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}

.stockwarning{ position: absolute; right:0rpx; bottom:0;display:flex; align-items:center;font-size:24rpx;color:red;   }
.stockwarning image{  margin-right:10rpx}
</style>