<template>
<view class="container" :style="{backgroundColor:info.bgcolor,minHeight:'100vh'}">
	<block v-if="isload">
		<view class="banner"><image :src="info.banner" mode="widthFix"></image></view>
		<view class="activity">
			<view class="activity-amin">
				<view class="h2" style="margin-bottom: 0;">我的奖品</view>
				<view class="tab-search" v-if="st==0" @tap="changest(1)" >查看中奖记录</view>
				<view class="tab-search" v-if="st==1" @tap="changest(0)" >查看所有记录</view>
				<view class="tb0">
					<view class="tr">
						<view class="td">参与时间 </view>
						<view class="td">活动信息</view>
						<view class="td">{{t('积分')}}</view>
						<view class="td">投注号码</view>
						<view class="td">领奖状态</view>
					</view>
					<view v-for="(item, index) in datalist" :key="index" class="tr">
						<view class="td td2">{{item.createtime}}</view>
						<view class="td td2">{{item.name}}</view>
						<view class="td td2">{{item.score}}</view>
						<view class="td td2">{{item.code}}</view>
						<view class="td td2">
							<text v-if="item.status==1" @tap="duijiang" :data-k="index" :data-id="item.id" style="background-color:#fb5a43;padding:4rpx 8rpx">兑奖</text>
							<text v-else-if="item.status==2" @tap="duijiang" :data-k="index" :data-id="item.id" style="background-color:#fb5a43;padding:4rpx 8rpx">查看兑奖</text>
							<text v-else="item.status!=1">{{item.status_str}}</text>
						</view>
					</view>
				</view>
				<view v-if="datalist.length==0" style="width:100%;padding:40rpx 0;text-align:center;color:#f19132">暂无中奖记录~</view>
				<view @tap="goback" class="goback">返回</view>
			</view>
		</view>
		
		<view id="mask-rule2" v-if="maskshow && record['status']==2">
			<view class="box-rule" style="height:900rpx">
				<view class="h2">兑奖信息</view>
				<view id="close-rule2" :style="'background: no-repeat center / contain;background-image: url('+pre_url+'/static/img/dzp/close.png);'" @tap="changemaskshow"></view>
				<view class="con">
					<view class="text" style="text-align:center">
						<view id="linkinfo" style="text-align: left;margin-left:10%;">
							<view>联系人：{{record['linkman']}}</view>
							<view>联系电话：{{record['tel']}}</view>
							<view>收货区域：{{record['region']}}</view>
							<view>详细地址：{{record['address']}}</view>
							<view>订单编号：{{record['ordernum']}}</view>
						</view>
						<!-- <image :src="record.hexiaoqr" style="width:80%" id="hexiaoqr" mode="widthFix"></image>
						<view>请出示兑奖码给核销员进行兑奖</view> -->
					</view>
				</view>
			</view>
		</view>
		<view id="mask-rule1" v-if="maskshow && record['status']==1">
			<view class="box-rule" style="height:640rpx">
				<view class="h2">请填写兑奖信息</view>
				<view id="close-rule1" :style="'background: no-repeat center / contain;background-image: url('+pre_url+'/static/img/dzp/close.png);'" @tap="changemaskshow"></view>
				<view class="con">
					<form class @submit="formsub">
					<view class="pay-form" style="margin-top:0.18rem">
						<view class="item flex-y-center">
							<view class="f1">收货人姓名：</view>
							<view class="f2 flex flex1">
								<block>
									<input type="text" name="linkman" class="input" placeholder="请输入收货人姓名"></input>
								</block>
							</view>
						</view>
						<view class="item flex-y-center">
							<view class="f1">联系电话：</view>
							<view class="f2 flex flex1">
								<block>
									<input type="text" name="tel" class="input" placeholder="请输入联系电话"></input>
								</block>
							</view>
						</view>
						<view class="item flex-y-center">
							<view class="f1">收货地址：</view>
							<view class="f2 flex flex1">
								<block>
									<uni-data-picker style="color: #333;" class="flex1" :border='false' :localdata="items" popup-title="请选择省市区"  @change="region_editorBindPickerChange" data-tplindex="0">
										<input type="text" name="region" :value="picker_region" placeholder="请选择省市区" placeholder-style="color:#fff;font-size:28rpx" style="border: none;color:#fff;font-size:28rpx;padding: 0rpx;"/>
									</uni-data-picker>
								</block>
							</view>
						</view>
						<view class="item flex-y-center">
							<view class="f1">详细地址：</view>
							<view class="f2 flex flex1">
								<block>
									<input type="text" name="address" class="input" placeholder="请输入详细地址"></input>
								</block>
							</view>
						</view>
						<view style="padding:0 40px 0 80px">
							<button class="subbtn" form-type="submit">确 定</button>
						</view>
					</view>
					</form>
				</view>
			</view>
		</view>
	</block>
	<nodata v-if="nodata"></nodata>
	<nomore v-if="nomore"></nomore>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();

