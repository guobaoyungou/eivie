<template>
<view>
	<block v-if="isload">
		<view style="color:red;padding:10rpx 30rpx;margin-top:20rpx" v-if="info.id && info.status==2">审核不通过：{{info.reason}}，请修改后再提交</view>
		<view style="color:red;padding:10rpx 30rpx;margin-top:20rpx" v-else-if="info.id && info.status==0 ">您已提交申请，请等待审核</view>
		<form @submit="subform">
			<view class="apply_box">
				<view class="apply_item">
					<view class="f1">商户类目</view>
					<view  style="display: flex;align-items: center;"  @tap="changeClistDialog">
						<text v-if="fwcids.length>0">{{fwcnames}}</text><text v-else style="color:#888">请选择</text><image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/>
					</view>
				</view>
				<view class="apply_item">
					<view>商户名称<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="name" :value="info.name" placeholder="请填写商户名称"></input></view>
				</view>
				
			</view>
			<view class="apply_box">
				<view class="apply_item" style="border-bottom:0"><view>商家LOGO</view></view>
				<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
					<view v-for="(item, index) in logopic" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="logopic"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
					</view>
					<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="logopic" v-if="logopic.length<1"></view>
				</view>
				<input type="text" hidden="true" name="logo" :value="logopic.join(',')" maxlength="-1"></input>
			</view>
			
			<view class="apply_box">
				<view class="apply_item" style="border-bottom:0"><view>商家照片</view></view>
				<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
					<view v-for="(item, index) in pics" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pics"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
					</view>
					<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pics" v-if="pics.length < 5"></view> 
				</view>
				<input type="text" hidden="true" name="pics" :value="pics.join(',')" maxlength="-1"></input>
			</view>
			<view class="apply_box">
				<view class="apply_item">
					<view>联系人</view>
					<view class="flex-y-center"><input type="text" name="linkman" :value="info.linkman" placeholder="请填写联系人"></input></view>
				</view>
				<view class="apply_item">
					<view>联系电话</view>
					<view class="flex-y-center"><input type="text" name="linktel" :value="info.linktel" placeholder="请填写联系电话"></input></view>
				</view>
				<view class="apply_item">
					<view>客服电话</view>
					<view class="flex-y-center"><input type="text" name="tel" :value="info.linktel" placeholder="请填写客服电话"></input></view>
				</view>
			</view>
			<view class="apply_box">
				<view class="apply_item">
					<view>商家地址<text style="color:red"> *</text></view>
					<view class="flex-y-center">
								<uni-data-picker :localdata="items" :border="false" :placeholder="regiondata || '请选择省市区'" @change="regionchange"></uni-data-picker>
					</view>
				</view>
				<view class="apply_item">
					<view>定位坐标<text style="color:red"> *</text></view>
					<view class="flex-y-center" @tap="locationSelect"><input type="text" disabled placeholder="请选择坐标" :value="latitude ? latitude+','+longitude:''" ></input></view>
				</view>
				<input type="text" hidden="true" name="latitude" style="position: fixed;left: -200%;" :value="latitude"></input>
				<input type="text" hidden="true" name="longitude" style="position: fixed;left: -200%;" :value="longitude"></input>
				<view class="apply_item">
					<view>商家描述</view>
					<view class="flex-y-center"><input type="text" name="desc"  :value="info.desc"  placeholder="个人简介"></input> </view>
				</view>
			</view>	
			<view style="padding:30rpx 0"><button v-if="!info.id || info.status==2" form-type="submit" class="set-btn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'">提交申请</button>
