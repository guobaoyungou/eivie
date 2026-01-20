<template>
<view class="container" v-if="isload">
	<view class="warning" v-if="detail.mohu>0">该人员设置了隐私保护，详细信息，请联系招聘顾问！</view>
	<view class="profile flex-s">
		<view class="thumb">
			<!-- <image :src="detail.thumb" mode="aspectFit" @tap="previewImage" :data-url="detail.thumb"></image> -->
			<!-- <image :src="detail.thumb" mode="aspectFit" @tap="previewImage" :data-url="detail.thumb"></image> -->
			<image v-if="detail.mohu>0" :src="detail.thumb" mode="aspectFit" :style="'filter: blur('+detail.mohu+'px);-webkit-filter: blur('+detail.mohu+'px);-moz-filter: blur('+detail.mohu+'px)'"></image>
			<image v-else :src="detail.thumb" mode="aspectFit" @tap="previewImage" :data-url="detail.thumb"></image>
		</view>
		<view class="info">
				<view class="name">{{detail.name}}</view>
				<view class="">
					<text v-if="detail.age">{{detail.age}}岁</text>
					<text v-if="detail.sex==1">/男</text>
					<text v-if="detail.sex==2">/女</text>
				</view>
				<view class="">
					期望岗位：{{detail.cnames}}
				</view>
		</view>
	</view>
	<view class="box title-box">
		<view class="title">{{detail.title}}</view>
		<view class="flex-sb">
			<view class="salary">{{detail.salary}}</view>
			<view class="" v-if="detail.area">{{detail.area}}</view>
		</view>
		<view class="flex-sb hui">
			<view class="">发布于{{detail.createtime}}</view>
			<view class="">浏览{{detail.readnum}}次</view>
		</view>
	</view>
	<form @submit="formSubmit">
		<view class="box top">
			<view class="form-item">
				<view class="form-label">参考费用</view>
				<view class="form-value">
					<text>{{set.top_per_fee}}元/天</text>
					<!-- <text v-if="feetype==1">{{set.top_fee}}元/天</text> -->
				</view>
			</view>
			<view class="form-item">
				<view class="form-label">置顶时间</view>
				<view class="form-value radio">
					<radio-group name="duration" @change="durationChange">
						<label>
								<radio value="7" style="transform: scale(0.7);" :checked="duration==7"/>一周
						</label>
						<label>
								<radio value="30" style="transform: scale(0.7);" :checked="duration==30"/>一个月
						</label>
					</radio-group>
				</view>
			</view>
		</view>
		<view style="height: 120rpx;"></view>
		<view class="option flex-sb">
			<view class="sum">合计：<text :style="{color:t('color1')}">￥{{totalprice}}</text></view>
			<view><button class="btn"  form-type="submit" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)',color:'#ffffff'}">置顶</button></view>
		</view>
	</form>
	
	<loading v-if="loading"></loading>
	<popmsg ref="popmsg"></popmsg>
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
			pre_url: app.globalData.pre_url,
			menuindex:-1,
			set:{},
			detail:{},
			formorder:{},
			feetype:2,
			duration:7,
			isbasearea:false,
			items:[],
			area:[],
			totalprice:0,
			city_num:1,
			unit_price:0
    }
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiZhaopin/qiuzhiTop', {id:that.opt.id}, function (res) {
				that.loading = false;
				if(res.status==1){
					that.detail = res.detail
					that.set = res.set
					that.unit_price = res.set.top_per_fee
					that.calculate()
					//地区加载
					// uni.request({
					// 	url: app.globalData.pre_url+'/static/area.json',
					// 	data: {},
					// 	method: 'GET',
					// 	header: { 'content-type': 'application/json' },
					// 	success: function(res2) {
					// 		that.items = res2.data
					// 	}
					// });
					that.loaded()
				}else{
					app.alert(res.msg)
				}
			});
		},
		calculate:function(){
			var that = this
			var totalprice = 0;
			totalprice = (that.unit_price * that.city_num * that.duration).toFixed(2)
			that.totalprice = totalprice
		},
		feetypeChange:function(e){
			var that = this
			that.feetype = e.detail.value
			if(that.feetype==1){
				that.city_num = 1;
				that.unit_price = that.set.top_fee
			}else if(that.feetype==2){
				that.city_num = that.area.length
				that.unit_price = that.set.top_per_fee
			}
			that.calculate()
		},
		durationChange:function(e){
			this.duration = e.detail.value
			this.calculate()
		},
		showBasearea:function(e){
			this.isbasearea = true
			// console.log(this.items)
		},
		hideBasearea:function(e){
			this.isbasearea = false
		},
		chooseBasearea:function(e){
			var that = this
			var provinceindex = e.currentTarget.dataset.index
			var cityindex = e.currentTarget.dataset.index1
			var items = that.items
			var checked = items[provinceindex]['children'][cityindex].checked
			
			if(!checked || checked==undefined || checked=='undefined'){
				items[provinceindex]['children'][cityindex].checked = 1;
			}else{
				items[provinceindex]['children'][cityindex].checked = 0;
			}
			var area = [];
			for(var i in items){
				for(var j in items[i]['children']){
					if(items[i]['children'][j].checked){
						area.push(items[i]['children'][j].text)
					}
				}
			}
			that.items = items
			that.city_num = area.length
			that.calculate()
			that.area = area
		},
		formSubmit: function (e) {
			var that = this;
		  var formdata = e.detail;
			
		  if (formdata.feetype == '' || formdata.feetype == 0){
		    app.alert('请选择收费标准');
		    return;
		  }
			if (formdata.duration == '' || formdata.duration == 0){
			  app.alert('请选择置顶时间');
			  return;
			}
			
		  var postdata = {
				id:that.opt.id,
				feetype:that.feetype,
				duration:that.duration,
				area:that.area
			};
			app.showLoading('提交中');
		  app.post("ApiZhaopin/qiuzhiTop", postdata, function (data) {
				app.showLoading(false);
		    if (data.status == 1) {
					var tourl = encodeURIComponent('/zhaopin/zhaopin/my?st=1&type=1');
					app.goto('/pages/pay/pay?id=' + data.payorderid + '&tourl='+tourl);
		    } else {
		      app.error(data.msg);
		    }
		  });
		}
  }
}
</script>
<style>
@import "../common.css";
.box{background:#FFFFFF;padding: 0 30rpx;margin-bottom: 20rpx;line-height: 50rpx;}
.warning{background: #000000;opacity: 0.7;color: #ff0000;position: fixed;top: 0;width: 100%;line-height: 50rpx;text-align: center;font-weight: bold;z-index: 10;}
.hui{color: #9a9a9a;}
.profile{background: #FFFFFF;padding:50rpx 30rpx;margin-bottom: 20rpx;font-size: 24rpx;}
.profile .info{padding-left: 20rpx;color: #999;line-height: 38rpx;}
.profile .name{font-size: 32rpx;font-weight: bold;color: #222222;line-height: 50rpx;}
.thumb{width: 120rpx;height: 120rpx;overflow: hidden;display: flex;align-items: center;justify-content: center;border-radius: 50%;}
.thumb image{max-height: 100%;border-radius: 50%;}
.form-item{display: flex;justify-content: flex-start;align-items: center;border-bottom: 1rpx solid #f6f6f6; padding: 20rpx 0;}
.form-label{flex-shrink: 0;width: 180rpx;flex-wrap: wrap;font-weight: bold;/* text-align: justify;text-align-last: justify; */padding-right: 30rpx;}
.form-value{flex: 1;}
.form-tips{color: #CCCCCC;font-size: 28rpx;padding: 20rpx 0;}
/* 行排列 */
.form-item-row{border-bottom: 1rpx solid #f6f6f6; padding: 20rpx 0;}
.form-item-row .form-label,.form-item-row .form-value{width: 100%;}
.form-value.textarea{padding:10rpx 4rpx;border-radius: 8rpx;color: #999;}
.tagitem{background: #f4f7fe;text-align: center;padding: 0 6rpx;margin-right: 8rpx;white-space: normal;font-size: 24rpx;color: #999;}
.title-box{padding: 30rpx;}
.title-box .title{font-size: 32rpx;font-weight: bold;}
.title-box .salary{color: #FF3A69;}

.box .form-item:last-child{border: none;}
.option{position: fixed;bottom: 0;width: 100%;padding: 30rpx;background: #FFFFFF;}
.option .sum{font-weight: bold;font-size: 32rpx;}
.option .btn{width: 180rpx;text-align: center;background:#031028;color: #FFFFFF;display: block;line-height: 60rpx ;}
</style>