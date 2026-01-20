<template>
<view class="container" :style="bgset && bgset.bgcolor?'background-color:'+bgset.bgcolor:''">
	<block v-if="errmsg=='' && isload">
		<image :src="bgset && bgset.bgimg?bgset.bgimg:pre_url + '/static/img/lv-upbanner.png'" class="banner" mode="widthFix"></image>
		
		<view class="contentbox">
			<view class="title">欢迎加入{{sysset.name}}</view>
			<view class="title">您的当前{{bgset && bgset.level_name?bgset.level_name:'等级'}}：<text style="font-weight:bold">{{userlevel.name}}</text></view>
			<view class="title" v-if="mendian_member_levelup_fenhong && sysset.mdname">扫码门店：<text style="font-weight:bold">{{sysset.mdname}}</text></view>
		</view>
		<form @submit="formSubmit">
			<!-- 输入验证码升级 -->
			<view v-if="type =='ycode'">
					<view class="contentbox" >
						<view class="form-item1">
						 <view style="width: 100%;overflow: hidden;margin: 10rpx 0;">
								<input @input="inputycode"  placeholder="输入验证码" style="width: 300rpx;float:left;line-height: 60rpx;border: 2rpx solid #eee;height: 60rpx;padding-left: 20rpx;"/>
								<view @tap="getlevel" style="width: 140rpx;float:right;line-height: 60rpx;border: 2rpx solid #eee;height: 60rpx;background-color: red;text-align: center;color: #fff;">
										确认
								</view>
						 </view>
						</view>
					</view>
					<view class="contentbox" v-if="nolevel">
						<view class="noup">
								<text class="fa fa-check"></text> 
								<text>等级不存在或不符合此级别升级条件</text>
							</view>
					</view>
			</view>
			<view class="contentbox" v-else>
				<view class="form-item1" v-if="aglevelCount>0">
					 <view class="panel">请选择升级{{bgset && bgset.level_name?bgset.level_name:'等级'}}：</view>
					 <radio-group @change="changelevel" name="levelid">
						 <block v-for="(item, idx) in aglevelList" :key="idx">
								 <label class="radio-item">
										<view class="flex1"><text style="font-weight:bold">{{item.name}}</text></view>
										<!-- <text>{{item.apply_paymoney>0 ? '加盟费'+item.apply_paymoney+'元':''}}</text> -->
										<radio :value="item.id+''" v-if="item.id!=userlevel.id"></radio>
										<text class="curlevel_tag" v-if="item.id==userlevel.id">当前等级</text>
								 </label>
								 <view v-if="item.apply_code" style="width: 100%;overflow: hidden;margin: 10rpx 0;">
										<input @input="applycode" :data-index="idx" :data-id="item.id" placeholder="输入验证码" style="width: 300rpx;float:left;line-height: 60rpx;border: 2rpx solid #eee;height: 60rpx;padding-left: 20rpx;"/>
								 </view>
						 </block>
					 </radio-group>
				</view>
				<view v-else class="noup">
					<text class="fa fa-check"></text> 
					<block v-if="levelid>0">
							<text v-if="levelid ==userinfo.levelid">您已达到此级别</text>
							<text v-else>暂不符合此级别升级条件</text>
					</block>
					<block v-else>
							<text>您已达到最高可升级级别</text>
					</block>
				</view>
			</view>
			<!-- 申请升级 -->
			<view class="contentbox" v-if="!nolevel && selectedLevel.can_apply==1 && selectedLevel.id!=userinfo.levelid">
				<view class="applytj">
					<view class="f1">
						<text>{{selectedLevel.name}}</text>
						<text class="t2">申请条件：</text>
					</view>
					<view class="f2">
						<view class="t1" v-if="selectedLevel.applytj!=''">{{selectedLevel.applytj}}</view>
						<view class="t2" v-if="selectedLevel.apply_paymoney>0">{{selectedLevel.apply_paytxt}}：￥{{selectedLevel.apply_paymoney}}</view>
						<view class="t3" v-if="selectedLevel.applytj_reach==0">您暂未达到申请条件，请继续努力！</view>
						<view class="t4" v-if="selectedLevel.applytj_reach==1"><text v-if="selectedLevel.applytj!=''">您已达到申请条件，</text>请填写申请资料</view>
					</view>
				</view>
				<view class="applydata" v-if="selectedLevel.applytj_reach==1 || need_school==1">
					<view class="form-item" v-for="(item,idx) in selectedLevel.apply_formdata" :key="item.id">
						<view class="label">{{item.val1}}<text v-if="item.val3==1" style="color:red"> *</text></view>
						<block v-if="item.key=='input'">
							<input type="text" :name="'form'+idx" class="input" :placeholder="item.val2" @input="inputchange" placeholder-style="font-size:28rpx" :data-key="idx"/>
							<block v-if="item.val4 ==2 && item.val6 ==1">
								<view  style="display: flex;align-items: center;margin-top: 10rpx;">
									<view style="width: 180rpx">验证码</view>
									<input type="text" name="smscode" value="" placeholder="请输入验证码" placeholder-style="padding:0 10rpx" class="inputcode" />
									<text class="code1" @tap="smscode" :data-key="idx">获取验证码</text>
								</view>
							</block>
						</block>
						<block v-if="item.key=='textarea'">
							<textarea :name="'form'+idx" class='textarea' :placeholder="item.val2" placeholder-style="font-size:28rpx"/>
						</block>
						<block v-if="item.key=='radio'">
							<radio-group class="flex" :name="'form'+idx" style="flex-wrap:wrap">
								<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
										<radio class="radio" :value="item1"/>{{item1}}
								</label>
							</radio-group>
						</block>
						<block v-if="item.key=='checkbox'">
							<checkbox-group :name="'form'+idx" class="flex" style="flex-wrap:wrap">
								<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
									<checkbox class="checkbox" :value="item1"/>{{item1}}
								</label>
							</checkbox-group>
						</block>
						<block v-if="item.key=='selector'">
							<picker class="picker" mode="selector" :name="'form'+idx" value="" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx">
								<view v-if="editorFormdata[idx] || editorFormdata[idx]===0"> {{item.val2[editorFormdata[idx]]}}</view>
								<view v-else>请选择</view>
							</picker>
						</block>
						<block v-if="item.key=='time'">
							<picker class="picker" mode="time" :name="'form'+idx" value="" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx">
								<view v-if="editorFormdata[idx]">{{editorFormdata[idx]}}</view>
								<view v-else>请选择</view>
							</picker>
						</block>
						<block v-if="item.key=='date'">
							<picker class="picker" mode="date" :name="'form'+idx" value="" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx">
								<view v-if="editorFormdata[idx]">{{editorFormdata[idx]}}</view>
								<view v-else>请选择</view>
							</picker>
						</block>

						<block v-if="item.key=='region'">
							<uni-data-picker :localdata="items" popup-title="请选择省市区" @change="onchange" styleData="width:100%"></uni-data-picker>
							<!-- <picker class="picker" mode="region" :name="'form'+idx" value="" @change="editorBindPickerChange" :data-idx="idx">
								<view v-if="editorFormdata[idx]">{{editorFormdata[idx]}}</view> 
								<view v-else>请选择省市区</view>
							</picker> -->
							<input type="text" style="display:none" :name="'form'+idx" :value="regiondata"/>
						</block>
						<block v-if="item.key=='region2'">
							<view class="flex-y-center">
								<text class="cell-tip" @click="toggleMaskLocation">
									<text class="choose-text">{{addressByPcrs}}</text>
									<text class="iconfont icon-xiangxia"></text>
								</text>
							</view>
							<input type="text" style="display:none" :name="'form'+idx" :value="addressByPcrs"/>
						</block>
						<block v-if="item.key=='upload'">
							<input type="text" style="display:none" :name="'form'+idx" :value="editorFormdata[idx]"/>
							<view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
								<view class="form-imgbox" v-if="editorFormdata[idx]">
									<view class="layui-imgbox-close" style="z-index: 2;" @tap="removeimg" :data-idx="idx"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
									<view class="form-imgbox-img"><image class="image" :src="editorFormdata[idx]" @click="previewImage" :data-url="editorFormdata[idx]" mode="aspectFit" :data-idx="idx"/></view>
								</view>
								<view v-else class="form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 50rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="editorChooseImage" :data-idx="idx"></view>
							</view>
						</block>
						<block v-if="item.key=='zuobiao'">
							<view class="gradeitem">
								<view class="flex-y-center" @tap="selectzuobiao"><input type="text" :name="'form'+idx" placeholder="请选择坐标" :value="latitude ? latitude+','+longitude:''" ></input></view>
						</view>
						</block>
					</view>
					<view class="form-item" v-if="selectedLevel.areafenhong == 1">
						<view class="label">代理区域<text style="color:red"> *</text></view>
						<uni-data-picker :localdata="provincedata" popup-title="请选择代理区域" :placeholder="areafenhong_province || '请选择代理区域'" @change="onchange1" styleData="width:100%"></uni-data-picker>
					</view>
					<view class="form-item" v-if="selectedLevel.areafenhong == 2">
						<view class="label">代理区域<text style="color:red"> *</text></view>
						<uni-data-picker :localdata="citydata" popup-title="请选择代理区域" :placeholder="areafenhong_city ? areafenhong_province + '/' + areafenhong_city : '请选择代理区域'" @change="onchange2" styleData="width:100%"></uni-data-picker>
					</view>
					<view class="form-item" v-if="selectedLevel.areafenhong == 3">
						<view class="label">代理区域<text style="color:red"> *</text></view>
						<uni-data-picker :localdata="items" popup-title="请选择代理区域" :placeholder="areafenhong_area ? areafenhong_province + '/' + areafenhong_city + '/' + areafenhong_area : '请选择代理区域'" @change="onchange3" styleData="width:100%"></uni-data-picker>
					</view>
					<view class="form-item" v-if="selectedLevel.areafenhong == 10">
						<view class="label">代理区域<text style="color:red"> *</text></view>
						<picker class="picker" mode="selector" value="" :range="largearea" @change="largeareaBindPickerChange">
							<view v-if="largeareaindex!=-1">{{largearea[largeareaindex]}}</view>
							<view v-else>请选择</view>
						</picker>
					</view>
					<view class="form-item" v-if="need_school">
						<view class="label">班级年级<text style="color:red"> *</text></view>
						<view class="gradeitem">
							<picker class="picker" mode="selector" :value="gradeindex" name="grade" :range="gradelist" @change="gradeChange" range-key="name">
								<view :class="gradeindex>-1?'':'hui'"> {{gradeindex>-1?gradelist[gradeindex].name:'请选择年级'}}</view>
							</picker>
							<picker v-if="gradeindex>-1" class="picker" mode="selector" :value="classindex" name="grade" :range="classlist" @change="classChange" range-key="name">
								<view :class="classindex>-1?'':'hui'"> <text class="hui">/</text> {{classindex>-1?classlist[classindex].name:'请选择班级'}}</view>
							</picker>
						</view>
					</view>
				</view>
				<view v-if="selectedLevel.is_agree==1" class="xycss1" style="text-align: center;">
					<checkbox-group @change="isagreeChange" style="display: inline-block;">
							<checkbox style="transform: scale(0.6)"  value="1" :checked="isagree"/>
							<text style="color: #000;">请先阅读并同意</text>
					</checkbox-group>
					<text @tap="showxieyiFun" style="color:red">《升级协议》</text>
				</view>
				<button class="form-btn" form-type="submit" v-if="selectedLevel.applytj_reach==1">申请成为{{selectedLevel.name}}</button>
			</view>
		</form>

		<view class="contentbox" v-if="!nolevel && selectedLevel.can_up==1 && selectedLevel.up_condition_show == 1 && selectedLevel.id!=userinfo.levelid">
			<view class="uplvtj">
				<view class="f1">
					<text>{{selectedLevel.name}}</text>
					<text class="t2">升级条件：</text>
				</view>
				<view v-if="changeState" class="f2">
					<parse :content="selectedLevel.autouptj" />
					<view class="t3">您达到升级条件后将自动升级为{{selectedLevel.name}}，请继续努力！</view>
				</view>
			</view>
		</view>
		
		<view class="contentbox">
			<view class="explain">
				<view class="f1">
					<text>{{selectedLevel.name}}</text>
					<text class="t2">{{bgset && bgset.level_name?bgset.level_name:'等级'}}特权：</text>
				</view>
				<view class="f2">
					<parse :content="userlevel.explain" v-if="userlevel.id==selectedLevel.id"/>
					<block v-for="(item,index) in aglevelList">
					<parse :content="item.explain" v-if="item.id==selectedLevel.id"/>
					</block>
				</view>
			</view>
		</view>
	</block>

	<view v-if="showxieyi" class="xieyibox">
		<view class="xieyibox-content">
			<view style="overflow:scroll;height:100%;">
				<parse :content="selectedLevel.agree_content" @navigate="navigate"></parse>
			</view>
			<view style="position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;text-align:center; width: 50%;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap="hidexieyi">已阅读并同意</view>
		</view>
	</view>
	<view style="display:none">{{test}}</view>
	<block v-if="errmsg!='' && isload">
	<view class="zan-box">
			<view class="zan-text">{{errmsg}}</view>
	</view>
	</block>
	
	<gk-city :headtitle="headtitle"	:provincedata="provincedata2"	:data="selfData"
	mode="cityPicker"	ref="cityPicker"	@funcvalue="getpickerParentValue"	:pickerSize="4"></gk-city>
	
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
</view>
</template>

