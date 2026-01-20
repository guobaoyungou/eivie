<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<view class="apply_box">
				<view class="apply_item">
					<view>商家名称<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="name" :value="info.name" :disabled="true" placeholder="请输入商家名称"></input></view>
				</view>
				<view class="apply_item">
					<view>商家描述<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="desc" :value="info.desc" placeholder="请输入商家描述"></input></view>
				</view>
				<view class="apply_item">
					<view>店铺坐标<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" disabled placeholder="请选择坐标" name="zuobiao" :value="latitude ? latitude+','+longitude:''" @tap="locationSelect"></input></view>
					<input type="text" hidden="true" name="latitude" :value="latitude"></input>
					<input type="text" hidden="true" name="longitude" :value="longitude"></input>
				</view>
				<view class="apply_item">
					<view>店铺地址<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="address" :value="address" placeholder="请输入商家详细地址"></input></view>
				</view>
				<view class="apply_item">
					<view>客服电话<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="tel" :value="info.tel" placeholder="请填写客服电话"></input></view>
				</view>
				<view v-if="new_score==1">
					<view class="apply_item">
						<view>让利比例(%)</view>
						<view class="flex-y-center"><input type="text" name="newscore_ratio" :value="info.newscore_ratio" placeholder="请填写比例"></input></view>
					</view>
					<view v-if="showBusinessNewscoreRatio" class="apply_item">
					  <view>让利到商家比例(%)</view>
					  <view class="flex-y-center"><input type="text" name="business_newscore_ratio" :value="info.business_newscore_ratio" placeholder="请填写比例"></input></view>
					</view>
					<view v-if="showMemberNewscoreRatio" class="apply_item">
					  <view>让利到消费者比例(%)</view>
					  <view class="flex-y-center"><input type="text" name="member_newscore_ratio" :value="info.member_newscore_ratio" placeholder="请填写比例"></input></view>
					</view>	
					<view class="flex-y-center" v-if="!isNull(new_score_log_str) && new_score_log_str">
						{{new_score_log_str}}
					</view>
					
				</view>		
				<view v-if="showSubsidyRatio" class="apply_item">
				  <view>让利比例(%)</view>
				  <view class="flex-y-center"><input type="text" name="feepercent" :value="info.feepercent" placeholder="请填写比例"></input></view>
				</view>
				<!-- <view class="apply_item" style="line-height:50rpx;display: none;"><textarea name="content" placeholder="请输入商家简介" :value="info.content"></textarea></view> -->
			</view>
			<view class="apply_box">
				<view class="apply_item" style="border-bottom:0"><view>商家主图<text style="color:red"> *</text></view></view>
				<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
					<view v-for="(item, index) in pic" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pic"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
					</view>
					<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pic" v-if="pic.length==0"></view>
				</view>
				<input type="text" hidden="true" name="logo" :value="pic.join(',')" maxlength="-1"></input>
			</view>
			<view class="apply_box">
				<view class="apply_item" style="border-bottom:0"><view>商家照片(3-5张)<text style="color:red"> *</text></view></view>
				<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
					<view v-for="(item, index) in pics" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pics"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
					</view>
					<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pics" v-if="pics.length<5"></view>
				</view>
				<input type="text" hidden="true" name="pics" :value="pics.join(',')" maxlength="-1"></input>
			</view>
			<view class="apply_box">
				<view class="apply_item" style="border-bottom:0"><view>营业时间<text style="color:red"> *</text></view></view>
					<view class="flex-col" style="flex-wrap:wrap;padding-bottom:20rpx;">
					<view class="flex-y-center">
						<picker mode="time" :value="start_hours" @change="bindStartHoursChange">
							 <view class="picker">{{start_hours}}</view>
						 </picker>
						 <view style="padding:0 30rpx;color:#222;">到</view>
						 <picker mode="time" :value="end_hours" @change="bindEndHoursChange">
							 <view class="picker">{{end_hours}}</view>
						 </picker>
					</view>
					<view class="flex-y-center">
						<picker mode="time" :value="start_hours2" @change="bindStartHours2Change">
							 <view class="picker">{{start_hours2}}</view>
						 </picker>
						 <view style="padding:0 30rpx;color:#222;">到</view>
						 <picker mode="time" :value="end_hours2" @change="bindEndHours2Change">
							 <view class="picker">{{end_hours2}}</view>
						 </picker>
					</view>
					<view class="flex-y-center">
						<picker mode="time" :value="start_hours3" @change="bindStartHours3Change">
							 <view class="picker">{{start_hours3}}</view>
						 </picker>
						 <view style="padding:0 30rpx;color:#222;">到</view>
						 <picker mode="time" :value="end_hours3" @change="bindEndHours3Change">
							 <view class="picker">{{end_hours3}}</view>
						 </picker>
					</view>
				</view>
				<view class="apply_item">
					<view>打烊后接单<text style="color:red"> *</text></view>
					<view class="flex-y-center">
						<radio-group class="radio-group" name="end_buy_status">
							<label><radio value="1" :checked="info.end_buy_status==1?true:false"></radio> 开启</label> 
							<label><radio value="0" :checked="info.end_buy_status==0?true:false"></radio> 关闭</label>
						</radio-group>
					</view>
				</view>
				<view class="apply_item">
					<view>店铺状态<text style="color:red"> *</text></view>
					<view class="flex-y-center">
						<radio-group class="radio-group" name="is_open">
							<label><radio value="1" :checked="info.is_open==1?true:false"></radio> 营业</label> 
							<label><radio value="0" :checked="info.is_open==0?true:false"></radio> 休息</label>
						</radio-group>
					</view>
				</view>
        <block v-if="info.canset_paymoney_givepercent">
          <view class="apply_item">
            <view>让利比例(%)</view>
            <view class="flex-y-center"><input type="text" name="paymoney_givepercent" :value="info.paymoney_givepercent" placeholder="请填写让利比例"></input></view>
          </view>
          
          <view v-if="info.paymoney_givepercent2>=0" class="apply_item" style="color: red;">
            <view>待审核让利比例(%)</view>
            <view class="flex-y-center"><input type="text" :disabled="true" :value="info.paymoney_givepercent2" ></input></view>
          </view>
        </block>
			</view>
			<view style="padding:30rpx 0"><button form-type="submit" class="set-btn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'">确 定</button></view>
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
      datalist: [],
      pagenum: 1,
      cateArr: [],
      cindex: 0,
			pic:[],
			pics:[],
      info: {},
      latitude: '',
      longitude: '',
      address:'',
			start_hours:'00:00',
			end_hours:'00:00',
			start_hours2:'00:00',
			end_hours2:'00:00',
			start_hours3:'00:00',
			end_hours3:'00:00',
			new_score:0,//激活币、新积分让利比例设置
			showBusinessNewscoreRatio:false,// 让利到消费者 是否显示
			showMemberNewscoreRatio:false,//让利到商家比例 是否显示
			new_score_log_str:'',//新积分修改审核记录
			showSubsidyRatio:0,//消费补贴买单让利 0隐藏 1显示
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
			app.get('ApiAdminIndex/setinfo', {}, function (res) {
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
				that.pics = pics;
				that.info = res.info;
        that.address = res.info.address;
        that.latitude = res.info.latitude;
        that.longitude = res.info.longitude;
				that.cateArr = cateArr;
				that.pic = res.info.logo ? [res.info.logo] : [];
				that.start_hours = res.info.start_hours ? res.info.start_hours : '00:00';
				that.end_hours = res.info.end_hours ? res.info.end_hours : '00:00';
				that.start_hours2 = res.info.start_hours2 ? res.info.start_hours2 : '00:00';
				that.end_hours2 = res.info.end_hours2 ? res.info.end_hours2 : '00:00';
				that.start_hours3 = res.info.start_hours3 ? res.info.start_hours3 : '00:00';
				that.end_hours3 = res.info.end_hours3 ? res.info.end_hours3 : '00:00';
				
				that.new_score = res.new_score;
				that.showBusinessNewscoreRatio = res.showBusinessNewscoreRatio || false;// 让利到消费者 是否显示
				that.showMemberNewscoreRatio = res.showMemberNewscoreRatio || false;//让利到商家比例 是否显示
				that.new_score_log_str = res.new_score_log_str || '';
				that.showSubsidyRatio = res.showSubsidyRatio || 0;//消费补贴让利
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
		
		bindStartHoursChange:function(e){
			this.start_hours = e.target.value
		},
		bindEndHoursChange:function(e){
			this.end_hours = e.target.value
		},
		
		bindStartHours2Change:function(e){
			this.start_hours2 = e.target.value
		},
		bindEndHours2Change:function(e){
			this.end_hours2 = e.target.value
		},
		
		bindStartHours3Change:function(e){
			this.start_hours3 = e.target.value
		},
		bindEndHours3Change:function(e){
			this.end_hours3 = e.target.value
		},
    subform: function (e) {
      var that = this;
      var info = e.detail.value;
      if (info.tel == '') {
        app.error('请填写客服电话');
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
        app.error('请上传商家主图');
        return false;
      }
      if (info.pics == '') {
        app.error('请上传商家照片');
        return false;
      }
      info.address = that.address;
      info.latitude = that.latitude;
      info.longitude = that.longitude;
			info.start_hours = that.start_hours ? that.start_hours : '00:00';
			info.end_hours = that.end_hours ? that.end_hours : '00:00';
			info.start_hours2 = that.start_hours2 ? that.start_hours2 : '00:00';
			info.end_hours2 = that.end_hours2 ? that.end_hours2 : '00:00';
			info.start_hours3 = that.start_hours3 ? that.start_hours3 : '00:00';
			info.end_hours3 = that.end_hours3 ? that.end_hours3 : '00:00';

			app.showLoading('提交中');
      app.post("ApiAdminIndex/setinfo", {info: info}, function (res) {
				app.showLoading(false);
        if (res.status == 1) {
          app.success(res.msg);
          setTimeout(function () {
            app.goto('index');
          }, 1000);
        } else {
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
</style>