<template>
<view class="container" style="background-color:#fce243;min-height:100vh">
	<block v-if="isload">
		<view class="banner"><image :src="info.banner" mode="widthFix"></image></view>
		<view class="activity">
			<view class="activity-amin">
				<view class="h2">我的奖品</view>
				<view class="tb0">
					<view class="tr">
						<view class="td">中奖时间 </view>
						<view class="td">中奖奖品</view>
						<view class="td">领奖状态</view>
						<view class="td">操作</view>
					</view>
					<view v-for="(item, index) in datalist" :key="index" class="tr">
						<view class="td td2">{{item.createtime}}</view>
						<view class="td td2">{{item.jxmc}}</view>
						<view class="td td2"><block v-if="item.status==0">未领奖</block><block v-else>已领奖</block></view>
						<view class="td td2">
							<text v-if="item.status==0" @tap="duijiang" :data-k="index" :data-id="item.id" style="background-color:#fb5a43;padding:4rpx 8rpx">兑奖</text>
							<text v-if="item.jxtp==3 && item.status==1" @tap="goto" data-url="/pagesExt/coupon/mycoupon" style="background-color:#fb6a43;padding:4rpx 8rpx">查看</text>
						</view>
					</view>
				</view>
				<view v-if="!datalist" style="width:100%;padding:40rpx 0;text-align:center;color:#f19132">暂无中奖记录~</view>
				<view @tap="goback" class="goback">返回</view>
			</view>
		</view>
		
		<view id="mask-rule2" v-if="maskshow && formdata">
			<view class="box-rule" style="height:900rpx">
				<view class="h2">兑奖信息</view>
				<view id="close-rule2" :style="'background: no-repeat center / contain;background-image: url('+pre_url+'/static/img/dzp/close.png);'" @tap="changemaskshow"></view>
				<view class="con">
					<view class="text" style="text-align:center">
						<view id="linkinfo" style="text-align: left;margin-left:10%;">
							<view>奖品：{{record.jxmc}}</view>
							<view v-for="(item, index) in formdata" :key="index">{{index}}:{{item}}</view>
						</view>
						<image :src="record.hexiaoqr" style="width:80%" id="hexiaoqr" mode="widthFix"></image>
						<view>请出示兑奖码给核销员进行兑奖</view>
					</view>
				</view>
			</view>
		</view>
		<view id="mask-rule1" v-if="maskshow && !formdata">
			<view class="box-rule" style="height:640rpx">
				<view class="h2">请填写兑奖信息</view>
				<view id="close-rule1" :style="'background: no-repeat center / contain;background-image: url('+pre_url+'/static/img/dzp/close.png);'" @tap="changemaskshow"></view>
				<view class="con">
					<form class @submit="formsub">
					<view class="pay-form" style="margin-top:0.18rem">
						<view v-for="(item, idx) in info.formcontent" :key="idx" class="item flex-y-center">
							<view class="f1">{{item.val1}}：</view>
							<view class="f2 flex flex1">
								<block v-if="item.key=='input'">
									<input type="text" :name="'form' + idx" class="input" :placeholder="item.val2"></input>
								</block>
								<block v-if="item.key=='textarea'">
									<textarea :name="'form' + idx" class="textarea" :placeholder="item.val2"></textarea>
								</block>
								<block v-if="item.key=='radio'">
									<radio-group class="radio-group" :name="'form' + idx">
										<label v-for="(item1, index) in item.val2" :key="index">
												<radio :value="item1"></radio>{{item1}}
										</label>
									</radio-group>
								</block>
								<block v-if="item.key=='checkbox'">
									<checkbox-group :name="'form' + idx">
										<label v-for="(item1, index) in item.val2" :key="index">
											<checkbox :value="item1" class="xyy-zu"></checkbox>{{item1}}
										</label>
									</checkbox-group>
								</block>
								<block v-if="item.key=='switch'">
									<switch class="xyy-zu" value="1" :name="'form' + idx"></switch>
								</block>
								<block v-if="item.key=='selector'">
									<picker mode="selector" :name="'form' + idx" class="xyy-pic" :range="item.val2" @change="selector_editorBindPickerChange" :data-idx="idx" data-tplindex="0">
										<view class="picker" v-if="item.val2[selectIndex]"> {{item.val2[selectIndex]}}</view>
										<view v-else>请选择</view>
									</picker>
								</block>
								<block v-if="item.key=='time'">
									<picker mode="time" :name="'form' + idx" class="xyy-pic" @change="time_editorBindPickerChange" :data-idx="idx" data-tplindex="0">
										<view class="picker" v-if="picker_tmer">{{picker_tmer}}</view>
										<view v-else>选择时间</view>
									</picker>
								</block>
								<block v-if="item.key=='date'">
									<picker mode="date" :name="'form' + idx" class="xyy-pic" @change="date_editorBindPickerChange" :data-idx="idx" data-tplindex="0">
										<view class="picker" v-if="picker_date"> {{picker_date}}</view>
										<view v-else>选择日期</view>
									</picker>
								</block>
								<block v-if="item.key=='region'">
									<uni-data-picker style="color: #333;" class="flex1" :localdata="items" popup-title="请选择省市区"  @change="region_editorBindPickerChange"	:data-idx="idx" data-tplindex="0">
										<input type="text" :name="'form'+idx" :value="picker_region" placeholder="请选择省市区" placeholder-style="color:#fff;font-size:28rpx" style="border: none;font-size:28rpx;padding: 0rpx;color:#fff;"/>
									</uni-data-picker>
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
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			pre_url:app.globalData.pre_url,

      st: 0,
			info:{},
      datalist: [],
      pagenum: 1,
      maskshow: false,
      record: "",
      formdata: "",
			selectIndex:null,
			picker_tmer:'',
			picker_date:'',
			picker_region:'',
			items: [],
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
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
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
		getdata: function () {
			var that = this;
			app.get('ApiChoujiang/myprize', {hid: that.opt.hid}, function (res) {
				res.info.formcontent = JSON.parse(res.info.formcontent);
				that.info = res.info;
				that.datalist = res.datalist;
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
      var formdata = JSON.parse(record.formdata);
      that.record = record;
      that.formdata = formdata;
      that.maskshow = true;
    },
    changemaskshow: function () {
      var that = this;
      that.maskshow = !that.maskshow;
    },
    formsub: function (e) {
      var that = this;
      var subdata = e.detail.value;
      var formcontent = that.info.formcontent;
      var record = that.record;
      var formdata = {};

      for (var i = 0; i < formcontent.length; i++) {
        //console.log(subdata['form' + i]);
        if (formcontent[i].val3 == 1 && (subdata['form' + i] === '' || subdata['form' + i] === undefined || subdata['form' + i].length == 0)) {
          app.alert(formcontent[i].val1 + ' 必填');
          return;
        }

        if (formcontent[i].key == 'switch') {
          if (subdata['form' + i] == false) {
            subdata['form' + i] = '否';
          } else {
            subdata['form' + i] = '是';
          }
        }

        if (formcontent[i].key == 'selector') {
          subdata['form' + i] = formcontent[i].val2[subdata['form' + i]];
        }

        var nowformdata = {};
        formdata[formcontent[i].val1] = subdata['form' + i];
      }

      console.log(formdata);
      app.post("ApiChoujiang/subinfo/rid/" + record.id, {
        formcontent: formdata
      }, function (res) {
        if (res.status == 0) {
          app.alert(res.msg);
        } else {
          that.changemaskshow();
          app.success(res.msg);
          that.getdata();
        }
      });
    }
  }
};
</script>
<style>
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
.tb0 .tr .td:nth-child(1){width:32%; }
.tb0 .tr .td:nth-child(2){width:32%; }
.tb0 .tr .td:nth-child(3){width:18%; }
.tb0 .tr .td:nth-child(4){width:18%; }
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
</style>