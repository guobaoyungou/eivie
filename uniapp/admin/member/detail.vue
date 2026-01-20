<template>
<view>
	<block v-if="isload">
		<view class="orderinfo">
			<view class="item">
				<text class="t1">ID</text>
				<text class="t2">{{member.id}}</text>
			</view>
			<view class="item">
				<text class="t1">头像</text>
				<view class="t2"><image :src="member.headimg" style="width:80rpx;height:80rpx"></image></view>
			</view>
			<view class="item">
				<text class="t1">昵称</text>
				<text class="t2">{{member.nickname}}</text>
			</view>
			<view class="item">
				<text class="t1">地区</text>
				<text class="t2">{{member.province ? member.province : '' }}{{member.city ? member.city : '' }}</text>
			</view>
			<view class="item">
				<text class="t1">加入时间</text>
				<text class="t2">{{member.createtime}}</text>
			</view>
			<view class="item">
				<text class="t1">姓名</text>
				<text class="t2">{{member.realname ? member.realname : '' }}</text>
			</view>
			<view class="item">
				<text class="t1">电话</text>
				<text class="t2">{{member.tel ? member.tel : '' }}</text>
			</view>
			<view class="item">
				<text class="t1">{{t('余额')}}</text>
				<text class="t2">{{member.money}}</text>
			</view>
			<view class="item">
				<text class="t1">{{t('积分')}}</text>
				<text class="t2">{{member.score}}</text>
			</view>
      <view v-if="member.showxianjin" class="item">
      	<text class="t1">{{t('现金')}}</text>
      	<text class="t2">{{member.xianjin}}</text>
      </view>
			<view class="item">
				<text class="t1">等级</text>
				<text class="t2">{{member.levelname}}</text>
			</view>
			<view class="item" v-if="member.remark">
				<text class="t1">备注</text>
				<text class="t2">{{member.remark}}</text>
			</view>
      <view class="item" v-if="member.mendian_member_levelup_fenhong">
        <text class="t1">门店</text>
        <text class="t2">{{member.mdname?member.mdname:'无'}}</text>
      </view>
			<view class="item" v-if="ordershow" style="justify-content: space-between;">
				<text class="t1" style="color: #007aff;">商城订单</text>
				<view class="flex" @tap="goto" :data-url="'/admin/order/shoporder?mid='+member.id" >{{member.ordercount}}	<text class="iconfont iconjiantou" style="color:#999;font-weight:normal; margin-top: 2rpx;"></text></view>
			</view>	
		</view>
		<view style="width:100%;height:120rpx"></view>
		<view class="bottom" v-if="isNull(admin_user.is_fuwu) || admin_user.is_fuwu==0">
			<view class="btn" @tap="recharge" :data-id="member.id">充值</view>
      <view class="btn" @tap="consume" :data-id="member.id">消费</view>
			<view class="btn" @tap="addscore" :data-id="member.id">加{{t('积分')}}</view>
      <view class="btn" v-if="member.showxianjin" @tap="xianjinrecharge" :data-id="member.id">充值{{t('现金')}}</view>
			<view class="btn" @tap="changelv" :data-id="member.id">修改等级</view>
			<view class="btn" @tap="remark" :data-id="member.id">备注</view>
			<view class="btn" @tap="goto" :data-url="'/admin/member/history?id='+member.id">足迹</view>
			<view class="btn" @tap="goto" :data-url="'richinfo?id='+member.id" v-if="member.showrichinfo">介绍</view>
		</view>
		
		<uni-popup id="rechargeDialog" ref="rechargeDialog" type="dialog">
			<uni-popup-dialog mode="input" title="充值" value="" placeholder="请输入充值金额" @confirm="rechargeConfirm"></uni-popup-dialog>
		</uni-popup>
    <uni-popup id="consumeDialog" ref="consumeDialog" type="dialog">
    	<uni-popup-dialog mode="input" title="消费" value="" placeholder="请输入消费金额" @confirm="consumeConfirm"></uni-popup-dialog>
    </uni-popup>
    
		<uni-popup id="addscoreDialog" ref="addscoreDialog" type="dialog">
			<uni-popup-dialog mode="input" :title="'加'+t('积分')" value="" :placeholder="'请输入增加'+t('积分')+'数'" @confirm="addscoreConfirm"></uni-popup-dialog>
		</uni-popup>
    <uni-popup id="xianjinrechargeDialog" ref="xianjinrechargeDialog" type="dialog">
    	<uni-popup-dialog mode="input" title="充值" value="" placeholder="请输入充值金额" @confirm="xianjinrechargeConfirm"></uni-popup-dialog>
    </uni-popup>
		<uni-popup id="remarkDialog" ref="remarkDialog" type="dialog">
			<uni-popup-dialog mode="input" title="设置备注" value="" placeholder="请输入备注" @confirm="remarkConfirm"></uni-popup-dialog>
		</uni-popup>

		
		<uni-popup id="dialogChangelv" ref="dialogChangelv" type="dialog">
			<view class="uni-popup-dialog">
				<view class="uni-dialog-title">
					<text class="uni-dialog-title-text">请选择等级</text>
				</view>
				<view class="uni-dialog-content">
						<picker @change="levelChange" :value="index2" :range="levelList2" style="width:100%;font-size:28rpx;border: 1px #eee solid;padding:10rpx;height:70rpx;border-radius:4px;flex:1">
							<view class="picker">{{levelList2[index2]}}</view>
						</picker>
				</view>
				<view class="uni-dialog-button-group">
					<view class="uni-dialog-button" @click="dialogChangelvClose">
						<text class="uni-dialog-button-text">取消</text>
					</view>
					<view class="uni-dialog-button uni-border-left" @click="confirmChangelv">
						<text class="uni-dialog-button-text uni-button-color">确定</text>
					</view>
				</view>
			</view>
		</uni-popup>

	</block>
	<popmsg ref="popmsg"></popmsg>
	<loading v-if="loading"></loading>
