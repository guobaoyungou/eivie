<template>
<view class="container">
	<block v-if="isload">
		<form @submit="subform">
			<view class="content">
				<view class="info-item">
					<view class="t1">预定时间</view>
					<view class="t2" @tap="chooseTime"><input type="text" placeholder="请选择时间" name="time" :value="chooseTimeStr"></view>
					<image class="t3" @tap="chooseTime" :src="pre_url+'/static/img/arrowright.png'"/>
				</view>
				<view class="info-item">
					<view class="t1">人数</view>
					<view class="t2">
						<picker @change="numChange" :value="nindex" :range="numArr" name="renshu">
							<view class="picker">{{numArr[nindex]}}</view>
						</picker>
					</view>
					<image class="t3" :src="pre_url+'/static/img/arrowright.png'"/>
				</view>
				<view class="info-item">
					<view class="t1">餐桌</view>
					<view class="t2" @tap="goto" :data-url="'bookingTableList?bid='+opt.bid"><input type="text" :value="tableName" placeholder="请选择"></view>
					<image class="t3" @tap="goto" :data-url="'bookingTableList?bid='+opt.bid" :src="pre_url+'/static/img/arrowright.png'"/>
				</view>
			</view>
			<view class="content">
				<view class="info-item">
					<view class="t1">顾客姓名</view>
					<view class="t2"><input type="text" @input="input" data-name="linkman" name="linkman" :value="linkman" placeholder="请输入姓名"></view>
				</view>
				<view class="info-item">
					<view class="t1">手机号</view>
					<view class="t2"><input type="number" @input="input" data-name="tel" name="tel" :value="tel" placeholder="请输入手机号"></view>
				</view>
			</view>
			
			<view class="content">
				<view class=" remark" >
					<view class="t1">备注</view>
					<view class="">
					<textarea @input="input" data-name="message" name="message" :value="message" placeholder="如您有其他需求请填写" placeholder-style="color:#ABABABFF"></textarea>
					</view>
				</view>
			</view>
			
			<view style="padding:30rpx 0">
				<button form-type="submit" class="set-btn" style="background:#FE5B07">添加预定</button>
			</view>
		</form>
		
		<view v-if="pstimeDialogShow" class="popup__container">
			<view class="popup__overlay" @tap.stop="hideTimeDialog"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请选择时间</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hideTimeDialog"/>
				</view>
				<view class="popup__content">
					<view class="pstime-item" v-for="(item, index) in timeArr" :key="index" @tap="timeRadioChange" :data-index="index">
						<view class="flex1">{{item.title}}</view>
						<view class="radio" :style="''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
					</view>
				</view>
			</view>
		</view>
		
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
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
			
			userinfo:{},
      pstimeDialogShow: false,
			timeArr:[],
			chooseTimeStr:'',
			chooseTimeIndex:0,
			nindex:0,
			numArr:[1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20],
			tableName:'',
			linkman:'',
			tel:'',
			message:'',
			pre_url:app.globalData.pre_url,
    };
  },

  onLoad: function (opt) {
		if(uni.getStorageSync('restaurant_booking') == '') {
			uni.setStorageSync('restaurant_booking', {})
		}
		this.opt = app.getopts(opt);
		this.opt.bid = this.opt.bid ? this.opt.bid : 0;
		var tempdata = uni.getStorageSync('restaurant_booking');
		this.chooseTimeStr = tempdata.chooseTimeStr;
		this.chooseTimeIndex = tempdata.chooseTimeIndex;
		this.nindex = tempdata.nindex;
		this.linkman = tempdata.linkman;
		this.tel = tempdata.tel;
		this.message = tempdata.message;
		
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiAdminRestaurantBooking/add', {bid:that.opt.bid,tableId:that.opt.tableId}, function (data) {
				that.loading = false;
				that.timeArr = data.timeArr;
				that.tableName = data.table ? data.table.name : '';
				that.loaded();
			});
		},
		subform: function (e) {
			var that = this;
			var info = e.detail.value;
			info.tableId = that.opt.tableId;
			info.bid = that.opt.bid;
			info.renshu = that.numArr[that.nindex]
			// console.log(info);return;
			if (info.time == '') {
				app.error('请选择时间');
				return false;
			}
			if (info.renshu <= 0) {
				app.error('请选择人数');
				return false;
			}
			if (info.tableId == 0) {
				app.error('请选择餐桌');
				return false;
			}
			if (info.linkman == '') {
				app.error('请填写姓名');
				return false;
			}
			if (info.tel == '') {
				app.error('请填写手机号');
				return false;
			}
			app.showLoading('提交中');
			app.post("ApiAdminRestaurantBooking/add", {info: info}, function (res) {
				app.showLoading(false);
				if(res.status == 0) {
					app.alert(res.msg);
				}
				uni.removeStorageSync('restaurant_booking');
				app.alert(res.msg,function(){
					if(res.payorderid)
					app.goto('/pagesExt/pay/pay?id='+res.payorderid);
					else
					app.goto('detail?id='+res.id);
				});
		  });
		},
		chooseTime: function (e) {
		  var that = this;
		  var allbuydata = that.allbuydata;
		  var bid = e.currentTarget.dataset.bid;
		  var timeArr = that.timeArr;
		  var itemlist = [];
		  for (var i = 0; i < timeArr.length; i++) {
		    itemlist.push(timeArr[i].title);
		  }
		  if (itemlist.length == 0) {
		    app.alert('当前没有可选时间段');
		    return;
		  }
		  that.nowbid = bid;
		  that.pstimeDialogShow = true;
		  that.pstimeIndex = -1;
		},
		timeRadioChange: function (e) {
		  var that = this;
			var allbuydata = that.allbuydata;
		  var pstimeIndex = e.currentTarget.dataset.index;
			console.log(pstimeIndex)
			var nowbid = that.nowbid;
			var chooseTime = that.timeArr[pstimeIndex];
			that.chooseTimeIndex = pstimeIndex;
			that.chooseTimeStr = that.timeArr[pstimeIndex].value;
			var tempdata = uni.getStorageSync('restaurant_booking');
			tempdata.chooseTimeIndex = pstimeIndex;
			tempdata.chooseTimeStr = that.timeArr[pstimeIndex].value;
			uni.setStorageSync('restaurant_booking', tempdata);
		  that.pstimeDialogShow = false;
		},
    hideTimeDialog: function () {
      this.pstimeDialogShow = false;
    },
		numChange: function (e) {
		  this.nindex = e.detail.value;
			var tempdata = uni.getStorageSync('restaurant_booking');
			tempdata.nindex = this.nindex;
			uni.setStorageSync('restaurant_booking', tempdata);
		},
		input: function(e){
			var value = e.target.value;
			// console.log(value)
			var name = e.currentTarget.dataset.name;
			// console.log(e)
			var tempdata = uni.getStorageSync('restaurant_booking');
			tempdata[name] = value;
			uni.setStorageSync('restaurant_booking', tempdata);
		}
  }
};
</script>
<style>
.content{width:94%;margin:20rpx 3%;background:#fff;border-radius:5px;padding:0 20rpx;}
.info-item{ display:flex;align-items:center;width: 100%; background: #fff;padding:0 3%;  border-bottom: 1px #f3f3f3 solid;height:120rpx;line-height:96rpx}
.info-item:last-child{border:none}
 .t1{ width: 200rpx;color: #333;font-weight:bold;height:96rpx;line-height:96rpx}
.info-item .t2{ color:#444444;text-align:right;flex:1;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.info-item .t3{ width: 26rpx;height:26rpx;margin-left:20rpx}
.remark {
	height: 320rpx;padding:0 3%; 
}
.remark .t1 {width: 100%; flex: inherit;}
.remark textarea { height: 100px;}
.set-btn{width: 90%;margin:0 5%;height:96rpx;line-height:96rpx;font-size:34rpx;border-radius:10rpx;color:#FFFFFF;}
picker {height: 96rpx;}

.pstime-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.pstime-item .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.pstime-item .radio .radio-img{width:100%;height:100%}

</style>