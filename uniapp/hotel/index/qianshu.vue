<template>
<view class="content">
		<db-signature @sumbit="sumbit"  @fail="fail" cid="ceshi1"></db-signature>
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
			
			set:{},
			psuser:{},
			resultUrl:''
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
		
		sumbit(res){
			console.log('sumbit',res)
			var that = this;
			try {
				// this.resultUrl = res.tempFilePath
				//将签名上传后台
				uni.uploadFile({
					url: app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app
									.globalData.aid + '/platform/' + app.globalData.platform +
									'/session_id/' +
									app.globalData.session_id,//仅为示例，非真实的接口地址
					filePath: res.tempFilePath,
					name: 'file',
					formData: {
						// 'user': 'test'
					},
					success: (uploadFileRes) => {
						//console.log(uploadFileRes.data);
						// 判断是否json字符串，将其转为json格式
						//let data = _this.$u.test.jsonString(uploadFileRes.data) ? JSON.parse(uploadFileRes.data) : uploadFileRes.data;
						var data =  JSON.parse(uploadFileRes.data)
						that.resultUrl = data.url;
						that.confirm()
						// _this.resultUrl = res.tempFilePath;
					}
				})
			} catch (e) {
			    // error
					console.log(e)
			}
		},
		confirm:function(e){
			var that=this;
			var image_url = that.resultUrl
			console.log(that.resultUrl);
			app.post('ApiMy/htconfirm', { image_url:image_url}, function (res) {
					if(res.status==1){
							app.goback()
					}
			})
		},
		
  }
};
</script>
<style>

</style>