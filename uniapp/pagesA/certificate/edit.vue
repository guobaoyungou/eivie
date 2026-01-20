<template>
	<view>
		<block v-if="isload">
			<form @submit="subform">
				<view class="form-box">
					<view class="form-item">
						<view class="f1">姓名<text style="color:red"> *</text></view>
						<view class="f2"><input type="text" name="name" :value="name" placeholder="请填写姓名" placeholder-style="color:#888"></input></view>
					</view>
					<view class="form-item">
						<view class="f1">联系方式<text style="color:red"> *</text></view>
						<view class="f2"><input type="text" name="tel" :value="tel" placeholder="请填写联系方式" placeholder-style="color:#888"></input></view>
					</view>
					<view class="form-item">
						<view class="f1">毕业院校<text style="color:red"> *</text></view>
						<view class="f2"><input type="text" name="school" :value="school" placeholder="请填写毕业院校" placeholder-style="color:#888"></input></view>
					</view>
					<view class="form-item">
						<view class="f1">学历<text style="color:red"> *</text></view>
						<view class="f2" @tap="changeEducationlistDialog"><text v-if="education">{{educationname}}</text><text v-else style="color:#888">请选择</text><image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
					</view>
					<view class="form-item">
						<view class="f1">证书类别<text style="color:red"> *</text></view>
						<view class="f2" @tap="changeClistDialog"><text v-if="cids.length > 0">{{cnames}}</text><text v-else style="color:#888">请选择</text><image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
					</view>
					<view class="form-item">
						<view class="f1">职业<text style="color:red"> *</text></view>
						<view class="f2" @tap="changeJoblistDialog"><text v-if="job_id.length > 0">{{job_name}}</text><text v-else style="color:#888">请选择</text><image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
					</view>
					
				</view>
				<view class="form-box">
					<view class="form-item flex-col" style="border-bottom:0">
						<view class="f1">证书图片<text style="color:red"> *</text></view>
						<view class="f2" style="flex-wrap: wrap">
							<view v-for="(item, index) in certificate_pic" :key="index" class="layui-imgbox">
								<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="certificate_pic"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
								<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
							</view>
							<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="certificate_pic" data-pernum="6" v-if="certificate_pic.length <6"></view>
						</view>
						<input type="text" hidden="true" name="certificate_pic" :value="certificate_pic.join(',')" maxlength="-1"/>
					</view>
				</view>
				<view class="form-box">
					<view class="form-item flex-col" style="border-bottom:0">
						<view class="f1">身份证正面<text style="color:red"> *</text></view>
						<view class="f2">
							<view v-for="(item, index) in idcard_pic_front" :key="index" class="layui-imgbox">
								<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="idcard_pic_front"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
								<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
							</view>
							<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="idcard_pic_front" data-pernum="1" v-if="idcard_pic_front==0"></view>
						</view>
						<input type="text" hidden="true" name="idcard_pic_front" :value="idcard_pic_front.join(',')" maxlength="-1"/>
					</view>
				</view>
				<view class="form-box">
					<view class="form-item flex-col" style="border-bottom:0">
						<view class="f1">身份证反面<text style="color:red"> *</text></view>
						<view class="f2">
							<view v-for="(item, index) in idcard_pic_back" :key="index" class="layui-imgbox">
								<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="idcard_pic_back"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
								<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
							</view>
							<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="idcard_pic_back" data-pernum="1" v-if="idcard_pic_back==0"></view>
						</view>
						<input type="text" hidden="true" name="idcard_pic_back" :value="idcard_pic_back.join(',')" maxlength="-1"/>
					</view>
				</view>
				<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">提交</button>
				<view style="height:50rpx"></view>
			</form>
			<view class="popup__container" v-if="educationshow">
				<view class="popup__overlay" @tap.stop="changeEducationlistDialog"></view>
				<view class="popup__modal">
					<view class="popup__title">
						<text class="popup__title-text">请选择学历</text>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="changeEducationlistDialog"/>
					</view>
					<view class="popup__content">
						<block v-for="(item, index) in educationlist" :key="item.id">
							<view class="clist-item" @tap="educationChange" :data-id="item.id" :data-name="item.name">
								<view class="flex1">{{item.name}}</view>
								<view class="radio" :style="item.id == education ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
							</view>
						</block>
					</view>
				</view>
			</view>
			<view class="popup__container" v-if="clistshow">
				<view class="popup__overlay" @tap.stop="changeClistDialog"></view>
				<view class="popup__modal">
					<view class="popup__title">
						<text class="popup__title-text">请选择证书类别</text>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="changeClistDialog"/>
					</view>
					<view class="popup__content">
						<block v-for="(item, index) in clist" :key="item.id">
							<view class="clist-item" @tap="cidsChange" :data-id="item.id" :data-name="item.name">
								<view class="flex1">{{item.name}}</view>
								<view class="radio" :style="inArray(item.id,cids) ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
							</view>
						</block>
					</view>
				</view>
			</view>
			<view class="popup__container" v-if="joblistshow">
				<view class="popup__overlay" @tap.stop="changeJoblistDialog"></view>
				<view class="popup__modal">
					<view class="popup__title">
						<text class="popup__title-text">请选择职业</text>
						<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="changeJoblistDialog"/>
					</view>
					<view class="popup__content">
						<block v-for="(item, index) in joblist" :key="item.id">
							<view class="clist-item" @tap="jobChange" :data-id="item.id" :data-name="item.name">
								<view class="flex1">{{item.name}}</view>
								<view class="radio" :style="inArray(item.id,job_id) ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
							</view>
						</block>
					</view>
				</view>
			</view>
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
				clistshow:false,
				joblistshow:false,
				educationshow:false,
				pre_url:app.globalData.pre_url,
				clist:[],
				joblist:[],
				pic:[],
				name:'',
				tel:'',
				school:'',
				education:'',
				educationname:'',
				gnames:'',
				educationlist:[],
				
				cateArr:[],
				cids:[],
				cnames:'',
				
				jobArr:[],
				job_id:[],
				job_name:'',
				
				certificate_pic:[],
				idcard_pic_front:[],
				idcard_pic_back:[]
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
				if(that.opt.id){
					app.get('ApiCertificate/getdetail',{id:that.opt.id}, function (res) {
						that.loading = false;
						var data = res.data;
						that.name = data.name;
						that.tel = data.tel;
						that.school = data.school;
						that.education = data.education;
						that.certificate_pic = data.certificate_pic;
						that.idcard_pic_front = data.idcard_pic_front;
						that.idcard_pic_back = data.idcard_pic_back;
						that.job_id = data.job_id;
						that.cids = data.cid;
						that.cnames = data.cname;
						that.job_name = data.job_name;
						that.educationname = data.educationname;
					})
				}
			
				app.get('ApiCertificate/getclist',{}, function (res) {
					that.cateArr = res.cateArr;
					that.jobArr = res.jobArr;
					that.clist = res.clist;
					that.joblist = res.joblist;
					that.educationlist = res.educationlist;
					if(that.opt.id){
						that.tel = res.tel;
					}
					
					that.loaded();
				})
			},
			changeClistDialog:function(){
				this.clistshow = !this.clistshow
			},
			changeJoblistDialog:function(){
				this.joblistshow = !this.joblistshow
			},
			changeEducationlistDialog:function(){
				this.educationshow = !this.educationshow
			},
			cidsChange:function(e){
				var clist = this.clist;
				var cids = this.cids;
				var cid = e.currentTarget.dataset.id;
				var newcids = [];
				var ischecked = false;
				for(var i in cids){
					if(cids[i] != cid){
						newcids.push(cids[i]);
					}else{
						ischecked = true;
					}
				}
				if(ischecked==false){
					if(newcids.length >= 5){
						app.error('最多只能选择五个分类');return;
					}
					newcids.push(cid);
				}
				this.cids = newcids;
				this.getcnames();
			},
			getcnames:function(){
				var cateArr = this.cateArr;
				var cids = this.cids;
				var cnames = [];
				for(var i in cids){
					console.log(cateArr[cids[i]]);
					cnames.push(cateArr[cids[i]]);
				}
				this.cnames = cnames.join(',');
			},
			jobChange:function(e){
				
				var clist = this.joblist;
				var cids = this.job_id;
				var cid = e.currentTarget.dataset.id;
				var newcids = [];
				var ischecked = false;
				for(var i in cids){
					if(cids[i] != cid){
						newcids.push(cids[i]);
					}else{
						ischecked = true;
					}
				}
				if(ischecked==false){
					if(newcids.length >= 5){
						app.error('最多只能选择五个职业');return;
					}
					newcids.push(cid);
				}
				this.job_id = newcids;
				this.getjobnames();
			},
			getjobnames:function(){
				var cateArr = this.jobArr;
				var cids = this.job_id;
				var cnames = [];
				for(var i in cids){
					cnames.push(cateArr[cids[i]]);
				}
				this.job_name = cnames.join(',');
			},
			educationChange:function(e){
				var educationlist = this.educationlist;
				var cid = e.currentTarget.dataset.id;
				var educationname =e.currentTarget.dataset.name;
				this.education = cid;
				this.educationname = educationname;
				var that =this;
				setTimeout(function(){
					that.educationshow = false;
				},200)
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
					if(field == 'certificate_pic') that.certificate_pic = pics;
					if(field == 'idcard_pic_front') that.idcard_pic_front = pics;
					if(field == 'idcard_pic_back') that.idcard_pic_back = pics;
				},pernum);
			},
			removeimg:function(e){
				var that = this;
				var index= e.currentTarget.dataset.index
				var field= e.currentTarget.dataset.field
				if(field == 'certificate_pic'){
					var pics = that.certificate_pic
					pics.splice(index,1);
					that.certificate_pic = pics;
				}else if(field == 'idcard_pic_front'){
					var pics = that.idcard_pic_front
					pics.splice(index,1);
					that.idcard_pic_front = pics;
				}else if(field == 'idcard_pic_back'){
					var pics = that.idcard_pic_front
					pics.splice(index,1);
					that.idcard_pic_back = pics;
				}
			},
			subform: function (e) {
				var that = this;
				var formdata = e.detail.value;
				console.log(formdata);
				if(formdata.name ==''){
					 app.error('请输入姓名');
					 return;
				}
				if(formdata.tel ==''){
					 app.error('请输入联系方式');
					 return;
				}
				if(formdata.school ==''){
					 app.error('请输入毕业院校');
					 return;
				}
				if(formdata.education ==''){
					 app.error('请选择学历');
					 return;
				}
				
				if(that.cid ==''){
					 app.error('请选择证书类别');
					 return;
				}
				if(that.job_id ==''){
					 app.error('请选择职业');
					 return;
				}
				if(formdata.certificate_pic ==''){
					 app.error('请上传证书');
					 return;
				}
				if(formdata.idcard_pic_front ==''){
					 app.error('请上传身份证正面');
					 return;
				}
				if(formdata.idcard_pic_back ==''){
					 app.error('请上传身份证反面');
					 return;
				}
				formdata.cid = that.cids;
				formdata.job_id = that.job_id;
				formdata.education = that.education;
				that.loading = true;
				app.post('ApiCertificate/save', {id:that.opt.id ,info:formdata}, function (res) {
					that.loading = false;
					if (res.status == 0) {
						app.error(res.msg);
					} else {
						app.success(res.msg);
						var url = 'mylist'
						setTimeout(function () {
						  app.goto(url, 'redirect');
						}, 1000);
					}
				});
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
.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.radio .radio-img{width:100%;height:100%;display:block}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }
.layui-imgbox-close{z-index: 5;}
</style>
