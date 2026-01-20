<template>
<view>
	<block v-if="isload">
		<view class="form-box">
			<view class="form-item">
				<view class="f1">姓名</view>
				<view class="f2"><input type="text" name="name" :value="info.name" disabled="true"></input></view>
			</view>
			<view class="form-item">
				<view class="f1">联系方式</view>
				<view class="f2"><input type="text" name="tel" :value="info.tel"  disabled="true"></input></view>
			</view>
			<view class="form-item">
				<view class="f1">毕业院校</view>
				<view class="f2"><input type="text" name="school" :value="info.school" disabled="true"></input></view>
			</view>
			<view class="form-item">
				<view class="f1">学历</view>
				<view class="f2"><input type="text" name="educationname" :value="info.educationname" disabled="true"></input></view>
			</view>
			<view class="form-item">
				<view class="f1">证书</view>
				<view class="f2"><input type="text" name="cid" :value="info.cname" disabled="true"></input></view>
			</view>
			<view class="form-item">
				<view class="f1">职业</view>
				<view class="f2"><input type="text" name="job_name" :value="info.job_name" disabled="true"></input></view>
			</view>
			<view class="form-item" v-if="opt.ismy ==1">
				<view class="f1">审核状态</view>
				<view class="f2">
					<input  :style="info.ischecked ==1?'color:green;':info.ischecked ==2?'color:red;width:60%':''" type="text" name="checkstatus" :value="info.checkstatus" disabled="true"></input>
					<button v-if="info.ischecked ==2" class="edit-btn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" @tap="goto" :data-url="'edit?id='+info.id">重新编辑</button>
					</view>
			</view>
			<view class="form-item" v-if="opt.ismy ==1 && info.ischecked ==2">
				<view class="f1">驳回理由</view>
				<view class="f2"><input type="text" name="check_reason" :value="info.check_reason" disabled="true"></input></view>
			</view>
		</view>
		<view class="form-box" v-if="info.certificate_pic && info.certificate_pic.length > 0">
			<view class="form-item flex-col" style="border-bottom:0">
				<view class="f1">证书图片</view>
				<view class="f2" style="flex-wrap: wrap;">
					<view v-for="(item, index) in info.certificate_pic" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="certificate_pic"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
					</view>
				</view>
			</view>
		</view>
		<view class="form-box" v-if="info.idcard_pic_front && info.idcard_pic_front.length > 0">
			<view class="form-item flex-col" style="border-bottom:0">
				<view class="f1">身份证正面图片</view>
				<view class="f2">
					<view v-for="(item, index) in info.idcard_pic_front" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="certificate_pic"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
					</view>
				</view>
			</view>
		</view>	
		<view class="form-box" v-if="info.idcard_pic_back && info.idcard_pic_back.length > 0">
			<view class="form-item flex-col" style="border-bottom:0">
				<view class="f1">身份证反面图片</view>
				<view class="f2">
					<view v-for="(item, index) in info.idcard_pic_back" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="certificate_pic"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
					</view>
				</view>
			</view>
		</view>
		
		<view v-if="!opt.ismy">
			<view class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" @tap="goto" :data-url="kfurl" v-if="kfurl!='contact::'" >
				<view class="t1">立即咨询</view>
			</view>
			<button class="savebtn" v-else open-type="contact" show-message-card="true" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" >立即咨询</button>
		</view>
		<view style="height:50rpx"></view>
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
				pre_url:app.globalData.pre_url,
				info:{},
				kfurl:'',
			}
		},
		onLoad: function (opt) {
				this.opt = app.getopts(opt);
				this.getdata();
		},
		methods: {
			getdata:function(){
				var that = this;
				that.loading = true;
				app.get('ApiCertificate/getdetail',{id:that.opt.id}, function (res) {
					that.info = res.data;
					that.kfurl = '/pages/kefu/index?bid=0';
					if(app.globalData.initdata.kfurl != ''){
						that.kfurl = app.globalData.initdata.kfurl;
					}
					that.loading = false;
					that.loaded();
				})
			},
		}
	}
</script>

<style>
.form-box{ padding:2rpx 24rpx 0 24rpx; background: #fff;margin: 24rpx;border-radius: 10rpx}
.form-box .form-item:last-child{ border:none}
.form-box .flex-col{padding-bottom:20rpx}

.form-item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee }
.form-item .f1{color:#222;width:200rpx;flex-shrink:0}
.form-item .f2{display:flex;align-items:center}
.form-item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right}
.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:30rpx; border: none; }
.edit-btn{border: none;    width: 28%;    background-color: red;    font-size: 24rpx;    color: #fff;}
</style>