<script>
var app = getApp();
export default {
  data() {
    return {
        opt:{},
        levelid:0,
        loading:false,
        isload: false,
        menuindex:-1,

        pre_url:app.globalData.pre_url,
        editorFormdata:[],
        regiondata:'',
        items: [],
        provincedata:[],
        citydata:[],
        test:'test',

        sysset: [],
        userinfo: [],
        aglevelList: [],
        aglevelCount: 0,
        applytj_reach: 0,
        errmsg: '',
        userlevel: "",
        selectedLevel: "",
        explain: "",
        applytj_info: "",
        autouptj_info: "",
        areafenhong_province:'',
        areafenhong_city:'',
        areafenhong_area:'',
        largeareaindex:-1,
        largearea:[],
        changeState: true,
        levelupcode:false,
        bgset:'',
        type:0,
        ycode:'',
        nolevel:false,
				need_school:0,
				school_id:0,
				gradelist:[],
				gradeindex:-1,
				classindex:-1,
				classlist:[],
				showxieyi:false,
				isagree:false,
				mdid:0,//门店id
        mendian_member_levelup_fenhong:false,
				latitude: '',
				longitude: '',
				mendian:[],
				provincedata2:[{	text:'请选择',	value:''}],
				addressByPcrs:"请选择所在地",
				headtitle:"请选择所在地",
				selfData:''
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt && this.opt.levelid){
				this.levelid = this.opt.levelid;
		}
		if(this.opt && this.opt.type){
				this.type = this.opt.type;
		}
		if(this.opt && this.opt.mdid){
			this.mdid = this.opt.mdid;
		}
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
					var provincedata = [];
					for(var i in res2.data){
						provincedata.push({text:res2.data[i].text,value:res2.data[i].value})
					}
					that.provincedata = provincedata;
					var citydata = [];
					for(var i in res2.data){
						var citys = [];
						for(var j in res2.data[i].children){
							citys.push({text:res2.data[i].children[j].text,value:res2.data[i].children[j].value});
						}
						citydata.push({text:res2.data[i].text,value:res2.data[i].value,children:citys});
					}
					that.citydata = citydata;
				}
			});
			if(customs.data.includes('member_level_add_apply_mendian')) {
				var url = app.globalData.pre_url+'/static/city.data.min.js';
				uni.request({
					url: url,
					data: {},
					method: 'GET',
					header: { 'content-type': 'application/json' },
					success: function(res) {
						that.selfData = res.data
					}
				});
			}
		});
		
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata(true);
	},
  onPullDownRefresh: function () {
    this.getdata(true);
  },
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiMy/levelup', {id:that.opt.id,cid:that.opt.cid,levelid:that.levelid,mdid:that.mdid}, function (res) {
				that.loading = false;
				uni.setNavigationBarTitle({
					title: that.t('会员') + '升级'
				});
				if (res.status == 0) {
					that.errmsg = res.msg;
				} else if (res.status == 2) {
					that.errmsg = res.msg;
					setTimeout(function () {
						app.goto('index');
					}, 1000);
				} else {
					that.userinfo = res.userinfo;
					that.bankname = res.userinfo.bankname;
					that.userlevel = res.userlevel;
					that.selectedLevel = res.userlevel;
					that.sysset = res.sysset;
					that.aglevelList = res.aglevelList;
					that.aglevelCount = res.aglevelList.length;
					that.explain = res.userlevel.explain;
					that.mendian_member_levelup_fenhong = res.sysset.mendian_member_levelup_fenhong;
					if(res.levelupcode){
						that.levelupcode = res.levelupcode;
					}
          if(res.bgset){
          	if(res.bgset.title){
          		uni.setNavigationBarTitle({
          			title: res.bgset.title
          		});
          	}
          	that.bgset = res.bgset;
          }
					if(res.largearea) that.largearea = res.largearea;
				}
				that.loaded();
			});
		},
    cannotapply: function () {
      app.alert('不满足申请条件');
    },
    bindBanknameChange: function (e) {
      this.bankname = this.banklist[e.detail.value];
    },
		gradeChange:function(e){
			var that = this;
			that.gradeindex = e.detail.value;
			that.classindex = -1;
			that.classlist = that.gradelist[that.gradeindex].classlist;
		},
		classChange:function(e){
			var that = this;
			that.classindex = e.detail.value;
		},
    formSubmit: function (e) {
			var that = this;
			var apply_formdata = this.selectedLevel.apply_formdata;
			var formdata = e.detail.value;
			for (var i = 0; i < apply_formdata.length;i++){
				//console.log(formdata['form' + i]);
				if (apply_formdata[i].val3 == 1 && (formdata['form' + i] === '' || formdata['form' + i] === undefined || formdata['form' + i].length==0)){
					app.alert(apply_formdata[i].val1+' 必填');return;
				}
				if (apply_formdata[i].key == 'selector') {
					formdata['form' + i] = apply_formdata[i].val2[formdata['form' + i]]
				}
				if(apply_formdata[i].key =='input' && apply_formdata[i].val4 ==2){
						formdata['tel'] = formdata['form' + i];
				}
			}
			var set_is_agree = this.selectedLevel.is_agree || 0;
			if(set_is_agree && !that.isagree){
				app.alert('请先阅读并同意《升级协议》');return;
			}
			if(this.selectedLevel.areafenhong==1 && this.areafenhong_province==''){
					app.alert('请选择代理区域');return;
			}
			if(this.selectedLevel.areafenhong==2 && this.areafenhong_city==''){
					app.alert('请选择代理区域');return;
			}
			if(this.selectedLevel.areafenhong==3 && this.areafenhong_area==''){
					app.alert('请选择代理区域');return;
			}
			if(this.selectedLevel.areafenhong==10 && this.largeareaindex==-1){
					app.alert('请选择代理区域');return;
			}
			if (formdata.levelid == '') {
					app.alert('请选择等级');
					return;
			}
			if(that.need_school){
				if(that.classindex<0){
					app.alert('请选择年级班级信息');
					return;
				}
				formdata.school_id = that.school_id
				formdata.grade_id = that.gradelist[that.gradeindex].id
				formdata.class_id = that.classlist[that.classindex].id
			}
			formdata.areafenhong_province = this.areafenhong_province;
			formdata.areafenhong_city = this.areafenhong_city;
			formdata.areafenhong_area = this.areafenhong_area;
			if(this.selectedLevel.areafenhong==10){
					formdata.areafenhong_largearea = this.largearea[this.largeareaindex];
			}
			if(that.levelupcode){
					formdata.code = '';
					if(that.type == 'ycode'){
							var aglevelList = that.aglevelList;
							formdata.levelid = aglevelList[0]['id'];
							formdata.code    = that.ycode;
					}else{
							var aglevelList = that.aglevelList;
							var len         = aglevelList.length;
							if(len>0){
									for(var i=0;i<len;i++){
											if(aglevelList[i]['id'] == formdata.levelid){
													formdata.code = aglevelList[i]['applycode'];
											}
									}
							}
					}
			}
			if(that.mendian_member_levelup_fenhong && that.sysset.mdid){
				formdata.mdid = that.sysset.mdid
			}
			app.showLoading('提交中');
			app.post('ApiMy/levelup', formdata, function (res) {
					app.showLoading(false);
					if (res.status == 0) {
						app.alert(res.msg);
						return;
					}
					app.success(res.msg);
					setTimeout(function () {
						app.goto(res.url);
					}, 1000);
			});
    },
    changelevel: function (e) {
		this.changeState = false;
        var levelid = e.detail.value;
        var aglevelList = this.aglevelList;
        var agleveldata;

        for (var i in aglevelList) {
            if (aglevelList[i].id == levelid) {
                agleveldata = aglevelList[i];
                break;
            }
        }
        var applytj = [];
        var applytj_reach = 0;
        var member = this.userinfo;

        // var applytj_info = applytj.join(' 或 ');
        var autouptj = [];

        // var autouptj_info = autouptj.join(' 或 ');
        // this.applytj_info = applytj_info;
        // this.applytj_reach = applytj_reach;
        // this.autouptj_info = autouptj_info;
        this.selectedLevel = agleveldata;
        this.explain = agleveldata.explain;
        this.editorFormdata = [];
        this.changeState = true;
        this.test = Math.random();
    },
    editorChooseImage: function (e) {
        var that = this;
        var idx = e.currentTarget.dataset.idx;
        var tplindex = e.currentTarget.dataset.tplindex;
        var editorFormdata = this.editorFormdata;
        if(!editorFormdata) editorFormdata = [];
        app.chooseImage(function(data){
            editorFormdata[idx] = data[0];
            console.log(editorFormdata)
            that.editorFormdata = editorFormdata
            that.test = Math.random();
        })
    },
		removeimg:function(e){
			var that = this;
			var idx = e.currentTarget.dataset.idx;
			var pics = that.editorFormdata
			pics.splice(idx,1);
			that.editorFormdata = pics;
		},
    editorBindPickerChange:function(e){
        var idx = e.currentTarget.dataset.idx;
        var tplindex = e.currentTarget.dataset.tplindex;
        var val = e.detail.value;
        var editorFormdata = this.editorFormdata;
        if(!editorFormdata) editorFormdata = [];
        editorFormdata[idx] = val;
        console.log(editorFormdata)
        this.editorFormdata = editorFormdata
        this.test = Math.random();
    },
    onchange(e) {
        const value = e.detail.value
        console.log(value[0].text + ',' + value[1].text + ',' + value[2].text)
        this.regiondata = value[0].text + ',' + value[1].text + ',' + value[2].text
    },
    onchange1(e) {
        const value = e.detail.value
        console.log(value[0].text)
        this.areafenhong_province = value[0].text;
    },
    onchange2(e) {
        const value = e.detail.value
        console.log(value[0].text + ',' + value[1].text)
        this.areafenhong_province = value[0].text;
        this.areafenhong_city = value[1].text
    },
    onchange3(e) {
        const value = e.detail.value
        this.areafenhong_province = value[0].text;
        this.areafenhong_city = value[1].text;
        this.areafenhong_area = value[2].text;
    },
    largeareaBindPickerChange:function(e){
        console.log(e.detail.value);
        this.largeareaindex = e.detail.value
    },
    applycode:function(e){
        var that = this;
        var aglevelList = that.aglevelList;

        var index = e.currentTarget.dataset.index;
        var val   = e.detail.value;
        var len   = aglevelList.length;
        
        if(len>0){
            for(var i=0;i<len;i++){
                if(i == index){
                    aglevelList[i]['applycode'] = val;
                }
            }
        }
        that.aglevelList=aglevelList;
    },
    inputycode:function(e){
        var that = this;
        var ycode  = e.detail.value;
        that.ycode = ycode;
    },
    getlevel: function () {
    	var that = this;

        var ycode = that.ycode;
        if(!ycode){
            app.alert('请填写验证码');
            return;
        }

    	that.loading = true;
    	app.get('ApiMy/levelup', {id:0,cid:0,levelid:0,ycode:that.ycode}, function (res) {
    		that.loading = false;
    		if (res.status == 0) {
    			that.errmsg = res.msg;
    		} else if (res.status == 2) {
    			that.errmsg = res.msg;
    			setTimeout(function () {
    				app.goto('index');
    			}, 1000);
    		}else{
    			that.aglevelList  = res.aglevelList;
    			that.aglevelCount = res.aglevelList.length;
					if(res.aglevelList.length<=0){
							that.nolevel = true;
							var userlevel      = that.userlevel;
							that.selectedLevel = that.userlevel;
							that.explain       = userlevel.explain;
					}else{
							that.nolevel = false;
							var data = {
									'detail':{
											'value':res.aglevelList[0]['id']
									}
							}
							that.changelevel(data);
					}
					//班级资料
					if(res.need_school && res.need_school==1){
						that.need_school = res.need_school
						that.school_id = res.school_id
						that.gradelist = res.gradelist
					}
    		}
    		that.loaded();
    	});
    },
	isagreeChange: function (e) {
		var val = e.detail.value;
		if (val.length > 0) {
			this.isagree = true;
		} else {
			this.isagree = false;
		}
		console.log(this.isagree);
	},
	showxieyiFun: function () {
		this.showxieyi = true;
	},
	hidexieyi: function () {
		this.showxieyi = false;
		this.isagree = true;
	},
	smscode: function (e) {
	  var that = this;
	  if (that.hqing == 1) return;
	  that.hqing = 1;
	  var tel = '';
	  var apply_formdata = this.selectedLevel.apply_formdata;
	  var index = e.currentTarget.dataset.key;
	  tel = apply_formdata[index].value;

	  if (tel == '') {
	    app.alert('请输入手机号码');
	    that.hqing = 0;
	    return false;
	  }
	  if (!app.isPhone(tel)) {
	    app.alert("手机号码有误，请重填");
	    that.hqing = 0;
	    return false;
	  }
	  app.post("ApiIndex/sendsms", {tel: tel}, function (data) {
	    if (data.status != 1) {
	      app.alert(data.msg);return;
	    }
	  });
	  var time = 120;
	  var interval1 = setInterval(function () {
	    time--;
	    if (time < 0) {
	      that.smsdjs = '重新获取';
	      that.hqing = 0;
	      clearInterval(interval1);
	    } else if (time >= 0) {
	      that.smsdjs = time + '秒';
	    }
	  }, 1000);
	},
	inputchange(e){
		var value = e.detail.value;
		var index = e.currentTarget.dataset.key;
		var apply_formdata= this.selectedLevel.apply_formdata;
		apply_formdata[index].value = value;
		this.selectedLevel.apply_formdata = apply_formdata;
	},
	selectzuobiao: function (e) {
	  var that = this;
		var index = e.currentTarget.dataset.key;

	  uni.chooseLocation({
	    success: function (res) {
	      console.log(res);
	      that.area = res.address;
	      that.address = res.name;
	      that.latitude = res.latitude;
	      that.longitude = res.longitude;
				
				var apply_formdata= this.selectedLevel.apply_formdata;
				apply_formdata[index].value = that.latitude+','+that.longitude;
				this.selectedLevel.apply_formdata = apply_formdata;
				
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
	toggleMaskLocation(){
		this.$nextTick(()=>{
			this.$refs["cityPicker"].show();
		})
	},
	getpickerParentValue(data){
		console.log(data.map(o=>{return o.text}));  //获取地址的value值
		this.provincedata2=data;
		this.addressByPcrs=data.map(o=>{return o.text}).join(",")
		console.log(this.addressByPcrs);
	},
  }
};
</script>
<style>
page{background:#F2D8B2;width: 100%;height: 100%;}
.container{width: 100%;height:auto;min-height: 100%;padding-bottom:20rpx;}
.banner{ width:100%;background:#fff;height:400rpx;display:table}

.contentbox{width:94%;margin: 0 3%;padding:20rpx 40rpx;border-radius:20rpx;background:#fff;color:#B17D2D;display:flex;flex-direction:column;margin-top:10px}
.title{height:50rpx;line-height:50rpx}

.user-level {margin-left:10rpx;display:flex;}
.user-level image {width: 44rpx;height: 44rpx;margin-right: 10rpx;margin-left: -4rpx;}
.level-name {height: 36rpx;border-radius: 18rpx;font-size: 24rpx;color: #fff;background-color: #5c5652;padding: 0 16rpx 0 0;display:flex;align-items: flex-end;}
.level-name .name{display:flex;align-items:center;height:100%}

.noup{ width:100%;text-align:center;font-size:32rpx;color:green}

.form-item1{width: 100%;display:flex;flex-direction:column;color:#333}
.form-item1 .panel{width: 100%;font-size:32rpx;color:#B17D2D;}
.form-item1 radio-group{width: 100%;background:#fff;padding-left:10rpx;}
.form-item1 .radio-item{display:flex;width:100%;color:#000;align-items: center;background:#fff;padding:12rpx 0;}
.form-item1 .radio-item:last-child{border:0}
.radio-item .user-level{flex:1}
.form-item1 radio{ transform: scale(0.8);}

.applytj{width:100%;}
.applytj .f1{color:#000;font-size:30rpx;height:60rpx;line-height:60rpx;font-size:30rpx;padding-left:20rpx;display:flex;align-items:center}
.applytj .f1 .t2{padding-left:10rpx}
.applytj .f2{padding:20rpx;background-color:#fff;color:#f56060}
.applytj .f2 .t2{padding-top:10rpx;color:#88e}
.applytj .f2 .t3{padding-top:10rpx}
.applytj .f2 .t4{padding-top:10rpx;color:green;font-size:30rpx}
.uplvtj{width:100%;margin-top:20rpx;}
.uplvtj .f1{color:#000;font-size:30rpx;height:60rpx;line-height:60rpx;font-size:30rpx;padding-left:20rpx;display:flex;align-items:center}
.uplvtj .f1 .t2{padding-left:10rpx}
.uplvtj .f2{padding:20rpx;background-color:#fff;color:#f56060}
.uplvtj .f2 .t3{padding-top:10rpx;color:green}

.explain{ width:100%;margin:20rpx 0;}
.explain .f1{color:#000;font-size:30rpx;height:60rpx;line-height:60rpx;font-size:30rpx;padding-left:20rpx;display:flex;align-items:center}
.explain .f1 .t2{padding-left:10rpx}
.explain .f2{padding:20rpx;background-color:#fff;color:#999999}


.applydata{width: 100%;background: #fff;padding: 0 20rpx;color:#333}

.form-btn{width:100%;height: 88rpx;line-height: 88rpx;border-radius:8rpx;background: #FC4343;color: #fff;margin-top: 40rpx;margin-bottom: 20rpx;}

.applydata .radio{transform:scale(.7);}
.applydata .checkbox{transform:scale(.7);}
.form-item{width: 100%;border-bottom: 1px #ededed solid;padding:10rpx 0px;display:flex;align-items: center;flex-wrap: wrap;}
.form-item:last-child{border:0}
.form-item .label{height:70rpx;line-height: 70rpx;width:160rpx;margin-right: 10px;flex-shrink:0}
.form-item .input{height: 70rpx;line-height: 70rpx;overflow: hidden;flex:1;border:1px solid #eee;padding:0 8rpx;border-radius:2px;}
.form-item .textarea{height:180rpx;line-height:40rpx;overflow: hidden;flex:1;border:1px solid #eee;border-radius:2px;padding:8rpx}
.form-item .radio{height: 70rpx;line-height: 70rpx;display:flex;align-items:center}
.form-item .radio2{display:flex;align-items:center;}
.form-item .radio .myradio{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:50%}
.form-item .checkbox{height: 70rpx;line-height: 70rpx;display:flex;align-items:center}
.form-item .checkbox2{display:flex;align-items:center;height: 40rpx;line-height: 40rpx;}
.form-item .checkbox .mycheckbox{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:2px}
.form-item .layui-form-switch{}
.form-item .picker{height: 70rpx;line-height:70rpx;flex:1;}

.form-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.form-imgbox-close{position: absolute;display: block;width:32rpx;height:32rpx;right:-16rpx;top:-16rpx;color:#999;font-size:32rpx;background:#fff}
.form-imgbox-close .image{width:100%;height:100%}
.form-imgbox-img{display: block;width:180rpx;height:180rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.form-imgbox-img>.image{width: 100%;height: 100%;}
.form-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.form-uploadbtn{position:relative;height:180rpx;width:180rpx;margin-right: 16rpx;margin-bottom:10rpx;}

.apply_code{float: right;width: 100rpx;line-height: 60rpx;border-radius: 60rpx;text-align: center;color: #fff;}
.curlevel_tag{color: #b4b4b4;font-size: 24rpx;}
.gradeitem{display: flex;justify-content: flex-start;align-items: center;flex: 1;}
.form-item .gradeitem .picker{height: 70rpx;line-height:70rpx;flex: unset;}
.hui{color: #BBBBBB;}
.xycss1{line-height: 60rpx;font-size: 24rpx;overflow: hidden;margin-top: 20rpx;}
.xieyibox{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)}
.xieyibox-content{width:90%;margin:0 auto;height:80%;margin-top:20%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px}
.inputcode{width:235rpx;height:70rpx;line-height: 70rpx;display: inline-block;background: none;border: 1px solid #eee}
.code1{height: 70rpx;font-size: 24rpx;line-height: 70rpx;background: #FC4343;color: #fff;text-align: center;width: 150rpx;margin-left: 20rpx;}

</style>