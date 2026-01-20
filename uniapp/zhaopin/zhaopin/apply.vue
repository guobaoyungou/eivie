<template>
	<view class="content" v-if="isload">
		<form @submit="formSubmit">
			<view class="title"><image src="../../static/img/reg-code.png"></image>企业认证</view>
			<view class="box">
				<view class="form-item">
					<view class="form-label">企业名称<text style="color:red"> *</text></view>
					<view class="form-value">
						<input type="text" name="company" placeholder="请输入企业名称" :value="info.company" placeholder-style="font-size:28rpx;color:#cccccc" />
					</view>
				</view>
				<view class="form-item">
					<view class="form-label">负责人姓名<text style="color:red"> *</text></view>
					<view class="form-value">
						<input type="text" name="name" placeholder="请输入负责人姓名" :value="info.name" placeholder-style="font-size:28rpx;color:#cccccc" />
					</view>
				</view>
				<view class="form-item">
					<view class="form-label">负责人电话<text style="color:red"> *</text></view>
					<view class="form-value">
						<input type="text" name="tel" placeholder="请输入负责人电话" :value="info.tel" placeholder-style="font-size:28rpx;color:#cccccc" />
					</view>
				</view>
				<view class="form-item">
					<view class="form-label">身份证号<text style="color:red"> *</text></view>
					<view class="form-value">
						<input type="text" name="cardno" placeholder="请输入负责人身份证号" :value="info.cardno" placeholder-style="font-size:28rpx;color:#cccccc" />
					</view>
				</view>
				<view class="form-item">
					<view class="form-label">营业执照<text style="color:red"> *</text></view>
					<view class="form-value">
						<view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
							<view class="form-imgbox" v-for="(pic, index) in pics">
								<view class="form-imgbox-close"  @tap="removeimg" :data-index="index" v-if="info.length==0 || (info && info.status==2)">
									<image src="/static/img/ico-del.png"></image>
								</view>
								<view class="form-imgbox-img">
									<image :src="pic" @click="previewImage" :data-url="pic" mode="widthFix" />
								</view>
							</view>
							<view class="form-uploadbtn"  v-if="pics.length<2"  @tap="uploadpic"  :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 32rpx',backgroundSize:'50rpx 50rpx',backgroundColor:'#F3F3F3'}"></view>
						</view>
					</view>
				</view>
			</view>
			<view class="box">
				<view class="form-item-row">
					<view class="form-label">备注</view>
					<view class="form-value textarea">
						<textarea name="remark" placeholder="请填写备注" :value="info.remark" placeholder-style="color:#cccccc;font-size:28rpx"></textarea>
					</view>
				</view>
			</view>
			<view class="form-option" v-if="info.length==0 || (info && info.status==2)">
				<!-- <button class="btn btn1" data-type="2" @tap="draft">保存草稿</button> -->
				<button class="btn" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)',color:'#ffffff'}" form-type="submit" data-type="1">确 定</button>
			</view>
		</form>
		
		<loading v-if="loading"></loading>
		<popmsg ref="popmsg"></popmsg>
	</view>
