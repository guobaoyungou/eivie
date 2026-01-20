<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit">
		<view class="form">
			<view class="form-item">
				<text class="label">寄存名称</text>
				<text class="flex1"></text>
				<text>{{info.name}}</text>
			</view>
			<view class="form-item">
				<text class="label">寄存数量</text>
				<text class="flex1"></text>
				<text>{{info.num}}</text>
			</view>
			<view class="form-item">
				<text class="label">寄存人</text>
				<text class="flex1"></text>
				<text>{{info.linkman}}</text>
			</view>
			<view class="form-item">
				<text class="label">手机号</text>
				<text class="flex1"></text>
				<text>{{info.tel}}</text>
			</view>
		</view>
		<view class="form">
			<view class="form-item">
				<text class="label">寄存备注</text>
				<text class="flex1"></text>
				<text>{{info.message}}</text>
			</view>
		</view>
		<view class="form">
			<view class="form-item">
				<text class="label">状态</text>
				<text class="flex1"></text>
				<text>{{info.statusLabel}}</text>
			</view>
		</view>
		<view class="form">
			<view class="flex-col">
				<text class="label" style="height:98rpx;line-height:98rpx;font-size:30rpx">寄存拍照</text>
				<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
					
						<view class="layui-imgbox-img"><image :src="info.pic" @tap="previewImage" :data-url="info.pic" mode="widthFix"></image></view>
					
				</view>
				<input type="text" hidden="true" name="pic" :value="pic.join(',')" maxlength="-1"></input>
			</view>
		</view>
		<button class="btn" v-if="info.status == 1" @tap="takeout" :data-num="info.num" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'">取出</button>
		<button class="btn" v-if="info.status == 0" @tap="check" data-type="access" data-operate="通过审核" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'">通过审核</button>
		<button class="btn btn2" v-if="info.status == 0" @tap="check"  data-type="refuse" data-operate="驳回">驳回</button>
		</form>
		
		<view class="expressinfo" v-if="info.log.length >0">
			<view class="content">
				<view v-for="(item, index) in info.log" :key="index" :class="'item ' + (index==0?'on':'')">
					<view class="f1"><image :src="'/static/img/dot' + (index==0?'2':'1') + '.png'"></image></view>
					<view class="f2">
						<text class="t2">{{dateFormat(item.createtime)}}</text>
						<text class="t1">{{item.remark}}{{item.num}}件</text>
					</view>
				</view>
			</view>
		</view>
		
		<!-- 弹框 -->
		<view v-if="boxShow" class="" @touchmove.stop.prevent="disabledScroll">
			<view class="popup__overlay" @tap.stop="handleClickMask"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请输入取出数量</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
						@tap.stop="handleClickMask" />
				</view>
				<view class="popup__content takeoutBox">
					<form @submit="formSubmit" @reset="formReset" report-submit="true">
						<view class="orderinfo">
							
							<view class="item">
								<text class="t1">取出数量</text>
								<input class="t2" type="text" placeholder="请输入要取出的数量" placeholder-style="font-size:28rpx;color:#BBBBBB" name="numbers" :value="num"></input>
							</view>
						</view>
						<button class="btn" form-type="submit" :style="{background:t('color1')}">确定</button>
						
					</form>
				</view>
			</view>
		</view>
		
	</block>
	<loading v-if="loading"></loading>
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

			pic:[],
			info:{},
			boxShow:false,
			num:1,
			
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
			app.post('ApiAdminRestaurantDepositOrder/detail', {id: that.opt.id}, function (res) {
				that.loading = false;
				var data = res.detail;
				that.info = data;
				that.loaded();
			  
			});
		},
		handleClickMask: function() {
			this.boxShow = !this.boxShow;
		},
		takeout: function (e) {
		   var that = this;
			 this.boxShow = true; //显示弹框
			 this.num = e.currentTarget.dataset.num;
		},
		disabledScroll: function (e) {
			return false;
		},
		formSubmit: function (e) {
			 var that = this;
			 var formdata = e.detail.value;
			 //alert(formdata.numbers);
			app.post('ApiAdminRestaurantDepositOrder/takeout', {orderid:that.info.id,numbers:formdata.numbers}, function (data) {
				if(data.status== 0){
					app.alert(data.msg);return;
				}
			  app.success(data.msg);
			  setTimeout(function () {
				  that.boxShow = false; //隐藏弹框
					that.getdata();
			  }, 1000);
			});
		},
		check: function(e){
			var that = this;
			var type = e.currentTarget.dataset.type;
			var operate = e.currentTarget.dataset.operate;
			app.confirm('确定要'+operate+'吗?', function () {
			  app.post('ApiAdminRestaurantDepositOrder/check', {orderid: that.info.id, type:type}, function (data) {
					if(data.status== 0){
						app.alert(data.msg);return;
					}
			    app.success(data.msg);
			    setTimeout(function () {
			      that.getdata();
			    }, 1000);
			  });
			});
		}
		
  }
};
</script>
<style>
page {position: relative;width: 100%;height: 100%;}
.container{height:auto;overflow:hidden;position: relative; padding-bottom: 20rpx;}

.form{ width:94%;margin:0 3%;border-radius:5px;padding:20rpx 20rpx;padding: 0 3%;background: #FFF;margin-top:20rpx}
.form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:98rpx;line-height:98rpx;font-size:30rpx}
.form-item:last-child{border:0}
.form-item .label{color: #000;width:200rpx;}
.form-item .input{flex:1;color: #000;text-align:right}
.form-item .f2{flex:1;color: #000;text-align:right}
.form-item .picker{height: 60rpx;line-height:60rpx;margin-left: 0;flex:1;color: #000;}

.btn{width:94%;margin:0 3%;margin-top:40rpx;height:90rpx;line-height:90rpx;text-align:center;background: linear-gradient(90deg, #FF7D15 0%, #FC5729 100%);color:#fff;font-size:32rpx;font-weight:bold;border-radius:10rpx}
.btn2 {background: #FFEEEE;border: 1px solid #FF9595;border-radius: 8rpx; color: #E34242;}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}

.expressinfo { width: 96%;margin:0 2%;margin-top:20rpx;padding:6rpx 0;padding:6rpx 0; background: #fff;border-radius:8px}
.expressinfo .content{ width: 100%;  background: #fff;display:flex;flex-direction:column;color: #979797;padding:20rpx 40rpx}
.expressinfo .content .on{color: #23aa5e;}
.expressinfo .content .item{display:flex;width: 96%;  margin: 0 2%;border-left: 1px #dadada solid;padding:10rpx 0}
.expressinfo .content .item .f1{ width:40rpx;flex-shrink:0;position:relative}
.expressinfo .content image{width: 30rpx; height: 30rpx; position: absolute; left: -16rpx; top: 22rpx;}
.expressinfo .content .item .f1 image{ width: 30rpx; height: 30rpx;}
.expressinfo .content .item .f2{display:flex;flex-direction:column;flex:auto;}
.expressinfo .content .item .f2 .t1{font-size: 30rpx;}
.expressinfo .content .item .f2 .t1{font-size: 26rpx;}

.takeoutBox .btn {border-radius:44rpx; margin: 0 auto; width: 96%; color: #FFF;}
.takeoutBox { padding-bottom: 30rpx;}
.orderinfo{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:0px dashed #ededed;overflow:hidden}
.orderinfo .item{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;flex-shrink:0}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}.popup__modal{ min-height: 0;position: fixed;} 

</style>