export default {
  data() {
    return {
		 pagenum: 1,
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			
			pre_url:app.globalData.pre_url,
      st: 0,
      datalist: [],
      pagenum: 1,
      maskshow: false,
      record: "",
			info:{},
      formdata: "",
			selectIndex:null,
			picker_tmer:'',
			picker_date:'',
			picker_region:'',
			items: [],
			formcontent:[],
			nodata: false,
			nomore: false,
			st:0
    };
  },

  onLoad: function (opt) {
		let that = this;
		app.get('ApiIndex/getCustom',{}, function (customs) {
			var url = app.globalData.pre_url+'/static/area.json';
			if(customs.data.includes('plug_zhiming')) {
				url = app.globalData.pre_url+'/static/area_gaoxin.json';
			}
			uni.request({
				url: app.globalData.pre_url+'/static/area.json',
				data: {},
				method: 'GET',
				header: { 'content-type': 'application/json' },
				success: function(res2) {
					that.items = res2.data
				}
			});
		});
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 0;
		this.getdata();
  },
  onPullDownRefresh: function () {
  		this.getdata();
  	},
  onReachBottom: function () {
		if (!this.nodata && !this.nomore) {
		  this.pagenum = this.pagenum + 1;
		  this.getdata(true);
		}
	},
  methods: {
		time_editorBindPickerChange:function(e){
			this.picker_tmer = e.detail.value
		},
		selector_editorBindPickerChange:function(e){
			this.selectIndex = e.detail.value;
		},
		date_editorBindPickerChange:function(e){
			this.picker_date = e.detail.value;
		},
		region_editorBindPickerChange:function(e){
			const value = e.detail.value
			this.picker_region = value[0].text + ',' + value[1].text + ',' + value[2].text;
		},
		getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
			var that = this;
			var pagenum = that.pagenum;
			that.loading = true;
			app.post('ApiChoujiangManren/myprize', {pagenum: pagenum,st:that.st}, function (res) {
				that.loading = false;
				// if(res.info.formcontent){
				// 	res.info.formcontent = JSON.parse(res.info.formcontent);
				// }
				var data = res.datalist;
				if (pagenum == 1) {
					that.info = res.info;
					that.datalist = data;
					if (data.length == 0) {
						that.nodata = true;
					}
					that.loaded();
				}else{
					if (data.length == 0) {
						that.nomore = true;
					} else {
						var datalist = that.datalist;
						var newdata = datalist.concat(data);
						that.datalist = newdata;
					}
				}
				that.loaded();
			});
		},
    duijiang: function (e) {
		var that = this;
		that.picker_tmer = '';
		that.picker_date = '';
		that.picker_region = '';
		that.selectIndex = null;
		var k = e.currentTarget.dataset.k;
		var id = e.currentTarget.dataset.id;
		var record = that.datalist[k];
		// var formcontent = JSON.parse(record.formcontent);
		// if(record.formdata){
		// 	var formdata = JSON.parse(record.formdata);
		// }
		
		// console.log(formdata);
		that.record = record;
		// that.formcontent = formcontent;
		// that.formdata = formdata;
		that.maskshow = true;
    },
    changemaskshow: function () {
      var that = this;
      that.maskshow = !that.maskshow;
    },
    formsub: function (e) {
      var that = this;
      console.log(e);
      var subdata = e.detail.value;
      var formcontent = that.formcontent;
      var record = that.record;
      var formdata = {};
	  console.log(subdata);
		if(subdata['linkman']==''){
			app.alert('请填写收货人');
			return;
		}
		if(subdata['tel']==''){
			app.alert('请填写联系电话');
			return;
		}
		if(that.picker_region==''){
			app.alert('请选择收货地址');
			return;
		}
		subdata['region'] = that.picker_region;
		if(subdata['address']==''){
			app.alert('请填写详细地址');
			return;
		}
		subdata['rid'] = record.id;
      console.log(formdata);
      app.post("ApiChoujiangManren/subinfo", subdata, function (res) {
        if (res.status == 0) {
          app.alert(res.msg);
        } else {
          that.changemaskshow();
          app.success(res.msg);
          that.getdata();
        }
      });
    },
	changest:function(st){
		this.st = st;
		this.getdata(false);
	}
  }
};
</script>
<style>
	.container{min-height: 100vh;}