</template>
<script>
	var app = getApp();
	export default {
		data() {
			return {
				opt: {},
				loading: false,
				isload: false,
				pre_url: app.globalData.pre_url,
				thumb:'',
				pics:[],
				info:[],
				bid:0,
				showModal:false,
				zhaopin_id:0,
				vipoid:0,
				tmplids:[]
			}
		},
		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.bid = this.opt.bid || ''; 
			this.zhaopin_id = this.opt.zid || 0; 
			this.vipoid = this.opt.vipoid || 0; 
			this.getdata();
		},
		onPullDownRefresh: function(e) {
			
		},
		methods: {
			getdata: function() {
				var that = this;
				var opt = this.opt
				that.loading = true
				app.get('ApiZhaopin/zhaopinApply', {bid:that.bid}, function (res) {
					that.loading = false;
					if(res.status==0){
						app.alert(res.msg)
						app.goback(true);
						return;
					}else if(res.status==2){
						app.alert(res.msg)
						// that.showModal = true
					}
					that.tmplids = res.tmplids;
					if(that.info){
						that.info = res.info
						if(that.info.hasOwnProperty('pics')){
							that.pics = that.info.pics
						}
					}
					that.loaded()
				});
			},
			uploadpic:function(e){
				var that = this;
				var pics = that.pics;
				app.chooseImage(function(urls){
					for(var i=0;i<urls.length;i++){
						pics.push(urls[i]);
					}
				})
				that.pics = pics
			},
			removeimg:function(e){
				var that = this;
				var index= e.currentTarget.dataset.index
				var pics = that.pics
				pics.splice(index,1)
				that.pics = pics
			},
			formSubmit: function (e) {
				var that = this;
			  var formdata = e.detail.value;
				//
				console.log(formdata)
				if (formdata.company == ''){
				  app.alert('请填写企业名称');
				  return;
				}
			  if (formdata.name == ''){
			    app.alert('请输入负责人姓名');
			    return;
			  }
				if (formdata.tel == ''){
				  app.alert('请输入负责人电话');
				  return;
				}
				if (!/^1[3456789]\d{9}$/.test(formdata.tel)) {
					app.alert('负责人电话格式错误');return false;
				}
				if (formdata.cardno == ''){
				  app.alert('请输入身份证号');
				  return;
				}
				
			  if (that.pics.length == 0) {
			    app.alert('请上传营业执照');
			    return;
			  }
				formdata['pics'] = that.pics.join(',');
				formdata['zhaopin_id'] = that.zhaopin_id;
				formdata['vip_orderid'] = that.vipoid;
				app.showLoading('提交中');
			  app.post("ApiZhaopin/zhaopinApply", {info:formdata}, function (data) {
					app.showLoading(false);
			    if (data.status == 1) {
						// that.getdata()
			      app.alert(data.msg,function(){
							that.subscribeMessage(function () {
							  setTimeout(function () {
									app.goto('/pages/index/index')
							  }, 1000);
							});
						});
			    } else {
			      app.error(data.msg);
			    }
			  });
			},
	}
}
</script>
<style>
	@import "../common.css";
	.content{color:#222222}
	.title{background: #FFFFFF;padding: 30rpx; font-size: 36rpx;font-weight:bold;}
	.title image{width: 46rpx;height: 46rpx;vertical-align: text-bottom;margin-right: 10rpx;}
	.box{background:#FFFFFF;padding: 0 30rpx;margin-bottom: 20rpx;}
	.hui{color: #CCCCCC;}
	.form-item{display: flex;justify-content: flex-start;align-items: center;border-bottom: 1rpx solid #f6f6f6; padding: 20rpx 0;}
	.form-value textarea{padding: 10rpx 0;}
	.form-label{flex-shrink: 0;width: 200rpx;flex-wrap: wrap;/* text-align: justify;text-align-last: justify; */padding-right: 30rpx;}
	.form-value{flex: 1;}
	.form-tips{color: #CCCCCC;font-size: 28rpx;padding: 20rpx 0;}
	.form-value .down{width: 32rpx;height: 32rpx;vertical-align:middle;flex-shrink: 0;}
	.form-value.radio label{margin-right: 20rpx;}
	.form-value.upload{display: flex;align-items: center;flex-wrap: wrap;}
	/deep/.input-value-border{border: none;}
	/deep/.input-value{line-height: normal;padding: 0;}
	/* 行排列 */
	.form-item-row{border-bottom: 1rpx solid #f6f6f6; padding: 20rpx 0;}
	.form-item-row .form-label,.form-item-row .form-value{width: 100%;}
	.form-item-row .form-value textarea{width: 100%;height: 200rpx;}
	
	.form-option{display: flex;justify-content: center;padding: 30rpx;}
	.form-option .btn{text-align: center;padding:0 20rpx;width: 90%;border-radius: 10rpx;}
	.form-option .btn1{border: 1rpx solid #CCCCCC;margin-left: 0;}
	
	/* 图片 */
	.form-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
	.form-imgbox-close{position: absolute;display: block;width:32rpx;height:32rpx;right:-16rpx;top:-16rpx;color:#999;font-size:32rpx;background:#fff}
	.form-imgbox-close image{width:100%;height:100%;z-index: 9;}
	.form-imgbox-img{display: block;width:120rpx;height:120rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
	.form-imgbox-img>image{max-width:100%;}
	.form-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
	.form-uploadbtn{position:relative;height:116rpx;width:116rpx}
	
	.box .form-item:last-child{border: none;}
	.headpic{text-align: center;padding:0;background: #FFFFFF;margin-bottom: 20rpx;}
	.headpic .thumb{width: 100%;height: 220rpx;display: flex;justify-content: center;align-items: center;}
	.headpic .thumb image{height: 180rpx;width: 180rpx;border-radius: 50%;}
	.headpic .camera{padding: 30rpx;}
	.headpic .camera image{width: 120rpx;height: 120rpx;}
</style>
