<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<view class="form-box">
				<view class="form-item">
					<view class="f1">队列名称<text style="color:red"> *</text></view>
					<view class="f2"><input type="text" name="name" :value="info.name" placeholder="请填写名称" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view class="f1">前缀</view>
					<view class="f2"><input type="text" name="code" :value="info.code" placeholder="前缀,如A 大桌" placeholder-style="color:#888"></input></view>
				</view><view class="form-item">
					<view class="f1">最少座位数</view>
					<view class="f2"><input type="text" name="seat_min" :value="info.seat_min" placeholder="" placeholder-style="color:#888"></input></view>
				</view><view class="form-item">
					<view class="f1">最大座位数</view>
					<view class="f2"><input type="text" name="seat_max" :value="info.seat_max" placeholder="" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view class="f1">等待时间</view>
					<view class="f2"><input type="text" name="per_minute" :value="info.per_minute" placeholder="每个号等待时间(分)" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view class="f1">叫号语音文字</view>
					<view class="f2"><input type="text" name="call_text" :value="info.call_text" placeholder="排队号用'[排队号]'代替" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view class="f1">排序</view>
					<view class="f2"><input type="text" name="sort" :value="info.sort" placeholder="用于排序,越大越靠前" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view>状态<text style="color:red"> *</text></view>
					<view>
						<radio-group class="radio-group" name="status" @change="">
							<label><radio value="1" :checked="info.status==1?true:false"></radio> 显示</label> 
							<label><radio value="0" :checked="!info || info.status==0?true:false"></radio> 隐藏</label>
						</radio-group>
					</view>
				</view>
			</view>


			<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">提交</button>
			<button class="button text-btn" @tap="todel" v-if="info.id">删除</button>
			<view style="height:50rpx"></view>
		</form>

	</block>
	<loading v-if="loading"></loading>
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
		}
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
  methods: {
		getdata:function(){
			var that = this;
			var id = that.opt.id ? that.opt.id : '';
			that.loading = true;
			app.get('ApiAdminRestaurantQueue/categoryEdit',{id:id}, function (res) {
				that.loading = false;
				that.info = res.info;

				that.loaded();
			});
		},
    subform: function (e) {
      var that = this;
      var formdata = e.detail.value;
			
			if(formdata.name == '') {
				app.error('请输入名称');return;
			}

      var id = that.opt.id ? that.opt.id : '';
      app.post('ApiAdminRestaurantQueue/categorySave', {id:id,info:formdata}, function (res) {
        if (res.status == 0) {
          app.error(res.msg);
        } else {
          app.success(res.msg);
          setTimeout(function () {
            app.goto('queueCategory', 'redirect');
          }, 1000);
        }
      });
    },
		todel: function (e) {
		  var that = this;
		  var id = that.opt.id ? that.opt.id : '';
		  app.confirm('确定要删除吗?队列下排队号也会删除', function () {
		    app.post('ApiAdminRestaurantQueue/categoryDel', {id: id}, function (res) {
		      if (res.status == 1) {
		        app.success(res.msg);
		        app.goback(true)
		      } else {
		        app.error(res.msg);
		      }
		    });
		  });
		},
		bindStatusChange:function(e){
			this.info.status = e.detail.value;
		},
  }
};
</script>
<style>
radio{transform: scale(0.6);}
checkbox{transform: scale(0.6);}
.form-box{ padding:2rpx 24rpx 0 24rpx; background: #fff;margin: 24rpx;border-radius: 10rpx}
.form-item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee }
.form-item .f1{color:#222;width:200rpx;flex-shrink:0}
.form-item .f2{display:flex;align-items:center}
.form-box .form-item:last-child{ border:none}
.form-box .flex-col{padding-bottom:20rpx}
.form-item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right}
.form-item textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;}
.form-item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.form-item .upload_pic image{ width: 32rpx;height: 32rpx; }
.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}


.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.radio .radio-img{width:100%;height:100%;display:block}

</style>