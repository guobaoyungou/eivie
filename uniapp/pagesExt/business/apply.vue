<template>
<view>
	<block v-if="isload">
		<view style="color:red;padding:10rpx 30rpx;margin-top:20rpx" v-if="info.id && info.status==2">审核不通过：{{info.reason}}，请修改后再提交</view>
		<view style="color:red;padding:10rpx 30rpx;margin-top:20rpx" v-if="info.id && info.status==0">您已提交申请，请等待审核</view>
		<form @submit="subform">
			<view class="apply_box">
				<view class="apply_item">
					<view>联系人姓名<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="linkman" :value="info.linkman" placeholder="请填写姓名"></input></view>
				</view>
				<view class="apply_item">
					<view>联系人电话<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="linktel" :value="info.linktel" placeholder="请填写手机号码"></input></view>
				</view>
				<view class="apply_item" v-if="bset.pid_input_status &&  bset.pid_input_status==1">
					<view>推荐人ID<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="business_pid" v-model="business_pid" placeholder="请填写推荐人ID"></input></view>
				</view>
			</view>
			<view class="apply_box">
				<view class="apply_item">
					<view>商家名称<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="name" :value="info.name" placeholder="请输入商家名称"></input></view>
				</view>
				<view class="apply_item">
					<view>商家描述<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="desc" :value="info.desc" placeholder="请输入商家描述"></input></view>
				</view>
				<view class="apply_item">
					<view>主营类目<text style="color:red"> *</text></view>
					<view>
						<picker @change="cateChange" :value="cindex" :range="cateArr">
							<view class="picker">{{cateArr[cindex]}}</view>
						</picker>
					</view>
				</view>
				<view class="apply_item">
					<view>店铺坐标<text style="color:red"> *</text></view>
					<view class="flex-y-center" @tap="locationSelect" ><input type="text" readonly placeholder="请选择坐标" name="zuobiao" :value="latitude ? latitude+','+longitude:''" ></input></view>
				</view>
				<view class="apply_item">
					<view>店铺地址<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="address" :value="address" placeholder="请输入商家详细地址"></input></view>
				</view>
				<input type="text" hidden="true" style="position: fixed;left: -200%;"  name="latitude" :value="latitude"></input>
				<input type="text" hidden="true" style="position: fixed;left: -200%;" name="longitude" :value="longitude"></input>
				<view class="apply_item">
					<view>客服电话<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="tel" :value="info.tel" placeholder="请填写客服电话"></input></view>
				</view>
				<view class="apply_item" v-if="queue_free_set.show_rate_back ==1">
					<view>{{t('排队免单')}}{{t('返利比例')}}(%)</view>
					<view class="flex-y-center"><input type="text" name="rate_back" :value="queue_free_set.rate_back" placeholder="请填写比例"></input></view>
				</view>
				<view v-if="active_coin==1">
					<view class="apply_item">
						<view>让利比例(%)</view>
						<view class="flex-y-center"><input type="text" name="activecoin_ratio" :value="info.activecoin_ratio" placeholder="请填写比例"></input></view>
					</view>

          <view v-if="showBusinessActivecoinRatio" class="apply_item">
            <view>让利到消费者比例(%)</view>
            <view class="flex-y-center"><input type="text" name="business_activecoin_ratio" :value="info.business_activecoin_ratio" placeholder="请填写比例"></input></view>
          </view>
          <view v-if="showMemberActivecoinRatio" class="apply_item">
            <view>让利到商家比例(%)</view>
            <view class="flex-y-center"><input type="text" name="member_activecoin_ratio" :value="info.member_activecoin_ratio" placeholder="请填写比例"></input></view>
          </view>		

				</view>
				<view v-if="new_score==1">
					<view class="apply_item">
						<view>{{t('新积分')}}让利比例(%)</view>
						<view class="flex-y-center"><input type="text" name="newscore_ratio" :value="info.newscore_ratio" placeholder="请填写比例"></input></view>
					</view>
					<view v-if="showMemberNewscoreRatio" class="apply_item">
					  <view>{{t('新积分')}}让利到消费者(%)</view>
					  <view class="flex-y-center"><input type="text" name="member_newscore_ratio" :value="info.member_newscore_ratio" placeholder="请填写比例"></input></view>
					</view>		
					<view v-if="showBusinessNewscoreRatio" class="apply_item">
					  <view>{{t('新积分')}}让利到商家(%)</view>
					  <view class="flex-y-center"><input type="text" name="business_newscore_ratio" :value="info.business_newscore_ratio" placeholder="请填写比例"></input></view>
					</view>
				</view>	
				<view v-if="showSubsidyRatio" class="apply_item">
				  <view>让利比例(%)</view>
				  <view class="flex-y-center"><input type="text" name="feepercent" :value="info.feepercent" placeholder="请填写比例"></input></view>
				</view>
				<view class="apply_item" style="line-height:50rpx"><textarea name="content" placeholder="请输入商家简介" :value="info.content"></textarea></view>
			</view>
			<view class="apply_box">
				<view class="apply_item" style="border-bottom:0"><view>商家logo<text style="color:red"> *</text></view></view>
				<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
					<view v-for="(item, index) in pic" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pic"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
					</view>
					<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pic" v-if="pic.length==0"></view>
				</view>
				<input type="text" hidden="true" name="pic" :value="pic.join(',')" maxlength="-1"></input>
			</view>
			<view class="apply_box">
				<view class="apply_item" style="border-bottom:0"><view>商家照片({{min_bpic_num}}-{{max_bpic_num}}张)<text style="color:red"> *</text></view></view>
				<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
					<view v-for="(item, index) in pics" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pics"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
					</view>
					<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pics" v-if="pics.length< max_bpic_num"></view>
				</view>
				<input type="text" hidden="true" name="pics" :value="pics.join(',')" maxlength="-1"></input>
			</view>
      
      <block v-if="!bset.zhengmingtype">
        <view class="apply_box" v-if="!bset.nearby">
          <view class="apply_item" style="border-bottom:0"><view>证明材料<text style="color:red"> </text></view></view>
          <view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
            <view v-for="(item, index) in zhengming" :key="index" class="layui-imgbox">
              <view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="zhengming"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
              <view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
            </view>
            <view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="zhengming"></view>
          </view>
          <input type="text" hidden="true" name="zhengming" :value="zhengming.join(',')" maxlength="-1"></input>
        </view>
      </block>
      <block v-if="bset.zhengmingtype">
        <view class="apply_box">
          <view class="apply_item" style="border-bottom:0"><view>证明材料<text style="color:red"> </text></view></view>
          <view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
            <view v-for="(item,idx) in formfields.content"  :key="idx" style="marigin-bottom:20rpx;">
              <view v-if="item.showtype && item.showtype==1" style="margin-bottom:20rpx;margin-right: 8rpx;width: 200rpx;">
                <input type="text" style="display:none" :name="'form'+idx" :value="editorFormdata[idx]"/>
                <view>
                  <view v-if="editorFormdata[idx]" class="layui-imgbox">
                    <view class="layui-imgbox-close" @tap="removeimgzdy" :data-idx="idx" :data-formidx="'form'+idx"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
                    <view class="layui-imgbox-img"><image :src="editorFormdata[idx]" @click="previewImage" :data-url="editorFormdata[idx]" mode="widthFix" :data-idx="idx"></image></view>
                  </view>
                  <view v-else class="uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="editorChooseImage" :data-idx="idx" :data-formidx="'form'+idx"></view>
                </view>
                <view style="color:#B2B5BE">{{item.val1?item.val1:item.val2}}</view>
              </view>
            </view>
          </view>
        </view>
      </block>

			<!-- 自定义注册S -->
			<block v-if="show_custom_field">
			  <view class="apply_box custom_field" v-if="show_custom_field">
          <block v-for="(item,idx) in formfields.content"  :key="idx">
            <view v-if="!item.showtype" class="dp-form-item" >
              <view >{{item.val1}}<text v-if="item.val3==1" style="color:red"> *</text></view>
              <view class="flex-y-center">
                <block v-if="item.key=='input' || item.key=='realname' || item.key=='usercard'">
                  <text v-if="item.val5" style="margin-right:10rpx">{{item.val5}}</text>
                  <input :type="item.input_type" :name="'form'+idx" class="input" :placeholder="item.val2" placeholder-style="font-size:30rpx;color:#B2B5BE" :value="editorFormdata[idx]" @input="setfield" :data-formidx="'form'+idx"/>
                </block>
                <block v-if="item.key=='textarea'">
                  <textarea :name="'form'+idx" class='textarea' :placeholder="item.val2" placeholder-style="font-size:30rpx;color:#B2B5BE"  :value="editorFormdata[idx]" @input="setfield" :data-formidx="'form'+idx"/>
                </block>
                <block v-if="item.key=='radio' || item.key=='sex'">
                  <radio-group class="flex" :name="'form'+idx" style="flex-wrap:wrap" @change="setfield" :data-formidx="'form'+idx">
                    <label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
                        <radio class="radio" :value="item1" style="transform: scale(0.8);" :checked="editorFormdata[idx] && editorFormdata[idx]==item1 ? true : false"/>{{item1}}
                    </label>
                  </radio-group>
                </block>
                <block v-if="item.key=='checkbox'">
                  <checkbox-group :name="'form'+idx" class="flex" style="flex-wrap:wrap" @change="setfield" :data-formidx="'form'+idx">
                    <label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
                      <checkbox class="checkbox" style="transform: scale(0.8);" :value="item1" :checked="editorFormdata[idx] && inArray(item1,editorFormdata[idx]) ? true : false"/>{{item1}}
                    </label>
                  </checkbox-group>
                </block>
                <block v-if="item.key=='selector'">
                  <picker class="picker" mode="selector" :name="'form'+idx" :value="editorFormdata[idx]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx" :data-formidx="'form'+idx">
                    <view v-if="editorFormdata[idx] || editorFormdata[idx]===0"> {{item.val2[editorFormdata[idx]]}}</view>
                <!-- <view v-if="editorFormdata[idx]">{{editorFormdata[idx]}}</view> -->
                    <view v-else style="color: #b2b5be;">请选择</view>
                  </picker>
                </block>
                <block v-if="item.key=='time'">
                  <picker class="picker" mode="time" :name="'form'+idx" :value="editorFormdata[idx]"  :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx" :data-formidx="'form'+idx">
                    <view v-if="editorFormdata[idx]">{{editorFormdata[idx]}}</view>
                    <view v-else style="color: #b2b5be;">请选择</view>
                  </picker>
                </block>
                <block v-if="item.key=='date' || item.key=='birthday'">
                  <picker class="picker" mode="date" :name="'form'+idx" :value="editorFormdata[idx]" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx" :data-formidx="'form'+idx">
                    <view v-if="editorFormdata[idx]">{{editorFormdata[idx]}}</view>
                    <view v-else style="color: #b2b5be;">请选择</view>
                  </picker>
                </block>
              
                <block v-if="item.key=='region'">
                    <uni-data-picker :localdata="items" popup-title="请选择省市区" :placeholder="editorFormdata[idx] || '请选择省市区'" @change="onchange" :data-formidx="'form'+idx"></uni-data-picker>
                    <input type="text" style="display:none" :name="'form'+idx" :value="regiondata ? regiondata : editorFormdata[idx]"/>
                </block>
                <block v-if="item.key=='upload'">
                  <input type="text" style="display:none" :name="'form'+idx" :value="editorFormdata[idx]"/>
                  <view class="flex" style="flex-wrap:wrap;">
                    <view class="dp-form-imgbox" v-if="editorFormdata[idx]">
                      <view class="dp-form-imgbox-close" @tap="removeimgzdy" :data-idx="idx" :data-formidx="'form'+idx"><image :src="pre_url+'/static/img/ico-del.png'" class="image"></image></view>
                      <view class="dp-form-imgbox-img"><image class="image" :src="editorFormdata[idx]" @click="previewImage" :data-url="editorFormdata[idx]" mode="widthFix" :data-idx="idx"/></view>
                    </view>
                    <view v-else class="dp-form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="editorChooseImage" :data-idx="idx" :data-formidx="'form'+idx"></view>
                  </view>
                </block>
                <!-- #ifdef H5 || MP-WEIXIN -->
                <block v-if="item.key=='upload_file'">
                <input type="text" style="display:none" :name="'form'+idx" :value="editorFormdata[idx]"/>
                <view class="flex" style="flex-wrap:wrap;">
                  <view class="dp-form-imgbox" v-if="editorFormdata[idx]">
                    <view class="dp-form-imgbox-close" @tap="removeimgzdy" :data-idx="idx" :data-formidx="'form'+idx"><image :src="pre_url+'/static/img/ico-del.png'" class="image"></image></view>
                    <view class="dp-form-imgbox-img">已上传</view>
                  </view>
                  <view v-else class="dp-form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="editorChooseFile" :data-idx="idx" :data-formidx="'form'+idx"></view>
                </view>
                </block>
                <!-- #endif -->
              </view>
            </view>
          </block>
			  </view>
			  <view style="display:none">{{test}}</view>
			</block>
			<!-- 自定义注册E -->
			
			<view v-if="showunpwd && showunpwd == 1" class="apply_box">
				<view>
					<view class="apply_item" style="border: none;">
						<view>登录账号<text style="color:red"> *</text></view>
						<view class="flex-y-center" >
							<input type="text" name="un" :value="info.un" placeholder="请设置登录账号" autocomplete="off"></input>
						</view>
					</view>
					<view style="border-bottom: 1px solid #eee;color: #999;margin-top: -20rpx;padding-bottom: 10rpx;font-size: 24rpx;" >
						<text v-if="bset.nearby">如需开通商城功能请联系后台</text>
					</view>
				</view>
				
				<view class="apply_item">
					<view>登录密码<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="password" name="pwd" :value="info.pwd" placeholder="请设置登录密码" autocomplete="off"></input></view>
				</view>
				<view class="apply_item">
					<view>确认密码<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="password" name="repwd" :value="info.repwd" placeholder="请再次设置登录密码"></input></view>
				</view>
			</view>
			
			<block v-if="bset.xieyi_show==1">
			<view class="flex-y-center" style="margin-left:20rpx;color:#999" v-if="!info.id || info.status==2">
				<checkbox-group @change="isagreeChange"><label class="flex-y-center"><checkbox value="1" :checked="isagree"></checkbox>阅读并同意</label></checkbox-group>
				<text style="color:#666" @tap="showxieyiFun">《商户入驻协议》</text>
			</view>
			</block>
			<view style="padding:30rpx 0">
				<button v-if="!info.id || info.status==2" form-type="submit" class="set-btn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'">
					<text v-if="bset.deposit && bset.deposit > 0" style="margin-right: 20rpx;">{{t('入驻保证金')}}{{bset.deposit}}</text>提交申请
				</button>
			</view>
		</form>
		
		<view id="xieyi" :hidden="!showxieyi" style="width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)">
			<view style="width:90%;margin:0 auto;height:85%;margin-top:10%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px">
				<view style="overflow:scroll;height:100%;">
					<parse :content="bset.xieyi"/>
				</view>
				<view style="position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;text-align:center" @tap="hidexieyi">已阅读并同意</view>
			</view>
		</view>
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
      datalist: [],
      pagenum: 1,
      cateArr: [],
      cindex: 0,
      isagree: false,
      showxieyi: false,
			pic:[],
			pics:[],
			zhengming:[],
      info: {},
			bset:{},
      latitude: '',
      longitude: '',
      address:'',
      min_bpic_num:3,
      max_bpic_num:5,
      queue_free_set:[],
      //自定义表单Start
      has_custom:0,
      show_custom_field:false,
      regiondata:'',
      editorFormdata:{},
      test:'',
      formfields:[],
      custom_formdata:[],
      items: [],
      formvaldata:{},
      submitDisabled:false,
      register_forms:[],
      //自定义表单End
      active_coin:0,
      showBusinessActivecoinRatio:false,// 让利到消费者 是否显示
      showMemberActivecoinRatio:false,//让利到商家比例 是否显示
      showunpwd:0,//账号密码显示 0:隐藏 1：显示;
	  showSubsidyRatio:0,//消费补贴买单让利 0隐藏 1显示
	  new_score:0,//新积分
	  showBusinessNewscoreRatio:false,// 新积分让利到商家 是否显示
	  showMemberNewscoreRatio:false,//新积分让利到消费者 是否显示
      business_pid:'', //推荐人ID
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
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiBusiness/apply', {}, function (res) {
				that.loading = false;
				if (res.status == 2) {
					app.alert(res.msg, function () {
						app.goto('/admin/index/index', 'redirect');
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
				var pics = res.info ? res.info.pics : '';
				if (pics) {
					pics = pics.split(',');
				} else {
					pics = [];
				}
				var zhengming = res.info ? res.info.zhengming : '';
				if (zhengming) {
					zhengming = zhengming.split(',');
				} else {
					zhengming = [];
				}
				//自定义表单
				if(res.has_custom){
					if(res.register_forms){
						that.editorFormdata = res.register_forms;
						that.formvaldata = res.formvaldata;
					}
					that.formfields = res.custom_form_field;
					that.has_custom = res.has_custom
					that.show_custom_field = true
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
				//自定义表单end
				that.clist = res.clist
				that.bset = res.bset
				if(that.bset.nearby){
					that.min_bpic_num =6;
					that.max_bpic_num =8;
				}
				that.info = res.info
        that.address = res.info.address;
        that.latitude = res.info.latitude;
        that.longitude = res.info.longitude;
				that.cateArr = cateArr;
				that.pic = res.info.pic ? [res.info.pic] : [];
				that.pics = pics;
				that.zhengming = zhengming;
				that.active_coin = res.active_coin;
				that.queue_free_set = res.queue_free_set;
        that.showBusinessActivecoinRatio = res.showBusinessActivecoinRatio || false;// 让利到消费者 是否显示
        that.showMemberActivecoinRatio = res.showMemberActivecoinRatio || false;//让利到商家比例 是否显示
        that.showunpwd = res.showunpwd;
		that.showSubsidyRatio = res.showSubsidyRatio || 0;//消费补贴让利
		that.new_score = res.new_score || 0;//新积分
		that.showBusinessNewscoreRatio = res.showBusinessNewscoreRatio || false;// 新积分让利到消费者 是否显示
		that.showMemberNewscoreRatio = res.showMemberNewscoreRatio || false;//新积分让利到商家比例 是否显示
        if (that.bset && that.bset.pid_input_status == 1 && that.opt.pid) {
          that.business_pid = that.opt.pid;
        }
        if(that.info && that.info.business_pid){
          that.business_pid = that.info.business_pid;
        }
				that.loaded();
			});
		},
    cateChange: function (e) {
      this.cindex = e.detail.value;
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
    subform: function (e) {
      var that = this;
      var info = e.detail.value;
      if (info.linkman == '') {
        app.error('请填写联系人姓名');
        return false;
      }
      if (info.linktel == '') {
        app.error('请填写联系人手机号');
        return false;
      }
	  if(!app.isPhone(info.linktel)){
		  return app.error('请填写正确的手机号');
	  }
	  
      if (info.tel == '') {
        app.error('请填写客服电话');
        return false;
      }
	  if(!app.isPhone(info.tel) && !app.isPhone(info.tel, 2) && !app.isPhone(info.tel, 3)){
		  return app.error('请填写正确的客服电话');
	  }
      if (info.name == '') {
        app.error('请填写商家名称');
        return false;
      }
      if (info.zuobiao == '') {
        //app.error('请选择店铺坐标');
        //return false;
      }
      if (info.address == '') {
        app.error('请填写店铺地址');
        return false;
      }
      if (info.pic == '') {
        app.error('请上传商家logo');
        return false;
      }
      if (info.pics == '') {
        app.error('请上传商家照片');
        return false;
      }
      if (info.zhengming == '') {//$.error('请上传证明材料');return false;
      }
      if(that.showunpwd && that.showunpwd == 1){
        if (info.un == '') {
          app.error('请填写登录账号');
          return false;
        }
        if (info.pwd == '') {
          app.error('请填写登录密码');
          return false;
        }
        var pwd = info.pwd;
        if (pwd.length < 6) {
          app.error('密码不能小于6位');
          return false;
        }
        if (info.repwd != info.pwd) {
          app.error('两次输入密码不一致');
          return false;
        }
      }
      //if(!/(^0?1[3|4|5|6|7|8|9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]$)/.test(tel)){
      //	dialog('手机号格式错误');return false;
      //}
      //info.address = that.address;
      if(that.bset.pid_input_status && that.bset.pid_input_status ==1 && that.business_pid ==''){
        app.error('请填写推荐人ID');
        return false;
      }
      if(that.business_pid){
        info.business_pid = that.business_pid;
      }
      info.latitude = that.latitude;
      info.longitude = that.longitude;
      if (that.bset.xieyi_show == 1 && !that.isagree) {
        app.error('请先阅读并同意商户入驻协议');
        return false;
      }
      info.cid = that.clist[that.cindex].id;
      if (that.info && that.info.id) {
        info.id = that.info.id;
      }
	  //如果有自定义表单则验证表单内容
	  if(that.show_custom_field){
	  	var customformdata = {};
	  	var customData = that.checkCustomFormFields();
	  	if(!customData){
	  		return;
	  	}
	  	info.customformdata = customData
	  	info.customformid = that.formfields.id
	  }
	  //自定义表单end
			if(that.bset.referrer_independent && that.bset.referrer_independent ==1){
				info.pid = that.opt.pid || app.globalData.pid;
			}
			app.showLoading('提交中');
      app.post("ApiBusiness/apply", {info: info}, function (res) {
				app.showLoading(false);
				if(that.bset.deposit && that.bset.deposit > 0){
					app.error(res.msg);
					if(that.info.status ==2){
						setTimeout(function () {
							that.getdata();
						}, 1000);
					}
				}else{
					app.error(res.msg);
					if(res.status == 1){
						setTimeout(function () {
							app.goto(app.globalData.indexurl);
						}, 1000);
					}
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
				if(field == 'pic') that.pic = pics;
				if(field == 'pics') that.pics = pics;
				if(field == 'zhengming') that.zhengming = pics;
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
			}else if(field == 'zhengming'){
				var pics = that.zhengming
				pics.splice(index,1);
				that.zhengming = pics;
			}
		},
		//自定义表单
		onchange(e) {
		  const value = e.detail.value
			this.regiondata = value[0].text + ',' + value[1].text + ',' + value[2].text;
		},
		setfield:function(e){
			var field = e.currentTarget.dataset.formidx;
			var value = e.detail.value;
			this.formvaldata[field] = value;
		},
		editorBindPickerChange:function(e){
			var that = this;
			var idx = e.currentTarget.dataset.idx;
			var val = e.detail.value;
			var editorFormdata = this.editorFormdata;
			
			if(!editorFormdata) editorFormdata = {};
			editorFormdata[idx] = val;
			that.editorFormdata = editorFormdata
			this.test = Math.random();
			var field = e.currentTarget.dataset.formidx;
			this.formvaldata[field] = val;
		},
		checkCustomFormFields:function(e){
			var that = this;
			var subdata = this.formvaldata;
			var formcontent = that.formfields.content;
			var formid = that.formfields.id;
			var formdata = {};
			for (var i = 0; i < formcontent.length;i++){
				console.log(subdata['form' + i]);
				var value = subdata['form' + i];
				if (formcontent[i].key == 'region') {
					if(that.regiondata){
						value = that.regiondata;
					}						
				}
				if (formcontent[i].val3 == 1 && (subdata['form' + i] === '' || subdata['form' + i] === null || subdata['form' + i] === undefined || subdata['form' + i].length==0)){
						app.alert(formcontent[i].val1+' 必填');return false;
				}
				if (formcontent[i].key =='switch'){
						if (subdata['form' + i]==false){
								value = '否'
						}else{
								value = '是'
						}
				}
				if (formcontent[i].key == 'selector') {
					if(formcontent[i].val2[subdata['form' + i]]){
						value = formcontent[i].val2[subdata['form' + i]]
					}
						
				}
				if (formcontent[i].key == 'usercard' && subdata['form' + i]!='') {
					if(!app.isIdCard(subdata['form' + i])){
						app.alert(formcontent[i].val1+' 格式错误');return false;
					}
				}
				if (formcontent[i].key == 'input' && formcontent[i].val4 && subdata['form' + i]!==''){
					if(formcontent[i].val4 == '2'){ //手机号
						if (!app.isPhone(subdata['form' + i])) {
							app.alert(formcontent[i].val1+' 格式错误');return false;
						}
					}
					if(formcontent[i].val4 == '3'){ //身份证号
						if (!app.isIdCard(subdata['form' + i])) {
							app.alert(formcontent[i].val1+' 格式错误');return false;
						}
					}
					if(formcontent[i].val4 == '4'){ //邮箱
						if (!/^(.+)@(.+)$/.test(subdata['form' + i])) {
							app.alert(formcontent[i].val1+' 格式错误');return false;
						}
					}
				}
				formdata['form' + i] = value;
			}
			return formdata;
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
		
				var field = e.currentTarget.dataset.formidx;
				that.formvaldata[field] = data[0];
		
			})
		},
		editorChooseFile: function (e) {
			var that = this;
			var idx = e.currentTarget.dataset.idx;
			var tplindex = e.currentTarget.dataset.tplindex;
			var editorFormdata = this.editorFormdata;
			if(!editorFormdata) editorFormdata = [];
			app.chooseFile(function(data){
				editorFormdata[idx] = data;
				console.log(editorFormdata)
				that.editorFormdata = editorFormdata
				that.test = Math.random();
		
				var field = e.currentTarget.dataset.formidx;
				that.formvaldata[field] = data;
		
			})
		},
		removeimgzdy:function(e){
			var that = this;
			var idx = e.currentTarget.dataset.idx;
			var tplindex = e.currentTarget.dataset.tplindex;
			var field = e.currentTarget.dataset.formidx;
			var editorFormdata = this.editorFormdata;
			if(!editorFormdata) editorFormdata = [];
			editorFormdata[idx] = '';
			that.editorFormdata = editorFormdata
			that.test = Math.random();
			that.formvaldata[field] = '';
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
/* 自定义字段显示 */
/* .dp-form-item{width: 100%;display:flex;align-items: center;border-bottom:1px solid #F0F3F6;padding: 10rpx 0;} */
/* .dp-form-item:last-child{border:0} */
.dp-form-item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee }
.dp-form-item:last-child{ border:none}
.dp-form-item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right}
.dp-form-item input::placeholder{ color:#999999}
/* .dp-form-item .label{line-height: 50rpx;width:156rpx;margin-right: 10px;flex-shrink:0;text-align: right;color: #666666;font-size: 28rpx;} */
/* .dp-form-item .input{height: 88rpx;line-height: 88rpx;overflow: hidden;flex:1;border-radius:2px;} */
.dp-form-item .textarea{height:180rpx;line-height:40rpx;overflow: hidden;flex:1;border:none;border-radius:2px;padding:8rpx}
.dp-form-item .radio{height: 88rpx;line-height: 88rpx;display:flex;align-items:center}
.dp-form-item .radio2{display:flex;align-items:center;}
.dp-form-item .radio .myradio{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:50%}
.dp-form-item .checkbox{height: 88rpx;line-height: 88rpx;display:flex;align-items:center}
.dp-form-item .checkbox2{display:flex;align-items:center;height: 40rpx;line-height: 40rpx;}
.dp-form-item .checkbox .mycheckbox{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:2px}
.dp-form-item .layui-form-switch{}
.dp-form-item .picker{height: 88rpx;line-height:88rpx;flex:1;}

.dp-form-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.dp-form-imgbox-close{position: absolute;display: block;width:32rpx;height:32rpx;right:-20rpx;top:-25rpx;color:#999;font-size:32rpx;}
.dp-form-imgbox-close .image{width:100%;height:100%}
.dp-form-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px; margin:2px; border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.dp-form-imgbox-img>.image{max-width:100%;}
.dp-form-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.dp-form-uploadbtn{position:relative;height:200rpx;width:200rpx}
</style>