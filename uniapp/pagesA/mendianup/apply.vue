<template>
<view>
	<block v-if="isload">
		<view style="color:red;padding:10rpx 30rpx;margin-top:20rpx" v-if="info.id && info.check_status==2">审核不通过：{{info.reason}}，请修改后再提交</view>
		<view style="color:red;padding:10rpx 30rpx;margin-top:20rpx" v-else-if="info.id && info.check_status==0 && info.status==1">您已提交申请，请等待审核</view>
		<view style="color:red;padding:10rpx 30rpx;margin-top:20rpx" v-else-if="info.id && info.check_status==0">您已提交申请，请等待审核</view>
		<form @submit="subform">
			<view class="apply_box">
				<view class="apply_item">
					<view>{{t('门店')}}名称<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="name" :value="info.name" :placeholder="'请填写'+t('门店')+'名称'"></input></view>
				</view>
				<view class="apply_item">
					<view>联系电话<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="tel" :value="info.tel" placeholder="请填写手机号码"></input></view>
				</view>
			</view>
		
			<view class="apply_box">
				
				<block v-if="!mendian_up">
					<view class="apply_item" style="border-bottom:0"><view>{{t('门店')}}主图<text style="color:red"> *</text></view></view>
					<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
						<view v-for="(item, index) in pic" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pic"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pic" v-if="pic.length==0"></view>
					</view>
					<input type="text" hidden="true" name="pic" :value="pic.join(',')" maxlength="-1"></input>
					
					<view class="apply_item" style="border-bottom:0"><view>{{t('门店')}}图片<text style="color:red"> *</text></view></view>
					<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
						<view v-for="(item, index) in pics" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pics"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pics" v-if="pics.length==0"></view>
					</view>
					<input type="text" hidden="true" name="pics" :value="pics.join(',')" maxlength="-1"></input>
				</block>	

				<block v-if="mendian_up">
					<view class="apply_item">
						<view>{{t('门店')}}地址<text style="color:red"> *</text></view>
						<view class="flex-y-center">
							<text class="cell-tip" @click="toggleMaskLocation">
								<text class="choose-text">{{addressByPcrs}}</text>
								<text class="iconfont icon-xiangxia"></text>
							</text>
						</view>
					</view>
					<view class="apply_item">
						<view>社区名称<text style="color:red"> *</text></view>
						<view class="flex-y-center"><input class="input" type="text" :placeholder="'请输入社区名称'" placeholder-style="font-size:28rpx;color:#BBBBBB" name="xqname" :value="info.xqname"></input></view>
					</view>
					
				</block>
				<block v-else>
					<view class="apply_item1">
						<view style="line-height: 100rpx;">门店地址<text style="color:red"> *</text></view>
						<view class="flex-y-center">
									<uni-data-picker :localdata="items" :border="false" :placeholder="regiondata || '请选择省市区'" @change="regionchange"></uni-data-picker>
						</view>
					</view>
				</block>
				
				
				<view class="apply_item">
					<view>详细位置<text style="color:red"> *</text></view>
					<view class="flex-y-center" @tap="selectzuobiao"><input type="text"  placeholder="请选择坐标" :value="latitude ? latitude+','+longitude:''" ></input></view>
				</view>
				<view class="apply_item">
					<view>详细地址<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input class="input" type="text" :placeholder="'请输入'+t('详细地址')" placeholder-style="font-size:28rpx;color:#BBBBBB" name="address" :value="address"></input></view>
				</view>
				<block v-if="!mendian_up">
					<view class="apply_item">
						<view>{{t('门店')}}简介</view>
						<view class="flex-y-center"><input type="text" name="subname"  :value="info.subname"  :placeholder="t('门店')+'简介'"></input> </view>
					</view>	
				</block>
				
				<input type="text" hidden="true" name="latitude" style="position: fixed;left: -200%;" :value="latitude"></input>
				<input type="text" hidden="true" name="longitude" style="position: fixed;left: -200%;" :value="longitude"></input>
			</view>
		
			<view class="apply_box" v-if="!mendian_up">
				<view class="apply_item">
					<view>设置登录账号<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="un" :value="info.un" placeholder="请填写登录账号" autocomplete="off"></input></view>
				</view>
				<view class="apply_item">
					<view>设置登录密码<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="password" name="pwd" :value="info.pwd" placeholder="请填写登录密码" autocomplete="off"></input></view>
				</view>
				<view class="apply_item">
					<view>确认密码<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="password" name="repwd"  placeholder="请再次填写密码"></input></view>
				</view>
			</view>
			
			
			<view style="padding:30rpx 0"><button v-if="!info.id || info.check_status==2" form-type="submit" class="set-btn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'">提交申请</button>