</view>
</template>

<script>
var app = getApp();

export default {
  data() {
    return {
      isload: false,
      member: "",
			index2:0,
			levelList:[],
			levelList2:[],
			ordershow:false,
      admin_user:{}
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
		getdata:function(){
			var that = this;
			that.loading = true;
			app.post('ApiAdminMember/detail', {mid: that.opt.mid}, function (res) {
				that.loading = false;
				that.member = res.member;
				that.ordershow = res.ordershow
				uni.setNavigationBarTitle({
					title: that.t('会员') + '信息'
				});

				var levelList2 = [];
				for(var i in res.levelList){
					levelList2.push(res.levelList[i].name);
				}
				that.levelList = res.levelList;
				that.levelList2 = levelList2;
        that.admin_user = res.admin_user;
				that.loaded();
			});
		},
    recharge: function (e) {
      this.$refs.rechargeDialog.open();
    },
    rechargeConfirm: function (done,value) {
			this.$refs.rechargeDialog.close();
      var that = this;
      app.post('ApiAdminMember/recharge', {rechargemid:that.opt.mid,rechargemoney:value}, function (data) {
				if (data.status == 0) {
				  app.error(data.msg);
				  return;
				}
        app.success(data.msg);
        setTimeout(function () {
          that.getdata();
        }, 1000);
      });
    },
    xianjinrecharge: function (e) {
      this.$refs.xianjinrechargeDialog.open();
    },
    xianjinrechargeConfirm: function (done,value) {
    	this.$refs.xianjinrechargeDialog.close();
      var that = this;
      app.post('ApiAdminMember/xianjinrecharge', {rechargemid:that.opt.mid,rechargemoney:value}, function (data) {
    		if (data.status == 0) {
    		  app.error(data.msg);
    		  return;
    		}
        app.success(data.msg);
        setTimeout(function () {
          that.getdata();
        }, 1000);
      });
    },
    consume: function (e) {
      this.$refs.consumeDialog.open();
    },
    consumeConfirm: function (done,value) {
    	this.$refs.consumeDialog.close();
      var that = this;
      app.post('ApiAdminMember/recharge', {rechargemid:that.opt.mid,rechargemoney:-value,type:'consume'}, function (data) {
    		if (data.status == 0) {
    		  app.error(data.msg);
    		  return;
    		}
        app.success(data.msg);
        setTimeout(function () {
          that.getdata();
        }, 1000);
      });
    },
    addscore: function (e) {
      this.$refs.addscoreDialog.open();
    },
   addscoreConfirm: function (done,value) {
			this.$refs.addscoreDialog.close();
      var that = this;
      app.post('ApiAdminMember/addscore', {rechargemid:that.opt.mid,rechargescore:value}, function (data) {
        app.success(data.msg);
        setTimeout(function () {
          that.getdata();
        }, 1000);
      });
    },
		remark:function(e){
			this.$refs.remarkDialog.open();
		},
		remarkConfirm: function (done,value) {
			this.$refs.remarkDialog.close();
      var that = this;
      app.post('ApiAdminMember/remark', {remarkmid:that.opt.mid,remark:value}, function (data) {
        app.success(data.msg);
        setTimeout(function () {
          that.getdata();
        }, 1000);
      });
    },
		changelv:function(){
			this.$refs.dialogChangelv.open();
		},
		dialogChangelvClose:function(){
			this.$refs.dialogChangelv.close();
		},
		levelChange:function(e){
			this.index2 = e.detail.value;
		},
		confirmChangelv:function(){
			var that = this
			console.log(this.index2);
			console.log(this.levelList[this.index2]);
			var levelid = this.levelList[this.index2].id
			app.post('ApiAdminMember/changelv', { changemid:that.opt.mid,levelid:levelid}, function (res) {
				app.success(res.msg);
				that.$refs.dialogChangelv.close();
				setTimeout(function () {
					that.getdata();
				}, 1000)
			})
		},
  }
};
</script>
<style>

.orderinfo{ width:94%;margin:20rpx 3%;border-radius:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}

.bottom{ width: 100%; padding: 16rpx 20rpx;background: #fff; position: fixed; bottom: 0px; left: 0px;display:flex;justify-content:flex-end;align-items:center;flex-wrap:wrap}
.bottom .btn{ border-radius:10rpx; padding:10rpx 16rpx;margin-left: 10rpx;border: 1px #999 solid;margin-bottom: 4rpx}

.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 15px 15px 15px;width:100%}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}
</style>