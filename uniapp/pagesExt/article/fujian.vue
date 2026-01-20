<template>
<view class="container">
	<block v-if="isload">
		<!--资源-->
		<view class="zybox" v-if="datalist.length > 0">
			<view class="zy_list" v-for="(item,index) in datalist" :data-type="item.type" :data-url = "item.url" @click="openFile">
				<view class="zy_left  flex-y-center ">
					<image v-if="item.type =='pdf'" class="image zy_image" :src="pre_url + '/static/img/article/pdf.png'" />
					<image v-else-if="item.type =='xlsx' ||item.type =='xls' " class="image zy_image" :src="pre_url + '/static/img/article/excel.png'" />
					<image v-else-if="item.type =='doc' ||item.type =='docx' " class="image zy_image" :src="pre_url + '/static/img/article/word.png'" />
					<image v-else-if="item.type =='ppt' ||item.type =='pptx' " class="image zy_image" :src="pre_url + '/static/img/article/ppt.png'" />
					<image v-else-if="item.type =='mp4'  " class="image zy_image" :src="pre_url + '/static/img/article/mp4.png'" />
					<image v-else-if="item.type =='mp3' " class="image zy_image" :src="pre_url + '/static/img/article/mp3.png'" />
					<image v-else-if="item.type =='zip' ||item.type =='rar' ||item.type =='7z'" class="image zy_image" :src="pre_url + '/static/img/article/zip.png'" />
					<image v-else class="image zy_image" :src="pre_url + '/static/img/article/png.png'" />		
				</view>
				<view class="zy_content flex-y-center" >
					{{item.name}}
				</view>
				<view class="flex-y-center f1" style="justify-content: center; ">
					<image v-if="look_auth=='0'"  class="image suo_image" :src="pre_url + '/static/img/article/suo.png'" />
					<button v-else class="btn_yl"><text v-if="item.type =='zip' ||item.type =='rar' ||item.type =='7z'">下载</text><text v-else>预览</text></button>
				</view>
			</view>
		</view>
	</block>
	<nodata v-if="nodata"></nodata>
	<nomore v-if="nomore"></nomore>
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
			pre_url:app.globalData.pre_url,
			opt:{},
			loading:false,
			isload: false,
			menuindex:-1,
			nodata:false,
			pagenum: 1,
			datalist: [],
			fujianpic:'',
			showfujian:false,
			showtype:'',
			picindex:0,
			down_auth:0,
			look_auth:0,
			audioindex:0,
		};
	},
	onLoad: function (opt) {
	this.opt = app.getopts(opt);
	if(this.opt){
		
	}
	this.getdata();
	},
	methods: {
		getdata: function () {
		  var that = this;
		  var id = that.opt.id;
			that.loading = true;
		  app.post('ApiArticle/getFujian', {id:id}, function (res) {
			that.loading = false;
			var data = res.data.fujian;
			uni.setNavigationBarTitle({
				title: res.title
			});
			that.down_auth = res.data.is_download_resource;
			that.look_auth = res.data.is_look_resource;
			if(data.length > 0){
				that.datalist = data;
			}else{
				that.nodata = true;
			}
			that.loaded();
		  });
		},
		openFile(e){
			var that = this;
			var file  = e.currentTarget.dataset.url;
			var type  = e.currentTarget.dataset.type;
			if(that.look_auth =='0'){
				app.alert('请升级会员后查看');
				return;
			}

			if(file ==''){
				app.alert('打开文件失败');
				return;
			}
			that.loading = true;
			var pngtype = ['png','jpg','gif','jepg','bmp','pd'];
			if(pngtype.indexOf(type) !== -1 || type =='mp4' || type =='mp3'){
				var showtype= type =='mp4'?'mp4':type =='mp3' ?'mp3':'png';
				that.loading = false;
				app.goto('/pagesExt/article/show?type='+showtype+'&url='+encodeURIComponent(file)+'&auth='+that.down_auth);
			}
			const filetype = ['pptx', 'ppt', 'docx', 'doc', 'xlsx', 'xls', 'pdf']
			if(filetype.indexOf(type) !== -1){
				// #ifdef H5
				    window.location.href= file;
				// #endif
				
				// #ifdef MP-WEIXIN
				uni.downloadFile({
					url: file, 
					success: (res) => {
						that.loading = false;
				        var filePath = res.tempFilePath;
						if (res.statusCode === 200) {
							uni.openDocument({
				              filePath: filePath,
				              showMenu: true,
				              success: function (res) {
				                console.log('打开文档成功');
				              }
				            });
						}
					}
				});
				// #endif
			}	
			const ziptype = ['zip', 'rar','7z']
			if(ziptype.indexOf(type) !== -1){
				// #ifdef H5
				    window.location.href= file;
				// #endif
				
				// #ifdef MP-WEIXIN
				const downloadTask = uni.downloadFile({
					url: file, 
					success: (res) => {
						that.loading = false;
				        var filePath = res.tempFilePath;
						if (res.statusCode === 200) {
							var tempfile =  res.tempFilePath;
							uni.getFileSystemManager().saveFile({
							  tempFilePath: tempfile,
							  success: function (res) {
								var savedFilePath  = res.savedFilePath;
								app.success('文件保存成功');
							  }
							}); 
						}
					}
				});
				// #endif
			}
		},
		imageClose: function() {
			this.showfujian = false;
		}
	}
};

</script>
<style>
page{background:#fff}
.zybox{background: #fff;width: 100%;padding: 0rpx 20rpx 20rpx 20rpx;margin-top: 25rpx;}
.zy_title{font-size: 34rpx;padding: 30rpx 0;}
.zy_title .zy_tip{font-size: 28rpx; color: #C0C0C0;margin-left: 20rpx;}
.zy_list{display: flex;height: 100rpx;border-bottom: 1px solid #EEEEEE;}
.zy_left{flex: 1;}
.zy_list .zy_image { width: 60rpx;height: 60rpx;}
.zy_list .zy_content{font-size: 26rpx;flex: 7;word-break: break-all}
.tobuy{height: 60rpx;line-height: 60rpx;color: #FFFFFF;border-radius: 32rpx;margin-left: 20rpx;flex-shrink: 0;padding: 0 50rpx;font-size: 24rpx; font-weight: bold;}
.btn_yl{height: 35rpx;line-height: 33rpx;color: #03a9f4;border: 1px solid #03a9f4;border-radius: 32rpx;padding: 0 15rpx;font-size: 24rpx;}
.btn_save{ width: 300rpx; wheight: 60rpx;line-height: 60rpx; background-color: #03a9f4; color: #FFFFFF;border-radius: 32rpx;flex-shrink: 0;padding: 0 50rpx;font-size: 24rpx; font-weight: bold;margin: 0rpx 0;justify-content: center;}
.video{width:100%;height:650rpx;}
.suo_image{ width: 40rpx; height: 40rpx;flex: 1;}
</style>