</view>
		</form>
	</block>
	
	<view class="popup__container" v-if="fwlistshow">
		<view class="popup__overlay" @tap.stop="changeClistDialog"></view>
		<view class="popup__modal">
			<view class="popup__title">
				<text class="popup__title-text">请选择商户分类</text>
				<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="changeClistDialog"/>
			</view>
			<view class="popup__content">
				<block v-for="(item, index) in clist" :key="item.id">
					<view class="clist-item" @tap="fwcidsChange" :data-id="item.id">
						<view class="flex1">{{item.name}}</view>
						<view class="radio" :style="inArray(item.id,fwcids) ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
					</view>
					
				</block>
			</view>
		</view>
	</view>
	
	
	
	
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
		regiondata:'',
		pre_url:app.globalData.pre_url,
		datalist: [],
		pagenum: 1,
		logopic:[],
		pics:[],			
		cindex: 0,
		isagree: false,
		showxieyi: false,
		fwlistshow:false,
		fwcids:[],
		fwcateArr:[],
		fwcnames:'',
		info: [],
		set:{},
		latitude: '',
		longitude: '',
		address:'',
		items:[],
		bid:'',
    };
  },

  onLoad: function (opt) {
		this.getdata();
		this.opt = app.getopts(opt);
		if(this.opt.bid){
			this.bid = this.opt.bid;
		}
		console.log(this.bid,'bid');
		this.getdata();
		this.type = this.opt.type || 0;
		var that = this;
		app.get('ApiIndex/getCustom',{}, function (customs) {
			var url = app.globalData.pre_url+'/static/area.json';
			if(customs.data.includes('plug_zhiming')) {
				url = app.globalData.pre_url+'/static/area_gaoxin.json';
			}
			uni.request({
				url: url,
				data: {},
				method: 'GET',
				header: { 'content-type': 'application/json' },
				success: function(res2) {
					that.items = res2.data
				}
			});
		});
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiAdminBusiness/editBusiness', {bid:that.bid}, function (res) {
				that.loading = false;
				uni.setNavigationBarTitle({
					title: res.title
				});
				that.info = res.info
				console.log(that.info,'that.info');
				var clist = res.clist;
				var logopic = res.info ? res.info.logo : '';
				
				
				if (logopic) {
					logopic = logopic.split(',');
				} else {
					logopic = [];
				}
				that.logopic = logopic;
				console.log( logopic,'logopic');
				var pics = res.info ? res.info.pics : '';
				if (pics) {
					pics = pics.split(',');
				} else {
					pics = [];
				}
				that.pics = pics;
				that.clist = res.clist
				that.fwcateArr = res.fwcateArr
				that.fwcids = res.fwcids;
				that.regiondata = res.info.citys
				that.latitude = res.info.latitude;
				that.longitude = res.info.longitude;
				that.address = res.info.address;
				that.set = res.set
			
				that.getcnames();
				that.loaded();
			});
		},
		fwcidsChange:function(e){
			var fwlist = this.fwlist;
			var fwcids = this.fwcids;
			var fwcid = e.currentTarget.dataset.id;
			var newfwcids = [];
			var ischecked = false;
			for(var i in fwcids){
				if(fwcids[i] != fwcid){
					newfwcids.push(fwcids[i]);
				}else{
					ischecked = true;
				}
			}
			if(ischecked==false){
				if(newfwcids.length >= 5){
					app.error('最多只能选择五个分类');return;
				}
				newfwcids.push(fwcid);
			}
			this.fwcids = newfwcids;
			this.getcnames();
		},
		getcnames:function(){
			var fwcateArr = this.fwcateArr;
			var fwcids = this.fwcids;
			var fwcnames = [];
			for(var i in fwcids){
				fwcnames.push(fwcateArr[fwcids[i]]);
			}
			this.fwcnames = fwcnames.join(',');
		},
		
		
		changeClistDialog:function(){
			var that =this

			this.fwlistshow = !this.fwlistshow
		},
		
		locationSelect: function () {
		  var that = this;
		  uni.chooseLocation({
			success: function (res) {
			  that.info.address = res.name;
						that.info.latitude = res.latitude;
			  that.info.longitude = res.longitude;
			  that.address = res.name;
			  that.latitude = res.latitude;
			  that.longitude = res.longitude;
			}
		  });
		},
		regionchange(e) {
			const value = e.detail.value
			this.regiondata = value[0].text + ',' + value[1].text + ',' + value[2].text
		},
		
		subform: function (e) {
			var that = this;
			var info = e.detail.value;
			if (info.name == '') {
				app.error('请填写商户名称');
				return false;
			}
			var regiondata = that.regiondata;
			if(regiondata == '' || regiondata==undefined) {
				app.error('请选择商家地址');
				return;
			}
			info.citys = regiondata;
			if (info.latitude == '' || info.latitude==undefined ) {
				app.error('请选择地址坐标');
				return false;
			}
			info.fwcids = that.fwcids
			info.address = that.address;
			info.latitude = that.latitude;
			info.longitude = that.longitude;
			if (that.info && that.info.id) {
			  info.id = that.info.id;
			}
			app.showLoading('提交中');
			app.post("ApiAdminBusiness/editBusiness", {info: info}, function (res) {
				app.showLoading(false);
				app.error(res.msg);
				if(res.status == 1){
					setTimeout(function () {
						that.getdata()
						 app.goto(res.tourl);
						//app.goto(app.globalData.indexurl);
					}, 1000);
				}
		  });
		},
    isagreeChange: function (e) {
      console.log(e.detail.value);
      var val = e.detail.value;
      if (val.length > 0) {
        this.isagree = true;
      } else {
        this.isagree = false;
      }
    },
    showxieyiFun: function () {
      this.showxieyi = true;
    },
    hidexieyi: function () {
      this.showxieyi = false;
			this.isagree = true;
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
				if(field == 'logopic') that.logopic = pics;
				if(field == 'pics') that.pics = pics;
			},1)
		},
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			if(field == 'logopic'){
				var pics = that.logopic
				pics.splice(index,1);
				that.logopic = pics;
			}else if(field == 'pics'){
				var pics = that.pics
				pics.splice(index,1);
				that.pics = pics;
			}
		},
  }
}
</script>
<style>
radio{transform: scale(0.6);}
checkbox{transform: scale(0.6);}
.apply_box{ padding:2rpx 24rpx 0 24rpx; background: #fff;margin: 24rpx;border-radius: 10rpx}
.apply_title { background: #fff}
.apply_title .qr_goback{ width:18rpx;height:32rpx; margin-left:24rpx;     margin-top: 34rpx;}
.apply_title .qr_title{ font-size: 36rpx; color: #242424;   font-weight:bold;margin: 0 auto; line-height: 100rpx;}

.apply_item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee }
.apply_box .apply_item:last-child{ border:none}
.apply_item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right}
.apply_item input::placeholder{ color:#999999}
.apply_item textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;}
.apply_item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.apply_item .upload_pic image{ width: 32rpx;height: 32rpx; }
.set-btn{width: 90%;margin:0 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}



.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.clist-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.clist-item .radio .radio-img{width:100%;height:100%;display:block}

</style>