</view>


		</form>
		
	</block>
	
	<view class="popup__container" v-if="fwlistshow">
		<view class="popup__overlay" @tap.stop="changeClistDialog"></view>
		<view class="popup__modal">
			<view class="popup__title">
				<text class="popup__title-text">请选择服务类目</text>
				<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="changeClistDialog"/>
			</view>
			<view class="popup__content">
				<block v-for="(item, index) in fwclist" :key="item.id">
					<view class="clist-item" @tap="fwcidsChange" :data-id="item.id">
						<view class="flex1">{{item.name}}</view>
						<view class="radio" :style="inArray(item.id,fwcids) ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
					</view>
					<block v-for="(item2, index2) in item.child" :key="item2.id">
						<view class="clist-item" style="padding-left:80rpx" @tap="fwcidsChange" :data-id="item2.id">
							<view class="flex1" v-if="item.child.length-1==index2">└ {{item2.name}}</view>
							<view class="flex1" v-else>├ {{item2.name}}</view>
							<view class="radio" :style="inArray(item2.id,fwcids) ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
					</block>
				</block>
			</view>
		</view>
	</view>
	
	
	<gk-city :headtitle="headtitle"	:provincedata="provincedata"	:data="selfData"
	mode="cityPicker"	ref="cityPicker"	@funcvalue="getpickerParentValue"	:pickerSize="4"></gk-city>
	
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
</view>
</template>

