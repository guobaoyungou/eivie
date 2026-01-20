<template>
<view class="container" v-if="isload">
	<view class="thumb" v-if="detail.thumb"><image :src="detail.thumb" mode="widthFix" @tap="previewImage" :data-url="detail.thumb"></image></view>
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
		<view class="box top" v-if="set.show_top">
			<view class="form-title"> 置顶费用<text v-if="is_assurance" class="label-tips">(担保托管需先置顶)</text></view>
			<view class="form-item">
				<view class="form-label">收费标准</view>
				<view class="form-value radio">
					<radio-group name="fee_type" @change="feetypeChange">
						<label>
								<radio value="1" style="transform: scale(0.7);" :checked="feetype==1"/>全国一口价
						</label>
						<label>
								<radio value="2" style="transform: scale(0.7);" :checked="feetype==2"/>自定义区域
						</label>
					</radio-group>
				</view>
			</view>
			<view class="form-item">
				<view class="form-label">参考费用</view>
				<view class="form-value">
					<text v-if="feetype==2">{{set.top_per_fee}}元/天/城市</text>
					<text v-if="feetype==1">{{set.top_fee}}元/天</text>
				</view>
			</view>
			<view class="form-item">
				<view class="form-label">置顶城市</view>
				<view v-if="feetype==1">全国</view>
				<view v-if="feetype==2" class="form-value picker">
					<view :class="area.length>0?'':'hui'" class="flex-sb" @tap.stop="showBasearea">
						<text>{{area.length>0?area.join(','):'请选择城市'}}</text>
						<image class="down" src="../../static/img/arrowright.png">
					</view>
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
		<view class="box" v-if="is_assurance">
			<view class="form-title">担保费用</view>
			<view class="form-item">
				<view class="form-label">担保人数</view>
				<view class="form-value" style="">
						<input type="number" name="assurance_num" class="input input-sm" :value="detail.num" @input="changeInput" data-field="assurance_num">
				</view>
				<view class="form-tips">人</view>
			</view>
			<view class="form-item">
				<view class="form-label">保证金</view>
				<view class="form-value">
					<text>￥{{set.assurance_per_fee}}/人</text>
				</view>
			</view>
			<view class="form-item" v-if="assurance_fee>0">
				<view class="form-label">可用保证金</view>
				<view class="form-value">
					<text>￥{{set.assurance_fee}}</text>
				</view>
			</view>
		</view>
		<view style="height: 120rpx;"></view>
		<view class="option flex-sb">
			<view class="sum">合计：<text :style="{color:t('color1')}">￥{{totalprice}}</text></view>
			<view><button class="btn"  form-type="submit" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)',color:'#ffffff'}">确定</button></view>
		</view>
	</form>
	
	<!-- 期望省份Start -->
	<view v-if="isbasearea" class="popup__container popup_area">
		<view class="popup__overlay" @tap.stop="hideBasearea"></view>
		<view class="popup__modal">
			<view class="popup__title">
				<text class="popup__title-text">请选择城市</text>
				<image src="/static/img/close.png" class="popup__close" @tap.stop="hideBasearea" />
			</view>
			<view class="popup__content">
				<view class="choose-main" v-for="(item,index) in items" :key="index">
						<view class="choose-title">{{item.text}}</view>
						<view class="choose-box">
							<view class="choose-item" v-for="(item1,index1) in item.children" @tap="chooseBasearea" :data-index="index" :data-index1="index1" :class="item1.checked?'on':''">{{item1.text}}</view>
						</view>
				</view>
			</view>
		</view>
	</view>
	<!-- 期望省份End -->
	
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
			feetype:1,
			duration:7,
			isbasearea:false,
			items:[],
			area:[],
			totalprice:0,
			city_num:1,
			unit_price:0,
			is_assurance:0,
			assurance_fee:0,
			assurance_per_fee:0,
			assurance_num:0
    }
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.is_assurance = this.opt.isas || 0;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiZhaopin/zhaopinTop', {id:that.opt.id,is_assurance:that.is_assurance}, function (res) {
				that.loading = false;
				if(res.status==1){
					that.detail = res.detail
					that.set = res.set
					that.unit_price = res.set.top_fee
					if(that.is_assurance){
						that.assurance_fee = that.set.assurance_fee
						that.assurance_per_fee = that.set.assurance_per_fee
					}
					that.assurance_num = that.detail.num
					that.calculate()
					//地区加载
					uni.request({
						url: app.globalData.pre_url+'/static/area.json',
						data: {},
						method: 'GET',
						header: { 'content-type': 'application/json' },
						success: function(res2) {
							that.items = res2.data
						}
					});
					that.loaded()
				}else{
					app.alert(res.msg)
				}
			});
		},
		calculate:function(){
			var that = this
			var totalprice = 0;
			if(that.set.show_top){
				totalprice = that.unit_price * that.city_num * that.duration
			}
			if(that.is_assurance){
				var assurance_fee = that.assurance_num * that.assurance_per_fee
				//如果担保的费用超过了我的剩余保证金，则需要补交
				if(assurance_fee>that.assurance_fee){
					assurance_fee = assurance_fee - that.assurance_fee
					totalprice = totalprice*1 + assurance_fee
				}
			}
			that.totalprice = totalprice.toFixed(2)
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
		changeInput:function(e){
			var field = e.currentTarget.dataset.field
			this[field] = e.detail.value
			if(field=='assurance_num'){
				this.calculate()
			}
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
				area:that.area,
				is_assurance:that.is_assurance,
				assurance_num:that.assurance_num
			};
			app.showLoading('提交中');
		  app.post("ApiZhaopin/zhaopinTop", postdata, function (data) {
				app.showLoading(false);
		    if (data.status == 1) {
					if(data.needApprove){
						var tourl = encodeURIComponent('/zhaopin/zhaopin/apply?zid='+that.opt.id);
					}else{
						var tourl = encodeURIComponent('/zhaopin/zhaopin/my?type=2&st=1');
					}
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
/* .top{margin-bottom: -200rpx;} */
.hui{color: #9a9a9a;}
.label-tips{color: #9a9a9a;font-weight: normal;font-size: 28rpx;}
.form-title{font-size: 32rpx;font-weight: bold;padding-top: 30rpx;}
.form-tips{color: #9a9a9a;font-size: 26rpx;padding-top: 20rpx;}
.thumb{background: #FFFFFF;margin-bottom: 20rpx;width: 100%;max-height: 400rpx;overflow: hidden;display: flex;align-items: center;justify-content: center;}
.thumb image{max-height: 100%;}
.form-item{display: flex;justify-content: flex-start;align-items: center;border-bottom: 1rpx solid #f6f6f6; padding: 20rpx 0;}
.form-label{flex-shrink: 0;width: 180rpx;flex-wrap: wrap;font-weight: bold;/* text-align: justify;text-align-last: justify; */padding-right: 30rpx;}
.form-value{flex: 1;}
.form-value .input{border: 1rpx solid #e7e7e7;height: 64rpx;border-radius: 10rpx;}
.form-value .input-sm{text-align: center;width: 160rpx;}
.form-value.radio label{display: block;}
/* 行排列 */
.form-item-row{border-bottom: 1rpx solid #f6f6f6; padding: 20rpx 0;}
.form-item-row .form-label,.form-item-row .form-value{width: 100%;}
.form-value.textarea{padding:10rpx 4rpx;border-radius: 8rpx;color: #999;}
.form-value .down{width: 32rpx;height: 32rpx;vertical-align:middle;flex-shrink: 0;}
.tagitem{background: #f4f7fe;text-align: center;padding: 0 6rpx;margin-right: 8rpx;white-space: normal;font-size: 24rpx;color: #999;}
.title-box{padding: 30rpx;}
.title-box .title{font-size: 32rpx;font-weight: bold;}
.title-box .salary,.number{color: #FF3A69;}

.box .form-item:last-child{border: none;}
.option{position: fixed;bottom: 0;width: 100%;padding: 30rpx;background: #FFFFFF;}
.option .sum{font-weight: bold;font-size: 32rpx;}
.option .btn{width: 180rpx;text-align: center;background:#031028;color: #FFFFFF;display: block;line-height: 60rpx ;}


/* modal */
	.popup__content{padding:10rpx 20rpx 50rpx 20rpx;}
	.popup__modal{border-radius: 0;max-height: 640rpx;min-height: 640rpx;}
	.popup__title{background: #f6f6f6;padding: 20rpx;}
	.popup__title .popup__close{width: 24rpx;height: 24rpx;}
	.popup__content .choose-main{padding-top: 20rpx;}
	.popup__content .choose-title{width: 100%;font-weight: bold;font-size: 32rpx;padding-left: 10rpx;}
	.popup__content .choose-box{display: flex;justify-content: flex-start;flex-wrap: wrap;align-items: center;}
	.popup__content .choose-box .choose-item{width: 49%;flex-shrink: 0;overflow: hidden;background: #F6F6F6;text-align: center;padding:16rpx;margin-bottom: 16rpx;}
	.popup__content .choose-box .choose-item.on{color: #FE924A;background:#fe924a30;}

	.popup_area .choose-box .choose-item{width: 165rpx;line-height: 36rpx;flex-wrap: nowrap;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;overflow: hidden;margin-top: 20rpx;margin-bottom: 0;margin-left: 10rpx;}
	

</style>