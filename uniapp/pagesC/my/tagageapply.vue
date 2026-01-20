<template>
<view class="container" :style="bgset && bgset.bgcolor?'background-color:'+bgset.bgcolor:''">
	<block v-if="errmsg=='' && isload">
		<form @submit="formSubmit">
			<view class="contentbox" v-if="aglevelCount>0 && !tagid">
				<view class="form-item1" >
					 <view class="panel">请选择要申请的：</view>
					 <radio-group @change="changelevel" name="tagid">
						 <block v-for="(item, idx) in aglevelList" :key="idx">
								 <label class="radio-item">
										<view class="flex1"><text style="font-weight:bold">{{item.name}}</text></view>
										<radio :value="item.id+''" v-if="item.id!=userlevel.id"></radio>
								 </label>
						 </block>
					 </radio-group>
				</view>
			</view>
			<!-- 申请升级 -->
			<view class="contentbox" v-if="selectedLevel">
				<view class="applytj">
					<view class="f1">
						<text>{{selectedLevel.name}}</text>
					</view>
					<view class="f2">
						<view class="t4" v-if="bh_info" style="color: red;font-size: 26rpx;margin-bottom: 20rpx">{{bh_info}}</view>
						<view class="t4" v-if="selectedLevel.apply_formdata">请填写申请资料</view>
					</view>
				</view>
				<view class="applydata" >
					<view class="form-item" v-for="(item,idx) in selectedLevel.apply_formdata" :key="item.id">
						<view class="label">{{item.val1}}<text v-if="item.val3==1" style="color:red"> *</text></view>
						<block v-if="item.key=='input'">
							<input type="text" :name="'form'+idx" class="input" :placeholder="item.val2" @input="inputchange" placeholder-style="font-size:28rpx" :data-key="idx"/>
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
							<input type="text" style="display:none" :name="'form'+idx" :value="regiondata"/>
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
					</view>
				</view>
				<button class="form-btn" form-type="submit" >申 请</button>
			</view>
		</form>
    <nodata v-if="nodata" :text="nots"></nodata>
    <nomore v-if="nomore"></nomore>
	</block>

	<view style="display:none">{{test}}</view>
	<block v-if="errmsg!='' && isload">
	<view class="zan-box">
			<view class="zan-text">{{errmsg}}</view>
	</view>
	</block>
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
        nodata: false,
        nomore: false,
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
        bh_info: "",
        nots: "没有可申请的会员标签",
        tagid: 0,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);

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
    });

		this.getdata();
  },
  methods: {
		getdata: function () {
			var that = this;
      that.tagid = that.opt.tagid || 0
			that.loading = true;
			app.get('ApiMy/tagageapply', {tagid:that.tagid}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					that.errmsg = res.msg;
				} else if (res.status == 2) {
					that.errmsg = res.msg;
					setTimeout(function () {
						app.goto('index');
					}, 1000);
				} else {
					that.userinfo = res.userinfo;
					that.sysset = res.sysset;
					that.aglevelList = res.aglevelList;
					that.aglevelCount = res.aglevelList.length;
					that.bh_info = res.bh_info;
          if(res.msg){
            that.nots = res.msg || '';
          }
          if(that.aglevelCount <= 0){
            that.nodata = true;
          }
          if(res.sqname){
            uni.setNavigationBarTitle({
              title: res.sqname || '会员标签申请'
            });
          }
          if(that.tagid){
            that.selectedLevel = that.aglevelList[0];
          }
				}
				that.loaded();
			});
		},
    formSubmit: function (e) {
			var that = this;
			var apply_formdata = this.selectedLevel.apply_formdata;
			var formdata = e.detail.value;
      if(apply_formdata){
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
      }
      if(that.tagid){
        formdata.tagid = that.tagid;
      }

			app.showLoading('提交中');
			app.post('ApiMy/tagageapply', formdata, function (res) {
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

    this.selectedLevel = agleveldata;
    this.bh_info = agleveldata.bh_info;
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

    inputchange(e){
      var value = e.detail.value;
      var index = e.currentTarget.dataset.key;
      var apply_formdata= this.selectedLevel.apply_formdata;
      apply_formdata[index].value = value;
      this.selectedLevel.apply_formdata = apply_formdata;
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