<script>
var app = getApp();
//import provinceData from '@/common/city.data.min.js';
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
      cateArr: [],
      cindex: 0,
      isagree: false,
      showxieyi: false,
      info: [],
      latitude: '',
      longitude: '',
      address:'',
			xieyi_show:false,
			bid:'',
			isapply2:0,
			fwlistshow:false,
			fwcids:0,
			pic:[],
			items:[],
			pics:[],
			mendian:[],
			mendian_up:false,
			provincedata:[{	text:'请选择',	value:''}],
			addressByPcrs:"请选择所在地",
			headtitle:"请选择所在地",
			//selfData:provinceData,
			selfData:''
    };
  },

  onLoad: function (opt) {
		this.getdata();
		this.opt = app.getopts(opt);
		this.type = this.opt.type || 0;
		var that = this;

		app.get('ApiIndex/getCustom',{}, function (customs) {
			if(customs.data.includes('mendian_upgrade')) {
					var url = app.globalData.pre_url+'/static/city.data.min.js';
			}else{
					var url = app.globalData.pre_url+'/static/area.json';
			}
			uni.request({
				url: url,
				data: {},
				method: 'GET',
				header: { 'content-type': 'application/json' },
				success: function(res2) {
					that.items = res2.data
					//console.log(res2.data)
					that.selfData = res2.data
				}
			});
		});
		uni.setNavigationBarTitle({
			title:'申请'+that.t('门店')
		});
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiMendianup/apply', {}, function (res) {
				that.loading = false;
				if (res.status == 2) {
					//app.alert(res.msg, function () {
						app.goto(res.tourl, 'redirect');
					//})
					return;
				}
				that.info = res.info
				that.mendian_up = res.mendian_up
				if (res.info.province){
					var regiondata = res.info.province+ ',' + res.info.city+ ',' + res.info.district;
				 } else {
					var regiondata = '';
				 }
				 if(res.mendian_up && res.info.province){
					 	that.addressByPcrs = res.info.province+','+res.info.city+','+res.info.district+','+res.info.street
				 }else{
					 that.addressByPcrs ='请选择所在地';
				 }
				 that.regiondata = regiondata
				 that.latitude = res.info.latitude;
				 that.longitude = res.info.longitude;
				 
				 var pic = res.info ? res.info.pic : '';
				 if (pic) {
				 	pic = pic.split(',');
				 } else {
				 	pic = [];
				 }
				 that.pic = pic;

				 var pics = res.info ? res.info.pics : '';
				 if (pics) {
				 	pics = pics.split(',');
				 } else {
				 	pics = [];
				 }
				that.pics = pics;
				that.address = res.info.address
				uni.setNavigationBarTitle({
					title: res.title
				});
				that.loaded();
			});
		},

   selectzuobiao: function () {
   	console.log('selectzuobiao')
     var that = this;
     uni.chooseLocation({
       success: function (res) {
         console.log(res);
         that.area = res.address;
         that.address = res.name;
         that.latitude = res.latitude;
         that.longitude = res.longitude;
       },
       fail: function (res) {
   			console.log(res)
         if (res.errMsg == 'chooseLocation:fail auth deny') {
           //$.error('获取位置失败，请在设置中开启位置信息');
           app.confirm('获取位置失败，请在设置中开启位置信息', function () {
             uni.openSetting({});
           });
         }
       }
     });
   },
		regionchange(e) {
			const value = e.detail.value
			console.log(value[0].text + ',' + value[1].text + ',' + value[2].text);
			this.regiondata = value[0].text + ',' + value[1].text + ',' + value[2].text
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
    subform: function (e) {
      var that = this;
      var info = e.detail.value;
      if (info.realname == '') {
        app.error('请填写姓名');
        return false;
      }

      if (info.tel == '') {
        app.error('请填写电话');
        return false;
      }

      if (info.un == '') {
        app.error('请填写登录账号');
        return false;
      }
      if (!that.info.id &&  info.pwd == '') {
        app.error('请填写登录密码');
        return false;
      }
      var pwd = info.pwd;
      if (!that.info && pwd.length < 6) {
        app.error('密码不能小于6位');
        return false;
      }
      if (info.repwd != info.pwd) {
        app.error('两次输入密码不一致');
        return false;
      } //if(!/(^0?1[3|4|5|6|7|8|9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]$)/.test(tel)){
      //	dialog('手机号格式错误');return false;
      //}
			var regiondata = that.regiondata;
			if(that.mendian_up){
					info.citys = that.addressByPcrs;
			}else{
					info.citys = regiondata;
			}
      info.address = that.address;
      info.latitude = that.latitude;
      info.longitude = that.longitude;

    //  info.cid = that.clist[that.cindex].id;
      if (that.info && that.info.id) {
					info.id = that.info.id;
      }

			//console.log(info);return;
			app.showLoading('提交中');
      app.post("ApiMendianup/apply", {info: info}, function (res) {
				app.showLoading(false);
        app.error(res.msg);
				if(res.status == 1){
					setTimeout(function () {
						that.getdata()
						if(res.payorderid){
								app.goto('/pagesExt/pay/pay?id=' + res.payorderid);
						}else{
							  app.goto(res.tourl,'reLaunch');
						}
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
				if(field == 'headimg') that.headimg = pics;
				if(field == 'codepic') that.codepic = pics;
				if(field == 'otherpic') that.otherpic = pics;
			},1)
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
		toggleMaskLocation(){
			this.$nextTick(()=>{
				this.$refs["cityPicker"].show();
			})
		},
		getpickerParentValue(data){
			console.log(data.map(o=>{return o.text}));  //获取地址的value值
			this.provincedata=data;
			this.addressByPcrs=data.map(o=>{return o.text}).join(",")
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

.apply_item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee;align-items: center; }
.apply_item1{  display: flex;justify-content: space-between;}
.apply_box .apply_item:last-child{ border:none}
.apply_item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right;}
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