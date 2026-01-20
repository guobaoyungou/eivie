<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<view class="apply_box">
				<view class="apply_item">
					<view>姓名<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="realname" :value="info.realname" placeholder="请填写姓名"></input></view>
				</view>
				<view class="apply_item">
					<view>电话<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="tel" :value="info.tel" placeholder="请填写手机号码"></input></view>
				</view>
			</view>
			
			<view class="apply_box">
				<view class="apply_item">
					<view>加入商家<text style="color:red"> *</text></view>
					<view>	
						<picker @change="busChange" :value="bindex" :range="blist" range-key="name">
							<view class="picker">{{bname}}</view>
						</picker>
					</view>
				</view>
				<view class="apply_item">
					<view class="f1">服务类目<text style="color:red"> *</text></view>
					<view  style="display: flex;align-items: center;"  @tap="changeClistDialog">
						<text v-if="fwcids.length>0">{{fwcnames}}</text><text v-else style="color:#888">请选择</text><image src="/static/img/arrowright.png" style="width:30rpx;height:30rpx"/>
					</view>
				</view>
				
				<view class="apply_item">
					<view>所属类型<text style="color:red"> *</text></view>
					<view>
						<picker @change="cateChange" :value="cindex" :range="cateArr">
							<view class="picker">{{cateArr[cindex]}}</view>
						</picker>
					</view>
				</view>
				<view class="apply_item">
						<text class="label">年龄</text>
						<view class="flex-y-center"><input type="number" name="age" :value="info.age" placeholder="请填写年龄"></input></view>
				</view>
				<view class="apply_item">
						<text class="label">性别</text>
						<view class="flex-y-center">
							<radio-group class="radio-group" name="sex">
							<label class="radio">
								<radio value="1" :checked="info.sex==1?true:false" ></radio>男
							</label>
							<label class="radio">
								<radio value="2"  :checked="info.sex==2?true:false" ></radio>女
							</label>
							</radio-group>
						</view>	
				</view>
				<view class="apply_item">
					<view>服务城市<text style="color:red"> *</text></view>
					<view class="flex-y-center">
								<uni-data-picker :localdata="items" :border="false" :placeholder="regiondata || '请选择省市区'" @change="regionchange"></uni-data-picker>
					</view>
				</view>
				<view class="apply_item">
					<view>定位坐标<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" disabled placeholder="请选择坐标" :value="latitude ? latitude+','+longitude:''" @tap="locationSelect"></input></view>
				</view>
				<view class="apply_item">
					<view>服务公里数<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="fuwu_juli"  :value="info.fuwu_juli"  placeholder="请输入服务公里数"></input> &nbsp;KM</view>
				</view>
				
				<view class="apply_item">
					<view>个人简介</view>
					<view class="flex-y-center"><input type="text" name="desc"  :value="info.desc"  placeholder="个人简介"></input> </view>
				</view>
				
				<input type="text" hidden="true" name="latitude" :value="latitude"></input>
				<input type="text" hidden="true" name="longitude" :value="longitude"></input>
			</view>
			<view class="apply_box">
				<view class="apply_item" style="border-bottom:0"><view>工作照片<text style="color:red"> *</text></view></view>
				<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
					<view v-for="(item, index) in headimg" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="headimg"><image src="/static/img/ico-del.png"></image></view>
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
					</view>
					<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="headimg" v-if="headimg.length==0"></view>
				</view>
				<input type="text" hidden="true" name="headimg" :value="headimg.join(',')" maxlength="-1"></input>
			</view>
			<view class="apply_box">
				<view class="apply_item" style="border-bottom:0"><view>身份证正反面<text style="color:red"> *</text></view></view>
				<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
					<view v-for="(item, index) in codepic" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="codepic"><image src="/static/img/ico-del.png"></image></view>
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
					</view>
					<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="codepic" v-if="codepic.length<2"></view>
				</view>
				<input type="text" hidden="true" name="codepic" :value="codepic.join(',')" maxlength="-1"></input>
			</view>
			<view class="apply_box">
				<view class="apply_item" style="border-bottom:0"><text>其他证件<text style="color:red"> </text> (上传资格证书和健康证，没有可以不填）</text></view>
				<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
					<view v-for="(item, index) in otherpic" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="otherpic"><image src="/static/img/ico-del.png"></image></view>
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
					</view>
					<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="otherpic"></view>
				</view>
				<input type="text" hidden="true" name="otherpic" :value="otherpic.join(',')" maxlength="-1"></input>
			</view>
			<view class="apply_box">
				<view class="apply_item">
					<view>登录账号<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="un" :value="info.un" placeholder="请填写登录账号" autocomplete="off"></input></view>
				</view>
			</view>


		
			<view style="padding:30rpx 0"><button v-if="info.id" form-type="submit" class="set-btn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'">修改资料</button>
</view>

		</form>
		
		
	</block>
	
	<view class="popup__container" v-if="fwlistshow">
		<view class="popup__overlay" @tap.stop="changeClistDialog"></view>
		<view class="popup__modal">
			<view class="popup__title">
				<text class="popup__title-text">请选择服务类目</text>
				<image src="/static/img/close.png" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="changeClistDialog"/>
			</view>
			<view class="popup__content">
				<block v-for="(item, index) in fwclist" :key="item.id">
					<view class="clist-item" @tap="fwcidsChange" :data-id="item.id">
						<view class="flex1">{{item.name}}</view>
						<view class="radio" :style="inArray(item.id,fwcids) ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" src="/static/img/checkd.png"/></view>
					</view>
					<block v-for="(item2, index2) in item.child" :key="item2.id">
						<view class="clist-item" style="padding-left:80rpx" @tap="fwcidsChange" :data-id="item2.id">
							<view class="flex1" v-if="item.child.length-1==index2">└ {{item2.name}}</view>
							<view class="flex1" v-else>├ {{item2.name}}</view>
							<view class="radio" :style="inArray(item2.id,fwcids) ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" src="/static/img/checkd.png"/></view>
						</view>
					</block>
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
      cateArr: [],
      cindex: 0,
      isagree: false,
      showxieyi: false,
			codepic:[],
			otherpic:[],
			headimg:[],
      info: {},
			set:{},
      latitude: '',
      longitude: '',
      address:'',
			items:[],
			xieyi_show:false,
			bindex:'0',
			bname:'请选择加入商家',
			fwlistshow:false,
			fwcids:[],
			fwcateArr:[],
			fwcnames:'',
			
    };
  },

  onLoad: function (opt) {
		this.getdata();
		this.opt = app.getopts(opt);
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
			app.get('ApiYuyueWorker/sets', {}, function (res) {
				that.loading = false;
				var clist = res.clist;
				var cateArr = [];
				for (var i in clist) {
					cateArr.push(clist[i].name);
				}
				var codepic = res.info ? res.info.codepic : '';
				if (codepic) {
					codepic = codepic.split(',');
				} else {
					codepic = [];
				}
				var otherpic = res.info ? res.info.otherpic : '';
				if (otherpic) {
					otherpic = otherpic.split(',');
				} else {
					otherpic = [];
				}
				var headimg = res.info ? res.info.headimg : '';
				if (headimg) {
					headimg = headimg.split(',');
				} else {
					headimg = [];
				}
				that.clist = res.clist
				that.blist = res.blist
				that.info = res.info
				/* if (res.info.province){
					var regiondata = res.info.province+ ',' + res.info.city+ ',' + res.info.district;
				 } else {
					var regiondata = '';
				 }*/
				that.regiondata = res.info.citys
        that.latitude = res.info.latitude;
        that.longitude = res.info.longitude;
				that.cateArr = cateArr;
				that.codepic = codepic;
				that.otherpic = otherpic;
				that.headimg = headimg;
				that.order = res.order
				that.fwcateArr = res.fwcateArr
				that.fwcids = res.fwcids;
				that.fwclist = res.fwclist
				if(res.info) {
					if(res.info.bid==0){
							that.bname = '平台自营';
					}else{
						that.bname = res.busarr[res.info.bid].name
					}
				}
				that.bid = res.info.bid
				that.getcnames();
				that.loaded();
			});
		},
		changeClistDialog:function(){
			var that =this
			if(that.bid===''){
					app.error('请先选择加入商家');return;
			}
			this.fwlistshow = !this.fwlistshow
		},
    cateChange: function (e) {
      this.cindex = e.detail.value;
    },
		busChange: function (e) {
			var that=this
		  var bindex = e.detail.value;
			that.bname = that.blist[bindex].name
			if(that.bid===''){
					that.bid = that.blist[bindex].id
			}
			app.get('ApiYuyue/classify', {bid:that.bid}, function (res) {
				if(res.status==1){
					var fwclist = res.data
					that.fwclist = fwclist;
				}else{
						app.error('获取失败');return;
				}
			})
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
			
			if(that.bid===''){
				app.error('请选择加入商家');
				return false;
			}
			info.bid = that.bid
			
			if(!that.fwcids){
				app.error('请选择服务类目');
				return false;
			}
			info.fwcids = that.fwcids
		  var regiondata = that.regiondata;
		  if(regiondata == '') {
				app.error('请选择服务城市');
				return;
			}
			info.citys = regiondata;
      if (info.zuobiao == '') {
        //app.error('请选择店铺坐标');
        //return false;
      }
      if (info.fuwu_juli == '') {
        app.error('请填写服务公里数');
        return false;
      }
      if (info.headimg == '') {
       // app.error('请上传工作照');
       // return false;
      }
			if (info.codepic == '') {
			 // app.error('请上传身份证正反面');
			 // return false;
			}
      if (info.other_pic== '') {//$.error('请上传证明材料');return false;
      }
     //if(!/(^0?1[3|4|5|6|7|8|9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]$)/.test(tel)){
      //	dialog('手机号格式错误');return false;
      //}
      info.address = that.address;
      info.latitude = that.latitude;
      info.longitude = that.longitude;
      info.cid = that.clist[that.cindex].id;
      if (that.info && that.info.id) {
        info.id = that.info.id;
      }
			//console.log(info);return;
			app.showLoading('提交中');
      app.post("ApiYuyueWorker/sets", {info: info}, function (res) {
				app.showLoading(false);
				if(res.status == 1){
					app.success(res.msg);
					//app.goto('/activity/yuyue/my', 'redirect');
				}else{
					app.error(res.msg);
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
			if(field == 'headimg'){
				var pics = that.headimg
				pics.splice(index,1);
				that.headimg = pics;
			}else if(field == 'codepic'){
				var pics = that.codepic
				pics.splice(index,1);
				that.codepic = pics;
			}else if(field == 'otherpic'){
				var pics = that.otherpic
				pics.splice(index,1);
				that.otherpic = pics;
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