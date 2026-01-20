<template>
<view>
	<block v-if="isload">
		<view style="color:red;padding:10rpx 30rpx;margin-top:20rpx" v-if="info.id && info.shstatus==2">审核不通过：{{info.reason}}，请修改后再提交</view>
		<view style="color:red;padding:10rpx 30rpx;margin-top:20rpx" v-if="info.id && info.shstatus==0 && info.apply_paymoney>0 && order.status==0">您已提交资料，待支付</view>
		<view style="color:red;padding:10rpx 30rpx;margin-top:20rpx" v-else-if="info.id && info.shstatus==0 && info.apply_paymoney>0 && order.status==1">您已提交申请，请等待审核</view>
		<view style="color:red;padding:10rpx 30rpx;margin-top:20rpx" v-else-if="info.id && info.shstatus==0 && info.apply_paymoney==0">您已提交申请，请等待审核</view>
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
				<block v-if="!isapply2">
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
							<text v-if="fwcids.length>0">{{fwcnames}}</text><text v-else style="color:#888">请选择</text><image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/>
						</view>
					</view>
				</block>
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
				<block v-if="!hide_city">
					<view class="apply_item">
						<view>服务城市<text style="color:red"> *</text></view>
						<view class="flex-y-center">
									<uni-data-picker :localdata="items" :border="false" :placeholder="regiondata || '请选择省市区'" @change="regionchange"></uni-data-picker>
						</view>
					</view>
					<block v-if="!isapply2">
						<view class="apply_item">
							<view>定位坐标<text style="color:red"> *</text></view>
							<view class="flex-y-center" @tap="locationSelect"><input type="text" disabled placeholder="请选择坐标" :value="latitude ? latitude+','+longitude:''" ></input></view>
						</view>
						<view class="apply_item">
							<view>服务公里数<text style="color:red"> *</text></view>
							<view class="flex-y-center"><input type="text" name="fuwu_juli"  :value="info.fuwu_juli"  placeholder="请输入服务公里数"></input> &nbsp;KM</view>
						</view>
					</block>
				</block>
				<view class="apply_item">
					<view>个人简介</view>
					<view class="flex-y-center"><input type="text" name="desc"  :value="info.desc"  placeholder="个人简介"></input> </view>
				</view>
				
				<input type="text" hidden="true" name="latitude" style="position: fixed;left: -200%;" :value="latitude"></input>
				<input type="text" hidden="true" name="longitude" style="position: fixed;left: -200%;" :value="longitude"></input>
			</view>

				<view class="apply_box">
					<view class="apply_item" style="border-bottom:0"><view>工作照片<text style="color:red"> *</text></view></view>
					<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
						<view v-for="(item, index) in headimg" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="headimg"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
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
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="codepic"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="codepic" v-if="codepic.length<2"></view>
					</view>
					<input type="text" hidden="true" name="codepic" :value="codepic.join(',')" maxlength="-1"></input>
				</view>
				<view class="apply_box">
					<view class="apply_item" style="border-bottom:0"><text>其他证件<text style="color:red"> </text> (上传资格证书和健康证）</text></view>
					<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
						<view v-for="(item, index) in otherpic" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="otherpic"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="otherpic"></view>
					</view>
					<input type="text" hidden="true" name="otherpic" :value="otherpic.join(',')" maxlength="-1"></input>
				</view>

			<view class="apply_box">
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
					<view class="flex-y-center"><input type="password" name="repwd" :value="info.repwd" placeholder="请再次填写密码"></input></view>
				</view>
				<view class="apply_item" v-if="set.apply_paymoney>0">{{isapply2?'保证金费用':'缴纳金额'}}：<view style="color: red;">￥<text style="font-weight:bold;font-size: 36rpx;color: red;">{{set.apply_paymoney}}</text></view></view>
			</view>


			<block v-if="set.xieyi_show==1">
			<view class="flex-y-center" style="margin-left:20rpx;color:#999" v-if="!info.id || info.shstatus==2">
				<checkbox-group @change="isagreeChange"><label class="flex-y-center"><checkbox value="1" :checked="isagree"></checkbox>阅读并同意</label></checkbox-group>
				<text :style="'color:'+t('color1')" @tap="showxieyiFun">《入驻协议》</text>
			</view>
			</block>
			<view style="padding:30rpx 0"><button v-if="!info.id || info.shstatus==2" form-type="submit" class="set-btn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'">提交申请</button>
</view>
			<view style="padding:30rpx 0"><button v-if="info.id && info.status==0 && info.apply_paymoney>0 && order.status==0 && info.shstatus==0"  class="set-btn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" @tap="goto" :data-url="'/pagesExt/pay/pay?id=' + order.payorderid">立即支付</button>
</view>

		</form>
		
		<view id="xieyi" :hidden="!showxieyi" style="width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)">
			<view style="width:90%;margin:0 auto;height:85%;margin-top:10%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px">
				<view style="overflow:scroll;height:100%;">
					<parse :content="set.xieyi"/>
				</view>
				<view style="position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;text-align:center" @tap="hidexieyi">已阅读并同意</view>
			</view>
		</view>
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
      info: [],
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
			blist:[],
			order:[],
			isapply2:false,
			bid:'',
			hide_city:false
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
			app.get('ApiYuyue/apply', {}, function (res) {
				that.loading = false;
				if (res.status == 2) {
					app.alert(res.msg, function () {
						app.goto('/yuyue/yuyue/my', 'redirect');
					})
					return;
				}
				uni.setNavigationBarTitle({
					title: res.title
				});
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
				that.set = res.set
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
				that.hide_city = res.hide_city
				if(res.info.id){
					if(res.info.bid==0){
							that.bname = '平台自营';
					}else{
						that.bname = res.busarr[res.info.bid].name
					}
					that.bid = res.info.bid
				}
				that.isapply2 = res.isapply2
				that.getcnames();
				that.loaded();
			});
		},
		changeClistDialog:function(){
			var that =this
			if(!that.bid){
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
			that.bid = that.blist[bindex].id
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

			if(!that.isapply2){
				if(that.bid===''){
					app.error('请选择加入商家');
					return false;
				}
				info.bid = that.bid
					console.log(that.fwcids.length);
				if(that.fwcids.length==0){
					app.error('请选择服务类目');
					return false;
				}
				info.fwcids = that.fwcids
				var regiondata = that.regiondata;
				if(!that.hide_city){
					if(regiondata == '' || regiondata==undefined) {
						app.error('请选择服务城市');
						return;
					}
					info.citys = regiondata;
					if (info.latitude == '' || info.latitude==undefined ) {
						app.error('请选择店铺坐标');
						return false;
					}
					if (info.fuwu_juli == '') {
						app.error('请填写服务公里数');
						return false;
					}	
				}
				if (info.headimg == '') {
					app.error('请上传工作照');
					return false;
				}
				if (info.codepic == '') {
					app.error('请上传身份证正反面');
					return false;
				}
				if (info.other_pic== '') {//$.error('请上传证明材料');return false;
				}
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
      if (!that.info.id && pwd.length < 6) {
        app.error('密码不能小于6位');
        return false;
      }
      if (info.repwd != info.pwd) {
        app.error('两次输入密码不一致');
        return false;
      } //if(!/(^0?1[3|4|5|6|7|8|9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]$)/.test(tel)){
      //	dialog('手机号格式错误');return false;
      //}
      info.address = that.address;
      info.latitude = that.latitude;
      info.longitude = that.longitude;
      if (that.set.xieyi_show == 1 && !that.isagree) {
        app.error('请先阅读并同意入驻协议');
        return false;
      }
      info.cid = that.clist[that.cindex].id;
      if (that.info && that.info.id) {
        info.id = that.info.id;
      }
			
			
			//console.log(info);return;
			app.showLoading('提交中');
      app.post("ApiYuyue/apply", {info: info}, function (res) {
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