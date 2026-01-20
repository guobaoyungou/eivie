<template>
<view class="container" style="padding:20rpx 0" :style="{background:toupiao.color1}">
	<block v-if="isload">
		<view style="color:red;padding:30rpx 30rpx;margin-top:20rpx;background:#fff;margin:0 28rpx;border-radius:8rpx" v-if="info.id && info.status==2">审核不通过：{{info.reason}}，请修改后再提交</view>
		<view style="color:red;padding:30rpx 30rpx;margin-top:20rpx;background:#fff;margin:0 28rpx;border-radius:8rpx" v-if="info.id && info.status==1">您已成功参与报名</view>
		<view style="color:green;padding:30rpx 30rpx;margin-top:20rpx;background:#fff;margin:0 28rpx;border-radius:8rpx" v-if="info.id && info.status==0">您已提交申请，请等待审核</view>
		<form @submit="formSubmit" @reset="formReset">

		
		<view class="apply_box">
			<view class="apply_item">
				<view>名称<text style="color:red"> *</text></view>
				<view class="flex-y-center"><input type="text" name="name" :value="info.name" placeholder="请输入选手名称"/></view>
			</view>
			<view class="apply_item">
				<view>联系方式<text style="color:red"> *</text></view>
				<view class="flex-y-center"><input type="text" name="weixin" :value="info.weixin" placeholder="请输入联系方式"></input></view>
			</view>
			
			<!-- 报名自定义字段 -->
			<view :class="{'apply_box': item.key === 'textarea' || item.key === 'upload_pics','apply_item': item.key !== 'textarea' && item.key !== 'upload_pics','aic':item.key === 'upload' || item.key === 'region'}"  v-for="(item, idx) in toupiao.formdata"  :key="item.id">
				<block v-if="item.key=='input'">
					<view>{{item.val1}}<text v-if="item.val3==1" style="color:red"> *</text></view>
					<view class="flex-y-center">
						<input type="text" :name="'form'+idx" class="input" :value="editorFormdata[idx]" :placeholder="item.val2" @input="setfield" :data-formidx="'form'+idx" />
					</view>
				</block>
				<block v-if="item.key=='textarea'">
					<view class="apply_item flex-col">
						<view><text class="title">{{item.val1}}</text><text v-if="item.val3==1" style="color:red"> *</text></view>
						<view class="flex-y-center"><textarea type="textarea" :name="'form'+idx" @input="setfield" :data-formidx="'form'+idx" :placeholder="item.val2" placeholder-style="font-size:24rpx" style="height:100rpx;background:#F8F8F8;padding:10rpx 20rpx" maxlength="-1" :value="editorFormdata[idx]"></textarea></view>
					</view>
				</block>
				<block v-if="item.key=='radio'">
					<view>{{item.val1}}<text v-if="item.val3==1" style="color:red"> *</text></view>
					<view class="flex-y-center">
						<radio-group class="radio-group" :name="'form'+idx" @change="setfield" :data-formidx="'form'+idx">
							<label v-for="(item1,idx1) in item.val2" :key="item1.id">
								<radio class="radio" :value="item1" :checked="editorFormdata[idx] && editorFormdata[idx]==item1 ? true : false"/>{{item1}}
							</label>
						</radio-group>
					</view>
				</block>
				<block v-if="item.key=='checkbox'">
					<view>{{item.val1}}<text v-if="item.val3==1" style="color:red"> *</text></view>
					<checkbox-group :name="'form'+idx" class="checkbox-group" @change="setfield" :data-formidx="'form'+idx">
						<label v-for="(item1,idx1) in item.val2" :key="item1.id">
							<checkbox class="checkbox" :value="item1" :checked="editorFormdata[idx] && inArray(item1,editorFormdata[idx]) ? true : false"/>{{item1}}
						</label>
					</checkbox-group>
				</block>
				<block v-if="item.key=='selector'">
					<view>{{item.val1}}<text v-if="item.val3==1" style="color:red"> *</text></view>
					<picker class="picker" mode="selector" :name="'form'+idx" :value="editorFormdata[idx] ? editorFormdata[idx] : '' " :range="item.val2" @change="editorBindPickerChange" :data-idx="idx" :data-formidx="'form'+idx">
						<view v-if="editorFormdata[idx] || editorFormdata[idx]===0">{{item.val2[editorFormdata[idx]]}}</view>
						<view v-else>请选择</view>
					</picker>
					<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
				</block>
				<block v-if="item.key=='time'">
					<view>{{item.val1}}<text v-if="item.val3==1" style="color:red"> *</text></view>
					<picker class="picker" mode="time" :name="'form'+idx" :value="editorFormdata[idx] ? editorFormdata[idx] : '' " :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx" :data-formidx="'form'+idx">
						<view v-if="editorFormdata[idx]">{{editorFormdata[idx]}}</view>
						<view v-else>请选择</view>
					</picker>
					<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
				</block>
				<block v-if="item.key=='date'">
					<view>{{item.val1}}<text v-if="item.val3==1" style="color:red"> *</text></view>
					<picker class="picker" mode="date" :name="'form'+idx" :value="editorFormdata[idx] ? editorFormdata[idx] : ''" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx" :data-formidx="'form'+idx">
						<view v-if="editorFormdata[idx]">{{editorFormdata[idx]}}</view>
						<view v-else>请选择</view>
					</picker>
					<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
				</block>
				<block v-if="item.key=='region'">
					<view>{{item.val1}}<text v-if="item.val3==1" style="color:red"> *</text></view>
					<uni-data-picker style="width: 80%;" :localdata="items" popup-title="请选择省市区" :placeholder="editorFormdata[idx] || '请选择省市区'" @change="onchange($event,idx)"></uni-data-picker>
				</block>
				<block v-if="item.key=='upload'">
					<view>{{item.val1}}<text v-if="item.val3==1" style="color:red"> *</text></view>
					<input type="text" style="display:none" :name="'form'+idx" :value="editorFormdata[idx]"/>
					<view class="flex" style="flex-wrap:wrap;padding:20rpx 0;">
						<view v-if="editorFormdata[idx]" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-idx="idx" :data-formidx="'form'+idx"><image :src="pre_url+'/static/img/ico-del.png'" style="display: block;"></image></view>
							<view class="layui-imgbox-img"><image :src="editorFormdata[idx]" @click="previewImage" :data-url="editorFormdata[idx]" mode="widthFix"></image></view>
						</view>
						<view v-else class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @click="uploadpic" :data-formidx="'form'+idx" :data-idx="idx"></view>
					</view>
				</block>	
				<block v-if="item.key=='upload_pics'">
					<view class="apply_item" style="border-bottom:none;">
						<view><text>{{item.val1}}</text><text v-if="item.val3==1" style="color:red"> *</text></view>
					</view>
					<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
						<view v-for="(item2, index2) in editorFormdata[idx]" :key="index2" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index2" data-type="pics" :data-idx="idx" :data-formidx="'form'+idx"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
							<view class="layui-imgbox-img"><image :src="item2" @tap="previewImage" :data-url="item2" mode="widthFix" :data-idx="idx"></image></view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @click="uploadpic" :data-idx="idx" :data-formidx="'form'+idx" data-type="pics"></view>
					</view>
				</block>
			</view>
			<!-- 报名自定义字段 end -->
			
		</view>
		<view class="apply_box">
			<view class="apply_item" style="border-bottom:0"><view>首图<text style="color:red"> *</text></view></view>
			<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
				<view v-if="pic" class="layui-imgbox">
					<view class="layui-imgbox-close" @tap="removepic"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
					<view class="layui-imgbox-img"><image :src="pic" @tap="previewImage" :data-url="pic" mode="widthFix"></image></view>
				</view>
				<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadpic" v-if="!pic"></view>
				<input type="text" hidden="true" name="pic" :value="pic" maxlength="-1"></input>
			</view>
		</view>
		<view class="apply_box" v-if="toupiao_type == 0">
			<view class="apply_item" style="border-bottom:0"><text>详情图片</text></view>
			<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
				<view v-for="(item, index) in pics" :key="index" class="layui-imgbox">
					<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pics"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
					<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
				</view>
				<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pics"></view>
				<input type="text" hidden="true" name="pics" :value="pics.join(',')" maxlength="-1"></input>
			</view>
		</view>
		<view class="apply_box" v-else-if="toupiao_type == 1">
			<view class="apply_item" style="border-bottom:0"><text>详情视频</text></view>
			<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
				<view v-for="(val, key) in videos" :key="key" class="layui-videobox">
					<view class="layui-imgbox-close" @tap="removeVideo" :data-index="key" data-field="videos"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
					<view class="layui-videobox-video">
						<video :src="val" style="width: 100%;"></video>
					</view>
				</view>
				<view class="uploadvideobtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-position:center;background-color:#F3F3F3;'" @tap="uploadvideo" data-field="videos"></view>
				<input type="text" hidden="true" name="videos" :value="videos.join(',')" maxlength="-1"></input>
			</view>
		</view>
		<view class="apply_box" style="padding-bottom:20rpx">
			<view class="apply_item flex-col">
				<view><text class="title">详情文字</text></view>
				<view class="flex-y-center"><textarea type="text" name="detail_txt" :value="info.detail_txt" placeholder="请输入详情文字~" placeholder-style="font-size:24rpx" style="height:100rpx;background:#F8F8F8;padding:10rpx 20rpx" maxlength="-1"></textarea></view>
			</view>
		</view>
		<button class="set-btn" form-type="submit" :style="{color:toupiao.color2}" v-if="!info.id || info.status==2">提 交</button>
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
			
			pic:'',
			pics:[],
			info:{},
			toupiao:{},
      headimg:[],
			latitude:'',
			longitude:'',
			videos:[],
			toupiao_type:0,
			editorFormdata:[],
			formvaldata:[],
			items:[]
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
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiToupiao/baoming', {id:that.opt.id}, function (res) {
				that.loading = false;
				if(res.status==1){
					that.info = res.info;
					
					that.toupiao = res.toupiao;
					if (that.toupiao.fanwei == 1) {
						app.getLocation(function(res2) {
							that.latitude = res2.latitude;
							that.longitude = res2.longitude;
						});
					}
					
					var pics = res.info ? res.info.pics : '';
					if (pics) {
						pics = pics.split(',');
					} else {
						pics = [];
					}
					that.pics = pics;
					
					that.pic = res.info.pic ? res.info.pic : '';
					
					if(res.toupiao && res.toupiao.toupiao_type){
						that.toupiao_type = res.toupiao.toupiao_type;
						if(that.toupiao_type == 1){
							var videos = res.info ? res.info.videos : '';
							if (videos) {
								videos = videos.split(',');
							} else {
								videos = [];
							}
							that.videos = videos;
						}
					}
					
					/* 报名自定义字段 */
					if(res.toupiao && res.toupiao.editorFormdata){
						that.editorFormdata = res.toupiao.editorFormdata;
						that.formvaldata = res.toupiao.formvaldata;
						
						uni.request({
							url: app.globalData.pre_url+'/static/area.json',
							data: {},
							method: 'GET',
							header: { 'content-type': 'application/json' },
							success: function(res2) {
								that.items = res2.data
							}
						});
					}
					that.loaded();
				}else{
					app.alert(res.msg)
					setTimeout(function(){
						app.goback();
					},1000)
				}
			});
		},
    formSubmit: function (e) {
			var that = this;
      var formdata = e.detail.value;
			formdata.id = that.opt.id;
      if (formdata.name == '') {
        app.alert('请输入名称');return;
      }
      if (formdata.weixin == '') {
        app.alert('请输入联系方式');return;
      }
			if (formdata.pic == '') {
			  app.alert('请上传首图');return;
			}
			formdata["latitude"] = that.latitude
			formdata["longitude"] = that.longitude
			/* 报名自定义字段验证 */
			if(that.toupiao.formdata){
				var formdataSet = that.toupiao.formdata;
				var formvaldata = that.formvaldata;
				for (var i = 0; i < formdataSet.length;i++){
					var value = formvaldata['form' + i];
					if (formdataSet[i].val3 == 1 && (formvaldata['form' + i] === '' || formvaldata['form' + i] === undefined || formvaldata['form' + i].length==0)){
							app.alert(formdataSet[i].val1+' 必填');return;
					}
					if (formdataSet[i].key == 'selector') {
						if(formdataSet[i].val2[formvaldata['form' + i]]){
							value = formdataSet[i].val2[formvaldata['form' + i]]
						}
					}
					formdata['form' + i] = value;
				}
			}
			/* 报名自定义字段验证 end */
			app.showLoading('提交中');
      app.post("ApiToupiao/baoming",formdata, function (data) {
				app.showLoading(false);
        if (data.status == 1) {
          app.success(data.msg);
          setTimeout(function () {
            app.goto('index?id='+that.opt.id);
          }, 1000);
        } else {
          app.error(data.msg);
        }
      });
    },
		uploadpic:function(e){
			var that = this;
			var idx = e.currentTarget.dataset.idx;
			var type = e.currentTarget.dataset.type;
			app.chooseImage(function(urls){
				/* 报名自定义字段 */
				if(idx >= 0){
					if(!that.editorFormdata) that.editorFormdata = [];
					if(type == 'pics'){
						var pics = that.editorFormdata[idx];
						if(!pics){
						  pics = [];
						}
						for(var i=0;i<urls.length;i++){
							pics.push(urls[i]);
						}
						that.$set(that.editorFormdata, idx, pics);
						var field = e.currentTarget.dataset.formidx;
						that.formvaldata[field] = pics;
					}else{
						that.$set(that.editorFormdata, idx, urls[0]);
						
						var field = e.currentTarget.dataset.formidx;
						that.formvaldata[field] = urls[0];
					}
					
				}else{
					that.pic = urls[0];
				}
			},1)
		},
		removepic:function(e){
			var that = this;
			that.pic = '';
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
				if(field == 'pics') that.pics = pics;
			},1)
		},
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			var idx = e.currentTarget.dataset.idx;
			/* 报名自定义字段 */
			if(idx >= 0){
				var type  = e.currentTarget.dataset.type;
				if(!that.editorFormdata) that.ditorFormdata = [];
				if(type == 'pics'){
					var pics = that.editorFormdata[idx];
					pics.splice(index,1);
					that.editorFormdata[idx] = pics;
					that.formvaldata[formidx] = pics;
				}else{
					var pics = that.editorFormdata
					var formidx = e.currentTarget.dataset.formidx;
					pics.splice(idx,1);
					that.editorFormdata = pics;
					that.formvaldata[formidx] = '';
					return;
				}
			}
			if(field == 'pics'){
				var pics = that.pics
				pics.splice(index,1);
				that.pics = pics;
			}
		},
		uploadvideo:function(e){
			var that = this;
			uni.chooseVideo({
			  sourceType: ['album', 'camera'],
			  maxDuration: 60,
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
								that.videos.push(data.url);
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
		removeVideo:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var video = that.videos
			video.splice(index,1);
			that.videos = video;
		},
		setfield:function(e){
			var field = e.currentTarget.dataset.formidx;
			var value = e.detail.value;
			this.formvaldata[field] = value;
		},
		editorBindPickerChange:function(e){
			console.log('editorBindPickerChange');
			var that = this;
			var idx = e.currentTarget.dataset.idx;
			var val = e.detail.value;
			if(!this.editorFormdata) this.editorFormdata = [];
			this.$set(this.editorFormdata, idx, val);
			
			var field = e.currentTarget.dataset.formidx;
			this.formvaldata[field] = val;
		},
		onchange(e,idx) {
		  const value = e.detail.value;
			var regiondata = value[0].text + ',' + value[1].text + ',' + value[2].text;
			this.$set(this.editorFormdata, idx, regiondata);
			this.formvaldata['form'+idx] = regiondata;
		},
  }
};
</script>
<style>
radio{transform: scale(0.6);}
checkbox{transform: scale(0.6);}
.apply_box{ padding:2rpx 24rpx 0 24rpx; background: #fff;margin: 24rpx;border-radius: 10rpx}
.apply_title { background: #fff}
.apply_title .qr_goback{ width:18rpx;height:32rpx; margin-left:24rpx;     margin-top: 34rpx;}
.apply_title .qr_title{ font-size: 36rpx; color: #242424;   font-weight:bold;margin: 0 auto; line-height: 100rpx;}

.apply_item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee }
.apply_box .apply_box{margin: 0;padding: 0;}
.apply_box .apply_item:last-child{ border:none}
.apply_box .picker{flex:1;text-align: right;}
.apply_item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right}
.apply_item input::placeholder{ color:#999999}
.apply_item textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;}
.apply_item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.apply_item .upload_pic image{ width: 32rpx;height: 32rpx; }
.set-btn{width: 90%;margin:0 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-size:30rpx;font-weight:bold;background:#fff}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.layui-videobox{position: relative;width: 100%;}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
.uploadvideobtn{height:200rpx;width:100%}
.aic{align-items: center;}
</style>