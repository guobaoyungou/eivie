<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<view class="form-box">
				<block v-if="set.activity_time_custom">
					<view class="form-item">
						<view>活动时间设置</view>
						<view>
							<radio-group class="radio-group" name="activity_time_status" @change="">
								<label><radio value="0" :checked="!info || info.activity_time_status==0?true:false"></radio>关闭</label>
								<label><radio value="1" :checked="info.activity_time_status==1?true:false"></radio> 开启</label> 
							</radio-group>
						</view>
					</view>
					<view class="form-item">
						<view class="f1">活动时间</view>
						<view class="f2">
							<!-- <input type="text" name="name" :value="info.activity_time" placeholder="请填写名称" placeholder-style="color:#888"></input> -->
							<picker class="picker" mode="date" name="activity_time_start" :value="info.activity_time_start"   @change="activityTimeStartChange" >
								<view v-if="info.activity_time_start">{{info.activity_time_start}}</view>
								<view v-else>请选择</view>
							</picker>
							<view style="padding: 0 20rpx;"> ~ </view>
							<picker class="picker" mode="date" name="activity_time_end"   :value="info.activity_time_end" @change="activityTimeEndChange" >
								<view v-if="info.activity_time_end">{{info.activity_time_end}}</view>
								<view v-else>请选择</view>
							</picker>
						</view>
					</view>
				</block>
				<block v-if="set.business_own_mode_custom &&  platform_set.business_own_mode_st==1">
					<view class="form-item">
						<radio-group @change="modechange">
							<label class="flex" v-for="(item, index) in modeitems" :key="index" style="height: 60rpx;line-height:60rpx;;">
								<view>
									<radio :value="index" :checked="index == mode" />
								</view>
								<view>{{item}}</view>
							</label>
						</radio-group>
					</view>	
					<view class="form-item2" v-if="mode ==1">
						<view>
							<view class="item flex-y-center">
								<text class="label">平均返最低金额：</text>
								<view class="input-item flex-y-center">
									<input name="pj_min_money" type="digit" :value="info.pj_min_money" placeholder="0不限制，最低0.01元" placeholder-style="color:#999;"></input>元
								</view>
							</view>
						</view>
						
						<view class="desc">根据自己的订单消费金额在整个订单当中的总金额占比，来进行分配,最新下单+最早下单不超过50%</view>
					</view>	
					<view class="form-item2" v-if="mode ==2">
						<view>
							<view class="item flex-y-center">
								<text class="label">最新下单比例：</text>
								<view class="input-item flex-y-center">
									<input name="new_order_ratio" type="digit" :value="info.new_order_ratio" placeholder="请输入最新下单比例" placeholder-style="color:#999;"></input>%
								</view>
							</view>
							<view class="item flex-y-center">
								<text class="label">最早下单比例：</text>
								<view class="input-item flex-y-center">
									<input name="old_order_ratio" type="digit" :value="info.old_order_ratio" placeholder="请输入最早下单比例" placeholder-style="color:#999;"></input>%
								</view>
							</view>
						</view>
						<view class="desc">根据自己的订单消费金额在整个订单当中的总金额占比，来进行分配,最新下单+最早下单不超过50%</view>
					</view>	
					<view class="form-item2" v-if="mode ==3">
						<view>
							<view class="item flex-y-center">
								<text class="label">平均补贴比例：</text>
								<view class="input-item flex-y-center">
									<input name="pjmoney_pj_ratio" type="digit" :value="info.pjmoney_pj_ratio" placeholder="请输入最新下单比例" placeholder-style="color:#999;"></input>%
								</view>
							</view>
							<view class="item flex-y-center">
								<text class="label">平均返最低金额：</text>
								<view class="input-item flex-y-center">
									<input name="pjmoney_pj_min_money" type="digit" :value="info.pjmoney_pj_min_money" placeholder="请输入最早下单比例" placeholder-style="color:#999;"></input>元
								</view>
							</view>
							<view class="item flex-y-center">
								<text class="label">金额补贴比例：</text>
								<view class="input-item flex-y-center">
									<input name="pjmoney_money_ratio" type="digit" :value="info.pjmoney_money_ratio" placeholder="请输入金额补贴比例" placeholder-style="color:#999;"></input>%
								</view>
							</view>
							<view class="item flex-y-center">
								<text class="label">最新下单比例：</text>
								<view class="input-item flex-y-center">
									<input name="pjmoney_new_order_ratio" type="digit" :value="info.pjmoney_new_order_ratio" placeholder="请输入最新下单比例" placeholder-style="color:#999;"></input>%
								</view>
							</view>
							<view class="item flex-y-center">
								<text class="label">最早下单比例：</text>
								<view class="input-item flex-y-center">
									<input name="pjmoney_old_order_ratio" type="digit" :value="info.pjmoney_old_order_ratio" placeholder="请输入最早下单比例" placeholder-style="color:#999;"></input>%
								</view>
							</view>
						</view>
						
						<view class="desc">平均补贴+金额补贴必须等于 100%，最新下单+最早下单不超过50%</view>
					</view>	
					<view class="form-item2" v-if="mode ==4">
						<view>
							<view class="item flex-y-center">
								<text class="label">固定比例：</text>
								<view class="input-item flex-y-center">
									<input name="pjfixed_fixed_ratio" type="digit" :value="info.pjfixed_fixed_ratio" placeholder="请输入固定比例" placeholder-style="color:#999;"></input>%
								</view>
							</view>
							<view class="item flex-y-center">
								<text class="label">平均返最低金额：</text>
								<view class="input-item flex-y-center">
									<input name="pjfixed_pj_min_money" type="digit" :value="info.pjfixed_pj_min_money" placeholder="请输入最早下单比例" placeholder-style="color:#999;"></input>元
								</view>
							</view>
						</view>
						<view class="desc">平均比例 = 总返利比例(%) - 固定比例(%)</view>
					</view>	
					
					<view class="form-item2" v-if="mode ==5">
						<view>
							<view class="item flex-y-center">
								<text class="label">固定比例：</text>
								<view class="input-item flex-y-center">
									<input name="today_fixed_ratio" type="digit" :value="info.today_fixed_ratio" placeholder="请输入固定比例" placeholder-style="color:#999;"></input>%
								</view>
							</view>
							<view class="item flex-y-center">
								<text class="label">今日平均比例：</text>
								<view class="input-item flex-y-center">
									<input name="today_pj_ratio" type="digit" :value="info.today_pj_ratio" placeholder="请输入今日平均比例" placeholder-style="color:#999;"></input>元
								</view>
							</view>
							<view class="item flex-y-center">
								<text class="label">非今日平均：</text>
								<view class="input-item flex-y-center">
									<input name="today_other_ratio" type="digit" :value="info.today_other_ratio" placeholder="请输入非今日平均" placeholder-style="color:#999;"></input>元
								</view>
							</view>
						</view>
						<view class="desc">固定比例(%)+ 今日平均比例(%) + 非今日平均(%)  100%</view>
					</view>	
				</block>
			</view>

			<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">提交</button>
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
			set:{},
			platform_set:{},//平台设置
			modeitems:['固定分配','平均分配','金额占比分配','平均+金额占比分配','平均+固定分配'],
			mode:'0'
		}
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiAdminQueueFree/getQueueFreeSet',{}, function (res) {
				that.loading = false;
				if(res.status==0){
					app.error(res.msg);
					return;
				}
				that.info = res.info;
				that.set = res.set;
				that.platform_set = res.platform_set;
				that.mode = that.info.mode
				that.modeitems = that.set.modeitems;
				that.loaded();
			});
		},
    subform: function (e) {
      var that = this;
      var formdata = e.detail.value;
		formdata.mode = that.mode;
      var id = that.opt.id ? that.opt.id : '';
	  that.loading = true;
      app.post('ApiAdminQueueFree/saveQueueFreeSet', {info:formdata}, function (res) {
		  that.loading = false;
        if (res.status == 0) {
          app.error(res.msg);
        } else {
          app.success(res.msg);
          that.getdata();
        }
      });
    },
	activityTimeStartChange(e){
		var activity_time_start = e.detail.value;
		let activity_time_start_date = new Date(activity_time_start) //开始时间
		let activity_time_end_date = new Date(this.info.activity_time_end) //结束时间
		if(activity_time_start_date > activity_time_end_date){
			app.error('开始时间不能大于结束时间');
			return false;
		}
		this.info.activity_time_start = activity_time_start;
	},
	activityTimeEndChange(e){
		var activity_time_end = e.detail.value;
		let activity_time_start_date = new Date(this.info.activity_time_start) //开始时间
		let activity_time_end_date = new Date(activity_time_end) //结束时间
		if(activity_time_start_date > activity_time_end_date){
			app.error('结束时间不能小于开始时间');
			return false;
		}
		this.info.activity_time_end = activity_time_end;
	},
	modechange(e){
		this.mode = e.detail.value;
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
.form-item .f2{display:flex;align-items:center;}
.form-box .form-item:last-child{ border:none}
.form-box .flex-col{padding-bottom:20rpx}
.form-item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right}
.form-item textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;}
.form-item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.form-item .upload_pic image{ width: 32rpx;height: 32rpx; }

.form-item2{width: 100%;padding: 10rpx 0;}
.form-item2 .item{margin-top: 10rpx;}
.form-item2 .item .label{ width: 230rpx;height:60rpx;line-height:60rpx}
.form-item2 .item .input-item{ width:50%;}
.form-item2  .desc{color: #999;padding:0 0 10rpx 0;font-size: 26rpx;line-height: 40rpx;}

.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}


.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.radio .radio-img{width:100%;height:100%;display:block}

</style>