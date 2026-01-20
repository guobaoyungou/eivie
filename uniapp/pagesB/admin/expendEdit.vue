<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<view class="form-box">
				<view class="form-item">
					<view class="f1">支出金额</view>
					<view class="f2"><input type="text" name="money" :value="info.money" placeholder="清输入支出金额" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view class="f1">选择分类</view>
					<view class="f2">
						<picker mode="selector" :range="clist" range-key="name" @change="pickerChange" data-field="clist">
							<view class="picker">
								<view class="picker-txt"><text>{{clist_index>-1?clist[clist_index].name:'请选择分类'}}	</text><image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"></image></view>
							</view>
						</picker>
					</view>
				</view>
				<view class="form-item">
					<view class="f1">备注</view>
					<view class="f2"><input type="text" name="remark" :value="info.remark" placeholder="" placeholder-style="color:#888"></input></view>
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
			clistshow:false,
			cid:0,
			cnames:'',
			cateArr:[],
			clist:[],
			clist_index:-1
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
			app.get('ApiAdminExpend/edit',{id:id}, function (res) {
				that.loading = false;
				that.info = res.info;
				if(that.info) that.cid = that.info.cid;
				that.clist = res.clist;
				if(that.clist && that.cid){
					for(var i in that.clist){
						if(that.cid==that.clist[i].id){
							that.clist_index = i;
						}
					}
				}
				
				that.cateArr = res.cateArr;

				that.loaded();
			});
		},
    subform: function (e) {
      var that = this;
      var formdata = e.detail.value;
			if(formdata.money == '') {
				app.error('请输入金额');
				return;
			}
      var id = that.opt.id ? that.opt.id : '';
			formdata.cid = that.cid;
			that.loading = true;
      app.post('ApiAdminExpend/save', {id:id,info:formdata}, function (res) {
				that.loading = false;
        if (res.status == 0) {
          app.error(res.msg);
        } else {
          app.success(res.msg);
          setTimeout(function () {
            app.goto('expend', 'redirect');
          }, 1000);
        }
      });
    },
		todel: function (e) {
		  var that = this;
		  var id = that.opt.id ? that.opt.id : '';
		  app.confirm('确定要删除吗？删除后不可恢复', function () {
				that.loading = true;
		    app.post('ApiAdminExpend/del', {id: id}, function (res) {
					that.loading = false;
		      if (res.status == 1) {
		        app.success(res.msg);
		        app.goback(true)
		      } else {
		        app.error(res.msg);
		      }
		    });
		  });
		},
		pickerChange:function(e){
			var field = e.currentTarget.dataset.field
			this[field+'_index'] = e.detail.value;
			this.cid = this.clist[e.detail.value].id;
		}
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

 .picker-txt{display: flex;    align-items: center;}

</style>