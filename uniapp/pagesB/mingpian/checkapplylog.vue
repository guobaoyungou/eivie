<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<view class="form-box">
				<view class="form-item flex-col" style="border-bottom:0">
					<view class="f1" style="width:100%">联系信息</view>
				</view>
				<block v-for="(item,index) in field_list" :key="index" >
          <view v-if="item.isshow==1" class="form-item">
            <view class="f1">{{item.name}}</view>
            <view class="f2">
              <radio-group>
              	<label @tap="changseeaddfields(index,true)"><radio :value="true" :checked="seeaddfields[index]? true : false" />展示</label>
                <label style="margin-left: 20rpx;" @tap="changseeaddfields(index,false)"><radio :value="false" :checked="seeaddfields[index]? false : true" />不展示</label>
              </radio-group>
            </view>
            <view class="f3" style="color:#58e;font-size:24rpx;margin-left:6rpx"></view>
          </view>
				</block>
			</view>
      <view v-if="log.applysee == 1" style="margin-top:60rpx;display: flex;justify-content: space-between;width: 680rpx;margin: 0 auto;">
        <button class="savebtn" form-type="submit2" style="background:#fff;color: #212121;">驳回</button>
        <button class="savebtn" form-type="submit">提交并同意</button>
      </view>
      <view v-else-if="log.applysee == 2" style="margin-top:60rpx;width: 680rpx;margin: 0 auto;">
        <button class="savebtn" form-type="submit" style="width: 100%;">提交</button>
      </view>
			<view style="height:50rpx"></view>
		</form>
	</block>
	<view style="display:none">{{test}}</view>
	<loading v-if="loading"></loading>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
</view>
</template>

<script>
var app = getApp();

export default {
  data() {
    return {
			isload:false,
			loading:false,
			pre_url:app.globalData.pre_url,
      info:{},
			field_list:[],
			pagecontent:[],

      log:{},
      addfields:{},
      seeaddfields:{},
      id:0
    };
  },

  onLoad: function (opt) {
    var that = this;
    var opt = app.getopts(opt);
    if(opt){
      that.opt = opt;
      that.id  = opt.id || 0;
    }
		that.getdata();
  },
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiMingpian/checkAddfields',{id:that.id}, function (res) {
				that.loading = false;
        if (res.status == 1) {
          that.log = res.log || {};
          that.info = res.info || {};
          that.field_list = res.field_list;
          if(res.addfields){
            that.addfields = res.addfields;
          }
          if(res.seeaddfields){
            that.seeaddfields = res.seeaddfields
          }
          that.loaded();
        }else{
        		if(res.msg) {
        			app.alert(res.msg, function() {
        				if (res.url) app.goto(res.url);
        			});
        		} else if(res.url) {
        			app.goto(res.url);
        		} else {
        			app.alert('您无查看权限',function(){
                app.goback();
              });
        		}
        	}
			});
		},
    subform: function (e) {
      var that = this;
      var seeaddfields = that.seeaddfields;
      var msg = '确定提交信息吗？'
      app.confirm(msg,function(){
        app.showLoading();
        app.post('ApiMingpian/checkAddfields', {id:that.id,seeaddfields:seeaddfields,type:1}, function (res) {
          if (res.status == 0) {
            app.error(res.msg);
          } else {
            app.success(res.msg);
            setTimeout(function () {
              app.goback();
            }, 1000);
          }
        });
      });
    },
    subform2: function (e) {
      var that = this;
      var formdata = e.detail.value;
      var msg = '确定驳回申请吗？'
      app.confirm(msg,function(){
        app.showLoading();
        app.post('ApiMingpian/save', {id:that.id,seeaddfields:'',type:0}, function (res) {
          if (res.status == 0) {
            app.error(res.msg);
          } else {
            app.success(res.msg);
            setTimeout(function () {
              app.goback();
            }, 1000);
          }
        });
      });
    },
    changseeaddfields:function(field,type){
      var that = this;
      var seeaddfields = that.seeaddfields;
      seeaddfields[field] = type?true:false;
      that.seeaddfields = seeaddfields;
      console.log(seeaddfields)
    }
  }
};
</script>
<style>
radio{transform: scale(0.6);}
checkbox{transform: scale(0.6);}
.form-box{ padding:2rpx 24rpx 0 24rpx; background: #fff;margin: 24rpx;border-radius: 10rpx}
.form-item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:0px solid #eee }
.form-item .f1{color:#222;width:250rpx;flex-shrink:0;}
.form-item .f2{display:flex;align-items:center;flex:1}
.form-box .form-item:last-child{ border:none}
.form-box .flex-col{padding-bottom:20rpx}
.form-item input{ width: 100%; border: 1px solid #f1f1f1;color:#111;font-size:28rpx; /*text-align: right;*/height:70rpx;padding:0 10rpx;border-radius:6rpx}
.form-item textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;}
.form-item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.form-item .upload_pic image{ width: 32rpx;height: 32rpx; }
.savebtn{ width: 300rpx; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold; border: none;background:#4A84FF}

.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.clist-image{width: 100%;display: block;}
.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;}
.radio .radio-img{width:100%;height:100%;display:block}

</style>