.banner{ width:100%;padding:0 5%}
.banner image{ display:block;width:100%;}
.activity{padding:0 0 45rpx 0;margin-top:20rpx}
.activity-amin{width:94%; margin:0 auto;}
.activity-amin .h2{ margin:0 auto 30rpx auto;width:330rpx;height: 60rpx;background-color: #fc8209;text-align: center;line-height:60rpx;font-size: 30rpx;color: #ffffff;border-radius: 26rpx;letter-spacing:14rpx}
.wt1{display:block; border:none; background-color:#FFF; padding:22rpx 22rpx; border-radius:8rpx; font-size: 30rpx; margin-bottom:60rpx;width:100%}
.wt4{width:100%;background-color:#fb3a13; color:#FFF;font-size:30rpx;margin-top: 60rpx;}
.tb0{ width:100%; margin-bottom:6%;font-size:24rpx}
.tb0 .tr{width:100%;display:flex;border-bottom:1px solid #fff}
.tb0 .tr .td{width:20%;background-color:#f19132;line-height:80rpx;text-align:center; color:#fff886;}
.tb0 .tr .td:nth-child(1){width:25%; }
.tb0 .tr .td:nth-child(2){width:25%; }
.tb0 .tr .td:nth-child(3){width:10%; }
.tb0 .tr .td:nth-child(4){width:22%; }
.tb0 .tr .td:nth-child(5){width:18%; }
.tb0 .tr .td2{padding:20rpx 0; text-align:center; color:#FFF; background-color:#f19c48}
.goback{display:block;color:#fff;background-color:#fb3a13;margin:20rpx auto 40rpx auto;width:90%;padding:20rpx 0;text-align:center;font-size:36rpx;border-radius:15rpx;}

#mask-rule1{position: fixed;top: 0;z-index: 10;width: 100%;max-width:640px;height: 100%;background-color: rgba(0, 0, 0, 0.85);}
#mask-rule1 .box-rule {background-color: #f58d40;position: relative;margin: 30% auto;padding-top:40rpx;width: 90%;height:700rpx;border-radius:20rpx;}
#mask-rule1 .box-rule .h2{width: 100%;text-align: center;line-height:34rpx;font-size: 34rpx;font-weight: normal;color: #fff;}
#mask-rule1 #close-rule1{position: absolute;right:34rpx;top: 38rpx;width: 40rpx;height: 40rpx;}
#mask-rule1 .con {overflow: auto;position: relative;margin: 40rpx auto;padding-right: 15rpx;width:580rpx;height: 82%;line-height: 48rpx;font-size: 26rpx;color: #fff;}
#mask-rule1 .con .text {position: absolute;top: 0;left: 0;width: inherit;height: auto;}

#mask-rule2{position: fixed;top: 0;z-index: 10;width: 100%;max-width:640px;height: 100%;background-color: rgba(0, 0, 0, 0.85);}
#mask-rule2 .box-rule {background-color: #f58d40;position: relative;margin: 30% auto;padding-top:40rpx;width: 90%;height:700rpx;border-radius:20rpx;}
#mask-rule2 .box-rule .h2{width: 100%;text-align: center;line-height:34rpx;font-size: 34rpx;font-weight: normal;color: #fff;}
#mask-rule2 #close-rule2{position: absolute;right:34rpx;top: 38rpx;width: 40rpx;height: 40rpx;}
#mask-rule2 .con {overflow: auto;position: relative;margin: 20rpx auto;padding-right: 15rpx;width:580rpx;height:90%;line-height: 48rpx;font-size: 26rpx;color: #fff;}
#mask-rule2 .con .text {position: absolute;top: 0;left: 0;width: inherit;height: auto;}

.pay-form .item{width:100%;padding:0 0 10px 0;color:#fff;}
.pay-form .item:last-child{border-bottom:0}
.pay-form .item .f1{width:80px;text-align:right;padding-right:10px}
.pay-form .item .f2 input[type=text]{width:100%;height:35px;padding:2px 5px;border:1px solid #ddd;border-radius:2px}
.pay-form .item .f2 textarea{width:100%;height:60px;padding:2px 5px;border:1px solid #ddd;border-radius:2px}
.pay-form .item .f2 select{width:100%;height:35px;padding:2px 5px;border:1px solid #ddd;border-radius:2px}
.pay-form .item .f2 label{height:35px;line-height:35px;}
.subbtn{width:100%;background:#fb3a13;font-size: 30rpx;padding:0 22rpx;border-radius: 8rpx;color:#FFF;}

.tab-search{width: 100%;text-align: right;margin-bottom: 20rpx;
    color: white;
}
</style>