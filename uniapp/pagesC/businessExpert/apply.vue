<template>
<view>
	<block v-if="isload">
		<view style="color:red;padding:10rpx 30rpx;margin-top:20rpx" v-if="info.id && info.status==2">审核不通过：{{info.reason}}，请修改后再提交</view>
		<view style="color:red;padding:10rpx 30rpx;margin-top:20rpx" v-if="info.id && info.status==0">您已提交申请，请等待审核</view>
		<form @submit="subform">
      <view class="apply_box">
      	<view class="apply_item">
      		<view>选择商家<text style="color:red"> *</text></view>
      		<view class="flex-y-center" style="align-items: center;min-width: 400rpx;justify-content: flex-end;">
            <view v-if="bid && bid>0">
            	{{bname}}
            </view>
            <view v-else style="color:#999;" @tap="goto" data-url="/pagesExt/business/blist?type=businessexpert">
            	请选择商家
            </view>
            <image class="img" :src="pre_url+'/static/img/arrowright.png'" style="width: 30rpx;height:30rpx;" />
          </view>
      	</view>
      </view>
			<view class="apply_box">
				<view class="apply_item">
					<view>联系人姓名<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="linkman" :value="info.linkman" placeholder="请填写姓名" placeholder-style="color:#999;"></input></view>
				</view>
				<view class="apply_item">
					<view>联系人电话<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="linktel" :value="info.linktel" placeholder="请填写手机号码" placeholder-style="color:#999;"></input></view>
				</view>
        <view class="apply_item" style="line-height:50rpx"><textarea name="reason" placeholder="请输入申请原因" :value="info.reason" placeholder-style="color:#999;"></textarea></view>
			</view>
			
			<view v-if="set.showpics" class="apply_box">
				<view class="apply_item" style="border-bottom:0">
          <view>
            相关照片
            <block v-if="set.min_picsnum && set.max_picsnum">
              ({{set.min_picsnum}}-{{set.max_picsnum}}张)
            </block>
            <block v-else>
              (最多{{set.max_picsnum}}张)
            </block>
            <text style="color:red"> *</text>
          </view>
        </view>
				<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
					<view v-for="(item, index) in pics" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pics"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
					</view>
					<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pics" v-if="pics.length< set.max_picsnum"></view>
				</view>
				<input type="text" hidden="true" name="pics" :value="pics.join(',')" maxlength="-1"></input>
			</view>

			<block v-if="set.xieyi_show==1">
        <view class="flex-y-center" style="margin-left:20rpx;color:#999" v-if="!info.id || info.status==2">
          <checkbox-group @change="isagreeChange"><label class="flex-y-center"><checkbox value="1" :checked="isagree"></checkbox>阅读并同意</label></checkbox-group>
          <text style="color:#666" @tap="showxieyiFun">《申请协议》</text>
        </view>
			</block>
			<view style="padding:30rpx 0">
				<button v-if="!info.id || info.status==2" form-type="submit" class="set-btn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'">
					提交申请
				</button>
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
      
      bid:0,
      bname:'',
      datalist: [],
      pagenum: 1,
      isagree: false,
      showxieyi: false,
			pics:[],
      info: {},
			set:{},

      ispost:false,

    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
  onShow:function(){
    var that = this;
    var pages = getCurrentPages(); //获取加载的页面
    var currentPage = pages[pages.length - 1]; //获取当前页面的对象
    if(currentPage && currentPage.$vm.expertbid){
        that.bid  = currentPage.$vm.expertbid;
        that.bname= currentPage.$vm.expertbname
    }
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiBusiness/applyexpert', {}, function (res) {
				that.loading = false;
        if(res.status == 1){
          var pics = res.info ? res.info.pics : '';
          if (pics) {
          	pics = pics.split(',');
          } else {
          	pics = [];
          }
          that.set = res.set
          that.info = res.info
          that.pics = pics;
          that.loaded();
        }else{
          app.alert(res.msg)
        }
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
      var bid  = that.bid;
      
      if (!bid || bid<=0) {
        app.error('请选择要申请的商户');
        return false;
      }
      info['bid'] = bid;

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
	  
      if (that.set.showpics && info.pics == '') {
        app.error('请上传相关照片');
        return false;
      }

      if (that.set.xieyi_show == 1 && !that.isagree) {
        app.error('请先阅读并同意申请协议');
        return false;
      }

      if(that.info && that.info.id) {
        info.id = that.info.id;
      }
      if(that.ispost) return;
      that.ispost = true;
			app.showLoading('提交中');
      app.post("ApiBusiness/applyexpert", {info: info}, function (res) {
				app.showLoading(false);
        setTimeout(function () {
           that.ispost = false
        }, 1000);
        if(res.status == 1){
          app.success(res.msg)
          setTimeout(function () {
            app.goto('/pagesC/businessExpert/expertlist','redirect');
          }, 1000);
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